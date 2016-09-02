<?php

/**
 * NO LONGER IN USE, reference implementation only
 * @desc A concrete implementation class to post to CM (Cyberclick Marketing)
 * 
 * @deprecated ********** entries for cm1, cm2, cm2a etc have been commented out of vendor_post.php ******
 * property shorts that are not listed in vendor_post.php will be picked up by post_impl_dynamic if the
 * prop short exists
 */
class Vendor_Post_Impl_CM1 extends Abstract_Vendor_Post_Implementation
{
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 2;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/CM',
				//'post_url' =>   'https://www.cyberclick-marketing.com/cyberclickapplicationacceptor/xmlacceptor.aspx'
				'transaction_type' => '12'
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://www.cyberclick-marketing.com/cyberclickapplicationacceptor/xmlacceptor.aspx',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'cm1'    => Array(
				'ALL'      => Array(
                    'source' => 'A022000'
					),
				'LOCAL'    => Array(
                    //'post_url' => 'http://206.55.116.170/ApplicationAcceptor/XmlAcceptor.aspx',
                    'transaction_type' => '1'
					),
				'RC'       => Array(
                    //'post_url' => 'http://206.55.116.170/ApplicationAcceptor/XmlAcceptor.aspx',
                    'transaction_type' => '1'
					),
				'LIVE'     => Array(
					),
				),
			'cm1a'    => Array(
				'ALL'      => Array(
                    'source' => 'A022001'
					),
				'LOCAL'    => Array(
                    //'post_url' => 'http://206.55.116.170/ApplicationAcceptor/XmlAcceptor.aspx',
                    'transaction_type' => '1'
					),
				'RC'       => Array(
                    //'post_url' => 'http://206.55.116.170/ApplicationAcceptor/XmlAcceptor.aspx',
                    'transaction_type' => '1'
					),
				'LIVE'     => Array(
					),
				),
				
				//pulled 'cm2', changed to use vendor_post_impl_dynamic GForge [#5963] [TF]
				
			'cm2a' => Array(
				
				'ALL' => Array(
                    'source' => 'A021001'
					),
				'LOCAL'    => Array(
                    //'post_url' => 'http://206.55.116.170/ApplicationAcceptor/XmlAcceptor.aspx',
                    'transaction_type' => '1'
					),
				
				'RC' => Array(
                    //'post_url' => 'http://206.55.116.170/ApplicationAcceptor/XmlAcceptor.aspx',
                    'transaction_type' => '1'
				),
				
			),
			
		);
	
	protected $static_thankyou = FALSE;
	
		
	public function Generate_Fields(&$lead_data, &$params)
	{

		
         //Paydate Freq
        if(isset($lead_data['data']['paydate_model']) && 
           isset($lead_data['data']['paydate_model']['income_frequency']) &&
           $lead_data['data']['paydate_model']['income_frequency'] != "")
        {
        	$freq = $lead_data['data']['paydate_model']['income_frequency'];
        }
        elseif(isset($lead_data['data']['income_frequency']) && 
           $lead_data['data']['income_frequency'] != "")
        {
            $freq = $lead_data['data']['income_frequency'];
        }
        elseif(isset($lead_data['data']['paydate']) && 
               isset($lead_data['data']['paydate']['frequency']) &&
               $lead_data['data']['paydate']['frequency'] != "")
        {
        	$freq = $lead_data['data']['paydate']['frequency'];
        }
        
        if(isset($freq))
        {
            //convert income frequency to requested format
            switch($freq)
            {
                case 'WEEKLY':
                    $income_frequency = 'Weekly';
                    break;
                case 'BIWEEKLY':
                case 'BI_WEEKLY':
                    $income_frequency = 'Bi Weekly';
                    break;
                case 'TWICE_MONTHLY':
                    $income_frequency = 'Twice Monthly';
                    break;
            }
//            $fields["payperiod"] = $income_frequency;
        }
        
        
        $ref1Array = split(" ", $lead_data['data']['ref_01_name_full']);
        $ref1FirstName = $ref1Array[0];
        		$ref1LastName = "";
        for($i = 1; $i < count($ref1Array); $i++){
        	if($i > 1){
        		$ref1LastName .= " ";
        	}
        	$ref1LastName .= $ref1Array[$i];
        }
        $ref2Array = split(" ", $lead_data['data']['ref_02_name_full']);
        $ref2FirstName = $ref2Array[0];
        		$ref2LastName = "";
        for($i = 1; $i < count($ref2Array); $i++){
        	if($i > 1){
        		$ref2LastName .= " ";
        	}
        	$ref2LastName .= $ref2Array[$i];
        }
        
$resType = (strtolower($lead_data['data']['residence_type']) != '') ? $lead_data['data']['residence_type'] : 'Rent';
$address1 = $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'];
$direct_deposit = ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'Y' : 'N';
$birthdate = date("m/d/Y", strtotime($lead_data['data']['dob']));
$first_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][0]));
$second_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][1]));
$length_at_job = ($lead_data['data']['employer_length'] == 'TRUE') ? 'Y' : 'N';
$best_time_to_call = ($lead_data['data']['best_call_time'] != '') ? $lead_data['data']['best_call_time'] : '' ;
$active_military =  ($lead_data['data']['military'] == 'TRUE') ? '1' : '0'; //Mantis 12099 - Add Military Changes [MJ]
$password = "password";
$username = "px5-=4N";
$source = $params['source'];
$fields = <<<END
<?xml version="1.0" encoding="utf-8"?>
		<APPLICATION>
