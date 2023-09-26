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
use Symfony\Component\Process\Process;
use YConnect\Constant\OIDConnectDisplay;
use YConnect\Constant\OIDConnectPrompt;
use YConnect\Constant\OIDConnectScope;
use YConnect\Constant\ResponseType;
use YConnect\Credential\ClientCredential;
use YConnect\YConnectClient;


class YahooApiTestCommand extends ContainerAwareCommand
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
      ->setName('misc:yahoo-api-test')
      ->setDescription('Yahoo Api 接続試験');
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

    $fileUtil = $container->get('misc.util.file');
    $output->writeln($fileUtil->getRootDir());


    // アプリケーションID, シークレット
    $client_id     = "dj0zaiZpPXJtMGtqT3BEY0hrdyZzPWNvbnN1bWVyc2VjcmV0Jng9MmY-";
    $client_secret = "5017b7a714560e3e5648949baac3a3104018e934";

    // 各パラメータ初期化
    $redirect_uri = "https://forest.plusnao.local/app_test.php/callback/yahoo_auth";

    // リクエストとコールバック間の検証用のランダムな文字列を指定してください
    $state = "44Oq44Ki5YWF44Gr5L+644Gv44Gq44KL77yBhogehoge";
    // リプレイアタック対策のランダムな文字列を指定してください
    $nonce = "5YOV44Go5aWR57SE44GX44GmSUTljqjjgavjgarjgaPjgabjgoghogehoge=";

    $response_type = ResponseType::CODE_IDTOKEN;
    $scope = array(
      // OIDConnectScope::OPENID,
      // OIDConnectScope::PROFILE,
      // OIDConnectScope::EMAIL,
      // OIDConnectScope::ADDRESS
    );
    $display = OIDConnectDisplay::DEFAULT_DISPLAY;
    $prompt = array(
      OIDConnectPrompt::DEFAULT_PROMPT
    );

    // クレデンシャルインスタンス生成
    $cred = new ClientCredential( $client_id, $client_secret );
    // YConnectクライアントインスタンス生成
    $client = new YConnectClient( $cred );

    var_dump(get_class($client));

    // Authorizationエンドポイントにリクエスト
    $client->requestAuth(
      $redirect_uri,
      $state,
      $nonce,
      $response_type,
      $scope,
      $display,
      $prompt
    );

    var_dump(get_class_methods($client));





    $output->writeln('done!');
  }


}
