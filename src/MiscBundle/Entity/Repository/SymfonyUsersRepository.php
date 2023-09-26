<?php

namespace MiscBundle\Entity\Repository;

use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\Form;

/**
 * SymfonyUsersRepository
 */
class SymfonyUsersRepository extends BaseRepository
{
  /**
   * ログイン可能アカウント 一覧取得
   */
  public function getActiveAccounts()
  {
    $qb = $this->createQueryBuilder('u')
          ->where('u.is_active <> 0')
          ->orderBy('u.id');
    return $qb->getQuery()->getResult();
  }
  /**
   * ログイン可能アカウント ユーザ名、ユーザコード一覧取得
   */
  public function getActiveAccountsNameAndCd()
  {
    $qb = $this->createQueryBuilder('u')
    ->select('u.id,u.username,u.user_cd')
    ->where('u.is_active <> 0')
    ->orderBy('u.id');
    return $qb->getQuery()->getResult();
  }
  /**
   * ログインアカウントの倉庫IDとユーザID取得
   */
  public function getLoginAccountsWarehouseId()
  {
    $userWarehouseId = $this->createQueryBuilder('uw')
           ->select('uw.id,uw.warehouse_id')
           ->orderBy('uw.id');

    return $userWarehouseId->getQuery()->getResult();
  }
  /**
   * ユーザコードに対するユーザ名取得
   */
  public function getUserName($userCd)
  {
    $qb = $this->createQueryBuilder('u')
    ->select('u.username')
    ->where('u.user_cd = :userCd')
    ->setParameters(array(
      'userCd' => $userCd
    ));
    return $qb->getQuery()->getResult();
  }
  /**
   * 指定したユーザID以外でユーザコードが一致するデータを取得
   */
  public function findByCdWithDifferentId($userCd, $userId)
  {
    $qb = $this->createQueryBuilder('u')
    ->where('u.id != :userId and u.user_cd = :userCd')
    ->setParameters(array(
      'userId' => $userId,
      'userCd' => $userCd
    ));
    return $qb->getQuery()->getResult();
  }

  /**
   * 商品売上担当者の権限のあるユーザーのIDとusernameを取得
   */
  public function findRoleSalesProductAccountIdAndName()
  {
    $sql = <<<EOD
      SELECT
        id
        , username 
      FROM
        symfony_users 
      WHERE
        roles LIKE '%ROLE_SALES_PRODUCT_ACCOUNT%'
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    if (!$result) {
      return [];
    }
    return $result;
  }

  /**
   * 指定した権限を持つユーザのidを配列で返す。
   * @param array $roles 権限の配列
   * @return array ユーザidの配列
   */
  public function findUsersWithRole($roles)
  {
    $dbMain = $this->getConnection('main');

    $wheres = [];
    $params = [];
    $i = 1;
    foreach ($roles as $role) {
      $wheres[] = "roles LIKE :role{$i}";
      $params[":role{$i}"] = $role;
      $i++;
    }
    $addWhere = implode(' OR ', $wheres);

    $sql = <<<EOD
      SELECT
        id
      FROM
        symfony_users
      WHERE
        {$addWhere}
EOD;
    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, "%{$v}%", \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }
}
