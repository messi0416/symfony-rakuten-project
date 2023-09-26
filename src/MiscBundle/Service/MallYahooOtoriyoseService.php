<?php


namespace MiscBundle\Service;

use MiscBundle\Entity\TbYahooOtoriyoseAccessLog;

class MallYahooOtoriyoseService
{
  use ServiceBaseTrait;

  public static $YAHOO_OTORIYOSE_PURCHASE_CSV_HEADERS = [
      '順位'
    , '商品名'
    , '商品コード'
    , '売上（税込）'
    , '注文数'
    , '注文点数'
    , '注文者数'
    , '購買率'
    , 'ページビュー'
    , '訪問者数(ユニークユーザー数)'
  ];

  /**
   * アップロードされたおとりよせCSVをすべてUTF-8へ変換し、オブジェクト配列で返す
   * @param $file
   * @param $headerArray
   * @param $targetDate
   * @return array<TbYahooOtoriyoseAccessLog>
   */
  public function extractDataFromCsv($file, $headerArray, $targetDate)
  {
    $result = [];
    try {
      $fp = fopen($file->getPathname(), 'rb');
      // 日付とヘッダーなので捨てる
      fgets($fp);
      fgets($fp);

      while ($line = str_replace(["\r\n", "\n"], '' ,mb_convert_encoding(fgets($fp), 'UTF-8', 'SJIS-WIN'))) {
        $separatedLine = explode(',', $line);
        $row = [];
        for($i = 0;$i < count($headerArray);$i++) {
          $row[$headerArray[$i]] = $separatedLine[$i];
        }

        $otoriyose = new TbYahooOtoriyoseAccessLog();
        $otoriyose->setTargetDate($targetDate);
        $otoriyose->setDaihyoSyohinCode($row['商品コード']);
        $otoriyose->setPv($row['ページビュー']);
        $otoriyose->setUu($row['訪問者数(ユニークユーザー数)']);
        $result[] = $otoriyose;
      }
    } catch (\Exception $e) {
      throw new \RuntimeException(sprintf("%s [%s]", $e->getMessage(), $file->getClientOriginalName()));
    }
    return $result;
  }
}
