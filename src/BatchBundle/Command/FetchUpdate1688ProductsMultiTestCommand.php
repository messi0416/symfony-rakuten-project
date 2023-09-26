<?php
/**
 * バッチ処理 アリババ登録商品巡回(定時テスト)
 *
 * ※アリババ登録商品巡回(定時テスト)
 *
 * 1. アリババ店舗巡回
 * 2. アリババ登録商品巡回(定時テスト)
 * 3. アリババ登録商品巡回(定時テスト) （在庫巡回） <- ここ
 *    この処理は、キューから巡回商品を一つずつ取得して実行する。-> すなわちJobが回す。
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AlibabaMallProcess;
use MiscBundle\Entity\Repository\Tb1688ProductRepository;
use MiscBundle\Entity\Repository\TbVendoraddressRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\Tb1688Company;
use MiscBundle\Entity\Tb1688Product;
use MiscBundle\Entity\TbVendoraddress;
use MiscBundle\Util\FileLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchUpdate1688ProductsMultiTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  const RETRY_LIMIT   = 20;
  const RETRY_WAIT    = 600; // 秒
  const WAIT          = 10; // 秒

  private $retryLimit;

  protected function configure()
  {
    $this
      ->setName('batch:fetch-update-1688-products-multi-test')
      ->setDescription('アリババ登録商品巡回(定時テスト) 定時巡回テスト')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('retry-limit', null, InputOption::VALUE_OPTIONAL, 'リトライ回数 0:リトライしない 1～:リトライ回数', self::RETRY_LIMIT)
      ->addArgument('offer-id', InputArgument::REQUIRED, 'offerId')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    /** @var FileLogger $fileLogger */
    $fileLogger = $this->getContainer()->get('misc.util.file_logger');
    $fileLogger->setFileName(str_replace(['\\', '/', ' '], '_', 'fetch_1688_products'));

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
      $this->results = [
        'message' => null
      ];

      $this->retryLimit = $input->getOption('retry-limit');
      $offerIds = $input->getArgument('offer-id');

      if (!$offerIds) {
        throw new \RuntimeException('no offer id.');
      }

      $now = new \DateTime();
      $fileLogger->info(sprintf('---- start: %s', $now->format('Y-m-d H:i:s')));
      $fileLogger->info(sprintf('---- ids: %s', $offerIds));

      /** @var AlibabaMallProcess $alibabaProcess */
      $alibabaProcess = $this->getContainer()->get('batch.mall_process.alibaba');
      $offerIds = explode(',', $offerIds);

      foreach($offerIds as $offerId) {

        $retryCount = 0;
        RETRY_START: // retry start -----------------------------------
        try {
          // APIで商品情報を取得、登録
          $offer = $alibabaProcess->apiGetOffer($offerId);

          if (!$offer) {
            $fileLogger->info(sprintf("%s\terror: no offer in alibaba", $offerId));
          }

          $product = new Tb1688Product();

          $product->setOfferId($offerId);
          $product->setDetailsUrl($offer->detailsUrl);
          $product->setMemberId($offer->memberId);
          $product->setType($offer->type);

          $product->setOfferStatus($offer->offerStatus);
          $product->setSubject($offer->subject);
          $product->setQualityLevel($offer->qualityLevel);
          $product->setTradeType($offer->tradeType);
          $product->setPostCategryId($offer->postCategryId);
          $product->setUnit($offer->unit);
          $product->setPriceUnit($offer->priceUnit);
          $product->setAmount($offer->amount);
          $product->setAmountOnSale($offer->amountOnSale);
          $product->setSaledCount($offer->saledCount);
          $product->setProductUnitWeight($offer->productUnitWeight);
          $product->setFreightType($offer->freightType);
          $product->setTermOfferProcess($offer->termOfferProcess);

          $product->setIsPrivate($offer->isPrivate);
          $product->setIsPrivateOffer($offer->isPrivateOffer);
          $product->setIsPriceAuthOffer($offer->isPriceAuthOffer);
          $product->setIsPicAuthOffer($offer->isPicAuthOffer);
          $product->setIsOfferSupportOnlineTrade($offer->isOfferSupportOnlineTrade);
          $product->setIsSkuOffer($offer->isSkuOffer);
          $product->setIsSkuTradeSupported($offer->isSkuTradeSupported);
          $product->setIsSupportMix($offer->isSupportMix);

          $product->setGmtCreate($offer->getGmtCreateJst());
          $product->setGmtModified($offer->getGmtModifiedJst());
          $product->setGmtLastRepost($offer->getGmtLastRepostJst());
          $product->setGmtApproved($offer->getGmtApprovedJst());
          $product->setGmtExpire($offer->getGmtExpireJst());

          $product->setSkuNum($offer->getSkuNum());
          $product->setSkuActiveNum($offer->getSkuNum(function ($item) {
            return isset($item->canBookCount) && $item->canBookCount > 0;
          }));

          $product->setLastChecked(new \DateTime());

          $fileLogger->info(implode("\t", $product->toScalarArray()));

        } catch (FetchUpdate1688ProductsCommandNoRetryException $e) {
          $logger->error($e->getMessage());
          throw $e;

        } catch (\Exception $e) {
          $logger->error($e->getMessage());

          if (++$retryCount > $this->retryLimit) {
            $logger->error('リトライ回数の上限を超過。処理を終了します。');
            throw $e;
          }

          sleep(self::RETRY_WAIT);
          $logger->info('リトライ回数 ' . $retryCount . ' / ' . $this->retryLimit);

          goto RETRY_START; // return to retry start -----------------------------------
        }
      }

    } catch (FetchUpdate1688ProductsCommandException $e) {
      // 正常終了。 do nothing.
      $logger->info($e->getMessage());

    } catch (\Exception $e) {

      $logger->error('アリババ登録商品巡回(定時テスト) エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('アリババ登録商品巡回(定時テスト) エラー', 'アリババ登録商品巡回(定時テスト) エラー', 'エラー終了')->setInformation($e->getMessage())
        , false // true // FOR DEBUG
        , 'アリババ登録商品巡回(定時テスト) でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}
