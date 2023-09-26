<?php

namespace AppBundle\Controller;

use BatchBundle\Job\MainJob;
use BatchBundle\MallProcess\AmazonMallProcess;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\Repository\TbNeMallOrderRepository;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\StringUtil;
use SplFileInfo;
use SplFileObject;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use phpseclib\Net\SFTP;

/**
 * モール受注CSV関連
 * @package AppBundle\Controller
 */
class MallOrderController extends BaseController
{
  /**
   * 取込済データ一覧画面
   * @param int $page
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function listAction($page)
  {
    $account = $this->getLoginUser();

    /** @var TbNeMallOrderRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbNeMallOrder');
    $pagination = $repo->findListByConvertedDate([], [], $page);

    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->get('batch.mall_process.next_engine');

    // CSVファイル名 作成（一応、テンプレート表示と出力時のファイル名を合わせるためにここで作成）
    $fileNames = [];
    foreach($pagination->getItems() as $item) {
      $shopCode = $item['shop_code'];

      // CSVファイル名
      if (!isset($fileNames[$shopCode])) {
        $fileNames[$shopCode] = [];
      }
      if ($item['converted']) {
        /** @noinspection PhpUndefinedMethodInspection */
        $converted = $item['converted']->format('Y-m-d H:i:s');
        $fileNames[$shopCode][$converted] = $mallProcess->createMallOrderCsvFileName($shopCode, $converted);
      }
    }

    // 画面表示
    return $this->render('AppBundle:MallOrder:list.html.twig', [
        'account' => $account
      , 'pagination' => $pagination
      , 'paginationInfo' => $pagination->getPaginationData()
      , 'fileNames' => $fileNames
    ]);
  }


  /**
   * モール受注CSV アップロード処理
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function csvUploadAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('mall order csv upload: start.');

    $logger->info(print_r($_FILES, true));
    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'info' => []
    ];

    try {
      $account = $this->getLoginUser();

      // ファイル変換、モール判定（ヘッダチェック）
      $mallFiles = $this->processMallFiles($request->files->get('upload'));
      if (!$mallFiles) {
        throw new \RuntimeException('取り込めるデータがありませんでした。');
      }

      $mallCodes = array();
      $resque = $this->getResque();

      // 取込処理、同一モールのファイルが２つ以上のときの対策のため、まず取り込みをすべて行う
      $hasUpdateOrderDone = false;
      foreach($mallFiles as $mallCode => &$files) {
        $logger->info('mall code => ' . $mallCode);

        $this->importMallFiles($mallCode, $files, $result);
        
        if(!in_array($mallCode, $mallCodes)) $mallCodes[] = $mallCode;
      }

      // モールごとの変換キュー追加処理 取り込んだファイルのモールごとにキュー呼び出しを行う。
      foreach($mallCodes as $mallCode) {
        $job = new MainJob();
        $job->queue = 'main'; // キュー名
        $job->args = [
            'command'   => MainJob::COMMAND_KEY_CONVERT_MALL_ORDER_CSV_DATA
          , 'mallCode'  => $mallCode
          , 'account'   => $account->getId()
          , 'doUpload'  => ($this->get('kernel')->getEnvironment() === 'test') ? false : true
        ];
        // 最初の1回は受注明細差分更新を行う
        if (!$hasUpdateOrderDone) {
          $job->args['updateOrder'] = 1;
          $hasUpdateOrderDone = true;
        }

        $resque->enqueue($job); // リトライなし
      }

      $logger->info(print_r($mallCodes, true));
      $logger->info(print_r($mallFiles, true));

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
      $result['error'] = $e->getMessage();
    }

    // $logger->info(print_r($result, true));
    return new JsonResponse($result);
  }

  /**
   * CSVダウンロード
   * @param integer $shopCode
   * @param string $converted
   * @return StreamedResponse
   */
  public function csvDownloadAction($shopCode, $converted)
  {
    if (!$shopCode || !$converted) {
      throw new \RuntimeException('CSVの指定が正しくありません。');
    }

    // 日付整形
    if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $converted, $match)) {
      $converted = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]);
    }

    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->get('batch.mall_process.next_engine');

    $response = $mallProcess->generateMallOrderCsv($shopCode, $converted, 'response', false);
    $response->send();

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    // ダウンロード日時の更新
    $sql = <<<EOD
      UPDATE tb_ne_mall_order o
      SET downloaded = NOW()
      WHERE shop_code = :shopCode
        AND converted = :converted
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $shopCode);
    $stmt->bindValue(':converted', $converted);
    $stmt->execute();

    return $response;
  }

  /**
   * NextEngine 受注一括登録 アップロード処理（手動起動）
   * @param integer $shopCode
   * @param string $converted
   * @return JsonResponse
   */
  public function csvNextEngineUploadAction($shopCode, $converted)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'shopCode' => $shopCode
      , 'converted' => $converted
      , 'csv' => []
    ];

    try {
      if (!$shopCode || !$converted) {
        throw new \RuntimeException('CSVの指定が正しくありません。');
      }

      // 日付整形
      if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $converted, $match)) {
        $converted = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]);
      }

      /** @var NextEngineMallProcess $mallProcess */
      $mallProcess = $this->get('batch.mall_process.next_engine');

      $exportFiles = $mallProcess->generateMallOrderCsv($shopCode, $converted, 'file', false);
      if ($exportFiles) {
        $hasError = false;

        /** @var DbCommonUtil $commonUtil */
        $commonUtil = $this->get('misc.util.db_common');
        $mall = $commonUtil->getShoppingMall($commonUtil->getMallIdByMallCode($commonUtil->getMallCodeByNeMallId($shopCode)));

        foreach($exportFiles as $exportFile) {
          $logger->info($exportFile->getPathname());

          $ret = $mallProcess->apiUploadMallOrderCsv($mall, $exportFile);

          if ($ret['status'] == 'ok') {
            $result['message'] .= sprintf("NextEngineへ受注データをアップロードしました。 [%s]\n", $exportFile->getBasename());
          } else {
            $result['message'] .= sprintf("アップロードエラーが発生しました。 [%s][%s]\n", $ret['message'], $exportFile->getBasename());
            $hasError = true;
          }
        }

        // エラーがなければ、ダウンロード日時・アップロード日時を更新
        // こちらはCSV出力処理に合わせて、指定convertedのみが対象。 (generateMallOrderCsv() 第4引数: false)
        if (!$hasError) {
          /** @var \Doctrine\DBAL\Connection $dbMain */
          $dbMain = $this->getDoctrine()->getConnection('main');
          $sql = <<<EOD
                UPDATE tb_ne_mall_order o
                SET downloaded = NOW()
                  , uploaded = NOW()
                WHERE shop_code = :shopCode
                  AND converted = :converted
EOD;
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':shopCode', $mall->getNeMallId());
          $stmt->bindValue(':converted', $converted);
          $stmt->execute();
        }

      } else {
        throw new \RuntimeException('CSVファイルの出力に失敗しました。 (' . $shopCode . ' : ' . $converted . ')');
      }

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
    }

    return new JsonResponse($result);

  }



  /**
   * モール受注CSV Q10発送処理CSV出力 ファイルアップロード処理
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function csvQ10DeliveryUploadAction(Request $request)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('mall order csv q10 delivery upload: start.');

    $logger->info(print_r($_FILES, true));
    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'csv' => [
          'delivery' => null
        , 'tracking' => null
      ]
    ];

    try {
      $account = $this->getLoginUser();

      $logger->info(print_r($request->files->get('upload'), true));

      // ファイル変換、モール判定（ヘッダチェック）
      $mallFiles = $this->processMallFiles($request->files->get('upload'));
      if (!$mallFiles) {
        throw new \RuntimeException('取り込めるデータがありませんでした。');
      }

      if (!isset($mallFiles[DbCommonUtil::MALL_CODE_Q10])) {
        throw new \RuntimeException('Q10の配送要請ファイルではないようです。');
      }

      // 一時テーブル取込
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');
      $dbLog = $this->getDoctrine()->getConnection('log');;
      $logDbName = $dbLog->getDatabase();
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->get('misc.util.db_common');

      $temporaryWord = ' TEMPORARY ';
      // $temporaryWord = ' '; // FOR DEBUG
      $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_mall_order_q10");
      $dbMain->exec("CREATE {$temporaryWord} TABLE tmp_work_mall_order_q10 LIKE {$logDbName}.tb_mall_order_q10 ");

      /**
       * @var SplFileInfo $file
       */
      foreach($mallFiles[DbCommonUtil::MALL_CODE_Q10] as $i => $file) {

        $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            IGNORE INTO TABLE tmp_work_mall_order_q10
            FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n' IGNORE 1 LINES
            SET imported = CURRENT_TIMESTAMP
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':importFilePath', $file->getPathname());
        $stmt->execute();
      }

      // CSVデータ変数格納処理
      /** @var StringUtil $stringUtil */
      $stringUtil = $this->get('misc.util.string');

      $csvDelivery = '';
      $sql = <<<EOD
        SELECT
            t.`配送状態`
          , t.`注文番号`
          , t.`カート番号`
          , t.`配送会社`
          , t.`送り状番号`
          , t.`発送日`
          , o.`発送予定日`  /* ここだけ差し替え */
          , t.`商品名`
          , t.`数量`
          , t.`オプション情報`
          , t.`オプションコード`
          , t.`受取人名`
          , t.`販売者商品コード`
          , t.`決済サイト`
        FROM tmp_work_mall_order_q10 t
        INNER JOIN {$logDbName}.tb_mall_order_q10 o ON t.`注文番号` = o.`注文番号` AND t.`カート番号` = o.`カート番号`
        WHERE t.`発送予定日` = ''
          AND o.`発送予定日` <> ''
        ORDER BY t.`注文番号`, t.`カート番号`
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      $fileName = 'Delivery.csv';
      $logger->info('csv name: ' . $fileName . ' ( ' . $stmt->rowCount() . ' ) ');

      if ($stmt->rowCount()) {
        // ヘッダ
        $headers = [
            '配送状態' => '配送状態'
          , '注文番号' => '注文番号'
          , 'カート番号' => 'カート番号'
          , '配送会社' => '配送会社'
          , '送り状番号' => '送り状番号'
          , '発送日' => '発送日'
          , '発送予定日' => '発送予定日'
          , '商品名' => '商品名'
          , '数量' => '数量'
          , 'オプション情報' => 'オプション情報'
          , 'オプションコード' => 'オプションコード'
          , '受取人名' => '受取人名'
          , '販売者商品コード' => '販売者商品コード'
          , '決済サイト' => '決済サイト'
        ];

        $eol = "\r\n";

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;

        $csvDelivery .= $header;
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
          $csvDelivery .= $line;
        }

        $result['csv']['delivery'] = [
            'name' => $fileName
          , 'data' => $csvDelivery
        ];
      }

      $csvTracking = '';
      $sql = <<<EOD
        SELECT
            t.`配送状態`
          , t.`注文番号`
          , t.`カート番号`
          , t.`配送会社`
          , o.`送り状番号` /* ここだけ差し替え */
          , t.`発送日`
          , t.`発送予定日`
          , t.`商品名`
          , t.`数量`
          , t.`オプション情報`
          , t.`オプションコード`
          , t.`受取人名`
          , t.`販売者商品コード`
          , t.`決済サイト`
        FROM tmp_work_mall_order_q10 t
        INNER JOIN {$logDbName}.tb_mall_order_q10 o ON t.`注文番号` = o.`注文番号` AND t.`カート番号` = o.`カート番号`
        WHERE t.`送り状番号` = ''
          AND (
                   o.`送り状番号` <> ''
                OR o.`配送会社` = '普通郵便'
                OR o.`配送会社` = '定形外郵便' /* これあるの？ */
              )
        ORDER BY t.`注文番号`, t.`カート番号`
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();

      $fileName = 'Tracking.csv';
      $logger->info('csv name: ' . $fileName . ' ( ' . $stmt->rowCount() . ' ) ');

      if ($stmt->rowCount()) {
        // ヘッダ
        $headers = [
            '配送状態' => '配送状態'
          , '注文番号' => '注文番号'
          , 'カート番号' => 'カート番号'
          , '配送会社' => '配送会社'
          , '送り状番号' => '送り状番号'
          , '発送日' => '発送日'
          , '発送予定日' => '発送予定日'
          , '商品名' => '商品名'
          , '数量' => '数量'
          , 'オプション情報' => 'オプション情報'
          , 'オプションコード' => 'オプションコード'
          , '受取人名' => '受取人名'
          , '販売者商品コード' => '販売者商品コード'
          , '決済サイト' => '決済サイト'
        ];

        $eol = "\r\n";

        // ヘッダ
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;

        $csvTracking .= $header;
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          // Accessの元処理にあった書き換え。必要かどうかは不明だが実施。
          if ($row['配送会社'] == '定形外郵便') {
            $row['配送会社'] = '普通郵便';
          }
          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
          $csvTracking .= $line;
        }

        $result['csv']['tracking'] = [
            'name' => $fileName
          , 'data' => $csvTracking
        ];
      }

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['error'] = $e->getMessage();
    }

    // $logger->info(print_r($result, true));
    return new JsonResponse($result);
  }

  /**
   * EC-CUBE 受注変換
   */
  public function convertEcCubeOrderAction()
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');
    $logger->info('mall order convert ec-cube order: start.');

    $result = [
        'status' => 'ok'
      , 'message' => null
      , 'info' => []
    ];

    try {
      $account = $this->getLoginUser();

      $targets = [
          DbCommonUtil::MALL_CODE_EC01
        , DbCommonUtil::MALL_CODE_EC02
      ];

      foreach($targets as $target) {
        $logger->info('convert mall order ec: ' . $target);

        $commandArgs = [
           'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
          , sprintf('--account=%d', $account->getId())
          , sprintf('--do-upload=%d', 1)
          , sprintf('--target=%s', $target)
        ];

        $input = new ArgvInput($commandArgs);
        $output = new ConsoleOutput();

        $command = $this->get('batch.convert_mall_order_csv_ec_cube_and_upload');
        $exitCode = $command->run($input, $output);

        if ($exitCode !== 0) { // コマンドが異常終了した
          throw new \RuntimeException('変換に失敗しました。');
        }
      }

      $result['message'] = 'EC-CUBE受注取込を完了しました。';

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->error($e->getTraceAsString());

      $result['status'] = 'ng';
      $result['message'] = $e->getMessage();
      $result['error'] = $e->getMessage();
    }

    $logger->info(print_r($result, true));
    return new JsonResponse($result);
  }


  /**
   * 全てをUTF-8へ変換し、モール別にファイルを仕分け
   * @param UploadedFile[] $files
   * @return array
   */
  private function processMallFiles($files)
  {
    $logger = $this->get('misc.util.batch_logger');

    $result = [];
    $logger->info('件数 : ' . count($files));

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->get('misc.util.file');

    $fs = new Filesystem();
    $uploadDir = sprintf('%s/MallOrder/Import/%s', $fileUtil->getWebCsvDir(), (new \DateTime())->format('YmdHis'));
    if (!$fs->exists($uploadDir)) {
      $fs->mkdir($uploadDir, 0755);
    }

    foreach($files as $file) {
      $logger->info('uploaded : ' . print_r($file->getPathname(), true));

      // 2行目（最初のデータ）で文字コード判定 ＆ UTF-8変換
      $fp = fopen($file->getPathname(), 'rb');
      fgets($fp); // 先頭行を捨てる
      $secondLine = fgets($fp);
      fclose($fp);
      if (!$secondLine) { // 2行目がなければスルー（データが無いため処理不要）
        continue;
      }

      $charset = mb_detect_encoding($secondLine, ['SJIS-WIN', 'UTF-8', 'EUCJP-WIN']);
      $logger->info(sprintf('%s : %s', $file->getClientOriginalName(), $charset));
      if (!$charset) {
        throw new \RuntimeException(sprintf('CSVファイルの文字コードが判定できませんでした。[%s]', $file->getClientOriginalName()));
      }

      $newFilePath = tempnam($uploadDir, 'utf_');
      chmod($newFilePath, 0666);
      $fp = fopen($newFilePath, 'wb');
      $fileUtil->createConvertedCharsetTempFile($fp, $file->getPathname(), $charset, 'UTF-8');
      fclose($fp);
      $newFile = new File($newFilePath);
      try {
        $mallCode = $this->guessMallByCsvHeader($newFile);

        if (!$mallCode) {
          throw new \RuntimeException(sprintf('モールが特定できませんでした。[%s]', $file->getClientOriginalName()));
        }

        // 複数ファイルセットモール（というかYahoo）
        if (strpos($mallCode, '/') !== false) {
          list($mallCode, $type) = explode('/', $mallCode);
          if (!isset($result[$mallCode])) {
            $result[$mallCode] = [];
          }
          if (!isset($result[$mallCode][$type])) {
            $result[$mallCode][$type] = [];
          }

          $result[$mallCode][$type][] = $newFile;

        // 単体ファイルセット
        } else {
          if (!isset($result[$mallCode])) {
            $result[$mallCode] = [];
          }

          $result[$mallCode][] = $newFile;
        }

      } catch (\Exception $e) {
        throw new \RuntimeException(sprintf('%s [%s]', $e->getMessage(), $file->getClientOriginalName()));
      }
    }

    return $result;
  }

  /**
   * ヘッダ行（およびデータ１行目）からモール判定
   * ※valid チェックも兼ねる
   * @param SplFileInfo $file
   * @return string
   */
  private function guessMallByCsvHeader($file)
  {
    /** @var BatchLogger $logger */
    $logger = $this->get('misc.util.batch_logger');

    $fObj = $file->openFile('rb');

    $firstLine = $fObj->fgets();
    $secondLine = $fObj->fgets();

    if (!$firstLine || !$secondLine) {
      throw new \RuntimeException('invalid file. (no 2 lines)');
    }

    // ヘッダ行を配列に分解

    // まずタブ区切りを試してみる
    $tmpFields = explode("\t", $firstLine);

    $fObj->rewind();
    // おそらくタブ区切り
    if (count($tmpFields) > 10) {
      $fields = $fObj->fgetcsv("\t", '"', '\\');
    // でなければカンマ区切り
    } else {
      $fields = $fObj->fgetcsv(',', '"', '\\');
    }

    if (!is_array($fields) || count($fields) < 2) {
      throw new \RuntimeException('ヘッダ行が取得できませんでした。');
    }

    $mallCode = null;
    switch ($fields) {
      case self::$CSV_FIELDS_PPM:
        $mallCode = DbCommonUtil::MALL_CODE_PPM;
        break;

      case self::$CSV_FIELDS_YAHOO_ORDER:
        // 二行目取得・モール判定。
        $mallName = $this->getYahooMallCodeByCsvObj($fObj);
        if ($mallName) {
          $mallCode = sprintf('%s/order', $mallName);
        }
        break;

      case self::$CSV_FIELDS_YAHOO_ITEM:
        // 二行目取得・モール判定。
        $mallName = $this->getYahooMallCodeByCsvObj($fObj);
        if ($mallName) {
          $mallCode = sprintf('%s/item', $mallName);
        }
        break;

      case self::$CSV_FIELDS_Q10:
        $mallCode = DbCommonUtil::MALL_CODE_Q10;
        break;

      // case self::$CSV_FIELDS_DENA:
      case self::$CSV_FIELDS_WOWMA:
        $mallCode = DbCommonUtil::MALL_CODE_BIDDERS;
        break;

      case self::$CSV_FIELDS_RAKUTEN:
        // 二行目取得・モール判定。
        $mallCode = $this->getRakutenMallCodeByCsvObj($fObj);
        if (!$mallCode) {
          $mallCode = DbCommonUtil::MALL_CODE_RAKUTEN; // 一応。
        }
        break;

      case self::$CSV_FIELDS_RAKUTEN_PAY:
        // 二行目取得・モール判定。
        $mallCode = $this->getRakutenMallCodeByCsvObj($fObj, true);
        if (!$mallCode) {
          $mallCode = DbCommonUtil::MALL_CODE_RAKUTEN_PAY; // 一応。
        }
        break;

      case AmazonMallProcess::$CSV_FIELDS_MALL_ORDER:
        $mallCode = DbCommonUtil::MALL_CODE_AMAZON;
        break;

      default:
        break;
    }

    return $mallCode;
  }

  /**
   * Yahoo CSVからモール名取得
   * 2行目データ行の "Id" カラムから取得
   * @param SplFileObject $fObj
   * @return string
   */
  private function getYahooMallCodeByCsvObj($fObj)
  {
    $result = null;

    $codeList = [
        'plusnao' => DbCommonUtil::MALL_CODE_PLUSNAO_YAHOO
      , 'kawa-e-mon' => DbCommonUtil::MALL_CODE_KAWA_YAHOO
      , 'mignonlindo' => DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO
    ];

    $fObj->rewind();
    $fields = $fObj->fgetcsv(',', '"', '\\');
    $idColNum = array_search('Id', $fields);
    if ($idColNum === false) {
      return null; // イレギュラー
    }

    $data = $fObj->fgetcsv(',', '"', '\\');
    $id = $data[$idColNum];
    if (preg_match('/^(.+)-\d+$/', $id, $m)) {
      if (isset($codeList[$m[1]])) {
        $result = $codeList[$m[1]];
      }
    }

    return $result;
  }

  /**
   * 楽天 CSVからモールコード取得
   * 2行目データ行の "受注番号" カラムから取得
   * @param SplFileObject $fObj
   * @param bool $isRakutenPay
   * @return string
   */
  private function getRakutenMallCodeByCsvObj($fObj, $isRakutenPay = false)
  {
    $result = null;

    if ($isRakutenPay) {
      $codeList = [
          '242190' => DbCommonUtil::MALL_CODE_RAKUTEN_PAY
        , '345459' => DbCommonUtil::MALL_CODE_RAKUTEN_PAY_MINNA
        , '384255' => DbCommonUtil::MALL_CODE_RAKUTEN_PAY_MOTTO
        , '405585' => DbCommonUtil::MALL_CODE_RAKUTEN_PAY_LAFOREST
        , '349354' => DbCommonUtil::MALL_CODE_RAKUTEN_PAY_DOLCISSIMO
        , '411285' => DbCommonUtil::MALL_CODE_RAKUTEN_PAY_GEKIPLA
      ];
      $orderNumberWord = '注文番号';
    } else {
      $codeList = [
          '242190' => DbCommonUtil::MALL_CODE_RAKUTEN
        , '345459' => DbCommonUtil::MALL_CODE_RAKUTEN_MINNA
        , '384255' => DbCommonUtil::MALL_CODE_RAKUTEN_MOTTO
        , '405585' => DbCommonUtil::MALL_CODE_RAKUTEN_LAFOREST
        , '349354' => DbCommonUtil::MALL_CODE_RAKUTEN_DOLCISSIMO
        , '411285' => DbCommonUtil::MALL_CODE_RAKUTEN_GEKIPLA
      ];
      $orderNumberWord = '受注番号';
    }

    $fObj->rewind();
    $fields = $fObj->fgetcsv(',', '"', '\\');
    $orderNumberColNum = array_search($orderNumberWord, $fields);
    if ($orderNumberColNum === false) {
      return null; // イレギュラー
    }

    $data = $fObj->fgetcsv(',', '"', '\\');
    $orderNumber = $data[$orderNumberColNum];
    if (preg_match('/^(.+)-\d+-\d+$/', $orderNumber, $m)) {
      if (isset($codeList[$m[1]])) {
        $result = $codeList[$m[1]];
      }
    }

    return $result;
  }


  /**
   * モールCSVデータ取込
   * @param string $mallCode
   * @param File[] $files
   * @param array $result
   */
  private function importMallFiles($mallCode, &$files, &$result)
  {
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log');
    $logDbName = $dbLog->getDatabase();

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->get('batch.mall_process.next_engine');

    $now = new \DateTime();

    switch ($mallCode) {
      // PPM
      case DbCommonUtil::MALL_CODE_PPM:

        $result['info'][$mallCode] = [
          'load_data' => []
        ];

        foreach($files as $i => $file) {
          $beforeCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_ppm")->fetchColumn(0);
          $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            IGNORE INTO TABLE tb_mall_order_ppm
            FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n' IGNORE 1 LINES
            SET imported = CURRENT_TIMESTAMP
EOD;
          $stmt = $dbLog->prepare($sql);
          $stmt->bindValue(':importFilePath', $file->getPathname());
          $stmt->execute();

          $afterCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_ppm")->fetchColumn(0);

          // ファイル名をモールがわかるものに変更（検証用履歴データ）
          $fileName = sprintf('%s_%s_%02d', $mallCode, $now->format('YmdHis'), $i + 1);
          $file->move($file->getPath(), $fileName);
          $file = new File(sprintf('%s/%s', $file->getPath(), $fileName));
          $files[$i] = $file;

          $result['info'][$mallCode]['load_data'][$file->getPathname()] = [
              'before' => $beforeCount
            , 'after' => $afterCount
            , 'num' => $afterCount - $beforeCount
          ];
        }
        break;

      // Q10
      case DbCommonUtil::MALL_CODE_Q10:

        $result['info'][$mallCode] = [
          'load_data' => []
        ];

        // Delivery (出荷予定日 更新)
        // ここは、取込日から2営業日後で固定。CSVでの登録はこれで行う。（実際の遅延処理はコンシェルのフローで別途行う）
        // $shippingDate = $commonUtil->getWorkingDateAfterDays(new \DateTime(), 2);
        // => さらに、もう営業日関係なく固定で3日後でよい。とのこと
        // => 2018/01/30 さらにQ10が最近うるさいため、4日後に。（後に戻す想定）
        // => 2018/01/31 4日後にするとエラーになるとのこと。3日後に戻す。Q10ひどい
        $today = new \DateTime();
        $shippingDate = $today->modify('+3 day');

        foreach($files as $i => $file) {
          $beforeCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_q10")->fetchColumn(0);
          $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            IGNORE INTO TABLE tb_mall_order_q10
            FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n' IGNORE 1 LINES
            SET imported = CURRENT_TIMESTAMP
              , `発送予定日` = :shippingDate
EOD;
          $stmt = $dbLog->prepare($sql);
          $stmt->bindValue(':importFilePath', $file->getPathname());
          $stmt->bindValue(':shippingDate', $shippingDate->format('Y/m/d'));
          $stmt->execute();

          $afterCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_q10")->fetchColumn(0);

          // ファイル名をモールがわかるものに変更（検証用履歴データ）
          $fileName = sprintf('%s_%s_%02d', $mallCode, $now->format('YmdHis'), $i + 1);
          $file->move($file->getPath(), $fileName);
          $file = new File(sprintf('%s/%s', $file->getPath(), $fileName));
          $files[$i] = $file;

          $result['info'][$mallCode]['load_data'][$file->getPathname()] = [
              'before' => $beforeCount
            , 'after' => $afterCount
            , 'num' => $afterCount - $beforeCount
          ];
        }
        break;

      // Wowma(DeNA)
      case DbCommonUtil::MALL_CODE_BIDDERS:

        $result['info'][$mallCode] = [
          'load_data' => []
        ];

        foreach($files as $i => $file) {
          $beforeCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_wowma")->fetchColumn(0);

          // DeNAの受注CSVには、全く同じ行が複数存在する。数量を全て加算する必要があるので、一時テーブルにまず投入。
          $dbLog->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_tb_mall_order_wowma;");
          $dbLog->query("CREATE TEMPORARY TABLE tmp_work_tb_mall_order_wowma LIKE tb_mall_order_wowma;");
          $dbLog->query("ALTER TABLE tmp_work_tb_mall_order_wowma DROP KEY `uniq_注文`;");
          $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            INTO TABLE tmp_work_tb_mall_order_wowma
            FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n' IGNORE 1 LINES
            SET imported = CURRENT_TIMESTAMP
EOD;
          $stmt = $dbLog->prepare($sql);
          $stmt->bindValue(':importFilePath', $file->getPathname());
          $stmt->execute();

          // 同じ商品を合算してimport (これはひどい)
          $sql =<<<EOD
            INSERT IGNORE INTO tb_mall_order_wowma (
                `controlType`
              , `orderId`
              , `orderDetailId`
              , `orderDate`
              , `sellMethodSegment`
              , `releaseDate`
              , `siteAndDevice`
              , `itemManagementId`
              , `itemCode`
              , `lotnumber`
              , `itemName`
              , `itemOption`
              , `shippingDayDispText`
              , `shippingTimelimitDate`
              , `mailAddress`
              , `ordererName`
              , `ordererKana`
              , `ordererZipCode`
              , `ordererAddress`
              , `ordererPhoneNumber1`
              , `ordererPhoneNumber2`
              , `nickname`
              , `senderName`
              , `senderKana`
              , `senderZipCode`
              , `senderAddress`
              , `senderPhoneNumber1`
              , `senderPhoneNumber2`
              , `orderOption`
              , `settlementName`
              , `userComment`
              , `memo`
              , `orderStatus`
              , `contactStatus`
              , `contactDate`
              , `authorizationStatus`
              , `authorizationDate`
              , `paymentStatus`
              , `paymentDate`
              , `shipStatus`
              , `shipDate`
              , `printStatus`
              , `printDate`
              , `cancelStatus`
              , `cancelReason`
              , `cancelComment`
              , `itemPrice`
              , `beforeDiscount`
              , `discount`
              , `unit`
              , `totalItemPrice`
              , `taxType`
              , `postagePrice`
              , `chargePrice`
              , `totalPrice`
              , `couponTotalPrice`
              , `usePoint`
              , `usePointCancel`
              , `useAuPointPrice`
              , `useAuPoint`
              , `useAuPointCancel`
              , `requestPrice`
              , `totalGiftPoint`
              , `pointFixedDate`
              , `pointFixedStatus`
              , `settleStatus`
              , `pgResult`
              , `pgResponseCode`
              , `pgResponseDetail`
              , `pgOrderId`
              , `pgRequestPrice`
              , `authoriTimelimitDate`
              , `couponType`
              , `couponKey`
              , `cardJadgement`
              , `deliveryName`
              , `deliveryMethodId`
              , `deliveryId`
              , `deliveryRequestDay`
              , `deliveryRequestTime`
              , `shippingDate`
              , `shippingCarrier`
              , `shippingNumber`
              , `rawMailAddress`

              , `imported`
            )
            SELECT
                MAX(`controlType`)  AS `controlType`
              , MAX(`orderId`)  AS `orderId`
              , MAX(`orderDetailId`)  AS `orderDetailId`
              , MAX(`orderDate`)  AS `orderDate`
              , MAX(`sellMethodSegment`)  AS `sellMethodSegment`
              , MAX(`releaseDate`)  AS `releaseDate`
              , MAX(`siteAndDevice`)  AS `siteAndDevice`
              , MAX(`itemManagementId`)  AS `itemManagementId`
              , MAX(`itemCode`)  AS `itemCode`
              , MAX(`lotnumber`)  AS `lotnumber`
              , MAX(`itemName`)  AS `itemName`
              , MAX(`itemOption`)  AS `itemOption`
              , MAX(`shippingDayDispText`)  AS `shippingDayDispText`
              , MAX(`shippingTimelimitDate`)  AS `shippingTimelimitDate`
              , MAX(`mailAddress`)  AS `mailAddress`
              , MAX(`ordererName`)  AS `ordererName`
              , MAX(`ordererKana`)  AS `ordererKana`
              , MAX(`ordererZipCode`)  AS `ordererZipCode`
              , MAX(`ordererAddress`)  AS `ordererAddress`
              , MAX(`ordererPhoneNumber1`)  AS `ordererPhoneNumber1`
              , MAX(`ordererPhoneNumber2`)  AS `ordererPhoneNumber2`
              , MAX(`nickname`)  AS `nickname`
              , MAX(`senderName`)  AS `senderName`
              , MAX(`senderKana`)  AS `senderKana`
              , MAX(`senderZipCode`)  AS `senderZipCode`
              , MAX(`senderAddress`)  AS `senderAddress`
              , MAX(`senderPhoneNumber1`)  AS `senderPhoneNumber1`
              , MAX(`senderPhoneNumber2`)  AS `senderPhoneNumber2`
              , MAX(`orderOption`)  AS `orderOption`
              , MAX(`settlementName`)  AS `settlementName`
              , MAX(`userComment`)  AS `userComment`
              , MAX(`memo`)  AS `memo`
              , MAX(`orderStatus`)  AS `orderStatus`
              , MAX(`contactStatus`)  AS `contactStatus`
              , MAX(`contactDate`)  AS `contactDate`
              , MAX(`authorizationStatus`)  AS `authorizationStatus`
              , MAX(`authorizationDate`)  AS `authorizationDate`
              , MAX(`paymentStatus`)  AS `paymentStatus`
              , MAX(`paymentDate`)  AS `paymentDate`
              , MAX(`shipStatus`)  AS `shipStatus`
              , MAX(`shipDate`)  AS `shipDate`
              , MAX(`printStatus`)  AS `printStatus`
              , MAX(`printDate`)  AS `printDate`
              , MAX(`cancelStatus`)  AS `cancelStatus`
              , MAX(`cancelReason`)  AS `cancelReason`
              , MAX(`cancelComment`)  AS `cancelComment`
              , MAX(`itemPrice`)  AS `itemPrice`
              , MAX(`beforeDiscount`)  AS `beforeDiscount`
              , MAX(`discount`)  AS `discount`
              , SUM(`unit`)  AS `unit`
              , SUM(`totalItemPrice`)  AS `totalItemPrice`
              , MAX(`taxType`)  AS `taxType`
              , MAX(`postagePrice`)  AS `postagePrice`
              , MAX(`chargePrice`)  AS `chargePrice`
              , MAX(`totalPrice`)  AS `totalPrice`
              , MAX(`couponTotalPrice`)  AS `couponTotalPrice`
              , MAX(`usePoint`)  AS `usePoint`
              , MAX(`usePointCancel`)  AS `usePointCancel`
              , MAX(`useAuPointPrice`)  AS `useAuPointPrice`
              , MAX(`useAuPoint`)  AS `useAuPoint`
              , MAX(`useAuPointCancel`)  AS `useAuPointCancel`
              , MAX(`requestPrice`)  AS `requestPrice`
              , MAX(`totalGiftPoint`)  AS `totalGiftPoint`
              , MAX(`pointFixedDate`)  AS `pointFixedDate`
              , MAX(`pointFixedStatus`)  AS `pointFixedStatus`
              , MAX(`settleStatus`)  AS `settleStatus`
              , MAX(`pgResult`)  AS `pgResult`
              , MAX(`pgResponseCode`)  AS `pgResponseCode`
              , MAX(`pgResponseDetail`)  AS `pgResponseDetail`
              , MAX(`pgOrderId`)  AS `pgOrderId`
              , MAX(`pgRequestPrice`)  AS `pgRequestPrice`
              , MAX(`authoriTimelimitDate`)  AS `authoriTimelimitDate`
              , MAX(`couponType`)  AS `couponType`
              , MAX(`couponKey`)  AS `couponKey`
              , MAX(`cardJadgement`)  AS `cardJadgement`
              , MAX(`deliveryName`)  AS `deliveryName`
              , MAX(`deliveryMethodId`)  AS `deliveryMethodId`
              , MAX(`deliveryId`)  AS `deliveryId`
              , MAX(`deliveryRequestDay`)  AS `deliveryRequestDay`
              , MAX(`deliveryRequestTime`)  AS `deliveryRequestTime`
              , MAX(`shippingDate`)  AS `shippingDate`
              , MAX(`shippingCarrier`)  AS `shippingCarrier`
              , MAX(`shippingNumber`)  AS `shippingNumber`
              , MAX(`rawMailAddress`)  AS `rawMailAddress`

              , MAX(imported) AS imported
            FROM tmp_work_tb_mall_order_wowma tmp_o
            WHERE tmp_o.`orderId` <> ''
            GROUP BY  tmp_o.`orderId`
                    , tmp_o.`itemCode`
                    , tmp_o.`itemManagementId`
EOD;
          $dbLog->exec($sql);

          $afterCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_wowma")->fetchColumn(0);

          // ファイル名をモールがわかるものに変更（検証用履歴データ）
          $fileName = sprintf('%s_%s_%02d', $mallCode, $now->format('YmdHis'), $i + 1);
          $file->move($file->getPath(), $fileName);
          $file = new File(sprintf('%s/%s', $file->getPath(), $fileName));
          $files[$i] = $file;

          $result['info'][$mallCode]['load_data'][$file->getPathname()] = [
              'before' => $beforeCount
            , 'after' => $afterCount
            , 'num' => $afterCount - $beforeCount
          ];
        }
        break;

      // Rakuten
      case DbCommonUtil::MALL_CODE_RAKUTEN:
      case DbCommonUtil::MALL_CODE_RAKUTEN_MINNA:
      case DbCommonUtil::MALL_CODE_RAKUTEN_MOTTO:
      case DbCommonUtil::MALL_CODE_RAKUTEN_LAFOREST:
      case DbCommonUtil::MALL_CODE_RAKUTEN_DOLCISSIMO:
      case DbCommonUtil::MALL_CODE_RAKUTEN_GEKIPLA:

        $result['info'][$mallCode] = [
          'load_data' => []
        ];

        foreach($files as $i => $file) {
          $beforeCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_rakuten")->fetchColumn(0);
          $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            IGNORE INTO TABLE tb_mall_order_rakuten
            FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n' IGNORE 1 LINES
            SET imported = CURRENT_TIMESTAMP
              , mall_code = :mallCode
EOD;
          $stmt = $dbLog->prepare($sql);
          $stmt->bindValue(':importFilePath', $file->getPathname());
          $stmt->bindValue(':mallCode', $mallCode);
          $stmt->execute();

          // 代表商品コード補完
          $sql = <<<EOD
            UPDATE
            {$logDbName}.tb_mall_order_rakuten mo
            INNER JOIN tb_productchoiceitems pci ON mo.商品番号 = pci.ne_syohin_syohin_code
            SET mo.daihyo_syohin_code = pci.daihyo_syohin_code
            WHERE mo.daihyo_syohin_code = ''
              AND mo.convert_flg = 0
EOD;
          $dbMain->exec($sql);

          $afterCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_rakuten")->fetchColumn(0);

          // ファイル名をモールがわかるものに変更（検証用履歴データ）
          $fileName = sprintf('%s_%s_%02d', $mallCode, $now->format('YmdHis'), $i + 1);
          $file->move($file->getPath(), $fileName);
          $file = new File(sprintf('%s/%s', $file->getPath(), $fileName));
          $files[$i] = $file;

          $result['info'][$mallCode]['load_data'][$file->getPathname()] = [
              'before' => $beforeCount
            , 'after' => $afterCount
            , 'num' => $afterCount - $beforeCount
          ];
        }

        break;

      // Rakutenペイ
      case DbCommonUtil::MALL_CODE_RAKUTEN_PAY:
      case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_MINNA:
      case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_MOTTO:
      case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_LAFOREST:
      case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_DOLCISSIMO:
      case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_GEKIPLA:

        $result['info'][$mallCode] = [
          'load_data' => []
        ];

        foreach($files as $i => $file) {
          $beforeCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_rakuten_pay")->fetchColumn(0);
          $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            IGNORE INTO TABLE tb_mall_order_rakuten_pay
            FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n' IGNORE 1 LINES
            SET imported = CURRENT_TIMESTAMP
              , mall_code = :mallCode
EOD;
          $stmt = $dbLog->prepare($sql);
          $stmt->bindValue(':importFilePath', $file->getPathname());
          $stmt->bindValue(':mallCode', $mallCode);
          $stmt->execute();

          // 代表商品コード補完
          $sql = <<<EOD
            UPDATE
            {$logDbName}.tb_mall_order_rakuten_pay mo
            INNER JOIN tb_productchoiceitems pci ON CAST(mo.商品番号 as CHAR) = pci.ne_syohin_syohin_code
            SET mo.daihyo_syohin_code = pci.daihyo_syohin_code
            WHERE mo.daihyo_syohin_code = ''
              AND mo.convert_flg = 0
EOD;
          $dbMain->exec($sql);

          $afterCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_rakuten_pay")->fetchColumn(0);

          // ファイル名をモールがわかるものに変更（検証用履歴データ）
          $fileName = sprintf('%s_%s_%02d', $mallCode, $now->format('YmdHis'), $i + 1);
          $file->move($file->getPath(), $fileName);
          $file = new File(sprintf('%s/%s', $file->getPath(), $fileName));
          $files[$i] = $file;

          $result['info'][$mallCode]['load_data'][$file->getPathname()] = [
              'before' => $beforeCount
            , 'after' => $afterCount
            , 'num' => $afterCount - $beforeCount
          ];
        }

        break;

      // Amazon
      case DbCommonUtil::MALL_CODE_AMAZON:
        $result['info'][$mallCode] = $neMallProcess->importMallOrderAmazon($files, $now);

        break;

      // Yahoo群
      case DbCommonUtil::MALL_CODE_PLUSNAO_YAHOO: // fallthrough
      case DbCommonUtil::MALL_CODE_KAWA_YAHOO: // fallthrough
      case DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO:

        $result['info'][$mallCode] = [
          'load_data' => [
              'order' => []
            , 'item' => []
          ]
        ];

        // 一時テーブルを作成して格納。（order と itemの一致をチェックしてから格納するため）
        // ここですでにそろってなければ即エラー
        if (empty($files['order']) || empty($files['item'])) {
          throw new \RuntimeException($mallCode . ' の order と item がそろっていません。');
        }

        $temporaryWord = ' TEMPORARY ';
        // $temporaryWord = ' '; // FOR DEBUG
        $dbLog->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_mall_order_yahoo_order");
        $dbLog->exec("CREATE {$temporaryWord} TABLE tmp_work_mall_order_yahoo_order LIKE {$logDbName}.tb_mall_order_yahoo_order");

        $dbLog->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_mall_order_yahoo_item");
        $dbLog->exec("CREATE {$temporaryWord} TABLE tmp_work_mall_order_yahoo_item LIKE {$logDbName}.tb_mall_order_yahoo_item");

        // order
        /** @var  File $file */
        foreach($files['order'] as $i => $file) {
          $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            IGNORE INTO TABLE tmp_work_mall_order_yahoo_order
            FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n' IGNORE 1 LINES
            SET imported = CURRENT_TIMESTAMP
              , mall_code = :mallCode
EOD;
          $stmt = $dbLog->prepare($sql);
          $stmt->bindValue(':importFilePath', $file->getPathname());
          $stmt->bindValue(':mallCode', $mallCode);
          $stmt->execute();

          // ファイル名をモールがわかるものに変更（検証用履歴データ）
          $fileName = sprintf('%s_order_%s_%02d', $mallCode, $now->format('YmdHis'), $i + 1);
          $file->move($file->getPath(), $fileName);
          $file = new File(sprintf('%s/%s', $file->getPath(), $fileName));
          $files[$i] = $file;
        }

        // item
        /** @var  File $file */
        foreach($files['item'] as $i => $file) {
          $sql = <<<EOD
            LOAD DATA LOCAL INFILE :importFilePath
            IGNORE INTO TABLE tmp_work_mall_order_yahoo_item
            FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
            LINES TERMINATED BY '\n' IGNORE 1 LINES
            SET imported = CURRENT_TIMESTAMP
              , mall_code = :mallCode
EOD;
          $stmt = $dbLog->prepare($sql);
          $stmt->bindValue(':importFilePath', $file->getPathname());
          $stmt->bindValue(':mallCode', $mallCode);
          $stmt->execute();

          // ファイル名をモールがわかるものに変更（検証用履歴データ）
          $fileName = sprintf('%s_item_%s_%02d', $mallCode, $now->format('YmdHis'), $i + 1);
          $file->move($file->getPath(), $fileName);
          $file = new File(sprintf('%s/%s', $file->getPath(), $fileName));
          $files[$i] = $file;
        }

        // 伝票と明細の一致チェック
        $sql = <<<EOD
          SELECT
            COUNT(*) AS cnt
          FROM tmp_work_mall_order_yahoo_order o
          LEFT JOIN tmp_work_mall_order_yahoo_item i ON o.OrderId = i.OrderId
          WHERE i.OrderId IS NULL
EOD;
        $diff = $dbLog->query($sql)->fetchColumn(0);
        $this->get('misc.util.batch_logger')->info($diff);
        if ($diff > 0) {
          throw new \RuntimeException(sprintf('%s の注文データと商品データが一致しません。(item のない order : %d)', $mallCode, $diff));
        }

        $sql = <<<EOD
          SELECT
            COUNT(*) AS cnt
          FROM tmp_work_mall_order_yahoo_item i
          LEFT JOIN tmp_work_mall_order_yahoo_order o ON i.OrderId = o.OrderId
          WHERE o.OrderId IS NULL
EOD;
        $diff = $dbLog->query($sql)->fetchColumn(0);
        $this->get('misc.util.batch_logger')->info($diff);
        if ($diff > 0) {
          throw new \RuntimeException(sprintf('%s の注文データと商品データが一致しません。(order のない item : %d)', $mallCode, $diff));
        }

        // 一致に問題なければ、本テーブルへ格納
        $beforeCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_yahoo_order")->fetchColumn(0);
        $fieldOrders = implode(
            ','
          , array_reduce(
              array_merge(self::$CSV_FIELDS_YAHOO_ORDER, ['imported', 'mall_code'])
            , function($result, $field) use ($dbLog) {
                if ($field == 'Id') {
                  $field = 'MallOrderId';
                }
                $result[] = $dbLog->quoteIdentifier($field);
                return $result;
              }
            , []
          )
        );
        $sql = <<<EOD
          INSERT IGNORE INTO tb_mall_order_yahoo_order ({$fieldOrders})
          SELECT {$fieldOrders} FROM tmp_work_mall_order_yahoo_order
EOD;
        $dbLog->exec($sql);

        $afterCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_yahoo_order")->fetchColumn(0);
        $result['info'][$mallCode]['load_data']['order'] = [
            'before' => $beforeCount
          , 'after' => $afterCount
          , 'num' => $afterCount - $beforeCount
        ];

        $beforeCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_yahoo_item")->fetchColumn(0);
        $fieldItems = implode(
            ','
          , array_reduce(
              array_merge(self::$CSV_FIELDS_YAHOO_ITEM, ['imported', 'mall_code'])
            , function($result, $field) use ($dbLog) {
                if ($field == 'Id') {
                  $field = 'MallOrderId';
                }
                $result[] = $dbLog->quoteIdentifier($field);
                return $result;
              }
            , []
          )
        );
        $sql = <<<EOD
            INSERT IGNORE INTO tb_mall_order_yahoo_item ({$fieldItems})
            SELECT {$fieldItems} FROM tmp_work_mall_order_yahoo_item
EOD;
        $dbLog->exec($sql);

        $afterCount = $dbLog->query("SELECT COUNT(*) FROM tb_mall_order_yahoo_item")->fetchColumn(0);
        $result['info'][$mallCode]['load_data']['item'] = [
            'before' => $beforeCount
          , 'after' => $afterCount
          , 'num' => $afterCount - $beforeCount
        ];

        break;

      default:
        // throw new \RuntimeException('実装されていないモールです。');
        break;
    }

  }


  protected static $CSV_FIELDS_PPM = [
      '注文番号'
    , '注文日時'
    , '担当者'
    , '注文者名字'
    , '注文者名前'
    , '注文者名字フリガナ'
    , '注文者名前フリガナ'
    , '注文者郵便番号1'
    , '注文者郵便番号2'
    , '注文者住所：都道府県'
    , '注文者住所：市区町村以降'
    , '注文者電話番号'
    , 'メールキャリアコード'
    , '会員フラグ'
    , '利用端末'
    , '送付先名字'
    , '送付先名前'
    , '送付先名字フリガナ'
    , '送付先名前フリガナ'
    , '送付先郵便番号1'
    , '送付先郵便番号2'
    , '送付先住所：都道府県'
    , '送付先住所：市区町村以降'
    , '送付先電話番号'
    , '送付先一致フラグ'
    , '商品管理ID'
    , '商品ID（表示用）'
    , '商品名'
    , '商品ID'
    , '個数'
    , '単価'
    , '購入オプション'
    , '送料無料・別'
    , '代引手数料込別'
    , '商品URL'
    , '在庫タイプ'
    , '税込別'
    , '税込別(リボン)'
    , '税込別(包装紙)'
    , 'ラッピング種類(リボン)'
    , 'ラッピング種類(包装紙)'
    , 'ラッピング料金(リボン)'
    , 'ラッピング料金(包装紙)'
    , 'ギフト配送（0:希望なし/1:希望あり）'
    , '合計'
    , '送料(-99999=無効値)'
    , '消費税(-99999=無効値)'
    , '代引料(-99999=無効値)'
    , '合計金額(-99999=無効値)'
    , 'ポイント利用有無'
    , 'ポイント利用額'
    , '請求金額(-99999=無効値)'
    , 'のし'
    , '決済方法'
    , 'クレジットカード種類'
    , 'クレジットカード分割選択'
    , 'クレジットカード分割備考'
    , 'カード決済ステータス'
    , '入金日'
    , '発送日'
    , '配送方法'
    , '配送区分'
    , 'お荷物伝票番号'
    , 'お届け時間帯'
    , 'お届け日指定'
    , 'コメント'
    , '作業メモ'
    , '受注ステータス'
    , 'メールフラグ'
    , 'メール差込文(お客様へのメッセージ)'
    , '同梱ID'
    , '同梱ステータス'
    , '同梱ポイント利用合計'
    , '同梱合計金額'
    , '同梱商品合計金額'
    , '同梱消費税合計'
    , '同梱請求金額'
    , '同梱送料合計'
    , '同梱代引料合計'
    , 'ポイント付与率'
    , '付与ポイント数'
    , 'クーポン利用額'
    , 'クーポン利用額内訳（ショップ発行送料分）'
    , 'クーポン利用額内訳（ショップ発行商品分）'
    , 'クーポン利用額内訳（リクルート発行送料分）'
    , 'クーポン利用額内訳（リクルート発行商品分）'
    , '同梱注文クーポン利用額'
    , '警告表示フラグ'
    , 'システム利用料対象額'
  ];

  protected static $CSV_FIELDS_YAHOO_ORDER = [
      'OrderId'
    , 'ParentOrderId'
    , 'DeviceType'
    , 'IsAffiliate'
    , 'FspLicenseCode'
    , 'FspLicenseName'
    , 'OrderTime'
    , 'OrderTimeUnixEpoch'
    , 'UsePointType'
    , 'OrderStatus'
    , 'StoreStatus'
    , 'Referer'
    , 'EntryPoint'
    , 'Clink'
    , 'SuspectMessage'
    , 'IsItemCoupon'
    , 'IsShippingCoupon'
    , 'ShipName'
    , 'ShipFirstName'
    , 'ShipLastName'
    , 'ShipAddress1'
    , 'ShipAddress2'
    , 'ShipCity'
    , 'ShipPrefecture'
    , 'ShipZipCode'
    , 'ShipNameKana'
    , 'ShipFirstNameKana'
    , 'ShipLastNameKana'
    , 'ShipAddress1Kana'
    , 'ShipAddress2Kana'
    , 'ShipCityKana'
    , 'ShipPrefectureKana'
    , 'ShipSection1Field'
    , 'ShipSection1Value'
    , 'ShipSection2Field'
    , 'ShipSection2Value'
    , 'ShipPhoneNumber'
    , 'ShipEmgPhoneNumber'
    , 'ShipMethod'
    , 'ShipMethodName'
    , 'ShipRequestDate'
    , 'ShipRequestTime'
    , 'ShipNotes'
    , 'ArriveType'
    , 'ShipInvoiceNumber1'
    , 'ShipInvoiceNumber2'
    , 'ShipUrl'
    , 'ShipDate'
    , 'GiftWrapType'
    , 'GiftWrapPaperType'
    , 'GiftWrapName'
    , 'NeedBillSlip'
    , 'NeedDetailedSlip'
    , 'NeedReceipt'
    , 'Option1Field'
    , 'Option1Value'
    , 'Option2Field'
    , 'Option2Value'
    , 'GiftWrapMessage'
    , 'BillName'
    , 'BillFirstName'
    , 'BillLastName'
    , 'BillAddress1'
    , 'BillAddress2'
    , 'BillCity'
    , 'BillPrefecture'
    , 'BillZipCode'
    , 'BillNameKana'
    , 'BillFirstNameKana'
    , 'BillLastNameKana'
    , 'BillAddress1Kana'
    , 'BillAddress2Kana'
    , 'BillCityKana'
    , 'BillPrefectureKana'
    , 'BillSection1Field'
    , 'BillSection1Value'
    , 'BillSection2Field'
    , 'BillSection2Value'
    , 'BillPhoneNumber'
    , 'BillEmgPhoneNumber'
    , 'BillMailAddress'
    , 'PayMethod'
    , 'PayMethodName'
    , 'PayKind'
    , 'CardPayCount'
    , 'CardPayType'
    , 'SettleStatus'
    , 'SettleId'
    , 'PayNo'
    , 'PayNoIssueDate'
    , 'PayDate'
    , 'BuyerComments'
    , 'AgeConfirm'
    , 'QuantityDetail'
    , 'ShipCharge'
    , 'PayCharge'
    , 'GiftWrapCharge'
    , 'Discount'
    , 'UsePoint'
    , 'GetPoint'
    , 'Total'
    , 'TotalPrice'
    , 'ShippingCouponDiscount'
    , 'ItemCouponDiscount'
    , 'TotalMallCouponDiscount'
    , 'Id'
  ];

  protected static $CSV_FIELDS_YAHOO_ITEM = [
      'OrderId'
    , 'LineId'
    , 'Quantity'
    , 'ItemId'
    , 'SubCode'
    , 'Title'
    , 'ItemOptionName'
    , 'ItemOptionValue'
    , 'SubCodeOption'
    , 'InscriptionName'
    , 'InscriptionValue'
    , 'UnitPrice'
    , 'UnitGetPoint'
    , 'LineSubTotal'
    , 'LineGetPoint'
    , 'PointFspCode'
    , 'Condition'
    , 'CouponId'
    , 'CouponDiscount'
    , 'OriginalPrice'
    , 'IsGetPointFix'
    , 'GetPointFixDate'
    , 'ReleaseDate'
    , 'GetPointType'
    , 'Jan'
    , 'ProductId'
    , 'Id'
  ];

  protected static $CSV_FIELDS_Q10 = [
      '配送状態'
    , '注文番号'
    , 'カート番号'
    , '配送会社'
    , '送り状番号'
    , '発送日'
    , '発送予定日'
    , '商品名'
    , '数量'
    , 'オプション情報'
    , 'オプションコード'
    , '受取人名'
    , '販売者商品コード'
    , '外部広告'
    , '決済サイト'
  ];

  protected static $CSV_FIELDS_DENA = [
      '取引No.'
    , '管理No.'
    , 'ロットNo.'
    , 'タイトル'
    , '落札価格'
    , '個数'
    , '落札日'
    , 'ニックネーム'
    , 'Eメールアドレス'
    , '【取引管理】名前'
    , '【取引管理】住所'
    , '【取引管理】電話番号'
    , '【取引ナビ】名前'
    , '【取引ナビ】住所'
    , '【取引ナビ】電話番号'
    , '【取引ナビ】希望取引方法'
    , '【取引ナビ】コメント'
    , '【出品時設定】希望取引方法'
    , '【取引管理】実際の取引方法'
    , '連絡済み'
    , '連絡日'
    , '入金確認済み'
    , '入金確認日'
    , '発送済み'
    , '発送日'
    , '販売単価'
    , '販売個数'
    , '小計'
    , '消費税'
    , '手数料'
    , '送料'
    , '請求金額'
    , '取引メモ'
    , '【取引ナビ】送付先氏名'
    , '【取引ナビ】送付先住所'
    , '【取引ナビ】送付先電話番号'
    , '【取引ナビ】落札者カナ'
    , '【取引ナビ】落札者日中連絡先'
    , '【取引ナビ】落札者メールアドレス'
    , '【取引ナビ】送付先カナ'
    , '【取引ナビ】送付先日中連絡先'
    , '販売総額'
    , '販売総数'
    , '消費税区分'
    , 'キャンセル'
    , 'アイテムオプション'
    , '(旧)取引No.'
    , 'カード種類'
    , 'カード番号'
    , '有効期限・年'
    , '有効期限・月'
    , 'カード名義人'
    , '名義人生年月日'
    , 'オークションタイプ'
    , 'ホームサイト'
    , '商品コード'
    , '総合計'
    , 'ポイント利用分'
    , '利用キャンセル状況'
    , '付与ポイント数'
    , 'CB原資付与ポイント数'
    , '付与ポイント確定(予定)日'
    , '付与ポイント状況'
    , '取引オプション'
    , 'クレジットカードオプション'
  ];

  protected static $CSV_FIELDS_WOWMA = [
      'controlType'
    , 'orderId'
    , 'orderDetailId'
    , 'orderDate'
    , 'sellMethodSegment'
    , 'releaseDate'
    , 'siteAndDevice'
    , 'itemManagementId'
    , 'itemCode'
    , 'lotnumber'
    , 'itemName'
    , 'itemOption'
    , 'shippingDayDispText'
    , 'shippingTimelimitDate'
    , 'mailAddress'
    , 'ordererName'
    , 'ordererKana'
    , 'ordererZipCode'
    , 'ordererAddress'
    , 'ordererPhoneNumber1'
    , 'ordererPhoneNumber2'
    , 'nickname'
    , 'senderName'
    , 'senderKana'
    , 'senderZipCode'
    , 'senderAddress'
    , 'senderPhoneNumber1'
    , 'senderPhoneNumber2'
    , 'orderOption'
    , 'settlementName'
    , 'userComment'
    , 'memo'
    , 'orderStatus'
    , 'contactStatus'
    , 'contactDate'
    , 'authorizationStatus'
    , 'authorizationDate'
    , 'paymentStatus'
    , 'paymentDate'
    , 'shipStatus'
    , 'shipDate'
    , 'printStatus'
    , 'printDate'
    , 'cancelStatus'
    , 'cancelReason'
    , 'cancelComment'
    , 'itemPrice'
    , 'beforeDiscount'
    , 'discount'
    , 'unit'
    , 'totalItemPrice'
    , 'taxType'
    , 'postagePrice'
    , 'chargePrice'
    , 'totalPrice'
    , 'couponTotalPrice'
    , 'usePoint'
    , 'usePointCancel'
    , 'useAuPointPrice'
    , 'useAuPoint'
    , 'useAuPointCancel'
    , 'requestPrice'
    , 'totalGiftPoint'
    , 'pointFixedDate'
    , 'pointFixedStatus'
    , 'settleStatus'
    , 'pgResult'
    , 'pgResponseCode'
    , 'pgResponseDetail'
    , 'pgOrderId'
    , 'pgRequestPrice'
    , 'authoriTimelimitDate'
    , 'couponType'
    , 'couponKey'
    , 'cardJadgement'
    , 'deliveryName'
    , 'deliveryMethodId'
    , 'deliveryId'
    , 'deliveryRequestDay'
    , 'deliveryRequestTime'
    , 'shippingDate'
    , 'shippingCarrier'
    , 'shippingNumber'
    , 'rawMailAddress'
  ];

  protected static $CSV_FIELDS_RAKUTEN = [
      '受注番号'
    , '受注ステータス'
    , 'カード決済ステータス'
    , '入金日'
    , '配送日'
    , 'お届け時間帯'
    , 'お届け日指定'
    , '担当者'
    , 'ひとことメモ'
    , 'メール差込文(お客様へのメッセージ)'
    , '初期購入合計金額'
    , '利用端末'
    , 'メールキャリアコード'
    , 'ギフトチェック（0:なし/1:あり）'
    , 'コメント'
    , '注文日時'
    , '複数送付先フラグ'
    , '警告表示フラグ'
    , '楽天会員フラグ'
    , '合計'
    , '消費税(-99999=無効値)'
    , '送料(-99999=無効値)'
    , '代引料(-99999=無効値)'
    , '請求金額(-99999=無効値)'
    , '合計金額(-99999=無効値)'
    , '同梱ID'
    , '同梱ステータス'
    , '同梱商品合計金額'
    , '同梱送料合計'
    , '同梱代引料合計'
    , '同梱消費税合計'
    , '同梱請求金額'
    , '同梱合計金額'
    , '同梱楽天バンク決済振替手数料'
    , '同梱ポイント利用合計'
    , 'メールフラグ'
    , '注文日'
    , '注文時間'
    , 'モバイルキャリア決済番号'
    , '購入履歴修正可否タイプ'
    , '購入履歴修正アイコンフラグ'
    , '購入履歴修正催促メールフラグ'
    , '送付先一致フラグ'
    , 'ポイント利用有無'
    , '注文者郵便番号１'
    , '注文者郵便番号２'
    , '注文者住所：都道府県'
    , '注文者住所：都市区'
    , '注文者住所：町以降'
    , '注文者名字'
    , '注文者名前'
    , '注文者名字フリガナ'
    , '注文者名前フリガナ'
    , '注文者電話番号１'
    , '注文者電話番号２'
    , '注文者電話番号３'
    , 'メールアドレス'
    , '注文者性別'
    , '注文者誕生日'
    , '決済方法'
    , 'クレジットカード種類'
    , 'クレジットカード番号'
    , 'クレジットカード名義人'
    , 'クレジットカード有効期限'
    , 'クレジットカード分割選択'
    , 'クレジットカード分割備考'
    , '配送方法'
    , '配送区分'
    , 'ポイント利用額'
    , 'ポイント利用条件'
    , 'ポイントステータス'
    , '楽天バンク決済ステータス'
    , '楽天バンク振替手数料負担区分'
    , '楽天バンク決済手数料'
    , 'ラッピングタイトル(包装紙)'
    , 'ラッピング名(包装紙)'
    , 'ラッピング料金(包装紙)'
    , '税込別(包装紙)'
    , 'ラッピングタイトル(リボン)'
    , 'ラッピング名(リボン)'
    , 'ラッピング料金(リボン)'
    , '税込別(リボン)'
    , '送付先送料'
    , '送付先代引料'
    , '送付先消費税'
    , 'お荷物伝票番号'
    , '送付先商品合計金額'
    , 'のし'
    , '送付先郵便番号１'
    , '送付先郵便番号２'
    , '送付先住所：都道府県'
    , '送付先住所：都市区'
    , '送付先住所：町以降'
    , '送付先名字'
    , '送付先名前'
    , '送付先名字フリガナ'
    , '送付先名前フリガナ'
    , '送付先電話番号１'
    , '送付先電話番号２'
    , '送付先電話番号３'
    , '商品ID'
    , '商品名'
    , '商品番号'
    , '商品URL'
    , '単価'
    , '個数'
    , '送料込別'
    , '税込別'
    , '代引手数料込別'
    , '項目・選択肢'
    , 'ポイント倍率'
    , 'ポイントタイプ'
    , 'レコードナンバー'
    , '納期情報'
    , '在庫タイプ'
    , 'ラッピング種類(包装紙)'
    , 'ラッピング種類(リボン)'
    , 'あす楽希望'
    , 'クーポン利用額'
    , '店舗発行クーポン利用額'
    , '楽天発行クーポン利用額'
    , '同梱注文クーポン利用額'
    , '配送会社'
    , '薬事フラグ'
    , '楽天スーパーDEAL'
    , 'メンバーシッププログラム'
  ];

  protected static $CSV_FIELDS_RAKUTEN_PAY = [
      '注文番号'
    , 'ステータス'
    , 'サブステータスID'
    , 'サブステータス'
    , '注文日時'
    , '注文日'
    , '注文時間'
    , 'キャンセル期限日'
    , '注文確認日時'
    , '注文確定日時'
    , '発送指示日時'
    , '発送完了報告日時'
    , '支払方法名'
    , 'クレジットカード支払い方法'
    , 'クレジットカード支払い回数'
    , '配送方法'
    , '配送区分'
    , '注文種別'
    , '複数送付先フラグ'
    , '送付先一致フラグ'
    , '離島フラグ'
    , '楽天確認中フラグ'
    , '警告表示タイプ'
    , '楽天会員フラグ'
    , '購入履歴修正有無フラグ'
    , '商品合計金額'
    , '消費税合計'
    , '送料合計'
    , '代引料合計'
    , '請求金額'
    , '合計金額'
    , 'ポイント利用額'
    , 'クーポン利用総額'
    , '店舗発行クーポン利用額'
    , '楽天発行クーポン利用額'
    , '注文者郵便番号1'
    , '注文者郵便番号2'
    , '注文者住所都道府県'
    , '注文者住所郡市区'
    , '注文者住所それ以降の住所'
    , '注文者姓'
    , '注文者名'
    , '注文者姓カナ'
    , '注文者名カナ'
    , '注文者電話番号1'
    , '注文者電話番号2'
    , '注文者電話番号3'
    , '注文者メールアドレス'
    , '注文者性別'
    , '申込番号'
    , '申込お届け回数'
    , '送付先ID'
    , '送付先送料'
    , '送付先代引料'
    , '送付先消費税合計'
    , '送付先商品合計金額'
    , '送付先合計金額'
    , 'のし'
    , '送付先郵便番号1'
    , '送付先郵便番号2'
    , '送付先住所都道府県'
    , '送付先住所郡市区'
    , '送付先住所それ以降の住所'
    , '送付先姓'
    , '送付先名'
    , '送付先姓カナ'
    , '送付先名カナ'
    , '送付先電話番号1'
    , '送付先電話番号2'
    , '送付先電話番号3'
    , '商品明細ID'
    , '商品ID'
    , '商品名'
    , '商品番号'
    , '商品管理番号'
    , '単価'
    , '個数'
    , '送料込別'
    , '税込別'
    , '代引手数料込別'
    , '項目・選択肢'
    , 'ポイント倍率'
    , '納期情報'
    , '在庫タイプ'
    , 'ラッピングタイトル1'
    , 'ラッピング名1'
    , 'ラッピング料金1'
    , 'ラッピング税込別1'
    , 'ラッピング種類1'
    , 'ラッピングタイトル2'
    , 'ラッピング名2'
    , 'ラッピング料金2'
    , 'ラッピング税込別2'
    , 'ラッピング種類2'
    , 'お届け時間帯'
    , 'お届け日指定'
    , '担当者'
    , 'ひとことメモ'
    , 'メール差込文 (お客様へのメッセージ)'
    , 'ギフト配送希望'
    , 'コメント'
    , '利用端末'
    , 'メールキャリアコード'
    , 'あす楽希望フラグ'
    , '医薬品受注フラグ'
    , '楽天スーパーDEAL商品受注フラグ'
    , 'メンバーシッププログラム受注タイプ'
  ];

  public function sftpTestAction()
  {
    $ftp = new SFTP('upload.rakuten.ne.jp');

    try {
      $ftp->login('plusnao', 'Yoshiko9');
    } catch (\Exception $e) {
      return new JsonResponse($e->getMessage());
    }

    return new JsonResponse($ftp->nlist('./'));
  }
}
