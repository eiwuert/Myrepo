<?php

	class Agean_Batch extends Analysis_Batch
	{
		/**
		 * Used for Customer Specific Model Overrides
		 * Example: HMS_Models_Loan
		 *
		 * @var string
		 */
		protected $customer_name = 'AGEAN';

		public static function getECashDb($company, $mode)
		{
			switch (strtolower($company))
			{
				case 'pcal':
				case 'mydy':
				case 'micr':
				case 'cbnk':
				case 'jiffy':
					return new DB_MySQLConfig_1(
						'reader.ecashagean.ept.tss',
						'ecash',
						'Zeir5ahf',
						'ldb_agean',
						3306
					);
			}

			throw new Exception('Invalid company, '. $company);
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
						'agean_analysis',
						3307
					);

				case 'live':
					return new DB_MySQLConfig_1(
						'analytics.tss',
						'datax',
						'Eth7eeDu',
						'agean_analysis',
						3306
					);
			}

			throw new Exception('Invalid mode, '. $mode);
		}

		/**
		 * @see Analysis_Batch::getQueryPlanForApplicationsBySSN();
		 */
		protected function getQueryPlanForApplicationsBySSN()
		{
			$query_plan = parent::getQueryPlanForApplicationsBySSN();
			$query_plan['addSelect']['loan_type'] = 'lt.name_short AS loan_type';
			$query_plan['addJoin']['loan_type'] = array('loan_type AS lt', 'lt.loan_type_id = ap.loan_type_id');

			return $query_plan;
		}

		/**
		 * @see Analysis_Batch::loanFromApplication();
		 */
		protected function loanFromApplication($app)
		{
			$loan = parent::loanFromApplication($app);
			$loan['loan_type'] = $app->loan_type;

			// If date_fund_actual is not set, use the estimate instead
			$loan['date_advance']   = (! empty($app->date_fund_actual)) ? $app->date_fund_actual : $app->date_fund_estimated;

			return $loan;
		}
	}

?>
