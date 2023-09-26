<?php

namespace BatchBundle\Command;

use BatchBundle\Job\NextEngineUploadJob;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;

/**
 * NextEngine CSVエクスポートの登録・更新のキュー追加処理。
 * アップロードディレクトリに存在する商品CSVと、セット商品CSVのアップロードキュー登録を行う。
 *
 * セット商品CSVは商品CSVが全て登録されてからアップロードされる必要があるため、以下の2つのタイミングでのみキュー登録を行う。
 * ・商品CSVが最後の1件である場合、そのあと
 * ・商品CSVが存在しない
 *
 * ファイルが大きく、処理に１時間ほどかかることと、その間在庫同期が止まってしまうため、
 * 1.5hを目安にアップするように変更。
 */
class ExportCsvNextEngineProductEnqueueCommand extends PlusnaoBaseCommand
{  
  // アップロード間隔（分）
  const WAIT_UPLOAD_MINUTE = 90;

  /** NextEngine側の環境 */
  private $targetEnv = null;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-next-engine-product-enqueue')
      ->setDescription('NextEngine CSVエクスポートの登録・更新のキュー追加処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, '対象のNE環境', 'test') // デフォルト test
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_NE_UPLOAD); // NEキューで呼び出し
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = 'NextEngine CSVエクスポートの登録・更新のキュー追加処理';
  }

  /**
   * ログの出力をキュー追加時のみにするため、executeをオーバーライド
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->initializeProcess($input);

    try {
      $this->doProcess($input, $output);

      return 0;

    } catch (\Throwable $t) {
      $logger = $this->getLogger();
      $logger->error($this->commandName . ':' . $t->getMessage() . $t->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog(null, 'エラー終了')->setInformation($t->getMessage() . ':' . $t->getTraceAsString())
          , true, $this->commandName . 'でエラーが発生しました。', 'error'
          );
      $this->updateProcessLogToError($t); // プロセス実行履歴　異常終了ログを登録
      $logger->logTimerFlush();
      return 1;
    }
  }

  /**
   * 処理本体。
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->setExecTitle($this->commandName);

    $dbMain = $this->getDb('main');

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    // 最終アップロード日時取得 ＆ 指定の分数経っていなければ終了
    $now = new \DateTime();
    $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE_PRODUCT_UPLOAD)->modify('+'.self::WAIT_UPLOAD_MINUTE.' minute');;
    
    if (!$lastUpdated || $lastUpdated > $now){
      return 0;
    }
    
    // ワークディレクトリ確認
    $currentDir = $this->getFileUtil()->getWebCsvDir() . '/NextEngine/' . ExportCsvNextEngineCommand::CURRENT_UPLOAD_DIRECTORY_NAME;
    $fs = new FileSystem();
    if (!$fs->exists($currentDir)) {
      $fs->mkdir($currentDir, 0755);
      return 0;
    }

    $finder = new Finder();
    $files = $finder->in($currentDir)->name('NE_Products*.csv')->files();
    $fileNum = $files->count();

    $rescue = $this->getResque();
    $count = 0; // アップロードした件数（商品で +1、セット商品で +1）

    // 引き続き、NextEngineへのアップロード
    // → 別Jobとして別のキューで呼び出す（=> 排他でのリトライを独立して行うため）
    // → ProductのみはこのJobで管理
    if ($fileNum > 0) {
      // プロセス実行履歴　開始ログを登録
      $this->insertProcessStartLog($input); 
      $this->initLogger($input);

      // 先頭１ファイルのみの追加
      // Finderの仕様上、Foreach～Breakを使用する
      foreach($files as $file) {
        $filePath = $file->getPath() . '/' . $file->getFilename();
        $job = new NextEngineUploadJob();

        $job->queue = 'neUpload'; // キュー名
        $job->args = [
            'command' => NextEngineUploadJob::COMMAND_KEY_NE_UPLOAD_PRODUCTS_AND_RESERVATIONS
          , 'dataDir' => $currentDir
          , 'file' => basename($filePath)
          , 'targetEnv' => $this->getTargetEnv($input)
        ];
        if ($this->account) {
          $job->args['account'] = $this->account->getId();
        }

        $rescue->enqueue($job);
        $logger->info('NextEngine CSVアップロード 登録・更新キュー追加 ファイルパス：'.$filePath);
        
        // 一個追加したら終了
        $count++;
        break;
      }
      
      // 最終アップロード日時更新
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE_PRODUCT_UPLOAD, new \DateTime());
    }

    // セット商品のアップロード こちらは今のところすぐ終わるようなので、最終アップロード日時を更新しない
    $setProductFilePath = $currentDir . '/' . ExportCsvNextEngineSetProductCommand::CSV_FILENAME_SET_PRODUCT;
    if ($fileNum <= 1 && file_exists($setProductFilePath)) {
      if ($count == 0) {
        // プロセス実行履歴　開始ログを登録
        $this->insertProcessStartLog($input);
        $this->initLogger($input);
      }

      $job = new NextEngineUploadJob();
      $job->queue = 'neUpload'; // キュー名
      $job->args = [
          'command' => NextEngineUploadJob::COMMAND_KEY_NE_UPLOAD_SET_PRODUCTS
          , 'filePath' => $setProductFilePath
          , 'targetEnv' => $this->getTargetEnv($input)
      ];
      if ($this->account) {
        $job->args['account'] = $this->account->getId();
      }
      $rescue = $this->getResque();
      $rescue->enqueue($job);
      $logger->info("NextEngineセット商品CSVアップロード キュー追加 ファイルパス： $setProductFilePath");
      $count++;
    }

    if ($count) {
      $logger->addDbLog($logger->makeDbLog(null, '終了'));
      $logger->info($this->commandName . 'を終了しました。');
      $this->updateProcessLogToFinish(); // プロセス実行履歴　正常終了ログを登録
      $logger->logTimerFlush();
    }
    return 0;
  }

  private function getTargetEnv($input) {
    if ($this->targetEnv != null) {
      return $this->targetEnv;
    }

    $env = $input->getOption('target-env');
    if ($env !== 'prod') {
      $this->targetEnv = 'test';
      $this->getLogger()->info('NextEngine CSVアップロードはテスト環境！');
    } else {
      $this->targetEnv = 'prod';
      $this->getLogger()->info('NextEngine CSVアップロードは本番環境！！！！！');
    }
    return $this->targetEnv;
  }

}
