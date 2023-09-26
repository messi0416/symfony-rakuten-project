<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\RakutenMallProcess;
use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\BatchLockException;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;


class CsvDownloadAndUpdateRakutenReviewCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers 実行アカウント */
  private $account;

  /** @var string 対象店舗。rakuten/motto/laforest/dolcissimo/gekipla */
  private $targetShop;

  /** @var string コマンド和名 */
  private $commandName;

  /** @var string CSV保存パス、プレフィックスに使用 */
  private $shopName;

  /** @var int tb_updaterecordのupdaterecordno、更新日時保存用 */
  private $recodeNumber;

  /** @var string CSVデータ一時保存用テーブル名 */
  private $tmpTable;

  /** @var int NEモールID */
  private $rakutenNeMallId;

  // 通知閾値ポイント
  const NOTIFICATION_POINT_THRESHOLD = 2;

  /** 対象店舗文字列：楽天 */
  const TARGET_SHOP_RAKUTEN = 'rakuten';
  /** 対象店舗文字列：motto-motto */
  const TARGET_SHOP_MOTTO = 'motto';
  /** 対象店舗文字列：La forest */
  const TARGET_SHOP_LAFOREST = 'laforest';
  /** 対象店舗文字列：dolcissimo */
  const TARGET_SHOP_DOLCISSIMO = 'dolcissimo';
  /** 対象店舗文字列：gekipla */
  const TARGET_SHOP_GEKIPLA = 'gekipla';

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-rakuten-review')
      ->setDescription('login to RMS(rakuten) Web site and download review CSV file, and update DB.')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('downloaded-data', null, InputOption::VALUE_OPTIONAL, 'ダウンロード済みファイル指定（ダウンロードをskip）')
      ->addOption('only-new', null, InputOption::VALUE_OPTIONAL, '新規分のみ取得する')
      ->addOption('target-shop', null, InputOption::VALUE_OPTIONAL, '対象店舗。rakuten|motto|laforest|dolcissimo|gekipla', self::TARGET_SHOP_RAKUTEN)
      ;
  }

    /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '楽天レビュー取込';
    $logger = $this->getLogger();

    // このコマンドでは、shopName は、楽天のパスを記載。https://www.rakuten.co.jp/○○/ ← この○○の部分。
    $this->targetShop = $input->getOption('target-shop');
    if ($this->targetShop == self::TARGET_SHOP_RAKUTEN) {
      $this->commandName = '楽天レビュー取込[plusnao]';
      $this->shopName = 'plusnao';
      $this->recodeNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_RAKUTEN_REVIEW;
      $this->tmpTable = 'tmp_rakuten_plusnao_reviews';
      $this->rakutenNeMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN;
    } else if ($this->targetShop == self::TARGET_SHOP_MOTTO) {
      $this->commandName = '楽天レビュー取込[motto]';
      $this->shopName = 'motto-motto';
      $this->recodeNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_MOTTO_REVIEW;
      $this->tmpTable = 'tmp_motto_reviews';
      $this->rakutenNeMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_MOTTO;
    } else if ($this->targetShop == self::TARGET_SHOP_LAFOREST) {
      $this->commandName = '楽天レビュー取込[laforest]';
      $this->shopName = 'laforest';
      $this->recodeNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_LAFOREST_REVIEW;
      $this->tmpTable = 'tmp_laforest_reviews';
      $this->rakutenNeMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_LAFOREST;
    } else if ($this->targetShop == self::TARGET_SHOP_DOLCISSIMO) {
      $this->commandName = '楽天レビュー取込[dolcissimo]';
      $this->shopName = 'kobe-s';
      $this->recodeNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_DOLCISSIMO_REVIEW;
      $this->tmpTable = 'tmp_dolcissimo_reviews';
      $this->rakutenNeMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_DOLTI;
    } else if ($this->targetShop == self::TARGET_SHOP_GEKIPLA) {
      $this->commandName = '楽天レビュー取込[gekipla]';
      $this->shopName = 'geki-pla';
      $this->recodeNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_GEKIPLA_REVIEW;
      $this->tmpTable = 'tmp_gekipla_reviews';
      $this->rakutenNeMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_GEKIPLA;
    } else {
      $logger->addDbLog($logger->makeDbLog($this->commandName, 'エラー終了', "店舗指定不正[" . $this->targetShop . "]"));
      throw new \RuntimeException("[{$this->commandName}]対象店舗指定不正のため処理終了");
    }
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->initializeProcess($input);

    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('楽天レビュー取込処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logger->addDbLog($logger->makeDbLog($this->commandName, $this->commandName, '開始'));

    $rootDir = $container->get('kernel')->getRootDir();
    $dataDir = dirname($rootDir) . '/data';

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $container->get('misc.util.db_common');
      $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime($this->recodeNumber);

      $saveDir = null;

      // ディレクトリ指定があればダウンロードはスキップ
      $downloadedFile = $input->getOption('downloaded-data');
      if (!$downloadedFile) {

        $logger->addDbLog($logger->makeDbLog($this->commandName, 'CSVダウンロード', '開始')->setLogLevel(TbLog::DEBUG));

        // RMS ログイン
        $client = $webAccessUtil->getWebClient();
        $crawler = $webAccessUtil->rmsLogin($client, 'api', $this->targetShop);

        // レビューチェックツール画面へ移動
        $nextUrl = 'https://review.rms.rakuten.co.jp/';
        $crawler = $client->request('get', $nextUrl);

        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        $header = null;
        try {
          $header = $crawler->filter('#header_nav_title');
        } catch (\InvalidArgumentException $e) {
          // do nothing
        }
        if ($status !== 200 || !$header || strpos($header->text(), 'レビューチェックツール') === false ) {
          throw new RuntimeException('move to access review-check-tool page error!! [' . $status . '][' . $uri . ']');
        }
        $logger->debug('レビューチェックツール画面へ遷移成功');

        // 日付絞込※一度に取得できるのは3000件まで、絞り込まない場合新しいほうから3000件（2020/04現在）
        if ($input->getOption('only-new')) {

          // 取得開始日指定
          if ($lastUpdated) {
            $uri = $client->getRequest()->getUri() . 'search/index/';

            $now = new \DateTime();
            $params = [
                'sy' => $lastUpdated->format('Y')
              , 'sm' => $lastUpdated->format('n')
              , 'sd' => $lastUpdated->format('j')
              , 'sh' => $lastUpdated->format('G')
              , 'si' => intval($lastUpdated->format('i')) // ※0をつけない
              , 'ey' => $now->format('Y')
              , 'em' => $now->format('n')
              , 'ed' => $now->format('j')
              , 'eh' => $now->format('G')
              , 'ei' => intval($now->format('i')) // ※0をつけない
              , 'ev' => '0' // 評価 0:全部
              , 'tc' => '0' // 商品レビュー／ショップレビュー 0:両方
              , 'kw' => '' // キーワード
              , 'ao' => 'A' // And or Or (A|O)
              , 'st' => '1' // ソート順 1:新着順 2:評価が低い順
            ];

            $uri .= '?' . http_build_query($params);
            $logger->info($uri);

            $crawler = $client->request('get', $uri);

            $response = $client->getResponse();
            $status = $response->getStatus();
            $uri = $client->getRequest()->getUri();

            $header = null;
            try {
              $header = $crawler->filter('#header_nav_title');
            } catch (\InvalidArgumentException $e) {
              // do nothing
            }
            if ($status !== 200 || !$header || strpos($header->text(), 'レビューチェックツール') === false ) {
              throw new \RuntimeException('move to access review-check-tool page error!! [' . $status . '][' . $uri . ']');
            }

            $logger->info('レビューチェックツール画面 絞込成功');

            $resultBox = null;
            try {
              $resultBox = $crawler->filter('#search_message_box');
              if ($resultBox && strstr($resultBox->text(), 'ご指定の検索条件に該当するレビューはありませんでした。') !== FALSE ) {
                $logger->addDbLog($logger->makeDbLog($this->commandName, $this->commandName, '終了')->setInformation(['message' => '該当レビューなし', 'searchDate' => $lastUpdated->format('Y-m-d H:i:s')])->setLogLevel(TbLog::DEBUG));
                $logger->logTimerFlush();

                $logger->info('レビュー件数なし。終了');
                return 0;
              }

            // nodeがなければ問題なし
            // （node が見つからないだけで例外を投げるcrawlerの挙動は正直使いにくい）
            } catch (\InvalidArgumentException $e) {
              // do nothing
            }
          }
        }

        // CSVダウンロードリンク取得
        $link = $crawler->selectLink('CSVダウンロード')->link();
        $logger->info($link->getUri());

        // CSVダウンロード 実行
        $crawler = $client->click($link);

        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();
        $contentType = $response->getHeader('Content-Type');
        $contentLength = intval($response->getHeader('Content-Length'));

        if ($status !== 200 || strpos($contentType, 'application/octet-stream') === false) {
          throw new RuntimeException('review csv download error!! [' . $status . '][' . $uri . '][' . $contentType . ']');
        }

        // CSVファイル保存ディレクトリ
        $fs = new FileSystem();
        $saveDir = $dataDir . '/review';
        if (!$fs->exists($saveDir)) {
          $fs->mkdir($saveDir, 0755);
        }

        // ファイル保存
        $fileName = preg_match('/filename="([a-zA-Z0-9_-]+.csv)"/', $response->getHeader('Content-Disposition'), $match)
          ? $match[1]
          : sprintf('data%s.csv', date('YmdHis00000000'));

        $path = $saveDir . '/' . $this->shopName . '_' . $fileName;
        if ($fs->exists($path)) {
          throw new RuntimeException('same csv name exists error!! [' . $path . ']');
        }

        $file = new \SplFileObject($path, 'w'); // 上書き
        $bytes = $file->fwrite($response->getContent());

        if (!$fs->exists($path) || ! $bytes) {
          @$fs->remove($path);
          throw new RuntimeException('can not save csv file. [ ' . $path . ' ][' . $bytes . '][' . $contentLength . ']');
        }
        $logger->info('楽天レビュー CSV出力成功。[' . $path . ']');

        // DB記録＆通知処理
        // チェック機能のため、サブ2にファイル名、サブ3に行数、ファイルサイズを登録(JSON)
        $fileInfo = $this->getFileUtil()->getTextFileInfo($path);
        $info = [
            'size' => $fileInfo['size']
          , 'lineCount' => $fileInfo['lineCount']
        ];

        $logger->addDbLog($logger->makeDbLog($this->commandName, '' . ' 楽天レビューCSVダウンロード')->setInformation($info)->setLogLevel(TbLog::DEBUG));

        $downloadedFile = $path;
      }

      $logger->addDbLog($logger->makeDbLog($this->commandName, 'CSVダウンロード', '終了')->setLogLevel(TbLog::DEBUG));

      // ====================================================
      // 取込処理を実行 （ダウンロードが早いので分割せず処理してしまう）
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($this->commandName, 'CSV取込処理', '開始')->setLogLevel(TbLog::DEBUG));

      $info = $this->importCsvData($logger, $downloadedFile);

      $logger->info('楽天レビュー取込処理が終了しました。');
      $logger->addDbLog($logger->makeDbLog($this->commandName, $this->commandName, '終了'));
      $logger->logTimerFlush();

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $info = [
        'message' => $e->getMessage()
      ];
      if ($e instanceof BatchLockException) {
        $lock = $e->getLock();
        $info['password'] = $lock->getLockKey();

        // ロックが通知されてから一定期間で再度通知
        if ($lock->hasToNotify()) {
          $logger->addDbLog(
            $logger->makeDbLog($this->commandName, $this->commandName, 'エラー終了')->setInformation($info)
            , true, "{$this->commandName}でエラーが発生しました。処理は停止したままです。（バッチ処理ロック通知）", 'error'
          );

          $lock->setLastNotified(new \DateTime());

          /** @var EntityManager $em */
          $em = $this->getDoctrine()->getManager('main');
          $em->persist($lock);
          $em->flush();
        }

      } else {
        $logger->addDbLog(
          $logger->makeDbLog($this->commandName, $this->commandName, 'エラー終了')->setInformation($info)
          , true, "{$this->commandName}でエラーが発生しました。", 'error'
        );
      }
      return 1;
    }
  }

  /**
   * ダウンロードCSV DB取込処理
   * @param string $filePath
   * @return array
   */
  private function importCsvData($logger, $filePath)
  {

    $fs = new FileSystem();

    if (!$fs->exists($filePath)) {
      throw new RuntimeException('no data file!! [' . $filePath . ']');
    }

    $commonUtil = $this->getDbCommonUtil();

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDb('main');

    if (!$this->validateCsv($filePath)) {
      throw new RuntimeException('楽天レビューCSVが正しくダウンロードされませんでした。');
    }

    // 実行前 行数
    $countPreReview = $dbMain->fetchColumn('SELECT COUNT(*) FROM tb_rakuten_reviews');
    $countPreShop   = $dbMain->fetchColumn('SELECT COUNT(*) FROM tb_rakuten_shop_reviews');

    $dbTmpName = $this->getDb('tmp')->getDatabase();

    $sql = "DROP TABLE IF EXISTS {$dbTmpName}.{$this->tmpTable};";
    $dbMain->query($sql);

    $sql = <<<EOD
      CREATE TABLE {$dbTmpName}.{$this->tmpTable} (
        `レビュータイプ` varchar(255) NOT NULL DEFAULT '',
        `商品名` varchar(255) DEFAULT NULL,
        `レビュー詳細URL` varchar(255) NOT NULL DEFAULT '',
        `評価` tinyint(4) DEFAULT '0',
        `投稿時間` varchar(255) DEFAULT NULL,
        `タイトル` varchar(255) NOT NULL DEFAULT '',
        `レビュー本文` text,
        `フラグ` tinyint(4) DEFAULT '0',
        `注文番号` varchar(255) NOT NULL DEFAULT '',
        `未対応フラグ` varchar(10) NOT NULL DEFAULT '',
        `daihyo_syohin_code` varchar(30) NOT NULL,
        `購入日時` datetime NOT NULL,
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        PRIMARY KEY (`ID`),
        UNIQUE KEY `レビュー詳細URL` (`レビュー詳細URL`),
        KEY `daihyo_syohin_code` (`daihyo_syohin_code`) USING BTREE,
        KEY `index_注文番号` (`注文番号`),
        KEY `index_url` (`レビュー詳細URL`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8
EOD;
    $dbMain->query($sql);

    $dbMain->query("SET character_set_database=sjis;");

    $sql = <<<EOD
      LOAD DATA LOCAL INFILE '{$filePath}'
      INTO TABLE {$dbTmpName}.{$this->tmpTable}
      FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
      LINES TERMINATED BY '\\r\\n' IGNORE 1 LINES
EOD;


    $dbMain->query($sql);

    // '既に存在するレビューを削除 （review）
    $sql = <<<EOD
      DELETE TMP.*
      FROM {$dbTmpName}.{$this->tmpTable} AS TMP
      INNER JOIN tb_rakuten_reviews AS R ON TMP.レビュー詳細URL = R.レビュー詳細URL
EOD;
    $dbMain->query($sql);

    // '既に存在するレビューを削除 （shop_review）
    $sql = <<<EOD
      DELETE TMP.*
      FROM {$dbTmpName}.{$this->tmpTable} AS TMP
      INNER JOIN tb_rakuten_shop_reviews AS R ON TMP.レビュー詳細URL = R.レビュー詳細URL
EOD;
    $dbMain->query($sql);

    // tmp上で代表商品コードと購入日時を補完（この時点の代表商品コードは、2件以上明細があると不正なものに紐づく場合がある。商品レビューは次でつけなおし）
    $sql = <<<EOD
      UPDATE {$dbTmpName}.{$this->tmpTable} tmp
      INNER JOIN tb_sales_detail_analyze AS A ON tmp.注文番号 = A.受注番号
      SET tmp.daihyo_syohin_code = A.daihyo_syohin_code
        , tmp.購入日時 = A.受注日;
EOD;
    $dbMain->query($sql);

    // 2件以上明細がある商品レビューを抽出し、スクレイピングで正しい代表商品コードにつけなおし
    // ※ショップレビューは2件以上の購入がある場合、そもそも特定商品へのレビューという形にならないのでつけなおさず代表1件とする
    /** @var RakutenMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.rakuten');

    $sql = <<<EOD
      SELECT
          t2.ID as id
        , t2.レビュー詳細URL as url
      FROM (
        SELECT
            tmp.注文番号
        FROM {$dbTmpName}.{$this->tmpTable} tmp
        JOIN tb_sales_detail_analyze a ON tmp.注文番号 = a.受注番号
        WHERE tmp.レビュータイプ = '商品レビュー'
        GROUP BY tmp.注文番号
        HAVING count(*) >= 2
      ) pick
      JOIN {$dbTmpName}.{$this->tmpTable} t2 ON pick.注文番号 = t2.注文番号 AND t2.レビュータイプ = '商品レビュー'
EOD;
    $stmt = $dbMain->query($sql);

    while ($review = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $daihyoSyohinCode = '';
      try {
        $daihyoSyohinCode = $processor->getDaihyoSyohinCodeFromProductReview($logger, $review['url'], $this->shopName);
      } catch (Exception $e) {
        // 取れないものがあってもエラーを吐いて握りつぶす 取れなかったものは別途補正バッチでリトライ
        $logger->error("ID [" . $review['id'] . "]:" . $e->getMessage());
      }
      $sql = <<<EOD
        UPDATE {$dbTmpName}.{$this->tmpTable}
        SET daihyo_syohin_code = :daihyoSyohinCode
        WHERE ID = :id
EOD;
      $stmt2 = $dbMain->prepare($sql);
      $stmt2->bindValue(':daihyoSyohinCode', $daihyoSyohinCode);
      $stmt2->bindValue(':id', $review['id']);
      $stmt2->execute();
    }

    try {
      $dbMain->beginTransaction();

      if ($this->targetShop === self::TARGET_SHOP_RAKUTEN) {
        // インサート (shop_review)
        $sql = <<<EOD
          INSERT INTO tb_rakuten_shop_reviews (
              レビュータイプ
            , 商品名
            , レビュー詳細URL
            , 評価
            , 投稿時間
            , タイトル
            , レビュー本文
            , フラグ
            , 注文番号
            , daihyo_syohin_code
            , 購入日時
          )
          SELECT
              レビュータイプ
            , 商品名
            , レビュー詳細URL
            , 評価
            , 投稿時間
            , タイトル
            , レビュー本文
            , フラグ
            , 注文番号
            , daihyo_syohin_code
            , 購入日時
          FROM {$dbTmpName}.{$this->tmpTable}
          WHERE レビュータイプ <> '商品レビュー'
          ORDER BY 投稿時間
EOD;
        $dbMain->query($sql);

        $sql = <<<EOD
          INSERT INTO tb_rakuten_reviews (
              レビュータイプ
            , 商品名
            , レビュー詳細URL
            , 評価
            , 投稿時間
            , タイトル
            , レビュー本文
            , フラグ
            , 注文番号
            , daihyo_syohin_code
            , 購入日時
          )
          SELECT
              レビュータイプ
            , 商品名
            , レビュー詳細URL
            , 評価
            , 投稿時間
            , タイトル
            , レビュー本文
            , フラグ
            , 注文番号
            , daihyo_syohin_code
            , 購入日時
          FROM
            {$dbTmpName}.{$this->tmpTable}
          WHERE レビュータイプ = '商品レビュー'
          ORDER BY 投稿時間
EOD;
        $dbMain->query($sql);
      }

      // 共通商品レビュー管理テーブルにもINSERT
      // レビュー詳細URLが同一の登録済みデータは除く（このバッチで登録する限りないはずだけれど念のため）
      $sql = <<<EOD
        INSERT INTO tb_product_reviews
        SELECT
            NULL as id
          , :rakuten_ne_mall_id as ne_mall_id
          , r.レビュー詳細URL as original_review_id
          , r.daihyo_syohin_code
          , CASE
              WHEN same_product.count = 1 THEN same_product.ne_syohin_syohin_code
              ELSE NULL
            END as ne_syohin_syohin_code
          , a.伝票番号 as voucher_number
          , r.投稿時間 as review_datetime
          , r.評価
          , r.タイトル as title
          , r.レビュー本文 as body
          , r.投稿時間
          , 0
        FROM {$dbTmpName}.{$this->tmpTable} r
        LEFT JOIN tb_sales_detail_analyze a ON r.注文番号 = a.受注番号
        LEFT JOIN (
          SELECT
              tmp.ID
            , MAX(a2.商品コード（伝票）) as ne_syohin_syohin_code
            , count(*) as count
          FROM {$dbTmpName}.{$this->tmpTable} tmp
          LEFT JOIN tb_sales_detail_analyze a2 ON tmp.注文番号 = a2.受注番号 AND tmp.daihyo_syohin_code = a2.daihyo_syohin_code
          GROUP BY tmp.ID
        ) same_product ON r.ID = same_product.ID
        LEFT JOIN tb_product_reviews pr ON pr.ne_mall_id = :rakuten_ne_mall_id AND pr.original_review_id = r.レビュー詳細URL
        WHERE pr.original_review_id IS NULL
          AND r.レビュータイプ = '商品レビュー'
        GROUP BY r.ID
        ORDER BY r.ID
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':rakuten_ne_mall_id', $this->rakutenNeMallId);
      $stmt->execute();

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
                SELECT distinct daihyo_syohin_code FROM {$dbTmpName}.{$this->tmpTable} WHERE レビュータイプ = '商品レビュー'
            ) tmp ON tmp.daihyo_syohin_code = r.daihyo_syohin_code
            WHERE delete_flg = 0
            GROUP BY daihyo_syohin_code
        ) AS r ON cal.daihyo_syohin_code = r.daihyo_syohin_code
        SET cal.review_num = r.review_num
          , cal.last_review_date = r.last_review_date
          , cal.review_point_ave = r.review_point_ave
EOD;
      $stmt = $dbMain->query($sql);

      $dbMain->commit();
    } catch (\Exception $e) {
      $dbMain->rollback();
      throw $e;
    }

    // 最終投稿日時をtmpから取得
    $sql = <<<EOD
      SELECT MAX(投稿時間) AS 投稿時間 FROM {$dbTmpName}.{$this->tmpTable};
EOD;
    $stmt = $dbMain->query($sql);

    $lastPosted = null;
    if ($date = $stmt->fetchColumn(0)) {
      $lastPosted = new \DateTime($date);
    }

    // 投稿がなく、最新が取得できない場合は更新しない
    if ($lastPosted) {

      // 最終更新日時をセット
      $commonUtil->updateUpdateRecordTable($this->recodeNumber, $lastPosted);
    }

    // ファイル削除
    try {
      $fs->remove($filePath);
    } catch (\Exception $e) {
      // 握りつぶす
    }

    // 実行後 行数
    $countPostReview = $dbMain->fetchColumn('SELECT COUNT(*) FROM tb_rakuten_reviews');
    $countPostShop   = $dbMain->fetchColumn('SELECT COUNT(*) FROM tb_rakuten_shop_reviews');

    $info = [
        'pre'  => [ $countPreReview, $countPreShop ]
      , 'post' => [ $countPostReview, $countPostShop ]
      , 'diff' => [ $countPostReview - $countPreReview, $countPostShop - $countPreShop ]
    ];
    return $info;
  }


  /**
   * CSVデータ書式チェック
   */
  private function validateCsv($path)
  {
    // 一行目で判定
    $validLine = '"レビュータイプ","商品名","レビュー詳細URL","評価","投稿時間","タイトル","レビュー本文","フラグ","注文番号","未対応フラグ"';

    $fp = fopen($path, 'r');
    $line = fgets($fp);
    fclose($fp);

    return (trim(mb_convert_encoding($line, 'UTF-8', 'SJIS-WIN')) === $validLine);
  }
}
