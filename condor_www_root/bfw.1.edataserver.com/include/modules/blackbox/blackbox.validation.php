<?php
/**
 * Blackbox Validation
 * 
 * Does VALIDATION for BLACKBOX!
 */
require_once BFW_MODULE_DIR . 'olp/stat_limits.php';

	class BlackBox_Validation
	{
		protected $config;

		protected $valid;

		public function __construct(&$config)
		{
			$this->config = $config;

			$this->valid = TRUE;
		}


		public function Valid($valid = NULL)
		{
			if(!is_null($valid) && is_bool($valid))
			{
				$this->valid = $valid;
			}

			return $this->valid;
		}



		public function Validate_Cashline($property, &$targets, &$parent = NULL)
		{
			if ($this->Valid())
			{

				if ($this->config->debug->Debug_Option(DEBUG_RUN_CASHLINE) !== FALSE)
				{

					try
					{
						// Run the cashline check
						$cashline = $this->Cashline_Check($property, $targets, $parent);
					}
					catch(Exception $e)
					{
						$cashline = FALSE;
					}

					// used to disable stats for react
					if($cashline)
					{
						$this->config->react = $cashline[Previous_Customer_Check::STATUS_REACT];
						//Only set it if we ran for all companies, otherwise we really don't
						//care that much.
						if(empty($property))
						{
							$_SESSION['react_properties'] = $cashline[Previous_Customer_Check::STATUS_REACT];					
						}
					}
				}
				else
				{
					$this->config->Log_Event(EVENT_CASHLINE_CHECK, EVENT_SKIP);
				}

			}
		}



		public function Validate_DataX(&$datax, $event, $account, $source)
		{
			$valid = TRUE;

			if ($this->Valid())
			{

				if ($this->config->debug->Debug_Option(DEBUG_RUN_DATAX_IDV) !== FALSE)
				{
					// Check if rework is being used and a rework hasn't already been run yet. GForge [#5732] [DW]
					if($_SESSION["IDV_REWORK"] && !$_SESSION['REWORK_RAN'])
					{
						$rework_type = ($event == EVENT_DATAX_IDV) ? EVENT_DATAX_IDV_REWORK : EVENT_DATAX_PDX_REWORK;
						if ($rework_type == EVENT_DATAX_PDX_REWORK && strcasecmp($account, 'IC'))
						{
							// GForge #7322 - Don't run PDX Rework for non-IC accounts. [RM]
							$valid = FALSE;
						}
						else
						{
							$valid = $datax->Run($rework_type, $account, $source);
						}
					}
					else
					{
						if($this->config->bb_mode == MODE_DEFAULT)
						{
							Stats::Hit_Stats('bb_br_pass', $this->config->session, $this->config->log, $this->config->applog, $this->config->application_id);
						}

						//$datax_type will be either EVENT_DATAX_IDV or EVENT_DATAX_PDX_IMPACT
						$valid = $datax->Run($event, $account, $source);
					}

					$this->valid = $valid;
				}
				else
				{
					$this->config->Log_Event($event, EVENT_SKIP);
				}

			}
			else if(!$_SESSION["IDV_REWORK"])
			{
				Stats::Hit_Stats('bb_br_fail', $this->config->session, $this->config->log, $this->config->applog, $this->config->application_id);
			}


			return $valid;
		}




		public function Validate_Used_Info(&$targets, $bypass_used_info, $prop_short = NULL)
		{
			if ($this->Valid())
			{
				if ($this->config->debug->Debug_Option(DEBUG_RUN_USEDINFO) !== FALSE)
				{
					// Do we need to bypass the used info check? This is needed for
					// the new online confirmation process.
					if(!$bypass_used_info)
					{
						// Run the used info check
						$this->valid = $this->Used_Info($prop_short);
						
						$target_stats = OLPStats_Spaces::getInstance(
							$this->config->mode,
							0,
							$this->config->bb_mode,
							$this->config->config->page_id,
							$this->config->config->promo_id,
							$this->config->config->promo_sub_code
						);
						
						if(!$this->valid)
						{
							$targets->Open(NULL, FALSE);
							$this->config->Log_Event(EVENT_USEDINFO_CHECK, EVENT_FAIL, $prop_short);
							if ($target_stats) $target_stats->hitStat('used_info_check_fail');
						}
						else
						{
							$this->config->Log_Event(EVENT_USEDINFO_CHECK, EVENT_PASS, $prop_short);
						}
					}
				}
				else
				{
					$this->config->Log_Event(EVENT_USEDINFO_CHECK, EVENT_SKIP, $prop_short);
				}

			}

			return $this->valid;
		}



		/**
			@param $targets BlackBox_Target_Collection
			@param $tiers BlackBox_Tier_Collection
		*/
		protected function Cashline_Check($property, &$targets, &$parent = NULL)
		{
			// start timer
			if ($_SESSION['config']->mode != 'LIVE')
			{
				$timer = new Timer($this->config->applog);
				$timer->Timer_Start(EVENT_CASHLINE_CHECK);
			}

			// something like that
			$ssn = @$this->config->data['social_security_number'];
			$email = @$this->config->data['email_primary'];
			$bank_account = @$this->config->data['bank_account'];
			$bank_aba = @$this->config->data['bank_aba'];
			$home_phone = @$this->config->data['phone_home'];
			$drivers_license = @$this->config->data['state_id_number'];
			$dob = @$this->config->data['dob'];

			$result = NULL;
			$failed = FALSE;
			$results = NULL;

			// don't run this at all in email CONFIRMATION mode
			if ($this->config->bb_mode !== MODE_CONFIRMATION)
			{

				// targets which are open
				$open = $targets->Get_Open();

				// initialize our cashline object

				if($_SESSION['cs']['olp_process'] == 'ecashapp_react')
				{
					$_SESSION['data']['ecashapp'] = $_SESSION['config']->property_short;
				}

				// included bb_mode for when checking to expire previous incomplete apps in previous customer check. Mantis #12472 [DW]
				$cashline = Previous_Customer_Check::Get_Object(
					$this->config->sql, 
					$this->config->database,
					$property,
					$this->config->mode,
					$this->config->bb_mode
				);

				if(!is_object($cashline))
				{
					return NULL;
				}

				$single_company = NULL;
				if(isset($_SESSION['data']['ecashapp']) || (isset($_SESSION['react_target']) && $_SESSION['react_target'] !== FALSE))
				{
					$single_company = (isset($_SESSION['data']['ecashapp'])) ? $_SESSION['data']['ecashapp'] : $_SESSION['react_target'];
					$cashline->Set_Single_Company($single_company);
				}
				elseif ($this->config->bb_mode == MODE_ECASH_REACT
					&& isset($this->config->config->property_short)
					&& strcasecmp($this->config->config->property_short, 'bb') != 0)
				{
					// GForge #3128 - Non-react application listed as a react [RM]
					// BrianF stated that all reacts can be assumed to only target one
					// company for Cashline Checks.
					$single_company =  $this->config->config->property_short;
					$cashline->Set_Single_Company($single_company);
				}

				// CAN'T nest the if statements for these checks here,
				// otherwise, on prequal, we'll only run, say, the check
				// by home phone number if we have the email address

				// CASHLINE BY SSN :: REACT-ENABLED
				if (count($open) 
					&& (($this->config->bb_mode === MODE_DEFAULT) || (strlen($ssn) == 9))
					&& $cashline->Offers_Check(Previous_Customer_Check::TYPE_SSN))
				{

					// run the SSN check
					$open = $cashline->Check(
						$ssn,
						$open,
						Previous_Customer_Check::TYPE_SSN,
						$results,
						$this->config->application_id,
						($this->config->bb_mode === MODE_ECASH_REACT),
						($this->config->debug->Debug_Option(DEBUG_RUN_PREACT_CHECK) === TRUE)
					);
					$results = $cashline->Results();
					$result = $cashline->Result();

					// OK, so if we're on an enterprise site and we're a react,
					// then we only run cashline checks for the specific company!
					if(!$single_company
						&& $this->config->is_enterprise
						&& count($open)
						&& count(array_intersect($open, $results[Previous_Customer_Check::STATUS_REACT]))
						&& $this->config->bb_mode !== MODE_AGREE
					)
					{
						// first, filter the current result-set
						$results = $cashline->Filter_Company($results, $open);
						$result = $cashline->Decide($results, $open);
						
						// now, filter the future result-sets :-p
						$single_company = reset($open);
						$cashline->Set_Single_Company($single_company);
					}
					
					if(count($results[Previous_Customer_Check::STATUS_ACTIVE])
						&& !array_diff($results[Previous_Customer_Check::STATUS_ACTIVE],
						$results[Previous_Customer_Check::STATUS_REACT])
					)
					{
						$open = array();
					}

					$_SESSION['CASHLINE_RESULTS']['SSN'] = $result;
					$this->config->Log_Event(EVENT_CASHLINE_CHECK.'_SSN', $result, $single_company);

				}

				// CASHLINE BY EMAIL AND DATE OF BIRTH :: REACT-ENABLED
				if (count($open) && ($this->config->bb_mode !== MODE_ECASH_REACT) 
					&& ($this->config->bb_mode === MODE_DEFAULT || strlen($ssn) == 9 && $dob != '')
					&& $cashline->Offers_Check(Previous_Customer_Check::TYPE_EMAIL_DOB))
				{

					// Run SSN and DoB check
					$open = $cashline->Check(
						array($email, $dob),
						$open,
						Previous_Customer_Check::TYPE_EMAIL_DOB,
						$results,
						$this->config->application_id,
						false,
						($this->config->debug->Debug_Option(DEBUG_RUN_PREACT_CHECK) === TRUE)
					);
					$results = $cashline->Results();
					$result = $cashline->Result();

					// OK, so if we're on an enterprise site and we're a react,
					// then we only run cashline checks for the specific company!
					//if (!$single_company && $this->config->is_enterprise && 
					// count($open) && count(array_intersect($open, $results[Cashline::STATUS_REACT])) == 0)	
					// The intersection appears to be a logical flaw and allowing for non-reacts to be marked as react.s
					if (!$single_company && $this->config->is_enterprise && count($open) && $this->config->bb_mode !== MODE_AGREE)
					{

						// first, filter the current result-set
						$results = $cashline->Filter_Company($results, $open);
						$result = $cashline->Decide($results, $open);

						// now, filter the future result-sets :-p
						$single_company = reset($open);
						$cashline->Set_Single_Company($single_company);

					}

					$_SESSION['CASHLINE_RESULTS']['EMAIL_DOB'] = $result;
					$this->config->Log_Event(EVENT_CASHLINE_CHECK.'_EMAIL_DOB', $result, $single_company);

				}

				// Disable as per bug 9729. Emails can now be the same,
				// as long as the SSNs are different.
				// CASHLINE BY EMAIL

				// CASHLINE BY EMAIL AND SSN
				if (count($open) 
					&& (($this->config->bb_mode === MODE_DEFAULT) || ($email != '' && strlen($ssn) == 9))
					&& $cashline->Offers_Check(Previous_Customer_Check::TYPE_EMAIL_SSN))
				{

					// run the email & ssn check
					$open = $cashline->Check(
						array($email, $ssn),
						$open,
						Previous_Customer_Check::TYPE_EMAIL_SSN,
						$results,
						$this->config->application_id,
						false,
						($this->config->debug->Debug_Option(DEBUG_RUN_PREACT_CHECK) === TRUE)
					);
					$results = $cashline->Results();
					$result = $cashline->Result();

					// Save overall result
					$_SESSION['CASHLINE_RESULTS']['EMAIL_SSN'] = $result;
					$this->config->Log_Event(EVENT_CASHLINE_CHECK.'_EMAIL_SSN', $result, $single_company);

				}

				// CASHLINE BY HOME PHONE
				if (count($open) && ($this->config->bb_mode !== MODE_ECASH_REACT) 
					&& (($this->config->bb_mode === MODE_DEFAULT) || (strlen($home_phone) == 10))
					&& $cashline->Offers_Check(Previous_Customer_Check::TYPE_HOME_PHONE))
				{

					$open = $cashline->Check(
						$home_phone,
						$open,
						Previous_Customer_Check::TYPE_HOME_PHONE,
						$results,
						$this->config->application_id,
						false,
						($this->config->debug->Debug_Option(DEBUG_RUN_PREACT_CHECK) === TRUE)
					);
					$results = $cashline->Results();
					$result = $cashline->Result();

					$_SESSION['CASHLINE_RESULTS']['HOME_PHONE'] = $result;
					$this->config->Log_Event(EVENT_CASHLINE_CHECK.'_HOME_PHONE', $result, $single_company);

				}

				// CASHLINE BY BANK ACCOUNT
				// Adding in SSN for this check per requested in #8554
				if (count($open) 
					&& (($this->config->bb_mode === MODE_DEFAULT) || ($bank_account != '' && $bank_aba != '' && (strlen($ssn) == 9)))
					&& $cashline->Offers_Check(Previous_Customer_Check::TYPE_BANK_ACCOUNT))
				{

					$bank_ssn_info = array(
						'bank_account' => $bank_account,
						'bank_aba' => $bank_aba,
						'social_security_number' => $ssn
					);

					// run the bank account check
					$open = $cashline->Check(
						$bank_ssn_info,
						$open,
						Previous_Customer_Check::TYPE_BANK_ACCOUNT,
						$results,
						$this->config->application_id,
						false,
						($this->config->debug->Debug_Option(DEBUG_RUN_PREACT_CHECK) === TRUE)
					);
					$results = $cashline->Results();
					$result = $cashline->Result();

					$_SESSION['CASHLINE_RESULTS']['ACCOUNT'] = $result;
					$this->config->Log_Event(EVENT_CASHLINE_CHECK.'_ACCOUNT', $result, $single_company);

				}

				// add Find_By_Account_DoB for CLK (mantis 0012508)
				if (count($open) 
					&& (($this->config->bb_mode === MODE_DEFAULT) || ($bank_account != '' && $bank_aba != '' && $dob != ''))
					&& $cashline->Offers_Check(Previous_Customer_Check::TYPE_BANK_ACCOUNT_DOB))
				{
					$bank_dob_info = array(
						'bank_account' => $bank_account,
						'bank_aba' => $bank_aba,
						'dob' => $dob
					);
					$open = $cashline->Check(
								$bank_dob_info,
								$open,
								Previous_Customer_Check::TYPE_BANK_ACCOUNT_DOB,
								$results,
								$this->config->application_id,
								false,
								($this->config->debug->Debug_Option(DEBUG_RUN_PREACT_CHECK) === TRUE)
					);

					$results = $cashline->Results();
					$result = $cashline->Result();

					$_SESSION['CASHLINE_RESULTS']['BANK_ACCOUNT_DOB'] = $result;
					$this->config->Log_Event(EVENT_CASHLINE_CHECK.'_BANK_ACCOUNT_DOB', $result, $single_company);
				}

				// add Find_By_Home_Phone_DoB for CLK (mantis 0012508)
				if (count($open) 
					&& ($this->config->bb_mode == MODE_DEFAULT) || ($dob != '' && strlen($home_phone) == 10)
					&& $cashline->Offers_Check(Previous_Customer_Check::TYPE_HOME_PHONE_DOB))
				{
					$home_phone_dob_info = array(
						'home_phone' => $home_phone,
						'dob' => $dob
					);
					$open = $cashline->Check(
								$home_phone_dob_info,
								$open,
								Previous_Customer_Check::TYPE_HOME_PHONE_DOB,
								$results,
								$this->config->application_id,
								false,
								($this->config->debug->Debug_Option(DEBUG_RUN_PREACT_CHECK) === TRUE)
					);

					$results = $cashline->Results();
					$result = $cashline->Result();

					$_SESSION['CASHLINE_RESULTS']['HOME_PHONE_DOB'] = $result;
					$this->config->Log_Event(EVENT_CASHLINE_CHECK.'_HOME_PHONE_DOB', $result, $single_company);
				}

				// CASHLINE BY DRIVERS LICENSE
				if (count($open) && 
					(($this->config->bb_mode === MODE_DEFAULT || $this->config->bb_mode === MODE_ECASH_REACT)
						&& !empty($drivers_license)
						&& strcasecmp($drivers_license, 'none') != 0)
					&& $cashline->Offers_Check(Previous_Customer_Check::TYPE_DRIVERS_LICENSE))
				{

					// run the drivers license check
					$open = $cashline->Check(
						$drivers_license,
						$open,
						Previous_Customer_Check::TYPE_DRIVERS_LICENSE,
						$results,
						$this->config->application_id,
						($this->config->bb_mode === MODE_ECASH_REACT),
						($this->config->debug->Debug_Option(DEBUG_RUN_PREACT_CHECK) === TRUE));
					$results = $cashline->Results();
					$result = $cashline->Result();

					$_SESSION['CASHLINE_RESULTS']['DRIVERS_LICENSE'] = $result;
					$this->config->Log_Event(EVENT_CASHLINE_CHECK.'_DL', $result, $single_company);

				}

				// CASHLINE BY SSN AND DATE OF BIRTH
				// This is only done for eCash 3.0 and disabled per
				// the bug ticket #2856
			}

			// did we actually run any of the checks?
			if ($result !== NULL)
			{
				// Hit decisioning stats
				$target_stats = OLPStats_Spaces::getInstance(
					$this->config->mode,
					0,
					$this->config->bb_mode,
					$this->config->config->page_id,
					$this->config->config->promo_id,
					$this->config->config->promo_sub_code
				);
				
				$stat_array = array(
					Previous_Customer_Check::RESULT_BAD => 'prevcust_bad_fail',
					Previous_Customer_Check::RESULT_DENIED => 'prevcust_denied_fail',
					Previous_Customer_Check::RESULT_DNL => 'prevcust_dnl_fail',
					Previous_Customer_Check::RESULT_OVERACTIVE => 'prevcust_overactive_fail',
					Previous_Customer_Check::RESULT_UNDERACTIVE => 'prevcust_underactive_pass',
					Previous_Customer_Check::RESULT_REACT => 'prevcust_react_pass',
					Previous_Customer_Check::RESULT_NEW => 'prevcust_new_pass',
				);
				
				if (isset($stat_array[$result]))
				{
					if ($target_stats) $target_stats->hitStat($stat_array[$result]);
				}

				// log our combined result
				$this->config->Log_Event(EVENT_CASHLINE_CHECK, $result);

				// ECASH Reacts have a weird way of doing things like overactive can react :P [RL]
				if($this->config->bb_mode == MODE_ECASH_REACT)
				{
					
					if(!empty($results[Previous_Customer_Check::STATUS_BAD]) || 
					!empty($results[Previous_Customer_Check::STATUS_DENIED]) || 
					!empty($results[Previous_Customer_Check::STATUS_DNL]))
					{
						//hit an event for do not loan to know better what is going on [tp]
						if(!empty($results[Previous_Customer_Check::STATUS_DNL]))
						{
							foreach($results[Previous_Customer_Check::STATUS_DNL] as $dnl)
							{
								$this->config->Log_Event("DNL_HIT", $dnl);
							}
						}
						
						//hit an event for do not loan override, so we know what was overrided [tp]
						if(!empty($results[Previous_Customer_Check::STATUS_DNLO])) 
						{
							foreach($results[Previous_Customer_Check::STATUS_DNLO] as $dnl_override)
							{
								$this->config->Log_Event("DNL_OVERRIDE_HIT", $dnl_override);
							}
						}

						// close the targets we can't go to
						$close = array_diff($targets->Get_Open(), $open);
						$targets->Open($close, FALSE);
					}
				
					$this->valid = !empty($open);
				}
				else
				{
					// close the targets we can't go to
					$close = array_diff($targets->Get_Open(), $open);
					$open = $targets->Open($close, FALSE);

					$this->valid = !empty($open);

					// we no longer sell customers that are in "good standing" (i.e., they have
					// an inactive or active loan, but did not fail the check) to non-CLK companies
					if (!is_null($tiers)
						&& $this->Valid()
						&& (count($results[Previous_Customer_Check::STATUS_ACTIVE])
							|| count($results[Previous_Customer_Check::STATUS_REACT])))
					{
						$collection = $parent->Get_Collection();
						$close = array_diff($collection->Get_Use(), array($parent->Name()));
						$collection->Open($close, FALSE);
					}
				}

				// don't hit these stats in online CONFIRMATION mode or EACASH REACT [RSK] [RL]
				if(!in_array($this->config->bb_mode, array(MODE_AGREE, MODE_ONLINE_CONFIRMATION, MODE_ECASH_REACT)))
				{
					// figure out which stat we're going to hit, based off
					// the end result of the combined Email/SSN checks
					$stat = (($this->config->bb_mode == MODE_PREQUAL) ? 'prequal_' : 'bb_nms_').$result;
					Stats::Hit_Stats($stat, $this->config->session, $this->config->log, $this->config->applog, $this->config->application_id);

					// if we have an inactive loan, but can still go to CLK,
					// hit a react stat so we know how many bypass our caps
					if ($this->Valid() && count($results[Previous_Customer_Check::STATUS_REACT]))
					{
						// we'll only get a react count back from the SSN check,
						// so we don't have to switch the stat name off $strict
						Stats::Hit_Stats('bb_nms_react', $this->config->session, $this->config->log, $this->config->applog, $this->config->application_id);
					}
				}

			}
			else
			{
				// we didn't run
				$results = NULL;
			}

			// stop timer
			if ($_SESSION['config']->mode != 'LIVE')
			{
				$timer->Timer_Stop(EVENT_CASHLINE_CHECK);
			}

			// return cashline counts
			return $results;
		}


		protected function Used_Info($prop_short = NULL)
		{

			// assume we pass
			$valid = TRUE;

			// something like that
			$ssn = $this->config->data['social_security_number'];
			$aba = $this->config->data['bank_aba'];
			$account = $this->config->data['bank_account'];
			$email = $this->config->data['email_primary'];

			if (($this->config->bb_mode !== MODE_PREQUAL) || ($aba && $account && (strlen($ssn) == 9)))
			{

				// do the used_info check
				$used_info = new Used_Info($this->config->mode, $this->config, $this->config->ent_prop_list, $prop_short);
				$valid = $used_info->ABA_Check($aba, $account, $ssn);

			}

			// return open targets
			return $valid;

		}

		/**

		  @desc Qualify a candidate for Tier 1 properties
			@return FALSE or fund_amount
		*/
		public function Qualify($winner) 
		{
			$winner = Enterprise_Data::resolveAlias($winner);
			if($this->Valid())
			{
				if($this->config->data['income_monthly_net'] && $this->config->data['income_direct_deposit'])
				{
					$income = $this->config->data['income_monthly_net'];
					$direct_deposit = $this->config->data['income_direct_deposit'];
					$frequency_name = $this->config->data['income_frequency'];
				}
				else
				{
					$income = $_SESSION["cs"]["income_monthly"];
					$direct_deposit = $_SESSION["cs"]['income_direct_deposit'];
					$frequency_name = $_SESSION['cs']['income_frequency'];
				}
				
				if (($this->config->bb_mode !== MODE_PREQUAL) || ($income && $direct_deposit))
				{
					
					$fund_amount = $this->Find_Fund_Amount($winner, $income, $direct_deposit, $frequency_name);
					if ($fund_amount)
					{
						// save this
						$this->valid = TRUE;
						$outcome = EVENT_PASS;
					}
					else
					{
						$this->valid = FALSE;
						$outcome = EVENT_FAIL;
					}

					$this->config->Log_Event(EVENT_QUALIFY, $outcome, $winner);

				}
				else
				{
					// couldn't run it
					$fund_amount = NULL;
				}
			}

			return $fund_amount;

		}
		
		protected function Find_Fund_Amount($winner, $income, $direct_deposit, $frequency_name)
		{
			$fund_amount = null;
			//If it's already set in the CFE attributes, no point in doing the rest of this mess
			if(isset($_SESSION['cfe_attributes']['fund_qualified']) && is_numeric($_SESSION['cfe_attributes']['fund_qualified']))
			{
				$fund_amount = $_SESSION['cfe_attributes']['fund_qualified'];
			}
			else 
			{
				try
				{
					$db = &Setup_DB::Get_Instance('mysql', $this->config->mode . '_READONLY', $winner);
				
					$qualify = new OLP_Qualify_2(
						$winner,
						array(),
						$this->config->sql,
						$db,
						$this->config->applog,
						$this->config->mode,
						$this->config->title_loan
					);
					
					if ($this->config->config->ecash_react) $qualify->setIsEcashReact(TRUE);
				}
				catch(Exception $e)
				{
					$qualify = null;
				}

				// Use React Loan if React or Testing a Ecash React
				if((is_array($this->config->react) && in_array($winner, $this->config->react)) || ($this->config->config->ecash_react))
				{
					$app_id = $this->config->application_id;
					if(!empty($this->config->data['ecashapp']) && !empty($this->config->data['react_app_id']))
					{
						$app_id = $this->config->data['react_app_id'];
					}
					elseif(!empty($_SESSION['react']['transaction_id']))
					{
						$app_id = $_SESSION['react']['transaction_id'];
					}
					$fund_amount = $qualify->Calculate_React_Loan_Amount(
						$income,
						$direct_deposit,
						$app_id,
						strtolower($frequency_name)
					);
				}
				elseif(!is_null($qualify))
				{
					$fund_amount = $qualify->Calculate_Loan_Amount($income, $direct_deposit);
				}
			}
			
			return $fund_amount;
		}

	}
	
	
	
	class BlackBox_Validation_Agean extends BlackBox_Validation
	{
		protected function Find_Fund_Amount($winner, $income, $direct_deposit, $frequency_name)
		{
			$fund_amount = null;
			//if CFE already calculated it, just use that number.
			if(isset($_SESSION['cfe_attributes']['fund_qualified']) && is_numeric($_SESSION['cfe_attributes']['fund_qualified']))
			{
				$fund_amount = $_SESSION['cfe_attributes']['fund_qualified'];
			}
			else 
			{
				try
				{
					require_once(ECASH_COMMON_DIR . 'ecash_api/loan_amount_calculator.class.php');
					require_once('business_rules.class.php');
				
					$db = Setup_DB::Get_Instance('mysql', $this->config->mode . '_READONLY', $winner);
					require_once(BFW_CODE_DIR.'OLPECashHandler.php');
					$calc = OLPECashHandler::getLoanAmountCalculator($winner, $this->config->mode.'_READONLY');
				
					$rule_set = $this->Get_Rule_Set($db, $winner);
					$rule_set->income_monthly = $income;
					$rule_set->is_react = ((is_array($this->config->react) && in_array($winner, $this->config->react)) || ($this->config->config->ecash_react));
					$rule_set->application_list = $this->getApplicationList($winner);
					
					if($this->config->title_loan)
					{
						$rule_set->vehicle_vin = $this->config->data['vehicle_vin'];
						$rule_set->vehicle_make = $this->config->data['vehicle_make'];
						$rule_set->vehicle_model = $this->config->data['vehicle_model'];
						$rule_set->vehicle_series = $this->config->data['vehicle_series'];
						$rule_set->vehicle_style = $this->config->data['vehicle_style'];
						$rule_set->vehicle_year = $this->config->data['vehicle_year'];
					}

					$fund_amount = $calc->calculateMaxLoanAmount($rule_set);
				}
				catch(Exception $e)
				{
					$this->config->Applog_Write($e->getMessage());
					$fund_amount = null;
				}
			}

			return $fund_amount;
		}
		
		
		protected function getApplicationList($prop_short)
		{
			$ssn = $this->config->data['social_security_number'];
			$db = Setup_DB::Get_PDO_Instance('mysql',$this->config->mode.'_READONLY', $prop_short);
			$query = '
				SELECT
					application.application_id,
					DATE_FORMAT(application.date_created,\'%m-%d-%Y\') as date_created,
					application.date_fund_actual,
					application.date_first_payment,
					application.date_application_status_set,
					fund_actual,
					application.application_status_id,
					(
						SELECT 
							application_status.name 
						FROM 
							application_status 
						WHERE 
							application_status.application_status_id = application.application_status_id
					) as status_name
				FROM
					application
				JOIN 
					company 
				ON 
					(application.company_id = company.company_id)
				WHERE
					application.ssn = ?
				AND
					company.name_short = ?
			';
			$apps = $db->queryPrepared($query, array($ssn, $prop_short))->fetchAll(PDO::FETCH_OBJ);
			return (is_array($apps)) ? $apps : array();
		}
		
		private function Get_Rule_Set($db, $winner)
		{
			$rule_set = new stdClass();

			$loan_type = OLP_Qualify_2::Get_Loan_Type($winner, $this->config->title_loan);			
			$query = "SELECT
					lt.company_id,
					lt.loan_type_id,
					lt.name,
					rs.rule_set_id
				FROM loan_type lt
				INNER JOIN rule_set rs USING (loan_type_id)
				WHERE lt.company_id = (
					SELECT company_id
					FROM company
					WHERE name_short = '{$winner}'
					AND active_status = 'active'
				)
				AND lt.name_short = '{$loan_type}'
				AND lt.active_status = 'active'
				ORDER BY rs.date_effective DESC LIMIT 1";

			$result = $db->Query($query);
			if($result && ($row = $result->Fetch_Object_Row()))
			{
				$rule_set->company_id	= $row->company_id;
				$rule_set->loan_type_id	= $row->loan_type_id;
				$rule_set->loan_type_name	= $row->name;
				$rule_set->rule_set_id	= $row->rule_set_id;

				$b_rules = new Business_Rules($db);
				$rule_set->business_rules = $b_rules->Get_Rule_Set_Tree($row->rule_set_id);
			}
			
			return $rule_set;
		}
	}


?>
