<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\Repository\SymfonyUserYahooAgentRepository;
use MiscBundle\Entity\SymfonyUserYahooAgent;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use YConnect\Constant\OIDConnectDisplay;
use YConnect\Constant\OIDConnectPrompt;
use YConnect\Constant\ResponseType;
use YConnect\Credential\ClientCredential;
use YConnect\YConnectClient;


class YahooApiOtoriyoseTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:yahoo-api-otoriyose-test')
      ->setDescription('Yahoo Api (おとりよせ.com) 接続試験');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;

    $logger = $this->getContainer()->get('misc.util.batch_logger');

    // do something
    // $this->baseTest();

    // WebAccessUtil でのテスト
    $this->webAccessUtilTest();

    $output->writeln('done!');
  }

  private function webAccessUtilTest()
  {
    $logger = $this->getLogger();

    /** @var SymfonyUserYahooAgentRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserYahooAgent');

    /** @var SymfonyUserYahooAgent $shopAccount */
    $shopAccount = $repo->getActiveShopAccountByShopCode('otoriyose');
    if (!$shopAccount) {
      throw new \RuntimeException('no account');
    }

    $webAccessUtil = $this->getWebAccessUtil();

    // 本番環境用
    $logger->info('app id:' . $shopAccount->getAppId());
    $logger->info('app secret:' . $shopAccount->getAppSecret());

    $auth = $webAccessUtil->getYahooAccessTokenWithRefresh($shopAccount);
    if (!$auth) {
      throw new \RuntimeException('no auth.');
    }

    $client = $webAccessUtil->getWebClient();

    // カテゴリランキング
    $logger->info('yahoo api test カテゴリランキング');
    $url = 'https://shopping.yahooapis.jp/ShoppingWebService/V1/categoryRanking';
    $params = [
      'appid' => $shopAccount->getAppId()
    ];

    $url = $url . '?' . http_build_query($params);

    $client->request('GET', $url);
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    /** @var \Symfony\Component\BrowserKit\Request $request */
    $request = $client->getRequest();

    $logger->info($request->getUri());
    $logger->info('Yahoo api response: ' . $response->getStatus() . ' : ' . $response->getContent());

    // 成功時： status code 200 ※それ以外(401, 403)はエラー。これだけで判定して問題ない。
    $dom = simplexml_load_string($response->getContent());
    if ($response->getStatus() != 200) {
      $errorMessage = '';
      $logger->error(print_r($response->getContent(), true));

      foreach($dom->xpath('/Error') as $error) {
        $errorMessage .= $error->asXML();
      }

      throw new \RuntimeException('エラー: ' . $errorMessage);
    }
    $logger->info(sprintf('Yahoo [otoriyose] カテゴリランキングAPIテスト成功'));
    $logger->info(print_r($response->getContent(), true));

    /// ----------------------------------
    $authString = sprintf('Bearer %s', $auth->getAccessToken());
    $logger->info($authString);
    $client->setHeader('Authorization', $authString);

    // ユーザ権限 確認
    $logger->info('yahoo api test ユーザ権限 確認');
    $url = 'https://userinfo.yahooapis.jp/yconnect/v1/attribute';
    $params = [
      'schema' => 'openid'
    ];
    $client->request('POST', $url, $params);
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $logger->info('Yahoo api response: ' . $response->getStatus() . ' : ' . $response->getContent());

    // 商品情報取得
    $logger->info('yahoo api test 商品情報取得');
    $url = 'https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/getItem';
    $params = [
        'seller_id' => 'mignonlindo'
      , 'item_code' => 'zl2030'
    ];

    $url = $url . '?' . http_build_query($params);
    $client->request('GET', $url);
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();

    $logger->info('Yahoo api response: ' . $response->getStatus() . ' : ' . $response->getContent());

    // 成功時： status code 200 ※それ以外(401, 403)はエラー。これだけで判定して問題ない。
    $dom = simplexml_load_string($response->getContent());
    if ($response->getStatus() != 200) {
      $errorMessage = '';
      $logger->error(print_r($response->getContent(), true));

      foreach($dom->xpath('/Error') as $error) {
        $errorMessage .= $error->asXML();
      }

      throw new \RuntimeException('エラー: ' . $errorMessage);
    }
    $logger->info(sprintf('Yahoo [otoriyose] 商品取得APIテスト成功'));
    $logger->info(print_r($response->getContent(), true));


    // 在庫CSVダウンロードリクエスト
    $logger->info('yahoo api test 商品情報取得');
    // $url = $this->getContainer()->getParameter('yahoo_api_url_shopping_download_request');
    $url = 'https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/downloadRequest'; // 本番環境
    $params = [
        'seller_id' => 'mignonlindo'
      , 'type' => '2' // 在庫
    ];

    $client->request('POST', $url, $params);
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();

    $logger->info('Yahoo api response: ' . $response->getStatus() . ' : ' . $response->getContent());

    // 成功時： status code 200 ※それ以外(401, 403)はエラー。これだけで判定して問題ない。
    $dom = simplexml_load_string($response->getContent());
    if ($response->getStatus() != 200) {
      $errorMessage = '';
      foreach($dom->xpath('/Error') as $error) {
        $errorMessage .= $error->asXML();
      }

      throw new \RuntimeException('エラー: ' . $errorMessage);
    }
    $logger->info(sprintf('Yahoo [otoriyose] CSVダウンロードリクエスト送信成功'));
  }

  private function baseTest()
  {
    $container = $this->getContainer();

    $doctrine = $container->get('doctrine');
    // var_dump(get_class($doctrine));

    $this->commonUtil = $container->get('misc.util.db_common');

    $fileUtil = $container->get('misc.util.file');
    $this->output->writeln($fileUtil->getRootDir());

    // アプリケーションID, シークレット
    $client_id     = "dj0zaiZpPXJtMGtqT3BEY0hrdyZzPWNvbnN1bWVyc2VjcmV0Jng9MmY-";
    $client_secret = "5017b7a714560e3e5648949baac3a3104018e934";

    // 各パラメータ初期化
    $redirect_uri = "https://starlight.plusnao.local/app_test.php/service_auth/yahoo_agent_callback/otoriyose";

    // リクエストとコールバック間の検証用のランダムな文字列を指定してください
    $state = "44Oq44Ki5YWF44Gr5L+644Gv44Gq44KL77yBhogehoge";
    // リプレイアタック対策のランダムな文字列を指定してください
    $nonce = "5YOV44Go5aWR57SE44GX44GmSUTljqjjgavjgarjgaPjgabjgoghogehoge=";

    $response_type = ResponseType::CODE_IDTOKEN;
    $scope = array(
      // OIDConnectScope::OPENID,
      // OIDConnectScope::PROFILE,
      // OIDConnectScope::EMAIL,
      // OIDConnectScope::ADDRESS
    );
    $display = OIDConnectDisplay::DEFAULT_DISPLAY;
    $prompt = array(
      OIDConnectPrompt::DEFAULT_PROMPT
    );

    // クレデンシャルインスタンス生成
    $cred = new ClientCredential( $client_id, $client_secret );
    // YConnectクライアントインスタンス生成
    $client = new YConnectClient( $cred );

    var_dump(get_class($client));

    // Authorizationエンドポイントにリクエスト
    $client->requestAuth(
      $redirect_uri,
      $state,
      $nonce,
      $response_type,
      $scope,
      $display,
      $prompt
    );

    var_dump(get_class_methods($client));
  }




}
