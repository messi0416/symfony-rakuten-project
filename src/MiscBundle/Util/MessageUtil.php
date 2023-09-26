<?php
namespace MiscBundle\Util;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Swift_Mailer;

/**
 * メッセージ送信
 */
class MessageUtil
{
  /** @var ContainerInterface */
  private $container;

  /** @var  Swift_Mailer */
  private $mailer;

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
   * @param Swift_Mailer $mailer
   */
  public function setMailer(Swift_Mailer $mailer)
  {
    $this->mailer = $mailer;
  }

  /**
   * バッチ処理 メール送信処理
   * @param array $setting
   * @param string $subject
   * @param string $body
   * @param &array $failed
   * @return int
   */
  public function sendMail($setting, $subject, $body, &$failed = null)
  {
    $failed = (array) $failed;

    $message = \Swift_Message::newInstance()
      ->setSubject($subject)
      ->setFrom($setting['from'])
      ->setTo($setting['to'])
      ->setBcc($setting['bcc'])
      ->setBody($body)
    ;
    $this->mailer->send($message);

    // spool フラッシュ ... なぜspoolを使っているのか。・・・
    $transport = $this->mailer->getTransport();
    if (!$transport instanceof \Swift_Transport_SpoolTransport) {
      throw new \RuntimeException('SwiftMailer: no spool transport');
    }

    $spool = $transport->getSpool();
    if (!$spool instanceof \Swift_MemorySpool) {
      throw new \RuntimeException('SwiftMailer: no file spool');
    }

    $transportReal = $this->container->get('swiftmailer.transport.real');
    $result = $spool->flushQueue($transportReal, $failed);

    return $result;

  }



}

