<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use MiscBundle\Entity\ProductImages;
use MiscBundle\Entity\TbMainproductsCal;

/**
 */
class ProductImagesRepository extends BaseRepository
{
  /**
   * 画像一覧を取得
   * @param string $daihyoSyohinCode
   * @param array $codeList
   * @return ProductImages[]
   */
  public function findProductImagesAll()
  {
    $qb = $this->createQueryBuilder('pi');
    $qb->select('pi.daihyo_syohin_code, pi.address');

    return $qb->getQuery()->getResult();
  }

  /**
   * 商品の画像一覧を取得
   * @param string $daihyoSyohinCode
   * @param array $codeList
   * @return ProductImages[]
   */
  public function findProductImages($daihyoSyohinCode, $codeList = null)
  {
    $qb = $this->createQueryBuilder('pi');
    $qb->andWhere('pi.daihyo_syohin_code = :daihyoSyohinCode')->setParameter(':daihyoSyohinCode', $daihyoSyohinCode);
    if (is_array($codeList)) {
      $qb->andWhere($qb->expr()->in('pi.code', $codeList));
    }

    $qb->addOrderBy('pi.code', 'ASC');

    return $qb->getQuery()->getResult();
  }

  /**
   * Yahooのアップロード対象商品画像を抽出し、エンティティの配列として返却する。
   * 対象画像は、ExportCsvYahooCommand, ExportCsvYahooOtoriyoseCommand の出品対象に合わせる。
   * 　1 出品フラグがonの商品
   * 　2 権利侵害・アダルト審査が「ブラック」「グレー」「未審査」ではない商品
   * 　3 Yahooへ既登録済みの全商品で1、2に該当する完売3年以内の全商品
   * 
   * @param $targetMall DbCommonUtil で定数定義されたモールコード
   * @return ProductImages[]
   */
  public function findYahooNewImages($targetMall)
  {
    /** @var \MiscBundle\Util\DbCommonUtil $dbUtil */
    $dbUtil = $this->getContainer()->get('misc.util.db_common');
    $infomationTable = $dbUtil->getMallTableName($targetMall);
    
    $db = $this->getEntityManager()->getConnection();
    $codeList = $this->getYahooImageCodeList();
    foreach($codeList as $i => $code) {
      $codeList[$i] = $db->quote($code, \PDO::PARAM_STR);
    }
    $codeListStr = implode(', ', $codeList);
    
    $exportLimit = new \DateTime();
    $exportLimit->modify('-3 year'); // 販売終了から三年間

    // 画像一覧取得
    $sql = <<<EOD
      SELECT
        pi.*
      FROM product_images pi
      INNER JOIN {$infomationTable} i ON pi.daihyo_syohin_code = i.daihyo_syohin_code
      INNER JOIN tb_mainproducts m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal cal ON cal.daihyo_syohin_code = m.daihyo_syohin_code
      WHERE 
        IFNULL(m.YAHOOディレクトリID, '') <> ''
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND i.registration_flg <> 0
        AND cal.adult_check_status NOT IN (:adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone)
        AND (cal.endofavailability IS NULL OR cal.endofavailability >= :exportLimit)
        AND pi.code IN ( {$codeListStr} )
        AND (i.last_image_upload_datetime IS NULL OR pi.updated > i.last_image_upload_datetime)
      ORDER BY pi.daihyo_syohin_code , pi.code
EOD;

    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $rsm = new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:ProductImages', 'pi');

    $query = $em->createNativeQuery($sql, $rsm);
    $query->setParameter('deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $query->setParameter('adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK);
    $query->setParameter('adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY);
    $query->setParameter('adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE);
    $query->setParameter('exportLimit', $exportLimit->format('Y-m-d 00:00:00'));
    return $query->getResult();
  }

  /**
   * 商品画像 更新日時更新処理
   * @param string $daihyoSyohinCode
   * @param \DateTimeInterface|null $dateTime
   */
  public function updateUpdated($daihyoSyohinCode, $dateTime = null)
  {
    if (!$dateTime) {
      $dateTime = new \DateTime();
    }

    $db = $this->getEntityManager()->getConnection();

    $sql = <<<EOD
      UPDATE
      product_images pi
      SET pi.updated = :updated
      WHERE pi.daihyo_syohin_code = :daihyoSyohinCode
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':updated', $dateTime->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
    $stmt->bindValue(':daihyoSyohinCode', $daihyoSyohinCode, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * Yahoo アップロード対象 画像コード一覧取得
   */
  public function getYahooImageCodeList()
  {
    $codeList = [];
    for ($i = 1; $i <= 20; $i++) { // p1 ～ p20 まで (無印から _20 までしかYahooは受け付けない)
      $codeList[] = sprintf('p%03d', $i);
    }
    return $codeList;
  }
  
  /**
   * Yahoo アップロード対象 画像コード一覧を、IN句で使用できる形とした文字列を返却する。
   * 元のコード一覧は、getYahooImageCodeList() で取得したもの
   */
  public function getYahooImageCodeListStr() {
    $db = $this->getEntityManager()->getConnection();
    $codeList = $this->getYahooImageCodeList();
    foreach($codeList as $i => $code) {
      $codeList[$i] = $db->quote($code, \PDO::PARAM_STR);
    }
    return implode(', ', $codeList);
  }

  /**
   * `product_images`中に楽天CabinetAPIから取得したファイル一覧のものを取得する。
   */
  public function getExistFilenamesFromArray($files = [])
  {
    if (count($files) === 0) {
      return [];
    }

    $folderName = $files[0]['FolderName'];
    $filenames = [];
    foreach ($files as $file) {
      $filenames[] = $file['FilePath'];
    }

    $qb = $this->createQueryBuilder('pi');
    $qb->select('pi.filename');
    $qb->andWhere('pi.directory = :directory')->setParameter(':directory', $folderName);
    $qb->andWhere($qb->expr()->in('pi.filename', $filenames));

    $qb->addOrderBy('pi.code', 'ASC');

    $result = $qb->getQuery()->getResult();
    
    $filenames = [];
    foreach ($result as $file) {
      $filenames[] = $file['filename'];
    }

    return $filenames;
  }
}
