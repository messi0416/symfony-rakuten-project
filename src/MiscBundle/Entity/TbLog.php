<?php

namespace MiscBundle\Entity;

/**
 * TbLog
 */
class TbLog
{
  /**
   * Detailed debug information
   */
  const DEBUG = 1;

  /**
   * Interesting events
   *
   * Examples: User logs in, SQL logs.
   */
  const INFO = 2;

  /**
   * // 当初からずっとこの「3」で保存されているため、これがデフォルト(Noticeなら調度よい、かも)
   * Uncommon events
   */
  const NOTICE = 3;

  /**
   * Exceptional occurrences that are not errors
   *
   * Examples: Use of deprecated APIs, poor use of an API,
   * undesirable things that are not necessarily wrong.
   */
  const WARNING = 4;

  /**
   * Runtime errors
   */
  const ERROR = 5;

  /**
   * Critical conditions
   *
   * Example: Application component unavailable, unexpected exception.
   */
  const CRITICAL = 6;

  /**
   * Action must be taken immediately
   *
   * Example: Entire website down, database unavailable, etc.
   * This should trigger the SMS alerts and wake you up.
   */
  const ALERT = 7;

  /**
   * Urgent alert.
   */
  const EMERGENCY = 8;

  /**
   * デスクトップ通知を行うログかどうか判定
   */
  public function hasToNotify()
  {
    $result = false;

    // ログレベルが notice 以上でなければ通知対象外
    if ($this->getLogLevel() < self::NOTICE) {
      return $result;
    }

    // 処理タイトルとログタイトルが同じもの => 開始・終了とみなす
    if (
         $this->getExecTitle() == $this->getLogTitle()
      && in_array($this->getLogSubtitle1(), ['開始', '終了'])
    ) {
      $result = true;
    } else if ($this->isErrorLog()) {
      $result = true;
    }


    return $result;
  }

  /**
   *
   */
  public function isErrorLog()
  {
    return    $this->getLogSubtitle1() == 'エラー終了'
           || $this->getLogSubtitle2() == 'エラー終了'
           || $this->getLogSubtitle3() == 'エラー終了'
           || $this->getErrorFlag()    <> 0;
  }


  /**
   * 配列へ変換
   * @param bool $stringify
   * @param bool $withInformation
   * @return array
   */
  public function toArray($stringify = true, $withInformation = false)
  {
    $result = array();
    $fields = [
        'id'
      , 'pc'
      , 'exec_title'
      , 'exec_timestamp'
      , 'log_level'
      , 'log_title'
      , 'log_subtitle1'
      , 'log_subtitle2'
      , 'log_subtitle3'
      , 'log_timestamp'
      , 'log_interval'
      , 'log_elapse'
      , 'error_flag'
      , 'num'
      , 'size'
      , 'group_start_id'
      , 'group_start'
      , 'group_end'
    ];
    if ($withInformation) {
      $fields[] = 'information';
    }

    foreach($fields as $key) {
      $value = $this->$key;

      // 日付を文字列に変換
      if ($stringify && in_array($key, ['exec_timestamp', 'log_timestamp'])) {
        $value = $value ? $value->format('Y-m-d H:i:s') : '';
      }

      $result[strtoupper($key)] = $value; // DBのフィールド名に合わせる
    }

    return $result;
  }

  /**
   * 通知文言 作成
   */
  public function getNotificationMessage()
  {
    $result = '';
    if (!$this->getExecTitle()) {
      return $result;
    }

    switch($this->getLogSubtitle1()) {
      case '開始':
        $result = sprintf('%sを開始しました。', $this->getExecTitle());
        break;
      case '終了':
        $result = sprintf('%sを終了しました。', $this->getExecTitle());
        break;
      default:
        $result = sprintf('%s %s', $this->getExecTitle(), $this->getLogSubtitle1());
        break;
    }

    return $result;
  }

