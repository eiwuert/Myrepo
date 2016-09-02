<?php
/**
 * Execute a one or more actions
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_CFE_Actions_DoAll extends ECash_CFE_Base_BaseAction implements ECash_CFE_IExpression
{
	/**
	 * Executes all actions passed to it
	 * @return string
	 */
	public function getType()
	{
		return "DoAll";
	}

	/**
	 * Returns an array of required parameters with format name=>type
	 * @return array
	 */
	public function getParameters()
	{
		return array('ECash_CFE_IAction');
	}

	public function evaluate(ECash_CFE_IContext $c)
	{
		return $this->execute($c);
	}

	/**
	 * Executes the action
	 *
	 * @param ECash_CFE_IContext $c
	 */
	public function execute(ECash_CFE_IContext $c)
	{
		foreach($this->params as $parameter)
		{
			$parameter->execute($c);
		}
	}
}