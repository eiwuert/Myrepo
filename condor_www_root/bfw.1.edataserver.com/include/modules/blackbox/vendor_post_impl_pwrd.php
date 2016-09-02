<?php

/**
 * This is similar setup as LRRT except it's being redirected to PartnerWeekly's own site.
 *
 * @author Demin Yin <Demin.Yin@SellingSource.com>  
 * @see    GForge [#7159] - SL4 redirect to TakeABreakCash.com
 * @since  Mon 11 Feb 2008 09:05:12 AM PST
 */
class Vendor_Post_Impl_PWRD extends Abstract_Vendor_Post_Implementation
{
	
	/**
	 * @var array
	 */
	protected $rpc_params = array(
		'ALL' => array(	
		), 
		'pwrd1' => array(
			'ALL' => array(
				'redirect_url' => 'http://takeabreakcash.com/?',
			),
		),
	);
	
	/** 
	 * @var int
	 */
	const REDIRECT = 4;
	
	/**
	 * @var boolean
	 */
	protected $static_thankyou = FALSE;
	
	/**
	 * @var string
	 */
	private $query_string = '';
	
	/**
	 * Generate field values for post request.
	 *
	 * @param array &$lead_data User input data.
	 * @param array &$params Values from $this->rpc_params.
	 * @return array Field values for post request.
	 */
	public function Generate_Fields(&$lead_data, &$params)
	{
		$temp_array = array(
			
		);
		$temp_array['dob'] = $lead_data['data']['date_dob_m'] . '/' . $lead_data['data']['date_dob_d'] . '/' . $lead_data['data']['date_dob_y'];
		$temp_array['phone_area_code'] = substr($lead_data['data']['phone_home'], 0, 3);
		
		$fields = array(
			'name_first' => $lead_data['data']['name_first'],
			'name_last' => $lead_data['data']['name_last'],
			'address_1' => $lead_data['data']['home_street'] . ' ' . $lead_data['data']['home_unit'],
			'address_2' => '',
			'city' => $lead_data['data']['home_city'],
			'state' => $lead_data['data']['home_state'], 
			'zip' => $lead_data['data']['home_zip'], 
			'phone_home' => $lead_data['data']['phone_home'],
			'phone_mobile' => $lead_data['data']['phone_cell'],
			'phone_work' => $lead_data['data']['phone_work'],
			'dob' => $temp_array['dob'],
			'email' => strtolower($lead_data['data']['email_primary']),
			'client_ip' => $lead_data['data']['client_ip_address'],		
		
			'areacode' => $temp_array['phone_area_code'], // maybe this is useless now		
		);
		
		$this->query_string = http_build_query($fields);
		
		return '';
	}
	
	/**
	 * Generate result based on data received from the HTTP request.
	 * 
	 * @param string &$data_received Data received from the HTTP request.
	 * @param array &$cookies Cookies received from the HTTP request.
	 * @return Vendor_Post_Result Result generated.
	 */
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		$result->Set_Message("Accepted");
		$result->Set_Success(TRUE);
		$result->Set_Thank_You_Content(self::Thank_You_Content());
		$result->Set_Vendor_Decision('ACCEPTED');
		
		return $result;
	}
	
	/**
	 * Generate thank you content.
	 *
	 * @param string &$data_received Input data.
	 * @return string Thank You content.
	 */
	public function Thank_You_Content(&$data_received = NULL)
	{
		$url = $this->params['redirect_url'] . $this->query_string;
		return parent::Generic_Thank_You_Page($url, self::REDIRECT);
	}
	
	/**
	 * A PHP magic function.
	 *
	 * @see http://www.php.net/manual/en/language.oop5.magic.php Magic Methods
	 * @return string a string describing this class.
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [PWRD]";
	}

}
