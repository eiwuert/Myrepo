-- MySQL dump 10.11
--
-- Host: monster.tss    Database: olp
-- ------------------------------------------------------
-- Server version	5.0.44sp1-enterprise-gpl-log
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `application`
--

DROP TABLE IF EXISTS `application`;
CREATE TABLE `application` (
  `application_id` int(10) unsigned NOT NULL,
  `session_id` varchar(250) NOT NULL default '',
  `modified_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `created_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `bb_vp_id` varchar(250) default NULL,
  `track_id` varchar(40) default NULL,
  `target_id` int(10) default NULL,
  `transaction_id` bigint(20) default NULL,
  `application_type` enum('VISITOR','PENDING','COMPLETED','AGREED','DISAGREED','CONFIRMED','FAILED','CONFIRMED_DISAGREED','EXPIRED') default 'VISITOR',
  `application_status_id` int(10) unsigned NOT NULL default '0',
  `denied_target_id` int(11) default NULL,
  `olp_process` varchar(255) NOT NULL default 'online_confirmation',
  `is_react` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`application_id`),
  KEY `idx_created_data` (`created_date`),
  KEY `idx_track_id` (`track_id`(6))
);

--
-- Table structure for table `application_documents`
--

DROP TABLE IF EXISTS `application_documents`;
CREATE TABLE `application_documents` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `document_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`application_id`,`document_id`)
);

--
-- Table structure for table `application_loan_action`
--

DROP TABLE IF EXISTS `application_loan_action`;
CREATE TABLE `application_loan_action` (
  `loan_action_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned NOT NULL default '0',
  `action_name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`loan_action_id`),
  KEY `idx_app` (`application_id`)
);

--
-- Table structure for table `application_status`
--

DROP TABLE IF EXISTS `application_status`;
CREATE TABLE `application_status` (
  `application_status_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) default NULL,
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`application_status_id`)
);

--
-- Table structure for table `application_tag_details`
--

DROP TABLE IF EXISTS `application_tag_details`;
CREATE TABLE `application_tag_details` (
  `tag_id` int(11) unsigned NOT NULL,
  `tag_name` varchar(11) NOT NULL default '',
  `name` varchar(50) default NULL,
  `description` varchar(255) default NULL,
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`tag_id`)
);

--
-- Table structure for table `application_tags`
--

DROP TABLE IF EXISTS `application_tags`;
CREATE TABLE `application_tags` (
  `app_tag_id` int(11) unsigned NOT NULL,
  `tag_id` int(11) unsigned default NULL,
  `application_id` int(11) unsigned default NULL,
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`app_tag_id`),
  KEY `idx_application_id` (`application_id`)
);

--
-- Table structure for table `asynch_result`
--

DROP TABLE IF EXISTS `asynch_result`;
CREATE TABLE `asynch_result` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `asynch_result_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned NOT NULL,
  `asynch_result_object` blob NOT NULL,
  `mode` enum('BROKER','PREQUAL','CONFIRMATION','ONLINE_CONFIRMATION','ECASH_REACT') default NULL,
  PRIMARY KEY  (`asynch_result_id`),
  KEY `application_id` (`application_id`)
);

--
-- Table structure for table `authentication`
--

DROP TABLE IF EXISTS `authentication`;
CREATE TABLE `authentication` (
  `authentication_id` int(11) NOT NULL,
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `application_id` int(11) NOT NULL default '0',
  `authentication_source_id` int(11) NOT NULL default '1',
  `sent_package` text NOT NULL,
  `received_package` text NOT NULL,
  `decision` enum('PASS','FAIL') NOT NULL default 'PASS',
  `reason` varchar(250) default NULL,
  `timer` double(8,5) default NULL,
  `encrypted` tinyint(4) NOT NULL default '0',
  `score` varchar(10) default NULL,
  `package_sent` text NOT NULL,
  `package_recv` text NOT NULL,
  PRIMARY KEY  (`authentication_id`),
  KEY `idx_application_id` (`application_id`),
  KEY `idx_created_date` (`date_created`)
);

--
-- Table structure for table `bad_email_aba`
--

DROP TABLE IF EXISTS `bad_email_aba`;
CREATE TABLE `bad_email_aba` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `id` int(10) unsigned NOT NULL,
  `email_primary` varchar(255) NOT NULL,
  `date_modified` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `idx_date_modified` (`date_modified`),
  KEY `idx_email_primary` (`email_primary`)
);

--
-- Table structure for table `bank_info_encrypted`
--

DROP TABLE IF EXISTS `bank_info_encrypted`;
CREATE TABLE `bank_info_encrypted` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `bank_name` varchar(250) NOT NULL default '',
  `account_number` varchar(250) NOT NULL default '',
  `routing_number` varchar(250) NOT NULL default '',
  `check_number` varchar(250) NOT NULL default '',
  `direct_deposit` enum('TRUE','FALSE','OTHER') default NULL,
  `bank_account_type` enum('CHECKING','SAVINGS') default NULL,
  `banking_start_date` date default NULL,
  `debit_card_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`application_id`),
  KEY `idx_bank` (`account_number`(12)),
  KEY `idx_route` (`routing_number`(12))
);

