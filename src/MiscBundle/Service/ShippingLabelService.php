<?php

namespace MiscBundle\Service;

use MiscBundle\Entity\Repository\TbPostalZipsiwakeRepository;
use MiscBundle\Entity\Repository\TbSalesDetailRepository;
use MiscBundle\Entity\Repository\TbShipmentTrackingNumberRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherRepository;
use MiscBundle\Entity\Repository\TbUsedTrackingNumberReportRepository;
use MiscBundle\Entity\TbDeliveryMethod;
use MiscBundle\Entity\TbShippingdivision;
use MiscBundle\Service\ServiceBaseTrait;
use MiscBundle\Entity\TbSalesDetail;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Util\FileUtil;
use MiscBundle\Util\ImageUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;

/**
 * 出荷ラベルService。
 *
 * loggerやdoctrineなどはコンストラクタで定義しているため各メソッドでそのまま使用可能。
 */
class ShippingLabelService
{
  use ServiceBaseTrait;

  /**
   * 出荷ラベルをPDFで作成する。
   * @param array $voucherNumbers 伝票番号の配列
   * @param integer|string $deliveryMethodId 配送方法ID
   * @param boolean $isDebug Truthyなら、PDFでなくHTML形式で表示。厳密な型判定は行わない。
   */
  public function makeShippingLabelPdf($voucherNumbers, $deliveryMethodId, $isDebug = false)
  {
    /** @var TbShipmentTrackingNumberRepository $repoTracking */
    $repoTracking = $this->getDoctrine()->getRepository('MiscBundle:TbShipmentTrackingNumber');

    // 発送方法ID毎に出力を切り替え。
    switch ($deliveryMethodId) {
      case TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEI: // 定形
      case TbSalesDetail::SHIPPING_METHOD_CODE_TEIKEIGAI: // 定形外
        // 自社管理発送伝票番号使用済の伝票が有れば、ステータスをキャンセルに変更する
        $repoTracking->updateStatusToCancelled($voucherNumbers);

        $response = $this->createPdfResponseTeikeigai($voucherNumbers, $isDebug);
        break;
      case TbSalesDetail::SHIPPING_METHOD_CODE_YUU_PACKET: // ゆうパケット
        $response = $this->createPdfResponseYuuPacket($voucherNumbers, $isDebug);
        break;
      default:
        throw new \RuntimeException('unknown delivery method: ' . $deliveryMethodId);
    }

    return $response;
  }

