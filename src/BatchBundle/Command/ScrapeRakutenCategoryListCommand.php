<?php
namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 楽天カテゴリリスト更新バッチ。
 * 
 * 通常楽天CSV出力で行われるが、よく503エラーとなるなど、更新タイミングが安定しないので
 * 成功するまでトライできるよう、そこだけ分離したバッチ。
 * 新規店舗や、スクレイピングが変更された際のテストでの利用を想定。
 */
class ScrapeRakutenCategoryListCommand extends PlusnaoBaseCommand
{
  protected function configure()
  {
    $this
      ->setName('batch:scrape-rakuten-category-list')
      ->setDescription('楽天カテゴリリスト更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target-shop', null, InputOption::VALUE_OPTIONAL, '対象店舗。rakuten|motto|laforest|dolcissimo|gekipla')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN) // このまま記載すること
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = '楽天カテゴリリスト更新処理[' . $input->getOption('target-shop') . ']';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    /** @var RakutenMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.rakuten');
    $logger = $this->getLogger();
    
    $processor->setTargetShop($input->getOption('target-shop')); // 店舗指定チェックはProcessorに任せてしまう
    $processor->updateCategoryListFromRakutenWebSite($logger);
  }
}


