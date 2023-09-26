<?php
namespace MiscBundle\Util;

use BatchBundle\Command\ExportCsvYahooCommand;
use BatchBundle\Command\ExportCsvYahooOtoriyoseCommand;
use BatchBundle\MallProcess\AmazonMallProcess;
use BatchBundle\MallProcess\NextEngineMallProcess;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;
use MiscBundle\Entity\TbLog;
use MiscBundle\Entity\TbMainproductsCal;
use MiscBundle\Entity\TbSetting;
use MiscBundle\Entity\TbShoppingMall;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use MiscBundle\Entity\TbShippingdivision;


/**
 * 共通処理
 */
class DbCommonUtil
{
  const CURRENT_TAX_RATE_PERCENT = 10;
  const CURRENT_TAX_RATE = 1 + ( self::CURRENT_TAX_RATE_PERCENT / 100 );

  // tb_updaterecord テーブル PK
  const UPDATE_RECORD_NUMBER_STOCK = 1; // E_UPDATE_RECNO.在庫 (在庫取込)
  const UPDATE_RECORD_NUMBER_ORDER = 2; // E_UPDATE_RECNO.受注
  const UPDATE_RECORD_NUMBER_INOUT = 3; // E_UPDATE_RECNO.入出庫
  const UPDATE_RECORD_NUMBER_VIEW_RANKING = 4; // E_UPDATE_RECNO.閲覧ランキング
  const UPDATE_RECORD_NUMBER_ORDER_DETAIL = 5; // E_UPDATE_RECNO.受注明細  (受注明細取込)
  const UPDATE_RECORD_NUMBER_RAKUTEN_BANK = 6; // E_UPDATE_RECNO.楽天BANK明細
  const UPDATE_RECORD_NUMBER_RAKUTEN_REVIEW = 7; // E_UPDATE_RECNO.楽天レビュー
  // 「8」はNULLでレコードがあるため、念のためスキップ
  const UPDATE_RECORD_NUMBER_RAKUTEN_CATEGORY_FOR_SALES_RANKING  = 9; // E_UPDATE_RECNO.楽天カテゴリ_売れ筋ランキング
  const UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_CHECK                 = 10; // E_UPDATE_RECNO.商品画像チェック
  const UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_RAKUTEN        = 11; // E_UPDATE_RECNO.商品画像アップロード(楽天)
  const UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_YAHOO_PLUSNAO  = 12; // E_UPDATE_RECNO.商品画像アップロード(Yahoo:plusnao) 使用終了
  const UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_YAHOO_KAWAEMON = 13; // E_UPDATE_RECNO.商品画像アップロード(Yahoo:kawaemon) 使用終了
  const UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_PPM            = 14; // E_UPDATE_RECNO.商品画像アップロード(PPM)
  const UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_SHOPLIST       = 15; // E_UPDATE_RECNO.商品画像アップロード(SHOPLIST)

  const UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE_UPDATE_STOCK   = 16; // NextEngine在庫同期処理
  const UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE                = 17; // NextEngine CSV出力
  const UPDATE_RECORD_NUMBER_EXPORT_CSV_NEXT_ENGINE_PRODUCT_UPLOAD = 19; // NextEngine 登録・更新アップロード

  const UPDATE_RECORD_NUMBER_PRODUCT_IMAGE_UPLOAD_RAKUTEN_AMAZON = 18; // E_UPDATE_RECNO.商品画像アップロード(楽天:Amazon)

  const UPDATE_RECORD_NUMBER_NOTIFY_NON_ASSIGNED_SHORTAGE_STOCK = 21; // WEB注残未引当欠品通知処理

  const UPDATE_RECORD_NUMBER_CONVERT_MALL_ORDER_CSV_EC01 = 31; // EC-CUBE(EC01 club-plusnao.jp) 受注変換＆アップロード
  const UPDATE_RECORD_NUMBER_CONVERT_MALL_ORDER_CSV_EC02 = 32; // EC-CUBE(EC02 club-forest.shop) 受注変換＆アップロード

  const UPDATE_RECORD_NUMBER_EXPORT_CSV_RAKUTEN = 51; // E_UPDATE_RECNO.CSV出力_楽天
  const UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_RAKUTEN_KICK = 52; // E_UPDATE_RECNO.CSVダウンロード_楽天_RMSキック
  const UPDATE_RECORD_NUMBER_UPDATE_RAKUTEN_NOKI_KANRI = 53; // 楽天納期管理番号更新

  const UPDATE_RECORD_NUMBER_EXPORT_CSV_MOTTO = 56; // E_UPDATE_RECNO.CSV出力_楽天motto-motto
  const UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_MOTTO_KICK = 57; // E_UPDATE_RECNO.CSVダウンロード_楽天motto-motto_RMSキック

  const UPDATE_RECORD_NUMBER_WEB_CHECK_NETSEA = 61; // E_UPDATE_RECNO.WEBチェック_NETSEA
  const UPDATE_RECORD_NUMBER_WEB_CHECK_SUPER_DELIVERY = 62; // E_UPDATE_RECNO.WEBチェック_SUPER_DELIVERY
  const UPDATE_RECORD_NUMBER_WEB_CHECK_ALIBABA = 63; // E_UPDATE_RECNO.WEBチェック_阿里巴巴

  const UPDATE_RECORD_NUMBER_ORDER_DETAIL_INCREMENTAL_UPDATE = 71; // 受注明細取込（差分更新） ※NextEngine APIによる差分取得
  const UPDATE_RECORD_NUMBER_NE_SHOP_LIST = 72; // NextEngine店舗一覧 更新
  const UPDATE_RECORD_NUMBER_MOTTO_REVIEW = 73; // E_UPDATE_RECNO.楽天mottoレビュー

  const UPDATE_RECORD_NUMBER_EXPORT_CSV_LAFOREST = 74; // E_UPDATE_RECNO.CSV出力_楽天laforest
  const UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_LAFOREST_KICK = 75; // E_UPDATE_RECNO.CSVダウンロード_楽天laforest_RMSキック

  const UPDATE_RECORD_NUMBER_EXPORT_CSV_DOLCISSIMO = 76; // E_UPDATE_RECNO.CSV出力_楽天dolcissimo
  const UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_DOLCISSIMO_KICK = 77; // E_UPDATE_RECNO.CSVダウンロード_楽天dolcissimo_RMSキック

  const UPDATE_RECORD_NUMBER_LAFOREST_REVIEW = 78; // E_UPDATE_RECNO.楽天laforestレビュー
  const UPDATE_RECORD_NUMBER_DOLCISSIMO_REVIEW = 79; // E_UPDATE_RECNO.楽天dolcissimoレビュー

  const UPDATE_RECORD_NUMBER_SHOPLIST_SALES = 80; // SHOPLIST販売実績テーブル更新処理

  const UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN          = 81; // 楽天plusnaoのアクセス数取得処理
  const UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN_MOTTO    = 82; // 楽天mottoのアクセス数取得処理
  const UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN_LAFOREST = 83; // 楽天laforestのアクセス数取得処理
  const UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN_DOLTI    = 84; // 楽天dolcissimoのアクセス数取得処理

  const UPDATE_RECORD_NUMBER_EXPORT_CSV_GEKIPLA = 85; // E_UPDATE_RECNO.CSV出力_楽天gekipla
  const UPDATE_RECORD_NUMBER_DOWNLOAD_CSV_GEKIPLA_KICK = 86; // E_UPDATE_RECNO.CSVダウンロード_楽天gekipla_RMSキック
  
  const UPDATE_RECORD_YAHOO_IMAGE_CHECK_REGIST_PLUSNAO = 87; // E_UPDATE_RECNO.Yahoo画像アップロードチェック　レコード登録（plusnao）
  const UPDATE_RECORD_YAHOO_IMAGE_CHECK_REGIST_KAWAEMON = 88; // E_UPDATE_RECNO.Yahoo画像アップロードチェック レコード登録（kawa-e-mon）
  const UPDATE_RECORD_YAHOO_IMAGE_CHECK_REGIST_OTORIYOSE = 89; // E_UPDATE_RECNO.Yahoo画像アップロードチェック レコード登録（おとりよせ）

  const UPDATE_RECORD_NUMBER_ACCESS_COUNT_RAKUTEN_GEKIPLA = 91; // 楽天gekiplaのアクセス数取得処理
  const UPDATE_RECORD_NUMBER_GEKIPLA_REVIEW = 92; // E_UPDATE_RECNO.楽天gekiplaレビュー

  const UPDATE_RECORD_NUMBER_STOCK_LIST_UPDATE = 93; // 最新在庫データを取得

  // m.送料設定
  const DELIVERY_TYPE_TAKUHAI_BETSU   = 0; // 宅配別 = 0
  const DELIVERY_TYPE_TAKUHAI_KOMI    = 1; // 宅配込 = 1
  const DELIVERY_TYPE_KOBETSU         = 2; // 個別送料 = 2
  const DELIVERY_TYPE_MAILBIN_KOMI    = 3; // メール便込 = 3
  const DELIVERY_TYPE_TEIKEIGAI_KOMI  = 4; // 定形外込 = 4
  const DELIVERY_TYPE_YUU_PACKET      = 5; // ゆうパケット = 5
  const DELIVERY_TYPE_NEKOPOSU        = 6; // ねこポス = 6
  const DELIVERY_TYPE_TEIKEI        = 24; // 定形郵便 = 24

  public static $DELIVERY_METHOD_LIST = [
      self::DELIVERY_TYPE_TAKUHAI_BETSU   => '宅配別'
    , self::DELIVERY_TYPE_TAKUHAI_KOMI    => '宅配込'
    , self::DELIVERY_TYPE_KOBETSU         => '個別送料'
    , self::DELIVERY_TYPE_MAILBIN_KOMI    => 'メール便込'
    , self::DELIVERY_TYPE_TEIKEI  => '定形郵便'
    , self::DELIVERY_TYPE_TEIKEIGAI_KOMI  => '定形外込'
    , self::DELIVERY_TYPE_YUU_PACKET      => 'ゆうパケット'
    , self::DELIVERY_TYPE_NEKOPOSU        => 'ねこポス'
  ];

  // モールID (tb_shopping_mall.mall_id ※ ne_mall_id ではない)
  const MALL_ID_RAKUTEN    = 1;
  const MALL_ID_BIDDERS    = 2;
  const MALL_ID_AMAZON     = 3;
  const MALL_ID_YAHOO      = 4;
  const MALL_ID_Q10        = 5;
  const MALL_ID_SS         = 16;
  const MALL_ID_YAHOOKAWA  = 17;
  const MALL_ID_YAHOO_OTORIYOSE = 21;
  const MALL_ID_PPM        = 18;
  const MALL_ID_SHOPLIST   = 20;
  const MALL_ID_FREE_ORDER = 14;
  const MALL_ID_EC01       = 22;
  const MALL_ID_EC02       = 23;
  const MALL_ID_REAL01     = 24;
  const MALL_ID_AMAZON_COM = 25;
  const MALL_ID_RAKUTEN_MINNA = 26;
  const MALL_ID_RAKUTEN_DOLCISSIMO = 29;
  const MALL_ID_RAKUTEN_MOTTO = 31;
  const MALL_ID_RAKUTEN_LAFOREST = 32;
  const MALL_ID_RAKUTEN_GEKIPLA = 33;

  // モールコード
  const MALL_CODE_RAKUTEN       = 'rakuten';
  const MALL_CODE_BIDDERS       = 'bidders';
  const MALL_CODE_PLUSNAO_YAHOO = 'plusnao_yahoo';
  const MALL_CODE_KAWA_YAHOO    = 'kawa_yahoo';
  const MALL_CODE_OTORIYOSE_YAHOO = 'otoriyose_yahoo';
  const MALL_CODE_CUBE          = 'cube';
  const MALL_CODE_Q10           = 'q10';
  const MALL_CODE_AMAZON        = 'amazon';
  const MALL_CODE_SS            = 'ss';
  const MALL_CODE_PPM           = 'ppm';
  const MALL_CODE_SHOPLIST      = 'shoplist';
  const MALL_CODE_FREE_ORDER    = 'free_order';
  const MALL_CODE_EC01          = 'ec01';
  const MALL_CODE_EC02          = 'ec02';
  const MALL_CODE_REAL01        = 'real01';
  const MALL_CODE_AMAZON_COM    = 'amazon_com';
  const MALL_CODE_RAKUTEN_MINNA = 'rakuten_minna';
  const MALL_CODE_RAKUTEN_MOTTO = 'rakuten_motto';
  const MALL_CODE_RAKUTEN_LAFOREST = 'rakuten_laforest';
  const MALL_CODE_RAKUTEN_DOLCISSIMO = 'rakuten_dolcissimo';
  const MALL_CODE_RAKUTEN_GEKIPLA = 'rakuten_gekipla';
  const MALL_CODE_RAKUTEN_PAY   = 'rakuten_pay'; // モール受注CSV変換での新旧区別にのみ利用。
  const MALL_CODE_RAKUTEN_PAY_MINNA = 'rakuten_pay_minna';  // モール受注CSV変換での新旧区別にのみ利用。
  const MALL_CODE_RAKUTEN_PAY_MOTTO = 'rakuten_pay_motto';  // モール受注CSV変換での新旧区別にのみ利用。
  const MALL_CODE_RAKUTEN_PAY_LAFOREST = 'rakuten_pay_laforest';  // モール受注CSV変換での新旧区別にのみ利用。
  const MALL_CODE_RAKUTEN_PAY_DOLCISSIMO = 'rakuten_pay_dolcissimo';  // モール受注CSV変換での新旧区別にのみ利用。
  const MALL_CODE_RAKUTEN_PAY_GEKIPLA = 'rakuten_pay_激安プラネット';  // モール受注CSV変換での新旧区別にのみ利用。


