<?php

namespace MiscBundle\Util;

/**
 * 文字列ユーティリティ
 */
class StringUtil
{
  /**
   * UNICODE エスケープをUTF-8文字列に戻す
   * @param $str
   * @param $charset
   * @return mixed
   */
  public function unicodeDecode($str, $charset = 'UTF-8') {
    return preg_replace_callback(
        "/\\\\u([0-9a-zA-Z]{4})/"
      , function($matches) use ($charset) {
         return mb_convert_encoding(pack("H*", $matches[1]), $charset, "UTF-16");
      }
      , $str);
  }

  /**
   * CSV文字列 作成
   * @param $array
   * @param array $fieldList
   * @param array $noEncloseFields ダブルクォートで囲まないフィールド指定（添字 or キー）
   *              ※Accessの気まぐれエンクローズと合わせるため
   * @param string $delimiter
   * @return string
   */
  public function convertArrayToCsvLine($array, $fieldList = [], $noEncloseFields = [], $delimiter = ',')
  {
    $row = [];
    if ($fieldList) {
      foreach($fieldList as $k) {
        $v = array_key_exists($k, $array) ? $array[$k] : '';
        $v = $this->processCsvFieldValue($k, $v, $noEncloseFields);
        $row[] = $v;
      }
    } else {
      foreach($array as $i => $v) {
        $row[] = $this->processCsvFieldValue($i, $v, $noEncloseFields);
      }
    }

    return implode($delimiter, $row);
  }

  // 内部メソッド
  private function processCsvFieldValue($k, $v, $noEncloseFields = [])
  {
    // ダブルクォートエスケープ
    $v = str_replace('"', '""', $v);

    if (is_array($noEncloseFields) && count($noEncloseFields)) {
      if (!strlen($v) || in_array($k, $noEncloseFields)) {
        // 気まぐれエンクローズ対応フィールド
        return $v;
      }
    }

    return sprintf('"%s"', $v);
  }

  /**
   * NULLならデフォルト値に置き換える
   * @param $var
   * @param string $default
   * @return string
   */
  public function ifNull($var, $default = '')
  {
    return is_null($var) ? $default : $var;
  }


  /**
   * 任意の長さのランダム文字列を作成
   */
  public function makeRandomString($length = 64)
  {
    return strtr(substr(base64_encode(openssl_random_pseudo_bytes($length)), 0, $length),'/+','_-');
  }


  /**
   * バイナリデータをBase64文字列化（ついでに圧縮）
   * @param string $binary
   * @return string
   */
  public function binaryToBase64WithDeflate($binary)
  {
    return base64_encode(gzdeflate($binary));
  }

  /**
   * バイナリデータをBase64文字列から戻す
   * @param string $binary
   * @return string
   */
  public function reverseBinaryToBase64WithDeflate($binary)
  {
    return gzinflate(base64_decode($binary));
  }

  /**
   * JAN-13 チェックディジット計算
   * @param $num
   * @return int
   */
  public function calcJanCodeDigit($num)
  {
    $arr = str_split($num);
    $odd = 0;
    $mod = 0;
    for($i = 0; $i < count($arr) ; $i++){
      if(($i + 1) % 2 == 0) {
        // 偶数の総和
        $mod += intval($arr[$i]);
      } else {
        // 奇数の総和
        $odd += intval($arr[$i]);
      }
    }

    //偶数の和を3倍+奇数の総和を加算して、下1桁の数字を10から引く
    $cd = 10 - intval(substr((string)(($mod * 3) + $odd), -1));

    // 10なら1の位は0なので、0を返す。
    return $cd === 10 ? 0 : $cd;
  }

  /**
   * 数値を12桁 + 1桁のJAN13コードとして返す。
   */
  public function convertNumToJan13($num)
  {
    $prefix = '10'; // 0 開始はスマレジバーコードが読んでくれない。（規格外れゆえ？）
    $code = sprintf('%s%010d', $prefix, $num);
    return $code . $this->calcJanCodeDigit($code);
  }

  /**
   * 13桁のJAN13コードから商品コードIDを抜き出す
   * @param $jan13
   * @return int|null
   */
  public function convertJan13ToNum($jan13)
  {
    if (!preg_match('/^\d{2}(\d{10})\d$/', $jan13, $m)) {
      return null;
    }
    return intval($m[1]);
  }



  /**
   * 文字列をスネークケースへ変換
   * @param string $str
   * @return string
   */
  public function convertToSnakeCase($str)
  {
    return preg_replace_callback('/(^[A-Z]+)|([A-Z]+)/', function($matches){
      return isset($matches[2]) ? ('_' . strtolower($matches[2])) : strtolower($matches[1]);
    }, $str);
  }

  /**
   * 文字列をキャメルケースへ変換
   * @param string $str
   * @return string
   */
  public function convertToCamelCase($str)
  {
    return preg_replace_callback('/(_[a-z0-9])/', function($matches){
      return str_replace('_', '', strtoupper($matches[1]));
    }, $str);
  }

  /**
   * サーバのユニークID文字列を返す
   * @param string $prefix
   * @return string
   */
  public function getUniqueId($prefix = '')
  {
    return uniqid($prefix, true);
  }

  /**
   * カンマとアンバサンドを全角に変換する
   * @param string $str 文字列
   * @return string
   */
  public function convertCommaAndAmpersandToFullwidth($str)
  {
    $str = str_replace(",", "，", $str);
    $str = str_replace("&", "＆", $str);
    return $str;
  }
}


