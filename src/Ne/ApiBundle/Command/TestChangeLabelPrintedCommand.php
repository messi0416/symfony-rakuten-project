<?php
/**
 * 配送方法出力済み 変更機能 テストコマンド
 * User: hirai
 */

namespace Ne\ApiBundle\Command;


use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class TestChangeLabelPrintedCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('ne:test-change-label-printed')
      ->setDescription('check api')
      ->addArgument('voucher-number', InputArgument::REQUIRED, '伝票番号')
      ->addArgument('flag', InputArgument::REQUIRED, '0：配送情報出力対象にする, 1：配送情報出力済みにする')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    // $logger = $this->getContainer()->get('logger');
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $commonUtil = $this->getDbCommonUtil();

    // ---------------------------------------------
    // API データ取得処理 開始

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

    $client->log('create instance.');
    $client->log('access_token: ' . $client->_access_token);
    $client->log('refresh_token: ' . $client->_refresh_token);

    ////////////////////////////////////////////////////////////////////////////////
    // 受注情報を取得し、変更する
    ////////////////////////////////////////////////////////////////////////////////
    $voucherNumber = $input->getArgument('voucher-number');
    $flag = $input->getArgument('flag');


    $fields = [
        'receive_order_id' // 更新不可
      , 'receive_order_creation_date' // 更新不可
      , 'receive_order_last_modified_date' // 更新不可
      , 'receive_order_shop_id'
      , 'receive_order_shop_cut_form_id'
      , 'receive_order_date'
      , 'receive_order_delivery_method_id'
    ];

    $query = array() ;

    // 検索結果のフィールド指定
    $query['fields'] = implode(',', $fields) ;
    $query['receive_order_id-eq'] = $voucherNumber;
    $query['offset'] = '0' ;

    // アクセス制限中はアクセス制限が終了するまで待つ。
    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
    $query['wait_flag'] = '1' ;

    // 検索実行
    $receives = $client->apiExecute('/api_v1_receiveorder_row/search', $query) ;

    if ($receives['result'] != 'success') {
      $logger->info(print_r($receives, true));
      $message = 'NE APIエラー';
      if (isset($receives['code'])) {
        $message = sprintf('[%s] ', $receives['code']);
      }
      if (isset($receives['message'])) {
        $message .= $receives['message'];
      }

      throw new \RuntimeException($message);
    }

    if ($receives['result'] != 'success' || $receives['count'] == 0) {
      $logger->info('no data!!');
      $output->writeln('no data.');
      return 1;
    }

    $voucher = $receives['data'][0];
    if ($voucher['receive_order_id'] !== $voucherNumber) {
      throw new \RuntimeException('invalid find result.');
    }


    $logger->info('受注情報更新！！');

    $fields = [
    ];
    $query = array() ;

    // 検索結果のフィールド指定
    $query['fields'] = implode(',', $fields) ;
    $query['receive_order_id'] = $voucherNumber;
    $query['receive_order_last_modified_date'] = $voucher['receive_order_last_modified_date'];
    // $query['receive_order_label_print_flag'] = boolval($flag) ? '2' : '1';
    $query['receive_order_label_print_flag'] = 1;

    $logger->dump($query);

    // アクセス制限中はアクセス制限が終了するまで待つ。
    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
    $query['wait_flag'] = '1' ;

    // 更新実行
    $receives = $client->apiExecute('/api_v1_receiveorder_base/labelprinted', $query) ;

    if ($receives['result'] != 'success') {
      $logger->info(print_r($receives, true));
      $message = 'NE APIエラー';
      if (isset($receives['code'])) {
        $message = sprintf('[%s] ', $receives['code']);
      }
      if (isset($receives['message'])) {
        $message .= $receives['message'];
      }

      throw new \RuntimeException($message);
    }

    if ($receives['result'] != 'success') {
      $logger->info('failed !!');
      $output->writeln('failed !!');
      return 1;
    }

    $logger->info(print_r($receives, true));


    $output->writeln('done!!');
    return 0;
  }

}
