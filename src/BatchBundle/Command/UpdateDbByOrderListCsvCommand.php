<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;


use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use stdClass;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;


class UpdateDbByOrderListCsvCommand extends ContainerAwareCommand
{
  /** @var BatchLogger */
  private $logger;

  /** @var \Doctrine\DBAL\Connection $dbMain */
  private $dbMain;

  /** @var \MiscBundle\Util\DbCommonUtil */
  private $commonUtil;

  /** @var \BatchBundle\MallProcess\NextEngineMallProcess */
  private $neMallProcess;

  /** @var SymfonyUsers */
  private $account;

  // 取込情報
  /** @var  stdClass */
  private $importInfo;

  // ファイル削除フラグ
  private $doDeleteFile = true;

  protected function configure()
  {
    $this
      ->setName('batch:update-db-by-order-list-csv')
      ->setDescription('受注データCSVを元にしたDB更新処理を行う（受注明細取込）')
      ->addArgument('data-dir', InputArgument::REQUIRED, '取得CSVファイル格納ディレクトリ(ディレクトリ内の.csvをすべて処理)')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('delete-file', null, InputOption::VALUE_OPTIONAL, 'ダウンロードファイルを削除するか。', 1)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $container->get('misc.util.batch_logger');
    $logger->initLogTimer();
    $this->logger = $logger;

    $logger->info('受注明細取込を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // 対象ファイル格納ディレクトリ
    $dataDir = $input->getArgument('data-dir');

    $this->doDeleteFile = boolval($input->getOption('delete-file'));

    // DB記録＆通知処理
    $logExecTitle = '受注明細取込';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    // 受注明細取込
    $fs = new FileSystem();
    $finder = new Finder();

    if (!$fs->exists($dataDir)) {
      throw new RuntimeException('no data dir!! [' . $dataDir . ']');
    }
    $files = $finder->in($dataDir)->files();
    if (! $files->count()) {
      throw new RuntimeException('no data files!! [' . $dataDir . ']');
    }

    try {
      /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
      $doctrine = $container->get('doctrine');
      $this->commonUtil = new DbCommonUtil($doctrine);

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $this->dbMain = $container->get('doctrine')->getConnection('main');
      $dbMain = $this->dbMain;

      $this->neMallProcess = $container->get('batch.mall_process.next_engine');


      // 受注データ取り込みテーブルへの取り込み準備（一時テーブル作成）
      $this->neMallProcess->renewTableTbOrderDataTmp();

      $files = $files->sort(function($a, $b) {
        /** @var \SplFileInfo $a */
        /** @var \SplFileInfo $b */
        return strcmp($b->getFilename(), $a->getFilename());
      });

      // CSVファイルを順番にすべてDBへ取り込む
      $this->importInfo = $this->neMallProcess->createImportInfo();

      /** @var \SplFileInfo $file */
      foreach($files as $file) {
        $filePath = $file->getPath() . '/' . $file->getFilename();

        $logger->info('ファイル 開始 [' . $filePath . ']');

        $this->importSalesDetail($filePath);

        $logger->info('ファイル 終了 [' . $filePath . ']');
      }

      // 不要データ削除、分析用テーブル(analyze)更新処理
      $this->neMallProcess->updateSalesDetailAnalyze('all', $this->importInfo);

      // productchoiceitems 引当数・フリー在庫数更新
      $this->neMallProcess->updateProductchoiceitemsAssignedNum();

      // 最終受注日を更新
      $this->neMallProcess->updateLastOrderDateWithMinMax($this->importInfo);

      //'最終更新日時をセット
      $this->commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_ORDER_DETAIL);

      // CSVファイル及びディレクトリ 削除
      if ($this->doDeleteFile) {
        $fs->remove($files);
        $fs->remove($dataDir);
      }

      // 月別売上集計 作成処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, '月別売上集計 作成処理', '開始'));
      $logger->info('月別売上集計 作成処理 開始');

      $this->makeProfitVoucherYm();

      $logger->addDbLog($logger->makeDbLog($logExecTitle, '月別売上集計 作成処理', '終了'));
      $logger->info('月別売上集計 作成処理 終了');

      $logger->info('DB更新 by 受注一覧CSV 完了');


      $info = [
          'count'   => $this->importInfo->importCount
        , 'min'     => $this->importInfo->importMinCode
        , 'max'     => $this->importInfo->importMaxCode
        , 'minDate' => ($this->importInfo->importMinDate ? $this->importInfo->importMinDate->format('Y-m-d') : '')
        , 'maxDate' => ($this->importInfo->importMaxDate ? $this->importInfo->importMaxDate->format('Y-m-d') : '')
      ];

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($info));

      // ---------------------------------------------------------
      // 受注データ取込処理 （Accessで別処理になっていたものを統合）
      $logExecTitle = '受注データ取込';

      $logger->info('受注データ取込 開始');
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $this->importOrderDataFromTmpTable($dbMain);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->info('受注データ取込 終了');

      $logger->logTimerFlush();

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * 受注明細 取り込み処理（1ファイル）
   */
  private function importSalesDetail($path)
  {
    $logger = $this->logger;
    $dbMain = $this->dbMain;

    $time = microtime(true);
    // CSV書式確認
    if (!$this->validateCsv($path)) {
      throw new \RuntimeException('invalid CSV data . [' . $path . ']');
    }

    // 文字コード変換
    // -- 一時ファイル作成
    $tmpFile = tmpfile();
    $tmpInfo = stream_get_meta_data($tmpFile);
    $tmpPath = $tmpInfo['uri'];

    $fp = fopen($path, 'rb');
    while($line = fgets($fp)) {
      fputs($tmpFile, mb_convert_encoding(trim($line), 'UTF-8', 'SJIS-WIN') . "\n");
    }
    fclose($fp);

    // $dbMain->query("SET character_set_database=sjis;");
    $dbMain->query("TRUNCATE tb_sales_detail_tmp");

    // 一時テーブルへの取り込み
    $sql  = " LOAD DATA LOCAL INFILE :filePath ";
    $sql .= " INTO TABLE tb_sales_detail_tmp ";
    $sql .= " FIELDS ENCLOSED BY '\"' ESCAPED BY '' TERMINATED BY ',' LINES TERMINATED BY '\\n' IGNORE 1 LINES ";

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':filePath', $tmpPath);
    $stmt->execute();
    fclose($tmpFile);

    $logger->info('(受注明細) 一時テーブルへのCSV取り込み');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 一時テーブルのデータを元に統計情報を取得　※以前は個人情報のマスクをしていたので行っていた　もう不要かもしれないがいったん統計情報は残す
    $this->neMallProcess->convertPersonalInfo($logger);

    // 受注データ取り込みテーブルへの取り込み準備（一時テーブルへインサートのみしておく）
    $sql = <<<EOD
      INSERT INTO tb_order_data_tmp (
        `伝票番号`
      , `受注番号`
      , `受注日`
      , `出荷日`
      , `取込日`
      , `入金日`
      , `配送日`
      , `出荷予定日`
      , `納品書印刷指示日`
      , `受注キャンセル日`
      , `キャンセル`
      , `入金金額`
      , `配送伝票番号`
      , `店舗名`
      , `発送方法`
      , `支払方法`
      , `合計金額`
      , `税金`
      , `送料`
      , `手数料`
      , `その他`
      , `ポイント`
      , `受注状態`
      , `受注担当者`
      , `確認チェック`
      , `作業者欄`
      , `配送備考`
      , `ピック指示内容`
      , `納品書備考`
      , `備考`
      , `配送時間帯`
      , `購入者名`
      , `購入者カナ`
      , `購入者電話番号`
      , `購入者郵便番号`
      , `購入者住所1`
      , `購入者住所2`
      , `購入者メールアドレス`
      , `顧客コード`
      , `顧客区分`
      , `発送先名`
      , `発送先カナ`
      , `発送先電話番号`
      , `発送先郵便番号`
      , `発送先住所1`
      , `発送先住所2`
      , `入金区分`
      , `名義人`
      , `承認区分`
      , `承認金額`
      , `納品書発行日`
      , `商品コード`
      , `商品名`
      , `商品オプション`
      , `受注数`
      , `引当数`
      , `引当日`
      , `商品単価`
      , `小計`
      , `掛率`

      /* === データなし === */
      /*
      受注チェック
      受注チェック担当者
      出荷担当者
      ラベル発行日
      クレジット区分
      有効期限
      承認番号
      承認日
      オーソリ名
      購入者ＦＡＸ
      発送先ＦＡＸ
      */
      )
      SELECT
        `伝票番号`
      , `受注番号`
      , `受注日`
      , `出荷確定日`
      , `取込日`
      , `入金日`
      , `配達希望日`
      , `出荷予定日`
      , `納品書印刷指示日`
      , `キャンセル日`
      , CASE
          WHEN `キャンセル区分` = 0 AND `明細行キャンセル` = 0 THEN 0 /* 両方見なきゃだめ */
          ELSE 1
        END AS `キャンセル区分`
      , `入金額`
      , `発送伝票番号`
      , `店舗名`
      , `発送方法`
      , `支払方法`
      , `総合計`
      , `税金`
      , `発送代`
      , `手数料`
      , `他費用`
      , `ポイント数`
      , `受注状態`
      , `受注担当者`
      , `確認チェック`
      , `作業用欄`
      , `発送伝票備考欄`
      , `ピッキング指示`
      , `納品書特記事項`
      , `備考`
      , `配送時間帯`
      , `購入者名`
      , `購入者カナ`
      , `購入者電話番号`
      , `購入者郵便番号`
      , `購入者住所1`
      , `購入者住所2`
      , `購入者メールアドレス`
      , `顧客cd`
      , `顧客区分`
      , `送り先名`
      , `送り先カナ`
      , `送り先電話番号`
      , `送り先郵便番号`
      , `送り先住所1`
      , `送り先住所2`
      , `入金状況`
      , `名義人`
      , `承認状況`
      , `承認額`
      , `納品書発行日`
      , `商品コード（伝票）`
      , `商品名（伝票）`
      , `商品オプション`
      , `受注数`
      , `引当数`
      , `引当日`
      , `売単価`
      , `小計`
      , `掛率`

      /* === 不使用 === */
      /* , `店舗コード` */
      /* , `配送方法コード` */
      /* , `支払方法コード` */
      /* , `商品計` */
      /* , `受注分類タグ` */
      /* , `購入者（住所1+住所2）` */
      /* , `送り先（住所1+住所2）` */
      /* , `ギフト` */
      /* , `重要チェック` */
      /* , `重要チェック者`*/
      /* , `明細行` */
      /* , `明細行キャンセル` ※複合で利用 */
      /* , `元単価` */
      FROM tb_sales_detail_tmp
EOD;

    $dbMain->query($sql);
    $logger->info('(受注データ) 一時テーブルへのCSV取り込み');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // 波ダッシュ補正
    // -> 対象なし

    // '====================
    // 'sales
    //  一時テーブルから tb_sales_detail への取込処理
    // '====================
    $this->neMallProcess->updateSalesDetailWithSalesDetailTmp($this->importInfo);
  }

