-- MySQL dump 10.13  Distrib 5.1.73, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: plusnao_db
-- ------------------------------------------------------
-- Server version	5.1.73

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cake_sessions`
--

DROP TABLE IF EXISTS `cake_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cake_sessions` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `data` text NOT NULL,
  `expires` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `donna_item_mains`
--

DROP TABLE IF EXISTS `donna_item_mains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donna_item_mains` (
  `id` varchar(255) NOT NULL,
  `receivings_exists` tinyint(1) NOT NULL,
  `reservations_exsits` tinyint(1) NOT NULL,
  `shipings_exsits` tinyint(1) NOT NULL,
  `average_receiving_price_extax` int(11) NOT NULL,
  `average_selling_price_intax` int(11) NOT NULL,
  `stock_volume` int(11) NOT NULL,
  `receiving_volume` int(11) NOT NULL,
  `reservation_volume` int(11) NOT NULL,
  `shiping_volume` int(11) NOT NULL,
  `sales_volume` int(11) NOT NULL,
  `defective_volume` int(11) NOT NULL,
  `last_receiving_datetime` int(11) NOT NULL,
  `last_selling_datetime` int(11) NOT NULL,
  `first_reservations_datetime` int(11) NOT NULL,
  `price_setting` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `donna_item_masters`
--

DROP TABLE IF EXISTS `donna_item_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donna_item_masters` (
  `id` varchar(255) NOT NULL COMMENT 'item_master_code',
  `receivings_exists` tinyint(1) NOT NULL,
  `reservations_exsits` tinyint(1) NOT NULL,
  `shipings_exsits` tinyint(1) NOT NULL,
  `donna_item_main_id` varchar(255) NOT NULL,
  `options_color_code` varchar(255) NOT NULL,
  `options_size_code` varchar(255) NOT NULL,
  `sort_number` int(11) NOT NULL,
  `average_receiving_price_extax` int(11) NOT NULL,
  `average_selling_price_intax` int(11) NOT NULL,
  `stock_volume` int(11) NOT NULL,
  `receiving_volume` int(11) NOT NULL,
  `reservation_volume` int(11) NOT NULL,
  `shiping_volume` int(11) NOT NULL,
  `sales_volume` int(11) NOT NULL,
  `defective_volume` int(11) NOT NULL,
  `last_receiving_datetime` int(11) NOT NULL,
  `last_selling_datetime` int(11) NOT NULL,
  `first_reservations_datetime` int(11) NOT NULL,
  `price_setting` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `donna_item_receivings`
--

DROP TABLE IF EXISTS `donna_item_receivings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donna_item_receivings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` int(11) DEFAULT NULL,
  `donna_item_reservations_id` int(11) NOT NULL,
  `donna_item_master_id` varchar(255) NOT NULL,
  `donna_item_main_id` varchar(255) NOT NULL,
  `invalid` tinyint(1) NOT NULL,
  `donna_item_shiping_id` int(11) DEFAULT NULL,
  `receiving_classification` varchar(255) NOT NULL,
  `donna_supplier_id` int(11) NOT NULL,
  `payment_methods` varchar(255) NOT NULL,
  `receiving_price_extax` int(11) NOT NULL,
  `receiving_datetime` int(11) NOT NULL,
  `receiving_user_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `update_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `donna_item_shiping_id` (`donna_item_shiping_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `donna_item_reservations`
--

DROP TABLE IF EXISTS `donna_item_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donna_item_reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` int(11) DEFAULT NULL,
  `other_id` varchar(255) DEFAULT NULL,
  `donna_item_master_id` varchar(255) NOT NULL,
  `donna_item_main_id` varchar(255) NOT NULL,
  `options_color_code` varchar(255) NOT NULL,
  `options_size_code` varchar(255) NOT NULL,
  `supplier_item_code` varchar(255) NOT NULL,
  `reservation_classification` varchar(255) NOT NULL,
  `donna_supplier_id` int(11) NOT NULL,
  `payment_methods` varchar(255) NOT NULL,
  `reservation_price_extax` int(11) NOT NULL,
  `reservation_quantity` int(11) NOT NULL,
  `reservation_invalid_quantity` int(11) NOT NULL,
  `receiving_quantity` int(11) NOT NULL,
  `reservation_datetime` int(11) NOT NULL,
  `schedule_date` int(11) NOT NULL,
  `comments` varchar(255) NOT NULL,
  `reservation_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `donna_item_shipings`
--

DROP TABLE IF EXISTS `donna_item_shipings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donna_item_shipings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` int(11) DEFAULT NULL,
  `donna_item_master_id` varchar(255) NOT NULL,
  `invalid` tinyint(1) NOT NULL,
  `invalid_subject` varchar(255) DEFAULT NULL,
  `shiping_classification` varchar(255) NOT NULL,
  `payment_methods` varchar(255) NOT NULL,
  `shiping_price_intax` int(11) NOT NULL,
  `shiping_datetime` int(11) NOT NULL,
  `shiping_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `donna_report_days`
--

DROP TABLE IF EXISTS `donna_report_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donna_report_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buying_id` int(11) DEFAULT NULL,
  `supplier_classification` varchar(255) NOT NULL,
  `item_code` varchar(255) NOT NULL,
  `options_color` varchar(255) NOT NULL,
  `options_size` varchar(255) NOT NULL,
  `selling_price` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `credit_card_name` varchar(255) NOT NULL,
  `sale_date` datetime NOT NULL,
  `returned_date` date DEFAULT NULL,
  `cancel_date` date DEFAULT NULL,
  `add_users_id` int(11) NOT NULL,
  `lastupdate_users_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `donna_suppliers`
--

DROP TABLE IF EXISTS `donna_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donna_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_kana` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `main_tel` varchar(255) NOT NULL,
  `main_fax` varchar(255) NOT NULL,
  `main_mail` varchar(255) NOT NULL,
  `post_code` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forest_mailtemplates`
--

DROP TABLE IF EXISTS `forest_mailtemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forest_mailtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `choices1` varchar(255) NOT NULL,
  `choices2` varchar(255) NOT NULL,
  `choices3` varchar(255) NOT NULL,
  `choices4` varchar(255) NOT NULL,
  `choices5` varchar(255) NOT NULL,
  `choices6` varchar(255) NOT NULL,
  `choices7` varchar(255) NOT NULL,
  `choices8` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forest_rb_torihikimeisais`
--

DROP TABLE IF EXISTS `forest_rb_torihikimeisais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forest_rb_torihikimeisais` (
  `id` varchar(255) NOT NULL,
  `sort_number` int(11) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(255) NOT NULL COMMENT '口座番号',
  `dates` int(11) NOT NULL COMMENT '取引日',
  `banking` int(11) NOT NULL COMMENT '入出金(円)',
  `balance` int(11) NOT NULL COMMENT '残高(円)',
  `content` varchar(255) NOT NULL COMMENT '入出金先内容',
  `approval_number` varchar(255) DEFAULT NULL COMMENT '承認番号',
  `forest_rb_visadebitmeisais_id` varchar(255) DEFAULT NULL,
  `memo` text,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`sort_number`),
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forest_rb_visadebitmeisais`
--

DROP TABLE IF EXISTS `forest_rb_visadebitmeisais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forest_rb_visadebitmeisais` (
  `id` varchar(255) NOT NULL COMMENT 'VISA照会番号',
  `sort_number` int(11) NOT NULL AUTO_INCREMENT,
  `dates` int(11) NOT NULL COMMENT 'ご利用日',
  `utilization` varchar(255) NOT NULL COMMENT 'ご利用先',
  `amount` int(11) NOT NULL COMMENT 'ご利用金額（円）',
  `local_amount` int(11) DEFAULT NULL COMMENT '現地通貨額',
  `abbreviation` varchar(255) NOT NULL COMMENT '通貨略称',
  `rate` varchar(255) NOT NULL COMMENT '換算レート',
  `using_local` varchar(255) NOT NULL COMMENT '使用地域',
  `approval_number` varchar(255) NOT NULL COMMENT '承認番号',
  `rakuten_bank_point` int(11) DEFAULT NULL COMMENT '楽天銀行ポイント',
  `rakuten_super_points` int(11) DEFAULT NULL COMMENT '楽天スーパーポイント（ポイント獲得）',
  `state_points` int(11) DEFAULT NULL COMMENT '（ポイント状態）',
  `processing_date_points` int(11) DEFAULT NULL COMMENT '（ポイント処理日）',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`sort_number`),
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gmarket_item_masters`
--

DROP TABLE IF EXISTS `gmarket_item_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gmarket_item_masters` (
  `id` varchar(255) NOT NULL,
  `gmarket_item_main_id` varchar(255) NOT NULL,
  `options_size_name` varchar(255) DEFAULT NULL,
  `options_color_name` varchar(255) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gmarket_orders`
--

DROP TABLE IF EXISTS `gmarket_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gmarket_orders` (
  `id` varchar(255) NOT NULL COMMENT '注文番号',
  `delivery_status` varchar(255) NOT NULL,
  `cart_number` varchar(255) NOT NULL,
  `shipping_company` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `shipping_date` varchar(255) NOT NULL,
  `order_datetime` datetime NOT NULL,
  `payment_day` datetime NOT NULL,
  `desired_delivery` varchar(255) DEFAULT NULL,
  `shipping_scheduledate` varchar(255) DEFAULT NULL,
  `delivery_completion_date` varchar(255) DEFAULT NULL,
  `shipping_method` varchar(255) NOT NULL,
  `item_number` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `optional_information` varchar(255) DEFAULT NULL,
  `option_code` varchar(255) DEFAULT NULL,
  `bonus` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `recipient_name_kana` varchar(255) NOT NULL,
  `recipient_phone` varchar(255) NOT NULL,
  `recipient_mobile_phone` varchar(255) NOT NULL,
  `recipient_address` varchar(255) NOT NULL,
  `recipient_postcode` varchar(255) NOT NULL,
  `recipient_state` varchar(255) NOT NULL,
  `shipping_payment` varchar(255) NOT NULL,
  `settlement_site` varchar(255) NOT NULL,
  `currency` varchar(255) NOT NULL,
  `buyer_settlement_amount` int(11) NOT NULL,
  `selling_price` int(11) NOT NULL,
  `discount` int(11) NOT NULL,
  `total_order_amount` int(11) DEFAULT NULL,
  `total_cost` int(11) NOT NULL,
  `buyer_name` varchar(255) NOT NULL,
  `buyer_name_kana` varchar(255) NOT NULL,
  `buyer_note` varchar(255) NOT NULL,
  `buyer_phone` varchar(255) NOT NULL,
  `buyer_mobile_phone` varchar(255) NOT NULL,
  `buyer_email` varchar(255) NOT NULL,
  `sellers_item_code` varchar(255) NOT NULL,
  `jan_code` varchar(255) NOT NULL,
  `standard_number` varchar(255) NOT NULL,
  `gift` varchar(255) NOT NULL,
  `size_name` varchar(255) NOT NULL,
  `color_name` varchar(255) NOT NULL,
  `item_master_code` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gmarket_orders_options`
--

DROP TABLE IF EXISTS `gmarket_orders_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gmarket_orders_options` (
  `id` varchar(255) NOT NULL,
  `ne_sales_buyer_id` varchar(255) DEFAULT NULL,
  `order_date` varchar(255) NOT NULL,
  `order_time` varchar(255) NOT NULL,
  `buyer_phone1` varchar(255) NOT NULL,
  `buyer_phone2` varchar(255) NOT NULL,
  `buyer_phone3` varchar(255) NOT NULL,
  `recipient_postcode1` varchar(255) NOT NULL,
  `recipient_postcode2` varchar(255) NOT NULL,
  `recipient_phone1` varchar(255) NOT NULL,
  `recipient_phone2` varchar(255) NOT NULL,
  `recipient_phone3` varchar(255) NOT NULL,
  `used_points` int(11) NOT NULL,
  `cart_total_buyer_settlement_amount` int(11) DEFAULT NULL,
  `cart_total_order_amount` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `complement` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `im_item_deliveries`
--

DROP TABLE IF EXISTS `im_item_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `im_item_deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` varchar(255) NOT NULL,
  `delivery_datetime` int(11) NOT NULL,
  `classification` varchar(255) NOT NULL,
  `im_item_master_id` varchar(255) NOT NULL,
  `cost_extax` int(11) NOT NULL,
  `price_extax` int(11) NOT NULL,
  `tax_price` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `contact` int(11) NOT NULL,
  `note` text NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `im_item_masters`
--

DROP TABLE IF EXISTS `im_item_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `im_item_masters` (
  `id` varchar(255) NOT NULL,
  `im_item_main_id` varchar(255) NOT NULL,
  `stock` int(11) NOT NULL,
  `cost` int(11) NOT NULL,
  `selling_price` int(11) NOT NULL,
  `stock_constant` int(11) NOT NULL,
  `stock_variable` int(11) NOT NULL,
  `yet_arrived` int(11) NOT NULL,
  `order_number` int(11) NOT NULL COMMENT '並び順No',
  `last_reservation` int(11) NOT NULL,
  `last_receiving` int(11) NOT NULL,
  `last_selling` int(11) NOT NULL,
  `last_shiping` int(11) NOT NULL,
  `last_updater` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ne_item_stocks`
--

DROP TABLE IF EXISTS `ne_item_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ne_item_stocks` (
  `id` varchar(255) NOT NULL,
  `item_master_name` varchar(255) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `provision_quantity` int(11) NOT NULL,
  `free_stock_quantity` int(11) NOT NULL,
  `reservation_quantity` int(11) NOT NULL,
  `provision_reservation_quantity` int(11) NOT NULL,
  `free_reservation_quantity` int(11) NOT NULL,
  `defective_quantity` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ne_sales_buyers`
--

DROP TABLE IF EXISTS `ne_sales_buyers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ne_sales_buyers` (
  `id` int(11) NOT NULL,
  `store_name` varchar(255) NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `order_datetime` datetime NOT NULL,
  `import_datetime` datetime NOT NULL,
  `check_order` varchar(255) NOT NULL,
  `check_order_person` varchar(255) NOT NULL,
  `confirmation_check` varchar(255) NOT NULL,
  `order_cancellation_datetime` datetime NOT NULL,
  `order_status` varchar(255) NOT NULL,
  `order_contact` varchar(255) NOT NULL,
  `shipping_method` varchar(255) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `total_amount` int(11) NOT NULL,
  `taxes` int(11) NOT NULL,
  `commission` int(11) NOT NULL,
  `shipping_rates` int(11) NOT NULL,
  `overhead` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `approval_amount` int(11) NOT NULL,
  `remarks` text NOT NULL,
  `deposit_amount` int(11) NOT NULL,
  `deposit_classification` varchar(255) NOT NULL,
  `deposit_day` date NOT NULL,
  `invoice_print_instruction_date` date NOT NULL,
  `Invoice_Issue_date` date NOT NULL,
  `invoice_remarks` text NOT NULL,
  `ship_datetime` datetime NOT NULL,
  `ship_scheduled_date` date NOT NULL,
  `ship_person` varchar(255) NOT NULL,
  `field_workers` text NOT NULL,
  `pick_instruction_content` text NOT NULL,
  `relabeling_datetime` datetime NOT NULL,
  `delivery_date` date NOT NULL,
  `delivery_times` varchar(255) NOT NULL,
  `shipping_document_number` varchar(255) NOT NULL,
  `credit_groups` varchar(255) NOT NULL,
  `credit_holder` varchar(255) NOT NULL,
  `credit_expiration` varchar(255) NOT NULL,
  `credit_authorization_number` varchar(255) NOT NULL,
  `credit_approval_category` varchar(255) NOT NULL,
  `credit_approval_date` date NOT NULL,
  `credit_osori_name` varchar(255) NOT NULL,
  `customer_segments` varchar(255) NOT NULL,
  `customer_code` varchar(255) NOT NULL,
  `buyer_name` varchar(255) NOT NULL,
  `buyer_name_kana` varchar(255) NOT NULL,
  `buyer_zip` varchar(255) NOT NULL,
  `buyer_address1` varchar(255) NOT NULL,
  `buyer_address2` varchar(255) NOT NULL,
  `buyer_phone` varchar(255) NOT NULL,
  `buyer_fax` varchar(255) NOT NULL,
  `buyer_email` varchar(255) NOT NULL,
  `shipping_name` varchar(255) NOT NULL,
  `shipping_name_kana` varchar(255) NOT NULL,
  `shipping_zip` varchar(255) NOT NULL,
  `shipping_address1` varchar(255) NOT NULL,
  `shipping_address2` varchar(255) NOT NULL,
  `shipping_phone` varchar(255) NOT NULL,
  `shipping_fax` varchar(255) NOT NULL,
  `delivery_notes` varchar(255) NOT NULL,
  `my_ship_scheduled_date_buyer` date NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ne_sales_orders`
--

DROP TABLE IF EXISTS `ne_sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ne_sales_orders` (
  `id` varchar(255) NOT NULL,
  `row` int(11) NOT NULL,
  `ne_sales_buyers_id` int(11) NOT NULL,
  `cancel_flag` tinyint(1) NOT NULL,
  `item_full_code` varchar(255) NOT NULL,
  `item_full_name` varchar(255) NOT NULL,
  `order_quantity` int(11) NOT NULL,
  `item_price` int(11) NOT NULL,
  `hanging_ratio` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL,
  `item_options` varchar(255) NOT NULL,
  `reserves` int(11) NOT NULL,
  `reserves_date` datetime NOT NULL,
  `my_ship_scheduled_date_order` date NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `newprice`
--

DROP TABLE IF EXISTS `newprice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newprice` (
  `daihyo_syohin_code` varchar(50) NOT NULL DEFAULT '',
  `oldprice` int(11) DEFAULT NULL,
  `newprice` int(11) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_registration_logs`
--

DROP TABLE IF EXISTS `product_registration_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_registration_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `contents` varchar(255) NOT NULL,
  `main_id` varchar(255) NOT NULL,
  `date_added` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_added` (`date_added`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sagawa_b_c_integrations`
--

DROP TABLE IF EXISTS `sagawa_b_c_integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sagawa_b_c_integrations` (
  `id` varchar(255) NOT NULL,
  `shipping_date` date NOT NULL,
  `contact_no` varchar(255) NOT NULL,
  `clients` varchar(255) NOT NULL,
  `client_no` varchar(255) NOT NULL,
  `control_no` varchar(255) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `carry` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `delivery_price` int(11) NOT NULL,
  `delivery_fee` int(11) NOT NULL,
  `cards_fee` int(11) NOT NULL,
  `stamps_fee` int(11) NOT NULL,
  `fares_sign` varchar(255) NOT NULL,
  `fare` int(11) NOT NULL,
  `insurance_fee` int(11) NOT NULL,
  `transit_fee` int(11) NOT NULL,
  `various_fee` int(11) NOT NULL,
  `total_fare` int(11) NOT NULL,
  `payment_types` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `collection_date` date NOT NULL,
  `payment_date` date NOT NULL,
  `tr_contact_no` varchar(255) NOT NULL,
  `original_comment` text NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_201_order_daily`
--

DROP TABLE IF EXISTS `tb_201_order_daily`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_201_order_daily` (
  `年月日` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `年月` varchar(6) NOT NULL DEFAULT '',
  `年` int(11) NOT NULL DEFAULT '0',
  `月` int(11) NOT NULL DEFAULT '0',
  `入荷個数` int(11) NOT NULL DEFAULT '0',
  `入荷金額` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`年月日`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_201_order_shipping`
--

DROP TABLE IF EXISTS `tb_201_order_shipping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_201_order_shipping` (
  `年月日` date NOT NULL DEFAULT '0000-00-00',
  `年月` varchar(6) NOT NULL DEFAULT '000000',
  `年` int(11) NOT NULL DEFAULT '0',
  `月` int(11) NOT NULL DEFAULT '0',
  `入荷担当者` varchar(255) NOT NULL,
  `入荷秒` int(11) NOT NULL DEFAULT '0',
  `入荷時間` double(6,1) NOT NULL DEFAULT '0.0',
  `出荷担当者` varchar(255) NOT NULL,
  `出荷秒` int(11) NOT NULL DEFAULT '0',
  `出荷時間` double(6,1) NOT NULL DEFAULT '0.0',
  `総秒` int(11) NOT NULL DEFAULT '0',
  `総時間` double(6,1) NOT NULL DEFAULT '0.0',
  `出荷明細数` int(11) NOT NULL DEFAULT '0',
  `出荷伝票数` int(11) NOT NULL DEFAULT '0',
  `出荷金額` int(11) NOT NULL DEFAULT '0',
  `入荷個数` int(11) NOT NULL DEFAULT '0',
  `入荷金額` int(11) NOT NULL DEFAULT '0',
  `出荷明細速度` double(6,1) NOT NULL DEFAULT '0.0',
  `出荷伝票速度` double(6,1) NOT NULL DEFAULT '0.0',
  `出荷金額速度` double(11,1) NOT NULL DEFAULT '0.0',
  `入荷個数速度` double(6,1) NOT NULL DEFAULT '0.0',
  `入荷金額速度` double(11,1) NOT NULL DEFAULT '0.0',
  `やり残しコメント` varchar(255) NOT NULL,
  `備考` varchar(255) NOT NULL,
  PRIMARY KEY (`年月日`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_201_order_shipping_esc`
--

DROP TABLE IF EXISTS `tb_201_order_shipping_esc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_201_order_shipping_esc` (
  `年月日` date NOT NULL DEFAULT '0000-00-00',
  `入荷秒` int(11) NOT NULL DEFAULT '0',
  `入荷時間` double(6,1) NOT NULL DEFAULT '0.0',
  `入荷担当者` varchar(255) NOT NULL,
  `出荷秒` int(11) NOT NULL DEFAULT '0',
  `出荷時間` double(6,1) NOT NULL DEFAULT '0.0',
  `出荷担当者` varchar(255) NOT NULL,
  `総秒` int(11) NOT NULL DEFAULT '0',
  `総時間` double(6,1) NOT NULL DEFAULT '0.0',
  `やり残しコメント` varchar(255) NOT NULL,
  `備考` varchar(255) NOT NULL,
  PRIMARY KEY (`年月日`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_201_order_worker`
--

DROP TABLE IF EXISTS `tb_201_order_worker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_201_order_worker` (
  `年` int(11) DEFAULT NULL,
  `月` int(11) DEFAULT NULL,
  `日` int(11) DEFAULT NULL,
  `入荷担当者` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_201_order_worktime`
--

DROP TABLE IF EXISTS `tb_201_order_worktime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_201_order_worktime` (
  `年月日` date NOT NULL DEFAULT '0000-00-00',
  `入荷秒` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`年月日`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_201_shipping_worker`
--

DROP TABLE IF EXISTS `tb_201_shipping_worker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_201_shipping_worker` (
  `年` int(11) DEFAULT NULL,
  `月` int(11) DEFAULT NULL,
  `日` int(11) DEFAULT NULL,
  `出荷担当者` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_201_shipping_worktime`
--

DROP TABLE IF EXISTS `tb_201_shipping_worktime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_201_shipping_worktime` (
  `年月日` date NOT NULL DEFAULT '0000-00-00',
  `出荷秒` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`年月日`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_410_work_summary`
--

DROP TABLE IF EXISTS `tb_410_work_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_410_work_summary` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `job_description` varchar(255) NOT NULL DEFAULT '',
  `users_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `work_seconds` int(11) DEFAULT NULL,
  `work_seconds_net` int(11) DEFAULT NULL,
  `break_seconds` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_access_analyze`
--

DROP TABLE IF EXISTS `tb_access_analyze`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_access_analyze` (
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `daihyo_syohin_name` varchar(255) DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `access_nums` int(11) NOT NULL DEFAULT '0',
  `access_peoples` int(11) NOT NULL DEFAULT '0',
  `sales_nums` int(11) NOT NULL DEFAULT '0',
  `sales_rate` float NOT NULL DEFAULT '0',
  `unit_price` int(11) NOT NULL DEFAULT '0',
  `rakuten_baika_tanka` int(11) NOT NULL DEFAULT '0',
  `review_point_ave` float(2,1) NOT NULL DEFAULT '0.0',
  `sales_amount` int(11) NOT NULL DEFAULT '0',
  `access_nums_sales_rate` float NOT NULL DEFAULT '0',
  `sales_rate_unit_price` float NOT NULL DEFAULT '0',
  `access_nums_unit_price` float NOT NULL DEFAULT '0',
  `粗利率` float NOT NULL DEFAULT '0',
  `楽天カテゴリ１` varchar(255) NOT NULL,
  `楽天カテゴリ１第１カテゴリ` varchar(255) NOT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_access_analyze_petitprice`
--

DROP TABLE IF EXISTS `tb_access_analyze_petitprice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_access_analyze_petitprice` (
  `no` int(11) NOT NULL DEFAULT '0',
  `id` varchar(30) DEFAULT NULL,
  `img_url` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `price` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_access_analyze_ranking`
--

DROP TABLE IF EXISTS `tb_access_analyze_ranking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_access_analyze_ranking` (
  `no` int(11) NOT NULL DEFAULT '0',
  `id` varchar(30) DEFAULT NULL,
  `img_url` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_amazon_keyword`
--

DROP TABLE IF EXISTS `tb_amazon_keyword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_amazon_keyword` (
  `ne_syohin_code` varchar(100) NOT NULL DEFAULT '',
  `検索キーワード` varchar(100) DEFAULT NULL,
  `推奨ブラウズノード` varchar(100) DEFAULT NULL,
  `親子` varchar(100) DEFAULT NULL,
  `商品タイプ` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ne_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_amazon_keyword2`
--

DROP TABLE IF EXISTS `tb_amazon_keyword2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_amazon_keyword2` (
  `NEディレクトリID` varchar(255) DEFAULT NULL,
  `検索キーワード1` varchar(255) DEFAULT NULL,
  `商品タイプ` varchar(255) DEFAULT NULL,
  `推奨ブラウズノード1` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_amazon_raku`
--

DROP TABLE IF EXISTS `tb_amazon_raku`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_amazon_raku` (
  `楽天ディレクトリID` varchar(100) NOT NULL DEFAULT '',
  `推奨ブラウズノード1` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`楽天ディレクトリID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_amazoninfo_detail`
--

DROP TABLE IF EXISTS `tb_amazoninfo_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_amazoninfo_detail` (
  `item_sku` varchar(255) NOT NULL DEFAULT '',
  `item_name` varchar(255) DEFAULT NULL,
  `brand_name` varchar(255) DEFAULT NULL,
  `product_subtype` varchar(255) DEFAULT NULL,
  `part_number` varchar(255) DEFAULT NULL,
  `product_description` mediumtext,
  `quantity` varchar(255) DEFAULT NULL,
  `fulfillment_latency` varchar(255) DEFAULT NULL,
  `condition_type` varchar(255) DEFAULT NULL,
  `standard_price` varchar(255) DEFAULT NULL,
  `currency` varchar(255) DEFAULT NULL,
  `missing_keyset_reason` varchar(255) DEFAULT NULL,
  `bullet_point1` varchar(255) DEFAULT NULL,
  `generic_keywords1` varchar(255) DEFAULT NULL,
  `generic_keywords2` varchar(255) DEFAULT NULL,
  `recommended_browse_nodes1` varchar(255) DEFAULT NULL,
  `main_image_url` varchar(255) DEFAULT NULL,
  `swatch_image_url` varchar(255) DEFAULT NULL,
  `other_image_url1` varchar(255) DEFAULT NULL,
  `other_image_url2` varchar(255) DEFAULT NULL,
  `other_image_url3` varchar(255) DEFAULT NULL,
  `other_image_url4` varchar(255) DEFAULT NULL,
  `other_image_url5` varchar(255) DEFAULT NULL,
  `other_image_url6` varchar(255) DEFAULT NULL,
  `other_image_url7` varchar(255) DEFAULT NULL,
  `other_image_url8` varchar(255) DEFAULT NULL,
  `parent_child` varchar(255) DEFAULT NULL,
  `parent_sku` varchar(255) DEFAULT NULL,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `relationship_type` varchar(255) DEFAULT NULL,
  `variation_theme` varchar(255) DEFAULT NULL,
  `size_name` varchar(255) DEFAULT NULL,
  `color_name` varchar(255) DEFAULT NULL,
  `department_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`item_sku`),
  KEY `parent_sku` (`parent_sku`) USING BTREE,
  KEY `daihyo_syohin_code` (`daihyo_syohin_code`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_amazoninfomation`
--

DROP TABLE IF EXISTS `tb_amazoninfomation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_amazoninfomation` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `amazon_title` varchar(255) DEFAULT NULL,
  `registration_flg` tinyint(1) NOT NULL DEFAULT '-1' COMMENT ' 登録フラグ',
  `original_price` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `org_pic_num` int(2) NOT NULL DEFAULT '0' COMMENT '独自画像枚数',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_bidders_au_main`
--

DROP TABLE IF EXISTS `tb_bidders_au_main`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_bidders_au_main` (
  `伝票番号` varchar(255) NOT NULL,
  `ピッキング指示内容` varchar(255) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `出荷確定日` date DEFAULT NULL,
  `納品書印刷指示日` varchar(255) DEFAULT NULL,
  `納品書発行日` varchar(255) DEFAULT NULL,
  `状態` varchar(255) DEFAULT NULL,
  `受注番号` varchar(255) DEFAULT NULL,
  `店舗` varchar(255) DEFAULT NULL,
  `受注担当者` varchar(255) DEFAULT NULL,
  `購入者名` varchar(255) DEFAULT NULL,
  `商品計` varchar(255) DEFAULT NULL,
  `税金` varchar(255) DEFAULT NULL,
  `発送料` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `他費用` varchar(255) DEFAULT NULL,
  `ポイント数` varchar(255) DEFAULT NULL,
  `総合計` varchar(255) DEFAULT NULL,
  `送り先名` varchar(255) DEFAULT NULL,
  `送り先〒` varchar(255) DEFAULT NULL,
  `送り先住所` varchar(255) DEFAULT NULL,
  `発送方法` varchar(255) DEFAULT NULL,
  `支払方法` varchar(255) DEFAULT NULL,
  `発送伝票番号` varchar(255) DEFAULT NULL,
  `備考` text,
  `重要` varchar(255) DEFAULT NULL,
  `重要チェック者` varchar(255) DEFAULT NULL,
  `受注分類タグ` varchar(255) DEFAULT NULL,
  `課金確定チェック` int(11) DEFAULT '0',
  `メモ欄` text,
  PRIMARY KEY (`伝票番号`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_bidders_category`
--

DROP TABLE IF EXISTS `tb_bidders_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_bidders_category` (
  `bidders_category_id` varchar(255) NOT NULL,
  `フィールド1` varchar(255) DEFAULT NULL,
  `フィールド2` varchar(255) DEFAULT NULL,
  `フィールド3` varchar(255) DEFAULT NULL,
  `フィールド4` varchar(255) DEFAULT NULL,
  `フィールド5` varchar(255) DEFAULT NULL,
  `フィールド6` varchar(255) DEFAULT NULL,
  `フィールド7` varchar(255) DEFAULT NULL,
  `フィールド8` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`bidders_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_bidders_folog`
--

DROP TABLE IF EXISTS `tb_bidders_folog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_bidders_folog` (
  `Code` varchar(255) NOT NULL DEFAULT '',
  `SeqExhibitId` varchar(255) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Price` varchar(255) DEFAULT NULL,
  `StockNum` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_bidders_ranking`
--

DROP TABLE IF EXISTS `tb_bidders_ranking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_bidders_ranking` (
  `item_code` varchar(20) NOT NULL,
  `check_date` date NOT NULL,
  `ranking` int(10) unsigned DEFAULT NULL,
  `sales_volume` int(11) DEFAULT NULL,
  `item_title` varchar(255) DEFAULT NULL,
  `item_seller` varchar(255) DEFAULT NULL,
  `item_price` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`item_code`,`check_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_biddersinfomation`
--

DROP TABLE IF EXISTS `tb_biddersinfomation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_biddersinfomation` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `front_title` varchar(255) DEFAULT NULL,
  `bidders_title` varchar(255) DEFAULT NULL,
  `registration_flg` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '登録フラグ',
  `original_price` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `biddersmdescription` text,
  `biddersmdetaildescription` text,
  `pricelabel1` varchar(100) DEFAULT NULL,
  `pricelabel2` varchar(100) DEFAULT NULL,
  `rand_no_seq` int(10) unsigned DEFAULT NULL,
  `rand_link1_no` int(10) unsigned DEFAULT NULL,
  `rand_link2_no` int(10) unsigned DEFAULT NULL,
  `search_keyword1` varchar(255) DEFAULT NULL,
  `search_keyword2` varchar(255) DEFAULT '春夏秋冬サマー',
  `search_keyword3` varchar(255) DEFAULT '激安バーゲンセール',
  `bidders_pc_caption` text,
  `bidders_price` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_biddersitemoptions`
--

DROP TABLE IF EXISTS `tb_biddersitemoptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_biddersitemoptions` (
  `daihyo_syohin_code` varchar(255) NOT NULL,
  `SkuOptionValueCol` varchar(255) DEFAULT NULL,
  `SkuOptionTypeCol` varchar(255) DEFAULT NULL,
  `SkuOptionValueRow` varchar(255) DEFAULT NULL,
  `SkuOptionTypeRow` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_biddersmobilelink`
--

DROP TABLE IF EXISTS `tb_biddersmobilelink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_biddersmobilelink` (
  `cd` int(10) unsigned NOT NULL,
  `linkaddress` varchar(255) DEFAULT NULL,
  `linkname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_calendar`
--

DROP TABLE IF EXISTS `tb_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_calendar` (
  `calendar_date` date NOT NULL,
  `workingday` int(11) DEFAULT NULL,
  PRIMARY KEY (`calendar_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_chat`
--

DROP TABLE IF EXISTS `tb_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_chat` (
  `chat_cd` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sent_datetime` datetime DEFAULT NULL,
  `destination` int(10) unsigned DEFAULT NULL,
  `sender` int(10) unsigned DEFAULT NULL,
  `body` text,
  PRIMARY KEY (`chat_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_croozmallcategory_dl`
--

DROP TABLE IF EXISTS `tb_croozmallcategory_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_croozmallcategory_dl` (
  `コントロールカラム` varchar(10) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(255) NOT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `表示先カテゴリ` varchar(255) NOT NULL,
  `優先度` int(10) unsigned DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `1ページ複数形式` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQUE` (`商品管理番号（商品URL）`,`表示先カテゴリ`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_croozmallinformation`
--

DROP TABLE IF EXISTS `tb_croozmallinformation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_croozmallinformation` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `registration_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '登録フラグ',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `mobilebody` varchar(1000) DEFAULT NULL,
  `pcbody` varchar(1000) DEFAULT NULL,
  `pcsubbody` varchar(1000) DEFAULT NULL,
  `price` int(10) unsigned DEFAULT NULL,
  `update_column` varchar(45) DEFAULT NULL,
  `picmobile01` varchar(255) DEFAULT NULL,
  `picmobile02` varchar(255) DEFAULT NULL,
  `picmobile03` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_dcode_denpyo_temp`
--

DROP TABLE IF EXISTS `tb_dcode_denpyo_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_dcode_denpyo_temp` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `伝票番号` int(11) NOT NULL,
  PRIMARY KEY (`daihyo_syohin_code`,`伝票番号`),
  KEY `伝票番号` (`伝票番号`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_dcode_tel_temp`
--

DROP TABLE IF EXISTS `tb_dcode_tel_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_dcode_tel_temp` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `tel` varchar(30) NOT NULL,
  PRIMARY KEY (`daihyo_syohin_code`,`tel`),
  KEY `tel` (`tel`) USING BTREE,
  KEY `daihyo_syohin_code` (`daihyo_syohin_code`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_dcode_temp`
--

DROP TABLE IF EXISTS `tb_dcode_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_dcode_temp` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_delete_image_check`
--

DROP TABLE IF EXISTS `tb_delete_image_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_delete_image_check` (
  `画像パス` varchar(255) NOT NULL,
  PRIMARY KEY (`画像パス`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_delivery_method`
--

DROP TABLE IF EXISTS `tb_delivery_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_delivery_method` (
  `delivery_id` int(11) NOT NULL DEFAULT '0',
  `delivery_name` varchar(50) DEFAULT NULL,
  `delivery_cost` int(11) DEFAULT '0',
  PRIMARY KEY (`delivery_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_dena_log_dl`
--

DROP TABLE IF EXISTS `tb_dena_log_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_dena_log_dl` (
  `取引No` varchar(255) DEFAULT NULL,
  `管理No` varchar(255) DEFAULT NULL,
  `ロットNo` varchar(255) DEFAULT NULL,
  `タイトル` varchar(255) DEFAULT NULL,
  `落札価格` varchar(255) DEFAULT NULL,
  `個数` varchar(255) DEFAULT NULL,
  `落札日` varchar(255) DEFAULT NULL,
  `ニックネーム` varchar(255) DEFAULT NULL,
  `Eメールアドレス` varchar(255) DEFAULT NULL,
  `【取引管理】名前` varchar(255) DEFAULT NULL,
  `【取引管理】住所` varchar(255) DEFAULT NULL,
  `【取引管理】電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】名前` varchar(255) DEFAULT NULL,
  `【取引ナビ】住所` varchar(255) DEFAULT NULL,
  `【取引ナビ】電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】希望取引方法` varchar(255) DEFAULT NULL,
  `【取引ナビ】コメント` varchar(255) DEFAULT NULL,
  `【出品時設定】希望取引方法` varchar(255) DEFAULT NULL,
  `【取引管理】実際の取引方法` varchar(255) DEFAULT NULL,
  `連絡済み` varchar(255) DEFAULT NULL,
  `連絡日` varchar(255) DEFAULT NULL,
  `入金確認済み` varchar(255) DEFAULT NULL,
  `入金確認日` varchar(255) DEFAULT NULL,
  `発送済み` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `販売単価` varchar(255) DEFAULT NULL,
  `販売個数` varchar(255) DEFAULT NULL,
  `小計` varchar(255) DEFAULT NULL,
  `消費税` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `送料` varchar(255) DEFAULT NULL,
  `請求金額` varchar(255) DEFAULT NULL,
  `取引メモ` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先氏名` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先住所` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者カナ` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者日中連絡先` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者メールアドレス` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先カナ` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先日中連絡先` varchar(255) DEFAULT NULL,
  `販売総額` varchar(255) DEFAULT NULL,
  `販売総数` varchar(255) DEFAULT NULL,
  `消費税区分` varchar(255) DEFAULT NULL,
  `キャンセル` varchar(255) DEFAULT NULL,
  `アイテムオプション` varchar(255) DEFAULT NULL,
  `(旧)取引No` varchar(255) DEFAULT NULL,
  `カード種類` varchar(255) DEFAULT NULL,
  `カード番号` varchar(255) DEFAULT NULL,
  `有効期限・年` varchar(255) DEFAULT NULL,
  `有効期限・月` varchar(255) DEFAULT NULL,
  `カード名義人` varchar(255) DEFAULT NULL,
  `名義人生年月日` varchar(255) DEFAULT NULL,
  `オークションタイプ` varchar(255) DEFAULT NULL,
  `ホームサイト` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `総合計` varchar(255) DEFAULT NULL,
  `ポイント利用分` varchar(255) DEFAULT NULL,
  `利用キャンセル状況` varchar(255) DEFAULT NULL,
  `付与ポイント数` varchar(255) DEFAULT NULL,
  `CB原資付与ポイント数` varchar(255) DEFAULT NULL,
  `付与ポイント確定(予定)日` varchar(255) DEFAULT NULL,
  `付与ポイント状況` varchar(255) DEFAULT NULL,
  `取引オプション` varchar(255) DEFAULT NULL,
  `クレジットカードオプション` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_diff`
--

DROP TABLE IF EXISTS `tb_diff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_diff` (
  `diff_key` varchar(255) NOT NULL DEFAULT '',
  `text1` varchar(255) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `text3` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`diff_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_exclusion`
--

DROP TABLE IF EXISTS `tb_exclusion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_exclusion` (
  `ex_id` int(11) NOT NULL,
  `ex_key` varchar(30) NOT NULL,
  `ex_flg` varchar(255) NOT NULL,
  `ex_desc` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ex_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_for_space_trim`
--

DROP TABLE IF EXISTS `tb_for_space_trim`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_for_space_trim` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `length_before` int(11) NOT NULL DEFAULT '0',
  `length_after` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_free_email`
--

DROP TABLE IF EXISTS `tb_free_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_free_email` (
  `free_email_cd` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `フィールド1` varchar(255) DEFAULT '\0',
  `フィールド2` varchar(255) DEFAULT '\0',
  `フィールド3` varchar(255) DEFAULT '\0',
  `フィールド4` varchar(255) DEFAULT '\0',
  `フィールド5` varchar(255) DEFAULT '\0',
  `フィールド6` varchar(255) DEFAULT '\0',
  `title` varchar(255) DEFAULT NULL,
  `body` text,
  `フィールド7` varchar(255) DEFAULT '\0',
  `フィールド8` varchar(255) DEFAULT '\0',
  PRIMARY KEY (`free_email_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_freestock`
--

DROP TABLE IF EXISTS `tb_freestock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_freestock` (
  `商品コード` varchar(255) NOT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `小売価格` varchar(100) DEFAULT NULL,
  `原価` varchar(100) DEFAULT NULL,
  `在庫数` varchar(100) DEFAULT NULL,
  `在庫金額` varchar(100) DEFAULT NULL,
  `フリー在庫数` int(10) unsigned DEFAULT NULL,
  `フリー在庫金額` varchar(100) DEFAULT NULL,
  `数量` int(10) unsigned DEFAULT NULL,
  `金額` varchar(100) DEFAULT NULL,
  `商品分類タグ` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`商品コード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_gmo_category`
--

DROP TABLE IF EXISTS `tb_gmo_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_gmo_category` (
  `カテゴリコード` varchar(255) NOT NULL,
  `カテゴリ名` varchar(255) DEFAULT NULL,
  `サブカテゴリ名` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`カテゴリコード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_gmo_infomation`
--

DROP TABLE IF EXISTS `tb_gmo_infomation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_gmo_infomation` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `gmo_title` varchar(150) DEFAULT NULL,
  `registration_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '登録フラグ',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `商品説明` varchar(1000) DEFAULT NULL,
  `モバイル商品説明` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_gmo_option_dl`
--

DROP TABLE IF EXISTS `tb_gmo_option_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_gmo_option_dl` (
  `商品特定コード指定` int(10) unsigned DEFAULT NULL,
  `オプション特定コード指定` int(10) unsigned DEFAULT NULL,
  `システム商品コード` varchar(255) DEFAULT NULL,
  `独自商品コード` varchar(255) NOT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `カテゴリ名` varchar(255) DEFAULT NULL,
  `サブカテゴリ名` varchar(255) DEFAULT NULL,
  `オプション独自コード` varchar(255) DEFAULT NULL,
  `オプション１項目` varchar(255) NOT NULL,
  `オプション２項目` varchar(255) NOT NULL,
  `販売価格` int(10) unsigned DEFAULT NULL,
  `数量` int(10) unsigned DEFAULT NULL,
  `JANコード` varchar(255) DEFAULT NULL,
  `item_code` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQUE` (`独自商品コード`,`オプション１項目`,`オプション２項目`) USING BTREE,
  KEY `Index_2` (`item_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_gmo_upload_category`
--

DROP TABLE IF EXISTS `tb_gmo_upload_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_gmo_upload_category` (
  `カテゴリコード` varchar(255) DEFAULT NULL,
  `カテゴリ名` varchar(255) DEFAULT NULL,
  `サブカテゴリ名` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_gmo_upload_dl`
--

DROP TABLE IF EXISTS `tb_gmo_upload_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_gmo_upload_dl` (
  `商品特定コード指定` int(10) unsigned DEFAULT NULL,
  `更新時間フラグ` int(10) unsigned DEFAULT NULL,
  `システム商品コード` varchar(255) DEFAULT NULL,
  `独自商品コード` varchar(255) NOT NULL,
  `カテゴリ名` varchar(255) DEFAULT NULL,
  `サブカテゴリ名` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `重量` int(10) unsigned DEFAULT NULL,
  `販売価格` int(10) unsigned DEFAULT NULL,
  `定価` int(10) unsigned DEFAULT NULL,
  `ポイント` int(10) unsigned DEFAULT NULL,
  `仕入価格` int(10) unsigned DEFAULT NULL,
  `製造元` varchar(255) DEFAULT NULL,
  `原産地` varchar(255) DEFAULT NULL,
  `原産地表示フラグ` int(10) unsigned DEFAULT NULL,
  `数量` int(10) unsigned DEFAULT NULL,
  `数量表示フラグ` int(10) unsigned DEFAULT NULL,
  `最小注文限度数` int(10) unsigned DEFAULT NULL,
  `最大注文限度数` int(10) unsigned DEFAULT NULL,
  `陳列位置` int(10) unsigned DEFAULT NULL,
  `送料個別設定` int(10) unsigned DEFAULT NULL,
  `割引使用フラグ` int(10) unsigned DEFAULT NULL,
  `割引率` int(10) unsigned DEFAULT NULL,
  `割引期間` varchar(255) DEFAULT NULL,
  `商品グループ` varchar(255) DEFAULT NULL,
  `商品検索語` varchar(255) DEFAULT NULL,
  `商品別特殊表示` varchar(255) DEFAULT NULL,
  `オプション１名称` varchar(255) DEFAULT NULL,
  `オプション２名称` varchar(255) DEFAULT NULL,
  `オプショングループ` varchar(255) DEFAULT NULL,
  `拡大画像名` varchar(255) DEFAULT NULL,
  `普通画像名` varchar(255) DEFAULT NULL,
  `縮小画像名` varchar(255) DEFAULT NULL,
  `モバイル画像名` varchar(255) DEFAULT NULL,
  `モバイル商品説明` text,
  `商品説明` text,
  `JANコード` varchar(255) DEFAULT NULL,
  `商品表示可否` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`独自商品コード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ichiba_genre_list`
--

DROP TABLE IF EXISTS `tb_ichiba_genre_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ichiba_genre_list` (
  `ディレクトリID` varchar(255) NOT NULL,
  `パス名` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ディレクトリID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_image_check`
--

DROP TABLE IF EXISTS `tb_image_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_image_check` (
  `mall_kbn` varchar(10) NOT NULL,
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `idx` int(11) NOT NULL COMMENT 'P:1-9 M:10-12',
  `pc_m_kbn` varchar(1) NOT NULL,
  `no` int(11) NOT NULL,
  `picfolder` varchar(30) NOT NULL,
  `picname` varchar(30) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`mall_kbn`,`daihyo_syohin_code`,`idx`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_individualorderhistory`
--

DROP TABLE IF EXISTS `tb_individualorderhistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_individualorderhistory` (
  `発注伝票番号` int(10) unsigned NOT NULL,
  `商品コード` varchar(255) NOT NULL,
  `発注数` int(10) DEFAULT '0',
  `注残計` int(10) DEFAULT '0',
  `予定納期` date DEFAULT NULL,
  `備考` varchar(255) DEFAULT '\0',
  `商品区分` varchar(255) DEFAULT '予約',
  `受注伝票番号` int(10) unsigned DEFAULT NULL,
  `仕入先cd` varchar(255) DEFAULT NULL,
  `商品区分値` varchar(255) DEFAULT '10',
  `発行日` date DEFAULT NULL,
  `option` varchar(255) DEFAULT NULL,
  `regular` int(11) DEFAULT '0',
  `defective` int(11) DEFAULT '0',
  `shortage` int(11) DEFAULT '0',
  `input_regular` int(11) DEFAULT '0',
  `input_defective` int(11) DEFAULT '0',
  `input_shortage` int(11) DEFAULT '0',
  `quantity_price` int(10) DEFAULT '0',
  `input_Quantity` int(10) DEFAULT '0',
  PRIMARY KEY (`発注伝票番号`,`商品コード`),
  KEY `Index_1` (`商品コード`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_infomation`
--

DROP TABLE IF EXISTS `tb_infomation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_infomation` (
  `CD` int(10) unsigned NOT NULL,
  `infomation_value01` text,
  `infomation_value02` text,
  `infomation_value03` text,
  `infomation_value04` text,
  `infomation_value05` text,
  `infomation_value06` text,
  `infomation_value07` text,
  `infomation_value08` text,
  `infomation_value09` text,
  `infomation_value10` text,
  `infomation_value11` text,
  `infomation_value12` text,
  `infomation_value13` text,
  PRIMARY KEY (`CD`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_inventory_interval`
--

DROP TABLE IF EXISTS `tb_inventory_interval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_inventory_interval` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ne_syohin_syohin_code` varchar(255) DEFAULT NULL,
  `sire_code` varchar(4) NOT NULL COMMENT '仕入先コード',
  `order_date` datetime NOT NULL,
  `order_num` int(11) NOT NULL DEFAULT '0',
  `inventory_date` datetime NOT NULL,
  `inventory_num` int(11) NOT NULL DEFAULT '0',
  `inventory_interval` int(11) NOT NULL COMMENT '発注から引き当てまでの日数（２日以上のもの）',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_item_class`
--

DROP TABLE IF EXISTS `tb_item_class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_item_class` (
  `class_cd` int(10) unsigned NOT NULL,
  `class_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`class_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_log`
--

DROP TABLE IF EXISTS `tb_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_log` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PC` varchar(50) DEFAULT NULL,
  `EXEC_TITLE` varchar(100) NOT NULL,
  `EXEC_TIMESTAMP` datetime NOT NULL,
  `LOG_LEVEL` int(11) NOT NULL,
  `LOG_TITLE` varchar(100) NOT NULL,
  `LOG_SUBTITLE1` varchar(50) NOT NULL,
  `LOG_SUBTITLE2` varchar(50) NOT NULL,
  `LOG_SUBTITLE3` varchar(50) NOT NULL,
  `LOG_TIMESTAMP` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `LOG_INTERVAL` int(11) NOT NULL DEFAULT '0',
  `LOG_ELAPSE` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_mainproducts`
--

DROP TABLE IF EXISTS `tb_mainproducts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_mainproducts` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `sire_code` varchar(10) DEFAULT NULL,
  `jan_code` varchar(50) DEFAULT NULL,
  `syohin_kbn` varchar(10) DEFAULT '10',
  `genka_tnk` int(10) unsigned DEFAULT NULL,
  `daihyo_syohin_name` varchar(255) DEFAULT NULL,
  `在庫変動チェックフラグ` tinyint(3) DEFAULT '0',
  `価格非連動チェック` tinyint(3) DEFAULT '0',
  `バリエーション変更チェック` tinyint(3) DEFAULT '0',
  `価格変更チェック` tinyint(3) DEFAULT '0',
  `備考` varchar(255) DEFAULT NULL,
  `楽天削除` tinyint(3) DEFAULT '0',
  `登録日時` datetime DEFAULT NULL,
  `販売開始日` date DEFAULT NULL,
  `送料設定` tinyint(3) DEFAULT '0',
  `入荷予定日` date DEFAULT NULL,
  `入荷アラート日数` int(10) unsigned DEFAULT '0',
  `優先表示修正値` int(10) DEFAULT '0',
  `優先表示順位` int(10) DEFAULT '0',
  `手動ゲリラSALE` tinyint(3) DEFAULT '0',
  `入荷遅延日数` int(10) unsigned DEFAULT '0',
  `総在庫数` int(10) unsigned DEFAULT '0',
  `総在庫金額` int(10) unsigned DEFAULT '0',
  `商品画像P1Cption` varchar(50) DEFAULT NULL,
  `商品画像P2Cption` varchar(50) DEFAULT NULL,
  `商品画像P3Cption` varchar(50) DEFAULT NULL,
  `商品画像P4Cption` varchar(50) DEFAULT NULL,
  `商品画像P5Cption` varchar(50) DEFAULT NULL,
  `商品画像P6Cption` varchar(50) DEFAULT NULL,
  `商品画像P7Cption` varchar(50) DEFAULT NULL,
  `商品画像P8Cption` varchar(50) DEFAULT NULL,
  `商品画像P9Cption` varchar(50) DEFAULT NULL,
  `商品画像P1Adress` varchar(200) DEFAULT NULL,
  `商品画像P2Adress` varchar(200) DEFAULT NULL,
  `商品画像P3Adress` varchar(200) DEFAULT NULL,
  `商品画像P4Adress` varchar(200) DEFAULT NULL,
  `商品画像P5Adress` varchar(200) DEFAULT NULL,
  `商品画像P6Adress` varchar(200) DEFAULT NULL,
  `商品画像P7Adress` varchar(200) DEFAULT NULL,
  `商品画像P8Adress` varchar(200) DEFAULT NULL,
  `商品画像P9Adress` varchar(200) DEFAULT NULL,
  `商品画像M1Caption` varchar(50) DEFAULT NULL,
  `商品画像M2Caption` varchar(50) DEFAULT NULL,
  `商品画像M3Caption` varchar(50) DEFAULT NULL,
  `商品画像M1Adress` varchar(200) DEFAULT NULL,
  `商品画像M2Adress` varchar(200) DEFAULT NULL,
  `商品画像M3Adress` varchar(200) DEFAULT NULL,
  `商品コメントPC` text,
  `一言ポイント` text,
  `補足説明PC` text,
  `必要補足説明` text,
  `B固有必要補足説明` text,
  `R固有必要補足説明` text,
  `NE更新カラム` varchar(10) DEFAULT NULL,
  `GMOタイトル` varchar(255) DEFAULT NULL,
  `サイズについて` text,
  `カラーについて` text,
  `素材について` text,
  `ブランドについて` text,
  `使用上の注意` text,
  `実勢価格` int(10) unsigned DEFAULT NULL,
  `横軸項目名` varchar(50) DEFAULT NULL,
  `縦軸項目名` varchar(50) DEFAULT NULL,
  `NEディレクトリID` varchar(50) DEFAULT NULL,
  `YAHOOディレクトリID` varchar(20) DEFAULT NULL,
  `標準出荷日数` int(10) unsigned DEFAULT '0',
  `stockreview` tinyint(3) DEFAULT '0',
  `stockinfomation` varchar(255) DEFAULT NULL,
  `stockreviewinfomation` varchar(255) DEFAULT NULL,
  `productchoiceitems_count` int(10) unsigned DEFAULT '0',
  `picnameP1` varchar(255) DEFAULT NULL,
  `picnameP2` varchar(255) DEFAULT NULL,
  `picnameP3` varchar(255) DEFAULT NULL,
  `picnameP4` varchar(255) DEFAULT NULL,
  `picnameP5` varchar(255) DEFAULT NULL,
  `picnameP6` varchar(255) DEFAULT NULL,
  `picnameP7` varchar(255) DEFAULT NULL,
  `picnameP8` varchar(255) DEFAULT NULL,
  `picnameP9` varchar(255) DEFAULT NULL,
  `picnameM1` varchar(255) DEFAULT NULL,
  `picnameM2` varchar(255) DEFAULT NULL,
  `picnameM3` varchar(255) DEFAULT NULL,
  `picfolderP1` varchar(255) DEFAULT NULL,
  `picfolderP2` varchar(255) DEFAULT NULL,
  `picfolderP3` varchar(255) DEFAULT NULL,
  `picfolderP4` varchar(255) DEFAULT NULL,
  `picfolderP5` varchar(255) DEFAULT NULL,
  `picfolderP6` varchar(255) DEFAULT NULL,
  `picfolderP7` varchar(255) DEFAULT NULL,
  `picfolderP8` varchar(255) DEFAULT NULL,
  `picfolderP9` varchar(255) DEFAULT NULL,
  `picfolderM1` varchar(255) DEFAULT NULL,
  `picfolderM2` varchar(255) DEFAULT NULL,
  `picfolderM3` varchar(255) DEFAULT NULL,
  `person` varchar(255) DEFAULT NULL,
  `check_price` int(10) unsigned DEFAULT NULL,
  `weight` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '重量(g)',
  `additional_cost` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '仕入付加費用',
  `pic_check_datetime` datetime DEFAULT NULL COMMENT '画像チェック日時',
  `pic_check_datetime_sort` datetime NOT NULL,
  `notfound_image_no_rakuten` int(2) NOT NULL DEFAULT '0',
  `notfound_image_no_dena` int(2) NOT NULL DEFAULT '0',
  `dummy` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`),
  KEY `Index_5` (`登録日時`) USING BTREE,
  KEY `pic_check_datetime` (`pic_check_datetime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_mainproducts_cal`
--

DROP TABLE IF EXISTS `tb_mainproducts_cal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_mainproducts_cal` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `endofavailability` datetime DEFAULT NULL,
  `deliverycode` int(11) DEFAULT '4',
  `genka_tnk_ave` int(11) DEFAULT NULL,
  `baika_tnk` int(10) unsigned DEFAULT NULL,
  `sunfactoryset` date DEFAULT NULL,
  `list_some_instant_delivery` text,
  `priority` int(10) unsigned DEFAULT '0',
  `earliest_order_date` date DEFAULT NULL,
  `delay_days` int(11) DEFAULT NULL,
  `visible_flg` int(10) unsigned DEFAULT '1',
  `sales_volume` int(10) unsigned DEFAULT NULL,
  `makeshop_Registration_flug` tinyint(1) DEFAULT '0',
  `rakuten_Registration_flug` tinyint(1) DEFAULT '0',
  `croozmall_Registration_flug` tinyint(11) DEFAULT '0',
  `amazon_registration_flug` tinyint(4) DEFAULT '0',
  `annual_sales` int(10) unsigned DEFAULT '0',
  `rakuten_Registration_flug_date` date DEFAULT NULL,
  `setnum` int(10) unsigned DEFAULT NULL,
  `rakutencategory_tep` varchar(255) DEFAULT NULL,
  `being_num` int(10) unsigned DEFAULT NULL,
  `mall_price_flg` tinyint(1) NOT NULL DEFAULT '0',
  `daihyo_syohin_label` varchar(255) NOT NULL COMMENT 'ラベル印刷用タイトル',
  `maxbuynum` int(2) NOT NULL DEFAULT '0' COMMENT '最大購入可能数',
  `outlet` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'アウトレットか？',
  `adult` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'アダルトか？',
  `big_size` tinyint(1) NOT NULL DEFAULT '0' COMMENT '大きいサイズあり フラグ',
  `viewrank` int(4) DEFAULT '0' COMMENT '閲覧ランキング',
  `reviewrequest` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'レビュー要求',
  `last_review_date` datetime NOT NULL COMMENT '最終レビュー日時',
  `review_point_ave` varchar(3) NOT NULL DEFAULT '0.0' COMMENT '平均レビュー得点',
  `review_num` int(11) NOT NULL DEFAULT '0' COMMENT 'レビュー数',
  `search_code` varchar(255) NOT NULL COMMENT '検索用コード（代表商品コード+商品ラベル+Q10コード+DENAコード',
  `fixed_cost` int(11) NOT NULL DEFAULT '0' COMMENT '商品固有固定費',
  `DENA画像チェック区分` tinyint(3) NOT NULL,
  `dena_pic_check_datetime` datetime DEFAULT NULL,
  `dena_pic_check_datetime_sort` datetime NOT NULL,
  `notfound_image_no_rakuten` int(2) NOT NULL DEFAULT '0',
  `notfound_image_no_dena` int(2) NOT NULL DEFAULT '0',
  `startup_flg` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '登録直後かどうか',
  `pricedown_flg` tinyint(1) NOT NULL DEFAULT '-1' COMMENT 'デフォルトで値下げ許可するか否か',
  `red_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '赤字販売フラグ',
  `last_orderdate` date NOT NULL COMMENT '最終発注日',
  `wang_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '広州(王)さん問い合わせ状況',
  `受発注可能フラグ退避F` tinyint(1) NOT NULL DEFAULT '0',
  `soldout_check_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '売切目視確認チェックフラグ',
  `label_remark_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'ラベル注目フラグ',
  `size_check_need_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'サイズチェック必要 フラグ',
  `weight_check_need_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '重量チェック必要 フラグ',
  `deliverycode_pre` int(11) NOT NULL DEFAULT '4' COMMENT '共通処理開始時点のdeliverycode',
  `high_sales_rate_flg` tinyint(4) NOT NULL DEFAULT '0' COMMENT '高成約率フラグ',
  `mail_send_nums` int(2) DEFAULT NULL COMMENT 'メール便可能数',
  `memo` varchar(2000) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rakutencategories_3` varchar(100) NOT NULL,
  PRIMARY KEY (`daihyo_syohin_code`),
  KEY `search_code` (`search_code`) USING BTREE,
  KEY `dena_pic_check_datetime` (`dena_pic_check_datetime`) USING BTREE,
  KEY `endofavailability` (`endofavailability`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_mainproducts_former`
--

DROP TABLE IF EXISTS `tb_mainproducts_former`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_mainproducts_former` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `sire_code` varchar(10) DEFAULT NULL,
  `jan_code` varchar(50) DEFAULT NULL,
  `syohin_kbn` varchar(10) DEFAULT NULL,
  `genka_tnk` int(10) unsigned DEFAULT NULL,
  `genka_tnk_ave` int(11) NOT NULL DEFAULT '0',
  `additional_cost` int(11) NOT NULL DEFAULT '0',
  `baika_tnk` int(10) unsigned DEFAULT NULL,
  `daihyo_syohin_name` varchar(255) DEFAULT NULL,
  `visible_flg` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_mainproducts_former_pre`
--

DROP TABLE IF EXISTS `tb_mainproducts_former_pre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_mainproducts_former_pre` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `sire_code` varchar(10) DEFAULT NULL,
  `jan_code` varchar(50) DEFAULT NULL,
  `syohin_kbn` varchar(10) DEFAULT NULL,
  `genka_tnk` int(10) unsigned DEFAULT NULL,
  `genka_tnk_ave` int(11) NOT NULL DEFAULT '0',
  `additional_cost` int(11) NOT NULL DEFAULT '0',
  `baika_tnk` int(10) unsigned DEFAULT NULL,
  `daihyo_syohin_name` varchar(255) DEFAULT NULL,
  `visible_flg` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_mainproducts_mall`
--

DROP TABLE IF EXISTS `tb_mainproducts_mall`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_mainproducts_mall` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `mall_id` int(11) NOT NULL,
  `registration_flg` tinyint(1) DEFAULT NULL,
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  PRIMARY KEY (`daihyo_syohin_code`,`mall_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_mainproducts_random`
--

DROP TABLE IF EXISTS `tb_mainproducts_random`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_mainproducts_random` (
  `daihyo_syohin_code` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_mainproducts_tep`
--

DROP TABLE IF EXISTS `tb_mainproducts_tep`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_mainproducts_tep` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `list_some_instant_delivery` text,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_makeshop_infomation`
--

DROP TABLE IF EXISTS `tb_makeshop_infomation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_makeshop_infomation` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `makeshop_title` varchar(150) DEFAULT NULL,
  `registration_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '登録フラグ',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `商品説明` varchar(1000) DEFAULT NULL,
  `モバイル商品説明` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_makeshop_option_dl`
--

DROP TABLE IF EXISTS `tb_makeshop_option_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_makeshop_option_dl` (
  `商品特定コード指定` int(10) unsigned DEFAULT NULL,
  `オプション特定コード指定` int(10) unsigned DEFAULT NULL,
  `システム商品コード` varchar(255) DEFAULT NULL,
  `独自商品コード` varchar(255) NOT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `カテゴリ名` varchar(255) DEFAULT NULL,
  `サブカテゴリ名` varchar(255) DEFAULT NULL,
  `オプション独自コード` varchar(255) DEFAULT NULL,
  `オプション１項目` varchar(255) NOT NULL,
  `オプション２項目` varchar(255) NOT NULL,
  `販売価格` int(10) unsigned DEFAULT NULL,
  `数量` int(10) unsigned DEFAULT NULL,
  `JANコード` varchar(255) DEFAULT NULL,
  `item_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`独自商品コード`,`オプション１項目`,`オプション２項目`),
  KEY `Index_2` (`item_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_makeshop_upload_category`
--

DROP TABLE IF EXISTS `tb_makeshop_upload_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_makeshop_upload_category` (
  `カテゴリコード` varchar(255) DEFAULT NULL,
  `カテゴリ名` varchar(255) DEFAULT NULL,
  `サブカテゴリ名` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_makeshop_upload_dl`
--

DROP TABLE IF EXISTS `tb_makeshop_upload_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_makeshop_upload_dl` (
  `商品特定コード指定` int(10) unsigned DEFAULT NULL,
  `更新時間フラグ` int(10) unsigned DEFAULT NULL,
  `システム商品コード` varchar(255) DEFAULT NULL,
  `独自商品コード` varchar(255) NOT NULL,
  `カテゴリ名` varchar(255) DEFAULT NULL,
  `サブカテゴリ名` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `重量` int(10) unsigned DEFAULT NULL,
  `販売価格` int(10) unsigned DEFAULT NULL,
  `定価` int(10) unsigned DEFAULT NULL,
  `ポイント` int(10) unsigned DEFAULT NULL,
  `仕入価格` int(10) unsigned DEFAULT NULL,
  `製造元` varchar(255) DEFAULT NULL,
  `原産地` varchar(255) DEFAULT NULL,
  `原産地表示フラグ` int(10) unsigned DEFAULT NULL,
  `数量` int(10) unsigned DEFAULT NULL,
  `数量表示フラグ` int(10) unsigned DEFAULT NULL,
  `最小注文限度数` int(10) unsigned DEFAULT NULL,
  `最大注文限度数` int(10) unsigned DEFAULT NULL,
  `陳列位置` int(10) unsigned DEFAULT NULL,
  `送料個別設定` int(10) unsigned DEFAULT NULL,
  `割引使用フラグ` int(10) unsigned DEFAULT NULL,
  `割引率` int(10) unsigned DEFAULT NULL,
  `割引期間` varchar(255) DEFAULT NULL,
  `商品グループ` varchar(255) DEFAULT NULL,
  `商品検索語` varchar(255) DEFAULT NULL,
  `商品別特殊表示` varchar(255) DEFAULT NULL,
  `オプション１名称` varchar(255) DEFAULT NULL,
  `オプション２名称` varchar(255) DEFAULT NULL,
  `オプショングループ` varchar(255) DEFAULT NULL,
  `拡大画像名` varchar(255) DEFAULT NULL,
  `普通画像名` varchar(255) DEFAULT NULL,
  `縮小画像名` varchar(255) DEFAULT NULL,
  `モバイル画像名` varchar(255) DEFAULT NULL,
  `モバイル商品説明` text,
  `商品説明` text,
  `JANコード` varchar(255) DEFAULT NULL,
  `商品表示可否` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`独自商品コード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_mall_input_check`
--

DROP TABLE IF EXISTS `tb_mall_input_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_mall_input_check` (
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `mall` varchar(30) NOT NULL,
  `col_name` varchar(30) NOT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_mall_payment_method`
--

DROP TABLE IF EXISTS `tb_mall_payment_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_mall_payment_method` (
  `ne_mall_id` int(2) NOT NULL,
  `payment_id` int(11) NOT NULL DEFAULT '0',
  `payment_cost_ratio` float DEFAULT '0',
  PRIMARY KEY (`ne_mall_id`,`payment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_member`
--

DROP TABLE IF EXISTS `tb_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_member` (
  `member_code` int(10) unsigned NOT NULL,
  `member_name` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `login_id` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`member_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ne_delete_master`
--

DROP TABLE IF EXISTS `tb_ne_delete_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ne_delete_master` (
  `商品コード` varchar(50) NOT NULL,
  PRIMARY KEY (`商品コード`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ne_products`
--

DROP TABLE IF EXISTS `tb_ne_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ne_products` (
  `syohin_code` varchar(50) NOT NULL,
  `sire_code` varchar(10) DEFAULT NULL,
  `jan_code` varchar(50) DEFAULT NULL,
  `syohin_name` varchar(255) NOT NULL,
  `syohin_kbn` varchar(10) DEFAULT NULL,
  `toriatukai_kbn` varchar(1) NOT NULL,
  `genka_tnk` int(10) unsigned DEFAULT NULL,
  `baika_tnk` int(10) unsigned DEFAULT NULL,
  `daihyo_syohin_code` varchar(30) DEFAULT NULL,
  `tag` varchar(50) NOT NULL,
  `location` varchar(50) NOT NULL,
  `visible_flg` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_netsea_vendoraddress`
--

DROP TABLE IF EXISTS `tb_netsea_vendoraddress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_netsea_vendoraddress` (
  `netsea_vendoraddress` varchar(255) NOT NULL,
  `netsea_vendor_code` varchar(255) NOT NULL,
  `netsea_title` varchar(255) NOT NULL,
  `netsea_price` int(10) unsigned NOT NULL,
  `netsea_set_count` int(10) unsigned DEFAULT NULL,
  `netsea_pass` tinyint(1) DEFAULT '0',
  `last_check` tinyint(1) DEFAULT '0',
  `ranking` int(10) unsigned DEFAULT '0',
  `display_order` int(10) unsigned DEFAULT '0',
  `sire_code` varchar(10) NOT NULL COMMENT '仕入先コード',
  PRIMARY KEY (`netsea_vendoraddress`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_netsea_vendoraddress_html`
--

DROP TABLE IF EXISTS `tb_netsea_vendoraddress_html`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_netsea_vendoraddress_html` (
  `netsea_vendoraddress` varchar(255) NOT NULL,
  `html` mediumtext NOT NULL,
  `netsea_set_count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`netsea_vendoraddress`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order`
--

DROP TABLE IF EXISTS `tb_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order` (
  `発注日` datetime NOT NULL,
  `商品コード` varchar(255) NOT NULL,
  `数量` int(10) unsigned NOT NULL,
  `確認用商品名` varchar(255) DEFAULT NULL,
  `横軸名` varchar(255) DEFAULT NULL,
  `縦軸名` varchar(255) DEFAULT NULL,
  `購入アドレス` varchar(255) DEFAULT NULL,
  `弊社楽天販売アドレス` varchar(255) DEFAULT NULL,
  `伝票番号` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`商品コード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_data`
--

DROP TABLE IF EXISTS `tb_order_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_data` (
  `店舗名` varchar(40) DEFAULT NULL,
  `伝票番号` int(10) unsigned DEFAULT NULL,
  `受注番号` varchar(40) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `取込日` varchar(29) DEFAULT NULL,
  `受注チェック` varchar(15) DEFAULT NULL,
  `受注チェック担当者` varchar(15) DEFAULT NULL,
  `確認チェック` varchar(21) DEFAULT NULL,
  `キャンセル` varchar(11) DEFAULT NULL,
  `受注キャンセル日` varchar(29) DEFAULT NULL,
  `受注状態` varchar(22) DEFAULT NULL,
  `受注担当者` varchar(16) DEFAULT NULL,
  `発送方法` varchar(23) DEFAULT NULL,
  `支払方法` varchar(23) DEFAULT NULL,
  `合計金額` varchar(19) DEFAULT NULL,
  `税金` varchar(18) DEFAULT NULL,
  `手数料` varchar(17) DEFAULT NULL,
  `送料` varchar(19) DEFAULT NULL,
  `その他` varchar(18) DEFAULT NULL,
  `ポイント` varchar(18) DEFAULT NULL,
  `承認金額` varchar(19) DEFAULT NULL,
  `備考` text,
  `入金金額` varchar(19) DEFAULT NULL,
  `入金区分` varchar(14) DEFAULT NULL,
  `入金日` varchar(29) DEFAULT NULL,
  `納品書印刷指示日` varchar(29) DEFAULT NULL,
  `納品書発行日` varchar(29) DEFAULT NULL,
  `納品書備考` varchar(1000) DEFAULT NULL,
  `出荷日` varchar(29) DEFAULT NULL,
  `出荷予定日` varchar(29) DEFAULT NULL,
  `出荷担当者` varchar(15) DEFAULT NULL,
  `作業者欄` text,
  `ピック指示内容` varchar(265) DEFAULT NULL,
  `ラベル発行日` varchar(29) DEFAULT NULL,
  `配送日` varchar(29) DEFAULT NULL,
  `配送時間帯` varchar(21) DEFAULT NULL,
  `配送伝票番号` varchar(38) DEFAULT NULL,
  `クレジット区分` varchar(20) DEFAULT NULL,
  `名義人` varchar(27) DEFAULT NULL,
  `有効期限` varchar(17) DEFAULT NULL,
  `承認番号` varchar(12) DEFAULT NULL,
  `承認区分` varchar(14) DEFAULT NULL,
  `承認日` varchar(29) DEFAULT NULL,
  `オーソリ名` varchar(10) DEFAULT NULL,
  `顧客区分` varchar(14) DEFAULT NULL,
  `顧客コード` varchar(10) DEFAULT NULL,
  `購入者名` varchar(49) DEFAULT NULL,
  `購入者カナ` varchar(43) DEFAULT NULL,
  `購入者郵便番号` varchar(26) DEFAULT NULL,
  `購入者住所1` varchar(149) DEFAULT NULL,
  `購入者住所2` varchar(115) DEFAULT NULL,
  `購入者電話番号` varchar(25) DEFAULT NULL,
  `購入者ＦＡＸ` varchar(10) DEFAULT NULL,
  `購入者メールアドレス` varchar(65) DEFAULT NULL,
  `発送先名` varchar(50) DEFAULT NULL,
  `発送先カナ` varchar(45) DEFAULT NULL,
  `発送先郵便番号` varchar(26) DEFAULT NULL,
  `発送先住所1` varchar(149) DEFAULT NULL,
  `発送先住所2` varchar(130) DEFAULT NULL,
  `発送先電話番号` varchar(25) DEFAULT NULL,
  `発送先ＦＡＸ` varchar(10) DEFAULT NULL,
  `配送備考` varchar(20) DEFAULT NULL,
  `商品コード` varchar(40) DEFAULT NULL,
  `商品名` varchar(197) DEFAULT NULL,
  `受注数` varchar(14) DEFAULT NULL,
  `商品単価` varchar(19) DEFAULT NULL,
  `掛率` varchar(14) DEFAULT NULL,
  `小計` varchar(19) DEFAULT NULL,
  `商品オプション` varchar(265) DEFAULT NULL,
  `引当数` varchar(13) DEFAULT NULL,
  `引当日` varchar(29) DEFAULT NULL,
  `出荷予定月日` varchar(10) NOT NULL,
  `出荷予定月` int(11) NOT NULL DEFAULT '0',
  KEY `Index_1` (`伝票番号`) USING BTREE,
  KEY `受注番号` (`受注番号`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_data_cal`
--

DROP TABLE IF EXISTS `tb_order_data_cal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_data_cal` (
  `伝票番号` int(11) DEFAULT NULL,
  `受注番号` varchar(255) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `商品名` varchar(255) NOT NULL,
  `受注数` int(11) NOT NULL DEFAULT '0',
  `引当数` int(11) NOT NULL DEFAULT '0',
  `元受注番号` varchar(255) DEFAULT NULL,
  `元カート番号` varchar(255) DEFAULT NULL,
  `出荷予定日文字列` varchar(255) DEFAULT NULL,
  `出荷予定日仮` datetime DEFAULT NULL,
  `出荷予定日` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_data_mainadd`
--

DROP TABLE IF EXISTS `tb_order_data_mainadd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_data_mainadd` (
  `伝票番号` int(10) unsigned NOT NULL,
  `check_for_dates_confirmed` date DEFAULT NULL,
  `作業者欄_former` text,
  `確認チェック` varchar(255) DEFAULT NULL,
  `hold_reason` varchar(255) DEFAULT NULL,
  `確認チェック_checked` int(10) DEFAULT '0',
  `being_processed` int(11) DEFAULT '-1',
  `shipping_time` date DEFAULT NULL,
  `受注状態` varchar(255) DEFAULT NULL,
  `支払方法` varchar(255) DEFAULT NULL,
  `入金区分` varchar(255) DEFAULT NULL,
  `delivery_terms` datetime DEFAULT NULL,
  `sun_payment_reminder` date DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `purchase_quantity` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`伝票番号`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_data_tep`
--

DROP TABLE IF EXISTS `tb_order_data_tep`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_data_tep` (
  `店舗名` varchar(255) DEFAULT NULL,
  `伝票番号` int(10) DEFAULT NULL,
  `受注番号` varchar(255) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `取込日` varchar(255) DEFAULT NULL,
  `受注チェック` varchar(255) DEFAULT NULL,
  `受注チェック担当者` varchar(255) DEFAULT NULL,
  `確認チェック` varchar(255) DEFAULT NULL,
  `キャンセル` varchar(255) DEFAULT NULL,
  `受注キャンセル日` varchar(255) DEFAULT NULL,
  `受注状態` varchar(255) DEFAULT NULL,
  `受注担当者` varchar(255) DEFAULT NULL,
  `発送方法` varchar(255) DEFAULT NULL,
  `支払方法` varchar(255) DEFAULT NULL,
  `合計金額` varchar(255) DEFAULT NULL,
  `税金` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `送料` varchar(255) DEFAULT NULL,
  `その他` varchar(255) DEFAULT NULL,
  `ポイント` varchar(255) DEFAULT NULL,
  `承認金額` varchar(255) DEFAULT NULL,
  `備考` text,
  `入金金額` varchar(255) DEFAULT NULL,
  `入金区分` varchar(255) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `納品書印刷指示日` varchar(255) DEFAULT NULL,
  `納品書発行日` varchar(255) DEFAULT NULL,
  `納品書備考` text,
  `出荷日` varchar(255) DEFAULT NULL,
  `出荷予定日` varchar(255) DEFAULT NULL,
  `出荷担当者` varchar(255) DEFAULT NULL,
  `作業者欄` text,
  `ピック指示内容` varchar(255) DEFAULT NULL,
  `ラベル発行日` varchar(255) DEFAULT NULL,
  `配送日` varchar(255) DEFAULT NULL,
  `配送時間帯` varchar(255) DEFAULT NULL,
  `配送伝票番号` varchar(255) DEFAULT NULL,
  `クレジット区分` varchar(255) DEFAULT NULL,
  `名義人` varchar(255) DEFAULT NULL,
  `有効期限` varchar(255) DEFAULT NULL,
  `承認番号` varchar(255) DEFAULT NULL,
  `承認区分` varchar(255) DEFAULT NULL,
  `承認日` varchar(255) DEFAULT NULL,
  `オーソリ名` varchar(255) DEFAULT NULL,
  `顧客区分` varchar(255) DEFAULT NULL,
  `顧客コード` varchar(255) DEFAULT NULL,
  `購入者名` varchar(255) DEFAULT NULL,
  `購入者カナ` varchar(255) DEFAULT NULL,
  `購入者郵便番号` varchar(255) DEFAULT NULL,
  `購入者住所1` varchar(255) DEFAULT NULL,
  `購入者住所2` varchar(255) DEFAULT NULL,
  `購入者電話番号` varchar(255) DEFAULT NULL,
  `購入者ＦＡＸ` varchar(255) DEFAULT NULL,
  `購入者メールアドレス` varchar(255) DEFAULT NULL,
  `発送先名` varchar(255) DEFAULT NULL,
  `発送先カナ` varchar(255) DEFAULT NULL,
  `発送先郵便番号` varchar(255) DEFAULT NULL,
  `発送先住所1` varchar(255) DEFAULT NULL,
  `発送先住所2` varchar(255) DEFAULT NULL,
  `発送先電話番号` varchar(255) DEFAULT NULL,
  `発送先ＦＡＸ` varchar(255) DEFAULT NULL,
  `配送備考` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `受注数` varchar(255) DEFAULT NULL,
  `商品単価` varchar(255) DEFAULT NULL,
  `掛率` varchar(255) DEFAULT NULL,
  `小計` varchar(255) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `引当数` varchar(255) DEFAULT NULL,
  `引当日` varchar(255) DEFAULT NULL,
  KEY `INDEX_1` (`伝票番号`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_enabled_flg_backup`
--

DROP TABLE IF EXISTS `tb_order_enabled_flg_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_enabled_flg_backup` (
  `ne_syohin_syohin_code` varchar(255) NOT NULL,
  `並び順` int(3) NOT NULL DEFAULT '0',
  `受発注可能フラグ` tinyint(1) NOT NULL DEFAULT '0',
  `daihyo_syohin_code` varchar(50) NOT NULL,
  PRIMARY KEY (`ne_syohin_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_enabled_flg_backup_nontarget`
--

DROP TABLE IF EXISTS `tb_order_enabled_flg_backup_nontarget`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_enabled_flg_backup_nontarget` (
  `daihyo_syohin_code` varchar(50) NOT NULL DEFAULT '',
  `登録日時` date NOT NULL,
  `受注数` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_enabled_flg_backup_target`
--

DROP TABLE IF EXISTS `tb_order_enabled_flg_backup_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_enabled_flg_backup_target` (
  `daihyo_syohin_code` varchar(50) NOT NULL DEFAULT '',
  `登録日時` date NOT NULL,
  `受注数` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_enabled_flg_backup_target_del`
--

DROP TABLE IF EXISTS `tb_order_enabled_flg_backup_target_del`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_enabled_flg_backup_target_del` (
  `daihyo_syohin_code` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_request`
--

DROP TABLE IF EXISTS `tb_order_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_request` (
  `商品コード` varchar(255) NOT NULL,
  `genka_tnk` int(10) DEFAULT '0',
  `order_request` int(10) DEFAULT '0',
  `sire_code` varchar(255) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `order_quantity` int(10) DEFAULT '0',
  PRIMARY KEY (`商品コード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_request_summary`
--

DROP TABLE IF EXISTS `tb_order_request_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_request_summary` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `商品コード` varchar(255) DEFAULT NULL,
  `genka_tnk` int(11) DEFAULT NULL,
  `order_request` int(11) DEFAULT NULL,
  `sire_code` varchar(255) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `order_quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_summary_a`
--

DROP TABLE IF EXISTS `tb_order_summary_a`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_summary_a` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `sire_code` varchar(255) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `メーカー商品コード` varchar(30) DEFAULT NULL,
  `受発注可能フラグ` int(11) DEFAULT NULL,
  `価格非連動チェック` smallint(6) DEFAULT NULL,
  `手動ゲリラSALE` smallint(6) DEFAULT NULL,
  `genka_tnk` int(11) DEFAULT NULL,
  `order_request` int(11) DEFAULT NULL,
  `order_quantity` int(11) DEFAULT NULL,
  `daihyo_syohin_code` varchar(255) DEFAULT NULL,
  `im_stock` int(11) DEFAULT NULL,
  `yours_stock` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_order_summary_b`
--

DROP TABLE IF EXISTS `tb_order_summary_b`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_order_summary_b` (
  `sire_code` varchar(255) NOT NULL DEFAULT '',
  `最古受注日` datetime DEFAULT NULL,
  `金額` int(11) DEFAULT NULL,
  PRIMARY KEY (`sire_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_amazon_items_dl`
--

DROP TABLE IF EXISTS `tb_orderr_amazon_items_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_amazon_items_dl` (
  `order-id` varchar(255) DEFAULT NULL,
  `order-item-id` varchar(30) DEFAULT NULL,
  `purchase-date` varchar(255) DEFAULT NULL,
  `payments-date` varchar(255) DEFAULT NULL,
  `buyer-email` varchar(255) DEFAULT NULL,
  `buyer-name` varchar(255) DEFAULT NULL,
  `buyer-phone-number` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `product-name` varchar(255) DEFAULT NULL,
  `quantity-purchased` int(11) DEFAULT NULL,
  `currency` varchar(255) DEFAULT NULL,
  `item-price` int(11) DEFAULT NULL,
  `item-tax` int(11) DEFAULT NULL,
  `shipping-price` int(11) DEFAULT NULL,
  `shipping-tax` int(11) DEFAULT NULL,
  `gift-wrap-price` varchar(255) DEFAULT NULL,
  `gift-wrap-tax` varchar(255) DEFAULT NULL,
  `ship-service-level` varchar(255) DEFAULT NULL,
  `recipient-name` varchar(255) DEFAULT NULL,
  `ship-address-1` varchar(255) DEFAULT NULL,
  `ship-address-2` varchar(255) DEFAULT NULL,
  `ship-address-3` varchar(255) DEFAULT NULL,
  `ship-city` varchar(255) DEFAULT NULL,
  `ship-state` varchar(255) DEFAULT NULL,
  `ship-postal-code` varchar(255) DEFAULT NULL,
  `ship-country` varchar(255) DEFAULT NULL,
  `ship-phone-number` varchar(255) DEFAULT NULL,
  `gift-wrap-type` varchar(255) DEFAULT NULL,
  `gift-message-text` varchar(255) DEFAULT NULL,
  `delivery-start-date` varchar(255) DEFAULT NULL,
  `delivery-end-date` varchar(255) DEFAULT NULL,
  `delivery-time-zone` varchar(255) DEFAULT NULL,
  `delivery-Instructions` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_amazon_items_dl_bak`
--

DROP TABLE IF EXISTS `tb_orderr_amazon_items_dl_bak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_amazon_items_dl_bak` (
  `order-id` varchar(255) DEFAULT NULL,
  `order-item-id` varchar(30) DEFAULT NULL,
  `purchase-date` varchar(255) DEFAULT NULL,
  `payments-date` varchar(255) DEFAULT NULL,
  `buyer-email` varchar(255) DEFAULT NULL,
  `buyer-name` varchar(255) DEFAULT NULL,
  `buyer-phone-number` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `product-name` varchar(255) DEFAULT NULL,
  `quantity-purchased` int(11) DEFAULT NULL,
  `currency` varchar(255) DEFAULT NULL,
  `item-price` int(11) DEFAULT NULL,
  `item-tax` int(11) DEFAULT NULL,
  `shipping-price` int(11) DEFAULT NULL,
  `shipping-tax` int(11) DEFAULT NULL,
  `gift-wrap-price` varchar(255) DEFAULT NULL,
  `gift-wrap-tax` varchar(255) DEFAULT NULL,
  `ship-service-level` varchar(255) DEFAULT NULL,
  `recipient-name` varchar(255) DEFAULT NULL,
  `ship-address-1` varchar(255) DEFAULT NULL,
  `ship-address-2` varchar(255) DEFAULT NULL,
  `ship-address-3` varchar(255) DEFAULT NULL,
  `ship-city` varchar(255) DEFAULT NULL,
  `ship-state` varchar(255) DEFAULT NULL,
  `ship-postal-code` varchar(255) DEFAULT NULL,
  `ship-country` varchar(255) DEFAULT NULL,
  `ship-phone-number` varchar(255) DEFAULT NULL,
  `gift-wrap-type` varchar(255) DEFAULT NULL,
  `gift-message-text` varchar(255) DEFAULT NULL,
  `delivery-start-date` varchar(255) DEFAULT NULL,
  `delivery-end-date` varchar(255) DEFAULT NULL,
  `delivery-time-zone` varchar(255) DEFAULT NULL,
  `delivery-Instructions` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_amazon_items_dl_tmp`
--

DROP TABLE IF EXISTS `tb_orderr_amazon_items_dl_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_amazon_items_dl_tmp` (
  `order-id` varchar(255) DEFAULT NULL,
  `order-item-id` varchar(30) DEFAULT NULL,
  `purchase-date` varchar(255) DEFAULT NULL,
  `payments-date` varchar(255) DEFAULT NULL,
  `buyer-email` varchar(255) DEFAULT NULL,
  `buyer-name` varchar(255) DEFAULT NULL,
  `buyer-phone-number` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `product-name` varchar(255) DEFAULT NULL,
  `quantity-purchased` int(11) DEFAULT NULL,
  `currency` varchar(255) DEFAULT NULL,
  `item-price` int(11) DEFAULT NULL,
  `item-tax` int(11) DEFAULT NULL,
  `shipping-price` int(11) DEFAULT NULL,
  `shipping-tax` int(11) DEFAULT NULL,
  `gift-wrap-price` varchar(255) DEFAULT NULL,
  `gift-wrap-tax` varchar(255) DEFAULT NULL,
  `ship-service-level` varchar(255) DEFAULT NULL,
  `recipient-name` varchar(255) DEFAULT NULL,
  `ship-address-1` varchar(255) DEFAULT NULL,
  `ship-address-2` varchar(255) DEFAULT NULL,
  `ship-address-3` varchar(255) DEFAULT NULL,
  `ship-city` varchar(255) DEFAULT NULL,
  `ship-state` varchar(255) DEFAULT NULL,
  `ship-postal-code` varchar(255) DEFAULT NULL,
  `ship-country` varchar(255) DEFAULT NULL,
  `ship-phone-number` varchar(255) DEFAULT NULL,
  `gift-wrap-type` varchar(255) DEFAULT NULL,
  `gift-message-text` varchar(255) DEFAULT NULL,
  `delivery-start-date` varchar(255) DEFAULT NULL,
  `delivery-end-date` varchar(255) DEFAULT NULL,
  `delivery-time-zone` varchar(255) DEFAULT NULL,
  `delivery-Instructions` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_dena_log_dl`
--

DROP TABLE IF EXISTS `tb_orderr_dena_log_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_dena_log_dl` (
  `取引No` varchar(255) DEFAULT NULL,
  `管理No` varchar(255) DEFAULT NULL,
  `ロットNo` varchar(255) DEFAULT NULL,
  `タイトル` varchar(255) DEFAULT NULL,
  `落札価格` varchar(255) DEFAULT NULL,
  `個数` varchar(255) DEFAULT NULL,
  `落札日` varchar(255) DEFAULT NULL,
  `ニックネーム` varchar(255) DEFAULT NULL,
  `Eメールアドレス` varchar(255) DEFAULT NULL,
  `【取引管理】名前` varchar(255) DEFAULT NULL,
  `【取引管理】住所` varchar(255) DEFAULT NULL,
  `【取引管理】電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】名前` varchar(255) DEFAULT NULL,
  `【取引ナビ】住所` varchar(255) DEFAULT NULL,
  `【取引ナビ】電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】希望取引方法` varchar(255) DEFAULT NULL,
  `【取引ナビ】コメント` varchar(255) DEFAULT NULL,
  `【出品時設定】希望取引方法` varchar(255) DEFAULT NULL,
  `【取引管理】実際の取引方法` varchar(255) DEFAULT NULL,
  `連絡済み` varchar(255) DEFAULT NULL,
  `連絡日` varchar(255) DEFAULT NULL,
  `入金確認済み` varchar(255) DEFAULT NULL,
  `入金確認日` varchar(255) DEFAULT NULL,
  `発送済み` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `販売単価` varchar(255) DEFAULT NULL,
  `販売個数` varchar(255) DEFAULT NULL,
  `小計` varchar(255) DEFAULT NULL,
  `消費税` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `送料` varchar(255) DEFAULT NULL,
  `請求金額` varchar(255) DEFAULT NULL,
  `取引メモ` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先氏名` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先住所` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者カナ` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者日中連絡先` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者メールアドレス` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先カナ` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先日中連絡先` varchar(255) DEFAULT NULL,
  `販売総額` varchar(255) DEFAULT NULL,
  `販売総数` varchar(255) DEFAULT NULL,
  `消費税区分` varchar(255) DEFAULT NULL,
  `キャンセル` varchar(255) DEFAULT NULL,
  `アイテムオプション` varchar(255) DEFAULT NULL,
  `(旧)取引No` varchar(255) DEFAULT NULL,
  `カード種類` varchar(255) DEFAULT NULL,
  `カード番号` varchar(255) DEFAULT NULL,
  `有効期限・年` varchar(255) DEFAULT NULL,
  `有効期限・月` varchar(255) DEFAULT NULL,
  `カード名義人` varchar(255) DEFAULT NULL,
  `名義人生年月日` varchar(255) DEFAULT NULL,
  `オークションタイプ` varchar(255) DEFAULT NULL,
  `ホームサイト` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `総合計` varchar(255) DEFAULT NULL,
  `ポイント利用分` varchar(255) DEFAULT NULL,
  `利用キャンセル状況` varchar(255) DEFAULT NULL,
  `付与ポイント数` varchar(255) DEFAULT NULL,
  `CB原資付与ポイント数` varchar(255) DEFAULT NULL,
  `付与ポイント確定(予定)日` varchar(255) DEFAULT NULL,
  `付与ポイント状況` varchar(255) DEFAULT NULL,
  `取引オプション` varchar(255) DEFAULT NULL,
  `クレジットカードオプション` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_dena_log_dl_bak`
--

DROP TABLE IF EXISTS `tb_orderr_dena_log_dl_bak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_dena_log_dl_bak` (
  `取引No` varchar(255) DEFAULT NULL,
  `管理No` varchar(255) DEFAULT NULL,
  `ロットNo` varchar(255) DEFAULT NULL,
  `タイトル` varchar(255) DEFAULT NULL,
  `落札価格` varchar(255) DEFAULT NULL,
  `個数` varchar(255) DEFAULT NULL,
  `落札日` varchar(255) DEFAULT NULL,
  `ニックネーム` varchar(255) DEFAULT NULL,
  `Eメールアドレス` varchar(255) DEFAULT NULL,
  `【取引管理】名前` varchar(255) DEFAULT NULL,
  `【取引管理】住所` varchar(255) DEFAULT NULL,
  `【取引管理】電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】名前` varchar(255) DEFAULT NULL,
  `【取引ナビ】住所` varchar(255) DEFAULT NULL,
  `【取引ナビ】電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】希望取引方法` varchar(255) DEFAULT NULL,
  `【取引ナビ】コメント` varchar(255) DEFAULT NULL,
  `【出品時設定】希望取引方法` varchar(255) DEFAULT NULL,
  `【取引管理】実際の取引方法` varchar(255) DEFAULT NULL,
  `連絡済み` varchar(255) DEFAULT NULL,
  `連絡日` varchar(255) DEFAULT NULL,
  `入金確認済み` varchar(255) DEFAULT NULL,
  `入金確認日` varchar(255) DEFAULT NULL,
  `発送済み` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `販売単価` varchar(255) DEFAULT NULL,
  `販売個数` varchar(255) DEFAULT NULL,
  `小計` varchar(255) DEFAULT NULL,
  `消費税` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `送料` varchar(255) DEFAULT NULL,
  `請求金額` varchar(255) DEFAULT NULL,
  `取引メモ` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先氏名` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先住所` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者カナ` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者日中連絡先` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者メールアドレス` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先カナ` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先日中連絡先` varchar(255) DEFAULT NULL,
  `販売総額` varchar(255) DEFAULT NULL,
  `販売総数` varchar(255) DEFAULT NULL,
  `消費税区分` varchar(255) DEFAULT NULL,
  `キャンセル` varchar(255) DEFAULT NULL,
  `アイテムオプション` varchar(255) DEFAULT NULL,
  `(旧)取引No` varchar(255) DEFAULT NULL,
  `カード種類` varchar(255) DEFAULT NULL,
  `カード番号` varchar(255) DEFAULT NULL,
  `有効期限・年` varchar(255) DEFAULT NULL,
  `有効期限・月` varchar(255) DEFAULT NULL,
  `カード名義人` varchar(255) DEFAULT NULL,
  `名義人生年月日` varchar(255) DEFAULT NULL,
  `オークションタイプ` varchar(255) DEFAULT NULL,
  `ホームサイト` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `総合計` varchar(255) DEFAULT NULL,
  `ポイント利用分` varchar(255) DEFAULT NULL,
  `利用キャンセル状況` varchar(255) DEFAULT NULL,
  `付与ポイント数` varchar(255) DEFAULT NULL,
  `CB原資付与ポイント数` varchar(255) DEFAULT NULL,
  `付与ポイント確定(予定)日` varchar(255) DEFAULT NULL,
  `付与ポイント状況` varchar(255) DEFAULT NULL,
  `取引オプション` varchar(255) DEFAULT NULL,
  `クレジットカードオプション` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_dena_log_dl_tmp`
--

DROP TABLE IF EXISTS `tb_orderr_dena_log_dl_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_dena_log_dl_tmp` (
  `取引No` varchar(255) DEFAULT NULL,
  `管理No` varchar(255) DEFAULT NULL,
  `ロットNo` varchar(255) DEFAULT NULL,
  `タイトル` varchar(255) DEFAULT NULL,
  `落札価格` varchar(255) DEFAULT NULL,
  `個数` varchar(255) DEFAULT NULL,
  `落札日` varchar(255) DEFAULT NULL,
  `ニックネーム` varchar(255) DEFAULT NULL,
  `Eメールアドレス` varchar(255) DEFAULT NULL,
  `【取引管理】名前` varchar(255) DEFAULT NULL,
  `【取引管理】住所` varchar(255) DEFAULT NULL,
  `【取引管理】電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】名前` varchar(255) DEFAULT NULL,
  `【取引ナビ】住所` varchar(255) DEFAULT NULL,
  `【取引ナビ】電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】希望取引方法` varchar(255) DEFAULT NULL,
  `【取引ナビ】コメント` varchar(255) DEFAULT NULL,
  `【出品時設定】希望取引方法` varchar(255) DEFAULT NULL,
  `【取引管理】実際の取引方法` varchar(255) DEFAULT NULL,
  `連絡済み` varchar(255) DEFAULT NULL,
  `連絡日` varchar(255) DEFAULT NULL,
  `入金確認済み` varchar(255) DEFAULT NULL,
  `入金確認日` varchar(255) DEFAULT NULL,
  `発送済み` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `販売単価` varchar(255) DEFAULT NULL,
  `販売個数` varchar(255) DEFAULT NULL,
  `小計` varchar(255) DEFAULT NULL,
  `消費税` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `送料` varchar(255) DEFAULT NULL,
  `請求金額` varchar(255) DEFAULT NULL,
  `取引メモ` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先氏名` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先住所` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先電話番号` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者カナ` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者日中連絡先` varchar(255) DEFAULT NULL,
  `【取引ナビ】落札者メールアドレス` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先カナ` varchar(255) DEFAULT NULL,
  `【取引ナビ】送付先日中連絡先` varchar(255) DEFAULT NULL,
  `販売総額` varchar(255) DEFAULT NULL,
  `販売総数` varchar(255) DEFAULT NULL,
  `消費税区分` varchar(255) DEFAULT NULL,
  `キャンセル` varchar(255) DEFAULT NULL,
  `アイテムオプション` varchar(255) DEFAULT NULL,
  `(旧)取引No` varchar(255) DEFAULT NULL,
  `カード種類` varchar(255) DEFAULT NULL,
  `カード番号` varchar(255) DEFAULT NULL,
  `有効期限・年` varchar(255) DEFAULT NULL,
  `有効期限・月` varchar(255) DEFAULT NULL,
  `カード名義人` varchar(255) DEFAULT NULL,
  `名義人生年月日` varchar(255) DEFAULT NULL,
  `オークションタイプ` varchar(255) DEFAULT NULL,
  `ホームサイト` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `総合計` varchar(255) DEFAULT NULL,
  `ポイント利用分` varchar(255) DEFAULT NULL,
  `利用キャンセル状況` varchar(255) DEFAULT NULL,
  `付与ポイント数` varchar(255) DEFAULT NULL,
  `CB原資付与ポイント数` varchar(255) DEFAULT NULL,
  `付与ポイント確定(予定)日` varchar(255) DEFAULT NULL,
  `付与ポイント状況` varchar(255) DEFAULT NULL,
  `取引オプション` varchar(255) DEFAULT NULL,
  `クレジットカードオプション` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_ppm_items_dl`
--

DROP TABLE IF EXISTS `tb_orderr_ppm_items_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_ppm_items_dl` (
  `注文番号` varchar(30) DEFAULT NULL,
  `注文日時` varchar(255) DEFAULT NULL,
  `担当者` varchar(255) DEFAULT NULL,
  `注文者名字` varchar(20) DEFAULT NULL,
  `注文者名前` varchar(20) DEFAULT NULL,
  `注文者名字フリガナ` varchar(20) DEFAULT NULL,
  `注文者名前フリガナ` varchar(20) DEFAULT NULL,
  `注文者郵便番号1` varchar(3) DEFAULT NULL,
  `注文者郵便番号2` varchar(4) DEFAULT NULL,
  `注文者住所：都道府県` varchar(20) DEFAULT NULL,
  `注文者住所：市区町村以降` varchar(255) DEFAULT NULL,
  `注文者電話番号` varchar(30) DEFAULT NULL,
  `メールキャリアコード` varchar(1) DEFAULT NULL,
  `会員フラグ` varchar(1) DEFAULT NULL,
  `利用端末` varchar(1) DEFAULT NULL,
  `送付先名字` varchar(20) DEFAULT NULL,
  `送付先名前` varchar(20) DEFAULT NULL,
  `送付先名字フリガナ` varchar(20) DEFAULT NULL,
  `送付先名前フリガナ` varchar(20) DEFAULT NULL,
  `送付先郵便番号1` varchar(3) DEFAULT NULL,
  `送付先郵便番号2` varchar(4) DEFAULT NULL,
  `送付先住所：都道府県` varchar(10) DEFAULT NULL,
  `送付先住所：市区町村以降` varchar(255) DEFAULT NULL,
  `送付先電話番号` varchar(30) DEFAULT NULL,
  `送付先一致フラグ` varchar(1) DEFAULT NULL,
  `商品管理ID` varchar(50) DEFAULT NULL,
  `商品ID（表示用）` varchar(50) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品ID` varchar(50) DEFAULT NULL,
  `個数` varchar(11) DEFAULT NULL,
  `単価` varchar(11) DEFAULT NULL,
  `購入オプション` varchar(255) DEFAULT NULL,
  `送料無料・別` varchar(255) DEFAULT NULL,
  `代引手数料込別` varchar(255) DEFAULT NULL,
  `商品URL` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(255) DEFAULT NULL,
  `税込別` varchar(255) DEFAULT NULL,
  `税込別(リボン)` varchar(255) DEFAULT NULL,
  `税込別(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング種類(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング種類(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング料金(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング料金(包装紙)` varchar(255) DEFAULT NULL,
  `ギフト配送（0:希望なし/1:希望あり）` varchar(255) DEFAULT NULL,
  `合計` varchar(255) DEFAULT NULL,
  `送料(-99999=無効値)` varchar(255) DEFAULT NULL,
  `消費税(-99999=無効値)` varchar(255) DEFAULT NULL,
  `代引料(-99999=無効値)` varchar(255) DEFAULT NULL,
  `合計金額(-99999=無効値)` varchar(255) DEFAULT NULL,
  `ポイント利用有無` varchar(255) DEFAULT NULL,
  `ポイント利用額` varchar(255) DEFAULT NULL,
  `請求金額(-99999=無効値)` varchar(255) DEFAULT NULL,
  `のし` varchar(255) DEFAULT NULL,
  `決済方法` varchar(255) DEFAULT NULL,
  `クレジットカード種類` varchar(255) DEFAULT NULL,
  `クレジットカード分割選択` varchar(255) DEFAULT NULL,
  `クレジットカード分割備考` varchar(255) DEFAULT NULL,
  `カード決済ステータス` varchar(255) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `配送方法` varchar(255) DEFAULT NULL,
  `配送区分` varchar(255) DEFAULT NULL,
  `お荷物伝票番号` varchar(255) DEFAULT NULL,
  `お届け時間帯` varchar(255) DEFAULT NULL,
  `お届け日指定` varchar(255) DEFAULT NULL,
  `コメント` varchar(255) DEFAULT NULL,
  `作業メモ` varchar(255) DEFAULT NULL,
  `受注ステータス` varchar(255) DEFAULT NULL,
  `メールフラグ` varchar(255) DEFAULT NULL,
  `メール差込文(お客様へのメッセージ)` varchar(255) DEFAULT NULL,
  `同梱ID` varchar(255) DEFAULT NULL,
  `同梱ステータス` varchar(255) DEFAULT NULL,
  `同梱ポイント利用合計` varchar(11) DEFAULT NULL,
  `同梱合計金額` varchar(11) DEFAULT NULL,
  `同梱商品合計金額` varchar(11) DEFAULT NULL,
  `同梱消費税合計` varchar(11) DEFAULT NULL,
  `同梱請求金額` varchar(11) DEFAULT NULL,
  `同梱送料合計` varchar(11) DEFAULT NULL,
  `同梱代引料合計` varchar(11) DEFAULT NULL,
  `ポイント付与率` varchar(255) DEFAULT NULL,
  `付与ポイント数` varchar(11) DEFAULT NULL,
  `クーポン利用額` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（ショップ発行送料分）` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（ショップ発行商品分）` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（リクルート発行送料分）` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（リクルート発行商品分）` varchar(11) DEFAULT NULL,
  `同梱注文クーポン利用額` varchar(11) DEFAULT NULL,
  `警告表示フラグ` varchar(1) DEFAULT NULL,
  `システム利用料対象額` varchar(11) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_ppm_items_dl_bak`
--

DROP TABLE IF EXISTS `tb_orderr_ppm_items_dl_bak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_ppm_items_dl_bak` (
  `注文番号` varchar(30) DEFAULT NULL,
  `注文日時` varchar(255) DEFAULT NULL,
  `担当者` varchar(255) DEFAULT NULL,
  `注文者名字` varchar(20) DEFAULT NULL,
  `注文者名前` varchar(20) DEFAULT NULL,
  `注文者名字フリガナ` varchar(20) DEFAULT NULL,
  `注文者名前フリガナ` varchar(20) DEFAULT NULL,
  `注文者郵便番号1` varchar(3) DEFAULT NULL,
  `注文者郵便番号2` varchar(4) DEFAULT NULL,
  `注文者住所：都道府県` varchar(20) DEFAULT NULL,
  `注文者住所：市区町村以降` varchar(255) DEFAULT NULL,
  `注文者電話番号` varchar(30) DEFAULT NULL,
  `メールキャリアコード` varchar(1) DEFAULT NULL,
  `会員フラグ` varchar(1) DEFAULT NULL,
  `利用端末` varchar(1) DEFAULT NULL,
  `送付先名字` varchar(20) DEFAULT NULL,
  `送付先名前` varchar(20) DEFAULT NULL,
  `送付先名字フリガナ` varchar(20) DEFAULT NULL,
  `送付先名前フリガナ` varchar(20) DEFAULT NULL,
  `送付先郵便番号1` varchar(3) DEFAULT NULL,
  `送付先郵便番号2` varchar(4) DEFAULT NULL,
  `送付先住所：都道府県` varchar(10) DEFAULT NULL,
  `送付先住所：市区町村以降` varchar(255) DEFAULT NULL,
  `送付先電話番号` varchar(30) DEFAULT NULL,
  `送付先一致フラグ` varchar(1) DEFAULT NULL,
  `商品管理ID` varchar(50) DEFAULT NULL,
  `商品ID（表示用）` varchar(50) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品ID` varchar(50) DEFAULT NULL,
  `個数` varchar(11) DEFAULT NULL,
  `単価` varchar(11) DEFAULT NULL,
  `購入オプション` varchar(255) DEFAULT NULL,
  `送料無料・別` varchar(255) DEFAULT NULL,
  `代引手数料込別` varchar(255) DEFAULT NULL,
  `商品URL` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(255) DEFAULT NULL,
  `税込別` varchar(255) DEFAULT NULL,
  `税込別(リボン)` varchar(255) DEFAULT NULL,
  `税込別(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング種類(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング種類(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング料金(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング料金(包装紙)` varchar(255) DEFAULT NULL,
  `ギフト配送（0:希望なし/1:希望あり）` varchar(255) DEFAULT NULL,
  `合計` varchar(255) DEFAULT NULL,
  `送料(-99999=無効値)` varchar(255) DEFAULT NULL,
  `消費税(-99999=無効値)` varchar(255) DEFAULT NULL,
  `代引料(-99999=無効値)` varchar(255) DEFAULT NULL,
  `合計金額(-99999=無効値)` varchar(255) DEFAULT NULL,
  `ポイント利用有無` varchar(255) DEFAULT NULL,
  `ポイント利用額` varchar(255) DEFAULT NULL,
  `請求金額(-99999=無効値)` varchar(255) DEFAULT NULL,
  `のし` varchar(255) DEFAULT NULL,
  `決済方法` varchar(255) DEFAULT NULL,
  `クレジットカード種類` varchar(255) DEFAULT NULL,
  `クレジットカード分割選択` varchar(255) DEFAULT NULL,
  `クレジットカード分割備考` varchar(255) DEFAULT NULL,
  `カード決済ステータス` varchar(255) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `配送方法` varchar(255) DEFAULT NULL,
  `配送区分` varchar(255) DEFAULT NULL,
  `お荷物伝票番号` varchar(255) DEFAULT NULL,
  `お届け時間帯` varchar(255) DEFAULT NULL,
  `お届け日指定` varchar(255) DEFAULT NULL,
  `コメント` varchar(255) DEFAULT NULL,
  `作業メモ` varchar(255) DEFAULT NULL,
  `受注ステータス` varchar(255) DEFAULT NULL,
  `メールフラグ` varchar(255) DEFAULT NULL,
  `メール差込文(お客様へのメッセージ)` varchar(255) DEFAULT NULL,
  `同梱ID` varchar(255) DEFAULT NULL,
  `同梱ステータス` varchar(255) DEFAULT NULL,
  `同梱ポイント利用合計` varchar(11) DEFAULT NULL,
  `同梱合計金額` varchar(11) DEFAULT NULL,
  `同梱商品合計金額` varchar(11) DEFAULT NULL,
  `同梱消費税合計` varchar(11) DEFAULT NULL,
  `同梱請求金額` varchar(11) DEFAULT NULL,
  `同梱送料合計` varchar(11) DEFAULT NULL,
  `同梱代引料合計` varchar(11) DEFAULT NULL,
  `ポイント付与率` varchar(255) DEFAULT NULL,
  `付与ポイント数` varchar(11) DEFAULT NULL,
  `クーポン利用額` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（ショップ発行送料分）` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（ショップ発行商品分）` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（リクルート発行送料分）` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（リクルート発行商品分）` varchar(11) DEFAULT NULL,
  `同梱注文クーポン利用額` varchar(11) DEFAULT NULL,
  `警告表示フラグ` varchar(1) DEFAULT NULL,
  `システム利用料対象額` varchar(11) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_ppm_items_dl_tmp`
--

DROP TABLE IF EXISTS `tb_orderr_ppm_items_dl_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_ppm_items_dl_tmp` (
  `注文番号` varchar(30) DEFAULT NULL,
  `注文日時` varchar(255) DEFAULT NULL,
  `担当者` varchar(255) DEFAULT NULL,
  `注文者名字` varchar(20) DEFAULT NULL,
  `注文者名前` varchar(20) DEFAULT NULL,
  `注文者名字フリガナ` varchar(20) DEFAULT NULL,
  `注文者名前フリガナ` varchar(20) DEFAULT NULL,
  `注文者郵便番号1` varchar(3) DEFAULT NULL,
  `注文者郵便番号2` varchar(4) DEFAULT NULL,
  `注文者住所：都道府県` varchar(20) DEFAULT NULL,
  `注文者住所：市区町村以降` varchar(255) DEFAULT NULL,
  `注文者電話番号` varchar(30) DEFAULT NULL,
  `メールキャリアコード` varchar(1) DEFAULT NULL,
  `会員フラグ` varchar(1) DEFAULT NULL,
  `利用端末` varchar(1) DEFAULT NULL,
  `送付先名字` varchar(20) DEFAULT NULL,
  `送付先名前` varchar(20) DEFAULT NULL,
  `送付先名字フリガナ` varchar(20) DEFAULT NULL,
  `送付先名前フリガナ` varchar(20) DEFAULT NULL,
  `送付先郵便番号1` varchar(3) DEFAULT NULL,
  `送付先郵便番号2` varchar(4) DEFAULT NULL,
  `送付先住所：都道府県` varchar(10) DEFAULT NULL,
  `送付先住所：市区町村以降` varchar(255) DEFAULT NULL,
  `送付先電話番号` varchar(30) DEFAULT NULL,
  `送付先一致フラグ` varchar(1) DEFAULT NULL,
  `商品管理ID` varchar(50) DEFAULT NULL,
  `商品ID（表示用）` varchar(50) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品ID` varchar(50) DEFAULT NULL,
  `個数` varchar(11) DEFAULT NULL,
  `単価` varchar(11) DEFAULT NULL,
  `購入オプション` varchar(255) DEFAULT NULL,
  `送料無料・別` varchar(255) DEFAULT NULL,
  `代引手数料込別` varchar(255) DEFAULT NULL,
  `商品URL` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(255) DEFAULT NULL,
  `税込別` varchar(255) DEFAULT NULL,
  `税込別(リボン)` varchar(255) DEFAULT NULL,
  `税込別(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング種類(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング種類(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング料金(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング料金(包装紙)` varchar(255) DEFAULT NULL,
  `ギフト配送（0:希望なし/1:希望あり）` varchar(255) DEFAULT NULL,
  `合計` varchar(255) DEFAULT NULL,
  `送料(-99999=無効値)` varchar(255) DEFAULT NULL,
  `消費税(-99999=無効値)` varchar(255) DEFAULT NULL,
  `代引料(-99999=無効値)` varchar(255) DEFAULT NULL,
  `合計金額(-99999=無効値)` varchar(255) DEFAULT NULL,
  `ポイント利用有無` varchar(255) DEFAULT NULL,
  `ポイント利用額` varchar(255) DEFAULT NULL,
  `請求金額(-99999=無効値)` varchar(255) DEFAULT NULL,
  `のし` varchar(255) DEFAULT NULL,
  `決済方法` varchar(255) DEFAULT NULL,
  `クレジットカード種類` varchar(255) DEFAULT NULL,
  `クレジットカード分割選択` varchar(255) DEFAULT NULL,
  `クレジットカード分割備考` varchar(255) DEFAULT NULL,
  `カード決済ステータス` varchar(255) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `配送方法` varchar(255) DEFAULT NULL,
  `配送区分` varchar(255) DEFAULT NULL,
  `お荷物伝票番号` varchar(255) DEFAULT NULL,
  `お届け時間帯` varchar(255) DEFAULT NULL,
  `お届け日指定` varchar(255) DEFAULT NULL,
  `コメント` varchar(255) DEFAULT NULL,
  `作業メモ` varchar(255) DEFAULT NULL,
  `受注ステータス` varchar(255) DEFAULT NULL,
  `メールフラグ` varchar(255) DEFAULT NULL,
  `メール差込文(お客様へのメッセージ)` varchar(255) DEFAULT NULL,
  `同梱ID` varchar(255) DEFAULT NULL,
  `同梱ステータス` varchar(255) DEFAULT NULL,
  `同梱ポイント利用合計` varchar(11) DEFAULT NULL,
  `同梱合計金額` varchar(11) DEFAULT NULL,
  `同梱商品合計金額` varchar(11) DEFAULT NULL,
  `同梱消費税合計` varchar(11) DEFAULT NULL,
  `同梱請求金額` varchar(11) DEFAULT NULL,
  `同梱送料合計` varchar(11) DEFAULT NULL,
  `同梱代引料合計` varchar(11) DEFAULT NULL,
  `ポイント付与率` varchar(255) DEFAULT NULL,
  `付与ポイント数` varchar(11) DEFAULT NULL,
  `クーポン利用額` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（ショップ発行送料分）` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（ショップ発行商品分）` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（リクルート発行送料分）` varchar(11) DEFAULT NULL,
  `クーポン利用額内訳（リクルート発行商品分）` varchar(11) DEFAULT NULL,
  `同梱注文クーポン利用額` varchar(11) DEFAULT NULL,
  `警告表示フラグ` varchar(1) DEFAULT NULL,
  `システム利用料対象額` varchar(11) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_rakuten_items_dl`
--

DROP TABLE IF EXISTS `tb_orderr_rakuten_items_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_rakuten_items_dl` (
  `受注番号` varchar(30) DEFAULT NULL,
  `受注ステータス` varchar(20) DEFAULT NULL,
  `カード決済ステータス` varchar(20) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `配送日` varchar(255) DEFAULT NULL,
  `お届け時間帯` varchar(255) DEFAULT NULL,
  `お届け日指定` varchar(255) DEFAULT NULL,
  `担当者` varchar(255) DEFAULT NULL,
  `ひとことメモ` varchar(255) DEFAULT NULL,
  `メール差込文(お客様へのメッセージ)` varchar(255) DEFAULT NULL,
  `初期購入合計金額` varchar(255) DEFAULT NULL,
  `利用端末` varchar(255) DEFAULT NULL,
  `メールキャリアコード` varchar(1) DEFAULT NULL,
  `ギフトチェック（0:なし/1:あり）` varchar(1) DEFAULT NULL,
  `コメント` mediumtext,
  `注文日時` varchar(255) DEFAULT NULL,
  `複数送付先フラグ` varchar(1) DEFAULT NULL,
  `警告表示フラグ` varchar(1) DEFAULT NULL,
  `楽天会員フラグ` varchar(1) DEFAULT NULL,
  `合計` varchar(11) DEFAULT NULL,
  `消費税(-99999=無効値)` varchar(11) DEFAULT NULL,
  `送料(-99999=無効値)` varchar(11) DEFAULT NULL,
  `代引料(-99999=無効値)` varchar(11) DEFAULT NULL,
  `請求金額(-99999=無効値)` varchar(11) DEFAULT NULL,
  `合計金額(-99999=無効値)` varchar(11) DEFAULT NULL,
  `同梱ID` varchar(255) DEFAULT NULL,
  `同梱ステータス` varchar(255) DEFAULT NULL,
  `同梱商品合計金額` varchar(11) DEFAULT NULL,
  `同梱送料合計` varchar(11) DEFAULT NULL,
  `同梱代引料合計` varchar(11) DEFAULT NULL,
  `同梱消費税合計` varchar(11) DEFAULT NULL,
  `同梱請求金額` varchar(11) DEFAULT NULL,
  `同梱合計金額` varchar(11) DEFAULT NULL,
  `同梱楽天バンク決済振替手数料` varchar(11) DEFAULT NULL,
  `同梱ポイント利用合計` varchar(11) DEFAULT NULL,
  `メールフラグ` varchar(255) DEFAULT NULL,
  `注文日` varchar(255) DEFAULT NULL,
  `注文時間` varchar(255) DEFAULT NULL,
  `モバイルキャリア決済番号` varchar(255) DEFAULT NULL,
  `購入履歴修正可否タイプ` varchar(255) DEFAULT NULL,
  `購入履歴修正アイコンフラグ` varchar(1) DEFAULT NULL,
  `購入履歴修正催促メールフラグ` varchar(1) DEFAULT NULL,
  `送付先一致フラグ` varchar(1) DEFAULT NULL,
  `ポイント利用有無` varchar(255) DEFAULT NULL,
  `注文者郵便番号１` varchar(3) DEFAULT NULL,
  `注文者郵便番号２` varchar(4) DEFAULT NULL,
  `注文者住所：都道府県` varchar(10) DEFAULT NULL,
  `注文者住所：都市区` varchar(30) DEFAULT NULL,
  `注文者住所：町以降` varchar(50) DEFAULT NULL,
  `注文者名字` varchar(20) DEFAULT NULL,
  `注文者名前` varchar(20) DEFAULT NULL,
  `注文者名字フリガナ` varchar(20) DEFAULT NULL,
  `注文者名前フリガナ` varchar(20) DEFAULT NULL,
  `注文者電話番号１` varchar(5) DEFAULT NULL,
  `注文者電話番号２` varchar(5) DEFAULT NULL,
  `注文者電話番号３` varchar(5) DEFAULT NULL,
  `メールアドレス` varchar(255) DEFAULT NULL,
  `注文者性別` varchar(255) DEFAULT NULL,
  `注文者誕生日` varchar(255) DEFAULT NULL,
  `決済方法` varchar(255) DEFAULT NULL,
  `クレジットカード種類` varchar(255) DEFAULT NULL,
  `クレジットカード番号` varchar(255) DEFAULT NULL,
  `クレジットカード名義人` varchar(255) DEFAULT NULL,
  `クレジットカード有効期限` varchar(255) DEFAULT NULL,
  `クレジットカード分割選択` varchar(255) DEFAULT NULL,
  `クレジットカード分割備考` varchar(255) DEFAULT NULL,
  `配送方法` varchar(255) DEFAULT NULL,
  `配送区分` varchar(255) DEFAULT NULL,
  `ポイント利用額` varchar(255) DEFAULT NULL,
  `ポイント利用条件` varchar(255) DEFAULT NULL,
  `ポイントステータス` varchar(255) DEFAULT NULL,
  `楽天バンク決済ステータス` varchar(255) DEFAULT NULL,
  `楽天バンク振替手数料負担区分` varchar(255) DEFAULT NULL,
  `楽天バンク決済手数料` varchar(255) DEFAULT NULL,
  `ラッピングタイトル(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング名(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング料金(包装紙)` varchar(255) DEFAULT NULL,
  `税込別(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピングタイトル(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング名(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング料金(リボン)` varchar(255) DEFAULT NULL,
  `税込別(リボン)` varchar(255) DEFAULT NULL,
  `送付先送料` varchar(255) DEFAULT NULL,
  `送付先代引料` varchar(255) DEFAULT NULL,
  `送付先消費税` varchar(255) DEFAULT NULL,
  `お荷物伝票番号` varchar(255) DEFAULT NULL,
  `送付先商品合計金額` varchar(255) DEFAULT NULL,
  `のし` varchar(255) DEFAULT NULL,
  `送付先郵便番号１` varchar(3) DEFAULT NULL,
  `送付先郵便番号２` varchar(4) DEFAULT NULL,
  `送付先住所：都道府県` varchar(255) DEFAULT NULL,
  `送付先住所：都市区` varchar(255) DEFAULT NULL,
  `送付先住所：町以降` varchar(255) DEFAULT NULL,
  `送付先名字` varchar(255) DEFAULT NULL,
  `送付先名前` varchar(255) DEFAULT NULL,
  `送付先名字フリガナ` varchar(255) DEFAULT NULL,
  `送付先名前フリガナ` varchar(255) DEFAULT NULL,
  `送付先電話番号１` varchar(5) DEFAULT NULL,
  `送付先電話番号２` varchar(5) DEFAULT NULL,
  `送付先電話番号３` varchar(5) DEFAULT NULL,
  `商品ID` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品番号` varchar(255) DEFAULT NULL,
  `商品URL` varchar(255) DEFAULT NULL,
  `単価` varchar(255) DEFAULT NULL,
  `個数` varchar(255) DEFAULT NULL,
  `送料込別` varchar(255) DEFAULT NULL,
  `税込別` varchar(255) DEFAULT NULL,
  `代引手数料込別` varchar(255) DEFAULT NULL,
  `項目・選択肢` varchar(255) DEFAULT NULL,
  `ポイント倍率` varchar(255) DEFAULT NULL,
  `ポイントタイプ` varchar(255) DEFAULT NULL,
  `レコードナンバー` varchar(255) DEFAULT NULL,
  `納期情報` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(1) DEFAULT NULL,
  `ラッピング種類(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング種類(リボン)` varchar(255) DEFAULT NULL,
  `あす楽希望` varchar(1) DEFAULT NULL,
  `クーポン利用額` varchar(255) DEFAULT NULL,
  `店舗発行クーポン利用額` varchar(255) DEFAULT NULL,
  `楽天発行クーポン利用額` varchar(255) DEFAULT NULL,
  `同梱注文クーポン利用額` varchar(255) DEFAULT NULL,
  `配送会社` varchar(255) DEFAULT NULL,
  `薬事フラグ` varchar(1) DEFAULT NULL,
  `楽天スーパーDEAL` varchar(1) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_rakuten_items_dl_bak`
--

DROP TABLE IF EXISTS `tb_orderr_rakuten_items_dl_bak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_rakuten_items_dl_bak` (
  `受注番号` varchar(30) DEFAULT NULL,
  `受注ステータス` varchar(20) DEFAULT NULL,
  `カード決済ステータス` varchar(20) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `配送日` varchar(255) DEFAULT NULL,
  `お届け時間帯` varchar(255) DEFAULT NULL,
  `お届け日指定` varchar(255) DEFAULT NULL,
  `担当者` varchar(255) DEFAULT NULL,
  `ひとことメモ` varchar(255) DEFAULT NULL,
  `メール差込文(お客様へのメッセージ)` varchar(255) DEFAULT NULL,
  `初期購入合計金額` varchar(255) DEFAULT NULL,
  `利用端末` varchar(255) DEFAULT NULL,
  `メールキャリアコード` varchar(1) DEFAULT NULL,
  `ギフトチェック（0:なし/1:あり）` varchar(1) DEFAULT NULL,
  `コメント` mediumtext,
  `注文日時` varchar(255) DEFAULT NULL,
  `複数送付先フラグ` varchar(1) DEFAULT NULL,
  `警告表示フラグ` varchar(1) DEFAULT NULL,
  `楽天会員フラグ` varchar(1) DEFAULT NULL,
  `合計` varchar(11) DEFAULT NULL,
  `消費税(-99999=無効値)` varchar(11) DEFAULT NULL,
  `送料(-99999=無効値)` varchar(11) DEFAULT NULL,
  `代引料(-99999=無効値)` varchar(11) DEFAULT NULL,
  `請求金額(-99999=無効値)` varchar(11) DEFAULT NULL,
  `合計金額(-99999=無効値)` varchar(11) DEFAULT NULL,
  `同梱ID` varchar(255) DEFAULT NULL,
  `同梱ステータス` varchar(255) DEFAULT NULL,
  `同梱商品合計金額` varchar(11) DEFAULT NULL,
  `同梱送料合計` varchar(11) DEFAULT NULL,
  `同梱代引料合計` varchar(11) DEFAULT NULL,
  `同梱消費税合計` varchar(11) DEFAULT NULL,
  `同梱請求金額` varchar(11) DEFAULT NULL,
  `同梱合計金額` varchar(11) DEFAULT NULL,
  `同梱楽天バンク決済振替手数料` varchar(11) DEFAULT NULL,
  `同梱ポイント利用合計` varchar(11) DEFAULT NULL,
  `メールフラグ` varchar(255) DEFAULT NULL,
  `注文日` varchar(255) DEFAULT NULL,
  `注文時間` varchar(255) DEFAULT NULL,
  `モバイルキャリア決済番号` varchar(255) DEFAULT NULL,
  `購入履歴修正可否タイプ` varchar(255) DEFAULT NULL,
  `購入履歴修正アイコンフラグ` varchar(1) DEFAULT NULL,
  `購入履歴修正催促メールフラグ` varchar(1) DEFAULT NULL,
  `送付先一致フラグ` varchar(1) DEFAULT NULL,
  `ポイント利用有無` varchar(255) DEFAULT NULL,
  `注文者郵便番号１` varchar(3) DEFAULT NULL,
  `注文者郵便番号２` varchar(4) DEFAULT NULL,
  `注文者住所：都道府県` varchar(10) DEFAULT NULL,
  `注文者住所：都市区` varchar(30) DEFAULT NULL,
  `注文者住所：町以降` varchar(50) DEFAULT NULL,
  `注文者名字` varchar(20) DEFAULT NULL,
  `注文者名前` varchar(20) DEFAULT NULL,
  `注文者名字フリガナ` varchar(20) DEFAULT NULL,
  `注文者名前フリガナ` varchar(20) DEFAULT NULL,
  `注文者電話番号１` varchar(5) DEFAULT NULL,
  `注文者電話番号２` varchar(5) DEFAULT NULL,
  `注文者電話番号３` varchar(5) DEFAULT NULL,
  `メールアドレス` varchar(255) DEFAULT NULL,
  `注文者性別` varchar(255) DEFAULT NULL,
  `注文者誕生日` varchar(255) DEFAULT NULL,
  `決済方法` varchar(255) DEFAULT NULL,
  `クレジットカード種類` varchar(255) DEFAULT NULL,
  `クレジットカード番号` varchar(255) DEFAULT NULL,
  `クレジットカード名義人` varchar(255) DEFAULT NULL,
  `クレジットカード有効期限` varchar(255) DEFAULT NULL,
  `クレジットカード分割選択` varchar(255) DEFAULT NULL,
  `クレジットカード分割備考` varchar(255) DEFAULT NULL,
  `配送方法` varchar(255) DEFAULT NULL,
  `配送区分` varchar(255) DEFAULT NULL,
  `ポイント利用額` varchar(255) DEFAULT NULL,
  `ポイント利用条件` varchar(255) DEFAULT NULL,
  `ポイントステータス` varchar(255) DEFAULT NULL,
  `楽天バンク決済ステータス` varchar(255) DEFAULT NULL,
  `楽天バンク振替手数料負担区分` varchar(255) DEFAULT NULL,
  `楽天バンク決済手数料` varchar(255) DEFAULT NULL,
  `ラッピングタイトル(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング名(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング料金(包装紙)` varchar(255) DEFAULT NULL,
  `税込別(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピングタイトル(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング名(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング料金(リボン)` varchar(255) DEFAULT NULL,
  `税込別(リボン)` varchar(255) DEFAULT NULL,
  `送付先送料` varchar(255) DEFAULT NULL,
  `送付先代引料` varchar(255) DEFAULT NULL,
  `送付先消費税` varchar(255) DEFAULT NULL,
  `お荷物伝票番号` varchar(255) DEFAULT NULL,
  `送付先商品合計金額` varchar(255) DEFAULT NULL,
  `のし` varchar(255) DEFAULT NULL,
  `送付先郵便番号１` varchar(3) DEFAULT NULL,
  `送付先郵便番号２` varchar(4) DEFAULT NULL,
  `送付先住所：都道府県` varchar(255) DEFAULT NULL,
  `送付先住所：都市区` varchar(255) DEFAULT NULL,
  `送付先住所：町以降` varchar(255) DEFAULT NULL,
  `送付先名字` varchar(255) DEFAULT NULL,
  `送付先名前` varchar(255) DEFAULT NULL,
  `送付先名字フリガナ` varchar(255) DEFAULT NULL,
  `送付先名前フリガナ` varchar(255) DEFAULT NULL,
  `送付先電話番号１` varchar(5) DEFAULT NULL,
  `送付先電話番号２` varchar(5) DEFAULT NULL,
  `送付先電話番号３` varchar(5) DEFAULT NULL,
  `商品ID` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品番号` varchar(255) DEFAULT NULL,
  `商品URL` varchar(255) DEFAULT NULL,
  `単価` varchar(255) DEFAULT NULL,
  `個数` varchar(255) DEFAULT NULL,
  `送料込別` varchar(255) DEFAULT NULL,
  `税込別` varchar(255) DEFAULT NULL,
  `代引手数料込別` varchar(255) DEFAULT NULL,
  `項目・選択肢` varchar(255) DEFAULT NULL,
  `ポイント倍率` varchar(255) DEFAULT NULL,
  `ポイントタイプ` varchar(255) DEFAULT NULL,
  `レコードナンバー` varchar(255) DEFAULT NULL,
  `納期情報` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(1) DEFAULT NULL,
  `ラッピング種類(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング種類(リボン)` varchar(255) DEFAULT NULL,
  `あす楽希望` varchar(1) DEFAULT NULL,
  `クーポン利用額` varchar(255) DEFAULT NULL,
  `店舗発行クーポン利用額` varchar(255) DEFAULT NULL,
  `楽天発行クーポン利用額` varchar(255) DEFAULT NULL,
  `同梱注文クーポン利用額` varchar(255) DEFAULT NULL,
  `配送会社` varchar(255) DEFAULT NULL,
  `薬事フラグ` varchar(1) DEFAULT NULL,
  `楽天スーパーDEAL` varchar(1) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_rakuten_items_dl_tmp`
--

DROP TABLE IF EXISTS `tb_orderr_rakuten_items_dl_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_rakuten_items_dl_tmp` (
  `受注番号` varchar(30) DEFAULT NULL,
  `受注ステータス` varchar(20) DEFAULT NULL,
  `カード決済ステータス` varchar(20) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `配送日` varchar(255) DEFAULT NULL,
  `お届け時間帯` varchar(255) DEFAULT NULL,
  `お届け日指定` varchar(255) DEFAULT NULL,
  `担当者` varchar(255) DEFAULT NULL,
  `ひとことメモ` varchar(255) DEFAULT NULL,
  `メール差込文(お客様へのメッセージ)` varchar(255) DEFAULT NULL,
  `初期購入合計金額` varchar(255) DEFAULT NULL,
  `利用端末` varchar(255) DEFAULT NULL,
  `メールキャリアコード` varchar(1) DEFAULT NULL,
  `ギフトチェック（0:なし/1:あり）` varchar(1) DEFAULT NULL,
  `コメント` mediumtext,
  `注文日時` varchar(255) DEFAULT NULL,
  `複数送付先フラグ` varchar(1) DEFAULT NULL,
  `警告表示フラグ` varchar(1) DEFAULT NULL,
  `楽天会員フラグ` varchar(1) DEFAULT NULL,
  `合計` varchar(11) DEFAULT NULL,
  `消費税(-99999=無効値)` varchar(11) DEFAULT NULL,
  `送料(-99999=無効値)` varchar(11) DEFAULT NULL,
  `代引料(-99999=無効値)` varchar(11) DEFAULT NULL,
  `請求金額(-99999=無効値)` varchar(11) DEFAULT NULL,
  `合計金額(-99999=無効値)` varchar(11) DEFAULT NULL,
  `同梱ID` varchar(255) DEFAULT NULL,
  `同梱ステータス` varchar(255) DEFAULT NULL,
  `同梱商品合計金額` varchar(11) DEFAULT NULL,
  `同梱送料合計` varchar(11) DEFAULT NULL,
  `同梱代引料合計` varchar(11) DEFAULT NULL,
  `同梱消費税合計` varchar(11) DEFAULT NULL,
  `同梱請求金額` varchar(11) DEFAULT NULL,
  `同梱合計金額` varchar(11) DEFAULT NULL,
  `同梱楽天バンク決済振替手数料` varchar(11) DEFAULT NULL,
  `同梱ポイント利用合計` varchar(11) DEFAULT NULL,
  `メールフラグ` varchar(255) DEFAULT NULL,
  `注文日` varchar(255) DEFAULT NULL,
  `注文時間` varchar(255) DEFAULT NULL,
  `モバイルキャリア決済番号` varchar(255) DEFAULT NULL,
  `購入履歴修正可否タイプ` varchar(255) DEFAULT NULL,
  `購入履歴修正アイコンフラグ` varchar(1) DEFAULT NULL,
  `購入履歴修正催促メールフラグ` varchar(1) DEFAULT NULL,
  `送付先一致フラグ` varchar(1) DEFAULT NULL,
  `ポイント利用有無` varchar(255) DEFAULT NULL,
  `注文者郵便番号１` varchar(3) DEFAULT NULL,
  `注文者郵便番号２` varchar(4) DEFAULT NULL,
  `注文者住所：都道府県` varchar(10) DEFAULT NULL,
  `注文者住所：都市区` varchar(30) DEFAULT NULL,
  `注文者住所：町以降` varchar(50) DEFAULT NULL,
  `注文者名字` varchar(20) DEFAULT NULL,
  `注文者名前` varchar(20) DEFAULT NULL,
  `注文者名字フリガナ` varchar(20) DEFAULT NULL,
  `注文者名前フリガナ` varchar(20) DEFAULT NULL,
  `注文者電話番号１` varchar(5) DEFAULT NULL,
  `注文者電話番号２` varchar(5) DEFAULT NULL,
  `注文者電話番号３` varchar(5) DEFAULT NULL,
  `メールアドレス` varchar(255) DEFAULT NULL,
  `注文者性別` varchar(255) DEFAULT NULL,
  `注文者誕生日` varchar(255) DEFAULT NULL,
  `決済方法` varchar(255) DEFAULT NULL,
  `クレジットカード種類` varchar(255) DEFAULT NULL,
  `クレジットカード番号` varchar(255) DEFAULT NULL,
  `クレジットカード名義人` varchar(255) DEFAULT NULL,
  `クレジットカード有効期限` varchar(255) DEFAULT NULL,
  `クレジットカード分割選択` varchar(255) DEFAULT NULL,
  `クレジットカード分割備考` varchar(255) DEFAULT NULL,
  `配送方法` varchar(255) DEFAULT NULL,
  `配送区分` varchar(255) DEFAULT NULL,
  `ポイント利用額` varchar(255) DEFAULT NULL,
  `ポイント利用条件` varchar(255) DEFAULT NULL,
  `ポイントステータス` varchar(255) DEFAULT NULL,
  `楽天バンク決済ステータス` varchar(255) DEFAULT NULL,
  `楽天バンク振替手数料負担区分` varchar(255) DEFAULT NULL,
  `楽天バンク決済手数料` varchar(255) DEFAULT NULL,
  `ラッピングタイトル(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング名(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング料金(包装紙)` varchar(255) DEFAULT NULL,
  `税込別(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピングタイトル(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング名(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング料金(リボン)` varchar(255) DEFAULT NULL,
  `税込別(リボン)` varchar(255) DEFAULT NULL,
  `送付先送料` varchar(255) DEFAULT NULL,
  `送付先代引料` varchar(255) DEFAULT NULL,
  `送付先消費税` varchar(255) DEFAULT NULL,
  `お荷物伝票番号` varchar(255) DEFAULT NULL,
  `送付先商品合計金額` varchar(255) DEFAULT NULL,
  `のし` varchar(255) DEFAULT NULL,
  `送付先郵便番号１` varchar(3) DEFAULT NULL,
  `送付先郵便番号２` varchar(4) DEFAULT NULL,
  `送付先住所：都道府県` varchar(255) DEFAULT NULL,
  `送付先住所：都市区` varchar(255) DEFAULT NULL,
  `送付先住所：町以降` varchar(255) DEFAULT NULL,
  `送付先名字` varchar(255) DEFAULT NULL,
  `送付先名前` varchar(255) DEFAULT NULL,
  `送付先名字フリガナ` varchar(255) DEFAULT NULL,
  `送付先名前フリガナ` varchar(255) DEFAULT NULL,
  `送付先電話番号１` varchar(5) DEFAULT NULL,
  `送付先電話番号２` varchar(5) DEFAULT NULL,
  `送付先電話番号３` varchar(5) DEFAULT NULL,
  `商品ID` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品番号` varchar(255) DEFAULT NULL,
  `商品URL` varchar(255) DEFAULT NULL,
  `単価` varchar(255) DEFAULT NULL,
  `個数` varchar(255) DEFAULT NULL,
  `送料込別` varchar(255) DEFAULT NULL,
  `税込別` varchar(255) DEFAULT NULL,
  `代引手数料込別` varchar(255) DEFAULT NULL,
  `項目・選択肢` varchar(255) DEFAULT NULL,
  `ポイント倍率` varchar(255) DEFAULT NULL,
  `ポイントタイプ` varchar(255) DEFAULT NULL,
  `レコードナンバー` varchar(255) DEFAULT NULL,
  `納期情報` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(1) DEFAULT NULL,
  `ラッピング種類(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング種類(リボン)` varchar(255) DEFAULT NULL,
  `あす楽希望` varchar(1) DEFAULT NULL,
  `クーポン利用額` varchar(255) DEFAULT NULL,
  `店舗発行クーポン利用額` varchar(255) DEFAULT NULL,
  `楽天発行クーポン利用額` varchar(255) DEFAULT NULL,
  `同梱注文クーポン利用額` varchar(255) DEFAULT NULL,
  `配送会社` varchar(255) DEFAULT NULL,
  `薬事フラグ` varchar(1) DEFAULT NULL,
  `楽天スーパーDEAL` varchar(1) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_yahoo_ne_items_dl`
--

DROP TABLE IF EXISTS `tb_orderr_yahoo_ne_items_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_yahoo_ne_items_dl` (
  `OrderId` varchar(255) DEFAULT NULL,
  `LineId` varchar(255) DEFAULT NULL,
  `Quantity` varchar(255) DEFAULT NULL,
  `ItemId` varchar(255) DEFAULT NULL,
  `SubCode` varchar(255) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `ItemOptionName` varchar(255) DEFAULT NULL,
  `ItemOptionValue` varchar(255) DEFAULT NULL,
  `SubCodeOption` varchar(255) DEFAULT NULL,
  `InscriptionName` varchar(255) DEFAULT NULL,
  `InscriptionValue` varchar(255) DEFAULT NULL,
  `UnitPrice` varchar(255) DEFAULT NULL,
  `UnitGetPoint` varchar(255) DEFAULT NULL,
  `LineSubTotal` varchar(255) DEFAULT NULL,
  `LineGetPoint` varchar(255) DEFAULT NULL,
  `PointFspCode` varchar(255) DEFAULT NULL,
  `Condition` varchar(255) DEFAULT NULL,
  `CouponId` varchar(255) DEFAULT NULL,
  `CouponDiscount` varchar(255) DEFAULT NULL,
  `OriginalPrice` varchar(255) DEFAULT NULL,
  `IsGetPointFix` varchar(255) DEFAULT NULL,
  `GetPointFixDate` varchar(255) DEFAULT NULL,
  `ReleaseDate` varchar(255) DEFAULT NULL,
  `GetPointType` varchar(255) DEFAULT NULL,
  `Jan` varchar(255) DEFAULT NULL,
  `ProductId` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_yahoo_ne_items_dl_bak`
--

DROP TABLE IF EXISTS `tb_orderr_yahoo_ne_items_dl_bak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_yahoo_ne_items_dl_bak` (
  `OrderId` varchar(255) DEFAULT NULL,
  `LineId` varchar(255) DEFAULT NULL,
  `Quantity` varchar(255) DEFAULT NULL,
  `ItemId` varchar(255) DEFAULT NULL,
  `SubCode` varchar(255) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `ItemOptionName` varchar(255) DEFAULT NULL,
  `ItemOptionValue` varchar(255) DEFAULT NULL,
  `SubCodeOption` varchar(255) DEFAULT NULL,
  `InscriptionName` varchar(255) DEFAULT NULL,
  `InscriptionValue` varchar(255) DEFAULT NULL,
  `UnitPrice` varchar(255) DEFAULT NULL,
  `UnitGetPoint` varchar(255) DEFAULT NULL,
  `LineSubTotal` varchar(255) DEFAULT NULL,
  `LineGetPoint` varchar(255) DEFAULT NULL,
  `PointFspCode` varchar(255) DEFAULT NULL,
  `Condition` varchar(255) DEFAULT NULL,
  `CouponId` varchar(255) DEFAULT NULL,
  `CouponDiscount` varchar(255) DEFAULT NULL,
  `OriginalPrice` varchar(255) DEFAULT NULL,
  `IsGetPointFix` varchar(255) DEFAULT NULL,
  `GetPointFixDate` varchar(255) DEFAULT NULL,
  `ReleaseDate` varchar(255) DEFAULT NULL,
  `GetPointType` varchar(255) DEFAULT NULL,
  `Jan` varchar(255) DEFAULT NULL,
  `ProductId` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_yahoo_ne_items_dl_tmp`
--

DROP TABLE IF EXISTS `tb_orderr_yahoo_ne_items_dl_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_yahoo_ne_items_dl_tmp` (
  `OrderId` varchar(255) DEFAULT NULL,
  `LineId` varchar(255) DEFAULT NULL,
  `Quantity` varchar(255) DEFAULT NULL,
  `ItemId` varchar(255) DEFAULT NULL,
  `SubCode` varchar(255) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `ItemOptionName` varchar(255) DEFAULT NULL,
  `ItemOptionValue` varchar(255) DEFAULT NULL,
  `SubCodeOption` varchar(255) DEFAULT NULL,
  `InscriptionName` varchar(255) DEFAULT NULL,
  `InscriptionValue` varchar(255) DEFAULT NULL,
  `UnitPrice` varchar(255) DEFAULT NULL,
  `UnitGetPoint` varchar(255) DEFAULT NULL,
  `LineSubTotal` varchar(255) DEFAULT NULL,
  `LineGetPoint` varchar(255) DEFAULT NULL,
  `PointFspCode` varchar(255) DEFAULT NULL,
  `Condition` varchar(255) DEFAULT NULL,
  `CouponId` varchar(255) DEFAULT NULL,
  `CouponDiscount` varchar(255) DEFAULT NULL,
  `OriginalPrice` varchar(255) DEFAULT NULL,
  `IsGetPointFix` varchar(255) DEFAULT NULL,
  `GetPointFixDate` varchar(255) DEFAULT NULL,
  `ReleaseDate` varchar(255) DEFAULT NULL,
  `GetPointType` varchar(255) DEFAULT NULL,
  `Jan` varchar(255) DEFAULT NULL,
  `ProductId` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `即納F` tinyint(1) NOT NULL DEFAULT '0',
  `一部即納F` tinyint(1) NOT NULL DEFAULT '0',
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `出荷予定日` varchar(20) NOT NULL,
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_yahoo_ne_items_temp`
--

DROP TABLE IF EXISTS `tb_orderr_yahoo_ne_items_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_yahoo_ne_items_temp` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `OrderId` varchar(255) DEFAULT NULL,
  `LineId` varchar(255) DEFAULT NULL,
  `ItemOptionName` varchar(255) DEFAULT NULL,
  `ItemOptionValue` varchar(255) DEFAULT NULL,
  `Sort` int(11) DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_yahoo_ne_orders_dl`
--

DROP TABLE IF EXISTS `tb_orderr_yahoo_ne_orders_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_yahoo_ne_orders_dl` (
  `OrderId` varchar(20) DEFAULT NULL,
  `ParentOrderId` varchar(20) DEFAULT NULL,
  `DeviceType` varchar(1) DEFAULT NULL,
  `IsAffiliate` varchar(1) DEFAULT NULL,
  `FspLicenseCode` varchar(255) DEFAULT NULL,
  `FspLicenseName` varchar(255) DEFAULT NULL,
  `OrderTime` varchar(20) DEFAULT NULL,
  `OrderTimeUnixEpoch` varchar(20) DEFAULT NULL,
  `UsePointType` varchar(255) DEFAULT NULL,
  `OrderStatus` varchar(255) DEFAULT NULL,
  `StoreStatus` varchar(255) DEFAULT NULL,
  `Referer` varchar(255) DEFAULT NULL,
  `EntryPoint` varchar(255) DEFAULT NULL,
  `Clink` varchar(255) DEFAULT NULL,
  `SuspectMessage` varchar(255) DEFAULT NULL,
  `IsItemCoupon` varchar(1) DEFAULT NULL,
  `IsShippingCoupon` varchar(1) DEFAULT NULL,
  `ShipName` varchar(255) DEFAULT NULL,
  `ShipFirstName` varchar(50) DEFAULT NULL,
  `ShipLastName` varchar(50) DEFAULT NULL,
  `ShipAddress1` varchar(255) DEFAULT NULL,
  `ShipAddress2` varchar(255) DEFAULT NULL,
  `ShipCity` varchar(50) DEFAULT NULL,
  `ShipPrefecture` varchar(50) DEFAULT NULL,
  `ShipZipCode` varchar(20) DEFAULT NULL,
  `ShipNameKana` varchar(50) DEFAULT NULL,
  `ShipFirstNameKana` varchar(50) DEFAULT NULL,
  `ShipLastNameKana` varchar(50) DEFAULT NULL,
  `ShipAddress1Kana` varchar(255) DEFAULT NULL,
  `ShipAddress2Kana` varchar(255) DEFAULT NULL,
  `ShipCityKana` varchar(50) DEFAULT NULL,
  `ShipPrefectureKana` varchar(50) DEFAULT NULL,
  `ShipSection1Field` varchar(255) DEFAULT NULL,
  `ShipSection1Value` varchar(255) DEFAULT NULL,
  `ShipSection2Field` varchar(255) DEFAULT NULL,
  `ShipSection2Value` varchar(255) DEFAULT NULL,
  `ShipPhoneNumber` varchar(20) DEFAULT NULL,
  `ShipEmgPhoneNumber` varchar(20) DEFAULT NULL,
  `ShipMethod` varchar(255) DEFAULT NULL,
  `ShipMethodName` varchar(255) DEFAULT NULL,
  `ShipRequestDate` varchar(255) DEFAULT NULL,
  `ShipRequestTime` varchar(255) DEFAULT NULL,
  `ShipNotes` varchar(255) DEFAULT NULL,
  `ArriveType` varchar(255) DEFAULT NULL,
  `ShipInvoiceNumber1` varchar(255) DEFAULT NULL,
  `ShipInvoiceNumber2` varchar(255) DEFAULT NULL,
  `ShipUrl` varchar(255) DEFAULT NULL,
  `ShipDate` varchar(255) DEFAULT NULL,
  `GiftWrapType` varchar(255) DEFAULT NULL,
  `GiftWrapPaperType` varchar(255) DEFAULT NULL,
  `GiftWrapName` varchar(255) DEFAULT NULL,
  `NeedBillSlip` varchar(255) DEFAULT NULL,
  `NeedDetailedSlip` varchar(255) DEFAULT NULL,
  `NeedReceipt` varchar(255) DEFAULT NULL,
  `Option1Field` varchar(255) DEFAULT NULL,
  `Option1Value` varchar(255) DEFAULT NULL,
  `Option2Field` varchar(255) DEFAULT NULL,
  `Option2Value` varchar(255) DEFAULT NULL,
  `GiftWrapMessage` varchar(255) DEFAULT NULL,
  `BillName` varchar(255) DEFAULT NULL,
  `BillFirstName` varchar(255) DEFAULT NULL,
  `BillLastName` varchar(255) DEFAULT NULL,
  `BillAddress1` varchar(255) DEFAULT NULL,
  `BillAddress2` varchar(255) DEFAULT NULL,
  `BillCity` varchar(255) DEFAULT NULL,
  `BillPrefecture` varchar(255) DEFAULT NULL,
  `BillZipCode` varchar(255) DEFAULT NULL,
  `BillNameKana` varchar(255) DEFAULT NULL,
  `BillFirstNameKana` varchar(255) DEFAULT NULL,
  `BillLastNameKana` varchar(255) DEFAULT NULL,
  `BillAddress1Kana` varchar(255) DEFAULT NULL,
  `BillAddress2Kana` varchar(255) DEFAULT NULL,
  `BillCityKana` varchar(255) DEFAULT NULL,
  `BillPrefectureKana` varchar(255) DEFAULT NULL,
  `BillSection1Field` varchar(255) DEFAULT NULL,
  `BillSection1Value` varchar(255) DEFAULT NULL,
  `BillSection2Field` varchar(255) DEFAULT NULL,
  `BillSection2Value` varchar(255) DEFAULT NULL,
  `BillPhoneNumber` varchar(255) DEFAULT NULL,
  `BillEmgPhoneNumber` varchar(255) DEFAULT NULL,
  `BillMailAddress` varchar(255) DEFAULT NULL,
  `PayMethod` varchar(255) DEFAULT NULL,
  `PayMethodName` varchar(255) DEFAULT NULL,
  `PayKind` varchar(255) DEFAULT NULL,
  `CardPayCount` varchar(255) DEFAULT NULL,
  `CardPayType` varchar(255) DEFAULT NULL,
  `SettleStatus` varchar(255) DEFAULT NULL,
  `SettleId` varchar(255) DEFAULT NULL,
  `PayNo` varchar(255) DEFAULT NULL,
  `PayNoIssueDate` varchar(255) DEFAULT NULL,
  `PayDate` varchar(255) DEFAULT NULL,
  `BuyerComments` varchar(255) DEFAULT NULL,
  `AgeConfirm` varchar(255) DEFAULT NULL,
  `QuantityDetail` varchar(255) DEFAULT NULL,
  `ShipCharge` varchar(11) DEFAULT NULL,
  `PayCharge` varchar(11) DEFAULT NULL,
  `GiftWrapCharge` varchar(255) DEFAULT NULL,
  `Discount` varchar(11) DEFAULT NULL,
  `UsePoint` varchar(11) DEFAULT NULL,
  `GetPoint` varchar(11) DEFAULT NULL,
  `Total` varchar(11) DEFAULT NULL,
  `TotalPrice` varchar(11) DEFAULT NULL,
  `ShippingCouponDiscount` varchar(255) DEFAULT NULL,
  `ItemCouponDiscount` varchar(255) DEFAULT NULL,
  `TotalMallCouponDiscount` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_yahoo_ne_orders_dl_bak`
--

DROP TABLE IF EXISTS `tb_orderr_yahoo_ne_orders_dl_bak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_yahoo_ne_orders_dl_bak` (
  `OrderId` varchar(20) DEFAULT NULL,
  `ParentOrderId` varchar(20) DEFAULT NULL,
  `DeviceType` varchar(1) DEFAULT NULL,
  `IsAffiliate` varchar(1) DEFAULT NULL,
  `FspLicenseCode` varchar(255) DEFAULT NULL,
  `FspLicenseName` varchar(255) DEFAULT NULL,
  `OrderTime` varchar(20) DEFAULT NULL,
  `OrderTimeUnixEpoch` varchar(20) DEFAULT NULL,
  `UsePointType` varchar(255) DEFAULT NULL,
  `OrderStatus` varchar(255) DEFAULT NULL,
  `StoreStatus` varchar(255) DEFAULT NULL,
  `Referer` varchar(255) DEFAULT NULL,
  `EntryPoint` varchar(255) DEFAULT NULL,
  `Clink` varchar(255) DEFAULT NULL,
  `SuspectMessage` varchar(255) DEFAULT NULL,
  `IsItemCoupon` varchar(1) DEFAULT NULL,
  `IsShippingCoupon` varchar(1) DEFAULT NULL,
  `ShipName` varchar(255) DEFAULT NULL,
  `ShipFirstName` varchar(50) DEFAULT NULL,
  `ShipLastName` varchar(50) DEFAULT NULL,
  `ShipAddress1` varchar(255) DEFAULT NULL,
  `ShipAddress2` varchar(255) DEFAULT NULL,
  `ShipCity` varchar(50) DEFAULT NULL,
  `ShipPrefecture` varchar(50) DEFAULT NULL,
  `ShipZipCode` varchar(20) DEFAULT NULL,
  `ShipNameKana` varchar(50) DEFAULT NULL,
  `ShipFirstNameKana` varchar(50) DEFAULT NULL,
  `ShipLastNameKana` varchar(50) DEFAULT NULL,
  `ShipAddress1Kana` varchar(255) DEFAULT NULL,
  `ShipAddress2Kana` varchar(255) DEFAULT NULL,
  `ShipCityKana` varchar(50) DEFAULT NULL,
  `ShipPrefectureKana` varchar(50) DEFAULT NULL,
  `ShipSection1Field` varchar(255) DEFAULT NULL,
  `ShipSection1Value` varchar(255) DEFAULT NULL,
  `ShipSection2Field` varchar(255) DEFAULT NULL,
  `ShipSection2Value` varchar(255) DEFAULT NULL,
  `ShipPhoneNumber` varchar(20) DEFAULT NULL,
  `ShipEmgPhoneNumber` varchar(20) DEFAULT NULL,
  `ShipMethod` varchar(255) DEFAULT NULL,
  `ShipMethodName` varchar(255) DEFAULT NULL,
  `ShipRequestDate` varchar(255) DEFAULT NULL,
  `ShipRequestTime` varchar(255) DEFAULT NULL,
  `ShipNotes` varchar(255) DEFAULT NULL,
  `ArriveType` varchar(255) DEFAULT NULL,
  `ShipInvoiceNumber1` varchar(255) DEFAULT NULL,
  `ShipInvoiceNumber2` varchar(255) DEFAULT NULL,
  `ShipUrl` varchar(255) DEFAULT NULL,
  `ShipDate` varchar(255) DEFAULT NULL,
  `GiftWrapType` varchar(255) DEFAULT NULL,
  `GiftWrapPaperType` varchar(255) DEFAULT NULL,
  `GiftWrapName` varchar(255) DEFAULT NULL,
  `NeedBillSlip` varchar(255) DEFAULT NULL,
  `NeedDetailedSlip` varchar(255) DEFAULT NULL,
  `NeedReceipt` varchar(255) DEFAULT NULL,
  `Option1Field` varchar(255) DEFAULT NULL,
  `Option1Value` varchar(255) DEFAULT NULL,
  `Option2Field` varchar(255) DEFAULT NULL,
  `Option2Value` varchar(255) DEFAULT NULL,
  `GiftWrapMessage` varchar(255) DEFAULT NULL,
  `BillName` varchar(255) DEFAULT NULL,
  `BillFirstName` varchar(255) DEFAULT NULL,
  `BillLastName` varchar(255) DEFAULT NULL,
  `BillAddress1` varchar(255) DEFAULT NULL,
  `BillAddress2` varchar(255) DEFAULT NULL,
  `BillCity` varchar(255) DEFAULT NULL,
  `BillPrefecture` varchar(255) DEFAULT NULL,
  `BillZipCode` varchar(255) DEFAULT NULL,
  `BillNameKana` varchar(255) DEFAULT NULL,
  `BillFirstNameKana` varchar(255) DEFAULT NULL,
  `BillLastNameKana` varchar(255) DEFAULT NULL,
  `BillAddress1Kana` varchar(255) DEFAULT NULL,
  `BillAddress2Kana` varchar(255) DEFAULT NULL,
  `BillCityKana` varchar(255) DEFAULT NULL,
  `BillPrefectureKana` varchar(255) DEFAULT NULL,
  `BillSection1Field` varchar(255) DEFAULT NULL,
  `BillSection1Value` varchar(255) DEFAULT NULL,
  `BillSection2Field` varchar(255) DEFAULT NULL,
  `BillSection2Value` varchar(255) DEFAULT NULL,
  `BillPhoneNumber` varchar(255) DEFAULT NULL,
  `BillEmgPhoneNumber` varchar(255) DEFAULT NULL,
  `BillMailAddress` varchar(255) DEFAULT NULL,
  `PayMethod` varchar(255) DEFAULT NULL,
  `PayMethodName` varchar(255) DEFAULT NULL,
  `PayKind` varchar(255) DEFAULT NULL,
  `CardPayCount` varchar(255) DEFAULT NULL,
  `CardPayType` varchar(255) DEFAULT NULL,
  `SettleStatus` varchar(255) DEFAULT NULL,
  `SettleId` varchar(255) DEFAULT NULL,
  `PayNo` varchar(255) DEFAULT NULL,
  `PayNoIssueDate` varchar(255) DEFAULT NULL,
  `PayDate` varchar(255) DEFAULT NULL,
  `BuyerComments` varchar(255) DEFAULT NULL,
  `AgeConfirm` varchar(255) DEFAULT NULL,
  `QuantityDetail` varchar(255) DEFAULT NULL,
  `ShipCharge` varchar(11) DEFAULT NULL,
  `PayCharge` varchar(11) DEFAULT NULL,
  `GiftWrapCharge` varchar(255) DEFAULT NULL,
  `Discount` varchar(11) DEFAULT NULL,
  `UsePoint` varchar(11) DEFAULT NULL,
  `GetPoint` varchar(11) DEFAULT NULL,
  `Total` varchar(11) DEFAULT NULL,
  `TotalPrice` varchar(11) DEFAULT NULL,
  `ShippingCouponDiscount` varchar(255) DEFAULT NULL,
  `ItemCouponDiscount` varchar(255) DEFAULT NULL,
  `TotalMallCouponDiscount` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_orderr_yahoo_ne_orders_dl_tmp`
--

DROP TABLE IF EXISTS `tb_orderr_yahoo_ne_orders_dl_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_orderr_yahoo_ne_orders_dl_tmp` (
  `OrderId` varchar(20) DEFAULT NULL,
  `ParentOrderId` varchar(20) DEFAULT NULL,
  `DeviceType` varchar(1) DEFAULT NULL,
  `IsAffiliate` varchar(1) DEFAULT NULL,
  `FspLicenseCode` varchar(255) DEFAULT NULL,
  `FspLicenseName` varchar(255) DEFAULT NULL,
  `OrderTime` varchar(20) DEFAULT NULL,
  `OrderTimeUnixEpoch` varchar(20) DEFAULT NULL,
  `UsePointType` varchar(255) DEFAULT NULL,
  `OrderStatus` varchar(255) DEFAULT NULL,
  `StoreStatus` varchar(255) DEFAULT NULL,
  `Referer` varchar(255) DEFAULT NULL,
  `EntryPoint` varchar(255) DEFAULT NULL,
  `Clink` varchar(255) DEFAULT NULL,
  `SuspectMessage` varchar(255) DEFAULT NULL,
  `IsItemCoupon` varchar(1) DEFAULT NULL,
  `IsShippingCoupon` varchar(1) DEFAULT NULL,
  `ShipName` varchar(255) DEFAULT NULL,
  `ShipFirstName` varchar(50) DEFAULT NULL,
  `ShipLastName` varchar(50) DEFAULT NULL,
  `ShipAddress1` varchar(255) DEFAULT NULL,
  `ShipAddress2` varchar(255) DEFAULT NULL,
  `ShipCity` varchar(50) DEFAULT NULL,
  `ShipPrefecture` varchar(50) DEFAULT NULL,
  `ShipZipCode` varchar(20) DEFAULT NULL,
  `ShipNameKana` varchar(50) DEFAULT NULL,
  `ShipFirstNameKana` varchar(50) DEFAULT NULL,
  `ShipLastNameKana` varchar(50) DEFAULT NULL,
  `ShipAddress1Kana` varchar(255) DEFAULT NULL,
  `ShipAddress2Kana` varchar(255) DEFAULT NULL,
  `ShipCityKana` varchar(50) DEFAULT NULL,
  `ShipPrefectureKana` varchar(50) DEFAULT NULL,
  `ShipSection1Field` varchar(255) DEFAULT NULL,
  `ShipSection1Value` varchar(255) DEFAULT NULL,
  `ShipSection2Field` varchar(255) DEFAULT NULL,
  `ShipSection2Value` varchar(255) DEFAULT NULL,
  `ShipPhoneNumber` varchar(20) DEFAULT NULL,
  `ShipEmgPhoneNumber` varchar(20) DEFAULT NULL,
  `ShipMethod` varchar(255) DEFAULT NULL,
  `ShipMethodName` varchar(255) DEFAULT NULL,
  `ShipRequestDate` varchar(255) DEFAULT NULL,
  `ShipRequestTime` varchar(255) DEFAULT NULL,
  `ShipNotes` varchar(255) DEFAULT NULL,
  `ArriveType` varchar(255) DEFAULT NULL,
  `ShipInvoiceNumber1` varchar(255) DEFAULT NULL,
  `ShipInvoiceNumber2` varchar(255) DEFAULT NULL,
  `ShipUrl` varchar(255) DEFAULT NULL,
  `ShipDate` varchar(255) DEFAULT NULL,
  `GiftWrapType` varchar(255) DEFAULT NULL,
  `GiftWrapPaperType` varchar(255) DEFAULT NULL,
  `GiftWrapName` varchar(255) DEFAULT NULL,
  `NeedBillSlip` varchar(255) DEFAULT NULL,
  `NeedDetailedSlip` varchar(255) DEFAULT NULL,
  `NeedReceipt` varchar(255) DEFAULT NULL,
  `Option1Field` varchar(255) DEFAULT NULL,
  `Option1Value` varchar(255) DEFAULT NULL,
  `Option2Field` varchar(255) DEFAULT NULL,
  `Option2Value` varchar(255) DEFAULT NULL,
  `GiftWrapMessage` varchar(255) DEFAULT NULL,
  `BillName` varchar(255) DEFAULT NULL,
  `BillFirstName` varchar(255) DEFAULT NULL,
  `BillLastName` varchar(255) DEFAULT NULL,
  `BillAddress1` varchar(255) DEFAULT NULL,
  `BillAddress2` varchar(255) DEFAULT NULL,
  `BillCity` varchar(255) DEFAULT NULL,
  `BillPrefecture` varchar(255) DEFAULT NULL,
  `BillZipCode` varchar(255) DEFAULT NULL,
  `BillNameKana` varchar(255) DEFAULT NULL,
  `BillFirstNameKana` varchar(255) DEFAULT NULL,
  `BillLastNameKana` varchar(255) DEFAULT NULL,
  `BillAddress1Kana` varchar(255) DEFAULT NULL,
  `BillAddress2Kana` varchar(255) DEFAULT NULL,
  `BillCityKana` varchar(255) DEFAULT NULL,
  `BillPrefectureKana` varchar(255) DEFAULT NULL,
  `BillSection1Field` varchar(255) DEFAULT NULL,
  `BillSection1Value` varchar(255) DEFAULT NULL,
  `BillSection2Field` varchar(255) DEFAULT NULL,
  `BillSection2Value` varchar(255) DEFAULT NULL,
  `BillPhoneNumber` varchar(255) DEFAULT NULL,
  `BillEmgPhoneNumber` varchar(255) DEFAULT NULL,
  `BillMailAddress` varchar(255) DEFAULT NULL,
  `PayMethod` varchar(255) DEFAULT NULL,
  `PayMethodName` varchar(255) DEFAULT NULL,
  `PayKind` varchar(255) DEFAULT NULL,
  `CardPayCount` varchar(255) DEFAULT NULL,
  `CardPayType` varchar(255) DEFAULT NULL,
  `SettleStatus` varchar(255) DEFAULT NULL,
  `SettleId` varchar(255) DEFAULT NULL,
  `PayNo` varchar(255) DEFAULT NULL,
  `PayNoIssueDate` varchar(255) DEFAULT NULL,
  `PayDate` varchar(255) DEFAULT NULL,
  `BuyerComments` varchar(255) DEFAULT NULL,
  `AgeConfirm` varchar(255) DEFAULT NULL,
  `QuantityDetail` varchar(255) DEFAULT NULL,
  `ShipCharge` varchar(11) DEFAULT NULL,
  `PayCharge` varchar(11) DEFAULT NULL,
  `GiftWrapCharge` varchar(255) DEFAULT NULL,
  `Discount` varchar(11) DEFAULT NULL,
  `UsePoint` varchar(11) DEFAULT NULL,
  `GetPoint` varchar(11) DEFAULT NULL,
  `Total` varchar(11) DEFAULT NULL,
  `TotalPrice` varchar(11) DEFAULT NULL,
  `ShippingCouponDiscount` varchar(255) DEFAULT NULL,
  `ItemCouponDiscount` varchar(255) DEFAULT NULL,
  `TotalMallCouponDiscount` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `新送料` int(11) NOT NULL DEFAULT '0',
  `送料差額` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_payment_method`
--

DROP TABLE IF EXISTS `tb_payment_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_payment_method` (
  `payment_id` int(11) NOT NULL DEFAULT '0',
  `payment_name` varchar(50) DEFAULT NULL,
  `payment_cost_ratio` float DEFAULT '0',
  PRIMARY KEY (`payment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_picking`
--

DROP TABLE IF EXISTS `tb_picking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_picking` (
  `日時` datetime NOT NULL,
  `商品コード` varchar(255) NOT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `フリー在庫数` int(10) unsigned DEFAULT NULL,
  `在庫数` int(10) unsigned DEFAULT NULL,
  `総ピッキング数` int(10) unsigned DEFAULT NULL,
  `ロケーションコード` varchar(255) DEFAULT NULL,
  `型番` varchar(255) DEFAULT NULL,
  `janコード` varchar(255) DEFAULT NULL,
  `仕入先コード` varchar(255) DEFAULT NULL,
  `仕入先名` varchar(255) DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `colname` varchar(255) DEFAULT NULL,
  `rowname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`商品コード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_plusnaoproductdirectory`
--

DROP TABLE IF EXISTS `tb_plusnaoproductdirectory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_plusnaoproductdirectory` (
  `NEディレクトリID` varchar(15) NOT NULL,
  `フィールド1` varchar(255) NOT NULL DEFAULT '',
  `フィールド2` varchar(255) NOT NULL DEFAULT '',
  `フィールド3` varchar(255) NOT NULL DEFAULT '',
  `フィールド4` varchar(255) NOT NULL DEFAULT '',
  `フィールド5` varchar(255) NOT NULL DEFAULT '',
  `フィールド6` varchar(255) DEFAULT NULL,
  `楽天ディレクトリID` varchar(15) NOT NULL,
  `BIDDDERSディレクトリID` varchar(15) NOT NULL,
  `YAHOOディレクトリID` varchar(15) NOT NULL,
  `Q10ディレクトリID` varchar(15) NOT NULL,
  `AMAZON商品タイプ` varchar(100) NOT NULL,
  `AMAZON検索キーワード1` varchar(255) NOT NULL,
  `AMAZON推奨ブラウズノード1` varchar(30) NOT NULL,
  `SSディレクトリID` varchar(30) NOT NULL COMMENT 'ショップサーブディレクトリID',
  `SSディレクトリ名` varchar(255) NOT NULL COMMENT 'ショップサーブディレクトリ名',
  `PPMディレクトリID` varchar(15) NOT NULL COMMENT 'ポンパレモールディレクトリID',
  `rakutencategories_1` varchar(255) DEFAULT NULL,
  `rakutencategories_1_order` int(11) NOT NULL DEFAULT '0',
  `rakutencategories_2` varchar(255) DEFAULT NULL,
  `gmo_category_main` varchar(255) DEFAULT NULL,
  `gmo_category_sub` varchar(255) DEFAULT NULL,
  `yahoo_category` varchar(255) NOT NULL COMMENT 'YAHOOプロダクトカテゴリ',
  `item_count` int(10) unsigned DEFAULT '0',
  `makeshop_cat1` varchar(255) DEFAULT NULL,
  `makeshop_cat2` varchar(255) DEFAULT NULL,
  `bidders_bunrui` varchar(255) DEFAULT NULL,
  `clothingtype` varchar(255) DEFAULT NULL,
  `gender` varchar(1) NOT NULL COMMENT '性別',
  `generation` varchar(1) NOT NULL COMMENT '世代',
  PRIMARY KEY (`NEディレクトリID`),
  KEY `Index_1` (`フィールド1`) USING BTREE,
  KEY `Index_2` (`フィールド2`) USING BTREE,
  KEY `Index_3` (`フィールド3`) USING BTREE,
  KEY `Index_4` (`フィールド4`) USING BTREE,
  KEY `Index_5` (`フィールド5`) USING BTREE,
  KEY `Index_6` (`フィールド6`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_plusnaoproductdirectory_backup`
--

DROP TABLE IF EXISTS `tb_plusnaoproductdirectory_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_plusnaoproductdirectory_backup` (
  `NEディレクトリID` varchar(15) NOT NULL,
  `フィールド1` varchar(255) NOT NULL DEFAULT '',
  `フィールド2` varchar(255) NOT NULL DEFAULT '',
  `フィールド3` varchar(255) NOT NULL DEFAULT '',
  `フィールド4` varchar(255) NOT NULL DEFAULT '',
  `フィールド5` varchar(255) NOT NULL DEFAULT '',
  `フィールド6` varchar(255) DEFAULT NULL,
  `楽天ディレクトリID` varchar(15) NOT NULL,
  `BIDDDERSディレクトリID` varchar(15) NOT NULL,
  `YAHOOディレクトリID` varchar(15) NOT NULL,
  `Q10ディレクトリID` varchar(15) NOT NULL,
  `AMAZON商品タイプ` varchar(100) NOT NULL,
  `AMAZON検索キーワード1` varchar(255) NOT NULL,
  `AMAZON推奨ブラウズノード1` varchar(30) NOT NULL,
  `SSディレクトリID` varchar(30) NOT NULL COMMENT 'ショップサーブディレクトリID',
  `SSディレクトリ名` varchar(255) NOT NULL COMMENT 'ショップサーブディレクトリ名',
  `PPMディレクトリID` varchar(15) NOT NULL COMMENT 'ポンパレモールディレクトリID',
  `rakutencategories_1` varchar(255) DEFAULT NULL,
  `rakutencategories_1_order` int(11) NOT NULL DEFAULT '0',
  `rakutencategories_2` varchar(255) DEFAULT NULL,
  `gmo_category_main` varchar(255) DEFAULT NULL,
  `gmo_category_sub` varchar(255) DEFAULT NULL,
  `yahoo_category` varchar(255) NOT NULL COMMENT 'YAHOOプロダクトカテゴリ',
  `item_count` int(10) unsigned DEFAULT '0',
  `makeshop_cat1` varchar(255) DEFAULT NULL,
  `makeshop_cat2` varchar(255) DEFAULT NULL,
  `bidders_bunrui` varchar(255) DEFAULT NULL,
  `clothingtype` varchar(255) DEFAULT NULL,
  `gender` varchar(1) NOT NULL COMMENT '性別',
  `generation` varchar(1) NOT NULL COMMENT '世代',
  PRIMARY KEY (`NEディレクトリID`),
  KEY `Index_1` (`フィールド1`) USING BTREE,
  KEY `Index_2` (`フィールド2`) USING BTREE,
  KEY `Index_3` (`フィールド3`) USING BTREE,
  KEY `Index_4` (`フィールド4`) USING BTREE,
  KEY `Index_5` (`フィールド5`) USING BTREE,
  KEY `Index_6` (`フィールド6`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_category`
--

DROP TABLE IF EXISTS `tb_ppm_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_category` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `コントロールカラム` varchar(10) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `表示先カテゴリ` varchar(255) NOT NULL,
  `優先度` varchar(50) DEFAULT '1000',
  `daihyo_syohin_code` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQUE` (`表示先カテゴリ`,`daihyo_syohin_code`) USING BTREE,
  KEY `daihyo_syohin_code` (`daihyo_syohin_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_category_del_target`
--

DROP TABLE IF EXISTS `tb_ppm_category_del_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_category_del_target` (
  `daihyo_syohin_code` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_category_dl`
--

DROP TABLE IF EXISTS `tb_ppm_category_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_category_dl` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理ID（商品URL）` varchar(50) NOT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `ショップ内カテゴリ` varchar(255) NOT NULL DEFAULT '',
  `表示順位` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_category_tmp`
--

DROP TABLE IF EXISTS `tb_ppm_category_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_category_tmp` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理ID（商品URL）` varchar(50) NOT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `ショップ内カテゴリ` varchar(255) NOT NULL DEFAULT '',
  `表示順位` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_image_del_target`
--

DROP TABLE IF EXISTS `tb_ppm_image_del_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_image_del_target` (
  `daihyo_syohin_code` varchar(255) NOT NULL DEFAULT '',
  `pic_folder` varchar(255) DEFAULT NULL,
  `row` int(11) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_image_error_tmp`
--

DROP TABLE IF EXISTS `tb_ppm_image_error_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_image_error_tmp` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理ID（商品URL）` varchar(255) NOT NULL DEFAULT '',
  `販売ステータス` varchar(255) DEFAULT NULL,
  `商品ID` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `キャッチコピー` varchar(255) DEFAULT NULL,
  `販売価格` varchar(255) DEFAULT NULL,
  `表示価格` varchar(255) DEFAULT NULL,
  `消費税` varchar(255) DEFAULT NULL,
  `送料` varchar(255) DEFAULT NULL,
  `独自送料グループ(1)` varchar(255) DEFAULT NULL,
  `独自送料グループ(2)` varchar(255) DEFAULT NULL,
  `個別送料` varchar(255) DEFAULT NULL,
  `代引料` varchar(255) DEFAULT NULL,
  `のし対応` varchar(255) DEFAULT NULL,
  `注文ボタン` varchar(255) DEFAULT NULL,
  `商品問い合わせボタン` varchar(255) DEFAULT NULL,
  `販売期間指定` varchar(255) DEFAULT NULL,
  `注文受付数` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(255) DEFAULT NULL,
  `在庫数` varchar(255) DEFAULT NULL,
  `在庫表示` varchar(255) DEFAULT NULL,
  `商品説明(1)` mediumtext,
  `商品説明(2)` mediumtext,
  `商品説明(テキストのみ)` varchar(255) DEFAULT NULL,
  `商品画像URL` varchar(255) DEFAULT NULL,
  `モールジャンルID` varchar(50) DEFAULT NULL,
  `シークレットセールパスワード` varchar(255) DEFAULT NULL,
  `ポイント率` varchar(50) DEFAULT NULL,
  `ポイント率適用期間` varchar(50) DEFAULT NULL,
  `SKU横軸項目名` varchar(50) DEFAULT NULL,
  `SKU縦軸項目名` varchar(255) DEFAULT NULL,
  `SKU在庫用残り表示閾値` varchar(255) DEFAULT NULL,
  `商品説明(スマートフォン用)` varchar(255) DEFAULT NULL,
  `JANコード` varchar(255) DEFAULT NULL,
  `ヘッダー・フッター・サイドバー` varchar(255) DEFAULT NULL,
  `お知らせ枠` varchar(255) DEFAULT NULL,
  `自由告知枠` varchar(255) DEFAULT NULL,
  `再入荷リクエストボタン` varchar(1) DEFAULT NULL,
  KEY `商品管理ID（商品URL）` (`商品管理ID（商品URL）`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_information`
--

DROP TABLE IF EXISTS `tb_ppm_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_information` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `input_商品説明1` text NOT NULL,
  `input_商品説明2` text NOT NULL,
  `input_商品説明テキストのみ` text NOT NULL,
  `input_商品説明スマートフォン用` text NOT NULL,
  `ppm_title` varchar(255) DEFAULT NULL,
  `registration_flg` int(1) NOT NULL DEFAULT '-1' COMMENT '登録フラグ',
  `exist_image` tinyint(1) NOT NULL DEFAULT '0',
  `キャッチコピー` varchar(255) DEFAULT NULL,
  `original_price` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `商品説明文_共通` text NOT NULL,
  `商品説明1` text NOT NULL,
  `商品説明2` text NOT NULL,
  `商品説明テキストのみ` text NOT NULL,
  `商品画像URL` text NOT NULL,
  `商品説明スマートフォン用` text NOT NULL,
  `category` varchar(255) NOT NULL,
  `variation` varchar(255) NOT NULL,
  `variation_ex` varchar(255) NOT NULL,
  `variation_ex2` varchar(255) NOT NULL,
  `rand_no` int(10) unsigned NOT NULL DEFAULT '0',
  `rand_link1_no` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_item_dl`
--

DROP TABLE IF EXISTS `tb_ppm_item_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_item_dl` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理ID（商品URL）` varchar(255) NOT NULL DEFAULT '',
  `販売ステータス` varchar(255) DEFAULT NULL,
  `商品ID` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `キャッチコピー` varchar(255) DEFAULT NULL,
  `販売価格` varchar(255) DEFAULT NULL,
  `表示価格` varchar(255) DEFAULT NULL,
  `消費税` varchar(255) DEFAULT NULL,
  `送料` varchar(255) DEFAULT NULL,
  `独自送料グループ(1)` varchar(255) DEFAULT NULL,
  `独自送料グループ(2)` varchar(255) DEFAULT NULL,
  `個別送料` varchar(255) DEFAULT NULL,
  `代引料` varchar(255) DEFAULT NULL,
  `のし対応` varchar(255) DEFAULT NULL,
  `注文ボタン` varchar(255) DEFAULT NULL,
  `商品問い合わせボタン` varchar(255) DEFAULT NULL,
  `販売期間指定` varchar(255) DEFAULT NULL,
  `注文受付数` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(255) DEFAULT NULL,
  `在庫数` varchar(255) DEFAULT NULL,
  `在庫表示` varchar(255) DEFAULT NULL,
  `商品説明(1)` mediumtext,
  `商品説明(2)` mediumtext,
  `商品説明(テキストのみ)` varchar(255) DEFAULT NULL,
  `商品画像URL` varchar(255) DEFAULT NULL,
  `モールジャンルID` varchar(50) DEFAULT NULL,
  `シークレットセールパスワード` varchar(255) DEFAULT NULL,
  `ポイント率` varchar(50) DEFAULT NULL,
  `ポイント率適用期間` varchar(50) DEFAULT NULL,
  `SKU横軸項目名` varchar(50) DEFAULT NULL,
  `SKU縦軸項目名` varchar(255) DEFAULT NULL,
  `SKU在庫用残り表示閾値` varchar(255) DEFAULT NULL,
  `商品説明(スマートフォン用)` varchar(255) DEFAULT NULL,
  `JANコード` varchar(255) DEFAULT NULL,
  `ヘッダー・フッター・サイドバー` varchar(255) DEFAULT NULL,
  `お知らせ枠` varchar(255) DEFAULT NULL,
  `自由告知枠` varchar(255) DEFAULT NULL,
  `再入荷リクエストボタン` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`商品管理ID（商品URL）`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_item_tmp`
--

DROP TABLE IF EXISTS `tb_ppm_item_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_item_tmp` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理ID（商品URL）` varchar(255) NOT NULL DEFAULT '',
  `販売ステータス` varchar(255) DEFAULT NULL,
  `商品ID` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `キャッチコピー` varchar(255) DEFAULT NULL,
  `販売価格` varchar(255) DEFAULT NULL,
  `表示価格` varchar(255) DEFAULT NULL,
  `消費税` varchar(255) DEFAULT NULL,
  `送料` varchar(255) DEFAULT NULL,
  `独自送料グループ(1)` varchar(255) DEFAULT NULL,
  `独自送料グループ(2)` varchar(255) DEFAULT NULL,
  `個別送料` varchar(255) DEFAULT NULL,
  `代引料` varchar(255) DEFAULT NULL,
  `のし対応` varchar(255) DEFAULT NULL,
  `注文ボタン` varchar(255) DEFAULT NULL,
  `商品問い合わせボタン` varchar(255) DEFAULT NULL,
  `販売期間指定` varchar(255) DEFAULT NULL,
  `注文受付数` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(255) DEFAULT NULL,
  `在庫数` varchar(255) DEFAULT NULL,
  `在庫表示` varchar(255) DEFAULT NULL,
  `商品説明(1)` mediumtext,
  `商品説明(2)` mediumtext,
  `商品説明(テキストのみ)` varchar(255) DEFAULT NULL,
  `商品画像URL` varchar(255) DEFAULT NULL,
  `モールジャンルID` varchar(50) DEFAULT NULL,
  `シークレットセールパスワード` varchar(255) DEFAULT NULL,
  `ポイント率` varchar(50) DEFAULT NULL,
  `ポイント率適用期間` varchar(50) DEFAULT NULL,
  `SKU横軸項目名` varchar(50) DEFAULT NULL,
  `SKU縦軸項目名` varchar(255) DEFAULT NULL,
  `SKU在庫用残り表示閾値` varchar(255) DEFAULT NULL,
  `商品説明(スマートフォン用)` varchar(255) DEFAULT NULL,
  `JANコード` varchar(255) DEFAULT NULL,
  `ヘッダー・フッター・サイドバー` varchar(255) DEFAULT NULL,
  `お知らせ枠` varchar(255) DEFAULT NULL,
  `自由告知枠` varchar(255) DEFAULT NULL,
  `再入荷リクエストボタン` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`商品管理ID（商品URL）`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_itemlist_add`
--

DROP TABLE IF EXISTS `tb_ppm_itemlist_add`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_itemlist_add` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `商品番号` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_itemlist_del`
--

DROP TABLE IF EXISTS `tb_ppm_itemlist_del`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_itemlist_del` (
  `商品番号` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`商品番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_mobilelink`
--

DROP TABLE IF EXISTS `tb_ppm_mobilelink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_mobilelink` (
  `識別コード` int(10) unsigned NOT NULL,
  `リンクアドレス` varchar(255) DEFAULT NULL,
  `リンク名` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`識別コード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_select_add`
--

DROP TABLE IF EXISTS `tb_ppm_select_add`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_select_add` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理ID（商品URL）` varchar(30) NOT NULL DEFAULT '',
  `選択肢タイプ` varchar(1) NOT NULL DEFAULT '',
  `購入オプション名` varchar(50) NOT NULL DEFAULT '',
  `オプション項目名` varchar(150) NOT NULL DEFAULT '',
  `SKU横軸項目ID` varchar(15) NOT NULL,
  `SKU横軸項目名` varchar(255) DEFAULT NULL,
  `SKU縦軸項目ID` varchar(15) NOT NULL,
  `SKU縦軸項目名` varchar(255) DEFAULT NULL,
  `SKU在庫数` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_select_del_target`
--

DROP TABLE IF EXISTS `tb_ppm_select_del_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_select_del_target` (
  `daihyo_syohin_code` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_select_dl`
--

DROP TABLE IF EXISTS `tb_ppm_select_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_select_dl` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理ID（商品URL）` varchar(30) NOT NULL DEFAULT '',
  `選択肢タイプ` varchar(1) NOT NULL DEFAULT '',
  `購入オプション名` varchar(50) NOT NULL DEFAULT '',
  `オプション項目名` varchar(150) NOT NULL DEFAULT '',
  `SKU横軸項目ID` varchar(15) NOT NULL,
  `SKU横軸項目名` varchar(255) DEFAULT NULL,
  `SKU縦軸項目ID` varchar(15) NOT NULL,
  `SKU縦軸項目名` varchar(255) DEFAULT NULL,
  `SKU在庫数` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  KEY `商品管理ID（商品URL）` (`商品管理ID（商品URL）`) USING BTREE,
  KEY `商品管理ID（商品URL）_2` (`商品管理ID（商品URL）`,`SKU横軸項目ID`,`SKU縦軸項目ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_select_dl_s`
--

DROP TABLE IF EXISTS `tb_ppm_select_dl_s`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_select_dl_s` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理ID（商品URL）` varchar(30) NOT NULL DEFAULT '',
  `選択肢タイプ` varchar(1) NOT NULL DEFAULT '',
  `購入オプション名` varchar(50) NOT NULL DEFAULT '',
  `オプション項目名` varchar(150) NOT NULL DEFAULT '',
  `SKU横軸項目ID` varchar(15) NOT NULL,
  `SKU横軸項目名` varchar(255) DEFAULT NULL,
  `SKU縦軸項目ID` varchar(15) NOT NULL,
  `SKU縦軸項目名` varchar(255) DEFAULT NULL,
  `SKU在庫数` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  KEY `商品管理ID（商品URL）` (`商品管理ID（商品URL）`) USING BTREE,
  KEY `商品管理ID（商品URL）_2` (`商品管理ID（商品URL）`,`SKU横軸項目ID`,`SKU縦軸項目ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ppm_select_tmp`
--

DROP TABLE IF EXISTS `tb_ppm_select_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ppm_select_tmp` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理ID（商品URL）` varchar(30) NOT NULL DEFAULT '',
  `選択肢タイプ` varchar(1) NOT NULL DEFAULT '',
  `購入オプション名` varchar(50) NOT NULL DEFAULT '',
  `オプション項目名` varchar(150) NOT NULL DEFAULT '',
  `SKU横軸項目ID` varchar(15) NOT NULL,
  `SKU横軸項目名` varchar(255) DEFAULT NULL,
  `SKU縦軸項目ID` varchar(15) NOT NULL,
  `SKU縦軸項目名` varchar(255) DEFAULT NULL,
  `SKU在庫数` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_pricedown`
--

DROP TABLE IF EXISTS `tb_pricedown`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_pricedown` (
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `stock_amount` int(11) NOT NULL DEFAULT '0',
  `last_orderdate` date NOT NULL COMMENT '最終入荷日',
  `last_order_interval` int(11) NOT NULL DEFAULT '0' COMMENT '最終入荷日からの日数',
  `last_sales_date` date DEFAULT NULL COMMENT '最終販売日',
  `last_sales_interval` int(11) NOT NULL DEFAULT '366' COMMENT '最終販売日からの日数',
  `sales_start_date` date NOT NULL COMMENT '販売開始日',
  `sales_start_interval` int(11) NOT NULL DEFAULT '0' COMMENT '販売開始日からの日数',
  `annual_sales_amount` int(11) NOT NULL DEFAULT '0',
  `expected_monthly_sales_amount` double NOT NULL DEFAULT '0',
  `stock_remain_period` double NOT NULL DEFAULT '0',
  `costrate_addrate` double NOT NULL DEFAULT '0',
  `genka_tnk` int(11) NOT NULL DEFAULT '0',
  `genka_tnk_ave` int(11) NOT NULL DEFAULT '0',
  `current_cost_rate` double NOT NULL DEFAULT '0',
  `renewal_cost_rate` double NOT NULL DEFAULT '0',
  `current_price` int(11) NOT NULL DEFAULT '0',
  `renewal_price` int(11) NOT NULL DEFAULT '0',
  `pricedown_flg_former` tinyint(1) NOT NULL DEFAULT '0',
  `pricedown_flg` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_pricedown_ishida`
--

DROP TABLE IF EXISTS `tb_pricedown_ishida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_pricedown_ishida` (
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `stock_amount` int(11) NOT NULL DEFAULT '0',
  `last_orderdate` date NOT NULL COMMENT '最終入荷日',
  `last_order_interval` int(11) NOT NULL DEFAULT '0' COMMENT '最終入荷日からの日数',
  `last_sales_date` date DEFAULT NULL COMMENT '最終販売日',
  `last_sales_interval` int(11) NOT NULL DEFAULT '366' COMMENT '最終販売日からの日数',
  `sales_start_date` date NOT NULL COMMENT '販売開始日',
  `sales_start_interval` int(11) NOT NULL DEFAULT '0' COMMENT '販売開始日からの日数',
  `annual_sales_amount` int(11) NOT NULL DEFAULT '0',
  `expected_monthly_sales_amount` double NOT NULL DEFAULT '0',
  `stock_remain_days` int(11) NOT NULL DEFAULT '0' COMMENT '在庫消化日数',
  `stock_remain_expire_days` int(11) NOT NULL DEFAULT '0' COMMENT '在庫消化希望残日数',
  `stock_remain_over_days` int(11) NOT NULL DEFAULT '0' COMMENT '在庫消化過剰日数',
  `costrate_addrate` double NOT NULL DEFAULT '0',
  `genka_tnk` int(11) NOT NULL DEFAULT '0',
  `genka_tnk_ave` int(11) NOT NULL DEFAULT '0',
  `current_cost_rate` double NOT NULL DEFAULT '0',
  `renewal_cost_rate` double NOT NULL DEFAULT '0',
  `current_price` int(11) NOT NULL DEFAULT '0',
  `renewal_price` int(11) NOT NULL DEFAULT '0',
  `pricedown_flg_former` tinyint(1) NOT NULL DEFAULT '0',
  `pricedown_flg` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_productchoiceitems`
--

DROP TABLE IF EXISTS `tb_productchoiceitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_productchoiceitems` (
  `ne_syohin_syohin_code` varchar(255) NOT NULL DEFAULT '-',
  `並び順No` int(10) unsigned DEFAULT NULL,
  `colname` varchar(255) DEFAULT NULL,
  `colcode` varchar(100) DEFAULT NULL,
  `rowname` varchar(255) DEFAULT NULL,
  `rowcode` varchar(100) DEFAULT NULL,
  `受発注可能フラグ` tinyint(3) DEFAULT '0',
  `toriatukai_kbn` varchar(50) DEFAULT '0',
  `zaiko_teisu` int(3) DEFAULT '0',
  `hachu_ten` varchar(50) DEFAULT '0',
  `lot` varchar(50) DEFAULT '0',
  `daihyo_syohin_code` varchar(255) DEFAULT NULL,
  `tag` varchar(100) DEFAULT 'empty',
  `location` varchar(255) DEFAULT '_new',
  `フリー在庫数` int(10) DEFAULT '0',
  `予約フリー在庫数` int(10) DEFAULT '0',
  `予約在庫修正値` int(11) DEFAULT '0',
  `在庫数` int(10) DEFAULT '0',
  `発注残数` int(10) DEFAULT '0',
  `最古発注伝票番号` int(10) DEFAULT NULL,
  `最古発注日` date DEFAULT NULL,
  `previouslocation` varchar(255) DEFAULT '_new',
  `予約引当数` int(10) DEFAULT '0',
  `引当数` int(10) DEFAULT '0',
  `予約在庫数` int(10) DEFAULT '0',
  `不良在庫数` int(10) DEFAULT '0',
  `label_application` int(10) unsigned DEFAULT '0',
  `check_why` varchar(255) DEFAULT NULL,
  `gmarket_copy_check` tinyint(1) NOT NULL DEFAULT '0',
  `temp_shortage_date` datetime NOT NULL COMMENT '暫定欠品日時',
  `maker_syohin_code` varchar(30) NOT NULL COMMENT 'メーカー商品コード',
  `在庫あり時納期管理番号` varchar(4) NOT NULL,
  PRIMARY KEY (`ne_syohin_syohin_code`),
  UNIQUE KEY `location` (`location`,`ne_syohin_syohin_code`,`並び順No`),
  KEY `Index_4` (`colcode`,`rowcode`,`colname`,`rowname`,`並び順No`) USING BTREE,
  KEY `Index_2` (`hachu_ten`,`zaiko_teisu`,`daihyo_syohin_code`) USING BTREE,
  KEY `Index_1` (`location`,`フリー在庫数`) USING BTREE,
  KEY `index_3` (`daihyo_syohin_code`,`並び順No`) USING BTREE,
  KEY `daihyo_syohin_code` (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_productchoiceitems_former`
--

DROP TABLE IF EXISTS `tb_productchoiceitems_former`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_productchoiceitems_former` (
  `ne_syohin_syohin_code` varchar(255) NOT NULL,
  `並び順No` int(10) unsigned DEFAULT NULL,
  `colname` varchar(255) DEFAULT NULL,
  `colcode` varchar(100) DEFAULT NULL,
  `rowname` varchar(255) DEFAULT NULL,
  `rowcode` varchar(100) DEFAULT NULL,
  `受発注可能フラグ` tinyint(3) DEFAULT NULL,
  `toriatukai_kbn` varchar(50) DEFAULT NULL,
  `zaiko_teisu` varchar(50) DEFAULT NULL,
  `hachu_ten` varchar(50) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `daihyo_syohin_code` varchar(100) DEFAULT NULL,
  `tag` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `フリー在庫数` int(10) DEFAULT NULL,
  `予約フリー在庫数` int(10) DEFAULT NULL,
  `予約在庫修正値` int(11) DEFAULT NULL,
  `在庫数` int(10) DEFAULT NULL,
  `発注残数` int(10) DEFAULT NULL,
  `最古発注伝票番号` int(10) DEFAULT NULL,
  `最古発注日` date DEFAULT NULL,
  `previouslocation` varchar(255) DEFAULT NULL,
  `予約引当数` int(10) DEFAULT NULL,
  `引当数` int(10) DEFAULT NULL,
  `予約在庫数` int(10) DEFAULT NULL,
  `不良在庫数` int(10) DEFAULT NULL,
  `label_application` int(10) unsigned DEFAULT NULL,
  `check_why` varchar(255) DEFAULT NULL,
  `gmarket_copy_check` tinyint(1) NOT NULL DEFAULT '0',
  `maker_syohin_code` varchar(30) NOT NULL COMMENT 'メーカー商品コード',
  `temp_shortage_date` datetime NOT NULL COMMENT '暫定欠品日時',
  `在庫あり時納期管理番号` varchar(4) NOT NULL,
  PRIMARY KEY (`ne_syohin_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_productchoiceitems_former_pre`
--

DROP TABLE IF EXISTS `tb_productchoiceitems_former_pre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_productchoiceitems_former_pre` (
  `ne_syohin_syohin_code` varchar(255) NOT NULL,
  `並び順No` int(10) unsigned DEFAULT NULL,
  `colname` varchar(255) DEFAULT NULL,
  `colcode` varchar(100) DEFAULT NULL,
  `rowname` varchar(255) DEFAULT NULL,
  `rowcode` varchar(100) DEFAULT NULL,
  `受発注可能フラグ` tinyint(3) DEFAULT NULL,
  `toriatukai_kbn` varchar(50) DEFAULT NULL,
  `zaiko_teisu` varchar(50) DEFAULT NULL,
  `hachu_ten` varchar(50) DEFAULT NULL,
  `lot` varchar(50) DEFAULT NULL,
  `daihyo_syohin_code` varchar(100) DEFAULT NULL,
  `tag` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `フリー在庫数` int(10) DEFAULT NULL,
  `予約フリー在庫数` int(10) DEFAULT NULL,
  `予約在庫修正値` int(11) DEFAULT NULL,
  `在庫数` int(10) DEFAULT NULL,
  `発注残数` int(10) DEFAULT NULL,
  `最古発注伝票番号` int(10) DEFAULT NULL,
  `最古発注日` date DEFAULT NULL,
  `previouslocation` varchar(255) DEFAULT NULL,
  `予約引当数` int(10) DEFAULT NULL,
  `引当数` int(10) DEFAULT NULL,
  `予約在庫数` int(10) DEFAULT NULL,
  `不良在庫数` int(10) DEFAULT NULL,
  `label_application` int(10) unsigned DEFAULT NULL,
  `check_why` varchar(255) DEFAULT NULL,
  `gmarket_copy_check` tinyint(1) NOT NULL DEFAULT '0',
  `maker_syohin_code` varchar(30) NOT NULL COMMENT 'メーカー商品コード',
  `temp_shortage_date` datetime NOT NULL COMMENT '暫定欠品日時',
  `在庫あり時納期管理番号` varchar(4) NOT NULL,
  PRIMARY KEY (`ne_syohin_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_productchoiceitems_minrowno`
--

DROP TABLE IF EXISTS `tb_productchoiceitems_minrowno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_productchoiceitems_minrowno` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `minrowno` int(11) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_purchase_quantity`
--

DROP TABLE IF EXISTS `tb_purchase_quantity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_purchase_quantity` (
  `商品コード` varchar(255) NOT NULL,
  `option` varchar(255) NOT NULL,
  `数量` int(11) NOT NULL,
  PRIMARY KEY (`商品コード`,`option`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_purchasedocument`
--

DROP TABLE IF EXISTS `tb_purchasedocument`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_purchasedocument` (
  `仕入伝票番号` int(10) unsigned DEFAULT '0',
  `仕入明細行` int(10) unsigned DEFAULT '0',
  `仕入先コード` varchar(255) DEFAULT NULL,
  `仕入先名` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `仕入数` int(10) unsigned DEFAULT '0',
  `仕入単価` int(10) unsigned DEFAULT '0',
  `小計` int(10) unsigned DEFAULT '0',
  `マスタ原価` int(10) unsigned DEFAULT '0',
  `マスタ売価` int(10) unsigned DEFAULT '0',
  `備考` varchar(255) DEFAULT '\0',
  `発注伝票番号` int(10) unsigned DEFAULT '0',
  `発注明細行` int(10) unsigned DEFAULT '0',
  `受注伝票番号` int(10) unsigned DEFAULT '0',
  `受注明細行` int(10) unsigned DEFAULT '0',
  `最終更新日` varchar(255) DEFAULT NULL,
  `purchasedocument_key` varchar(255) DEFAULT NULL,
  `pre_label_output` int(10) unsigned DEFAULT '0',
  `pre_label_output_former` int(10) unsigned DEFAULT '0',
  `purchasedocument_cd` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`purchasedocument_cd`),
  KEY `Index_2` (`仕入伝票番号`,`商品コード`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_purchasedocument_dl`
--

DROP TABLE IF EXISTS `tb_purchasedocument_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_purchasedocument_dl` (
  `仕入伝票番号` int(10) unsigned NOT NULL,
  `仕入明細行` int(10) unsigned DEFAULT NULL,
  `仕入先コード` varchar(255) DEFAULT NULL,
  `仕入先名` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `仕入数` int(10) unsigned DEFAULT NULL,
  `仕入単価` int(10) unsigned DEFAULT NULL,
  `小計` int(10) unsigned DEFAULT NULL,
  `マスタ原価` int(10) unsigned DEFAULT NULL,
  `マスタ売価` int(10) unsigned DEFAULT NULL,
  `備考` varchar(255) DEFAULT NULL,
  `発注伝票番号` int(10) unsigned DEFAULT NULL,
  `発注明細行` int(10) unsigned DEFAULT NULL,
  `受注伝票番号` int(10) unsigned DEFAULT NULL,
  `受注明細行` int(10) unsigned DEFAULT NULL,
  `最終更新日` datetime DEFAULT NULL,
  `purchasedocument_key` varchar(255) DEFAULT NULL,
  KEY `Index_1` (`purchasedocument_key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_purchasedocument_print`
--

DROP TABLE IF EXISTS `tb_purchasedocument_print`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_purchasedocument_print` (
  `label1` varchar(255) DEFAULT NULL,
  `label2` varchar(255) DEFAULT NULL,
  `label3` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_delivery`
--

DROP TABLE IF EXISTS `tb_qten_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_delivery` (
  `配送状態` varchar(255) DEFAULT NULL,
  `注文番号` varchar(255) DEFAULT NULL,
  `カート番号` varchar(255) DEFAULT NULL,
  `配送会社` varchar(255) DEFAULT NULL,
  `送り状番号` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `発送予定日` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `数量` varchar(255) DEFAULT NULL,
  `オプション情報` varchar(255) DEFAULT NULL,
  `オプションコード` varchar(255) DEFAULT NULL,
  `受取人名` varchar(255) DEFAULT NULL,
  `販売者商品コード` varchar(255) DEFAULT NULL,
  `決済サイト` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_delivery_date`
--

DROP TABLE IF EXISTS `tb_qten_delivery_date`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_delivery_date` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `配送状態` varchar(255) DEFAULT NULL,
  `注文番号` varchar(255) DEFAULT NULL,
  `カート番号` varchar(255) DEFAULT NULL,
  `配送会社` varchar(255) DEFAULT NULL,
  `送り状番号` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `発送予定日` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `数量` varchar(255) DEFAULT NULL,
  `オプション情報` varchar(255) DEFAULT NULL,
  `オプションコード` varchar(255) DEFAULT NULL,
  `受取人名` varchar(255) DEFAULT NULL,
  `販売者商品コード` varchar(255) DEFAULT NULL,
  `決済サイト` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_delivery_o`
--

DROP TABLE IF EXISTS `tb_qten_delivery_o`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_delivery_o` (
  `配送状態` varchar(255) DEFAULT NULL,
  `注文番号` varchar(255) DEFAULT NULL,
  `カート番号` varchar(255) DEFAULT NULL,
  `配送会社` varchar(255) DEFAULT NULL,
  `送り状番号` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `発送予定日` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `数量` varchar(255) DEFAULT NULL,
  `オプション情報` varchar(255) DEFAULT NULL,
  `オプションコード` varchar(255) DEFAULT NULL,
  `受取人名` varchar(255) DEFAULT NULL,
  `販売者商品コード` varchar(255) DEFAULT NULL,
  `決済サイト` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_delivery_x`
--

DROP TABLE IF EXISTS `tb_qten_delivery_x`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_delivery_x` (
  `配送状態` varchar(255) DEFAULT NULL,
  `注文番号` varchar(255) DEFAULT NULL,
  `カート番号` varchar(255) DEFAULT NULL,
  `配送会社` varchar(255) DEFAULT NULL,
  `送り状番号` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `発送予定日` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `数量` varchar(255) DEFAULT NULL,
  `オプション情報` varchar(255) DEFAULT NULL,
  `オプションコード` varchar(255) DEFAULT NULL,
  `受取人名` varchar(255) DEFAULT NULL,
  `販売者商品コード` varchar(255) DEFAULT NULL,
  `決済サイト` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_general`
--

DROP TABLE IF EXISTS `tb_qten_general`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_general` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `店舗伝票番号` varchar(255) DEFAULT NULL,
  `受注日` varchar(255) DEFAULT NULL,
  `受注郵便番号` varchar(255) DEFAULT NULL,
  `受注住所１` varchar(255) DEFAULT NULL,
  `受注住所２` varchar(255) DEFAULT NULL,
  `受注名` varchar(255) DEFAULT NULL,
  `受注名カナ` varchar(255) DEFAULT NULL,
  `受注電話番号` varchar(255) DEFAULT NULL,
  `受注メールアドレス` varchar(255) DEFAULT NULL,
  `発送郵便番号` varchar(255) DEFAULT NULL,
  `発送先住所１` varchar(255) DEFAULT NULL,
  `発送先住所２` varchar(255) DEFAULT NULL,
  `発送先名` varchar(255) DEFAULT NULL,
  `発送先カナ` varchar(255) DEFAULT NULL,
  `発送電話番号` varchar(255) DEFAULT NULL,
  `支払方法` varchar(255) DEFAULT NULL,
  `発送方法` varchar(255) DEFAULT NULL,
  `商品計` varchar(255) DEFAULT NULL,
  `税金` varchar(255) DEFAULT NULL,
  `発送料` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `ポイント` varchar(255) DEFAULT NULL,
  `その他費用` varchar(255) DEFAULT NULL,
  `合計金額` varchar(255) DEFAULT NULL,
  `ギフトフラグ` varchar(255) DEFAULT NULL,
  `時間帯指定` varchar(255) DEFAULT NULL,
  `日付指定` varchar(255) DEFAULT NULL,
  `作業者欄` varchar(255) DEFAULT NULL,
  `備考` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `商品価格` varchar(255) DEFAULT NULL,
  `受注数量` varchar(255) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `出荷済フラグ` varchar(255) DEFAULT NULL,
  `カート番号` varchar(20) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_information`
--

DROP TABLE IF EXISTS `tb_qten_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_information` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `q10_itemcode` varchar(30) NOT NULL COMMENT 'Q10付与の商品コード',
  `q10_itemcode_index` int(11) NOT NULL DEFAULT '0',
  `q10_title` varchar(255) NOT NULL COMMENT 'Q10タイトル',
  `registration_flg` tinyint(3) NOT NULL DEFAULT '-1' COMMENT '登録フラグ',
  `original_price` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `sell_price` int(11) NOT NULL DEFAULT '0' COMMENT '税込価格',
  `sell_qty` int(11) NOT NULL DEFAULT '0' COMMENT '数量',
  `exist_image` tinyint(1) NOT NULL DEFAULT '0' COMMENT '画像有無',
  `title` varchar(255) NOT NULL,
  `explanation` mediumtext NOT NULL COMMENT '商品情報',
  `free_explanation` mediumtext NOT NULL COMMENT '自由説明(HTML可)',
  `inventory_info` mediumtext NOT NULL COMMENT '在庫表',
  `status` varchar(3) NOT NULL,
  `2nd_cat_code` varchar(30) NOT NULL,
  `shipping_group_no` varchar(10) NOT NULL,
  `available_date` date DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `additional_item_image` varchar(1024) NOT NULL COMMENT '最大11',
  PRIMARY KEY (`daihyo_syohin_code`),
  KEY `q10_itemcode_index` (`q10_itemcode_index`) USING BTREE,
  KEY `q10_itemcode` (`q10_itemcode`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_itemcode`
--

DROP TABLE IF EXISTS `tb_qten_itemcode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_itemcode` (
  `q10_itemcode` varchar(30) NOT NULL DEFAULT '',
  `q10_itemcode_index` int(11) NOT NULL DEFAULT '0',
  `daihyo_syohin_code` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`q10_itemcode`),
  KEY `q10_itemcode_index` (`q10_itemcode_index`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_itemcode_org`
--

DROP TABLE IF EXISTS `tb_qten_itemcode_org`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_itemcode_org` (
  `q10_itemcode` varchar(30) NOT NULL DEFAULT '',
  `daihyo_syohin_code` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`q10_itemcode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_order_data`
--

DROP TABLE IF EXISTS `tb_qten_order_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_order_data` (
  `伝票番号` varchar(255) DEFAULT NULL,
  `受注番号` varchar(255) DEFAULT NULL,
  `受注日` varchar(255) DEFAULT NULL,
  `出荷確定日` varchar(255) DEFAULT NULL,
  `取込日` varchar(255) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `配達希望日` varchar(255) DEFAULT NULL,
  `出荷予定日` varchar(255) DEFAULT NULL,
  `納品書印刷指示日` varchar(255) DEFAULT NULL,
  `キャンセル日` varchar(255) DEFAULT NULL,
  `キャンセル区分` varchar(255) DEFAULT NULL,
  `入金額` varchar(255) DEFAULT NULL,
  `発送伝票番号` varchar(255) DEFAULT NULL,
  `店舗名` varchar(255) DEFAULT NULL,
  `店舗コード` varchar(255) DEFAULT NULL,
  `発送方法` varchar(255) DEFAULT NULL,
  `配送方法コード` varchar(255) DEFAULT NULL,
  `支払方法` varchar(255) DEFAULT NULL,
  `支払方法コード` varchar(255) DEFAULT NULL,
  `総合計` varchar(255) DEFAULT NULL,
  `商品計` varchar(255) DEFAULT NULL,
  `税金` varchar(255) DEFAULT NULL,
  `発送代` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `他費用` varchar(255) DEFAULT NULL,
  `ポイント数` varchar(255) DEFAULT NULL,
  `受注状態` varchar(255) DEFAULT NULL,
  `受注担当者` varchar(255) DEFAULT NULL,
  `受注分類タグ` varchar(255) DEFAULT NULL,
  `確認チェック` varchar(255) DEFAULT NULL,
  `作業用欄` varchar(255) DEFAULT NULL,
  `発送伝票備考欄` varchar(255) DEFAULT NULL,
  `ピッキング指示` varchar(255) DEFAULT NULL,
  `納品書特記事項` varchar(255) DEFAULT NULL,
  `備考` mediumtext,
  `配送時間帯` varchar(255) DEFAULT NULL,
  `購入者名` varchar(255) DEFAULT NULL,
  `購入者カナ` varchar(255) DEFAULT NULL,
  `購入者電話番号` varchar(255) DEFAULT NULL,
  `購入者郵便番号` varchar(255) DEFAULT NULL,
  `購入者住所1` varchar(255) DEFAULT NULL,
  `購入者住所2` varchar(255) DEFAULT NULL,
  `購入者（住所1+住所2）` varchar(255) DEFAULT NULL,
  `購入者メールアドレス` varchar(255) DEFAULT NULL,
  `顧客cd` varchar(255) DEFAULT NULL,
  `顧客区分` varchar(255) DEFAULT NULL,
  `送り先名` varchar(255) DEFAULT NULL,
  `送り先カナ` varchar(255) DEFAULT NULL,
  `送り先電話番号` varchar(255) DEFAULT NULL,
  `送り先郵便番号` varchar(255) DEFAULT NULL,
  `送り先住所1` varchar(255) DEFAULT NULL,
  `送り先住所2` varchar(255) DEFAULT NULL,
  `送り先（住所1+住所2）` varchar(255) DEFAULT NULL,
  `ギフト` varchar(255) DEFAULT NULL,
  `入金状況` varchar(255) DEFAULT NULL,
  `名義人` varchar(255) DEFAULT NULL,
  `承認状況` varchar(255) DEFAULT NULL,
  `承認額` varchar(255) DEFAULT NULL,
  `納品書発行日` varchar(255) DEFAULT NULL,
  `重要チェック` varchar(255) DEFAULT NULL,
  `重要チェック者` varchar(255) DEFAULT NULL,
  `明細行` varchar(255) DEFAULT NULL,
  `明細行キャンセル` varchar(255) DEFAULT NULL,
  `商品コード（伝票）` varchar(255) DEFAULT NULL,
  `商品名（伝票）` varchar(255) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `受注数` varchar(255) DEFAULT NULL,
  `引当数` varchar(255) DEFAULT NULL,
  `引当日` varchar(255) DEFAULT NULL,
  `売単価` varchar(255) DEFAULT NULL,
  `小計` varchar(255) DEFAULT NULL,
  `元単価` varchar(255) DEFAULT NULL,
  `掛率` varchar(255) DEFAULT NULL,
  `元注文番号` varchar(255) NOT NULL,
  `元カート番号` varchar(255) NOT NULL,
  `商品出荷予定日文字列` varchar(255) NOT NULL,
  `商品出荷予定日仮` date NOT NULL,
  `商品出荷予定日` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_order_data_tmp`
--

DROP TABLE IF EXISTS `tb_qten_order_data_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_order_data_tmp` (
  `伝票番号` varchar(255) DEFAULT NULL,
  `受注番号` varchar(255) DEFAULT NULL,
  `受注日` varchar(255) DEFAULT NULL,
  `出荷確定日` varchar(255) DEFAULT NULL,
  `取込日` varchar(255) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `配達希望日` varchar(255) DEFAULT NULL,
  `出荷予定日` varchar(255) DEFAULT NULL,
  `納品書印刷指示日` varchar(255) DEFAULT NULL,
  `キャンセル日` varchar(255) DEFAULT NULL,
  `キャンセル区分` varchar(255) DEFAULT NULL,
  `入金額` varchar(255) DEFAULT NULL,
  `発送伝票番号` varchar(255) DEFAULT NULL,
  `店舗名` varchar(255) DEFAULT NULL,
  `店舗コード` varchar(255) DEFAULT NULL,
  `発送方法` varchar(255) DEFAULT NULL,
  `配送方法コード` varchar(255) DEFAULT NULL,
  `支払方法` varchar(255) DEFAULT NULL,
  `支払方法コード` varchar(255) DEFAULT NULL,
  `総合計` varchar(255) DEFAULT NULL,
  `商品計` varchar(255) DEFAULT NULL,
  `税金` varchar(255) DEFAULT NULL,
  `発送代` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `他費用` varchar(255) DEFAULT NULL,
  `ポイント数` varchar(255) DEFAULT NULL,
  `受注状態` varchar(255) DEFAULT NULL,
  `受注担当者` varchar(255) DEFAULT NULL,
  `受注分類タグ` varchar(255) DEFAULT NULL,
  `確認チェック` varchar(255) DEFAULT NULL,
  `作業用欄` varchar(255) DEFAULT NULL,
  `発送伝票備考欄` varchar(255) DEFAULT NULL,
  `ピッキング指示` varchar(255) DEFAULT NULL,
  `納品書特記事項` varchar(255) DEFAULT NULL,
  `備考` mediumtext,
  `配送時間帯` varchar(255) DEFAULT NULL,
  `購入者名` varchar(255) DEFAULT NULL,
  `購入者カナ` varchar(255) DEFAULT NULL,
  `購入者電話番号` varchar(255) DEFAULT NULL,
  `購入者郵便番号` varchar(255) DEFAULT NULL,
  `購入者住所1` varchar(255) DEFAULT NULL,
  `購入者住所2` varchar(255) DEFAULT NULL,
  `購入者（住所1+住所2）` varchar(255) DEFAULT NULL,
  `購入者メールアドレス` varchar(255) DEFAULT NULL,
  `顧客cd` varchar(255) DEFAULT NULL,
  `顧客区分` varchar(255) DEFAULT NULL,
  `送り先名` varchar(255) DEFAULT NULL,
  `送り先カナ` varchar(255) DEFAULT NULL,
  `送り先電話番号` varchar(255) DEFAULT NULL,
  `送り先郵便番号` varchar(255) DEFAULT NULL,
  `送り先住所1` varchar(255) DEFAULT NULL,
  `送り先住所2` varchar(255) DEFAULT NULL,
  `送り先（住所1+住所2）` varchar(255) DEFAULT NULL,
  `ギフト` varchar(255) DEFAULT NULL,
  `入金状況` varchar(255) DEFAULT NULL,
  `名義人` varchar(255) DEFAULT NULL,
  `承認状況` varchar(255) DEFAULT NULL,
  `承認額` varchar(255) DEFAULT NULL,
  `納品書発行日` varchar(255) DEFAULT NULL,
  `重要チェック` varchar(255) DEFAULT NULL,
  `重要チェック者` varchar(255) DEFAULT NULL,
  `明細行` varchar(255) DEFAULT NULL,
  `明細行キャンセル` varchar(255) DEFAULT NULL,
  `商品コード（伝票）` varchar(255) DEFAULT NULL,
  `商品名（伝票）` varchar(255) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `受注数` varchar(255) DEFAULT NULL,
  `引当数` varchar(255) DEFAULT NULL,
  `引当日` varchar(255) DEFAULT NULL,
  `売単価` varchar(255) DEFAULT NULL,
  `小計` varchar(255) DEFAULT NULL,
  `元単価` varchar(255) DEFAULT NULL,
  `掛率` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_qten_tracking`
--

DROP TABLE IF EXISTS `tb_qten_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_qten_tracking` (
  `配送状態` varchar(255) DEFAULT NULL,
  `注文番号` varchar(255) DEFAULT NULL,
  `カート番号` varchar(255) DEFAULT NULL,
  `配送会社` varchar(255) DEFAULT NULL,
  `送り状番号` varchar(255) DEFAULT NULL,
  `発送日` varchar(255) DEFAULT NULL,
  `発送予定日` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `数量` varchar(255) DEFAULT NULL,
  `オプション情報` varchar(255) DEFAULT NULL,
  `オプションコード` varchar(255) DEFAULT NULL,
  `受取人名` varchar(255) DEFAULT NULL,
  `販売者商品コード` varchar(255) DEFAULT NULL,
  `決済サイト` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_category_list`
--

DROP TABLE IF EXISTS `tb_rakuten_category_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_category_list` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `levels` int(11) DEFAULT NULL,
  `cat1` varchar(255) DEFAULT NULL,
  `cat2` varchar(255) DEFAULT NULL,
  `cat3` varchar(255) DEFAULT NULL,
  `cat4` varchar(255) DEFAULT NULL,
  `cat5` varchar(255) DEFAULT NULL,
  `cat_code` varchar(20) DEFAULT NULL,
  `表示先カテゴリ` varchar(255) DEFAULT NULL,
  `html` mediumtext,
  `表示順` int(11) NOT NULL DEFAULT '99999',
  `表示F` tinyint(1) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_category_list_save`
--

DROP TABLE IF EXISTS `tb_rakuten_category_list_save`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_category_list_save` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `表示先カテゴリ` varchar(255) DEFAULT NULL,
  `表示順` int(11) NOT NULL DEFAULT '99999',
  `表示F` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_gold_category`
--

DROP TABLE IF EXISTS `tb_rakuten_gold_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_gold_category` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `categories1` varchar(255) DEFAULT NULL,
  `f1` varchar(100) DEFAULT NULL,
  `f2` varchar(100) DEFAULT NULL,
  `f3` varchar(100) DEFAULT NULL,
  `directory_id` varchar(30) DEFAULT NULL,
  `cate1` varchar(255) DEFAULT NULL,
  `cate2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `f1` (`f1`,`f2`,`f3`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_item_del`
--

DROP TABLE IF EXISTS `tb_rakuten_item_del`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_item_del` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(255) NOT NULL DEFAULT '',
  `商品番号` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`商品管理番号（商品URL）`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_itemformat_export`
--

DROP TABLE IF EXISTS `tb_rakuten_itemformat_export`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_itemformat_export` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(255) NOT NULL DEFAULT '',
  `商品番号` varchar(30) DEFAULT NULL,
  `全商品ディレクトリID` varchar(15) DEFAULT NULL,
  `タグID` varchar(255) DEFAULT NULL,
  `PC用キャッチコピー` varchar(255) DEFAULT NULL,
  `モバイル用キャッチコピー` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `販売価格` int(11) DEFAULT NULL,
  `表示価格` int(11) DEFAULT NULL,
  `消費税` varchar(255) DEFAULT NULL,
  `送料` varchar(255) DEFAULT NULL,
  `個別送料` varchar(255) DEFAULT NULL,
  `送料区分1` varchar(255) DEFAULT NULL,
  `送料区分2` varchar(255) DEFAULT NULL,
  `代引料` int(11) DEFAULT NULL,
  `倉庫指定` varchar(255) DEFAULT NULL,
  `商品情報レイアウト` varchar(255) DEFAULT NULL,
  `注文ボタン` varchar(255) DEFAULT NULL,
  `資料請求ボタン` varchar(255) DEFAULT NULL,
  `商品問い合わせボタン` varchar(255) DEFAULT NULL,
  `モバイル表示` varchar(255) DEFAULT NULL,
  `のし対応` varchar(255) DEFAULT NULL,
  `PC用商品説明文` varchar(255) DEFAULT NULL,
  `モバイル用商品説明文` mediumtext,
  `スマートフォン用商品説明文` varchar(255) DEFAULT NULL,
  `PC用販売説明文` mediumtext,
  `商品画像URL` mediumtext,
  `商品画像名（ALT）` varchar(255) DEFAULT NULL,
  `動画` varchar(255) DEFAULT NULL,
  `販売期間指定` varchar(255) DEFAULT NULL,
  `注文受付数` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(255) DEFAULT NULL,
  `在庫数` varchar(255) DEFAULT NULL,
  `在庫数表示` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用横軸項目名` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用縦軸項目名` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用残り表示閾値` varchar(255) DEFAULT NULL,
  `RAC番号` varchar(255) DEFAULT NULL,
  `闇市パスワード` varchar(255) DEFAULT NULL,
  `カタログID` varchar(255) DEFAULT NULL,
  `在庫戻しフラグ` varchar(255) DEFAULT NULL,
  `在庫切れ時の注文受付` varchar(255) DEFAULT NULL,
  `在庫あり時納期管理番号` varchar(255) DEFAULT NULL,
  `在庫切れ時納期管理番号` varchar(255) DEFAULT NULL,
  `予約商品発売日` varchar(255) DEFAULT NULL,
  `ポイント変倍率` varchar(255) DEFAULT NULL,
  `ポイント変倍率適用期間` varchar(255) DEFAULT NULL,
  `ヘッダー・フッター・レフトナビ` varchar(255) DEFAULT NULL,
  `表示項目の並び順` varchar(255) DEFAULT NULL,
  `共通説明文（小）` varchar(255) DEFAULT NULL,
  `目玉商品` varchar(255) DEFAULT NULL,
  `共通説明文（大）` varchar(255) DEFAULT NULL,
  `レビュー本文表示` varchar(255) DEFAULT NULL,
  `あす楽配送管理番号` varchar(255) DEFAULT NULL,
  `海外配送管理番号` varchar(255) DEFAULT NULL,
  `サイズ表リンク` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`商品管理番号（商品URL）`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_items_dl`
--

DROP TABLE IF EXISTS `tb_rakuten_items_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_items_dl` (
  `受注番号` varchar(30) DEFAULT NULL,
  `受注ステータス` varchar(20) DEFAULT NULL,
  `カード決済ステータス` varchar(20) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `配送日` varchar(255) DEFAULT NULL,
  `お届け時間帯` varchar(255) DEFAULT NULL,
  `お届け日指定` varchar(255) DEFAULT NULL,
  `担当者` varchar(255) DEFAULT NULL,
  `ひとことメモ` varchar(255) DEFAULT NULL,
  `メール差込文(お客様へのメッセージ)` varchar(255) DEFAULT NULL,
  `初期購入合計金額` varchar(255) DEFAULT NULL,
  `利用端末` varchar(255) DEFAULT NULL,
  `メールキャリアコード` varchar(1) DEFAULT NULL,
  `ギフトチェック（0:なし/1:あり）` varchar(1) DEFAULT NULL,
  `コメント` mediumtext,
  `注文日時` varchar(255) DEFAULT NULL,
  `複数送付先フラグ` varchar(1) DEFAULT NULL,
  `警告表示フラグ` varchar(1) DEFAULT NULL,
  `楽天会員フラグ` varchar(1) DEFAULT NULL,
  `合計` varchar(11) DEFAULT NULL,
  `消費税(-99999=無効値)` varchar(11) DEFAULT NULL,
  `送料(-99999=無効値)` varchar(11) DEFAULT NULL,
  `代引料(-99999=無効値)` varchar(11) DEFAULT NULL,
  `請求金額(-99999=無効値)` varchar(11) DEFAULT NULL,
  `合計金額(-99999=無効値)` varchar(11) DEFAULT NULL,
  `同梱ID` varchar(255) DEFAULT NULL,
  `同梱ステータス` varchar(255) DEFAULT NULL,
  `同梱商品合計金額` varchar(11) DEFAULT NULL,
  `同梱送料合計` varchar(11) DEFAULT NULL,
  `同梱代引料合計` varchar(11) DEFAULT NULL,
  `同梱消費税合計` varchar(11) DEFAULT NULL,
  `同梱請求金額` varchar(11) DEFAULT NULL,
  `同梱合計金額` varchar(11) DEFAULT NULL,
  `同梱楽天バンク決済振替手数料` varchar(11) DEFAULT NULL,
  `同梱ポイント利用合計` varchar(11) DEFAULT NULL,
  `メールフラグ` varchar(255) DEFAULT NULL,
  `注文日` varchar(255) DEFAULT NULL,
  `注文時間` varchar(255) DEFAULT NULL,
  `モバイルキャリア決済番号` varchar(255) DEFAULT NULL,
  `購入履歴修正可否タイプ` varchar(255) DEFAULT NULL,
  `購入履歴修正アイコンフラグ` varchar(1) DEFAULT NULL,
  `購入履歴修正催促メールフラグ` varchar(1) DEFAULT NULL,
  `送付先一致フラグ` varchar(1) DEFAULT NULL,
  `ポイント利用有無` varchar(255) DEFAULT NULL,
  `注文者郵便番号１` varchar(3) DEFAULT NULL,
  `注文者郵便番号２` varchar(4) DEFAULT NULL,
  `注文者住所：都道府県` varchar(10) DEFAULT NULL,
  `注文者住所：都市区` varchar(30) DEFAULT NULL,
  `注文者住所：町以降` varchar(50) DEFAULT NULL,
  `注文者名字` varchar(20) DEFAULT NULL,
  `注文者名前` varchar(20) DEFAULT NULL,
  `注文者名字フリガナ` varchar(20) DEFAULT NULL,
  `注文者名前フリガナ` varchar(20) DEFAULT NULL,
  `注文者電話番号１` varchar(5) DEFAULT NULL,
  `注文者電話番号２` varchar(5) DEFAULT NULL,
  `注文者電話番号３` varchar(5) DEFAULT NULL,
  `メールアドレス` varchar(255) DEFAULT NULL,
  `注文者性別` varchar(255) DEFAULT NULL,
  `注文者誕生日` varchar(255) DEFAULT NULL,
  `決済方法` varchar(255) DEFAULT NULL,
  `クレジットカード種類` varchar(255) DEFAULT NULL,
  `クレジットカード番号` varchar(255) DEFAULT NULL,
  `クレジットカード名義人` varchar(255) DEFAULT NULL,
  `クレジットカード有効期限` varchar(255) DEFAULT NULL,
  `クレジットカード分割選択` varchar(255) DEFAULT NULL,
  `クレジットカード分割備考` varchar(255) DEFAULT NULL,
  `配送方法` varchar(255) DEFAULT NULL,
  `配送区分` varchar(255) DEFAULT NULL,
  `ポイント利用額` varchar(255) DEFAULT NULL,
  `ポイント利用条件` varchar(255) DEFAULT NULL,
  `ポイントステータス` varchar(255) DEFAULT NULL,
  `楽天バンク決済ステータス` varchar(255) DEFAULT NULL,
  `楽天バンク振替手数料負担区分` varchar(255) DEFAULT NULL,
  `楽天バンク決済手数料` varchar(255) DEFAULT NULL,
  `ラッピングタイトル(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング名(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング料金(包装紙)` varchar(255) DEFAULT NULL,
  `税込別(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピングタイトル(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング名(リボン)` varchar(255) DEFAULT NULL,
  `ラッピング料金(リボン)` varchar(255) DEFAULT NULL,
  `税込別(リボン)` varchar(255) DEFAULT NULL,
  `送付先送料` varchar(255) DEFAULT NULL,
  `送付先代引料` varchar(255) DEFAULT NULL,
  `送付先消費税` varchar(255) DEFAULT NULL,
  `お荷物伝票番号` varchar(255) DEFAULT NULL,
  `送付先商品合計金額` varchar(255) DEFAULT NULL,
  `のし` varchar(255) DEFAULT NULL,
  `送付先郵便番号１` varchar(3) DEFAULT NULL,
  `送付先郵便番号２` varchar(4) DEFAULT NULL,
  `送付先住所：都道府県` varchar(255) DEFAULT NULL,
  `送付先住所：都市区` varchar(255) DEFAULT NULL,
  `送付先住所：町以降` varchar(255) DEFAULT NULL,
  `送付先名字` varchar(255) DEFAULT NULL,
  `送付先名前` varchar(255) DEFAULT NULL,
  `送付先名字フリガナ` varchar(255) DEFAULT NULL,
  `送付先名前フリガナ` varchar(255) DEFAULT NULL,
  `送付先電話番号１` varchar(5) DEFAULT NULL,
  `送付先電話番号２` varchar(5) DEFAULT NULL,
  `送付先電話番号３` varchar(5) DEFAULT NULL,
  `商品ID` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `商品番号` varchar(255) DEFAULT NULL,
  `商品URL` varchar(255) DEFAULT NULL,
  `単価` varchar(255) DEFAULT NULL,
  `個数` varchar(255) DEFAULT NULL,
  `送料込別` varchar(255) DEFAULT NULL,
  `税込別` varchar(255) DEFAULT NULL,
  `代引手数料込別` varchar(255) DEFAULT NULL,
  `項目・選択肢` varchar(255) DEFAULT NULL,
  `ポイント倍率` varchar(255) DEFAULT NULL,
  `ポイントタイプ` varchar(255) DEFAULT NULL,
  `レコードナンバー` varchar(255) DEFAULT NULL,
  `納期情報` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(1) DEFAULT NULL,
  `ラッピング種類(包装紙)` varchar(255) DEFAULT NULL,
  `ラッピング種類(リボン)` varchar(255) DEFAULT NULL,
  `あす楽希望` varchar(1) DEFAULT NULL,
  `クーポン利用額` varchar(255) DEFAULT NULL,
  `店舗発行クーポン利用額` varchar(255) DEFAULT NULL,
  `楽天発行クーポン利用額` varchar(255) DEFAULT NULL,
  `同梱注文クーポン利用額` varchar(255) DEFAULT NULL,
  `配送会社` varchar(255) DEFAULT NULL,
  `薬事フラグ` varchar(1) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_ngword`
--

DROP TABLE IF EXISTS `tb_rakuten_ngword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_ngword` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(255) NOT NULL DEFAULT '',
  `check_page_nums` int(5) NOT NULL DEFAULT '0' COMMENT '全ページのうち精査ＯＫのページ数',
  `total_page_nums` int(5) NOT NULL DEFAULT '0' COMMENT 'ＮＧワードで検索されたページ数',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_nokikanri`
--

DROP TABLE IF EXISTS `tb_rakuten_nokikanri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_nokikanri` (
  `納期管理番号` int(11) NOT NULL DEFAULT '0',
  `出荷日` datetime DEFAULT NULL,
  `見出し` varchar(255) DEFAULT NULL,
  `出荷までの日数` int(11) DEFAULT NULL,
  PRIMARY KEY (`納期管理番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_review`
--

DROP TABLE IF EXISTS `tb_rakuten_review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_review` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `レビュー得点` tinyint(1) NOT NULL DEFAULT '1',
  `購入者名` varchar(255) NOT NULL,
  `購入ステータス` varchar(10) NOT NULL,
  `購入日時` datetime NOT NULL,
  `レビュー内容` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_review_tmp`
--

DROP TABLE IF EXISTS `tb_rakuten_review_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_review_tmp` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `商品行番号` int(5) NOT NULL DEFAULT '0',
  `レビュー得点` tinyint(1) NOT NULL DEFAULT '1',
  `購入者名` varchar(255) NOT NULL,
  `購入ステータス` varchar(10) NOT NULL,
  `購入日時` datetime NOT NULL,
  `レビュー内容` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_review_tmp_del`
--

DROP TABLE IF EXISTS `tb_rakuten_review_tmp_del`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_review_tmp_del` (
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_reviews`
--

DROP TABLE IF EXISTS `tb_rakuten_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_reviews` (
  `レビュータイプ` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `レビュー詳細URL` mediumtext,
  `評価` tinyint(1) DEFAULT '0',
  `投稿時間` varchar(255) DEFAULT NULL,
  `タイトル` varchar(255) DEFAULT NULL,
  `レビュー本文` varchar(255) DEFAULT NULL,
  `フラグ` varchar(255) DEFAULT NULL,
  `注文番号` varchar(255) DEFAULT NULL,
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `購入日時` datetime NOT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  KEY `daihyo_syohin_code` (`daihyo_syohin_code`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_reviews_tmp`
--

DROP TABLE IF EXISTS `tb_rakuten_reviews_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_reviews_tmp` (
  `レビュータイプ` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `レビュー詳細URL` mediumtext,
  `評価` tinyint(1) DEFAULT '0',
  `投稿時間` varchar(255) DEFAULT NULL,
  `タイトル` varchar(255) DEFAULT NULL,
  `レビュー本文` varchar(255) DEFAULT NULL,
  `フラグ` varchar(255) DEFAULT NULL,
  `注文番号` varchar(255) DEFAULT NULL,
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `購入日時` datetime NOT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  KEY `daihyo_syohin_code` (`daihyo_syohin_code`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuten_shop_reviews`
--

DROP TABLE IF EXISTS `tb_rakuten_shop_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuten_shop_reviews` (
  `レビュータイプ` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `レビュー詳細URL` mediumtext,
  `評価` tinyint(1) DEFAULT '0',
  `投稿時間` varchar(255) DEFAULT NULL,
  `タイトル` varchar(255) DEFAULT NULL,
  `レビュー本文` varchar(255) DEFAULT NULL,
  `フラグ` varchar(255) DEFAULT NULL,
  `注文番号` varchar(255) DEFAULT NULL,
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `購入日時` datetime NOT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  KEY `daihyo_syohin_code` (`daihyo_syohin_code`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakutencategory`
--

DROP TABLE IF EXISTS `tb_rakutencategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakutencategory` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `コントロールカラム` varchar(10) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `表示先カテゴリ` varchar(255) NOT NULL,
  `優先度` varchar(50) DEFAULT '999999999',
  `URL` varchar(255) DEFAULT NULL,
  `1ページ複数形式` varchar(255) DEFAULT NULL,
  `daihyo_syohin_code` varchar(255) NOT NULL,
  `cat_list_html` text NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQUE` (`表示先カテゴリ`,`daihyo_syohin_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakutencategory_dl`
--

DROP TABLE IF EXISTS `tb_rakutencategory_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakutencategory_dl` (
  `コントロールカラム` varchar(10) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(255) NOT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `表示先カテゴリ` varchar(255) NOT NULL,
  `優先度` int(10) unsigned DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `1ページ複数形式` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQUE` (`商品管理番号（商品URL）`,`表示先カテゴリ`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuteninformation`
--

DROP TABLE IF EXISTS `tb_rakuteninformation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuteninformation` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `楽天タイトル` varchar(255) DEFAULT NULL,
  `補正楽天タイトル` varchar(127) DEFAULT NULL,
  `variation` varchar(255) NOT NULL,
  `variation_ex` varchar(255) NOT NULL,
  `variation_ex2` varchar(255) NOT NULL,
  `registration_flg` int(1) NOT NULL DEFAULT '-1' COMMENT '登録フラグ',
  `original_price` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `Rモバイル用商品説明文` text,
  `RPC用商品説明文` text,
  `RPC用商品説明文_PC` text,
  `RPC用商品説明文_SP` text,
  `RPC用販売説明文` text,
  `旧楽天P説明` text,
  `商品画像URL` text,
  `rand_no` int(10) unsigned DEFAULT NULL,
  `rand_link1_no` int(10) unsigned DEFAULT NULL,
  `delivery_Information` int(10) unsigned DEFAULT NULL,
  `rakuten_price` int(10) unsigned DEFAULT NULL,
  `レビュー本文表示` varchar(1) NOT NULL DEFAULT '0',
  `sales_period_start_date` date NOT NULL,
  `sales_period_start_time` varchar(5) NOT NULL,
  `sales_period_end_date` date NOT NULL,
  `sales_period_end_time` varchar(5) NOT NULL,
  `sales_period` varchar(33) DEFAULT '0' COMMENT '販売期間指定',
  `表示価格` varchar(11) NOT NULL,
  `二重価格文言管理番号` varchar(1) DEFAULT '0',
  `input_PC商品説明文` text NOT NULL,
  `input_SP商品説明文` text NOT NULL,
  `input_PC販売説明文` text NOT NULL,
  `cat_list_html` mediumtext NOT NULL,
  `商品名` varchar(127) NOT NULL,
  `PC用キャッチコピー` varchar(87) NOT NULL,
  `モバイル用キャッチコピー` varchar(30) NOT NULL,
  `商品画像名（ALT）` mediumtext NOT NULL,
  PRIMARY KEY (`daihyo_syohin_code`),
  KEY `Index_2` (`rand_no`,`rand_link1_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakuteninformation_edit`
--

DROP TABLE IF EXISTS `tb_rakuteninformation_edit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakuteninformation_edit` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `補正楽天タイトル` varchar(127) DEFAULT NULL,
  `商品名` varchar(127) DEFAULT NULL,
  `ALT用商品名` varchar(127) DEFAULT NULL,
  `RPC用商品説明文` text NOT NULL,
  `RPC用商品説明文_PC` text NOT NULL,
  `RPC用商品説明文_SP` text NOT NULL,
  `RPC用販売説明文` text NOT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakutenitem_dl`
--

DROP TABLE IF EXISTS `tb_rakutenitem_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakutenitem_dl` (
  `コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(255) NOT NULL DEFAULT '',
  `商品番号` varchar(255) DEFAULT NULL,
  `商品画像URL` varchar(255) DEFAULT NULL,
  `在庫タイプ` varchar(255) DEFAULT NULL,
  `在庫数表示` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`商品管理番号（商品URL）`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakutenmobilelink`
--

DROP TABLE IF EXISTS `tb_rakutenmobilelink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakutenmobilelink` (
  `識別コード` int(10) unsigned NOT NULL,
  `リンクアドレス` varchar(255) DEFAULT NULL,
  `リンク名` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`識別コード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakutenselect_dl`
--

DROP TABLE IF EXISTS `tb_rakutenselect_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakutenselect_dl` (
  `項目選択肢用コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(50) DEFAULT NULL,
  `選択肢タイプ` varchar(2) DEFAULT NULL,
  `Select/Checkbox用項目名` varchar(80) DEFAULT NULL,
  `Select/Checkbox用選択肢` varchar(80) DEFAULT NULL,
  `項目選択肢別在庫用横軸選択肢` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用横軸選択肢子番号` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用縦軸選択肢` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用縦軸選択肢子番号` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用取り寄せ可能表示` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用在庫数` varchar(255) DEFAULT NULL,
  `在庫戻しフラグ` varchar(255) DEFAULT NULL,
  `在庫切れ時の注文受付` varchar(255) DEFAULT NULL,
  `在庫あり時納期管理番号` varchar(255) DEFAULT NULL,
  `在庫切れ時納期管理番号` varchar(255) DEFAULT NULL,
  KEY `商品管理番号（商品URL）` (`商品管理番号（商品URL）`,`選択肢タイプ`,`Select/Checkbox用項目名`,`Select/Checkbox用選択肢`) USING BTREE,
  KEY `商品管理番号（商品URL_2` (`商品管理番号（商品URL）`,`項目選択肢別在庫用横軸選択肢子番号`,`項目選択肢別在庫用縦軸選択肢子番号`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakutenselect_dl_i`
--

DROP TABLE IF EXISTS `tb_rakutenselect_dl_i`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakutenselect_dl_i` (
  `項目選択肢用コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(50) DEFAULT NULL,
  `選択肢タイプ` varchar(2) DEFAULT NULL,
  `Select/Checkbox用項目名` varchar(80) DEFAULT NULL,
  `Select/Checkbox用選択肢` varchar(80) DEFAULT NULL,
  `項目選択肢別在庫用横軸選択肢` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用横軸選択肢子番号` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用縦軸選択肢` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用縦軸選択肢子番号` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用取り寄せ可能表示` varchar(255) DEFAULT NULL,
  `項目選択肢別在庫用在庫数` varchar(255) DEFAULT NULL,
  `在庫戻しフラグ` varchar(255) DEFAULT NULL,
  `在庫切れ時の注文受付` varchar(255) DEFAULT NULL,
  `在庫あり時納期管理番号` varchar(255) DEFAULT NULL,
  `在庫切れ時納期管理番号` varchar(255) DEFAULT NULL,
  `ne_syohin_syohin_code` varchar(255) NOT NULL,
  PRIMARY KEY (`ne_syohin_syohin_code`),
  KEY `商品管理番号（商品URL）` (`商品管理番号（商品URL）`,`選択肢タイプ`,`Select/Checkbox用項目名`,`Select/Checkbox用選択肢`) USING BTREE,
  KEY `商品管理番号（商品URL_2` (`商品管理番号（商品URL）`,`項目選択肢別在庫用横軸選択肢子番号`,`項目選択肢別在庫用縦軸選択肢子番号`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakutenselect_key`
--

DROP TABLE IF EXISTS `tb_rakutenselect_key`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakutenselect_key` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `ne_syohin_syohin_code` varchar(50) NOT NULL DEFAULT '',
  `colname` varchar(255) NOT NULL,
  `rowname` varchar(255) NOT NULL,
  PRIMARY KEY (`ne_syohin_syohin_code`),
  KEY `daihyo_syohin_code` (`daihyo_syohin_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rakutenselect_tmp`
--

DROP TABLE IF EXISTS `tb_rakutenselect_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rakutenselect_tmp` (
  `項目選択肢用コントロールカラム` varchar(255) DEFAULT NULL,
  `商品管理番号（商品URL）` varchar(50) DEFAULT NULL,
  `選択肢タイプ` varchar(20) DEFAULT NULL,
  `Select/Checkbox用項目名` varchar(80) DEFAULT NULL,
  `Select/Checkbox用選択肢` varchar(80) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`),
  KEY `商品管理番号（商品URL）` (`商品管理番号（商品URL）`,`選択肢タイプ`,`Select/Checkbox用項目名`,`Select/Checkbox用選択肢`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rb_torihiki_meisai`
--

DROP TABLE IF EXISTS `tb_rb_torihiki_meisai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rb_torihiki_meisai` (
  `取引日` varchar(255) DEFAULT NULL,
  `入出金(円)` int(11) DEFAULT NULL,
  `残高(円)` int(11) DEFAULT NULL,
  `入出金先内容` varchar(255) DEFAULT NULL,
  `承認番号` varchar(255) DEFAULT NULL,
  `ご利用先` varchar(255) NOT NULL,
  `口座番号` varchar(30) NOT NULL,
  `摘要` varchar(255) NOT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rb_torihiki_meisai_note`
--

DROP TABLE IF EXISTS `tb_rb_torihiki_meisai_note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rb_torihiki_meisai_note` (
  `取引日` varchar(8) NOT NULL DEFAULT '',
  `入出金(円)` int(11) NOT NULL DEFAULT '0',
  `残高(円)` int(11) NOT NULL,
  `入出金先内容` varchar(255) NOT NULL DEFAULT '',
  `口座番号` varchar(30) NOT NULL,
  `摘要` varchar(255) NOT NULL,
  PRIMARY KEY (`取引日`,`入出金(円)`,`残高(円)`,`入出金先内容`,`口座番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rb_torihiki_meisai_tekiyo1`
--

DROP TABLE IF EXISTS `tb_rb_torihiki_meisai_tekiyo1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rb_torihiki_meisai_tekiyo1` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `口座番号` varchar(30) NOT NULL DEFAULT '',
  `ご利用先` varchar(255) NOT NULL,
  `摘要` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rb_torihiki_meisai_tekiyo2`
--

DROP TABLE IF EXISTS `tb_rb_torihiki_meisai_tekiyo2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rb_torihiki_meisai_tekiyo2` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `入出金先内容` varchar(255) NOT NULL,
  `摘要` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rb_torihiki_meisai_tmp`
--

DROP TABLE IF EXISTS `tb_rb_torihiki_meisai_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rb_torihiki_meisai_tmp` (
  `取引日` varchar(255) DEFAULT NULL,
  `入出金(円)` int(11) DEFAULT NULL,
  `残高(円)` int(11) DEFAULT NULL,
  `入出金先内容` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rb_visadebit_meisai`
--

DROP TABLE IF EXISTS `tb_rb_visadebit_meisai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rb_visadebit_meisai` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ご利用日` varchar(255) DEFAULT NULL,
  `ご利用先` varchar(255) DEFAULT NULL,
  `ご利用金額（円）` int(11) DEFAULT NULL,
  `VISA照会番号` varchar(255) DEFAULT NULL,
  `承認番号` varchar(255) DEFAULT NULL,
  `口座番号` varchar(30) NOT NULL,
  `摘要` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rb_visadebit_meisai_coy`
--

DROP TABLE IF EXISTS `tb_rb_visadebit_meisai_coy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rb_visadebit_meisai_coy` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ご利用日` varchar(255) DEFAULT NULL,
  `ご利用先` varchar(255) DEFAULT NULL,
  `ご利用金額（円）` int(11) DEFAULT NULL,
  `VISA照会番号` varchar(255) DEFAULT NULL,
  `承認番号` varchar(255) DEFAULT NULL,
  `口座番号` varchar(30) NOT NULL,
  `摘要` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rb_visadebit_meisai_note`
--

DROP TABLE IF EXISTS `tb_rb_visadebit_meisai_note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rb_visadebit_meisai_note` (
  `ご利用日` varchar(8) NOT NULL DEFAULT '',
  `ご利用先` varchar(255) NOT NULL DEFAULT '',
  `ご利用金額（円）` int(11) NOT NULL DEFAULT '0',
  `VISA照会番号` varchar(30) NOT NULL,
  `口座番号` varchar(30) NOT NULL,
  `摘要` varchar(255) NOT NULL,
  PRIMARY KEY (`ご利用日`,`ご利用先`,`ご利用金額（円）`,`VISA照会番号`,`口座番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_rb_visadebit_meisai_tmp`
--

DROP TABLE IF EXISTS `tb_rb_visadebit_meisai_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_rb_visadebit_meisai_tmp` (
  `ご利用日` varchar(255) DEFAULT NULL,
  `ご利用先` varchar(255) DEFAULT NULL,
  `ご利用金額（円）` int(11) DEFAULT NULL,
  `現地通貨額` varchar(255) DEFAULT NULL,
  `通貨略称` varchar(255) DEFAULT NULL,
  `換算レート` varchar(255) DEFAULT NULL,
  `使用地域` varchar(255) DEFAULT NULL,
  `VISA照会番号` varchar(255) DEFAULT NULL,
  `承認番号` varchar(255) DEFAULT NULL,
  `楽天銀行ポイント` varchar(255) DEFAULT NULL,
  `楽天スーパーポイント（ポイント獲得）` varchar(255) DEFAULT NULL,
  `（ポイント状態）` varchar(255) DEFAULT NULL,
  `（ポイント処理日）` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_recordsini`
--

DROP TABLE IF EXISTS `tb_recordsini`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_recordsini` (
  `recordsini_cd` int(10) unsigned NOT NULL,
  `intdata` int(11) DEFAULT NULL,
  `strdata` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`recordsini_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_related_buy_main_item`
--

DROP TABLE IF EXISTS `tb_related_buy_main_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_related_buy_main_item` (
  `daihyo_syohin_code` varchar(50) NOT NULL DEFAULT '',
  `cnt` int(11) NOT NULL DEFAULT '0',
  `row` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_related_buy_othertime`
--

DROP TABLE IF EXISTS `tb_related_buy_othertime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_related_buy_othertime` (
  `daihyo_syohin_code` varchar(50) NOT NULL DEFAULT '',
  `related_daihyo_syohin_code` varchar(50) NOT NULL,
  `cnt` int(11) NOT NULL DEFAULT '0',
  `row` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`,`related_daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_related_buy_sametime`
--

DROP TABLE IF EXISTS `tb_related_buy_sametime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_related_buy_sametime` (
  `daihyo_syohin_code` varchar(50) NOT NULL DEFAULT '',
  `related_daihyo_syohin_code` varchar(50) NOT NULL,
  `cnt` int(11) NOT NULL DEFAULT '0',
  `row` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`,`related_daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_running`
--

DROP TABLE IF EXISTS `tb_running`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_running` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `proc` varchar(255) DEFAULT NULL,
  `start_datetime` varchar(255) DEFAULT NULL,
  `estimate_time` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_ana_match`
--

DROP TABLE IF EXISTS `tb_sales_ana_match`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_ana_match` (
  `受注年` int(11) NOT NULL DEFAULT '0',
  `受注月` int(11) NOT NULL DEFAULT '0',
  `総合計` int(11) DEFAULT NULL,
  PRIMARY KEY (`受注年`,`受注月`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail`
--

DROP TABLE IF EXISTS `tb_sales_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail` (
  `伝票番号` int(11) NOT NULL,
  `受注番号` varchar(255) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `出荷確定日` datetime DEFAULT NULL,
  `取込日` datetime DEFAULT NULL,
  `入金日` datetime DEFAULT NULL,
  `配達希望日` datetime DEFAULT NULL,
  `出荷予定日` datetime DEFAULT NULL,
  `納品書印刷指示日` datetime DEFAULT NULL,
  `キャンセル日` datetime DEFAULT NULL,
  `キャンセル区分` varchar(255) DEFAULT NULL,
  `入金額` int(11) DEFAULT NULL,
  `発送伝票番号` varchar(255) DEFAULT NULL,
  `店舗名` varchar(255) DEFAULT NULL,
  `店舗コード` varchar(255) DEFAULT NULL,
  `発送方法` varchar(255) DEFAULT NULL,
  `配送方法コード` varchar(255) DEFAULT NULL,
  `支払方法` varchar(255) DEFAULT NULL,
  `支払方法コード` varchar(255) DEFAULT NULL,
  `総合計` int(11) DEFAULT NULL,
  `商品計` int(11) DEFAULT NULL,
  `税金` int(11) DEFAULT NULL,
  `発送代` int(11) DEFAULT NULL,
  `手数料` int(11) DEFAULT NULL,
  `他費用` int(11) DEFAULT NULL,
  `ポイント数` int(11) DEFAULT NULL,
  `受注状態` varchar(255) DEFAULT NULL,
  `受注担当者` varchar(255) DEFAULT NULL,
  `受注分類タグ` varchar(255) DEFAULT NULL,
  `確認チェック` varchar(255) DEFAULT NULL,
  `作業用欄` varchar(255) DEFAULT NULL,
  `発送伝票備考欄` varchar(255) DEFAULT NULL,
  `ピッキング指示` varchar(255) DEFAULT NULL,
  `納品書特記事項` varchar(255) DEFAULT NULL,
  `備考` mediumtext,
  `配送時間帯` varchar(255) DEFAULT NULL,
  `購入者名` varchar(255) DEFAULT NULL,
  `購入者カナ` varchar(255) DEFAULT NULL,
  `購入者電話番号` varchar(255) DEFAULT NULL,
  `購入者郵便番号` varchar(255) DEFAULT NULL,
  `購入者住所1` varchar(255) DEFAULT NULL,
  `購入者住所2` varchar(255) DEFAULT NULL,
  `購入者（住所1+住所2）` varchar(255) DEFAULT NULL,
  `購入者メールアドレス` varchar(255) DEFAULT NULL,
  `顧客cd` varchar(255) DEFAULT NULL,
  `顧客区分` varchar(255) DEFAULT NULL,
  `送り先名` varchar(255) DEFAULT NULL,
  `送り先カナ` varchar(255) DEFAULT NULL,
  `送り先電話番号` varchar(255) DEFAULT NULL,
  `送り先郵便番号` varchar(255) DEFAULT NULL,
  `送り先住所1` varchar(255) DEFAULT NULL,
  `送り先住所2` varchar(255) DEFAULT NULL,
  `送り先（住所1+住所2）` varchar(255) DEFAULT NULL,
  `ギフト` varchar(255) DEFAULT NULL,
  `入金状況` varchar(255) DEFAULT NULL,
  `名義人` varchar(255) DEFAULT NULL,
  `承認状況` varchar(255) DEFAULT NULL,
  `承認額` int(11) DEFAULT NULL,
  `納品書発行日` datetime DEFAULT NULL,
  `重要チェック` varchar(255) DEFAULT NULL,
  `重要チェック者` varchar(255) DEFAULT NULL,
  `明細行` int(11) NOT NULL DEFAULT '0',
  `明細行キャンセル` varchar(255) DEFAULT NULL,
  `商品コード（伝票）` varchar(255) DEFAULT NULL,
  `商品名（伝票）` varchar(255) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `受注数` int(11) DEFAULT NULL,
  `引当数` int(11) DEFAULT NULL,
  `引当日` datetime DEFAULT NULL,
  `売単価` int(11) DEFAULT NULL,
  `小計` int(11) DEFAULT NULL,
  `元単価` int(11) DEFAULT NULL,
  `掛率` int(11) DEFAULT NULL,
  PRIMARY KEY (`伝票番号`,`明細行`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_analyze`
--

DROP TABLE IF EXISTS `tb_sales_detail_analyze`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_analyze` (
  `伝票番号` int(11) NOT NULL,
  `明細行` int(11) NOT NULL,
  `受注番号` varchar(255) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  `出荷確定日` datetime DEFAULT NULL,
  `取込日` datetime DEFAULT NULL,
  `入金日` datetime DEFAULT NULL,
  `配達希望日` datetime DEFAULT NULL,
  `出荷予定日` datetime DEFAULT NULL,
  `納品書印刷指示日` datetime DEFAULT NULL,
  `キャンセル日` datetime DEFAULT NULL,
  `キャンセル区分` varchar(255) DEFAULT NULL,
  `入金額` int(11) DEFAULT NULL,
  `発送伝票番号` varchar(255) DEFAULT NULL,
  `店舗名` varchar(255) DEFAULT NULL,
  `店舗コード` varchar(255) DEFAULT NULL,
  `発送方法` varchar(255) DEFAULT NULL,
  `配送方法コード` varchar(255) DEFAULT NULL,
  `支払方法` varchar(255) DEFAULT NULL,
  `支払方法コード` varchar(255) DEFAULT NULL,
  `総合計` int(11) DEFAULT NULL,
  `商品計` int(11) DEFAULT NULL,
  `税金` int(11) DEFAULT NULL,
  `発送代` int(11) DEFAULT NULL,
  `手数料` int(11) DEFAULT NULL,
  `他費用` int(11) DEFAULT NULL,
  `ポイント数` int(11) DEFAULT NULL,
  `受注状態` varchar(255) DEFAULT NULL,
  `受注分類タグ` varchar(255) DEFAULT NULL,
  `確認チェック` varchar(255) DEFAULT NULL,
  `発送伝票備考欄` varchar(255) DEFAULT NULL,
  `ピッキング指示` varchar(255) DEFAULT NULL,
  `納品書特記事項` varchar(255) DEFAULT NULL,
  `顧客cd` varchar(255) DEFAULT NULL,
  `顧客区分` varchar(255) DEFAULT NULL,
  `入金状況` varchar(255) DEFAULT NULL,
  `名義人` varchar(255) DEFAULT NULL,
  `承認状況` varchar(255) DEFAULT NULL,
  `承認額` int(11) DEFAULT NULL,
  `納品書発行日` datetime DEFAULT NULL,
  `重要チェック` varchar(255) DEFAULT NULL,
  `重要チェック者` varchar(255) DEFAULT NULL,
  `明細行キャンセル` varchar(255) DEFAULT NULL,
  `商品コード（伝票）` varchar(255) DEFAULT NULL,
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `商品名（伝票）` varchar(255) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `受注数` int(11) DEFAULT NULL,
  `引当数` int(11) DEFAULT NULL,
  `引当日` datetime DEFAULT NULL,
  `売単価` int(11) DEFAULT NULL,
  `小計` int(11) DEFAULT NULL,
  `元単価` int(11) DEFAULT NULL,
  `掛率` int(11) DEFAULT NULL,
  `受注年` int(4) NOT NULL,
  `受注月` int(2) NOT NULL,
  `購入者名` varchar(50) NOT NULL,
  `購入者電話番号` varchar(30) NOT NULL,
  `出荷予定年月日` date NOT NULL,
  `出荷予定月日` varchar(10) NOT NULL,
  `出荷予定月` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`伝票番号`,`明細行`),
  KEY `daihyo_syohin_code` (`daihyo_syohin_code`) USING BTREE,
  KEY `購入者電話番号` (`購入者電話番号`) USING BTREE,
  KEY `伝票番号` (`伝票番号`) USING BTREE,
  KEY `受注番号` (`受注番号`) USING BTREE,
  KEY `受注状態` (`受注状態`) USING BTREE,
  KEY `支払方法` (`支払方法`) USING BTREE,
  KEY `入金状況` (`入金状況`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_buycount`
--

DROP TABLE IF EXISTS `tb_sales_detail_buycount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_buycount` (
  `伝票番号` int(11) NOT NULL,
  `購入者名` varchar(50) NOT NULL,
  `購入者電話番号` varchar(30) NOT NULL,
  `購入回数` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`伝票番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_profit`
--

DROP TABLE IF EXISTS `tb_sales_detail_profit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_profit` (
  `伝票番号` int(11) NOT NULL DEFAULT '0',
  `明細行` int(3) NOT NULL DEFAULT '0',
  `受注番号` varchar(50) DEFAULT NULL,
  `受注年月日` date DEFAULT NULL,
  `受注年` int(4) DEFAULT NULL,
  `受注月` int(2) DEFAULT NULL,
  `出荷年月日` date DEFAULT NULL,
  `出荷年` int(4) DEFAULT NULL,
  `出荷月` int(2) DEFAULT NULL,
  `キャンセル区分` varchar(1) DEFAULT NULL,
  `明細行キャンセル` varchar(1) DEFAULT NULL,
  `受注状態` varchar(255) DEFAULT NULL,
  `店舗コード` int(2) DEFAULT NULL,
  `店舗名` varchar(100) NOT NULL,
  `配送方法コード` int(3) NOT NULL DEFAULT '0',
  `配送方法名` varchar(100) NOT NULL,
  `支払方法コード` int(3) DEFAULT NULL,
  `支払方法名` varchar(100) NOT NULL,
  `商品コード` varchar(50) DEFAULT NULL,
  `代表商品コード` varchar(50) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `仕入先コード` varchar(4) DEFAULT NULL,
  `仕入先名` varchar(100) NOT NULL,
  `受注数` int(3) DEFAULT '0',
  `引当数` int(3) DEFAULT '0',
  `ポイント数を含む総合計` int(11) DEFAULT '0',
  `総合計` int(11) DEFAULT '0',
  `ポイント数` int(11) DEFAULT '0',
  `商品計` int(11) DEFAULT '0',
  `税金` int(11) DEFAULT '0',
  `発送代` int(11) DEFAULT '0',
  `手数料` int(11) DEFAULT '0',
  `他費用` int(11) DEFAULT '0',
  `売単価` int(11) DEFAULT '0',
  `小計` int(11) DEFAULT '0',
  `仕入先費用率` float NOT NULL DEFAULT '0',
  `仕入先費用額` int(11) NOT NULL DEFAULT '0',
  `仕入原価` int(11) NOT NULL DEFAULT '0',
  `付加費用額` int(11) NOT NULL DEFAULT '0',
  `固定費用額` int(11) NOT NULL DEFAULT '0',
  `明細粗利額` int(11) NOT NULL DEFAULT '0' COMMENT '伝票単位の費用を差し引く前の粗利額',
  PRIMARY KEY (`伝票番号`,`明細行`),
  KEY `代表商品コード` (`代表商品コード`) USING BTREE,
  KEY `商品コード` (`商品コード`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_tmp`
--

DROP TABLE IF EXISTS `tb_sales_detail_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_tmp` (
  `伝票番号` varchar(255) DEFAULT NULL,
  `受注番号` varchar(255) DEFAULT NULL,
  `受注日` varchar(255) DEFAULT NULL,
  `出荷確定日` varchar(255) DEFAULT NULL,
  `取込日` varchar(255) DEFAULT NULL,
  `入金日` varchar(255) DEFAULT NULL,
  `配達希望日` varchar(255) DEFAULT NULL,
  `出荷予定日` varchar(255) DEFAULT NULL,
  `納品書印刷指示日` varchar(255) DEFAULT NULL,
  `キャンセル日` varchar(255) DEFAULT NULL,
  `キャンセル区分` varchar(255) DEFAULT NULL,
  `入金額` varchar(255) DEFAULT NULL,
  `発送伝票番号` varchar(255) DEFAULT NULL,
  `店舗名` varchar(255) DEFAULT NULL,
  `店舗コード` varchar(255) DEFAULT NULL,
  `発送方法` varchar(255) DEFAULT NULL,
  `配送方法コード` varchar(255) DEFAULT NULL,
  `支払方法` varchar(255) DEFAULT NULL,
  `支払方法コード` varchar(255) DEFAULT NULL,
  `総合計` varchar(255) DEFAULT NULL,
  `商品計` varchar(255) DEFAULT NULL,
  `税金` varchar(255) DEFAULT NULL,
  `発送代` varchar(255) DEFAULT NULL,
  `手数料` varchar(255) DEFAULT NULL,
  `他費用` varchar(255) DEFAULT NULL,
  `ポイント数` varchar(255) DEFAULT NULL,
  `受注状態` varchar(255) DEFAULT NULL,
  `受注担当者` varchar(255) DEFAULT NULL,
  `受注分類タグ` varchar(255) DEFAULT NULL,
  `確認チェック` varchar(255) DEFAULT NULL,
  `作業用欄` varchar(255) DEFAULT NULL,
  `発送伝票備考欄` varchar(255) DEFAULT NULL,
  `ピッキング指示` varchar(255) DEFAULT NULL,
  `納品書特記事項` varchar(255) DEFAULT NULL,
  `備考` varchar(255) DEFAULT NULL,
  `配送時間帯` varchar(255) DEFAULT NULL,
  `購入者名` varchar(255) DEFAULT NULL,
  `購入者カナ` varchar(255) DEFAULT NULL,
  `購入者電話番号` varchar(255) DEFAULT NULL,
  `購入者郵便番号` varchar(255) DEFAULT NULL,
  `購入者住所1` varchar(255) DEFAULT NULL,
  `購入者住所2` varchar(255) DEFAULT NULL,
  `購入者（住所1+住所2）` varchar(255) DEFAULT NULL,
  `購入者メールアドレス` varchar(255) DEFAULT NULL,
  `顧客cd` varchar(255) DEFAULT NULL,
  `顧客区分` varchar(255) DEFAULT NULL,
  `送り先名` varchar(255) DEFAULT NULL,
  `送り先カナ` varchar(255) DEFAULT NULL,
  `送り先電話番号` varchar(255) DEFAULT NULL,
  `送り先郵便番号` varchar(255) DEFAULT NULL,
  `送り先住所1` varchar(255) DEFAULT NULL,
  `送り先住所2` varchar(255) DEFAULT NULL,
  `送り先（住所1+住所2）` varchar(255) DEFAULT NULL,
  `ギフト` varchar(255) DEFAULT NULL,
  `入金状況` varchar(255) DEFAULT NULL,
  `名義人` varchar(255) DEFAULT NULL,
  `承認状況` varchar(255) DEFAULT NULL,
  `承認額` varchar(255) DEFAULT NULL,
  `納品書発行日` varchar(255) DEFAULT NULL,
  `重要チェック` varchar(255) DEFAULT NULL,
  `重要チェック者` varchar(255) DEFAULT NULL,
  `明細行` varchar(255) DEFAULT NULL,
  `明細行キャンセル` varchar(255) DEFAULT NULL,
  `商品コード（伝票）` varchar(255) DEFAULT NULL,
  `商品名（伝票）` varchar(255) DEFAULT NULL,
  `商品オプション` varchar(255) DEFAULT NULL,
  `受注数` varchar(255) DEFAULT NULL,
  `引当数` varchar(255) DEFAULT NULL,
  `引当日` varchar(255) DEFAULT NULL,
  `売単価` varchar(255) DEFAULT NULL,
  `小計` varchar(255) DEFAULT NULL,
  `元単価` varchar(255) DEFAULT NULL,
  `掛率` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher` (
  `伝票番号` int(11) NOT NULL,
  `受注年月日` date NOT NULL,
  `受注年月` varchar(6) NOT NULL,
  `受注年` int(4) DEFAULT '0',
  `受注月` int(2) DEFAULT '0',
  `出荷年月日` date NOT NULL,
  `出荷年月` varchar(6) NOT NULL,
  `出荷年` int(4) NOT NULL DEFAULT '0',
  `出荷月` int(2) NOT NULL DEFAULT '0',
  `ポイント数を含む総合計` int(11) NOT NULL DEFAULT '0',
  `明細数` int(3) NOT NULL DEFAULT '0',
  `総合計` int(11) DEFAULT '0',
  `商品計` int(11) DEFAULT '0',
  `税金` int(11) DEFAULT '0',
  `発送代` int(11) DEFAULT '0',
  `手数料` int(11) DEFAULT '0',
  `他費用` int(11) DEFAULT '0',
  `ポイント数` int(11) DEFAULT '0',
  `店舗コード` int(2) NOT NULL DEFAULT '0',
  `支払方法コード` int(3) NOT NULL DEFAULT '0',
  `配送方法コード` int(3) NOT NULL DEFAULT '0',
  `配送料額` int(11) NOT NULL DEFAULT '0',
  `代引手数料額` int(11) NOT NULL DEFAULT '0',
  `モールシステム料率` float NOT NULL DEFAULT '0',
  `モールシステム料額` int(11) NOT NULL DEFAULT '0',
  `モール別支払方法別手数料率` float NOT NULL DEFAULT '0',
  `モール別支払方法別手数料額` int(11) NOT NULL DEFAULT '0',
  `仕入原価` int(11) NOT NULL DEFAULT '0',
  `仕入原価の消費税` int(11) NOT NULL DEFAULT '0',
  `粗利額` int(11) NOT NULL DEFAULT '0',
  `購入者名` varchar(50) NOT NULL,
  `購入者電話番号` varchar(30) NOT NULL,
  `出荷予定年月日` date NOT NULL,
  PRIMARY KEY (`伝票番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_cate_directory`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_cate_directory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_cate_directory` (
  `NEディレクトリID` varchar(15) NOT NULL DEFAULT '',
  `rakutencategories_1` varchar(255) NOT NULL,
  `rakutencategories_1_root` varchar(255) NOT NULL,
  `rakutencategories_1_order` int(11) NOT NULL,
  `rakutencategories_1_branch` varchar(255) NOT NULL,
  PRIMARY KEY (`NEディレクトリID`),
  KEY `rakutencategories_1` (`rakutencategories_1`),
  KEY `rakutencategories_1_root` (`rakutencategories_1_root`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_cate_order_ym`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_cate_order_ym`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_cate_order_ym` (
  `cate_code` varchar(255) NOT NULL,
  `受注年月` varchar(6) NOT NULL DEFAULT '',
  `受注年` int(4) DEFAULT NULL,
  `受注月` int(2) DEFAULT NULL,
  `伝票数` int(3) NOT NULL DEFAULT '0',
  `伝票金額` double NOT NULL DEFAULT '0',
  `伝票粗利額` double NOT NULL DEFAULT '0',
  `伝票粗利率` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`cate_code`,`受注年月`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_cate_order_ym_trasition12`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_cate_order_ym_trasition12`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_cate_order_ym_trasition12` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `cate_level` int(1) NOT NULL DEFAULT '1',
  `cate_root` varchar(255) NOT NULL,
  `cate_code` varchar(255) NOT NULL,
  `cate_name` varchar(255) NOT NULL,
  `cate_order` int(11) NOT NULL DEFAULT '0',
  `出品数` int(11) NOT NULL DEFAULT '0',
  `受注年月01` varchar(6) NOT NULL DEFAULT '',
  `伝票数01` int(11) NOT NULL DEFAULT '0',
  `伝票金額01` double NOT NULL DEFAULT '0',
  `伝票粗利額01` double NOT NULL DEFAULT '0',
  `伝票粗利率01` double NOT NULL DEFAULT '0',
  `1伝票粗利額01` double NOT NULL DEFAULT '0',
  `1出品粗利額01` double NOT NULL DEFAULT '0',
  `受注年月02` varchar(6) NOT NULL DEFAULT '',
  `伝票数02` int(11) NOT NULL DEFAULT '0',
  `伝票金額02` double NOT NULL DEFAULT '0',
  `伝票粗利額02` double NOT NULL DEFAULT '0',
  `伝票粗利率02` double NOT NULL DEFAULT '0',
  `1伝票粗利額02` double NOT NULL DEFAULT '0',
  `1出品粗利額02` double NOT NULL DEFAULT '0',
  `受注年月03` varchar(6) NOT NULL DEFAULT '',
  `伝票数03` int(11) NOT NULL DEFAULT '0',
  `伝票金額03` double NOT NULL DEFAULT '0',
  `伝票粗利額03` double NOT NULL DEFAULT '0',
  `伝票粗利率03` double NOT NULL DEFAULT '0',
  `1伝票粗利額03` double NOT NULL DEFAULT '0',
  `1出品粗利額03` double NOT NULL DEFAULT '0',
  `受注年月04` varchar(6) NOT NULL DEFAULT '',
  `伝票数04` int(11) NOT NULL DEFAULT '0',
  `伝票金額04` double NOT NULL DEFAULT '0',
  `伝票粗利額04` double NOT NULL DEFAULT '0',
  `伝票粗利率04` double NOT NULL DEFAULT '0',
  `1伝票粗利額04` double NOT NULL DEFAULT '0',
  `1出品粗利額04` double NOT NULL DEFAULT '0',
  `受注年月05` varchar(6) NOT NULL DEFAULT '',
  `伝票数05` int(11) NOT NULL DEFAULT '0',
  `伝票金額05` double NOT NULL DEFAULT '0',
  `伝票粗利額05` double NOT NULL DEFAULT '0',
  `伝票粗利率05` double NOT NULL DEFAULT '0',
  `1伝票粗利額05` double NOT NULL DEFAULT '0',
  `1出品粗利額05` double NOT NULL DEFAULT '0',
  `受注年月06` varchar(6) NOT NULL DEFAULT '',
  `伝票数06` int(11) NOT NULL DEFAULT '0',
  `伝票金額06` double NOT NULL DEFAULT '0',
  `伝票粗利額06` double NOT NULL DEFAULT '0',
  `伝票粗利率06` double NOT NULL DEFAULT '0',
  `1伝票粗利額06` double NOT NULL DEFAULT '0',
  `1出品粗利額06` double NOT NULL DEFAULT '0',
  `受注年月07` varchar(6) NOT NULL DEFAULT '',
  `伝票数07` int(11) NOT NULL DEFAULT '0',
  `伝票金額07` double NOT NULL DEFAULT '0',
  `伝票粗利額07` double NOT NULL DEFAULT '0',
  `伝票粗利率07` double NOT NULL DEFAULT '0',
  `1伝票粗利額07` double NOT NULL DEFAULT '0',
  `1出品粗利額07` double NOT NULL DEFAULT '0',
  `受注年月08` varchar(6) NOT NULL DEFAULT '',
  `伝票数08` int(11) NOT NULL DEFAULT '0',
  `伝票金額08` double NOT NULL DEFAULT '0',
  `伝票粗利額08` double NOT NULL DEFAULT '0',
  `伝票粗利率08` double NOT NULL DEFAULT '0',
  `1伝票粗利額08` double NOT NULL DEFAULT '0',
  `1出品粗利額08` double NOT NULL DEFAULT '0',
  `受注年月09` varchar(6) NOT NULL DEFAULT '',
  `伝票数09` int(11) NOT NULL DEFAULT '0',
  `伝票金額09` double NOT NULL DEFAULT '0',
  `伝票粗利額09` double NOT NULL DEFAULT '0',
  `伝票粗利率09` double NOT NULL DEFAULT '0',
  `1伝票粗利額09` double NOT NULL DEFAULT '0',
  `1出品粗利額09` double NOT NULL DEFAULT '0',
  `受注年月10` varchar(6) NOT NULL DEFAULT '',
  `伝票数10` int(11) NOT NULL DEFAULT '0',
  `伝票金額10` double NOT NULL DEFAULT '0',
  `伝票粗利額10` double NOT NULL DEFAULT '0',
  `伝票粗利率10` double NOT NULL DEFAULT '0',
  `1伝票粗利額10` double NOT NULL DEFAULT '0',
  `1出品粗利額10` double NOT NULL DEFAULT '0',
  `受注年月11` varchar(6) NOT NULL DEFAULT '',
  `伝票数11` int(11) NOT NULL DEFAULT '0',
  `伝票金額11` double NOT NULL DEFAULT '0',
  `伝票粗利額11` double NOT NULL DEFAULT '0',
  `伝票粗利率11` double NOT NULL DEFAULT '0',
  `1伝票粗利額11` double NOT NULL DEFAULT '0',
  `1出品粗利額11` double NOT NULL DEFAULT '0',
  `受注年月12` varchar(6) NOT NULL DEFAULT '',
  `伝票数12` int(11) NOT NULL DEFAULT '0',
  `伝票金額12` double NOT NULL DEFAULT '0',
  `伝票粗利額12` double NOT NULL DEFAULT '0',
  `伝票粗利率12` double NOT NULL DEFAULT '0',
  `1伝票粗利額12` double NOT NULL DEFAULT '0',
  `1出品粗利額12` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_cate_order_ym_trasition24`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_cate_order_ym_trasition24`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_cate_order_ym_trasition24` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `cate_level` int(1) NOT NULL DEFAULT '1',
  `cate_root` varchar(255) NOT NULL,
  `cate_code` varchar(255) NOT NULL,
  `cate_name` varchar(255) NOT NULL,
  `cate_order` int(11) NOT NULL DEFAULT '0',
  `出品数` int(11) NOT NULL DEFAULT '0',
  `受注年月01` varchar(6) NOT NULL DEFAULT '',
  `伝票数01` int(11) NOT NULL DEFAULT '0',
  `伝票金額01` double NOT NULL DEFAULT '0',
  `伝票粗利額01` double NOT NULL DEFAULT '0',
  `伝票粗利率01` double NOT NULL DEFAULT '0',
  `1伝票粗利額01` double NOT NULL DEFAULT '0',
  `1出品粗利額01` double NOT NULL DEFAULT '0',
  `受注年月02` varchar(6) NOT NULL DEFAULT '',
  `伝票数02` int(11) NOT NULL DEFAULT '0',
  `伝票金額02` double NOT NULL DEFAULT '0',
  `伝票粗利額02` double NOT NULL DEFAULT '0',
  `伝票粗利率02` double NOT NULL DEFAULT '0',
  `1伝票粗利額02` double NOT NULL DEFAULT '0',
  `1出品粗利額02` double NOT NULL DEFAULT '0',
  `受注年月03` varchar(6) NOT NULL DEFAULT '',
  `伝票数03` int(11) NOT NULL DEFAULT '0',
  `伝票金額03` double NOT NULL DEFAULT '0',
  `伝票粗利額03` double NOT NULL DEFAULT '0',
  `伝票粗利率03` double NOT NULL DEFAULT '0',
  `1伝票粗利額03` double NOT NULL DEFAULT '0',
  `1出品粗利額03` double NOT NULL DEFAULT '0',
  `受注年月04` varchar(6) NOT NULL DEFAULT '',
  `伝票数04` int(11) NOT NULL DEFAULT '0',
  `伝票金額04` double NOT NULL DEFAULT '0',
  `伝票粗利額04` double NOT NULL DEFAULT '0',
  `伝票粗利率04` double NOT NULL DEFAULT '0',
  `1伝票粗利額04` double NOT NULL DEFAULT '0',
  `1出品粗利額04` double NOT NULL DEFAULT '0',
  `受注年月05` varchar(6) NOT NULL DEFAULT '',
  `伝票数05` int(11) NOT NULL DEFAULT '0',
  `伝票金額05` double NOT NULL DEFAULT '0',
  `伝票粗利額05` double NOT NULL DEFAULT '0',
  `伝票粗利率05` double NOT NULL DEFAULT '0',
  `1伝票粗利額05` double NOT NULL DEFAULT '0',
  `1出品粗利額05` double NOT NULL DEFAULT '0',
  `受注年月06` varchar(6) NOT NULL DEFAULT '',
  `伝票数06` int(11) NOT NULL DEFAULT '0',
  `伝票金額06` double NOT NULL DEFAULT '0',
  `伝票粗利額06` double NOT NULL DEFAULT '0',
  `伝票粗利率06` double NOT NULL DEFAULT '0',
  `1伝票粗利額06` double NOT NULL DEFAULT '0',
  `1出品粗利額06` double NOT NULL DEFAULT '0',
  `受注年月07` varchar(6) NOT NULL DEFAULT '',
  `伝票数07` int(11) NOT NULL DEFAULT '0',
  `伝票金額07` double NOT NULL DEFAULT '0',
  `伝票粗利額07` double NOT NULL DEFAULT '0',
  `伝票粗利率07` double NOT NULL DEFAULT '0',
  `1伝票粗利額07` double NOT NULL DEFAULT '0',
  `1出品粗利額07` double NOT NULL DEFAULT '0',
  `受注年月08` varchar(6) NOT NULL DEFAULT '',
  `伝票数08` int(11) NOT NULL DEFAULT '0',
  `伝票金額08` double NOT NULL DEFAULT '0',
  `伝票粗利額08` double NOT NULL DEFAULT '0',
  `伝票粗利率08` double NOT NULL DEFAULT '0',
  `1伝票粗利額08` double NOT NULL DEFAULT '0',
  `1出品粗利額08` double NOT NULL DEFAULT '0',
  `受注年月09` varchar(6) NOT NULL DEFAULT '',
  `伝票数09` int(11) NOT NULL DEFAULT '0',
  `伝票金額09` double NOT NULL DEFAULT '0',
  `伝票粗利額09` double NOT NULL DEFAULT '0',
  `伝票粗利率09` double NOT NULL DEFAULT '0',
  `1伝票粗利額09` double NOT NULL DEFAULT '0',
  `1出品粗利額09` double NOT NULL DEFAULT '0',
  `受注年月10` varchar(6) NOT NULL DEFAULT '',
  `伝票数10` int(11) NOT NULL DEFAULT '0',
  `伝票金額10` double NOT NULL DEFAULT '0',
  `伝票粗利額10` double NOT NULL DEFAULT '0',
  `伝票粗利率10` double NOT NULL DEFAULT '0',
  `1伝票粗利額10` double NOT NULL DEFAULT '0',
  `1出品粗利額10` double NOT NULL DEFAULT '0',
  `受注年月11` varchar(6) NOT NULL DEFAULT '',
  `伝票数11` int(11) NOT NULL DEFAULT '0',
  `伝票金額11` double NOT NULL DEFAULT '0',
  `伝票粗利額11` double NOT NULL DEFAULT '0',
  `伝票粗利率11` double NOT NULL DEFAULT '0',
  `1伝票粗利額11` double NOT NULL DEFAULT '0',
  `1出品粗利額11` double NOT NULL DEFAULT '0',
  `受注年月12` varchar(6) NOT NULL DEFAULT '',
  `伝票数12` int(11) NOT NULL DEFAULT '0',
  `伝票金額12` double NOT NULL DEFAULT '0',
  `伝票粗利額12` double NOT NULL DEFAULT '0',
  `伝票粗利率12` double NOT NULL DEFAULT '0',
  `1伝票粗利額12` double NOT NULL DEFAULT '0',
  `1出品粗利額12` double NOT NULL DEFAULT '0',
  `受注年月13` varchar(6) NOT NULL DEFAULT '',
  `伝票数13` int(11) NOT NULL DEFAULT '0',
  `伝票金額13` double NOT NULL DEFAULT '0',
  `伝票粗利額13` double NOT NULL DEFAULT '0',
  `伝票粗利率13` double NOT NULL DEFAULT '0',
  `1伝票粗利額13` double NOT NULL DEFAULT '0',
  `1出品粗利額13` double NOT NULL DEFAULT '0',
  `受注年月14` varchar(6) NOT NULL DEFAULT '',
  `伝票数14` int(11) NOT NULL DEFAULT '0',
  `伝票金額14` double NOT NULL DEFAULT '0',
  `伝票粗利額14` double NOT NULL DEFAULT '0',
  `伝票粗利率14` double NOT NULL DEFAULT '0',
  `1伝票粗利額14` double NOT NULL DEFAULT '0',
  `1出品粗利額14` double NOT NULL DEFAULT '0',
  `受注年月15` varchar(6) NOT NULL DEFAULT '',
  `伝票数15` int(11) NOT NULL DEFAULT '0',
  `伝票金額15` double NOT NULL DEFAULT '0',
  `伝票粗利額15` double NOT NULL DEFAULT '0',
  `伝票粗利率15` double NOT NULL DEFAULT '0',
  `1伝票粗利額15` double NOT NULL DEFAULT '0',
  `1出品粗利額15` double NOT NULL DEFAULT '0',
  `受注年月16` varchar(6) NOT NULL DEFAULT '',
  `伝票数16` int(11) NOT NULL DEFAULT '0',
  `伝票金額16` double NOT NULL DEFAULT '0',
  `伝票粗利額16` double NOT NULL DEFAULT '0',
  `伝票粗利率16` double NOT NULL DEFAULT '0',
  `1伝票粗利額16` double NOT NULL DEFAULT '0',
  `1出品粗利額16` double NOT NULL DEFAULT '0',
  `受注年月17` varchar(6) NOT NULL DEFAULT '',
  `伝票数17` int(11) NOT NULL DEFAULT '0',
  `伝票金額17` double NOT NULL DEFAULT '0',
  `伝票粗利額17` double NOT NULL DEFAULT '0',
  `伝票粗利率17` double NOT NULL DEFAULT '0',
  `1伝票粗利額17` double NOT NULL DEFAULT '0',
  `1出品粗利額17` double NOT NULL DEFAULT '0',
  `受注年月18` varchar(6) NOT NULL DEFAULT '',
  `伝票数18` int(11) NOT NULL DEFAULT '0',
  `伝票金額18` double NOT NULL DEFAULT '0',
  `伝票粗利額18` double NOT NULL DEFAULT '0',
  `伝票粗利率18` double NOT NULL DEFAULT '0',
  `1伝票粗利額18` double NOT NULL DEFAULT '0',
  `1出品粗利額18` double NOT NULL DEFAULT '0',
  `受注年月19` varchar(6) NOT NULL DEFAULT '',
  `伝票数19` int(11) NOT NULL DEFAULT '0',
  `伝票金額19` double NOT NULL DEFAULT '0',
  `伝票粗利額19` double NOT NULL DEFAULT '0',
  `伝票粗利率19` double NOT NULL DEFAULT '0',
  `1伝票粗利額19` double NOT NULL DEFAULT '0',
  `1出品粗利額19` double NOT NULL DEFAULT '0',
  `受注年月20` varchar(6) NOT NULL DEFAULT '',
  `伝票数20` int(11) NOT NULL DEFAULT '0',
  `伝票金額20` double NOT NULL DEFAULT '0',
  `伝票粗利額20` double NOT NULL DEFAULT '0',
  `伝票粗利率20` double NOT NULL DEFAULT '0',
  `1伝票粗利額20` double NOT NULL DEFAULT '0',
  `1出品粗利額20` double NOT NULL DEFAULT '0',
  `受注年月21` varchar(6) NOT NULL DEFAULT '',
  `伝票数21` int(11) NOT NULL DEFAULT '0',
  `伝票金額21` double NOT NULL DEFAULT '0',
  `伝票粗利額21` double NOT NULL DEFAULT '0',
  `伝票粗利率21` double NOT NULL DEFAULT '0',
  `1伝票粗利額21` double NOT NULL DEFAULT '0',
  `1出品粗利額21` double NOT NULL DEFAULT '0',
  `受注年月22` varchar(6) NOT NULL DEFAULT '',
  `伝票数22` int(11) NOT NULL DEFAULT '0',
  `伝票金額22` double NOT NULL DEFAULT '0',
  `伝票粗利額22` double NOT NULL DEFAULT '0',
  `伝票粗利率22` double NOT NULL DEFAULT '0',
  `1伝票粗利額22` double NOT NULL DEFAULT '0',
  `1出品粗利額22` double NOT NULL DEFAULT '0',
  `受注年月23` varchar(6) NOT NULL DEFAULT '',
  `伝票数23` int(11) NOT NULL DEFAULT '0',
  `伝票金額23` double NOT NULL DEFAULT '0',
  `伝票粗利額23` double NOT NULL DEFAULT '0',
  `伝票粗利率23` double NOT NULL DEFAULT '0',
  `1伝票粗利額23` double NOT NULL DEFAULT '0',
  `1出品粗利額23` double NOT NULL DEFAULT '0',
  `受注年月24` varchar(6) NOT NULL DEFAULT '',
  `伝票数24` int(11) NOT NULL DEFAULT '0',
  `伝票金額24` double NOT NULL DEFAULT '0',
  `伝票粗利額24` double NOT NULL DEFAULT '0',
  `伝票粗利率24` double NOT NULL DEFAULT '0',
  `1伝票粗利額24` double NOT NULL DEFAULT '0',
  `1出品粗利額24` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_item_order_ym`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_item_order_ym`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_item_order_ym` (
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `受注年月` varchar(6) NOT NULL DEFAULT '',
  `受注年` int(4) DEFAULT NULL,
  `受注月` int(2) DEFAULT NULL,
  `伝票数` int(3) NOT NULL DEFAULT '0',
  `伝票金額` double NOT NULL DEFAULT '0',
  `伝票粗利額` double NOT NULL DEFAULT '0',
  `伝票粗利率` double NOT NULL DEFAULT '0',
  `受注数` int(3) NOT NULL DEFAULT '0',
  `明細数` int(3) NOT NULL DEFAULT '0',
  `明細金額` double NOT NULL DEFAULT '0',
  `明細粗利額` double NOT NULL DEFAULT '0',
  `明細粗利率` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`,`受注年月`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_item_order_ym_total`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_item_order_ym_total`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_item_order_ym_total` (
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `伝票金額` double DEFAULT NULL,
  `伝票粗利額` double DEFAULT NULL,
  `粗利率` double DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_item_shipping_ym`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_item_shipping_ym`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_item_shipping_ym` (
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  `出荷年月` varchar(6) NOT NULL,
  `出荷年` int(4) DEFAULT NULL,
  `出荷月` int(2) DEFAULT NULL,
  `伝票数` int(3) NOT NULL DEFAULT '0',
  `伝票金額` double NOT NULL DEFAULT '0',
  `伝票粗利額` double NOT NULL DEFAULT '0',
  `伝票粗利率` double NOT NULL DEFAULT '0',
  `受注数` int(3) NOT NULL DEFAULT '0',
  `明細数` int(3) NOT NULL DEFAULT '0',
  `明細金額` double NOT NULL DEFAULT '0',
  `明細粗利額` double NOT NULL DEFAULT '0',
  `明細粗利率` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`,`出荷年月`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_nayose`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_nayose`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_nayose` (
  `伝票番号` int(11) NOT NULL,
  `受注年月日` date NOT NULL,
  `受注年月` varchar(6) NOT NULL,
  `受注年` int(4) DEFAULT '0',
  `受注月` int(2) DEFAULT '0',
  `出荷年月日` date NOT NULL,
  `出荷年月` varchar(6) NOT NULL,
  `出荷年` int(4) NOT NULL DEFAULT '0',
  `出荷月` int(2) NOT NULL DEFAULT '0',
  `ポイント数を含む総合計` int(11) NOT NULL DEFAULT '0',
  `明細数` int(3) NOT NULL DEFAULT '0',
  `店舗コード` int(2) NOT NULL DEFAULT '0',
  `仕入原価` int(11) NOT NULL DEFAULT '0',
  `粗利額` int(11) NOT NULL DEFAULT '0',
  `購入者名` varchar(50) NOT NULL,
  `購入者電話番号` varchar(30) NOT NULL,
  PRIMARY KEY (`伝票番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_nayose_for_change`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_nayose_for_change`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_nayose_for_change` (
  `伝票番号` int(11) NOT NULL,
  `受注年月日` date NOT NULL,
  `受注年月` varchar(6) NOT NULL,
  `受注年` int(4) DEFAULT '0',
  `受注月` int(2) DEFAULT '0',
  `出荷年月日` date NOT NULL,
  `出荷年月` varchar(6) NOT NULL,
  `出荷年` int(4) NOT NULL DEFAULT '0',
  `出荷月` int(2) NOT NULL DEFAULT '0',
  `ポイント数を含む総合計` int(11) NOT NULL DEFAULT '0',
  `明細数` int(3) NOT NULL DEFAULT '0',
  `店舗コード` int(2) NOT NULL DEFAULT '0',
  `仕入原価` int(11) NOT NULL DEFAULT '0',
  `粗利額` int(11) NOT NULL DEFAULT '0',
  `購入者名` varchar(50) NOT NULL,
  `購入者電話番号` varchar(30) NOT NULL,
  PRIMARY KEY (`伝票番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_nayose_sum_change`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_nayose_sum_change`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_nayose_sum_change` (
  `idx` int(3) NOT NULL DEFAULT '0',
  `開始年月日` date NOT NULL,
  `終了年月日` date NOT NULL,
  `伝票数` int(11) NOT NULL DEFAULT '0',
  `占有率` double NOT NULL DEFAULT '0',
  `購入者電話番号のカウント` int(11) NOT NULL DEFAULT '0',
  `購入金額合計の合計` double NOT NULL DEFAULT '0',
  `購入金額平均の平均` double NOT NULL DEFAULT '0',
  `粗利額平均の平均` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`idx`,`伝票数`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_nayose_sum_change2`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_nayose_sum_change2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_nayose_sum_change2` (
  `idx` int(3) NOT NULL DEFAULT '0',
  `開始年月日` date NOT NULL,
  `終了年月日` date NOT NULL,
  `購入回数１回` double NOT NULL DEFAULT '0',
  `購入回数２回` double NOT NULL DEFAULT '0',
  `購入回数３回` double NOT NULL DEFAULT '0',
  `購入回数４回以上` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`idx`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_order_ym`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_order_ym`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_order_ym` (
  `店舗コード` int(11) NOT NULL DEFAULT '0',
  `受注年月` varchar(6) NOT NULL DEFAULT '',
  `受注年` int(4) DEFAULT '0',
  `受注月` int(2) DEFAULT '0',
  `ポイント数を含む総合計` double DEFAULT NULL,
  `平均購入額` int(11) NOT NULL DEFAULT '0',
  `明細数` int(11) NOT NULL DEFAULT '0',
  `伝票数` int(11) NOT NULL DEFAULT '0',
  `総合計` double DEFAULT NULL,
  `商品計` double DEFAULT NULL,
  `税金` double DEFAULT NULL,
  `発送代` double DEFAULT NULL,
  `手数料` double DEFAULT NULL,
  `他費用` double DEFAULT NULL,
  `ポイント数` double DEFAULT NULL,
  `配送料額` double DEFAULT NULL,
  `代引手数料額` double DEFAULT NULL,
  `モールシステム料率` double DEFAULT NULL,
  `モールシステム料額` double DEFAULT NULL,
  `モール別支払方法別手数料率` double DEFAULT NULL,
  `モール別支払方法別手数料額` double DEFAULT NULL,
  `仕入原価` double DEFAULT NULL,
  `仕入原価の消費税` double DEFAULT NULL,
  `粗利額` double DEFAULT NULL,
  `粗利額小計` double DEFAULT NULL,
  `総合計小計` double DEFAULT NULL,
  `伝票数小計` int(11) NOT NULL DEFAULT '0',
  `粗利率` double DEFAULT NULL,
  PRIMARY KEY (`店舗コード`,`受注年月`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_order_ym_repeater`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_order_ym_repeater`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_order_ym_repeater` (
  `店舗コード` int(11) NOT NULL DEFAULT '0',
  `受注年月` varchar(6) NOT NULL DEFAULT '',
  `購入回数` int(5) NOT NULL,
  `受注年` int(4) DEFAULT '0',
  `受注月` int(2) DEFAULT '0',
  `ポイント数を含む総合計` double DEFAULT NULL,
  `明細数` int(11) NOT NULL DEFAULT '0',
  `伝票数` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`店舗コード`,`受注年月`,`購入回数`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_repeater`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_repeater`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_repeater` (
  `受注年月日` date NOT NULL,
  `受注年月` varchar(6) NOT NULL,
  `受注年` int(4) DEFAULT '0',
  `受注月` int(2) DEFAULT '0',
  `ポイント数を含む総合計` int(11) NOT NULL DEFAULT '0',
  `伝票数` int(3) NOT NULL DEFAULT '0',
  `ポイント数を含む総合計_購入１回` int(11) NOT NULL DEFAULT '0',
  `伝票数_購入１回` int(3) NOT NULL DEFAULT '0',
  `購入割合_購入１回` float NOT NULL DEFAULT '0',
  `ポイント数を含む総合計_購入２回` int(11) NOT NULL DEFAULT '0',
  `伝票数_購入２回` int(3) NOT NULL DEFAULT '0',
  `購入割合_購入２回` float NOT NULL DEFAULT '0',
  `ポイント数を含む総合計_購入３回` int(11) NOT NULL DEFAULT '0',
  `伝票数_購入３回` int(3) NOT NULL DEFAULT '0',
  `購入割合_購入３回` float NOT NULL DEFAULT '0',
  `ポイント数を含む総合計_購入４回以上` int(11) NOT NULL DEFAULT '0',
  `伝票数_購入４回以上` int(3) NOT NULL DEFAULT '0',
  `購入割合_購入４回以上` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`受注年月日`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_repeater_shop`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_repeater_shop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_repeater_shop` (
  `店舗コード` varchar(2) NOT NULL,
  `受注年月日` date NOT NULL,
  `受注年月` varchar(6) NOT NULL,
  `受注年` int(4) DEFAULT '0',
  `受注月` int(2) DEFAULT '0',
  `ポイント数を含む総合計` int(11) NOT NULL DEFAULT '0',
  `伝票数` int(3) NOT NULL DEFAULT '0',
  `ポイント数を含む総合計_購入１回` int(11) NOT NULL DEFAULT '0',
  `伝票数_購入１回` int(3) NOT NULL DEFAULT '0',
  `購入割合_購入１回` float NOT NULL DEFAULT '0',
  `ポイント数を含む総合計_購入２回` int(11) NOT NULL DEFAULT '0',
  `伝票数_購入２回` int(3) NOT NULL DEFAULT '0',
  `購入割合_購入２回` float NOT NULL DEFAULT '0',
  `ポイント数を含む総合計_購入３回` int(11) NOT NULL DEFAULT '0',
  `伝票数_購入３回` int(3) NOT NULL DEFAULT '0',
  `購入割合_購入３回` float NOT NULL DEFAULT '0',
  `ポイント数を含む総合計_購入４回以上` int(11) NOT NULL DEFAULT '0',
  `伝票数_購入４回以上` int(3) NOT NULL DEFAULT '0',
  `購入割合_購入４回以上` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`店舗コード`,`受注年月日`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_shipping_ym`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_shipping_ym`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_shipping_ym` (
  `店舗コード` int(11) NOT NULL DEFAULT '0',
  `出荷年月` varchar(6) NOT NULL,
  `出荷年` int(4) NOT NULL DEFAULT '0',
  `出荷月` int(2) NOT NULL DEFAULT '0',
  `ポイント数を含む総合計` double DEFAULT NULL,
  `平均購入額` int(11) NOT NULL DEFAULT '0',
  `明細数` int(11) NOT NULL DEFAULT '0',
  `伝票数` int(11) NOT NULL DEFAULT '0',
  `総合計` double DEFAULT NULL,
  `商品計` double DEFAULT NULL,
  `税金` double DEFAULT NULL,
  `発送代` double DEFAULT NULL,
  `手数料` double DEFAULT NULL,
  `他費用` double DEFAULT NULL,
  `ポイント数` double DEFAULT NULL,
  `配送料額` double DEFAULT NULL,
  `代引手数料額` double DEFAULT NULL,
  `モールシステム料率` double DEFAULT NULL,
  `モールシステム料額` double DEFAULT NULL,
  `モール別支払方法別手数料率` double DEFAULT NULL,
  `モール別支払方法別手数料額` double DEFAULT NULL,
  `仕入原価` double DEFAULT NULL,
  `仕入原価の消費税` double DEFAULT NULL,
  `粗利額` double DEFAULT NULL,
  `粗利額小計` double DEFAULT NULL,
  `総合計小計` double DEFAULT NULL,
  `伝票数小計` int(11) NOT NULL DEFAULT '0',
  `粗利率` double DEFAULT NULL,
  PRIMARY KEY (`店舗コード`,`出荷年月`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_sire_order_ym`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_sire_order_ym`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_sire_order_ym` (
  `sire_code` varchar(10) NOT NULL,
  `受注年月` varchar(6) NOT NULL DEFAULT '',
  `受注年` int(4) DEFAULT NULL,
  `受注月` int(2) DEFAULT NULL,
  `伝票数` int(3) NOT NULL DEFAULT '0',
  `伝票金額` double NOT NULL DEFAULT '0',
  `伝票粗利額` double NOT NULL DEFAULT '0',
  `伝票粗利率` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`sire_code`,`受注年月`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_sire_order_ym_trasition12`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_sire_order_ym_trasition12`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_sire_order_ym_trasition12` (
  `sire_code` varchar(10) NOT NULL,
  `出品数` int(11) NOT NULL DEFAULT '0',
  `受注年月01` varchar(6) NOT NULL DEFAULT '',
  `伝票数01` int(11) NOT NULL DEFAULT '0',
  `伝票金額01` double NOT NULL DEFAULT '0',
  `伝票粗利額01` double NOT NULL DEFAULT '0',
  `伝票粗利率01` double NOT NULL DEFAULT '0',
  `1伝票粗利額01` double NOT NULL DEFAULT '0',
  `1出品粗利額01` double NOT NULL DEFAULT '0',
  `受注年月02` varchar(6) NOT NULL DEFAULT '',
  `伝票数02` int(11) NOT NULL DEFAULT '0',
  `伝票金額02` double NOT NULL DEFAULT '0',
  `伝票粗利額02` double NOT NULL DEFAULT '0',
  `伝票粗利率02` double NOT NULL DEFAULT '0',
  `1伝票粗利額02` double NOT NULL DEFAULT '0',
  `1出品粗利額02` double NOT NULL DEFAULT '0',
  `受注年月03` varchar(6) NOT NULL DEFAULT '',
  `伝票数03` int(11) NOT NULL DEFAULT '0',
  `伝票金額03` double NOT NULL DEFAULT '0',
  `伝票粗利額03` double NOT NULL DEFAULT '0',
  `伝票粗利率03` double NOT NULL DEFAULT '0',
  `1伝票粗利額03` double NOT NULL DEFAULT '0',
  `1出品粗利額03` double NOT NULL DEFAULT '0',
  `受注年月04` varchar(6) NOT NULL DEFAULT '',
  `伝票数04` int(11) NOT NULL DEFAULT '0',
  `伝票金額04` double NOT NULL DEFAULT '0',
  `伝票粗利額04` double NOT NULL DEFAULT '0',
  `伝票粗利率04` double NOT NULL DEFAULT '0',
  `1伝票粗利額04` double NOT NULL DEFAULT '0',
  `1出品粗利額04` double NOT NULL DEFAULT '0',
  `受注年月05` varchar(6) NOT NULL DEFAULT '',
  `伝票数05` int(11) NOT NULL DEFAULT '0',
  `伝票金額05` double NOT NULL DEFAULT '0',
  `伝票粗利額05` double NOT NULL DEFAULT '0',
  `伝票粗利率05` double NOT NULL DEFAULT '0',
  `1伝票粗利額05` double NOT NULL DEFAULT '0',
  `1出品粗利額05` double NOT NULL DEFAULT '0',
  `受注年月06` varchar(6) NOT NULL DEFAULT '',
  `伝票数06` int(11) NOT NULL DEFAULT '0',
  `伝票金額06` double NOT NULL DEFAULT '0',
  `伝票粗利額06` double NOT NULL DEFAULT '0',
  `伝票粗利率06` double NOT NULL DEFAULT '0',
  `1伝票粗利額06` double NOT NULL DEFAULT '0',
  `1出品粗利額06` double NOT NULL DEFAULT '0',
  `受注年月07` varchar(6) NOT NULL DEFAULT '',
  `伝票数07` int(11) NOT NULL DEFAULT '0',
  `伝票金額07` double NOT NULL DEFAULT '0',
  `伝票粗利額07` double NOT NULL DEFAULT '0',
  `伝票粗利率07` double NOT NULL DEFAULT '0',
  `1伝票粗利額07` double NOT NULL DEFAULT '0',
  `1出品粗利額07` double NOT NULL DEFAULT '0',
  `受注年月08` varchar(6) NOT NULL DEFAULT '',
  `伝票数08` int(11) NOT NULL DEFAULT '0',
  `伝票金額08` double NOT NULL DEFAULT '0',
  `伝票粗利額08` double NOT NULL DEFAULT '0',
  `伝票粗利率08` double NOT NULL DEFAULT '0',
  `1伝票粗利額08` double NOT NULL DEFAULT '0',
  `1出品粗利額08` double NOT NULL DEFAULT '0',
  `受注年月09` varchar(6) NOT NULL DEFAULT '',
  `伝票数09` int(11) NOT NULL DEFAULT '0',
  `伝票金額09` double NOT NULL DEFAULT '0',
  `伝票粗利額09` double NOT NULL DEFAULT '0',
  `伝票粗利率09` double NOT NULL DEFAULT '0',
  `1伝票粗利額09` double NOT NULL DEFAULT '0',
  `1出品粗利額09` double NOT NULL DEFAULT '0',
  `受注年月10` varchar(6) NOT NULL DEFAULT '',
  `伝票数10` int(11) NOT NULL DEFAULT '0',
  `伝票金額10` double NOT NULL DEFAULT '0',
  `伝票粗利額10` double NOT NULL DEFAULT '0',
  `伝票粗利率10` double NOT NULL DEFAULT '0',
  `1伝票粗利額10` double NOT NULL DEFAULT '0',
  `1出品粗利額10` double NOT NULL DEFAULT '0',
  `受注年月11` varchar(6) NOT NULL DEFAULT '',
  `伝票数11` int(11) NOT NULL DEFAULT '0',
  `伝票金額11` double NOT NULL DEFAULT '0',
  `伝票粗利額11` double NOT NULL DEFAULT '0',
  `伝票粗利率11` double NOT NULL DEFAULT '0',
  `1伝票粗利額11` double NOT NULL DEFAULT '0',
  `1出品粗利額11` double NOT NULL DEFAULT '0',
  `受注年月12` varchar(6) NOT NULL DEFAULT '',
  `伝票数12` int(11) NOT NULL DEFAULT '0',
  `伝票金額12` double NOT NULL DEFAULT '0',
  `伝票粗利額12` double NOT NULL DEFAULT '0',
  `伝票粗利率12` double NOT NULL DEFAULT '0',
  `1伝票粗利額12` double NOT NULL DEFAULT '0',
  `1出品粗利額12` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`sire_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_sire_order_ym_trasition24`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_sire_order_ym_trasition24`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_sire_order_ym_trasition24` (
  `sire_code` varchar(10) NOT NULL,
  `出品数` int(11) NOT NULL DEFAULT '0',
  `受注年月01` varchar(6) NOT NULL DEFAULT '',
  `伝票数01` int(11) NOT NULL DEFAULT '0',
  `伝票金額01` double NOT NULL DEFAULT '0',
  `伝票粗利額01` double NOT NULL DEFAULT '0',
  `伝票粗利率01` double NOT NULL DEFAULT '0',
  `1伝票粗利額01` double NOT NULL DEFAULT '0',
  `1出品粗利額01` double NOT NULL DEFAULT '0',
  `受注年月02` varchar(6) NOT NULL DEFAULT '',
  `伝票数02` int(11) NOT NULL DEFAULT '0',
  `伝票金額02` double NOT NULL DEFAULT '0',
  `伝票粗利額02` double NOT NULL DEFAULT '0',
  `伝票粗利率02` double NOT NULL DEFAULT '0',
  `1伝票粗利額02` double NOT NULL DEFAULT '0',
  `1出品粗利額02` double NOT NULL DEFAULT '0',
  `受注年月03` varchar(6) NOT NULL DEFAULT '',
  `伝票数03` int(11) NOT NULL DEFAULT '0',
  `伝票金額03` double NOT NULL DEFAULT '0',
  `伝票粗利額03` double NOT NULL DEFAULT '0',
  `伝票粗利率03` double NOT NULL DEFAULT '0',
  `1伝票粗利額03` double NOT NULL DEFAULT '0',
  `1出品粗利額03` double NOT NULL DEFAULT '0',
  `受注年月04` varchar(6) NOT NULL DEFAULT '',
  `伝票数04` int(11) NOT NULL DEFAULT '0',
  `伝票金額04` double NOT NULL DEFAULT '0',
  `伝票粗利額04` double NOT NULL DEFAULT '0',
  `伝票粗利率04` double NOT NULL DEFAULT '0',
  `1伝票粗利額04` double NOT NULL DEFAULT '0',
  `1出品粗利額04` double NOT NULL DEFAULT '0',
  `受注年月05` varchar(6) NOT NULL DEFAULT '',
  `伝票数05` int(11) NOT NULL DEFAULT '0',
  `伝票金額05` double NOT NULL DEFAULT '0',
  `伝票粗利額05` double NOT NULL DEFAULT '0',
  `伝票粗利率05` double NOT NULL DEFAULT '0',
  `1伝票粗利額05` double NOT NULL DEFAULT '0',
  `1出品粗利額05` double NOT NULL DEFAULT '0',
  `受注年月06` varchar(6) NOT NULL DEFAULT '',
  `伝票数06` int(11) NOT NULL DEFAULT '0',
  `伝票金額06` double NOT NULL DEFAULT '0',
  `伝票粗利額06` double NOT NULL DEFAULT '0',
  `伝票粗利率06` double NOT NULL DEFAULT '0',
  `1伝票粗利額06` double NOT NULL DEFAULT '0',
  `1出品粗利額06` double NOT NULL DEFAULT '0',
  `受注年月07` varchar(6) NOT NULL DEFAULT '',
  `伝票数07` int(11) NOT NULL DEFAULT '0',
  `伝票金額07` double NOT NULL DEFAULT '0',
  `伝票粗利額07` double NOT NULL DEFAULT '0',
  `伝票粗利率07` double NOT NULL DEFAULT '0',
  `1伝票粗利額07` double NOT NULL DEFAULT '0',
  `1出品粗利額07` double NOT NULL DEFAULT '0',
  `受注年月08` varchar(6) NOT NULL DEFAULT '',
  `伝票数08` int(11) NOT NULL DEFAULT '0',
  `伝票金額08` double NOT NULL DEFAULT '0',
  `伝票粗利額08` double NOT NULL DEFAULT '0',
  `伝票粗利率08` double NOT NULL DEFAULT '0',
  `1伝票粗利額08` double NOT NULL DEFAULT '0',
  `1出品粗利額08` double NOT NULL DEFAULT '0',
  `受注年月09` varchar(6) NOT NULL DEFAULT '',
  `伝票数09` int(11) NOT NULL DEFAULT '0',
  `伝票金額09` double NOT NULL DEFAULT '0',
  `伝票粗利額09` double NOT NULL DEFAULT '0',
  `伝票粗利率09` double NOT NULL DEFAULT '0',
  `1伝票粗利額09` double NOT NULL DEFAULT '0',
  `1出品粗利額09` double NOT NULL DEFAULT '0',
  `受注年月10` varchar(6) NOT NULL DEFAULT '',
  `伝票数10` int(11) NOT NULL DEFAULT '0',
  `伝票金額10` double NOT NULL DEFAULT '0',
  `伝票粗利額10` double NOT NULL DEFAULT '0',
  `伝票粗利率10` double NOT NULL DEFAULT '0',
  `1伝票粗利額10` double NOT NULL DEFAULT '0',
  `1出品粗利額10` double NOT NULL DEFAULT '0',
  `受注年月11` varchar(6) NOT NULL DEFAULT '',
  `伝票数11` int(11) NOT NULL DEFAULT '0',
  `伝票金額11` double NOT NULL DEFAULT '0',
  `伝票粗利額11` double NOT NULL DEFAULT '0',
  `伝票粗利率11` double NOT NULL DEFAULT '0',
  `1伝票粗利額11` double NOT NULL DEFAULT '0',
  `1出品粗利額11` double NOT NULL DEFAULT '0',
  `受注年月12` varchar(6) NOT NULL DEFAULT '',
  `伝票数12` int(11) NOT NULL DEFAULT '0',
  `伝票金額12` double NOT NULL DEFAULT '0',
  `伝票粗利額12` double NOT NULL DEFAULT '0',
  `伝票粗利率12` double NOT NULL DEFAULT '0',
  `1伝票粗利額12` double NOT NULL DEFAULT '0',
  `1出品粗利額12` double NOT NULL DEFAULT '0',
  `受注年月13` varchar(6) NOT NULL DEFAULT '',
  `伝票数13` int(11) NOT NULL DEFAULT '0',
  `伝票金額13` double NOT NULL DEFAULT '0',
  `伝票粗利額13` double NOT NULL DEFAULT '0',
  `伝票粗利率13` double NOT NULL DEFAULT '0',
  `1伝票粗利額13` double NOT NULL DEFAULT '0',
  `1出品粗利額13` double NOT NULL DEFAULT '0',
  `受注年月14` varchar(6) NOT NULL DEFAULT '',
  `伝票数14` int(11) NOT NULL DEFAULT '0',
  `伝票金額14` double NOT NULL DEFAULT '0',
  `伝票粗利額14` double NOT NULL DEFAULT '0',
  `伝票粗利率14` double NOT NULL DEFAULT '0',
  `1伝票粗利額14` double NOT NULL DEFAULT '0',
  `1出品粗利額14` double NOT NULL DEFAULT '0',
  `受注年月15` varchar(6) NOT NULL DEFAULT '',
  `伝票数15` int(11) NOT NULL DEFAULT '0',
  `伝票金額15` double NOT NULL DEFAULT '0',
  `伝票粗利額15` double NOT NULL DEFAULT '0',
  `伝票粗利率15` double NOT NULL DEFAULT '0',
  `1伝票粗利額15` double NOT NULL DEFAULT '0',
  `1出品粗利額15` double NOT NULL DEFAULT '0',
  `受注年月16` varchar(6) NOT NULL DEFAULT '',
  `伝票数16` int(11) NOT NULL DEFAULT '0',
  `伝票金額16` double NOT NULL DEFAULT '0',
  `伝票粗利額16` double NOT NULL DEFAULT '0',
  `伝票粗利率16` double NOT NULL DEFAULT '0',
  `1伝票粗利額16` double NOT NULL DEFAULT '0',
  `1出品粗利額16` double NOT NULL DEFAULT '0',
  `受注年月17` varchar(6) NOT NULL DEFAULT '',
  `伝票数17` int(11) NOT NULL DEFAULT '0',
  `伝票金額17` double NOT NULL DEFAULT '0',
  `伝票粗利額17` double NOT NULL DEFAULT '0',
  `伝票粗利率17` double NOT NULL DEFAULT '0',
  `1伝票粗利額17` double NOT NULL DEFAULT '0',
  `1出品粗利額17` double NOT NULL DEFAULT '0',
  `受注年月18` varchar(6) NOT NULL DEFAULT '',
  `伝票数18` int(11) NOT NULL DEFAULT '0',
  `伝票金額18` double NOT NULL DEFAULT '0',
  `伝票粗利額18` double NOT NULL DEFAULT '0',
  `伝票粗利率18` double NOT NULL DEFAULT '0',
  `1伝票粗利額18` double NOT NULL DEFAULT '0',
  `1出品粗利額18` double NOT NULL DEFAULT '0',
  `受注年月19` varchar(6) NOT NULL DEFAULT '',
  `伝票数19` int(11) NOT NULL DEFAULT '0',
  `伝票金額19` double NOT NULL DEFAULT '0',
  `伝票粗利額19` double NOT NULL DEFAULT '0',
  `伝票粗利率19` double NOT NULL DEFAULT '0',
  `1伝票粗利額19` double NOT NULL DEFAULT '0',
  `1出品粗利額19` double NOT NULL DEFAULT '0',
  `受注年月20` varchar(6) NOT NULL DEFAULT '',
  `伝票数20` int(11) NOT NULL DEFAULT '0',
  `伝票金額20` double NOT NULL DEFAULT '0',
  `伝票粗利額20` double NOT NULL DEFAULT '0',
  `伝票粗利率20` double NOT NULL DEFAULT '0',
  `1伝票粗利額20` double NOT NULL DEFAULT '0',
  `1出品粗利額20` double NOT NULL DEFAULT '0',
  `受注年月21` varchar(6) NOT NULL DEFAULT '',
  `伝票数21` int(11) NOT NULL DEFAULT '0',
  `伝票金額21` double NOT NULL DEFAULT '0',
  `伝票粗利額21` double NOT NULL DEFAULT '0',
  `伝票粗利率21` double NOT NULL DEFAULT '0',
  `1伝票粗利額21` double NOT NULL DEFAULT '0',
  `1出品粗利額21` double NOT NULL DEFAULT '0',
  `受注年月22` varchar(6) NOT NULL DEFAULT '',
  `伝票数22` int(11) NOT NULL DEFAULT '0',
  `伝票金額22` double NOT NULL DEFAULT '0',
  `伝票粗利額22` double NOT NULL DEFAULT '0',
  `伝票粗利率22` double NOT NULL DEFAULT '0',
  `1伝票粗利額22` double NOT NULL DEFAULT '0',
  `1出品粗利額22` double NOT NULL DEFAULT '0',
  `受注年月23` varchar(6) NOT NULL DEFAULT '',
  `伝票数23` int(11) NOT NULL DEFAULT '0',
  `伝票金額23` double NOT NULL DEFAULT '0',
  `伝票粗利額23` double NOT NULL DEFAULT '0',
  `伝票粗利率23` double NOT NULL DEFAULT '0',
  `1伝票粗利額23` double NOT NULL DEFAULT '0',
  `1出品粗利額23` double NOT NULL DEFAULT '0',
  `受注年月24` varchar(6) NOT NULL DEFAULT '',
  `伝票数24` int(11) NOT NULL DEFAULT '0',
  `伝票金額24` double NOT NULL DEFAULT '0',
  `伝票粗利額24` double NOT NULL DEFAULT '0',
  `伝票粗利率24` double NOT NULL DEFAULT '0',
  `1伝票粗利額24` double NOT NULL DEFAULT '0',
  `1出品粗利額24` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`sire_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_ym_a`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_ym_a`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_ym_a` (
  `伝票番号` int(11) NOT NULL,
  `受注年月日` date NOT NULL,
  `受注年月` varchar(6) NOT NULL,
  `受注年` int(4) DEFAULT '0',
  `受注月` int(2) DEFAULT '0',
  `店舗コード` int(11) NOT NULL DEFAULT '0',
  `ポイント数を含む総合計` int(11) NOT NULL DEFAULT '0',
  `明細数` int(3) NOT NULL DEFAULT '0',
  `総合計` int(11) DEFAULT '0',
  `商品計` int(11) DEFAULT '0',
  `税金` int(11) DEFAULT '0',
  `発送代` int(11) DEFAULT '0',
  `手数料` int(11) DEFAULT '0',
  `他費用` int(11) DEFAULT '0',
  `ポイント数` int(11) DEFAULT '0',
  PRIMARY KEY (`伝票番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_ym_shop`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_ym_shop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_ym_shop` (
  `yyyymm` varchar(6) NOT NULL,
  `total` double NOT NULL DEFAULT '0',
  `shop01` double NOT NULL DEFAULT '0',
  `shop02` double NOT NULL DEFAULT '0',
  `shop03` double NOT NULL DEFAULT '0',
  `shop04` double NOT NULL DEFAULT '0',
  `shop05` double NOT NULL DEFAULT '0',
  `shop06` double NOT NULL DEFAULT '0',
  `shop07` double NOT NULL DEFAULT '0',
  `shop08` double NOT NULL DEFAULT '0',
  `shop09` double NOT NULL DEFAULT '0',
  `shop10` double NOT NULL DEFAULT '0',
  `shop11` double NOT NULL DEFAULT '0',
  `shop12` double NOT NULL DEFAULT '0',
  `shop13` double NOT NULL DEFAULT '0',
  `shop14` double NOT NULL DEFAULT '0',
  `shop15` double NOT NULL DEFAULT '0',
  `shop16` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`yyyymm`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sales_detail_voucher_ym_shop_repeater`
--

DROP TABLE IF EXISTS `tb_sales_detail_voucher_ym_shop_repeater`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sales_detail_voucher_ym_shop_repeater` (
  `yyyymm` varchar(6) NOT NULL,
  `total` double NOT NULL DEFAULT '0',
  `shop01` double NOT NULL DEFAULT '0',
  `shop02` double NOT NULL DEFAULT '0',
  `shop03` double NOT NULL DEFAULT '0',
  `shop04` double NOT NULL DEFAULT '0',
  `shop05` double NOT NULL DEFAULT '0',
  `shop06` double NOT NULL DEFAULT '0',
  `shop07` double NOT NULL DEFAULT '0',
  `shop08` double NOT NULL DEFAULT '0',
  `shop09` double NOT NULL DEFAULT '0',
  `shop10` double NOT NULL DEFAULT '0',
  `shop11` double NOT NULL DEFAULT '0',
  `shop12` double NOT NULL DEFAULT '0',
  `shop13` double NOT NULL DEFAULT '0',
  `shop14` double NOT NULL DEFAULT '0',
  `shop15` double NOT NULL DEFAULT '0',
  `shop16` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`yyyymm`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_setting`
--

DROP TABLE IF EXISTS `tb_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_setting` (
  `setting_key` varchar(30) NOT NULL,
  `setting_val` varchar(255) NOT NULL,
  `setting_desc` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_shipping_delay`
--

DROP TABLE IF EXISTS `tb_shipping_delay`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_shipping_delay` (
  `伝票番号` varchar(255) NOT NULL DEFAULT '',
  `明細行` int(11) NOT NULL DEFAULT '0',
  `個別商品コード` varchar(255) DEFAULT NULL,
  `代表商品コード` varchar(255) DEFAULT NULL,
  `仕入先CD` varchar(255) DEFAULT NULL,
  `個別出荷予定日` date DEFAULT NULL,
  `伝票出荷予定日` date DEFAULT NULL,
  `伝票出荷予定日N日前` int(11) DEFAULT '0',
  `出荷遅延マーク` varchar(255) DEFAULT NULL,
  `受注日` datetime DEFAULT NULL,
  PRIMARY KEY (`伝票番号`,`明細行`),
  KEY `伝票出荷予定日N日前` (`伝票出荷予定日N日前`) USING BTREE,
  KEY `個別出荷予定日` (`個別出荷予定日`) USING BTREE,
  KEY `伝票出荷予定日` (`伝票出荷予定日`) USING BTREE,
  KEY `伝票出荷予定日N日前_2` (`伝票出荷予定日N日前`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_shipping_fixdate`
--

DROP TABLE IF EXISTS `tb_shipping_fixdate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_shipping_fixdate` (
  `shipping_date` date NOT NULL,
  `shipping_fixdate` date DEFAULT NULL,
  PRIMARY KEY (`shipping_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_shippingdivision`
--

DROP TABLE IF EXISTS `tb_shippingdivision`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_shippingdivision` (
  `ne-souryo_kbn_code` varchar(255) NOT NULL,
  `送料設定名` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ne-souryo_kbn_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_shopping_mall`
--

DROP TABLE IF EXISTS `tb_shopping_mall`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_shopping_mall` (
  `mall_id` int(11) NOT NULL AUTO_INCREMENT,
  `ne_mall_id` int(2) NOT NULL COMMENT 'NEモールID',
  `mall_name` varchar(100) NOT NULL,
  `mall_name_short1` varchar(10) NOT NULL,
  `mall_name_short2` varchar(10) NOT NULL,
  `ne_mall_name` varchar(50) NOT NULL COMMENT 'NEモール名',
  `mall_url` varchar(255) NOT NULL,
  `additional_cost_ratio` int(11) NOT NULL DEFAULT '0' COMMENT '付加費用率(%)',
  `system_usage_cost_ratio` float NOT NULL DEFAULT '0' COMMENT 'システム利用料(%)',
  `obey_postage_setting` tinyint(1) NOT NULL DEFAULT '-1' COMMENT '送料設定に従う',
  `mall_desc` varchar(255) DEFAULT NULL,
  `mall_sort` int(11) NOT NULL,
  PRIMARY KEY (`mall_id`),
  UNIQUE KEY `ne_mall_id` (`ne_mall_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_soldout_check`
--

DROP TABLE IF EXISTS `tb_soldout_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_soldout_check` (
  `daihyo_syohin_code` varchar(50) NOT NULL DEFAULT '',
  `sire_code` varchar(4) NOT NULL,
  `setnum` int(11) NOT NULL DEFAULT '0',
  `soldout_check_flg` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_category_list`
--

DROP TABLE IF EXISTS `tb_ss_category_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_category_list` (
  `表示順` int(11) NOT NULL DEFAULT '0',
  `表示` varchar(255) DEFAULT NULL,
  `カテゴリ` varchar(255) DEFAULT NULL,
  `タイトル` varchar(255) DEFAULT NULL,
  `キーワード` varchar(255) DEFAULT NULL,
  `ディスクリプション` varchar(255) DEFAULT NULL,
  `まとめ買い表示方法` varchar(255) DEFAULT NULL,
  `まとめ買いボタン表示（上部）` varchar(255) DEFAULT NULL,
  `まとめ買いボタン表示（中央）` varchar(255) DEFAULT NULL,
  `まとめ買いボタン表示（下部）` varchar(255) DEFAULT NULL,
  `まとめ買いボタン名` varchar(255) DEFAULT NULL,
  `まとめ買い案内文` varchar(255) DEFAULT NULL,
  `カテゴリ専用ヘッダ（PC用）` varchar(255) DEFAULT NULL,
  `カテゴリ専用フッタ（PC用）` varchar(255) DEFAULT NULL,
  `関連商品1` varchar(255) DEFAULT NULL,
  `関連商品2` varchar(255) DEFAULT NULL,
  `関連商品3` varchar(255) DEFAULT NULL,
  `関連商品4` varchar(255) DEFAULT NULL,
  `関連商品5` varchar(255) DEFAULT NULL,
  `関連商品6` varchar(255) DEFAULT NULL,
  `関連商品7` varchar(255) DEFAULT NULL,
  `関連商品8` varchar(255) DEFAULT NULL,
  `関連商品9` varchar(255) DEFAULT NULL,
  `関連商品10` varchar(255) DEFAULT NULL,
  `カテゴリ専用ヘッダ（携帯用オプション）` varchar(255) DEFAULT NULL,
  `カテゴリ専用ヘッダ（スマートフォン用オプション）` varchar(255) DEFAULT NULL,
  `カテゴリ専用フッタ（携帯用オプション）` varchar(255) DEFAULT NULL,
  `カテゴリ専用フッタ（スマートフォン用オプション）` varchar(255) DEFAULT NULL,
  `カテゴリの商品一覧ページURL` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_image_del`
--

DROP TABLE IF EXISTS `tb_ss_image_del`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_image_del` (
  `画像ファイル名` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`画像ファイル名`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_image_upload`
--

DROP TABLE IF EXISTS `tb_ss_image_upload`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_image_upload` (
  `画像ファイル名` varchar(255) NOT NULL DEFAULT '',
  `ファイル名` varchar(255) DEFAULT NULL,
  `代替テキスト` varchar(255) DEFAULT NULL,
  `カテゴリ` varchar(255) DEFAULT '商品画像',
  PRIMARY KEY (`画像ファイル名`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_information`
--

DROP TABLE IF EXISTS `tb_ss_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_information` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `registration_flg` tinyint(3) NOT NULL DEFAULT '0' COMMENT '登録フラグ',
  `original_price` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `exist_image` tinyint(1) NOT NULL DEFAULT '0' COMMENT '画像有無',
  `ss_title` varchar(255) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT '0' COMMENT '優先順位',
  `sort` int(11) NOT NULL DEFAULT '0',
  `商品名` varchar(255) NOT NULL,
  `ショップサーブカテゴリ名` varchar(255) DEFAULT NULL,
  `ショップサーブカテゴリID` varchar(50) DEFAULT NULL,
  `販売価格` varchar(11) DEFAULT NULL,
  `定価項目名` varchar(100) DEFAULT NULL,
  `型番品番` varchar(100) DEFAULT NULL,
  `ポイント還元率` varchar(11) DEFAULT NULL,
  `最低個数` varchar(11) DEFAULT NULL,
  `最高個数` varchar(11) DEFAULT NULL,
  `重量` varchar(11) DEFAULT NULL,
  `個別送料設定` varchar(1) DEFAULT NULL,
  `個別送料` varchar(11) DEFAULT NULL,
  `優先` varchar(1) DEFAULT NULL,
  `画像ファイル名` varchar(255) DEFAULT NULL,
  `メイン紹介文` mediumtext,
  `サブ紹介文１` mediumtext,
  `サブ紹介文２` varchar(255) DEFAULT NULL,
  `携帯用メイン紹介文` varchar(255) DEFAULT NULL,
  `携帯用サブ紹介文１` varchar(255) DEFAULT NULL,
  `携帯用サブ紹介文２` varchar(255) DEFAULT NULL,
  `商品ページタイトル` varchar(255) DEFAULT NULL,
  `商品ページキーワード` varchar(100) DEFAULT NULL,
  `外部用キャッチコピー` mediumtext,
  `内部用キャッチコピー` varchar(100) DEFAULT NULL,
  `携帯用内部キャッチコピー` varchar(100) DEFAULT NULL,
  `シークレットグループ` varchar(50) DEFAULT NULL,
  `新着` varchar(1) DEFAULT NULL,
  `おすすめ` varchar(1) DEFAULT NULL,
  `備考１` varchar(1) DEFAULT NULL,
  `備考欄名１` varchar(50) DEFAULT NULL,
  `備考２` varchar(1) DEFAULT NULL,
  `備考欄名２` varchar(50) DEFAULT NULL,
  `備考３` varchar(1) DEFAULT NULL,
  `備考欄名３` varchar(50) DEFAULT NULL,
  `自作商品ページの商品URL` varchar(255) DEFAULT NULL,
  `サブ画像１` varchar(255) DEFAULT NULL,
  `サブ画像２` varchar(255) DEFAULT NULL,
  `サブ画像３` varchar(255) DEFAULT NULL,
  `サブ画像４` varchar(255) DEFAULT NULL,
  `サブ画像５` varchar(255) DEFAULT NULL,
  `サブ画像６` varchar(255) DEFAULT NULL,
  `サブ画像７` varchar(255) DEFAULT NULL,
  `サブ画像８` varchar(255) DEFAULT NULL,
  `サブ画像９` varchar(255) DEFAULT NULL,
  `サブ画像１０` varchar(255) DEFAULT NULL,
  `JANコード` varchar(14) DEFAULT NULL,
  `メーカー` varchar(100) DEFAULT NULL,
  `陳列期間` varchar(30) DEFAULT NULL,
  `セール項目名` varchar(50) DEFAULT NULL,
  `セール価格` varchar(11) DEFAULT NULL,
  `開始年月日` varchar(8) DEFAULT NULL,
  `開始時刻` varchar(6) DEFAULT NULL,
  `終了年月日` varchar(8) DEFAULT NULL,
  `終了時刻` varchar(6) DEFAULT NULL,
  `商品説明` varchar(255) DEFAULT NULL,
  `製品重量` varchar(11) DEFAULT NULL,
  `製品重量（単位）` varchar(5) DEFAULT NULL,
  `関連商品` varchar(255) DEFAULT NULL,
  `並び順番号` varchar(11) DEFAULT NULL,
  `評価コメントの設定１` varchar(1) DEFAULT NULL,
  `評価コメントの設定２` varchar(1) DEFAULT NULL,
  `携帯用メイン紹介文（オプション）` varchar(50) DEFAULT NULL,
  `スマフォ用メイン紹介文（オプション）` varchar(50) DEFAULT NULL,
  `携帯用サブ紹介文１（オプション）` varchar(50) DEFAULT NULL,
  `スマフォ用サブ紹介文１（オプション）` varchar(50) DEFAULT NULL,
  `携帯用サブ紹介文２（オプション）` varchar(50) DEFAULT NULL,
  `スマフォ用サブ紹介文２（オプション）` varchar(50) DEFAULT NULL,
  `携帯用内部キャッチコピー（オプション）` varchar(50) DEFAULT NULL,
  `スマフォ用内部キャッチコピー（オプション）` varchar(50) DEFAULT NULL,
  `カテゴリ1` varchar(50) DEFAULT NULL,
  `メール便` varchar(1) DEFAULT NULL,
  `同梱不可設定` varchar(1) NOT NULL,
  `決済方法` varchar(20) NOT NULL,
  `Googleファッション属性性別` varchar(1) DEFAULT NULL,
  `Googleファッション属性世代` varchar(1) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_itemlist_add`
--

DROP TABLE IF EXISTS `tb_ss_itemlist_add`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_itemlist_add` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `商品番号` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_itemlist_del`
--

DROP TABLE IF EXISTS `tb_ss_itemlist_del`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_itemlist_del` (
  `商品番号` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`商品番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_itemlist_dl`
--

DROP TABLE IF EXISTS `tb_ss_itemlist_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_itemlist_dl` (
  `商品番号` varchar(50) NOT NULL DEFAULT '',
  `商品名` varchar(255) DEFAULT NULL,
  `ショップサーブカテゴリ名` varchar(255) DEFAULT NULL,
  `ショップサーブカテゴリID` varchar(255) DEFAULT NULL,
  `グループ` varchar(255) DEFAULT NULL,
  `販売価格` varchar(11) DEFAULT NULL,
  `定価項目名` varchar(255) DEFAULT NULL,
  `定価表示` varchar(1) DEFAULT NULL,
  `定価` varchar(255) DEFAULT NULL,
  `型番/品番` varchar(255) DEFAULT NULL,
  `ポイント還元率` varchar(255) DEFAULT NULL,
  `購入数制限設定` varchar(1) DEFAULT NULL,
  `最低個数` varchar(255) DEFAULT NULL,
  `最高個数` varchar(255) DEFAULT NULL,
  `単位` varchar(5) DEFAULT NULL,
  `重量` varchar(255) DEFAULT NULL,
  `個別送料設定` varchar(1) DEFAULT NULL,
  `個別送料` varchar(11) DEFAULT NULL,
  `送料無料表示` varchar(1) DEFAULT NULL,
  `優先` varchar(1) DEFAULT NULL,
  `クール便` varchar(1) DEFAULT NULL,
  `画像ファイル名` varchar(255) DEFAULT NULL,
  `メイン紹介文` varchar(255) DEFAULT NULL,
  `サブ紹介文１` varchar(255) DEFAULT NULL,
  `カートボタンの設定１` varchar(255) DEFAULT NULL,
  `サブ紹介文２` varchar(255) DEFAULT NULL,
  `カートボタンの設定２` varchar(255) DEFAULT NULL,
  `商品ページタイトル` varchar(255) DEFAULT NULL,
  `商品ページキーワード` varchar(255) DEFAULT NULL,
  `外部用キャッチコピー` varchar(255) DEFAULT NULL,
  `内部用キャッチコピー` varchar(255) DEFAULT NULL,
  `公開・非公開` varchar(1) DEFAULT NULL,
  `限定販売方法` varchar(1) DEFAULT NULL,
  `シークレットグループ` varchar(255) DEFAULT NULL,
  `新着` varchar(1) DEFAULT NULL,
  `おすすめ` varchar(1) DEFAULT NULL,
  `問合せフォーム` varchar(1) DEFAULT NULL,
  `友達に教える` varchar(1) DEFAULT NULL,
  `カゴに入れる` varchar(1) DEFAULT NULL,
  `在庫をみる` varchar(1) DEFAULT NULL,
  `携帯に送る` varchar(1) DEFAULT NULL,
  `QRコード` varchar(1) DEFAULT NULL,
  `備考１` varchar(1) DEFAULT NULL,
  `備考欄名１` varchar(255) DEFAULT NULL,
  `備考２` varchar(1) DEFAULT NULL,
  `備考欄名２` varchar(255) DEFAULT NULL,
  `備考３` varchar(1) DEFAULT NULL,
  `備考欄名３` varchar(255) DEFAULT NULL,
  `自作商品ページの商品URL` varchar(255) DEFAULT NULL,
  `パーク出品サービスへの出品` varchar(1) DEFAULT NULL,
  `ショッピングフィードplus10への出品` varchar(1) DEFAULT NULL,
  `複数画像登録機能` varchar(1) DEFAULT NULL,
  `サブ画像１` varchar(255) DEFAULT NULL,
  `サブ画像２` varchar(255) DEFAULT NULL,
  `サブ画像３` varchar(255) DEFAULT NULL,
  `サブ画像４` varchar(255) DEFAULT NULL,
  `サブ画像５` varchar(255) DEFAULT NULL,
  `JANコード` varchar(255) DEFAULT NULL,
  `新品・中古区分` varchar(1) DEFAULT NULL,
  `メーカー` varchar(255) DEFAULT NULL,
  `商品追加情報を表示する` varchar(1) DEFAULT NULL,
  `陳列期間・セール期間を指定する` varchar(1) DEFAULT NULL,
  `陳列期間` varchar(255) DEFAULT NULL,
  `セール期間` varchar(255) DEFAULT NULL,
  `セール項目名` varchar(255) DEFAULT NULL,
  `セール価格` varchar(255) DEFAULT NULL,
  `開始年月日` varchar(255) DEFAULT NULL,
  `開始時刻` varchar(255) DEFAULT NULL,
  `終了年月日` varchar(255) DEFAULT NULL,
  `終了時刻` varchar(255) DEFAULT NULL,
  `商品説明` varchar(255) DEFAULT NULL,
  `製品名` varchar(255) DEFAULT NULL,
  `製造年` varchar(255) DEFAULT NULL,
  `幅` varchar(255) DEFAULT NULL,
  `奥行` varchar(255) DEFAULT NULL,
  `高さ` varchar(255) DEFAULT NULL,
  `製品重量` varchar(255) DEFAULT NULL,
  `製品重量（単位）` varchar(255) DEFAULT NULL,
  `新品・中古区分を表示する` varchar(1) DEFAULT NULL,
  `関連商品` varchar(255) DEFAULT NULL,
  `入荷連絡` varchar(1) DEFAULT NULL,
  `並び順番号` varchar(255) DEFAULT NULL,
  `評価コメントの設定` varchar(1) DEFAULT NULL,
  `評価コメントの設定１` varchar(1) DEFAULT NULL,
  `評価コメントの設定２` varchar(1) DEFAULT NULL,
  `携帯用メイン紹介文（オプション）` varchar(50) DEFAULT NULL,
  `スマフォ用メイン紹介文（オプション）` varchar(50) DEFAULT NULL,
  `携帯用サブ紹介文１（オプション）` varchar(50) DEFAULT NULL,
  `スマフォ用サブ紹介文１（オプション）` varchar(50) DEFAULT NULL,
  `携帯用サブ紹介文２（オプション）` varchar(50) DEFAULT NULL,
  `スマフォ用サブ紹介文２（オプション）` varchar(50) DEFAULT NULL,
  `携帯用内部キャッチコピー（オプション）` varchar(50) DEFAULT NULL,
  `スマフォ用内部キャッチコピー（オプション）` varchar(50) DEFAULT NULL,
  `販売方法` varchar(1) DEFAULT NULL,
  `会員ランク特典` varchar(1) DEFAULT NULL,
  `カテゴリ1` varchar(255) DEFAULT NULL,
  `カテゴリ2` varchar(255) DEFAULT NULL,
  `カテゴリ3` varchar(255) DEFAULT NULL,
  `カテゴリ4` varchar(255) DEFAULT NULL,
  `カテゴリ5` varchar(255) DEFAULT NULL,
  `メール便` varchar(255) DEFAULT NULL,
  `同梱不可設定` varchar(255) DEFAULT NULL,
  `決済方法` varchar(255) DEFAULT NULL,
  `Googleカテゴリ（編集不可）` varchar(255) DEFAULT NULL,
  `Googleファッション属性性別` varchar(255) DEFAULT NULL,
  `Googleファッション属性世代` varchar(255) DEFAULT NULL,
  `Googleファッション属性色` varchar(255) DEFAULT NULL,
  `Googleファッション属性サイズ` varchar(255) DEFAULT NULL,
  `Googleファッション属性素材` varchar(255) DEFAULT NULL,
  `Googleファッション属性柄` varchar(255) DEFAULT NULL,
  `予約期間を指定する` varchar(1) DEFAULT NULL,
  `予約表示設定` varchar(255) DEFAULT NULL,
  `予約価格設定` varchar(255) DEFAULT NULL,
  `予約価格項目名` varchar(255) DEFAULT NULL,
  `予約価格` varchar(255) DEFAULT NULL,
  `予約開始年月日` varchar(255) DEFAULT NULL,
  `予約開始時刻` varchar(255) DEFAULT NULL,
  `予約終了年月日` varchar(255) DEFAULT NULL,
  `予約終了時刻` varchar(255) DEFAULT NULL,
  `Google広告` varchar(1) DEFAULT NULL,
  `Criteo広告` varchar(1) DEFAULT NULL,
  `Criteo用カテゴリ1` varchar(255) DEFAULT NULL,
  `Criteo用カテゴリ2` varchar(255) DEFAULT NULL,
  `Criteo用カテゴリ3` varchar(255) DEFAULT NULL,
  `サブ画像６` varchar(255) DEFAULT NULL,
  `サブ画像７` varchar(255) DEFAULT NULL,
  `サブ画像８` varchar(255) DEFAULT NULL,
  `サブ画像９` varchar(255) DEFAULT NULL,
  `サブ画像１０` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`商品番号`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_stock`
--

DROP TABLE IF EXISTS `tb_ss_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_stock` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `商品番号` varchar(30) NOT NULL,
  `商品名<>バリエーション` varchar(255) NOT NULL DEFAULT '',
  `現在の在庫数` varchar(255) DEFAULT NULL,
  `変更する在庫数` varchar(255) DEFAULT NULL,
  `現在の在庫わずか機能` varchar(255) DEFAULT NULL,
  `変更後の在庫わずか機能` varchar(255) DEFAULT NULL,
  `入荷連絡待ち` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_variation`
--

DROP TABLE IF EXISTS `tb_ss_variation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_variation` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `並び順no` int(3) NOT NULL,
  `商品番号` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `在庫管理` varchar(255) DEFAULT NULL,
  `バリエーション１項目名` varchar(255) DEFAULT NULL,
  `バリエーション１選択肢` varchar(255) DEFAULT NULL,
  `バリエーション２項目名` varchar(255) DEFAULT NULL,
  `バリエーション２選択肢` varchar(255) DEFAULT NULL,
  `バリエーション３項目名` varchar(255) DEFAULT NULL,
  `バリエーション３選択肢` varchar(255) DEFAULT NULL,
  `商品枝番号` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_ss_variation_dl`
--

DROP TABLE IF EXISTS `tb_ss_variation_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_ss_variation_dl` (
  `商品番号` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `在庫管理` varchar(255) DEFAULT NULL,
  `バリエーション１項目名` varchar(255) DEFAULT NULL,
  `バリエーション１選択肢` varchar(255) DEFAULT NULL,
  `バリエーション２項目名` varchar(255) DEFAULT NULL,
  `バリエーション２選択肢` varchar(255) DEFAULT NULL,
  `バリエーション３項目名` varchar(255) DEFAULT NULL,
  `バリエーション３選択肢` varchar(255) DEFAULT NULL,
  `商品枝番号` varchar(255) NOT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ne_syohin_syohin_code` varchar(255) NOT NULL,
  `colname` varchar(255) NOT NULL,
  `colcode` varchar(100) NOT NULL,
  `rowname` varchar(255) NOT NULL,
  `rowcode` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_stock_history`
--

DROP TABLE IF EXISTS `tb_stock_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_stock_history` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `在庫日時` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `フリー在庫数` int(5) NOT NULL DEFAULT '0',
  `現在庫数` int(5) NOT NULL DEFAULT '0',
  `フリー在庫金額` int(11) NOT NULL DEFAULT '0',
  `現在庫金額` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_stockreturn`
--

DROP TABLE IF EXISTS `tb_stockreturn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_stockreturn` (
  `入出庫日` datetime NOT NULL,
  `入出1` varchar(255) DEFAULT NULL,
  `入出2` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `原価` varchar(255) DEFAULT NULL,
  `入出庫数` int(10) unsigned DEFAULT NULL,
  `担当者名` varchar(255) DEFAULT NULL,
  `理由` varchar(255) DEFAULT NULL,
  `inventory` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sudden_sales_diff`
--

DROP TABLE IF EXISTS `tb_sudden_sales_diff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sudden_sales_diff` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `buy_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sudden_sales_diff_new`
--

DROP TABLE IF EXISTS `tb_sudden_sales_diff_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sudden_sales_diff_new` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `buy_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sudden_sales_diff_pre`
--

DROP TABLE IF EXISTS `tb_sudden_sales_diff_pre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sudden_sales_diff_pre` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `buy_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sudden_sales_post`
--

DROP TABLE IF EXISTS `tb_sudden_sales_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sudden_sales_post` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `buy_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_sudden_sales_pre`
--

DROP TABLE IF EXISTS `tb_sudden_sales_pre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_sudden_sales_pre` (
  `daihyo_syohin_code` varchar(50) NOT NULL,
  `buy_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_title_parts`
--

DROP TABLE IF EXISTS `tb_title_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_title_parts` (
  `daihyo_syohin_code` varchar(255) NOT NULL,
  `front_title` varchar(255) DEFAULT NULL,
  `back_title` varchar(255) DEFAULT NULL,
  `directory` varchar(255) DEFAULT NULL,
  `directory_ex` varchar(255) DEFAULT NULL,
  `directory_ex2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`daihyo_syohin_code`),
  KEY `Index_2` (`front_title`,`back_title`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_title_parts_target`
--

DROP TABLE IF EXISTS `tb_title_parts_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_title_parts_target` (
  `daihyo_syohin_code` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_totalstock_dl`
--

DROP TABLE IF EXISTS `tb_totalstock_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_totalstock_dl` (
  `商品コード` varchar(255) NOT NULL,
  `商品名` varchar(255) DEFAULT NULL,
  `在庫数` int(10) DEFAULT NULL,
  `引当数` int(10) DEFAULT NULL,
  `フリー在庫数` int(10) DEFAULT NULL,
  `予約在庫数` int(10) DEFAULT NULL,
  `予約引当数` int(10) DEFAULT NULL,
  `予約フリー在庫数` int(10) DEFAULT NULL,
  `不良在庫数` int(10) DEFAULT NULL,
  PRIMARY KEY (`商品コード`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_updaterecord`
--

DROP TABLE IF EXISTS `tb_updaterecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_updaterecord` (
  `updaterecordno` int(10) unsigned NOT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`updaterecordno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_vendoraddress`
--

DROP TABLE IF EXISTS `tb_vendoraddress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_vendoraddress` (
  `daihyo_syohin_code` varchar(50) DEFAULT NULL,
  `sire_code` varchar(10) DEFAULT NULL,
  `sire_adress` varchar(200) DEFAULT NULL,
  `vendoraddress_CD` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setbefore` int(10) DEFAULT '0',
  `setafter` int(10) unsigned DEFAULT '0',
  `checkdate` datetime DEFAULT NULL,
  `stop` tinyint(1) DEFAULT '0',
  `price` int(11) DEFAULT '99999',
  `soldout` tinyint(1) NOT NULL DEFAULT '0' COMMENT '完売等につき販売停止中',
  `retrycnt` int(1) NOT NULL DEFAULT '0' COMMENT 'リトライ数',
  PRIMARY KEY (`vendoraddress_CD`),
  KEY `Index_2` (`daihyo_syohin_code`,`sire_code`,`sire_adress`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_vendormasteraddress`
--

DROP TABLE IF EXISTS `tb_vendormasteraddress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_vendormasteraddress` (
  `vendormasteraddress_cd` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aderssmemo` varchar(255) DEFAULT NULL,
  `vendormasteraddress` varchar(255) DEFAULT NULL,
  `sire_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`vendormasteraddress_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_vendormasterdata`
--

DROP TABLE IF EXISTS `tb_vendormasterdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_vendormasterdata` (
  `sire_code` varchar(10) NOT NULL,
  `sire_name` varchar(255) NOT NULL,
  `sire_kana` varchar(255) DEFAULT NULL,
  `yubin_bangou` varchar(50) DEFAULT NULL,
  `jyusyo1` varchar(255) DEFAULT NULL,
  `jyusyo2` varchar(255) DEFAULT NULL,
  `denwa` varchar(100) DEFAULT NULL,
  `fax` varchar(100) DEFAULT NULL,
  `mail_adr` varchar(100) DEFAULT NULL,
  `busyo` varchar(255) DEFAULT NULL,
  `tantou_name` varchar(100) DEFAULT NULL,
  `tantou_kana` varchar(100) DEFAULT NULL,
  `busyo_jyusyo1` varchar(255) DEFAULT NULL,
  `busyo_jyusyo2` varchar(255) DEFAULT NULL,
  `busyo_denwa` varchar(100) DEFAULT NULL,
  `busyo_fax` varchar(100) DEFAULT NULL,
  `busyo_mail_adr` varchar(100) DEFAULT NULL,
  `hachu_kbn` varchar(50) DEFAULT NULL,
  `su_jyoken` varchar(50) DEFAULT NULL,
  `kin_jyoken` varchar(50) DEFAULT NULL,
  `hachu_jyoken_kbn` varchar(50) DEFAULT NULL,
  `hachu_horyu_bi` varchar(50) DEFAULT NULL,
  `siharai_houhou_kbn` varchar(50) DEFAULT NULL,
  `sime_bi` varchar(50) DEFAULT NULL,
  `siharai_sight_bi` varchar(50) DEFAULT NULL,
  `hachu_mukou_flg` varchar(50) DEFAULT NULL,
  `hachu_kinsi_bi` varchar(50) DEFAULT NULL,
  `bikou` varchar(255) DEFAULT NULL,
  `メーカー次回出荷予定日` date DEFAULT NULL,
  `出荷修正日数` int(10) unsigned DEFAULT NULL,
  `取引状態` tinyint(3) DEFAULT NULL,
  `表示順` int(11) DEFAULT NULL,
  `フルオーダー完了日時` datetime DEFAULT NULL,
  `フリー在庫金額` int(10) unsigned DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `storenumber` varchar(255) DEFAULT NULL,
  `dummy` int(10) unsigned DEFAULT NULL,
  `netseaper` varchar(255) DEFAULT NULL,
  `netseaenddate` datetime DEFAULT NULL,
  `arrivalspan` int(10) unsigned DEFAULT NULL,
  `shippingschedule` date DEFAULT NULL,
  `netsea_maker_id` varchar(255) DEFAULT NULL,
  `makeshop_Registration_flug` tinyint(1) DEFAULT '0',
  `superdelivery_maker_id` varchar(255) DEFAULT NULL,
  `superdelivery_pcp` tinyint(1) DEFAULT NULL,
  `perweight_postage` int(11) NOT NULL DEFAULT '0' COMMENT '従量送料',
  `cost_rate` int(11) NOT NULL DEFAULT '0' COMMENT '原価率',
  `additional_cost_rate` float NOT NULL COMMENT '仕入先費用率(%)',
  `gross_margin` int(3) NOT NULL DEFAULT '50' COMMENT '粗利益率',
  `guerrilla_margin` int(3) NOT NULL DEFAULT '40' COMMENT 'ゲリラSALE粗利益率',
  `a_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '仕入先Aフラグ',
  `b_flg` tinyint(1) NOT NULL DEFAULT '0' COMMENT '仕入先Bフラグ',
  `available_itemcnt` int(6) NOT NULL DEFAULT '0' COMMENT '有効商品数',
  `stock_amount` int(8) NOT NULL DEFAULT '0' COMMENT '在庫金額',
  `crawl_frequency` int(2) NOT NULL DEFAULT '1' COMMENT '巡回頻度（何日に１回か？0の場合は無条件で対象、-1の場合は対象外）',
  `last_crawl_date` datetime NOT NULL COMMENT '最終巡回日時',
  `worker_payment` int(3) NOT NULL DEFAULT '0' COMMENT '内職報酬単価',
  `要ロット入荷F` tinyint(1) NOT NULL DEFAULT '0' COMMENT '仕入単位が複数個か否か',
  `max_pages` int(4) NOT NULL DEFAULT '0',
  `発注点計算期間` int(3) NOT NULL DEFAULT '1',
  `発注点倍率` double(2,1) NOT NULL DEFAULT '1.0',
  PRIMARY KEY (`sire_code`),
  KEY `Index_2` (`sire_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_vendormasterdata_order`
--

DROP TABLE IF EXISTS `tb_vendormasterdata_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_vendormasterdata_order` (
  `sire_code` varchar(10) NOT NULL DEFAULT '',
  `表示順` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sire_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_viewranking`
--

DROP TABLE IF EXISTS `tb_viewranking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_viewranking` (
  `キャリア` varchar(10) NOT NULL,
  `年月日` date NOT NULL,
  `ranking` int(7) NOT NULL DEFAULT '0',
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `ページ名` varchar(255) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `ジャンル第1階層` varchar(255) DEFAULT NULL,
  `ジャンル第2階層` varchar(255) DEFAULT NULL,
  `ジャンル第3階層` varchar(255) DEFAULT NULL,
  `ジャンル第4階層` varchar(255) DEFAULT NULL,
  `ジャンル第5階層` varchar(255) DEFAULT NULL,
  `カタログID` varchar(255) DEFAULT NULL,
  `ページ種別` varchar(255) DEFAULT NULL,
  `ページ区分` varchar(255) DEFAULT NULL,
  `アクセス数` varchar(255) DEFAULT NULL,
  `アクセス人数` varchar(255) DEFAULT NULL,
  `売上件数` varchar(255) DEFAULT NULL,
  `売上` varchar(255) DEFAULT NULL,
  `ページ転換率` varchar(255) DEFAULT NULL,
  `ページ客単価` varchar(255) DEFAULT NULL,
  `平均滞在時間_秒` varchar(255) DEFAULT NULL,
  `離脱数` varchar(255) DEFAULT NULL,
  `離脱率` varchar(255) DEFAULT NULL,
  `男` varchar(255) DEFAULT NULL,
  `女` varchar(255) DEFAULT NULL,
  `性別不明` varchar(255) DEFAULT NULL,
  `-10` varchar(255) DEFAULT NULL,
  `20` varchar(255) DEFAULT NULL,
  `30` varchar(255) DEFAULT NULL,
  `40` varchar(255) DEFAULT NULL,
  `50` varchar(255) DEFAULT NULL,
  `60+` varchar(255) DEFAULT NULL,
  `年齢不明` varchar(255) DEFAULT NULL,
  `D` varchar(255) DEFAULT NULL,
  `P` varchar(255) DEFAULT NULL,
  `G` varchar(255) DEFAULT NULL,
  `S` varchar(255) DEFAULT NULL,
  `R` varchar(255) DEFAULT NULL,
  `北海道` varchar(255) DEFAULT NULL,
  `東北` varchar(255) DEFAULT NULL,
  `関東` varchar(255) DEFAULT NULL,
  `北陸甲信越` varchar(255) DEFAULT NULL,
  `東海` varchar(255) DEFAULT NULL,
  `近畿` varchar(255) DEFAULT NULL,
  `中国` varchar(255) DEFAULT NULL,
  `四国` varchar(255) DEFAULT NULL,
  `九州` varchar(255) DEFAULT NULL,
  `国外` varchar(255) DEFAULT NULL,
  `地域不明` varchar(255) DEFAULT NULL,
  `レビュー数` varchar(255) DEFAULT NULL,
  `総合評価` varchar(255) DEFAULT NULL,
  `総レビュー数` varchar(255) DEFAULT NULL,
  `評価1レビュー数` varchar(255) DEFAULT NULL,
  `評価2レビュー数` varchar(255) DEFAULT NULL,
  `評価3レビュー数` varchar(255) DEFAULT NULL,
  `評価4レビュー数` varchar(255) DEFAULT NULL,
  `評価5レビュー数` varchar(255) DEFAULT NULL,
  `性別不明レビュー数` varchar(255) DEFAULT NULL,
  `男性年齢不明レビュー数` varchar(255) DEFAULT NULL,
  `男性20代未満レビュー数` varchar(255) DEFAULT NULL,
  `男性20代レビュー数` varchar(255) DEFAULT NULL,
  `男性30代レビュー数` varchar(255) DEFAULT NULL,
  `男性40代レビュー数` varchar(255) DEFAULT NULL,
  `男性50代レビュー数` varchar(255) DEFAULT NULL,
  `男性60代以上レビュー数` varchar(255) DEFAULT NULL,
  `女性年齢不明レビュー数` varchar(255) DEFAULT NULL,
  `女性20代未満レビュー数` varchar(255) DEFAULT NULL,
  `女性20代レビュー数` varchar(255) DEFAULT NULL,
  `女性30代レビュー数` varchar(255) DEFAULT NULL,
  `女性40代レビュー数` varchar(255) DEFAULT NULL,
  `女性50代レビュー数` varchar(255) DEFAULT NULL,
  `女性60代以上レビュー数` varchar(255) DEFAULT NULL,
  `前日在庫` varchar(255) DEFAULT NULL,
  `在庫` varchar(255) DEFAULT NULL,
  `変動数` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`キャリア`,`年月日`,`ranking`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_viewranking_dl`
--

DROP TABLE IF EXISTS `tb_viewranking_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_viewranking_dl` (
  `rank` int(11) NOT NULL DEFAULT '0',
  `daihyo_syohin_code` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`rank`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_viewranking_noget`
--

DROP TABLE IF EXISTS `tb_viewranking_noget`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_viewranking_noget` (
  `キャリア` varchar(10) NOT NULL DEFAULT '',
  `年月日` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`キャリア`,`年月日`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_viewranking_tmp_mp`
--

DROP TABLE IF EXISTS `tb_viewranking_tmp_mp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_viewranking_tmp_mp` (
  `ページ名` varchar(255) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `ページ種別` varchar(255) DEFAULT NULL,
  `ページ区分` varchar(255) DEFAULT NULL,
  `アクセス数` varchar(255) DEFAULT NULL,
  `アクセス人数` varchar(255) DEFAULT NULL,
  `売上件数` varchar(255) DEFAULT NULL,
  `売上` varchar(255) DEFAULT NULL,
  `ページ転換率` varchar(255) DEFAULT NULL,
  `ページ客単価` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ranking` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_viewranking_tmp_pcsp`
--

DROP TABLE IF EXISTS `tb_viewranking_tmp_pcsp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_viewranking_tmp_pcsp` (
  `ranking` varchar(255) DEFAULT NULL,
  `ページ名` varchar(255) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `ジャンル第1階層` varchar(255) DEFAULT NULL,
  `ジャンル第2階層` varchar(255) DEFAULT NULL,
  `ジャンル第3階層` varchar(255) DEFAULT NULL,
  `ジャンル第4階層` varchar(255) DEFAULT NULL,
  `ジャンル第5階層` varchar(255) DEFAULT NULL,
  `カタログID` varchar(255) DEFAULT NULL,
  `ページ種別` varchar(255) DEFAULT NULL,
  `ページ区分` varchar(255) DEFAULT NULL,
  `アクセス数` varchar(255) DEFAULT NULL,
  `アクセス人数` varchar(255) DEFAULT NULL,
  `売上件数` varchar(255) DEFAULT NULL,
  `売上` varchar(255) DEFAULT NULL,
  `ページ転換率` varchar(255) DEFAULT NULL,
  `ページ客単価` varchar(255) DEFAULT NULL,
  `平均滞在時間_秒` varchar(255) DEFAULT NULL,
  `離脱数` varchar(255) DEFAULT NULL,
  `離脱率` varchar(255) DEFAULT NULL,
  `男` varchar(255) DEFAULT NULL,
  `女` varchar(255) DEFAULT NULL,
  `性別不明` varchar(255) DEFAULT NULL,
  `-10` varchar(255) DEFAULT NULL,
  `20` varchar(255) DEFAULT NULL,
  `30` varchar(255) DEFAULT NULL,
  `40` varchar(255) DEFAULT NULL,
  `50` varchar(255) DEFAULT NULL,
  `60+` varchar(255) DEFAULT NULL,
  `年齢不明` varchar(255) DEFAULT NULL,
  `D` varchar(255) DEFAULT NULL,
  `P` varchar(255) DEFAULT NULL,
  `G` varchar(255) DEFAULT NULL,
  `S` varchar(255) DEFAULT NULL,
  `R` varchar(255) DEFAULT NULL,
  `北海道` varchar(255) DEFAULT NULL,
  `東北` varchar(255) DEFAULT NULL,
  `関東` varchar(255) DEFAULT NULL,
  `北陸甲信越` varchar(255) DEFAULT NULL,
  `東海` varchar(255) DEFAULT NULL,
  `近畿` varchar(255) DEFAULT NULL,
  `中国` varchar(255) DEFAULT NULL,
  `四国` varchar(255) DEFAULT NULL,
  `九州` varchar(255) DEFAULT NULL,
  `国外` varchar(255) DEFAULT NULL,
  `地域不明` varchar(255) DEFAULT NULL,
  `レビュー数` varchar(255) DEFAULT NULL,
  `総合評価` varchar(255) DEFAULT NULL,
  `総レビュー数` varchar(255) DEFAULT NULL,
  `評価1レビュー数` varchar(255) DEFAULT NULL,
  `評価2レビュー数` varchar(255) DEFAULT NULL,
  `評価3レビュー数` varchar(255) DEFAULT NULL,
  `評価4レビュー数` varchar(255) DEFAULT NULL,
  `評価5レビュー数` varchar(255) DEFAULT NULL,
  `性別不明レビュー数` varchar(255) DEFAULT NULL,
  `男性年齢不明レビュー数` varchar(255) DEFAULT NULL,
  `男性20代未満レビュー数` varchar(255) DEFAULT NULL,
  `男性20代レビュー数` varchar(255) DEFAULT NULL,
  `男性30代レビュー数` varchar(255) DEFAULT NULL,
  `男性40代レビュー数` varchar(255) DEFAULT NULL,
  `男性50代レビュー数` varchar(255) DEFAULT NULL,
  `男性60代以上レビュー数` varchar(255) DEFAULT NULL,
  `女性年齢不明レビュー数` varchar(255) DEFAULT NULL,
  `女性20代未満レビュー数` varchar(255) DEFAULT NULL,
  `女性20代レビュー数` varchar(255) DEFAULT NULL,
  `女性30代レビュー数` varchar(255) DEFAULT NULL,
  `女性40代レビュー数` varchar(255) DEFAULT NULL,
  `女性50代レビュー数` varchar(255) DEFAULT NULL,
  `女性60代以上レビュー数` varchar(255) DEFAULT NULL,
  `前日在庫` varchar(255) DEFAULT NULL,
  `在庫` varchar(255) DEFAULT NULL,
  `変動数` varchar(255) DEFAULT NULL,
  `項目選択肢縦` varchar(255) DEFAULT NULL,
  `項目選択肢横` varchar(255) DEFAULT NULL,
  `在庫_個` varchar(255) DEFAULT NULL,
  `売上個数_個` varchar(255) DEFAULT NULL,
  `売上高_円` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_wang_order`
--

DROP TABLE IF EXISTS `tb_wang_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_wang_order` (
  `daihyo_syohin_code` varchar(255) NOT NULL DEFAULT '',
  `sales_volume` int(11) DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_wc_setting`
--

DROP TABLE IF EXISTS `tb_wc_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_wc_setting` (
  `type` varchar(30) NOT NULL,
  `autorun_flg` tinyint(1) DEFAULT '0',
  `autorun_starttime` varchar(4) DEFAULT '0000',
  `autorun_lastdate` varchar(8) DEFAULT '00000000',
  PRIMARY KEY (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_worklog`
--

DROP TABLE IF EXISTS `tb_worklog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_worklog` (
  `worklog_CD` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `worklog_datetime` datetime DEFAULT NULL,
  `Content` varchar(255) DEFAULT NULL,
  `worker` int(10) unsigned DEFAULT NULL,
  `work_no` int(10) unsigned DEFAULT NULL,
  `changes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`worklog_CD`),
  KEY `Index_2` (`worklog_datetime`,`Content`,`worker`,`work_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_workname`
--

DROP TABLE IF EXISTS `tb_workname`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_workname` (
  `work_no` int(10) unsigned NOT NULL,
  `workname` varchar(255) NOT NULL,
  PRIMARY KEY (`work_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_data_add`
--

DROP TABLE IF EXISTS `tb_yahoo_data_add`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_data_add` (
  `path` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(30) NOT NULL,
  `sub-code` mediumtext,
  `original-price` int(255) DEFAULT '0',
  `price` int(11) DEFAULT '0',
  `price_add_10per` int(11) NOT NULL DEFAULT '0',
  `sale-price` int(255) DEFAULT '0',
  `options` mediumtext,
  `headline` varchar(255) DEFAULT NULL,
  `caption` mediumtext,
  `abstract` varchar(255) DEFAULT NULL,
  `explanation` mediumtext,
  `additional1` mediumtext,
  `additional2` mediumtext,
  `additional3` mediumtext,
  `relevant-links` varchar(255) DEFAULT NULL,
  `ship-weight` varchar(7) DEFAULT '1',
  `taxable` varchar(1) DEFAULT NULL,
  `release-date` varchar(255) DEFAULT NULL,
  `temporary-point-term` varchar(255) DEFAULT NULL,
  `point-code` varchar(255) DEFAULT NULL,
  `meta-key` varchar(255) DEFAULT NULL,
  `meta-desc` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `sale-period-start` varchar(255) DEFAULT NULL,
  `sale-period-end` varchar(255) DEFAULT NULL,
  `sale-limit` varchar(255) DEFAULT NULL,
  `sp-code` varchar(255) DEFAULT NULL,
  `brand-code` varchar(255) DEFAULT NULL,
  `person-code` varchar(255) DEFAULT NULL,
  `yahoo-product-code` varchar(255) DEFAULT NULL,
  `product-code` varchar(255) DEFAULT NULL,
  `jan` varchar(255) DEFAULT NULL,
  `isbn` varchar(255) DEFAULT NULL,
  `delivery` varchar(255) DEFAULT NULL,
  `astk-code` varchar(255) DEFAULT NULL,
  `condition` varchar(255) DEFAULT NULL,
  `taojapan` varchar(255) DEFAULT NULL,
  `product-category` varchar(10) DEFAULT NULL,
  `spec1` varchar(255) DEFAULT NULL,
  `spec2` varchar(255) DEFAULT NULL,
  `spec3` varchar(255) DEFAULT NULL,
  `spec4` varchar(255) DEFAULT NULL,
  `spec5` varchar(255) DEFAULT NULL,
  `display` varchar(1) DEFAULT NULL,
  `sort` varchar(255) DEFAULT NULL,
  `sp-additional` mediumtext,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_data_dl`
--

DROP TABLE IF EXISTS `tb_yahoo_data_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_data_dl` (
  `path` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `sub-code` mediumtext,
  `original-price` int(255) DEFAULT '0',
  `price` int(11) DEFAULT '0',
  `sale-price` int(255) DEFAULT '0',
  `options` mediumtext,
  `headline` varchar(255) DEFAULT NULL,
  `caption` mediumtext,
  `abstract` varchar(255) DEFAULT NULL,
  `explanation` mediumtext,
  `additional1` mediumtext,
  `additional2` mediumtext,
  `additional3` mediumtext,
  `relevant-links` varchar(255) DEFAULT NULL,
  `ship-weight` varchar(255) DEFAULT NULL,
  `taxable` varchar(1) DEFAULT NULL,
  `release-date` varchar(255) DEFAULT NULL,
  `temporary-point-term` varchar(255) DEFAULT NULL,
  `point-code` varchar(255) DEFAULT NULL,
  `meta-key` varchar(255) DEFAULT NULL,
  `meta-desc` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `sale-period-start` varchar(255) DEFAULT NULL,
  `sale-period-end` varchar(255) DEFAULT NULL,
  `sale-limit` varchar(255) DEFAULT NULL,
  `sp-code` varchar(255) DEFAULT NULL,
  `brand-code` varchar(255) DEFAULT NULL,
  `person-code` varchar(255) DEFAULT NULL,
  `yahoo-product-code` varchar(255) DEFAULT NULL,
  `product-code` varchar(255) DEFAULT NULL,
  `jan` varchar(255) DEFAULT NULL,
  `isbn` varchar(255) DEFAULT NULL,
  `delivery` varchar(255) DEFAULT NULL,
  `astk-code` varchar(255) DEFAULT NULL,
  `condition` varchar(255) DEFAULT NULL,
  `taojapan` varchar(255) DEFAULT NULL,
  `product-category` int(255) DEFAULT NULL,
  `spec1` varchar(255) DEFAULT NULL,
  `spec2` varchar(255) DEFAULT NULL,
  `spec3` varchar(255) DEFAULT NULL,
  `spec4` varchar(255) DEFAULT NULL,
  `spec5` varchar(255) DEFAULT NULL,
  `display` varchar(1) DEFAULT NULL,
  `sort` varchar(255) DEFAULT NULL,
  `sp-additional` mediumtext,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_information`
--

DROP TABLE IF EXISTS `tb_yahoo_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_information` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `yahoo_title` varchar(255) NOT NULL COMMENT 'Yahooタイトル',
  `registration_flg` tinyint(3) NOT NULL DEFAULT '-1' COMMENT '登録フラグ',
  `registration_flg_adult` tinyint(1) NOT NULL DEFAULT '-1',
  `original_price` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `meta-key` varchar(255) NOT NULL,
  `exist_image` tinyint(1) NOT NULL DEFAULT '0' COMMENT '画像有無',
  `explanation` mediumtext COMMENT '商品情報',
  `caption` mediumtext COMMENT '商品説明',
  `sub-code` mediumtext,
  `options` mediumtext,
  `options-upddate` datetime NOT NULL,
  `sp-additional` mediumtext COMMENT 'スマートフォン用フリースペース',
  `input_caption` mediumtext NOT NULL,
  `input_sp_additional` mediumtext NOT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_kawa_information`
--

DROP TABLE IF EXISTS `tb_yahoo_kawa_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_kawa_information` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `yahoo_title` varchar(255) NOT NULL COMMENT 'Yahooタイトル',
  `registration_flg` tinyint(3) NOT NULL DEFAULT '-1' COMMENT '登録フラグ',
  `registration_flg_adult` tinyint(1) NOT NULL DEFAULT '-1',
  `original_price` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'モール別価格非連動',
  `baika_tanka` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '売価単価',
  `meta-key` varchar(255) NOT NULL,
  `exist_image` tinyint(1) NOT NULL DEFAULT '0' COMMENT '画像有無',
  `explanation` mediumtext COMMENT '商品情報',
  `caption` mediumtext COMMENT '商品説明',
  `sub-code` mediumtext,
  `options` mediumtext,
  `options-upddate` datetime NOT NULL,
  `sp-additional` mediumtext COMMENT 'スマートフォン用フリースペース',
  `input_caption` mediumtext NOT NULL,
  `input_sp_additional` mediumtext NOT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`daihyo_syohin_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_ne_items_dl`
--

DROP TABLE IF EXISTS `tb_yahoo_ne_items_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_ne_items_dl` (
  `OrderId` varchar(255) DEFAULT NULL,
  `LineId` varchar(255) DEFAULT NULL,
  `Quantity` varchar(255) DEFAULT NULL,
  `ItemId` varchar(255) DEFAULT NULL,
  `SubCode` varchar(255) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `ItemOptionName` varchar(255) DEFAULT NULL,
  `ItemOptionValue` varchar(255) DEFAULT NULL,
  `SubCodeOption` varchar(255) DEFAULT NULL,
  `InscriptionName` varchar(255) DEFAULT NULL,
  `InscriptionValue` varchar(255) DEFAULT NULL,
  `UnitPrice` varchar(255) DEFAULT NULL,
  `UnitGetPoint` varchar(255) DEFAULT NULL,
  `LineSubTotal` varchar(255) DEFAULT NULL,
  `LineGetPoint` varchar(255) DEFAULT NULL,
  `PointFspCode` varchar(255) DEFAULT NULL,
  `Condition` varchar(255) DEFAULT NULL,
  `CouponId` varchar(255) DEFAULT NULL,
  `CouponDiscount` varchar(255) DEFAULT NULL,
  `OriginalPrice` varchar(255) DEFAULT NULL,
  `IsGetPointFix` varchar(255) DEFAULT NULL,
  `GetPointFixDate` varchar(255) DEFAULT NULL,
  `ReleaseDate` varchar(255) DEFAULT NULL,
  `GetPointType` varchar(255) DEFAULT NULL,
  `Jan` varchar(255) DEFAULT NULL,
  `ProductId` varchar(255) DEFAULT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `メール便F` tinyint(1) NOT NULL DEFAULT '0',
  `定形外郵便F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便込F` tinyint(1) NOT NULL DEFAULT '0',
  `宅配便別F` tinyint(1) NOT NULL DEFAULT '0',
  `発送方法および送料要確認F` tinyint(1) NOT NULL DEFAULT '-1',
  `メール便可能数未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `重量未設定F` tinyint(1) NOT NULL DEFAULT '0',
  `単品F` tinyint(1) NOT NULL DEFAULT '0',
  `mail_send_nums` int(2) NOT NULL DEFAULT '0',
  `mail_send_nums_rate` float NOT NULL DEFAULT '0',
  `mail_send_nums_rate_total` float NOT NULL DEFAULT '0',
  `weight` int(10) NOT NULL DEFAULT '0',
  `weight_total` int(11) NOT NULL DEFAULT '0',
  `配送方法自動設定済F` tinyint(1) NOT NULL DEFAULT '0',
  `自動設定番号` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_ne_items_temp`
--

DROP TABLE IF EXISTS `tb_yahoo_ne_items_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_ne_items_temp` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `OrderId` varchar(255) DEFAULT NULL,
  `LineId` varchar(255) DEFAULT NULL,
  `ItemOptionName` varchar(255) DEFAULT NULL,
  `ItemOptionValue` varchar(255) DEFAULT NULL,
  `SORT` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_ne_orders_dl`
--

DROP TABLE IF EXISTS `tb_yahoo_ne_orders_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_ne_orders_dl` (
  `OrderId` varchar(20) DEFAULT NULL,
  `ParentOrderId` varchar(20) DEFAULT NULL,
  `DeviceType` varchar(1) DEFAULT NULL,
  `IsAffiliate` varchar(1) DEFAULT NULL,
  `FspLicenseCode` varchar(255) DEFAULT NULL,
  `FspLicenseName` varchar(255) DEFAULT NULL,
  `OrderTime` varchar(20) DEFAULT NULL,
  `OrderTimeUnixEpoch` varchar(20) DEFAULT NULL,
  `UsePointType` varchar(255) DEFAULT NULL,
  `OrderStatus` varchar(255) DEFAULT NULL,
  `StoreStatus` varchar(255) DEFAULT NULL,
  `Referer` varchar(255) DEFAULT NULL,
  `EntryPoint` varchar(255) DEFAULT NULL,
  `Clink` varchar(255) DEFAULT NULL,
  `SuspectMessage` varchar(255) DEFAULT NULL,
  `IsItemCoupon` varchar(1) DEFAULT NULL,
  `IsShippingCoupon` varchar(1) DEFAULT NULL,
  `ShipName` varchar(255) DEFAULT NULL,
  `ShipFirstName` varchar(50) DEFAULT NULL,
  `ShipLastName` varchar(50) DEFAULT NULL,
  `ShipAddress1` varchar(255) DEFAULT NULL,
  `ShipAddress2` varchar(255) DEFAULT NULL,
  `ShipCity` varchar(50) DEFAULT NULL,
  `ShipPrefecture` varchar(50) DEFAULT NULL,
  `ShipZipCode` varchar(20) DEFAULT NULL,
  `ShipNameKana` varchar(50) DEFAULT NULL,
  `ShipFirstNameKana` varchar(50) DEFAULT NULL,
  `ShipLastNameKana` varchar(50) DEFAULT NULL,
  `ShipAddress1Kana` varchar(255) DEFAULT NULL,
  `ShipAddress2Kana` varchar(255) DEFAULT NULL,
  `ShipCityKana` varchar(50) DEFAULT NULL,
  `ShipPrefectureKana` varchar(50) DEFAULT NULL,
  `ShipSection1Field` varchar(255) DEFAULT NULL,
  `ShipSection1Value` varchar(255) DEFAULT NULL,
  `ShipSection2Field` varchar(255) DEFAULT NULL,
  `ShipSection2Value` varchar(255) DEFAULT NULL,
  `ShipPhoneNumber` varchar(20) DEFAULT NULL,
  `ShipEmgPhoneNumber` varchar(20) DEFAULT NULL,
  `ShipMethod` varchar(255) DEFAULT NULL,
  `ShipMethodName` varchar(255) DEFAULT NULL,
  `ShipRequestDate` varchar(255) DEFAULT NULL,
  `ShipRequestTime` varchar(255) DEFAULT NULL,
  `ShipNotes` varchar(255) DEFAULT NULL,
  `ArriveType` varchar(255) DEFAULT NULL,
  `ShipInvoiceNumber1` varchar(255) DEFAULT NULL,
  `ShipInvoiceNumber2` varchar(255) DEFAULT NULL,
  `ShipUrl` varchar(255) DEFAULT NULL,
  `ShipDate` varchar(255) DEFAULT NULL,
  `GiftWrapType` varchar(255) DEFAULT NULL,
  `GiftWrapPaperType` varchar(255) DEFAULT NULL,
  `GiftWrapName` varchar(255) DEFAULT NULL,
  `NeedBillSlip` varchar(255) DEFAULT NULL,
  `NeedDetailedSlip` varchar(255) DEFAULT NULL,
  `NeedReceipt` varchar(255) DEFAULT NULL,
  `Option1Field` varchar(255) DEFAULT NULL,
  `Option1Value` varchar(255) DEFAULT NULL,
  `Option2Field` varchar(255) DEFAULT NULL,
  `Option2Value` varchar(255) DEFAULT NULL,
  `GiftWrapMessage` varchar(255) DEFAULT NULL,
  `BillName` varchar(255) DEFAULT NULL,
  `BillFirstName` varchar(255) DEFAULT NULL,
  `BillLastName` varchar(255) DEFAULT NULL,
  `BillAddress1` varchar(255) DEFAULT NULL,
  `BillAddress2` varchar(255) DEFAULT NULL,
  `BillCity` varchar(255) DEFAULT NULL,
  `BillPrefecture` varchar(255) DEFAULT NULL,
  `BillZipCode` varchar(255) DEFAULT NULL,
  `BillNameKana` varchar(255) DEFAULT NULL,
  `BillFirstNameKana` varchar(255) DEFAULT NULL,
  `BillLastNameKana` varchar(255) DEFAULT NULL,
  `BillAddress1Kana` varchar(255) DEFAULT NULL,
  `BillAddress2Kana` varchar(255) DEFAULT NULL,
  `BillCityKana` varchar(255) DEFAULT NULL,
  `BillPrefectureKana` varchar(255) DEFAULT NULL,
  `BillSection1Field` varchar(255) DEFAULT NULL,
  `BillSection1Value` varchar(255) DEFAULT NULL,
  `BillSection2Field` varchar(255) DEFAULT NULL,
  `BillSection2Value` varchar(255) DEFAULT NULL,
  `BillPhoneNumber` varchar(255) DEFAULT NULL,
  `BillEmgPhoneNumber` varchar(255) DEFAULT NULL,
  `BillMailAddress` varchar(255) DEFAULT NULL,
  `PayMethod` varchar(255) DEFAULT NULL,
  `PayMethodName` varchar(255) DEFAULT NULL,
  `PayKind` varchar(255) DEFAULT NULL,
  `CardPayCount` varchar(255) DEFAULT NULL,
  `CardPayType` varchar(255) DEFAULT NULL,
  `SettleStatus` varchar(255) DEFAULT NULL,
  `SettleId` varchar(255) DEFAULT NULL,
  `PayNo` varchar(255) DEFAULT NULL,
  `PayNoIssueDate` varchar(255) DEFAULT NULL,
  `PayDate` varchar(255) DEFAULT NULL,
  `BuyerComments` varchar(255) DEFAULT NULL,
  `AgeConfirm` varchar(255) DEFAULT NULL,
  `QuantityDetail` varchar(255) DEFAULT NULL,
  `ShipCharge` varchar(11) DEFAULT NULL,
  `PayCharge` varchar(11) DEFAULT NULL,
  `GiftWrapCharge` varchar(255) DEFAULT NULL,
  `Discount` varchar(11) DEFAULT NULL,
  `UsePoint` varchar(11) DEFAULT NULL,
  `GetPoint` varchar(11) DEFAULT NULL,
  `Total` varchar(11) DEFAULT NULL,
  `TotalPrice` varchar(11) DEFAULT NULL,
  `ShippingCouponDiscount` varchar(255) DEFAULT NULL,
  `ItemCouponDiscount` varchar(255) DEFAULT NULL,
  `TotalMallCouponDiscount` varchar(255) DEFAULT NULL,
  `ID` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_quantity_add`
--

DROP TABLE IF EXISTS `tb_yahoo_quantity_add`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_quantity_add` (
  `code` varchar(30) DEFAULT NULL,
  `sub-code` varchar(255) NOT NULL DEFAULT '',
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`sub-code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_quantity_del`
--

DROP TABLE IF EXISTS `tb_yahoo_quantity_del`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_quantity_del` (
  `code` varchar(30) DEFAULT NULL,
  `sub-code` varchar(255) NOT NULL DEFAULT '',
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`sub-code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tb_yahoo_quantity_dl`
--

DROP TABLE IF EXISTS `tb_yahoo_quantity_dl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tb_yahoo_quantity_dl` (
  `code` varchar(255) DEFAULT NULL,
  `sub-code` varchar(255) NOT NULL DEFAULT '',
  `sp-code` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`sub-code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `timecards`
--

DROP TABLE IF EXISTS `timecards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timecards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `relation_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `ip_started` varchar(255) DEFAULT NULL,
  `ip_finished` varchar(255) DEFAULT NULL,
  `work_started` datetime DEFAULT NULL,
  `work_finished` datetime DEFAULT NULL,
  `work_seconds` int(11) NOT NULL DEFAULT '0',
  `work_seconds_net` int(11) NOT NULL DEFAULT '0',
  `break_started` datetime DEFAULT NULL,
  `break_finished` datetime DEFAULT NULL,
  `break_seconds` int(11) NOT NULL DEFAULT '0',
  `break_seconds_subtotal` int(11) NOT NULL DEFAULT '0',
  `job_description` varchar(255) DEFAULT NULL,
  `users_salary_classes_id` int(11) DEFAULT NULL,
  `basis_salary` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `comments` text,
  `total_s` int(11) DEFAULT NULL,
  `actually_worked_s` int(11) DEFAULT NULL,
  `calculated_salary` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `timecards_bak`
--

DROP TABLE IF EXISTS `timecards_bak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timecards_bak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `relation_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `ip_started` varchar(255) DEFAULT NULL,
  `ip_finished` varchar(255) DEFAULT NULL,
  `work_started` datetime DEFAULT NULL,
  `work_finished` datetime DEFAULT NULL,
  `work_seconds` int(11) NOT NULL DEFAULT '0',
  `work_seconds_net` int(11) NOT NULL DEFAULT '0',
  `break_started` datetime DEFAULT NULL,
  `break_finished` datetime DEFAULT NULL,
  `break_seconds` int(11) NOT NULL DEFAULT '0',
  `break_seconds_subtotal` int(11) NOT NULL DEFAULT '0',
  `job_description` varchar(255) DEFAULT NULL,
  `users_salary_classes_id` int(11) DEFAULT NULL,
  `basis_salary` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `comments` text,
  `total_s` int(11) DEFAULT NULL,
  `actually_worked_s` int(11) DEFAULT NULL,
  `calculated_salary` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `timecards_break`
--

DROP TABLE IF EXISTS `timecards_break`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timecards_break` (
  `id` int(11) NOT NULL,
  `break_seconds_subtotal` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tmp_mainproducts`
--

DROP TABLE IF EXISTS `tmp_mainproducts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tmp_mainproducts` (
  `daihyo_syohin_code` varchar(30) NOT NULL,
  `sire_code` varchar(10) DEFAULT NULL,
  `jan_code` varchar(50) DEFAULT NULL,
  `syohin_kbn` varchar(10) DEFAULT '10',
  `genka_tnk` int(10) unsigned DEFAULT NULL,
  `daihyo_syohin_name` varchar(255) DEFAULT NULL,
  `在庫変動チェックフラグ` tinyint(3) DEFAULT '0',
  `価格非連動チェック` tinyint(3) DEFAULT '0',
  `バリエーション変更チェック` tinyint(3) DEFAULT '0',
  `価格変更チェック` tinyint(3) DEFAULT '0',
  `備考` varchar(255) DEFAULT NULL,
  `楽天削除` tinyint(3) DEFAULT '0',
  `登録日時` datetime DEFAULT NULL,
  `販売開始日` date DEFAULT NULL,
  `送料設定` tinyint(3) DEFAULT '0',
  `入荷予定日` date DEFAULT NULL,
  `入荷アラート日数` int(10) unsigned DEFAULT '0',
  `優先表示修正値` int(10) DEFAULT '0',
  `優先表示順位` int(10) DEFAULT '0',
  `手動ゲリラSALE` tinyint(3) DEFAULT '0',
  `入荷遅延日数` int(10) unsigned DEFAULT '0',
  `総在庫数` int(10) unsigned DEFAULT '0',
  `総在庫金額` int(10) unsigned DEFAULT '0',
  `商品画像P1Cption` varchar(50) DEFAULT NULL,
  `商品画像P2Cption` varchar(50) DEFAULT NULL,
  `商品画像P3Cption` varchar(50) DEFAULT NULL,
  `商品画像P4Cption` varchar(50) DEFAULT NULL,
  `商品画像P5Cption` varchar(50) DEFAULT NULL,
  `商品画像P6Cption` varchar(50) DEFAULT NULL,
  `商品画像P7Cption` varchar(50) DEFAULT NULL,
  `商品画像P8Cption` varchar(50) DEFAULT NULL,
  `商品画像P9Cption` varchar(50) DEFAULT NULL,
  `商品画像P1Adress` varchar(200) DEFAULT NULL,
  `商品画像P2Adress` varchar(200) DEFAULT NULL,
  `商品画像P3Adress` varchar(200) DEFAULT NULL,
  `商品画像P4Adress` varchar(200) DEFAULT NULL,
  `商品画像P5Adress` varchar(200) DEFAULT NULL,
  `商品画像P6Adress` varchar(200) DEFAULT NULL,
  `商品画像P7Adress` varchar(200) DEFAULT NULL,
  `商品画像P8Adress` varchar(200) DEFAULT NULL,
  `商品画像P9Adress` varchar(200) DEFAULT NULL,
  `商品画像M1Caption` varchar(50) DEFAULT NULL,
  `商品画像M2Caption` varchar(50) DEFAULT NULL,
  `商品画像M3Caption` varchar(50) DEFAULT NULL,
  `商品画像M1Adress` varchar(200) DEFAULT NULL,
  `商品画像M2Adress` varchar(200) DEFAULT NULL,
  `商品画像M3Adress` varchar(200) DEFAULT NULL,
  `商品コメントPC` text,
  `一言ポイント` text,
  `補足説明PC` text,
  `必要補足説明` text,
  `B固有必要補足説明` text,
  `R固有必要補足説明` text,
  `NE更新カラム` varchar(10) DEFAULT NULL,
  `GMOタイトル` varchar(255) DEFAULT NULL,
  `サイズについて` text,
  `カラーについて` text,
  `素材について` text,
  `ブランドについて` text,
  `使用上の注意` text,
  `実勢価格` int(10) unsigned DEFAULT NULL,
  `横軸項目名` varchar(50) DEFAULT NULL,
  `縦軸項目名` varchar(50) DEFAULT NULL,
  `NEディレクトリID` varchar(50) DEFAULT NULL,
  `YAHOOディレクトリID` varchar(20) DEFAULT NULL,
  `標準出荷日数` int(10) unsigned DEFAULT '0',
  `stockreview` tinyint(3) DEFAULT '0',
  `stockinfomation` varchar(255) DEFAULT NULL,
  `stockreviewinfomation` varchar(255) DEFAULT NULL,
  `productchoiceitems_count` int(10) unsigned DEFAULT '0',
  `picnameP1` varchar(255) DEFAULT NULL,
  `picnameP2` varchar(255) DEFAULT NULL,
  `picnameP3` varchar(255) DEFAULT NULL,
  `picnameP4` varchar(255) DEFAULT NULL,
  `picnameP5` varchar(255) DEFAULT NULL,
  `picnameP6` varchar(255) DEFAULT NULL,
  `picnameP7` varchar(255) DEFAULT NULL,
  `picnameP8` varchar(255) DEFAULT NULL,
  `picnameP9` varchar(255) DEFAULT NULL,
  `picnameM1` varchar(255) DEFAULT NULL,
  `picnameM2` varchar(255) DEFAULT NULL,
  `picnameM3` varchar(255) DEFAULT NULL,
  `picfolderP1` varchar(255) DEFAULT NULL,
  `picfolderP2` varchar(255) DEFAULT NULL,
  `picfolderP3` varchar(255) DEFAULT NULL,
  `picfolderP4` varchar(255) DEFAULT NULL,
  `picfolderP5` varchar(255) DEFAULT NULL,
  `picfolderP6` varchar(255) DEFAULT NULL,
  `picfolderP7` varchar(255) DEFAULT NULL,
  `picfolderP8` varchar(255) DEFAULT NULL,
  `picfolderP9` varchar(255) DEFAULT NULL,
  `picfolderM1` varchar(255) DEFAULT NULL,
  `picfolderM2` varchar(255) DEFAULT NULL,
  `picfolderM3` varchar(255) DEFAULT NULL,
  `person` varchar(255) DEFAULT NULL,
  `check_price` int(10) unsigned DEFAULT NULL,
  `weight` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '重量(g)',
  `additional_cost` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '仕入付加費用',
  `pic_check_datetime` datetime DEFAULT NULL COMMENT '画像チェック日時',
  `pic_check_datetime_sort` datetime NOT NULL,
  `notfound_image_no_rakuten` int(2) NOT NULL DEFAULT '0',
  `notfound_image_no_dena` int(2) NOT NULL DEFAULT '0',
  `dummy` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daihyo_syohin_code`),
  KEY `Index_5` (`登録日時`) USING BTREE,
  KEY `pic_check_datetime` (`pic_check_datetime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tmp_tyumon`
--

DROP TABLE IF EXISTS `tmp_tyumon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tmp_tyumon` (
  `tyumon_no` varchar(100) DEFAULT NULL,
  `cart_no` varchar(100) DEFAULT NULL,
  `商品コード` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tmp_tyumon2`
--

DROP TABLE IF EXISTS `tmp_tyumon2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tmp_tyumon2` (
  `注文番号` varchar(255) DEFAULT NULL,
  `商品コード` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_output` tinyint(1) NOT NULL DEFAULT '-1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `username` varchar(16) NOT NULL,
  `password` varchar(50) NOT NULL,
  `group_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `prefix` varchar(255) NOT NULL,
  `default_make_group_id` int(11) NOT NULL DEFAULT '9',
  `users_status_names` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_addinfos`
--

DROP TABLE IF EXISTS `users_addinfos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_addinfos` (
  `id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `tel_terminal` varchar(255) DEFAULT NULL,
  `tel_sub` varchar(255) DEFAULT NULL,
  `tel_sub_terminal` varchar(255) DEFAULT NULL,
  `tel_emergency` varchar(255) DEFAULT NULL,
  `tel_emergency_name` varchar(255) DEFAULT NULL,
  `postcode` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_sub` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_salaries`
--

DROP TABLE IF EXISTS `users_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_salaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `salary_name` varchar(255) NOT NULL,
  `users_salary_classes_id` int(11) NOT NULL,
  `salary` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_salary_classes`
--

DROP TABLE IF EXISTS `users_salary_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_salary_classes` (
  `id` int(11) NOT NULL,
  `salary_classes_name` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `yours_item_deliveries`
--

DROP TABLE IF EXISTS `yours_item_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yours_item_deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` varchar(255) NOT NULL,
  `delivery_datetime` int(11) NOT NULL,
  `classification` varchar(255) NOT NULL,
  `yours_item_master_id` varchar(255) NOT NULL,
  `cost_extax` int(11) NOT NULL,
  `price_extax` int(11) NOT NULL,
  `tax_price` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `contact` int(11) NOT NULL,
  `note` text NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `yours_item_masters`
--

DROP TABLE IF EXISTS `yours_item_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yours_item_masters` (
  `id` varchar(255) NOT NULL,
  `yours_item_main_id` varchar(255) NOT NULL,
  `stock` int(11) NOT NULL,
  `cost` int(11) NOT NULL,
  `selling_price` int(11) NOT NULL,
  `stock_constant` int(11) NOT NULL,
  `stock_variable` int(11) NOT NULL,
  `yet_arrived` int(11) NOT NULL,
  `order_number` int(11) NOT NULL COMMENT '並び順No',
  `last_reservation` int(11) NOT NULL,
  `last_receiving` int(11) NOT NULL,
  `last_selling` int(11) NOT NULL,
  `last_shiping` int(11) NOT NULL,
  `last_updater` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'plusnao_db'
--
/*!50003 DROP FUNCTION IF EXISTS `buildAmazonKeyword1` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 FUNCTION `buildAmazonKeyword1`(str varchar(1000)) RETURNS varchar(1000) CHARSET utf8
BEGIN

DECLARE s varchar(1000);
DECLARE loc1 int;
DECLARE start int;
DECLARE cnt int;
DECLARE ss varchar(1000);

set ss='';
set s = str;
set s = Replace(Mid(s, InStr(s, '\\') + 1), '\\', ':');

set cnt = 0;
set start = 1;
set loc1 = InstrX(s,':',start);

LOOP1:WHILE (loc1 > 0) DO

	set cnt = cnt + 1;
	if (cnt>10) then
		leave LOOP1;
	end if;

	if (cnt>1) then
		set ss = concat(ss,' ');
	end if;

	set ss = concat(ss,left(s,loc1-1));

	set start = loc1+1;
	set loc1 = InstrX(s,':',start);

END WHILE LOOP1;

return ss;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `buildYahooPath` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 FUNCTION `buildYahooPath`(str varchar(1000)) RETURNS varchar(1000) CHARSET utf8
BEGIN

DECLARE s varchar(1000);
DECLARE loc1 int;
DECLARE start int;
DECLARE cnt int;
DECLARE ss varchar(1000);
DECLARE bak varchar(1000);

set ss='';
set s = str;
set s = Replace(s, '\\', ':');

set cnt = 0;
set start = 1;
set loc1 = InstrX(s,':',start);

LOOP1:WHILE (loc1 > 0) DO

	set cnt = cnt + 1;
	if (cnt>10) then
		leave LOOP1;
	end if;

	if (cnt=1) then
		set bak = left(s,loc1-1);
	elseif (cnt=2) then
		set ss = concat(ss,left(s,loc1-1),'\r\n');
		set ss = concat(ss,bak,'\r\n');
	else
		set ss = concat(ss,left(s,loc1-1),'\r\n');
	end if;

	set start = loc1+1;
	set loc1 = InstrX(s,':',start);

END WHILE LOOP1;

set ss = concat(ss,s,'\r\n');
if (cnt=1) then
	set ss = concat(ss,bak,'\r\n');
end if;

return ss;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `hkana2dkana` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 FUNCTION `hkana2dkana`(data TEXT) RETURNS text CHARSET utf8
    DETERMINISTIC
BEGIN
  DECLARE kana1_len, kana2_len INT(2);
  DECLARE kana1_h VARCHAR(61) DEFAULT 'ｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜｦﾝｯｬｭｮｧｨｩｪｫｰ｡｢｣､･';
  DECLARE kana1_z VARCHAR(61) DEFAULT 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲンッャュョァィゥェォー。「」、・';
  DECLARE kana2_h VARCHAR(52) DEFAULT 'ｶﾞｷﾞｸﾞｹﾞｺﾞｻﾞｼﾞｽﾞｾﾞｿﾞﾀﾞﾁﾞﾂﾞﾃﾞﾄﾞﾊﾞﾋﾞﾌﾞﾍﾞﾎﾞﾊﾟﾋﾟﾌﾟﾍﾟﾎﾟｳﾞ';
  DECLARE kana2_z VARCHAR(26) DEFAULT 'ガギグゲゴザジズゼゾダヂヅデドバビブベボパピプペポヴ';
  SET kana1_len = CHAR_LENGTH(kana1_z);
  SET kana2_len = CHAR_LENGTH(kana2_z);
  WHILE kana2_len > 0 DO
    SET data = REPLACE(data, SUBSTRING(kana2_h,kana2_len*2-1,2), SUBSTRING(kana2_z,kana2_len,1));
    SET kana2_len = kana2_len - 1;
  END WHILE;
  WHILE kana1_len > 0 DO
    SET data = REPLACE(data, SUBSTRING(kana1_h,kana1_len,1), SUBSTRING(kana1_z,kana1_len,1));
    SET kana1_len = kana1_len - 1;
  END WHILE;
  RETURN data;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `InstrX` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 FUNCTION `InstrX`(str varchar(1000),search varchar(1000),start int) RETURNS int(11)
BEGIN
    DECLARE s varchar(1000);
    DECLARE p int;
    set s = mid(str,start);
    set p = instr(s,search);
    if (p>0) then
    RETURN (start+p-1);
    else
    RETURN 0;
    end if;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `buildOptions` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `buildOptions`(IN lc_dcode varchar(50),IN lc_colTitle varchar(50),IN lc_rowTitle varchar(50))
BEGIN

	DECLARE myColName varchar(50);
	DECLARE myRowName varchar(50);
	DECLARE myOptions text DEFAULT '';
	DECLARE flag int DEFAULT 0; 
	DECLARE cnt int DEFAULT 0;

	DECLARE myCurCol CURSOR FOR SELECT tb_productchoiceitems.colname FROM tb_productchoiceitems WHERE tb_productchoiceitems.daihyo_syohin_code=lc_dcode GROUP BY tb_productchoiceitems.colname order by 並び順No;
	DECLARE myCurRow CURSOR FOR SELECT tb_productchoiceitems.rowname FROM tb_productchoiceitems WHERE tb_productchoiceitems.daihyo_syohin_code=lc_dcode GROUP BY tb_productchoiceitems.rowname order by 並び順No;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

	

	set lc_colTitle = ifnull(lc_colTitle,'');
	set lc_rowTitle = ifnull(lc_rowTitle,'');

	If (lc_colTitle = '') Then
		set lc_colTitle = '-';
	End If;
	If (lc_rowTitle = '') Then
		set lc_rowTitle = '-';
	End If;
    
	If (lc_colTitle = '-' And lc_rowTitle = '-') Then
		set lc_colTitle = 'サイズ';
		set lc_rowTitle = 'カラー';
	End If;
    
	If (lc_colTitle = '-') Then
		If (lc_rowTitle != 'サイズ') Then
			set lc_colTitle = 'サイズ';
		Else
			set lc_colTitle = 'カラー';
		End If;
	End If;

    If (lc_rowTitle = '-') Then
        If (lc_colTitle != 'カラー') Then
            set lc_rowTitle = 'カラー';
        Else
            set lc_rowTitle = 'サイズ';
        End If;
    End If;

	OPEN myCurRow;
	FETCH myCurRow INTO myRowName;

	IF flag=0 THEN

		set cnt=1;
		REPEAT 
			If (cnt = 1) Then
				set myOptions = concat(myOptions , lc_rowTitle);
			End If;
			set myOptions = concat(myOptions , ' ' , myRowName);
			FETCH myCurRow INTO myRowName;
			set cnt = cnt + 1;
		UNTIL flag=1
		END REPEAT;

		CLOSE myCurRow;

	END IF;


	
	set myOptions = concat(myOptions,'\r\n\r\n');

	
	set flag=0;


	OPEN myCurCol;
	FETCH myCurCol INTO myColName;

	IF flag=0 THEN

		set cnt=1;

		REPEAT 
			If (cnt = 1) Then
				set myOptions = concat(myOptions , lc_colTitle);
			End If;
			set myOptions = concat(myOptions , ' ' , myColName);
			FETCH myCurCol INTO myColName;
			set cnt = cnt + 1;
		UNTIL flag=1
		END REPEAT;

		CLOSE myCurCol;

	END IF;

	
	insert into tb_yahoo_information(daihyo_syohin_code,options) values(lc_dcode,myOptions) ON DUPLICATE KEY UPDATE options=myOptions;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `buildOptionsKawa` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `buildOptionsKawa`(IN lc_dcode varchar(50),IN lc_colTitle varchar(50),IN lc_rowTitle varchar(50))
BEGIN

	DECLARE myColName varchar(50);
	DECLARE myRowName varchar(50);
	DECLARE myOptions text DEFAULT '';
	DECLARE flag int DEFAULT 0; 
	DECLARE cnt int DEFAULT 0;

	DECLARE myCurCol CURSOR FOR SELECT tb_productchoiceitems.colname FROM tb_productchoiceitems WHERE tb_productchoiceitems.daihyo_syohin_code=lc_dcode GROUP BY tb_productchoiceitems.colname order by 並び順No;
	DECLARE myCurRow CURSOR FOR SELECT tb_productchoiceitems.rowname FROM tb_productchoiceitems WHERE tb_productchoiceitems.daihyo_syohin_code=lc_dcode GROUP BY tb_productchoiceitems.rowname order by 並び順No;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

	

	set lc_colTitle = ifnull(lc_colTitle,'');
	set lc_rowTitle = ifnull(lc_rowTitle,'');

	If (lc_colTitle = '') Then
		set lc_colTitle = '-';
	End If;
	If (lc_rowTitle = '') Then
		set lc_rowTitle = '-';
	End If;
    
	If (lc_colTitle = '-' And lc_rowTitle = '-') Then
		set lc_colTitle = 'サイズ';
		set lc_rowTitle = 'カラー';
	End If;
    
	If (lc_colTitle = '-') Then
		If (lc_rowTitle != 'サイズ') Then
			set lc_colTitle = 'サイズ';
		Else
			set lc_colTitle = 'カラー';
		End If;
	End If;

    If (lc_rowTitle = '-') Then
        If (lc_colTitle != 'カラー') Then
            set lc_rowTitle = 'カラー';
        Else
            set lc_rowTitle = 'サイズ';
        End If;
    End If;

	OPEN myCurRow;
	FETCH myCurRow INTO myRowName;

	IF flag=0 THEN

		set cnt=1;
		REPEAT 
			If (cnt = 1) Then
				set myOptions = concat(myOptions , lc_rowTitle);
			End If;
			set myOptions = concat(myOptions , ' ' , myRowName);
			FETCH myCurRow INTO myRowName;
			set cnt = cnt + 1;
		UNTIL flag=1
		END REPEAT;

		CLOSE myCurRow;

	END IF;


	
	set myOptions = concat(myOptions,'\r\n\r\n');

	
	set flag=0;


	OPEN myCurCol;
	FETCH myCurCol INTO myColName;

	IF flag=0 THEN

		set cnt=1;

		REPEAT 
			If (cnt = 1) Then
				set myOptions = concat(myOptions , lc_colTitle);
			End If;
			set myOptions = concat(myOptions , ' ' , myColName);
			FETCH myCurCol INTO myColName;
			set cnt = cnt + 1;
		UNTIL flag=1
		END REPEAT;

		CLOSE myCurCol;

	END IF;

	
	insert into tb_yahoo_kawa_information(daihyo_syohin_code,options) values(lc_dcode,myOptions) ON DUPLICATE KEY UPDATE options=myOptions;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `buildPpmTitleVariation` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `buildPpmTitleVariation`(IN lc_dcode varchar(50))
BEGIN

DECLARE myColName varchar(50);
DECLARE myRowName varchar(50);
DECLARE cnt int DEFAULT 0;
DECLARE myVariation text DEFAULT '';
DECLARE myMinNo INT DEFAULT 0; 
DECLARE flag INT DEFAULT 0; 

DECLARE myCurCol CURSOR FOR select min(並び順No) as minno,colname from tb_productchoiceitems where daihyo_syohin_code=lc_dcode group by daihyo_syohin_code,colname order by minno;
DECLARE myCurRow CURSOR FOR select min(並び順No) as minno,rowname from tb_productchoiceitems where daihyo_syohin_code=lc_dcode group by daihyo_syohin_code,rowname order by minno;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCurCol;

FETCH myCurCol INTO myMinNo,myColName;

IF flag=0 THEN

set cnt=1;

REPEAT 

	set myVariation = concat(myVariation , myColName, ' ');
    FETCH myCurCol INTO myMinNo,myColName;

    set cnt = cnt + 1;
UNTIL flag=1
END REPEAT;

CLOSE myCurCol;

END IF;

set flag = 0;
OPEN myCurRow;

FETCH myCurRow INTO myMinNo,myRowName;

IF flag=0 THEN

REPEAT 

	set myVariation = concat(myVariation , myRowName, ' ');
    FETCH myCurRow INTO myMinNo,myRowName;

    set cnt = cnt + 1;
UNTIL flag=1
END REPEAT;

CLOSE myCurRow;

END IF;


	update tb_ppm_information set variation = myVariation where daihyo_syohin_code=lc_dcode;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildPpmTitleVariationAll` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildPpmTitleVariationAll`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myCur CURSOR FOR SELECT tb_title_parts.daihyo_syohin_code FROM tb_title_parts INNER JOIN tb_title_parts_target ON tb_title_parts.daihyo_syohin_code = tb_title_parts_target.daihyo_syohin_code;

DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode;
IF flag=0 THEN

REPEAT 
	call BuildPpmTitleVariation(myDcode);
	FETCH myCur INTO myDcode;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildPpmTitleVariationAllFirst` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildPpmTitleVariationAllFirst`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myCur CURSOR FOR SELECT tp.daihyo_syohin_code
 FROM tb_title_parts as tp INNER JOIN tb_title_parts_target as tpt
 ON tp.daihyo_syohin_code = tpt.daihyo_syohin_code
 inner join tb_ppm_information as i
 on tp.daihyo_syohin_code = i.daihyo_syohin_code
 where ifnull(i.variation,'') = '';

DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode;
IF flag=0 THEN

REPEAT 
	call BuildPpmTitleVariation(myDcode);
	FETCH myCur INTO myDcode;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `buildQ10AdditionalItemImageAll` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `buildQ10AdditionalItemImageAll`()
BEGIN


update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,商品画像P1Adress) where IFNULL(商品画像P1Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像P2Adress) where IFNULL(商品画像P2Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像P3Adress) where IFNULL(商品画像P3Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像P4Adress) where IFNULL(商品画像P4Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像P5Adress) where IFNULL(商品画像P5Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像P6Adress) where IFNULL(商品画像P6Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像P7Adress) where IFNULL(商品画像P7Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像P8Adress) where IFNULL(商品画像P8Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像M1Adress) where IFNULL(商品画像M1Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像M2Adress) where IFNULL(商品画像M2Adress,'') <> '';
update tb_qten_information as i inner join tb_mainproducts as m on i.daihyo_syohin_code=m.daihyo_syohin_code set additional_item_image = concat(additional_item_image,'$$',商品画像M3Adress) where IFNULL(商品画像M3Adress,'') <> '';


END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `buildQ10InventoryInfo` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `buildQ10InventoryInfo`(IN lc_dcode varchar(50),IN lc_colTitle varchar(50),IN lc_rowTitle varchar(50))
BEGIN

DECLARE myColName varchar(50);
DECLARE myColCode varchar(50);
DECLARE myRowName varchar(50);
DECLARE myRowCode varchar(50);
DECLARE myAvailable int;
DECLARE myFreeStock int;
DECLARE myStock int;
DECLARE cnt int DEFAULT 0;
DECLARE myInventory text DEFAULT '';
DECLARE flag INT DEFAULT 0; 

DECLARE myCur CURSOR FOR SELECT `受発注可能フラグ`,colname, colcode, rowname, rowcode,`フリー在庫数` FROM tb_productchoiceitems WHERE daihyo_syohin_code=lc_dcode ORDER BY `並び順No`;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;




    If (lc_colTitle = '') Then
        set lc_colTitle = '-';
    End If;
    If (lc_rowTitle = '') Then
        set lc_rowTitle = '-';
    End If;
    
    If (lc_colTitle = '-' And lc_rowTitle = '-') Then
        set lc_colTitle = 'サイズ';
        set lc_rowTitle = 'カラー';
    End If;
    
    If (lc_colTitle = '-') Then
        If (lc_rowTitle != 'サイズ') Then
            set lc_colTitle = 'サイズ';
        Else
            set lc_colTitle = 'カラー';
        End If;
    End If;
    If (lc_rowTitle = '-') Then
        If (lc_colTitle != 'カラー') Then
            set lc_rowTitle = 'カラー';
        Else
            set lc_rowTitle = 'サイズ';
        End If;
    End If;









OPEN myCur;

FETCH myCur INTO myAvailable,myColName,myColCode,myRowName,myRowCode,myFreeStock;
IF flag=0 THEN

set cnt=1;

REPEAT 

        If (cnt > 1) Then
            set myInventory = concat(myInventory , '$$');
        End If;
        if (myAvailable=0) Then
		
		
		set myStock = myFreeStock;
	Else
		set myStock = myFreeStock;
	End if;
        set myInventory = concat(myInventory , lc_colTitle , '||*' , myColName , '||*' , lc_rowTitle , '||*' , myRowName , '||*', '0.00', '||*', myStock, '||*'  , myColCode , myRowCode);
    FETCH myCur INTO myAvailable,myColName,myColCode,myRowName,myRowCode,myFreeStock;

    set cnt = cnt + 1;
UNTIL flag=1
END REPEAT;

CLOSE myCur;

	set myInventory = ifnull(myInventory,'');
	insert into tb_qten_information(daihyo_syohin_code,`inventory_info`) values(lc_dcode,myInventory) ON DUPLICATE KEY UPDATE `inventory_info`=myInventory;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildQ10InventoryInfoAll` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildQ10InventoryInfoAll`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR SELECT m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_qten_information as i on m.daihyo_syohin_code=i.daihyo_syohin_code
 where i.registration_flg<>0;

DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildQ10InventoryInfo(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `buildRakutenTitleVariation` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `buildRakutenTitleVariation`(IN lc_dcode varchar(50))
BEGIN

DECLARE myColName varchar(50);
DECLARE myRowName varchar(50);
DECLARE cnt int DEFAULT 0;
DECLARE myVariation text DEFAULT '';
DECLARE myMinNo INT DEFAULT 0; 
DECLARE flag INT DEFAULT 0; 

DECLARE myCurCol CURSOR FOR select min(並び順No) as minno,colname from tb_productchoiceitems where daihyo_syohin_code=lc_dcode group by daihyo_syohin_code,colname order by minno;
DECLARE myCurRow CURSOR FOR select min(並び順No) as minno,rowname from tb_productchoiceitems where daihyo_syohin_code=lc_dcode group by daihyo_syohin_code,rowname order by minno;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCurCol;

FETCH myCurCol INTO myMinNo,myColName;

IF flag=0 THEN

set cnt=1;

REPEAT 

	set myVariation = concat(myVariation , myColName, ' ');
    FETCH myCurCol INTO myMinNo,myColName;

    set cnt = cnt + 1;
UNTIL flag=1
END REPEAT;

CLOSE myCurCol;

END IF;

set flag = 0;
OPEN myCurRow;

FETCH myCurRow INTO myMinNo,myRowName;

IF flag=0 THEN

REPEAT 

	set myVariation = concat(myVariation , myRowName, ' ');
    FETCH myCurRow INTO myMinNo,myRowName;

    set cnt = cnt + 1;
UNTIL flag=1
END REPEAT;

CLOSE myCurRow;

END IF;


	update tb_rakuteninformation set variation = myVariation where daihyo_syohin_code=lc_dcode;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildRakutenTitleVariationAll` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildRakutenTitleVariationAll`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myCur CURSOR FOR SELECT tb_title_parts.daihyo_syohin_code FROM tb_title_parts INNER JOIN tb_title_parts_target ON tb_title_parts.daihyo_syohin_code = tb_title_parts_target.daihyo_syohin_code;

DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode;
IF flag=0 THEN

REPEAT 
	call BuildRakutenTitleVariation(myDcode);
	FETCH myCur INTO myDcode;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `buildSubcode` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `buildSubcode`(IN lc_dcode varchar(50),IN lc_colTitle varchar(50),IN lc_rowTitle varchar(50))
BEGIN

DECLARE myColName varchar(50);
DECLARE myColCode varchar(50);
DECLARE myRowName varchar(50);
DECLARE myRowCode varchar(50);
DECLARE cnt int DEFAULT 0;
DECLARE mySubcode text DEFAULT '';
DECLARE flag INT DEFAULT 0; 

DECLARE myCur CURSOR FOR SELECT colname, colcode, rowname, rowcode FROM tb_productchoiceitems WHERE daihyo_syohin_code=lc_dcode ORDER BY `並び順No`;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;




    If (lc_colTitle = '') Then
        set lc_colTitle = '-';
    End If;
    If (lc_rowTitle = '') Then
        set lc_rowTitle = '-';
    End If;
    
    If (lc_colTitle = '-' And lc_rowTitle = '-') Then
        set lc_colTitle = 'サイズ';
        set lc_rowTitle = 'カラー';
    End If;
    
    If (lc_colTitle = '-') Then
        If (lc_rowTitle != 'サイズ') Then
            set lc_colTitle = 'サイズ';
        Else
            set lc_colTitle = 'カラー';
        End If;
    End If;
    If (lc_rowTitle = '-') Then
        If (lc_colTitle != 'カラー') Then
            set lc_rowTitle = 'カラー';
        Else
            set lc_rowTitle = 'サイズ';
        End If;
    End If;









OPEN myCur;

FETCH myCur INTO myColName,myColCode,myRowName,myRowCode;
IF flag=0 THEN

set cnt=1;

REPEAT 

        If (cnt > 1) Then
            set mySubcode = concat(mySubcode , '&');
        End If;
        set mySubcode = concat(mySubcode , lc_rowTitle , ':' , myRowName , '#' , lc_colTitle , ':' , myColName , '=' , lc_dcode , myColCode , myRowCode);
    FETCH myCur INTO myColName,myColCode,myRowName,myRowCode;

    set cnt = cnt + 1;
UNTIL flag=1
END REPEAT;

CLOSE myCur;
	insert into tb_yahoo_information(daihyo_syohin_code,`sub-code`) values(lc_dcode,mySubcode) ON DUPLICATE KEY UPDATE `sub-code`=mySubcode;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `buildSubcodeKawa` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `buildSubcodeKawa`(IN lc_dcode varchar(50),IN lc_colTitle varchar(50),IN lc_rowTitle varchar(50))
BEGIN

DECLARE myColName varchar(50);
DECLARE myColCode varchar(50);
DECLARE myRowName varchar(50);
DECLARE myRowCode varchar(50);
DECLARE cnt int DEFAULT 0;
DECLARE mySubcode text DEFAULT '';
DECLARE flag INT DEFAULT 0; 

DECLARE myCur CURSOR FOR SELECT colname, colcode, rowname, rowcode FROM tb_productchoiceitems WHERE daihyo_syohin_code=lc_dcode ORDER BY `並び順No`;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;




    If (lc_colTitle = '') Then
        set lc_colTitle = '-';
    End If;
    If (lc_rowTitle = '') Then
        set lc_rowTitle = '-';
    End If;
    
    If (lc_colTitle = '-' And lc_rowTitle = '-') Then
        set lc_colTitle = 'サイズ';
        set lc_rowTitle = 'カラー';
    End If;
    
    If (lc_colTitle = '-') Then
        If (lc_rowTitle != 'サイズ') Then
            set lc_colTitle = 'サイズ';
        Else
            set lc_colTitle = 'カラー';
        End If;
    End If;
    If (lc_rowTitle = '-') Then
        If (lc_colTitle != 'カラー') Then
            set lc_rowTitle = 'カラー';
        Else
            set lc_rowTitle = 'サイズ';
        End If;
    End If;









OPEN myCur;

FETCH myCur INTO myColName,myColCode,myRowName,myRowCode;
IF flag=0 THEN

set cnt=1;

REPEAT 

        If (cnt > 1) Then
            set mySubcode = concat(mySubcode , '&');
        End If;
        set mySubcode = concat(mySubcode , lc_rowTitle , ':' , myRowName , '#' , lc_colTitle , ':' , myColName , '=' , lc_dcode , myColCode , myRowCode);
    FETCH myCur INTO myColName,myColCode,myRowName,myRowCode;

    set cnt = cnt + 1;
UNTIL flag=1
END REPEAT;

CLOSE myCur;
	insert into tb_yahoo_kawa_information(daihyo_syohin_code,`sub-code`) values(lc_dcode,mySubcode) ON DUPLICATE KEY UPDATE `sub-code`=mySubcode;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsAll` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsAll`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR SELECT m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_yahoo_information as i on m.daihyo_syohin_code=i.daihyo_syohin_code;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcode(myDcode,myColTitle,myRowTitle);
	call buildOptions(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsAvailable` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsAvailable`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR select m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code=cal.daihyo_syohin_code where endofavailability is null;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcode(myDcode,myColTitle,myRowTitle);
	call buildOptions(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsDataAdd` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsDataAdd`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR select m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_yahoo_data_add as a on m.daihyo_syohin_code=a.code;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcode(myDcode,myColTitle,myRowTitle);
	call buildOptions(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsDIFF` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsDIFF`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR SELECT m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_diff as d on m.daihyo_syohin_code=d.diff_key;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcode(myDcode,myColTitle,myRowTitle);
	call buildOptions(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsIsNull` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsIsNull`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR SELECT m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_yahoo_information as i on m.daihyo_syohin_code=i.daihyo_syohin_code where ifnull(i.options,'')='';
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcode(myDcode,myColTitle,myRowTitle);
	call buildOptions(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsKawaAll` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsKawaAll`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR SELECT m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_yahoo_information as i on m.daihyo_syohin_code=i.daihyo_syohin_code;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcodeKawa(myDcode,myColTitle,myRowTitle);
	call buildOptionsKawa(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsKawaAvailable` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsKawaAvailable`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR select m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code=cal.daihyo_syohin_code where endofavailability is null;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcodeKawa(myDcode,myColTitle,myRowTitle);
	call buildOptionsKawa(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsKawaDataAdd` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsKawaDataAdd`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR select m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_yahoo_data_add as a on m.daihyo_syohin_code=a.code;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcodeKawa(myDcode,myColTitle,myRowTitle);
	call buildOptionsKawa(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsKawaDIFF` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsKawaDIFF`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR SELECT m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_diff as d on m.daihyo_syohin_code=d.diff_key;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcodeKawa(myDcode,myColTitle,myRowTitle);
	call buildOptionsKawa(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsKawaIsNull` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsKawaIsNull`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR SELECT m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_yahoo_information as i on m.daihyo_syohin_code=i.daihyo_syohin_code where ifnull(i.options,'')='';
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcodeKawa(myDcode,myColTitle,myRowTitle);
	call buildOptionsKawa(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsKawaNUV` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsKawaNUV`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR SELECT m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_yahoo_information as i on m.daihyo_syohin_code=i.daihyo_syohin_code where i.registration_flg<>0 and m.`NE更新カラム` in ('N','U','V');
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcodeKawa(myDcode,myColTitle,myRowTitle);
	call buildOptionsKawa(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `BuildSubcodeOptionsNUV` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `BuildSubcodeOptionsNUV`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDcode varchar(50);
DECLARE myColTitle varchar(50);
DECLARE myRowTitle varchar(50);


DECLARE myCur CURSOR FOR SELECT m.daihyo_syohin_code,m.`横軸項目名`, m.`縦軸項目名` FROM tb_mainproducts as m inner join tb_yahoo_information as i on m.daihyo_syohin_code=i.daihyo_syohin_code where i.registration_flg<>0 and m.`NE更新カラム` in ('N','U','V');
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDcode,myColTitle,myRowTitle;
IF flag=0 THEN

REPEAT 
	call buildSubcode(myDcode,myColTitle,myRowTitle);
	call buildOptions(myDcode,myColTitle,myRowTitle);
	FETCH myCur INTO myDcode,myColTitle,myRowTitle;
UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `InsOrderDetailBuycount` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `InsOrderDetailBuycount`()
BEGIN

DECLARE flag INT DEFAULT 0; 
DECLARE myDenpyoCode int;
DECLARE myCustemerTel varchar(30);


DECLARE myCur CURSOR FOR SELECT a.伝票番号,a.購入者電話番号 FROM tb_sales_detail_voucher as a left join tb_sales_detail_buycount b on a.伝票番号=b.伝票番号 where a.購入者電話番号<>'' and b.伝票番号 is null;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET flag = 1;

OPEN myCur;

FETCH myCur INTO myDenpyoCode,myCustemerTel;
IF flag=0 THEN

REPEAT 
	FETCH myCur INTO myDenpyoCode,myCustemerTel;

	insert into tb_sales_detail_buycount(伝票番号,購入回数)
	 select 伝票番号 ,(select count(*) as 購入回数 from tb_sales_detail_voucher as sub where 伝票番号<=myDenpyoCode and 購入者電話番号=myCustemerTel and 購入者電話番号<>'') as 購入回数 from tb_sales_detail_voucher as main where 伝票番号=myDenpyoCode;

UNTIL flag=1
END REPEAT;

CLOSE myCur;


END IF;



END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_amazon_items_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_amazon_items_dl`()
BEGIN

update tb_orderr_amazon_items_dl set `product-name`=replace(`product-name`,'?','～') where instr(`product-name`,'?')>0;
update tb_orderr_amazon_items_dl set `ship-address-1`=replace(`ship-address-1`,'?','～') where instr(`ship-address-1`,'?')>0;
update tb_orderr_amazon_items_dl set `ship-address-2`=replace(`ship-address-2`,'?','～') where instr(`ship-address-2`,'?')>0;
update tb_orderr_amazon_items_dl set `ship-address-3`=replace(`ship-address-3`,'?','～') where instr(`ship-address-3`,'?')>0;

update tb_orderr_amazon_items_dl set `product-name`=replace(`product-name`,'?','－') where instr(`product-name`,'?')>0;
update tb_orderr_amazon_items_dl set `ship-address-1`=replace(`ship-address-1`,'?','－') where instr(`ship-address-1`,'?')>0;
update tb_orderr_amazon_items_dl set `ship-address-2`=replace(`ship-address-2`,'?','－') where instr(`ship-address-2`,'?')>0;
update tb_orderr_amazon_items_dl set `ship-address-3`=replace(`ship-address-3`,'?','－') where instr(`ship-address-3`,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_bidders_folog` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_bidders_folog`()
BEGIN

update tb_bidders_folog set Title=replace(Title,'?','～') where instr(Title,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_dena_log_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_dena_log_dl`()
BEGIN

UPDATE tb_orderr_dena_log_dl as dl set dl.`タイトル`=replace(dl.`タイトル`,'?','～') where instr(dl.`タイトル`,'?')>0;
UPDATE tb_orderr_dena_log_dl as dl SET dl.`【取引ナビ】住所` = Replace(dl.`【取引ナビ】住所`,'?','～') WHERE instr(dl.`【取引ナビ】住所`,'?')>0;
UPDATE tb_orderr_dena_log_dl as dl SET dl.`【取引ナビ】送付先住所` = Replace(dl.`【取引ナビ】送付先住所`,'?','～') WHERE instr(dl.`【取引ナビ】送付先住所`,'?')>0;

UPDATE tb_orderr_dena_log_dl as dl set dl.`タイトル`=replace(dl.`タイトル`,'?','－') where instr(dl.`タイトル`,'?')>0;
UPDATE tb_orderr_dena_log_dl as dl SET dl.`【取引ナビ】住所` = Replace(dl.`【取引ナビ】住所`,"?","－") WHERE instr(dl.`【取引ナビ】住所`,'?')>0;
UPDATE tb_orderr_dena_log_dl as dl SET dl.`【取引ナビ】送付先住所` = Replace(dl.`【取引ナビ】送付先住所`,"?","－") WHERE instr(dl.`【取引ナビ】送付先住所`,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_gmo_category` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_gmo_category`()
BEGIN

update tb_gmo_category set カテゴリ名=replace(カテゴリ名,'?','～') where instr(カテゴリ名,'?')>0;
update tb_gmo_category set サブカテゴリ名=replace(サブカテゴリ名,'?','～') where instr(サブカテゴリ名,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_gmo_option_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_gmo_option_dl`()
BEGIN

update tb_gmo_option_dl set カテゴリ名=replace(カテゴリ名,'?','～') where instr(カテゴリ名,'?')>0;
update tb_gmo_option_dl set サブカテゴリ名=replace(サブカテゴリ名,'?','～') where instr(サブカテゴリ名,'?')>0;
update tb_gmo_option_dl set 商品名=replace(商品名,'?','～') where instr(商品名,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_gmo_upload_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_gmo_upload_dl`()
BEGIN

update tb_gmo_upload_dl set カテゴリ名=replace(カテゴリ名,'?','～') where instr(カテゴリ名,'?')>0;
update tb_gmo_upload_dl set サブカテゴリ名=replace(サブカテゴリ名,'?','～') where instr(サブカテゴリ名,'?')>0;
update tb_gmo_upload_dl set モバイル商品説明=replace(モバイル商品説明,'?','～') where instr(モバイル商品説明,'?')>0;
update tb_gmo_upload_dl set 商品名=replace(商品名,'?','～') where instr(商品名,'?')>0;
update tb_gmo_upload_dl set 商品説明=replace(商品説明,'?','～') where instr(商品説明,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_makeshop_option_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_makeshop_option_dl`()
BEGIN

update tb_makeshop_option_dl set カテゴリ名=replace(カテゴリ名,'?','～') where instr(カテゴリ名,'?')>0;
update tb_makeshop_option_dl set サブカテゴリ名=replace(サブカテゴリ名,'?','～') where instr(サブカテゴリ名,'?')>0;
update tb_makeshop_option_dl set 商品名=replace(商品名,'?','～') where instr(商品名,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_makeshop_upload_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_makeshop_upload_dl`()
BEGIN

update tb_makeshop_upload_dl set カテゴリ名=replace(カテゴリ名,'?','～') where instr(カテゴリ名,'?')>0;
update tb_makeshop_upload_dl set サブカテゴリ名=replace(サブカテゴリ名,'?','～') where instr(サブカテゴリ名,'?')>0;
update tb_makeshop_upload_dl set モバイル商品説明=replace(モバイル商品説明,'?','～') where instr(モバイル商品説明,'?')>0;
update tb_makeshop_upload_dl set 商品名=replace(商品名,'?','～') where instr(商品名,'?')>0;
update tb_makeshop_upload_dl set 商品説明=replace(商品説明,'?','～') where instr(商品説明,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_picking_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_picking_dl`()
BEGIN

update tb_picking_dl set 商品名=replace(商品名,'?','～') where instr(商品名,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_ppmcategory_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_ppmcategory_dl`()
BEGIN

update tb_ppm_category_dl set ショップ内カテゴリ=replace(ショップ内カテゴリ,'?','～') where instr(ショップ内カテゴリ,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_ppmselect_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_ppmselect_dl`()
BEGIN

update tb_ppm_select_dl set SKU横軸項目名=replace(SKU横軸項目名,'?','～') where instr(SKU横軸項目名,'?')>0;
update tb_ppm_select_dl set SKU縦軸項目名=replace(SKU縦軸項目名,'?','～') where instr(SKU縦軸項目名,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_PPM_items_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_PPM_items_dl`()
BEGIN

update tb_orderr_ppm_items_dl set `商品名`=replace(`商品名`,'?','～') where instr(`商品名`,'?')>0;
update tb_orderr_ppm_items_dl set `コメント`=replace(`コメント`,'?','～') where instr(`コメント`,'?')>0;
update tb_orderr_ppm_items_dl set `購入オプション`=replace(`購入オプション`,'?','～') where instr(`購入オプション`,'?')>0;
update tb_orderr_ppm_items_dl set `注文者住所：市区町村以降`=replace(`注文者住所：市区町村以降`,'?','～') where instr(`注文者住所：市区町村以降`,'?')>0;
update tb_orderr_ppm_items_dl set `送付先住所：市区町村以降`=replace(`送付先住所：市区町村以降`,'?','～') where instr(`送付先住所：市区町村以降`,'?')>0;

update tb_orderr_ppm_items_dl set `商品名`=replace(`商品名`,'?','－') where instr(`商品名`,'?')>0;
update tb_orderr_ppm_items_dl set `コメント`=replace(`コメント`,'?','－') where instr(`コメント`,'?')>0;
update tb_orderr_ppm_items_dl set `購入オプション`=replace(`購入オプション`,'?','－') where instr(`購入オプション`,'?')>0;
update tb_orderr_ppm_items_dl set `注文者住所：市区町村以降`=replace(`注文者住所：市区町村以降`,'?','－') where instr(`注文者住所：市区町村以降`,'?')>0;
update tb_orderr_ppm_items_dl set `送付先住所：市区町村以降`=replace(`送付先住所：市区町村以降`,'?','－') where instr(`送付先住所：市区町村以降`,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_rakutencategory_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_rakutencategory_dl`()
BEGIN

update tb_rakutencategory_dl set 商品名=replace(商品名,'?','～') where instr(商品名,'?')>0;
update tb_rakutencategory_dl set 表示先カテゴリ=replace(表示先カテゴリ,'?','～') where instr(表示先カテゴリ,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_rakutenselect_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_rakutenselect_dl`()
BEGIN

update tb_rakutenselect_dl set `Select/Checkbox用項目名`=replace(`Select/Checkbox用項目名`,'?','～') where instr(`Select/Checkbox用項目名`,'?')>0;
update tb_rakutenselect_dl set `Select/Checkbox用選択肢`=replace(`Select/Checkbox用選択肢`,'?','～') where instr(`Select/Checkbox用選択肢`,'?')>0;
update tb_rakutenselect_dl set `項目選択肢別在庫用横軸選択肢`=replace(`項目選択肢別在庫用横軸選択肢`,'?','～') where instr(`項目選択肢別在庫用横軸選択肢`,'?')>0;
update tb_rakutenselect_dl set `項目選択肢別在庫用縦軸選択肢`=replace(`項目選択肢別在庫用縦軸選択肢`,'?','～') where instr(`項目選択肢別在庫用縦軸選択肢`,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_rakuten_items_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_rakuten_items_dl`()
BEGIN

update tb_orderr_rakuten_items_dl set `商品名`=replace(`商品名`,'?','～') where instr(`商品名`,'?')>0;
update tb_orderr_rakuten_items_dl set `注文者住所：町以降`=replace(`注文者住所：町以降`,'?','～') where instr(`注文者住所：町以降`,'?')>0;
update tb_orderr_rakuten_items_dl set `送付先住所：町以降`=replace(`送付先住所：町以降`,'?','～') where instr(`送付先住所：町以降`,'?')>0;

update tb_orderr_rakuten_items_dl set `商品名`=replace(`商品名`,'?','－') where instr(`商品名`,'?')>0;
update tb_orderr_rakuten_items_dl set `注文者住所：町以降`=replace(`注文者住所：町以降`,'?','－') where instr(`注文者住所：町以降`,'?')>0;
update tb_orderr_rakuten_items_dl set `送付先住所：町以降`=replace(`送付先住所：町以降`,'?','－') where instr(`送付先住所：町以降`,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_stockreturn_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_stockreturn_dl`()
BEGIN

update tb_stockreturn_dl set 商品名=replace(商品名,'?','～') where instr(商品名,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_totalstock_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_totalstock_dl`()
BEGIN

update tb_totalstock_dl set 商品名=replace(商品名,'?','～') where instr(商品名,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_yahoodata_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_yahoodata_dl`()
BEGIN

update tb_yahoo_data_dl set `name`=replace(`name`,'?','～') where instr(`name`,'?')>0;
update tb_yahoo_data_dl set `explanation`=replace(`explanation`,'?','～') where instr(`explanation`,'?')>0;
update tb_yahoo_data_dl set `additional1`=replace(`additional1`,'?','～') where instr(`additional1`,'?')>0;
update tb_yahoo_data_dl set `additional2`=replace(`additional2`,'?','～') where instr(`additional2`,'?')>0;
update tb_yahoo_data_dl set `additional3`=replace(`additional3`,'?','～') where instr(`additional3`,'?')>0;
update tb_yahoo_data_dl set `sp-additional`=replace(`sp-additional`,'?','～') where instr(`sp-additional`,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_yahoo_ne_items_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_yahoo_ne_items_dl`()
BEGIN

update tb_orderr_yahoo_ne_items_dl set `title`=replace(`title`,'?','～') where instr(`title`,'?')>0;
update tb_orderr_yahoo_ne_items_dl set `ItemOptionName`=replace(`ItemOptionName`,'?','～') where instr(`ItemOptionName`,'?')>0;
update tb_orderr_yahoo_ne_items_dl set `ItemOptionValue`=replace(`ItemOptionValue`,'?','～') where instr(`ItemOptionValue`,'?')>0;

update tb_orderr_yahoo_ne_items_dl set `title`=replace(`title`,'?','－') where instr(`title`,'?')>0;
update tb_orderr_yahoo_ne_items_dl set `ItemOptionName`=replace(`ItemOptionName`,'?','－') where instr(`ItemOptionName`,'?')>0;
update tb_orderr_yahoo_ne_items_dl set `ItemOptionValue`=replace(`ItemOptionValue`,'?','－') where instr(`ItemOptionValue`,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_FIX_WAVEDASH_yahoo_ne_orders_dl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_FIX_WAVEDASH_yahoo_ne_orders_dl`()
BEGIN

update tb_orderr_yahoo_ne_orders_dl set `ShipAddress1`=replace(`ShipAddress1`,'?','～') where instr(`ShipAddress1`,'?')>0;
update tb_orderr_yahoo_ne_orders_dl set `ShipAddress2`=replace(`ShipAddress2`,'?','～') where instr(`ShipAddress2`,'?')>0;
update tb_orderr_yahoo_ne_orders_dl set `BillAddress1`=replace(`BillAddress1`,'?','～') where instr(`BillAddress1`,'?')>0;
update tb_orderr_yahoo_ne_orders_dl set `BillAddress2`=replace(`BillAddress2`,'?','～') where instr(`BillAddress2`,'?')>0;

update tb_orderr_yahoo_ne_orders_dl set `ShipAddress1`=replace(`ShipAddress1`,'?','－') where instr(`ShipAddress1`,'?')>0;
update tb_orderr_yahoo_ne_orders_dl set `ShipAddress2`=replace(`ShipAddress2`,'?','－') where instr(`ShipAddress2`,'?')>0;
update tb_orderr_yahoo_ne_orders_dl set `BillAddress1`=replace(`BillAddress1`,'?','－') where instr(`BillAddress1`,'?')>0;
update tb_orderr_yahoo_ne_orders_dl set `BillAddress2`=replace(`BillAddress2`,'?','－') where instr(`BillAddress2`,'?')>0;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_SET_AVAILABLE_ITEMCNT_vendormasterdata` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_SET_AVAILABLE_ITEMCNT_vendormasterdata`()
BEGIN

update tb_vendormasterdata as v set v.available_itemcnt=0;
update tb_vendormasterdata as v inner join (SELECT m.sire_code,count(m.daihyo_syohin_code) as CNT FROM `tb_mainproducts` as m inner join `tb_mainproducts_cal` as cal on m.daihyo_syohin_code=cal.daihyo_syohin_code WHERE ifnull(cal.endofavailability,'')=''group by m.sire_code) TBL1 on v.sire_code=TBL1.sire_code
set available_itemcnt = TBL1.cnt;

update tb_vendormasterdata as v set v.stock_amount=0;
update tb_vendormasterdata as v inner join (select m.sire_code,sum((cal.`genka_tnk_ave`+m.`additional_cost`)*choice.`フリー在庫数`) as amount from tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code=cal.daihyo_syohin_code
inner join tb_productchoiceitems as choice on m.daihyo_syohin_code=choice.daihyo_syohin_code group by m.sire_code
) TBL1 on v.sire_code=TBL1.sire_code
set v.stock_amount = TBL1.amount;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_SET_ITEMCNT_mainproducts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_SET_ITEMCNT_mainproducts`()
BEGIN

UPDATE tb_mainproducts SET tb_mainproducts.productchoiceitems_count = 0;

UPDATE tb_mainproducts INNER JOIN 
(SELECT tb_productchoiceitems.daihyo_syohin_code AS CODE, Count(tb_productchoiceitems.ne_syohin_syohin_code) AS CNT
 FROM tb_mainproducts INNER JOIN tb_productchoiceitems
 ON tb_mainproducts.daihyo_syohin_code = tb_productchoiceitems.daihyo_syohin_code
 GROUP BY tb_mainproducts.daihyo_syohin_code) AS SUB
 ON tb_mainproducts.daihyo_syohin_code = SUB.CODE SET
 tb_mainproducts.productchoiceitems_count = SUB.CNT;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_SET_ITEMCNT_plusnaoproductdirectory` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_SET_ITEMCNT_plusnaoproductdirectory`()
BEGIN

UPDATE tb_plusnaoproductdirectory SET
  tb_plusnaoproductdirectory.NEディレクトリID = IFNULL(NEディレクトリID,'')
, tb_plusnaoproductdirectory.フィールド1 = IFNULL(フィールド1,'')
, tb_plusnaoproductdirectory.フィールド2 = IFNULL(フィールド2,'')
, tb_plusnaoproductdirectory.フィールド3 = IFNULL(フィールド3,'')
, tb_plusnaoproductdirectory.フィールド4 = IFNULL(フィールド4,'')
, tb_plusnaoproductdirectory.フィールド5 = IFNULL(フィールド5,'')
, tb_plusnaoproductdirectory.フィールド6 = IFNULL(フィールド6,'')
, tb_plusnaoproductdirectory.楽天ディレクトリID = IFNULL(楽天ディレクトリID,'')
, tb_plusnaoproductdirectory.BIDDDERSディレクトリID = IFNULL(BIDDDERSディレクトリID,'')
, tb_plusnaoproductdirectory.rakutencategories_1 = IFNULL(rakutencategories_1,'')
, tb_plusnaoproductdirectory.rakutencategories_2 = IFNULL(rakutencategories_2,'')
, tb_plusnaoproductdirectory.gmo_category_main = IFNULL(gmo_category_main,'')
, tb_plusnaoproductdirectory.gmo_category_sub = IFNULL(gmo_category_sub,'')
, tb_plusnaoproductdirectory.makeshop_cat1 = IFNULL(makeshop_cat1,'')
, tb_plusnaoproductdirectory.makeshop_cat2 = IFNULL(makeshop_cat2,'')
, tb_plusnaoproductdirectory.bidders_bunrui = IFNULL(bidders_bunrui,'');

UPDATE tb_plusnaoproductdirectory SET
 tb_plusnaoproductdirectory.item_count = 0
 WHERE tb_plusnaoproductdirectory.item_count <> 0;

UPDATE tb_plusnaoproductdirectory INNER JOIN 
(SELECT m.NEディレクトリID, COUNT(m.NEディレクトリID) AS CNT FROM tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code=cal.daihyo_syohin_code where ifnull(cal.endofavailability,'')='' GROUP BY m.NEディレクトリID ) AS SUB
 ON tb_plusnaoproductdirectory.NEディレクトリID = SUB.NEディレクトリID
 SET tb_plusnaoproductdirectory.item_count = SUB.CNT;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_SET_PIC_DIR_NAME_mainproducts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_SET_PIC_DIR_NAME_mainproducts`()
BEGIN

UPDATE tb_mainproducts set picfolderP1=REPLACE(SUBSTRING_INDEX(`商品画像P1Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P1Adress`,'/',5),'/'),'') where `商品画像P1Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameP1=REPLACE(SUBSTRING_INDEX(`商品画像P1Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P1Adress`,'/',6),'/'),'') where `商品画像P1Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderP2=REPLACE(SUBSTRING_INDEX(`商品画像P2Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P2Adress`,'/',5),'/'),'') where `商品画像P2Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameP2=REPLACE(SUBSTRING_INDEX(`商品画像P2Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P2Adress`,'/',6),'/'),'') where `商品画像P2Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderP3=REPLACE(SUBSTRING_INDEX(`商品画像P3Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P3Adress`,'/',5),'/'),'') where `商品画像P3Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameP3=REPLACE(SUBSTRING_INDEX(`商品画像P3Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P3Adress`,'/',6),'/'),'') where `商品画像P3Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderP4=REPLACE(SUBSTRING_INDEX(`商品画像P4Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P4Adress`,'/',5),'/'),'') where `商品画像P4Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameP4=REPLACE(SUBSTRING_INDEX(`商品画像P4Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P4Adress`,'/',6),'/'),'') where `商品画像P4Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderP5=REPLACE(SUBSTRING_INDEX(`商品画像P5Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P5Adress`,'/',5),'/'),'') where `商品画像P5Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameP5=REPLACE(SUBSTRING_INDEX(`商品画像P5Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P5Adress`,'/',6),'/'),'') where `商品画像P5Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderP6=REPLACE(SUBSTRING_INDEX(`商品画像P6Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P6Adress`,'/',5),'/'),'') where `商品画像P6Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameP6=REPLACE(SUBSTRING_INDEX(`商品画像P6Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P6Adress`,'/',6),'/'),'') where `商品画像P6Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderP7=REPLACE(SUBSTRING_INDEX(`商品画像P7Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P7Adress`,'/',5),'/'),'') where `商品画像P7Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameP7=REPLACE(SUBSTRING_INDEX(`商品画像P7Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P7Adress`,'/',6),'/'),'') where `商品画像P7Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderP8=REPLACE(SUBSTRING_INDEX(`商品画像P8Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P8Adress`,'/',5),'/'),'') where `商品画像P8Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameP8=REPLACE(SUBSTRING_INDEX(`商品画像P8Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P8Adress`,'/',6),'/'),'') where `商品画像P8Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderP9=REPLACE(SUBSTRING_INDEX(`商品画像P9Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P9Adress`,'/',5),'/'),'') where `商品画像P9Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameP9=REPLACE(SUBSTRING_INDEX(`商品画像P9Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P9Adress`,'/',6),'/'),'') where `商品画像P9Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderM1=REPLACE(SUBSTRING_INDEX(`商品画像M1Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像M1Adress`,'/',5),'/'),'') where `商品画像M1Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameM1=REPLACE(SUBSTRING_INDEX(`商品画像M1Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像M1Adress`,'/',6),'/'),'') where `商品画像M1Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderM2=REPLACE(SUBSTRING_INDEX(`商品画像M2Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像M2Adress`,'/',5),'/'),'') where `商品画像M2Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameM2=REPLACE(SUBSTRING_INDEX(`商品画像M2Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像M2Adress`,'/',6),'/'),'') where `商品画像M2Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderM3=REPLACE(SUBSTRING_INDEX(`商品画像M3Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像M3Adress`,'/',5),'/'),'') where `商品画像M3Adress` IS NOT NULL;
UPDATE tb_mainproducts set picnameM3=REPLACE(SUBSTRING_INDEX(`商品画像M3Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像M3Adress`,'/',6),'/'),'') where `商品画像M3Adress` IS NOT NULL;

UPDATE tb_mainproducts set picfolderP1 = Null, picnameP1 = Null where (`商品画像P1Adress` IS NULL OR `商品画像P1Adress`='');
UPDATE tb_mainproducts set picfolderP2 = Null, picnameP2 = Null where (`商品画像P2Adress` IS NULL OR `商品画像P2Adress`='');
UPDATE tb_mainproducts set picfolderP3 = Null, picnameP3 = Null where (`商品画像P3Adress` IS NULL OR `商品画像P3Adress`='');
UPDATE tb_mainproducts set picfolderP4 = Null, picnameP4 = Null where (`商品画像P4Adress` IS NULL OR `商品画像P4Adress`='');
UPDATE tb_mainproducts set picfolderP5 = Null, picnameP5 = Null where (`商品画像P5Adress` IS NULL OR `商品画像P5Adress`='');
UPDATE tb_mainproducts set picfolderP6 = Null, picnameP6 = Null where (`商品画像P6Adress` IS NULL OR `商品画像P6Adress`='');
UPDATE tb_mainproducts set picfolderP7 = Null, picnameP7 = Null where (`商品画像P7Adress` IS NULL OR `商品画像P7Adress`='');
UPDATE tb_mainproducts set picfolderP8 = Null, picnameP8 = Null where (`商品画像P8Adress` IS NULL OR `商品画像P8Adress`='');
UPDATE tb_mainproducts set picfolderP9 = Null, picnameP9 = Null where (`商品画像P9Adress` IS NULL OR `商品画像P9Adress`='');
UPDATE tb_mainproducts set picfolderM1 = Null, picnameM1 = Null where (`商品画像M1Adress` IS NULL OR `商品画像M1Adress`='');
UPDATE tb_mainproducts set picfolderM2 = Null, picnameM2 = Null where (`商品画像M2Adress` IS NULL OR `商品画像M2Adress`='');
UPDATE tb_mainproducts set picfolderM3 = Null, picnameM3 = Null where (`商品画像M3Adress` IS NULL OR `商品画像M3Adress`='');

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `PROC_SET_PIC_DIR_NAME_mainproducts_StartUp` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 PROCEDURE `PROC_SET_PIC_DIR_NAME_mainproducts_StartUp`()
BEGIN

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP1=REPLACE(SUBSTRING_INDEX(`商品画像P1Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P1Adress`,'/',5),'/'),'') where `商品画像P1Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameP1=REPLACE(SUBSTRING_INDEX(`商品画像P1Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P1Adress`,'/',6),'/'),'') where `商品画像P1Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP2=REPLACE(SUBSTRING_INDEX(`商品画像P2Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P2Adress`,'/',5),'/'),'') where `商品画像P2Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameP2=REPLACE(SUBSTRING_INDEX(`商品画像P2Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P2Adress`,'/',6),'/'),'') where `商品画像P2Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP3=REPLACE(SUBSTRING_INDEX(`商品画像P3Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P3Adress`,'/',5),'/'),'') where `商品画像P3Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameP3=REPLACE(SUBSTRING_INDEX(`商品画像P3Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P3Adress`,'/',6),'/'),'') where `商品画像P3Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP4=REPLACE(SUBSTRING_INDEX(`商品画像P4Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P4Adress`,'/',5),'/'),'') where `商品画像P4Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameP4=REPLACE(SUBSTRING_INDEX(`商品画像P4Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P4Adress`,'/',6),'/'),'') where `商品画像P4Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP5=REPLACE(SUBSTRING_INDEX(`商品画像P5Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P5Adress`,'/',5),'/'),'') where `商品画像P5Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameP5=REPLACE(SUBSTRING_INDEX(`商品画像P5Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P5Adress`,'/',6),'/'),'') where `商品画像P5Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP6=REPLACE(SUBSTRING_INDEX(`商品画像P6Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P6Adress`,'/',5),'/'),'') where `商品画像P6Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameP6=REPLACE(SUBSTRING_INDEX(`商品画像P6Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P6Adress`,'/',6),'/'),'') where `商品画像P6Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP7=REPLACE(SUBSTRING_INDEX(`商品画像P7Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P7Adress`,'/',5),'/'),'') where `商品画像P7Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameP7=REPLACE(SUBSTRING_INDEX(`商品画像P7Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P7Adress`,'/',6),'/'),'') where `商品画像P7Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP8=REPLACE(SUBSTRING_INDEX(`商品画像P8Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P8Adress`,'/',5),'/'),'') where `商品画像P8Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameP8=REPLACE(SUBSTRING_INDEX(`商品画像P8Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P8Adress`,'/',6),'/'),'') where `商品画像P8Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP9=REPLACE(SUBSTRING_INDEX(`商品画像P9Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像P9Adress`,'/',5),'/'),'') where `商品画像P9Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameP9=REPLACE(SUBSTRING_INDEX(`商品画像P9Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像P9Adress`,'/',6),'/'),'') where `商品画像P9Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderM1=REPLACE(SUBSTRING_INDEX(`商品画像M1Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像M1Adress`,'/',5),'/'),'') where `商品画像M1Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameM1=REPLACE(SUBSTRING_INDEX(`商品画像M1Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像M1Adress`,'/',6),'/'),'') where `商品画像M1Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderM2=REPLACE(SUBSTRING_INDEX(`商品画像M2Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像M2Adress`,'/',5),'/'),'') where `商品画像M2Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameM2=REPLACE(SUBSTRING_INDEX(`商品画像M2Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像M2Adress`,'/',6),'/'),'') where `商品画像M2Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderM3=REPLACE(SUBSTRING_INDEX(`商品画像M3Adress`,'/',6),CONCAT(SUBSTRING_INDEX(`商品画像M3Adress`,'/',5),'/'),'') where `商品画像M3Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picnameM3=REPLACE(SUBSTRING_INDEX(`商品画像M3Adress`,'/',7),CONCAT(SUBSTRING_INDEX(`商品画像M3Adress`,'/',6),'/'),'') where `商品画像M3Adress` IS NOT NULL and (cal.deliverycode<>4 and cal.startup_flg<>0);

UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP1 = Null, picnameP1 = Null where (`商品画像P1Adress` IS NULL OR `商品画像P1Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP2 = Null, picnameP2 = Null where (`商品画像P2Adress` IS NULL OR `商品画像P2Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP3 = Null, picnameP3 = Null where (`商品画像P3Adress` IS NULL OR `商品画像P3Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP4 = Null, picnameP4 = Null where (`商品画像P4Adress` IS NULL OR `商品画像P4Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP5 = Null, picnameP5 = Null where (`商品画像P5Adress` IS NULL OR `商品画像P5Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP6 = Null, picnameP6 = Null where (`商品画像P6Adress` IS NULL OR `商品画像P6Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP7 = Null, picnameP7 = Null where (`商品画像P7Adress` IS NULL OR `商品画像P7Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP8 = Null, picnameP8 = Null where (`商品画像P8Adress` IS NULL OR `商品画像P8Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderP9 = Null, picnameP9 = Null where (`商品画像P9Adress` IS NULL OR `商品画像P9Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderM1 = Null, picnameM1 = Null where (`商品画像M1Adress` IS NULL OR `商品画像M1Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderM2 = Null, picnameM2 = Null where (`商品画像M2Adress` IS NULL OR `商品画像M2Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);
UPDATE tb_mainproducts as m inner join tb_mainproducts_cal as cal on m.daihyo_syohin_code = cal.daihyo_syohin_code set picfolderM3 = Null, picnameM3 = Null where (`商品画像M3Adress` IS NULL OR `商品画像M3Adress`='') and (cal.deliverycode<>4 and cal.startup_flg<>0);

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