--
-- Table structure for table `blackbox_batch`
--

DROP TABLE IF EXISTS `blackbox_batch`;
CREATE TABLE `blackbox_batch` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `winner` varchar(4) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `data_sent` text NOT NULL,
  `data_recv` text NOT NULL,
  `num_retry` int(10) unsigned NOT NULL default '0',
  `num_update` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`application_id`,`winner`),
  KEY `idx_date_created` (`date_created`)
);

--
-- Table structure for table `blackbox_post`
--

DROP TABLE IF EXISTS `blackbox_post`;
CREATE TABLE `blackbox_post` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `winner` varchar(8) NOT NULL default '',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `data_sent` text NOT NULL,
  `data_recv` text NOT NULL,
  `post_result_id` int(10) unsigned NOT NULL default '0',
  `num_retry` int(10) unsigned NOT NULL default '0',
  `num_update` int(10) unsigned NOT NULL default '0',
  `post_time` float(8,5) NOT NULL default '0.00000',
  `success` enum('TRUE','FALSE','PROCESSING') default 'FALSE',
  `compression` enum('NONE','GZ') default 'NONE',
  `type` enum('POST','VERIFY_POST') NOT NULL default 'POST',
  `vendor_decision` varchar(16) default '',
  `vendor_reason` varchar(255) default '',
  `encrypted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`application_id`,`winner`,`type`),
  KEY `idx_date_created` (`date_created`),
  KEY `success` (`success`)
);

--
-- Table structure for table `blackbox_post_result`
--

DROP TABLE IF EXISTS `blackbox_post_result`;
CREATE TABLE `blackbox_post_result` (
  `blackbox_post_result_id` int(10) unsigned NOT NULL,
  `hash` varchar(32) NOT NULL default '',
  `data` text NOT NULL,
  PRIMARY KEY  (`blackbox_post_result_id`),
  KEY `idx_hash` (`hash`)
);

--
-- Table structure for table `blackbox_snapshot`
--

DROP TABLE IF EXISTS `blackbox_snapshot`;
CREATE TABLE `blackbox_snapshot` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `snapshot` text NOT NULL,
  PRIMARY KEY  (`application_id`),
  KEY `idx_date_created` (`date_created`)
);

--
-- Table structure for table `blackbox_stats`
--

DROP TABLE IF EXISTS `blackbox_stats`;
CREATE TABLE `blackbox_stats` (
  `stat_date` date NOT NULL default '0000-00-00',
  `property_short` char(10) NOT NULL default '',
  `count` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`stat_date`,`property_short`)
);

--
-- Table structure for table `campaign`
--

DROP TABLE IF EXISTS `campaign`;
CREATE TABLE `campaign` (
  `date_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `campaign_id` int(10) unsigned NOT NULL,
  `target_id` int(10) unsigned NOT NULL default '0',
  `percentage` tinyint(3) unsigned NOT NULL default '0',
  `status` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `type` enum('ONGOING','BY_DATE') NOT NULL default 'ONGOING',
  `start_date` date NOT NULL default '0000-00-00',
  `end_date` date default NULL,
  `limit` mediumtext NOT NULL,
  `hourly_limit` mediumtext NOT NULL,
  `limit_mult` float(3,2) NOT NULL default '0.00',
  `thank_you_content` mediumtext NOT NULL,
  `username` varchar(100) NOT NULL default '',
  `total_limit` int(10) unsigned NOT NULL default '0',
  `lead_amount` float(5,2) NOT NULL default '0.00',
  `dd_ratio` tinyint(3) unsigned NOT NULL default '0',
  `max_deviation` tinyint(3) unsigned NOT NULL default '0',
  `priority` float(6,2) default '1.00',
  `overflow` tinyint(3) unsigned NOT NULL default '0',
  `daily_limit` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`campaign_id`),
  KEY `target_id` (`target_id`)
);

--
-- Table structure for table `campaign_info`
--

DROP TABLE IF EXISTS `campaign_info`;
CREATE TABLE `campaign_info` (
  `campaign_info_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `promo_id` int(10) unsigned NOT NULL default '0',
  `promo_sub_code` varchar(250) NOT NULL default '',
  `license_key` varchar(250) NOT NULL default '',
  `created_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `ip_address` varchar(15) NOT NULL default '',
  `url` varchar(250) NOT NULL default '',
  `offers` enum('TRUE','FALSE') default NULL,
  `tel_app_proc` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `reservation_id` bigint(20) default NULL,
  PRIMARY KEY  (`campaign_info_id`),
  KEY `application_id` (`application_id`),
  KEY `license_key` (`license_key`),
  KEY `idx_created` (`created_date`)
);

--
-- Table structure for table `ccn_daily`
--

