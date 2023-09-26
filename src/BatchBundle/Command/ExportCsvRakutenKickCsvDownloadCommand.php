<?php
/**
 * 楽天 CSV出力 インポート用CSVダウンロード準備処理
 * User: hirai
 * Date: 2016/02/04
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

class ExportCsvRakutenKickCsvDownloadCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  /** 楽天RMSショップコード：楽天 */
  const RMS_SHOP_CODE_RAKUTEN = 242190;

  /** 楽天RMSショップコード：motto-motto */
  const RMS_SHOP_CODE_MOTTO = 384255;

  /** 楽天RMSショップコード：LaForest */
  const RMS_SHOP_CODE_LAFOREST = 405585;

  /** 楽天RMSショップコード：dolcissimo */
  const RMS_SHOP_CODE_DOLCISSIMO = 349354;

  /** 楽天RMSショップコード：gekipla */
  const RMS_SHOP_CODE_GEKIPLA = 411285;

  /** ダウンロード待機時間 */
  const DOWNLOAD_WAIT = 120;

  /** item.csvで必要な項目 */
  const NEED_FIELD_ITEM_CSV =
    ['商品番号',
     '倉庫指定',
     '商品画像URL',
     '在庫タイプ',
     '在庫数表示'];

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-rakuten-kick-csv-download')
      ->setDescription('CSVエクスポート 楽天 インポート用CSV出力準備処理')
      ->addOption('target-shop', null, InputOption::VALUE_OPTIONAL, '対象店舗。rakuten|motto|laforest|dolcissimo|gekipla')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();

    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info('[楽天 インポート用CSV出力準備処理]楽天CSV出力処理に伴う、インポート用CSVファイルダウンロード準備を開始しました。');
    $logExecTitle = sprintf('楽天CSV出力用 インポートCSVファイルダウンロードキック処理');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // 対象店舗ごとの設定取得
    // $shopCode : 楽天RMSの店舗ID
    // $shopName : ログ表示用のショップ名
    // $updateRecordNumber : tb_updaterecord にキック日時を登録するキー値
    $targetShop = $input->getOption('target-shop');
    if (!$targetShop) {
      $logger->info('[楽天 インポート用CSV出力準備処理]対象店舗を指定してください');
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'エラー終了', '対象店舗指定なし'));
      return 0;
    } else if ($targetShop == 'rakuten') {
      $shopCode = self::RMS_SHOP_CODE_RAKUTEN;
      $shopName = '楽天';
      $updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_RAKUTEN_KICK;
    } else if ($targetShop == 'motto') {
      $shopCode = self::RMS_SHOP_CODE_MOTTO;
      $shopName = 'motto-motto';
      $updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_MOTTO_KICK;
    } else if ($targetShop == 'laforest') {
      $shopCode = self::RMS_SHOP_CODE_LAFOREST;
      $shopName = 'LaForest';
      $updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_LAFOREST_KICK;
    } else if ($targetShop == 'dolcissimo') {
      $shopCode = self::RMS_SHOP_CODE_DOLCISSIMO;
      $shopName = 'dolcissimo';
      $updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_DOLCISSIMO_KICK;
    } else if ($targetShop == 'gekipla') {
      $shopCode = self::RMS_SHOP_CODE_GEKIPLA;
      $shopName = 'gekipla';
      $updateRecordNumber = DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_GEKIPLA_KICK;
    } else {
      $logger->info('[楽天 インポート用CSV出力準備処理]対象店舗を指定してください');
      $logger->addDbLog($logger->makeDbLog($logExecTitle, 'エラー終了', "店舗指定不正[$targetShop]"));
      return 0;
    }

    // Kick時刻を保存(処理待機時間冗長化に対して)
    $kickDate = null;

    try {
      $kickDate = new \DateTime();


      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();

      $logger->addDbLog($logger->makeDbLog($logExecTitle, '開始', "店舗名[$shopName]"));
      $this->stopwatch->start('main');

      $webAccessUtil = $this->getWebAccessUtil();

      // RMS ログイン
      $client = $webAccessUtil->getWebClient();
      $webAccessUtil->rmsLogin($client, 'api', $targetShop); // 必要なら、アカウント名を追加して切り替える

      // その１. 商品情報 dl-item年月日時分-連番.csv, dl-select-連番.csv
      // CSVダウンロードのAPIを直接キック
      $url = "https://item.rms.rakuten.co.jp/shops/$shopCode/download-csv/getFields";
      $crawler = $client->request('GET', $url);
      $response = $client->getResponse();
      $status = $response->getStatus();

      if ($status !== 200) {
        throw new RuntimeException("ダウンロード項目取得に失敗しました [$shopName][$status][$url]");
      }
      $logger->info("[楽天 インポート用CSV出力準備処理][$shopName] ダウンロード項目取得完了");

      $fieldlist = json_decode($response->getContent(), true);
      $fieldlist = $fieldlist['fieldList']['item'];
      $json = array("item" => array());
      foreach ($fieldlist as $field) {
        $checked = false;
        if (in_array($field['name'], self::NEED_FIELD_ITEM_CSV)) $checked = true;
        $json['item'][] = array('id' => $field['id'], 'name' => $field['name'], 'checked' => $checked);
      }

      $url = "https://item.rms.rakuten.co.jp/shops/$shopCode/download-csv/customize";
      $headers = [
          "Content-Type" => "application/json"
      ];
      $options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT;
      $client->request('POST', $url, array(), array(), $headers, json_encode($json, $options));

      $response = $client->getResponse();
      $status = $response->getStatus();

      if ($status !== 200) {
        throw new RuntimeException("[楽天 インポート用CSV出力準備処理]商品データ・項目選択肢・項目選択肢別在庫（item.csv、select.csv）のキックに失敗しました[$shopName][$status ][$url]");
      }
      $logger->info("[楽天 インポート用CSV出力準備処理][$shopName]商品データ・項目選択肢・項目選択肢別在庫（item.csv、select.csv）のキック完了");

      // RMSの記述に従い、1分以上待つ。（念のため2分待つ）
      $logger->info("[楽天 インポート用CSV出力準備処理][$shopName]RMSダウンロードキック処理 カテゴリCSV要求送信まで".(self::DOWNLOAD_WAIT / 60).'分待機');
      sleep(self::DOWNLOAD_WAIT);

      // その２. カテゴリ情報 item-cat.csv
      // CSVダウンロードのAPIを直接キック
      $nextUrl = "https://item.rms.rakuten.co.jp/shops/$shopCode/download-csv/categoryType/all";
      $crawler = $client->request('POST', $nextUrl);

      $response = $client->getResponse();
      $status = $response->getStatus();

      if ($status !== 200) {
        throw new RuntimeException("カテゴリ（item-cat.csv）のキックに失敗しました  [$shopName][$status][$nextUrl]");
      }
      $logger->info("[楽天 インポート用CSV出力準備処理][$shopName]カテゴリ（item-cat.csv）のキック完了");

      // 最終処理日時 更新
      $this->getDbCommonUtil()->updateUpdateRecordTable($updateRecordNumber, $kickDate);

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info("[楽天 インポート用CSV出力準備処理][$shopName] 楽天CSV出力処理に伴う、インポート用CSVファイルダウンロード準備 完了");
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error("[楽天 インポート用CSV出力準備処理][$shopName] 楽天CSV出力処理に伴う、インポート用CSVファイルダウンロード準備 エラー:" . $e->getMessage(). $e->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, 'エラー終了', "店舗名[$shopName]")->setInformation($e->getMessage())
        , true, '楽天CSV出力処理に伴う、インポート用CSVファイルダウンロード準備でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;
  }

}
