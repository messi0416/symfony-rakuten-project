<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbYahooOtoriyoseAccessLog;
use \Doctrine\ORM\OptimisticLockException;

class TbYahooOtoriyoseAccessLogRepository extends BaseRepository
{

  /**
   * @param \DateTime $date
   * @return boolean
   */
  public function isUploaded($date)
  {
    return $this->findOneBy(['targetDate' => $date]) !== null;
  }

  /**
   * @param array<TbYahooOtoriyoseAccessLog> $otoriyoseList
   * @throws OptimisticLockException
   */
  public function storeList($otoriyoseList)
  {
    $em = $this->getEntityManager();
    foreach ($otoriyoseList as $row) {
      $em->persist($row);
    }
    $em->flush();
  }

  /**
   * @param \DateTime[] $dates
   * @throws OptimisticLockException
   */
  public function deleteAllByDates($dates)
  {
    $dateList = array_map(function ($date) {
      return $date->format('Y-m-d');
    }, $dates);
    $em = $this->getEntityManager();
    $qb = $this->createQueryBuilder('yo');
    $qb->delete()->where($qb->expr()->in('yo.targetDate', $dateList));
    $qb->getQuery()->getResult();
    $em->flush();
  }
}
