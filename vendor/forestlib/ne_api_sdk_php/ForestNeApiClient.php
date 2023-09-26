<?php
use Psr\Log\LoggerInterface;
use Goutte\Client;

/**
 * Nextengine API SDK(http://api.next-e.jp/).
 *
 * @since 2013/10/10
 * @copyright Hamee Corp. All Rights Reserved.
 *
*/

class ForestNeApiClient Extends neApiClient
{
	/** @var  LoggerInterface */
	protected $logger;

	/** @var string */
	protected $loginCode;

	/** @var string */
	protected $password;

  protected $loginRetryCount = 0;
  protected $loginRetryMaxCount = 1; // 認証失敗した場合に、再ログインを試みる回数

  // API実行で失敗した場合の再ログイン成否
  protected $reLogin = false;
  protected $reLoginSuccess = false;


	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	public function log($message, $level = 'info')
	{
		if (!empty($this->logger)) {
			$this->logger->{$level}($message);
		}
	}

	public function getWebClient($config = null)
	{
		$client = new Client($config);
		return $client;
	}

	public function setUserAccount($code, $password)
	{
		$this->loginCode = $code;
		$this->password = $password;
	}



    /**
    * ネクストエンジンログインを実施し、かつAPIを実行し、結果を返します。
    *
    * @param    string    $path            呼び出すAPIのパスです。/から指定して下さい。
    * @param    array    $api_params        呼び出すAPIの必要に応じてパラメータ(連想配列)です。
    *                                    パラメータが不要な場合、省略又はNULLを指定して下さい。
    * @param    string    $redirect_uri    インスタンスを作成した後、リダイレクト先を変更したい
    *                                    場合のみ設定して下さい。
    * @return    array  実行結果。内容は呼び出したAPIにより異なります。
    */
    public function apiExecute($path, $api_params = array(), $redirect_uri = NULL) {

        // 再ログインフラグ、API再実行フラグをOFF
        $this->loginRetryCount = 0;
        $this->reLogin = false;
        $this->reLoginSuccess = false;

        if( !is_null($redirect_uri) ) {
            $this->_redirect_uri = $redirect_uri ;
        }

        // access_tokenが未発行の場合、メンバ変数に設定
        if( is_null($this->_access_token) ) {

          $this->log('api execute : no access token');

            // uid及びstateをメンバ変数に設定
            $this->setUidAndState() ;

          $this->log('api execute : no access token / after uid an state');

          // uid及びstateを元にaccess_tokenを発行
            $response = $this->setAccessToken() ;
            if( $response['result'] !== self::RESULT_SUCCESS ) {
                return($response) ;
            }
        }

        $api_params['access_token'] = $this->_access_token ;
        if( isset($this->_refresh_token) ) {
            $api_params['refresh_token'] = $this->_refresh_token ;
        }

        // APIを実行して処理結果を返す
        $response = $this->post(self::API_SERVER_HOST.$path, $api_params) ;

        if( isset($response['access_token']) ) {
            $this->_access_token = $response['access_token'] ;
        }
        if( isset($response['refresh_token']) ) {
            $this->_refresh_token = $response['refresh_token'] ;
        }

        // リダイレクトの可能性があるのでチェックする(成功・失敗に関わらず結果を返して終了)
        $this->responseCheck($response);

        $this->log('reLogin : ' . ($this->reLogin ? 'true' : 'false'));
        $this->log('reLoginSuccess : ' . ($this->reLoginSuccess ? 'true' : 'false'));

        // 再ログインで成功していれば、もう一度同じAPIを実行する
        if ($this->reLogin && $this->reLoginSuccess) {
          $this->log('再ログイン後、APIを再実行 ' . $path);
          // APIを実行して処理結果を返す
          $api_params['access_token'] = $this->_access_token;
          $api_params['refresh_token'] = $this->_refresh_token;
          $response = $this->post(self::API_SERVER_HOST.$path, $api_params) ;

          if( isset($response['access_token']) ) {
            $this->_access_token = $response['access_token'] ;
          }
          if( isset($response['refresh_token']) ) {
            $this->_refresh_token = $response['refresh_token'] ;
          }
        }

        $this->responseCheck($response);
        return($response) ;
    }