  // モールID配列
  public static $MALL_CODE_LIST = [
      self::MALL_ID_RAKUTEN    => self::MALL_CODE_RAKUTEN
    , self::MALL_ID_BIDDERS    => self::MALL_CODE_BIDDERS
    , self::MALL_ID_AMAZON     => self::MALL_CODE_AMAZON
    , self::MALL_ID_YAHOO      => self::MALL_CODE_PLUSNAO_YAHOO
    , self::MALL_ID_Q10        => self::MALL_CODE_Q10
    , self::MALL_ID_SS         => self::MALL_CODE_SS
    , self::MALL_ID_YAHOOKAWA  => self::MALL_CODE_KAWA_YAHOO
    , self::MALL_ID_YAHOO_OTORIYOSE => self::MALL_CODE_OTORIYOSE_YAHOO
    , self::MALL_ID_PPM        => self::MALL_CODE_PPM
    , self::MALL_ID_SHOPLIST   => self::MALL_CODE_SHOPLIST
    , self::MALL_ID_FREE_ORDER => self::MALL_CODE_FREE_ORDER
    , self::MALL_ID_EC01       => self::MALL_CODE_EC01
    , self::MALL_ID_EC02       => self::MALL_CODE_EC02
    , self::MALL_ID_REAL01     => self::MALL_CODE_REAL01
    , self::MALL_ID_AMAZON_COM => self::MALL_CODE_AMAZON_COM
    , self::MALL_ID_RAKUTEN_MINNA => self::MALL_CODE_RAKUTEN_MINNA
    , self::MALL_ID_RAKUTEN_MOTTO => self::MALL_CODE_RAKUTEN_MOTTO
    , self::MALL_ID_RAKUTEN_LAFOREST => self::MALL_CODE_RAKUTEN_LAFOREST
    , self::MALL_ID_RAKUTEN_DOLCISSIMO => self::MALL_CODE_RAKUTEN_DOLCISSIMO
    , self::MALL_ID_RAKUTEN_GEKIPLA => self::MALL_CODE_RAKUTEN_GEKIPLA
  ];

  // recordsini 設定キー (E_INI)
  const E_INI_CODE_ORDER_POINT_VALID              = 1;
  const E_INI_CODE_VERSION                        = 2;
  const E_INI_CODE_ORDER_POINT_CALCULATION_POINT  = 3;
  const E_INI_CODE_ORDER_POINT_RATE               = 4;
  const E_INI_CODE_INTERVAL                       = 5;
  const E_INI_CODE_IMAGE_FOLDER                   = 6;
  const E_INI_CODE_ORDER_NUM                      = 7;
  const E_INI_CODE_8                              = 8;
  const E_INI_CODE_DISCOUNT                       = 9;

  // NextEngine 文言
  // 支払い方法 文言
  const PAYMENT_METHOD_DAIBIKI = '代金引換';
  const PAYMENT_METHOD_DONE = '支払済';

//  const PAYMENT_METHOD_CREDIT = 'クレジットカード';
//  const PAYMENT_METHOD_DAIBIKI = '代金引換';
//  const PAYMENT_METHOD_BANK_PRE = '銀行振込前払い';
//  const PAYMENT_METHOD_BANK_POST = '銀行振込後払い';
//  const PAYMENT_METHOD_YUBIN_FURIKAE = '郵便振替';
//  const PAYMENT_METHOD_CACHE_MAIL = '現金書留';
//  const PAYMENT_METHOD_AU_MATOMETE = 'まとめてau支払い';
//  const PAYMENT_METHOD_MOBILE_SUICA = 'モバイルSuica';
//  const PAYMENT_METHOD_CONVENIENCE_STORE = 'コンビニ決済';
//  const PAYMENT_METHOD_PAY_EASY = 'ペイジー決済';
//  const PAYMENT_METHOD_NP = 'NP後払い';
//  const PAYMENT_METHOD_LAWSON = 'ローソン前払';
//  const PAYMENT_METHOD_SEVEN_ELEVEN = 'セブンイレブン前払';
//  const PAYMENT_METHOD_RAKUTEN_E_BANK = '楽天バンク決済';
//  const PAYMENT_METHOD_S_MATOMETE = 'S!まとめて支払い';
//  const PAYMENT_METHOD_DOCOMO_KEITAI = 'ドコモケータイ払い';
//  const PAYMENT_METHOD_ID = 'iD決済';
//  const PAYMENT_METHOD_YAHOO_KANTAN = 'Yahoo!かんたん決済';
//  const PAYMENT_METHOD_WEB_MONEY = 'ウェブマネー決済';
//  const PAYMENT_METHOD_RAKUTEN_BANK = '楽天銀行';
//  const PAYMENT_METHOD_EDY = 'Edy決済';
//  const PAYMENT_METHOD_AMAZON_PAYMENT = 'Amazonペイメント';
//  const PAYMENT_METHOD_JAPAN_NET_BANK = 'ジャパンネット銀行';
//  const PAYMENT_METHOD_ATOBARAI = '後払い.com';
//  const PAYMENT_METHOD_AU_KANTAN = 'auかんたん決済';
//  const PAYMENT_METHOD_PAYGENT = 'ペイジェント決済';
//  const PAYMENT_METHOD_PG_CREDIT = 'クレジットカード(pg)';
//  const PAYMENT_METHOD_PG_CONVENIENCE_STORE = 'コンビニ決済(pg)';
//  const PAYMENT_METHOD_PG_BANK_NET = '銀行ネット決済(pg)';
//  const PAYMENT_METHOD_PG_ATM = 'ATM決済(pg)';
//  const PAYMENT_METHOD_PG_MOBILE_CARRIER = '携帯キャリア決済(pg)';
//  const PAYMENT_METHOD_PAPERLESS = 'ペーパーレス決済';
//  const PAYMENT_METHOD_Q10 = 'Qoo10';
//  const PAYMENT_METHOD_PAYPAL = 'Paypal';
//  const PAYMENT_METHOD_AT = '＠払い';
//  const PAYMENT_METHOD_SOFTBANK_MATOMETE = 'ソフトバンクまとめて支払い';
//  const PAYMENT_METHOD_ATODENE = 'アトディーネ';
//  const PAYMENT_METHOD_YAHOO_MONEY = 'Yahoo!マネー／預金払い';
//  const PAYMENT_METHOD_KURONEKO_POST = 'クロネコ代金後払い';
//  const PAYMENT_METHOD_SMASH = 'Smash';
//  const PAYMENT_METHOD_MAEBARAI = '前払決済';
//  const PAYMENT_METHOD_POINT = 'ポイント全額支払い';
//  const PAYMENT_METHOD_SAMPLE = 'サンプル・貸し出し';
//  const PAYMENT_METHOD_INTERNATIONAL = '国際取引決済';
//  const PAYMENT_METHOD_KAKEURI = '掛売';
//  const PAYMENT_METHOD_OTHER = 'その他';
//  const PAYMENT_METHOD_DONE = '支払済';

  // 発送方法 文言
  const DELIVERY_METHOD_TAKUHAI = '佐川急便(e飛伝2)';
  const DELIVERY_METHOD_MAIL_BIN = 'ﾔﾏﾄ(メール便)B2v6';
  const DELIVERY_METHOD_YAMATO_HATSUBARAI = 'ﾔﾏﾄ(発払い)B2v6';
  const DELIVERY_METHOD_TEIKEI = '定形郵便';
  const DELIVERY_METHOD_TEIKEIGAI = '定形外郵便';
  const DELIVERY_METHOD_TEIKEIGAI_DAIBIKI = '定形外代引';
  const DELIVERY_METHOD_YUU_PACKET = 'ゆうパケット';
  const DELIVERY_METHOD_NEKOPOSU = 'ねこポス';
  const DELIVERY_METHOD_TENTOU = '店頭渡し';
  const DELIVERY_METHOD_CLICKPOST = 'クリックポスト';
  const DELIVERY_METHOD_YUU_PACK = 'ゆうパック';
  const DELIVERY_METHOD_YUU_PACK_RSL = 'ゆうパック(RSL)';
  const DELIVERY_METHOD_SHOPLIST = 'SHOPLIST';

  /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
  private $doctrine; // Doctrine

  /** @var  \Doctrine\DBAL\Connection */
  private $db; // main DB接続

  /** @var  \Doctrine\DBAL\Connection */
  private $dbLog; // log DB接続

  /** @var ContainerInterface */
  private $container;

  /**
   * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
   */
  public function __construct($doctrine)
  {
    $this->doctrine = $doctrine;
    $this->db = $doctrine->getConnection('main');
    $this->dbLog = $doctrine->getConnection('log');
  }

  /**
   * @param ContainerInterface $container
   */
  public function setContainer(ContainerInterface $container)
  {
    $this->container = $container;
  }

  public function isProcessRunning($queueName = 'main')
  {
    $sql = "SELECT COUNT(*) as cnt FROM tb_running WHERE queue_name = :queueName";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':queueName', $queueName, \PDO::PARAM_STR);
    $stmt->execute();
    if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      return ($row['cnt'] > 0);
    }

    throw new RuntimeException('can not check process is running or not.');
  }

  public function getRunningProcesses($queueName = 'main')
  {
    $sql = "SELECT * FROM tb_running WHERE queue_name = :queueName ORDER BY start_datetime";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':queueName', $queueName, \PDO::PARAM_STR);
    $stmt->execute();

    $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return ($row);
  }


  public function insertRunningLog($title, $queueName = 'main')
  {
    $sql = "INSERT INTO tb_running(proc, queue_name, start_datetime, estimate_time) ";
    $sql .= " values( :title, :queueName, NOW(), :estimate_time)";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':title', $title, \PDO::PARAM_STR);
    $stmt->bindValue(':queueName', $queueName, \PDO::PARAM_STR);
    $stmt->bindValue(':estimate_time', '', \PDO::PARAM_STR); // TODO 実装
    $stmt->execute();
  }

  public function deleteRunningLog($title, $queueName = 'main')
  {
    // 空のロックレコードもついでに削除（たまに紛れ込むが、害にしかならない）
    $sql = "DELETE FROM tb_running WHERE (proc = :title AND queue_name = :queueName) OR COALESCE(proc, '') = ''";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':title', $title, \PDO::PARAM_STR);
    $stmt->bindValue(':queueName', $queueName, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * ロック取得待ち処理
   * @param string $processName ロック名
   * @param int $interval ロック取得試行間隔 (秒)
   * @param \DateTime $limit ロック取得試行制限日時 ※安全のため必須
   * @param string $queueName キュー名
   * @param LoggerInterface $logger
   * @return boolean true: 成功 false: 失敗（& 例外送出）
   */
  public function waitRunningProcessLock($processName, $interval, $limit, $logger = null, $queueName = 'main')
  {
    do {
      $result = ! ($this->isProcessRunning($queueName));
      // ロック取得成功
      if ($result) {
        $this->insertRunningLog($processName, $queueName);
        break;
      }

      // 制限時間切れ
      if ($limit && $limit < (new \DateTime())) {
        if ($logger) {
          $logger->info(sprintf('process lock wait (%s), reached to limit %s', $processName, $limit->format('Y-m-d H:i:s')));
          $logger->info(print_r($this->getRunningProcesses($queueName), true));
        }
        throw new ProcessLockWaitException('can not get process lock until limit: ' . $processName . ': ' . $limit->format('Y-m-d H:i:s'));
      }

      if ($logger) {
        $logger->debug(sprintf('process lock wait (%s) %d seconds, up to %s', $processName, $interval, $limit->format('Y-m-d H:i:s')));
      }
      sleep($interval);

    } while(!$result);

    return $result;
  }


  /**
   * 最終更新日時テーブル 最終更新日時取得
   * @param $recordNumber
   * @return \DateTime
   */
  public function getUpdateRecordLastUpdatedDateTime($recordNumber)
  {
    $sql  = " SELECT `datetime` ";
    $sql .= " FROM tb_updaterecord ";
    $sql .= " WHERE updaterecordno = :recordNumber ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':recordNumber', $recordNumber, \PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchColumn();

    return $result ? new \DateTime($result) : null;
  }


  /**
   * 最終更新日時テーブル 更新
   * @param int $recordNumber
   * @param \DateTime $datetime
   */
  public function updateUpdateRecordTable($recordNumber, $datetime = null)
  {
    if (!$datetime) {
      $datetime = new \DateTime();
    }

    $sql  = " REPLACE tb_updaterecord ( ";
    $sql .= "     `updaterecordno`  ";
    $sql .= "   , `datetime`  ";
    $sql .= " ) VALUES ( ";
    $sql .= "     :recordNumber ";
    $sql .= "   , :datetime ";
    $sql .= " ) ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':recordNumber', $recordNumber, \PDO::PARAM_INT);
    $stmt->bindValue(':datetime', $datetime->format('Y-m-d H:i:s'), \PDO::PARAM_STR);

    $stmt->execute();
  }

  /**
   * ログ一覧取得処理
   */
  public function getLastLogList($lastLogId, $limit = 100)
  {
    $lastLogId = intval($lastLogId);

    /** @var EntityManager $em */
    $em = $this->doctrine->getManager('main');
    $qb = $em->createQueryBuilder();

    $qb->select('tb_log');
    $qb->from('\MiscBundle\Entity\TbLog', 'tb_log');

    // ログレベル notice 以上
    $qb->andWhere('tb_log.log_level >= :border')->setParameter('border', TbLog::NOTICE, \PDO::PARAM_INT);

    if ($lastLogId) {
      $qb->andWhere('tb_log.id > :lastId');
      $qb->setParameter('lastId', $lastLogId);
      $qb->setMaxResults($limit); // 安全のための制限

    } else {
      $qb->setMaxResults(20); // 初期では20件取得
    }

    $qb->orderBy('tb_log.id', 'DESC');
    $query = $qb->getQuery();

    $tmp = $query->getResult(); // toArray() でキーを大文字にするため、一旦オブジェクトで取得
    $result = [];
    /** @var TbLog $log */
    foreach($tmp as $log) {
      $row = $log->toArray(); // information は含まれない
      $row['HAS_INFORMATION'] = strlen($log->getInformation()) ? 1 : 0;
      $result[] = $row;
    }

    return $result;
  }

  /**
   * ログ一覧取得処理
   * @param $firstLogId
   * @param int $limit
   * @return array
   */
  public function getLastLogListMore($firstLogId, $limit = 100)
  {
    $firstLogId = intval($firstLogId);

    $em = $this->doctrine->getEntityManager();
    $qb = $em->createQueryBuilder();

    $qb->select('tb_log');
    $qb->from('\MiscBundle\Entity\TbLog', 'tb_log');

    // ログレベル notice 以上
    $qb->andWhere('tb_log.log_level >= :border')->setParameter('border', TbLog::NOTICE, \PDO::PARAM_INT);

    if ($firstLogId) {
      $qb->andWhere('tb_log.id < :firstId');
      $qb->setParameter('firstId', $firstLogId);
      $qb->setMaxResults($limit); // 安全のための制限

    } else {
      throw new RuntimeException('!!');
    }

    $qb->orderBy('tb_log.id', 'DESC');
    $query = $qb->getQuery();

    $tmp = $query->getResult(); // toArray() でキーを大文字にするため、一旦オブジェクトで取得
    $result = [];
    /** @var TbLog $log */
    foreach($tmp as $log) {
      $row = $log->toArray(); // information は含まれない
      $row['HAS_INFORMATION'] = strlen($log->getInformation()) ? 1 : 0;
      $result[] = $row;
    }

    return $result;
  }

  /**
   * ログ絞込一覧取得処理
   * @param $searchInfo
   * @param int $limit
   * @return array
   */
  public function getLogSearchList($searchInfo, $limit = 1000)
  {
    $em = $this->doctrine->getEntityManager();

    $paramArray = array();

    $dql = 'SELECT t FROM MiscBundle:TbLog t'
      . ' WHERE t.log_level >= :border';
    $paramArray['border'] = TbLog::NOTICE;
    if (! empty($searchInfo['dateFrom'])) {
      $dql .= ' AND t.log_timestamp >= :dateFrom';
      $paramArray['dateFrom'] = $searchInfo['dateFrom'];
    }
    if (! empty($searchInfo['dateTo'])) {
      $dql .= ' AND t.log_timestamp <= :dateTo';
      $paramArray['dateTo'] = $searchInfo['dateTo'];
    }
    if (! empty($searchInfo['pcName'])) {
      for ($i = 0; $i < count($searchInfo['pcName']); $i++) {
        $dql .= ' AND t.pc like :pcName'.$i;
        $paramArray['pcName'.$i] = '%' . $this->escapeLikeString($searchInfo['pcName'][$i]) . '%';
      }
    }
    if (! empty($searchInfo['execTitle'])) {
      for ($i = 0; $i < count($searchInfo['execTitle']); $i++) {
        $dql .= ' AND t.exec_title like :execTitle'.$i;
        $paramArray['execTitle'.$i] = '%' . $this->escapeLikeString($searchInfo['execTitle'][$i]) . '%';
      }
    }
    if (! empty($searchInfo['logTitle'])) {
      for ($i = 0; $i < count($searchInfo['logTitle']); $i++) {
        $dql .= ' AND t.log_title like :logTitle'.$i;
        $paramArray['logTitle'.$i] = '%' . $this->escapeLikeString($searchInfo['logTitle'][$i]) . '%';
      }
    }
    if (! empty($searchInfo['sub'])) {
      for ($i = 0; $i < count($searchInfo['sub']); $i++) {
        $dql .= ' AND (';
        $dql .= " t.log_subtitle1 like :sub{$i}1";
        $dql .= ' OR ';
        $dql .= " t.log_subtitle2 like :sub{$i}2";
        $dql .= ' OR ';
        $dql .= " t.log_subtitle3 like :sub{$i}3";
        $dql .= ' )';
        $paramArray["sub{$i}1"] = '%' . $this->escapeLikeString($searchInfo['sub'][$i]) . '%';
        $paramArray["sub{$i}2"] = '%' . $this->escapeLikeString($searchInfo['sub'][$i]) . '%';
        $paramArray["sub{$i}3"] = '%' . $this->escapeLikeString($searchInfo['sub'][$i]) . '%';
      }
    }
    $dql .= ' ORDER BY t.id desc';
    $query = $em->createQuery($dql);
    foreach ($paramArray as $key => $value) {
      $query->setParameter($key, $value);
    }
    $query->setMaxResults($limit);
    $tmp = $query->getResult(); // toArray() でキーを大文字にするため、一旦オブジェクトで取得
    $result = [];
    /** @var TbLog $log */
    foreach($tmp as $log) {
      $row = $log->toArray(); // information は含まれない
      $row['HAS_INFORMATION'] = strlen($log->getInformation()) ? 1 : 0;
      $result[] = $row;
    }
    return $result;
  }

  /**
   * 設定テーブル 値取得
   * @param string $settingName
   * @param string|null $env
   * @param bool $reload
   * @return mixed
   */
  public function getSettingValue($settingName, $env = null, $reload = false)
  {
    $result = null;

    // 開発環境用固定値設定
    $envSettings = [
      // 楽天FTPパスワード
      'RAKUTEN_GOLD_FTP_PASSWORD' => [
        'test' => '1234' // 開発環境では固定値
      ]
    ];
    if ($env) {
      if (isset($envSettings[$settingName]) && isset($envSettings[$settingName][$env])) {
        return $envSettings[$settingName][$env];
      }
    }

    $repo = $this->doctrine->getRepository('MiscBundle:TbSetting');
    /** @var TbSetting $setting */
    $setting = $repo->find($settingName);
    if ($reload) {
      $em = $this->doctrine->getManager('main');
      $em->refresh($setting);
    }

    if ($setting) {
      $result = $setting->getSettingVal();
    }

    return $result;
  }

  /**
   * 設定テーブル 値更新
   * @param string $settingName
   * @param string $settingValue
   * @return mixed
   */
  public function updateSettingValue($settingName, $settingValue)
  {
    $result = null;

    /** @var \Doctrine\ORM\EntityRepository */
    $repo = $this->doctrine->getRepository('MiscBundle:TbSetting');

    /** @var TbSetting $setting */
    $setting = $repo->find($settingName);

    $em = $this->doctrine->getManager();

    if ($setting) {
      $setting->setSettingVal($settingValue);
    } else {
      $setting = new TbSetting();
      $setting->setSettingKey($settingName);
      $setting->setSettingVal($settingValue);
    }

    $em->persist($setting);
    $em->flush();
  }


  /**
   * tb_recordsiniテーブル 更新 (setIni_Integer)
   * @param int $iniCode
   * @param int $iniValue
   */
  public function setRecordIniInteger($iniCode, $iniValue)
  {
    $db = $this->db;

    $sql = <<<EOD
      UPDATE tb_recordsini SET tb_recordsini.intdata = :value
      WHERE tb_recordsini.recordsini_cd = :code
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':value', intval($iniValue), \PDO::PARAM_INT);
    $stmt->bindValue('code', intval($iniCode), \PDO::PARAM_INT);
    $stmt->execute();
  }

