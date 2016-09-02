<?php

/**
 * A simple list of deferred actions
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_DeferredQueue
{
	/**
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Adds a deferred action to the queue
	 * @param Delegate_1 $d
	 * @return void
	 */
	public function add(Delegate_1 $d, $key = NULL)
	{
		if ($key)
		{
			$this->actions[$key] = $d;
		}
		else
		{
			$this->actions[] = $d;
		}
	}

	/**
	 * Unset a deferred action from the queue
	 * @param string $key
	 * @return void
	 */
	public function remove($key)
	{
		unset($this->actions[$key]);
	}

	/**
	 * Executes all deferred actions
	 * @return void
	 */
	public function executeAll()
	{
		foreach ($this->actions as $delegate)
		{
			$delegate->invoke();
		}
	}
}

?>