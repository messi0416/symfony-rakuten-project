<?php
/**
 * 受注情報取得機能 テストコマンド
 * User: hirai
 */

namespace Ne\ApiBundle\Command;


use BatchBundle\Command\CommandBaseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class TestFetchOrderCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('ne:test-fetch-order')
      ->setDescription('check api')
      ->addOption('order-num', null, InputOption::VALUE_OPTIONAL, '伝票番号', null)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger LoggerInterface */
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
    // 受注情報を取得するサンプル
    ////////////////////////////////////////////////////////////////////////////////
    $fields = [
        'receive_order_id' // 更新不可
      , 'receive_order_creation_date' // 更新不可
      , 'receive_order_last_modified_date' // 更新不可
      , 'receive_order_shop_id'
      , 'receive_order_shop_cut_form_id'
      , 'receive_order_date'

      , 'receive_order_confirm_ids'
      , 'receive_order_confirm_check_id'
      , 'receive_order_gruoping_tag'
      , 'receive_order_cancel_type_id'
      , 'receive_order_delivery_id'
      , 'receive_order_payment_method_id'
      , 'receive_order_goods_amount'
      , 'receive_order_tax_amount'
      , 'receive_order_charge_amount'
      , 'receive_order_delivery_fee_amount'
      , 'receive_order_other_amount'
      , 'receive_order_point_amount'
      , 'receive_order_total_amount'
      , 'receive_order_deposit_amount'
      , 'receive_order_deposit_type_id'
      , 'receive_order_deposit_date'
      , 'receive_order_note'
      , 'receive_order_statement_delivery_instruct_printing_date'
      , 'receive_order_statement_delivery_text'
      , 'receive_order_worker_text'
      , 'receive_order_picking_instruct'
      , 'receive_order_hope_delivery_date'
      , 'receive_order_hope_delivery_time_slot_id'
      , 'receive_order_delivery_method_id'
      , 'receive_order_business_office_stop_id'
      , 'receive_order_invoice_id'
      , 'receive_order_temperature_id'
      , 'receive_order_seal1_id'
      , 'receive_order_seal2_id'
      , 'receive_order_seal3_id'
      , 'receive_order_seal4_id'
      , 'receive_order_gift_flag'
      , 'receive_order_delivery_cut_form_id'
      , 'receive_order_delivery_cut_form_note'
      , 'receive_order_credit_type_id'
      , 'receive_order_credit_approval_no'
      , 'receive_order_credit_approval_amount'
      , 'receive_order_credit_approval_type_id'
      , 'receive_order_credit_approval_type_name'
      , 'receive_order_credit_approval_date'
      , 'receive_order_customer_type_id'
      , 'receive_order_customer_id'
      , 'receive_order_purchaser_name'
      , 'receive_order_purchaser_kana'
      , 'receive_order_purchaser_zip_code'
      , 'receive_order_purchaser_address1'
      , 'receive_order_purchaser_address2'
      , 'receive_order_purchaser_tel'
      , 'receive_order_purchaser_mail_address'
      , 'receive_order_consignee_name'
      , 'receive_order_consignee_kana'
      , 'receive_order_consignee_zip_code'
      , 'receive_order_consignee_address1'
      , 'receive_order_consignee_address2'
      , 'receive_order_consignee_tel'
      , 'receive_order_important_check_id'
      , 'receive_order_statement_delivery_printing_date'
      , 'receive_order_credit_number_payments'
      , 'receive_order_send_plan_date'

      , 'receive_order_row_no'
      , 'receive_order_row_shop_row_no'
      , 'receive_order_row_goods_id'
      , 'receive_order_row_goods_name'
      , 'receive_order_row_quantity'
      , 'receive_order_row_unit_price'
      , 'receive_order_row_received_time_first_cost'
      , 'receive_order_row_wholesale_retail_ratio'
      , 'receive_order_row_sub_total_price'
      , 'receive_order_row_goods_option'
      , 'receive_order_row_cancel_flag'
      , 'receive_order_include_from_order_id'
      , 'receive_order_include_from_row_no'
      , 'receive_order_row_multi_delivery_parent_order_id'
      , 'receive_order_row_divide_from_row_no'
      , 'receive_order_row_copy_from_row_no'
      , 'receive_order_row_stock_allocation_quantity'
      , 'receive_order_row_advance_order_stock_allocation_quantity'
      , 'receive_order_row_stock_allocation_date'
      , 'receive_order_row_received_time_merchandise_id'
      , 'receive_order_row_received_time_merchandise_name'
      , 'receive_order_row_received_time_goods_type_id'
      , 'receive_order_row_received_time_goods_type_name'
      , 'receive_order_row_returned_good_quantity'
      , 'receive_order_row_returned_bad_quantity'
      , 'receive_order_row_returned_reason_id'
      , 'receive_order_row_returned_reason_name'
      , 'receive_order_row_org_row_no'
      , 'receive_order_row_deleted_flag'

      , 'receive_order_row_creation_date'
      , 'receive_order_row_last_modified_date'
      , 'receive_order_row_last_modified_null_safe_date'
      , 'receive_order_row_last_modified_newest_date'
    ];

    $query = array() ;

    // 検索結果のフィールド指定
    $query['fields'] = implode(',', $fields) ;

    if ($input->getOption('order-num')) {
      $query['receive_order_id-eq'] = $input->getOption('order-num');

    } else {
      // $query['receive_order_row_creation_date-gt'] = '2016-06-27 15:39:12';
      // $query['receive_order_row_last_modified_newest_date-gt'] = '2010-01-01';
      $query['receive_order_row_last_modified_null_safe_date-gt'] = '2018-08-08 17:50:00';
    }

    $query['offset'] = '0' ;
    // $query['limit'] = '3' ;

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

    var_dump($client->_access_token);
    var_dump($client->_refresh_token);

    $logger->info(print_r($receives, true));

//    $query = array() ;
//
//    // 検索結果のフィールド指定
//    $query['fields'] = implode(',', $fields) ;
//    $query['receive_order_id-eq'] = '1';
//    $query['offset'] = '0' ;
//    // $query['limit'] = '50' ;
//
//    // アクセス制限中はアクセス制限が終了するまで待つ。
//    // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
//    $query['wait_flag'] = '1' ;
//
//    // 検索実行
//    $receives = $client->apiExecute('/api_v1_receiveorder_base/search', $query) ;
//    if ($receives['result'] != 'success' || $receives['count'] == 0) {
//      $output->writeln('no data.');
//      return 1;
//    }
//
//    var_dump($client->_access_token);
//    var_dump($client->_refresh_token);
//
//    var_dump($receives);


    $output->writeln('done!!');
    return 0;
  }

}
