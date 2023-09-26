<?php
/**
 * バッチ処理 アリババ会社テーブル一括更新処理
 *
 * ※巡回において最初に実行。新商品巡回の前に実行する。
 *
 * 1. アリババ店舗巡回 <- ここ
 * 2. アリババ未取得商品巡回
 * 3. アリババ登録商品巡回 （在庫巡回）
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AlibabaMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\Tb1688Company;
use MiscBundle\Util\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchUpdate1688CompaniesCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $account;

  private $results;

  const RETRY_LIMIT   = 20;
  const RETRY_WAIT    = 600; // 秒
  const WAIT          = 10; // 秒

  protected function configure()
  {
    $this
      ->setName('batch:fetch-update-1688-companies')
      ->setDescription('アリババ会社テーブル一括更新処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('アリババ会社テーブル一括更新処理を開始しました。');

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
      $this->results = [
          'message' => null
      ];

      $logExecTitle = sprintf('アリババ会社テーブル一括更新処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'), false);

      /** @var AlibabaMallProcess $alibabaProcess */
      $alibabaProcess = $this->getContainer()->get('batch.mall_process.alibaba');

      // 巡回対象取得
      // tb_1688_company へ未登録の member_id
      $db = $this->getDb('main');
      $sql = <<<EOD
        SELECT
          DISTINCT p.member_id
        FROM tb_1688_product p
        LEFT JOIN tb_1688_company c ON p.member_id = c.member_id
        WHERE c.member_id IS NULL
        ORDER BY p.member_id
EOD;
      $stmt = $db->prepare($sql);
      $stmt->execute();

      /** @var StringUtil $stringUtil */
      $stringUtil = $this->getContainer()->get('misc.util.string');

      foreach($stmt as $row) {

        $memberId = $row['member_id'];
        $logger->info(sprintf('アリババ会社取得 : %s', $memberId));

        $retryCount = 0;
        RETRY_START: // retry start -----------------------------------
        try {
          // APIで会社情報を取得、登録
          $companyData = $alibabaProcess->apiGetCompany($memberId);

          sleep(self::WAIT); // APIを実行したら必ずsleep

          $company = new Tb1688Company();
          $company->setMemberId($memberId);

          // 登録がなくても空レコードを挿入
          if (!$companyData) {
            $logger->info('-- (skip: no company in alibaba) : ' . $memberId);
            $company->setCheckStop(-1);

          } else {
            $company->setCompanyName($stringUtil->ifNull($companyData->companyName, ''));
            $company->setUrl($stringUtil->ifNull($companyData->homepageUrl, ''));
            // $company->setCompanyCategoryInfo($companyData->companyCategoryInfo); // 配列か。ひとまずスルー
            $company->setCompanyNameEn($stringUtil->ifNull($companyData->companyNameEN, ''));
            $company->setProductionService($stringUtil->ifNull($companyData->productionService, ''));
            $company->setLegalStatus($stringUtil->ifNull($companyData->legalStatus, ''));
            $company->setBizPlace($stringUtil->ifNull($companyData->bizPlace, ''));
            $company->setBizModel($stringUtil->ifNull($companyData->bizModel, ''));
            $company->setProfile($stringUtil->ifNull($companyData->profile, ''));
          }

          $em = $this->getDoctrine()->getManager('main');
          $em->persist($company);

          $em->flush();

          $logger->info('ok: ' . $memberId);

        } catch (\Exception $e) {
          $logger->error($e->getMessage());

          if ($retryCount++ > self::RETRY_LIMIT) {
            $logger->error('リトライ回数の上限を超過。処理を終了します。');
            throw $e;
          }

          sleep(self::RETRY_WAIT);
          $logger->info('リトライ回数 ' . $retryCount . ' / ' . self::RETRY_LIMIT);

          goto RETRY_START; // return to retry start -----------------------------------
        }

      }

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'), false);
      $logger->logTimerFlush();

      $logger->info('アリババ会社テーブル一括更新処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('アリババ会社テーブル一括更新処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog('アリババ会社テーブル一括更新処理 エラー', 'アリババ会社テーブル一括更新処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , false // true // FOR DEBUG
        , 'アリババ会社テーブル一括更新処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;

  }
}