  /**
   * 通知レベル 判定
   */
  public function getNotificationLevel()
  {
    $result = 'info';
    if ($this->isErrorLog()) {
      $result = 'error';
    }
    return $result;
  }



































  // ===================================================
  // column property, setter, getter
  // ===================================================

  /**
   * @var integer
   */
  private $id;

  /**
   * @var string
   */
  private $pc;

  /**
   * @var string
   */
  private $exec_title;

  /**
   * @var \DateTime
   */
  private $exec_timestamp;

  /**
   * @var string
   */
  private $log_level;

  /**
   * @var string
   */
  private $log_title;

  /**
   * @var string
   */
  private $log_subtitle1;

  /**
   * @var string
   */
  private $log_subtitle2;

  /**
   * @var string
   */
  private $log_subtitle3;

  /**
   * @var \DateTime
   */
  private $log_timestamp;

  /**
   * @var integer
   */
  private $log_interval;

  /**
   * @var integer
   */
  private $log_elapse;


  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set pc
   *
   * @param string $pc
   *
   * @return TbLog
   */
  public function setPc($pc)
  {
    $this->pc = $pc;

    return $this;
  }

  /**
   * Get pc
   *
   * @return string
   */
  public function getPc()
  {
    return $this->pc;
  }

  /**
   * Set execTitle
   *
   * @param string $execTitle
   *
   * @return TbLog
   */
  public function setExecTitle($execTitle)
  {
    $this->exec_title = $execTitle;

    return $this;
  }

  /**
   * Get execTitle
   *
   * @return string
   */
  public function getExecTitle()
  {
    return $this->exec_title;
  }

  /**
   * Set execTimestamp
   *
   * @param \DateTime|string $execTimestamp
   *
   * @return TbLog
   */
  public function setExecTimestamp($execTimestamp)
  {
    if (!$execTimestamp) {
      $execTimestamp = null;
    }
    if (! $execTimestamp instanceof \DateTime) {
      $execTimestamp = new \DateTime($execTimestamp);
    }

    $this->exec_timestamp = $execTimestamp;
    return $this;
  }

  /**
   * Get execTimestamp
   *
   * @return \DateTime
   */
  public function getExecTimestamp()
  {
    return $this->exec_timestamp;
  }

  /**
   * Set logLevel
   *
   * @param string $logLevel
   *
   * @return TbLog
   */
  public function setLogLevel($logLevel)
  {
    $this->log_level = $logLevel;

    // エラーならエラーフラグも立てる
    if ($logLevel == self::ERROR) {
      $this->setErrorFlag(-1);
    }

    return $this;
  }

  /**
   * Get logLevel
   *
   * @return string
   */
  public function getLogLevel()
  {
    return $this->log_level;
  }

  /**
   * Set logTitle
   *
   * @param string $logTitle
   *
   * @return TbLog
   */
  public function setLogTitle($logTitle)
  {
    $this->log_title = $logTitle;

    return $this;
  }

  /**
   * Get logTitle
   *
   * @return string
   */
  public function getLogTitle()
  {
    return $this->log_title;
  }

  /**
   * Set logSubtitle1
   *
   * @param string $logSubtitle1
   *
   * @return TbLog
   */
  public function setLogSubtitle1($logSubtitle1)
  {
    $this->log_subtitle1 = $logSubtitle1;

    return $this;
  }

  /**
   * Get logSubtitle1
   *
   * @return string
   */
  public function getLogSubtitle1()
  {
    return $this->log_subtitle1;
  }

  /**
   * Set logSubtitle2
   *
   * @param string $logSubtitle2
   *
   * @return TbLog
   */
  public function setLogSubtitle2($logSubtitle2)
  {
    $this->log_subtitle2 = $logSubtitle2;

    return $this;
  }

  /**
   * Get logSubtitle2
   *
   * @return string
   */
  public function getLogSubtitle2()
  {
    return $this->log_subtitle2;
  }

