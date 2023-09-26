<?php
namespace MiscBundle\Util;

use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\TbLog;


/**
 * バッチ処理 実行時間計測処理
 * Accessでのログ時間計測に合わせた計測処理実装
 * 「開始」のログに頑張って「経過時間」を上書きする仕様。（ひとまずそのまま引き継ぎ）
 */
class BatchLogTimer
{
  /** @var \DateTime  */
  private $execStart = null;
  /** @var \DateTime  */
  private $lastStart = null;

  /** @var \MiscBundle\Entity\TbLog $lastLog */
  private $lastLog = null;

  private $isFlushed = false;

  /**
   * @param \DateTime $execStart
   * @param \MiscBundle\Entity\TbLog $lastLog
     */
  public function __construct($execStart = null, $lastLog = null)
  {
    $this->init($execStart, $lastLog);
  }

  /**
   * 初期処理
   * @param \DateTime $execStart $execStart
   * @param \MiscBundle\Entity\TbLog  $lastLog
   */
  public function init($execStart = null, $lastLog = null)
  {
    if (!$execStart) {
      if ($lastLog && $lastLog->getExecTimestamp()) {
        $execStart = $lastLog->getExecTimestamp();
      } else {
        $execStart = new \DateTime();
      }
    }
    $this->execStart = $execStart;
    $this->lastLog = $lastLog;
    $this->lastStart = $lastLog ? $lastLog->getLogTimestamp() : null;
    $this->isFlushed = false;
  }

  /**
   * 次回ログを記録した時の処理
   *   => 前回ログの経過時間アップデート
   * @param EntityManager $em
   * @param TbLog $currentLog
   */
  public function updateFormerLog(EntityManager $em, TbLog $currentLog)
  {
    $now = new \DateTime();

    // 前回ログを保持していれば、更新処理を行う。
    if ($this->lastLog) {
      $this->updateLastLogTimes($em, $now);
    }

    // 今回のログを前回のログとして保持。
    $this->lastStart = $now;
    $this->lastLog = $currentLog;
  }

  /**
   * 最終処理
   * ※もとのAccess実装と同様に実装すると必要になる
   * @param EntityManager $em
   * @param \DateTime $now
   */
  public function flush(EntityManager $em, \DateTime $now = null)
  {
    if (!$this->isFlushed) {
      $this->updateLastLogTimes($em, $now);
    }
    $this->isFlushed = true;
  }

  /**
   * 実際の前回ログ更新処理
   * @param EntityManager $em
   * @param \DateTIme $now
   */
  private function updateLastLogTimes(EntityManager $em, \DateTime $now = null)
  {
    if (!$now) {
      $now = new \DateTime();
    }

    // 前回ログを保持していれば、更新処理を行う。
    if ($this->lastLog) {
      $lastStart = $this->lastStart ? $this->lastStart : new \DateTime();
      $interval = $now->format('U') -  $lastStart->format('U');
      $elapse = $now->format('U') - $this->execStart->format('U');

      $this->lastLog->setLogInterval($interval);
      $this->lastLog->setLogElapse($elapse);

      $em->persist($this->lastLog);
      $em->flush();
    }
  }



}

?>
