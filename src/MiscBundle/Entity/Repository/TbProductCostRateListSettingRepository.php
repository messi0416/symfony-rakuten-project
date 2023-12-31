<?php

namespace MiscBundle\Entity\Repository;
use MiscBundle\Entity\TbProductCostRateListSetting;

/**
 * TbProductCostRateListSettingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TbProductCostRateListSettingRepository extends BaseRepository
{
  const CURRENT_SETTING_ID = 1;

  /**
   * 設定取得
   */
  public function getCurrentSetting()
  {
    $result = $this->find(self::CURRENT_SETTING_ID);
    if (!$result) {
      $class = $this->getEntityName();

      /** @var TbProductCostRateListSetting $result */
      $result = new $class();
      $result->setId(self::CURRENT_SETTING_ID)
        ->setThresholdVoucherNum(1)
        ->setThresholdVoucherTerm(30)
        ->setSamplingDays(7)
        ->setMoveThresholdRate(3)
        ->setShakeBorder(3)
        ->setChangeAmount(3)
      ;

      $em = $this->getEntityManager();
      $em->persist($result);
      $em->flush();
    }

    return $result;
  }
}
