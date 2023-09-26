<?php

namespace MiscBundle\Entity\Repository;

/**
 * TbProductSalesTaskRepository
 */
class TbProductSalesTaskRepository extends BaseRepository
{
  /**
   * 削除されていない(delete_flgがfalse)taskすべて取得
   */
  public function findNotDeleteTasks()
  {
    $qb = $this->createQueryBuilder('t')
      ->where('t.deleteFlg = 0')
      ->orderBy('t.id');
    return $qb->getQuery()->getResult();
  }
}
