<?php

namespace BatchBundle\Command;

use BatchBundle\Command\ExportCsvNextEngineUploadMallProductCommand;
use BatchBundle\Job\BaseJob;
use BatchBundle\Job\NextEngineUploadJob;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\StringUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * NextEngineモール商品CSV出力処理
 *
 * データ量が多すぎてNextEngine側でエラーが出るため、
 * 現在は在庫連携必須である即納・一部即納に限って出力する。
 * @package BatchBundle\Command
 */
class ExportCsvNextEngineMallProductCommand extends PlusnaoBaseCommand
{
  private $shop; // 対象店舗。rakuten|motto|laforest|dolcissimo|yahooPlusnao|kawaemon
  private $isOnlyDiff = true; // 差分のみを対象にするか
  private $doUpload = true; // アップロード実行フラグ
  private $targetEnv = 'test'; // NextEngine側の環境

  private $exportPath; // CSV出力ディレクトリ

  private $neMallId; // NE側店舗コード
  private $tableItemDl; // 楽天select.csvのインポートテーブル
  private $tableSelectDl; // 楽天select.csvのインポートテーブル
  private $tableInformation; // モール別のinformationテーブル

  private $now; // 処理時刻
  private $currentCsv; // 現在処理中のCSV
  private $currentFileType; // 現在処理中のファイルタイプ(アップロード用)

  /** 対象店舗文字列：楽天 */
  const EXPORT_TARGET_RAKUTEN = 'rakuten';
  /** 対象店舗文字列：motto-motto */
  const EXPORT_TARGET_MOTTO = 'motto';
  /** 対象店舗文字列：LaForest */
  const EXPORT_TARGET_LAFOREST = 'laforest';
  /** 対象店舗文字列：dolcissimo */
  const EXPORT_TARGET_DOLCISSIMO = 'dolcissimo';
  /** 対象店舗文字列：gekipla */
  const EXPORT_TARGET_GEKIPLA = 'gekipla';
  /** 対象店舗文字列：yahoo plusnao */
  const EXPORT_TARGET_YAHOO_PLUSNAO = 'yahooPlusnao';
  /** 対象店舗文字列：yahoo kawaemon */
  const EXPORT_TARGET_KAWAEMON = 'kawaemon';
  /** 対象店舗文字列：wowma */
  const EXPORT_TARGET_WOWMA = 'wowma';
  /** 対象店舗文字列：ppm */
  const EXPORT_TARGET_PPM = 'ppm';

  /* キュー処理店舗一覧（本番テスト完了後に店舗を追加することを想定） */
  const ENQUEUE_SHOP_LIST = [
    self::EXPORT_TARGET_RAKUTEN,
    self::EXPORT_TARGET_MOTTO,
    self::EXPORT_TARGET_LAFOREST,
    self::EXPORT_TARGET_DOLCISSIMO,
    self::EXPORT_TARGET_GEKIPLA,
    self::EXPORT_TARGET_YAHOO_PLUSNAO,
    self::EXPORT_TARGET_KAWAEMON,
    self::EXPORT_TARGET_WOWMA,
    self::EXPORT_TARGET_PPM,
  ];

  const SHOP_LIST = [
    self::EXPORT_TARGET_RAKUTEN,
    self::EXPORT_TARGET_MOTTO,
    self::EXPORT_TARGET_LAFOREST,
    self::EXPORT_TARGET_DOLCISSIMO,
    self::EXPORT_TARGET_GEKIPLA,
    self::EXPORT_TARGET_YAHOO_PLUSNAO,
    self::EXPORT_TARGET_KAWAEMON,
    self::EXPORT_TARGET_WOWMA,
    self::EXPORT_TARGET_PPM,
  ];

  const RAKUTEN_SHOP_LIST = [
    self::EXPORT_TARGET_RAKUTEN,
    self::EXPORT_TARGET_MOTTO,
    self::EXPORT_TARGET_LAFOREST,
    self::EXPORT_TARGET_DOLCISSIMO,
    self::EXPORT_TARGET_GEKIPLA,
  ];

  const YAHOO_SHOP_LIST = [
    self::EXPORT_TARGET_YAHOO_PLUSNAO,
    self::EXPORT_TARGET_KAWAEMON,
  ];

  const WOWMA_SHOP_LIST = [
    self::EXPORT_TARGET_WOWMA,
  ];

  const PPM_SHOP_LIST = [
    self::EXPORT_TARGET_PPM,
  ];

