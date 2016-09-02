<?php

	class AALM_Batch extends Analysis_Batch
	{
		/**
		 * Used for Customer Specific Model Overrides
		 * Example: HMS_Models_Loan
		 *
		 * @var string
		 */
		protected $customer_name = 'AALM';
		
		public static function getECashDb($company, $mode)
		{
			switch (strtolower($company))
			{
				case 'generic':
					return new DB_MySQLConfig_1(
						'reader.ecashaalm.ept.tss',
						'username',
						'password',
						'database',
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
						'aalm_analysis',
						3307
					);

				case 'live':
					return new DB_MySQLConfig_1(
						'analytics.tss',
						'datax',
						'Eth7eeDu',
						'aalm_analysis',
						3306
					);
			}
			throw new Exception('Invalid mode, '. $mode);
		}
		
		/**
		 * @see Analysis_Batch::loanFromApplication();
		 */
		protected function loanFromApplication($app)
		{
			$loan = parent::loanFromApplication($app);

			// If date_fund_actual is not set, use the estimate instead
			$loan['date_advance']   = (! empty($app->date_fund_actual)) ? $app->date_fund_actual : $app->date_fund_estimated;

			return $loan;
		}
		
	}


?>
