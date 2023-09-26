<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakutenLaforestInformation
 */
class TbRakutenLaforestInformation extends BaseRakutenMallInformation
{
  public function getShopName()
  {
    return 'laforest';
  }
}
