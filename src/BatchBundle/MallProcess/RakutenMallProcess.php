<?php

namespace BatchBundle\MallProcess;
use BatchBundle\Job\RakutenCsvUploadJob;
use InvalidArgumentException;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use BatchBundle\Command\ExportCsvRakutenCommand;
use phpseclib\Net\SFTP;

/**
 * モール別特殊処理 - 楽天
 * 楽天plusnao、楽天motto、楽天lafoest、楽天dolcissimo、楽天gekiplaの分割に伴い、必要なところだけ分割
 */
class RakutenMallProcess extends BaseMallProcess
{

  const RAKUTEN_CATEGORY_LIST_URL = 'https://item.rakuten.co.jp/plusnao/c/';

  const RAKUTEN_MOTTO_CATEGORY_LIST_URL = 'https://item.rakuten.co.jp/motto-motto/c/';

  const RAKUTEN_LAFOREST_CATEGORY_LIST_URL = 'https://item.rakuten.co.jp/laforest/c/';

  const RAKUTEN_DOLCISSIMO_CATEGORY_LIST_URL = 'https://item.rakuten.co.jp/kobe-s/c/';

  const RAKUTEN_GEKIPLA_CATEGORY_LIST_URL = 'https://item.rakuten.co.jp/geki-pla/c/';

  const RAKUTEN_CABINET_URL = 'https://image.rakuten.co.jp/plusnao/cabinet/';

  const RAKUTEN_MOTTO_CABINET_URL = 'https://image.rakuten.co.jp/motto-motto/cabinet/';

  const RAKUTEN_LAFOREST_CABINET_URL = 'https://image.rakuten.co.jp/laforest/cabinet/';

  const RAKUTEN_DOLCISSIMO_CABINET_URL = 'https://image.rakuten.co.jp/kobe-s/cabinet/';

  const RAKUTEN_GEKIPLA_CABINET_URL = 'https://image.rakuten.co.jp/geki-pla/cabinet/';

  /** 対象店舗 */
  private $targetShop = null;
  /** テーブル名：カテゴリリスト */
  private $tableCategoryList = null;
  /** テーブル名：店舗別information */
  private $tableMallInformation = null;
  /** テーブル名：店舗別 楽天item.csvのインポートテーブル */
  private $tableItemDl = null;

  public static $IMAGE_CODE_LIST = [
      'p001'
    , 'p002'
    , 'p003'
    , 'p004'
    , 'p005'
    , 'p006'
    , 'p007'
    , 'p008'
    , 'p009'
    , 'p010'
    , 'p011'
    , 'p012'
    , 'p013'
    , 'p014'
    , 'p015'
    , 'p016'
    , 'p017'
    , 'p018'
    , 'p019'
    , 'p020'
  ];

  /**
   * 対象店舗を指定する。
   * ※歴史的経緯により、ここで指定した店舗は、他の店舗指定が可能なメソッドでは上書きされることがあるので注意。
   */
  public function setTargetShop($targetShop) {
    $this->targetShop = $targetShop;
  }

  /**
   * パンくずリスト作成
   *
   * @param targetShop 対象店舗 rakuten|motto|laforest|dolcissimo|gekipla
   */
  public function createPankuzuList($targetShop, BatchLogger $logger)
  {
    $dbMain = $this->getDb('main');

    // 対象店舗ごとに分かれる設定を取得
    $this->targetShop = $targetShop;
    if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN) {
      $this->tableCategoryList = 'tb_rakuten_category_list';
      $this->tableMallInformation = 'tb_rakuteninformation';
    } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO) {
      $this->tableCategoryList = 'tb_rakutenmotto_category_list';
      $this->tableMallInformation = 'tb_rakuten_motto_information';
    } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST) {
      $this->tableCategoryList = 'tb_rakutenlaforest_category_list';
      $this->tableMallInformation = 'tb_rakuten_laforest_information';
    } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO) {
      $this->tableCategoryList = 'tb_rakutendolcissimo_category_list';
      $this->tableMallInformation = 'tb_rakuten_dolcissimo_information';
    } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA) {
      $this->tableCategoryList = 'tb_rakutengekipla_category_list';
      $this->tableMallInformation = 'tb_rakuten_gekipla_information';
    } else {
      throw new RuntimeException('店舗指定が正しくありません');
    }

    $this->insertRakutenCategoryList($logger);

    // 'tb_rakutencategoryに転記
    // LEFT JOINでないと、連続実行で tb_rakutencategory の初期化を行わない場合、
    // motto|laforest|dolcissimo|gekipla側にplusnaoのパンくずリストが残ってしまう場合がある
    // LEFT JOIN にすることで、motto|laforest|dolcissimo|gekipla側にないものはパンくずを削除
    $sql = <<<EOD
      UPDATE tb_rakutencategory AS c
      LEFT JOIN {$this->tableCategoryList} AS l ON c.表示先カテゴリ = l.表示先カテゴリ
      SET c.cat_list_html = l.html
EOD;
    $dbMain->query($sql);

    // '商品毎のカテゴリHTMLを１つにまとめる
    $dbMain->query("SET SESSION group_concat_max_len = 20480");
    $sql = <<<EOD
    UPDATE {$this->tableMallInformation} AS i
    INNER JOIN (
        SELECT
            daihyo_syohin_code
          , GROUP_CONCAT(cat_list_html ORDER BY `ID` SEPARATOR '\r\n') AS cat_list_html
        FROM tb_rakutencategory
        GROUP BY daihyo_syohin_code
    ) AS sub ON i.daihyo_syohin_code = sub.daihyo_syohin_code
    SET i.cat_list_html = sub.cat_list_html
