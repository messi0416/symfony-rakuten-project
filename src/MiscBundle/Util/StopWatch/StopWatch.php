<?php
namespace MiscBundle\Util\StopWatch;

/**
 * ひとまずシンプルに時間を計るのみ
 * Class StopWatch
 * @package MiscBundle\Util
 */

class StopWatch
{
  private $name = '';
  private $start;
  private $past = 0;

  private $isRunning = false;

  /**
   * @param string $name
   */
  public function __construct($name = '')
  {
    $this->setName($name);
  }

  /**
   * @param string $name
   * @return StopWatch
   */
  public function setName($name)
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  public function start()
  {
    if ($this->isRunning) {
      return;
    }

    $this->start = microtime(true);
    $this->isRunning = true;
  }

  /**
   * @return float
   */
  public function now()
  {
    return $this->formatTime($this->calcNow());
  }

  /**
   * @return float
   */
  public function stop()
  {
    if (!$this->start || !$this->isRunning) {
      return $this->formatTime(0);
    }

    $this->past = $this->calcNow();
    $this->isRunning = false;

    return $this->formatTime($this->past);
  }

  public function reset()
  {
    $this->isRunning = false;
    $this->past = 0;
    $this->start = null;
  }

  /**
   * @return float
   */
  public function stopRestart()
  {
    $result = $this->stop();
    $this->reset();
    $this->start();

    return $result;
  }

  /**
   * @return float
   */
  private function calcNow()
  {
    $time = $this->isRunning ? microtime(true) - $this->start : 0;
    return $this->past + $time;
  }

  /**
   * @param float $value
   * @param int $precision
   * @return float
   */
  private function formatTime($value, $precision = 4)
  {
    return round($value, $precision);
  }

}
