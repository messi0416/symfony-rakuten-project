<?php
namespace MiscBundle\Util;

use DateTimeZone;

class StringifyDateTime extends \DateTime
{
  /**
   * override
   * @param string $format
   * @param string $time
   * @param DateTimeZone $timezone
   * @return StringifyDateTime
   */
  public static function createFromFormat ($format, $time, $timezone = NULL)
  {
    if ($timezone) {
      $dt = parent::createFromFormat($format, $time, $timezone);
    } else {
      $dt = parent::createFromFormat($format, $time);
    }

    return new self($dt->format('Y-m-d H:i:s'));
  }

  public function __toString()
  {
    return $this->format('Y-m-d H:i:s');
  }
}
