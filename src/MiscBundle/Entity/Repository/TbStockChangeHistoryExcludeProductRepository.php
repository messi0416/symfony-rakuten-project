<?php

namespace MiscBundle\Entity\Repository;

/**
 * TbStockChangeHistoryExcludeProductRepository
 */
class TbStockChangeHistoryExcludeProductRepository extends BaseRepository
{
  /**
   * @param array $excludeProducts 除外商品リスト
   */
  public function saveExcludeProducts($excludeProducts)
  {
    $db = $this->getConnection('main');

    /** @var EntityManager $em */
    $em = $this->getEntityManager();

    try {
      $em->beginTransaction();

      $sql = 'DELETE FROM tb_stock_change_history_exclude_product';
      $stmt = $db->prepare($sql);
      $stmt->execute();

      if (count($excludeProducts) > 0) {
        $values = array_map(function ($product) {
          return "('${product}')";
        }, $excludeProducts);
        $valuesStr = implode(", ", $values);

        $sql = <<<EOD
          INSERT IGNORE INTO 
            tb_stock_change_history_exclude_product (daihyo_syohin_code)
          VALUES
            {$valuesStr}
EOD;
        $stmt = $db->prepare($sql);
        $stmt->execute();
      }

      $em->commit();
    } catch (Exception $e) {
      if ($em->getConnection()->isTransactionActive()) {
        $em->rollback();
      }
      throw $e;
    }
  }
}
