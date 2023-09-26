<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace Plusnao\MainBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use MiscBundle\Entity\Repository\ChouchouClairProductRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;


class ImportChouchouClairProductsCsvCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('plusnao-main:import-chouchou-clair-products-csv')
      ->setDescription('シュシュクレール在庫連携機能 CSVデータ取込')
      ->addArgument('import-file-path', InputArgument::REQUIRED, '入力データ圧縮ファイル（.zip）', null)
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('account-type', null, InputOption::VALUE_OPTIONAL, '実行アカウント種別 user|client')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('シュシュクレール在庫連携機能 CSVデータ取込を開始しました。');

    $logger->info($input->getOption('account-type'));
    $logger->info($input->getOption('account'));

//    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
//    if ($accountId = $input->getOption('account')) {
//      /** @var SymfonyUsers $account */
//      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
//      if ($account) {
//        $this->account = $account;
//        $logger->setAccount($account);
//      }
//    }

    $logExecTitle = 'シュシュクレール在庫連携機能 CSVデータ取込';

    try {

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始')->setLogLevel(TbLog::DEBUG));

      $now = new \DateTime();

      $fileUtil = $this->getFileUtil();

      // 対象ファイルパス
      $importPath = $input->getArgument('import-file-path');
      $logger->info($importPath);

      $fs = new Filesystem();
      if (!$fs->exists($importPath) || !is_file($importPath) || !preg_match('|^(.*?)/?([^/]+)\.zip$|', $importPath, $match)) {
        throw new \RuntimeException('インポートするCSV圧縮ファイル(.zip)が見つかりませんでした。');
      }

      $decompressionDir = $match[1] . '/' . $match[2] . '_' . $now->format('YmdHis');
      $logger->info($decompressionDir);

      if ($fs->exists($decompressionDir)) {
        $fs->remove($decompressionDir);
      }

      // zip ファイル解凍
      $zip = new \ZipArchive();
      $result = $zip->open($importPath);
      if (!$result) {
        throw new \RuntimeException('can not open zip file. [' . $importPath . ']');
      }

      $zip->extractTo($decompressionDir);
      $zip->close();

      if (!$fs->exists($decompressionDir)) {
        throw new \RuntimeException('failed to extract zip file.');
      }

      // CSVファイルインポート処理
      $fileLineNum = 0;

      $dbMain = $this->getDb('main');
      $preRecordNum = $dbMain->query("SELECT COUNT(*) FROM chouchou_clair_product")->fetchColumn(0);

      $dbMain->query("SET character_set_database=cp932");
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :filePath
        REPLACE INTO TABLE chouchou_clair_product
        FIELDS TERMINATED BY ',' ESCAPED BY '' OPTIONALLY ENCLOSED BY '"'
        LINES TERMINATED BY '\r\n'
        IGNORE 1 LINES
        (
            商品管理番号
          , 商品名
          , キャッチコピー
          , JANコード
          , メーカー品番
          , カテゴリ
          , 掲載開始日
          , 掲載終了日
          , 出荷条件
          , 良品返品
          , サイズ・容量
          , 規格
          , コメント
          , 注意事項
          , スタンプ
          , スタイル
          , 検索タグ1
          , 検索タグ2
          , 検索タグ3
          , 検索タグ4
          , 検索タグ5
          , 画像1
          , 画像1キャプション
          , 画像2
          , 画像2キャプション
          , 画像3
          , 画像3キャプション
          , 画像4
          , 画像4キャプション
          , 画像5
          , 画像5キャプション
          , 画像6
          , 画像6キャプション
          , 画像7
          , 画像7キャプション
          , 画像8
          , 画像8キャプション
          , 画像9
          , 画像9キャプション
          , 画像10
          , 画像10キャプション
          , 注文欄番号
          , 商品管理枝番号
          , 内訳
          , 参考価格種別
          , 上代価格
          , 卸価格
          , セット毎数量
          , 在庫数
          , 枝番号削除フラグ
          , 価格非公開フラグ
          , 販売中
          , 販売方法
          , 販売サイト
          , 固定キーワード1
          , 固定キーワード2
          , 固定キーワード3
          , ブランド管理ID
          , 割引開始日時
          , 割引終了日時
          , 割引率
          , 送料区分
          , 商品個別送料
          , まとめ買い割引対象商品フラグ
          , 画像転載許可フラグ
        )
        SET `pre_stock` = 在庫数
          , `stock_modified` = NULL
EOD;
      $stmt = $dbMain->prepare($sql);

      $finder = new Finder();

      /** @var ChouchouClairProductRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:ChouchouClairProduct'); // CSVヘッダ行チェック用

      /** @var SplFileInfo $file */
      foreach ($finder->in($decompressionDir)->name('*.csv')->sortByName()->files() as $file) {
        $logger->info($file->getPathname());

        $fileInfo = $fileUtil->getTextFileInfo($file->getPathname());
        $fileLineNum += ($fileInfo['lineCount'] > 0 ? $fileInfo['lineCount'] - 1 : 0);

        // ヘッダ行チェック
        $inFp = fopen($file->getPathname(), 'rb');
        $firstLine = fgets($inFp);
        $firstLineItems = explode(',', mb_convert_encoding(trim($firstLine), 'UTF-8', 'SJIS-WIN'));
        foreach ($repo->getCsvFields() as $i => $field) {
          if ($firstLineItems[$i] <> $field) {
            $e = new ImportChouchouClairProductsCsvCommandInvalidHeaderException('CSVファイルのヘッダ行が一致しません。処理を中止しました。');
            $e->setFileName(mb_convert_encoding($file->getFilename(), 'UTF-8', 'SJIS-WIN'));
            throw $e;
          }
        }
        fclose($inFp);


        /*
        // utf-8 LF へ変換 => この処理では character_set_database にまかせてみる（ファイルサイズが巨大なため）
        $tmpFilePath = tempnam($decompressionDir, 'tag_csv_');
        $outFp = fopen($tmpFilePath, 'wb');
        $inFp = fopen($file->getPathname(), 'rb');

        $firstLine = true;
        while($line = fgets($inFp)) {
          // 先頭行をスキップしてカウント
          if ($firstLine) {
            $firstLine = false;
          } else {
            $fileLineNum++;
          }

          $line = trim($line) . "\n";
          fputs($outFp, mb_convert_encoding($line, 'UTF-8', 'SJIS-WIN'));
        }
        fclose($outFp);
        fclose($inFp);
        */

        $stmt->bindValue(':filePath', $file->getPathname());
        $stmt->execute();
      }

      // 解凍ディレクトリ削除
      $fs->remove($decompressionDir);

      $dbMain->query("SET character_set_database=utf8");

      $recordNum = $dbMain->query("SELECT COUNT(*) FROM chouchou_clair_product")->fetchColumn(0);

      $logger->info('import file lines: ' . $fileLineNum . ' 件');
      $logger->info('import table rows: ' . $preRecordNum . ' 件');
      $logger->info('import table rows: ' . $recordNum . ' 件');


      $logger->info('DB更新 by シュシュクレール在庫連携CSV 完了');

      $info = [
        'fileLineNum'    => $fileLineNum
        , 'recordNum'    => $recordNum
        , 'preRecordNum' => $preRecordNum
        , 'newRecordNum' => $recordNum - $preRecordNum
      ];

      $logger->info($logExecTitle . ' 終了');

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($info)->setLogLevel(TbLog::DEBUG));
      $logger->logTimerFlush();

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      $errorCode = 1;
      if ($e instanceof ImportChouchouClairProductsCsvCommandInvalidHeaderException) {
        $errorCode = 10;
      }
      return $errorCode;
    }
  }

}

class ImportChouchouClairProductsCsvCommandInvalidHeaderException extends \RuntimeException
{
  protected $fileName;

  public function setFileName($fileName)
  {
    $this->fileName = $fileName;
  }
  public function getFilePath()
  {
    return $this->fileName;
  }

}
