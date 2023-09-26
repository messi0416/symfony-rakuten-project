<?php
include(dirname(__FILE__) . '/http.php');
include(dirname(__FILE__) . '/simple_html_dom.php');
include(dirname(__FILE__) . '/db_connect.php');

class Save1688Good {
    public static $EVERTHING_OK = 0;
    public static $SOLD_OUT = -2;
    public static $GOOD_NOT_EXIST = -3;
    public static $UNKNOWN_ERROR = -4;

    public static $prefix_1688 = 'https://detail.1688.com/offer/';


    public static $CHANGE_TYPE_DELETED = 1;
    public static $CHANGE_TYPE_SOLDOUT = 2;
    public static $CHANGE_TYPE_ADDED = 3;
    public static $CHANGE_TYPE_NAME_CHANGED = 4;
    public static $CHANGE_TYPE_SKU_SOLDOUT = 5;
    public static $CHANGE_TYPE_SKU_CHANGED = 6;
    public static $CHANGE_TYPE_SKU_ADDED = 7;

    public $goodInformTable = 'tb_1688_good_inform';
    public $goodSKUInformTable = 'tb_1688_good_sku_inform';
    public $goodSKUDetailInformTable = 'tb_1688_good_sku_detail_inform';
    public $logTable = 'tb_1688_product_change_log';

    private $db;

    public function __construct($db_global) {
        $this->db = $db_global;
    }

    public function getBooleanValue($str) {
        if ($str === 'true')
            return true;
        else
            return false;
    }

    private function getOfferIdFromUrl($url) {
        $offerLink = substr($url, strlen(self::$prefix_1688));
        $indexHtmlSuffix = strpos($offerLink, '.html');
        $offerId = substr($offerLink, 0, $indexHtmlSuffix);

        return $offerId;
    }

    private function getUrlFromOfferId($offerId) {
        $url = self::$prefix_1688 . $offerId . '.html';

        return $url;
    }

    private function log($offerId, $changetype, $logStr, $sku_attrname = null, $amount_before = null, $amount_after = null) {
        $goodInformSQL = <<<EOT
INSERT INTO `$this->logTable` (`offerid`, `offerurl`, `check_time`, `change_type`, `attrname`, `amount_before`, `amount_after`, `change_log`) VALUES
(:offerid, :offerurl, now(), :type, :attrname, :amount_before, :amount_after, :log);
EOT;
        $stmt = $this->db->prepare($goodInformSQL);
        $stmt->bindParam("offerid", $offerId);
        $stmt->bindParam("offerurl", $this->getUrlFromOfferId($offerId));
        $stmt->bindParam("type", $changetype);
        $stmt->bindParam("attrname", $sku_attrname);
        $stmt->bindParam("amount_before", $amount_before);
        $stmt->bindParam("amount_after", $amount_after);
        $stmt->bindParam("log", $logStr);
        $stmt->execute();
    }

    private function logDeleted($offerId) {
        $this->log($offerId, self::$CHANGE_TYPE_DELETED, "商品が削除されました。");
    }

    private function logSoldOut($offerId, $isSoldOut) {
        if ($isSoldOut) // good status changed from possible to impossible
            $this->log($offerId, self::$CHANGE_TYPE_SOLDOUT, "商品が全部売れました。");
        else // good status changed from impossible to possible
            $this->log($offerId, self::$CHANGE_TYPE_ADDED, "商品が新たに入りました。");
    }

    private function logProductChanged($offerId, $prevName, $otherName) {
        $this->log($offerId, self::$CHANGE_TYPE_NAME_CHANGED, "商品名が '" . $prevName . "'から '" . $otherName."'に変更されました。");
    }

    private function logSKUDiff($offerId, $newSKUData) {
        $stmt = $this->db->prepare("SELECT * FROM `$this->goodSKUDetailInformTable` WHERE `offerid` = :offerid");
        $stmt->bindParam("offerid", $offerId);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // check the goods that already in db.
        for ($i = 0; $i < count($result); $i++) {
            $attrname = $result[$i]["attrname"];
            $originalRemaining = $result[$i]["canBookCount"];

            if (isset($newSKUData[$attrname]))
                $canBookCount = $newSKUData[$attrname]->canBookCount;
            else
                $canBookCount = 0;

            // if remaining count has changed
            if ($canBookCount == 0)
                $this->log($offerId, self::$CHANGE_TYPE_SKU_SOLDOUT, "'" . $attrname . "'が " . $originalRemaining . "から 全て売れました。", $attrname, $originalRemaining, 0);
            else if ($originalRemaining != $canBookCount)
                $this->log($offerId, self::$CHANGE_TYPE_SKU_CHANGED, "'" . $attrname . "'の数量が " . $originalRemaining . "から " . $canBookCount . "に 変更されました。", $attrname, $originalRemaining, $canBookCount);

            unset($newSKUData[$attrname]);
        }
	
        // check new added goods
        foreach ($newSKUData as $key => $value) {
            $this->log($offerId, self::$CHANGE_TYPE_SKU_ADDED, "'" . $key . "'が " . $value->canBookCount . "個が新しくできました。", $key, 0, $value->canBookCount);
        }
    }

