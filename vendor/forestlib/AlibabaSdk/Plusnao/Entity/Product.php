<?php
namespace forestlib\AlibabaSdk\Plusnao\Entity;

class Product extends AbstractEntity
{
  public $productId;


//  /**
//   * 日本時刻へ変換
//   * @return \DateTime
//   */
//  public function getGmtCreateJst()
//  {
//    return $this->convertDateStringToJst($this->gmtCreate);
//  }
//  /**
//   * 日本時刻へ変換
//   * @return \DateTime
//   */
//  public function getGmtModifiedJst()
//  {
//    return $this->convertDateStringToJst($this->gmtModified);
//  }
//  /**
//   * 日本時刻へ変換
//   * @return \DateTime
//   */
//  public function getGmtLastRepostJst()
//  {
//    return $this->convertDateStringToJst($this->gmtLastRepost);
//  }
//  /**
//   * 日本時刻へ変換
//   * @return \DateTime
//   */
//  public function getGmtApprovedJst()
//  {
//    return $this->convertDateStringToJst($this->gmtApproved);
//  }
//  /**
//   * 日本時刻へ変換
//   * @return \DateTime
//   */
//  public function getGmtExpireJst()
//  {
//    return $this->convertDateStringToJst($this->gmtExpire);
//  }
//
//  /**
//   * 日本時刻への変換処理
//   * @param $str
//   * @return \DateTime|null
//   */
//  private function convertDateStringToJst($str)
//  {
//    $dt = null;
//    if (strlen($str)) {
//      if (preg_match('/^(\d{8})(\d{6})(?:\d{3})(\+\d{4})/', $str, $m)) {
//        $dt = new \DateTime(sprintf('%sT%s%s', $m[1], $m[2], $m[3]));
//        $dt->setTimezone(new \DateTimeZone('Asia/Tokyo'));
//
//      } else {
//        throw new \RuntimeException('unexpected format datetime: [' . $str . ']');
//      }
//    }
//
//    return $dt;
//  }

}
?>
