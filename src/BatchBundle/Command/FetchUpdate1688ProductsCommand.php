<?php
/**
 * バッチ処理 アリババ登録商品巡回
 *
 * ※アリババ登録商品巡回
 *
 * 1. アリババ店舗巡回
 * 2. アリババ登録商品巡回
 * 3. アリババ登録商品巡回 （在庫巡回） <- ここ
 *    この処理は、キューから巡回商品を一つずつ取得して実行する。-> すなわちJobが回す。
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AlibabaMallProcess;
use MiscBundle\Entity\Repository\Tb1688ProductRepository;
use MiscBundle\Entity\Repository\TbVendoraddressRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\Tb1688Product;
use MiscBundle\Entity\TbVendoraddress;
use MiscBundle\Util\FileLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchUpdate1688ProductsCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  /** @var FileLogger */
  private $fileLogger;

  const RETRY_LIMIT   = 20;
  const RETRY_WAIT    = 600; // 秒
  const WAIT          = 10; // 秒

  private $retryLimit;

  protected function configure()
  {
    $this
      ->setName('batch:fetch-update-1688-products')
      ->setDescription('アリババ登録商品巡回')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('retry-limit', null, InputOption::VALUE_OPTIONAL, 'リトライ回数 0:リトライしない 1～:リトライ回数', self::RETRY_LIMIT)
      ->addArgument('offer-id', InputArgument::REQUIRED, 'offerId')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $fileLogger = $this->getFileLogger();

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
      $offerId = $input->getArgument('offer-id');

      if (!$offerId) {
        throw new \RuntimeException('no offer id.');
      }

      /** @var AlibabaMallProcess $alibabaProcess */
      $alibabaProcess = $this->getContainer()->get('batch.mall_process.alibaba');

      /** @var Tb1688ProductRepository $repoProduct */
      $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:Tb1688Product');
      /** @var TbVendoraddressRepository $repoVendorAddress */
      $repoVendorAddress = $this->getDoctrine()->getRepository('MiscBundle:TbVendoraddress');

      /** @var Tb1688Product $product */
      $product = $repoProduct->find($offerId);
      if (!$product) {
        throw new \RuntimeException('no 1688 product record.');
      }
      /** @var TbVendoraddress[] $vendorAddresses */
      $vendorAddresses = $repoVendorAddress->findBy([
          'sireAdress' => $product->getDetailsUrl()
        , 'stop' => 0
      ]);
      if (!count($vendorAddresses)) {
        throw new FetchUpdate1688ProductsCommandNoRetryException('no vendor address record.');
      }

      $retryCount = 0;
      RETRY_START: // retry start -----------------------------------
      try {
        // APIで商品情報を取得、登録
        $offer = $alibabaProcess->apiGetOffer($offerId);

        // アリババにないなら stop でよい。...と思うが、強制stopは今はしない方針なので、soldoutを更新し、setafter を 0へ。
        // soldout:0 ... 通常の巡回対象からは外れる
        // setafter:0 ... setbefore から変更されているのであれば、WEBチェッカー反映確認へ出てくる
        if (!$offer) {
          foreach ($vendorAddresses as $va) {
            $repoVendorAddress->setSoldOutOn($va->getVendoraddressCd());
          }

          // 取得できなかったステータスとして、offer_status に独自コード格納
          $product->setOfferStatus(Tb1688ProductRepository::OFFER_STATUS_MISSING);
          $product->setLastChecked(new \DateTime());
          $em = $this->getDoctrine()->getManager('main');
          $em->flush();

          throw new FetchUpdate1688ProductsCommandNoRetryException('no offer in alibaba');
        }

        // 復活頻度確認のため、ログに出力
        if ( $product->getOfferStatus() != Tb1688ProductRepository::OFFER_STATUS_ONLINE
          && $offer->offerStatus == Tb1688ProductRepository::OFFER_STATUS_ONLINE
        ) {
          $log = sprintf("アリババ登録商品巡回 復活:[%s => %s]:%s %s"
            , $product->getOfferStatus()
            , $offer->offerStatus
            , $offerId
            , (new \DateTime())->format('Y-m-d H:i:s')
          );
          $logger->info($log);
          $fileLogger->info($log);
        }

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

        $em = $this->getDoctrine()->getManager('main');
        $em->flush();

        $log = sprintf('[%s] stock check ok: %s => AVAILABLE SKU NUM: %d / SKU NUM: %d'
          , (new \DateTime())->format('Y-m-d H:i:s')
          , $offerId
          , $offer->getSkuNum(function ($item) {
            return isset($item->canBookCount) && $item->canBookCount > 0;
          })
          , $offer->getSkuNum()
        );
        $logger->info($log);
        $fileLogger->info($log);

        // vendor_address setafter 更新
        foreach ($vendorAddresses as $va) {
          // offerStatus による販売中判定
          if (in_array($offer->offerStatus, [
              Tb1688ProductRepository::OFFER_STATUS_ONLINE
            // , Tb1688ProductRepository::OFFER_STATUS_OUTDATED // 'outdated' はやっぱり売り切れ。通常巡回はする。
          ])) {
            $repoVendorAddress->updateSetAfter($va->getVendoraddressCd(), $product->getSkuActiveNum());
          } else {
            $repoVendorAddress->setSoldOutOn($va->getVendoraddressCd());
          }
        }

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

      $logger->info(sprintf('アリババ登録商品巡回: %s', $offerId));

    } catch (FetchUpdate1688ProductsCommandException $e) {
      // 正常終了。 do nothing.
      $logger->info($e->getMessage());

    } catch (\Exception $e) {

      $logger->error('アリババ登録商品巡回 エラー:' . $e->getMessage());
      $fileLogger->error($e->getMessage());

      $logger->addDbLog(
        $logger->makeDbLog('アリババ登録商品巡回 エラー', 'アリババ登録商品巡回 エラー', 'エラー終了')->setInformation($e->getMessage())
        , false // true // FOR DEBUG
        , 'アリババ登録商品巡回 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }


  private function initFileLogger($name)
  {
    if (!isset($this->fileLogger)) {
      /** @var FileLogger $fileLogger */
      $this->fileLogger = $this->getContainer()->get('misc.util.file_logger');
      $this->fileLogger->setFileName(str_replace(['\\', '/', ' '], '_', $name));
    }
  }

  /**
   * @return FileLogger
   */
  private function getFileLogger()
  {
    if (!isset($this->fileLogger)) {
      $this->initFileLogger(get_class($this));
    }
    return $this->fileLogger;
  }


}


class FetchUpdate1688ProductsCommandException extends \RuntimeException {}
class FetchUpdate1688ProductsCommandNoRetryException extends FetchUpdate1688ProductsCommandException {}



