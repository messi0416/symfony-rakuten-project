<?php
/**
 * バッチ処理 Yahoo商品レビューCSVデータ登録処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\EntityInterface\SymfonyUserInterface;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use MiscBundle\Entity\TbShoppingMall;

class UpdateYahooReviewCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUserInterface */
  private $account;

  private $results;

  private $doChangeLocationOrder = false;

  protected function configure()
  {
    $this
    ->setName('batch:review_csv_register')
    ->setDescription('Yahoo商品レビューCSVデータ登録処理')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('file-name', null, InputOption::VALUE_OPTIONAL, 'レビューCSVファイル名')
    ->addOption('review-site-name', null, InputOption::VALUE_OPTIONAL, 'レビュー登録サイト名')
    ->addOption('review-site-id', null, InputOption::VALUE_OPTIONAL, 'レビュー登録サイトID')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Yahoo商品レビューCSVデータ登録処理を開始しました。');

    $this->setInput($input);
    $this->setOutput($output);
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
      $reviewSiteName = $input->getOption('review-site-name');
      $reviewSiteId = $input->getOption('review-site-id');

      $logExecTitle = 'Yahoo商品レビューCSVデータ登録処理 ('.$reviewSiteName.')';
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // CSVファイル読込
      $fileUtil = $this->getFileUtil();
      $dataDir = $fileUtil->getDataDir();
      $fileName = $input->getOption('file-name');
      $filePath = $dataDir.'/review/csv/'.$fileName;
      // 一時テーブル登録処理
      $this->createTemporaryTables($logger);
      $this->importCsv($logger, $filePath, $reviewSiteId);
      // 商品レビュー登録処理
      $this->createNewReviews($logger, $reviewSiteId);
      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Yahoo商品レビューCSVデータ登録処理を完了しました。');

    } catch (\Exception $e) {
      $logger->error('Yahoo商品レビューCSVデータ登録処理 エラー:' . $e->getMessage() . ':' . $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog(null, 'Yahoo商品レビューCSVデータ登録処理 エラー', 'エラー終了')->setInformation($e->getMessage() . ':' . $e->getTraceAsString())
        , true, 'Yahoo商品レビューCSVデータ登録処理 でエラーが発生しました。', 'error'
        );
      return 1;
    }
    return 0;
  }

  /**
   * テンポラリテーブルをクリアして新規生成
   * @param MiscBundle\Util\BatchLogger $logger Logger
   */
  private function createTemporaryTables($logger) {
    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');

    $dbTmp->query("DROP TABLE IF EXISTS tb_yahoo_reviews_tmp");
    $sql = <<<EOD
        CREATE TABLE `tb_yahoo_reviews_tmp` (
          `評価日` varchar(255) NOT NULL,
          `評価点数` int(11) NOT NULL,
          `商品名` varchar(255) DEFAULT NULL,
          `商品コード` varchar(30) DEFAULT NULL,
          `注文ID` varchar(255) DEFAULT NULL,
          `コメントタイトル` varchar(255) DEFAULT NULL,
          `コメント内容` text DEFAULT NULL,
          `投稿元モールレビューID` varchar(255) AS (CONCAT(注文ID, '_', 商品コード)) STORED,
          `ID` int(11) NOT NULL AUTO_INCREMENT,
          `voucher_number` int(11) DEFAULT NULL,
          `order_id` varchar(255) AS (SUBSTRING_INDEX(注文ID, '-', -1)) STORED,
          PRIMARY KEY (`ID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbTmp->query($sql);
  }

  /**
   * CSVをテンポラリ1へ取込み
   * @param MiscBundle\Util\BatchLogger $logger Logger
   * @param string $filePath ファイルパス
   * @param string $reviewSiteId NEモールID
   */
  private function importCsv($logger, $filePath, $reviewSiteId) {
    $fs = new FileSystem();

    if (!$fs->exists($filePath)) {
      throw new RuntimeException('no data file!! [' . $filePath . ']');
    }
    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');
    $dbMainName = $dbMain->getDatabase();

    $sql = <<<EOD
      LOAD DATA LOCAL INFILE '{$filePath}'
      INTO TABLE `tb_yahoo_reviews_tmp`
      FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
      LINES TERMINATED BY '\\n' IGNORE 1 LINES
EOD;
    $dbTmp->query($sql);

    /* テンポラリ上で伝票番号を補完 */

    $sql = <<<EOD
      UPDATE tb_yahoo_reviews_tmp tmp
      INNER JOIN {$dbMainName}.tb_sales_detail_analyze AS a ON a.店舗コード = {$reviewSiteId} AND tmp.order_id = a.受注番号
      SET tmp.voucher_number = a.伝票番号
EOD;
    $dbTmp->query($sql);
  }

  /**
   * Yahoo商品レビュー登録処理
   * @param MiscBundle\Util\BatchLogger $logger Logger
   * @param String $reviewSiteId NEモールID
   *
   */
  function createNewReviews($logger, $reviewSiteId) {
    $logger->info('Yahoo商品レビュー登録処理');

    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');
    $dbTmpName = $dbTmp->getDatabase();
    $dbMain = $this->getDb('main');

    // 過去データ削除
    // 登録済みデータはON DUPLICATE KEY UPDATE でバルクアップデート
    $sql = <<<EOD
      INSERT INTO tb_product_reviews (
        id
        , ne_mall_id
        , original_review_id
        , daihyo_syohin_code
        , ne_syohin_syohin_code
        , voucher_number
        , review_datetime
        , score
        , title
        , body
      )
      SELECT
        pr.id
        , {$reviewSiteId}
        , 投稿元モールレビューID
        , 商品コード
        , CASE
            WHEN same_product.count = 1 THEN same_product.ne_syohin_syohin_code
            ELSE NULL
          END as ne_syohin_syohin_code
        , yrt.voucher_number
        , 評価日
        , 評価点数
        , CASE
            WHEN コメント内容 LIKE (CONCAT(SUBSTRING(コメントタイトル,1,CHARACTER_LENGTH(コメントタイトル)-1),'%')) THEN null
            ELSE コメントタイトル
          END
        , コメント内容
      FROM
        {$dbTmpName}.tb_yahoo_reviews_tmp yrt
        LEFT JOIN tb_product_reviews pr ON pr.ne_mall_id = {$reviewSiteId} AND pr.original_review_id = yrt.投稿元モールレビューID
        LEFT JOIN (
          SELECT
              tmp2.id
            , MAX(a2.商品コード（伝票）) as ne_syohin_syohin_code
            , count(*) as count
          FROM {$dbTmpName}.tb_yahoo_reviews_tmp tmp2
          LEFT JOIN tb_sales_detail_analyze a2 ON tmp2.voucher_number = a2.伝票番号 AND tmp2.商品コード = a2.daihyo_syohin_code
          GROUP BY tmp2.id
        ) same_product ON yrt.id = same_product.id
      ON DUPLICATE KEY UPDATE 
        review_datetime = VALUES(review_datetime)
        , score = VALUES(score)
        , title = VALUES(title)
        , body = VALUES(body)
EOD;
    $dbMain->query($sql);

    // 今回レビューされた商品について、サマリを更新
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal AS cal
      INNER JOIN (
          SELECT
              r.daihyo_syohin_code
            , COUNT(*) AS review_num
            , MAX(review_datetime) AS last_review_date
            , AVG(r.score) AS review_point_ave
          FROM tb_product_reviews r
          JOIN (
              SELECT distinct 商品コード FROM {$dbTmpName}.tb_yahoo_reviews_tmp
          ) tmp ON tmp.商品コード = r.daihyo_syohin_code
          WHERE delete_flg = 0
          GROUP BY daihyo_syohin_code
      ) AS r ON cal.daihyo_syohin_code = r.daihyo_syohin_code
      SET cal.review_num = r.review_num
        , cal.last_review_date = r.last_review_date
        , cal.review_point_ave = r.review_point_ave
EOD;
    $dbMain->query($sql);
  }

}


