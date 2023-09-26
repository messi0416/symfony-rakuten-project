<?php

namespace MiscBundle\Entity\Repository;
use Doctrine\Common\Collections\Collection;
use MiscBundle\Entity\TbWarehouse;

/**
 * TbWarehouseRepository
 */
class TbWarehouseRepository extends BaseRepository
{
  const DEFAULT_WAREHOUSE_ID = 12; // 削除不可 初期倉庫ID
  const TUMEKAE_MUGEN_WAREHOUSE_ID = 5; // 削除不可 詰替MUGEN倉庫ID
  const FBA_MULTI_WAREHOUSE_ID = 6; // 削除不可 FBA仮想倉庫
  const YABUYOSHI_WAREHOUSE_ID = 7; // 削除不可 藪吉倉庫ID
  const RSL_WAREHOUSE_ID = 10; // 削除不可 RSL
  const SHOPLIST_WAREHOUSE_ID = 11; // 削除不可 SHOPLISTロジ
  const FURUICHI_WAREHOUSE_ID = 12; // 削除不可 古市倉庫ID
  const TUMEKAE_FURUICHI_WAREHOUSE_ID = 13; // 削除不可 詰替古市倉庫ID
  const MINAMI_KYOBATE_WAREHOUSE_ID = 14; // 削除不可 南京終倉庫ID
  const BYAKUGOZI_WAREHOUSE_ID = 15; // 削除不可 白毫寺倉庫ID
  const YAMADAGAWA_WAREHOUSE_ID = 17; // 削除不可 山田川倉庫ID
  const KYUUMUKAI_WAREHOUSE_ID = 18; // 削除不可 旧ムカイ倉庫ID

  /**
   * 略号で取得 バリデーション 略号ユニークチェック
   * @param string $symbol
   * @param int $exceptId
   * @return mixed
   * @throws \Doctrine\ORM\NoResultException
   * @throws \Doctrine\ORM\NonUniqueResultException
   */
  public function findBySymbol($symbol, $exceptId = null)
  {
    $qb = $this->createQueryBuilder('w');
    $qb->where('w.symbol = :symbol')->setParameter(':symbol', $symbol);
    if ($exceptId) {
      $qb->andWhere('w.id != :exceptId')->setParameter(':exceptId', $exceptId);
    }

    $result = $qb->getQuery()->getResult();
    return $result ? array_shift($result) : $result;
  }

  /**
   * プルダウン用配列 取得 (JavaScript用に、0からの添字配列)
   * @param boolean $onlyShipmentEnabled 出荷可能倉庫のみ取得するか
   *  true - 出荷可能のみ / false - 全て
   */
  public function getPullDown($onlyShipmentEnabled = false)
  {
    $list = [];

    $qb = $this->createQueryBuilder('w');
    $qb->andWhere('w.terminate_flg = 0');
    if ($onlyShipmentEnabled === true) {
      $qb->andWhere('w.shipment_enabled <> 0');
    }
    $qb->orderBy('w.display_order', 'ASC');
    /** @var TbWarehouse[] $result */
    $result = $qb->getQuery()->getResult();

    foreach($result as $warehouse) {
      $list[] = [
          'id' => $warehouse->getId()
        , 'name' => $warehouse->getName()
        , 'symbol' => $warehouse->getSymbol()
      ];
    }

    return $list;
  }


  /**
   * プルダウン用配列 取得 (全フィールド)
   */
  public function getPullDownAll()
  {
    $list = [];

    $qb = $this->createQueryBuilder('w');
    $qb->orderBy('w.display_order', 'ASC');
    /** @var TbWarehouse[] $result */
    $result = $qb->getQuery()->getResult();

    foreach($result as $warehouse) {
      $list[] = $warehouse->toScalarArray();
    }

    return $list;
  }

  /**
   * プルダウン用配列 取得 (通常用。ID連想配列)
   */
  public function getPullDownObjects()
  {
    $list = [];

    $qb = $this->createQueryBuilder('w');
    $qb->orderBy('w.display_order', 'ASC');
    /** @var TbWarehouse[] $result */
    $result = $qb->getQuery()->getResult();

    foreach($result as $warehouse) {
      $list[$warehouse->getId()] = $warehouse;
    }

    return $list;
  }

