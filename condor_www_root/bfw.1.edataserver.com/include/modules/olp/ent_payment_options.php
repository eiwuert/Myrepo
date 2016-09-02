<?php	

	class Ent_Payment_Options
	{
		private $application;
		private $ecash_api;
		private $sql;
		private $applog;
		private $prop_list;
		
		public $account_summary_doc;
		public $archive_id;
		
		public function __construct($application, &$sql, $prop_list)
		{
			$this->application = $application;
			$this->ecash_api = OLPECashHandler::getECashAPI($prop_list['property_short'], $application->application_id, BFW_MODE);
			$this->sql = $sql;
			$this->applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);
			$this->prop_list = $prop_list;
	
			$this->account_summary_doc = null;
			$this->archive_id = 0;
		}
		
		public function Can_View_Page($status)
		{
			$data = array();

			if($status != 'active')
			{
				$data['disallow_payment'] = 'bad_status';
			}
			elseif($this->ecash_api->Has_Paydown() || isset($_SESSION['account_summary']['esigned']))
			{
				$data['disallow_payment'] = 'has_paydown';
			}
			else
			{
				$next_due_date = $this->ecash_api->Get_Current_Due_Date();
	
				//We don't want to allow them to change this if their next payment
				//is scheduled within two days.
				$next_bus_date = $this->Get_Next_Business_Day(2);
	
				if((strtotime($next_due_date) - $next_bus_date) <= 0)
				{
					$data['disallow_payment'] = 'within_two_days';
				}
			}

			return $data;
		}
		
		/* Generate a list of paydown amounts. Will only display $0 if there
		 * is no other paydown amounts to show. If the user attempts to submit
		 * it, it will return an error.
		 */
		private function Generate_Pay_Down_Amounts($increment_amount = 50)
		{
			$pay_down_amounts = array();
			$balance_info = $this->ecash_api->Get_Balance_Information();
			$principal_amount = $balance_info->principal_pending - $this->ecash_api->Get_Current_Due_Principal_Amount();
			
			if($principal_amount < 0)
			{
				$principal_amount = 0;
			}
			
			do
			{
				$pay_down_amounts[] = number_format($principal_amount, 2);
				$principal_amount -= ($principal_amount % $increment_amount !== 0) ? $principal_amount % $increment_amount : $increment_amount;
			} while($principal_amount > 0);
			
			return array_reverse($pay_down_amounts);
		}
		
		public function Build_Page()
		{
			$page_data = array(
				'pay_amount'		=> $this->ecash_api->Get_Current_Due_Amount(),
				'pay_date'			=> $this->ecash_api->Get_Current_Due_Date(),
				'pay_down_amounts'	=> array()
			);

			$page_data['pay_down_amounts'] = $this->Generate_Pay_Down_Amounts();

			return $page_data;
		}
		
		public function Generate_Account_Summary_Doc($data, $prop_short)
		{
			$_SESSION['account_summary'] = array();
			
			$sql = Setup_DB::Get_Instance('blackbox', $_SESSION['config']->mode, $prop_short);
			$qualify = new OLP_Qualify_2($prop_short, $_SESSION['holiday_array'], $sql, $this->sql, $this->applog, $_SESSION['config']->mode, $data['title_loan']);

			/*$payoff_amount = $this->ecash_api->Get_Payoff_Amount();
			
			$loan_amount = ($data['pay_down_type'] == 'increments') ? intval($data['increment_amount']) : $payoff_amount;

			$qualify_info = $qualify->Finance_Info(strtotime($payoff_date), strtotime($fund_date), $loan_amount);
			$qualify_info['apr'] = $qualify->Calc_APR($payoff_amount, $fund_date, $loan_amount, $qualify_info['finance_charge']);
			
			$pd_fin_charge = $qualify_info['finance_charge'];
			*/

			$payoff_amount = $this->ecash_api->Get_Payoff_Amount();
			$paydown_amount = ($data['pay_down_type'] == 'increments') ? intval($data['increment_amount']) : $payoff_amount;
			
			if(($paydown_amount <= 0) || (!in_array($paydown_amount, $this->Generate_Pay_Down_Amounts()) && $paydown_amount != $payoff_amount))
			{
				return array(
					'disallow_payment' => 'payment_not_valid'
				);	
			}
			
			$payoff_date = $this->ecash_api->Get_Current_Due_Date();
			$fund_date = $this->ecash_api->Get_Date_Funded();
			$loan_amount = $this->ecash_api->Get_Loan_Amount();
			$pd_fin_charge = $this->ecash_api->Get_Current_Due_Service_Charge_Amount();
			$qualify_info = $qualify->Finance_Info(strtotime($payoff_date), strtotime($fund_date), $loan_amount, $pd_fin_charge);
			$initials = strtoupper($this->application->name_first[0].$this->application->name_last[0]);
			
			$_SESSION['account_summary']['amount'] = $paydown_amount;
			$_SESSION['account_summary']['date'] = $payoff_date;
			
			//We need to display different data if they're paying out their loan.
			$paid_in_full = false;
			if($paydown_amount == $payoff_amount)
			{
				$_SESSION['account_summary']['payout'] = TRUE;
				$paid_in_full = true;
				$pd_amount = 0;
				$pd_total = 0;
				$pd_next_total = 0;
				$pd_fin_charge = 0;
				$pd_next_due_date = 'Not Scheduled';
				
				$display_amount = $paydown_amount;
			}
			else
			{
				$pd_amount = $this->ecash_api->Get_Current_Due_Principal_Amount($paydown_amount);
				$pd_total = $this->ecash_api->Get_Current_Due_Amount($paydown_amount);
				$pd_next_total = $this->ecash_api->Get_Next_Due_Amount($paydown_amount);
				$pd_next_due_date = date('m/d/Y', strtotime($this->ecash_api->Get_Next_Due_Date()));
				
				$display_amount = $pd_total;
			}
			
			//Get the logo for the doc here
			switch(strtolower($prop_short))
			{
				default:
				case 'ufc': $logo = 'http://imagedataserver.com/SHARED/live/themes/HEBRIDES/skins/nms/ufc/usfastcash.com/media/image/usfastcash_small.jpg'; break;
				case 'pcl': $logo = 'http://imagedataserver.com/SHARED/live/themes/HEBRIDES/skins/nms/pcl/oneclickcash.com/media/image/oneclickcash_small.jpg'; break;
				case 'ucl': $logo = 'http://imagedataserver.com/SHARED/live/themes/HEBRIDES/skins/nms/ucl/unitedcashloans.com/media/image/unitedcashloans_small.jpg'; break;
				case 'd1': $logo = 'http://imagedataserver.com/SHARED/live/themes/HEBRIDES/skins/nms/d1/500fastcash.com/media/image/fastcash_small.jpg'; break;
				case 'ca': $logo = 'http://imagedataserver.com/SHARED/live/themes/TASMANIA/skins/nms/ca/ameriloan.com/media/image/ameriloan_small.jpg'; break;
			}
			
			$_SESSION['account_summary']['condor_data'] = array(
				'BankName'			=> $this->application->bank_name,
			
				'CompanyLogoSmall'	=> "<img src=\"{$logo}\" />",
				'CompanyPhone'		=> '1-' . $this->prop_list['cs_phone'],
				'CompanyFax'		=> '1-' . $this->prop_list['cs_fax'],
				'CompanyNameLegal'	=> $_SESSION['config']->legal_entity,

				'CustomerESig'		=> '',
				'CustomerNameFull'	=> ucwords($this->application->name_first . ' ' . $this->application->name_last),
				'CustomerStreet'	=> ucwords($this->application->street),
				'CustomerUnit'		=> strtoupper($this->application->unit),
				'CustomerCity'		=> ucwords($this->application->city),
				'CustomerState'		=> strtoupper($this->application->state),
				'CustomerZip'		=> $this->application->zip_code,
				
				'LoanApplicationID' => $this->application->application_id,
				
				'LoanDueDate'		=> date('m/d/Y', strtotime($this->ecash_api->Get_Current_Due_Date())),
				'LoanBalance'		=> '$' . number_format($payoff_amount, 2),
				'LoanFinCharge'		=> '$' . number_format($this->ecash_api->Get_Current_Due_Service_Charge_Amount(), 2),
				'LoanAPR'			=> $qualify_info['apr'] . '%',
				'LoanFundAmount'	=> '$' . number_format($this->ecash_api->Get_Loan_Amount(), 2),

				'PDDueDate'			=> date('m/d/Y', strtotime($this->ecash_api->Get_Current_Due_Date())),
				'PDFinCharge'		=> '$' . number_format($pd_fin_charge, 2),
				'PDAmount'			=> '$' . number_format($pd_amount, 2),
				'PDTotal'			=> '$' . number_format($pd_total, 2),
				
				'PDNextDueDate'		=> $pd_next_due_date,
				'PDNextTotal'		=> '$' . number_format($pd_next_total, 2),
				
				'CustomerInitialPayDown' => (($paid_in_full)?'___':$initials),
				'CustomerInitialInFull' => (($paid_in_full)?$initials:'___'),
				
				'Today'				=> date('m/d/Y')
			);
			
			
			return array(
				'display_esig' => TRUE,
				'debit_amount' => '$' . number_format($display_amount, 2),
				'debit_date' => $this->ecash_api->Get_Current_Due_Date()
			);
		}
		
		private function Format_Phone($phone, $one = TRUE)
		{
			return (($one) ? '1-' : '') . substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
		}
		
		public function ESign_Account_Summary_Doc($data, $prop_short)
		{
			$condor_data = $_SESSION['account_summary']['condor_data'];
			$condor_data['CustomerESig'] = $condor_data['CustomerNameFull'];
			
			$return = array(
				'esig_successful' => TRUE
			);
			
			//Let's not let them sign twice or something dumb.
			if(empty($_SESSION['account_summary']['esigned']))
			{
				try
				{
					require_once ('prpc/client.php');
					require_once ('esign_doc.php');
					
					if(!$_SESSION['account_summary']['amount'] || !$_SESSION['account_summary']['date'])
					{
						throw new Exception("Payout is invalid.");
					}
					
					$prpc_server = Server::Get_Server($_SESSION['config']->mode, 'CONDOR', $prop_short);
					$condor_api = new prpc_client("prpc://{$prpc_server}/condor_api.php");
					
					$condor_doc = $condor_api->Create(
						'Account Summary - FAX',			// Template
						$condor_data,						// Data
						TRUE,								// Archive
						$this->application->application_id,	// Application ID
						$_SESSION['statpro']['track_key'],	// Track key
						$_SESSION['statpro']['space_key']	// Space key
					);
					
					//Don't store the entire doc inside the session because that is lame.
					unset($condor_doc['document']->data);
					$_SESSION['condor_data'] = $condor_doc;
				
					//Sign and add it to LDB
					$esign_doc = new eSignature($condor_doc['archive_id'], $_SESSION['config']->mode, $prop_short);
					$result = $esign_doc->Sign_Doc();
					$_SESSION['document_event'] = TRUE;

					if($result[0])
					{
						$this->ecash_api->Set_Agent_Id('olp');
						if($_SESSION['account_summary']['payout'])
						{
							$this->ecash_api->Payout($_SESSION['account_summary']['amount'], $_SESSION['account_summary']['date']);
							$this->ecash_api->Add_Comment('Web Payout received for $' . $_SESSION['account_summary']['amount'] . ' for ' . date('m/d/Y', strtotime($_SESSION['account_summary']['date'])));
						}
						else
						{
							$paydown_amount = $this->ecash_api->Get_Current_Due_Principal_Amount($_SESSION['account_summary']['amount']);
							
							$this->ecash_api->Add_Paydown($_SESSION['account_summary']['amount'], $_SESSION['account_summary']['date']);
							$this->ecash_api->Add_Comment('Web Paydown received for $' . $paydown_amount . ' for ' . date('m/d/Y', strtotime($_SESSION['account_summary']['date'])));
						}
						
						$this->ecash_api->Push_To_Queue(eCash_API_2::QUEUE_ACCOUNT_SUMMARY);
	
						$_SESSION['account_summary']['esigned'] = TRUE;
					}
				}
				catch(Exception $e)
				{
					$result = array(false, 'Caught exception ' . $e->getMessage());
				}
				
				if(!$result[0])
				{
					$this->applog->Write('Failed to esign Account Summary doc: ' . $result[1]);
					$return = array(
						'esig_error' => TRUE
					);
				}
			}
			
			return $return;
		}
		
		/**
			This function is terrible
		*/
		private function Get_Next_Business_Day($num_days = 1)
		{
			$today = mktime(0,0,0);
			$check_date = $today;
			$one_day = 60*60*24;
			$modifier = 0;

			require_once(BFW_CODE_DIR . 'pay_date_validation.php');
			$pdv = new Pay_Date_Validation(array(), $_SESSION['holiday_array']);
			
			while($check_date < ($today + ($one_day * $num_days) + $modifier) || $pdv->_Is_Holiday($check_date) || $pdv->_Is_Weekend($check_date))
			{
				if(($pdv->_Is_Holiday($check_date) || $pdv->_Is_Weekend($check_date)) && $check_date != $today)
				{
					$modifier += $one_day;
				}
				
				$check_date += $one_day;
			}
			
			return $check_date;
		}
	}
?>
