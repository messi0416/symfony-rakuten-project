<?php
/**
 * バッチ処理 EC-CUBE受注変換NextEngineアップロード処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertMallOrderCsvEcCubeAndUploadCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  private static $TARGET_DBS = [
      DbCommonUtil::MALL_CODE_EC01 => 'ec01'
    , DbCommonUtil::MALL_CODE_EC02 => 'ec02'
  ];

  private static $UPDATE_RECORD_NUMBERS = [
      DbCommonUtil::MALL_CODE_EC01 => DbCommonUtil::UPDATE_RECORD_NUMBER_CONVERT_MALL_ORDER_CSV_EC01
    , DbCommonUtil::MALL_CODE_EC02 => DbCommonUtil::UPDATE_RECORD_NUMBER_CONVERT_MALL_ORDER_CSV_EC02
  ];

  protected function configure()
  {
    $this
      ->setName('batch:convert-mall-order-csv-ec-cube-and-upload')
      ->setDescription('EC-CUBE受注変換NextEngineアップロード処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target', null, InputOption::VALUE_REQUIRED, '対象店舗コード ec01|ec02')
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'NextEngineへアップロード 0:しない 1:する', 1)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('EC-CUBE受注変換処理を開始しました。');

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
      $target = $input->getOption('target');
      $targetList = array_keys(self::$TARGET_DBS);
      if (!in_array($target, $targetList)) {
        throw new \RuntimeException(sprintf('invalid target [%s]. (allowed: %s)', $target, implode(' / ', $targetList)));
      }

      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('EC-CUBE受注変換処理'); // NextEngineアップロードはまだ
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $dbMain = $this->getDb('main');
      $dbLog = $this->getDb('log');
      $dbEc = $this->getDb(self::$TARGET_DBS[$target]);

      // step 1. 受注データを tb_ne_mall_order へ取り込む。
      // 最終変換日時
      $processStarted = new \DateTimeImmutable(); // 最終更新日時とする日時（処理開始時）
      $lastConverted = $commonUtil->getUpdateRecordLastUpdatedDateTime(self::$UPDATE_RECORD_NUMBERS[$target]);

      /*
      1 : 新規受付
      2 : 入金待ち
      4 : 取り寄せ中
      5 : 発送済み
      6 : 入金済み

      3 : キャンセル
      7 : 決済処理中
      8 : 購入処理中
      */
      $fetchStatuses = [
          1, 2, 4, 5, 6
      ];
      $fetchStatusesStr = implode(',', $fetchStatuses);

      $sql = <<<EOD
        SELECT
          o.`order_id`
        , o.`customer_id`
        , o.`order_pref`
        , o.`order_sex`
        , o.`order_job`
        , o.`payment_id`
        , o.`message`
        , o.`order_name01`
        , o.`order_name02`
        , o.`order_kana01`
        , o.`order_kana02`
        , o.`order_company_name`
        , o.`order_email`
        , o.`order_tel01`
        , o.`order_tel02`
        , o.`order_tel03`
        , o.`order_fax01`
        , o.`order_fax02`
        , o.`order_fax03`
        , o.`order_zip01`
        , o.`order_zip02`
        , o.`order_zipcode`
        , o.`order_addr01`
        , o.`order_addr02`
        , o.`order_birth`
        , o.`subtotal` - o.`tax` AS subtotal
        , o.`discount`
        , o.`delivery_fee_total`
        , o.`charge`
        , o.`tax`
        , o.`total`
        , o.`payment_total`
        , o.`payment_method`
        , o.`note`
        , o.`create_date`
        , o.`update_date`
        , o.`order_date`
        , o.`commit_date`
        , o.`payment_date`
        , o.`status`

        , s.`shipping_id`
        , s.`shipping_country_id`
        , s.`shipping_pref`
        , s.`delivery_id`
        , s.`time_id`
        , s.`fee_id`
        , s.`shipping_name01`
        , s.`shipping_name02`
        , s.`shipping_kana01`
        , s.`shipping_kana02`
        , s.`shipping_company_name`
        , s.`shipping_tel01`
        , s.`shipping_tel02`
        , s.`shipping_tel03`
        , s.`shipping_fax01`
        , s.`shipping_fax02`
        , s.`shipping_fax03`
        , s.`shipping_zip01`
        , s.`shipping_zip02`
        , s.`shipping_zipcode`
        , s.`shipping_addr01`
        , s.`shipping_addr02`
        , s.`shipping_delivery_name`
        , s.`shipping_delivery_time`
        , s.`shipping_delivery_date`
        , s.`shipping_delivery_fee`
        , s.`shipping_commit_date`
        , mp1.name AS order_pref_name
        , ms1.name AS order_sex_name
        , mp2.name AS shipping_pref_name
        FROM dtb_order o
        INNER JOIN dtb_shipping s ON o.order_id = s.order_id
        INNER JOIN mtb_pref mp1 ON o.order_pref = mp1.id
        INNER JOIN mtb_pref mp2 ON s.shipping_pref = mp2.id
        LEFT JOIN mtb_sex ms1 ON o.order_sex = ms1.id
        WHERE o.del_flg = 0
          AND s.del_flg = 0
          AND o.status IN ( {$fetchStatusesStr} )
