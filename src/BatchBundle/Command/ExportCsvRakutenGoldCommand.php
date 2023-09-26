<?php

namespace BatchBundle\Command;

use BatchBundle\Command\ExportCsvRakutenCommand;
use BatchBundle\MallProcess\RakutenMallProcess;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Entity\Repository\TbSettingRepository;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\StringUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * 楽天GOLD CSV出力処理
 *
 * @package BatchBundle\Command
 */
class ExportCsvRakutenGoldCommand extends PlusnaoBaseCommand
{
  private $shop; // 対象店舗。rakuten|motto|laforest|dolcissimo
  private $doUpload = true; // アップロード実行フラグ
  private $aggregateDate; // 集計期間
  private $minReviewPoint; // レビュー下限
  private $maxPetitPrice; // プチプライス価格上限

  private $exportPath; // CSV出力ディレクトリ

  private $tableCategoryList; // 楽天カテゴリリストテーブル
  private $tableCategoryDl; // 楽天category.csvのインポートテーブル
  private $tableInformation; // 店舗別のinformationテーブル

  private $currentCsv; // 現在処理中のCSV
  private $rankList = []; // 受注伝票数ランキングリスト

  const MAX_CSV_LINE = 10; // 各CSVの最大行数

  const EXPORT_TARGET_CATEGORY = 'category';
  const EXPORT_TARGET_RANKING = 'ranking';
  const EXPORT_TARGET_PETIT_PRICE = 'petitprice';

  /* キュー処理店舗一覧（本番テスト完了後に店舗を追加することを想定） */
  const ENQUEUE_SHOP_LIST = [
    ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN,
    ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO,
    ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST,
    ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO,
    ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA,
  ];

  const SHOP_LIST = [
    ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN,
    ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO,
    ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST,
    ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO,
    ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA,
  ];

  const CSV_LIST = [
    self::EXPORT_TARGET_CATEGORY,
    self::EXPORT_TARGET_RANKING,
    self::EXPORT_TARGET_PETIT_PRICE,
  ];

  const FILENAME_LIST = [
    '全商品' => 'allitemrank',
    'アクセサリー' => 'jewelry',
    'ワンピース' => 'onepiecerank',
    'オールインワン・サロペット' => 'allinonerank',
    'インナー・下着・ルームウェア' => 'innerrank',
    '靴' => 'shoesrank',
    '帽子' => 'hatrank',
    'レディースバッグ' => 'womensbagsrank',
    'ファッション小物' => 'accessoryrank',
    '時計' => 'watchrank',
    'パーティードレス' => 'partydressrank',
    '水着' => 'swimwearrank',
    'メンズ' => 'mensrank',
    'メンズバッグ' => 'mensbagsrank',
    '男女兼用' => 'unisexrank',
    'キッズ・ベビー' => 'kidsbabyrank',
    'パーティー・イベント用品' => 'eventsrank',
    'トップス' => 'topsrank',
    'アウター' => 'outerrank',
    'スポーツ・アウトドア' => 'sportsrank',
    'ボトムス' => 'bottomsrank',
    'ペットグッズ' => 'petgoodsrank',
    'その他商品' => 'otherrank',
  ];

