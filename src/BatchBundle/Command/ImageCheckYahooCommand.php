<?php

namespace BatchBundle\Command;

use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbMaintenanceSchedule;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Yahooへ画像がアップロードされているかをチェックし、アップロードされていなければinformationテーブルのlast_image_upload_datetimeを戻して再アップロード対象とする。
 * APIを利用するため、APIの呼び出し回数上限（1日5万、1秒1回まで）を考慮し、1回の起動ごとに、1店舗1000商品まで（3店舗で3000商品、約1時間）。※APIが代表商品単位
 * 
 * チェック対象は以下の通り。
 * ・商品の販売開始日、および画像登録日から3日経過（Yahoo側の取込期間やメンテを考慮し、更新直後はチェックしない）
 * ・それぞれの店舗で販売対象の商品
 * ・対象はYahooの仕様によりp001～p020の範囲
 * 
 * 処理にはチェックテーブル tb_yahoo_image_upload_check を利用する。
 * 
 * ■処理詳細
 *  
 * [チェックテーブルに掲載する対象となる画像の条件]
 * 
 * ・Yahooの仕様により、p001～p020の範囲の画像
 * ・deliverycode 即納・一部即納・受発注のみ・販売終了　kawa-e-monは即納・一部即納のみ
 * ・更新されたらチェック回数を0、チェックステータスを未チェックに戻す
 * 
 * [チェックテーブルに掲載されている中で、実際にチェックする条件]
 * 
 * ・Yahooで販売不可でない商品
 * 　・出品フラグ <> 0
 * 　・アダルトチェック がブラック、グレー、未審査でない
 * 　・販売終了から3年以内
 * 　・deliverycode_pre <> 仮登録（kawa-e-monでは販売終了、受発注のみも除外）
 * ・販売開始から3日以上経過
 * ・画像登録から3日以上経過
 * ・informationテーブルでアップロード済み扱いとなっており、3日以上経過
 * ・チェック回数が5回未満（5回再アップロード対象としても完了とならないのは、商品が販売されていないなど、他の問題の可能性があるためチェック対象から外す）
 * 
 * [チェック仕様]
 * 
 * ・Yahoo側のNoImage画像について
 * 　・各画像とも、API上では登録があることになっているのに、実際の表示画像は「画像がありません」画像（GIF）となることがある。
 * 　　APIには実際の画像のデータが登録されており、表示してみないとNoImageであることはわからないので、これについては実際にDLしてファイルサイズを見る。
 */
class ImageCheckYahooCommand extends PlusnaoBaseCommand
{  
  /** 関連処理完了までの予想時間 */
  const FINISH_MIN = 60;
  
  /** 画像ごとの最大チェック回数：これを超えるとチェック対象外とする */
  const IMAGE_CHECK_LIMIT = 5;
  
  /** モードAの「画像はありません」のファイルサイズ。 */
  const NOIMAGE_IMG_A_SIZE = 374;
  
  /** モードLの「画像はありません」のファイルサイズ。 */
  const NOIMAGE_IMG_L_SIZE = 2949;
  
  /** XML保存ディレクトリ。/data/yahooImageXml/[店舗]/ */
  private $xmlSaveDir = null;
  /** XMLを保存するか。パラメータ切り替え */
  private $isSaveXml = false;
  
  // 店舗ごとに固定の情報　privateメソッドで切り替える
  private $targetShop; // 対象店舗。plusnao|kawaemon|otoriyose。チェックテーブルのカラム名にも使用
  private $tableInformation; // モール別のinformationテーブル。モールごとの情報が全てここに格納されている。
  private $checkTableRegistUpdaterecordNo; // チェックテーブル最終登録日時を管理する、tb_updaterecordのNo
  private $neMallId; // NEモールID
  private $sellerId; // モール側店舗コード
  
  private $checkDatetime; // チェック日時private 

