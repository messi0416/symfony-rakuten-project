<?php

namespace BatchBundle\Command;

use BatchBundle\Command\ExportCsvNextEngineMallProductCommand;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Exception\BusinessException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * NextEngineモール商品CSVアップロード。
 *
 * モール商品CSVを、スクレイピングを利用してNextEngineにアップロードする
 */
class ExportCsvNextEngineUploadMallProductCommand extends PlusnaoBaseCommand
{  
  private $neMallId; // NE側店舗コード
  private $filePath; // アップロードファイルのフルパス
  private $fileType; // ファイルタイプ
  private $targetCsv; // 対象CSV(ログ用)

  private $targetNum = 0; // 対象件数

  const FILE_TYPE_RAKUTEN_ITEM = '1';
  const FILE_TYPE_RAKUTEN_SELECT = '2';
  const FILE_TYPE_YAHOO_QUANTITY = '3';
  const FILE_TYPE_WOWMA_STOCK = '4';
  const FILE_TYPE_PPM_ITEM = '5';
  const FILE_TYPE_PPM_SELECT = '6';

  const FILE_TYPE_LIST = [
    self::FILE_TYPE_RAKUTEN_ITEM => '楽天/dl-item',
    self::FILE_TYPE_RAKUTEN_SELECT => '楽天/dl-select',
    self::FILE_TYPE_YAHOO_QUANTITY => 'Yahoo/quantity',
    self::FILE_TYPE_WOWMA_STOCK => 'Wowma/stock',
    self::FILE_TYPE_PPM_ITEM => 'PPM/item',
    self::FILE_TYPE_PPM_SELECT => 'PPM/option',
  ];

  const TARGET_TABLE = [
    'main' => [
      self::FILE_TYPE_RAKUTEN_ITEM,
      self::FILE_TYPE_PPM_ITEM,
    ],
    'sku' => [
      self::FILE_TYPE_RAKUTEN_SELECT,
      self::FILE_TYPE_YAHOO_QUANTITY,
      self::FILE_TYPE_WOWMA_STOCK,
      self::FILE_TYPE_PPM_SELECT,
    ]
  ];

  // NE モール商品一括登録 店舗一覧 のvalue属性値
  const MALL_LIST = [
    TbShoppingMall::NE_MALL_ID_RAKUTEN => [
      self::FILE_TYPE_RAKUTEN_ITEM => '1:2:【新RMS】Plus',
      self::FILE_TYPE_RAKUTEN_SELECT => '1:13:【商品オプション】Plus',
    ],
    TbShoppingMall::NE_MALL_ID_RAKUTEN_MOTTO => [
      self::FILE_TYPE_RAKUTEN_ITEM => '31:2:【新RMS】MottoMotto',
      self::FILE_TYPE_RAKUTEN_SELECT => '31:13:【商品オプション】MottoMotto',
    ],
    TbShoppingMall::NE_MALL_ID_RAKUTEN_LAFOREST => [
      self::FILE_TYPE_RAKUTEN_ITEM => '32:2:【新RMS】La',
      self::FILE_TYPE_RAKUTEN_SELECT => '32:13:【商品オプション】La',
    ],
    TbShoppingMall::NE_MALL_ID_RAKUTEN_DOLTI => [
      self::FILE_TYPE_RAKUTEN_ITEM => '27:2:【新RMS】ドルチッシモ楽天市場店',
      self::FILE_TYPE_RAKUTEN_SELECT => '27:13:【商品オプション】ドルチッシモ楽天市場店',
    ],
    TbShoppingMall::NE_MALL_ID_RAKUTEN_GEKIPLA => [
      self::FILE_TYPE_RAKUTEN_ITEM => '35:2:【新RMS】激安プラネット楽天市場店',
      self::FILE_TYPE_RAKUTEN_SELECT => '35:13:【商品オプション】激安プラネット楽天市場店',
    ],
    TbShoppingMall::NE_MALL_ID_YAHOO => [
      self::FILE_TYPE_YAHOO_QUANTITY => '12:62:【YAHOO',
    ],
    TbShoppingMall::NE_MALL_ID_KAWA_E_MON => [
      self::FILE_TYPE_YAHOO_QUANTITY => '14:62:【YAHOO',
    ],
    TbShoppingMall::NE_MALL_ID_WOWMA => [
      self::FILE_TYPE_WOWMA_STOCK => '2:70:【Wow!manager（stock）】Plus',
    ],
    TbShoppingMall::NE_MALL_ID_PPM => [
      self::FILE_TYPE_PPM_ITEM => '16:63:【商品登録用】Plus',
      self::FILE_TYPE_PPM_SELECT => '16:64:【SKU在庫登録用】Plus',
    ],
  ];

