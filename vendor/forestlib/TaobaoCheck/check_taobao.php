<?php
require_once(dirname(__FILE__) . '/TaobaoCheckStatuses.php');
require_once 'database.php';
date_default_timezone_set('Asia/Tokyo');

ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);

class Taobao {

  private $_db;
  private $_debug;
  private $_taobao_url_regexp = '/^https?:\/\/world\.taobao\.com\/item\/([0-9]+)\.htm/';
  private $_taobao_url_regexp2 = '/^https?:\/\/item\.taobao\.com\/world\/item\.htm\?ft=t&toSite=main&id=([0-9]+)/';
  private $_tmall_url_regexp = '/^https?:\/\/world\.tmall\.com\/item\/([0-9]+)\.htm/';
  private $_table_prefix = 'tb_taobao_';

  /* 共有化のため、AlibabaCheckStatuses 定数へ切り出し
  const CHANGE_TYPE_DELETED = 1;
  const CHANGE_TYPE_SOLDOUT = 2;
  const CHANGE_TYPE_ADDED = 3;
  const CHANGE_TYPE_NAME_CHANGED = 4;
  const CHANGE_TYPE_SKU_SOLDOUT = 5;
  const CHANGE_TYPE_SKU_CHANGED = 6;
  const CHANGE_TYPE_SKU_ADDED = 7;
  */
  const CHANGE_TYPE_DELETED = TaobaoCheckStatuses::CHANGE_TYPE_DELETED;
  const CHANGE_TYPE_SOLDOUT = TaobaoCheckStatuses::CHANGE_TYPE_SKU_SOLDOUT;
  const CHANGE_TYPE_ADDED = TaobaoCheckStatuses::CHANGE_TYPE_ADDED;
  const CHANGE_TYPE_NAME_CHANGED = TaobaoCheckStatuses::CHANGE_TYPE_NAME_CHANGED;
  const CHANGE_TYPE_SKU_SOLDOUT = TaobaoCheckStatuses::CHANGE_TYPE_SKU_SOLDOUT;
  const CHANGE_TYPE_SKU_CHANGED = TaobaoCheckStatuses::CHANGE_TYPE_SKU_CHANGED;
  const CHANGE_TYPE_SKU_ADDED = TaobaoCheckStatuses::CHANGE_TYPE_SKU_ADDED;

  const WAIT_TIME = 3;

  public function __construct($db, $debug = false) {
    $this->_db = $db;
    $this->_debug = $debug;
  }

  public function crawl() {
    $sql = "SELECT `sire_adress` FROM `tb_vendoraddress` WHERE `sire_adress` LIKE '%taobao.com%' OR `sire_adress` LIKE '%tmall.com%' AND `stop` = 0";

    $stmt = $this->_db->prepare($sql);
    $stmt->execute();

    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($addresses as $address) {

      printf("%s taobao check: %s\n", (new \DateTime())->format('Y-m-d H:i:s'), $address['sire_adress']);

      $new_product = $this->scrape($address['sire_adress']);

      if ($new_product) {
        $this->_save_product($new_product);
      } else {
        if ($uid = $this->_get_uid_from_url($address['sire_adress'])) {

          $sql = "SELECT * FROM `{$this->_table_prefix}products` WHERE `uid` = :uid LIMIT 1";
          $stmt = $this->_db->prepare($sql);
          $stmt->bindParam('uid', $uid);
          $stmt->execute();

          $old_product = $stmt->fetch();

          if ($old_product) {
            $message = "「{$old_product['name']}」が削除されました";
            $this->_log($old_product['id'], null, self::CHANGE_TYPE_DELETED, $message);

            // SKUを0リセット
            $sql = "UPDATE `{$this->_table_prefix}variants` SET in_stock = 0, stock = 0 WHERE `product_id` = :product_id";
            $stmt = $this->_db->prepare($sql);
            $stmt->bindValue('product_id', $old_product['id']);
            $stmt->execute();
          }
        }
      }

      if ($this->_debug) {
        exit;
      }

      sleep(self::WAIT_TIME);
    }
  }

