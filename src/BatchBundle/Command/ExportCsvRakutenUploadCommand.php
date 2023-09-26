<?php
/**
 * 楽天 CSV出力処理アップロード
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\RakutenMallProcess;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\BatchLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use MiscBundle\Exception\BusinessException;


/**
 * 楽天CSV出力アップロード処理。
 *
 * 楽天plusnao、楽天motto-motto、楽天LaForest、楽天dolcissimo、楽天gekipla用のCSV出力アップロードを行う。
 * SFTPがキューを介すと何故かエラーになるので、暫定処置。
 * このコマンドを直接起動することで、キューを介さずにアップロードをすることが目的。
 * 本来、楽天CSV出力で、--do-upload=1（デフォルト）の形でエラーにならないなら、
 * このコマンドを使う必要はない。
 *
 * @package BatchBundle\Command
 */
class ExportCsvRakutenUploadCommand extends PlusnaoBaseCommand
{
  // 店舗ごとに固定のもの　initializeProcessで設定
  private $targetShop; // 対象店舗。rakuten|motto|laforest|dolcissimo|gekipla
  private $ftpParamRoot; // parametars.ymlに定義しているFTP設定のルートキー。ftp_rakuten|ftp_rakuten_motto|ftp_rakuten_laforest|ftp_rakuten_dolcissimo|ftp_rakuten_gekipla
  private $settingKeyPrefix; // tb_setting から値を取得するためのキーのプレフィックス。RAKUTEN|MOTTO|LAFOREST|DOLCISSIMO|GEKIPLA
  private $exportPath; // CSV出力ディレクトリ、パラメータで指定がなければ、$targetShopが含まれる
  private $results = [];

  const EXPORT_PATH = 'Rakuten/Export';

  /** 対象店舗文字列：楽天 */
  const EXPORT_TARGET_RAKUTEN = 'rakuten';

  /** 対象店舗文字列：motto-motto */
  const EXPORT_TARGET_MOTTO = 'motto';

  /** 対象店舗文字列：LaForest */
  const EXPORT_TARGET_LAFOREST = 'laforest';

  /** 対象店舗文字列：dolcissimo */
  const EXPORT_TARGET_DOLCISSIMO = 'dolcissimo';

  /** 対象店舗文字列：gekipla */
  const EXPORT_TARGET_GEKIPLA = 'gekipla';

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-rakuten-upload')
      ->setDescription('CSVエクスポート 楽天 アップロード')
      ->addArgument('export-dir', InputArgument::OPTIONAL, '出力先ディレクトリ', null)
      ->addOption('target-shop', null, InputOption::VALUE_OPTIONAL, '対象店舗。rakuten|motto|laforest|dolcissimo|gekipla')
      ->addOption('test', null, InputOption::VALUE_OPTIONAL, 'テスト実行かどうか。テストの場合、実際のアップロード処理はスキップ。', 0)
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '楽天CSV出力アップロード処理';
    $logger = $this->getLogger();

    $this->targetShop = $input->getOption('target-shop');
    if (!$this->targetShop) {
      $logger->addDbLog($logger->makeDbLog($this->commandName, 'エラー終了', '対象店舗指定なし'));
      throw new BusinessException("[楽天CSV出力アップロード処理]対象店舗指定なしのため処理終了");
    } else if ($this->targetShop == self::EXPORT_TARGET_RAKUTEN) {
      $this->commandName = '楽天CSV出力アップロード処理[楽天]';
      $this->ftpParamRoot = 'ftp_rakuten';
      $this->settingKeyPrefix = 'RAKUTEN';
    } else if ($this->targetShop == self::EXPORT_TARGET_MOTTO) {
      $this->commandName = '楽天CSV出力アップロード処理[motto]';
      $this->ftpParamRoot = 'ftp_rakuten_motto';
      $this->settingKeyPrefix = 'MOTTO';
    } else if ($this->targetShop == self::EXPORT_TARGET_LAFOREST) {
      $this->commandName = '楽天CSV出力アップロード処理[laforest]';
      $this->ftpParamRoot = 'ftp_rakuten_laforest';
      $this->settingKeyPrefix = 'LAFOREST';
    } else if ($this->targetShop == self::EXPORT_TARGET_DOLCISSIMO) {
      $this->commandName = '楽天CSV出力アップロード処理[dolcissimo]';
      $this->ftpParamRoot = 'ftp_rakuten_dolcissimo';
      $this->settingKeyPrefix = 'DOLCISSIMO';
    } else if ($this->targetShop == self::EXPORT_TARGET_GEKIPLA) {
      $this->commandName = '楽天CSV出力アップロード処理[gekipla]';
      $this->ftpParamRoot = 'ftp_rakuten_gekipla';
      $this->settingKeyPrefix = 'GEKIPLA';
    } else {
      $logger->addDbLog($logger->makeDbLog($this->commandName, 'エラー終了', "店舗指定不正[" . $this->targetShop . "]"));
      throw new BusinessException("[楽天CSV出力アップロード処理]対象店舗指定不正のため処理終了");
    }