  const MAX_UPDATE_NUM = 1000; // 1回の最大更新件数

  protected function configure()
  {
    $fileTypeKeys = [];
    $fileTypePairs = [];
    foreach (self::FILE_TYPE_LIST as $key => $value) {
      $fileTypeKeys[] = $key;
      $fileTypePairs[] = $key . ':' . $value;
    }
    $fileType = implode('|', $fileTypeKeys);
    $fileTypeDesc = implode(', ', $fileTypePairs);

    $this
      ->setName('batch:export-csv-next-engine-upload-mall-product')
      ->setDescription('NextEngineモール商品CSVアップロード')
      ->addOption('shop', null, InputOption::VALUE_OPTIONAL, '対象店舗。' . implode('|', ExportCsvNextEngineMallProductCommand::SHOP_LIST))
      ->addOption('file-path', null, InputOption::VALUE_OPTIONAL, 'アップロードファイルのフルパス', null)
      ->addOption('file-type', null, InputOption::VALUE_OPTIONAL, "ファイルタイプ。$fileType ($fileTypeDesc)", null)
      ->addOption('target-env', null, InputOption::VALUE_OPTIONAL, 'NextEngineのターゲット環境', 'test') // 危険なのでデフォルトはtest
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('queue-name', null, InputOption::VALUE_OPTIONAL,  '呼び出しキュー名', TbProcessExecuteLog::QUEUE_NAME_UNKNOWN) // このまま記載すること
    ;
  }

  /**
   * 初期化を行う。
   */
  protected function initializeProcess(InputInterface $input)
  {
    $this->commandName = 'NEモール商品CSVアップロード';
    if ($input->getOption('shop')) {
      $this->commandName .= '[' . $input->getOption('shop') . ']';
    }
    if (in_array($input->getOption('file-type'), array_keys(self::FILE_TYPE_LIST))) {
      $this->commandName .= '[' . self::FILE_TYPE_LIST[$input->getOption('file-type')] . ']';
    }
  }

