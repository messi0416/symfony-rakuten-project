<?php
/**
 * 商品画像 アップロード処理（楽天・Yahoo・PPM）
 * 10分～30分程度の間隔で定期処理
 *
 * Yahooおとりよせ.comの商品画像は、ExportCsvYahooOtoriyoseCommand ・ ExportCsvYahooUploadCommand からアップロードする。
 * このためおとりよせ.comはバリエーション画像のみ。
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Entity\TbMaintenanceSchedule;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\Repository\TbMaintenanceScheduleRepository;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Exception\RuntimeException;
use phpseclib\Net\SFTP;

class ProductImageUploadFtpCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;
  private $results = array(
      'rakuten' => null
    , 'rakuten_amazon' => null
    , 'rakuten_motto' => null
    , 'rakuten_laforest' => null
    , 'rakuten_dolcissimo' => null
    , 'rakuten_gekipla' => null
    , 'cube' => null
    , 'yahoo_plusnao' => null
    , 'yahoo_kawaemon' => null
    , 'yahoo_otoriyose' => null
    , 'ppm' => null
  );
  private $errors = array();

  const MAX_UPLOAD_SIZE = 2000000000; // 2GB
  
  /** Yahoo商品画像・商品詳細画像 */
  const MAX_UPLOAD_SIZE_YAHOO_PRODUCT = 25000000; // 25MB
  /** Yahooバリエーション画像（追加画像） */
  const MAX_UPLOAD_SIZE_YAHOO_VALIATION = 20000000; // 20MB

  const RETRY_WAIT = 10;
  const RETRY_COUNT_RAKUTEN = 5; // 楽天は妙にエラーが多いため、リトライを行う

  // YahooCSV出力と被るとDB更新でタイムアウトエラーが発生する場合があるためスキップする時刻 開始（H:i） */
  const YAHOO_SKIP_TIME_FROM = '00:00';
  /* Yahoo実行をスキップする時刻　終了（H:i） */
  const YAHOO_SKIP_TIME_TO = '03:00';
  
  // 画像アップロード、Yahoo側取込完了 が全て終わるまでの予想時間（分） メンテナンスチェック用 メンテ中にFTPにアップロードすると欠け画像になるので、余裕をもって確保
  /** 関連処理完了までの予想時間(画像） */
  const EXPORT_CSV_FINISH_MIN_IMAGE = 180;


  // processRakuten()の引数で利用
  const PROCESS_RAKUTEN_IMAGE = 1; // MiscBundle:ProductImagesに対して処理を行う
  const PROCESS_RAKUTEN_IMAGE_AMAZON = 2; // MiscBundle:ProductImagesAmazonに対して処理を行う

  /* RAKUTEN_AMAZON_MAX_WIDTH * RAKUTEN_AMAZON_MAX_HEIGHTより画素数が大きいか、
     RAKUTEN_AMAZON_MAX_SIZE以上のファイルサイズの画像は
     RAKUTEN_AMAZON_MAX_WIDTH * RAKUTEN_AMAZON_MAX_HEIGHTに収まり、
     かつRAKUTEN_AMAZON_MAX_SIZEのサイズに収まるようにリサイズしてからアップロード */
  const RAKUTEN_AMAZON_MAX_WIDTH = 1600;
  const RAKUTEN_AMAZON_MAX_HEIGHT = 1200;
  const RAKUTEN_AMAZON_MAX_SIZE = 2000000;

  /* YAHOO_VARIATION_MAX_WIDTH * YAHOO_VARIATION_MAX_HEIGHTより画素数が大きいか、
   YAHOO_VARIATION_MAX_SIZE以上のファイルサイズの画像は
   YAHOO_VARIATION_MAX_WIDTH * YAHOO_VARIATION_MAX_HEIGHTに収まり、
   かつYAHOO_VARIATION_MAX_SIZEのサイズに収まるようにリサイズしてからアップロード */
  const YAHOO_VARIATION_MAX_WIDTH = 1250;
  const YAHOO_VARIATION_MAX_HEIGHT = 1250;
  const YAHOO_VARIATION_MAX_SIZE = 490000;

  /* EC-CUBE用画像サイズ */
  const CUBE_VARIATION_MAX_WIDTH = 800;
  const CUBE_VARIATION_MAX_HEIGHT = 800;

  /** 処理対象：楽天 */
  const TARGET_TYPE_RAKUTEN = 'rakuten';
  /** 処理対象：楽天Amazon */
  const TARGET_TYPE_RAKUTEN_AMAZON = 'rakuten_amazon';
  /** 処理対象：楽天Motto */
  const TARGET_TYPE_RAKUTEN_MOTTO = 'rakuten_motto';
  /** 処理対象：楽天LaForest */
  const TARGET_TYPE_RAKUTEN_LAFOREST = 'rakuten_laforest';
  /** 処理対象：楽天dolcissimo */
  const TARGET_TYPE_RAKUTEN_DOLCISSIMO = 'rakuten_dolcissimo';
  /** 処理対象：楽天gekipla */
  const TARGET_TYPE_RAKUTEN_GEKIPLA = 'rakuten_gekipla';
  /** 処理対象：EC-CUBE */
  const TARGET_TYPE_CUBE = 'cube';  
  /** 処理対象：PLUSNAO */
  const TARGET_TYPE_PLUSNAO = 'plusnao';
  /** 処理対象：kawa-e-mon */
  const TARGET_TYPE_KAWAEMON = 'kawaemon';
  /** 処理対象：おとりよせ.com */
  const TARGET_TYPE_OTORIYOSE = 'otoriyose';
  /** 処理対象：PLUSNAOバリエーション画像 */
  const TARGET_TYPE_PLUSNAO_VARIATION = 'plusnao_variation';
  /** 処理対象：kawa-e-monバリエーション画像 */
  const TARGET_TYPE_KAWAEMON_VARIATION = 'kawaemon_variation';
  /** 処理対象：おとりよせ.comバリエーション画像 */
  const TARGET_TYPE_OTORIYOSE_VARIATION = 'otoriyose_variation';
  /** 処理対象：PPM */
  const TARGET_TYPE_PPM = 'ppm';

  protected function configure()
  {
    $this
      ->setName('batch:product-image-upload-ftp')
      ->setDescription('商品画像アップロード処理（楽天R-Cabinet, Yahoo, PPM）')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target_type', null, InputOption::VALUE_OPTIONAL, '処理対象種別 rakuten|rakuten_amazon|rakuten_motto|rakuten_laforest|rakuten_dolcissimo|rakuten_gekipla|cube|plusnao|kawaemon|otoriyose|plusnao_variation|kawaemon_variation|otoriyose_variation|ppm')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $doctrine = $this->getDoctrine();

    $logExecTitle = '商品画像アップロード処理（楽天・Yahoo・PPM）';
    $logger->info($logExecTitle . 'を開始しました。');

    // 本番サーバでテスト環境起動をしてしまわない様にブロック
    $env = $this->getEnvironment();
    if ($env === 'test' &&  !file_exists('/this_is_dev_server')) {
      $message = 'このタスクは本番サーバで env=test で実行することはできません。';
      $logger->error($message);
      $output->writeln($message);
      return 1;
    }

    $logger->setExecTitle($logExecTitle);

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    // キューが遅延していた時のため、時刻チェック。2-3時台ならば終了（cron設定に合わせる事）
    $now = new \DateTime();
    if ($now->format('H') === '02' || $now->format('H') === '03') {
      $message = "日時画像チェック処理との重複を避けるため、$logExecTitle は現在は起動できません。現在時刻：" . $now->format('YmdHis');
      $logger->info($message);
      $output->writeln($message);
      return 1;
    }

    // 処理対象を取得
    $targetType = $input->getOption('target_type');
    if (!is_null($targetType)) {
      if ($targetType != self::TARGET_TYPE_RAKUTEN
          && $targetType != self::TARGET_TYPE_RAKUTEN_AMAZON
          && $targetType != self::TARGET_TYPE_RAKUTEN_MOTTO
          && $targetType != self::TARGET_TYPE_RAKUTEN_LAFOREST
          && $targetType != self::TARGET_TYPE_RAKUTEN_DOLCISSIMO
          && $targetType != self::TARGET_TYPE_RAKUTEN_GEKIPLA
          && $targetType != self::TARGET_TYPE_CUBE
          && $targetType != self::TARGET_TYPE_PLUSNAO
          && $targetType != self::TARGET_TYPE_KAWAEMON
          && $targetType != self::TARGET_TYPE_OTORIYOSE
          && $targetType != self::TARGET_TYPE_PLUSNAO_VARIATION
          && $targetType != self::TARGET_TYPE_KAWAEMON_VARIATION
          && $targetType != self::TARGET_TYPE_OTORIYOSE_VARIATION
          && $targetType != self::TARGET_TYPE_PPM) {
        $message = "target_typeが不正です。target_typeは次から選択してください。rakuten|rakuten_amazon|olplusnao|kawaemon|otoriyose|plusnao_variation|kawaemon_variation|otoriyose_variation|ppm : 入力値[$targetType]";
        $logger->error($message);
        $output->writeln($message);
        return 1;
      }
    }

    try {

      // 楽天アップロード
      if (is_null($targetType) || $targetType === self::TARGET_TYPE_RAKUTEN) {
        $logger->debug($logExecTitle . '：楽天　開始');
        $retryCount = 0;
        while (true) { // 無限ループ注意
          try {
            $this->results['rakuten'] = $this->processRakuten(self::PROCESS_RAKUTEN_IMAGE);
            break;

          } catch (\Exception $e) {
            // リトライ
            if ($retryCount <= self::RETRY_COUNT_RAKUTEN) {
              $logger->warning(sprintf('楽天画像FTPアップロード失敗 (retry: %d / %d, wait: %d) : %s', $retryCount, self::RETRY_COUNT_RAKUTEN, self::RETRY_WAIT, $e->getMessage()));
              $retryCount++;
              sleep(self::RETRY_WAIT);
              continue;
            }

            // リトライ回数超過。失敗とする。
            $logger->error($logExecTitle . "[楽天]" . $e->getMessage() . $e->getTraceAsString());
            $this->errors['rakuten'] = $e->getMessage();
            break;
          }
        }
      }

      // 楽天（Amzaonイメージ）アップロード
      if (is_null($targetType) || $targetType === self::TARGET_TYPE_RAKUTEN_AMAZON) {
        $logger->debug($logExecTitle . '：楽天Amazon　開始');
        $retryCount = 0;
        while (true) { // 無限ループ注意
          try {
            $this->results['rakuten_amazon'] = $this->processRakuten(self::PROCESS_RAKUTEN_IMAGE_AMAZON);
            break;

          } catch (\Exception $e) {
            // リトライ
            if ($retryCount <= self::RETRY_COUNT_RAKUTEN) {
              $logger->warning(sprintf('楽天画像FTPアップロード失敗 (retry: %d / %d, wait: %d) : %s', $retryCount, self::RETRY_COUNT_RAKUTEN, self::RETRY_WAIT, $e->getMessage()));
              $retryCount++;
              sleep(self::RETRY_WAIT);
              continue;
            }

            // リトライ回数超過。失敗とする
            $logger->error($logExecTitle . "[楽天Amazon]" . $e->getMessage() . $e->getTraceAsString());
            $this->errors['rakuten_amazon'] = $e->getMessage();
            break;
          }
        }
      }

      // 楽天モール アップロード
      if (
        is_null($targetType) 
        || $targetType === self::TARGET_TYPE_RAKUTEN_MOTTO
        || $targetType === self::TARGET_TYPE_RAKUTEN_LAFOREST
        || $targetType === self::TARGET_TYPE_RAKUTEN_DOLCISSIMO
        || $targetType === self::TARGET_TYPE_RAKUTEN_GEKIPLA
      ) {
        $targetTypeList = is_null($targetType)
          ? [self::TARGET_TYPE_RAKUTEN_MOTTO, self::TARGET_TYPE_RAKUTEN_LAFOREST, self::TARGET_TYPE_RAKUTEN_DOLCISSIMO, self::TARGET_TYPE_RAKUTEN_GEKIPLA]
          : [$targetType];
        foreach ($targetTypeList as $target) {
          $logger->debug($logExecTitle . '：' . $target . '開始');
          $retryCount = 0;
          while (true) { // 無限ループ注意
            try {
              $this->results[$target] = $this->processRakutenShop($target);
              break;
  
            } catch (\Exception $e) {
              // リトライ
              if ($retryCount <= self::RETRY_COUNT_RAKUTEN) {
                $logger->warning(sprintf('楽天画像FTPアップロード失敗 (retry: %d / %d, wait: %d) : %s', $retryCount, self::RETRY_COUNT_RAKUTEN, self::RETRY_WAIT, $e->getMessage()));
                $retryCount++;
                sleep(self::RETRY_WAIT);
                continue;
              }
  
              // リトライ回数超過。失敗とする。
              $logger->error($logExecTitle . "[$target]" . $e->getMessage() . $e->getTraceAsString());
              $this->errors[$target] = $e->getMessage();
              break;
            }
          }
        }
      }
      
      // EC-CUBE アップロード
      if (is_null($targetType) || $targetType === self::TARGET_TYPE_CUBE) {
        $logger->debug($logExecTitle . '：EC-CUBE　開始');
        try {
          $this->results['cube'] = $this->processCube();
        } catch (\Exception $e) {
          $logger->error($logExecTitle . "[EC-CUBE]" . $e->getMessage() . $e->getTraceAsString());
          $this->errors['cube'] = $e->getMessage();
        }
      }
      
      // YahooはFTPメンテ中はアップロードできない　Yahoo側の取込完了まで余裕をもって、3時間以内にメンテがあるときはスキップ
      /** @var \MiscBundle\Entity\Repository\TbMaintenanceScheduleRepository $repo */
      $mainteRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMaintenanceSchedule');
      $isYahooMainte = $mainteRepo->isMaintenance(array(TbMaintenanceSchedule::MAINTENANCE_TYPE_YAHOO_SCHEDULED), self::EXPORT_CSV_FINISH_MIN_IMAGE);

      // YahooはCSV処理と重複すると、「Y-002-0020 ファイルアップロード中または反映中のため、商品との紐づけが行えませんでした。」が出るため
      // 指定時間帯はスキップ（※2022/8/23 Yahoo側で障害報告あり。直っている可能性がある。が、特に詰まらなければスキップでも支障はないのでいったんこのまま）
      preg_match('/(\d{2}):(\d{2})/', self::YAHOO_SKIP_TIME_FROM, $skipFromStringArray);
      preg_match('/(\d{2}):(\d{2})/', self::YAHOO_SKIP_TIME_TO, $skipToStringArray);
      $skipFromTime = clone $now;
      $skipToTime = clone $now;
      $skipFromTime->setTime($skipFromStringArray[1], $skipFromStringArray[2]);
      $skipToTime->setTime($skipToStringArray[1], $skipToStringArray[2]);
      
      if (!$isYahooMainte && ($now < $skipFromTime || $now > $skipToTime)) { // メンテ中ではなく、スキップ開始時刻より前、または終了時刻より後ならば実行
      
        // Yahoo plusnaoアップロード
        if (is_null($targetType) || $targetType === self::TARGET_TYPE_PLUSNAO) {
          $logger->debug($logExecTitle . '：Plusnao　開始');
          $key = 'yahoo_' . ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO;
          try {
            $this->results[$key] = $this->processYahoo(ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO);
          } catch (\Exception $e) {
            $logger->error($logExecTitle . "[Yahoo　plusnao]" . $e->getMessage() . $e->getTraceAsString());
            $this->errors[$key] = $e->getMessage();
          }
        }
  
        // Yahoo kawaemonアップロード
        if (is_null($targetType) || $targetType === self::TARGET_TYPE_KAWAEMON) {
          $logger->debug($logExecTitle . '：kawa-e-mon　開始');
          $key = 'yahoo_' . ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON;
          try {
            $this->results[$key] = $this->processYahoo(ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON);
          } catch (\Exception $e) {
            $logger->error($logExecTitle . "[Yahoo　kawa-e-mon]" . $e->getMessage() . $e->getTraceAsString());
            $this->errors[$key] = $e->getMessage();
          }
        }

        // Yahoo おとりよせアップロード
        if (is_null($targetType) || $targetType === self::TARGET_TYPE_OTORIYOSE) {
          $logger->debug($logExecTitle . '：otoriyose　開始');
          $key = 'yahoo_' . ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE;
          try {
            $this->results[$key] = $this->processYahoo(ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE);
          } catch (\Exception $e) {
            $logger->error($logExecTitle . "[Yahoo　おとりよせ]" . $e->getMessage() . $e->getTraceAsString());
            $this->errors[$key] = $e->getMessage();
          }
        }
        
        // Yahoo plusnaoバリエーション画像アップロード
        if (is_null($targetType) || $targetType === self::TARGET_TYPE_PLUSNAO_VARIATION) {
          $logger->debug($logExecTitle . '：Plusnao variation画像　開始');
          $key = 'yahoo_' . ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO . '_variation';
          try {
            $this->results[$key] = $this->processYahooVariation(ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO);
          } catch (\Exception $e) {
            $logger->error($logExecTitle . "[Yahoo　plusnao バリエーション]" . $e->getMessage() . $e->getTraceAsString());
            $this->errors[$key] = $e->getMessage();
          }
        }

        // Yahoo kawa-e-monバリエーション画像アップロード
        if (is_null($targetType) || $targetType === self::TARGET_TYPE_KAWAEMON_VARIATION) {
          $logger->debug($logExecTitle . '：kawa-e-mon variation画像　開始');
          $key = 'yahoo_' . ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON . '_variation';
          try {
            $this->results[$key] = $this->processYahooVariation(ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON);
          } catch (\Exception $e) {
            $logger->error($logExecTitle . "[Yahoo　kawa-e-mon バリエーション]" . $e->getMessage() . $e->getTraceAsString());
            $this->errors[$key] = $e->getMessage();
          }
        }

        // Yahoo おとりよせ.comバリエーション画像アップロード
        if (is_null($targetType) || $targetType === self::TARGET_TYPE_OTORIYOSE_VARIATION) {
          $logger->debug($logExecTitle . '：otoriyose variation画像　開始');
          $key = 'yahoo_' . ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE . '_variation';
          try {
            $this->results[$key] = $this->processYahooVariation(ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE);
          } catch (\Exception $e) {
            $logger->error($logExecTitle . "[Yahooおとりよせバリエーション]" . $e->getMessage() . $e->getTraceAsString());
            $this->errors[$key] = $e->getMessage();
          }
        }
      } else {
        $logger->debug("YahooCSV出力処理時刻・メンテナンス時刻のため、Yahooの各サイトの画像アップロード処理はスキップします");
      }

      // PPMアップロード
      if (is_null($targetType) || $targetType === self::TARGET_TYPE_PPM) {
        $logger->debug($logExecTitle . '：PPM　開始');
        try {
          $this->results['ppm'] = $this->processPPM();
        } catch (\Exception $e) {
          $logger->error($logExecTitle . "[PPM]" . $e->getMessage() . $e->getTraceAsString());
          $this->errors['ppm'] = $e->getMessage();
        }
      }

      // エラーがあれば通知する
      if (count($this->errors)) {
        $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '定期アップロード エラー')->setInformation(['results' => $this->results, 'errors' => $this->errors])->setLogLevel(TbLog::ERROR));
      }

      // 処理完了
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '定期アップロード済')->setInformation(['results' => $this->results, 'errors' => $this->errors])->setLogLevel(TbLog::DEBUG));
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
   * 新規・更新商品画像 取得処理
   * @param \DateTime $lastUpdated
   * @param array $codeList p1～p9 指定配列 （Yahoo用の絞込など）
   * @return \Doctrine\ORM\Internal\Hydration\IterableResult
   */
  private function findNewImages($lastUpdated, $codeList = [])
  {
    // この処理ではサーバ負荷を避けるため、DBの最終更新日時を元にアップロードする。
    // 商品画像一覧テーブル 再作成処理(createProductImagesTable) はここでは行わない。
    // ※FTPからの直接アップロードなどがなければ不要であるはず。

    // 新しい画像を取得
    /** @var \MiscBundle\Entity\Repository\ProductImagesRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:ProductImages');
    $q = $repo->createQueryBuilder('p');

    if ($lastUpdated) {
      // 同じ秒も含める（一部重複は許容する）
      $q->andWhere('p.updated >= :lastUpdated')->setParameter('lastUpdated', $lastUpdated);
    }

    // 取得画像コード指定
    if ($codeList) {
      $q->andWhere($q->expr()->in('p.code', $codeList));
    }

    $logger = $this->getLogger();
    $q->addOrderBy('p.updated', 'asc'); // 最終更新日時でソート ※重要。途中で終了した場合にこの次から始めるため。

    $newFilesIterator = $q->getQuery()->iterate();

    return $newFilesIterator;
  }

  /**
   * 新規・更新商品画像 取得処理（Amazonイメージ）
   * @param \DateTime $lastUpdated
   * @param array $codeList p1～p9 指定配列 （Yahoo用の絞込など）
   * @return \Doctrine\ORM\Internal\Hydration\IterableResult
   */
  private function findNewImagesAmazon($lastUpdated, $codeList = [])
  {
    // この処理ではサーバ負荷を避けるため、DBの最終更新日時を元にアップロードする。
    // 商品画像一覧テーブル 再作成処理(createProductImagesTable) はここでは行わない。
    // ※FTPからの直接アップロードなどがなければ不要であるはず。

    // 新しい画像を取得
    /** @var \MiscBundle\Entity\Repository\ProductImagesAmazonRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:ProductImagesAmazon');
    $q = $repo->createQueryBuilder('p');

    if ($lastUpdated) {
      // 同じ秒も含める（一部重複は許容する）
      $q->andWhere('p.updated >= :lastUpdated')->setParameter('lastUpdated', $lastUpdated);
    }

    // 取得画像コード指定
    if ($codeList) {
      $q->andWhere($q->expr()->in('p.code', $codeList));
    }

    $q->addOrderBy('p.updated', 'asc'); // 最終更新日時でソート ※重要。途中で終了した場合にこの次から始めるため。

    $newFilesIterator = $q->getQuery()->iterate();

    return $newFilesIterator;
  }

  /**
   * 新規・更新商品画像 取得処理（楽天イメージ）
   *
   * 即納・一部即納のもののみがアップロード対象
   * 代表商品ごとにアップロード時刻を保持するため、代表商品コードでソート
   * @param string $targetShop
   * @return array
   */
  private function findNewImagesRakutenShop($targetShop)
  {
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    $informationTable = $commonUtil->getMallTableName($targetShop);

    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      SELECT img.daihyo_syohin_code, img.`directory`, img.filename, img.updated
      FROM product_images img
      INNER JOIN tb_mainproducts_cal cal ON img.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN {$informationTable} ri ON img.daihyo_syohin_code = ri.daihyo_syohin_code
      WHERE cal.deliverycode IN (0, 1)
        AND (ri.last_image_upload_datetime IS NULL OR img.updated >= ri.last_image_upload_datetime)
        AND ri.warehouse_stored_flg = :warehouse_stored_flg
      ORDER BY img.daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouse_stored_flg', 0, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * 新規・更新商品画像 取得処理（PPMイメージ）
   * @param \DateTime $lastUpdated
   * @param array $stmt
   */
  private function findNewImagesPPM($lastUpdated)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      SELECT img.daihyo_syohin_code, img.`directory`, img.filename, img.updated
      FROM product_images img
      INNER JOIN tb_ppm_information ppm ON img.daihyo_syohin_code = ppm.daihyo_syohin_code
      WHERE ppm.is_sold = -1 AND (ppm.is_uploaded_images = 0 OR img.updated >= :lastUpdated)
      ORDER BY img.updated ASC /* 途中で終了した場合にこの次から始めるため */
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':lastUpdated', $lastUpdated->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();

    return $stmt;
  }

  /**
   * PPMにアップロードした画像のフラグを更新する
   * @param String $maxDateTime
   */
  private function updateUploadImagesFlg($maxDateTime)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      UPDATE tb_ppm_information ppm
      INNER JOIN product_images img ON ppm.daihyo_syohin_code = img.daihyo_syohin_code
      SET ppm.is_uploaded_images = -1
      WHERE ppm.is_sold = -1 AND img.updated < :maxDateTime
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':maxDateTime', $maxDateTime, \PDO::PARAM_STR);
    $stmt->execute();
  }
  
  /**
   * 商品画像をアップロードした代表商品は、Informationテーブルの日時を更新する。対象カラム名はlast_image_upload_datetime
   * @param string $targetMallCode DbCommonUtil で定義された MallCode。
   * @param array $daihyoSyohinCodeList アップロードした代表商品リスト。
   * @param $updateDatetime \DateTime DBに設定する更新日時。
   */
  private function updateLastImageUploadDatetime($targetMallCode, $daihyoSyohinCodeList, $updateDatetime) {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $targetTable = $this->getDbCommonUtil()->getMallTableName($targetMallCode);
    $count = count($daihyoSyohinCodeList);
    for ($i = 0; $i < $count; $i++) {
      $daihyoSyohinCodeList[$i] = $dbMain->quote($daihyoSyohinCodeList[$i], \PDO::PARAM_STR);
    }
    $daihyoSyohinCodeListStr = implode(',', $daihyoSyohinCodeList);

    $sql = <<<EOD
      UPDATE {$targetTable} i
      SET i.last_image_upload_datetime = :updateDatetime
      WHERE i.daihyo_syohin_code IN ( {$daihyoSyohinCodeListStr} )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':updateDatetime', $updateDatetime->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $result = $stmt->execute();
if ($targetMallCode === DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO) {
  $logger->info("★★★sql: $sql");
  $logger->info('★★★result:' . $result ? 'success' : 'ng');
}
  }

  /**
   * Yahooにバリエーション画像をアップロードした代表商品は、Informationテーブルの日時を更新する
   * @param string $target plusnao|kawaemon
   * @param array $daihyoSyohinCodeList アップロードした代表商品リスト。IN句にそのまま展開するため、クォーテーション済みでリストとすること。
   * @param $updateDatetime DBに設定する更新日時
   */
  private function updateYahooVariationUploadDate($target, $daihyoSyohinCodeList, $updateDatetime) {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $targetTable = $this->getDbCommonUtil()->getYahooTargetTableName($target);
    $daihyoSyohinCodeListStr = implode(',', $daihyoSyohinCodeList);

    $sql = <<<EOD
      UPDATE {$targetTable} i
      SET i.variation_image_upload_date = :updateDatetime
      WHERE i.daihyo_syohin_code IN ( {$daihyoSyohinCodeListStr})
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':updateDatetime', $updateDatetime->format('Y-m-d h:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * 楽天 アップロード
   */
  private function processRakuten($imageType)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    switch ($imageType) {
      case self::PROCESS_RAKUTEN_IMAGE:
        $updateRecordKey = DbCommonUtil::UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_RAKUTEN;
        $imageDir = $container->getParameter('product_image_dir');
        break;
      case self::PROCESS_RAKUTEN_IMAGE_AMAZON:
        $updateRecordKey = DbCommonUtil::UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_RAKUTEN_AMAZON;
        $imageDir = $container->getParameter('product_image_amazon_dir');
        break;
      default:
        throw new \RuntimeException('unknown imageType');
    }

    // 最終画像アップロード日時 取得
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();
    $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime($updateRecordKey);

    // FTPアップロード処理
    $logTitle = '商品画像FTPアップロード(楽天)';
    $logger->info($logTitle . ': ファイルアップロード 開始');

    $results = [
      'count' => 0
      , 'size' => 0
      , 'min_timestamp' => ( $lastUpdated ? $lastUpdated->format('Y-m-d H:i:s') : null )
      , 'max_timestamp' => null
      , 'file_missing' => []
    ];

    $ftp = $this->openRakutenFtp('ftp_rakuten', 'RAKUTEN_GOLD_FTP_PASSWORD', '楽天');

    // 画像一覧取得
    switch ($imageType) {
      case self::PROCESS_RAKUTEN_IMAGE:
        $newFilesIterator = $this->findNewImages($lastUpdated);
        break;
      case self::PROCESS_RAKUTEN_IMAGE_AMAZON:
        $newFilesIterator = $this->findNewImagesAmazon($lastUpdated);
        break;
      default:
        throw new \RuntimeException('unknown imageType');
    }

    $uploadSize = 0;
    $uploadCount = 0;
    $fs = new FileSystem();
    $maxDateTime = null;

    /** @var ProductImages $image */
    while($image = $newFilesIterator->next()) {
      // なぜか配列
      if ($image) {
        $image = $image[0];
      } else {
        continue;
      }

      $maxDateTime = $image->getUpdated();
      // 処理共通化のため詰め替え
      $img = [
        'directory' => $image->getDirectory(),
        'filename' => $image->getFilename(),
        'daihyo_syohin_code' => $image->getDaihyoSyohinCode()
      ];
      $currentSize = $this->uploadRakutenImage($img, $ftp, $fs, $imageDir, $results['file_missing']);
      $uploadSize += $currentSize;
      $uploadCount++;

      if ($uploadSize >= self::MAX_UPLOAD_SIZE) {
        $logger->info($logTitle . ': アップロード制限に達しました。今回の処理を終了します。');
        break;
      }
    }

    $results['count'] = $uploadCount;
    $results['size'] = $uploadSize;

    // 最終アップロード日時　更新
    // ※ アップロードした内、最大（＝最後）の最終更新日で更新
    $newLastUpdated = $maxDateTime ? $maxDateTime : $lastUpdated;
    // もしファイルの最大最終更新日と前回の最大とが同じ場合、その時間のファイルをアップロードし終えたと考え、1秒進める。
    // （2GB分も同じファイルは存在しないとする。 ）
    if ($lastUpdated && $lastUpdated->format('Y-m-d H:i:s') === $newLastUpdated->format('Y-m-d H:i:s')) {
      $newLastUpdated->modify('+1 second');
    }
    $commonUtil->updateUpdateRecordTable($updateRecordKey, $newLastUpdated);

    $results['max_timestamp'] = $newLastUpdated->format('Y-m-d H:i:s');

    return $results;
  }

  /**
   * 楽天他店舗（plusnao以外） アップロード
   * 
   * @param string $targetShop
   */
  private function processRakutenShop($targetShop)
  {
    $logger = $this->getLogger();

    // FTPアップロード処理
    $logTitle = '商品画像FTPアップロード(' . $targetShop . ')';
    $logger->info($logTitle . ': ファイルアップロード 開始');

    $results = [
      'count' => 0
      , 'size' => 0
      , 'file_missing' => []
    ];

    $settingKey = '';
    $shopName = '';
    if ($targetShop === self::TARGET_TYPE_RAKUTEN_MOTTO) {
      $settingKey = 'MOTTO_GOLD_FTP_PASSWORD';
      $shopName = '楽天motto';
    } elseif ($targetShop === self::TARGET_TYPE_RAKUTEN_LAFOREST) {
      $settingKey = 'LAFOREST_GOLD_FTP_PASSWORD';
      $shopName = '楽天laforest';
    } elseif ($targetShop === self::TARGET_TYPE_RAKUTEN_DOLCISSIMO) {
      $settingKey = 'DOLCISSIMO_GOLD_FTP_PASSWORD';
      $shopName = '楽天dolcissimo';
    } elseif ($targetShop === self::TARGET_TYPE_RAKUTEN_GEKIPLA) {
      $settingKey = 'GEKIPLA_GOLD_FTP_PASSWORD';
      $shopName = '楽天激安プラネット';
    } 

    $ftp = $this->openRakutenFtp('ftp_' . $targetShop, $settingKey, $shopName);
    $newFileList = $this->findNewImagesRakutenShop($targetShop);

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

    $fs = new FileSystem();
    $uploadDatetime = new \DateTime(); // DB保存用に現在時刻を確保　念のため2分ずらす この2分間で更新された画像は次回もアップロードされる
    $uploadDatetime->modify('-2 mins');
    $imageDir = $this->getContainer()->getParameter('product_image_dir');

    for ($i = 0; $i < $limitLoopCount; $i++) {
      for ($pos; $pos < count($newFileList); $pos++) {
        $image = $newFileList[$pos];
        $daihyoSyohinCode = $image['daihyo_syohin_code'];
        // サイズ上限越え、かつ代表商品の区切りであれば、現在のファイルはアップロードせずDB更新
        if ($uploadSize >= $limitUploadSize && $daihyoSyohinCode != $previousCode) {
          $this->updateRakutenShopUploadDate($targetShop, array_unique($daihyoSyohinCodeList), $uploadDatetime);
          $daihyoSyohinCodeList = []; // DB更新が終わったら空に戻す
          $uploadSize = 0;
          break; // breakすると $pos は進まない様子　なので次のループも $pos は同じところから始まる
        }
        $previousCode = $daihyoSyohinCode;
        $daihyoSyohinCodeList[] = $daihyoSyohinCode; // 後でarray_uniqueするのでひとまず全部入れる

        // ファイルアップロード
        $currentSize = $this->uploadRakutenImage($image, $ftp, $fs, $imageDir, $results['file_missing']);
        $uploadSize += $currentSize;
        $totalUploadSize += $currentSize;
        $uploadCount++;
      }
      if (count($daihyoSyohinCodeList) > 0) {
        $this->updateRakutenShopUploadDate($targetShop, array_unique($daihyoSyohinCodeList), $uploadDatetime);
      }
    }

    $results['count'] = $uploadCount;
    $results['size'] = $totalUploadSize;

    return $results;
  }

  /**
   * 楽天仕様でFTPに接続し、レスポンスを返却する（楽天plusnao、楽天motto、楽天laforest、楽天dolcissimo、楽天激安プラネット対応）
   * @param string $targetConfig 設定名
   * @param string $settingKey TbSettingに設定しているパスワード取得用のキー
   * @param string $shopName サイト和名（エラー時のログ用）
   */
  private function openRakutenFtp($targetConfig, $settingKey, $shopName)
  {
    $container = $this->getContainer();
    $ftpConfig = $container->getParameter($targetConfig);
    $config = $ftpConfig['image'];

    $commonUtil = $this->getDbCommonUtil();

    // 開発環境はパスワード決め打ち
    $env = $this->getApplication()->getKernel()->getEnvironment();
    $password = $commonUtil->getSettingValue($settingKey, $env);

    $ftp = new SFTP($config['host']);

    try {
      $ftp->login($config['user'], $password);
    } catch (\Exception $e) {
      $message = "$shopName の画像アップロード処理中、FTPにログインできませんでした。パスワードが変更されている場合は、Accessの「各種設定」から「 $settingKey 」を正しく更新してください。";
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->chdir($config['path']);
    return $ftp;
  }

  /**
   * 楽天に指定された画像を1ファイルアップロードする。
   * その際、必要に応じて整形する。
   * 整形があるため、アップロードしたファイルサイズを返却する。
   */
  private function uploadRakutenImage($image, $ftp, $fs, $imageDir, &$missingList)
  {
    $dir = $image['directory'];
    $filename = $image['filename'];

    // ディレクトリ作成
    $dirs = $ftp->nlist('.');
    if (!is_array($dirs)) {
      throw new \RuntimeException('ディレクトリ一覧の取得に失敗しました。(' . print_r($dirs, true) . ')');
    }
    if (!in_array($dir, $dirs)) {
      $ftp->mkdir($dir);
    }

    $remotePath = sprintf('%s/%s', $dir, $filename);
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
      if ($width > ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_WIDTH || $height > ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_HEIGHT
        || $fileSize >= ProductImageUploadFtpCommand::RAKUTEN_AMAZON_MAX_SIZE) {
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

        $content = stream_get_contents($fp);
        $ftp->put($remotePath, $content, SFTP::SOURCE_STRING);
        fclose($fp);
      } else {
        $fp = fopen($filePath, 'r');
        $content = stream_get_contents($fp);
        rewind($fp);
        $ftp->put($remotePath, $content, SFTP::SOURCE_STRING);
        fclose($fp);
      }
      $im->clear();
      return $fileSize;

      // ファイルが存在しない
    } else {
      $missingList[] = sprintf('%s : %s', $image['daihyo_syohin_code'], $remotePath);
    }
  }

  /**
   * 楽天motto/laforest/dolcissimo/gekiplaに商品画像をアップロードした代表商品は、Informationテーブルの日時を更新する
   * @param string $targetShop 更新対象店舗
   * @param array $daihyoSyohinCodeList アップロードした代表商品リスト。
   * @param \DateTime $updateDatetime DBに設定する更新日時
   */
  private function updateRakutenShopUploadDate($targetShop, $daihyoSyohinCodeList, $updateDatetime)
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $quoteList = array_map(function ($daihyoSyohinCode) {
      return "'$daihyoSyohinCode'";
    }, $daihyoSyohinCodeList);
    $daihyoSyohinCodeListStr = implode(',', $quoteList);

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    // tb_rakuten_○○_information
    $updateTable = $commonUtil->getMallTableName($targetShop);

    $sql = <<<EOD
      UPDATE {$updateTable} i
      SET i.last_image_upload_datetime = :updateDatetime
      WHERE i.daihyo_syohin_code IN ({$daihyoSyohinCodeListStr})
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':updateDatetime', $updateDatetime->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * EC-CUBE アップロード
   * 
   * @return array
   */
  private function processCube()
  {
    $logger = $this->getLogger();

    // FTPアップロード処理
    $logTitle = '商品画像FTPアップロード(EC-CUBE)';
    $logger->info($logTitle . ': ファイルアップロード 開始');

    $results = [
      'count' => 0
      , 'size' => 0
      , 'file_missing' => []
    ];

    $targetShop = DbCommonUtil::MALL_CODE_CUBE;

    $ftp = $this->openCubeFtp();
    $newFileList = $this->findNewImagesCube();

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

    $fs = new FileSystem();
    $uploadDatetime = new \DateTime(); // DB保存用に現在時刻を確保　念のため2分ずらす この2分間で更新された画像は次回もアップロードされる
    $uploadDatetime->modify('-2 mins');
    $imageDir = $this->getContainer()->getParameter('product_image_dir');

    for ($i = 0; $i < $limitLoopCount; $i++) {
      for ($pos; $pos < count($newFileList); $pos++) {
        $image = $newFileList[$pos];
        $daihyoSyohinCode = $image['daihyo_syohin_code'];
        // サイズ上限越え、かつ代表商品の区切りであれば、現在のファイルはアップロードせずDB更新
        if ($uploadSize >= $limitUploadSize && $daihyoSyohinCode != $previousCode) {
          $this->updateLastImageUploadDatetime($targetShop, array_unique($daihyoSyohinCodeList), $uploadDatetime);
          $daihyoSyohinCodeList = []; // DB更新が終わったら空に戻す
          $uploadSize = 0;
          break; // breakすると $pos は進まない様子　なので次のループも $pos は同じところから始まる
        }
        $previousCode = $daihyoSyohinCode;
        $daihyoSyohinCodeList[] = $daihyoSyohinCode; // 後でarray_uniqueするのでひとまず全部入れる

        // ファイルアップロード
        $currentSize = $this->uploadCubeImage($image, $ftp, $fs, $imageDir, $results['file_missing']);
        $uploadSize += $currentSize;
        $totalUploadSize += $currentSize;
        $uploadCount++;
      }
      if (count($daihyoSyohinCodeList) > 0) {
        $this->updateLastImageUploadDatetime($targetShop, array_unique($daihyoSyohinCodeList), $uploadDatetime);
      }
    }
    $ftp->close();

    $results['count'] = $uploadCount;
    $results['size'] = $totalUploadSize;

    return $results;
  }

  /**
   * EC-CUBEのFTPに接続し、レスポンスを返却する
   * 
   * @return \Ijanki\Bundle\FtpBundle\Ftp $ftp FTPコネクション
   */
  private function openCubeFtp() {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    /* FTPアカウント取得 */
    $ftpConfig = $container->getParameter('ftp_cube');
    $config = $ftpConfig['image_upload'];

    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    if ($this->getEnvironment() == 'prod') {
      // EC-CUBE は FTPS接続。connectで失敗しても例外が飛ばないので login の成否で判定
      $ftp->ssl_connect($config['host']);
    } else {
      $ftp->connect($config['host']);
    }

    $ret = $ftp->login($config['user'], $config['password']);
    if (!$ret) {
      throw new \RuntimeException('[cube] ftp login failed.');
    }

    $ftp->pasv(true);
    $ftp->chdir($config['path']);
    return $ftp;
  }

  /**
   * EC-CUBE 新規・更新商品画像 取得処理
   *
   * 即納・一部即納のもののみがアップロード対象
   * 代表商品ごとにアップロード時刻を保持するため、代表商品コードでソート
   * 
   * @return array
   */
  private function findNewImagesCube()
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
      INNER JOIN tb_cube_information ci
        ON img.daihyo_syohin_code = ci.daihyo_syohin_code
      WHERE
        cal.deliverycode IN (0, 1)
        AND (
          ci.last_image_upload_datetime IS NULL
          OR img.updated >= ci.last_image_upload_datetime
        )
      ORDER BY img.daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }  

  /**
   * 指定された画像を1ファイルアップロードする。
   * その際、必要に応じて整形する。
   * 整形があるため、アップロードしたファイルサイズを返却する。
   * @param array $image 画像ファイルの配列 
   * @param mixed $ftp FTPコネクション
   * @param mixed $fs ファイルシステムオブジェクト
   * @param string $imageDir 画像格納TOPフォルダ
   * @param array &$missingList 存在しないファイルの配列
   * @return integer $fileSize ファイルサイズ
   */
  private function uploadCubeImage($image, $ftp, $fs, $imageDir, &$missingList) {
    $dir = $image['directory'];
    $filename = $image['filename'];

    // EC-CUBEはディレクトリ分割しない。リモートファイル名とローカルファイル名は同じになる。
    $remotePath = $filename;

    $filePath = sprintf('%s/%s/%s', $imageDir, $dir, $filename);
    if ($fs->exists($filePath)) {
      $im = new \Imagick($filePath);
      $width = $im->getImageWidth();
      $height = $im->getImageHeight();
      $fileSize = filesize($filePath);

      if($width > ProductImageUploadFtpCommand::CUBE_VARIATION_MAX_WIDTH
          || $height > ProductImageUploadFtpCommand::CUBE_VARIATION_MAX_HEIGHT) {
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
   * Yahoo アップロード（代表商品ごとにアップロード日時管理版）
   * 代表商品ごとにアップロード日時を管理している店舗で使用する。
   * @param String $target モールコード
   * @return array
   */
  private function processYahoo($target)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    
    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getFileUtil();
    
    // FTPアップロード処理
    $logTitle = sprintf('商品画像FTPアップロード(Yahoo:%s)', $target);
    $logger->info($logTitle . ': ファイルアップロード 開始');
    
    $mallCode = null;
    $targetImages = null;
    switch ($target) {
      case ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO:
        $mallCode = DbCommonUtil::MALL_CODE_PLUSNAO_YAHOO;
        break;
      case ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON:
        $mallCode = DbCommonUtil::MALL_CODE_KAWA_YAHOO;
        break;
      case ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE:
        $mallCode = DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO;
        break;
      default:
        throw new \RuntimeException('unknown yahoo target');
    }

    $results = [
      'count' => 0
      , 'size' => 0
      , 'file_missing' => []
      , 'archives' => []
    ];
    
    // 対象がなければ終了
    $targetImages = $container->get('doctrine')->getRepository('MiscBundle:ProductImages')->findYahooNewImages($mallCode);
    if (! $targetImages) {
      return $results;
    }    
    $imageDir = $container->getParameter('product_image_dir');
    
    // 一時ディレクトリ作成
    $fs = new FileSystem();
    
    $now = new \DateTime();
    $tmpDir = sprintf('%s/upload_image_yahoo/%s_%s', $fileUtil->getDataDir(), $target, $now->format('YmdHis'));
    $fs->mkdir($tmpDir);
    
    $daihyoSyohinCodeList = []; // アップロード対象の代表商品コード
    $imageFiles = []; // アップロード対象の画像リスト
    $uploadCount = 0;
    $uploadSize = 0;

    $currentDaihyoSyohinCode = ''; // 現在処理中の代表商品
    $imageFilesByCode = []; // 商品毎のアップロード対象の画像リスト（一時保存）
    $fileMissingByCode = []; // 商品毎の画像パス不存在リスト（一時保存）
    $uploadCountByCode = 0; // 商品毎のアップロード対象数（一時保存）
    $uploadSizeByCode = 0; // 商品毎のアップロードサイズ合計（一時保存）
    $hasAllImagesByCode = true; // 全ての画像のパスが存在するか（一時保存）

    /** @var ProductImages $image */
    foreach ($targetImages as $image) {
      // 代表商品が変わる度にアップロードチェック。
      if ($image->getDaihyoSyohinCode() != $currentDaihyoSyohinCode) {
        // ※全て商品画像が揃っている場合に限り、アップロード対象に追加。
        if ($hasAllImagesByCode) {
          $daihyoSyohinCodeList[] = $currentDaihyoSyohinCode;
          $imageFiles = array_merge($imageFiles, $imageFilesByCode);
          $uploadCount += $uploadCountByCode;
          $uploadSize += $uploadSizeByCode;
        } else {
          $results['file_missing'] = array_merge($results['file_missing'], $fileMissingByCode);
if ($mallCode === DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO) {
  $logger->info("★★★画像アップロード対象外: $currentDaihyoSyohinCode");
}
        }

        // 初期化
        $imageFilesByCode = [];
        $fileMissingByCode = [];
        $uploadCountByCode = 0;
        $uploadSizeByCode = 0;
        $hasAllImagesByCode = true;
        $currentDaihyoSyohinCode = $image->getDaihyoSyohinCode();
      }

      $filePath = sprintf('%s/%s', $imageDir, $image->getFileDirPath());

      if ($fs->exists($filePath)) {
        $file = [
          'path' => $filePath,
          'filename' => basename($filePath),
        ];
        
        // Yahoo形式になっていなければここでリネームしてアップロード
        // ※従来の実装の流れを引き継いでの仕様
        //   既存画像差し替え時に前のファイル名を引き継いでいるため、
        //   アップロード時のこの変換処理も必要。
        //   → 理想は、すべての画像ファイル名の付け直し＆再アップロード
        if (!$image->isValidYahooImageName()) {
          $file['filename'] = $image->getYahooImageName();
        }
        $imageFilesByCode[] = $file;
        $uploadCountByCode++;
        $uploadSizeByCode += filesize($filePath);
      } else {
        $fileMissingByCode[] = sprintf('%s : %s (local)', $image->getDaihyoSyohinCode(), $filePath);
        $hasAllImagesByCode = false;
      }

      // ファイルサイズチェック。一つの代表商品コードの途中で終了させないようにする。
      if (self::MAX_UPLOAD_SIZE_YAHOO_PRODUCT <= $uploadSize + $uploadSizeByCode) {
        $logger->info($logTitle . ': アップロード制限に達しました。今回の処理を終了します。');
        $mbSize = ceil(($uploadSize + $uploadSizeByCode) / 1000000 * 10) / 10; // MB換算（小数第一位まで）
        $logger->info("{$logTitle}: 「{$currentDaihyoSyohinCode}」分を含めると、約{$mbSize}MBになるので見送り");

        $imageFilesByCode = [];
        $fileMissingByCode = [];
        $uploadCountByCode = 0;
        $uploadSizeByCode = 0;
        break;
      }
    }

    // 上限に達する前に、ループを抜けた場合の処理
    if ($uploadCountByCode > 0 && $hasAllImagesByCode) {
      $daihyoSyohinCodeList[] = $currentDaihyoSyohinCode;
      $imageFiles = array_merge($imageFiles, $imageFilesByCode);
      $uploadCount += $uploadCountByCode;
      $uploadSize += $uploadSizeByCode;
    } else {
      $results['file_missing'] = array_merge($results['file_missing'], $fileMissingByCode);
if ($mallCode === DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO) {
  $logger->info("★★★画像アップロード対象外: $currentDaihyoSyohinCode");
}
    }

    // zip ファイル作成とFTP upload
    // 上限 25MB
    if ($imageFiles) {
      $zipFiles = $this->archiveImageFiles($imageFiles, $tmpDir, 'img', self::MAX_UPLOAD_SIZE_YAHOO_PRODUCT);
      
      if ($zipFiles) {
        $this->uploadZipToYahoo($target, $zipFiles);
      }
    } else {
      // 対象がなければ終了
      return $results;
    }
    
    // いずれにせよ一時ディレクトリは削除
    $fs->remove($tmpDir);
    
    $results['count'] = $uploadCount;
    $results['size'] = $uploadSize;

if ($mallCode === DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO) {
  $dateTimeStr = $now->format('Y-m-d H:i:s');
  $codes = print_r($daihyoSyohinCodeList, true);
  $logger->info("★★★画像アップロード日時更新[$dateTimeStr]: {$uploadCount}件: $codes");
}

    // 最終アップロード日時　更新
    $this->updateLastImageUploadDatetime($mallCode, $daihyoSyohinCodeList, $now);
    return $results;
  }

  /**
   * Yahoo バリエーション画像アップロード
   * @param String $target モールコード(plusnao|kawaemon|otoriyose)
   * @return array
   */
  private function processYahooVariation($target)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $now = new \DateTime();

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    $dbMain = $this->getDoctrine()->getConnection('main');

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getFileUtil();

    $logTitle = sprintf('商品バリエーション画像FTPアップロード(Yahoo:%s)', $target);
    $logger->info($logTitle . ': ファイルアップロード 開始');
    $uploadSize = 0;
    $uploadCount = 0;

    $results = [
        'count' => 0
        , 'size' => 0
        , 'file_missing' => []
        , 'archives' => []
    ];

    // 対象画像一覧取得
    $targetImages = $container->get('doctrine')->getRepository('MiscBundle:ProductImagesVariation')->findYahooUploadImage($target);

    // 対象がなければ終了
    if (! $targetImages) {
      return $results;
    }

    // 一時ディレクトリ作成
    $fs = new FileSystem();
    $now = new \DateTime(); // 更新時刻に利用
    $tmpDir = sprintf('%s/upload_image_yahoo_variation/%s_%s', $fileUtil->getDataDir(), $target, $now->format('YmdHis'));
    $fs->mkdir($tmpDir);

    $variationImageDir = $this->getContainer()->getParameter('product_image_variation_dir');

    $imageFiles = array(); // アップロード対象の画像リスト
    $daihyoSyohinCodeList = array(); // アップロード対象の代表商品コード
    $currentDaihyoSyohinCode = ''; // 現在処理中の代表商品
    foreach ($targetImages as $image) {

      // 冒頭でファイルサイズチェック。一つの代表商品コードの途中で終了させないためにアップロード制限まで5MB以上残っていないときは終了する
      if($currentDaihyoSyohinCode != $image['daihyo_syohin_code']) {
        $currentDaihyoSyohinCode = $image['daihyo_syohin_code'];
        if(self::MAX_UPLOAD_SIZE_YAHOO_VALIATION - 5000000 <= $uploadSize) {  // 一枚200KBと想定して20枚分以上
          $logger->info($logTitle . ': アップロード制限に達しました。今回の処理を終了します。');
          break;
        }
        $daihyoSyohinCodeList[] = $dbMain->quote($image['daihyo_syohin_code'], \PDO::PARAM_STR);
      }

      $filePath = sprintf('%s/%s/%s', $variationImageDir, $image['directory'], $image['filename']);
      $tmpImgPath = sprintf($tmpDir . '/' . $image['daihyo_syohin_code'] . '_' . $image['ne_syohin_syohin_code'] . '_main.jpg');
      if ($fs->exists($filePath)) {
        // 一時ディレクトリにコピーと、個々の画像の画素数＆ファイルサイズが上限を超えていたらリサイズ
        $this->copyAndResizeYahooVariation($filePath, $tmpImgPath);

        $file = [
            'path' => $tmpImgPath
            , 'filename' => $image['daihyo_syohin_code'] . '_' . $image['ne_syohin_syohin_code'] . '_main.jpg'
        ];
        $imageFiles[] = $file;

        $uploadCount++;
        $uploadSize += filesize($filePath);
      } else {
        $results['file_missing'][] = sprintf('%s : %s (local)', $image['daihyo_syohin_code'], $filePath);
      }
     }

    // zip ファイル作成とFTP upload
    // 上限 20MB
    if ($imageFiles) {
      $zipFiles = $this->archiveImageFiles($imageFiles, $tmpDir, 'lib_img', 20000000);

      if ($zipFiles) {
        $this->uploadZipToYahoo($target, $zipFiles, 'lib_img');
      }
    }

    // 一時ディレクトリは削除
    $fs->remove($tmpDir);

    $results['count'] = $uploadCount;
    $results['size'] = $uploadSize;

    // ファイルをアップロードした代表商品はアップロード日時を更新
    $this->updateYahooVariationUploadDate($target, $daihyoSyohinCodeList, $now);

    return $results;
  }

  /**
   * 画像パスを受け取り、Yahooバリエーション画像の上限サイズを超えていればリサイズしたうえで、destPathにコピーする。
   * @param unknown $origPath
   * @param unknown $destPath
   */
  private function copyAndResizeYahooVariation($origPath, $destPath) {
    $logger = $this->getLogger();

    $file_size = filesize($origPath);
    $im = new \Imagick($origPath);
    $width = $im->getImageWidth();
    $height = $im->getImageHeight();

    /* YAHOO_VARIATION_MAX_WIDTH * YAHOO_VARIATION_MAX_HEIGHTより画素数が大きいか、
     YAHOO_VARIATION_MAX_SIZE以上のファイルサイズの画像は
     YAHOO_VARIATION_MAX_WIDTH * YAHOO_VARIATION_MAX_HEIGHTに収まり、
     かつYAHOO_VARIATION_MAX_SIZEのサイズに収まるようにリサイズしてからアップロード */
    if ($width > self::YAHOO_VARIATION_MAX_WIDTH || $height > self::YAHOO_VARIATION_MAX_HEIGHT
        || $file_size >= self::YAHOO_VARIATION_MAX_SIZE) {
      $im->setImageFormat('jpg');
      $im->setOption('jpeg:extent', self::YAHOO_VARIATION_MAX_SIZE);

      if ($width > self::YAHOO_VARIATION_MAX_WIDTH || $height > self::YAHOO_VARIATION_MAX_HEIGHT) {
        $im->adaptiveResizeImage(self::YAHOO_VARIATION_MAX_WIDTH, self::YAHOO_VARIATION_MAX_HEIGHT, true);
      }

      $fp = fopen($destPath, 'w+b');
      fwrite($fp, $im->getImageBlob());
      rewind($fp);
    // サイズに支障がなければコピーするだけ
    } else {
      copy($origPath, $destPath);
    }
    $im->clear();
  }

  /**
   * Yahooへzipのアップロードを行う
   */
  private function uploadZipToYahoo($target, $zipFiles, $zipPrefix = 'img') {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $uploaded = [];
    $ftpConfig = $container->getParameter('ftp_yahoo');
    $config = isset($ftpConfig[$target]) ? $ftpConfig[$target] : null;
    if (!$config) {
      throw new \RuntimeException('no target config (yahoo ftp) !!');
    }

    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    $ftp->ssl_connect($config['host']);
    $ftp->login($config['user'], $config['password']);
    $ftp->pasv(true);
    $ftp->chdir('/');

    // YahooのFTP仕様により、ファイル名で取込順を指定できる。
    // 時刻は現在より未来の予定時刻を指定
    $baseDateTime = new \DateTime();
    $baseDateTime->modify('+5 minutes'); // 5分 余裕を見る

    // アップロード先 ファイル存在チェック
    $existsFiles = $ftp->nlist('/');
    if (!is_array($existsFiles)) {
      $e = new ExportCsvYahooUploadException(sprintf('[%s] FTP接続に失敗しました。処理を中止します。', $target));
      $e->setResults([
          'success' => $uploaded
          , 'error' => [ $target => $existsFiles ]
      ]);
      throw $e;
    } else if (count($existsFiles)) {

      // もし、すでにアップロードされているファイルがあれば、今回のアップロードはその最終取込指定時間より後の時刻を指定。
      // ※ NextEngineからの在庫連携もこのFTPを利用している
      $dateForName = null;
      foreach($existsFiles as $fileName) {
        if (preg_match('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $fileName, $match)) {
          $date = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]));
          if (!$dateForName || ($dateForName < $date)) {
            $dateForName = $date;
          }
        }
      }

      if ($dateForName && ($dateForName > $baseDateTime)) {
        $baseDateTime = clone $dateForName;
        $baseDateTime->modify('+1 minutes');
      }
    }

    $index = 1;
    foreach($zipFiles as $path) {
      $name = basename($path);
      $pattern = '/^' . $zipPrefix . '(\d+)\.zip$/';
      if (preg_match($pattern, $name)) {
        $newName = sprintf($zipPrefix . '%s%02d.zip', $baseDateTime->format('YmdHi'), $index++);
        $ftp->put($newName, $path, FTP_BINARY);
        $results['archives'][] = sprintf('%s => %s/%s', $path, dirname($path), $newName);

      } else {
        $logger->warning('unknown file is exists! (yahoo ftp upload). ' . $path);
        continue; // ひとまずスルー
      }
    }
    $ftp->close();
  }

  /**
   * PPM アップロード
   */
  private function processPPM()
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getDbCommonUtil();

    // 最終画像アップロード日時 取得
    $lastUpdated = $commonUtil->getUpdateRecordLastUpdatedDateTime(DbCommonUtil::UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_PPM);

    $fs = new FileSystem();

    // FTPアップロード処理
    $logTitle = '商品画像FTPアップロード(PPM)';
    $logger->info($logTitle . ': ファイルアップロード 開始');
    $uploadSize = 0;
    $uploadCount = 0;

    $results = [
      'count' => 0
      , 'size' => 0
      , 'min_timestamp' => ( $lastUpdated ? $lastUpdated->format('Y-m-d H:i:s') : null )
      , 'max_timestamp' => null
      , 'file_missing' => []
    ];

    $imageDir = $container->getParameter('product_image_dir');

    $config = $container->getParameter('ftp_ppm');
    if (!$config) {
      throw new \RuntimeException('no ftp config (PPM image upload)');
    }
    $config['user'] = $commonUtil->getSettingValue(TbSetting::KEY_PPM_FTP_USER);
    $config['password'] = $commonUtil->getSettingValue(TbSetting::KEY_PPM_FTP_PASSWORD);

    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
    try {
      if ($this->getEnvironment() == 'prod') {
        // PPM は FTPS接続。connectで失敗しても例外が飛ばないので login の成否で判定
        $ftp->ssl_connect($config['host']);
      } else {
        $ftp->connect($config['host']);
      }

      $ret = $ftp->login($config['user'], $config['password']);
      if (!$ret) {
        throw new \RuntimeException('ftp login failed.');
      }
    } catch (\Exception $e) {
      $message = 'PPMの画像アップロード処理中、PPMのFTPにログインできませんでした。';

      $logger->error(print_r($message, true));
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $ftp->pasv(true);
    $ftp->chdir($config['path_image']); // /imageUpload/images へ移動

    // itemrootディレクトリ作成
    $rootDirName = 'itemroot';
    $dirs = $ftp->nlist('.');
    if (!is_array($dirs)) {
      throw new \RuntimeException('ディレクトリ一覧の取得に失敗しました。');
    }
    if (!in_array($rootDirName, $dirs)) {
      $ftp->mkdir($rootDirName);
    }
    $ftp->chdir($rootDirName);

    // 画像一覧取得
    $newImageData = $this->findNewImagesPPM($lastUpdated);

    // １つずつアップロード
    $maxDateTime = null;
    $image = null;
    $uploadingSyohinCode = '';
    while($image = $newImageData->fetch(\PDO::FETCH_ASSOC)) {
      $maxDateTime = $image['updated'];

      $dir = $image['directory'];
      $filename = $image['filename'];

      // ディレクトリ作成
      $dirs = $ftp->nlist('.');

      if (!in_array($dir, $dirs)) {
        $ftp->mkdir($dir);
      }

      $remotePath = sprintf('%s/%s', $dir, $filename);
      $filePath = sprintf('%s/%s/%s', $imageDir, $dir, $filename);

      if ($fs->exists($filePath)) {
        $ftp->put($remotePath, $filePath, FTP_BINARY);
        $uploadCount++;
        $uploadSize += filesize($filePath);

        if($uploadingSyohinCode != $image['daihyo_syohin_code']) {
          $uploadingSyohinCode = $image['daihyo_syohin_code'];
          // 一つの代表商品コードの途中で終了させないためにアップロード制限まで20MB以上残っていないときは終了する
          if(self::MAX_UPLOAD_SIZE - 20000000 <= $uploadSize) {  // 一枚1MBと想定して20枚分
            $logger->info($logTitle . ': アップロード制限に達しました。今回の処理を終了します。');
            break;
          }
        }

      } else {
        $results['file_missing'][] = sprintf('%s : %s', $image['daihyo_syohin_code'], $remotePath);
      }
    }

    $ftp->close();

    $results['count'] = $uploadCount;
    $results['size'] = $uploadSize;

    // 最大最終更新日以前(アップロード済み)のフラグを更新する
    if($maxDateTime != null){
      $this->updateUploadImagesFlg($maxDateTime);
    }

    // 最終アップロード日時　更新
    // ※ アップロードした内、最大（＝最後）の最終更新日で更新
    $newLastUpdated = $maxDateTime ? new \DateTime($maxDateTime) : $lastUpdated;
    // もしファイルの最大最終更新日と前回の最大とが同じ場合、その時間のファイルをアップロードし終えたと考え、1秒進める。
    // （2GB分も同じファイルは存在しないとする。 ）
    if ($lastUpdated && $lastUpdated->format('Y-m-d H:i:s') === $newLastUpdated->format('Y-m-d H:i:s')) {
      $newLastUpdated->modify('+1 second');
    }
    $commonUtil->updateUpdateRecordTable(DbCommonUtil::UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_PPM, $newLastUpdated);

    $results['max_timestamp'] = $newLastUpdated->format('Y-m-d H:i:s');

    return $results;
  }



  /// Yahoo アップロード用圧縮ファイル作成
  /**
   * @param array $fileList
   * @param string $distDir
   * @param string $zipPrefix YahooのFTPアップロード用のプレフィックス　商品画像・商品詳細画像=img 追加画像（バリエーション画像）=lib_img
   * @param int $limitSize $zip1ファイルの最大サイズ（余裕を見て指定する事）
   * @return array
   */
  private function archiveImageFiles($fileList, $distDir, $zipPrefix = 'img', $limitSize = 49000000)
  {
    $logger = $this->getLogger();
    $zipFiles = [];
    $totalSize = 0;
    $num = 0;

    if ($fileList) {
      $dateTime = new \DateTime();
      $dateTime->modify('+5 minutes'); // 5分後に取り込まれる様に。
      $index = 1;
      $currentSize = 0;

      foreach($fileList as $file) {
        if (!isset($zip)) {
          $zip = new \ZipArchive();
          $fileName = sprintf('%s/%s%s%02d.zip', $distDir, $zipPrefix, $dateTime->format('YmdHi'), $index++);
          if (! $zip->open($fileName, \ZipArchive::CREATE)) {
            throw new RuntimeException('can not create image zip file. aborted. [' . $fileName . ']');
          }
          $zipFiles[] = $fileName;
        }

        $filePath = $file['path'];
        $localName = $file['filename'];

        $zip->addFile($filePath, $localName);
        $currentSize += filesize($filePath);
        $totalSize += filesize($filePath);
        $num++;

        // 閉じてオブジェクト削除（次のファイル作成の判定のため）
        if ($currentSize >= $limitSize) {
          $zip->close();
          $currentSize = 0;
          unset($zip);
        }
      }

      // 最後のファイルを閉じる
      if (isset($zip)) {
        $zip->close();
      }
    }

    return $zipFiles;
  }
}
