<?php

	require_once('ecash_common/ecash_api/ecash_api.2.php');
	require_once('ole_smtp_lib.php');

	/***
		Customer class for customer-centric enterprise sites.
		
		Contains data on the customer and methods for accessing
		their data.
	*/
	class Ent_Customer
	{
		public $customer_id;	//Customer ID
		public $application_id;
		public $ssn;			//SSN
		
		public $property_short;
		public $application;
		public $status;
		
		public $can_react;
		public $view_payment_options;
		public $next_page;
		public $page_header;
		
		protected $sql;	//LDB sql connection
		protected $ecash_api;
		protected $app_campaign_manager; //For OLP-specific stuff
		
		protected $status_map;
		
		public function __construct($application_id, $prop_short, &$sql, &$app_campaign_manager)
		{
			$this->application_id = $application_id;
			$this->property_short = $prop_short;
			$this->sql = $sql;
			$this->app_campaign_manager = $app_campaign_manager;
			$this->next_page = 'ent_status';
			$this->page_header = null;

			$this->status_map = array(
				'active' => array(
					'/customer/servicing/active'
				),
				
				'bankruptcy' => array(
					'/customer/collections/bankruptcy/verified',
				),
				
				'collections' => array(
					'/external_collections/pending',
					'/external_collections/sent',
					'/customer/servicing/past_due',
					'/customer/servicing/funding_failed',
					'/customer/collections/new',
					'/customer/collections/indef_dequeue',
					'/customer/collections/arrangements/arrangements_failed',
					'/customer/collections/bankruptcy/unverified',
					'/customer/collections/contact/dequeued',
					'/customer/collections/contact/follow_up',
					'/customer/collections/contact/queued',
					'/customer/collections/quickcheck/ready',
					'/customer/collections/quickcheck/sent',
					'/customer/collections/quickcheck/return',
					//'/customer/collections/quickcheck/arrangements',
				),
				
				'declined' => array(
					'/prospect/confirm_declined',
					'/prospect/declined',
					'/prospect/disagree'
				),
				
				'denied' => array(
					'/applicant/denied'
				),
				
				'in_progress' => array(
					'/prospect/agree',
					'/prospect/preact_agree',
					'/prospect/confirmed',
					'/prospect/preact_confirmed',
					'/prospect/pending',
					'/prospect/preact_pending',
					'/applicant/underwriting/dequeued',
					'/applicant/underwriting/follow_up',
					'/applicant/underwriting/queued',
					'/applicant/verification/dequeued',
					'/applicant/verification/follow_up',
					'/applicant/verification/queued',
					'/applicant/fraud/dequeued',
					'/applicant/fraud/follow_up',
					'/applicant/fraud/queued',
					'/applicant/fraud/confirmed',
					'/applicant/high_risk/dequeued',
					'/applicant/high_risk/follow_up',
					'/applicant/high_risk/queued',
					'/customer/servicing/approved'
				),
				
				'paid' => array(
					'/customer/paid',
					'/external_collections/recovered'
				),
				
				'made_arrangements' => array(
					'/customer/collections/arrangements/current',
					'/customer/collections/quickcheck/arrangements'
				),
				
				'withdrawn' => array(
					'/applicant/withdrawn'
				)
			);
			
			$this->customer_id = $this->Find_Customer_ID();

			if (isset($this->application_id))
			{
				$mode = ($_SESSION['use_new_process'] == 1) ? BFW_MODE.'_READONLY' : BFW_MODE;
				$this->ecash_api = OLPECashHandler::getECashAPI($prop_short, $this->application_id, $mode);
								
				$this->status = $this->Get_Application_Status();
				$this->can_react = $this->Reactable();
				$this->view_payment_options = $this->Can_View_Payment_Options();
			}
			
			unset($_SESSION['cs']['hide_cust_name'],
				$_SESSION['cs']['hide_app_num'],
				$_SESSION['cs']['contact_us_submitted'],
				$_SESSION['cs']['profile_submitted']);
		}
		
		
		/**
			Finds a customer_id based on the SSN
		*/
		public function Find_Customer_ID()
		{
			$customer_id = NULL;
			
			if ($this->Find_Application() !== NULL)
			{
				$query = "SELECT DISTINCT customer_id
					FROM application
					INNER JOIN company USING (company_id)
					WHERE ssn = '{$this->ssn}'
						AND company.name_short = '{$this->property_short}'
					LIMIT 1";
				$result = $this->sql->Query($query);
				$row = $result->Fetch_Array_Row(MYSQLI_ASSOC);
				
				if (!empty($row))
				{
					$customer_id = $row['customer_id'];
				}
			}

			return $customer_id;
		}
		
		/**
			Returns the app_ids for this customer
		*/
		public function Get_Apps()
		{
			$apps = array();
			
			if (!is_null($this->customer_id))
			{
				$query = "SELECT application_id FROM application WHERE customer_id = '{$this->customer_id}' ORDER BY date_created DESC LIMIT 1";
				$result = $this->sql->Query($query);
				
				while($row = $result->Fetch_Object_Row())
				{
					$apps[] = $row->application_id;
				}
			}
			
			return $apps;
		}
		

		private function Find_Application()
		{
			$this->application = null;

			if (!empty($this->application_id))
			{
				$query = "
					SELECT
						a.application_id,
						a.name_first,
						a.name_last,
						a.email,
						a.street,
						a.city,
						a.unit,
						a.state,
						a.zip_code,
						a.phone_home,
						a.phone_work,
						a.phone_cell,
						ssn,
						employer_name,
						bank_account_type,
						income_monthly,
						income_direct_deposit,
						income_frequency,
						bank_name,
						bank_account,
						SUM(amount) AS balance
	        		FROM 
	        			application AS a
	        			LEFT JOIN transaction_register AS tr USING (application_id)
	        		WHERE IFNULL(transaction_status, 'complete') = 'complete'
	        			AND a.application_id = {$this->application_id}
	        		GROUP BY a.application_id
	        		ORDER BY balance DESC, date_effective DESC, a.date_created DESC";

				$result = $this->sql->Query($query);
				$row = $result->Fetch_Object_Row();
				
				if (!empty($row))
				{
					$this->application = $row;
					$this->ssn = $row->ssn;
				}
			}
			
			return $this->application;
		}

		private function Get_Application_Status()
		{
			$status = $this->ecash_api->Get_Application_Status_Chain($this->application_id);
			$status = $this->Find_Status($status);

			return $status;
		}
		
		private function Reactable()
		{
			return ($this->ecash_api->Get_Loan_Status() == 'paid');
		}
		
		private function Can_View_Payment_Options()
		{
			return !in_array($this->status, array('made_arrangements', 'bankruptcy'));
		}
		
		public function Build_Status($override = NULL, $holiday_array = NULL)
		{
			$page_data = array(
				'customer_name' => $this->Get_Customer_Name(),
				'application_id' => $this->application_id
			);

			$date_format = '%m/%d/%Y %h:%i%p PST';
			
			if (!is_null($this->application))
			{

				if (is_null($override))
				{
					$status = $this->status;
					
					//Uncomment for debugging purposes.
					//?page=ent_status&override=page to force to a specific page.
					/*if(isset($_SESSION['data']['override']))
					{
						$status = $_SESSION['data']['override'];
					}*/
					
					$page_data['app_status'] = $status;
					$page_data['app_status_display'] = strtoupper($status);
				}
				else
				{
					$status = $this->Get_Status_Override($override);
					$page_data['app_status'] = $status;
				}

				//If we have an active loan that has just made its last payment, we
				//need to display some different crap.
				if ($status == 'active' && intval($this->ecash_api->Get_Payoff_Amount()) == 0)
				{
					$status = 'paid';
					$page_data['show_note'] = TRUE;
				}
				
				switch ($status)
				{
					case 'paid':
					case 'recovered':
					{
						$page_data['app_status'] = 'paid';
						$page_data['app_status_display'] = 'PAID IN FULL';
						
						
						if (isset($page_data['show_note']))
						{
							$page_data['last_payment_date'] = $this->ecash_api->Get_Last_Payment_Date();
							
							$i = 4;
							do
							{
								// Changed from +3 days to +4 days per Mantis #11745
								$react_date = date('Y-m-d', strtotime("+{$i} days", strtotime($page_data['last_payment_date'])));
							
								// Get the calculated Business Days from the dates that we originally calculate.
								$business_days = $this->Get_Working_Days($page_data['last_payment_date'], $react_date, $holiday_array);
								$i++;
							}
							while($business_days < 4);
							
							// If the actual business days are correct (i.e. = 4) then we can use the originaly calculated date, otherwise recalculate with the difference.
							$page_data['react_time'] = ($business_days == 4) ? $react_date : date('m-d-Y', strtotime('+4 days', strtotime($react_date)));
							
							$this->can_react = FALSE;
						}
						else
						{
							$page_data['last_payment_date'] = $this->ecash_api->Get_Paid_Out_Date();
							$page_data['show_note'] = FALSE;
						}
						
						break;
					}
					
					case 'active':
					{
						$page_data['next_due_date']	= $this->ecash_api->Get_Current_Due_Date();
						$page_data['amount_due']	= $this->ecash_api->Get_Current_Due_Amount();
						$page_data['date_funded']	= $this->ecash_api->Get_Date_Funded();
						
						if (!empty($page_data['amount_due']))
						{
							$page_data['amount_due'] = '$' . number_format($page_data['amount_due'], 2);
						}
						
						$statuses = $this->app_campaign_manager->Get_Statuses($this->application_id, $date_format);
						$page_data['date_received']	= $this->Get_Status_Date((isset($statuses['preact_pending'])) ? 'preact_received' : 'received');
						$page_data['date_confirmed']= $this->Get_Status_Date((isset($statuses['preact_confirmed'])) ? 'preact_confirmed' : 'confirmed');
						$page_data['date_approved']	= $this->Get_Status_Date('approved');
						
						//Somehow there will be some apps that aren't in confirmed status (see: 43048004)
						//I don't really know wtf, so we'll just assign it to date_approved.
						if (empty($page_data['date_confirmed']) && !empty($page_data['date_approved']))
						{
							$page_data['date_confirmed'] = $page_data['date_approved'];
						}
						
						break;
					}
					
					case 'in_progress':
					{
						$page_data['app_status'] = 'in_progress';
						$page_data['app_status_display'] = 'IN PROGRESS';
						$statuses = $this->app_campaign_manager->Get_Statuses($this->application_id, $date_format);
						$page_data['date_received']	= $this->Get_Status_Date((isset($statuses['preact_pending'])) ? 'preact_received' : 'received');
						$page_data['date_confirmed']= $this->Get_Status_Date((isset($statuses['preact_confirmed'])) ? 'preact_confirmed' : 'confirmed');
						$page_data['date_approved']	= $this->Get_Status_Date('approved');
						$page_data['date_funded'] = $this->ecash_api->Get_Date_Funded();
						
						if (empty($page_data['date_approved']))
						{
							$page_data['date_approved'] = 'Pending';
						}
						
						if (empty($page_data['date_funded']))
						{
							$page_data['date_funded'] = 'Pending';
						}
						
						break;
					}

					
					case 'payment_history':
					{
						$page_data['app_status'] = 'payment_history';
						$page_data['last_payment_amount'] = $this->ecash_api->Get_Last_Payment_Amount();
						
						if (!empty($page_data['last_payment_amount']))
						{
							$page_data['last_payment_amount'] = '$' . number_format($page_data['last_payment_amount'], 2);
						}
						
						$page_data['last_payment_date'] = $this->ecash_api->Get_Last_Payment_Date();

						break;
					}
					
					//Here are some hilariously awesome fall-through case statements.
					case 'made_arrangements':
					{
						$page_data['app_status'] = 'arrangements';
						$page_data['app_status_display'] = 'MADE ARRANGEMENTS';
					}
					
					case 'balance':
					{
						if (!isset($page_data['app_status']))
						{
							$page_data['app_status'] = 'balance';
						}
						
						$page_data['next_due_date'] = $this->ecash_api->Get_Current_Due_Date();
						$page_data['amount_due'] = $this->ecash_api->Get_Current_Due_Amount();

						if (!empty($page_data['amount_due']))
						{
							$page_data['amount_due'] = '$' . number_format($page_data['amount_due'], 2);
						}
						
						$page_data['payoff_amount'] = $this->ecash_api->Get_Payoff_Amount();
						
						if (!empty($page_data['payoff_amount']))
						{
							$page_data['payoff_amount'] = '$' . number_format($page_data['payoff_amount'], 2);
						}

						$this->view_payment_options = ($this->status == 'active');
						
						break;
					}
					
					case 'bankruptcy':
					{
						$page_data['app_status'] = 'bankruptcy';
						$page_data['app_status_display'] = 'BANKRUPTCY VERIFIED';
						break;
					}
					
					case 'collections':
					{
						$page_data['app_status'] = 'collections';
						break;
					}
					
					case 'denied':
					{
						$statuses = $this->app_campaign_manager->Get_Statuses($this->application_id, $date_format);
						$page_data['date_received']	= $this->Get_Status_Date((isset($statuses['preact_pending'])) ? 'preact_received' : 'received');
						break;
					}
					
					case 'error':
					{
						$page_data['app_status'] = 'error';
						unset($page_data['app_status_display']);
						break;
					}

					
					default:
					{
						break;
					}
				}
			}
			
			$page_data['app_status'] = 'ent_status_' . $page_data['app_status'];
			if (empty($this->page_header)) $this->page_header = 'LOAN STATUS';

			return $page_data;
		}
		
		// Snagged this Function from php.net  [RV]
		// The function returns the no. of business days between two dates and it skeeps the holidays
		private function Get_Working_Days($start_date, $end_date, $holidays)
		{			
		    //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
		    //We add one to inlude both dates in the interval.
		    $days = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;
		
		    $no_full_weeks = floor($days / 7);
		    $no_remaining_days = fmod($days, 7);
		
		    //It will return 1 if it's Monday,.. ,7 for Sunday
		    $the_first_day_of_week = date("N",strtotime($start_date));
		    $the_last_day_of_week = date("N",strtotime($end_date));
		
		    //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
		    //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
		    if ($the_first_day_of_week <= $the_last_day_of_week)
		    {
		        if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) $no_remaining_days--;
		        if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) $no_remaining_days--;
		    }
		    else
		    {
		        if ($the_first_day_of_week <= 6) $no_remaining_days--;
		        //In the case when the interval falls in two weeks, there will be a Sunday for sure
		        $no_remaining_days--;
		    }
		
		    //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
			//---->february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
		   $working_days = $no_full_weeks * 5;
		   
		    if ($no_remaining_days > 0 )
		    {
		      $working_days += $no_remaining_days;
		    }
		
		    //We subtract the holidays
		    foreach($holidays as $holiday)
		    {
		        $time_stamp = strtotime($holiday);
		        //If the holiday doesn't fall in weekend
		        if (strtotime($start_date) <= $time_stamp 
		        	&& $time_stamp <= strtotime($end_date) 
		        	&& date("N",$time_stamp) != 6 
		        	&& date("N",$time_stamp) != 7
		        	) 
		        {
		        	$working_days--;
		        }
		    }
		
		    return $working_days -1;
		}
		
		private function Get_Status_Override($override)
		{
			$status = 'error';
			
			switch($override)
			{
				case 'ent_balance':
				case 'ent_next_payment':
				{
					$this->page_header = ($override == 'ent_balance') ? 'CURRENT BALANCE' : 'NEXT PAYMENT DUE';
					$status = 'balance';
					break;
				}
				
				case 'ent_payment_history':
				{
					$this->page_header = 'PAYMENT HISTORY';
					$status = 'payment_history';
					break;
				}
			}
			
			return $status;
		}
		
		public function Get_Customer_Name()
		{
			return ucwords($this->application->name_first . ' ' . $this->application->name_last);
		}
		
		public function Build_Generic($override, $enable_header = TRUE)
		{
			$page_data = array(
				'app_status' => $override,
				'customer_name' => $this->Get_Customer_Name(),
				'application_id' => $this->application_id,
				'id' => $this->application_id
			);
			
			if (!$enable_header)
			{
				$page_data['hide_cust_name'] = TRUE;
				$page_data['hide_app_num'] = TRUE;
			}

			$this->next_page = $override;
			
			return $page_data;
		}
		
		
		public function Build_Profile()
		{
			$page_data = array(
				'home_street'	=> $this->application->street,
				'home_city'		=> $this->application->city,
				'home_state'	=> $this->application->state,
				'home_zip'		=> $this->application->zip_code,
				'email_primary'	=> $this->application->email,
				//'phone_home'	=> $this->application->phone_home,
				//'phone_work'	=> $this->application->phone_work,
				//'phone_cell'	=> $this->application->phone_cell,
			
			);
			
			$page_data = array_merge($page_data, get_object_vars($this->application));

			$page_data['ssn_part_3'] = substr($page_data['ssn'], -4);
			$page_data['last_4_of_bank_account'] = substr($page_data['bank_account'], -4);
			$page_data['income_monthly'] = number_format($page_data['income_monthly'], 2, '.', ',');
			$page_data['income_frequency'] = str_replace('_', ' ', $page_data['income_frequency']);
			
			if ($this->application->income_direct_deposit == 'yes')
			{
				$page_data['income_type'] = 'Direct Deposit to ';
				$page_data['income_type'] .= ($this->application->bank_account_type == 'checking') ? 'Checking account' : 'Savings account';
			}
			else
			{
				$page_data['income_type'] = 'Paper Check';
			}

			
			$query = "SELECT name_full, phone_home, relationship
				FROM personal_reference
				WHERE application_id = {$this->application_id}";
			
			$result = $this->sql->Query($query);
			
			$count = 1;
			while($row = $result->Fetch_Object_Row())
			{
				$page_data["ref_{$count}_name"] = $row->name_full;
				$page_data["ref_{$count}_relationship"] = $row->relationship;
				$page_data["ref_{$count}_phone"] = implode('-', array(substr($row->phone_home, 0, 3), substr($row->phone_home, 3, 3), substr($row->phone_home, 6)));
				
				++$count;
			}
			
			$page_data = array_map('ucwords', $page_data);
			$page_data['state'] = strtoupper($page_data['state']);
			
			$page_data['app_status'] = 'ent_profile';
			$page_data['hide_app_num'] = TRUE;
			$page_data['customer_name'] = $this->Get_Customer_Name();
			
			$this->next_page = 'ent_profile';
			
			return $page_data;
		}
		
		public function Compare_Profile($data)
		{
			$page_data = array(
				'app_status' => 'ent_profile',
				'profile_submitted' => TRUE,
				'hide_cust_name' => TRUE,
				'hide_app_num' => TRUE,
				'customer_name' => $this->Get_Customer_Name()
			);

			$compare_fields = array(
				'street'	=> 'home_street',
				'city'		=> 'home_city',
				'state'		=> 'home_state',
				'zip_code'	=> 'home_zip',
				'email'		=> 'email_primary',
				'phone_home'=> 'phone_home',
				'phone_work'=> 'phone_work',
				'phone_cell'=> 'phone_cell');
			$changed = array();
			
			foreach($compare_fields as $ldb_field => $field)
			{
				$data[$field] = str_replace('-', '', $data[$field]);
				
				if (strcasecmp(trim($data[$field]), trim($this->application->$ldb_field)) !== 0)
				{
					$changed[$ldb_field] = array(
						'old' => $this->application->$ldb_field,
						'new' => $data[$field]
					);
				}
			}
			
			if (!empty($changed))
			{
				$body = '';
				foreach($changed as $field => $values)
				{
					$body .= "{$field} changed from \"{$values['old']}\" to \"{$values['new']}\"\n";
				}
				
				/* GForge 8078 [MJ]
				 * changed from_email from:
				 * 'from_email'	=> $this->application->email . ' <' . $this->Get_Customer_Name() . '>',
				 */
				$email_data = array(
					'application_id'=> $this->application_id,
					'customer_name'	=> $this->Get_Customer_Name(),
					'from_email'	=> $this->application->email,
					'subject'		=> 'Profile Data changed.',
					'message'		=> "Changed Values:\n{$body}"
				);
				
				$this->Send_Mail($email_data);
			}

			$this->next_page = 'ent_profile';
			
			return $page_data;
		}

		
		public function Submit_Contact_Us($data)
		{
			$page_data = array(
				'app_status' => 'ent_contact_us',
				'contact_us_submitted' => TRUE,
				'hide_cust_name' => TRUE,
				'hide_app_num' => TRUE,
				'customer_name' => $this->Get_Customer_Name()
			);
			
			$email_data = array();
			
			//We actually need a question/comment first
			if (!empty($data['body']))
			{
				/* Mantis 14235 [MJ]
				 * changed from_email from:
				 * $this->application->email . " <{$data['name_first']} {$data['name_last']}>" 
				 * to: $this->application->email
				 */
				$email_data = array(
					'application_id'=> $data['id'],
					'customer_name'	=> $data['name_first'] . ' ' .$data['name_last'],
					'from_email'	=> $this->application->email,
					'subject'		=> 'Contact Us Submission',
					'message'		=> "Message:\n{$data['body']}"
				);
				
				$this->Send_Mail($email_data);
			}
			
			$this->next_page = 'ent_contact_us';
			
			return $page_data;
		}
		
		
		private function Send_Mail($data)
		{
			$email_data = array(
				'email_primary'			=> $_SESSION['config']->customer_service_email,
				'email_primary_name'	=> $_SESSION['config']->legal_entity,
				'site'					=> $_SESSION['config']->site_name,
				'site_name'				=> $_SESSION['config']->site_name,
				'name_view'				=> $_SESSION['config']->legal_entity,
				
				'date_received'			=> date('m/d/Y g:i:sa')
			);

			$email_data = array_merge($email_data, $data);

			if (strcasecmp(BFW_MODE, 'LIVE') !== 0)
			{
				$email_data['email_primary'] = 'olpmail@gmail.com';
			}

				require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
				$tx = new OlpTxMailClient(false);

				try 
				{
					$r = $tx->sendMessage('live', 'OLP_ENT_GENERIC_EMAIL', $email_data['email_primary'], $_SESSION['statpro']['track_key'], $email_data);
				}
				catch(Exception $e)
				{
					$r = FALSE;
				}
				
				if ($r === FALSE)
				{
					$ole_applog = OLP_Applog_Singleton::Get_Instance(APPLOG_OLE_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);
					$ole_applog->Write("TrendEx Send Mail failed. Last message: \n" . print_r($email_data, true) . "\nCalled from " . __FILE__ . ':' . __LINE__);
				}
		}
		
		public function Format_Dates($data)
		{
			foreach($data as $key => $value)
			{
				if (preg_match('/^date|date$/', $key))
				{
					if (!empty($value))
					{
						$strtotime = strtotime($value);

						if ($strtotime != -1 && $strtotime !== FALSE)
						{
							$data[$key] = date('m-d-Y', $strtotime);
						}
					}
				}
			}

			return $data;
		}
		
		private function Get_Status_Date($name)
		{
			$date = NULL;
			
			switch ($name)
			{
				case 'received':
				{
					$status = $this->Unmap_Status('/prospect/pending');
					break;
				}
				
				case 'preact_received':
				{
					$status = $this->Unmap_Status('/prospect/preact_pending');
					break;
				}
				
				case 'confirmed':
				{
					$status = $this->Unmap_Status('/prospect/confirmed');
					break;
				}
				
				case 'preact_confirmed':
				{
					$status = $this->Unmap_Status('/prospect/preact_confirmed');
					break;
				}
				
				case 'approved':
				{
					$status = array(
						$this->Unmap_Status('/applicant/underwriting/queued'),
						$this->Unmap_Status('/applicant/underwriting/dequeued')
					);
					break;
				}

			}

			if (!empty($status))
			{
				$date = $this->ecash_api->Get_Status_Date($name, $status);
			}
			
			return $date;
		}
		
		
		private function Map_Status($status)
		{
			$parts = explode('::', $status);
			array_pop($parts);
			
			return '/' . implode('/', array_reverse($parts));
		}
		
		private function Unmap_Status($status)
		{
			$parts = explode('/', $status);
			array_shift($parts);
			
			return implode('::', array_reverse($parts)) . '::*root';
		}
		
		private function Find_Status($status)
		{
			$found_status = 'error';
			
			$status = $this->Map_Status($status);

			foreach($this->status_map as $type => $statuses)
			{
				if (array_search($status, $statuses) !== FALSE)
				{
					$found_status = $type;
					break;
				}
			}
			
			return $found_status;
		}
		
	}

?>