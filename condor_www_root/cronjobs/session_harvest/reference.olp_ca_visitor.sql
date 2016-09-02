-- MySQL dump 9.11
--
-- Host: ds001.ibm.tss    Database: olp_ca_visitor
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
) TYPE=MyISAM;

--
-- Table structure for table `agent`
--

DROP TABLE IF EXISTS `agent`;
CREATE TABLE `agent` (
  `agent_id` int(10) unsigned NOT NULL auto_increment,
  `full_name` varchar(250) NOT NULL default '',
  `login` varchar(250) NOT NULL default '',
  `modified_date` timestamp(14) NOT NULL,
  `login_expire_date` timestamp(14) NOT NULL default '00000000000000',
  `account_expire_date` timestamp(14) NOT NULL default '00000000000000',
  `password_expire_date` timestamp(14) NOT NULL default '00000000000000',
  `hash_pass` varchar(250) NOT NULL default '',
  `hash_temp` varchar(250) NOT NULL default '',
  `active` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `access_level` tinyint(3) unsigned NOT NULL default '0',
  `access_type` enum('CUSTOMER','VENDOR','TSS') NOT NULL default 'CUSTOMER',
  PRIMARY KEY  (`agent_id`),
  UNIQUE KEY `login` (`login`)
) TYPE=MyISAM;

--
-- Table structure for table `agent_touch`
--

DROP TABLE IF EXISTS `agent_touch`;
CREATE TABLE `agent_touch` (
  `agent_id` int(11) NOT NULL default '0',
  `application_id` int(11) NOT NULL default '0',
  `touched_date` timestamp(14) NOT NULL,
  `action` longtext NOT NULL,
  KEY `application_id` (`application_id`),
  KEY `agent_id` (`agent_id`)
) TYPE=MyISAM;

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
  PRIMARY KEY  (`application_id`),
  KEY `type_idx` (`type`),
  KEY `idx_sess_id` (`session_id`(6)),
  KEY `idx_created_data` (`created_date`),
  KEY `idx_app_id` (`application_id`)
) TYPE=MyISAM;

--
-- Table structure for table `authentication`
--