    private function insertSKUDetailInformation($offerId, $skuDetailInform) {
        $stmt = $this->db->prepare("DELETE FROM `$this->goodSKUDetailInformTable` WHERE `offerid` = :offerid");
        $stmt->bindParam("offerid", $offerId);
        $stmt->execute();

        foreach ($skuDetailInform as $key => $value) {
            $insertGoodSKUDetailInformSQL = <<<EOT
        INSERT INTO `$this->goodSKUDetailInformTable`
        (`offerid`, `attrname`, `canBookCount`, `discountPrice`, `price`, `saleCount`, `skuId`, `specId`) VALUES
        (:offerid, :attrname, :canBookCount, :discountPrice, :price, :saleCount, :skuId, :specId)
EOT;
            $stmt = $this->db->prepare($insertGoodSKUDetailInformSQL);
            $stmt->bindParam("offerid", $offerId);
            $stmt->bindParam("attrname", $key);
            $stmt->bindParam("canBookCount", $value->canBookCount);
            $stmt->bindParam("discountPrice", $value->discountPrice);
            $stmt->bindParam("price", $value->price);
            $stmt->bindParam("saleCount", $value->saleCount);
            $stmt->bindParam("skuId", $value->skuId);
            $stmt->bindParam("specId", $value->specId);
            $stmt->execute();
        }
    }

