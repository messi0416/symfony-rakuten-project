<?php
namespace BatchBundle\Command;

use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbProcessExecuteLog;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MiscBundle\Exception\BusinessException;

abstract class PlusnaoBaseCommand extends ContainerAwareCommand {

  use CommandBaseTrait;

  /**
   * コマンドのクラス名とプロセスIDのリスト。tb_process_execute_logへ投入する際のプロセスIDを取得する。
   * @var array
   */
  private $processIdList =
  [
      'BatchBundle\Command\AggregateSalesDetailCommand' => 1, //伝票毎集計
      'BatchBundle\Command\ArchiveAdultCheckImageToZipCommand' => 2, //アダルトチェック画像コピー圧縮処理
      'BatchBundle\Command\CheckProductImageCommand' => 3, //楽天画像存在チェック
      'BatchBundle\Command\ConvertMallOrderCsvDataCommand' => 4, //モール受注CSV変換処理
      'BatchBundle\Command\ConvertMallOrderCsvEcCubeAndUploadCommand' => 5, //EC-CUBE受注変換NextEngineアップロード処理
      'BatchBundle\Command\CreateAmazonFbaMultiChannelTransportListCommand' => 6, //Amazon FBAマルチチャネル移動伝票作成処理
      'BatchBundle\Command\CsvDownloadAndUpdateAccessCountCommand' => 7, //楽天アクセス数更新処理
      'BatchBundle\Command\CsvDownloadAndUpdateAmazonFbaStockCommand' => 8, //Amazon在庫比較テーブル FBA在庫数更新
      'BatchBundle\Command\CsvDownloadAndUpdateAmazonMallOrderCommand' => 9, //Amazon モール受注CSV取込
      'BatchBundle\Command\CsvDownloadAndUpdateAmazonOrderRecentCommand' => 10, //Amazon注文情報 更新
      'BatchBundle\Command\CsvDownloadAndUpdateAmazonProductStockCommand' => 11, //Amazon在庫比較テーブル更新
      'BatchBundle\Command\CsvDownloadAndUpdateRakutenReviewCommand' => 12, //楽天レビュー取込処理
      'BatchBundle\Command\CsvDownloadAndUpdateRSLProductStockCommand' => 13, //楽天スーパーロジ倉庫 在庫テーブル更新
      'BatchBundle\Command\CsvDownloadAndUpdateShoplistProductStockCommand' => 14, //SHOPLIST在庫比較テーブル更新
      'BatchBundle\Command\CsvDownloadAndUpdateShoplistProductStockSpeedCommand' => 15, //SHOPLIST在庫比較テーブル更新
      'BatchBundle\Command\CsvDownloadAndUpdateShoplistSalesCommand' => 16, //SHOPLIST 販売実績テーブル更新
      'BatchBundle\Command\CsvDownloadAndUpdateStockInOutCommand' => 17, //入出庫データ取込
      'BatchBundle\Command\CsvDownloadAndUpdateUploadCheckCommand' => 18, //CSVアップロード状態データ取込
      'BatchBundle\Command\CsvDownloadAndUpdateViewRankingCommand' => 19, //閲覧ランキング更新処理
      'BatchBundle\Command\CsvDownloadAndUpdateYabuyoshiProductStockCommand' => 20, //藪吉在庫比較テーブルの更新処理
      'BatchBundle\Command\CsvDownloadOrderDataCommand' => 21, //受注取込 CSVデータダウンロード
      'BatchBundle\Command\CsvDownloadPpmProductsCommand' => 22, //PPM 商品CSVダウンロード
      'BatchBundle\Command\CsvDownloadStockListCommand' => 23, //在庫データ取込 CSVダウンロード
      'BatchBundle\Command\CsvDownloadStockListOriginalCommand' => 24, //在庫更新処理（オリジナルCSV）
      'BatchBundle\Command\CsvDownloadYahooProductsCommand' => 25, //Yahoo CSVダウンロード
      'BatchBundle\Command\DailyBatchCommand' => 26, //共通日次バッチ
      'BatchBundle\Command\DailyDbBackupCheckCommand' => 27, //DBバックアップ日次処理 処理結果通知＆確認
      'BatchBundle\Command\DevCreateDummyProductPriceLogCommand' => 28, //開発用 商品価格履歴データ作成
      'BatchBundle\Command\DevCreateMiniDatabaseCommand' => 29, //開発用DB作成
      'BatchBundle\Command\DiscountProcessCommand' => 30, //値下確定処理
      'BatchBundle\Command\ExportCsvAmazonComCommand' => 31, //Amazon.com CSV出力処理
      'BatchBundle\Command\ExportCsvAmazonCommand' => 32, //AmazonCSV出力処理
      'BatchBundle\Command\ExportCsvAmazonFbaOrderCommand' => 33, //Amazon FBA出荷用CSV出力
      'BatchBundle\Command\ExportCsvAmazonUpdateStockCommand' => 34, //CSVエクスポート Amazon 在庫更新
      'BatchBundle\Command\ExportCsvAmazonUploadCommand' => 35, //CSVエクスポート Amazon アップロード
      'BatchBundle\Command\ExportCsvNextEngineCommand' => 36, //NextEngine CSVエクスポート
      'BatchBundle\Command\ExportCsvNextEngineUpdateStockCommand' => 37, //NextEngineCSV在庫同期処理
      'BatchBundle\Command\ExportCsvNextEngineUploadCommand' => 38, //NextEngineCSV出力処理 アップロード
      'BatchBundle\Command\ExportCsvNextEngineUploadProductApiCommand' => 39, //NextEngine商品マスタ一括更新API処理
      'BatchBundle\Command\ExportCsvPpmCommand' => 40, //PPM CSV出力
      'BatchBundle\Command\ExportCsvQ10Command' => 41, //Q10 CSV出力
      'BatchBundle\Command\ExportCsvRakutenCommand' => 42, //楽天CSV出力
      'BatchBundle\Command\ExportCsvRakutenKickCsvDownloadCommand' => 43, //楽天 CSV出力 インポート用CSVダウンロード準備
      'BatchBundle\Command\ExportCsvRakutenRppExcludeCommand' => 44, //楽天RPP除外CSV出力
      'BatchBundle\Command\ExportCsvRakutenUpdateStockCommand' => 45, //楽天 在庫更新CSV出力
      'BatchBundle\Command\ExportCsvShoplistCommand' => 46, //SHOPLIST CSV出力
      'BatchBundle\Command\ExportCsvShoplistUpdateStockCommand' => 47, //SHOPLIST CSV出力処理 在庫更新
      'BatchBundle\Command\ExportCsvShoplistUploadCommand' => 48, //SHOPLIST CSV出力処理 アップロード
      'BatchBundle\Command\ExportCsvWowmaCommand' => 49, //Wowma CSV出力
      'BatchBundle\Command\ExportCsvYahooCommand' => 50, //ヤフーCSV出力処理
      'BatchBundle\Command\ExportCsvYahooOtoriyoseCommand' => 51, //YahooおとりよせCSV出力
      'BatchBundle\Command\ExportCsvYahooUpdateStockCommand' => 52, //Yahoo CSV出力処理 在庫更新
      'BatchBundle\Command\ExportCsvYahooUploadCommand' => 53, //ヤフーCSV出力処理 アップロード
      'BatchBundle\Command\ExportOrderListToExcelCommand' => 54, //輸出書類出力
      'BatchBundle\Command\FetchUpdate1688CompaniesCommand' => 55, //アリババ会社テーブル一括更新
      'BatchBundle\Command\FetchUpdate1688CompanyProductsCommand' => 56, //アリババ企業商品一括更新
      'BatchBundle\Command\FetchUpdate1688NewProductsCommand' => 57, //アリババ未取得商品
      'BatchBundle\Command\FetchUpdate1688ProductsCommand' => 58, //アリババ登録商品巡回
      'BatchBundle\Command\FetchUpdate1688ProductsMultiTestCommand' => 59, //アリババ登録商品巡回(定時テスト)
      'BatchBundle\Command\ImageCheckYahooCommand' => 68, //Yahoo CSV出力処理 画像チェック＆楽天からの取得処理
      'BatchBundle\Command\ImageCheckYahooCreateListCommand' => 69, //ヤフー画像チェック処理
      'BatchBundle\Command\ImportRakutenTagCsvCommand' => 70, //楽天タグ一覧CSV インポート処理
      'BatchBundle\Command\LogProductLocationSnapshotCommand' => 71, //商品ロケーション日次スナップショット 保存
      'BatchBundle\Command\LogProductPriceCommand' => 72, //毎日の商品価格 保存
      'BatchBundle\Command\LogVendorCostRateCommand' => 73, //仕入先原価率履歴 保存
      'BatchBundle\Command\NotifyNonAssignedShortageStockCommand' => 74, //注残欠品未引当通知
      'BatchBundle\Command\OrderMethodChangeCommand' => 75, //発送方法一括変換
      'BatchBundle\Command\ProductImageCheckCommand' => 76, //商品画像チェック
      'BatchBundle\Command\ProductImageUploadFtpCommand' => 77, //商品画像 アップロード処理（楽天・Yahoo・PPM）
      'BatchBundle\Command\ProductImageUploadFtpShoplistCommand' => 78, //商品画像 一括アップロード処理（SHOPLIST）
      'BatchBundle\Command\ProductLocationSortOrderCommand' => 79, //商品ロケーション自動並べ替え
      'BatchBundle\Command\RealShopImportSmaregiStockCommand' => 80, //スマレジ在庫取込
      'BatchBundle\Command\RealShopRegisterSmaregiProductsCommand' => 81, //スマレジ商品一括登録
      'BatchBundle\Command\RecalculateProductStocksCommand' => 82, //商品在庫再集計
      'BatchBundle\Command\RecalculatePurchaseOrderCommand' => 83, //発注再計算
      'BatchBundle\Command\RefreshLocationCommand' => 84, //ロケーション更新
      'BatchBundle\Command\RefreshLocationRackPlaceCodeCommand' => 85, //ロケーション棚コード一覧更新
      'BatchBundle\Command\RefreshLocationWarehouseToListCommand' => 86, //ロケーション 倉庫へ画面 在庫数更新
      'BatchBundle\Command\RefreshProductTitleKeywordListCommand' => 87, //商品タイトルキーワードリスト作成
      'BatchBundle\Command\RefreshRealShopPickingListCommand' => 88, //実店舗ピッキングリスト更新
      'BatchBundle\Command\RefreshStatementDetailProductNumListCommand' => 89, //納品書印刷待ち伝票一覧再集計
      'BatchBundle\Command\RefreshWarehouseStockMoveListCommand' => 90, //在庫移動一覧更新
      'BatchBundle\Command\ScrapeRakutenKeywordRankingItemLogCommand' => 91, //楽天キーワードランキング 商品一覧履歴保存
      'BatchBundle\Command\ScrapeRakutenKeywordRankingLogCommand' => 92, //楽天キーワードランキング履歴保存
      'BatchBundle\Command\SkuShippingdivisionAutoSettingCommand' => 93, //SKU別送料設定自動設定
      'BatchBundle\Command\SkuShippingdivisionReflectMainproductCommand' => 95, //SKU別送料設定の商品マスタ反映
      'BatchBundle\Command\SkuSizeChangeRelatedUpdateCommand' => 96, //SKUのサイズ変更に伴う更新処理起動
      'BatchBundle\Command\TbNeMallOrderRefreshCommand' => 97, //モール受注リフレッシュ
      'BatchBundle\Command\UpdateChangeShippingMethodOrderCommand' => 98, //発送方法変更 受注情報更新処理
      'BatchBundle\Command\UpdateDbByOrderListCsvCommand' => 99, //受注データ取込
      'BatchBundle\Command\UpdateDbByOrderListNextEngineApiCommand' => 100, //受注明細取込（差分更新）
      'BatchBundle\Command\UpdateDbByRakutenReviewInvalidDataCommand' => 101, //楽天レビュー代表商品コード補正
      'BatchBundle\Command\UpdateDbByStockListCsvCommand' => 102, //在庫一覧CSVを元にしたDB更新処理
      'BatchBundle\Command\UpdateImmediateShippingDateCommand' => 103, //即納予定日更新
      'BatchBundle\Command\UpdateNextEngineApiKubunListCommand' => 104, //NextEngine 区分値一覧テーブル更新
      'BatchBundle\Command\UpdateNextEngineApiShopListCommand' => 105, //NextEngine店舗一覧テーブル更新
      'BatchBundle\Command\UpdateProductCostRateProcessCommand' => 106, //商品別原価率更新処理
      'BatchBundle\Command\UpdateProductSizeCommand' => 107, //代表商品サイズ更新
      'BatchBundle\Command\UpdateYahooAgentProductCommand' => 108, //Yahoo代理店商品更新
      'BatchBundle\Command\UpdateYahooReviewCommand' => 109, //Yahoo商品レビューCSVデータ登録
      'BatchBundle\Command\WarehouseBoxMoveCommand' => 110, //倉庫間箱移動
      'BatchBundle\Command\WebCheckAlibabaApiQueueCommand' => 111, //アリババ(1688.com) 巡回処理 在庫巡回キュー追加
      'BatchBundle\Command\WebCheckAlibabaScrapingUpdateProductStatusCommand' => 112, //WebChecker 巡回処理実装 （阿里巴巴スクレイピング）結果反映
      'BatchBundle\Command\WebCheckTaobaoScrapingUpdateProductStatusCommand' => 113, //WebChecker 巡回処理実装 （タオバオスクレイピング）結果反映
      'BatchBundle\Command\WebCheckVendorProductsAkfCommand' => 114, //WebChecker 巡回処理実装 新商品巡回 (AKF)
      'BatchBundle\Command\WebCheckVendorProductsAlibabaCommand' => 115, //WebChecker 巡回処理実装 新商品巡回 (阿里巴巴)
      'BatchBundle\Command\WebCheckVendorProductsNetseaCommand' => 116, //WebChecker 巡回処理実装 新商品巡回 (NETSEA)
      'BatchBundle\Command\WebCheckVendorProductsSuperDeliveryCommand' => 117, //WebChecker 巡回処理実装 新商品巡回 (SUPER DELIVERY)
      'BatchBundle\Command\WebCheckVendorProductsVivicaDuoCommand' => 118, //WebChecker 巡回処理実装 新商品巡回 (Vivica Duo)
      'BatchBundle\Command\ExportAmazonXMLWithConvertingToNextEngineCSVCommand' => 119, //AmazonMWS注文のCSV作成およびネクストエンジンへのアップロード
      'BatchBundle\Command\AggregateWarehouseResultHistoryCommand' => 123,
      'BatchBundle\Command\ExportCsvNextEngineProductEnqueueCommand' => 124, //NextEngine 登録・更新CSV キュー追加
      // #210667 TbProcessで、143, 144に分離したので125は使用終了
      'BatchBundle\Command\AggregateProductSalesAccountResultHistoryCommand' => 125, //商品売上実績集計処理
      'BatchBundle\Command\AggregateProductImagesAttentionImageCommand' => 127, // 商品画像アテンション画像集計処理
      'BatchBundle\Command\CsvDownloadAndUpdateShippingVoucherCommand' => 128, // 出荷リスト自動生成処理
      'BatchBundle\Command\AggregatePickingScoreCommand' => 129, // ピッキングスコア集計処理
      'BatchBundle\Command\ExportCsvNextEngineSetProductCommand' => 130, // CSVエクスポート NextEngine セット商品CSV出力
      'BatchBundle\Command\ExportCsvNextEngineUploadSetProductCommand' => 131, // CSVエクスポート NextEngine セット商品CSVアップロード
      'BatchBundle\Command\UpdateProductSalesStatusCommand' => 132, // 代表商品販売ステータス更新処理
      'BatchBundle\Command\UpdateSkuColorCommand' => 133, // SKU別カラー種別更新処理
      'BatchBundle\Command\UpdateProductSalesAccountApplyEndCommand' => 134, // 商品売上担当者適用終了処理
      'BatchBundle\Command\ExportCsvRakutenGoldCommand' => 135, // 楽天GOLD CSV出力処理
      'BatchBundle\Command\ExportCsvNextEngineMallProductCommand' => 136, // CSVエクスポート NextEngine モール商品CSV出力
      'BatchBundle\Command\ExportCsvNextEngineUploadMallProductCommand' => 137, // CSVエクスポート NextEngineモール商品CSVアップロード
      'BatchBundle\Command\ScrapeRakutenCategoryListCommand' => 138, // 楽天カテゴリリスト更新
      'BatchBundle\Command\UpdateRakutenGenreAttributeCommand' => 139, // 楽天商品属性項目マスタ更新処理
      'BatchBundle\Command\UpdateSkuRakutenAttributeCommand' => 140, // SKU別商品属性項目更新処理
      // 143 商品売上実績集計処理（全体集計）
      // 144 商品売上実績集計処理（担当者更新分の集計）
      'BatchBundle\Command\CreateTransportListForShoplistSpeedBinCommand' => 145, // SHOPLISTスピード便移動伝票作成
      'BatchBundle\Command\ImportRakutenSkuAttributeValueCommand' => 146, // 楽天SKU属性情報値取込処理
      'BatchBundle\Command\AggregateShoplistSpeedbinDeliveryCommand' => 147, // SHOPLISTスピード便出荷数集計処理
      'BatchBundle\Command\ExportCsvRakutenUploadCommand' => 148, // CSVエクスポート 楽天 アップロード
  ];

