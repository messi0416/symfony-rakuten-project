<?php
/**
 * バッチ処理 Amazon FBA出荷用CSV出力処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AmazonMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCsvAmazonFbaOrderCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-amazon-fba-order')
      ->setDescription('Amazon FBA出荷用CSV出力処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('update-order', null, InputOption::VALUE_OPTIONAL, '注文情報を取得更新するか 0:更新しない 1:更新する', '1')
      ->addOption('update-fba-stock', null, InputOption::VALUE_OPTIONAL, 'FBA在庫を取得更新するか 0:更新しない 1:更新する', '1');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('Amazon FBA出荷用CSV出力処理を開始しました。');

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

    try {

      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
        'message' => null
      ];

      $logExecTitle = sprintf('Amazon FBA出荷用CSV出力処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->getContainer()->get('batch.mall_process.amazon');
      $mallProcess->setEnvironment('prod'); // test環境で本番へ接続する記述

      // 注文情報の更新処理
      if ($input->getOption('update-order')) {
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'Amazon注文情報更新処理', '開始'));

        $startDate = new \DateTime();
        $startDate->modify('-14 day')->setTime(0, 0, 0);
        $endDate = new \DateTime();
        $endDate->setTime(0, 0, 0);
        $mallProcess->updateOrder(AmazonMallProcess::SHOP_NAME_VOGUE, $startDate, $endDate);

        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'Amazon注文情報更新処理', '終了'));
      }

      // FBA在庫情報の更新処理
      if ($input->getOption('update-fba-stock')) {
        // ====================================================
        // FBA在庫データ更新（FBA在庫情報 ダウンロード ＆ データ更新）
        // ====================================================
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '開始'));
        $mallProcess->updateFbaProductStock(AmazonMallProcess::SHOP_NAME_VOGUE);
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA在庫更新処理', '終了'));

        // ====================================================
        // FBA仮想倉庫 在庫ロケーション更新
        // ====================================================
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA仮想倉庫ロケーション更新処理', '開始'));
        $mallProcess->updateFbaMultiProductLocation(AmazonMallProcess::SHOP_NAME_VOGUE, $account);
        $result = $mallProcess->updateFbaMultiProductLocation(AmazonMallProcess::SHOP_NAME_VOGUE, $account);
        if($result['status'] === 'ng'){
          $logger->addDbLog(
              $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー')->setInformation($result['message'])
            , true, $logExecTitle . "でエラーが発生しました。", 'error'
          );
        }
        
        $logger->addDbLog($logger->makeDbLog($logExecTitle, 'FBA仮想倉庫ロケーション更新処理', '終了'));
      }

      $commonUtil = $this->getDbCommonUtil();
      $dbMain = $this->getDb('main');

      // Amazon在庫情報テーブルから、セッション数テーブルの sku を補完
      $sql = <<<EOD
        UPDATE
        tb_amazon_business_report_session sess
        INNER JOIN tb_amazon_product_stock stock ON sess.child_asin = stock.asin
        SET sess.sku = stock.sku
        WHERE sess.sku = ''
EOD;
      $dbMain->exec($sql);


      // CSV出力処理
      $now = new \DateTime();

      // 基準セッション数(/月)
      $sessionNumBorder = $commonUtil->getSettingValue('AMAZON_FBA_SESSION_BORDER');
      if (!strlen($sessionNumBorder) || !is_numeric($sessionNumBorder)) {
        $sessionNumBorder = 30;
      }
      $sessionNumBorder = intval($sessionNumBorder);

      // 最低出荷在庫数
      $minStock = intval($commonUtil->getSettingValue('AMAZON_FBA_MIN_STOCK'));


      /** @var FileUtil $fileUtil */
      $fileUtil = $this->getFileUtil();
      $filePath = sprintf('%s/%s_%s.csv', $mallProcess->getFbaOrderCsvDir(), $mallProcess->getFbaOrderCsvPrefix(), $now->format('YmdHis'));

      $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_work_amazon_fba_order");
      $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_work_amazon_fba_order (
            sku VARCHAR(50) NOT NULL PRIMARY KEY
          , quantity INTEGER NOT NULL DEFAULT 0
          , price  INTEGER NOT NULL DEFAULT 0
          , tax INTEGER NOT NULL DEFAULT 0
          , title VARCHAR(255) NOT NULL DEFAULT ''
        ) Engine=InnoDB DEFAULT CHARSET utf8;