EOD;
    $dbMain->query($sql);
  }


  /**
   * レビュー本文表示設定___
   *   * 仕様：商品ごとに直近3レビューまでの中にレビュー得点3以下のレビューが無い商品について、レビュー表示 ON
   *
   */
  public function setReviewDisplay($targetShop)
  {
    // 対象店舗ごとに分かれる設定を取得
    $this->targetShop = $targetShop;
    $neMallId = null;
    if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN) {
      $this->tableMallInformation = 'tb_rakuteninformation';
      $neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN;
    } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO) {
      $this->tableMallInformation = 'tb_rakuten_motto_information';
      $neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_MOTTO;
    } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST) {
      $this->tableMallInformation = 'tb_rakuten_laforest_information';
      $neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_LAFOREST;
    } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO) {
      $this->tableMallInformation = 'tb_rakuten_dolcissimo_information';
      $neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_DOLTI;
    } else if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA) {
      $this->tableMallInformation = 'tb_rakuten_gekipla_information';
      $neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_GEKIPLA;
    } else {
      throw new RuntimeException('店舗指定が正しくありません');
    }

    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'レビュー本文表示設定';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('レビュー本文表示設定を再設定しています。');

    $dbMain->query("TRUNCATE tb_rakuten_review_tmp");
    $dbMain->query("TRUNCATE tb_rakuten_review_tmp_del");

    $sql = <<<EOD
      INSERT INTO tb_rakuten_review_tmp (
          daihyo_syohin_code
        , review_datetime
        , レビュー得点
      )
      SELECT
          r.daihyo_syohin_code
        , r.review_datetime
        , r.score
      FROM tb_product_reviews r
      WHERE r.ne_mall_id = :neMallId
      ORDER BY r.daihyo_syohin_code, r.review_datetime DESC
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallId', $neMallId);
    $stmt->execute();

    // 同一商品に連番を振る
    $sql = <<<EOD
      UPDATE tb_rakuten_review_tmp UT
      INNER JOIN (
        SELECT
           T1.daihyo_syohin_code
         , T2.ID
         , COUNT(*) AS CNT
        FROM tb_rakuten_review_tmp T1
        INNER JOIN tb_rakuten_review_tmp T2
           ON T1.daihyo_syohin_code = T2.daihyo_syohin_code
          AND T1.ID <= T2.ID
        GROUP BY T1.daihyo_syohin_code, T2.ID
        ORDER BY T1.daihyo_syohin_code, T1.ID
      ) T ON UT.ID = T.ID
      SET UT.商品行番号 = T.CNT
EOD;
    $dbMain->query($sql);

    // '直近３レビュー以外を削除する
    $sql = <<<EOD
      DELETE FROM tb_rakuten_review_tmp
      WHERE COALESCE(daihyo_syohin_code, '') = ''
         OR 商品行番号 > 3
EOD;
    $dbMain->query($sql);

    // '悪評価を抽出
    $sql = <<<EOD
      INSERT INTO tb_rakuten_review_tmp_del (
        daihyo_syohin_code
      )
      SELECT daihyo_syohin_code
      FROM tb_rakuten_review_tmp AS del
      WHERE del.レビュー得点 <= 3
      GROUP BY daihyo_syohin_code
EOD;
    $dbMain->query($sql);

    // '悪評価のある商品を対象から除外
    $sql = <<<EOD
      DELETE tmp.*
      FROM tb_rakuten_review_tmp AS tmp
      INNER JOIN tb_rakuten_review_tmp_del AS del ON tmp.daihyo_syohin_code = del.daihyo_syohin_code
