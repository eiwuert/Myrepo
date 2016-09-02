<?php 
	class ECash_Models_Reference_LoanActionSection extends DB_Models_ReferenceModel_1
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'loan_action_section_id',
				'name_short', 'description', 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('loan_action_section_id');
		}
		public function getAutoIncrement()
		{
			return 'loan_action_section_id';
		}
		public function getTableName()
		{
			return 'loan_action_section';
		}
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
			$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
			return $column_data;
		}		
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_modified'] = strtotime($data['date_modified']);
			$this->column_data['date_created'] = strtotime($data['date_created']);
		}
		public function getColumnId()
		{
			return 'loan_action_section_id';
		}
		public function getColumnName()
		{
			return 'name_short';
		}
	}
