<?php
/**
 * 即納予定日更新処理
 *
 * 0:00 より前に実行される前提の日次バッチ処理
 * 翌日を基準としてその２営業日後の日付を取得し、tb_setting 該当レコードを更新
 */

namespace BatchBundle\Command;

use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateImmediateShippingDateCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('batch:update-immediate-shipping-date')
      ->setDescription('即納予定日更新処理');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    try {
      /** @var DbCommonUtil $commonUtil */
      $commonUtil = $this->getDbCommonUtil();

      $logExecTitle = sprintf('即納予定日更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $now = new \DateTimeImmutable();
      $today = $now->setTime(0, 0, 0);
      // 仕様：翌日を基準に、その2営業日後の営業日
      $immediateShippingDate = $commonUtil->getWorkingDateAfterDays($today->modify('+1 days'), 2);
      $commonUtil->updateImmediateShippingDate($immediateShippingDate);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation(['date' => $immediateShippingDate->format('Y-m-d')]));
      $logger->logTimerFlush();

      $logger->info('即納予定日更新処理を完了しました。[' . $immediateShippingDate->format('Y-m-d') . ']');

    } catch (\Exception $e) {

      $logger->error('即納予定日更新処理エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('即納予定日更新処理', '即納予定日更新処理', 'エラー終了')->setInformation($e->getMessage())
        , true, '即納予定日更新処理' . "でエラーが発生しました。", 'error'
      );

      return 1;
    }

    return 0;
  }

}
