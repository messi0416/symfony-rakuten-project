<?php
/**
 * Yahoo CSV出力処理
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace BatchBundle\Command;

use BatchBundle\Command\ExportCsvYahooCommand;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\Repository\ProductImagesRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;
use MiscBundle\Entity\TbShippingdivision;

class ExportCsvYahooOtoriyoseCommand extends PlusnaoBaseCommand
{
  private $exportParentPath;
  private $exportPath;

  private $skipCommonProcess = false;

  /**
   * ファイル名に付与する日時。連番にするために必要っぽい
   * @see http://www.realmax.co.jp/system/e-commerce/post-12302/）
   * @var  \DateTime
   */
  private $fileTimeStamp;

  // アップロードファイルの分割設定件数
  const QUANTITY_MAX = 9999; // quantity 数量 最大値（NextEngineとの自動連携の数値に合わせたもの）

  const EXPORT_TARGET_OTORIYOSE = 'otoriyose';
  const PRODUCT_STOCK_TABLE_OTORIYOSE = 'tb_yahoo_otoriyose_product_stock';

  public static $EXPORT_TARGET_LIST = [
    self::EXPORT_TARGET_OTORIYOSE
  ];

  const DOWNLOADED_DIR = 'Yahoo/Import'; // ひとまず同居

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-yahoo-otoriyose')
      ->setDescription('CSVエクスポート Yahoo(Otoriyose)')
      ->addArgument('export-dir', InputArgument::REQUIRED, '出力先ディレクトリ', null)
      ->addOption('export-target', null, InputOption::VALUE_REQUIRED, '出力対象', NULL)// 1件のみ
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('import-path', null, InputOption::VALUE_OPTIONAL, 'インポートファイルパス', null)
      ->addOption('skip-common-process', null, InputOption::VALUE_OPTIONAL, '共通処理をスキップ', '0')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = 'ヤフーCSV出力処理[otoriyose]';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    // 親ディレクトリ
    $this->exportParentPath = $input->getArgument('export-dir');

    if (!strlen($this->exportParentPath)) {
      throw new RuntimeException('export path is not determined. (Yahoo otoriyose CSV)');
    }

    // なければエラー終了
    $fs = new FileSystem();
    if (!$fs->exists($this->exportParentPath)) {
      throw new RuntimeException('export path is not exists. (Yahoo otoriyose CSV). [' . $this->exportParentPath . ']');
    }

    // 共通処理スキップフラグ
    $this->skipCommonProcess = boolval($input->getOption('skip-common-process'));

    // 対象モールごとに処理
    $currentTarget = $input->getOption('export-target');

    if (!$currentTarget) {
      throw new RuntimeException('CSV出力対象が選択されていません。(otoriyose)');
    }
    $logger->info('ヤフーCSV出力対象: ' . $currentTarget);

    $fileUtil = $this->getFileUtil();

    $dbMain = $this->getDb('main');

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    // モール別ループ
    $this->stopwatch->start($currentTarget);

    // 共通処理
    // ※「受発注可能フラグ退避」「復元」は、同様の挙動になるようにフラグを確認して処理し、
    //   フラグのコピー・戻し処理は移植しない。
    //   「受発注可能フラグ」を判定している箇所すべてについて、
    //   (「受発注可能フラグ」= True AND 「受発注可能フラグ退避F」 = False) とする。
    //   => pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0
    if (!$this->skipCommonProcess) {
      $logExecTitle = sprintf('ヤフーCSV出力処理[%s][共通処理]', $currentTarget);
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $commonUtil->exportCsvCommonProcess($logger, null);
      $commonUtil->calculateMallPrice($logger, DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));
    }

    // ログ出力 EXEC_TITLE 切り替え
    $logger->setExecTitle($this->commandName);
    $logger->initLogTimer(true);

    // DB記録＆通知処理
    $logger->addDbLog($logger->makeDbLog(null, $this->commandName, '開始'));

    // 出力ディレクトリ 作成
    $this->exportPath = sprintf('%s/%s', $this->exportParentPath, $currentTarget);
    $fs = new FileSystem();
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }

    try {
      // CSV出力 データ作成処理 実装

      // '====================
      // Call YAHOOディレクトリID補正___
      // '未入力のYAHOOディレクトリIDにデフォルトのIDを設定
      // '====================
      $sql = <<<EOD
      UPDATE tb_mainproducts AS M
      INNER JOIN tb_plusnaoproductdirectory AS PD ON M.NEディレクトリID = PD.NEディレクトリID
      SET M.YAHOOディレクトリID = PD.YAHOOディレクトリID
      WHERE COALESCE(M.YAHOOディレクトリID, '') = ''
        AND COALESCE(PD.YAHOOディレクトリID, '') <> ''
EOD;
      $dbMain->query($sql);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('YAHOOディレクトリID補正', $currentTarget));

      // '====================
      // Call Prepare___()
      // '====================
      // Call setYahooInformation___()
      $this->updateYahooInformation($currentTarget);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('updateYahooInformation', $currentTarget));

      // Call prepareData___()
      $this->prepareData($currentTarget);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('prepareData', $currentTarget));

      // Call prepareStock___()
      $this->prepareStock();
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('prepareStock', $currentTarget));

      // '====================
      // Call Export___(lc_export_dir, )
      // '====================
      $this->export($currentTarget, $input->getOption('import-path'));
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('export', $currentTarget));

      // '====================
      // Call NE更新カラムリセット
      // ここだけ、帳尻（処理が終わったらリセットされている、という出口）を合わせるため残す。
      // '====================
      $commonUtil->resetNextEngineUpdateColumn($logger);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('resetNextEngineUpdateColumn', $currentTarget));

      $finder = new Finder(); // 結果ファイル確認
      $message = '';
      $fileNum = $finder->in($this->exportPath)->files()->count();
      if (!$fileNum) {
        $message = 'CSVファイルが作成されませんでした。処理を完了します。';
      }
      $event = $this->stopwatch->stop($currentTarget);
      $logger->info(sprintf('%s: duration: %.02f / memory: %s', $currentTarget, $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $event = $this->stopwatch->stop('main');

      // 出力ディレクトリが空なら削除しておく
      $fs = new Filesystem();
      if ($this->exportPath && $fs->exists($this->exportPath)) {
        $finder = new Finder();
        if ($finder->in($this->exportPath)->count() == 0) {
          $fs->remove($this->exportPath);
        }
      }
      throw $e;
    }
    $event = $this->stopwatch->stop('main');
    $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));
  }

  /**
   * CSV出力処理
   * @param string $exportTarget
   * @param string $importCsvPath
   * @throws \Doctrine\DBAL\DBALException
   */
  private function export($exportTarget, $importCsvPath)
  {
    $logger = $this->getLogger();
    $db = $this->getDb('main');

    $targetTable = $this->getDbCommonUtil()->getYahooTargetTableName($exportTarget);

    $logTitle = 'ヤフーCSV出力処理';
    $subTitle = 'Export___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    /** @var \MiscBundle\Util\StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $result = [
        'data_del.csv' => null
      , 'data_add.csv' => null
      , 'quantity.csv' => null
    ];

    $this->fileTimeStamp = new \DateTime();
    $this->fileTimeStamp->modify('+20 minutes'); // 画像チェックが20分程度で終わってくれることを期待

    // '====================
    // 'data_add.csv
    // '====================
    $logger->info('data_add.csv 作成中');

    $commonUtil = $this->getDbCommonUtil();

    // plusnao, kawaemon とも同じテーブルからの出力だが、中身は個別に作成されているため違う。
    $sql = <<<EOD
      SELECT
          a.path
        , a.name
        , a.code
        , a.`sub-code`
        , a.`original-price`
        , a.price
        , a.`sale-price`
        , a.options
        , a.headline
        , a.caption
        , a.abstract
        , a.explanation
        , a.additional1
        , a.additional2
        , a.additional3
        , a.`relevant-links`
        , a.`ship-weight`
        , a.taxable
        , a.`release-date`
        , a.`temporary-point-term`
        , a.`point-code`
        , a.`meta-key`
        , a.`meta-desc`
        , a.template
        , a.`sale-period-start`
        , a.`sale-period-end`
        , a.`sale-limit`
        , a.`sp-code`
        , a.`brand-code`
        , a.`person-code`
        , a.`yahoo-product-code`
        , a.`product-code`
        , a.jan
        , a.isbn
        , a.delivery
        , a.`astk-code`
        , a.condition
        , a.taojapan
        , a.`product-category`
        , a.spec1
        , a.spec2
        , a.spec3
        , a.spec4
        , a.spec5
        , a.display
        , a.sort as `sort_priority`
        , a.`sp-additional`
        , a.`lead-time-instock`
        , a.`pr-rate`

      FROM tb_yahoo_data_add a
      ORDER BY a.code
