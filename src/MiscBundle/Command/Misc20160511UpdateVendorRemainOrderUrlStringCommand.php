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
use MiscBundle\Entity\TbVendormasterdata;
use MiscBundle\Util\StringUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Misc20160511UpdateVendorRemainOrderUrlStringCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:20160511-update-vendor-remain-order-url-string')
      ->setDescription('2016/05/11 仕入先 注残一覧画面 URL文字列一括更新処理');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    /** @var BaseRepository $repo */
    $repo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbVendormasterdata');

    /** @var TbVendormasterdata[] $vendors */
    $vendors = $repo->findAll();

    /** @var StringUtil $util */
    $util = $this->getContainer()->get('misc.util.string');

    foreach($vendors as $vendor) {
      $vendor->setRemainingOrderUrlString($util->makeRandomString(32));
    }

    $em = $this->getDoctrine()->getManager('main');
    $em->flush();

    $output->writeln('done!');
  }

}
