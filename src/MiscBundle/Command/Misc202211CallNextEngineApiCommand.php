<?php
namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class Misc202211CallNextEngineApiCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
    ->setName('misc:misc-202211-call-next-negine-api')
    ->setDescription('NextEngine納品書印刷済み更新API呼び出しテスト')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('voucher-number',  null, InputOption::VALUE_OPTIONAL, '対象の伝票番号（カンマ区切り）')
    ;
  }

  /**
   *
   *
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    
    $commonUtil = $this->getDbCommonUtil();
    
    // ---------------------------------------------
    // API 納品書印刷済み一括更新　実行
    
    // 前準備
    $apiInfo = $this->getContainer()->getParameter('ne_api');
    $clientId = $apiInfo['client_id'];
    $clientSecret = $apiInfo['client_secret'];
    $redirectUrl = $apiInfo['redirect_url'];
    
    $accessToken = $commonUtil->getSettingValue('NE_API_ACCESS_TOKEN');
    if (!$accessToken) {
      $accessToken = null;
    }
    $refreshToken = $commonUtil->getSettingValue('NE_API_REFRESH_TOKEN');
    if (!$refreshToken) {
      $refreshToken = null;
    }
    
    $client = new \ForestNeApiClient($clientId, $clientSecret, $redirectUrl, $accessToken, $refreshToken);
    $client->setLogger($logger);
    
    $loginId = $commonUtil->getSettingValue('NE_API_LOGIN_ID');
    $loginPassword = $commonUtil->getSettingValue('NE_API_LOGIN_PASSWORD');
    
    $client->setUserAccount($loginId, $loginPassword);
    
    $client->log('納品書印刷済み更新APIテスト：　create instance.');
    $client->log('納品書印刷済み更新APIテスト：　access_token: ' . $client->_access_token);
    $client->log('納品書印刷済み更新APIテスト：　refresh_token: ' . $client->_refresh_token);
    
    // 指定された伝票それぞれ、更新日時を取得
    $voucherNumbers = $input->getOption('voucher-number');
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $sql = "SELECT 伝票番号, MAX(NE側更新日時) as ne_updated FROM tb_sales_detail WHERE 伝票番号 IN ({$voucherNumbers}) GROUP BY 伝票番号";
    $stmt = $dbMain->query($sql);
    $stmt->execute();
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $logger->debug("納品書印刷済み更新APIテスト：　対象伝票[ $voucherNumbers ]");
    
    // リクエストデータ準備 XMLデータを作成する
    
    $receiveorders = ["receiveorder" => []];
    $i = 0;
    foreach ($list as $data) {
      $receiveorders["receiveorder"][$i] = [];
      $receiveorders["receiveorder"][$i]["@receive_order_id"] = $data['伝票番号']; // @を付けると属性
      $receiveorders["receiveorder"][$i]["@receive_order_last_modified_date"] = $data['ne_updated'];
      $receiveorders["receiveorder"][$i]["receive_order_label_print_flag"] = "1";
      $i++;
    }
    
    $encoders = array(new XmlEncoder(), new XmlEncoder());
    $normalizers = array(new GetSetMethodNormalizer());
    $serializer = new Serializer($normalizers, $encoders);
    
    $context = [
      'xml_root_node_name' => 'root'
      , 'xml_format_output' => true
      , 'xml_encoding' => 'UTF-8'
    ];
    
    $updateXml = $serializer->serialize($receiveorders, 'xml', $context);
    
    $logger->debug("納品書印刷済み更新APIテスト：　出力XML[ $updateXml ]");
    
    $query = [];
    
    // アクセス制限中はアクセス制限が終了するまで待つ。
    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
    $query['wait_flag'] = '1' ;
    
    // データ形式XML
    $query['data_type'] = 'xml';
    $query['data'] = $updateXml;
    
    $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
    
    // 更新実行
    $result = $client->apiExecute('/api_v1_receiveorder_base/bulkupdatereceipted', $query) ;
    file_put_contents($scrapingResponseDir . '/next_engine_test_bulkupdatereceipted_result', print_r($result, true));
    
    $logger->debug("納品書印刷済み更新APIテスト：　result" . print_r($result['result'], true));
    $logger->debug("納品書印刷済み更新APIテスト：　message" . print_r($result['message'], true));

    $logger->info("納品書印刷済み更新APIテスト： 終了");
    
    // アクセストークン・リフレッシュトークンの保存
    $commonUtil->updateSettingValue('NE_API_ACCESS_TOKEN', $client->_access_token);
    $commonUtil->updateSettingValue('NE_API_REFRESH_TOKEN', $client->_refresh_token);
  }
}