<?php

	// Model of rule_action
	
	class ECash_CFE_API_ActionDef extends ECash_Models_CfeRuleAction 
	{
		public $Type;
		
		private $defined_action;
		
		const TYPE_EXECUTE_ON_FAILURE = 1;
		const TYPE_EXECUTE_ON_SUCCESS = 0;

		
		/**
		 * fetches multiple rows by the conditions passed in the first parameter
		 *
		 * @param array $where_args
		 * @param array $override_dbs
		 * @return array of CFE_API_ActionDef
		 */
		public function loadAllBy(array $where_args = array())
		{
			$retval = null;
			$query = "SELECT * FROM cfe_rule_action" . self::buildWhere($where_args);

			/* @var $base CFE_API_ConditionDef */

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
		 * fetches all rule_actions that are linked to the same rule as the rule_condition defined by the rule_condition_id
		 *
		 * @param int $condition_id
		 * @param array $override_dbs
		 * @return array of CFE_API_ActionDef
		 */
		public function loadAllByConditionId($condition_id, array $override_dbs = NULL)
		{
			$retval = null;
			$query = "
					SELECT ra.* 
					FROM cfe_rule_action AS ra
					INNER JOIN cfe_rule_condition AS rc ON rc.cfe_rule_id=ra.cfe_rule_id
					WHERE rc.cfe_rule_condition_id=" . $condition_id;


			if (($rs = $this->getDatabaseInstance(self::DB_INST_READ)->query($query)) !== FALSE)
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
		 * returns an array of the columns in this table
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'cfe_rule_action_id',
				'cfe_rule_id', 'cfe_action_id', 'params', 'sequence_no', 'rule_action_type'
			);
			return $columns;
		}

		/**
		 * returns an array of the primary key
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('cfe_rule_action_id');
		}

		/**
		 * returns the auto_increment field
		 *
		 * @return int
		 */
		public function getAutoIncrement()
		{
			return 'cfe_rule_action_id';
		}

		/**
		 * returns the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_rule_action';
		}
		
		public function getparams() {
			if(is_null($this->column_data['params'])) {
				$action_instance = $this->getDefinedAction();
				if(is_object($action_instance)) {
					return $action_instance->getParams();
				} else {
					return array();
				}
			}
			return $this->column_data['params'];
		}
		
		public function getDefinedAction() {
			if(is_null($this->defined_action)) {
				$this->defined_action = new ECash_CFE_API_DefinedActionDef($this->getDatabaseInstance());
				$this->defined_action->loadBy(array("cfe_action_id" => $this->cfe_action_id));
			}
			return $this->defined_action;
		}
	}

?>