DROP TABLE IF EXISTS `authentication`;
CREATE TABLE `authentication` (
  `authentication_id` int(11) NOT NULL auto_increment,
  `date_modified` timestamp(14) NOT NULL,
  `date_created` timestamp(14) NOT NULL default '00000000000000',
  `application_id` int(11) NOT NULL default '0',
  `authentication_source_id` int(11) NOT NULL default '0',
  `sent_package` text NOT NULL,
  `received_package` text NOT NULL,
  `score` varchar(100) NOT NULL default '',
  `flags` text,
  `audit_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`authentication_id`),
  KEY `idx_created_date` (`date_created`),
  KEY `idx_app_id` (`application_id`)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
CREATE TABLE `comment` (
  `comment_id` int(10) unsigned NOT NULL auto_increment,
  `agent_id` int(10) unsigned NOT NULL default '0',
  `application_id` int(11) unsigned NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `comment` longtext NOT NULL,
  `comment_type` enum('PUBLIC','PRIVATE') NOT NULL default 'PRIVATE',
  `comment_by` enum('CSR','LPR','CUSTOMER') NOT NULL default 'LPR',
  PRIMARY KEY  (`comment_id`),
  KEY `application_id` (`application_id`),
  KEY `agent_id` (`agent_id`)
) TYPE=MyISAM;

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
-- Table structure for table `data_lock`
--

DROP TABLE IF EXISTS `data_lock`;
CREATE TABLE `data_lock` (
  `agent_id` int(10) unsigned NOT NULL default '0',
  `application_id` int(10) unsigned NOT NULL default '0',
  `open_stamp` timestamp(14) NOT NULL,
  UNIQUE KEY `application_id` (`application_id`),
  UNIQUE KEY `agent_id` (`agent_id`)
) TYPE=MyISAM;

--
-- Table structure for table `document`
--

DROP TABLE IF EXISTS `document`;
CREATE TABLE `document` (
  `document_id` int(10) unsigned NOT NULL auto_increment,
  `application_id` int(10) unsigned NOT NULL default '0',
  `document_list_id` int(10) unsigned NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `alt_display_name` varchar(250) default NULL,
  `dnis` varchar(250) NOT NULL default '',
  `tiff` varchar(250) NOT NULL default '',
  `sent` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `sent_date` timestamp(14) NOT NULL default '00000000000000',
  `sent_by_id` int(10) unsigned NOT NULL default '0',
  `sent_method` enum('FAX','EMAIL','BOTH') default NULL,
  `received` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `received_date` timestamp(14) NOT NULL default '00000000000000',
  `received_by_id` int(10) unsigned NOT NULL default '0',
  `verified` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `verified_date` timestamp(14) NOT NULL default '00000000000000',
  `verified_by_id` int(10) unsigned NOT NULL default '0',
  `verified_properties` text,
  PRIMARY KEY  (`document_id`),
  KEY `document_list_id` (`document_list_id`),
  KEY `dnis` (`dnis`),
  KEY `tiff` (`tiff`),
  KEY `application_id` (`application_id`)
) TYPE=MyISAM;

--
-- Table structure for table `document_answer_list`
--

DROP TABLE IF EXISTS `document_answer_list`;
CREATE TABLE `document_answer_list` (
  `answer_id` int(10) unsigned NOT NULL auto_increment,
  `doc_question_id` int(10) unsigned NOT NULL default '0',
  `document_id` int(10) unsigned NOT NULL default '0',
  `status` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  PRIMARY KEY  (`answer_id`),
  KEY `doc_question_id` (`doc_question_id`,`document_id`)
) TYPE=MyISAM;

--
-- Table structure for table `document_list`
--

DROP TABLE IF EXISTS `document_list`;
CREATE TABLE `document_list` (
  `document_list_id` int(10) unsigned NOT NULL auto_increment,
  `display_name` varchar(250) NOT NULL default '',
  `required` enum('TRUE','FALSE') NOT NULL default 'TRUE',
  `file_name` varchar(250) NOT NULL default '',
  `verify_properties` text,
  PRIMARY KEY  (`document_list_id`)
) TYPE=MyISAM;

--
-- Table structure for table `document_question`
--

DROP TABLE IF EXISTS `document_question`;
CREATE TABLE `document_question` (
  `doc_question_id` int(10) unsigned NOT NULL auto_increment,
  `document_list_id` int(10) unsigned NOT NULL default '0',
  `question` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`doc_question_id`),
  KEY `document_list_id` (`document_list_id`)
) TYPE=MyISAM;

--
-- Table structure for table `email_def`
--

DROP TABLE IF EXISTS `email_def`;
CREATE TABLE `email_def` (
  `email_id` int(10) unsigned NOT NULL auto_increment,
  `ole_event` char(255) NOT NULL default '',
  `start_date` timestamp(14) NOT NULL,
  PRIMARY KEY  (`email_id`),
  UNIQUE KEY `ole_event` (`ole_event`)
) TYPE=MyISAM COMMENT='a definition for each type of transactional email\nwe may sen';

--
-- Table structure for table `email_log`
--

DROP TABLE IF EXISTS `email_log`;
CREATE TABLE `email_log` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `email_def_id` int(10) unsigned NOT NULL default '0',
  `time_sent` timestamp(14) NOT NULL,
  KEY `sendme` (`email_def_id`,`time_sent`),
  KEY `app_id` (`application_id`)
) TYPE=MyISAM COMMENT='a record of each transactional email we send.\nthis table wil';

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
) TYPE=MyISAM;

--
-- Table structure for table `img_export`
--

DROP TABLE IF EXISTS `img_export`;
CREATE TABLE `img_export` (
  `order_date` char(19) binary default NULL,
  `type` enum('VISITOR','QUALIFIED','PROSPECT','APPLICANT','CUSTOMER','DOA','PENDING') NOT NULL default 'VISITOR',
  `first_name` char(250) NOT NULL default '',
  `last_name` char(75) NOT NULL default '',
  `email` char(250) NOT NULL default '',
  `home_phone` char(250) default '',
  `address_1` char(250) NOT NULL default '',
  `city` char(250) NOT NULL default '',
  `state` char(250) NOT NULL default '',
  `zip` char(250) NOT NULL default ''
) TYPE=MyISAM;

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
) TYPE=MyISAM;

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
) TYPE=MyISAM;

--
-- Table structure for table `paperless_queue`
--

DROP TABLE IF EXISTS `paperless_queue`;
CREATE TABLE `paperless_queue` (
  `paperless_queue_id` int(11) NOT NULL auto_increment,
  `modified_date` timestamp(14) NOT NULL,
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `application_id` int(11) NOT NULL default '0',
  `printed_flag` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  `status` enum('NEW','PRINTED','REMOVED','UNCONFIRMED') NOT NULL default 'NEW',
  `app_updates` longtext,
  `paydate_warning` enum('TRUE','FALSE') NOT NULL default 'FALSE',
  PRIMARY KEY  (`paperless_queue_id`),
  KEY `idx_app_id` (`application_id`)
) TYPE=MyISAM;

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
  `doc_send_method` enum('FAX','EMAIL','DIRECT') default NULL,
  PRIMARY KEY  (`application_id`),
  KEY `idx_ssn` (`social_security_number`(9)),
  KEY `idx_last_name` (`last_name`(10))
) TYPE=MyISAM;

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
) TYPE=MyISAM;

--
-- Table structure for table `residence`
--

DROP TABLE IF EXISTS `residence`;
CREATE TABLE `residence` (
  `application_id` int(10) unsigned NOT NULL default '0',
  `residence_type` enum('RENT','OWN') default NULL,
  `length_of_residence` mediumint(8) unsigned default NULL,
  `address_1` varchar(250) NOT NULL default '',
  `apartment` varchar(250) NOT NULL default '',
  `city` varchar(250) NOT NULL default '',
  `state` varchar(250) NOT NULL default '',
  `zip` varchar(250) NOT NULL default '',
  `address_2` varchar(100) default '',
  PRIMARY KEY  (`application_id`)
) TYPE=MyISAM;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `session_id` varchar(33) NOT NULL default '',
  `modifed_date` timestamp(14) NOT NULL,
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `session_info` longtext NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `modifed_date` (`modifed_date`)
) TYPE=MyISAM;

--
-- Table structure for table `session_agent`
--

DROP TABLE IF EXISTS `session_agent`;
CREATE TABLE `session_agent` (
  `session_id` varchar(33) NOT NULL default '',
  `modifed_date` timestamp(14) NOT NULL,
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `session_info` longtext NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `modifed_date` (`modifed_date`)
) TYPE=MyISAM;

--
-- Table structure for table `session_site`
--

DROP TABLE IF EXISTS `session_site`;
CREATE TABLE `session_site` (
  `session_id` varchar(33) NOT NULL default '',
  `modifed_date` timestamp(14) NOT NULL,
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `session_info` longtext NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `modifed_date` (`modifed_date`),
  KEY `created_date` (`created_date`)
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
  `confirmed` timestamp(14) NOT NULL default '00000000000000',
  `funded` timestamp(14) NOT NULL default '00000000000000',
  `pulled_prospect` timestamp(14) NOT NULL default '00000000000000',
  `return_customer` timestamp(14) NOT NULL default '00000000000000',
  PRIMARY KEY  (`application_id`),
  KEY `accepted_idx` (`accepted`)
) TYPE=MyISAM;

--
-- Table structure for table `statement`
--

DROP TABLE IF EXISTS `statement`;
CREATE TABLE `statement` (
  `statement_id` int(10) unsigned NOT NULL auto_increment,
  `social_security_nubmer` varchar(9) NOT NULL default '',
  `modified_date` timestamp(14) NOT NULL,
  `created_date` timestamp(14) NOT NULL default '00000000000000',
  `statement_date` timestamp(14) NOT NULL default '00000000000000',
  `statement` longtext NOT NULL,
  PRIMARY KEY  (`statement_id`),
  KEY `social_security_nubmer` (`social_security_nubmer`)
) TYPE=MyISAM;

--
-- Table structure for table `test`
--

DROP TABLE IF EXISTS `test`;
CREATE TABLE `test` (
  `test` varchar(100) NOT NULL default ''
) TYPE=MyISAM;