EOD;
      if ($lastConverted) {
        $sql .= " AND o.order_date >= " . $dbEc->quote($lastConverted->format('Y-m-d H:i:s'));
      }
      $sql .= " ORDER BY o.order_date, o.create_date ";
      $stmt = $dbEc->prepare($sql);
      $stmt->execute();

      // 一括挿入（order）
      $insertBuilder = new MultiInsertUtil("tb_mall_order_ec", [
        'fields' => [
            'mall_code' => \PDO::PARAM_STR
          , 'order_id' => \PDO::PARAM_INT
          , 'customer_id' => \PDO::PARAM_INT
          , 'order_pref' => \PDO::PARAM_INT
          , 'order_pref_name' => \PDO::PARAM_STR
          , 'order_sex' => \PDO::PARAM_STR
          , 'order_job' => \PDO::PARAM_STR
          , 'payment_id' => \PDO::PARAM_STR
          , 'message' => \PDO::PARAM_STR
          , 'order_name01' => \PDO::PARAM_STR
          , 'order_name02' => \PDO::PARAM_STR
          , 'order_kana01' => \PDO::PARAM_STR
          , 'order_kana02' => \PDO::PARAM_STR
          , 'order_company_name' => \PDO::PARAM_STR
          , 'order_email' => \PDO::PARAM_STR
          , 'order_tel01' => \PDO::PARAM_STR
          , 'order_tel02' => \PDO::PARAM_STR
          , 'order_tel03' => \PDO::PARAM_STR
          , 'order_fax01' => \PDO::PARAM_STR
          , 'order_fax02' => \PDO::PARAM_STR
          , 'order_fax03' => \PDO::PARAM_STR
          , 'order_zip01' => \PDO::PARAM_STR
          , 'order_zip02' => \PDO::PARAM_STR
          , 'order_zipcode' => \PDO::PARAM_STR
          , 'order_addr01' => \PDO::PARAM_STR
          , 'order_addr02' => \PDO::PARAM_STR
          , 'order_birth' => \PDO::PARAM_STR
          , 'subtotal' => \PDO::PARAM_INT
          , 'discount' => \PDO::PARAM_INT
          , 'delivery_fee_total' => \PDO::PARAM_INT
          , 'charge' => \PDO::PARAM_INT
          , 'tax' => \PDO::PARAM_INT
          , 'total' => \PDO::PARAM_INT
          , 'payment_total' => \PDO::PARAM_INT
          , 'payment_method' => \PDO::PARAM_INT
          , 'note' => \PDO::PARAM_STR
          , 'create_date' => \PDO::PARAM_STR
          , 'update_date' => \PDO::PARAM_STR
          , 'order_date' => \PDO::PARAM_STR
          , 'commit_date' => \PDO::PARAM_STR
          , 'payment_date' => \PDO::PARAM_STR
          , 'status' => \PDO::PARAM_INT

          , 'shipping_id' => \PDO::PARAM_INT
          , 'shipping_country_id' => \PDO::PARAM_INT
          , 'shipping_pref' => \PDO::PARAM_INT
          , 'shipping_pref_name' => \PDO::PARAM_STR
          , 'delivery_id' => \PDO::PARAM_INT
          , 'time_id' => \PDO::PARAM_INT
          , 'fee_id' => \PDO::PARAM_INT
          , 'shipping_name01' => \PDO::PARAM_STR
          , 'shipping_name02' => \PDO::PARAM_STR
          , 'shipping_kana01' => \PDO::PARAM_STR
          , 'shipping_kana02' => \PDO::PARAM_STR
          , 'shipping_company_name' => \PDO::PARAM_STR
          , 'shipping_tel01' => \PDO::PARAM_STR
          , 'shipping_tel02' => \PDO::PARAM_STR
          , 'shipping_tel03' => \PDO::PARAM_STR
          , 'shipping_fax01' => \PDO::PARAM_STR
          , 'shipping_fax02' => \PDO::PARAM_STR
          , 'shipping_fax03' => \PDO::PARAM_STR
          , 'shipping_zip01' => \PDO::PARAM_STR
          , 'shipping_zip02' => \PDO::PARAM_STR
          , 'shipping_zipcode' => \PDO::PARAM_STR
          , 'shipping_addr01' => \PDO::PARAM_STR
          , 'shipping_addr02' => \PDO::PARAM_STR
          , 'shipping_delivery_name' => \PDO::PARAM_STR
          , 'shipping_delivery_time' => \PDO::PARAM_STR
          , 'shipping_delivery_date' => \PDO::PARAM_STR
          , 'shipping_delivery_fee' => \PDO::PARAM_INT
          , 'shipping_commit_date' => \PDO::PARAM_STR
        ]
        , 'prefix' => 'INSERT IGNORE INTO '
      ]);

      $commonUtil->multipleInsert($insertBuilder, $dbLog, $stmt, function($row) use ($target, $logger) {
        $item = $row;
        $item['mall_code'] = $target;

        return $item;
      });

      // order_detail
      $sql = <<<EOD
        SELECT
            d.`order_detail_id`
          , d.`order_id`
          , d.`product_id`
          , d.`product_class_id`
          , d.`product_name`
          , d.`product_code`
          , d.`class_name1`
          , d.`class_name2`
          , d.`class_category_name1`
          , d.`class_category_name2`
          , d.`price`
          , d.`quantity`
          , d.`tax_rate`
          , d.`tax_rule`
        FROM dtb_order_detail d
        INNER JOIN dtb_order o ON d.order_id = o.order_id
        WHERE o.del_flg = 0
          AND o.status IN ( 1, 2, 4, 5, 6 )
