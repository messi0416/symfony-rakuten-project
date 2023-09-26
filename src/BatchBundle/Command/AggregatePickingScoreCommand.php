<?php
namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Service\PickingScoreService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 ピッキングスコア集計処理
 * ピッキングスコアを算出しテーブルに保存する
 */
class AggregatePickingScoreCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:aggregate-picking-score')
      ->setDescription('ピッキングスコア集計処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
    ;
  }

  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = 'ピッキングスコア集計処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $settingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
    // 過去nか月分のデータを対象とする
    $limitMonth = $settingRepo->find(TbSetting::KEY_PICKING_LIMIT_MONTH)->getSettingVal();
    $modifyStr = sprintf('-%s months', $limitMonth);
    $now = new \DateTime();
    $targetDate = $now->modify($modifyStr)->format('Y/m/d');

    /** @var $service PickingScoreService */
    $service = $this->getContainer()->get('misc.service.picking_score');
    $pickingScore = $service->aggregatePickingScore($targetDate);
    $service->storePickingScore($pickingScore);
  }
}