  protected function configure()
  {
    $this
      ->setName('batch:export-csv-rakuten-gold')
      ->setDescription('楽天GOLD CSV出力処理')
      ->addOption('shop', null, InputOption::VALUE_OPTIONAL, '対象店舗。rakuten|motto|laforest|dolcissimo')
      ->addOption('csv', null, InputOption::VALUE_OPTIONAL, '対象CSVカンマ区切り。category|ranking|petitprice')
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'アップロード実行フラグ', 1)
      ->addOption('aggregate-date', null, InputOption::VALUE_OPTIONAL, '集計期間')
      ->addOption('min-review-point', null, InputOption::VALUE_OPTIONAL, 'レビュー下限')
      ->addOption('max-petit-price', null, InputOption::VALUE_OPTIONAL, 'プチプライス価格上限')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN)
      ;
  }

  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = "楽天GOLD CSV出力処理[{$input->getOption('shop')}]";
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $this->validate($input);

    $this->shopCodeForRanking = TbShoppingMall::NE_MALL_ID_RAKUTEN;

    $this->shop = $input->getOption('shop');
    $this->doUpload = (bool)$input->getOption('do-upload');
    switch ($this->shop) {
      case ExportCsvRakutenCommand::EXPORT_TARGET_RAKUTEN:
        $this->tableCategoryList = 'tb_rakuten_category_list';
        $this->tableCategoryDl = 'tb_rakutencategory_dl';
        $this->tableInformation = 'tb_rakuteninformation';
        break;
      case ExportCsvRakutenCommand::EXPORT_TARGET_MOTTO:
        $this->tableCategoryList = 'tb_rakutenmotto_category_list';
        $this->tableCategoryDl = 'tb_rakutenmotto_category_dl';
        $this->tableInformation = 'tb_rakuten_motto_information';
        $this->shopCodeForRanking .= ', ' . TbShoppingMall::NE_MALL_ID_RAKUTEN_MOTTO;
        break;
      case ExportCsvRakutenCommand::EXPORT_TARGET_LAFOREST:
        $this->tableCategoryList = 'tb_rakutenlaforest_category_list';
        $this->tableCategoryDl = 'tb_rakutenlaforest_category_dl';
        $this->tableInformation = 'tb_rakuten_laforest_information';
        $this->shopCodeForRanking .= ', ' . TbShoppingMall::NE_MALL_ID_RAKUTEN_LAFOREST;
        break;
      case ExportCsvRakutenCommand::EXPORT_TARGET_DOLCISSIMO:
        $this->tableCategoryList = 'tb_rakutendolcissimo_category_list';
        $this->tableCategoryDl = 'tb_rakutendolcissimo_category_dl';
        $this->tableInformation = 'tb_rakuten_dolcissimo_information';
        $this->shopCodeForRanking .= ', ' . TbShoppingMall::NE_MALL_ID_RAKUTEN_DOLTI;
        break;
      case ExportCsvRakutenCommand::EXPORT_TARGET_GEKIPLA:
        $this->tableCategoryList = 'tb_rakutengekipla_category_list';
        $this->tableCategoryDl = 'tb_rakutengekipla_category_dl';
        $this->tableInformation = 'tb_rakuten_gekipla_information';
        $this->shopCodeForRanking .= ', ' . TbShoppingMall::NE_MALL_ID_RAKUTEN_GEKIPLA;
      break;
    }

    /** @var TbSettingRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbSetting');
    $settings = $repo->findRakutenGoldSetting();
    $this->aggregateDate = $input->getOption('aggregate-date')
                         ?? $settings[TbSetting::KEY_RAKUTEN_GOLD_AGGREGATE_DAYS];
    $this->minReviewPoint = $input->getOption('min-review-point')
                          ?? $settings[TbSetting::KEY_RAKUTEN_GOLD_MIN_REVIEW_POINT];
    $this->maxPetitPrice = $input->getOption('max-petit-price')
                         ?? $settings[TbSetting::KEY_RAKUTEN_GOLD_MAX_PETIT_PRICE];

    $fs = new FileSystem();
    $now = (new \DateTime())->format('YmdHis');
    $this->exportPath = "{$this->getFileUtil()->getWebCsvDir()}/Rakuten/Export/{$this->shop}/gold_{$now}";
    if (!$fs->exists($this->exportPath)) {
      $fs->mkdir($this->exportPath, 0755);
    }

    $csvList = array_unique(explode(',', $input->getOption('csv')));
    foreach ($csvList as $csv) {
      $this->currentCsv = $csv;
      switch ($this->currentCsv) {
        case self::EXPORT_TARGET_CATEGORY:
          $this->createCategoryCsv();
          break;
        case self::EXPORT_TARGET_RANKING:
        case self::EXPORT_TARGET_PETIT_PRICE:
          $this->createRankingCsv();
          break;
      }
    }
  }

  private function createCategoryCsv()
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $dbMain = $this->getDb('main');

    $logTitle = 'カテゴリCSV出力';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $sql = <<<EOD
      SELECT
        distinct c.cat_code id,
        CASE
          WHEN REPLACE(REPLACE(c.表示先カテゴリ, '全商品送料無料\\\\', ''), '\\\\', ',') LIKE '%,%'
            THEN REPLACE(REPLACE(c.表示先カテゴリ, '全商品送料無料\\\\', ''), '\\\\', ',')
          ELSE CONCAT(REPLACE(c.表示先カテゴリ, '全商品送料無料\\\\', ''), ',0')
        END 'category1,category2'
      FROM
        {$this->tableCategoryList} c
        JOIN tb_rakuten_category_list rc ON c.表示先カテゴリ = rc.表示先カテゴリ
        LEFT JOIN {$this->tableCategoryDl} dl ON c.表示先カテゴリ= dl.表示先カテゴリ
        LEFT JOIN {$this->tableInformation} i
          ON dl.商品管理番号（商品URL） = i.daihyo_syohin_code AND i.warehouse_stored_flg = 0
      WHERE
        c.cat1= '全商品送料無料'
        AND c.表示F <> 0 AND rc.表示順 <> 99999
        AND (i.daihyo_syohin_code IS NOT NULL
          OR REPLACE(REPLACE(c.表示先カテゴリ, '全商品送料無料\\\\', ''), '\\\\', ',') NOT LIKE '%,%')
      ORDER BY
        rc.表示順;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    if (!$stmt->rowCount()) {
      $logger->info(
        "[{$this->commandName}] {$this->currentCsv}.csv: 件数が0のためファイルは作成しませんでした。"
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, '0件のため出力なしで終了'));
      return;
    }

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $headers = ['id', 'category1,category2'];
    $headerLine = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers));
    $headerLine .= "\r\n";

    $num = 0;
    $filePath = sprintf('%s/%s.csv', $this->exportPath, $this->currentCsv);
    $fp = fopen($filePath, 'wb');
    fputs($fp, $headerLine);

    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $line = $stringUtil->convertArrayToCsvLine($row, $headers, $headers);
      $line .= "\r\n";
      fputs($fp, $line);
      $num++;
    }
    fclose($fp);

    $logger->info("[{$this->commandName}] {$this->currentCsv}.csv: $num 件");

    if ($this->doUpload) {
      $this->upload($filePath);
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  private function findRanking()
  {
    $dbMain = $this->getDb('main');

    $sql = <<<EOD
      SELECT
        m.daihyo_syohin_code AS id,
        SUBSTRING_INDEX(dir.rakutencategories_1, '\\\\', 1) AS category,
        image.address AS img_url,
        i.楽天タイトル AS title,
        FLOOR(i.baika_tanka * :taxRate) AS price
      FROM
        tb_mainproducts m
        INNER JOIN tb_plusnaoproductdirectory dir
          ON m.NEディレクトリID = dir.NEディレクトリID
        INNER JOIN product_images image
          ON m.daihyo_syohin_code = image.daihyo_syohin_code
          AND image.code = 'p001'
        INNER JOIN {$this->tableInformation} i
          ON m.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN (
          /* 代表商品毎の受注伝票数合計 */
          /* 通常商品 */
          SELECT
            a.daihyo_syohin_code AS daihyo_syohin_code,
            COUNT(DISTINCT a.伝票番号) AS voucher_quantity
          FROM
            tb_sales_detail_analyze AS a
            LEFT JOIN tb_sales_detail_set_distribute_info s
              ON a.伝票番号 = s.voucher_number AND a.明細行 = s.line_number
          WHERE
            s.voucher_number IS NULL
            AND a.daihyo_syohin_code <> ''
            AND a.daihyo_syohin_code IS NOT NULL
            AND a.キャンセル区分 = '0'
            AND a.明細行キャンセル = '0'
            AND a.受注日 >= :fromDate
            AND a.店舗コード IN ({$this->shopCodeForRanking})
          GROUP BY
            a.daihyo_syohin_code
          UNION ALL
          /* セット商品 */
          SELECT
            pci.daihyo_syohin_code AS daihyo_syohin_code,
            COUNT(DISTINCT a.伝票番号) AS voucher_quantity
          FROM
            tb_sales_detail_analyze AS a
            INNER JOIN tb_sales_detail_set_distribute_info s
              ON a.伝票番号 = s.voucher_number AND a.明細行 = s.line_number
            INNER JOIN tb_productchoiceitems pci
              ON s.original_ne_syohin_syohin_code = pci.ne_syohin_syohin_code
          WHERE
            a.daihyo_syohin_code <> ''
            AND a.daihyo_syohin_code IS NOT NULL
            AND a.キャンセル区分 = '0'
            AND a.明細行キャンセル = '0'
            AND a.受注日 >= :fromDate
            AND a.店舗コード IN ({$this->shopCodeForRanking})
          GROUP BY
            a.daihyo_syohin_code
        ) A
          ON m.daihyo_syohin_code = A.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal cal
          ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE
        i.warehouse_stored_flg = 0
        AND cal.review_point_ave >= :minReviewPoint
      ORDER BY
        A.voucher_quantity DESC;
