<?php 
	/**
	 * GeneralLog_Models_GeneralLogType is the base model for general_log_type
	 *
	 * @author Adam Englander <adam.englander@sellingsource.com>
	 */
	class GeneralLog_Models_GeneralLogType extends DB_Models_WritableModel_1
	{
		/**
		 * Constructor
		 *
		 * @param DB_IConnection_1 $db
		 * @return void
		 */
		public function __construct(DB_IConnection_1 $db = NULL)
		{
			parent::__construct($db);
			$this->setInsertMode(self::INSERT_ON_DUPLICATE_KEY_UPDATE);
		}
		
		/**
		 * Get array of column names
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'general_log_type_id', 'name', 'descr'
			);
			return $columns;
		}
		
		/**
		 * Get array of columns comprising the primary key
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('general_log_type_id');
		}
		
		/**
		 * Get the name of the auto increment
		 *
		 * @return string
		 */
		public function getAutoIncrement()
		{
			return 'general_log_type_id';
		}
		
		/**
		 * Get the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'general_log_type';
		}
		
		/**
		 * Get associative array of data
		 *
		 * @return array
		 */
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			
			return $column_data;
		}		
		
		/**
		 * Set column data
		 *
		 * @param array $data
		 * @return void
		 */
		public function setColumnData($data)
		{
			$this->column_data = $data;
			
		}
	}
?>