<?php
/**
 * 商品画像 存在・名前チェック処理
 * ※相当に時間がかかるため、日次処理？
 *
 * 加工済み画像ディレクトリ: /home/workuser/product_images
 * オリジナル画像ディレクトリ: /home/workuser/product_images_original
 *
 * FPTアクセス ... オリジナル画像ディレクトリ
 * チェック対象 ... 加工済み画像ディレクトリ
 * 自動アップロード対象: 加工済み画像ディレクトリ
 *
 * ※オリジナル画像ディレクトリ内の各ファイルについて、ファイル更新日時を基準に加工・更新。
 *   また、オリジナル画像ディレクトリに存在しない画像は加工済み画像ディレクトリから削除
 *
 *    TODO : 楽天・Yahoo からの画像削除処理 仕様検討・実装
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Util\MultiInsertUtil;
use MiscBundle\Util\StringUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;

class ProductImageCheckCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results = [
      'DB未登録削除'       => []
    , '新規・更新ファイル' => []
    , '楽天から取得'       => []
    , 'DB未登録削除(Amazon)' => []

    // 以下、イレギュラー
    , '楽天から取得失敗'   => []
    , 'オリジナル紛失'     => []
    , 'ファイル紛失(Amazon)' => []
  ];

  protected function configure()
  {
    $this
      ->setName('batch:product-image-check')
      ->setDescription('商品画像チェック処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    $logExecTitle = '商品画像チェック処理';
    $logger->info($logExecTitle . 'を開始しました。');

    // 本番サーバでテスト環境起動をしてしまわない様にブロック（違うテーブルを元に画像削除等が行われてしまう）
    $env = $this->getApplication()->getKernel()->getEnvironment();
    if ($env === 'test' &&  !file_exists('/this_is_dev_server')) {
      $message = 'このタスクは本番サーバで env=test で実行することはできません。';
      $logger->error($message);
      $output->writeln($message);
      return 1;
    }

    $logger->setExecTitle($logExecTitle);

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
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    try {

      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->getDbCommonUtil();

      /** @var ImageUtil $imageUtil */
      $imageUtil = $this->getContainer()->get('misc.util.image');

      // 最終画像チェック日時 取得＆更新
      $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_CHECK);
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_CHECK);

      // 0. 商品画像一覧テーブル 作成
      $logger->info('0. 商品画像一覧テーブル 作成');
      $imageUtil->createProductImagesTable();

      // 1. DB未登録画像 削除処理
      $logger->info('1. DB未登録画像 削除処理');
      $this->deleteNonRegisteredImages();

      // 2. DBにありファイルがない画像 楽天からダウンロード
      $logger->info('2. DBにありファイルがない画像 楽天からダウンロード');
      $this->downloadNonExistsImages();

      // 3. 更新ファイル 加工＆コピー処理（ product_images_original => product_images へ）
      // ※WEBからのアップロードのみになったため不要。
      $logger->info('3. 更新ファイル 加工＆コピー処理 -> WEBからのアップロードのみになったため不要。');

      // 4. 不要加工済みファイル 削除処理 ( product_images_original に存在しないファイルを product_images から削除 ）
      $logger->info('4. 不要加工済みファイル 削除処理');
      $this->deleteMissingImages();

      // 5. 類似画像チェック用文字列作成処理
      //  この処理では、アップロードに関わりを持たせないためupdatedを更新しない。（ここまで来ると、更新日時カラムは分けるべきだった）
      $logger->info('5. 類似画像チェック用文字列作成処理 -> 類似画像チェック削除により不要。');

      // 6. Amazon画像チェック
      $logger->info('6. Amazon画像チェック');
      $this->checkAmazonImages();

      // 7. 結果出力、通知処理（画像がないなどイレギュラーはRedmine チケット作成）
      $env = $this->getApplication()->getKernel()->getEnvironment();
      $logger->info('7. 結果出力、通知処理（イレギュラーの場合には Redmine チケット作成');
      if (
           (
                count($this->results['楽天から取得失敗'])
             || count($this->results['オリジナル紛失'])
             || count($this->results['ファイル紛失(Amazon)'])
           )
           && $env == 'prod' // 本番環境のみ
      ) {

        $now = new \DateTime();

        /** @var WebAccessUtil $webAccessUtil */
        $webAccessUtil = $container->get('misc.util.web_access');
        $ticket = [
          'issue' => [
              'subject'         => sprintf('[画像存在チェック] %s : 画像ファイルまたはアドレスを確認してください', $now->format('Y-m-d H:i'))
            , 'project_id'      => $container->getParameter('redmine_create_error_ticket_project')
            , 'priority_id'     => $container->getParameter('redmine_create_error_ticket_priority')
            , 'description'     => json_encode($this->results, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
            , 'assigned_to_id'  => $container->getParameter('redmine_create_error_ticket_user')
            , 'tracker_id'      => $container->getParameter('redmine_create_error_ticket_tracker')
            // , 'category_id'     => ''
            // , 'status_id'       => ''
          ]
        ];

        $webAccessUtil->requestRedmineApi('POST', '/issues.json', $ticket);
      }

      // 処理完了
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('商品画像チェック処理 完了');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation(['error' => $e->getMessage(), 'results' => $this->results])
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * 6. Amazon画像チェック
   * * データにないファイルを削除
   * * データがあってファイルが存在しない画像はイレギュラー
   */
  private function checkAmazonImages()
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();
    $container = $this->getContainer();

    // ファイル一覧一時テーブル作成
    $amazonImageDir = $container->getParameter('product_image_amazon_dir');
    $this->createAmazonImageFileListTemporaryTable($amazonImageDir, 'tmp_work_product_images_amazon_exist_files');

    // DBに登録がないファイルを削除
    $fs = new FileSystem();
    $sql = <<<EOD
      SELECT
          E.path
      FROM tmp_work_product_images_amazon_exist_files E
      LEFT JOIN product_images_amazon I ON (E.directory = I.directory AND E.filename = I.filename)
      WHERE I.daihyo_syohin_code IS NULL
EOD;
    $stmt = $dbMain->query($sql);
    while($file = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $logger->info('delete unregistered image: ' . $file['path']);
      $fs->remove($file['path']);
      $this->results['DB未登録削除(Amazon)'][] = $file['path'];
    }

    // product_images_original_exist_files 更新
    $sql = <<<EOD
      DELETE E
      FROM product_images_original_exist_files E
      LEFT JOIN product_images I ON (E.directory = I.directory AND E.filename = I.filename)
      WHERE I.daihyo_syohin_code IS NULL
EOD;
    $dbMain->query($sql);

    // DBに登録があり、ファイルが存在しない画像をリストアップ
    $sql = <<<EOD
      SELECT
          I.daihyo_syohin_code
        , I.address
        , I.filename
      FROM product_images_amazon I
      LEFT JOIN tmp_work_product_images_amazon_exist_files E ON (I.directory = E.directory AND I.filename = E.filename)
      WHERE E.filename IS NULL
EOD;
    $stmt = $dbMain->query($sql);
    while($file = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $logger->info(sprintf('missing image: %s => %s', $file['daihyo_syohin_code'], $file['filename']));
      $this->results['ファイル紛失(Amazon)'][] = sprintf('%s : %s', $file['daihyo_syohin_code'], $file['filename']);
    }

    return;
  }


  /**
   * 5. 類似画像チェック用文字列作成処理
   *    ※こちらも、FTPアップロードがなくなったため補助的な処理（漏れ対応程度）
   */
  // -> 削除

  /**
   * 4. 不要加工済みファイル 削除処理 ( product_images_original に存在しないファイルを product_images から削除 ）
   */
  private function deleteMissingImages()
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();

    $fs = new FileSystem();
    $sql = <<<EOD
      SELECT
          E.path
      FROM product_images_exist_files E
      WHERE NOT EXISTS (
        SELECT * FROM product_images_original_exist_files OE
        WHERE OE.directory = E.directory
          AND OE.filename = E.filename
      )
