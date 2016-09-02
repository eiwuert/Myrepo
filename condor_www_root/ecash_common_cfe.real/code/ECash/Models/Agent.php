<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Agent extends ECash_Models_WritableModel
	{
		public function loadBySystemLogin($system_name_short, $login)
		{
			$query = "
				SELECT
					agent.*
				FROM agent
				INNER JOIN system ON (system.system_id = agent.system_id)
				WHERE
					agent.login = :login
				AND agent.active_status = 'active'
				AND system.name_short = :system_name_short
			";

			$db = $this->getDatabaseInstance(self::DB_INST_READ);

			$row = DB_Util_1::querySingleRow($db, $query, array(
				'login' => $login,
				'system_name_short' => $system_name_short
			));

			if ($row !== FALSE)
			{
				$this->fromDbRow($row);
				return TRUE;
			}
			return FALSE;
		}


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
	}
?>
