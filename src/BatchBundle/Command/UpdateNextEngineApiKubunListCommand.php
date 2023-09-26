<?php
/**
 * バッチ処理 NextEngine 区分値一覧テーブル更新処理
 * モール受注CSV変換で参照する、区分値一覧テーブルを更新する。
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateNextEngineApiKubunListCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  const KUBUN_PAYMENT = 'payment';
  const KUBUN_DELIVERY = 'delivery';
  const KUBUN_CONVERT = 'convert';

  public static $KUBUN_TARGETS = [
      self::KUBUN_PAYMENT
    , self::KUBUN_DELIVERY
    , self::KUBUN_CONVERT
  ];

  protected function configure()
  {
    $this
      ->setName('batch:update-next-engine-api-kubun-list')
      ->setDescription('NextEngine区分値一覧更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target', null, InputOption::VALUE_REQUIRED, '更新対象 payment, delivery, convert')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('NextEngine区分値一覧更新処理を開始しました。');

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

      // 対象チェック
      $target = $input->getOption('target');
      if (!in_array($target, self::$KUBUN_TARGETS)) {
        throw new \RuntimeException('invalid target : [' . $target . ']');
      }

      $logExecTitle = sprintf('NextEngine区分値一覧更新処理 [' . $target . ']');
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

      $query = array() ;

      // 区分値情報を取得
      $saveFields = [];

      switch ($target) {
        case self::KUBUN_PAYMENT: // 支払区分
          $url = '/api_v1_system_paymentmethod/info';
          $targetTable = 'ne_kubun_payment_method';
          $saveFields = [
              'payment_method_id'
            , 'payment_method_name'
          ];

          break;
        case self::KUBUN_DELIVERY: // 発送方法区分
          $url = '/api_v1_system_delivery/info';
          $targetTable = 'ne_kubun_delivery_method';
          $saveFields = [
              'delivery_id'
            , 'delivery_name'
          ];

          break;
        case self::KUBUN_CONVERT:
          $url = '/api_v1_receiveorder_paymentdeliveryconvert/search';
          $fields = [
              'payment_delivery_convert_text'
            , 'payment_delivery_convert_type'
            , 'payment_delivery_convert_multi_id'
            , 'payment_delivery_convert_delivery_id'
          ];
          $query['fields'] = implode(',', $fields);
          $query['payment_delivery_convert_deleted_flag-eq'] = '0'; // 未削除のもののみ
          $query['offset'] = '0' ;

          $targetTable = 'ne_payment_delivery_convert_setting';
          $saveFields = $fields;
          break;
        default:
          throw new \RuntimeException('unreachable code...');
      }

      // アクセス制限中はアクセス制限が終了するまで待つ。
      // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
      $query['wait_flag'] = '1' ;

      // 検索実行
      $result = $client->apiExecute($url, $query) ;
      $client->log($result['result']);
      $client->log($result['count']);

      if ($result['result'] != 'success') {
        $logger->info(print_r($result, true));
        $message = 'NE APIエラー';
        if (isset($result['code'])) {
          $message = sprintf('[%s] ', $result['code']);
        }
        if (isset($result['message'])) {
          $message .= $result['message'];
        }

        throw new \RuntimeException($message);
      }

      // アクセストークン・リフレッシュトークンの保存
      $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $client->_access_token);
      $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $client->_refresh_token);

      $client->log('access_token: ' . $client->_access_token);
      $client->log('refresh_token: ' . $client->_refresh_token);

      $this->results['count'] = $result['count'];

      if ($result['count'] != 0) {
        $dbMain = $this->getDb('main');

        $dbMain->query("TRUNCATE {$targetTable}");

        // 一括insert
        $parameterFields = [];
        foreach($saveFields as $field) {
          $parameterFields[$field] = \PDO::PARAM_STR;
        }

        $insertBuilder = new MultiInsertUtil($targetTable, [
            'fields' => $parameterFields
          , 'prefix' => 'INSERT IGNORE INTO '
        ]);

        $commonUtil->multipleInsert($insertBuilder, $dbMain, $result['data'], function($row) {
          return $row;
        }, 'foreach');
      }

      // 最終更新日時 更新
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_NE_SHOP_LIST, $newLastUpdated);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('NextEngine区分値一覧更新処理を完了しました。');

    } catch (\Exception $e) {

      // 処理に失敗した場合にも、アクセストークンは更新する。（NULL更新でも更新）
      if (isset($commonUtil) && isset($client) && $client instanceof \ForestNeApiClient) {
        $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $client->_access_token);
        $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $client->_refresh_token);
      }

      $logger->error('NextEngine区分値一覧更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('NextEngine区分値一覧更新処理 エラー', 'NextEngine区分値一覧更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'NextEngine区分値一覧更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


