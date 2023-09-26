<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Exception\RuntimeException;
use ZipArchive;


class ImportRakutenTagCsvCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('batch:import-rakuten-tag-csv')
      ->setDescription('楽天タグ一覧CSV インポート処理 ※手動実行。 tb_rakuten_tag, tb_rakuten_directory 更新')
      ->addArgument('import-file-path', InputArgument::REQUIRED, '入力データ圧縮ファイル（.zip）', null)
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('楽天タグ一覧CSV インポート処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // DB記録＆通知処理
    $logExecTitle = '楽天タグ一覧CSV インポート処理';
    $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'), false);
    $logger->setExecTitle($logExecTitle);

    try {
      $dbMain = $this->getDb('main');

      $importPath = $input->getArgument('import-file-path');
      $logger->info($importPath);

      $fs = new Filesystem();
      if (!$fs->exists($importPath) || !is_file($importPath) || !preg_match('|^(.*?)/?([^/]+)\.zip$|', $importPath, $match)) {
        throw new \RuntimeException('インポートするCSV圧縮ファイル(.zip)が見つかりませんでした。');
      }

      $decompressionDir = $match[1] . '/' . $match[2];
      $logger->info($decompressionDir);

      if ($fs->exists($decompressionDir)) {
        $fs->remove($decompressionDir);
      }

      // zip ファイル解凍
      $zip = new ZipArchive();
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

      $dbMain->query("TRUNCATE tb_rakuten_tag");
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :filePath
        INTO TABLE tb_rakuten_tag
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\\n'
        IGNORE 1 LINES
        (`ディレクトリID`, `パス名`, `分類`, `タグ名`, `タグID`, @selectTagEnabled)
        SET `項目選択肢別在庫登録可` = CASE WHEN @selectTagEnabled = '○' THEN -1 ELSE 0 END
EOD;
      $stmt = $dbMain->prepare($sql);

      $finder = new Finder();
      /** @var SplFileInfo $file */
      foreach($finder->in($decompressionDir)->name('*.csv')->sortByName()->files() as $file) {
        $logger->info($file->getPathname());

        // utf-8 LF へ変換
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

        $stmt->bindValue(':filePath', $tmpFilePath);
        $stmt->execute();

        $fs->remove($tmpFilePath);
      }

      $recordNum = $dbMain->query("SELECT COUNT(*) FROM tb_rakuten_tag")->fetchColumn(0);

      $logger->info('import file lines: ' . $fileLineNum . ' 件');
      $logger->info('import table rows: ' . $recordNum . ' 件');


      // 楽天ディレクトリテーブル 更新
      $dbMain->query("TRUNCATE tb_rakuten_directory");

      $sql = <<<EOD
        INSERT INTO tb_rakuten_directory (
            ディレクトリID
          , field01
          , field02
          , field03
          , field04
          , field05
          , field06
          , `level`
        )
        SELECT
            T2.ディレクトリID
          , T2.field01
          , T2.field02
          , T2.field03
          , T2.field04
          , T2.field05
          , T2.field06
          , 6
            - CASE WHEN T2.field01 = '' THEN 1 ELSE 0 END
            - CASE WHEN T2.field02 = '' THEN 1 ELSE 0 END
            - CASE WHEN T2.field03 = '' THEN 1 ELSE 0 END
            - CASE WHEN T2.field04 = '' THEN 1 ELSE 0 END
            - CASE WHEN T2.field05 = '' THEN 1 ELSE 0 END
            - CASE WHEN T2.field06 = '' THEN 1 ELSE 0 END
            AS `level`
        FROM (
          SELECT
               T.ディレクトリID
             , REPLACE(
               SUBSTRING(
                     SUBSTRING_INDEX(T.`パス名`, '>', 1)
                   , CHAR_LENGTH(SUBSTRING_INDEX(T.`パス名`, '>', 1 - 1)) + 1
               )
               , '>'
               , ''
            ) AS field01
            , REPLACE(
               SUBSTRING(
                     SUBSTRING_INDEX(T.`パス名`, '>', 2)
                   , CHAR_LENGTH(SUBSTRING_INDEX(T.`パス名`, '>', 2 - 1)) + 1
               )
               , '>'
               , ''
            ) AS field02
            , REPLACE(
               SUBSTRING(
                     SUBSTRING_INDEX(T.`パス名`, '>', 3)
                   , CHAR_LENGTH(SUBSTRING_INDEX(T.`パス名`, '>', 3 - 1)) + 1
               )
               , '>'
               , ''
            ) AS field03
            , REPLACE(
               SUBSTRING(
                     SUBSTRING_INDEX(T.`パス名`, '>', 4)
                   , CHAR_LENGTH(SUBSTRING_INDEX(T.`パス名`, '>', 4 - 1)) + 1
               )
               , '>'
               , ''
            ) AS field04
            , REPLACE(
               SUBSTRING(
                     SUBSTRING_INDEX(T.`パス名`, '>', 5)
                   , CHAR_LENGTH(SUBSTRING_INDEX(T.`パス名`, '>', 5 - 1)) + 1
               )
               , '>'
               , ''
            ) AS field05
            , REPLACE(
               SUBSTRING(
                     SUBSTRING_INDEX(T.`パス名`, '>', 6)
                   , CHAR_LENGTH(SUBSTRING_INDEX(T.`パス名`, '>', 6 - 1)) + 1
               )
               , '>'
               , ''
            ) AS field06
          FROM (
            SELECT
               DISTINCT
                ディレクトリID
              , t.`パス名`
            FROM tb_rakuten_tag t
          ) T
        ) T2
        ORDER BY T2.`ディレクトリID`
EOD;
      $dbMain->query($sql);

      $recordNum = $dbMain->query("SELECT COUNT(*) FROM tb_rakuten_directory")->fetchColumn(0);
      $logger->info('import table rows (directory): ' . $recordNum . ' 件');

      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'), false);
      $logger->logTimerFlush();

      $logger->info('楽天タグ一覧CSV インポート処理を終了しました。');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
        , true, "楽天タグ一覧CSV インポート処理でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

}
