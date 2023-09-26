<?php
/**
 * バッチ処理 モール受注CSV変換処理
 */

namespace BatchBundle\Command;

use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\SymfonyUsers;
use MiscBundle\Entity\TbShoppingMall;
use MiscBundle\Entity\TbShippingdivision;
use MiscBundle\Entity\TbDeliveryMethod;
use MiscBundle\Entity\TbDeliverySplitRule;
use MiscBundle\Entity\TbPrefecture;
use MiscBundle\Entity\Repository\TbPrefectureRepository;
use MiscBundle\Util\DbCommonUtil;
use MiscBundle\Util\MultiInsertUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertMallOrderCsvDataCommand extends ContainerAwareCommand
{
  use CommandBaseTrait;

  /** @var SymfonyUsers */
  private $account;

  private $results;

  /** @var \DateTime|null */
  private $converted = null; // 変換日時

  const IMMEDIATE_SHIPPING_MESSAGE = '1〜2日以内に発送予定（店舗休業日を除く） ';

  protected function configure()
  {
    $this
      ->setName('batch:convert-mall-order-csv-data')
      ->setDescription('モール受注CSV変換処理')
      ->addOption('account', null, InputOption::VALUE_OPTIONAL, '実行アカウント symfony_users.id')
      ->addOption('mall-code', null, InputOption::VALUE_OPTIONAL, '対象モールコード 省略時は全て', null)
      ->addOption('do-upload', null, InputOption::VALUE_OPTIONAL, 'NextEngineへのアップロード', 1)
      ->addOption('force', null, InputOption::VALUE_OPTIONAL, '受注明細がない明細も変換する', '0')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setInput($input);

    $container = $this->getContainer();
    $logger = $this->getLogger();
    $logger->info('モール受注CSV変換処理を開始しました。');

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
        , 'mallCode' => $input->getOption('mall-code')
        , 'upload' => $input->getOption('do-upload')
        , 'uploadResults' => []
      ];

      $logExecTitle = sprintf('モール受注CSV変換処理');
      $logger->setExecTitle($logExecTitle);
      $logger->initLogTimer();
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '開始'));

      $targetMalls = $input->getOption('mall-code') ? [ $input->getOption('mall-code') ] : [
          DbCommonUtil::MALL_CODE_PPM
        , DbCommonUtil::MALL_CODE_AMAZON
        , DbCommonUtil::MALL_CODE_RAKUTEN
        , DbCommonUtil::MALL_CODE_BIDDERS
        , DbCommonUtil::MALL_CODE_PLUSNAO_YAHOO
        , DbCommonUtil::MALL_CODE_KAWA_YAHOO
        , DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO
        , DbCommonUtil::MALL_CODE_Q10
        , DbCommonUtil::MALL_CODE_RAKUTEN_MINNA
        , DbCommonUtil::MALL_CODE_RAKUTEN_PAY
        , DbCommonUtil::MALL_CODE_RAKUTEN_PAY_MINNA
        , DbCommonUtil::MALL_CODE_RAKUTEN_MOTTO
        , DbCommonUtil::MALL_CODE_RAKUTEN_LAFOREST
        , DbCommonUtil::MALL_CODE_RAKUTEN_DOLCISSIMO
        , DbCommonUtil::MALL_CODE_RAKUTEN_GEKIPLA

        // ここには、一旦EC-CUBEは入れない。明示実行のみとする。
      ];

      $logger->info('target malls: ' . print_r($targetMalls, true));

      foreach($targetMalls as $mallCode) {
        switch ($mallCode) {
          case DbCommonUtil::MALL_CODE_PPM:
            $this->processPpm();
            break;
          case DbCommonUtil::MALL_CODE_AMAZON:
            $this->processAmazon();
            break;
          case DbCommonUtil::MALL_CODE_RAKUTEN:
          case DbCommonUtil::MALL_CODE_RAKUTEN_MINNA:
          case DbCommonUtil::MALL_CODE_RAKUTEN_MOTTO:
          case DbCommonUtil::MALL_CODE_RAKUTEN_LAFOREST:
          case DbCommonUtil::MALL_CODE_RAKUTEN_DOLCISSIMO:
          case DbCommonUtil::MALL_CODE_RAKUTEN_GEKIPLA:
            $this->processRakuten($mallCode);
            break;
          case DbCommonUtil::MALL_CODE_RAKUTEN_PAY:
          case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_MINNA:
          case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_MOTTO:
          case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_LAFOREST:
          case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_DOLCISSIMO:
          case DbCommonUtil::MALL_CODE_RAKUTEN_PAY_GEKIPLA:
            $this->processRakutenPay($mallCode);
            break;

          case DbCommonUtil::MALL_CODE_BIDDERS:
            $this->processDena();
            break;
          case DbCommonUtil::MALL_CODE_PLUSNAO_YAHOO:
          case DbCommonUtil::MALL_CODE_KAWA_YAHOO:
          case DbCommonUtil::MALL_CODE_OTORIYOSE_YAHOO:
            $this->processYahoo($mallCode);
            break;
          case DbCommonUtil::MALL_CODE_Q10:
            $this->processQ10();
            break;
          case DbCommonUtil::MALL_CODE_EC01:
          case DbCommonUtil::MALL_CODE_EC02:
            $this->processEcCube($mallCode);
            break;
        }
      }

      $logger->info('check 2');

      // NextEngine アップロード
      $logger->info($this->converted ? $this->converted->format('Y-m-d H:i:s') : 'no data!');

      if ($input->getOption('do-upload') && $this->converted) {

        $logger->info('check 3');

        $dbMain = $this->getDb('main');
        $commonUtil = $this->getDbCommonUtil();

        /** @var NextEngineMallProcess $mallProcess */
        $mallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

        foreach($targetMalls as $mallCode) {
          $logger->info('check 4');

          $mallId = $commonUtil->getMallIdByMallCode($mallCode);
          $mall = $commonUtil->getShoppingMall($mallId);
          if (!$mall) {
            throw new \RuntimeException('invalid mall_code. ' . $mallCode );
          }

          // CSV出力
          // 指定変換日時および、既存の未ダウンロード受注出力。
          $newAlwaysUpload = true;
          $exportFiles = $mallProcess->generateMallOrderCsv($mall->getNeMallId(), $this->converted->format('YmdHis'), 'file', $newAlwaysUpload);

          $logger->info('ne_mall_order:' . $mallCode . ':' . ($exportFiles ? print_r($exportFiles, true) : '(none)'));

          // アップロード実行
          if ($exportFiles) {
            $hasError = false;
            foreach($exportFiles as $exportFile) {
              $result = $mallProcess->apiUploadMallOrderCsv($mall, $exportFile);
              $this->results['uploadResults'][] = $result;
              if ($result['status'] == 'ok') {

                $logger->info('ne_mall_order: upload success. ' . $mallId . ':' . $exportFile->getPathname());

              } else {
                // エラーを記録して次へ。
                $hasError = true;
                $logger->addDbLog(
                    $logger->makeDbLog($logExecTitle, $logExecTitle, 'アップロードエラー')->setInformation($this->results['uploadResults'])
                  , true, 'モール受注CSV変換 NextEngineアップロード処理 でエラーが発生しました。', 'error'
                );
              }
            }

            // エラーがなければ、ダウンロード日時・アップロード日時を更新
            if (!$hasError) {
              $newAlwaysExportSql = '';
              if ($newAlwaysUpload) {
                $newAlwaysExportSql = sprintf(" OR ( converted IS NOT NULL AND downloaded IS NULL )");
              }
              $sql = <<<EOD
                UPDATE tb_ne_mall_order o
                SET downloaded = NOW()
                  , uploaded = NOW()
                WHERE shop_code = :shopCode
                  AND (
                     converted = :converted
                     {$newAlwaysExportSql}
                  )
                  AND downloaded IS NULL
EOD;
              $stmt = $dbMain->prepare($sql);
              $stmt->bindValue(':shopCode', $mall->getNeMallId());
              $stmt->bindValue(':converted', $this->converted->format('Y-m-d H:i:s'));
              $stmt->execute();
            }
          }
        }

        $logger->info('check 5');
      }

      $logger->info('check 6');

      // DB記録＆通知処理
      $logger->addDbLog($logger->makeDbLog($logExecTitle, $logExecTitle, '終了')->setInformation($this->results));
      $logger->logTimerFlush();

      $logger->info('モール受注CSV変換処理を完了しました。');

    } catch (\Exception $e) {

      $logger->error('モール受注CSV変換処理 エラー:' . $e->getMessage());
      $logger->addDbLog(
        $logger->makeDbLog('モール受注CSV変換処理 エラー', 'モール受注CSV変換処理 エラー', 'エラー終了')->setInformation($e->getMessage())
        , true, 'モール受注CSV変換処理 でエラーが発生しました。', 'error'
      );

      return 1;
    }

    return 0;
  }

  /**
   * 共通変換処理
   * @param int $shopCode NextEngineモールID
   * @throws \Doctrine\DBAL\DBALException
   */
  private function processCommon($shopCode)
  {
    /** @var TbPrefectureRepository $pRepo */
    $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');

    $logger = $this->getLogger();

    $dbMain = $this->getDb('main');
    $commonUtil = $this->getDbCommonUtil();

    // ネコポス サイズ制限（cm）
    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.next_engine');
    $nekoposuSizeLimits = $mallProcess->getNekoposuSizeWeightLimits();

    $shoppingMall = $commonUtil->getShoppingMall($commonUtil->getMallIdByMallCode($commonUtil->getMallCodeByNeMallId($shopCode)));
    if (!$shoppingMall) {
      throw new \RuntimeException('unknown neMallId: ' . $shopCode);
    }

    // 商品タイトル前に出荷予定日をつけるか。（Q10, Yahoo、おとりよせ.com,楽天 以外）
    // 楽天は個別処理で商品名追加が行われているため。
    // Yahooは優良配送対応のため。
    $doAddShippingDateToProductTitle = ! ($shoppingMall->isMallId(DbCommonUtil::MALL_ID_Q10)
                                       || $shoppingMall->isMallId(DbCommonUtil::MALL_ID_YAHOO)
                                       || $shoppingMall->isMallId(DbCommonUtil::MALL_ID_YAHOOKAWA)
                                       || $shoppingMall->isMallId(DbCommonUtil::MALL_ID_YAHOO_OTORIYOSE)
                                       || $shoppingMall->isMallId(DbCommonUtil::MALL_ID_RAKUTEN)
                                       || $shoppingMall->isMallId(DbCommonUtil::MALL_ID_RAKUTEN_MINNA)
                                       || $shoppingMall->isMallId(DbCommonUtil::MALL_ID_RAKUTEN_MOTTO)
                                       || $shoppingMall->isMallId(DbCommonUtil::MALL_ID_RAKUTEN_LAFOREST)
                                       || $shoppingMall->isMallId(DbCommonUtil::MALL_ID_RAKUTEN_DOLCISSIMO)
                                       || $shoppingMall->isMallId(DbCommonUtil::MALL_ID_RAKUTEN_GEKIPLA)
                                         );

    $logger->info('mall order: common process 1 ( ' . $shoppingMall->getMallName() . ' )');

    // 受注明細との紐付け
    // ※変換が済んだものはもう触らない（DeNAやその他レアケースでNextEngineで明細行が変わることがごくまれにあり、巻き込まれる意味が無いため回避）
    //   また、同一商品が複数明細になっていることもあり、その場合には完全に正確な一致ではないが、一致させるキーが存在しないために
    //   この不一致はやむを得ないとする。（=> 伝票番号と明細行番号に頼る実装はなしとし、あくまでも参考値とする。）
    $sql = <<<EOD
      UPDATE tb_ne_mall_order o
      INNER JOIN tb_sales_detail_analyze a ON o.shop_code = a.店舗コード
                                          AND o.店舗伝票番号 = a.受注番号
                                          AND o.商品コード = a.`商品コード（伝票）`
      SET o.伝票番号 = a.伝票番号
        , o.明細行   = a.明細行
      WHERE o.伝票番号 IS NULL
        AND o.converted IS NULL
EOD;
    $dbMain->query($sql);

    $logger->info('mall order: common process 2');

    // 変換対象データ取得

    // 2017/08/02 コンシェル運用の簡単のため、「処理待ち」で止めるのもやめる。全変換する。
    $addWhere = '';
//    // 強制変換でない場合には、30分以上経過したものを変換する。
//    // また、Accessに登録がない商品の場合は無条件で変換する。（止めておいても永遠に受注明細と対応しないため）
//    // （修正）受注明細がない場合に止めておく処理はトラブルの元にばかりなるため一旦なし。時間経過のみで変換する。
//    if (
//         ! $this->input->getOption('force')
//      && ! $shoppingMall->isMallId(DbCommonUtil::MALL_ID_AMAZON) // Amazonは受注明細がないため常に強制
//    ) {
//      $addWhere = <<<EOD
//        INNER JOIN (
//          SELECT DISTINCT
//              o.shop_code
//            , o.`店舗伝票番号`
//          FROM tb_ne_mall_order o
//          WHERE o.`受注日` <= DATE_ADD(NOW(), INTERVAL -30 MINUTE)
//            AND o.converted IS NULL
//        ) T ON o.shop_code = T.shop_code AND o.`店舗伝票番号` = T.`店舗伝票番号`
//
//EOD;
//    }

    $sql = <<<EOD
      SELECT
          o.shop_code
        , o.mall_order_id
        , o.店舗伝票番号
        , o.発送先住所１
        , o.受注電話番号
        , o.発送電話番号
        , o.受注日
        , o.支払方法
        , o.発送方法
        , o.商品計
        , o.税金
        , o.発送料
        , o.手数料
        , o.ポイント
        , o.合計金額
        , o.時間帯指定
        , o.日付指定
        , o.作業者欄
        , o.備考
        , o.商品名
        , o.商品コード
        , o.受注数量
        , o.伝票番号
        , o.明細行

        , s.受注数
        , s.引当数

        , pci.shippingdivision_id
        , m.送料設定
        , m.fba_multi_flag
        , cal.mail_send_nums
        , cal.nekoposu_send_nums

        , f.伝票初回出荷予定年月日 AS shipping_date
        , COALESCE(s.`作業用欄`, '') AS `NE作業用欄`

        , size.weight + IFNULL(pt.weight, 0) AS weight
        , IF(IFNULL(pt.longlength, 0)>0, CAST(pt.longlength / 10 AS DECIMAL(10, 1)), size.side1) AS side1
        , IF(IFNULL(pt.middlelength, 0)>0, CAST(pt.middlelength / 10 AS DECIMAL(10, 1)), size.side2) AS side2
        , IF(IFNULL(pt.shortlength, 0)>0, CAST(pt.shortlength / 10 AS DECIMAL(10, 1)), size.side3) AS side3
        , IFNULL(sd.shipping_group_code, 0) as shipping_group_code
        , IFNULL(sd2.shipping_group_code, 0) as shipping_group_code2

      FROM tb_ne_mall_order o
      LEFT JOIN tb_mainproducts     m   ON o.daihyo_syohin_code = m.daihyo_syohin_code
      LEFT JOIN tb_mainproducts_cal cal ON o.daihyo_syohin_code = cal.daihyo_syohin_code
      LEFT JOIN tb_sales_detail     s   ON o.伝票番号 = s.伝票番号 AND o.明細行 = s.明細行
      LEFT JOIN tb_sales_detail_first_shipping_date f ON o.伝票番号 = f.伝票番号 AND o.明細行 = f.明細行
      LEFT JOIN v_product_sku_size size ON o.商品コード = size.ne_syohin_syohin_code
      LEFT JOIN tb_productchoiceitems pci ON o.商品コード = pci.ne_syohin_syohin_code
      LEFT JOIN tb_package_type pt ON pci.package_id = pt.package_id 
      LEFT JOIN tb_shippingdivision sd ON pci.shippingdivision_id = sd.id
      LEFT JOIN tb_shippingdivision sd2 ON m.送料設定 = sd2.id

      {$addWhere}

      WHERE o.shop_code = :shopCode
        AND o.converted IS NULL
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $shopCode);
    $stmt->execute();

    $logger->info('mall order: common process 3');

    // ※この、「伝票初回出荷予定年月日」が、伝票中の明細が欠けている場合に正しくない。
    //  （その欠けた明細に引き当たってないのに即納日付など）
    //   明細毎に違う出荷予定年月日をまとめるための「伝票初回出荷予定年月日」だが、
    //  「明細が無い」可能性を前提とするここの処理にはあまりよろしくない。


    // データを全て、伝票単位でまとめる
    $voucherList = [];
    while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

      if (!isset($voucherList[$row['店舗伝票番号']])) {
        // 単純変換
        if (strlen($row['NE作業用欄'])) {
          if (strlen($row['作業者欄'])) {
            $row['作業者欄'] .= "\n";
          }
          $row['作業者欄'] .= $row['NE作業用欄'];
        }

        // リスト作成
        $voucherList[$row['店舗伝票番号']] = [
            'details'           => []
//        , 'has_takuhai_betsu' => false
//        , 'has_takuhai_komi'  => false
//        , 'has_takuhai'       => false
//        , 'has_teikeigai'     => false
//        , 'has_yuu_packet'    => false
//        , 'has_nekoposu'      => false
//        , 'has_mail'          => false
          , 'has_fba_multi'     => false

          , '受注日'         => $row['受注日'] // 同一受注では全て同じはず
          
          // 受注者情報、発送先情報　同一受注では全て同じはず　ラベル印刷できないパターンの除去
          // １、購入者電話番号、発送先電話番号に「+81」が入っていれば、「0」に変換する
          // ２、購入者電話番号、発送先電話番号に数値以外が入っていれば、削除する(1の処理が上位優先)
          , '発送先住所１' => $row['発送先住所１']
          , '受注電話番号' => preg_replace('/[\D]/', '', str_replace('+81', '0', $row['受注電話番号']))
          , '発送電話番号' => preg_replace('/[\D]/', '', str_replace('+81', '0', $row['発送電話番号']))
          
          , '支払方法'       => $row['支払方法'] // 同一受注では全て同じはず

          , '受注数'         => 0
          , '引当数'         => 0
          , '未引当数'       => 0
          , 'weight_total'        => 0
          , 'volume_total'        => 0
          , 'mail_send_nums_rate' => 0
          , 'nekoposu_send_nums_rate' => 0

          , 'no_weight_codes'         => []
          , 'no_size_codes'           => []
          , 'out_of_nekoposu_size_codes' => []
          , 'no_mail_send_nums_codes' => []
          , 'no_nekoposu_send_nums_codes' => []
          , 'no_sales_detail'         => false // いずれかの明細がNextEngineの受注明細と紐付いていない場合 true

          , '自動設定番号'   => 99
          , '発送方法'       => ''
          , '自動設定送料'   => $row['発送料']
          , '自動設定手数料' => $row['手数料']
          , 'shipping_date' => $row['shipping_date']

          , '商品名'   => $row['商品名']
          , '商品計'   => $row['商品計']
          , '税金'     => $row['税金']
          , '発送料'   => $row['発送料']
          , '手数料'   => $row['手数料']
          , 'ポイント' => $row['ポイント']
          , '合計金額' => $row['合計金額']
          , '備考'     => $row['備考']
          , '作業者欄' => $row['作業者欄']
          
          // 新ルール用
          , '明細数' => 0
          , 'size' => array(
            'long' => 0,
            'middle' => 0,
            'short' => 0,
            'total' => 0,
            'weight' => 0,
          )
          , '商品コード' => '' // 廃止予定？
          , '伝票番号' => '' // デバッグ用
          , '送料設定ID' => ''
          , '送料設定グループ' => array()
          , '送料' => 0
        ];
      }

      $voucher = $voucherList[$row['店舗伝票番号']];
      $voucher['伝票番号'] = $row['伝票番号'];

      if (is_null($voucher['受注数'])) {
        $voucher['no_sales_detail'] = true;
      }
      
      $shippingdivisionId = '';
      $groupCode  = "";
      // SKU商品に送料設定があればそちらを使用
      if($row['shipping_group_code'] > 0) {
        $shippingdivisionId = $row['shippingdivision_id'];
        $groupCode = "".$row['shipping_group_code'];
      // ない場合は代表商品のものを使用
      } else if($row['shipping_group_code2'] > 0) {
        $shippingdivisionId = $row['送料設定'];
        $groupCode = "".$row['shipping_group_code2'];
      }
      $voucher['送料設定ID'] = $shippingdivisionId;
      // 文字列に変換して挿入
      if(!empty($groupCode)) $voucher['送料設定グループ'][] = $groupCode;
      
      $voucher['商品コード'] = $row['商品コード'];
      $voucher['発送方法'] = $row['発送方法'];
      
      $voucher['受注数'] += $row['受注数量'];
      $voucher['引当数'] += $row['引当数'];
      $voucher['未引当数'] += ($row['受注数量'] - $row['引当数']);
      $voucher['明細数'] += 1;

      // メール便枚数
      if (!floatval($row['mail_send_nums'])) {
        $voucher['no_mail_send_nums_codes'][] = $row['商品コード'];
      } else {
        $voucher['mail_send_nums_rate'] += $row['受注数量'] / $row['mail_send_nums'];
      }

      // ねこポス枚数
      if (!floatval($row['nekoposu_send_nums'])) {
        $voucher['no_nekoposu_send_nums_codes'][] = $row['商品コード'];
      } else {
        $voucher['nekoposu_send_nums_rate'] += $row['受注数量'] / $row['nekoposu_send_nums'];
      }

      // 重量
      if (!$row['weight']) {
        $voucher['no_weight_codes'][] = $row['商品コード'];
      } else {
        $voucher['weight_total'] += ($row['weight'] * $row['受注数量']);
      }

      // 容積
      $row['volume'] = 0;
      $row['unit_volume'] = 0;
      if (!$row['side1'] || !$row['side2'] || !$row['side3']) {
        $voucher['no_size_codes'][] = $row['商品コード'];
      } else {
        if($voucher['size']['long'] < $row['side1'])$voucher['size']['long'] = $row['side1'];
        if($voucher['size']['middle'] < $row['side2'])$voucher['size']['middle'] = $row['side2'];
        if($voucher['size']['short'] < $row['side3'])$voucher['size']['short'] = $row['side3'];
        if($voucher['size']['total'] < ($row['side1'] + $row['side2'] + $row['side3']))$voucher['size']['total'] = $row['side1'] + $row['side2'] + $row['side3'];
        if($voucher['size']['weight'] < $row['weight'])$voucher['size']['weight'] = $row['weight'];
      
        $row['unit_volume'] = $row['side1'] * $row['side2'] * $row['side3'];
        $row['volume'] = $row['unit_volume'] * $row['受注数量'];
        $voucher['volume_total'] += $row['volume'];

        // 超過チェック（単体）
        if (
             $row['side1'] > $nekoposuSizeLimits['side1']
          || $row['side2'] > $nekoposuSizeLimits['side2']
          || $row['side3'] > $nekoposuSizeLimits['side3']
        ) {
          $voucher['out_of_nekoposu_size_codes'][] = $row['商品コード'];
        }
      }

      // ネコポス商品であれば、商品タイトルの先頭に容積率を追加
      // ネコポスは廃止済みなので、基本的には通らないはず
      if ($groupCode == TbShippingdivision::SHIPPING_GROUP_CODE_NEKOPOSU) {
        $rate = $row['volume'] > 0 ? round($row['volume'] / $this->getNekoposuVolumeLimit() * 100, 0) : '--';
        $row['商品名'] = sprintf('[%2s] %s', $rate, $row['商品名']);
      }

      // FBAマルチ
      if ($row['fba_multi_flag'] != 0) {
        $voucher['has_fba_multi'] = true;
      }

      $voucher['details'][] = $row;
      $voucherList[$row['店舗伝票番号']] = $voucher; // 上書き
    }

    $logger->info('mall order: common process 4');
    
    // 振り分けルール取得
    $sql = <<<EOD
      SELECT 
        dsr.ID,
        dsr.rulename,
        dsr.checkorder,
        CAST(dsr.longlength / 10 AS DECIMAL(10 ,1)) AS longlength,
        CAST(dsr.middlelength / 10 AS DECIMAL(10 ,1)) AS middlelength,
        CAST(dsr.shortlength / 10 AS DECIMAL(10 ,1)) AS shortlength,
        CAST(dsr.totallength / 10 AS DECIMAL(10 ,1)) AS totallength,
        CAST(dsr.volume / 1000 AS DECIMAL(10 ,1)) AS volume,
        dsr.weight,
        dsr.sizecheck,
        dsr.maxflg,
        dsr.prefecture_check_column,
        dsr.delivery_id,
        dsr.groupid,
        dsr.groupname,
        dm.delivery_name 
      FROM tb_delivery_split_rule AS dsr
      INNER JOIN tb_delivery_method AS dm ON dsr.delivery_id = dm.delivery_id 
      WHERE dsr.groupid = 1 
      ORDER BY dsr.checkorder;
