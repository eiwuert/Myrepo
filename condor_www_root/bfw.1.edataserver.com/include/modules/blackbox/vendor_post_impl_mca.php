<?php

/**
 * @desc A concrete implementation class for posting to Magnum Cash Advance
 */
class Vendor_Post_Impl_MCA extends Abstract_Vendor_Post_Implementation
{
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'test' => TRUE,
			    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/MCA',
				//'post_url' => 'https://www.magnumcashadvance.com/LeadsServiceTest.asp',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'test' => FALSE,
				'post_url' => 'https://www.magnumcashadvance.com/LeadsService.asp',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
		);
	
	protected $static_thankyou = FALSE;	
	
	public function Generate_Fields(&$lead_data, &$params)
	{

		$payperiod = array(
			'WEEKLY' => '4',
			'BI_WEEKLY' => '3',
			'TWICE_MONTHLY' => '2',
			'MONTHLY' => '1',
		);

		$frequency = '';
		if (isset($lead_data['data']['paydate']['frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate']['frequency']];
		}
		if (!$frequency && isset($lead_data['data']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['income_frequency']];
		}
		if (!$frequency && isset($lead_data['data']['paydate_model']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate_model']['income_frequency']];
		}

        //calculate last paycheck based on frequency
        $monthly = $lead_data['data']['income_monthly_net'];
        $last_paycheck = 0;
        
        switch($frequency) {
            case 4:
                $last_paycheck = round(($monthly*12)/52);
                break;
            case 3:
                $last_paycheck = round(($monthly*12)/26);
                break;
            case 2:
                $last_paycheck = round($monthly/2);
                break;
            case 1:
                $last_paycheck = round($monthly);
                break;
        }

		//Make sure the month and day have leading zeroes
		$dob = implode('-', array($lead_data['data']['date_dob_y'],
								sprintf('%02d', $lead_data['data']['date_dob_m']),
								sprintf('%02d', $lead_data['data']['date_dob_d'])));

		$fields =
"<?xml version=\"1.0\"?>
<lead-delivery>
    <vendor-id>13</vendor-id>
    <mode>combined/full-data</mode>
    <ssn>".$lead_data['data']['ssn_part_1'].$lead_data['data']['ssn_part_2'].$lead_data['data']['ssn_part_3']."</ssn>
    <first-name>{$lead_data['data']['name_first']}</first-name>
    <middle-initial>{$lead_data['data']['name_middle']}</middle-initial>
    <last-name>{$lead_data['data']['name_last']}</last-name>
    <address>".$lead_data['data']['home_street'].(strlen(trim($lead_data['data']['home_unit']))?" {$lead_data['data']['home_unit']}":"")."</address>
    <city>{$lead_data['data']['home_city']}</city>
    <state>{$lead_data['data']['home_state']}</state>
    <zip>{$lead_data['data']['home_zip']}</zip>
    <email>{$lead_data['data']['email_primary']}</email>
    <dl-number>{$lead_data['data']['state_id_number']}</dl-number>
    <home-phone>{$lead_data['data']['phone_home']}</home-phone>
    <work-phone>{$lead_data['data']['phone_work']}</work-phone>
    <work-extension>{$lead_data['data']['ext_work']}</work-extension>
    <cell-phone>{$lead_data['data']['phone_cell']}</cell-phone>
    <home-fax>{$lead_data['data']['phone_fax']}</home-fax>
    <birth-date>{$dob}</birth-date>
    <employer-name>{$lead_data['data']['employer_name']}</employer-name>
    <employer-phone>{$lead_data['data']['phone_work']}</employer-phone>
    <income-direct-deposit>".($lead_data['data']['income_direct_deposit']=='TRUE'?'1':'0')."</income-direct-deposit>
    <income-last-net-pay>{$last_paycheck}</income-last-net-pay>
    <income-last-pay-date>{$lead_data['data']['paydates'][0]}</income-last-pay-date>
    <income-pay-frequency>{$frequency}</income-pay-frequency>
    <income-source>".(trim($lead_data['data']['income_type'])=='EMPLOYMENT'?'0':'1')."</income-source>
    <bank-routing>{$lead_data['data']['bank_aba']}</bank-routing>
    <bank-account-number>{$lead_data['data']['bank_account']}</bank-account-number>
    <bank-account-type>".(trim($lead_data['data']['bank_account_type'])=='CHECKING'?'27':'37')."</bank-account-type>
    <reference1-name>{$lead_data['data']['ref_01_name_full']}</reference1-name>
    <reference1-phone>{$lead_data['data']['ref_01_phone_home']}</reference1-phone>
    <reference2-name>{$lead_data['data']['ref_02_name_full']}</reference2-name>
    <reference2-phone>{$lead_data['data']['ref_02_phone_home']}</reference2-phone>
</lead-delivery>";

		return $fields;
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif (preg_match("/<response-value>Accepted<\/response-value>/i", strtolower(str_replace(" ", "", $data_received))))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
		}

		return $result;
	}

//	Uncomment the next line to use HTTP GET instead of POST
//	public static function Get_Post_Type() {return Http_Client::HTTP_GET;}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [Magnum Cash Advance]";
	}
	
	public function Thank_You_Content(&$data_received)
	{

		$response = explode( ',', $data_received );
		$id = explode( '<BR>', $response[2] );

        if (preg_match('/<link>(.*)<\/link>/i', $data_received, $m));
        {
			$url = explode(' ', $m[1]);
			$url = $url[0];

			$content = parent::Generic_Thank_You_Page($url);
        }
		$content = parent::Generic_Thank_You_Page($url);
		return($content);
	}
	
}
