<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\TbCompany;
use MiscBundle\Entity\TbShippingVoucherAutoGenerate;

/**
 * TbShippingVoucherAutoGenerateRepository
 */
class TbShippingVoucherAutoGenerateRepository extends BaseRepository
{
  /**
   * ステータスの更新。
   * @param int $id 出荷リスト自動生成ID
   * @param int $status ステータス
   */
  public function updateStatus($id, $status)
  {
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_auto_generate
      SET
        status = :status
      WHERE
        id = :id;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * 登録伝票数の更新。
   * @param int $id 出荷リスト自動生成ID
   * @param int $registNum 登録伝票数
   */
  public function updateRegistNum($id, $registNum)
  {
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_auto_generate
      SET
        regist_num = :registNum
      WHERE
        id = :id;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':registNum', $registNum, \PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * ファイルパスの更新。
   * @param int $id 出荷リスト自動生成ID
   * @param string $fileName ファイル名
   */
  public function updateFileName($id, $fileName)
  {
    $sql = <<<EOD
      UPDATE tb_shipping_voucher_auto_generate
      SET
        file_name = :fileName
      WHERE
        id = :id;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':fileName', $fileName, \PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
  }

  /**
   * バルクインサート(一括登録)
   * @param array $entityList 出荷リスト自動生成Entityのリスト
   */
  public function bulkInsert($entityList)
  {
    $values = array_map(function($entity) {
      return sprintf(
        '(%s, %s, %s, "%s", %s, %s, %s, %s)'
        , $entity->getPackingGroupId()
        , $entity->getCompanyId()
        , $entity->getWarehouseId()
        , $entity->getDeliveryMethod()
        , $entity->getPage()
        , $entity->getStatus()
        , $entity->getTargetNum()
        , $entity->getAccountId()
        );
    }, $entityList);
    $valueStr = implode(', ', $values);

    $sql = <<<EOD
      INSERT
      INTO tb_shipping_voucher_auto_generate(
        packing_group_id
        , company_id
        , warehouse_id
        , delivery_method
        , page
        , status
        , target_num
        , account_id
      )
      VALUES {$valueStr};
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    return $stmt->execute();
  }

  /**
   * 全データ削除
   */
  public function truncate()
  {
    $dbMain = $this->getConnection('main');
    $dbMain->query('TRUNCATE tb_shipping_voucher_auto_generate');
    return;
  }

  /**
   * 出荷リスト自動生成一覧取得。
   *
   * @param int $warehouseId 倉庫ID || null
   * @param int $status ステータス || null
   * @return array 出荷リスト自動生成の連想配列
   */
  public function findShippingVoucherAutoGenerateList($warehouseId, $status)
  {
    $addWheres = [];
    $addParams = [];
    $addWhereSql = "";
    if ($warehouseId) {
      $addWheres[] = "a.warehouse_id = :warehouseId";
      $addParams[':warehouseId'] = $warehouseId;
    }
    if ($status) {
      $addWheres[] = "a.status = :status";
      $addParams[':status'] = $status;
    }
    if ($addWheres) {
      $addWhereSql = sprintf(" AND ( %s ) ", implode(" AND ", $addWheres));
    }
    $sql = <<<EOD
      SELECT
        a.id id,
        a.company_id companyId,
        c.name company,
        a.warehouse_id warehouseId,
        w.name warehouse,
        a.delivery_method deliveryMethod,
        a.page,
        a.packing_group_id packingGroupId,
        a.status,
        a.target_num targetNum,
        a.regist_num registNum,
        u.username account,
        a.created,
        a.updated
      FROM
        tb_shipping_voucher_auto_generate a
      INNER JOIN tb_company c ON a.company_id = c.id
      INNER JOIN tb_warehouse w ON a.warehouse_id = w.id
      INNER JOIN symfony_users u ON a.account_id = u.id
      WHERE
        1
        {$addWhereSql}
      ORDER BY
        a.id DESC;
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    foreach($addParams as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
}
