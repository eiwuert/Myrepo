<?php
/**
 * CFE action to add an exit strategy offer.
 */
class VendorAPI_CFE_Actions_AddExitStrategyFlag extends ECash_CFE_Base_BaseAction implements ECash_CFE_IExpression
{
	/**
	 * Returns a name short that can be used to identify this rule in the database
	 * @return string
	 */
	public function getType()
	{

	}

	/**
	 * Returns an array of required parameters with format name=>type
	 * @return array
	 */
	public function getParameters()
	{

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
		$params = $this->evalParameters($c);
		
		$page_data = $c->getAttribute('page_data');
		
		if (!is_array($page_data['exit_strategy_flags']))
		{
			$page_data['exit_strategy_flags'] = array();
		}
		
		foreach ($params as $key => $value)
		{
			if ($value instanceof ECash_CFE_IExpression)
			{
				$value = $value->evaluate($c);
			}
			$page_data['exit_strategy_flags'][$key] = $value;
		}
	}
}