DROP TABLE IF EXISTS `ccn_daily`;
CREATE TABLE `ccn_daily` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `date_created` datetime default NULL,
  PRIMARY KEY  (`application_id`)
);

--
-- Table structure for table `client`
--

DROP TABLE IF EXISTS `client`;
CREATE TABLE `client` (
  `date_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `client_id` int(10) unsigned NOT NULL,
  `name` char(255) NOT NULL default '',
  `phone_number` char(15) NOT NULL default '',
  `email_address` char(255) NOT NULL default '',
  `contact` char(255) NOT NULL default '',
  `status` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  PRIMARY KEY  (`client_id`)
);

--
-- Table structure for table `coreg_request`
--

DROP TABLE IF EXISTS `coreg_request`;
CREATE TABLE `coreg_request` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `num_retry` int(10) unsigned NOT NULL,
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `data_recv` blob NOT NULL,
  `data_sent` blob NOT NULL,
  `success` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `coreg_type` enum('coreg','egc') NOT NULL default 'coreg',
  PRIMARY KEY  (`application_id`,`num_retry`),
  KEY `idx_date_created` (`date_created`)
);

--
-- Table structure for table `cs_session`
--

DROP TABLE IF EXISTS `cs_session`;
CREATE TABLE `cs_session` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `session_id` char(32) NOT NULL default '',
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`application_id`,`session_id`),
  KEY `idx_date_created` (`date_created`)
);

--
-- Table structure for table `debit_card`
--

DROP TABLE IF EXISTS `debit_card`;
CREATE TABLE `debit_card` (
  `debit_card_id` int(10) unsigned NOT NULL,
  `name` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`debit_card_id`),
  UNIQUE KEY `idx_name` (`name`)
);

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
  `work_verification_phone` varchar(250) default NULL,
  `work_verification_phone_ext` varchar(250) default NULL,
  `employer_phone` varchar(15) default NULL,
  `employer_phone_ext` varchar(8) default NULL,
  PRIMARY KEY  (`application_id`)
);

--
-- Table structure for table `encryption_agent`
--

DROP TABLE IF EXISTS `encryption_agent`;
CREATE TABLE `encryption_agent` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `login` varchar(50) NOT NULL default '',
  `password` varchar(255) default NULL,
  `crypt_password` varchar(255) default NULL,
  PRIMARY KEY  (`login`)
);

--
-- Table structure for table `error_log`
--

DROP TABLE IF EXISTS `error_log`;
CREATE TABLE `error_log` (
  `error_log_id` int(10) unsigned NOT NULL,
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `application_id` int(10) unsigned NOT NULL default '0',
  `error_code` varchar(64) NOT NULL default '',
  `num_errors` tinyint(3) unsigned NOT NULL default '0',
  `site_type` varchar(50) NOT NULL default '',
  `page` varchar(50) NOT NULL default '',
  `promo_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`error_log_id`),
  KEY `idx_error_code` (`error_code`(5)),
  KEY `idx_application_id` (`application_id`)
);

--
-- Table structure for table `event_log`
--

DROP TABLE IF EXISTS `event_log`;
CREATE TABLE `event_log` (
  `id` int(11) unsigned NOT NULL,
  `application_id` int(11) unsigned NOT NULL default '0',
  `event_id` int(11) unsigned NOT NULL default '0',
  `response_id` int(11) unsigned NOT NULL default '0',
  `target_id` int(11) unsigned default NULL,
  `mode` enum('BROKER','PREQUAL','CONFIRMATION','ONLINE_CONFIRMATION','ECASH_REACT') default NULL,
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `idx_app_id` (`application_id`)
);

--
-- Table structure for table `event_responses`
--

DROP TABLE IF EXISTS `event_responses`;
CREATE TABLE `event_responses` (
  `response_id` int(11) unsigned NOT NULL,
  `response` varchar(255) NOT NULL default '',
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`response_id`),
  KEY `response` (`response`(5))
);

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `event_id` int(11) unsigned NOT NULL,
  `event` varchar(255) NOT NULL default '',
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`event_id`),
  KEY `event` (`event`(10))
);

--
-- Table structure for table `fail_log`
--

DROP TABLE IF EXISTS `fail_log`;
CREATE TABLE `fail_log` (
  `fail_log_id` int(11) NOT NULL,
  `failover_name` varchar(128) NOT NULL default '',
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `alert_sent` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  PRIMARY KEY  (`fail_log_id`),
  KEY `idx_date_name` (`date_created`,`failover_name`)
);

--
-- Table structure for table `failover_data`
--

DROP TABLE IF EXISTS `failover_data`;
CREATE TABLE `failover_data` (
  `name` varchar(255) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `date_set` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `set_by` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`name`)
);

--
-- Table structure for table `fle_dupes`
--

