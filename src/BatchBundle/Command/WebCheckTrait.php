<?php

namespace BatchBundle\Command;

use MiscBundle\Entity\Repository\TbVendoraddressRepository;
use MiscBundle\Util\FileLogger;
use MiscBundle\Util\MultiInsertUtil;

trait WebCheckTrait
{
  use CommandBaseTrait;

  /** @var  TbVendoraddressRepository */
  protected $tbVendoraddressRepo;

  /** @var  FileLogger */
  protected $fileLogger;

  /**
   * @return TbVendoraddressRepository
   */
  protected function getVendorAddressRepo()
  {
    if (!isset($this->tbVendoraddressRepo)) {
      $this->tbVendoraddressRepo = $this->getDoctrine()->getRepository('MiscBundle:TbVendoraddress');
    }

    return $this->tbVendoraddressRepo;
  }

  protected function initFileLogger($name)
  {
    if (!isset($this->fileLogger)) {
      /** @var FileLogger $fileLogger */
      $this->fileLogger = $this->getContainer()->get('misc.util.file_logger');
      $this->fileLogger->setFileName(str_replace(['\\', '/', ' '], '_', $name));
    }
  }

  /**
   * @return FileLogger
   */
  protected function getFileLogger()
  {
    if (!isset($this->fileLogger)) {
      $this->initFileLogger(get_class($this));
    }
    return $this->fileLogger;
  }

  /**
   * tb_vendoraddress 更新用 一時テーブル作成
   */
  protected function createTmpVendoraddressTable()
  {
    $dbMain = $this->getDb('main');

    /** @noinspection SqlNoDataSourceInspection */
    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_vendoraddress_update");

    $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_vendoraddress_update (
             `sire_adress` VARCHAR(200) NOT NULL PRIMARY KEY
           , `setafter` INT(10) UNSIGNED NOT NULL DEFAULT 0
           , `price` int(11) NOT NULL DEFAULT 99999
           , `original_title` VARCHAR(255) NOT NULL DEFAULT ''
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbMain->query($sql);

    /** @noinspection SqlNoDataSourceInspection */
    $dbMain->query("DROP TEMPORARY TABLE IF EXISTS tmp_netsea_vendoraddress_update");

    $sql = <<<EOD
        CREATE TEMPORARY TABLE tmp_netsea_vendoraddress_update (
              `netsea_vendoraddress` VARCHAR(255) NOT NULL PRIMARY KEY
            , `netsea_vendor_code` VARCHAR(255) NOT NULL DEFAULT ''
            , `netsea_title` VARCHAR(255) NOT NULL DEFAULT ''
            , `netsea_price` INT(10) UNSIGNED NOT NULL DEFAULT 0
            , `netsea_set_count` INT(10) UNSIGNED NOT NULL DEFAULT 0
            , `last_check` TINYINT(1) NOT NULL DEFAULT '0'
            , `ranking` INT(10) UNSIGNED NOT NULL DEFAULT '0'
            , `display_order` INT(10) UNSIGNED NOT NULL DEFAULT '0'
            , `sire_code` VARCHAR(10) NOT NULL DEFAULT ''
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
EOD;
    $dbMain->query($sql);
  }

  /**
   * tb_netsea_vendoraddress 前処理
   * @param string $sireCode
   * @param string $sireAddressKeyword
   * @throws \Doctrine\DBAL\DBALException
   */
  protected function webCheckPreProcess($sireCode, $sireAddressKeyword = '')
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    // 'crawl_frequency=-1 削除。巡回対象外は常に削除する
    $addConditionAddressKeyword= "";
    if (strlen($sireAddressKeyword)) {
      $addConditionAddressKeyword = " AND netsea_vendoraddress LIKE :keyword ";
    }

    $sql = <<<EOD
        DELETE v
        FROM tb_netsea_vendoraddress AS v
        LEFT JOIN tb_vendormasterdata AS m ON v.sire_code = m.sire_code
        WHERE m.sire_code IS NULL
           OR (
             m.crawl_frequency = -1
             {$addConditionAddressKeyword}
           )
EOD;
    $stmt = $dbMain->prepare($sql);
    if (strlen($sireAddressKeyword)) {
      $keyword = $commonUtil->escapeLikeString($sireAddressKeyword);
      $stmt->bindValue(':keyword', '%' . $keyword . '%');
    }
    $stmt->execute();

    // 巡回対象アドレスの last_check を一括更新
    // TODO 巡回先を減らした場合、ここでもっと絞る必要がある？
    $sql = <<<EOD
      UPDATE tb_netsea_vendoraddress
      SET last_check = -1
      WHERE sire_code = :sireCode
EOD;
    // netsea, superdelivery, 1688.com
    if (strlen($sireAddressKeyword)) {
      $sql .= " AND netsea_vendoraddress LIKE :keyword ";
    }

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':sireCode', $sireCode);

    if (strlen($sireAddressKeyword)) {
      $keyword = $commonUtil->escapeLikeString($sireAddressKeyword);
      $stmt->bindValue(':keyword', '%' . $keyword . '%');
    }

    $stmt->execute();
  }