EOD;
    $stmt = $db->query($sql);

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
          'path'
        , 'name'
        , 'code'
        , 'sub-code'
        , 'original-price'
        , 'price'
        , 'sale-price'
        , 'options'
        , 'headline'
        , 'caption'
        , 'abstract'
        , 'explanation'
        , 'additional1'
        , 'additional2'
        , 'additional3'
        , 'relevant-links'
        , 'ship-weight'
        , 'taxable'
        , 'release-date'
        , 'temporary-point-term'
        , 'point-code'
        , 'meta-key'
        , 'meta-desc'
        , 'template'
        , 'sale-period-start'
        , 'sale-period-end'
        , 'sale-limit'
        , 'sp-code'
        , 'brand-code'
        , 'person-code'
        , 'yahoo-product-code'
        , 'product-code'
        , 'jan'
        , 'isbn'
        , 'delivery'
        , 'astk-code'
        , 'condition'
        , 'taojapan'
        , 'product-category'
        , 'spec1'
        , 'spec2'
        , 'spec3'
        , 'spec4'
        , 'spec5'
        , 'display'
        , 'sort_priority'
        , 'sp-additional'
        , 'lead-time-instock'
        , 'pr-rate'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // なぜかダブルクォートで囲まないフィールド ※ Access出力との一致確認用。本番ではすべて囲っておいた方が無難かも。
      $noEncloseFields = [
          'original-price'
        , 'price'
        , 'sale-price'
        , 'headline'
        , 'additional1'
        , 'additional2'
        , 'additional3'
        , 'relevant-links'
        , 'release-date'
        , 'temporary-point-term'
        , 'point-code'
        , 'sale-period-start'
        , 'sale-period-end'
        , 'sale-limit'
        , 'sp-code'
        , 'brand-code'
        , 'person-code'
        , 'yahoo-product-code'
        , 'product-code'
        , 'jan'
        , 'isbn'
      ];

      // データ
      $num = 0;
      $count = 0;
      // このファイルは10万件で分割する。
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/data_add%s%02d00.csv', $this->exportPath, $this->fileTimeStamp->format('YmdH'), intval($this->fileTimeStamp->format('i')) + $fileIndex++);
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        // 出力時データ加工

        // 文字数制限対応
        //'厳密にバイト数で切り捨ててアップロードしたが警告が発生したためザックリと文字数で切り捨てるように変更する
        //'そうなるともはやローカルで実行する意味はないのでサーバーで実行する
        //'出荷予定文言（backtitle）を付加してから切り捨てるとNGだったが、現在は問題ないためこのままここで。
        $row['explanation'] = mb_substr($row['explanation'], 0, 499, 'UTF-8');
        $row['caption'] = mb_substr($row['caption'], 0, 4999, 'UTF-8');

        // 出力
        $line = $stringUtil->convertArrayToCsvLine($row, $headers, $noEncloseFields);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;

        if ($count >= ExportCsvYahooCommand::UPLOAD_CSV_MAX_NUM_DATA_ADD) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      fclose($fp);

      $result['data_add.csv'] = $num;
      $logger->info("Yahoo CSV出力 data_add.csv: $num 件");

    } else {
      $logger->info("Yahoo CSV出力 data_add.csv: 件数が0のためファイルは作成しませんでした。");
    }

    // '====================
    // 'quantity.csv
    // '====================
    // plusnao, kawaemon とも同じテーブルからの出力だが、中身は個別に作成されているため違う。
    $sql = <<<EOD
      SELECT
          a.code
        , a.`sub-code`
        , a.quantity
      FROM tb_yahoo_quantity_add a
      ORDER BY
          a.code
        , a.`sub-code`
