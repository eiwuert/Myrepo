<?php

/**
 * Class to handle sending the page and getting the response from eCash
 * for the ESign Doc process.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_ECashClient_ESignDoc extends OLP_ECashClient_RPC1
{
	/**
	 * Returns a verbose description of the driver in human readable form.
	 *
	 * @return string
	 */
	public function getDriverDescription()
	{
		return 'API to process ESign Doc.';
	}
	
	/**
	 * The filename of the API.
	 *
	 * @return string
	 */
	protected function getURLFilename()
	{
		return 'esig.php';
	}
	
	/**
	 * What username should we use? ESign Doc uses a different username.
	 *
	 * @param string $mode
	 * @return string
	 */
	protected function getUsername($mode)
	{
		return 'esig_api';
	}
	
	/**
	 * And password?
	 *
	 * @param string $mode
	 * @return string
	 */
	protected function getPassword($mode)
	{
		return '28eDJsrc';
	}
	
	/**
	 * Return the documents page or whatever.
	 *
	 * @param int $application_id
	 * @param string $site_root
	 * @param array $request
	 * @param string $ip
	 * @return array
	 */
	public function getESignDocPage($application_id, $site_root, array $request, $ip)
	{
		$response = $this->getAPI()->getPage(
			$application_id,
			$site_root . '/?page=esign_doc_list',
			$site_root . '/?page=esign_doc_preview',
			'esig',
			$request,
			$ip
		);
		
		return $response;
	}
}

?>
