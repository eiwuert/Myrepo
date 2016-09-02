<?php
/**
 * ECash Commercial specific inquiry client implementation
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 * @package WebService
 */
class ECash_WebService_InquiryClient extends WebServices_Client_InquiryClient
{
	/**
	 * Constructor for the ECash_WebService_InquiryClient object (Use NULL when possible for defaults)
	 *
	 * @param Applog $log
	 * @param string $url
	 * @param string $user
	 * @param string $pass
	 * @return void
	 */
	public function __construct(Applog $log = NULL, WebServices_WebService $webservice = NULL )
	{
		$log = is_null($log) ? ECash::getLog('inquiry_service') : $log;
		if(empty($webservice))
		{
			$url =  ECash::getconfig()->INQUIRY_SERVICE_URL;
			$user = ECash::getConfig()->APP_SERVICE_USER;
			$pass = ECash::getConfig()->APP_SERVICE_PASS;
			$inquiry_service =  new ECash_WebService(
				$log,
				$url,
				$user,
				$pass
			);
		}
		else
		{
			$inquiry_service = $webservice;
		}
		parent::__construct($log, $inquiry_service);
	}
}

?>
