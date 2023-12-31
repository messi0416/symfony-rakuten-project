<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\SymfonyUserYahooAgent;
use MiscBundle\Entity\TbYahooAgentApiAuth;

/**
 * TbYahooApiAuthRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TbYahooAgentApiAuthRepository extends BaseRepository
{
  /**
   * 最新のAPI認証情報を取得する
   * -> access_token の expiration で判定
   * @param SymfonyUserYahooAgent $yahooAgent
   * @return TbYahooAgentApiAuth
   */
  public function findLatestAuth($yahooAgent)
  {
    $result = null;

    $qb = $this->createQueryBuilder('auth');
    $qb->where("auth.access_token <> ''")
       ->andWhere("auth.refresh_token <> ''")
       ->andWhere("auth.refresh_token <> ''")
       ->andWhere("auth.shop_code = :shopCode")->setParameter(':shopCode', $yahooAgent->getShopCode())
       ->orderBy("auth.expiration", 'DESC')
       ->setMaxResults(1);

    $query = $qb->getQuery();
    $list = $query->getResult();
    if ($list) {
      $result = array_shift($list);
    }

    return $result;
  }

}
