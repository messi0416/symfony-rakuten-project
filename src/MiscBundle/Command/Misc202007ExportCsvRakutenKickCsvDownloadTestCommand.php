<?php

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\DomCrawler\Field\InputFormField;

/**
 * 楽天 CSV出力 インポート用CSVダウンロード準備処理テスト
 */
class Misc202007ExportCsvRakutenKickCsvDownloadTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  const RETRY_COUNT = 20; // 最大20回実行
  const RETRY_WAIT = 600; // 180秒待つ
  const DOWNLOAD_WAIT = 1200; // ダウンロード待機時間

  private $account;

  protected function configure()
  {
    $this
      ->setName('misc:misc-202007-export-csv-rakuten-kick-csv-download-test')
      ->setDescription('CSVエクスポート 楽天 インポート用CSV出力準備処理テスト')
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
    $logger->info('楽天CSVファイルダウンロード準備テストを開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    $tryCount = 0;
    $success = false;

    // Kick時刻を保存(処理待機時間冗長化に対して)
    $kickDate = null;


      try {
        $kickDate = new \DateTime();

        $logExecTitle = sprintf('楽天CSV出力用 インポートCSVファイルダウンロードキック処理');
        $logger->setExecTitle($logExecTitle);

        $this->stopwatch->start('main');

        $webAccessUtil = $this->getWebAccessUtil();

        // RMS ログイン
        $client = $webAccessUtil->getWebClient();
        $webAccessUtil->rmsLogin($client, 'api'); // 必要なら、アカウント名を追加して切り替える

        // 商品登録・更新画面へ移動
        $nextUrl = 'https://mainmenu.rms.rakuten.co.jp/?left_navi=11';
        $crawler = $client->request('get', $nextUrl);

        $response = $client->getResponse();
        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();

        $header = null;
        try {
          $header = $crawler->filter('h1');
        } catch (\InvalidArgumentException $e) {
          // do nothing
        }
        if ($status !== 200 || !$header || strpos($header->text(), '商品管理') === false) {
          throw new \RuntimeException('商品管理画面への遷移に失敗しました [' . $status . '][' . $uri . '][' . $header->text() . ']');
        }
        $logger->info('商品登録・更新画面へ遷移成功');

        // その１. 商品情報 dl-item年月日時分-連番.csv, dl-select-連番.csv

        // 商品ページ設定メニュー リンク取得・遷移
        $link = $crawler->selectLink('CSV更新（変更・削除）')->link();
        $logger->info($link->getUri());
        $crawler = $client->click($link);

        // CSVファイルダウンロード画面 リンク 取得
        $link = $crawler->selectLink('CSVファイルの項目を選択してダウンロード')->link();
        $logger->info($link->getUri());
        $crawler = $client->click($link);

        $form = $crawler->selectButton('CSVファイルをダウンロード')->form();
        // 下記、どうやら指定が効いていない。ページがEUC-JPであるためか。
        // 指定してもしなくても処理に影響はないため、一旦なしとする。
        // $form['catalog_item_number']        = '商品番号'; // 商品番号
        // $form['image_url']                  = '商品画像URL'; // 商品画像URL
        // $form['normal_item_inventory_type'] = '在庫タイプ'; // 在庫タイプ
        // $form['normal_item_rest_type']      = '在庫数表示'; // 在庫数表示
        // $form['normal_item_depot_flag']->tick();

        $logger->debug("楽天CSVキックテスト：form値：" . print_r($form->getValues(), true));
        $logger->debug("楽天CSVキックテスト：uri：" . $form->getUri());
        $parameter = $form->getValues();
        $nextUrl = $form->getUri();
        $parameter['normal_item_depot_flag']     = mb_convert_encoding('倉庫指定', 'EUC-JP', 'UTF-8');
        $crawler = $client->request('post', $nextUrl, $parameter);

        $response = $client->getResponse();

        $status = $response->getStatus();
        $uri = $client->getRequest()->getUri();
        $logger->info(sprintf('kick csv download. status: %s, url: %s', $status, $uri));

        $content = mb_convert_encoding($response->getContent(), 'UTF-8', 'EUC-JP');
        if (strstr($content, 'CSVファイルをダウンロードしています') !== false) {
          $logger->info('その１. 商品情報 キック処理 成功');
        } else {
          $logger->info('その１. 商品情報 キック処理 失敗？');
          $logger->info($content);

          if (strstr($content, '入力エラーがあります。') !== false) {
            throw new \RuntimeException('エラーメッセージ: 入力エラーがあります。');
          }
        }
        $logger->logTimerFlush();

        $logger->info('楽天CSV出力処理に伴う、インポート用CSVファイルダウンロード準備 完了');
        $event = $this->stopwatch->stop('main');
        $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

        // 処理成功
        $success = true;

      } catch (\Exception $e) {

        $logger->error('楽天CSV出力処理に伴う、インポート用CSVファイルダウンロード準備 エラー:' . $e->getMessage());
      }

    return $success ? 0 : 1;
  }

}