  protected function doProcess(InputInterface $input, OutputInterface $output)
  {
    $this->validate($input);
    $this->filePath = $input->getOption('file-path');
    $this->fileType = $input->getOption('file-type');
    $this->targetCsv = self::FILE_TYPE_LIST[$this->fileType];
    switch ($input->getOption('shop')) {
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_RAKUTEN:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN;
        break;
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_MOTTO:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_MOTTO;
        break;
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_LAFOREST:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_LAFOREST;
        break;
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_DOLCISSIMO:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_DOLTI;
        break;
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_GEKIPLA:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_RAKUTEN_GEKIPLA;
        break;
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_YAHOO_PLUSNAO:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_YAHOO;
        break;
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_KAWAEMON:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_KAWA_E_MON;
        break;
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_WOWMA:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_WOWMA;
        break;
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_PPM:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_PPM;
        break;
    }

    /** @var WebAccessUtil $webAccessUtil */
    $webAccessUtil = $this->getWebAccessUtil();
    if ($this->account) {
      $webAccessUtil->setAccount($this->account);
    }
    $client = $webAccessUtil->getWebClient();
    $logger = $this->getLogger();

    // NEログイン
    $crawler = $webAccessUtil->neLogin($client, 'api', $input->getOption('target-env')); // 必要なら、アカウント名を追加して切り替える

    // CSVファイルアップロード
    $uri = $client->getRequest()->getUri();

    $hostName = null;
    $match = null;
    if (preg_match('!^(https.*\.next-engine\.(?:org|com))!', $uri, $match)) {
      $hostName = $match[1];
    } else {
      throw new \RuntimeException('can not move top page error');
    }

    // モール商品一括登録画面へ遷移
    $uri = $hostName . '/Usertorikomi';
    $crawler = $client->request('get', $uri);
    $csrfTokenInfo = $webAccessUtil->getNeCsrfTokenInfo($crawler);

    $status = $client->getResponse()->getStatus();
    $uri = $client->getRequest()->getUri();
    $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($client->getResponse());
    if (
      $status !== 200
      || $isInvalidAccess
      || !preg_match('!.next-engine.(?:org|com)/Usertorikomi!', $uri)
    ) {
      $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
      file_put_contents($scrapingResponseDir . '/next_engine_mall_product.html', $client->getResponse()->getContent());
      $message = $isInvalidAccess ? '不正アクセスエラー' : '';
      throw new \RuntimeException("move to csv upload page (master) error!! $message [ $status ][ $uri ]");
    }
    $logger->info('モール商品一括登録画面へ遷移成功');

    // csrfトークンをform追加するために、用意したhtmlからcrawlerを新規作成する
    $uploadHtmlCrawler = new Crawler($this -> createHtmlUploadMallProduct(), $hostName);
    $form = $uploadHtmlCrawler -> selectButton('モール商品CSVファイルをアップロード') -> form();

    $logger->info('モール商品一括登録CSV アップロード試行');

    // 件数上限があるが、ひとまずそのままアップロードとする。エラーが出た場合、ログから後々に対応。
    $form['moru'] = self::MALL_LIST[$this->neMallId][$this->fileType];
    $form['fl']->upload($this->filePath);
    $form['csrf_token'] = $csrfTokenInfo['value']; // responseそのままだとform内にcsrftokenが書き込まれていないため追記
    $crawler = $client->submit($form);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    $isInvalidAccess = $webAccessUtil->isNeInvalidAccess($response);
    $message = null;

    if (
      $status !== 200 || $isInvalidAccess || $response->getHeader('Content-Type') !== 'text/html; charset=UTF-8'
        || strpos($uri, 'Usertorikomi/input/') === false
    ) {
      $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
      file_put_contents($scrapingResponseDir . '/next_engine_upload_mall_product.tmp.html', $response->getContent());
      $errorMessage = $isInvalidAccess ? '不正アクセスエラー' : '';
      throw new \RuntimeException("アップロード完了画面に遷移しませんでした。 $errorMessage [ $status ][ $uri ][" . $response->getHeader('Content-Type') . '][' . $this->filePath . ']');
    }

    // アップロードメッセージチェック
    if (strpos($response->getContent(), basename($this->filePath).'は正常にアップロードされました') === false) {
      $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
      file_put_contents($scrapingResponseDir . '/next_engine_upload_mall_product_error.tmp.html', $response->getContent());
      throw new \RuntimeException("$this->targetCsv のアップロードに失敗しました。[ $this->filePath ]");
    } else {
      // エラー無し
      $logger->info($this->commandName . ": $this->targetCsv のアップロードが成功しました。[ $this->filePath ]");
    }

    // 登録管理テーブル更新
    if (in_array($this->fileType, self::TARGET_TABLE['main'], true)) {
      $this->createTmpNeMallProductMain();
      $this->updateNeMallProductMainRegistration();
      $logger->info($this->commandName . ': NEモール代表商品登録を更新しました。');
    }
    if (in_array($this->fileType, self::TARGET_TABLE['sku'], true)) {
      $this->createTmpNeMallProductSku();
      $this->updateNeMallProductSkuRegistration();
      $logger->info($this->commandName . ': NEモールSKU登録を更新しました。');
    }
  }

