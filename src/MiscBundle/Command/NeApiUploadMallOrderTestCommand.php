<?php
/**
 * バッチ処理 NextEngineAPIテスト - 受注一括登録処理
 */

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\Repository\TbShoppingMallRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbShoppingMall;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;

class NeApiUploadMallOrderTestCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('misc:ne-api-test-upload-mall-order')
      ->setDescription('NextEngineAPIテスト - 受注一括登録処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addArgument('mall', InputArgument::REQUIRED, '対象モールコード')
      ->addArgument('path', InputArgument::REQUIRED, 'アップロードファイルPATH');
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('NextEngineAPIテスト - 受注一括登録処理を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    try {

      $commonUtil = $this->getDbCommonUtil();

      $this->results = [
          'status' => 'ok'
        , 'message' => null
        , 'mall' => null
        , 'path' => null
      ];

      $logExecTitle = sprintf('NextEngineAPIテスト - 受注一括登録処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));


      $mallCode = $input->getArgument('mall');
      $path = $input->getArgument('path');

      $this->results['mall'] = $mallCode;
      $this->results['path'] = $path;

      /** @var TbShoppingMallRepository $repo */
      $repo = $this->getContainer()->get('doctrine')->getRepository(TbShoppingMall::class);
      /** @var TbShoppingMall $mall */
      $mall = $repo->find($commonUtil->getMallIdByMallCode($mallCode));
      if (!$mall) {
        throw new \RuntimeException('モールコードが正しくありません。 [' . $mallCode . ']');
      }
      if (!$mall->getNeOrderUploadPatternId()) {
        throw new \RuntimeException('NextEngineの受注一括登録パターンIDが設定されていません。');
      }

      $file = new File($path);
      if (!$file->isFile() || !$file->isReadable()) {
        throw new \RuntimeException('invalid file : ' . $path);
      }

      /** @var NextEngineMallProcess $process */
      $process = $this->getContainer()->get('batch.mall_process.next_engine');
      $this->results['result'] = $process->apiUploadMallOrderCsv($mall, $file);

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('NextEngineAPIテスト - 受注一括登録処理を完了しました。');

    } catch (\Exception $e) {

      $this->results['status'] = 'ng';
      $this->results['message'] = $e->getMessage();

      $logger->error('NextEngineAPIテスト - 受注一括登録処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('NextEngineAPIテスト - 受注一括登録処理 エラー', 'NextEngineAPIテスト - 受注一括登録処理 エラー', 'エラー終了')->setInformation($this->results)
        , true, 'NextEngineAPIテスト - 受注一括登録処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


