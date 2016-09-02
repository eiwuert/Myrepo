<?php

/**
 * This is similar setup as LRRT/PWRD except it's being redirected to PartnerWeekly's own site.
 *
 * @author Brian Armstrong <brian.armstrong@sellingsource.com>
 * @see    GForge [#8576] Last Chance Cash Advance - SL4 redirect
 * @since  Tues 04 March 2008 08:50:00 AM PST
 */
class Vendor_Post_Impl_LCCA extends Abstract_Vendor_Post_Implementation
{

	/**
	 * @var array
	 */
	protected $rpc_params = array(
		'ALL' => array(
		),
		'lcca' => array(
			'ALL' => array(
				'redirect_url' => 'http://click.linkstattrack.com/zoneId/180927/?',
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
		$fields = array(
			'email' => strtolower($lead_data['data']['email_primary']),
			'firstname' => $lead_data['data']['name_first'],
			'lastname' => $lead_data['data']['name_last'],
			'address1' => $lead_data['data']['home_street'],
			'city' => $lead_data['data']['home_city'],
			'state' => $lead_data['data']['home_state'],
			'zip' => $lead_data['data']['home_zip'],
			'job_employer' => $lead_data['data']['employer_name'],
			'job_title' => '',
			'job_time' => $lead_data['data']['employer_length'],
			'ssn1' => $lead_data['data']['ssn_part_1'],
			'ssn2' => $lead_data['data']['ssn_part_2'],
			'ssn3' => $lead_data['data']['ssn_part_3'],
			'homephone1' => $lead_data['data']['ph_area_code'],
			'homephone2' => $lead_data['data']['ph_prefix'],
			'homephone3' => $lead_data['data']['ph_exchange'],
			'cellphone1' => $lead_data['data']['ph2_area_code'],
			'cellphone2' => $lead_data['data']['ph2_prefix'],
			'cellphone3' => $lead_data['data']['ph2_exchange'],
			'monthob' => $lead_data['data']['date_dob_m'],
			'dayob' => $lead_data['data']['date_dob_d'],
			'yearob' => $lead_data['data']['date_dob_y'],
			'bank_aba' => $lead_data['data']['bank_aba'],
			'bank_account_num' => $lead_data['data']['bank_account'],
			'residence_type' => '',
			'military' => (($lead_data['data']['military'] == 'TRUE')?1:0),
			'activechecking' => (($lead_data['data']['bank_account_type'] == 'CHECKING')?1:0),
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
		return "Vendor Post Implementation [LCCA]";
	}

}
