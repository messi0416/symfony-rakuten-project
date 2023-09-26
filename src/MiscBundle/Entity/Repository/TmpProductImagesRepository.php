<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\TmpProductImages;

/**
 */
class TmpProductImagesRepository extends BaseRepository
{
  /**
   * 一時画像 一括取得
   * @param $imageKey
   * @param $daihyoSyohinCode
   * @return TmpProductImages[]
   */
  public function findByImageKey($imageKey, $daihyoSyohinCode = null)
  {
    $qb = $this->createQueryBuilder('t');

    $qb->where('t.image_key = :imageKey')
       ->setParameter('imageKey', $imageKey);

    if ($daihyoSyohinCode) {
      $qb->andWhere('t.daihyo_syohin_code = :daihyoSyohinCode')
        ->setParameter('daihyoSyohinCode', $daihyoSyohinCode);
    };

    $qb->orderBy('t.image_code');

    return $qb->getQuery()->getResult();
  }

  /**
   * 一時画像 一括削除
   * @param $imageKey
   * @throws \Doctrine\DBAL\DBALException
   */
  public function deleteByImageKey($imageKey)
  {
    $sql = <<<EOD
      DELETE
      FROM tmp_product_images
      WHERE image_key = :imageKey
EOD;
    $stmt = $this->getConnection('tmp')->prepare($sql);
    $stmt->bindValue(':imageKey', $imageKey, \PDO::PARAM_STR);
    $stmt->execute();
  }


}
