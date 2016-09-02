<?php

/**
 * OLP Api - Call Me
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_ECashClient_CallMe extends OLP_ECashClient_PRPC2
{
	/**
	 * Returns a verbose description of the driver in human readable form.
	 *
	 * @return string
	 */
	public function getDriverDescription()
	{
		return 'API to submit Call Me requests.';
	}
	
	/**
	 * Submits the call me request.
	 *
	 * @param int $application_id
	 * @param string $call_time
	 * @param string $phone_number
	 * @return bool
	 */
	public function addCallMe($application_id, $call_time, $phone_number)
	{
		$result = FALSE;
		
		$api = $this->getAPI();
		if ($api)
		{
			$response = $api->addCallMe($application_id, $call_time, $phone_number);
			
			// According to the api, the only valid response is 'complete'
			if ($response === 'complete')
			{
				$result = TRUE;
			}
		}
		
		return $result;
	}
	
	/**
	 * The filename of the API.
	 *
	 * @return string
	 */
	protected function getURLFilename()
	{
		return "olp.php";
	}
	
}

?>
