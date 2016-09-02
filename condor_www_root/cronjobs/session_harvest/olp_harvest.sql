
-- DROP DATABASE IF EXISTS olp_test_harvest;
-- CREATE DATABASE olp_test_harvest;
-- USE olp_test_harvest;

-- DROP TABLE IF EXISTS harvest_log;
CREATE TABLE harvest_log (
	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	ts_run timestamp NOT NULL,
	ts_from timestamp NOT NULL,
	ts_until timestamp NOT NULL,
	db_from CHAR(24) NOT NULL,
	table_from CHAR(16) NOT NULL,
	sessions_qualified INTEGER UNSIGNED NOT NULL DEFAULT 0,
	sessions_new INTEGER UNSIGNED NOT NULL DEFAULT 0,
	sessions_updated INTEGER UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	KEY idx_db_from (db_from(5), table_from(9))
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS harvest_session_log;
CREATE TABLE harvest_session_log (
	session_id CHAR(32) NOT NULL,
	PRIMARY KEY (session_id)
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS harvest_application_log;
CREATE TABLE harvest_application_log (
	application_id INTEGER NOT NULL,
	PRIMARY KEY (application_id)
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS `bank_info`;
CREATE TABLE `bank_info` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `bank_name` char(40) NOT NULL default '',
  `account_number` char(16) NOT NULL default '',
  `routing_number` char(10) NOT NULL default '',
  `check_number` char(8) NOT NULL default '',
  `direct_deposit` enum('TRUE','FALSE') default NULL,
  PRIMARY KEY  (`application_id`),
  KEY `idx_routing_number` (`routing_number`(3))
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS `campaign_info`;
CREATE TABLE `campaign_info` (
  `campaign_info_id` int(10) unsigned NOT NULL auto_increment,
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `promo_id` int(10) unsigned NOT NULL default '0',
  `promo_sub_code` char(24) NOT NULL default '',
  `license_key` char(32) NOT NULL default '',
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `active` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `ip_address` char(15) NOT NULL default '',
  `url` char(50) NOT NULL default '',
  PRIMARY KEY  (`campaign_info_id`),
  KEY `application_id` (`application_id`),
  KEY `license_key` (`license_key`)
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS `employment`;
CREATE TABLE `employment` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `employer` char(20) NOT NULL default '',
  `address_id` int(10) unsigned default NULL,
  `work_phone` char(11) NOT NULL default '',
  `work_ext` char(8) NOT NULL default '',
  `title` char(16) NOT NULL default '',
  `shift` char(16) NOT NULL default '',
  `date_of_hire` date NOT NULL default '0000-00-00',
  `income_type` enum('BENEFITS','EMPLOYMENT') default NULL,
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS `income`;
CREATE TABLE `income` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `net_pay` mediumint(9) unsigned default NULL,
  `pay_frequency` enum('WEEKLY','BI_WEEKLY','TWICE_MONTHLY','MONTHLY') NOT NULL default 'WEEKLY',
  `paid_on_day_1` int(10) unsigned default NULL,
  `paid_on_day_2` int(10) unsigned default NULL,
  `pay_date_1` timestamp(14) NOT NULL default '00000000000000',
  `pay_date_2` timestamp(14) NOT NULL default '00000000000000',
  `pay_date_3` timestamp(14) NOT NULL default '00000000000000',
  `pay_date_4` timestamp(14) NOT NULL default '00000000000000',
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS `paydate`;
CREATE TABLE `paydate` (
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `paydate_id` int(11) NOT NULL auto_increment,
  `application_id` int(11) NOT NULL default '0',
  `paydate_model_id` enum('DW','DWPD','DWDM','WWDW','DM','DMDM','WDW') default NULL,
  `day_of_week` int(11) default NULL,
  `next_paydate` date default NULL,
  `day_of_month_1` int(11) default NULL,
  `day_of_month_2` int(11) default NULL,
  `week_1` int(11) default NULL,
  `week_2` int(11) default NULL,
  `accuracy_warning` smallint(6) default NULL,
  PRIMARY KEY  (`paydate_id`)
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS `personal`;
CREATE TABLE `personal` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `first_name` char(32) NOT NULL default '',
  `middle_name` char(16) NOT NULL default '',
  `last_name` char(32) NOT NULL default '',
  `home_phone` char(11) default '',
  `cell_phone` char(11) default '',
  `fax_phone` char(11) default '',
  `email` char(50) NOT NULL default '',
  `alt_email` char(50) default NULL,
  `date_of_birth` date NOT NULL default '0000-00-00',
  `contact_id_1` int(10) unsigned NOT NULL default '0',
  `contact_id_2` int(10) unsigned NOT NULL default '0',
  `social_security_number` char(9) NOT NULL default '',
  `drivers_license_number` char(24) default NULL,
  `best_call_time` enum('MORNING','AFTERNOON','EVENING') default NULL,
  PRIMARY KEY  (`application_id`),
  KEY `idx_home_phone` (`home_phone`),
  KEY `idx_ssn` (`social_security_number`(3)),
  KEY `idx_last_name` (`last_name`(5))
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS `personal_contact`;
CREATE TABLE `personal_contact` (
  `contact_id` int(10) unsigned NOT NULL auto_increment,
  `application_id` int(10) unsigned NOT NULL default '0',
  `full_name` char(32) NOT NULL default '',
  `phone` char(10) NOT NULL default '',
  `relationship` char(12) NOT NULL default '',
  PRIMARY KEY  (`contact_id`),
  KEY `application_id` (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

-- DROP TABLE IF EXISTS `residence`;
CREATE TABLE `residence` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `residence_type` enum('RENT','OWN') default NULL,
  `length_of_residence` mediumint(8) unsigned default '3',
  `address_1` char(32) NOT NULL default '',
  `apartment` char(16) NOT NULL default '',
  `city` char(24) NOT NULL default '',
  `state` char(2) NOT NULL default '',
  `zip` char(10) NOT NULL default '',
  `address_2` char(24) default '',
  PRIMARY KEY  (`application_id`),
  KEY `idx_state` (`state`),
  KEY `idx_zip` (`zip`(3))
) TYPE=MyISAM PACK_KEYS=1;

