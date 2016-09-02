<?php

        /**
         * @package Ecash.Models
         */
        class ECash_Models_TransactionLedger extends ECash_Models_WritableModel
        {
                public function getColumns()
                {
                        static $columns = array(
                                'date_modified','date_created','company_id','application_id','transaction_ledger_id','transaction_register_id','transaction_type_id','amount','date_posted','source_id'
                        );
                        return $columns;
                }
		public function getPrimaryKey()
		{
			return array('transaction_ledger_id');
		}
		public function getAutoIncrement()
		{
			return 'transaction_ledger_id';
		}
		public function getTableName()
                {
                        return 'transaction_ledger';
                }
                public function getColumnData()
                {
                        $column_data = parent::getColumnData();
                        $column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
                        $column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
                        $column_data['date_posted'] = date('Y-m-d', $column_data['date_posted']);
                        return $column_data;
                }
                public function setColumnData($data)
                {
                        $this->column_data = $data;
                        $this->column_data['date_modified'] = strtotime($data['date_modified']);
                        $this->column_data['date_created'] = strtotime($data['date_created']);
                        $this->column_data['date_posted'] = strtotime($data['date_posted']);
                }
        }
?>