  /** ファイルの分割設定件数: Yahoo/quantity */
  const YAHOO_QUANTITY_CSV_MAX_NUM = 40000; // 4万件で分割
  /** ファイルの分割設定件数: Wowma/stock */
  const WOWMA_STOCK_CSV_MAX_NUM = 20000; // 2万件で分割
  /** ファイルの分割設定件数: PPM/item */
  const PPM_ITEM_CSV_MAX_NUM = 8000; // 8000件で分割
  /** ファイルの分割設定件数: PPM/option */
  const PPM_OPTION_CSV_MAX_NUM = 39000; // 3.9万件で分割

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-next-engine-mall-product')
      ->setDescription('NextEngineモール商品CSV出力処理')
      ->addOption('shop', null, InputOption::VALUE_OPTIONAL, '対象店舗。' . implode('|', self::SHOP_LIST))
      ->addOption('is-only-diff', null, InputOption::VALUE_OPTIONAL, '差分のみを対象にするか。', 1)
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'アップロード実行フラグ', 1)
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, '対象のNE環境', 'test')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN);
  }

  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = 'NextEngineモール商品CSV出力処理';
    if ($input->getOption('shop')) {
      $this->commandName .= '[' . $input->getOption('shop') . ']';
    }
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $this->validate($input);

    $this->shop = $input->getOption('shop');
    $this->isOnlyDiff = (bool)$input->getOption('is-only-diff');
    $this->doUpload = (bool)$input->getOption('do-upload');
    $this->targetEnv = $input->getOption('target-env');
    switch ($this->shop) {
      case self::EXPORT_TARGET_RAKUTEN:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN;
        $this->tableItemDl = 'tb_rakutenitem_dl';
        $this->tableSelectDl = 'tb_rakutenselect_dl';
        break;
      case self::EXPORT_TARGET_MOTTO:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_MOTTO;
        $this->tableItemDl = 'tb_rakutenmotto_item_dl';
        $this->tableSelectDl = 'tb_rakutenmotto_select_dl';
        break;
      case self::EXPORT_TARGET_LAFOREST:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_LAFOREST;
        $this->tableItemDl = 'tb_rakutenlaforest_item_dl';
        $this->tableSelectDl = 'tb_rakutenlaforest_select_dl';
        break;
      case self::EXPORT_TARGET_DOLCISSIMO:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_DOLTI;
        $this->tableItemDl = 'tb_rakutendolcissimo_item_dl';
        $this->tableSelectDl = 'tb_rakutendolcissimo_select_dl';
        break;
      case self::EXPORT_TARGET_GEKIPLA:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_GEKIPLA;
        $this->tableItemDl = 'tb_rakutengekipla_item_dl';
        $this->tableSelectDl = 'tb_rakutengekipla_select_dl';
        break;
      case self::EXPORT_TARGET_YAHOO_PLUSNAO:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_YAHOO;
        $this->tableInformation = 'tb_yahoo_information';
        break;
      case self::EXPORT_TARGET_KAWAEMON:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_KAWA_E_MON;
        $this->tableInformation = 'tb_yahoo_kawa_information';
        break;
      case self::EXPORT_TARGET_WOWMA:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_WOWMA;
        $this->tableInformation = 'tb_biddersinfomation';
        break;
      case self::EXPORT_TARGET_PPM:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_PPM;
        $this->tableItemDl = 'tb_ppm_item_dl';
        $this->tableSelectDl = 'tb_ppm_select_dl';
        break;
    }

    // ディレクトリやファイル名に使う処理時刻を保存
    $this->now = (new \DateTime())->format('YmdHis');

    if (in_array($input->getOption('shop'), self::RAKUTEN_SHOP_LIST, true)) {
      $this->createRakutenItemCsv();
      $this->createRakutenSelectCsv();
    }
    if (in_array($input->getOption('shop'), self::YAHOO_SHOP_LIST, true)) {
      $this->createYahooQuantityCsv();
    }
    if (in_array($input->getOption('shop'), self::WOWMA_SHOP_LIST, true)) {
      $this->createWowmaStockCsv();
    }
    if (in_array($input->getOption('shop'), self::PPM_SHOP_LIST, true)) {
      $this->createPpmItemCsv();
      $this->createPpmSelectCsv();
    }
  }

  private function removeRegistrationFlg($target = main)
  {
    $sql = <<<EOD
      UPDATE tb_ne_mall_product_${target}_registration
         SET registration_flg = 0
       WHERE ne_mall_id = :neMallId AND registration_flg <> 0
EOD;
    $dbMain = $this->getDb('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * 楽天/dl-item.csv作成。
   *
   * データは対象店舗の tb_rakuten~item_dlより取得するが、
   * このテーブルは日次の深夜バッチで、楽天CSV出力で更新されている。
   */
  private function createRakutenItemCsv()
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    $this->currentCsv = 'dl-item';
    $logTitle = $this->currentCsv . '.csv出力';

    if (!$this->isOnlyDiff) {
      $this->removeRegistrationFlg('main');
    }

    $stmt = $this->findRakutenItemList();
    if (!$stmt->rowCount()) {
      $logger->info(
        "[{$this->commandName}] {$this->currentCsv}.csv: 件数が0のためファイルは作成しませんでした。"
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, '0件のため出力なしで終了'));
      return;
    }

    $fs = new FileSystem();
    $webCsvDir = $this->getFileUtil()->getWebCsvDir();
    $this->exportPath = "{$webCsvDir}/NextEngine/MallProduct/{$this->shop}/ne_mall_product_{$this->now}";
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }
    $filePath = sprintf('%s/%s_%s.csv', $this->exportPath, $this->currentCsv, $this->now);
    $fp = fopen($filePath, 'wb');

    $headers = [
      'コントロールカラム',
      '商品管理番号（商品URL）',
      '商品番号',
      '商品画像URL',
      '在庫タイプ',
      '在庫数表示',
    ];
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $header = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers));
    $header .= "\r\n";
    $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
    fputs($fp, $header);

    $num = 0;
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $line = $stringUtil->convertArrayToCsvLine($row, $headers, []);
      $line .= "\r\n";
      $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');
      fputs($fp, $line);
      $num++;
    }
    fclose($fp);

    $logger->info("[{$this->commandName}] {$this->currentCsv}.csv: $num 件");

    if ($this->doUpload) {
      $this->currentFileType = ExportCsvNextEngineUploadMallProductCommand::FILE_TYPE_RAKUTEN_ITEM;
      $this->upload($filePath);
    }
  }

  private function findRakutenItemList()
  {
    $addJoin = '';
    $addWhere = '';
    if ($this->isOnlyDiff) {
      $addJoin = <<<EOD
        LEFT JOIN tb_ne_mall_product_main_registration mall
          ON r.`商品管理番号（商品URL）` = mall.daihyo_syohin_code
          AND mall.ne_mall_id = :neMallId
EOD;
      $addWhere = 'AND (mall.id IS NULL OR mall.registration_flg = 0)';
    }

    $sql = <<<EOD
      SELECT
        r.コントロールカラム,
        r.商品管理番号（商品URL）,
        r.商品番号,
        r.商品画像URL,
        r.在庫タイプ,
        r.在庫数表示
      FROM
        {$this->tableItemDl} r
        JOIN tb_mainproducts_cal cal
          ON r.`商品管理番号（商品URL）` = cal.daihyo_syohin_code
        {$addJoin}
      WHERE
        cal.deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially)
        {$addWhere}