  private function validateCsv($filePath)
  {
    // ファイルがない
    if (!file_exists($filePath)) {
      return false;
    }

    // ヘッダ行チェック
    $fp = fopen($filePath, "r");
    $fileHeader = trim(mb_convert_encoding(fgets($fp), 'UTF-8', 'SJIS-WIN'));
    $validHeaderLine = implode(',', [
      '"伝票番号"'
      , '"受注番号"'
      , '"受注日"'
      , '"出荷確定日"'
      , '"取込日"'
      , '"入金日"'
      , '"配達希望日"'
      , '"出荷予定日"'
      , '"納品書印刷指示日"'
      , '"キャンセル日"'
      , '"キャンセル区分"'
      , '"入金額"'
      , '"発送伝票番号"'
      , '"店舗名"'
      , '"店舗コード"'
      , '"発送方法"'
      , '"配送方法コード"'
      , '"支払方法"'
      , '"支払方法コード"'
      , '"総合計"'
      , '"商品計"'
      , '"税金"'
      , '"発送代"'
      , '"手数料"'
      , '"他費用"'
      , '"ポイント数"'
      , '"受注状態"'
      , '"受注担当者"'
      , '"受注分類タグ"'
      , '"確認チェック"'
      , '"作業用欄"'
      , '"発送伝票備考欄"'
      , '"ピッキング指示"'
      , '"納品書特記事項"'
      , '"備考"'
      , '"配送時間帯"'
      , '"購入者名"'
      , '"購入者カナ"'
      , '"購入者電話番号"'
      , '"購入者郵便番号"'
      , '"購入者住所1"'
      , '"購入者住所2"'
      , '"購入者（住所1+住所2）"'
      , '"購入者メールアドレス"'
      , '"顧客cd"'
      , '"顧客区分"'
      , '"送り先名"'
      , '"送り先カナ"'
      , '"送り先電話番号"'
      , '"送り先郵便番号"'
      , '"送り先住所1"'
      , '"送り先住所2"'
      , '"送り先（住所1+住所2）"'
      , '"ギフト"'
      , '"入金状況"'
      , '"名義人"'
      , '"承認状況"'
      , '"承認額"'
      , '"納品書発行日"'
      , '"重要チェック"'
      , '"重要チェック者"'
      , '"明細行"'
      , '"明細行キャンセル"'
      , '"商品コード（伝票）"'
      , '"商品名（伝票）"'
      , '"商品オプション"'
      , '"受注数"'
      , '"引当数"'
      , '"引当日"'
      , '"売単価"'
      , '"小計"'
      , '"元単価"'
      , '"掛率"'
    ]);

    if ($fileHeader !== $validHeaderLine) {
      return false;
    }

    return true;
  }

