<?php

namespace BatchBundle\Command;

use MiscBundle\Util\FileUtil;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;
use UnexpectedValueException;
use phpseclib\Net\SFTP;

/**
 * バッチ処理 楽天SKU属性情報値取込処理
 */
class ImportRakutenSkuAttributeValueCommand extends PlusnaoBaseCommand
{

  const TEMPORARY_DATABASE_NAME = "TEMPORARY"; // 一時テーブルで作業を行なうため、この値は空値もしくはTEMPORARYのみ指定可能
  const CSV_WORK_DIR_PATH = "/rakuten_sku_attribute_value"; // 作業ディレクトリのパス

  protected function configure()
  {
    $this
      ->setName('batch:import-rakuten-sku-attribute-value')
      ->setDescription('楽天SKU属性情報値取込処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('import-dir', null, InputOption::VALUE_OPTIONAL, 'インポート元ディレクトリ ※指定すればダウンロードなし')
      ->addOption('import-file-datetime', null, InputOption::VALUE_OPTIONAL, 'インポートファイル日時 ※指定がなければ、最終CSVダウンロードキック時間')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL, '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN);
  }

  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = '楽天SKU属性情報値取込処理';
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getFileUtil();
    $workDirPath = $fileUtil->getDataDir().self::CSV_WORK_DIR_PATH; 
    $importDir = $input->getOption('import-dir');
    $importFileDatetime = null;
    if ($input->getOption('import-file-datetime')) {
      $importFileDatetime = new \DateTime($input->getOption('import-file-datetime'));
      if (!$importFileDatetime) {
        throw new \RuntimeException('[楽天]' . 'インポートファイル日付の指定が不正です。[' . $input->getOption('import-file-datetime') . ']');
      }
    }
    $convertedCsvPath = $workDirPath."/dl-normal-item_converted.csv"; // 作業ファイル。変換後ファイル

    try{
      // FTPからファイルを取得
      $downloadCsvPaths = $this->downloadImportCsvData($importDir,$importFileDatetime);
      
      // CSVを作業ディレクトリにコピー
      $this->copyCSVFromOrg($downloadCsvPaths, $workDirPath);
      
      // 元CSVをロード用CSVに変換
      $this->convertCSV($workDirPath, $convertedCsvPath); 

      // temporaryテーブルを作成する
      $this->createTempTable();
      
      // temporaryテーブルにロード用CSVを取り込む
      $this->loadDataFromCSVFile($convertedCsvPath);
  
      // 実テーブル(tb_productchoiceitems_rakuten_attribute.value)に、temporaryテーブルの値を反映する
      $this->updateRakutenProductchoiceitemsAttribute();
    }catch(Throwable $t){
      throw $t;
    } finally {
      // 作業ディレクトリのCSV削除
      $finder = new Finder();
      $finder->in($workDirPath)->name('*.csv');
      foreach($finder->files() as $file){
        unlink($file);
      }
    }
  }

  private function copyCSVFromOrg($downloadCsvPaths, $workDirPath){ 
    $logger = $this->getLogger();
    $fs = new Filesystem();
    if (!$fs->exists($workDirPath)) {
      // 無い場合作業用ディレクトリを作成
      $fs->mkdir($workDirPath, 0755);
    }
    foreach ($downloadCsvPaths as $filePath) {
      $sourceCSVFilePath = sprintf('%s/%s',$workDirPath,basename($filePath)); 
      if (!$fs->exists($filePath)) {
        throw new UnexpectedValueException("コピー元ファイルが存在しません。path:".$filePath);
      }
      if ($fs->exists($sourceCSVFilePath)) {
        throw new UnexpectedValueException("既にコピー先ファイルが存在します。path:".$sourceCSVFilePath);
      }
      
      // 作業ディレクトリにコピー
      if (!copy($filePath, $sourceCSVFilePath)) {
        throw new UnexpectedValueException("ファイルコピーに失敗しました。");
      }
    }

    return;
  }

