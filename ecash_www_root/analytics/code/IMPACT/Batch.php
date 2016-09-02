<?php

	class Impact_Batch extends Analysis_Batch
	{
		/**
		 * Used for Customer Specific Model Overrides
		 * Example: HMS_Models_Loan
		 *
		 * @var string
		 */
		protected $customer_name = 'IMPACT';

		public static function getECashDb($company, $mode)
		{
			switch (strtolower($company))
			{
				case 'ipdl':
				case 'icf':
				case 'ic':
				case 'ifs':
					return new DB_MySQLConfig_1(
						'reporting.ecashimpact.ept.tss',
						'ecash',
						'showmethemoney',
						'ldb_impact',
						3310// Live
					);
					break;
				case 'iic':
					return new DB_MySQLConfig_1(
						'writer.intacash.ept.tss',
						'ecash_intacash',
						'at4aeDul',
						'ldb_intacash',
						3307
					);
					break;
			}
			
			throw new Exception("Invalid company, " . $company);
		}

		public static function getAnalysisDb($mode)
		{
			switch (strtolower($mode))
			{
				case 'rc':
					return new DB_MySQLConfig_1(
						'db101.ept.tss',
						'ecash_analytics',
						'shahX1th',
						'impact_analysis',
						3307
					);

				case 'live':
					return new DB_MySQLConfig_1(
						'analytics.tss',
						'datax',
						'Eth7eeDu',
						'impact_analysis',
						3306
					);
			}
			throw new Exception("Invalid mode, " . $mode);
		}

		/**
		 * @see Analysis_Batch::getQueryPlanForApplicationsBySSN();
		 */
		protected function getQueryPlanForApplicationsBySSN()
		{
			$query_plan = parent::getQueryPlanForApplicationsBySSN();
			$query_plan['addSelect']['schedule_model'] = "IF(m.name IS NULL, 'fund', m.name) as schedule_model";
			$query_plan['addSelect']['prev_schedule_model'] = "IF(ft.name_short = 'prev_paydown', 'fund_paydown', IF(m.name IS NULL, 'fund', m.name)) AS prev_schedule_model";
			$query_plan['addSelect']['portfolio_tag'] = "SUBSTRING(atd.tag_name FROM 4) as portfolio_tag";
			$query_plan['addSelect']['verification_agent'] = "(
						SELECT CONCAT(ag.name_last, ', ', ag.name_first)
						FROM status_history AS sh
						JOIN agent AS ag ON ag.agent_id = sh.agent_id
						WHERE sh.application_id = ap.application_id
						AND   sh.date_created < " . $this->ecash_db->quote($this->effective_date) . "
						AND   sh.application_status_id = " . $this->ecash_db->quote($this->status_reverse_map['queued::underwriting::applicant::*root']) . "
						ORDER BY sh.date_created, sh.status_history_id DESC
						LIMIT 1
					) AS verification_agent";
			$query_plan['addJoin']['schedule_model'] = array('schedule_model AS m', 'm.schedule_model_id = ap.schedule_model_id', 'LEFT');
			$query_plan['addJoin']['application_flag'] = array('application_flag AS af', 'af.application_id = ap.application_id', 'LEFT');
			$query_plan['addJoin']['flag_type'] = array('flag_type AS ft', 'ft.flag_type_id = af.flag_type_id', 'LEFT');
			$query_plan['addJoin']['application_tags'] = array('application_tags AS at', 'at.application_id = ap.application_id', 'LEFT');
			$query_plan['addJoin']['application_tag_details'] = array('application_tag_details AS atd', 'atd.tag_id = at.tag_id AND atd.company_id = ap.company_id', 'LEFT');
			$query_plan['setOrderBy'] = 'ap.application_id ASC';
			$query_plan['addGroupBy'] = 'ap.application_id';

			return $query_plan;
		}

		/**
		 * @see Analysis_Batch::loanFromApplication();
		 */
		protected function loanFromApplication($app)
		{
			$loan = parent::loanFromApplication($app);
			$loan['model'] = $app->schedule_model;
			$loan['previous_model'] = $app->prev_schedule_model;
			$loan['portfolio_tag'] = $app->portfolio_tag;
			$loan['verification_agent'] = $app->verification_agent;

			return $loan;
		}
	}

?>
