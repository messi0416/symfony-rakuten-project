<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new AppBundle\AppBundle(),
            new Ne\ApiBundle\NeApiBundle(),
            new MiscBundle\MiscBundle(),
            new BCC\ResqueBundle\BCCResqueBundle(),
            new BatchBundle\BatchBundle(),
            new Plusnao\MainBundle\PlusnaoMainBundle(),
            new Plusnao\YahooAgentBundle\PlusnaoYahooAgentBundle(),

            new Ijanki\Bundle\FtpBundle\IjankiFtpBundle(),

            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
            new Knp\Bundle\SnappyBundle\KnpSnappyBundle(),
            new Liuggio\ExcelBundle\LiuggioExcelBundle(),
            new Oyrworks\Bundle\AMCBundle\OyrworksAMCBundle(), // added by Ryo Akaishi
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
          $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
