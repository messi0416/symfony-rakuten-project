<?php
namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShoplistSpeedbinShipping;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbShoplistDailySalesRepository;
use MiscBundle\Entity\Repository\TbShoplistSpeedbinShippingRepository;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Exception\ValidationException;
use MiscBundle\Service\ShoplistSpeedbinService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理　SHOPLISTスピード便出荷数集計処理
 */
class AggregateShoplistSpeedbinDeliveryCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:aggregate-shoplist-speedbin-delivery')
      ->setDescription('SHOPLISTスピード便出荷数集計処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target-id', null, InputOption::VALUE_OPTIONAL, '処理対象のSHOPLISTスピード便出荷ID')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
    ;
  }

  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = 'SHOPLISTスピード便出荷数集計処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $this->validate($input);
    
    // 処理対象のEntityを取得
    /** @var TbShoplistSpeedbinShippingRepository $shoplistSpeedbinRepo */
    $shoplistSpeedbinRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShoplistSpeedbinShipping');
    $shoplistSpeedbinShipping = $shoplistSpeedbinRepo->find($input->getOption('target-id'));
    $em = $this->getDoctrine()->getManager('main');
    
    try {
      // 処理中に更新
      $shoplistSpeedbinShipping->setStatus(TbShoplistSpeedbinShipping::STATUS_ONGOING);
      
      // 集計基礎データを取得して登録
      /** @var TbShoplistDailySalesRepository $shoplistDailySalesRepo */
      $shoplistDailySalesRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShoplistDailySales');
      $latestSalesDataDate = $shoplistDailySalesRepo->findLatestOrderDate();
      $shoplistSpeedbinShipping->setShoplistSalesLatestDate($latestSalesDataDate);
      
      /** @var TbLocationRepository $locationRepo */
      $locationRepo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');
      $locations = $locationRepo->findLocationsByWarehouseId(TbWarehouseRepository::SHOPLIST_WAREHOUSE_ID); // 必ず1件取得
      $shoplistSpeedbinShipping->setSpeedbinStockImportDatetime(new \DateTime($locations[0]['updated']));
      $em->flush();
      
      $this->getLogger()->debug($this->commandName . ": 集計開始");
      
      // 集計実施
      /** @var ShoplistSpeedbinService $service */
      $service = $this->getContainer()->get('misc.service.shoplist_speedbin');
      $service->calculateSpeedbinDeliveryAmount($input->getOption('target-id'));
      
      $this->getLogger()->debug($this->commandName . ": 集計完了");
      
      // 終了に更新
      $shoplistSpeedbinShipping->setStatus(TbShoplistSpeedbinShipping::STATUS_DONE);
      $em->flush();
      
    } catch (\Exception $e) {
      $shoplistSpeedbinShipping->setStatus(TbShoplistSpeedbinShipping::STATUS_ERROR);
      $em->flush();
      throw $e;
    }
  }
  
  private function validate(InputInterface $input) {
    $targetId = $input->getOption('target-id');
    if (!$targetId) {
      throw new ValidationException("指定されたSHOPLISTスピード便出荷データは存在しません。ID = " . $input->getOption('target-id'));
    }
  }
}