  private function _get_uid_from_url($url) {
    if (preg_match($this->_taobao_url_regexp, $url, $match)) {
      return $match[1];
    } else if (preg_match($this->_tmall_url_regexp, $url, $match)) {
      return $match[1];
    }

    return false;
  }

  private function _log($product_id, $variant_id, $change_type, $message) {
    $now = strftime('%Y-%m-%d %H:%M:%S');

    $product_id = $product_id ? $product_id : 0;
    $variant_id = $variant_id ? $variant_id : 0;

    $sql = "INSERT INTO `{$this->_table_prefix}change_logs` (`product_id`, `variant_id`, `change_type`, `message`, `created_at`) VALUES (:product_id, :variant_id, :change_type, :message, :created_at)";
    $stmt = $this->_db->prepare($sql);
    $stmt->bindParam('product_id', $product_id);
    $stmt->bindParam('variant_id', $variant_id);
    $stmt->bindParam('change_type', $change_type);
    $stmt->bindParam('message', $message);
    $stmt->bindParam('created_at', $now);
    $stmt->execute();
  }

  private function _save_product($new_product) {
    $sql = "SELECT * FROM `{$this->_table_prefix}products` WHERE `uid` = :uid LIMIT 1";
    $stmt = $this->_db->prepare($sql);
    $stmt->bindParam('uid', $new_product['uid']);
    $stmt->execute();

    $old_product = $stmt->fetch();

    $now = strftime('%Y-%m-%d %H:%M:%S');

    if ($old_product) {
      if ($old_product['name'] != $new_product['name']) {
        $message = "商品名が「{$old_product['name']}」から「{$new_product['name']}」に変わりました";
        $this->_log($old_product['id'], null, self::CHANGE_TYPE_NAME_CHANGED, $message);
      }

      $sql = "UPDATE `{$this->_table_prefix}products` SET `name` = :name, `updated_at` = :updated_at WHERE `id` = :id";
      $stmt = $this->_db->prepare($sql);
      $stmt->bindParam('name', $new_product['name']);
      $stmt->bindParam('id', $old_product['id']);
      $stmt->bindParam('updated_at', $now);

      $stmt->execute();

      $product_id = $old_product['id'];
    } else {
      $sql = "INSERT INTO `{$this->_table_prefix}products` (`uid`, `name`, `url`, `orig_url`, `created_at`, `updated_at`) VALUES (:uid, :name, :url, :orig_url, :created_at, :updated_at)";
      $stmt = $this->_db->prepare($sql);
      $stmt->bindParam('uid', $new_product['uid']);
      $stmt->bindParam('name', $new_product['name']);
      $stmt->bindParam('url', $new_product['url']);
      $stmt->bindParam('orig_url', $new_product['orig_url']);
      $stmt->bindParam('created_at', $now);
      $stmt->bindParam('updated_at', $now);

      $stmt->execute();

      $product_id = $this->_db->lastInsertId();
    }

    $new_variant_uids = array();

    foreach ($new_product['variants'] as $new_variant) {
      $sql = "SELECT * FROM `{$this->_table_prefix}variants` WHERE `uid` = :uid LIMIT 1";
      $stmt = $this->_db->prepare($sql);
      $stmt->bindParam('uid', $new_variant['uid']);
      $stmt->execute();

      $old_variant = $stmt->fetch();

      if ($old_variant) {
        if ($old_variant['in_stock'] == 0 && $new_variant['in_stock'] == 1) {
          $message = "「{$old_product['name']}」の属性「{$old_variant['name']}」が購入可能になりました。";
          $this->_log($old_product['id'], $old_variant['id'], self::CHANGE_TYPE_SKU_ADDED, $message);
        } else if ($old_variant['in_stock'] == 1 && $new_variant['in_stock'] == 0) {
          $message = "「{$old_product['name']}」の属性「{$old_variant['name']}」が売り切れました。";
          $this->_log($old_product['id'], $old_variant['id'], self::CHANGE_TYPE_SKU_SOLDOUT, $message);
        }

        $sql = "UPDATE `{$this->_table_prefix}variants` SET `in_stock` = :in_stock, price = :price, stock = :stock, `updated_at` = :updated_at WHERE `id` = :id";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam('id', $old_variant['id']);
        $stmt->bindParam('in_stock', $new_variant['in_stock']);
        $stmt->bindParam('price', $new_variant['price']);
        $stmt->bindParam('stock', $new_variant['stock']);
        $stmt->bindParam('updated_at', $now);

        $stmt->execute();
      } else {
        if ($old_product) {
          $message = "「{$old_product['name']}」の属性「{$new_variant['name']}」が購入可能になりました。";
          $this->_log($old_product['id'], $new_variant['id'], self::CHANGE_TYPE_SKU_ADDED, $message);
        }

        $sql = "INSERT INTO `{$this->_table_prefix}variants` (`product_id`, `uid`, `name`, `in_stock`, `price`, `stock`, `created_at`, `updated_at`) VALUES (:product_id, :uid, :name, :in_stock, :price, :stock, :created_at, :updated_at)";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam('product_id', $product_id);
        $stmt->bindParam('uid', $new_variant['uid']);
        $stmt->bindParam('name', $new_variant['name']);
        $stmt->bindParam('in_stock', $new_variant['in_stock']);
        $stmt->bindParam('price', $new_variant['price']);
        $stmt->bindParam('stock', $new_variant['stock']);
        $stmt->bindParam('created_at', $now);
        $stmt->bindParam('updated_at', $now);

        $stmt->execute();
      }

      $new_variant_uids[] = $new_variant['uid'];
    }

    if ($old_product) {
      $sql = "SELECT * FROM `{$this->_table_prefix}variants` WHERE `product_id` = :product_id";
      $stmt = $this->_db->prepare($sql);
      $stmt->bindParam('product_id', $old_product['id']);
      $stmt->execute();

      $old_variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($old_variants as $old_variant) {
        if (!in_array($old_variant['uid'], $new_variant_uids) && $old_product['in_stock'] == 1) {
          $message = "「{$old_product['name']}」の属性「{$old_variant['name']}」が売り切れました。";
          $this->_log($old_product['id'], $old_variant['id'], self::CHANGE_TYPE_SKU_SOLDOUT, $message);

          $sql = "UPDATE `{$this->_table_prefix}variants` SET `in_stock` = 0, `updated_at` = :updated_at WHERE `id` = :id";
          $stmt = $this->_db->prepare($sql);
          $stmt->bindParam('id', $old_variant['id']);
          $stmt->bindParam('updated_at', $now);

          $stmt->execute();
        }
      }
    }
  }

