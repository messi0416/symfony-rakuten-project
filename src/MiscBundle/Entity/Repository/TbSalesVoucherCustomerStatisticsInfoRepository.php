<?php

namespace MiscBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use MiscBundle\Util\BatchLogger;

/**
 */
class TbSalesVoucherCustomerStatisticsInfoRepository extends BaseRepository
{
  /** 電話番号の変換に使用するソルト */
  private $salt = null;

  /**
   * データを登録する。既に存在すれば置換する。
   *
   * モールからデータが送信されない、マスク済み、等、有効な登録データが取得できない場合は何もせず終了する。
   *
   * @param array $data tb_sales_detail のレコードの配列。中には少なくとも、伝票番号、電話番号、住所の3カラムが必要
   */
  public function replaceData($data, $prefectureMap) {
    // 都道府県コードを取得
    $prefecrureCd = '';
    if ($data['address']) {
      preg_match("/(.{1,3}[都道府県])/u", $data['address'], $matches);
      if ($matches) {
        if (array_key_exists($matches[1], $prefectureMap)) {
          $prefecrureCd = $prefectureMap[$matches[1]];
        // 市区町村名が　都道府県　のいずれかで始まっている場合、一文字余計に取ってきている場合があるのでそのフォロー
        } else {
          $str = mb_substr($matches[1], 0, -1);
          $prefecrureCd = @$prefectureMap[$str];
        }
      }
    }
    if (is_null($prefecrureCd)) {
      $prefecrureCd = '';
    }

    $hashTel = '';
    if ($data['tel'] && preg_match('/^0[1-9][0-9]{8,9}$/', $data['tel'])) {
      $hashTel = $this->convertHashTel($data['tel']);
    }
    if (! $hashTel && ! $prefecrureCd) {
      return; // 電話番号、都道府県コードが取れない場合は終了。これまでに取れていたデータは削除しない
    }

    /** @var \Doctrine\DBAL\Connection $dbMain */
    $dbMain = $this->getConnection('main');
    $sql = 'REPLACE INTO tb_sales_voucher_customer_statistics_info (';
    $sql .= 'voucher_number, hash_tel, prefecture_cd';
    $sql .= ') VALUES (';
    $sql .= ':voucher_number, :hash_tel, :prefecture_cd';
    $sql .= ')';
    $stmt = $dbMain->prepare($sql);
    $stmt->bindValue(':voucher_number', $data['voucher_number'], \PDO::PARAM_STR);
    $stmt->bindValue(':hash_tel', $hashTel, \PDO::PARAM_STR);
    $stmt->bindValue(':prefecture_cd', $prefecrureCd, \PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
   * ハッシュ化した電話番号を取得する
   * @param unknown $src
   * @return unknown
   */
  private function convertHashTel($src) {
    if (is_null($this->salt)) {
      $this->salt = $this->getContainer()->getParameter('customer_info_tel_salt');
      if (is_null($this->salt)) {
        throw new \RuntineException("saltが設定されていません");
      }
    }
    return hash('sha256', $src . $this->salt);
  }

}