EOD;
    $stmt = $db->query($sql);

    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
          'code'
        , 'sub-code'
        , 'quantity'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      // データ
      $num = 0;
      $count = 0;
      // このファイルは10万件で分割する。
      $fileIndex = 1;
      $fp = null;
      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($fp) || !$fp) {
          $filePath = sprintf('%s/quantity%s%02d00.csv', $this->exportPath, $this->fileTimeStamp->format('YmdH'), intval($this->fileTimeStamp->format('i')) + $fileIndex++);
          $fp = fopen($filePath, 'wb');
          fputs($fp, $headerLine);
        }

        $line = $stringUtil->convertArrayToCsvLine($row, $headers);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
        $count++;
        if ($count >= ExportCsvYahooCommand::UPLOAD_CSV_MAX_NUM_DATA_QUANTITY) {
          fclose($fp);
          unset($fp);
          $count = 0;
        }
      }
      if (isset($fp) && $fp) {
        fclose($fp);
      }

      $result['quantity.csv'] = $num;
      $logger->info("Yahoo CSV出力 quantity.csv: $num 件");

    } else {
      $logger->info("Yahoo CSV出力 quantity.csv: 件数が0のためファイルは作成しませんでした。");
    }

    // '====================
    // 'data_del.csv
    // '====================
    //'2014.01.30 削除せずに非表示にするよう仕様変更。エクスポートしない
    //'2015.06.18 もはやAccessに存在しない商品をエクスポート対象とするように変更
    // 2015.12.09 仕様：Accessに存在しない、またはモール別情報「出品フラグ」（registration_flg）がOFFのものは削除
    //            仕様：削除対象外商品コードは除外 (tb_delete_excluded_products.syohin_code)
    // 2016.09.06 仕様：出力対象外は全て削除( #8327 )
    $fs = new FileSystem();
    if ($importCsvPath && $fs->exists($importCsvPath)) {

      $logger->info('data_del.csv 作成中');
      $logger->info('Yahoo在庫データインポート');

      $commonUtil = $this->getDbCommonUtil();
      $mallId = $commonUtil->getMallIdByMallCode(DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO);

      $db->query("TRUNCATE tb_yahoo_otoriyose_product_stock");
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :importFilePath
        INTO TABLE tb_yahoo_otoriyose_product_stock
        FIELDS TERMINATED BY ',' ENCLOSED BY '' ESCAPED BY ''
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES
        (`code`, `sub-code`, `quantity`)
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':importFilePath', $importCsvPath);
      $stmt->execute();

      $sql = <<<EOD
        SELECT
          DISTINCT dl.code
        FROM tb_yahoo_otoriyose_product_stock dl
        LEFT JOIN tb_yahoo_data_add a ON dl.code = a.code
        WHERE a.code IS NULL
          AND NOT EXISTS (
              SELECT * FROM tb_delete_excluded_products dep
              WHERE dep.mall_id = :mallId
                AND dep.syohin_code = dl.code
          )
        ORDER BY dl.code
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':mallId', $mallId, \PDO::PARAM_INT);
      $stmt->execute();

      // 出力
      if ($stmt->rowCount()) {
        // ヘッダ
        $headers = [
          'code'
        ];
        $headerLine = $stringUtil->convertArrayToCsvLine($headers);
        $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

        // データ
        $num = 0;
        $filePath = sprintf('%s/data_del%s.csv', $this->exportPath, $this->fileTimeStamp->format('YmdHi00'));
        $fp = fopen($filePath, 'wb');
        fputs($fp, $headerLine);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
          $line = $stringUtil->convertArrayToCsvLine($row, $headers);
          $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
          fputs($fp, $line);

          $num++;
        }
        fclose($fp);

        $result['data_del.csv'] = $num;
        $logger->info("Yahoo CSV出力 data_del.csv: $num 件");

      } else {
        $logger->info("Yahoo CSV出力 data_del.csv: 件数が0のためファイルは作成しませんでした。");
      }
    }

    $this->processExecuteLog->setProcessNumber1($result['data_add.csv']); // 処理件数1
    $this->processExecuteLog->setProcessNumber2($result['quantity.csv']); // 処理件数2
    // 処理件数3はあとで設定（画像数）
    $this->processExecuteLog->setVersion(1.0);
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了')->setInformation($result));
  }

  /// データ作成
  private function updateYahooInformation($exportTarget)
  {
    $logger = $this->getLogger();

    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $targetTable = $commonUtil->getYahooTargetTableName($exportTarget);

    // 2017/01/06 一旦廃止。100%で固定で試してみます。
//    $widthSp = $commonUtil->getSettingValue('YAHOO_IMAGE_WIDTH_SP');
//    if (!$widthSp) {
//      $widthSp = 350;
//    }

    //'商品情報(explanation)の組み立て。ここはHTMLタグ不可。sp版商品情報まで構築してから500文字以内に切るので、ここでは文字数オーバー。
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      LEFT JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.explanation =
        CONCAT(
            CASE WHEN
              COALESCE(m.商品コメントPC, '') = '' THEN ''
              ELSE CONCAT(m.商品コメントPC, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`サイズについて`, '') = '' THEN ''
              ELSE CONCAT('【サイズについて】\n', m.`サイズについて`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`素材について`, '') = '' THEN ''
              ELSE CONCAT('【素材について】\n', m.`素材について`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`カラーについて`, '') = '' THEN ''
              ELSE CONCAT('【カラーについて】\n', m.`カラーについて`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`ブランドについて`, '') = '' THEN ''
              ELSE CONCAT('【ブランド】\n', m.`ブランドについて`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`使用上の注意`, '') = '' THEN ''
              ELSE CONCAT('【使用上の注意】\n', m.`使用上の注意`, '\n\n')
            END
          , CASE
              WHEN COALESCE(m.`補足説明PC`, '') = '' THEN ''
              ELSE CONCAT('【補足説明】\n', m.`補足説明PC`, '\n\n')
            END
        )
      WHERE cal.endofavailability is NULL
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: 商品情報(explanation)の組み立て', $exportTarget));

    // 商品説明(caption)とスマートフォン用フリースペース(sp-additional)の組み立て。
    // ・caption←explanation 個別情報があればそれも追加、改行コードを<br>に変換も実施、末尾に<br>を付加もまとめて。
    // 　末尾に<br>付加は、前verでは「末尾が<br>でないときだけ<br>付加」だったが、UPDATEが1回増えてしまうので速度を考慮して常時<br>付加にしてみる。
    // ・sp-additional←explanation　ここは、商品情報の500文字を超える部分（HTML） + Yahoo用個別情報 + モールデザイン。
    // モールデザインは最後に tb_yahoo_data_add 構築時に足すのでここでは何もしない。
    // スマホ版は、現在のYahoo商品詳細画面は、
    // 商品情報とフリースペースが連続したデザイン（ http://tk2-217-18298.vs.sakura.ne.jp/issues/197154#note-7 参照）なので、
    // 改行などを入れない。
    $sql = <<<EOD
        UPDATE ${targetTable} AS i
        SET caption = CONCAT(
            REPLACE(REPLACE(REPLACE(REPLACE(
              i.explanation
            , '\\r\\n', '<br>') , '\\r', '<br>') , '\\n', '<br>') , '<br>', '<br>\\r\\n')
            , '<br>\\r\\n', IFNULL(i.`input_caption`, ''), '<br>\\r\\n')
          , i.`sp-additional` = CONCAT(
            REPLACE(REPLACE(REPLACE(REPLACE(
              SUBSTRING(i.explanation, 500) -- 500字に満たなければ空文字
            , '\\r\\n', '<br>') , '\\r', '<br>') , '\\n', '<br>') , '<br>', '<br>\\r\\n')
          , '<br>\\r\\n', IFNULL(i.`input_sp_additional`, ''), '<br>\\r\\n')
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: 商品説明(caption)、SP版追加情報（sp-additional）の組み立て', $exportTarget));

    // '=================
    // 'Path
    // '=================
    // 'Pathを初期化
    $dbMain->query("UPDATE ${targetTable} AS i SET i.path = ''");
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: Pathを初期化', $exportTarget));

    //'発売終了品に関しては画像が無くてもPATHを'soldout'に設定する
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.path = CASE WHEN cal.endofavailability IS NOT NULL THEN 'soldout'
                        ELSE ''
                   END
EOD;

    $dbMain->query($sql);
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: PATHをsoldoutに設定', $exportTarget));

    // PR-RATE（PR料率）を更新
    $this->updateYahooInformationPrRate($dbMain);
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: PR料率設定', $exportTarget));

    $sql = <<<EOD
      UPDATE tb_plusnaoproductdirectory as pd
      INNER JOIN tb_mainproducts as m ON pd.NEディレクトリID = m.NEディレクトリID
      INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN ${targetTable} as i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      SET i.path = buildYahooPath(rakutencategories_1)
        , pd.yahoo_category = buildYahooPath(rakutencategories_1)
      WHERE cal.endofavailability IS NULL
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND i.registration_flg <> 0
        AND IFNULL(m.YAHOOディレクトリID, '') <> ''
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('updateYahooInformation: buildYahooPath(rakutencategories_1)', $exportTarget));

    // バリエーション画像アップロードフラグを設定
    $this->updateVariationImageFlg();
  }

  /**
   * TbYahooOtoriyoseInformationテーブルのPR料率を設定する。
   */
  private function updateYahooInformationPrRate($dbMain) {
    $logger = $this->getLogger();

    try {
      $logger->debug("PR料率処理開始");

      // Web画面で設定した設定値を取得
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $settingsArray = $repo->findCsvYahooOtoriyoseSettingByArray();

      $today = new \DateTime();
      $today->setTime(0, 0, 0);

      /* (1) 商品登録後 X 日以内の商品 */
      /* (2) その他の商品 */
      /* (3) 季節外商品 */
      /* (4) 即納商品 */

      $newArrivalBaseDate = clone $today; // 新着とする基準日（この日を含む）
      $newArrivalBaseDate->modify(sprintf('- %d day', $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_NEW_DAYS]));
      $seasonColumn = sprintf('s%d', (new \DateTime())->format('n')); // シーズン設定 当月カラム

      $sql = <<<EOD
        UPDATE tb_yahoo_otoriyose_information yoi
        LEFT JOIN tb_mainproducts p ON yoi.daihyo_syohin_code = p.daihyo_syohin_code
        LEFT JOIN tb_mainproducts_cal cal ON yoi.daihyo_syohin_code = cal.daihyo_syohin_code
        LEFT JOIN tb_product_season s ON yoi.daihyo_syohin_code = s.daihyo_syohin_code
        SET yoi.`pr-rate` =
        CASE
          WHEN cal.deliverycode = :readyDeliveryCode THEN :readyPrPer
          WHEN s.`{$seasonColumn}` = 0 THEN :offSeasonPer
          WHEN p.`販売開始日` >= :newArrivalBaseDate THEN :newArrivalPer
          WHEN p.`販売開始日` < :newArrivalBaseDate THEN :otherPer
          ELSE 0.0
        END
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':readyDeliveryCode', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
      $stmt->bindValue(':readyPrPer', $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_READY_PER], \PDO::PARAM_STR);
      $stmt->bindValue(':offSeasonPer', $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_OFF_PER], \PDO::PARAM_STR);
      $stmt->bindValue(':newArrivalBaseDate', $newArrivalBaseDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':newArrivalPer', $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_NEW_PER], \PDO::PARAM_STR);
      $stmt->bindValue(':otherPer', $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_OTHER_PER], \PDO::PARAM_STR);
      $stmt->execute();

      /* (5) 販売数量制限 */
      $amountBaseDate = clone $today; // 新着とする基準日（この日を含む）
      $amountBaseDate->modify(sprintf('- %d day', $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_DAYS]));
      $logger->debug("販売数量設定：　基準日:" . $amountBaseDate->format('Y-m-d')
          . ", 基準数:" .  $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_NUM]
          . ", 設定率:" . $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_PER]);

      $sql = <<<EOD
        UPDATE tb_yahoo_otoriyose_information yoi
          INNER JOIN (
            SELECT
              daihyo_syohin_code
            FROM
              tb_sales_detail_analyze a
            WHERE
              `受注日` >= :amountBaseDate
              AND a.`キャンセル区分` = '0'
              AND a.`明細行キャンセル` = '0'
            GROUP BY
              daihyo_syohin_code
            HAVING
              SUM(`受注数`) >= :amountBaseSum
          ) amt
            ON yoi.daihyo_syohin_code = amt.daihyo_syohin_code
        SET
          yoi.`pr-rate` = :amountBasePer
