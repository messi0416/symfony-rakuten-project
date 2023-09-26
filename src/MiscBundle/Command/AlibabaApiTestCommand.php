<?php
/**
 * Alibaba API利用テスト処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use forestlib\AlibabaSdk\Plusnao\Facade\OfferFacade;
use MiscBundle\Entity\SymfonyUsers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AlibabaApiTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:alibaba-api-test')
      ->setDescription('Alibaba API利用テスト処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Alibaba API利用テスト処理を開始しました。');

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

      $logExecTitle = sprintf('Alibaba API利用テスト処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $config = [
          'appKey' => '8462834'
        , 'secKey' => '7szMSnBUwGR'
        , 'serverHost' => 'gw.open.1688.com'
      ];

      //you need change this refresh token when you run this example.
      $testRefreshToken ="6291ea7b-8658-4cea-9e45-b880d66e2d11";

      // 会社情報取得 テスト
//      $companyFacade = new CompanyFacade($config);
//      $company = $companyFacade->getCompany('b2b-2515131606');

//      var_dump($company);


      // 商品情報取得 テスト
      $offerFacade = new OfferFacade($config);
      $offer = $offerFacade->getOffer('1179846202');

      var_dump($offer);
//
//      // 会社商品取得テスト
//      if ($offer && $offer->memberId) {
//
//        $param = new OfferSearchParam();
//        $param->setMemberId($offer->memberId);
//        $param->setPageNo(1);
//        $param->setPageSize(20); // 20 ～ 200
//        $param->setOrderBy('gmt_create:asc');
//        $param->setStatus('published');
//
//        $offerSearchResult = $offerFacade->searchOffers($param);
//
//        var_dump($offerSearchResult->getTotal());
//        var_dump($offerSearchResult->isSuccess());
//
//        /** @var Offer $companyOffer */
//        foreach($offerSearchResult->getHydratedList() as $companyOffer) {
//          var_dump(sprintf('%s : %s : %s', $companyOffer->offerId, $companyOffer->detailsUrl, $companyOffer->subject));
//        }
//      }

      // 商品情報（product）取得テスト
      // get access token
      // アクセストークンは、どうやらログインしたブラウザからしか取得できない模様？（クッキーか何かか）

      // 商品情報取得
      // $config['accessToken'] = '6e650e17-a149-492d-a2b7-e67280fb323d';
      // $config['refreshToken'] = '1b62eb6c-e00e-4ade-a8a5-9684e2cdd061';

      // $productFacade = new ProductFacade($config);

      // $product = $productFacade->getProduct('543494979619');

      // var_dump($product);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('Alibaba API利用テスト処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Alibaba API利用テスト処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Alibaba API利用テスト処理 エラー', 'Alibaba API利用テスト処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Alibaba API利用テスト処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