  /**
   * tb_netsea_vendoraddress, tb_vendoraddress 後処理
   * * 巡回しなかった tb_netsea_vendoraddress レコードを削除
   * * tb_netsea_vendoraddress レコードが存在しない tb_vendoraddress を soldout に更新（404 などで漏れた）
   * @param string $sireCode
   * @param string $sireAddressKeyword
   */
  protected function webCheckPostProcess($sireCode, $sireAddressKeyword = '')
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    // '不要行を削除 （last_check = -1 → 巡回対象中に商品ページが存在しなかった）
    $sql = <<<EOD
      DELETE v
      FROM tb_netsea_vendoraddress AS v
      INNER JOIN tb_vendormasterdata AS m ON v.sire_code = m.sire_code
      WHERE v.sire_code = :sireCode
        AND v.last_check = -1
EOD;
    // netsea, superdelivery
    if (strlen($sireAddressKeyword)) {
      $sql .= " AND v.netsea_vendoraddress LIKE :keyword ";
    }

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':sireCode', $sireCode);

    if (strlen($sireAddressKeyword)) {
      $keyword = $commonUtil->escapeLikeString($sireAddressKeyword);
      $stmt->bindValue(':keyword', '%' . $keyword . '%');
    }

    $stmt->execute();

    // soldout更新（巡回が済んで tb_netsea_vendoraddress にレコードが無い ＝ 仕入れサイトに掲載なし）
    // こちらは
    $sql = <<<EOD
      UPDATE tb_vendoraddress va
      LEFT JOIN  tb_netsea_vendoraddress AS na ON va.sire_adress = na.netsea_vendoraddress
      SET va.checkdate = NOW()
        , va.soldout = 1
        , va.setafter = 0
        , va.soldout_checkdate = NOW()
      WHERE va.sire_code = :sireCode
        AND (
            va.soldout = 0
         OR va.soldout_checkdate IS NULL
        )
        AND (
             na.netsea_vendoraddress IS NULL
          OR va.setafter = 0 /* セット数がないものも soldout 扱いにして、復活確認送りとする */
        )