  private function convertCSV($workDirPath, $convertedCSVFilePath){ 
    $logger = $this->getLogger();
    // CSVを変換前CSVから1行ずつ読み込んで変換し変換後CSVに出力する
    //   変換前CSV(dl-normal-item.csv)、文字コードEUC
    //   変換後CSV(dl-normal-item_converted.csv)、文字コードUTF-8
    //   変換方法：
    //     1.変換前csvのヘッダーをparseして保持する
    //     2.変換前csvから1行取得してヘッダー"SKU管理番号"に対応する値が空値だった場合、この操作をもう一度行う
    //     3.ヘッダー文字列"SKU管理番号","商品属性（項目）1","商品属性（値）1"に対応する値を、CSV形式で変換後csvに書き込んで改行する。ヘッダー文字列は固定の文字列です。
    //   実装上の注意：
    //     1.stream_get_contents()の代わりにfgets() を使用する
    
    $fs = new Filesystem();
    if ($fs->exists($convertedCSVFilePath)) {
      throw new UnexpectedValueException("既にロード用ファイルが存在します。path:".$convertedCSVFilePath);
    }

    // 作業ディレクトリのファイル一覧を取得
    $sourceFilePaths = [];
    $finder = new Finder();
    $finder->in($workDirPath)->name('*.csv');
    foreach($finder->files() as $file){
      $sourceFilePaths[] = $file->getPathname();
    }

    // 変換後CSVファイルを開く
    if (($outputHandle = fopen($convertedCSVFilePath, "w")) !== false) {
      // 変換後CSVに書き込むデータの生成
      $convertedCSVHeader = array("SKU管理番号", "商品属性（項目）", "商品属性（値）");
      fputcsv($outputHandle, $convertedCSVHeader);

      foreach($sourceFilePaths as $sourceFilePath){
        // 元CSVファイルを開く
        if (($inputHandle = fopen($sourceFilePath, "r")) !== false) {
          // 変換前CSVヘッダー配列の初期化
          $headers = array();
          
          // ヘッダー行をparseして保持する
          $headerLine = fgets($inputHandle);
          $charset = mb_detect_encoding($headerLine, ['SJIS-WIN', 'UTF-8', 'EUCJP-WIN']);
          $headerCols = mb_convert_encoding($headerLine, 'UTF-8', $charset);
          $headers = str_getcsv($headerCols);
          
          // ヘッダーにSKU管理番号が無い場合処理中止
          $skuCodeIndex = array_search("SKU管理番号", $headers);
          if ($skuCodeIndex === false){
            throw new UnexpectedValueException("ヘッダーが不正です");
          }
  
          // SKU管理番号以外のヘッダindex管理map
          $colIndexMap = []; // k/v=ヘッダ名/そのヘッダが何番目か
  
          // 項目名suffix
          $n = 1;
  
          // 1行ずつ読み込み
          while (($line = fgets($inputHandle)) !== false) {
            $cols = mb_convert_encoding($line, 'UTF-8', $charset);
            $cols = str_getcsv($cols);
            // SKU管理番号の値が空の場合は次の行へ
            if (empty($cols[$skuCodeIndex])) {
                continue;
            }
            // 商品属性項目毎に別行として変換後CSVに出力
            while (true) {
              $itemName = "商品属性（項目）{$n}";
              $valueName = "商品属性（値）{$n}";
              $itemNameIndex = $this->getColumnIndexFromHeaderName($itemName, $headers, $colIndexMap);
              $valueNameIndex = $this->getColumnIndexFromHeaderName($valueName, $headers, $colIndexMap);
              // 該当ヘッダーが存在し、商品属性項目の値が存在した場合は出力
              if ($itemNameIndex
              && $valueNameIndex 
              && !empty($cols[$itemNameIndex])) {
                  $newLine = array(
                      $cols[$skuCodeIndex],
                      $cols[$itemNameIndex],
                      $cols[$valueNameIndex]
                  );
                  fputcsv($outputHandle, $newLine);
              }
  
              $n++;
              if($n > 100) break;
            }
            // 項目名suffixをリセット
            $n = 1;
          }
          // 元ファイルを閉じる
          fclose($inputHandle);
        }
      }
      // 出力ファイルを閉じる
      fclose($outputHandle);

    }
    return;
  }