EOD;

    $stmt = $dbMain->prepare($sql);
    $stmt->execute();
    
    $rules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    // 対象都道府県情報を付与
    $rules = array_map(function ($rule) use ($pRepo){
      $rule['prefectures'] = [];
      if ($rule['prefecture_check_column']) {
        $rule['prefectures'] = $pRepo->findCheckColumnAvailabilityPrefectures(
          $rule['prefecture_check_column']
        );
      }
      return $rule;
    }, $rules);
    
    // 最大発送コード取得
    $sql = <<<EOD
      SELECT 
          dsr.delivery_id, 
          dm.delivery_name 
      FROM tb_delivery_split_rule AS dsr
      INNER JOIN tb_delivery_method AS dm ON dsr.delivery_id = dm.delivery_id 
      WHERE 
          dsr.groupid = 1
      AND dsr.maxflg = 1;
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    $maxId = $row['delivery_id'];
    $maxName = $row['delivery_name'];
    
    // 発送方法ごとの情報を取得
    $dmRepository = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbDeliveryMethod');
    $deliveryMethods = $dmRepository->getTbDeliveryMethodsWithShippingGroupCode();

    foreach($voucherList as $voucherNumber => $voucher) {
      // 旧送料変換メソッド、中身はほぼ廃止済みだが、FBAマルチ時のコメント付与などを実施
      $this->setShippingMethod($voucher, $shoppingMall);
      
      // 判定フラグ
      $setSize = false;
      
      // 単明細
      if($voucher['明細数'] === 1){
        // 単商品
        if($voucher['受注数'] > 1){
          // サイズ計測OK
          if(count($voucher['no_size_codes']) === 0 && count($voucher['no_weight_codes']) === 0) {
            $setSize = true;
          }
        }
      // 複数明細
      } else {
        // サイズ計測OK
        if(count($voucher['no_size_codes']) === 0 && count($voucher['no_weight_codes']) === 0) {
          $setSize = true;
        }
      }
      
      // 全品計測OKなら振り分けルールで振り分け
      if($setSize) {
        $this->setShippingMethodNew($voucher, $rules, $maxName);
      // 送料設定で発送方法を決定
      } else {
        $yamatoAvailableFlg = 0;
        $sagawaAvailableFlg = 0;
        // 1明細1受注で、
        if ($voucher['明細数'] === 1 && $voucher['受注数'] === 1) {
          // 一部の宅配便は、ヤマト>佐川の順に利用可能か検討する。
          if (
            $voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_60
            || $voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_80
            || $voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_100
            || $voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_140
            || $voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_160
          ) {
            $yamatoAvailableFlg = $this->checkYamatoAvailability($voucher);
          }

          if ($yamatoAvailableFlg === 0) {
            if (
              $voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_60
              || $voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_80
            ) {
              $sagawaAvailableFlg = $this->checkSagawaAvailability($voucher);
            }
          }
        }

        if ($yamatoAvailableFlg === 1) {
          $voucher['発送方法'] = DbCommonUtil::DELIVERY_METHOD_YAMATO_HATSUBARAI;
        } elseif ($sagawaAvailableFlg === 1) {
          $voucher['発送方法'] = DbCommonUtil::DELIVERY_METHOD_TAKUHAI;
        } else {
          foreach($voucher['送料設定グループ'] as $shippingGroupCode){
            $deliveryName = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$shippingGroupCode]['name'];
            $deliveryId   = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$shippingGroupCode]['id'];
            $deliveryCost = $deliveryMethods[$deliveryId]->getDeliveryCost();
            if($voucher['送料'] < $deliveryCost){
              $voucher['送料']     = $deliveryCost;
              $voucher['発送方法'] = $deliveryName;
            }
          }
        }
      }

      // 納期情報 確定
      $orderDate = new \DateTime($voucher['受注日']);
      $orderDate->setTime(0, 0, 0); // 比較のため揃える
      $immediateShippingDate = $commonUtil->getWorkingDateAfterDays($orderDate, 2);

      $this->setShippingDate($voucher, $immediateShippingDate);
      $shippingDate = null;
      if ($voucher['shipping_date']) {
        $shippingDate = new \DateTime($voucher['shipping_date']);
        $shippingDate->setTime(0, 0, 0);
      }

      // 即納文言対応変換 など
      $isImmediateShipping = $shippingDate && ($shippingDate <= $immediateShippingDate);

      $shippingDateMessage = '';
      if ($shippingDate) {
        if ($isImmediateShipping) {
          $shippingDateMessage = self::IMMEDIATE_SHIPPING_MESSAGE;
        } else {
          $shippingDateMessage = sprintf('%s頃出荷予定 ', $shippingDate->format('n月j日'));
        }
      }

      // 明細への一括登録
      foreach($voucher['details'] as $i => $detail) {
        $detail['自動設定番号']   = $voucher['自動設定番号'];
        $detail['自動設定手数料'] = $voucher['自動設定手数料'];
        $detail['自動設定送料']   = $voucher['自動設定送料'];

        $detail['受注電話番号'] = $voucher['受注電話番号'];
        $detail['発送電話番号'] = $voucher['発送電話番号'];

        $detail['発送方法']       = $voucher['発送方法'];
        $detail['shipping_date'] = ($shippingDate ? $shippingDate->format('Y-m-d') : '0000-00-00');
        $detail['is_immediate_shipping'] = $isImmediateShipping ? -1 : 0;

        // FBAマルチ商品には先頭に 「F」
        if ($detail['fba_multi_flag'] != 0) {
          $detail['商品名'] = sprintf('F %s', $detail['商品名']);
        }

        $detail['発送料']   = $voucher['発送料'];
        $detail['手数料']   = $voucher['手数料'];
        $detail['合計金額'] = $voucher['合計金額'];
        $detail['備考']     = $voucher['備考'];
        $detail['作業者欄'] = $voucher['作業者欄'];

        $detail['引当数']   = intval($detail['引当数']);
        $detail['未引当数'] = $detail['受注数量'] - $detail['引当数'];
        $detail['伝票未引当数'] = $voucher['未引当数'];

        // Q10・おとりよせ・楽天・Yahooでは発送予定日付を追加しない。楽天は個別で付与するため。
        // また、未引き当ての伝票には商品名を付与しない。
        if($detail['未引当数'] === 0){
          $detail['商品名'] = sprintf('%s%s', ($doAddShippingDateToProductTitle ? $shippingDateMessage : ''), $detail['商品名']);
        }

        $voucher['details'][$i] = $detail;
      }

      $voucherList[$voucherNumber] = $voucher;
    }

    $logger->info('mall order: common process 5');

    // 登録用の明細のみの配列
    $data = [];
    foreach($voucherList as $voucher) {
      foreach($voucher['details'] as $detail) {
        $data[] = $detail;
      }
    }

    // 一括insertによるUPDATE
    $insertBuilder = new MultiInsertUtil("tb_ne_mall_order", [
      'fields' => [
          'shop_code'     => \PDO::PARAM_STR
        , 'mall_order_id' => \PDO::PARAM_STR
        , 'shipping_date' => \PDO::PARAM_STR
        , '受注電話番号' => \PDO::PARAM_STR
        , '発送電話番号' => \PDO::PARAM_STR
        , '発送方法'  => \PDO::PARAM_STR
        , '商品名'    => \PDO::PARAM_STR
        , '発送料'    => \PDO::PARAM_INT
        , '手数料'    => \PDO::PARAM_INT
        , '合計金額'  => \PDO::PARAM_INT
        , '備考'      => \PDO::PARAM_STR
        , '作業者欄'  => \PDO::PARAM_STR

        , '自動設定番号'   => \PDO::PARAM_STR
        , '自動設定手数料' => \PDO::PARAM_STR
        , '自動設定送料'   => \PDO::PARAM_STR

        , '引当数'         => \PDO::PARAM_INT
        , '未引当数'       => \PDO::PARAM_INT
        , '伝票未引当数'   => \PDO::PARAM_INT
        , 'is_immediate_shipping' => \PDO::PARAM_INT
        , 'converted'     => \PDO::PARAM_STR
        , 'converted_by'  => \PDO::PARAM_STR
      ]
      , 'postfix' => " ON DUPLICATE KEY UPDATE "
                   . "     shipping_date = VALUES(shipping_date) "
                   . "   , 受注電話番号   = VALUES(受注電話番号) "
                   . "   , 発送電話番号   = VALUES(発送電話番号) "
                   . "   , 発送方法      = VALUES(発送方法) "
                   . "   , 商品名        = VALUES(商品名) "
                   . "   , 発送料        = VALUES(発送料) "
                   . "   , 手数料        = VALUES(手数料) "
                   . "   , 合計金額      = VALUES(合計金額) "
                   . "   , 備考          = VALUES(備考) "
                   . "   , 作業者欄      = VALUES(作業者欄) "
                   . "   , 自動設定番号   = VALUES(自動設定番号) "
                   . "   , 自動設定手数料 = VALUES(自動設定手数料) "
                   . "   , 自動設定送料   = VALUES(自動設定送料) "
                   . "   , 引当数         = VALUES(引当数) "
                   . "   , 未引当数       = VALUES(未引当数) "
                   . "   , 伝票未引当数   = VALUES(伝票未引当数) "
                   . "   , is_immediate_shipping = VALUES(is_immediate_shipping) "
                   . "   , converted     = VALUES(converted) "
                   . "   , converted_by  = VALUES(converted_by) "
    ]);

    $this->converted = new \DateTime();
    $commonUtil->multipleInsert($insertBuilder, $dbMain, $data, function($row) use ($logger) {

      $item = $row;

      $item['converted'] = $this->converted->format('Y-m-d H:i:s');
      $item['converted_by'] = $this->account ? $this->account->getClientName() : 'BatchSV02';
      return $item;

    }, 'foreach');

    $logger->info('mall order: common process 6');
  }

  /**
   * PPM 処理
   */
  private function processPpm()
  {
    $logger = $this->getLogger();
    $logger->info('モール受注CSV変換:PPM 開始');

    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logDbName = $dbLog->getDatabase();
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'PPM';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // 変換のため、データを取得してからバルクインサート
    $mallPpm = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_PPM);
    $sql = <<<EOD
      SELECT
          `注文番号`                                                          AS `店舗伝票番号`
        , `注文日時`                                                          AS `受注日`
        , CONCAT(`注文者郵便番号1`, `注文者郵便番号2`)                          AS `受注郵便番号`
        , CONCAT(`注文者住所：都道府県`, ' ', `注文者住所：市区町村以降`)        AS `受注住所1`
        , ''                                                                  AS `受注住所2`
        , CONCAT(`注文者名字`, ' ', `注文者名前`)                              AS `受注名`
        , CONCAT(`注文者名字フリガナ`, ' ', `注文者名前フリガナ`)               AS `受注名カナ`
        , `注文者電話番号`                                                     AS `受注電話番号`
        , ''                                                                  AS `受注メールアドレス`
        , CONCAT(`送付先郵便番号1`, `送付先郵便番号2`)                          AS `発送郵便番号`
        , CONCAT(`送付先住所：都道府県`, ' ', `送付先住所：市区町村以降`)         AS `発送先住所１`
        , ''                                                                   AS `発送先住所２`
        , CONCAT(`送付先名字`, ' ', `送付先名前`)                               AS `発送先名`
        , CONCAT(`送付先名字フリガナ`, ' ', `送付先名前フリガナ`)                AS `発送先カナ`
        , `送付先電話番号`                                                     AS `発送電話番号`
        , `決済方法`                                                           AS `支払方法`
        , ''                                                                  AS `発送方法` /* CSVの「配送方法」は利用しない */
        , `合計`                                                              AS `商品計` /* 税抜き */
        , `消費税(-99999=無効値)` AS `税金`
        , `送料(-99999=無効値)`   AS `発送料`
        , `代引料(-99999=無効値)` AS `手数料`
        , `ポイント利用額`        AS `ポイント`
        , -1 * `クーポン利用額` AS `その他費用`
        , `請求金額(-99999=無効値)` AS `合計金額`
        , 0 AS `ギフトフラグ`
        , CASE WHEN `お届け時間帯` = '0' THEN '' ELSE `お届け時間帯` END AS `時間帯指定`
        , CASE WHEN `お届け日指定` = '' THEN NULL ELSE `お届け日指定` END AS `日付指定`
        , `作業メモ` AS `作業者欄`
        , `コメント` AS `備考`
        , `商品名` AS `商品名`
        , '' AS `商品コード` /* 商品オプションから作成する */
        , `単価` AS `商品価格`
        , `個数` AS `受注数量`
        , `購入オプション` AS `商品オプション`
        , 0 AS `出荷済フラグ`
        , 0 AS `顧客区分`
        , '' AS `顧客コード`
        , :shopCode AS `shop_code`
        , id   AS `mall_order_id`
        , NULL AS `伝票番号`
        , NULL AS `明細行`
        , 商品ID     AS daihyo_syohin_code
        , `imported` AS `imported`
        , NULL       AS `converted`
        , NULL       AS `downloaded`
      FROM {$logDbName}.tb_mall_order_ppm o
      WHERE o.convert_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $mallPpm->getNeMallId());
    $stmt->execute();

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_ne_mall_order", [
      'fields' => [
          '店舗伝票番号' => \PDO::PARAM_STR
        , '受注日' => \PDO::PARAM_STR
        , '受注郵便番号' => \PDO::PARAM_STR
        , '受注住所1' => \PDO::PARAM_STR
        , '受注住所2' => \PDO::PARAM_STR
        , '受注名' => \PDO::PARAM_STR
        , '受注名カナ' => \PDO::PARAM_STR
        , '受注電話番号' => \PDO::PARAM_STR
        , '受注メールアドレス' => \PDO::PARAM_STR
        , '発送郵便番号' => \PDO::PARAM_STR
        , '発送先住所１' => \PDO::PARAM_STR
        , '発送先住所２' => \PDO::PARAM_STR
        , '発送先名' => \PDO::PARAM_STR
        , '発送先カナ' => \PDO::PARAM_STR
        , '発送電話番号' => \PDO::PARAM_STR
        , '支払方法' => \PDO::PARAM_STR
        , '発送方法' => \PDO::PARAM_STR
        , '商品計' => \PDO::PARAM_INT
        , '税金' => \PDO::PARAM_INT
        , '発送料' => \PDO::PARAM_INT
        , '手数料' => \PDO::PARAM_INT
        , 'ポイント' => \PDO::PARAM_INT
        , 'その他費用' => \PDO::PARAM_INT
        , '合計金額' => \PDO::PARAM_INT
        , 'ギフトフラグ' => \PDO::PARAM_STR
        , '時間帯指定' => \PDO::PARAM_STR
        , '日付指定' => \PDO::PARAM_STR
        , '作業者欄' => \PDO::PARAM_STR
        , '備考' => \PDO::PARAM_STR
        , '商品名' => \PDO::PARAM_STR
        , '商品コード' => \PDO::PARAM_STR
        , '商品価格' => \PDO::PARAM_INT
        , '受注数量' => \PDO::PARAM_INT
        , '商品オプション' => \PDO::PARAM_STR
        , '出荷済フラグ' => \PDO::PARAM_STR
        , '顧客区分' => \PDO::PARAM_STR
        , '顧客コード' => \PDO::PARAM_STR
        , 'shop_code' => \PDO::PARAM_INT
        , 'mall_order_id' => \PDO::PARAM_STR
        , '伝票番号' => \PDO::PARAM_INT
        , '明細行' => \PDO::PARAM_INT
        , 'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'imported' => \PDO::PARAM_STR
        , 'converted' => \PDO::PARAM_STR
        , 'downloaded' => \PDO::PARAM_STR
      ]
      , 'prefix' => 'INSERT IGNORE INTO '
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($row) use ($logger, $neMallProcess) {

      $item = $row;

      foreach($item as $key => $value) {
        // -99999=無効値
        if (in_array($key, [
            '税金'
          , '発送料'
          , '手数料'
          , '合計金額'
        ])) {
          if ($value == '-99999') {
            $item[$key] = 0;
          }
        }
      }

      // 文言変更 支払方法
      $item['支払方法'] = $neMallProcess->convertPaymentMethod($item['支払方法']);

      // '請求金額＝０の場合は支払方法を「ポイント全額支払い」に変更する
      // ※
      // '合計＝０で決済方法が特定の手段になっている場合は決済方法を「ポイント全額支払い」に変更する
      // という処理も存在したが、最初の一括処理で同じことになってしまっているため省略（`決済方法` in ('ソフトバンクまとめて支払い' Or 'ドコモケータイ払い' Or 'auかんたん決済')）
      // 唯一、0 円のはずがが宅配送料がついて、というケースでふさわしくない処理だがこれはレアケースなため一旦保留
      if ($item['合計金額'] == 0) {
        $item['支払方法'] = 'ポイント全額支払い';
      }

      // 商品コード作成
      // CSV中の「商品ID（表示用）」はハイフンが一つ余分について生成されているため、商品オプションから取得する
      $options = explode("\n", $item['商品オプション']);
      // ２件以上ないケースはイレギュラー。一旦無視
      if (count($options) < 2) {
        throw new \RuntimeException('オプションから横軸・縦軸が取得できませんでした。');
      }

      $colOption = $options[0];
      $rowOption = $options[1];
      $colCode = preg_match('/^[^\\(]+\\(([^\\)]+)\\)/', $colOption, $m) ? $m[1] : '--';
      $rowCode = preg_match('/^[^\\(]+\\(([^\\)]+)\\)/', $rowOption, $m) ? $m[1] : '--';
      $item['商品コード'] = sprintf('%s%s%s', $item['daihyo_syohin_code'], $colCode, $rowCode);
      $item['商品オプション'] = sprintf('%s|%s|', $colOption, $rowOption); // こちらは必要か不明だが、現在の取込結果に合わせておく。

      // TODO 「商品名の付加文言削除」 ・・・ 必要なら実装する。

      return $item;

    }, 'foreach');

    // 後は共通処理
    $this->processCommon($mallPpm->getNeMallId());

    // 即納でなければ備考欄に「未引当あり」追加（NextEngine確認チェック用）
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_ppm i ON mo.mall_order_id = i.id
      SET mo.備考 = CONCAT('【未引当あり】', '\r\n', mo.備考)
      WHERE i.convert_flg = 0
        AND mo.`伝票未引当数` > 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // ppm 特殊処理：メールアドレスの取得 （紐付いた受注明細から取得する）
    $sql = <<<EOD
      UPDATE tb_ne_mall_order o
      INNER JOIN tb_sales_detail d ON o.伝票番号 = d.伝票番号 AND o.明細行 = d.明細行
      SET o.受注メールアドレス = d.購入者メールアドレス
      WHERE o.shop_code = :shopCode
        AND o.受注メールアドレス = ''
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $mallPpm->getNeMallId());
    $stmt->execute();

    // convert_flg の更新
    $sql = <<<EOD
      UPDATE {$logDbName}.tb_mall_order_ppm o
      SET o.convert_flg = -1
      WHERE o.convert_flg = 0
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    $logger->info('モール受注CSV変換:PPM 終了');
  }

  /**
   * Amazon 処理
   */
  private function processAmazon()
  {
    $logger = $this->getLogger();
    $logger->info('モール受注CSV変換:Amazon 開始');

    $logTitle = 'Amazon';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logDbName = $dbLog->getDatabase();
    $commonUtil = $this->getDbCommonUtil();

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

    // 変換のため、データを取得してからバルクインサート
    $mall = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_AMAZON);
    $sql = <<<EOD
      SELECT
          o.`order-id`                  AS `店舗伝票番号`
        , `purchase-date`               AS `受注日`
        , `ship-postal-code`                                        AS `受注郵便番号` /* ※1 */
        , CONCAT(`ship-state`, ' ', `ship-city`, `ship-address-1`)  AS `受注住所1` /* ※1 */
        , CONCAT(`ship-address-2`, `ship-address-3`)                AS `受注住所2` /* ※1 */
        , `buyer-name`         AS `受注名`
        , ''                   AS `受注名カナ`
        , `buyer-phone-number` AS `受注電話番号`
        , `buyer-email`        AS `受注メールアドレス`

        , `ship-postal-code`                                       AS `発送郵便番号`
        , CONCAT(`ship-state`, ' ', `ship-city`, `ship-address-1`) AS `発送先住所１`
        , CONCAT(`ship-address-2`, `ship-address-3`)               AS `発送先住所２`
        , `recipient-name`     AS `発送先名`
        , ''                   AS `発送先カナ`
        , `ship-phone-number`  AS `発送電話番号`
        , 'Amazonペイメント'    AS `支払方法`
        , ''                   AS `発送方法`

        , T.item_price                      AS `商品計` /* 税込 */ /* item_tax に数値が入る様になったので足さない。 */
        , 0                                 AS `税金` /* 商品計が税込みなので0円固定 */
        , T.shipping_price + T.shipping_tax AS `発送料`
        , 0           AS `手数料`
        , 0           AS `ポイント`
        , 0           AS `その他費用`
        , T.item_price + T.shipping_price + T.shipping_tax AS `合計金額`

        , 0    AS `ギフトフラグ`
        , ''   AS `時間帯指定`
        , NULL AS `日付指定`
        , ''   AS `作業者欄`
        , ''   AS `備考`

        , `product-name`             AS `商品名`
        , `sku`                      AS `商品コード`
        , CASE
            WHEN `quantity-purchased` = 0 THEN 0
            ELSE TRUNCATE(`item-price` / `quantity-purchased`, 0)
          END AS `商品価格`
        , `quantity-purchased`       AS `受注数量`

        , ''                         AS `商品オプション`
        , 0                          AS `出荷済フラグ`
        , 0                          AS `顧客区分`
        , ''                         AS `顧客コード`
        , :shopCode AS `shop_code`
        , id   AS `mall_order_id`
        , NULL AS `伝票番号`
        , NULL AS `明細行`
        , daihyo_syohin_code AS daihyo_syohin_code
        , `imported` AS `imported`
        , NULL       AS `converted`
        , NULL       AS `downloaded`

      FROM {$logDbName}.tb_mall_order_amazon o
      INNER JOIN (
        SELECT
            a.`order-id`
          , SUM(a.`item-price`) AS item_price
          , SUM(a.`item-tax`) AS item_tax
          , SUM(a.`shipping-price`) AS shipping_price
          , SUM(a.`shipping-tax`) AS shipping_tax
        FROM {$logDbName}.tb_mall_order_amazon a
        GROUP BY a.`order-id`
      ) T ON o.`order-id` = T.`order-id`
      WHERE o.convert_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $mall->getNeMallId());
    $stmt->execute();

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_ne_mall_order", [
      'fields' => [
          '店舗伝票番号' => \PDO::PARAM_STR
        , '受注日' => \PDO::PARAM_STR
        , '受注郵便番号' => \PDO::PARAM_STR
        , '受注住所1' => \PDO::PARAM_STR
        , '受注住所2' => \PDO::PARAM_STR
        , '受注名' => \PDO::PARAM_STR
        , '受注名カナ' => \PDO::PARAM_STR
        , '受注電話番号' => \PDO::PARAM_STR
        , '受注メールアドレス' => \PDO::PARAM_STR
        , '発送郵便番号' => \PDO::PARAM_STR
        , '発送先住所１' => \PDO::PARAM_STR
        , '発送先住所２' => \PDO::PARAM_STR
        , '発送先名' => \PDO::PARAM_STR
        , '発送先カナ' => \PDO::PARAM_STR
        , '発送電話番号' => \PDO::PARAM_STR
        , '支払方法' => \PDO::PARAM_STR
        , '発送方法' => \PDO::PARAM_STR
        , '商品計' => \PDO::PARAM_INT
        , '税金' => \PDO::PARAM_INT
        , '発送料' => \PDO::PARAM_INT
        , '手数料' => \PDO::PARAM_INT
        , 'ポイント' => \PDO::PARAM_INT
        , 'その他費用' => \PDO::PARAM_INT
        , '合計金額' => \PDO::PARAM_INT
        , 'ギフトフラグ' => \PDO::PARAM_STR
        , '時間帯指定' => \PDO::PARAM_STR
        , '日付指定' => \PDO::PARAM_STR
        , '作業者欄' => \PDO::PARAM_STR
        , '備考' => \PDO::PARAM_STR
        , '商品名' => \PDO::PARAM_STR
        , '商品コード' => \PDO::PARAM_STR
        , '商品価格' => \PDO::PARAM_INT
        , '受注数量' => \PDO::PARAM_INT
        , '商品オプション' => \PDO::PARAM_STR
        , '出荷済フラグ' => \PDO::PARAM_STR
        , '顧客区分' => \PDO::PARAM_STR
        , '顧客コード' => \PDO::PARAM_STR
        , 'shop_code' => \PDO::PARAM_INT
        , 'mall_order_id' => \PDO::PARAM_STR
        , '伝票番号' => \PDO::PARAM_INT
        , '明細行' => \PDO::PARAM_INT
        , 'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'imported' => \PDO::PARAM_STR
        , 'converted' => \PDO::PARAM_STR
        , 'downloaded' => \PDO::PARAM_STR
      ]
      , 'prefix' => 'INSERT IGNORE INTO '
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($row) use ($neMallProcess, $logger) {

      $item = $row;

      // 受注日 形式変換（および UTC->JST）
      if (preg_match('/^(\d+)-(\d+)-(\d+)[T ](\d+):(\d+):(\d+)(\+\d\d:?\d\d)?$/', $item['受注日'], $matched)) {
        $y = $matched[1];
        $m = $matched[2];
        $d = $matched[3];
        $h = $matched[4];
        $i = $matched[5];
        $s = $matched[6];

        $timeZone =  isset($matched[7]) ? $matched[7] : '+09:00';
        $dt = new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d', $y, $m, $d, $h, $i, $s), new \DateTimeZone($timeZone));
        $dt->setTimezone(new \DateTimeZone('Asia/Tokyo'));

        $item['受注日'] = $dt->format('Y-m-d H:i:s');
      }

      return $item;

    }, 'foreach');

    // 後は共通処理
    $this->processCommon($mall->getNeMallId());