  /** @var TbProcessExecuteLog 処理履歴ログ。処理数1、2、3、バージョンのみ利用します */
  protected $processExecuteLog = null;

  /** @var string コマンド和名 ログファイルのログ出力に利用 実装クラスで指定しなければクラス名が利用される */
  protected $commandName = null;

  /** @var SymfonyUsers $account */
  protected $account = null;

  /**
   * 初期化を行う。
   * 各クラスでオーバーライドし、必要な情報を登録する。
   */
  protected function initializeProcess(InputInterface $input) {
    $this->commandName = get_class($this); // abstractクラスではクラス名を入れているが、実際にはログ出力用の和名を登録する
  }

  /**
   * 履歴登録用のプロセスIDを取得する。
   * デフォルトではクラス名から取得する。
   * 同じクラスでも、大きく異なる複数の処理を行う機能では、オーバーライドして処理種別ごとの値を取得させる。
   * （定数定義は TbProcessクラスで行う）
   */
  protected function getProcessId(InputInterface $input) {
    return $this->processIdList[get_class($this)];
  }

  /**
   * 処理を実装する。処理の本体はdoProcessで行い、executeではテンプレートメソッドパターンで前後の処理を提供する。
   *
   * 各呼び出し先メソッドからBusinessExceptionがthrowされた場合は、このメソッドではテキストログの出力のみ行い、exit 1で処理を終了する。
   * tb_logへの登録（addDbLog）など、他の処理が必要な場合は、throw前に各子クラスで実施する。
   * exit 0 にする場合は呼び出し先でthrowをやめ、正常終了すること。
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    try {
      $this->initializeProcess($input);
      $this->insertProcessStartLog($input); // プロセス実行履歴　開始ログを登録
      $this->initLogger($input);
      $logger = $this->getLogger();

      $this->doProcess($input, $output);

      $logger->addDbLog($logger->makeDbLog(null, '終了'));
      $logger->info($this->commandName . 'を終了しました。');
      $this->updateProcessLogToFinish(); // プロセス実行履歴　正常終了ログを登録
      $logger->logTimerFlush();

      return 0;

    } catch (BusinessException $e) {
      $logger = $this->getLogger();
      $logger->info($this->commandName . 'で業務例外が発生しました。' . $e->getMessage());
      return 1;
    } catch (\Throwable $t) {
      $logger = $this->getLogger();
      $logger->error($this->commandName . ':' . $t->getMessage() . $t->getTraceAsString());
      $logger->addDbLog(
          $logger->makeDbLog(null, 'エラー終了')->setInformation($t->getMessage() . ':' . $t->getTraceAsString())
          , true, $this->commandName . 'でエラーが発生しました。', 'error'
          );
      $this->updateProcessLogToError($t); // プロセス実行履歴　異常終了ログを登録
      $logger->logTimerFlush();
      return 1;
    }
  }

  /**
   * loggerの初期化を行う。タイトルと、あればアカウント設定を行い、開始ログ、DB開始ログを出力する。
   * @param InputInterface $input
   */
  protected function initLogger(InputInterface $input) {
    $logger = $this->getLogger();
    $logger->setExecTitle($this->commandName);
    $logger->info($this->commandName . 'を開始しました。');

    // 実行アカウントの取得 (ログ記録PC, NEログインアカウント)
    if ($input->hasOption('account')) {
      if ($accountId = $input->getOption('account')) {
        $logger->debug("アカウントID=" . $accountId);
        /** @var SymfonyUsers $account */
        $account = $this->getDoctrine()->getRepository('MiscBundle:SymfonyUsers')->find($accountId);
        if ($account) {
          $this->account = $account;
          $logger->setAccount($account);
        }
      }
    }
    $logger->addDbLog($logger->makeDbLog(null, '開始'));
  }

