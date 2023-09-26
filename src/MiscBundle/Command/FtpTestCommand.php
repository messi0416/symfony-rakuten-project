<?php
/**
 * Created by PhpStorm.
 * User: hirai
 * Date: 2015/09/11
 * Time: 15:09
 */

namespace MiscBundle\Command;


use Doctrine\ORM\QueryBuilder;
use forestlib\GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use MiscBundle\Entity\Repository\TbPlusnaoproductdirectoryRepository;
use MiscBundle\Entity\Repository\TbRakutenCategoryForSalesRankingRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;


class FtpTestCommand extends ContainerAwareCommand
{
  /** @var InputInterface */
  protected $input;

  /** @var OutputInterface */
  protected $output;

  /** @var BatchLogger */
  protected $logger;

  /** @var DbCommonUtil  */
  protected $commonUtil;

  protected function configure()
  {
    $this
      ->setName('misc:ftp-test')
      ->setDescription('FTPのテスト');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;

    $this->logger = $this->getContainer()->get('misc.util.batch_logger');

    $container = $this->getContainer();

    $doctrine = $container->get('doctrine');
    // var_dump(get_class($doctrine));

    $this->commonUtil = $container->get('misc.util.db_common');

    // 設定値取得
    $ftpYahoo = $container->getParameter('ftp_yahoo');
    $ftpYahooPlusnao = $ftpYahoo['plusnao'];
    $ftpYahooKawaemon = $ftpYahoo['kawaemon'];
    var_dump($ftpYahooPlusnao);
    var_dump($ftpYahooKawaemon);

    // plusnao
    /** @var \Ijanki\Bundle\FtpBundle\Ftp $ftp */
    $ftp = $container->get('ijanki_ftp');
//    var_dump(get_class($ftp));
//    var_dump(get_class_methods($ftp));

    $ftp->ssl_connect($ftpYahooPlusnao['host']);
    $ftp->login($ftpYahooPlusnao['user'], $ftpYahooPlusnao['password']);
    $result = $ftp->pasv(true);
    var_dump($result);
    $result = $ftp->chdir('/');
    var_dump($result);
    var_dump($ftp->nlist('.'));

//    $tmpFile = sprintf('/tmp/%s.txt', (new \DateTime())->format('Ymd_His'));
//    var_dump($tmpFile);
//    file_put_contents($tmpFile, "FOR yahoo plusnao FTP. 日本語。です。");
//    $ftp->put('/' . basename($tmpFile), $tmpFile, FTP_BINARY);
//
//    var_dump($ftp->nlist('/'));
//
//    unlink($tmpFile);
//    $ftp->close();
//
//    $ftp->connect($ftpYahooKawaemon['host']);
//    $ftp->login($ftpYahooKawaemon['user'], $ftpYahooKawaemon['password']);
//    $ftp->chdir('/');
//    var_dump($ftp->nlist('/'));
//    $tmpFile = sprintf('/tmp/%s.txt', (new \DateTime())->format('Ymd_His'));
//    var_dump($tmpFile);
//    file_put_contents($tmpFile, "FOR yahoo kawaemon FTP. 日本語。ですです。");
//    $ftp->put('/' . basename($tmpFile), $tmpFile, FTP_BINARY);
//    var_dump($ftp->nlist('/'));
//
//    unlink($tmpFile);

    $ftp->close();

    $output->writeln('done!');
  }

}
