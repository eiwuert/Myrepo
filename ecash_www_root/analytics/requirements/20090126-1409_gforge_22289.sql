-- All Companies - Add date_first_payment for [#22289]
ALTER TABLE `loan`
 ADD COLUMN `date_first_payment` date NOT NULL default '0000-00-00' AFTER `date_advance`;

