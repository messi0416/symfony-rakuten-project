<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\SymfonyUserYahooAgent;

/**
 * SymfonyUserYahooAgentRepository
 */
class SymfonyUserYahooAgentRepository extends BaseRepository
{
  /**
   * 有効なアカウント一覧取得
   * @return SymfonyUserYahooAgent[]
   * @throws \Doctrine\ORM\NonUniqueResultException
   */
  public function getActiveAccounts()
  {
    $qb = $this->createQueryBuilder('u')
      ->where('u.is_active <> 0')
      ->orderBy('u.id', 'ASC');

    return $qb->getQuery()->getResult();
  }

  /**
   * shop_code でアカウント取得（代表アカウント：１件）
   * @param string $shopCode
   * @return SymfonyUserYahooAgent
   * @throws \Doctrine\ORM\NonUniqueResultException
   */
  public function getActiveShopAccountByShopCode($shopCode)
  {
    $qb = $this->createQueryBuilder('u')
      ->where('u.is_active <> 0')
      ->andWhere('u.shop_code = :shopCode')->setParameter(':shopCode', $shopCode, \PDO::PARAM_STR)
      ->orderBy('u.id', 'ASC');

    $result = $qb->getQuery()->getResult();
    return $result ? $result[0] : null;
  }


  /**
   * shop_code でアカウント取得
   * @param string $shopCode
   * @return SymfonyUserYahooAgent[]
   * @throws \Doctrine\ORM\NonUniqueResultException
   */
  public function getActiveAccountByShopCode($shopCode)
  {
    $qb = $this->createQueryBuilder('u')
      ->where('u.is_active <> 0')
      ->andWhere('u.shop_code = :shopCode')->setParameter(':shopCode', $shopCode, \PDO::PARAM_STR)
      ->orderBy('u.id', 'ASC');

    return $qb->getQuery()->getResult();
  }

  /**
   * shop_code に対応したアカウントか
   * @param int $id
   * @param string $shopCode
   * @return bool
   * @throws \Doctrine\ORM\NonUniqueResultException
   */
  public function isValidAccountForShopCode($id, $shopCode)
  {
    $qb = $this->createQueryBuilder('u')
          ->select('COUNT(u.id) AS cnt')
          ->where('u.is_active <> 0')
          ->andWhere('u.id = :id')->setParameter(':id', intval($id), \PDO::PARAM_INT)
          ->andWhere('u.shop_code = :shopCode')->setParameter(':shopCode', $shopCode, \PDO::PARAM_STR);

    $result = $qb->getQuery()->getSingleScalarResult();
    return $result > 0;
  }

}
