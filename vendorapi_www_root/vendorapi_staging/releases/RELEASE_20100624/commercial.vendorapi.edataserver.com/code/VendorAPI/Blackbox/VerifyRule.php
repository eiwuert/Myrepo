<?php 

/**
 * VERIFY RULE
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
abstract class VendorAPI_Blackbox_VerifyRule extends VendorAPI_Blackbox_Rule
{
	/**
	 * The name of the loan action this rule 
	 * will trigger
	 *
	 * @var string
	 */
	protected $actions = array();


	/**
	  * Verify rules really don't actually fail,
	  * so we'll ALWAYS return true
	  *
	  * @param Blackbox_Data $data
	  * @param Blackbox_IStateData $state_data
	  * @return TRUE
	  */
	final public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::isValid($data, $state_data);
		return TRUE;
	}
	
	/**
	 * Adds a loan action to the state data?
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	public function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->hasAction())
		{
			$actions = $this->getActionStack();
			if (count($actions))
			{
				foreach ($actions as $action)
				{
					$this->addAction($state_data, $action);
				}
			}
		}
		
		if (($event = $this->getEventName()) !== NULL)
		{
			$this->logEvent($event, 'VERIFY', VendorAPI_Blackbox_EventLog::FAIL);
		}
	}
	
	/**
	 * Adds an action to the state right now
	 *
	 * @param Blackbox_IStateData $state_data
	 * @param string $action_name
	 * @return void
	 */
	public function addAction($state_data, $action_name)
	{
		if (!$state_data->loan_actions instanceof VendorAPI_Blackbox_LoanActions)
		{
			$state_data->loan_actions = $this->getLoanActionObject();	
		}
		$state_data->loan_actions->addLoanAction($action_name);
	}
	
	/**
	 * Return a new loan action object
	 *
	 * @return VendorAPI_Blackbox_LoanActions
	 */
	public function getLoanActionObject()
	{
		return new VendorAPI_Blackbox_LoanActions();
	}
	
	/**
	 * Set the action in the stack which will be added only
	 * if the rule is invalid.
	 *
	 * @param string $action_name
	 * @return void
	 */
	public function addActionToStack($action_name)
	{
		if (is_string($action_name))
		{
			if (!is_array($this->actions))
			{
				$this->actions = array();
			}
			$this->actions[] = $action_name;
		}
		if (is_array($action_name))
		{
			$this->addActionsToStack($action_name);
		}
	}
	
	/**
	 * Add an array of actions ot the stack
	 * to be attached.
	 *
	 * @param array $action_names
	 * @return void
	 */
	public function addActionsToStack(array $action_names)
	{
		foreach ($action_names as $name)
		{
			if (is_string($name))
			{
				$this->addActionToStack($name);
			}
		}
	}
	
	/**
	 * Does this rule have a loan action name?
	 *
	 * @return boolean 
	 */
	public function hasAction($name = NULL)
	{
		return is_null($name) ? !empty($this->actions) : in_array($name, $this->actions);
	}
	
	/**
	 * Remove an action from the list of actions
	 *
	 * @param array $action_name
	 * @return void
	 */
	public function removeActionFromStack($action_name)
	{
		$key = array_search($action_name, $this->actions);
		if (is_numeric($key))
		{
			unset($this->actions[$key]);
		}
	}
	
	/**
	 * Return the name of the action?
	 *
	 * @return array
	 */
	public function getActionStack()
	{
		return $this->actions;
	} 
}
