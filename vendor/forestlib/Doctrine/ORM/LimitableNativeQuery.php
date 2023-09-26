<?php
namespace forestlib\Doctrine\ORM;

use \Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use \Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

class LimitableNativeQuery extends AbstractQuery
{

  /**
   * @var string
   */
  private $sqlSelectPart = 'SELECT *';

  /**
   * @var string
   */
  private $sqlCountPart = 'SELECT COUNT(*) AS cnt';

  /**
   * @var string
   */
  private $sqlBodyPart = '';

  /**
   * @var array
   */
  private $orders = [];

  /**
   * The first result to return (the "offset").
   *
   * @var integer
   */
  private $_firstResult = null;

  /**
   * The maximum number of results to return (the "limit").
   *
   * @var integer
   */
  private $_maxResults = null;

  /**
   * create self
   * @param \Doctrine\ORM\EntityManagerInterface $em
   * @param ResultSetMapping $rsm
   * @param string $sqlSelectPart
   * @param string $sqlBodyPart
   * @param string $sqlCountPart
   * @return LimitableNativeQuery
   */
  public static function createQuery(EntityManagerInterface $em, ResultSetMapping $rsm, $sqlSelectPart = null, $sqlBodyPart = null, $sqlCountPart = null)
  {
    $query = new self($em);
    $query->setResultSetMapping($rsm);

    if ($sqlSelectPart) {
      $query->setSqlSelectPart($sqlSelectPart);
    }
    if ($sqlBodyPart) {
      $query->setSqlBodyPart($sqlBodyPart);
    }
    if ($sqlCountPart) {
      $query->setSqlCountPart($sqlCountPart);
    }

    return $query;
  }


  /**
   * Sets the 'SELECT' part of the query.
   * @param string $sql
   *
   * @return LimitableNativeQuery This query instance.
   */
  public function setSqlSelectPart($sql)
  {
    $this->sqlSelectPart = $sql;
    return $this;
  }

  /**
   * Sets the 'SELECT COUNT() ' part of the query.
   * @param string $sql
   *
   * @return LimitableNativeQuery This query instance.
   */
  public function setSqlCountPart($sql)
  {
    $this->sqlCountPart = $sql;
    return $this;
  }

  /**
   * Sets the remain of 'SELECT' part of the query.
   * ※ 後ろに ORDER BY 句, LIMIT句が接続できる形でないとSQLエラー
   *
   * @param string $sql
   *
   * @return LimitableNativeQuery This query instance.
   */
  public function setSqlBodyPart($sql)
  {
    $this->sqlBodyPart = $sql;
    return $this;
  }

  /**
   * @param array
   * @return LimitableNativeQuery
   */
  public function setOrders($orders)
  {
    $this->orders = $orders;
    return $this;
  }

  /**
   * SELECT SQL の取得
   * @return string
   */
  public function getSelectSql()
  {
    if (!strlen($this->sqlSelectPart)) {
      throw new \RuntimeException('no select part.');
    }

    $sql = $this->sqlSelectPart
         . ' ' . $this->sqlBodyPart;

    if ($this->orders) {
      $orderSql = ' ORDER BY ';
      $orders = [];
      foreach($this->orders as $field => $direction) {
        if (!strlen($field)) {
          continue;
        }
        if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) {
          continue;
        }

        $ele = explode('.', $field);
        foreach($ele as $i => $v) {
          $ele[$i] = $this->_em->getConnection()->quoteIdentifier($v);
        }

        $orders[] = sprintf('%s %s', implode('.', $ele), $direction);
      }

      if ($orders) {
        $orderSql .= implode(', ', $orders);
        $sql .= $orderSql;
      }
    }

    if (!is_null($this->_maxResults)) {
      $sql .= sprintf(' LIMIT %d', $this->_maxResults);

      if (!is_null($this->_firstResult)) {
        $sql .= sprintf(' OFFSET %d', $this->_firstResult);
      }
    }

    return $sql;
  }

  /**
   * SELECT COUNT SQLの取得
   * ORDER BY, LIMIT, OFFSET は無し。
   * @return string
   */
  public function getCountSql()
  {
    if (!strlen($this->sqlCountPart)) {
      throw new \RuntimeException('no select count part.');
    }

    $sql = $this->sqlCountPart
      . ' ' . $this->sqlBodyPart;

    return $sql;
  }

  /**
   * @return int
   * @throws \Doctrine\DBAL\DBALException
   */
  public function count()
  {
    $result = $this->doSelect($this->getCountSql());
    return intval($result->fetchColumn(0));
  }

  /**
   * @return \Doctrine\DBAL\Driver\Statement
   */
  public function select()
  {
    return $this->doSelect($this->getSelectSql());
  }

  /**
   * @param $sql
   * @return \Doctrine\DBAL\Driver\Statement
   * @throws \Doctrine\DBAL\DBALException
   */
  private function doSelect($sql)
  {
    $parameters = array();
    $types      = array();

    foreach ($this->getParameters() as $parameter) {
      $name  = $parameter->getName();
      $value = $this->processParameterValue($parameter->getValue());
      $type  = ($parameter->getValue() === $value)
        ? $parameter->getType()
        : Query\ParameterTypeInferer::inferType($value);

      $parameters[$name] = $value;
      $types[$name]      = $type;
    }

    if ($parameters && is_int(key($parameters))) {
      ksort($parameters);
      ksort($types);

      $parameters = array_values($parameters);
      $types      = array_values($types);
    }

    $result = $this->_em->getConnection()->executeQuery(
      $sql, $parameters, $types, $this->_queryCacheProfile
    );

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function _doExecute()
  {
    return $this->select();
  }

  /**
   * Sets the position of the first result to retrieve (the "offset").
   *
   * @param integer $firstResult The first result to return.
   *
   * @return Query This query object.
   */
  public function setFirstResult($firstResult)
  {
    $this->_firstResult = $firstResult;

    return $this;
  }

  /**
   * Gets the position of the first result the query object was set to retrieve (the "offset").
   * Returns NULL if {@link setFirstResult} was not applied to this query.
   *
   * @return integer The position of the first result.
   */
  public function getFirstResult()
  {
    return $this->_firstResult;
  }

  /**
   * Sets the maximum number of results to retrieve (the "limit").
   *
   * @param integer $maxResults
   *
   * @return Query This query object.
   */
  public function setMaxResults($maxResults)
  {
    $this->_maxResults = $maxResults;

    return $this;
  }

  /**
   * Gets the maximum number of results the query object was set to retrieve (the "limit").
   * Returns NULL if {@link setMaxResults} was not applied to this query.
   *
   * @return integer Maximum number of results.
   */
  public function getMaxResults()
  {
    return $this->_maxResults;
  }


  /**
   * Gets the SQL query that corresponds to this query object.
   * The returned SQL syntax depends on the connection driver that is used
   * by this query object at the time of this method call.
   *
   * @return string SQL query
   */
  public function getSQL()
  {
    return $this->getSelectSql();
  }
}