EOD;
    $stmt = $dbMain->query($sql);
    while($file = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $logger->info('delete original-less image: ' . $file['path']);
      $fs->remove($file['path']);

      $this->results['オリジナル紛失'][] = $file['path'];
    }
  }


  /**
   * 2. DBにありファイルがない画像 楽天からダウンロード
   */
  private function downloadNonExistsImages()
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getContainer()->get('misc.util.web_access');
    $client = $webAccessUtil->getWebClient();

    $originalImageDir = $this->getContainer()->getParameter('product_image_original_dir');

    // ファイルがない画像について、楽天からダウンロードを試みる
    // そもそもがイレギュラーな状態なので、DB更新は一括でできるとみなす。
    // 一括登録
    $insertBuilder = new MultiInsertUtil("product_images_original_exist_files", [
      'fields' => [
          'directory' => \PDO::PARAM_STR
        , 'filename' => \PDO::PARAM_STR
        , 'path' => \PDO::PARAM_STR
      ]
    ]);

    $fs = new FileSystem();
    $sql = <<<EOD
      SELECT
           directory
         , filename
      FROM product_images I
      WHERE NOT EXISTS (
        SELECT * FROM product_images_original_exist_files E
        WHERE E.directory = I.directory
          AND E.filename = I.filename
      )
