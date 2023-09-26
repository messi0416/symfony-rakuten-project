<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\MultiInsertUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

/*
  データ例
  [
    {
      "対象": "商品",
      "店舗名": "",
      "ファイル名": "NE_Products_20.csv",
      "ステータス": "処理待ち",
      "メッセージ": "",
      "登録者": "石田奈緒哉",
      "登録日": "2016-10-20 10:19:55"
    },
    {
      "対象": "商品",
      "店舗名": "",
      "ファイル名": "NE_Products_19.csv",
      "ステータス": "処理待ち",
      "メッセージ": "",
      "登録者": "石田奈緒哉",
      "登録日": "2016-10-20 10:19:48"
    }
  ]
 */

class CsvDownloadAndUpdateUploadCheckCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUsers */
  private $account;

  private $results = [];

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-upload-check')
      ->setDescription('login to NextEngine Web site and download upload check data and update db.')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return bool
     */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    $logger->info('CSVアップロード状態データ取込を開始しました。');

    // DB記録＆通知処理
    $logExecTitle = 'CSVアップロード状態データ取込';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始')->setLogLevel(TbLog::DEBUG));

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '開始')->setLogLevel(TbLog::DEBUG));

      $client = $webAccessUtil->getWebClient();

      // NEログイン
      $crawler = $webAccessUtil->neLogin($client, 'api', $input->getOption('target-env')); // 必要なら、アカウント名を追加して切り替える

      $mainHost = null;
      $uri = $client->getRequest()->getUri();
      if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
        $mainHost = $match[1];
      } else {
        throw new RuntimeException('メイン機能URLのホスト名の取得に失敗しました。');
      }

      // 「商品管理の一括登録」画面へ遷移（CSV取込処理のキックになっている可能性があるため）
      $nextUrl = $mainHost . '/User_Syohin_Upload';
      $crawler = $client->request('get', $nextUrl);
      
      $status = $client->getResponse()->getStatus();
      $uri = $client->getRequest()->getUri();
      if ($status !== 200 || !preg_match('!.next-engine.(?:org|com)/User_Syohin_Upload!', $uri)) {
        throw new RuntimeException('move to product upload page error!! [' . $status . '][' . $uri . ']');
      }
      $logger->info($logExecTitle . ' 商品管理の一括登録へ遷移成功');

      // 商品一括登録の状態・履歴（過去6ヶ月分のうち最大300件表示） CSV ダウンロード
      // → CSV元データがAjaxにより画面表示されているものであるため、データソースへ直接アクセス（JSON）
      $nextUrl = $mainHost . '/User_Syohin_Upload/log?param=1';
      $client->request('get', $nextUrl);

      /** @var \Symfony\Component\BrowserKit\Response $response */
      $response = $client->getResponse();
      $status = $response->getStatus();
      $requestUri = $client->getRequest()->getUri();
      $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);

      $jsonData = @json_decode($response->getContent(), true);
      $logger->info($logExecTitle
        . " URI[$nextUrl]"
        . (is_array($jsonData) ? ' is_array ok, ' : ' is_array ng, ')
        . (count($jsonData) ? 'count ok ' : 'count ng ')
        . $isInvalidAccess ? 'invalid access' : '');

      if ($status !== 200
        || $isInvalidAccess
        // || $response->getHeader('Content-Type') !== 'application/json; charset=UTF-8' // Content-Typeは text/html と application/json で不安定なので、チェックには使わない
        // || !$contentLength // Content-Length は返らない。
        || !is_array($jsonData)
      ) {
        $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
        file_put_contents($scrapingResponseDir . '/next_engine_update_upload_check.html', $response->getContent());
        $message = $isInvalidAccess ? '不正アクセスエラー' : '';
        throw new RuntimeException("can not download verify csv error!! $message [" . $status . '][' . $requestUri . '][' . $response->getHeader('Content-Type') . ']');
      }
      $logger->info('CSVアップロード状態データ レスポンス取得');

      // 取込処理
      $this->importUploadCheckCsvData($jsonData);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了')->setLogLevel(TbLog::DEBUG));

      // 結果取得 （処理待ち・処理中）
      $sql = <<<EOD
        SELECT
            ステータス
          , 登録日
          , ファイル名
          , 登録者
          , メッセージ
        FROM tb_ne_upload_check
        WHERE ステータス IN ('処理中', '処理待ち')
        ORDER BY 登録日, id
EOD;
      $this->results['unfinished'] = $this->getDb('main')->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('CSVアップロード状態データ取込 終了');

      return 0;

    } catch (\Exception $e) {

      $logger->error($logExecTitle . ':' . $e->getMessage() . ':' . $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "CSVアップロード状態データ取込処理でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }


  /**
   * データ取込処理 実装
   * @param array $data
   * @throws \Doctrine\DBAL\DBALException
   */
  private function importUploadCheckCsvData($data)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logger->info('insert 開始 : ' . count($data) . '件');

    // 件数取得
    $preNum = $dbMain->query("SELECT COUNT(*) FROM tb_ne_upload_check")->fetchColumn(0);


    $fields = [
        '対象'
      , '店舗名'
      , 'ファイル名'
      , 'ステータス'
      , 'メッセージ'
      , '登録者'
      , '登録日'
    ];

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_ne_upload_check", [
      'fields' => [
          '対象'       => \PDO::PARAM_STR
        , '店舗名'     => \PDO::PARAM_STR
        , 'ファイル名' => \PDO::PARAM_STR
        , 'ステータス' => \PDO::PARAM_STR
        , 'メッセージ' => \PDO::PARAM_STR
        , '登録者'     => \PDO::PARAM_STR
        , '登録日'     => \PDO::PARAM_STR
      ]
      , 'postfix' => " ON DUPLICATE KEY UPDATE "
                   . "     ステータス = VALUES(ステータス) "
                   . "   , メッセージ = VALUES(メッセージ) "
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $data, function($row) use ($fields) {

      $item = [];
      foreach($fields as $field) {
        $item[$field] = isset($row[$field]) ? $row[$field] : '';
        if ($field == '登録日' && !$item[$field]) {
          $item[$field] = '0000-00-00 00:00:00';
        }
      }

      return $item;

    }, 'foreach');

    // 件数取得
    $postNum = $dbMain->query("SELECT COUNT(*) FROM tb_ne_upload_check")->fetchColumn(0);

    $increased = $postNum - $preNum;
    $logger->info('insert 終了 （増加' . $increased . '件）');

    $this->results['count'] = count($data);
    $this->results['increased'] = $increased;
  }

}
