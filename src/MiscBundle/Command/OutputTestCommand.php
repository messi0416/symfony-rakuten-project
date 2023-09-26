<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use BatchBundle\Command\CommandBaseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class OutputTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  protected function configure()
  {
    $this
      ->setName('misc:output-test')
      ->setDescription('ResqueのJobから出力した時の挙動を確認するためのコマンド');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $logger = $this->getLogger();

    // $output->writeln('done!');

    $logger->info('出力テスト 開始');

    echo "出力テスト echo \n";

    $logger->info('出力テスト echo OK');

    fputs(STDOUT, "出力テスト STDOUT");
    $logger->info('出力テスト STDOUT OK');

    fputs(STDERR, "出力テスト STDERR");
    $logger->info('出力テスト STDERR OK');

    $logger->info('出力テスト 終了');

    return 0;
  }

}
