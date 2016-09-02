<?php

	class OPM_Batch extends Analysis_Batch
	{

		/**
		 * Used for Customer Specific Model Overrides
		 * Example: OPM_Models_Loan
		 *
		 * @var string
		 */
		protected $customer_name = 'OPM';

		public static function getECashDb($company, $mode)
		{
			switch (strtolower($company))
			{
				case 'opm_bsc':
					return new DB_MySQLConfig_1(
						'reader.ecashaalm.ept.tss',
						'ecashopm',
						'uoPeeH0p',
						'ldb_opm',
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
						'opm_analysis',
						3307
					);

				case 'live':
					return new DB_MySQLConfig_1(
						'analytics.tss',
						'datax',
						'Eth7eeDu',
						'opm_analysis',
						3306
					);
			}
			throw new Exception('Invalid mode, '. $mode);
		}
	}


?>