EOD;
    $dbMain = $this->getDb('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    if ($this->isOnlyDiff) {
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt;
  }

  /**
   * 楽天/dl-select.csv作成。
   *
   * データは対象店舗の tb_rakuten~select_dlより取得するが、
   * このテーブルは日次の深夜バッチで、楽天CSV出力で更新されている。
   * 選択肢タイプsは不要、iのみ出力。
   * 
   * select.csvは、分割不可、必ず一括全件アップロード。分割すると最後のファイル分しか取り込まれない。
   * https://manual.next-engine.net/main/stock/stk_settei-unyou/zaiko_rakuten/5538/
   * このため、オプション設定に関わらず、常時全件出力する。
   * ただし連続実行など、1データも対象がない場合は何も出力しない。このため、差分管理は引き続き行う。
   */
  private function createRakutenSelectCsv()
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    $this->currentCsv = 'dl-select';
    $logTitle = $this->currentCsv . '.csv出力';

    if (!$this->isOnlyDiff) {
      $this->removeRegistrationFlg('sku');
    }

    $cnt = $this->countRakutenSelectList();
    if (!$cnt) {
      $logger->info(
        "[{$this->commandName}] {$this->currentCsv}.csv: 件数が0のためファイルは作成しませんでした。"
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, '0件のため出力なしで終了'));
      return;
    }

    $fs = new FileSystem();
    $webCsvDir = $this->getFileUtil()->getWebCsvDir();
    $this->exportPath = "{$webCsvDir}/NextEngine/MallProduct/{$this->shop}/ne_mall_product_{$this->now}";
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }
    $filePath = sprintf('%s/%s_%s.csv', $this->exportPath, $this->currentCsv, $this->now);
    $fp = fopen($filePath, 'wb');

    $headers = [
      '項目選択肢用コントロールカラム',
      '商品管理番号（商品URL）',
      '選択肢タイプ',
      '項目選択肢項目名',
      '項目選択肢',
      '項目選択肢別在庫用横軸選択肢',
      '項目選択肢別在庫用横軸選択肢子番号',
      '項目選択肢別在庫用縦軸選択肢',
      '項目選択肢別在庫用縦軸選択肢子番号',
      '項目選択肢別在庫用取り寄せ可能表示',
      '項目選択肢別在庫用在庫数',
      '在庫戻しフラグ',
      '在庫切れ時の注文受付',
      '在庫あり時納期管理番号',
      '在庫切れ時納期管理番号',
      'タグID',
      '画像URL',
      '項目選択肢選択必須',
    ];
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $header = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers));
    $header .= "\r\n";
    $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
    fputs($fp, $header);
    $stmt = $this->findRakutenSelectList(); // データ取得
    $num = 0;
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $line = $stringUtil->convertArrayToCsvLine($row, $headers, []);
      $line .= "\r\n";
      $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');
      fputs($fp, $line);
      $num++;
    }
    fclose($fp);

    $logger->info("[{$this->commandName}] {$this->currentCsv}.csv: $num 件");

    if ($this->doUpload) {
      $this->currentFileType = ExportCsvNextEngineUploadMallProductCommand::FILE_TYPE_RAKUTEN_SELECT;
      $this->upload($filePath);
    }
  }
  
  /**
   * 差分更新の場合、出力対象の件数をチェックする。
   * @return 出力対象の件数
   */
  private function countRakutenSelectList()
  {
    $sql = <<<EOD
      SELECT
        count(*)
      FROM
        {$this->tableSelectDl} r
        INNER JOIN tb_mainproducts_cal cal
          ON r.`商品管理番号（商品URL）` = cal.daihyo_syohin_code
        INNER JOIN tb_productchoiceitems pci
          ON r.`商品管理番号（商品URL）` = pci.daihyo_syohin_code
          AND r.項目選択肢別在庫用横軸選択肢子番号 = pci.colcode
          AND r.項目選択肢別在庫用縦軸選択肢子番号 = pci.rowcode
        LEFT JOIN tb_ne_mall_product_sku_registration mall
          ON pci.ne_syohin_syohin_code = mall.ne_syohin_syohin_code
          AND mall.ne_mall_id = :neMallId
      WHERE
        cal.deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially)
        AND r.選択肢タイプ = 'i'
        AND (mall.id IS NULL OR mall.registration_flg = 0)
      ORDER BY r.id
