<?php

namespace Ne\ApiBundle;

use MiscBundle\DependencyInjection\Compiler\MiscCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NeApiBundle extends Bundle
{
  /**
   * {@inheritdoc}
   */
  public function build(ContainerBuilder $container)
  {
    // RepositoryFactory 差し替え
    parent::build($container);
    $container->addCompilerPass(new MiscCompilerPass());
  }
}
