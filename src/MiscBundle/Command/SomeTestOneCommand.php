<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\NextEngineMallProcess;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Doctrine\ORM\Query;
use BatchBundle\MallProcess\RakutenMallProcess;
use forestlib\GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MiscBundle\Entity\Repository\BatchLockRepository;
use MiscBundle\Entity\Repository\TbDiscountListRepository;
use MiscBundle\Entity\Repository\TbProductLocationLogRepository;
use MiscBundle\Entity\Repository\TbRakutenCategoryForSalesRankingRepository;
use MiscBundle\Entity\Repository\TbVendoraddressRepository;
use MiscBundle\Entity\TbDiscountList;
use MiscBundle\Util\FileLogger;
use MiscBundle\Util\StringUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;


class SomeTestOneCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:some-test-one')
      ->setDescription('なんだかんだテスト');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $output->writeln($this->getFileUtil()->getRootDir());

    $container = $this->getContainer();

    $this->process();


    $output->writeln('done!');
  }

  private function process()
  {
    /** @var NextEngineMallProcess $process */
    $process = $this->getContainer()->get('batch.mall_process.next_engine');

    $dir = '/home/workuser/working/ne_api/data/stocks/20170530162427';

    $process->downloadNextEngineStockDataOriginal($dir, null, null, false);

    $this->output->writeln('import!!');
  }

}