  /**
   * 月別 売上明細集計 作成処理 (makeProfitVoucher_ym)
   * ※tb_sales_ana_match との参照用。tb_sales_ana_match は、受注CSVダウンロード時に更新される。
   */
  private function makeProfitVoucherYm()
  {
    /*
     *
        Call Sql_Truncate("tb_sales_detail_voucher_ym_a")

        Call Sql_Connection("open", Nothing)

        '====================
        'tb_sales_detail_voucher（伝票レベル）の作成
        '====================

        mysql = "INSERT INTO tb_sales_detail_voucher_ym_a ( 伝票番号, 受注年月日, 受注年, 受注月, 総合計, 商品計, 税金, 発送代, 手数料, 他費用, ポイント数 )" & _
                " SELECT tb_sales_detail_analyze.伝票番号, tb_sales_detail_analyze.受注日 AS 受注日の先頭, tb_sales_detail_analyze.受注年 AS 受注年の先頭, tb_sales_detail_analyze.受注月 AS 受注月の先頭, tb_sales_detail_analyze.総合計 AS 総合計の先頭, tb_sales_detail_analyze.商品計 AS 商品計の先頭, tb_sales_detail_analyze.税金 AS 税金の先頭, tb_sales_detail_analyze.発送代 AS 発送代の先頭, tb_sales_detail_analyze.手数料 AS 手数料の先頭, tb_sales_detail_analyze.他費用 AS 他費用の先頭, tb_sales_detail_analyze.ポイント数 AS ポイント数の先頭" & _
                " FROM tb_sales_detail_analyze" & _
                " WHERE (" & _
                "(tb_sales_detail_analyze.キャンセル区分='0') AND (tb_sales_detail_analyze.明細行キャンセル='0')" & _
                ")" & _
                " GROUP BY tb_sales_detail_analyze.伝票番号, tb_sales_detail_analyze.受注年, tb_sales_detail_analyze.受注月, tb_sales_detail_analyze.総合計, tb_sales_detail_analyze.商品計, tb_sales_detail_analyze.税金, tb_sales_detail_analyze.発送代, tb_sales_detail_analyze.手数料, tb_sales_detail_analyze.他費用, tb_sales_detail_analyze.ポイント数;"

        CN.Execute mysql

        '====================
        'tb_sales_detail_voucher（伝票レベル）の更新
        '====================

        mysql = "update tb_sales_detail_voucher_ym_a set `受注年月`=concat(`受注年`,lpad(`受注月`,2,'0'))"
        CN.Execute mysql

        mysql = "update tb_sales_detail_voucher_ym_a set ポイント数を含む総合計 = 総合計+ポイント数;"
        CN.Execute mysql

        Call Sql_Connection("close", Nothing)
     *
     */
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getContainer()->get('doctrine')->getConnection('main');
    $dbMain->query('TRUNCATE tb_sales_detail_voucher_ym_a');

    // tb_sales_detail_voucher（伝票レベル）の作成
    // ※ 取得している値はすべて「伝票レベル」の値（明細毎に違わない）
    //    MySQL の GROUP BY のローカル挙動を悪用？して取得している（元実装ママ）
    // → 2016/07/15 差分取込により重複が出るようになった可能性があるため、正規のGROUP BY へ変更
    $sql = <<<EOD
      INSERT
      INTO tb_sales_detail_voucher_ym_a(
             伝票番号
           , 受注年月日
           , 受注年月
           , 受注年
           , 受注月
           , 総合計
           , 商品計
           , 税金
           , 発送代
           , 手数料
           , 他費用
           , ポイント数
           , ポイント数を含む総合計
      )
      SELECT
          伝票番号
        , MIN(受注日) AS 受注日の先頭
        , CONCAT(`受注年`, LPAD(`受注月`, 2, '0')) AS 受注年月
        , MIN(受注年) AS 受注年の先頭
        , MIN(受注月) AS 受注月の先頭
        , MAX(総合計) AS 総合計の先頭
        , MAX(商品計) AS 商品計の先頭
        , MAX(税金) AS 税金の先頭
        , MAX(発送代) AS 発送代の先頭
        , MAX(手数料) AS 手数料の先頭
        , MAX(他費用) AS 他費用の先頭
        , MAX(ポイント数) AS ポイント数の先頭
        , MAX(総合計 + ポイント数) AS ポイント数を含む総合計
      FROM tb_sales_detail_analyze
      WHERE tb_sales_detail_analyze.キャンセル区分 = '0'
        AND tb_sales_detail_analyze.明細行キャンセル = '0'
      GROUP BY
        伝票番号
      ORDER BY 伝票番号
EOD;

    $dbMain->query($sql);

    return;
  }