EOD;
    $stmt = $dbMain->query($sql);
    while($file = $stmt->fetch(\PDO::FETCH_ASSOC)) {

      $url = sprintf('https://image.rakuten.co.jp/plusnao/cabinet/%s/%s', $file['directory'], $file['filename']);
      $path = sprintf('%s/%s/%s', $originalImageDir, $file['directory'], $file['filename']);
      $logger->info(sprintf('download image from rakuten: %s => %s', $url, $path));

      try {
        $client->request('GET', $url);
        /** @var Response $response */
        $response = $client->getResponse();
        if ($response->getStatus() == 200) {
          $logger->info(sprintf('download image from rakuten SUCCESS: %s => %s', $url, $path));
          $this->results['楽天から取得'][] = sprintf('%s/%s', $file['directory'], $file['filename']);

          if (!$fs->exists(dirname($path))) {
            $fs->mkdir(dirname($path));
          }

          file_put_contents($path, $response->getContent());

          // product_images_original_exist_files 格納
          $item = [
              'directory'     => $file['directory']
            , 'filename'      => $file['filename']
            , 'path'          => $path
          ];

          $insertBuilder->bindRow($item);

        } else {
          throw new \RuntimeException('catch me!!');
        }
      } catch (\Exception $e) {
        $logger->info(sprintf('download image from rakuten ERROR: %s => %s', $url, $path));
        $this->results['楽天から取得失敗'][] = sprintf('%s', $url);
      }
    }
  }

  /**
   * 1. DB未登録画像 削除(オリジナル、加工済み 双方削除)
   */
  private function deleteNonRegisteredImages()
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();
    $container = $this->getContainer();

    // ファイル一覧一時テーブル作成
    $imageDir = $container->getParameter('product_image_dir');
    $originalImageDir = $container->getParameter('product_image_original_dir');

    $this->createFileListTemporaryTable($imageDir, 'product_images_exist_files');
    $this->createFileListTemporaryTable($originalImageDir, 'product_images_original_exist_files');

    // DBに登録がないファイルを削除（オリジナル）
    // ※ unchecked 画像は除外
    $fs = new FileSystem();
    $sql = <<<EOD
      SELECT
          E.path
      FROM product_images_original_exist_files E
      LEFT JOIN product_images I ON (E.directory = I.directory AND E.filename = I.filename)
      WHERE I.daihyo_syohin_code IS NULL
EOD;
    $stmt = $dbMain->query($sql);
    while($file = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $logger->info('delete unregistered image: ' . $file['path']);
      $fs->remove($file['path']);
      $this->results['DB未登録削除'][] = $file['path'];
    }

    // product_images_original_exist_files 更新
    $sql = <<<EOD
      DELETE E
      FROM product_images_original_exist_files E
      LEFT JOIN product_images I ON (E.directory = I.directory AND E.filename = I.filename)
      WHERE I.daihyo_syohin_code IS NULL
EOD;
    $dbMain->query($sql);

    // DBに登録がないファイルを削除（加工済み）
    $fs = new FileSystem();
    $sql = <<<EOD
      SELECT
          E.path
      FROM product_images_exist_files E
      LEFT JOIN product_images I ON (E.directory = I.directory AND E.filename = I.filename)
      WHERE I.daihyo_syohin_code IS NULL
EOD;
    $stmt = $dbMain->query($sql);
    while($file = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $fs->remove($file['path']);
    }

    // product_images_exist_files 更新
    $sql = <<<EOD
      DELETE E
      FROM product_images_exist_files E
      LEFT JOIN product_images I ON (E.directory = I.directory AND E.filename = I.filename)
      WHERE I.daihyo_syohin_code IS NULL
EOD;
    $dbMain->query($sql);
  }


  /**
   * ファイル一覧テーブル作成
   * TODO 一時テーブルに変更
   *
   * @param string $parentDirName 親ディレクトリ名
   * @param string $tableName テーブル名
   */
  private function createFileListTemporaryTable($parentDirName, $tableName)
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();

    $dbMain->query("DROP TABLE IF EXISTS {$tableName}");
    $sql = <<<EOD
      CREATE TABLE IF NOT EXISTS {$tableName} (
          directory VARCHAR(20) not null default ''
        , filename VARCHAR(50) not null default ''
        , path VARCHAR(255) not null default ''
        , phash VARCHAR(16) NOT NULL DEFAULT ''
        , PRIMARY KEY (`directory`, `filename`)
      ) ENGINE=InnoDB CHARSET=utf8 COLLATE utf8_bin COMMENT '画像パス格納一時テーブル [{$parentDirName}]'
