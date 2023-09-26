<?php

namespace MiscBundle\Entity;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;

/**
 * BatchLock
 */
class BatchLock
{
  use ArrayTrait;
  use FillTimestampTrait;

  // キー値はvarchar(20)

  /** 楽天plusnaoログイン */
  const BATCH_CODE_RMS_LOGIN = 'rms_login';
  /** 楽天mottoログイン */
  const BATCH_CODE_RMS_MOTTO_LOGIN = 'rms_motto_login';
  /** 楽天laforestログイン */
  const BATCH_CODE_RMS_LAFOREST_LOGIN = 'rms_laforest_login';
  /** 楽天dolcissimoログイン */
  const BATCH_CODE_RMS_DOLCISSIMO_LOGIN = 'rms_dolcissimo_login';
  /** 楽天激安プラネットログイン */
  const BATCH_CODE_RMS_GEKIPLA_LOGIN = 'rms_gekipla_login';

  const BATCH_CODE_PPM_LOGIN = 'ppm_login';

  const DEFAULT_NOTIFICATION_SPAN = 3600; // 1時間おきに通知

  /**
   * 通知間隔判定
   * @param int $span
   * @return bool
   */
  public function hasToNotify($span = null)
  {
    if (!$span) {
      $span = self::DEFAULT_NOTIFICATION_SPAN;
    }

    $ret = true;

    $last = $this->getLastNotified();
    if ($last) {
      $diff = (new \DateTime())->format('U') - $last->format('U');
      if ($diff <= $span) {
        $ret = false;
      }
    }

    return $ret;
  }

  /**
   * リトライ回数上限チェック
   */
  public function isOverRetryMax()
  {
    return $this->getRetryCount() >= $this->getRetryCountMax();
  }

  /**
   * リトライ回数更新
   */
  public function increaseRetryCount()
  {
    $this->setRetryCount($this->getRetryCount() + 1);
  }

  // --------------------------------------
  // setter, getter
  // --------------------------------------

  /**
   * @var string
   */
  private $batch_code;

  /**
   * @var \DateTime
   */
  private $locked;

  /**
   * @var string
   */
  private $lock_key = '';

  /**
   * @var string
   */
  private $info = '';

  /**
   * @var \DateTime
   */
  private $last_notified;

  /**
   * @var int
   */
  private $retry_count = 0;

  /**
   * @var int
   */
  private $retry_count_max = 0;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set batchCode
   *
   * @param string $batchCode
   *
   * @return BatchLock
   */
  public function setBatchCode($batchCode)
  {
    $this->batch_code = $batchCode;

    return $this;
  }

  /**
   * Get batchCode
   *
   * @return string
   */
  public function getBatchCode()
  {
    return $this->batch_code;
  }

  /**
   * Set locked
   *
   * @param \DateTime $locked
   *
   * @return BatchLock
   */
  public function setLocked($locked)
  {
    $this->locked = $locked;

    return $this;
  }

  /**
   * Get locked
   *
   * @return \DateTime
   */
  public function getLocked()
  {
    return $this->locked;
  }

  /**
   * Set lockKey
   *
   * @param string $lockKey
   *
   * @return BatchLock
   */
  public function setLockKey($lockKey)
  {
    $this->lock_key = $lockKey;

    return $this;
  }

  /**
   * Get lockKey
   *
   * @return string
   */
  public function getLockKey()
  {
    return $this->lock_key;
  }

  /**
   * Set info
   *
   * @param string $info
   *
   * @return BatchLock
   */
  public function setInfo($info)
  {
    $this->info = $info;

    return $this;
  }

  /**
   * Get info
   *
   * @return string
   */
  public function getInfo()
  {
    return $this->info;
  }

  /**
   * Set last_notified
   *
   * @param \DateTime $lastNotified
   *
   * @return BatchLock
   */
  public function setLastNotified($lastNotified)
  {
    $this->last_notified = $lastNotified;

    return $this;
  }

  /**
   * Get last_notified
   *
   * @return \DateTime
   */
  public function getLastNotified()
  {
    return $this->last_notified;
  }

  /**
   * Set retryCount
   *
   * @param int $retryCount
   *
   * @return BatchLock
   */
  public function setRetryCount($retryCount)
  {
    $this->retry_count = $retryCount;

    return $this;
  }

  /**
   * Get retryCount
   *
   * @return int
   */
  public function getRetryCount()
  {
    return $this->retry_count;
  }

  /**
   * Set retryCountMax
   *
   * @param int $retryCountMax
   *
   * @return BatchLock
   */
  public function setRetryCountMax($retryCountMax)
  {
    $this->retry_count_max = $retryCountMax;

    return $this;
  }

  /**
   * Get retryCountMax
   *
   * @return int
   */
  public function getRetryCountMax()
  {
    return $this->retry_count_max;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   *
   * @return BatchLock
   */
  public function setCreated($created)
  {
    $this->created = $created;

    return $this;
  }

  /**
   * Get created
   *
   * @return \DateTime
   */
  public function getCreated()
  {
    return $this->created;
  }

  /**
   * Set updated
   *
   * @param \DateTime $updated
   *
   * @return BatchLock
   */
  public function setUpdated($updated)
  {
    $this->updated = $updated;

    return $this;
  }

  /**
   * Get updated
   *
   * @return \DateTime
   */
  public function getUpdated()
  {
    return $this->updated;
  }
}

class BatchLockException extends \RuntimeException
{
  /** @var  BatchLock */
  protected $lock;

  public function setLock($lock)
  {
    $this->lock = $lock;
  }
  public function getLock()
  {
    return $this->lock;
  }
}
