<?php

/**
 * Factory to produce a LenderAPI_IClient object.
 *
 * @package VendorAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class LenderAPI_Factory_Client extends OLPBlackbox_Factory_ModelFactory
{
	/**
	 * Storage for clients. 
	 * 
	 * Clients should be reusable, we'll keep track of them here and hand them
	 * back out when asked to produce them. Should clients ever NOT be reusable,
	 * the factory can simply make a new one and disregard this property.
	 * 
	 * @var array
	 */
	protected static $clients = array();
	
	/**
	 * Obtain a vendor post client.
	 * @param string $environment The environment this will be run in (LOCAL,
	 * RC, LIVE, etc)
	 * @param bool $enterprise Whether to assemble a client capable of handling
	 * enterprise vendors.
	 * @return LenderAPI_IClient
	 */
	public function getClient($environment = 'LOCAL', $post_type = LenderAPI_Generic_Client::POST_TYPE_STANDARD)
	{
		if (empty(self::$clients[$post_type])) 
		{
			self::$clients[$post_type] = new LenderAPI_Generic_Client(
				$environment,
				$this->getDbConnection(),
				$post_type
			);
		}
		
		return self::$clients[$post_type];
	}
}
?>
