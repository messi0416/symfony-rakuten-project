<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\FileUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Exception\RuntimeException;


class ExportCsvYahooUploadCommand extends ContainerAwareCommand
{
  /** @var  FileUtil */
  private $fileUtil;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-yahoo-upload')
      ->setDescription('upload Yahoo CSV and image(zip) files.')
      ->addArgument('data-dir', InputArgument::REQUIRED, 'アップロードCSVファイル格納ディレクトリ(ディレクトリ内の該当.csvを処理)')
      ->addOption('upload-target', null, InputOption::VALUE_REQUIRED, '対象店舗 plusnao|kawaemon|otoriyose')
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

    $logger->info('ヤフーCSV出力処理 アップロードを開始しました。');

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
    $logExecTitle = 'ヤフーCSV出力処理 アップロード';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

    try {
      // 対象ファイル格納ディレクトリ
      $dataDir = $input->getArgument('data-dir');
      $logger->info($dataDir);

      $fs = new FileSystem();
      if (!$dataDir || !$fs->exists($dataDir)) {
        throw new RuntimeException('no data dir!! [' . $dataDir . ']');
      }

      $target = $input->getOption('upload-target');
      $ftpConfig = $container->getParameter('ftp_yahoo');
      $config = isset($ftpConfig[$target]) ? $ftpConfig[$target] : null;
      if (
          !$target
        || !in_array($target, [
                ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO
              , ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON
              , ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE
           ])
        || !$config
      ) {
        throw new RuntimeException('unknown yahoo csv target : ' . $target);
      }

      $finder = new Finder();
      $files = $finder->in($dataDir)->files()->sortByName();
      if (! $files->count()) {
        throw new RuntimeException('CSVファイル・画像圧縮ファイルが出力されていませんでした。処理を終了します。 [' . $dataDir . ']');
      }

      $results = [];

      $finder = new Finder();
      $targetList = $finder->in($dataDir)->name($target)->directories();
      /** @var SplFileInfo $dir */
      foreach($targetList as $dir) {

        $target = $dir->getBasename();

        $logger->info('ヤフーCSV出力処理 アップロード: '. $target);

        $results[$target] = [];

        /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
        $ftp = $container->get('ijanki_ftp');
        $ftp->ssl_connect($config['host']);
        $ftp->login($config['user'], $config['password']);
        $ftp->pasv(true);
        $ftp->chdir('/');

        // YahooのFTP仕様により、ファイル名で取込順を指定できる。
        // 時刻は現在より未来の予定時刻を指定
        $baseDateTime = new \DateTime();
        $baseDateTime->modify('+5 minutes'); // 5分 余裕を見る

        // アップロード先 ファイル存在チェック
        $existsFiles = $ftp->nlist('/');
        if (!is_array($existsFiles)) {
          $e = new ExportCsvYahooUploadException(sprintf('[%s] FTP接続に失敗しました。処理を中止します。', $target));
          $e->setResults([
              'success' => $results
            , 'error' => [ $target => $existsFiles ]
          ]);
          throw $e;
        } else if (count($existsFiles)) {

          // もし、すでにアップロードされているファイルがあれば、今回のアップロードはその最終取込指定時間より後の時刻を指定。
          // ※ NextEngineからの在庫連携もこのFTPを利用している
          $maxDateTime = null;
          foreach($existsFiles as $fileName) {
            if (preg_match('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $fileName, $match)) {
              $date = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]));
              if (!$maxDateTime || ($maxDateTime < $date)) {
                $maxDateTime = $date;
              }
            }
          }

          if ($maxDateTime && ($maxDateTime > $baseDateTime)) {
            $baseDateTime = clone $maxDateTime;
            $baseDateTime->modify('+1 minutes');
          }
        }

        // ファイル名 付け替え

