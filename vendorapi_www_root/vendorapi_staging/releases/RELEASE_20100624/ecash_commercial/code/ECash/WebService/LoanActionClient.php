<?php
/**
 * ECash Commercial specific application client implementation
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_LoanActionClient extends WebServices_Client_LoanActionClient
{
	/**
	 * Constructor for the ECash_WebService_LoanActionClient object (Use NULL when possible for defaults)
	 *
	 * @param Applog $log
	 * @param ECash_WebService $webservice
	 * @return void
	 */
	public function __construct(Applog $log = NULL, WebServices_BufferedWebService $webservice = NULL, WebServices_Cache $cache)
	{
		$log = is_null($log) ? ECash::getLog('application_service') : $log;
		if(empty($webservice))
		{
			$url =  ECash::getconfig()->LOAN_ACTION_SERVICE_URL;
			$user = ECash::getConfig()->APP_SERVICE_USER;
			$pass =  ECash::getConfig()->APP_SERVICE_PASS;
			$la_service = new ECash_BufferedWebService(
				$log,
				$url,
				$user,
				$pass,
				"loanaction",
				ECash::getconfig()->AGGREGATE_SERVICE_URL,
				new WebServices_Buffer($log)
			);
		}
		else
		{
			$la_service = $webservice;
		}
		parent::__construct($log, $la_service, ECash::getAgent()->getAgentId(), $cache);
	}
}

?>
