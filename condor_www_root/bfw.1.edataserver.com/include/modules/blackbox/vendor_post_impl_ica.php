<?php
/**
 * @desc A concrete implementation class for posting to ICA
 */
class Vendor_Post_Impl_ICA extends Abstract_Vendor_Post_Implementation
{
	const REDIRECT = 1;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/ICA',
				'headers' => array(
					'Content-Type: text/xml; charset=utf-8',
					'Accept: text/xml, multipart/related, text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2',
					'SOAPAction: ""'
				),
				'loan_amount' => 300,
				'accountid' => 'test-account',
				'username' => 'testLeadVendor',
				'password' => 'testLeadVendorPassword',
			),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
			),
			'RC'      => Array(
			),
			'LIVE'    => Array(
			),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'ica'    => Array(
				'ALL'      => Array(
					'post_url' => 'http://fortress-soap-api-beta.cmaxdev.com:8080/fortress-soap-api',
				),
				'LOCAL'    => Array(
					'accountid' => 'CMXE185',
					'post_url' => 'http://cmaxasp3.williamway.cmaxinc.com:9191/fortress-soap-api/LoanLeadsPurchasingServiceBean',
					'username' => 'PartnerWeekly',
					'password' => 'password'
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'post_url' => 'http://zuriel.williamway.cmaxinc.com:9191/fortress-soap-api/LoanLeadsPurchasingServiceBean',
					'accountid' => 'CMXE185',
					'username' => 'PartnerWeekly',
					'password' => 'password'
				),
			),
		);	

	protected $static_thankyou = FALSE;				

	public function Generate_Fields(&$lead_data, &$params)
	{
		$prim_email = split('@',$lead_data['data']['email_primary'],2);
		
		$home_areacode = substr($lead_data['data']['phone_home'],0,3);
		$home_phone = substr($lead_data['data']['phone_home'],3);
		
		$ref[0]['areacode'] = substr($lead_data['data']['ref_01_phone_home'],0,3);
		$ref[0]['phone'] = substr($lead_data['data']['ref_01_phone_home'],3);
		$ref[1]['areacode'] = substr($lead_data['data']['ref_02_phone_home'],0,3);
		$ref[1]['phone'] = substr($lead_data['data']['ref_02_phone_home'],3);
		list($m1,$d1,$y1) = explode('/',$_SESSION['data']['dob']);
		$dob = "$y1-$m1-$d1";
		$dom = new DOMDocument('1.0');
		$root_elm = $dom->createElement('S:Envelope');
		$root_elm->setAttribute('xmlns:S','http://schemas.xmlsoap.org/soap/envelope/');
		$dom->appendChild($root_elm);
		$body = $root_elm->appendChild($dom->createElement('S:Body'));
		$ns2 = $body->appendChild($dom->createElement('ns2:processPurchaseProposal'));
		$ns2->setAttribute('xmlns:ns2','http://fortress.cmaxinc.com/soap-api/leads-purchasing-service');

		// our authorization info
		$auth = $ns2->appendChild($dom->createElement('authorization'));
		$auth->appendChild($dom->createElement('customerAccountId',$params['accountid']));
		$auth->appendChild($dom->createElement('password',$params['password']));
		$auth->appendChild($dom->createElement('vendorId',$params['username']));


		$loan_app = $ns2->appendChild($dom->createElement('loanApplication'));

		$loan_app->appendChild($dom->createElement('amount',$params['loan_amount']));
		$loan_app->appendChild($dom->createElement('branch',NULL));


		//cust info nodes
		$custInfo =  $dom->createElement('custInfo');
		$loan_app->appendChild($custInfo);

		//bank account
		$bankAccount = $dom->createElement('bankAccount');
		$custInfo->appendChild($bankAccount);
		$bankAccount->appendChild($dom->createElement('bankAccountNumber',$lead_data['data']['bank_account']));
		$bankRoutingNumber = $dom->createElement('bankRoutingNumber');
		$bankAccount->appendChild($bankRoutingNumber);
			$bankRoutingNumber->appendChild($dom->createElement('number',$lead_data['data']['bank_aba']));
		$custInfo->appendChild($dom->createElement('bankName',substr($lead_data['data']['bank_name'],0,30)));
		$custInfo->appendChild($dom->createElement('dateOfBirth',$dob));
		$custInfo->appendChild($dom->createElement('driversLicense',$lead_data['data']['state_id_number']));

		//email
		$email = $dom->createElement('email');
		$custInfo->appendChild($email);

		$email->appendChild($dom->createElement('host',$prim_email[1]));
		$email->appendChild($dom->createElement('user',$prim_email[0]));

		// Employer name
		$custInfo->appendChild($dom->createElement('employerName',substr($lead_data['data']['employer_name'],0,30)));
		
		//Home address
		$homeAddress = $custInfo->appendChild($dom->createElement('homeAddress'));
		$homeAddress->appendChild($dom->createElement('addressLine1',$lead_data['data']['home_street']));
		$homeAddress->appendChild($dom->createElement('addressLine2',$lead_data['data']['home_unit']));
		$homeAddress->appendChild($dom->createElement('city',$lead_data['data']['home_city']));
		$zipcode = $homeAddress->appendChild($dom->createElement('postalCode'));
		$zipcode->appendChild($dom->createElement('code',$lead_data['data']['home_zip']));
		$homeAddress->appendChild($dom->createElement('state',strtoupper($lead_data['data']['home_state'])));

		// Home Phone
		$homePhoneNumber = $custInfo->appendChild($dom->createElement('homePhoneNumber'));
		$homePhoneNumber->appendChild($dom->createElement('countryCode',1));
		$homePhoneNumber->appendChild($dom->createElement('areaCode',$home_areacode));
		$homePhoneNumber->appendChild($dom->createElement('subscriberNumber',$home_phone));

		// Income source
		$income = $custInfo->appendChild($dom->createElement('income-source'));
		$income->appendChild($dom->createElement('amount',$lead_data['data']['income_monthly_net']));
		$dd = ($lead_data['data']['income_direct_deposit']) ? 1 : 0 ;
		$income->appendChild($dom->createElement('doesDirectDeposit',$dd));
		

		$joint = $custInfo->appendChild($dom->createElement('jointHolder'));

		// Name
		$name = $custInfo->appendChild($dom->createElement('name'));
		$name->appendChild($dom->createElement('firstname',$lead_data['data']['name_first']));
		$name->appendChild($dom->createElement('lastname',$lead_data['data']['name_last']));

		// Reference #1
		$personal_ref1 = $custInfo->appendChild($dom->createElement('personal-reference'));

		$per_ref_1_name = $personal_ref1->appendChild($dom->createElement('name'));
		list($first,$last) = split(" ", $lead_data['data']['ref_01_name_full']);
		$per_ref_1_name->appendChild($dom->createElement('firstname',$first));
		$per_ref_1_name->appendChild($dom->createElement('lastname',$last));
		$ref_1_phone = $personal_ref1->appendChild($dom->createElement('phoneNumber'));
		$ref_1_phone->appendChild($dom->createElement('countryCode',1));
		$ref_1_phone->appendChild($dom->createElement('areaCode',$ref[0]['areacode']));
		$ref_1_phone->appendChild($dom->createElement('subscriberNumber',$ref[0]['phone']));
		$personal_ref1->appendChild($dom->createElement('relationshipDescription',$lead_data['data']['ref_01_relationship']));


		// reference #2
		$personal_ref2 = $custInfo->appendChild($dom->createElement('personal-reference'));

		$per_ref_2_name = $personal_ref2->appendChild($dom->createElement('name'));
		list($first,$last) = split(" ", $lead_data['data']['ref_02_name_full']);
		$per_ref_2_name->appendChild($dom->createElement('firstname',$first));
		$per_ref_2_name->appendChild($dom->createElement('lastname',$last));
		$ref_2_phone = $personal_ref2->appendChild($dom->createElement('phoneNumber'));
		$ref_2_phone->appendChild($dom->createElement('countryCode',1));
		$ref_2_phone->appendChild($dom->createElement('areaCode',$ref[1]['areacode']));
		$ref_2_phone->appendChild($dom->createElement('subscriberNumber',$ref[1]['phone']));
		$personal_ref2->appendChild($dom->createElement('relationshipDescription',$lead_data['data']['ref_02_relationship']));
		
		// ssn 
		$custInfo->appendChild($dom->createElement('ssn',$lead_data['data']['social_security_number']));

		$fields = $dom->saveXML();
		return $fields;
	}

	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		if(!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif(preg_match('!<purchaseApproved>true</purchaseApproved>!i', $data_received))
		{
			$result->Set_Message('Accepted');
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			$result->Set_Message('Rejected');
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
		return "Vendor Post Implementation [ICA]";
	}

	public function Thank_You_Content(&$data_received)
	{
		$url = '';
		if(preg_match('!<customerRedirectUrl>([^<]+)?</customerRedirectUrl>!i', $data_received, $match))
		{
			//Since they're sending back XML, we need to decode some entities
			//like &amp;s so that it doesn't screw up the auto redirect.
			$url = html_entity_decode($match[1]);
		}
		$content = (!empty($url)) ? parent::Generic_Thank_You_Page($url) : FALSE;
		return $content;
	}

}
?>
