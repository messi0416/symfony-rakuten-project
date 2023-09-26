<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use Doctrine\ORM\QueryBuilder;
use forestlib\GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MiscBundle\Entity\Repository\TbPlusnaoproductdirectoryRepository;
use MiscBundle\Entity\Repository\TbRakutenCategoryForSalesRankingRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;


class ASyncProcessTestCommand extends ContainerAwareCommand
{
  /** @var InputInterface */
  protected $input;

  /** @var OutputInterface */
  protected $output;

  /** @var BatchLogger */
  protected $logger;

  /** @var DbCommonUtil  */
  protected $commonUtil;

  protected function configure()
  {
    $this
      ->setName('misc:async-test')
      ->setDescription('Process クラスの挙動確認');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;

    $this->logger = $this->getContainer()->get('misc.util.batch_logger');

    $container = $this->getContainer();

    $doctrine = $container->get('doctrine');
    // var_dump(get_class($doctrine));

    $this->commonUtil = $container->get('misc.util.db_common');

    $fileUtil = $container->get('misc.util.file');
    $output->writeln($fileUtil->getRootDir());

    $this->logger->info('非同期処理 起動終了');

    $command = $fileUtil->getRootDir() . "/console misc:wait-test";
    /*
    $arguments = [];
    $command .= ' ' . implode(' ', $arguments);
    */

    $process = new Process($command);
    $process->start();

    $this->logger->info('非同期処理 起動終了');

    $output->writeln('done!');
  }


}