EOD;

      $logger->debug("sql:" . $sql);

      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':amountBaseDate', $amountBaseDate->format('Y-m-d'), \PDO::PARAM_STR, \PDO::PARAM_STR);
      $stmt->bindValue(':amountBaseSum', $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_NUM], \PDO::PARAM_STR);
      $stmt->bindValue(':amountBasePer', $settingsArray[TbSetting::KEY_YAHOO_OTORIYOSE_PR_AMOUNT_PER], \PDO::PARAM_STR);
      $stmt->execute();


    } catch (Throwable $t) {
      $logger->error("YahooおとりよせCSV生成で致命的エラー: " . $t->getMessage() . " ：" . $t->getTraceAsString());
      throw $t;
    }

    $logger->debug("PR料率設定SQL実行終了");
  }

  /**
   * 商品バリエーション画像出力フラグ設定
   * 画像が登録されており、かつ在庫数が基準以上のもののフラグを立てる。
   * 対象となる代表商品は、バリエーション画像が存在し、かつ tb_yahoo_data_add.display = 1 となり、在庫数が基準以上
   *
   * 一度ONにしたものはOFFにしてはならない（Yahoo側の画像は自動で消せないので、画像を残したままOFFにすると縦横設定で問題が出る）。
   * ここが新たにONになると、次のProductImageUploadFtpCommandの実行時にバリエーション画像がアップロードされる。
   * なお、Yahoo側の画像を消すのはかなりの手間なので、基準を緩めすぎないこと
   */
  private function updateVariationImageFlg() {

      $updateStockBase = intval($this->commonUtil->getSettingValue(TbSetting::KEY_YAHOO_OTORITISE_VARI_IMG_STOCK_BASE)); // 基準在庫数 0 未満なら何もせず終了
      if (is_null($updateStockBase) || $updateStockBase === '' ||  $updateStockBase < 0 ) {
        return;
      }

    // バリエーション画像は在庫があるものだけ
    $sql = <<<EOD
      UPDATE tb_yahoo_otoriyose_information i
      JOIN (
        SELECT distinct v.daihyo_syohin_code
        FROM product_images_variation v
        INNER JOIN tb_mainproducts m ON m.daihyo_syohin_code = v.daihyo_syohin_code
        INNER JOIN tb_productchoiceitems pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code AND
            CASE
              WHEN m.カラー軸 = 'row' THEN pci.rowcode = v.variation_code AND v.code = 'row'
              WHEN m.カラー軸 = 'col' THEN pci.colcode = v.variation_code AND v.code = 'col'
            END
        INNER JOIN tb_yahoo_otoriyose_information AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN (
          SELECT daihyo_syohin_code FROM tb_productchoiceitems GROUP BY daihyo_syohin_code HAVING SUM(フリー在庫数) >= :baseStock
        ) stock ON stock.daihyo_syohin_code = m.daihyo_syohin_code
        WHERE
          i.variation_image_upload_flg = 0
          AND IFNULL(m.YAHOOディレクトリID, '') <> ''
          AND cal.deliverycode_pre IN ( :deliveryCodeReady , :deliveryCodeReadyPartially, :deliveryCodePurchaseOnOrder)
          AND i.registration_flg <> 0
          AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone )
          AND cal.endofavailability IS NULL
      ) target ON i.daihyo_syohin_code = target.daihyo_syohin_code
      SET i.variation_image_upload_flg = 1
      WHERE  i.variation_image_upload_flg = 0
