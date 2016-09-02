<?php
/**
 * Factory for creating OLP Blackbox rules.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_Rule
{
	/**
	 * Keep track of simple rules we've instantiated.
	 *
	 * @var array of Blackbox_IRules
	 */
	public $references = array();
	
	/**
	 * Array of OLPBlackbox_Factory_Legacy_Rule instances.
	 *
	 * @var array
	 */
	protected static $rule_instances = array();
	
	/**
	 * Returns an instance of OLPBlackbox_Factory_Legacy_Rule.
	 *
	 * @param string $property_short the property short of the target we're getting rules for
	 * @return OLPBlackbox_Factory_Legacy_Rule
	 */
	public static function getInstance($property_short = 'default')
	{
		/**
		 * Originally, we were passing in the company name, but it seems a little more intuitive to pass
		 * in the property short and then do the conversion here.
		 */
		$company_name = $property_short;
		if (strcasecmp($company_name, 'default') != 0)
		{
			$company_name = EnterpriseData::getCompany($property_short);
		}
		
		if (!isset(self::$rule_instances[$company_name]))
		{
			switch ($company_name)
			{
				case EnterpriseData::COMPANY_CLK:
					self::$rule_instances[$company_name] = new OLPBlackbox_Enterprise_CLK_Factory_Legacy_Rule();
					break;
				case EnterpriseData::COMPANY_IMPACT:
					self::$rule_instances[$company_name] = new OLPBlackbox_Enterprise_Impact_Factory_Legacy_Rule();
					break;
				case EnterpriseData::COMPANY_AGEAN:
					self::$rule_instances[$company_name] = new OLPBlackbox_Enterprise_Agean_Factory_Legacy_Rule();
					break;
				default:
					self::$rule_instances[$company_name] = new OLPBlackbox_Factory_Legacy_Rule();
					break;
			}
		}
		
		return self::$rule_instances[$company_name];
	}
	
	/**
	 * Returns an OLPBlackbox_Rule.
	 *
	 * @param string $rule_name
	 * @param string $rule_value
	 * @return OLPBlackbox_Rule
	 */
	public function getRule($rule_name, $rule_value)
	{
		// before we "transform" the rule_value, make up a hash to
		// uniquely identify the rule_name/rule_value for IReusableRules later
		$rule_hash = md5($rule_name.$rule_value);
		
		// short circuit and see if we already have instantiated an IReusableRule
		// that we can use here.
		if (array_key_exists($rule_hash, $this->references)
			&& $this->references[$rule_hash] instanceof Blackbox_IRule)
		{
			return $this->references[$rule_hash];
		}
		
		// hack to check for serialized array
		if (substr($rule_value, 0, 2) == 'a:')
		{
			$rule_value = unserialize($rule_value);
		}
		// check for a string TRUE | FALSE
		elseif ($rule_value === 'TRUE')
		{
			$rule_value = TRUE;
		}
		elseif ($rule_value === 'FALSE')
		{
			$rule_value = FALSE;
		}

		// If we dont have a value for this rule, lets skip it.
		if (empty($rule_value) && $rule_value !== FALSE)
		{
			return FALSE;
		}

		// Map the rule from the db to the appropriate [OLP]BBx rule.
		$field = NULL;
		$rule = NULL;
		$event = NULL;
		$stat = NULL;
		switch ($rule_name)
		{
			case 'weekends':
				if ($rule_value === FALSE)
				{
					$rule_value = array('sat','sun');
					$field = "";
					$rule = "ExcludeDayOfWeek"; // Originally Allow_Weekends
					$event = 'WEEKEND';
					$stat = 'weekend';
				}
				break;

			case 'non_dates':
				$field = "";
				$rule = "DateNotIn"; // Originally Not_Today
				$event = 'NON_DATE';
				$stat = 'non_date';
				break;

			case 'bank_account_type':
				$field = 'bank_account_type';
				// Changed to EqualsNoCase because doesn't need to be case sensitive - GForge #10438 [DW]
				$rule = 'EqualsNoCase'; // Used to be "In" but comes out of the db a string not an array
				$event = 'ACCOUNT_TYPE';
				$stat = 'account_type';
				break;

			case 'minimum_income':
				$field = 'income_monthly_net';
				$rule = 'GreaterThanEquals';
				$event = 'MIN_INCOME';
				$stat = 'min_income';
				break;

			case 'income_direct_deposit':
				$field = 'income_direct_deposit';
				$rule = 'Identical'; // Used to be "Direct_Deposit", but it was nothing more than a === check.
				$event = 'DIRECT_DEPOSIT';
				$stat = 'direct_deposit';
				$rule_value = ($rule_value) ? 'TRUE' : 'FALSE'; // This one DOES actually check the string value
				break;

			case 'excluded_states':
				$field = 'home_state';
				$rule = 'NotIn';
				$event = 'EXCL_STATES';
				$stat = 'excl_states';
				break;

			case 'restricted_states':
				$field = 'home_state';
				$rule = 'In';
				$event = 'RESTR_STATES';
				$stat = 'restr_states';
				break;

			case 'income_frequency':
				$field = 'income_frequency';
				$rule = 'In';
				$event = 'INCOME_FREQ';
				$stat = 'income_freq';
				break;

			case 'state_id_required':
				if ($rule_value)
				{
					$field = 'state_id_number';
					$rule = 'Required';
					$event = 'STATE_ID';
					$stat = 'state_id';
				}
				break;

			case 'state_issued_id_required':
				if ($rule_value)
				{
					$field = 'state_issued_id';
					$rule = 'Required';
					$event = 'STATE_ISSUED_ID';
					$stat = 'state_issued_id';
				}
				break;

			case 'minimum_recur_ssn':
				$field = 'social_security_number_encrypted';
				$rule = 'MinimumRecur_SSN'; // Current: Minimum_Recur
				$event = 'SSN_RECUR';
				$stat = 'ssn_recur';
				break;

			case 'minimum_recur_email':
				$field = 'email_primary';
				$rule = 'MinimumRecur_Email'; // Current: Minimum_Recur
				$event = 'EMAIL_RECUR';
				$stat = 'email_recur';
				break;

			case 'income_type':
				$field = 'income_type';
				$rule = 'EqualsNoCase';
				$event = 'INCOME_TYPE';
				$stat = 'income_type';
				break;

			case 'min_loan_amount_requested':
				$field = 'loan_amount_desired';
				$rule = 'GreaterThanEquals';
				$event = 'MIN_LOAN_REQ';
				$stat = 'min_loan_req';
				break;

			case 'max_loan_amount_requested':
				$field = 'loan_amount_desired';
				$rule = 'LessThanEquals';
				$event = 'MAX_LOAN_REQ';
				$stat = 'max_loan_req';
				break;

			case 'residence_length':
				$field = 'residence_start_date';
				$rule = 'DateMonthsBefore';
				$event = 'RES_LENGTH';
				$stat = 'res_length';
				break;

			case 'employer_length':
				$field = 'date_of_hire';
				$rule = 'DateMonthsBefore';
				$event = 'EMP_LENGTH';
				$stat = 'emp_length';
				break;

			case 'residence_type':
				$field = 'residence_type';
				$rule = 'EqualsNoCase';
				$event = 'RES_TYPE';
				$stat = 'res_type';
				break;

			case 'excluded_zips':
				$field = 'home_zip';
				$rule = 'NotIn';
				$event = 'EXCL_ZIPS';
				$stat = 'excl_zips';
				break;

			case 'suppression_lists':
				$list_factory = $this->getSuppressionListFactory();
				$rule = $list_factory->getSuppressionLists($rule_value);
				break;

			case 'operating_hours':
				// event name
				$event = 'OPERATING_HOURS';
				$stat = 'operating_hours';

				/* Operating hours currently works out of an array where keys:
				 * 0 => mon-fri start
				 * 1 => mon-fri end
				 * 2 => sat start
				 * 3 => sat end
				 * 4 => sun start
				 * 5 => sun end
				 * 6 => special date if applicable
				 * 7 => date specific start
				 * 8 => date specific end
				 *
				 * If sat and sun arent specified, use values for mon-fri.
				 *
				 * The times are in 12 hour format, and use HH:MM:AM, so we need
				 * to strip out the : between the AM or PM, and then calculate
				 * the 24 hour formatted time.
				 */
				include_once('/virtualhosts/olp_lib/OperatingHours.php');
				$operating_hours = new OperatingHours();

				// Loop through and clean up the data.
				$replace = array('/:AM/', '/:PM/');
				$with = array('AM', 'PM');
				foreach (array_keys($rule_value) as $key)
				{
					if ($key != 6)
					{
						$rule_value[$key] = preg_replace($replace, $with, $rule_value[$key]);
						$rule_value[$key] = date('H:i', strtotime($rule_value[$key]));
					}
				}

				// Set mon through friday
				if ($rule_value[0] && $rule_value[1])
				{
					$operating_hours->addDayOfWeekHours('mon', $rule_value[0], $rule_value[1]);
					$operating_hours->addDayOfWeekHours('tue', $rule_value[0], $rule_value[1]);
					$operating_hours->addDayOfWeekHours('wed', $rule_value[0], $rule_value[1]);
					$operating_hours->addDayOfWeekHours('thu', $rule_value[0], $rule_value[1]);
					$operating_hours->addDayOfWeekHours('fri', $rule_value[0], $rule_value[1]);
				}
				//Set saturday
				if ($rule_value[2] && $rule_value[3])
				{
					$operating_hours->addDayOfWeekHours('sat', $rule_value[2], $rule_value[3]);
				}
				else
				{
					$operating_hours->addDayOfWeekHours('sat', $rule_value[0], $rule_value[1]);
				}
				//Set sunday
				if ($rule_value[4] && $rule_value[5])
				{
					$operating_hours->addDayOfWeekHours('sun', $rule_value[4], $rule_value[5]);
				}
				else
				{
					$operating_hours->addDayOfWeekHours('sun', $rule_value[0], $rule_value[1]);
				}
				//Set special
				if ($rule_value[7] && $rule_value[8])
				{
					$operating_hours->addDateHours($rule_value[6], $rule_value[7], $rule_value[8]);
				}

				// Setup the new rule.
				$rule = new OLPBlackbox_Rule_OperatingHours($operating_hours);
				break;

			case 'minimum_age':
				$field = 'dob';
				$rule = 'MinimumAge';
				$event = 'MINIMUM_AGE';
				$stat = 'minimum_age';
				break;

			case 'identical_phone_numbers':
				if ($rule_value === FALSE)
				{
					// If the value is true, we can allow identical numbers, so dont run this check.
					$field = array('phone_home', 'phone_work');
					$rule = 'NotCompare';
					$event = 'ALLOW_IDENTICAL_PHONE_NUMBERS';
					$stat = 'allow_identical_phone_numbers';
				}
				break;

			case 'identical_work_cell_numbers':
				if ($rule_value === FALSE)
				{
					// If the value is true, we can allow identical numbers, so dont run this check.
					$field = array('phone_work', 'phone_cell');
					$rule = 'NotCompare';
					$event = 'ALLOW_IDENTICAL_WORK_CELL_NUMBERS';
					$stat = 'allow_identical_work_cell_numbers';
				}
				break;
				
			// GFORGE_11152 required_references rule not implemented [TF]
			case 'required_references':
				if ($this->getConfig()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
				{
					$field = array();
					$rule = 'ReferencesCount';
					$event = 'REQ_REFS';
					$stat = 'req_refs';
				}
				break;

			case 'dd_check':
				$field = 'social_security_number_encrypted';
				$rule = 'MinimumRecur_DirectDeposit'; // Current: Direct_Deposit_Recur
				$event = 'DIRECT_DEPOSIT_RECUR';
				$stat = 'direct_deposit_recur';
				break;

			case 'military':
				$rule = 'AllowMilitary';
				$event = 'ALLOW_MILITARY';
				$stat = 'allow_military';
				break;
			
			case 'nin_required':
				$field = 'nin';
				$rule = 'Required';
				$event = 'NIN';
				$stat = 'nin';
				break;
				
			case 'residence_type_required':
				$field = 'residence_type';
				$rule = 'Required';
				$event = 'RESIDENCE_TYPE';
				$stat = 'residence_type';
				break;
			
			case 'bank_aba_required':
				$field = 'bank_aba';
				$rule = 'Required';
				$event = 'BANK_ABA';
				$stat = 'bank_aba';
				break;
			
			case 'bank_account_required':
				$field = 'bank_account';
				$rule = 'Required';
				$event = 'BANK_ACCOUNT';
				$stat = 'bank_account';
				break;
			
			case 'employer_phone_required':
				$field = 'supervisor_phone';
				$rule = 'Required';
				$event = 'EMPLOYER_PHONE';
				$stat = 'employer_phone';
				break;
			
			case 'best_call_time':
				$field = 'best_call_time';
				$rule = 'In';
				$event = 'BEST_CALL_TIME';
				$stat = 'best_call_type';
				break;
			
			case 'filters':
				if (is_array($rule_value))
				{
					$filter_factory = $this->getFilterFactory();
					$rule = $filter_factory->getFilterCollection($rule_value);
				}
				break;
		}
		
		// Now that the data for this rule have been set, create the rule
		// and add it to the rule collection.
		if ( $field || $rule )
		{
			if (!($rule instanceof Blackbox_IRule))
			{
				$rule = OLPBlackbox_Factory_Rules::getRule($rule);
				$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => $field,
					OLPBlackbox_Rule::PARAM_VALUE  => $rule_value,
				));
			}

			if ($this->getConfig()->blackbox_mode != OLPBlackbox_Config::MODE_BROKER
				&& !$rule instanceof OLPBlackbox_RuleCollection)
			{
				// in prequal mode, rules can be skipped
				$rule->setSkippable(TRUE);
			}

			if ($event)
			{
				$rule->setEventName($event);
			}

			if ($stat)
			{
				$rule->setStatName($stat);
			}
		}
		
		//  if this is a simple rule, we can keep a reference and reuse it later.
		if ($rule instanceof OLPBlackbox_Factory_Legacy_IReusableRule)
		{
			$this->references[$rule_hash] = $rule;
		}
		
		return $rule;
	}

	/**
	 * Returns an instance of the OLPBlackbox_Factory_Legacy_SuppressionList.
	 *
	 * @return OLPBlackbox_Factory_Legacy_SuppressionList
	 */
	protected function getSuppressionListFactory()
	{
		return new OLPBlackbox_Factory_Legacy_SuppressionList();
	}
	
	/**
	 * Returns an instance of the OLPBlackbox_Factory_Legacy_Filter factory.
	 *
	 * @return OLPBlackbox_Factory_Legacy_Filter
	 */
	protected function getFilterFactory()
	{
		return OLPBlackbox_Factory_Legacy_Filter::getInstance();
	}
	
	/**
	 * Returns an instance of OLPBlackbox_Config.
	 *
	 * @return OLPBlackbox_Config
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}
}
?>