DROP TABLE IF EXISTS `fle_dupes`;
CREATE TABLE `fle_dupes` (
  `fle_dupe_id` int(10) unsigned NOT NULL,
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `email` char(100) NOT NULL default '',
  `site` char(100) NOT NULL default '',
  PRIMARY KEY  (`fle_dupe_id`),
  KEY `idx_email` (`email`(5)),
  KEY `idx_date_created` (`date_created`)
);

--
-- Table structure for table `fraud`
--

DROP TABLE IF EXISTS `fraud`;
CREATE TABLE `fraud` (
  `application_id` int(10) unsigned NOT NULL,
  `fraud_field` enum('dep_account','income_frequency','income_monthly_net','dob') NOT NULL,
  `value` int(8) unsigned NOT NULL,
  `application_id2` int(10) unsigned NOT NULL,
  `value2` int(8) unsigned NOT NULL,
  PRIMARY KEY  (`application_id`)
);

--
-- Table structure for table `fraud_application`
--

DROP TABLE IF EXISTS `fraud_application`;
CREATE TABLE `fraud_application` (
  `fraud_rule_id` int(11) unsigned NOT NULL default '0',
  `application_id` int(11) unsigned NOT NULL default '0',
  UNIQUE KEY `fraud_app_idx` (`fraud_rule_id`,`application_id`)
);

--
-- Table structure for table `fraud_query_log`
--

DROP TABLE IF EXISTS `fraud_query_log`;
CREATE TABLE `fraud_query_log` (
  `query_id` int(10) unsigned NOT NULL,
  `email` varchar(100) NOT NULL,
  `promo_id` int(10) unsigned NOT NULL,
  `promo_sub_code` varchar(250) default NULL,
  `result` enum('','dep_account','income_frequency','income_monthly_net') NOT NULL,
  `created_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`query_id`),
  KEY `idx_promo_id_created_date` (`promo_id`,`created_date`),
  KEY `idx_created_date` (`created_date`),
  KEY `idx_email` (`email`)
);

--
-- Table structure for table `freq_query_log`
--

DROP TABLE IF EXISTS `freq_query_log`;
CREATE TABLE `freq_query_log` (
  `query_id` int(10) unsigned NOT NULL,
  `email` varchar(250) NOT NULL,
  `promo_id` int(10) unsigned NOT NULL,
  `promo_sub_code` varchar(250) default NULL,
  `created_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`query_id`),
  KEY `idx_promo_id_created_date` (`promo_id`,`created_date`),
  KEY `idx_created_date` (`created_date`)
);

--
-- Table structure for table `income`
--

DROP TABLE IF EXISTS `income`;
CREATE TABLE `income` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `net_pay` mediumint(9) unsigned default NULL,
  `pay_frequency` enum('WEEKLY','BI_WEEKLY','TWICE_MONTHLY','MONTHLY') NOT NULL default 'WEEKLY',
  `paid_on_day_1` int(10) unsigned default NULL,
  `paid_on_day_2` int(10) unsigned default NULL,
  `pay_date_1` timestamp NOT NULL default '0000-00-00 00:00:00',
  `pay_date_2` timestamp NOT NULL default '0000-00-00 00:00:00',
  `pay_date_3` timestamp NOT NULL default '0000-00-00 00:00:00',
  `pay_date_4` timestamp NOT NULL default '0000-00-00 00:00:00',
  `monthly_net` mediumint(8) unsigned default NULL,
  PRIMARY KEY  (`application_id`)
);

--
-- Table structure for table `list_mgmt_buffer`
--

DROP TABLE IF EXISTS `list_mgmt_buffer`;
CREATE TABLE `list_mgmt_buffer` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `application_id` int(10) unsigned NOT NULL default '0',
  `email` varchar(250) NOT NULL default '',
  `first_name` varchar(250) NOT NULL default '',
  `last_name` varchar(250) NOT NULL default '',
  `ole_list_id` smallint(5) unsigned NOT NULL default '1',
  `ole_site_id` smallint(5) unsigned NOT NULL default '1',
  `group_id` smallint(5) unsigned NOT NULL default '1',
  `mode` varchar(250) NOT NULL default '',
  `license_key` varchar(250) NOT NULL default '',
  `address_1` varchar(250) NOT NULL default '',
  `apartment` varchar(250) NOT NULL default '',
  `city` varchar(250) NOT NULL default '',
  `state` varchar(250) NOT NULL default '',
  `zip` varchar(250) NOT NULL default '',
  `url` varchar(250) NOT NULL default '',
  `phone_home` varchar(250) NOT NULL default '',
  `phone_cell` varchar(15) default NULL,
  `date_of_birth` varchar(10) default NULL,
  `promo_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`application_id`),
  KEY `idx_date` (`date_created`)
);

--
-- Table structure for table `list_mgmt_nosell`
--

DROP TABLE IF EXISTS `list_mgmt_nosell`;
CREATE TABLE `list_mgmt_nosell` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `email` varchar(250) NOT NULL default '',
  `target_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`email`,`target_id`),
  KEY `idx_date` (`date_created`)
);

