<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use BatchBundle\Command\CommandBaseTrait;
use Doctrine\ORM\QueryBuilder;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\WebAccessUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\BrowserKit\Cookie;


class ShoplistLoginTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('misc:shoplist-login-test')
      ->setDescription('SHOPLIST ログインの確認')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->initLogTimer();

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    // SHOPLIST ログイン
    $client = $webAccessUtil->getWebClient();

//    $url = 'https://service.shop-list.com/shopadmin/';
//    $crawler = $client->request('get', $url);
//
//    $shopCode = 'plusnao';
//    $account  = '6cc729a0';
//    $password = '7550b3af';

    $crawler = $webAccessUtil->shoplistLogin($client);
    exit;

    $response = $client->getResponse();
    $request = $client->getRequest();

    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getStatus(), true));
    $logger->info(print_r($request->getUri(), true));

    // $logger->info(print_r($crawler->html(), true));

    $form = $crawler->selectButton('login')->form();


    // $form = $crawler->selectButton('Login')->form();
    // $logger->info(print_r($form, true));

    return 1;

  }

}
