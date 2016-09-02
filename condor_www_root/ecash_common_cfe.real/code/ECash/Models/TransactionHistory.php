<?php

        /**
         * @package Ecash.Models
         */
        class ECash_Models_TransactionHistory extends ECash_Models_WritableModel
        {
                public function getColumns()
                {
                        static $columns = array(
                                'date_created', 'company_id', 'application_id', 'transaction_register_id',
                                'transaction_history_id', 'agent_id', 'status_before', 'status_after'
                        );
                        return $columns;
                }
		public function getPrimaryKey()
		{
			return array('transaction_history_id');
		}
		public function getAutoIncrement()
		{
			return 'transaction_history_id';
		}
		public function getTableName()
                {
                        return 'transaction_history';
                }
                public function getColumnData()
                {
                        $column_data = parent::getColumnData();
                        $column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
                        return $column_data;
                }
                public function setColumnData($data)
                {
                        $this->column_data = $data;
                        $this->column_data['date_created'] = strtotime($data['date_created']);
                }
        }
?>