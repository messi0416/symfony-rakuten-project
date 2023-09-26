<?php
	namespace Oyrworks\Bundle\AMCBundle\DependencyInjection;

	use Symfony\Component\HttpKernel\DependencyInjection\Extension;
	use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
	use Symfony\Component\DependencyInjection\ContainerBuilder;
	use Symfony\Component\Config\FileLocator;

	class OyrworksAMCExtension extends Extension
	{

		public function load(array $configs, ContainerBuilder $container)
		{
			$configuration = new Configuration();
			$config = $this->processConfiguration($configuration, $configs);
			
			$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
			$loader->load('services.yml');
		}
		
		public function getAlias()
		{
			return 'AMC';
		}
	}
?>
