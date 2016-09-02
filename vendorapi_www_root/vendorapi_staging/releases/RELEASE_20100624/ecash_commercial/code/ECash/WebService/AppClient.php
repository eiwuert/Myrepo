<?php
/**
 * ECash Commercial specific application client implementation
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_AppClient extends WebServices_Client_AppClient
{
	/**
	 * Constructor for the ECash_WebService_AppClient object (Use NULL when possible for defaults)
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
			$url =  ECash::getconfig()->APP_SERVICE_URL;
			$user = ECash::getConfig()->APP_SERVICE_USER;
			$pass =  ECash::getConfig()->APP_SERVICE_PASS;
			$app_service = new ECash_BufferedWebService(
				$log,
				$url,
				$user,
				$pass,
				"application",
				ECash::getconfig()->AGGREGATE_SERVICE_URL,
				new WebServices_Buffer($log)
			);
		}
		else
		{
			$app_service = $webservice;
		}
		parent::__construct($log, $app_service, ECash::getAgent()->getAgentId(), $cache);
	}

	/**
	 * Performs a search of the app service for applications which meet the proper criteria
	 * returns an array of applications
	 * 
	 * @param array $request
	 * @param int $limit
	 * @return array
	 */
	public function applicationSearch($request, $limit)
	{
		/**
		 * @todo: Unsure of what we're getting back in the result or how we're supposed to transpose the status
		 */
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		
		$results = parent::applicationSearch($request, $limit);
//		echo "<pre>\n";
//		echo "Request: " . var_export($request, TRUE) . "\n";
//		echo "Limit: " . var_export($limit, TRUE) . "\n";
//		echo "Results: " . var_export($results, TRUE) . "\n";
//		echo "</pre>\n";
		
		if (is_array($results) && count($results) > 0)
		{
			foreach ($results as $i => $result)
			{
				$id = $asf->toId($result->application_status_name);
				$status = ECash::getFactory()->getModel('ApplicationStatusFlat', NULL);
				$status->loadBy(array('application_status_id' => $id));

				$results[$i]->application_status_id = $id;
				$results[$i]->application_status = $status->level0_name;
				$results[$i]->application_status_short = $status->level0; 
				
				$results[$i]->dnl = (isset($result->dnl) && $result->dnl) ? "1" : "0";
			}
		}
		
		return $results;
	}

}

?>