  private function getColumnIndexFromHeaderName($headerName, $headers, &$colIndexMap){ 
    if(isset($colIndexMap[$headerName])){
      return $colIndexMap[$headerName];
    }else{
      if(in_array($headerName, $headers)) {
        $index = array_search($headerName, $headers);
        $colIndexMap[$headerName] = $index;
        return $index;
      } else {
        return false;
      }
    }
  }

  /**
   * インポート用CSVファイル ダウンロード処理
   * dl-normal-item_xxxxxxxxx.csvのみ取得する
   * @param string $importDir FTP取得したファイルを配置するディレクトリ。この引数が指定された場合配下に置かれた全てのファイルがFTPから取得された扱いとなる
   * @param \DateTime $baseDateTime ダウンロード基準日時 ファイル名の日付がこの日付より新しいデータを全て対象とする。
   */
  private function downloadImportCsvData($importDir, $baseDateTime = null)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $commonUtil = $this->getDbCommonUtil();

    // デバッグ用。日時はフォーマットのみ見ることに注意
    if ($importDir) {
      $filePaths = [];
      $finder = new Finder();
      $finder->in($importDir)->name('/^(?:\.\/)?dl-normal-item_(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(-\d+).csv$/');
      foreach($finder->files() as $file){
        $filePaths[] = $file->getPathname();
      }
      if (empty($filePaths)) {
        throw new \RuntimeException('取込対象ファイルがありませんでした。処理を中止します。');
      }
      return $filePaths;
    }

