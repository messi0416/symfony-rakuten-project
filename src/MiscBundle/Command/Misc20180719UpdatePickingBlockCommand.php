<?php
/**
 * バッチ処理 20180719ピッキングブロック更新処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\Repository\TbWarehouseRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeliveryPickingBlock;
use MiscBundle\Entity\TbDeliveryPickingBlockDetail;
use MiscBundle\Entity\TbLog;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class Misc20180719UpdatePickingBlockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:20180719-update-picking-block')
      ->setDescription('20180719ピッキングブロック更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addArgument('data-path', InputArgument::REQUIRED, '全ブロックデータ')
      ->addOption('warehouse-id', null, InputOption::VALUE_OPTIONAL, '倉庫ID', TbWarehouseRepository::DEFAULT_WAREHOUSE_ID)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('20180719ピッキングブロック更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {

      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('20180719ピッキングブロック更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始')->setLogLevel(TbLog::DEBUG));

      $blocks = [];
      $warehouseId = $input->getOption('warehouse-id');

      $path = $input->getArgument('data-path');
      $file = new File($path);
      $fo = $file->openFile('r');

      $i = 0;
      foreach($fo as $line) {
        $i++;
        $line = trim($line);
        if (!strlen($line)) {
          continue;
        }

        $logger->info($line);

        if (!preg_match('/^([^:]+):([a-zA-Z0-9,]+)$/', $line, $m)) {
          throw new \RuntimeException(sprintf('(%d行目) 書式が正しくない行があります。[ブロックコード]:[パターンコンマ区切り] / %s', $i, $line));
        }

        $blockCode = $m[1];
        if (isset($blocks[$blockCode])) {
          throw new \RuntimeException('ブロックコードの重複があります。');
        }

        $patterns = explode(',', $m[2]);
        sort($patterns);
        $blocks[$blockCode] = $patterns;
      }

      // 更新処理開始
      $dbMain = $this->getDb('main');
      $displayOrder = count($blocks) * 100;

      $em = $this->getDoctrine()->getManager('main');

      $dbMain->query("TRUNCATE tb_delivery_picking_block_detail;");
      $dbMain->query("TRUNCATE tb_delivery_picking_block;");
      foreach($blocks as $blockCode => $patterns) {
        $block = new TbDeliveryPickingBlock();
        $block->setWarehouseId($warehouseId);
        $block->setBlockCode($blockCode);
        $block->setDisplayOrder($displayOrder);
        $em->persist($block);

        $displayOrder -= 100;

        foreach($patterns as $pattern) {
          $detail = new TbDeliveryPickingBlockDetail();
          $detail->setWarehouseId($warehouseId);
          $detail->setBlockCode($blockCode);
          $detail->setPattern(trim($pattern));
          $em->persist($detail);
        }
      }

      $em->flush();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setLogLevel(TbLog::DEBUG));
      $logger->logTimerFlush();

      $logger->info('20180719ピッキングブロック更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('20180719ピッキングブロック更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('20180719ピッキングブロック更新処理 エラー', '20180719ピッキングブロック更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '20180719ピッキングブロック更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


