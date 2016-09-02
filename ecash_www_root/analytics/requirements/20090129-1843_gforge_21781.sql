-- All companies, adds the collected balances to the loan table for [#21781]
ALTER TABLE `loan`
   ADD COLUMN `collection_fees` decimal(7,2) NOT NULL default '0.00' AFTER `loan_balance`,
   ADD COLUMN `collection_principal` decimal(7,2) NOT NULL default '0.00' AFTER `collection_fees`;
