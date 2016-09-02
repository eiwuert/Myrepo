<?php

/**
 * Class for connecting to a Rpc_Client_1 client.
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 *
 */
abstract class OLP_ECashClient_RPC1 extends OLP_ECashClient_Base
{
	/**
	 * Returns a Rpc_Client_1 of eCash's API.
	 *
	 * @return RPC_Client_1
	 */
	protected function getAPI()
	{
		$url = $this->getURL();
		$prpc = NULL;
		
		if (!empty($url))
		{
			$prpc = new Rpc_Client_1($url, $this->connection_timeout, $this->connection_timeout);
		}
		
		return $prpc;
	}
}