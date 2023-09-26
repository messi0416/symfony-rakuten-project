<?php
namespace MiscBundle\Util;

use Monolog\Formatter\LineFormatter;
use Symfony\Bridge\Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Bundle\SecurityBundle\Tests\Functional\app\AppKernel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Exception\RuntimeException;

/**
 * 単純なファイルロガー
 * ※ 実装不要な可能性大
 */
class FileLogger extends Logger
{
  /** @var Container */
  private $container;

  private $filePath = null;

  /**
   * @param Container $container
   */
  public function setContainer($container)
  {
    $this->container = $container;
  }

  public function __construct($name, array $handlers = array(), array $processors = array())
  {
    parent::__construct($name, $handlers, $processors);
  }

  /// ファイル名（=stream）の設定
  public function setFileName($name)
  {
    // 最初の1回だけ実行
    if ($this->filePath) {
      throw new \RuntimeException('already file path and handler was set.');
    }

    /** @var AppKernel $kernel */
    $kernel = $this->container->get('kernel');
    $filePath = sprintf('%s/%s_%s.log', $kernel->getLogDir(), $name, $kernel->getEnvironment());

    $this->filePath = $filePath;

    $handler = new StreamHandler($filePath);
    $handler->setFormatter(new LineFormatter("%message%\n"));
    $this->pushHandler($handler);

    return $this;
  }

  /// override
  public function addRecord($level, $message, array $context = array())
  {
    // ログファイル出力 ※ひとまずデフォルト TODO 適切な出力に
    parent::addRecord($level, $message, $context);
  }

}