EOD;
    $dbMain = $this->getDb('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':baseStock', $updateStockBase);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);

    $stmt->execute();
  }

  /**
   * エクスポートデータ作成
   * @param $exportTarget
   */
  private function prepareData($exportTarget)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'ヤフーCSV出力処理';
    $subTitle = 'prepareData___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    $targetTable = $commonUtil->getYahooTargetTableName($exportTarget);
    $kawaemonAddRate = intval($commonUtil->getSettingValue('YAHOO_KAWAEMON_B_TNK_ADD_RATE'));
    $yahooTemplate = $commonUtil->getSettingValue('YAHOO_TEMPLATE');

    //'====================
    //    '商品データ
    //'====================
    $dbMain->query("TRUNCATE tb_yahoo_data_add");

    /*
     * 出品条件
     * 1 出品フラグがonの商品
     * 2 権利侵害・アダルト審査が「ブラック」「グレー」「未審査」ではない商品
     * 3 Yahoo(otoriyose)へ既登録済みの全商品で1、2に該当する完売3年以内の全商品
     */

    // '販売中
    $sql = <<<EOD
      INSERT INTO tb_yahoo_data_add (
          path
        , code
        , `product-category`
        , display
        , `lead-time-instock`
      )
      SELECT
          COALESCE(i.path, '＿新規') AS path
        , m.daihyo_syohin_code
        , m.YAHOOディレクトリID
        , '1' AS display
        , '3' AS `lead-time-instock`   /* 3:山崎さん手動更新 ( or 4000:お取り寄せ) 固定 */
      FROM tb_mainproducts           AS m
      INNER JOIN ${targetTable}      AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE IFNULL(m.YAHOOディレクトリID, '') <> ''
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND i.registration_flg <> 0
        AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone )
        AND cal.endofavailability IS NULL
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: 商品データ', $exportTarget));

    //'販売終了品
    $sql = <<<EOD
      INSERT INTO tb_yahoo_data_add (
          path
        , code
        , `product-category`
        , display
        , `lead-time-instock`
      )
      SELECT
          COALESCE(i.path, '＿新規') AS path
        , m.daihyo_syohin_code
        , m.YAHOOディレクトリID
        , '1' as display
        , '3' AS `lead-time-instock`   /* 3:山崎さん手動更新 ( or 4000:お取り寄せ) 固定 */
      FROM tb_mainproducts           AS m
      INNER JOIN ${targetTable}      AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE IFNULL(m.YAHOOディレクトリID, '') <> ''
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND i.registration_flg <> 0
        AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone )
        AND cal.endofavailability is NOT NULL
        AND cal.endofavailability >= :exportLimitDate
EOD;
    $exportLimit = new \DateTime();
    $exportLimit->modify('-3 year'); // 販売終了から三年間

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->bindValue(':exportLimitDate', $exportLimit->format('Y-m-d 00:00:00'), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: 販売終了品', $exportTarget));

    $logger->info("SUBコードとOptionの設定");

    //'====================
    //    'Sub-Code & Options
    //'====================
    /*
    switch ($exportTarget) {
      case self::EXPORT_TARGET_PLUSNAO:
        $dbMain->query("CALL BuildSubcodeOptionsDataAdd()");
        break;
      case self::EXPORT_TARGET_KAWAEMON:
        $dbMain->query("CALL BuildSubcodeOptionsKawaDataAdd()");
        break;
    }
    */

    $dbMain->query("SET SESSION group_concat_max_len = 20480");

    $sql = <<<EOD
      INSERT INTO {$targetTable} (
           daihyo_syohin_code
        , `sub-code`
        , options
      )
      SELECT
          T1.daihyo_syohin_code
        , T1.sub_code
        , T2.options
      FROM (
        SELECT
            daihyo_syohin_code
          , GROUP_CONCAT(sub_code ORDER BY 並び順No SEPARATOR '&') AS sub_code
        FROM (
          SELECT
              pci.daihyo_syohin_code
            , 並び順No
            , CASE
                WHEN i.variation_image_upload_flg = 0 OR m.カラー軸 <> 'col' THEN
                  CONCAT(
                    m.row_title
                  , ':'
                  , rowname
                  , '#'
                  , m.col_title
                  , ':'
                  , pci.colname
                  , '='
                  , pci.daihyo_syohin_code
                  , pci.colcode
                  , pci.rowcode
                  )
                ELSE
                  CONCAT(
                    m.col_title
                  , ':'
                  , colname
                  , '#'
                  , m.row_title
                  , ':'
                  , pci.rowname
                  , '='
                  , pci.daihyo_syohin_code
                  , pci.colcode
                  , pci.rowcode
                  )
              END AS sub_code
          FROM tb_productchoiceitems pci
          INNER JOIN tb_yahoo_data_add a ON pci.daihyo_syohin_code = a.code
          INNER JOIN tb_yahoo_otoriyose_information i ON i.daihyo_syohin_code = a.code
          INNER JOIN (
            SELECT
                  daihyo_syohin_code
                , CASE
                    WHEN (COALESCE(m.`縦軸項目名`, '') = '' OR m.`縦軸項目名` = '-')  THEN
                      CASE WHEN (COALESCE(m.`横軸項目名`, '') = '' OR m.`横軸項目名` = '-')  THEN 'カラー'
                           WHEN m.`横軸項目名` <> 'カラー' THEN 'カラー'
                           ELSE 'サイズ'
                      END
                    ELSE m.`縦軸項目名`
                  END AS row_title
                , CASE
                    WHEN (COALESCE(m.`横軸項目名`, '') = '' OR m.`横軸項目名` = '-')  THEN
                      CASE WHEN (COALESCE(m.`縦軸項目名`, '') = '' OR m.`縦軸項目名` = '-')  THEN 'サイズ'
                           WHEN m.`縦軸項目名` <> 'サイズ' THEN 'サイズ'
                           ELSE 'カラー'
                      END
                    ELSE m.`横軸項目名`
                  END AS col_title
                , m.カラー軸
            FROM tb_mainproducts m
          ) AS m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        ) AS T
        GROUP BY daihyo_syohin_code
      ) T1
      INNER JOIN (
        SELECT
            m.daihyo_syohin_code
          , CASE
              WHEN i.variation_image_upload_flg = 0 OR m.カラー軸 <> 'col' THEN
                CONCAT(
                    m.row_title
                  , ' '
                  , pci.rowname_list
                  , '\\r\\n\\r\\n'
                  , m.col_title
                  , ' '
                  , pci.colname_list
                )
              ELSE
                CONCAT(
                    m.col_title
                  , ' '
                  , pci.colname_list
                  , '\\r\\n\\r\\n'
                  , m.row_title
                  , ' '
                  , pci.rowname_list
                )
              END
            AS options
        FROM tb_yahoo_data_add a
        INNER JOIN tb_yahoo_otoriyose_information i ON i.daihyo_syohin_code = a.code
        INNER JOIN (
          SELECT
                daihyo_syohin_code
              , CASE
                  WHEN (COALESCE(m.`縦軸項目名`, '') = '' OR m.`縦軸項目名` = '-')  THEN
                    CASE WHEN (COALESCE(m.`横軸項目名`, '') = '' OR m.`横軸項目名` = '-')  THEN 'カラー'
                         WHEN m.`横軸項目名` <> 'カラー' THEN 'カラー'
                         ELSE 'サイズ'
                    END
                  ELSE m.`縦軸項目名`
                END AS row_title
              , CASE
                  WHEN (COALESCE(m.`横軸項目名`, '') = '' OR m.`横軸項目名` = '-')  THEN
                    CASE WHEN (COALESCE(m.`縦軸項目名`, '') = '' OR m.`縦軸項目名` = '-')  THEN 'サイズ'
                         WHEN m.`縦軸項目名` <> 'サイズ' THEN 'サイズ'
                         ELSE 'カラー'
                    END
                  ELSE m.`横軸項目名`
                END AS col_title
              , m.カラー軸
          FROM tb_mainproducts m
        ) AS m ON a.code = m.daihyo_syohin_code
        INNER JOIN (
          SELECT
              daihyo_syohin_code
            , GROUP_CONCAT(DISTINCT rowname ORDER BY 並び順No SEPARATOR ' ') AS rowname_list
            , GROUP_CONCAT(DISTINCT colname ORDER BY 並び順No SEPARATOR ' ') AS colname_list
          FROM tb_productchoiceitems pci
          INNER JOIN tb_yahoo_data_add a ON pci.daihyo_syohin_code = a.code
          GROUP BY daihyo_syohin_code
        ) AS pci ON a.code = pci.daihyo_syohin_code
      ) T2 ON T1.daihyo_syohin_code = T2.daihyo_syohin_code
      ON DUPLICATE KEY UPDATE
          `sub-code` = T1.sub_code
        , options = T2.options
EOD;
    $dbMain->query($sql);
    $logger->info($this->getLapTimeAndMemory('prepareData: BuildSubcodeOptions(Kawa)DataAdd', $exportTarget));

    // '2015.03.09 optionsに注記を
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, 'appendOptions', '開始'));
    $this->appendOptions($exportTarget);
    $logger->info($this->getLapTimeAndMemory('prepareData: appendOptions', $exportTarget));
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, 'appendOptions', '終了'));

    // tb_yahoo_data_add に値をコピー
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add
      INNER JOIN ${targetTable} AS i ON tb_yahoo_data_add.code = i.daihyo_syohin_code
      SET tb_yahoo_data_add.`sub-code` = i.`sub-code`
        , tb_yahoo_data_add.options = i.options
