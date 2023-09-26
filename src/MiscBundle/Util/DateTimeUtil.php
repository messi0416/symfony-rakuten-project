<?php
namespace MiscBundle\Util;

use DateInterval;
use MiscBundle\Exception\ValidationException;

class DateTimeUtil
{
  public function formatIntervalJp(DateInterval $interval)
  {
    $format = array();
    if($interval->y !== 0) {
      $format[] = "%Y年";
    }
    if($interval->m !== 0) {
      $format[] = "%mか月";
    }
    if($interval->d !== 0) {
      $format[] = "%d日";
    }
    if($interval->h !== 0) {
      $format[] = (count($format) ? '' : '') . "%h時間";
    }
    if($interval->i !== 0) {
      $format[] = "%i分";
    }
    if($interval->s !== 0) {
      $format[] = "%s秒";
    }

    return $interval->format(implode('', $format));
  }

  /**
   * Y-m-d形式の日付の妥当性を確認する。
   * @param string $date
   */
  public function validateYmdDate($date, $dateName = '')
  {
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    $addMessage = $dateName === '' ? '' : $dateName . 'が、';
    if (!preg_match($datePattern, $date)) {
      throw new ValidationException($addMessage . 'Y-m-d形式ではありません [' . $date . ']');
    }
    list($year, $month, $day) = explode('-', $date);
    if (!checkdate($month, $day, $year)) {
      throw new ValidationException($addMessage . '正しい日付ではありません [' . $date . ']');
    }
  }
  
  /**
   * Y-m-d H:i:s形式の日時の妥当性を確認する。
   * @param string $date
   */
  public function validateYmdHisDate($date, $dateName = '')
  {
    $datePattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';
    $addMessage = $dateName === '' ? '' : $dateName . 'が、';
    if (!preg_match($datePattern, $date)) {
      throw new ValidationException($addMessage . 'Y-m-d H:i:s形式ではありません [' . $date . ']');
    }
    $dateObj = new \DateTime($date);
    $compareStr = $dateObj->format('Y-m-d H:i:s');
    if ($date != $compareStr) {
      throw new ValidationException($addMessage . '正しい日時ではありません [' . $date . ']');
    }
  }
}
