<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use MiscBundle\Entity\TbShoplistSpeedbinShipping;

class TbShoplistSpeedbinShippingRepository extends BaseRepository
{
  /**
   * SHOPLISTスピード便出荷情報を、連想配列の配列で返却する。
   * @param integer $limit 取得件数（最新から指定件数） 
   * @return array SHOPLISTスピード便出荷情報
   */
  public function findShippingArray($limit) {
    $sql = <<<EOD
      SELECT 
        s.id
        , s.status
        , u.username
        , s.created
      FROM tb_shoplist_speedbin_shipping s
      LEFT JOIN symfony_users u ON s.create_user_id = u.id
      ORDER BY id DESC
      LIMIT :limit
EOD;
    $stmt = $this->getConnection('main')->prepare($sql);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    $resultList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    // ステータス名を追加
    foreach ($resultList as &$data) {
      $data['status_name'] = TbShoplistSpeedbinShipping::$STATUS_DISPLAYS[$data['status']];
    }
    return $resultList;
  }
}