EOD;
      $dbMain->query($sql);

      $sql = <<<EOD
        INSERT INTO tmp_work_amazon_fba_order
        SELECT
            pci.ne_syohin_syohin_code
          , COALESCE(o.quantity, 0) - COALESCE(s.fba_quantity_fulfillable, 0) AS quantity
          , i.fba_baika AS price
          , TRUNCATE(i.fba_baika * CAST(:taxRate AS DECIMAL(10, 2)), 0) AS tax
          , i.amazon_title
        FROM tb_productchoiceitems pci
        INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_amazoninfomation i ON cal.daihyo_syohin_code = i.daihyo_syohin_code
        LEFT JOIN (
          SELECT
              o.sku
            , SUM(o.quantity) AS quantity
          FROM tb_amazon_order_recent o
          GROUP BY o.sku
        ) o ON pci.ne_syohin_syohin_code = o.sku
        LEFT JOIN tb_amazon_business_report_session sess ON o.sku = sess.sku
        LEFT JOIN tb_amazon_product_stock s ON o.sku = s.sku
        WHERE i.fba_flg <> 0
          AND COALESCE(sess.セッション, 0) >= :sessionNumBorder
        ORDER BY pci.ne_syohin_syohin_code
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':taxRate', $commonUtil->getTaxRate() - 1, \PDO::PARAM_STR);
      $stmt->bindValue(':sessionNumBorder', $sessionNumBorder, \PDO::PARAM_INT);
      $stmt->execute();

      // 最低出荷数補正
      $sql = <<<EOD
        UPDATE tmp_work_amazon_fba_order t
        INNER JOIN tb_productchoiceitems pci ON t.sku = pci.ne_syohin_syohin_code
        SET t.quantity = :minStock
        WHERE t.quantity < :minStock
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':minStock', $minStock, \PDO::PARAM_INT);
      $stmt->execute();

      // quantity はフリー在庫数までとする
      $sql = <<<EOD
        UPDATE tmp_work_amazon_fba_order t
        INNER JOIN tb_productchoiceitems pci ON t.sku = pci.ne_syohin_syohin_code
        SET t.quantity = pci.フリー在庫数
        WHERE pci.フリー在庫数 < t.quantity
EOD;
      $dbMain->query($sql);

      // 集計値取得
      $sql = <<<EOD
        SELECT
            SUM(t.price * t.quantity)                           AS price_sum
          , SUM(t.tax * t.quantity)                             AS tax_sum
          , SUM(t.price * t.quantity) + SUM(t.tax * t.quantity) AS price_total
        FROM tmp_work_amazon_fba_order t
        WHERE t.quantity > 0
EOD;
      $sum = $dbMain->query($sql)->fetch(\PDO::FETCH_ASSOC);

      // データ取得
      $sql = <<<EOD
        SELECT
            :voucherNumber            AS `店舗伝票番号`
          , :orderDate                AS `受注日`
          , '6300226'                 AS `受注郵便番号`
          , '奈良県生駒市小平尾町57-1'  AS `受注住所1`
          , ''                        AS `受注住所2`
          , 'ヴォーグ' AS `受注名`
          , 'ヴォーグ' AS `受注名カナ`
          , '0743765090'              AS `受注電話番号`
          , 'welcome@plusnao.co.jp'   AS `受注メールアドレス`
          , '3501182'                 AS `発送郵便番号`
          , '埼玉県川越市南台1-10-15'   AS `発送先住所１`
          , ''                        AS `発送先住所２`
          , 'Amazon.co.jp NRT5 1F FBA入庫係' AS `発送先名`
          , '' AS `発送先カナ`
          , '' AS `発送電話番号`
          , :paymentMethod        AS `支払方法`
          , :deliveryMethod       AS `発送方法`
          , :priceSum             AS `商品計`
          , :taxSum               AS `税金`
          , 0 AS `発送料`
          , 0 AS `手数料`
          , 0 AS `ポイント`
          , 0 AS `その他費用`
          , :priceTotal           AS `合計金額`
          , 1 AS `ギフトフラグ`
          , '' AS `時間帯指定`
          , '' AS `日付指定`
          , 'FBA'                 AS `作業者欄`
          , '' AS `備考`
          , t.title               AS `商品名`
          , t.sku                 AS `商品コード`
          , t.price               AS `商品価格`
          , t.quantity            AS `受注数量`
          , '' AS `商品オプション`
          , '' AS `出荷済フラグ`
          , '' AS `顧客区分`
          , '' AS `顧客コード`
        FROM tmp_work_amazon_fba_order t
        WHERE t.quantity > 0
        ORDER BY t.sku