  /**
   * 定形外用出荷ラベルをPDFで作成する。
   * @param array $voucherNumbers 伝票番号の配列
   * @param boolean $isDebug Truthyなら、PDFでなくHTML形式で表示。厳密な型判定は行わない。
   * @return Response $response
   */
  public function createPdfResponseTeikeigai($voucherNumbers, $isDebug){
    /** @var TbSalesDetailRepository $repoVoucher */
    $repoVoucher = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
    $vouchers = $repoVoucher->getVoucherByVoucherNumbers($voucherNumbers, true);
    
    $voucherNumbers = [];
    foreach ($vouchers as $voucher) {
      $voucherNumbers[] = $voucher->getVoucherNumber();
    }
    
    // 配送情報を取得する。これは納品書CSVアップロード時のデータ。$vouchersのデータはamazonデータがマスクされているため依頼元・配送先データ取得にはこれを利用
    /** @var TbShippingVoucherRepository $shippingVoucherRepo */
    $shippingVoucherRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    $shippingInfos = $shippingVoucherRepo->getShippingInfoByVoucherNumbers($voucherNumbers);

    /** @var ImageUtil $imageUtil ※バーコード用 */
    $imageUtil = $this->getContainer()->get('misc.util.image');
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getFileUtil();

    // 料金後納郵便のパスを取得
    $webDir = $fileUtil->getWebDir();
    $this->getLogger()->info($webDir .  '/img/postpaid-mail-symbol.png');
    $postpaidMailSymbolBase64 = base64_encode(file_get_contents($webDir . '/img/postpaid-mail-symbol.png'));

    // 配送先情報の詰め直し
    $data = [];
    foreach($vouchers as $voucher) {
      $item = $voucher->toScalarArray();
      $row = $item;
      $shippingInfo = null;
      if (isset($shippingInfos[$voucher->getVoucherNumber()])) {
        $shippingInfo = $shippingInfos[$voucher->getVoucherNumber()];
      }
      
      // バーコードを生成（1件ずつ）
      $row['barcode'] = $imageUtil->getBarcodeSVG($voucher->getVoucherNumber(), 'CODABAR', false, 1.2, 24);

      $row['伝票no'] = $voucher->getVoucherNumber();
      $row['受注no'] = $voucher->getOrderNumber();

      // 配送先情報をセット
      if ($shippingInfo) {
        $row['受注名'] = $shippingInfo['購入者名'];
        $row['受注郵便番号'] = $shippingInfo['購入者郵便番号'];
        $row['受注者住所'] = $shippingInfo['購入者住所1'] . $shippingInfo['購入者住所2'];
        $row['受注者電話番号'] = $shippingInfo['購入者電話番号'];

        $row['発送先名'] = $shippingInfo['発送先名'];
        $row['発送先名length'] = mb_strwidth($row['発送先名']);
        $row['発送先郵便番号'] = $shippingInfo['発送先郵便番号'];
        $row['発送先住所'] = $shippingInfo['発送先住所1'] . $shippingInfo['発送先住所2'];
        $row['発送先住所length'] = mb_strwidth($row['発送先住所']);
        $row['発送先電話番号'] = $shippingInfo['発送先電話番号'];
      } else {
        $row['受注名'] = $voucher->getCustomerName();
        $row['受注郵便番号'] = $voucher->getCustomerZipcode();
        $row['受注者住所'] = $voucher->getCustomerAddress1() . $voucher->getCustomerAddress2();
        $row['受注者電話番号'] = $voucher->getCustomerTel();

        $row['発送先名'] = $voucher->getDeliveryName();
        $row['発送先名length'] = mb_strwidth($row['発送先名']);
        $row['発送先郵便番号'] = $voucher->getDeliveryZipcode();
        $row['発送先住所'] = $voucher->getDeliveryAddress1() . $voucher->getDeliveryAddress2();
        $row['発送先住所length'] = mb_strwidth($row['発送先住所']);
        $row['発送先電話番号'] = $voucher->getDeliveryTel();
      }

      $row['企業名'] = '株式会社フォレスト';
      $row['企業郵便番号'] = '6308424';
      $row['企業住所'] = '奈良県奈良市古市町789';
      $row['企業電話番号'] = '05053056840';
      
      $data[] = $row;
    }
    
    // 奇数件のとき、最後の伝票のサイズが変になるので、空情報を追加
    if(count($data) % 2 == 1)$data[] = array('伝票no' => "");
    
    // 1Pごとに分割
    $data = array_chunk($data, 8);
    
    /** @var LoggableGenerator $pdf */
    $pdf = $this->getContainer()->get('knp_snappy.pdf');
    $options = [
      'encoding' => 'utf-8'
      , 'page-size' => 'A4'
      , 'margin-top'    => '10mm'
      , 'margin-bottom' => '10mm'
      , 'margin-left'   => '10mm'
      , 'margin-right'  => '10mm'
    ];
    foreach($options as $k => $v) {
      $pdf->setOption($k, $v);
    }
    
    // 一時ファイルパスの生成
    $fs = new Filesystem();
    $tmpFilePath = $this->getContainer()->get('kernel')->getCacheDir() . '/delivery/shipping_label_pdf_' . microtime(true);
    if ($fs->exists($tmpFilePath)) {
      throw new \RuntimeException('PDFの一時ファイルが作成できませんでした。処理を終了します。' . $tmpFilePath);
    }
    
    /** @var \Twig_Environment $twig */
    $twig = $this->getContainer()->get('twig');

    $twig = $twig->load('AppBundle:Delivery:pdf/label-print-pdf-teikeigai.html.twig');
    
    // HTML出力
    $html = $twig->render([
        'data' => $data,
        'postpaidMailSymbolBase64' => $postpaidMailSymbolBase64
    ]);

    // デバッグ用 HTML出力
    if ($isDebug) {
      return new Response($html, 200, [ 'Content-Type' => 'text/html' ]);
      // end of debug HTML OUTPUT
    }

    // PDFレンダリング
    $pdf->generateFromHtml($html, $tmpFilePath);
    
    $response = new Response(
        file_get_contents($tmpFilePath)
        , 200
        , array(
          'Content-Type'          => 'application/pdf'
        )
    );
    
    // 一時ファイル削除
    $fs->remove($tmpFilePath);
    
    return $response;
  }