EOD;
    $dbMain = $this->getDb('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
    $stmt->execute();
    return intval($stmt->fetchColumn(0));
  }
  
  /**
   * 楽天は出力する場合は全件なので、ここは差分状況は無関係
   * @return unknown
   */
  private function findRakutenSelectList()
  {
    $sql = <<<EOD
      SELECT
        r.項目選択肢用コントロールカラム,
        r.商品管理番号（商品URL）,
        r.選択肢タイプ,
        r.`Select/Checkbox用項目名` AS 項目選択肢項目名,
        r.`Select/Checkbox用選択肢` AS 項目選択肢,
        r.項目選択肢別在庫用横軸選択肢,
        r.項目選択肢別在庫用横軸選択肢子番号,
        r.項目選択肢別在庫用縦軸選択肢,
        r.項目選択肢別在庫用縦軸選択肢子番号,
        r.項目選択肢別在庫用取り寄せ可能表示,
        r.項目選択肢別在庫用在庫数,
        r.在庫戻しフラグ,
        r.在庫切れ時の注文受付,
        r.在庫あり時納期管理番号,
        r.在庫切れ時納期管理番号,
        r.タグID,
        r.画像URL,
        r.項目選択肢選択必須
      FROM
        {$this->tableSelectDl} r
        INNER JOIN tb_mainproducts_cal cal
          ON r.`商品管理番号（商品URL）` = cal.daihyo_syohin_code
      WHERE
        cal.deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially)
        AND r.選択肢タイプ = 'i'
      ORDER BY r.id