        // 1. 画像(.zip)
        $finder = new Finder();
        $files = $finder->in($dir->getPathname())->name('*.zip')->sortByName()->files();
        /** @var SplFileInfo $file */
        foreach($files as $file) {
          $baseDateTime->modify('+1 minutes'); // 1分ずらす
          $name = $file->getBasename();
          if (preg_match('/^img(\d+)\.zip$/', $name)) {
            $newPathFormat = $file->getPath() . '/img%s00.zip';

            $newPath = $this->renameFileWithDateTime($file->getPathname(), $newPathFormat, $baseDateTime);
            $logger->info(sprintf('rename file: %s => %s', $file->getPathname(), $newPath));
          }
        }
        // 2. data_add ※ 末尾は00 (Yahoo のマニュアル記載に従う)
        $finder = new Finder();
        $files = $finder->in($dir->getPathname())->name('data_add*.csv')->sortByName()->files();
        /** @var SplFileInfo $file */
        foreach($files as $file) {
          $baseDateTime->modify('+1 minutes'); // 1分ずらす
          $name = $file->getBasename();
          if (preg_match('/^data_add(\d+)\.csv/', $name)) {
            $newNameFormat = 'data_add%s00.csv';
            $newPathFormat = $file->getPath() . '/' . $newNameFormat;

            $newPath = $this->renameFileWithDateTime($file->getPathname(), $newPathFormat, $baseDateTime);
            $logger->info(sprintf('rename file: %s => %s', $file->getPathname(), $newPath));
          }
        }
        // 3. data_del ※ 末尾は00 (Yahoo のマニュアル記載に従う)
        $finder = new Finder();
        $files = $finder->in($dir->getPathname())->name('data_del*.csv')->sortByName()->files();
        /** @var SplFileInfo $file */
        foreach($files as $file) {
          $baseDateTime->modify('+1 minutes'); // 1分ずらす
          $name = $file->getBasename();
          if (preg_match('/^data_del(\d+)\.csv/', $name)) {
            $newNameFormat = 'data_del%s00.csv';
            $newPathFormat = $file->getPath() . '/' . $newNameFormat;

            $newPath = $this->renameFileWithDateTime($file->getPathname(), $newPathFormat, $baseDateTime);
            $logger->info(sprintf('rename file: %s => %s', $file->getPathname(), $newPath));
          }
        }
        // 4. quantity ※ 末尾は00 (Yahoo のマニュアル記載に従う)
        $finder = new Finder();
        $files = $finder->in($dir->getPathname())->name('quantity*.csv')->sortByName()->files();
        /** @var SplFileInfo $file */
        foreach($files as $file) {
          $baseDateTime->modify('+1 minutes'); // 1分ずらす
          $name = $file->getBasename();
          if (preg_match('/^quantity(\d+)\.csv/', $name)) {
            $newNameFormat = 'quantity%s00.csv';
            $newPathFormat = $file->getPath() . '/' . $newNameFormat;

            $newPath = $this->renameFileWithDateTime($file->getPathname(), $newPathFormat, $baseDateTime);
            $logger->info(sprintf('rename file: %s => %s', $file->getPathname(), $newPath));
          }
        }

        // アップロード処理
        $finder = new Finder();
        $files = $finder->in($dir->getPathname())->files();
        /** @var SplFileInfo $file */
        foreach($files as $file) {
          $ftp->put($file->getBasename(), $file->getPathname(), FTP_BINARY);
          $results[$target][] = $file->getBasename();
        }

        $ftp->close();
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($results));
      $logger->logTimerFlush();

      $logger->info('ヤフーCSV出力処理 アップロードを完了しました。');

      return 0;

    } catch (ExportCsvYahooUploadException $e) {

      // エラー情報
      $logger->error($e->getMessage());
      $logger->info(print_r($e->getResults(), true));
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation([ 'message' => $e->getMessage(), 'results' => $e->getResults()])
        , true, "ヤフーCSV出力処理 アップロードでエラーが発生しました。", 'error'
      );

      return 1;

    } catch (\Exception $e) {

      // エラー情報
      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "ヤフーCSV出力処理 アップロードでエラーが発生しました。", 'error'
      );

      return 1;
    }
  }


  /**
   * 時刻によるファイルリネーム
   * （自分自身を含めた）同一ファイル名が存在しなくなるまで1分ずつ加算してトライ
   * @param string $originalPath
   * @param string $newPathFormat
   * @param \DateTime $dateTime
   * @return string $newPath
   */
  private function renameFileWithDateTime($originalPath, $newPathFormat, $dateTime)
  {
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $dir = dirname($originalPath);
    $fs = new FileSystem();

    $newPath = null;
    if (!$fs->exists($originalPath) || !$fs->exists($dir) || !is_dir($dir)) {
      return $newPath;
    }

    $count = 0;
    $maxRetry = 20; // 20回やって無理なら流石に何かおかしい。
    do {
      $newPath = sprintf($newPathFormat, $dateTime->format('YmdHi'));
      try {
        if ($fs->exists($newPath)) {
          throw new \RuntimeException('path: ' . $newPath . ' is already exists. retrying...' );
        }

        $fs->rename($originalPath, $newPath);

      } catch (\Exception $e) {

        $logger->warning('failed to rename to exists filename ' . $newPath);

        $newPath = null;
        $dateTime->modify('+1 minutes'); // 1分加算
      }

    } while (!$newPath && ($count++ <= $maxRetry));

    if (!$newPath) {
      throw new \RuntimeException(sprintf('リネームできませんでした。 %s => %s (試行 %d 回)', $originalPath, sprintf($newPathFormat, $dateTime->format('YmdHi')), $count));
    }

    return $newPath;
  }

}

class ExportCsvYahooUploadException extends \RuntimeException
{
  protected $results = [];

  public function setResults($results)
  {
    $this->results = $results;
  }

  public function getResults()
  {
    return $this->results;
  }
};