    /**
    * ネクストエンジンログインが不要なAPIを実行します。
    *
    * @param    string    $path            呼び出すAPIのパスです。/から指定して下さい。
    * @param    array    $api_params        呼び出すAPIの必要に応じてパラメータ(連想配列)です。
    *                                    パラメータが不要な場合、省略又はNULLを指定して下さい。
    *
    * @return    array  実行結果。内容は呼び出したAPIにより異なります。
    */
    public function apiExecuteNoRequiredLogin($path, $api_params = array()) {
        $api_params['client_id'] = $this->_client_id ;
        $api_params['client_secret'] = $this->_client_secret ;

        $response = $this->post(self::API_SERVER_HOST.$path, $api_params) ;
        return($response) ;
    }

    ///////////////////////////////////////////////////////
    // 以下は全てSDKの内部処理用のメソッドです
    ///////////////////////////////////////////////////////
    public function __destruct() {
        curl_close($this->_curl);
    }

    /**
     * レスポンスチェックで失敗した場合に再ログインを試みる
     * @see http://api.next-e.jp/refresh_token.php
     * （抜粋）
     * access_tokenの有効期限は1日、refresh_tokenの有効期限は3日です。
     * 有効期限は、「最初にaccess_tokenを発行した日時、又は最後にaccess_tokenの有効期限が切れて
     * access_tokenが更新された日時からの日数」です
     * バッチ等認証なしで定期的にAPIを利用する場合、2日より前に定期的にAPIを実施し有効期限が切れないように
     * 利用することを推奨します
     * （refresh_tokenの有効期限も切れた際は、access_tokenの有効期限切れと同じ002004のエラーになります。
     *   新しいaccess_tokenが発行されたにもかかわらず、古いaccess_tokenで実行した場合は、002002のエラーになります）
     *
     * @param $response
     * @return bool|int|void
     * @throws Exception
     */
    protected function responseCheck($response) {
        switch($response['result']) {
        case self::RESULT_ERROR :         //エラー
          $this->log('レスポンスチェック結果: ' .  print_r($response, true));
          if (in_array($response['code'], array('002002', '002004'))) {
              $this->loginRetryCount++;
              $this->log(sprintf('access_tokenエラー。再ログイン %d / %d', $this->loginRetryCount, $this->loginRetryMaxCount));
              if ($this->loginRetryCount <= $this->loginRetryMaxCount) {
                $this->reLogin = true;
                return $this->redirectNeLogin();
              }
            }
            return(false) ;
        case self::RESULT_REDIRECT :    // リダイレクト
            $this->reLogin = true;
            return $this->redirectNeLogin() ;
        case self::RESULT_SUCCESS :     // 成功
            return(true) ;
        default :
            $this->log('レスポンスチェック結果: ' .  print_r($response, true));
            throw new Exception('SDKで例外が発生しました。クライアントID・シークレットや指定したパスが正しいか確認して下さい。' . print_r($response, true)) ;
        }
    }

    protected function redirectNeLogin() {
        $params = array() ;
        $params['client_id'] = $this->_client_id ;
        $params['client_secret'] = $this->_client_secret ;
        $params['redirect_uri'] = $this->_redirect_uri ;

        $url = self::NE_SERVER_HOST.self::PATH_LOGIN.'?'.$this->getUrlParams($params) ;

      $this->log('redirectNeLogin : ' . $url);

      $config = array('useragent' => 'forest ua/1.1');
      $client = $this->getWebClient($config);
      $crawler = $client->request('GET', $url);

      $form = $crawler->selectButton('ログイン')->form();

      try {
        $form['user[login_code]'] = $this->loginCode;
        $form['user[password]'] = $this->password;

        $client->submit($form);

      } catch (Exception $e) {
        $this->log(print_r($e->getMessage(), true));
        throw $e;
      }

      $response = $client->getResponse();
      $status = $response->getStatus();

      if ($status == '200') {

        $data = json_decode($response->getContent(), true);

        if (!$data) {
          $this->log(print_r('アクセストークンが取得できませんでした。アプリの利用設定を確認してください。', true));
          throw new \RuntimeException('アクセストークンが取得できませんでした。アプリの利用設定を確認してください。');
        }

        $params = @$data['get'];

        $this->_uid = $params['uid'];
        $this->_state = $params['state'];

        $this->log('再ログイン成功: ' . print_r($params, true));
        $this->log('アクセストークン再取得試行: ' . $this->_access_token);

        $this->setAccessToken();
        $this->log('アクセストークン再取得終了: ' . $this->_access_token);

        if ($this->reLogin) {
          $this->reLoginSuccess = true;
        }

        $this->log('NextEngine API ログイン成功！');
      }

      return strlen($this->_access_token);
    }

}
?>