  /**
   * ゆうパケット用出荷ラベルをPDFで作成する。
   * @param array $voucherNumbers 伝票番号の配列
   * @param boolean $isDebug Truthyなら、PDFでなくHTML形式で表示。厳密な型判定は行わない。
   * @return Response $response
   */
  public function createPdfResponseYuuPacket($voucherNumbers, $isDebug){
    /** @var TbSalesDetailRepository $repoVoucher */
    $repoVoucher = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
    /** @var TbShipmentTrackingNumberRepository $repoTracking */
    $repoTracking = $this->getDoctrine()->getRepository('MiscBundle:TbShipmentTrackingNumber');
    $vouchers = $repoVoucher->getVoucherByVoucherNumbers($voucherNumbers, true);

    $voucherNumbers = [];
    foreach ($vouchers as $voucher) {
      $voucherNumbers[] = $voucher->getVoucherNumber();
    }

    // 配送情報を取得する。これは納品書CSVアップロード時のデータ。$vouchersのデータはamazonデータがマスクされているため依頼元・配送先データ取得にはこれを利用
    /** @var TbShippingVoucherRepository $shippingVoucherRepo */
    $shippingVoucherRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucher');
    $shippingInfos = $shippingVoucherRepo->getShippingInfoByVoucherNumbers($voucherNumbers);

    // 仕分番号を取得する。仕分番号データは日本郵便からDLした6桁の数値データ
    /** @var TbPostalZipsiwakeRepository $postalZipsiwakeRepo */
    $postalZipsiwakeRepo = $this->getDoctrine()->getRepository('MiscBundle:TbPostalZipsiwake');

    /** @var ImageUtil $imageUtil ※バーコード用 */
    $imageUtil = $this->getContainer()->get('misc.util.image');
    /** @var FileUtil $fileUtil */
    $fileUtil = $this->getFileUtil();

    // 料金後納郵便のパスを取得
    $webDir = $fileUtil->getWebDir();
    $this->getLogger()->info($webDir .  '/img/postpaid-mail-symbol-yuupacket.png');
    $postpaidMailSymbolBase64 = base64_encode(file_get_contents($webDir . '/img/postpaid-mail-symbol-yuupacket.png'));

    // ゆうパケットの画像パスを取得
    $this->getLogger()->info($webDir .  '/img/mail-symbol-yuupacket.png');
    $yuupacketSymbolBase64 = base64_encode(file_get_contents($webDir . '/img/mail-symbol-yuupacket.png'));

    // ゆうパケットの発送方法idを取得
    $yuupacketGroupCode = TbShippingdivision::SHIPPING_GROUP_CODE_YUU_PACKET;
    $yuupacketId = TbDeliveryMethod::SHIPPING_GROUP_CODE_DELIVERY_MAPPING[$yuupacketGroupCode]['id'];

    // ゆうパケット以外で、自社管理発送伝票番号使用済の伝票が有れば、ステータスをキャンセルに変更する
    $repoTracking->updateStatusToCancelled($voucherNumbers, $yuupacketId);

    // 該当伝票番号のうち、既に指定発送方法の発送先伝票番号が割り当てられている一覧を取得
    $voucherTrackingMaps = $repoTracking->findAssignedTrackingNumbers($voucherNumbers, $yuupacketId);

    // 伝票番号のうち発送伝票番号が割り当てられていない分だけ、未使用の発送先伝票番号を取得
    $count = count($voucherNumbers) - count($voucherTrackingMaps);
    $trackingNumbers = $repoTracking->findUnUsedTrackingNumbers($yuupacketId, $count);
    if (count($trackingNumbers) < $count) {
      throw new BusinessException('新しく割り当てられる未使用発送伝票番号の数が足りません');
    }

    // 発送伝票番号のない伝票番号と、未使用発送伝票番号を組み合わせる
    $newVoucherTrackingMaps = [];
    $i = 0;
    foreach($vouchers as $voucher) {
      $voucherNumber = $voucher->getVoucherNumber();
      if (!isset($voucherTrackingMaps[$voucherNumber])) {
        $newVoucherTrackingMaps[$voucherNumber] = $trackingNumbers[$i];
        $i++;
      }
    }

    // トランザクション開始
    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getDoctrine()->getConnection('main');
    $dbMain->beginTransaction();
    try {
      // 発送伝票番号テーブル一括更新
      if (!empty($newVoucherTrackingMaps)) {
        $repoTracking->updateTrackingNumbersWithVouchers($newVoucherTrackingMaps, $yuupacketId);
      }
      // トランザクションコミット
      $dbMain->commit();
    } catch (\Exception $e) {
      $this->getLogger()->error("ゆうパケット発送伝票番号割り当て時にエラー発生: $e");
      // エラーが発生した場合、トランザクションをロールバック
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    }

    // 配送先情報の詰め直し
    $voucherTrackingMaps = $voucherTrackingMaps + $newVoucherTrackingMaps; // キー維持
    $data = [];
    foreach($vouchers as $voucher) {
      $item = $voucher->toScalarArray();
      $row = $item;
      $shippingInfo = null;
      if (isset($shippingInfos[$voucher->getVoucherNumber()])) {
        $shippingInfo = $shippingInfos[$voucher->getVoucherNumber()];
      }

      $row['伝票no'] = $voucher->getVoucherNumber();
      $row['受注no'] = $voucher->getOrderNumber();

      // 発送先伝票番号の取得
      $trackingNumber = $voucherTrackingMaps[$row['伝票no']];
      $row['toiawase'] = 'A' . $trackingNumber . 'A';

      // バーコードを生成（仕分番号）
      $postalZipsiwake = $postalZipsiwakeRepo->findOneBy(['zipCode' => $shippingInfo['購入者郵便番号']])->getSiwakeCode();
      $row['siwake_code'] = $this->insertSiwakeHyphen($postalZipsiwake);
      $row['barcode_siwake'] = $imageUtil->getBarcodeSVG($postalZipsiwake, 'C128', false, 1.0, 32);
      $row['barcode_toiawase'] = $imageUtil->getBarcodeSVG($trackingNumber, 'CODABAR', false, 1.0, 34);

      // 配送先情報をセット
      if ($shippingInfo) {
        $row['受注名'] = $shippingInfo['購入者名'];
        $row['受注郵便番号'] = $shippingInfo['購入者郵便番号'];
        $row['受注者住所1'] = $shippingInfo['購入者住所1'];
        $row['受注者住所2'] = $shippingInfo['購入者住所2'];
        $row['受注者電話番号'] = $shippingInfo['購入者電話番号'];

        $row['発送先名'] = $shippingInfo['発送先名'];
        $row['発送先名length'] = mb_strwidth($row['発送先名']);
        $row['発送先郵便番号'] = $this->insertZipcodeHyphen($shippingInfo['発送先郵便番号']);
        $row['発送先住所1'] = $shippingInfo['発送先住所1'];
        $row['発送先住所2'] = $shippingInfo['発送先住所2'];
        $row['発送先住所length'] = mb_strwidth($row['発送先住所1']) + mb_strwidth($row['発送先住所2']);
        $row['発送先電話番号'] = $shippingInfo['発送先電話番号'];
      } else {
        $row['受注名'] = $voucher->getCustomerName();
        $row['受注郵便番号'] = $voucher->getCustomerZipcode();
        $row['受注者住所1'] = $voucher->getCustomerAddress1();
        $row['受注者住所2'] = $voucher->getCustomerAddress2();
        $row['受注者電話番号'] = $voucher->getCustomerTel();

        $row['発送先名'] = $voucher->getDeliveryName();
        $row['発送先名length'] = mb_strwidth($row['発送先名']);
        $row['発送先郵便番号'] = $voucher->getDeliveryZipcode();
        $row['発送先住所1'] = $voucher->getDeliveryAddress1();
        $row['発送先住所2'] = $voucher->getDeliveryAddress2();
        $row['発送先住所length'] = mb_strwidth($row['発送先住所1']) + mb_strwidth($row['発送先住所2']);
        $row['発送先電話番号'] = $voucher->getDeliveryTel();
      }

      $row['企業名'] = '株式会社フォレスト';
      $row['企業郵便番号'] = '6308424';
      $row['企業住所'] = '奈良県奈良市古市町789';
      $row['企業電話番号'] = '05053056840';

      $data[] = $row;
    }

    // 件数が10の倍数にならないとき最終ページの伝票の高さが自動調整されるため、
    // ダミーを追加し10の倍数に揃える
    if(($cnt = count($data) % 10) !== 0){
      for($i = 0; $i < 10 - $cnt; $i ++){
        $data[] = array('伝票no' => "");
      }
    }

    if(count($data) % 2 == 1)$data[] = array('伝票no' => "");

    // 1Pごとに分割
    $data = array_chunk($data, 10);

    /** @var LoggableGenerator $pdf */
    $pdf = $this->getContainer()->get('knp_snappy.pdf');
    $options = [
      'encoding' => 'utf-8'
      , 'page-size' => 'A4'
      , 'margin-top'    => '0mm'
      , 'margin-bottom' => '0mm'
      , 'margin-left'   => '0mm'
      , 'margin-right'  => '0mm'
    ];
    foreach($options as $k => $v) {
      $pdf->setOption($k, $v);
    }

    // 一時ファイルパスの生成
    $fs = new Filesystem();
    $tmpFilePath = $this->getContainer()->get('kernel')->getCacheDir() . '/delivery/shipping_label_pdf_' . microtime(true);
    if ($fs->exists($tmpFilePath)) {
      throw new \RuntimeException('PDFの一時ファイルが作成できませんでした。処理を終了します。' . $tmpFilePath);
    }

    /** @var \Twig_Environment $twig */
    $twig = $this->getContainer()->get('twig');

    $twig = $twig->load('AppBundle:Delivery:pdf/label-print-pdf-yuupacket.html.twig');

    // HTML出力
    $html = $twig->render([
        'data' => $data,
        'postpaidMailSymbolBase64' => $postpaidMailSymbolBase64,
        'yuupacketSymbolBase64' => $yuupacketSymbolBase64
    ]);

    // デバッグ用 HTML出力
    if ($isDebug) {
      return new Response($html, 200, [ 'Content-Type' => 'text/html' ]);
      // end of debug HTML OUTPUT
    }

    // PDFレンダリング
    $pdf->generateFromHtml($html, $tmpFilePath);
    
    $response = new Response(
        file_get_contents($tmpFilePath)
        , 200
        , array(
          'Content-Type'          => 'application/pdf'
        )
    );

    // 一時ファイル削除
    $fs->remove($tmpFilePath);

    return $response;
  }