/*
    // 即納でなければ備考欄に「未引当あり」追加（NextEngine確認チェック用）
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_amazon i ON mo.mall_order_id = i.id
      SET mo.備考 = CONCAT('【未引当あり】', '\r\n', mo.備考)
      WHERE i.convert_flg = 0
        AND mo.`伝票未引当数` > 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();
*/

    // convert_flg の更新
    $sql = <<<EOD
      UPDATE
      {$logDbName}.tb_mall_order_amazon o
      SET o.convert_flg = -1
      WHERE o.convert_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    $logger->info('モール受注CSV変換:Amazon 終了');
  }

  /**
   * 楽天 処理
   * @param $mallCode
   * @throws \Doctrine\DBAL\DBALException
   */
  private function processRakuten($mallCode)
  {
    $logger = $this->getLogger();
    $logger->info('モール受注CSV変換:楽天 開始 ' . $mallCode);

    $logTitle = '楽天(' . $mallCode . ')';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logDbName = $dbLog->getDatabase();
    $commonUtil = $this->getDbCommonUtil();

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

    // 変換のため、データを取得してからバルクインサート
    $mall = $commonUtil->getShoppingMall($commonUtil->getMallIdByMallCode($mallCode));
    $sql = <<<EOD
      SELECT
          `受注番号`                                               AS `店舗伝票番号`
        , CONCAT(`注文日`, ' ', `注文時間`)                         AS `受注日`
        , CONCAT(`注文者郵便番号１`, `注文者郵便番号２`)             AS `受注郵便番号`
        , CONCAT(`注文者住所：都道府県`, ' ', `注文者住所：都市区`)  AS `受注住所1`
        , `注文者住所：町以降`                                      AS `受注住所2`
        , CONCAT(`注文者名字`, ' ', `注文者名前`)                   AS `受注名`
        , CONCAT(`注文者名字フリガナ`, ' ', `注文者名前フリガナ`)    AS `受注名カナ`
        , CONCAT(`注文者電話番号１`, `注文者電話番号２`, `注文者電話番号３`) AS `受注電話番号`
        , `メールアドレス` AS `受注メールアドレス`

        , CONCAT(`送付先郵便番号１`, `送付先郵便番号２`) AS `発送郵便番号`
        , CONCAT(`送付先住所：都道府県`, ' ', `送付先住所：都市区`) AS `発送先住所１`
        , `送付先住所：町以降`          AS `発送先住所２`
        , CONCAT(`送付先名字`, ' ', `送付先名前`) AS `発送先名`
        , CONCAT(`送付先名字フリガナ`, ' ', `送付先名前フリガナ`)          AS `発送先カナ`
        , CONCAT(`送付先電話番号１`, `送付先電話番号２`, `送付先電話番号３`) AS `発送電話番号`
        , `決済方法`                      AS `支払方法`
        , ''                              AS `発送方法`

        , `合計`             AS `商品計` /* 税抜き */
        , `消費税(-99999=無効値)` /* `消費税` */      AS `税金`
        , `送料(-99999=無効値)`                AS `発送料`
        ,   (
            `代引料(-99999=無効値)`
          + `楽天バンク決済手数料`
          + `ラッピング料金(包装紙)`
          + `ラッピング料金(リボン)`
          ) AS `手数料`
        , `ポイント利用額`           AS `ポイント`
        , -1 * `クーポン利用額`      AS `その他費用`
        , `請求金額(-99999=無効値)`  AS `合計金額`

        , `ギフトチェック（0:なし/1:あり）` AS `ギフトフラグ`
        , `お届け時間帯`                   AS `時間帯指定`
        , `お届け日指定`                   AS `日付指定`
        , `ひとことメモ`                   AS `作業者欄`
        , `コメント`                       AS `備考`

        , `商品名`                        AS `商品名`
        , `商品番号`                      AS `商品コード`
        , `単価`                          AS `商品価格`
        , `個数`                          AS `受注数量`
        , `項目・選択肢`                   AS `商品オプション`
        , 0                               AS `出荷済フラグ`
        , 0                               AS `顧客区分`
        , ''                              AS `顧客コード`
        , :shopCode AS `shop_code`
        , id   AS `mall_order_id`
        , NULL AS `伝票番号`
        , NULL AS `明細行`
        , daihyo_syohin_code AS daihyo_syohin_code
        , `imported` AS `imported`
        , NULL       AS `converted`
        , NULL       AS `downloaded`

        , `カード決済ステータス` AS `カード決済ステータス`
        , `あす楽希望`          AS `あす楽希望`

      FROM {$logDbName}.tb_mall_order_rakuten o
      WHERE o.convert_flg = 0
        AND o.mall_code = :mallCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $mall->getNeMallId());
    $stmt->bindValue(':mallCode', $mallCode);
    $stmt->execute();


    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_ne_mall_order", [
      'fields' => [
          '店舗伝票番号' => \PDO::PARAM_STR
        , '受注日' => \PDO::PARAM_STR
        , '受注郵便番号' => \PDO::PARAM_STR
        , '受注住所1' => \PDO::PARAM_STR
        , '受注住所2' => \PDO::PARAM_STR
        , '受注名' => \PDO::PARAM_STR
        , '受注名カナ' => \PDO::PARAM_STR
        , '受注電話番号' => \PDO::PARAM_STR
        , '受注メールアドレス' => \PDO::PARAM_STR
        , '発送郵便番号' => \PDO::PARAM_STR
        , '発送先住所１' => \PDO::PARAM_STR
        , '発送先住所２' => \PDO::PARAM_STR
        , '発送先名' => \PDO::PARAM_STR
        , '発送先カナ' => \PDO::PARAM_STR
        , '発送電話番号' => \PDO::PARAM_STR
        , '支払方法' => \PDO::PARAM_STR
        , '発送方法' => \PDO::PARAM_STR
        , '商品計' => \PDO::PARAM_INT
        , '税金' => \PDO::PARAM_INT
        , '発送料' => \PDO::PARAM_INT
        , '手数料' => \PDO::PARAM_INT
        , 'ポイント' => \PDO::PARAM_INT
        , 'その他費用' => \PDO::PARAM_INT
        , '合計金額' => \PDO::PARAM_INT
        , 'ギフトフラグ' => \PDO::PARAM_STR
        , '時間帯指定' => \PDO::PARAM_STR
        , '日付指定' => \PDO::PARAM_STR
        , '作業者欄' => \PDO::PARAM_STR
        , '備考' => \PDO::PARAM_STR
        , '商品名' => \PDO::PARAM_STR
        , '商品コード' => \PDO::PARAM_STR
        , '商品価格' => \PDO::PARAM_INT
        , '受注数量' => \PDO::PARAM_INT
        , '商品オプション' => \PDO::PARAM_STR
        , '出荷済フラグ' => \PDO::PARAM_STR
        , '顧客区分' => \PDO::PARAM_STR
        , '顧客コード' => \PDO::PARAM_STR
        , 'shop_code' => \PDO::PARAM_INT
        , 'mall_order_id' => \PDO::PARAM_STR
        , '伝票番号' => \PDO::PARAM_INT
        , '明細行' => \PDO::PARAM_INT
        , 'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'imported' => \PDO::PARAM_STR
        , 'converted' => \PDO::PARAM_STR
        , 'downloaded' => \PDO::PARAM_STR
      ]
      , 'prefix' => 'INSERT IGNORE INTO '
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($row) use ($neMallProcess, $logger) {

      $item = $row;

      // 文言変更 支払方法
      $item['支払方法'] = $neMallProcess->convertPaymentMethod($item['支払方法']);

      // カード決済ステータス補完
      if ($item['支払方法'] == 'クレジットカード') {
        if ($item['カード決済ステータス'] != 'オーソリ済み') {
          $item['作業者欄'] = sprintf("[カード決済ステータス]%s\n", $item['カード決済ステータス']) . $item['作業者欄'];
        }
      }

      // あす楽判定
      if ($item['あす楽希望'] == 1) {
        $item['作業者欄'] = "あす楽希望\n" . $item['作業者欄'];
      }

      // 商品オプション 切り取り（先頭2行のみ）
      $options = explode("\n", $item['商品オプション']);
      if (count($options) > 2) {
        $options = array_slice($options, 0, 2);
      }
      $item['商品オプション'] = implode("\n", $options);

      return $item;

    }, 'foreach');

    // 後は共通処理
    $this->processCommon($mall->getNeMallId());

    // 即納でなければ備考欄に「未引当あり」追加（NextEngine確認チェック用）
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_rakuten i ON mo.mall_order_id = i.id
      SET mo.備考 = CONCAT('【未引当あり】', '\r\n', mo.備考)
      WHERE i.convert_flg = 0
        AND i.mall_code = :mallCode
        AND mo.`伝票未引当数` > 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // 即納なら商品名の先頭に納期情報を追加
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_rakuten i ON mo.mall_order_id = i.id
      SET mo.商品名 = CONCAT(i.納期情報, mo.商品名)
      WHERE i.convert_flg = 0
        AND i.mall_code = :mallCode
        AND mo.`未引当数` = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // convert_flg の更新
    $sql = <<<EOD
      UPDATE
      {$logDbName}.tb_mall_order_rakuten o
      SET o.convert_flg = -1
      WHERE o.convert_flg = 0
        AND o.mall_code = :mallCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode);
    $stmt->execute();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    $logger->info('モール受注CSV変換:楽天 終了');
  }

  /**
   * 楽天ペイ 処理
   * @param $mallCode
   * @throws \Doctrine\DBAL\DBALException
   */
  private function processRakutenPay($mallCode)
  {
    $logger = $this->getLogger();
    $logger->info('モール受注CSV変換:楽天ペイ 開始 ' . $mallCode);

    $logTitle = '楽天ペイ(' . $mallCode . ')';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logDbName = $dbLog->getDatabase();
    $commonUtil = $this->getDbCommonUtil();

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

    // 変換のため、データを取得してからバルクインサート
    $mall = $commonUtil->getShoppingMall($commonUtil->getMallIdByMallCode($mallCode));
    $sql = <<<EOD
      SELECT
          `注文番号`                                               AS `店舗伝票番号`
        , `注文日時`                         AS `受注日`
        , CONCAT(`注文者郵便番号1`, `注文者郵便番号2`)             AS `受注郵便番号`
        , CONCAT(`注文者住所都道府県`, ' ', `注文者住所郡市区`)  AS `受注住所1`
        , `注文者住所それ以降の住所`                                      AS `受注住所2`
        , CONCAT(`注文者姓`, ' ', `注文者名`)                   AS `受注名`
        , CONCAT(`注文者姓カナ`, ' ', `注文者名カナ`)    AS `受注名カナ`
        , CONCAT(`注文者電話番号1`, `注文者電話番号2`, `注文者電話番号3`) AS `受注電話番号`
        , `注文者メールアドレス` AS `受注メールアドレス`

        , CONCAT(`送付先郵便番号1`, `送付先郵便番号2`) AS `発送郵便番号`
        , CONCAT(`送付先住所都道府県`, ' ', `送付先住所郡市区`) AS `発送先住所１`
        , `送付先住所それ以降の住所`          AS `発送先住所２`
        , CONCAT(`送付先姓`, ' ', `送付先名`) AS `発送先名`
        , CONCAT(`送付先姓カナ`, ' ', `送付先名カナ`)          AS `発送先カナ`
        , CONCAT(`送付先電話番号1`, `送付先電話番号2`, `送付先電話番号3`) AS `発送電話番号`
        , `支払方法名`                      AS `支払方法`
        , ''                              AS `発送方法`

        , `商品合計金額`             AS `商品計` /* 税抜き */
        , `消費税合計`               AS `税金`
        , `送料合計`                 AS `発送料`
        ,  `代引料合計`              AS `手数料`
        , `ポイント利用額`           AS `ポイント`
        , (
             (-1 * `クーポン利用総額`)
             + `ラッピング料金1`
             + `ラッピング料金2`
           ) AS `その他費用`
        , `請求金額`                AS `合計金額`

        , `ギフト配送希望`           AS `ギフトフラグ`
        , '' /* `お届け時間帯` */    AS `時間帯指定`
        , `お届け日指定`                   AS `日付指定`
        , `ひとことメモ`                   AS `作業者欄`
        , `コメント`                       AS `備考`

        , `商品名`                        AS `商品名`
        , `商品番号`                      AS `商品コード`
        , `単価`                          AS `商品価格`
        , `個数`                          AS `受注数量`
        , `項目・選択肢`                   AS `商品オプション`
        , 0                               AS `出荷済フラグ`
        , 0                               AS `顧客区分`
        , ''                              AS `顧客コード`
        , :shopCode AS `shop_code`
        , id   AS `mall_order_id`
        , NULL AS `伝票番号`
        , NULL AS `明細行`
        , daihyo_syohin_code AS daihyo_syohin_code
        , `imported` AS `imported`
        , NULL       AS `converted`
        , NULL       AS `downloaded`

        , `ステータス` AS `ステータス`
        /* , `カード決済ステータス` AS `カード決済ステータス` */
        , `あす楽希望フラグ`          AS `あす楽希望フラグ`

      FROM {$logDbName}.tb_mall_order_rakuten_pay o
      WHERE o.convert_flg = 0
        AND o.mall_code = :mallCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $mall->getNeMallId());
    $stmt->bindValue(':mallCode', $mallCode);
    $stmt->execute();


    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_ne_mall_order", [
      'fields' => [
        '店舗伝票番号' => \PDO::PARAM_STR
        , '受注日' => \PDO::PARAM_STR
        , '受注郵便番号' => \PDO::PARAM_STR
        , '受注住所1' => \PDO::PARAM_STR
        , '受注住所2' => \PDO::PARAM_STR
        , '受注名' => \PDO::PARAM_STR
        , '受注名カナ' => \PDO::PARAM_STR
        , '受注電話番号' => \PDO::PARAM_STR
        , '受注メールアドレス' => \PDO::PARAM_STR
        , '発送郵便番号' => \PDO::PARAM_STR
        , '発送先住所１' => \PDO::PARAM_STR
        , '発送先住所２' => \PDO::PARAM_STR
        , '発送先名' => \PDO::PARAM_STR
        , '発送先カナ' => \PDO::PARAM_STR
        , '発送電話番号' => \PDO::PARAM_STR
        , '支払方法' => \PDO::PARAM_STR
        , '発送方法' => \PDO::PARAM_STR
        , '商品計' => \PDO::PARAM_INT
        , '税金' => \PDO::PARAM_INT
        , '発送料' => \PDO::PARAM_INT
        , '手数料' => \PDO::PARAM_INT
        , 'ポイント' => \PDO::PARAM_INT
        , 'その他費用' => \PDO::PARAM_INT
        , '合計金額' => \PDO::PARAM_INT
        , 'ギフトフラグ' => \PDO::PARAM_STR
        , '時間帯指定' => \PDO::PARAM_STR
        , '日付指定' => \PDO::PARAM_STR
        , '作業者欄' => \PDO::PARAM_STR
        , '備考' => \PDO::PARAM_STR
        , '商品名' => \PDO::PARAM_STR
        , '商品コード' => \PDO::PARAM_STR
        , '商品価格' => \PDO::PARAM_INT
        , '受注数量' => \PDO::PARAM_INT
        , '商品オプション' => \PDO::PARAM_STR
        , '出荷済フラグ' => \PDO::PARAM_STR
        , '顧客区分' => \PDO::PARAM_STR
        , '顧客コード' => \PDO::PARAM_STR
        , 'shop_code' => \PDO::PARAM_INT
        , 'mall_order_id' => \PDO::PARAM_STR
        , '伝票番号' => \PDO::PARAM_INT
        , '明細行' => \PDO::PARAM_INT
        , 'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'imported' => \PDO::PARAM_STR
        , 'converted' => \PDO::PARAM_STR
        , 'downloaded' => \PDO::PARAM_STR
      ]
      , 'prefix' => 'INSERT IGNORE INTO '
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($row) use ($neMallProcess, $logger) {

      $item = $row;

      // 文言変更 支払方法
      $item['支払方法'] = $neMallProcess->convertPaymentMethod($item['支払方法']);

      // ステータスによって作業者欄にコメント追加
      // https://manual.next-engine.net/main/orders/ord_torikomi-shori/juchu_torikomi/juchu__rakuten/20637/
      // 2018/10/01 NextEngineの変換仕様を模したものだが、確認内容設定の「[ステータス]」文言にすべて引っかかるためいったん削除。
      // 必要であればまたもとに戻す。
//      $comment = '';
//      switch ($item['ステータス']) {
//        case '100':
//          $comment = "[ステータス]注文確認待ち\n";
//          break;
//        case '200':
//          $comment = "[ステータス]楽天処理中\n";
//          break;
//        case '400':
//          $comment = "[ステータス]変更確定待ち\n";
//          break;
//        case '500':
//          $comment = "[ステータス]発送済\n";
//          break;
//        case '600':
//          $comment = "[ステータス]支払手続き中\n";
//          break;
//        case '700':
//          $comment = "[ステータス]支払手続き済\n";
//          break;
//        case '800':
//          $comment = "[ステータス]キャンセル確定待ち\n";
//          break;
//        case '900':
//          $comment = "[ステータス]キャンセル確定\n";
//          break;
//      }
//      if (strlen($comment) > 0) {
//        $item['作業者欄'] = $comment . $item['作業者欄'];
//      }

      // あす楽判定
      if ($item['あす楽希望フラグ'] == 1) {
        $item['作業者欄'] = "あす楽希望\n" . $item['作業者欄'];
      }

      // 商品オプション 切り取り（先頭2行のみ）
      $options = explode("\n", $item['商品オプション']);
      if (count($options) > 2) {
        $options = array_slice($options, 0, 2);
      }
      $item['商品オプション'] = implode("\n", $options);

      return $item;

    }, 'foreach');

    // 後は共通処理
    $this->processCommon($mall->getNeMallId());

    // 即納でなければ備考欄に「未引当あり」追加（NextEngine確認チェック用）
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_rakuten_pay i ON mo.mall_order_id = i.id
      SET mo.備考 = CONCAT('【未引当あり】', '\r\n', mo.備考)
      WHERE i.convert_flg = 0
        AND i.mall_code = :mallCode
        AND mo.`伝票未引当数` > 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // 即納なら商品名の先頭に納期情報を追加
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_rakuten_pay i ON mo.mall_order_id = i.id
      SET mo.商品名 = CONCAT(i.納期情報, mo.商品名)
      WHERE i.convert_flg = 0
        AND i.mall_code = :mallCode
        AND mo.`未引当数` = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // convert_flg の更新
    $sql = <<<EOD
      UPDATE
      {$logDbName}.tb_mall_order_rakuten_pay o
      SET o.convert_flg = -1
      WHERE o.convert_flg = 0
        AND o.mall_code = :mallCode
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode);
    $stmt->execute();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    $logger->info('モール受注CSV変換:楽天ペイ 終了');
  }


  /**
   * DeNA 処理
   */
  private function processDena()
  {
    $logger = $this->getLogger();
    $logger->info('モール受注CSV変換:DeNA 開始');

    $logTitle = 'Wowma(DeNA, Bidders)';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logDbName = $dbLog->getDatabase();
    $commonUtil = $this->getDbCommonUtil();

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

    // 店舗伝票番号が空のレコードは削除（CSVの空行など）
    $sql = <<<EOD
      DELETE o
      FROM {$logDbName}.tb_mall_order_wowma o
      WHERE o.`orderId` = ''
EOD;
    $dbMain->exec($sql);

    // 変換のため、データを取得してからバルクインサート
    $mall = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_BIDDERS);
    $sql = <<<EOD
      SELECT
          `orderId`                       AS `店舗伝票番号`
        , `orderDate`                     AS `受注日`
        , REPLACE(`ordererZipCode`, '-', '') AS `受注郵便番号`
        , `ordererAddress`               AS `受注住所1`
        , ''                              AS `受注住所2`
        , `ordererName`               AS `受注名`
        , `ordererKana`               AS `受注名カナ`
        , `ordererPhoneNumber1`       AS `受注電話番号`
        , `mailAddress`               AS `受注メールアドレス`
        , REPLACE(`senderZipCode`, '-', '') AS `発送郵便番号`
        , `senderAddress`             AS `発送先住所１`
        , ''                          AS `発送先住所２`
        , `senderName`          AS `発送先名`
        , `senderKana`          AS `発送先カナ`
        , `senderPhoneNumber1`  AS `発送電話番号`
        , `settlementName`      AS `支払方法`
        , `deliveryName`        AS `発送方法`

        , `totalPrice` - `postagePrice` - `chargePrice` AS `商品計` /* 税込 */
        , 0 /* `消費税` */      AS `税金` /* 小計が税込なので、0固定 */
        , `postagePrice`                AS `発送料`
        , `chargePrice`              AS `手数料`
        , (
              CASE WHEN o.usePointCancel <> 'Y' THEN o.usePoint ELSE 0 END 
            + CASE WHEN o.useAuPointCancel <> 'Y' THEN o.useAuPointPrice ELSE 0 END
          ) AS `ポイント`
        , (-1 * couponTotalPrice) AS `その他費用`
        , `requestPrice`        AS `合計金額`

        , 0                       AS `ギフトフラグ`
        , ''                      AS `時間帯指定`
        , NULL                    AS `日付指定`
        , `memo`                  AS `作業者欄`
        , `userComment`           AS `備考`

        , `itemName`                      AS `商品名`
        , CONCAT(`itemCode`, `itemManagementId`) AS `商品コード`
        , `itemPrice`              AS `商品価格`
        , `unit`                   AS `受注数量`
        , `itemOption`             AS `商品オプション`
        , 0                               AS `出荷済フラグ`
        , 0                               AS `顧客区分`
        , ''                              AS `顧客コード`
        , :shopCode AS `shop_code`
        , id   AS `mall_order_id`
        , NULL AS `伝票番号`
        , NULL AS `明細行`
        , `itemCode` AS daihyo_syohin_code
        , `imported` AS `imported`
        , NULL       AS `converted`
        , NULL       AS `downloaded`

        , `orderOption` AS `取引オプション`

      FROM {$logDbName}.tb_mall_order_wowma o
      WHERE o.convert_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $mall->getNeMallId());
    $stmt->execute();


    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_ne_mall_order", [
      'fields' => [
          '店舗伝票番号' => \PDO::PARAM_STR
        , '受注日' => \PDO::PARAM_STR
        , '受注郵便番号' => \PDO::PARAM_STR
        , '受注住所1' => \PDO::PARAM_STR
        , '受注住所2' => \PDO::PARAM_STR
        , '受注名' => \PDO::PARAM_STR
        , '受注名カナ' => \PDO::PARAM_STR
        , '受注電話番号' => \PDO::PARAM_STR
        , '受注メールアドレス' => \PDO::PARAM_STR
        , '発送郵便番号' => \PDO::PARAM_STR
        , '発送先住所１' => \PDO::PARAM_STR
        , '発送先住所２' => \PDO::PARAM_STR
        , '発送先名' => \PDO::PARAM_STR
        , '発送先カナ' => \PDO::PARAM_STR
        , '発送電話番号' => \PDO::PARAM_STR
        , '支払方法' => \PDO::PARAM_STR
        , '発送方法' => \PDO::PARAM_STR
        , '商品計' => \PDO::PARAM_INT
        , '税金' => \PDO::PARAM_INT
        , '発送料' => \PDO::PARAM_INT
        , '手数料' => \PDO::PARAM_INT
        , 'ポイント' => \PDO::PARAM_INT
        , 'その他費用' => \PDO::PARAM_INT
        , '合計金額' => \PDO::PARAM_INT
        , 'ギフトフラグ' => \PDO::PARAM_STR
        , '時間帯指定' => \PDO::PARAM_STR
        , '日付指定' => \PDO::PARAM_STR
        , '作業者欄' => \PDO::PARAM_STR
        , '備考' => \PDO::PARAM_STR
        , '商品名' => \PDO::PARAM_STR
        , '商品コード' => \PDO::PARAM_STR
        , '商品価格' => \PDO::PARAM_INT
        , '受注数量' => \PDO::PARAM_INT
        , '商品オプション' => \PDO::PARAM_STR
        , '出荷済フラグ' => \PDO::PARAM_STR
        , '顧客区分' => \PDO::PARAM_STR
        , '顧客コード' => \PDO::PARAM_STR
        , 'shop_code' => \PDO::PARAM_INT
        , 'mall_order_id' => \PDO::PARAM_STR
        , '伝票番号' => \PDO::PARAM_INT
        , '明細行' => \PDO::PARAM_INT
        , 'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'imported' => \PDO::PARAM_STR
        , 'converted' => \PDO::PARAM_STR
        , 'downloaded' => \PDO::PARAM_STR
      ]
      , 'prefix' => 'INSERT IGNORE INTO '
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($row) use ($neMallProcess, $logger) {

      $item = $row;

      // 文言変更 支払方法
      $pre = $item['支払方法'];
      $item['支払方法'] = $neMallProcess->convertPaymentMethod($item['支払方法']);

      // 配達時間帯指定
      if (preg_match('/配達希望時間帯=(\d+-\d+時|午前中)/u', $item['取引オプション'], $m)) {
        $item['時間帯指定'] = $m[1];
      }

      // Wowma限定補正：「ポイント利用分」があるデータとないデータがまちまち。NextEngineでは請求額を勝手に加算してしまうため、こちらで先に補正。
      // 元の「請求額」（$item['合計金額']）を正として計算を合わせる。
      // NextEngine仕様： 合計金額 = 商品計＋税金＋発送料＋手数料-ポイント＋その他費用
      $calcTotal = $item['商品計']
                 + $item['税金']
                 + $item['発送料']
                 + $item['手数料']
                 + $item['その他費用']
                 - $item['ポイント']
      ;

      if ($calcTotal <>  $item['合計金額']) {
        // 計算上価格の方が大きい場合には、差額をポイントに加算する。
        if ($calcTotal > $item['合計金額']) {
          $item['ポイント'] += ($calcTotal - $item['合計金額']);

        // 請求金額の方が大きい場合には、その他費用に加算する。また、備考欄に追記する。
        } else if ($calcTotal < $item['合計金額']) {
          $item['その他費用'] += ($item['合計金額'] - $calcTotal);

          $item['備考'] = sprintf("(system)「その他費用」に%d円加算しています。\n\n", $item['合計金額'] - $calcTotal)
                        . $item['備考'];
        }
      }

      return $item;

    }, 'foreach');

    // 後は共通処理
    $this->processCommon($mall->getNeMallId());

    // 即納でなければ備考欄に「未引当あり」追加（NextEngine確認チェック用）
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_wowma i ON mo.mall_order_id = i.id
      SET mo.備考 = CONCAT('【未引当あり】', '\r\n', mo.備考)
      WHERE i.convert_flg = 0
        AND mo.`伝票未引当数` > 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // convert_flg の更新
    $sql = <<<EOD
      UPDATE
      {$logDbName}.tb_mall_order_wowma o
      SET o.convert_flg = -1
      WHERE o.convert_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    $logger->info('モール受注CSV変換:DeNA 終了');
  }

  /**
   * Yahoo 処理
   * @param $mallCode
   */
  private function processYahoo($mallCode)
  {
    $targetName = sprintf('Yahoo(%s)', $mallCode);

    $logger = $this->getLogger();
    $logger->info('モール受注CSV変換:' . $targetName . ' 開始');

    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logDbName = $dbLog->getDatabase();
    $commonUtil = $this->getDbCommonUtil();

    /** @var NextEngineMallProcess $neMallProcess */
    $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

    $logTitle = $targetName;
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // 変換のため、データを取得してからバルクインサート
    $mall = $commonUtil->getShoppingMall($commonUtil->getMallIdByMallCode($mallCode));
    $sql = <<<EOD
      SELECT
          o.OrderId                                                  AS `店舗伝票番号`
        , o.OrderTime                                                AS `受注日`
        , o.BillZipCode                                              AS `受注郵便番号`
        , CONCAT(o.BillPrefecture, ' ', o.BillCity, o.BillAddress1)  AS `受注住所1`
        , o.BillAddress2                                             AS `受注住所2`
        , o.BillName                                                 AS `受注名`
        , o.BillNameKana                                             AS `受注名カナ`
        , o.BillPhoneNumber                                          AS `受注電話番号`
        , o.BillMailAddress                                          AS `受注メールアドレス`
        , o.ShipZipCode                                              AS `発送郵便番号`
        , CONCAT(o.ShipPrefecture, ' ', o.ShipCity, o.ShipAddress1)  AS `発送先住所１`
        , o.ShipAddress2                                             AS `発送先住所２`
        , o.ShipName                                                 AS `発送先名`
        , o.ShipNameKana                                             AS `発送先カナ`
        , o.ShipPhoneNumber                                          AS `発送電話番号`
        , o.PayMethodName               AS `支払方法`
        , ''                            AS `発送方法` /* CSVの「配送方法」は利用しない */
        /* ItemCouponDiscount 分 が引かれていないSubTotal */
        , T.SubTotal                    AS `商品計` /* 税込 */
        , 0                             AS `税金` /* 小計が税込なので、0固定 */
        , o.ShipCharge                  AS `発送料`
        , o.PayCharge                   AS `手数料`
        , o.UsePoint                    AS `ポイント`
        ,   o.GiftWrapCharge
          + o.Discount
          /* + o.ShippingCouponDiscount */ /* 扱いが不明な上に利用していないので加算無し */
          - o.ItemCouponDiscount
          + o.TotalMallCouponDiscount   AS `その他費用`
        , o.Total                       AS `合計金額`
        , 0                 AS `ギフトフラグ`
        , CASE WHEN o.ShipRequestDate = '0000-00-00' THEN NULL ELSE o.ShipRequestDate END AS `日付指定`
        , CASE WHEN o.ShipRequestTime = '' THEN NULL ELSE o.ShipRequestTime END           AS `時間帯指定`
        , ''                AS `作業者欄`
        , o.BuyerComments   AS `備考`
        , i.Title           AS `商品名`
        , i.SubCode         AS `商品コード`
        , i.UnitPrice + i.CouponDiscount   AS `商品価格` /* UnitPriceは定率クーポンの減額済み */
        , i.Quantity        AS `受注数量`
        , ''                AS `商品オプション` /* 加工して生成 ※不要文言が多いので除外するのが目的 */
        , 0                 AS `出荷済フラグ`
        , 0                 AS `顧客区分`
        , ''                AS `顧客コード`

        , :shopCode     AS `shop_code`
        , i.id          AS `mall_order_id`
        , NULL          AS `伝票番号`
        , NULL          AS `明細行`
        , i.ItemId      AS daihyo_syohin_code
        , o.`imported`  AS `imported`
        , NULL          AS `converted`
        , NULL          AS `downloaded`

        /* 変換用 */
        , o.ShipNotes AS ShipNotes
        , i.ItemOptionName  AS ItemOptionName
        , i.ItemOptionValue AS ItemOptionValue

        , o.ItemCouponDiscount
        , o.ShippingCouponDiscount
        , o.TotalMallCouponDiscount
      FROM {$logDbName}.tb_mall_order_yahoo_order o
      INNER JOIN {$logDbName}.tb_mall_order_yahoo_item i ON o.MallOrderId = i.MallOrderId
      INNER JOIN (
        SELECT
            i.MallOrderId
          , SUM((i.UnitPrice + i.CouponDiscount) * i.Quantity) AS SubTotal
        FROM {$logDbName}.tb_mall_order_yahoo_item i
        GROUP BY i.MallOrderId
      ) T ON o.MallOrderId = T.MallOrderId
      WHERE o.mall_code = :mallCode
        AND o.convert_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $mall->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_ne_mall_order", [
      'fields' => [
          '店舗伝票番号' => \PDO::PARAM_STR
        , '受注日' => \PDO::PARAM_STR
        , '受注郵便番号' => \PDO::PARAM_STR
        , '受注住所1' => \PDO::PARAM_STR
        , '受注住所2' => \PDO::PARAM_STR
        , '受注名' => \PDO::PARAM_STR
        , '受注名カナ' => \PDO::PARAM_STR
        , '受注電話番号' => \PDO::PARAM_STR
        , '受注メールアドレス' => \PDO::PARAM_STR
        , '発送郵便番号' => \PDO::PARAM_STR
        , '発送先住所１' => \PDO::PARAM_STR
        , '発送先住所２' => \PDO::PARAM_STR
        , '発送先名' => \PDO::PARAM_STR
        , '発送先カナ' => \PDO::PARAM_STR
        , '発送電話番号' => \PDO::PARAM_STR
        , '支払方法' => \PDO::PARAM_STR
        , '発送方法' => \PDO::PARAM_STR
        , '商品計' => \PDO::PARAM_INT
        , '税金' => \PDO::PARAM_INT
        , '発送料' => \PDO::PARAM_INT
        , '手数料' => \PDO::PARAM_INT
        , 'ポイント' => \PDO::PARAM_INT
        , 'その他費用' => \PDO::PARAM_INT
        , '合計金額' => \PDO::PARAM_INT
        , 'ギフトフラグ' => \PDO::PARAM_STR
        , '時間帯指定' => \PDO::PARAM_STR
        , '日付指定' => \PDO::PARAM_STR
        , '作業者欄' => \PDO::PARAM_STR
        , '備考' => \PDO::PARAM_STR
        , '商品名' => \PDO::PARAM_STR
        , '商品コード' => \PDO::PARAM_STR
        , '商品価格' => \PDO::PARAM_INT
        , '受注数量' => \PDO::PARAM_INT
        , '商品オプション' => \PDO::PARAM_STR
        , '出荷済フラグ' => \PDO::PARAM_STR
        , '顧客区分' => \PDO::PARAM_STR
        , '顧客コード' => \PDO::PARAM_STR
        , 'shop_code' => \PDO::PARAM_INT
        , 'mall_order_id' => \PDO::PARAM_STR
        , '伝票番号' => \PDO::PARAM_INT
        , '明細行' => \PDO::PARAM_INT
        , 'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'imported' => \PDO::PARAM_STR
        , 'converted' => \PDO::PARAM_STR
        , 'downloaded' => \PDO::PARAM_STR
      ]
      , 'prefix' => 'INSERT IGNORE INTO '
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($row) use ($neMallProcess, $logger) {

      $item = $row;

      // 文言変更 支払方法
      $item['支払方法'] = $neMallProcess->convertPaymentMethod($item['支払方法']);

      if (strlen($item['ShipNotes'])) {
        $item['備考'] .= (strlen($item['備考']) ? "\n" : ''); // 改行はNextEngineで除去されるので意味が無いか。
        $item['備考'] .= $item['ShipNotes'];
      }

      // 商品オプション 変換 ※最初の2件のみ取得
      $options = [];
      $optionsNames = explode(';', $item['ItemOptionName']);
      $optionValues = explode(';', $item['ItemOptionValue']);
      for ($i = 0; $i < 2; $i++) {
        $name = count($optionsNames) ? array_shift($optionsNames) : '';
        $value = count($optionValues) ? array_shift($optionValues) : '';
        $options[] = sprintf('%s:%s', $name, $value);
      }
      $item['商品オプション'] = implode(' ', $options);

      // クーポン文言設定
      if ($item['ShippingCouponDiscount'] > 0) {
        $item['作業者欄'] = sprintf("[送料無料クーポン]%d\n",   abs($item['ShippingCouponDiscount'])) . $item['作業者欄'];
      }
      if ($item['ItemCouponDiscount'] > 0) {
        $item['作業者欄'] = sprintf("[定額・定率クーポン]%d\n", abs($item['ItemCouponDiscount'])) . $item['作業者欄'];
      }
      if ($item['TotalMallCouponDiscount'] < 0) {
        $item['作業者欄'] = sprintf("[モールクーポン]%d\n",     abs($item['TotalMallCouponDiscount'])) . $item['作業者欄'];
      }

      return $item;

    }, 'foreach');

    // 後は共通処理
    $this->processCommon($mall->getNeMallId());

    // 即納でなければ備考欄に「未引当あり」追加（NextEngine確認チェック用）
    // (plusnao, kawa-e-mon, otoriyoseすべて)
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_yahoo_item i ON mo.mall_order_id = i.id
      INNER JOIN {$logDbName}.tb_mall_order_yahoo_order o ON i.MallOrderId = o.MallOrderId
      SET mo.備考 = CONCAT('【未引当あり】', '\r\n', mo.備考)
      WHERE o.convert_flg = 0
        AND o.mall_code = :mallCode
        AND mo.`伝票未引当数` > 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // 北海道 佐川なら備考欄追記（送料加算のため。沖縄・離島はNextEngine設定で引っかかるためスルーでOK）
    // (plusnao, kawa-e-mon, otoriyoseすべて)
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_yahoo_item i ON mo.mall_order_id = i.id
      INNER JOIN {$logDbName}.tb_mall_order_yahoo_order o ON i.MallOrderId = o.MallOrderId
      SET mo.備考 = CONCAT('【北海道で宅配便は送料300円追加必要】', '\r\n', mo.備考)
      WHERE o.convert_flg = 0
        AND o.mall_code = :mallCode
        AND o.ShipPrefecture = '北海道'
        AND mo.`発送方法` = '佐川急便(e飛伝2)'
        AND mo.`発送料` = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // convert_flg の更新
    $sql = <<<EOD
      UPDATE
      {$logDbName}.tb_mall_order_yahoo_order o
      SET o.convert_flg = -1
      WHERE o.mall_code = :mallCode
        AND o.convert_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    $logger->info('モール受注CSV変換:' . $targetName . ' 終了');
  }

  /**
   * Q10 処理
   */
  private function processQ10()
  {
    $logger = $this->getLogger();
    $logger->info('モール受注CSV変換:Q10 開始');

    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logDbName = $dbLog->getDatabase();
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = 'Q10';
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));

    // 変換のため、データを取得してからバルクインサート
    $mall = $commonUtil->getShoppingMall(DbCommonUtil::MALL_ID_Q10);
    $sql = <<<EOD
      SELECT
          s.受注番号             AS `店舗伝票番号`
        , s.受注日               AS `受注日`
        , s.購入者郵便番号       AS `受注郵便番号`
        , s.購入者住所1          AS `受注住所1`
        , s.購入者住所2          AS `受注住所2`
        , s.購入者名             AS `受注名`
        , s.購入者カナ           AS `受注名カナ`
        , s.購入者電話番号       AS `受注電話番号`
        , s.購入者メールアドレス AS `受注メールアドレス`
        , s.送り先郵便番号       AS `発送郵便番号`
        , s.送り先住所1          AS `発送先住所１`
        , s.送り先住所2          AS `発送先住所２`
        , s.送り先名             AS `発送先名`
        , s.送り先カナ           AS `発送先カナ`
        , s.送り先電話番号       AS `発送電話番号`
        , s.支払方法             AS `支払方法`
        , o.配送会社             AS `発送方法` /* ここだけQ10の配送要請CSVの値で上書き */
        , s.商品計               AS `商品計`
        , s.税金                 AS `税金`
        , s.発送代               AS `発送料`
        , s.手数料               AS `手数料`
        , s.ポイント数           AS `ポイント`
        , s.他費用               AS `その他費用`
        , s.総合計               AS `合計金額`
        , s.ギフト               AS `ギフトフラグ`
        , s.配送時間帯           AS `時間帯指定`
        , s.配達希望日           AS `日付指定`
        , s.作業用欄             AS `作業者欄`
        , s.備考                 AS `備考`
        , s.`商品名（伝票）`      AS `商品名`
        , s.`商品コード（伝票）`  AS `商品コード`
        , s.売単価               AS `商品価格`
        , s.受注数               AS `受注数量`
        , s.商品オプション        AS `商品オプション`
        , 0                      AS `出荷済フラグ`
        , s.顧客区分             AS `顧客区分`
        , s.顧客cd               AS `顧客コード`
        , :neMallId         AS `shop_code`
        , o.id              AS `mall_order_id`
        , s.伝票番号        AS `伝票番号`
        , s.明細行          AS `明細行`
        , o.販売者商品コード AS daihyo_syohin_code
        , o.`imported`      AS `imported`
        , NULL              AS `converted`
        , NULL              AS `downloaded`
      FROM tb_sales_detail s
      INNER JOIN {$logDbName}.tb_mall_order_q10 o ON s.`店舗コード` = :neMallId
                                                  AND o.`注文番号` = s.`受注番号`
                                                  AND s.受注状態 = '受注メール取込済'
      WHERE o.convert_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallId', $mall->getNeMallId());
    $stmt->execute();

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_ne_mall_order", [
      'fields' => [
          '店舗伝票番号' => \PDO::PARAM_STR
        , '受注日' => \PDO::PARAM_STR
        , '受注郵便番号' => \PDO::PARAM_STR
        , '受注住所1' => \PDO::PARAM_STR
        , '受注住所2' => \PDO::PARAM_STR
        , '受注名' => \PDO::PARAM_STR
        , '受注名カナ' => \PDO::PARAM_STR
        , '受注電話番号' => \PDO::PARAM_STR
        , '受注メールアドレス' => \PDO::PARAM_STR
        , '発送郵便番号' => \PDO::PARAM_STR
        , '発送先住所１' => \PDO::PARAM_STR
        , '発送先住所２' => \PDO::PARAM_STR
        , '発送先名' => \PDO::PARAM_STR
        , '発送先カナ' => \PDO::PARAM_STR
        , '発送電話番号' => \PDO::PARAM_STR
        , '支払方法' => \PDO::PARAM_STR
        , '発送方法' => \PDO::PARAM_STR
        , '商品計' => \PDO::PARAM_INT
        , '税金' => \PDO::PARAM_INT
        , '発送料' => \PDO::PARAM_INT
        , '手数料' => \PDO::PARAM_INT
        , 'ポイント' => \PDO::PARAM_INT
        , 'その他費用' => \PDO::PARAM_INT
        , '合計金額' => \PDO::PARAM_INT
        , 'ギフトフラグ' => \PDO::PARAM_STR
        , '時間帯指定' => \PDO::PARAM_STR
        , '日付指定' => \PDO::PARAM_STR
        , '作業者欄' => \PDO::PARAM_STR
        , '備考' => \PDO::PARAM_STR
        , '商品名' => \PDO::PARAM_STR
        , '商品コード' => \PDO::PARAM_STR
        , '商品価格' => \PDO::PARAM_INT
        , '受注数量' => \PDO::PARAM_INT
        , '商品オプション' => \PDO::PARAM_STR
        , '出荷済フラグ' => \PDO::PARAM_STR
        , '顧客区分' => \PDO::PARAM_STR
        , '顧客コード' => \PDO::PARAM_STR
        , 'shop_code' => \PDO::PARAM_INT
        , 'mall_order_id' => \PDO::PARAM_STR
        , '伝票番号' => \PDO::PARAM_INT
        , '明細行' => \PDO::PARAM_INT
        , 'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'imported' => \PDO::PARAM_STR
        , 'converted' => \PDO::PARAM_STR
        , 'downloaded' => \PDO::PARAM_STR
      ]
      , 'prefix' => 'INSERT IGNORE INTO '
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($row) use ($logger) {

      $item = $row;

      // 備考欄 <割引金額>\xxx をポイントに加算して削除 ※ここだけバックスラッシュ頑張ってるのは仕方ないから
      $pattern = '/^\\<割引金額\\>\\\\(\\d+)\\r?\\n/um';
      if (preg_match($pattern, $item['備考'], $m)) {
        $discount = intval($m[1]);
        $item['ポイント'] += $discount;
        $item['備考'] = preg_replace($pattern, '', $item['備考']);
      }

      // 備考からカート番号を取得し、カート番号行を削除
      $cartNumber = '';
      if (preg_match('/^カート番号：(\d+)\r?\n/m', $item['備考'], $m)) {
        $cartNumber = $m[1];
        $item['備考'] = preg_replace('/^カート番号：\d+\r?\n/m', '', $item['備考']); // カート番号行を削除（備考に残っているとNextEngine確認チェックで邪魔）
      }

      // 商品名の先頭に元注文番号およびカート番号を追加
      $item['商品名'] = sprintf('%s-%s %s', $item['店舗伝票番号'], $cartNumber, $item['商品名']);

      // 備考が空白文字のみならざくっと削除。
      $item['備考'] = preg_replace('/\A\s+\z/m', '', $item['備考']);

      // 日付指定で0000-00-00はエラーになるので変換
      if ($item['日付指定'] == '0000-00-00 00:00:00') {
        $item['日付指定'] = null;
      }

      return $item;

    }, 'foreach');

    // 後は共通処理
    $this->processCommon($mall->getNeMallId());

    // 即納でなければ備考欄に「未引当あり」追加（NextEngine確認チェック用）
    // (plusnao, kawa-e-mon, otoriyoseすべて)
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_q10 i ON mo.mall_order_id = i.id
      SET mo.備考 = CONCAT('【未引当あり】', '\r\n', mo.備考)
      WHERE i.convert_flg = 0
        AND mo.`伝票未引当数` > 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // Q10特殊処理：Delivery, Tracking データ作成
    // 受注データ抽出一時テーブル （そのままJOINすると、条件がひどい（商品タイトルの部分一致）ので動かなくなる。）
    $temporaryWord = ' TEMPORARY ';
    // $temporaryWord = ' '; // FOR DEBUG
    $dbMain->exec("DROP {$temporaryWord} TABLE IF EXISTS tmp_work_q10_order ");
    $sql = <<<EOD
      CREATE {$temporaryWord} TABLE tmp_work_q10_order (
          伝票番号 VARCHAR(20) NOT NULL
        , 明細行 INTEGER NOT NULL
        , 注文番号 VARCHAR(20) NOT NULL
        , カート番号 VARCHAR(20) NOT NULL DEFAULT ''
        , 受注状態 VARCHAR(20) NOT NULL DEFAULT ''
        , 出荷予定年月日 DATE
        , 発送方法 VARCHAR(20) NOT NULL DEFAULT ''
        , 発送伝票番号 VARCHAR(20) NOT NULL DEFAULT ''
      ) Engine=InnoDB DEFAULT CHARACTER SET utf8
