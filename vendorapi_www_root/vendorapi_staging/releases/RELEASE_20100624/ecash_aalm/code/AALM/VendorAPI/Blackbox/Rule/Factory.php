<?php
/**
 *
 * AALM specific rule factory
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class AALM_VendorAPI_Blackbox_Rule_Factory extends ECash_VendorAPI_Blackbox_Rule_Factory
{
	/**
	 * List of organic campaigns for AALM
	 * @var array
	 */
	protected $organic_campaign_list = array();

	/**
	 * @return VendorAPI_Blackbox_Rule_DataxRecur
	 */
	public function uwRecur()
	{
		//do not run datax for reacts [#53647]
		if($this->getRuleMode() == VendorAPI_Blackbox_Config::MODE_ECASH_REACT)
		{
			//instead look at the datax packet from the last non-react [#54991]
			return new AALM_VendorAPI_Blackbox_Rule_UwRecur(
				$this->event_log,
				$this->driver->getInquiryClient(),
				$this->driver->getCompany());
		}

		return new VendorAPI_Blackbox_Rule_UwRecur(
			$this->event_log,
			$this->driver->getInquiryClient(),
			'90',
			$this->driver->getCompanyId(),
			$this->driver->getDataXCallType($this->loan_type_id));
	}

	public function getUwTribal($rework = FALSE)
	{
		$call = $this->driver->getTribalCall($this->loan_type_id);

		$rule = $this->getTribalRule($call, $rework);

		// Observers ?

		return $rule;
	}

	/**
	 * Returns the DataX rule.
	 *
	 * @param VendorAPI_Blackbox_DataX_CallHistory $history
	 * @param bool $rework
	 * @param bool $ignore_skip Ignore the skip check and force return of the real DataX rule
	 * @return VendorAPI_Blackbox_Rule_DataX
	 */
	public function getUwDataX($rework = FALSE, $ignore_skip = FALSE)
	{
		//do not run datax for reacts [#53647]
		if ((!$ignore_skip && $this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::DATAX)) || $this->getRuleMode() == VendorAPI_Blackbox_Config::MODE_ECASH_REACT || in_array(strtolower($this->config->campaign), $this->organic_campaign_list))
		{
			return new VendorAPI_Blackbox_Rule_Skip();
		}

		$call = $this->driver->getDataXCall($this->loan_type_id);

		$rule = $this->getDataXRule($call, $rework);
		$observers = $this->getDataXRuleObservers();

		if (!is_null($observers) && is_array($observers))
		{
			foreach ($observers as $observer)
			{
				$rule->attachObserver($observer);
			}
		}
		return $rule;
	}

	/**
	 * Escalated fix for #32208, which is related to #22264
	 * - AALM does not want to purchase leads when there is a 
	 *   failure from DataX.  This method wraps the parent and sets 
	 *   the appropriate value to accomplish that. [BR]
	 */
	public function getDataXRule(TSS_DataX_Call $call, $rework = FALSE)
	{
		$rule = parent::getDataXRule($call, $rework);
		if($rule instanceof VendorAPI_Blackbox_Rule_DataX)
		{
			$rule->setFailOnError(TRUE);
		}
		return $rule;
	}

    /**
	 * Returns the FactorTrust rule.
	 *
	 * @param VendorAPI_Blackbox_FactorTrust_CallHistory $history
	 * @param bool $rework
	 * @param bool $ignore_skip Ignore the skip check and force return of the real DataX rule
	 * @return VendorAPI_Blackbox_Rule_FactorTrust
	 */
	public function getUwFactorTrust($inquiry, $store, $rework = FALSE, $ignore_skip = FALSE)
	{
		//do not run datax for reacts [#53647]
		if ((!$ignore_skip && $this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::FT)) || $this->getRuleMode() == VendorAPI_Blackbox_Config::MODE_ECASH_REACT || in_array(strtolower($this->config->campaign), $this->organic_campaign_list))
		{
			return new VendorAPI_Blackbox_Rule_Skip();
		}

		$call = $this->driver->getFactorTrustCall($inquiry, $store, $this->loan_type_id);

		$rule = $this->getFactorTrustRule($call, $rework);
		$observers = $this->getFactorTrustRuleObservers();

		if (!is_null($observers) && is_array($observers))
		{
			foreach ($observers as $observer)
			{
				$rule->attachObserver($observer);
			}
		}
		return $rule;
	}

	/**
	 * Escalated fix for #32208, which is related to #22264
	 * - AALM does not want to purchase leads when there is a 
	 *   failure from FactorTrust.  This method wraps the parent and sets 
	 *   the appropriate value to accomplish that. [BR]
	 */
	public function getFactorTrustRule(FactorTrust_UW_Call $call, $rework = FALSE)
	{
		$rule = parent::getFactorTrustRule($call, $rework);
		if($rule instanceof VendorAPI_Blackbox_Rule_FactorTrust)
		{
			$rule->setFailOnError(TRUE);
		}
		return $rule;
	}
	

	/**
	 * Returns the Clarity rule.
	 *
	 * @param VendorAPI_Blackbox_Clarity_CallHistory $history
	 * @param bool $rework
	 * @param bool $ignore_skip Ignore the skip check and force return of the real Clarity rule
	 * @return VendorAPI_Blackbox_Rule_Clarity
	 */
	public function getUwClarity($inquiry, $store, $rework = FALSE, $ignore_skip = FALSE)
	{
		//do not run datax for reacts [#53647]
		if ((!$ignore_skip && $this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::CL)) || $this->getRuleMode() == VendorAPI_Blackbox_Config::MODE_ECASH_REACT || in_array(strtolower($this->config->campaign), $this->organic_campaign_list))
		{
			return new VendorAPI_Blackbox_Rule_Skip();
		}

		$call = $this->driver->getClarityCall($inquiry, $store, $this->loan_type_id);

		$rule = $this->getClarityRule($call, $rework);
		$observers = $this->getClarityRuleObservers();

		if (!is_null($observers) && is_array($observers))
		{
			foreach ($observers as $observer)
			{
				$rule->attachObserver($observer);
			}
		}
		return $rule;
	}

	/**
	 * Escalated fix for #32208, which is related to #22264
	 * - AALM does not want to purchase leads when there is a 
	 *   failure from Clarity.  This method wraps the parent and sets 
	 *   the appropriate value to accomplish that. [BR]
	 */
	public function getClarityRule(Clarity_UW_Call $call, $rework = FALSE)
	{
		$rule = parent::getClarityRule($call, $rework);
		if($rule instanceof VendorAPI_Blackbox_Rule_Clarity)
		{
			$rule->setFailOnError(TRUE);
		}
		return $rule;
	}
	
	/**
	 * [#47175] Adds AALM specific suppression lists
	 * @return Blackbox_IRule
	 */
	public function getRuleCollection(ECash_CustomerHistory $customer_history)
	{
		$collection = new Blackbox_RuleCollection();

		//skip the parent so AALM controls when PurchasedLeads is run and how [#44776]
		//$parent_rule = parent::getRuleCollection($customer_history);
		//if ($parent_rule instanceof Blackbox_IRule) $collection->addRule($parent_rule);

		//Run only on non-organic leads [#44776]
		if ($this->config->blackbox_mode == VendorAPI_Blackbox_Config::MODE_BROKER &&
			!in_array(strtolower($this->config->campaign), $this->organic_campaign_list))
		{
			$collection->addRule(
				new VendorAPI_Blackbox_Rule_PurchasedLeads(
					$this->config->event_log, 
	   				new VendorAPI_PurchasedLeadStore_Memcache(
							$this->driver->getEnterprise(), 
							$this->driver->getMemcachePool()
					), 
					NULL, 
					NULL, 
					$this->config->company, 
					'90 days', 
					1
					)
				);
		}

		if (!$this->config->debug->skipRule())
		{
			$collection->addRule($this->rulesFromXmlFile(
				dirname(__FILE__) . '/' . $this->config->company . '_basic_rules.xml',
				$customer_history
			));
		}
		
		return $collection;
	}
	
	protected function getBrokerSuppressionLists() {
		$lists = array_merge_recursive(
			$this->getReactSuppressionLists(),
			array(
                                'EXCLUDE' => array(
                                        'Generic - Suppression List',

				),
                                /*
				'RESTRICT' => array(
                                ),
				'EXCLUDE_G' => array(
				),
                                'RESTRICT_M' => array(
                                ),
                                'EXCLUDE_M' => array(
                                ),
                                'ROUTE_M' => array(
                                ),
                                */
                        )
		);
		return $lists;
	}

	protected function getReactSuppressionLists() {
		return array(
			//'VERIFY' => array(
			//	'Employer Watch List',
			//	'ABA Watch List',
			//),
			'EXCLUDE' => array(
				'GENERIC - React Suppression List',
			),
			'EXCLUDE_G' => array(
				'GENERIC - Global Multiple Exclusion',
			),
		);
	}
}

