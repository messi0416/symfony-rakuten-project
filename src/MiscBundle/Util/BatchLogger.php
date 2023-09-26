<?php
namespace MiscBundle\Util;

use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\EntityInterface\SymfonyUserClientInterface;
use MiscBundle\Entity\TbLog;
use Swift_Mailer;
use Swift_Transport;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\Container;

/**
 * バックエンド処理 ログ機能
 */
class BatchLogger extends Logger
{
  /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
  private $doctrine; // Doctrine
  private $db; // main DB接続

  /** @var Swift_Mailer */
  private $mailer; // Swift_Mailer

  /** @var Container */
  private $container;

  /** @var BatchLogTimer $logTimer */
  private $logTimer = null;

  /** @var SymfonyUserClientInterface */
  private $account = null;

  /** @var string */
  private $execTitle = null;

  // パラメータ群
  private $errorMailFrom;
  private $errorMailTo = [];
  private $errorMailBcc = [];

  /**
   * @param Container $container
   */
  public function setContainer($container)
  {
    $this->container = $container;
  }

  /**
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
   */
  public function setDoctrine(\Doctrine\Bundle\DoctrineBundle\Registry $doctrine)
  {
    $this->doctrine = $doctrine;
    $this->db = $doctrine->getConnection('main');
  }

  /**
   * @param Swift_Mailer $mailer
   */
  public function setMailer(Swift_Mailer $mailer)
  {
    $this->mailer = $mailer;
  }

  /**
   * @param SymfonyUserClientInterface $account
   */
  public function setAccount(SymfonyUserClientInterface $account)
  {
    $this->account = $account;
  }

  /**
   * ログExecTitle セット
   * @param $execTitle
   */
  public function setExecTitle($execTitle)
  {
    $this->execTitle = $execTitle;
  }

  /**
   * print_r
   * @param mixed $var
   * @param int $level
   * @return bool
   */
  public function dump($var, $level = self::INFO)
  {
    return $this->log($level, print_r($var, true));
  }


  /**
   * ログ出力のタイマーを初期化する。
   * @param boolean $force 既に初期化されていても、新たなタイマーを設定するか。falseならば、初期化済みの時は何もしない。
   */
  public function initLogTimer($force = false)
  {
    if ($force || is_null($this->logTimer)) {
      $this->logTimer = new BatchLogTimer(null, null);
    }
  }

  public function setErrorMailFrom($mailFrom)
  {
    $this->errorMailFrom = $mailFrom;
  }
  public function setErrorMailTo(array $mailTo)
  {
    $this->errorMailTo = $mailTo;
  }
  public function setErrorMailBcc(array $bcc)
  {
    $this->errorMailBcc = $bcc;
  }

  /**
   * 最後に logTimerのflush を行う
   */
  public function __destruct()
  {
    try {
      $this->logTimerFlush();
    } catch (\Exception $e) {
      // ここの失敗は握りつぶす
    }
  }

  /**
   * ログを１件作成する
   * @param string|null $execTitle
   * @param string $logTitle
   * @param string $subTitle1
   * @param string $subTitle2
   * @param string $subTitle3
   * @param string $pc
   * @return TbLog $log
   */
  public function makeDbLog($execTitle, $logTitle, $subTitle1 = '', $subTitle2 = '', $subTitle3 = '', $pc = null)
  {
    if (!$execTitle) {
      if ($this->execTitle) {
        $execTitle = $this->execTitle;
      } else {
        $execTitle = '';
      }
    }

    if (!$pc) {
      if ($this->account) {
        $pc = sprintf('BatchSV01:WEB(%s)', $this->account->getUsername());
      } else {
        $pc = 'BatchSV01';
      }
    }

    // 初期値を入れないと NOT NULL カラムが通らない。
    // ※出力するINSERTのSQLに書かなきゃ、DBのデフォルト値が利用されるのに。
    //   Doctrine2が抜けているのか、何か設定があるのか・・・
    $log = new TbLog();
    $log->setPc($pc);
    $log->setExecTitle($execTitle);
    $log->setExecTimestamp(new \DateTime());
    $log->setLogLevel(TbLog::NOTICE);
    $log->setLogTitle($logTitle);
    $log->setLogSubtitle1($subTitle1);
    $log->setLogSubtitle2($subTitle2);
    $log->setLogSubtitle3($subTitle3);
    $log->setLogTimestamp(new \DateTime());
    $log->setLogInterval(0);
    $log->setLogElapse(0);
    $log->setinformation(null);
    $log->seterrorFlag(0);
    $log->setNum(0);
    $log->setSize(0);
    $log->setGroupStartId(0);
    $log->setGroupStart(0);
    $log->setGroupEnd(0);

    return $log;
  }

