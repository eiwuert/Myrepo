# Host: serenity.verihub.com
# Database: datax
# Table: 'ca_check'
# 
CREATE TABLE `ca_check` (
  `custnum` int(11) NOT NULL default '0',
  `payee` varchar(100) NOT NULL default '',
  `check_number` varchar(100) NOT NULL default '',
  `check_date` date NOT NULL default '0000-00-00',
  `check_amount` varchar(100) NOT NULL default '',
  `check_amount_verbage` varchar(255) NOT NULL default '',
  `check_auth_date` date NOT NULL default '0000-00-00',
  `bank_name` varchar(255) NOT NULL default '',
  `bank_address` varchar(255) NOT NULL default '',
  `bank_citystate` varchar(100) NOT NULL default '',
  `bank_routing` varchar(24) NOT NULL default '',
  `bank_account` varchar(24) NOT NULL default '',
  `printed` varchar(100) NOT NULL default '',
  KEY `index` (`custnum`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ca_customer'
# 
CREATE TABLE `ca_customer` (
  `custnum` int(11) default NULL,
  `status` varchar(255) default NULL,
  `ssn` varchar(9) default NULL,
  `ln` varchar(255) default NULL,
  `fn` varchar(255) default NULL,
  `mi` varchar(255) default NULL,
  `phone_home` varchar(12) default NULL,
  `phone_cell` varchar(12) default NULL,
  `phone_work` varchar(12) default NULL,
  `employer_name` varchar(100) default NULL,
  `ad1` varchar(255) default NULL,
  `ad2` varchar(255) default NULL,
  `city` varchar(255) default NULL,
  `st` varchar(255) default NULL,
  `zip` varchar(10) default NULL,
  `email` varchar(255) default NULL,
  `orgdate` varchar(255) default NULL,
  `dob` varchar(255) default NULL,
  `field15` varchar(255) default NULL,
  `payperiod` varchar(255) default NULL,
  `income` float default NULL,
  `field18` varchar(30) default NULL,
  `field19` varchar(30) default NULL,
  `field20` varchar(255) default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `ssn_idx` (`ssn`),
  KEY `stat` (`status`),
  KEY `idx_routing` (`field18`),
  KEY `idx_account` (`field19`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ca_debits'
# 
CREATE TABLE `ca_debits` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `debit_type` enum('PAYDOWN','ACH_RETRY','RETURN_FEE','SERVICE_CHARGE') default NULL,
  `debit_date` date default NULL,
  `debit_amount` int(11) unsigned default NULL,
  `debit_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ca_loan'
# 
CREATE TABLE `ca_loan` (
  `custnum` int(11) default NULL,
  `loan_id` int(11) default NULL,
  `overhead_cost` float default NULL,
  `acquisition_cost` float default NULL,
  `advance` varchar(255) default NULL,
  `advpaid` float default NULL,
  `paydown` varchar(255) default NULL,
  `feesaccrued` varchar(255) default NULL,
  `feespaid` varchar(255) default NULL,
  `balance` int(11) default NULL,
  `netbalance` varchar(255) default NULL,
  `cycle` varchar(255) default NULL,
  `achret` int(11) default NULL,
  `numcycles` varchar(255) default NULL,
  `loannum` int(11) default NULL,
  `advdate` varchar(255) default NULL,
  `date_advance` date NOT NULL default '0000-00-00',
  `paiddate` varchar(255) default NULL,
  `paidflag` varchar(11) default NULL,
  `depchkdate` date default NULL,
  `retchkdate` date default NULL,
  `depchkamt` varchar(255) default NULL,
  `retchkamt` varchar(255) default NULL,
  `first_return_code` varchar(10) default NULL,
  `first_return_msg` varchar(250) default NULL,
  `first_return_date` date default NULL,
  `last_return_code` varchar(10) default NULL,
  `last_return_msg` varchar(250) default NULL,
  `last_return_date` date default NULL,
  `next_due_date` date default NULL,
  `first_due_date` date default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `lid` (`loan_id`),
  KEY `ad` (`advdate`),
  KEY `ln` (`loannum`),
  KEY `dcd` (`depchkdate`),
  KEY `rcd` (`retchkdate`),
  KEY `idx_dadv` (`date_advance`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ca_newest_loan'
# 
CREATE TABLE `ca_newest_loan` (
  `custnum` int(11) default NULL,
  `loan_id` bigint(20) default NULL,
  KEY `cn` (`custnum`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ca_raw_transact'
# 
CREATE TABLE `ca_raw_transact` (
  `custnum` int(11) NOT NULL default '0',
  `transaction_id` int(11) NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `transaction_date` timestamp(14) NOT NULL default '00000000000000',
  `type` varchar(100) NOT NULL default '',
  `amount` float NOT NULL default '0',
  `duedate` date NOT NULL default '0000-00-00',
  `paid` float NOT NULL default '0',
  `datepaid` date NOT NULL default '0000-00-00',
  `balance` float NOT NULL default '0',
  `lastupdate` date NOT NULL default '0000-00-00',
  `payment_history` mediumtext NOT NULL,
  `selectedforpayment` varchar(100) NOT NULL default '',
  `confirmed` enum('Y','N') NOT NULL default 'Y',
  `loandoc` enum('Y','N') NOT NULL default 'Y',
  `paymentamount` float NOT NULL default '0',
  `effectivedate` date NOT NULL default '0000-00-00',
  `nextduedate` date NOT NULL default '0000-00-00',
  `comments` mediumtext NOT NULL,
  `datebankprocessed` date NOT NULL default '0000-00-00',
  `timebankprocessed` timestamp(14) NOT NULL default '00000000000000',
  `datesenttobank` date NOT NULL default '0000-00-00',
  `timesenttobank` timestamp(14) NOT NULL default '00000000000000',
  `approvalflag` varchar(100) NOT NULL default '',
  `loanamountapproved` varchar(100) NOT NULL default '',
  `dateofinquiry` varchar(100) NOT NULL default '',
  `timeofinquiry` varchar(100) NOT NULL default '',
  `trackingnumber` varchar(100) NOT NULL default '',
  `scoresign` varchar(100) NOT NULL default '',
  `score` varchar(100) NOT NULL default '',
  `dateprocessedbybank` varchar(100) NOT NULL default '',
  `timeprocessedbybank` varchar(100) NOT NULL default '',
  `approveddenied` varchar(100) NOT NULL default '',
  `changeflag` varchar(100) NOT NULL default '',
  `notes` varchar(100) NOT NULL default '',
  `loan_id` int(11) default NULL,
  KEY `type` (`type`),
  KEY `custnum` (`custnum`),
  KEY `tdate` (`transaction_date`),
  KEY `lid` (`loan_id`),
  KEY `txid` (`transaction_id`),
  KEY `dp` (`datepaid`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ca_returns'
# 
CREATE TABLE `ca_returns` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `return_date` date default NULL,
  `return_amount` int(11) unsigned default NULL,
  `return_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'd1_check'
# 
CREATE TABLE `d1_check` (
  `custnum` int(11) NOT NULL default '0',
  `payee` varchar(100) NOT NULL default '',
  `check_number` varchar(100) NOT NULL default '',
  `check_date` date NOT NULL default '0000-00-00',
  `check_amount` varchar(100) NOT NULL default '',
  `check_amount_verbage` varchar(255) NOT NULL default '',
  `check_auth_date` date NOT NULL default '0000-00-00',
  `bank_name` varchar(255) NOT NULL default '',
  `bank_address` varchar(255) NOT NULL default '',
  `bank_citystate` varchar(100) NOT NULL default '',
  `bank_routing` varchar(24) NOT NULL default '',
  `bank_account` varchar(24) NOT NULL default '',
  `printed` varchar(100) NOT NULL default '',
  KEY `index` (`custnum`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'd1_customer'
# 
CREATE TABLE `d1_customer` (
  `custnum` int(11) default NULL,
  `status` varchar(255) default NULL,
  `ssn` varchar(9) default NULL,
  `ln` varchar(255) default NULL,
  `fn` varchar(255) default NULL,
  `mi` varchar(255) default NULL,
  `phone_home` varchar(12) default NULL,
  `phone_cell` varchar(12) default NULL,
  `phone_work` varchar(12) default NULL,
  `employer_name` varchar(100) default NULL,
  `ad1` varchar(255) default NULL,
  `ad2` varchar(255) default NULL,
  `city` varchar(255) default NULL,
  `st` varchar(255) default NULL,
  `zip` varchar(10) default NULL,
  `email` varchar(255) default NULL,
  `orgdate` varchar(255) default NULL,
  `dob` varchar(255) default NULL,
  `field15` varchar(255) default NULL,
  `payperiod` varchar(255) default NULL,
  `income` float default NULL,
  `field18` varchar(30) default NULL,
  `field19` varchar(30) default NULL,
  `field20` varchar(255) default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `ssn_idx` (`ssn`),
  KEY `stat` (`status`),
  KEY `idx_routing` (`field18`),
  KEY `idx_account` (`field19`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'd1_debits'
# 
CREATE TABLE `d1_debits` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `debit_type` enum('PAYDOWN','ACH_RETRY','RETURN_FEE','SERVICE_CHARGE') default NULL,
  `debit_date` date default NULL,
  `debit_amount` int(11) unsigned default NULL,
  `debit_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'd1_loan'
# 
CREATE TABLE `d1_loan` (
  `custnum` int(11) default NULL,
  `loan_id` int(11) default NULL,
  `overhead_cost` float default NULL,
  `acquisition_cost` float default NULL,
  `advance` varchar(255) default NULL,
  `advpaid` float default NULL,
  `paydown` varchar(255) default NULL,
  `feesaccrued` varchar(255) default NULL,
  `feespaid` varchar(255) default NULL,
  `balance` int(11) default NULL,
  `netbalance` varchar(255) default NULL,
  `cycle` varchar(255) default NULL,
  `achret` int(11) default NULL,
  `numcycles` varchar(255) default NULL,
  `loannum` int(11) default NULL,
  `advdate` varchar(255) default NULL,
  `date_advance` date NOT NULL default '0000-00-00',
  `paiddate` varchar(255) default NULL,
  `paidflag` varchar(11) default NULL,
  `depchkdate` date default NULL,
  `retchkdate` date default NULL,
  `depchkamt` varchar(255) default NULL,
  `retchkamt` varchar(255) default NULL,
  `first_return_code` varchar(10) default NULL,
  `first_return_msg` varchar(250) default NULL,
  `first_return_date` date default NULL,
  `last_return_code` varchar(10) default NULL,
  `last_return_msg` varchar(250) default NULL,
  `last_return_date` date default NULL,
  `next_due_date` date default NULL,
  `first_due_date` date default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `lid` (`loan_id`),
  KEY `ad` (`advdate`),
  KEY `ln` (`loannum`),
  KEY `dcd` (`depchkdate`),
  KEY `rcd` (`retchkdate`),
  KEY `idx_dadv` (`date_advance`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'd1_newest_loan'
# 
CREATE TABLE `d1_newest_loan` (
  `custnum` int(11) default NULL,
  `loan_id` bigint(20) default NULL,
  KEY `cn` (`custnum`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'd1_raw_transact'
# 
CREATE TABLE `d1_raw_transact` (
  `custnum` int(11) NOT NULL default '0',
  `transaction_id` int(11) NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `transaction_date` timestamp(14) NOT NULL default '00000000000000',
  `type` varchar(100) NOT NULL default '',
  `amount` float NOT NULL default '0',
  `duedate` date NOT NULL default '0000-00-00',
  `paid` float NOT NULL default '0',
  `datepaid` date NOT NULL default '0000-00-00',
  `balance` float NOT NULL default '0',
  `lastupdate` date NOT NULL default '0000-00-00',
  `payment_history` mediumtext NOT NULL,
  `selectedforpayment` varchar(100) NOT NULL default '',
  `confirmed` enum('Y','N') NOT NULL default 'Y',
  `loandoc` enum('Y','N') NOT NULL default 'Y',
  `paymentamount` float NOT NULL default '0',
  `effectivedate` date NOT NULL default '0000-00-00',
  `nextduedate` date NOT NULL default '0000-00-00',
  `comments` mediumtext NOT NULL,
  `datebankprocessed` date NOT NULL default '0000-00-00',
  `timebankprocessed` timestamp(14) NOT NULL default '00000000000000',
  `datesenttobank` date NOT NULL default '0000-00-00',
  `timesenttobank` timestamp(14) NOT NULL default '00000000000000',
  `approvalflag` varchar(100) NOT NULL default '',
  `loanamountapproved` varchar(100) NOT NULL default '',
  `dateofinquiry` varchar(100) NOT NULL default '',
  `timeofinquiry` varchar(100) NOT NULL default '',
  `trackingnumber` varchar(100) NOT NULL default '',
  `scoresign` varchar(100) NOT NULL default '',
  `score` varchar(100) NOT NULL default '',
  `dateprocessedbybank` varchar(100) NOT NULL default '',
  `timeprocessedbybank` varchar(100) NOT NULL default '',
  `approveddenied` varchar(100) NOT NULL default '',
  `changeflag` varchar(100) NOT NULL default '',
  `notes` varchar(100) NOT NULL default '',
  `loan_id` int(11) default NULL,
  KEY `type` (`type`),
  KEY `cnu` (`custnum`),
  KEY `trandate` (`transaction_date`),
  KEY `lid` (`loan_id`),
  KEY `txid` (`transaction_id`),
  KEY `dp` (`datepaid`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'd1_returns'
# 
CREATE TABLE `d1_returns` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `return_date` date default NULL,
  `return_amount` int(11) unsigned default NULL,
  `return_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'pcl_check'
# 
CREATE TABLE `pcl_check` (
  `custnum` int(11) NOT NULL default '0',
  `payee` varchar(100) NOT NULL default '',
  `check_number` varchar(100) NOT NULL default '',
  `check_date` date NOT NULL default '0000-00-00',
  `check_amount` varchar(100) NOT NULL default '',
  `check_amount_verbage` varchar(255) NOT NULL default '',
  `check_auth_date` date NOT NULL default '0000-00-00',
  `bank_name` varchar(255) NOT NULL default '',
  `bank_address` varchar(255) NOT NULL default '',
  `bank_citystate` varchar(100) NOT NULL default '',
  `bank_routing` varchar(24) NOT NULL default '',
  `bank_account` varchar(24) NOT NULL default '',
  `printed` varchar(100) NOT NULL default '',
  KEY `index` (`custnum`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'pcl_customer'
# 
CREATE TABLE `pcl_customer` (
  `custnum` int(11) default NULL,
  `status` varchar(255) default NULL,
  `ssn` varchar(9) default NULL,
  `ln` varchar(255) default NULL,
  `fn` varchar(255) default NULL,
  `mi` varchar(255) default NULL,
  `phone_home` varchar(12) default NULL,
  `phone_cell` varchar(12) default NULL,
  `phone_work` varchar(12) default NULL,
  `employer_name` varchar(100) default NULL,
  `ad1` varchar(255) default NULL,
  `ad2` varchar(255) default NULL,
  `city` varchar(255) default NULL,
  `st` varchar(255) default NULL,
  `zip` varchar(10) default NULL,
  `email` varchar(255) default NULL,
  `orgdate` varchar(255) default NULL,
  `dob` varchar(255) default NULL,
  `field15` varchar(255) default NULL,
  `payperiod` varchar(255) default NULL,
  `income` float default NULL,
  `field18` varchar(30) default NULL,
  `field19` varchar(30) default NULL,
  `field20` varchar(255) default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `ssn_idx` (`ssn`),
  KEY `stat` (`status`),
  KEY `idx_routing` (`field18`),
  KEY `idx_account` (`field19`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'pcl_debits'
# 
CREATE TABLE `pcl_debits` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `debit_type` enum('PAYDOWN','ACH_RETRY','RETURN_FEE','SERVICE_CHARGE') default NULL,
  `debit_date` date default NULL,
  `debit_amount` int(11) unsigned default NULL,
  `debit_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'pcl_loan'
# 
CREATE TABLE `pcl_loan` (
  `custnum` int(11) default NULL,
  `loan_id` int(11) default NULL,
  `overhead_cost` float default NULL,
  `acquisition_cost` float default NULL,
  `advance` varchar(255) default NULL,
  `advpaid` float default NULL,
  `paydown` varchar(255) default NULL,
  `feesaccrued` varchar(255) default NULL,
  `feespaid` varchar(255) default NULL,
  `balance` int(11) default NULL,
  `netbalance` varchar(255) default NULL,
  `cycle` varchar(255) default NULL,
  `achret` int(11) default NULL,
  `numcycles` varchar(255) default NULL,
  `loannum` int(11) default NULL,
  `advdate` varchar(255) default NULL,
  `date_advance` date NOT NULL default '0000-00-00',
  `paiddate` varchar(255) default NULL,
  `paidflag` varchar(11) default NULL,
  `depchkdate` date default NULL,
  `retchkdate` date default NULL,
  `depchkamt` varchar(255) default NULL,
  `retchkamt` varchar(255) default NULL,
  `first_return_code` varchar(10) default NULL,
  `first_return_msg` varchar(250) default NULL,
  `first_return_date` date default NULL,
  `last_return_code` varchar(10) default NULL,
  `last_return_msg` varchar(250) default NULL,
  `last_return_date` date default NULL,
  `next_due_date` date default NULL,
  `first_due_date` date default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `lid` (`loan_id`),
  KEY `ad` (`advdate`),
  KEY `ln` (`loannum`),
  KEY `dcd` (`depchkdate`),
  KEY `rcd` (`retchkdate`),
  KEY `idx_dadv` (`date_advance`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'pcl_newest_loan'
# 
CREATE TABLE `pcl_newest_loan` (
  `custnum` int(11) default NULL,
  `loan_id` bigint(20) default NULL,
  KEY `l_id` (`loan_id`),
  KEY `c_id` (`custnum`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'pcl_raw_transact'
# 
CREATE TABLE `pcl_raw_transact` (
  `custnum` int(11) NOT NULL default '0',
  `transaction_id` int(11) NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `transaction_date` timestamp(14) NOT NULL default '00000000000000',
  `type` varchar(100) NOT NULL default '',
  `amount` float NOT NULL default '0',
  `duedate` date NOT NULL default '0000-00-00',
  `paid` float NOT NULL default '0',
  `datepaid` date NOT NULL default '0000-00-00',
  `balance` float NOT NULL default '0',
  `lastupdate` date NOT NULL default '0000-00-00',
  `payment_history` mediumtext NOT NULL,
  `selectedforpayment` varchar(100) NOT NULL default '',
  `confirmed` enum('Y','N') NOT NULL default 'Y',
  `loandoc` enum('Y','N') NOT NULL default 'Y',
  `paymentamount` float NOT NULL default '0',
  `effectivedate` date NOT NULL default '0000-00-00',
  `nextduedate` date NOT NULL default '0000-00-00',
  `comments` mediumtext NOT NULL,
  `datebankprocessed` date NOT NULL default '0000-00-00',
  `timebankprocessed` timestamp(14) NOT NULL default '00000000000000',
  `datesenttobank` date NOT NULL default '0000-00-00',
  `timesenttobank` timestamp(14) NOT NULL default '00000000000000',
  `approvalflag` varchar(100) NOT NULL default '',
  `loanamountapproved` varchar(100) NOT NULL default '',
  `dateofinquiry` varchar(100) NOT NULL default '',
  `timeofinquiry` varchar(100) NOT NULL default '',
  `trackingnumber` varchar(100) NOT NULL default '',
  `scoresign` varchar(100) NOT NULL default '',
  `score` varchar(100) NOT NULL default '',
  `dateprocessedbybank` varchar(100) NOT NULL default '',
  `timeprocessedbybank` varchar(100) NOT NULL default '',
  `approveddenied` varchar(100) NOT NULL default '',
  `changeflag` varchar(100) NOT NULL default '',
  `notes` varchar(100) NOT NULL default '',
  `loan_id` int(11) default NULL,
  KEY `cid` (`custnum`),
  KEY `type` (`type`),
  KEY `tdate` (`transaction_date`),
  KEY `lid` (`loan_id`),
  KEY `txid` (`transaction_id`),
  KEY `dp` (`datepaid`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'pcl_returns'
# 
CREATE TABLE `pcl_returns` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `return_date` date default NULL,
  `return_amount` int(11) unsigned default NULL,
  `return_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ucl_check'
# 
CREATE TABLE `ucl_check` (
  `custnum` int(11) NOT NULL default '0',
  `payee` varchar(100) NOT NULL default '',
  `check_number` varchar(100) NOT NULL default '',
  `check_date` date NOT NULL default '0000-00-00',
  `check_amount` varchar(100) NOT NULL default '',
  `check_amount_verbage` varchar(255) NOT NULL default '',
  `check_auth_date` date NOT NULL default '0000-00-00',
  `bank_name` varchar(255) NOT NULL default '',
  `bank_address` varchar(255) NOT NULL default '',
  `bank_citystate` varchar(100) NOT NULL default '',
  `bank_routing` varchar(24) NOT NULL default '',
  `bank_account` varchar(24) NOT NULL default '',
  `printed` varchar(100) NOT NULL default '',
  KEY `index` (`custnum`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ucl_customer'
# 
CREATE TABLE `ucl_customer` (
  `custnum` int(11) default NULL,
  `status` varchar(255) default NULL,
  `ssn` varchar(9) default NULL,
  `ln` varchar(255) default NULL,
  `fn` varchar(255) default NULL,
  `mi` varchar(255) default NULL,
  `phone_home` varchar(12) default NULL,
  `phone_cell` varchar(12) default NULL,
  `phone_work` varchar(12) default NULL,
  `employer_name` varchar(100) default NULL,
  `ad1` varchar(255) default NULL,
  `ad2` varchar(255) default NULL,
  `city` varchar(255) default NULL,
  `st` varchar(255) default NULL,
  `zip` varchar(10) default NULL,
  `email` varchar(255) default NULL,
  `orgdate` varchar(255) default NULL,
  `dob` varchar(255) default NULL,
  `field15` varchar(255) default NULL,
  `payperiod` varchar(255) default NULL,
  `income` float default NULL,
  `field18` varchar(30) default NULL,
  `field19` varchar(30) default NULL,
  `field20` varchar(255) default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `ssn_idx` (`ssn`),
  KEY `stat` (`status`),
  KEY `idx_routing` (`field18`),
  KEY `idx_account` (`field19`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ucl_debits'
# 
CREATE TABLE `ucl_debits` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `debit_type` enum('PAYDOWN','ACH_RETRY','RETURN_FEE','SERVICE_CHARGE') default NULL,
  `debit_date` date default NULL,
  `debit_amount` int(11) unsigned default NULL,
  `debit_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ucl_dupeaccts'
# 
CREATE TABLE `ucl_dupeaccts` (
  `ABA` varchar(11) default NULL,
  `ACCT` varchar(11) default NULL,
  `Custs` int(11) default NULL
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ucl_loan'
# 
CREATE TABLE `ucl_loan` (
  `custnum` int(11) default NULL,
  `loan_id` int(11) default NULL,
  `overhead_cost` float default NULL,
  `acquisition_cost` float default NULL,
  `advance` varchar(255) default NULL,
  `advpaid` float default NULL,
  `paydown` varchar(255) default NULL,
  `feesaccrued` varchar(255) default NULL,
  `feespaid` varchar(255) default NULL,
  `balance` int(11) default NULL,
  `netbalance` varchar(255) default NULL,
  `cycle` varchar(255) default NULL,
  `achret` int(11) default NULL,
  `numcycles` varchar(255) default NULL,
  `loannum` int(11) default NULL,
  `advdate` varchar(255) default NULL,
  `date_advance` date NOT NULL default '0000-00-00',
  `paiddate` varchar(255) default NULL,
  `paidflag` varchar(11) default NULL,
  `depchkdate` date default NULL,
  `retchkdate` date default NULL,
  `depchkamt` varchar(255) default NULL,
  `retchkamt` varchar(255) default NULL,
  `first_return_code` varchar(10) default NULL,
  `first_return_msg` varchar(250) default NULL,
  `first_return_date` date default NULL,
  `last_return_code` varchar(10) default NULL,
  `last_return_msg` varchar(250) default NULL,
  `last_return_date` date default NULL,
  `next_due_date` date default NULL,
  `first_due_date` date default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `lid` (`loan_id`),
  KEY `ad` (`advdate`),
  KEY `ln` (`loannum`),
  KEY `dcd` (`depchkdate`),
  KEY `rcd` (`retchkdate`),
  KEY `idx_dadv` (`date_advance`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ucl_newest_loan'
# 
CREATE TABLE `ucl_newest_loan` (
  `custnum` int(11) default NULL,
  `loan_id` bigint(20) default NULL,
  KEY `l_id` (`loan_id`),
  KEY `c_id` (`custnum`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ucl_raw_transact'
# 
CREATE TABLE `ucl_raw_transact` (
  `custnum` int(11) NOT NULL default '0',
  `transaction_id` int(11) NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `transaction_date` timestamp(14) NOT NULL default '00000000000000',
  `type` varchar(100) NOT NULL default '',
  `amount` float NOT NULL default '0',
  `duedate` date NOT NULL default '0000-00-00',
  `paid` float NOT NULL default '0',
  `datepaid` date NOT NULL default '0000-00-00',
  `balance` float NOT NULL default '0',
  `lastupdate` date NOT NULL default '0000-00-00',
  `payment_history` mediumtext NOT NULL,
  `selectedforpayment` varchar(100) NOT NULL default '',
  `confirmed` enum('Y','N') NOT NULL default 'Y',
  `loandoc` enum('Y','N') NOT NULL default 'Y',
  `paymentamount` float NOT NULL default '0',
  `effectivedate` date NOT NULL default '0000-00-00',
  `nextduedate` date NOT NULL default '0000-00-00',
  `comments` mediumtext NOT NULL,
  `datebankprocessed` date NOT NULL default '0000-00-00',
  `timebankprocessed` timestamp(14) NOT NULL default '00000000000000',
  `datesenttobank` date NOT NULL default '0000-00-00',
  `timesenttobank` timestamp(14) NOT NULL default '00000000000000',
  `approvalflag` varchar(100) NOT NULL default '',
  `loanamountapproved` varchar(100) NOT NULL default '',
  `dateofinquiry` varchar(100) NOT NULL default '',
  `timeofinquiry` varchar(100) NOT NULL default '',
  `trackingnumber` varchar(100) NOT NULL default '',
  `scoresign` varchar(100) NOT NULL default '',
  `score` varchar(100) NOT NULL default '',
  `dateprocessedbybank` varchar(100) NOT NULL default '',
  `timeprocessedbybank` varchar(100) NOT NULL default '',
  `approveddenied` varchar(100) NOT NULL default '',
  `changeflag` varchar(100) NOT NULL default '',
  `notes` varchar(100) NOT NULL default '',
  `loan_id` int(11) default NULL,
  KEY `idx1` (`custnum`),
  KEY `idx2` (`type`),
  KEY `idx3` (`transaction_date`),
  KEY `lid` (`loan_id`),
  KEY `txid` (`transaction_id`),
  KEY `dp` (`datepaid`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ucl_returns'
# 
CREATE TABLE `ucl_returns` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `return_date` date default NULL,
  `return_amount` int(11) unsigned default NULL,
  `return_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ufc_check'
# 
CREATE TABLE `ufc_check` (
  `custnum` int(11) NOT NULL default '0',
  `payee` varchar(100) NOT NULL default '',
  `check_number` varchar(100) NOT NULL default '',
  `check_date` date NOT NULL default '0000-00-00',
  `check_amount` varchar(100) NOT NULL default '',
  `check_amount_verbage` varchar(255) NOT NULL default '',
  `check_auth_date` date NOT NULL default '0000-00-00',
  `bank_name` varchar(255) NOT NULL default '',
  `bank_address` varchar(255) NOT NULL default '',
  `bank_citystate` varchar(100) NOT NULL default '',
  `bank_routing` varchar(24) NOT NULL default '',
  `bank_account` varchar(24) NOT NULL default '',
  `printed` varchar(100) NOT NULL default '',
  KEY `index` (`custnum`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ufc_customer'
# 
CREATE TABLE `ufc_customer` (
  `custnum` int(11) default NULL,
  `status` varchar(255) default NULL,
  `ssn` varchar(9) default NULL,
  `ln` varchar(255) default NULL,
  `fn` varchar(255) default NULL,
  `mi` varchar(255) default NULL,
  `phone_home` varchar(12) default NULL,
  `phone_cell` varchar(12) default NULL,
  `phone_work` varchar(12) default NULL,
  `employer_name` varchar(100) default NULL,
  `ad1` varchar(255) default NULL,
  `ad2` varchar(255) default NULL,
  `city` varchar(255) default NULL,
  `st` varchar(255) default NULL,
  `zip` varchar(10) default NULL,
  `email` varchar(255) default NULL,
  `orgdate` varchar(255) default NULL,
  `dob` varchar(255) default NULL,
  `field15` varchar(255) default NULL,
  `payperiod` varchar(255) default NULL,
  `income` float default NULL,
  `field18` varchar(30) default NULL,
  `field19` varchar(30) default NULL,
  `field20` varchar(255) default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `ssn_idx` (`ssn`),
  KEY `stat` (`status`),
  KEY `idx_routing` (`field18`),
  KEY `idx_account` (`field19`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ufc_debits'
# 
CREATE TABLE `ufc_debits` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `debit_type` enum('PAYDOWN','ACH_RETRY','RETURN_FEE','SERVICE_CHARGE') default NULL,
  `debit_date` date default NULL,
  `debit_amount` int(11) unsigned default NULL,
  `debit_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ufc_loan'
# 
CREATE TABLE `ufc_loan` (
  `custnum` int(11) default NULL,
  `loan_id` int(11) default NULL,
  `overhead_cost` float default NULL,
  `acquisition_cost` float default NULL,
  `advance` varchar(255) default NULL,
  `advpaid` float default NULL,
  `paydown` varchar(255) default NULL,
  `feesaccrued` varchar(255) default NULL,
  `feespaid` varchar(255) default NULL,
  `balance` int(11) default NULL,
  `netbalance` varchar(255) default NULL,
  `cycle` varchar(255) default NULL,
  `achret` int(11) default NULL,
  `numcycles` varchar(255) default NULL,
  `loannum` int(11) default NULL,
  `advdate` varchar(255) default NULL,
  `date_advance` date NOT NULL default '0000-00-00',
  `paiddate` varchar(255) default NULL,
  `paidflag` varchar(11) default NULL,
  `depchkdate` date default NULL,
  `retchkdate` date default NULL,
  `depchkamt` varchar(255) default NULL,
  `retchkamt` varchar(255) default NULL,
  `first_return_code` varchar(10) default NULL,
  `first_return_msg` varchar(250) default NULL,
  `first_return_date` date default NULL,
  `last_return_code` varchar(10) default NULL,
  `last_return_msg` varchar(250) default NULL,
  `last_return_date` date default NULL,
  `next_due_date` date default NULL,
  `first_due_date` date default NULL,
  KEY `custnum_idx` (`custnum`),
  KEY `lid` (`loan_id`),
  KEY `ad` (`advdate`),
  KEY `ln` (`loannum`),
  KEY `dcd` (`depchkdate`),
  KEY `rcd` (`retchkdate`),
  KEY `idx_dadv` (`date_advance`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ufc_newest_loan'
# 
CREATE TABLE `ufc_newest_loan` (
  `custnum` int(11) default NULL,
  `loan_id` bigint(20) default NULL,
  KEY `l_id` (`loan_id`),
  KEY `c_id` (`custnum`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ufc_raw_transact'
# 
CREATE TABLE `ufc_raw_transact` (
  `custnum` int(11) NOT NULL default '0',
  `transaction_id` int(11) NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `transaction_date` timestamp(14) NOT NULL default '00000000000000',
  `type` varchar(100) NOT NULL default '',
  `amount` float NOT NULL default '0',
  `duedate` date NOT NULL default '0000-00-00',
  `paid` float NOT NULL default '0',
  `datepaid` date NOT NULL default '0000-00-00',
  `balance` float NOT NULL default '0',
  `lastupdate` date NOT NULL default '0000-00-00',
  `payment_history` mediumtext NOT NULL,
  `selectedforpayment` varchar(100) NOT NULL default '',
  `confirmed` enum('Y','N') NOT NULL default 'Y',
  `loandoc` enum('Y','N') NOT NULL default 'Y',
  `paymentamount` float NOT NULL default '0',
  `effectivedate` date NOT NULL default '0000-00-00',
  `nextduedate` date NOT NULL default '0000-00-00',
  `comments` mediumtext NOT NULL,
  `datebankprocessed` date NOT NULL default '0000-00-00',
  `timebankprocessed` timestamp(14) NOT NULL default '00000000000000',
  `datesenttobank` date NOT NULL default '0000-00-00',
  `timesenttobank` timestamp(14) NOT NULL default '00000000000000',
  `approvalflag` varchar(100) NOT NULL default '',
  `loanamountapproved` varchar(100) NOT NULL default '',
  `dateofinquiry` varchar(100) NOT NULL default '',
  `timeofinquiry` varchar(100) NOT NULL default '',
  `trackingnumber` varchar(100) NOT NULL default '',
  `scoresign` varchar(100) NOT NULL default '',
  `score` varchar(100) NOT NULL default '',
  `dateprocessedbybank` varchar(100) NOT NULL default '',
  `timeprocessedbybank` varchar(100) NOT NULL default '',
  `approveddenied` varchar(100) NOT NULL default '',
  `changeflag` varchar(100) NOT NULL default '',
  `notes` varchar(100) NOT NULL default '',
  `loan_id` int(11) default NULL,
  KEY `cid` (`custnum`),
  KEY `type` (`type`),
  KEY `tdate` (`transaction_date`),
  KEY `lid` (`loan_id`),
  KEY `txid` (`transaction_id`),
  KEY `dp` (`datepaid`)
) TYPE=MyISAM; 

# Host: serenity.verihub.com
# Database: datax
# Table: 'ufc_returns'
# 
CREATE TABLE `ufc_returns` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `loan_id` int(11) default NULL,
  `return_date` date default NULL,
  `return_amount` int(11) unsigned default NULL,
  `return_cycle` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `lid` (`loan_id`)
) TYPE=MyISAM; 