  public function scrape($url) {
    $html = $this->_get_html($url);

    if (preg_match($this->_taobao_url_regexp, $html['url'], $match)) {
      return $this->_taobao_scrape($html['html'], $url, false);
    } else if (preg_match($this->_taobao_url_regexp2, $html['url'], $match)) {
      return $this->_taobao_scrape($html['html'], $url, true);
    } else if (preg_match($this->_tmall_url_regexp, $html['url'], $match)) {
      return $this->_tmall_scrape($html['html'], $url);
    }

    return false;
  }

  private function _tmall_scrape($html, $url) {
    if (!preg_match($this->_tmall_url_regexp, $url, $match)) {
      return false;
    }

    $id = $match[1];
    $result = array();
    $result['uid'] = $id;
    $result['url'] = sprintf('https://world.tmall.com/item/%s.htm', $id);

    $html = mb_convert_encoding($html, 'utf-8', 'gbk');

    if (!preg_match('/"title":"(.+?)",/', $html, $match)) {
      return false;
    }

    $result['name'] = $match[1];

    if (!preg_match('/"skuList":(\[.+?\]),/', $html, $skuList)) {
      return false;
    }

    $skuList = json_decode($skuList[1]);

    if (!preg_match('/"skuMap":(.+?)\},"valLoginIndicator"/', $html, $skuMap)) {
      return false;
    }

    $skuMap = json_decode($skuMap[1]);

    $variants = array();

    foreach ($skuList as $sku) {
      foreach ($skuMap as $_sku) {
        if ($sku->skuId == $_sku->skuId) {
          $variant = array();
          $variant['uid'] = $sku->skuId;
          $variant['price'] = $_sku->price;
          $variant['stock'] = $_sku->stock;
          $variant['in_stock'] = (intval($_sku->stock) >= 1) ? 1 : 0;
          $variant['name'] = $sku->names;

          $variants[] = $variant;
        }
      }
    }

    $result['variants'] = $variants;
    $result['orig_url'] = $url;

    return $result;
  }

