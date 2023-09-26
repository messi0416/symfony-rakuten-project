<?php

namespace MiscBundle\Extend\Doctrine\ORM;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerAwareEntityRepository extends EntityRepository implements ContainerAwareInterface
{
  /**
   * Container
   * @var ContainerInterface
   */
  private $container;

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }

  /**
   * Get Container
   * @return ContainerInterface
   */
  public function getContainer()
  {
    return $this->container;
  }
}
