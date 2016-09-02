
-- All Companies
ALTER TABLE `loan`
 ADD COLUMN `date_application_sold` DATE DEFAULT NULL AFTER `date_advance`,
 ADD COLUMN `promo_id_first` VARCHAR(10) DEFAULT NULL AFTER `promo_id`,
 ADD COLUMN `promo_id_final` VARCHAR(10) DEFAULT NULL AFTER `promo_id_first`,
 ADD COLUMN `campaign_short` VARCHAR(10) DEFAULT NULL AFTER `last_return_date`;

-- CLK Only
ALTER TABLE `loan`
 ADD COLUMN `lead_price` DECIMAL(7,2) DEFAULT NULL;
