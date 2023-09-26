<?php
/**
 * バッチ処理 アリババ企業商品一括更新処理
 */

namespace BatchBundle\Command;

use BatchBundle\Exception\NeverRetryException;
use BatchBundle\MallProcess\AlibabaMallProcess;
use forestlib\AlibabaSdk\Plusnao\Entity\Offer;
use MiscBundle\Entity\Repository\Tb1688CompanyRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\Tb1688Company;
use MiscBundle\Util\MultiInsertUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchUpdate1688CompanyProductsCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  const RETRY_LIMIT   = 20;
  const RETRY_WAIT    = 600; // 秒
  const WAIT          = 7; // 秒

  protected function configure()
  {
    $this
      ->setName('batch:fetch-update-1688-company-products')
      ->setDescription('アリババ企業商品一括更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('member-id', null, InputOption::VALUE_REQUIRED, 'アリババ企業memberId')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('アリババ企業商品一括更新処理を開始しました。');

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
        , 'memberId' => null
        , 'totalCount' => 0
        , 'fetchCount' => 0
      ];

      $memberId = $input->getOption('member-id');
      $this->results['memberId'] = $memberId ? $memberId : '(none)';

      $logExecTitle = sprintf('アリババ企業商品一括更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始', $this->results['memberId']), false);

      /** @var Tb1688CompanyRepository $repoCompany */
      $repoCompany = $this->getDoctrine()->getRepository('MiscBundle:Tb1688Company');
      /** @var Tb1688Company $company */
      $company = $repoCompany->find($memberId);
      if (!$company) {
        throw new \RuntimeException(sprintf('アリババ企業情報が見つかりませんでした。[%s]', $memberId));
      }

      $logger->info(sprintf('アリババ企業商品一括更新 開始: %s : %s', $company->getMemberId(), $company->getCompanyName()));

      /** @var AlibabaMallProcess $alibabaProcess */
      $alibabaProcess = $this->getContainer()->get('batch.mall_process.alibaba');

      $dbMain = $this->getDb('main');
      $commonUtil = $this->getDbCommonUtil();

      $page = 1;
      $pageSize = AlibabaMallProcess::PAGE_SIZE_MAX_SEARCH_OFFER;
      $lastPage = null;

      $retryCount = 0;
      RETRY_START: // retry start -----------------------------------
      try {
        do {
          $offerSearchResult = $alibabaProcess->apiGetCompanyProducts($company->getMemberId(), $page, $pageSize);

          // 最初に最大ページ数を取得
          if (is_null($lastPage)) {

            $this->results['totalCount'] = $offerSearchResult->getTotal();
            if (is_null($this->results['totalCount'])) { // 通信失敗？
              throw new \RuntimeException('total count is null. retry.');
            }

            $lastPage = ceil($this->results['totalCount'] / $pageSize);
          }

          $logger->info(print_r($offerSearchResult->getReturnData(), true));
          throw new \RuntimeException('DEBUG STOP');

          /** @var Offer[] $offers */
          $offers = $offerSearchResult->getHydratedList();

          // 一括 insert or update
          $insertBuilder = new MultiInsertUtil("tb_1688_product", [
            'fields' => [
                'offer_id'                      => \PDO::PARAM_INT
              , 'details_url'                   => \PDO::PARAM_STR
              , 'member_id'                     => \PDO::PARAM_STR
              , 'type'                          => \PDO::PARAM_STR
              , 'offer_status'                  => \PDO::PARAM_STR
              , 'subject'                       => \PDO::PARAM_STR
              , 'quality_level'                 => \PDO::PARAM_INT
              , 'trade_type'                    => \PDO::PARAM_INT
              , 'post_categry_id'               => \PDO::PARAM_INT
              , 'unit'                          => \PDO::PARAM_STR
              , 'price_unit'                    => \PDO::PARAM_STR
              , 'amount'                        => \PDO::PARAM_INT
              , 'amount_on_sale'                => \PDO::PARAM_INT
              , 'saled_count'                   => \PDO::PARAM_INT
              , 'product_unit_weight'           => \PDO::PARAM_STR
              , 'freight_type'                  => \PDO::PARAM_STR
              , 'term_offer_process'            => \PDO::PARAM_INT
              , 'is_private'                    => \PDO::PARAM_INT
              , 'is_private_offer'              => \PDO::PARAM_INT
              , 'is_price_auth_offer'           => \PDO::PARAM_INT
              , 'is_pic_auth_offer'             => \PDO::PARAM_INT
              , 'is_offer_support_online_trade' => \PDO::PARAM_INT
              , 'is_sku_offer'                  => \PDO::PARAM_INT
              , 'is_sku_trade_supported'        => \PDO::PARAM_INT
              , 'is_support_mix'                => \PDO::PARAM_INT
              , 'gmt_create'                    => \PDO::PARAM_STR
              , 'gmt_modified'                  => \PDO::PARAM_STR
              , 'gmt_last_repost'               => \PDO::PARAM_STR
              , 'gmt_approved'                  => \PDO::PARAM_STR
              , 'gmt_expire'                    => \PDO::PARAM_STR
            ]
            , 'prefix' => "INSERT INTO"
            , 'postfix' => " ON DUPLICATE KEY UPDATE "
                          . "     details_url                   = VALUES(details_url) "
                          . "   , member_id                     = VALUES(member_id) "
                          . "   , type                          = VALUES(type) "
                          . "   , offer_status                  = VALUES(offer_status) "
                          . "   , subject                       = VALUES(subject) "
                          . "   , quality_level                 = VALUES(quality_level) "
                          . "   , trade_type                    = VALUES(trade_type) "
                          . "   , post_categry_id               = VALUES(post_categry_id) "
                          . "   , unit                          = VALUES(unit) "
                          . "   , price_unit                    = VALUES(price_unit) "
                          . "   , amount                        = VALUES(amount) "
                          . "   , amount_on_sale                = VALUES(amount_on_sale) "
                          . "   , saled_count                   = VALUES(saled_count) "
                          . "   , product_unit_weight           = VALUES(product_unit_weight) "
                          . "   , freight_type                  = VALUES(freight_type) "
                          . "   , term_offer_process            = VALUES(term_offer_process) "
                          . "   , is_private                    = VALUES(is_private) "
                          . "   , is_private_offer              = VALUES(is_private_offer) "
                          . "   , is_price_auth_offer           = VALUES(is_price_auth_offer) "
                          . "   , is_pic_auth_offer             = VALUES(is_pic_auth_offer) "
                          . "   , is_offer_support_online_trade = VALUES(is_offer_support_online_trade) "
                          . "   , is_sku_offer                  = VALUES(is_sku_offer) "
                          . "   , is_sku_trade_supported        = VALUES(is_sku_trade_supported) "
                          . "   , is_support_mix                = VALUES(is_support_mix) "
                          . "   , gmt_create                    = VALUES(gmt_create) "
                          . "   , gmt_modified                  = VALUES(gmt_modified) "
                          . "   , gmt_last_repost               = VALUES(gmt_last_repost) "
                          . "   , gmt_approved                  = VALUES(gmt_approved) "
                          . "   , gmt_expire                    = VALUES(gmt_expire) "
          ]);

          $commonUtil->multipleInsert($insertBuilder, $dbMain, $offers, function($offer) use ($logger) {

            /** @var Offer $offer */

            $item = [
                'offer_id'                      => $offer->offerId
              , 'details_url'                   => $offer->detailsUrl
              , 'member_id'                     => $offer->memberId
              , 'type'                          => $offer->type
              , 'offer_status'                  => $offer->offerStatus
              , 'subject'                       => $offer->subject
              , 'quality_level'                 => $offer->qualityLevel
              , 'trade_type'                    => $offer->tradeType
              , 'post_categry_id'               => $offer->postCategryId
              , 'unit'                          => $offer->unit
              , 'price_unit'                    => $offer->priceUnit
              , 'amount'                        => $offer->amount
              , 'amount_on_sale'                => $offer->amountOnSale
              , 'saled_count'                   => $offer->saledCount
              , 'product_unit_weight'           => $offer->productUnitWeight
              , 'freight_type'                  => $offer->freightType
              , 'term_offer_process'            => $offer->termOfferProcess
              , 'is_private'                    => $offer->isPrivate
              , 'is_private_offer'              => $offer->isPrivateOffer
              , 'is_price_auth_offer'           => $offer->isPriceAuthOffer
              , 'is_pic_auth_offer'             => $offer->isPicAuthOffer
              , 'is_offer_support_online_trade' => $offer->isOfferSupportOnlineTrade
              , 'is_sku_offer'                  => $offer->isSkuOffer
              , 'is_sku_trade_supported'        => $offer->isSkuTradeSupported
              , 'is_support_mix'                => $offer->isSupportMix
              , 'gmt_create'                    => $offer->getGmtCreateJst()     ? $offer->getGmtCreateJst()->format('Y-m-d H:i:s') : null
              , 'gmt_modified'                  => $offer->getGmtModifiedJst()   ? $offer->getGmtModifiedJst()->format('Y-m-d H:i:s') : null
              , 'gmt_last_repost'               => $offer->getGmtLastRepostJst() ? $offer->getGmtLastRepostJst()->format('Y-m-d H:i:s') : null
              , 'gmt_approved'                  => $offer->getGmtApprovedJst()   ? $offer->getGmtApprovedJst()->format('Y-m-d H:i:s') : null
              , 'gmt_expire'                    => $offer->getGmtExpireJst()     ? $offer->getGmtExpireJst()->format('Y-m-d H:i:s') : null
            ];

            $logger->info(print_r($offer->skuArray, true));

            return $item;

          }, 'foreach');

          $this->results['fetchCount'] += count($offers);

          $logger->info(sprintf('保存 %d 件 ( %d / %d )', count($offers), $page, $lastPage));
          sleep(self::WAIT); // APIを実行したら必ずsleep

          $page++;

        } while ($lastPage === null || $page <= $lastPage);

        $logger->info(sprintf('アリババ企業商品一括更新 終了: %s : %s', $company->getMemberId(), $company->getCompanyName()));

      } catch (\Exception $e) {
        $logger->error($e->getMessage());

        if ($e instanceof NeverRetryException) {
          $logger->error('リトライ不可例外により、処理を終了します。');
          throw $e;
        }

        if ($retryCount++ > self::RETRY_LIMIT) {
          $logger->error('リトライ回数の上限を超過。処理を終了します。');
          throw $e;
        }

        sleep(self::RETRY_WAIT);
        $logger->info('リトライ回数 ' . $retryCount . ' / ' . self::RETRY_LIMIT);

        goto RETRY_START; // return to retry start -----------------------------------
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results), false);
      $logger->logTimerFlush();

      $logger->info('アリババ企業商品一括更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('アリババ企業商品一括更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('アリババ企業商品一括更新処理 エラー', 'アリババ企業商品一括更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , false // true // FOR DEBUG
        , 'アリババ企業商品一括更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}