EOD;
      if ($lastConverted) {
        $sql .= " AND o.order_date >= " . $dbEc->quote($lastConverted->format('Y-m-d H:i:s'));
      }
      $sql .= " ORDER BY o.order_date, o.create_date ";
      $stmt = $dbEc->prepare($sql);
      $stmt->execute();

      // 一括挿入（order）
      $insertBuilder = new MultiInsertUtil("tb_mall_order_ec_detail", [
        'fields' => [
            'mall_code' => \PDO::PARAM_STR
          , 'order_id' => \PDO::PARAM_INT
          , 'order_detail_id' => \PDO::PARAM_INT
          , 'product_id' => \PDO::PARAM_INT
          , 'product_class_id' => \PDO::PARAM_INT
          , 'product_name' => \PDO::PARAM_STR
          , 'product_code' => \PDO::PARAM_STR
          , 'class_name1' => \PDO::PARAM_STR
          , 'class_name2' => \PDO::PARAM_STR
          , 'class_category_name1' => \PDO::PARAM_STR
          , 'class_category_name2' => \PDO::PARAM_STR
          , 'price' => \PDO::PARAM_INT
          , 'quantity' => \PDO::PARAM_INT
          , 'tax_rate' => \PDO::PARAM_STR
          , 'tax_rule' => \PDO::PARAM_STR
        ]
        , 'prefix' => 'INSERT IGNORE INTO '
      ]);

      $commonUtil->multipleInsert($insertBuilder, $dbLog, $stmt, function($row) use ($target, $logger) {
        $item = $row;
        $item['mall_code'] = $target;

        return $item;
      });

      // step 2. モール受注CSV変換処理を行う
      // モール受注CSV変換 実行
      $commandArgs = [
          'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
        , sprintf('--mall-code=%s', $target)
        , sprintf('--do-upload=%d', $input->getOption('do-upload') ? 1 : 0)
        , '--force=1'
      ];
      if (!is_null($this->account)) {
        $commandArgs[] = sprintf('--account=%d', $this->account->getId());
      }

      $logger->info('convert_mall_order_csv_data: ' . print_r($commandArgs, true));
      $input = new ArgvInput($commandArgs);
      $output = new ConsoleOutput();

      $command = $this->getContainer()->get('batch.convert_mall_order_csv_data');
      $exitCode = $command->run($input, $output);
      $logger->info('Convert Mall Order CSV Done. [' . $exitCode . ']');

      if ($exitCode) {
        throw new \RuntimeException('モール受注CSV変換に失敗しました。');
      }

      // 全て成功すれば、最終更新日時を更新
      $commonUtil->updateUpdateRecordTable(self::$UPDATE_RECORD_NUMBERS[$target]);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('EC-CUBE受注変換処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('EC-CUBE受注変換処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('EC-CUBE受注変換処理 エラー', 'EC-CUBE受注変換処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'EC-CUBE受注変換処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


