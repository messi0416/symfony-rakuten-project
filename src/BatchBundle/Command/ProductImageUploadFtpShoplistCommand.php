<?php
/**
 * 商品画像 一括アップロード処理（SHOPLIST）
 * 前回アップロード以降の更新日時で、
 */

namespace BatchBundle\Command;

use Doctrine\DBAL\Statement;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ProductImageUploadFtpShoplistCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results = array(
  );
  private $errors = array();

  const MAX_UPLOAD_SIZE = 100000000; // 100MB
  const WAIT = 360;
  const UPLOAD_IMAGE_COUNT_LIMIT = 1000; // アップロードのし過ぎで怒られたため、再発防止策として一日当たり1000枚までしかアップロードしない

  protected function configure()
  {
    $this
      ->setName('batch:product-image-upload-ftp-shoplist')
      ->setDescription('商品画像アップロード処理（SHOPLIST）')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('start-code', null, InputOption::VALUE_OPTIONAL, '取得開始商品コード', '')
      ->addOption('last-updated', null, InputOption::VALUE_OPTIONAL, '取得下限日時 yyyy-mm-dd', null)
      ->addOption('code-file', null, InputOption::VALUE_OPTIONAL, '対象商品コード一覧ファイル（改行区切り）', null)
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();

    $this->setInput($input);
    $this->setOutput($output);

    $doctrine = $this->getDoctrine();

    $logExecTitle = '商品画像アップロード処理（SHOPLIST 一括）';
    $logger->setExecTitle($logExecTitle);
    $logger->info($logExecTitle . 'を開始しました。');

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

      // アップロード処理
      try {
        $logger->info('SHOPLIST 画像アップロード 対象取得');

        $this->results['shoplist'] = $this->processUpload();

        $logger->info('SHOPLIST 画像アップロード件数: ' . $this->results['shoplist']['count']);

      } catch (\Exception $e) {
        $this->errors['shoplist'] = $e->getMessage();
      }

      // エラーがあれば通知する
      if (count($this->errors)) {
        $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'アップロード エラー')->setInformation(['results' => $this->results, 'errors' => $this->errors])->setLogLevel(TbLog::ERROR));
      }

      // 処理完了
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, 'アップロード済')->setInformation(['results' => $this->results, 'errors' => $this->errors]));
      $logger->logTimerFlush();

      $logger->info($logExecTitle . ': 完了');

      return 0;

    } catch (\Exception $e) {

      $logger->error($e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation(['error' => $e->getMessage()])
        , true, $logExecTitle . "でエラーが発生しました。", 'error'
      );

      return 1;
    }
  }

  /**
   * 商品コードファイルからの画像取得処理
   * @param string $codeFile
   * @return Statement
   * @throws \Doctrine\DBAL\DBALException
   */
  private function findImagesByCodeListFile($codeFile)
  {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();

    $codeList = [];

    $fp = fopen($codeFile, 'rb');
    while($code = fgets($fp)) {
      $code = trim($code);
      if (preg_match('/^[a-zA-Z0-9_-]+$/', $code)) {
        $codeList[] = $dbMain->quote($code, \PDO::PARAM_STR);
      }
    }
    fclose($fp);

    $codeListStr = implode(',', $codeList);
    $sql = <<<EOD
      SELECT
        p.*
      FROM product_images p
      INNER JOIN tb_mainproducts m ON p.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT s.`商品管理番号（商品URL）` AS syohin_code
        FROM tb_shoplist_product_stock s
      ) s ON p.daihyo_syohin_code = s.syohin_code
      INNER JOIN (
        SELECT daihyo_syohin_code, MAX(code) as code FROM product_images
        GROUP BY daihyo_syohin_code
      ) max_image_code ON p.daihyo_syohin_code = max_image_code.daihyo_syohin_code
      LEFT JOIN product_images_attention_image ai ON ai.md5hash = p.md5hash
      WHERE p.daihyo_syohin_code IN ( {$codeListStr} )
        AND (p.code <> max_image_code.code OR ai.attention_flg IS NULL OR ai.attention_flg = 0) -- 末尾のアテンション画像はアップロード対象外
      ORDER BY p.daihyo_syohin_code, p.code
      LIMIT :limit
