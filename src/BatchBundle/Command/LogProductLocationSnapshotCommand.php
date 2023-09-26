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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class LogProductLocationSnapshotCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('batch:log-product-location-snapshot')
      ->setDescription('商品ロケーション日次スナップショット 保存バッチ処理')
      ->addOption('date', null, InputOption::VALUE_OPTIONAL, '日付指定 yyyy-mm-dd', null)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();

    $logExecTitle = 'バッチ:商品ロケーションスナップショット 保存処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始', '', '', 'BatchSV01:CRON'));

    try {
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDb('main');
      $mainDbName = $dbMain->getDatabase();

      /** @var \Doctrine\DBAL\Connection $db */
      $db = $this->getDb('log');

      if ($input->getOption('date')) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $input->getOption('date'))) {
          $logDate = new \DateTime($input->getOption('date'));
        } else {
          throw new \RuntimeException('日付指定の書式が正しくありません。 ' . $input->getOption('date'));
        }
      } else {
        $logDate = new \DateTime();
      }

      // パーティション存在確認
      $logTableName = 'tb_product_location_snapshot';
      $partitionName = sprintf('p%s', $logDate->format('Ym'));

      $sql  = " SELECT ";
      $sql .= "     TABLE_SCHEMA ";
      $sql .= "   , TABLE_NAME ";
      $sql .= "   , PARTITION_NAME ";
      $sql .= "   , PARTITION_ORDINAL_POSITION ";
      $sql .= "   , TABLE_ROWS  ";
      $sql .= " FROM ";
      $sql .= "   INFORMATION_SCHEMA.PARTITIONS  ";
      $sql .= " WHERE TABLE_NAME = :tableName  ";
      $sql .= "   AND PARTITION_NAME = :partitionName ";

      $stmt = $db->prepare($sql);
      $stmt->execute([
          ':tableName' => $logTableName
        , ':partitionName' => $partitionName
      ]);
      $partition = $stmt->fetch();

      // 該当月パーティション作成
      if (empty($partition)) {
        $sql  = " ALTER TABLE `{$logTableName}` REORGANIZE PARTITION pmax INTO ( ";
        $sql .= "     PARTITION `{$partitionName}` VALUES LESS THAN (:limitDate), ";
        $sql .= "     PARTITION pmax VALUES LESS THAN MAXVALUE ";
        $sql .= " ); ";

        $partitionDate = new \DateTime($logDate->format('Y-m-t 00:00:00'));
        $partitionDate->modify('+1 day');
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limitDate', $partitionDate->format('Y-m-d 00:00:00'));
        $stmt->execute();
      }

      // 処理前件数取得
      $stmt = $db->query("SELECT COUNT(*) FROM tb_product_location_snapshot");
      $preCount = $stmt->fetchColumn(0);

      $sql  = " INSERT IGNORE INTO tb_product_location_snapshot ( ";
      $sql .= "       log_date ";
      $sql .= "     , ne_syohin_syohin_code ";
      $sql .= "     , location_code ";
      $sql .= "     , warehouse_id ";
      $sql .= "     , location_id ";
      $sql .= "     , stock ";
      $sql .= "     , position ";
      $sql .= "     , row_created ";
      $sql .= "     , row_updated ";
      $sql .= " )  ";
      $sql .= " SELECT  ";
      $sql .= "       :logDate AS log_Date ";
      $sql .= "     , pl.ne_syohin_syohin_code ";
      $sql .= "     , l.location_code ";
      $sql .= "     , l.warehouse_id ";
      $sql .= "     , pl.location_id ";
      $sql .= "     , pl.stock ";
      $sql .= "     , pl.position ";
      $sql .= "     , pl.created ";
      $sql .= "     , pl.updated ";

      $sql .= " FROM `{$mainDbName}`.tb_product_location pl  ";
      $sql .= " INNER JOIN `{$mainDbName}`.tb_location l ON pl.location_id = l.id ";
      $sql .= " ORDER BY pl.ne_syohin_syohin_code, pl.position  ";

      $stmt = $db->prepare($sql);
      $stmt->bindValue(':logDate', $logDate->format('Y-m-d'));
      $stmt->execute();

      // 処理後件数取得 ＆ チェック ＆ ログ保存
      $stmt = $db->query("SELECT COUNT(*) FROM tb_product_location_snapshot");
      $postCount = $stmt->fetchColumn(0);
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

      $logger->info('[daily] batch:log-product-location-snapshot DONE.');



      // tb_product_location_record_log のパーティションチェック＆追加も行う。 (ロケーションログ関連でまとめたがあまりよくない？)
      // こちらは来月分のパーティションを 各月21日以降、毎日チェックする。
      // ※トリガでの挿入であるため、処理の前にチェックができない。事前にパーティションを用意しておく
      $today = new \DateTimeImmutable();
      $logger->info(print_r($today, true));
      if (intval($today->format('d')) >= 21) {
        $nextMonth = (new \DateTimeImmutable($today->format('Y-m-1 00:00:00')))->modify('+1 month');
        // パーティション存在確認
        $logTableName = 'tb_product_location_record_log';
        $partitionName = sprintf('p%s', $nextMonth->format('Ym'));

        $sql  = " SELECT ";
        $sql .= "     TABLE_SCHEMA ";
        $sql .= "   , TABLE_NAME ";
        $sql .= "   , PARTITION_NAME ";
        $sql .= "   , PARTITION_ORDINAL_POSITION ";
        $sql .= "   , TABLE_ROWS  ";
        $sql .= " FROM ";
        $sql .= "   INFORMATION_SCHEMA.PARTITIONS  ";
        $sql .= " WHERE TABLE_NAME = :tableName  ";
        $sql .= "   AND PARTITION_NAME = :partitionName ";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':tableName' => $logTableName
          , ':partitionName' => $partitionName
        ]);
        $partition = $stmt->fetch();

        // 該当月パーティション作成
        if (empty($partition)) {
          $sql  = " ALTER TABLE `{$logTableName}` REORGANIZE PARTITION pmax INTO ( ";
          $sql .= "     PARTITION `{$partitionName}` VALUES LESS THAN (:limitDate), ";
          $sql .= "     PARTITION pmax VALUES LESS THAN MAXVALUE ";
          $sql .= " ); ";

          $partitionDate = new \DateTime($nextMonth->format('Y-m-t 00:00:00'));
          $partitionDate->modify('+1 day');
          $stmt = $db->prepare($sql);
          $stmt->bindValue(':limitDate', $partitionDate->format('Y-m-d 00:00:00'));
          $stmt->execute();
        }
      }

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage()), 1, $logExecTitle . 'に失敗しました。', 'error');
      return 1;
    }
  }

}