    public function getFuruichiWarehouse()
    {
        $list = [];

        $qb = $this->createQueryBuilder('w');
        $qb->where("w.name = '古市'");
        /** @var TbWarehouse[] $result */
        $result = $qb->getQuery()->getResult();

        foreach($result as $warehouse) {
            $list[$warehouse->getId()] = $warehouse;
        }

        return $list;
    }

  /**
   * 出荷可能倉庫 取得
   * @return TbWarehouse[]|Collection
   */
  public function getShipmentEnabledWarehouses()
  {
    $qb = $this->createQueryBuilder('w');
    $qb->andWhere('w.shipment_enabled <> 0');
    $qb->addOrderBy('w.shipment_priority', 'DESC');
    $qb->addOrderBy('w.display_order', 'ASC');
    return $qb->getQuery()->getResult();
  }


  /**
   * 在庫移動倉庫一覧 取得
   * メイン出荷倉庫を除く倉庫を出荷優先順位の高い順で取得
   * @param null $mainWarehouseId
   * @return Collection|\MiscBundle\Entity\TbWarehouse[]
   */
  public function getStockMoveWarehouses($mainWarehouseId = null)
  {
    $qb = $this->createQueryBuilder('w');
    if ($mainWarehouseId) {
      $qb->andWhere('w.id <> :mainWarehouseId')->setParameter(':mainWarehouseId', $mainWarehouseId, \PDO::PARAM_INT);
    }

    $qb->andWhere(':limit < w.transport_priority')->setParameter(':limit', 0, \PDO::PARAM_INT); // 倉庫移動優先重みづけが0未満の場合は対象外にする
    $qb->addOrderBy('w.transport_priority', 'DESC'); // 移動優先重みづけの大きい順（優先重みづけの高い順。この機能は数字が大きいほうが優先順位が高い）
    $qb->addOrderBy('w.display_order', 'ASC');
    return $qb->getQuery()->getResult();
  }

  /**
   * 在庫移動ピッキング対象 一覧取得
   */
  public function getWarehouseStockMoveProducts()
  {
    $dbMain = $this->getConnection('main');
    $sql = <<<EOD
      SELECT
         *
      FROM tb_shipping_product_move_list l
      ORDER BY l.from_warehouse_id
             , l.num DESC
             , l.ne_syohin_syohin_code
EOD;
    return $dbMain->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
  }


  /**
   * 南京終用移動伝票作成用 移動対象倉庫一覧取得
   * @param int $mainWarehouseId
   * @return TbWarehouse[]
   */
  public function getTransportFromWarehouses($mainWarehouseId = null)
  {
    $qb = $this->createQueryBuilder('w');
    if ($mainWarehouseId) {
      $qb->andWhere('w.id <> :mainWarehouseId')->setParameter(':mainWarehouseId', $mainWarehouseId, \PDO::PARAM_INT);
    }
    $qb->andWhere('w.transport_priority > 0');

    $qb->addOrderBy('w.transport_priority', 'DESC'); // 移動優先順位の高い順
    $qb->addOrderBy('w.display_order', 'ASC');
    return $qb->getQuery()->getResult();
  }

  /**
   * FBA移動伝票作成用 移動対象倉庫一覧取得
   * @param int $fbaWarehouseId
   * @return TbWarehouse[]
   */
  public function getTransportFbaFromWarehouses($fbaWarehouseId = null)
  {
    $qb = $this->createQueryBuilder('w');
    if ($fbaWarehouseId) {
      $qb->andWhere('w.id <> :fbaWarehouseId')->setParameter(':fbaWarehouseId', $fbaWarehouseId, \PDO::PARAM_INT);
    }
    $qb->andWhere('w.fba_transport_priority > 0');

    $qb->addOrderBy('w.fba_transport_priority', 'DESC'); // 移動優先順位の高い順
    $qb->addOrderBy('w.display_order', 'ASC');
    return $qb->getQuery()->getResult();
  }
  /**
   * 倉庫ID取得
   */
  public function getWarehouse_Id()
  {
    $WarehouseId = $this->createQueryBuilder('w')
      ->select('w.id')
      ->orderBy('w.id');

    return $WarehouseId->getQuery()->getResult();

  }

  /**
   * 出荷実績レビュー表示 倉庫情報取得
   * @return TbWarehouse[]
   */
  public function getResultHistoryDisplayWarehouses()
  {
    $qb = $this->createQueryBuilder('w');
    $qb->andWhere('w.result_history_display_flg <> 0');
    $qb->addOrderBy('w.id', 'ASC');
    return $qb->getQuery()->getResult();
  }
}
