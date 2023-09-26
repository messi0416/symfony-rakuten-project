<?php
/**
 * 楽天 在庫更新CSV出力処理
 * User: hirai
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use BatchBundle\MallProcess\RakutenMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Exception\RuntimeException;

class ExportCsvRakutenUpdateStockCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $exportPath;

  /** @var \DateTime */
  private $processStart; // 処理開始日時。処理完了後、前回処理日時として保存する。

  private $results = [];

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-rakuten-update-stock')
      ->setDescription('CSVエクスポート 楽天 即納のみ在庫更新')
      ->addArgument('export-dir', InputArgument::REQUIRED, '出力先ディレクトリ', null)
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
    $logger->info('楽天 在庫更新CSV出力処理を開始しました。');

    $this->processStart = new \DateTime();

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
      // 出力先ディレクトリ
      $this->exportPath = $input->getArgument('export-dir');
      if (!strlen($this->exportPath)) {
        throw new RuntimeException('export path is not determined. (Rakuten Update Stock CSV)');
      }

      // なければエラー終了
      $fs = new FileSystem();
      if (!$fs->exists($this->exportPath)) {
        throw new RuntimeException('export path is not exists. (Rakuten CSV). [' . $this->exportPath . ']');
      }

      $logExecTitle = sprintf('楽天在庫更新CSV出力処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
      $this->stopwatch->start('main');

      try {
        // CSV出力 データ作成処理 実装

        // 出力 ＆ アップロード
        $this->exportCsvUpdateStock();
        /* ------------ DEBUG LOG ------------ */ $logger->debug($this->getLapTimeAndMemory('lap point', 'main'));

        $finder = new Finder(); // 結果ファイル確認
        $message = '';
        $fileNum = $finder->in($this->exportPath)->files()->count();
        if (!$fileNum) {
          $message = 'CSVファイルが作成されませんでした。処理を完了します。';
        }

        $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($message));
        $logger->logTimerFlush();

      } catch (\Exception $e) {

        $logger->error($e->getMessage());
        $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
          , true, $logExecTitle . "でエラーが発生しました。", 'error'
        );

        // 出力ディレクトリが空なら削除しておく
        $fs = new Filesystem();
        if ($this->exportPath && $fs->exists($this->exportPath)) {
          $finder = new Finder();
          if ($finder->in($this->exportPath)->count() == 0) {
            $fs->remove($this->exportPath);
          }
        }

        return 1;
      }

      // 最終処理日時 更新 → 不要
      // $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_RAKUTEN, $this->processStart);

      $logger->info('楽天 在庫更新CSV Export 完了');
      $event = $this->stopwatch->stop('main');
      $logger->info(sprintf('main: duration: %.02f / memory: %s', $event->getDuration() / 1000000, number_format($event->getMemory())));

    } catch (\Exception $e) {

      $logger->error('楽天 在庫更新CSV出力処理 Export エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('楽天在庫更新CSV出力処理', '楽天在庫更新CSV出力処理', 'エラー終了')->setInformation($e->getMessage())
        , true, '楽天在庫更新CSV出力処理でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;
  }

  /**
   * item.csv 在庫更新用出力処理
   */
  private function exportCsvUpdateStock()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'エクスポート';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $logger->info($this->exportPath);

    // 販売不可在庫数 更新処理
    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');
    $neMallProcess->updateNotForSaleStock();

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // '====================
    // 'select.csv
    // '====================
    $sql = <<<EOD
      SELECT
          'u'                         AS 項目選択肢用コントロールカラム
        , LOWER(s.`product_code`)     AS `商品管理番号（商品URL）`
        , 'i'                         AS 選択肢タイプ
        , s.colname                   AS 項目選択肢別在庫用横軸選択肢
        , s.rowname                   AS 項目選択肢別在庫用縦軸選択肢
        , pci.フリー在庫数             AS 項目選択肢別在庫用在庫数
      FROM tb_productchoiceitems     AS pci
      INNER JOIN tb_rakuten_product_stock s ON pci.daihyo_syohin_code = s.product_code
                                           AND pci.colcode = s.colcode
                                           AND pci.rowcode = s.rowcode
      INNER JOIN tb_rakuteninformation i ON pci.daihyo_syohin_code = i.daihyo_syohin_code
      WHERE pci.`フリー在庫数` <> s.stock
        AND i.registration_flg <> 0
      ORDER BY
          s.`product_code`
        , pci.並び順No
EOD;
    $stmt = $dbMain->query($sql);
    // 出力
    if ($stmt->rowCount()) {
      // ヘッダ
      $headers = [
          '項目選択肢用コントロールカラム'
        , '商品管理番号（商品URL）'
        , '選択肢タイプ'
        , '項目選択肢別在庫用横軸選択肢'
        , '項目選択肢別在庫用縦軸選択肢'
        , '項目選択肢別在庫用在庫数'
        , '在庫あり時納期管理番号'
      ];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";

      $noEncloseFields = [
      ];

      // データ
      $num = 0;
      $filePath = sprintf('%s/select.csv', $this->exportPath);
      $fp = fopen($filePath, 'wb');
      fputs($fp, $headerLine);

      while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        // 納期管理情報 随時変更
        // 2016/04/26時点で、予約販売の再開予定が無いため、納期管理番号は 40（完売）or 1000（即納）のみとする
        // ※ただ、40（完売）を出力する必要は無い可能性あり
        $row['在庫あり時納期管理番号'] = $row['項目選択肢別在庫用在庫数'] == 0 ? '40': '1000';

        $line = $stringUtil->convertArrayToCsvLine($row, $headers, $noEncloseFields);
        $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
        fputs($fp, $line);

        $num++;
      }
      fclose($fp);

      $this->results['select.csv'] = $num;
      $logger->info("楽天 在庫更新CSV出力処理 select.csv: $num 件");

      // 在庫確認用テーブル 更新
      /** @var RakutenMallProcess $processor */
      $processor = $this->getContainer()->get('batch.mall_process.rakuten');
      $processor->updateRakutenProductStock();

    } else {
      $logger->info("楽天 在庫更新CSV出力処理 select.csv: 件数が0のためファイルは作成しませんでした。");
      $this->results['message'] = "楽天 在庫更新CSV出力処理 select.csv: 件数が0のためファイルは作成しませんでした。";
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了')->setInformation($this->results));
  }

}