EOD;
    $stmt = $dbMain->prepare($sql);
    $margin = 1;
    $stmt->bindValue(':limit', self::UPLOAD_IMAGE_COUNT_LIMIT + $margin, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt;
  }

  /**
   * 新規・更新商品画像 取得処理
   * @param \DateTime $lastUpdated
   * @param string $startCode
   * @return Statement
   * @throws \Doctrine\DBAL\DBALException
   */
  private function findNewImages($startCode = '')
  {
    $dbMain = $this->getDb('main');

    // SHOPLISTへ登録されている商品で、最終更新日時より新しい画像のみを取得
    $sql = <<<EOD
      SELECT
        p.*
      FROM product_images p
      INNER JOIN tb_mainproducts m ON p.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_shoplist_information i ON p.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN (
        SELECT
          DISTINCT s.`商品管理番号（商品URL）` AS syohin_code
        FROM tb_shoplist_product_stock s
      ) s ON p.daihyo_syohin_code = s.syohin_code
      INNER JOIN (
        SELECT daihyo_syohin_code, MAX(code) as code FROM product_images
        GROUP BY daihyo_syohin_code
      ) max_image_code ON p.daihyo_syohin_code = max_image_code.daihyo_syohin_code
      LEFT JOIN product_images_attention_image ai ON ai.md5hash = p.md5hash
      WHERE
        (p.code <> max_image_code.code OR ai.attention_flg IS NULL OR ai.attention_flg = 0) -- 末尾のアテンション画像はアップロード対象外
        AND (
            i.last_image_upload_datetime IS NULL
         OR p.updated >= i.last_image_upload_datetime
        )
        AND p.daihyo_syohin_code >= :startCode
      ORDER BY p.daihyo_syohin_code, p.code
      LIMIT :limit
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':startCode', $startCode, \PDO::PARAM_STR);
    $margin = 1;
    $stmt->bindValue(':limit', self::UPLOAD_IMAGE_COUNT_LIMIT + $margin, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt;
  }

  /**
   * アップロード処理
   */
  private function processUpload()
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logTitle = '商品画像FTPアップロード(SHOPLIST)';

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    $startCode = $this->input->getOption('start-code');
    $lastUpdated = $this->input->getOption('last-updated');
    $codeFile = $this->input->getOption('code-file');

    // 対象商品コード指定
    if ($codeFile) {
      if (!file_exists($codeFile) || !is_readable($codeFile)) {
        throw new \RuntimeException('no code list file. aborted.');
      }
      $logger->info($logTitle . '：指定対象商品コードファイル: ' . $codeFile);
    } else {
      if (strlen($lastUpdated)) {
        $lastUpdated = new \DateTime($lastUpdated);
      } else {
        $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_SHOPLIST);
      }
      $logger->info($logTitle . '：最終更新日: ' . $lastUpdated->format('Y-m-d H:i:s'));
    }

    // FTPアップロード処理
    $logger->info($logTitle . ': ファイルアップロード 開始');
    $uploadSize = 0;
    $uploadCount = 0;

    $results = [
        'count' => 0
      , 'size' => 0
      , 'min_timestamp' => null
      , 'max_timestamp' => null
      , 'file_missing' => []
      , 'no_code' => []
    ];

    $imageDir = $container->getParameter('product_image_dir');

    $config = $container->getParameter('ftp_shoplist');
    if (!$config || !isset($config['image'])) {
      throw new \RuntimeException('no ftp config (SHOPLIST image upload)');
    }
    $config = $config['image'];
    $config['password'] = $commonUtil->getSettingValue(TbSetting::KEY_SHOPLIST_FTP_PASSWORD); 

    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    try {
      $ftp->connect($config['host']);
      $ret = $ftp->login($config['user'], $config['password']);
      if (!$ret) {
        throw new \RuntimeException('ftp login failed.');
      }
    } catch (\Exception $e) {
      $message = 'SHOPLISTの画像アップロード処理中、FTPにログインできませんでした。';
      $logger->error($message);
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->pasv(true);
    $ftp->chdir($config['path']);

    // 画像一覧取得
    if ($codeFile) {
      $stmt = $this->findImagesByCodeListFile($codeFile);
    } else {
      $stmt = $this->findNewImages($startCode);
    }
    $logger->info('SHOPLIST画像一括アップロード 件数:' . $stmt->rowCount() . '件');

    $dbMain = $this->getDb('main');
    // 最終アップロード日時更新ステートメント
    $sql = <<<EOD
          UPDATE tb_shoplist_information
          SET last_image_upload_datetime = NOW()
          WHERE daihyo_syohin_code = :daihyoSyohinCode
EOD;
    $updateStmt = $dbMain->prepare($sql);

    $maxDateTime = null;
    $minDateTime = null;

    $imageList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    if ($stmt->rowCount() > 0) {
      $currentCode = $imageList[0]['daihyo_syohin_code'];

      // 制限枚数以下であればすべてアップロードする
      if ($stmt->rowCount() <= self::UPLOAD_IMAGE_COUNT_LIMIT) {
        $imageList[] = ['daihyo_syohin_code' => null]; // 番兵を利用して処理を簡易化
      }

      $sameCodeList = [];
      for ($i = 0;$i < count($imageList);$i++) {
        if ($currentCode === $imageList[$i]['daihyo_syohin_code']) {
          $sameCodeList[] = $imageList[$i];
          continue;
        }

        if ($i > self::UPLOAD_IMAGE_COUNT_LIMIT) {
          break;
        }

        // アップロード処理
        foreach ($sameCodeList as $image) {
          $fileNum = null;
          if (preg_match('/p(\d+)/', $image['code'], $m)) {
            $fileNum = $m[1];
          } else {
            $results['no_code'][] = sprintf('%s : %s', $image['daihyo_syohin_code'], $image['code']);
            continue;
          }

          $dir = $image['directory'];
          $filename = $image['filename'];
          $newDir = strtolower($image['daihyo_syohin_code']); // 小文字商品コード（※仕様）
          $newFilename = sprintf('%d.jpg', $fileNum);

          $remotePath = sprintf('%s/%s', $newDir, $newFilename);
          $filePath = sprintf('%s/%s/%s', $imageDir, $dir, $filename);

          $updated = $image['updated'] ? new \DateTime($image['updated']) : null;
          if ($updated) {
            if (!$maxDateTime || $maxDateTime < $updated) {
              $maxDateTime = $updated;
            }
            if (!$minDateTime || $minDateTime > $updated) {
              $minDateTime = $updated;
            }
          }

          $fs = new Filesystem();
          if ($fs->exists($filePath)) {
            // ディレクトリ作成
            $dirs = $ftp->nlist('.');

            if (!in_array($newDir, $dirs)) {
              $ftp->mkdir($newDir);
            }

            // アップロード処理
            $ftp->put($remotePath, $filePath, FTP_BINARY);

            $uploadCount++;
            $uploadSize += filesize($filePath);

            // sleep
            if ($uploadSize >= self::MAX_UPLOAD_SIZE) {
              $logger->info($logTitle . ': アップロード制限に達しました。スリープします');
              $uploadSize = 0;
              sleep(self::WAIT);
            }
          } else {
            $results['file_missing'][] = sprintf('%s : %s', $image['daihyo_syohin_code'], $remotePath);
          }
        }

        // アップロード時間の更新
        $updateStmt->bindValue(':daihyoSyohinCode', $currentCode);
        $updateStmt->execute();

        // $sameCodeListと$currentCode を初期化、更新し、次のループへ
        $sameCodeList = [];
        $sameCodeList[] = $imageList[$i];
        $currentCode = $imageList[$i]['daihyo_syohin_code'];
      }
    }

    $ftp->close();

    // 最終アップロードタイムスタンプ
    if (!$codeFile) {
      $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_SHOPLIST, (new \DateTime()));
    }

    $results['count'] = $uploadCount;
    $results['size'] = $uploadSize;
    $results['min_timestamp'] = $minDateTime ? $minDateTime->format('Y-m-d H:i:s') : null;
    $results['max_timestamp'] = $maxDateTime ? $maxDateTime->format('Y-m-d H:i:s') : null;

    return $results;
  }
}
