<?php

	class ECash_Application_ContactFlags extends ECash_Application_Component
	{
		const ATTR_BAD_INFO = 'bad_info';
		const ATTR_DO_NOT_CONTACT = 'do_not_contact';
		const ATTR_BEST_CONTACT = 'best_contact';
		const ATTR_DO_NOT_MARKET = 'do_not_market';
		const ATTR_DO_NOT_LOAN = 'do_not_loan';
		const ATTR_HIGH_RISK = 'high_risk';
		const ATTR_FRAUD = 'fraud';

		/**
		 * Sets an attribute on column '$column' in table '$table' for row id '$row_id'
		 *
		 * ex. mark the phone number bad on app 1000:
		 *
		 * set($agent_id, 1000, ECash_Application_ContactFlags::ATTR_BAD_INFO, 'phone_home', 'application')
		 *
		 * @param int $agent_id
		 * @param int $row_id
		 * @param string $flag
		 * @param string $column
		 * @param string $table
		 */
		public function set($agent_id, $flag, $column, $table = 'application', $row_id = NULL)
		{

			if ($row_id === NULL) $row_id = $this->application_id;

			$flags = ECash::getFactory()->getReferenceList('ApplicationFieldAttribute', $this->db);

			$model = $this->buildApplicationFieldModel($agent_id, $flags->toId($flag), $column, $table, $row_id);
			$model->setInsertMode(DB_Models_WritableModel_1::INSERT_IGNORE);

			$model->insert();
		}

		protected function buildApplicationFieldModel($agent_id, $flag_id, $column, $table, $row_id)
		{
	
			$model = ECash::getFactory()->getModel('ApplicationField', $this->db);

			$model->date_modified = time();
			$model->date_created = time();
			$model->company_id = $this->company_id;
			$model->table_row_id = $row_id;
			$model->table_name = $table;
			$model->column_name = $column;
			$model->agent_id = $agent_id;
			$model->application_field_attribute_id = $flag_id;

			return $model;
		}
		public function setMultiple($agent_id, $flag, array $column_list, $table = 'application', $row_id = NULL)
		{
			if ($row_id === NULL) $row_id = $this->application_id;

			$flags = ECash::getFactory()->getReferenceList('ApplicationFieldAttribute', $this->db);

			$model_list = new DB_Models_ModelList_1(
				ECash::getFactory()->getModelClass('ApplicationField'),
				ECash_Config::getMasterDbConnection()
			);

			$model_list->setInsertMode(DB_Models_ModelList_1::INSERT_IGNORE);

			foreach ($column_list as $column)
			{
				$model = $this->buildApplicationFieldModel($agent_id, $flags->toId($flag), $column, $table, $row_id);
				$model_list->add($model);
			}

			$model_list->save();
		}

		/**
		 * Unset the attribute on column '$column' in table '$table' for row id '$row_id'
		 *
		 * @param int $row_id
		 * @param string $flag
		 * @param string $column
		 * @param string $table
		 */
		public function clear($flag, $column, $table = 'application', $row_id = NULL)
		{
			if ($row_id === NULL) $row_id = $this->application_id;
			
			$flags = ECash::getFactory()->getReferenceList('ApplicationFieldAttribute');
			
			ECash::getFactory()->getData('Application')->clearContactFlags($flags->toId($flag), $column, $table, $this->company_id,$row_id);

		}

		/**
		 * Unset the attribute on column '$column' in table '$table' for row id '$row_id'
		 *
		 * @param int $row_id
		 * @param string $flag
		 * @param string $column
		 * @param string $table
		 */
		public function clearAllByColumn($column, $table = 'application', $row_id = NULL)
		{
			if ($row_id === NULL) $row_id = $this->application_id;

			ECash::getFactory()->getData('Application')->clearContactFlagsByColumn($column, $table, $this->company_id, $row_id);
		}

		public function clearAllByType($flag, $table = 'application', $row_id = NULL)
		{
			if ($row_id === NULL) $row_id = $this->application_id;

			$flags = ECash::getFactory()->getReferenceList('ApplicationFieldAttribute')->toArray('field_name');

			ECash::getFactory()->getData('Application')->clearContactFlagsByType($flags[$flag], $table, $this->company_id, $row_id);
		}

		public function clearAllByRow($table = 'application', $row_id = NULL)
		{
			if ($row_id === NULL) $row_id = $this->application_id;

			ECash::getFactory()->getData('Application')->clearContactFlagsByRow($table, $this->company_id, $row_id);
		}

		public function getAll($table = 'application', $row_id = NULL)
		{
			if ($row_id === NULL) $row_id = $this->application_id;
			return ECash::getFactory()->getData('Application')->getContactFlags($table, $row_id);

		}
	}

?>