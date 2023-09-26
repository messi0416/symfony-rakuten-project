<?php

namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\Repository\TbProductchoiceitemsRakutenAttributeRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 SKU別楽天商品属性項目更新処理
 */
class UpdateSkuRakutenAttributeCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:update-sku-rakuten-attribute')
      ->setDescription('SKU別楽天商品属性項目更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('daihyo-syohin-code', null, InputOption::VALUE_OPTIONAL, '代表商品コード', '')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL, '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN);
  }

  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = 'SKU別楽天商品属性項目更新';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $daihyoSyohinCode = $input->getOption('daihyo-syohin-code');

    /** @var TbProductchoiceitemsRakutenAttributeRepository $aRepo */
    $aRepo = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitemsRakutenAttribute');
    $updateCount = $aRepo->autoUpsertSkuAttribute($daihyoSyohinCode, true);

    // 処理実行ログの登録
    $this->processExecuteLog->setProcessNumber1($updateCount);
    $this->processExecuteLog->setVersion(1.0);
  }
}
