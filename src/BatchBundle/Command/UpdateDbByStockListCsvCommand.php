<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use Goutte\Client;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;


class UpdateDbByStockListCsvCommand extends ContainerAwareCommand
{
  /** @var FileUtil $util */
  private $fileUtil;

  /** @var SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:update-db-by-stock-list-csv')
      ->setDescription('在庫一覧CSVを元にしたDB更新処理を行う')
      ->addArgument('filename', InputArgument::REQUIRED, '取得CSVファイル名')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $this->fileUtil = $this->getContainer()->get('misc.util.file');

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');
    $logger->initLogTimer();

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理 （時間計測を実装したメソッドにする）
    $logExecTitle = '在庫データ取込';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    $fs = new FileSystem();

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
    $doctrine = $container->get('doctrine');
    $commonUtil = new DbCommonUtil($doctrine);

    $rootDir = $container->get('kernel')->getRootDir();
    $dataDir = dirname($rootDir) . '/data/stocks';
    $fileName = $input->getArgument('filename');
    $filePath = $dataDir . '/' . $fileName;

    try {
      $logger->info('ファイル Open');

      $fileInfo = $this->fileUtil->getTextFileInfo($filePath);
      if (!$fileInfo['exists']) {
        throw new RuntimeException('no file!! [' . $filePath . ']');
      }
      if (!$fileInfo['readable']) {
        throw new RuntimeException('not readable!! [' . $filePath . ']');
      }

      // CSV書式確認
      if (!$this->validateCsv($fileInfo)) {
        throw new \RuntimeException('invalid CSV data . [' . $filePath . ']');
      }

      // 一時テーブルへCSV格納
      $time = microtime(true);
      $logger->info('一時テーブルへCSV格納');

      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $container->get('doctrine')->getConnection('main');
      $dbMain->query("TRUNCATE tb_totalstock_dl");
      $dbMain->query("SET character_set_database=sjis;");

      $sql  = " LOAD DATA LOCAL INFILE '" . $filePath . "' ";
      $sql .= " INTO TABLE tb_totalstock_dl ";
      $sql .= "   FIELDS ENCLOSED BY '\"' TERMINATED BY ',' ";
      $sql .= "   LINES TERMINATED BY '\\r\\n' IGNORE 1 LINES;";

      $dbMain->query($sql);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 波ダッシュ補正
      // TODO もし単純な置換なら、CSV上でやってしまったほうが軽くないか？
      $logger->info('波ダッシュ補正');

      $dbMain->query("CALL PROC_FIX_WAVEDASH_totalstock_dl;");
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // tb_productchoiceitems項目のリセット
      $logger->info('tb_productchoiceitems項目のリセット');

      $sql = " UPDATE tb_productchoiceitems SET ";
      $sql .= "   在庫数           = 0";
      $sql .= " , 引当数           = 0";
      $sql .= " , フリー在庫数     = 0";
      $sql .= " , 予約在庫数       = 0";
      $sql .= " , 予約引当数       = 0";
      $sql .= " , 予約フリー在庫数 = 0";
      $sql .= " , 不良在庫数       = 0;";
      $dbMain->query($sql);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 'tb_totalstock_dlからtb_productchoiceitemsへ転記
      $logger->info('tb_totalstock_dlからtb_productchoiceitemsへ転記');

      $sql  = " UPDATE tb_totalstock_dl INNER JOIN tb_productchoiceitems " ;
      $sql .= " ON tb_totalstock_dl.商品コード = tb_productchoiceitems.ne_syohin_syohin_code SET" ;
      $sql .= "   tb_productchoiceitems.在庫数 = tb_totalstock_dl.在庫数" ;
      $sql .= "  , tb_productchoiceitems.引当数 = tb_totalstock_dl.引当数" ;
      $sql .= "  , tb_productchoiceitems.フリー在庫数 = tb_totalstock_dl.フリー在庫数" ;
      $sql .= "  , tb_productchoiceitems.予約在庫数 = tb_totalstock_dl.予約在庫数" ;
      $sql .= "  , tb_productchoiceitems.予約引当数 = tb_totalstock_dl.予約引当数" ;
      $sql .= "  , tb_productchoiceitems.予約フリー在庫数 = tb_totalstock_dl.予約フリー在庫数" ;
      $sql .= "  , tb_productchoiceitems.不良在庫数 = tb_totalstock_dl.不良在庫数;";
      $dbMain->query($sql);

      // 入荷入力反映確認用在庫数 更新処理
      $virtualCode = $commonUtil->getSettingValue('NYUKA_HANNEI_KAKUNIN_CODE');
      if (strlen($virtualCode)) {
        $sql = "SELECT 在庫数 FROM tb_totalstock_dl WHERE `商品コード` = :syohinCode";
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':syohinCode', $virtualCode);
        $stmt->execute();
        $num = $stmt->fetchColumn(0);
        if (!is_null($num)) {
          $commonUtil->updateSettingValue('NYUKA_HANNEI_KAKUNIN_NE_STOCK', $num);
        }
      }

      $sql  = " DELETE tb_productchoiceitems_former.*" ;
      $sql .= " FROM tb_productchoiceitems_former LEFT JOIN tb_totalstock_dl" ;
      $sql .= " ON tb_productchoiceitems_former.ne_syohin_syohin_code = tb_totalstock_dl.商品コード" ;
      $sql .= " WHERE ISNULL(tb_totalstock_dl.商品コード);";
      $dbMain->query($sql);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // '在庫数0の商品のロケーションを_newに戻す
      $logger->info('庫数0の商品のロケーションを_newに戻す');

      $sql  = " UPDATE tb_productchoiceitems";
      $sql .= " SET tb_productchoiceitems.location = '_new'";
      $sql .= " WHERE tb_productchoiceitems.在庫数 = 0;";
      $dbMain->query($sql);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // 総在庫数の再設定
      $logger->info('総在庫数の再設定');

      $dbMain->query("UPDATE tb_mainproducts SET 総在庫数=0;");

      $sql  = " UPDATE tb_mainproducts INNER JOIN" ;
      $sql .= "(SELECT tb_productchoiceitems.daihyo_syohin_code, SUM(tb_productchoiceitems.フリー在庫数) AS Result_1" ;
      $sql .= " FROM tb_productchoiceitems" ;
      $sql .= " WHERE tb_productchoiceitems.フリー在庫数 > 0" ;
      $sql .= " GROUP BY tb_productchoiceitems.daihyo_syohin_code) AS TB_2" ;
      $sql .= " ON tb_mainproducts.daihyo_syohin_code = TB_2.daihyo_syohin_code" ;
      $sql .= " SET tb_mainproducts.総在庫数 = TB_2.Result_1;";
      $dbMain->query($sql);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));


      // '在庫履歴を作成
      $sql  = " insert into tb_stock_history(現在庫数,フリー在庫数,現在庫金額,フリー在庫金額)";
      $sql .= " select sum(在庫数) as 現在庫数";
      $sql .= " , sum(フリー在庫数) as フリー在庫数";
      $sql .= " , truncate(sum(在庫数 * price.cost_tanka),0) as 現在庫金額";
      $sql .= " , truncate(sum(フリー在庫数 * price.cost_tanka),0) as フリー在庫金額";
      $sql .= " from tb_productchoiceitems as pc";
      $sql .= " inner join tb_mainproducts as m";
      $sql .= " on pc.daihyo_syohin_code=m.daihyo_syohin_code";
      $sql .= " inner join tb_mainproducts_cal as cal";
      $sql .= " on m.daihyo_syohin_code=cal.daihyo_syohin_code";
      $sql .= " inner join tb_vendormasterdata as v";
      $sql .= " on m.sire_code=v.sire_code";
      $sql .= " INNER JOIN v_product_price price on m.daihyo_syohin_code = price.daihyo_syohin_code ";
      $dbMain->query($sql);
      $now = microtime(true); $rap = $now - $time; $time = $now; $logger->info('rap: ' . round($rap, 2));

      // '最終更新日時をセット
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_STOCK);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('DB更新 by 在庫一覧CSV 完了');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "在庫データ取込処理でエラーが発生しました。", 'error'
      );
      $logger->logTimerFlush();

      return 1;
    }
  }

  /// CSV書式チェック
  private function validateCsv($fileInfo)
  {
    // $logger = $this->getContainer()->get('misc.util.batch_logger');

    // ファイルがない
    if (!$fileInfo['exists'] || !$fileInfo['readable']) {
      return false;
    }

    // ヘッダ行チェック
    $fp = fopen($fileInfo['path'], "r");
    $fileHeader = trim(mb_convert_encoding(fgets($fp), 'UTF-8', 'SJIS-WIN'));
    $validHeaderLine = '"商品コード","商品名","在庫数","引当数","フリー在庫数","予約在庫数","予約引当数","予約フリー在庫数","不良在庫数"';

    if ($fileHeader !== $validHeaderLine) {
      return false;
    }

    // ファイルサイズチェック TODO


    return true;
  }


}
