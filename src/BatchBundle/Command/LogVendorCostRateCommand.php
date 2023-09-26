<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class LogVendorCostRateCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:log-vendor-cost-rate')
      ->setDescription('仕入先別 原価率の保存バッチ処理');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $logExecTitle = 'バッチ:仕入先原価率履歴 保存処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始', '', '', 'BatchSV01:CRON'));

    try {
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getContainer()->get('doctrine')->getConnection('main');
      $mainDbName = $dbMain->getDatabase();

      /** @var \Doctrine\DBAL\Connection $db */
      $db = $this->getContainer()->get('doctrine')->getConnection('log');

      // 処理前件数取得
      $preCount = $db->query("SELECT COUNT(*) FROM tb_vendor_cost_rate_log")->fetchColumn(0);

      // 保存処理
      $sql  = " INSERT IGNORE INTO tb_vendor_cost_rate_log ( ";
      $sql .= "      log_date ";
      $sql .= "    , sire_code ";
      $sql .= "    , sire_name ";
      $sql .= "    , cost_rate ";
      $sql .= " )  ";
      $sql .= " SELECT  ";
      $sql .= "     NOW() ";
      $sql .= "   , sire_code ";
      $sql .= "   , sire_name ";
      $sql .= "   , cost_rate ";
      $sql .= " FROM `{$mainDbName}`.tb_vendormasterdata  ";
      $sql .= " ORDER BY sire_code; ";

      $db->query($sql);

      // 処理後件数取得
      $postCount = $db->query("SELECT COUNT(*) FROM tb_vendor_cost_rate_log")->fetchColumn(0);
      $info = sprintf('%s 件 => %s 件 (保存: %s 件)'
                , number_format($preCount)
                , number_format($postCount)
                , number_format($postCount - $preCount)
      );

      $logger->addDbLog($logger->makeDbLog(
                            $logExecTitle
                          , $logExecTitle
                          , '終了'
                          , ''
                          , ''
                          , 'BatchSV01:CRON'
                        )->setInformation($info));

      $logger->info('[daily] log-vendor-cost-rate DONE.');
      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage()), 1, $logExecTitle . 'に失敗しました。', 'error');
      return 1;
    }
  }

}
