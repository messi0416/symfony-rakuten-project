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
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\Repository\VProductCostRateItemRepository;
use MiscBundle\Entity\VProductCostRateItem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class PdfTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:pdf-test')
      ->setDescription('ORM テスト');
  }

  ///
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $output->writeln($this->getFileUtil()->getRootDir());

    /** @var \Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator $snappy  */
    $snappy = $this->getContainer()->get('knp_snappy.pdf');
    $snappy->generate('http://www.rakuten.co.jp/plusnao/', '/tmp/test.pdf');

    $output->writeln('done!');
  }

}