<TRANSACTIONTYPE>{$params['transaction_type']}</TRANSACTIONTYPE>
<USERNAME>{$username}</USERNAME>
<PASSWORD>{$password}</PASSWORD>
<FIRSTNAME>{$lead_data['data']['name_first']}</FIRSTNAME>
<LASTNAME>{$lead_data['data']['name_last']}</LASTNAME>
<EMAILADDRESS>{$lead_data['data']['email_primary']}</EMAILADDRESS>
<MIDDLEINITIAL></MIDDLEINITIAL>
<SOURCE>{$source}</SOURCE>
<SS>{$lead_data['data']['social_security_number']}</SS>
<HOMEPHONE>{$lead_data['data']['phone_home']}</HOMEPHONE>
<RENTOWN>{$resType}</RENTOWN>
<MONTHSATRESIDENCE>0</MONTHSATRESIDENCE>
<FAXNO></FAXNO>
<ADDRESS1>{$address1}</ADDRESS1>
<CITY>{$lead_data['data']['home_city']}</CITY>
<STATE>{$lead_data['data']['home_state']}</STATE>
<ZIP>{$lead_data['data']['home_zip']}</ZIP>
<DLNUMBER>{$lead_data['data']['state_id_number']}</DLNUMBER>
<DLSTATE>{$lead_data['data']['state_issued_id']}</DLSTATE>
<WORKPHONE>{$lead_data['data']['phone_work']}</WORKPHONE>
<WORKEXT>{$lead_data['data']['ext_work']}</WORKEXT>
<OCCUPATION></OCCUPATION>
<EMPLOYER>{$lead_data['data']['employer_name']}</EMPLOYER>
<LENGTHATJOB>0</LENGTHATJOB>
<DEPARTMENT></DEPARTMENT>
<SHIFT>1</SHIFT>
<SUPERVISORSNAME></SUPERVISORSNAME>
<MONTHLYINCOME>{$lead_data['data']['income_monthly_net']}</MONTHLYINCOME>
<DIRECTDEPOSIT>{$direct_deposit}</DIRECTDEPOSIT>
<PAYPERIOD>{$income_frequency}</PAYPERIOD>
<PR1FIRSTNAME>{$ref1FirstName}</PR1FIRSTNAME>
<PR1LASTNAME>{$ref1LastName}</PR1LASTNAME>
<PR2FIRSTNAME>{$ref2FirstName}</PR2FIRSTNAME>
<PR2LASTNAME>{$ref2LastName}</PR2LASTNAME>
<PR1PHONE>{$lead_data['data']['ref_01_phone_home']}</PR1PHONE>
<PR2PHONE>{$lead_data['data']['ref_02_phone_home']}</PR2PHONE>
<PR1RELATIONSHIP>{$lead_data['data']['ref_01_relationship']}</PR1RELATIONSHIP>
<PR2RELATIONSHIP>{$lead_data['data']['ref_02_relationship']}</PR2RELATIONSHIP>
<BIRTHDATE>{$birthdate}</BIRTHDATE>
<BANK>{$lead_data['data']['bank_name']}</BANK>
<ACCOUNTTYPE>{$lead_data['data']['bank_account_type']}</ACCOUNTTYPE>
<ROUTINGNUMBER>{$lead_data['data']['bank_aba']}</ROUTINGNUMBER>
<ACCOUNTNUMBER>{$lead_data['data']['bank_account']}</ACCOUNTNUMBER>
<ADVANCEAMOUNT>300</ADVANCEAMOUNT>
<MONTHSATBANK>0</MONTHSATBANK>
<NEXTPAYDATE>{$first_pay_date}</NEXTPAYDATE>
<BULKEMAILOPTOUT>Y</BULKEMAILOPTOUT>
<SUPERVISORSPHONE></SUPERVISORSPHONE>
<ORIGINATINGDOMAIN>sellingsource.com</ORIGINATINGDOMAIN>
<OTHERPHONE>{$lead_data['data']['phone_cell']}</OTHERPHONE>
<BESTTIMETOCONTACT>{$best_time_to_call}</BESTTIMETOCONTACT>
<SECONDPAYDATE>{$second_pay_date}</SECONDPAYDATE>
<ACTIVEMILITARY>{$active_military}</ACTIVEMILITARY>
<SUBID>{$lead_data['config']->promo_id}</SUBID>
		</APPLICATION>
END;
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
		elseif (preg_match ('/<ERROR>0<\/ERROR>/i', $data_received, $d))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			preg_match('/<Message>([^<]+)<\/Message>/i', $data_received, $m);
			
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			$result->Set_Vendor_Reason($m[1]);
		}
		return($result);
	}

//	Uncomment the next line to use HTTP GET instead of POST
//	public static function Get_Post_Type() {return Http_Client::HTTP_GET;}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [CM]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		preg_match('/<DYNAMICURL>([^<]+)<\/DYNAMICURL>/i', $data_received, $m);
		$url = trim($m[1]);
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		return($content);
	}
	
}
