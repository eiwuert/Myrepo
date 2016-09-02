<?php 
	class ECash_Models_AgentAction extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_created', 'company_id', 'agent_id', 'action_id',
				'time_expended', 'application_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array();
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'agent_action';
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
