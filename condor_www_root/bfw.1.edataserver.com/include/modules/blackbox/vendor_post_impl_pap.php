<?php

/**
 * @desc A concrete implementation class for posting to eCheckTrac
 * 		 
 *
 */
class Vendor_Post_Impl_PAP extends Abstract_Vendor_Post_Implementation
{
	const REDIRECT = 1; // why is there here again?

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $tFalsehis->mode
			'ALL'     => Array(
			   // 'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/PAP',
				//'post_url' => 'http://rc.bfw.1.edataserver.com/header_ok.php',
				'headers' => array(
						'Content-Type: text/xml; charset=utf-8',
					),
				'username' => 'pwleads',
				'password' => 'password',
				'logintoken' => 'papsm',
            'storeid' => '103'
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				'post_url' => 'https://app.echecktrac.com/echecktrac/servlets/WebServiceServlet',

				),
			'RC'      => Array(
				'post_url' => 'https://app.echecktrac.com/echecktrac/servlets/WebServiceServlet',

				),
			'LIVE'    => Array(
				'post_url' => 'https://app.echecktrac.com/echecktrac/servlets/WebServiceServlet',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
		);

	protected $static_thankyou = FALSE;	
	
	/**
		I generate the proper paydate format
	*/
	private function generatePayFrequency($data)
	{
		switch(strtoupper($data))
		{
			case 'BI_WEEKLY':
			return 'Bi Weekly';
			
			case 'MONTHLY':
			return 'Monthly';
			
			default:
			return NULL;
		}
	}
		/**
		I generate the proper paydate format
	*/
	private function generatePayAmount($pay_span, $pay)
	{


		$paycheck = FALSE;

		switch (strtoupper($pay_span))
		{

			case 'WEEKLY':
                $paycheck = round(($pay * 12) / 52);
                break;
            case 'BI_WEEKLY':
                $paycheck = round(($pay * 12) / 26);
                break;
            case 'TWICE_MONTHLY':
                $paycheck = round($pay / 2);
                break;
            case 'MONTHLY':
                $paycheck = round($pay);
                break;
			default:
				$this->errors[] = "Invalid pay span, or monthly net pay is zero.";
		}

		return $paycheck;
   }

	
	public function Generate_Fields(&$lead_data, &$params)
	{


        
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
        

		$frequency = '';
		if (isset($lead_data['data']['paydate']['frequency']))
		{
			$frequency = $lead_data['data']['paydate']['frequency'];
		}
		if (!$frequency && isset($lead_data['data']['income_frequency']))
		{
			$frequency = $lead_data['data']['income_frequency'];
		}
		if (!$frequency && isset($lead_data['data']['paydate_model']['income_frequency']))
		{
			$frequency = $lead_data['data']['paydate_model']['income_frequency'];
		}

		if(strtoupper($lead_data['data']['income_type']) == "EMPLOYMENT")
		{
			// Employment benefits
			$income_type = 4;
		}
		else if(strtoupper($lead_data['data']['income_type']) == "BENEFITS")
		{
			// Disabilit Benefits may need to change this to SSI
			$income_type = 1;
		}
		else
		{
			$income_type = 4;
		}

$my_frequency = $this->generatePayFrequency($frequency);
$now_date = date("m/d/Y h:i:s");
$resType = (strtolower($lead_data['data']['residence_type']) != '') ? $lead_data['data']['residence_type'] : 'Rent';
$address1 = $lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'];
$direct_deposit = ($lead_data['data']['income_direct_deposit'] == 'TRUE') ? 'Y' : 'N';
$birthdate = date("m/d/Y", strtotime($lead_data['data']['dob']));
$first_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][0]));
$second_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][1]));
$third_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][2]));
$forth_pay_date = date("m/d/Y", strtotime($lead_data['data']['paydates'][3]));
$length_at_job = ($lead_data['data']['employer_length'] == 'TRUE') ? 'Y' : 'N';
$income_type = (strtolower($lead_data['data']['income_type']) == 'employment') ? 'Employed' : 'Benefits';
$calculated_income = $this->generatePayAmount($frequency, $lead_data['data']['income_monthly_net']); // TODO: Get the calculated income from OLP/Session Data (still need to do - want to test it out first - if we actually need the function there....
$fields = <<<END
<REQUEST> 
  <AUTHENTICATION username="{$params['username']}" password="{$params['password']}" 
  loginToken="{$params['logintoken']}" 
  ipAddress="{$lead_data['data']['ip_address']}"/> 
  <COMMAND name="createProspect" id="PW103-{$lead_data['application_id']}"> 
    <PROSPECT status="NEW" adSource="Partner Weekly" yearsAtAddress="1" 
	emailAddress="{$lead_data['data']['email_primary']}" locationCode="103" > 
      <PERSON firstName="{$lead_data['data']['name_first']}" 
      	lastName="{$lead_data['data']['name_last']}" middleName="" 
      	SSN="{$lead_data['data']['social_security_number']}" 
		DLState="{$lead_data['data']['state_issued_id']}" 
		DLNumber="{$lead_data['data']['state_id_number']}" 
		maritalStatus="" sex="" DOB="{$birthdate}"/> 
      <ADDRESS streetNo=" " streetDir="" streetName="{$address1}" streetType="" unit="" 
		zip="{$lead_data['data']['home_zip']}" /> 
      <PHONE type="home" number="{$lead_data['data']['phone_home']}"/> 
      <PHONE type="cell" number="{$lead_data['data']['phone_cell']}"/> 
      <EMPLOYER name="{$lead_data['data']['employer_name']}" 
      payAmount="{$calculated_income}" 
      payFrequency="{$my_frequency}" 
