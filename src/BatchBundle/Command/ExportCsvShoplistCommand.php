<?php
/**
 * SHOPLIST CSV出力処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvShoplistCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results;

  // アップロードファイルの分割設定件数
  const UPLOAD_CSV_MAX_NUM_DETAIL = 10000; // 1万件で分割
  const UPLOAD_CSV_MAX_NUM_SELECT = 60000; // 6万件で分割
  const UPLOAD_CSV_MAX_NUM_STOCK  = 60000; // 6万件で分割

  CONST CSV_FILENAME_DETAIL     = 'product_detail.csv';
  CONST CSV_FILENAME_DETAIL_NEW = 'product_detail_new.csv';
  CONST CSV_FILENAME_SELECT = 'product_select.csv';

  const EXPORT_PATH = 'Shoplist/Export';

  protected $exportPath;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-shoplist')
      ->setDescription('CSVエクスポート SHOPLIST')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, '出力ディレクトリ', null);
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info('SHOPLIST CSV出力処理を開始しました。');

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

      $now = new \DateTimeImmutable();
      $this->stopwatch->start('main');
      $fileUtil = $this->getFileUtil();

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
        , 'delete' => null
        , 'update' => null
      ];

      // 出力パス
      $this->exportPath = $input->getOption('export-dir');
      if (!$this->exportPath) {
        $this->exportPath = $this->getFileUtil()->getWebCsvDir() . '/' . self::EXPORT_PATH . '/' . $now->format('YmdHis');
      }

      // 出力ディレクトリ 作成
      $fs = new FileSystem();
      if (!$fs->exists($this->exportPath)) {
        $fs->mkdir($this->exportPath, 0755);
      }

      // 共通処理
      // ※「受発注可能フラグ退避」「復元」は、同様の挙動になるようにフラグを確認して処理し、
      //   フラグのコピー・戻し処理は移植しない。
      //   「受発注可能フラグ」を判定している箇所すべてについて、
      //   (「受発注可能フラグ」= True AND 「受発注可能フラグ退避F」 = False) とする。
      //   => pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0

      $logExecTitle = sprintf('SHOPLIST CSV出力処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $rakutenCsvOutputDir = $fileUtil->getWebCsvDir() . '/RakutenNokiKanri';
      $commonUtil->exportCsvCommonProcess($logger, $rakutenCsvOutputDir);
      $commonUtil->calculateMallPrice($logger, DbCommonUtil::MALL_CODE_SHOPLIST);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

      // CSV出力 データ作成処理 実装

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '開始'));
      
      // セット商品は出品フラグOFF （#189370 SHOPLISTではセット商品を販売しない（強制出品OFF） のための暫定処理）
      $this->updateSetProductRegistrationFlg();

      /*
      ▽CSV出力順

      1. SKUの削除（在庫確認テーブルにあってDBに存在しない）
      商品自体の削除（在庫確認テーブルにあってDBに存在しない）

      product_select (d) => product_select_delete.csv
      product_detail (d) => product_detail_delete.csv

      ※ registration_flg OFFでの削除は、誤削除時のリカバリーが大変面倒なモールであるため、一旦見合わせる。
      　（数百件の誤削除などをしてしまった時にリカバリーしてくれるか相当に怪しい。）
        → registration_flg OFF および アダルトブラックは倉庫指定 1 （非表示）

      2. 新規商品・商品更新

      product_detail (n) => product_detail_new.csv
      product_detail (u) => product_detail.csv
      （対象）
      ・ registration_flg ON
      ・AND 在庫確認テーブルに存在するか、代表商品コードでくくってフリー在庫 > 0 （=> 即納or一部即納）
      かつ
      （
      ・ 更新フラグ ON
      ・OR ※価格更新ON※ => 全商品
      ）

      （n or u）
      ・ 在庫確認テーブルに1本も無い => "n"
      ・ 在庫確認テーブルに1本でもある => "u"

      product_select (n) => product_select.csv
      （対象）
      ・product_detail_update に含まれ、在庫確認テーブルに存在しない => n
      OR
      ・在庫確認テーブルと表示名が違う

      3. 在庫更新

      stock.csv (u)

      （対象）
      ・在庫確認テーブルに存在する
      かつ
      （
      ・ registration_flg OFF => 0 固定で更新（すでに0ならスキップ）
      ・OR registration_flg ON かつ
      　　SKUの在庫と違っている
      　　※この時、product_select_update の結果で在庫確認テーブルは更新されていることが前提
      ）
       */

      $detailNewPath = $this->exportPath . '/' . self::CSV_FILENAME_DETAIL_NEW;
      $detailPath = $this->exportPath . '/' . self::CSV_FILENAME_DETAIL;
      $selectPath = $this->exportPath . '/' . self::CSV_FILENAME_SELECT;

      // --------------------------------------
      // 削除CSVデータ作成
      // --------------------------------------
      $this->exportDeleteCsv($detailPath, $selectPath);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('delete csv'));

      // --------------------------------------
      // 更新CSVデータ作成
      // --------------------------------------
      $this->exportUpdateCsv($detailNewPath, $detailPath, $selectPath);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('update csv'));

      // '====================
      // Call NE更新カラムリセット
      // ここだけ、帳尻（処理が終わったらリセットされている、という出口）を合わせるため残す。
      // '====================
      $commonUtil->resetNextEngineUpdateColumn($logger);
      /* ------------ DEBUG LOG ------------ */
      $logger->debug($this->getLapTimeAndMemory('resetNextEngineUpdateColumn'));

      $finder = new Finder(); // 結果ファイル確認
      $fileNum = $finder->in($this->exportPath)->files()->count();
      if (!$fileNum) {
        $this->results['message'] = 'CSVファイルが作成されませんでした。処理を完了します。';
        // 空のディレクトリを削除
        $fs = new FileSystem();
        $fs->remove($this->exportPath);
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'CSV出力', '終了')->setInformation($this->results));
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('SHOPLIST CSV出力処理を完了しました。');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('SHOPLIST CSV Export エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('SHOPLIST CSV出力処理', 'SHOPLIST CSV出力処理', 'エラー終了')->setInformation($e->getMessage())
        , true, 'SHOPLIST CSV出力処理' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }
  
  /**
   * セット商品の出品フラグを全てOFFとする
   */
  private function updateSetProductRegistrationFlg() {
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      UPDATE tb_shoplist_information i
      JOIN tb_mainproducts m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      SET i.registration_flg = 0
      WHERE m.set_flg <> 0
EOD;
    $dbMain->query($sql);
  }

  /**
   * 削除CSV出力
   * @param string $detailPath
   * @param string $selectPath
   * @throws \Doctrine\DBAL\DBALException
   */
  private function exportDeleteCsv($detailPath, $selectPath)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $logTitle = 'SHOPLIST CSV出力処理';
    $subTitle = '削除CSV出力';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));
    $logger->info('SHOPLIST 削除CSV出力');

    // 削除商品用一時テーブル
    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_shoplist_delete_code");
    $sql = <<<EOD
      CREATE TEMPORARY TABLE tmp_shoplist_delete_code (
        daihyo_syohin_code VARCHAR(30) NOT NULL PRIMARY KEY
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbMain->query($sql);

    // 削除商品
    $sql = <<<EOD
      INSERT INTO tmp_shoplist_delete_code (
        daihyo_syohin_code
      )
      SELECT
        DISTINCT s.`商品管理番号（商品URL）`
      FROM tb_shoplist_product_stock s
      LEFT JOIN tb_mainproducts m ON s.`商品管理番号（商品URL）` = m.daihyo_syohin_code
      WHERE m.daihyo_syohin_code IS NULL
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      SELECT
        daihyo_syohin_code
      FROM tmp_shoplist_delete_code
      ORDER BY daihyo_syohin_code
EOD;
    $stmt = $dbMain->query($sql);

    $deleteDetailCount = $stmt->rowCount();
    $logger->info('SHOPLIST 削除CSV出力（詳細） : ' . $deleteDetailCount);

    if ($deleteDetailCount) {
      $headers = $this->getCsvHeadersDetail();

      $fs = new FileSystem();
      $fileExists = $fs->exists($detailPath);
      $fp = fopen($detailPath, 'ab');

      if (!$fileExists) {
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        $data = [
            'コントロールカラム'      => 'd'
          , '商品管理番号（商品URL）' => strtolower($row['daihyo_syohin_code'])
          , '商品番号'               => $row['daihyo_syohin_code']
        ];
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      fclose($fp);
    }

    // 削除SKU（すでにDBに存在しないSKUで、詳細で削除するもの以外）
    $sql = <<<EOD
      SELECT
          s.`商品管理番号（商品URL）`
        , s.`項目選択肢別在庫用横軸選択肢`
        , s.`項目選択肢別在庫用横軸選択肢子番号`
        , s.`項目選択肢別在庫用縦軸選択肢`
        , s.`項目選択肢別在庫用縦軸選択肢子番号`
      FROM tb_shoplist_product_stock s
      LEFT JOIN tb_productchoiceitems pci ON s.`商品管理番号（商品URL）` = pci.daihyo_syohin_code 
        AND s.`項目選択肢別在庫用横軸選択肢子番号` = pci.colcode
        AND s.`項目選択肢別在庫用縦軸選択肢子番号` = pci.rowcode
      LEFT JOIN tmp_shoplist_delete_code t ON s.`商品管理番号（商品URL）` = t.daihyo_syohin_code
      WHERE pci.ne_syohin_syohin_code IS NULL
        AND t.daihyo_syohin_code IS NULL
EOD;
    $stmt = $dbMain->query($sql);

    $deleteSelectCount = $stmt->rowCount();
    $logger->info('SHOPLIST 削除CSV出力（SKU） : ' . $deleteSelectCount);

    if ($deleteSelectCount) {
      $headers = $this->getCsvHeadersSelect();

      $fs = new FileSystem();
      $fileExists = $fs->exists($selectPath);
      $fp = fopen($selectPath, 'ab');

      if (!$fileExists) {
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $data = [
            '項目選択肢用コントロールカラム'    => 'd'
          , '商品管理番号（商品URL）'          => $row['商品管理番号（商品URL）']
          , '選択肢タイプ'                    => 'i'
          , '項目選択肢別在庫用横軸選択肢'      => $row['項目選択肢別在庫用横軸選択肢']
          , '項目選択肢別在庫用横軸選択肢子番号' => $row['項目選択肢別在庫用横軸選択肢子番号']
          , '項目選択肢別在庫用縦軸選択肢'      => $row['項目選択肢別在庫用縦軸選択肢']
          , '項目選択肢別在庫用縦軸選択肢子番号' => $row['項目選択肢別在庫用縦軸選択肢子番号']
        ];

        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      fclose($fp);
    }

    // 在庫確認用テーブル 更新（削除）
    $sql = <<<EOD
      DELETE s
      FROM tb_shoplist_product_stock s
      INNER JOIN tmp_shoplist_delete_code t ON s.`商品管理番号（商品URL）`  = t.daihyo_syohin_code
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      DELETE s
      FROM tb_shoplist_product_stock s
      LEFT JOIN tb_productchoiceitems pci ON s.`商品管理番号（商品URL）` = pci.daihyo_syohin_code 
        AND s.`項目選択肢別在庫用横軸選択肢子番号` = pci.colcode
        AND s.`項目選択肢別在庫用縦軸選択肢子番号` = pci.rowcode
      WHERE pci.ne_syohin_syohin_code IS NULL
EOD;
    $dbMain->query($sql);

    $this->results['delete'] = [
        'detail' => $deleteDetailCount
      , 'select' => $deleteSelectCount
    ];

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * 更新CSV出力
   * 'n' と 'u' でフォーマットが別。
   * → 画像カラムをつけるつけない、販売価格をつけるつけないなどで、統一は諦めて別ファイルに変更
   *
   * @param string $detailNewPath
   * @param string $detailPath
   * @param string $selectPath
   * @throws \Doctrine\DBAL\DBALException
   */
  private function exportUpdateCsv($detailNewPath, $detailPath, $selectPath)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $logTitle = 'SHOPLIST CSV出力処理';
    $subTitle = '更新CSV出力';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    // 登録・更新商品用一時テーブル
    $dbMain->query("DROP TABLE IF EXISTS tmp_shoplist_update_code");
    $sql = <<<EOD
      CREATE TABLE tmp_shoplist_update_code (
          daihyo_syohin_code VARCHAR(30) NOT NULL PRIMARY KEY
        , control_column VARCHAR(1) NOT NULL DEFAULT 'n'
        , hidden TINYINT NOT NULL DEFAULT 0
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbMain->query($sql);

    $dbMain->query("DROP TABLE IF EXISTS tmp_shoplist_update_sku");
    $sql = <<<EOD
      CREATE TEMPORARY TABLE tmp_shoplist_update_sku (
          ne_syohin_syohin_code VARCHAR(50) NOT NULL PRIMARY KEY
        , control_column VARCHAR(1) NOT NULL DEFAULT 'n'
        , daihyo_syohin_code VARCHAR(30) NOT NULL
        , colname VARCHAR(50) NOT NULL DEFAULT ''
        , colcode VARCHAR(50) NOT NULL DEFAULT ''
        , rowname VARCHAR(50) NOT NULL DEFAULT ''
        , rowcode VARCHAR(50) NOT NULL DEFAULT ''
        , `フリー在庫数` INTEGER NOT NULL DEFAULT 0
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbMain->query($sql);

    // 出力対象 全挿入
    $sql = <<<EOD
      INSERT INTO tmp_shoplist_update_code (
          daihyo_syohin_code
        , control_column
        , hidden
      )
      SELECT
          m.daihyo_syohin_code
        , CASE
            WHEN s.code IS NULL THEN 'n'
            ELSE 'u'
          END AS control_column
        , 0 AS hidden
      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_shoplist_information i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN (
        SELECT
            s.`商品管理番号（商品URL）` AS code
          , COUNT(*) AS num
        FROM tb_shoplist_product_stock s
        GROUP BY s.`商品管理番号（商品URL）`
      ) s ON m.daihyo_syohin_code = s.code
      WHERE i.registration_flg <> 0
        AND cal.deliverycode <> :deliveryCodeTemporary
        AND cal.adult_check_status IN ( :adultCheckStatusWhite , :adultCheckStatusGray )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':adultCheckStatusWhite', TbMainproductsCal::ADULT_CHECK_STATUS_WHITE, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->execute();

    // 非表示対象 差分挿入
    // ※出力対象ではなく、SHOPLISTに存在する商品で、倉庫設定になっていないもの
    $sql = <<<EOD
      INSERT INTO tmp_shoplist_update_code (
          daihyo_syohin_code
        , control_column
        , hidden
      )
      SELECT
          DISTINCT
          s.`商品管理番号（商品URL）`
        , 'u' AS control_column
        , 1 AS hidden
      FROM tb_shoplist_product_stock s
      LEFT JOIN  tmp_shoplist_update_code t ON s.`商品管理番号（商品URL）` = t.daihyo_syohin_code
      WHERE t.daihyo_syohin_code IS NULL
        AND s.hidden = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // 差分がなく更新しない行を除去
    $sql = <<<EOD
      DELETE t
      FROM tmp_shoplist_update_code t
      INNER JOIN tb_mainproducts m ON t.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON t.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_shoplist_information i ON t.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN (
        SELECT
            s.`商品管理番号（商品URL）` AS code
          , COUNT(*) AS num
        FROM tb_shoplist_product_stock s
        GROUP BY s.`商品管理番号（商品URL）`
      ) s ON t.daihyo_syohin_code = s.code
      WHERE t.hidden = 0 /* 「倉庫設定: 1」のレコードは除外せず必ず更新（すでに差分挿入されている） */
        AND (
              /* 在庫ありのみ更新 */
              cal.deliverycode NOT IN ( :deliveryCodeReady, :deliveryCodeReadyPartially ) /* 即納 or 一部即納 */
              /* 新規 or 更新あり でなければ更新しない */
           OR (s.code IS NOT NULL AND i.update_flg = 0)
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->execute();

    // CSV出力

    // detail出力
    $sql = <<<EOD
      SELECT
          t.control_column
        , t.hidden  AS 倉庫指定
        , m.daihyo_syohin_code
        , d.`楽天ディレクトリID`
        , SUBSTR(i.title, 1, 127) AS title /* 文字数制限 */
        , i.baika_tanka
        , m.`横軸項目名`
        , m.`縦軸項目名`

        , m.`商品コメントPC`
        , m.サイズについて
        , m.カラーについて
        , m.素材について
        , m.ブランドについて
        , m.使用上の注意
        , m.補足説明PC

        , m.picnameP1
        , m.picnameP2
        , m.picnameP3
        , m.picnameP4
        , m.picnameP5
        , m.picnameP6
        , m.picnameP7
        , m.picnameP8
        , m.picnameP9
        , m.picfolderP1
        , m.picfolderP2
        , m.picfolderP3
        , m.picfolderP4
        , m.picfolderP5
        , m.picfolderP6
        , m.picfolderP7
        , m.picfolderP8
        , m.picfolderP9

        , CASE
            WHEN d.rakutencategories_1 LIKE '%キッズ%' THEN 'plusnaokids'
            ELSE 'plusnao'
          END AS `ブランドコード`

      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_shoplist_information i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_plusnaoproductdirectory d ON m.`NEディレクトリID` = d.`NEディレクトリID`
      INNER JOIN tmp_shoplist_update_code t ON m.daihyo_syohin_code = t.daihyo_syohin_code
      WHERE COALESCE(i.baika_tanka, 0) > 0
EOD;
    $stmt = $dbMain->query($sql);

    $detailCount = $stmt->rowCount();
    $logger->info('SHOPLIST 更新CSV出力（詳細） : ' . $detailCount);

    if ($detailCount) {

      // 新規登録CSV (detail_new)
      $headersNew = $this->getCsvHeadersDetailForNew();

      $fs = new FileSystem();
      $fileExists = $fs->exists($detailNewPath);
      $fpNew = fopen($detailNewPath, 'ab');

      if (!$fileExists) {
        fputs($fpNew, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headersNew), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      // データ更新CSV
      // 価格更新フラグによって、「販売価格」項目の有無が変更される
      $headers = $this->getCsvHeadersDetail();

      $fs = new FileSystem();
      $fileExists = $fs->exists($detailPath);
      $fp = fopen($detailPath, 'ab');

      if (!$fileExists) {
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        $data = [
            'コントロールカラム'       => $row['control_column']
          , '商品管理番号（商品URL）'  => strtolower($row['daihyo_syohin_code'])
          , '商品番号'                => $row['daihyo_syohin_code']
          , '全商品ディレクトリID'     => $row['楽天ディレクトリID']
          , '商品名'                  => $row['title']
          , '販売価格'                => $row['baika_tanka']
          , '消費税'                  => 0
          , '倉庫指定'                => $row['倉庫指定']
          , 'モバイル用商品説明文'     => ''
          // , '商品画像URL'             => ''
          , '在庫タイプ'              => 2
          , '項目選択肢別在庫用横軸項目名' => $row['横軸項目名']
          , '項目選択肢別在庫用縦軸項目名' => $row['縦軸項目名']

          , 'ブランドコード' => $row['ブランドコード']
        ];

        // 商品説明文 作成
        $description = '';
        $columns = [
            '商品コメントPC'
          , 'サイズについて'
          , 'カラーについて'
          , '素材について'
          , 'ブランドについて'
          , '使用上の注意'
          , '補足説明PC'
        ];
        foreach($columns as $col) {
          $row[$col] = str_replace("\n", '<br>', str_replace("\r", '', trim($row[$col])));
        }
        if (strlen($row['商品コメントPC'])) {
          $description .= $row['商品コメントPC'] . '<br>';
        }
        if (strlen($row['サイズについて'])) {
          $description .= '<br><br>【サイズについて】<br>';
          $description .= $row['サイズについて'] . '<br>';
        }
        if (strlen($row['カラーについて'])) {
          $description .= '<br><br>【カラーについて】<br>';
          $description .= $row['カラーについて'] . '<br>';
        }
        if (strlen($row['素材について'])) {
          $description .= '<br><br>【素材について】<br>';
          $description .= $row['素材について'] . '<br>';
        }
        if (strlen($row['ブランドについて'])) {
          $description .= '<br><br>【ブランド】<br>';
          $description .= $row['ブランドについて'] . '<br>';
        }
        if (strlen($row['使用上の注意'])) {
          $description .= '<br><br>【使用上の注意】<br>';
          $description .= $row['使用上の注意'] . '<br>';
        }
        if (strlen($row['補足説明PC'])) {
          $description .= '<br><br>【補足説明】<br>';
          $description .= $row['補足説明PC'] . '<br>';
        }

        $data['モバイル用商品説明文'] = $description;

//        // 商品画像URL 作成
//        $images = [];
//        for ($i = 1; $i <= 9; $i++) {
//          $columnDir  = sprintf('picfolderP%d', $i);
//          $columnFile = sprintf('picnameP%d', $i);
//
//          // 画像設定 有無確認
//          if (strlen($row[$columnDir]) && strlen($row[$columnFile])) {
//            $images[] = sprintf(
//                          'http://img.shop-list.com/res/up/shoplist/shp/plusnao/%s/%d.jpg'
//                          , $data['商品管理番号（商品URL）']
//                          , $i
//                        );
//          } else{
//            $images[] = ''; // （暫定実装）もし画像がない番号があっても間を詰めない
//          }
//        }
//        $data['商品画像URL'] = implode(' ', $images);

        // 出力

        // 新規
        if ($row['control_column'] == 'n') {
          fputs($fpNew, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headersNew), 'SJIS-WIN', 'UTF-8') . "\r\n");
        } else {
          fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
        }
      }

      fclose($fpNew);
      fclose($fp);
    }

    // select出力
    // 在庫確認テーブルの更新のため、一時テーブルに全て投入してからSELECTする
    /*
    （対象）
    ・product_detail の n or u に含まれ、在庫確認テーブルに存在しない => n
    OR
    ・在庫確認テーブルに代表商品コードが存在し、在庫確認テーブルに存在しない => n
    OR
    ・在庫確認テーブルと表示名が違う => u
    */
    $sql = <<<EOD
      INSERT INTO tmp_shoplist_update_sku
      SELECT
          pci.ne_syohin_syohin_code
        , CASE
            WHEN (
                    s.`商品管理番号（商品URL）` IS NOT NULL
                AND (pci.colname <> s.`項目選択肢別在庫用横軸選択肢` OR pci.rowname <> s.`項目選択肢別在庫用縦軸選択肢` )
              ) THEN 'u'
            ELSE 'n'
          END AS control_column
        , pci.daihyo_syohin_code
        , pci.colname
        , pci.colcode
        , pci.rowname
        , pci.rowcode
        , pci.`フリー在庫数`
      FROM tb_productchoiceitems pci
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      LEFT JOIN tb_shoplist_product_stock s ON pci.ne_syohin_syohin_code = s.sku
      LEFT JOIN (
        SELECT
          DISTINCT s.`商品管理番号（商品URL）` AS code
        FROM tb_shoplist_product_stock s
      ) SCODE ON m.daihyo_syohin_code = SCODE.code
      LEFT JOIN tmp_shoplist_update_code t ON pci.daihyo_syohin_code = t.daihyo_syohin_code
      WHERE
            ( t.daihyo_syohin_code IS NOT NULL AND s.`商品管理番号（商品URL）` IS NULL )
         OR ( SCODE.code IS NOT NULL AND s.`商品管理番号（商品URL）` IS NULL )
         OR (
               s.`商品管理番号（商品URL）` IS NOT NULL
           AND (pci.colname <> s.`項目選択肢別在庫用横軸選択肢` OR pci.rowname <> s.`項目選択肢別在庫用縦軸選択肢` )
         )
      ORDER BY pci.ne_syohin_syohin_code
EOD;
    $dbMain->query($sql);

    // CSV出力(SKU)
    $sql = <<<EOD
      SELECT * FROM tmp_shoplist_update_sku
      ORDER BY ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->query($sql);

    $selectCount = $stmt->rowCount();
    $logger->info('SHOPLIST 更新CSV出力（SKU） : ' . $selectCount);

    if ($selectCount) {
      $headers = $this->getCsvHeadersSelect();

      $fs = new FileSystem();
      $fileExists = $fs->exists($selectPath);
      $fp = fopen($selectPath, 'ab');

      if (!$fileExists) {
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $data = [
            '項目選択肢用コントロールカラム'    => $row['control_column']
          , '商品管理番号（商品URL）'          => strtolower($row['daihyo_syohin_code'])
          , '選択肢タイプ'                    => 'i'
          , '項目選択肢別在庫用横軸選択肢'      => $row['colname']
          , '項目選択肢別在庫用横軸選択肢子番号' => $row['colcode']
          , '項目選択肢別在庫用縦軸選択肢'      => $row['rowname']
          , '項目選択肢別在庫用縦軸選択肢子番号' => $row['rowcode']
          , '項目選択肢別在庫用在庫数'          => $row['フリー在庫数']
        ];

        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      fclose($fp);
    }

    // 在庫確認テーブル更新
    // あくまでSKU情報の更新のみで、在庫情報は更新してはいけない
    // このproduct_select.csv では在庫数の更新はされず、この後いつか行われるstock.csvで更新しなければならず（SHOPLIST仕様）
    // そこまで在庫数の差分は維持する必要がある。
    // 新規SKUは在庫数を更新してもどちらでもよいはずだが、簡単のためやはり0で更新する
    $sql = <<<EOD
      INSERT INTO tb_shoplist_product_stock (
          `商品管理番号（商品URL）`
        , `選択肢タイプ`
        , `項目選択肢別在庫用横軸選択肢`
        , `項目選択肢別在庫用横軸選択肢子番号`
        , `項目選択肢別在庫用縦軸選択肢`
        , `項目選択肢別在庫用縦軸選択肢子番号`
      )
      SELECT
          t.daihyo_syohin_code
        , 'i'
        , t.colname
        , t.colcode
        , t.rowname
        , t.rowcode
      FROM tmp_shoplist_update_sku t
      ON DUPLICATE KEY UPDATE
          `項目選択肢別在庫用横軸選択肢` = VALUES(`項目選択肢別在庫用横軸選択肢`)
        , `項目選択肢別在庫用縦軸選択肢` = VALUES(`項目選択肢別在庫用縦軸選択肢`)
EOD;
    $dbMain->query($sql);

    // 倉庫設定 更新
    $sql = <<<EOD
      UPDATE
      tb_shoplist_product_stock s
      INNER JOIN tmp_shoplist_update_code t ON s.`商品管理番号（商品URL）` = t.daihyo_syohin_code
      SET s.hidden = t.hidden
      WHERE s.hidden <> t.hidden
EOD;
    $dbMain->query($sql);

    // 新規商品について、最終画像アップロード日時をNULLに更新
    // （同一商品の削除後の復活はイレギュラーなので本来不要ではあるが、わかりやすさのために実施）
    $sql = <<<EOD
      UPDATE tb_shoplist_information i
      INNER JOIN tmp_shoplist_update_code t ON i.daihyo_syohin_code = t.daihyo_syohin_code
      SET i.last_image_upload_datetime = NULL
      WHERE t.control_column = 'n'
EOD;
    $dbMain->query($sql);

    // 更新フラグ OFF
    $dbMain->query("UPDATE tb_shoplist_information SET update_flg = 0");

    $this->results['update'] = [
        'detail' => $detailCount
      , 'select' => $selectCount
    ];

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * CSVヘッダ取得（詳細：NEW）
   */
  private function getCsvHeadersDetailForNew()
  {
    $headers = array_unique(array_merge($this->getCsvHeadersDetail(), [
        'コントロールカラム'
      , '商品管理番号（商品URL）'
      , '商品番号'
      , '全商品ディレクトリID'
      , 'PC用キャッチコピー'        // for new
      , 'モバイル用キャッチコピー'  // for new
      , '商品名'
      , '販売価格' // for new
      , '表示価格' // for new
      , '消費税'
      , '倉庫指定'
      , 'PC用商品説明文'        // for new
      , 'モバイル用商品説明文'
      , '商品画像URL'           // for new
      , '商品画像名（ALT）'     // for new
      , '販売期間指定'          // for new
      , '在庫タイプ'
      , '在庫数'               // for new
      , '在庫数表示'           // for new
      , '項目選択肢別在庫用横軸項目名'
      , '項目選択肢別在庫用縦軸項目名'
      , '項目選択肢別在庫用残り表示閾値' // for new
      , '予約商品発売日'               // for new
      , 'ブランドコード' // for new
    ]));

    return $headers;
  }

  /**
   * CSVヘッダ取得（詳細）
   */
  private function getCsvHeadersDetail()
  {
    $headers = [
        'コントロールカラム'
      , '商品管理番号（商品URL）'
      , '商品番号'
      , '全商品ディレクトリID'
      // , 'PC用キャッチコピー'
      // , 'モバイル用キャッチコピー'
      , '商品名'
      , '消費税'
      , '倉庫指定'
      , 'モバイル用商品説明文'
      // , '商品画像URL'
      , '在庫タイプ'
      , '項目選択肢別在庫用横軸項目名'
      , '項目選択肢別在庫用縦軸項目名'
    ];

    return $headers;
  }

  /**
   * CSVヘッダ取得（項目選択肢）
   */
  private function getCsvHeadersSelect()
  {
    $headers = [
        '項目選択肢用コントロールカラム'
      , '商品管理番号（商品URL）'
      , '選択肢タイプ'
      , '項目選択肢別在庫用横軸選択肢'
      , '項目選択肢別在庫用横軸選択肢子番号'
      , '項目選択肢別在庫用縦軸選択肢'
      , '項目選択肢別在庫用縦軸選択肢子番号'
      , '項目選択肢別在庫用取り寄せ可能表示'
      , '項目選択肢別在庫用在庫数'
    ];

    return $headers;
  }

}
