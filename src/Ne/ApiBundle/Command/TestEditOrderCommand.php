<?php
/**
 * 受注編集機能 テストコマンド
 * User: hirai
 * Date: 2015/11/27
 * Time: 15:09
 */

namespace Ne\ApiBundle\Command;


use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;


class TestEditOrderCommand extends ContainerAwareCommand
{
    const CLIENT_ID = '1zmP7Rn8Wrqi9V';
    const CLIENT_SECRET = 'CgHtzJuNs1IhaKybRLSkocTd7VpEBY8Ae3OZjG9Q';

    const REDIRECT_URL = 'https://forest.plusnao.co.jp/callback.php';

    protected function configure()
    {
        $this
            ->setName('api:test-edit-order')
            ->setDescription('check api')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $logger LoggerInterface */
        $logger = $this->getContainer()->get('logger');

        // この値を「アプリを作る->API->テスト環境設定」の値に更新して下さい。
        // (アプリを販売する場合は本番環境設定の値に更新して下さい)
        // このサンプルでは、利用者情報とマスタ情報にアクセスするため、許可して下さい。

        // 本SDKは、ネクストエンジンログインが必要になるとネクストエンジンのログイン画面に
        // リダイレクトします。ログイン成功後に、リダイレクトしたい
        // アプリケーションサーバーのURIを指定して下さい。
        // 呼び出すAPI毎にリダイレクト先を変更したい場合は、apiExecuteの引数に指定して下さい。
        // $pathinfo = pathinfo(strtok($_SERVER['REQUEST_URI'],'?')) ;
        // $redirect_uri = 'https://'.$_SERVER['HTTP_HOST'].$pathinfo['dirname'].'/'.$pathinfo['basename'] ;

        $client = new \ForestNeApiClient(self::CLIENT_ID, self::CLIENT_SECRET, self::REDIRECT_URL) ;
        $client->setLogger($logger);

        // TODO 設定ファイルその他から取得する
        $client->setUserAccount('forest.api', 'ipa.tserof0');

        $client->log('create instance.');

        ////////////////////////////////////////////////////////////////////////////////
        // 受注情報を取得するサンプル
        ////////////////////////////////////////////////////////////////////////////////
        $fields = [
            'receive_order_id' // 更新不可
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
        ];

        $query = array() ;

        // 検索結果のフィールド指定
        $query['fields'] = implode(',', $fields) ;
        $query['receive_order_id-eq'] = '2';
        $query['offset'] = '0' ;
        $query['limit'] = '50' ;

        // アクセス制限中はアクセス制限が終了するまで待つ。
        // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
        $query['wait_flag'] = '1' ;

        // 検索実行
        $receives = $client->apiExecute('/api_v1_receiveorder_base/search', $query) ;
        if ($receives['result'] != 'success' || $receives['count'] == 0) {
          $output->writeln('no data.');
          return 1;
        }

        $receive = $receives['data'][0];

        $updateReceive = $receive; // ひとまずざっくり
        unset($updateReceive['receive_order_id']); // 更新不可
        unset($updateReceive['receive_order_last_modified_date']); // 更新不可

        if ($updateReceive['receive_order_statement_delivery_instruct_printing_date'] == '0000-00-00 00:00:00') {
          unset($updateReceive['receive_order_statement_delivery_instruct_printing_date']);
        }

        $updateReceive['receive_order_shop_id'] .= ' Edited';
        $updateReceive['receive_order_shop_cut_form_id'] .= ' Edited';
        // $updateReceive['receive_order_date'] .= ' Edited';
        // $updateReceive['receive_order_confirm_ids'] .= ' Edited';
        $updateReceive['receive_order_confirm_check_id'] = $updateReceive['receive_order_confirm_check_id'] ? 0 : 1;
        $updateReceive['receive_order_gruoping_tag'] .= '[受注]';
        $updateReceive['receive_order_cancel_type_id'] = $updateReceive['receive_order_cancel_type_id'] ? 0 : 1;
        $updateReceive['receive_order_delivery_id'] = 80;
        $updateReceive['receive_order_payment_method_id'] = 30;
        $updateReceive['receive_order_goods_amount'] += 1;
        $updateReceive['receive_order_tax_amount'] += 100;
        $updateReceive['receive_order_charge_amount'] += 10;
        $updateReceive['receive_order_delivery_fee_amount'] += 10;
        $updateReceive['receive_order_other_amount'] += 10;
        $updateReceive['receive_order_point_amount'] += 10;
        $updateReceive['receive_order_total_amount'] += 1000;
        $updateReceive['receive_order_deposit_amount'] += 1;
        $updateReceive['receive_order_deposit_type_id'] = $updateReceive['receive_order_deposit_type_id'] ? 0 : 1;
        $updateReceive['receive_order_deposit_date'] = (new \DateTime())->format('Y-m-d H:i:s');
        $updateReceive['receive_order_note'] .= "\n" . ' Edited ※ここが一番大事！';
        $updateReceive['receive_order_statement_delivery_instruct_printing_date'] = (new \DateTime())->format('Y-m-d H:i:s');
        $updateReceive['receive_order_statement_delivery_text'] .= ' Edited';
        $updateReceive['receive_order_worker_text'] .= ' Edited';
        $updateReceive['receive_order_picking_instruct'] .= ' Edited';
        $updateReceive['receive_order_hope_delivery_date'] = (new \DateTime())->format('Y-m-d H:i:s');
        $updateReceive['receive_order_hope_delivery_time_slot_id'] .= ' Edited';
        $updateReceive['receive_order_delivery_method_id'] .= ' Edited';
        $updateReceive['receive_order_business_office_stop_id'] .= ' Edited';
        $updateReceive['receive_order_invoice_id'] .= ' Edited';
        $updateReceive['receive_order_temperature_id'] .= ' Edited';
        $updateReceive['receive_order_seal1_id'] .= ' Edited';
        $updateReceive['receive_order_seal2_id'] .= ' Edited';
        $updateReceive['receive_order_seal3_id'] .= ' Edited';
        $updateReceive['receive_order_seal4_id'] .= ' Edited';
        $updateReceive['receive_order_gift_flag'] = $updateReceive['receive_order_gift_flag'] ? 0 : 1;
        $updateReceive['receive_order_delivery_cut_form_id'] .= ' Edited';
        $updateReceive['receive_order_delivery_cut_form_note'] .= ' Edited';
        $updateReceive['receive_order_credit_type_id'] = 1;
        $updateReceive['receive_order_credit_approval_no'] = 0;
        $updateReceive['receive_order_credit_approval_amount'] = 0;
        $updateReceive['receive_order_credit_approval_type_id'] = 0;
        $updateReceive['receive_order_credit_approval_type_name'] .= ' Edited';
        $updateReceive['receive_order_credit_approval_date'] = (new \DateTime())->format('Y-m-d H:i:s');
        $updateReceive['receive_order_customer_type_id'] = 0;
        $updateReceive['receive_order_customer_id'] .= ' Edited';
        $updateReceive['receive_order_purchaser_name'] .= ' Edited';
        $updateReceive['receive_order_purchaser_kana'] .= ' Edited';
        $updateReceive['receive_order_purchaser_zip_code'] += 1;
        $updateReceive['receive_order_purchaser_address1'] .= ' Edited';
        $updateReceive['receive_order_purchaser_address2'] .= ' Edited';
        $updateReceive['receive_order_purchaser_tel'] += 1;
        $mailParts = explode('@', $updateReceive['receive_order_purchaser_mail_address']);;
        $updateReceive['receive_order_purchaser_mail_address'] = $mailParts[0] . '+edited@' . $mailParts[1];
        $updateReceive['receive_order_consignee_name'] .= ' Edited';
        $updateReceive['receive_order_consignee_kana'] .= ' Edited';
        $updateReceive['receive_order_consignee_zip_code'] += 1;
        $updateReceive['receive_order_consignee_address1'] .= ' Edited';
        $updateReceive['receive_order_consignee_address2'] .= ' Edited';
        $updateReceive['receive_order_consignee_tel'] += 1;
        $updateReceive['receive_order_important_check_id'] = $updateReceive['receive_order_important_check_id'] ? 0 : 1;
        $updateReceive['receive_order_statement_delivery_printing_date'] = (new \DateTime())->format('Y-m-d H:i:s');
        $updateReceive['receive_order_credit_number_payments'] += 1;
        $updateReceive['receive_order_send_plan_date'] = (new \DateTime())->format('Y-m-d H:i:s');

        $updateData = [
          'receiveorder_base' => $updateReceive
        ];

        $encoders = array(new XmlEncoder(), new XmlEncoder());
        $normalizers = array(new GetSetMethodNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        $context = [
            'xml_root_node_name' => 'root'
          , 'xml_format_output' => true
          , 'xml_encoding' => 'UTF-8'
        ];

        $updateXml = $serializer->serialize($updateData, 'xml', $context);
        var_dump($updateXml);

        // 更新処理実行
        $query = array() ;

        $query['receive_order_id'] = $receive['receive_order_id'];
        $query['receive_order_last_modified_date'] = $receive['receive_order_last_modified_date'];
        $query['data'] = $updateXml ;
        $query['receive_order_shipped_update_flag'] = 1 ;

        // アクセス制限中はアクセス制限が終了するまで待つ。
        // (1以外/省略時にアクセス制限になった場合はエラーのレスポンスが返却される)
        $query['wait_flag'] = '1' ;

        // 検索実行
        $result = $client->apiExecute('/api_v1_receiveorder_base/update', $query) ;

        var_dump($result);

        $output->writeln('done!!');
        return 0;
    }

}