  /**
   * モール商品CSVファイルをアップロード用htmlを作成する。
   * 2023/1よりcsrf_tokenがhtml描画後、jsによってform内に追加されるようになった。
   * responseへの要素追加がcrawlerやformのfunctionで行えないため、htmlを直接作成する形で対応する
   */
  private function createHtmlUploadMallProduct()
  {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
    <title>CSV取り込み</title>
    <meta charset="utf-8">
    <base href="https://main.next-engine.com/">
    </head>
    <body>
    <form id ="F_ga7510" method='post' action= 'Usertorikomi/input/' enctype='multipart/form-data' class="form-horizontal">
        <div class="form-actions">
            <button type="button submit" class="btn btn-primary">
                モール商品CSVファイルをアップロード<i class="icon-upload-alt"></i>
            </button>
            <p id="msg" style="border:none"></p>
        </div>
      <select id="moru" class="span12"  name="moru" size="30" >
          <option value=1:2:【新RMS】Plus Nao 楽天市場店 title="店舗コード[1]　モールコード[2]">1 :【新RMS】Plus Nao 楽天市場店</option>
          <option value=1:13:【商品オプション】Plus Nao 楽天市場店 title="店舗コード[1]　モールコード[13]">1 :【商品オプション】Plus Nao 楽天市場店</option>
          <option value=2:70:【Wow!manager（stock）】Plus Nao Wowma店 title="店舗コード[2]　モールコード[70]">2 :【Wow!manager（stock）】Plus Nao Wowma店</option>
          <option value=3:34:【新MakeShop】Plus Nao 本店 title="店舗コード[3]　モールコード[34]">3 :【新MakeShop】Plus Nao 本店</option>
          <option value=3:35:【新MakeShopオプション】Plus Nao 本店 title="店舗コード[3]　モールコード[35]">3 :【新MakeShopオプション】Plus Nao 本店</option>
          <option value=4:2:【新RMS】Plus Nao フリーオーダー title="店舗コード[4]　モールコード[2]">4 :【新RMS】Plus Nao フリーオーダー</option>
          <option value=4:13:【商品オプション】Plus Nao フリーオーダー title="店舗コード[4]　モールコード[13]">4 :【商品オプション】Plus Nao フリーオーダー</option>
          <option value=6:21:【商品】I AM 1号店 title="店舗コード[6]　モールコード[21]">6 :【商品】I AM 1号店</option>
          <option value=6:68:【オプション価格】I AM 1号店 title="店舗コード[6]　モールコード[68]">6 :【オプション価格】I AM 1号店</option>
          <option value=9:12:【amazon】ヴォーグamazon店 title="店舗コード[9]　モールコード[12]">9 :【amazon】ヴォーグamazon店</option>
          <option value=11:21:【商品】Yours 1号店 title="店舗コード[11]　モールコード[21]">11 :【商品】Yours 1号店</option>
          <option value=11:68:【オプション価格】Yours 1号店 title="店舗コード[11]　モールコード[68]">11 :【オプション価格】Yours 1号店</option>
          <option value=12:10:【新YAHOO】Yahoo plusnao title="店舗コード[12]　モールコード[10]">12 :【新YAHOO】Yahoo plusnao</option>
          <option value=12:62:【YAHOO quantity.csv】Yahoo plusnao title="店舗コード[12]　モールコード[62]">12 :【YAHOO quantity.csv】Yahoo plusnao</option>
          <option value=13:46:【Qoo10 Goods】PlusNao Qoo10 Shop title="店舗コード[13]　モールコード[46]">13 :【Qoo10 Goods】PlusNao Qoo10 Shop</option>
          <option value=13:53:【Qoo10 Inventory】PlusNao Qoo10 Shop title="店舗コード[13]　モールコード[53]">13 :【Qoo10 Inventory】PlusNao Qoo10 Shop</option>
          <option value=14:10:【新YAHOO】Yahoo kawa-e-mon title="店舗コード[14]　モールコード[10]">14 :【新YAHOO】Yahoo kawa-e-mon</option>
          <option value=14:62:【YAHOO quantity.csv】Yahoo kawa-e-mon title="店舗コード[14]　モールコード[62]">14 :【YAHOO quantity.csv】Yahoo kawa-e-mon</option>
          <option value=16:63:【商品登録用】Plus Nao ポンパレモール店 title="店舗コード[16]　モールコード[63]">16 :【商品登録用】Plus Nao ポンパレモール店</option>
          <option value=16:64:【SKU在庫登録用】Plus Nao ポンパレモール店 title="店舗コード[16]　モールコード[64]">16 :【SKU在庫登録用】Plus Nao ポンパレモール店</option>
          <option value=17:33:【汎用】フリマ title="店舗コード[17]　モールコード[33]">17 :【汎用】フリマ</option>
          <option value=18:59:【SHOPLIST】SHOPLIST PlusNao title="店舗コード[18]　モールコード[59]">18 :【SHOPLIST】SHOPLIST PlusNao</option>
          <option value=19:33:【汎用】在庫引抜き用 title="店舗コード[19]　モールコード[33]">19 :【汎用】在庫引抜き用</option>
          <option value=20:10:【新YAHOO】Yahoo(おとりよせ.com） title="店舗コード[20]　モールコード[10]">20 :【新YAHOO】Yahoo(おとりよせ.com）</option>
          <option value=20:62:【YAHOO quantity.csv】Yahoo(おとりよせ.com） title="店舗コード[20]　モールコード[62]">20 :【YAHOO quantity.csv】Yahoo(おとりよせ.com）</option>
          <option value=22:33:【汎用】Club Plus Nao title="店舗コード[22]　モールコード[33]">22 :【汎用】Club Plus Nao</option>
          <option value=23:33:【汎用】Club Forest title="店舗コード[23]　モールコード[33]">23 :【汎用】Club Forest</option>
          <option value=24:36:【SuperD】SUPER DELIVERY店 title="店舗コード[24]　モールコード[36]">24 :【SuperD】SUPER DELIVERY店</option>
          <option value=25:2:【新RMS】楽天ロジ title="店舗コード[25]　モールコード[2]">25 :【新RMS】楽天ロジ</option>
          <option value=25:13:【商品オプション】楽天ロジ title="店舗コード[25]　モールコード[13]">25 :【商品オプション】楽天ロジ</option>
          <option value=26:2:【新RMS】楽天市場SHANZE.店 title="店舗コード[26]　モールコード[2]">26 :【新RMS】楽天市場SHANZE.店</option>
          <option value=26:13:【商品オプション】楽天市場SHANZE.店 title="店舗コード[26]　モールコード[13]">26 :【商品オプション】楽天市場SHANZE.店</option>
          <option value=27:2:【新RMS】ドルチッシモ楽天市場店 title="店舗コード[27]　モールコード[2]">27 :【新RMS】ドルチッシモ楽天市場店</option>
          <option value=27:13:【商品オプション】ドルチッシモ楽天市場店 title="店舗コード[27]　モールコード[13]">27 :【商品オプション】ドルチッシモ楽天市場店</option>
          <option value=28:33:【汎用】CRESTWOOD title="店舗コード[28]　モールコード[33]">28 :【汎用】CRESTWOOD</option>
          <option value=30:33:【汎用】(仮)WEBで注文、倉庫で受取 title="店舗コード[30]　モールコード[33]">30 :【汎用】(仮)WEBで注文、倉庫で受取</option>
          <option value=31:2:【新RMS】MottoMotto 楽天市場店 title="店舗コード[31]　モールコード[2]">31 :【新RMS】MottoMotto 楽天市場店</option>
          <option value=31:13:【商品オプション】MottoMotto 楽天市場店 title="店舗コード[31]　モールコード[13]">31 :【商品オプション】MottoMotto 楽天市場店</option>
          <option value=32:2:【新RMS】La Forest 楽天市場店 title="店舗コード[32]　モールコード[2]">32 :【新RMS】La Forest 楽天市場店</option>
          <option value=32:13:【商品オプション】La Forest 楽天市場店 title="店舗コード[32]　モールコード[13]">32 :【商品オプション】La Forest 楽天市場店</option>
          <option value=34:33:【汎用】PlusNao(EC-CUBE) title="店舗コード[34]　モールコード[33]">34 :【汎用】PlusNao(EC-CUBE)</option>
          <option value=35:2:【新RMS】激安プラネット楽天市場店 title="店舗コード[35]　モールコード[2]">35 :【新RMS】激安プラネット楽天市場店</option>
          <option value=35:13:【商品オプション】激安プラネット楽天市場店 title="店舗コード[35]　モールコード[13]">35 :【商品オプション】激安プラネット楽天市場店</option>
        </select>
      <input type="file" id="fl" name="fl">
      <input type="hidden" name="csrf_token" value="1234567890">
    </form>
    </body>
    </html>
HTML;
    
    return $html;
  }


  private function createTmpNeMallProductMain()
  {
    $dbMain = $this->getDb('main');
    $dbTmpName = $this->getDb('tmp')->getDatabase();

    $sql = <<<EOD
      CREATE TEMPORARY TABLE {$dbTmpName}.tmp_ne_mall_product_main (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `daihyo_syohin_code` varchar(30) NOT NULL,
        `ne_mall_id` int(11) NOT NULL,
        `shop_daihyo_syohin_code` varchar(30) NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    if ($this->fileType === self::FILE_TYPE_RAKUTEN_ITEM) {
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :filePath
        INTO TABLE {$dbTmpName}.tmp_ne_mall_product_main
        CHARACTER SET SJIS
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\\r\\n'
        IGNORE 1 LINES
        (@1, @product_url, @product_number, @4, @5, @6)
        SET
          daihyo_syohin_code = @product_url,
          ne_mall_id = :neMallId,
          shop_daihyo_syohin_code = @product_number;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':filePath', $this->filePath, \PDO::PARAM_STR);
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
      $stmt->execute();
      $this->targetNum = $stmt->rowCount();
    }

    if ($this->fileType === self::FILE_TYPE_PPM_ITEM) {
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :filePath
        INTO TABLE {$dbTmpName}.tmp_ne_mall_product_main
        CHARACTER SET SJIS
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\\r\\n'
        IGNORE 1 LINES
        (@1, @product_url, @product_id, @4, @5, @6, @7, @8, @9, @10)
        SET
          daihyo_syohin_code = @product_id,
          ne_mall_id = :neMallId,
          shop_daihyo_syohin_code = @product_url;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':filePath', $this->filePath, \PDO::PARAM_STR);
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
      $stmt->execute();
      $this->targetNum = $stmt->rowCount();
    }
  }

  private function updateNeMallProductMainRegistration()
  {
    $dbMain = $this->getDb('main');
    $dbTmpName = $this->getDb('tmp')->getDatabase();

    $offsetStart = 0;
    while ($offsetStart < $this->targetNum) {
      $sql = <<<EOD
        INSERT INTO
          tb_ne_mall_product_main_registration (
            daihyo_syohin_code,
            ne_mall_id,
            shop_daihyo_syohin_code,
            registration_flg
          )
        SELECT
          daihyo_syohin_code,
          ne_mall_id,
          shop_daihyo_syohin_code,
          1
        FROM
          {$dbTmpName}.tmp_ne_mall_product_main
        WHERE
          id > :offsetStart
          AND id <= :offsetEnd
        ON DUPLICATE KEY UPDATE
          shop_daihyo_syohin_code = VALUES(shop_daihyo_syohin_code),
          registration_flg = 1;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':offsetStart', $offsetStart, \PDO::PARAM_INT);
      $stmt->bindValue(':offsetEnd', $offsetStart + self::MAX_UPDATE_NUM, \PDO::PARAM_INT);
      $stmt->execute();

      $offsetStart += self::MAX_UPDATE_NUM;
    }
  }

  private function createTmpNeMallProductSku()
  {
    $dbMain = $this->getDb('main');
    $dbTmpName = $this->getDb('tmp')->getDatabase();

    $sql = <<<EOD
      CREATE TEMPORARY TABLE {$dbTmpName}.tmp_ne_mall_product_sku (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `ne_syohin_syohin_code` varchar(255) NOT NULL,
        `ne_mall_id` int(11) NOT NULL,
        `shop_syohin_code` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    if ($this->fileType === self::FILE_TYPE_RAKUTEN_SELECT) {
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :filePath
        INTO TABLE {$dbTmpName}.tmp_ne_mall_product_sku
        CHARACTER SET SJIS
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\\r\\n'
        IGNORE 1 LINES
        (@1, @product_url, @3, @4, @5, @6, @col_code, @8, @row_code, @10, @11, @12, @13, @14, @15, @16, @17, @18)
        SET
          ne_syohin_syohin_code = CONCAT(@product_url, @col_code, @row_code),
          ne_mall_id = :neMallId,
          shop_syohin_code = CONCAT(@product_url, @col_code, @row_code);
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':filePath', $this->filePath, \PDO::PARAM_STR);
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
      $stmt->execute();
      $this->targetNum = $stmt->rowCount();
    }

    if ($this->fileType === self::FILE_TYPE_YAHOO_QUANTITY) {
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :filePath
        INTO TABLE {$dbTmpName}.tmp_ne_mall_product_sku
        CHARACTER SET SJIS
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\\r\\n'
        IGNORE 1 LINES
        (@1, @sub_code, @3, @4, @5)
        SET
          ne_syohin_syohin_code = @sub_code,
          ne_mall_id = :neMallId,
          shop_syohin_code = @sub_code;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':filePath', $this->filePath, \PDO::PARAM_STR);
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
      $stmt->execute();
      $this->targetNum = $stmt->rowCount();
    }

    if ($this->fileType === self::FILE_TYPE_WOWMA_STOCK) {
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :filePath
        INTO TABLE {$dbTmpName}.tmp_ne_mall_product_sku
        CHARACTER SET SJIS
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\\r\\n'
        IGNORE 1 LINES
        (@1, @lotNumber, @itemCode, @4, @5, @6, @choicesStockHorizontalCode, @8, @9, @choicesStockVerticalCode, @11, @12, @13, @14, @15, @16)
        SET
          ne_syohin_syohin_code = CONCAT(@itemCode, @choicesStockHorizontalCode, @choicesStockVerticalCode),
          ne_mall_id = :neMallId,
          shop_syohin_code = CONCAT(@lotNumber, @choicesStockHorizontalCode, @choicesStockVerticalCode);
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':filePath', $this->filePath, \PDO::PARAM_STR);
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
      $stmt->execute();
      $this->targetNum = $stmt->rowCount();
    }

    if ($this->fileType === self::FILE_TYPE_PPM_SELECT) {
      $sql = <<<EOD
        LOAD DATA LOCAL INFILE :filePath
        INTO TABLE {$dbTmpName}.tmp_ne_mall_product_sku
        CHARACTER SET SJIS
        FIELDS ENCLOSED BY '"' ESCAPED BY '' TERMINATED BY ','
        LINES TERMINATED BY '\\r\\n'
        IGNORE 1 LINES
        (@1, @product_url, @3, @4, @5, @col_code, @7, @row_code, @9, @10)
        SET
          ne_syohin_syohin_code = CONCAT(@product_url, @col_code, @row_code),
          ne_mall_id = :neMallId,
          shop_syohin_code = CONCAT(@product_url, @col_code, @row_code);
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':filePath', $this->filePath, \PDO::PARAM_STR);
      $stmt->bindValue(':neMallId', $this->neMallId, \PDO::PARAM_STR);
      $stmt->execute();
      $this->targetNum = $stmt->rowCount();
    }
  }

  private function updateNeMallProductSkuRegistration()
  {
    $dbMain = $this->getDb('main');
    $dbTmpName = $this->getDb('tmp')->getDatabase();

    $offsetStart = 0;
    while ($offsetStart < $this->targetNum) {
      $sql = <<<EOD
        INSERT INTO
          tb_ne_mall_product_sku_registration (
            ne_syohin_syohin_code,
            ne_mall_id,
            shop_syohin_code,
            registration_flg
          )
        SELECT
          ne_syohin_syohin_code,
          ne_mall_id,
          shop_syohin_code,
          1
        FROM
          {$dbTmpName}.tmp_ne_mall_product_sku
        WHERE
          id > :offsetStart
          AND id <= :offsetEnd
        ON DUPLICATE KEY UPDATE
          registration_flg = 1;
EOD;
      $stmt = $dbMain->prepare($sql);
      $stmt->bindValue(':offsetStart', $offsetStart, \PDO::PARAM_INT);
      $stmt->bindValue(':offsetEnd', $offsetStart + self::MAX_UPDATE_NUM, \PDO::PARAM_INT);
      $stmt->execute();

      $offsetStart += self::MAX_UPDATE_NUM;
    }
  }

  private function validate(InputInterface $input)
  {
    if (!in_array($input->getOption('shop'), ExportCsvNextEngineMallProductCommand::SHOP_LIST, true)) {
      throw new BusinessException('対象店舗は、' . implode(', ', ExportCsvNextEngineMallProductCommand::SHOP_LIST) . 'から指定してください [' . $input->getOption('shop') . ']');
    }
    if (is_null($input->getOption('file-path'))) {
      throw new BusinessException('アップロードファイルのフルパスは、必ず指定してください');
    }
    if (!file_exists($input->getOption('file-path'))) {
      throw new BusinessException('指定されたファイルが存在しません [' . $input->getOption('file-path') . ']');
    }
    if (!in_array($input->getOption('file-type'), array_keys(self::FILE_TYPE_LIST))) {
      throw new BusinessException('ファイルタイプは、' . implode(', ', array_keys(self::FILE_TYPE_LIST)) . 'の中から指定してください [' . $input->getOption('file-type') . ']');
    }
  }
}
