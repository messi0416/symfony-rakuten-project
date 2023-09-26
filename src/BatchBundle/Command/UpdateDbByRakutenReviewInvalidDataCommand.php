<?php
namespace BatchBundle\Command;

use BatchBundle\MallProcess\RakutenMallProcess;
use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\BatchLockException;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbLog;
use MiscBundle\Util\DbCommonUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Exception\RuntimeException;

/**
 * 楽天レビュー代表商品コード補正処理。
 *
 * レビュー取り込み時に、スクレイピングエラーで代表商品が取れていない場合がある。
 * その場合のスクレイピングのリトライを行う。
 *
 * @author a-jinno
 *
 */
class UpdateDbByRakutenReviewInvalidDataCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** データが補正できない場合の、レビュー日からの削除基準日数（この間に補正できなければデータを削除） */
  const DEL_PERIOD_DAYS = 31;

  /** @var  SymfonyUsers */
  private $account;

  protected function configure()
  {
    $this
    ->setName('batch:update-db-by-rakuten-review-invalid-data')
    ->setDescription('楽天の商品レビューデータのうち、代表商品コードが取れていないデータを補正する.')
    ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'ターゲット環境', 'test')
    ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->getStopwatch();
    $this->stopwatch->start('main');

    $container = $this->getContainer();

    /** @var \MiscBundle\Util\BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info('楽天レビュー代表商品コード補正処理を開始しました。');
    $logExecTitle = '楽天レビュー代表商品コード補正処理';

    $this->processStart = new \DateTime();

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($accountId = $input->getOption('account')) {
      /** @var SymfonyUsers $account */
      $account = $container->get('doctrine')->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
      if ($account) {
        $this->account = $account;
        $logger->setAccount($account);
      }
    }

    $dbMain = $this->getDb('main');
    $dbMain->beginTransaction();
    $doctrine = $container->get('doctrine');
    $em = $doctrine->getManager('main');
    /** @var RakutenMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.rakuten');

    $targetCount = 0; // 対象データ数
    $updateCount = 0; // 更新データ数
    $logicalDeleteCount = 0; // 論理削除データ数
    $physicalDeleteCount = 0; // 物理削除データ数

    try {
      /** @var TbProductReviewsRepository $repo */
      $reviewRepo = $doctrine->getRepository('MiscBundle:TbProductReviews');
      $salesDetailRepo = $doctrine->getRepository('MiscBundle:TbSalesDetailAnalyze');
      $list = $reviewRepo->findBy(array('neMallId' => 1, 'daihyoSyohinCode' => '', 'deleteFlg' => 0)); // 楽天のNEモールIDは1固定
      $delBaseDate = new \DateTime(); // 削除基準日
      $delBaseDate->setTime(0, 0, 0);
      $delBaseDate->modify('-' . self::DEL_PERIOD_DAYS . ' days');
      $logger->debug("楽天レビュー代表商品コード補正処理　削除基準日：" . $delBaseDate->format("Y-m-d"));

      // 対象データがなければ終了
      if (empty($list)) {
        $logger->info('楽天レビュー代表商品コード補正処理 対象無し');
        return 0;
      }

      $targetCount = count($list);
      $quotedDaihyoSyohinList = array(); // 取得した代表商品コードのリスト（クォーテーション付与済み）。サマリ更新用
      foreach ($list as $review) {
        try {
          $daihyoSyohinCode = $processor->getDaihyoSyohinCodeFromProductReview($logger, $review->getOriginalReviewId());
        } catch (\RuntimeException $e) {
          // データの状態と例外メッセージを元に対応を分岐。
          // レビューページ自体なければデータを削除。レビューページはあり、商品が取れないなら論理削除
          if ($review->getReviewDatetime() > $delBaseDate) {
            // 削除対象期間になっていないのでエラーは握りつぶす
            $logger->debug("ID [" . $review->getId() . "]:" . $e->getMessage());
            continue;
          }
          if ($e->getMessage() == 'deleted review data') { // 楽天側にレビュー自体がないデータなので物理削除
            $logger->debug("ID[" . $review->getId() . "]を物理削除。" . $e->getMessage());
            $em->remove($review);
            $physicalDeleteCount++;
            continue;
          } else if (strpos($e->getMessage(), "can't get item page url") !== false) {
            $review->setDeleteFlg(true); // 論理削除
            $logger->debug("ID[" . $review->getId() . "]を論理削除");
            $logicalDeleteCount++;
            continue;
          }

        }
        $quotedDaihyoSyohinList[] = '\''. $daihyoSyohinCode . '\'';
        $review->setDaihyoSyohinCode($daihyoSyohinCode);
        // SKUが特定できれば特定
        if ($review->getVoucherNumber()) {
          $salesList = $salesDetailRepo->findBy(array('voucher_number' => $review->getVoucherNumber(), 'daihyo_syohin_code' => $review->getDaihyoSyohinCode()));
          if (count($salesList) == 1) {
            $salesData = $salesList[0];
            $review->setNeSyohinSyohinCode($salesData->getNeSyohinSyohinCode());
          }
        }
        $updateCount++;
      }

      // ここまでを反映
      $em->flush();

      // サマリを再計算
      // 今回アップデートされた商品について、サマリを更新
      if ($quotedDaihyoSyohinList) {
      $daihyoSyohinListStr = implode(',', $quotedDaihyoSyohinList);
      $sql = <<<EOD
        UPDATE tb_mainproducts_cal AS cal
        INNER JOIN (
            SELECT
                r.daihyo_syohin_code
              , COUNT(*) AS review_num
              , MAX(review_datetime) AS last_review_date
              , AVG(r.score) AS review_point_ave
            FROM tb_product_reviews r
            WHERE r.daihyo_syohin_code IN ({$daihyoSyohinListStr})
              AND r.delete_flg = 0
            GROUP BY daihyo_syohin_code
        ) AS r ON cal.daihyo_syohin_code = r.daihyo_syohin_code
        SET cal.review_num = r.review_num
          , cal.last_review_date = r.last_review_date
          , cal.review_point_ave = r.review_point_ave
EOD;
        $stmt = $dbMain->query($sql);
      }

      $dbMain->commit();
      if ($targetCount == $updateCount) {
        $logger->addDbLog($logger->makeDbLog($logExecTitle, "終了：対象 $targetCount 件 / 取込 $updateCount 件"));
      } else {
        $info = array(
            "対象" => $targetCount,
            "取込" => $updateCount,
            "物理削除" => $physicalDeleteCount,
            "論理削除" => $logicalDeleteCount,
            "未処理" => $targetCount - $updateCount - $physicalDeleteCount - $logicalDeleteCount
        );
        $logger->addDbLog($logger->makeDbLog($logExecTitle, "終了：対象 $targetCount 件 / 取込 $updateCount 件")->setInformation(json_encode($info, JSON_UNESCAPED_UNICODE)));
      }
      return 0;
    } catch (Exception $e) {
      $dbMain->rollback();

      $logger->addDbLog(
          $logger->makeDbLog($logExecTitle, $logExecTitle, 'エラー終了')->setInformation($e->getMessage())
          , true, "楽天レビュー代表商品コード補正処理でエラーが発生しました。", 'error'
          );
      return 1;
    }
  }
}