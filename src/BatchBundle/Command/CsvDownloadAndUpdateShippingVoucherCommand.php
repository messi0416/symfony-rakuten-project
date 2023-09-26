<?php
namespace BatchBundle\Command;

use AppBundle\Controller\DeliveryController;
use Exception;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Service\ShippingVoucherService;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

use Symfony\Component\Filesystem\Filesystem;
use MiscBundle\Entity\Repository\TbShippingVoucherAutoGenerateRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\TbShippingVoucherAutoGenerate;
use MiscBundle\Entity\TbLog;
use MiscBundle\Entity\Repository\SymfonyUsersRepository;


class CsvDownloadAndUpdateShippingVoucherCommand extends PlusnaoBaseCommand
{
  private $targetFilenameList;

  private $targetEnv;
  
  /** ステータス更新と納品書CSV生成に、スクレイピングを使用するか。使用するならば1 */
  private $useScraping = 0;

  // リトライ上限
  const RETRY_LIMIT = 3;

  // リトライ間隔（秒）
  const RETRY_WAIT = 10;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-shipipng-voucher')
      ->setDescription('NextEngineより納品書CSVをDLし、plusnaoに登録して出荷リスト・ピッキングリストを作成します')
      ->addOption('target-filename-list', null, InputOption::VALUE_OPTIONAL, 'デバッグ用。対象ファイル名\n/home/workuser/working/ne_api/data/csv_ndl配下のファイルを利用して処理を行う。ターゲット環境がtestの際にのみ利用する。カンマ区切りで複数指定可能。')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN) // NEキューで呼び出し
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = 'NextEngine CSV 出荷リスト自動作成';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    /** DBアクセス取得 */
    $dbMain = $this->getDb('main');

    /** @var TbShippingVoucherAutoGenerateRepository $autoRepo */
    $autoRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherAutoGenerate');
    /** @var TbWarehouseRepository $warehouseRepo */
    $warehouseRepo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouse');
    /** @var SymfonyUsersRepository $userRepo */
    $userRepo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getFileUtil();
    /** @var ShippingVoucherService $service */
    $service = $container->get('misc.service.shipping_voucher');

    $this->targetEnv = $input->getOption('target-env');
    
    $this->useScraping = $commonUtil->getSettingValue('NE_SCRAPING_ENABLE_RECEIPT_PRINT');

    // ステータスが0(登録済)の出荷リスト自動生成を1件ずつ取得してループで回す
    $autoGenerateCount = 0;
    $this->targetFilenameList = $input->getOption('target-filename-list') ? explode(',', $input->getOption('target-filename-list'))  : [];
    while ($autoGenerate = $autoRepo->findOneBy(['status' => TbShippingVoucherAutoGenerate::STATUS_REGISTERED], ['id' => 'asc'])) {
      // ステータスを処理中に更新する
      $autoRepo->updateStatus($autoGenerate->getId(), TbShippingVoucherAutoGenerate::STATUS_PROCESSING);
      try {
        $warehouse = $warehouseRepo->find($autoGenerate->getWarehouseId());
        $account = $userRepo->find($autoGenerate->getAccountId());

        $fs = new FileSystem();

        // 納品書CSVを置くディレクトリがなければ作成する
        $dataDir = $fileUtil->getDataDir() . '/csv_ndl';
        if (!$fs->exists($dataDir)) {
          $fs->mkdir($dataDir, 0755);
        }

        $targetFile = null;
        if ($autoGenerate->getFileName()) {
          $targetFile = $dataDir . '/' . $autoGenerate->getFileName();
        } else {
          $targetFile = $this->fetchTargetFile($dataDir, $autoGenerate, $autoGenerateCount, $service);
        }
        if (is_null($targetFile)) {
          // $targetFileがnullの場合、対象伝票番号なしのため処理が終了しているので次のデータへ
          continue;
        }
        $autoRepo->updateFileName($autoGenerate->getId(), str_replace($dataDir . '/', '', $targetFile));
        $logger->info('[出荷リスト自動作成]一時ファイル作成');

        // SJIS => UTF-8
        $tmpFile = tmpFile();
        $fp = fopen($targetFile, 'rb');
        while ($line = fgets($fp)) {
          $line = mb_convert_encoding(trim($line), 'UTF-8', 'SJIS-win') . "\n";
          fputs($tmpFile, $line);
        }

        $meta = stream_get_meta_data($tmpFile);
        $tmpFileName = isset($meta['uri']) ? $meta['uri'] : null;
        if (!$tmpFileName) {
          throw new \RuntimeException('一時ファイルの作成に失敗しました。');
        }

        // 書式確認
        fseek($tmpFile, 0);
        $headers = fgetcsv($tmpFile, null, ',', '"', '"');
        if ($headers != DeliveryController::$CSV_FIELDS_NE_SHIPPING_VOUCHER) {
          throw new \RuntimeException('CSVの書式が違うようです。ファイルパス：'.$targetFile);
        }

        // 同一ファイル確認
        $fileHash = sha1(file_get_contents($tmpFileName));
        $logger->info($fileHash);

        $sql = <<<EOD
          SELECT
            COUNT(*) AS cnt
          FROM tb_shipping_voucher
          WHERE `created` >= CURRENT_DATE
            AND file_hash = :fileHash
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':fileHash', $fileHash);
        $stmt->execute();
        $count = $stmt->fetchColumn(0);
        if ($count > 0) {
          throw new \RuntimeException('すでに本日アップロードされた内容です。ファイルパス：'.$targetFile);
        }

        $result = $service->manageShippingVoucherImport($account, $warehouse, $fileHash, $tmpFileName, $autoGenerate->getPackingGroupId());
        $result['path'] = $targetFile;

        $autoRepo->updateRegistNum($autoGenerate->getId(), $result['packingCount']);
        $autoRepo->updateStatus($autoGenerate->getId(), TbShippingVoucherAutoGenerate::STATUS_FINISHED);
      } catch (\Exception $e) {
        $autoRepo->updateStatus($autoGenerate->getId(), TbShippingVoucherAutoGenerate::STATUS_ERROR);

        $logger->error($this->commandName . ':' . $e->getMessage() . $e->getTraceAsString());

        try {
          $logger->addDbLog(
            $logger->makeDbLog(
              null,
              'エラーのため自動生成スキップ',
              sprintf('倉庫ID[%s]', $autoGenerate->getWarehouseId()),
              $autoGenerate->getDeliveryMethod(),
              sprintf('ページ[%s]', $autoGenerate->getPage())
              )
            ->setLogLevel(TbLog::ERROR)
            ->setInformation($e->getMessage() . '\n'
                . sprintf('出荷リスト自動生成Id[%s], 倉庫ID[%s], %s, ページ[%s]',
                  $autoGenerate->getId(),
                  $autoGenerate->getWarehouseId(),
                  $autoGenerate->getDeliveryMethod(),
                  $autoGenerate->getPage())
                )
            );
        } catch(\Exception $e2) {
          $logger->error($this->commandName . ':' . $e2->getMessage() . $e2->getTraceAsString());
        }
      }
      $autoGenerateCount++;
    }
    return;
  }

  /**
   * 引数によって絞った納品書印刷待ち集計データの伝票番号を返す。
   * @param int $warehouseId 倉庫ID
   * @param string $deliveryMethod 発送方法
   * @param int $page ページ
   * @param int $companyId 会社ID
   * @return array 伝票番号の配列
   */
  private function getVoucherNumber($warehouseId, $deliveryMethod, $page, $companyId)
  {
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
        SELECT STRAIGHT_JOIN
          r.伝票番号 AS voucehrNumber
        FROM
          tb_delivery_statement_detail_num_order_list_result r
          INNER JOIN tb_productchoiceitems pci
            ON (
              pci.ne_syohin_syohin_code = substr(
                r.対象商品コード
                , instr(r.対象商品コード, ':') + 1
                , char_length(r.対象商品コード)
              )
              OR (
                instr(r.対象商品コード, ':') = 0
                AND instr(r.対象商品コード, ',') >= 1
                AND pci.ne_syohin_syohin_code = substr(r.対象商品コード, 1, instr(r.対象商品コード, ',') - 1)
              )
            )
          INNER JOIN tb_mainproducts m
            ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN tb_company c
            ON m.company_code = c.code
        WHERE
          r.warehouse_id = :warehouseId
          AND r.発送方法 = :deliveryMethod
          AND r.page = :page
          AND c.id = :companyId
        ORDER BY
          r.id asc;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouseId', $warehouseId, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryMethod', $deliveryMethod, \PDO::PARAM_STR);
    $stmt->bindValue(':page', $page, \PDO::PARAM_INT);
    $stmt->bindValue(':companyId', $companyId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }

  /**
   * 納品書CSVファイルを取得。
   *
   * バッチでファイルを指定されていればループ数番目に指定されているファイルを返す。
   * ファイルの指定がなければNextEngineからCSVダウンロードする。
   * @param string $dataDir ディレクトリ
   * @param TbShippingVoucherAutoGenerate $autoGenerate 出荷リスト自動生成
   * @param int $autoGenerateCount ループ数
   * @param ShippingVoucherService $service
   * @return string 対象ファイルのパス。対象伝票番号なしのため処理が終了した場合nullを返す。
   */
  private function fetchTargetFile($dataDir, $autoGenerate, $autoGenerateCount, $service)
  {
    $targetFile = null;
    if (count($this->targetFilenameList) > 0) {
      // バッチ実行時に指定したファイルを返す
      $targetFile = $dataDir . '/' . $this->targetFilenameList[$autoGenerateCount];
      
    } else {
      // NextEngineからスクレイピングでCSVダウンロード
      if ($this->useScraping) {
        $targetFile = $this->downloadTargetFileByScraping($dataDir, $autoGenerate);
        
      // NextEngineのAPIで納品書印刷済みに更新＆受注明細APIから納品書CSV生成
      } else {
        // 出荷リスト自動生成に紐づく伝票番号を取得
        $voucherNumberList = $this->getVoucherNumber($autoGenerate->getWarehouseId(), $autoGenerate->getDeliveryMethod(), $autoGenerate->getPage(), $autoGenerate->getCompanyId());
        if (count($voucherNumberList) === 0) {
          throw new \RuntimeException('伝票番号が指定されていません。');
        }
        $targetFile = $service->updateReceiptedAndDownloadShippingVoucher($dataDir, $voucherNumberList);
        if (!$targetFile) { // 更新可能な対象がない場合はnullが返却される。ステータス3(完了(対象無し))に更新し、登録伝票数を0に更新
          /** @var TbShippingVoucherAutoGenerateRepository $autoRepo */
          $autoRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherAutoGenerate');
          $autoRepo->updateStatus($autoGenerate->getId(), TbShippingVoucherAutoGenerate::STATUS_FINISHED_NO_TARGET);
          $autoRepo->updateRegistNum($autoGenerate->getId(), 0);
        }
      }
    }
    return $targetFile;
  }

  /**
   * NextEngineからCSVダウンロードする。CSVはスクレイピングにより取得する。
   * ダウンロードの際、NextEngineの機能により、各受注のステータスは「納品書印刷待ち」から「納品書印刷済み」となる。
   * 
   * @param string $dataDir ディレクトリ
   * @param TbShippingVoucherAutoGenerate $autoGenerate 出荷リスト自動生成
   * @return string 対象ファイルのパス。対象伝票番号なしのため処理が終了した場合nullを返す。
   */
  private function downloadTargetFileByScraping($dataDir, $autoGenerate)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');

    /** @var TbShippingVoucherAutoGenerateRepository $autoRepo */
    $autoRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherAutoGenerate');
    /** @var SymfonyUsersRepository $userRepo */
    $userRepo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');

    $account = $userRepo->find($autoGenerate->getAccountId());
    if ($account) {
      $webAccessUtil->setAccount($account);
    }

    $client = $webAccessUtil->getWebClient();
    // NEログイン・メインページへの遷移
    $crawler = $webAccessUtil->neLogin($client, 'api', $this->targetEnv); // 必要なら、アカウント名を追加して切り替える

    // 出荷リスト自動生成に紐づく伝票番号を取得
    $voucherNumberList = $this->getVoucherNumber($autoGenerate->getWarehouseId(), $autoGenerate->getDeliveryMethod(), $autoGenerate->getPage(), $autoGenerate->getCompanyId());
    if (count($voucherNumberList) === 0) {
      throw new \RuntimeException('伝票番号が指定されていません。');
    }

    $mainHost = null;
    $uri = $client->getRequest()->getUri();
    $match = null;
    if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
      $mainHost = $match[1];
    } else {
      throw new \RuntimeException('メイン機能URLのホスト名の取得に失敗しました。');
    }
    
    // 納品書ダウンロード画面に遷移し、CSRFトークンを取得
    $url = $mainHost . '/Userdownload/ndl';
    $crawler = $client->request('GET', $url);
    $csrfInfo = $webAccessUtil->getNeCsrfTokenInfo($crawler);
    
    $jyuchu_arr = array();
    $retry_count = 0;
    $logger->info('出荷リスト自動作成開始 伝票番号：'.implode(', ', $voucherNumberList));
    
    // 対象伝票番号を取得できたらOK
    $action = $mainHost . '/Usersearchjyuchunouhinsyo/search';
    $params = [
      'ele_type'   => 'textarea'  //伝票番号
      , 'ele_id'     => "jyuchu_search_field01_multi"
      , 'ele_val'    => implode(', ', $voucherNumberList)
      , 'limit_from' => 0     //
      , 'limit_to'   => 300   //
      , 'sel_type'   => "R"
    ];

    do{
      // 直接 URLを指定してPOST
      $crawler = $client->request('POST', $action, $params);

      /** @var \Symfony\Component\BrowserKit\Response $response */
      $response = $client->getResponse();
      $status = $response->getStatus();
      $requestUri = $client->getRequest()->getUri();
      $contentType = $response->getHeader('Content-Type'); // 通常 text/html。application/json が返ってきたこともあったので、判定には使用しない
      $content = $response->getContent(); // 通常はJSONが返却される。受注CSV取込中はおそらくHTML

      // htmlチェック。200以外が返るか、受注CSV取込中であればリトライ
      if ($status !== 200 || strpos($content, '受注ＣＳＶファイル取込中です。暫くたってから、納品書ダウンロードを行ってください。<br>') !== false) {
        $retry_count++;
        if($retry_count > self::RETRY_LIMIT){
          // リトライ上限に達したら強制終了
          $logger->warning('can not download verify csv error!! [' . $status . '][' . $requestUri . '][' . $response->getHeader('Content-Type') . ']');
          throw new \RuntimeException('対象伝票番号取得に失敗しました、時間をおいて再実行してください。伝票番号：'.implode(', ', $voucherNumberList));
        } else {
          // 指定秒数待機
          $logger->addDbLog($logger->makeDbLog(null, '対象伝票番号取得', 'リトライ'.$retry_count.'回目'));
          sleep(self::RETRY_WAIT);
        }
      } else{
        // チェックがセーフならループを抜け出す
        break;
      }
    }while(true);

    $jsonData = @json_decode($response->getContent(), true);
    if(!is_array($jsonData) || !count($jsonData)){
      $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
      file_put_contents($scrapingResponseDir . '/aut-picking-list-empty.tmp.html', $response->getContent()); // レスポンス内容をログとして/tmp/に出力
      
      if ($webAccessUtil->isNeInvalidAccess($response)) {
        throw new \RuntimeException('NextEngineで不正アクセスエラーが発生しました');
      }
      
      // ステータス3(完了(対象無し))に更新し、登録伝票数を0に更新
      $autoRepo->updateStatus($autoGenerate->getId(), TbShippingVoucherAutoGenerate::STATUS_FINISHED_NO_TARGET);
      $autoRepo->updateRegistNum($autoGenerate->getId(), 0);

      $logger->info('出荷リスト自動作成：対象伝票番号なしのため処理終了');
      $result = [];
      $result['message'] = '対象伝票番号の件数が0件でした。処理を終了します。';
      $result['data'] = [];
      $result['data']['target_number'] = implode(', ', $voucherNumberList);
      $result['data']['is_array'] = is_array($jsonData);
      $result['data']['count'] = count($jsonData);
      $logger->addDbLog($logger->makeDbLog(null, '対象伝票番号取得', '終了')->setInformation($result));
      

      return null;
    }

    foreach($jsonData as $row) {
      $jyuchu_arr[] = $row['伝票番号'];
    }

    $logger->info('出荷リスト自動作成：照合用データ レスポンス取得　取得伝票番号：'.implode(',',$jyuchu_arr));

    if(count($jyuchu_arr) > count($voucherNumberList)) {
      throw new \RuntimeException('入力したパラメータよりも多くの伝票番号を取得しています。お手数ですがシステム担当者にご連絡くださいませ。');
    }

    $retry_count = 0;
    $action = $mainHost . '/Userdownload/pdfndl';
    $params = [
      'jyuchu_arr' => $jyuchu_arr //伝票番号
      , 'lmt'        => 300         //上限
      , 'mode'       => "H"         //ステータス変更　U：印刷済みへ移動　H：印刷済みへ移動＋配送情報　S：印刷待ちのまま　F：出荷済みへ移動＋配送情報
      , 'ss'         => -1          //出荷指示書DL -1:しない 1:する、CSVの場合はどちらにせよ出力されない
      , 'sort'       => 0           //並び順
      , 'output'     => 'CSV'       //出力形式
      , 'moji_code'  => 'SJIS'      //文字コード
      , 'check'      => 'ok'        //何かしらのチェック結果、とりあえず偽装
    ];

    do{
      // 直接 URLを指定してPOST
      $crawler = $client->request('POST', $action, $params);

      /** @var \Symfony\Component\BrowserKit\Response $response */
      $response = $client->getResponse();
      $status = $response->getStatus();
      $requestUri = $client->getRequest()->getUri();
      $contentType = $response->getHeader('Content-Type');

      if ($status !== 200 || strpos($contentType, 'application/octet-stream') === false) {
        $retry_count++;
        if($retry_count > self::RETRY_LIMIT){
          // リトライ上限に達したら強制終了
          $logger->warning('can not download verify csv error!! [' . $status . '][' . $requestUri . '][' . $response->getHeader('Content-Type') . ']');
          throw new \RuntimeException('ダウンロードに失敗しました、時間をおいて再実行してください。伝票番号：'.implode(', ', $voucherNumberList).' [' . $status . '][' . $requestUri . '][' . $response->getHeader('Content-Type') . ']');
        } else {
          // 指定秒数待機
          $logger->addDbLog($logger->makeDbLog(null, '納品書ダウンロード', 'リトライ'.$retry_count.'回目'));
          sleep(self::RETRY_WAIT);
        }
      } else {
        // チェックがセーフならループを抜け出す
        break;
      }
    }while(true);

    $logger->info('納品書CSVダウンロードレスポンス取得:'.$response->getHeader('Content-Disposition'));

    $match = null;
    $fileName = preg_match('/filename="([a-zA-Z0-9_-]+.csv)"/', $response->getHeader('Content-Disposition'), $match)
    ? $match[1]
    : sprintf('data%s.csv', date('YmdHis00000000'));

    $downloadFilePath = $dataDir . '/' . $fileName;
    $fs = new FileSystem();
    if ($fs->exists($downloadFilePath)) {
      throw new \RuntimeException('same csv name exists error!! [' . $downloadFilePath . ']');
    }

    $file = new \SplFileObject($downloadFilePath, 'w'); // 上書き
    $bytes = $file->fwrite($response->getContent());

    if (!$fs->exists($downloadFilePath) || ! $bytes) {
      @$fs->remove($downloadFilePath);
      throw new \RuntimeException('can not save csv file. [ ' . $downloadFilePath . ' ][' . $bytes . ']');
    }

    return $downloadFilePath;
  }
}
