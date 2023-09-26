<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakuteninformation
 */
class TbRakuteninformation extends BaseRakutenMallInformation
{
  public function getShopName()
  {
    return 'plusnao';
  }
}
