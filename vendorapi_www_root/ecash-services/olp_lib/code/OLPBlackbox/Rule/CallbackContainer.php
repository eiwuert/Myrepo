<?php

/**
 * Test a rule container which fires callbacks when valid/invalid.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_CallbackContainer implements Blackbox_IRule
{
	/**
	 * @var Blackbox_IRule
	 */
	protected $rule;

	/**
	 * @var array
	 */
	protected $callbacks;
	
	/**
	 * Set this rule up with a driving rule which will actually run the main
	 * logic.
	 *
	 * @param Blackbox_IRule $rule
	 */
	function __construct(Blackbox_IRule $rule)
	{
		$this->rule = $rule;
		$this->callbacks = array('on_valid' => array(), 'on_invalid' => array());
	}
	
	/**
	 * Create a callback which will run when this rule evaluates as valid.
	 * 
	 * @see OLPBlackbox_Rule_Callback::__construct()
	 * @param string $callback_name The name of the function/method to call.
	 * @param array $arguments The arguments for the method/function call.
	 * @param object|NULL $callback_object If this is a method call, the object is passed here.
	 * @return void
	 */
	public function newOnValidCallback($callback_name, array $arguments = array(), $callback_object = NULL)
	{
		$this->addCallbackTo('on_valid', 
			new OLPBlackbox_Rule_Callback($callback_name, $arguments, $callback_object)
		);
	}
		
	/**
	 * Return all callback objects which will be run when this rule evaluates to
	 * valid.
	 * @return array List of OLPBlackbox_Rule_Callback objects.
	 */
	public function getOnValidCallbacks()
	{
		return $this->callbacks['on_valid'];
	}
	
	/**
	 * Create a new callback to fire when this rule evaluates as invalid.
	 * 
	 * @see OLPBlackbox_Rule_Callback::__construct()
	 * @param string $callback_name The name of the function/method to call.
	 * @param array $arguments The arguments for the method/function call.
	 * @param object|NULL $callback_object If this is a method call, the object is passed here.
	 * @return void
	 */
	public function newOnInvalidCallback($callback_name, array $arguments = array(), $callback_object = NULL)
	{
		$this->addCallbackTo('on_invalid',
			new OLPBlackbox_Rule_Callback($callback_name, $arguments, $callback_object)
		);
	}
	
	/**
	 * Returns the callback objects which will be invoked when this rule is 
	 * evaluated as invalid.
	 * @return array List of OLPBlackbox_Rule_Callback objects.
	 */
	public function getOnInvalidCallbacks()
	{
		return $this->callbacks['on_invalid'];
	}
	
	/**
	 * Returns the underlying rule this decorates.
	 * @return Blackbox_IRule
	 */
	public function getRule()
	{
		return $this->rule;
	}
	
	/**
	 * Runs the contained rule, running callback methods based on the result.
	 * @param Blackbox_Data $data Data about the application being processed.
	 * @param Blackbox_IStateData $state_data Data about the state of blackbox.
	 * @return bool Valid or not.
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = $this->rule->isValid($data, $state_data);
		
		if ($valid)
		{
			$this->runOnValidCallbacks();
		}
		else 
		{
			$this->runOnInvalidCallbacks();
		}
		
		return $valid;
	}
	
	/**
	 * String representation of this object for debugging purposes.
	 * @return string
	 */
	public function __toString()
	{
		$count = count($this->getOnInvalidCallbacks()) 
			+ count($this->getOnValidCallbacks());
		$rule_string = method_exists($this->rule, '__toString')
			? $this->rule->__toString()
			: get_class($this->rule);
			
		return '[CallbackContainer using ' 
			. $rule_string 
			. " (with $count callbacks) ]";
	}
	
	/**
	 * Add a callback object to a collection of callbacks to be triggered by
	 * the $trigger which will trigger it such as 'on_valid' or 'on_invalid'
	 * 
	 * @param string $trigger Descriptor for when these events will fire.
	 * @param OLPBlackbox_Rule_Callback $callback The callback to run.
	 * @return void
	 */
	protected function addCallbackTo($trigger, OLPBlackbox_Rule_Callback $callback)
	{	
		$this->callbacks[$trigger][] = $callback;
	}
	
	/**
	 * @return void
	 */
	protected function runOnValidCallbacks()
	{
		$this->runCallbacks($this->callbacks['on_valid']);
	}
	
	/**
	 * @return void
	 */
	protected function runOnInvalidCallbacks()
	{
		$this->runCallbacks($this->callbacks['on_invalid']);
	}
	
	/**
	 * Loop through a list of OLPBlackbox_Rule_Callback objects and invoke them.
	 * @param array $callbacks List of OLPBlackbox_Rule_Callback objects.
	 * @return void
	 */
	protected function runCallbacks(array $callbacks)
	{
		foreach ($callbacks as $callback)
		{
			if ($callback instanceof OLPBlackbox_Rule_Callback)
			{
				// can change to $callback(); in PHP 5.3
				$callback->__invoke();
			}
		}
	}
}

?>