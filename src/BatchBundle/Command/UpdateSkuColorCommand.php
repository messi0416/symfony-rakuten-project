<?php

namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSkuColorCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:update-sku-color')
      ->setDescription('SKU別カラー種別更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
    ;
  }

  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = 'SKU別カラー種別更新処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $dbMain = $this->getDoctrine()->getConnection('main');

    try {
      $dbMain->beginTransaction();
      $colorRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitemsColor');
      $colorRepo->updateSkuColor();

      $dbMain->commit();
    } catch (\Exception $e) {
      try {
        $dbMain->rollback();
      } catch (\Exception $e2) {
        $logger = $this->getLogger();
        $logger->info('SKU別カラー種別更新処理 rollbackでエラー: '. $e2->getMessage() . $e2->getTraceAsString());
      }
      throw $e;
    }
  }
}
