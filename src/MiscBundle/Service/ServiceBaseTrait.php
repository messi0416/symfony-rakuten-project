<?php

namespace MiscBundle\Service;

use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use MiscBundle\Util\MessageUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use MiscBundle\Util\StringUtil;


trait ServiceBaseTrait
{
  protected $container;

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

  /** @var StringUtil */
  protected $stringUtil;

  /** @var MessageUtil */
  protected $messageUtil;

  /** @var  string 環境文字列 'prod' 'dev' 'test' */
  protected $env;

  /** @var InputInterface */
  protected $input;

  /** @var OutputInterface */
  protected $output;

  /**
   * @param ContainerInterface $container
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }

  /**
   * @return ContainerInterface
   */
  public function getContainer()
  {
    return $this->container;
  }

  /**
   * @param $env
   */
  public function setEnvironment($env)
  {
    $this->env = $env;
  }

  /**
   * @return string
   */
  protected function getEnvironment()
  {
    if (!$this->env) {
      $this->env = $this->getApplication()->getKernel()->getEnvironment();
    }
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
   * @return StringUtil
   */
  protected function getStringUtil()
  {
    if (!isset($this->stringUtil)) {
      $this->stringUtil = $this->getContainer()->get('misc.util.string');
    }

    return $this->stringUtil;
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
   * @return MessageUtil
   */
  protected function getMessageUtil()
  {
    $this->messageUtil = $this->getContainer()->get('misc.util.message');
    return $this->messageUtil;
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

  /**
   * @param InputInterface $input
   */
  public function setInput($input)
  {
    $this->input = $input;
  }

  /**
   * @param OutputInterface $output
   */
  public function setOutput($output)
  {
    $this->output = $output;
  }

}