  protected function configure()
  {
    $this
      ->setName('batch:image-check-yahoo')
      ->setDescription('Yahoo画像アップロード済みチェック')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN) // このまま記載すること
      ->addOption('target-shop', null, InputOption::VALUE_OPTIONAL, 'チェックを行う場合、チェック対象店舗(コンマ区切り)。(plusnao|kawaemon|otoriyose)', NULL)
      ->addOption('target-product', null, InputOption::VALUE_OPTIONAL, '対象代表商品コード。主に本番デバッグ用。指定された場合、その商品のステータスを未処理に戻し1件処理', NULL)
      ->addOption('limit', null, InputOption::VALUE_OPTIONAL, '1店舗あたりの処理件数上限。アカウントごとの総API呼び出し回数制限があるので注意', 1000)
      ->addOption('save-xml', null, InputOption::VALUE_OPTIONAL, 'デバッグ用。XMLを保存するか', 0)
      ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = 'Yahoo画像チェック';
  }
  
  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $logger = $this->getLogger();

    // メンテナンス中はAPIが使用できないので終了
    // YahooはFTPメンテ中はAPI接続も、その後のアップロードも出来ないため、ここで即時終了する。
    /** @var \MiscBundle\Entity\Repository\TbMaintenanceScheduleRepository $repo */
    $mainteRepo = $this->getDoctrine()->getRepository('MiscBundle:TbMaintenanceSchedule');
    if ($mainteRepo->isMaintenance(array(TbMaintenanceSchedule::MAINTENANCE_TYPE_YAHOO_SCHEDULED), self::FINISH_MIN)) {
      throw new BusinessException("メンテナンス中のため処理スキップ");
    }
    
    if ($this->account) {
      $this->getWebAccessUtil()->setAccount($this->account);
    }
    
    // NoImage画像チェック（YahooのNoImage画像が変わっていると、NoImage画像が表示されているか把握出来ないので最初にチェック）
    $this->checkNoImageFile();
    
    // 対象店舗
    $targetShops = TbShoppingMall::getYahooShopList();
    if ($input->getOption('target-shop')) {
      $targetShops = [$input->getOption('target-shop')];
    }
    
    // 対象代表商品コード
    $targetDaihyoSyohinCode = null;
    if ($input->getOption('target-product')) {
      $targetDaihyoSyohinCode = $input->getOption('target-product');
    }
    
    // XMLを保存するか
    if ($input->getOption('save-xml')) {
      $this->isSaveXml = $input->getOption('save-xml');
    }
    
    // 店舗ごとにループ
    // sleepがあり処理時間が長いので、全体的にトランザクションは使用せず1クエリごとに反映。それを前提に、中断した時も、問題なく復旧できるように実装すること。
    $result = [];
    $totalCheckCount = 0;
    $totalFailureCount = 0;
    foreach ($targetShops as $shop) {
      
      $logger->info($this->commandName . "[$shop] 処理開始");
      
      // 対象店舗切り替え
      $this->changeTargetShop($shop);
    
      // チェックテーブルに対象データ登録・更新
      $this->registerCheckTarget($targetDaihyoSyohinCode);
      
      // チェック対象データを取得
      $imageList = $this->findTargetList($input->getOption('limit'), $targetDaihyoSyohinCode);
      if (empty($imageList)) { // 対象がなければ次の店舗へ
        $logger->info($this->commandName . "[$shop] 対象なしで終了");
        continue;
      }
      
      // clientとsellerId（Yahooの店舗コード） を取得
      $client = $this->getWebAccessUtil()->getClientWithYahooAccessToken($shop);

      $checkedList = null;
      $checkCount = 0;
      $failureCount = 0;
      $errorList = [];
      foreach ($imageList as $daihyoSyohinCode => $image) {
        $checkedList = $this->checkImage($client, $this->sellerId, $daihyoSyohinCode, $image);
        $existFailure = $this->resetUploadDatetime($daihyoSyohinCode, $checkedList);
        if ($existFailure) {
          $failureCount++;
          $errorList[] = $daihyoSyohinCode;
        }
        $this->updateCheckImage($checkedList);
        $checkCount++;
        sleep(1); // API仕様で、1秒1回まで
      }
      $result[$shop] = [
        'checkNum' => $checkCount
        , 'failureCount' => $failureCount
        , 'errorList' => $errorList
      ];      
      $totalCheckCount += $checkCount;
      $totalFailureCount += $failureCount;
      
      $logger->info($this->commandName . "[$shop] 処理終了：エラー商品：" . implode(',', $errorList));
    }
    $logger->addDbLog($logger->makeDbLog(null, 'チェック結果', "総件数[$totalCheckCount]", "画像なし件数[$totalFailureCount]")->setInformation($result));
    $this->processExecuteLog->setProcessNumber1($totalCheckCount);
  }
  
  /**
   * ダミー画像のファイルサイズを確認する。
   * （ダミー画像は適当なファイル名で表示されるようなので、それを利用）
   * 意図したサイズでない場合エラーを出力する。
   */
  private function checkNoImageFile() {
    $dummyModeAUrl = 'https://item-shopping.c.yimg.jp/i/a/plusnao_top-99999';
    $dummyModeLUrl = 'https://item-shopping.c.yimg.jp/i/l/plusnao_top-99999';
    
    // モードAのサイズチェック
    $modeABinary = file_get_contents($dummyModeAUrl);
    if (strlen($modeABinary) !== self::NOIMAGE_IMG_A_SIZE) {
      throw new \RuntimeException("YahooのNoImage画像のファイルサイズが異なっています。URL[$dummyModeAUrl], expected[" 
          . self::NOIMAGE_IMG_A_SIZE . "], acrual[" . strlen($modeABinary) . "]");
    }
    
    // モードLのサイズチェック
    $modeLBinary = file_get_contents($dummyModeLUrl);
    if (strlen($modeLBinary) !== self::NOIMAGE_IMG_L_SIZE) {
      throw new \RuntimeException("YahooのNoImage画像のファイルサイズが異なっています。URL[$dummyModeLUrl], expected["
        . self::NOIMAGE_IMG_L_SIZE . "], acrual[" . strlen($modeLBinary) . "]");
    }
  }
  
  /**
   * 対象店舗切り替え。
   */
  private function changeTargetShop($targetShop) {
    $this->targetShop = $targetShop;
    if ($this->isSaveXml) {
      $fileUtil = $this->getFileUtil();
      $this->xmlSaveDir = $fileUtil->getDataDir() . '/yahooImageXml/' . $targetShop;
      $fs = new FileSystem();
      if (!$fs->exists($this->xmlSaveDir)) {
        $fs->mkdir($this->xmlSaveDir, 0755);
      }
    }
    
    if ($this->targetShop == TbShoppingMall::BATCH_SHOP_CODE_YAHOO_PLUSNAO) {
      $this->tableInformation = 'tb_yahoo_information';
      $this->checkTableRegistUpdaterecordNo = DbCommonUtil::UPDATE_RECORD_YAHOO_IMAGE_CHECK_REGIST_PLUSNAO;
      $this->neMallId = TbShoppingMall::NE_MALL_ID_YAHOO;
    } else if ($this->targetShop == TbShoppingMall::BATCH_SHOP_CODE_YAHOO_KAWAEMON) {
      $this->tableInformation = 'tb_yahoo_kawa_information';
      $this->checkTableRegistUpdaterecordNo = DbCommonUtil::UPDATE_RECORD_YAHOO_IMAGE_CHECK_REGIST_KAWAEMON;
      $this->neMallId = TbShoppingMall::NE_MALL_ID_KAWA_E_MON;
    } else if ($this->targetShop == TbShoppingMall::BATCH_SHOP_CODE_YAHOO_OTORIYOSE) {
      $this->tableInformation = 'tb_yahoo_otoriyose_information';
      $this->checkTableRegistUpdaterecordNo = DbCommonUtil::UPDATE_RECORD_YAHOO_IMAGE_CHECK_REGIST_OTORIYOSE;
      $this->neMallId = TbShoppingMall::NE_MALL_ID_OTORIYOSE;
    } else {
      $this->getLogger()->addDbLog($this->getLogger()->makeDbLog($this->commandName, 'エラー終了', "店舗指定不正[" . $this->targetShop . "]"));
      throw new BusinessException("[Yahoo画像チェック準備処理]対象店舗指定不正のため処理終了");
    }
    $this->sellerId = TbShoppingMall::getMallShopCode($targetShop);
    
    $this->checkDatetime = new \DateTime();
  }
  
  /**
   * 
   * 登録・更新された画像群を、tb_yahoo_image_upload_check　に登録する。
   * 
   * 登録・更新対象は、店舗ごとに tb_updaterecord で最終登録済み日時を管理し、画像の更新日時がそれより新しいもの。
   * ただし代表商品コードが指定されていれば、その商品のみ登録・更新対象とする。この場合更新日時は考慮しない。
   * 
   * 登録済みで、更新時刻も変わらないものは無視する。登録済みだが更新時刻が更新されているものは、チェック状態を初期化する。
   * tb_yahoo_image_upload_checkは店舗ごと。現在処理中の店舗情報は、プロパティより取得する。
   * 画像更新時刻による管理を行うため、この時点で販売対象外の商品も登録する。
   * （例えば この時点でdeliverycode = 受発注のみ の商品を登録対象外にすると、tb_updaterecord の登録済み日時が進んでしまうので、
   * あとから入荷して即納に変更されても、tb_yahoo_image_upload_checkに登録されなくなる）
   * 
   * @param string $daihyoSyohinCode 特定の1件だけ処理する場合、代表商品コード。通常処理で良ければ null。そもそも処理対象でない商品の場合は何も登録されない
   */
  private function registerCheckTarget($daihyoSyohinCode = null) {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $now = new \DateTime();
    
    /** @var DbCommonUtil $dbUtil */
    $dbUtil = $this->getDbCommonUtil();
    
    $container = $this->getContainer();
    $codeListStr = $container->get('doctrine')->getRepository('MiscBundle:ProductImages')->getYahooImageCodeListStr();
    
    $addWheres = [];
    $addParams = [];
    $addWhereSql = "";
    if ($daihyoSyohinCode) {
      $addWheres[] = "pi.daihyo_syohin_code = :daihyoSyohinCode";
      $addParams[':daihyoSyohinCode'] = $daihyoSyohinCode;
    } else {
      $lastRegisterDatetime = $dbUtil->getUpdateRecordLastUpdatedDateTime($this->checkTableRegistUpdaterecordNo);
      $addWheres[] = "pi.updated = :fromDate";
      $addParams[':fromDate'] = $lastRegisterDatetime->format('Y-m-d H:i:s');
    }
    if ($addWheres) {
      $addWhereSql = sprintf(" AND ( %s ) ", implode(" AND ", $addWheres));
    }
    
    // 未登録の画像を登録
    $sql = <<<EOD
      INSERT INTO tb_yahoo_image_upload_check
      SELECT 
        pi.daihyo_syohin_code,
        pi.code,
        :neMallId,
        null,
        0,
        0
      FROM product_images pi
      JOIN tb_mainproducts_cal cal ON pi.daihyo_syohin_code = cal.daihyo_syohin_code
      LEFT JOIN tb_yahoo_image_upload_check c 
        ON pi.daihyo_syohin_code = c.daihyo_syohin_code AND pi.code = c.code AND c.ne_mall_id = :neMallId
      WHERE
        c.daihyo_syohin_code IS NULL
        {$addWhereSql}
        AND pi.code IN ( {$codeListStr} )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_INT);
    foreach ($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    
    // 登録済みで更新時刻が新しいものを初期化。
    $sql = <<<EOD
      UPDATE tb_yahoo_image_upload_check c
      JOIN product_images pi ON pi.daihyo_syohin_code = c.daihyo_syohin_code AND pi.code = c.code AND c.ne_mall_id = :neMallId
      SET 
        c.check_datetime = null,
        c.regist_status = 0,
        c.check_count = 0
      WHERE 1=1
        {$addWhereSql}
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_INT);
    foreach ($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    
    // 代表商品指定の時は登録日時は更新しない
    if (!$daihyoSyohinCode) {
      $dbUtil->updateUpdateRecordTable($this->checkTableRegistUpdaterecordNo, $now);
    }
  }
  
  /**
   * 処理対象のdeliverycodeを取得する
   * @return unknown
   */
  private function getDeliveryCodeString() {
    $targetDeliveryCode = [
      TbMainproductsCal::DELIVERY_CODE_READY
      , TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY
    ];
    if ($this->targetShop === TbShoppingMall::BATCH_SHOP_CODE_YAHOO_PLUSNAO // plusnaoとおとりよせは受発注のみ・販売終了も対象
        || $this->targetShop === TbShoppingMall::BATCH_SHOP_CODE_YAHOO_OTORIYOSE) {
      $targetDeliveryCode[] = TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER;
      $targetDeliveryCode[] = TbMainproductsCal::DELIVERY_CODE_FINISHED;
    }
    return implode(', ', $targetDeliveryCode);
  }
    
  /**
   * チェック対象画像を取得する。
   * チェックは代表商品単位で行うため、代表商品単位で取得する。
   * ・画像登録日時が古い順に代表商品を $limit ぶん取得
   * ・その代表商品の、チェックが必要な全ての画像を取得
   * 
   * 対象は、画像の更新日時が新しい順（古いものは過去にチェックを受けているなど、問題ない可能性が高いので、新しいものを先にチェック）
   * @param number $limit 処理件数
   * @param string $daihyoSyohinCode 特定の商品のチェックであれば、代表商品コード。指定がある場合、画像更新日時・販売開始日・アップロード日時は無視
   */
  private function findTargetList($limit, $daihyoSyohinCode = null) {
    $now = new \DateTimeImmutable();
    $salesFinishDate = $now->modify('-3 year'); // 販売終了から三年間
    
    $deliveryCodeStr = $this->getDeliveryCodeString();
    
    $addWheres = [];
    $addParams = [];
    $addWhereSql = "";
    if ($daihyoSyohinCode) {
      $addWheres[] = "pi.daihyo_syohin_code = :daihyoSyohinCode";
      $addParams[':daihyoSyohinCode'] = $daihyoSyohinCode;
    } else {
      $salesStartDate = $now->modify('-3 day'); // 販売開始から3日
      $imageUploadDate = $now->modify('-3 day'); // アップロードから3日
      $addWheres[] = "m.販売開始日 <= :salesStartDate";
      $addParams[':salesStartDate'] = $salesStartDate->format('Y-m-d 00:00:00');
      $addWheres[] = "i.last_image_upload_datetime <= :imageUploadDate";
      $addParams[':imageUploadDate'] = $imageUploadDate->format('Y-m-d H:i:s');
    }
    if ($addWheres) {
      $addWhereSql = sprintf(" AND ( %s ) ", implode(" AND ", $addWheres));
    }
    
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      SELECT
        pi.daihyo_syohin_code key_code, pi.daihyo_syohin_code, pi.code, pi.updated, c.check_count
      FROM product_images pi
      JOIN tb_yahoo_image_upload_check c ON pi.daihyo_syohin_code = c.daihyo_syohin_code AND pi.code = c.code
      JOIN (
        SELECT DISTINCT pi.daihyo_syohin_code
        FROM product_images pi
        JOIN tb_yahoo_image_upload_check c ON pi.daihyo_syohin_code = c.daihyo_syohin_code AND pi.code = c.code
        JOIN tb_mainproducts m ON pi.daihyo_syohin_code = m.daihyo_syohin_code
        JOIN tb_mainproducts_cal cal ON cal.daihyo_syohin_code = m.daihyo_syohin_code
        JOIN {$this->tableInformation} i ON i.daihyo_syohin_code = m.daihyo_syohin_code
        WHERE c.regist_status = 0
          AND c.ne_mall_id = :neMallId
          AND cal.deliverycode IN ( {$deliveryCodeStr} )
          AND IFNULL(m.YAHOOディレクトリID, '') <> ''
          AND i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone)
          AND (cal.endofavailability IS NULL OR cal.endofavailability >= :salesFinishDate)
          AND c.check_count < :imageCheckLimit
          {$addWhereSql}
          ORDER BY pi.updated DESC
          LIMIT :limit
      ) target ON target.daihyo_syohin_code = pi.daihyo_syohin_code
      WHERE c.regist_status = 0
        AND c.ne_mall_id = :neMallId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    $stmt->bindValue(':salesFinishDate', $salesFinishDate->format('Y-m-d 00:00:00'));
    $stmt->bindValue(':imageCheckLimit', self::IMAGE_CHECK_LIMIT, \PDO::PARAM_INT);
    $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_INT);
    $stmt->bindValue(':limit', (integer) $limit, \PDO::PARAM_INT);
    foreach ($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_GROUP);
  }
  
  /**
   * 指定された画像群のチェック日付・チェック結果を更新する。 ON DUPLICATE KEY UPDATE。
   */
  private function updateCheckImage($checkedList) {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();
    $commonUtil = $this->getDbCommonUtil();
    // 一括insertによるUPDATE
    $insertBuilder = new MultiInsertUtil("tb_yahoo_image_upload_check", [
      'fields' => [
        'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'code' => \PDO::PARAM_STR
        , 'ne_mall_id' => \PDO::PARAM_INT
        , 'check_datetime' => \PDO::PARAM_STR
        , 'regist_status' => \PDO::PARAM_INT
        , 'check_count' => \PDO::PARAM_INT
      ]
      , 'postfix' => " ON DUPLICATE KEY UPDATE "
        . "check_datetime = VALUES(check_datetime) "
        . ", regist_status = VALUES(regist_status) "
        . ", check_count = VALUES(check_count) "
    ]);
    $commonUtil->multipleInsert($insertBuilder, $dbMain, $checkedList, function($row) use ($logger) {
      $item = $row;
      $item['ne_mall_id'] = $this->neMallId;
      $item['check_datetime'] = $this->checkDatetime->format('Y-m-d H:i:s');
      $item['regist_status'] = $row['registStatus'];
      $item['check_count'] = ($row['check_count'] + 1);
      return $item;
    }, 'foreach');
  }
  
  /**
   * 失敗した画像があった代表商品について、アップロード日付をnullとし、再アップロード対象とする。
   * @return 更新件数
   */
  private function resetUploadDatetime($daihyoSyohinCode, $checkedList) {
    $dbMain = $this->getDb('main');
    $logger = $this->getLogger();
    // 失敗画像があるかチェック
    $hasError = false;
    foreach ($checkedList as $data) {
      if (!$data['registStatus']) {
        $hasError = true;
        break;
      }
    }
    if (!$hasError) {
      return 0;
    }
    
    // アップロード日付を初期化
    $sql = <<<EOD
      UPDATE {$this->tableInformation} 
      SET last_image_upload_datetime = null
      WHERE daihyo_syohin_code = :daihyoSyohinCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
    return 1; // DB側が既にnullの場合など、rowCount() が0になるので、1を固定で返却
  }
  
  /**
   * 指定された画像の存在をチェックする。
   * 代表商品の商品画像一覧API https://developer.yahoo.co.jp/webapi/shopping/itemImageList.html　を利用して、紐づく全画像を取得する。
   * このため、代表商品ごとにチェックが必要な画像をまとめて配列としてパラメータで渡す。
   * 
   * ・画像がなければ未チェックとし、再アップロード対象として処理する
   * ・画像があっても、画像日時が取得でき、forest側の更新日時より前であれば、更新が反映されていないとして未チェック、再アップロード対象とする。
   * ・NoImageの場合、URLは正しい画像と同じだが、ダウンロードするとNoImage画像が取得される。このため、ダウンロードしてファイルサイズを確認する。
   * 　・まず最小画像（modeA）で確認し、そのサイズがNoImageだと思われる場合、最大画像（modeL）で再確認する。
   * 
   * 結果は、チェック対象の画像の code をキー、アップロードが確認できたかの結果を値とする配列となる
   * @param client 有効なクライアント
   * @param sellerId Yahoo側の店舗コード　TbShoppingMallで管理
   * @param array $productImages 代表商品コードをキー、画像情報の配列を値とする二重配列
   * @return array 次の形式の二重配列 
   *   [
   *     'hoge-12345_p001' => ["daihyo_syohin_code" => "hoge-12345", "code" => "p001", "registStatus" => 0],
   *     'hoge-12345_p002' ["daihyo_syohin_code" => "hoge-12345", "code" => "p002", "registStatus" => 0],
   *     ...
   *   ]
   */
  private function checkImage($client, $sellerId, $daihyoSyohinCode, $productImages) {
    $logger = $this->getLogger();
    
    $checkedList = []; // 戻り値を初期化　すべて0（未チェック）
    foreach ($productImages as $targetImage) {
      $checkedList["{$daihyoSyohinCode}_{$targetImage['code']}"] = [
        'daihyo_syohin_code' => $daihyoSyohinCode
        , 'code' => $targetImage['code']
        , 'registStatus' => 0
        , 'check_count' => $targetImage['check_count']
      ];
    }
    
    $xml = $this->getXml($client, $daihyoSyohinCode);
    
    // 正常に画像リストが取得出来れば、結果をparseして格納（代表商品に紐づく全画像）
    $imageList = [];
    foreach ($xml->Result as $imageInfo) {
      // modeA、modeLのファイルを取得してサイズチェック 「画像がありません」ならmodeAは374バイト、modeLは2949バイトになる 「画像がありません」ならば画像なしとして次へ
      $modeABinary = file_get_contents($imageInfo->Url->ModeA);
      if (strlen($modeABinary) === self::NOIMAGE_IMG_A_SIZE) {
        $modeLBinary = file_get_contents($imageInfo->Url->ModeL);
        if (strlen($modeLBinary) === self::NOIMAGE_IMG_L_SIZE) {
          continue;
        }
      }
      
      $code = null;
      preg_match("/${daihyoSyohinCode}_([0-9]*)/i", $imageInfo->Name, $matches); // 画像ファイル名に2 などの末尾があれば取得 大文字小文字無視
      if ($matches) {
        $code = sprintf("p%03d", $matches[1] + 1);
      } else {
        $code = 'p001';
      }

      $updated = null;
      // 時間があれば時間を取得
      if ($imageInfo->UploadDate) {
        $updated = \DateTimeImmutable::createFromFormat(\DateTime::W3C, $imageInfo->UploadDate);
      }
      $imageData = ['fileName' => $imageInfo->Name, 'updated' => $updated];
      $imageList[$code] = $imageData;
    }
    
    // チェック対象の画像と商品画面の画像群を比較し、チェック対象画像の結果を登録
    foreach ($productImages as $targetImage) {
      $key = "{$daihyoSyohinCode}_{$targetImage['code']}";
      if (isset($imageList[$targetImage['code']])) {
        if ($imageList[$targetImage['code']]['updated']) { // 時刻があれば時刻までチェック
          // Yahoo側が新しければアップロード済み　Yahooの取込ペースを考えると、1秒差などだと実はYahooは古い画像の気がするがそこまでは考慮しない
          $dbUpdateDatetime = \DateTime::createFromFormat('Y-m-d H:i:s', $targetImage['updated']);
          if ($imageList[$targetImage['code']]['updated'] > $dbUpdateDatetime) {
            $checkedList[$key]['registStatus'] = 1;
          } else {
            $checkedList[$key]['registStatus'] = 0;
          }
        } else { // 時刻がないときは画像が存在すればOK 
          $checkedList[$key]['registStatus'] = 1;
        }
      }
    }
    return $checkedList;
  }
  
  /**
   * API結果のXMLオブジェクトを取得する。エラーの場合は\RuntimeExceptionを返却する。
   * 
   * ※テストでダミーデータを使う場合は、ここを書き換えて、
   * $dom = simplexml_load_string(file_get_contents("/home/workuser/tmp/yahoo_image_dummy.xml"));
   * といった形で、ファイルから読み込むようにすればダミーファイルが使用可能。
   * ※この場合、ダミーファイル内の<ID><Name>タグ内の代表商品コードが一致していないと全てアンマッチになるので注意。
   * 
   * @param unknown $client
   * @param string $daihyoSyohinCode
   * @return API結果をsimplexml_load_stringで読み込んだ DOMオブジェクト
   */
  private function getXml($client, $daihyoSyohinCode) {
    $url = "https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/itemImageList";
    $params = [
      'seller_id' => $this->sellerId
      , 'query' => strtolower($daihyoSyohinCode) // 商品コード前方一致。pet-01991 と pet-01991-1 がある場合、pet-01991を指定すると両方取れる
      , 'results' => 100 
    ];
    
    $client->request('get', $url . '?' . http_build_query($params));
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $dom = simplexml_load_string($response->getContent());
    if ($this->isSaveXml) {
      file_put_contents("{$this->xmlSaveDir}/{$daihyoSyohinCode}.xml", $response->getContent());
    }
    
    if ($response->getStatus() != 200) {
      $errorMessage = '';
      foreach($dom->xpath('/Error') as $error) {
        $errorMessage .= $error->asXML();
      }
      throw new \RuntimeException("Yahoo画像チェック：[$this->targetShop]　エラー " . $errorMessage);
    }
    return $dom;
  }
}
