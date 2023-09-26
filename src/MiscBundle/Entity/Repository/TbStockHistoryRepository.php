<?php

namespace MiscBundle\Entity\Repository;

use MiscBundle\Entity\Repository\TbProductSalesAccountRepository;
use MiscBundle\Entity\Repository\TbProductSalesAccountResultHistoryRepository;

/**
 * TbStockHistoryRepository
 */
class TbStockHistoryRepository extends BaseRepository
{
  /**
   * 指定期間最終日の在庫・注残と、期間中の在庫・注残の平均、及び担当者不在の在庫・注残情報を返す。
   *
   * @param array $conditions 検索条件
   * @return array 以下のキーを持つ連想配列
   *  'stockDate' => string 在庫日時,
   *  'stockQuantity' => int 全体在庫数量,
   *  'stockAmount' => int 全体在庫金額,
   *  'remainQuantity' => int 全体注残数量,
   *  'remainAmount' => int 全体注残金額,
   *  'stockQuantityAvg' => int 平均在庫数量,
   *  'stockAmountAvg' => int 平均在庫金額,
   *  'remainQuantityAvg' => int 平均注残数量,
   *  'remainAmountAvg' => int 平均注残金額
   *  'noAccountStockQuantity' => int 担当者なし在庫数量,
   *  'noAccountStockAmount' => int 担当者なし在庫金額,
   *  'noAccountRemainQuantity' => int 担当者なし注残数量,
   *  'noAccountRemainAmount' => int 担当者なし注残金額,
   */
  public function findStockAndRemainInPeriod($conditions)
  {
    $dbMain = $this->getConnection('main');

    $stockDateFrom = $conditions['targetDateFrom'];
    $stockDateTo = (new \DateTime($conditions['targetDateTo']))->modify('+1 days')->format('Y-m-d');

    $stockWheres = [];
    // 条件：対象日時From
    if ($stockDateFrom) {
      $stockWheres[] = '在庫日時 >= :stockDateFrom';
      $params[':stockDateFrom'] = $stockDateFrom;
    }
    // 条件：対象日時To
    if ($stockDateTo) {
      $stockWheres[] = '在庫日時 < :stockDateTo';
      $params[':stockDateTo'] = $stockDateTo;
    }
    $addStockWheres = ' AND ' . implode(' AND ', $stockWheres);

    $sql = <<<EOD
      SELECT
        DATE_FORMAT(s.在庫日時, '%Y-%m-%d') stockDate,
        s.現在庫数 stockQuantity,
        s.現在庫金額 stockAmount,
        COALESCE(i.発注済在庫数, 0) + COALESCE(i.入荷済在庫数, 0) + COALESCE(i.出荷待在庫数, 0) + COALESCE(i.出荷済在庫数, 0) remainQuantity,
        COALESCE(i.発注済在庫金額, 0) + COALESCE(i.入荷済在庫金額, 0) + COALESCE(i.出荷待在庫金額, 0) + COALESCE(i.出荷済在庫金額, 0) remainAmount
      FROM
        tb_stock_history s
        INNER JOIN (
          SELECT
            max(id) id,
            DATE_FORMAT(在庫日時, '%Y-%m-%d') AS stock_date
          FROM
            tb_stock_history
          WHERE
            company_code = 'forest'
            {$addStockWheres}
          GROUP BY
            DATE_FORMAT(在庫日時, '%Y-%m-%d')
        ) S ON s.id = S.id
        LEFT JOIN tb_stock_history_ioh i ON s.id = i.history_id
      ORDER BY stockDate DESC;
EOD;
    $stmt = $dbMain->prepare($sql);
    foreach($params as $k => $v) {
      $stmt->bindValue($k, $v, \PDO::PARAM_STR);
    }
    $stmt->execute();
    $stocks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $days = count($stocks); 
    $stockDate = $days ? $stocks[0]['stockDate'] : '-';

    $noAccountResult = [];
    if ($days) {
      // タスク適用開始日の指定有無に応じて、担当者なし実績を求める
      if ($conditions['applyStartDateFrom'] || $conditions['applyStartDateTo']) {
        // 有: 指定期間の担当者なし実績を直接取得する
        /** @var TbProductSalesAccountRepository $aRepo */
        $aRepo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbProductSalesAccount');
        $noAccountResult = $aRepo->findNoAccountStockResultLimitSaleStart($stockDate, $conditions);
      } else {
        // 無: 担当者実績合計を求め、全体から差し引くことで算出する
        /** @var TbProductSalesAccountResultHistoryRepository $rRepo */
        $rRepo = $this->getContainer()->get('doctrine')->getRepository('MiscBundle:TbProductSalesAccountResultHistory');
        $accountResultTotal = $rRepo->findAccountStockResultTotal($stockDate);
        $noAccountResult = [
          'stockQuantity' => $stocks[0]['stockQuantity'] - $accountResultTotal['stockQuantity'],
          'stockAmount' => $stocks[0]['stockAmount'] - $accountResultTotal['stockAmount'],
          'remainQuantity' => $stocks[0]['remainQuantity'] - $accountResultTotal['remainQuantity'],
          'remainAmount' => $stocks[0]['remainAmount'] - $accountResultTotal['remainAmount'],
        ];
      }
    }

    return [
      'stockDate' => $stockDate,
      'stockQuantity' => $days ? $stocks[0]['stockQuantity'] : 0,
      'stockAmount' => $days ? $stocks[0]['stockAmount'] : 0,
      'remainQuantity' => $days ? $stocks[0]['remainQuantity'] : 0,
      'remainAmount' => $days ? $stocks[0]['remainAmount'] : 0,
      'stockQuantityAvg' => $days ? round(array_sum(array_column($stocks, 'stockQuantity')) / $days) : 0,
      'stockAmountAvg' => $days ? round(array_sum(array_column($stocks, 'stockAmount')) / $days) : 0,
      'remainQuantityAvg' => $days ? round(array_sum(array_column($stocks, 'remainQuantity')) / $days) : 0,
      'remainAmountAvg' => $days ? round(array_sum(array_column($stocks, 'remainAmount')) / $days) : 0,
      'noAccountStockQuantity' => $days ? $noAccountResult['stockQuantity'] : 0,
      'noAccountStockAmount' => $days ? $noAccountResult['stockAmount'] : 0,
      'noAccountRemainQuantity' => $days ? $noAccountResult['remainQuantity'] : 0,
      'noAccountRemainAmount' => $days ? $noAccountResult['remainAmount'] : 0,
    ];
  }
}
