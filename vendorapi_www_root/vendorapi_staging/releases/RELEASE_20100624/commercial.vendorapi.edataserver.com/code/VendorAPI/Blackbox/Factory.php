<?php

/** Factory to return blackbox object for VendorAPI.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
require_once('UWChooser.php');
class VendorAPI_Blackbox_Factory
{
	/**
	 * Used to pull database connections...
	 * @var VendorAPI_IDriver
	 */
	protected $driver;

	/**
	 * @var VendorAPI_Blackbox_Config
	 */
	protected $config;

	/**
	 * Blackbox rule factory.
	 *
	 * @var VendorAPI_Blackbox_Rule_Factory
	 */
	protected $rule_factory;
    
    protected $UWchooser;

	/**
	 * @param VendorAPI_IDriver $driver
	 * @param VendorAPI_Blackbox_Config $config
	 */
	public function __construct(
		VendorAPI_IDriver $driver,
		VendorAPI_Blackbox_Config $config,
		VendorAPI_Blackbox_Rule_Factory $rule_factory)
	{
		$this->driver = $driver;
		$this->config = $config;
		$this->rule_factory = $rule_factory;
		$this->UWchooser = new VendorAPI_Capaign2UW_Chooser();
	}

	/**
	 * Gets the blackbox object.
	 *
	 * Returns the Blackbox object or FALSE if the object has no targets.
	 *
	 * @param bool $datax_rework Whether rework should run
	 * @param Blackbox_IStateData $state_data
	 * @return Blackbox_Root
	 */
	public function getBlackbox(
		$datax_rework = FALSE,
		Blackbox_IStateData $state_data = NULL
	)
	{
		if (!$state_data)
		{
			$state_data = new VendorAPI_Blackbox_StateData();
		}

		$this->setupTriggers($state_data);
		// Create blackbox and attach our one and only target
		$blackbox = new Blackbox_Root($state_data);

		$target = new VendorAPI_Blackbox_Target();

		$target->setRules($this->getRuleCollection($datax_rework, $state_data));

		$blackbox->setRootCollection($target);

		return $blackbox;
	}

	/**
	 * If the rule factory has any triggers defined,
	 * we'll setup the loan action data as well as add
	 * the list of triggers to it.
	 *
	 * @param Blackbox_IStateData $state
	 * @return void
	 */
	public function setupTriggers(Blackbox_IStateData $state)
	{
		$trigs = $this->rule_factory->getTriggers();
		if ($trigs instanceof VendorAPI_Blackbox_Triggers)
		{
			if (!$state->loan_actions instanceof VendorAPI_Blackbox_LoanActions)
			{
				$state->loan_actions = new VendorAPI_Blackbox_LoanActions();
				$state->loan_actions->setTriggers($trigs);
			}
		}
	}

	/**
	 * Gets a rule collection for a target.
	 *
	 * @param bool $rework
	 * @return Blackbox_IRule
	 */
	public function getRuleCollection($rework = FALSE, Blackbox_IStateData $state_data = NULL)
	{
		// Create the rule collection
		$rule_collection = new Blackbox_RuleCollection();

		if ($this->config->run_tribal)
		{
			if (($rule = $this->rule_factory->getUwTribal($rework)) instanceof Blackbox_IRule)
			{
				$rule_collection->addRule($rule);
			}
		}

		$rule_collection->addRule($this->rule_factory->getSuppressionListRule());

		if ($this->config->prev_customer
			&& ($rule = $this->rule_factory->getPreviousCustomerRule($state_data->customer_history)) instanceof Blackbox_IRule)
		{
			$rule_collection->addRule($rule);
		}

		if ($this->config->used_info
			&& ($rule = $this->rule_factory->getUsedInfoRule()) instanceof Blackbox_IRule)
		{
			$rule_collection->addRule($rule);
		}

		if ($this->config->uw_recur
			&& ($rule = $this->rule_factory->uwRecur()) instanceof Blackbox_IRule)
		{
			$rule_collection->addRule($rule);
		}
		$c = $this->rule_factory->getRuleCollection($state_data->customer_history);
		if ($c instanceof Blackbox_IRule) 
		{
			$rule_collection->addRule($c);
		}
        
		if ($this->config->run_uw)
		{
            		$uwSource = $this->UWchooser->chooseUWinquiry($this->config->campaign);
            		switch ($uwSource[0]) {
                		case 'FT':
                    			if (($rule = $this->rule_factory->getUwFactorTrust($uwSource[1],$uwSource[2],$rework)) instanceof Blackbox_IRule) {
                                    $rule_collection->addRule($rule);
                        			break;
                    			}
                		case 'DATAX':
                    			if (($rule = $this->rule_factory->getUwDataX($rework)) instanceof Blackbox_IRule) {
                                    $rule_collection->addRule($rule);
                                    $this->setUWInquiry($rule,$uwSource[1]);
                        			break;
                    			}
                		case 'CL':
                    			if (($rule = $this->rule_factory->getUwClarity($uwSource[1],$uwSource[2],$rework)) instanceof Blackbox_IRule) {
                                    $rule_collection->addRule($rule);
                        			break;
                    			}
            			}
			if ($rule instanceof Blackbox_IRule) {
			    	
            		} 
		}

		if ($this->config->datax_fraud
			&& !$this->config->is_react
			&& ($rule = $this->rule_factory->getDataXFraud()) instanceof Blackbox_IRule)
		{
			$rule_collection->addRule($rule);
		}

		if ($this->config->verify_rules)
		{
			$verify = $this->rule_factory->getVerifyRule($state_data->customer_history);
			if ($verify instanceof Blackbox_IRule)
			{
				$rule_collection->addRule($verify);
			}
		}

		if ($this->config->verify_paydates)
		{
			$verify = $this->rule_factory->getVerifyPaydateRule();
			if ($verify instanceof Blackbox_IRule)
			{
				$rule_collection->addRule($verify);
			}
		}

		return $rule_collection;
	}

 	public function setUWInquiry($rule,$inquiry)
 	{
        if (is_object($rule->call))
        {
            $request = $rule->call->getRequest();
            $request->setCallType($inquiry);
            $rule->call->setRequest($request);
        }
    }
}
