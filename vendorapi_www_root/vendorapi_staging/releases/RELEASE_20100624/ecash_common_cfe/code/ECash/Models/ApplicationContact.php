<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_ApplicationContact extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_created', 'application_contact_id', 'application_id', 'application_field_attribute_id',
				'type', 'category', 'value', 'notes',
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('application_contact_id');
		}
		public function getAutoIncrement()
		{
			return 'application_contact_id';
		}
		public function getTableName()
		{
			return 'application_contact';
		}
	}
?>