    if ($input->getOption('test')) {
      $this->commandName .= '（テスト実行）';
    }
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    // 出力パス
    $this->exportPath = $input->getArgument('export-dir');
    if (!$this->exportPath) {
      $exportDir = $this->getFileUtil()->getWebCsvDir() . '/' . self::EXPORT_PATH . '/' . $this->targetShop;
      $finder = new Finder();

      // WEB_CSV/Rakuten/Export/[店舗]/ にある楽天CSV出力結果の最新のディレクトリを取得
      $finder->directories()->in($exportDir)->filter(function (\SplFileInfo $file) {
        // ディレクトリ名が 'YmdHis' 形式であることを確認
        return (
          preg_match('/^\d{14}$/', $file->getFilename()) === 1
          && preg_match('/^gold_/', $file->getFilename()) === 0
        );
      })->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
        return strcmp($b->getFilename(), $a->getFilename());
      });

      if ($finder->count() === 0) {
        throw new \RuntimeException(
          "楽天CSV出力結果のディレクトリがありません。[{$exportDir}]"
        );
      }

      $latestDirectory = null;
      foreach ($finder as $dir) {
        // $dirはSplFileInfoオブジェクトで、ディレクトリの詳細情報を取得するメソッドを提供します
        $latestDirectory = $dir->getFilename();
        // 最新のディレクトリのみを取得するため、最初のディレクトリ名を取得したらループを抜ける
        break;
      }

      $this->exportPath = $exportDir . '/' . $latestDirectory;
    }
    $logger->info('★exportPath: ' . $this->exportPath);
    $this->results['targetPath'] = $this->exportPath;

    // 出力ディレクトリ 作成
    $fs = new FileSystem();
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }

    $commonUtil = $this->getDbCommonUtil();

    try {
      /** @var RakutenMallProcess $processor */
      $processor = $this->getContainer()->get('batch.mall_process.rakuten');

      $ftpConfig = $this->getContainer()->getParameter($this->ftpParamRoot);
      $config = $ftpConfig['csv_upload'];

      // 開発環境はパスワード決め打ち
      $ftpPasswordSettingKey = $this->settingKeyPrefix . '_GOLD_FTP_PASSWORD';
      $config['password'] = $commonUtil->getSettingValue(
        $ftpPasswordSettingKey,
        $this->getEnvironment()
      );

      // アップロードするファイルの順序を定義
      $uploadOrder = [
        'item-warehouse.csv' => 'item.csv',
        'item-cat-\d{2}\.csv' => 'item-cat.csv',
        'item-2nd.csv' => 'item.csv',
        'item-3rd-\d{2}\.csv' => 'item.csv',
        'select-update-\d{2}\.csv' => 'select.csv',
        'item-cat-3rd-\d{2}\.csv' => 'item-cat.csv',
        'select-attention-del-\d{2}\.csv' => 'select.csv',
        'select-attention-add-\d{2}\.csv' => 'select.csv',
      ];

      // アップロード対象ファイル名取得
      $finder = new Finder();
      $finder->files()->in($this->exportPath);

      $filenames = [];
      foreach ($finder as $file) {
        $filenames[] = $file->getFilename();
      }

      $test = $input->getOption('test');
      // FTPアップロード ※空になるのを待つ
      foreach ($uploadOrder as $pattern => $remoteFileName) {
        // ループ中に配列から要素を削除すると、予期しない結果を引き起こす可能性があるのでキーを使用
        foreach(array_keys($filenames) as $key) {
          if (preg_match('/^' . $pattern . '$/', $filenames[$key])) {
            $logger->info('★uploadStart: ' . $this->exportPath . '/' . $filenames[$key]);
            if (!$test) {
              $processor->uploadCsv(
                $config,
                $this->exportPath . '/' . $filenames[$key],
                $config['path'] . '/' . $remoteFileName
              );
              $this->results['files'][] = $filenames[$key] . ' → ' . $remoteFileName;
            }
            // マッチしたファイル名を配列から削除
            unset($filenames[$key]);
          }
        }
      }

      $logTitle = $test ? 'アップロード対象パス表示' : 'アップロード結果';
      $logger->addDbLog(
        $logger->makeDbLog(null, $logTitle)->setInformation($this->results)
      );

    } catch (\Exception $e) {

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
  }
}
