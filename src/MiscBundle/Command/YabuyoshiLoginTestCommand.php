<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\Filesystem\Filesystem;


class YabuyoshiLoginTestCommand extends ContainerAwareCommand
{
  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
      ->setName('misc:yabuyoshi-login-test')
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

    // 藪吉 ログイン
    $client = $webAccessUtil->getWebClient();
    $url = 'https://web01.lisa-c.jp/yabuyoshi/logininit.html';

    $crawler = $client->request('get', $url);
    $output->writeln($crawler->html());

    $response = $client->getResponse();
    $request = $client->getRequest();

    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getStatus(), true));
    $logger->info(print_r($request->getUri(), true));

    $logger->info(print_r($request->getCookies(), true));


    // ログイン処理
    $form = $crawler->selectButton('ログイン')->form();
    $form['userId'] = 'plusnao';
    $form['userPass'] = '1622';
    $form['accountId'] = '';
    $form['url'] = '';
    $form['loginSubmit'] = 'true';

    $crawler = $client->submit($form); // Login 実行

    /** @var Response $response */
    $response = $client->getResponse();
    $request = $client->getRequest();

    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getStatus(), true));
    $logger->info(print_r($request->getUri(), true));

    $logger->info(print_r($request->getCookies(), true));

    if ($response->getStatus() !== 200 || $request->getUri() !== 'https://web01.lisa-c.jp/yabuyoshi/menuItem.html') {
      throw new \RuntimeException(sprintf('ログインに失敗しました。[%s][%s][%s]', $response->getStatus(), $request->getUri(), $response->getContent()));
    }

    // 在庫情報ダウンロード画面 遷移
    $url = 'https://web01.lisa-c.jp/yabuyoshi/Wst051.html';

    $crawler = $client->request('get', $url);

    /** @var Response $response */
    $response = $client->getResponse();
    $request = $client->getRequest();

    $logger->info(print_r($response->getHeaders(), true));
    $logger->info(print_r($response->getStatus(), true));
    $logger->info(print_r($request->getUri(), true));

    $logger->info(print_r($request->getCookies(), true));

    $output->writeln($crawler->html());


    $form = $crawler->filter('#Wst051');
    if (!$form || $form->count() === 0) {
      throw new \RuntimeException('no csv download form');
    }

    $form = $form->form();

    // キー類 捜索＆追加
    $oFwSessKey = $crawler->filter('[name=fwSessKey]');
    $oFwActId = $crawler->filter('[name=fwReqId]');
    $oFwCldId = $crawler->filter('[name=fwCldId]');

    $url = '/yabuyoshi/Wst051!download.html';
    if ($oFwSessKey->count()) {
      $url .= ';jsessionid=' . $oFwSessKey->attr('value');
    }

    // action 差し替え
    $form->getFormNode()->setAttribute('action', $url);

    $logger->info(sprintf('sess: %d', $oFwSessKey->count()));
    $logger->info(sprintf('req: %d', $oFwActId->count()));
    $logger->info(sprintf('cld: %d', $oFwCldId->count()));



    if ($oFwActId->count()) {
      $domDocument = new \DOMDocument;
      $input = $domDocument->createElement('input');
      $input->setAttribute('name', 'fwReqId');
      $input->setAttribute('value', $oFwActId->attr('value'));
      $input = new InputFormField($input);
      $form->set($input);
    }

    if ($oFwCldId->count()) {
      $domDocument = new \DOMDocument;
      $input = $domDocument->createElement('input');
      $input->setAttribute('name', 'fwCldId');
      $input->setAttribute('value', $oFwCldId->attr('value'));
      $input = new InputFormField($input);
      $form->set($input);
    }

    $form['outputType'] = '2'; // 「在庫リスト（商品順）」
    // $form['downloadFlg'] = 'true';

    foreach($form->all() as $field) {
      $logger->info(sprintf('%s : %s', $field->getName(), $field->getValue()));
    }

    $crawler = $client->submit($form); // ダウンロード実行

    /** @var Response $response */
    $response = $client->getResponse();
    $request = $client->getRequest();

    $headers = $response->getHeaders();
    $status = $response->getStatus();
    $uri = $request->getUri();

    $logger->info(print_r($headers, true));
    $logger->info(print_r($status, true));
    $logger->info(print_r($uri, true));
    $logger->info(print_r($request->getCookies(), true));

    if ($status !== 200 || $uri !== 'https://web01.lisa-c.jp/yabuyoshi/Wst051!download.html') {
      throw new \RuntimeException('ダウンロードの実行に失敗しました。');
    }

    if (!isset($headers['Content-Disposition'])) {
      throw new \RuntimeException('ダウンロード時のレスポンスではありませんでした。(Content-Disposition)', print_r($headers, true));
    }

    $fileName = null;
    foreach($headers['Content-Disposition'] as $header) {
      if (preg_match('/^attachment;filename="([^"]+)"/', $header, $match)) {
        $fileName = $match[1];
      }
    }
    if (!$fileName) {
      throw new \RuntimeException('ダウンロードのレスポンスではありませんでした。', print_r($headers, true));
    }

    $fs = new FileSystem();
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');
    $exportDir = sprintf('%s/Yabuyoshi/Import/', $fileUtil->getWebCsvDir());
    if (!$fs->exists($exportDir)) {
      $fs->mkdir($exportDir, 0755);
    }

    $exportPath = sprintf('%s/stock_%s.csv', $exportDir, (new \DateTime())->format('YmdHis'));
    $fp = new \SplFileObject($exportPath,'wb');
    $fp->fwrite($response->getContent());
    unset($fp);

    $logger->info('output csv: ' . $exportPath . ' ... done.');

    return 1;
  }

}
