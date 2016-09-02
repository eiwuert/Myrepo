<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Acl extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{

		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'company_id',
				 'access_group_id', 'section_id', 'acl_mask', 'read_only' 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('access_group_id','section_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'acl';
		}
		
		/** Added For GF #22280
         * Sets the column_data for 'read_only'
		 * 
		 * @param int $value read_only value
		 */
		public function setReadOnlyAcl($value)
		{
			if ($this->getReadOnly())
			{
				throw new DB_Models_ReadOnlyException();
			}

			if ($this->column_data['read_only'] !== $value)
			{
				$this->column_data['read_only'] = $value;
				$this->altered_columns['read_only'] = 'read_only';
			}
		}
	}
?>