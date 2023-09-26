<?php
namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;

class Misc202004UpdateRakutenReviewDaihyoSyohinCodeCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
    ->setName('misc:202004-update-rakuten-review-daihyo-syohin-code')
    ->setDescription('楽天レビュー再取得処理')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('downloaded-data', null, InputOption::VALUE_OPTIONAL, 'ダウンロードする代わりに別のデータでimport（デバッグ用）')
    ->addOption('current-phase', 0, InputOption::VALUE_OPTIONAL, 'テンポラリ2への取り込み完了 =2。2を指定された場合ダウンロード・取り込みを行わず、テンポラリ2の続きの処理から実行')
    ;
  }

  /**
   * 処理フロー
   * (1) 3000件ずつダウンロード（ループ処理）
   * 　　・テンポラリ1へ登録
   * (2) テンポラリ2へ代表商品の付け替えが必要なものを登録
   * (3) テンポラリ2を順に取得して、テンポラリ1の代表商品を更新し、処理済みのテンポラリ2のレコードは削除
   * (4) テンポラリ2がなくなり、テンポラリ1のデータが全て修正されたら、本体へ反映
   * 　　・本体を別名で作成
   * 　　・旧本体データをTRUNCATEし、INSERT～SELECTで全件登録
   * (1)から(4)まで時間がかかるため、完了後、本体のレビュー処理はこの件のCSVダウンロード開始時刻以前まで巻き戻す
   * 本体の不具合は修正済みである必要がある
   *
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('楽天レビュー再取得処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = '楽天レビュー再取得';
    $logger->setExecTitle($logExecTitle);
    $logger->addDbLog($logger->makeDbLog(null, '開始'));

    $currentPhase = $input->getOption('current-phase');

    if ($currentPhase != 2) {

      $this->createTemporaryTables($logger);

      // レビューCSVをダウンロード
      $downloadedFile = $input->getOption('downloaded-data');
      if (!$downloadedFile) {
        $logger->addDbLog($logger->makeDbLog(null, 'CSVダウンロード', '開始'));
        $list = $this->getList();
        foreach ($list as $data) {
          $downloadedFile = $this->downloadCsv($logger, $data[0], $data[1]);
        }
        $logger->addDbLog($logger->makeDbLog(null, 'CSVダウンロード', '終了'));

      // CSVがパラメータで指定されていれば、ダウンロードなしでインポート
      } else {
        $this->importCsv($logger, $downloadedFile);
      }

      // テンポラリテーブル1, 2へ登録
      $this->adjustTempData($logger);
    }

    // テンポラリ1の代表商品コードをつけなおしていく
    $this->updateDaihyoSyohinCode($logger);

    // 新本体テーブル作成
    $this->createNewReviews($logger);

    $logger->addDbLog($logger->makeDbLog(null, '終了'));

    $logger->info('楽天レビュー代表商品コード再取得処理を終了しました。');
  }

  /**
   * テンポラリテーブル1をクリアして新規生成
   */
  private function createTemporaryTables($logger) {
    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');

    $dbTmp->query("DROP TABLE IF EXISTS tb_rakuten_reviews_tmp");
    $sql = <<<EOD
        CREATE TABLE `tb_rakuten_reviews_tmp` (
          `レビュータイプ` varchar(255) DEFAULT NULL,
          `商品名` varchar(255) DEFAULT NULL,
          `レビュー詳細URL` varchar(255) UNIQUE DEFAULT NULL,
          `評価` tinyint(1) DEFAULT '0',
          `投稿時間` varchar(255) DEFAULT NULL,
          `タイトル` varchar(255) DEFAULT NULL,
          `レビュー本文` text DEFAULT NULL,
          `フラグ` varchar(255) DEFAULT NULL,
          `注文番号` varchar(255) DEFAULT NULL,
          `未対応フラグ` varchar(10) NOT NULL DEFAULT '',
          `daihyo_syohin_code` varchar(30) NOT NULL,
          `購入日時` datetime NOT NULL,
          `ID` int(11) NOT NULL AUTO_INCREMENT,
          PRIMARY KEY (`ID`),
          KEY `index_注文番号` (`注文番号`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbTmp->query($sql);

    $dbTmp->query("DROP TABLE IF EXISTS tb_rakuten_reviews_multi_tmp");
    $sql = <<<EOD
        CREATE TABLE `tb_rakuten_reviews_multi_tmp` (
          `id` int(11) NOT NULL,
          `url` varchar(255) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOD;
    $dbTmp->query($sql);
  }

  /**
   * 楽天レビューCSVを取得するための日時リスト（二重配列）
   */
  private function getList() {

    $list = [
        ['2003/01/01', '2010/04/01'],
        ['2010/04/01', '2011/04/01'],
        ['2011/04/01', '2012/06/01'],
        ['2012/06/01', '2013/06/01'],
        ['2013/06/01', '2013/08/01'],
        ['2013/08/01', '2014/05/01'],
        ['2014/05/01', '2015/01/01'],
        ['2015/01/01', '2015/07/01'],
        ['2015/07/01', '2016/01/01'],
        ['2016/01/01', '2016/09/01'],
        ['2016/09/01', '2017/03/01'],
        ['2017/03/01', '2017/08/01'],
        ['2017/08/01', '2017/12/01'],
        ['2017/12/01', '2018/03/01'],
        ['2018/03/01', '2018/06/01'],
        ['2018/06/01', '2018/08/01'],
        ['2018/08/01', '2018/09/01'],
        ['2018/09/01', '2018/11/01'],
        ['2018/11/01', '2019/01/01'],
        ['2019/01/01', '2019/03/01'],
        ['2019/03/01', '2019/05/01'],
        ['2019/05/01', '2019/06/01'],
        ['2019/06/01', '2019/07/01'],
        ['2019/07/01', '2019/08/01'],
        ['2019/08/01', '2019/09/01'],
        ['2019/09/01', '2019/10/01'],
        ['2019/10/01', '2019/11/01'],
        ['2019/11/01', '2019/12/01'],
        ['2019/12/01', '2020/01/01'],
        ['2020/01/01', '2020/02/01'],
        ['2020/02/01', '2020/03/01'],
        ['2020/03/01', '2020/04/01'],
        ['2020/04/01', '2020/05/01']
    ];

    return $list;
  }

  /**
   * 1件ずつダウンロードし、テンポラリ1へインポート
   * 指定された期間のデータを取得し、importを行う
   *
   * @param $logger Logger
   * @param $from この日時を含む
   * @param $to この日時を含む
   */
  private function downloadCsv($logger, $fromStr, $toStr) {
    $logger->info("楽天レビュー再取得：CSVダウンロード開始 [$fromStr - $toStr]");
    $from = new \DateTime($fromStr);
    $to = new \DateTime($toStr);

    $container = $this->getContainer();

    $rootDir = $container->get('kernel')->getRootDir();
    $dataDir = dirname($rootDir) . '/data';

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    // RMS ログイン
    $client = $webAccessUtil->getWebClient();
    $crawler = $webAccessUtil->rmsLogin($client, 'api'); // 必要なら、アカウント名を追加して切り替える

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
      throw new RuntimeException('move to access analyze page error!! [' . $status . '][' . $uri . ']');
    }

    // 取得開始日指定
    $uri = $client->getRequest()->getUri() . 'search/index/';

    $now = new \DateTime();
    $params = [
        'sy' => $from->format('Y')
        , 'sm' => $from->format('n')
        , 'sd' => $from->format('j')
        , 'sh' => '0'
        , 'si' => '0'
        , 'ey' => $to->format('Y')
        , 'em' => $to->format('n')
        , 'ed' => $to->format('j')
        , 'eh' => '23'
        , 'ei' => '59'
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
      throw new RuntimeException('move to access analyze page error!! [' . $status . '][' . $uri . ']');
    }

    $resultBox = null;
    try {
      $resultBox = $crawler->filter('#search_message_box');
      if ($resultBox && strstr($resultBox->text(), 'ご指定の検索条件に該当するレビューはありませんでした。') !== FALSE ) {
        $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation(['message' => '該当レビューなし', 'searchDate' => $lastUpdated->format('Y-m-d H:i:s')])->setLogLevel(TbLog::DEBUG));
        $logger->logTimerFlush();

        $logger->info('レビュー件数なし。終了');
        return 0;
      }

      // nodeがなければ問題なし
      // （node が見つからないだけで例外を投げるcrawlerの挙動は正直使いにくい）
    } catch (\InvalidArgumentException $e) {
      // do nothing
    }

    $pager = $crawler->filter('#pager span')->eq(0);
    preg_match('/全 \d{1,3}(,\d{3})*件/', $pager->text(), $matches);
    $logger->info("処理件数：" . $matches[0]);

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
    $saveDir = $dataDir . '/review/retry';
    if (!$fs->exists($saveDir)) {
      $fs->mkdir($saveDir, 0755);
    }

    $logger->info('楽天レビュー CSVダウンロードレスポンス取得');

    // ファイル保存
    $fileName = preg_match('/filename="([a-zA-Z0-9_-]+.csv)"/', $response->getHeader('Content-Disposition'), $match)
    ? $match[1]
    : sprintf('data%s.csv', $to->format('Ymd'));

    $path = $saveDir . '/' . $fileName;
    if ($fs->exists($path)) {
      throw new RuntimeException('same csv name exists error!! [' . $path . ']');
    }

    $file = new \SplFileObject($path, 'w'); // 上書き
    $bytes = $file->fwrite($response->getContent());

    if (!$fs->exists($path) || ! $bytes) {
      @$fs->remove($path);
      throw new RuntimeException('can not save csv file. [ ' . $path . ' ][' . $bytes . '][' . $contentLength . ']');
    }
    $logger->info("楽天レビュー再取得：CSVダウンロード成功 [$path]（$fromStr - $toStr ）");
    $this->importCsv($logger, $path);
  }

  /**
   * CSVをテンポラリ1へ取込み
   * @param unknown $logger
   * @param unknown $filePath
   * @throws RuntimeException
   */
  private function importCsv($logger, $filePath) {
    $fs = new FileSystem();

    if (!$fs->exists($filePath)) {
      throw new RuntimeException('no data file!! [' . $filePath . ']');
    }

    if (!$this->validateCsv($filePath)) {
      throw new RuntimeException('楽天レビューCSVの形式が不正です。');
    }

    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');
    $dbTmp->query("SET character_set_database=sjis;");

    $sql = <<<EOD
      LOAD DATA LOCAL INFILE '{$filePath}'
      INTO TABLE `tb_rakuten_reviews_tmp`
      FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
      LINES TERMINATED BY '\\r\\n' IGNORE 1 LINES
EOD;
    $dbTmp->query($sql);
  }

  /**
   * テンポラリ1に暫定で代表商品コードと購入日時を付与
   * 複数明細ある注文に紐づくレビューと、紐づく注文番号がなく、代表商品コードが取れていないレビューをピックアップしてテンポラリ2へ登録
   * @param unknown $logger
   */
  private function adjustTempData($logger) {
    $dbMain = $this->getDb('main');
    $dbMainName = $dbMain->getDatabase();
    $dbTmp = $this->getDb('tmp');

    // tmp上で代表商品コードと購入日時を補完（この時点の代表商品コードは、2件以上明細があると不正なものに紐づく場合がある。商品レビューは次でつけなおし）
    $sql = <<<EOD
      UPDATE tb_rakuten_reviews_tmp tmp
      INNER JOIN {$dbMainName}.tb_sales_detail_analyze AS A ON tmp.注文番号 = A.受注番号
      SET tmp.daihyo_syohin_code = A.daihyo_syohin_code
        , tmp.購入日時 = A.受注日;
EOD;
    $dbTmp->query($sql);

    // 複数明細ある注文をピックアップしてテンポラリ2へ登録
    $sql = <<<EOD
        INSERT INTO `tb_rakuten_reviews_multi_tmp`
        SELECT
            ID
          , レビュー詳細URL
        FROM (
          SELECT
            tmp.注文番号
          FROM tb_rakuten_reviews_tmp tmp
          JOIN {$dbMainName}.tb_sales_detail_analyze a ON tmp.注文番号 = a.受注番号
          WHERE tmp.レビュータイプ = '商品レビュー'
          GROUP BY tmp.注文番号
          HAVING count(*) >= 2
        ) pick
        JOIN tb_rakuten_reviews_tmp t2 ON pick.注文番号 = t2.注文番号 AND t2.レビュータイプ = '商品レビュー'
EOD;
    $dbTmp->query($sql);

    // 代表商品コードが取れていない注文をピックアップして登録
    $sql = <<<EOD
        REPLACE INTO `tb_rakuten_reviews_multi_tmp`
        SELECT
            ID
          , レビュー詳細URL
        FROM tb_rakuten_reviews_tmp
        WHERE daihyo_syohin_code = '' AND レビュータイプ = '商品レビュー'
EOD;
    $dbTmp->query($sql);
  }

  /**
   * テンポラリ1の代表商品コードを更新する
   */
  private function updateDaihyoSyohinCode($logger) {
    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');

    $sql = <<<EOD
      SELECT
        count(*) as count
      FROM tb_rakuten_reviews_multi_tmp
EOD;
    $stmt = $dbTmp->query($sql);
    $result = $stmt->fetch();

    $logger->info("レビュー再取得：代表商品更新対象レコード数：" . $result['count'] . ", 想定所要時間：" . $result['count'] * 2 . ' 秒');

    $sql = <<<EOD
      SELECT
          id
        , url
      FROM tb_rakuten_reviews_multi_tmp
      ORDER BY id
EOD;
    $stmt = $dbTmp->query($sql);

    while ($review = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $daihyoSyohinCode = '';
      try {
        $daihyoSyohinCode = $this->getDaihyoSyohinCodeFromProductReview($logger, $review['url']);
      } catch (\Exception $e) {
        // 取れないものがあってもエラーを吐いて握り潰し、continue
        $logger->error("レビュー再取得エラー：ID [" . $review['id'] . "]:" . $e->getMessage());
      }
      $sql = <<<EOD
        UPDATE tb_rakuten_reviews_tmp
        SET daihyo_syohin_code = :daihyoSyohinCode
        WHERE ID = :id
EOD;
      $stmt2 = $dbTmp->prepare($sql);
      $stmt2->bindValue(':daihyoSyohinCode', $daihyoSyohinCode); // 取れていない場合は空文字
      $stmt2->bindValue(':id', $review['id']);
      $stmt2->execute();

      // 終わったらテンポラリ2から削除
      $sql = <<<EOD
        DELETE FROM tb_rakuten_reviews_multi_tmp
        WHERE id = :id
EOD;
      $stmt2 = $dbTmp->prepare($sql);
      $stmt2->bindValue(':id', $review['id']);
      $stmt2->execute();
    }
  }

  /**
   * 本体のレビューテーブルとショップレビューを別名で構築
   * @param unknown $logger
   */
  function createNewReviews($logger) {
    $logger->info('本体レビューテーブルを構築');

    /** @var \Doctrine\DBAL\Connection $dbTmp */
    $dbTmp = $this->getDb('tmp');
    $dbTmpName = $dbTmp->getDatabase();
    $dbMain = $this->getDb('main');

    $dbMain->query("DROP TABLE IF EXISTS tb_rakuten_reviews_new"); // 新テーブルの名前は _new
    $dbMain->query("DROP TABLE IF EXISTS tb_rakuten_shop_reviews_new"); // 新テーブルの名前は _new
    $sql = <<<EOD
      CREATE TABLE `tb_rakuten_reviews_new` (
        `レビュータイプ` varchar(255) NOT NULL DEFAULT '',
        `商品名` varchar(255) NOT NULL DEFAULT '',
        `レビュー詳細URL` varchar(255) UNIQUE NOT NULL DEFAULT '' COMMENT 'レビューを一意に特定するにはこれを利用',
        `評価` tinyint(1) NOT NULL DEFAULT '0',
        `投稿時間` datetime NOT NULL,
        `タイトル` varchar(255) NOT NULL DEFAULT '',
        `レビュー本文` text,
        `フラグ` varchar(255) DEFAULT NULL,
        `注文番号` varchar(255) DEFAULT NULL,
        `daihyo_syohin_code` varchar(30) NOT NULL,
        `購入日時` datetime NOT NULL,
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `低評価通知済み` tinyint(4) NOT NULL DEFAULT '0',
        PRIMARY KEY (`ID`),
        KEY `daihyo_syohin_code` (`daihyo_syohin_code`),
        KEY `index_url` (`レビュー詳細URL`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbMain->query($sql);

    $sql = <<<EOD
      CREATE TABLE `tb_rakuten_shop_reviews_new` (
        `レビュータイプ` varchar(255) NOT NULL DEFAULT '',
        `商品名` varchar(255) DEFAULT NULL,
        `レビュー詳細URL` varchar(255) UNIQUE NOT NULL DEFAULT '' COMMENT 'レビューを一意に特定するにはこれを利用',
        `評価` tinyint(1) DEFAULT '0',
        `投稿時間` datetime NOT NULL,
        `タイトル` varchar(255) DEFAULT NULL,
        `レビュー本文` text,
        `フラグ` varchar(255) DEFAULT NULL,
        `注文番号` varchar(255) DEFAULT NULL,
        `daihyo_syohin_code` varchar(30) NOT NULL,
        `購入日時` datetime NOT NULL,
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `低評価通知済み` tinyint(4) NOT NULL DEFAULT '0',
        PRIMARY KEY (`ID`),
        KEY `daihyo_syohin_code` (`daihyo_syohin_code`),
        KEY `index_url` (`レビュー詳細URL`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbMain->query($sql);

    // 処理用にINDEXを追加
    $sql = <<<EOD
      ALTER TABLE tb_rakuten_reviews_tmp ADD INDEX index_review_time(`レビュータイプ`, `投稿時間`);
EOD;
    $dbTmp->query($sql);

    $sql = <<<EOD
      INSERT INTO tb_rakuten_reviews_new (
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
        {$dbTmpName}.tb_rakuten_reviews_tmp
      WHERE レビュータイプ = '商品レビュー'
      ORDER BY 投稿時間
EOD;
    $dbMain->query($sql);

    // インサート (shop_review)
    $sql = <<<EOD
      INSERT INTO tb_rakuten_shop_reviews_new (
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
      FROM {$dbTmpName}.tb_rakuten_reviews_tmp
      WHERE レビュータイプ <> '商品レビュー'
      ORDER BY 投稿時間
EOD;
    $dbMain->query($sql);
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

  /**
   * レビュー詳細画面から、代表商品コードを取得する。
   * レビュー詳細画面の、商品リンク部分のURLから代表商品コードを取得する。
   * @param unknown $logger
   * @param unknown $reviewUrl レビュー詳細画面のURL
   * @throws RuntimeException レビュー画面が表示できなかった、または商品詳細画面のURLが取得できなかった場合にthrowする
   */
  private function getDaihyoSyohinCodeFromProductReview($logger, $reviewUrl) {
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getContainer()->get('misc.util.web_access');

    $client = $webAccessUtil->getWebClient();
    sleep(2); // サーバ負荷調整のため2秒待つ
    $crawler = $client->request('GET',$reviewUrl);
    $response = $client->getResponse();
    $status = $response->getStatus();

    if ($status !== 200) {
      throw new RuntimeException("can't access review page. status[$status], url[$reviewUrl]");
    }

    $url = $crawler->filter('h2.revItemTtl a')->attr('href'); // 商品名のリンク先が商品詳細ページで、URLから代表商品コードが取得できる
    preg_match('/https:\/\/item.rakuten.co.jp\/plusnao\/(.*)\//', $url, $matches);
    if (count($matches) != 2) {
      throw new RuntimeException("can't get item page url. url:[$url]");
    }
    return $matches[1];
  }
}