<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbYahooInformation
 */
class TbYahooInformation extends BaseYahooMallInformation
{
  public function getShopName()
  {
    return 'plusnao';
  }
}