EOD;
    $dbMain->exec($sql);

    // データ流し込み（直近3ヶ月分）
    $borderDate = (new \DateTime())->modify('-3 Month');
    $sql = <<<EOD
      SELECT
          a.伝票番号
        , a.明細行
        , a.受注状態
        , a.出荷予定年月日
        , a.発送方法
        , a.発送伝票番号

        , a.商品名（伝票） AS 商品名
      FROM tb_sales_detail_analyze a
      WHERE a.受注日 >= :borderDate
        AND a.店舗コード = :neMallId
        AND a.キャンセル区分 = '0'
        AND a.明細行キャンセル = '0'
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':neMallId', $mall->getNeMallId(), \PDO::PARAM_INT);
    $stmt->bindValue(':borderDate', $borderDate->format('Y-m-d'), \PDO::PARAM_STR);
    $stmt->execute();

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tmp_work_q10_order", [
      'fields' => [
          '伝票番号'       => \PDO::PARAM_STR
        , '明細行'         => \PDO::PARAM_STR
        , '注文番号'       => \PDO::PARAM_STR
        , 'カート番号'     => \PDO::PARAM_STR
        , '受注状態'       => \PDO::PARAM_STR
        , '出荷予定年月日' => \PDO::PARAM_STR
        , '発送方法'       => \PDO::PARAM_STR
        , '発送伝票番号'   => \PDO::PARAM_STR
      ]
      , 'prefix' => 'INSERT IGNORE INTO '
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($row) use ($logger) {

      $item = $row;

      // 注文番号・カート番号を商品タイトルから復元。※このあたりが、なんとももっさい。
      $item['注文番号'] = '';
      $item['カート番号'] = '';

      if (preg_match('/([\d]{9})-([\d]{8,9})?[^\d]/', $item['商品名'], $m)) {
        $item['注文番号'] = $m[1];
        $item['カート番号'] = isset($m[2]) ? $m[2] : '';
      }

      return $item;

    }, 'foreach');

    // Delivery (出荷予定日 更新)
    // ※最初のアップロード時に格納しているためこちらは不要だが、もし受注明細から取得するようになればここでやる、という意味で残しておきます。

    // ---------- ここから
    // 取込日から2営業日後で固定。CSVでの登録はこれで行う。（実際の遅延処理はコンシェルのフローで別途行う）
    // $shippingDate = $commonUtil->getWorkingDateAfterDays(new \DateTime(), 2);
    // => さらに、もう営業日関係なく固定で3日後でよい。とのこと
    // => 2018/01/30 さらにQ10が最近うるさいため、4日後に。（後に戻す想定）
    // => 2018/01/31 4日後にするとエラーになるとのこと。3日後に戻す。Q10ひどい
    $today = new \DateTime();
    $shippingDate = $today->modify('+3 day');

    $sql = <<<EOD
      UPDATE
      {$logDbName}.tb_mall_order_q10 q
      INNER JOIN tmp_work_q10_order o ON q.注文番号 = o.注文番号 AND q.カート番号 = o.カート番号
      SET q.発送予定日 = :shippingDate
      WHERE q.`発送予定日` = ''
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shippingDate', $shippingDate->format('Y/m/d'));
    $stmt->execute();
    // ---------- ここまで、現在は不要。


    // Tracking（発送番号）
    $sql = <<<EOD
      UPDATE
      {$logDbName}.tb_mall_order_q10 q
      INNER JOIN tmp_work_q10_order o ON q.注文番号 = o.注文番号 AND q.カート番号 = o.カート番号
      SET q.送り状番号 = o.発送伝票番号
      WHERE q.送り状番号 = ''
        AND o.受注状態 = '出荷確定済（完了）'
        AND o.発送伝票番号 <> ''
        AND o.発送方法 IN (
            'ﾔﾏﾄ(メール便)B2v6'
          , '佐川急便(e飛伝2)'
          , 'ゆうパケット'
          , 'ヤマト(ネコポス)'
        )
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->execute();

    // convert_flg の更新
    $sql = <<<EOD
      UPDATE {$logDbName}.tb_mall_order_q10 o
      SET o.convert_flg = -1
      WHERE o.convert_flg = 0
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    $logger->info('モール受注CSV変換:Q10 終了');
  }

  /**
   * EC-CUBE処理
   * @param $mallCode
   */
  private function processEcCube($mallCode)
  {
    $logger = $this->getLogger();
    $logger->info('モール受注CSV変換:EC-CUBE 開始');

    $dbMain = $this->getDb('main');
    $dbLog = $this->getDb('log');
    $logDbName = $dbLog->getDatabase();
    $commonUtil = $this->getDbCommonUtil();

    $logTitle = sprintf('EC-CUBE(%s)', $mallCode);
    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '開始'));


    // 変換のため、データを取得してからバルクインサート
    $mallId = $commonUtil->getMallIdByMallCode($mallCode);
    $mall = $commonUtil->getShoppingMall($mallId);
    $sql = <<<EOD
      SELECT
          o.`order_id`                                                          AS `店舗伝票番号`
        , o.`order_date`                                                        AS `受注日`
        , CONCAT(o.`order_zip01`, o.`order_zip02`)                          AS `受注郵便番号`
        , CONCAT(o.`order_pref_name`, ' ', o.order_addr01, o.order_addr02)        AS `受注住所1`
        , ''                                                                  AS `受注住所2`
        , CONCAT(o.order_name01, ' ', o.order_name02)                              AS `受注名`
        , CONCAT(o.order_kana01, ' ', o.order_kana02)               AS `受注名カナ`
        , CONCAT(o.order_tel01, '-', o.order_tel02, '-', o.order_tel03)       AS `受注電話番号`
        , o.order_email                                                                  AS `受注メールアドレス`
        , CONCAT(o.shipping_zip01, o.shipping_zip02)                          AS `発送郵便番号`
        , CONCAT(o.shipping_pref_name, ' ', o.shipping_addr01, o.shipping_addr02)         AS `発送先住所１`
        , ''                                                                   AS `発送先住所２`
        , CONCAT(o.shipping_name01, ' ', o.shipping_name02)                               AS `発送先名`
        , CONCAT(o.shipping_kana01, ' ', o.shipping_kana02)                AS `発送先カナ`
        , CONCAT(o.shipping_tel01, '-', o.shipping_tel02, '-', o.shipping_tel03)  AS `発送電話番号`
        , o.payment_method                                                           AS `支払方法`
        , ''                                                                  AS `発送方法` /* CSVの「配送方法」は利用しない */
        , o.subtotal                                                              AS `商品計` /* 税抜き */
        , o.tax AS `税金`
        , o.delivery_fee_total   AS `発送料`
        , o.charge AS `手数料`
        , 0        AS `ポイント`
        , 0 AS `その他費用`
        , o.total AS `合計金額`
        , 0 AS `ギフトフラグ`
        , o.shipping_delivery_time AS `時間帯指定`
        , o.shipping_delivery_date AS `日付指定`
        , o.note AS `作業者欄`
        , o.message AS `備考`
        , d.product_name AS `商品名`
        , d.product_code AS `商品コード`
        , d.price AS `商品価格`
        , d.quantity AS `受注数量`
        , '' AS `商品オプション`
        , 0 AS `出荷済フラグ`
        , 0 AS `顧客区分`
        , '' AS `顧客コード`
        , :shopCode AS `shop_code`
        , d.order_detail_id   AS `mall_order_id`
        , NULL AS `伝票番号`
        , NULL AS `明細行`
        , pci.daihyo_syohin_code  AS daihyo_syohin_code
        , `imported` AS `imported`
        , NULL       AS `converted`
        , NULL       AS `downloaded`

        , pci.フリー在庫数 AS フリー在庫数
      FROM {$logDbName}.tb_mall_order_ec o
      INNER JOIN {$logDbName}.tb_mall_order_ec_detail d ON o.mall_code = d.mall_code AND o.order_id = d.order_id
      INNER JOIN tb_productchoiceitems pci ON d.product_code = pci.ne_syohin_syohin_code
      WHERE o.convert_flg = 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':shopCode', $mall->getNeMallId());
    $stmt->execute();

    // 一括insert
    $insertBuilder = new MultiInsertUtil("tb_ne_mall_order", [
      'fields' => [
          '店舗伝票番号' => \PDO::PARAM_STR
        , '受注日' => \PDO::PARAM_STR
        , '受注郵便番号' => \PDO::PARAM_STR
        , '受注住所1' => \PDO::PARAM_STR
        , '受注住所2' => \PDO::PARAM_STR
        , '受注名' => \PDO::PARAM_STR
        , '受注名カナ' => \PDO::PARAM_STR
        , '受注電話番号' => \PDO::PARAM_STR
        , '受注メールアドレス' => \PDO::PARAM_STR
        , '発送郵便番号' => \PDO::PARAM_STR
        , '発送先住所１' => \PDO::PARAM_STR
        , '発送先住所２' => \PDO::PARAM_STR
        , '発送先名' => \PDO::PARAM_STR
        , '発送先カナ' => \PDO::PARAM_STR
        , '発送電話番号' => \PDO::PARAM_STR
        , '支払方法' => \PDO::PARAM_STR
        , '発送方法' => \PDO::PARAM_STR
        , '商品計' => \PDO::PARAM_INT
        , '税金' => \PDO::PARAM_INT
        , '発送料' => \PDO::PARAM_INT
        , '手数料' => \PDO::PARAM_INT
        , 'ポイント' => \PDO::PARAM_INT
        , 'その他費用' => \PDO::PARAM_INT
        , '合計金額' => \PDO::PARAM_INT
        , 'ギフトフラグ' => \PDO::PARAM_STR
        , '時間帯指定' => \PDO::PARAM_STR
        , '日付指定' => \PDO::PARAM_STR
        , '作業者欄' => \PDO::PARAM_STR
        , '備考' => \PDO::PARAM_STR
        , '商品名' => \PDO::PARAM_STR
        , '商品コード' => \PDO::PARAM_STR
        , '商品価格' => \PDO::PARAM_INT
        , '受注数量' => \PDO::PARAM_INT
        , '商品オプション' => \PDO::PARAM_STR
        , '出荷済フラグ' => \PDO::PARAM_STR
        , '顧客区分' => \PDO::PARAM_STR
        , '顧客コード' => \PDO::PARAM_STR
        , 'shop_code' => \PDO::PARAM_INT
        , 'mall_order_id' => \PDO::PARAM_STR
        , '伝票番号' => \PDO::PARAM_INT
        , '明細行' => \PDO::PARAM_INT
        , 'daihyo_syohin_code' => \PDO::PARAM_STR
        , 'imported' => \PDO::PARAM_STR
        , 'converted' => \PDO::PARAM_STR
        , 'downloaded' => \PDO::PARAM_STR
      ]
      , 'prefix' => 'INSERT IGNORE INTO '
    ]);

    $commonUtil->multipleInsert($insertBuilder, $dbMain, $stmt, function($row) use ($logger) {

      $item = $row;

      // 文言変更
      // フリー在庫数に足りているか否かで、商品タイトルに【即納】【お取り寄せ】どちらかを追記。
      $item['商品名'] = sprintf("%s %s"
                        , ($item['受注数量'] <= $item['フリー在庫数'] ? '【即納】' : '【お取り寄せ】')
                        , $item['商品名']
      );

      return $item;

    }, 'foreach');

    // 後は共通処理
    $this->processCommon($mall->getNeMallId());

    // 即納でなければ備考欄に「未引当あり」追加（NextEngine確認チェック用）
    // (plusnao, kawa-e-mon, otoriyoseすべて)
    $sql = <<<EOD
      UPDATE tb_ne_mall_order mo
      INNER JOIN {$logDbName}.tb_mall_order_ec_detail i ON mo.mall_order_id = i.order_detail_id
      INNER JOIN {$logDbName}.tb_mall_order_ec o ON i.order_id = o.order_id
      SET mo.備考 = CONCAT('【未引当あり】', '\r\n', mo.備考)
      WHERE o.convert_flg = 0
        AND o.mall_code = :mallCode
        AND mo.`伝票未引当数` > 0
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':mallCode', $mallCode, \PDO::PARAM_STR);
    $stmt->execute();

    // convert_flg の更新
    $sql = <<<EOD
      UPDATE {$logDbName}.tb_mall_order_ec o
      SET o.convert_flg = -1
      WHERE o.convert_flg = 0
