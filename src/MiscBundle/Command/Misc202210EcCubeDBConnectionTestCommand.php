<?php
namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Misc202210EcCubeDBConnectionTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
    ->setName('misc:202210-ec-cube-db-connection-test')
    ->setDescription('EC-CUBEのMySQLに接続するためのテストコマンド')
    ;
  }

  /**
   * EC-CUBEのMySQLに接続するためのテストプログラム
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    $logger->info('EC-CUBEのMySQL接続テストを開始しました。');

    // EC-CUBEのMySQLサーバーに接続してSELECT文を実行する
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('ec_cube');
    $sql = <<<EOD
      select * from dtb_order_item
      ;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    foreach($stmt as $row) {
        $result['results'][] = $row;
    }

    var_dump($result);
  }

}
