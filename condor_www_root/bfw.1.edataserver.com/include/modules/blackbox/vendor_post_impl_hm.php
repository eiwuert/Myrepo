<?php
/**
 * @desc A concrete implementation class for posting to hm (Heritage Market)
 */
class Vendor_Post_Impl_HM extends Abstract_Vendor_Post_Implementation
{
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/HM1',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				// We were asked to use following URL to replace payday-loan-yes.com/xxxx for hm1/hm3/hm4,
				// but since hm2 is an inactive campaign now, we use the following URL to replace all 
				// HM campaigns. GForge #5699 [DY] 
				'post_url' => 'https://xml.globalpaydayloan.com/leadprocessing/submitapp.aspx', // was 'https://www.payday-loan-yes.com/leadprocessing/submitapp.aspx'
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'hm1'    => Array(
				'ALL'      => Array(
					'ref_site' => 'prtnrwkly','store_num'=>'1'
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
				
			'hm2'	=> Array(
				'ALL'      => Array(
					'ref_site' => 'prtnrwkl3','store_num' => '1'
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
			'hm3'    => Array(
				'ALL'      => Array(
					'ref_site' => 'prtnrwkle','store_num'=>'17'
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					),
				),
			'hm4'    => Array(
				'ALL'      => Array(
					'ref_site' => 'prtnrwklfc','store_num'=>'6'
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
		$map_incomesource = array (
			'EMPLOYMENT' => 'E',
			'BENEFITS' => 'B',
		);

		$map_payperiod = array(
			'WEEKLY' => 'W',
			'BI_WEEKLY' => 'B',
			'TWICE_MONTHLY' => 'S',
			'MONTHLY' => 'M',
		);

		$paydates = array();
		foreach ($lead_data['data']['paydates'] AS $paydate)
		{
			list($y, $m, $d) = explode('-', $paydate);
			$paydates[] = $m.'/'.$d.'/'.$y;
		}

		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];

		$fields =
'<?xml version="1.0"?>
<Application>
  <CustomerInfo>
    <StoreNumber>'.$params['store_num'].'</StoreNumber>
    <WebRefSite>'.$params['ref_site'].'</WebRefSite>
    <WebKeyWords>
    </WebKeyWords>
    <AID>
    </AID>
    <PID>
    </PID>
    <RefBy>
    </RefBy>
    <FromWeb>Y</FromWeb>
    <SSN>'.$lead_data['data']['ssn_part_1'].$lead_data['data']['ssn_part_2'].$lead_data['data']['ssn_part_3'].'</SSN>
    <LName>'.$lead_data['data']['name_last'].'</LName>
    <FName>'.$lead_data['data']['name_first'].'</FName>
    <Address1>'.$lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit'].'</Address1>
    <AptNumber></AptNumber>
    <City>'.$lead_data['data']['home_city'].'</City>
    <State>'.$lead_data['data']['home_state'].'</State>
    <ZipCode>'.$lead_data['data']['home_zip'].'</ZipCode>
    <DOB>'.$lead_data['data']['date_dob_y'].'-'.$lead_data['data']['date_dob_m'].'-'.$lead_data['data']['date_dob_d'].'</DOB>
    <AreaCode>'.substr($lead_data['data']['phone_home'], 0, 3).'</AreaCode>
    <PhoneNumber>'.substr($lead_data['data']['phone_home'], 3, 7).'</PhoneNumber>
    <CellPhone>'.$lead_data['data']['phone_cell'].'</CellPhone>
    <Sex></Sex>
    <DriverLic>'.$lead_data['data']['state_id_number'].'</DriverLic>
    <LicState>'.$issued_state.'</LicState>
    <EmailAddress>'.$lead_data['data']['email_primary'].'</EmailAddress>
    <OwnHome></OwnHome>
    <LoanReq></LoanReq>
    <IncomeSource>'.$map_incomesource[$lead_data['data']['income_type']].'</IncomeSource>
    <Employer>'.$lead_data['data']['employer_name'].'</Employer>
    <EmployerCity></EmployerCity>
    <EmployerState></EmployerState>
    <EmployerPhone>'.$lead_data['data']['phone_work'].'</EmployerPhone>
    <EmployerExtension></EmployerExtension>
    <EmployerZip></EmployerZip>
    <Supervisor></Supervisor>
    <SupervisorPhone></SupervisorPhone>
    <Position></Position>
    <WorkHours></WorkHours>
    <Income>'.$lead_data['data']['income_monthly_net'].'</Income>
    <PayPeriod>'.$map_payperiod[$lead_data['data']['paydate_model']['income_frequency']].'</PayPeriod>
    <DirectDeposit>'.($lead_data['data']['income_direct_deposit']=='TRUE'?'Yes':'No').'</DirectDeposit>
    <PayDate1>'.$paydates[0].'</PayDate1>
    <PayDate2>'.$paydates[1].'</PayDate2>
    <PayDate3>'.$paydates[2].'</PayDate3>
    <PayDate4>'.$paydates[3].'</PayDate4>
    <MosJob></MosJob>
    <AccountType>'.$lead_data['data']['bank_account_type'].'</AccountType>
    <BankName>'.$lead_data['data']['bank_name'].'</BankName>
    <ABA>'.$lead_data['data']['bank_aba'].'</ABA>
    <AccountNumber>'.$lead_data['data']['bank_account'].'</AccountNumber>
    <AccountOpened></AccountOpened>
    <RefName1>'.$lead_data['data']['ref_01_name_full'].'</RefName1>
    <RefRelation1>'.$lead_data['data']['ref_01_relationship'].'</RefRelation1>
    <RefPhone1>'.$lead_data['data']['ref_01_phone_home'].'</RefPhone1>
    <RefName2>'.$lead_data['data']['ref_02_name_full'].'</RefName2>
    <RefRelation2>'.$lead_data['data']['ref_02_relationship'].'</RefRelation2>
    <RefPhone2>'.$lead_data['data']['ref_02_phone_home'].'</RefPhone2>
    <DoNotEmail>N</DoNotEmail>
    <NSF>0</NSF>
  </CustomerInfo>
  <WebTracking>
    <Camp></Camp>
    <Site></Site>
    <Area></Area>
    <Bann></Bann>
    <UID>'.$lead_data['config']->promo_id.'</UID>
  </WebTracking>
</Application>';

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
		elseif (preg_match('/<ACCEPT_REJECT>ACCEPTED<\/ACCEPT_REJECT>/i', $data_received))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
			
			$m = array();
			preg_match('/<ACCEPT_REJECT_REASON>(.*)<\/ACCEPT_REJECT_REASON>/i', $data_received, $m);
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
		return "Vendor Post Implementation [HM]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		if (preg_match("/<DOC_LINK>(.*)<\/DOC_LINK>/", $data_received, $m))
		{
			return parent::Generic_Thank_You_Page($m[1]);
		}
		
		return false;
	}
	
}
?>