//  /** ※現在利用なし。コメントアウト
//   * tb_recordsiniテーブル データ取得 (getIni_Integer)
//   * @param int $iniCode
//   * @return int|null
//   */
//  public function getRecordIniInteger($iniCode)
//  {
//    $db = $this->db;
//
//    $sql = <<<EOD
//      SELECT
//        initdata
//      FROM tb_recordsini
//      WHERE tb_recordsini.recordsini_cd = :code
//EOD;
//    $stmt = $db->prepare($sql);
//    $stmt->bindValue('code', intval($iniCode), \PDO::PARAM_INT);
//    $stmt->execute();
//
//    return $stmt->fetchColumn(0);
//  }


  /**
   * 区分値配列取得
   *
   * @param string $name
   * @return array
   */
  public function getKubunList($name = null)
  {
    $kubunList = [
        '承認区分' => [
            '0' => '必要なし'
          , '10' => '未承認'
          , '20' => '承認中'
          , '30' => '承認済'
          , '70' => '請求済'
          , '90' => '承認NG'
      ]
      , '確認チェック' => [
          '0' => '確認必要なし（非表示）'
        , '1' => '確認必要（表示）'
        , '2' => '確認済（表示）'
      ]
      , '入金区分' => [
          '0' => '未入金'
        , '1' => '一部入金'
        , '2' => '入金済み'
      ]
      , '顧客区分' => [
          '0' => '一般顧客'
        , '99' => 'ブラック'
      ]
    ];

    if (!$name) {
      return $kubunList;
    } else {

      return isset($kubunList[$name]) ? $kubunList[$name] : null;
    }
  }

  /**
   * モール別設定取得
   * @param $mallCode
   * @param $column
   * @return null
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getMallSetting($mallCode, $column)
  {
    $db = $this->db;

    $mallId = $this->getMallIdByMallCode($mallCode);

    $sql = "SELECT * FROM tb_shopping_mall WHERE mall_id = :mallId ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':mallId', $mallId);
    $stmt->execute();

    $result = null;
    if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $result = isset($row[$column]) ? $row[$column] : null;
    }

    return $result;
  }

  /**
   * 消費税率取得 （1.xx）
   * @param \DateTimeInterface $date
   * @return float
   */
  public function getTaxRate(\DateTimeInterface $date = null)
  {
    $rate = self::CURRENT_TAX_RATE;
    if ($date) {
      // なにもしない。常に最新のものを返し続ける
    }
    return $rate;
  }

  /**
   * CSV出力 共通処理
   * Accessシステムからの移植
   * ※「受発注可能フラグ退避」「復元」は、同様の挙動になるようにフラグを確認して処理し、
   *   フラグのコピー・戻し処理は移植しない。
   *
   * @param BatchLogger $logger
   * @param string $rakutenCsvOutputDir
   * @throws \Doctrine\DBAL\DBALException
   */
  public function exportCsvCommonProcess(BatchLogger $logger, $rakutenCsvOutputDir = null)
  {
    $db = $this->db;

    $logTitle = 'CreatingFacts';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // 受発注可能フラグ退避 状態確認
    // ※こちらの処理では退避しないが、退避中＝別処理実行中およびdeliverycode変更中 ということなので
    //   処理をせずに中止
    if ($this->getSettingValue('ORDER_ENABLED_FLG_BACKUP_DOING') != 0) {
      throw new DbCommonUtilException('受発注可能フラグが退避中には共通処理は実行できません。');
    }

    // セット商品 受発注可能フラグ 更新処理
    $this->updateSetProductPurchasableFlag($logger);

    // Call Deliverycode更新退避___
    $this->updateDeliveryCode($logger);

    // Call 補正___(lc_skip)
    $this->adjustTexts($logger);

    // Call 価格再計算("ALL", "", False, lc_skip)
    $this->recalculatePrices($logger);

    // Call カレンダーの再作成___(lc_skip)
    $this->addCalendarDate($logger); // TODO バッチ処理で可

    // Call メーカー到着予定日を再設定
    $this->updateVendorArrivalDate($logger);

    // Call 商品出荷設定日
    $this->updateShippingDate($logger);

    // Call rakuten納期管理番号準備(lc_skip) ※FTPアップロード はスキップ
    if ($rakutenCsvOutputDir) {
      $this->prepareRakutenNokiNumber($logger, $rakutenCsvOutputDir);
    }

    // Call 即納可能な項目選択肢リストを作成___(lc_skip)
    $this->updateReadyProductChoiceItemsHtmlString($logger);

    // Call 自動タイトルの再設定___(lc_skip)
    $this->updateTitleParts($logger);

    // Call 表示順位の設定___(lc_skip)
    $this->updatePriority($logger);

    // '====================
    // 'startup_flgを0に
    // '====================
    $sql = <<<EOD
    	UPDATE tb_mainproducts_cal
    	SET startup_flg = 0
    	WHERE deliverycode_pre <> :deliveryCodeTemporary
    	  AND startup_flg <> 0
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * セット商品 受発注可能フラグ更新処理
   * @param BatchLogger $logger
   * @throws \Doctrine\DBAL\DBALException
   */
  private function updateSetProductPurchasableFlag(BatchLogger $logger)
  {
    $db = $this->db;

    $logTitle = 'CreatingFacts';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'セット商品受発注可能フラグ更新', '開始'));

    $db->exec("CALL PROC_UPDATE_SET_PRODUCT_PURCHASABLE_FLAGS");

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'セット商品受発注可能フラグ更新', '終了'));
  }

  /**
   * CSV出力共通処理 : deliverycode 更新処理
   * @param BatchLogger $logger
   */
  private function updateDeliveryCode(BatchLogger $logger)
  {
    $db = $this->db;

    $logTitle = 'CreatingFacts';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'deliverycode, 販売開始日, 販売終了日を更新', '開始'));

    // フリー在庫数計算用 販売不可在庫数更新処理
    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->container->get('batch.mall_process.next_engine');
    $neMallProcess->updateNotForSaleStock();

    $sql = <<<EOD
      UPDATE tb_mainproducts_cal AS cal
      INNER JOIN tb_mainproducts m ON cal.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN (
        SELECT
            cal.daihyo_syohin_code
          , cal.deliverycode
          /* deliverycode_new */
          , CASE
              WHEN pci.フリー在庫数 > 0 AND pci.予約販売件数 = 0 THEN :deliveryCodeReady  /* 0:即納 */
              WHEN pci.フリー在庫数 > 0 AND pci.予約販売件数 > 0 THEN :deliveryCodeReadyPartially  /* 1:一部即納 */
              WHEN pci.予約販売件数 > 0                         THEN :deliveryCodePurchaseOnOrder /* 2:受発注のみ */
              ELSE :deliveryCodeFinished        /* 販売終了 */
            END AS deliverycode_new
          , pci.フリー在庫数
          , pci.予約販売件数
        FROM tb_mainproducts_cal cal
        INNER JOIN (
          SELECT
              pci.daihyo_syohin_code
            , SUM( pci.`フリー在庫数` ) AS フリー在庫数
            , SUM(
                CASE
                  WHEN
                     pci.`フリー在庫数` = 0
                     AND (
                          pci.予約フリー在庫数 <> 0
                       OR ( pci.受発注可能フラグ <> 0 AND cal.受発注可能フラグ退避F = 0 )
                     )
                  THEN 1
                  ELSE 0
                END
              ) AS 予約販売件数
          FROM tb_productchoiceitems pci
          INNER JOIN tb_mainproducts_cal cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
          GROUP BY pci.daihyo_syohin_code
        ) AS pci ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      ) T ON cal.daihyo_syohin_code = T.daihyo_syohin_code
      SET cal.endofavailability =
               CASE
                  WHEN T.deliverycode_new IN (:deliveryCodeReady, :deliveryCodeReadyPartially, :deliveryCodePurchaseOnOrder) THEN NULL
                  WHEN cal.endofavailability IS NULL THEN NOW()
                  ELSE cal.endofavailability
               END
        , m.`販売開始日` =
               CASE
                  WHEN T.deliverycode_new IN (:deliveryCodeReady, :deliveryCodeReadyPartially, :deliveryCodePurchaseOnOrder)
                   AND ( cal.deliverycode IN (:deliveryCodeFinished, :deliveryCodeTemporary) OR m.`販売開始日` IS NULL )
                     THEN NOW()
                  ELSE m.`販売開始日`
               END
        , cal.deliverycode = T.deliverycode_new
      WHERE cal.deliverycode <> :deliveryCodeTemporary
