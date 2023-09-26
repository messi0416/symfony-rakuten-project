<?php
namespace MiscBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\Command\ProductImageUploadFtpCommand;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

use Symfony\Component\Filesystem\Filesystem;

use MiscBundle\Exception\ValidationException;

class Misc202210MallImageUploadCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** 対象店舗文字列：楽天激安プラネット */
  const TARGET_SHOP_GEKIPLA = 'gekipla';
  /** 対象店舗文字列：EC-CUBE */
  const TARGET_SHOP_CUBE = 'cube';

  const SHOP_LIST = [
    self::TARGET_SHOP_GEKIPLA,
    self::TARGET_SHOP_CUBE
  ];

  private $account;

  private $results;

  private $settingKey = '';

  // 店舗名
  private $targetShopName;
  // parameters.env.yml用
  private $targetConfig;
  // テーブル名
  private $targetTable;

  protected function configure()
  {
    $this
    ->setName('misc:202210-mall-image-upload')
    ->setDescription('初期画像アップロード')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ->addOption('shop', null, InputOption::VALUE_OPTIONAL, '店舗名指定（gekipla/cube）', 'gekipla')
    ;
  }

  /**
   * 画像アップロードを実行する。
   * 通常画像アップロードと同じ頻度（10分おきに2GB）で実装すると楽天側に怒られそうなので、ゆっくり処理するために別クラス
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    // 本番サーバでテスト環境起動をしてしまわない様にブロック
    $env = $this->getEnvironment();
    if ($env === 'test' &&  !file_exists('/this_is_dev_server')) {
      $message = 'このタスクは本番サーバで env=test で実行することはできません。';
      $logger->error($message);
      $output->writeln($message);
      return 1;
    }

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // 店舗に関係する情報取得
    try{
      // 店舗名チェック
      $this->validate($input);
      $this->targetShopName = $input->getOption('shop');
      switch ($this->targetShopName) {
        case self::TARGET_SHOP_GEKIPLA:
          $this->settingKey = 'GEKIPLA_GOLD_FTP_PASSWORD';
          $this->targetTable = 'tb_rakuten_gekipla_information';
          $this->targetConfig = 'ftp_rakuten_' . self::TARGET_SHOP_GEKIPLA;
          break;
        case self::TARGET_SHOP_CUBE:
          $this->settingKey = 'CUBE_FTP_PASSWORD';
          $this->targetTable = 'tb_cube_information';
          $this->targetConfig = 'ftp_' . self::TARGET_SHOP_CUBE;
          break;
      }
    } catch (ValidationException $e) {
      $logger->info('初期画像アップロードでパラメーターエラーが発生しました。' . $e->getMessage());
      $logger->logTimerFlush();
      return 1;
    }
    
    $logExecTitle = '初期画像アップロード(' . $this->targetShopName . ')';
    $logger->info($logExecTitle . 'を開始しました。');

    // 時刻チェック。2-3時台ならば終了（cron設定に合わせる事）
    $now = new \DateTime();
    if ($now->format('H') === '02' || $now->format('H') === '03') {
      $message = "日時画像チェック処理との重複を避けるため、$logExecTitle は現在は起動できません。現在時刻：" . $now->format('YmdHis');
      $logger->info($message);
      $output->writeln($message);
      return 1;
    }

    // DB記録＆通知処理
    $logger->setExecTitle($logExecTitle);

    try {
      $result = $this->process();
      $logger->info($logExecTitle . 'を終了しました。result = [' . print_r($result, true) . ']');
      $logger->logTimerFlush();

    } catch (\Throwable $t) {
      $logger = $this->getLogger();
      $logger->error($logExecTitle . ':' . $t->getMessage() . $t->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog(null, 'エラー終了')->setInformation($t->getMessage() . ':' . $t->getTraceAsString())
          , true, $logExecTitle . 'でエラーが発生しました。', 'error'
          );
      $logger->logTimerFlush();
    }
  }

  /**
   * 初期画像アップロード。
   * 楽天PlusNao以外は容量が小さいため、販売対象のみアップロードする。
   *
   * そのため楽天PlusNaoのように、
   * 「全体の画像アップロード日時だけ管理して、それより新しい画像を全てアップロード」
   * ではなく、代表商品ごとに最終アップロード日時を管理する。
   *
   * 現在の販売対象は即納・一部即納のもの。
   * 
   */
  private function process()
  {
    $logger = $this->getLogger();
    $container = $this->getContainer();
    $uploadDatetime = new \DateTime(); // DB保存用に現在時刻を確保　念のため2分ずらす この2分間で更新された画像は次回もアップロードされる
    $uploadDatetime->modify('-2 mins');

    $fs = new FileSystem();

    // FTPアップロード処理
    $logTitle = '商品画像FTPアップロード(' . $this->targetShopName . ')';
    $logger->info($logTitle . ': ファイルアップロード 開始');
    $uploadSize = 0;
    $uploadCount = 0;

    // max_timestamp、min_timestampは関係ないので削除
    $results = [
      'count' => 0
      , 'size' => 0
      , 'file_missing' => []
    ];

    // 画像一覧取得　対象がなければ終了
    $newFileList = $this->findNewImages();

    // FTP接続
    $ftp = $this->openFtp();

    // アップロード日付が代表商品単位で更新のため、代表商品単位でアップロードする。
    // 代表商品のうち1ファイルだけアップロード、などすると、残りファイルは取りこぼすので不可。

    //  1回のバッチ実行で、「アップロード最大量（+α）×ループ回数」ぶんのファイルをアップロードする。

    // DB反映を行うアップロード最大量 この単位でcommitする　小さくするとエラー後に多重にアップロードする量が減る
    // 「これを超えたら代表商品が変わるタイミングで終了」なので、最大でこれ +5MB程度アップロードされると考える
    $limitUploadSize = 10 * 1000 * 1000; // 1周10MB

    // ループ回数
    $limitLoopCount = 8; // 8周

    $daihyoSyohinCodeList = []; // 1回のコミット分の代表商品コードを格納する
    $pos = 0; // 現在のカーソル位置　戻ったりするので必要
    $previousCode = ''; // 現在の代表商品コード　代表商品コードの区切りを探すのに使う
    $totalUploadSize = 0; // 全体のアップロードサイズ合計
    $uploadSize = 0; // 現在ループのアップロードサイズ合計
    $uploadCount = 0; // アップロードしたファイル数

    $imageDir = $container->getParameter('product_image_dir');

    /** @var ProductImages $image */
    $image = null;
    for ($i = 0; $i < $limitLoopCount; $i++) {
      for ($pos; $pos < count($newFileList); $pos++) {
        $image = $newFileList[$pos];
        $daihyoSyohinCode = $image['daihyo_syohin_code'];
        // サイズ上限越え、かつ代表商品の区切りであれば、現在のファイルはアップロードせずDB更新
        if ($uploadSize >= $limitUploadSize && $daihyoSyohinCode != $previousCode) {
          $this->updateUploadDate(array_unique($daihyoSyohinCodeList), $uploadDatetime);
          $daihyoSyohinCodeList = []; // DB更新が終わったら空に戻す
          $uploadSize = 0;
          break; // breakすると $pos は進まない様子　なので次のループも $pos は同じところから始まる
        }
        $previousCode = $daihyoSyohinCode;
        $daihyoSyohinCodeList[] = $daihyoSyohinCode; // 後でarray_uniqueするのでひとまず全部入れる

        // ファイルアップロード
        $currentSize = $this->uploadImage($image, $ftp, $fs, $imageDir, $results['file_missing']);
        $uploadSize += $currentSize;
        $totalUploadSize += $currentSize;
        $uploadCount++;
      }
      if (count($daihyoSyohinCodeList) > 0) {
        $this->updateUploadDate(array_unique($daihyoSyohinCodeList), $uploadDatetime);
      }
    }
    $ftp->close();

    $results['count'] = $uploadCount;
    $results['size'] = $totalUploadSize;

    return $results;
  }

  /**
   * 新規・更新商品画像 取得処理
   *
   * 即納・一部即納のもののみがアップロード対象
   * 代表商品ごとにアップロード時刻を保持するため、代表商品コードでソート
   *
   * tb_○○_informationにレコードがない商品があり得るが、それはアップロード対象外（初期ver対応）
   */
  private function findNewImages()
  {
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      SELECT
        img.daihyo_syohin_code,
        img.`directory`,
        img.filename,
        img.updated
      FROM product_images img
      INNER JOIN tb_mainproducts_cal cal
        ON img.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN {$this->targetTable} ri
        ON img.daihyo_syohin_code = ri.daihyo_syohin_code
      WHERE
        cal.deliverycode IN (0, 1)
        AND (
          ri.last_image_upload_datetime IS NULL
          OR img.updated >= ri.last_image_upload_datetime
        )
      ORDER BY img.daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * FTPに接続し、レスポンスを返却する
   */
  private function openFtp() {
    $container = $this->getContainer();
    $ftpConfig = $container->getParameter($this->targetConfig);

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    // パスワード取得
    // 楽天店舗：tb_setting
    // 楽天以外：parameters.env.yml
    switch ($this->targetShopName) {
      case self::TARGET_SHOP_GEKIPLA:
        $config = $ftpConfig['image'];
        $env = $this->getApplication()->getKernel()->getEnvironment();
        $password = $commonUtil->getSettingValue($this->settingKey, $env);
        break;
      case self::TARGET_SHOP_CUBE:
        $config = $ftpConfig['image_upload'];
        $password = $config['password'];
        break;
    }

    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    $ftp->connect($config['host']);

    try {
      $ftp->login($config['user'], $password);
    } catch (\Exception $e) {
      $message = $this->targetShopName . 'の画像アップロード処理中、FTPにログインできませんでした。パスワードが変更されている場合は、Accessの「各種設定」から「 ' . $this->settingKey . ' 」を正しく更新してください。';
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->pasv(true);
    $ftp->chdir($config['path']);
    return $ftp;
  }

  /**
   * 指定された画像を1ファイルアップロードする。
   * その際、必要に応じて整形する。
   * 整形があるため、アップロードしたファイルサイズを返却する。
   */
  private function uploadImage($image, $ftp, $fs, $imageDir, &$missingList) {
    $dir = $image['directory'];
    $filename = $image['filename'];

    // リモートパス
    // EC-CUBEはフォルダ分けをしない
    switch ($this->targetShopName) {
      case self::TARGET_SHOP_GEKIPLA:
        // ディレクトリ作成
        $dirs = $ftp->nlist('.');
        if (!is_array($dirs)) {
          throw new \RuntimeException('ディレクトリ一覧の取得に失敗しました。(' . print_r($dirs, true) . ')');
        }
        if (!in_array($dir, $dirs)) {
          $ftp->mkdir($dir);
        }
        $remotePath = sprintf('%s/%s', $dir, $filename);
        break;
      case self::TARGET_SHOP_CUBE:
        $remotePath = $filename;
        break;
    }

    $filePath = sprintf('%s/%s/%s', $imageDir, $dir, $filename);
    if ($fs->exists($filePath)) {
      $im = new \Imagick($filePath);
      $width = $im->getImageWidth();
      $height = $im->getImageHeight();
      $fileSize = filesize($filePath);

      /* RAKUTEN_AMAZON_MAX_WIDTH * RAKUTEN_AMAZON_MAX_HEIGHTより画素数が大きいか、
        RAKUTEN_AMAZON_MAX_SIZE以上のファイルサイズの画像は
        RAKUTEN_AMAZON_MAX_WIDTH * RAKUTEN_AMAZON_MAX_HEIGHTに収まり、
        かつRAKUTEN_AMAZON_MAX_SIZEのサイズに収まるようにリサイズしてからアップロード */
      if ($this->targetShopName === self::TARGET_SHOP_GEKIPLA && 
          ($width > ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_WIDTH 
          || $height > ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_HEIGHT
          || $fileSize >= ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_SIZE)) {
        $im->setImageFormat('jpg');
        $im->setOption('jpeg:extent', ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_SIZE);

        if ($width > ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_WIDTH || $height > ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_HEIGHT) {
          $im->adaptiveResizeImage(ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_WIDTH, ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_HEIGHT, true);
        }

        $fp = fopen('php://temp', 'w+b');
        fwrite($fp, $im->getImageBlob());

        rewind($fp);
        $fstats = fstat($fp);
        $fileSize = $fstats['size'];

        $ftp->fput($remotePath, $fp, FTP_BINARY);
        fclose($fp);
      } else if($this->targetShopName === self::TARGET_SHOP_CUBE &&
                ($width > ProductImageUploadFtpCommand::CUBE_VARIATION_MAX_WIDTH
                || $height > ProductImageUploadFtpCommand::CUBE_VARIATION_MAX_HEIGHT)) {
        $im->setImageFormat('jpg');
        $im->adaptiveResizeImage(ProductImageUploadFtpCommand::CUBE_VARIATION_MAX_WIDTH, ProductImageUploadFtpCommand::CUBE_VARIATION_MAX_HEIGHT, true);

        $fp = fopen('php://temp', 'w+b');
        fwrite($fp, $im->getImageBlob());

        rewind($fp);
        $fstats = fstat($fp);
        $fileSize = $fstats['size'];

        $ftp->fput($remotePath, $fp, FTP_BINARY);
        fclose($fp);
      } else {
        $ftp->put($remotePath, $filePath, FTP_BINARY);
      }
      $im->clear();
      return $fileSize;

    // ファイルが存在しない
    } else {
      $missingList[] = sprintf('%s : %s', $image['daihyo_syohin_code'], $remotePath);
    }
  }

  /**
   * 商品画像をアップロードした代表商品は、Informationテーブルの日時を更新する
   * @param array $daihyoSyohinCodeList アップロードした代表商品リスト。
   * @param $updateDatetime DBに設定する更新日時
   */
  private function updateUploadDate($daihyoSyohinCodeList, $updateDatetime) {
    $dbMain = $this->getDb('main');
    $quoteList = array_map(function ($daihyoSyohinCode) {
      return "'$daihyoSyohinCode'";
    }, $daihyoSyohinCodeList);
    $daihyoSyohinCodeListStr = implode(',', $quoteList);

    $sql = <<<EOD
      UPDATE {$this->targetTable} i
      SET i.last_image_upload_datetime = :updateDatetime
      WHERE i.daihyo_syohin_code IN ({$daihyoSyohinCodeListStr})
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':updateDatetime', $updateDatetime->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * 店舗名チェック
   * 
   * @param InputInterface $input
  */
  private function validate(InputInterface $input)
  {
    if (!in_array($input->getOption('shop'), self::SHOP_LIST, true)) {
      throw new ValidationException('対象店舗は、' . implode(', ', self::SHOP_LIST) . 'から指定してください [' . $input->getOption('shop') . ']');
    }
  }  
}
