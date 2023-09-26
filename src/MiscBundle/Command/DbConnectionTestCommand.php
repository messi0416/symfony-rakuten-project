<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use MiscBundle\Entity\Repository\TbProductLocationLogRepository;
use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DbConnectionTestCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('misc:db-connection-test')
      ->setDescription('DB接続の確認')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $doctrine = $this->getContainer()->get('doctrine');
//    var_dump(get_class($doctrine));

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->getContainer()->get('doctrine')->getConnection('main');

    $logger->info('(command)' . $db->getHost());
    $logger->info('(command)' . $db->getDatabase());
    $logger->info('(command)' . $db->getUsername());

    $db = $this->getContainer()->get('doctrine')->getConnection('log');
    $logger->info('(command)' . $db->getHost());
    $logger->info('(command)' . $db->getDatabase());
    $logger->info('(command)' . $db->getUsername());

    // レポジトリからの接続テスト
    /** @var TbProductLocationLogRepository $repo */
    $repo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbProductLocationLog', 'log');
    $logger->info('(command)' . $repo->getDatabaseName());

    /*
    // CubeSV01 DB接続テスト
    $db = $this->getContainer()->get('doctrine')->getConnection('ec_batch');
    $logger->info('(command)' . $db->getHost());
    $logger->info('(command)' . $db->getDatabase());
    $logger->info('(command)' . $db->getUsername());
    foreach($db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_NUM) as $row) {
      $logger->info('(tables)' . $row[0]);
    }

    $db = $this->getContainer()->get('doctrine')->getConnection('ec01');
    $logger->info('(command)' . $db->getHost());
    $logger->info('(command)' . $db->getDatabase());
    $logger->info('(command)' . $db->getUsername());
    foreach($db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_NUM) as $row) {
      $logger->info('(tables)' . $row[0]);
    }
    */

    // ユーザ関連
//    $repo = $doctrine->getRepository('MiscBundle:SymfonyUsers');

//    // ユーザ作成テスト
////    $user = new SymfonyUsers();
////    $user->setUsername('平井 和彦');
////    $user->setPassword('12345678');
//    /** @var \Doctrine\ORM\EntityManager $em */
//    $em = $this->getContainer()->get('doctrine')->getEntityManager();
////    $em->persist($user);
//
//    $user = $repo->find('1');
//    print_r($user);
//    $user->setUsername('hoge テスト平井');
//    $user->setPassword('mogemoge');
//    $user->setIsActive(true);
//
//    // $em->flush();
//
//    print_r($user);
//

  }

}