EOD;

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER);
    $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    // セット商品のdeliverycodeを追加計算。
    // セット商品は個別に在庫を持つのではなく、構成品の在庫数から計算されるため、tb_productchoiceitems上では在庫0でもフリー在庫がある場合がある
    // 販売終了処理はされているので、ここでは簡単にフリー在庫が全SKUにあるかで判定
    // 再計算するのは受発注可能商品のみ。販売終了になっているものはそのまま。

    $sql = <<<EOD
      UPDATE tb_mainproducts_cal cal
      JOIN (
        SELECT
            daihyo_syohin_code
          , CASE
              WHEN SUM(exist_stock) > 0 AND SUM(no_stock) = 0 THEN :deliveryCodeReady -- 即納
              WHEN SUM(exist_stock) > 0 AND SUM(no_stock) > 0 THEN :deliveryCodeReadyPartially -- 一部即納
              ELSE :deliveryCodePurchaseOnOrder -- 受発注のみ
            END deliverycode
        FROM (
          SELECT
              pci.daihyo_syohin_code                      AS daihyo_syohin_code
            , pci.ne_syohin_syohin_code                   AS set_sku
            , CASE
                WHEN (TRUNCATE((pci_detail.`フリー在庫数` / d.num), 0) > 0) THEN 1
                ELSE 0
               END AS exist_stock
            , CASE
                WHEN (TRUNCATE((pci_detail.`フリー在庫数` / d.num), 0) = 0) THEN 1
                ELSE 0
              END AS no_stock
          FROM tb_productchoiceitems pci
          INNER JOIN tb_mainproducts m ON pci.daihyo_syohin_code = m.daihyo_syohin_code
          INNER JOIN tb_set_product_detail d ON pci.ne_syohin_syohin_code = d.set_ne_syohin_syohin_code
          INNER JOIN tb_productchoiceitems pci_detail ON d.ne_syohin_syohin_code = pci_detail.ne_syohin_syohin_code
          WHERE m.set_flg <> 0
        ) T
        GROUP BY T.daihyo_syohin_code
      ) S ON cal.daihyo_syohin_code = S.daihyo_syohin_code
      SET cal.deliverycode = S.deliverycode
      WHERE cal.deliverycode = :deliveryCodePurchaseOnOrder
EOD;

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER);
    $stmt->execute();

    // 開始時点のdeviverycodeを保持。以後の処理は開始時点のdeliverycodeにて判断する
    $db->query("UPDATE tb_mainproducts_cal AS cal SET cal.deliverycode_pre = cal.deliverycode");

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, 'deliverycode, 販売開始日, 販売終了日を更新', '終了'));
  }

  /**
   * 補正処理
   * @param BatchLogger $logger
   */
  private function adjustTexts(BatchLogger $logger)
  {
    $db = $this->db;

    $logTitle = 'CreatingFacts';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '補正___', '開始'));

    // '====================
    // '画像アドレス -> 不要
    // '====================
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '補正___', '画像アドレスを補正', '(skip)'));
    // $db->query("call PROC_SET_PIC_DIR_NAME_mainproducts"); これは登録・編集時(Access, PHP)の処理が入るため不要

    // '====================
    // '縦軸項目名、横軸項目名 -> 不要
    // '====================
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '補正___', '縦軸項目名・横軸項目名を補正', '(skip)'));
    // これは登録・編集時(Access, PHP)の処理が入るため不要

    /*
    $sql = <<<EOD
      UPDATE tb_productchoiceitems AS pci
      INNER JOIN tb_mainproducts_cal AS cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      SET colname = REPLACE(colname, '(', '【')
      WHERE colname LIKE '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_productchoiceitems as pci inner join tb_mainproducts_cal as cal
 on pci.daihyo_syohin_code = cal.daihyo_syohin_code
 set colname = replace(colname,'（','【')
 where colname like '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_productchoiceitems as pci inner join tb_mainproducts_cal as cal
 on pci.daihyo_syohin_code = cal.daihyo_syohin_code
 set colname = replace(colname,')','】')
 where colname like '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_productchoiceitems as pci inner join tb_mainproducts_cal as cal
 on pci.daihyo_syohin_code = cal.daihyo_syohin_code
 set colname = replace(colname,'）','】')
 where colname like '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_productchoiceitems as pci inner join tb_mainproducts_cal as cal
 on pci.daihyo_syohin_code = cal.daihyo_syohin_code
 set rowname = replace(rowname,'(','【')
 where rowname like '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_productchoiceitems as pci inner join tb_mainproducts_cal as cal
 on pci.daihyo_syohin_code = cal.daihyo_syohin_code
 set rowname = replace(rowname,'（','【')
 where rowname like '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_productchoiceitems as pci inner join tb_mainproducts_cal as cal
 on pci.daihyo_syohin_code = cal.daihyo_syohin_code
 set rowname = replace(rowname,')','】')
 where rowname like '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_productchoiceitems as pci inner join tb_mainproducts_cal as cal
 on pci.daihyo_syohin_code = cal.daihyo_syohin_code
 set rowname = replace(rowname,'）','】')
 where rowname like '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_mainproducts as m inner join tb_mainproducts_cal as cal
 on m.daihyo_syohin_code = cal.daihyo_syohin_code
 set m.横軸項目名 = replace(m.横軸項目名,'(','【')
 where m.横軸項目名 like '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_mainproducts as m inner join tb_mainproducts_cal as cal
 on m.daihyo_syohin_code = cal.daihyo_syohin_code
 set m.横軸項目名 = replace(m.横軸項目名,'（','【')
 where m.横軸項目名 like '%（%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_mainproducts as m inner join tb_mainproducts_cal as cal
 on m.daihyo_syohin_code = cal.daihyo_syohin_code
 set m.横軸項目名 = replace(m.横軸項目名,')','】')
 where m.横軸項目名 like '%)%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_mainproducts as m inner join tb_mainproducts_cal as cal
 on m.daihyo_syohin_code = cal.daihyo_syohin_code
 set m.横軸項目名 = replace(m.横軸項目名,'）','】')
 where m.横軸項目名 like '%）%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_mainproducts as m inner join tb_mainproducts_cal as cal
 on m.daihyo_syohin_code = cal.daihyo_syohin_code
 set m.縦軸項目名 = replace(m.縦軸項目名,'(','【')
 where m.縦軸項目名 like '%(%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_mainproducts as m inner join tb_mainproducts_cal as cal
 on m.daihyo_syohin_code = cal.daihyo_syohin_code
 set m.縦軸項目名 = replace(m.縦軸項目名,'（','【')
 where m.縦軸項目名 like '%（%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_mainproducts as m inner join tb_mainproducts_cal as cal
 on m.daihyo_syohin_code = cal.daihyo_syohin_code
 set m.縦軸項目名 = replace(m.縦軸項目名,')','】')
 where m.縦軸項目名 like '%)%'
EOD;
    $db->query($sql);

    $sql = <<<EOD
update tb_mainproducts as m inner join tb_mainproducts_cal as cal
 on m.daihyo_syohin_code = cal.daihyo_syohin_code
 set m.縦軸項目名 = replace(m.縦軸項目名,'）','】')
 where m.縦軸項目名 like '%）%'
EOD;
    $db->query($sql);
    */

//    // '====================
//    // '販売開始日
//    // → トリッキーな実装をさけ、また「販売開始日」の意味が通る様に deliverycode や 販売終了日 更新の際に一緒に行うように変更
//    // '====================
//    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '補正___', '販売開始日を補正'));
//
//    // （Access VBAコメント）'販売終了商品に販売開始日を設定する。トリッキーだが、こうすることで販売再開した際に販売終了日の翌日を販売開始日とみなすことが出来る
//    $sql = <<<EOD
//      UPDATE tb_mainproducts AS m
//      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
//      SET 販売開始日 = NOW()
//      WHERE cal.endofavailability IS NOT NULL
//EOD;
//    $db->query($sql);

    // '====================
    // '商品タイトルを補正 -> 不要
    // '====================
    // 'ダブルクォーテーションが含まれる場合は倍角のものに置換
    /* PHP で行うため不要
    $sql = <<<EOD
      UPDATE tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      SET daihyo_syohin_name = REPLACE (daihyo_syohin_name, '"', '”')
      WHERE INSTR(daihyo_syohin_name, '"') > 0
EOD;
    $db->query($sql);

    // 'カンマが含まれる場合は倍角のものに置換
    $sql = <<<EOD
      UPDATE tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      SET daihyo_syohin_name = REPLACE(daihyo_syohin_name, ',', '，')
      WHERE INSTR(daihyo_syohin_name, ',') > 0
EOD;
    $db->query($sql);
    */

    // 平均仕入単価を補正
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '補正___', '平均仕入単価を補正'));
    // 初期値 設定
    $sql = <<<EOD
      UPDATE tb_mainproducts as m
      INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      SET cal.genka_tnk_ave = m.genka_tnk
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->execute();

    // ' 最終仕入れから3ヶ月までの間の平均
    $sql = <<<EOD
       UPDATE tb_mainproducts_cal as cal
       INNER JOIN (
           SELECT
               cal.daihyo_syohin_code
             , TRUNCATE((SUM(idh.regular * idh.quantity_price) / SUM(idh.regular)) +.99999, 0) AS 平均仕入れ価格
           FROM tb_mainproducts_cal as cal
           INNER JOIN tb_productchoiceitems as pci ON cal.daihyo_syohin_code = pci.daihyo_syohin_code
           INNER JOIN tb_individualorderhistory as idh ON pci.ne_syohin_syohin_code = idh.商品コード
           INNER JOIN (
               SELECT
                    cal.daihyo_syohin_code
                  , MAX(発行日) AS 最終発行日
               FROM tb_mainproducts_cal as cal
               INNER JOIN tb_productchoiceitems as pci ON cal.daihyo_syohin_code = pci.daihyo_syohin_code
               INNER JOIN tb_individualorderhistory as idh ON pci.ne_syohin_syohin_code = idh.商品コード
               WHERE idh.regular > 0
                 AND idh.quantity_price IS NOT NULL
               GROUP BY cal.daihyo_syohin_code
           ) AS T発行日 ON cal.daihyo_syohin_code = T発行日.daihyo_syohin_code
           WHERE idh.regular > 0
             AND idh.quantity_price IS NOT NULL
             AND idh.発行日 BETWEEN DATE_ADD(最終発行日, INTERVAL -3 MONTH) AND 最終発行日
           GROUP BY cal.daihyo_syohin_code
         ) AS TB_2 ON cal.daihyo_syohin_code = TB_2.daihyo_syohin_code
       SET cal.genka_tnk_ave = TB_2.平均仕入れ価格

EOD;
    $db->query($sql);

    // 最終発注日を再計算
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '補正___', '最終発注日を再計算'));
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal AS CAL
      INNER JOIN (
          SELECT
              PC.daihyo_syohin_code
            , DATE_FORMAT(MAX(PD.最終更新日), '%Y-%m-%d') AS last_orderdate
          FROM tb_purchasedocument AS PD
          INNER JOIN tb_productchoiceitems AS PC ON PD.商品コード = ne_syohin_syohin_code
          GROUP BY PC.daihyo_syohin_code
      ) AS O ON CAL.daihyo_syohin_code = O.daihyo_syohin_code
      SET CAL.last_orderdate = O.last_orderdate
