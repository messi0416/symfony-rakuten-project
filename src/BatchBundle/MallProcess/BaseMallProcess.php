<?php

namespace BatchBundle\MallProcess;

use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Stopwatch\Stopwatch;


class BaseMallProcess
{
  /** @var Stopwatch */
  protected $stopwatch;

  /** @var DbCommonUtil */
  protected $commonUtil;

  /** @var BatchLogger */
  protected $logger;

  /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
  protected $doctrine;

  /** @var \Doctrine\DBAL\Connection[] */
  protected $dbConnections = [];

  /** @var WebAccessUtil */
  protected $webAccessUtil;

  /** @var FileUtil */
  protected $fileUtil;

  /** @var Container */
  protected $container;

  /** @var string */
  protected $env;

  /**
   * @param Container $container
   */
  public function setContainer($container)
  {
    $this->container = $container;
  }

  /**
   * @return Container $container
   */
  public function getContainer()
  {
    return $this->container;
  }

  /**
   * @param string $env
   */
  public function setEnvironment($env)
  {
    $this->env = $env;
  }

  /**
   * return string
   */
  public function getEnvironment()
  {
    return $this->env;
  }

  /**
   * @return Stopwatch
   */
  protected function getStopwatch()
  {
    if (!isset($this->stopwatch)) {
      $this->stopwatch = $this->getContainer()->get('debug.stopwatch');
  }

    return $this->stopwatch;
  }

  /**
   * @return DBCommonUtil
   */
  protected function getDbCommonUtil()
  {
    if (!isset($this->commonUtil)) {
      $this->commonUtil = $this->getContainer()->get('misc.util.db_common');
    }

    return $this->commonUtil;
  }

  /**
   * @return FileUtil
   */
  protected function getFileUtil()
  {
    if (!isset($this->fileUtil)) {
      $this->fileUtil = $this->getContainer()->get('misc.util.file');
    }

    return $this->fileUtil;
  }

  /**
   * @return BatchLogger
   */
  protected function getLogger()
  {
    if (!isset($this->logger)) {
      $this->logger = $this->getContainer()->get('misc.util.batch_logger');
      $this->logger->initLogTimer();
    }

    return $this->logger;
  }

  /**
   * @param string
   * @return \Doctrine\Bundle\DoctrineBundle\Registry
   */
  protected function getDoctrine()
  {
    if (!isset($this->doctrine)) {
      $this->doctrine = $this->getContainer()->get('doctrine');
    }
    return $this->doctrine;
  }

  /**
   * @param string $name
   * @return \Doctrine\DBAL\Connection
   */
  protected function getDb($name)
  {
    if (!array_key_exists($name, $this->dbConnections)) {
      $this->dbConnections[$name] = $this->getDoctrine()->getConnection($name);
    }

    return isset($this->dbConnections[$name]) ? $this->dbConnections[$name] : null;
  }

  /**
   * @return WebAccessUtil
   */
  protected function getWebAccessUtil()
  {
    $this->webAccessUtil = $this->getContainer()->get('misc.util.web_access');
    return $this->webAccessUtil;
  }

  /**
   * @return \BCC\ResqueBundle\Resque
   */
  protected function getResque()
  {
    return $this->getContainer()->get('bcc_resque.resque');
  }

  protected function getLapTimeAndMemory($name, $base = 'main')
  {
    $result = '';
    if ($this->getStopwatch()->isStarted($base)) {
      // どうも使い方がよくわからない、のでがちゃがちゃやってます。
      $stopwatch = $this->getStopwatch();
      $lapEvent = $stopwatch->lap($base);
      $result = sprintf('[%s : %s] lap: %.04f / memory: %s', $base, $name, $lapEvent->getDuration() / 1000, number_format($lapEvent->getMemory()));
    }

    return $result;
  }



}
