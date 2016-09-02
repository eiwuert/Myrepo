-- MySQL dump 9.09
--
-- Host: linux22.iwaynetworks.net    Database: ge_batch
-- ------------------------------------------------------
-- Server version	4.0.12-log

CREATE DATABASE IF NOT EXISTS ge_batch;
USE ge_batch;

--
-- Table structure for table `batch_file`
--

DROP TABLE IF EXISTS batch_file;
CREATE TABLE batch_file (
  file_id int(11) NOT NULL auto_increment,
  batch_id varchar(50) NOT NULL default 'fillerid',
  batch_date datetime NOT NULL default '0000-00-00 00:00:00',
  site_code varchar(8) default NULL,
  FILE longblob NOT NULL,
  return_received enum('0','1') NOT NULL default '0',
  true_up_received enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (file_id)
) TYPE=MyISAM;