  /// 受注データ取込処理
  /// 受注データ取込一時テーブル
  /**
   * @param \Doctrine\DBAL\Connection $db
   * @throws \Doctrine\DBAL\DBALException
   */
  private function importOrderDataFromTmpTable($db)
  {
    $logger = $this->logger;
    $time = microtime(true);

    // 本来これでいくらか絞れるはずだが、全更新が必要な処理もある模様なので（現行のまま）一旦何もしない。
    // $importMinCode = $this->importMinCode;
    // $importMaxCode = ($this->importMaxCode ? $this->importMaxCode : 2147483647);

    // 商品名 or 商品オプションから出荷予定日の作成
    $sql = <<<EOD
      UPDATE tb_order_data_tmp
      SET 出荷予定月日 = CASE
                          WHEN 商品名 NOT LIKE '%本日注文%頃出荷予定%'
                            THEN ''
                          WHEN 商品名 LIKE '%本日注文%頃出荷予定%'
                            THEN  /* m月d日 -> m-d 文字列変換 */
                                  REPLACE (
                                    REPLACE (
                                      SUBSTRING(
                                          商品名
                                        , INSTR(商品名, '本日注文') + CHAR_LENGTH('本日注文')
                                        , LOCATE('頃出荷予定', 商品名, INSTR(商品名, '本日注文')) - (INSTR(商品名, '本日注文') + CHAR_LENGTH('本日注文'))
                                      )
                                      , '月'
                                      , '-'
                                    )
                                    , '日'
                                    , ''
                                  )
                          WHEN 商品オプション LIKE '%本日注文%頃出荷予定%'
                            THEN  /* m月d日 -> m-d 文字列変換 */
                                  REPLACE (
                                    REPLACE (
                                      SUBSTRING(
                                          商品オプション
                                        , INSTR(商品オプション, '本日注文') + CHAR_LENGTH('本日注文')
                                        , INSTR(商品オプション, '頃出荷予定') - (INSTR(商品オプション, '本日注文') + CHAR_LENGTH('本日注文'))
                                      )
                                      , '月'
                                      , '-'
                                    )
                                    , '日'
                                    , ''
                                  )
                          ELSE ''
                        END
EOD;
    $db->query($sql);
    $logger->info('受注データ加工 出荷予定月日');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    $sql = <<<EOD
      UPDATE tb_order_data_tmp
      SET 出荷予定月 = MONTH(CONCAT('0000-', 出荷予定月日))
      WHERE 出荷予定月日 <> ''
EOD;
    $db->query($sql);
    $logger->info('受注データ加工 出荷予定月');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


    //'同一レコード(伝票番号)の削除
    $sql = <<<EOD
      DELETE tb_order_data.*
      FROM tb_order_data INNER JOIN tb_order_data_tmp
        ON tb_order_data.伝票番号 = tb_order_data_tmp.伝票番号
EOD;
    $db->query($sql);

    // '一時テーブルからの取り込み処理

    // 区分値変換SQL 作成
    $kubunParams = [];

    // キャンセル (0 or 1)
    // → SQLに直接記載

    // 確認チェック
    $kubunConfirmCheckSql = "CASE ";
    foreach($this->commonUtil->getKubunList('確認チェック') as $value => $name) {
      $holderValue = sprintf(':kubunConfirmCheckValue%s', $value);
      $holderName = sprintf(':kubunConfirmCheckName%s', $value);
      $kubunParams[$holderValue] = $value;
      $kubunParams[$holderName] = $name;
      $kubunConfirmCheckSql .= sprintf(" WHEN 確認チェック = %s THEN %s ", $holderValue, $holderName);
    }
    $kubunConfirmCheckSql .= " ELSE '' END AS 確認チェック ";

    // 入金区分
    $kubunPaymentSql = "CASE ";
    foreach($this->commonUtil->getKubunList('入金区分') as $value => $name) {
      $holderValue = sprintf(':kubunPaymentValue%s', $value);
      $holderName = sprintf(':kubunPaymentName%s', $value);
      $kubunParams[$holderValue] = $value;
      $kubunParams[$holderName] = $name;
      $kubunPaymentSql .= sprintf(" WHEN 入金区分 = %s THEN %s ", $holderValue, $holderName);
    }
    $kubunPaymentSql .= " ELSE '' END AS 入金区分 ";

    // 承認区分
    $kubunAuthSql = "CASE ";
    foreach($this->commonUtil->getKubunList('承認区分') as $value => $name) {
      $holderValue = sprintf(':kubunAuthValue%s', $value);
      $holderName = sprintf(':kubunAuthName%s', $value);
      $kubunParams[$holderValue] = $value;
      $kubunParams[$holderName] = $name;
      $kubunAuthSql .= sprintf(" WHEN 承認区分 = %s THEN %s ", $holderValue, $holderName);
    }
    $kubunAuthSql .= " ELSE '' END AS 承認区分 ";

    // 顧客区分
    $kubunCustomerSql = "CASE ";
    foreach($this->commonUtil->getKubunList('顧客区分') as $value => $name) {
      $holderValue = sprintf(':kubunCustomerValue%s', $value);
      $holderName = sprintf(':kubunCustomerName%s', $value);
      $kubunParams[$holderValue] = $value;
      $kubunParams[$holderName] = $name;
      $kubunCustomerSql .= sprintf(" WHEN 顧客区分 = %s THEN %s ", $holderValue, $holderName);
    }
    $kubunCustomerSql .= " ELSE '' END AS 顧客区分 ";


    $sql = <<<EOD
      INSERT
      INTO tb_order_data (
          `店舗名`
        , `伝票番号`
        , `受注番号`
        , `受注日`
        , `取込日`
        , `受注チェック`
        , `受注チェック担当者`
        , `確認チェック`
        , `キャンセル`
        , `受注キャンセル日`
        , `受注状態`
        , `受注担当者`
        , `発送方法`
        , `支払方法`
        , `合計金額`
        , `税金`
        , `手数料`
        , `送料`
        , `その他`
        , `ポイント`
        , `承認金額`
        , `備考`
        , `入金金額`
        , `入金区分`
        , `入金日`
        , `納品書印刷指示日`
        , `納品書発行日`
        , `納品書備考`
        , `出荷日`
        , `出荷予定日`
        , `出荷担当者`
        , `作業者欄`
        , `ピック指示内容`
        , `ラベル発行日`
        , `配送日`
        , `配送時間帯`
        , `配送伝票番号`
        , `クレジット区分`
        , `名義人`
        , `有効期限`
        , `承認番号`
        , `承認区分`
        , `承認日`
        , `オーソリ名`
        , `顧客区分`
        , `顧客コード`
        , `購入者名`
        , `購入者カナ`
        , `購入者郵便番号`
        , `購入者住所1`
        , `購入者住所2`
        , `購入者電話番号`
        , `購入者ＦＡＸ`
        , `購入者メールアドレス`
        , `発送先名`
        , `発送先カナ`
        , `発送先郵便番号`
        , `発送先住所1`
        , `発送先住所2`
        , `発送先電話番号`
        , `発送先ＦＡＸ`
        , `配送備考`
        , `商品コード`
        , `商品名`
        , `受注数`
        , `商品単価`
        , `掛率`
        , `小計`
        , `商品オプション`
        , `引当数`
        , `引当日`
        , `出荷予定月日`
        , `出荷予定月`
      )
      SELECT
          `店舗名`
        , `伝票番号`
        , `受注番号`
        , `受注日` /* ここはDATETIMEに任せる。というかなぜ他がVARCHAR */
        , REPLACE(`取込日`, '/', '-')
        , `受注チェック`
        , `受注チェック担当者`
        , {$kubunConfirmCheckSql}
        , CASE
            WHEN `キャンセル` = 0 THEN 0
            ELSE 1
          END AS `キャンセル`
        , `受注キャンセル日`
        , `受注状態`
        , `受注担当者`
        , `発送方法`
        , `支払方法`
        , `合計金額`
        , `税金`
        , `手数料`
        , `送料`
        , `その他`
        , `ポイント`
        , `承認金額`
        , `備考`
        , `入金金額`
        , {$kubunPaymentSql}
        , REPLACE(`入金日`, '/', '-')
        , `納品書印刷指示日`
        , `納品書発行日`
        , `納品書備考`
        , REPLACE(`出荷日`, '/', '-')
        , REPLACE(`出荷予定日`, '/', '-')
        , `出荷担当者`
        , `作業者欄`
        , `ピック指示内容`
        , `ラベル発行日`
        , `配送日`
        , `配送時間帯`
        , `配送伝票番号`
        , `クレジット区分`
        , `名義人`
        , `有効期限`
        , `承認番号`
        , {$kubunAuthSql}
        , `承認日`
        , `オーソリ名`
        , {$kubunCustomerSql}
        , `顧客コード`
        , `購入者名`
        , `購入者カナ`
        , `購入者郵便番号`
        , `購入者住所1`
        , `購入者住所2`
        , `購入者電話番号`
        , `購入者ＦＡＸ`
        , `購入者メールアドレス`
        , `発送先名`
        , `発送先カナ`
        , `発送先郵便番号`
        , `発送先住所1`
        , `発送先住所2`
        , `発送先電話番号`
        , `発送先ＦＡＸ`
        , `配送備考`
        , `商品コード`
        , `商品名`
        , `受注数`
        , `商品単価`
        , `掛率`
        , `小計`
        , `商品オプション`
        , `引当数`
        , REPLACE(`引当日`, '/', '-')
        , `出荷予定月日`
        , `出荷予定月`
      FROM
        tb_order_data_tmp
EOD;

    $stmt = $db->prepare($sql);
    // 区分値変換パラメータ
    foreach($kubunParams as $key => $value) {
      $stmt->bindValue($key, $value, \PDO::PARAM_STR); // すべて文字列
    }
    $stmt->execute();

    // 楽天 無効伝票削除処理 ※受注明細と同等の処理
    // ... だったが、そちらは差分更新の関連で不具合が入り修正されている。
    // こちらは厳密な引当数の集計は不要なため、そのままの処理。
    $sql = <<<EOD
        SELECT MAX(取込日)
        FROM tb_sales_detail
        WHERE 店舗コード = 1 /* Plus Nao 楽天市場店 */
          AND 受注状態 = '起票済(CSV/手入力)'
EOD;
    $maxRakutenCsvImportDate = $db->query($sql)->fetchColumn(0);

    $sql = <<<EOD
        DELETE FROM tb_order_data
        WHERE 店舗名 = 'Plus Nao 楽天市場店'
          AND 受注状態 = '受注メール取込済'
          AND 取込日 < :lastImportDate
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':lastImportDate', $maxRakutenCsvImportDate, \PDO::PARAM_STR);
    $stmt->execute();

    $logger->info('受注データ取込 一時テーブルからコピー');
    $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

    // order_data_mainadd 更新
    $this->neMallProcess->updateOrderDataMainaddWithSalesDetailAnalyze();


    //'最終更新日時をセット
    $this->commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_ORDER);

    //'売上急上昇商品をメール通知
    // → この機能は不要とのことで、実装しない
    //Call SendSuddenSales
  }

}