    // 基準日時 最新キック日時
    if (!$baseDateTime) {
      $csvKicked = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_RAKUTEN_KICK);
      $lastProcessed = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_EXPORT_CSV_RAKUTEN);
      // もしキックの記録がなかったり、キックが前回CSV出力時より古ければエラー
      if (!$csvKicked) {
        throw new \RuntimeException('[楽天]' . '楽天CSVダウンロード 基準時間の指定がなく、キック処理の時間が保存されていません。処理を中止しました。');
      } else if ($lastProcessed && $csvKicked < $lastProcessed) {
        throw new \RuntimeException('[楽天]' . '楽天CSVダウンロード 基準時間の指定がなく、キック処理の時間が前回出力時以前です。処理を中止しました。');
      }

      // 1分のみ余裕を持つ（同一時間対策）
      $csvKicked->modify('-1 minute');

      $baseDateTime = $csvKicked;
    }

    if (!$baseDateTime) {
      throw new \RuntimeException('[楽天]' . 'ダウンロード基準日時が取得できませんでした。処理を中止します。');
    }

    $logTitle = 'インポート用CSVダウンロード';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('楽天 インポート用CSVダウンロード [' . $baseDateTime->format('Y-m-d H:i:s') . ' ～]');

    $importDir = $this->getRakutenImportDir();

    // FTP ダウンロード処理
    $ftpConfig = $this->getContainer()->getParameter('ftp_rakuten');
    $config = $ftpConfig['csv_download'];

    // 開発環境はパスワード決め打ち
    $env = $this->getEnvironment();
    $password = $commonUtil->getSettingValue('RAKUTEN_GOLD_FTP_PASSWORD', $env); 

    $ftp = new SFTP($config['host']);

    try {
      $ftp->login($config['user'], $password);
    } catch (\Exception $e) {
      $message = '[楽天]' . '楽天のCSVファイルダウンロード処理中、楽天のFTPにログインできませんでした。パスワードが変更されている場合は、Accessの「各種設定」から「RAKUTEN_GOLD_FTP_PASSWORD」を正しく更新してください。';
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->chdir($config['path']);

    // ダウンロードファイル一覧 絞込 (dl-normal-item)
    $latestDatetime = null; // ファイル名から取得できる最新日時
    $localPaths =[]; // ダウンロードしたファイルのパス群
    $allFiles = $ftp->nlist('./');

    // まず、利用するファイルの日付を確定
    foreach($allFiles as $file) {
      if (preg_match('/^(?:\.\/)?dl-normal-item_(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(-\d+).csv$/', $file, $m)) {
        $datetime = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]));

        // 基準日時以前ならスキップ
        if ($datetime <= $baseDateTime) {
          continue;
        }

        if ($latestDatetime < $datetime) {
          $latestDatetime = $datetime;
        }
      }
    }

    // ファイル取得
    foreach($allFiles as $file) {
      if (preg_match('/^(?:\.\/)?dl-normal-item_(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(-\d+).csv$/', $file, $m)) {
        $datetime = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]));
        if ($latestDatetime == $datetime) {

          // ダウンロード
          $localPath = sprintf('%s/%s', $importDir, preg_replace('|^\./|', '', $file));
          $ftp->get($file, $localPath);
          $localPaths[] = $localPath;
        }
      }
    }

    if (empty($localPaths)) {
      throw new \RuntimeException('取込対象ファイルがありませんでした。処理を中止します。');
    }

    return $localPaths;
  }

  /**
   * 楽天商品データファイル・ディレクトリ作成 （インポート用）
   */
  private function getRakutenImportDir()
  {
    $fileUtil = $this->getFileUtil();

    $fs = new Filesystem();
    $importPath = sprintf('%s/%s/%s/%s', $fileUtil->getWebCsvDir(), 'Rakuten/Import', (new \DateTime())->format('YmdHis'), 'rakuten');
    if (!file_exists($importPath)) {
      $fs->mkdir($importPath);
    }

    return $importPath;
  }

  private function updateRakutenProductchoiceitemsAttribute(){
    $dbMain = $this->getDoctrine()->getConnection('main');

    // tb_productchoiceitems_rakuten_attribute.valueを更新
    $sql = <<<EOD
    UPDATE tb_productchoiceitems_rakuten_attribute tpra
    INNER JOIN ( 
      SELECT
        tmp.ne_syohin_syohin_code
        , trga.id
        , tmp.attribute_value 
      FROM
        tmp_work_rakuten_noramal_item_attribute_save tmp 
        INNER JOIN tb_rakuten_genre_attribute trga 
          ON tmp.attribute_name = trga.attribute_name
    ) tv ON
      tpra.ne_syohin_syohin_code = tv.ne_syohin_syohin_code 
      AND tpra.tb_rakuten_genre_attribute_id = tv.id
    SET
      tpra.value = tv.attribute_value 
    WHERE
      tv.attribute_value != ''
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // tb_productchoiceitems_rakuten_attributeを削除
    $sql = <<<EOD
    DELETE tpra
    FROM
      tb_productchoiceitems_rakuten_attribute tpra
        INNER JOIN ( 
        SELECT
          tmp.ne_syohin_syohin_code
          , trga.id 
            , tmp.attribute_value 
        FROM
          tmp_work_rakuten_noramal_item_attribute_save tmp 
          INNER JOIN tb_rakuten_genre_attribute trga 
            ON tmp.attribute_name = trga.attribute_name
      ) tv 
        ON tpra.ne_syohin_syohin_code = tv.ne_syohin_syohin_code 
        AND tpra.tb_rakuten_genre_attribute_id = tv.id 
    WHERE
      tv.attribute_value = '';
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
  }

  private function loadDataFromCSVFile($filepath){
    $dbMain = $this->getDoctrine()->getConnection('main');

    $sql = <<<EOD
    LOAD DATA LOCAL INFILE :filepath
    INTO TABLE tmp_work_rakuten_noramal_item_attribute_save
    FIELDS TERMINATED BY ',' ENCLOSED BY '"' ESCAPED BY ''
    LINES TERMINATED BY '\\n' IGNORE 1 LINES
    (@1, @2, @3)
    SET
    ne_syohin_syohin_code = @1,
    attribute_name = @2,
    attribute_value = @3
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':filepath', $filepath);
    $stmt->execute();
  }

  private function createTempTable(){
    $dbMain = $this->getDoctrine()->getConnection('main');

    $temporaryWord = self::TEMPORARY_DATABASE_NAME;

    // 一時テーブル楽天アイテム属性保持
    $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_rakuten_noramal_item_attribute_save");
    $sql = <<<EOD
    CREATE {$temporaryWord} TABLE tmp_work_rakuten_noramal_item_attribute_save  (
      ne_syohin_syohin_code VARCHAR(255) NOT NULL 
    , attribute_name VARCHAR(255) NOT NULL
    , attribute_value VARCHAR(255) 
    ) Engine=InnoDB DEFAULT CHARACTER SET utf8;
EOD;
    $dbMain->exec($sql);
  }
}