EOD;
    $dbMain = $this->getDb('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt;
  }

  /**
   * Yahoo/quantity.csv作成。
   *
   * 対象は下記全てを満たす商品
   * 1. 対象店舗の tb_yahoo~information で出品フラグがON
   * 2. 権利侵害・アダルト審査が「ブラック」「グレー」「未審査」ではない
   * 3. 販売中、または、販売終了後3年以内
   */
  private function createYahooQuantityCsv()
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    $this->currentCsv = 'quantity';
    $logTitle = $this->currentCsv . '.csv出力';

    if (!$this->isOnlyDiff) {
      $this->removeRegistrationFlg('sku');
    }

    $stmt = $this->findYahooQuantityList();
    if (!$stmt->rowCount()) {
      $logger->info(
        "[{$this->commandName}] {$this->currentCsv}.csv: 件数が0のためファイルは作成しませんでした。"
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, '0件のため出力なしで終了'));
      return;
    }

    $fs = new FileSystem();
    $webCsvDir = $this->getFileUtil()->getWebCsvDir();
    $this->exportPath = "{$webCsvDir}/NextEngine/MallProduct/{$this->shop}";
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }
    // CSVファイル名フォーマット
    $filePathFormat = '%s/%s%s_%03d.csv';

    $headers = [
      'code',
      'sub-code',
      'quantity',
      'allow-overdraft',
      'stock-close',
    ];

    // NEアップロードのための情報
    $this->currentFileType = ExportCsvNextEngineUploadMallProductCommand::FILE_TYPE_YAHOO_QUANTITY;

    // CSV分割出力およびNEアップロード
    $this->splitCsvOutputAndUpload($stmt, $filePathFormat, $headers, self::YAHOO_QUANTITY_CSV_MAX_NUM);
  }

  private function findYahooQuantityList()
  {
    $addJoin = '';
    $addWhere = '';
    if ($this->isOnlyDiff) {
      $addJoin = <<<EOD
        LEFT JOIN tb_ne_mall_product_sku_registration mall
          ON pci.ne_syohin_syohin_code = mall.ne_syohin_syohin_code
          AND mall.ne_mall_id = :neMallId
EOD;
      $addWhere = 'AND (mall.id IS NULL OR mall.registration_flg = 0)';
    }

    // NEのモール商品一括登録の為のデータで、商品登録や在庫登録は行われないので、
    // quantity, allow-overdraft, stock-close は、0固定で良い。
    $sql = <<<EOD
      SELECT
        m.daihyo_syohin_code code,
        pci.ne_syohin_syohin_code `sub-code`,
        0 quantity,
        0 `allow-overdraft`,
        0 `stock-close`
      FROM
        tb_mainproducts m
        INNER JOIN {$this->tableInformation} i
          ON m.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal cal
          ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_productchoiceitems pci
          ON m.daihyo_syohin_code = pci.daihyo_syohin_code
        {$addJoin}
      WHERE
        IFNULL(m.YAHOOディレクトリID, '') <> ''
        AND cal.deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially)
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND i.registration_flg <> 0
        AND cal.adult_check_status NOT IN ( :adultCheckStatusBlack, :adultCheckStatusGray , :adultCheckStatusNone )
        AND (
          cal.endofavailability IS NULL
          OR (cal.endofavailability is NOT NULL AND cal.endofavailability >= :exportLimitDate)
        )
        {$addWhere}
EOD;
    $exportLimit = new \DateTime();
    $exportLimit->modify('-3 year');
    $dbMain = $this->getDb('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':adultCheckStatusBlack', TbMainproductsCal::ADULT_CHECK_STATUS_BLACK, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    $stmt->bindValue(':exportLimitDate', $exportLimit->format('Y-m-d 00:00:00'), \PDO::PARAM_STR);
    if ($this->isOnlyDiff) {
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt;
  }

  /**
   * Wowma/stock.csv作成。
   *
   * 対象は下記全てを満たす商品
   * 1. tb_biddersinfomation で出品フラグがON
   * 2. 権利侵害・アダルト審査が「未審査」ではない
   * 3. 販売中、または、販売終了後10日以内
   * 4. 0円商品でない
   */
  private function createWowmaStockCsv()
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    $this->currentCsv = 'stock';
    $logTitle = $this->currentCsv . '.csv出力';

    if (!$this->isOnlyDiff) {
      $this->removeRegistrationFlg('sku');
    }

    $stmt = $this->findWowmaStockList();
    if (!$stmt->rowCount()) {
      $logger->info(
        "[{$this->commandName}] {$this->currentCsv}.csv: 件数が0のためファイルは作成しませんでした。"
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, '0件のため出力なしで終了'));
      return;
    }

    $fs = new FileSystem();
    $webCsvDir = $this->getFileUtil()->getWebCsvDir();
    $this->exportPath = "{$webCsvDir}/NextEngine/MallProduct/{$this->shop}";
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }
    // CSVファイル名フォーマット
    $filePathFormat = '%s/%s%s_%03d.csv';

    $headers = [
      'ctrlCol',
      'lotNumber',
      'itemCode',
      'stockSegment',
      'stockCount',
      'choicesStockHorizontalName',
      'choicesStockHorizontalCode',
      'choicesStockHorizontalSeq',
      'choicesStockVerticalName',
      'choicesStockVerticalCode',
      'choicesStockVerticalSeq',
      'choicesStockCount',
      'choicesStockShippingDayId',
      'choicesStockShippingDayDispTxt',
      'choicesStockImageUrl',
      'choicesStockColorSegment',
    ];

    // NEアップロードのための情報
    $this->currentFileType = ExportCsvNextEngineUploadMallProductCommand::FILE_TYPE_WOWMA_STOCK;

    // CSV分割出力およびNEアップロード
    $this->splitCsvOutputAndUpload($stmt, $filePathFormat, $headers, self::WOWMA_STOCK_CSV_MAX_NUM);
  }

  private function findWowmaStockList()
  {
    $addJoin = '';
    $addWhere = '';
    if ($this->isOnlyDiff) {
      $addJoin = <<<EOD
        LEFT JOIN tb_ne_mall_product_sku_registration mall
          ON pci.ne_syohin_syohin_code = mall.ne_syohin_syohin_code
          AND mall.ne_mall_id = :neMallId
EOD;
      $addWhere = 'AND (mall.id IS NULL OR mall.registration_flg = 0)';
    }

    // NEのモール商品一括登録の為のデータで、商品登録や在庫登録は行われないので、
    // choicesStockHorizontalSeq, choicesStockVerticalSeq... 等々商品コード以外は、
    // 無理に取得しようとせず固定値とする。
    $sql = <<<EOD
      SELECT
        '' ctrlCol,
        l.lotNumber lotNumber,
        pci.daihyo_syohin_code itemCode,
        '2' stockSegment,
        '' stockCount,
        pci.colname choicesStockHorizontalName,
        pci.colcode choicesStockHorizontalCode,
        '1' choicesStockHorizontalSeq,
        pci.rowname choicesStockVerticalName,
        pci.rowcode choicesStockVerticalCode,
        '1' choicesStockVerticalSeq,
        '0' choicesStockCount,
        '1' choicesStockShippingDayId,
        '' choicesStockShippingDayDispTxt,
        '' choicesStockImageUrl,
        '' choicesStockColorSegment
      FROM
        tb_mainproducts m
        INNER JOIN {$this->tableInformation} i
          ON m.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal cal
          ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN tb_productchoiceitems pci
          ON m.daihyo_syohin_code = pci.daihyo_syohin_code
        INNER JOIN tb_wowma_lot_number l
          ON m.daihyo_syohin_code = l.itemCode
        {$addJoin}
      WHERE
        cal.deliverycode_pre <> :deliveryCodeTemporary
        AND cal.deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially)
        AND (
          cal.endofavailability > DATE_ADD(CURRENT_DATE, INTERVAL - 10 DAY)
          OR cal.endofavailability IS NULL
          OR (
            i.last_deliverycode IN (:deliveryCodeReady, :deliveryCodeReadyPartially, :deliveryCodePurchaseOnOrder)
            AND cal.deliverycode_pre = :deliveryCodeReadyFinished
          )
        )
        AND i.registration_flg <> 0
        AND cal.adult_check_status <> :adultCheckStatusNone
        AND COALESCE(i.`baika_tanka`, 0) > 0
        {$addWhere}
