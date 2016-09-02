<?php

/**
 * Sets a blackbox config variable
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_CFE_Actions_SetBlackboxConfig extends ECash_CFE_Base_BaseAction
{
	/**
	 * Does nothing
	 * @return void
	 */
	public function getType() {}
	/**
	 * Does nothing
	 * @return void
	 */
	public function getParameters() {}
	
	/**
	 * Executes the action?
	 * @param ECash_CFE_IContext $c
	 * @return void
	 */
	public function execute(ECash_CFE_IContext $c)
	{
		$params = $this->evalParameters($c);
		
		unset($params['blackbox_config']->{$params['config_variable']});
		$params['blackbox_config']->{$params['config_variable']} = $params['value'];
	}
}