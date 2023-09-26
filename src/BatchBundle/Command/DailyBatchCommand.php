<?php
/**
 * バッチ処理 共通日次バッチ処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\AlibabaMallProcess;
use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\Repository\TbDeliveryChangeShippingMethodRepository;
use MiscBundle\Entity\Repository\TbDeliveryPickingListRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbRealShopPickingListRepository;
use MiscBundle\Entity\Repository\TbWarehouseStockMovePickingListRepository;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class DailyBatchCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUsers */
  private $account;

  private $results;

  protected function configure()
  {
    $this
      ->setName('batch:daily-batch')
      ->setDescription('共通日次バッチ処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ処理を開始しました。');

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

      $logExecTitle = sprintf('共通日次バッチ処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $dbMain = $this->getDb('main');

      // NextEngine 区分値更新
      $this->updateNeKubunList();

      // バーコード作成漏れ 補完処理（現時点で、UPDATEでの編集が無い限り漏れは無いはずなのに、実際に存在。原因不明）
      $this->insertMissingBarcode();

      // 受注明細 幽霊明細削除（DeNA 同一商品複数行をまとめることで取り残される受注メール取込済明細の削除）
      // ※必ず、商品在庫再計算処理の前に行う。
      $this->deleteSummarizedSalesDetailAnalyze();

      // 実店舗ピッキングリスト 削除処理 ※必ず商品在庫再計算処理の前に行う。
      $this->clearRealShopPickingList();

      // 倉庫移動ピッキングリスト 削除処理 ※こちらは在庫数の計算に無関係
      // どうも日またぎで置いておくことがあるようなので、在庫数の計算に関係ないこともあり、一旦削除はしない。
      // $this->clearWarehouseStockMovePickingList();

      // 商品在庫 再計算処理
      $this->recalculateProductStocks();

      // 在庫履歴記録
      $this->logStockHistory();

      // 在庫定数リセット
      $this->resetZaikoTeisu();

      // 商品総在庫数 集計
      $this->updateFreeStockTotal();

      // 注残 日次履歴保存処理
      $this->logDailyOrderRemainLog();

      // 注残欠品未引当通知処理
      // 2018/06/25 不要とのことでコメントアウト
      // $this->notifyNonAssignedShortageStock();

      // 代表商品非稼働日更新処理
      $this->updateInactiveDate();

      // 日別商品別販売個数 更新
      $this->updateSalesDailyProduct();

      // 仕入先 商品数＆在庫金額更新処理
      $logger->info('共通日次バッチ: 仕入先 商品数＆在庫金額更新処理 開始');
      $dbMain->query("CALL PROC_SET_AVAILABLE_ITEMCNT_vendormasterdata");
      $logger->info('共通日次バッチ: 仕入先 商品数＆在庫金額更新処理 終了');

      // 空ロケーション削除処理 ※ロケーション修正、ピッキングなどで逐次行っていたが、デッドロックの原因となっていたため日次処理に移動。
      $this->deleteEmptyLocation();

      // NextEngine CSVアップロード状態取得処理
      $this->checkNextEngineUploadStatus();

      // ピッキングリスト定期削除
      $this->deleteOldPickingList();

      // ピッキングリスト 未完了チェック ※ピッキング引当が残っているとフリー在庫数が減る
      $this->checkUnfinishedPickingList();

      // 発送ラベル再発行伝票 定期削除
      $this->deleteOldReissueLabel();

      // 不要お問い合わせ番号 定期削除
      $this->deleteOldVoucherNoneedInquiryNumber();

      // 発送方法変更伝票 一括完了処理
      // 2018/03/14 日またぎで残しておく必要があるためコメントアウト。
      // $this->updateChangeShippingMethodListStatusDone();

      // カラバリ画像 一覧作成処理
      $this->createVariationImageList();

      // アリババ仕入れURL 不要クエリパラメータ除去
      /** @var AlibabaMallProcess $alibabaProcess */
      $alibabaProcess = $this->getContainer()->get('batch.mall_process.alibaba');
      $logger->info('共通日次バッチ: 仕入先URLクエリパラメータ除去 開始');
      $result = $alibabaProcess->trimAlibabaSireAddressQueryParameter();
      $logger->info('共通日次バッチ: 仕入先URLクエリパラメータ除去 終了 (' . $result . '件)');

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了'));
      $logger->logTimerFlush();

      $logger->info('共通日次バッチ処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('共通日次バッチ処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('共通日次バッチ処理 エラー', '共通日次バッチ処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '共通日次バッチ処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;
  }

  /**
   * 幽霊受注明細削除
   * ※モール受注CSV変換時、同一商品複数明細をまとめるために発生。
   *   受注明細取込or差分更新時に幽霊化するため、本来はそこで削除するべきだが、重いので日次バッチで実行
   */
  private function deleteSummarizedSalesDetailAnalyze()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 集約済み受注明細削除 開始');

    $dbMain = $this->getDb('main');

    // DeNA に限定していたが、Yahoo, 楽天でも同一商品複数明細受注があり、変換時にまとめる可能性もあるため、全店舗受注を対象に変更。
    // $mall = $this->getDbCommonUtil()->getShoppingMall(DbCommonUtil::MALL_ID_BIDDERS);

    $sql = <<<EOD
      DELETE a
      FROM tb_sales_detail a
      INNER JOIN (
        SELECT
             a.`伝票番号`
           , COUNT(DISTINCT a.`受注状態`) AS status_num
        FROM tb_sales_detail a
        GROUP BY a.`伝票番号`, a.`店舗名`
        HAVING status_num > 1
      ) T ON a.`伝票番号` = T.伝票番号
      WHERE a.`受注状態` = '受注メール取込済'
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $sql = <<<EOD
      DELETE a
      FROM tb_sales_detail_analyze a
      INNER JOIN (
        SELECT
             a.`伝票番号`
           , COUNT(DISTINCT a.`受注状態`) AS status_num
        FROM tb_sales_detail_analyze a
        GROUP BY a.`伝票番号`, a.`店舗名`
        HAVING status_num > 1
      ) T ON a.`伝票番号` = T.伝票番号
      WHERE a.`受注状態` = '受注メール取込済'
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // productchoiceitems 引当数・フリー在庫数更新
    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');
    $neMallProcess->updateProductchoiceitemsAssignedNum();


    $logger->info('共通日次バッチ: 集約済み受注明細削除 終了');
  }

  /**
   * 商品在庫数再計算処理
   */
  private function recalculateProductStocks()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 商品在庫数再計算処理 開始');

    $commandArgs = [
       'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
    ];

    $input = new ArgvInput($commandArgs);
    $output = new ConsoleOutput();

    $command = $this->getContainer()->get('batch.recalculate_product_stock');
    $command->run($input, $output);

    $logger->info('共通日次バッチ: 商品在庫数再計算処理 終了');

  }


  /**
   * 在庫履歴記録処理
   */
  private function logStockHistory()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 在庫履歴記録処理 開始');

    $dbMain = $this->getDb('main');
    
    $season = 's'.date('n');

    // '在庫履歴を作成
    $sql = <<<EOD
      INSERT
      INTO tb_stock_history(
          現在庫数
        , フリー在庫数
        , 現在庫金額
        , フリー在庫金額
        , 販売不可在庫数
        , 販売不可在庫金額
        , 移動中在庫数
        , 移動中在庫金額
        , 季節内在庫数
        , 季節内在庫金額
        , 季節外在庫数
        , 季節外在庫金額
        , company_code
      )
      SELECT
          SUM(pci.在庫数 - coalesce(not_asset.stock, 0)) AS 現在庫数
        , SUM(CASE 
            WHEN pci.フリー在庫数 - coalesce(not_asset.stock, 0) < -1 THEN 0
            ELSE pci.フリー在庫数 - coalesce(not_asset.stock, 0)
          END) AS フリー在庫数
        , SUM((pci.在庫数 - coalesce(not_asset.stock, 0)) * p.baika_genka) AS 現在庫金額
        , SUM(
            (CASE 
              WHEN pci.フリー在庫数 - coalesce(not_asset.stock, 0) < -1 THEN 0
              ELSE pci.フリー在庫数 - coalesce(not_asset.stock, 0)
            END) * p.baika_genka) AS フリー在庫金額
        , SUM(pci.販売不可在庫数) AS 販売不可在庫数
        , SUM(pci.販売不可在庫数 * p.baika_genka) AS 販売不可在庫金額
        , SUM(pci.移動中在庫数) AS 移動中在庫数
        , SUM(pci.移動中在庫数 * p.baika_genka) AS 移動中在庫金額
        , SUM(CASE WHEN ps.$season <> 0 THEN pci.在庫数 ELSE 0 END) AS 季節内在庫数
        , SUM(CASE WHEN ps.$season <> 0 THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS 季節内在庫金額
        , SUM(CASE WHEN ps.$season = 0 THEN pci.在庫数 ELSE 0 END) AS 季節外在庫数
        , SUM(CASE WHEN ps.$season = 0 THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS 季節外在庫金額
        , m.company_code
      FROM tb_productchoiceitems AS pci
      INNER JOIN tb_mainproducts AS m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN v_product_price AS p ON pci.daihyo_syohin_code = p.daihyo_syohin_code
      LEFT JOIN tb_product_season AS ps ON  pci.daihyo_syohin_code = ps.daihyo_syohin_code
      -- 資産に計上しない在庫数。在庫数からこのぶんを減算する
      LEFT JOIN (
        SELECT
            pl.ne_syohin_syohin_code
          , SUM(pl.stock) AS stock
        FROM
          tb_product_location pl 
          JOIN tb_productchoiceitems pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
          JOIN tb_location l ON pl.location_id = l.id
          JOIN tb_warehouse w ON l.warehouse_id = w.id
        WHERE
            pl.position >= 0
            AND w.asset_flg = 0
        GROUP BY pl.ne_syohin_syohin_code
      ) not_asset ON pci.ne_syohin_syohin_code = not_asset.ne_syohin_syohin_code
      GROUP BY m.company_code
EOD;

    $dbMain->query($sql);

    // 最大商品番号取得
    $sql = <<<EOD
      SELECT MAX(ID) AS ID FROM tb_stock_history
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    $historyId = intval($stmt->fetchColumn(0));
    if (!$historyId) {
      $historyId = 0;
    }

    // '在庫履歴を作成
    $sql = <<<EOD
      INSERT
      INTO tb_stock_history_ioh(
          history_id
         ,発注済在庫数
         ,発注済在庫金額
         ,入荷済在庫数
         ,入荷済在庫金額
         ,出荷待在庫数
         ,出荷待在庫金額
         ,出荷済在庫数
         ,出荷済在庫金額
        , company_code
      )
      SELECT 
          sh.id
        , SUM(CASE WHEN ioh.remain_status = 'ORDERED' THEN ioh.注残計 ELSE 0 END) AS 発注済在庫数
        , SUM(CASE WHEN ioh.remain_status = 'ORDERED' THEN ioh.注残計 * m.genka_tnk ELSE 0 END) AS 発注済在庫金額
        , SUM(CASE WHEN ioh.remain_status = 'ARRIVED' THEN ioh.注残計 ELSE 0 END) AS 入荷済在庫数
        , SUM(CASE WHEN ioh.remain_status = 'ARRIVED' THEN ioh.注残計 * m.genka_tnk ELSE 0 END) AS 入荷済在庫金額
        , SUM(CASE WHEN ioh.remain_status = 'WAITED' THEN ioh.注残計 ELSE 0 END) AS 出荷待在庫数
        , SUM(CASE WHEN ioh.remain_status = 'WAITED' THEN ioh.注残計 * m.genka_tnk ELSE 0 END) AS 出荷待在庫金額
        , SUM(CASE WHEN ioh.remain_status = 'SHIPPED' THEN ioh.注残計 ELSE 0 END) AS 出荷済在庫数
        , SUM(CASE WHEN ioh.remain_status = 'SHIPPED' THEN ioh.注残計 * m.genka_tnk ELSE 0 END) AS 出荷済在庫金額
        , m.company_code
      FROM tb_individualorderhistory as ioh
      INNER JOIN tb_productchoiceitems AS pci ON ioh.商品コード = pci.ne_syohin_syohin_code
      INNER JOIN tb_mainproducts AS m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN v_product_price AS p ON pci.daihyo_syohin_code = p.daihyo_syohin_code
      INNER JOIN (
        SELECT MAX(ID) AS id,company_code FROM tb_stock_history GROUP BY company_code
      ) sh ON m.company_code = sh.company_code
      GROUP BY m.company_code
EOD;

    $dbMain->query($sql);

    // '在庫履歴を作成
    $sql = <<<EOD
      INSERT
      INTO tb_stock_history_location(
          history_id
--          ,南京終在庫数 /廃止済み
--          ,南京終在庫金額 /廃止済み
--          ,舞台在庫数 /廃止済み
--          ,舞台在庫金額 /廃止済み
         ,詰替前在庫数
         ,詰替前在庫金額
         ,FBA在庫数
         ,FBA在庫金額
         ,藪吉出荷在庫数
         ,藪吉出荷在庫金額
         ,藪吉ストック在庫数
         ,藪吉ストック在庫金額
         ,RSL在庫数
         ,RSL在庫金額
         ,SHOPLIST在庫数
         ,SHOPLIST在庫金額
         ,古市在庫数
         ,古市在庫金額
         ,詰替古市在庫数
         ,詰替古市在庫金額
         ,舞台2在庫数 -- 現南京終
         ,舞台2在庫金額 -- 現南京終
         ,白毫寺在庫数
         ,白毫寺在庫金額
         ,布目在庫数
         ,布目在庫金額
         ,山田川在庫数
         ,山田川在庫金額
         ,旧ムカイ在庫数
         ,旧ムカイ在庫金額
         ,帯解在庫数
         ,帯解在庫金額
        , company_code
      )
      SELECT 
          sh.id
--        , SUM(CASE WHEN wh.id = 1 THEN pl.stock ELSE 0 END) AS 南京終在庫数 / 廃止済み
--        , SUM(CASE WHEN wh.id = 1 THEN pl.stock * p.baika_genka ELSE 0 END) AS 南京終在庫金額 / 廃止済み
--        , SUM(CASE WHEN wh.id = 4 THEN pl.stock ELSE 0 END) AS 舞台在庫数 / 廃止済み
--        , SUM(CASE WHEN wh.id = 4 THEN pl.stock * p.baika_genka ELSE 0 END) AS 舞台在庫金額 / 廃止済み
        , SUM(CASE WHEN wh.id = 5 THEN pl.stock ELSE 0 END) AS 詰替前在庫数
        , SUM(CASE WHEN wh.id = 5 THEN pl.stock * p.baika_genka ELSE 0 END) AS 詰替前在庫金額
        , SUM(CASE WHEN wh.id = 6 THEN pl.stock ELSE 0 END) AS FBA在庫数
        , SUM(CASE WHEN wh.id = 6 THEN pl.stock * p.baika_genka ELSE 0 END) AS FBA在庫金額
        , SUM(CASE WHEN wh.id = 7 THEN pl.stock ELSE 0 END) AS 藪吉出荷在庫数
        , SUM(CASE WHEN wh.id = 7 THEN pl.stock * p.baika_genka ELSE 0 END) AS 藪吉出荷在庫金額
        , SUM(CASE WHEN wh.id = 8 THEN pl.stock ELSE 0 END) AS 藪吉ストック在庫数
        , SUM(CASE WHEN wh.id = 8 THEN pl.stock * p.baika_genka ELSE 0 END) AS 藪吉ストック在庫金額
        , SUM(CASE WHEN wh.id = 10 THEN pl.stock ELSE 0 END) AS RSL在庫数
        , SUM(CASE WHEN wh.id = 10 THEN pl.stock * p.baika_genka ELSE 0 END) AS RSL在庫金額
        , SUM(CASE WHEN wh.id = 11 THEN pl.stock ELSE 0 END) AS SHOPLIST在庫数
        , SUM(CASE WHEN wh.id = 11 THEN pl.stock * p.baika_genka ELSE 0 END) AS SHOPLIST在庫金額
        , SUM(CASE WHEN wh.id = 12 THEN pl.stock ELSE 0 END) AS 古市在庫数
        , SUM(CASE WHEN wh.id = 12 THEN pl.stock * p.baika_genka ELSE 0 END) AS 古市在庫金額
        , SUM(CASE WHEN wh.id = 13 THEN pl.stock ELSE 0 END) AS 詰替古市在庫数
        , SUM(CASE WHEN wh.id = 13 THEN pl.stock * p.baika_genka ELSE 0 END) AS 詰替古市在庫金額
        , SUM(CASE WHEN wh.id = 14 THEN pl.stock ELSE 0 END) AS 舞台2在庫数
        , SUM(CASE WHEN wh.id = 14 THEN pl.stock * p.baika_genka ELSE 0 END) AS 舞台2在庫金額
        , SUM(CASE WHEN wh.id = 15 THEN pl.stock ELSE 0 END) AS 白毫寺在庫数
        , SUM(CASE WHEN wh.id = 15 THEN pl.stock * p.baika_genka ELSE 0 END) AS 白毫寺在庫金額
        , SUM(CASE WHEN wh.id = 16 THEN pl.stock ELSE 0 END) AS 布目在庫数
        , SUM(CASE WHEN wh.id = 16 THEN pl.stock * p.baika_genka ELSE 0 END) AS 布目在庫金額
        , SUM(CASE WHEN wh.id = 17 THEN pl.stock ELSE 0 END) AS 山田川在庫数
        , SUM(CASE WHEN wh.id = 17 THEN pl.stock * p.baika_genka ELSE 0 END) AS 山田川在庫金額
        , SUM(CASE WHEN wh.id = 18 THEN pl.stock ELSE 0 END) AS 旧ムカイ在庫数
        , SUM(CASE WHEN wh.id = 18 THEN pl.stock * p.baika_genka ELSE 0 END) AS 旧ムカイ在庫金額
        , SUM(CASE WHEN wh.id = 21 THEN pl.stock ELSE 0 END) AS 帯解在庫数
        , SUM(CASE WHEN wh.id = 21 THEN pl.stock * p.baika_genka ELSE 0 END) AS 帯解在庫金額
        , m.company_code
      FROM tb_warehouse AS wh
      INNER JOIN tb_location AS l ON wh.id = l.warehouse_id
      INNER JOIN tb_product_location AS pl ON pl.location_id = l.id
      INNER JOIN tb_productchoiceitems AS pci ON pl.ne_syohin_syohin_code = pci.ne_syohin_syohin_code
      INNER JOIN tb_mainproducts AS m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN v_product_price AS p ON pci.daihyo_syohin_code = p.daihyo_syohin_code
      INNER JOIN (
        SELECT MAX(ID) AS id,company_code FROM tb_stock_history GROUP BY company_code
      ) sh ON m.company_code = sh.company_code
      GROUP BY m.company_code
EOD;

    $dbMain->query($sql);
    
    $date1 = (new \DateTime())->modify(sprintf('-%d months', 3))->format('Y-m-d');
    $date2 = (new \DateTime())->modify(sprintf('-%d months', 6))->format('Y-m-d');
    $date3 = (new \DateTime())->modify(sprintf('-%d year', 1))->format('Y-m-d');
    $date4 = (new \DateTime())->modify(sprintf('-%d year', 2))->format('Y-m-d');
    $date5 = (new \DateTime())->modify(sprintf('-%d year', 3))->format('Y-m-d');
    $date6 = (new \DateTime())->modify(sprintf('-%d year', 4))->format('Y-m-d');
    $date7 = (new \DateTime())->modify(sprintf('-%d year', 5))->format('Y-m-d');
    $date8 = (new \DateTime())->modify(sprintf('-%d year', 6))->format('Y-m-d');
    $date9 = (new \DateTime())->modify(sprintf('-%d year', 7))->format('Y-m-d');
    $date10 = (new \DateTime())->modify(sprintf('-%d year', 8))->format('Y-m-d');
    
    // '仕入れ日基準在庫を作成
    $sql = <<<EOD
      INSERT
      INTO tb_stock_history_order(
          history_id
         ,３ヶ月以内在庫数
         ,３ヶ月以内在庫金額
         ,６ヶ月以内在庫数
         ,６ヶ月以内在庫金額
         ,１年以内在庫数
         ,１年以内在庫金額
         ,２年以内在庫数
         ,２年以内在庫金額
         ,３年以内在庫数
         ,３年以内在庫金額
         ,４年以内在庫数
         ,４年以内在庫金額
         ,５年以内在庫数
         ,５年以内在庫金額
         ,６年以内在庫数
         ,６年以内在庫金額
         ,７年以内在庫数
         ,７年以内在庫金額
         ,８年以内在庫数
         ,８年以内在庫金額
        , company_code
      )
      SELECT 
          sh.id
		, SUM(CASE WHEN cal.last_orderdate >= '$date1' THEN pci.在庫数 ELSE 0 END) AS ３ヶ月以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date1' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS ３ヶ月以内在庫金額
        , SUM(CASE WHEN cal.last_orderdate >= '$date2' AND cal.last_orderdate < '$date1' THEN pci.在庫数 ELSE 0 END) AS ６ヶ月以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date2' AND cal.last_orderdate < '$date1' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS ６ヶ月以内在庫金額
        , SUM(CASE WHEN cal.last_orderdate >= '$date3' AND cal.last_orderdate < '$date2' THEN pci.在庫数 ELSE 0 END) AS １年以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date3' AND cal.last_orderdate < '$date2' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS １年以内在庫金額
        , SUM(CASE WHEN cal.last_orderdate >= '$date4' AND cal.last_orderdate < '$date3' THEN pci.在庫数 ELSE 0 END) AS ２年以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date4' AND cal.last_orderdate < '$date3' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS ２年以内在庫金額
        , SUM(CASE WHEN cal.last_orderdate >= '$date5' AND cal.last_orderdate < '$date4' THEN pci.在庫数 ELSE 0 END) AS ３年以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date5' AND cal.last_orderdate < '$date4' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS ３年以内在庫金額
        , SUM(CASE WHEN cal.last_orderdate >= '$date6' AND cal.last_orderdate < '$date5' THEN pci.在庫数 ELSE 0 END) AS ４年以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date6' AND cal.last_orderdate < '$date5' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS ４年以内在庫金額
        , SUM(CASE WHEN cal.last_orderdate >= '$date7' AND cal.last_orderdate < '$date6' THEN pci.在庫数 ELSE 0 END) AS ５年以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date7' AND cal.last_orderdate < '$date6' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS ５年以内在庫金額
        , SUM(CASE WHEN cal.last_orderdate >= '$date8' AND cal.last_orderdate < '$date7' THEN pci.在庫数 ELSE 0 END) AS ６年以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date8' AND cal.last_orderdate < '$date7' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS ６年以内在庫金額
        , SUM(CASE WHEN cal.last_orderdate >= '$date9' AND cal.last_orderdate < '$date8' THEN pci.在庫数 ELSE 0 END) AS ７年以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date9' AND cal.last_orderdate < '$date8' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS ７年以内在庫金額
        , SUM(CASE WHEN cal.last_orderdate >= '$date10' AND cal.last_orderdate < '$date9' THEN pci.在庫数 ELSE 0 END) AS ８年以内在庫数
        , SUM(CASE WHEN cal.last_orderdate >= '$date10' AND cal.last_orderdate < '$date9' THEN pci.在庫数 * p.baika_genka ELSE 0 END) AS ８年以内在庫金額
        , m.company_code
      FROM tb_productchoiceitems AS pci
      INNER JOIN tb_mainproducts_cal AS cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_mainproducts AS m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN v_product_price AS p ON pci.daihyo_syohin_code = p.daihyo_syohin_code
      INNER JOIN (
        SELECT MAX(ID) AS id,company_code FROM tb_stock_history GROUP BY company_code
      ) sh ON m.company_code = sh.company_code
      GROUP BY m.company_code
EOD;

    $dbMain->query($sql);

    // DB記録＆通知処理
    $logger->addDbLog($logger->makeDbLog(null, '在庫履歴記録処理', '終了'));
    $logger->info('共通日次バッチ: 在庫履歴記録処理 終了');
  }

  /**
   * 在庫定数リセット処理
   * ・在庫定数リセット日を迎えた商品は、在庫定数を0とする。
   * ・在庫定数が入っているのにリセット日が設定されていないもの（新規に在庫定数を設定したもの）は4ヶ月先のリセット日を設定する。
   * 　※長く在庫定数を入れたい場合には、はるか未来の日付を設定する運用とする。
   */
  private function resetZaikoTeisu()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 在庫定数リセット処理 開始');

    $dbMain = $this->getDb('main');

    // 在庫定数リセット処理
    $sql = <<<EOD
      UPDATE
      tb_productchoiceitems pci
      INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      SET pci.zaiko_teisu = 0
        , cal.zaiko_teisu_reset_date = NULL
      WHERE cal.zaiko_teisu_reset_date <= CURRENT_DATE
EOD;
    $dbMain->query($sql);

    // 在庫定数があるのにリセット日が未設定の商品は、1ヶ月先をリセット日とする。
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal cal
      INNER JOIN (
        SELECT
          DISTINCT pci.daihyo_syohin_code
        FROM tb_productchoiceitems pci
        WHERE pci.zaiko_teisu <> 0
      ) T ON cal.daihyo_syohin_code = T.daihyo_syohin_code
      SET cal.zaiko_teisu_reset_date = DATE_ADD(CURRENT_DATE, INTERVAL +4 MONTH)
      WHERE cal.zaiko_teisu_reset_date IS NULL
EOD;
    $dbMain->query($sql);

    // DB記録＆通知処理
    $logger->addDbLog($logger->makeDbLog(null, '在庫定数リセット処理', '終了'));
    $logger->info('共通日次バッチ: 在庫定数リセット処理 終了');
  }

  /**
   * 商品マスタ「総在庫数」「総在庫価格」更新
   */
  private function updateFreeStockTotal()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 総在庫数・総在庫金額の再設定 開始');

    $dbMain = $this->getDb('main');

    // 総在庫数の再設定
    $sql = <<<EOD
      UPDATE tb_mainproducts m
      INNER JOIN v_product_price AS p ON m.daihyo_syohin_code = p.daihyo_syohin_code
      LEFT JOIN (
          SELECT
              pci.daihyo_syohin_code
            , SUM(pci.フリー在庫数) AS total
          FROM tb_productchoiceitems pci
          WHERE pci.フリー在庫数 > 0
          GROUP BY pci.daihyo_syohin_code
      ) AS T ON m.daihyo_syohin_code = T.daihyo_syohin_code
      SET m.総在庫数   = COALESCE(T.total, 0)
        , m.総在庫金額 = COALESCE(T.total, 0) * p.cost_tanka
EOD;
    $dbMain->query($sql);

    $logger->info('共通日次バッチ: 総在庫数・総在庫金額の再設定 終了');
  }

  /**
   * tb_mainproducts_cal「非稼働日」 更新
   */
  private function updateInactiveDate()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 代表商品非稼働日更新 開始');

    $dbMain = $this->getDb('main');

    $sql = <<<EOD
      /* tb_productchoiceitemsに存在しない代表商品も非稼働としたいので、
         外部結合にしてサブクエリ内では、WHERE句を使わない */
      UPDATE
        tb_mainproducts_cal cal
        LEFT JOIN (
          /* 紐づくSKUの状態から、代表商品として稼働中か否かを判定 */
          SELECT
            pci.daihyo_syohin_code,
            MAX(CASE
              WHEN pci.zaiko_teisu <> 0 THEN 1
              WHEN pci.在庫数 <> 0 THEN 1
              WHEN pci.発注残数 <> 0 THEN 1
              ELSE 0
            END) AS active_flg
          FROM
            tb_productchoiceitems pci
          GROUP BY
            pci.daihyo_syohin_code
        ) A
          ON cal.daihyo_syohin_code = A.daihyo_syohin_code
      SET
        cal.inactive_date = CASE WHEN A.active_flg THEN NULL ELSE :today END
      WHERE
        /* 稼働状態に変化のある商品のみを更新の対象にする */
        (cal.inactive_date IS NOT NULL AND A.active_flg)
        OR (cal.inactive_date IS NULL AND (A.active_flg = 0 OR A.active_flg IS NULL));
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':today', (new \DateTime())->format('Y-m-d') , \PDO::PARAM_STR);
    $stmt->execute();

    $sql = <<<EOD
      /* セット商品の時は、更に、紐づくセットSKUで稼働しているものが1件でも有れば稼働中とする */
      /* セットSKUは、全構成品を、各在庫数、在庫定数、発注残数から作成可能な状態であれば、稼働していると考える。 */
      UPDATE
        tb_mainproducts_cal cal
        INNER JOIN (
          /* セット代表商品として、稼働中かどうか */
          SELECT
            cal2.daihyo_syohin_code,
            CASE WHEN MAX(T.active_flg) <> 0 THEN 1 ELSE 0 END AS active_flg
          FROM
            tb_mainproducts_cal cal2
            INNER JOIN (
              /* セットSKU毎に、稼働しているか判定 */
              SELECT
                pci.daihyo_syohin_code,
                pci.ne_syohin_syohin_code AS set_sku,
                MIN(CASE
                      WHEN (pci_detail.`在庫数` + pci_detail.zaiko_teisu + pci_detail.`発注残数`) >= d.num THEN 1
                      ELSE 0
                    END) AS active_flg
              FROM
                tb_productchoiceitems pci
                INNER JOIN tb_mainproducts m
                  ON pci.daihyo_syohin_code = m.daihyo_syohin_code
                LEFT JOIN tb_set_product_detail d
                  ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
                LEFT JOIN tb_productchoiceitems pci_detail
                  ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
              WHERE
                m.set_flg <> 0
              GROUP BY
                pci.daihyo_syohin_code, set_sku
            ) T
              ON cal2.daihyo_syohin_code = T.daihyo_syohin_code
          GROUP BY
            cal2.daihyo_syohin_code
        ) A
          ON cal.daihyo_syohin_code = A.daihyo_syohin_code
      SET
        cal.inactive_date = CASE WHEN A.active_flg THEN NULL ELSE :today END
      WHERE
        /* 稼働状態に変化のある商品のみを更新の対象にする */
        (cal.inactive_date IS NOT NULL AND A.active_flg)
        OR (cal.inactive_date IS NULL AND A.active_flg = 0);
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':today', (new \DateTime())->format('Y-m-d') , \PDO::PARAM_STR);
    $stmt->execute();

    $logger->info('共通日次バッチ: 代表商品非稼働日更新 終了');
  }

  /**
   * 日別商品別販売個数テーブル 更新
   */
  private function updateSalesDailyProduct()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 日別商品別販売個数テーブル更新 開始');

    $dbMain = $this->getDb('main');

    $sql = <<<EOD
      INSERT INTO tb_sales_daily_product
      SELECT
          a.`受注日`
        , a.daihyo_syohin_code
        , SUM(a.`受注数`) AS 受注数
        , COUNT(*) AS 明細数
        , COUNT(DISTINCT a.`伝票番号`) AS 伝票数
        , SUM(a.`小計`) AS 明細金額
      FROM tb_sales_detail_analyze a
      WHERE a.`キャンセル区分` = '0'
        AND a.`明細行キャンセル` = '0'
      GROUP BY a.`受注日`, a.daihyo_syohin_code
      ORDER BY a.受注日, a.daihyo_syohin_code
      ON DUPLICATE KEY UPDATE
            受注数 = VALUES(受注数)
          , 明細数 = VALUES(明細数)
          , 伝票数 = VALUES(伝票数)
          , 明細金額 = VALUES(明細金額)
