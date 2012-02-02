# ************************************************************
# Sequel Pro SQL dump
# Version 3408
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.5.11)
# Database: treebase
# Generation Time: 2012-02-02 11:36:49 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table ncbi_names
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ncbi_names`;

CREATE TABLE `ncbi_names` (
  `tax_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name_txt` varchar(255) NOT NULL DEFAULT '',
  `unique_name` varchar(255) DEFAULT NULL,
  `name_class` varchar(32) NOT NULL DEFAULT '',
  KEY `tax_id` (`tax_id`),
  KEY `name_class` (`name_class`),
  KEY `name_txt` (`name_txt`),
  KEY `unique_name` (`unique_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table ncbi_nodes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ncbi_nodes`;

CREATE TABLE `ncbi_nodes` (
  `tax_id` int(11) unsigned NOT NULL DEFAULT '0',
  `parent_tax_id` int(11) unsigned NOT NULL DEFAULT '0',
  `rank` varchar(32) DEFAULT NULL,
  `embl_code` varchar(16) DEFAULT NULL,
  `division_id` smallint(6) NOT NULL DEFAULT '0',
  `inherited_div_flag` tinyint(4) NOT NULL DEFAULT '0',
  `genetic_code_id` smallint(6) NOT NULL DEFAULT '0',
  `inherited_GC_flag` tinyint(4) NOT NULL DEFAULT '0',
  `mitochondrial_genetic_code_id` smallint(4) NOT NULL DEFAULT '0',
  `inherited_MGC_flag` tinyint(4) NOT NULL DEFAULT '0',
  `GenBank_hidden_flag` smallint(4) NOT NULL DEFAULT '0',
  `hidden_subtree_root_flag` tinyint(4) NOT NULL DEFAULT '0',
  `comments` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`tax_id`),
  KEY `parent_tax_id` (`parent_tax_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table ncbi_tree
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ncbi_tree`;

CREATE TABLE `ncbi_tree` (
  `tax_id` int(11) NOT NULL DEFAULT '0',
  `parent_tax_id` int(11) unsigned NOT NULL,
  `post_order` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `left` double NOT NULL,
  `right` double NOT NULL,
  `bbox` geometry NOT NULL,
  `weight` int(11) DEFAULT NULL,
  PRIMARY KEY (`tax_id`),
  KEY `parent_tax_id` (`parent_tax_id`),
  SPATIAL KEY `bbox` (`bbox`),
  KEY `weight` (`weight`),
  KEY `post_order` (`post_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table treebase
# ------------------------------------------------------------

DROP TABLE IF EXISTS `treebase`;

CREATE TABLE `treebase` (
  `id` varchar(32) NOT NULL DEFAULT '',
  `publication` text,
  `label` varchar(255) DEFAULT NULL,
  `majority_taxon_tax_id` int(11) DEFAULT NULL,
  `majority_taxon_bbox` geometry NOT NULL,
  `tree` mediumtext,
  PRIMARY KEY (`id`),
  KEY `majority_taxon_tax_id` (`majority_taxon_tax_id`),
  SPATIAL KEY `majority_taxon_bbox` (`majority_taxon_bbox`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
