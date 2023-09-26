<?php

namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbProductSalesAccount;
use MiscBundle\Entity\TbProductSalesAccountHistory;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\Repository\TbProductSalesAccountRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 商品売上担当者適用終了日登録処理
 * 
 * 一定期間稼働中でない商品の売上担当者を適用終了にする。
 * 非稼働日が更新される共通日次バッチよりも、後に実行される方が望ましい。
 * 適用終了日がNULLないし本日より後のデータを本日に変更するのに実績再集計は不要の為、省略。
 * このバッチが終了した後、同日に売上が発生することは考えられ、
 * 丁度同日に稼働中に戻ったが、担当者は適用終了されてしまったという事は起き得るので注意。
 */
class UpdateProductSalesAccountApplyEndCommand extends PlusnaoBaseCommand
{

  protected function configure()
  {
    $this
    ->setName('batch:update-product-sales-account-apply-end')
    ->setDescription('商品売上担当者適用終了処理')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
    ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '商品売上担当者適用終了処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    try {
      $logger = $this->getLogger();
      /** @var \Doctrine\DBAL\Connection $dbMain */
      $dbMain = $this->getDoctrine()->getConnection('main');

      $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      $inactiveMonths = $settingRepo->find(TbSetting::KEY_PRODUCT_SALES_ACCOUNT_TERMINATE_MONTHS)->getSettingVal();

      $today = (new \DateTime())->format('Y-m-d');
      $targetDate = (new \DateTime())->modify('-' . $inactiveMonths . ' month');
  
      $sql = <<<EOD
        SELECT
          a.id,
          a.apply_end_date AS applyEndDate
        FROM
          tb_product_sales_account a
          INNER JOIN tb_mainproducts_cal cal
            ON a.daihyo_syohin_code = cal.daihyo_syohin_code
        WHERE
          a.status = :registration
          AND cal.inactive_date < :targetDate
          AND a.apply_start_date < :today
          AND (a.apply_end_date IS NULL OR a.apply_end_date > :today)
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':registration', TbProductSalesAccount::STATUS_REGISTRATION, \PDO::PARAM_STR);
      $stmt->bindValue(':today', $today, \PDO::PARAM_STR);
      $stmt->bindValue(':targetDate', $targetDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->execute();
      $targetAccounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

      if (empty($targetAccounts)) {
        return;
      }

      // 対象件数次第では、SQLが無限に長くなってしまうので1000件ずつ処理するよう分割
      $targetAccountChunk = array_chunk($targetAccounts, 1000);

      $dbMain->beginTransaction();

      foreach ($targetAccountChunk as $targetAccountList) {
        $accountIdsStr = implode(',', array_column($targetAccountList, 'id'));
        $sql = <<<EOD
          UPDATE tb_product_sales_account a
             SET a.apply_end_date = :today
           WHERE a.id IN ({$accountIdsStr})
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':today', $today, \PDO::PARAM_STR);
        $stmt->execute();
      }


      /** @var TbProductSalesAccountRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbProductSalesAccount');
      $em = $this->getDoctrine()->getManager('main');
      $count = 0;
      foreach($targetAccounts as $targetAccount) {
        /** @var TbProductSalesAccount $account */
        $account = $repo->find((int)$targetAccount['id']);
        $beforeEndDate = $targetAccount['applyEndDate'] ?? '未設定';
        $note = sprintf('適用終了日：%s ⇒ %s', $beforeEndDate, $today);

        $accountHistory = new TbProductSalesAccountHistory();
        $accountHistory->setProcessType(TbProductSalesAccountHistory::PROCESS_TYPE_CHANGE);
        $accountHistory->setNote($note);
        $accountHistory->setUpdateAccount(NULL);
        $accountHistory->addProductSalesAccount($account);
        $em->persist($accountHistory);
        $count++;
        if ($count >= 1000) {
          $em->flush();
          $count = 0;
        }
      }
      $em->flush();

      $dbMain->commit();
    } catch (\Exception $e) {
      try {
        $dbMain->rollback();
      } catch (\Exception $e2) {
        $logger->error(
          $this->commandName . 'rollbackでエラー: ' . $e2->getMessage() . $e2->getTraceAsString()
        );
      }
      throw $e;
    }
  }
}
