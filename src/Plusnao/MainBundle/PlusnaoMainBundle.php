<?php

namespace Plusnao\MainBundle;

use MiscBundle\DependencyInjection\Compiler\MiscCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PlusnaoMainBundle extends Bundle
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