EOD;
    $dbMain->query($sql);
    $logger->info($this->getLapTimeAndMemory('prepareData: tb_yahoo_data_add に値をコピー', $exportTarget));

    $logger->info("その他の情報を設定中");

    // '2014.01.23 商品情報（explanation）ではなく商品説明（caption）に設定するように変更
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add      AS A
      LEFT JOIN tb_mainproducts     AS m   ON A.code = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS cal ON A.code = cal.daihyo_syohin_code
      LEFT JOIN ${targetTable}      AS i   ON A.code = i.daihyo_syohin_code
      SET A.`original-price` = m.`実勢価格`
        , A.price = TRUNCATE(i.baika_tanka * :taxRate, - 1)
        , A.price_add_10per = TRUNCATE(
            i.baika_tanka * ((100 + :kawaemonAddRate) / 100) * :taxRate
          , - 1
        )
        , A.`sale-price`    = NULL
        , A.`meta-key`      = IFNULL(i.`meta-key`, '')
        , A.`meta-desc`     = IFNULL(m.`商品コメントPC`, '')
        , A.caption         = IFNULL(i.caption, '')
        , A.explanation     = IFNULL(i.explanation, '')
        , A.`sp-additional` = IFNULL(i.`sp-additional`, '')
        , A.`pr-rate`       = i.`pr-rate`
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':kawaemonAddRate', $kawaemonAddRate, \PDO::PARAM_STR);
    $stmt->bindValue(':taxRate', $commonUtil->getTaxRate(), \PDO::PARAM_STR);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: その他の情報を設定', $exportTarget));

    // Call abstract___
    $this->updateAbstract($exportTarget);
    $logger->info($this->getLapTimeAndMemory('prepareData: updateAbstract', $exportTarget));

    // spec更新
    // spec1: サイズ、 spec2: カラー、 spec3~: 現状利用予定なし それぞれマッチするものがなければカラでよい
    $yahooDataAddRepo = $this->getDoctrine()->getRepository('MiscBundle:TbYahooDataAdd');
    $yahooDataAddRepo->applySizeSpec();
    $logger->info($this->getLapTimeAndMemory('prepareData: a.spec1 ~ 3', $exportTarget));
    $yahooDataAddRepo->applyColorSpec();
    $logger->info($this->getLapTimeAndMemory('prepareData: a.spec4', $exportTarget));


    $logger->info("スマートフォン用情報に追加情報を設定中");

    // スマートフォン商品情報にモールデザイン管理の情報を追加
    // tb_mall_designからデザインHTML取得
    // 【おとりよせ.com】スマートフォン商品情報上部(code="otoriyose_sp_top")
    $designHTML = '';
    $sql = <<<EOD
      SELECT design_html FROM tb_mall_design d WHERE d.code = 'otoriyose_sp_top'
EOD;
    $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
    if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
      $designHTML = $tmp['design_html'];
    }

    $designHTML = preg_replace('/^\\s+/m', '', $designHTML); // m修飾子 大事
    $designHTML = preg_replace('/\r\n|\r|\n/', '',  $designHTML);

    // すべての商品情報に追加
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add SET `sp-additional` = CONCAT(`sp-additional`, :designHTML)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':designHTML', $designHTML);
    $stmt->execute();

    // 2017/09/22 PlusNaoとおとりよせ.com相互リンク
    // 【おとりよせ.com】Plusnaoへの商品リンク（SP大）(code="o_to_p_sp_l")
    $designHTML = '';
    $sql = <<<EOD
      SELECT design_html FROM tb_mall_design d WHERE d.code = 'o_to_p_sp_l'
EOD;
    $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
    if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
      $designHTML = $tmp['design_html'];
    }
    // 【おとりよせ.com】Plusnaoへの商品リンク（SP小）(code="o_to_p_sp_s")
    $sql = <<<EOD
      SELECT design_html FROM tb_mall_design d WHERE d.code = 'o_to_p_sp_s'
