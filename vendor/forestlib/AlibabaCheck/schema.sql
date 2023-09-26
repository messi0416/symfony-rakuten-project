/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : test

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2016-12-22 03:39:25
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for tb_1688_product_change_log
-- ----------------------------
DROP TABLE IF EXISTS `tb_1688_product_change_log`;
CREATE TABLE `tb_1688_product_change_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `offerid` bigint(50) NOT NULL,
  `offerurl` varchar(1024) NOT NULL,
  `check_time` datetime NOT NULL,
  `change_type` int(11) NOT NULL DEFAULT '1' COMMENT '1:delete, 2: soldout, 3: new, 4: namechange, 5: skusoldout, 6: skuchanged, 7: skuadd',
  `attrname` varchar(255) DEFAULT NULL,
  `amount_before` bigint(20) DEFAULT NULL,
  `amount_after` bigint(20) DEFAULT NULL,
  `change_log` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tb_1688_product_change_log
-- ----------------------------

-- ----------------------------
-- Table structure for tb_1688_good_sku_inform
-- ----------------------------
DROP TABLE IF EXISTS `tb_1688_good_sku_inform`;
CREATE TABLE `tb_1688_good_sku_inform` (
  `offerid` bigint(50) NOT NULL,
  `discount` decimal(20,2) DEFAULT NULL,
  `discountPrice` decimal(20,2) DEFAULT NULL,
  `price` varchar(255) NOT NULL,
  `retailPrice` varchar(255) NOT NULL,
  `canBookCount` bigint(20) NOT NULL,
  `saleCount` bigint(20) NOT NULL,
  `priceRange` varchar(1024) DEFAULT NULL,
  `priceRangeOriginal` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`offerid`),
  CONSTRAINT `fk_sku_good_offer` FOREIGN KEY (`offerid`) REFERENCES `tb_1688_good_inform` (`offerid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tb_1688_good_sku_inform
-- ----------------------------

-- ----------------------------
-- Table structure for tb_1688_good_sku_detail_inform
-- ----------------------------
DROP TABLE IF EXISTS `tb_1688_good_sku_detail_inform`;
CREATE TABLE `tb_1688_good_sku_detail_inform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offerid` bigint(50) NOT NULL,
  `attrname` varchar(255) NOT NULL,
  `canBookCount` bigint(20) NOT NULL,
  `discountPrice` decimal(20,2) DEFAULT NULL,
  `price` decimal(20,2) DEFAULT NULL,
  `saleCount` bigint(20) NOT NULL,
  `skuId` varchar(255) NOT NULL,
  `specId` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_SKU` (`offerid`,`skuId`),
  CONSTRAINT `fk_sku_detail_good_offer` FOREIGN KEY (`offerid`) REFERENCES `tb_1688_good_inform` (`offerid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tb_1688_good_sku_detail_inform
-- ----------------------------

-- ----------------------------
-- Table structure for tb_1688_good_inform
-- ----------------------------
DROP TABLE IF EXISTS `tb_1688_good_inform`;
CREATE TABLE `tb_1688_good_inform` (
  `offerid` bigint(50) NOT NULL,
  `offerurl` varchar(1024) NOT NULL,
  `productname` varchar(1024) NOT NULL,
  `defaultImage` varchar(1024) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0: normal, 1: soldout',
  `pageid` varchar(255) NOT NULL,
  `catid` int(11) NOT NULL,
  `dcatid` int(11) NOT NULL,
  `parentdcatid` int(11) NOT NULL,
  `isRangePriceSKU` tinyint(1) NOT NULL,
  `isSKUOffer` tinyint(1) NOT NULL,
  `isTP` tinyint(1) NOT NULL,
  `isSlsjSeller` tinyint(1) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `priceUnit` varchar(50) DEFAULT NULL,
  `isPreview` tinyint(1) DEFAULT NULL,
  `isVirtualCat` tinyint(1) DEFAULT NULL,
  `refPrice` decimal(10,2) DEFAULT NULL,
  `beginAmount` int(11) DEFAULT NULL,
  `companySiteLink` varchar(255) NOT NULL,
  `hasConsignPrice` tinyint(1) NOT NULL,
  `qrcode` varchar(1024) DEFAULT NULL,
  `minqrcode` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`offerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tb_1688_good_inform
-- ----------------------------

