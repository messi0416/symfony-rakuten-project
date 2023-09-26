<?php

namespace MiscBundle\Entity\Repository;
use BatchBundle\Exception\LeveledException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\ProductImagesAmazon;
use MiscBundle\Entity\ProductImagesVariation;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbProductSalesAccount;
use MiscBundle\Entity\TbRakuteninformation;
use MiscBundle\Entity\TbWarehouse;
use MiscBundle\Entity\TmpProductImages;
use MiscBundle\Entity\Repository\TbProductSalesAccountRepository;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\ImageUtil;
use MiscBundle\Entity\TbShippingdivision;

/**
 */
class TbMainproductsRepository extends BaseRepository
{
  /**
   * 画像URL作成処理
   * @param string $imageDir
   * @param string $imageName
   * @param string $parentPath
   * @return null|string
   */
  public static function createImageUrl($imageDir, $imageName, $parentPath = '')
  {
    if (!$imageDir || !$imageName) {
      return null;
    }

    $url = sprintf('/%s/%s', $imageDir, $imageName);
    if ($parentPath) {
      $parentPath = preg_replace('|/$|', '', $parentPath);
      $url = sprintf('%s%s', $parentPath, $url);
    }

    return $url;
  }

  /**
   * 楽天 商品詳細URL取得
   * @param $daihyoSyohinCode
   * @return string 楽天詳細ページURL
   */
  public static function getRakutenDetailUrl($daihyoSyohinCode)
  {
    return sprintf('http://item.rakuten.co.jp/plusnao/%s', strtolower($daihyoSyohinCode));
  }



  /**
   * 全商品件数取得
   * ※ 仮登録以外の全件数。
   */
  public function getAllProductCount()
  {
    $db = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
        COUNT(*) AS cnt
      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE cal.deliverycode <> :deliveryCodeTemporary
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    $result = intval($stmt->fetchColumn(0));
    return $result;
  }

  /**
   * 商品コードで検索
   * @param array $conditions
   * @param string $likeMode
   * @param int $limit
   * @return array
   */
  public function searchByDaihyoSyohinCode($conditions, $likeMode = 'forward', $limit = 200)
  {
    $keyword = isset($conditions['keyword']) ? $conditions['keyword'] : null;
    $searchNoStockProduct = isset($conditions['include_no_stock_product']) ? boolval($conditions['include_no_stock_product']) : false;

    if (!strlen($keyword)) {
      return [];
    }

    $dbMain = $this->getConnection('main');

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');

    switch ($likeMode) {
      // 完全一致
      case 'equal':
        break;

      // 部分一致
      case 'part':
        $keyword = $commonUtil->escapeLikeString($keyword);
        $keyword = sprintf('%%%s%%', $keyword);
        break;

      // 前方一致
      case 'forward':
        $keyword = $commonUtil->escapeLikeString($keyword);
        $keyword = sprintf('%s%%', $keyword);
        break;
      default:
        throw new \InvalidArgumentException('invalid like mode');
    }

    $params = [];

    $addWhere = "";
    if (! $searchNoStockProduct) {
      $addWhere .= " AND T.stock_total > 0 ";
    }

    $sql = <<<EOD
      SELECT
          m.daihyo_syohin_code
        , m.picfolderP1 AS image_p1_directory
        , m.picnameP1   AS image_p1_filename
        , T.stock_total
      FROM tb_mainproducts m
      INNER JOIN (
        SELECT
            pci.daihyo_syohin_code
          , SUM(pci.`在庫数`) AS stock_total
        FROM tb_productchoiceitems pci
        WHERE pci.daihyo_syohin_code LIKE :keyword
        GROUP BY pci.daihyo_syohin_code
      ) T ON m.daihyo_syohin_code = T.daihyo_syohin_code
      WHERE m.daihyo_syohin_code LIKE :keyword
        {$addWhere}
      ORDER BY m.daihyo_syohin_code
EOD;
    $params[':keyword'] = $keyword;

    $stmt = $dbMain->prepare($sql);

    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();

    $results = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $results[] = $row;
    }

    return $results;
  }

  /**
   * 重量・メール便枚数・寸法未設定商品 取得
   * ※配送方法：宅配便別 以外
   * @param TbWarehouse $currentWarehouse
   * @param array $conditions
   * @param array $orders
   * @param null $limit
   * @param int $page
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function searchMissingWeightList($currentWarehouse, $conditions = [], $orders = [], $limit = null, $page = 1)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $addSqls = [];
    $addSql = "";
    if (strlen($conditions['delivery_method'])) {
      $shippingdivision_cond = implode(',', array(
          TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET,
          TbShippingdivision::SHIPPING_GROUP_CODE_NEKOPOSU,
          TbShippingdivision::SHIPPING_GROUP_CODE_CLICKPOST)
      );

      if ($conditions['delivery_method'] == 1) { // ゆうパケット・クリックポスト・ネコポス
        $addSqls[] = " ( sd.shipping_group_code IN ($shippingdivision_cond) ) ";
      } else { // 上記以外
        $addSqls[] = " ( sd.shipping_group_code NOT IN ($shippingdivision_cond) ) ";
      }
    }

    $addSqls[] = " ( DStock.stock > 0 ) ";

    if ($addSqls) {
      $addSql = " AND ( " . implode(' AND ', $addSqls) . " ) ";
    }

    $sqlSelect = <<<EOD
      SELECT
          pci.ne_syohin_syohin_code
        , pci.daihyo_syohin_code
        , COALESCE(T.受注数合計, 0) AS order_num
        , I.directory AS image_directory
        , I.filename AS image_filename
        , LOC.first_location AS first_location
        , DStock.stock
EOD;

		if (!$conditions['stock_only']){
			$DecideWarehouse = " WHERE pl.stock > 0 ";
		}else{
			$DecideWarehouse = " WHERE pl.stock > 0 AND l.warehouse_id = :currentWarehouseId ";
		}

    $sqlBody = <<<EOD
      FROM tb_productchoiceitems pci
      INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_shippingdivision sd ON m.送料設定 = sd.id
      LEFT JOIN (
        SELECT
            a.daihyo_syohin_code
          , SUM(a.`受注数`) AS 受注数合計
        FROM tb_sales_daily_product a
        WHERE a.`受注日` >= DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY)
        GROUP BY a.daihyo_syohin_code
      ) T ON m.daihyo_syohin_code = T.daihyo_syohin_code
      LEFT JOIN (
        SELECT
            i.daihyo_syohin_code
          , i.`directory`
          , i.filename
        FROM product_images i
        WHERE i.code = 'p001'
      ) I ON pci.daihyo_syohin_code = I.daihyo_syohin_code
      LEFT JOIN (
        SELECT
            ne_syohin_syohin_code
          , SUBSTRING_INDEX(GROUP_CONCAT(l.location_code ORDER BY pl.position SEPARATOR ','), ',', 1) AS first_location
        FROM tb_product_location pl
        INNER JOIN tb_location l ON pl.location_id = l.id
        $DecideWarehouse
        GROUP BY pl.ne_syohin_syohin_code
      ) AS LOC ON pci.ne_syohin_syohin_code = LOC.ne_syohin_syohin_code
      LEFT JOIN (
        SELECT
            ne_syohin_syohin_code
          , SUM(pl.stock) AS stock
        FROM tb_product_location pl
        INNER JOIN tb_location l ON pl.location_id = l.id
        $DecideWarehouse
        GROUP BY pl.ne_syohin_syohin_code
      ) AS DStock ON pci.ne_syohin_syohin_code = DStock.ne_syohin_syohin_code
      WHERE 
        (
          (
                pci.weight = 0
             OR pci.depth = 0
             OR pci.width = 0
             OR pci.height = 0
          )
        )
        {$addSql}
EOD;
    $rsm = new ResultSetMappingBuilder($em);
    $rsm->addScalarResult('daihyo_syohin_code', 'daihyo_syohin_code', 'string');
    $rsm->addScalarResult('ne_syohin_syohin_code', 'ne_syohin_syohin_code', 'string');
    $rsm->addScalarResult('order_num', 'order_num', 'integer');

    $rsm->addScalarResult('image_directory', 'image_directory', 'string');
    $rsm->addScalarResult('image_filename', 'image_filename', 'string');
    $rsm->addScalarResult('first_location', 'first_location', 'string');
    $rsm->addScalarResult('stock', 'stock', 'integer');

		$query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);

    $query->setOrders([
        'order_num' => 'DESC'
      , 'm.daihyo_syohin_code' => 'DESC'
      , 'stock' => 'DESC'
      , 'm.販売開始日' => 'DESC'
      , 'm.登録日時' => 'DESC'
    ]);

    $query->setParameter(':currentWarehouseId', $currentWarehouse->getId(), \PDO::PARAM_INT);
    $query->setParameter(':defaultWarehouseId', TbWarehouseRepository::DEFAULT_WAREHOUSE_ID, \PDO::PARAM_INT);

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $query /* query NOT result */
      , $page
      , $limit
    );

    return $pagination;
  }


  /**
   * Amazonメイン画像・カラー画像 未登録商品 取得
   * ・Amazon出品フラグON
   * ・在庫あり
   * ・ホワイト or グレー
   */
  public function searchMissingAmazonMainImageList($conditions = [], $orders = [], $limit = null, $page = 1)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $conditionParams = [];

    $sqlSelect = <<<EOD
      SELECT
         m.*
       , COALESCE(T.受注数合計, 0) AS 一ヶ月受注数
EOD;
    $sqlBody = <<<EOD
      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_amazoninfomation i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      LEFT JOIN product_images_amazon a ON m.daihyo_syohin_code = a.daihyo_syohin_code
      LEFT JOIN (
        SELECT
            s.daihyo_syohin_code
          , SUM(s.`受注数`) AS 受注数合計
        FROM tb_sales_daily_product s
        WHERE s.`受注日` >= DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY)
        GROUP BY s.daihyo_syohin_code
      ) T ON m.daihyo_syohin_code = T.daihyo_syohin_code
      LEFT JOIN (
        SELECT
          DISTINCT pci.daihyo_syohin_code
        FROM tb_productchoiceitems pci
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        LEFT JOIN product_images_variation v ON pci.daihyo_syohin_code = v.daihyo_syohin_code
                                            AND m.`カラー軸` = v.code
                                            AND (
                                                  m.`カラー軸` = 'row' AND pci.rowcode = v.variation_code
                                               OR m.`カラー軸` = 'col' AND pci.colcode = v.variation_code
                                            )
        WHERE pci.`フリー在庫数` > 0
          AND v.variation_code IS NULL
      ) VI ON m.daihyo_syohin_code = VI.daihyo_syohin_code
      WHERE i.registration_flg <> 0
        AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusNone)
        AND m.総在庫数 > 0
        AND (
             a.daihyo_syohin_code IS NULL /* メイン画像なし */
          OR VI.daihyo_syohin_code IS NOT NULL /* フリー在庫のあるSKUにカラー画像なし */
        )
EOD;

    if (isset($conditions['image_photo_need_flg']) && strlen($conditions['image_photo_need_flg'])) {
      $sqlBody .= " AND cal.image_photo_need_flg = :imagePhotoNeedFlg ";
      $conditionParams[':imagePhotoNeedFlg'] = boolval($conditions['image_photo_need_flg']) ? '-1' : '0';
    }

    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:TbMainproductsWithSalesOfMonth', 'm');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    $query->setOrders([
        'T.受注数合計' => 'DESC'
      , 'm.販売開始日' => 'DESC'
      , 'm.登録日時' => 'DESC'
      , 'm.daihyo_syohin_code' => 'ASC'
    ]);
    $query->setParameter(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
    $query->setParameter(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    if ($conditionParams) {
      foreach($conditionParams as $k => $v) {
        $query->setParameter($k, $v);
      }
    }

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
      $query /* query NOT result */
      , $page
      , $limit
    );

    return $pagination;
  }

  /**
   * 商品計測 一覧取得
   */
  public function searchSizeCheckList($conditions = [], $orders = [], $limit = null, $page = 1)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $conditionParams = [];

    $sqlSelect = <<<EOD
      SELECT
         m.*
       , COALESCE(T.受注数合計, 0) AS 一ヶ月受注数
EOD;
    $sqlBody = <<<EOD
      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      LEFT JOIN (
        SELECT
            s.daihyo_syohin_code
          , SUM(s.`受注数`) AS 受注数合計
        FROM tb_sales_daily_product s
        WHERE s.`受注日` >= DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY)
        GROUP BY s.daihyo_syohin_code
      ) T ON m.daihyo_syohin_code = T.daihyo_syohin_code
      WHERE cal.size_check_need_flg <> 0
