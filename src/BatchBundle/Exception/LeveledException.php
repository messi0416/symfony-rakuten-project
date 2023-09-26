<?php
namespace BatchBundle\Exception;

class LeveledException extends \RuntimeException
{
  const DEBUG     = 100;
  const INFO      = 200;
  const NOTICE    = 250;
  const WARNING   = 300;
  const ERROR     = 400;
  const CRITICAL  = 500;
  const ALERT     = 550;
  const EMERGENCY = 600;

  /**
   * Logging levels from syslog protocol defined in RFC 5424
   * また、この配列の並び順を深刻度順とする。（定数値は考慮せず、あくまでも配列の並び順）
   *
   * @var array $LEVELS Logging levels
   */
  protected static $LEVELS = [
    self::DEBUG     => 'DEBUG',
    self::INFO      => 'INFO',
    self::NOTICE    => 'NOTICE',
    self::WARNING   => 'WARNING',
    self::ERROR     => 'ERROR',
    self::CRITICAL  => 'CRITICAL',
    self::ALERT     => 'ALERT',
    self::EMERGENCY => 'EMERGENCY',
  ];

  protected $level = self::ERROR;

  /**
   * @param integer $level
   * @return $this
   */
  public function setLevel($level)
  {
    if (!in_array($level, array_keys(self::$LEVELS))) {
      throw new \RuntimeException('invalid argument. [' . $level . ']');
    }
    $this->level = $level;
    return $this;
  }
  /**
   * @return int
   */
  public function getLevel()
  {
    return $this->level;
  }

  /**
   * 深刻度比較：低
   * @param integer $level
   * @return bool
   */
  public function lowerThanEqual($level)
  {
    return $this->getSeverity($this->getLevel()) <= $this->getSeverity($level);
  }

  /**
   * 深刻度比較：高
   * @param integer $level
   * @return bool
   */
  public function higherThanEqual($level)
  {
    return $this->getSeverity($this->getLevel()) >= $this->getSeverity($level);
  }

  /**
   * 深刻度取得
   * @param $level
   * @return mixed
   */
  protected function getSeverity($level)
  {
    $levelList = array_keys(self::$LEVELS);
    $severity = array_search($level, $levelList);
    if ($severity === false) {
      throw new \RuntimeException('invalid level argument. [' . $level . ']');
    }

    return $severity;
  }



}
