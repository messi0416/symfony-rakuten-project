<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\SymfonyUserClient;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\Form;

/**
 * SymfonyUserClientRepository
 */
class SymfonyUserClientRepository extends BaseRepository
{
  const NON_AGENT_LOGIN_NAME = 'default';

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
   * 依頼先IDで一括削除
   * @param integer $agentId
   * @throws \Doctrine\DBAL\DBALException
   */
  public function removeByAgentId($agentId)
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      DELETE u
      FROM symfony_user_client u
      WHERE u.agent_id = :agentId
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':agentId', $agentId, \PDO::PARAM_INT);
    $stmt->execute();

    return;
  }

  /**
   * ログイン用： ユーザ名とagentNameで取得
   * @param $userName
   * @param $agentName
   * @return SymfonyUserClient
   */
  public function findByUsernameAndAgentName($userName, $agentName)
  {
    $user = null;
    $qb = $this->createQueryBuilder('u')
      ->where('u.is_active <> 0')
      ->andWhere('u.username = :username')->setParameter(':username', $userName, \PDO::PARAM_STR)
      ->orderBy('u.id');

    if ($agentName === self::NON_AGENT_LOGIN_NAME) {
      $qb->andWhere('u.agent_id = 0');
    } else {
      $qb->innerJoin(PurchasingAgent::class, 'a', JOIN::WITH, 'u.agent_id = a.id')
        ->andWhere('a.login_name = :agentName')->setParameter(':agentName', $agentName, \PDO::PARAM_STR);
   }

    $results = $qb->getQuery()->getResult();
    if ($results) {
      $user = $results[0];
    }

    return $user;
  }


}
