<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\TbProductchoiceitems;
use MiscBundle\Entity\TbProductchoiceitemsShippingdivisionPending;
use MiscBundle\Entity\TbMainproducts;
use MiscBundle\Entity\TbShippingdivision;

/**
 * TbProductchoiceitemsShippingdivisionPendingRepository
 */
class TbProductchoiceitemsShippingdivisionPendingRepository extends BaseRepository {

  /**
   * SKU別送料設定保留中データとそれに関連する情報を、1データ1行の配列形式で返却します。
   * @return array SKU別送料設定保留中データの配列
   */
  public function findPendingArray() {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    $paramArray = array();
    $dql = 'SELECT'
           . ' psp.id, psp.daihyoSyohinCode, mp.daihyo_syohin_name as daihyoSyohinName, psp.bundleAxis, psp.axisCode,'
           . ' psp.targetNeSyohinSyohinCode, prevSd.name as prevSdName, prevSd.price as prevSdPrice, pendingSd.name as pendingSdName,'
           . ' pendingSd.price as pendingSdPrice, mpSd.name as mpSdName, mpSd.price as mpSdPrice, psp.reflectStatus'
           . ' FROM MiscBundle:TbProductchoiceitemsShippingdivisionPending psp'
           . ' INNER JOIN MiscBundle:TbMainproducts mp WITH psp.daihyoSyohinCode = mp.daihyoSyohinCode'
           . ' INNER JOIN MiscBundle:TbShippingdivision prevSd WITH psp.prevShippingdivisionId = prevSd.id'
           . ' INNER JOIN MiscBundle:TbShippingdivision pendingSd WITH psp.pendingShippingdivisionId = pendingSd.id'
           . ' INNER JOIN MiscBundle:TbShippingdivision mpSd WITH mp.shippingdivision = mpSd.id'
           . ' WHERE psp.reflectStatus = :reflectStatus'
           . ' ORDER BY psp.daihyoSyohinCode asc, psp.axisCode asc';
    $paramArray['reflectStatus'] = TbProductchoiceitemsShippingdivisionPending::REFLECT_STATUS_PENDING;
    $query = $em->createQuery($dql);
    foreach ($paramArray as $key => $value) {
      $query->setParameter($key, $value);
    }
    $list = $query->getResult();

    // SKUの情報を追加する
    $pciRepos = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbProductchoiceitems');
    foreach ($list as &$data) {
      $skus = array();
      if ($data['targetNeSyohinSyohinCode']) { // 1件のみの場合（2軸ともサイズ）
        $skus[] = $pciRepos->find($data['targetNeSyohinSyohinCode']);
      } else if ($data['bundleAxis']) { // 軸の指定がある場合
        $skus = $pciRepos->findByDaihyoSyohinCodeAndAxis($data['daihyoSyohinCode'], $data['bundleAxis'], $data['axisCode']);
      } else { // サイズ軸なし
        $skus = $pciRepos->findByDaihyoSyohinCode($data['daihyoSyohinCode']);
      }

      // 1件目のみ使用
      $data['height'] = $skus[0]->getHeight();
      $data['width'] = $skus[0]->getWidth();
      $data['depth'] = $skus[0]->getDepth();
      $data['weight'] = $skus[0]->getWeight();
    }
    return $list;
  }



  /**
   * 代表商品コードと軸コードまたはSKUコードを指定して、該当する保留中データがあれば1件取得する。
   * なければnullを返却する。
   *
   * @param string $daihyoSyohinCode 代表商品コード
   * @param string $bundleAxis 軸コード(rowまたはcol)
   * @param string $axisCode コード値（軸コードが指定されている場合必須）
   * @param string $targetNeSyohinSyohinCode SKUコード
   */
  public function findPendingByAxisOrNeSyohinSyohinCode(string $daihyoSyohinCode, string $bundleAxis = null, string $axisCode = null, string $targetNeSyohinSyohinCode = null) {
    /** @var BatchLogger $logger */
    $logger = $this->getContainer()->get('misc.util.batch_logger');

    $qb = $this->createQueryBuilder('psp');
    $qb->andWhere('psp.reflectStatus = :reflectStatus')->setParameter(':reflectStatus', TbProductchoiceitemsShippingdivisionPending::REFLECT_STATUS_PENDING);
    $qb->andWhere('psp.daihyoSyohinCode = :daihyoSyohinCode')->setParameter(':daihyoSyohinCode', $daihyoSyohinCode);
    if ($bundleAxis) {
      $qb->andWhere('psp.bundleAxis = :bundleAxis')->setParameter(':bundleAxis', $bundleAxis);
      $qb->andWhere('psp.axisCode = :axisCode')->setParameter(':axisCode', $axisCode);
    }
    if ($targetNeSyohinSyohinCode) {
      $qb->andWhere('psp.targetNeSyohinSyohinCode = :targetNeSyohinSyohinCode')->setParameter(':targetNeSyohinSyohinCode', $targetNeSyohinSyohinCode);
    }
    $qb->setMaxResults(1);

    $query = $qb->getQuery();
    $result = $query->getResult();
    if (count($result) == 0) {
      return null;
    } else if (count($result) >= 2) {
      // エラーログは出すが1件目のみ処理して継続　うまくいけば次の更新でもう1件も消える
      $logger->error("SKU別送料設定保留データが重複しています。[代表商品コード=$daihyoSyohinCode, サイズ=$bundleAxis=$axisCode, 対象SKU=$targetNeSyohinSyohinCode");
    }
    return $result[0];
  }
}