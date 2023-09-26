<?php
/**
 * バッチ処理 発送方法変更 受注情報更新処理
 * ※受注明細差分更新はこの前に済んでいる前提とし、その発送方法の取得並びにそこで取得されない配送情報関連の補完を行う。
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\Repository\TbDeliveryChangeShippingMethodRepository;
use MiscBundle\Entity\Repository\TbSalesDetailRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeliveryChangeShippingMethod;
use MiscBundle\Entity\VSalesVoucher;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateChangeShippingMethodOrderCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:update-change-shipping-method-order')
      ->setDescription('発送方法変更 受注情報更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('発送方法変更 受注情報更新処理を開始しました。');

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

      $logExecTitle = sprintf('発送方法変更 受注情報更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      // ---------------------------------------------
      // 受注明細からの変更反映（発送方法）
      /** @var TbDeliveryChangeShippingMethodRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryChangeShippingMethod');
      /** @var TbDeliveryChangeShippingMethod[] $changeList */
      $changeList = $repo->findList();

      $voucherNumbers = [];
      foreach ($changeList as $change) {
        $voucherNumbers[] = $change->getVoucherNumber();
      }

      if (!$voucherNumbers) {
        throw new UpdateChangeShippingMethodOrderCommandNoVoucherException('発送方法変更対象の伝票がありません。');
      }

      /** @var TbSalesDetailRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
      /** @var VSalesVoucher $voucher */
      $vouchers = $repo->getVoucherByVoucherNumbers($voucherNumbers);

      $now = new \DateTime();
      foreach ($changeList as $change) {
        $voucherNumber = $change->getVoucherNumber();
        if (isset($vouchers[$voucherNumber])) {
          $voucher = $vouchers[$voucherNumber];
          if ($change->getCurrentReceiveOrderDeliveryId() != $voucher->getShippingMethodCode()) {
            $change->setCurrentReceiveOrderDeliveryId($voucher->getShippingMethodCode());
            $change->setCurrentShippingMethod($voucher->getShippingMethodName());
            $change->setShippingMethodChanged($now);
          }
        }
      }

      /** @var EntityManager $em */
      $em = $this->getDoctrine()->getManager('main');
      $em->flush();

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('発送方法変更 受注情報更新処理を完了しました。');

    } catch (UpdateChangeShippingMethodOrderCommandNoVoucherException $e) {

      $this->results['message'] = $e->getMessage();
      // DB記録＆通知処理
      $logExecTitle = '発送方法変更 受注情報更新処理';
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('発送方法変更 受注情報更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('発送方法変更 受注情報更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('発送方法変更 受注情報更新処理 エラー', '発送方法変更 受注情報更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '発送方法変更 受注情報更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}

class UpdateChangeShippingMethodOrderCommandNoVoucherException extends \RuntimeException {}

