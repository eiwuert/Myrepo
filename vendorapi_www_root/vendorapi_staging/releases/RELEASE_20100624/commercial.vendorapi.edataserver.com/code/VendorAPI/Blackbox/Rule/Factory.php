<?php
/**
 * Blackbox rule factory.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_Factory
{
	/**
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 * @var VendorAPI_Blackbox_Config
	 */
	protected $config;

	/**
	 * @var int
	 */
	protected $loan_type_id;

	/**
	 * @var VendorAPI_Blackbox_DebugConfig
	 */
	protected $debug;

	/**
	 * @var VendorAPI_Blackbox_EventLog
	 */
	protected $event_log;


	/**
	 * @param VendorAPI_IDriver $driver
	 * @param VendorAPI_Blackbox_Config $config
	 * @param integer $loan_type_id
	 */
	public function __construct(VendorAPI_IDriver $driver, VendorAPI_Blackbox_Config $config, $loan_type_id)
	{
		$this->driver = $driver;
		$this->config = $config;
		$this->event_log = $config->event_log;
		$this->loan_type_id = $loan_type_id;
	}

	public function getUwTribal($rework = FALSE)
	{
		$call = $this->driver->getTribalCall($this->loan_type_id);

		$rule = $this->getTribalRule($call, $rework);

		// Observers ?

		return $rule;
	}
	
	/**
	 * Returns the Tribal rule implementation
	 * @param TSS_Tribal_Call $call
	 * @param bool $rework
	 * @return VendorAPI_Blackbox_Rule_Tribal
	 */
	protected function getTribalRule(TSS_Tribal_Call $call, $rework = FALSE)
	{
		return new VendorAPI_Blackbox_Rule_Tribal($this->event_log, $call, $rework);
	}

	/**
	 * Returns the DataX underwriter rule.
	 *
	 * @param bool $rework
	 * @param bool $ignore_skip Ignore the skip check and force return of the real DataX rule
	 * @return VendorAPI_Blackbox_Rule_DataX
	 */
	public function getUwDataX($rework = FALSE, $ignore_skip = FALSE)
	{

		if (!$ignore_skip && $this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::DATAX))
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
	 * Returns the Factor Trust underwriter rule.
	 *
	 * @param bool $rework
	 * @param bool $ignore_skip Ignore the skip check and force return of the real DataX rule
	 * @return VendorAPI_Blackbox_Rule_DataX
	 */
	public function getUwFactorTrust($inquiry, $store, $rework = FALSE, $ignore_skip = FALSE)
	{

		if (!$ignore_skip && $this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::FT))
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
	 * Returns the Clarity underwriter rule.
	 *
	 * @param bool $rework
	 * @param bool $ignore_skip Ignore the skip check and force return of the real DataX rule
	 * @return VendorAPI_Blackbox_Rule_DataX
	 */
	public function getUwClarity($inquiry, $store, $rework = FALSE, $ignore_skip = FALSE)
	{

		if (!$ignore_skip && $this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::CL))
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
	 * Returns the datax rule implementation
	 * @param TSS_DataX_Call $call
	 * @param bool $rework
	 * @return VendorAPI_Blackbox_Rule_DataX
	 */
	protected function getDataXRule(TSS_DataX_Call $call, $rework = FALSE)
	{
		return new VendorAPI_Blackbox_Rule_DataX($this->event_log, $call, $rework);
	}

	/**
	 * Get the observer for adverse actions, if any
	 * @return VendorAPI_Blackbox_DataX_ICallObserver[]
	 */
	protected function getDataXRuleObservers()
	{
		return NULL;
	}
    
	/**
	 * Returns the datax rule implementation
	 * @param FactorTrust_UW_Call $call
	 * @param bool $rework
	 * @return VendorAPI_Blackbox_Rule_FactorTrust
	 */
	protected function getFactorTrustRule(FactorTrust_UW_Call $call, $rework = FALSE)
	{
		return new VendorAPI_Blackbox_Rule_FactorTrust($this->event_log, $call, $rework);
	}

	/**
	 * Get the observer for adverse actions, if any
	 * @return VendorAPI_Blackbox_FactorTrust_ICallObserver[]
	 */
	protected function getFactorTrustRuleObservers()
	{
		return NULL;
	}
        
	/**
	 * Returns the datax rule implementation
	 * @param Clarity_UW_Call $call
	 * @param bool $rework
	 * @return VendorAPI_Blackbox_Rule_Clarity
	 */
	protected function getClarityRule(Clarity_UW_Call $call, $rework = FALSE)
	{
		return new VendorAPI_Blackbox_Rule_Clarity($this->event_log, $call, $rework);
	}

	/**
	 * Get the observer for adverse actions, if any
	 * @return VendorAPI_Blackbox_Clarity_ICallObserver[]
	 */
	protected function getClarityRuleObservers()
	{
		return NULL;
	}

	/**
	 * Returns the DataX Fraud Check rule
	 * @return VendorAPI_Blackbox_Rule_DataX
	 */
	public function getDataXFraud()
	{
		if ($this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::DATAX_FRAUD))
		{
			return new VendorAPI_Blackbox_Rule_Skip();
		}
		
		$call = $this->driver->getDataXFraudCall();
		$rule = $this->getDataXFraudRule($call);

		return $rule;
	}

	/**
	 * Returns the DataX Fraud Check rule implementation
	 * @param TSS_DataX_Call $call
	 * @param bool $rework
	 * @return VendorAPI_Blackbox_Rule_DataX
	 */
	protected function getDataXFraudRule(TSS_DataX_Call $call)
	{
		return new VendorAPI_Blackbox_Rule_DataXFraud($this->event_log, $call);
	}

	/**
	 * Builds and returns the "used info" rule
	 * @return VendorAPI_Blackbox_Rule_UsedABACheck
	 */
	public function getUsedInfoRule()
	{
		if ($this->config->blackbox_mode != VendorAPI_Blackbox_Config::MODE_BROKER)
		{
			return NULL;
		}

		if ($this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::USED_INFO))
		{
			return new VendorAPI_Blackbox_Rule_Skip();
		}

		// default threshold values (1 SSN, -1 year) are fine
		return new VendorAPI_Blackbox_Rule_UsedABACheck(
			$this->event_log
		);
	}

	/**
	 * Gets the appropriate previous customer rule
	 *
	 * @param ECash_CustomerHistory $customer_history
	 * @return Blackbox_IRule
	 */
	public function getPreviousCustomerRule(ECash_CustomerHistory $customer_history)
	{
		if ($this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::PREV_CUSTOMER))
		{
			return new VendorAPI_Blackbox_Rule_Skip();
		}

		$factory = VendorAPI_Blackbox_Generic_PreviousCustomerFactory::getInstance(
			$this->config->enterprise,
			$this->config->company,
			$this->driver,
			$this->config
		);
		return $factory->getPreviousCustomerRule($customer_history);
	}

	/**
	 * Returns a collection of suppression rules.
	 *
	 * @todo Use name_short instead of field_name of the suppression list.
	 * @return Blackbox_IRule
	 */
	public function getSuppressionListRule()
	{
		$collection = new Blackbox_RuleCollection();
		if ($this->config->debug->skipRule(VendorAPI_Blackbox_DebugConfig::SUPPRESSION_LISTS))
		{
			return new VendorAPI_Blackbox_Rule_Skip();
		}
	
		$suppression_list = new VendorAPI_SuppressionList_CachingLoader(new VendorAPI_SuppressionList_DBLoader($this->driver->getDatabase()), $this->driver->getEnterprise());

		if($this->getRuleMode() == VendorAPI_Blackbox_Config::MODE_ECASH_REACT)
		{
			$lists = $this->getReactSuppressionLists();
		}
		else
		{
			$lists = $this->getBrokerSuppressionLists();
		}

		foreach ($lists as $type => $list_array)
		{
			foreach ($list_array as $list_name)
			{
				if ($type == 'VERIFY')
				{
					$collection->addRule(
						new VendorAPI_Blackbox_Rule_Suppression_Verify(
							$this->config->event_log,
							$suppression_list,
							$list_name));
				}
				elseif ($type == 'EXCLUDE')
				{
					$collection->addRule(
						new VendorAPI_Blackbox_Rule_Suppression_Exclude(
							$this->config->event_log,
							$suppression_list,
							$list_name));
				}
				elseif ($type == 'RESTRICT')
				{
					$collection->addRule(
						new VendorAPI_Blackbox_Rule_Suppression_Restrict(
							$this->config->event_log,
							$suppression_list,
							$list_name));
				}
                                //asm 3
                                elseif ($type == 'EXCLUDE_G')
				{
					$collection->addRule(
						new VendorAPI_Blackbox_Rule_Suppression_ExcludeG(
							$this->config->event_log,
							$suppression_list,
							$list_name,
                                                        $this->config->campaign));
				}
                                elseif ($type == 'EXCLUDE_M')
				{
					$collection->addRule(
						new VendorAPI_Blackbox_Rule_Suppression_ExcludeMU(
							$this->config->event_log,
							$suppression_list,
							$list_name,
                                                        $this->config->campaign));
				}
                                /*
                                elseif ($type == 'EXCLUDE_M')
				{
					$collection->addRule(
						new VendorAPI_Blackbox_Rule_Suppression_ExcludeMU(
							$this->config->event_log,
							$suppression_list,
							$list_name,
                                                        $this->config->campaign));
				}
				elseif ($type == 'RESTRICT_M')
				{
					$collection->addRule(
						new VendorAPI_Blackbox_Rule_Suppression_RestrictMU(
							$this->config->event_log,
							$suppression_list,
							$list_name,
							$this->config->campaign));
				}
                                */
                                elseif ($type == 'ROUTE_M')
				{
					$collection->addRule(
						new VendorAPI_Blackbox_Rule_Suppression_RouteM(
							$this->config->event_log,
							$suppression_list,
							$list_name,
							$this->config->campaign));
				}
				else
				{
					throw new Exception("Unknown suppression list type {$type} given to Factory");
				}
			}
		}
		return $collection;
	}

	/**
	 * Returns a blackbox rule for verify
	 * or by default, false since we have no
	 * verify rules.
	 *
	 * @return FALSE|Blackbox_IRule
	 */
	public function getVerifyRule(ECash_CustomerHistory $customer_history)
	{
		$rule_collection = new Blackbox_RuleCollection();
		//$rule_collection->addRule(new VendorAPI_Blackbox_Rule_VerifyMonthlyIncome($this->event_log, 1300));
		$rule_collection->addRule(new VendorAPI_Blackbox_Rule_VerifySameWorkHomePhone($this->event_log));
		//$rule_collection->addRule(new VendorAPI_Blackbox_Rule_VerifyIncomeFrequency($this->event_log));
		$rule_collection->addRule(new VendorAPI_Blackbox_Rule_VerifyEmployer($this->event_log));
		$rule_collection->addRule(new VendorAPI_Blackbox_Rule_VerifyEmailAddress($this->event_log));
		$rule_collection->addRule(new VendorAPI_Blackbox_Rule_VerifyDueDate($this->event_log));
		return $rule_collection;
	}

	/**
	 * Returns a new paydate proximity rule. This is split so it can
	 * be run after the onAgree page
	 *
	 * @return VendorAPI_Blackbox_Rule_VerifyPaydateProximity
	 */
	public function getVerifyPaydateRule()
	{
		return new VendorAPI_Blackbox_Rule_VerifyPaydateProximity($this->event_log, 5);
	}

	/**
	 * Returns a collection of triggers?
	 *
	 * @return VendorAPI_Blackbox_Triggers|FALSE
	 */
	public function getTriggers()
	{
		return FALSE;
	}

	/**
	 * return a datax recur rule?
	 *
	 * @param ECash_CustomerHistory $customer_history
	 * @return Blackbox_IRule
	 */
	public function uwRecur()
	{
		return FALSE;
	}

	/**
	 * Returns a rule collection
	 * @param ECash_CustomerHistory $customer_history
	 * @return boolean
	 */
	public function getRuleCollection(ECash_CustomerHistory $customer_history)
	{
		return FALSE;
	}

	
	/**
	 * @return Blackbox_RuleCollection
	 */
	protected function rulesFromXmlFile($file_location, ECash_CustomerHistory $customer_history)
	{
		$doc = new DOMDocument('1.0');
		if (!$doc->load($file_location))
		{
			throw new Blackbox_Exception("unable to load rules from $file_location");
		}

		return $this->rulesFromDOMDocument($doc, $customer_history);
	}

	/**
	 * @param DOMDocument $doc The configuration for the rules.
	 * @return Blackbox_RuleCollection
	 */
	protected function rulesFromDOMDocument(DOMDocument $doc, ECash_CustomerHistory $customer_history)
	{
		$rule_collection = new Blackbox_RuleCollection();

		$xpath = new DOMXPath($doc);

		// TODO: add property check on configuration? not necessary yet with a
		// single company/enterprise/"campaign" being assembled with each VendorAPI call
		
		/**
		 * Now allowing rules in the XML file to be run for Non-Organic leads. [#54143]
		 *
		 * If we're an organic lead, essentially pull any rule node that either has no runForSource attribute,
		 * or an attribute value of both or enterprise.  For non-organic, we only look for rules with attribute
		 * values of nonenterprise or both.
		 */
		if($this->isIsOrganicLead())
		{
			$rule_nodes = $xpath->query('//configuration/rules/rule[@runForSource="enterprise" or @runForSource="both" or not(@runForSource)]');
		}
		else
		{
			$rule_nodes = $xpath->query('//configuration/rules/rule[@runForSource="nonenterprise" or @runForSource="both"]');
		}

		if ($rule_nodes->length)
		{
			foreach ($rule_nodes as $rule_element)
			{
				$rule = $this->ruleFromDOMElement($rule_element, $customer_history);
				if ($rule instanceof Blackbox_IRule) $rule_collection->addRule($rule);
			}
		}

		return $rule_collection;
	}

	/**
	 * Determines if this lead was posted to the "organic" campaign for this company.
	 *
	 * The organic campaign is intended for use from the enterprise websites, not
	 * from external lead sources.
	 * @return boolean
	 */
	protected function isIsOrganicLead()
	{
		return strtolower($this->config->campaign) == strtolower($this->config->company);
	}

	function ruleModeOK(DOMElement $e)
	{
		/* @var $mode_elements DOMNodeList */
		$mode_elements = $e->getElementsByTagName("mode");

		if ($mode_elements->length < 1) return TRUE;
		
		for ($i=0; $i<$mode_elements->length; ++$i)
		{
			if (strtoupper(trim($mode_elements->item($i)->nodeValue)) == $this->getRuleMode())
				return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Figures out the mode we're running in as far as rules are concerned (in the
	 * form that originally came from OLP blackbox.)
	 * @return string
	 */
	protected function getRuleMode()
	{
		$mode = NULL;
		if ($this->config->is_react
			|| $this->config->blackbox_mode == VendorAPI_Blackbox_Config::MODE_ECASH_REACT)
		{
			$mode = VendorAPI_Blackbox_Config::MODE_ECASH_REACT;
		}
		else
		{
			$mode = VendorAPI_Blackbox_Config::MODE_BROKER;
		}

		return $mode;
	}

	/**
	 * @param DOMElement $e The DOM Element representing the rule we're parsing.
	 * @return boolean
	 */
	public function ruleIsForDifferentCampaign(DOMElement $e)
	{
		$k = 'campaign_name';
		if ($e->hasAttribute('campaign_name')
			and $e->getAttribute('campaign_name') != $this->config->campaign)
			return true;

		return false;
	}

	/**
	 * @param DOMElement $node The node which contains the definition of a
	 * blackbox rule.
	 * @return Blackbox_IRule
	 */
	protected function ruleFromDOMElement(DOMElement $e, ECash_CustomerHistory $customer_history)
	{
		$rule = null;

		$class = $e->getAttribute("class_name");

		if (!class_exists($class))
		{
			throw new Blackbox_Exception(
				"Unable to assemble rule, unknown class $class"
			);
		}

		if (!$this->ruleModeOK($e) or $this->ruleIsForDifferentCampaign($e)) return null;

		// TODO: should be in XML, but this whole thing should be in CFE
		if ($this->isTitleLoanRule($e) && !$this->config->is_title_loan)
		{
			return null;
		}

		/* @var $rule VendorAPI_Blackbox_Rule */
		$rule = new $class($this->event_log);	// TODO: constructor params?



		$Class = new ReflectionClass(get_class($rule));
		if ($Class->hasMethod('addDatabase'))
		{
			$rule->addDatabase($this->driver->getDatabase());
		}
		if ($Class->hasMethod('setCustomerHistory'))
		{
			$rule->setCustomerHistory($customer_history);
		}

		if ($e->getAttribute("event_name"))
			$rule->setEventName($e->getAttribute("event_name"));

		if ($e->hasChildNodes())
		{
			/* @var $setup_params DOMNodeList */
			$setup_params = $e->getElementsByTagName("setup_params");
			if ($setup_params->length)
			{
				if ($setup_params->length > 1)
					throw new Blackbox_Exception("more than one set of setup params!?");
				$this->setupRuleFromDOMElement($rule, $setup_params->item(0));
			}
		}

		if ($e->getAttribute('action')
			&& strtolower($e->getAttribute('action')) == 'verify')
		{
			$rule = new VendorAPI_Blackbox_Rule_VerifyRuleDecorator($rule);
		}

		return $rule;
	}

	/**
	 * Determines if the DOMElement representing a Blackbox rule is a title loan
	 * rule.
	 *
	 * TODO: I hate this, but honestly, this whole thing should be in CFE not in
	 * some specific BBx rule XML format.
	 * 
	 * @param DOMElement $e A <rule> element which should have an event_name.
	 * @return boolean
	 */
	protected function isTitleLoanRule(DOMElement $e)
	{
		return ($e->getAttribute("event_name") && stristr($e->getAttribute("event_name"), 'vehicle') !== false);
	}

	/**
	 * @param VendorAPI_Blackbox_Rule $rule
	 * @param DOMElement $e The <setup_params> element which contains the <field>
	 * and <value> items.
	 * @return null
	 */
	protected function setupRuleFromDOMElement(VendorAPI_Blackbox_Rule $rule, DOMElement $e)
	{
		$params = array();

		$field = $this->getValueByTagName($e, "field");
		if ($field) $params[Blackbox_StandardRule::PARAM_FIELD] = $field;

		$value = $this->getValueByTagName($e, "value");
		if ($value) 
		{
			if (substr($value, 0, 2) == 'a:') $value = unserialize($value);
			elseif ($e->getElementsByTagName("value")->item(0)->getAttribute('type'))
				$value = $this->castToType($value, $e->getElementsByTagName("value")->item(0)->getAttribute('type'));
			$params[Blackbox_StandardRule::PARAM_VALUE] = $value;
		}

		if (count($params)) $rule->setupRule($params);
	}

	function castToType($value,  $type)
	{
		if (!is_string($type)) throw new InvalidArgumentException("type must be a string");
		$type = strtolower($type);
		$func = "{$type}val"; // intval(), strval(), etc.

		if ($type == 'boolean') return (bool)$value;
		elseif (function_exists($func)) return $func($value);
		elseif ($type == 'string') return "$value";
		else throw new InvalidArgumentException("Unable to cast value " . var_export($value, true) . " to type $type");
	}

	/**
	 * @param DOMElement $e An element with a child that has a tagName = $field_name
	 * @param string $tag_name The name of the child element we'd like the value of.
	 * @return string|null The value of the element, or null if the element doesn't exist.
	 */
	protected function getValueByTagName(DOMElement $e, $tag_name)
	{
		$value = null;

		/* @var $nodes DOMNodeList */
		$nodes = $e->getElementsByTagName($tag_name);

		if ($nodes->length)
		{
			if ($nodes->length == 1 && $nodes->item(0) instanceof DOMElement)
				$value = $nodes->item(0)->nodeValue;
			else
				throw new InvalidArgumentException("more than one child of tag $tag_name");
		}

		return $value;
	}

	/**
	 * Factory method to return a multidimensional array of supression list
	 * types and names for non-reacts.
	 * 
	 * e.g.:
	 * 	array(
	 * 		'VERIFY' => array('Suppresion 1', Suppression 2'),
	 * 		'EXCLUDE' => array('Suppresion 3', Suppression 4'),
	 * 		'RESTRICT' => array('Suppresion 5', Suppression 6')
	 * 	)
	 */
	protected function getBrokerSuppressionLists()
	{
		return array();
	}
		
	/**
	 * Factory method to return a multidimensional array of supression list
	 * types and names for reacts.
	 * 
	 * e.g.:
	 * 	array(
	 * 		'VERIFY' => array('Suppresion 1', Suppression 2'),
	 * 		'EXCLUDE' => array('Suppresion 3', Suppression 4'),
	 * 		'RESTRICT' => array('Suppresion 5', Suppression 6')
	 * 	)
	 */
	protected function getReactSuppressionLists()
	{
		return array();
	}
}