  private function _taobao_scrape($html, $url, $alt = false) {
    if (preg_match('/charset="gbk"/', $html)) {
      $html = mb_convert_encoding($html, 'utf-8', 'gbk');
    }

    if (preg_match($this->_taobao_url_regexp, $url, $match)) {
      $id = $match[1];
    } else if (preg_match($this->_tmall_url_regexp, $url, $match)) {
      $id = $match[1];
    } else {
      return false;
    }

    $result = array();
    $result['uid'] = $id;

    if (!$alt) {
      $result['url'] = sprintf('https://world.taobao.com/item/%s.htm', $id);

      if (!preg_match('/itemTitle:"(.+?)",/', $html, $match)) {
        return false;
      }

      $result['name'] = $match[1];
    } else {
      $result['url'] = sprintf('https://item.taobao.com/world/item.htm?ft=t&toSite=main&id=%s', $id);

      if (!preg_match('/data-title=\"(.+?)\"/', $html, $match)) {
        return false;
      }

      $result['name'] = $match[1];
    }

    if (!preg_match('/skuMap.*?:.*?(\{.+?\}\})/s', $html, $skuInfo)) {
      return false;
    }

    $skuInfo = json_decode($skuInfo[1]);
    $variants = [];

    foreach ($skuInfo as $i => $sku) {
      $variant = array();
      $variant['uid'] = $sku->skuId;
      $variant['in_stock'] = (intval($sku->stock) >= 1) ? 1 : 0;
      $variant['price'] = (float)$sku->price;
      $variant['stock'] = (float)$sku->stock;

      $name_array = array();

      if ($alt) {
        $properties = trim($i, ';');
        $properties = explode(';', $properties);

        $name_array = array();

        foreach ($properties as $property) {
          if (preg_match("/data-value=\"{$property}\".+?<span>(.+?)<\/span>/s", $html, $property_name)) {
            $name_array[] = $property_name[1];
          } else {
            $name_array[] = $property;
          }
        }
      } else {
        $properties = trim($sku->properties, ';');
        $properties = explode(';', $properties);

        foreach ($properties as $property) {
          if (preg_match("/data-pv=\"{$property}\">.+?<a title=\"(.+?)\"/s", $html, $property_name)) {
            $name_array[] = $property_name[1];
          } else {
            $name_array[] = $property;
          }
        }
      }

      $variant['name'] = implode(', ', $name_array);

      $variants[] = $variant;
    }

    $result['variants'] = $variants;
    $result['orig_url'] = $url;

    return $result;
  }

  private function _get_html($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $html = curl_exec($ch);
    $info = curl_getinfo($ch);

    curl_close($ch);

    if ($info['http_code'] != 200) {
      return false;
    }

    return array('html' => $html, 'url' => $info['url']);
  }
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8', $host, $dbname);
$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$taobao = new Taobao($db);
$taobao->crawl();


// 巡回完了後、商品情報のフラグ更新処理を実行
$env = TaobaoCheckStatuses::isEnvDev() ? 'test' : 'prod';
$appRoot = dirname(dirname(dirname(dirname(__FILE__))));
$command = sprintf('%s/app/console --env=%s batch:web-check-taobao-scraping-update-product-status', $appRoot, $env);

system($command);