  /**
   * @param TbLog $log
   * @param bool|null $notify
   * @param string|null $notificationMessage
   * @param string $notificationLevel info|notice|error
   * @return int
   */
  public function addDbLog(TbLog $log, $notify = null, $notificationMessage = null, $notificationLevel = null)
  {
    // 時間計測 ＆ 前回ログ更新処理
    if (!$this->logTimer) {
      $this->initLogTimer();
    }

    // DBへのログ格納
    /** @var EntityManager $em */
    $em = $this->doctrine->getEntityManager();
    $em->persist($log);
    $em->flush();

    $em->refresh($log);

    $this->logTimer->updateFormerLog($em, $log);
    
    try {
      // 通知サーバへPOST処理 ( tb_log テーブルへ格納したもののみ )
      // $config = array('useragent' => 'forest batch logger ua/1.0');
      /** @var WebAccessUtil $webAccessUtil */
      $webAccessUtil = $this->container->get('misc.util.web_access');
  
      $client = $webAccessUtil->getWebClient();
      if (is_null($notify)) {
        $notify = $log->hasToNotify();
      }
      if ($notify && empty($notificationMessage)) {
        $notificationMessage = $log->getNotificationMessage();
      }
      if ($notify && empty($notificationLevel)) {
        $notificationLevel = $log->getNotificationLevel();
      }
  
      $data = array_merge($log->toArray(), [
          'notify' => $notify
        , 'notification_level' => $notificationLevel
        , 'notification_message' => $notificationMessage
      ]);
  
      $url = $this->container->getParameter('app_notification_url') . $this->container->getParameter('app_notification_path');
      $client->request('post', $url, $data);
    // #215132 通知サーバへのpost時に「cURL error 35: Unknown SSL protocol error in connection」が出ることがある。
    // メイン処理を中断しないよう、ログに出して握りつぶす
    } catch (\Exception $e) { 
      $this->error("BatchLogger:通知サーバへの通知時にエラー発生: $e");
      $ticket = [
        'issue' => [
          'subject'         => '[Plusnao エラー] BatchLogger:通知サーバへの通知時にエラー発生'
          , 'project_id'      => $this->container->getParameter('redmine_create_error_ticket_project')
          , 'priority_id'     => $this->container->getParameter('redmine_create_error_ticket_priority')
          , 'description'     => $e->getMessage() . "\r\n" . $e->getTraceAsString()
          , 'assigned_to_id'  => $this->container->getParameter('redmine_create_error_ticket_user')
          , 'tracker_id'      => $this->container->getParameter('redmine_create_error_ticket_tracker')
          // , 'category_id'     => ''
          // , 'status_id'       => ''
        ]
      ];
      $result = $webAccessUtil->requestRedmineApi('POST', '/issues.json', $ticket);
    }

    // エラーならエラーログとして保存しメールを送信する
    $failed = [];
    if ($notificationLevel == 'error') {
      $log->setErrorFlag(-1);
      $em->flush();

      $mailBody = print_r(array_merge($log->toArray(), [ 'information' => $log->getInformation() ]), true);
      $count = $this->sendErrorMail('[Plusnao エラー]' . $notificationMessage, $mailBody, $failed);

      $this->info('after send error mail. [num: ' . $count . ']');
      $this->info('after send error mail ... failed mails. [' . implode(' / ', $failed) . ']');

      // 本番環境であれば Redmineのチケットも作成する(parameters.yml)
      if ($this->container->getParameter('redmine_create_error_ticket')) {
        try {

          $ticket = [
            'issue' => [
                'subject'         => '[Plusnao エラー]' . $notificationMessage
              , 'project_id'      => $this->container->getParameter('redmine_create_error_ticket_project')
              , 'priority_id'     => $this->container->getParameter('redmine_create_error_ticket_priority')
              , 'description'     => $mailBody
              , 'assigned_to_id'  => $this->container->getParameter('redmine_create_error_ticket_user')
              , 'tracker_id'      => $this->container->getParameter('redmine_create_error_ticket_tracker')
              // , 'category_id'     => ''
              // , 'status_id'       => ''
            ]
          ];

          $result = $webAccessUtil->requestRedmineApi('POST', '/issues.json', $ticket);
          $this->info('redmine create ticket:' . $result);

        } catch (\Exception $e) {
          // ここでのエラーはひとまず握り潰す。
          $this->error("BatchLogger: Redmineチケット作成時にエラー: " . $e->getMessage());
        }
      }
    }

    return $log->getId();
  }

  /// ログタイマー flush
  public function logTimerFlush()
  {
    if ($this->logTimer) {
      $this->logTimer->flush($this->doctrine->getEntityManager());
    }
  }


  /// override
  public function addRecord($level, $message, array $context = array())
  {
    // ログファイル出力 ※ひとまずデフォルト TODO 適切な出力に
    parent::addRecord($level, $message, $context);
  }


  /**
   * エラーメール送信処理
   * @param string $subject
   * @param string $body
   * @param &array $failed
   * @return int
   */
  public function sendErrorMail($subject, $body, &$failed = null)
  {
    $failed = (array) $failed;

    $message = \Swift_Message::newInstance()
      ->setSubject($subject)
      ->setFrom($this->errorMailFrom)
      ->setTo($this->errorMailTo)
      ->setBcc($this->errorMailBcc)
      ->setBody($body)
    ;
    $this->mailer->send($message);

    // spool フラッシュ ... なぜspoolを使っているのか。・・・
    $transport = $this->mailer->getTransport();
    if (!$transport instanceof \Swift_Transport_SpoolTransport) {
      $this->error('SwiftMailer: no spool transport');
      return 0;
    }

    $spool = $transport->getSpool();
    if (!$spool instanceof \Swift_MemorySpool) {
      $this->error('SwiftMailer: no file spool');
      return 0;
    }

    /** @var Swift_Transport $transportReal */
    $transportReal = $this->container->get('swiftmailer.transport.real');
    $result = $spool->flushQueue($transportReal, $failed);

    return $result;
  }

}