--
-- Table structure for table `list_revision_values`
--

DROP TABLE IF EXISTS `list_revision_values`;
CREATE TABLE `list_revision_values` (
  `list_id` int(11) unsigned NOT NULL default '0',
  `revision_id` int(11) unsigned NOT NULL default '0',
  `value_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`list_id`,`revision_id`,`value_id`)
);

--
-- Table structure for table `list_revisions`
--

DROP TABLE IF EXISTS `list_revisions`;
CREATE TABLE `list_revisions` (
  `list_id` int(11) unsigned NOT NULL default '0',
  `revision_id` int(11) unsigned NOT NULL,
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `status` enum('ACTIVE','INACTIVE') NOT NULL default 'INACTIVE',
  PRIMARY KEY  (`list_id`,`revision_id`)
);

--
-- Table structure for table `list_values`
--

DROP TABLE IF EXISTS `list_values`;
CREATE TABLE `list_values` (
  `value_id` int(11) unsigned NOT NULL,
  `value` varchar(255) NOT NULL default '',
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`value_id`)
);

--
-- Table structure for table `lists`
--

DROP TABLE IF EXISTS `lists`;
CREATE TABLE `lists` (
  `list_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `field_name` varchar(255) NOT NULL default '',
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `description` text NOT NULL,
  `loan_action` text NOT NULL,
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`list_id`)
);

--
-- Table structure for table `loan_action`
--

DROP TABLE IF EXISTS `loan_action`;
CREATE TABLE `loan_action` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_modified` timestamp NOT NULL default '0000-00-00 00:00:00',
  `name` varchar(30) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `status` enum('UNSYNCHED','SYNCHED') NOT NULL default 'UNSYNCHED'
);

--
-- Table structure for table `loan_note`
--

DROP TABLE IF EXISTS `loan_note`;
CREATE TABLE `loan_note` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `estimated_fund_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `actual_fund_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `fund_amount` mediumint(8) unsigned NOT NULL default '0',
  `num_payments` tinyint(4) unsigned NOT NULL default '0',
  `apr` float unsigned default NULL,
  `finance_charge` float unsigned default NULL,
  `total_payments` float unsigned default NULL,
  `estimated_payoff_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`application_id`)
);

--
-- Table structure for table `login`
--

DROP TABLE IF EXISTS `login`;
CREATE TABLE `login` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `login` varchar(50) default NULL,
  `password` varchar(255) default NULL,
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`application_id`),
  UNIQUE KEY `idx_login` (`login`)
);

--
-- Table structure for table `no_checks_ssn_list`
--

DROP TABLE IF EXISTS `no_checks_ssn_list`;
CREATE TABLE `no_checks_ssn_list` (
  `social_security_number` varchar(15) NOT NULL,
  PRIMARY KEY  (`social_security_number`)
);

--
-- Table structure for table `paydate`
--

DROP TABLE IF EXISTS `paydate`;
CREATE TABLE `paydate` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `paydate_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL default '0',
  `paydate_model_id` enum('DW','DWPD','DWDM','WWDW','DM','DMDM','WDW') default NULL,
  `day_of_week` int(11) default NULL,
  `next_paydate` date default NULL,
  `day_of_month_1` int(11) default NULL,
  `day_of_month_2` int(11) default NULL,
  `week_1` int(11) default NULL,
  `week_2` int(11) default NULL,
  `accuracy_warning` smallint(6) default NULL,
  PRIMARY KEY  (`paydate_id`),
  KEY `idx_app_id` (`application_id`)
);

--
-- Table structure for table `personal_contact`
--

DROP TABLE IF EXISTS `personal_contact`;
CREATE TABLE `personal_contact` (
  `contact_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned NOT NULL default '0',
  `full_name` varchar(250) NOT NULL default '',
  `phone` varchar(250) NOT NULL default '',
  `relationship` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`contact_id`),
  KEY `application_id` (`application_id`)
);

--
-- Table structure for table `personal_encrypted`
--

DROP TABLE IF EXISTS `personal_encrypted`;
CREATE TABLE `personal_encrypted` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `first_name` varchar(250) NOT NULL default '',
  `middle_name` varchar(250) NOT NULL default '',
  `last_name` varchar(75) NOT NULL default '',
  `home_phone` varchar(250) default '',
  `cell_phone` varchar(250) default '',
  `fax_phone` varchar(250) default '',
  `email` varchar(250) NOT NULL default '',
  `alt_email` varchar(250) default NULL,
  `date_of_birth` varchar(250) NOT NULL default '0000-00-00',
  `contact_id_1` int(10) unsigned NOT NULL default '0',
  `contact_id_2` int(10) unsigned NOT NULL default '0',
  `social_security_number` varchar(250) NOT NULL default '',
  `drivers_license_number` varchar(250) default NULL,
  `best_call_time` enum('MORNING','AFTERNOON','EVENING') default NULL,
  `drivers_license_state` char(2) default NULL,
  `email_agent_created` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `military` enum('n/a','no','yes') default 'n/a',
  PRIMARY KEY  (`application_id`),
  KEY `idx_home_phone` (`home_phone`(10)),
  KEY `idx_ssn` (`social_security_number`(12)),
  KEY `idx_last_name` (`last_name`(12)),
  KEY `idx_email` (`email`(15)),
  KEY `idx_drivers` (`drivers_license_number`(10)),
  KEY `idx_cell_phone` (`cell_phone`(10))
);

