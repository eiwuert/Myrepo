<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Comment extends ECash_Models_WritableModel
	{
		public $Company;
		public $Application;
		public $Agent;
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id',
				'application_id', 'comment_id', 'source', 'type',
				'related_table', 'related_key', 'visibility', 'agent_id',
				'comment'
			);
			return $columns;
		}
		public function getCommentTypes()
		{
			static $types = array(
				'standard','withdraw','deny','followup','reverify','transaction',
				'collection','notes','row','declined','ach_correction','dnl'
			);
			return $types;
		}
		
		public function getPrimaryKey()
		{
			return array('comment_id');
		}
		public function getAutoIncrement()
		{
			return 'comment_id';
		}
		public function getTableName()
		{
			return 'comment';
		}

		public function getColumnData()
		{
			$column_data = $this->column_data;
			$column_data['date_created'] = date("Y-m-d H:i:s", $this->column_data['date_created']);
			return $column_data;
		}

		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_created'] = strtotime($data['date_created']);
		}
	}
?>