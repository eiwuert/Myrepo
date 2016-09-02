<?php
/**
 * Class for calling the query service
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_QueryClient extends WebServices_Client_QueryClient
{
	/**
	 * Constructor for the WebServices_Client_QueryClient object (Use NULL when possible for defaults)
	 *
	 * @param Applog $log
	 * @param ECash_WebService $webservice
	 * @return void
	 */
	public function __construct(Applog $log = NULL, WebServices_BufferedWebService $webservice = NULL, WebServices_Cache $cache = NULL)
	{
		$log = is_null($log) ? get_log('application_service') : $log;
		if(empty($webservice))
		{
			$url = is_null($url) ? LOAN_ACION_SERVICE_URL : $url;
			$user = is_null($user) ? $GLOBALS["APP_SERVICE_COMPANY_LOGINS"][ECash::getCompanyName()]['user'] : $user;
			$pass = is_null($pass) ? $GLOBALS["APP_SERVICE_COMPANY_LOGINS"][ECash::getCompanyName()]['pwd'] : $pass;
			$qc_service = new ECash_WebService(
				$log,
				$url,
				$user,
				$pass
			);
		}
		else
		{
			$qc_service = $webservice;
		}
		parent::__construct($log, $qc_service, $_SESSION['agent_id'], $cache);
	}


}
