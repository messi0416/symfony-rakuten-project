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
use MiscBundle\Entity\TbIndividualorderhistory;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\VProductCostRateItem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DoctrineTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:doctrine-test')
      ->setDescription('ORM テスト');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $output->writeln($this->getFileUtil()->getRootDir());

    $em = $this->getDoctrine()->getManager('main');

    // readonly テスト
    $dbMain = $this->getDb('main');

    /** @var TbProductchoiceitems $item */
    $item = $this->getDoctrine()->getRepository('MiscBundle:TbProductchoiceitems')->findOneBy([]);

    var_dump($item->getNeSyohinSyohinCode());

    $dbMain->query("DELETE FROM tb_individualorderhistory WHERE 発注伝票番号 = 999999");

    $ind = new TbIndividualorderhistory();
    $ind->setChoiceItem($item);
    $ind->setVoucherNumber('999999');
    // $ind->setLineNumber();
    $ind->setSireCode('0190');
    $ind->setOrderNum('10');

    $em->persist($ind);
    $em->flush();

    $item->setStock($item->getStock() + 10);
    $item->setFreeStock(10);
    $em->flush();

//    $this->pagerTest();
//    exit;

//    $this->limitableNativeQueryTest();
//    exit;

    /** @var VProductCostRateItemRepository $repo */
    // $repo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:VProductCostRateItem');
    // $result = $repo->getListPagination();

    $output->writeln('done!');
  }

  private function limitableNativeQueryTest()
  {
    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager('main');

    $sqlSelect = <<<EOD
      SELECT
          m.daihyo_syohin_code
        , cal.baika_tnk
EOD;
    $sqlBody = <<<EOD
      FROM tb_mainproducts m
      INNER JOIN tb_mainproducts_cal cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE cal.baika_tnk > :tanka
EOD;

    $rsm =  new ResultSetMappingBuilder($em);
    $rsm->addRootEntityFromClassMetadata('MiscBundle:VProductCostRateItem', 'm');

    $query = LimitableNativeQuery::createQuery($em, $rsm, $sqlSelect, $sqlBody);
    $query->setParameter(':tanka', 1500);
    $query->setOrders(['cal.baika_tnk' => 'ASC']);

    $query->setFirstResult(0);
    $query->setMaxResults(10);

    var_dump($query->count());
    /** @var VProductCostRateItem[] $products */
    $products = $query->getResult();


//
//    /** @var \Knp\Component\Pager\Paginator $paginator */
//    $paginator  = $this->getContainer()->get('knp_paginator');
//    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $pagination */
//    $pagination = $paginator->paginate(
//        $query /* query NOT result */
//      , $page
//      , $limit
//    );
//
//    // $pagination に条件を追加
//    $pagination->setParam('test', 'hoge');
//
//    /** @var VProductCostRateItem[] $products */
//    $products = $query->getResult();
//

    foreach($products as $product) {
      var_dump(get_class($product));
      var_dump($product->getBaikaTnk());
      // var_dump(get_class);
    }

  }

  private function pagerTest()
  {
    $paginator = $this->getContainer()->get('knp_paginator');
    /** @var \Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination $files */
    $files = $paginator->paginate(
      __DIR__.'/../'
      , 1 // page
      , 10 // limit
    );

    var_dump(get_class($files));
    var_dump($files->count());
    foreach($files as $file) {
      // var_dump(get_class($file));
      var_dump($file);
    }

    return compact('files');
  }

}
