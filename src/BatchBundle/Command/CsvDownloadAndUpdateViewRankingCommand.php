<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Goutte\Client;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;


class CsvDownloadAndUpdateViewRankingCommand extends ContainerAwareCommand
{
  /** @var  FileUtil */
  private $fileUtil;

  /** @var  SymfonyUsers */
  private $account;

  /** @var BatchLogger $logger */
  private $logger;

  protected function configure()
  {
    $this
      ->setName('batch:csv-download-and-update-view-ranking')
      ->setDescription('login to RMS(rakuten) Web site and download view ranking CSV file, and update DB.')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'prod')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('downloaded-data-dir', null, InputOption::VALUE_OPTIONAL, 'ダウンロード済みディレクトリ指定（ダウンロードをskip）');
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    $this->fileUtil = $this->getContainer()->get('misc.util.file');

    $this->logger = $this->getContainer()->get('misc.util.batch_logger');
    $logger = $this->logger;
    $logger->initLogTimer();

    $logger->info('閲覧ランキング更新処理を開始しました。');

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
    $logExecTitle = '閲覧ランキング取込処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    $rootDir = $container->get('kernel')->getRootDir();
    $dataDir = dirname($rootDir) . '/data';

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    try {

      $saveDir = null;

      // ディレクトリ指定があればダウンロードはスキップ
      $downloadedDir = $input->getOption('downloaded-data-dir');
      if (!$downloadedDir) {

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '開始'));

        // RMS ログイン
        $client = $webAccessUtil->getWebClient();
        $crawler = $webAccessUtil->rmsLogin($client, 'api'); // 必要なら、アカウント名を追加して切り替える

        // アクセス分析画面へ移動
        $nextUrl = 'https://mainmenu.rms.rakuten.co.jp/?left_navi=32';
        $crawler = $client->request('get', $nextUrl);

        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        $header = null;
        try {
          $header = $crawler->filter('#rmsTop-dashboard');
        } catch (\InvalidArgumentException $e) {
          // do nothing
        }
        if ($status !== 200 || !$header || strpos($header->text(), 'アクセス分析') === false ) {
          throw new RuntimeException('move to access analyze page error!! [' . $status . '][' . $uri . ']');
        }
        $logger->info('アクセス分析画面へ遷移成功');

        // 商品ページランキング画面へ移動
        $nextUrl = $crawler->selectLink('商品ページランキング')->attr('href');
        $logger->info($nextUrl);

        $crawler = $client->request('get', $nextUrl);

        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        // rdatatool の認証に失敗していれば、ここでエラー画面にリダイレクトされる。(2015/10/27時点の挙動)
        if ($status !== 200 || $uri != $nextUrl) {
          throw new RuntimeException('move to rdatatool page error!! [' . $status . '][' . $uri . ']');
        }

        // CSVファイル保存ディレクトリ
        $fs = new FileSystem();
        $rankingDir = $dataDir . '/view_ranking';
        if (!$fs->exists($rankingDir)) {
          $fs->mkdir($rankingDir, 0755);
        }

        $saveDir = $rankingDir . '/' . (new \DateTime())->format('YmdHis');
        if ($fs->exists($saveDir)) {
          throw new RuntimeException('duplicate save directory.');
        }
        $fs->mkdir($saveDir, 0755);


        // 各種類 取得
        $menus = ['pc', 'mobile', 'smp'];

        // 対象日付 （＝当日分のみ）
        $targetDate = new \DateTime();
        $targetDate->modify('-1 day');

