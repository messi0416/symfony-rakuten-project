<?php
/**
 * SHOPLISTスピード便移動伝票作成バッチ
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Service\TransportListService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTransportListForShoplistSpeedBinCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:create-transport-list-shoplist-speed-bin')
      ->setDescription('SHOPLISTスピード便移動伝票作成')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('departure-date', null, InputOption::VALUE_OPTIONAL, '出発日')
      ->addOption('arrival-date', null, InputOption::VALUE_OPTIONAL, '到着日')
      ->addOption('shipping-method', null, InputOption::VALUE_OPTIONAL, '発送方法')
      ->addOption('transport-number', null, InputOption::VALUE_OPTIONAL, '移動コード')
      ->addOption('upload-filepath', null, InputOption::VALUE_REQUIRED, '確定ファイルパス')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = 'SHOPLISTスピード便移動伝票作成';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $this->setInput($input);
    $this->setOutput($output);
    $departureDate = strval($input->getOption('departure-date'));
    $arrivalDate = strval($input->getOption('arrival-date'));
    $shippingMethod = strval($input->getOption('shipping-method'));
    $transportNumber = strval($input->getOption('transport-number'));
    $uploadFilepath = strval($input->getOption('upload-filepath'));
    
    // メイン処理
    /** @var TransportListService $service */
    $service = $this->getContainer()->get('misc.service.transport_list'); 

    /** @var SymfonyUsers $account */
    $account = $this->account;
    $service->createShoplistTransportList($departureDate, $arrivalDate, $shippingMethod, $transportNumber, $uploadFilepath, $account->getUserName()); 
  }
}
