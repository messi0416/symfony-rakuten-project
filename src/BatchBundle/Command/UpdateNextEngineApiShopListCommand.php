<?php
/**
 * バッチ処理 NextEngine店舗一覧テーブル更新処理
 * 受注明細取込で参照する、店舗一覧テーブルを更新する。
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateNextEngineApiShopListCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:update-next-engine-api-shop-list')
      ->setDescription('NextEngine店舗一覧更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('NextEngine店舗一覧更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {

      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
        'message' => null
      ];

      $logExecTitle = sprintf('NextEngine店舗一覧更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // ---------------------------------------------
      // API データ取得処理 開始

      $apiInfo = $this->getContainer()->getParameter('ne_api');
      $clientId = $apiInfo['client_id'];
      $clientSecret = $apiInfo['client_secret'];
      $redirectUrl = $apiInfo['redirect_url'];

      $accessToken = $commonUtil->getSettingValue('NE_API_ACCESS_TOKEN');
      if (!$accessToken) {
        $accessToken = null;
      }
      $refreshToken = $commonUtil->getSettingValue('NE_API_REFRESH_TOKEN');
      if (!$refreshToken) {
        $refreshToken = null;
      }

      $client = new \ForestNeApiClient($clientId, $clientSecret, $redirectUrl, $accessToken, $refreshToken);
      $client->setLogger($logger);

      $loginId = $commonUtil->getSettingValue('NE_API_LOGIN_ID');
      $loginPassword = $commonUtil->getSettingValue('NE_API_LOGIN_PASSWORD');

      $client->setUserAccount($loginId, $loginPassword);

      $client->log('create instance.');
      $client->log('access_token: ' . $client->_access_token);
      $client->log('refresh_token: ' . $client->_refresh_token);

      $newLastUpdated = new \DateTimeImmutable(); // 処理成功後、最終更新日時とする日付。

      // 店舗情報を取得

      $fields = [
          'shop_id' // 店舗ID
        , 'shop_name' // 店舗名
//        , 'shop_kana' // 店舗名カナ
//        , 'shop_abbreviated_name' // 店舗略名
//        , 'shop_handling_goods_name' // 取扱商品名
//        , 'shop_close_date' // 閉店日
//        , 'shop_note' // 備考
//        , 'shop_mall_id' // モールID
//        , 'shop_authorization_type_id' // オーソリ区分ID
//        , 'shop_authorization_type_name' // オーソリ区分名
//        , 'shop_tax_id' // 税区分ID
//        , 'shop_tax_name' // 税区分名
//        , 'shop_currency_unit_id' // 通貨単位区分ID
//        , 'shop_currency_unit_name' // 通貨単位区分名
//        , 'shop_tax_calculation_sequence_id' // 税計算順序
//        , 'shop_type_id' // 後払い.com サイトID
//        , 'shop_deleted_flag' // 削除フラグ
//        , 'shop_creation_date' // 作成日
//        , 'shop_last_modified_date' // 最終更新日
//        , 'shop_last_modified_null_safe_date' // 最終更新日
//        , 'shop_creator_id' // 作成担当者ID
//        , 'shop_creator_name' // 作成担当者名
//        , 'shop_last_modified_by_id' // 最終更新者ID
//        , 'shop_last_modified_by_null_safe_id' // 最終更新者ID
//        , 'shop_last_modified_by_name' // 最終更新者名
//        , 'shop_last_modified_by_null_safe_name' // 最終更新者名
      ];

      $query = array() ;

      // 検索結果のフィールド指定
      $query['fields'] = implode(',', $fields) ;
      $query['offset'] = '0' ;
      // $query['limit'] = '3' ;

      // アクセス制限中はアクセス制限が終了するまで待つ。
      // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
      $query['wait_flag'] = '1' ;

      // 検索実行
      $shops = $client->apiExecute('/api_v1_master_shop/search', $query) ;
      $client->log($shops['result']);
      $client->log($shops['count']);

      if ($shops['result'] != 'success') {
        $logger->info(print_r($shops, true));
        $message = 'NE APIエラー';
        if (isset($shops['code'])) {
          $message = sprintf('[%s] ', $shops['code']);
        }
        if (isset($shops['message'])) {
          $message .= $shops['message'];
        }

        throw new \RuntimeException($message);
      }

      // アクセストークン・リフレッシュトークンの保存
      $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $client->_access_token);
      $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $client->_refresh_token);

      $client->log('access_token: ' . $client->_access_token);
      $client->log('refresh_token: ' . $client->_refresh_token);

      $this->results['count'] = $shops['count'];
      if ($shops['count'] != 0) {
        $dbMain = $this->getDb('main');

        $sql = <<<EOD
          INSERT INTO tb_ne_shop (
              shop_id
            , shop_name
          ) VALUES (
              :shopId
            , :shopName
          )
          ON DUPLICATE KEY UPDATE shop_name = VALUES(shop_name)
EOD;
        $stmt = $dbMain->prepare($sql);

        foreach($shops['data'] as $row) {
          $stmt->bindValue(':shopId', $row['shop_id']);
          $stmt->bindValue(':shopName', $row['shop_name']);
          $stmt->execute();
        }
      }

      // 最終更新日時 更新
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_NE_SHOP_LIST, $newLastUpdated);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('NextEngine店舗一覧更新処理を完了しました。');

    } catch (\Exception $e) {

      // 処理に失敗した場合にも、アクセストークンは更新する。（NULL更新でも更新）
      if (isset($commonUtil) && isset($client) && $client instanceof \ForestNeApiClient) {
        $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $client->_access_token);
        $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $client->_refresh_token);
      }

      $logger->error('NextEngine店舗一覧更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('NextEngine店舗一覧更新処理 エラー', 'NextEngine店舗一覧更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'NextEngine店舗一覧更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


