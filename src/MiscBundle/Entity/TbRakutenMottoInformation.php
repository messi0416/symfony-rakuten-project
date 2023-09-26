<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakutenMottoInformation
 */
class TbRakutenMottoInformation extends BaseRakutenMallInformation
{
  public function getShopName()
  {
    return 'motto';
  }
}
