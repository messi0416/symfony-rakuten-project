<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbYahooOtoriyoseInformation
 */
class TbYahooOtoriyoseInformation extends BaseYahooMallInformation
{
  public function getShopName()
  {
    return 'otoriyose';
  }
}