EOD;
    $dbMain->query($sql);

    $logger->info('共通日次バッチ: 日別商品別販売個数テーブル更新 終了');
  }


  /**
   * 空ロケーション削除処理
   */
  private function deleteEmptyLocation()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 空ロケーション削除処理 開始');

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');

    /** @var TbLocationRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

    $dbMain->beginTransaction();

    // （履歴用）アクションキー 作成＆セット
    $actionKey = $repo->setLocationLogActionKey($dbMain);

    // 空になったロケーションを削除
    $repo->deleteEmptyLocation();

    // ロケーション変更履歴 保存
    /** @var \Doctrine\DBAL\Connection $dbLog */
    $dbLog = $this->getDoctrine()->getConnection('log');
    $repo->saveLocationChangeLogSummary($dbMain, $dbLog, TbLocationRepository::LOG_OPERATION_DELETE_EMPTY_LOCATION,  ($this->account ? $this->account->getUsername() : 'BatchSV02:CRON'), $actionKey);

    $dbMain->commit();

    $logger->info('共通日次バッチ: 空ロケーション削除処理 終了');
  }

  /**
   * NextEngine CSVアップロード状態取得処理
   */
  private function checkNextEngineUploadStatus()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: NextEngine CSVアップロード状態取得処理 開始');

    // NextEngine CSVアップロード状態取得処理 実行
    $commandArgs = [
        'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
      , sprintf('--target-env=%s', $this->getEnvironment())
    ];

    $input = new ArgvInput($commandArgs);
    $output = new ConsoleOutput();

    $command = $this->getContainer()->get('batch.update_ne_upload_status');
    $command->run($input, $output);

    $logger->info('共通日次バッチ: NextEngine CSVアップロード状態取得処理 終了');
  }

  /**
   * ピッキングリスト 未完了チェック
   * 未完了のピッキングリストがあればチケットを作成する。
   */
  private function checkUnfinishedPickingList()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: ピッキングリスト 未完了チェック 開始');

    /** @var TbDeliveryPickingListRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryPickingList');
    $result = $repo->getUnfinishedPickingList();

    // もし存在すればチケット作成
    if ($result && $this->getContainer()->getParameter('redmine_picking_ng_ticket')) {

      /** @var WebAccessUtil $webAccessUtil */
      $webAccessUtil = $this->getContainer()->get('misc.util.web_access');

      $now = new \DateTime();
      $yesterday = $now->modify('-1 day');

      $title = sprintf('[日次バッチ][未完了ピッキング][%s] %d 件', $yesterday->format('Y-m-d'), count($result));

      $body = '';
      foreach($result as $picking) {
        $body .= <<<EOD
|日付       |{$picking->getDate()->format('Y/m/d')}|
|No.        |{$picking->getNumber()}|
|ピッキング担当者|{$picking->getPickingAccountName()}|
|商品コード  |{$picking->getSyohinCode()}|
|ピッキング数|{$picking->getItemNum()}|

----

EOD;
      }

      $ticket = [
        'issue' => [
            'subject'         => $title
          , 'project_id'      => $this->getContainer()->getParameter('redmine_picking_ng_ticket_project')
          , 'priority_id'     => $this->getContainer()->getParameter('redmine_picking_ng_ticket_priority')
          , 'description'     => $body
          , 'assigned_to_id'  => $this->getContainer()->getParameter('redmine_picking_ng_ticket_user')
          , 'tracker_id'      => $this->getContainer()->getParameter('redmine_picking_ng_ticket_tracker')
          // , 'category_id'     => ''
          // , 'status_id'       => ''
        ]
      ];

      $ret = $webAccessUtil->requestRedmineApi('POST', '/issues.json', $ticket);
      $logger->info('redmine create ticket:' . $ret);
    }

    $logger->info('共通日次バッチ: ピッキングリスト 未完了チェック 終了');
  }


  /**
   * ピッキングリスト 削除
   */
  private function deleteOldPickingList()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: ピッキングリスト定期削除処理 開始');

    try {
      $dbMain = $this->getDb('main');

      // 1ヶ月で削除
      $sql = <<<EOD
        DELETE p
        FROM tb_delivery_picking_list p
        WHERE p.`date` < DATE_ADD(CURRENT_DATE, INTERVAL -30 DAY)
EOD;
      $dbMain->exec($sql);

      $logger->info('共通日次バッチ: ピッキングリスト定期削除処理 終了');

    } catch (\Exception $e) {
      $logger->error('共通日次バッチ処理 ピッキングリスト定期削除処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('共通日次バッチ処理 ピッキングリスト定期削除処理 エラー', '共通日次バッチ処理 ピッキングリスト定期削除処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '共通日次バッチ処理 ピッキングリスト定期削除処理 でエラーが発生しました。', 'error'
      );

      // エラーは記録だけして次の処理へ。
    }
  }

  /**
   * 発送ラベル再発行伝票 削除
   */
  private function deleteOldReissueLabel()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 発送ラベル再発行伝票定期削除処理 開始');

    try {
      $dbMain = $this->getDb('main');

      // 1週間で削除
      $pickingListDate = (new \DateTime())->modify('-7 day')->format('Y-m-d');

      // DELETEでJOINすると、重くなる恐れがあるので先に削除対象を取得。
      $sql = <<<EOD
        SELECT
          r.id
        FROM
          tb_shipping_reissue_label r
        INNER JOIN
          tb_shipping_voucher_packing p
          ON r.shipping_voucher_packing_id = p.id
        INNER JOIN
          tb_shipping_voucher v
          ON p.voucher_id = v.id
        WHERE
          v.picking_list_date < :pickingListDate;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':pickingListDate', $pickingListDate);
      $stmt->execute();
      $deleteIdList = $stmt->fetchAll(\PDO::FETCH_COLUMN);

      // 削除
      if (! empty($deleteIdList)) {
        $deleteIdListStr = implode(', ', $deleteIdList);
        $sql = <<<EOD
          DELETE
          FROM
            tb_shipping_reissue_label
          WHERE
            id IN ({$deleteIdListStr});
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->execute();
      }

      $logger->info('共通日次バッチ: 発送ラベル再発行伝票定期削除処理 終了');

    } catch (\Exception $e) {
      $logger->error('共通日次バッチ処理 発送ラベル再発行伝票定期削除処理 エラー:' . $e->getMessage() . $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog('共通日次バッチ処理 発送ラベル再発行伝票定期削除処理 エラー', '共通日次バッチ処理 発送ラベル再発行伝票定期削除処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '共通日次バッチ処理 発送ラベル再発行伝票定期削除処理 でエラーが発生しました。', 'error'
      );

      // エラーは記録だけして次の処理へ。
    }
  }

  /**
   * 不要お問い合わせ番号 削除
   */
  private function deleteOldVoucherNoneedInquiryNumber()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 不要お問い合わせ番号定期削除処理 開始');

    try {
      $dbMain = $this->getDb('main');

      // 1週間で削除
      $pickingListDate = (new \DateTime())->modify('-7 day')->format('Y-m-d');

      // DELETEでJOINすると、重くなる恐れがあるので先に削除対象を取得。
      $sql = <<<EOD
        SELECT
          n.id
        FROM
          tb_shipping_voucher_noneed_inquiry_number n
        INNER JOIN
          tb_shipping_voucher_packing p
          ON n.shipping_voucher_packing_id = p.id
        INNER JOIN
          tb_shipping_voucher v
          ON p.voucher_id = v.id
        WHERE
          v.picking_list_date < :pickingListDate;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':pickingListDate', $pickingListDate);
      $stmt->execute();
      $deleteIdList = $stmt->fetchAll(\PDO::FETCH_COLUMN);

      // 削除
      if (! empty($deleteIdList)) {
        $deleteIdListStr = implode(', ', $deleteIdList);
        $sql = <<<EOD
          DELETE
          FROM
            tb_shipping_voucher_noneed_inquiry_number
          WHERE
            id IN ({$deleteIdListStr});
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->execute();
      }

      $logger->info('共通日次バッチ: 不要お問い合わせ番号定期削除処理 終了');

    } catch (\Exception $e) {
      $logger->error('共通日次バッチ処理 不要お問い合わせ番号定期削除処理 エラー:' . $e->getMessage() . $e->getTraceAsString());
      $logger->addDbLog(
        $logger->makeDbLog('共通日次バッチ処理 不要お問い合わせ番号定期削除処理 エラー', '共通日次バッチ処理 不要お問い合わせ番号定期削除処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '共通日次バッチ処理 不要お問い合わせ番号定期削除処理 でエラーが発生しました。', 'error'
      );

      // エラーは記録だけして次の処理へ。
    }
  }

  /**
   * 発送方法変更伝票 一括完了処理
   * ※現在不使用。いらない
   */
  private function updateChangeShippingMethodListStatusDone()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 発送方法変更伝票 一括完了処理 開始');

    try {
      $dbMain = $this->getDb('main');

      $sql = <<<EOD
        UPDATE
        tb_delivery_change_shipping_method c
        SET c.status = :statusDone
        WHERE c.`date` < CURRENT_DATE
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':statusDone', TbDeliveryChangeShippingMethodRepository::STATUS_DONE, \PDO::PARAM_INT);
      $stmt->execute();

      $logger->info('共通日次バッチ: 発送方法変更伝票 一括完了処理 終了');

    } catch (\Exception $e) {
      $logger->error('共通日次バッチ処理 発送方法変更伝票 一括完了処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('共通日次バッチ処理 発送方法変更伝票 一括完了処理 エラー', '共通日次バッチ処理 発送方法変更伝票 一括完了処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '共通日次バッチ処理 発送方法変更伝票 一括完了処理 でエラーが発生しました。', 'error'
      );

      // エラーは記録だけして次の処理へ。
    }
  }



  /**
   * 注残履歴保存処理
   */
  private function logDailyOrderRemainLog()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 注残履歴保存処理 開始');

    $dbMain = $this->getDb('main');
    $sql = <<<EOD
      INSERT IGNORE INTO tb_order_remain_log (
          log_date
        , agent_code
        , remain_status
        , row_num
        , sku_num
        , remain_order_num
        , cost
      )
      SELECT
          CURRENT_DATE     AS log_date
        , o.`依頼先cd`     AS agent_code
        , o.remain_status                           AS remain_status
        , COUNT(*)                                  AS row_num
        , COUNT(DISTINCT o.`商品コード`)             AS sku_num
        , SUM(o.`注残計`)                            AS remain_order_num
        , SUM(COALESCE(m.genka_tnk, 0) * o.`注残計`) AS cost
      FROM tb_individualorderhistory o
      LEFT JOIN tb_productchoiceitems pci ON o.`商品コード` = pci.ne_syohin_syohin_code
      LEFT JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
      WHERE o.`注残計` > 0
      GROUP BY o.`依頼先cd`
             , o.remain_status
      ORDER BY o.`依頼先cd`, o.remain_status
