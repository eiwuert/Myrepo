<?php

/**
 * Class for connecting to a PRPC2 client.
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 *
 */
abstract class OLP_ECashClient_PRPC2 extends OLP_ECashClient_Base
{
	/**
	 * Returns a Prpc_Client2 of eCash's API.
	 *
	 * @return RPC_Client_1
	 */
	protected function getAPI()
	{
		$url = $this->getURL();
		$prpc = NULL;
		
		if (!empty($url))
		{
			if (!class_exists('Prpc_Client2'))
			{
				require_once 'prpc2/client.php';
			}
			
			$prpc = new Prpc_Client2($url);
		}
		
		return $prpc;
	}
}