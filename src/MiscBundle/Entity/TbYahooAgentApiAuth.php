<?php

namespace MiscBundle\Entity;

/**
 * TbYahooAgentApiAuth
 */
class TbYahooAgentApiAuth extends MappedSuperClassTbYahooApiAuth
{
  /**
   * @var string
   */
  private $shop_code;

  /**
   * setter
   * @param string $shopCode
   * @return $this
   */
  public function setShopCode($shopCode)
  {
    $this->shop_code = $shopCode;

    return $this;
  }

  /**
   * getter
   * @return string
   */
  public function getShopCode()
  {
    return $this->shop_code;
  }

}
