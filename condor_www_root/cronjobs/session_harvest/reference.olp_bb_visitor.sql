-- MySQL dump 9.11
--
-- Host: ds001.ibm.tss    Database: olp_bb_visitor
-- ------------------------------------------------------
-- Server version	4.0.20-standard-log

--
-- Table structure for table `account`
--

DROP TABLE IF EXISTS `account`;
CREATE TABLE `account` (
  `account_id` varchar(250) NOT NULL default '',
  `active_application_id` int(10) unsigned NOT NULL default '0',
  `assigned_csr` int(10) unsigned default NULL,
  `login` varchar(250) NOT NULL default 'login',
  `modified_date` timestamp(14) NOT NULL,
  `login_expire_date` timestamp(14) NOT NULL default '00000000000000',
  `account_expire_date` timestamp(14) NOT NULL default '00000000000000',
  `password_expire_date` timestamp(14) NOT NULL default '00000000000000',
  `hash_pass` varchar(250) NOT NULL default '',
  `hash_temp` varchar(250) NOT NULL default '',
  `active` enum('TRUE','FALSE') default NULL,
  `access_level` tinyint(3) unsigned default NULL,
  `access_type` enum('CUSTOMER','VENDOR','TSS') default NULL,
  PRIMARY KEY  (`account_id`),
  UNIQUE KEY `active_application_id` (`active_application_id`),
  UNIQUE KEY `login` (`login`),
  KEY `assigned_csr` (`assigned_csr`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `application`
--

DROP TABLE IF EXISTS `application`;
CREATE TABLE `application` (
  `application_id` int(10) unsigned NOT NULL auto_increment,
  `account_id` varchar(250) NOT NULL default '',
  `session_id` varchar(250) NOT NULL default '',
  `modified_date` timestamp(14) NOT NULL,
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `type` enum('VISITOR','QUALIFIED','PROSPECT','APPLICANT','CUSTOMER','DOA','PENDING') NOT NULL default 'VISITOR',
  `status` enum('APPROVED','DENIED','WITHDRAWN','HOLD','NOT_PROCESSED','CSR','CSR_DOC','CSR_DOC_COMPLETE','PENDING','DECLINED') NOT NULL default 'NOT_PROCESSED',
  `assigned_lpr` int(10) unsigned NOT NULL default '0',
  `csr_status` enum('CSR','CSR_DOC','CSR_DOC_COMPLETE') default NULL,
  `customer_type` enum('NEW','RETURN') NOT NULL default 'NEW',
  `bb_vp_id` varchar(250) default NULL,
  PRIMARY KEY  (`application_id`),
  KEY `idx_created_data` (`created_date`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `authentication`
--

DROP TABLE IF EXISTS `authentication`;
CREATE TABLE `authentication` (
  `authentication_id` int(11) NOT NULL auto_increment,
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `application_id` int(11) NOT NULL default '0',
  `authentication_source_id` int(11) NOT NULL default '1',
  `sent_package` text NOT NULL,
  `received_package` text NOT NULL,
  `score` varchar(100) NOT NULL default '',
  `flags` text,
  `audit_id` int(11) NOT NULL default '0',
  `bb_result` int(10) unsigned default NULL,
  `display_data` text,
  PRIMARY KEY  (`authentication_id`),
  KEY `idx_created_date` (`date_created`),
  KEY `idx_application_id` (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `bank_info`
--

DROP TABLE IF EXISTS `bank_info`;
CREATE TABLE `bank_info` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `bank_name` varchar(250) NOT NULL default '',
  `account_number` varchar(250) NOT NULL default '',
  `routing_number` varchar(250) NOT NULL default '',
  `check_number` varchar(250) NOT NULL default '',
  `direct_deposit` enum('TRUE','FALSE') default NULL,
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `blackbox_batch`
--

DROP TABLE IF EXISTS `blackbox_batch`;
CREATE TABLE `blackbox_batch` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `winner` varchar(4) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `data_sent` text NOT NULL,
  `data_recv` text NOT NULL,
  `num_retry` int(10) unsigned NOT NULL default '0',
  `num_update` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`application_id`,`winner`),
  KEY `idx_date_created` (`date_created`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `blackbox_post`
--

DROP TABLE IF EXISTS `blackbox_post`;
CREATE TABLE `blackbox_post` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `winner` varchar(4) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `data_sent` text NOT NULL,
  `data_recv` text NOT NULL,
  `post_result_id` int(10) unsigned NOT NULL default '0',
  `num_retry` int(10) unsigned NOT NULL default '0',
  `num_update` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`application_id`,`winner`),
  KEY `idx_date_created` (`date_created`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `blackbox_post_result`
--

DROP TABLE IF EXISTS `blackbox_post_result`;
CREATE TABLE `blackbox_post_result` (
  `blackbox_post_result_id` int(10) unsigned NOT NULL auto_increment,
  `hash` varchar(32) NOT NULL default '',
  `data` text NOT NULL,
  PRIMARY KEY  (`blackbox_post_result_id`),
  KEY `idx_hash` (`hash`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `blackbox_state`
--

DROP TABLE IF EXISTS `blackbox_state`;
CREATE TABLE `blackbox_state` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `bb_nms_new` datetime default NULL,
  `bb_nms_bad` datetime default NULL,
  `bb_nms_underactive` datetime default NULL,
  `bb_nms_overactive` datetime default NULL,
  `bb_status_inactive_yes` datetime default NULL,
  `bb_status_inactive_no` datetime default NULL,
  `bb_status_denied_yes` datetime default NULL,
  `bb_status_denied_no` datetime default NULL,
  `bb_clv_pass` datetime default NULL,
  `bb_clv_fail` datetime default NULL,
  `bb_clv_basic` datetime default NULL,
  `bb_ca` datetime default NULL,
  `bb_d1` datetime default NULL,
  `bb_pcl` datetime default NULL,
  `bb_ucl` datetime default NULL,
  `bb_ted` datetime default NULL,
  `bb_ezm_c2` datetime default NULL,
  `accepted` datetime default NULL,
  `bb_ezm_p` datetime default NULL,
  `bb_ezm_p2` datetime default NULL,
  `bb_ca_agree` datetime default NULL,
  `bb_d1_agree` datetime default NULL,
  `bb_pcl_agree` datetime default NULL,
  `bb_ucl_agree` datetime default NULL,
  `bb_ezm_c_agree` datetime default NULL,
  `bb_ezm_c2_agree` datetime default NULL,
  `bb_ezm_p_agree` datetime default NULL,
  `bb_ezm_p2_agree` datetime default NULL,
  `bb_cac` datetime default NULL,
  `bb_vp` datetime default NULL,
  `bb_ufc` datetime default NULL,
  `bb_ufc_agree` datetime default NULL,
  `bb_efm` datetime default NULL,
  `bb_cap` datetime default NULL,
  `bb_cap_agree` datetime default NULL,
  `bb_lc` datetime default NULL,
  `bb_ds` datetime default NULL,
  `bb_efm3` datetime default NULL,
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM;

--
-- Table structure for table `campaign_info`
--

DROP TABLE IF EXISTS `campaign_info`;
CREATE TABLE `campaign_info` (
  `campaign_info_id` int(10) unsigned NOT NULL auto_increment,
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `promo_id` int(10) unsigned NOT NULL default '0',
  `promo_sub_code` varchar(250) NOT NULL default '',
  `license_key` varchar(250) NOT NULL default '',
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `active` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `ip_address` varchar(15) NOT NULL default '',
  `url` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`campaign_info_id`),
  KEY `application_id` (`application_id`),
  KEY `license_key` (`license_key`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `csr_status`
--

DROP TABLE IF EXISTS `csr_status`;
CREATE TABLE `csr_status` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `status` enum('CALL_BACK','AWAITING_RESPONSE') NOT NULL default 'CALL_BACK',
  `call_back_time` timestamp(14) NOT NULL,
  PRIMARY KEY  (`application_id`),
  KEY `idx_call_back_time` (`call_back_time`)
) TYPE=MyISAM;

--
-- Table structure for table `employment`
--

DROP TABLE IF EXISTS `employment`;
CREATE TABLE `employment` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `employer` varchar(250) NOT NULL default '',
  `address_id` int(10) unsigned default NULL,
  `work_phone` varchar(250) NOT NULL default '',
  `work_ext` varchar(250) NOT NULL default '',
  `title` varchar(250) NOT NULL default '',
  `shift` varchar(250) NOT NULL default '',
  `date_of_hire` date NOT NULL default '0000-00-00',
  `income_type` enum('BENEFITS','EMPLOYMENT') default NULL,
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `income`
--

DROP TABLE IF EXISTS `income`;
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

--
-- Table structure for table `loan_declined`
--

DROP TABLE IF EXISTS `loan_declined`;
CREATE TABLE `loan_declined` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `reason` enum('WANTED_MORE_MONEY','WANTED_LESS_MONEY','FEES_TOO_HIGH','NO_PAYSTUB','NO_BANK_STATEMENT','NO_FAX','NO_PRINTER','JUST_CURIOUS','WANT_MORE_INFO','NEVER_INTERESTED','NO_TIME','NOT_NOW','DUE_TOO_SOON','OTHER','NO_DOCUMENTS','SELF_CANCEL','NO_CONTACT','JOB_TOO_SHORT','NO_INCOME','TOO_YOUNG','FRAUD','NOT_EMPLOYED','NO_CHECKING_ACCOUNT','BANKRUPTCY') NOT NULL default 'OTHER',
  `other` longtext,
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM;

--
-- Table structure for table `loan_note`
--

DROP TABLE IF EXISTS `loan_note`;
CREATE TABLE `loan_note` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `estimated_fund_date` timestamp(14) NOT NULL default '00000000000000',
  `actual_fund_date` timestamp(14) NOT NULL default '00000000000000',
  `fund_amount` mediumint(8) unsigned NOT NULL default '0',
  `num_payments` tinyint(4) unsigned NOT NULL default '0',
  `apr` float unsigned default NULL,
  `finance_charge` float unsigned default NULL,
  `total_payments` float unsigned default NULL,
  `estimated_payoff_date` timestamp(14) NOT NULL default '00000000000000',
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `paydate`
--

DROP TABLE IF EXISTS `paydate`;
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
) TYPE=MyISAM;

--
-- Table structure for table `personal`
--

DROP TABLE IF EXISTS `personal`;
CREATE TABLE `personal` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `first_name` varchar(250) NOT NULL default '',
  `middle_name` varchar(250) NOT NULL default '',
  `last_name` varchar(75) NOT NULL default '',
  `home_phone` varchar(250) default '',
  `cell_phone` varchar(250) default '',
  `fax_phone` varchar(250) default '',
  `email` varchar(250) NOT NULL default '',
  `alt_email` varchar(250) default NULL,
  `date_of_birth` date NOT NULL default '0000-00-00',
  `contact_id_1` int(10) unsigned NOT NULL default '0',
  `contact_id_2` int(10) unsigned NOT NULL default '0',
  `social_security_number` varchar(250) NOT NULL default '',
  `drivers_license_number` varchar(250) default NULL,
  `best_call_time` enum('MORNING','AFTERNOON','EVENING') default NULL,
  PRIMARY KEY  (`application_id`),
  KEY `idx_home_phone` (`home_phone`(10)),
  KEY `idx_ssn` (`social_security_number`(9)),
  KEY `idx_last_name` (`last_name`(10))
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `personal_contact`
--

DROP TABLE IF EXISTS `personal_contact`;
CREATE TABLE `personal_contact` (
  `contact_id` int(10) unsigned NOT NULL auto_increment,
  `application_id` int(10) unsigned NOT NULL default '0',
  `full_name` varchar(250) NOT NULL default '',
  `phone` varchar(250) NOT NULL default '',
  `relationship` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`contact_id`),
  KEY `application_id` (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `residence`
--

DROP TABLE IF EXISTS `residence`;
CREATE TABLE `residence` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `residence_type` enum('RENT','OWN') default NULL,
  `length_of_residence` mediumint(8) unsigned default '3',
  `address_1` varchar(250) NOT NULL default '',
  `apartment` varchar(250) NOT NULL default '',
  `city` varchar(250) NOT NULL default '',
  `state` varchar(250) NOT NULL default '',
  `zip` varchar(250) NOT NULL default '',
  `address_2` varchar(100) default '',
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_0`
--

DROP TABLE IF EXISTS `session_0`;
CREATE TABLE `session_0` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_1`
--

DROP TABLE IF EXISTS `session_1`;
CREATE TABLE `session_1` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_2`
--

DROP TABLE IF EXISTS `session_2`;
CREATE TABLE `session_2` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_3`
--

DROP TABLE IF EXISTS `session_3`;
CREATE TABLE `session_3` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_4`
--

DROP TABLE IF EXISTS `session_4`;
CREATE TABLE `session_4` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_5`
--

DROP TABLE IF EXISTS `session_5`;
CREATE TABLE `session_5` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_6`
--

DROP TABLE IF EXISTS `session_6`;
CREATE TABLE `session_6` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_7`
--

DROP TABLE IF EXISTS `session_7`;
CREATE TABLE `session_7` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_8`
--

DROP TABLE IF EXISTS `session_8`;
CREATE TABLE `session_8` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_9`
--

DROP TABLE IF EXISTS `session_9`;
CREATE TABLE `session_9` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_a`
--

DROP TABLE IF EXISTS `session_a`;
CREATE TABLE `session_a` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_b`
--

DROP TABLE IF EXISTS `session_b`;
CREATE TABLE `session_b` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_c`
--

DROP TABLE IF EXISTS `session_c`;
CREATE TABLE `session_c` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_d`
--

DROP TABLE IF EXISTS `session_d`;
CREATE TABLE `session_d` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_e`
--

DROP TABLE IF EXISTS `session_e`;
CREATE TABLE `session_e` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_f`
--

DROP TABLE IF EXISTS `session_f`;
CREATE TABLE `session_f` (
  `session_id` varchar(32) NOT NULL default '',
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `date_locked` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  `session_lock` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`session_id`),
  KEY `idx_created` (`date_created`),
  KEY `idx_modified` (`date_modified`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `session_site`
--

DROP TABLE IF EXISTS `session_site`;
CREATE TABLE `session_site` (
  `session_id` varchar(33) NOT NULL default '',
  `modifed_date` timestamp(14) NOT NULL,
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `created_date` (`created_date`),
  KEY `modifed_date` (`modifed_date`)
) TYPE=MyISAM;

--
-- Table structure for table `stat_info`
--

DROP TABLE IF EXISTS `stat_info`;
CREATE TABLE `stat_info` (
  `application_id` int(11) NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `prequal` timestamp(14) NOT NULL default '00000000000000',
  `accepted` timestamp(14) NOT NULL default '00000000000000',
  `funded` timestamp(14) NOT NULL default '00000000000000',
  `pulled_prospect` timestamp(14) NOT NULL default '00000000000000',
  `return_customer` timestamp(14) NOT NULL default '00000000000000',
  `confirmed` timestamp(14) NOT NULL default '00000000000000',
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM PACK_KEYS=1;

--
-- Table structure for table `stat_limit`
--

DROP TABLE IF EXISTS `stat_limit`;
CREATE TABLE `stat_limit` (
  `stat_limit_date` date NOT NULL default '0000-00-00',
  `bb_ezm_c_agree` int(10) unsigned NOT NULL default '0',
  `bb_ezm_c2_agree` int(10) unsigned NOT NULL default '0',
  `bb_ezm_p_agree` int(10) unsigned NOT NULL default '0',
  `bb_ezm_p2_agree` int(10) unsigned NOT NULL default '0',
  `bb_cac` int(10) unsigned NOT NULL default '0',
  `bb_vp` int(10) unsigned NOT NULL default '0',
  `bb_pcl_agree` int(10) unsigned NOT NULL default '0',
  `bb_ucl_agree` int(10) unsigned NOT NULL default '0',
  `bb_ca_agree` int(10) unsigned NOT NULL default '0',
  `bb_ufc_agree` int(10) unsigned NOT NULL default '0',
  `bb_efm` int(10) unsigned NOT NULL default '0',
  `bb_cap_agree` int(10) unsigned NOT NULL default '0',
  `bb_lc` int(10) unsigned NOT NULL default '0',
  `bb_ds` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`stat_limit_date`)
) TYPE=MyISAM PACK_KEYS=1;