    private function deleteOfferData($offerId) {
        $stmt = $this->db->prepare("SELECT * FROM `$this->goodInformTable` WHERE `offerid` = :offerid");
        $stmt->bindParam("offerid", $offerId);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result != null) {
            $this->logDeleted($offerId);

            $stmt = $this->db->prepare("DELETE FROM `$this->goodInformTable` WHERE `offerid` = :offerid");
            $stmt->bindParam("offerid", $offerId);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM `$this->goodSKUInformTable` WHERE `offerid` = :offerid");
            $stmt->bindParam("offerid", $offerId);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM `$this->goodSKUDetailInformTable` WHERE `offerid` = :offerid");
            $stmt->bindParam("offerid", $offerId);
            $stmt->execute();
        }
    }

    public function insertGoodInformation($url) {
        $detailDebug = false;
        try {
            $now = time();
            var_dump($url);

            $offerId = $this->getOfferIdFromUrl($url);

            $iDetailConfig_str = '';
            $iDetailData_str = '';

            $goodName = '';
            $goodImage = '';

            list($receive_headers, $receive_contents, $receive_cookies, $receive_info) = simple_get($url, '', $cookies=Array(), 30, false);
            if ($detailDebug)
                echo (time() - $now) . "-111\n";

            $isSoldOut = 1;

            $contentList = explode('<div id="J_DetailInside"', $receive_contents);
            if (count($contentList) == 2) {
                $receive_contents = $contentList[1];

                list($receive_contents, $temp) = explode('<div class="detail-inside area-detail-activity"', $receive_contents);
                $receive_contents = '<div id="J_DetailInside"' . $receive_contents;

                $body_structure = str_get_html($receive_contents);
                if ($detailDebug)
                    echo (time() - $now) . "-444\n";

                foreach ($body_structure->find('div#mod-detail') as $div) {
                    $isSoldOut = 0;

                    foreach ($div->find('div#mod-detail-title > h1') as $h1) {
                        $goodName = iconv("gbk", "UTF-8", $h1->innertext);
                    }

                    foreach ($div->find('div.offerdetail_w1190_gallery > div.mod-detail-gallery > div.tab-pane > a.box-img > img') as $img) {
                        $goodImage = $img->src;
                    }
                }
            } else {
                $contentList = explode('<div data-widget-name="offerdetail_common_jsheader"', $receive_contents);
                if (count($contentList) < 2) {
                    $this->deleteOfferData($offerId);

                    return self::$GOOD_NOT_EXIST;
                }

                $receive_contents = $contentList[1];
                list($receive_contents, $temp) = explode('</div>', $receive_contents);

                $body_structure = str_get_html($receive_contents);
            }

            if ($detailDebug)
                echo (time() - $now) . "-555\n";
            $get_config = false;
            $get_data = false;
            foreach ($body_structure->find('script') as $script) {
                $scriptContent = $script->innertext;

                preg_match('/var iDetailConfig ?= ?(\{.*?\});/s', $scriptContent, $matches);
                if ($matches) {
                    $iDetailConfig_str = iconv("gbk", "UTF-8", $matches[1]);

                    $iDetailConfig_str = str_replace("'", "\"", $iDetailConfig_str);

                    $get_config = true;
                }

                preg_match('/var iDetailData ?= ?(\{.*?\});/s', $scriptContent, $matches);
                if ($matches) {
                    $iDetailData_str = iconv("gbk", "UTF-8", $matches[1]);

                    $iDetailData_str = str_replace("'", "\"", $iDetailData_str);

                    $get_data = true;
                }

                if ($get_config && $get_data)
                    break;
            }

            // GOOD Information Part
            $good_obj = json_decode($iDetailConfig_str);

            if ($good_obj == null) {
                $this->deleteOfferData($offerId);

                return self::$GOOD_NOT_EXIST;
            }

            if ($detailDebug)
                echo (time() - $now) . "-666\n";
            $stmt = $this->db->prepare("SELECT * FROM `$this->goodInformTable` WHERE `offerid` = :offerid");
            $stmt->bindParam("offerid", $offerId);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result == null) {
                $goodInformSQL = <<<EOT
INSERT INTO `$this->goodInformTable`
(`offerid`, `offerurl`, `productname`, `defaultImage`, `status`, `pageid`, `catid`, `dcatid`, `parentdcatid`, `isRangePriceSKU`, `isSKUOffer`, `isTP`,
`isSlsjSeller`, `unit`, `priceUnit`, `isPreview`, `isVirtualCat`, `refPrice`, `beginAmount`, `companySiteLink`, `hasConsignPrice`, `qrcode`, `minqrcode`) VALUES
(:offerid, :offerurl, :productname, :defaultImage, :status, :pageid, :catid, :dcatid, :parentdcatid, :isRangePriceSKU, :isSKUOffer, :isTP,
:isSlsjSeller, :unit, :priceUnit, :isPreview, :isVirtualCat, :refPrice, :beginAmount, :companySiteLink, :hasConsignPrice, :qrcode, :minqrcode)
EOT;
            } else {
                // if status of good has changed
                if ($result['status'] != $isSoldOut) {
                    $this->logSoldOut($offerId, $isSoldOut);
                } else if ($isSoldOut == 0 && $result['productname'] != $goodName) {
                    $this->logProductChanged($offerId, $result['productname'], $goodName);
                }

                $goodInformSQL = <<<EOT
UPDATE `$this->goodInformTable` SET
`offerurl` = :offerurl,
`productname` = :productname,
`defaultImage` = :defaultImage,
`status` = :status,
`pageid` = :pageid,
`catid` = :catid,
`dcatid` = :dcatid,
`parentdcatid` = :parentdcatid,
`isRangePriceSKU` = :isRangePriceSKU,
`isSKUOffer` = :isSKUOffer,
`isTP` = :isTP,
`isSlsjSeller` = :isSlsjSeller,
`unit` = :unit,
`priceUnit` = :priceUnit,
`isPreview` = :isPreview,
`isVirtualCat` = :isVirtualCat,
`refPrice` = :refPrice,
`beginAmount` = :beginAmount,
`companySiteLink` = :companySiteLink,
`hasConsignPrice` = :hasConsignPrice,
`qrcode` = :qrcode,
`minqrcode` = :minqrcode
WHERE
`offerid` = :offerid
EOT;
            }
            $stmt = $this->db->prepare($goodInformSQL);
            $stmt->bindParam("offerid", $offerId);
            $stmt->bindParam("offerurl", $this->getUrlFromOfferId($offerId));
            $stmt->bindParam("productname", $goodName);
            $stmt->bindParam("defaultImage", $goodImage);
            $stmt->bindParam("status", $isSoldOut);
            $stmt->bindParam("pageid", $good_obj->pageid);
            $stmt->bindParam("catid", $good_obj->catid);
            $stmt->bindParam("dcatid", $good_obj->dcatid);
            $stmt->bindParam("parentdcatid", $good_obj->parentdcatid);
            $stmt->bindParam("isRangePriceSKU", $this->getBooleanValue($good_obj->isRangePriceSku));
            $stmt->bindParam("isSKUOffer", $this->getBooleanValue($good_obj->isSKUOffer));
            $stmt->bindParam("isTP", $this->getBooleanValue($good_obj->isTP));
            $stmt->bindParam("isSlsjSeller", $this->getBooleanValue($good_obj->isSlsjSeller));
            $stmt->bindParam("unit", $good_obj->unit);
            $stmt->bindParam("priceUnit", $good_obj->priceUnit);
            $stmt->bindParam("isPreview", $this->getBooleanValue($good_obj->isPreview));
            $stmt->bindParam("isVirtualCat", $this->getBooleanValue($good_obj->isVirtualCat));
            $stmt->bindParam("refPrice", $good_obj->refPrice);
            $stmt->bindParam("beginAmount", $good_obj->beginAmount);
            $stmt->bindParam("companySiteLink", $good_obj->companySiteLink);
            $stmt->bindParam("hasConsignPrice", $this->getBooleanValue($good_obj->hasConsignPrice));
            $stmt->bindParam("qrcode", $good_obj->qrcode);
            $stmt->bindParam("minqrcode", $good_obj->minqrcode);
            $stmt->execute();


            if ($detailDebug)
                echo (time() - $now) . "-777\n";

            // SKU Information Part
            $skuProps = json_decode($iDetailData_str);
            if (isset($skuProps->sku) != null) {
                $skuInform = $skuProps->sku;

                $stmt = $this->db->prepare("SELECT * FROM `$this->goodSKUInformTable` WHERE `offerid` = :offerid");
                $stmt->bindParam("offerid", $offerId);
                $stmt->execute();
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($result == null) {
                    $goodSKUInformSQL = <<<EOT
INSERT INTO `$this->goodSKUInformTable`
(`offerid`, `discount`, `discountPrice`, `price`, `retailPrice`, `canBookCount`, `saleCount`, `priceRange`, `priceRangeOriginal`) VALUES
(:offerid, :discount, :discountPrice, :price, :retailPrice, :canBookCount, :saleCount, :priceRange, :priceRangeOriginal)
EOT;
                } else {
                    $goodSKUInformSQL = <<<EOT
UPDATE `$this->goodSKUInformTable` SET
`discount` = :discount,
`discountPrice` = :discountPrice,
`price` = :price,
`retailPrice` = :retailPrice,
`canBookCount` = :canBookCount,
`saleCount` = :saleCount,
`priceRange` = :priceRange,
`priceRangeOriginal` = :priceRangeOriginal
WHERE
`offerid` = :offerid
EOT;
                }
                $stmt = $this->db->prepare($goodSKUInformSQL);
                $stmt->bindParam("offerid", $offerId);
                $stmt->bindParam("discount", $skuInform->discount);
                $stmt->bindParam("discountPrice", $skuInform->discountPrice);
                $stmt->bindParam("price", $skuInform->price);
                $stmt->bindParam("retailPrice", $skuInform->retailPrice);
                $stmt->bindParam("canBookCount", $skuInform->canBookCount);
                $stmt->bindParam("saleCount", $skuInform->saleCount);

                if (isset($skuInform->priceRange))
                    $priceRange = json_encode($skuInform->priceRange);
                else
                    $priceRange = "";
                $stmt->bindParam("priceRange", $priceRange);
                if (isset($skuInform->priceRangeOriginal))
                    $priceRangeOriginal = json_encode($skuInform->priceRangeOriginal);
                else
                    $priceRangeOriginal = "";
                $stmt->bindParam("priceRangeOriginal", $priceRangeOriginal);
                $stmt->execute();


                // log the detail SKU information that has changed
                $skuDetailInform = (array)$skuInform->skuMap;
                $this->logSKUDiff($offerId, $skuDetailInform);

                $this->insertSKUDetailInformation($offerId, $skuDetailInform);
            } else {
                echo "===============================================\n";

                $stmt = $this->db->prepare("DELETE FROM `$this->goodSKUInformTable` WHERE `offerid` = :offerid");
                $stmt->bindParam("offerid", $offerId);
                $stmt->execute();

                $saleStateStr = "";
                foreach ($body_structure->find('div#mod-detail') as $div) {
                    foreach ($div->find('div[data-widget-name=offerdetail_ditto_purchasing] > div.mod-detail-purchasing-single') as $divsale) {
                        $saleStateStr = iconv("gbk", "UTF-8", $divsale->attr['data-mod-config']);
                        $saleStateStr = str_replace("'", "\"", $saleStateStr);

                        break;
                    }
                }

                $normalpriceStr = "";
                foreach ($body_structure->find('div#mod-detail') as $div) {
                    foreach ($div->find('div[data-widget-name=offerdetail_ditto_promoting] > div.d-content') as $divspecial) {
                        $normalpriceStr = html_entity_decode(iconv("gbk", "UTF-8", $divspecial->attr['data-price']));
                        $normalpriceStr = str_replace("'", "\"", $normalpriceStr);
                        break;
                    }
                }

                $skuDetailInform = array();

                if ($saleStateStr != '' && $normalpriceStr != '') {
                    $saleStateArray = json_decode($saleStateStr);
                    $normalpriceArray = json_decode($normalpriceStr)->normalPrice;

                    $priceArray = array();
                    foreach($normalpriceArray as $normalprice) {
                        if (isset($normalprice->begin))
                            $begin = (int)$normalprice->begin;
                        else
                            $begin = 1;
                        $priceArray[] = array($begin, (float)$normalprice->price);
                    }

                    $goodSKUInformSQL = <<<EOT
INSERT INTO `$this->goodSKUInformTable`
(`offerid`, `price`, `retailPrice`, `canBookCount`, `saleCount`, `priceRange`, `priceRangeOriginal`) VALUES
(:offerid, '', '', :canBookCount, 0, :priceRange, :priceRangeOriginal)
EOT;
                    $stmt = $this->db->prepare($goodSKUInformSQL);
                    $stmt->bindParam("offerid", $offerId);
                    $stmt->bindParam("canBookCount", $saleStateArray->max);

                    $priceRange = json_encode($priceArray);
                    $stmt->bindParam("priceRange", $priceRange);
                    $stmt->bindParam("priceRangeOriginal", $priceRange);
                    $stmt->execute();


                    // log the detail SKU information that has changed

                    $outlineSKUInformObj = new stdClass();
                    $outlineSKUInformObj->canBookCount = $saleStateArray->max;
                    $outlineSKUInformObj->saleCount = 0;
                    $outlineSKUInformObj->skuId = '';
                    $outlineSKUInformObj->specId = '';
                    $skuDetailInform[''] = $outlineSKUInformObj;
                }

                $this->logSKUDiff($offerId, $skuDetailInform);

                $this->insertSKUDetailInformation($offerId, $skuDetailInform);
            }

            unset($body_structure);

            if ($isSoldOut)
                return self::$SOLD_OUT;
            else
                return self::$EVERTHING_OK;
        } catch (Exception $e) {
            var_dump($e);

            return self::$UNKNOWN_ERROR;
        }
    }
};


