<?php

namespace MiscBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MiscCompilerPass implements CompilerPassInterface
{
  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container)
  {
    $repositoryFactory = $container->getDefinition('misc.doctrine.orm.repository_factory');
    $container->findDefinition('doctrine.orm.configuration')->addMethodCall('setRepositoryFactory', [$repositoryFactory]);
  }
}