nextPayDate="{$first_pay_date}" yearsAtJob="" 
directDeposit="{$direct_deposit}" position="" supervisor=""  startTime="" endTime="" daysOff="">
        <PHONE type="work" number="{$lead_data['data']['phone_work']}" ext="{$lead_data['data']['work_ext']}"/>
        
      <ADDRESS streetNo=" " streetDir="" streetName=" " streetType="" unit="" 
		zip=" " />  
      </EMPLOYER> 
      <BANK name="{$lead_data['data']['bank_name']}" routingNumber="{$lead_data['data']['bank_aba']}" accountNumber="{$lead_data['data']['bank_account']}" 
accountType="{$lead_data['data']['bank_account_type']}" dateOpened="" branch=""/> 
      <CONTACT relationshipType="{$lead_data['data']['ref_01_relationship']}"> 
        <PERSON firstName="{$ref1FirstName}" lastName="{$ref1LastName}" middleName="" SSN=""/> 
        <PHONE type="home" number="{$lead_data['data']['ref_01_phone_home']}" />  
        
      <ADDRESS streetNo=" " streetDir="" streetName=" " streetType="" unit="" 
		zip=" " />  
      </CONTACT> 
      <CONTACT relationshipType="{$lead_data['data']['ref_01_relationship']}"> 
        <PERSON firstName="{$ref2FirstName}" lastName="{$ref2LastName}" middleName="" SSN=""/> 
        <PHONE type="home" number="{$lead_data['data']['ref_02_phone_home']}" />
        
      <ADDRESS streetNo=" " streetDir="" streetName=" " streetType="" unit="" 
		zip=" " />   
      </CONTACT> 
    </PROSPECT> 
	<LOAN amount="300.00" />
  </COMMAND> 
</REQUEST>
END;

         // No Trailing or leading SPACES!
         $fields = trim($fields); 
         return $fields; 


		//return $fields;
	}

	/** 
	 * @param string $data_received Data received from vendor's post_url.
	 * @param array $cookies cookie information from the HTTP response.
	 * @return an array which contains one result's information, or an array of results.
	 */
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result =  new Vendor_Post_Result();
		//  Was the record accepted? [LR]
      preg_match('/returncode="(\d+)"/i', $data_received, $d);
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
      }
      elseif($d[1] == 0)
      {
	      $result->Set_Message("Accepted");
	      $result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
			$result->Set_Vendor_Decision('ACCEPTED');
			if(!$this->Is_SOAP_Type())// added for Mantis #11073 [AuMa]
			{
				$result->Set_Next_Page( 'bb_vs_thanks' );	
			}
      }
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			$m = array();
			preg_match('/message="(\w+)"/i', $data_received, $m);
			$reason = substr($m[1],0,255);
			$result->Set_Vendor_Reason($reason);
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
		return "Vendor Post Implementation [Pay-Advance-Payday]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		// TODO: Set this information correctly...
		$content = '<p>Your information has been passed to <b>Payday Advance Plus, Inc.</b> and to expedite your request please fax or email the following:</p>
 <p>
Most recent pay stub
</p><p>
Most recent complete bank statement
</p><p>
Most recent telephone or utility bill (address page only)
</p><p>
A copy of your driver\'s license
</p><p>
A copy of a voided blank check
</p><p>
1-800-667-2207 phone
</p><p>
1-888-272-1533 fax
</p><p>
<a href="mailto:info@payday-advance-plus.com">info@payday-advance-plus.com</a>
</p><p>
Once your documents are received a representative will contact you to complete your request.  Please feel free to email or call us with any questions.</p>
 ';
					
			$_SESSION['bb_vs_thanks'] = $content;
		if(!$this->Is_SOAP_Type())
		{
			return $content;
		}
		else
		{
			switch(BFW_MODE)
			{
				case 'LIVE':
					$url = 'https://easycashcrew.com';
					break;
				case 'RC':
					$url = 'http://rc.easycashcrew.com';
					break;
				case 'LOCAL':
					$url = 'http://pcl.3.easycashcrew.com.ds82.tss';
			}
			
			$_SESSION['config']->bb_static_thanks = $content;
			return parent::Generic_Thank_You_Page($url . '/?page=bb_vs_thanks');
			//return parent::Generic_Thank_You_Page($url . '/?page=bb_static_thanks');
		}
	}
	


}
?>
