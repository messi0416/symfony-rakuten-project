<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


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


class RmsLoginTestCommand extends ContainerAwareCommand
{
  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('misc:rms-login-test')
      ->setDescription('RMSログインの確認')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();

    /** @var BatchLogger $logger */
    $logger = $container->get('misc.util.batch_logger');
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

    $doctrine = $container->get('doctrine');

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $container->get('misc.util.web_access');
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }

    // RMS ログイン
    $client = $webAccessUtil->getWebClient();

    $url = 'https://rdatatool.rms.rakuten.co.jp/access/?menu=pc&evt=RT_P03_01&stat=1';


    // s_sess
    $cookie = new Cookie('', '%20s_cc%3Dtrue%3B%20s_prevsite%3Djprms%3B%20s_sq%3D%3B'
      , null, $path =  '/', $domain = '.rakuten.co.jp', $secure = false, $httponly = false, $encodedValue = true);
    $client->getCookieJar()->set($cookie);

    $cookie = new Cookie('friedrice', 'MWE3ODBjYTc1NmU5OWFiYzZlNDA1MDJkYjNlZjZjYjJkZWNlMDkxYmN7eT8Z21g_CxwG7OvTLrj5nYJrsnERuN5cdUS-mlzVIgtjiyDv3VaHN35GFvCwIXweyEAE6DlS-Ti3Rqd4izf6Rv4Amj8HSIXyVbrTi49-2myw5ouuTaH1L1FED4J8K-JOcHz8ys1oAFSs3j1VhPUD'
      , null, $path =  '/', $domain = '.rms.rakuten.co.jp', $secure = true, $httponly = true, $encodedValue = true);
    $client->getCookieJar()->set($cookie);

    $cookie = new Cookie('ginger', '21249667961%3Adfd5e0748e776b4f47f8438f9ef4c190%3A1265066435%3A43d1c264a7ff54a497de097393306b23%3A151027104431%3Ac7ca05ae750767283756f94b13416e85'
      , null, $path =  '/', $domain = '.rms.rakuten.co.jp', $secure = true, $httponly = true, $encodedValue = true);
    $client->getCookieJar()->set($cookie);

    $cookie = new Cookie('sesame', 'MTAzYjQ5NjJhYThkYjhmMWQ0Yjc3OGNiOGUzN2RjMTgyMSxAaUOAMlrwss1lmaAiGN-hRxf0ei85Dj91MyBgoJU%7E'
      , null, $path =  '/', $domain = '.rms.rakuten.co.jp', $secure = true, $httponly = true, $encodedValue = true);
    $client->getCookieJar()->set($cookie);

    $cookie = new Cookie('shop', 'MjQyMTkwJjEmMTQ0NTkxMDI3MyYxNDQ1OTc1OTk5'
      , null, $path = '/', $domain = '.rms.rakuten.co.jp', $secure = true, $httponly = true, $encodedValue = true);
    $client->getCookieJar()->set($cookie);

    $crawler = $client->request('get', $url);

    $response = $client->getResponse();
    $request = $client->getRequest();

    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getStatus(), true));
    $logger->info(print_r($request->getUri(), true));

    $logger->info(print_r($request->getCookies(), true));

    return 1;

  }

}
