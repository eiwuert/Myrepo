<?php
/**
 *
 * @author stephan.soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_CFE_Actions_AddRequiredData extends ECash_CFE_Base_BaseAction implements ECash_CFE_IExpression
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
		if (!$page_data['required_data'] instanceof ArrayObject)
		{
			$page_data['required_data'] = new ArrayObject();
		}
		if (is_array($params))
		{
			foreach ($params as $param)
			{
				$page_data['required_data']->append($param);
			}
		}
		else
		{
			$page_data['required_data']->append($params);
		}
	}
}