--
-- Table structure for table `residence`
--

DROP TABLE IF EXISTS `residence`;
CREATE TABLE `residence` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `residence_type` enum('RENT','OWN') default NULL,
  `residence_start_date` date default NULL,
  `address_1` varchar(250) NOT NULL default '',
  `apartment` varchar(250) NOT NULL default '',
  `city` varchar(250) NOT NULL default '',
  `state` varchar(250) NOT NULL default '',
  `zip` varchar(250) NOT NULL default '',
  `address_2` varchar(100) default '',
  `ca_resident_agree` smallint(6) default NULL,
  `country` varchar(250) NOT NULL default '',
  `county` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`application_id`)
);

--
-- Table structure for table `rule_set_component_parm_value`
--

DROP TABLE IF EXISTS `rule_set_component_parm_value`;
CREATE TABLE `rule_set_component_parm_value` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `agent_id` int(10) unsigned NOT NULL default '0',
  `rule_set_id` int(10) unsigned NOT NULL default '0',
  `rule_component_id` int(10) unsigned NOT NULL default '0',
  `rule_component_parm_id` int(10) unsigned NOT NULL default '0',
  `parm_value` text NOT NULL,
  PRIMARY KEY  (`rule_set_id`,`rule_component_id`,`rule_component_parm_id`)
);

--
-- Table structure for table `rules`
--

DROP TABLE IF EXISTS `rules`;
CREATE TABLE `rules` (
  `date_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `rule_id` int(10) unsigned NOT NULL,
  `target_id` int(10) unsigned NOT NULL default '0',
  `weekends` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `non_dates` mediumtext NOT NULL,
  `bank_account_type` enum('CHECKING','SAVINGS') default NULL,
  `minimum_income` int(10) unsigned NOT NULL default '0',
  `income_direct_deposit` enum('TRUE','FALSE') default NULL,
  `excluded_states` text,
  `restricted_states` text,
  `income_frequency` text,
  `force_promo_id` text,
  `force_site_id` text,
  `status` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `username` varchar(100) NOT NULL default '',
  `state_id_required` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `state_issued_id_required` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `minimum_recur` smallint(5) unsigned NOT NULL default '0',
  `income_type` enum('BOTH','BENEFITS','EMPLOYMENT') NOT NULL default 'BOTH',
  `datax_idv` enum('TRUE','FALSE') default NULL,
  `excluded_zips` longtext NOT NULL,
  `suppression_lists` text,
  `vendor_qualify_post` enum('TRUE','FALSE') default NULL,
  `verify_post_type` enum('XML','HTTP') NOT NULL default 'XML',
  `operating_hours` mediumtext NOT NULL,
  `minimum_age` tinyint(3) unsigned NOT NULL default '0',
  `identical_phone_numbers` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `identical_work_cell_numbers` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `paydate_minimum` tinyint(3) unsigned NOT NULL default '0',
  `filter` text,
  `withheld_targets` text,
  `dd_check` tinyint(4) NOT NULL default '0',
  `list_mgmt_nosell` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `required_references` tinyint(3) unsigned NOT NULL default '0',
  `reference_data` text,
  `military` enum('ALLOW','DENY','ONLY') default 'DENY',
  `post_url` longtext NOT NULL,
  `run_fraud` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `frequency_decline` text,
  `residence_length` int(10) unsigned NOT NULL default '0',
  `employer_length` int(10) unsigned NOT NULL default '0',
  `min_loan_amount_requested` int(10) unsigned NOT NULL default '0',
  `max_loan_amount_requested` int(10) unsigned NOT NULL default '0',
  `residence_type` enum('RENT','OWN') default NULL,
  PRIMARY KEY  (`rule_id`),
  KEY `target_id` (`target_id`)
);

--
-- Table structure for table `session_site`
--

DROP TABLE IF EXISTS `session_site`;
CREATE TABLE `session_site` (
  `session_id` varchar(33) NOT NULL default '',
  `modifed_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `created_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `compression` enum('none','gz','bz') NOT NULL default 'none',
  `session_info` mediumblob NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `created_date` (`created_date`),
  KEY `modifed_date` (`modifed_date`)
);

--
-- Table structure for table `sms_remove`
--

DROP TABLE IF EXISTS `sms_remove`;
CREATE TABLE `sms_remove` (
  `sms_remove_id` int(10) unsigned NOT NULL,
  `cell_number` char(10) default NULL,
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `status` enum('ACTIVE','INACTIVE') default 'ACTIVE',
  PRIMARY KEY  (`sms_remove_id`),
  KEY `idx_cell_number` (`cell_number`(5))
);

--
-- Table structure for table `soap_data_log`
--

DROP TABLE IF EXISTS `soap_data_log`;
CREATE TABLE `soap_data_log` (
  `id` int(11) NOT NULL,
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `unique_id` varchar(32) default NULL,
  `email` varchar(255) NOT NULL default '',
  `remote_site` varchar(255) NOT NULL default '',
  `data` text NOT NULL,
  `elapsed` decimal(7,6) default NULL,
  `type` enum('SOAP_REQUEST','REQUEST','RESPONSE','SOAP_RESPONSE') default NULL,
  `encrypted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `unique_id` (`unique_id`),
  KEY `email` (`email`),
  KEY `idx_remote_site` (`remote_site`(10))
);

