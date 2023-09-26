<?php

	namespace Oyrworks\Bundle\AMCBundle;

	use MiscBundle\DependencyInjection\Compiler\MiscCompilerPass;
	use Symfony\Component\DependencyInjection\ContainerBuilder;
	use Symfony\Component\HttpKernel\Bundle\Bundle;
	use Oyrworks\Bundle\AMCBundle\DependencyInjection\OyrworksAMCExtension;

	class OyrworksAMCBundle extends Bundle
	{
		public function getContainerExtension()
		{
			return new OyrworksAMCExtension();
		}

		public function build(ContainerBuilder $container)
		{
			// RepositoryFactory 差し替え
			parent::build($container);
			$container->addCompilerPass(new MiscCompilerPass());
		}
	}

?>