EOD;
    $stmt = $dbMain->prepare($sql);
    $fromDate = (new \DateTime())->modify(sprintf('-%d day', $this->aggregateDate));
    $stmt->bindValue(':taxRate', DbCommonUtil::CURRENT_TAX_RATE, \PDO::PARAM_STR);
    $stmt->bindValue(':fromDate', $fromDate->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':minReviewPoint', $this->minReviewPoint, \PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * ランキングCSV作成
   * 
   * 1. findRankingで、全売上ランキングを取得
   * 2. createNormalRankingCsvで、全商品のCSVを出力するとともに、
   *    カテゴリごとの配列（$rankByCategoryList）を作成
   * 3. createRankingCsvByCategory で、カテゴリごとの配列をそれぞれ処理
   */
  private function createRankingCsv()
  {
    if (empty($this->rankList)) {
      $this->rankList = $this->findRanking();
    }

    if ($this->currentCsv === self::EXPORT_TARGET_RANKING) {
      $this->createNormalRankingCsv();
    }
    if ($this->currentCsv === self::EXPORT_TARGET_PETIT_PRICE) {
      $this->createPetitPriceCsv();
    }
  }

  private function createNormalRankingCsv()
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    $logTitle = 'ランキングCSV出力';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    if (empty($this->rankList)) {
      $logger->info(
        "[{$this->commandName}] {$logTitle}: 件数が0のためファイルは作成しませんでした。"
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, '0件のため出力なしで終了'));
      return;
    }

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $headers = ['no', 'id', 'img_url', 'title'];
    $headerLine = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers));
    $headerLine .= "\r\n";
    $category = '全商品';
    $this->currentCsv = self::FILENAME_LIST[$category];
    $filePath = sprintf('%s/%s.csv', $this->exportPath, $this->currentCsv);
    $fp = fopen($filePath, 'wb');
    fputs($fp, $headerLine);

    $fieldList = [
      'no' => 0,
      'id' => (new \DateTime())->format('Y/n/j'),
      'img_url' => 0,
      'title' => $category,
    ];
    $line = $stringUtil->convertArrayToCsvLine($fieldList, $headers, $headers);
    $line .= "\r\n";
    fputs($fp, $line);

    $rankByCategoryList = [];
    $num = 0;
    foreach ($this->rankList as $row) {
      if ($num < self::MAX_CSV_LINE) {
        $fieldList = [
          'no' => $num + 1,
          'id' => $row['id'],
          'img_url' => $row['img_url'],
          'title' => $row['title'],
        ];
        $line = $stringUtil->convertArrayToCsvLine($fieldList, $headers, $headers);
        $line .= "\r\n";
        fputs($fp, $line);
        $num++;
      }

      // カテゴリ別ランキング作成
      $category = $row['category'] ?? 'NULL';
      if (!array_key_exists($category, $rankByCategoryList)) {
        $rankByCategoryList[$category] = [];
      }
      $rankByCategoryList[$category][] = $row;
    }
    fclose($fp);
    $logger->info("[{$this->commandName}] {$this->currentCsv}.csv: $num 件");

    if ($this->doUpload) {
      $this->upload($filePath);
    }

    // カテゴリごとのランキングCSVを生成
    $this->createRankingCsvByCategory($rankByCategoryList);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  private function createRankingCsvByCategory($list)
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    foreach ($list as $category => $item) {
      if (!array_key_exists($category, self::FILENAME_LIST)) {
        continue;
      }
      $this->currentCsv = self::FILENAME_LIST[$category];
      $headers = ['no', 'id', 'img_url', 'title'];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers));
      $headerLine .= "\r\n";
      $filePath = sprintf('%s/%s.csv', $this->exportPath, $this->currentCsv);
      $fp = fopen($filePath, 'wb');
      fputs($fp, $headerLine);

      $title = $category;
      // DBのカテゴリとCSVの1行目のタイトルが違うもののみ変換
      switch ($category) {
        case 'インナー・下着・ルームウェア':
          $title = 'インナー';
          break;
        case 'キッズ・ベビー':
          $title = 'キッズ';
          break;
        case 'オールインワン・サロペット':
          $title = 'オールインワン';
          break;
      }
      $fieldList = [
        'no' => 0,
        'id' => (new \DateTime())->format('Y/n/j'),
        'img_url' => 0,
        'title' => $title,
      ];
      $line = $stringUtil->convertArrayToCsvLine($fieldList, $headers, $headers);
      $line .= "\r\n";
      fputs($fp, $line);

      $num = 0;
      foreach ($item as $row) {
        $fieldList = [
          'no' => $num + 1,
          'id' => $row['id'],
          'img_url' => $row['img_url'],
          'title' => $row['title'],
        ];
        $line = $stringUtil->convertArrayToCsvLine($fieldList, $headers, $headers);
        $line .= "\r\n";
        fputs($fp, $line);
        $num++;
        if ($num >= self::MAX_CSV_LINE) {
          break;
        }
      }
      fclose($fp);
      $logger->info("[{$this->commandName}] {$this->currentCsv}.csv: $num 件");

      if ($this->doUpload) {
        $this->upload($filePath);
      }
    }
  }

  private function createPetitPriceCsv()
  {
    /** @var BatchLogger $logger */
    $logger = $this->getLogger();

    $logTitle = 'プチプライスCSV出力';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $petitPriceList = array_filter($this->rankList, function ($row) {
      return $row['price'] <= $this->maxPetitPrice;
    });

    if (empty($petitPriceList)) {
      $logger->info(
        "[{$this->commandName}] {$this->currentCsv}.csv: 件数が0のためファイルは作成しませんでした。"
      );
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, '0件のため出力なしで終了'));
      return;
    }

    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');
    $headers = ['no', 'id', 'img_url', 'title', 'price'];
    $headerLine = $stringUtil->convertArrayToCsvLine($headers, [], array_keys($headers));
    $headerLine .= "\r\n";

    $filePath = sprintf('%s/%s.csv', $this->exportPath, $this->currentCsv);
    $fp = fopen($filePath, 'wb');
    fputs($fp, $headerLine);

    $fieldList = [
      'no' => '0',
      'id' => (new \DateTime())->format('Y/n/j'),
      'img_url' => '0',
      'title' => '0',
      'price' => '0'
    ];
    $line = $stringUtil->convertArrayToCsvLine($fieldList, $headers, $headers);
    $line .= "\r\n";
    fputs($fp, $line);

    $num = 0;
    foreach ($petitPriceList as $row) {
      $row['no'] = $num + 1;
      $line = $stringUtil->convertArrayToCsvLine($row, $headers, $headers);
      $line .= "\r\n";
      fputs($fp, $line);
      $num++;
      if ($num >= self::MAX_CSV_LINE) {
        break;
      }
    }
    fclose($fp);

    $logger->info("[{$this->commandName}] {$this->currentCsv}.csv: $num 件");

    if ($this->doUpload) {
      $this->upload($filePath);
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  private function upload($filePath)
  {
    /** @var RakutenMallProcess $processor */
    $processor = $this->getContainer()->get('batch.mall_process.rakuten');
    $processor->enqueueUploadCsv(
      $filePath,
      "{$this->currentCsv}.csv",
      $this->getEnvironment(),
      $this->shop,
      $this->commandName,
      ($this->account ? $this->account->getId() : null),
      true
    );
  }

  private function validate(InputInterface $input)
  {
    if (is_null($input->getOption('shop'))) {
      throw new BusinessException('対象店舗は、必ず指定してください');
    }
    if (!in_array($input->getOption('shop'), self::SHOP_LIST, true)) {
      throw new BusinessException('対象店舗は' . implode(', ', self::SHOP_LIST) . 'から指定してください');
    }
    if (is_null($input->getOption('csv'))) {
      throw new BusinessException('対象CSVは、必ず指定してください');
    }
    foreach (explode(',', $input->getOption('csv')) as $csv) {
      if (!in_array($csv, self::CSV_LIST, true)) {
        throw new BusinessException('対象CSVは、category, ranking, petitprice からカンマ区切りで指定してください');
      }
    }
    if (!is_null($input->getOption('aggregate-date'))) {
      if (
        !preg_match('/^\d+$/', $input->getOption('aggregate-date'))
        || ((int)$input->getOption('aggregate-date')) < 1
      ) {
        throw new BusinessException('集計期間は、1以上の整数を指定してください');
      }
    }
    if (!is_null($input->getOption('min-review-point'))) {
      if (
        !is_numeric($input->getOption('min-review-point'))
        || ((float)$input->getOption('min-review-point')) < 0
        || ((float)$input->getOption('min-review-point')) > 5
      ) {
        throw new BusinessException('レビュー下限は、0〜5の数値を指定してください');
      }
    }
    if (!is_null($input->getOption('max-petit-price'))) {
      if (
        !preg_match('/^\d+$/', $input->getOption('max-petit-price'))
        || ((int)$input->getOption('max-petit-price')) < 1
      ) {
        throw new BusinessException('プチプライス価格上限は、1以上の整数を指定してください');
      }
    }
  }
}
