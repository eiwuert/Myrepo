<?php

        /**
         * @package Ecash.Models
         *
         * @TODO this file should be deleted, and the Reference Model used instead
         */

        class ECash_Models_TransactionType extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
        {
                const STATUS_COMPLETE = 'complete';
                const STATUS_FAILED = 'failed';

                const CLEARING_ACH = 'ach';
                const CLEARING_QUICKCHECK = 'quickcheck';
                const CLEARING_EXTERNAL = 'external';
                const CLEARING_ACCRUED_CHARGE = 'accrued charge'; //no underscore (_) here
                const CLEARING_ADJUSTMENT = 'adjustment';

                public function getColumns()
                {
			static $columns = array(
				'date_modified', 'date_created','active_status', 'company_id', 'transaction_type_id', 'name', 'name_short',
				'clearing_type', 'affects_principal', 'pending_period', 'end_status', 'period_type'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('transaction_type_id');
		}
		public function getAutoIncrement()
		{
			return 'transaction_type_id';
		}
		public function getTableName()
		{
			return 'transaction_type';
		}
	}
?>