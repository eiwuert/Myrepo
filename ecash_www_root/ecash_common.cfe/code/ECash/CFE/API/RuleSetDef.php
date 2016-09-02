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


	class ECash_CFE_API_RuleSetDef extends ECash_Models_CfeRuleSet 
	{
		/**
		 * array of CFE_RuleDef -- Requires some sort of loader
		 */
		public $Rules = array();

		public function loadAllBy(array $where_args = array())
		{
			$retval = null;
			$query = "SELECT * FROM cfe_rule_set" . self::buildWhere($where_args);

			if (($rs = $this->getDatabaseInstance(self::DB_INST_READ)->queryPrepared($query, $where_args)) !== FALSE)
			{
				$results = $rs->fetchAll();
				$retval = array();
				foreach($results as $result) {
					$temp = new self($this->getDatabaseInstance());
					$temp->fromDbRow($result);
					$retval[] = $temp;
				}
			}
			return $retval;
		}
		
		
		/**
		 * Gets the rules for this rule_set
		 *
		 * @return array of CFE_API_RuleDef
		 */
		public function getRules() {
			if(empty($this->Rules) && $this->rule_set_id) {
				$model =  new CFE_API_RuleDef($this->getDatabaseInstance());
				$this->Rules = $model->loadAllBy(array("cfe_rule_set_id" => $this->rule_set_id));
			}
			return $this->Rules;
		}
		

	}
	
	
?>
