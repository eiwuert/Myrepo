-- MySQL dump 10.10
--
-- Host: analytics.dx.tss    Database: impact_analysis
-- ------------------------------------------------------
-- Server version	5.0.44sp1-enterprise-gpl-log

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
-- Table structure for table `batch`
--

DROP TABLE IF EXISTS `batch`;
CREATE TABLE `batch` (
  `batch_id` int(10) unsigned NOT NULL auto_increment,
  `company_id` int(10) unsigned NOT NULL default '0',
  `date_begin` int(10) unsigned NOT NULL default '0',
  `date_end` int(10) unsigned default NULL,
  PRIMARY KEY  (`batch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=619 DEFAULT CHARSET=latin1;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
CREATE TABLE `company` (
  `company_id` int(10) unsigned NOT NULL auto_increment,
  `name_short` varchar(5) NOT NULL default '',
  `originating_system` enum('cashline','ecash_30') NOT NULL default 'cashline',
  PRIMARY KEY  (`company_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  `customer_id` int(10) unsigned NOT NULL auto_increment,
  `company_id` int(10) unsigned NOT NULL default '0',
  `application_id` int(10) unsigned default NULL,
  `cashline_id` int(10) unsigned default NULL,
  `ssn` varchar(9) default NULL,
  `name_last` varchar(70) NOT NULL default '',
  `name_first` varchar(50) NOT NULL default '',
  `name_middle` varchar(50) NOT NULL default '',
  `phone_home` varchar(10) NOT NULL default '',
  `phone_cell` varchar(10) NOT NULL default '',
  `phone_work` varchar(10) NOT NULL default '',
  `employer_name` varchar(100) NOT NULL default '',
  `address_street` varchar(100) NOT NULL default '',
  `address_unit` varchar(30) NOT NULL default '',
  `address_city` varchar(100) NOT NULL default '',
  `address_state` char(2) NOT NULL default '',
  `address_zipcode` varchar(9) NOT NULL default '',
  `drivers_license` varchar(100) NOT NULL default '',
  `ip_address` varchar(40) default NULL,
  `email_address` varchar(255) NOT NULL default '',
  `date_origination` date NOT NULL default '0000-00-00',
  `dob` date NOT NULL default '0000-00-00',
  `pay_frequency` enum('weekly','twice_monthly','bi_weekly','monthly') NOT NULL default 'weekly',
  `income_monthly` decimal(7,2) NOT NULL default '0.00',
  `bank_aba` varchar(9) NOT NULL default '',
  `bank_account` varchar(17) NOT NULL default '',
  PRIMARY KEY  (`customer_id`),
  KEY `idx_application_id` (`application_id`),
  KEY `idx_cashline_id` (`cashline_id`),
  KEY `idx_ssn` (`ssn`),
  KEY `idx_aba` (`bank_aba`)
) ENGINE=MyISAM AUTO_INCREMENT=23233604 DEFAULT CHARSET=latin1;

--
-- Table structure for table `loan`
--

DROP TABLE IF EXISTS `loan`;
CREATE TABLE `loan` (
  `loan_id` int(10) unsigned NOT NULL auto_increment,
  `application_id` int(10) unsigned default NULL,
  `customer_id` int(10) unsigned NOT NULL default '0',
  `company_id` int(10) unsigned NOT NULL default '0',
  `status_id` int(10) unsigned NOT NULL default '0',
  `date_advance` date NOT NULL default '0000-00-00',
  `fund_amount` decimal(7,2) NOT NULL default '0.00',
  `amount_paid` decimal(7,2) NOT NULL default '0.00',
  `principal_paid` decimal(7,2) NOT NULL default '0.00',
  `fees_accrued` decimal(7,2) NOT NULL default '0.00',
  `fees_paid` decimal(7,2) NOT NULL default '0.00',
  `loan_balance` decimal(7,2) NOT NULL default '0.00',
  `first_return_pay_cycle` int(10) unsigned default NULL,
  `current_cycle` int(10) unsigned NOT NULL default '0',
  `loan_number` int(10) unsigned NOT NULL default '0',
  `date_loan_paid` date default NULL,
  `first_return_code` varchar(20) default NULL,
  `first_return_msg` varchar(150) default NULL,
  `first_return_date` date default NULL,
  `last_return_code` varchar(20) default NULL,
  `last_return_msg` varchar(150) default NULL,
  `last_return_date` date default NULL,
  `promo_id` varchar(10) default NULL,
  `model` enum('fund','fund_paydown','fund_payout') default 'fund',
  `previous_model` enum('fund','fund_paydown','fund_payout') default 'fund',
  `portfolio_tag` varchar(255) default '',
  PRIMARY KEY  (`loan_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_profit_report` (`company_id`,`date_advance`),
  KEY `idx_application` (`application_id`)
) ENGINE=MyISAM AUTO_INCREMENT=22846920 DEFAULT CHARSET=latin1;

--
-- Table structure for table `loan_performance`
--

DROP TABLE IF EXISTS `loan_performance`;
CREATE TABLE `loan_performance` (
  `loan_id` int(10) unsigned NOT NULL default '0',
  `company_id` int(10) unsigned NOT NULL default '0',
  `is_funded` tinyint(1) NOT NULL default '0',
  `is_active` tinyint(1) NOT NULL default '0',
  `is_paidout` tinyint(1) NOT NULL default '0',
  `is_baddebt` tinyint(1) NOT NULL default '0',
  `net_balance` decimal(7,2) NOT NULL default '0.00',
  `principal_and_fees` decimal(7,2) NOT NULL default '0.00',
  `baddebt_principal` decimal(7,2) NOT NULL default '0.00',
  `baddebt_fees` decimal(7,2) NOT NULL default '0.00',
  `baddebt_paid_principal_and_fees` decimal(7,2) NOT NULL default '0.00',
  `baddebt_principal_and_fees` decimal(7,2) NOT NULL default '0.00',
  `overhead_cost` decimal(7,2) default NULL,
  `acquisition_cost` decimal(7,2) default NULL,
  `cost` decimal(7,2) NOT NULL default '0.00',
  `profit` decimal(7,2) NOT NULL default '0.00',
  PRIMARY KEY  (`loan_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `quickcheck`
--

DROP TABLE IF EXISTS `quickcheck`;
CREATE TABLE `quickcheck` (
  `quickcheck_id` int(10) unsigned NOT NULL auto_increment,
  `company_id` int(10) unsigned NOT NULL default '0',
  `loan_id` int(10) unsigned NOT NULL default '0',
  `date_batched` date NOT NULL default '0000-00-00',
  `date_returned` date default NULL,
  `amount` decimal(7,2) NOT NULL default '0.00',
  `status` enum('pending','failed','completed') NOT NULL default 'pending',
  PRIMARY KEY  (`quickcheck_id`),
  KEY `idx_loan_id` (`loan_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `status`
--

DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `status_id` int(10) unsigned NOT NULL auto_increment,
  `name_short` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`status_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

