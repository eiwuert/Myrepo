<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_ApplicationStatusFlat extends ECash_Models_Reference_Model
	{
		const STATUS_STRING = 'status-string';
		
		public $ApplicationStatus;

		public function getColumnID()
		{
			return 'application_status_id';
		}

		/** This is a fake column name that will return an application
		 *  status string such as
		 *  'queued::verification::applicant::*root' To insure this
		 *  column appears that it doesn't really exist, we'll have to
		 *  override __get() [JustinF]
		 */
		public function getColumnName()
		{
			return self::STATUS_STRING;
		}

		public function __get($name)
		{
			if($name == self::STATUS_STRING)
			{
				return $this->getApplicationStatus();
			}
			else
			{
				return parent::__get($name);
			}
		}


		/**
		 * Override this since getColumnName() returns a bogus string
		 */
		protected function loadByName($name)
		{
			if (!$this->st_byname)
			{
				$db = $this->db;

				list($where, $values) = $this->prepareStatusString($name);
				
				$query = "
					SELECT *
					FROM {$this->empty->getTableName()}
					WHERE {$where}
				";

				$this->st_byname = $db->prepare($query);
			}

			return $this->fromStatement($this->st_byname, $values);
		}

	private function prepareStatusString($application_status)
	{
		if(!is_array($application_status))
		{
			$application_status = split('::', $application_status);
		}

		$where = array();
		$names = array();
		foreach($application_status as $level => $status)
		{
			$where[] = "level{$level} = ?";
			$names[] = $status;
		}

		return array(join(' AND ', $where), $names);
	}
		
	public function getApplicationStatus()
	{
		return join('::', $this->toArray());
	}
	public function toName()
	{
		return join('::', $this->toArray());
	}
	public function toArray()
	{
		$status = array();
		if($this->level0)
			$status['level0'] = $this->level0;
		if($this->level1)
			$status['level1'] = $this->level1;
		if($this->level2)
			$status['level2'] = $this->level2;
		if($this->level3)
			$status['level3'] = $this->level3;
		if($this->level4)
			$status['level4'] = $this->level4;
		if($this->level5)
			$status['level5'] = $this->level5;

		return $status;
	}

	public function equals($status)
	{
		if (is_int($status)) return ($this->application_status_id == $status);
		else if (is_string($status)) return ($this->getApplicationStatus() == $status);
		else if (is_array($status))
		{
			$status = implode("::", $status);
			return ($this->getApplicationStatus() == $status);
		}
	}

		public function getColumns()
		{
			static $columns = array(
				'application_status_id', 'level0', 'level0_name',
				'active_status', 'level1', 'level2', 'level3', 'level4',
				'level5'
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
			return 'application_status_flat';
		}

	}
?>
