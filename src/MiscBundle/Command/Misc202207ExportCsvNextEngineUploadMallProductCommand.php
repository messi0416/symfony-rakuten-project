<?php

namespace MiscBundle\Command;

use BatchBundle\Command\CommandBaseTrait;
use BatchBundle\Command\ExportCsvNextEngineMallProductCommand;
use MiscBundle\Entity\TbProcessExecuteLog;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Exception\BusinessException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * NextEngineモール商品CSVアップロード。(本番テスト用 NE接続部分のみ。)
 *
 * ExportCsvNextEngineUploadMallProductCommandから引用
 * モール商品CSVを、スクレイピングを利用してNextEngineにアップロードする
 */
class Misc202207ExportCsvNextEngineUploadMallProductCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  private $neMallId; // NE側店舗コード
  private $filePath; // アップロードファイルのフルパス
  private $fileType; // ファイルタイプ
  private $targetCsv; // 対象CSV(ログ用)

  const FILE_TYPE_RAKUTEN_ITEM = '1';
  const FILE_TYPE_RAKUTEN_SELECT = '2';
  const FILE_TYPE_YAHOO_QUANTITY = '3';

  const FILE_TYPE_LIST = [
    self::FILE_TYPE_RAKUTEN_ITEM => '楽天/dl-item',
    self::FILE_TYPE_RAKUTEN_SELECT => '楽天/dl-select',
    self::FILE_TYPE_YAHOO_QUANTITY => 'Yahoo/quantity',
  ];

  const TARGET_TABLE = [
    'main' => [
      self::FILE_TYPE_RAKUTEN_ITEM,
    ],
    'sku' => [
      self::FILE_TYPE_RAKUTEN_SELECT,
      self::FILE_TYPE_YAHOO_QUANTITY,
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
    TbShoppingMall::NE_MALL_ID_YAHOO => [
      self::FILE_TYPE_YAHOO_QUANTITY => '12:62:【YAHOO',
    ],
    TbShoppingMall::NE_MALL_ID_KAWA_E_MON => [
      self::FILE_TYPE_YAHOO_QUANTITY => '14:62:【YAHOO',
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
      ->setName('misc:202207-export-csv-next-engine-upload-mall-product')
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
   * @return WebAccessUtil
   */
  protected function getWebAccessUtil()
  {
    $this->webAccessUtil = $this->getContainer()->get('misc.util.web_access');
    return $this->webAccessUtil;
  }

  /**
   * @return FileUtil
   */
  protected function getFileUtil()
  {
    if (!isset($this->fileUtil)) {
      $this->fileUtil = $this->getContainer()->get('misc.util.file');
    }
    return $this->fileUtil;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->commandName = 'NEモール商品CSVアップロード';
    if ($input->getOption('shop')) {
      $this->commandName .= '[' . $input->getOption('shop') . ']';
    }
    if (in_array($input->getOption('file-type'), array_keys(self::FILE_TYPE_LIST))) {
      $this->commandName .= '[' . self::FILE_TYPE_LIST[$input->getOption('file-type')] . ']';
    }

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
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_YAHOO_PLUSNAO:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_YAHOO;
        break;
      case ExportCsvNextEngineMallProductCommand::EXPORT_TARGET_KAWAEMON:
        $this->neMallId = TbShoppingMall::NE_MALL_ID_KAWA_E_MON;
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

    $status = $client->getResponse()->getStatus();
    $uri = $client->getRequest()->getUri();
    if (
      $status !== 200
      || !preg_match('!.next-engine.(?:org|com)/Usertorikomi!', $uri)
    ) {
      throw new \RuntimeException('move to csv upload page (master) error!! [' . $status . '][' . $uri . ']');
    }
    $logger->info('モール商品一括登録画面へ遷移成功');

    $form = $crawler->selectButton('モール商品CSVファイルをアップロード')->form();

    $logger->info('モール商品一括登録CSV アップロード試行');

    // 件数上限があるが、ひとまずそのままアップロードとする。エラーが出た場合、ログから後々に対応。
    $form['moru'] = self::MALL_LIST[$this->neMallId][$this->fileType];
    $form['fl']->upload($this->filePath);
    $crawler = $client->submit($form);

    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $client->getResponse();
    $status = $response->getStatus();
    $uri = $client->getRequest()->getUri();
    $message = null;

    if (
      $status !== 200 || $response->getHeader('Content-Type') !== 'text/html; charset=UTF-8'
      || strpos($uri, 'Usertorikomi/input/') === false
    ) {
      $scrapingResponseDir = $this->getFileUtil()->getScrapingResponseDir();
      file_put_contents($scrapingResponseDir . '/next_engine_upload_mall_product.tmp.html', $response->getContent());
      throw new \RuntimeException('アップロード完了画面に遷移しませんでした。 [' . $status . '][' . $uri . '][' . $response->getHeader('Content-Type') . '][' . $this->filePath . ']');
    }

    // アップロードメッセージ
    $messageResult = null;
    try {
      $messageResult = $crawler->filter('#temp')->text();
    } catch (\Exception $e) {
      // filterでの例外は無視
    }
    if (strpos($messageResult, '正常にアップロードされました') === false) {
      // エラー有り
      throw new \RuntimeException("$this->targetCsv のアップロードに失敗しました。[ $this->filePath ]: $messageResult");
    } else {
      // エラー無し
      $logger->info($this->commandName . ": $this->targetCsv のアップロードが成功しました。[ $this->filePath ]: $messageResult");
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