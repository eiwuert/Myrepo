
 
CREATE TABLE `account` (
  `cc_number` bigint(20) unsigned NOT NULL default '0',
  `modified_date` timestamp(14) NOT NULL,
  `credit_limit` float NOT NULL default '0',
  `available_balance` float NOT NULL default '0',
  `account_status` enum('HOLD','PENDING','INACTIVE','DENIED','CANCELLED','ACTIVE','WITHDRAWN','COLLECTIONS') default 'HOLD',
  `ach_routing_number` varchar(100) NOT NULL default '',
  `ach_account_number` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`cc_number`)
) TYPE=MyISAM; 

 
CREATE TABLE `customer` (
  `cc_number` bigint(20) unsigned NOT NULL default '0',
  `first_name` varchar(50) NOT NULL default '',
  `last_name` varchar(50) NOT NULL default '',
  `maiden_name` varchar(50) NOT NULL default '',
  `email` varchar(100) NOT NULL default '',
  `address_1` varchar(100) NOT NULL default '',
  `address_2` varchar(100) NOT NULL default '',
  `city` varchar(100) NOT NULL default '',
  `state` varchar(100) NOT NULL default '',
  `zip` varchar(5) NOT NULL default '',
  `home_phone` varchar(18) NOT NULL default '',
  `work_phone` varchar(18) NOT NULL default '',
  `ssn` varchar(15) NOT NULL default '',
  `bankruptcy` char(1) NOT NULL default '',
  `discharged` char(2) NOT NULL default '',
  `date_of_birth` date default NULL,
  `income` varchar(50) NOT NULL default '',
  `promo_id` int(10) unsigned NOT NULL default '1',
  `promo_sub_code` varchar(250) default NULL,
  PRIMARY KEY  (`cc_number`)
) TYPE=MyISAM; 

 
CREATE TABLE `transaction` (
  `transaction_id` int(10) unsigned NOT NULL auto_increment,
  `modified_date` timestamp(14) NOT NULL,
  `origination_date` timestamp(14) NOT NULL,
  `send_batch_date` timestamp(14) NOT NULL,
  `recieve_batch_date` timestamp(14) NOT NULL,
  `cross_reference_id` int(10) unsigned NOT NULL default '0',
  `cc_number` bigint(20) unsigned NOT NULL default '0',
  `cc_amount` double NOT NULL default '0',
  `ach_amount` double NOT NULL default '0',
  `ach_routing_number` varchar(100) NOT NULL default '',
  `ach_account_number` varchar(100) NOT NULL default '',
  `ach_reason` varchar(250) default NULL,
  `transaction_status` enum('HOLD','PENDING','SENT','APPROVED','DENIED') default 'PENDING',
  `transaction_type` enum('ENROLLMENT','DOWN PAYMENT','REFUND','MONTHLY PAYMENT') NOT NULL default 'ENROLLMENT',
  `transaction_source` enum('SSO','EGC','AGENT') NOT NULL default 'SSO',
  `notes` text,
  `promo_id` int(10) unsigned NOT NULL default '1',
  `promo_sub_code` varchar(250) default NULL,
  PRIMARY KEY  (`transaction_id`),
  KEY `cc` (`cc_number`)
) TYPE=MyISAM; 




















insert into customer  select  account_status.cc_number, first_name, last_name,  maiden, email, address1,   address2, city, state, zip, homephone, workphone, ssn, bankruptcy, discharged, CONCAT('19', orders.year, '-', orders.month, '-', orders.day), income, ref, null   from orders, account_status where account_status.cc_number = orders.cc_number;

insert into account select ord.cc_number, null, credit_limit, available_balance, account_status, ord.routing_number, ord.acctno from account_status as ac  , orders as ord  where ac.cc_number = ord.cc_number;

insert into  `transaction` select transaction_id, modified_date, origination_date, send_batch_date,   recieve_batch_date , reference_id,  cc_number,  cc_amount, amount,  ach_routing, ach_account,  reason ,   status, 'DOWN PAYMENT', 'SSO', 'NULL', 0, 'NULL' from transaction_status;

insert into  `transaction` select null, modified_date, null, send_batch_date, recieve_batch_date , processed_status.cid ,  processed_status.cc_number, 0.00, 9.95, orders.routing_number, orders.acctno, reason , processed_status.status, 'ENROLLMENT',  'EGC', 'NULL', orders.ref, 'NULL' from processed_status, orders where orders.cc_number = processed_status.cc_number;
