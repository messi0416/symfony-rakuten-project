<?php
/**
 * バッチ処理 アリババ未取得商品巡回
 *
 * ※アリババ未取得商品巡回
 *
 * 1. アリババ店舗巡回
 * 2. アリババ未取得商品巡回  <- ここ
 * 3. アリババ登録商品巡回 （在庫巡回）
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AlibabaMallProcess;
use MiscBundle\Entity\Repository\Tb1688ProductRepository;
use MiscBundle\Entity\Repository\TbVendoraddressRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\Tb1688Product;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchUpdate1688NewProductsCommand extends ContainerAwareCommand
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
      ->setName('batch:fetch-update-1688-new-products')
      ->setDescription('アリババ未取得商品巡回')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('retry-limit', null, InputOption::VALUE_OPTIONAL, 'リトライ回数 0:リトライしない 1～:リトライ回数', self::RETRY_LIMIT)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('アリババ未取得商品巡回を開始しました。');

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

      $logExecTitle = sprintf('アリババ未取得商品巡回');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'), false);

      $this->retryLimit = $input->getOption('retry-limit');

      // 仕入れアドレス 不要なクエリパラメータ除去
      /** @var AlibabaMallProcess $alibabaProcess */
      $alibabaProcess = $this->getContainer()->get('batch.mall_process.alibaba');
      $result = $alibabaProcess->trimAlibabaSireAddressQueryParameter();
      $logger->info('アリババ仕入れ先取得: 仕入先URLクエリパラメータ除去 終了 (' . $result . '件)');

      // 巡回対象取得
      // tb_1688_products へ未登録の offer_id (stop = -1 は除外)
      $db = $this->getDb('main');

      /** @var Tb1688ProductRepository $repoProduct */
      $repoProduct = $this->getDoctrine()->getRepository('MiscBundle:Tb1688Product');
      /** @var TbVendoraddressRepository $repoVendorAddress */
      $repoVendorAddress = $this->getDoctrine()->getRepository('MiscBundle:TbVendoraddress');

      $sql = <<<EOD
        SELECT
           va.daihyo_syohin_code
         , va.sire_adress
         , va.vendoraddress_CD AS id
        FROM tb_vendoraddress va
        INNER JOIN tb_mainproducts m ON va.daihyo_syohin_code = m.daihyo_syohin_code
        LEFT JOIN tb_1688_product p ON va.sire_adress = p.details_url
        WHERE va.sire_adress LIKE '%detail.1688.com/%'
          AND p.offer_id IS NULL
          AND va.stop = 0
          AND va.soldout = 0 /* 通常巡回では対象としない。全巡回では、漸減処理を実装予定。ひとまず通常巡回のみ */
EOD;
      $stmt = $db->prepare($sql);
      $stmt->execute();

      $logger->info(sprintf('alibaba check start : %d items', $stmt->rowCount()));

      foreach($stmt as $row) {

        $offerId = $alibabaProcess->getOfferIdByUrl($row['sire_adress']);
        $logger->info(sprintf('%s : %s : %s', $row['daihyo_syohin_code'], $row['sire_adress'], $offerId));

        if (!$offerId) {
          $logger->info('-- (skip: no offer id)');
          continue;
        }

        $product = $repoProduct->find($offerId);
        // ひとまず、登録済み商品はスキップ
        if ($product) {
          $logger->info('-- (skip: registered product)');
          continue;
        }

        $retryCount = 0;
        RETRY_START: // retry start -----------------------------------
        try {

          // APIで商品情報を取得、登録
          $offer = $alibabaProcess->apiGetOffer($offerId);

          sleep(self::WAIT); // APIを実行したら必ずsleep

          // アリババにないなら stop でよい。...と思うが、強制stopは今はしない方針なので、soldoutを更新し、setafter を 0へ。
          // soldout:0 ... 通常の巡回対象からは外れる
          // setafter:0 ... setbefore から変更されているのであれば、WEBチェッカー反映確認へ出てくる
          if (!$offer) {
            $logger->info('-- (skip: no offer in alibaba)');
            $repoVendorAddress->setSoldOutOn($row['id']);
            continue;
          }

          $product = new Tb1688Product();
          $product->setOfferId($offer->offerId);
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
          $product->setSkuActiveNum($offer->getSkuNum(function($item) {
            return isset($item->canBookCount) && $item->canBookCount > 0;
          }));

          $product->setLastChecked(new \DateTime());

          $em = $this->getDoctrine()->getManager('main');
          $em->persist($product);

          $em->flush();

          $logger->info(
            sprintf('ok: %s => AVAILABLE SKU NUM: %d / SKU NUM: %d'
                , $offerId
                , $offer->getSkuNum(function($item) {
                  return isset($item->canBookCount) && $item->canBookCount > 0;
                })
                , $offer->getSkuNum()
            )
          );

          // vendor_address setafter 更新
          $repoVendorAddress->updateSetAfter($row['id'], $product->getSkuActiveNum());

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

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'), false);
      $logger->logTimerFlush();

      $logger->info('アリババ未取得商品巡回を完了しました。');

    } catch (\Exception $e) {

      $logger->error('アリババ未取得商品巡回 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('アリババ未取得商品巡回 エラー', 'アリババ未取得商品巡回 エラー', 'エラー終了')->setInformation($e->getMessage())
        , false // true // FOR DEBUG
        , 'アリババ未取得商品巡回 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