EOD;
    $dbMain->query($sql);

    $logger->addDbLog($logger->makeDbLog(null, $logTitle, '終了'));
    $logger->info('モール受注CSV変換:EC-CUBE 終了');
  }


  /**
   * 発送方法 および 送料その他設定処理
   * @param array $voucher
   * @param TbShoppingMall $shoppingMall
   * @internal param $ &array $voucher
   */
  private function setShippingMethod(&$voucher, $shoppingMall)
  {
    $commonUtil = $this->getDbCommonUtil();

    $daibikiFreeTaxPrice = intval($commonUtil->getSettingValue('YAHOO_DAIBIKI_FREE_TACPRICE')); // 設定名はYahooだが全モール共通
    $postageFreeTaxPrice = intval($commonUtil->getSettingValue('POSTAGE_FREE_TAXPRICE'));
    $postageFreeTaxPriceEc02 = intval($commonUtil->getSettingValue('POSTAGE_FREE_TAXPRICE_EC02'));

    $teikeigaiLimitWeight = intval($commonUtil->getSettingValue('ABNORMAL_MAX_WEIGHT'));

    $nekoposuVolumeLimit = $this->getNekoposuVolumeLimit();
    $nekoposuWeightLimit = $this->getNekoposuWeightLimit();

    // 大きいものから順次見ていく
    // 2018/06/14 メール便は利用不可。即エラー
    // 2019/10/11 自動設定番号と自動設定送料を全面廃止
    
    $voucher['自動設定番号'] = 99;
    $voucher['自動設定送料'] = 0;

    // club-forest (ec02)は、10,000円以上送料無料 かつ、代引き手数料無料は無し。
    if ($shoppingMall->isClubForest()) {
      // 10,000円以上、送料無料
      if ($voucher['商品計'] + $voucher['税金'] >= $postageFreeTaxPriceEc02) {
        $voucher['自動設定送料'] = 0;
      }

    // club-forest以外は共通
    } else {
      // 10,000 円以上、代引手数料無料
      if ($voucher['商品計'] + $voucher['税金'] >= $daibikiFreeTaxPrice) {
        $voucher['自動設定手数料'] = 0;
      }

      // 7,000 円以上、送料無料
      if ($voucher['商品計'] + $voucher['税金'] >= $postageFreeTaxPrice) {
        $voucher['自動設定送料'] = 0;
      }
    }

    // 手数料に変更がある場合、コメントおよび価格修正
    if ($voucher['手数料'] != $voucher['自動設定手数料']) {
      $diff = $voucher['自動設定手数料'] - $voucher['手数料'];

      $voucher['手数料'] = $voucher['手数料'] + $diff;
      $voucher['合計金額'] = $voucher['合計金額'] + $diff;

      $voucher['備考'] = sprintf("(system) 手数料を %d 円加算しています。モール処理が必要です。\r\n", $diff) . $voucher['備考'];
    }

    // 送料に変更がある場合、コメントおよび価格修正
    if ($voucher['発送料'] != $voucher['自動設定送料']) {
      $diff = $voucher['自動設定送料'] - $voucher['発送料'];

      $voucher['発送料'] = $voucher['発送料'] + $diff;
      $voucher['合計金額'] = $voucher['合計金額'] + $diff;

      $voucher['備考'] = sprintf("(system) 送料を %d 円加算しています。モール処理が必要です。\r\n", $diff) . $voucher['備考'];
    }

    // FBAマルチ商品が含まれていれば、その旨記載（伝票分割を行う）
    if ($voucher['has_fba_multi']) {
      $voucher['備考'] = sprintf("(system) FBAマルチ商品が含まれています。伝票の分割が必要です。\r\n") . $voucher['備考'];
    }
  }
  
    /**
   * 発送方法 および 送料その他設定処理
   * @param array $voucher
   * @param array $rules
   * @param string $maxName
   */
  private function setShippingMethodNew(&$voucher, $rules, $maxName)
  {
    /** @var TbPrefectureRepository $pRepo */
    $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');

    $logger = $this->getLogger();
  
    $long = $voucher['size']['long'];
    $middle = $voucher['size']['middle'];
    $short = $voucher['size']['short'];
    $weight = $voucher['size']['weight'];
    $totallength = $voucher['size']['total'];
    $volume = $long * $middle * $short;


    foreach($rules as $rule){
      // 短辺チェック
      if($rule['shortlength'] > 0 && $short > $rule['shortlength']){
        continue;
      }

      // 中辺チェック
      if($rule['middlelength'] > 0 && $middle > $rule['middlelength']){
        continue;
      }

      // 長辺チェック
      if($rule['longlength'] > 0 && $long > $rule['longlength']){
        continue;
      }

      // サイズチェック
      if($rule['sizecheck'] > 0 && $totallength > $rule['totallength']){
        continue;
      }

      // 明細数が一つ
      if($voucher['明細数'] === 1){
        // 重量チェック
        if($weight * $voucher['受注数'] > $rule['weight']){
          continue;
        }
        
        // 複数購入チェック
        if($voucher['受注数'] > 1){
          if($rule['sizecheck'] == 0){
          
            // サイズチェックなし
            
            // 重ね数（短辺）のチェック
            $overlapCount = floor($rule['shortlength'] / $short);
            if($overlapCount == 0){
              $overlapCount = 1;
            }
            
            // 平面的にいくつ入るか
            $areaCount = floor(($rule['middlelength'] * $rule['longlength']) / ($middle * $long));
            $boxedCount = $overlapCount * $areaCount;
            
            // 入る数でチェック
            if($voucher['受注数'] > $boxedCount){
              continue;
            }
          } else {
            // サイズチェックあり
            // 体積チェック
            $volumeCount = floor($rule['volume'] / $volume);
            
            // 入る数でチェック
            if($voucher['受注数'] > $volumeCount){
              continue;
            }
          }
        }

      // 明細が複数（同梱）
      } else {
        // 重量チェック
        if($voucher['weight_total'] > $rule['weight']){
          continue;
        }
        
        // 体積チェック
        if($voucher['volume_total'] > $rule['volume']){
          continue;
        }
      }

      // 都道府県チェック
      if ($rule['prefecture_check_column']) {
        // 受注情報から発注先都道府県を取得
        $shippingPrefecture = $this->extractPrefectureFromAddress($voucher['発送先住所１']);

        // 利用可フラグ確認
        if (!in_array($shippingPrefecture, $rule['prefectures'], true)) {
          continue;
        }
      }

      // 決定したらセット
      $voucher['発送方法'] = $rule['delivery_name'];
      return ;
    }
    
    // 決定しなかったら最大をセット
    $voucher['発送方法'] = $maxName;
    return;
  }

  /**
   * ヤマトに変更可能か判定する
   * @param array $voucher
   * @return int 1|0 tb_prefecture.〇〇_available_flg
   */
  private function checkYamatoAvailability($voucher)
  {
    /** @var TbPrefectureRepository $pRepo */
    $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');

    if ($voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_60) {
      $prefectureCheckColumn = 'yamato60_available_flg';
    } elseif ($voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_80) {
      $prefectureCheckColumn = 'yamato80_available_flg';
    } elseif ($voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_100) {
      $prefectureCheckColumn = 'yamato140_available_flg';
    } elseif ($voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_140) {
      $prefectureCheckColumn = 'yamato140_available_flg';
    } elseif ($voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_160) {
      $prefectureCheckColumn = 'yamato160_available_flg';
    } else {
      return 0;
    }

    $shippingPrefecture = $this->extractPrefectureFromAddress($voucher['発送先住所１']);
    return $pRepo->checkPrefectureCheckColumnAvailability($prefectureCheckColumn, $shippingPrefecture);
  }

  /**
   * 佐川に変更可能か判定する
   * @param array $voucher
   * @return int 1|0 tb_prefecture.〇〇_available_flg
   */
  private function checkSagawaAvailability($voucher)
  {
    /** @var TbPrefectureRepository $pRepo */
    $pRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPrefecture');

    if ($voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_60) {
      $prefectureCheckColumn = 'sagawa60_available_flg';
    } elseif ($voucher['送料設定ID'] == TbShippingdivision::SHIPPING_DIVISION_ID_TAKUHAIBIN_80) {
      $prefectureCheckColumn = 'sagawa80_available_flg';
    } else {
      return 0;
    }

    $shippingPrefecture = $this->extractPrefectureFromAddress($voucher['発送先住所１']);
    return $pRepo->checkPrefectureCheckColumnAvailability($prefectureCheckColumn, $shippingPrefecture);
  }

  /**
   * 住所文字列から都道府県情報を抜き出して返す。
   * @param string $address 住所情報
   * @return string 都道府県情報
   */
  private function extractPrefectureFromAddress($address)
  {
    foreach (TbPrefecture::PREFECTURE_NAMES as $prefecture) {
      if (preg_match("/$prefecture/u", $address, $matches)) {
        return $matches[0];
      }
    }
    // 該当がなければ、空文字を返却
    return '';
  }

  /**
   * 納期情報 設定
   * @param array $voucher
   * @param \DateTime $immediateShippingDate
   */
  private function setShippingDate(&$voucher, $immediateShippingDate)
  {
    // 全て引き当たって入れば即納
    if (! $voucher['no_sales_detail'] && $voucher['未引当数'] == 0) {
      $shippingDate = null;
      if ($voucher['shipping_date']) {
        $shippingDate = new \DateTime($voucher['shipping_date']);
        $shippingDate->setTime(0, 0, 0);
      }

      if (!$shippingDate || $shippingDate > $immediateShippingDate) {
        $voucher['shipping_date'] = $immediateShippingDate->format('Y-m-d');
      }

    }

  }

  /**
   * ネコポス容積上限
   */
  private function getNekoposuVolumeLimit()
  {
    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

    $limits = $mallProcess->getNekoposuSizeWeightLimits();

    return $limits['side1'] * $limits['side2'] * $limits['side3'];
  }


  /**
   * ネコポス重量上限設定
   */
  private function getNekoposuWeightLimit()
  {
    /** @var NextEngineMallProcess $mallProcess */
    $mallProcess = $this->getContainer()->get('batch.mall_process.next_engine');

    $limits = $mallProcess->getNekoposuSizeWeightLimits();

    return $limits['weight'];
  }

}