--
-- Table structure for table `soap_redirect_links`
--

DROP TABLE IF EXISTS `soap_redirect_links`;
CREATE TABLE `soap_redirect_links` (
  `redirect_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned default NULL,
  `redirect_link` varchar(255) default NULL,
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `random_string` varchar(255) default NULL,
  PRIMARY KEY  (`redirect_id`)
);

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
  `bb_d1_agree` int(10) unsigned NOT NULL default '0',
  `bb_mct3` int(10) unsigned NOT NULL default '0',
  `bb_mct1` int(10) unsigned NOT NULL default '0',
  `bb_vp2` int(10) unsigned NOT NULL default '0',
  `bb_vp3` int(10) unsigned NOT NULL default '0',
  `bb_vp4` int(10) NOT NULL default '0',
  `bb_fb` int(10) NOT NULL default '0',
  `bb_ntl` int(10) unsigned NOT NULL default '0',
  `bb_vp5` int(10) unsigned NOT NULL default '0',
  `bb_vp6` int(10) unsigned NOT NULL default '0',
  `bb_cwf` int(10) unsigned NOT NULL default '0',
  `bb_mtfc` int(10) unsigned NOT NULL default '0',
  `bb_cg` int(10) unsigned NOT NULL default '0',
  `bb_pru` int(10) unsigned NOT NULL default '0',
  `bb_vp2_t4` int(10) unsigned NOT NULL default '0',
  `bb_ct4u` int(10) unsigned NOT NULL default '0',
  `bb_vp7` int(10) unsigned NOT NULL default '0',
  `bb_vp8` int(10) unsigned NOT NULL default '0',
  `bb_vp9` int(10) unsigned NOT NULL default '0',
  `bb_tsstest` int(10) unsigned default '0',
  `bb_sun` int(10) unsigned NOT NULL default '0',
  `bb_sun2` int(10) unsigned NOT NULL default '0',
  `bb_trt` int(10) unsigned NOT NULL default '0',
  `bb_vp13` int(10) unsigned NOT NULL default '0',
  `bb_bmg172` int(10) unsigned NOT NULL default '0',
  `bb_vp11` int(10) unsigned NOT NULL default '0',
  `bb_vp12` int(10) unsigned NOT NULL default '0',
  `bb_vp1_5` int(10) unsigned NOT NULL default '0',
  `bb_cgdd` int(10) unsigned NOT NULL default '0',
  `bb_ezm4` int(10) unsigned NOT NULL default '0',
  `bb_trtdd` int(10) unsigned NOT NULL default '0',
  `bb_trtdd2` int(10) unsigned NOT NULL default '0',
  `bb_pdo1` int(10) unsigned NOT NULL default '0',
  `bb_pdo2` int(10) unsigned NOT NULL default '0',
  `bb_ame` int(10) unsigned NOT NULL default '0',
  `bb_ic_agree` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`stat_limit_date`)
);

--
-- Table structure for table `stat_limits`
--

DROP TABLE IF EXISTS `stat_limits`;
CREATE TABLE `stat_limits` (
  `stat_date` date NOT NULL default '0000-00-00',
  `stat_name` varchar(255) NOT NULL default '',
  `site_id` int(11) unsigned NOT NULL default '0',
  `promo_id` int(11) unsigned NOT NULL default '0',
  `vendor_id` int(11) unsigned NOT NULL default '0',
  `count` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`stat_date`,`stat_name`,`site_id`,`promo_id`,`vendor_id`)
);

--
-- Table structure for table `status_history`
--

DROP TABLE IF EXISTS `status_history`;
CREATE TABLE `status_history` (
  `status_history_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned NOT NULL default '0',
  `application_status_id` int(10) unsigned NOT NULL default '0',
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`status_history_id`),
  KEY `idx_app` (`application_id`),
  KEY `idx_status_id` (`application_status_id`),
  KEY `idx_date_created` (`date_created`)
);

--
-- Table structure for table `target`
--