EOD;
    // netsea, superdelivery
    if (strlen($sireAddressKeyword)) {
      $sql .= " AND na.netsea_vendoraddress LIKE :keyword ";
    }

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':sireCode', $sireCode);

    if (strlen($sireAddressKeyword)) {
      $keyword = $commonUtil->escapeLikeString($sireAddressKeyword);
      $stmt->bindValue(':keyword', '%' . $keyword . '%');
    }

    $stmt->execute();
  }



  /**
   * tb_vendoraddress テーブル 更新
   * ※存在するもののみ更新
   * @param array $pageItems
   * @throws \Doctrine\DBAL\DBALException
   */
  protected function updateSetAfter($pageItems)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $dbMain->query('TRUNCATE tmp_vendoraddress_update');

    // 一時テーブルに一括INSERT
    /** @noinspection SqlNoDataSourceInspection */
    $insertBuilder = new MultiInsertUtil("tmp_vendoraddress_update", [
      'fields' => [
          'sire_adress' => \PDO::PARAM_STR
        , 'setafter'    => \PDO::PARAM_INT
        , 'price'       => \PDO::PARAM_INT
        , 'original_title' => \PDO::PARAM_STR
      ]
      , 'prefix' => "INSERT IGNORE INTO "
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $pageItems, function($item) {

      $originalTitle = isset($item['title']) ? $item['title'] : '';
      if (isset($item['original_title']) && strlen($item['original_title'])) {
        $originalTitle = $item['original_title'];
      }

      return [
          'sire_adress'     => $item['url']
        , 'setafter'        => $item['setNum']
        , 'price'           => $item['price']
        , 'original_title'  => $originalTitle
      ];
    }, 'foreach');

    // JOINして一括更新
    // TODO soldoutをここで設定するのではなく、AccessのWeb Checker で行うべきか。（ここでは反映確認で破棄した場合に戻せない。）
    $sql = <<<EOD
      UPDATE tb_vendoraddress a
      INNER JOIN tmp_vendoraddress_update t ON a.sire_adress = t.sire_adress
      SET a.setafter  = t.setafter
        , a.price     = t.price
        , a.original_title = CASE
                               WHEN a.original_title = "" THEN t.original_title
                               ELSE a.original_title
                             END
        , a.checkdate = NOW()
        , a.soldout   = CASE WHEN t.setafter = 0 THEN 1 ELSE 0 END
        , a.soldout_checkdate = CASE WHEN t.setafter = 0 THEN NOW() ELSE a.soldout_checkdate END
EOD;
    $dbMain->query($sql);
  }

  /**
   *
   */
  protected function insertOrUpdateNetseaVendorAddress($vendor, $pageItems)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $dbMain->query('TRUNCATE tmp_netsea_vendoraddress_update');

    // 一時テーブルに一括INSERT
    /** @noinspection SqlNoDataSourceInspection */
    $insertBuilder = new MultiInsertUtil("tmp_netsea_vendoraddress_update", [
      'fields' => [
          'netsea_vendoraddress' => \PDO::PARAM_STR
        , 'netsea_vendor_code'   => \PDO::PARAM_STR
        , 'netsea_title'         => \PDO::PARAM_STR
        , 'netsea_price'         => \PDO::PARAM_INT
        , 'netsea_set_count'     => \PDO::PARAM_INT
        , 'last_check'           => \PDO::PARAM_INT
        , 'ranking'              => \PDO::PARAM_INT
        , 'display_order'        => \PDO::PARAM_INT
        , 'sire_code'            => \PDO::PARAM_STR
      ]
      , 'prefix' => "INSERT IGNORE INTO "
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $pageItems, function($item) use ($vendor) {

      $row = [
          'netsea_vendoraddress' => $item['url']
        , 'netsea_vendor_code'   => $vendor['sire_name']
        , 'netsea_title'         => $item['title']
        , 'netsea_price'         => $item['price']
        , 'netsea_set_count'     => $item['setNum']
        , 'last_check'           => 0
        , 'ranking'              => $item['ranking']
        , 'display_order'        => $vendor['表示順']
        , 'sire_code'            => $vendor['sire_code']
      ];

      return $row;

    }, 'foreach');

    // JOINして一括挿入or更新
    // 非巡回仕入先は、新規挿入はしない（Vivica, AKF用実装）
    $addJoin = '';
    if (isset($vendor['crawl_frequency']) && $vendor['crawl_frequency'] == -1) {
      $addJoin = <<<EOD
        INNER JOIN tb_vendoraddress va ON t.netsea_vendoraddress = va.sire_adress
EOD;
    }

    $sql = <<<EOD
      INSERT INTO tb_netsea_vendoraddress (
          netsea_vendoraddress
        , netsea_vendor_code
        , netsea_title
        , netsea_price
        , netsea_set_count
        , last_check
        , ranking
        , display_order
        , sire_code
      )
      SELECT
          t.netsea_vendoraddress
        , t.netsea_vendor_code
        , t.netsea_title
        , t.netsea_price
        , t.netsea_set_count
        , t.last_check
        , t.ranking
        , t.display_order
        , t.sire_code
      FROM tmp_netsea_vendoraddress_update t
      {$addJoin}
      ON DUPLICATE KEY UPDATE
            netsea_vendor_code = t.netsea_vendor_code
          , netsea_title = t.netsea_title
          , netsea_price = t.netsea_price
          , netsea_set_count = t.netsea_set_count
          , last_check = t.last_check
          , ranking = t.ranking
          , display_order = t.display_order
          , sire_code = t.sire_code
EOD;
    $dbMain->query($sql);

  }

  /**
   * tb_netsea_vendoraddress セット数のみ更新（在庫確認巡回用）
   * @param $pageItems
   */
  protected function updateNetseaVendorAddressSetNum($pageItems)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $dbMain->query('TRUNCATE tmp_netsea_vendoraddress_update');

    // 一時テーブルに一括INSERT
    /** @noinspection SqlNoDataSourceInspection */
    $insertBuilder = new MultiInsertUtil("tmp_netsea_vendoraddress_update", [
      'fields' => [
          'netsea_vendoraddress' => \PDO::PARAM_STR
        , 'netsea_set_count'     => \PDO::PARAM_INT
        , 'last_check'           => \PDO::PARAM_INT
      ]
      , 'prefix' => "INSERT IGNORE INTO "
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $pageItems, function($item) {
      return [
          'netsea_vendoraddress' => $item['url']
        , 'netsea_set_count'     => $item['setNum']
        , 'last_check'           => 0
      ];
    }, 'foreach');

    // JOINして一括更新
    $sql = <<<EOD
      UPDATE tb_netsea_vendoraddress na
      INNER JOIN tmp_netsea_vendoraddress_update t ON na.netsea_vendoraddress = t.netsea_vendoraddress
      SET na.netsea_set_count  = t.netsea_set_count
        , na.last_check        = t.last_check
EOD;
    $dbMain->query($sql);
  }


  /**
   * 在庫確認巡回 対象レコード取得
   *
   * [対象レコード (NETSEA, SUPER DELIVERY)]
   *
   *  * checkdate < 最終新商品巡回開始日時 （ tb_updaterecord テーブル記録 ）
   *    => これにより、新商品巡回でチェックされたアドレスは除外される
   *
   *  かつ 下記のいずれか
   *
   *  * soldout = 0
   *    => soldout 判定されていないものは毎回チェック
   *
   *  * soldout = 1 かつ soldout_checkdate > 7日前
   *    => 1週間以内に soldout 判定されたアドレスは毎回チェック
   *
   *  * soldout = 1 かつ soldout_checkdate > 30日前 の 古い方から 最低1,000件 ～ 全件数の 1/7
   *    => 1か月以内に soldout 判定されたアドレスは 最大1週間程度後にはチェックされる
   *
   *  * soldout = 1 かつ soldout_checkdate > 365日前 の 古い方から 最低1,000件 ～ 全件数の 1/30
   *    => 1年以内に soldout 判定されたアドレスは 最大1か月程度後にはチェックされる
   *
   * ※ 1年以上前に soldout 判定されたアドレスはチェックしない。
   *
   * @param \DateTime $lastCheckDate
   * @param string $targetSite 'netsea', 'superdelivery'
   * @param FileLogger $fileLogger
   * @return \Doctrine\DBAL\Statement
   */
  protected function getCheckProductStockAddresses($lastCheckDate, $targetSite, $fileLogger = null)
  {
    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    $siteKeyword = sprintf('%%%s%%', $commonUtil->escapeLikeString($targetSite));

    // 対象件数を条件ごとに取得(soldout判定 8～30日以内, 31日～365日以内)
    $sql = <<<EOD
      SELECT
         COUNT(*) AS num
      FROM tb_vendoraddress va
      INNER JOIN tb_vendormasterdata m ON va.sire_code = m.sire_code
      WHERE sire_adress LIKE :siteKeyword
        AND ( checkdate IS NULL OR checkdate < :lastCheckDate)
        AND (
             soldout = 0
          OR soldout_checkdate > DATE_ADD(CURRENT_DATE, INTERVAL -7 DAY)
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':siteKeyword', $siteKeyword);
    $stmt->bindValue(':lastCheckDate', $lastCheckDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $numActive = $stmt->fetchColumn(0);

    $sql = <<<EOD
      SELECT
         COUNT(*) AS num
      FROM tb_vendoraddress va
      INNER JOIN tb_vendormasterdata m ON va.sire_code = m.sire_code
      WHERE sire_adress LIKE :siteKeyword
        AND soldout <> 0
        AND ( checkdate IS NULL OR checkdate < :lastCheckDate)
        AND soldout_checkdate < DATE_ADD(CURRENT_DATE, INTERVAL -7 DAY)
        AND soldout_checkdate >= DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':siteKeyword', $siteKeyword);
    $stmt->bindValue(':lastCheckDate', $lastCheckDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $numWeekly = ceil($stmt->fetchColumn(0) / 7);
    if ($numWeekly < 1000) { // 最低チェック数
      $numWeekly = 1000;
    }

    $sql = <<<EOD
      SELECT
         COUNT(*) AS num
      FROM tb_vendoraddress va
      INNER JOIN tb_vendormasterdata m ON va.sire_code = m.sire_code
      WHERE sire_adress LIKE :siteKeyword
        AND soldout <> 0
        AND ( checkdate IS NULL OR checkdate < :lastCheckDate)
        AND soldout_checkdate < DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY)
        AND soldout_checkdate >= DATE_ADD(CURRENT_DATE, INTERVAL -365 DAY)
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':siteKeyword', $siteKeyword);
    $stmt->bindValue(':lastCheckDate', $lastCheckDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->execute();
    $numMonthly = ceil($stmt->fetchColumn(0) / 365);
    if ($numMonthly < 1000) { // 最低チェック数
      $numMonthly = 1000;
    }

    // 対象レコード取得
    $sql = <<<EOD
      SELECT
          sire_code
        , sire_adress
        , price
        , checkdate
      FROM (
        (
          SELECT
              va.sire_code
            , va.sire_adress
            , va.price
            , va.checkdate
          FROM tb_vendoraddress va
          INNER JOIN tb_vendormasterdata m ON va.sire_code = m.sire_code
          WHERE sire_adress LIKE :siteKeyword
            AND ( checkdate IS NULL OR checkdate < :lastCheckDate)
            AND (
                 soldout = 0
              OR soldout_checkdate > DATE_ADD(CURRENT_DATE, INTERVAL -7 DAY)
            )
        )
        UNION (
          SELECT
              va.sire_code
            , va.sire_adress
            , va.price
            , va.checkdate
          FROM tb_vendoraddress va
          INNER JOIN tb_vendormasterdata m ON va.sire_code = m.sire_code
          WHERE sire_adress LIKE :siteKeyword
            AND soldout <> 0
            AND ( checkdate IS NULL OR checkdate < :lastCheckDate)
            AND soldout_checkdate < DATE_ADD(CURRENT_DATE, INTERVAL -7 DAY)
            AND soldout_checkdate >= DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY)
          ORDER BY checkdate
          LIMIT :numWeekly
        )
        UNION (
           SELECT
               va.sire_code
             , va.sire_adress
             , va.price
             , va.checkdate
           FROM tb_vendoraddress va
           INNER JOIN tb_vendormasterdata m ON va.sire_code = m.sire_code
          WHERE sire_adress LIKE :siteKeyword
             AND soldout <> 0
             AND ( checkdate IS NULL OR checkdate < :lastCheckDate)
             AND soldout_checkdate < DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY)
             AND soldout_checkdate >= DATE_ADD(CURRENT_DATE, INTERVAL -365 DAY)
           ORDER BY checkdate
          LIMIT :numMonthly
        )
      ) T
      ORDER BY T.checkdate
EOD;

    if ($fileLogger) {
      $fileLogger->info(sprintf('件数: 販売中～7日: %d, 8～30日: %d, 31～365日: %d', $numActive, $numWeekly, $numMonthly));
    }

    $stmtAddress = $dbMain->prepare($sql);
    $stmtAddress->bindValue(':siteKeyword', $siteKeyword);
    $stmtAddress->bindValue(':lastCheckDate', $lastCheckDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmtAddress->bindValue(':numWeekly', $numWeekly, \PDO::PARAM_INT);
    $stmtAddress->bindValue(':numMonthly', $numMonthly, \PDO::PARAM_INT);
    $stmtAddress->execute();

    return $stmtAddress;
  }

}
