<?php

namespace MiscBundle\Service;

use BatchBundle\MallProcess\NextEngineMallProcess;
use MiscBundle\Entity\TbShippingReissueLabel;
use MiscBundle\Entity\Repository\TbDeliveryMethodRepository;
use MiscBundle\Entity\Repository\TbSalesDetailRepository;
use MiscBundle\Entity\Repository\TbShippingReissueLabelRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherNoneedInquiryNumberRepository;
use MiscBundle\Entity\Repository\TbShippingVoucherPackingRepository;
use MiscBundle\Exception\BusinessException;
use MiscBundle\Util\StringUtil;

use MiscBundle\Service\ServiceBaseTrait;

/**
 * 配送ラベル再発行伝票・不使用問い合わせ番号関連Service。
 *
 * loggerやdoctrineなどはコンストラクタで定義しているため各メソッドでそのまま使用可能。
 */
class ShippingInquiryNumberService
{
  use ServiceBaseTrait;

  /**
   * 配送ラベル再発行リストを取得する。
   *
   * 倉庫ID・梱包グループ名・梱包グループコメント・伝票番号・CSV未ダウンロード限定の有無、
   * を格納した連想配列をもとに、配送ラベル再発行伝票のリストを連想配列で返却。
   * （当日分且つ、出荷伝票梱包ステータスがOKのものに限る。更新日時順。）
   * @param array $conditions 検索条件の連想配列
   * @return array 以下のキーを持つ連想配列の配列。
   *    'id' => int 発送ラベル再発行伝票ID
   *    'shippingVoucherPackingId' => int 出荷伝票梱包ID
   *    'deliveryMethodId' => int 発送方法
   *    'status' => int 発送ラベル再発行伝票ステータス
   *    'voucherNumber' => int 伝票番号
   *    'shippingVoucherName' => string 出荷伝票グループ名（ex. 古-4）
   *    'warehouseId' => int 倉庫ID
   *    'pickingListDate' => date ピッキングリスト日付
   *    'pickingListNumber' => int ピッキングリストNo.
   *    'productQuantity' => int 受注数合計
   */
  public function findReissueList($conditions)
  {
    /** @var TbShippingReissueLabelRepository $reissueRepo */
    $reissueRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingReissueLabel');

    $list = $reissueRepo->findReissueListByConditions($conditions);

    if (empty($list)) {
      return [];
    }

    // 出荷伝票グループ名を作成
    $list = array_map(function($row) {
      $row['shippingVoucherName'] = $row['symbol'] . '-' . $row['warehouseDailyNumber'];
      unset($row['symbol'], $row['warehouseDailyNumber']);
      return $row;
    }, $list);

    return $list;
  }

