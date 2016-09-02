<?php

	require_once('analytics.php');
	require_once('cashline_processor.php');
	require_once('timer.1.php');
	
	//define('BENCHMARKING', '');

	class Analytics_Batch
	{
		private static $companies = array(
			'ucl',
			'pcl',
			'd1',
			'ca'
		);

		private static $valid_modes = array('live', 'rc');

		public static function Main($argc, $argv)
		{
			$usage = "Usage:\nphp -q ".$_SERVER['SCRIPT_FILENAME']." {".implode("|",self::$valid_modes)."} {".implode("|", self::$companies)."}\n\n";
			
			
			// parse args
			if ($argc < 3)
				die("\nNot enough args. $usage");

			$company = strtolower(trim($argv[2]));
			$mode = strtolower(trim($argv[1]));
				
			if ($argc > 3 || !in_array($company, self::$companies) || !in_array($mode, self::$valid_modes))
			{
				die("\nInvalid arguments. $usage");
			}
			
			$ab = new Analytics_Batch($company, $mode);
			$ab->Go();
		}
		
		private static $company_odbc_lookup = array(
			'ucl' => 'acc_ucl',
			'pcl' => 'acc_pcl',
			'd1' => 'acc_d1',
			'ca' => 'acc_ca'
		);
		
		private static $status_lookup = array(
			'ACTIVE' => 'active',
			'INACTIVE' => 'paid',
			'HOLD' => 'quickcheck',
			'COLLECTION' => 'internal_collections',
			'DENIED' => 'denied',
			'BANKRUPTCY' => 'bankruptcy',
			'INCOMPLETE' => 'unknown',
			'' => 'unknown',
			'PENDING' => 'unknown',
			'SCANNED' => 'external_collections',
			'WITHDRAWN' => 'withdrawn'
		);
					
		private static $payperiod_lookup = array(
			'SEMI-MONTHLY' => 'twice_monthly',
			'BI-WEEKLY' => 'bi_weekly',
			'MONTHLY' => 'monthly'
		);		
		
		private $analytics;
		private $company;
		private $odbc_connection;
		private $cashline_processor;
		//only used to pass to other libraries
		private $mode;
		
		public function __construct($company, $mode)
		{
			if (!in_array($company, self::$companies))
			{
				throw new Exception("Invalid company identifier.");
			}
			$this->mode = $mode;
			$this->company = $company;
			$this->analytics = new Analytics($mode);
			$this->cashline_processor = new Cashline_Processor();
			$this->odbcConnect();
		}	
		
		public function Go()
		{
			
			$result = $this->odbcQuery("SELECT
						CUSTOMERNUMBER,
						STATUS,
						SSNUMBER,
						FIRSTNAME,
						LASTNAME,
						MIDDLEINITIAL,
						ADDRESS1,
						ADDRESS2,
						CITY,
						STATE,
						ZIPCODE,
						EMAILADDRESS,
						CUSTADDEDDATE,
						BIRTHDATE,
						DRIVERSLICENSE,
						PAYPERIOD,
						INCOME,
						ABA,
						ACCOUNTNUMBER,
						TELEPHONE,
						EMPLOYERPHONE,
						CELLPHONE,
						EMPLOYER
					FROM
						Customer");
			
			$count = 0;
			$start = time() - 1;
			
			while (odbc_fetch_row($result))
			{
				echo "Processing customer " . odbc_result($result, "CUSTOMERNUMBER") . " ... " . ($count / (time() - $start)) . " /sec \n";

				echo "Calling odbc_result for customernumber ... \n";
				if (odbc_result($result, "CUSTOMERNUMBER") > 0)
				{
					echo "Building customer array ... \n";
					$cm = array(
						'cashline_id' =>odbc_result($result, "CUSTOMERNUMBER"),
						'ssn' =>odbc_result($result, "SSNUMBER"),
						'name_last' =>odbc_result($result, "LASTNAME"),
						'name_first' =>odbc_result($result, "FIRSTNAME"),
						'name_middle' =>odbc_result($result, "MIDDLEINITIAL"),
						'phone_home' =>odbc_result($result, "TELEPHONE"),
						'phone_cell' =>odbc_result($result, "CELLPHONE"),
						'phone_work' =>odbc_result($result, "EMPLOYERPHONE"),
						'employer_name' =>odbc_result($result, "EMPLOYER"),
						'address_street' =>odbc_result($result, "ADDRESS1"),
						'address_unit' =>odbc_result($result, "ADDRESS2"),
						'address_city' =>odbc_result($result, "CITY"),
						'address_state' =>odbc_result($result, "STATE"),
						'address_zipcode' =>odbc_result($result, "ZIPCODE"),
						'drivers_license' =>odbc_result($result, "DRIVERSLICENSE"),
						'ip_address' =>'0.0.0.0',
						'email_address' =>odbc_result($result, "EMAILADDRESS"),
						'date_origination' =>date("Y-m-d", (odbc_result($result, "CUSTADDEDDATE")-61729) * 86400),
						'dob' =>date("Y-m-d", (odbc_result($result, "BIRTHDATE")-61729) * 86400),
						'pay_frequency' => self::$payperiod_lookup[odbc_result($result, "PAYPERIOD")],
						'income_monthly' => odbc_result($result, "INCOME"),
						'bank_aba' => odbc_result($result, "ABA"),
						'bank_account' => odbc_result($result, "ACCOUNTNUMBER")
					);
					
					echo "Calling odbcQuery ... \n";
					$result_tr = $this->odbcQuery("
						SELECT
							CUSTOMERNUMBER,
							TRANSACTIONNUMBER,
							TYPE,
							AMOUNT,
							PAID,
							PAYMENTHISTORY,
							DUEDATE,
							DATEPAID,
							DATE 
						FROM Transact 
						WHERE
							Transact.CUSTOMERNUMBER = " . odbc_result($result, "CUSTOMERNUMBER"));

					$transactions = array();
					while (odbc_fetch_row($result_tr))
					{
						$transactions[] = array(
							'custnum' => odbc_result($result_tr, "CUSTOMERNUMBER"),
							'transaction_id' => odbc_result($result_tr, "TRANSACTIONNUMBER"),
							'type' => odbc_result($result_tr, "TYPE"),
							'amount' => odbc_result($result_tr, "AMOUNT"),
							'paid' => odbc_result($result_tr, "PAID"),
							'payment_history' => odbc_result($result_tr, "PAYMENTHISTORY"),
							'dd0' => strtotime(odbc_result($result_tr, "DUEDATE")),
							'pd0' => strtotime(odbc_result($result_tr, "DATEPAID")),
							'td0' => strtotime(odbc_result($result_tr, "DATE"))
						);
					}
					echo "Calling process customer ... \n";
					$loans = $this->cashline_processor->Process_Customer($transactions);
					echo "process customer returned ... \n";
					
					if (defined('BENCHMARKING')) {
						$customer_timer_2->Stop_Timer();
						echo "BMK : customer > process customer took : " . $customer_timer_2->Get_Time(4) . "\n";
					}
					
					echo "Process customer finished ... \n";
					/*
					foreach ($loans as &$loan)
					{
						if (defined('BENCHMARKING')) {
							$loan_timer = new Code_Timer();
						}
						
						echo "Processing loan ... \n";
						if (next($loans) === false)
						{
							$loan->anal['status'] = self::$status_lookup[odbc_result($result, "STATUS")];
						}
						else
						{
							prev($loans);
							$loan->anal['status'] = 'paid';
						}
						//$this->analytics->Add_Loan($loan->anal);
						
						if (defined('BENCHMARKING')) {
							$loan_timer->Stop_Timer();
							echo "BMK : Loan took : " . $loan_timer->Get_Time(4) . "\n";
						}
					}*/
					odbc_free_result($result_tr);
					
					if (defined('BENCHMARKING')) {
						$customer_timer_2->Start_Timer();
					}
					//$this->analytics->End_Customer();
					
					if (defined('BENCHMARKING')) {
						$customer_timer_2->Stop_Timer();
						echo "BMK : customer > end customer took : " . $customer_timer_2->Get_Time(4) . "\n";
					}
					
					if (defined('BENCHMARKING')) {
						$customer_timer->Stop_Timer();
						echo "BMK : Customer took : " . $customer_timer->Get_Time(4) . "\n";
					}
					
					$count ++;
				}
			}
			odbc_free_result($result);
			$this->odbcClose();
			$this->analytics->End_Batch();
			if (defined('BENCHMARKING')) {
				$batch_timer->Stop_Timer();
			}
		}
		
		private function odbcConnect()
		{
			$this->odbc_connection = odbc_connect(self::$company_odbc_lookup[$this->company], "", "", SQL_CUR_USE_ODBC);
		}
		private function odbcClose()
		{
			odbc_close($this->odbc_connection);
		}
		private function odbcQuery($query)
		{
			if (!($st = @odbc_exec($this->odbc_connection, $query)))
			{
				throw new Exception("ODBC query failed: (" . odbc_errormsg($this->odbc_connection) . ") in query: " . $query);
			}
			return $st;
		}
	}

	Analytics_Batch::Main($_SERVER['argc'], $_SERVER['argv']);

?>
