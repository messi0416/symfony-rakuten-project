<?php
namespace AppBundle\Security\User;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SymfonyUserProductEditorProvider implements UserProviderInterface
{
  /** @var Container */
  protected $container;

  /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
  protected $doctrine;

  /** @var \Doctrine\DBAL\Connection[] */
  protected $dbConnections = [];

  /**
   * @param Container $container
   */
  public function setContainer($container)
  {
    $this->container = $container;
  }

  /**
   * @return Container $container
   */
  public function getContainer()
  {
    return $this->container;
  }


  /**
   * @param string
   * @return \Doctrine\Bundle\DoctrineBundle\Registry
   */
  protected function getDoctrine()
  {
    if (!isset($this->doctrine)) {
      $this->doctrine = $this->getContainer()->get('doctrine');
    }
    return $this->doctrine;
  }

  /**
   * @param string $name
   * @return \Doctrine\DBAL\Connection
   */
  protected function getDb($name)
  {
    if (!array_key_exists($name, $this->dbConnections)) {
      $this->dbConnections[$name] = $this->getDoctrine()->getConnection($name);
    }

    return isset($this->dbConnections[$name]) ? $this->dbConnections[$name] : null;
  }


  // ------------------------------------------------
  // additional methods
  // ------------------------------------------------
  /**
   * 一時キーで業務一括管理システムからユーザ取得
   * @param $key
   * @return SymfonyUserProductEditor|null
   */
  public function loadUserByCakeUserByKey($key)
  {
    $user = null;

    if (!strlen($key)) {
      return null;
    }

    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      SELECT
        u.*
      FROM users u
      WHERE u.auth_token = :key
        AND u.auth_token_expires > NOW()
      LIMIT 1
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':key', $key, \PDO::PARAM_STR);
    $stmt->execute();

    if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $user = $this->createUser(sprintf('(出品)' . $row['name']));
    }

    return $user;
  }

  /**
   * Userオブジェクトを作成
   * @param $username
   * @return SymfonyUserProductEditor
   */
  public function createUser($username)
  {
    $user = new SymfonyUserProductEditor();
    $user->setUsername($username);
    return $user;
  }




  // ------------------------------------------------
  // interface methods
  // ------------------------------------------------
  /**
   * Loads the user for the given username.
   *
   * This method must throw UsernameNotFoundException if the user is not
   * found.
   *
   * @param string $username The username
   *
   * @return UserInterface
   *
   * @see UsernameNotFoundException
   *
   * @throws UsernameNotFoundException if the user is not found
   */
  public function loadUserByUsername($username)
  {
    return $this->createUser($username);
  }

  /**
   * Refreshes the user for the account interface.
   *
   * It is up to the implementation to decide if the user data should be
   * totally reloaded (e.g. from the database), or if the UserInterface
   * object can just be merged into some internal array of users / identity
   * map.
   *
   * @param UserInterface $user
   *
   * @return UserInterface
   *
   * @throws UnsupportedUserException if the account is not supported
   */
  public function refreshUser(UserInterface $user)
  {
    if (!$user instanceof SymfonyUserProductEditor) {
      throw new UnsupportedUserException(
        sprintf('Instances of "%s" are not supported.', get_class($user))
      );
    }

    return $this->loadUserByUsername($user->getUsername());
  }

  /**
   * Whether this provider supports the given user class.
   *
   * @param string $class
   *
   * @return bool
   */
  public function supportsClass($class)
  {
    return SymfonyUserProductEditor::class === $class;
  }
}