EOD;
    $dbMain = $this->getDb('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED, \PDO::PARAM_INT);
    $stmt->bindValue(':adultCheckStatusNone', TbMainproductsCal::ADULT_CHECK_STATUS_NONE, \PDO::PARAM_STR);
    if ($this->isOnlyDiff) {
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt;
  }

  private function upload($filePath)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $logger->info($this->currentCsv . 'アップロード処理。DB更新。');

    $job = new NextEngineUploadJob();
    $job->queue = 'neUpload'; // キュー名
    $job->args = [
      'command' => NextEngineUploadJob::COMMAND_KEY_NE_UPLOAD_MALL_PRODUCTS,
      'shop' => $this->shop,
      'filePath' => $filePath,
      'fileType' => $this->currentFileType,
      'account' => $this->account ? $this->account->getId() : null,
      'targetEnv' => $this->targetEnv === 'prod' ? 'prod' : 'test',
    ];

    $resque = $this->getResque();
    $resque->enqueue($job);
    $commandName = BaseJob::$COMMAND_NAMES[$job->args['command']];
    $logger->info("$commandName キュー追加 ファイルパス： $filePath");
  }

  private function validate(InputInterface $input)
  {
    if (!in_array($input->getOption('shop'), self::SHOP_LIST, true)) {
      throw new BusinessException('対象店舗は、' . implode(', ', self::SHOP_LIST) . 'から指定してください [' . $input->getOption('shop') . ']');
    }
  }

  /**
   * PPM（ﾎﾟﾝﾊﾟﾚﾓｰﾙ）/item_{YmdHis}.csv作成。
   *
   * データは tb_ppm_item_dlより取得するが、
   * このテーブルは日次の深夜バッチで、PPM CSV出力で更新されている。
   */
  private function createPpmItemCsv()
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    $this->currentCsv = 'item';
    $logTitle = $this->currentCsv . '.csv出力';

    if (!$this->isOnlyDiff) {
      $this->removeRegistrationFlg('main');
    }

    // CSV出力するデータを取得
    $stmt = $this->findPpmItemList();
    if (!$stmt->rowCount()) {
      $logger->info(
        "[{$this->commandName}] {$this->currentCsv}.csv: 件数が0のためファイルは作成しませんでした。"
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, '0件のため出力なしで終了'));
      return;
    }

    // 出力ディレクトリの確認
    $fs = new FileSystem();
    $webCsvDir = $this->getFileUtil()->getWebCsvDir();
    $this->exportPath = "{$webCsvDir}/NextEngine/MallProduct/{$this->shop}/ne_mall_product_{$this->now}";
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }
    // CSVファイル名フォーマット
    // WEB_CSV/NextEngine/MallProduct/ppm/ne_mall_product_20221005103640/item_20221005103645_001.csv
    // パラメータ(%s)の各値は splitCsvOutputAndUpload関数 で与えられる
    $filePathFormat = '%s/%s_%s_%03d.csv';

    $headers = [
      'コントロールカラム',
      '商品管理ID（商品URL）',
      '商品ID',
      '商品名',
      '在庫タイプ',
      '在庫数',
      '在庫表示',
      'SKU横軸項目名',
      'SKU縦軸項目名',
      'SKU在庫用残り表示閾値'
    ];

    // NEアップロードのための情報
    $this->currentFileType = ExportCsvNextEngineUploadMallProductCommand::FILE_TYPE_PPM_ITEM;

    // CSV分割出力およびNEアップロード
    $this->splitCsvOutputAndUpload($stmt, $filePathFormat, $headers, self::PPM_ITEM_CSV_MAX_NUM);
  }

  private function findPpmItemList()
  {
    $addJoin = '';
    $addWhere = '';
    if ($this->isOnlyDiff) {
      $addJoin = <<<EOD
        LEFT JOIN tb_ne_mall_product_main_registration mall
          ON r.`商品管理ID（商品URL）` = mall.daihyo_syohin_code
          AND mall.ne_mall_id = :neMallId
EOD;
      $addWhere = 'AND (mall.id IS NULL OR mall.registration_flg = 0)';
    }

    $sql = <<<EOD
      SELECT
        '' コントロールカラム,
        r.商品管理ID（商品URL）,
        r.商品ID,
        r.商品名,
        r.在庫タイプ,
        r.在庫数,
        r.在庫表示,
        r.SKU横軸項目名,
        r.SKU縦軸項目名,
        r.SKU在庫用残り表示閾値
      FROM
        {$this->tableItemDl} r
        JOIN tb_mainproducts_cal cal
          ON r.`商品管理ID（商品URL）` = cal.daihyo_syohin_code
        {$addJoin}
      WHERE cal.deliverycode_pre IN (
            :deliveryCodeReady
          , :deliveryCodeReadyPartially
          , :deliveryCodePurchaseOnOrder
        )
        AND (
          cal.adult_check_status IN (
              :adultCheckStatusWhite
            , :adultCheckStatusGray
          )
        )
        {$addWhere}
EOD;
    $dbMain = $this->getDb('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->bindValue(':adultCheckStatusWhite', TbMainproductsCal::ADULT_CHECK_STATUS_WHITE, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    if ($this->isOnlyDiff) {
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt;
  }

  /**
   * PPM（ﾎﾟﾝﾊﾟﾚﾓｰﾙ）/option_{YmdHis}.csv作成。
   *
   * データは tb_ppm_select_dlより取得するが、
   * このテーブルは日次の深夜バッチで、PPM CSV出力で更新されている。
   * PPMの選択肢タイプ定義（sを登録する）
   *  s: SKU在庫設定
   *  o: 購入オプション
   */
  private function createPpmSelectCsv()
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    $this->currentCsv = 'option';
    $logTitle = $this->currentCsv . '.csv出力';

    if (!$this->isOnlyDiff) {
      $this->removeRegistrationFlg('sku');
    }

    // CSV出力するデータを取得
    $stmt = $this->findPpmSelectList();
    if (!$stmt->rowCount()) {
      $logger->info(
        "[{$this->commandName}] {$this->currentCsv}.csv: 件数が0のためファイルは作成しませんでした。"
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, '0件のため出力なしで終了'));
      return;
    }

    // 出力ディレクトリの確認
    $fs = new FileSystem();
    $webCsvDir = $this->getFileUtil()->getWebCsvDir();
    $this->exportPath = "{$webCsvDir}/NextEngine/MallProduct/{$this->shop}/ne_mall_product_{$this->now}";
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }
    // CSVファイル名フォーマット
    // WEB_CSV/NextEngine/MallProduct/ppm/ne_mall_product_20221005103640/option_20221005103645_001.csv
    // パラメータ(%s)の各値は splitCsvOutputAndUpload関数 で与えられる
    $filePathFormat = '%s/%s_%s_%03d.csv';

    $headers = [
      'コントロールカラム',
      '商品管理ID（商品URL）',
      '選択肢タイプ',
      '購入オプション名',
      'オプション項目名',
      'SKU横軸項目ID',
      'SKU横軸項目名',
      'SKU縦軸項目ID',
      'SKU縦軸項目名',
      'SKU在庫数',
    ];

    // NEアップロードのための情報
    $this->currentFileType = ExportCsvNextEngineUploadMallProductCommand::FILE_TYPE_PPM_SELECT;

    // CSV分割出力およびNEアップロード
    $this->splitCsvOutputAndUpload($stmt, $filePathFormat, $headers, self::PPM_OPTION_CSV_MAX_NUM);
  }

  private function findPpmSelectList()
  {
    $addJoin = '';
    $addWhere = '';
    if ($this->isOnlyDiff) {
      $addJoin = <<<EOD
        INNER JOIN tb_productchoiceitems pci
          ON r.`商品管理ID（商品URL）` = pci.daihyo_syohin_code 
          AND r.SKU横軸項目ID = pci.colcode
          AND r.SKU縦軸項目ID = pci.rowcode
        LEFT JOIN tb_ne_mall_product_sku_registration mall
          ON pci.ne_syohin_syohin_code = mall.ne_syohin_syohin_code
          AND mall.ne_mall_id = :neMallId
EOD;
      $addWhere = 'AND (mall.id IS NULL OR mall.registration_flg = 0)';
    }

    $sql = <<<EOD
      SELECT
        '' コントロールカラム,
        r.商品管理ID（商品URL）,
        r.選択肢タイプ,
        '' 購入オプション名,
        '' オプション項目名,
        r.SKU横軸項目ID,
        r.SKU横軸項目名,
        r.SKU縦軸項目ID,
        r.SKU縦軸項目名,
        r.SKU在庫数
      FROM
        {$this->tableSelectDl} r
        INNER JOIN tb_mainproducts_cal cal
          ON r.`商品管理ID（商品URL）` = cal.daihyo_syohin_code
        {$addJoin}
      WHERE cal.deliverycode_pre IN (
            :deliveryCodeReady
          , :deliveryCodeReadyPartially
          , :deliveryCodePurchaseOnOrder
        )
        AND (
          cal.adult_check_status IN (
              :adultCheckStatusWhite
            , :adultCheckStatusGray
          )
        )
        AND r.選択肢タイプ = 's'
        {$addWhere}
      ORDER BY r.id
EOD;
    $dbMain = $this->getDb('main');
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->bindValue(':adultCheckStatusWhite', TbMainproductsCal::ADULT_CHECK_STATUS_WHITE, \PDO::PARAM_STR);
    $stmt->bindValue(':adultCheckStatusGray', TbMainproductsCal::ADULT_CHECK_STATUS_GRAY, \PDO::PARAM_STR);
    if ($this->isOnlyDiff) {
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt;
  }

  /**
   * CSV分割出力
   * 分割する行数は店舗により異なる。self::*_CSV_MAX_NUM を参照のこと。
   *
   * @param mixed $stmt
   * @param string $filePathFormat
   * @param array $headers
   * @param integer $maxNum
   */
  private function splitCsvOutputAndUpload($stmt, $filePathFormat, $headers, $maxNum)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // Yahooのみヘッダにダブルクォーテーションを使用
    if($this->currentFileType === ExportCsvNextEngineUploadMallProductCommand::FILE_TYPE_YAHOO_QUANTITY){
        $header = $stringUtil->convertArrayToCsvLine($headers);
    }else{
        $header = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers));
    }
    $header .= "\r\n";
    $header = mb_convert_encoding($header, 'SJIS-WIN', 'UTF-8');
    
    $num = 0;
    $count = 0;
    $fileIndex = 1;
    $filePath = '';
    $fp = null;
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      if (!isset($fp) || !$fp) {
        $filePath = sprintf(
          $filePathFormat,
          $this->exportPath,
          $this->currentCsv,
          $this->now,
          $fileIndex++
        );
        $fp = fopen($filePath, 'wb');
        fputs($fp, $header);
      }
      // PPMのみデータにダブルクォーテーションを使わない
      if($this->currentFileType === ExportCsvNextEngineUploadMallProductCommand::FILE_TYPE_PPM_ITEM ||
        $this->currentFileType === ExportCsvNextEngineUploadMallProductCommand::FILE_TYPE_PPM_SELECT){
        $line = $stringUtil->convertArrayToCsvLine($row, [], array_keys($headers));
      }else{
        $line = $stringUtil->convertArrayToCsvLine($row, $headers, []);
      }
      $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8') . "\r\n";
      fputs($fp, $line);
      $num++;
      $count++;
      if ($count >= $maxNum) {
        fclose($fp);
        if ($this->doUpload) {
          $this->upload($filePath);
        }
        unset($fp);
        $count = 0;
      }
    }
    if (isset($fp) && $fp) {
      fclose($fp);
      if ($this->doUpload) {
        $this->upload($filePath);
      }
    }

    $logger->info("[{$this->commandName}] {$this->currentCsv}.csv: 合計 $num 件");
  }
}
