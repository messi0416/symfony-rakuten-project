<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
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
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Util\FileLogger;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\StringUtil;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;


class Misc20170803CreateVariationImageListCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:20170803-create-variation-image-list')
      ->setDescription('日次処理 カラバリ画像作成臨時実行');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $logger = $this->getLogger();

    $today = new \DateTime();

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');

    $fs = new Filesystem();
    $logDir = sprintf('%s/variation_images', $fileUtil->getLogDir());
    if (!$fs->exists($logDir)) {
      $fs->mkdir($logDir, 0755);
    }
    $logFilePath = sprintf('%s/variation_images.%s.gz', $logDir, $today->format('Ymd'));
    $imageDir = $this->getContainer()->getParameter('product_image_variation_dir');

    $command = '/usr/bin/find "' . $imageDir . '" -type f -printf "%TY-%Tm-%Td %TH:%TM:%TS %p %k\\n"'
      . ' | sed -e "s|' . $imageDir . '/||"'
      . ' | sort -k 3 '
      . ' | gzip -c > "' . $logFilePath . '"';

    $logger->info($command);

    try {

      $process = new Process($command);
      $process->mustRun();

    } catch (\Exception $e) {
      $logger->error('共通日次バッチ処理 バリエーション画像一覧作成 エラー:' . $e->getMessage());
    }

    $output->writeln('done!');
  }

}
