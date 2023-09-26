<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/11/05
 * Time: 15:09
 */

namespace MiscBundle\Command;

use BatchBundle\Command\WebCheckTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebCheckTestCommand extends ContainerAwareCommand
{
  use WebCheckTrait;

  const NO_DATA_LIMIT = 5;
  const RETRY_LIMIT = 20;
  const RETRY_WAIT = 3000000;
  const WAIT = 30000;

  const LOGIN_ACCOUNT_SETTING_NAME = 'web_checker';

  protected function configure()
  {
    $this
      ->setName('misc:web-check-test')
      ->setDescription('WebCheck テスト');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);
    $this->setOutput($output);

    $batchLogger = $this->getLogger();
    $batchLogger->initLogTimer();

//    // DB記録＆通知処理
//    $logExecTitle = 'WEB巡回処理（NETSEA）';
//    $batchLogger->setExecTitle($logExecTitle);
//    $batchLogger->addDbLog($batchLogger->makeDbLog($logExecTitle, $logExecTitle, '開始'));
//
//    // ファイルログ出力先
//    $this->initFileLogger('web_checker_netsea');

    $webAccessUtil = $this->getWebAccessUtil();
    $client = $webAccessUtil->getWebClient();
    $client->followRedirects(false);

    $url = 'https://detail.1688.com/offer/521245223766.html';
    $client->request('HEAD', $url);

    /** @var Response $response */
    $response = $client->getResponse();

    var_dump($response->getStatus());
    var_dump($response->getHeaders());
    var_dump($response->getContent());

    $url = 'https://detail.1688.com/offer/521245223766_HOGEHOGE.html';
    $client->request('HEAD', $url);
    /** @var Response $response */
    $response = $client->getResponse();

    var_dump($response->getStatus());
    var_dump($response->getHeaders());
    var_dump($response->getContent());

  }
}