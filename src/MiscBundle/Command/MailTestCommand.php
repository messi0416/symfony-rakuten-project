<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use Doctrine\ORM\QueryBuilder;
use MiscBundle\Util\BatchLogger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class MailTestCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('misc:mail-test')
      ->setDescription('メール送信の確認');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var $logger BatchLogger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $message = <<<EOD
メール送信テストです。
二行目
三行目
EOD;

    $logger->sendErrorMail('テストエラー', $message);

    $logger->addDbLog(
      $logger->makeDbLog('テスト', 'タイトル', 'タイトル1', 'タイトル2', 'タイトル3')
      , true
      , 'テストエラーメッセージ'
      , 'error'
    );
  }

}