EOD;
    $dbMain->query($sql);

    // ファイル一覧テーブル挿入
    $builder = new ProcessBuilder(array('find', $parentDirName, '-type', 'f'));

    $process = $builder->getProcess();
    $process->setTimeout(3600);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    $files = explode("\n", $process->getOutput());
    $count = 0;
    foreach ($files as $path) {
      if (!strlen($path)) {
        continue;
      }

      // unchecked ディレクトリを除外
      if (strpos($path, 'unchecked') !== FALSE) {
        continue;
      }

      $parts = explode('/', $path);
      if (count($parts) != 6) {
        $logger->warning(sprintf('[%s][%s] invalid path? : %s', $parentDirName, $tableName, $path));
        continue;
      }

      $fileName = array_pop($parts);
      $dirName = array_pop($parts);
      if (!$fileName || !$dirName) {
        $logger->warning(sprintf('[%s][%s] invalid path? : %s', $parentDirName, $tableName, $path));
        continue;
      }

      if (!isset($insertBuilder)) {
        // 一括登録
        $insertBuilder = new MultiInsertUtil($tableName, [
          'fields' => [
              'directory'     => \PDO::PARAM_STR
            , 'filename'      => \PDO::PARAM_STR
            , 'path'          => \PDO::PARAM_STR
          ]
          , 'prefix' => 'INSERT IGNORE INTO'
        ]);
      }

      $item = [
          'directory'     => $dirName
        , 'filename'      => $fileName
        , 'path'          => $path
      ];

      $insertBuilder->bindRow($item);

      // 分割 INSERT (1000件ずつ)
      if (++$count >= 1000) {
        if (count($insertBuilder->binds())) {
          $stmt = $dbMain->prepare($insertBuilder->toQuery());
          $insertBuilder->bindValues($stmt);
          $stmt->execute();
        } else {
          $logger->info($tableName . ': no bind data. something wrong ... ?');
        }

        unset($insertBuilder);
        $count = 0;
      }
    }
    // INSERT 残り
    if ($count && isset($insertBuilder) && count($insertBuilder->binds())) {
      $stmt = $dbMain->prepare($insertBuilder->toQuery());
      $insertBuilder->bindValues($stmt);
      $stmt->execute();
    }
  }


  /**
   * ファイル一覧テーブル作成（Amazon画像 ※平置きなので別メソッド）
   *
   * @param string $parentDirName 親ディレクトリ名
   * @param string $tableName テーブル名
   */
  private function createAmazonImageFileListTemporaryTable($parentDirName, $tableName)
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();

    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS {$tableName}");
    $sql = <<<EOD
      CREATE TEMPORARY TABLE IF NOT EXISTS {$tableName} (
          directory VARCHAR(20) not null default ''
        , filename VARCHAR(50) not null default ''
        , path VARCHAR(255) not null default ''
        , phash VARCHAR(16) NOT NULL DEFAULT ''
        , PRIMARY KEY (`directory`, `filename`)
      ) ENGINE=InnoDB CHARSET=utf8 COLLATE utf8_bin COMMENT '画像パス格納一時テーブル [{$parentDirName}]'
EOD;
    $dbMain->query($sql);

    // ファイル一覧テーブル挿入
    $builder = new ProcessBuilder(array('find', $parentDirName, '-type', 'f'));

    $process = $builder->getProcess();
    $process->setTimeout(3600);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    $files = explode("\n", $process->getOutput());
    $count = 0;
    foreach ($files as $path) {
      if (!strlen($path)) {
        continue;
      }

      $parts = explode('/', $path);
      if (count($parts) != 6) {
        $logger->warning(sprintf('[%s][%s] invalid path? : %s => %d', $parentDirName, $tableName, $path, count($parts)));
        continue;
      }

      $fileName = array_pop($parts);
      $dirName = array_pop($parts);
      if (!$fileName || !$dirName) {
        $logger->warning(sprintf('[%s][%s] invalid filename ? : %s => %s', $parentDirName, $tableName, $path, $fileName));
        continue;
      }

      if (!isset($insertBuilder)) {
        // 一括登録
        $insertBuilder = new MultiInsertUtil($tableName, [
          'fields' => [
              'directory'     => \PDO::PARAM_STR
            , 'filename'      => \PDO::PARAM_STR
            , 'path'          => \PDO::PARAM_STR
          ]
          , 'prefix' => 'INSERT IGNORE INTO'
        ]);
      }

      $item = [
          'directory'     => $dirName
        , 'filename'      => $fileName
        , 'path'          => $path
      ];

      $insertBuilder->bindRow($item);

      // 分割 INSERT (1000件ずつ)
      if (++$count >= 1000) {
        if (count($insertBuilder->binds())) {
          $stmt = $dbMain->prepare($insertBuilder->toQuery());
          $insertBuilder->bindValues($stmt);
          $stmt->execute();
        } else {
          $logger->info($tableName . ': no bind data. something wrong ... ?');
        }

        unset($insertBuilder);
        $count = 0;
      }
    }
    // INSERT 残り
    if ($count && isset($insertBuilder) && count($insertBuilder->binds())) {
      $stmt = $dbMain->prepare($insertBuilder->toQuery());
      $insertBuilder->bindValues($stmt);
      $stmt->execute();
    }
  }


}