EOD;
    $db->query($sql);

    // 値下げ開始日の更新。 値下げ開始日がNULL、あるいは計算結果でより大きくなるデータについて更新を行う
    $this->updateDiscountBaseDate();

    // '====================
    // '検索コードを設定
    // '====================
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '補正___', '検索コード設定'));
    $this->updateSearchCode();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '補正___', '終了'));
  }



  /**
   * 検索コード (tb_mainproducts_cal.search_code) を再作成（一括）
   * tb_mainproducts_cal.search_code は Accessでの検索に利用されるコード
   * （各店舗、仕入先の商品コードで検索できるように）
   *
   * 各更新タイミングがバラバラなために共通処理に入っているもの
   * * daihyo_syohin_label : Access 商品管理フォーム
   * * tb_qten_information.q10_itemcode : Q10 CSV取り込み時？ 処理が難解なため一旦解析保留
   * * tb_bidders_folog.SeqExhibitId : DeNA CSV出力時の取り込み処理
   *
   * ※ 他で必要になるまでprivate
   */
  private function updateSearchCode()
  {
    $db = $this->db;

    $sql = <<<EOD
      UPDATE tb_mainproducts_cal AS cal
      LEFT JOIN tb_qten_information on cal.daihyo_syohin_code = tb_qten_information.daihyo_syohin_code
      LEFT JOIN tb_bidders_folog ON cal.daihyo_syohin_code = tb_bidders_folog.Code
      SET cal.search_code = CONCAT(
            cal.daihyo_syohin_code
          , '/'
          , ifnull(cal.daihyo_syohin_label, '')
          , '/'
          , ifnull(tb_bidders_folog.SeqExhibitId, '')
          , '/'
          , ifnull(tb_qten_information.q10_itemcode, '')
      )
EOD;

    $db->query($sql);
  }

  /**
   * 価格再計算
   *  * CSV出力共通処理
   *  * 値引き確定処理
   * @param BatchLogger $logger
   * @throws \Doctrine\DBAL\DBALException
   */
  public function recalculatePrices(BatchLogger $logger)
  {
    $db = $this->db;
    $logTitle = 'CreatingFacts';
    $subTitle = '価格再計算';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    // 全モール一括更新

    // 付加費用（非コンテナ）
    $sql = <<<EOD
      UPDATE  tb_mainproducts as m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendormasterdata as v ON m.sire_code = v.sire_code
      SET m.additional_cost = m.weight * v.perweight_postage
      WHERE m.container_flg = 0
        AND m.weight <> 0
EOD;
    $db->query($sql);

    // 付加費用（コンテナ） ※現時点では計算式がないため、分岐だけして同じ計算 TODO 計算式適用
    $sql = <<<EOD
      UPDATE  tb_mainproducts as m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendormasterdata as v ON m.sire_code = v.sire_code
      SET m.additional_cost = m.weight * v.perweight_postage
      WHERE m.container_flg <> 0
        AND m.weight <> 0
EOD;
    $db->query($sql);

    // 自動売価単価 更新
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal cal
      INNER JOIN v_product_price price ON cal.daihyo_syohin_code = price.daihyo_syohin_code
      SET cal.base_baika_tanka = price.baika_tanka
        , cal.cost_tanka = price.cost_tanka
EOD;
    $db->query($sql);

    // 売価単価 更新
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal cal
      INNER JOIN tb_mainproducts m ON cal.daihyo_syohin_code = m.daihyo_syohin_code
      SET cal.baika_tnk = cal.base_baika_tanka
      WHERE m.価格非連動チェック = 0
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
    $stmt->execute();

    // 粗利率 更新
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal cal
      INNER JOIN v_product_price v ON cal.daihyo_syohin_code = v.daihyo_syohin_code
      SET cal.profit_rate = COALESCE(ROUND((CAST(cal.baika_tnk AS SIGNED) - v.baika_genka) / CAST(cal.baika_tnk AS SIGNED) * 100, 2), 0)
EOD;
    $db->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * モール別価格再計算
   * // 商品指定は現状実装しない。
   * // また、全件処理フラグ は利用しない（ Access実装で、true で呼び出している箇所がない ）
   *
   * @param BatchLogger $logger
   * @param $mallCode
   * @throws \Doctrine\DBAL\DBALException
   */
  public function calculateMallPrice(BatchLogger $logger, $mallCode)
  {
    $db = $this->db;

    $logTitle = '価格再計算';
    $subTitle = sprintf('モール別価格再計算[%s]', $mallCode);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    // Amazon.com は特殊計算
    if ($mallCode === self::MALL_CODE_AMAZON_COM) {
      /** @var AmazonMallProcess $mallProcess */
      $mallProcess = $this->container->get('batch.mall_process.amazon');
      $mallProcess->updateAmazonComPrice();

      // 処理完了
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
      return;
    }

    // 商品テーブル名
    $tableName = $this->getMallTableName($mallCode);

    // 検品費用率（Yahoo 受発注のみ）
    $inspectionCostRate = $this->getSettingValue('INSPECTION_COST_RATE');

    // 送料設定に従う
    $obeyPostageSetting = $this->getMallSetting($mallCode, 'obey_postage_setting');
    // 付加費用率
    $additionalCostRatio = $this->getMallSetting($mallCode, 'additional_cost_ratio');

    // baika_tnk 初期値更新
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '売価単価の転記'));

    // SHOPLISTでは値下げ結果を含む cal.baika_tnk ではなく、改めての計算値を転記する。（値下げしない）
    // 原価率は固定値（SHOPLIST取り分 40% を除いた中で利益を確保するため。（通常45%））
    // また、SHOPLISTは「送料設定に従わない」固定
    // また、SHOPLISTは「付加費用率」を0固定
    if ($mallCode === self::MALL_CODE_SHOPLIST) {

      // 原価率（商品別 or 仕入先別）
      $sql = <<<EOD
      UPDATE `{$tableName}` AS i
      INNER JOIN tb_mainproducts     AS m     ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal   ON i.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN v_product_price     AS price ON i.daihyo_syohin_code = price.daihyo_syohin_code

      SET i.baika_tanka = price.baika_tanka_shoplist
      WHERE i.original_price = 0
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY, \PDO::PARAM_INT);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED, \PDO::PARAM_INT);
      $stmt->execute();

    // SHOPLIST以外では cal.baika_tnk を転記する。（値下げを反映した売価）
    } else {
      $sql = <<<EOD
      UPDATE tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN `{$tableName}` AS i ON m.daihyo_syohin_code = i.daihyo_syohin_code
      SET i.baika_tanka = cal.baika_tnk
      WHERE i.original_price = 0
        AND cal.deliverycode_pre <> :deliveryCodeTemporary
        AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;

      $stmt = $db->prepare($sql);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
      $stmt->execute();
    }

    // 送料設定に従うがTRUEの場合
    // FBAマルチチャネル送料もここで加算
    If ($obeyPostageSetting <> "0") {

      // FBAマルチチャネル送料
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, 'FBAマルチチャネル送料の加算'));
      // 重量・寸法の設定があるものについて、FBAマルチチャネル価格を計算。設定がなければ regular 19 + 532 = 551円とする
      $fbaMultiCostRate = (100 + intval($this->getSettingValue('FBA_MULTI_COST_RATE'))) / 100;
      $sql = <<<EOD
        UPDATE `{$tableName}` i
        INNER JOIN tb_mainproducts as m ON i.daihyo_syohin_code = m.daihyo_syohin_code
        INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        LEFT JOIN v_amazon_fba_size v ON i.daihyo_syohin_code = v.daihyo_syohin_code
        SET i.baika_tanka =
            CASE
              WHEN v.size IS NULL THEN i.baika_tanka + CEIL((19 + 532) / :taxRate) /* 551円固定 */
              ELSE
                  i.baika_tanka
                + CEIL(
                  (
                      v.storage_charge /* 保管手数料 */
                    + CASE v.size
                        WHEN 'small'   THEN 527 /* 135 + 392 */
                        WHEN 'regular' THEN 573 /* 135 + 438 */
                        WHEN 'large1'  THEN 722 /* 258 + 464 */
                        WHEN 'large2'  THEN 745 /* 258 + 487 */
                        WHEN 'large3'  THEN 793 /* 258 + 535 */
                        WHEN 'extra_large' THEN 1515 /* 258 + 1257 */
                    END /* 出荷作業手数料 + 発送重量手数料 */
                  )
                  / :taxRate
                  * CAST(:fbaMultiCostRate AS DECIMAL)
                )
            END
        WHERE i.original_price = 0
          AND m.fba_multi_flag <> 0
          AND cal.deliverycode_pre <> :deliveryCodeTemporary
          AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':taxRate', $this->getTaxRate(), \PDO::PARAM_STR);
      $stmt->bindValue(':fbaMultiCostRate', $fbaMultiCostRate, \PDO::PARAM_STR);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
      $stmt->execute();

      // 送料を加算
      // 条件は共通で以下の通り。
      // ・モール別価格非連動無効
      // ・FBAフラグ無効
      // ・deliverycode_pre が販売終了・仮登録ではない
      // ・同梱数（指定された配送方法の、1/4より小さい容積であれば

      $addWhere = "";
      if($mallCode === self::MALL_CODE_KAWA_YAHOO){
        // YahooPlusnaoに寄せる方針となったため、廃止。
        //$addWhere = "AND i.baika_tanka >= 3000";
      }

      // 同梱数を考慮する
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '送料の加算　全ての発送方法で、条件に合えば同梱数を考慮'));
      $sql = <<<EOD
        UPDATE tb_mainproducts as m
        INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN `{$tableName}` as i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        INNER JOIN tb_shippingdivision sd ON m.`送料設定` = sd.id
        SET i.baika_tanka =
              i.baika_tanka
            + CASE WHEN (
                    cal.pricedown_flg <> 0 /* 値下げ許可フラグ ON */
                AND cal.bundle_num_average > 1
                AND sd.max_three_edge_individual IS NOT NULL /* 画面側の制御で、nullまたはカンマ区切りの3つの数値が入っている */
                AND m.width > 0
                AND m.height > 0
                AND m.depth > 0
                AND m.width * m.height * m.depth * 4 <
                  (SUBSTRING_INDEX(sd.max_three_edge_individual, ',', 1) *
                  SUBSTRING_INDEX(SUBSTRING_INDEX(sd.max_three_edge_individual, ',', 2), ',', -1) *
                  SUBSTRING_INDEX(sd.max_three_edge_individual, ',', -1))
              ) THEN
                sd.price / cal.bundle_num_average
              ELSE
                sd.price
              END
        WHERE i.original_price = 0
          AND m.fba_multi_flag = 0
          AND cal.deliverycode_pre <> :deliveryCodeTemporary
          AND cal.deliverycode_pre <> :deliveryCodeFinished
          {$addWhere}
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
      $stmt->execute();

    } // ------------------------------------------------ 送料加算ここまで


    // '付加費用率
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '付加費用の加算'));
    $sql = <<<EOD
        UPDATE tb_mainproducts as m
        INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN `{$tableName}` as i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        /* ROUNDはついていないが、MySQL側で四捨五入される */
        SET i.baika_tanka = i.baika_tanka / (1 - (:additionalCostRatio / 100))
        WHERE i.original_price = 0
          AND cal.deliverycode_pre <> :deliveryCodeTemporary
          AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':additionalCostRatio', $additionalCostRatio);
    $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
    $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
    $stmt->execute();

    // Yahoo（おとりよせ、Plusnao、Kawa-e-mon共通） 受発注商品検品費用率を加算。
    // 受発注商品は検品を丁寧にする、また欠品・不良品リスクの（自社の）補完 => 高くなる の理屈
    if (in_array($mallCode, [
        self::MALL_CODE_PLUSNAO_YAHOO
      , self::MALL_CODE_KAWA_YAHOO
      , self::MALL_CODE_OTORIYOSE_YAHOO
    ])) {
      $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, 'Yahoo 受発注商品検品費用率計算'));

      $sql = <<<EOD
        UPDATE
        `{$tableName}` i
        INNER JOIN tb_mainproducts_cal cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        SET i.baika_tanka = ROUND(i.baika_tanka / (1 - ( :inspectionCostRate / 100 )), 0)
        WHERE i.original_price = 0
          AND cal.deliverycode_pre IN ( :deliveryCodePurchaseOnOrder )
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':inspectionCostRate', intval($inspectionCostRate), \PDO::PARAM_INT);
      $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER);
      $stmt->execute();
    }

    // 税抜価格補正
    // 税込価格がキリの良い数字になるように税抜価格を調整する
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '税抜価格補正'));
    $sql = <<<EOD
      UPDATE `{$tableName}` AS i
      LEFT JOIN tax_price t ON i.baika_tanka = t.base
      SET i.baika_tanka = COALESCE(t.fixed, i.baika_tanka)
      WHERE i.baika_tanka <> COALESCE(t.fixed, 0)
          AND i.original_price = 0
EOD;
    $db->query($sql);

    // Amazon FBA価格計算、S&L価格計算
    if ($mallCode === self::MALL_CODE_AMAZON) {

      $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, 'Amazon FBA売価更新'));

      // 値下げ込みの基準売価をコピー。
      $sql = <<<EOD
      UPDATE `{$tableName}` AS i
      INNER JOIN tb_mainproducts AS m ON i.daihyo_syohin_code = m.daihyo_syohin_code
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      SET i.fba_baika = cal.baika_tnk
        , i.snl_baika = 0
      WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
        AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
      $stmt->execute();

      // 【FBA価格】 --------------------------------------------
      // 重量・寸法の設定があるものについて、FBA価格を計算。設定がなければ regular 19 + 329 = 348円とする
      // ※下記SQLのCASE v.sizeでINTEGERを切り詰めたというwarningが出るが原因不明。更新自体はできているためそのまま。
      $sql = <<<EOD
        UPDATE `{$tableName}` i
        INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        LEFT JOIN v_amazon_fba_size v ON i.daihyo_syohin_code = v.daihyo_syohin_code
        SET i.fba_baika = CASE
                            WHEN v.size IS NULL THEN i.fba_baika + (19 + 329) /* 348円固定 */
                            ELSE
                                i.fba_baika
                              + v.storage_charge /* 保管手数料 */
                              + CASE v.size
                                  WHEN 'small'   THEN 245
                                  WHEN 'regular' THEN 329
                                  WHEN 'large1'  THEN 530
                                  WHEN 'large2'  THEN 572
                                  WHEN 'large3'  THEN 609
                                  WHEN 'extra_large' THEN 1258
                                END /* 出荷作業手数料 + 発送重量手数料 */
                          END
        WHERE i.original_price = 0
          AND cal.deliverycode_pre <> :deliveryCodeTemporary
          AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
      $stmt->execute();

      // 付加費用率加算
      $sql = <<<EOD
        UPDATE tb_mainproducts as m
        INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN `{$tableName}` as i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        SET i.fba_baika = i.fba_baika / (1 - (:additionalCostRatio / 100))
        WHERE i.original_price = 0
          AND i.fba_baika > 0
          AND cal.deliverycode_pre <> :deliveryCodeTemporary
          AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':additionalCostRatio', $additionalCostRatio);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
      $stmt->execute();

      // 税抜価格補正
      $sql = <<<EOD
      UPDATE `{$tableName}` AS i
      LEFT JOIN tax_price t ON i.fba_baika = t.base
      SET i.fba_baika = COALESCE(t.fixed, i.fba_baika)
      WHERE i.fba_baika <> COALESCE(t.fixed, 0)
          AND i.original_price = 0