DROP TABLE IF EXISTS `target`;
CREATE TABLE `target` (
  `date_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `target_id` int(10) unsigned NOT NULL,
  `name` char(255) NOT NULL default '',
  `property_short` char(10) NOT NULL default '',
  `phone_number` char(15) NOT NULL default '',
  `email_address` char(255) NOT NULL default '',
  `url` char(255) NOT NULL default '',
  `status` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `client_id` int(10) unsigned NOT NULL default '0',
  `username` char(100) NOT NULL default '',
  `deleted` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `tier_id` int(10) unsigned NOT NULL default '0',
  `parent_target_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`target_id`),
  KEY `client_id` (`client_id`),
  KEY `tier_id` (`tier_id`),
  KEY `property_short` (`property_short`),
  KEY `parent_target_id` (`parent_target_id`)
);

--
-- Table structure for table `target_stat`
--

DROP TABLE IF EXISTS `target_stat`;
CREATE TABLE `target_stat` (
  `target_stat_id` int(10) unsigned NOT NULL,
  `target_stat_date` date NOT NULL default '0000-00-00',
  `target_id` int(10) NOT NULL default '0',
  `stat_count` int(10) NOT NULL default '0',
  PRIMARY KEY  (`target_stat_id`)
);

--
-- Table structure for table `tier`
--

DROP TABLE IF EXISTS `tier`;
CREATE TABLE `tier` (
  `date_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `tier_id` int(10) unsigned NOT NULL,
  `tier_number` tinyint(3) unsigned NOT NULL default '0',
  `name` char(100) NOT NULL default '',
  `weight_type` enum('AMOUNT','PERCENT','PRIORITY') default 'AMOUNT',
  `status` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  PRIMARY KEY  (`tier_id`),
  UNIQUE KEY `tier_number` (`tier_number`)
);

--
-- Table structure for table `trendex_log`
--

DROP TABLE IF EXISTS `trendex_log`;
CREATE TABLE `trendex_log` (
  `trendex_log_id` int(10) unsigned NOT NULL COMMENT 'Unique identifier for log records',
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'Date and time log record was created',
  `application_id` int(10) unsigned default NULL COMMENT 'Foreign key link to application',
  `template` varchar(50) default NULL COMMENT 'template identifier for email template',
  `message_id` int(10) unsigned default NULL COMMENT 'Trendex message identifier.  Null if message queuing failed',
  `email` varchar(64) default NULL,
  `detail` varchar(1024) default NULL COMMENT 'Detailed information regarding the trendex attempt.  Usually contains error messages',
  PRIMARY KEY  (`trendex_log_id`),
  KEY `email` (`email`),
  KEY `application_id` (`application_id`),
  KEY `date_created` (`date_created`)
);

--
-- Table structure for table `vehicle`
--

DROP TABLE IF EXISTS `vehicle`;
CREATE TABLE `vehicle` (
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `application_id` int(10) unsigned NOT NULL default '0',
  `vin` varchar(17) NOT NULL default '',
  `license_plate` varchar(10) NOT NULL default '',
  `make` varchar(25) NOT NULL default '',
  `model` varchar(25) NOT NULL default '',
  `series` varchar(25) NOT NULL default '',
  `style` varchar(25) NOT NULL default '',
  `color` varchar(25) NOT NULL default '',
  `engine` varchar(10) NOT NULL default '',
  `keywords` varchar(55) NOT NULL default '',
  `year` smallint(5) unsigned NOT NULL default '0',
  `mileage` mediumint(8) unsigned NOT NULL default '0',
  `value` decimal(7,2) default NULL,
  `title_state` char(2) NOT NULL default '',
  PRIMARY KEY  (`application_id`),
  KEY `idx_vehicle_vin` (`vin`),
  KEY `idx_vehicle_plate` (`license_plate`)
);

--
-- Table structure for table `vendor_decline_freq`
--

DROP TABLE IF EXISTS `vendor_decline_freq`;
CREATE TABLE `vendor_decline_freq` (
  `vendor_decline_id` int(10) unsigned NOT NULL,
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `client_email` varchar(80) NOT NULL default '',
  `declined_sum` int(3) unsigned NOT NULL default '0',
  `accept_property_short` varchar(10) NOT NULL default '',
  `application_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`vendor_decline_id`),
  KEY `idx_client_email` (`client_email`(16)),
  KEY `idx_application_id` (`application_id`),
  KEY `idx_target` (`accept_property_short`),
  KEY `idx_date_target` (`date_created`,`accept_property_short`)
);

--
-- Table structure for table `zip_lookup`
--

DROP TABLE IF EXISTS `zip_lookup`;
CREATE TABLE `zip_lookup` (
  `zip_code` varchar(5) NOT NULL default '00000',
  `city` varchar(128) NOT NULL default '',
  `state` char(2) NOT NULL default '',
  `tz` tinyint(3) unsigned NOT NULL default '0',
  `dst` enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (`zip_code`,`city`,`tz`)
);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-02-24 18:13:18
