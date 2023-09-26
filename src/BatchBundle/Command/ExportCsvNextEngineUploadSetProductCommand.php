<?php

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * NextEngineセット商品CSVアップロード。
 *
 * セット商品CSVを、スクレイピングを利用してNextEngineにアップロードする
 * @author a-jinno
 */
class ExportCsvNextEngineUploadSetProductCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
    ->setName('batch:export-csv-next-engine-set-product-upload')
    ->setDescription('NextEngineセット商品CSVアップロード')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN) // このまま記載すること
    ->addOption('file-path', null, InputOption::VALUE_OPTIONAL, 'アップロードファイルのフルパス', null)
    ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'NextEngineのターゲット環境', 'test') // 危険なのでデフォルトはtest
    ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = 'NextEngineセット商品CSVアップロード';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $filePath = $input->getOption('file-path');
    if (! file_exists($filePath)) {
      throw new \Exception($this->commandName . ": 指定されたファイルが存在しません。[$filePath]");
    }
    $logger = $this->getLogger();
    $file = new \SplFileInfo($filePath);
    
    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.next_engine');
    
    try {
      $ret = $mallProcess->apiUploadSetProductCsv($file);
      if ($ret['status'] == 'ok') {
        $logger->info($this->commandName . "ファイルアップロードを完了しました。");
      } else {
        $logger->error($this->commandName . ": ファイルアップロードでエラーが発生しました。" . print_r($ret, true));
        $logger->addDbLog(
          $logger->makeDbLog($this->commandName, 'エラー終了')->setInformation($ret)
          , true, "NextEngineセット商品CSVアップロードでエラーが発生しました。", 'error'
        );
      }
    } finally {
      // ここまで到達したら、成功でも失敗でもファイルは削除（無限ループ避け）
      $fs = new FileSystem();
      $fs->remove($filePath);
    }
  }
}
