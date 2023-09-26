<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use forestlib\Doctrine\ORM\LimitableNativeQuery;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbProductchoiceitemsShippingGroupLog;
use MiscBundle\Entity\TbShippingdivision;
use MiscBundle\Util\BatchLogger;
use MiscBundle\Util\StringUtil;

/**
 * TbProductchoiceitemsShippingGroupLogRepository
 */
class TbProductchoiceitemsShippingGroupLogRepository extends BaseRepository
{

  /**
   * NE商品コードと送料グループ種別を指定し、ログを1件登録する。
   * @param String $neSyohinSyohinCode NE商品コード（not null)
   * @param int $shippingGroupCode 送料グループ種別 (not null)
   * @param int $accountId アカウントID
   * @param bool $reflectedFlg 反映済みフラグ
   * @return TbProductchoiceitemsShippingGroupLog $log 生成したエンティティ
   */
  public function insertLog(String $neSyohinSyohinCode, int $shippingGroupCode, int $accountId, bool $reflectedFlg) {
    $em = $this->getEntityManager();

    $tbProductchoiceitemsRepository = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbProductchoiceitems');
    $sku = $tbProductchoiceitemsRepository->find($neSyohinSyohinCode);
    if (is_null($sku)) {
      throw new \UnexpectedValueException('指定されたSKUコードに該当するデータが存在しません');
    }

    // 軸情報を取得
    $sizeAxis = $tbProductchoiceitemsRepository->getSizeAxisAndValue($sku);

    $log = new TbProductchoiceitemsShippingGroupLog();
    $log->setDaihyoSyohinCode($sku->getDaihyoSyohinCode());
    if ($sizeAxis['axis'] === 'col' || $sizeAxis['axis'] === 'row') {
      $log->setBundleAxis($sizeAxis['axis']);
      $log->setAxisCode($sizeAxis['code']);
    } else if ($sizeAxis['axis'] === 'both') {
      $log->setTargetNeSyohinSyohinCode($neSyohinSyohinCode);
    }
    $log->setShippingGroupCode($shippingGroupCode);
    $log->setCreateNeSyohinSyohinCode($neSyohinSyohinCode);
    $log->setCreateSymfonyUsersId($accountId);
    $log->setReflectedFlg($reflectedFlg);

    $em->persist($log);
    $em->flush();

    return $log;
  }

  /**
   * 送料設定で、指定した日付以後の手動設定ログがあるかどうかを確認し、存在すればtrue、存在しなければfalseを返却する。
   * このメソッドは、findByAxisOrNeSyohinSyohinCode 呼び出しのためのラッパー。
   * @param string $daihyoSyohinCode 代表商品コード (NotNull)
   * @param array $sizeAxis TbProductchoiceitemsRepository->getSizeAxisAndValue(SKUコード) の結果(NotNull)
   * @param string $targetNeSyohinSyohinCode 1件更新の場合のSKUコードだが、1件更新でなくても指定して良い(Nullable)
   * @param DateTime $fromDate 検索対象とする設定日。これ以降のデータを取得する（この日を含む）(Nullable)
   */
  public function existShippingdivisionManualSettingLog($daihyoSyohinCode, $sizeAxis, $targetNeSyohinSyohinCode, \DateTime $fromDate = null) {
    $log = $this->findByAxisOrNeSyohinSyohinCode(
        $daihyoSyohinCode,
        $sizeAxis['axis'] == 'both' || $sizeAxis['axis'] == 'none' ? null : $sizeAxis['axis'], // 軸がbothかnoneの場合はnullを渡す
        $sizeAxis['axis'] == 'both' || $sizeAxis['axis'] == 'none' ? null : $sizeAxis['code'], // 軸がbothかnoneの場合はnullを渡す
        $sizeAxis['axis'] == 'both' || $sizeAxis['axis'] == 'both' ? $targetNeSyohinSyohinCode : null, // 軸がbothの場合はtarget指定
        $fromDate);
    if (! empty($log)) {
      return true;
    }
    return false;
  }

  /**
   * 代表商品コードと軸コードまたはSKUコード、検索範囲の開始日を指定して、履歴の最新の1件を取得する。
   * 対象の日付以後で、手動設定がある場合のデータを取得するのに使用する。
   *
   * @param string $daihyoSyohinCode 代表商品コード
   * @param string $bundleAxis 軸コード(rowまたはcol)
   * @param string $axisCode コード値（軸コードが指定されている場合必須）
   * @param string $neSyohinSyohinCode SKUコード
   * @param \DateTime $fromDate 検索開始日（この日を含む）
   */
  public function findByAxisOrNeSyohinSyohinCode(
      string $daihyoSyohinCode, string $bundleAxis = null, string $axisCode = null, string $targetNeSyohinSyohinCode = null, \DateTime $fromDate = null) {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $qb = $this->createQueryBuilder('pci');
    $qb->andWhere('pci.daihyoSyohinCode = :daihyoSyohinCode')->setParameter(':daihyoSyohinCode', $daihyoSyohinCode);
    if ($bundleAxis) {
      $qb->andWhere('pci.bundleAxis = :bundleAxis')->setParameter(':bundleAxis', $bundleAxis);
      $qb->andWhere('pci.axisCode = :axisCode')->setParameter(':axisCode', $axisCode);
    }
    if ($targetNeSyohinSyohinCode) {
      $qb->andWhere('pci.targetNeSyohinSyohinCode = :targetNeSyohinSyohinCode')->setParameter(':targetNeSyohinSyohinCode', $targetNeSyohinSyohinCode);
    }
    if ($fromDate) {
      $qb->andWhere('pci.created >= :created')->setParameter(':created', $fromDate->format('Y-m-d'));
    }
    $qb->addOrderBy('pci.created', 'DESC');
    $qb->setMaxResults(1);

    return $qb->getQuery()->getResult();
  }
}