  /**
   * 仕分番号にハイフンを追加する（ラベル表示用）。
   * @param string $code 仕分番号
   * @param integer $digit ハイフンで区切る桁数
   * @param string $set_char 区切り記号
   * @return string $out_code
   */
  function insertSiwakeHyphen($code, $digit = 2, $set_char = '-')
  {
    //出力コード初期化
    $out_code = '';

    // コードを指定桁ごとに分割
    $in_code = str_split($code, $digit);

    for($cnt = 0; $cnt < (sizeof($in_code) - 1); $cnt ++) //桁数分ループ
    {
      //ハイフンセット
      $out_code .= $in_code[$cnt] . $set_char;
    }

    // 最後の文字列をセット
    $out_code .= $in_code[(sizeof($in_code) - 1)];

    // 処理終了
    return $out_code;
  }

  /**
   * 郵便番号にハイフンを追加する（ラベル表示用）。
   * @param string $zipcode 郵便番号
   * @param string $set_char 区切り記号
   * @return string $out_code XXX-XXXX形式
   */
  function insertZipcodeHyphen($zipcode, $set_char = '-')
  {
    //出力コード初期化
    $out_code = '';

    // 区切り記号追加
    $out_code = substr($zipcode ,0,3) . $set_char . substr($zipcode ,3);

    // 処理終了
    return $out_code;
  }


}