EOD;
    $dbMain->query($sql);

    // '良評価の商品のみレビュー本文表示を１、その他を0
    $sql = <<<EOD
      UPDATE {$this->tableMallInformation} AS i
      LEFT JOIN tb_rakuten_review_tmp AS tmp ON i.daihyo_syohin_code = tmp.daihyo_syohin_code
      SET i.レビュー本文表示 =
            CASE WHEN tmp.ID IS NOT NULL
              THEN '1'
              ELSE '0'
            END
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * 販売期間設定
   */
  public function setSalePeriod()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = '販売期間指定___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));
    $logger->info('販売期間指定を設定しています。');

    // '全体の販売期間を設定
    $salesPeriod = 0;
    if ($commonUtil->getSettingValue('RAKUTEN_SALES_PERIOD_ENABLED')) {
      $endDate = $commonUtil->getSettingValue('RAKUTEN_SALES_END_DATE');
      $endTime = $commonUtil->getSettingValue('RAKUTEN_SALES_END_TIME');

      if (
           preg_match('/^(\d{4})(\d{2})(\d{2})$/', $endDate, $mDate)
        && preg_match('/^(\d{2})(\d{2})$/', $endTime, $mTime)
      ) {
        $now = new \DateTime();
        $salesPeriod = sprintf('%s 00:00 %04d/%02d/%02d %02d:%02d'
                                , $now->format('Y-m-d')
                                , $mDate[1], $mDate[2], $mDate[3]
                                , $mTime[1], $mTime[2]
                        );
        $logger->info($salesPeriod);
      }
    }

    $sql = <<<EOD
      UPDATE {$this->tableMallInformation} as i SET i.sales_period = :salesPeriod
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':salesPeriod', $salesPeriod);
    $stmt->execute();

    // '個別の販売期間を設定
    $sql = <<<EOD
      UPDATE {$this->tableMallInformation} AS i
      SET sales_period = CONCAT(
            DATE_FORMAT(sales_period_start_date, '%Y/%m/%d')
          , ' '
          , sales_period_start_time
          , ' '
          , DATE_FORMAT(sales_period_end_date, '%Y/%m/%d')
          , ' '
          , sales_period_end_time
      )
      WHERE sales_period_start_date <> '0000-00-00'
        AND IFNULL(sales_period_start_time, '') <> ''
        AND sales_period_end_date <> '0000-00-00'
        AND IFNULL(sales_period_end_time, '') <> ''
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * 倉庫格納フラグを更新する。
   *
   * いったんすべて1（格納）とし、以下の順に基準件数までを0（格納しない）に更新する。
   *
   * 設定順は以下の通り（すべて、楽天で販売対象であることが条件）
   *  1. 即納、一部即納の商品
   *  2. 倉庫不可フラグあり（受発注のみ）
   *  3. 過去2年間の売上高（金額）降順に並び替え（受発注のみ・販売終了）
   *  4. 発売日降順（受発注のみ・販売終了）
   * 
   * motto|laforest|dolcissimo|gekiplaの場合は、楽天で売れ行きの良い商品は倉庫から出さない。
   * 更に、laforestはレビュー平均が指定値未満の商品は倉庫から出さない。
   *
   * @param BatchLogger $logger
   * @param string $targetShop 対象店舗。rakuten|motto|laforest|dolcissimo|gekipla
   */
  public function setWarehouseStoredFlg(BatchLogger $logger, $targetShop) {

    // 対象店舗ごとに分かれる設定を取得
    $this->targetShop = $targetShop;
    if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN) {
      $this->tableMallInformation = 'tb_rakuteninformation';
      $this->tableItemDl = 'tb_rakutenitem_dl';
    } else if (
      $targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO
      || $targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST
      || $targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO
      || $targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA
    ) {
      $this->tableMallInformation = "tb_rakuten_{$targetShop}_information";
      $this->tableItemDl = "tb_rakuten{$targetShop}_item_dl";

      // motto/laforest/dolcissimo/gekiplaのページ非公開設定
      $today = new \DateTime();
      $today->setTime(0, 0, 0);
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
      // 現時点では、laforestはmottoの各設定値+レビュー平均点数なので、laforestを指定
      $settingsArray = $repo->findUnpublishedPageSettingByArray('laforest');
      $minReviewPointAve = 
          $targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST
          ? $settingsArray[TbSetting::KEY_LAFOREST_REVIEW_POINT]
          : '0';
      $quantityBaseDate = clone $today;
      $quantityBaseDate->modify(sprintf('- %d day', $settingsArray[TbSetting::KEY_MOTTO_QUANTITY_DAYS]));
      $salesBaseDate = clone $today;
      $salesBaseDate->modify(sprintf('- %d day', $settingsArray[TbSetting::KEY_MOTTO_SALES_DAYS]));
    } else {
      throw new RuntimeException('店舗指定が正しくありません');
    }

    // まず全件格納に更新
    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      UPDATE {$this->tableMallInformation} as i SET warehouse_stored_flg = :warehouse_stored_flg
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouse_stored_flg', 1);
    $stmt->execute();

    // 1. 即納、一部即納の商品
    // 2. 倉庫不可フラグ有効（受発注のみ）
    // 別々に行うと件数カウント1回ぶんコストが増えるので一括で。ここまでは上限は超えないはず
    $stmt = null;

    // 楽天plusnao
    if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN) {
      $sql = <<<EOD
        UPDATE
          {$this->tableMallInformation} i
        JOIN tb_mainproducts                  AS m   ON i.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
        INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        SET i.warehouse_stored_flg = :warehouse_stored_flg
        WHERE i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          AND cal.deliverycode <> :deliveryCodeTemporary
          AND (
            (cal.deliverycode IN ( :deliveryCodeReady , :deliveryCodeReadyPartially)) -- 即納、一部即納
            OR (i.warehouse_flg <> 0 AND cal.deliverycode = :deliveryCodePurchaseOnOrder)-- 倉庫不可フラグ
          )
EOD;
      $stmt = $dbMain->prepare($sql);

    // 楽天motto|laforest|dolcissimo|gekipla
    } else {
      $sql = <<<EOD
        UPDATE
          {$this->tableMallInformation} i
        JOIN tb_mainproducts                  AS m   ON i.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
        INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        LEFT JOIN (
          SELECT
            daihyo_syohin_code
          FROM
            tb_sales_detail_analyze a
          WHERE
            `受注日` >= :quantityBaseDate
            AND a.`キャンセル区分` = '0'
            AND a.`明細行キャンセル` = '0'
            AND a.`店舗コード` = :mallRakuten
          GROUP BY
            daihyo_syohin_code
          HAVING
            SUM(`受注数`) >= :quantityBaseSum
          ) amt ON m.daihyo_syohin_code = amt.daihyo_syohin_code
        LEFT JOIN (
          SELECT
            daihyo_syohin_code
          FROM
            tb_sales_detail_analyze a
          WHERE
            `受注日` >= :salesBaseDate
            AND a.`キャンセル区分` = '0'
            AND a.`明細行キャンセル` = '0'
            AND a.`店舗コード` = :mallRakuten
          GROUP BY
            daihyo_syohin_code
          HAVING
            SUM(`小計`) >= :salesBaseSum
        ) total ON m.daihyo_syohin_code = total.daihyo_syohin_code
        SET i.warehouse_stored_flg = :warehouse_stored_flg
        WHERE i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          AND cal.deliverycode <> :deliveryCodeTemporary
          AND (
            (cal.deliverycode IN ( :deliveryCodeReady , :deliveryCodeReadyPartially)) -- 即納、一部即納
            OR (i.warehouse_flg <> 0 AND cal.deliverycode = :deliveryCodePurchaseOnOrder)-- 倉庫不可フラグ
          )
          AND amt.daihyo_syohin_code IS NULL
          AND total.daihyo_syohin_code IS NULL
          AND cal.review_point_ave >= :minReviewPointAve
EOD;
      $stmt = $dbMain->prepare($sql);

      // motto/laforest/dolcissimo/gekiplaのページ非公開設定
      $stmt->bindValue(':quantityBaseDate', $quantityBaseDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':quantityBaseSum', $settingsArray[TbSetting::KEY_MOTTO_QUANTITY], \PDO::PARAM_STR);
      $stmt->bindValue(':salesBaseDate', $salesBaseDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':salesBaseSum', $settingsArray[TbSetting::KEY_MOTTO_SALES], \PDO::PARAM_STR);
      $stmt->bindValue(':mallRakuten', TbShoppingMall::NE_MALL_ID_RAKUTEN, \PDO::PARAM_STR);
      $stmt->bindValue(':minReviewPointAve', $minReviewPointAve, \PDO::PARAM_STR);
    }

    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':warehouse_stored_flg', 0, \PDO::PARAM_INT);
    $stmt->execute();
    $updateCount = $stmt->rowCount(); // 更新件数

    // 上限から、ここまでに倉庫から出した件数を減算
    $remainingCount = ExportCsvRakutenCommand::NON_WAREHOUSE_LIMIT - $updateCount;

    $logger->info("楽天CSV出力： item-3rd.csv 倉庫フラグ計算中: 即納、一部即納 + 倉庫不可フラグ : $updateCount 件 / 残件: $remainingCount");

    // 3. 過去2年間の売上高（金額）降順に並び替え（受発注のみ・販売終了）
    // 4. 発売日降順（受発注のみ・販売終了）
    // UPDATE と JOIN と ORDER BYを使おうとしたらIncorrect usage of UPDATE and ORDER BYが出たので、SELECTしてPK指定でUPDATE
    $stmt = null;
    // 楽天plusnao
    if ($targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN) {
      $sql = <<<EOD
        SELECT
          i.daihyo_syohin_code
        FROM {$this->tableMallInformation} i
        JOIN tb_mainproducts                  AS m   ON i.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
        INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        LEFT JOIN
          (
            SELECT
              s.daihyo_syohin_code,
              SUM(s.detail_amount) AS amount
            FROM
                tb_sales_detail_summary_item_ym AS s
                JOIN tb_mainproducts_cal cal ON s.daihyo_syohin_code = cal.daihyo_syohin_code
            WHERE
              cal.deliverycode IN (:deliveryCodePurchaseOnOrder, :deliveryCodeFinished)
              AND s.order_ym BETWEEN :startDate AND :endDate
            GROUP BY daihyo_syohin_code
           ) AS sales ON sales.daihyo_syohin_code = m.daihyo_syohin_code
        WHERE i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          AND cal.deliverycode IN (:deliveryCodePurchaseOnOrder, :deliveryCodeFinished)
          AND i.warehouse_stored_flg = 1
        ORDER BY sales.amount IS NULL ASC, sales.amount DESC, m.販売開始日 DESC
        LIMIT :remainingCount
EOD;
      $stmt = $dbMain->prepare($sql);

    // 楽天motto|laforest|dolcissimo|gekiplaは登録済み商品優先
    // 楽天plusnaoで一定以上売れている商品を非表示とするため、
    // 楽天plusnaoで基準以上の売上数・売上額の商品をLEFT JOINする（取得できたものは非表示）
    } else {
      $sql = <<<EOD
        SELECT
          i.daihyo_syohin_code
        FROM {$this->tableMallInformation} i
        JOIN tb_mainproducts                  AS m   ON i.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_plusnaoproductdirectory AS d   ON m.NEディレクトリID = d.NEディレクトリID
        INNER JOIN tb_mainproducts_cal        AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        LEFT JOIN {$this->tableItemDl}     AS dl  ON m.daihyo_syohin_code = dl.商品管理番号（商品URL）
        LEFT JOIN
          (
            SELECT
              s.daihyo_syohin_code,
              SUM(s.detail_amount) AS amount
            FROM
                tb_sales_detail_summary_item_ym AS s
                JOIN tb_mainproducts_cal cal ON s.daihyo_syohin_code = cal.daihyo_syohin_code
            WHERE
              cal.deliverycode IN (:deliveryCodePurchaseOnOrder, :deliveryCodeFinished)
              AND s.order_ym BETWEEN :startDate AND :endDate
            GROUP BY daihyo_syohin_code
           ) AS sales ON sales.daihyo_syohin_code = m.daihyo_syohin_code
        LEFT JOIN (
          SELECT
            daihyo_syohin_code
          FROM
            tb_sales_detail_analyze a
          WHERE
            `受注日` >= :quantityBaseDate
            AND a.`キャンセル区分` = '0'
            AND a.`明細行キャンセル` = '0'
            AND a.`店舗コード` = :mallRakuten
          GROUP BY
            daihyo_syohin_code
          HAVING
            SUM(`受注数`) >= :quantityBaseSum
          ) amt ON m.daihyo_syohin_code = amt.daihyo_syohin_code
        LEFT JOIN (
          SELECT
            daihyo_syohin_code
          FROM
            tb_sales_detail_analyze a
          WHERE
            `受注日` >= :salesBaseDate
            AND a.`キャンセル区分` = '0'
            AND a.`明細行キャンセル` = '0'
            AND a.`店舗コード` = :mallRakuten
          GROUP BY
            daihyo_syohin_code
          HAVING
            SUM(`小計`) >= :salesBaseSum
        ) total ON m.daihyo_syohin_code = total.daihyo_syohin_code
        WHERE i.registration_flg <> 0
          AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
          AND cal.deliverycode IN (:deliveryCodePurchaseOnOrder, :deliveryCodeFinished)
          AND i.warehouse_stored_flg = 1
          AND amt.daihyo_syohin_code IS NULL
          AND total.daihyo_syohin_code IS NULL
          AND cal.review_point_ave >= :minReviewPointAve
        ORDER BY
          CASE -- 登録済み優先
            WHEN dl.商品管理番号（商品URL） IS NOT NULL THEN 0
            ELSE 1
          END ASC,
          sales.amount IS NULL ASC, sales.amount DESC, m.販売開始日 DESC
        LIMIT :remainingCount
EOD;
      $stmt = $dbMain->prepare($sql);

      // motto固有のパラメータ(laforest/dolcissimo/gekiplaもページ非公開設定はmottoに合わせる)
      $stmt->bindValue(':quantityBaseDate', $quantityBaseDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':quantityBaseSum', $settingsArray[TbSetting::KEY_MOTTO_QUANTITY], \PDO::PARAM_STR);
      $stmt->bindValue(':salesBaseDate', $salesBaseDate->format('Y-m-d'),  \PDO::PARAM_STR);
      $stmt->bindValue(':salesBaseSum', $settingsArray[TbSetting::KEY_MOTTO_SALES], \PDO::PARAM_STR);
      $stmt->bindValue(':mallRakuten', TbShoppingMall::NE_MALL_ID_RAKUTEN, \PDO::PARAM_STR);
      $stmt->bindValue(':minReviewPointAve', $minReviewPointAve, \PDO::PARAM_STR);
    }

    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED, \PDO::PARAM_INT);
    $stmt->bindValue(':startDate', (new \DateTime())->modify('-2 year')->format('Ym'), \PDO::PARAM_INT); // 売上参照期間START
    $stmt->bindValue(':endDate', (new \DateTime())->modify('-1 day')->format('Ym'), \PDO::PARAM_INT); // 売上参照期間END
    $stmt->bindValue(':remainingCount', $remainingCount, \PDO::PARAM_INT);
    $stmt->execute();

    $quotedDaihyoSyohinCodeList = array();
    $count = 0;
    $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    foreach($list as $index => $row) {

      $quotedDaihyoSyohinCodeList[] = "'" . $row['daihyo_syohin_code'] . "'";
      $count++;
      // 1000件ごとに反映
      if ($count >= 1000) {
        $daihyoSyohinCodeStr = implode(',', $quotedDaihyoSyohinCodeList);
        $sql = <<<EOD
        UPDATE {$this->tableMallInformation} SET warehouse_stored_flg = :warehouse_stored_flg WHERE daihyo_syohin_code IN ( {$daihyoSyohinCodeStr} )
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':warehouse_stored_flg', 0, \PDO::PARAM_INT);
        $stmt->execute();

        $count = 0;
        $quotedDaihyoSyohinCodeList = array();
      }
    }
    // 余りを処理
    if ($quotedDaihyoSyohinCodeList) {
      $daihyoSyohinCodeStr = implode(',', $quotedDaihyoSyohinCodeList);
      $sql = <<<EOD
          UPDATE {$this->tableMallInformation} SET warehouse_stored_flg = :warehouse_stored_flg WHERE daihyo_syohin_code IN ( {$daihyoSyohinCodeStr} )
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':warehouse_stored_flg', 0, \PDO::PARAM_INT);
      $stmt->execute();
    }
  }

  // --------------------------------------------
  // 以下、内部メソッド
  // --------------------------------------------

  /**
   * getInsRakutenCateList
   */
  private function insertRakutenCategoryList(BatchLogger $logger)
  {
    $dbMain = $this->getDb('main');

    $dbMain->query('TRUNCATE tb_rakuten_category_list_save');

    // '表示順と表示Fを退避
    $sql = <<<EOD
       INSERT INTO tb_rakuten_category_list_save (
           表示先カテゴリ
         , 表示順
         , 表示F
       )
       SELECT
           表示先カテゴリ
         , 表示順
         , 表示F
       FROM {$this->tableCategoryList}
EOD;
    $dbMain->query($sql);

    // カテゴリ一覧を楽天から取得しながら作成
    $this->updateCategoryListFromRakutenWebSite($logger);

    // updateCategoryListFromRakutenWebSite が503により処理なく終了したときも、以下の項目は繰り返し処理しても問題なさそうなのでそのまま継続

    // '表示先カテゴリを組み立てる
    $sql = <<<EOD
      UPDATE {$this->tableCategoryList}
      SET {$this->tableCategoryList}.表示先カテゴリ = CONCAT(
          CASE WHEN COALESCE(cat1, '') = '' THEN '' ELSE cat1 END
        , CASE WHEN COALESCE(cat2, '') = '' THEN '' ELSE CONCAT('\\\\', cat2) END
        , CASE WHEN COALESCE(cat3, '') = '' THEN '' ELSE CONCAT('\\\\', cat3) END
        , CASE WHEN COALESCE(cat4, '') = '' THEN '' ELSE CONCAT('\\\\', cat4) END
        , CASE WHEN COALESCE(cat5, '') = '' THEN '' ELSE CONCAT('\\\\', cat5) END
      );
EOD;
    $dbMain->query($sql);

    // '表示順と表示Fを復元
    $sql = <<<EOD
      UPDATE {$this->tableCategoryList} as list
      INNER JOIN tb_rakuten_category_list_save AS save ON list.表示先カテゴリ = save.表示先カテゴリ
      SET list.表示順 = save.表示順
        , list.表示F  = save.表示F
EOD;
    $dbMain->query($sql);

    // 'cat2が空白のものは表示FをOffに
    $sql = <<<EOD
      UPDATE {$this->tableCategoryList}
      SET 表示F = 0
      WHERE COALESCE(cat2, '') = ''
EOD;
    $dbMain->query($sql);

    return;
  }


  /**
   * 楽天カテゴリリストを楽天から取得し、初期登録をおこなう。
   *
   * TODO できれば楽天の CategoryApi で取得することが望ましい
   * @see https://mainmenu.rms.rakuten.co.jp/auth/index.phtml?params=service/api/17.html&s=&param2=0
   * '「店舗内カテゴリトップ」の次の<table ～ </table><br><br>まで
   * 
   * このメソッドは、歴史的経緯によりパラメータで店舗を指定しない。
   * （もとはprivateメソッドだったので）
   * 外部から呼ぶときは、事前に setTargetShop() で店舗設定を行う必要がある。
   *
   * @param $logger BatchLogger
   */
  public function updateCategoryListFromRakutenWebSite(BatchLogger $logger)
  {
    // URLをサイトごとに切り替え
    $baseUrl = null;
    $shopStr = null;
    if ($this->targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN) {
      $baseUrl = self::RAKUTEN_CATEGORY_LIST_URL;
      $this->tableCategoryList = 'tb_rakuten_category_list';
      $shopStr = 'plusnao';
    } elseif ($this->targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO) {
      $baseUrl = self::RAKUTEN_MOTTO_CATEGORY_LIST_URL;
      $this->tableCategoryList = 'tb_rakutenmotto_category_list';
      $shopStr = 'motto-motto';
    } elseif ($this->targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST) {
      $baseUrl = self::RAKUTEN_LAFOREST_CATEGORY_LIST_URL;
      $this->tableCategoryList = 'tb_rakutenlaforest_category_list';
      $shopStr = 'laforest';
    } elseif ($this->targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO) {
      $baseUrl = self::RAKUTEN_DOLCISSIMO_CATEGORY_LIST_URL;
      $this->tableCategoryList = 'tb_rakutendolcissimo_category_list';
      $shopStr = 'kobe-s';
    } elseif ($this->targetShop === ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA) {
      $baseUrl = self::RAKUTEN_GEKIPLA_CATEGORY_LIST_URL;
      $this->tableCategoryList = 'tb_rakutengekipla_category_list';
      $shopStr = 'geki-pla';
    } else {
      throw new \RuntimeException('カテゴリリスト作成の店舗指定が不正です。[' . $this->targetShop . ']');
    }

    $webClient = $this->getWebAccessUtil()->getWebClient();

    // サイトは EUC-JP だが、きちんとヘッダ・metaタグがあるためCrawlerにより自動でUTF-8変換される
    $crawler = $webClient->request('GET', $baseUrl);
    /** @var Response $response */
    $response = $webClient->getResponse();

    // 503エラーの場合は、再構築を行わず過去のものをそのまま使用
    if ($response->getStatus() == 503) {
      $logger->addDbLog($logger->makeDbLog(null, '楽天カテゴリリスト取得', '503エラーのため前回データを利用'));
      return;
    // 503以外のエラーならばエラー終了
    } else if ($response->getStatus() != 200 || !strlen($response->getContent())) {
      $logger->error('楽天カテゴリリスト' . self::RAKUTEN_CATEGORY_LIST_URL . 'への接続でエラーが発生しました。HTTP STATUS=' . $response->getStatus() . ', strlen=' . strlen($response->getContent()));
      if (!strlen($response->getContent())) {
        $logger->error('楽天カテゴリリスト取得エラー本文：' . strip_tags($response->getContent()));
      }
      throw new \RuntimeException('no rakuten category list. HTTP STATUS=' . $response->getStatus() . ", strlen=" . strlen($response->getContent()));
    }

    $dbMain = $this->getDb('main');
    $dbMain->query("TRUNCATE {$this->tableCategoryList}");

    $insertBuilder = new MultiInsertUtil($this->tableCategoryList, [
      'fields' => [
          'levels' => \PDO::PARAM_STR
        , 'cat1' => \PDO::PARAM_STR
        , 'cat2' => \PDO::PARAM_STR
        , 'cat3' => \PDO::PARAM_STR
        , 'cat4' => \PDO::PARAM_STR
        , 'cat5' => \PDO::PARAM_STR
        , 'cat_code'      => \PDO::PARAM_STR
        , '表示先カテゴリ' => \PDO::PARAM_STR
        , 'html'          => \PDO::PARAM_STR
      ]
    ]);

    $tables = $crawler->filter('td.sdtoptext') // td
                ->parents() // tr
                ->parents() // table ? tbody ?
                ->nextAll();

    $categoryTree = [
        1 => null
      , 2 => null
      , 3 => null
      , 4 => null
      , 5 => null
    ];
    $categoryCodeList = [];

    $tables->each(function($node) use (&$categoryTree, &$categoryCodeList, $insertBuilder, $shopStr, $baseUrl, $logger) {
      /** @var \Symfony\Component\DomCrawler\Crawler $node */

      // aタグがなければスキップ
      try {
        $node->filter('a')->text();

      } catch (InvalidArgumentException $e) {
        if ($e->getMessage() == 'The current node list is empty.') {
          return;
        }

        throw $e;
      }

      // カテゴリ階層を取得
      $level = $this->guessCategoryLevel($node);
      if (!$level) {
        // イレギュラー
        return;
      }

      // 自分より下位のカテゴリをクリア
      for ($i = $level + 1; $i <= 5; $i++) {
        $categoryTree[$i] = null;
      }
      $categoryName = trim($node->filter('a')->text());
      $categoryName = str_replace('〜', '～', $categoryName); // Goutteの変換では'〜'にしてしまうらしい
      $url = $node->filter('a')->attr('href');
      $categoryCode = preg_match('|https?://item.rakuten.co.jp/' . $shopStr . '/c/([a-zA-Z0-9_-]+)/|', $url, $m) ? $m[1]: null;
      $categoryTree[$level] = $categoryName;

      $category = [
          'levels' => $level
        , 'cat1' => $categoryTree[1]
        , 'cat2' => $categoryTree[2]
        , 'cat3' => $categoryTree[3]
        , 'cat4' => $categoryTree[4]
        , 'cat5' => $categoryTree[5]
        , 'cat_code' => $categoryCode
      ];

      // 表示先カテゴリ作成
      $disps = [];
      foreach(['cat1', 'cat2', 'cat3', 'cat4', 'cat5'] as $key) {
        if (!$category[$key]) {
          continue;
        }
        $disps[] = $category[$key];
      }
      $category['表示先カテゴリ'] = implode('\\', $disps);

      $categoryCodeList[$category['表示先カテゴリ']] = $categoryCode;

      // HTML作成
      $currentKey = '';
      $links = [];
      foreach($disps as $cate) {
        $currentKey .= ($currentKey ? ('\\' . $cate) : $cate);
        $currentCode = isset($categoryCodeList[$currentKey]) ? $categoryCodeList[$currentKey] : '';
        $links[] = sprintf('<a href="%s%s/?s=1&i=1#risFil">%s</a>', $baseUrl, $currentCode, $cate);
      }
      $category['html'] = implode('&nbsp;&gt;&nbsp;', $links) . '<br><hr>';

      $insertBuilder->bindRow($category);
    });

    if (count($insertBuilder->binds()) > 0) {
      $stmt = $dbMain->prepare($insertBuilder->toQuery());
      $insertBuilder->bindValues($stmt);
      $stmt->execute();
    }
  }

  /**
   * カテゴリ一覧 各カテゴリの階層取得
   * @param Crawler $node
   * @return int
   */
  private function guessCategoryLevel($node)
  {
    $level = 1;
    if (preg_match_all('/(>■<|>▼<|>├<|>│<|>└<)/', $node->html(), $m)) {
      $level = count($m[1]);
    }

    return $level - 1;
  }

  /**
   * 在庫差分確認テーブル 更新
   */
  public function updateRakutenProductStock()
  {
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    // 在庫差分確認テーブル 更新
    $logger->addDbLog($logger->makeDbLog(null, '在庫差分テーブル更新'));
    $sql = <<<EOD
        UPDATE tb_rakuten_product_stock s
        INNER JOIN tb_productchoiceitems pci ON s.product_code = pci.daihyo_syohin_code
                                            AND pci.colcode = s.colcode
                                            AND pci.rowcode = s.rowcode
        SET s.stock = pci.`フリー在庫数`
        WHERE s.stock <> pci.`フリー在庫数`
EOD;
    $dbMain->query($sql);
  }

  /**
   * 楽天 CSVダウンロード処理
   * ※ remoteDir にファイルが無くなるまで待ってダウンロードする。
   * @param array $config FTP接続設定
   * @param string $shopName 店舗名（ログ用）
   * @param \DateTime $baseDateTime ダウンロード基準日時 ファイル名の日付がこの日付より新しいデータを全て対象とする。
   * @param string $importDir インポートディレクトリ
   * @return array ダウンロードファイル一覧
   */
  public function downloadCsv($config, $shopName, $baseDateTime, $importDir)
  {
    $logger = $this->getLogger();
    $ftp = new SFTP($config['host']);

    try {
      $ftp->login($config['user'], $config['password']);
    } catch (\Exception $e) {
      $logger->debug("SFTP CONNECT FAILED");
      $message = '[' . $shopName . ']' . '楽天のCSVファイルダウンロード処理中、楽天のFTPにログインできませんでした。パスワードが変更されている場合は、Accessの「各種設定」から「RAKUTEN_GOLD_FTP_PASSWORD」を正しく更新してください。';
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    $logger->debug("SFTP CONNECT SUCCESS");
    $ftp->chdir($config['path']);

    // ダウンロードファイル一覧 絞込 (dl-item, dl-select, dl-item-cat)
    /** @var \DateTime[] $dateTimes */
    $dateTimes = [
        'item' => null
      , 'select' => null
      , 'item-cat' => null
    ];
    $files = [
        'item' => []
      , 'select' => []
      , 'item-cat' => []
    ];
    $allFiles = $ftp->nlist('./');

    // まず、利用するファイルの日付を確定
    foreach($allFiles as $file) {
      if (preg_match('/^(?:\.\/)?dl-(item|select|item-cat)(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(-\d+|-\d+-\d+).csv$/', $file, $m)) {
        $type = $m[1];
        $datetime = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:00', $m[2], $m[3], $m[4], $m[5], $m[6]));

        // 基準日時以前ならスキップ
        if ($datetime <= $baseDateTime) {
          continue;
        }

        if ($dateTimes[$type] < $datetime) {
          $dateTimes[$type] = $datetime;
        }
      }
    }

    // ファイル取得
    foreach($allFiles as $file) {
      if (preg_match('/^(?:\.\/)?dl-(item|select|item-cat)(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(-\d+|-\d+-\d+).csv$/', $file, $m)) {
        $type = $m[1];
        $datetime = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:00', $m[2], $m[3], $m[4], $m[5], $m[6]));
        if ($dateTimes[$type] == $datetime) {
          $files[$type][] = $file;

          // ダウンロード
          $localPath = sprintf('%s/%s', $importDir, preg_replace('|^\./|', '', $file));
          $ftp->get($file, $localPath);
        }
      }
    }

    return [
      'dateTimes' => $dateTimes,
      'files' => $files,
    ];
  }

  /**
   * 楽天 CSVアップロード処理
   * ※ remoteDir にファイルが無くなるまで待ってアップロードする。
   * @param array $config FTP接続設定
   * @param string $filePath アップロードファイルパス
   * @param string $remotePath リモートパス
   * @param bool $isGold 楽天Gold CSV処理出力かどうか
   */
  public function uploadCsv($config, $filePath, $remotePath, $isGold = false)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();

    $logTitle = 'CSVファイルアップロード';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, basename($filePath)));
    $logger->info('楽天 CSVファイルアップロード ' . $filePath);


    $ftp = null;
    if (array_key_exists('port', $config)) {
      if ($isGold) {
        $ftp = $container->get('ijanki_ftp');
        $ftp->connect($config['host'], $config['port']);
      } else {
        $ftp = new SFTP($config['host'], $config['port']);
      }
    } else {
      if ($isGold) {
        $ftp = $container->get('ijanki_ftp');
        $ftp->connect($config['host']);
      } else {
        $ftp = new SFTP($config['host']);
      }
    }

    try {
      $ftp->login($config['user'], $config['password']);
    } catch (\Exception $e) {
      $message = '楽天のCSVファイルアップロード処理中、楽天のFTPにログインできませんでした。パスワードが変更されている場合は、Accessの「各種設定」から「RAKUTEN_GOLD_FTP_PASSWORD」を正しく更新してください。';
      throw new \RuntimeException($message . "\n" . $e->getMessage());
    }
    if ($isGold) {
      $ftp->pasv(true);
    }
    $ftp->chdir($config['path']);

    // ディレクトリが空になるまで待つ(楽天Gold CSV処理出力時は待たない)
    if (!$isGold) {
      $limit = new \DateTime(); // 開始時刻
      $limit->modify('+ 6 hour'); // 6時間で諦める
      do {

        $fileList = $ftp->nlist('./');
        $remoteFileName = basename($remotePath);

        $logger->info('楽天CSV FTPアップロード FTP空き待ち: ' . print_r($fileList, true) . ' => ' . $remoteFileName);

        if (!is_array($fileList)) {
          $message = '楽天のCSVファイルアップロード処理中、楽天のFTPのファイル一覧が取得できませんでした。';
          throw new \RuntimeException($message);
        }

        $isExists = false;
        foreach($fileList as $file) {
          if (basename($file) == $remoteFileName) {
            $isExists = true;
            break;
          }
        }
        if (!$isExists) {
          break;
        }

        $logger->info('楽天CSV FTPアップロード FTP空き待ち: ' . print_r($fileList, true));
        sleep(5); // 5秒待つ

        if ($limit < new \DateTime()) {
          $message = '楽天のCSVファイルアップロード処理中、楽天のFTPが空にならずアップロードできませんでした。処理を中止します。';
          throw new \RuntimeException($message);
        }

      } while(count($fileList)) ;
    }

    if ($isGold) {
      $ftp->put($remotePath, $filePath, FTP_BINARY);
      $ftp->close();
    } else {
      $fp = fopen($filePath, 'r');
      $content = stream_get_contents($fp);
      rewind($fp);
      $ftp->put($remotePath, $content, SFTP::SOURCE_STRING);
      fclose($fp);
    }
  }

  /**
   * 楽天 CSVアップロード キュー追加処理
   * @param $filePath
   * @param $remoteFileName
   * @param $targetEnv
   * @param $targetShop
   * @param string $execTitle
   * @param integer $accountId
   * @param bool $isGold
   */
  public function enqueueUploadCsv($filePath, $remoteFileName, $targetEnv, $targetShop, $execTitle = '', $accountId = null, $isGold = false)
  {
    $container = $this->getContainer();

    $logger = $this->getLogger();
    $logger->info(sprintf('enqueue rakuten csv upload : %s / %s / %s / %s', $filePath, $remoteFileName, $targetEnv, $targetShop));

    /** @var \BCC\ResqueBundle\Resque $resque */
    $resque = $container->get('bcc_resque.resque');

    $job = new RakutenCsvUploadJob();
    $job->queue = 'rakutenCsvUpload'; // キュー名
    $job->args = [
        'filePath' => $filePath
      , 'remoteFileName' => $remoteFileName
      , 'targetEnv' => $targetEnv
      , 'execTitle' => $execTitle
      , 'target' => $targetShop
      , 'isGold' => $isGold
    ];
    if (!is_null($accountId)) {
      $job->args['account'] = $accountId;
    }

    $resque->enqueue($job); // リトライなし
  }

  /**
   * 楽天レビューについて、レビュー詳細画面のURLを受け取り、代表商品コードを取得する。
   *
   * レビュー詳細画面の、商品リンク部分のURLから代表商品コードを取得する。
   * @param unknown $logger
   * @param unknown $reviewUrl レビュー詳細画面のURL
   * @param string $shopName 店舗名
   * @throws RuntimeException レビュー画面が表示できなかった、または商品詳細画面のURLが取得できなかった場合にthrowする
   */
  public function getDaihyoSyohinCodeFromProductReview($logger, $reviewUrl, $shopName = 'plusnao') {
    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();

    $client = $webAccessUtil->getWebClient();
    sleep(1); // サーバ負荷調整のため1秒待つ
    $crawler = $client->request('GET',$reviewUrl);
    $response = $client->getResponse();
    $status = $response->getStatus();

    // 商品が倉庫に入っており、商品ページに遷移できない場合と、レビュー自体が削除されている場合がある
    // ここの例外メッセージで呼び出し元処理を分岐するため、例外に設定しているメッセージを変更しない事

    // レビュー画面に接続できない
    if ($status !== 200) {
      throw new \RuntimeException("can't access review page. status[$status], url[$reviewUrl]");
    }

    // レビューが削除されている
    try {
      $revNotFoundMessage = $crawler->filter('p.revNotFound');
      if ($revNotFoundMessage && $revNotFoundMessage->text() == '対象のレビューが見つかりませんでした。') {
        throw new \RuntimeException("deleted review data");
      }
    } catch (\InvalidArgumentException $e) { // NotFoundでなかった
      // do nothing
    }

    // レビューが存在する場合、代表商品コードの取得にトライ
    try {
      $url = $crawler->filter('h2.revItemTtl a')->attr('href'); // 商品名のリンク先が商品詳細ページで、URLから代表商品コードが取得できる
      preg_match('/https:\/\/item.rakuten.co.jp\/' . $shopName .'\/(.*)\//', $url, $matches);
      if (count($matches) != 2) {
        throw new \RuntimeException("can't get item page url. url:[$url]"); // リンクURLから代表商品コードが取れない場合
      }
      return $matches[1];
    } catch (\InvalidArgumentException $e) { // そもそも商品名リンクが存在しない
      throw new \RuntimeException("can't get item page url. reviewUrl:[$reviewUrl]");
    }
  }

  /**
   * RPPフラグを有効とする代表商品コードのリストをパラメータとして取得し、指定されたものを有効、含まれていなかったものを無効とする。
   * 存在しないものが含まれていた場合は例外を throwする。
   *
   * @param array $targetList RPPフラグを有効とする代表商品コードのリスト。
   * @param $dbMain DbMainのコネクション。このメソッド内ではトランザクション管理を行わないので、呼び出し元で管理する事。
   */
  public function updateRppFlg(array $targetList, $dbMain) {

    // まず全件OFF（RPP除外）に
    $sql = <<<EOD
      UPDATE tb_rakuteninformation SET rpp_flg = 0 WHERE rpp_flg = 1
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':warehouse_stored_flg', 0, \PDO::PARAM_INT);
    $stmt->execute();

    // 次に指定されたものを有効化
    $sql = <<<EOD
      UPDATE tb_rakuteninformation SET rpp_flg = 1 WHERE daihyo_syohin_code = :daihyo_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    foreach ($targetList as $daihyoSyohinCode) {
      if (trim($daihyoSyohinCode) != '') {
        $stmt->bindValue(':daihyo_syohin_code', trim($daihyoSyohinCode));
        $stmt->execute();
        $cnt = $stmt->rowCount();
        if ($cnt == 0) { // 更新対象がなければエラー
          throw new \RuntimeException("指定された代表商品コード [ $daihyoSyohinCode ] は存在しません");
        }
      }
    }
  }
}
