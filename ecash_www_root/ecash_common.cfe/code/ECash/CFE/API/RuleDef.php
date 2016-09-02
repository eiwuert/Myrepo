<?php

	class ECash_CFE_API_RuleDef extends ECash_Models_CfeRule 
	{
		public $Event;
		
		public $Salience;
		
		/**
		 * array of CFE_ConditionDef, null to flag that it needs to be populated
		 */
		public $Conditions = null;
		
		/**
		 * array of CFE_ActionDef, null to flag that it needs to be populated
		 */
		public $Actions = null;


		public function loadAllBy(array $where_args = array())
		{
			$retval = null;
			$query = "SELECT * FROM cfe_rule" . self::buildWhere($where_args);

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
		 * gets the conditions for this rule
		 *
		 * @return array of CFE_API_ConditionDef
		 */
		public function getConditions() {
			if(is_null($this->Conditions) && $this->rule_id) {
				$model = new CFE_API_ConditionDef($this->getDatabaseInstance());
				$this->Conditions = $model->loadAllBy(array("cfe_rule_id" => $this->rule_id));
			}
			return $this->Conditions;
		}
		
		public function getActions() {
			if(is_null($this->Actions) && $this->rule_id) {
				$model = new CFE_API_ActionDef($this->getDatabaseInstance());
				$this->Actions = $model->loadAllBy(array("cfe_rule_id" => $this->rule_id));
			}
			return $this->Actions;
		}
	}


?>