EOD;
    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:TbMainproductsWithSalesOfMonth', 'm');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    $query->setOrders([
        'T.受注数合計' => 'DESC'
      , 'm.販売開始日' => 'DESC'
      , 'm.登録日時' => 'DESC'
      , 'm.daihyo_syohin_code' => 'ASC'
    ]);
    if ($conditionParams) {
      foreach($conditionParams as $k => $v) {
        $query->setParameter($k, $v);
      }
    }

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $query /* query NOT result */
      , $page
      , $limit
    );

    return $pagination;
  }


  /**
   * 重量・寸法設定済み コピー元商品取得
   * @param array $conditions
   * @param array $orders
   * @param null $limit
   * @param int $page
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function findWeightSizeCopyData($conditions = [], $orders = [], $limit = null, $page = 1)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $sqlSelect = <<<EOD
      SELECT
          m.daihyo_syohin_code
        , m.`横軸項目名` AS col_title
        , m.`縦軸項目名` AS row_title
        , m.weight
        , m.depth
        , m.width
        , m.height
        , C.col_codes
        , C.row_codes
        , I.directory AS image_directory
        , I.filename AS image_filename
EOD;
    $sqlBody = <<<EOD
      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN (
        SELECT
            pci.daihyo_syohin_code
          , GROUP_CONCAT(DISTINCT pci.colcode ORDER BY pci.`並び順No`, pci.colcode) AS col_codes
          , GROUP_CONCAT(DISTINCT pci.rowcode ORDER BY pci.`並び順No`, pci.rowcode) AS row_codes
          , SUM(
              (CASE WHEN pci.weight = 0 THEN 0 ELSE 1 END)
            + (CASE WHEN pci.depth = 0 THEN 0 ELSE 1 END)
            + (CASE WHEN pci.width = 0 THEN 0 ELSE 1 END)
            + (CASE WHEN pci.height = 0 THEN 0 ELSE 1 END)
          ) AS weight_size_set
        FROM tb_productchoiceitems pci
        INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code /* 軽量化のためここでJOIN */
        WHERE cal.weight_check_need_flg = 0
        GROUP BY pci.daihyo_syohin_code
      ) C ON m.daihyo_syohin_code = C.daihyo_syohin_code
      LEFT JOIN (
        SELECT
            i.daihyo_syohin_code
          , i.`directory`
          , i.filename
        FROM product_images i
        WHERE i.code = 'p001'
      ) I ON m.daihyo_syohin_code = I.daihyo_syohin_code
      WHERE m.weight > 0
        AND m.depth > 0
        AND m.width > 0
        AND m.height > 0
        AND C.weight_size_set = 0
EOD;

    $rsm = new ResultSetMappingBuilder($em);
    $rsm->addScalarResult('daihyo_syohin_code', 'daihyo_syohin_code', 'string');
    $rsm->addScalarResult('col_title', 'col_title', 'string');
    $rsm->addScalarResult('row_title', 'row_title', 'string');
    $rsm->addScalarResult('weight', 'weight', 'integer');
    $rsm->addScalarResult('depth', 'depth', 'integer');
    $rsm->addScalarResult('width', 'width', 'integer');
    $rsm->addScalarResult('height', 'height', 'integer');

    $rsm->addScalarResult('col_codes', 'col_codes', 'string');
    $rsm->addScalarResult('row_codes', 'row_codes', 'string');

    $rsm->addScalarResult('image_directory', 'image_directory', 'string');
    $rsm->addScalarResult('image_filename', 'image_filename', 'string');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    $query->setOrders([
        'cal.sales_volume' => 'DESC'
      , 'm.daihyo_syohin_code' => 'ASC'
    ]);

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $query /* query NOT result */
      , $page
      , $limit
    );

    return $pagination;
  }

  /**
   * アダルトチェックステータス 絞込取得
   * @param array $conditions
   * @param array $orders
   * @param null $limit
   * @param int $page
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function searchNotWhiteList($conditions = [], $orders = [], $limit = null, $page = 1)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    if (empty($conditions['adult_check_status'])) {
      $conditions['adult_check_status'] = [
          TbMainproductsCal::ADULT_CHECK_STATUS_NONE
        , TbMainproductsCal::ADULT_CHECK_STATUS_GRAY
        , TbMainproductsCal::ADULT_CHECK_STATUS_BLACK
      ];
    }

    $conditionParams = [];

    $dbMain = $this->getConnection('main');

    // アダルトチェック条件
    $tmp = [];
    foreach($conditions['adult_check_status'] as $status) {
      $tmp[] = $dbMain->quote($status);
    }
    $adultCheckStatus = implode(', ', $tmp);

    $sqlSelect = <<<EOD
      SELECT
         m.*
EOD;
    $sqlBody = <<<EOD
      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE cal.adult_check_status IN ( {$adultCheckStatus} )
        AND cal.deliverycode <> :deliveryCodeTemporary
EOD;
    $conditionParams[':deliveryCodeTemporary'] = TbMainproductsCal::DELIVERY_CODE_TEMPORARY;

    // 販売開始日 start
    if (isset($conditions['date_start']) && strlen($conditions['date_start'])) {
      $sqlBody .= " AND m.登録日時 >= :dateStart ";
      $conditionParams[':dateStart'] = $conditions['date_start'];
    }
    // 販売開始日 end
    if (isset($conditions['date_end']) && strlen($conditions['date_end'])) {
      $sqlBody .= " AND m.登録日時 <= :dateEnd ";
      $conditionParams[':dateEnd'] = $conditions['date_end'];
    }
    // 販売状況
    if (!empty($conditions['deliverycode'])) {
      $tmp = [];
      foreach($conditions['deliverycode'] as $code) {
        $tmp[] = intval($code);
      }

      $deliveryCodeList = implode(', ', $tmp);
      $sqlBody .= " AND cal.deliverycode IN ( {$deliveryCodeList} ) ";
    }

    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:TbMainproducts', 'm');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    $query->setOrders([
        'm.登録日時' => 'DESC'
      , 'm.daihyo_syohin_code' => 'ASC'
    ]);
    if ($conditionParams) {
      foreach($conditionParams as $k => $v) {
        $query->setParameter($k, $v);
      }
    }

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $query /* query NOT result */
      , $page
      , $limit
    );

    return $pagination;
  }



  /**
   * アダルト画像チェック用 画像取得処理 ※メイン画像のみ
   * 1.「アダルトの登録される可能性のあるカテゴリの商品」で「ホワイト」設定になっているもの
   *
   * @return \Doctrine\ORM\Internal\Hydration\IterableResult
   */
  public function findAdultCheckImagesAdultCategoryWhiteProductImages()
  {
    /** @var TbPlusnaoproductdirectoryRepository $repoDirectory */
    $repoDirectory = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbPlusnaoproductdirectory');

    $dbMain = $this->getConnection('main');

    $adultDirectories = $repoDirectory->getAdultDirectories();
    $directoryIds = [];
    foreach($adultDirectories as $row) {
      $directoryIds[] = $dbMain->quote($row->getNeDirectoryId(), \PDO::PARAM_STR);
    }
    $directoryIdCondition = implode(', ', $directoryIds);

    $sql = <<<EOD
      SELECT
        pi.*
      FROM product_images pi
      INNER JOIN tb_mainproducts m ON pi.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON pi.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE m.`NEディレクトリID` IN ( {$directoryIdCondition} )
        AND cal.adult_check_status = :adultCheckStatusWhite
        AND pi.code = 'p001'
EOD;

    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:ProductImages', 'pi');

    $query = $em->createNativeQuery($sql, $rsm);
    $query->setParameter(':adultCheckStatusWhite', TbMainproductsCal::ADULT_CHECK_STATUS_WHITE);

    return $query->iterate();
  }


  /**
   * アダルト画像チェック用 画像取得処理 ※メイン画像のみ
   * 2. すべてのブラックの商品
   *
   * @return \Doctrine\ORM\Internal\Hydration\IterableResult
   */
  public function findAdultCheckImagesAllBlackProductImages()
  {
    $sql = <<<EOD
      SELECT
        pi.*
      FROM product_images pi
      INNER JOIN tb_mainproducts m ON pi.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON pi.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE cal.adult_check_status = :adultCheckStatusBlack
        AND pi.code = 'p001'
EOD;

    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:ProductImages', 'pi');

    $query = $em->createNativeQuery($sql, $rsm);
    $query->setParameter(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);

    return $query->iterate();
  }


  /**
   * アダルト画像チェック用 画像取得処理 ※メイン画像のみ
   * 3. すべてのグレーの商品
   * @return \Doctrine\ORM\Internal\Hydration\IterableResult
   */
  public function findAdultCheckImagesAllGrayProductImages()
  {
    $sql = <<<EOD
      SELECT
        pi.*
      FROM product_images pi
      INNER JOIN tb_mainproducts m ON pi.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON pi.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE cal.adult_check_status = :adultCheckStatusGray
        AND pi.code = 'p001'
EOD;

    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:ProductImages', 'pi');

    $query = $em->createNativeQuery($sql, $rsm);
    $query->setParameter(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY);

    return $query->iterate();
  }

  /**
   * アダルト画像チェック用 画像取得処理 ※メイン画像のみ
   * 4.「Amazonメイン画像」で「Amazonへ登録されている商品」
   * @return \Doctrine\ORM\Internal\Hydration\IterableResult
   */
  public function findAdultCheckImagesAllAmazonRegisteredProductImages()
  {
    $sql = <<<EOD
      SELECT
        pi.*
      FROM product_images_amazon pi
      INNER JOIN tb_mainproducts m ON pi.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON pi.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_amazon_product_stock s ON pi.daihyo_syohin_code = s.sku
      WHERE pi.code = 'amazonMain'
EOD;

    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:ProductImagesAmazon', 'pi');

    $query = $em->createNativeQuery($sql, $rsm);

    return $query->iterate();
  }

  /**
   * 英語情報 一覧取得
   * @param array $conditions
   * @param array $orders
   * @param int $limit
   * @param int $page
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function findEnglishDataList($conditions = [], $orders = [], $limit = 100, $page = 1)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    /** @var DbCommonUtil $commonUtil */
    $commonUtil = $this->getContainer()->get('misc.util.db_common');

    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $conditionParams = [];

    $sqlSelect = <<<EOD
      SELECT
           m.daihyo_syohin_code
         , m.picfolderP1 AS image_dir
         , m.picnameP1   AS image_file
         , e.title       AS english_title
         , e.manual_input
         , e.check_flg
EOD;

    $sqlBody = <<<EOD
      FROM tb_mainproducts m
      LEFT JOIN tb_mainproducts_english e ON m.daihyo_syohin_code = e.daihyo_syohin_code
      WHERE 1