EOD;
      $stmt = $dbMain->prepare($sql);

      $stmt->bindValue(':voucherNumber', sprintf('FBA_%s', $now->format('YmdHis')), \PDO::PARAM_STR);
      $stmt->bindValue(':orderDate', $now->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
      $stmt->bindValue(':paymentMethod', DbCommonUtil::PAYMENT_METHOD_DONE, \PDO::PARAM_STR); // 支払済
      $stmt->bindValue(':deliveryMethod', DbCommonUtil::DELIVERY_METHOD_TENTOU, \PDO::PARAM_STR); // 店頭渡し

      $stmt->bindValue(':priceSum', $sum['price_sum'], \PDO::PARAM_INT);
      $stmt->bindValue(':taxSum', $sum['tax_sum'], \PDO::PARAM_INT);
      $stmt->bindValue(':priceTotal', $sum['price_total'], \PDO::PARAM_INT);
      $stmt->execute();

      $results = [
          'message' => null
        , 'count' => null
        , 'filename' => null
      ];

      if ($stmt->rowCount() > 0) {

        $results['count'] = 0;
        $results['filename'] = basename($filePath);

        /** @var StringUtil $stringUtil */
        $stringUtil = $this->getContainer()->get('misc.util.string');

        // ヘッダ
        $headers = [
            '店舗伝票番号'  => '店舗伝票番号'
          , '受注日'        => '受注日'
          , '受注郵便番号'  => '受注郵便番号'
          , '受注住所1'     => '受注住所1'
          , '受注住所2'     => '受注住所2'
          , '受注名'       => '受注名'
          , '受注名カナ'   => '受注名カナ'
          , '受注電話番号'  => '受注電話番号'
          , '受注メールアドレス' => '受注メールアドレス'
          , '発送郵便番号'  => '発送郵便番号'
          , '発送先住所１'  => '発送先住所１'
          , '発送先住所２'  => '発送先住所２'
          , '発送先名'      => '発送先名'
          , '発送先カナ'   => '発送先カナ'
          , '発送電話番号'  => '発送電話番号'
          , '支払方法'      => '支払方法'
          , '発送方法'      => '発送方法'
          , '商品計'        => '商品計'
          , '税金'          => '税金'
          , '発送料'        => '発送料'
          , '手数料'        => '手数料'
          , 'ポイント'      => 'ポイント'
          , 'その他費用'     => 'その他費用'
          , '合計金額'      => '合計金額'
          , 'ギフトフラグ'  => 'ギフトフラグ'
          , '時間帯指定'     => '時間帯指定'
          , '日付指定'      => '日付指定'
          , '作業者欄'      => '作業者欄'
          , '備考'          => '備考'
          , '商品名'       => '商品名'
          , '商品コード'   => '商品コード'
          , '商品価格'      => '商品価格'
          , '受注数量'      => '受注数量'
          , '商品オプション' => '商品オプション'
          , '出荷済フラグ'   => '出荷済フラグ'
          , '顧客区分'      => '顧客区分'
          , '顧客コード'    => '顧客コード'
        ];


        $fp = fopen($filePath, 'wb');

        // ヘッダ
        $eol = "\r\n";
        $header = $stringUtil->convertArrayToCsvLine(array_values($headers), [], [], ",") . $eol;
        $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
        fwrite($fp, $header);

        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

          $line = $stringUtil->convertArrayToCsvLine($row, array_keys($headers), [], ",") . $eol;
          $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');

          fwrite($fp, $line);

          $results['count']++;
        }

        fclose($fp);

      } else {
        $results['message'] = '出力するデータがありませんでした。';
      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($results));
      $logger->logTimerFlush();

      $logger->info('Amazon FBA出荷用CSV出力処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('Amazon FBA出荷用CSV出力処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('Amazon FBA出荷用CSV出力処理 エラー', 'Amazon FBA出荷用CSV出力処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'Amazon FBA出荷用CSV出力処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}