EOD;
    $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
    if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
      $designHTML = $designHTML .$tmp['design_html'];
    }
    // デザインHTMLに含まれる「##code##」はcode（商品コード：小文字）に置き換えて更新する
    // フリー在庫がある即納・一部即納商品のみ対象（「即納可能」という、Yahoo plusnaoへの小さいバナーが固定表示される）
    $sql = <<<EOD
      UPDATE
        tb_yahoo_data_add
      SET
        `sp-additional` = CONCAT(`sp-additional`, REPLACE(:designHTML, '##code##', LOWER(code)))
      WHERE
        code IN (
          SELECT daihyo_syohin_code FROM tb_productchoiceitems WHERE フリー在庫数 > 0 GROUP BY daihyo_syohin_code
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':designHTML', $designHTML);
    $stmt->execute();

    // ??? JOINの意図は不明
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add AS A
      LEFT JOIN tb_mainproducts     AS m   ON A.code = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS cal ON A.code = cal.daihyo_syohin_code
      LEFT JOIN ${targetTable}      AS i   ON A.code = i.daihyo_syohin_code
      SET A.taxable     = '1'
        , A.template    = :yahooTemplate
        , A.`astk-code` = '0'
        , A.condition   = '0'
        , A.taojapan    = '1'
        , A.sort        = (9999999 - cal.priority)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':yahooTemplate', $yahooTemplate);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: taxable,template,astk-code,condition,taojapan,sort', $exportTarget));

    $dbMain->query("UPDATE tb_yahoo_data_add SET `sort` = 9999999 WHERE `sort` IS NULL");

    // 'ゲリラSALE
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add AS A
      INNER JOIN tb_mainproducts     AS m   ON A.code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendormasterdata AS v   ON m.sire_code = v.sire_code
      SET A.path = CONCAT(A.path, '\\r\\n【ゲリラSALE】\\r\\n')
      WHERE
          (
               v.`cost_rate` >= v.`guerrilla_margin`
            OR m.手動ゲリラSALE <> 0
          )
        AND cal.endofavailability IS NULL
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND A.path <> '＿新規'
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: ゲリラSALE', $exportTarget));

    //'アウトレット
    if ($commonUtil->getSettingValue('RAKUTEN_TITLE_OUTLET') <> 0) {
      $sql = <<<EOD
        UPDATE tb_yahoo_data_add AS A
        INNER JOIN tb_mainproducts_cal AS cal ON A.code = cal.daihyo_syohin_code
        SET A.path = CONCAT(A.path, '\\r\\n【アウトレット】\\r\\n')
        WHERE cal.outlet <> 0
          AND A.path <> '＿新規'
EOD;

      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $logger->info($this->getLapTimeAndMemory('prepareData: アウトレット', $exportTarget));
    }

    // 'PATHで改行が２つ連続している場合は１つにする
    $dbMain->query("UPDATE tb_yahoo_data_add AS A SET A.Path = REPLACE(A.path,'\\r\\n\\r\\n', '\\r\\n')");
    $logger->info($this->getLapTimeAndMemory('prepareData: PATHで改行が２つ連続している場合は１つにする', $exportTarget));

    // 'nameとexplanationがどういうわけか一部のレコードが更新されない。商品コード指定で単品だと更新する
    // '理由が分からないが仕方がないのでサーバー側でSQL実行する
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add A
      LEFT JOIN tb_mainproducts     AS m   ON A.code = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal AS cal ON A.code = cal.daihyo_syohin_code
      LEFT JOIN ${targetTable}      AS i   ON A.code = i.daihyo_syohin_code
      SET A.name = IFNULL(i.yahoo_title, '')
EOD;
    $dbMain->query($sql);
    $logger->info($this->getLapTimeAndMemory('prepareData: A.name = i.yahoo_title', $exportTarget));

    // '全角記号等の除去
    $this->fixName("[]♪、。，．・：；？！゛゜´｀¨＾￣＿ヽヾゝゞ〃仝々〆〇―‐／＼∥｜…‥‘’（）〔〕［］｛｝〈〉《》「」『』【】＋－±×÷＝≠＜＞≦≧∞∴♂♀°′″℃￥＄￠￡％＃＆＊＠§☆★○●◇◆□■△▲▽▼※〒→←↑↓〓“”◎", ' ');
    $logger->info($this->getLapTimeAndMemory('prepareData: fixName', $exportTarget));

    //Call fixNameSpaces___
    // 全角空白の変換・余分な空白の除去
    $this->fixNameSpaces();
    $logger->info($this->getLapTimeAndMemory('prepareData: fixNameSpaces', $exportTarget));

    //'項目の切り捨て（backtitle付加前に！）
    $dbMain->query("UPDATE tb_yahoo_data_add SET `meta-desc` = LEFT(`meta-desc`,80)");
    $logger->info($this->getLapTimeAndMemory('prepareData: meta-desc 80文字', $exportTarget));

    //'出荷予定のないものは75文字
    $dbMain->query("UPDATE tb_yahoo_data_add SET `name` = LEFT(`name`,75)");
    $logger->info($this->getLapTimeAndMemory('prepareData: name 75文字', $exportTarget));

    //'重量設定・、送料設定
    // 宅配便送料別も送料無料となるため、ship-weight=10000、delivery=1とする
    $sql = <<<EOD
      UPDATE tb_yahoo_data_add A
      INNER JOIN tb_mainproducts AS m ON A.code = m.daihyo_syohin_code
      SET `ship-weight` = 0
      , delivery = 1
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $logger->info($this->getLapTimeAndMemory('prepareData: 重量設定・、送料設定', $exportTarget));

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * オプションに注記追加
   * @param $exportTarget
   */
  private function appendOptions($exportTarget)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $targetTable = $commonUtil->getYahooTargetTableName($exportTarget);

    //メール便
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は メール便発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeMailbin
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeMailbin', TbShippingdivision::SHIPPING_GROUP_CODE_MAILBIN, \PDO::PARAM_INT);
    $stmt->execute();

    //定形郵便
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は 定形郵便にて発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeTeikei
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikei', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEI, \PDO::PARAM_INT);
    $stmt->execute();

    //定形外郵便
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は ゆうパケット又は、定形外にて発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeTeikeigai
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTeikeigai', TbShippingdivision::SHIPPING_GROUP_CODE_TEIKEIGAI, \PDO::PARAM_INT);
    $stmt->execute();

    //ゆうパケット
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は ゆうパケット又は、定形外にて発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeYuuPacket
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeYuuPacket', TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET, \PDO::PARAM_INT);
    $stmt->execute();

    //ねこポス
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m  ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★この商品は ねこポス発送予定です')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeNekoposu
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeNekoposu', TbShippingdivision::SHIPPING_GROUP_CODE_NEKOPOSU, \PDO::PARAM_INT);
    $stmt->execute();

    //宅配便(おとりよせ.comでは宅配便送料別は送料込みとなるため、同じ設定にする)
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      SET i.`options` = CONCAT(
            i.`options`
          , '\\r\\n\\r\\n'
          , '★この商品は 宅配便発送予定です'
        )
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND sd.shipping_group_code = :shippingGroupCodeTakuhaibin
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':shippingGroupCodeTakuhaibin', TbShippingdivision::SHIPPING_GROUP_CODE_TAKUHAIBIN, \PDO::PARAM_INT);
    $stmt->execute();

    //ゲリラセール
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_vendormasterdata AS v ON m.sire_code = v.sire_code
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★セール特典 ゲリラSALE中')
      WHERE
        cal.endofavailability IS NULL
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND (
             v.`cost_rate` >= v.`guerrilla_margin`
          OR m.手動ゲリラSALE <> 0
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();

    //おとりよせ.comでは宅配便送料別は送料込みとなるため、削除
//     $sql = <<<EOD
//       UPDATE ${targetTable} AS i
//       INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
//       SET i.`options` = CONCAT(
//           i.`options`
//           , '\\r\\n\\r\\n'
//           , :postageFreeComment
//         )
//       WHERE cal.endofavailability IS NULL
//         AND cal.deliverycode_pre <> :deliveryCodeTemporary
// EOD;
//     $stmt = $dbMain->prepare($sql);
//     $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
//     $stmt->bindValue(':postageFreeComment', sprintf('合計%d円(税込)以上で さらに全品送料無料', $commonUtil->getSettingValue('POSTAGE_FREE_TAXPRICE')), \PDO::PARAM_STR);
//     $stmt->execute();

    //店内全品送料無料
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★店内全品送料無料 一部地域を除く')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();

    //代引き・時間指定
    $sql = <<<EOD
      UPDATE ${targetTable} AS i
      INNER JOIN tb_yahoo_data_add a ON i.daihyo_syohin_code = a.code
      INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.`options` = CONCAT(i.`options`, '\\r\\n\\r\\n', '★代引き・時間指定不可 了承済み')
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /// ひと言コメント
  private function updateAbstract($exportTarget)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    // リセット
    $dbMain->query("UPDATE tb_yahoo_data_add SET tb_yahoo_data_add.abstract = ''");

    // 2017/09/22 PlusNaoとおとりよせ.com相互リンク
    // tb_mall_designからデザインHTML取得、Plusnaoの商品ページへリンク
    // 【おとりよせ.com】Plusnaoへの商品リンク（PC）(code="o_to_p_pc")

    $logger->info("ひと言コメントを設定中");

    // デザインHTML取得
    $designHTML = '';
    $sql = <<<EOD
      SELECT design_html FROM tb_mall_design d WHERE d.code = 'o_to_p_pc'
EOD;
    $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
    if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
      $designHTML = $tmp['design_html'];
    }

    $designHTML = preg_replace('/^\\s+/m', '', $designHTML); // m修飾子 大事
    $designHTML = preg_replace('/\r\n|\r|\n/', '',  $designHTML);

    // デザインHTMLに含まれる「##code##」はcode（商品コード：小文字）に置き換えて更新する
    // フリー在庫がある商品のみ対象
    $sql = <<<EOD
      UPDATE
        tb_yahoo_data_add
      SET
        abstract = REPLACE(:designHTML, '##code##', LOWER(code))
      WHERE
        code IN (
          SELECT daihyo_syohin_code FROM tb_productchoiceitems WHERE フリー在庫数 > 0
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':designHTML', $designHTML);
    $stmt->execute();

    // デザインHTML取得 お買い物ガイドへのリンクバナー
    $designHTML = '';
    $sql = <<<EOD
      SELECT design_html FROM tb_mall_design d WHERE d.code = 'otoriyose_pc_hitokoto'
