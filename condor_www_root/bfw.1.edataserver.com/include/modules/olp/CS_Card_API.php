<?php
require_once("ecash_api.php");
require_once("prpc2/client.php");

/**
 * Customer Service Card API class allows us to talk to Cubis' API
 *
 * @author Rob Voss
 */
class csCardAPI
{
	/**
	 * @var MySQL_4 - $sqli
	 * @var str - $ssn
	 * @var str - $property_short
	 * @var str - $page
	 * @var str - $application_id
	 * @var str - $client_ip_address
	 * @var Prpc - $api_obj
	 * @var str - $session_id
	 */
	private $sqli;
	private $ssn;
	private $property_short;
	private $application_id;
	private $client_ip_address;
	private $api_obj;
	private $session_id;
	private $mode;
	private $cs_site_url;
	private $react_site_url;
	protected $hash_key = 'l04ns';

	/**
	 * Cubis Card API constructor
	 *
	 * @param arr - An array of the CS data from OLP
	 * @param str - The property short for the company the application is attached to
	 * @param MySQL_4 - The LDB sql object used to look up the card record
	 */
	public function __construct($app_data, $property_short, $sqli, $mode)
	{
		switch (strtolower($mode))
		{
			case 'dev':
				$this->cubis_api = 'prpc://api.cubisqa.tss/ccs/ccs.api.prpc.php';
				$this->cubis_user = "fcpdev";
				$this->cubis_pass = "fcpdev";
				break;

			case 'local':
			case 'rc':
				$this->cubis_api = 'https://rc.api.cubisfinancial.com/ccs/ccs.api.prpc.php';
				$this->cubis_user = "fcpdev";
				$this->cubis_pass = "fcpdev";
				break;
			
			case 'live':
				$this->cubis_api = 'https://api.cubisfinancial.com/ccs/ccs.api.prpc.php';
				$this->cubis_user = "fcpc_olp";
				$this->cubis_pass = "us4k1bo8";
				break;
		}
		
		$this->mode					= $mode;
		$this->property_short		= strtolower($property_short);
		$this->sqli					= $sqli;
		$this->client_ip_address  	= ($this->mode == 'LOCAL') ? '208.67.191.194' : $app_data['client_ip_address'];
		$this->application_id 		= $app_data['application_id'];
		$this->ssn					= $app_data['social_security_number'];
		$this->page					= $app_data['page'];
		

		switch (strtoupper($this->property_short))
		{
			default:
			case 'D1':
				$this->cs_site_url = 'fastcashpreferred.com';
				$this->react_site_url = '500fastcash.com';
				break;
		}
		
		$this->api_obj =  new Prpc_Client2($this->cubis_api);
	}

	/**
	* Forwards the user on to the Cubis CS site.
	*/
	public function redirectToCardCS()
	{
		// Start our session
		$this->startSession();

		// Get our card_id for this application from eCash
		$card_id = csCardAPI::getActiveCardID();

		// Generate a logged in url for 500FC site.
		$prefix = ($this->mode == 'RC') ? 'rc.' : '';
		$site_name = $prefix . $this->cs_site_url;

		$reckey = urlencode(base64_encode($this->application_id));
		$react_link = "http://{$prefix}{$this->react_site_url}/?force_new_session&page=ent_cs_confirm_start&reckey={$reckey}";

		// Get the URL to redirect to
		$response = $this->api_obj->Cardholder_Login(
			$this->session_id,
			$card_id,
			$this->client_ip_address, 
			array(
				'remote_fail_url' => $site_name,
				'add_funds_url' => $react_link
			),
			$this->page
		);

		if (!$response['success'])
		{
			// Pass this back to the front end and show error.
			$errors = "Unable to log you in at this time. Please contact customer service: ". $response['response_code_desc'];
			$page = "ent_cs_card_login";
			return array('page' => $page, 'errors' => $errors, 'login' => '');
		}

		// End our Session
		$this->endSession();

		$timestamp = time() + 10;
		$_SESSION['data']['redirect_expire'] = $timestamp;

		return $_SESSION['data']['redirect'] = $response['response_data']['url'];
	}

	/**
	 * Method to look up the card status for the application
	 *
	 * @return int - card_id for the application
	 */
	public function getCardStatus()
	{
		// Start our session
		$this->startSession();

		// Get our card_id for this application from eCash
		$card_id = csCardAPI::getActiveCardID();

		if ($card_id)
		{
			// Get the card status
			$response = $this->api_obj->Get_Card_Status($this->session_id, $card_id);
	
			// End our Session
			$this->endSession();
			
			if (!$response['success'])
			{
				return FALSE;
			}
			else 
			{
				if (strtolower($response['response_data']['card_status_name_short']) == 'active') return TRUE;
			}
		}
		else 
		{
			return FALSE;
		}
	}
	
	/**
	 * Start the session with Cubis API Server
	 */
	private function startSession()
	{
		$result_data = $this->api_obj->Session_Start($this->cubis_user, $this->cubis_pass);
		$this->session_id = $result_data['response_data']['session_id'];
	}

	/**
	 * End the session with Cubis API Server
	 */
	private function endSession()
	{
		$this->api_obj->Session_End($this->session_id);
	}

	/**
	 * Method to look up the card_id for the application
	 *
	 * @return int - card_id for the application
	 */
	private function getActiveCardID()
	{
		return get_Card_Id_By_SSN($this->ssn, $this->property_short, $this->sqli);
	}
}

?>
