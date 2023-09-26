<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakutenDolcissimoInformation
 */
class TbRakutenDolcissimoInformation extends BaseRakutenMallInformation
{
  public function getShopName()
  {
    return 'dolcissimo';
  }
}
