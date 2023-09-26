<?php
namespace MiscBundle\Util;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\StreamedResponse;
/**
 * ファイル関連ユーティリティ
 */
class FileUtil
{
  /** @var ContainerInterface */
  private $container;

  /**
   * @param ContainerInterface $container
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }

  /**
   * @return ContainerInterface
   */
  public function getContainer()
  {
    return $this->container;
  }

  /**
   * アプリケーションルートディレクトリ取得
   */
  public function getRootDir()
  {
    $rootDir = $this->getContainer()->get('kernel')->getRootDir();
    return $rootDir;
  }

  /**
   * WEB CSV出力、入力ディレクトリ取得 （WEB_CSV）
   */
  public function getWebCsvDir()
  {
    $rootDir = $this->getRootDir();
    $webCsvDir = dirname($rootDir) . '/WEB_CSV';

    return $webCsvDir;
  }

  /**
   * data ディレクトリ
   */
  public function getDataDir()
  {
    $rootDir = $this->getRootDir();
    $dataDir = dirname($rootDir) . '/data';

    return $dataDir;
  }

  /**
   * web ディレクトリ
   */
  public function getWebDir()
  {
    $rootDir = $this->getRootDir();
    $webDir = dirname($rootDir) . '/web';

    return $webDir;
  }

  /**
   * スクレイピングで取得したレスポンス出力ディレクトリ。
   *
   * 主にエラ－時のレスポンスを格納し、調査などを行うのに使用する。
   * @return string
   */
  public function getScrapingResponseDir()
  {
    $scrapingResponseDir = $this->getDataDir() . '/scraping_response';

    $fs = new FileSystem();
    if (!$fs->exists($scrapingResponseDir)) {
      $fs->mkdir($scrapingResponseDir, 0755);
    }

    return $scrapingResponseDir;
  }

  /**
   * logs ディレクトリ
   */
  public function getLogDir()
  {
    return $this->getContainer()->get('kernel')->getLogDir();
  }


  /**
   * cache ディレクトリ（環境別）
   */
  public function getCacheDir()
  {
    return $this->getContainer()->get('kernel')->getCacheDir();
  }


  /**
   * テキストファイルの行数計数、その他
   * @param string $filePath
   * @return array
   */
  public function getTextFileInfo($filePath)
  {
    $exists = file_exists($filePath) && is_file($filePath);

    $result = [
      'path'        => $filePath
      , 'basename'  => basename($filePath)
      , 'dirname'   => dirname($filePath)
      , 'exists'    => $exists
      , 'readable'  => $exists && is_readable($filePath)
      , 'writable'  => $exists && is_writable($filePath)
      , 'size'      => $exists ? filesize($filePath) : null
      , 'lineCount' => 0
    ];

    if ($exists && $result['readable']) {
      // 単純な行数を数える
      $lineCount = 0;

      $fp = fopen($filePath, 'r');
      while (!feof($fp)) {
        fgets($fp);
        $lineCount++;
      }
      fclose($fp);

      $result['lineCount'] = $lineCount;
    }

    return $result;
  }

  /**
   * テキストファイル文字コード変換一時ファイル作成
   * ファイルハンドルへの参照がなくなると削除されるため、先に作成して引数で渡す。
   * @param $tmpFile
   * @param $filePath
   * @param $fromChar
   * @param $toChar
   * @param callable $callback
   * @return string 一時ファイルのファイルパス
   */
  public function createConvertedCharsetTempFile(&$tmpFile, $filePath, $fromChar, $toChar, $callback = null)
  {
    // 文字コード変換
    $tmpInfo = stream_get_meta_data($tmpFile);
    $tmpPath = $tmpInfo['uri'];

    $fp = fopen($filePath, 'rb');

    $num = 0;
    while($line = fgets($fp)) {

      // BOMがあれば除去。本当にめんどい
      if ($num++ === 0 && $fromChar == 'UTF-8') {
        if (preg_match('/^\xEF\xBB\xBF/', $line)) {
          $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
        }
      }

      // 改行コードはLF（簡単のため。もしこれでアウトなファイル(Excel CSVなど)でアウトなケースは、
      // UTF-8に変換なんかもしないで頑張れー）
      $line = preg_replace('/[\x0D\x0A]/', '', $line) . "\n";
      $line = mb_convert_encoding($line, $toChar, $fromChar);
      if ($callback) {
        $line = $callback($line);
      }
      fputs($tmpFile, $line);
    }
    fclose($fp);

    fseek($tmpFile, 0);

    return $tmpPath;
  }

  /**
   * ファイル ダウンロード
   * @param string $filePath
   * @return StreamedResponse $response
   */
  public function downloadFile($filePath) {
    $response = new StreamedResponse();
    $response->setCallback(
      function () use ($filePath) {
        $outputFile = new \SplFileObject('php://output', 'w');
        $outputFile->fwrite(file_get_contents($filePath));
        flush();
      }
    );
    $response->headers->set('Content-type', 'application/octet-stream'); // ファイルごとに変えるか検討
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s";', basename($filePath)));
    return $response;
  }

  /**
   * @see https://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
   */
  public function tail($filename, $lines = 10, $buffer = 4096)
  {
    // Open the file（ファイルを開く）
    $f = fopen($filename, "rb");
    
    // Jump to last character（最後の文字にジャンプ）
    fseek($f, -1, SEEK_END);
    
    // Read it and adjust line number if necessary（それを読み、必要ならば行番号を調整する）
    // (Otherwise the result would be wrong if file doesn't end with a blank line)（（それ以外の場合、ファイルが空白行で終わっていなければ結果は間違っているでしょう））
    if(fread($f, 1) != "\n") $lines -= 1;
    
    // Start reading（読み始めます）
    $output = '';
    $chunk = '';
    
    // While we would like more（もっと欲しいのですが）
    while(ftell($f) > 0 && $lines >= 0)
    {
      // Figure out how far back we should jump（ジャンプする距離を計算する）
      $seek = min(ftell($f), $buffer);
      
      // Do the jump (backwards, relative to where we are)（（私たちがいる場所に対して相対的に後方へ）ジャンプします）
      fseek($f, -$seek, SEEK_CUR);
      
      // Read a chunk and prepend it to our output（チャンクを読み、それを私たちの出力の前に追加します）
      $output = ($chunk = fread($f, $seek)).$output;
      
      // Jump back to where we started reading（読み始めたところに戻る）
      fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
      
      // Decrease our line counter（ラインカウンターを減らす）
      $lines -= substr_count($chunk, "\n");
    }
    
    // While we have too many lines（行数が多すぎる）
    // (Because of buffer size we might have read too many)（（バッファサイズのため、読み過ぎた可能性があります））
    while($lines++ < 0)
    {
      // Find first newline and remove all text before that（最初の改行を見つけて、それより前のすべてのテキストを削除します）
      $output = substr($output, strpos($output, "\n") + 1);
    }
    
    // Close file and return（ファイルを閉じて戻る）
    fclose($f);
    return $output;
  }
}
