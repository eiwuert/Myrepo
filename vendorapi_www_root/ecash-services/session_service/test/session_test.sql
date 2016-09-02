-- MySQL dump 10.11
--
-- Host: db101.ept.tss    Database: olp
-- ------------------------------------------------------
-- Server version	5.0.58-enterprise-gpl

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
-- Table structure for table `session_0`
--

DROP TABLE IF EXISTS `session_0`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_0` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_1`
--

DROP TABLE IF EXISTS `session_1`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_1` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_2`
--

DROP TABLE IF EXISTS `session_2`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_2` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_3`
--

DROP TABLE IF EXISTS `session_3`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_3` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_4`
--

DROP TABLE IF EXISTS `session_4`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_4` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_5`
--

DROP TABLE IF EXISTS `session_5`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_5` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_6`
--

DROP TABLE IF EXISTS `session_6`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_6` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_7`
--

DROP TABLE IF EXISTS `session_7`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_7` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_8`
--

DROP TABLE IF EXISTS `session_8`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_8` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_9`
--

DROP TABLE IF EXISTS `session_9`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_9` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_a`
--

DROP TABLE IF EXISTS `session_a`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_a` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_b`
--

DROP TABLE IF EXISTS `session_b`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_b` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_c`
--

DROP TABLE IF EXISTS `session_c`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_c` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_d`
--

DROP TABLE IF EXISTS `session_d`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_d` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_e`
--

DROP TABLE IF EXISTS `session_e`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_e` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `session_f`
--

DROP TABLE IF EXISTS `session_f`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `session_f` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;
SET character_set_client = @saved_cs_client;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2009-09-18 16:05:56
