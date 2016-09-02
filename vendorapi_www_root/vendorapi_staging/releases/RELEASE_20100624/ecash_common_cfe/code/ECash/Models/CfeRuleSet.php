<?php

/*
Table Definition:

CREATE TABLE `rule_set` (
  `date_modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `date_created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_status` enum('active','inactive') NOT NULL default 'active',
  `rule_set_id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `loan_type_id` int(10) unsigned NOT NULL default '0',
  `date_effective` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`rule_set_id`),
  UNIQUE KEY `idx_rule_set_loan_type_effdate` (`loan_type_id`,`date_effective`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 */


	class ECash_Models_CfeRuleSet extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		/**
		 * array of CFE_RuleDef -- Requires some sort of loader
		 */
		public $Rules = array();
		

		public function getActiveByLoanType($loan_type_id, array $override_dbs = NULL)
		{
			$query = "
					SELECT * 
					FROM 
						cfe_rule_set 
					WHERE 
						active_status = :active_status 
					AND 
						loan_type_id = :loan_type_id 
					AND
						date_effective = (
											SELECT
												MAX(date_effective)
											FROM
												cfe_rule_set
											WHERE
												active_status = :active_status
											AND
												loan_type_id = :loan_type_id
										)
					LIMIT 1
					";
//			$list = new self();
//			$list->setOverrideDatabases($override_dbs);
//			$db = $list->getDatabaseInstance();
//			$list->statement = $db->queryPrepared($query, array('active_status' => 'active',
//																'loan_type_id'  => $loan_type_id));

			$where_args = array('active_status' => 'active',
								'loan_type_id'  => $loan_type_id);
								

			if (($row = $this->getDatabaseInstance(self::DB_INST_READ)->querySingleRow($query, $where_args)) !== FALSE)
			{
				$this->fromDbRow($row);
				return true;
			}
			return NULL;
		}
		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'cfe_rule_set_id', 'name', 'loan_type_id', 'date_effective', 'created_by'
			);
			return $columns;
		}

		public function getPrimaryKey()
		{
			return array('cfe_rule_set_id');
		}

		public function getAutoIncrement()
		{
			return 'cfe_rule_set_id';
		}

		public function getTableName()
		{
			return 'cfe_rule_set';
		}
	}
	
	
?>
