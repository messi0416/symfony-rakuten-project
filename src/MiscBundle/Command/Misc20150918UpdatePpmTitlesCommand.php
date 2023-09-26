<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class Misc20150918UpdatePpmTitlesCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('misc:20150918_update_ppm')
      ->setDescription('ポンパレモール用データ 商品名が長すぎるエラー対応データ修正');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger LoggerInterface */
    $logger = $this->getContainer()->get('logger');

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $this->getContainer()->get('doctrine.dbal.default_connection');

    $sql = <<<EOD
SELECT
      daihyo_syohin_code
    , ppm_title
FROM tb_ppm_information
ORDER BY daihyo_syohin_code
LIMIT 10 /* FOR TEST */
EOD;

    $stmt = $db->prepare($sql);
    $stmt->execute();

    while($row = $stmt->fetch()) {
      $output->writeln($row['ppm_title']);
    }

    $output->writeln('done!!');
  }

}
