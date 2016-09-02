<?php

/**
 * @desc A concrete implementation class for posting to Global Rebates 
 */
class Vendor_Post_Impl_GR extends Abstract_Vendor_Post_Implementation
{
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'test' => TRUE,
				//'post_url' => 'https://www.nowaitrebates.com/poster/leadshorttest.php',
				//'post_url' => 'https://www.nowaitrebates.com/poster/leadshort.php',
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/GR'
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'http://216.55.169.220/~application/poster/leadshort.php',
				'test' => FALSE,
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'gr2'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
			),
			
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{

		$payperiod = array(
			'WEEKLY' => 'weekly',
			'BI_WEEKLY' => 'biweekly',
			'TWICE_MONTHLY' => 'twice-monthly',
			'MONTHLY' => 'monthly',
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

		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
		
		$fields =
'<?xml version="1.0" encoding="ISO-8859-1" ?>
<LEADSHORT>
<LOGIN>
  <USERID>ssource</USERID>
  <PASSWORD>password</PASSWORD>
</LOGIN>
<CUSTOMER>
  <FIRSTNAME>'.$lead_data['data']['name_first'].'</FIRSTNAME>
  <MIDDLEINITIAL>'.$lead_data['data']['name_middle'].'</MIDDLEINITIAL>
  <LASTNAME>'.$lead_data['data']['name_last'].'</LASTNAME>
  <HOMESTREETONE>'.$lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'].'</HOMESTREETONE>
  <HOMESTREETTWO></HOMESTREETTWO>
  <HOMECITY>'.$lead_data['data']['home_city'].'</HOMECITY>
  <HOMESTATE>'.$lead_data['data']['home_state'].'</HOMESTATE>
  <HOMEZIP>'.$lead_data['data']['home_zip'].'</HOMEZIP>
  <HOMEPHONE>'.$lead_data['data']['phone_home'].'</HOMEPHONE>
  <WORKPHONE>'.$lead_data['data']['phone_work'].'</WORKPHONE>
  <CELLPHONE>'.$lead_data['data']['phone_cell'].'</CELLPHONE>
  <EMAIL>'.$lead_data['data']['email_primary'].'</EMAIL>
  <DRIVERSLICENSENUM>'.$lead_data['data']['state_id_number'].'</DRIVERSLICENSENUM>
  <DRIVERSLICENSESTATE>'.$issued_state.'</DRIVERSLICENSESTATE>
  <BIRTHDATE>'.$lead_data['data']['date_dob_y'].'/'.sprintf("%02d", $lead_data['data']['date_dob_m']).'/'.sprintf("%02d", $lead_data['data']['date_dob_d']).'</BIRTHDATE>
  <SSN>'.$lead_data['data']['ssn_part_1'].$lead_data['data']['ssn_part_2'].$lead_data['data']['ssn_part_3'].'</SSN>
  <EMPLOYER>'.$lead_data['data']['employer_name'].'</EMPLOYER>
  <JOBTITLE></JOBTITLE>
  <WORKPHONE>'.$lead_data['data']['phone_work'].'</WORKPHONE>
  <PAYPERIOD>'.$frequency.'</PAYPERIOD>
  <MONTHLYINCOME>'.$lead_data['data']['income_monthly_net'].'</MONTHLYINCOME>
  <INCOMESOURCE>Job</INCOMESOURCE>
  <ACCOUNTLENGTH>3 Months</ACCOUNTLENGTH>
  <DIRECTDEPOSIT>'.($lead_data['data']['income_direct_deposit']=='TRUE'?'t':'f').'</DIRECTDEPOSIT>
  <TIMECALL>'.$lead_data['data']['best_call_time'].'</TIMECALL>
  <DATECREATED>'.date('Y/m/d').'</DATECREATED>
  <BESTPHONE></BESTPHONE>
  <NEXTPAYDATEONE>'.str_replace("-", "/", $lead_data['data']['paydates'][0]).'</NEXTPAYDATEONE>
  <NEXTPAYDATETWO>'.str_replace("-", "/", $lead_data['data']['paydates'][1]).'</NEXTPAYDATETWO>
  <BANKNAME>'.$lead_data['data']['bank_name'].'</BANKNAME>
  <ROUTINGNUMBER>'.$lead_data['data']['bank_aba'].'</ROUTINGNUMBER>
  <ACCOUNTNUMBER>'.$lead_data['data']['bank_account'].'</ACCOUNTNUMBER>
  <NOFAX></NOFAX>
  <IPADDRESS>'.$lead_data['data']['client_ip_address'].'</IPADDRESS>
</CUSTOMER>
</LEADSHORT>';

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
		elseif (preg_match("/<error>0<\/error>/", strtolower(str_replace(" ", "", $data_received))))
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
			
			$m = array();
			preg_match('/<MESSAGE>(.*)<\/MESSAGE>/i', $data_received, $m);
			$reason = substr($m[1],0,255);
			$result->Set_Vendor_Reason($reason);
		}

		return $result;
	}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [GlobalRebates]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		if(preg_match('/<FORWARDURL>(.*)<\/FORWARDURL>/is', $data_received, $m)) {
			$url=trim($m[1]);
		}
		//$url = 'https://www.nowaitrebates.com/ls_validate/thankyou.html';
		$content = parent::Generic_Thank_You_Page($url);
		return($content);
	}
	
}
