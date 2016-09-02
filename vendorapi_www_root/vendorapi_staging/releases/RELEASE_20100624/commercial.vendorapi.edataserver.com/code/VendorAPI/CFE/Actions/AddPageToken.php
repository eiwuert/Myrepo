<?php
/**
 * Adds a one or more page tokens to the page_data object in the context
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_CFE_Actions_AddPageToken extends ECash_CFE_Base_BaseAction implements ECash_CFE_IExpression
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

		if (!is_array($page_data['tokens']))
		{
			$page_data['tokens'] = array();
		}

		foreach ($params as $key => $value)
		{
			if ($value instanceof ECash_CFE_IExpression)
			{
				$value = $value->evaluate($c);
			}
			$page_data['tokens'][$key] = $value;
		}
	}
}