        foreach($menus as $menu) {

          $form = null;
          try {
            $form = $crawler->selectButton('日次データ表示')->form();
          } catch (\InvalidArgumentException $e) {
            // do nothing
          }
          if (!$form ) {
            throw new RuntimeException('move to products ranking page error!! [' . $status . '][' . $uri . ']');
          }
          $logger->info('閲覧ランキング ダウンロード画面へ遷移成功 [' . $menu . ']');

          $form['menu'] = $menu;
          $form['y'] = $targetDate->format('Y');
          $form['m'] = $targetDate->format('m');
          $form['d'] = $targetDate->format('d');

          // 日付・メニュー選択決定
          $crawler = $client->submit($form);

          $response = $client->getResponse();
          $status = $response->getStatus();
          $uri = $client->getRequest()->getUri();
          if ($status !== 200) {
            throw new RuntimeException('ranking csv download error!! [' . $status . '][' . $uri . '][' . $menu . ']');
          }

          $downloadForm = null;
          try {
            $downloadForm = $crawler->selectButton('ダウンロードする')->form();
          } catch (\InvalidArgumentException $e) {
            // do nothing
          }
          if (!$downloadForm ) {
            throw new RuntimeException('move to products ranking page error (no download form)!! [' . $status . '][' . $uri . '][' . $menu . ']');
          }
          $logger->info('ダウンロードボタン取得 [' . $menu . ']');

          $crawler = $client->submit($downloadForm); // ここで 「ファイルをダウンロード中です。しばらくお待ちください。」のページになる
          $logger->info('ダウンロードボタン実行 [' . $menu . ']');

          $response = $client->getResponse();
          $status = $response->getStatus();
          $uri = $client->getRequest()->getUri();

          $contentType = $response->getHeader('Content-Type');
          $contentLength = intval($response->getHeader('Content-Length'));

          if ($status !== 200 || !$contentLength || strpos($response->getContent(), 'ファイルをダウンロード中です。しばらくお待ちください。') === false) {
            throw new RuntimeException('can not download csv error!! [' . $status . '][' . $uri . '][' . $response->getHeader('Content-Type') . ']');
          }

          // 自動ダウンロードはJavaScript？ ダウンロードフォームが付いているので利用させてもらう
          $downloadForm = null;
          try {
            $downloadForm = $crawler->selectButton('ダウンロードする')->form();
          } catch (\InvalidArgumentException $e) {
            // do nothing
          }
          if (!$downloadForm) {
            throw new RuntimeException('can not find download form!! [' . $status . '][' . $uri . '][' . $response->getHeader('Content-Type') . ']');
          }

          $logger->info('商品ランキング CSVダウンロードレスポンス取得 [' . $menu . ']');

          // CSVダウンロード 実行
          $client->submit($downloadForm);
          $logger->info('ダウンロードボタン実行（遷移後実行） [' . $menu . ']');

          $response = $client->getResponse();
          $status = $response->getStatus();
          $uri = $client->getRequest()->getUri();

          $contentType = $response->getHeader('Content-Type');
          $contentLength = intval($response->getHeader('Content-Length'));

          // ファイル保存
          $fileName = preg_match('/filename="([a-zA-Z0-9_-]+.csv)"/', $response->getHeader('Content-Disposition'), $match)
            ? $match[1]
            : sprintf('data%s.csv', date('YmdHis00000000'));


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
          $logger->info('商品ランキング CSV出力成功。[' . $path . ']');

          // DB記録＆通知処理
          // チェック機能のため、サブ2にファイル名、サブ3に行数、ファイルサイズを登録(JSON)
          $fileInfo = $this->fileUtil->getTextFileInfo($path);
          $info = [
              'type' => $menu
            , 'size' => $fileInfo['size']
            , 'lineCount' => $fileInfo['lineCount']
          ];

          $logger->addDbLog($logger->makeDbLog($logExecTitle, 'ランキングCSVダウンロード', 'ファイル保存[' . $menu . ']')->setInformation($info));

          // 前のページに戻る
          $backForm = $crawler->selectButton('前のページへ戻る')->form();
          $crawler = $client->submit($backForm);
        }

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSVダウンロード', '終了'));
      }

      // ====================================================
      // 取込処理を実行 （ダウンロードが早いので分割せず処理してしまう）
      // ====================================================
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '開始'));

      if (!$saveDir && $downloadedDir) {
        $saveDir = $downloadedDir;
      }

      $info = $this->importCsvData($saveDir);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'CSV取込処理', '終了')->setInformation($info));

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "閲覧ランキングCSV取込処理でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * ダウンロードCSV DB取込処理
   * @param string $saveDir
   * @return array
   */
  private function importCsvData($saveDir)
  {
    $logger = $this->logger;

    // 閲覧ランキング取込
    $fs = new FileSystem();
    $finder = new Finder();

    if (!$fs->exists($saveDir)) {
      throw new RuntimeException('no data dir!! [' . $saveDir . ']');
    }
    $files = $finder->in($saveDir)->name('*.csv')->files();
    if (! $files->count()) {
      throw new RuntimeException('no data files!! [' . $saveDir . ']');
    }

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
    $doctrine = $this->getContainer()->get('doctrine');
    $commonUtil = new DbCommonUtil($doctrine);

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $doctrine->getConnection('main');


    // 実行前 行数
    $countPre = $dbMain->fetchColumn('SELECT COUNT(*) FROM tb_viewranking');

    /** @var \SplFileInfo $file */
    foreach($files as $file) {

      $filePath = $file->getPath() . '/' . $file->getFilename();
      $logger->info('ファイル 開始 [' . $filePath . ']');

      // 一時テーブルを空に
      $dbMain->query('TRUNCATE tb_viewranking_dl');
      $dbMain->query('TRUNCATE tb_viewranking_tmp_pcsp');
      $dbMain->query('TRUNCATE tb_viewranking_tmp_mp');

      // '１行目が「"商品ページランキング"」
      // '６行目がCSVヘッダ行
      // 改行コード: LF
      // 文字コード: シフトJIS

      // 'CSVの内容が正しいかチェック

      if (! $this->validateCsv($filePath)) {
        throw new RuntimeException('閲覧ランキングのCSVファイルが正常に取得できていません。処理を中断します。');
      }

      $fileInfo = $this->convertCsvForImport($filePath);
      // $logger->info(print_r($fileInfo, true));

      // 日付
      $date = $fileInfo['headers'][2]; // lc_date
      if (preg_match('/(\d+)年(\d+)月(\d+)日/', $date, $match)) {
        $date = sprintf('%d-%d-%d', $match[1], $match[2], $match[3]);
      }

      // 媒体
      $career = $fileInfo['headers'][3];
      if ($career == 'PC') {
        $career = 'PC';
      } else if ($career == 'スマートフォン') {
        $career = 'SP';
      } else if ($career == 'モバイル') {
        $career = 'MP';
      }

      $tableName = '';
      switch ($career) {
        case 'PC': // fall through
        case 'SP':
          $tableName = 'tb_viewranking_tmp_pcsp';
          $sql = <<<EOD
            LOAD DATA LOCAL INFILE '{$fileInfo['tmp_path']}'
            INTO TABLE {$tableName}
            FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
            LINES TERMINATED BY '\\n' IGNORE 1 LINES
EOD;
          $dbMain->query($sql);
          break;

        case 'MP':
          $tableName = 'tb_viewranking_tmp_mp';
          $sql = <<<EOD
            LOAD DATA LOCAL INFILE '{$fileInfo['tmp_path']}'
            INTO TABLE {$tableName} FIELDS
            ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
            LINES TERMINATED BY '\\n' IGNORE 1 LINES
EOD;
          $dbMain->query($sql);

          // ranking カラム更新？
          $minId = $dbMain->fetchColumn('SELECT MIN(ID) AS min_id FROM ' . $tableName);
          $sql = "UPDATE `{$tableName}` SET RANKING = `ID` - :minId + 1";
          $stmt = $dbMain->prepare($sql);
          $stmt->bindValue(':minId', $minId, \PDO::PARAM_INT);
          $stmt->execute();
          break;

        default:
          throw new RuntimeException('unknown career.');
      }

      $sql = "DELETE FROM {$tableName} WHERE URL = '' OR URL IS NULL";
      $dbMain->query($sql);

      $sql = "DELETE FROM tb_viewranking WHERE キャリア = :career AND 年月日 = :date";
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':career', $career);
      $stmt->bindValue(':date', $date);
      $stmt->execute();

      if ($career == 'MP') {
        $sql = <<<EOD
          INSERT INTO tb_viewranking (
              `キャリア`
            , 年月日
            , ranking
            , `ページ名`
            , URL
            , `ページ種別`
            , `ページ区分`
            , `アクセス数`
            , `アクセス人数`
            , 売上件数
            , 売上
            , `ページ転換率`
            , `ページ客単価`
          )
          SELECT
              :career AS キャリア
            , :date AS 年月日
            , ranking
            , `ページ名`
            , URL
            , `ページ種別`
            , `ページ区分`
            , `アクセス数`
            , `アクセス人数`
            , 売上件数
            , 売上
            , `ページ転換率`
            , `ページ客単価`
          FROM
            {$tableName}
EOD;

      } else {

        $sql = <<<EOD
          INSERT INTO tb_viewranking (
             `キャリア`
            , 年月日
            , ranking
            , `ページ名`
            , URL
            , `ジャンル第1階層`
            , `ジャンル第2階層`
            , `ジャンル第3階層`
            , `ジャンル第4階層`
            , `ジャンル第5階層`
            , `カタログID`
            , `ページ種別`
            , `ページ区分`
            , `アクセス数`
            , `アクセス人数`
            , 売上件数
            , 売上
            , `ページ転換率`
            , `ページ客単価`
            , 平均滞在時間_秒
            , 離脱数
            , 離脱率
            , 男
            , 女
            , 性別不明
            , `-10`
            , `20`
            , `30`
            , `40`
            , `50`
            , `60+`
            , 年齢不明
            , D
            , P
            , G
            , S
            , R
            , 北海道
            , 東北
            , 関東
            , 北陸甲信越
            , 東海
            , 近畿
            , 中国
            , 四国
            , 九州
            , 国外
            , 地域不明
            , `レビュー数`
            , 総合評価
            , 総レビュー数
            , 評価1レビュー数
            , 評価2レビュー数
            , 評価3レビュー数
            , 評価4レビュー数
            , 評価5レビュー数
            , 性別不明レビュー数
            , 男性年齢不明レビュー数
            , 男性20代未満レビュー数
            , 男性20代レビュー数
            , 男性30代レビュー数
            , 男性40代レビュー数
            , 男性50代レビュー数
            , 男性60代以上レビュー数
            , 女性年齢不明レビュー数
            , 女性20代未満レビュー数
            , 女性20代レビュー数
            , 女性30代レビュー数
            , 女性40代レビュー数
            , 女性50代レビュー数
            , 女性60代以上レビュー数
            , 前日在庫
            , 在庫
            , 変動数
          )
          SELECT
              :career AS キャリア
            , :date AS 年月日
            , ranking
            , `ページ名`
            , URL
            , `ジャンル第1階層`
            , `ジャンル第2階層`
            , `ジャンル第3階層`
            , `ジャンル第4階層`
            , `ジャンル第5階層`
            , `カタログID`
            , `ページ種別`
            , `ページ区分`
            , `アクセス数`
            , `アクセス人数`
            , 売上件数
            , 売上
            , `ページ転換率`
            , `ページ客単価`
            , 平均滞在時間_秒
            , 離脱数
            , 離脱率
            , 男
            , 女
            , 性別不明
            , `-10`
            , `20`
            , `30`
            , `40`
            , `50`
            , `60+`
            , 年齢不明
            , D
            , P
            , G
            , S
            , R
            , 北海道
            , 東北
            , 関東
            , 北陸甲信越
            , 東海
            , 近畿
            , 中国
            , 四国
            , 九州
            , 国外
            , 地域不明
            , `レビュー数`
            , 総合評価
            , 総レビュー数
            , 評価1レビュー数
            , 評価2レビュー数
            , 評価3レビュー数
            , 評価4レビュー数
            , 評価5レビュー数
            , 性別不明レビュー数
            , 男性年齢不明レビュー数
            , 男性20代未満レビュー数
            , 男性20代レビュー数
            , 男性30代レビュー数
            , 男性40代レビュー数
            , 男性50代レビュー数
            , 男性60代以上レビュー数
            , 女性年齢不明レビュー数
            , 女性20代未満レビュー数
            , 女性20代レビュー数
            , 女性30代レビュー数
            , 女性40代レビュー数
            , 女性50代レビュー数
            , 女性60代以上レビュー数
            , 前日在庫
            , 在庫
            , 変動数
          FROM
            {$tableName}
EOD;

      }

      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':career', $career);
      $stmt->bindValue(':date', $date);
      $stmt->execute();

      $logger->info('閲覧ランキング ファイル取込 [' . $career . ']');
    }

    // '売上の補正
    $sql = "UPDATE tb_viewranking SET 売上 = 0 WHERE 売上 = '-'"; // FIXME （旧実装ママ） 取り込む前に変換してはダメなの？
    $dbMain->query($sql);

    // '代表商品コードをセット // FIXME （旧実装ママ） 取り込む前に変換してはダメなの？
    $sql = <<<EOD
      UPDATE tb_viewranking
      SET
        daihyo_syohin_code = REPLACE(
          REPLACE(URL, 'https://item.rakuten.co.jp/plusnao/', '')
          , '/'
          , ''
        )
      WHERE
            daihyo_syohin_code = ''
        AND URL LIKE '%https://item.rakuten.co.jp/plusnao/%'
