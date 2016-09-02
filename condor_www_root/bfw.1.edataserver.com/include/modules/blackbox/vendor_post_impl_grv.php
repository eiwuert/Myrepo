<?php

/**
 * @desc A concrete implementation class for posting to Geneva-Roth Ventures
 * @author OLP Developers <no-reply@SellingSource.com>
 */
class Vendor_Post_Impl_GRV extends Abstract_Vendor_Post_Implementation
{
	/**
	 * @var array
	 */
	protected $rpc_params  = array(
		// Params which will be passed regardless of $this->mode
		'ALL' => array(
			'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/GRV',
		),
		// Specific cases varying with $this->mode, having higher priority than ALL.
		'LOCAL' => array(	
		),
		'RC' => array(	
		),
		'LIVE' => array(	
			'post_url' => 'https://lead.genevakc.com/lead/pw.aspx',
		),
		
		// The next entries are params specific to property shorts.
		// They have higher priority than all of the previous entries
		'grv' => array(
			'ALL' => array(
				'store_id' => '37',
			),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL' => array(	
			),
			'RC' => array(	
			),
			'LIVE' => array(	
				'post_url' => 'https://lead.genevakc.com/lead/pw.aspx',
			),
		),

		'grv_t1' => array(
			'ALL' => array(
				'store_id' => '75',
			),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL' => array(	
			),
			'RC' => array(	
			),
			'LIVE' => array(	
				'post_url'  => 'https://lead.genevakc.com/pw3.aspx',
			),
		),

		'grv2' => array(
			'ALL' => array(
				'store_id' => '74',
			),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL' => array(	
			),
			'RC' => array(	
			),
			'LIVE' => array(	
				'post_url' => 'https://lead.genevakc.com/pw2.aspx',
			),
		),

		'grv_t2' => array( // tier 1 GForge #7526 [DY]
			'ALL' => array(
				'store_id' => '90',
			),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL' => array(	
			),
			'RC' => array(	
			),
			'LIVE' => array(	
				'post_url' => 'https://lead.genevakc.com/pw4.aspx',
			),
		),
		
		'grv3' => array( // tier 2 GForge #7526 [DY] 
			'ALL' => array(
				'store_id' => '91',
			),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL' => array(	
			),
			'RC' => array(	
			),
			'LIVE' => array(	
				'post_url' => 'https://lead.genevakc.com/pw5.aspx',
			),
		),

		'grv4' => array( // tier 2 GForge #9473 [AuMa]
			'ALL' => array(
				'store_id' => '98',
			),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL' => array(	
			),
			'RC' => array(	
			),
			'LIVE' => array(	
				'post_url' => 'https://lead.genevakc.com/pw6.aspx',
			),
		),
	);

	/**
	 * @var boolean
	 */
	protected $static_thankyou = FALSE;
	
	/**
	 * Generate fields.
	 * 
	 * @param array &$lead_data Input data.
	 * @param array &$params Parameters merged from $this->rpc_params.
	 * @return string
	 */
	public function Generate_Fields(&$lead_data, &$params)
	{
		$bank_account_type = ($lead_data['data']['bank_account_type']=="SAVINGS") ? "S" : "C";
		$employ_type = ($lead_data['data']['income_type'] == "EMPLOYMENT") ? "P" : "G";
		$dd = ($lead_data['data']['income_direct_deposit'] == "TRUE") ? "D" : "P";
		switch ($lead_data['data']['residence_type'])
		{
			case "RENT":
				$residence = "R"; break;
			case "OWN":
				$residence = "O"; break;
			default:
				$residence = "P";
		}
		
		//Paydate Freq
		if (isset($lead_data['data']['income_frequency']) && 
			$lead_data['data']['income_frequency'] != "")
		{
			$freq = $lead_data['data']['income_frequency'];
		}
		elseif (isset($lead_data['data']['paydate']) && 
			isset($lead_data['data']['paydate']['frequency']) &&
			$lead_data['data']['paydate']['frequency'] != "")
		{
			$freq = $lead_data['data']['paydate']['frequency'];
		}
		elseif (isset($lead_data['data']['paydate_model']) && 
			isset($lead_data['data']['paydate_model']['income_frequency']) &&
			$lead_data['data']['paydate_model']['income_frequency'] != "")
		{
			$freq = $lead_data['data']['paydate_model']['income_frequency'];
		}

		$pay_freq = "";
		switch ($freq)
		{
			case "WEEKLY":
				$pay_freq = "W"; break;
			case "BI_WEEKLY":
				$pay_freq = "B"; break;
			case "TWICE_MONTHLY":
				$pay_freq = "S"; break;
			case "MONTHLY":
				$pay_freq = "M";
		}


		$fields =
'<?xml version="1.0" encoding="ISO-8859-1" ?>
<QUICKAPPLICATION>
  <LOGIN>
    <STROREID>'.$params['store_id'].'</STROREID>
    <USERID>PD5433</USERID>
    <PASSWORD>password</PASSWORD>
  </LOGIN>
  <CUSTOMER>
    <SSN>'.$lead_data['data']['ssn_part_1'].$lead_data['data']['ssn_part_2'].$lead_data['data']['ssn_part_3'].'</SSN>
    <CUSTFNAME>'.$lead_data['data']['name_first'].'</CUSTFNAME>
    <CUSTMNAME>'.$lead_data['data']['name_middle'].'</CUSTMNAME>
    <CUSTLNAME>'.$lead_data['data']['name_last'].'</CUSTLNAME>
    <CUSTADD1>'.$lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'].'</CUSTADD1>
    <CUSTADD2 />
    <CUSTCITY>'.$lead_data['data']['home_city'].'</CUSTCITY>
    <CUSTSTATE>'.$lead_data['data']['home_state'].'</CUSTSTATE>
    <CUSTZIP>'.$lead_data['data']['home_zip'].'</CUSTZIP>
    <CUSTHOMEPHONE>'.$lead_data['data']['phone_home'].'</CUSTHOMEPHONE>
    <CUSTMOBILEPHONE>'.$lead_data['data']['phone_cell'].'</CUSTMOBILEPHONE>
    <CUSTFAX />
    <CUSTEMAIL>'.$lead_data['data']['email_primary'].'</CUSTEMAIL>
    <CUSTDOB>'.$lead_data['data']['date_dob_y'].'-'.sprintf("%02d", $lead_data['data']['date_dob_m']).'-'.sprintf("%02d", $lead_data['data']['date_dob_d']).'</CUSTDOB>
    <YRSATCURRADD>0</YRSATCURRADD>
    <MNTHSATCURRADD>3</MNTHSATCURRADD>
    <HOMESTATUS>'. $residence . '</HOMESTATUS>
    <GROSSINCOME />
    <NETINCOME>'.$lead_data['data']['income_monthly_net'].'</NETINCOME>
    <MKTCODES>'.$lead_data['config']->promo_id.'</MKTCODES>
  </CUSTOMER>
  <BANK>
    <CUSTBANKNAME>'.$lead_data['data']['bank_name'].'</CUSTBANKNAME>
    <ROUTINGNUMBER>'.$lead_data['data']['bank_aba'].'</ROUTINGNUMBER>
    <ACCOUNTNUMBER>'.$lead_data['data']['bank_account'].'</ACCOUNTNUMBER>
    <CUSTACCTTYPE>' . $bank_account_type . '</CUSTACCTTYPE>
    <CUSTBANKADD1 />
    <CUSTBANKADD2 />
    <CUSTBANKCITY />
    <CUSTBANKSTATE />
    <CUSTBANKZIP />
    <CUSTBANKPHONE />
    <CUSTBANKFAX />
  </BANK>
  <EMPLOYER>
    <TYPEOFINCOME>' . $employ_type . '</TYPEOFINCOME>
    <EMPNAME>'.$lead_data['data']['employer_name'].'</EMPNAME>
    <EMPADD1 />
    <EMPADD2 />
    <EMPCITY />
    <EMPSTATE />
    <EMPZIP />
    <EMPPHONE>'.$lead_data['data']['phone_work'].'</EMPPHONE>
    <EMPEXT />
    <EMPFAX />
    <CONTACTNAME />
    <CONTACTPHONE />
    <CONTACTEXT />
    <CONTACTFAX />
    <EMPLTYPE />
    <JOBTITLE />
    <WORKSHIFT />
    <TYPEOFPAYROLL>' . $dd . '</TYPEOFPAYROLL>
    <PERIODICITY>' . $pay_freq . '</PERIODICITY>
    <NEXTPAYDATE>' . $lead_data['data']['paydates'][0] . '</NEXTPAYDATE>
    <SECONDPAYDATE>' . $lead_data['data']['paydates'][1] . '</SECONDPAYDATE>
  </EMPLOYER>
  <APPLICATION>
    <MERCHANTREFID>' . $_SESSION['application_id'] . '</MERCHANTREFID>
  </APPLICATION>
</QUICKAPPLICATION>';

		return $fields;
	}
	
	/**
	 * Generate a result object based on HTTP response received.
	 * 
	 * @param string &$data_received HTTP response body received.
	 * @param array &$cookies HTTP cookie received.
	 * @return Vendor_Post_Result Result object.
	 */
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif (preg_match("/<ISSUCCESS>1<\/ISSUCCESS>/", strtoupper($data_received)))
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
	 * String representation of this class/object. 
	 * 
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 * @return string String representation of this class/object.
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [Geneva-Roth Ventures]";
	}
	
	/**
	 * Return Thank You content based on input data.
	 * 
	 * @param string &$data_received Input data.
	 * @return string Thank You content.
	 */
	public function Thank_You_Content(&$data_received)
	{
		$url = 'https://agreement.loanpointusa.com/agreement.aspx?MERCHANTREFID=' . $_SESSION['application_id'];
		$content = parent::Generic_Thank_You_Page($url);
		return($content);
	}
	
}
