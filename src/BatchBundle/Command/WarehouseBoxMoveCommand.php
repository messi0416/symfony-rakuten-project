<?php
/**
 * 倉庫間箱移動処理
 */

namespace BatchBundle\Command;

use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\Repository\TbCronProcessScheduleRepository;
use MiscBundle\Entity\Repository\TbLocationRepository;
use MiscBundle\Entity\Repository\TbProductLocationRepository;
use MiscBundle\Entity\Repository\TbSalesDetailAnalyzeRepository;
use MiscBundle\Util\WebAccessUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class WarehouseBoxMoveCommand extends PlusnaoBaseCommand
{
    protected function configure()
    {
        $this
            ->setName('batch:warehouse-box-move')
            ->setDescription('倉庫間箱移動処理')
            ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
            ->addOption('stocks', null, InputOption::VALUE_OPTIONAL, '在庫数')
            ->addOption('order_date', null, InputOption::VALUE_OPTIONAL, '日の受注')
            ->addOption('magnification_percent', null, InputOption::VALUE_OPTIONAL, '倍率')
            ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
        ;
    }

    /**
     * 初期化を行う。
     */
    protected function initializeProcess(InputInterface $input) {
      $this->commandName = '倉庫間箱移動処理';
    }

    protected function doProcess(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $logger = $this->getLogger();

        $stocks = (int)$input->getOption('stocks');
        $order_date = (int)$input->getOption('order_date');
        $magnification_percent = (int)$input->getOption('magnification_percent');

        /** @var TbCronProcessScheduleRepository $cronProcessScheduleRepository */
        $cronProcessScheduleRepository = $this->getDoctrine()->getRepository('MiscBundle:TbCronProcessSchedule');
        $schedules = $cronProcessScheduleRepository->findExternalWarehouseBoxMoveSetting();
        $result = [];
        foreach ($schedules as $schedule) {
            $result[] = $schedule->toScalarArray();
        }

        if ($stocks == 0) {
            $stocks = $result[0]['stocks'];
        }

        if ($order_date == 0) {
            $order_date = $result[0]['order_date'];
        }

        if ($magnification_percent == 0) {
            $magnification_percent = $result[0]['magnification_percent'];
        }

        /** @var WebAccessUtil $webAccessUtil */
        $webAccessUtil = $container->get('misc.util.web_access');
        if ($this->account) {
            $webAccessUtil->setAccount($this->account);
        }

        if ($stocks >= 0) {
            /** @var TbLocationRepository $locationRepository */
            $locationRepository = $this->getDoctrine()->getRepository('MiscBundle:TbLocation');

            /** @var TbSalesDetailAnalyzeRepository $salesDetailAnalyzeRepository */
            $salesDetailAnalyzeRepository = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetailAnalyze');

            /** @var TbProductLocationRepository $productLocationRepository */
            $productLocationRepository = $this->getDoctrine()->getRepository('MiscBundle:TbProductLocation');

            $logger->debug("倉庫間箱移動処理 受注数確認日数:".$order_date." 倍率:".$magnification_percent." 最低在庫数:".$stocks);

            $em = $this->getDoctrine()->getManager('main');

            // 移動対象外倉庫の全ロケーションの古市移動フラグをnullに更新
            $locationRepository->updateMoveFuruichiWarehouseFlgNull();

            // 古市および移動対象倉庫の全ロケーションの古市移動フラグを0に更新(初期化)
            $locationRepository->updateMoveFuruichiWarehouseFlgDisable();

            // 受注数確認日数、倍率が指定されている場合、受注数から必要在庫数を計算
            $requiredStocks = []; // 必要在庫数一覧(key=商品コード)
            if ($order_date > 0 && $magnification_percent > 0) {
                $order_date_start = date('Y-m-d', strtotime("-" . $order_date . " days")); // 指定日前　～　本日
                $order_date_end = date('Y-m-d');

                // 商品別受注数取得
                $orders = $salesDetailAnalyzeRepository->getSumVoucherByOrderDate($order_date_start, $order_date_end);
                $this->processExecuteLog->setProcessNumber1(count($orders)); // 受注があった商品数

                // 受注数から必要在庫数を計算
                foreach ($orders as $order) {
                    $syohinCode = strtoupper($order['商品コード（伝票）']); // 商品コード（伝票）(マッピングのため大文字に統一)
                    $numOrders = (int)$order['SUM']; // 期間内の受注数合計
                    $requiredStockCalc = ceil($numOrders * $magnification_percent); // 受注数×倍率(端数切り上げ)

                    // (受注数×倍率)と最低在庫数を比較し、多い方を必要在庫数とする
                    if ($requiredStockCalc > $stocks) {
                        $requiredStocks[$syohinCode] = $requiredStockCalc;
                    } else {
                        $requiredStocks[$syohinCode] = $stocks;
                    }
                }
            }

            // 最低在庫数 > 0の場合
            if ($stocks > 0) {
                // 受注なしかつ在庫が存在する商品の一覧を取得
                $noOrderStockProducts = $productLocationRepository->getNeSyohinCodeInStock(array_keys($requiredStocks));

                // 受注なし商品の必要在庫数を追加
                foreach($noOrderStockProducts as $product) {
                    $syohinCode = strtoupper($product['ne_syohin_syohin_code']); // 商品コード (マッピングのため大文字に統一)
                    $requiredStocks[$syohinCode] = $stocks; // 最低在庫数
                }
            }

            // 倉庫移動候補の在庫一覧をtb_product_locationから抽出
            $productLocations = $productLocationRepository->getActiveLocationsForMoveWarehouse(array_keys($requiredStocks));

            // 在庫一覧と必要在庫数一覧を元に古市倉庫に移動するロケーションID一覧を作成
            $furuichiLocationIds = $this->convertToFuruichiLocations($productLocations, $requiredStocks);

            // ロケーションID指定で古市移動フラグを一括更新
            $locationRepository->updateMoveFuruichiWarehouseFlgByLocations($furuichiLocationIds);

            $this->processExecuteLog->setProcessNumber3(count($furuichiLocationIds));
            $this->processExecuteLog->setVersion(2.0);
        }
        $em->flush();
    }

    /**
     * SKU別の必要在庫数とロケーション一覧を元に、古市倉庫に移動するロケーションID一覧を生成
     * @param array $productStocks 在庫一覧（検索結果）
     * @param array $req_stocks 必要在庫数一覧 : 商品コード⇒必要在庫数
     * @return array 古市ロケーションID一覧
     */
    private function convertToFuruichiLocations(array $productStocks, array $req_stocks) : array
    {
        $furuichiLocations = []; // 古市ロケーション配列 (key=ロケーションID)
        $furuichiStocks = []; // 古市在庫数配列 (key=商品コード)

        foreach ($productStocks AS $ps) {
            $syohinCode = strtoupper($ps['ne_syohin_syohin_code']); // 商品コード (マッピングのため大文字に統一)
            $locationId = $ps['location_id'];
            $stock = $ps['stock'];

            if (!isset($req_stocks[$syohinCode])) {
                continue; // 必要在庫数一覧にSKUがなければスキップ
            }

            if (!isset($furuichiStocks[$syohinCode])) {
                $furuichiStocks[$syohinCode] = 0; // 初回のみ0をセット
            }
            // 必要在庫数 > 古市在庫数の場合
            if ($req_stocks[$syohinCode] > $furuichiStocks[$syohinCode]) {
                $furuichiStocks[$syohinCode] += $stock; // 古市在庫数に計上
                $furuichiLocations[$locationId] = 'true'; // 別商品等で登録済でも上書き
            }
        }
        return array_keys($furuichiLocations);
    }
}

