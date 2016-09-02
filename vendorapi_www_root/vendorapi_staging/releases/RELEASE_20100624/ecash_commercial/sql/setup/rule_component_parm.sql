CREATE TABLE IF NOT EXISTS `rule_component_parm` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `rule_component_parm_id` int(10) unsigned NOT NULL,
  `rule_component_id` int(10) unsigned NOT NULL default '0',
  `parm_name` varchar(30) NOT NULL default '',
  `parm_subscript` varchar(30) default NULL,
  `sequence_no` smallint(5) unsigned NOT NULL default '0',
  `display_name` varchar(50) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `parm_type` enum('string','numeric','integer','boolean') NOT NULL default 'string',
  `user_configurable` enum('yes','no') NOT NULL default 'yes',
  `input_type` enum('text','password','textarea','checkbox','radio','select') NOT NULL default 'text',
  `presentation_type` enum('scalar','array','bracketed_floor','bracketed_ceiling') NOT NULL default 'scalar',
  `value_label` varchar(50) default NULL,
  `subscript_label` varchar(50) default NULL,
  `value_min` int(11) default NULL,
  `value_max` int(11) default NULL,
  `value_increment` int(11) default NULL,
  `length_min` mediumint(8) unsigned default NULL,
  `length_max` mediumint(8) unsigned default NULL,
  `enum_values` varchar(255) default NULL,
  `preg_pattern` varchar(255) default NULL,
  PRIMARY KEY  (`rule_component_parm_id`),
  UNIQUE KEY `idx_rule_comp_parm_compid_name` (`rule_component_id`,`parm_name`,`parm_subscript`),
  KEY `idx_rule_comp_parm_compid_seqno` (`rule_component_id`,`sequence_no`)
);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:01:26','2005-08-10 23:30:07','active',5,4,'return_transaction_fee','',1,'return_transaction_fee','This is the additional fee assessed for a transaction that fails.','numeric','yes','select','scalar','dollars',NULL,0,50,5,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:03:32','2005-08-11 05:59:24','active',9,5,'1','',1,'Failure 1','This is the date to re-attempt to withdraw funds that failed on a previous attempt. The start of this period will be based on the number of days since the last due date.','string','yes','select','array','days',NULL,0,0,0,0,0,'next pay day, immediate, 1 day, 2 days, 3 days, 4 days, 5 days, 6 days, 7 days, 8 days, 9 days, 10 days','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-11-02 08:46:25','2005-08-11 08:08:53','active',10,6,'past_due_status','',1,'past_due_status','Customers who bounce their first payment will be considered past due and will not go into collections until the Max Failures Before Collections rule is met.','string','no','select','scalar','NONE',NULL,0,0,0,0,0,'past due status','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-10-04 03:30:27','2005-08-11 08:22:05','active',11,7,'max_svc_charge_failures','',1,'max_svc_charge_failures','The maximum number of times a service charge ACH can return before the account goes to internal collections.','string','yes','select','scalar','Number of times',NULL,1,2,1,0,0,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:06:23','2005-08-11 08:43:09','active',12,8,'max_svc_charge_only_pmts','',1,'max_svc_charge_only_pmts','The maximum number of times a service charge will be applied to an account.','integer','yes','select','scalar','Number of times',NULL,0,10,1,0,0,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:06:43','2005-08-11 08:51:39','active',13,9,'principal_payment_amount','',1,'principal_payment_amount','The amount to be applied towards the loan principal after the maximum service charge only payments rule has been exceeded. This amount is only valid as long as it is less then the loan principal.','integer','yes','select','scalar','Dollars',NULL,0,100,50,0,0,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:07:00','2005-08-11 09:10:40','active',14,10,'service_charge_percent','',1,'service_charge_percent','This will determine the service charge amount. It will be a percentage of the loan principal.','integer','yes','select','scalar','Percent',NULL,1,30,1,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-08-24 23:37:28','2005-08-11 09:21:46','active',15,11,'payment_frequency','',1,'payment_frequency','The frequency that we should collect payments for a given pay type','string','yes','select','scalar','Pay Frequency',NULL,0,0,0,0,0,'bi-weekly','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:07:27','2005-08-11 09:30:00','active',16,13,'react_loan_amnt_increase','',1,'react_loan_amnt_increase','This will be a dollar amount increase for repeat customers who previously completed a loan. This amount will start with the orignial qualify amount of their last loan and increase to the maximum in the maximum re-activate loan amount rule.','integer','yes','select','scalar','Dollars',NULL,10,100,10,2,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:09:15','2005-08-13 07:50:06','active',18,15,'max_contact_attempts','',1,'max_contact_attempts','This will be the maximum number of times a loan will be placed in the collections queue for a contact.','integer','yes','select','scalar','Times',NULL,1,15,1,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-10-04 00:38:19','2005-08-13 08:34:01','active',20,18,'percentage_rate','',1,'percentage_rate','This arrangements met discount will apply to any customer that makes his or her arranged payments on time and pays off the loan in full, if arrangements are broken, this discount will no longer apply.','string','yes','select','scalar','Percent Rate',NULL,0,30,1,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-10-04 03:18:05','2005-08-13 08:42:54','active',21,19,'ach_return_email','',1,'ach_return_email','Automatically send an e-mail out to any customers who have an ACH return.','string','no','select','scalar','Enabled',NULL,0,2,1,0,0,'On, Off','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-08-24 23:37:28','2005-08-14 03:12:43','active',22,20,'100','',1,'100','If payment arrangements are broken, this is the number of payments per borrowed amount.','integer','yes','select','array','Num Of Payments',NULL,1,10,1,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-08-24 23:37:28','2005-08-17 05:15:28','active',23,20,'200','',2,'200','If payment arrangements are broken, this is the number of payments per borrowed amount.','integer','yes','select','array','Num Of Payments','',1,10,1,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-08-24 23:37:28','2005-08-17 05:19:11','active',24,20,'300','',3,'300','If payment arrangements are broken, this is the number of payments per borrowed amount.','integer','yes','select','array','Num Of Payments','',1,10,1,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-08-24 23:37:28','2005-08-17 05:22:30','active',25,20,'400','',4,'400','If payment arrangements are broken, this is the number of payments per borrowed amount.','integer','yes','select','array','Num Of Payments','',1,10,1,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-08-24 23:37:28','2005-08-17 05:24:51','active',26,20,'500','',5,'500','If payment arrangements are broken, this is the number of payments per borrowed amount.','integer','yes','select','array','Num Of Payments','',1,10,1,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-08-24 23:37:28','2005-08-17 05:25:29','active',27,20,'600','',6,'600','If payment arrangements are broken, this is the number of payments per borrowed amount.','integer','yes','select','array','Num Of Payments','',1,10,1,1,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-10-04 03:32:41','2005-08-17 07:00:45','active',30,14,'800',NULL,1,'800','This will be the maximum loan amount that re-activate customers will qualify for.','integer','yes','select','array','Dollars','Monthly Income: 800 to 199 - Max Amount',100,500,50,3,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-10-04 03:34:38','2005-08-17 07:03:22','active',31,14,'1200',NULL,2,'1200','This will be the maximum loan amount that re-activate customers will qualify for.','integer','yes','select','array','Dollars','Monthly Income: 1200 to 1699 - Max Amount',100,500,50,3,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-10-04 03:35:22','2005-08-17 07:04:17','active',32,14,'1700',NULL,3,'1700','This will be the maximum loan amount that re-activate customers will qualify for.','integer','yes','select','array','Dollars','Monthly Income: 1700 to 1999 - Max Amount',100,500,50,3,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-10-04 03:35:42','2005-08-17 07:05:07','active',33,14,'2000',NULL,4,'2000','This will be the maximum loan amount that re-activate customers will qualify for.','integer','yes','select','array','Dollars','Monthly Income: > 2000 - Max Amount',100,500,50,3,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:03:51','2005-08-26 05:42:08','active',34,5,'2',NULL,2,'Failure 2','This is the date to re-attempt to withdraw funds that failed on a previous attempt. The start of this period will be based on the number of days since the last due date.','string','yes','select','array','days','',0,0,0,0,25,'next pay day, immediate, 1 day, 2 days, 3 days, 4 days, 5 days, 6 days, 7 days, 8 days, 9 days, 10 days',NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-10-04 23:41:29','2005-08-25 05:58:21','active',35,21,'max_ach_fee_chrg_per_loan',NULL,1,'Max Fee Number','This is the maximum number of times an ACH fee should be assessed during the lifetime of a loan. When the value for this rule is met, another attempt is made to collect the full amount of the loan.','string','yes','select','scalar','days',NULL,0,10,1,0,0,NULL,NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:10:12','2005-08-26 00:21:05','active',36,16,'100',NULL,1,'100','Max number of payment arrangements per borrowed amount.','integer','yes','select','array','Num Of Payments',NULL,1,10,1,1,2,NULL,NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:10:20','2005-08-26 00:21:05','active',37,16,'200',NULL,2,'200','Max number of payment arrangements per borrowed amount.','integer','yes','select','array','Num Of Payments',NULL,1,10,1,1,2,'',NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:10:34','2005-08-26 00:21:05','active',38,16,'300',NULL,3,'300','Max number of payment arrangements per borrowed amount.','integer','yes','select','array','Num Of Payments',NULL,1,10,1,1,2,NULL,NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:10:41','2005-08-26 00:21:05','active',39,16,'400',NULL,4,'400','Max number of payment arrangements per borrowed amount.','integer','yes','select','array','Num Of Payments',NULL,1,10,1,1,2,NULL,NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:10:52','2005-08-26 00:34:15','active',40,16,'500',NULL,5,'500','Max number of payment arrangements per borrowed amount.','integer','yes','select','array','Num Of Payments',NULL,1,10,1,1,2,NULL,NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-10 01:11:04','2005-08-26 00:35:26','active',41,16,'600',NULL,6,'600','Max number of payment arrangements per borrowed amount.','integer','yes','select','array','Num Of Payments',NULL,1,10,1,1,2,NULL,NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-11-02 08:44:37','2005-08-26 03:12:32','active',44,22,'weekly',NULL,1,'weekly','Debiting Frequency.','string','yes','select','array','Pay Period',NULL,0,0,0,0,25,'every pay period, every other pay period, every third pay period',NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2006-01-06 08:51:42','2005-08-26 03:14:12','active',45,22,'bi_weekly',NULL,2,'bi-weekly','Debiting Frequency.','string','yes','select','array','Pay Period',NULL,0,0,0,0,25,'every pay period, every other pay period, every third pay period',NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2006-01-06 08:51:45','2005-08-26 03:14:14','active',46,22,'twice_monthly',NULL,3,'twice-monthly','Debiting Frequency.','string','yes','select','array','Pay Period',NULL,0,0,0,0,25,'every pay period, every other pay period, every third pay period',NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-11-02 08:44:37','2005-08-26 03:14:16','active',47,22,'monthly',NULL,4,'monthly','Debiting Frequency.','string','yes','select','array','Pay Period',NULL,0,0,0,0,25,'every pay period, every other pay period, every third pay period',NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-15 23:27:29','2005-09-15 23:27:29','active',48,23,'1000',NULL,1,'1000','This will be the maximum loan amount that new loan customers will qualify for','integer','yes','select','array','Dollars','Monthly Income: 1000',100,300,50,3,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-15 23:28:49','2005-09-15 23:28:49','active',49,23,'1200',NULL,2,'1200','This will be the maximum loan amount that new loan customers will qualify for.','integer','yes','select','array','Dollars','Monthly Income: 1200',100,300,50,3,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-15 23:29:22','2005-09-15 23:29:22','active',50,23,'1700',NULL,3,'1700','This will be the maximum loan amount that new loan customers will qualify for.','integer','yes','select','array','Dollars','Monthly Income: 1700',100,300,50,3,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-15 23:30:41','2005-09-15 23:30:41','active',51,23,'2000',NULL,4,'2000','This will be the maximum loan amount that new loan customers will qualify for.','integer','yes','select','array','Dollars','Monthly Income: 2000',100,300,50,3,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-09-15 23:51:30','2005-09-15 23:31:16','active',52,23,'5000',NULL,5,'5000','This will be the maximum loan amount that new loan customers will qualify for.','integer','yes','select','array','Dollars','Monthly Income: 3000',100,300,50,3,3,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-10-29 01:03:49','2005-10-29 01:03:49','active',53,24,'elapsed_time',NULL,1,'Elapsed Time','This is how long an account can be in Bankruptcy Notified before being moved back into the Collections Contact queue.','integer','yes','select','array','Days','',10,50,5,2,2,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2005-12-17 06:31:22','2005-12-17 06:31:22','active',54,25,'cancelation_delay','',1,'delay','This is the number of days to cancel a previous transaction.','integer','yes','select','','Days','NULL',0,7,1,1,1,'','');
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2006-01-14 06:32:01','2006-01-14 06:32:01','active',55,26,'watch_period',NULL,1,'watch_period','This is the time period a user will be watched.  It essentially defines the expiration of the agent_affiliation with the applicant.','integer','yes','select','scalar','Days',NULL,0,365,1,1,3,NULL,NULL);
INSERT  IGNORE INTO `rule_component_parm` VALUES ('2006-03-17 03:16:15','2006-03-17 03:16:15','active',56,27,'grace_period',NULL,1,'grace_period','This is the period of time since the last paydate before the first payment is to be made','integer','yes','select','scalar','Days',NULL,0,31,1,1,3,NULL,NULL);