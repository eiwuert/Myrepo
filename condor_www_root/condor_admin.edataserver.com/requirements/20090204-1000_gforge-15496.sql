-- create the token_data
CREATE TABLE `condor_admin`.`token_data` (
  `date_created` DATETIME  NOT NULL,
  `token_data_id` INT  NOT NULL AUTO_INCREMENT,
  `raw_data` VARCHAR(255)  NOT NULL DEFAULT 'Empty',
  `token_data_type` ENUM('text','image','url')  NOT NULL DEFAULT 'text',
  PRIMARY KEY (`token_data_id`)
);

-- alter the token table
ALTER TABLE `tokens` ADD `token_data_id` INT NULL;
