<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbVendoraddress;
use MiscBundle\Util\DbCommonUtil;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\Form;

/**
 * TbVendoraddressRepository
 */
class TbVendoraddressRepository extends BaseRepository
{

  /**
   * 引数のURL配列から、登録されていて有効なURLのみを抽出する
   * @return array
   */
  public function filterActiveUrls($urls)
  {
    $result = array();

    if (!$urls) {
      return $result;
    }

    $qb = $this->createQueryBuilder('va');
    $qb->select('va.sireAdress');
    $qb->distinct(true);
    $qb->andWhere($qb->expr()->in('va.sireAdress', $urls));
    $qb->andWhere($qb->expr()->eq('va.soldout', 0));

    $registeredUrls = $qb->getQuery()->getArrayResult();
    foreach($registeredUrls as $row) {
      $result[] = $row['sireAdress'];
    }

    return $result;
  }

  /**
   * AKF 登録商品の最大商品番号取得(URLから)
   */
  public function getAkfMaxProductNumber()
  {
    $dbMain = $this->getConnection('main');

    // URL書式： http://www.akf-japan.jp/product/10000
    $sql = <<<EOD
      SELECT
        MAX(CAST(REPLACE(va.sire_adress, 'http://www.akf-japan.jp/product/', '') AS UNSIGNED)) AS max_number
      FROM tb_vendoraddress va
      WHERE va.sire_code = '0290' /* AKF */
EOD;
    $result = $dbMain->query($sql)->fetchColumn(0);

    return intval($result);
  }



  /**
   * 仕入先別の 仕入先アドレス取得処理
   * ※該当の仕入先のアドレスのみ。
   * @param $productCodes
   * @return array
   */
  public function getVendorAddressListBySireCode($productCodes)
  {
    $result = [];

    $dbMain = $this->getConnection('main');

    foreach($productCodes as $sireCode => $codes) {
      $result[$sireCode] = [];

      $tmp = [];
      foreach($codes as $code) {
        $tmp[] = $dbMain->quote($code);
      }
      if (count($tmp)) {

        $codeListStr = implode(',', $tmp);
        $sql = <<<EOD
          SELECT
              va.sire_code
            , va.daihyo_syohin_code
            , va.sire_adress
          FROM tb_vendoraddress va
          INNER JOIN tb_mainproducts m ON va.daihyo_syohin_code = m.daihyo_syohin_code
          WHERE va.stop = 0
            AND va.sire_code = :sireCode
            AND va.daihyo_syohin_code IN ( {$codeListStr} )
          ORDER BY va.daihyo_syohin_code
EOD;
        $stmt = $dbMain->prepare($sql);
        $stmt->bindValue(':sireCode', $sireCode);
        $stmt->execute();
        $addresses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach($addresses as $address) {
          $result[$sireCode][$address['daihyo_syohin_code']][] = $address['sire_adress'];
        }
      }
    }

    return $result;
  }

  /**
   * 1件 売り切れに更新 ※ EntityManager使えとか言わない
   *   soldout:0 ... 通常巡回の対象とはしない（Alibaba巡回のルール（予定））
   *   setafter:0 ... WEBチェッカー反映確認対象
   * @param int $id
   * @throws \Doctrine\DBAL\DBALException
   */
  public function setSoldOutOn($id)
  {
    $this->updateSetAfter($id, 0);
  }

  /**
   * 1件 購入可能SKU数更新
   * @param $id
   * @param $after
   * @throws \Doctrine\DBAL\DBALException
   */
  public function updateSetAfter($id, $after)
  {
    $dbMain = $this->getConnection('main');

    $sql = <<<EOD
      UPDATE tb_vendoraddress va
      SET va.soldout   = CASE WHEN :after = 0 THEN 1 ELSE 0 END
        , va.setbefore = CASE
                           WHEN checkdate IS NULL THEN -1
                           ELSE va.setafter
                         END
        , va.setafter  = :after
        , va.checkdate = NOW()
        , va.soldout_checkdate = CASE WHEN :after = 0 THEN NOW() ELSE NULL END
      WHERE vendoraddress_CD = :id
EOD;
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':after', intval($after), \PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
  }

}
