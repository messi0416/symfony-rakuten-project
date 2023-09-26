<?php

namespace MiscBundle\Entity\Repository;

/**
 * TbTeamRepository
 */
class TbTeamRepository extends BaseRepository
{
  /**
   * 削除されていない(delete_flgがfalse)teamすべて取得
   */
  public function findNotDeleteTeams()
  {
    $qb = $this->createQueryBuilder('t')
      ->where('t.deleteFlg = 0')
      ->orderBy('t.id');
    return $qb->getQuery()->getResult();
  }
}
