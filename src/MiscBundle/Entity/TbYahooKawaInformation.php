<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TbYahooKawaInformation
 */
class TbYahooKawaInformation extends BaseYahooMallInformation
{
  public function getShopName()
  {
    return 'kawaemon';
  }
}
