<?php
namespace AppBundle\Security\User;

use MiscBundle\Entity\PurchasingAgent;
use MiscBundle\Entity\Repository\BaseRepository;
use MiscBundle\Entity\Repository\SymfonyUserClientRepository;
use MiscBundle\Entity\SymfonyUserClient;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class SymfonyUserClientProvider implements UserProviderInterface
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


  // ----------------------------------

  public function loadUserByUsername($username, $agentName = null)
  {
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $this->getContainer()->get('request');
    if (!$agentName) {
      $agentName = $request->cookies->get('agentName');
    }

    /** @var SymfonyUserClientRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUserClient');
    $user = $repo->findByUsernameAndAgentName($username, $agentName);

    if ($user) {
      /** @var BaseRepository $repoAgent */
      $repoAgent = $this->getDoctrine()->getRepository('MiscBundle:PurchasingAgent');
      /** @var PurchasingAgent $agent */
      $agent = $repoAgent->find($user->getAgentId());
      if ($agent) {
        $user->setAgent($agent);
      }
      return $user;
    }

    throw new UsernameNotFoundException(
      sprintf('Username "%s" does not exist.', $username)
    );
  }

  public function refreshUser(UserInterface $user)
  {
    if (!$user instanceof SymfonyUserClient) {
      throw new UnsupportedUserException(
        sprintf('Instances of "%s" are not supported.', get_class($user))
      );
    }

    return $this->loadUserByUsername($user->getUsername());
  }

  public function supportsClass($class)
  {
    return SymfonyUserClient::class === $class;
  }
}
