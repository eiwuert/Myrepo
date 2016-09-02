-- MySQL dump 10.10
--
-- Host: localhost    Database: admin_framework
-- ------------------------------------------------------
-- Server version	4.1.13-standard-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access_group`
--

DROP TABLE IF EXISTS `access_group`;
CREATE TABLE `access_group` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `company_id` int(10) unsigned NOT NULL default '0',
  `system_id` int(10) unsigned NOT NULL default '0',
  `access_group_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`access_group_id`),
  UNIQUE KEY `idx_access_group_co_sys_name` (`company_id`,`system_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `acl`
--

DROP TABLE IF EXISTS `acl`;
CREATE TABLE `acl` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `company_id` int(10) unsigned NOT NULL default '0',
  `access_group_id` int(10) unsigned NOT NULL default '0',
  `section_id` int(10) unsigned NOT NULL default '0',
  `acl_mask` varchar(255) default NULL,
  PRIMARY KEY  (`access_group_id`,`section_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `agent`
--

DROP TABLE IF EXISTS `agent`;
CREATE TABLE `agent` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `system_id` int(10) unsigned NOT NULL default '0',
  `agent_id` int(10) unsigned NOT NULL default '0',
  `name_last` varchar(50) NOT NULL default '',
  `name_first` varchar(50) NOT NULL default '',
  `name_middle` varchar(50) default NULL,
  `email` varchar(100) default NULL,
  `phone` varchar(10) default NULL,
  `login` varchar(50) NOT NULL default '',
  `crypt_password` varchar(255) NOT NULL default '',
  `date_expire_account` date default NULL,
  `date_expire_password` date default NULL,
  PRIMARY KEY  (`agent_id`),
  UNIQUE KEY `idx_agent_login_sys` (`login`,`system_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `agent_access_group`
--

DROP TABLE IF EXISTS `agent_access_group`;
CREATE TABLE `agent_access_group` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `company_id` int(10) unsigned NOT NULL default '0',
  `agent_id` int(10) unsigned NOT NULL default '0',
  `access_group_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`agent_id`,`access_group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
CREATE TABLE `company` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `company_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(100) NOT NULL default '',
  `name_short` varchar(5) NOT NULL default '',
  `property_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`company_id`),
  UNIQUE KEY `idx_company_name_short` (`name_short`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `company_section_view`
--

DROP TABLE IF EXISTS `company_section_view`;
CREATE TABLE `company_section_view` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_modified` timestamp NOT NULL default '0000-00-00 00:00:00',
  `company_section_view_id` int(10) unsigned NOT NULL default '0',
  `company_id` int(10) unsigned NOT NULL default '0',
  `section_id` int(10) unsigned NOT NULL default '0',
  `section_view_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`company_section_view_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `module`
--

DROP TABLE IF EXISTS `module`;
CREATE TABLE `module` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `name_short` varchar(30) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  `directory` varchar(255) default NULL,
  `section_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`name_short`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `section`
--

DROP TABLE IF EXISTS `section`;
CREATE TABLE `section` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `system_id` int(10) unsigned NOT NULL default '0',
  `section_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(100) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `section_parent_id` int(10) unsigned default NULL,
  `sequence_no` smallint(5) unsigned NOT NULL default '0',
  `level` tinyint(3) unsigned NOT NULL default '0',
  `default_section_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`section_id`),
  UNIQUE KEY `idx_section_name_parent_sys` (`name`,`section_parent_id`,`system_id`),
  KEY `idx_section_sys_parent_seqno` (`system_id`,`section_parent_id`,`sequence_no`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `section_views`
--

DROP TABLE IF EXISTS `section_views`;
CREATE TABLE `section_views` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_modified` timestamp NOT NULL default '0000-00-00 00:00:00',
  `section_view_id` int(10) unsigned NOT NULL default '0',
  `section_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`section_view_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_0`
--

DROP TABLE IF EXISTS `session_0`;
CREATE TABLE `session_0` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_1`
--

DROP TABLE IF EXISTS `session_1`;
CREATE TABLE `session_1` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_2`
--

DROP TABLE IF EXISTS `session_2`;
CREATE TABLE `session_2` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_3`
--

DROP TABLE IF EXISTS `session_3`;
CREATE TABLE `session_3` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_4`
--

DROP TABLE IF EXISTS `session_4`;
CREATE TABLE `session_4` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_5`
--

DROP TABLE IF EXISTS `session_5`;
CREATE TABLE `session_5` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_6`
--

DROP TABLE IF EXISTS `session_6`;
CREATE TABLE `session_6` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_7`
--

DROP TABLE IF EXISTS `session_7`;
CREATE TABLE `session_7` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_8`
--

DROP TABLE IF EXISTS `session_8`;
CREATE TABLE `session_8` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_9`
--

DROP TABLE IF EXISTS `session_9`;
CREATE TABLE `session_9` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_a`
--

DROP TABLE IF EXISTS `session_a`;
CREATE TABLE `session_a` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_b`
--

DROP TABLE IF EXISTS `session_b`;
CREATE TABLE `session_b` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_c`
--

DROP TABLE IF EXISTS `session_c`;
CREATE TABLE `session_c` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_d`
--

DROP TABLE IF EXISTS `session_d`;
CREATE TABLE `session_d` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_e`
--

DROP TABLE IF EXISTS `session_e`;
CREATE TABLE `session_e` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `session_f`
--

DROP TABLE IF EXISTS `session_f`;
CREATE TABLE `session_f` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `system`
--

DROP TABLE IF EXISTS `system`;
CREATE TABLE `system` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `system_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(100) NOT NULL default '',
  `name_short` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`system_id`),
  UNIQUE KEY `idx_system_name_short` (`name_short`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

