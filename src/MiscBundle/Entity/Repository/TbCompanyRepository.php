<?php

namespace MiscBundle\Entity\Repository;
use Doctrine\Common\Collections\Collection;
use MiscBundle\Entity\TbCompany;

/**
 * TbCompanyRepository
 */
class TbCompanyRepository extends BaseRepository
{
  const DEFAULT_COMPANY_ID = -1; // 削除不可 初期会社ID

  /**
   * プルダウン用配列 取得 (JavaScript用に、0からの添字配列)
   */
  public function getPullDown()
  {
    $list = [];

    $qb = $this->createQueryBuilder('w');
    $qb->where('w.status = :active')->setParameter(':active', 0)->orderBy('w.display_order', 'ASC');
    /** @var TbCompany[] $result */
    $result = $qb->getQuery()->getResult();

    foreach($result as $company) {
      $list[$company->getCode()] = $company->getName();
    }

    return $list;
  }

  /**
   * プルダウン用配列 取得 (全フィールド)
   */
  public function getPullDownAll()
  {
    $list = [];

    $qb = $this->createQueryBuilder('w');
    $qb->orderBy('w.display_order', 'ASC');
    /** @var TbCompany[] $result */
    $result = $qb->getQuery()->getResult();

    foreach($result as $company) {
      $list[] = $company->toScalarArray();
    }

    return $list;
  }

  /**
   * プルダウン用配列 取得 (通常用。ID連想配列)
   */
  public function getPullDownObjects()
  {
    $list = [];

    $qb = $this->createQueryBuilder('w');
    $qb->orderBy('w.display_order', 'ASC');
    /** @var TbCompany[] $result */
    $result = $qb->getQuery()->getResult();

    foreach($result as $company) {
      $list[$company->getId()] = $company;
    }

    return $list;
  }

}