$db = new \PDO("mysql:host=$server_name;dbname=$db_name", $db_user, $db_password);
$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$db->exec("SET NAMES 'utf8'");

$saveDB = new Save1688Good($db);

if (true) { // for single product test
    $now = time();
    $url = "https://detail.1688.com/offer/1295652258.html";
    $resultDB = $saveDB->insertGoodInformation($url);
    var_dump($resultDB);
    echo (time() - $now) . "\n";
} else { // for crawling all products.
    $vendorTable = "tb_vendoraddress";
    $sql = "SELECT * FROM `$vendorTable` WHERE sire_adress like '" . Save1688Good::$prefix_1688 . "%' and stop = 0";
    $stmtVender = $db->prepare($sql);
    $stmtVender->execute();
    $result = $stmtVender->fetchAll(\PDO::FETCH_ASSOC);
    for ($i = 0; $i < count($result); $i++) {
        $now = time();
        $url = $result[$i]["sire_adress"];

        $resultDB = $saveDB->insertGoodInformation($url);
        var_dump($resultDB);
        if ($resultDB == Save1688Good::$GOOD_NOT_EXIST) {
            $sql = "UPDATE `$vendorTable` set stop = -1 WHERE vendoraddress_CD = :vendoraddress_CD";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("vendoraddress_CD", $result[$i]["vendoraddress_CD"]);
            $stmt->execute();
        }
        echo (time() - $now) . "\n";
    }
}