  /**
   * 処理本体。実装クラスはこの処理を実装する
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected abstract function doProcess(InputInterface $input, OutputInterface $output);

  /**
   * 処理履歴ログの開始ログを登録する。
   * 通常オーバーライドの必要はない。
   */
  protected function insertProcessStartLog(InputInterface $input) {
    $processId = $this->getProcessId($input);
    if ($processId == null) {
      throw new \RuntimeException('プロセスIDが定義されていません。');
    }

    $em = $this->getDoctrine()->getManager('log');
    $processExecuteLog = new TbProcessExecuteLog();
    $processExecuteLog->setProcessId($processId);
    $processExecuteLog->setStatus(TbProcessExecuteLog::STATUS_PROCESSING);
    $processExecuteLog->setQueueName($input->getOption('queue-name'));
    $processExecuteLog->setVersion(0.0);
    $processExecuteLog->setStartDatetime(new \DateTime());
    $em->persist($processExecuteLog);
    $em->flush();
    $this->processExecuteLog = $processExecuteLog;
  }

  /**
   * 処理履歴ログの正常終了ログを登録する。
   * そのままでも使用できるが、オーバーライドして各処理に合った実装とすることも可能。
   * 処理内容の例としては、処理件数をこのタイミングで再集計する場合などが考えられる。
   */
  protected function updateProcessLogToFinish() {
    $em = $this->getDoctrine()->getManager('log');
    $this->processExecuteLog->setStatus(TbProcessExecuteLog::STATUS_FINISHED);
    $this->processExecuteLog->setEndDatetime(new \DateTime());
    $em->flush();
  }

  /**
   * 処理履歴ログのエラー終了ログを登録する。
   * 通常オーバーライドの必要はない。
   * このクラスに例外がThrowされた場合はこのタイミングでログを書き出すが、通常は各処理から全体の例外処理のタイミングで呼び出す事。
   */
  protected function updateProcessLogToError(\Throwable $t) {
    $em = $this->getDoctrine()->getManager('log');
    $this->processExecuteLog->setStatus(TbProcessExecuteLog::STATUS_ERROR_END);
    $this->processExecuteLog->setEndDatetime(new \DateTime());
    $this->processExecuteLog->setErrorInformation($t->getMessage() . ':' . $t->getTraceAsString());
    $em->flush();
  }
}