  /**
   * 配送ラベル発行用のCSVをダウンロードする。
   *
   * 引数をもとに、発送方法に応じた配送ラベル発行用のCSVをダウンロードし、
   * 配送ラベル発行用のステータスを、未発行から発行済みに更新する。
   * また、出荷伝票梱包の有効なお問い合わせ番号ステータスについて、
   * 「ラベル再発行待ち」の場合、お問い合わせ番号が不要な配送方法でなければ、
   * 「有効なお問い合わせ番号がある」に更新する。
   * 対象が割り込みで更新されていた場合は、BusinessExceptionをthrowする。
   * ※このメソッドは完了時にDBコミットを行う
   * @param array $unissuedIds 未発行の発送ラベル再発行伝票IDリスト
   * @param array $unissuedPackingIds 未発行の出荷伝票梱包IDリスト
   * @param array $voucherNumbers 出荷伝票梱包の伝票番号リスト
   * @param int $deliveryMethodId 発送方法
   * @param int $accountId ログインユーザID
   * @throws BusinessException 対象データが既に更新されていた
   * @return StreamedResponse
   */
  public function csvDownload($unissuedIds, $unissuedPackingIds, $voucherNumbers, $deliveryMethodId, $accountId)
  {
    /** @var TbShippingVoucherPackingRepository $packingRepo */
    $packingRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherPacking');
    /** @var TbShippingReissueLabelRepository $reissueRepo */
    $reissueRepo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingReissueLabel');

    $dbMain = $this->getDoctrine()->getConnection('main');

    try {
      // トランザクション開始
      $dbMain->beginTransaction();

      // 未発行のデータが有れば、ロックして更新処理を行う。
      if (!empty($unissuedIds)) {
        $packingRepo->lockForUpdate($unissuedPackingIds);
        $reissueRepo->lockForUpdate($unissuedIds);

        // ステータスを発行済みに更新し、更新件数を取得する。
        $rowCount = $reissueRepo->updateStatusOnlyUnissued($unissuedIds, TbShippingReissueLabel::STATUS_ISSUED, $accountId);

        /* 更新した件数と更新予定だった件数が一致しない場合、
        エラーを投げて処理を取り消す。 */
        if ($rowCount !== count($unissuedIds)) {
          throw new BusinessException('対象データが既に更新されています。');
        }

        // 紐づく出荷伝票梱包の有効なお問い合わせ番号ステータスを更新
        $packingRepo->updateValidInquiryNumberStatusForReissueLabel($unissuedPackingIds, $accountId);
      }

      // 対象の伝票番号からCSVダウンロード
      /** @var TbSalesDetailRepository $repoVoucher */
      $repoVoucher = $this->getDoctrine()->getRepository('MiscBundle:TbSalesDetail');
      $vouchers = $repoVoucher->getVoucherByVoucherNumbers($voucherNumbers, true);

      /** @var NextEngineMallProcess $neMallProcess */
      $neMallProcess = $this->getContainer()->get('batch.mall_process.next_engine');
      $response = $neMallProcess->generateShippingLabelCsv($deliveryMethodId, $vouchers);

      // コミット
      $dbMain->commit();
      return $response;

    } catch (\Exception $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    }
  }