EOD;
    $dbMain->exec($sql);

    $logger->info('共通日次バッチ: 注残履歴保存処理 終了');
  }

  /**
   * NextEngine 区分値更新
   */
  private function updateNeKubunList()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: NextEngine 区分値更新処理 開始');

    // NextEngine CSVアップロード状態取得処理 実行

    foreach(UpdateNextEngineApiKubunListCommand::$KUBUN_TARGETS as $target) {
      $commandArgs = [
          'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
        , sprintf('--target=%s', $target)
      ];

      $input = new ArgvInput($commandArgs);
      $output = new ConsoleOutput();

      $command = $this->getContainer()->get('batch.update_next_engine_api_kubun_list');
      $command->run($input, $output);
    }

    $logger->info('共通日次バッチ: NextEngine 区分値更新処理 終了');
  }

  /**
   * カラバリ画像 ファイルリスト作成
   * ※登録・削除追跡用
   */
  public function createVariationImageList()
  {
    $logger = $this->getLogger();

    $today = new \DateTime();

    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getContainer()->get('misc.util.file');

    $fs = new Filesystem();
    $logDir = sprintf('%s/variation_images', $fileUtil->getLogDir());
    if (!$fs->exists($logDir)) {
      $fs->mkdir($logDir, 0755);
    }
    $logFilePath = sprintf('%s/variation_images.%s.gz', $logDir, $today->format('Ymd'));
    $imageDir = $this->getContainer()->getParameter('product_image_variation_dir');

    $command = '/usr/bin/find "' . $imageDir . '" -type f -printf "%TY-%Tm-%Td %TH:%TM:%TS %p %k\\n"'
      . ' | sed -e "s|' . $imageDir . '/||"'
      . ' | sort -k 3 '
      . ' | gzip -c > "' . $logFilePath . '"';

    $logger->info($command);

    try {

      $process = new Process($command);
      $process->setTimeout(0);
      $process->mustRun();

    } catch (\Exception $e) {
      $logger->error('共通日次バッチ処理 バリエーション画像一覧作成 エラー:' . $e->getMessage());
      $logger->addDbLog(
          $logger->makeDbLog('共通日次バッチ処理 バリエーション画像一覧作成 エラー', '共通日次バッチ処理 バリエーション画像一覧作成 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '共通日次バッチ処理 バリエーション画像一覧作成 でエラーが発生しました。', 'error'
      );

      // エラーは記録だけして次の処理へ。
    }

    return;
  }

  /**
   * バーコード作成漏れ 補完
   */
  private function insertMissingBarcode()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: バーコード作成漏れ補完処理 開始');

    try {
      $dbMain = $this->getDb('main');

      $sql = <<<EOD
        INSERT INTO tb_product_code (
          ne_syohin_syohin_code
        )
        SELECT
          pci.ne_syohin_syohin_code
        FROM tb_productchoiceitems pci
        LEFT JOIN tb_product_code c ON pci.ne_syohin_syohin_code = c.ne_syohin_syohin_code
        WHERE c.id IS NULL
EOD;
      $dbMain->exec($sql);

      $logger->info('共通日次バッチ: バーコード作成漏れ補完処理 終了');

    } catch (\Exception $e) {
      $logger->error('共通日次バッチ処理 バーコード作成漏れ補完処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('共通日次バッチ処理 バーコード作成漏れ補完処理 エラー', '共通日次バッチ処理 バーコード作成漏れ補完処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '共通日次バッチ処理 バーコード作成漏れ補完処理 でエラーが発生しました。', 'error'
      );

      // エラーは記録だけして次の処理へ。
    }
  }

  /**
   * 実店舗ピッキングリスト削除処理
   */
  private function clearRealShopPickingList()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 実店舗ピッキングリスト削除処理 開始');

    try {
      // すでにピッキング済みのデータがあれば、再集計できない。（PASSはスルー）
      /** @var TbRealShopPickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbRealShopPickingList');
      if ($repo->getPickedCount() > 0) {
        // エラーを残すのみ。処理は行う。
        $logger->addDbLog(
          $logger->makeDbLog('実店舗ピッキングリスト削除処理 エラー', '実店舗ピッキングリスト削除処理 エラー', '部分実行')
          , true, '実店舗ピッキングリスト削除処理 でエラーが発生しました。', 'error'
        );
      }

      $repo->clearAll();

    } catch (\Exception $e) {

      $logger->error('実店舗ピッキングリスト削除処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('実店舗ピッキングリスト削除処理 エラー', '実店舗ピッキングリスト削除処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '実店舗ピッキングリスト削除処理 でエラーが発生しました。', 'error'
      );

      // エラーは記録だけして次の処理へ。
    }
  }


  /**
   * 倉庫在庫ピッキングリスト削除処理
   */
  private function clearWarehouseStockMovePickingList()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 倉庫在庫ピッキングリスト削除処理 開始');

    try {
      /** @var TbWarehouseStockMovePickingListRepository $repo */
      $repo = $this->getDoctrine()->getRepository('MiscBundle:TbWarehouseStockMovePickingList');
      $repo->clearAll();

    } catch (\Exception $e) {

      $logger->error('倉庫在庫ピッキングリスト削除処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('倉庫在庫ピッキングリスト削除処理 エラー', '倉庫在庫ピッキングリスト削除処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '倉庫在庫ピッキングリスト削除処理 でエラーが発生しました。', 'error'
      );

      // エラーは記録だけして次の処理へ。
    }
  }
  
  /**
   * AnnualSalesの更新。anuualと言いながら現在は240日分
   */
  private function updateAnnualSales() {
    
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: AnnualSales集計　開始');
    
    try {
      $dbMain = $this->getDb('main');
      
      // 0で初期化
      $sql = <<<EOD
      UPDATE tb_mainproducts_cal AS cal
      SET cal.annual_sales = 0
EOD;
      $dbMain->query($sql);
      
      $sql = <<<EOD
        UPDATE tb_mainproducts_cal AS cal
        INNER JOIN (
            SELECT
                cal.daihyo_syohin_code
              , SUM(O.受注数) AS order_num
            FROM tb_mainproducts_cal cal
            INNER JOIN tb_sales_detail_analyze O ON O.daihyo_syohin_code = cal.daihyo_syohin_code
            WHERE O.受注日 >= DATE_ADD(CURDATE(), INTERVAL - 240 DAY)
              AND O.キャンセル区分 = '0'
              AND O.明細行キャンセル = '0'
            GROUP BY cal.daihyo_syohin_code
        ) AS OT ON cal.daihyo_syohin_code = OT.daihyo_syohin_code
        SET cal.annual_sales = order_num
EOD;
      $dbMain->exec($sql);
      $logger->info('共通日次バッチ: AnnualSales集計 終了');
    } catch (\Exception $e) {
      $logger->error('共通日次バッチ処理 AnnualSales集計 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('共通日次バッチ処理 AnnualSales集計 エラー', '共通日次バッチ処理　AnnualSales集計 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, '共通日次バッチ処理 AnnualSales集計 でエラーが発生しました。', 'error'
        );
      
      // エラーは記録だけして次の処理へ。
    }
  }

  /**
   * 注残欠品未引当通知処理
   */
  /* 不要につき削除予定
  private function notifyNonAssignedShortageStock()
  {
    $logger = $this->getLogger();
    $logger->info('共通日次バッチ: 注残欠品未引当通知処理 開始');

    $commandArgs = [
      'dummy' // 引数を並べていく。最初の引数は何でもよいが必要
    ];

    $input = new ArgvInput($commandArgs);
    $output = new ConsoleOutput();

    $command = $this->getContainer()->get('batch.notify_non_assigned_shortage_stock');
    $command->run($input, $output);

    $logger->info('共通日次バッチ: 注残欠品未引当通知処理 終了');
  }
  */

}
