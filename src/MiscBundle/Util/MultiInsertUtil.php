<?php
// ORIGINAL source code is
// https://github.com/walf443/php-sql-maker-insert_multi

namespace MiscBundle\Util;

/**
 * @example
 *
 *      // simple case.
 *      $builder = new SQL_Maker_InsertMulti("your_table", array('fields' => array('id', 'name', 'created_at'));
 *      foreach ( $data as $row ) {
 *          $builder->bindRow(array(
 *              'id'            => $row['id'],
 *              'name'          => $row['name'],
 *              'created_at'    => $row['created_at'],
 *          );
 *      }
 *
 *      $stmt = $pdo->prepare($builer->toQuery());
 *      $stmt->execute($builer->binds());
 *
 *      // with bindValue.
 *      $builder = new SQL_Maker_InsertMulti("your_table", array('fields' => array(
 *          'id' => \PDO::PARAM_INT,
 *          'name'  => \PDO::PARAM_STR,
 *          'created_at' => \PDO::PARAM_STR,
 *      ));
 *      foreach ( $data as $row ) {
 *          $builder->bindRow(array(
 *              'id'            => $row['id'],
 *              'name'          => $row['name'],
 *              'created_at'    => $row['created_at'],
 *          );
 *      }
 *
 *      $stmt = $pdo->prepare($builer->toQuery());
 *      $builer->bindValues($stmt);
 *      $stmt->execute();
 */
class MultiInsertUtil
{
  private $quoteIdentifierChar;
  private $tableName;
  private $fields;
  private $binds;
  private $prefix;
  private $postfix;

  function __construct($tableName, $options)
  {
    $default_options = array(
      'quoteIdentifierChar' => '`',
      'appendCallerToComment' => true,
      'prefix' => 'INSERT INTO', // you can set "INSERT IGNORE INTO" or "REPLACE INTO"
      'postfix' => '', // ON DUPLICATE KEY UPDATE address = VALUES(address) ...
    );
    $options += $default_options;

    $this->binds = array();
    $this->tableName = $tableName;

    if ( !isset($options['fields']) ) {
      throw new \InvalidArgumentException("fields options is required");
    }
    $this->fields = $options['fields'];

    $this->quoteIdentifierChar = $options['quoteIdentifierChar'];
    $this->appendCallerToComment = $options['appendCallerToComment'];
    $this->prefix = $options['prefix'];
    $this->postfix = $options['postfix'];
  }

  public function bindRow(array $row) {
    foreach ( $this->fields() as $field ) {
      if ( !array_key_exists($field, $row) ) {
        throw new \InvalidArgumentException("\$row should have '$field' field");
      } else {
        array_push($this->binds, $row[$field]);
      }
    }

    return true;
  }

  public function fields()
  {
    if ( isset($this->fields[0]) ) {
      return $this->fields;
    } else {
      return array_keys($this->fields);
    }
  }

  public function binds()
  {
    return $this->binds;
  }

  public function toQuery()
  {

    $bindCount = count($this->binds);
    if ( $bindCount === 0 ) {
      throw new \LogicException("There are no binds");
    }
    $fieldCount = count($this->fields());
    $rowCount = $bindCount / $fieldCount;
    if ( $bindCount % $fieldCount !== 0 ) {
      throw new \LogicException("Invalid count of binds: got " . $bindCount . ", but expected " . $fieldCount . " * " . $rowCount . " = " . $fieldCount * $rowCount);
    }

    $result = $this->prefix . " " . $this->quoteIdentifier($this->tableName) . " ";
    $quoted_fields = array();

    // generate fields expression
    foreach ( $this->fields() as $field ) {
      array_push($quoted_fields, $this->quoteIdentifier($field));
    }
    $result .= "(" . implode(", ", $quoted_fields) . ")";
    $result .= " VALUES ";

    // generate value expression
    $row_strs = array();
    for ($i = 0; $i < ( $bindCount / $fieldCount ); $i++ ) {
      $row = array();
      for ($j = 0; $j < $fieldCount; $j++ ) {
        array_push($row, '?');
      }
      $row_str = "(" . implode(", ", $row) . ")";
      array_push($row_strs, $row_str);
    }
    $result .= implode(", ", $row_strs);

    $result .= " " . $this->postfix;

    if ( $this->appendCallerToComment ) {
      $result = $this->appendCallerToComment($result);
    }
    return $result;
  }

  /**
   * @param \PDOStatement|\Doctrine\DBAL\Statement $pdoStmt
   */
  public function bindValues($pdoStmt)
  {
    if ( isset($this->fields[0]) ) {
      throw new \LogicException("you should specify bindValue parameter to fields on constructor.");
    }
    $fieldCount = count($this->fields());
    $bindParamIndexOf = array();
    $index = 0;
    foreach ( $this->fields as $field => $bindParam ) {
      $bindParamIndexOf[$index] = $bindParam;
      $index++;
    }
    $i = 0;
    foreach ( $this->binds() as $bind ) {
      $bindValue = $bindParamIndexOf[$i % $fieldCount];
      $pdoStmt->bindValue($i + 1, $bind, $bindValue);
      $i++;
    }
  }

  public function appendCallerToComment($sql)
  {
    $callers = debug_backtrace(false);
    $traces = array();
    foreach ( $callers as $caller ) {
      if ( count($traces) >= 2 ) {
        break;
      }
      if ( isset($caller["file"]) ) {
        array_push($traces, $caller["file"] . ":" . $caller["line"]);
      }
    }

    // Because calling from toQuery, take second trace as caller.
    $sql .= " /* generated from " . $traces[1] . " by SQL_Maker_InsertMulti */";
    return $sql;
  }

  public function quoteIdentifier($arg)
  {
    if ( strpos($arg, $this->quoteIdentifierChar) !== false ) {
      throw new \InvalidArgumentException("Can't include quoteIdentifierChar to identifier");
    }
    return $this->quoteIdentifierChar . $arg . $this->quoteIdentifierChar;
  }
}