  /**
   * 不要お問い合わせ番号リストを取得する。
   *
   * 倉庫ID・梱包グループ名・梱包グループコメント・伝票番号・入力未完了限定の有無、
   * を格納した連想配列をもとに、不要お問い合わせ番号のリストを連想配列で返却。
   * （当日分且つ、出荷伝票梱包ステータスがOK・商品不足・出荷STOPのいずれかのものに限る。更新日時順。）
   * @param array $conditions 検索条件の連想配列
   * @return array 以下のキーを持つ連想配列の配列。
   *    'id' => int 不要お問い合わせ番号ID
   *    'deliveryMethodId' => int 発送方法
   *    'status' => int 不要お問い合わせ番号ステータス
   *    'inquiryNumber' => int お問い合わせ番号
   *    'voucherNumber' => int 伝票番号
   *    'shippingVoucherName' => string 出荷伝票グループ名（ex. 古-4）
   *    'warehouseId' => int 倉庫ID
   *    'pickingListDate' => date ピッキングリスト日付
   *    'pickingListNumber' => int ピッキングリストNo.
   *    'productQuantity' => int 受注数合計
   */
  public function findNoneedList($conditions)
  {
    /** @var TbShippingVoucherNoneedInquiryNumberRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherNoneedInquiryNumber');

    $list = $repo->findNoneedListByConditions($conditions);

    if (empty($list)) {
      return [];
    }

    // 出荷伝票グループ名を作成
    $list = array_map(function($row) {
      $row['shippingVoucherName'] = $row['symbol'] . '-' . $row['warehouseDailyNumber'];
      unset($row['symbol'], $row['warehouseDailyNumber']);
      return $row;
    }, $list);

    return $list;
  }

  /**
   * 不要お問い合わせ番号を登録済みにする。
   *
   * 引数をもとに、不要お問い合わせ番号のステータスを登録済みに更新する。
   * 対象が割り込みで更新されていた場合は、BusinessExceptionをthrowする。
   * ※このメソッドは完了時にDBコミットを行う
   * @param array $unregisteredIds 未登録の不要お問い合わせ番号IDのリスト
   * @param int $accountId ログインユーザID
   * @throws BusinessException 対象データが既に更新されていた
   */
  public function noneedComplete($unregisteredIds, $accountId)
  {
    /** @var TbShippingVoucherNoneedInquiryNumberRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbShippingVoucherNoneedInquiryNumber');

    $dbMain = $this->getDoctrine()->getConnection('main');

    try {
      // トランザクション開始
      $dbMain->beginTransaction();

      // ロック
      $repo->lockForUpdate($unregisteredIds);

      // ステータスを発行済みに更新し、更新件数を取得する。
      $rowCount = $repo->updateStatusToComplete($unregisteredIds, $accountId);

      // 更新した件数と更新予定だった件数が一致しない場合、エラーを投げて処理を取り消す。
      if ($rowCount !== count($unregisteredIds)) {
        throw new BusinessException('対象データが既に更新されています。');
      }

      // コミット
      $dbMain->commit();

    } catch (BusinessException $e) {
      if (isset($dbMain) && $dbMain->isTransactionActive()) {
        $dbMain->rollback();
      }
      throw $e;
    } catch (\Exception $e) {
      if (isset($dbMain)) {
        $dbMain->rollback();
      }
      throw $e;
    }
  }

  /**
   * 配送方法情報取得を取得する。
   *
   * @param array $ids 発送方法IDのリスト
   * @return array 以下のキーを持つ連想配列。
   *    'id' => int 発送方法ID
   *    'name' => string 発送方法名
   */
  public function findDeliveryMethodListByIds($ids)
  {
    /** @var TbDeliveryMethodRepository $repo */
    $repo = $this->getDoctrine()->getRepository('MiscBundle:TbDeliveryMethod');

    $deliveryMethods = $repo->findBy(['deliveryId' => $ids]);
    $list = array_map(function($row) {
      $item['id'] = $row->getDeliveryId();
      $item['name'] = $row->getDeliveryName();
      return $item;
    }, $deliveryMethods);

    return $list;
  }

  /**
   * 引数の情報を使って、WEB-EDI用報告CSVデータを作成し、返却する。
   *
   * @param array $voucherAndTrackingNumberPairs 伝票番号と発送伝票番号の組み合わせの配列
   * @return string|false
   */
  public function downloadEdiUsedReportCsv($voucherAndTrackingNumberPairs)
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // CSVデータをメモリ上に保持する
    $fp = fopen('php://temp', 'r+');

    foreach ($voucherAndTrackingNumberPairs as $pair) {
      $row = [
        'DENFD',
        '',
        '',
        '',
        '',
        $pair['trackingNumber'],
        '',
        '1001365444-000001',
        '',
        '',
        '',
        '3-1000',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '450010',
        '0',
        '',
        '',
        '',
        '',
        '',
        '1',
      ];
      $line = $stringUtil->convertArrayToCsvLine($row);
      $line = mb_convert_encoding($line, 'SJIS-win', 'UTF-8') . "\r\n";
      fputs($fp, $line);
    }

    rewind($fp);
    $csvContent = stream_get_contents($fp);
    fclose($fp);

    return $csvContent;
  }

  /**
   * 引数の情報を使って、NextEngine用報告CSVデータを作成し、返却する。
   *
   * @param array $voucherAndTrackingNumberPairs 伝票番号と発送伝票番号の組み合わせの配列
   * @return string|false
   */
  public function downloadNeUsedReportCsv($voucherAndTrackingNumberPairs)
  {
    /** @var StringUtil $stringUtil */
    $stringUtil = $this->getContainer()->get('misc.util.string');

    // CSVデータをメモリ上に保持する
    $fp = fopen('php://temp', 'r+');
      $headers = ['伝票番号', '配送番号'];
      $headerLine = $stringUtil->convertArrayToCsvLine($headers);
      $headerLine = mb_convert_encoding($headerLine, 'SJIS-win', 'UTF-8') . "\r\n";
      fputs($fp, $headerLine);

    foreach ($voucherAndTrackingNumberPairs as $pair) {
      $row = [
        $pair['voucherNumber'],
        $pair['trackingNumber'],
      ];
      $line = $stringUtil->convertArrayToCsvLine($row) . "\r\n";
      fputs($fp, $line);
    }

    rewind($fp);
    $csvContent = stream_get_contents($fp);
    fclose($fp);

    return $csvContent;
  }
}
