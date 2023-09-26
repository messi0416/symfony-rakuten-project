/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# テーブルのダンプ tb_taobao_change_logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tb_taobao_change_logs`;

CREATE TABLE `tb_taobao_change_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL DEFAULT 0,
  `variant_id` int(11) NOT NULL DEFAULT 0,
  `change_type` int(11) NOT NULL DEFAULT 0,
  `message` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# テーブルのダンプ tb_taobao_products
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tb_taobao_products`;

CREATE TABLE `tb_taobao_products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) NOT NULL DEFAULT '',
  `name` VARCHAR(500) NOT NULL DEFAULT '',
  `url` VARCHAR(500) NOT NULL DEFAULT '' ,
  `orig_url` VARCHAR(500) NOT NULL DEFAULT '' ,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# テーブルのダンプ tb_taobao_variants
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tb_taobao_variants`;

CREATE TABLE `tb_taobao_variants` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL DEFAULT 0,
  `uid` varchar(255) NOT NULL DEFAULT '',
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `in_stock` tinyint(1) NOT NULL DEFAULT 0,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `stock` VARCHAR(20) NOT NULL DEFAULT '',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