  /**
   * Set logSubtitle3
   *
   * @param string $logSubtitle3
   *
   * @return TbLog
   */
  public function setLogSubtitle3($logSubtitle3)
  {
    $this->log_subtitle3 = $logSubtitle3;

    return $this;
  }

  /**
   * Get logSubtitle3
   *
   * @return string
   */
  public function getLogSubtitle3()
  {
    return $this->log_subtitle3;
  }

  /**
   * Set logTimestamp
   *
   * @param \DateTime $logTimestamp
   *
   * @return TbLog
   */
  public function setLogTimestamp($logTimestamp)
  {
    if (!$logTimestamp) {
      $logTimestamp = null;
    }
    if (! $logTimestamp instanceof \DateTime) {
      $logTimestamp = new \DateTime($logTimestamp);
    }

    $this->log_timestamp = $logTimestamp;
    return $this;
  }

  /**
   * Get logTimestamp
   *
   * @return \DateTime
   */
  public function getLogTimestamp()
  {
    return $this->log_timestamp;
  }

  /**
   * Set logInterval
   *
   * @param integer $logInterval
   *
   * @return TbLog
   */
  public function setLogInterval($logInterval)
  {
    $this->log_interval = $logInterval;

    return $this;
  }

  /**
   * Get logInterval
   *
   * @return integer
   */
  public function getLogInterval()
  {
    return $this->log_interval;
  }

  /**
   * Set logElapse
   *
   * @param integer $logElapse
   *
   * @return TbLog
   */
  public function setLogElapse($logElapse)
  {
    $this->log_elapse = $logElapse;

    return $this;
  }

  /**
   * Get logElapse
   *
   * @return integer
   */
  public function getLogElapse()
  {
    return $this->log_elapse;
  }
    /**
     * @var string
     */
    private $information;


    /**
     * Set information
     *
     * @param mixed $information
     *
     * @return TbLog
     */
    public function setInformation($information)
    {
        if (is_array($information)) {
          $information = json_encode($information, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        }
        $this->information = $information;

        return $this;
    }

    /**
     * Get information
     *
     * @return string
     */
    public function getInformation()
    {
        return $this->information;
    }

    /**
     * @var integer
     */
    private $error_flag;

    /**
     * @var integer
     */
    private $num;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var integer
     */
    private $group_start_id;

    /**
     * @var integer
     */
    private $group_start;

    /**
     * @var integer
     */
    private $group_end;


    /**
     * Set errorFlag
     *
     * @param integer $errorFlag
     *
     * @return TbLog
     */
    public function setErrorFlag($errorFlag)
    {
        $this->error_flag = $errorFlag;

        return $this;
    }

    /**
     * Get errorFlag
     *
     * @return integer
     */
    public function getErrorFlag()
    {
        return $this->error_flag;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return TbLog
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set size
     *
     * @param integer $size
     *
     * @return TbLog
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set groupStartId
     *
     * @param integer $groupStartId
     *
     * @return TbLog
     */
    public function setGroupStartId($groupStartId)
    {
        $this->group_start_id = $groupStartId;

        return $this;
    }

    /**
     * Get groupStartId
     *
     * @return integer
     */
    public function getGroupStartId()
    {
        return $this->group_start_id;
    }

    /**
     * Set groupStart
     *
     * @param integer $groupStart
     *
     * @return TbLog
     */
    public function setGroupStart($groupStart)
    {
        $this->group_start = $groupStart;

        return $this;
    }

    /**
     * Get groupStart
     *
     * @return integer
     */
    public function getGroupStart()
    {
        return $this->group_start;
    }

    /**
     * Set groupEnd
     *
     * @param integer $groupEnd
     *
     * @return TbLog
     */
    public function setGroupEnd($groupEnd)
    {
        $this->group_end = $groupEnd;

        return $this;
    }

    /**
     * Get groupEnd
     *
     * @return integer
     */
    public function getGroupEnd()
    {
        return $this->group_end;
    }
}
