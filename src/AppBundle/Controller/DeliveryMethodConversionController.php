<?php
namespace AppBundle\Controller;

use MiscBundle\Entity\Repository\SymfonyUsersRepository;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\StringUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use MiscBundle\Entity\Repository\TbDeliveryChangeShippingMethodRepository;
use MiscBundle\Entity\Repository\TbSalesDetailRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbDeliveryChangeShippingMethod;
use MiscBundle\Entity\VSalesVoucher;

class DeliveryMethodConversionController extends BaseController
{

    /**
     * 商品ロケーション履歴
     */
    public function ListAction(Request $request)
    {
      $message = "";

      $queue = $this->getResque()->getQueue('main');
      if ($queue) {
        /** @var MainJob $job */
        foreach($queue->getJobs() as $job) {
          if ($job->getCommand() == 'delivery_method_conversion') {
            $message = "只今一括変換処理待ちです、少々お待ち下さい。";
          }
        }
      }
    
      $dbMain = $this->getDoctrine()->getConnection('main');
      
      // 現在実行中の排他処理
      $now = new \DateTime();
      /** @var DateTimeUtil $dateUtil */
      $dateUtil = $this->get('misc.util.datetime');

      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRunning');
      $running = $repo->findAll();
      /** @var TbRunning $process */
      foreach($running as $process) {
        $running = $process->toScalarArray();

        // 経過時間
        /** @var \DateTime $start */
        if ($running['proc'] == '発送方法一括変換') {
          $message = "只今一括変換処理中です、少々お待ち下さい。";
        }
      }

      
      $sql = <<<EOD
SELECT
       伝票番号
       ,推奨発送方法
  FROM
       tb_progress_order
 WHERE
       推奨配送方法コード <> 配送方法コード
 GROUP BY
       伝票番号
 ORDER BY
       推奨配送方法コード DESC, 伝票番号
EOD;
      $result = $dbMain->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        
      $data = array();
        
      foreach($result as $row){
        if(!isset($data[$row['推奨発送方法']])) $data[$row['推奨発送方法']] = array();
        $data[$row['推奨発送方法']][] = $row['伝票番号'];
      }
      
      foreach($data as $key => $val){
        $data[$key] = implode("\r\n", $val);
      }

      // 画面表示
      return $this->render('AppBundle:DeliveryMethodConversion:list.html.twig', [
          'account' => $this->getLoginUser()
        , 'data' => $data
        , 'message' => $message
      ]);
        
        // ログインアカウント一覧取得（プルダウン表示）
        /** @var SymfonyUsersRepository $repoUser */
        /*
        $repoUser = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers');
        $users = $repoUser->getActiveAccounts();

        $conditions = [];
        $conditions['start'] = (new \DateTime())->format('Y-m-d');
        $conditions['end'] = (new \DateTime())->format('Y-m-d');
        $repo_tb_sales_detail_analyze = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetailAnalyze');
        $vouchers = $repo_tb_sales_detail_analyze->getVoucherByCondition($conditions['start'], $conditions['end']);
        $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryChangeShippingMethod');

        if ($request->getMethod() === Request::METHOD_POST) {
            $conditions['start'] = $request->get('shipping_date_start');
            $conditions['end'] = $request->get('shipping_date_end');
            if (strlen($conditions['start']) == 0){
                $this->setFlash('danger', '初回出荷予定の形式が正しくありません。再入力してください。');
                return $this->render('AppBundle:DeliveryMethodConversion:list.html.twig', [
                    'account' => $this->getLoginUser()
                    , 'users' => $users
                    , 'conditions' => $conditions
                ]);
            }

            $lists = [];
            foreach ($vouchers as $voucher) {
                $change = $repo->findActiveOneByVoucherNumber($voucher['伝票番号']);
                if (isset($change)) {
                    $temp =  ['product_code' => $voucher['商品コード（伝票）'], 'voucher_number' =>  $voucher['伝票番号'], 'order_number' =>  $voucher['受注番号'], 'order_date' => $change['date'], 'current_shipping_method' => $voucher['発送方法'], 'new_shipping_method' => $change['shipping_method'], 'updater' => $change['purchaser']];
                } else {
                    $temp =  ['product_code' => $voucher['商品コード（伝票）'], 'voucher_number' =>  $voucher['伝票番号'], 'order_number' =>  $voucher['受注番号'], 'order_date' => $voucher['受注日'], 'current_shipping_method' => $voucher['発送方法'], 'new_shipping_method' => '', 'updater' => $voucher['購入者名']];
                }
                $lists[] = $temp;
            }
            */

            /** @var StringUtil $stringUtil */
            /*
            $stringUtil = $this->get('misc.util.string');

            // ヘッダ
            $headers = [
                'order_date'             => '変更日時',
                'product_code'             => '商品コード',
                'voucher_number'          => '伝票番号',
                'order_number'          => '受注番号',
                'current_shipping_method' => '変更元発送方法',
                'new_shipping_method' => '変更先発送方法',
//                'updater' => '変更者'
            ];

            $response = new StreamedResponse();
            $response->setCallback(
                function () use ($lists, $stringUtil, $headers) {
                    $file = new \SplFileObject('php://output', 'w');
                    $eol = "\r\n";

                    // ヘッダ
                    $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
                    $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
                    $file->fwrite($header);

                    foreach($lists as $log) {

                        $line = $stringUtil->convertArrayToCsvLine($log, array_keys($headers), [], ",") . $eol;
                        $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

                        $file->fwrite($line);

                        flush();
                    }
                }
            );

            $fileName = sprintf('delivery_method_conversion_%s.csv', (new \DateTime())->format('YmdHis'));

            $response->headers->set('Content-type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', $fileName));
            $response->send();
            return $response;
        }
        */
    }
}
