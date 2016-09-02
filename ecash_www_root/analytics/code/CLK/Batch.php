<?php

	class CLK_Batch extends Analysis_Batch implements Analysis_IBatchLegacy
	{

		/**
		 * Used for Customer Specific Model Overrides
		 * Example: HMS_Models_Loan
		 *
		 * @var string
		 */
		protected $customer_name = 'CLK';

		public static function getECashDb($company, $mode)
		{
			$lower_company = strtolower($company);

			switch (strtoupper($mode))
			{
				case 'LIVE':
					switch ($lower_company)
					{
						case 'ca':
						case 'ufc':
						case 'pcl':
						case 'ucl':
						case 'd1':
							return new DB_MySQLConfig_1(
								'reader.ecash3' . $lower_company . '.ept.tss',
								'ecash',
								'ugd2vRjv',
								'ldb',
								3306
							);

						default:
							throw new Exception('Invalid company, '. $company);
					}

				case 'RC':
				case 'LOCAL':
					switch ($lower_company)
					{
						case 'ca':
							$company_db = 'ldb_aml';
							break;

						case 'ufc':
						case 'ucl':
							$company_db = 'ldb_' . $lower_company;
							break;

						case 'd1':
							$company_db = 'ldb_d1_20080328';
							break;

						case 'pcl':
							$company_db = 'ldb_occ';
							break;

						default:
							throw new Exception('Invalid company, '. $company);
					}

					return new DB_MySQLConfig_1(
						'db117.ept.tss',
						'ecash',
						'ugd2vRjv',
						$company_db
					);

// 				case 'LOCAL':
// 					switch ($lower_company)
// 					{
// 						case 'pcl':
// 							return new DB_MySQLConfig_1(
// 								'db118.ept.tss',
// 								'ecash',
// 								'lacosanostra',
// 								'ldb_20081121'
// 							);
// 
// 						default:
// 							throw new Exception('Invalid company, '. $company);
// 					}

				default:
					throw new Exception('Invalid mode, '. $mode);
			}
		}

		/**
		 * @see Analysis_IBatchLegacy::getLegacyDb
		 */
		public static function getLegacyDb($company, $mode)
		{
			$lower_company = strtolower($company);

			switch (strtoupper($mode))
			{
				case 'LIVE':
				case 'RC':
				case 'LOCAL':
					switch ($lower_company)
					{
						case 'ca':
						case 'ufc':
						case 'pcl':
						case 'ucl':
						case 'd1':
							return new DB_MySQLConfig_1(
								'reader.ecash3' . $lower_company . '.ept.tss',
								'ecash',
								'ugd2vRjv',
								'ldb'
							);

						default:
							throw new Exception('Invalid company, '. $company);
					}

				default:
					throw new Exception('Invalid mode, '. $mode);
			}
		}

		/**
		 * @see Analysis_IBatch::getAnalysisDb
		 */
		public static function getAnalysisDb($mode)
		{
			switch (strtoupper($mode))
			{
				case 'LOCAL':
					return new DB_MySQLConfig_1(
						'localhost',
						'root',
						'',
						'analysis'
					);

				case 'RC':
					return new DB_MySQLConfig_1(
						'db117.ept.tss',
						'ecash',
						'ugd2vRjv',
						'analysis'
					);

				case 'LIVE':
					return new DB_MySQLConfig_1(
						'analytics.tss',
						'datax',
						'Eth7eeDu',
						'analysis',
						3306
					);

				default:
					throw new Exception('Invalid mode, '. $mode);
			}
		}

		protected $link_id;

		protected function addLoansForCustomer($ssn)
		{
			$this->link_id = NULL;

			// this will call processApplication, which, in the overridden
			// version, will set $this->link_id, if an appropriate "linking"
			// application is found -- kinda ghetto, but better than having
			// one mega function, right?
			$num_loans = parent::addLoansForCustomer($ssn);

			if ($this->link_id !== NULL)
			{
				$this->addUnconvertedApplications($this->link_id);
			}

			return $num_loans;
		}

		protected function processApplication($app)
		{
			$loan = parent::processApplication($app);

			if ($loan !== FALSE
				&& $app->archive_cashline_id !== NULL)
			{
				/*
				application is transient, so we have to grab the legacy data and merge it with the
				$loan array we've been building.
				*/

				$query = "
					select
						*
					from cashline_legacy.loans ln
					where
						ln.application_id = {$app->application_id}
						and ln.loan_most_recent = 'true'
						and ln.company_id = {$this->company_id}
				";
				$cl = $this->legacy_db->query($query)->fetch(PDO::FETCH_OBJ);

				if ($cl)
				{
					// this is the link between ldb and cashline_legacy
					$this->link_id = $app->application_id;

					$loan['current_cycle'] += $cl->loan_cycle_count;
					$loan['fees_accrued'] += $cl->loan_fees_accrued;
					$loan['fees_paid'] += ($cl->loan_fees_accrued - $app->converted_fees_bal);
					$loan['principal_paid'] += ($cl->loan_amount - $app->converted_princ_bal);

					if ($loan['status'] == 'paid' && !isset($loan['date_loan_paid']))
					{
						$loan['date_loan_paid'] = $cl->loan_date_paid;
					}

					if ($cl->loan_first_return > 0)
					{
						$loan['first_return_pay_cycle'] = $cl->loan_first_return;
					}

					if ($cl->loan_is_closed != 'true')
					{
						$loan['current_cycle']--;
					}
				}
				else
				{
					//throw new Exception('Missing archive data for transient application '.$app->application_id);
					echo 'WARNING: Missing archive data for transient application ', $app->application_id, "\n";
				}
			}

			return $loan;
		}

		protected function addUnconvertedApplications($link_id)
		{
			$query = "
				select
					ln.*
				from cashline_legacy.loans ln
				where
					ln.company_id = {$this->company_id}
					and ln.application_id = {$link_id}
					and ln.loan_most_recent = 'false'
			";
			$st_ln = $this->legacy_db->query($query);

			// process loans that finalized pre-conversion
			while ($ln = $st_ln->fetch(PDO::FETCH_OBJ))
			{
				$loan = array();
				$loan['status'] = $this->convertCashlineStatus("INACTIVE");
				$loan['date_advance'] = $ln->loan_dispersment_date;
				$loan['fund_amount'] = $ln->loan_amount;
				$loan['principal_paid'] = $ln->loan_amount; /// TODO: Should be loan_amount_paid?
				$loan['fees_accrued'] = $ln->loan_fees_accrued;
				$loan['fees_paid'] = $ln->loan_fees_accrued; /// TODO: Should be loan_fees_paid?
				/// TODO: Was 0 to NULL but old script escaping turned back to 0;
				$loan['first_return_pay_cycle'] = ($ln->loan_first_return == 0 ? 0 : $ln->loan_first_return);
				$loan['current_cycle'] = $ln->loan_cycle_count;

				try {
					$this->analytics->addLoan($loan);
					$this->loan_count++;
				}
				catch (Exception $e)
				{
					echo "WARNING: ignoring cashline loan for application_id ({$app->application_id})\n";
				}
			}
		}

		protected function getDisbursementTypes()
		{
			return array(
				'loan_disbursement',
				'card_loan_disbursement',
			);
		}
	}

?>
