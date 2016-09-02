/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50617
Source Host           : localhost:3306
Source Database       : condor_admin

Target Server Type    : MYSQL
Target Server Version : 50617
File Encoding         : 65001

Date: 2016-01-08 12:00:57
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `access_group`
-- ----------------------------
DROP TABLE IF EXISTS `access_group`;
CREATE TABLE `access_group` (
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `company_id` int(10) unsigned NOT NULL DEFAULT '0',
  `system_id` int(10) unsigned NOT NULL DEFAULT '0',
  `access_group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`access_group_id`),
  UNIQUE KEY `idx_access_group_co_sys_name` (`company_id`,`system_id`,`name`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of access_group
-- ----------------------------
INSERT INTO `access_group` VALUES ('2007-11-21 14:31:23', '2007-11-21 14:31:23', 'active', '16', '1', '66', 'Admin');
INSERT INTO `access_group` VALUES ('2007-12-04 14:57:34', '2007-12-04 14:57:34', 'active', '16', '1', '68', 'Ops');

-- ----------------------------
-- Table structure for `acl`
-- ----------------------------
DROP TABLE IF EXISTS `acl`;
CREATE TABLE `acl` (
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `company_id` int(10) unsigned NOT NULL DEFAULT '0',
  `access_group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `section_id` int(10) unsigned NOT NULL DEFAULT '0',
  `acl_mask` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`access_group_id`,`section_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of acl
-- ----------------------------
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '19', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '18', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '13', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '8', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '20', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '21', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '22', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '31', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '14', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '33', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '38', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '34', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '9', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '15', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '32', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '36', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '35', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '37', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '12', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '16', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '27', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '28', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '29', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '30', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '11', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '17', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '23', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '24', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '25', null);
INSERT INTO `acl` VALUES ('2007-11-21 14:32:38', '2007-11-21 14:32:38', 'active', '16', '66', '26', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '33', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '18', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '8', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '38', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '37', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '36', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '35', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '34', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '33', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '32', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '31', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '30', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '29', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '28', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '27', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '26', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '25', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '24', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '23', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '22', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '21', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '20', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '19', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '18', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '17', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '16', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '15', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '14', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '13', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '12', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '11', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '9', null);
INSERT INTO `acl` VALUES ('2012-08-25 11:53:12', '2012-08-25 11:53:12', 'active', '45', '119', '8', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '31', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '22', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '21', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '20', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '19', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '18', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '13', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '8', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '14', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '31', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '14', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '13', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '34', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '22', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '21', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '20', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '19', null);
INSERT INTO `acl` VALUES ('2015-06-23 12:44:00', '2015-06-23 12:44:00', 'active', '16', '68', '38', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '33', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '38', null);
INSERT INTO `acl` VALUES ('2015-06-23 15:30:24', '2015-06-23 15:30:24', 'active', '45', '120', '34', null);

-- ----------------------------
-- Table structure for `agent`
-- ----------------------------
DROP TABLE IF EXISTS `agent`;
CREATE TABLE `agent` (
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `system_id` int(10) unsigned NOT NULL DEFAULT '0',
  `agent_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name_last` varchar(50) NOT NULL DEFAULT '',
  `name_first` varchar(50) NOT NULL DEFAULT '',
  `name_middle` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `login` varchar(50) NOT NULL DEFAULT '',
  `crypt_password` varchar(255) NOT NULL DEFAULT '',
  `date_expire_account` date DEFAULT NULL,
  `date_expire_password` date DEFAULT NULL,
  `company_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`agent_id`),
  UNIQUE KEY `idx_agent_login_sys` (`login`,`system_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of agent
-- ----------------------------
INSERT INTO `agent` VALUES ('2007-11-21 14:31:23', '2007-11-21 14:31:23', 'active', '2', '1', '', '', '', '', '', 'someloancompany', '4lDOHHM9WXcJ+YJhLnRRGQ==', null, null, '16');
INSERT INTO `agent` VALUES ('2007-11-21 14:31:23', '2007-11-21 14:31:23', 'active', '1', '2', '', '', '', '', '', 'someloancompany', '4lDOHHM9WXcJ+YJhLnRRGQ==', null, null, '16');
INSERT INTO `agent` VALUES ('2007-12-04 14:54:13', '2007-12-04 14:54:13', 'active', '1', '3', 'Condor', 'Admin', null, null, null, 'admin', '4lDOHHM9WXcJ+YJhLnRRGQ==', null, null, '16');

-- ----------------------------
-- Table structure for `agent_access_group`
-- ----------------------------
DROP TABLE IF EXISTS `agent_access_group`;
CREATE TABLE `agent_access_group` (
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `company_id` int(10) unsigned NOT NULL DEFAULT '0',
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `access_group_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`agent_id`,`access_group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of agent_access_group
-- ----------------------------
INSERT INTO `agent_access_group` VALUES ('2007-11-21 14:31:23', '2007-11-21 14:31:23', 'active', '16', '2', '66');
INSERT INTO `agent_access_group` VALUES ('2007-12-04 14:54:26', '2007-12-04 14:54:26', 'active', '16', '3', '66');

-- ----------------------------
-- Table structure for `company`
-- ----------------------------
DROP TABLE IF EXISTS `company`;
CREATE TABLE `company` (
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `company_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `name_short` varchar(5) NOT NULL DEFAULT '',
  `property_id` int(10) unsigned NOT NULL DEFAULT '0',
  `api_auth` varchar(255) NOT NULL,
  `fax_server` varchar(100) NOT NULL,
  PRIMARY KEY (`company_id`),
  UNIQUE KEY `idx_company_name_short` (`name_short`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of company
-- ----------------------------
INSERT INTO `company` VALUES ('2007-11-21 14:31:23', '2007-11-21 14:31:23', 'active', '16', 'Multi Loan Source', 'slc', '0', '7xd3HBRVZQpNmfYhZyFLKquEpPM7C9ByDThN2bpdglU=', 'fax1.condor.amg');

-- ----------------------------
-- Table structure for `company_section_view`
-- ----------------------------
DROP TABLE IF EXISTS `company_section_view`;
CREATE TABLE `company_section_view` (
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `company_section_view_id` int(10) unsigned NOT NULL DEFAULT '0',
  `company_id` int(10) unsigned NOT NULL DEFAULT '0',
  `section_id` int(10) unsigned NOT NULL DEFAULT '0',
  `section_view_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`company_section_view_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of company_section_view
-- ----------------------------

-- ----------------------------
-- Table structure for `module`
-- ----------------------------
DROP TABLE IF EXISTS `module`;
CREATE TABLE `module` (
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `name_short` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `directory` varchar(255) DEFAULT NULL,
  `section_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`name_short`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of module
-- ----------------------------
INSERT INTO `module` VALUES ('2005-01-17 14:35:49', '2005-01-17 14:35:49', 'active', 'admin', 'Admin', 'admin', null);
INSERT INTO `module` VALUES ('2005-01-17 14:35:34', '2005-01-17 14:35:34', 'active', 'funding', 'Funding', 'transaction', null);
INSERT INTO `module` VALUES ('2005-04-11 14:38:32', '2005-04-11 14:38:32', 'active', 'new_app', 'New App', 'new_app', null);
INSERT INTO `module` VALUES ('2005-01-17 14:36:03', '2005-01-17 14:36:03', 'active', 'reporting', 'Reporting', 'reporting', null);

-- ----------------------------
-- Table structure for `pop_accounts`
-- ----------------------------
DROP TABLE IF EXISTS `pop_accounts`;
CREATE TABLE `pop_accounts` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `account_name` varchar(128) NOT NULL,
  `from_domain` varchar(255) NOT NULL,
  `mail_server` varchar(255) NOT NULL,
  `mail_port` int(11) NOT NULL,
  `mail_box` varchar(255) NOT NULL DEFAULT 'INBOX',
  `mail_user` varchar(255) NOT NULL,
  `mail_pass` varchar(255) NOT NULL,
  `reply_to` varchar(255) NOT NULL,
  `mail_from` varchar(255) NOT NULL,
  `direction` enum('INCOMING','OUTGOING','BOTH') NOT NULL DEFAULT 'BOTH',
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `idx_company_id_name` (`company_id`,`account_name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of pop_accounts
-- ----------------------------
INSERT INTO `pop_accounts` VALUES ('29', '16', 'Default Outgoing', 'someloancompany.com', 'smtp.com', '25', 'INBOX', '', 'eZ+sF0PIdLhm4vfkezxxQg==', 'customerservice@someloancompany.com', 'SomeLoanCompany<customerservice@someloancompany.com>', 'OUTGOING');
INSERT INTO `pop_accounts` VALUES ('30', '16', 'Incoming Email', '', 'mail.someloancompany.com', '995', 'INBOX', 'customerservice@someloancompany.com', 'eZ+sF0PIdLhm4vfkezxxQg==', 'customerservice@someloancompany.com', '', 'INCOMING');
INSERT INTO `pop_accounts` VALUES ('174', '16', 'AALM Clientservices', '', 'mail.someloancompany.com', '995', 'INBOX', 'customerservice@someloancompany.com', 'eZ+sF0PIdLhm4vfkezxxQg==', 'customerservice@someloancompany.com', 'customerservice@someloancompany.com', 'INCOMING');
INSERT INTO `pop_accounts` VALUES ('204', '16', 'Outgoing Faxes', 'someloancompany.com', 'mail.someloancompany.com', '25', 'INBOX', 'customerservice@someloancompany.com', 'eZ+sF0PIdLhm4vfkezxxQg==', 'customerservice@someloancompany.com', 'customerservice@someloancompany.com', 'OUTGOING');

-- ----------------------------
-- Table structure for `section`
-- ----------------------------
DROP TABLE IF EXISTS `section`;
CREATE TABLE `section` (
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `system_id` int(10) unsigned NOT NULL DEFAULT '0',
  `section_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `section_parent_id` int(10) unsigned DEFAULT NULL,
  `sequence_no` smallint(5) unsigned NOT NULL DEFAULT '0',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `default_section_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `idx_section_name_parent_sys` (`name`,`section_parent_id`,`system_id`) USING BTREE,
  KEY `idx_section_sys_parent_seqno` (`system_id`,`section_parent_id`,`sequence_no`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of section
-- ----------------------------
INSERT INTO `section` VALUES ('2005-06-16 18:15:51', '2005-06-16 18:15:51', 'active', '0', '1', '*root', '*Root', null, '1', '0', '0');
INSERT INTO `section` VALUES ('2006-03-27 13:34:19', '2006-03-27 13:34:19', 'active', '1', '15', 'statistics_view', 'View Statistics', '9', '1', '3', '0');
INSERT INTO `section` VALUES ('2006-03-27 13:28:57', '2006-03-27 13:28:57', 'active', '1', '14', 'templates_new', 'Create New Template', '8', '2', '3', '0');
INSERT INTO `section` VALUES ('2006-03-27 13:20:03', '2006-03-27 13:20:03', 'active', '1', '13', 'templates_list', 'List Templates', '8', '1', '3', '0');
INSERT INTO `section` VALUES ('0000-00-00 00:00:00', '0000-00-00 00:00:00', 'active', '1', '7', 'ccs', 'ccs', '1', '5', '1', '0');
INSERT INTO `section` VALUES ('2006-03-27 13:37:12', '2006-03-27 13:37:12', 'active', '1', '16', 'archives_search', 'Find Documents', '12', '1', '3', '0');
INSERT INTO `section` VALUES ('2006-03-27 13:00:03', '0000-00-00 00:00:00', 'active', '1', '12', 'archives', 'Archives', '7', '3', '2', '0');
INSERT INTO `section` VALUES ('2006-03-27 13:00:28', '0000-00-00 00:00:00', 'active', '1', '8', 'templates', 'Templates', '7', '1', '2', '0');
INSERT INTO `section` VALUES ('2006-03-27 13:00:28', '0000-00-00 00:00:00', 'active', '1', '9', 'statistics', 'Statistics', '7', '2', '2', '0');
INSERT INTO `section` VALUES ('2006-03-27 13:00:28', '0000-00-00 00:00:00', 'active', '1', '11', 'incoming', 'Incoming Documents', '7', '5', '2', '0');
INSERT INTO `section` VALUES ('2006-03-27 20:25:23', '0000-00-00 00:00:00', 'active', '1', '18', 'templates_edit', 'Edit Template', '13', '1', '4', '0');
INSERT INTO `section` VALUES ('2006-03-27 14:10:40', '2006-03-27 14:10:40', 'active', '1', '17', 'incoming_unlinked', 'Unlinked Documents', '11', '1', '3', '0');
INSERT INTO `section` VALUES ('2006-03-30 08:14:09', '0000-00-00 00:00:00', 'active', '1', '19', 'templates_view', 'View Template', '13', '2', '4', '0');
INSERT INTO `section` VALUES ('2006-03-30 14:17:11', '0000-00-00 00:00:00', 'active', '1', '20', 'templates_preview', 'Preview Template', '19', '1', '5', '0');
INSERT INTO `section` VALUES ('2006-03-31 10:04:27', '0000-00-00 00:00:00', 'active', '1', '21', 'templates_attach_file', 'Attach File', '13', '4', '4', '0');
INSERT INTO `section` VALUES ('2006-03-31 10:04:27', '0000-00-00 00:00:00', 'active', '1', '22', 'templates_attach_template', 'Attach Template', '13', '5', '4', '0');
INSERT INTO `section` VALUES ('2006-04-04 13:42:25', '0000-00-00 00:00:00', 'active', '1', '23', 'admin', 'Admin', '7', '10', '2', '0');
INSERT INTO `section` VALUES ('2006-04-04 13:49:10', '0000-00-00 00:00:00', 'active', '1', '24', 'privs', 'Privileges', '23', '1', '3', '0');
INSERT INTO `section` VALUES ('2006-04-04 13:49:10', '0000-00-00 00:00:00', 'active', '1', '25', 'groups', 'Groups', '23', '2', '3', '0');
INSERT INTO `section` VALUES ('2006-04-04 13:49:10', '0000-00-00 00:00:00', 'active', '1', '26', 'profiles', 'Profiles', '23', '3', '3', '0');
INSERT INTO `section` VALUES ('2006-04-05 16:03:05', '0000-00-00 00:00:00', 'active', '1', '27', 'document_view', 'View Document', '16', '1', '4', '0');
INSERT INTO `section` VALUES ('2006-04-06 08:54:45', '0000-00-00 00:00:00', 'active', '1', '28', 'document_event_view', 'View Document Event History', '16', '2', '4', '0');
INSERT INTO `section` VALUES ('2006-04-06 08:54:45', '0000-00-00 00:00:00', 'active', '1', '29', 'document_resend', 'Resend Document', '16', '3', '4', '0');
INSERT INTO `section` VALUES ('2006-04-06 08:54:45', '0000-00-00 00:00:00', 'active', '1', '30', 'document_link', 'Link Document to Application ID', '16', '4', '4', '0');
INSERT INTO `section` VALUES ('2006-04-14 08:44:23', '2006-04-14 08:44:23', 'active', '1', '31', 'templates_history', 'View History', '13', '6', '4', '0');
INSERT INTO `section` VALUES ('2006-04-17 10:29:17', '2006-04-17 10:29:17', 'active', '1', '32', 'statistics_sent_failed', 'Failed Sent Documents', '9', '2', '3', '0');
INSERT INTO `section` VALUES ('2006-04-19 07:52:23', '2006-04-19 07:52:23', 'active', '1', '33', 'templates_tokens_list', 'List Tokens', '8', '3', '3', '0');
INSERT INTO `section` VALUES ('2006-04-19 08:56:06', '2006-04-19 08:56:06', 'active', '1', '34', 'templates_tokens_new', 'Create New Tokens', '8', '4', '3', '0');
INSERT INTO `section` VALUES ('2006-04-19 09:11:20', '0000-00-00 00:00:00', 'active', '1', '35', 'statistics_audit_report', 'Database Audit', '36', '1', '4', '0');
INSERT INTO `section` VALUES ('2006-04-20 08:22:19', '0000-00-00 00:00:00', 'active', '1', '36', 'statistics_auditing', 'Auditing', '9', '5', '3', '0');
INSERT INTO `section` VALUES ('2006-04-20 08:27:49', '0000-00-00 00:00:00', 'active', '1', '37', 'statistics_audit_filesystem', 'File System Audit', '36', '2', '4', '0');
INSERT INTO `section` VALUES ('2006-05-04 09:57:59', '2006-05-04 09:57:59', 'active', '1', '38', 'templates_tokens_edit', 'Edit Token', '33', '1', '4', '0');

-- ----------------------------
-- Table structure for `section_views`
-- ----------------------------
DROP TABLE IF EXISTS `section_views`;
CREATE TABLE `section_views` (
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `section_view_id` int(10) unsigned NOT NULL DEFAULT '0',
  `section_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`section_view_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of section_views
-- ----------------------------
INSERT INTO `section_views` VALUES ('2005-10-27 10:21:16', '0000-00-00 00:00:00', '1', '94', 'employment_info');
INSERT INTO `section_views` VALUES ('2005-10-27 10:21:16', '0000-00-00 00:00:00', '2', '94', 'bank_info');
INSERT INTO `section_views` VALUES ('2005-10-27 10:21:16', '0000-00-00 00:00:00', '3', '94', 'card_info');
INSERT INTO `section_views` VALUES ('2005-10-27 10:21:16', '0000-00-00 00:00:00', '4', '94', 'payday_info');
INSERT INTO `section_views` VALUES ('2005-10-28 08:36:19', '0000-00-00 00:00:00', '5', '94', 'card_info');
INSERT INTO `section_views` VALUES ('2006-01-17 13:10:02', '2006-01-17 12:58:43', '6', '94', 'olp_react_div_display');

-- ----------------------------
-- Table structure for `session_0`
-- ----------------------------
DROP TABLE IF EXISTS `session_0`;
CREATE TABLE `session_0` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_0
-- ----------------------------

-- ----------------------------
-- Table structure for `session_1`
-- ----------------------------
DROP TABLE IF EXISTS `session_1`;
CREATE TABLE `session_1` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_1
-- ----------------------------

-- ----------------------------
-- Table structure for `session_2`
-- ----------------------------
DROP TABLE IF EXISTS `session_2`;
CREATE TABLE `session_2` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_2
-- ----------------------------

-- ----------------------------
-- Table structure for `session_3`
-- ----------------------------
DROP TABLE IF EXISTS `session_3`;
CREATE TABLE `session_3` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_3
-- ----------------------------

-- ----------------------------
-- Table structure for `session_4`
-- ----------------------------
DROP TABLE IF EXISTS `session_4`;
CREATE TABLE `session_4` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_4
-- ----------------------------

-- ----------------------------
-- Table structure for `session_5`
-- ----------------------------
DROP TABLE IF EXISTS `session_5`;
CREATE TABLE `session_5` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_5
-- ----------------------------

-- ----------------------------
-- Table structure for `session_6`
-- ----------------------------
DROP TABLE IF EXISTS `session_6`;
CREATE TABLE `session_6` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_6
-- ----------------------------

-- ----------------------------
-- Table structure for `session_7`
-- ----------------------------
DROP TABLE IF EXISTS `session_7`;
CREATE TABLE `session_7` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_7
-- ----------------------------

-- ----------------------------
-- Table structure for `session_8`
-- ----------------------------
DROP TABLE IF EXISTS `session_8`;
CREATE TABLE `session_8` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_8
-- ----------------------------

-- ----------------------------
-- Table structure for `session_9`
-- ----------------------------
DROP TABLE IF EXISTS `session_9`;
CREATE TABLE `session_9` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_9
-- ----------------------------

-- ----------------------------
-- Table structure for `session_a`
-- ----------------------------
DROP TABLE IF EXISTS `session_a`;
CREATE TABLE `session_a` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_a
-- ----------------------------

-- ----------------------------
-- Table structure for `session_b`
-- ----------------------------
DROP TABLE IF EXISTS `session_b`;
CREATE TABLE `session_b` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_b
-- ----------------------------

-- ----------------------------
-- Table structure for `session_c`
-- ----------------------------
DROP TABLE IF EXISTS `session_c`;
CREATE TABLE `session_c` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_c
-- ----------------------------

-- ----------------------------
-- Table structure for `session_d`
-- ----------------------------
DROP TABLE IF EXISTS `session_d`;
CREATE TABLE `session_d` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_d
-- ----------------------------

-- ----------------------------
-- Table structure for `session_e`
-- ----------------------------
DROP TABLE IF EXISTS `session_e`;
CREATE TABLE `session_e` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_e
-- ----------------------------

-- ----------------------------
-- Table structure for `session_f`
-- ----------------------------
DROP TABLE IF EXISTS `session_f`;
CREATE TABLE `session_f` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_locked` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL DEFAULT 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `idx_created` (`date_created`) USING BTREE,
  KEY `idx_modified` (`date_modified`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of session_f
-- ----------------------------

-- ----------------------------
-- Table structure for `system`
-- ----------------------------
DROP TABLE IF EXISTS `system`;
CREATE TABLE `system` (
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `system_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `name_short` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`system_id`),
  UNIQUE KEY `idx_system_name_short` (`name_short`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of system
-- ----------------------------
INSERT INTO `system` VALUES ('2005-08-23 10:36:11', '0000-00-00 00:00:00', 'active', '1', 'ccsadmin', 'ccsadmin');
INSERT INTO `system` VALUES ('2006-05-19 08:18:08', '0000-00-00 00:00:00', 'active', '2', 'condorapi', 'condorapi');

-- ----------------------------
-- Table structure for `tokens`
-- ----------------------------
DROP TABLE IF EXISTS `tokens`;
CREATE TABLE `tokens` (
  `token` varchar(255) NOT NULL DEFAULT '',
  `date_created` datetime NOT NULL,
  `company_id` int(10) unsigned NOT NULL DEFAULT '0',
  `description` varchar(255) DEFAULT '',
  `encrypted` int(1) NOT NULL DEFAULT '0',
  `token_data_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`company_id`,`token`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of tokens
-- ----------------------------
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2006-08-14 13:42:52', '2', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2006-08-14 13:42:52', '2', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2006-08-14 13:42:52', '2', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2006-08-14 13:42:52', '2', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2006-08-14 13:42:52', '2', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2006-08-14 13:42:52', '2', 'Customers net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2006-08-14 13:42:52', '2', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2006-08-14 13:42:52', '2', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2006-08-14 13:42:52', '2', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2006-10-26 13:05:34', '2', 'Link to eSig page', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2006-08-28 08:30:33', '2', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2006-08-14 13:42:52', '2', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2006-08-14 13:42:52', '2', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2006-08-14 13:42:52', '2', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2006-08-14 13:42:52', '2', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2006-08-14 13:42:52', '2', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2006-08-14 13:42:52', '2', 'Customers Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2006-08-14 13:42:52', '2', 'Customers unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2006-08-14 13:42:52', '2', 'Customers street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2006-08-14 13:42:52', '2', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2006-08-14 13:42:52', '2', 'Last four numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2006-08-14 13:42:52', '2', 'Customers State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2006-08-14 13:42:52', '2', 'Middle two numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2006-08-14 13:42:52', '2', 'First three numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2006-08-14 13:42:52', '2', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2006-08-14 13:42:52', '2', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2006-08-30 13:15:43', '2', 'Customers cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2006-08-30 13:15:05', '2', 'Customers home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2006-08-14 13:42:52', '2', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2006-08-14 13:42:52', '2', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2006-08-14 13:42:52', '2', 'Customers fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2006-08-14 13:42:52', '2', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2006-08-30 13:11:31', '2', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2006-08-14 13:42:52', '2', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2006-08-14 13:42:52', '2', 'Customer\'s date of birth', '1', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2006-08-14 13:42:52', '2', 'Companys Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2006-08-14 13:42:52', '2', 'Customers city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2006-08-14 13:42:52', '2', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2006-08-14 13:42:52', '2', 'Companys unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2006-08-14 13:42:52', '2', 'Companys State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2006-08-14 13:42:52', '2', 'Companys street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2006-08-14 13:42:52', '2', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2006-08-14 13:42:52', '2', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2006-08-30 13:14:00', '2', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2006-08-30 13:13:27', '2', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2006-08-14 13:42:52', '2', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2006-08-14 13:42:52', '2', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyInitials%%%', '2006-10-26 14:47:20', '2', 'Company\'s initials', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompleteLetter%%%', '2006-08-03 12:48:16', '3', 'The complete letter.', '0', null);
INSERT INTO `tokens` VALUES ('%%%company_name%%%', '2006-10-12 07:42:19', '5', 'Company Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%company_state%%%', '2006-10-12 07:42:41', '5', 'Company State', '0', null);
INSERT INTO `tokens` VALUES ('%%%company_address%%%', '2006-10-12 07:45:25', '5', 'Company Address', '0', null);
INSERT INTO `tokens` VALUES ('%%%company_address%%%', '2006-10-12 07:53:54', '4', 'Company Address', '0', null);
INSERT INTO `tokens` VALUES ('%%%company_name%%%', '2006-10-12 07:54:04', '4', 'Company Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%company_state%%%', '2006-10-12 07:54:12', '4', 'Company State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2006-08-14 13:42:52', '2', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2006-08-14 13:42:52', '2', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2006-08-14 13:42:52', '2', 'Companys city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2006-08-30 13:09:15', '2', 'Companys stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2006-08-30 13:08:48', '2', 'Companys stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2006-08-30 13:08:24', '2', 'Companys stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2006-08-14 13:42:52', '2', 'Customers bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2006-08-30 13:07:54', '2', 'Companys stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2006-08-14 13:42:52', '2', 'Customers bank account number', '1', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2006-08-14 13:42:52', '2', 'Customers bank ABA number', '1', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2006-08-14 13:42:52', '2', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2006-08-14 13:42:52', '2', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2006-09-08 08:15:05', '2', 'Total due on the loan which consists of the outstanding principal, service charges, and fees', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2006-08-14 13:42:52', '2', 'The code assigned to the loan when it goes to collection', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2006-09-28 11:33:08', '2', 'The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2006-08-14 13:42:52', '2', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2006-08-14 13:42:52', '2', 'The due date of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2006-08-14 13:42:52', '2', 'Dollar amount of the credit cost', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2006-08-14 13:42:52', '2', 'Amount financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2006-08-14 13:42:52', '2', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2006-08-28 08:37:21', '2', 'The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2006-08-14 13:42:52', '2', 'Loan payoff date', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2006-10-26 15:44:39', '2', 'The status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2006-08-14 13:42:52', '2', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2006-08-28 08:29:27', '2', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2006-08-14 13:42:52', '2', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2006-08-14 13:42:52', '2', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2006-08-14 13:42:52', '2', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2006-08-30 12:46:44', '2', 'Paydown amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2006-08-30 12:46:15', '2', 'Paydown Finance Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2006-08-30 12:48:46', '2', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2006-08-30 12:47:16', '2', 'Total of all Paydown amounts', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2006-08-14 13:42:52', '2', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2006-08-14 13:42:52', '2', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2006-08-14 13:42:52', '2', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2006-08-14 13:42:52', '2', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2006-08-14 13:42:52', '2', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2006-08-14 13:42:52', '2', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2006-08-30 13:05:51', '2', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2006-08-14 13:42:52', '2', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2006-08-14 13:42:52', '2', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2006-08-14 13:42:52', '2', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2006-08-14 13:42:52', '2', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2006-08-14 13:42:52', '2', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2006-08-28 08:27:32', '2', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2006-08-14 13:42:52', '7', 'Customer\'s bank ABA number', '1', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2006-08-14 13:42:52', '7', 'Customer\'s bank account number', '1', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2006-08-14 13:42:52', '7', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2006-08-30 13:07:54', '7', 'Company\'s stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2006-08-30 13:08:24', '7', 'Company\'s stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2006-08-30 13:08:48', '7', 'Company\'s stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2006-08-30 13:09:15', '7', 'Company\'s stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2006-08-14 13:42:52', '7', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-01-25 10:57:40', '7', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-01-25 10:58:32', '7', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2006-08-14 13:42:52', '7', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2006-08-14 13:42:52', '7', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2006-08-14 13:42:52', '7', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2006-08-14 13:42:52', '7', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2006-08-30 13:13:27', '7', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2006-08-30 13:14:00', '7', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2006-08-14 13:42:52', '7', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2006-08-14 13:42:52', '7', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2006-08-14 13:42:52', '7', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2006-08-14 13:42:52', '7', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2006-08-14 13:42:52', '7', 'Company\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2006-08-14 13:42:52', '7', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2006-08-14 13:42:52', '7', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2006-08-14 13:42:52', '7', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2006-08-14 13:42:52', '7', 'Customer\'s date of birth', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2006-08-14 13:42:52', '7', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2006-08-30 13:11:31', '7', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2006-08-14 13:42:52', '7', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2006-08-14 13:42:52', '7', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2006-08-14 13:42:52', '7', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2006-08-14 13:42:52', '7', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2006-08-30 13:15:43', '7', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2006-08-30 13:15:05', '7', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2006-08-14 13:42:52', '7', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2006-08-14 13:42:52', '7', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2006-08-14 13:42:52', '7', 'First three numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2006-08-14 13:42:52', '7', 'Middle two numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2006-08-14 13:42:52', '7', 'Last four numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2006-08-14 13:42:52', '7', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2006-08-14 13:42:52', '7', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2006-08-14 13:42:52', '7', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2006-08-14 13:42:52', '7', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2006-08-14 13:42:52', '7', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2006-08-14 13:42:52', '7', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2006-08-14 13:42:52', '7', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2006-08-14 13:42:52', '7', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2006-08-14 13:42:52', '7', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2006-08-14 13:42:52', '7', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2006-08-28 08:30:33', '7', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2006-08-14 13:42:52', '7', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2006-08-14 13:42:52', '7', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2006-08-14 13:42:52', '7', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2006-08-14 13:42:52', '7', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2006-08-14 13:42:52', '7', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2006-08-14 13:42:52', '7', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2006-08-14 13:42:52', '7', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2006-08-14 13:42:52', '7', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2006-08-14 13:42:52', '7', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2006-08-14 13:42:52', '7', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2006-08-14 13:42:52', '7', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2006-09-08 08:15:05', '7', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2006-08-14 13:42:52', '7', 'The code assigned to the loan when it goes to collection. Set from company configuration.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-01-26 13:35:09', '7', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-01-26 13:36:42', '7', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-01-26 13:39:16', '7', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-01-26 13:37:32', '7', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2006-09-28 11:33:08', '7', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-01-26 13:35:44', '7', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-01-26 13:38:30', '7', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2006-08-14 13:42:52', '7', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2006-08-14 13:42:52', '7', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2006-08-14 13:42:52', '7', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2006-08-14 13:42:52', '7', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-01-26 14:45:33', '7', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2006-08-14 13:42:52', '7', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2006-08-28 08:37:21', '7', '(deprecated) The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-01-26 13:41:33', '7', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-01-26 13:43:32', '7', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-01-26 13:44:32', '7', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-01-26 13:42:45', '7', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2006-08-14 13:42:52', '7', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-01-26 14:42:40', '7', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2006-12-15 15:57:09', '7', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2006-08-14 13:42:52', '7', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-01-24 13:24:06', '7', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-01-24 13:25:07', '7', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-01-24 13:24:37', '7', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2006-08-28 08:29:27', '7', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2006-08-14 13:42:52', '7', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2006-08-14 13:42:52', '7', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2006-08-14 13:42:52', '7', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2006-08-30 12:46:44', '7', 'Paydown amount for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-01-26 13:50:45', '7', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2006-08-30 12:46:15', '7', 'Paydown Finance Charge for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-01-24 13:39:33', '7', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-01-26 13:51:14', '7', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2006-08-30 12:48:46', '7', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-01-24 13:40:31', '7', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2006-08-30 12:47:16', '7', 'Total current paydown amount (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2006-08-14 13:42:52', '7', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2006-08-14 13:42:52', '7', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2006-08-14 13:42:52', '7', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2006-08-14 13:42:52', '7', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2006-08-14 13:42:52', '7', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2006-08-14 13:42:52', '7', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2006-08-30 13:05:51', '7', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2006-08-14 13:42:52', '7', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2006-08-14 13:42:52', '7', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2006-08-14 13:42:52', '7', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2006-08-14 13:42:52', '7', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2006-08-14 13:42:52', '7', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2006-08-28 08:27:32', '7', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-02-28 08:51:19', '7', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-03-05 21:15:22', '2', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-03-05 21:16:47', '2', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-03-05 21:18:13', '2', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-03-05 21:19:14', '2', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-03-05 21:20:03', '2', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-03-05 21:20:30', '2', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-03-05 21:21:50', '2', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-03-05 21:22:23', '2', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-03-05 21:24:00', '2', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-03-05 21:24:59', '2', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-03-05 21:25:46', '2', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-03-05 21:26:39', '2', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-03-05 21:28:47', '2', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-03-05 21:30:51', '2', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-03-05 21:31:26', '2', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-03-05 21:32:36', '2', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-03-05 21:33:37', '2', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-03-05 21:36:36', '2', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-03-05 21:37:14', '2', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-03-05 21:37:54', '2', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-03-05 21:38:16', '2', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%Day%%%', '2007-03-05 21:44:00', '2', 'The day of the week', '0', null);
INSERT INTO `tokens` VALUES ('%%%Time%%%', '2007-03-05 21:44:48', '2', 'The time of day', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-03-28 09:50:53', '7', 'Used with the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-03-28 09:51:35', '7', 'Used with the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-05-18 09:34:12', '8', 'Customer\'s bank ABA number', '1', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-05-18 09:34:12', '8', 'Customer\'s bank account number', '1', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-05-18 09:34:12', '8', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-05-18 09:34:12', '8', 'Company\'s stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-05-18 09:34:12', '8', 'Company\'s stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-05-18 09:34:12', '8', 'Company\'s stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-05-18 09:34:12', '8', 'Company\'s stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-05-18 09:34:12', '8', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-05-18 09:34:12', '8', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-05-18 09:34:12', '8', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-05-18 09:34:12', '8', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-05-18 09:34:12', '8', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-05-18 09:34:12', '8', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-05-18 09:34:12', '8', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-05-18 09:34:12', '8', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-05-18 09:34:12', '8', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-05-18 09:34:12', '8', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-05-18 09:34:12', '8', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-05-18 09:34:12', '8', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-05-18 09:34:12', '8', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-05-18 09:34:12', '8', 'Company\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-05-18 09:34:12', '8', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-05-18 09:34:12', '8', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-05-18 09:34:12', '8', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-05-18 09:34:12', '8', 'Customer\'s date of birth', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-05-18 09:34:12', '8', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-05-18 09:34:12', '8', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-05-18 09:34:12', '8', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-05-18 09:34:12', '8', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-05-18 09:34:12', '8', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-05-18 09:34:12', '8', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-05-18 09:34:12', '8', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-05-18 09:34:12', '8', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-05-18 09:34:12', '8', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-05-18 09:34:12', '8', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-05-18 09:34:12', '8', 'First three numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-05-18 09:34:12', '8', 'Middle two numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-05-18 09:34:12', '8', 'Last four numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-05-18 09:34:12', '8', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-05-18 09:34:12', '8', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-05-18 09:34:12', '8', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-05-18 09:34:12', '8', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-05-18 09:34:12', '8', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-05-18 09:34:12', '8', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-05-18 09:34:12', '8', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-05-18 09:34:12', '8', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-05-18 09:34:12', '8', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-05-18 09:34:12', '8', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-05-18 09:34:12', '8', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-05-18 09:34:12', '8', 'Used with the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-05-18 09:34:12', '8', 'Used with the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-05-18 09:34:12', '8', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-05-18 09:34:12', '8', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-05-18 09:34:12', '8', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-05-18 09:34:12', '8', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-05-18 09:34:12', '8', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-05-18 09:34:12', '8', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-05-18 09:34:12', '8', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-05-18 09:34:12', '8', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-05-18 09:34:12', '8', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-05-18 09:34:12', '8', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-05-18 09:34:12', '8', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-05-18 09:34:12', '8', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-05-18 09:34:12', '8', 'The code assigned to the loan when it goes to collection. Set from company configuration.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-05-18 09:34:12', '8', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-05-18 09:34:12', '8', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-05-18 09:34:12', '8', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-05-18 09:34:12', '8', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-05-18 09:34:12', '8', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-05-18 09:34:12', '8', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-05-18 09:34:12', '8', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-05-18 09:34:12', '8', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-05-18 09:34:12', '8', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-05-18 09:34:12', '8', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-05-18 09:34:12', '8', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-05-18 09:34:12', '8', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-05-18 09:34:12', '8', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-05-18 09:34:12', '8', '(deprecated) The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-05-18 09:34:12', '8', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-05-18 09:34:12', '8', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-05-18 09:34:12', '8', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-05-18 09:34:12', '8', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-05-18 09:34:12', '8', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-05-18 09:34:12', '8', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-05-18 09:34:12', '8', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-05-18 09:34:12', '8', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-05-18 09:34:12', '8', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-05-18 09:34:12', '8', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-05-18 09:34:12', '8', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-05-18 09:34:12', '8', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-05-18 09:34:12', '8', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-05-18 09:34:12', '8', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-05-18 09:34:12', '8', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-05-18 09:34:12', '8', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-05-18 09:34:12', '8', 'Paydown amount for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-05-18 09:34:12', '8', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-05-18 09:34:12', '8', 'Paydown Finance Charge for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-05-18 09:34:12', '8', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-05-18 09:34:12', '8', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-05-18 09:34:12', '8', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-05-18 09:34:12', '8', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-05-18 09:34:12', '8', 'Total current paydown amount (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-05-18 09:34:12', '8', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-05-18 09:34:12', '8', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-05-18 09:34:12', '8', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-05-18 09:34:12', '8', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-05-18 09:34:12', '8', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-05-18 09:34:12', '8', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-05-18 09:34:12', '8', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-05-18 09:34:12', '8', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-05-18 09:34:12', '8', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-05-18 09:34:12', '8', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-05-18 09:34:12', '8', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-05-18 09:34:12', '8', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-05-18 09:34:12', '8', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrAmount%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoginId%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPrincipal%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanStatus%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPayoffDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrinPmnt%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrincipal%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFinCharge%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFees%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextDueDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextBalance%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextAPR%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAvail%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAmount%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFinCharge%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFees%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDueDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrinPmnt%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrincipal%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFinCharge%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFees%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrDueDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrAPR%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrBalance%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCollectionCode%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanApplicationID%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeType%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate4%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate3%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate2%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate1%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeNetPay%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeMonthlyNet%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeFrequency%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeDD%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericSubject%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericMessage%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericEsigLink%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildeSigLink%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerTitle%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerShift%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerPhone%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerName%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerLength%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerZip%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerUnit%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStreet%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStateID%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart3%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerState%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart2%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart1%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceType%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceLength%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneHome%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneCell%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameLast%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFull%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFirst%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerESig%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerFax%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerEmail%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerCity%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerDOB%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildConfirmLink%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyZip%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyWebSite%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyUnit%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyStreet%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanySupportFax%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyState%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPhone%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPromoID%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameLegal%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameShort%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoSmall%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoLarge%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyInit%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyFax%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyEmail%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCustServ%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCollections%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDept%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyCity%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServPhone%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServName%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankShort%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankName%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankName%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankAccount%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankABA%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-11-07 12:07:29', '9', 'Company\'s stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-11-07 12:07:29', '9', 'Company\'s stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-11-07 12:07:29', '9', 'Company\'s stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-11-07 12:07:29', '9', 'Company\'s stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardNumber%%%', '2007-11-07 12:07:29', '9', 'The number of the customer\'s stored value card.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardName%%%', '2007-11-07 12:07:29', '9', 'The name of the company\'s stored value card', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-11-07 12:07:29', '9', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-11-07 12:07:29', '9', 'Customer\'s bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-11-07 12:07:29', '9', 'Customer\'s bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-05-21 15:57:51', '10', 'Customer\'s bank ABA number', '1', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-05-21 15:57:51', '10', 'Customer\'s bank account number', '1', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-05-21 15:57:51', '10', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-05-21 15:57:51', '10', 'Company\'s stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-05-21 15:57:51', '10', 'Company\'s stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-05-21 15:57:51', '10', 'Company\'s stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-05-21 15:57:51', '10', 'Company\'s stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-05-21 15:57:51', '10', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-05-21 15:57:51', '10', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-05-21 15:57:51', '10', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-05-21 15:57:51', '10', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-05-21 15:57:51', '10', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-05-21 15:57:51', '10', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-05-21 15:57:51', '10', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-05-21 15:57:51', '10', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-05-21 15:57:51', '10', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-05-21 15:57:51', '10', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-05-21 15:57:51', '10', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-05-21 15:57:51', '10', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-05-21 15:57:51', '10', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-05-21 15:57:51', '10', 'Company\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-05-21 15:57:51', '10', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-05-21 15:57:51', '10', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-05-21 15:57:51', '10', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-05-21 15:57:51', '10', 'Customer\'s date of birth', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-05-21 15:57:51', '10', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-05-21 15:57:51', '10', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-05-21 15:57:51', '10', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-05-21 15:57:51', '10', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-05-21 15:57:51', '10', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-05-21 15:57:51', '10', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-05-21 15:57:51', '10', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-05-21 15:57:51', '10', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-05-21 15:57:51', '10', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-05-21 15:57:51', '10', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-05-21 15:57:51', '10', 'First three numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-05-21 15:57:51', '10', 'Middle two numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-05-21 15:57:51', '10', 'Last four numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-05-21 15:57:51', '10', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-05-21 15:57:51', '10', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-05-21 15:57:51', '10', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-05-21 15:57:51', '10', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-05-21 15:57:51', '10', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-05-21 15:57:51', '10', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-05-21 15:57:51', '10', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-05-21 15:57:51', '10', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-05-21 15:57:51', '10', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-05-21 15:57:51', '10', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-05-21 15:57:51', '10', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-05-21 15:57:51', '10', 'Used with the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-05-21 15:57:51', '10', 'Used with the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-05-21 15:57:51', '10', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-05-21 15:57:51', '10', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-05-21 15:57:51', '10', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-05-21 15:57:51', '10', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-05-21 15:57:51', '10', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-05-21 15:57:51', '10', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-05-21 15:57:51', '10', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-05-21 15:57:51', '10', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-05-21 15:57:51', '10', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-05-21 15:57:51', '10', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-05-21 15:57:51', '10', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-05-21 15:57:51', '10', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-05-21 15:57:51', '10', 'The code assigned to the loan when it goes to collection. Set from company configuration.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-05-21 15:57:51', '10', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-05-21 15:57:51', '10', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-05-21 15:57:51', '10', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-05-21 15:57:51', '10', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-05-21 15:57:51', '10', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-05-21 15:57:51', '10', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-05-21 15:57:51', '10', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-05-21 15:57:51', '10', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-05-21 15:57:51', '10', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-05-21 15:57:51', '10', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-05-21 15:57:51', '10', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-05-21 15:57:51', '10', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-05-21 15:57:51', '10', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-05-21 15:57:51', '10', '(deprecated) The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-05-21 15:57:51', '10', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-05-21 15:57:51', '10', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-05-21 15:57:51', '10', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-05-21 15:57:51', '10', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-05-21 15:57:51', '10', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-05-21 15:57:51', '10', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-05-21 15:57:51', '10', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-05-21 15:57:51', '10', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-05-21 15:57:51', '10', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-05-21 15:57:51', '10', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-05-21 15:57:51', '10', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-05-21 15:57:51', '10', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-05-21 15:57:51', '10', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-05-21 15:57:51', '10', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-05-21 15:57:51', '10', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-05-21 15:57:51', '10', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-05-21 15:57:51', '10', 'Paydown amount for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-05-21 15:57:51', '10', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-05-21 15:57:51', '10', 'Paydown Finance Charge for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-05-21 15:57:51', '10', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-05-21 15:57:51', '10', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-05-21 15:57:51', '10', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-05-21 15:57:51', '10', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-05-21 15:57:51', '10', 'Total current paydown amount (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-05-21 15:57:51', '10', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-05-21 15:57:51', '10', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-05-21 15:57:51', '10', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-05-21 15:57:51', '10', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-05-21 15:57:51', '10', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-05-21 15:57:51', '10', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-05-21 15:57:51', '10', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-05-21 15:57:51', '10', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-05-21 15:57:51', '10', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-05-21 15:57:51', '10', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-05-21 15:57:51', '10', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-05-21 15:57:51', '10', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-05-21 15:57:51', '10', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-05-21 15:57:54', '11', 'Customer\'s bank ABA number', '1', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-05-21 15:57:54', '11', 'Customer\'s bank account number', '1', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-05-21 15:57:54', '11', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-05-21 15:57:54', '11', 'Company\'s stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-05-21 15:57:54', '11', 'Company\'s stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-05-21 15:57:54', '11', 'Company\'s stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-05-21 15:57:54', '11', 'Company\'s stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-05-21 15:57:54', '11', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-05-21 15:57:54', '11', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-05-21 15:57:54', '11', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-05-21 15:57:54', '11', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-05-21 15:57:54', '11', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-05-21 15:57:54', '11', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-05-21 15:57:54', '11', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-05-21 15:57:54', '11', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-05-21 15:57:54', '11', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-05-21 15:57:54', '11', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-05-21 15:57:54', '11', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-05-21 15:57:54', '11', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-05-21 15:57:54', '11', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-05-21 15:57:54', '11', 'Company\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-05-21 15:57:54', '11', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-05-21 15:57:54', '11', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-05-21 15:57:54', '11', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-05-21 15:57:54', '11', 'Customer\'s date of birth', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-05-21 15:57:54', '11', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-05-21 15:57:54', '11', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-05-21 15:57:54', '11', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-05-21 15:57:54', '11', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-05-21 15:57:54', '11', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-05-21 15:57:54', '11', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-05-21 15:57:54', '11', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-05-21 15:57:54', '11', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-05-21 15:57:54', '11', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-05-21 15:57:54', '11', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-05-21 15:57:54', '11', 'First three numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-05-21 15:57:54', '11', 'Middle two numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-05-21 15:57:54', '11', 'Last four numbers of the social security number', '1', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-05-21 15:57:54', '11', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-05-21 15:57:54', '11', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-05-21 15:57:54', '11', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-05-21 15:57:54', '11', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-05-21 15:57:54', '11', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-05-21 15:57:54', '11', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-05-21 15:57:54', '11', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-05-21 15:57:54', '11', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-05-21 15:57:54', '11', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-05-21 15:57:54', '11', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-05-21 15:57:54', '11', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-05-21 15:57:54', '11', 'Used with the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-05-21 15:57:54', '11', 'Used with the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-05-21 15:57:54', '11', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-05-21 15:57:54', '11', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-05-21 15:57:54', '11', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-05-21 15:57:54', '11', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-05-21 15:57:54', '11', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-05-21 15:57:54', '11', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-05-21 15:57:54', '11', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-05-21 15:57:54', '11', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-05-21 15:57:54', '11', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-05-21 15:57:54', '11', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-05-21 15:57:54', '11', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-05-21 15:57:54', '11', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-05-21 15:57:54', '11', 'The code assigned to the loan when it goes to collection. Set from company configuration.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-05-21 15:57:54', '11', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-05-21 15:57:54', '11', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-05-21 15:57:54', '11', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-05-21 15:57:54', '11', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-05-21 15:57:54', '11', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-05-21 15:57:54', '11', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-05-21 15:57:54', '11', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-05-21 15:57:54', '11', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-05-21 15:57:54', '11', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-05-21 15:57:54', '11', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-05-21 15:57:54', '11', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-05-21 15:57:54', '11', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-05-21 15:57:54', '11', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-05-21 15:57:54', '11', '(deprecated) The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-05-21 15:57:54', '11', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-05-21 15:57:54', '11', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-05-21 15:57:54', '11', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-05-21 15:57:54', '11', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-05-21 15:57:54', '11', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-05-21 15:57:54', '11', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-05-21 15:57:54', '11', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-05-21 15:57:54', '11', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-05-21 15:57:54', '11', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-05-21 15:57:54', '11', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-05-21 15:57:54', '11', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-05-21 15:57:54', '11', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-05-21 15:57:54', '11', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-05-21 15:57:54', '11', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-05-21 15:57:54', '11', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-05-21 15:57:54', '11', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-05-21 15:57:54', '11', 'Paydown amount for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-05-21 15:57:54', '11', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-05-21 15:57:54', '11', 'Paydown Finance Charge for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-05-21 15:57:54', '11', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-05-21 15:57:54', '11', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-05-21 15:57:54', '11', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-05-21 15:57:54', '11', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-05-21 15:57:54', '11', 'Total current paydown amount (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-05-21 15:57:54', '11', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-05-21 15:57:54', '11', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-05-21 15:57:54', '11', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-05-21 15:57:54', '11', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-05-21 15:57:54', '11', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-05-21 15:57:54', '11', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-05-21 15:57:54', '11', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-05-21 15:57:54', '11', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-05-21 15:57:54', '11', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-05-21 15:57:54', '11', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-05-21 15:57:54', '11', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-05-21 15:57:54', '11', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-05-21 15:57:54', '11', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%fax_date%%%', '2007-06-29 09:56:24', '4', 'Fax Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%fax_time%%%', '2007-06-29 09:56:42', '4', 'Fax Time', '0', null);
INSERT INTO `tokens` VALUES ('%%%credit_amount%%%', '2007-06-29 09:57:03', '4', 'Credit Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%debit_amount%%%', '2007-06-29 09:57:20', '4', 'Debit Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%credit_transactions%%%', '2007-06-29 09:57:35', '4', 'Credit Transactions', '0', null);
INSERT INTO `tokens` VALUES ('%%%debit_transactions%%%', '2007-06-29 09:58:04', '4', 'Debit Transactions', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialPayDown%%%', '2007-07-17 07:09:49', '7', 'd', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2007-07-17 07:11:28', '7', 'd', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialInFull%%%', '2007-07-17 07:11:43', '7', 'd', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialPaydown%%%', '2007-07-17 07:15:16', '11', 'Customer\'s initials for pay downs.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialInFull%%%', '2007-07-17 07:15:38', '11', 'Customer\'s initials for paying in full.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialInFull%%%', '2007-08-02 15:32:49', '8', 'Customer\'s initials for paying in full.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialPayDown%%%', '2007-08-02 15:33:51', '8', 'Customer\'s initials for pay downs.', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextApp%%%', '2007-09-13 21:45:24', '7', 'Display text for ESig line for the application section', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextLoan%%%', '2007-09-13 21:45:51', '7', 'Display text for ESig line for the loan note and disclosure', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextAuth%%%', '2007-09-13 21:46:13', '7', 'Display text for ESig line for the authorization agreement', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextApp%%%', '2007-09-13 21:49:26', '8', 'Display text for ESig line for the application section', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextAuth%%%', '2007-09-13 21:49:41', '8', 'Display text for ESig line for the authorization agreement', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextLoan%%%', '2007-09-13 21:49:53', '8', 'Display text for ESig line for the loan note and disclosure', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextApp%%%', '2007-09-13 21:52:30', '11', 'Display text for ESig line for the application section', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextAuth%%%', '2007-09-13 21:52:42', '11', 'Display text for ESig line for the authorization agreement', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextLoan%%%', '2007-09-13 21:52:55', '11', 'Display text for ESig line for the loan note and disclosure', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReceiveCode%%%', '2007-09-25 17:08:14', '8', 'MoneyGram Receive Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReceiveCode%%%', '2007-09-25 17:09:42', '11', 'MoneyGram Receive Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReceiveCode%%%', '2007-09-25 17:10:54', '7', 'MoneyGram Receive Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%TestToken%%%', '2007-09-25 21:43:11', '7', 'A token to test stuff', '1', null);
INSERT INTO `tokens` VALUES ('%%%ChildNextBusinessDay%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrType%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDocDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanBalance%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanAPR%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundActionDate%%%', '2007-10-23 19:10:31', '2', 'The estimated date the funds are sent to the bank', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDueDate%%%', '2007-10-23 19:10:52', '2', 'Based on the LoanFundDueDate, this is the date the funds are available to the customer.', '0', null);
INSERT INTO `tokens` VALUES ('%%%AccountRep%%%', '2007-10-24 19:25:38', '12', 'Account Represetative', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-10-24 19:25:38', '12', 'Customer\'s bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-10-24 19:25:38', '12', 'Customer\'s bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccountType%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-10-24 19:25:38', '12', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-10-24 19:25:38', '12', 'Company\'s stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-10-24 19:25:38', '12', 'Company\'s stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-10-24 19:25:38', '12', 'Company\'s stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-10-24 19:25:38', '12', 'Company\'s stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankABA%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankAccount%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankName%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankName%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankShort%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServName%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServPhone%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyCity%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDept%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCollections%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCustServ%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyEmail%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyFax%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyInit%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoLarge%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoSmall%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameLegal%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameShort%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPhone%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPromoID%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyState%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyStreet%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanySupportFax%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyUnit%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyWebSite%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyZip%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildConfirmLink%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerCity%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerDOB%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerEmail%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerESig%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerFax%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFirst%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFull%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameLast%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneCell%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneHome%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceLength%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceType%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart1%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart2%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart3%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerState%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStateID%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStreet%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerUnit%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerZip%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerLength%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerName%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerPhone%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerShift%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerTitle%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildeSigLink%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericEsigLink%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericMessage%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericSubject%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeDD%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeFrequency%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeMonthlyNet%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeNetPay%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate1%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate2%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate3%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate4%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeType%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanApplicationID%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanAPR%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanBalance%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCollectionCode%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrAPR%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrBalance%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrDueDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFees%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFinCharge%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrincipal%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrinPmnt%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDocDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDueDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFees%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFinCharge%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAmount%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAvail%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextAPR%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextBalance%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextDueDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFees%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFinCharge%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrincipal%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrinPmnt%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPayoffDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPrincipal%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanStatus%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoginId%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrAmount%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrType%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildNextBusinessDay%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPassword%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrAmount%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrType%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDAmount%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDDueDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDFinCharge%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextAmount%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextDueDate%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextFinCharge%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextTotal%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDTotal%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPrincipalPaymentAmount%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01NameFull%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01PhoneHome%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01Relationship%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02NameFull%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02PhoneHome%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02Relationship%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRefinanceAmount%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnFee%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnReason%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSenderName%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourcePromoID%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourceSiteName%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildToday%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildTotalOfPayments%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildUsername%%%', '2007-10-24 19:25:38', '12', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-10-24 19:25:38', '12', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCounty%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-10-24 19:25:38', '12', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-10-24 19:25:38', '12', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-10-24 19:25:38', '12', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-10-24 19:25:38', '12', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-10-24 19:25:38', '12', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-10-24 19:25:38', '12', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-10-24 19:25:38', '12', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-10-24 19:25:38', '12', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-10-24 19:25:38', '12', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhonel%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-10-24 19:25:38', '12', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-10-24 19:25:38', '12', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-10-24 19:25:38', '12', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-10-24 19:25:38', '12', 'Company\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-10-24 19:25:38', '12', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-10-24 19:25:38', '12', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-10-24 19:25:38', '12', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-10-24 19:25:38', '12', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-10-24 19:25:38', '12', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-10-24 19:25:38', '12', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-10-24 19:25:38', '12', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFullName%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialInFull%%%', '2007-10-24 19:25:38', '12', 'Customer\'s initials for paying in full.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialPayDown%%%', '2007-10-24 19:25:38', '12', 'Customer\'s initials for pay downs.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-10-24 19:25:38', '12', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-10-24 19:25:38', '12', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-10-24 19:25:38', '12', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhone%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-10-24 19:25:38', '12', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-10-24 19:25:38', '12', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-10-24 19:25:38', '12', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-10-24 19:25:38', '12', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-10-24 19:25:38', '12', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-10-24 19:25:38', '12', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-10-24 19:25:38', '12', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-10-24 19:25:38', '12', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-10-24 19:25:38', '12', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-10-24 19:25:38', '12', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-10-24 19:25:38', '12', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-10-24 19:25:38', '12', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%DeliveryFee%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-10-24 19:25:38', '12', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-10-24 19:25:38', '12', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-10-24 19:25:38', '12', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-10-24 19:25:38', '12', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-10-24 19:25:38', '12', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-10-24 19:25:38', '12', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-10-24 19:25:38', '12', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-10-24 19:25:38', '12', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-10-24 19:25:38', '12', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-10-24 19:25:38', '12', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-10-24 19:25:38', '12', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-10-24 19:25:38', '12', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-10-24 19:25:38', '12', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-10-24 19:25:38', '12', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-10-24 19:25:38', '12', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-10-24 19:25:38', '12', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-10-24 19:25:38', '12', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-10-24 19:25:38', '12', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-10-24 19:25:38', '12', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-10-24 19:25:38', '12', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-10-24 19:25:38', '12', 'The code assigned to the loan when it goes to collection. Set from company configuration.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-10-24 19:25:38', '12', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-10-24 19:25:38', '12', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-10-24 19:25:38', '12', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-10-24 19:25:38', '12', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-10-24 19:25:38', '12', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-10-24 19:25:38', '12', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-10-24 19:25:38', '12', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-10-24 19:25:38', '12', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-10-24 19:25:38', '12', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinanceCharge%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-10-24 19:25:38', '12', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFunAmount%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-10-24 19:25:38', '12', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-10-24 19:25:38', '12', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-10-24 19:25:38', '12', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-10-24 19:25:38', '12', '(deprecated) The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-10-24 19:25:38', '12', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-10-24 19:25:38', '12', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-10-24 19:25:38', '12', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-10-24 19:25:38', '12', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-10-24 19:25:38', '12', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-10-24 19:25:38', '12', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanRefAmount%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-10-24 19:25:38', '12', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-10-24 19:25:38', '12', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Make%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-10-24 19:25:38', '12', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-10-24 19:25:38', '12', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-10-24 19:25:38', '12', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%Model%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReference%%%', '2007-10-24 19:25:38', '12', 'MoneyGram Reference Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%NetLoanProceeds%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%NetProceedsAmount%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-10-24 19:25:38', '12', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-10-24 19:25:38', '12', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-10-24 19:25:38', '12', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-10-24 19:25:38', '12', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-10-24 19:25:38', '12', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentDate%%%', '2007-10-24 19:25:38', '12', 'Payment Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-10-24 19:25:38', '12', 'Paydown amount for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-10-24 19:25:38', '12', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-10-24 19:25:38', '12', 'Paydown Finance Charge for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-10-24 19:25:38', '12', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-10-24 19:25:38', '12', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-10-24 19:25:38', '12', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-10-24 19:25:38', '12', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-10-24 19:25:38', '12', 'Total current paydown amount (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2007-10-24 19:25:38', '12', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-10-24 19:25:38', '12', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-10-24 19:25:38', '12', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-10-24 19:25:38', '12', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-10-24 19:25:38', '12', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-10-24 19:25:38', '12', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-10-24 19:25:38', '12', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-10-24 19:25:38', '12', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-10-24 19:25:38', '12', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SettlementOffer%%%', '2007-10-24 19:25:38', '12', 'Settlement Offer', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-10-24 19:25:38', '12', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-10-24 19:25:38', '12', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%test%%%', '2007-10-24 19:25:38', '12', 'Please remove this token when going live.', '0', null);
INSERT INTO `tokens` VALUES ('%%%TilteLienFee%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%Time%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-10-24 19:25:38', '12', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-10-24 19:25:38', '12', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-10-24 19:25:38', '12', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%VehicleMileage%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%VIN%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%WireFee%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%WireTransferFee%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%Year%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%YR%%%', '2007-10-24 19:25:38', '12', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%AccountRep%%%', '2007-10-25 00:37:36', '14', 'Account Representative', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-10-25 00:37:36', '14', 'Customer\'s bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-10-25 00:37:36', '14', 'Customer\'s bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccountType%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-10-25 00:37:36', '14', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-10-25 00:37:36', '14', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCounty%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDept%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-10-25 00:37:36', '14', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-10-25 00:37:36', '14', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyInit%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2007-10-25 00:37:36', '14', 'The name of the company.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-10-25 00:37:36', '14', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-10-25 00:37:36', '14', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-10-25 00:37:36', '14', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhonel%%%', '2007-10-25 00:37:36', '14', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPromoID%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-10-25 00:37:36', '14', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-10-25 00:37:36', '14', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-10-25 00:37:36', '14', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-10-25 00:37:36', '14', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-10-25 00:37:36', '14', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-10-25 00:37:36', '14', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCounty%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-10-25 00:37:36', '14', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-10-25 00:37:36', '14', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-10-25 00:37:36', '14', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-10-25 00:37:36', '14', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFullName%%%', '2007-10-25 00:37:36', '14', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-10-25 00:37:36', '14', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-10-25 00:37:36', '14', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-10-25 00:37:36', '14', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhone%%%', '2007-10-25 00:37:36', '14', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-10-25 00:37:36', '14', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-10-25 00:37:36', '14', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-10-25 00:37:36', '14', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-10-25 00:37:36', '14', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-10-25 00:37:36', '14', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-10-25 00:37:36', '14', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-10-25 00:37:36', '14', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-10-25 00:37:36', '14', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-10-25 00:37:36', '14', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-10-25 00:37:36', '14', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-10-25 00:37:36', '14', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-10-25 00:37:36', '14', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%Day%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DeliveryFee%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-10-25 00:37:36', '14', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-10-25 00:37:36', '14', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-10-25 00:37:36', '14', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-10-25 00:37:36', '14', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-10-25 00:37:36', '14', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-10-25 00:37:36', '14', 'Generic Message Text', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-10-25 00:37:36', '14', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-10-25 00:37:36', '14', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-10-25 00:37:36', '14', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-10-25 00:37:36', '14', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-10-25 00:37:36', '14', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-10-25 00:37:36', '14', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-10-25 00:37:36', '14', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-10-25 00:37:36', '14', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-10-25 00:37:36', '14', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-10-25 00:37:36', '14', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-10-25 00:37:36', '14', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-10-25 00:37:36', '14', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-10-25 00:37:36', '14', 'The current outstanding principal + the accrued interest as of today\'s date.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-10-25 00:37:36', '14', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-10-25 00:37:36', '14', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-10-25 00:37:36', '14', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinanceCharge%%%', '2007-10-25 00:37:36', '14', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-10-25 00:37:36', '14', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFunAmount%%%', '2007-10-25 00:37:36', '14', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-10-25 00:37:36', '14', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-10-25 00:37:36', '14', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanIntAccrued%%%', '2007-10-25 00:37:36', '14', 'Represents the amount of interest accrued from the beginning of the loan.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-10-25 00:37:36', '14', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanRefAmount%%%', '2007-10-25 00:37:36', '14', 'Loan Ref Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginID%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Make%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-10-25 00:37:36', '14', 'Missed Arrangement Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-10-25 00:37:36', '14', 'Missed Arrangement Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-10-25 00:37:36', '14', 'Missed Arrangement Type', '0', null);
INSERT INTO `tokens` VALUES ('%%%Model%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReference%%%', '2007-10-25 00:37:36', '14', 'MoneyGram Reference Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%NetLoanProceeds%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%NetProceedsAmount%%%', '2007-10-25 00:37:36', '14', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-10-25 00:37:36', '14', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-10-25 00:37:36', '14', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-10-25 00:37:36', '14', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentDate%%%', '2007-10-25 00:37:36', '14', 'Payment Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentPostedAmount%%%', '2007-10-25 00:37:36', '14', 'PaymentPostedAmount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReactLink%%%', '2007-10-25 00:37:36', '14', 'Link for reactivation loans.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-10-25 00:37:36', '14', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-10-25 00:37:36', '14', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-10-25 00:37:36', '14', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-10-25 00:37:36', '14', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-10-25 00:37:36', '14', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-10-25 00:37:36', '14', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-10-25 00:37:36', '14', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-10-25 00:37:36', '14', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SettlementOffer%%%', '2007-10-25 00:37:36', '14', 'Settlement Offer', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-10-25 00:37:36', '14', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-10-25 00:37:36', '14', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Suite%%%', '2007-10-25 00:37:36', '14', 'Company Suite Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Test%%%', '2007-10-25 00:37:36', '14', 'Please remove this token before going live', '0', null);
INSERT INTO `tokens` VALUES ('%%%TilteLienFee%%%', '2007-10-25 00:37:36', '14', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%Time%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TitleLienFee%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-10-25 00:37:36', '14', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-10-25 00:37:36', '14', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-10-25 00:37:36', '14', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%VehicleMileage%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%VIN%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%WireFee%%%', '2007-10-25 00:37:36', '14', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%WireTransferFee%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Year%%%', '2007-10-25 00:37:36', '14', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%YR%%%', '2007-10-25 00:37:36', '14', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDept%%%', '2007-10-25 00:38:55', '12', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyInit%%%', '2007-10-25 00:38:55', '12', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPromoID%%%', '2007-10-25 00:38:55', '12', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-10-25 00:38:55', '12', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCounty%%%', '2007-10-25 00:38:55', '12', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Day%%%', '2007-10-25 00:38:55', '12', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2007-10-25 00:38:55', '12', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanIntAccrued%%%', '2007-10-25 00:38:55', '12', 'Represents the amount of interest accrued from the beginning of the loan.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentPostedAmount%%%', '2007-10-25 00:38:55', '12', 'PaymentPostedAmount', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReactLink%%%', '2007-10-25 00:38:55', '12', 'Link for reactivation loans.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Suite%%%', '2007-10-25 00:38:55', '12', 'Company Suite Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%TitleLienFee%%%', '2007-10-25 00:38:55', '12', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%AccountRep%%%', '2007-10-25 01:00:16', '15', 'Account Representative', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-10-25 01:00:16', '15', 'Customer\'s bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-10-25 01:00:16', '15', 'Customer\'s bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccountType%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-10-25 01:00:16', '15', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-10-25 01:00:16', '15', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCounty%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDept%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-10-25 01:00:16', '15', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-10-25 01:00:16', '15', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyInit%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2007-10-25 01:00:16', '15', 'The name of the company.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-10-25 01:00:16', '15', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-10-25 01:00:16', '15', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-10-25 01:00:16', '15', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhonel%%%', '2007-10-25 01:00:16', '15', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPromoID%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-10-25 01:00:16', '15', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-10-25 01:00:16', '15', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-10-25 01:00:16', '15', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-10-25 01:00:16', '15', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-10-25 01:00:16', '15', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-10-25 01:00:16', '15', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCounty%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-10-25 01:00:16', '15', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-10-25 01:00:16', '15', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-10-25 01:00:16', '15', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-10-25 01:00:16', '15', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFullName%%%', '2007-10-25 01:00:16', '15', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-10-25 01:00:16', '15', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-10-25 01:00:16', '15', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-10-25 01:00:16', '15', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhone%%%', '2007-10-25 01:00:16', '15', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-10-25 01:00:16', '15', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-10-25 01:00:16', '15', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-10-25 01:00:16', '15', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-10-25 01:00:16', '15', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-10-25 01:00:16', '15', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-10-25 01:00:16', '15', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-10-25 01:00:16', '15', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-10-25 01:00:16', '15', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-10-25 01:00:16', '15', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-10-25 01:00:16', '15', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-10-25 01:00:16', '15', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-10-25 01:00:16', '15', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%Day%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DeliveryFee%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-10-25 01:00:16', '15', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-10-25 01:00:16', '15', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-10-25 01:00:16', '15', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-10-25 01:00:16', '15', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-10-25 01:00:16', '15', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-10-25 01:00:16', '15', 'Generic Message Text', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-10-25 01:00:16', '15', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-10-25 01:00:16', '15', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-10-25 01:00:16', '15', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-10-25 01:00:16', '15', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-10-25 01:00:16', '15', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-10-25 01:00:16', '15', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-10-25 01:00:16', '15', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-10-25 01:00:16', '15', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-10-25 01:00:16', '15', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-10-25 01:00:16', '15', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-10-25 01:00:16', '15', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-10-25 01:00:16', '15', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-10-25 01:00:16', '15', 'The current outstanding principal + the accrued interest as of today\'s date.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-10-25 01:00:16', '15', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-10-25 01:00:16', '15', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-10-25 01:00:16', '15', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinanceCharge%%%', '2007-10-25 01:00:16', '15', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-10-25 01:00:16', '15', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFunAmount%%%', '2007-10-25 01:00:16', '15', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-10-25 01:00:16', '15', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-10-25 01:00:16', '15', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanIntAccrued%%%', '2007-10-25 01:00:16', '15', 'Represents the amount of interest accrued from the beginning of the loan.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-10-25 01:00:16', '15', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanRefAmount%%%', '2007-10-25 01:00:16', '15', 'Loan Ref Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginID%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Make%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-10-25 01:00:16', '15', 'Missed Arrangement Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-10-25 01:00:16', '15', 'Missed Arrangement Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-10-25 01:00:16', '15', 'Missed Arrangement Type', '0', null);
INSERT INTO `tokens` VALUES ('%%%Model%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReference%%%', '2007-10-25 01:00:16', '15', 'MoneyGram Reference Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%NetLoanProceeds%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%NetProceedsAmount%%%', '2007-10-25 01:00:16', '15', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-10-25 01:00:16', '15', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-10-25 01:00:16', '15', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-10-25 01:00:16', '15', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentDate%%%', '2007-10-25 01:00:16', '15', 'Payment Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentPostedAmount%%%', '2007-10-25 01:00:16', '15', 'PaymentPostedAmount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReactLink%%%', '2007-10-25 01:00:16', '15', 'Link for reactivation loans.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-10-25 01:00:16', '15', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-10-25 01:00:16', '15', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-10-25 01:00:16', '15', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-10-25 01:00:16', '15', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-10-25 01:00:16', '15', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-10-25 01:00:16', '15', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-10-25 01:00:16', '15', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-10-25 01:00:16', '15', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SettlementOffer%%%', '2007-10-25 01:00:16', '15', 'Settlement Offer', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-10-25 01:00:16', '15', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-10-25 01:00:16', '15', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Suite%%%', '2007-10-25 01:00:16', '15', 'Company Suite Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Test%%%', '2007-10-25 01:00:16', '15', 'Please remove this token before going live', '0', null);
INSERT INTO `tokens` VALUES ('%%%TilteLienFee%%%', '2007-10-25 01:00:16', '15', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%Time%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TitleLienFee%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-10-25 01:00:16', '15', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-10-25 01:00:16', '15', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-10-25 01:00:16', '15', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%VehicleMileage%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%VIN%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%WireFee%%%', '2007-10-25 01:00:16', '15', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%WireTransferFee%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Year%%%', '2007-10-25 01:00:16', '15', 'New Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%YR%%%', '2007-10-25 01:00:16', '15', 'AutoGenerated', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox1%%%', '2007-10-25 19:44:37', '15', 'First checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox2%%%', '2007-10-25 19:44:37', '15', 'Second checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox3%%%', '2007-10-25 19:44:37', '15', 'Third checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox4%%%', '2007-10-25 19:44:37', '15', 'Fourth checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP1%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP2%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP3%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP4%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEPMilNo%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEPMilYes%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET1%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET2%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET3%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET4%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DETMilNo%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DETMilYes%%%', '2007-10-25 19:44:37', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MilitaryNo%%%', '2007-10-25 19:44:37', '15', 'Checkbox for answering no to the military question.', '0', null);
INSERT INTO `tokens` VALUES ('%%%MilitaryYes%%%', '2007-10-25 19:44:37', '15', 'Checkbox for answering yes to the military question.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CA1%%%', '2007-10-25 19:44:44', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CA2%%%', '2007-10-25 19:44:44', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CA3%%%', '2007-10-25 19:44:44', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CAMilNo%%%', '2007-10-25 19:44:44', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CAMilYes%%%', '2007-10-25 19:44:44', '15', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankABA%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankAccount%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankName%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankName%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankShort%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServName%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServPhone%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyCity%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDept%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCollections%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCustServ%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyEmail%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyFax%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyInit%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoLarge%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoSmall%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameLegal%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameShort%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPhone%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPromoID%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyState%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyStreet%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanySupportFax%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyUnit%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyWebSite%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyZip%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildConfirmLink%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerCity%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerDOB%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerEmail%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerESig%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerFax%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFirst%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFull%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameLast%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneCell%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneHome%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceLength%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceType%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart1%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart2%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart3%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerState%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStateID%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStreet%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerUnit%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerZip%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerLength%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerName%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerPhone%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerShift%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerTitle%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildeSigLink%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericEsigLink%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericMessage%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericSubject%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeDD%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeFrequency%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeMonthlyNet%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeNetPay%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate1%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate2%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate3%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate4%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeType%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanApplicationID%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanAPR%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanBalance%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCollectionCode%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrAPR%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrBalance%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrDueDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFees%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFinCharge%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrincipal%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrinPmnt%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDocDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDueDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFees%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFinCharge%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAmount%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAvail%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextAPR%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextBalance%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextDueDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFees%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFinCharge%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrincipal%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrinPmnt%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPayoffDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPrincipal%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanStatus%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoginId%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrAmount%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrType%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildNextBusinessDay%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPassword%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrAmount%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrType%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDAmount%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDDueDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDFinCharge%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextAmount%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextDueDate%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextFinCharge%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextTotal%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDTotal%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPrincipalPaymentAmount%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01NameFull%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01PhoneHome%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01Relationship%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02NameFull%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02PhoneHome%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02Relationship%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRefinanceAmount%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnFee%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnReason%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSenderName%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourcePromoID%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourceSiteName%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildToday%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildTotalOfPayments%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildUsername%%%', '2007-10-25 19:44:44', '15', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-10-25 19:44:44', '15', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-10-25 19:44:44', '15', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialInFull%%%', '2007-10-25 19:44:44', '15', 'Customer\'s initials for paying in full.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialPayDown%%%', '2007-10-25 19:44:44', '15', 'Customer\'s initials for pay downs.', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-10-25 19:44:44', '15', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-10-25 19:44:44', '15', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-10-25 19:44:44', '15', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-10-25 19:44:44', '15', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-10-25 19:44:44', '15', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-10-25 19:44:44', '15', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-10-25 19:44:44', '15', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-10-25 19:44:44', '15', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-10-25 19:44:44', '15', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-10-25 19:44:44', '15', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-10-25 19:44:44', '15', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-10-25 19:44:44', '15', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-10-25 19:44:44', '15', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-10-25 19:44:44', '15', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-10-25 19:44:44', '15', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-10-25 19:44:44', '15', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-10-25 19:44:44', '15', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2007-10-25 19:44:44', '15', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%CA1%%%', '2007-10-25 19:44:55', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CA2%%%', '2007-10-25 19:44:55', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CA3%%%', '2007-10-25 19:44:55', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CAMilNo%%%', '2007-10-25 19:44:55', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CAMilYes%%%', '2007-10-25 19:44:55', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox1%%%', '2007-10-25 19:44:55', '12', 'First checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox2%%%', '2007-10-25 19:44:55', '12', 'Second checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox3%%%', '2007-10-25 19:44:55', '12', 'Third checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox4%%%', '2007-10-25 19:44:55', '12', 'Fourth checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%MilitaryNo%%%', '2007-10-25 19:44:56', '12', 'Checkbox for answering no to the military question.', '0', null);
INSERT INTO `tokens` VALUES ('%%%MilitaryYes%%%', '2007-10-25 19:44:56', '12', 'Checkbox for answering yes to the military question.', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP1%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP2%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP3%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP4%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEPMilNo%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEPMilYes%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET1%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET2%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET3%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET4%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DETMilNo%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DETMilYes%%%', '2007-10-25 20:46:56', '12', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox1%%%', '2007-10-25 20:47:12', '14', 'First checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox2%%%', '2007-10-25 20:47:12', '14', 'Second checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox3%%%', '2007-10-25 20:47:12', '14', 'Third checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Checkbox4%%%', '2007-10-25 20:47:12', '14', 'Fourth checkbox in a document.', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP1%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP2%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP3%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEP4%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEPMilNo%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DEPMilYes%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET1%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET2%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET3%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DET4%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DETMilNo%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DETMilYes%%%', '2007-10-25 20:47:12', '14', 'OLP Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MilitaryNo%%%', '2007-10-25 20:47:12', '14', 'Checkbox for answering no to the military question.', '0', null);
INSERT INTO `tokens` VALUES ('%%%MilitaryYes%%%', '2007-10-25 20:47:12', '14', 'Checkbox for answering yes to the military question.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardNumber%%%', '2007-11-05 13:44:48', '10', 'Customer\'s Stored Value Card Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardName%%%', '2007-11-05 13:45:03', '10', 'The name of the company\'s stored value card.', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPassword%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrAmount%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrType%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDAmount%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDDueDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDFinCharge%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextAmount%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextDueDate%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextFinCharge%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextTotal%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDTotal%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPrincipalPaymentAmount%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01NameFull%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01PhoneHome%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01Relationship%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02NameFull%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02PhoneHome%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02Relationship%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRefinanceAmount%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnFee%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnReason%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSenderName%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourcePromoID%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourceSiteName%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildToday%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildTotalOfPayments%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildUsername%%%', '2007-11-07 12:07:29', '9', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-11-07 12:07:29', '9', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-11-07 12:07:29', '9', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-11-07 12:07:29', '9', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-11-07 12:07:29', '9', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-11-07 12:07:29', '9', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-11-07 12:07:29', '9', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-11-07 12:07:29', '9', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-11-07 12:07:29', '9', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-11-07 12:07:29', '9', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-11-07 12:07:29', '9', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-11-07 12:07:29', '9', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-11-07 12:07:29', '9', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-11-07 12:07:29', '9', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-11-07 12:07:29', '9', 'Company\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-11-07 12:07:29', '9', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-11-07 12:07:29', '9', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-11-07 12:07:29', '9', 'The link to use in confirming an application', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-11-07 12:07:29', '9', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-11-07 12:07:29', '9', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-11-07 12:07:29', '9', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-11-07 12:07:29', '9', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-11-07 12:07:29', '9', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialInFull%%%', '2007-11-07 12:07:29', '9', 'Customer\'s initials for paying in full.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialPayDown%%%', '2007-11-07 12:07:29', '9', 'Customer\'s initials for pay downs.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerIPAddress%%%', '2007-11-07 12:07:29', '9', 'The IP Address used to apply for the loan.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-11-07 12:07:29', '9', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-11-07 12:07:29', '9', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-11-07 12:07:29', '9', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-11-07 12:07:29', '9', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-11-07 12:07:29', '9', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-11-07 12:07:29', '9', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-11-07 12:07:29', '9', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-11-07 12:07:29', '9', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-11-07 12:07:29', '9', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-11-07 12:07:29', '9', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-11-07 12:07:29', '9', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-11-07 12:07:29', '9', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-11-07 12:07:29', '9', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-11-07 12:07:29', '9', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-11-07 12:07:29', '9', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-11-07 12:07:29', '9', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-11-07 12:07:29', '9', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-11-07 12:07:29', '9', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-11-07 12:07:29', '9', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-11-07 12:07:29', '9', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextApp%%%', '2007-11-07 12:07:29', '9', 'Display text for ESig line for the application section', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextAuth%%%', '2007-11-07 12:07:29', '9', 'Display text for ESig line for the authorization agreement', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextLoan%%%', '2007-11-07 12:07:29', '9', 'Display text for ESig line for the loan note and disclosure', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-11-07 12:07:29', '9', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-11-07 12:07:29', '9', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-11-07 12:07:29', '9', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-11-07 12:07:29', '9', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-11-07 12:07:29', '9', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-11-07 12:07:29', '9', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-11-07 12:07:29', '9', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-11-07 12:07:29', '9', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-11-07 12:07:29', '9', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-11-07 12:07:29', '9', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-11-07 12:07:29', '9', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-11-07 12:07:29', '9', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-11-07 12:07:29', '9', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-11-07 12:07:29', '9', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-11-07 12:07:29', '9', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-11-07 12:07:29', '9', 'The code assigned to the loan when it goes to collection. Set from company configuration.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-11-07 12:07:29', '9', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-11-07 12:07:29', '9', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-11-07 12:07:29', '9', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-11-07 12:07:29', '9', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-11-07 12:07:29', '9', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-11-07 12:07:29', '9', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-11-07 12:07:29', '9', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDateCreated%%%', '2007-11-07 12:07:29', '9', 'The date the application was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-11-07 12:07:29', '9', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-11-07 12:07:29', '9', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-11-07 12:07:29', '9', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-11-07 12:07:29', '9', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-11-07 12:07:29', '9', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-11-07 12:07:29', '9', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-11-07 12:07:29', '9', '(deprecated) The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-11-07 12:07:29', '9', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-11-07 12:07:29', '9', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-11-07 12:07:29', '9', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-11-07 12:07:29', '9', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-11-07 12:07:29', '9', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-11-07 12:07:29', '9', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-11-07 12:07:29', '9', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanTimeCreated%%%', '2007-11-07 12:07:29', '9', 'The time the application was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-11-07 12:07:29', '9', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-11-07 12:07:29', '9', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-11-07 12:07:29', '9', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-11-07 12:07:29', '9', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReceiveCode%%%', '2007-11-07 12:07:29', '9', 'Money Gram Receive Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-11-07 12:07:29', '9', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-11-07 12:07:29', '9', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-11-07 12:07:29', '9', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-11-07 12:07:29', '9', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-11-07 12:07:29', '9', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-11-07 12:07:29', '9', 'Paydown amount for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-11-07 12:07:29', '9', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-11-07 12:07:29', '9', 'Paydown Finance Charge for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-11-07 12:07:29', '9', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-11-07 12:07:29', '9', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-11-07 12:07:29', '9', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-11-07 12:07:29', '9', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-11-07 12:07:29', '9', 'Total current paydown amount (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2007-11-07 12:07:29', '9', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-11-07 12:07:29', '9', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-11-07 12:07:29', '9', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-11-07 12:07:29', '9', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-11-07 12:07:29', '9', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-11-07 12:07:29', '9', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-11-07 12:07:29', '9', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-11-07 12:07:29', '9', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-11-07 12:07:29', '9', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-11-07 12:07:29', '9', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-11-07 12:07:29', '9', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-11-07 12:07:29', '9', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-11-07 12:07:29', '9', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-11-07 12:07:29', '9', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-11-21 14:33:42', '16', 'Companys stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-11-21 14:33:42', '16', 'Companys stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-11-21 14:33:42', '16', 'Companys stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-11-21 14:33:42', '16', 'Companys stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-11-21 14:33:42', '16', 'Companyâ€™s city', '0', '54');
INSERT INTO `tokens` VALUES ('%%%CompanyDBA%%%', '2007-11-21 14:33:42', '16', 'Name company does business as', '0', '57');
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-11-21 14:33:42', '16', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-11-21 14:33:42', '16', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-11-21 14:33:42', '16', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-11-21 14:33:42', '16', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-11-21 14:33:42', '16', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-11-21 14:33:42', '16', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2007-11-21 14:33:42', '16', 'Company Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameFormal%%%', '2007-11-21 14:33:42', '16', 'Full Length Company Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-11-21 14:33:42', '16', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-11-21 14:33:42', '16', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-11-21 14:33:42', '16', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-11-21 14:33:42', '16', 'Companyâ€™s State', '0', '55');
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-11-21 14:33:42', '16', 'Companyâ€™s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-11-21 14:33:42', '16', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-11-21 14:33:42', '16', 'Companyâ€™s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-11-21 14:33:42', '16', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-11-21 14:33:42', '16', 'Companyâ€™s Zip Code', '0', '56');
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-11-21 14:33:42', '16', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-11-21 14:33:42', '16', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-11-21 14:33:42', '16', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-11-21 14:33:42', '16', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-11-21 14:33:42', '16', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-11-21 14:33:42', '16', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-11-21 14:33:42', '16', 'Customers home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-11-21 14:33:42', '16', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-11-21 14:33:42', '16', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-11-21 14:33:42', '16', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-11-21 14:33:42', '16', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-11-21 14:33:42', '16', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-11-21 14:33:42', '16', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%Day%%%', '2007-11-21 14:33:42', '16', 'The day of the week', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-11-21 14:33:42', '16', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-11-21 14:33:42', '16', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-11-21 14:33:42', '16', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-11-21 14:33:42', '16', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-11-21 14:33:42', '16', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-11-21 14:33:42', '16', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2007-11-21 14:33:42', '16', 'Generic ESig Link Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-11-21 14:33:42', '16', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-11-21 14:33:42', '16', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-11-21 14:33:42', '16', 'Customerâ€™s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-11-21 14:33:42', '16', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-11-21 14:33:42', '16', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-11-21 14:33:42', '16', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-11-21 14:33:42', '16', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-11-21 14:33:42', '16', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-11-21 14:33:42', '16', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-11-21 14:33:42', '16', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-11-21 14:33:42', '16', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-11-21 14:33:42', '16', 'Total due on the loan which consists of the outstanding principal, service charges, and fees', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-11-21 14:33:42', '16', 'The code assigned to the loan when it goes to collection', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-11-21 14:33:42', '16', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-11-21 14:33:42', '16', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-11-21 14:33:42', '16', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-11-21 14:33:42', '16', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-11-21 14:33:42', '16', 'The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-11-21 14:33:42', '16', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-11-21 14:33:42', '16', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-11-21 14:33:42', '16', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-11-21 14:33:42', '16', 'The due date of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-11-21 14:33:42', '16', 'Dollar amount of the credit cost', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundActionDate%%%', '2007-11-21 14:33:42', '16', 'The estimated date the funds are sent to the bank', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-11-21 14:33:42', '16', 'Amount financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-11-21 14:33:42', '16', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-11-21 14:33:42', '16', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-11-21 14:33:42', '16', 'The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDueDate%%%', '2007-11-21 14:33:42', '16', 'Based on the LoanFundDueDate, this is the date the funds are available to the customer.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-11-21 14:33:42', '16', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-11-21 14:33:42', '16', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-11-21 14:33:42', '16', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-11-21 14:33:42', '16', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-11-21 14:33:42', '16', 'Loan payoff date', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-11-21 14:33:42', '16', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-11-21 14:33:42', '16', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-11-21 14:33:42', '16', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-11-21 14:33:42', '16', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-11-21 14:33:42', '16', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-11-21 14:33:42', '16', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-11-21 14:33:42', '16', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-11-21 14:33:42', '16', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-11-21 14:33:42', '16', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-11-21 14:33:42', '16', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-11-21 14:33:42', '16', 'Paydown amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-11-21 14:33:42', '16', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-11-21 14:33:42', '16', 'Paydown Finance Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-11-21 14:33:42', '16', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-11-21 14:33:42', '16', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-11-21 14:33:42', '16', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-11-21 14:33:42', '16', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDPercent%%%', '2007-11-21 14:33:42', '16', 'Paydown percent amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-11-21 14:33:42', '16', 'Total of all Paydown amounts', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-11-21 14:33:42', '16', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-11-21 14:33:42', '16', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-11-21 14:33:42', '16', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-11-21 14:33:42', '16', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-11-21 14:33:42', '16', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-11-21 14:33:42', '16', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-11-21 14:33:42', '16', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-11-21 14:33:42', '16', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-11-21 14:33:42', '16', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-11-21 14:33:42', '16', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Time%%%', '2007-11-21 14:33:42', '16', 'The time of day', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-11-21 14:33:42', '16', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-11-21 14:33:42', '16', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-11-21 14:33:42', '16', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-11-28 09:13:52', '17', 'Customer\'s bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-11-28 09:13:52', '17', 'Customer\'s bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-11-28 09:13:52', '17', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardName%%%', '2007-11-28 09:13:52', '17', 'The name of the company\'s stored value card', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardNumber%%%', '2007-11-28 09:13:52', '17', 'The number of the customer\'s stored value card.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-11-28 09:13:52', '17', 'Company\'s stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-11-28 09:13:52', '17', 'Company\'s stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-11-28 09:13:52', '17', 'Company\'s stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-11-28 09:13:52', '17', 'Company\'s stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankABA%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankAccount%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankName%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankName%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankShort%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServName%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServPhone%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyCity%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDept%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCollections%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCustServ%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyEmail%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyFax%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyInit%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoLarge%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoSmall%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameLegal%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameShort%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPhone%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPromoID%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyState%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyStreet%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanySupportFax%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyUnit%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyWebSite%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyZip%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildConfirmLink%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerCity%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerDOB%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerEmail%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerESig%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerFax%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFirst%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFull%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameLast%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneCell%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneHome%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceLength%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceType%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart1%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart2%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart3%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerState%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStateID%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStreet%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerUnit%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerZip%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerLength%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerName%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerPhone%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerShift%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerTitle%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildeSigLink%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericEsigLink%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericMessage%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericSubject%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeDD%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeFrequency%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeMonthlyNet%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeNetPay%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate1%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate2%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate3%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate4%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeType%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanApplicationID%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanAPR%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanBalance%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCollectionCode%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrAPR%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrBalance%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrDueDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFees%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFinCharge%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrincipal%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrinPmnt%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDocDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDueDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFees%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFinCharge%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAmount%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAvail%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextAPR%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextBalance%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextDueDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFees%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFinCharge%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrincipal%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrinPmnt%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPayoffDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPrincipal%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanStatus%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoginId%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrAmount%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrType%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildNextBusinessDay%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPassword%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrAmount%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrType%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDAmount%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDDueDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDFinCharge%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextAmount%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextDueDate%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextFinCharge%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextTotal%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDTotal%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPrincipalPaymentAmount%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01NameFull%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01PhoneHome%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01Relationship%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02NameFull%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02PhoneHome%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02Relationship%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRefinanceAmount%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnFee%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnReason%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSenderName%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourcePromoID%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourceSiteName%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildToday%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildTotalOfPayments%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildUsername%%%', '2007-11-28 09:13:52', '17', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-11-28 09:13:52', '17', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-11-28 09:13:52', '17', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-11-28 09:13:52', '17', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-11-28 09:13:52', '17', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-11-28 09:13:52', '17', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-11-28 09:13:52', '17', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-11-28 09:13:52', '17', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-11-28 09:13:52', '17', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-11-28 09:13:52', '17', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-11-28 09:13:52', '17', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-11-28 09:13:52', '17', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-11-28 09:13:52', '17', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-11-28 09:13:52', '17', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-11-28 09:13:52', '17', 'Company\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-11-28 09:13:52', '17', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-11-28 09:13:52', '17', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-11-28 09:13:52', '17', 'The link to use in confirming an application', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-11-28 09:13:52', '17', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-11-28 09:13:52', '17', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-11-28 09:13:52', '17', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-11-28 09:13:52', '17', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-11-28 09:13:52', '17', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialInFull%%%', '2007-11-28 09:13:52', '17', 'Customer\'s initials for paying in full.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialPayDown%%%', '2007-11-28 09:13:52', '17', 'Customer\'s initials for pay downs.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerIPAddress%%%', '2007-11-28 09:13:52', '17', 'The IP Address used to apply for the loan.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-11-28 09:13:52', '17', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-11-28 09:13:52', '17', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-11-28 09:13:52', '17', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-11-28 09:13:52', '17', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-11-28 09:13:52', '17', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-11-28 09:13:52', '17', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-11-28 09:13:52', '17', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-11-28 09:13:52', '17', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-11-28 09:13:52', '17', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-11-28 09:13:52', '17', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-11-28 09:13:52', '17', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-11-28 09:13:52', '17', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-11-28 09:13:52', '17', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-11-28 09:13:52', '17', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-11-28 09:13:52', '17', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-11-28 09:13:52', '17', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-11-28 09:13:52', '17', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-11-28 09:13:52', '17', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-11-28 09:13:52', '17', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-11-28 09:13:52', '17', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextApp%%%', '2007-11-28 09:13:52', '17', 'Display text for ESig line for the application section', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextAuth%%%', '2007-11-28 09:13:52', '17', 'Display text for ESig line for the authorization agreement', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextLoan%%%', '2007-11-28 09:13:52', '17', 'Display text for ESig line for the loan note and disclosure', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-11-28 09:13:52', '17', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-11-28 09:13:52', '17', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-11-28 09:13:52', '17', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-11-28 09:13:53', '17', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-11-28 09:13:53', '17', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-11-28 09:13:53', '17', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-11-28 09:13:53', '17', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-11-28 09:13:53', '17', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-11-28 09:13:53', '17', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-11-28 09:13:53', '17', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-11-28 09:13:53', '17', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-11-28 09:13:53', '17', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-11-28 09:13:53', '17', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-11-28 09:13:53', '17', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-11-28 09:13:53', '17', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-11-28 09:13:53', '17', 'The code assigned to the loan when it goes to collection. Set from company configuration.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-11-28 09:13:53', '17', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-11-28 09:13:53', '17', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-11-28 09:13:53', '17', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-11-28 09:13:53', '17', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-11-28 09:13:53', '17', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-11-28 09:13:53', '17', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-11-28 09:13:53', '17', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDateCreated%%%', '2007-11-28 09:13:53', '17', 'The date the application was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-11-28 09:13:53', '17', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-11-28 09:13:53', '17', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-11-28 09:13:53', '17', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-11-28 09:13:53', '17', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-11-28 09:13:53', '17', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-11-28 09:13:53', '17', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-11-28 09:13:53', '17', '(deprecated) The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-11-28 09:13:53', '17', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-11-28 09:13:53', '17', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-11-28 09:13:53', '17', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-11-28 09:13:53', '17', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-11-28 09:13:53', '17', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-11-28 09:13:53', '17', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-11-28 09:13:53', '17', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanTimeCreated%%%', '2007-11-28 09:13:53', '17', 'The time the application was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-11-28 09:13:53', '17', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-11-28 09:13:53', '17', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-11-28 09:13:53', '17', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-11-28 09:13:53', '17', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReceiveCode%%%', '2007-11-28 09:13:53', '17', 'Money Gram Receive Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-11-28 09:13:53', '17', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-11-28 09:13:53', '17', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-11-28 09:13:53', '17', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-11-28 09:13:53', '17', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-11-28 09:13:53', '17', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-11-28 09:13:53', '17', 'Paydown amount for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-11-28 09:13:53', '17', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-11-28 09:13:53', '17', 'Paydown Finance Charge for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-11-28 09:13:53', '17', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-11-28 09:13:53', '17', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-11-28 09:13:53', '17', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-11-28 09:13:53', '17', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-11-28 09:13:53', '17', 'Total current paydown amount (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2007-11-28 09:13:53', '17', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-11-28 09:13:53', '17', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-11-28 09:13:53', '17', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-11-28 09:13:53', '17', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-11-28 09:13:53', '17', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-11-28 09:13:53', '17', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-11-28 09:13:53', '17', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-11-28 09:13:53', '17', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-11-28 09:13:53', '17', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-11-28 09:13:53', '17', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-11-28 09:13:53', '17', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-11-28 09:13:53', '17', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-11-28 09:13:53', '17', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-11-28 09:13:53', '17', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-11-28 20:21:29', '7', 'Confirmation Link', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDateCreated%%%', '2007-11-28 20:21:52', '7', 'Date the loan was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanTimeCreated%%%', '2007-11-28 20:22:21', '7', 'The time the loan was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerIPAddress%%%', '2007-11-28 20:23:02', '7', 'The IP Address the customer used when applying', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-11-28 20:36:38', '8', 'Confirmation Link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerIPAddress%%%', '2007-11-28 20:36:58', '8', 'The IP Address the customer used when applying', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDateCreated%%%', '2007-11-28 20:37:31', '8', 'Date the loan was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanTimeCreated%%%', '2007-11-28 20:37:44', '8', 'The time the loan was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-11-28 20:40:34', '11', 'Confirmation Link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerIPAddress%%%', '2007-11-28 20:40:48', '11', 'The IP Address the customer used when applying', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDateCreated%%%', '2007-11-28 20:40:58', '11', 'The time the loan was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanTimeCreated%%%', '2007-11-28 20:41:21', '11', 'The time the loan was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-11-28 20:42:22', '10', 'Confirmation Link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerIPAddress%%%', '2007-11-28 20:42:31', '10', 'The IP Address the customer used when applying', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDateCreated%%%', '2007-11-28 20:42:41', '10', 'Date the loan was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanTimeCreated%%%', '2007-11-28 20:42:52', '10', 'The time the loan was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-12-05 23:20:41', '18', 'Customers bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-12-05 23:20:41', '18', 'Customers bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-12-05 23:20:41', '18', 'Customers bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-12-05 23:20:41', '18', 'Companys stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-12-05 23:20:41', '18', 'Companys stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-12-05 23:20:41', '18', 'Companys stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-12-05 23:20:41', '18', 'Companys stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-12-05 23:20:41', '18', 'Companys city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-12-05 23:20:41', '18', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-12-05 23:20:41', '18', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-12-05 23:20:41', '18', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-12-05 23:20:41', '18', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyInitials%%%', '2007-12-05 23:20:41', '18', 'Company\'s initials', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-12-05 23:20:41', '18', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-12-05 23:20:41', '18', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-12-05 23:20:41', '18', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-12-05 23:20:41', '18', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-12-05 23:20:41', '18', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-12-05 23:20:41', '18', 'Companys State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-12-05 23:20:41', '18', 'Companys street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-12-05 23:20:41', '18', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-12-05 23:20:41', '18', 'Companys unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-12-05 23:20:41', '18', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-12-05 23:20:41', '18', 'Companys Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-12-05 23:20:41', '18', 'Customers city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-12-05 23:20:41', '18', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-12-05 23:20:41', '18', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-12-05 23:20:41', '18', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-12-05 23:20:41', '18', 'Customers fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-12-05 23:20:41', '18', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-12-05 23:20:41', '18', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-12-05 23:20:41', '18', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-12-05 23:20:41', '18', 'Customers cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-12-05 23:20:41', '18', 'Customers home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-12-05 23:20:41', '18', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-12-05 23:20:41', '18', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-12-05 23:20:41', '18', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-12-05 23:20:41', '18', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-12-05 23:20:41', '18', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-12-05 23:20:41', '18', 'Customers State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-12-05 23:20:41', '18', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-12-05 23:20:41', '18', 'Customers street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-12-05 23:20:41', '18', 'Customers unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-12-05 23:20:41', '18', 'Customers Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%Day%%%', '2007-12-05 23:20:41', '18', 'The day of the week', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-12-05 23:20:41', '18', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-12-05 23:20:41', '18', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-12-05 23:20:41', '18', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-12-05 23:20:41', '18', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-12-05 23:20:41', '18', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-12-05 23:20:41', '18', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2007-12-05 23:20:41', '18', 'Link to eSig page', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-12-05 23:20:41', '18', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-12-05 23:20:41', '18', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-12-05 23:20:41', '18', 'Customers net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-12-05 23:20:41', '18', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-12-05 23:20:41', '18', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-12-05 23:20:41', '18', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-12-05 23:20:41', '18', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-12-05 23:20:41', '18', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-12-05 23:20:41', '18', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-12-05 23:20:41', '18', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-12-05 23:20:41', '18', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-12-05 23:20:41', '18', 'Total due on the loan which consists of the outstanding principal, service charges, and fees', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-12-05 23:20:41', '18', 'The code assigned to the loan when it goes to collection', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-12-05 23:20:41', '18', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-12-05 23:20:41', '18', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-12-05 23:20:41', '18', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-12-05 23:20:41', '18', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-12-05 23:20:41', '18', 'The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-12-05 23:20:41', '18', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-12-05 23:20:41', '18', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-12-05 23:20:41', '18', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-12-05 23:20:41', '18', 'The due date of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-12-05 23:20:41', '18', 'Dollar amount of the credit cost', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundActionDate%%%', '2007-12-05 23:20:41', '18', 'The estimated date the funds are sent to the bank', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-12-05 23:20:41', '18', 'Amount financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-12-05 23:20:41', '18', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-12-05 23:20:41', '18', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-12-05 23:20:41', '18', 'The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDueDate%%%', '2007-12-05 23:20:41', '18', 'Based on the LoanFundDueDate, this is the date the funds are available to the customer.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-12-05 23:20:41', '18', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-12-05 23:20:41', '18', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-12-05 23:20:41', '18', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-12-05 23:20:41', '18', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-12-05 23:20:41', '18', 'Loan payoff date', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-12-05 23:20:41', '18', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-12-05 23:20:41', '18', 'The status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-12-05 23:20:41', '18', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-12-05 23:20:41', '18', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-12-05 23:20:41', '18', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-12-05 23:20:41', '18', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-12-05 23:20:41', '18', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-12-05 23:20:41', '18', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-12-05 23:20:41', '18', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-12-05 23:20:41', '18', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-12-05 23:20:41', '18', 'Paydown amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-12-05 23:20:41', '18', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-12-05 23:20:41', '18', 'Paydown Finance Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-12-05 23:20:41', '18', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-12-05 23:20:41', '18', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-12-05 23:20:41', '18', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-12-05 23:20:41', '18', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-12-05 23:20:41', '18', 'Total of all Paydown amounts', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-12-05 23:20:41', '18', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-12-05 23:20:41', '18', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-12-05 23:20:41', '18', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-12-05 23:20:41', '18', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-12-05 23:20:41', '18', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-12-05 23:20:41', '18', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-12-05 23:20:41', '18', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-12-05 23:20:41', '18', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-12-05 23:20:41', '18', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-12-05 23:20:41', '18', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Time%%%', '2007-12-05 23:20:41', '18', 'The time of day', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-12-05 23:20:41', '18', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-12-05 23:20:41', '18', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-12-05 23:20:41', '18', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-12-18 16:03:33', '19', 'Customers bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-12-18 16:03:33', '19', 'Customers bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-12-18 16:03:33', '19', 'Customers bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-12-18 16:03:33', '19', 'Companys stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-12-18 16:03:33', '19', 'Companys stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-12-18 16:03:33', '19', 'Companys stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-12-18 16:03:33', '19', 'Companys stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-12-18 16:03:33', '19', 'Companys city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-12-18 16:03:33', '19', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-12-18 16:03:33', '19', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-12-18 16:03:33', '19', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-12-18 16:03:33', '19', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyInitials%%%', '2007-12-18 16:03:33', '19', 'Company\'s initials', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-12-18 16:03:33', '19', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-12-18 16:03:33', '19', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-12-18 16:03:33', '19', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-12-18 16:03:33', '19', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-12-18 16:03:33', '19', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-12-18 16:03:33', '19', 'Companys State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-12-18 16:03:33', '19', 'Companys street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-12-18 16:03:33', '19', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-12-18 16:03:33', '19', 'Companys unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-12-18 16:03:33', '19', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-12-18 16:03:33', '19', 'Companys Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-12-18 16:03:33', '19', 'Customers city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-12-18 16:03:33', '19', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-12-18 16:03:33', '19', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-12-18 16:03:33', '19', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-12-18 16:03:33', '19', 'Customers fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-12-18 16:03:33', '19', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-12-18 16:03:33', '19', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-12-18 16:03:33', '19', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-12-18 16:03:33', '19', 'Customers cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-12-18 16:03:33', '19', 'Customers home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-12-18 16:03:33', '19', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-12-18 16:03:33', '19', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-12-18 16:03:33', '19', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-12-18 16:03:33', '19', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-12-18 16:03:33', '19', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-12-18 16:03:33', '19', 'Customers State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-12-18 16:03:33', '19', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-12-18 16:03:33', '19', 'Customers street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-12-18 16:03:33', '19', 'Customers unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-12-18 16:03:33', '19', 'Customers Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%Day%%%', '2007-12-18 16:03:33', '19', 'The day of the week', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-12-18 16:03:33', '19', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-12-18 16:03:33', '19', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-12-18 16:03:33', '19', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-12-18 16:03:33', '19', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-12-18 16:03:33', '19', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-12-18 16:03:33', '19', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2007-12-18 16:03:33', '19', 'Link to eSig page', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-12-18 16:03:33', '19', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-12-18 16:03:33', '19', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-12-18 16:03:33', '19', 'Customers net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-12-18 16:03:33', '19', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-12-18 16:03:33', '19', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-12-18 16:03:33', '19', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-12-18 16:03:33', '19', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-12-18 16:03:33', '19', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-12-18 16:03:33', '19', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-12-18 16:03:33', '19', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-12-18 16:03:33', '19', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-12-18 16:03:33', '19', 'Total due on the loan which consists of the outstanding principal, service charges, and fees', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-12-18 16:03:33', '19', 'The code assigned to the loan when it goes to collection', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-12-18 16:03:33', '19', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-12-18 16:03:33', '19', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-12-18 16:03:33', '19', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-12-18 16:03:33', '19', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-12-18 16:03:33', '19', 'The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-12-18 16:03:33', '19', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-12-18 16:03:33', '19', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-12-18 16:03:33', '19', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-12-18 16:03:33', '19', 'The due date of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-12-18 16:03:33', '19', 'Dollar amount of the credit cost', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundActionDate%%%', '2007-12-18 16:03:33', '19', 'The estimated date the funds are sent to the bank', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-12-18 16:03:33', '19', 'Amount financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-12-18 16:03:33', '19', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-12-18 16:03:33', '19', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-12-18 16:03:33', '19', 'The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDueDate%%%', '2007-12-18 16:03:33', '19', 'Based on the LoanFundDueDate, this is the date the funds are available to the customer.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-12-18 16:03:33', '19', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-12-18 16:03:33', '19', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-12-18 16:03:33', '19', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-12-18 16:03:33', '19', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-12-18 16:03:33', '19', 'Loan payoff date', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-12-18 16:03:33', '19', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-12-18 16:03:33', '19', 'The status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-12-18 16:03:33', '19', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-12-18 16:03:33', '19', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-12-18 16:03:33', '19', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-12-18 16:03:33', '19', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-12-18 16:03:33', '19', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-12-18 16:03:33', '19', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-12-18 16:03:33', '19', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-12-18 16:03:33', '19', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-12-18 16:03:33', '19', 'Paydown amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-12-18 16:03:33', '19', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-12-18 16:03:33', '19', 'Paydown Finance Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-12-18 16:03:33', '19', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-12-18 16:03:33', '19', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-12-18 16:03:33', '19', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-12-18 16:03:33', '19', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-12-18 16:03:33', '19', 'Total of all Paydown amounts', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-12-18 16:03:33', '19', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-12-18 16:03:33', '19', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-12-18 16:03:33', '19', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-12-18 16:03:33', '19', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-12-18 16:03:33', '19', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-12-18 16:03:33', '19', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-12-18 16:03:33', '19', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-12-18 16:03:33', '19', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-12-18 16:03:33', '19', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-12-18 16:03:33', '19', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Time%%%', '2007-12-18 16:03:33', '19', 'The time of day', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-12-18 16:03:33', '19', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-12-18 16:03:33', '19', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-12-18 16:03:33', '19', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-12-18 16:03:38', '20', 'Customers bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-12-18 16:03:38', '20', 'Customers bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-12-18 16:03:38', '20', 'Customers bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-12-18 16:03:38', '20', 'Companys stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-12-18 16:03:38', '20', 'Companys stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-12-18 16:03:38', '20', 'Companys stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-12-18 16:03:38', '20', 'Companys stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-12-18 16:03:38', '20', 'Companys city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-12-18 16:03:38', '20', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-12-18 16:03:38', '20', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-12-18 16:03:38', '20', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-12-18 16:03:38', '20', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyInitials%%%', '2007-12-18 16:03:38', '20', 'Company\'s initials', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-12-18 16:03:38', '20', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-12-18 16:03:38', '20', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-12-18 16:03:38', '20', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-12-18 16:03:38', '20', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-12-18 16:03:38', '20', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-12-18 16:03:38', '20', 'Companys State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-12-18 16:03:38', '20', 'Companys street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-12-18 16:03:38', '20', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-12-18 16:03:38', '20', 'Companys unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-12-18 16:03:38', '20', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-12-18 16:03:38', '20', 'Companys Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-12-18 16:03:38', '20', 'Customers city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-12-18 16:03:38', '20', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-12-18 16:03:38', '20', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-12-18 16:03:38', '20', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-12-18 16:03:38', '20', 'Customers fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-12-18 16:03:38', '20', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-12-18 16:03:38', '20', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-12-18 16:03:38', '20', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-12-18 16:03:38', '20', 'Customers cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-12-18 16:03:38', '20', 'Customers home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-12-18 16:03:38', '20', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-12-18 16:03:38', '20', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-12-18 16:03:38', '20', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-12-18 16:03:38', '20', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-12-18 16:03:38', '20', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-12-18 16:03:38', '20', 'Customers State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-12-18 16:03:38', '20', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-12-18 16:03:38', '20', 'Customers street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-12-18 16:03:38', '20', 'Customers unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-12-18 16:03:38', '20', 'Customers Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%Day%%%', '2007-12-18 16:03:38', '20', 'The day of the week', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-12-18 16:03:38', '20', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-12-18 16:03:38', '20', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-12-18 16:03:38', '20', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-12-18 16:03:38', '20', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-12-18 16:03:38', '20', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-12-18 16:03:38', '20', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2007-12-18 16:03:38', '20', 'Link to eSig page', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-12-18 16:03:38', '20', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-12-18 16:03:38', '20', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-12-18 16:03:38', '20', 'Customers net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-12-18 16:03:38', '20', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-12-18 16:03:38', '20', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-12-18 16:03:38', '20', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-12-18 16:03:38', '20', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-12-18 16:03:38', '20', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-12-18 16:03:38', '20', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-12-18 16:03:38', '20', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-12-18 16:03:38', '20', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-12-18 16:03:38', '20', 'Total due on the loan which consists of the outstanding principal, service charges, and fees', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-12-18 16:03:38', '20', 'The code assigned to the loan when it goes to collection', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-12-18 16:03:38', '20', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-12-18 16:03:38', '20', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-12-18 16:03:38', '20', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-12-18 16:03:38', '20', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-12-18 16:03:38', '20', 'The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-12-18 16:03:38', '20', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-12-18 16:03:38', '20', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-12-18 16:03:38', '20', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-12-18 16:03:38', '20', 'The due date of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-12-18 16:03:38', '20', 'Dollar amount of the credit cost', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundActionDate%%%', '2007-12-18 16:03:38', '20', 'The estimated date the funds are sent to the bank', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-12-18 16:03:38', '20', 'Amount financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-12-18 16:03:38', '20', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-12-18 16:03:38', '20', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-12-18 16:03:38', '20', 'The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDueDate%%%', '2007-12-18 16:03:38', '20', 'Based on the LoanFundDueDate, this is the date the funds are available to the customer.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-12-18 16:03:38', '20', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-12-18 16:03:38', '20', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-12-18 16:03:38', '20', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-12-18 16:03:38', '20', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-12-18 16:03:38', '20', 'Loan payoff date', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-12-18 16:03:38', '20', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-12-18 16:03:38', '20', 'The status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-12-18 16:03:38', '20', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-12-18 16:03:38', '20', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-12-18 16:03:38', '20', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-12-18 16:03:38', '20', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-12-18 16:03:38', '20', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-12-18 16:03:38', '20', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-12-18 16:03:38', '20', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-12-18 16:03:38', '20', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-12-18 16:03:38', '20', 'Paydown amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-12-18 16:03:38', '20', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-12-18 16:03:38', '20', 'Paydown Finance Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-12-18 16:03:38', '20', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-12-18 16:03:38', '20', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-12-18 16:03:38', '20', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-12-18 16:03:38', '20', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-12-18 16:03:38', '20', 'Total of all Paydown amounts', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-12-18 16:03:38', '20', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-12-18 16:03:38', '20', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-12-18 16:03:38', '20', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-12-18 16:03:38', '20', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-12-18 16:03:38', '20', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-12-18 16:03:38', '20', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-12-18 16:03:38', '20', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-12-18 16:03:38', '20', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-12-18 16:03:38', '20', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-12-18 16:03:38', '20', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Time%%%', '2007-12-18 16:03:38', '20', 'The time of day', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-12-18 16:03:38', '20', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-12-18 16:03:38', '20', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-12-18 16:03:38', '20', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReceiveCode%%%', '2008-02-11 15:00:08', '10', 'MoneyGramReceiveCode', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundActionDate%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDueDate%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2008-03-25 07:50:59', '21', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundActionDate%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDueDate%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2008-03-25 07:51:01', '22', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DATE%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TIME%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%NAME_FIRST%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%NAME_LAST%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ADDRESS%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CITY%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%STATE%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ZIP%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PHONE_HOME%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PHONE_WORK%%%', '2008-04-08 10:22:03', '23', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ACE_LOGO%%%', '2008-04-18 15:57:32', '23', 'Token for Ace Cash Logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%PW_LOGO%%%', '2008-04-18 15:57:47', '23', 'Token for Partner Weekly Logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2008-04-21 12:13:40', '21', 'Generic token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2008-04-21 12:13:59', '21', 'Generic Subject', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundActionDate%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDueDate%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2008-04-23 17:25:23', '24', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2008-06-03 10:07:51', '22', 'Payment Type Missed', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustEmail%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustFax%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustPhone%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollEmail%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollPhone%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientPhone%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientFax%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientEmail%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDIncrement%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeDays%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSLoginLink%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeTime%%%', '2008-07-08 14:19:18', '25', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptFaxCollections%%%', '2008-07-25 16:07:20', '17', 'Company\'s collections fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmailCollections%%%', '2008-07-25 16:09:26', '17', 'Company\'s collections email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2008-08-04 09:03:00', '18', 'Generic Message - Required for responding to email queue', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2008-08-04 09:03:25', '18', 'Generic Subject - Required for responding to Email queue', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSLoginLink%%%', '2008-09-30 16:24:10', '17', 'The link to where the applicant can log into the Customer Service site.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2008-10-21 14:18:59', '10', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2008-10-21 14:20:52', '8', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2008-10-21 14:21:53', '11', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeDays%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDIncrement%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustEmail%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustFax%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientEmail%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientFax%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollEmail%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollPhone%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientPhone%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSLoginLink%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustPhone%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSOLenderNameLegal%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeTime%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSOLenderInterest%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSOBrokerFee%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCancellationDelay%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCancellationDate%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSOTotalFinanceCost%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSOTotalOfPayments%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSOLenderLateFee%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSOLenderACHReturnFee%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSOBrokerFeePercent%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2008-11-06 20:36:36', '34', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDIncrement%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustEmail%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustPhone%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustFax%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeDays%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientEmail%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientPhone%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientFax%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSLoginLink%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollPhone%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollEmail%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeTime%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2008-11-20 21:10:33', '35', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%DATE%%%', '2008-12-18 16:08:12', '37', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TIME%%%', '2008-12-18 16:08:12', '37', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%NAME_FIRST%%%', '2008-12-18 16:08:12', '37', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%NAME_LAST%%%', '2008-12-18 16:08:12', '37', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EMAIL%%%', '2008-12-18 16:08:12', '37', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PHONE_HOME%%%', '2008-12-18 16:08:12', '37', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PHONE_WORK%%%', '2008-12-18 16:08:12', '37', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReactLink%%%', '2008-12-22 15:32:09', '25', 'URl for Reacts', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientEmail%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientPhone%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyClientFax%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSLoginLink%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%HoursOfOperation%%%', '2009-01-06 16:11:00', '17', 'Hours Of Operation string', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFirstPendingDate%%%', '2009-01-06 16:55:11', '17', 'Date of the first pending payment', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFirstPendingAmount%%%', '2009-01-06 16:55:39', '17', 'Amount of the first pending payment.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalName%%%', '2009-01-21 16:45:21', '17', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingBalance%%%', '2009-01-21 16:45:56', '17', 'The last pending balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingDueDate%%%', '2009-01-21 16:46:30', '17', 'The last pending due date', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalName%%%', '2009-01-21 16:47:41', '10', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingBalance%%%', '2009-01-21 16:48:10', '10', 'The last pending balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingDueDate%%%', '2009-01-21 16:48:31', '10', 'The last pending due date', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalName%%%', '2009-01-21 16:55:15', '8', 'The company legal name', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingBalance%%%', '2009-01-21 16:55:44', '8', 'The last pending balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingDueDate%%%', '2009-01-21 16:56:10', '8', 'The last pending due date', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalName%%%', '2009-01-21 16:57:45', '7', 'The company legal name', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingBalance%%%', '2009-01-21 16:58:15', '7', 'The last pending balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingDueDate%%%', '2009-01-21 16:58:38', '7', 'The last pending due date', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalName%%%', '2009-01-21 16:59:22', '9', 'The company legal name', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingBalance%%%', '2009-01-21 16:59:45', '9', 'The last pending balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingDueDate%%%', '2009-01-21 17:00:09', '9', 'The last pending due date', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalName%%%', '2009-01-21 17:00:56', '11', 'The company legal name', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingBalance%%%', '2009-01-21 17:01:17', '11', 'The last pending balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingDueDate%%%', '2009-01-21 17:01:41', '11', 'The last pending due date', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSLoginLink%%%', '2009-02-06 14:48:42', '16', 'Customer Service Reset Password Login Link', '0', '53');
INSERT INTO `tokens` VALUES ('%%%ATM_Locator%%%', '2009-03-03 16:05:04', '10', 'Token for Approval Terms Letter', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPaymentStreet%%%', '2009-03-13 13:17:53', '25', 'Manual Payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPaymentCity%%%', '2009-03-13 13:18:12', '25', 'Manual Payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPaymentState%%%', '2009-03-13 13:18:32', '25', 'Manual Payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPaymentZip%%%', '2009-03-13 13:18:48', '25', 'Manual Payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2009-03-13 15:21:07', '25', '*', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2009-03-13 15:21:19', '25', '*', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPaymentBank%%%', '2009-03-13 15:21:34', '25', 'Manual Payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPaymentABA%%%', '2009-03-13 15:21:47', '25', 'Manual Payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPaymentAccount%%%', '2009-03-13 15:21:58', '25', 'Manual Payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2009-03-13 15:22:16', '25', 'Legal Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCoBorrower%%%', '2009-04-08 14:14:57', '14', 'Title loan Co-borrowers Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCoBorrower%%%', '2009-04-20 09:11:28', '15', 'Title loan Co-borrowers Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustPhone%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustEmail%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustFax%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReactLink%%%', '2009-05-22 08:00:24', '33', 'React URL', '0', null);
INSERT INTO `tokens` VALUES ('%%%DNOCompany%%%', '2009-06-09 16:29:11', '17', 'DNO Company', '0', null);
INSERT INTO `tokens` VALUES ('%%%DNOCompanyStreet%%%', '2009-06-09 16:29:29', '17', 'Dno Company Street', '0', null);
INSERT INTO `tokens` VALUES ('%%%DNOCompanyCity%%%', '2009-06-09 16:30:30', '17', 'Dno Company City', '0', null);
INSERT INTO `tokens` VALUES ('%%%DNOCompanyState%%%', '2009-06-09 16:30:47', '17', 'Dno Company State', '0', null);
INSERT INTO `tokens` VALUES ('%%%DNOCompanyZip%%%', '2009-06-09 16:32:22', '17', 'Dno Company Zip', '0', null);
INSERT INTO `tokens` VALUES ('%%%DNOCompanyDebtPhoneCollections%%%', '2009-06-09 16:32:38', '17', 'DNOCompanyDebtPhoneCollections', '0', null);
INSERT INTO `tokens` VALUES ('%%%DNOCompanyFax%%%', '2009-06-09 16:32:49', '17', 'DNOCompanyFax', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalDepartmentEmail%%%', '2009-06-09 16:34:58', '17', 'CompanyLegalDepartmentEmail', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalDepartmentFax%%%', '2009-06-09 16:35:10', '17', 'CompanyLegalDepartmentFax', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSLoginLink%%%', '2009-06-10 13:19:22', '15', 'Cusomer Login Link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSLoginLink%%%', '2009-06-10 14:03:57', '14', 'Customer Login Link', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDocumentsLink%%%', '2009-06-16 14:50:19', '17', 'ESigDocumentsLink', '0', null);
INSERT INTO `tokens` VALUES ('%%%AccountSummaryOptionBlock%%%', '2009-06-16 15:22:09', '17', 'AccountSummaryOptionBlock', '0', null);
INSERT INTO `tokens` VALUES ('%%%FaxInstructions%%%', '2009-06-16 16:07:47', '17', 'FaxInstructions', '0', null);
INSERT INTO `tokens` VALUES ('%%%FaxOrDeliverLNaD%%%', '2009-06-16 16:11:20', '17', 'FaxOrDeliverLNaD', '0', null);
INSERT INTO `tokens` VALUES ('%%%SignAboveOrFax%%%', '2009-06-16 16:11:30', '17', 'SignAboveOrFax', '0', null);
INSERT INTO `tokens` VALUES ('%%%SignAndFaxTo%%%', '2009-06-16 16:11:40', '17', 'SignAndFaxTo', '0', null);
INSERT INTO `tokens` VALUES ('%%%FaxInstructions%%%', '2009-06-16 16:14:05', '10', 'FaxInstructions', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextApp%%%', '2009-07-08 17:07:43', '10', 'ESigDisplayTextApp', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextLoan%%%', '2009-07-08 17:07:54', '10', 'ESigDisplayTextLoan', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextAuth%%%', '2009-07-08 17:08:03', '10', 'ESigDisplayTextAuth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDIncrement%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeDays%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameFormal%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeTime%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollPhone%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollEmail%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2009-08-05 13:27:43', '33', 'Auto Generated Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2009-08-06 12:39:45', '2', 'Company Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2009-08-06 12:40:50', '2', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeDays%%%', '2009-08-06 12:41:08', '2', 'Loan Notice Days', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDIncrement%%%', '2009-08-06 12:41:23', '2', 'PD Increment', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustEmail%%%', '2009-08-06 12:41:49', '2', 'Company Cust Email', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustFax%%%', '2009-08-06 12:42:18', '2', 'Company Cust Fax', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2009-08-06 12:46:41', '18', 'Company Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2009-08-06 12:47:07', '18', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeDays%%%', '2009-08-06 12:47:25', '18', 'Loan Notice Days', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDIncrement%%%', '2009-08-06 12:47:55', '18', 'PD Increment', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustEmail%%%', '2009-08-06 12:48:26', '18', 'Company Cust Email', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustFax%%%', '2009-08-06 12:50:19', '18', 'Company Cust Fax', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNoticeDasy%%%', '2009-10-23 08:07:56', '33', 'days notice to request a paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollEmail%%%', '2009-10-29 10:32:45', '16', 'Company Contact Info', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustEmail%%%', '2009-10-29 10:33:09', '16', 'Company Contact Info', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollPhone%%%', '2009-10-29 10:33:34', '16', 'Company Contact Info', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustPhone%%%', '2009-10-29 10:34:00', '16', 'Company Contact Info', '0', null);
INSERT INTO `tokens` VALUES ('%%%TELELOAN_PHONE%%%', '2009-11-19 15:22:31', '9', 'Teleloan Phone Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%TELELOAN_PHONE%%%', '2009-11-19 15:23:14', '10', 'Teleloan Phone Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%TELELOAN_PHONE%%%', '2009-11-19 15:24:52', '17', 'Teleloan Phone Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%TELELOAN_PHONE%%%', '2009-11-19 15:25:37', '11', 'Teleloan Phone Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%TELELOAN_PHONE%%%', '2009-11-19 15:26:06', '7', 'Teleloan Phone Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%TELELOAN_PHONE%%%', '2009-11-19 15:27:24', '8', 'Teleloan Phone Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneQCQCollections%%%', '2010-08-25 16:11:35', '17', 'CompanyDeptPhoneQCQCollections', '0', '1');
INSERT INTO `tokens` VALUES ('%%%AGEANeSigLink%%%', '2010-10-06 10:18:50', '15', 'Agean esig link', '0', '2');
INSERT INTO `tokens` VALUES ('%%%EsigLinkLetterLink%%%', '2010-10-06 15:58:21', '11', 'EsigLinkLetterLink', '0', '3');
INSERT INTO `tokens` VALUES ('%%%unsubscribe%%%', '2010-10-06 15:58:39', '11', 'unsubscribe', '0', '4');
INSERT INTO `tokens` VALUES ('%%%EsigLinkLetterLink%%%', '2010-10-06 16:02:53', '7', 'EsigLinkLetterLink', '0', '5');
INSERT INTO `tokens` VALUES ('%%%unsubscribe%%%', '2010-10-06 16:03:03', '7', 'unsubscribe', '0', '6');
INSERT INTO `tokens` VALUES ('%%%EsigLinkLetterLink%%%', '2010-10-06 16:05:06', '10', 'EsigLinkLetterLink', '0', '7');
INSERT INTO `tokens` VALUES ('%%%unsubscribe%%%', '2010-10-06 16:05:09', '10', 'unsubscribe', '0', '8');
INSERT INTO `tokens` VALUES ('%%%unsubscribe%%%', '2010-10-06 16:06:44', '8', 'unsubscribe', '0', '9');
INSERT INTO `tokens` VALUES ('%%%EsigLinkLetterLink%%%', '2010-10-06 16:06:46', '8', 'EsigLinkLetterLink', '0', '10');
INSERT INTO `tokens` VALUES ('%%%EsigLinkLetterLink%%%', '2010-10-06 16:08:17', '9', 'EsigLinkLetterLink', '0', '11');
INSERT INTO `tokens` VALUES ('%%%AGEANeSigLink%%%', '2010-10-07 07:26:15', '14', 'Esig linjk for prospect confirmed', '0', '12');
INSERT INTO `tokens` VALUES ('%%%unsubscribe%%%', '2010-10-07 12:58:26', '9', 'unsubscribe', '0', '13');
INSERT INTO `tokens` VALUES ('%%%ZeroBalanceConfirmationNumber%%%', '2010-10-20 16:23:54', '17', 'ZeroBalanceConfirmationNumber', '0', '14');
INSERT INTO `tokens` VALUES ('%%%LoanDueDateYMD%%%', '2010-12-01 16:04:49', '17', 'LoanDueDate token formatted as Y-M-D', '0', '15');
INSERT INTO `tokens` VALUES ('%%%OriginalDueDate%%%', '2011-01-19 16:07:18', '17', 'Original Due Date if due date is extended', '0', '16');
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-11-07 12:07:29', '43', 'Customer\'s bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-11-07 12:07:29', '43', 'Customer\'s bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-11-07 12:07:29', '43', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardName%%%', '2007-11-07 12:07:29', '43', 'The name of the company\'s stored value card', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardNumber%%%', '2007-11-07 12:07:29', '43', 'The number of the customer\'s stored value card.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-11-07 12:07:29', '43', 'Company\'s stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-11-07 12:07:29', '43', 'Company\'s stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-11-07 12:07:29', '43', 'Company\'s stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-11-07 12:07:29', '43', 'Company\'s stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankABA%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankAccount%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankName%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankName%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankShort%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServName%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServPhone%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyCity%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDept%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCollections%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCustServ%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyEmail%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyFax%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyInit%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoLarge%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoSmall%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameLegal%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameShort%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPhone%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPromoID%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyState%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyStreet%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanySupportFax%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyUnit%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyWebSite%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyZip%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildConfirmLink%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerCity%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerDOB%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerEmail%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerESig%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerFax%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFirst%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFull%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameLast%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneCell%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneHome%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceLength%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceType%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart1%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart2%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart3%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerState%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStateID%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStreet%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerUnit%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerZip%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerLength%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerName%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerPhone%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerShift%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerTitle%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildeSigLink%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericEsigLink%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericMessage%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericSubject%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeDD%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeFrequency%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeMonthlyNet%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeNetPay%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate1%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate2%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate3%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate4%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeType%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanApplicationID%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanAPR%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanBalance%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCollectionCode%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrAPR%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrBalance%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrDueDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFees%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFinCharge%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrincipal%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrinPmnt%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDocDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDueDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFees%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFinCharge%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAmount%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAvail%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextAPR%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextBalance%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextDueDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFees%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFinCharge%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrincipal%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrinPmnt%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPayoffDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPrincipal%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanStatus%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoginId%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrAmount%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrType%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildNextBusinessDay%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPassword%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrAmount%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrType%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDAmount%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDDueDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDFinCharge%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextAmount%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextDueDate%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextFinCharge%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextTotal%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDTotal%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPrincipalPaymentAmount%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01NameFull%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01PhoneHome%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01Relationship%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02NameFull%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02PhoneHome%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02Relationship%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRefinanceAmount%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnFee%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnReason%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSenderName%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourcePromoID%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourceSiteName%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildToday%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildTotalOfPayments%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildUsername%%%', '2007-11-07 12:07:29', '43', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-11-07 12:07:29', '43', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-11-07 12:07:29', '43', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-11-07 12:07:29', '43', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-11-07 12:07:29', '43', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-11-07 12:07:29', '43', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalName%%%', '2009-01-21 16:59:22', '43', 'The company legal name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-11-07 12:07:29', '43', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-11-07 12:07:29', '43', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-11-07 12:07:29', '43', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-11-07 12:07:29', '43', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-11-07 12:07:29', '43', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-11-07 12:07:29', '43', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-11-07 12:07:29', '43', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-11-07 12:07:29', '43', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-11-07 12:07:29', '43', 'Company\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-11-07 12:07:29', '43', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-11-07 12:07:29', '43', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-11-07 12:07:29', '43', 'The link to use in confirming an application', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-11-07 12:07:29', '43', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-11-07 12:07:29', '43', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-11-07 12:07:29', '43', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-11-07 12:07:29', '43', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-11-07 12:07:29', '43', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialInFull%%%', '2007-11-07 12:07:29', '43', 'Customer\'s initials for paying in full.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialPayDown%%%', '2007-11-07 12:07:29', '43', 'Customer\'s initials for pay downs.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerIPAddress%%%', '2007-11-07 12:07:29', '43', 'The IP Address used to apply for the loan.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-11-07 12:07:29', '43', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-11-07 12:07:29', '43', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-11-07 12:07:29', '43', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-11-07 12:07:29', '43', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-11-07 12:07:29', '43', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-11-07 12:07:29', '43', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-11-07 12:07:29', '43', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-11-07 12:07:29', '43', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-11-07 12:07:29', '43', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-11-07 12:07:29', '43', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-11-07 12:07:29', '43', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-11-07 12:07:29', '43', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-11-07 12:07:29', '43', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-11-07 12:07:29', '43', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-11-07 12:07:29', '43', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-11-07 12:07:29', '43', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-11-07 12:07:29', '43', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-11-07 12:07:29', '43', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-11-07 12:07:29', '43', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-11-07 12:07:29', '43', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextApp%%%', '2007-11-07 12:07:29', '43', 'Display text for ESig line for the application section', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextAuth%%%', '2007-11-07 12:07:29', '43', 'Display text for ESig line for the authorization agreement', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextLoan%%%', '2007-11-07 12:07:29', '43', 'Display text for ESig line for the loan note and disclosure', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-11-07 12:07:29', '43', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%EsigLinkLetterLink%%%', '2010-10-06 16:08:17', '43', 'EsigLinkLetterLink', '0', '11');
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-11-07 12:07:29', '43', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-11-07 12:07:29', '43', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-11-07 12:07:29', '43', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-11-07 12:07:29', '43', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-11-07 12:07:29', '43', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-11-07 12:07:29', '43', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-11-07 12:07:29', '43', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-11-07 12:07:29', '43', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-11-07 12:07:29', '43', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-11-07 12:07:29', '43', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-11-07 12:07:29', '43', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingBalance%%%', '2009-01-21 16:59:45', '43', 'The last pending balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingDueDate%%%', '2009-01-21 17:00:09', '43', 'The last pending due date', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-11-07 12:07:29', '43', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-11-07 12:07:29', '43', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-11-07 12:07:29', '43', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-11-07 12:07:29', '43', 'The code assigned to the loan when it goes to collection. Set from company configuration.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-11-07 12:07:29', '43', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-11-07 12:07:29', '43', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-11-07 12:07:29', '43', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-11-07 12:07:29', '43', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-11-07 12:07:29', '43', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-11-07 12:07:29', '43', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-11-07 12:07:29', '43', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDateCreated%%%', '2007-11-07 12:07:29', '43', 'The date the application was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-11-07 12:07:29', '43', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-11-07 12:07:29', '43', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-11-07 12:07:29', '43', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-11-07 12:07:29', '43', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-11-07 12:07:29', '43', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-11-07 12:07:29', '43', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-11-07 12:07:29', '43', '(deprecated) The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-11-07 12:07:29', '43', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-11-07 12:07:29', '43', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-11-07 12:07:29', '43', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-11-07 12:07:29', '43', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-11-07 12:07:29', '43', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-11-07 12:07:29', '43', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-11-07 12:07:29', '43', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanTimeCreated%%%', '2007-11-07 12:07:29', '43', 'The time the application was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-11-07 12:07:29', '43', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-11-07 12:07:29', '43', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-11-07 12:07:29', '43', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-11-07 12:07:29', '43', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReceiveCode%%%', '2007-11-07 12:07:29', '43', 'Money Gram Receive Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-11-07 12:07:29', '43', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-11-07 12:07:29', '43', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-11-07 12:07:29', '43', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-11-07 12:07:29', '43', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-11-07 12:07:29', '43', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-11-07 12:07:29', '43', 'Paydown amount for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-11-07 12:07:29', '43', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-11-07 12:07:29', '43', 'Paydown Finance Charge for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-11-07 12:07:29', '43', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-11-07 12:07:29', '43', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-11-07 12:07:29', '43', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-11-07 12:07:29', '43', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-11-07 12:07:29', '43', 'Total current paydown amount (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2007-11-07 12:07:29', '43', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-11-07 12:07:29', '43', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-11-07 12:07:29', '43', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-11-07 12:07:29', '43', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-11-07 12:07:29', '43', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-11-07 12:07:29', '43', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-11-07 12:07:29', '43', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-11-07 12:07:29', '43', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-11-07 12:07:29', '43', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-11-07 12:07:29', '43', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-11-07 12:07:29', '43', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%TELELOAN_PHONE%%%', '2009-11-19 15:22:31', '43', 'Teleloan Phone Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-11-07 12:07:29', '43', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-11-07 12:07:29', '43', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%unsubscribe%%%', '2010-10-07 12:58:26', '43', 'unsubscribe', '0', '13');
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-11-07 12:07:29', '43', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2007-11-07 12:07:29', '44', 'Customer\'s bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2007-11-07 12:07:29', '44', 'Customer\'s bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2007-11-07 12:07:29', '44', 'Customer\'s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardName%%%', '2007-11-07 12:07:29', '44', 'The name of the company\'s stored value card', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardNumber%%%', '2007-11-07 12:07:29', '44', 'The number of the customer\'s stored value card.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2007-11-07 12:07:29', '44', 'Company\'s stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2007-11-07 12:07:29', '44', 'Company\'s stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2007-11-07 12:07:29', '44', 'Company\'s stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2007-11-07 12:07:29', '44', 'Company\'s stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankABA%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankAccount%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildBankName%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankName%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvBankShort%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServName%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCardProvServPhone%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyCity%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDept%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCollections%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyDeptPhoneCustServ%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyEmail%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyFax%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyInit%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoLarge%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyLogoSmall%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameLegal%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyNameShort%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPhone%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyPromoID%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyState%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyStreet%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanySupportFax%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyUnit%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyWebSite%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCompanyZip%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildConfirmLink%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerCity%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerDOB%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerEmail%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerESig%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerFax%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFirst%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameFull%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerNameLast%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneCell%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerPhoneHome%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceLength%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerResidenceType%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart1%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart2%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerSSNPart3%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerState%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStateID%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerStreet%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerUnit%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildCustomerZip%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerLength%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerName%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerPhone%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerShift%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildEmployerTitle%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildeSigLink%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericEsigLink%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericMessage%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildGenericSubject%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeDD%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeFrequency%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeMonthlyNet%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeNetPay%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate1%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate2%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate3%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomePaydate4%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildIncomeType%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanApplicationID%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanAPR%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanBalance%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCollectionCode%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrAPR%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrBalance%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrDueDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFees%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrFinCharge%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrincipal%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanCurrPrinPmnt%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDocDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanDueDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFees%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFinCharge%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAmount%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundAvail%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanFundDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextAPR%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextBalance%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextDueDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFees%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextFinCharge%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrincipal%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanNextPrinPmnt%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPayoffDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanPrincipal%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoanStatus%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildLoginId%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrAmount%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildMissedArrType%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildNextBusinessDay%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPassword%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrAmount%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPaymentArrType%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDAmount%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDDueDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDFinCharge%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextAmount%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextDueDate%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextFinCharge%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDNextTotal%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPDTotal%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildPrincipalPaymentAmount%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01NameFull%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01PhoneHome%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef01Relationship%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02NameFull%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02PhoneHome%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRef02Relationship%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildRefinanceAmount%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnFee%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildReturnReason%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSenderName%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourcePromoID%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildSourceSiteName%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildToday%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildTotalOfPayments%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%ChildUsername%%%', '2007-11-07 12:07:29', '44', '(Autogenerated Token)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2007-11-07 12:07:29', '44', 'Company\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2007-11-07 12:07:29', '44', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2007-11-07 12:07:29', '44', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2007-11-07 12:07:29', '44', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2007-11-07 12:07:29', '44', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLegalName%%%', '2009-01-21 16:59:22', '44', 'The company legal name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2007-11-07 12:07:29', '44', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2007-11-07 12:07:29', '44', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2007-11-07 12:07:29', '44', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2007-11-07 12:07:29', '44', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2007-11-07 12:07:29', '44', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2007-11-07 12:07:29', '44', 'Company\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2007-11-07 12:07:29', '44', 'Company\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2007-11-07 12:07:29', '44', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2007-11-07 12:07:29', '44', 'Company\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2007-11-07 12:07:29', '44', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2007-11-07 12:07:29', '44', 'Company\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%ConfirmLink%%%', '2007-11-07 12:07:29', '44', 'The link to use in confirming an application', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2007-11-07 12:07:29', '44', 'Customer\'s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2007-11-07 12:07:29', '44', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2007-11-07 12:07:29', '44', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2007-11-07 12:07:29', '44', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2007-11-07 12:07:29', '44', 'Customer\'s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialInFull%%%', '2007-11-07 12:07:29', '44', 'Customer\'s initials for paying in full.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerInitialPayDown%%%', '2007-11-07 12:07:29', '44', 'Customer\'s initials for pay downs.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerIPAddress%%%', '2007-11-07 12:07:29', '44', 'The IP Address used to apply for the loan.', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2007-11-07 12:07:29', '44', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2007-11-07 12:07:29', '44', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2007-11-07 12:07:29', '44', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2007-11-07 12:07:29', '44', 'Customer\'s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2007-11-07 12:07:29', '44', 'Customer\'s home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2007-11-07 12:07:29', '44', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2007-11-07 12:07:29', '44', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2007-11-07 12:07:29', '44', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2007-11-07 12:07:29', '44', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2007-11-07 12:07:29', '44', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2007-11-07 12:07:29', '44', 'Customer\'s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2007-11-07 12:07:29', '44', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2007-11-07 12:07:29', '44', 'Customer\'s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2007-11-07 12:07:29', '44', 'Customer\'s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2007-11-07 12:07:29', '44', 'Customer\'s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2007-11-07 12:07:29', '44', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2007-11-07 12:07:29', '44', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2007-11-07 12:07:29', '44', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2007-11-07 12:07:29', '44', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2007-11-07 12:07:29', '44', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextApp%%%', '2007-11-07 12:07:29', '44', 'Display text for ESig line for the application section', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextAuth%%%', '2007-11-07 12:07:29', '44', 'Display text for ESig line for the authorization agreement', '0', null);
INSERT INTO `tokens` VALUES ('%%%ESigDisplayTextLoan%%%', '2007-11-07 12:07:29', '44', 'Display text for ESig line for the loan note and disclosure', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2007-11-07 12:07:29', '44', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%EsigLinkLetterLink%%%', '2010-10-06 16:08:17', '44', 'EsigLinkLetterLink', '0', '11');
INSERT INTO `tokens` VALUES ('%%%GenericMessage%%%', '2007-11-07 12:07:29', '44', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericSubject%%%', '2007-11-07 12:07:29', '44', 'Used by the Generic Message template', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2007-11-07 12:07:29', '44', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2007-11-07 12:07:29', '44', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2007-11-07 12:07:29', '44', 'Customer\'s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2007-11-07 12:07:29', '44', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2007-11-07 12:07:29', '44', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2007-11-07 12:07:29', '44', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2007-11-07 12:07:29', '44', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2007-11-07 12:07:29', '44', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2007-11-07 12:07:29', '44', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingBalance%%%', '2009-01-21 16:59:45', '44', 'The last pending balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LastPendingDueDate%%%', '2009-01-21 17:00:09', '44', 'The last pending due date', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2007-11-07 12:07:29', '44', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2007-11-07 12:07:29', '44', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2007-11-07 12:07:29', '44', 'The currently scheduled total due on the loan (principal + fees + service charge), if set. Otherwise, the overall total due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2007-11-07 12:07:29', '44', 'The code assigned to the loan when it goes to collection. Set from company configuration.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2007-11-07 12:07:29', '44', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2007-11-07 12:07:29', '44', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2007-11-07 12:07:29', '44', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2007-11-07 12:07:29', '44', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2007-11-07 12:07:29', '44', '(deprecated) The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2007-11-07 12:07:29', '44', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2007-11-07 12:07:29', '44', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDateCreated%%%', '2007-11-07 12:07:29', '44', 'The date the application was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2007-11-07 12:07:29', '44', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2007-11-07 12:07:29', '44', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2007-11-07 12:07:29', '44', 'The service charge for the current payment cycle, if set. Otherwise, the total service charge amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2007-11-07 12:07:29', '44', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2007-11-07 12:07:29', '44', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2007-11-07 12:07:29', '44', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2007-11-07 12:07:29', '44', '(deprecated) The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2007-11-07 12:07:29', '44', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2007-11-07 12:07:29', '44', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2007-11-07 12:07:29', '44', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2007-11-07 12:07:29', '44', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2007-11-07 12:07:29', '44', 'Due date of the current payment cycle, if a schedule is set. Otherwise, the first payment date calculated from the paydate wizard.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2007-11-07 12:07:29', '44', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2007-11-07 12:07:29', '44', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanTimeCreated%%%', '2007-11-07 12:07:29', '44', 'The time the application was created', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2007-11-07 12:07:29', '44', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2007-11-07 12:07:29', '44', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2007-11-07 12:07:29', '44', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2007-11-07 12:07:29', '44', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MoneyGramReceiveCode%%%', '2007-11-07 12:07:29', '44', 'Money Gram Receive Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%NextBusinessDay%%%', '2007-11-07 12:07:29', '44', 'The next business day, based upon today.', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2007-11-07 12:07:29', '44', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2007-11-07 12:07:29', '44', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2007-11-07 12:07:29', '44', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2007-11-07 12:07:29', '44', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2007-11-07 12:07:29', '44', 'Paydown amount for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2007-11-07 12:07:29', '44', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2007-11-07 12:07:29', '44', 'Paydown Finance Charge for the current schedule cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2007-11-07 12:07:29', '44', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2007-11-07 12:07:29', '44', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2007-11-07 12:07:29', '44', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2007-11-07 12:07:29', '44', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2007-11-07 12:07:29', '44', 'Total current paydown amount (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PrincipalPaymentAmount%%%', '2007-11-07 12:07:29', '44', 'Principal Payment Amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2007-11-07 12:07:29', '44', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2007-11-07 12:07:29', '44', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2007-11-07 12:07:29', '44', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2007-11-07 12:07:29', '44', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2007-11-07 12:07:29', '44', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2007-11-07 12:07:29', '44', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2007-11-07 12:07:29', '44', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2007-11-07 12:07:29', '44', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2007-11-07 12:07:29', '44', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2007-11-07 12:07:29', '44', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%TELELOAN_PHONE%%%', '2009-11-19 15:22:31', '44', 'Teleloan Phone Number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2007-11-07 12:07:29', '44', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2007-11-07 12:07:29', '44', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%unsubscribe%%%', '2010-10-07 12:58:26', '44', 'unsubscribe', '0', '13');
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2007-11-07 12:07:29', '44', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%GreenConfirmImage%%%', '2012-04-23 16:31:35', '16', 'Green Confirmation Image', '0', '17');
INSERT INTO `tokens` VALUES ('%%%SilverApplyImage%%%', '2012-04-23 16:55:32', '16', 'Silver Apply Image', '0', '18');
INSERT INTO `tokens` VALUES ('%%%RedFinishImage%%%', '2012-04-26 17:41:05', '16', 'Red Finish Image', '0', '19');
INSERT INTO `tokens` VALUES ('%%%BankABA%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s bank ABA number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankAccount%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s bank account number', '0', null);
INSERT INTO `tokens` VALUES ('%%%BankName%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s bank name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankName%%%', '2012-08-25 11:59:41', '45', 'Companys stored-value card providers full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvBankShort%%%', '2012-08-25 11:59:41', '45', 'Companys stored-value card providers short name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServName%%%', '2012-08-25 11:59:41', '45', 'Companys stored-value card providers service providers name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CardProvServPhone%%%', '2012-08-25 11:59:41', '45', 'Companys stored-value card providers service providers phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCity%%%', '2012-08-25 11:59:41', '45', 'Companyâ€™s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollEmail%%%', '2012-08-25 11:59:41', '45', 'Company Contact Info', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCollPhone%%%', '2012-08-25 11:59:41', '45', 'Company Contact Info', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustEmail%%%', '2012-08-25 11:59:41', '45', 'Company Contact Info', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyCustPhone%%%', '2012-08-25 11:59:41', '45', 'Company Contact Info', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDBA%%%', '2012-08-25 11:59:41', '45', 'Name company does business as', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCollections%%%', '2012-08-25 11:59:41', '45', 'Company Phone Number - Collections Department', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyDeptPhoneCustServ%%%', '2012-08-25 11:59:41', '45', 'Company Phone Number - Customer Service (usually same as CompanyPhone)', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyEmail%%%', '2012-08-25 11:59:41', '45', 'Customer Service email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyFax%%%', '2012-08-25 11:59:41', '45', 'Customer Service fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoLarge%%%', '2012-08-25 11:59:41', '45', 'Large image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyLogoSmall%%%', '2012-08-25 11:59:41', '45', 'Small image of Company logo', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyName%%%', '2012-08-25 11:59:41', '45', 'Company Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameFormal%%%', '2012-08-25 11:59:41', '45', 'Full Length Company Name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameLegal%%%', '2012-08-25 11:59:41', '45', 'The legal name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyNameShort%%%', '2012-08-25 11:59:41', '45', 'The short name of the company', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyPhone%%%', '2012-08-25 11:59:41', '45', 'Customer Service phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyState%%%', '2012-08-25 11:59:41', '45', 'Companyâ€™s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyStreet%%%', '2012-08-25 11:59:41', '45', 'Companyâ€™s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanySupportFax%%%', '2012-08-25 11:59:41', '45', 'Company\'s customer support fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyUnit%%%', '2012-08-25 11:59:41', '45', 'Companyâ€™s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite%%%', '2012-08-25 11:59:41', '45', 'Company Enterprise website link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CompanyZip%%%', '2012-08-25 11:59:41', '45', 'Companyâ€™s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%CSLoginLink%%%', '2012-08-25 11:59:41', '45', 'Customer Service Login Link', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerCity%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s city', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerDOB%%%', '2012-08-25 11:59:41', '45', 'Customer\'s date of birth', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerEmail%%%', '2012-08-25 11:59:41', '45', 'Customer\'s email address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerESig%%%', '2012-08-25 11:59:41', '45', 'Customer\'s e-signature', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerFax%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s fax number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFirst%%%', '2012-08-25 11:59:41', '45', 'Customer\'s first name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameFull%%%', '2012-08-25 11:59:41', '45', 'Customer\'s full name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerNameLast%%%', '2012-08-25 11:59:41', '45', 'Customer\'s last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneCell%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s cell phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerPhoneHome%%%', '2012-08-25 11:59:41', '45', 'Customers home phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceLength%%%', '2012-08-25 11:59:41', '45', 'Length of time the customer has been at their current residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerResidenceType%%%', '2012-08-25 11:59:41', '45', 'Whether the customer rents or owns their residence', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart1%%%', '2012-08-25 11:59:41', '45', 'First three numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart2%%%', '2012-08-25 11:59:41', '45', 'Middle two numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerSSNPart3%%%', '2012-08-25 11:59:41', '45', 'Last four numbers of the social security number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerState%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s State', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStateID%%%', '2012-08-25 11:59:41', '45', 'State ID or driver\'s license number', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerStreet%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s street address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerUnit%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s unit number for address', '0', null);
INSERT INTO `tokens` VALUES ('%%%CustomerZip%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s Zip Code', '0', null);
INSERT INTO `tokens` VALUES ('%%%Day%%%', '2012-08-25 11:59:41', '45', 'The day of the week', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerLength%%%', '2012-08-25 11:59:41', '45', 'How long the customer has worked for their current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerName%%%', '2012-08-25 11:59:41', '45', 'Name of customer\'s current employer', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerPhone%%%', '2012-08-25 11:59:41', '45', 'Customer\'s work phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerShift%%%', '2012-08-25 11:59:41', '45', 'The customer\'s work shift or hours', '0', null);
INSERT INTO `tokens` VALUES ('%%%EmployerTitle%%%', '2012-08-25 11:59:41', '45', 'Customer\'s position at their place of employment', '0', null);
INSERT INTO `tokens` VALUES ('%%%eSigLink%%%', '2012-08-25 11:59:41', '45', 'The link to where the applicant can eSig the loan documents', '0', null);
INSERT INTO `tokens` VALUES ('%%%GenericEsigLink%%%', '2012-08-25 11:59:41', '45', 'Generic ESig Link Token', '0', null);
INSERT INTO `tokens` VALUES ('%%%GreenConfirmImage%%%', '2012-08-25 11:59:41', '45', 'Green Confirmation Image', '0', '17');
INSERT INTO `tokens` VALUES ('%%%IncomeDD%%%', '2012-08-25 11:59:41', '45', 'Indicates if the customer uses direct deposit', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeFrequency%%%', '2012-08-25 11:59:41', '45', 'How often the customer is paid', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeMonthlyNet%%%', '2012-08-25 11:59:41', '45', 'Customerâ€™s net monthly income', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeNetPay%%%', '2012-08-25 11:59:41', '45', 'Customer\'s net pay each paycheck', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate1%%%', '2012-08-25 11:59:41', '45', 'Customer\'s next paydate', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate2%%%', '2012-08-25 11:59:41', '45', 'Customer\'s second pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate3%%%', '2012-08-25 11:59:41', '45', 'Customer\'s third pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomePaydate4%%%', '2012-08-25 11:59:41', '45', 'Customer\'s fourth pay date', '0', null);
INSERT INTO `tokens` VALUES ('%%%IncomeType%%%', '2012-08-25 11:59:41', '45', 'Customer\'s type of income', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanApplicationID%%%', '2012-08-25 11:59:41', '45', 'Loan Application ID number', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanAPR%%%', '2012-08-25 11:59:41', '45', 'Annual Percentage Rate calculated for the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanBalance%%%', '2012-08-25 11:59:41', '45', 'Total due on the loan which consists of the outstanding principal, service charges, and fees', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCollectionCode%%%', '2012-08-25 11:59:41', '45', 'The code assigned to the loan when it goes to collection', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrAPR%%%', '2012-08-25 11:59:41', '45', 'APR of the Current Principal & Current Service Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrBalance%%%', '2012-08-25 11:59:41', '45', 'Current Loan Payoff Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrDueDate%%%', '2012-08-25 11:59:41', '45', 'Due Date of the current payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrFinCharge%%%', '2012-08-25 11:59:41', '45', 'Finance Charge for the Current Principal Balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinAmount%%%', '2012-08-25 11:59:41', '45', 'The outstanding principal balance', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrincipal%%%', '2012-08-25 11:59:41', '45', 'Current Principal Amount Financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanCurrPrinPmnt%%%', '2012-08-25 11:59:41', '45', 'Principal portion of the current payment due', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDocDate%%%', '2012-08-25 11:59:41', '45', 'The date of the loan document', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanDueDate%%%', '2012-08-25 11:59:41', '45', 'The due date of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFinCharge%%%', '2012-08-25 11:59:41', '45', 'Dollar amount of the credit cost', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundActionDate%%%', '2012-08-25 11:59:41', '45', 'The estimated date the funds are sent to the bank', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAmount%%%', '2012-08-25 11:59:41', '45', 'Amount financed', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundAvail%%%', '2012-08-25 11:59:41', '45', 'Estimated Availability of Funds', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate%%%', '2012-08-25 11:59:41', '45', 'Date funds are deposited into the customer\'s account', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDate2%%%', '2012-08-25 11:59:41', '45', 'The business day following the estimated day the loan will be funded', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanFundDueDate%%%', '2012-08-25 11:59:41', '45', 'Based on the LoanFundDueDate, this is the date the funds are available to the customer.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextAPR%%%', '2012-08-25 11:59:41', '45', 'APR of the Principal & Current Service of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextBalance%%%', '2012-08-25 11:59:41', '45', 'Total paydown amount (finance charge + principal) for the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextDueDate%%%', '2012-08-25 11:59:41', '45', 'Due Date of the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanNextPrincipal%%%', '2012-08-25 11:59:41', '45', 'Principal amount financed after the next payment cycle', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPayoffDate%%%', '2012-08-25 11:59:41', '45', 'Loan payoff date', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanPrincipal%%%', '2012-08-25 11:59:41', '45', 'The current principal payoff amount, if set. Otherwise, the total loan principal amount.', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoanStatus%%%', '2012-08-25 11:59:41', '45', 'Indicates the status of the loan', '0', null);
INSERT INTO `tokens` VALUES ('%%%LoginId%%%', '2012-08-25 11:59:41', '45', 'Agent Login - first initial last name', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrAmount%%%', '2012-08-25 11:59:41', '45', 'The amount of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrDate%%%', '2012-08-25 11:59:41', '45', 'The due date of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%MissedArrType%%%', '2012-08-25 11:59:41', '45', 'The type of the most recently passed payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%Password%%%', '2012-08-25 11:59:41', '45', 'The code given to a customer to log on to the company website.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrAmount%%%', '2012-08-25 11:59:41', '45', 'Payment arrangement amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrDate%%%', '2012-08-25 11:59:41', '45', 'Payment arrangement date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PaymentArrType%%%', '2012-08-25 11:59:41', '45', 'Form of payment for payment arrangement', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDAmount%%%', '2012-08-25 11:59:41', '45', 'Paydown amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDDueDate%%%', '2012-08-25 11:59:41', '45', 'Current Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDFinCharge%%%', '2012-08-25 11:59:41', '45', 'Paydown Finance Charge', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextAmount%%%', '2012-08-25 11:59:41', '45', 'Principal amount due at next paydown', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextDueDate%%%', '2012-08-25 11:59:41', '45', 'Next Scheduled Paydown Due Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextFinCharge%%%', '2012-08-25 11:59:41', '45', 'The next Paydown and finance charge.', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDNextTotal%%%', '2012-08-25 11:59:41', '45', 'Total next pay down payment due (finance charge + principal)', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDPercent%%%', '2012-08-25 11:59:41', '45', 'Paydown percent amount', '0', null);
INSERT INTO `tokens` VALUES ('%%%PDTotal%%%', '2012-08-25 11:59:41', '45', 'Total of all Paydown amounts', '0', null);
INSERT INTO `tokens` VALUES ('%%%RedFinishImage%%%', '2012-08-25 11:59:41', '45', 'Red Finish Image', '0', '19');
INSERT INTO `tokens` VALUES ('%%%Ref01NameFull%%%', '2012-08-25 11:59:41', '45', 'First reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01PhoneHome%%%', '2012-08-25 11:59:41', '45', 'First reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref01Relationship%%%', '2012-08-25 11:59:41', '45', 'First reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02NameFull%%%', '2012-08-25 11:59:41', '45', 'Second reference\'s name', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02PhoneHome%%%', '2012-08-25 11:59:41', '45', 'Second reference\'s phone number', '0', null);
INSERT INTO `tokens` VALUES ('%%%Ref02Relationship%%%', '2012-08-25 11:59:41', '45', 'Second reference\'s relationship to the customer', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnFee%%%', '2012-08-25 11:59:41', '45', 'Charge for a returned item transaction', '0', null);
INSERT INTO `tokens` VALUES ('%%%ReturnReason%%%', '2012-08-25 11:59:41', '45', 'The reason the item was returned', '0', null);
INSERT INTO `tokens` VALUES ('%%%SilverApplyImage%%%', '2012-08-25 11:59:41', '45', 'Silver Apply Image', '0', '18');
INSERT INTO `tokens` VALUES ('%%%SourcePromoID%%%', '2012-08-25 11:59:41', '45', 'Promo ID associated with the source site', '0', null);
INSERT INTO `tokens` VALUES ('%%%SourceSiteName%%%', '2012-08-25 11:59:41', '45', 'Name of the loan origination website', '0', null);
INSERT INTO `tokens` VALUES ('%%%Time%%%', '2012-08-25 11:59:41', '45', 'The time of day', '0', null);
INSERT INTO `tokens` VALUES ('%%%Today%%%', '2012-08-25 11:59:41', '45', 'Today\'s Date', '0', null);
INSERT INTO `tokens` VALUES ('%%%TotalOfPayments%%%', '2012-08-25 11:59:41', '45', 'Total paid after all scheduled payments', '0', null);
INSERT INTO `tokens` VALUES ('%%%Username%%%', '2012-08-25 11:59:41', '45', 'The unique name assigned to an applicant to log on to the company web site', '0', null);
INSERT INTO `tokens` VALUES ('%%%GreenFinishImage%%%', '2012-10-10 19:53:34', '16', 'GreenFinishImage', '0', '20');
INSERT INTO `tokens` VALUES ('%%%LoanFinChargeMax%%%', '2013-09-07 16:25:59', '16', 'LoanFinChargeMax', '0', '21');
INSERT INTO `tokens` VALUES ('%%%TotalOfPaymentsMax%%%', '2013-09-07 16:26:30', '16', 'TotalOfPaymentsMax', '0', '22');
INSERT INTO `tokens` VALUES ('%%%LoanFinChargeAdd%%%', '2013-09-10 07:00:55', '16', 'LoanFinChargeAdd', '0', '23');
INSERT INTO `tokens` VALUES ('%%%CardAuthCode%%%', '2014-01-10 10:58:08', '16', 'Payment Card Auth Code', '0', '24');
INSERT INTO `tokens` VALUES ('%%%LastPaymentDate%%%', '2014-01-10 10:58:38', '16', 'Last Payment Date', '0', '25');
INSERT INTO `tokens` VALUES ('%%%LastPaymentAmount%%%', '2014-01-10 10:59:04', '16', 'Last Payment Amount', '0', '26');
INSERT INTO `tokens` VALUES ('%%%CompanyHours%%%', '2014-01-10 11:04:15', '16', 'Company Hours', '0', '27');
INSERT INTO `tokens` VALUES ('%%%ACHBankDescriptor%%%', '2014-06-02 23:24:59', '16', 'ACHBankDescriptor', '0', '28');
INSERT INTO `tokens` VALUES ('%%%CompanyLegalName%%%', '2014-06-02 23:25:51', '16', 'CompanyLegalName', '0', '29');
INSERT INTO `tokens` VALUES ('%%%CompanyLegalAddress%%%', '2014-06-02 23:26:19', '16', 'CompanyLegalAddress', '0', '30');
INSERT INTO `tokens` VALUES ('%%%CompanyLegalCity%%%', '2014-06-02 23:26:48', '16', 'CompanyLegalCity', '0', '31');
INSERT INTO `tokens` VALUES ('%%%CompanyLegalState%%%', '2014-06-02 23:27:12', '16', 'CompanyLegalState', '0', '32');
INSERT INTO `tokens` VALUES ('%%%CompanyLegalZip%%%', '2014-06-02 23:27:41', '16', 'CompanyLegalZip', '0', '33');
INSERT INTO `tokens` VALUES ('%%%CompanyPaymentCutoff%%%', '2014-06-02 23:28:29', '16', 'CompanyPaymentCutoff', '0', '34');
INSERT INTO `tokens` VALUES ('%%%CompanyUnsEmail%%%', '2014-06-02 23:29:27', '16', 'CompanyUnsEmail', '0', '35');
INSERT INTO `tokens` VALUES ('%%%CompanyCopyright%%%', '2014-06-04 11:35:15', '16', 'Copyright', '0', '36');
INSERT INTO `tokens` VALUES ('%%%ACHBatchTime%%%', '2014-06-06 13:19:41', '16', 'ACHBatchTime', '0', '37');
INSERT INTO `tokens` VALUES ('%%%CompanyCustAddress%%%', '2014-06-09 05:15:50', '16', 'CompanyCustAddress', '0', '38');
INSERT INTO `tokens` VALUES ('%%%CompanyCustCity%%%', '2014-06-09 05:16:14', '16', 'CompanyCustCity', '0', '39');
INSERT INTO `tokens` VALUES ('%%%CompanyCustState%%%', '2014-06-09 05:16:30', '16', 'CompanyCustState', '0', '40');
INSERT INTO `tokens` VALUES ('%%%CompanyCustZip%%%', '2014-06-09 05:16:43', '16', 'CompanyCustZip', '0', '41');
INSERT INTO `tokens` VALUES ('%%%CompanyAddress%%%', '2014-06-23 13:39:02', '16', 'CompanyAddress', '0', '42');
INSERT INTO `tokens` VALUES ('%%%CompanyWebSite1%%%', '2014-06-23 22:42:56', '16', 'CompanyWebSite1', '0', '43');
INSERT INTO `tokens` VALUES ('%%%MaxAPR%%%', '2014-07-16 09:19:38', '16', 'Maximum APR with all renewals', '0', '44');
INSERT INTO `tokens` VALUES ('%%%NetLoanProceeds%%%', '2014-11-11 08:02:20', '16', 'Net Loan Amount', '0', '45');
INSERT INTO `tokens` VALUES ('%%%ACHDisbursementAmount%%%', '2014-11-13 11:54:43', '16', 'ACHDisbursementAmount', '0', '46');
INSERT INTO `tokens` VALUES ('%%%SpamLink%%%', '2015-04-29 08:14:06', '16', 'The link that directs the user to the privacy settings on the customer portal.', '0', '47');
INSERT INTO `tokens` VALUES ('%%%SpamLink%%%', '2015-04-30 12:06:19', '45', 'The link that directs the user to the privacy settings on the customer portal.', '0', '48');
INSERT INTO `tokens` VALUES ('%%%ACHDisbursementAmount%%%', '2015-04-30 12:07:13', '45', 'ACHDisbursementAmount', '0', '49');
INSERT INTO `tokens` VALUES ('%%%NetLoanProceeds%%%', '2015-04-30 12:08:01', '45', 'Net Loan Amount', '0', '50');
INSERT INTO `tokens` VALUES ('%%%CompanyHours%%%', '2015-04-30 10:22:08', '45', 'Company Hours', '0', '48');
INSERT INTO `tokens` VALUES ('%%%CompanyCopyright%%%', '2015-04-30 10:22:34', '45', 'Copyright', '0', '49');
INSERT INTO `tokens` VALUES ('%%%CompanyAddress%%%', '2015-07-08 13:04:50', '45', 'Company Address', '0', '50');
INSERT INTO `tokens` VALUES ('%%%CustomerESigIPAddress%%%', '2015-07-21 17:31:39', '16', 'Company Address', '0', '51');
INSERT INTO `tokens` VALUES ('%%%ReactLink%%%', '2015-07-29 11:19:07', '16', 'React URL', '0', '52');
INSERT INTO `tokens` VALUES ('%%%TribalIP%%%', '2015-09-16 15:03:12', '16', 'TribalIP', '0', '58');
INSERT INTO `tokens` VALUES ('%%%TribalResponseDateTime%%%', '2015-09-16 15:04:06', '16', 'TribalResponseDateTime', '0', '59');

-- ----------------------------
-- Table structure for `token_data`
-- ----------------------------
DROP TABLE IF EXISTS `token_data`;
CREATE TABLE `token_data` (
  `date_created` datetime NOT NULL,
  `token_data_id` int(11) NOT NULL AUTO_INCREMENT,
  `raw_data` varchar(255) NOT NULL DEFAULT 'Empty',
  `token_data_type` enum('text','image','url') NOT NULL DEFAULT 'text',
  PRIMARY KEY (`token_data_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of token_data
-- ----------------------------
INSERT INTO `token_data` VALUES ('2010-08-25 16:11:35', '1', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 10:18:50', '2', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 15:58:21', '3', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 15:58:39', '4', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 16:02:53', '5', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 16:03:03', '6', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 16:05:06', '7', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 16:05:09', '8', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 16:06:44', '9', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 16:06:46', '10', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-06 16:08:17', '11', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-07 07:26:15', '12', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-07 12:58:26', '13', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-10-20 16:23:54', '14', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2010-12-01 16:04:49', '15', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2011-01-19 16:07:18', '16', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2012-04-23 16:31:35', '17', 'https://someloancompany.com/imgdir/live/themes/IPS/skins/nms/slc/someloancompany.com/media/image/green_confirm.jpg', 'image');
INSERT INTO `token_data` VALUES ('2012-04-23 16:55:32', '18', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2012-04-26 17:41:05', '19', 'Empty', 'image');
INSERT INTO `token_data` VALUES ('2012-10-10 19:53:34', '20', 'Empty', 'image');
INSERT INTO `token_data` VALUES ('2013-09-07 16:25:59', '21', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2013-09-07 16:26:30', '22', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2013-09-10 07:00:55', '23', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2014-01-10 10:58:08', '24', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2014-01-10 10:58:38', '25', '1/1//2014', 'text');
INSERT INTO `token_data` VALUES ('2014-01-10 10:59:04', '26', '$100.00', 'text');
INSERT INTO `token_data` VALUES ('2014-01-10 11:04:15', '27', 'Mon - Fri 8 AM to 6 PM Eastern', 'text');
INSERT INTO `token_data` VALUES ('2014-06-02 23:24:59', '28', 'SLC-8005579038', 'text');
INSERT INTO `token_data` VALUES ('2014-06-02 23:25:51', '29', 'Clear Lake Holdings', 'text');
INSERT INTO `token_data` VALUES ('2014-06-02 23:26:19', '30', '123 Main St.', 'text');
INSERT INTO `token_data` VALUES ('2014-06-02 23:26:48', '31', 'Las Vegas', 'text');
INSERT INTO `token_data` VALUES ('2014-06-02 23:27:12', '32', 'NV', 'text');
INSERT INTO `token_data` VALUES ('2014-06-02 23:27:41', '33', '89103', 'text');
INSERT INTO `token_data` VALUES ('2014-06-02 23:28:29', '34', '2 business days before your payment is due', 'text');
INSERT INTO `token_data` VALUES ('2014-06-02 23:29:27', '35', 'unsubscribe@someloancompany.com', 'text');
INSERT INTO `token_data` VALUES ('2014-06-04 11:35:15', '36', '&#169; 2015', 'text');
INSERT INTO `token_data` VALUES ('2014-06-06 13:19:41', '37', '6 PM Eastern', 'text');
INSERT INTO `token_data` VALUES ('2014-06-09 05:15:50', '38', '621 Medicine Way Suite 3', 'text');
INSERT INTO `token_data` VALUES ('2014-06-09 05:16:14', '39', 'Ukiah', 'text');
INSERT INTO `token_data` VALUES ('2014-06-09 05:16:30', '40', 'CA', 'text');
INSERT INTO `token_data` VALUES ('2014-06-09 05:16:43', '41', '95482', 'text');
INSERT INTO `token_data` VALUES ('2014-06-23 13:39:02', '42', '621 Medicine Way Suite 3', 'text');
INSERT INTO `token_data` VALUES ('2014-06-23 22:42:56', '43', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2014-07-16 09:19:38', '44', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2014-11-11 08:02:20', '45', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2014-11-13 11:54:43', '46', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2015-04-29 08:14:06', '47', 'https://web2-staging.atlas-lms.com/spam_login?link=OTAyMjIxMDM2&key=2b2924d3ba8ace9e0072673f3e1f9a34', 'text');
INSERT INTO `token_data` VALUES ('2015-04-30 10:22:08', '48', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2015-04-30 10:22:34', '49', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2015-07-08 13:04:50', '50', '123 Test Street', 'text');
INSERT INTO `token_data` VALUES ('2015-07-21 17:31:39', '51', '255.255.255.255', 'text');
INSERT INTO `token_data` VALUES ('2015-07-29 11:19:07', '52', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2015-07-29 11:19:56', '53', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2015-08-02 20:06:40', '54', 'Ukiah', 'text');
INSERT INTO `token_data` VALUES ('2015-08-02 20:07:00', '55', 'CA', 'text');
INSERT INTO `token_data` VALUES ('2015-08-02 20:07:11', '56', '95482', 'text');
INSERT INTO `token_data` VALUES ('2015-08-02 20:07:42', '57', 'SomeLoanCompany', 'text');
INSERT INTO `token_data` VALUES ('2015-09-16 15:03:12', '58', 'Empty', 'text');
INSERT INTO `token_data` VALUES ('2015-09-16 15:04:06', '59', 'Empty', 'text');