EOD;
      $db->query($sql);

      // AmazonのFBAマルチチャネル商品の価格はFBA価格。（他のモールより有利！）
      $sql = <<<EOD
        UPDATE tb_mainproducts as m
        INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN `{$tableName}` as i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        SET i.baika_tanka = i.fba_baika
        WHERE i.original_price = 0
          AND m.fba_multi_flag <> 0
          AND i.fba_baika > 0
          AND cal.deliverycode_pre <> :deliveryCodeTemporary
          AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
      $stmt->execute();


      // / 【FBA価格】 --------------------------------------------

      // 【S&L価格】 --------------------------------------------
      // 重量・寸法の設定があるものについて、S&L価格を計算。設定がなければS&L不可でスルー(0円)
      // 出荷作業手数料は商品価格で分岐するため、一応（ざっくり）シミュレートして決める。 基準金額の -20 は税込み価格補正分（ざっくり。最大値18円差）
      $sql = <<<EOD
        UPDATE `{$tableName}` i
        INNER JOIN tb_mainproducts_cal AS cal ON i.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN v_amazon_snl_size v ON i.daihyo_syohin_code = v.daihyo_syohin_code
        SET i.snl_baika = CASE
                            WHEN v.size IS NULL THEN 0 /* S&L不可 */
                            ELSE
                              COALESCE(
                                  cal.baika_tnk
                                + v.storage_charge /* 保管手数料 */
                                + CASE v.size
                                    WHEN 'letter_a' THEN
                                      CASE
                                        WHEN ((cal.baika_tnk + v.storage_charge + 119) / ((100 - :additionalCostRatio) / 100) * :taxRate) <= (400 - 20) THEN 119
                                        WHEN ((cal.baika_tnk + v.storage_charge + 139) / ((100 - :additionalCostRatio) / 100) * :taxRate) <= (600 - 20) THEN 139
                                        WHEN ((cal.baika_tnk + v.storage_charge + 159) / ((100 - :additionalCostRatio) / 100) * :taxRate) <= (800 - 20) THEN 159
                                        ELSE NULL /* NULL加算で結果を0とする */
                                      END
                                    WHEN 'letter_b' THEN
                                      CASE
                                        WHEN ((cal.baika_tnk + v.storage_charge + 129) / ((100 - :additionalCostRatio) / 100) * :taxRate) <= (400 - 20) THEN 129
                                        WHEN ((cal.baika_tnk + v.storage_charge + 149) / ((100 - :additionalCostRatio) / 100) * :taxRate) <= (600 - 20) THEN 149
                                        WHEN ((cal.baika_tnk + v.storage_charge + 169) / ((100 - :additionalCostRatio) / 100) * :taxRate) <= (800 - 20) THEN 169
                                        ELSE NULL /* NULL加算で結果を0とする */
                                      END
                                    ELSE NULL /* NULL加算で結果を0とする */
                                  END /* 出荷作業手数料 + 発送重量手数料(0円) */
                              , 0)
                          END
        WHERE cal.deliverycode_pre <> :deliveryCodeTemporary
          AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':additionalCostRatio', $additionalCostRatio);
      $stmt->bindValue(':taxRate', $this->getTaxRate());
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
      $stmt->execute();

      // 付加費用率加算
      $sql = <<<EOD
        UPDATE tb_mainproducts as m
        INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
        INNER JOIN `{$tableName}` as i ON m.daihyo_syohin_code = i.daihyo_syohin_code
        SET i.snl_baika = i.snl_baika / (1 - (:additionalCostRatio / 100))
        WHERE i.original_price = 0
          AND i.snl_baika > 0
          AND cal.deliverycode_pre <> :deliveryCodeTemporary
          AND cal.deliverycode_pre <> :deliveryCodeFinished
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':additionalCostRatio', $additionalCostRatio);
      $stmt->bindValue(':deliveryCodeTemporary', TbMainproductsCal::DELIVERY_CODE_TEMPORARY);
      $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED);
      $stmt->execute();

      // 税抜価格補正
      $sql = <<<EOD
      UPDATE `{$tableName}` AS i
      LEFT JOIN tax_price t ON i.snl_baika = t.base
      SET i.snl_baika = COALESCE(t.fixed, i.snl_baika)
      WHERE i.snl_baika <> COALESCE(t.fixed, 0)
          AND i.original_price = 0
EOD;
      $db->query($sql);
      // / 【S&L価格】 --------------------------------------------

      // モール別価格非連動の場合は、
      //    FBA: なにもせず設定そのままで上書き
      //    S&L: S&L売価が設定されていれば上書き。されていなければ、S&L不可なので0円のまま
      $sql = <<<EOD
        UPDATE `{$tableName}` i
        SET i.fba_baika = i.baika_tanka
          , i.snl_baika = CASE
                            WHEN i.snl_baika > 0 THEN i.baika_tanka
                            ELSE 0
                          END
        WHERE i.original_price <> 0
