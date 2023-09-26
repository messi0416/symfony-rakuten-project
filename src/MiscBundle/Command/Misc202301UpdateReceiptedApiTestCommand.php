<?php
namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Service\ShippingVoucherService;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Misc202301UpdateReceiptedApiTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
    ->setName('misc:misc-2023-update-receipted-api-test')
    ->setDescription('NextEngine納品書印刷済み更新APIテスト。CSV生成とステータス更新のテストを行う')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('voucher-number',  null, InputOption::VALUE_OPTIONAL, '対象の伝票番号（カンマ区切り）')
    ->addOption('skip-update',  null, InputOption::VALUE_OPTIONAL, '更新を行わず、取得とCSV出力のみを行う場合は1', 1)
    ;
  }
  
  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    /** @var ShippingVoucherService $service */
    $service = $this->getContainer()->get('misc.service.shipping_voucher');
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getFileUtil();
    $commonUtil = $this->getDbCommonUtil();
    $logger->info("納品書印刷済み更新テスト開始");
    
    $apiClient = $this->getWebAccessUtil()->getForestNeApiClient();
    
    //　テスト用納品書ディレクトリ置き場
    $fs = new FileSystem();
    $outputDir = $fileUtil->getDataDir() . '/csv_ndl_test';
    if (!$fs->exists($outputDir)) {
      $fs->mkdir($outputDir, 0755);
    }
    
    $logger->info("納品書印刷済み更新テスト　データ取得");
    
    // (1) 対象の伝票から、ステータス違いなど納品書印刷できないデータを除外して最新データを受け取る
    $voucherNumbers = explode(',', $input->getOption('voucher-number'));
    $dataList = $service->searchReceiveorderForReceipt($apiClient, $voucherNumbers);
    if (count($dataList) === 0) {
      return null;
    }
    
    // (2) 納品書印刷が可能な伝票のみ、ステータスを更新。更新出来たデータのリストを改めて受け取る
    $receiptedDataList = $dataList;
    if (! $input->getOption('skip-update')) {
      $logger->info("納品書印刷済み更新テスト　ステータス更新");
      $receiptedDataList = $service->updateReceipted($apiClient, $dataList, $voucherNumbers);
    }
    
    // (3) CSVを出力
    $logger->info("納品書印刷済み更新テスト　CSV出力");
    $filePath = $service->createReceiptCsv($outputDir, $receiptedDataList, $voucherNumbers);
    
    // アクセストークン・リフレッシュトークンの保存
    $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $apiClient->_access_token);
    $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $apiClient->_refresh_token);
    
    $logger->info("納品書印刷済み更新テスト終了");
  }
}