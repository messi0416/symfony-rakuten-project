<?php
namespace MiscBundle\Util;
use MiscBundle\Util\StopWatch\StopWatch;

/**
 * ひとまずシンプルに時間を計るのみ
 * Class StopWatchUtil
 * @package MiscBundle\Util
 */

class StopWatchUtil
{
  private static $TOTAL_WATCH_NAME = 'total';

  private $name = '';
  private $watches = [];
  private $lapWatches = [];

  public function __construct($name = '')
  {
    $this->setName($name);
    $this->getWatch(self::$TOTAL_WATCH_NAME); // default total time
  }

  public function newInstance($name = '')
  {
    return new self($name);
  }

  public function setName($name)
  {
    $this->name = $name;
  }

  public function getName()
  {
    return $this->name;
  }

  /**
   * @param string $name
   * @return StopWatch
   */
  public function getWatch($name = null)
  {
    if (is_null($name)) {
      $name = self::$TOTAL_WATCH_NAME;
    }
    if (!isset($this->watches[$name])) {
      $this->watches[$name] = new StopWatch($name);
    }

    return $this->watches[$name];
  }

  /**
   * @param string $name
   * @return StopWatch
   */
  public function getLap($name = '')
  {
    if (!isset($this->lapWatches[$name])) {
      $this->lapWatches[$name] = new StopWatch($name);
    }

    return $this->lapWatches[$name];
  }

  public function start($name = null)
  {
    $this->getWatch($name)->start();
  }
  public function now($name = null)
  {
    return $this->getWatch($name)->now();
  }
  public function stop($name = null)
  {
    return $this->getWatch($name)->stop();
  }
  public function reset($name = null)
  {
    $this->getWatch($name)->now();
  }

  public function lapStart($name = '')
  {
    $this->getLap($name)->start();
  }
  public function lapNow($name = '')
  {
    return $this->getLap($name)->now();
  }
  public function lapStop($name = null)
  {
    return $this->getLap($name)->stop();
  }
  public function lapStopGo($name = null)
  {
    return $this->getLap($name)->stopRestart();
  }

}
