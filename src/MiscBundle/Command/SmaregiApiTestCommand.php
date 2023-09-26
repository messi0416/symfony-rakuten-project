<?php
/**
 * バッチ処理 スマレジAPIテスト処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\RealShopSmaregiMallProcess;
use MiscBundle\Entity\Repository\TbMainproductsRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbProductchoiceitems;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SmaregiApiTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:smaregi-api-test')
      ->setDescription('スマレジAPIテスト処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('スマレジAPIテスト処理を開始しました。');

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

      $logExecTitle = sprintf('スマレジAPIテスト処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      // $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $url = 'https://webapi.smaregi.jp/access/';
      $contractId = 'spu264y1';
      $accessToken = '8852e1508af780894d46cdd3dfb3b4f8';

      $webAccessUtil = $this->getWebAccessUtil();
      $client = $webAccessUtil->getWebClient();

      $client->setHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
      $client->setHeader('X_contract_id', $contractId);
      $client->setHeader('X_access_token', $accessToken);

      // カテゴリ一覧取得テスト
      // $this->getCategories($client, $url);

      // 商品登録・, $url
      // $this->registerProducts($client, $url);

      // 在庫更新テスト
      // $this->updateProductStocks($client, $url);

      // 商品削除テスト
      // $this->deleteProducts($client, $url);

      // 受注情報取得テスト
      // $this->getOrders($client, $url);

      // 実装用テスト：商品登録
      $this->registerProductsByMallProcess();




      // DB記録＆通知処理
      // $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('スマレジAPIテスト処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('スマレジAPIテスト処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('スマレジAPIテスト処理 エラー', 'スマレジAPIテスト処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'スマレジAPIテスト処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }

  /// カテゴリ一覧取得テスト
  /**
   * @param Client $client
   * @param string $url
   */
  private function getCategories($client, $url)
  {
    $logger = $this->getLogger();

    // 参照テスト
    $params = [
      'proc_name' => 'category_ref'
      , 'params' => json_encode([
          'fields' => ['categoryId', 'categoryName']
        , 'table_name' => 'Category'
      ])
    ];
    $client->request('POST', $url, $params);
    /** @var Request $request */
    $request =  $client->getRequest();
    /** @var Response $response */
    $response  = $client->getResponse();

    $logger->info(print_r($request->getParameters(), true));
    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getContent(), true));
    $logger->info(print_r(json_decode($response->getContent()), true));
  }

  /**
   * 商品登録テスト
   * @param Client $client
   * @param string $url
   */
  private function registerProducts($client, $url)
  {
    $logger = $this->getLogger();

    $products = [];

    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

    $products[] = $repo->find('HW287');
    $products[] = $repo->find('ti-s004');

    $data = [];

    // 商品マスタ
    $dataProducts = [
        'table_name' => 'Product'
      , 'rows' => []
    ];
    $dataShops = [
        'table_name' => 'ProductStore'
      , 'rows' => []
    ];

    $id = 101;

    /** @var TbMainproducts $product */
    foreach($products as $product) {

      /** @var TbProductchoiceitems  $choice */
      foreach($product->getChoiceItems() as $choice) {

        // 商品マスタ
        $dataProducts['rows'][] = [
            'productId' => $id
          , 'categoryId' => '1'
          , 'productName' => mb_substr($product->getDaihyoSyohinName(), 0, 85)
          , 'taxDivision' => 1
          , 'price' => $product->getCal()->getBaikaTnk()
          , 'size' => $choice->getSize()
          , 'color' => $choice->getColor()
          , 'groupCode' => $product->getDaihyoSyohinCode()
          , 'supplierProductNo' => $choice->getNeSyohinSyohinCode()
        ];

        // 取扱店舗
        $dataShops['rows'][] = [
            'productId' => $id
          , 'storeId' => 1 /* 店舗1: プラスナオ */
          , 'assignDivision' => 0 /* 販売する */
        ];

        $dataShops['rows'][] = [
            'productId' => $id
          , 'storeId' => 2 /* 店舗2: 南京終倉庫 */
          , 'assignDivision' => 1 /* 販売しない */
        ];

        $id++;
      }
    }

    $data[] = $dataProducts;
    $data[] = $dataShops;

    $logger->info(print_r($data, true));

    $params = [
      'proc_info' => [
        'proc_division' => 'U'
      ]
      , 'data' => $data
    ];

    $sendData = [
        'proc_name' => 'product_upd'
      , 'params' => json_encode($params)
    ];

    $client->request('POST', $url, $sendData);
    /** @var Request $request */
    $request =  $client->getRequest();
    /** @var Response $response */
    $response  = $client->getResponse();

    // $logger->info(print_r($request->getParameters(), true));
    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getContent(), true));
    $logger->info(print_r(json_decode($response->getContent()), true));
  }

  /**
   * 在庫更新テスト
   * @param Client $client
   * @param string $url
   */
  private function updateProductStocks($client, $url)
  {
    $logger = $this->getLogger();

    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

    $products = [];
    $products[] = $repo->find('HW287');
    $products[] = $repo->find('ti-s004');

    $data = [];

    $dataStocks = [
        'table_name' => 'Stock'
      , 'rows' => []
    ];

    $id = 101;

    /** @var TbMainproducts $product */
    foreach($products as $product) {

      /** @var TbProductchoiceitems  $choice */
      foreach($product->getChoiceItems() as $choice) {

        // 商品マスタ
        $dataStocks['rows'][] = [
            'storeId' => 1 // 店舗1: プラスナオ
          , 'productId' => $id
          , 'stockAmount' => $choice->getFreeStock()
          , 'stockDivision' => 15 // 在庫区分
        ];

        $id++;
      }
    }

    $data[] = $dataStocks;

    $logger->info(print_r($data, true));

    $params = [
      'proc_info' => [
          'proc_division' => 'U'
        , 'proc_detail_division' => '1' // 絶対値
      ]
      , 'data' => $data
    ];

    $sendData = [
      'proc_name' => 'stock_upd'
      , 'params' => json_encode($params)
    ];

    $client->request('POST', $url, $sendData);
    /** @var Request $request */
    $request =  $client->getRequest();
    /** @var Response $response */
    $response  = $client->getResponse();

    // $logger->info(print_r($request->getParameters(), true));
    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getContent(), true));
    $logger->info(print_r(json_decode($response->getContent()), true));
  }

  /**
   * 商品削除テスト
   * @param Client $client
   * @param string $url
   */
  private function deleteProducts($client, $url)
  {
    $logger = $this->getLogger();

    $products = [];

    /** @var TbMainproductsRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbMainproducts');

    $data = [];

    // 商品マスタ
    $dataProducts = [
      'table_name' => 'Product'
      , 'rows' => []
    ];

    for ($id = 1; $id <= 12; $id++) {
      // 商品マスタ
      $dataProducts['rows'][] = [
        'productId' => $id
      ];
    }
    $data[] = $dataProducts;

    $logger->info(print_r($data, true));

    $params = [
      'proc_info' => [
        'proc_division' => 'D'
      ]
      , 'data' => $data
    ];

    $sendData = [
      'proc_name' => 'product_upd'
      , 'params' => json_encode($params)
    ];

    $client->request('POST', $url, $sendData);
    /** @var Request $request */
    $request =  $client->getRequest();
    /** @var Response $response */
    $response  = $client->getResponse();

    // $logger->info(print_r($request->getParameters(), true));
    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getContent(), true));
    $logger->info(print_r(json_decode($response->getContent()), true));
  }

  /**
   * 受注情報取得テスト
   * @param Client $client
   * @param string $url
   */
  private function getOrders($client, $url)
  {
    $logger = $this->getLogger();

    // 参照テスト
    $params = [
      'proc_name' => 'transaction_ref'
      , 'params' => json_encode([
          'table_name' => 'TransactionHead'
        // , 'fields' => ['categoryId', 'categoryName']
      ])
    ];
    $client->request('POST', $url, $params);
    /** @var Request $request */
    $request =  $client->getRequest();
    /** @var Response $response */
    $response  = $client->getResponse();

    $logger->info(print_r($request->getParameters(), true));
    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getContent(), true));
    $logger->info(print_r(json_decode($response->getContent()), true));

    // 詳細
    $params = [
      'proc_name' => 'transaction_ref'
      , 'params' => json_encode([
        'table_name' => 'TransactionDetail'
        // , 'fields' => ['categoryId', 'categoryName']
      ])
    ];
    $client->request('POST', $url, $params);
    /** @var Request $request */
    $request =  $client->getRequest();
    /** @var Response $response */
    $response  = $client->getResponse();

    $logger->info(print_r($request->getParameters(), true));
    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getContent(), true));
    $logger->info(print_r(json_decode($response->getContent()), true));

  }

  /**
   *
   */
  private function registerProductsByMallProcess()
  {
    $codeList = [
        'akf-SW1709-BE-M'
      , 'akf-SW1656-L-BK'
      , 'akf-INSD1601-140150-B'
    ];

    /** @var RealShopSmaregiMallProcess $process */
    $process = $this->getContainer()->get('batch.mall_process.smaregi');

    $process->registerProducts($codeList);

  }


}