EOD;
    $tmp = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);;
    if ($tmp && isset($tmp['design_html']) && strlen($tmp['design_html'])) {
      $designHTML = $tmp['design_html'];
    }

    $designHTML = preg_replace('/^\\s+/m', '', $designHTML); // m修飾子 大事
    $designHTML = preg_replace('/\r\n|\r|\n/', '',  $designHTML);

    // 全商品に追加
    $sql = <<<EOD
      UPDATE
        tb_yahoo_data_add
      SET
        abstract = CONCAT(
                       COALESCE(abstract, '')
                     , '\n'
                     , REPLACE(:designHTML, '##code##', LOWER(code))
                   )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':designHTML', $designHTML);
    $stmt->execute();

  }

  /**
   * 利用できない文字を削除
   * TODO なんとか効率化できないか。（文字数の回数だけUPDATEが走ってしまうのは豪快すぎる）
   * @param $fromChars
   * @param $to
   */
  private function fixName($fromChars, $to)
  {
    $dbMain = $this->getDb('main');
    $fromCharsArray = preg_split('//u', $fromChars, -1, PREG_SPLIT_NO_EMPTY);

    $stmt = $dbMain->prepare("UPDATE tb_yahoo_data_add SET NAME = REPLACE(name, :fromChar, :toChar)");
    $stmt->bindValue(':toChar', $to);
    foreach ($fromCharsArray as $from) {
      $stmt->bindValue('fromChar', $from);
      $stmt->execute();
    }
  }

  /// 空白の変換・除去
  private function fixNameSpaces()
  {
    // Accessでは専用テーブルまで利用し、再帰的に連続空白を除去している。
    // ここの実装は当面は下記で済ませる。 TODO 連続空白除去処理を他での処理に置き換える。

    $dbMain = $this->getDb('main');

    // '全角スペースの置換
    $dbMain->query("UPDATE tb_yahoo_data_add SET name = REPLACE(name, '　', ' ')");

    // 連続スペースを一つに
    $dbMain->query("UPDATE tb_yahoo_data_add SET name = REPLACE(name, '  ', ' ')");
  }


  /**
   * 在庫表データ （prepareData の後に実行）
   */
  private function prepareStock()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $logger->info("追加用在庫表データの準備中です");


    // 予約フリー在庫 再計算
    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.next_engine');
    $mallProcess->recalculateFreeReservedStock();

    $dbMain->query("TRUNCATE tb_yahoo_quantity_add");

    $sql = <<<EOD
      INSERT INTO tb_yahoo_quantity_add (
          code
        , `sub-code`
        , quantity
      )
      SELECT
          m.daihyo_syohin_code
        , pci.ne_syohin_syohin_code
        , CASE
            WHEN (pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0) THEN :quantityMax
            WHEN pci.フリー在庫数 + pci.予約フリー在庫数 > :quantityMax THEN :quantityMax
            ELSE pci.フリー在庫数 + pci.予約フリー在庫数
          END AS quantity
      FROM tb_mainproducts as m
      INNER JOIN tb_productchoiceitems  AS pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal    AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_yahoo_data_add AS a ON m.daihyo_syohin_code = a.code /* 出力条件 */
      ORDER BY m.daihyo_syohin_code, pci.並び順No
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':quantityMax', self::QUANTITY_MAX, \PDO::PARAM_INT);
    $stmt->execute();

    // セット商品の在庫数を更新する
    // セット商品は、セット商品としてpciに登録している在庫数ではなく、構成品の在庫数を元に計算する
    $sql = <<<EOD
      UPDATE tb_yahoo_quantity_add a
      INNER JOIN (
        SELECT
            pci.ne_syohin_syohin_code AS set_sku
          , MIN(TRUNCATE((CASE
              WHEN (pci_detail.受発注可能フラグ <> 0 AND cal_detail.受発注可能フラグ退避F = 0) THEN :quantityMax
              WHEN pci_detail.フリー在庫数 + pci_detail.予約フリー在庫数 > :quantityMax THEN :quantityMax
              ELSE pci_detail.フリー在庫数 + pci_detail.予約フリー在庫数
            END / d.num), 0)) AS creatable_num
        FROM tb_productchoiceitems pci
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
        INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal_detail ON pci_detail.daihyo_syohin_code = cal_detail.daihyo_syohin_code  
        WHERE m.set_flg <> 0
        GROUP BY set_sku
      ) T ON a.`sub-code` = T.set_sku 
      SET a.quantity = T.creatable_num
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':quantityMax', self::QUANTITY_MAX, \PDO::PARAM_INT);
    $stmt->execute();
  }
}
