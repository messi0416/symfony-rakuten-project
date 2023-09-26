<?php
/**
 * バッチ処理 SKUのサイズ変更に伴う各種更新
 *
 * ※仕様詳細
 * このコマンドのトランザクションは、
 * (1) SKUサイズ前回値テーブルに、更新フラグ設定
 * (2) 各種関連処理
 *       ・関連処理ごとに、トランザクションもcommitされる
 * (3) SKUサイズ前回値テーブルを初期化
 * という3ステップでなっている。
 * このため、途中処理でエラーが発生した場合、(2)の各処理は再実行される場合がある。
 * 再実行されても良いような実装とすること。
 * （※処理件数が多くなることもあり得るため、あえて全体で1トランザクションとはしていない）
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\TbShippingdivision;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * バッチ処理 SKUのサイズ変更に伴う更新処理起動バッチ。
 * SKUのサイズ変更を元にする追加変更は、ここに記載する。
 *
 * SKUサイズ前回値テーブルを利用して、前回実行時と今回実行時でサイズの差分があるレコードをチェックし、差分があるものに対して後続処理を実行する。
 * 処理完了時に、SKUサイズ前回値テーブルを前回値で更新する。
 *
 * ※ 2019/12/04現在は、UpdateProductSizeCommand が独立している。将来的にはここにマージできると望ましい。
 * @author a-jinno
 *
 */
class SkuSizeChangeRelatedUpdateCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  private $results;

  protected function configure()
  {
    $this
    ->setName('batch:sku-size-change-related-update')
    ->setDescription('SKUのサイズ変更に伴う更新処理。前回実行時からの差分処理を実行する')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('SKUのサイズ変更に伴う更新処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = 'SKUのサイズ変更に伴う更新処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
    $logger->setExecTitle($logExecTitle);

    try {
      $pciFsRepository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitemsFormerSize');
      $pciRepository = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems');

      // SKUサイズ前回値テーブル準備
      $logger->addDbLog($logger->makeDbLog(null, 'サイズ変更チェック'));
      $pciFsRepository->updateChangedData();

      // 個別処理はここに記載
      $logger->addDbLog($logger->makeDbLog(null, '送料設定更新'));
      $pciRepository->updateSizeChangeItemShippingdivision($logger);

      $logger->addDbLog($logger->makeDbLog(null, '代表商品重量更新'));
      $productRepository = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');
      $productRepository->updateMainproductsWeightFromSku($logger);

      // SKUサイズ前回値テーブルを初期化
      $logger->addDbLog($logger->makeDbLog(null, 'サイズ前回値テーブルを現在値で更新'));
      $pciFsRepository->refleshAll();

      $logger->addDbLog($logger->makeDbLog(null, '正常終了', '終了'));

      $logger->logTimerFlush();
      return 0;

    } catch (\Exception $e) {
      $logger->error($e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog(null, 'エラー終了', 'エラー終了')->setInformation(['error' => $e->getMessage(), 'stacktrace' => $e->getTraceAsString()])
          , true, $logExecTitle . "でエラーが発生しました。", 'error'
          );
      return 1;
    }
  }
}