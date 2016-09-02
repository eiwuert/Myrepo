<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_Agent extends ECash_Models_Reference_Model
	{
		public $System;
		public $num_all_affiliations;
		public $num_inactive_affiliations;
		public $num_active_affiliations;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'system_id', 'agent_id', 'name_last', 'name_first',
				'name_middle', 'email', 'phone', 'login', 'crypt_password',
				'date_expire_account', 'date_expire_password', 'cross_company_admin'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('agent_id');
		}
		public function getAutoIncrement()
		{
			return 'agent_id';
		}
		public function getTableName()
		{
			return 'agent';
		}

		public function getColumnID()
		{
			return 'agent_id';
		}

		public function getColumnName()
		{
			return 'login';
		}
		
		public function getNumAllAffiliations()
		{
			return $this->num_all_affiliations;
		}
		
		public function setNumAllAffiliations($value)
		{
			$this->num_all_affiliations = $value;
		}
		
		public function getNumInactiveAffiliations()
		{
			return $this->num_inactive_affiliations;
		}
		
		public function setNumInactiveAffiliations($value)
		{
			$this->num_inactive_affiliations = $value;
		}
		
		public function getNumActiveAffiliations()
		{
			return $this->num_active_affiliations;
		}
		
		public function setNumActiveAffiliations($value)
		{
			$this->num_active_affiliations = $value;
		}
		
		public function __get($name)
		{
			$func_name = "get" . str_replace("_","",$name);
			if(method_exists($this,$func_name))
			{
				return $this->{$func_name}();
			} else {
				return parent::__get($name);
			}
		}
		
		public function __set($name, $value)
		{
			$func_name = "set" . str_replace("_","",$name);
			if(method_exists($this,$func_name))
			{
				$this->{$func_name}($value);
			} else {
				parent::__get($name, $value);
			}
		}
		
	}
?>
