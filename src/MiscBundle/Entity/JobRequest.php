<?php

namespace MiscBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MiscBundle\Entity\EntityTrait\ArrayTrait;
use MiscBundle\Entity\EntityTrait\FillTimestampTrait;
use MiscBundle\Entity\Repository\JobRequestRepository;

/**
 * JobRequest
 */
class JobRequest
{
  use ArrayTrait;
  use FillTimestampTrait;

  /**
   * @return boolean
   */
  public function isValid()
  {
    return $this->isNew() && !$this->isExpired();
  }

  /**
   * @return boolean
   */
  public function isNew()
  {
    return $this->getStatus() === JobRequestRepository::STATUE_NEW;
  }

  /**
   * @param \DateTimeInterface $datetime
   * @return bool
   */
  public function isExpired($datetime = null)
  {
    if (!$datetime) {
      $datetime = new \DateTime();
    }
    return $this->expired_at && ($this->expired_at < $datetime);
  }

  /**
   * Get options Array
   *
   * @return array
   */
  public function getOptionsArray()
  {
    $options = [];
    if (strlen($this->getOptions())) {
      $options = json_decode($this->getOptions(), true);
    }

    return $options;
  }

  /**
   * Get info Array
   *
   * @return array
   */
  public function getInfoArray()
  {
    $info = [];
    if (strlen($this->getInfo())) {
      $info = json_decode($this->getInfo(), true);
    }

    return $info;
  }

  /**
   * Merge info Array
   * @param array $update
   * @return array
   */
  public function setInfoMerge($update)
  {
    $info = $this->getInfoArray();
    $info = array_replace_recursive($info, $update);

    $this->setInfo(json_encode($info));

    return $info;
  }


  // -------------------------------------
  // properties
  // -------------------------------------

  /**
   * @var string
   */
  private $job_key;

  /**
   * @var string
   */
  private $process;

  /**
   * @var \DateTime
   */
  private $expired_at;

  /**
   * @var string
   */
  private $status = 'NEW';

  /**
   * @var \DateTime
   */
  private $queued;

  /**
   * @var \DateTime
   */
  private $started;

  /**
   * @var \DateTime
   */
  private $finished;

  /**
   * @var string
   */
  private $operator;

  /**
   * @var string
   */
  private $message;

  /**
   * @var \DateTime
   */
  private $created;

  /**
   * @var \DateTime
   */
  private $updated;


  /**
   * Set job_key
   *
   * @param string $jobKey
   * @return JobRequest
   */
  public function setJobKey($jobKey)
  {
    $this->job_key = $jobKey;

    return $this;
  }

  /**
   * Get job_key
   *
   * @return string 
   */
  public function getJobKey()
  {
    return $this->job_key;
  }

  /**
   * Set process
   *
   * @param string $process
   * @return JobRequest
   */
  public function setProcess($process)
  {
    $this->process = $process;

    return $this;
  }

  /**
   * Get process
   *
   * @return string 
   */
  public function getProcess()
  {
    return $this->process;
  }

  /**
   * Set expired_at
   *
   * @param \DateTime $expiredAt
   * @return JobRequest
   */
  public function setExpiredAt($expiredAt)
  {
    $this->expired_at = $expiredAt;

    return $this;
  }

  /**
   * Get expired_at
   *
   * @return \DateTime 
   */
  public function getExpiredAt()
  {
    return $this->expired_at;
  }

  /**
   * Set status
   *
   * @param string $status
   * @return JobRequest
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * Get status
   *
   * @return string 
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * Set queued
   *
   * @param \DateTime $queued
   * @return JobRequest
   */
  public function setQueued($queued)
  {
    $this->queued = $queued;

    return $this;
  }

  /**
   * Get queued
   *
   * @return \DateTime 
   */
  public function getQueued()
  {
    return $this->queued;
  }

  /**
   * Set started
   *
   * @param \DateTime $started
   * @return JobRequest
   */
  public function setStarted($started)
  {
    $this->started = $started;

    return $this;
  }

  /**
   * Get started
   *
   * @return \DateTime 
   */
  public function getStarted()
  {
    return $this->started;
  }

  /**
   * Set finished
   *
   * @param \DateTime $finished
   * @return JobRequest
   */
  public function setFinished($finished)
  {
    $this->finished = $finished;

    return $this;
  }

  /**
   * Get finished
   *
   * @return \DateTime 
   */
  public function getFinished()
  {
    return $this->finished;
  }

  /**
   * Set operator
   *
   * @param string $operator
   * @return JobRequest
   */
  public function setOperator($operator)
  {
    $this->operator = $operator;

    return $this;
  }

  /**
   * Get operator
   *
   * @return string 
   */
  public function getOperator()
  {
    return $this->operator;
  }

  /**
   * Set message
   *
   * @param string $message
   * @return JobRequest
   */
  public function setMessage($message)
  {
    $this->message = $message;

    return $this;
  }

  /**
   * Get message
   *
   * @return string 
   */
  public function getMessage()
  {
    return $this->message;
  }

  /**
   * Set created
   *
   * @param \DateTime $created
   * @return JobRequest
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
   * @return JobRequest
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

  /**
   * @var string
   */
  private $options;


  /**
   * Set options
   *
   * @param string $options
   * @return JobRequest
   */
  public function setOptions($options)
  {
    $this->options = $options;

    return $this;
  }

  /**
   * Get options
   *
   * @return string
   */
  public function getOptions()
  {
    return $this->options;
  }
  
  /**
   * @var string
   */
  private $info;

  /**
   * Set info
   *
   * @param string $info
   * @return JobRequest
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
}
