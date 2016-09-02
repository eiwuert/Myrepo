<?php
/**
 * @desc A concrete implementation class for posting to NTL (National Title Loans)
 *		Definition can be found here: http://leads.nationaltitleloans.com/WebServices/NTLServices.asmx?op=SubmitApplication
 * 
 * @TODO  
 */
class Vendor_Post_Impl_NTL extends Abstract_Vendor_Post_Implementation
{
	const REDIRECT = 1;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
			    'post_url1' => 'http://blackbox.post.server.ds95.tss:8080/p.php/NTL',
				'post_url2' => 'http://blackbox.post.server.ds95.tss:8080/p.php/NTL2',
				'headers1' => array(
						'Content-Type: text/xml; charset=utf-8',
						'SOAPAction: https://leads.nationaltitleloans.com/WebServices/NTLServices.asmx/SubmitApplication'
					),
				'headers2' => array(
						'Content-Type: text/xml; charset=utf-8',
						'SOAPAction: https://leads.nlservice.net/WebServices/NTLServices.asmx/SubmitApplication'
					),
				'campaign' => 'SELLINGSOURCE',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url1' => 'https://leads.nationaltitleloans.com/WebServices/NTLServices.asmx',
				'post_url2' => 'https://leads.nlservice.net/WebServices/NTLServices.asmx',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
		'ntl'    => Array(
				'ALL'      => Array(
					'user_id' => 'SELLINGSRCE',
					'password' => 'password',
					'client_id' => 'SLS',
					'adsource' => 'LP',
					'useNtlServer' => FALSE,
					'campaign' => 'SELLINGSRCE',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					
					),
				),
			'ntl2'    => Array(
				'ALL'      => Array(
					'user_id' => 'SELLINGSRCE-SAT',
					'password' => 'password',
					'client_id' => 'SLS',
					'adsource' => 'CA',
					'useNtlServer' => TRUE,
					'campaign' => 'SELLINGSRCE-SAT',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				), 
			'ntl3'    => Array(
				'ALL'      => Array(
					'user_id' => 'SELLINGSRCE-PM',
					'password' => 'password',
					'client_id' => 'SLS',
					'adsource' => 'CS',
					'useNtlServer' => false,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				), 
			'ntl4'    => Array(
				'ALL'      => Array(
					'user_id' => 'SELLINGSRCE-PM',
					'password' => 'password',
					'client_id' => 'SLS',
					'adsource' => 'PD',
					'useNtlServer' => FALSE,
					'campaign' => 'SELLINGSRCE-PM',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				), 
			'ntl5'    => Array(
				'ALL'      => Array(
					'user_id' => 'SELLINGSRCE-MN',
					'password' => 'password',
					'client_id' => 'SLS',
					'adsource' => 'CA',
					'useNtlServer' => false,
					'campaign' => 'SELLINGSRCE-MN',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				), 		
			'ntl6'    => Array(
				'ALL'      => Array(
					'user_id' => 'SELLINGSRCE-2',
					'password' => 'password',
					'client_id' => 'SLS',
					'adsource' => 'CA',
					'useNtlServer' => false,
					'campaign' => 'SELLINGSRCE-2',
				'post_url1' => 'https://leads.nationaltitleloans.com/WebServices/NTLServices.asmx',
				'post_url2' => 'https://leads.nlservice.net/WebServices/NTLServices.asmx',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				), 		
			'ntl_t1'    => Array(
				'ALL'      => Array(
					'user_id' => 'SELLINGSRCE',
					'password' => 'password',
					'client_id' => 'SLS',
					'adsource' => 'CS',
					'useNtlServer' => TRUE,
					'campaign' => 'SELLINGSRCE-ESIG',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				), 			
			'ntl_t2'    => Array(
				'ALL'      => Array(
					'user_id' => 'SELLINGSRCE-TIER1',
					'password' => 'password',
					'client_id' => 'SLS',
					'adsource' => 'CS',
					'useNtlServer' => false,
					'campaign' => 'SELLINGSRCE-TIER1',
				'post_url1' => 'https://leads.nationaltitleloans.com/WebServices/NTLServices.asmx',
				'post_url2' => 'https://leads.nlservice.net/WebServices/NTLServices.asmx',
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
		// Determine which server it is going to
		if ($params['useNtlServer'])
		{
			$params['post_url'] = $params['post_url1'];
			$params['headers'] = $params['headers1'];
		}
		else
		{
			$params['post_url'] = $params['post_url2'];
			$params['headers'] = $params['headers2'];
		}
		
		$payperiod = array(
			'WEEKLY'		=> 'W',
			'BI_WEEKLY'		=> 'B',
			'TWICE_MONTHLY'	=> 'S',
			'MONTHLY'		=> 'M',
		);

		$frequency = '';
		if(isset($lead_data['data']['paydate']['frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate']['frequency']];
		}
		elseif(isset($lead_data['data']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['income_frequency']];
		}
		elseif(isset($lead_data['data']['paydate_model']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate_model']['income_frequency']];
		}

		if(($lead_data['config']->site_type != 'soap_oc') && ($lead_data['config']->site_type != 'blackbox.one.page'))
		{
			$site_url = $lead_data['data']['client_url_root'];
		}
		else
		{
			$site_url = 'http://123onlinecash.com/';
		}
		$income_type = '';
		if(!empty($lead_data['data']['income_type']))
		{
			//Needs to be E for employment and B for benefits, so just grab
			//the first letter from whatever it is.
			$income_type = strtoupper(substr($lead_data['data']['income_type'], 0, 1));
		}
		
		$account_type = (strtoupper($lead_data['data']['bank_account_type']) == 'CHECKING') ? 'C' : 'S';

		$direct_deposit = ($lead_data['data']['income_direct_deposit']) ? 'Y' : 'N';

		if (
			!empty($lead_data['data']['name_first']) &&	
			!empty($lead_data['data']['name_last']) &&
			!empty($lead_data['data']['dob']) &&
			!empty($lead_data['data']['home_street']) &&
			!empty($lead_data['data']['home_city']) &&
			!empty($lead_data['data']['home_state']) &&
			!empty($lead_data['data']['home_zip']) &&
			!empty($lead_data['data']['phone_home']) &&
			!empty($lead_data['data']['email_primary']) &&
			!empty($lead_data['data']['income_direct_deposit']) &&
			!empty($lead_data['data']['income_monthly_net']) &&
			!empty($lead_data['data']['bank_aba']) &&
			!empty($lead_data['data']['bank_account']) &&
			!empty($lead_data['data']['social_security_number']) &&
			!empty($lead_data['data']['employer_name']) &&
			!empty($lead_data['data']['bank_name']) &&
			!empty($lead_data['data']['paydates']) &&
			!empty($lead_data['data']['state_id_number']) &&
			!empty($lead_data['data']['state_issued_id']) &&
			!empty($frequency) &&
			!empty($income_type) && 
			!empty($account_type)
			)
		{
			
			list($p1y, $p1m, $p1d) = explode('-', $lead_data['data']['paydates'][0]);
			$paydate_1 = "{$p1m}/{$p1d}/{$p1y}";
			
			list($p2y, $p2m, $p2d) = explode('-', $lead_data['data']['paydates'][1]);
			$paydate_2 = "{$p2m}/{$p2d}/{$p2y}";
			
			$phone_home = self::Format_Phone($lead_data['data']['phone_home']);
			$phone_work = self::Format_Phone($lead_data['data']['phone_work']);
			$phone_ref1 = self::Format_Phone($lead_data['data']['ref_01_phone_home']);
			$phone_ref2 = self::Format_Phone($lead_data['data']['ref_02_phone_home']);
			
			$is_military = (strcasecmp($lead_data['data']['military'], 'TRUE') === 0) ? 'Y' : 'N';
			
			// This was added becaue they wanted to track promo ids as well
			// GForge 6191 - [AuMa]
			// see the change in <OrigSiteURL>
			$promo_id = SiteConfig::getInstance()->promo_id;
			
			$fields = <<<END
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
	<soap:Body>
		<SubmitApplication xmlns="{$params['post_url']}">
			<clientCredentials>
				<UserID>{$params['user_id']}</UserID>
				<Password>{$params['password']}</Password>
				<AppID>{$lead_data['application_id']}</AppID>
				<IPAddress>{$lead_data['data']['client_ip_address']}</IPAddress>
				<OrigSiteURL>{$site_url}:{$promo_id}</OrigSiteURL>
				<Campaign>{$params['campaign']}</Campaign>
			</clientCredentials>
			<borrowerInfo>
				<LastName>{$lead_data['data']['name_last']}</LastName>
				<FirstName>{$lead_data['data']['name_first']}</FirstName>
				<MiddleInitial>{$lead_data['data']['name_middle']}</MiddleInitial>
				<Address>
					<Address1>{$lead_data['data']['home_street']}</Address1>
					<Address2></Address2>
					<City>{$lead_data['data']['home_city']}</City>
					<State>{$lead_data['data']['home_state']}</State>
					<ZipCode>{$lead_data['data']['home_zip']}</ZipCode>
					<Phone>{$phone_home}</Phone>
				</Address>
				<DriverNumber>{$lead_data['data']['state_id_number']}</DriverNumber>
				<DriverState>{$lead_data['data']['state_issued_id']}</DriverState>
				<SSN>{$lead_data['data']['social_security_number']}</SSN>
				<DOB>{$lead_data['data']['dob']}</DOB>
				<Sex></Sex>
				<WorkPhone>{$phone_work}</WorkPhone>
				<Extension>{$lead_data['data']['ext_work']}</Extension>
				<CellPhone></CellPhone>
				<EmailAddress>{$lead_data['data']['email_primary']}</EmailAddress>
				<Reference1Name>{$lead_data['data']['ref_01_name_full']}</Reference1Name>
				<Reference1Phone>{$phone_ref1}</Reference1Phone>
				<Reference1Relation>{$lead_data['data']['ref_01_relationship']}</Reference1Relation>
				<Reference2Name>{$lead_data['data']['ref_02_name_full']}</Reference2Name>
				<Reference2Phone>{$phone_ref2}</Reference2Phone>
				<Reference2Relation>{$lead_data['data']['ref_02_relationship']}</Reference2Relation>
				<IsMilitaryStatus>{$is_military}</IsMilitaryStatus>
				<EmailOptIn></EmailOptIn>
			</borrowerInfo>
			<employerInfo>
				<Name>{$lead_data['data']['employer_name']}</Name>
				<Address>
					<Address1></Address1>
					<Address2></Address2>
					<City></City>
					<State></State>
					<ZipCode></ZipCode>
					<Phone></Phone>
				</Address>
				<Fax></Fax>
				<Supervisor></Supervisor>
				<SuperPhone></SuperPhone>
				<YearsOfService></YearsOfService>
				<JobDescription></JobDescription>
				<EmploymentStatus></EmploymentStatus>
				<ShiftHours></ShiftHours>
				<HireDate></HireDate>
			</employerInfo>
			<bankInfo>
				<Name>{$lead_data['data']['bank_name']}</Name>
				<Phone></Phone>
				<ABA>{$lead_data['data']['bank_aba']}</ABA>
				<AccountNumber>{$lead_data['data']['bank_account']}</AccountNumber>
				<AccountType>{$account_type}</AccountType>
			</bankInfo>
			<incomeInfo>
				<IncomeSource>{$income_type}</IncomeSource>
				<DirectDeposit>{$direct_deposit}</DirectDeposit>
				<PayFrequency>{$frequency}</PayFrequency>
				<NetIncome>{$lead_data['data']['income_monthly_net']}</NetIncome>
				<NextPayDate>{$paydate_1}</NextPayDate>
				<NextPayDate2>{$paydate_2}</NextPayDate2>
			</incomeInfo>
		</SubmitApplication>
	</soap:Body>
</soap:Envelope>
END;

			// No Trailing or leading SPACES!
			$fields = trim($fields); 
		
		}
		else
		{
			$fields = NULL;
		}
		
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
		elseif(preg_match('!<Decision>Accepted</Decision>!i', $data_received))
		{
			$result->Set_Message('Accepted');
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		elseif(preg_match('!<Decision>Rejected</Decision>!i', $data_received))
		{
			$result->Set_Message('Rejected');
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			
			$m = array();
			preg_match('/<Reason>(.*)<\/Reason>/i', $data_received, $m);
			$reason = substr($m[1],0,255);
			$result->Set_Vendor_Reason($reason);
		}
		else
		{
			$result->Set_Message('Failed');
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('UNKNOWN');
			
			$m = array();
			preg_match('/<Reason>(.*)<\/Reason>/i', $data_received, $m);
			$reason = substr($m[1],0,255);
			$result->Set_Vendor_Reason($reason);
		}
		
		return $result;
	}
	
	
	public static function Format_Phone($phone)
	{
		if(strlen($phone) != 10) return '';
		
		return implode('-', array(
							substr($phone, 0, 3),
							substr($phone, 3, 3),
							substr($phone, 6),
							));
	}
	
	

//	Uncomment the next line to use HTTP GET instead of POST
//	public static function Get_Post_Type() {return Http_Client::HTTP_GET;}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [National Title Loans]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		$url = '';//'http://www.nationalloanservice.net/camasterloan.php';
		if(preg_match('!<RedirectUrl>([^<]+)?</RedirectUrl>!i', $data_received, $match))
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
