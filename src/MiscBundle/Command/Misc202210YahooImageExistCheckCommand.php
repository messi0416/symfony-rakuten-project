<?php

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\Command\ExportCsvYahooCommand;
use BatchBundle\Command\ExportCsvYahooOtoriyoseCommand;
use MiscBundle\Exception\BusinessException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 
 */
class Misc202210YahooImageExistCheckCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;
  
  // Yahoo 店舗コード
  const SELLER_ID_PLUSNAO = 'plusnao';
  const SELLER_ID_KAWAEMON = 'kawa-e-mon';
  const SELLER_ID_OTORIYOSE = 'mignonlindo'; // おとりよせ.com
  
  public static $SELLER_IDS = [
    ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO => self::SELLER_ID_PLUSNAO
    , ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON => self::SELLER_ID_KAWAEMON
    // おとりよせ
    , ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE => self::SELLER_ID_OTORIYOSE
  ];
  
  const LOG_EXEC_TITLE = "Yahoo画像存在チェックテスト";
  
  /** @var  SymfonyUsers */
  private $account;
  
  // おとりよせ店舗（Agent店舗）フラグ
  private $isOtoriyose = false;
  
  
  protected function configure()
  {
    $this
    ->setName('misc:202210yahoo-image-exist-check')
    ->setDescription('Yahoo画像存在チェックテスト。')
    ->addOption('target-shop', null, InputOption::VALUE_OPTIONAL, '集計対象店舗。[plusnao|kawaemon|otoriyose]')
    ->addOption('target-product', null, InputOption::VALUE_OPTIONAL, '対象商品')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }
  
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /* 初期化処理 */
    
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->initLogTimer();
    $logger->info(self::LOG_EXEC_TITLE . " 開始");
    
    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    $account = null;
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }
    
    if (!$input->getOption('target-shop')) {
      throw new \RuntimeException(self::LOG_EXEC_TITLE . ' 対象店舗は必須です');
    } else if (!$input->getOption('target-product')) {
      throw new \RuntimeException(self::LOG_EXEC_TITLE . ' 対象商品は必須です');
    }
    
    $sellerId = self::$SELLER_IDS[$input->getOption('target-shop')];
    $logger->debug(self::LOG_EXEC_TITLE . "seller_id=$sellerId");
    
    $client = $this->getClientWithAccessToken($input->getOption('target-shop'));
    
    // 
    $url = "https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/itemImageList";
    $params = [
      'seller_id' => $sellerId
      , 'query' => $input->getOption('target-product')
    ];
    
    $fileUtil = $container->get('misc.util.file');
    $scrapingResponseDir = $fileUtil->getScrapingResponseDir();
    $client->request('get', $url . '?' . http_build_query($params));
    
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    
    $logger->debug("YahooAPI 画像一覧: [$status]");
    file_put_contents($scrapingResponseDir . '/yahoo_image_list_api_result.xml', $response->getContent());
    
    try {
      $logger->info(self::LOG_EXEC_TITLE . " 終了. 処理対象[$sellerId]");
    } catch (\Exception $e) {
      $logger->error(self::LOG_EXEC_TITLE . ' エラー:' . $e->getMessage() . ':' . $e->getTraceAsString());
      return 1;
    }
    return 0;
  }
  
  /**
   * Yahoo APIのアクセストークンを取得し、ヘッダに設定する client を生成し、返却する。
   * @throws \RuntimeException
   */
  private function getClientWithAccessToken($exportTarget)
  {
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getContainer()->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }
    $client = $webAccessUtil->getWebClient();
    
    // Yahoo API アクセストークン取得
    // おとりよせ
    if ($this->isOtoriyose) {
      
      /** @var SymfonyUserYahooAgentRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserYahooAgent');
      
      /** @var SymfonyUserYahooAgent $shopAccount */
      $shopAccount = $repo->getActiveShopAccountByShopCode($exportTarget);
      if (!$shopAccount) {
        throw new \RuntimeException('no account');
      }
      
      $auth = $webAccessUtil->getYahooAccessTokenWithRefresh($shopAccount);
      
      // 通常
    } else {
      $auth = $webAccessUtil->getYahooAccessTokenWithRefresh();
    }
    
    if (!$auth) {
      throw new \RuntimeException('Yahoo API のアクセストークンが取得できませんでした(WEBからの認証が必要です)。処理を終了します。');
    }
    $client->setHeader('Authorization', sprintf('Bearer %s', $auth->getAccessToken()));
    return $client;
  }

}