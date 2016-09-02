<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_Section extends ECash_Models_Reference_Model
	{
		public $System;
		public $SectionParent;
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'system_id', 'section_id', 'name', 'description',
				'section_parent_id', 'sequence_no', 'level',
				'read_only_option'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('section_id');
		}
		public function getAutoIncrement()
		{
			return 'section_id';
		}
		public function getTableName()
		{
			return 'section';
		}
		
		public function getColumnID()
		{
			return 'section_id';
		}

		public function getColumnName()
		{
			return 'name';
		}
	}
?>