EOD;
    $needRegistered = false; // 登録済みのみの検索か

    // 商品コード
    if (isset($conditions['daihyo_syohin_code']) && strlen($conditions['daihyo_syohin_code'])) {
      $word = $commonUtil->escapeLikeString($conditions['daihyo_syohin_code']) . '%';
      $sqlBody .= " AND m.daihyo_syohin_code like :daihyoSyohinCode ";
      $conditionParams[':daihyoSyohinCode'] = $word;
    }

    // 登録済み
    if (isset($conditions['registered']) && strlen($conditions['registered'])) {
      if (boolval($conditions['registered'])) {
        $sqlBody .= " AND e.daihyo_syohin_code IS NOT NULL ";
        $needRegistered = true;
      } else {
        $sqlBody .= " AND e.daihyo_syohin_code IS NULL ";
      }
    }

    // 手動入力
    if (isset($conditions['manual_input']) && strlen($conditions['manual_input'])) {
      $sqlBody .= " AND e.manual_input = :manualInput ";
      $conditionParams[':manualInput'] = boolval($conditions['manual_input']) ? '-1' : '0';
      $needRegistered = true;
    }

    // チェック済み
    if (isset($conditions['check_flg']) && strlen($conditions['check_flg'])) {
      $sqlBody .= " AND e.check_flg = :checkFlg ";
      $conditionParams[':checkFlg'] = boolval($conditions['check_flg']) ? '-1' : '0';
      $needRegistered = true;
    }

    // 登録済みのみの検索なら、タイトル必須
    if ($needRegistered) {
      $sqlBody .= " AND e.title IS NOT NULL AND e.title <> '' ";
    }

    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('daihyo_syohin_code', 'daihyo_syohin_code', 'string');
    $rsm->addScalarResult('image_dir', 'image_dir', 'string');
    $rsm->addScalarResult('image_file', 'image_file', 'string');
    $rsm->addScalarResult('english_title', 'english_title', 'string');
    $rsm->addScalarResult('manual_input', 'manual_input', 'integer');
    $rsm->addScalarResult('check_flg', 'check_flg', 'integer');
    $rsm->addScalarResult('created', 'created', 'datetime');
    $rsm->addScalarResult('updated', 'updated', 'updated');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    foreach($conditionParams as $k => $v) {
      $query->setParameter($k, $v);
    }

    $resultOrders = [];
    $defaultOrders = [
      'm.daihyo_syohin_code' => 'ASC'
    ];

    if ($orders) {
      foreach($orders as $k => $v) {
        switch($k) {
          case 'daihyo_syohin_code':
//            $k = 'o.' . $k;
            break;
        }

        $resultOrders[$k] = $v;
        if (isset($defaultOrders[$k])) {
          unset($defaultOrders[$k]);
        }
      }
    }
    $query->setOrders(array_merge($resultOrders, $defaultOrders));

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
    $pagination = $paginator->paginate(
        $query /* query NOT result */
      , $page
      , $limit
    );

    return $pagination;
  }

  /**
   * セット商品全件取得
   * @return array
   */
  public function getAllSetProductList()
  {
    $db = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
          m.daihyo_syohin_code
        , m.daihyo_syohin_name
        , m.picfolderP1 AS image_dir
        , m.picnameP1 AS image_file
        , pci.required_stock
        , pci.sku_num
        , pci.stock
        , pci.free_stock
      FROM tb_mainproducts m
      INNER JOIN (
        SELECT
            pci.daihyo_syohin_code
          , COUNT(*) AS sku_num
          , SUM(pci.在庫数) AS stock
          , SUM(pci.`フリー在庫数`)  AS free_stock
          , SUM(sku.required_stock) AS required_stock
        FROM tb_productchoiceitems pci
        INNER JOIN tb_set_product_sku sku ON pci.ne_syohin_syohin_code = sku.ne_syohin_syohin_code
        INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
        WHERE m.set_flg <> 0
        GROUP BY pci.daihyo_syohin_code
      ) pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
      ORDER BY m.daihyo_syohin_code
EOD;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    return $products;
  }

  /**
   * セット商品 在庫設定数取得
   * @param TbMainproducts $product
   * @return array
   */
  public function getSetRequiredStocks(TbMainproducts $product)
  {
    $sql = <<<EOD
      SELECT
          pci.ne_syohin_syohin_code
        , COALESCE(sku.required_stock, 0) AS set_required_stock
      FROM tb_productchoiceitems pci
      LEFT JOIN tb_set_product_sku sku ON pci.ne_syohin_syohin_code = sku.ne_syohin_syohin_code
      WHERE pci.daihyo_syohin_code = :daihyoSyohinCode
      ORDER BY pci.並び順No
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $product->getDaihyoSyohinCode(), \PDO::PARAM_STR);
    $stmt->execute();
    $tmp = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $result = [];
    foreach($tmp as $row) {
      $result[$row['ne_syohin_syohin_code']] = $row['set_required_stock'];
    }
    return $result;
  }

  /**
   * セット商品 1SKU の SKU内訳を取得
   * @param TbProductchoiceitems $choice
   * @return array
   */
  public function getSetProductSkuDetails(TbProductchoiceitems $choice)
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      SELECT
          d.set_ne_syohin_syohin_code
        , d.ne_syohin_syohin_code
        , d.num
        , pci.colcode
        , pci.colname
        , pci.rowcode
        , pci.rowname
      FROM tb_set_product_detail d
      INNER JOIN tb_productchoiceitems pci ON d.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      WHERE d.set_ne_syohin_syohin_code = :neSyohinSyohinCode
      ORDER BY d.ne_syohin_syohin_code
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neSyohinSyohinCode', $choice->getNeSyohinSyohinCode(), \PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * セット商品 1SKU の SKU内訳を全削除
   * @param TbProductchoiceitems $choice
   */
  public function deleteSetProductSkuDetails(TbProductchoiceitems $choice)
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      DELETE d
      FROM tb_set_product_detail d
      WHERE d.set_ne_syohin_syohin_code = :neSyohinSyohinCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neSyohinSyohinCode', $choice->getNeSyohinSyohinCode(), \PDO::PARAM_STR);
    $stmt->execute();

    return;
  }

  /**
   * セット商品 不要セット内訳の削除、セットフラグの一括更新
   */
  public function clearInvalidSetDetailRecords()
  {
    $dbMain = $this->getConnection('main');

    // tb_productchoiceitems に紐付きの無い内訳レコードを削除
    $sql = <<<EOD
      DELETE d
      FROM tb_set_product_detail d
      LEFT JOIN tb_productchoiceitems pci1 ON d.set_ne_syohin_syohin_code = pci1.ne_syohin_syohin_code
      LEFT JOIN tb_productchoiceitems pci2 ON d.ne_syohin_syohin_code = pci2.ne_syohin_syohin_code
      WHERE pci1.ne_syohin_syohin_code IS NULL
         OR pci2.ne_syohin_syohin_code IS NULL
EOD;
    $dbMain->exec($sql);

  }

  /**
   * tb_set_product_sku レコードの欠番補充、不要レコード削除
   */
  public function createEmptySetProductSkuRecords()
  {
    $db = $this->getConnection('main');

    $sql = <<<EOD
      INSERT IGNORE INTO tb_set_product_sku (
        ne_syohin_syohin_code
      )
      SELECT
        pci.ne_syohin_syohin_code
      FROM tb_mainproducts m
      INNER JOIN tb_productchoiceitems pci ON m.daihyo_syohin_code = pci.daihyo_syohin_code
      LEFT JOIN tb_set_product_sku sku ON pci.ne_syohin_syohin_code = sku.ne_syohin_syohin_code
      WHERE m.set_flg <> 0
        AND sku.ne_syohin_syohin_code IS NULL
EOD;
    $db->exec($sql);

  }





  /**
   * 商品コピー処理
   * @param string $fromCode
   * @param string $toCode
   * @param SymfonyUsers|null $account
   * @return string $newCode
   * @throws \Doctrine\DBAL\ConnectionException
   */
  public function copyProduct($fromCode, $toCode, $account = null)
  {
    $logger = $this->getLogger();

    try {

      $fromProduct = null;
      $toProduct = null;

      if ($fromCode) {
        /** @var TbMainproducts $fromProduct */
        $fromProduct = $this->find($fromCode);
      }
      if (!$fromProduct) {
        throw (new LeveledException('コピー元商品が見つかりません。'))->setLevel(LeveledException::WARNING);
      }
      if ($fromProduct->hasSetSku()) {
        throw (new LeveledException('セット商品はコピーできません。'))->setLevel(LeveledException::WARNING);
      }

      if ($toCode) {
        $toProduct = $this->find($toCode);
      }
      if (!preg_match('/^[a-zA-Z0-9\-]{5,17}$/', $toCode)) {
        throw (new LeveledException('代表商品コードは半角英数字およびハイフンのみで5文字以上・17文字までです。'))->setLevel(LeveledException::WARNING);
      }
      if ($toProduct) {
        throw (new LeveledException('コピー先の商品コードはすでに存在します。'))->setLevel(LeveledException::WARNING);
      }

      $dbMain = $this->getConnection('main');

      // トランザクション
      $dbMain->beginTransaction();

      // DBコピー
      //tb_mainproducts
      $sql = <<<EOD
      INSERT INTO tb_mainproducts (
          `daihyo_syohin_code`
        , `sire_code`
        , `jan_code`
        , `syohin_kbn`
        , `genka_tnk`
        , `daihyo_syohin_name`
        , `在庫変動チェックフラグ`
        , `価格非連動チェック`
        , `バリエーション変更チェック`
        , `価格変更チェック`
        , `備考`
        , `仕入備考`
        , `更新者履歴`
        , `楽天削除`
        , `登録日時`
        , `販売開始日`
        , `送料設定`
        , `入荷予定日`
        , `入荷アラート日数`
        , `優先表示修正値`
        , `優先表示順位`
        , `手動ゲリラSALE`
        , `入荷遅延日数`
        , `総在庫数`
        , `総在庫金額`
        , `商品コメントPC`
        , `一言ポイント`
        , `補足説明PC`
        , `必要補足説明`
        , `B固有必要補足説明`
        , `R固有必要補足説明`
        , `NE更新カラム`
        , `GMOタイトル`
        , `サイズについて`
        , `カラーについて`
        , `素材について`
        , `ブランドについて`
        , `使用上の注意`
        , `実勢価格`
        , `横軸項目名`
        , `縦軸項目名`
        , `col_type`
        , `row_type`
        , `カラー軸`
        , `NEディレクトリID`
        , `YAHOOディレクトリID`
        , `標準出荷日数`
        , `stockreview`
        , `stockinfomation`
        , `stockreviewinfomation`
        , `productchoiceitems_count`
        , `person`
        , `check_price`
        , `weight`
        , `depth`
        , `width`
        , `height`
        , `container_flg`
        , `batteries_required`
        , `fba_multi_flag`
        , `additional_cost`
        , `pic_check_datetime`
        , `pic_check_datetime_sort`
        , `notfound_image_no_rakuten`
        , `notfound_image_no_dena`
        , `set_flg`
        , `copied_from`
        , `copied_by`
        , `copied_at`
        , `dummy`
        , `company_code`

      )
      SELECT
          :daihyoSyohinCode
        , `sire_code`
        , `jan_code`
        , `syohin_kbn`
        , `genka_tnk`
        , `daihyo_syohin_name`
        , `在庫変動チェックフラグ`
        , `価格非連動チェック`
        , `バリエーション変更チェック`
        , `価格変更チェック`
        , '' AS `備考`
        , '' AS `仕入備考`
        , '' AS `更新者履歴`
        , `楽天削除`
        , NOW() AS `登録日時`
        , NULL AS `販売開始日`
        , `送料設定`
        , NULL AS `入荷予定日`
        , `入荷アラート日数`
        , `優先表示修正値`
        , `優先表示順位`
        , `手動ゲリラSALE`
        , `入荷遅延日数`
        , 0 AS `総在庫数`
        , 0 AS `総在庫金額`
        , `商品コメントPC`
        , `一言ポイント`
        , `補足説明PC`
        , `必要補足説明`
        , `B固有必要補足説明`
        , `R固有必要補足説明`
        , `NE更新カラム`
        , `GMOタイトル`
        , `サイズについて`
        , `カラーについて`
        , `素材について`
        , `ブランドについて`
        , `使用上の注意`
        , `実勢価格`
        , `横軸項目名`
        , `縦軸項目名`
        , `col_type`
        , `row_type`
        , `カラー軸`
        , `NEディレクトリID`
        , `YAHOOディレクトリID`
        , `標準出荷日数`
        , `stockreview`
        , `stockinfomation`
        , `stockreviewinfomation`
        , `productchoiceitems_count`
        , :person AS `person`
        , `check_price`
        , `weight`
        , `depth`
        , `width`
        , `height`
        , `container_flg`
        , `batteries_required`
        , `fba_multi_flag`
        , `additional_cost`
        , `pic_check_datetime`
        , `pic_check_datetime_sort`
        , `notfound_image_no_rakuten`
        , `notfound_image_no_dena`
        , `set_flg`
        , :copiedFrom AS copied_from
        , :copiedBy AS copied_by
        , NOW() AS copied_at
        , `dummy`
        , `company_code`
      FROM tb_mainproducts
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':person', $account ? $account->getUsername() : '(コピー)');
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->bindValue(':copiedFrom', $fromCode);
      $stmt->bindValue(':copiedBy', $account ? $account->getUsername() : '');
      $stmt->execute();

      //tb_mainproducts_cal
      $sql = <<<EOD
      INSERT INTO tb_mainproducts_cal (
          `daihyo_syohin_code`
        , `endofavailability`
        , `deliverycode`
        , `genka_tnk_ave`
        , `baika_tnk`
        , `base_baika_tanka`
        , `cost_tanka`
        , `profit_rate`
        , `sunfactoryset`
        , `list_some_instant_delivery`
        , `priority`
        , `earliest_order_date`
        , `delay_days`
        , `visible_flg`
        , `sales_volume`
        , `makeshop_Registration_flug`
        , `rakuten_Registration_flug`
        , `croozmall_Registration_flug`
        , `amazon_registration_flug`
        , `annual_sales`
        , `rakuten_Registration_flug_date`
        , `setnum`
        , `rakutencategory_tep`
        , `being_num`
        , `mall_price_flg`
        , `daihyo_syohin_label`
        , `maxbuynum`
        , `outlet`
        , `adult`
        , `adult_image_flg`
        , `adult_check_status`
        , `big_size`
        , `viewrank`
        , `reviewrequest`
        , `last_review_date`
        , `review_point_ave`
        , `review_num`
        , `search_code`
        , `fixed_cost`
        , `cost_rate`
        , `DENA画像チェック区分`
        , `dena_pic_check_datetime`
        , `dena_pic_check_datetime_sort`
        , `notfound_image_no_rakuten`
        , `notfound_image_no_dena`
        , `startup_flg`
        , `pricedown_flg`
        , `discount_base_date`
        , `red_flg`
        , `last_orderdate`
        , `last_order_date`
        , `wang_status`
        , `受発注可能フラグ退避F`
        , `soldout_check_flg`
        , `label_remark_flg`
        , `size_check_need_flg`
        , `weight_check_need_flg`
        , `compress_flg`
        , `image_photo_need_flg`
        , `image_gradeup_need_flg`
        , `image_erase_character_need_flg`
        , `image_photo_comment`
        , `deliverycode_pre`
        , `high_sales_rate_flg`
        , `mail_send_nums`
        , `bundle_num_average`
        , `memo`
        , `timestamp`
        , `rakutencategories_3`
        , `zaiko_teisu_reset_date`
        , `quality_level`
        , `quality_level_updated`
        , `work_check_01`
        , `work_check_02`
        , `work_check_03`
        , `work_check_04`
      )
      SELECT
          :daihyoSyohinCode
        , NOW() AS `endofavailability`
        , :deliveryCodeFinished AS `deliverycode`
        , `genka_tnk_ave`
        , `baika_tnk`
        , `base_baika_tanka`
        , `cost_tanka`
        , `profit_rate`
        , `sunfactoryset`
        , `list_some_instant_delivery`
        , `priority`
        , `earliest_order_date`
        , `delay_days`
        , `visible_flg`
        , `sales_volume`
        , `makeshop_Registration_flug`
        , `rakuten_Registration_flug`
        , `croozmall_Registration_flug`
        , `amazon_registration_flug`
        , `annual_sales`
        , `rakuten_Registration_flug_date`
        , `setnum`
        , `rakutencategory_tep`
        , `being_num`
        , `mall_price_flg`
        , `daihyo_syohin_label`
        , `maxbuynum`
        , `outlet`
        , `adult`
        , `adult_image_flg`
        , `adult_check_status`
        , `big_size`
        , `viewrank`
        , `reviewrequest`
        , '0000-00-00 00:00:00' as `last_review_date`
        , '0.0' as `review_point_ave`
        , 0 as `review_num`
        , '' as `search_code`
        , `fixed_cost`
        , `cost_rate`
        , `DENA画像チェック区分`
        , `dena_pic_check_datetime`
        , `dena_pic_check_datetime_sort`
        , `notfound_image_no_rakuten`
        , `notfound_image_no_dena`
        , `startup_flg`
        , `pricedown_flg`
        , `discount_base_date`
        , `red_flg`
        , `last_orderdate`
        , null as `last_order_date`
        , `wang_status`
        , `受発注可能フラグ退避F`
        , `soldout_check_flg`
        , `label_remark_flg`
        , `size_check_need_flg`
        , `weight_check_need_flg`
        , `compress_flg`
        , `image_photo_need_flg`
        , `image_gradeup_need_flg`
        , `image_erase_character_need_flg`
        , `image_photo_comment`
        , `deliverycode_pre`
        , `high_sales_rate_flg`
        , `mail_send_nums`
        , `bundle_num_average`
        , `memo`
        , `timestamp`
        , `rakutencategories_3`
        , `zaiko_teisu_reset_date`
        , '0' as `quality_level`
        , null as `quality_level_updated`
        , `work_check_01`
        , `work_check_02`
        , `work_check_03`
        , `work_check_04`
      FROM tb_mainproducts_cal
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED, \PDO::PARAM_INT);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_mainproducts_english
      $sql = <<<EOD
      INSERT IGNORE INTO tb_mainproducts_english (
          `daihyo_syohin_code`
        , `title`
        , `description`
        , `about_size`
        , `about_color`
        , `about_material`
        , `about_brand`
        , `usage_note`
        , `supplemental_explanation`
        , `short_description`
        , `short_supplemental_explanation`
        , `manual_input`
        , `check_flg`
        , `created`
        , `updated`
      )
      SELECT
          :daihyoSyohinCode
        , `title`
        , `description`
        , `about_size`
        , `about_color`
        , `about_material`
        , `about_brand`
        , `usage_note`
        , `supplemental_explanation`
        , `short_description`
        , `short_supplemental_explanation`
        , `manual_input`
        , `check_flg`
        , `created`
        , `updated`
      FROM tb_mainproducts_english
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_productchoiceitems
      $sql = <<<EOD
      INSERT INTO tb_productchoiceitems (
          `ne_syohin_syohin_code`
        , `並び順No`
        , `colname`
        , `colcode`
        , `rowname`
        , `rowcode`
        , `受発注可能フラグ`
        , `toriatukai_kbn`
        , `zaiko_teisu`
        , `hachu_ten`
        , `lot`
        , `daihyo_syohin_code`
        , `tag`
        , `location`
        /* , `フリー在庫数` ... generated_columnにお任せ */
        , support_colname
        , support_rowname
        , color_map
        , size_map
        , weight
        , depth
        , width
        , height
      )
      SELECT
          CONCAT(:daihyoSyohinCode, `colcode`, `rowcode`) AS `ne_syohin_syohin_code`
        , `並び順No`
        , `colname`
        , `colcode`
        , `rowname`
        , `rowcode`
        , `受発注可能フラグ`
        , `toriatukai_kbn`
        , `zaiko_teisu`
        , `hachu_ten`
        , `lot`
        , :daihyoSyohinCode
        , `tag`
        , `location`
        /* , `フリー在庫数` */
        , support_colname
        , support_rowname
        , color_map
        , size_map
        , weight
        , depth
        , width
        , height
      FROM tb_productchoiceitems
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_product_season
      $sql = <<<EOD
      INSERT IGNORE INTO tb_product_season (
          `daihyo_syohin_code`
        , `m1`
        , `m2`
        , `m3`
        , `m4`
        , `m5`
        , `m6`
        , `m7`
        , `m8`
        , `m9`
        , `m10`
        , `m11`
        , `m12`
        , `s1`
        , `s2`
        , `s3`
        , `s4`
        , `s5`
        , `s6`
        , `s7`
        , `s8`
        , `s9`
        , `s10`
        , `s11`
        , `s12`
        , `c1`
        , `c2`
        , `c3`
        , `c4`
        , `c5`
        , `c6`
        , `c7`
        , `c8`
        , `c9`
        , `c10`
        , `c11`
        , `c12`
      )
      SELECT
          :daihyoSyohinCode
        , `m1`
        , `m2`
        , `m3`
        , `m4`
        , `m5`
        , `m6`
        , `m7`
        , `m8`
        , `m9`
        , `m10`
        , `m11`
        , `m12`
        , `s1`
        , `s2`
        , `s3`
        , `s4`
        , `s5`
        , `s6`
        , `s7`
        , `s8`
        , `s9`
        , `s10`
        , `s11`
        , `s12`
        , `c1`
        , `c2`
        , `c3`
        , `c4`
        , `c5`
        , `c6`
        , `c7`
        , `c8`
        , `c9`
        , `c10`
        , `c11`
        , `c12`
      FROM tb_product_season
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_amazoninfomation
      $sql = <<<EOD
      INSERT IGNORE INTO tb_amazoninfomation (
          `daihyo_syohin_code`
        , `amazon_title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `org_pic_num`
        , `fba_baika`
        , `fba_flg`
      )
      SELECT
          :daihyoSyohinCode
        , `amazon_title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `org_pic_num`
        , `fba_baika`
        , `fba_flg`
      FROM tb_amazoninfomation
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_biddersinfomation
      $sql = <<<EOD
      INSERT IGNORE INTO tb_biddersinfomation (
          `daihyo_syohin_code`
        , `front_title`
        , `bidders_title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `biddersmdescription`
        , `biddersmdetaildescription`
        , `pricelabel1`
        , `pricelabel2`
        , `rand_no_seq`
        , `rand_link1_no`
        , `rand_link2_no`
        , `search_keyword1`
        , `search_keyword2`
        , `search_keyword3`
        , `bidders_pc_caption`
        , `bidders_price`
      )
      SELECT
          :daihyoSyohinCode
        , `front_title`
        , `bidders_title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `biddersmdescription`
        , `biddersmdetaildescription`
        , `pricelabel1`
        , `pricelabel2`
        , `rand_no_seq`
        , `rand_link1_no`
        , `rand_link2_no`
        , `search_keyword1`
        , `search_keyword2`
        , `search_keyword3`
        , `bidders_pc_caption`
        , `bidders_price`
      FROM tb_biddersinfomation
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_qten_information
      $sql = <<<EOD
      INSERT IGNORE INTO tb_qten_information (
          `daihyo_syohin_code`
        , `q10_itemcode`
        , `q10_itemcode_index`
        , `q10_title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `sell_price`
        , `sell_qty`
        , `exist_image`
        , `title`
        , `explanation`
        , `free_explanation`
        , `inventory_info`
        , `status`
        , `2nd_cat_code`
        , `shipping_group_no`
        , `available_date`
        , `image_url`
        , `additional_item_image`
      )
      SELECT
          :daihyoSyohinCode
        , `q10_itemcode`
        , `q10_itemcode_index`
        , `q10_title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `sell_price`
        , `sell_qty`
        , `exist_image`
        , `title`
        , `explanation`
        , `free_explanation`
        , `inventory_info`
        , `status`
        , `2nd_cat_code`
        , `shipping_group_no`
        , `available_date`
        , `image_url`
        , `additional_item_image`
      FROM tb_qten_information
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      // 楽天モール information テーブル レコード登録
      $rakutenMallInformationTableList = [
        'tb_rakuteninformation',
        'tb_rakuten_motto_information',
        'tb_rakuten_laforest_information',
        'tb_rakuten_dolcissimo_information',
        'tb_rakuten_gekipla_information',
      ];

      foreach ($rakutenMallInformationTableList as $table) {
        $format = <<<EOD
        INSERT IGNORE INTO `%s` (
            `daihyo_syohin_code`
          , `楽天タイトル`
          , `補正楽天タイトル`
          , `variation`
          , `variation_ex`
          , `variation_ex2`
          , `registration_flg`
          , `update_flg`
          , `original_price`
          , `baika_tanka`
          , `Rモバイル用商品説明文`
          , `RPC用商品説明文`
          , `RPC用商品説明文_PC`
          , `RPC用商品説明文_SP`
          , `RPC用販売説明文`
          , `旧楽天P説明`
          , `商品画像URL`
          , `rand_no`
          , `rand_link1_no`
          , `delivery_Information`
          , `rakuten_price`
          , `レビュー本文表示`
          , `sales_period_start_date`
          , `sales_period_start_time`
          , `sales_period_end_date`
          , `sales_period_end_time`
          , `sales_period`
          , `表示価格`
          , `二重価格文言管理番号`
          , `input_PC商品説明文`
          , `input_SP商品説明文`
          , `input_PC販売説明文`
          , `cat_list_html`
          , `商品名`
          , `PC用キャッチコピー`
          , `モバイル用キャッチコピー`
          , `商品画像名（ALT）`
        )
        SELECT
            :daihyoSyohinCode
          , `楽天タイトル`
          , `補正楽天タイトル`
          , `variation`
          , `variation_ex`
          , `variation_ex2`
          , `registration_flg`
          , `update_flg`
          , `original_price`
          , `baika_tanka`
          , `Rモバイル用商品説明文`
          , `RPC用商品説明文`
          , `RPC用商品説明文_PC`
          , `RPC用商品説明文_SP`
          , `RPC用販売説明文`
          , `旧楽天P説明`
          , `商品画像URL`
          , `rand_no`
          , `rand_link1_no`
          , `delivery_Information`
          , `rakuten_price`
          , `レビュー本文表示`
          , `sales_period_start_date`
          , `sales_period_start_time`
          , `sales_period_end_date`
          , `sales_period_end_time`
          , `sales_period`
          , `表示価格`
          , `二重価格文言管理番号`
          , `input_PC商品説明文`
          , `input_SP商品説明文`
          , `input_PC販売説明文`
          , `cat_list_html`
          , `商品名`
          , `PC用キャッチコピー`
          , `モバイル用キャッチコピー`
          , `商品画像名（ALT）`
        FROM `%s`
        WHERE daihyo_syohin_code = :fromCode
EOD;
        $sql = sprintf($format, $table, $table);
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':daihyoSyohinCode', $toCode);
        $stmt->bindValue(':fromCode', $fromCode);
        $stmt->execute();
      }

      //tb_cube_information
      $sql = <<<EOD
      INSERT IGNORE INTO tb_cube_information (
          `daihyo_syohin_code`
        , `title`
        , `registration_flg`
        , `NE更新カラム`
        , `original_price`
        , `baika_tanka`
      )
      SELECT
          :daihyoSyohinCode
        , `title`
        , `registration_flg`
        , `NE更新カラム`
        , `original_price`
        , `baika_tanka`
      FROM tb_cube_information
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_yahoo_information
      $sql = <<<EOD
      INSERT IGNORE INTO tb_yahoo_information (
          `daihyo_syohin_code`
        , `yahoo_title`
        , `registration_flg`
        , `update_flg`
        , `registration_flg_adult`
        , `original_price`
        , `baika_tanka`
        , `meta-key`
        , `exist_image`
        , `explanation`
        , `caption`
        , `sub-code`
        , `options`
        , `options-upddate`
        , `sp-additional`
        , `input_caption`
        , `input_sp_additional`
        , `path`
        , `pr-rate`
      )
      SELECT
          :daihyoSyohinCode
        , `yahoo_title`
        , `registration_flg`
        , `update_flg`
        , `registration_flg_adult`
        , `original_price`
        , `baika_tanka`
        , `meta-key`
        , `exist_image`
        , `explanation`
        , `caption`
        , `sub-code`
        , `options`
        , `options-upddate`
        , `sp-additional`
        , `input_caption`
        , `input_sp_additional`
        , `path`
        , `pr-rate`
      FROM tb_yahoo_information
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_yahoo_kawa_information
      $sql = <<<EOD
      INSERT IGNORE INTO tb_yahoo_kawa_information (
          `daihyo_syohin_code`
        , `yahoo_title`
        , `registration_flg`
        , `update_flg`
        , `registration_flg_adult`
        , `original_price`
        , `baika_tanka`
        , `meta-key`
        , `exist_image`
        , `explanation`
        , `caption`
        , `sub-code`
        , `options`
        , `options-upddate`
        , `sp-additional`
        , `input_caption`
        , `input_sp_additional`
        , `path`
        , `pr-rate`
      )
      SELECT
          :daihyoSyohinCode
        , `yahoo_title`
        , `registration_flg`
        , `update_flg`
        , `registration_flg_adult`
        , `original_price`
        , `baika_tanka`
        , `meta-key`
        , `exist_image`
        , `explanation`
        , `caption`
        , `sub-code`
        , `options`
        , `options-upddate`
        , `sp-additional`
        , `input_caption`
        , `input_sp_additional`
        , `path`
        , `pr-rate`
      FROM tb_yahoo_kawa_information
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_yahoo_otoriyose_information
      $sql = <<<EOD
      INSERT IGNORE INTO tb_yahoo_otoriyose_information (
          `daihyo_syohin_code`
        , `yahoo_title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `meta-key`
        , `exist_image`
        , `explanation`
        , `caption`
        , `sub-code`
        , `options`
        , `options-upddate`
        , `sp-additional`
        , `input_caption`
        , `input_sp_additional`
        , `path`
        , `pr-rate`
        , `last_image_upload_datetime`
      )
      SELECT
          :daihyoSyohinCode
        , `yahoo_title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `meta-key`
        , `exist_image`
        , `explanation`
        , `caption`
        , `sub-code`
        , `options`
        , `options-upddate`
        , `sp-additional`
        , `input_caption`
        , `input_sp_additional`
        , `path`
        , `pr-rate`
        , `last_image_upload_datetime`
      FROM tb_yahoo_otoriyose_information
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_ss_information
      $sql = <<<EOD
      INSERT IGNORE INTO tb_ss_information (
          `daihyo_syohin_code`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `exist_image`
        , `ss_title`
        , `priority`
        , `sort`
        , `商品名`
        , `ショップサーブカテゴリ名`
        , `ショップサーブカテゴリID`
        , `販売価格`
        , `定価項目名`
        , `型番品番`
        , `ポイント還元率`
        , `最低個数`
        , `最高個数`
        , `重量`
        , `個別送料設定`
        , `個別送料`
        , `優先`
        , `画像ファイル名`
        , `メイン紹介文`
        , `サブ紹介文１`
        , `サブ紹介文２`
        , `携帯用メイン紹介文`
        , `携帯用サブ紹介文１`
        , `携帯用サブ紹介文２`
        , `商品ページタイトル`
        , `商品ページキーワード`
        , `外部用キャッチコピー`
        , `内部用キャッチコピー`
        , `携帯用内部キャッチコピー`
        , `シークレットグループ`
        , `新着`
        , `おすすめ`
        , `備考１`
        , `備考欄名１`
        , `備考２`
        , `備考欄名２`
        , `備考３`
        , `備考欄名３`
        , `自作商品ページの商品URL`
        , `サブ画像１`
        , `サブ画像２`
        , `サブ画像３`
        , `サブ画像４`
        , `サブ画像５`
        , `サブ画像６`
        , `サブ画像７`
        , `サブ画像８`
        , `サブ画像９`
        , `サブ画像１０`
        , `JANコード`
        , `メーカー`
        , `陳列期間`
        , `セール項目名`
        , `セール価格`
        , `開始年月日`
        , `開始時刻`
        , `終了年月日`
        , `終了時刻`
        , `商品説明`
        , `製品重量`
        , `製品重量（単位）`
        , `関連商品`
        , `並び順番号`
        , `評価コメントの設定１`
        , `評価コメントの設定２`
        , `携帯用メイン紹介文（オプション）`
        , `スマフォ用メイン紹介文（オプション）`
        , `携帯用サブ紹介文１（オプション）`
        , `スマフォ用サブ紹介文１（オプション）`
        , `携帯用サブ紹介文２（オプション）`
        , `スマフォ用サブ紹介文２（オプション）`
        , `携帯用内部キャッチコピー（オプション）`
        , `スマフォ用内部キャッチコピー（オプション）`
        , `カテゴリ1`
        , `メール便`
        , `同梱不可設定`
        , `決済方法`
        , `Googleファッション属性性別`
        , `Googleファッション属性世代`
      )
      SELECT
          :daihyoSyohinCode
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `exist_image`
        , `ss_title`
        , `priority`
        , `sort`
        , `商品名`
        , `ショップサーブカテゴリ名`
        , `ショップサーブカテゴリID`
        , `販売価格`
        , `定価項目名`
        , `型番品番`
        , `ポイント還元率`
        , `最低個数`
        , `最高個数`
        , `重量`
        , `個別送料設定`
        , `個別送料`
        , `優先`
        , `画像ファイル名`
        , `メイン紹介文`
        , `サブ紹介文１`
        , `サブ紹介文２`
        , `携帯用メイン紹介文`
        , `携帯用サブ紹介文１`
        , `携帯用サブ紹介文２`
        , `商品ページタイトル`
        , `商品ページキーワード`
        , `外部用キャッチコピー`
        , `内部用キャッチコピー`
        , `携帯用内部キャッチコピー`
        , `シークレットグループ`
        , `新着`
        , `おすすめ`
        , `備考１`
        , `備考欄名１`
        , `備考２`
        , `備考欄名２`
        , `備考３`
        , `備考欄名３`
        , `自作商品ページの商品URL`
        , `サブ画像１`
        , `サブ画像２`
        , `サブ画像３`
        , `サブ画像４`
        , `サブ画像５`
        , `サブ画像６`
        , `サブ画像７`
        , `サブ画像８`
        , `サブ画像９`
        , `サブ画像１０`
        , `JANコード`
        , `メーカー`
        , `陳列期間`
        , `セール項目名`
        , `セール価格`
        , `開始年月日`
        , `開始時刻`
        , `終了年月日`
        , `終了時刻`
        , `商品説明`
        , `製品重量`
        , `製品重量（単位）`
        , `関連商品`
        , `並び順番号`
        , `評価コメントの設定１`
        , `評価コメントの設定２`
        , `携帯用メイン紹介文（オプション）`
        , `スマフォ用メイン紹介文（オプション）`
        , `携帯用サブ紹介文１（オプション）`
        , `スマフォ用サブ紹介文１（オプション）`
        , `携帯用サブ紹介文２（オプション）`
        , `スマフォ用サブ紹介文２（オプション）`
        , `携帯用内部キャッチコピー（オプション）`
        , `スマフォ用内部キャッチコピー（オプション）`
        , `カテゴリ1`
        , `メール便`
        , `同梱不可設定`
        , `決済方法`
        , `Googleファッション属性性別`
        , `Googleファッション属性世代`
      FROM tb_ss_information
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_ppm_information
      $sql = <<<EOD
      INSERT IGNORE INTO tb_ppm_information (
          `daihyo_syohin_code`
        , `input_商品説明1`
        , `input_商品説明2`
        , `input_商品説明テキストのみ`
        , `input_商品説明スマートフォン用`
        , `ppm_title`
        , `registration_flg`
        , `update_flg`
        , `exist_image`
        , `キャッチコピー`
        , `original_price`
        , `baika_tanka`
        , `商品説明文_共通`
        , `商品説明1`
        , `商品説明2`
        , `商品説明テキストのみ`
        , `商品画像URL`
        , `商品説明スマートフォン用`
        , `category`
        , `variation`
        , `variation_ex`
        , `variation_ex2`
        , `rand_no`
        , `rand_link1_no`
      )
      SELECT
          :daihyoSyohinCode
        , `input_商品説明1`
        , `input_商品説明2`
        , `input_商品説明テキストのみ`
        , `input_商品説明スマートフォン用`
        , `ppm_title`
        , `registration_flg`
        , `update_flg`
        , `exist_image`
        , `キャッチコピー`
        , `original_price`
        , `baika_tanka`
        , `商品説明文_共通`
        , `商品説明1`
        , `商品説明2`
        , `商品説明テキストのみ`
        , `商品画像URL`
        , `商品説明スマートフォン用`
        , `category`
        , `variation`
        , `variation_ex`
        , `variation_ex2`
        , `rand_no`
        , `rand_link1_no`
      FROM tb_ppm_information
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_shoplist_information
      $sql = <<<EOD
      INSERT INTO tb_shoplist_information (
          `daihyo_syohin_code`
        , `title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `current_price`
        , `current_list_price`
        , `last_image_upload_datetime`

      )
      SELECT
          :daihyoSyohinCode
        , `title`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `current_price`
        , `current_list_price`
        , `last_image_upload_datetime`
      FROM tb_shoplist_information
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      //tb_amazon_com_information
      $sql = <<<EOD
      INSERT IGNORE INTO tb_amazon_com_information (
          `daihyo_syohin_code`
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `fba_baika`
        , `fba_flg`
        , `is_valid`
        , `packet_type`
        , `shipping_method`
        , `postage`
      )
      SELECT
          :daihyoSyohinCode
        , `registration_flg`
        , `update_flg`
        , `original_price`
        , `baika_tanka`
        , `fba_baika`
        , `fba_flg`
        , `is_valid`
        , `packet_type`
        , `shipping_method`
        , `postage`
      FROM tb_amazon_com_information
      WHERE daihyo_syohin_code = :fromCode
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->bindValue(':fromCode', $fromCode);
      $stmt->execute();

      // ここまでで登録された（未コミット）商品データでオブジェクトを取得
      /** @var TbMainproducts $toProduct */
      $toProduct = $this->find($toCode);
      if (!$toProduct) {
        throw (new LeveledException('コピー商品のDB登録ができませんでした。'))->setLevel(LeveledException::ERROR);
      }

      // 画像コピー
      // 画像データは残っていることがあるため、レコードをばっさり削除
      $stmt = $dbMain->prepare("DELETE FROM product_images WHERE daihyo_syohin_code = :daihyoSyohinCode ");
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->execute();

      $stmt = $dbMain->prepare("DELETE FROM product_images_amazon WHERE daihyo_syohin_code = :daihyoSyohinCode ");
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->execute();

      $stmt = $dbMain->prepare("DELETE FROM product_images_variation WHERE daihyo_syohin_code = :daihyoSyohinCode ");
      $stmt->bindValue(':daihyoSyohinCode', $toCode);
      $stmt->execute();

      $emMain = $this->getEntityManager();

      /** @var ImageUtil $imageUtil */
      $imageUtil = $this->getContainer()->get('misc.util.image');
      $imageDir = $imageUtil->findAvailableImageDirectory();
      $originalImageDir = $this->getContainer()->getParameter('product_image_original_dir');
      $amazonImageDir = $this->getContainer()->getParameter('product_image_amazon_dir');
      $variationImageDir = $this->getContainer()->getParameter('product_image_variation_dir');

      // -- 商品画像
      /** @var ProductImagesRepository $repoImages */
      $repoImages = $this->getContainer()->get('doctrine')->getRepository(ProductImages::class);
      $images = $repoImages->findProductImages($fromCode);
      foreach($images as $image) {

        $productImage = new ProductImages();
        $productImage->setDaihyoSyohinCode($toCode);
        $productImage->setCode($image->getCode());

        $imageName = $imageUtil->createMainImageFilename($productImage->getDaihyoSyohinCode(), $productImage->getCode());
        $imageAddress = sprintf('https://image.rakuten.co.jp/plusnao/cabinet/%s/%s', $imageDir, $imageName);

        $productImage->setAddress($imageAddress);
        $productImage->setDirectory($imageDir);
        $productImage->setFilename($imageName);
        $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

        // 本体へも格納（後方互換）
        if (in_array($image->getCode(), [
          'p001', 'p002', 'p003', 'p004', 'p005', 'p006', 'p007', 'p008', 'p009'
        ])) {
          $toProduct->setImageFieldData('address', $productImage->getCode(), $imageAddress);
          $toProduct->setImageFieldData('directory', $productImage->getCode(), $imageDir);
          $toProduct->setImageFieldData('filename', $productImage->getCode(), $imageName);
        }

        // 画像ファイルの保存
        $imageSource = sprintf('%s/%s', $originalImageDir, $image->getFileDirPath());
        $tmpImage = new TmpProductImages();
        $tmpImage->setImage(file_get_contents($imageSource));
        $originalFilePath = $imageUtil->saveTmpProductImageToOriginal($productImage, $tmpImage);
        if (!$originalFilePath) {
          throw (new LeveledException('メイン画像ファイルの保存ができませんでした。'))->setLevel(LeveledException::ERROR);
        }

        // 画像ファイルの加工処理
        $imageUtil->convertOriginalFileToFixedFile($originalFilePath);
        $productImage->setMd5hash(hash_file('md5', $originalFilePath));

        // 類似画像チェック用 文字列作成・格納（上書き） → 不要

        $emMain->persist($productImage);
      }

      // -- Amazonメイン画像
      /** @var ProductImagesAmazonRepository $repoImageAmazon */
      $repoImageAmazon = $this->getContainer()->get('doctrine')->getRepository(ProductImagesAmazon::class);
      $amazonImage = $repoImageAmazon->getMainImage($fromCode);
      if ($amazonImage) {

        // R-Cabinet制限に合わせて格納ディレクトリ取得
        $directory = $imageUtil->findAvailableImageDirectory('amazon_main');
        $fileName = sprintf('%s.jpg', strtolower($toCode));

        $productImage = new ProductImagesAmazon();
        $productImage->setDaihyoSyohinCode($toCode);
        $productImage->setCode($amazonImage->getCode());

        $productImage->setDirectory($directory);
        $productImage->setFilename($fileName);

        // 画像URLはplusnaoホストの画像ディレクトリ
        $productImage->setAddress(sprintf('https://%s/amazon_images/%s', $this->getContainer()->getParameter('host_plusnao'), $productImage->getFileDirPath()));
        $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

        // 画像ファイルの保存
        $imageSource = sprintf('%s/%s', $amazonImageDir, $amazonImage->getFileDirPath());
        $tmpImage = new TmpProductImages();
        $tmpImage->setImage(file_get_contents($imageSource));

        $filePath = $imageUtil->saveTmpProductImageToAmazon($productImage, $tmpImage);
        if (!$filePath) {
          throw (new LeveledException('Amazonメイン画像ファイルの保存ができませんでした。'))->setLevel(LeveledException::ERROR);
        }

        // 画像ファイルの加工処理 -> 無し

        // 類似画像チェック用 文字列作成・格納（上書き） → 不要

        $emMain->persist($productImage);
      }

      // -- バリエーション画像
      /** @var ProductImagesVariationRepository $repoImageVariation */
      $repoImageVariation = $this->getContainer()->get('doctrine')->getRepository(ProductImagesVariation::class);
      $variationImages = $repoImageVariation->findByDaihyoSyohinCode($fromCode);
      foreach($variationImages as $image) {

        // レコードの新規作成
        $productImage = new ProductImagesVariation();
        $productImage->setDaihyoSyohinCode($toCode);
        $productImage->setCode($toProduct->getColorAxis());
        $productImage->setVariationCode($image->getVariationCode());
        $emMain->persist($productImage);

        $fileName = sprintf('%s-sw-%s%s.jpg', strtolower($toCode), $toProduct->getColorAxis(), $image->getVariationCode());
        $dirName = sprintf('%s/%s', strtolower(substr($toCode, 0, '1')), $toCode);
        $productImage->setDirectory($dirName);
        $productImage->setFilename($fileName);
        $productImage->setUpdated(new \DateTime()); // 最終更新日時の更新（大事）

        // 画像URLはplusnaoホストの画像ディレクトリ
        $productImage->setAddress(sprintf('https://%s/variation_images/%s', $this->getContainer()->getParameter('host_plusnao'), $productImage->getFileDirPath()));

        // 画像ファイルの保存
        $imageSource = sprintf('%s/%s', $variationImageDir, $image->getFileDirPath());
        $tmpImage = new TmpProductImages();
        $tmpImage->setImage(file_get_contents($imageSource));

        $filePath = $imageUtil->saveTmpProductImageToVariation($productImage, $tmpImage);
        if (!$filePath) {
          throw (new LeveledException('バリエーション画像ファイルの保存ができませんでした。'))->setLevel(LeveledException::ERROR);
        }

        $emMain->persist($productImage);
      }

      $emMain->flush();
      $dbMain->commit();

    } catch (\Exception $e) {

      if (isset($dbMain)) {
        $dbMain->rollBack();
      }

      // すでにlevel設定されていればそのままthrow
      if ($e instanceof LeveledException) {
        $logger->info('level : ' . $e->getLevel());
        throw $e;
      }

      // 通知されるERRORレベルでthrow
      $exception = new LeveledException($e->getMessage(), $e->getCode(), $e);
      $exception->setLevel(LeveledException::ERROR);
      throw $exception;
    }

    return $toCode;
  }

  /**
   * SKUに設定されている送料設定のうち、もっとも価格の高いものを商品マスタの送料設定に設定する。
   * 処理対象は、以下の2パターンのいずれかで指定を行う。
   * (1) tb_productchoiceitems の更新日時が指定期間内のもの
   * (2) 指定された代表商品コード
   * @param unknown $fromDate
   * @param unknown $toDate
   * @param unknown $daihyoSyohinCode
   */
  public function updateShippingdivisionFromSku($fromDate, $toDate, $daihyoSyohinCode, $logger) {

    // SQLの構成は以下の通り。
    // (1) もっとも内側のサブクエリ update_recordで、SKUに更新があった代表商品コードを特定。
    // (2) その外側のサブクエリmax_priceで、update_recordに出てきた代表商品コードを持つすべてのSKUから、代表商品コードごとに最も送料設定の価格が高いものを取得
    // （更新があったSKUに最高値が含まれるとは限らない。代表商品コード内の全SKUから取得）。
    // (3) SKUで使われている送料設定の中で、(2)で取得した価格を持つ送料設定を取得。
    // (4) (3)で取得した送料設定を、商品マスタの送料設定に設定
    $dbMain = null;

    try {
      $dbMain = $this->getConnection('main');
      $dbMain->beginTransaction();

      $condition = null;
      if ($fromDate) {
        $condition = " pci2.updated BETWEEN :fromDate AND :toDate ";
      } else {
        $condition = " pci2.daihyo_syohin_code = :daihyoSyohinCode ";
      }
      $sql = <<<EOD
          UPDATE tb_mainproducts p
            INNER JOIN (
              SELECT
                max_price.daihyo_syohin_code
                , sd.id
              FROM
                (
                  SELECT
                    pci.daihyo_syohin_code
                    , MAX(sd.price) price
                  FROM
                    tb_productchoiceitems pci
                    INNER JOIN (
                      SELECT distinct
                        pci2.daihyo_syohin_code
                      FROM
                        tb_productchoiceitems pci2
                      WHERE
                        $condition
                    ) AS update_record
                      ON update_record.daihyo_syohin_code = pci.daihyo_syohin_code
                    INNER JOIN tb_shippingdivision sd
                      ON pci.shippingdivision_id = sd.id
                  GROUP BY
                    pci.daihyo_syohin_code
                ) AS max_price
                INNER JOIN tb_productchoiceitems pci
                  ON pci.daihyo_syohin_code = max_price.daihyo_syohin_code
                INNER JOIN tb_shippingdivision sd
                  ON pci.shippingdivision_id = sd.id
              WHERE
                sd.price = max_price.price
              GROUP BY
                max_price.daihyo_syohin_code
                , sd.id
                , sd.name
          	ORDER BY sd.id ASC
            ) AS max_shippingdivision
              ON p.daihyo_syohin_code = max_shippingdivision.daihyo_syohin_code
          SET
            p.送料設定 = max_shippingdivision.id
EOD;
      $stmt = $dbMain->prepare($sql);

      if ($fromDate) {
        $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
      } else {
        $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
      }
      $stmt->execute();

      $dbMain->commit();

    } catch (\Exception $e) {

      if (isset($dbMain)) {
        $dbMain->rollBack();
      }

      // 通知されるERRORレベルでthrow
      $exception = new LeveledException($e->getMessage(), $e->getCode(), $e);
      $exception->setLevel(LeveledException::ERROR);
      throw $exception;
    }

    $logger->addDbLog($logger->makeDbLog(null, '更新完了', '対象' . $stmt->rowCount() . '件'));
  }

  /**
   * SKUに設定されているサイズのうち、もっとも容積率（縦×横×奥行）の大きいものを商品マスタのサイズに設定する。
   * 未設定（0）の項目があるSKUが存在する場合は、それが実際には最大サイズである可能性もあるため、
   * 代表商品のサイズも未設定（3辺とも0）とする。
   *
   * 処理対象は、以下の2パターンのいずれかで指定を行う。
   * (1) tb_productchoiceitems の更新日時が指定期間内のもの
   * (2) 指定された代表商品コード
   * @param \DateTime $fromDate
   * @param \DateTime $toDate
   * @param string $daihyoSyohinCode
   * @return int 更新件数
   */
  public function updateMainproductsSizeFromSku($fromDate, $toDate, $daihyoSyohinCode, $logger) {

    // SQLの構成は以下の通り。
    // (1) もっとも内側のサブクエリ update で、SKUに更新があった代表商品コードを特定。
    // (2) その外側のサブクエリmin_maxで、update_recordに出てきた代表商品コードを持つすべてのSKUから、代表商品コードごとに最も大きな容積と小さな容積を取得
    // （更新があったSKUに最高値が含まれるとは限らない。代表商品コード内の全SKUから取得）。
    // (3) その外側のサブクエリ sku_info で、SKUごとに、代表商品コードと、それに紐づく最も大きな容積のレコードのne_syohin_syohin_codeを特定。
    //   複数ある場合は ne_syohin_syohin_codeが小さい（文字コード順）のもの。それに最小値も追加。
    // (4) その外側のサブクエリ max_size_info で、該当のne_syohin_syohin_code の3辺を取得。
    // (5) (4)で取得したサイズで商品テーブルを更新
    $dbMain = null;

    try {
      $dbMain = $this->getConnection('main');
      $dbMain->beginTransaction();

      $condition = '';
      if ($fromDate) {
        $condition = " WHERE pci1.updated BETWEEN :fromDate AND :toDate ";
      } else if ($daihyoSyohinCode) {
        $condition = " WHERE pci1.daihyo_syohin_code = :daihyoSyohinCode ";
      }
      $sql = <<<EOD
          UPDATE tb_mainproducts m
          INNER JOIN
          (SELECT
            pci4.daihyo_syohin_code
            , CASE sku_info.min_size
              WHEN 0 THEN 0
              ELSE pci4.height
              END as height
            , CASE sku_info.min_size
              WHEN 0 THEN 0
              ELSE pci4.width
              END as width
            , CASE sku_info.min_size
              WHEN 0 THEN 0
              ELSE pci4.depth
              END as depth
          FROM
            tb_productchoiceitems pci4
            INNER JOIN (
              SELECT
                pci3.daihyo_syohin_code
                , MIN(pci3.ne_syohin_syohin_code) as target_ne_syohin_syohin_code
                , min_max.min_size
              FROM
                tb_productchoiceitems pci3
                INNER JOIN (
                  SELECT
                    pci2.daihyo_syohin_code
                    , MAX(pci2.height * pci2.width * pci2.depth) max_size
                    , MIN(pci2.height * pci2.width * pci2.depth) min_size
                  FROM
                    tb_productchoiceitems pci2
                    INNER JOIN (
                      SELECT distinct
                        pci1.daihyo_syohin_code
                      FROM
                        tb_productchoiceitems pci1
                      $condition
                    ) AS updated
                      ON updated.daihyo_syohin_code = pci2.daihyo_syohin_code
                  GROUP BY
                    pci2.daihyo_syohin_code
                ) as min_max
                  ON pci3.daihyo_syohin_code = min_max.daihyo_syohin_code
                  AND min_max.max_size = (pci3.height * pci3.width * pci3.depth)
              GROUP BY
                pci3.daihyo_syohin_code
                , min_max.max_size
                , min_max.min_size
            ) AS sku_info
              ON sku_info.target_ne_syohin_syohin_code = pci4.ne_syohin_syohin_code) AS max_size_info
          ON max_size_info.daihyo_syohin_code = m.daihyo_syohin_code
          SET m.height = max_size_info.height, m.width = max_size_info.width, m.depth=max_size_info.depth
EOD;
      $stmt = $dbMain->prepare($sql);

      if ($fromDate) {
        $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $stmt->bindValue(':toDate', $toDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
      } else if ($daihyoSyohinCode) {
        $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
      }
      $stmt->execute();

      $dbMain->commit();

    } catch (\Exception $e) {

      if (isset($dbMain)) {
        $dbMain->rollBack();
      }

      // 通知されるERRORレベルでthrow
      $exception = new LeveledException($e->getMessage(), $e->getCode(), $e);
      $exception->setLevel(LeveledException::ERROR);
      throw $exception;
    }

    $rowCount = $stmt->rowCount();
    $logger->addDbLog($logger->makeDbLog(null, '更新完了', '対象' . $rowCount . '件'));
    return $rowCount;
  }


  /**
   * SKUに設定されている重量のうち、もっとも重いものを商品マスタの重量に設定する。
   * 未設定（0）の項目があるSKUが存在しても、設定済みの中で最も重いものを商品マスタの重量とする。
   * 全て未設定であれば更新を行わない。
   *
   * このメソッドは、SkuSizeChangeRelatedUpdateCommand から呼ばれる1処理となる。
   * 処理対象は、tb_productchoiceitems_former_size で changed_flg=1 であるものとなる。
   */
  public function updateMainproductsWeightFromSku($logger) {

    // SQLの構成は以下の通り。
    // (1) もっとも内側のサブクエリ updated で、SKUに更新があった代表商品コードを特定。
    // (2) その外側のサブクエリmax_weightで、updatedに出てきた代表商品コードを持つすべてのSKUから、代表商品コードごとに最も重い重量を取得
    // （更新があったSKUに最高値が含まれるとは限らない。代表商品コード内の全SKUから取得）。
    // (3) (2)で取得した重量で商品テーブルを更新
    $dbMain = null;

    try {
      $dbMain = $this->getConnection('main');
      $dbMain->beginTransaction();

      $sql = <<<EOD
          UPDATE tb_mainproducts m
          INNER JOIN (
            SELECT pci1.daihyo_syohin_code, MAX(pci1.weight) weight
            FROM tb_productchoiceitems pci1
            INNER JOIN (
              SELECT DISTINCT pci.daihyo_syohin_code FROM tb_productchoiceitems pci
              LEFT JOIN tb_productchoiceitems_former_size pcifs
                ON pci.ne_syohin_syohin_code = pcifs.ne_syohin_syohin_code
              WHERE pcifs.changed_flg = 1
            ) updated ON pci1.daihyo_syohin_code = updated.daihyo_syohin_code
            GROUP BY pci1.daihyo_syohin_code
          ) max_weight ON m.daihyo_syohin_code = max_weight.daihyo_syohin_code
          SET m.weight = max_weight.weight
          WHERE max_weight.weight <> 0
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->execute();
      $dbMain->commit();

    } catch (\Exception $e) {

      if (isset($dbMain)) {
        $dbMain->rollBack();
      }

      // 通知されるERRORレベルでthrow
      $exception = new LeveledException($e->getMessage(), $e->getCode(), $e);
      $exception->setLevel(LeveledException::ERROR);
      throw $exception;
    }

    $logger->addDbLog($logger->makeDbLog(null, '更新完了', '対象' . $stmt->rowCount() . '件'));
  }

  /**
   * daihyoSyohinCodeのリストをin句でmainproduct取得
   * @param array $daihyoSyohinCodes
   * @return array
   */
  public function findByDaihyoSyohinCodes($daihyoSyohinCodes = [])
  {
    $qb = $this->createQueryBuilder('m');
    $qb->andWhere($qb->expr()->in('m.daihyoSyohinCode', $daihyoSyohinCodes));
    return $qb->getQuery()->getResult();
  }
  
  /**
   * 各種商品編集画面の上部に表示するための基本情報を取得する。
   * 増やして良いが減らさないこと。速度が悪化する内容の場合は別メソッドとすること。
   * @param string $daihyoSyohinCode
   * @return array
   */
  public function findDaihyoSyohinBaseInfoForEdit($daihyoSyohinCode)
  {
    $sql = <<<EOD
      SELECT
        m.set_flg AS setFlg,
        m.daihyo_syohin_code AS daihyoSyohinCode,
        c.deliverycode,
        m.登録日時 AS registrationDate,
        m.販売開始日 AS salesStartDate,
        c.endofavailability,
        m.daihyo_syohin_name AS daihyoSyohinName,
        m.picnameP1,
        m.picfolderP1,
        m.genka_tnk AS genkaTnk,
        c.baika_tnk AS baikaTnk
      FROM
        tb_mainproducts m
        INNER JOIN tb_mainproducts_cal c
          ON m.daihyo_syohin_code = c.daihyo_syohin_code
      WHERE
        m.daihyo_syohin_code = :daihyoSyohinCode;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode);
    $stmt->execute();
    $product = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($product) {
      // 表示用項目を追加
      $product['deliverycodeName'] = TbMainproductsCal::$DELIVERY_CODE_LIST[(int)$product['deliverycode']];
      $imageUrlParent = sprintf('//%s/images/', $this->getContainer()->getParameter('host_plusnao'));
      $product['imageUrl'] = TbMainproductsRepository::createImageUrl(
        $product['picfolderP1'],
        $product['picnameP1'],
        $imageUrlParent);
    }
      
    return $product;
  }

  /**
   * 在庫定数設定画面のための代表商品情報を返す。
   * @param string $daihyoSyohinCode
   * @return array
   */
  public function findDaihyoSyohinInfoForInventoryConstant($daihyoSyohinCode)
  {
    $sql = <<<EOD
      SELECT
        m.set_flg AS setFlg,
        m.daihyo_syohin_code AS daihyoSyohinCode,
        c.deliverycode,
        m.登録日時 AS registrationDate,
        m.daihyo_syohin_name AS daihyoSyohinName,
        m.picnameP1,
        m.picfolderP1,
        m.genka_tnk AS genkaTnk,
        c.baika_tnk AS baikaTnk,
        c.zaiko_teisu_reset_date AS resetDate,
        V.sireAddresses
      FROM
        tb_mainproducts m
        INNER JOIN tb_mainproducts_cal c
          ON m.daihyo_syohin_code = c.daihyo_syohin_code
        LEFT JOIN (
          SELECT
            va.daihyo_syohin_code,
            GROUP_CONCAT(va.sire_adress) AS sireAddresses
          FROM
            tb_vendoraddress va
            INNER JOIN tb_mainproducts m
              ON va.daihyo_syohin_code = m.daihyo_syohin_code
            INNER JOIN tb_vendormasterdata vm
              ON m.sire_code = vm.sire_code
          WHERE
            va.daihyo_syohin_code = :daihyoSyohinCode
            AND va.sire_code = m.sire_code
            AND va.stop = 0
            AND vm.取引状態 = 0
        ) V
          ON m.daihyo_syohin_code = V.daihyo_syohin_code
      WHERE
        m.daihyo_syohin_code = :daihyoSyohinCode;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode);
    $stmt->execute();
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }
  


  /**
   * 商品一覧画面のための代表商品情報を返す。
   * @param array $conditions 検索条件
   * @param int $userId ユーザーID
   * @param int $limit 1ページに表示する件数
   * @param int $page 現在ページ数
   * @return \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination
   */
  public function findDaihyoSyohinInfoForProductList($conditions, $userId, $limit, $page)
  {
    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $wheres = [];
    $params = [];
    // 条件：代表商品コード
    if ($conditions['daihyoSyohinCode']) {
      $wheres[] = 'm.daihyo_syohin_code = :daihyoSyohinCode';
      $params[':daihyoSyohinCode'] = $conditions['daihyoSyohinCode'];
    }
    // 条件：代表商品名
    if ($conditions['daihyoSyohinName']) {
      // 半角スペースで区切る為に、ここで全角スペース→半角スペースに変換
      $daihyoSyohinName = mb_convert_kana($conditions['daihyoSyohinName'], 's');
      $nameWheres = [];
      $names = explode(' ', $daihyoSyohinName);
      $i = 1;
      foreach ($names as $name) {
        // 空文字をあいまい検索の対象から除外
        if ($name === '') {
          continue;
        }
        $name = '%' . $name . '%';
        $nameWheres[] = 'm.daihyo_syohin_name LIKE :daihyoSyohinName' . $i;
        $params[':daihyoSyohinName' . $i] = $name;
        $i++;
      }
      if ($nameWheres) {
        $wheres[] = '(' . implode(' AND ', $nameWheres) . ')';
      }
    }
    // 条件：仕入先URL
    if ($conditions['sireAdress']) {
      $wheres[] = <<<EOD
        EXISTS (
          SELECT
            *
          FROM
            tb_vendoraddress va
          WHERE
            m.daihyo_syohin_code = va.daihyo_syohin_code
            AND va.sire_adress LIKE :sireAdress
        )
EOD;
      $params[':sireAdress'] = '%' . $conditions['sireAdress'] . '%';
    }
    // 条件：販売開始日From
    if ($conditions['salesStartDateFrom']) {
      $wheres[] = 'm.販売開始日 >= :salesStartDateFrom';
      $params[':salesStartDateFrom'] = $conditions['salesStartDateFrom'];
    }
    // 条件：販売開始日To
    if ($conditions['salesStartDateTo']) {
      $wheres[] = 'm.販売開始日 <= :salesStartDateTo';
      $params[':salesStartDateTo'] = $conditions['salesStartDateTo'];
    }
    // 条件：登録日From
    if ($conditions['registrationDateFrom']) {
      $registrationDateFrom = new \DateTime($conditions['registrationDateFrom']);
      $registrationDateFrom->setTime(0, 0, 0);
      $wheres[] = 'm.登録日時 >= :registrationDateFrom';
      $params[':registrationDateFrom'] = $registrationDateFrom->format('Y-m-d H:i:s');
    }
    // 条件：登録日To
    if ($conditions['registrationDateTo']) {
      $registrationDateTo = new \DateTime($conditions['registrationDateTo']);
      $registrationDateTo->setTime(23, 59, 59);
      $wheres[] = 'm.登録日時 <= :registrationDateTo';
      $params[':registrationDateTo'] = $registrationDateTo->format('Y-m-d H:i:s');
    }
    // 条件：在庫定数設定可
    if ($conditions['configurable']) {
      // セット商品ではなく、
      // 検索したユーザが担当する商品か、担当者がおらず稼働中でもない商品に限定する
      $wheres[] = 'm.set_flg = 0';
      $wheres[] = <<<EOD
      ( 
        myAccount.daihyo_syohin_code IS NOT NULL 
        OR ( 
          A.salesAccounts IS NULL 
          AND s.active_flg = 0 
            AND NOT EXISTS ( 
              SELECT
                tp.ne_syohin_syohin_code 
              FROM
                tb_productchoiceitems tp 
                INNER JOIN tb_product_order_calculation tpoc 
                  ON tp.ne_syohin_syohin_code = tpoc.ne_syohin_syohin_code 
              WHERE
                tp.daihyo_syohin_code = m.daihyo_syohin_code 
                AND (tpoc.発注点 != 0 OR tpoc.季節在庫定数 != 0)
          )
        )
      )
EOD;
    }
    // 条件：自分が担当者
    if ($conditions['isMyProduct']) {
      $wheres[] = 'myAccount.daihyo_syohin_code IS NOT NULL';
    }
    // 条件：受発注可能
    if ($conditions['orderable']) {
      $wheres[] = 's.orderable_flg <> 0';
    }
    // 条件：在庫定数ゼロ
    if ($conditions['zaikoTeisuZero']) {
      $wheres[] = 's.zaiko_teisu_exist_flg = 0';
    }
    // 条件：カテゴリ
    if ($conditions['category']) {
      $wheres[] = 'CONCAT(s.big_category, s.mid_category) LIKE :category ';
      $params[':category'] = '%' . $conditions['category'] . '%';
    }
    // 条件：deliverycode
    if ($conditions['deliverycodes']) {
      $deliverycodesStr = implode(', ', $conditions['deliverycodes']);
      $wheres[] = "c.deliverycode IN ({$deliverycodesStr})";
    }
    // 条件：sireAddressNecessity（仕入先アドレスの要否）
    if ($conditions['sireAddressNecessity'] !== '') {
      /* セット商品、仕入先アドレス既登録の商品、登録不要の商品を抽出 */
      $addSql = <<<EOD
        (m2.set_flg <> 0
          OR (
            (vm.sire_address_need_flg = 0 OR va.daihyo_syohin_code IS NOT NULL)
            AND vm.sire_code IS NOT NULL
          )
        )
EOD;
      /* 通常商品で、仕入先アドレスの登録が必要なのに登録のない商品を抽出 */
      if ($conditions['sireAddressNecessity']) {
        $addSql = <<<EOD
          (m2.set_flg = 0
            AND (
              (vm.sire_address_need_flg <> 0 AND va.daihyo_syohin_code IS NULL)
              OR vm.sire_code IS NULL
            )
          )
EOD;
      }
      $wheres[] = <<<EOD
        EXISTS (
          SELECT
            m2.daihyo_syohin_code
          FROM
            tb_mainproducts m2
            LEFT JOIN tb_vendormasterdata vm
              ON m2.sire_code = vm.sire_code
              AND vm.取引状態 = 0
            LEFT JOIN tb_vendoraddress va
              ON m2.daihyo_syohin_code = va.daihyo_syohin_code
              AND va.stop = 0 AND vm.sire_code = va.sire_code
          WHERE
            {$addSql}
            AND m.daihyo_syohin_code = m2.daihyo_syohin_code
        )
EOD;
    }
    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }

    $sqlSelect = <<<EOD
      SELECT
        m.daihyo_syohin_code AS daihyoSyohinCode,
        m.set_flg AS setFlg,
        s.big_category AS bigCategory,
        s.mid_category AS midCategory,
        s.sire_code AS sireCode,
        s.sire_name AS sireName,
        m.picnameP1 AS imageFile,
        m.picfolderP1 AS imageDir,
        c.deliverycode,
        s.orderable_flg AS orderableFlg,
        s.baika_tanka AS baikaTanka,
        m.販売開始日 AS startofavailability,
        m.登録日時 AS registrationDate,
        c.zaiko_teisu_reset_date AS zaikoTeisuResetDate,
        c.endofavailability,
        A.salesAccounts,
        CASE
          WHEN myAccount.daihyo_syohin_code IS NOT NULL THEN 1
          ELSE 0
        END AS staffFlg,
        s.active_flg AS activeFlg
EOD;

    $sqlBody = <<<EOD
      FROM
        tb_mainproducts m
        INNER JOIN tb_mainproducts_cal c
          ON m.daihyo_syohin_code = c.daihyo_syohin_code
        INNER JOIN tb_mainproducts_sales_status s
          ON m.daihyo_syohin_code = s.daihyo_syohin_code
        LEFT JOIN (
          SELECT
            a.daihyo_syohin_code,
            GROUP_CONCAT(DISTINCT u.username ORDER BY a.apply_start_date) AS salesAccounts
          FROM
            tb_product_sales_account a
            INNER JOIN symfony_users u
              ON a.user_id = u.id
          WHERE
            a.status = :accountRegistration
            AND a.apply_start_date <= :today
            AND (a.apply_end_date IS NULL OR a.apply_end_date >= :today)
          GROUP BY
            a.daihyo_syohin_code
        ) A
          ON m.daihyo_syohin_code = A.daihyo_syohin_code
        LEFT JOIN (
          SELECT DISTINCT
            daihyo_syohin_code
          FROM
            tb_product_sales_account a
          WHERE
            a.user_id = :userId
            AND a.status = :accountRegistration
            AND a.apply_start_date <= :today
            AND (a.apply_end_date IS NULL OR a.apply_end_date >= :today)
        ) myAccount
          ON myAccount.daihyo_syohin_code = m.daihyo_syohin_code
      WHERE
        1
        {$addWheres}
EOD;

    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('daihyoSyohinCode', 'daihyoSyohinCode', 'string');
    $rsm->addScalarResult('setFlg', 'setFlg', 'integer');
    $rsm->addScalarResult('bigCategory', 'bigCategory', 'string');
    $rsm->addScalarResult('midCategory', 'midCategory', 'string');
    $rsm->addScalarResult('sireCode', 'sireCode', 'string');
    $rsm->addScalarResult('sireName', 'sireName', 'string');
    $rsm->addScalarResult('imageFile', 'imageFile', 'string');
    $rsm->addScalarResult('imageDir', 'imageDir', 'string');
    $rsm->addScalarResult('deliverycode', 'deliverycode', 'integer');
    $rsm->addScalarResult('orderableFlg', 'orderableFlg', 'integer');
    $rsm->addScalarResult('baikaTanka', 'baikaTanka', 'integer');
    $rsm->addScalarResult('startofavailability', 'startofavailability', 'string');
    $rsm->addScalarResult('endofavailability', 'endofavailability', 'string');
    $rsm->addScalarResult('salesAccounts', 'salesAccounts', 'string');
    $rsm->addScalarResult('registrationDate', 'registrationDate', 'string');
    $rsm->addScalarResult('zaikoTeisuResetDate', 'zaikoTeisuResetDate', 'string');
    $rsm->addScalarResult('staffFlg', 'staffFlg', 'integer');
    $rsm->addScalarResult('activeFlg', 'activeFlg', 'integer');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    $query->setParameter(':userId', $userId);
    $query->setParameter(':today', (new \DateTime())->format('Y-m-d'));
    $query->setParameter(
      ':accountRegistration',
      TbProductSalesAccount::STATUS_REGISTRATION
    );
    foreach($params as $k => $v) {
      $query->setParameter($k, $v);
    }

    $sortColums = [];
    switch ($conditions['sortKey']) {
      case 'daihyoSyohinCode':
        $sortColums[] = 'm.daihyo_syohin_code';
        break;
      case 'deliverycode':
        $sortColums[] = 'c.deliverycode';
        break;
      case 'orderableFlg':
        $sortColums[] = 's.orderable_flg';
        break;
      case 'baikaTanka':
        $sortColums[] = 's.baika_tanka';
        break;
      case 'saleDate':
        $sortColums[] = 'm.販売開始日';
        $sortColums[] = 'c.endofavailability';
        break;
      case 'registrationDate':
        $sortColums[] = 'm.登録日時';
        break;
      case 'zaikoTeisuResetDate':
        $sortColums[] = 'c.zaiko_teisu_reset_date';
        break;
      case 'salesAccounts':
        $sortColums[] = 'A.salesAccounts';
        break;
    }
    $sort = $conditions['sortDesc'] ? 'DESC' : 'ASC';
    $orders = [];
    foreach ($sortColums as $colum) {
      $orders[$colum] = $sort;
    }
    $query->setOrders(array_merge([], $orders));

    /** @var \Knp\Component\Pager\Paginator $paginator */
    $paginator  = $this->getContainer()->get('knp_paginator');

    return $paginator->paginate($query, $page, $limit);
  }

  /**
   * 指定した代表商品コードのモール商品メイン情報を返す
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array
   */
  public function findMallProductMainInfo($daihyoSyohinCode)
  {
    /** @var TbMainproductsRepository $repo */
    $repo = $this->getContainer()->get('doctrine')->getRepository("MiscBundle:TbMainproducts");
    /** @var TbMainproducts $mainInfo */
    $mainInfo = $repo->find($daihyoSyohinCode);

    return [
      // 真偽値のカラムでも、->set○○Flg('0')のように更新した直後に、getterで取得すると、
      // true/false ではなく、'-1'/'0'になる模様。
      // そのままJSに渡すと不都合('0'もtrue扱い)なので、ここで確実に真偽値に変換。
      // ※更新可能性が無い項目は変換の必要はないが、区別が面倒なので全て(bool)とする
      'daihyoSyohinCode' => $mainInfo->getDaihyoSyohinCode(),
      'imageDir' => $mainInfo->getImageP1Directory(),
      'imageFile' => pathinfo($mainInfo->getImageP1Filename())['filename'],
      'setFlg' => (bool)$mainInfo->getSetFlg(),
      'daihyoSyohinName' => $mainInfo->getDaihyoSyohinName(),
      'originalPriceFlg' => (bool)$mainInfo->getPriceUnlinkedCheck(),
      'guerrillaSaleFlg' => (bool)$mainInfo->getManualGuerrillaSale(),
      'mallPriceFlg' => (bool)$mainInfo->getCal()->getMallPriceFlg(),
      'genkaTnk' => $mainInfo->getGenkaTnk(),
      'baikaTnk' => $mainInfo->getCal()->getBaikaTnk(),
      'shippingDivission' => $mainInfo->getShippingdivision()->getName(),
      'adultCheckStatus' => $mainInfo->getCal()->getAdultCheckStatus(),
      'weight' => $mainInfo->getWeight(),
      'depth' => $mainInfo->getDepth(),
      'width' => $mainInfo->getWidth(),
      'height' => $mainInfo->getHeight(),
    ];
  }

  /**
   * 指定した代表商品コードのモール商品店舗毎の情報を返す
   * @param string $daihyoSyohinCode 代表商品コード
   * @return array
   */
  public function findMallProductByShopInfo($daihyoSyohinCode)
  {
    $entities = [
      'TbRakuteninformation',
      'TbRakutenMottoInformation',
      'TbRakutenLaforestInformation',
      'TbRakutenDolcissimoInformation',
      'TbRakutenGekiplaInformation',
      'TbYahooInformation',
      'TbYahooKawaInformation',
      'TbYahooOtoriyoseInformation',
      'TbBiddersinfomation',
      'TbShoplistInformation',
      'TbPpmInformation',
      'TbAmazoninfomation',
      'TbCubeInformation',
      'TbQtenInformation',
    ];

    // getMallInfoForMallProduct() の各getterでは、
    // 真偽値のカラムでも、->set○○Flg('0')のように更新した直後に取得すると、
    // true/false ではなく、'-1'/'0'になる模様。
    // そのままJSに渡すと不都合('0'もtrue扱い)なので、確実に真偽値に変換。
    // ※更新可能性が無い項目は変換の必要はないが、区別が面倒なので全て(bool)とする
    return array_map(function ($entity) use ($daihyoSyohinCode) {
      $repo = $this->getContainer()->get('doctrine')->getRepository("MiscBundle:$entity");
      $info = $repo->find($daihyoSyohinCode);
      return array_merge(isset($info) ? $info->getMallInfoForMallProduct() : [], ['entity' => $entity]);
    }, $entities);
  }

  /**
   * 指定した代表商品コードのモール商品メイン情報を更新する。
   * @param string $daihyoSyohinCode 代表商品コード
   * @param array $mainModifiedList メイン情報の変更分のリスト
   */
  public function updateMallProductMainInfo($daihyoSyohinCode, $mainModifiedList)
  {
    /** @var TbMainproductsRepository $repo */
    $repo = $this->getContainer()->get('doctrine')->getRepository("MiscBundle:TbMainproducts");
    /** @var TbMainproducts $mainInfo */
    $mainInfo = $repo->find($daihyoSyohinCode);

    foreach ($mainModifiedList as $key => $value) {
      switch ($key) {
        case 'daihyoSyohinName':
          $mainInfo->setDaihyoSyohinName($value);
          break;
        case 'originalPriceFlg':
          $mainInfo->setPriceUnlinkedCheck($value);
          break;
        case 'guerrillaSaleFlg':
          $mainInfo->setManualGuerrillaSale($value);
          break;
        case 'mallPriceFlg':
          $mainInfo->getCal()->setMallPriceFlg($value);
          break;
        case 'genkaTnk':
          $mainInfo->setGenkaTnk($value);
          break;
        case 'baikaTnk':
          $mainInfo->getCal()->setBaikaTnk($value);
          break;
      }
    }
    $this->getEntityManager()->flush();
  }

  /**
   * 指定した代表商品コードのモール店舗毎の情報を更新する。
   * @param string $daihyoSyohinCode 代表商品コード
   * @param array $byShopModifiedList 店舗毎の変更分のリスト
   */
  public function updateMallProductByShopInfo($daihyoSyohinCode, $byShopModifiedList)
  {
    foreach ($byShopModifiedList as $entity => $modified) {
      $repo = $this->getContainer()->get('doctrine')->getRepository("MiscBundle:$entity");
      $info = $repo->find($daihyoSyohinCode);
      foreach ($modified as $key => $value) {
        $nullableNumericColumns = ['prRate'];
        if (in_array($key, $nullableNumericColumns, true)) {
          if ($value === '') {
            $value = null;
          }
        }
        // $info->set~() メソッド呼び出し
        $info->updateMallProductInfo($key, $value);
      }
      $this->getEntityManager()->flush();
    }
  }

  /**
   * 在庫定数リセット日を更新する
   * @param string $daihyoSyohinCode 代表商品コード
   * @param DateTime|NULL $resetDate 在庫定数リセット日
   */
  public function updateInventoryConstantResetDate($daihyoSyohinCode, $resetDate)
  {
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal
      SET zaiko_teisu_reset_date = :zaikoTeisuResetDate
      WHERE daihyo_syohin_code = :daihyoSyohinCode
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    if ($resetDate) {
      $stmt->bindValue(':zaikoTeisuResetDate', $resetDate->format('Y-m-d'), \PDO::PARAM_STR);
    } else {
      $stmt->bindValue(':zaikoTeisuResetDate', NULL, \PDO::PARAM_NULL);
    }
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * 指定期間内に販売していた全商品数と、担当者不在の代表商品数を返す。
   *
   * @param array $conditions 検索条件
   * @return array 以下のキーを持つ連想配列
   *  'total' => int 全体期間内関連商品数,
   *  'noAccount' => int 担当者なし期間内関連商品数,
   */
  public function findProductNumInPeriod($conditions)
  {
    $productNum = [
      'total' => 0,
      'noAccount' => 0,
    ];

    $targetDateFrom = $conditions['targetDateFrom'];
    $targetDateTo = $conditions['targetDateTo'];
    $immediateProducts = isset($conditions['immediateProducts']);
    if ($targetDateTo !== '' && $targetDateFrom > $targetDateTo) {
      return $productNum;
    }

    $wheres = [];
    $params = [];
    // 条件：対象日時From
    if ($targetDateFrom) {
      $wheres[] = '(c.endofavailability IS NULL OR c.endofavailability >= :targetDateFrom)';
      $params[':targetDateFrom'] = $targetDateFrom . ' 00:00:00';
    }
    // 条件：対象日時To
    if ($targetDateTo) {
      $wheres[] = 'm.販売開始日 <= :targetDateTo';
      $params[':targetDateTo'] = $targetDateTo;
    }
    // 条件：即納商品(0:即納 1:一部即納)
    if ($immediateProducts) {
      $wheres[] = 'c.deliverycode in ( :deliveryCodeReady, :deliveryCodeReadyPartially)';
      $params[':deliveryCodeReady'] = TbMainproductsCal::DELIVERY_CODE_READY;
      $params[':deliveryCodeReadyPartially'] = TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY;
    }
    $addWheres = '';
    if ($wheres) {
      $addWheres = ' AND ' . implode(' AND ', $wheres);
    }
    $sql = <<<EOD
      SELECT
        count(m.daihyo_syohin_code)
      FROM
        tb_mainproducts m
        INNER JOIN tb_mainproducts_cal c
          ON m.daihyo_syohin_code = c.daihyo_syohin_code
      WHERE
        1
        {$addWheres};
EOD;
    $dbMain = $this->getConnection('main');
    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    $productNum['total'] = $stmt->fetchColumn();

    /** @var TbProductSalesAccountRepository $aRepo */
    $aRepo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbProductSalesAccount');

    // タスク適用開始日の指定有無に応じて、担当者なし実績を求める
    if ($conditions['applyStartDateFrom'] || $conditions['applyStartDateTo']) {
      // 有: 指定期間の担当者なし実績を直接取得する
      $productNum['noAccount'] = $aRepo->findNoAccountProductNumLimitSaleStart($conditions);
    } else {
      // 無: 担当者実績合計を求め、全体から差し引くことで算出する
      $accountProductNumTotal = $aRepo->findAccountProductNumTotal($conditions);
      $productNum['noAccount'] = $productNum['total'] - $accountProductNumTotal;
    }

    return $productNum;
  }
  public function searchMallProductByCode($daihyoSyohinCode = null, $limit = 20, $page = 1, &$count)
  {
    ini_set('memory_limit', '-1');
    $qb = $this->createQueryBuilder('amp');

    if (!empty($daihyoSyohinCode)) {
      $qb->where('mp.daihyoSyohinCode LIKE :daihyoSyohinCode')
        ->setParameter('daihyoSyohinCode', '%'.$daihyoSyohinCode.'%');
    }
    $count = count($qb->getQuery()->getResult());
    return $qb->setMaxResults($limit)
      ->setFirstResult($limit * ($page - 1))
      ->getQuery()
      ->getArrayResult();
  }
}
