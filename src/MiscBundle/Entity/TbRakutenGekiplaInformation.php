<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbRakutenGekiplaInformation
 */
class TbRakutenGekiplaInformation extends BaseRakutenMallInformation
{
  public function getShopName()
  {
    return '激安プラネット';
  }
}
