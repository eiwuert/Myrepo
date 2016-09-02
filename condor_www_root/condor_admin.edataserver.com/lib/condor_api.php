<?php
	require_once('/virtualhosts/lib5/prpc/client.php');

	/**
	 * Class to return the PRPC client resource for the Condor API.
	 */
	class Condor_API
	{
		/**
		 * Returns the PRPC client object to the Condor API.
		 *
		 * @param Server $server
		 * @return object
		 */
		public static function Get_API_Object(Server $server)
		{
			switch(EXECUTION_MODE)
			{
				case 'LOCAL':
						$prpc_url = 'condor.4.edataserver.com.gambit.tss:8080/condor_api.php';
						break;
				case 'RC':
						$prpc_url = 'rc.condor.4.edataserver.com/condor_api.php';
						break;
				default:
				case 'LIVE':
						$prpc_url = 'condor.loanservicingcompany.com/condor_api.php';
						break;
			}
			$user = $server->api_auth;
			$prpc_url = "prpc://$user@$prpc_url";	
			$condor_api = new Prpc_Client($prpc_url);
			return $condor_api;
		}
	}
?>