EOD;
    $dbMain->query($sql);

    // '代表商品コードをセット // FIXME （旧実装ママ） 取り込む前に変換してはダメなの？
    $sql = <<<EOD
      UPDATE tb_viewranking
      SET daihyo_syohin_code = REPLACE(
          REPLACE(url, 'http://m.rakuten.co.jp/plusnao/n/', '')
          , '/'
          , ''
        )
      WHERE
            daihyo_syohin_code = ''
        AND url LIKE '%http://m.rakuten.co.jp/plusnao/n/%'
EOD;
    $dbMain->query($sql);

    // 最終更新日時をセット
    $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_VIEW_RANKING);

    // ファイル削除
    try {
      $finder = new Finder();
      $files = $finder->in($saveDir)->files(); // *.csv および 一時ファイルもすべて削除
      $fs->remove($files);
      $fs->remove($saveDir);
    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      // 握りつぶす
    }

    // 実行後 行数
    $countPost = $dbMain->fetchColumn('SELECT COUNT(*) FROM tb_viewranking');

    $info = [
        'pre'  => $countPre
      , 'post' => $countPost
      , 'diff' => $countPost - $countPre
    ];

    return $info;
  }


  /**
   * CSVデータ書式チェック
   */
  private function validateCsv($path)
  {
    // 一行目で判定
    $validLine = '"商品ページランキング"';

    $fp = fopen($path, 'r');
    $line = fgets($fp);
    fclose($fp);

    return (trim(mb_convert_encoding($line, 'UTF-8', 'SJIS-WIN')) === $validLine);
  }

  /**
   * ヘッダを除去したCSVを作成する
   * また、文字列置換があるため UTF-8にしてから処理を行う
   *
   * @param string $path
   * @return array
   */
  private function convertCsvForImport($path)
  {
    $result = [
        'tmp_path' => null
      , 'headers' => []
    ];

    // 一時ファイルパス
    $tmpPath = tempnam(dirname($path), 'tmp_');

    $fp = fopen($path, 'r');
    $fpOut = fopen($tmpPath, 'w');

    $lineNum = 0;
    while(($line = fgets($fp)) !== false) {

      $lineNum++;
      $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-WIN');

      // 5行目までは付加情報
      if ($lineNum <= 5) {
        $result['headers'][] = trim(str_replace('"', '', $line));
        continue;

      // 6行目がCSVヘッダ。謎の変換
      } else if ($lineNum == 6) {
        $line = str_replace('#', 'ranking', $line);
        $line = str_replace('平均滞在時間(秒)', '平均滞在時間_秒', $line);
        $line = str_replace('在庫(個)', '在庫_個', $line);
        $line = str_replace('売上個数(個)', '売上個数_個', $line);
        $line = str_replace('売上高(円)', '売上高_円', $line);
      }

      fputs($fpOut, $line);
    }

    fclose($fp);
    fclose($fpOut);

    $result['tmp_path'] = $tmpPath;

    return $result;
  }

}
