<?php

namespace MiscBundle\Extend\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use MiscBundle\Extend\Doctrine\ORM\ContainerAwareEntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;


class ContainerAwareRepositoryFactory implements \Doctrine\ORM\Repository\RepositoryFactory
{
  /**
   * The list of EntityRepository instances.
   *
   * @var \Doctrine\Common\Persistence\ObjectRepository[]
   */
  private $repositoryList = array();

  /**
   * {@inheritdoc}
   */
  public function getRepository(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityName)
  {
    $repositoryHash = $entityManager->getClassMetadata($entityName)->getName() . spl_object_hash($entityManager);

    if (isset($this->repositoryList[$repositoryHash])) {
      return $this->repositoryList[$repositoryHash];
    }

    $repository = $this->createRepository($entityManager, $entityName);

    // set container
    if ($repository instanceof ContainerAwareEntityRepository) {
      $repository->setContainer($this->container);
    }

    $this->repositoryList[$repositoryHash] = $repository;

    return $repository;
  }

  /**
   * Create a new repository instance for an entity class.
   *
   * @param \Doctrine\ORM\EntityManagerInterface $entityManager The EntityManager instance.
   * @param string                               $entityName    The name of the entity.
   *
   * @return \Doctrine\Common\Persistence\ObjectRepository
   */
  private function createRepository(EntityManagerInterface $entityManager, $entityName)
  {
    /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
    $metadata            = $entityManager->getClassMetadata($entityName);
    $repositoryClassName = $metadata->customRepositoryClassName
      ?: $entityManager->getConfiguration()->getDefaultRepositoryClassName();

    return new $repositoryClassName($entityManager, $metadata);
  }

  // --------------------------------------
  /**
   * Container
   * @var ContainerInterface
   */
  private $container;

  /**
   * @param ContainerInterface $container
   */
  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
  }

}
