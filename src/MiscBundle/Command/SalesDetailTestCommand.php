<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use Doctrine\ORM\Query;
use MiscBundle\Entity\Repository\BaseRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SalesDetailTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:sales-detail')
      ->setDescription('受注明細テスト');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $output->writeln($this->getFileUtil()->getRootDir());

    $container = $this->getContainer();

    // モール受注CSVデータ取得

    /** @var BaseRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbNeMallOrder');

    $mallOrder = $repo->findOneBy([
        'voucher_number' => ''
      , 'line_number' => ''
    ]);

    $output->writeln('done!');
  }


}
