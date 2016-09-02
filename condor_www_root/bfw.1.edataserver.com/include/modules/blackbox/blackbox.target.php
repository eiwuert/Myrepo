<?php

	/**
	 * @name BlackBox_Target
	 * @version 0.1
	 * @author Andrew Minerd
	 * 
	 * @desc
	 * A class to store information about a "target": a
	 * potential loan provider. Each target is identified
	 * by a "property short", a two to five (or so)
	 * character alphanumeric unique identifier (ex. CA, VP2).
	 * 
	 * The BlackBox_Target class is used by BlackBox,
	 * and will probably never need to be instantiated
	 * on it's own.
	 */
	 

	class BlackBox_Target_OldSchool implements iBlackBox_Target, iBlackBox_Serializable
	{

		protected $name;
		protected $rate;
		protected $rules;

		// BlackBox_Stat object
		protected $stats;
		protected $config;
		protected $failed;

		// this should change
		protected $datax_idv;
		protected $datax_account;
		protected $vendor_qualify_post;
		protected $verify_post_type;

		protected $tier;		//Tier number this target belongs in
		protected $collection;	//Target this collection is in

		protected $datax;
		protected $fund_amount;
		protected $lead_amount;

		protected $overflow;	//1 = enabled, 0 = disabled

		protected $filters;
		protected $withheld_targets;

		protected $parent_id = 0;
		protected $target_id;
		protected $targets;
		protected $parent;
		
		protected $weight_type;
		protected $run_fraud;
		
		/**

			@desc BlackBox_Target constructor

			@param $sql object MySQL server link
			@param $target array An associate array of
				the target properties, most likely from
				a database row

			@return None.

		*/
		public function __construct(&$config = NULL, $target = NULL)
		{
			$this->tier = NULL;
			$this->collection = NULL;
			$this->config = &$config;
			$this->datax_account = NULL;
			$this->withheld_targets = NULL;
			$this->run_fraud = FALSE;

			if ($config && is_array($target))
			{

				// try to get it from a database row
				$this->From_Row($config, $target);

			}

			$this->datax = NULL;
			$this->fund_amount = NULL;
		}



		public function __destruct()
		{
			unset($this->datax);
			unset($this->filters);
			unset($this->withheld_targets);
			unset($this->rules);
			unset($this->stats);
			unset($this->config);
			unset($this->collection);
		}


		public function Add_Child(&$child)
		{
			if(empty($this->targets))
			{
				$this->targets = new BlackBox_Target_Collection($this->config);
			}
			
			$this->targets->Add($child, true);
			$child->Set_DataX($this->datax);
			$child->Set_Parent($this);
		}
		
		public function runFraud()
		{
			return $this->run_fraud;
		}
		
		public function Has_Children()
		{
			return !empty($this->targets);
		}
		
		public function Has_Parent()
		{
			return !empty($this->parent_id);
		}

		public function Get_Targets()
		{
			return $this->targets->Get_Targets();
		}
		
		public function Has_Target($name)
		{
			return $this->targets->Has_Target($name);
		}
		
		public function Get_Target_Collection()
		{
			return $this->targets;
		}


		public function Tier()
		{
			return (is_null($this->tier)) ? FALSE : $this->tier;
		}
		
		
		/**

			@desc Return the rules for this target.

			@return array An array of BlackBox_Rule_OldSchool objects

		*/
		public function Rules()
		{
			return($this->rules);
		}

		public function IDV()
		{
			return($this->datax_idv);
		}

		public function VerifyPost()
		{
			return($this->vendor_qualify_post);
		}

		public function VerifyPostType()
		{
			return($this->verify_post_type);
		}

		/**

			@desc Return the name of this target (the
				property short).

			@return string The property short of this target

		*/
		public function Name()
		{
			return($this->name);
		}

		/**

			@desc Return the current rate ($$) for this target:
				what we get paid each time we send them a loan.

			@return integer The current dollar rate

		*/
		public function Rate()
		{
			return($this->rate);
		}

		/**
		This function set the parent_id
		*/
		public function Set_Parent_ID($parent_id)
		{
			$this->parent_id = $parent_id;
		}
		
		/**
		This function get the parent_id
		*/
		public function Get_Parent_ID()
		{
			return $this->parent_id;
		}
		
		/**
		This function set the parent_id
		*/
		public function Set_Target_ID($target_id)
		{
			$this->target_id = $target_id;
		}
		
		/**
		This function get the parent_id
		*/
		public function Get_Target_ID()
		{
			return $this->target_id;
		}
		
		/**

			@desc Provide protected access to the Stats object:
				return an array of stats. For more information,
				see BlackBox_Stats::Stats().

			@return array Associate array of stat values

		*/
		public function Stats($stat_names = NULL)
		{
			$stats = $this->stats->Stats($stat_names);
			return($stats);
		}

		/**

			@desc Provide protected access to the Stats object:
				return an array of limits. For more information,
				see BlackBox_Stats::Limits().

			@return array Associate array of stat limits

		*/
		public function Limits($stat_names = NULL)
		{
			$limits = $this->stats->Limits($stat_names);
			return($limits);
		}

		/**

			@desc Provide protected access to the Stats object:
				return an array of totals. For more information,
				see BlackBox_Stats::Totals().

			@return array Associate array of stat totals

		*/
		public function Totals($stat_names = NULL)
		{
			$totals = $this->stats->Totals($stat_names);
			return($totals);
		}

		public function Failed()
		{
			$failed = FALSE;

			if($this->failed)
			{
				$failed = $this->failed;
			}

			return $failed;
		}

		/**

			@desc Perform the stats check for this target. For
			more information,	see BlackBox_Stats::Check_Stats().

			@param $blackbox BlackBox
			@param $stat_names array An array of stat names to check
			@param $simulate boolean

			@return boolean Outcome

		*/
		public function Check_Stats(&$blackbox, $stat_names = NULL, $simulate = FALSE)
		{
			// reset
			$this->failed = FALSE;

			// pass it on
			$valid = $this->stats->Check_Stats($blackbox, $stat_names, $simulate);

			// our outcome
			$outcome = ($valid) ? EVENT_PASS : EVENT_FAIL;
			if($simulate && (!$valid)) $outcome = EVENT_PREFIX_SIMULATE.$outcome;

			// log our event
			$blackbox->Log_Event(EVENT_STAT_CHECK, $outcome, $this->name);

			if(!$valid)
			{
				$this->failed = $this->stats->Failed();
			}

			return($valid);
		}

		/**

			@desc Perform the rules check for this target. To
				find the actual rules, see BlackBox_Rules.

			@param $blackbox BlackBox Parent BlackBox object
			@param $data array Associative array of normalized
				loan application data

			@return boolean Outcome

		*/
		public function Run_Rules(&$blackbox, &$data, $tier = NULL)
		{
			
			// reset
			$this->failed = FALSE;

			// initiliaze variables
			$valid = TRUE;
			$temp = NULL;

			// set our target name for the rules
			Blackbox_Rules::Target($this->name);
			
			// Log stats to a different space
			$target_stat = OLPStats_Spaces::getInstance(
				$this->config->config->mode,
				$this->target_id,
				$this->config->bb_mode,
				$this->config->config->page_id,
				$this->config->config->promo_id,
				$this->config->config->promo_sub_code
			);

			// data rules
			foreach($this->rules as &$rule)
			{
				// run the rule
				$valid = $rule->Run($blackbox, $this, $data);

				if(!is_null($valid))
				{
					if($rule->Event())
					{
						//We don't want to fail CLK if they don't have the required references
						//since they'll be prompted for them on the confirmation page.
						if($rule->Event() == 'REQ_REFS' && $tier == 1)
						{
							$outcome = EVENT_SKIP;
							$valid = TRUE;
						}
						else
						{
							$outcome = ($valid) ? EVENT_PASS : EVENT_FAIL;
						}

						// log the outcome

						$blackbox->Log_Event($rule->Event(), $outcome, $this->name);

						if(!$valid)
						{
							if ($target_stat) $target_stat->hitStat($rule->Event() . '_fail');
							
							switch($rule->Event())
							{
								case 'MIN_INCOME':
									$_SESSION['MINIMUM_INCOME_FAIL'] = TRUE;
									break;

								case 'REQ_REFS':
									$_SESSION['references_required'] = TRUE;
									break;
							}
						}
					}
					if (!$valid)
					{
						// store which rule we failed
						$this->failed = $rule->Event();
					}
				}

				unset($rule);

				// only have to fail one!
				if ($valid === FALSE) break;
			}

			$outcome = ($valid === FALSE) ? EVENT_FAIL : EVENT_PASS;
			$blackbox->Log_Event(EVENT_RULE_CHECK, $outcome, $this->name);

			unset($data);

			return($valid);
		}

		public function Run_CFE($blackbox, $data)
		{
			$valid = NULL;
			if(Enterprise_Data::isCFE($this->Name()))
			{
				$cfe = new Blackbox_CFE($this->Name(), $_SESSION['data'], $this->config);
				$valid =  $cfe->Run();
				if(!is_null($valid))
				{
					$outcome = ($valid) ? EVENT_PASS : EVENT_FAIL;
					$blackbox->Log_Event(EVENT_CFE_CHECK, $outcome, $this->name);
					if(!$valid)
					{
						$this->failed = EVENT_CFE_CHECK;
					}
				}
			}
			return $valid;
		}
		
		public function Run_Rule($type, &$data)
		{
			$valid = null;
		
			if(!empty($this->rules[$type]))
			{
				$valid = $this->rules[$type]->Run($this->config, $this, $data);
				
				if(!is_null($valid))
				{
					$outcome = ($valid) ? EVENT_PASS : EVENT_FAIL;
					$this->config->Log_Event($this->rules[$type]->Event(), $outcome, $this->name);
				}
			}
			
			return $valid;
		}
		
		
		/**

			@desc See BlackBox::Sleep()

		*/
		public function Sleep()
		{
			$rules = array();

			foreach ($this->rules as &$rule)
			{
				if ($rule instanceof BlackBox_Rule_OldSchool)
				{
					$rules[] = $rule->Sleep();
				}
			}

			$target = array();
			$target['tier'] = $this->tier;
			$target['name'] = $this->name;
			$target['target_id'] =   $this->target_id;
			$target['rate'] = $this->rate;
			$target['stats'] = $this->stats->Sleep();
			$target['rules'] = $rules;
			$target['verify_post'] = $this->vendor_qualify_post;
			$target['verify_post_type'] = $this->verify_post_type;
			$target['class_type'] = get_class($this);
			$target['filters'] = $this->filters->Get_Filter_Array();
			$target['fund_amount'] = (isset($this->fund_amount)) ? $this->fund_amount : 0;
			
			if(!empty($this->targets))
			{
				$target['targets'] = $this->targets->Sleep();
				$target['open'] = $this->targets->Get_Open();
			}

			return($target);
		}

		protected function Valid_Data($data)
		{
			$valid = is_array($data);

			if($valid) $valid = (isset($data['rules']) && is_array($data['rules']));
			if($valid) $valid = (isset($data['stats']) && is_array($data['stats']));
			if($valid) $valid = (isset($data['filters']) && is_array($data['filters']));
			if($valid) $valid = (isset($data['name']));
			if($valid) $valid = (isset($data['rate']));
			if($valid) $valid = (isset($data['fund_amount']));

			return($valid);
		}

		public function Restore($target, &$config)
		{
			$new_target = FALSE;

			if(BlackBox_Target_OldSchool::Valid_Data($target))
			{
				if(isset($this) && ($this instanceof BlackBox_Target_OldSchool))
				{
					$new_target = &$this;

					if($config)
					{
						$new_target->config = &$config;
					}
				}
				else
				{
					if($config)
					{
						$class_type = $target['class_type'];
						$new_target = new $class_type($config);

						if($new_target instanceof BlackBox_Preferred)
						{
							$new_target->Original_Tier($target['tier']);
						}
					}
				}

				if($new_target instanceof BlackBox_Target_OldSchool)
				{
					$new_target->rules = array();

					foreach($target['rules'] as $data)
					{
						$new_rule = BlackBox_Rule_OldSchool::Restore($data);

						if ($new_rule)
						{
							$new_target->rules[] = &$new_rule;
							unset($new_rule);
						}
					}

					$new_stats = BlackBox_Stats::Restore($target['stats'], $config, $target['target_id']);

					if($new_stats)
					{
						$new_target->stats = &$new_stats;
						unset($new_stats);
					}

					$new_target->tier = $target['tier'];
					$new_target->name = $target['name'];
					$new_target->target_id = $target['target_id'];
					$new_target->rate = $target['rate'];
					$new_target->vendor_qualify_post = $target['verify_post'];
					$new_target->verify_post_type = $target['verify_post_type'];
					$new_target->fund_amount = $target['fund_amount'];

					$new_target->Setup_Filters($target['filters']);
					
					if(!empty($target['targets']))
					{
						$new_target->targets = BlackBox_Target_Collection::Restore($target['targets'], $config);
	
						foreach($new_target->targets as $t)
						{
							$t->Set_DataX($new_target->datax);
							$t->Set_Parent($new_target);
						}
						
						// reset the open array
						$new_target->targets->Open($target['open'], TRUE);
					}
				}
			}

			return($new_target);
		}

		/**

			@desc Used when instantiating the target object:
				set local properties and builds the rules and
				stats based on the associative array, $row.

			@param $sql object MySQL connection
			@param $row array Associative array of target data,
				most likely from a database

			@return boolean Success

		*/
		public function From_Row(&$config, $row)
		{
			if(is_array($row))
			{
				// set our properties
				$this->name = $row['property_short'];
				$this->lead_amount = $row['lead_amount'];
				$this->overflow = $row['overflow'];
				
				$this->target_id = intval($row['target_id']);
				$this->parent_id = intval($row['parent_target_id']);
				$this->weight_type = $row['weight_type'];
				
				if($row['weight_type']=='AMOUNT')
				{
					$this->rate = $row['lead_amount'];
				}
				elseif($row['weight_type'] == 'PRIORITY')
				{
					$this->rate = $row['priority'];
				}
				else
				{
					$this->rate = $row['percentage'];
				}

				if(isset($row['datax_idv']))
				{
					// Must we pass DataX IDV?
					$this->datax_idv = ($row['datax_idv'] == 'TRUE');
				}

				if(isset($row['vendor_qualify_post']))
				{
					// Perform Vendor_Post verification [RL]
					$this->vendor_qualify_post = ($row['vendor_qualify_post'] == 'TRUE');
				}

				if(isset($row['verify_post_type']))
				{
					$this->verify_post_type = $row['verify_post_type'];
				}

				if(isset($row['tier_number']))
				{
					$this->tier = $row['tier_number'];
				}

				if(isset($row['target_id']))
				{
					$this->target_id = $row['target_id'];
				}
				
				if(isset($row['frequency_decline']))
				{
					if(strlen($row['frequency_decline'])>3)
					{
						$this->frequency_limits = unserialize($row['frequency_decline']);
					}
				}

				// import the rules
				$rules = $this->Rules_From_Row($row);
				if ($rules) $this->rules = &$rules;

				// import the stats
				$stats = $this->Stats_From_Row($config, $row);
				if ($stats) $this->stats = &$stats;

				$filters = (!empty($row['filters'])) ? $row['filters'] : array();
				$this->Setup_Filters($filters);

				if(!empty($row['withheld_targets']))
				{
					$this->withheld_targets =  array_map('trim', explode(',', $row['withheld_targets']));
				}
				//No one but CLK can run this, so just set to false here
				$this->run_fraud = false;
			}
		}


		public function Setup_Filters($filters)
		{
			$this->filters = new BlackBox_Filters($this->config, $this->tier);

			//First make sure we actually have filters
			if(!empty($filters))
			{
				//Then attempt to unserialize
				$filter_array = @unserialize($filters);

				//If it turns out we have an array (which we should)
				if(is_array($filter_array))
				{
					//We'll loop through it
					foreach($filter_array as $filter)
					{
						//Then make sure it's a valid filter name
						//Filters are set up in blackbox.config.php
						if(isset($this->config->filters[$filter]))
						{
							$this->filters->Add($this->config->filters[$filter]);
						}
					}
				}
			}
		}


		public function Has_Filters()
		{
			return $this->filters->Has_Filters();
		}


		public function Run_Filters($data)
		{
			$valid = NULL;

			if(isset($this->filters))
			{
				//Add in the app_id if we have it so we can exclude the current app
				if(isset($this->config->application_id))
				{
					$data['application_id'] = $this->config->application_id;
				}

				$valid = $this->filters->Run($data, $this->name);


				if(!$valid)
				{
					$this->failed = $this->filters->Failed();
				}


				$outcome = ($valid === FALSE) ? EVENT_FAIL : EVENT_PASS;
				$this->config->Log_Event(EVENT_FILTER_CHECK, $outcome, $this->name);
			}

			return $valid;
		}




		/**

			@desc Used when instantiating the target object:
				builds the stats object from $row.

			@param $row array Associative array of target data,
				most likely from a database

			@return None

		*/
		protected function Stats_From_Row(&$config, $row, $hourly_type = STAT_HOURLY_LEADS)
		{
			$stats = NULL;

			if(is_array($row))
			{
				// these always come from the
				// ongoing campaign
				$percent = $row['percentage'];
				$dd_ratio = $row['dd_ratio'];

				switch(strtolower($row['property_short']))
				{
					case 'd1':	$dd_ratio = (100 - 1); break;
					case 'ca':	$dd_ratio = (100 - 0.35); break;
					case 'pcl':	$dd_ratio = (100 - 0.6); break;
					case 'ufc': $dd_ratio = (100 - 1); break;
					case 'ucl': $dd_ratio = (100 - 0.5); break;
				}

				$max_dev = $row['max_deviation'];

				// This checks to sees if the campaign is using the new limit column.  If so it will replace
				// the old row value with a the new limit.
				if($row['daily_limit'])
				{
					$daily_limit_array = unserialize($row['daily_limit']);
			
					if(empty($daily_limit_array))
					{
						$daily_limit = $row['limit'];
					}
					else
					{			
						$day_index = date('N') - 1;
					
						// Check flag to see if we are using Detailed Daily Limits 
						// or the Default Limit	
						if($daily_limit_array[7] == '1')
						{
							// Use Detailed Daily Limits
							$daily_limit = $daily_limit_array[$day_index];
						}		
						else
						{
							// Use the Default Limit
							$daily_limit = $daily_limit_array[8];
						}
					}

					$row['limit'] = $daily_limit;
				}
				

				// get stats from the by_date
				// campaign, if there is one
				if(!is_null($row['cur_id']))
				{
					$daily_limit = $row['cur_limit'];
					$hourly_limits = $row['cur_hourly_limit'];
					$total_limit = $row['cur_total_limit'];
					$limit_mult = $row['cur_limit_mult'];
				}
				else
				{
					$daily_limit = $row['limit'];
					$hourly_limits = $row['hourly_limit'];
					$limit_mult = $row['limit_mult'];
					$total_limit = FALSE;
				}

				// unseralize this array
				if(!empty($hourly_limits))
				{
					$hourly_limits = unserialize($hourly_limits);
				}

				// create a new stat object
				$stats = new BlackBox_Stats($config, $row['property_short'], $row['target_id']);

				if(empty($hourly_limits))
				{
					// make sure that we are empty
					$hourly_limit = 0;

					// set up our standard stats
					if($limit_mult)
					{
						$daily_limit += round($daily_limit * $limit_mult);
					}

					$stats->Setup_Stat(STAT_DAILY_LEADS, $daily_limit);
				}
				else
				{
					// get the current hour
					$cur_hour = date('H');
					$hour_limit = 0;

					if(isset($hourly_limits[$cur_hour]))
					{
						// just to make sure
						ksort($hourly_limits);

						// our limit for this hour
						foreach($hourly_limits as $hour => $limit)
						{
							// convert to a float
							$limit = (float)$limit;

							// as a percentage of our daily limit?
							if($limit >= 0 && $limit < 1)
							{
								$limit = round($limit * $daily_limit);
							}

							$hour_limit += $limit;
							if($hour == $cur_hour) break;
						}

						// setup the stat
						if($limit_mult)
						{
							$hour_limit += round($hour_limit * $limit_mult);
						}

						$stats->Setup_Stat($hourly_type, $hour_limit);
					}
				}
				if($dd_ratio > 0)
				{
					$stats->Setup_Stat(STAT_DIRECT_DEPOSIT, $dd_ratio, $max_dev, STAT_DAILY_LEADS);
					$stats->Setup_Stat(STAT_NO_DIRECT_DEPOSIT, (100 - $dd_ratio), $max_dev, STAT_DAILY_LEADS);
				}

				if($total_limit)
				{
					$stats->Setup_Stat(STAT_TOTAL_LEADS, $total_limit);
				}

			}

			return (!$stats) ? FALSE : $stats;
		}

		/**

			@desc Used when instantiating the target object:
				builds the rules object from $row.

			@param $row array Associative array of target data,
				most likely from a database

			@return None

		*/
		protected function Rules_From_Row($row)
		{
			// Map rule columns to their respective rule type
			$type_map = array(
				'military' => 'Allow_Military',
				'weekends'=>'Allow_Weekends',
				'non_dates'=>'Not_Today',
				'bank_account_type'=>'In',
				'minimum_income'=>'More_Than_Equals',
				'income_direct_deposit'=>'Direct_Deposit',
				'excluded_states'=>'Not_In',
				'restricted_states'=>'In',
				'excluded_zips'=>'Not_In',
				'income_frequency'=>'In',
				'state_id_required'=>'Required',
				'state_issued_id_required' => 'Required',
				'minimum_recur_ssn'=>'Minimum_Recur',
				'minimum_recur_email' => 'Minimum_Recur',
				'dd_check' => 'Direct_Deposit_Recur',
				'minimum_recur_fle_dupe'=>'Minimum_Recur_FLE',
				'operating_hours'=>'Operating_Hours',
				'minimum_age'=>'Minimum_Age',
				'identical_phone_numbers'=>'Valid_Phone_Numbers',
				'identical_work_cell_numbers'=>'Valid_Phone_Numbers',
				'paydate_minimum'=>'Paydate_Minimum',
				'excluded_employers'=>'Not_In',
				'suppression_lists'=>'Suppression_Lists',
				'income_type' => 'Equals_No_Case',
				'required_references' => 'Reference_Count',
				//********************************************* 
				// GForge 6672 - New fields [AuMa]
				//********************************************* 
				'min_loan_amount_requested' => 'More_Than_Equals',
				'max_loan_amount_requested' => 'Less_Than_Equals',
				'residence_length' => 'Later_Than_Date_Months',
				'employer_length' => 'Later_Than_Date_Months',
				'residence_type' => 'Equals_No_Case',
				//********************************************* 
				// End GForge 6672
				//********************************************* 
			);

			// map rule columns to the data they examine
			$field_map = array(
				'military' => array('email_primary','military',false), // not nested
				'bank_account_type'=>'bank_account_type',
				'minimum_income'=>'income_monthly_net',
				'income_direct_deposit'=>'income_direct_deposit',
				'excluded_states'=>'home_state',
				'restricted_states'=>'home_state',
				'excluded_zips'=>'home_zip',
				'income_frequency'=>array('paydate_model', 'income_frequency'),
				'state_id_required'=>'state_id_number',
				'state_issued_id_required'=>'state_issued_id',
				'minimum_recur_ssn' => 'social_security_number',
				'minimum_recur_email' => 'email_primary',
				'dd_check' => 'social_security_number',
				'minimum_recur_fle_dupe' => 'email_primary',
				'minimum_age' => 'dob',
				'identical_phone_numbers'=>array('phone_home', 'phone_work', false), // not nested
				'identical_work_cell_numbers'=>array('phone_work', 'phone_cell', false), // not nested
				'paydate_minimum' => NULL,
				'excluded_employers'=>'employer_name',
				'income_type' => 'income_type',
				'required_references' => array('ref_01_name_full', 'ref_01_phone_home', 'ref_01_relationship', 'ref_02_name_full', 'ref_02_phone_home', 'ref_02_relationship', false), // not nested, see function Get_Data() in file blackbox.rules.php [DY]
				//********************************************* 
				// GForge 6672 - New Fields [AuMa]
				//********************************************* 
				'min_loan_amount_requested' => 'loan_amount_desired',
				'max_loan_amount_requested' => 'loan_amount_desired',
				'residence_length' => 'residence_start_date',
				'employer_length' => 'date_of_hire',
				'residence_type' => 'residence_type',
				//********************************************* 
				// End GForge 6672
				//********************************************* 
			);

			// map rule columns to the event they fire upon failure
			$event_map = array(
				'military' => 'ALLOW_MILITARY',
				'weekends'=>'WEEKEND',
				'non_dates'=>'NON_DATE',
				'bank_account_type'=>'ACCOUNT_TYPE',
				'minimum_income'=>'MIN_INCOME',
				'income_direct_deposit'=>'DIRECT_DEPOSIT',
				'excluded_states'=>'EXCL_STATES',
				'restricted_states'=>'RESTR_STATES',
				'excluded_zips'=>'EXCL_ZIPS',
				'income_frequency'=>'INCOME_FREQ',
				'state_id_required'=>'STATE_ID',
				'state_issued_id_required'=>'STATE_ISSUED_ID',
				'minimum_recur_ssn' => 'SSN_RECUR',
				'minimum_recur_email' => 'EMAIL_RECUR',
				'dd_check' => 'DIRECT_DEPOSIT_RECUR',
				'operating_hours'=>'OPERATING_HOURS',
				'minimum_age'=>'MINIMUM_AGE',
				'identical_phone_numbers'=>'ALLOW_IDENTICAL_PHONE_NUMBERS',
				'identical_work_cell_numbers'=>'ALLOW_IDENTICAL_WORK_CELL_NUMBERS',
				'paydate_minimum'=>'PAYDATE_MINIMUM', // never would be used coz it will always pass. Mantis #8769 [DY]
				'minimum_recur_fle_dupe' => 'FLE_RECUR',
				'income_type' => 'INCOME_TYPE',
				'datax_idv'=>EVENT_DATAX_IDV,
				'excluded_employers'=>'EXCL_EMPLOYERS',
				'suppression_lists'=>'SUPPRESS_LISTS',
				'vendor_qualify_post'=>'VERIFY_POST',
				'required_references' => 'REQ_REFS',
				
				//********************************************* 
				// GForge 6672 - New Fields [AuMa]
				//********************************************* 
				'min_loan_amount_requested' => 'MIN_LOAN_REQ',
				'max_loan_amount_requested' => 'MAX_LOAN_REQ',
				'residence_length' => 'RES_LENGTH',
				'employer_length' => 'EMP_LENGTH',
				'residence_type' => 'RES_TYPE',
				//********************************************* 
				// End GForge 6672
				//********************************************* 
			);

			// map columns which need sql
			$config_map = array(
				'minimum_recur_ssn',
				'minimum_recur_email',
				'minimum_recur_fle_dupe',
				'suppression_lists',
				'dd_check',
				'required_references',
				'paydate_minimum',
				'military',
			);

			// are we the FLE form
			if(isset($_SESSION['fle_dupe_id']))
			{
				// add the fle_dupe number
				$row['minimum_recur_fle_dupe'] = 30;
			}

			$rules = array();

			if(is_array($row))
			{
				foreach ($type_map as $col => $type)
				{
					// make sure it exists!
					if(isset($row[$col]))
					{
						$param = trim($row[$col]);

						// hack to check for serialized array
						if(substr($param, 0, 2) == 'a:')
						{
							$param = unserialize($param);
						}
						// check for a string TRUE | FALSE
						elseif($param === 'TRUE')
						{
							$param = TRUE;
						}
						elseif($param === 'FALSE')
						{
							$param = FALSE;
						}

						// an empty value (excluding FALSE) indicates
						// that this rule should not be applied
						if(!empty($param) || $param === FALSE)
						{
							// map this rule to it's type and field
							$type = (isset($type_map[$col])) ? $type_map[$col] : NULL;
							$field = (isset($field_map[$col])) ? $field_map[$col] : NULL;
							$event = (isset($event_map[$col])) ? $event_map[$col] : NULL;
							$config = in_array($col, $config_map);

							// add the rule object to our list
							if($type)
							{
								$rules[$col] = new BlackBox_Rule_OldSchool($type, $field, $param, $event, $config);
							}
						}
					}
				}
			}

			return $rules;
		}

		public function Get_Disallowed_States()
		{
			$query = "SELECT
							excluded_states
						FROM
							rules
						JOIN target 
						ON 
							(target.target_id = rules.target_id)
						WHERE
							rules.status='ACTIVE' AND
							target.status='ACTIVE' AND
							target.deleted='FALSE' AND
							property_short='".$this->Name()."'
						LIMIT 1";
			try
			{
				$result = $this->config->sql->Query($this->config->database, $query);
				if ($row = $this->config->sql->Fetch_Array_Row($result))
					$disallowed_states = unserialize($row['excluded_states']);
			}
			catch (Exception $e)
			{
				//unset($tiers);
				$disallowed_states = FALSE;
			}

			return $disallowed_states;
		}
		

		//This function is used in to check the list_mgmt_nosell column in the rules table to determine
		// if leads should or should not be remarketed.
		// A limit of 1 is set because there should only be 1 active rules row per campaign short.
		public function Get_Nosell()
		{
			$query = "SELECT
						list_mgmt_nosell
						FROM
							rules
						JOIN target
						ON
							(target.target_id=rules.target_id)
						WHERE
							rules.status='ACTIVE' AND
							target.status='ACTIVE' AND
							target.deleted='FALSE' AND
							property_short='".$this->Name()."'
						LIMIT 1";
			try
			{
				$result = $this->config->sql->Query($this->config->database, $query);
				$rows = $this->config->sql->Fetch_Array_Row($result);

				if($rows['list_mgmt_nosell'] == 'TRUE')
						$no_sell_flag = TRUE;
					else
						$no_sell_flag = FALSE;
			}
			catch (Exception $e)
			{
				$no_sell_flag = FALSE;
			}

			return $no_sell_flag;
		}

		public function Get_Limits()
		{
			$query = "SELECT
							`limit`,
							hourly_limit,
							daily_limit
						FROM
							campaign
						JOIN target
						ON
							(target.target_id = campaign.target_id)
						WHERE
							campaign.status='ACTIVE' AND
							target.status='ACTIVE' AND
							target.deleted='FALSE' AND
							property_short='".$this->Name()."'
						LIMIT 1";
			try
			{
				$result = $this->config->sql->Query($this->config->database, $query);
				if ($row = $this->config->sql->Fetch_Array_Row($result))
				{

					//This array should be 9 elements long. The first 7 elements are limits for
					//each day of the week. The 8th is a flag, and the 9th is the default limit.
					$daily_limit_array = unserialize($row['daily_limit']);

					if(empty($daily_limit_array))
					{
						$daily_limit = $row['limit'];
					}
					else
					{
						$day_index = date('N')-1;

						// Check flag to see if we are using Detailed Daily Limits
						// or the Default Limit
						if($daily_limit_array[7] == '1')
						{
							// Use Detailed Daily Limits
							$daily_limit = $daily_limit_array[$day_index];
						}
						else
						{
							// Use the Default Limit
							$daily_limit = $daily_limit_array[8];
						}
					}

					$hourly_limits = unserialize($row['hourly_limit']);
				}
			}
			catch (Exception $e)
			{
				//unset($tiers);
				$hourly_limits = FALSE;
			}

			return array('daily_limit' => $daily_limit, 'hourly_limits' => $hourly_limits);
		}

		public function Pick()
		{
			return $this;
		}

		public function Valid()
		{
			return empty($this->failed);
		}

		public function Validate($data, &$config = NULL)
		{
			$valid = TRUE;
			// Check to see if this Vendor support Posting Verification
			// and if so check to see if this Vendor even wants this lead
			//$process = TRUE;

			if ($this->VerifyPost())
			{
				Log_Vendor_Post::Insert_Dummy_Record(
					$this->config->sql,
					$this->config->database,
					$this->Name(),
					$this->config->application_id,
					Log_Vendor_Post::TYPE_VERIFY_POST);

				$vendor_post = new Vendor_Post($this->config->sql, $this->Name(), $_SESSION, $this->config->mode, $this->config->applog);
				$vp_impl = $vendor_post->Find_Post_Implementation($this->Name(), $this->config->mode, $_SESSION);
				$vp_result = $vp_impl->Verify($this->VerifyPostType());

				$valid = $vp_result->Is_Success();
				$this->config->Log_Event(EVENT_VERIFY_POST, ($valid) ? EVENT_PASS : EVENT_FAIL, $this->Name());

				Log_Vendor_Post::Log_Vendor_Post(
					$this->config->sql,
					$this->config->database,
					$this->Name(),
					$this->config->application_id,
					$vp_result,
					Log_Vendor_Post::TYPE_VERIFY_POST);
			}

			// null means we accept them either way,
			// so, in that case, we won't even run IDV
			if (!is_null($this->IDV()) && $valid)
			{
				if (($this->config->debug->Debug_Option(DEBUG_RUN_DATAX_IDV) !== FALSE))
				{
					$valid = $this->datax->Run(EVENT_DATAX_IDV, $this->Get_DataX_Account(), BlackBox_DataX::SOURCE_NONE, $this);
				}
				else
				{
					$this->config->Log_Event(EVENT_DATAX_IDV, EVENT_SKIP);
				}

			}


			// either way, can't use them again
			$this->collection->Open($this->Name(), FALSE);

			return $valid;
		}

		public function Set_Collection(&$collection)
		{
			if($collection instanceof BlackBox_Target_Collection)
			{
				$this->collection = &$collection;
			}
		}

		public function Get_Collection()
		{
			return $this->collection;
		}

		public function Set_DataX(&$datax)
		{
			if($datax instanceof BlackBox_DataX)
			{
				$this->datax = $datax;
				
				if($this->Has_Children())
				{
					foreach($this->targets as $target)
					{
						$target->Set_DataX($this->datax);
					}
				}
			}
		}


		public function Set_DataX_Account($account)
		{
			$this->datax_account = $account;
		}

		public function Get_DataX_Account()
		{
			return (is_null($this->datax_account)) ? 'PW' : $this->datax_account;
		}



		public function Set_Rate($property)
		{
			if(isset($this->{$property}))
			{
				$this->rate = $this->{$property};
			}
		}


		public function Get_Failed_Stat()
		{
			return (isset($this->stats)) ? $this->stats->Failed() : NULL;
		}


		public function Reset()
		{
			$this->fund_amount = NULL;
		}



		public function Get_Fund_Amount()
		{
			return $this->fund_amount;
		}


		public function Withheld_Targets()
		{
			return $this->withheld_targets;
		}
		
		
		public function Get_Target_List($in_use = true, $flat = true, $use_objects = false)
		{
			return ($use_objects) ? $this : $this->Name();
		}
		
		public function Set_Parent(&$parent)
		{
			$this->parent = &$parent;
		}
		
		public function Get_Parent()
		{
			return $this->parent;
		}
		

	}



	class BlackBox_Preferred extends BlackBox_Target_OldSchool
	{

		protected $orig_tier;

		public function __construct(&$config = NULL, &$target = NULL, $orig_tier = NULL)
		{
			parent::__construct($config, $target);
			$this->orig_tier = $orig_tier;
		}

		public function __destruct()
		{
			parent::__destruct();
		}

		public function Original_Tier($orig_tier = NULL)
		{
			if(is_numeric($orig_tier))
			{
				$this->orig_tier = $orig_tier;
			}

			return($this->orig_tier);
		}


	}
	
	
	
	class BlackBox_Target_CLK extends BlackBox_Preferred
	{
		public function __construct(&$config = NULL, &$target = NULL)
		{
			//Set up the overflow daily CLK limits!
			if(isset($target))
			{
				$daily_cap = 0;

				//Each day has a specific limit for all five companies combined
				switch(date('w'))
				{
					case 6:
					case 0: $daily_cap = 1094; break; //Saturday + Sunday
					case 1: $daily_cap = 3063; break; //Monday
					case 2: $daily_cap = 3500; break; //Tuesday
					case 3: $daily_cap = 3369; break; //Wednesday
					case 4: $daily_cap = 3038; break; //Thursday
					case 5: $daily_cap = 2588; break; //Friday
				}

				//Multiply it by their percentage to get our new fancy limit
				$target['limit'] = round($daily_cap * ($target['percentage']/100));
			}


			parent::__construct($config, $target, 1);
		}

		public function From_Row(&$config, $row)
		{
			parent::From_Row($config, $row);
			//Since CLK is the only one that can run fraud right now
			$this->run_fraud = (($row['run_fraud'] === true || strcasecmp($row['run_fraud'],'TRUE') == 0));
			$this->rate = $row['percentage'];
		}
		public function __destruct()
		{
			parent::__destruct();
		}


		public function Run_Rules(&$config, &$data)
		{
			return parent::Run_Rules($config, $data, 1);
		}


		public function Get_DataX_Account()
		{
			return $this->Name();
		}


		public function Validate($data, &$config = NULL)
		{
			$validation = new BlackBox_Validation($this->config);

			// try to qualify us
			$fund_amount = $validation->Qualify($this->Name());

			$valid = $validation->Valid();
			if($valid && $fund_amount !== FALSE)
			{
				$valid = TRUE;
				$this->fund_amount = $fund_amount;
			}

			if($valid)
			{
				// we don't just skip DataX in LOCAL/RC mode because we're
				// actually going to make a real request, but force it to pass
				if(($this->config->debug->Debug_Option(DEBUG_RUN_DATAX_PERF) !== FALSE))
				{
					$valid = $this->datax->Run(EVENT_DATAX_PERF, $this->Get_DataX_Account(), BlackBox_DataX::SOURCE_CLK, $this);
					
					if (!$valid && $this->config->bb_mode == MODE_DEFAULT)
					{
						// Hit the adverse action stats
						Stats::Hit_Stats(
							array(
								'aa_denial_teletrack',
								'aa_mail_teletrack_' . strtolower($this->Name())
							),
							$this->config->session,
							$this->config->log,
							$this->config->applog,
							$this->config->application_id
						);
					}
				}
				else
				{
					$this->config->Log_Event(EVENT_DATAX_PERF, EVENT_SKIP);
				}

				if(!$valid)
				{
					// for tier 1 only, close all targets
					// if we fail either IDV or Performance
					// close all the targets
					$this->collection->Open(NULL, FALSE);
				}
			}

			// run our validation rules
			if($valid && !in_array($this->config->bb_mode, array(MODE_PREQUAL, MODE_ECASH_REACT)))
			{
				$verify = new BlackBox_Verify_Rules_CLK();
				$verify->Run($this->config, $this, $this->config->data);
			}

			// either way, can't use them again
			$this->collection->Open($this->Name(), FALSE);
			return $valid;
		}


		protected function Stats_From_Row(&$config, $row)
		{
			return parent::Stats_From_Row($config, $row, STAT_OVERFLOW_LEADS);
		}


		public function Stats($stat_name)
		{
			if(is_string($stat_name) && $stat_name == STAT_DAILY_LEADS)
			{
				$stats = $this->stats->Stat($stat_name, TRUE);
			}
			else
			{
				$stats = parent::Stats($stat_name);
			}

			return $stats;
		}

	}
	
	/**
	 * Extended Blackbox_Target class for UFC.
	 *
	 * @author Matthew Jump <matthew.jump@sellingsource.com>
	 * @author Brian Feaver <brian.feaver@sellingsource.com>
	 */
	class Blackbox_Target_UFC extends BlackBox_Target_CLK 
	{
		/**
		 * Validate UFC
		 *
		 * @param array $data
		 * @param BlackBox_Config $config
		 * @return bool valid
		 */
		public function Validate($data, &$config = NULL)
		{
			$validation = new BlackBox_Validation($this->config);

			// try to qualify us
			$fund_amount = $validation->Qualify($this->Name());

			$valid = $validation->Valid();
			if($valid && $fund_amount !== FALSE)
			{
				$valid = TRUE;
				$this->fund_amount = $fund_amount;
			}
			if($valid)
			{
				// we don't just skip DataX in LOCAL/RC mode because we're
				// actually going to make a real request, but force it to pass
				if(($this->config->debug->Debug_Option(DEBUG_RUN_DATAX_PERF) !== FALSE))
				{
					$valid = $this->datax->Run(EVENT_DATAX_PERF, $this->Get_DataX_Account(), BlackBox_DataX::SOURCE_CLK, $this);
					
					if (!$valid && $this->config->bb_mode == MODE_DEFAULT)
					{
						// Hit the adverse action stats
						Stats::Hit_Stats(
							array(
								'aa_denial_teletrack',
								'aa_mail_teletrack_' . strtolower($this->Name())
							),
							$this->config->session,
							$this->config->log,
							$this->config->applog,
							$this->config->application_id
						);
					}
				}
				else
				{
					$this->config->Log_Event(EVENT_DATAX_PERF, EVENT_SKIP);
				}
				
				/**
				 * If we are still valid, check for to see if UFC had a VERIFY result from the
				 * suppression lists.
				 */
				if ($valid && isset($_SESSION['UFC_SUPPRESSION_VERIFY']))
				{
					$this->config->Log_Event('UFC_SUPPRESSION_VERIFY', EVENT_FAIL, $this->Name());
					$valid = FALSE;
				}
				
				if(!$valid)
				{
					// for tier 1 only, close all targets
					// if we fail either IDV or Performance
					// close all the targets
					$this->collection->Open(NULL, FALSE);
				}
			}
			// run our validation rules
			if($valid && !in_array($this->config->bb_mode, array(MODE_PREQUAL, MODE_ECASH_REACT)))
			{
				$verify = new BlackBox_Verify_Rules_UFC();
				$valid = $verify->Run($this->config, $this, $this->config->data);
				
				// Verify rules that match, fail UFC
				if (!$valid)
				{
					$this->collection->Open(NULL, FALSE);
				}
			}

			// either way, can't use them again
			$this->collection->Open($this->Name(), FALSE);
			
			return $valid;
		}
	}
	
	
	class BlackBox_Target_Impact extends BlackBox_Preferred
	{
		public function __construct(&$config = NULL, &$target = NULL, $orig_tier = NULL)
		{
			//check if we are ecash react and set mode if we are
			if($_SESSION['config']->site_name == 'ecashapp.com' && $_SESSION['config']->ecash_react)
			{
				$config->bb_mode = MODE_ECASH_REACT;
			}

			$acm = new App_Campaign_Manager($config->sql, $config->database, $config->applog);
			$olp_process = $acm->Get_Olp_Process($config->application_id);


			//Don't want to run filters for Impact
			$target['filters'] = array();
			//adjust excluded states for time zone info
			$start_time = mktime(6,0,0);
			$stop_time = mktime(8,0,0);
			$now_time = time();
			if(($start_time < $now_time) && ($stop_time > $now_time)
				&& $config->bb_mode !== MODE_ECASH_REACT
				&& !preg_match('/_react$/', $olp_process))
			{
				$old_excluded_states = unserialize($target['excluded_states']);
				if($old_excluded_states === FALSE)
				{
					$old_excluded_states = array();
				}
				$new_excluded_states = array('WA','MT','ID','CA','NV','OR','WY','HI','AK','NM','AZ','CO','UT');

				$target['excluded_states'] = serialize(array_merge($old_excluded_states,$new_excluded_states));
			}
			parent::__construct($config, $target, $orig_tier);
		}

		public function __destruct()
		{
			parent::__destruct();
		}

		public function Set_DataX(&$datax, $config = null)
		{
			if($datax instanceof BlackBox_DataX_Impact)
			{
				$this->datax = $datax;
			}
			else
			{
				$this->datax = new BlackBox_DataX_Impact($config);
			}
		}

		public function Get_DataX_Account()
		{
			 return Enterprise_Data::resolveAlias($this->Name());
		}
		
		public function Validate($data, &$config = NULL, $bypass_used_info = FALSE)
		{
			$validation = new BlackBox_Validation($this->config);
			$this->Run_Cashline($validation);
			
			if($this->config->bb_mode !== MODE_ONLINE_CONFIRMATION)
			{
				$validation->Validate_Used_Info($this->collection, $bypass_used_info, $this->Name());

				$target = Enterprise_Data::resolveAlias($this->Name());
				switch(strtolower($target))
				{
					// Change IC to use impact-idve - GForge 5576 [DW]
					case "ic":
						$eventName = EVENT_DATAX_IC_IDVE;
						$sourceId = BlackBox_DataX_Impact::SOURCE_IMP;
						break;
					case "ifs":
						$eventName = EVENT_DATAX_IFS_IDVE;
						$sourceId = BlackBox_DataX_Impact::SOURCE_IFS;
						break;
					case "icf":
						$eventName = EVENT_DATAX_ICF_IDVE;
						$sourceId = BlackBox_DataX_Impact::SOURCE_ICF;
						break;
					case "ipdl":
						$eventName = EVENT_DATAX_IPDL_IDVE;
						$sourceId = BlackBox_DataX_Impact::SOURCE_IPDL;
						break;
				}
					
				$datax_valid = $validation->Validate_DataX($this->datax, $eventName, $this->Get_DataX_Account(), $sourceId);

				if(!$datax_valid)
				{
					if($this->config->Is_Impact())
					{
						$this->collection->Open(NULL, FALSE);
					}

					$aa_denial = NULL;

					switch($this->datax->Get_DataX_Type($eventName, $sourceId))
					{
						case BlackBox_DataX::TYPE_PDX_REWORK:
						case BlackBox_DataX::TYPE_IDVE_IMPACT: // Change IC to use impact-idve - GForge 5576 [DW]
							$aa_denial = 'aa_denial_datax_impact';
							break;
						case BlackBox_DataX::TYPE_IDVE_IFS:
							$aa_denial = 'aa_denial_datax_ifs';
							break;
						case BlackBox_DataX::TYPE_IDVE_IPDL:
							$aa_denial = 'aa_denial_datax_ipdl';
							break;
						case BlackBox_DataX::TYPE_IDVE_ICF:
							$aa_denial = 'aa_denial_datax_icf';
							break;
					}

					if(!is_null($aa_denial) && !isset($_SESSION['adverse_action']))
					{
						$_SESSION['adverse_action'] = $aa_denial;
					}
				}
			}

			$fund_amount = $validation->Qualify($this->Name());
			// try to qualify us

			$valid = $validation->Valid();
			if($valid && $fund_amount !== FALSE)
			{
				$this->fund_amount = $fund_amount;
			}



			// run our validation rules
			if($valid && !in_array($this->config->bb_mode, array(MODE_PREQUAL, MODE_ECASH_REACT)))
			{
				$verify = new BlackBox_Verify_Rules_Impact(); // minor changes in the default verify gforge #4703
				$verify->Run($this->config, $this, $this->config->data);
			}



			//If we're in prequal, we only want to check Impact, so close everything after we're done.
			if($this->config->bb_mode == MODE_PREQUAL)
			{
				$this->collection->Open(NULL, FALSE);
			}
			else
			{
				// either way, can't use them again
				$this->collection->Open($this->Name(), FALSE);
			}
			
			if(!$valid && empty($_SESSION['adverse_action']))
			{
				$_SESSION['adverse_action'] = 'aa_denial_impact';
			}
			
			return $valid;
		}

		public function Run_Cashline(&$validation = NULL)
		{
			if(is_null($validation))
			{
				$validation = new BlackBox_Validation($this->config);
			}

			$validation->Validate_Cashline($this->Name(), $this->collection);

			return $validation->Valid();
		}

		/**
		 * Overriding Check_Stats so that we can bypass the hourly limit check if
		 * the application is a react.
		 */
		public function Check_Stats(&$blackbox, $stat_names = NULL, $simulate = FALSE)
		{
			$valid = true;
			
			if($this->config->bb_mode !== MODE_DEFAULT || $this->config->Bypass_Limits($this->Name()))
			{
				$this->failed = false;
			}
			elseif($this->config->bb_mode == MODE_DEFAULT)
			{
				$valid = parent::Check_Stats($blackbox, $stat_names, $simulate);
			}

			return $valid;
		}
		
		/**
		*	Don't run operating hours for Impact during online conf.
		*/
		protected function Rules_From_Row($row)
		{
			$rules = parent::Rules_From_Row($row);
			
			//Bypass operating hours and weekend checks
			if($this->config->bb_mode !== MODE_DEFAULT || $this->config->Bypass_Limits($this->Name()))
			{
				unset($rules['operating_hours'], $rules['weekends']);
			}
			
			return $rules;
		}

	}
	
	
	
	
	class BlackBox_Target_Agean extends BlackBox_Preferred
	{
		public function __destruct()
		{
			parent::__destruct();
		}
		
		public function Set_DataX()
		{
			$this->datax = new BlackBox_DataX_Agean($this->config);
		}
		
		public function Get_DataX_Account()
		{
			return Enterprise_Data::resolveAlias($this->Name());
		}
		
		protected function Rules_From_Row($row)
		{
			if($this->config->title_loan)
			{
				//Don't sell title loans to Alaska because I guess they don't need cars
				$excluded_states = unserialize($row['excluded_states']);
				$excluded_states[] = 'AK';
				$row['excluded_states'] = serialize($excluded_states);
			}
			
			$rules = parent::Rules_From_Row($row);
			
			if($this->config->title_loan)
			{
				//Need to make these 'official' rules at some point, but since they're Agean and title-loan
				//specific right now, we just don't have the time to bother with the WA2 crap.
				$rules['vehicle_mileage'] = new BlackBox_Rule_OldSchool('Less_Than', 'vehicle_mileage', 150000, 'VEHICLE_MILEAGE');
				$rules['vehicle_year'] = new BlackBox_Rule_OldSchool('More_Than_Equals', 'vehicle_year', 1998, 'VEHICLE_YEAR');
			}
			
			//Bypass operating hours and weekend checks
			if($this->config->bb_mode !== MODE_DEFAULT || $this->config->Bypass_Limits($this->Name()))
			{
				unset($rules['operating_hours'], $rules['weekends']);
			}
			
			return $rules;
		}
		
		public function Validate($data, &$config = NULL, $bypass_used_info = FALSE)
		{
			//Check to see if this ssn bypasses validation.
			// Run validation always if it is a react
			if($_SESSION['no_checks_ssn'] && $this->config->bb_mode != MODE_ECASH_REACT)
			{
				return TRUE;
			}
			
			$validation = new BlackBox_Validation_Agean($this->config, $this->tier);
			$validation->Validate_Cashline($this->Name(), $this->collection);
			
			if($this->config->bb_mode !== MODE_ONLINE_CONFIRMATION)
			{
				$validation->Validate_Used_Info($this->collection, $bypass_used_info, $this->Name());
				
				try
				{
					$verified = $this->Verify_React($validation->Valid());
				}
				catch(Exception $e)
				{
					//Ignore any errors.
					$this->config->Applog_Write('[BlackBox_Target_Agean] Error verifying react data: ' . $e->getMessage());
				}
					
				if($validation->Valid() && ($this->config->bb_mode === MODE_DEFAULT || !$verified))
				{
					$call_type = ($this->config->title_loan)
								? BlackBox_DataX_Agean::EVENT_AGEAN_TITLE
								: BlackBox_DataX_Agean::EVENT_AGEAN_PERF;
					
					$datax_valid = $validation->Validate_DataX(
						$this->datax,
						$call_type,
						$this->Get_DataX_Account(),
						BlackBox_DataX_Agean::SOURCE_AGEAN
					);
	
					if(!$datax_valid)
					{
						if($this->datax->Is_Adverse_Action())
						{
							$acm = new App_Campaign_Manager($this->config->sql, $this->config->database, $this->config->applog);
							$acm->Update_Denied_Target($this->config->application_id, $this->target_id);
						}
						
						if($this->config->Is_Agean())
						{
							$this->collection->Open(NULL, FALSE);
						}
					}
				}
			}
			
			
			
			$fund_amount = $validation->Qualify($this->Name());
			// try to qualify us
			
			$valid = $validation->Valid();
			if($valid && $fund_amount !== FALSE)
			{
				$this->fund_amount = $fund_amount;
			}
			
			// run our validation rules
			if($valid && !in_array($this->config->bb_mode, array(MODE_PREQUAL, MODE_ECASH_REACT)))
			{
				$verify = new BlackBox_Verify_Rules_Agean();
				$verify->Run($this->config, $this, $this->config->data);
			}


			// either way, can't use them again
			$this->collection->Open($this->Name(), FALSE);
			
			return $valid;
		}
		
		protected function Verify_React($valid)
		{
			$verified = true;

			if($valid && $this->config->bb_mode === MODE_ECASH_REACT && !empty($this->config->data['react_app_id']))
			{
				require_once(ECASH_COMMON_DIR . 'ecash_api/ecash_api.2.php');
				require_once(OLP_DIR . 'ent_cs.mysqli.php');
				
				$sql = Setup_DB::Get_Instance('mysql', BFW_MODE, $this->Name());
				$ecash_api = OLPECashHandler::getECashAPI($this->Name(), $this->config->data['react_app_id'], BFW_MODE);
				
				$date = $ecash_api->Get_Status_Date('paid', 'paid::customer::*root');

				$date_valid = (empty($date) || strtotime('+45 days', strtotime($date)) >= time()); 

				$old_process = $_SESSION['config']->use_new_process;
				$_SESSION['config']->use_new_process = false;
				$user_data = Ent_CS_MySQLi::Get_The_Kitchen_Sink($sql, null, $this->config->data['react_app_id']);
				$_SESSION['config']->use_new_process = $old_process;

				$bank_info_valid = ($user_data['bank_aba'] == $this->config->data['bank_aba']
									&& ltrim($user_data['bank_account'], '0') == ltrim($this->config->data['bank_account'], '0'));
				
				//If a react has been paid for more than 45 days,
				//or if the ABA or Account number have changed,
				//we'll need to re-run DataX
				if(!($date_valid && $bank_info_valid))
				{
					$opts = array(
						DEBUG_RUN_DATAX_IDV,
						DEBUG_RUN_DATAX_PERF,
					);

					foreach($opts as $opt)
					{
						$this->config->debug->Debug_Option($opt, TRUE);
					}
					
					$this->config->debug->Save_Snapshot('debug_opt', $this->config->debug->Get_Options());
					
					$verified = false;
				}
			}
			
			return $verified;
		}

		public function Check_Stats(&$config, $stat_names = NULL, $simulate = FALSE)
		{
			$valid = true;
			
			if(($_SESSION['no_checks_ssn'] && $this->config->bb_mode != MODE_ECASH_REACT) 
				|| $this->config->bb_mode !== MODE_DEFAULT 
				|| $this->config->Bypass_Limits($this->Name()))
			{
				$this->failed = false;
			}
			elseif($this->config->bb_mode == MODE_DEFAULT)
			{
				$valid = parent::Check_Stats($config, $stat_names, $simulate);
			}
			
			return $valid;
		}
		
		public function Run_Rules(&$blackbox, &$data, $tier = NULL)
		{
			$valid = true;
			// We always want to attempt to run what rules we need to on reacts
			if(!$_SESSION['no_checks_ssn'] || $this->config->bb_mode == MODE_ECASH_REACT)
			{
				$valid = parent::Run_Rules($blackbox, $data, $tier);
			}
			return $valid;
		}
	}
	
	
	class BlackBox_Target_CCRT extends BlackBox_Preferred
	{
		public function __destruct()
		{
			parent::__destruct();
		}
		

		public function Get_DataX_Account()
		{
			 return 'CCRT';
		}
		

		public function Validate($data, &$config = NULL, $bypass_used_info = FALSE)
		{
			$valid = parent::Validate($data, $config, $bypass_used_info);
			
			if($valid && $this->config->bb_mode !== MODE_PREQUAL)
			{
				$validation = new BlackBox_Validation($this->config);
				
				$datax_valid = $validation->Validate_DataX($this->datax, 'DATAX_CCRT', $this->Get_DataX_Account(), BlackBox_DataX::SOURCE_CCRT);
				if($datax_valid === false)
				{
					$this->collection->Open($this->Name(), false);
				}
				
				$valid = $validation->Valid();
			}
			
			$this->collection->Open($this->Name(), FALSE);
			
			return $valid;
		}
	}
	
	/**
	 * Generic eCash customer ('ENTerprise GENeric') loosely based on BlackBox_Target_Impact
	 * 
	 */
	class BlackBox_Target_Entgen extends BlackBox_Preferred
	{
		public function __construct(&$config = NULL, &$target = NULL, $orig_tier = NULL)
		{
			//check if we are ecash react and set mode if we are
			
			//if($_SESSION['config']->site_name == 'ecashapp.com' && $_SESSION['config']->ecash_react)
			if(SiteConfig::getInstance()->site_name == 'ecashapp.com' && SiteConfig::getInstance()->ecash_react)
			{
				$config->bb_mode = MODE_ECASH_REACT;
			}
						
			parent::__construct($config, $target, $orig_tier);
		}

		public function __destruct()
		{
			parent::__destruct();
		}


		public function Get_DataX_Account()
		{
			 return 'generic';
		}
		
		public function Validate($data, &$config = NULL, $bypass_used_info = FALSE)
		{
			$validation = new BlackBox_Validation_Agean($this->config);
			$this->Run_Cashline($validation);
			
			if($this->config->bb_mode !== MODE_ONLINE_CONFIRMATION)
			{
				$validation->Validate_Used_Info($this->collection, $bypass_used_info, $this->Name());
				$datax_valid = $validation->Validate_DataX($this->datax, EVENT_DATAX_AALM, $this->Get_DataX_Account(), BlackBox_DataX::SOURCE_MLS);

				if(!$datax_valid)
				{
					if($this->config->Is_Entgen())
					{
						$this->collection->Open(NULL, FALSE);
					}

					$aa_denial = NULL;

					switch($this->datax->Get_DataX_Type(EVENT_DATAX_AALM, BlackBox_DataX::SOURCE_MLS))
					{
						case BlackBox_DataX::TYPE_PERF_MLS:
							$aa_denial = 'aa_denial_datax_entgen';
							break;
					}

					if(!is_null($aa_denial) && !isset($_SESSION['adverse_action']))
					{
						$_SESSION['adverse_action'] = $aa_denial;
					}
				}
			}

			$fund_amount = $validation->Qualify($this->Name());
			// try to qualify us

			$valid = $validation->Valid();
			if($valid && $fund_amount !== FALSE)
			{
				$this->fund_amount = $fund_amount;
			}

			// run our validation rules
			if($valid && !in_array($this->config->bb_mode, array(MODE_PREQUAL, MODE_ECASH_REACT)))
			{
				$verify = new BlackBox_Verify_Rules();
				$verify->Run($this->config, $this, $this->config->data);
			}


			//If we're in prequal, we only want to check Impact, so close everything after we're done.
			if($this->config->bb_mode == MODE_PREQUAL)
			{
				$this->collection->Open(NULL, FALSE);
			}
			else
			{
				// either way, can't use them again
				$this->collection->Open($this->Name(), FALSE);
			}
			
			if(!$valid && empty($_SESSION['adverse_action']))
			{
				$_SESSION['adverse_action'] = 'aa_denial_entgen';
			}
			
			return $valid;
		}

		public function Run_Cashline(&$validation = NULL)
		{
			if(is_null($validation))
			{
				$validation = new BlackBox_Validation($this->config);
			}

			$validation->Validate_Cashline($this->Name(), $this->collection);

			return $validation->Valid();
		}

		protected function Rules_From_Row($row)
		{
			$rules = parent::Rules_From_Row($row);
			
			//Bypass operating hours and weekend checks
			if($this->config->bb_mode !== MODE_DEFAULT || $this->config->Bypass_Limits($this->Name()))
			{
				unset($rules['operating_hours'], $rules['weekends']);
			}
			
			return $rules;
		}
		
		/**
		 * Overriding Check_Stats so that we can bypass the hourly limit check if
		 * the application is a react.
		 */
		public function Check_Stats(&$config, $stat_names = NULL, $simulate = FALSE)
		{
			$valid = true;
			
			if($this->config->bb_mode !== MODE_DEFAULT || $this->config->Bypass_Limits($this->Name()))
			{
				$this->failed = false;
			}
			elseif($this->config->bb_mode == MODE_DEFAULT)
			{
				$valid = parent::Check_Stats($config, $stat_names, $simulate);
			}
			
			return $valid;
		}

	}

	/**
	 * This class handles the UK blackbox targets
	 *
	 * @author August Malson <august.malson@sellingsource.com>
	 */
	class BlackBox_Target_UK extends BlackBox_Preferred
	{
		public function __destruct()
		{
			parent::__destruct();
		}

	
        /**
         * This function creates the blackbox rules to run
		 * for the vendor.
		 *
		 * @param array row
		 * @return array Associated array of rules 
         */
        protected function Rules_From_Row($row)
        {   
            $rules = parent::Rules_From_Row($row);
			
			$prop_short = strtolower($this->Name());

            if ($prop_short == 'bi_uk')
            {   
                // need to make these 'official' rules at some point
                // but since they are UK loans, we're not going to 
                // mess with that right now.
                $rules['nin'] = new BlackBox_Rule_OldSchool('Required', 'nin', TRUE, 'NIN');
            }
			elseif ($prop_short == 'cg_uk' || $prop_short == 'cg_uk2')
			{	
				// updated for GForge 6278 [AuMa]
				if($prop_short == 'cg_uk')
				{
					// rules for only cg_uk
					$rules['sort_code'] = new BlackBox_Rule_OldSchool('Required', 'bank_aba', TRUE, 'BANK_ABA');
					$rules['bank_account_number'] = new BlackBox_Rule_OldSchool('Required', 'bank_account', TRUE, 'BANK_ACCOUNT');
				}
				// same as above - need to make these rules 'official'
				$rules['home_type'] = new BlackBox_Rule_OldSchool('Required', 'residence_type', TRUE, 'RESIDENCE_TYPE');
				// second phase additions: required references, employer phone, best call time
				// Required references are done in the webadmin2 area
				// everything else is handled here
				$rules['employer_phone'] = new BlackBox_Rule_OldSchool('Required','bank_aba', TRUE,'EMPLOYER_PHONE');
				$rules['best_call_time'] = new BlackBox_Rule_OldSchool(
					'In',
					'best_call_time', 
					 array('ANY','MORNING','EVENING','AFTERNOON'),
					 'BEST_CALL_TIME'
				);
			}
		    elseif ($prop_short == 'mem_uk')
			{
				// no required rules yet
                $rules['nin'] = new BlackBox_Rule_OldSchool('Required', 'nin', TRUE, 'NIN');
			}

            return $rules;
        }
	}
	
	
?>
