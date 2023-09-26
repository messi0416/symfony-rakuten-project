<?php
/**
 * NextEngine CSV出力処理 セット商品CSV
 */

namespace BatchBundle\Command;

use BatchBundle\Job\NextEngineUploadJob;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportCsvNextEngineSetProductCommand extends PlusnaoBaseCommand
{
  use CommandBaseTrait;

  private $results;

  const CSV_FILENAME_SET_PRODUCT  = 'NE_SetProduct.csv';

  const EXPORT_PATH = 'NextEngine/SetProduct';

  const CURRENT_UPLOAD_PATH = 'NextEngine/CurrentUpload';

  protected $exportPath;

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-nextengine-set-product')
      ->setDescription('CSVエクスポート NextEngine セット商品CSV出力')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, '出力ディレクトリ', null)
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'NextEngineへのアップロードを行うか', '0') // デフォルト OFF
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, '対象のNE環境', 'test') // デフォルト test
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN) // このまま記載すること
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = 'NextEngineセット商品CSV出力';
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
   */
  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    $doUpload = (bool)$input->getOption('do-upload');

    // 出力パス
    $this->exportPath = $input->getOption('export-dir');
    if (!$this->exportPath) {
      $now = new \DateTimeImmutable();
      $this->exportPath = $this->getFileUtil()->getWebCsvDir() . '/' . self::EXPORT_PATH . '/' . $now->format('YmdHis');
    }

    // 出力ディレクトリ 作成
    $fs = new FileSystem();
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }

    $filePath = $this->exportPath . '/' . self::CSV_FILENAME_SET_PRODUCT;

    // --------------------------------------
    // セット商品CSVデータ作成
    // --------------------------------------
    $this->exportSetProductCsv($doUpload, $filePath);

    $finder = new Finder(); // 出力対象がない場合は終了
    $fileNum = $finder->in($this->exportPath)->files()->count();
    if (!$fileNum) {
      $logger->addDbLog($logger->makeDbLog(null, '出力対象なし', '終了'));
      // 空のディレクトリを削除
      $fs = new FileSystem();
      $fs->remove($this->exportPath);
      return 0;
    }
  }

  /**
   * セット商品CSV出力
   * @param string $setProductPath
   * @throws \Doctrine\DBAL\DBALException
   */
  private function exportSetProductCsv($doUpload, $setProductPath)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    $logger->info('NextEngine セット商品CSV出力');

    // 削除対象とする販売終了日からの日数　未設定であれば10日
    $delDate = new \DateTime();
    $delDaysFromSalesEnd = $this->getDbCommonUtil()->getSettingValue('DEL_DAYS_FROM_SALES_END');
    if (! $delDaysFromSalesEnd) {
      $delDaysFromSalesEnd = 10;
    }
    $delDate->modify("-${delDaysFromSalesEnd} day"); // 日数を元に日付を計算
    $logger->debug("NEセットCSV出力：販売終了日から $delDaysFromSalesEnd 日経過していれば登録しない。削除基準日：" . $delDate->format('Y-m-d'));

    // セット商品を取得。販売中、または販売終了から10日以内であれば登録
    $targetDate = new \DateTime();
    $targetDate->modify("-10 day"); // 日数を元に日付を計算
    $sql = <<<EOD
      SELECT
          d.set_ne_syohin_syohin_code as set_syohin_code
        , d.ne_syohin_syohin_code as syohin_code
        , d.num as suryo
        , m.daihyo_syohin_name as set_syohin_name
        , cal.baika_tnk as set_baika_tnk
        , m.daihyo_syohin_code as daihyo_syohin_code
      FROM tb_set_product_detail d
      INNER JOIN tb_productchoiceitems pci ON d.set_ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE m.set_flg <> 0
        AND (cal.endofavailability IS NULL OR cal.endofavailability > :targetDate)
        AND cal.deliverycode <> :deliveryCodeTemporary
      ORDER BY set_syohin_code,syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':targetDate', $delDate->format('Y-m-d'));
    $stmt->execute();

    $count = $stmt->rowCount();
    $logger->info('NextEngine セット商品CSV出力 : ' . $count);

    if ($count) {
      $headers = $this->getCsvHeadersSetProduct();

      $fs = new FileSystem();
      $fileExists = $fs->exists($setProductPath);
      $fp = fopen($setProductPath, 'ab');

      if (!$fileExists) {
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }

      while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

        $data = [
            'set_syohin_code'    => $row['set_syohin_code']
          , 'syohin_code'        => $row['syohin_code']
          , 'suryo'              => $row['suryo']
          , 'set_syohin_name'    => $row['set_syohin_name']
          , 'set_baika_tnk'      => $row['set_baika_tnk']
          , 'daihyo_syohin_code' => $row['daihyo_syohin_code']
        ];
        fputs($fp, mb_convert_encoding($stringUtil->convertArrayToCsvLine($data, $headers), 'SJIS-WIN', 'UTF-8') . "\r\n");
      }
      fclose($fp);

      // アップロード時は作業フォルダにもコピー。アップロード処理は ExportCsvNextEngineProductEnqueueCommand で処理
      if ($doUpload) {
        $output = $this->getFileUtil()->getWebCsvDir() . '/' . self::CURRENT_UPLOAD_PATH;
        $fs->copy($setProductPath, $output . '/' . self::CSV_FILENAME_SET_PRODUCT);
      }
    }
  }

  /**
   * CSVヘッダ取得（セット商品）
   */
  private function getCsvHeadersSetProduct()
  {
    $headers = [
        'set_syohin_code'
      , 'syohin_code'
      , 'suryo'
      , 'set_syohin_name'
      , 'set_baika_tnk'
      , 'daihyo_syohin_code'
    ];

    return $headers;
  }

}