EOD;
      $db->query($sql);
    }

    // 処理完了
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * カレンダーの再作成___
   * @param BatchLogger $logger
   */
  private function addCalendarDate(BatchLogger $logger)
  {
    $db = $this->db;
    $logTitle = 'CreatingFacts';
    $subTitle = 'カレンダーの再作成___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    $current = new \DateTime();
    $current->modify('-1 month');

    $toDate = new \DateTime();
    $toDate->modify('+4 month');

    /* 土日は 0 */
    $sql = <<<EOD
      INSERT IGNORE INTO tb_calendar (
          calendar_date
        , workingday
      )
      VALUES(
          :date
        , CASE WHEN DAYOFWEEK(:date) = 1 OR DAYOFWEEK(:date) = 7
            THEN  0
            ELSE -1
          END
      )
EOD;
    $stmt = $db->prepare($sql);

    while($current < $toDate) {
      $stmt->bindValue(':date', $current->format('Y-m-d'));
      $stmt->execute();
      $current->modify('1 day');
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * メーカー到着予定日を再設定
   * @param BatchLogger $logger
   */
  private function updateVendorArrivalDate(BatchLogger $logger)
  {
    $db = $this->db;
    $logTitle = 'CreatingFacts';
    $subTitle = 'メーカー到着予定日を再設定___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    // 即納日付取得
    $immediateShippingDate = $this->getImmediateShippingDate();

    // 仕入先マスタを1件ずつループして[shippingschedule]を再設定する
    $vendorStmt = $db->query("SELECT sire_code, 出荷修正日数 FROM tb_vendormasterdata");

    while ($vendor = $vendorStmt->fetch(\PDO::FETCH_ASSOC)) {
      // 営業日ベースで明日から（出荷修正日数-1)日目を設定
      // ※ 「メーカー次回出荷予定日」を使う版が使われていない（ついでにバグあり）なので、
      //     「メーカー次回出荷予定日」の利用は廃止する。
      $days = intval($vendor['出荷修正日数']);

      $sql = <<<EOD
        UPDATE tb_vendormasterdata as v
        SET v.shippingschedule = (
            SELECT
              c.calendar_date
            FROM tb_calendar as c
            WHERE c.workingday = - 1
              AND c.calendar_date >= :immediateShippingDate
            ORDER BY c.calendar_date ASC
            LIMIT :days, 1
          )
        WHERE v.sire_code = :sireCode
EOD;
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':immediateShippingDate', $immediateShippingDate->format('Y-m-d'), \PDO::PARAM_STR);
      $stmt->bindValue(':days', ($days - 1), \PDO::PARAM_INT);
      $stmt->bindValue(':sireCode', $vendor['sire_code'], \PDO::PARAM_STR);
      $stmt->execute();
    }

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * 商品出荷設定日
   * @param BatchLogger $logger
   */
  private function updateShippingDate(BatchLogger $logger)
  {
    $db = $this->db;
    $logTitle = 'CreatingFacts';
    $subTitle = '商品出荷設定日';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    // 即納日付取得
    $immediateShippingDate = $this->getImmediateShippingDate();

    // 謎の 0 初期化（ tb_mainproducts.入荷アラート日数 の初期値を0にできない？ ）
    $sql = <<<EOD
      UPDATE tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal on m.daihyo_syohin_code = cal.daihyo_syohin_code
      SET m.入荷アラート日数 = 0
      WHERE m.入荷アラート日数 IS NULL
EOD;
    $db->query($sql);

    // 出荷設定日(sunfactoryset)の初期化
    $stmt = $db->prepare("UPDATE tb_mainproducts_cal SET sunfactoryset = :shippingDate");
    $stmt->bindValue(':shippingDate', $immediateShippingDate->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();

    // 一部即納・受発注のみの場合、出荷修正日数（仕入先） あるいは 出荷アラート日数（商品マスタ）から算出
    $sql = <<<EOD
      UPDATE tb_mainproducts as m
      INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendormasterdata as v   ON m.sire_code = v.sire_code
      SET
        cal.sunfactoryset = CASE
            WHEN v.出荷修正日数 > m.入荷アラート日数 THEN v.shippingschedule
            WHEN COALESCE(m.入荷アラート日数, 0) = 0 THEN cal.sunfactoryset /* 即納日付のまま */
            ELSE GET_FUTURE_WORKING_DATE( :immediateShippingDate, m.入荷アラート日数 )
        END
      WHERE (
           cal.deliverycode_pre = :deliveryCodeReadyPartially
        Or cal.deliverycode_pre = :deliveryCodePurchaseOnOrder
      )
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':immediateShippingDate', $immediateShippingDate->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->execute();

    // 一部即納・受発注のみで、商品の入荷予定日の方が大きい場合、商品の入荷予定日とする
    $sql = <<<EOD
      UPDATE tb_mainproducts as m
      INNER JOIN tb_mainproducts_cal as cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      SET
        cal.sunfactoryset = m.入荷予定日
      WHERE
          cal.sunfactoryset < m.入荷予定日
          AND (
               cal.deliverycode_pre = :deliveryCodeReadyPartially
            Or cal.deliverycode_pre = :deliveryCodePurchaseOnOrder
          )
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodePurchaseOnOrder', TbMainproductsCal::DELIVERY_CODE_PURCHASE_ON_ORDER, \PDO::PARAM_INT);
    $stmt->execute();

    // 価格履歴テーブルの出荷設定日ログ 更新（その日の最後の入荷設定日を残す）
    $sql = <<<EOD
      UPDATE
      {$this->dbLog->getDatabase()}.tb_product_price_log pl
      INNER JOIN tb_mainproducts_cal cal ON pl.log_date = CURRENT_DATE
                                        AND pl.daihyo_syohin_code = cal.daihyo_syohin_code
      SET pl.sunfactoryset = COALESCE(cal.sunfactoryset, '0000-00-00')
EOD;
    $db->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   *
   * 商品出荷設定日調整（旧：rakuten納期管理番号準備）
   *
   * tb_rakuten_nokikanriとその内容のCSVは、現在は楽天のデータとも一致しておらず、用途不明。
   * このメソッドの中では、tb_shipping_fixdateと連携して出荷予定日の営業日補正に使用しているため残している。
   *
   * @param BatchLogger $logger
   * @param $rakutenOutputDir
   * @throws \Doctrine\DBAL\DBALException
   */
  private function prepareRakutenNokiNumber(BatchLogger $logger, $rakutenOutputDir)
  {
    $db = $this->db;
    $logTitle = 'CreatingFacts';
    $subTitle = '商品出荷設定日調整';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    $rakutenCsvName = 'rakuten_noki.csv';

    $db->query("TRUNCATE tb_rakuten_nokikanri");

    $today = new \DateTime();
    $today->setTime(0, 0, 0);

    $sql = <<<EOD
      SELECT
        cal.sunfactoryset
      FROM tb_mainproducts_cal AS cal
      WHERE cal.sunfactoryset IS NOT NULL
        AND cal.sunfactoryset >= :today
      GROUP BY cal.sunfactoryset
      ORDER BY cal.sunfactoryset
EOD;
    $stmtSunfactoryset = $db->prepare($sql);
    $stmtSunfactoryset->bindValue(':today', $today->format('Y-m-d'));
    $stmtSunfactoryset->execute();

    $kanriNo = 0;
    $sql = <<<EOD
        INSERT INTO tb_rakuten_nokikanri (
            納期管理番号
          , 出荷日
          , 見出し
          , 出荷までの日数
        )
        VALUES (
            :kanriNo
          , :sunfactoryset
          , :title
          , :interval
        )
EOD;
    $stmtUpdate = $db->prepare($sql);

    while($row = $stmtSunfactoryset->fetch(\PDO::FETCH_ASSOC)) {
      $sunfactoryset = new \DateTime($row['sunfactoryset']);
      $kanriNo++;
      $interval = $today->diff($sunfactoryset);

      if ($kanriNo == 1) {
        $title = "即～翌日発送(土日祝除)";
      } else {
        $title = $sunfactoryset->format('n/j') . "頃出荷予定";
      }

      $stmtUpdate->bindValue(':kanriNo', $kanriNo, \PDO::PARAM_INT);
      $stmtUpdate->bindValue(':sunfactoryset', $sunfactoryset->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
      $stmtUpdate->bindValue(':title', $title, \PDO::PARAM_STR);
      $stmtUpdate->bindValue(':interval', intval($interval->format('%a')) + 1, \PDO::PARAM_INT);
      $stmtUpdate->execute();
    }

    // 先頭
    $db->query("UPDATE tb_rakuten_nokikanri SET 納期管理番号 = 1000 WHERE 納期管理番号 = 1");

    //    '出荷日の補正
    $db->query("TRUNCATE tb_shipping_fixdate");

    $stmt = $db->query("SELECT * FROM tb_rakuten_nokikanri ORDER BY 納期管理番号");
    $stmtInsert = $db->prepare("INSERT INTO tb_shipping_fixdate ( shipping_date, shipping_fixdate ) VALUES ( :date, :fixDate )");
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $date = new \DateTime($row['出荷日']);
      $fixDate = $this->getWorkingDate($date);

      $stmtInsert->bindValue(':date', $date->format('Y-m-d'));
      $stmtInsert->bindValue(':fixDate', $fixDate->format('Y-m-d'));
      $stmtInsert->execute();
    }

    $sql = <<<EOD
      UPDATE tb_mainproducts_cal AS cal
      INNER JOIN tb_shipping_fixdate AS sf ON cal.sunfactoryset = sf.shipping_date
      SET cal.sunfactoryset = shipping_fixdate
      WHERE cal.sunfactoryset <> shipping_fixdate
EOD;
    $db->query($sql);

    $sql = <<<EOD
      UPDATE tb_rakuten_nokikanri AS rn
      INNER JOIN tb_shipping_fixdate AS sf ON rn.出荷日 = sf.shipping_date
      SET rn.出荷日 = sf.shipping_fixdate
      WHERE rn.出荷日 <> shipping_fixdate
EOD;
    $db->query($sql);

    // CSV出力
    $fs = new FileSystem();
    if (!$fs->exists($rakutenOutputDir)) {
      $fs->mkdir($rakutenOutputDir, 0755);
    }

    $filePath = $rakutenOutputDir . "/" . $rakutenCsvName;
    $fp = fopen($filePath, 'wb');

    $sql = <<<EOD
      SELECT
          納期管理番号
        , DATE_FORMAT(出荷日, '%Y/%m/%d') AS 出荷日
        , 見出し
        , 出荷までの日数
      FROM tb_rakuten_nokikanri
      ORDER BY 納期管理番号
EOD;
    $stmt = $db->query($sql);
    $stringUtil = new StringUtil();
    // ヘッダ行は不要
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $line = $stringUtil->convertArrayToCsvLine($row, ['納期管理番号', '出荷日', '見出し', '出荷までの日数']) . "\r\n";
      $line = mb_convert_encoding($line, 'SJIS-WIN', 'UTF-8');
      fwrite($fp, $line);
    }
    fclose($fp);

    // XServer への FTPアップロードは不要に付き実装なし。

    $this->updateUpdateRecordTable(self::UPDATE_RECORD_NUMBER_UPDATE_RAKUTEN_NOKI_KANRI);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * 即納可能な項目選択肢リストを作成___
   * ※共通処理スキップフラグ処理は行わない。（出品商品全てに実施）
   * @param BatchLogger $logger
   */
  private function updateReadyProductChoiceItemsHtmlString(BatchLogger $logger)
  {
    $db = $this->db;
    $logTitle = 'CreatingFacts';
    $subTitle = '即納可能な項目選択肢リストを作成___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    $db->query("TRUNCATE tb_mainproducts_tep");

    $sql = <<<EOD
      UPDATE tb_mainproducts_cal as cal
      SET cal.list_some_instant_delivery = NULL
      WHERE cal.list_some_instant_delivery IS NOT NULL
EOD;
    $db->query($sql);

    $db->query("SET SESSION group_concat_max_len = 10240");

    $sql = <<<EOD
      INSERT
      INTO tb_mainproducts_tep (
          daihyo_syohin_code
        , list_some_instant_delivery
      )
      SELECT
          pci.daihyo_syohin_code
        , GROUP_CONCAT(
            '<br>'
          , pci.colname
          , ' '
          , pci.rowname
          ORDER BY
            pci.並び順No SEPARATOR ''
        )
      FROM tb_productchoiceitems AS pci
      INNER JOIN tb_mainproducts_cal AS cal ON pci.daihyo_syohin_code = cal.daihyo_syohin_code
      WHERE
        pci.フリー在庫数 > 0
      GROUP BY
        cal.daihyo_syohin_code
EOD;
    $db->query($sql);

    $sql = <<<EOD
      UPDATE tb_mainproducts_cal AS cal
      INNER JOIN tb_mainproducts_tep AS mt ON cal.daihyo_syohin_code = mt.daihyo_syohin_code
      SET cal.list_some_instant_delivery = mt.list_some_instant_delivery
EOD;
    $db->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * 自動タイトルの再設定___
   * @param BatchLogger $logger
   */
  private function updateTitleParts(BatchLogger $logger)
  {
    $db = $this->db;
    $logTitle = 'CreatingFacts';
    $subTitle = '自動タイトルの再設定___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    // ねこポスはここは省略。（そもそももう余り使っていない。）

    // '送料設定：0宅配 [別]　1宅配 [込]　2個別送料　3メール [込]
    $db->query("TRUNCATE tb_title_parts");
    $sql = <<<EOD
      INSERT INTO tb_title_parts (
          daihyo_syohin_code
        , front_title
        , back_title
      )
      SELECT
          m.daihyo_syohin_code
        , CONCAT(
             COALESCE(:itemFrontCacheCopy, '') /* NULLの罠 */
           , CASE WHEN cal.deliverycode = :deliveryCodeFinished
                  THEN '【完売御礼】'
                  ELSE ''
             END
           , CASE WHEN cal.deliverycode = :deliveryCodeReady
                  THEN '【即納】'
                  ELSE ''
             END
           , CASE WHEN cal.deliverycode = :deliveryCodeReadyPartially
                  THEN '【一部即納】'
                  ELSE ''
             END
           , CASE WHEN cal.endofavailability IS NULL
                   AND ( v.cost_rate >= v.guerrilla_margin OR m.手動ゲリラSALE <> 0 )
                  THEN '【ゲリラSALE】'
                  ELSE ''
             END
           , CASE WHEN m.登録日時 IS NULL /* このIS NULL 条件は元の実装には無いがSQL的に必然的にこうなる。実装が意図通りかは不明だが、出力を合わせるためあえて記述。 */
                    OR (m.登録日時 >= DATE_ADD(CURRENT_DATE, INTERVAL -7 DAY)) /* NOW()をCURRENT_DATEに変更。時刻は無視し、日単位で判定 */
                  THEN '【新着】'
                  ELSE ''
             END
           , CASE WHEN 0 <> :rakutenTitleOutlet AND cal.outlet <> 0
                  THEN '【アウトレット】'
                  ELSE ''
             END
           , CASE WHEN cal.review_point_ave >= 4
                  THEN '【レビュー★★★★★】'
                  ELSE ''
             END
        ) AS front_title
        , COALESCE(CONCAT(
            '◎本日注文'
          , DATE_FORMAT(cal.sunfactoryset,'%c')
          , '月'
          , DATE_FORMAT(cal.sunfactoryset,'%e')
          , '日頃出荷予定'
        ), '') AS back_title
      FROM tb_mainproducts AS m
      INNER JOIN tb_mainproducts_cal AS cal ON m.daihyo_syohin_code = cal.daihyo_syohin_code
      INNER JOIN tb_vendormasterdata AS v ON m.sire_code = v.sire_code
EOD;

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':itemFrontCacheCopy', $this->getSettingValue('ITEM_FRONT_CACHE_COPY'), \PDO::PARAM_STR);
    $stmt->bindValue(':deliveryCodeFinished', TbMainproductsCal::DELIVERY_CODE_FINISHED, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->bindValue(':rakutenTitleOutlet', $this->getSettingValue('RAKUTEN_TITLE_OUTLET'), \PDO::PARAM_INT);
    $stmt->execute();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * 表示順位の設定___
   * @param BatchLogger $logger
   */
  private function updatePriority(BatchLogger $logger)
  {
    $db = $this->db;
    $logTitle = 'CreatingFacts';
    $subTitle = '表示順位の設定___';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '開始'));

    // 商品管理フォーム: 「販売数」の取得範囲日数。
    // ここで固定値にリセット
    $this->setRecordIniInteger(self::E_INI_CODE_INTERVAL, 14);

    // sales_volume(販売数量)を０クリア
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal as cal
      SET cal.sales_volume = 0
      WHERE cal.sales_volume > 0
EOD;
    $db->query($sql);

    // sales_volume(販売数量)を計算
    $sql = <<<EOD
      UPDATE
      tb_mainproducts_cal cal
      INNER JOIN (
        SELECT
            T.daihyo_syohin_code
          , SUM(T.受注数合計) AS 受注数合計
        FROM
        (
          SELECT
              A.daihyo_syohin_code
            , SUM(A.受注数) AS 受注数合計
          FROM tb_sales_detail_analyze A
          WHERE A.キャンセル区分 = '0'
            AND A.明細行キャンセル = '0'
            AND A.受注日 >= DATE_ADD(CURDATE(), INTERVAL - 14 DAY)
            AND A.`店舗コード` <> 18
          GROUP BY A.daihyo_syohin_code

          UNION ALL
          SELECT
              s.daihyo_syohin_code
            , SUM(s.num_total) AS 受注数合計
          FROM tb_shoplist_daily_sales s
          WHERE s.order_date >= DATE_ADD(CURDATE(), INTERVAL - 14 DAY)
          GROUP BY s.daihyo_syohin_code

        ) T
        GROUP BY T.daihyo_syohin_code
      ) T ON cal.daihyo_syohin_code = T.daihyo_syohin_code
      SET cal.sales_volume = T.受注数合計
EOD;
    $db->query($sql);

    // 'priority(優先順位)を計算して更新
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal as cal
      INNER JOIN tb_mainproducts as m ON cal.daihyo_syohin_code = m.daihyo_syohin_code
        SET cal.priority = (
            IF(cal.deliverycode_pre = :deliveryCodeReady, 3, 0)
          + IF(cal.deliverycode_pre = :deliveryCodeReadyPartially, 1, 0)
          + cal.sales_volume
          + 7 - IF(DATEDIFF(CURRENT_DATE, m.登録日時) > 7, 7, DATEDIFF(CURRENT_DATE, m.登録日時))
        )
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':deliveryCodeReady', TbMainproductsCal::DELIVERY_CODE_READY, \PDO::PARAM_INT);
    $stmt->bindValue(':deliveryCodeReadyPartially', TbMainproductsCal::DELIVERY_CODE_READY_PARTIALLY, \PDO::PARAM_INT);
    $stmt->execute();

    // ==============================
    // Call setStatus("発注先発注可能行を計算しています。")
    // ==============================
    // setnum(発注先発注可能行)の計算
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal as cal
      INNER JOIN (
          SELECT
              v.daihyo_syohin_code
            , sum(v.setafter) AS setafter_sum
          FROM
            tb_vendoraddress as v
          GROUP BY
            v.daihyo_syohin_code
      ) AS SUB ON cal.daihyo_syohin_code = SUB.daihyo_syohin_code
      SET
        cal.setnum = SUB.setafter_sum
EOD;
    $db->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, $subTitle, '終了'));
  }

  /**
   * 値下げ開始日の更新
   *   CSV出力共通処理
   *   値下げ一覧 再計算処理
   */
  public function updateDiscountBaseDate()
  {
    $db = $this->db;

    // 値下げ開始日の更新。 値下げ開始日がNULL、あるいは計算結果でより大きくなるデータについて更新を行う
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal AS cal
      INNER JOIN tb_discount_setting s ON s.id = 1
      SET cal.discount_base_date = DATE_ADD(cal.last_orderdate, INTERVAL s.discount_excluded_days DAY)
      WHERE cal.last_orderdate != '0000-00-00'
        AND (
             cal.discount_base_date IS NULL
          OR DATE_ADD(cal.last_orderdate, INTERVAL s.discount_excluded_days DAY) > cal.discount_base_date
        )
EOD;
    $db->query($sql);
  }

  /**
   * 引数で指定された代表商品コードの商品について、最終入荷日を本日に設定する。
   *
   * イレギュラーな商品在庫の獲得(なぜかわからないが、倉庫にあったなど)の場合に、入荷処理を通さず直接DBを更新する事で
   * 入荷日未設定のデータが発生する場合がある。その対処となる。）
   * 引数の配列が空の場合はエラーが発生する。空の場合は呼び出し元で判定し、呼び出しを行わない事。
   * @param $daihyoSyohinCodeList 代表商品コードの配列（NOT NULL）
   */
  public function updateLastOrderdateToday(array $daihyoSyohinCodeList)
  {
    $db = $this->db;
    $inClause = substr(str_repeat(',?', count($daihyoSyohinCodeList)), 1); // '?,?,?'
    $sql = <<<EOD
      UPDATE tb_mainproducts_cal AS cal
      SET cal.last_orderdate = CURRENT_DATE
      WHERE (cal.last_orderdate IS NULL OR cal.last_orderdate = '0000-00-00')
        AND cal.daihyo_syohin_code IN ({$inClause})
EOD;

    $stmt = $db->prepare($sql);
    $stmt->execute($daihyoSyohinCodeList);
  }

  // ======================================================
  // モール関連
  // ======================================================
  /**
   * モール情報取得
   * 引数は定数値 MALL_ID_XX
   * @param int $mallId
   * @return TbShoppingMall
   */
  public function getShoppingMall($mallId)
  {
    /** @var EntityRepository $repo */
    $repo = $this->doctrine->getRepository('MiscBundle:TbShoppingMall');

    /** @var TbShoppingMall|null $shoppingMall */
    $shoppingMall = $repo->find($mallId);
    return $shoppingMall;
  }

  /**
   * @param string $mallCode モールコード
   * @return string 商品タイトルカラム名
   *
   * ※未使用につきコメントアウト
   */
//  private function getMallProductTitleColumn($mallCode)
//  {
//    switch ($mallCode) {
//      case self::MALL_CODE_RAKUTEN:
//        $result = "楽天タイトル";
//        break;
//      case self::MALL_CODE_BIDDERS:
//        $result = "bidders_title";
//        break;
//      case self::MALL_CODE_PLUSNAO_YAHOO:
//        $result = "yahoo_title";
//        break;
//      case self::MALL_CODE_KAWA_YAHOO:
//        $result = "yahoo_title";
//        break;
//      case self::MALL_CODE_Q10:
//        $result = "q10_title";
//        break;
//      case self::MALL_CODE_AMAZON:
//        $result = "amazon_title";
//        break;
//      case self::MALL_CODE_SS:
//        $result = "ss_title";
//        break;
//      case self::MALL_CODE_PPM:
//        $result = "ppm_title";
//        break;
//      case self::MALL_CODE_SHOPLIST:
//        $result = "title";
//        break;
//      default:
//        throw new RuntimeException('invalid mall code');
//    }
//
//    return $result;
//  }

  /**
   * @return array 未使用につきコメントアウト
   *
   * 未使用につきコメントアウト
   */
//  private Function getMallCodes()
//  {
//    return [
//        self::MALL_CODE_AMAZON
//      , self::MALL_CODE_BIDDERS
//      , self::MALL_CODE_Q10
//      , self::MALL_CODE_RAKUTEN
//      , self::MALL_CODE_PLUSNAO_YAHOO
//      , self::MALL_CODE_KAWA_YAHOO
//      , self::MALL_CODE_SS
//      , self::MALL_CODE_PPM
//      , self::MALL_CODE_SHOPLIST
//    ];
//  }

  /**
   * モール別商品テーブル名
   * @param string $mallCode
   * @return string テーブル名
   */
  public function getMallTableName($mallCode)
  {
    switch ($mallCode) {
      case self::MALL_CODE_RAKUTEN:
        $result = "tb_rakuteninformation";
        break;
      case self::MALL_CODE_RAKUTEN_MOTTO:
        $result = "tb_rakuten_motto_information";
        break;
      case self::MALL_CODE_RAKUTEN_LAFOREST:
        $result = "tb_rakuten_laforest_information";
        break;
      case self::MALL_CODE_RAKUTEN_DOLCISSIMO:
        $result = "tb_rakuten_dolcissimo_information";
        break;
      case self::MALL_CODE_RAKUTEN_GEKIPLA:
        $result = "tb_rakuten_gekipla_information";
        break;
      case self::MALL_CODE_CUBE:
        $result = "tb_cube_information";
        break;
      case self::MALL_CODE_BIDDERS:
        $result = "tb_biddersinfomation";
        break;
      case self::MALL_CODE_PLUSNAO_YAHOO:
        $result = "tb_yahoo_information";
        break;
      case self::MALL_CODE_KAWA_YAHOO:
        $result = "tb_yahoo_kawa_information";
        break;
      case self::MALL_CODE_OTORIYOSE_YAHOO:
        $result = "tb_yahoo_otoriyose_information";
        break;
      case self::MALL_CODE_Q10:
        $result = "tb_qten_information";
        break;
      case self::MALL_CODE_AMAZON:
        $result = "tb_amazoninfomation";
        break;
      case self::MALL_CODE_SS:
        $result = "tb_ss_information";
        break;
      case self::MALL_CODE_PPM:
        $result = "tb_ppm_information";
        break;
      case self::MALL_CODE_SHOPLIST:
        $result = "tb_shoplist_information";
        break;
      case self::MALL_CODE_AMAZON_COM:
        $result = "tb_amazon_com_information";
        break;
      default:
        throw new RuntimeException('invalid mall code');
    }

    return $result;
  }

  /// モールIDの取得
  public function getMallIdByMallCode($mallCode)
  {
    $mallId = array_search($mallCode, self::$MALL_CODE_LIST);
    // 楽天ペイ用 仮想コードの変換
    if ($mallId === false) {
      switch ($mallCode) {
        case self::MALL_CODE_RAKUTEN_PAY:
          $mallId = array_search(self::MALL_CODE_RAKUTEN, self::$MALL_CODE_LIST);
          break;
        case self::MALL_CODE_RAKUTEN_PAY_MINNA:
          $mallId = array_search(self::MALL_CODE_RAKUTEN_MINNA, self::$MALL_CODE_LIST);
          break;
        case self::MALL_CODE_RAKUTEN_PAY_MOTTO:
          $mallId = array_search(self::MALL_CODE_RAKUTEN_MOTTO, self::$MALL_CODE_LIST);
          break;
        case self::MALL_CODE_RAKUTEN_PAY_LAFOREST:
          $mallId = array_search(self::MALL_CODE_RAKUTEN_LAFOREST, self::$MALL_CODE_LIST);
          break;
        case self::MALL_CODE_RAKUTEN_PAY_DOLCISSIMO:
          $mallId = array_search(self::MALL_CODE_RAKUTEN_DOLCISSIMO, self::$MALL_CODE_LIST);
          break;
        case self::MALL_CODE_RAKUTEN_PAY_GEKIPLA:
          $mallId = array_search(self::MALL_CODE_RAKUTEN_GEKIPLA, self::$MALL_CODE_LIST);
          break;
      }
    }
    return $mallId;
  }

  /**
   * モールIDの取得
   * @param $neMallId
   * @return string|null
   */
  public function getMallCodeByNeMallId($neMallId)
  {
    /** @var EntityRepository $repo */
    $repo = $this->doctrine->getRepository('MiscBundle:TbShoppingMall');

    /** @var TbShoppingMall|null $shoppingMall */
    $shoppingMall = $repo->findOneBy([ 'neMallId' => $neMallId ]);

    $mallCode = null;
    if ($shoppingMall && isset(self::$MALL_CODE_LIST[$shoppingMall->getMallId()])) {
      $mallCode = self::$MALL_CODE_LIST[$shoppingMall->getMallId()];
    }

    return $mallCode;
  }

  /**
   * 即納日付の更新
   * @param \DateTimeInterface $date
   * @return \DateTimeInterface
   */
  public function updateImmediateShippingDate($date)
  {
    $this->updateSettingValue('IMMEDIATE_SHIPPING_DATE', $date->format('Ymd'));
  }

  /**
   * 即納日付の取得
   * @return \DateTime
   */
  public function getImmediateShippingDate()
  {
    $date = $this->getSettingValue('IMMEDIATE_SHIPPING_DATE');
    $date = (strlen($date) == 8) ? new \DateTime($date) : new \DateTime();
    $date = $date->setTime(0, 0, 0);
    return $date;
  }

  /**
   * 即納日付までの日数を取得
   * @param \DateTimeInterface $date
   * @return int
   */
  public function getDaysForImmediateShippingDate($date = null)
  {
    if (is_null($date)) {
      $date = new \DateTimeImmutable();
    } else {
      $date = clone $date;
    }
    $date = $date->setTime(0, 0, 0);

    $diff = $date->diff($this->getImmediateShippingDate());
    $days = intval($diff->format('%R%a'));
    if ($days <= 0) {
      $days = 1;
    }
    return $days;
  }

  /**
   * 直近の営業日
   * '指定日以降、直後の営業日を取得する（指定日を含む）
   * @param \DateTime
   * @return \DateTime
   */
  public function getWorkingDate(\DateTime $baseDate)
  {
    $db = $this->db;
    $date = clone $baseDate;

    $sql = <<<EOD
      SELECT
        calendar_date
      FROM tb_calendar
      WHERE calendar_date >= :baseDate
        AND workingday <> 0
      ORDER BY calendar_date
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':baseDate', $date->format('Y-m-d'));
    $stmt->execute();
    $result = $stmt->fetchColumn();

    if (!$result) {
      return $date;
    } else {
      $ret = new \DateTime($result);
      $ret->setTime(0, 0, 0);
      return $ret;
    }
  }

  /**
   * N営業日後 日付取得
   * 指定日の翌日を含むN営業日後の日付を取得($days = 1で翌営業日)
   * @param \DateTimeInterface $baseDate
   * @param $days
   * @return \DateTime
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getWorkingDateAfterDays(\DateTimeInterface $baseDate, $days)
  {
    $db = $this->db;
    $date = clone $baseDate;

    $sql = <<<EOD
      SELECT
        calendar_date
      FROM tb_calendar
      WHERE calendar_date > :baseDate
        AND workingday <> 0
      ORDER BY calendar_date
      LIMIT 1 OFFSET :days
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':baseDate', $date->format('Y-m-d'));
    $stmt->bindValue(':days', $days - 1, \PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchColumn();

    if (!$result) {
      return $date;
    } else {
      $ret = new \DateTime($result);
      $ret->setTime(0, 0, 0);
      return $ret;
    }
  }

  /**
   * N営業日前 日付取得
   * 指定日からN営業日前の日付を取得（$days = 1 で昨営業日）
   * @param \DateTimeInterface $baseDate
   * @param $days
   * @return \DateTime|\DateTimeInterface
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getWorkingDateBeforeDays(\DateTimeInterface $baseDate, $days)
  {
    $db = $this->db;
    $date = clone $baseDate;

    $sql = <<<EOD
      SELECT
        calendar_date
      FROM tb_calendar c
      WHERE c.calendar_date < :baseDate
        AND c.workingday <> 0
      ORDER BY c.calendar_date DESC
      LIMIT 1 OFFSET :days
EOD;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':baseDate', $date->format('Y-m-d'));
    $stmt->bindValue(':days', $days - 1, \PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchColumn();

    if (!$result) {
      return $date;
    } else {
      $ret = new \DateTime($result);
      $ret->setTime(0, 0, 0);
      return $ret;
    }
  }


  /**
   * 楽天画像URL 1～6取得処理
   * @param string $code 代表商品コード
   * @return array 1～6 までのURL配列
   */
  public function getRakutenImageUrl($code)
  {
    $indexes = range(1, 6);
    $result = [];
    foreach($indexes as $i) {
      $result[$i] = null;
    }

    $product = $this->doctrine->getRepository('MiscBundle:TbMainproducts')->find($code);
    if ($product) {
      foreach($result as $i => $x) {
        $getter = sprintf('getImageP%dAddress', $i);
        $result[$i] = $product->{$getter}();
      }
    }

    return $result;
  }



  /**
   * LIKE検索用の文字列エスケープ処理（メタ文字無効化）
   *
   * @param string $str
   * @return string
   */
  public function escapeLikeString($str)
  {
    return str_replace('_', '\\_', str_replace('%', '\\%', $str));
  }

  /**
   * 楽天商品詳細ページ URL取得
   * @param $daihyoSyohinCode
   * @return string
   */
  public function getRakutenProductDetailUrl($daihyoSyohinCode)
  {
    return sprintf('http://item.rakuten.co.jp/plusnao/%s/', strtolower($daihyoSyohinCode));
  }

  /**
   * nint商品詳細ページ URL取得
   * @param $daihyoSyohinCode
   * @return string
   */
  public function getNintProductDetailUrl($daihyoSyohinCode)
  {
    return sprintf('http://ec.nint.jp/shop/itemRankTrend/242190?itemCode=%s', strtolower($daihyoSyohinCode));
  }

  /**
   * NE更新カラムリセット 処理
   */
  public function resetNextEngineUpdateColumn(BatchLogger $logger)
  {
    $logTitle = 'NE更新カラムリセット';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $this->db->query("UPDATE tb_mainproducts SET tb_mainproducts.NE更新カラム = NULL");

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
  }

  /**
   * 画像存在フラグ更新処理
   * 画像チェック時に更新される
   * @param string $table
   * @param string $code
   * @param int|bool $value
   * @throws \Doctrine\DBAL\DBALException
   */
  public function updateExistImage($table, $code, $value)
  {
    $value = (bool)$value ? -1 : 0; // 値の統一 ※ここは Q10 のみ（？） -1/0 で他は 1/0 ？ 頑張って統一する。

    if (!in_array($table, [
        'tb_yahoo_information'
      , 'tb_yahoo_kawa_information'
      , 'tb_yahoo_otoriyose_information'
      , 'tb_ppm_information'
      , 'tb_qten_information'
      , 'tb_ss_information'
    ])) {
      throw new InvalidArgumentException('invalid table name was given. [' . $table . ']');
    }

    if (!strlen($code)) {
      throw new InvalidArgumentException('invalid code was given. [' . $code . ']');
    }

    $sql = "UPDATE `$table` SET `exist_image` = :value WHERE daihyo_syohin_code = :code";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':value', $value, \PDO::PARAM_INT);
    $stmt->bindValue(':code', $code, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * 楽天画像ダウンロード処理
   * @param BatchLogger $logger
   * @param \GuzzleHttp\Client $httpClient
   * @param string $daihyoSyohinCode
   * @param string $targetDirectory
   * @return bool
   */
  public function downloadRakutenImages($logger, $httpClient, $daihyoSyohinCode, $targetDirectory)
  {
    $urls = $this->getRakutenImageUrl($daihyoSyohinCode);

    $filePaths = [];
    $requests = [];
    foreach($urls as $index => $url) {
      if (!strlen($url)) {
        continue;
      }

      // 保存ファイルパス
      $saveFilePath = $index == 1
                    ? sprintf('%s/%s.jpg', $targetDirectory, $daihyoSyohinCode)
                    : sprintf('%s/%s_%d.jpg', $targetDirectory, $daihyoSyohinCode, ($index - 1));

      $filePaths[] = $saveFilePath;
      $requests[] = new \GuzzleHttp\Psr7\Request('GET', $url);
    }

    $result = [
        'success' => []
      , 'error' => []
    ];

    $pool = new \GuzzleHttp\Pool($httpClient, $requests, [
        'concurrency' => 6 // 並列数

      , 'fulfilled' => function($response, $index) use ($filePaths, &$result) {
        /** @var \GuzzleHttp\Psr7\Response $response */

        $contentTypes = $response->getHeader('Content-Type');
        $contentType = $contentTypes ? reset($contentTypes) : null;

        $filePath = $filePaths[$index];
        if ($response->getStatusCode() === 200 && strlen($response->getBody()) && preg_match('|image/|', $contentType)) {

          file_put_contents($filePath, $response->getBody());

          $result['success'][] = $filePath;
        } else {
          // 失敗の場合は特にすることがない。
          $result['error'][] = sprintf('not a image. [%s]', $filePath);
        }
      }
      , 'rejected' => function ($reason, $index) use ($logger, &$result) {
        /** @var \Exception $reason */
        $logger->warning($reason->getMessage());
        $result['error'][] = $reason->getMessage();
      }
    ]);

    // すべて取得して戻る
    $pool->promise()->wait();

    return $result;
  }


  /**
   * Yahoo モール別情報テーブル名取得
   * @param string $exportTarget (plusnao or kawaemon or otoriyose)
   * @return string
   */
  public function getYahooTargetTableName($exportTarget)
  {
    switch($exportTarget) {
      case ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO:
        return $this->getMallTableName(self::MALL_CODE_PLUSNAO_YAHOO);
      case ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON:
        return $this->getMallTableName(self::MALL_CODE_KAWA_YAHOO);
      case ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE:
        return $this->getMallTableName(self::MALL_CODE_OTORIYOSE_YAHOO);
      default:
        throw new RuntimeException('invalid export target. [' . $exportTarget . ']');
    }
  }

  /**
   * Yahoo モール別商品在庫テーブル名取得
   * @param string $exportTarget (plusnao or kawaemon or otoriyose)
   * @return string
   */
  public function getYahooProductStockTableName($exportTarget)
  {
    switch ($exportTarget) {
      case ExportCsvYahooCommand::EXPORT_TARGET_PLUSNAO:
        $result = ExportCsvYahooCommand::PRODUCT_STOCK_TABLE_PLUSNAO;
        break;
      case ExportCsvYahooCommand::EXPORT_TARGET_KAWAEMON:
        $result = ExportCsvYahooCommand::PRODUCT_STOCK_TABLE_KAWAEMON;
        break;
      case ExportCsvYahooOtoriyoseCommand::EXPORT_TARGET_OTORIYOSE:
        $result = ExportCsvYahooOtoriyoseCommand::PRODUCT_STOCK_TABLE_OTORIYOSE;
        break;
      default:
        throw new RuntimeException('invalid mall code');
    }
    return $result;
  }

  /**
   * 大量 INSER OR UPDATE 処理
   * @param MultiInsertUtil $originalInsertBuilder
   * @param \Doctrine\DBAL\Connection $db
   * @param array|object $data
   * @param callable $fetchItemProcess
   * @param string $loopType
   * @return int 件数
   */
  public function multipleInsert($originalInsertBuilder, $db, $data, $fetchItemProcess, $loopType = 'foreach')
  {
    $total = 0;
    $builder = null;

    $count = 0;

    // ひとまず foreachのみ実装
    if ($loopType == 'foreach') {
      foreach($data as $ele) {

        if (!isset($builder)) {
          $builder = clone $originalInsertBuilder;
        }

        $item = $fetchItemProcess($ele);
        // もし配列以外が帰ってきたらスキップ
        if (!is_array($item) || !$item) {
          continue;
        }

        $builder->bindRow($item);

        $total++;

        // 分割 INSERT（を利用したUPDATE） (1000件ずつ)
        if (++$count >= 1000) {
          if (count($builder->binds())) {
            $stmt = $db->prepare($builder->toQuery());
            $builder->bindValues($stmt);
            $stmt->execute();
          } else {
            // do nothing
          }

          unset($builder);
          $count = 0;
        }
      }
    } else {
      throw new \RuntimeException('not yet implemented!!');
    }

    // INSERT 残り
    if ($count && isset($builder) && count($builder->binds())) {
      $stmt = $db->prepare($builder->toQuery());
      $builder->bindValues($stmt);
      $stmt->execute();
    }

    return $total;
  }

}

// 汎用例外
class DbCommonUtilException extends RuntimeException {}
class ProcessLockWaitException extends DbCommonUtilException {}
