<?php
/**
    @publicsection
    @public
    @brief
        Returns token values generated from $data for use with the new Condor system
    
    @version 
        1.0 2006-04-19 Norbinn Rodrigo

 */
class Condor_Display 
{
	
	private $type; // Are we previewing this doc?
	private $tokens; // Are we previewing this doc?
	
	/**
	 * Generate_Tokens will pull these from business
	 * rules when. Format is business_rule_name => token_name
	 *
	 * @var unknown_type
	 */
	protected static $biz_rule_to_token_map = array(
		'return_transaction_fee' => 'ReturnFee',
		'principal_payment_amount' => 'PrincipalPaymentAmount',
	);

	public function __construct($type = 'view')
	{
		$this->type = $type;
		
		$this->tokens = array(
			'BankABA',
			'BankAccount',
			'BankName',
			'CardProvBankName',
			'CardProvBankShort',
			'CardProvServName',
			'CardProvServPhone',
			'CompanyCity',
			'CompanyDept',
			'CompanyEmail',
			'CompanyFax',
			'CompanyInit',
			'CompanyNameFormal',
			'CompanyNameLegal',
			'CompanyNameShort',
			'CompanyLogoLarge',
			'CompanyLogoSmall',
			'CompanyPhone',
			'CompanyPromoID',
			'CompanyState',
			'CompanyStreet',
			'CompanySupportFax',
			'CompanyUnit',
			'CompanyWebsite',
			'CompanyZip',
			'CustomerCity',
			'CustomerDOB',
			'CustomerEmail',
			'CustomerESig',
			'CustomerFax',
			'CustomerNameFirst',
			'CustomerNameFull',
			'CustomerNameLast',
			'CustomerPhoneCell',
			'CustomerPhoneHome',
			'CustomerResidenceLength',
			'CustomerResidenceType',
			'CustomerSSNPart1',
			'CustomerSSNPart2',
			'CustomerSSNPart3',
			'CustomerState',
			'CustomerStateID',
			'CustomerStreet',
			'CustomerUnit',
			'CustomerZip',
			'EmployerLength',
			'EmployerName',
			'EmployerPhone',
			'EmployerTitle',
			'EmployerShift',
			'IncomeDD',
			'IncomeFrequency',
			'IncomeMonthlyNet',
			'IncomeNetPay',
			'IncomePaydate1',
			'IncomePaydate2',
			'IncomePaydate3',
			'IncomePaydate4',
			'IncomeType',
			'LoanApplicationID',
			'LoanAPR',
			'LoanCollectionCode',
			'LoanDocDate',
			'LoanDueDate',
			'LoanFinCharge',
			'LoanFundAmount',
			'LoanFundDate',
			'LoanPayoffDate',
			'LoanRefAmount',
			'LoginId',
			'PaymentArrAmount',
			'PaymentArrDate',
			'PaymentArrType',
			'PDAmount',
			'PDFinCharge',
			'PDNextFinCharge',
			'PDTotal',
			'Ref01NameFull',
			'Ref01PhoneHome',
			'Ref01Relationship',
			'Ref02NameFull',
			'Ref02PhoneHome',
			'Ref02Relationship',
			'ReturnFee',
			'ReturnReason',
			'SourceSiteName',
			'SourcePromoID',
			'Today',
			'TotalOfPayments',
			'ESigDisplayTextApp',
			'ESigDisplayTextLoan',
			'ESigDisplayTextAuth',
			'PrincipalPaymentAmount',
			'CardName',
			'CardNumber',
			'CardProvBankName',
		);

		//This is retarded, but as a programmer, I am lazy.
		$this->tokens = array_flip($this->tokens);
		foreach($this->tokens as $key => $value) $this->tokens[$key] = null;
	}
	
	private function Format( $key, $value )
	{

		$output = '';
		
		switch( $key )
		{
			case 'LoanDocDate':
			/*case 'IncomePaydate1':
			case 'IncomePaydate2':
			case 'IncomePaydate3':
			case 'IncomePaydate4':
			case 'LoanPayoffDate':
			case 'LoanFundDate':*/
			case 'Today':
				$output = (!empty($value)) ? date('m/d/Y', strtotime($value)) : date('m/d/Y');
				break;
			case 'CustomerPhoneHome':
			case 'CustomerPhoneCell':
			case 'CustomerFax':
			case 'EmployerPhone':
			case 'Ref01PhoneHome':
			case 'Ref02PhoneHome':
				if ($value)
				{
					$output = '(' . substr($value, 0, 3) . ') ' . substr($value, 3, 3) . '-' . substr($value, 6, 4);
				}
				else
				{
					$output = 'N/A';
				}
				break;
			case 'CustomerResidenceLength':
				$output = is_numeric($value) ?	floor ($value/12) . ' yrs ' . ($value % 12) . ' mnths' : 'Unspecified';
				break;
			case 'CustomerResidenceType':
				if (isset($value) && $value != 'unspecified')
				{
					$output = 'I am the ' . (($value == 'RENT') ? 'renter' : 'owner') . ' of the residence';
				}
				else
				{
					$output = 'NA';
				}
				break;
			case 'IncomeType':
				$output = (strtoupper($value) == 'BENEFITS') ? 'benefits' : 'job';
				break;
			case 'EmployerLength':
				$output = '0 Yrs&nbsp;&nbsp;&nbsp;&nbsp;3+ Mths&nbsp;&nbsp;&nbsp;&nbsp;';
				break;
			case 'ReturnFee':
			case 'IncomeMonthlyNet':
			case 'IncomeNetPay':
			case 'LoanBalance':
			case 'PrincipalPaymentAmount':
			case 'LoanFinCharge':
			case 'LoanFundAmount':
			case 'TotalOfPayments':
				if (preg_match('/\.\d$/', trim($value)))
				{
					$output =  '$' . $value . '0';
				}
				elseif (preg_match('/\.\d\d$/', trim($value)))
				{
					$output = '$' . $value;
				}
				else
				{
					$output = '$' . $value . '.00';
				}
				break;
			case 'IncomeDD':
				$output = ($value == 'TRUE') ? 'Yes' : 'No';
				break;
			case 'IncomeFrequency':
				$output = preg_replace ('/_/', '-', strtolower($value));
				break;
			case 'LoanAPR':
				$output = round($value, 2) . '%';
				break;
				
			default:
				$output = $value;
				break;
		}
		
		return $output;
	}
	
	
	public function Generate_Condor_Tokens($prop_data = NULL)
	{
		$token_data = array();
		
		$data = (!empty($_SESSION['cs'])) ? $_SESSION['cs'] : $_SESSION['data'];
		$config = clone $_SESSION['config'];
		
		if(empty($prop_data))
		{
			$property_short = (strtoupper($config->property_short) == 'BB') ? $config->bb_force_winner : $config->property_short;			
		}
		else
		{
			$property_short = $prop_data['property_short'];
		}

		$application_id = $this->Get_Application_ID();

		// GForge 6741 - Won't work without an application id. [RM]
		if ($application_id)
		{
			try
			{
				$sql = &Setup_DB::Get_Instance('blackbox', $config->mode, $property_short);
				
				$query = "SELECT
						routing_number			AS BankABA,
						account_number			AS BankAccount,
						UPPER(bank_name)			AS BankName,
						
						UPPER(city)				AS CustomerCity,
						UPPER(state)				AS CustomerState,
						UPPER(address_1)			AS CustomerStreet,
						UPPER(apartment)			AS CustomerUnit,
						zip					AS CustomerZip,
						UPPER(drivers_license_number)	AS CustomerStateID,
						
						date_of_birth				AS dob,
						email					AS CustomerEmail,
						fax_phone				AS CustomerFax,
						home_phone				AS CustomerPhoneHome,
						cell_phone				AS CustomerPhoneCell,
						UPPER(first_name)			AS CustomerNameFirst,
						UPPER(last_name)			AS CustomerNameLast,
						'unspecified'				AS CustomerResidenceType,
						social_security_number		AS ssn,
	
						
						NULL					AS EmployerLength,
						UPPER(employer)			AS EmployerName,
						work_phone				AS EmployerPhone,
						UPPER(title)				AS EmployerTitle,
						shift					AS EmployerShift,
						
						direct_deposit			AS IncomeDD,
						pay_frequency			AS IncomeFrequency,
						monthly_net				AS IncomeMonthlyNet,
						income_type				AS IncomeType,
						net_pay				AS IncomeNetPay,
						DATE_FORMAT(estimated_payoff_date, '%m/%d/%Y')	AS LoanPayoffDate,
						
						a.application_id			AS LoanApplicationID,
						apr					AS LoanAPR,
						finance_charge			AS LoanFinCharge,
						fund_amount				AS LoanFundAmount,
						DATE_FORMAT(IFNULL(actual_fund_date, estimated_fund_date), '%m/%d/%Y')	AS LoanFundDate,
						
						DATE_FORMAT(pay_date_1, '%m/%d/%Y')	AS IncomePaydate1,
						DATE_FORMAT(pay_date_2, '%m/%d/%Y')	AS IncomePaydate2,
						DATE_FORMAT(pay_date_3, '%m/%d/%Y')	AS IncomePaydate3,
						DATE_FORMAT(pay_date_4, '%m/%d/%Y')	AS IncomePaydate4,
						
						total_payments			AS TotalOfPayments
					
					FROM application a
						INNER JOIN personal_encrypted USING (application_id)
						INNER JOIN residence USING (application_id)
						INNER JOIN bank_info_encrypted USING (application_id)
						INNER JOIN employment USING (application_id)
						INNER JOIN income USING (application_id)
						INNER JOIN loan_note USING (application_id)
					WHERE a.application_id = {$application_id}";
				
				
				$result = $sql->Query($sql->db_info['db'], $query);
		
				$tokens = $this->tokens;
				$tokens = array_merge($tokens, $sql->Fetch_Array_Row($result));
				
				$crypt_config 	= Crypt_Config::Get_Config(BFW_MODE);
				$crypt_object	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
				
				$tokens['BankABA'] = $crypt_object->decrypt($tokens['BankABA']);
				$tokens['BankAccount'] = $crypt_object->decrypt($tokens['BankAccount']);
				$tokens['dob'] = explode("-",$crypt_object->decrypt($tokens['dob']));
				$tokens['CustomerDOB'] = $tokens['dob'][1]."/".$tokens['dob'][2]."/".$tokens['dob'][0];				
				$tokens['ssn'] = $crypt_object->decrypt($tokens['ssn']);
				$tokens['LoanBalance'] = $tokens['LoanFundAmount'];
							
				$query = "SELECT
						UPPER(full_name)	AS NameFull,
						phone				AS PhoneHome,
						UPPER(relationship) AS Relationship
					FROM personal_contact
					WHERE application_id = '{$application_id}'";
		
				$result = $sql->Query($sql->db_info['db'], $query);
				
				$count = 1;
				while($row = $sql->Fetch_Array_Row($result))
				{
					$ref = 'Ref' . sprintf('%02d', $count);
					
					foreach($row as $key => $value)
					{
						$tokens[$ref . $key] = $value;
					}
					
					++$count;
				}
				
				
				if(empty($prop_data))
				{
					$tokens['CompanyNameLegal']	= $config->legal_entity;
					$tokens['CompanySupportFax']= $config->support_fax;
					$tokens['CompanyWebsite']	= $config->site_name;
	
					$tokens['SourceSiteName']	= $config->site_name;
				}
				else
				{
					$tokens['CompanyNameLegal']	= $prop_data['legal_entity'];
					$tokens['CompanySupportFax']= $prop_data['fax'];
					$tokens['CompanyWebsite']	= $prop_data['site_name'];
					
					$tokens['SourceSiteName']	= $prop_data['site_name'];
				}
				
				// Mantis #13027 - Make fax number look prettier [RM]
				if (preg_match('/^1?800(\d{3})(\d{4})$/', $tokens['CompanySupportFax'], $matches))
				{
					$tokens['CompanySupportFax'] = "1-800-{$matches[1]}-{$matches[2]}";
				}
				
				$tokens['CompanyNameShort'] = $property_short;
				$tokens['SourcePromoID'] = $config->promo_id;
				
				$tokens['CustomerNameFull'] = $tokens['CustomerNameFirst'] . ' ' . $tokens['CustomerNameLast'];
				$tokens['CustomerESig'] = ($this->type == 'preview') ? '' : $tokens['CustomerNameFull'];
				
		
				$tokens['CustomerSSNPart1'] = substr($tokens['ssn'], 0, 3);
				$tokens['CustomerSSNPart2'] = substr($tokens['ssn'], 3, 2);
				$tokens['CustomerSSNPart3'] = substr($tokens['ssn'], 5, 4);
		
				$tokens['ESigDisplayTextApp'] = $this->GetEsigDisplayText('app', !empty($config->is_ivr_app));
				$tokens['ESigDisplayTextLoan'] = $this->GetEsigDisplayText('loan', !empty($config->is_ivr_app));
				$tokens['ESigDisplayTextAuth'] = $this->GetEsigDisplayText('auth', !empty($config->is_ivr_app));
							
	
				if($_SESSION['data']['loan_type'] == 'card' || $_SESSION['cs']['loan_type'] == 'card')
				{
					$card_vals = $this->GetCardInfo($tokens['ssn'], $property_short);
					$card_provider_vals = $this->getCardProviderInfo($property_short);
					
				if(is_array($card_provider_vals) && is_array($card_vals))
				{
					$tokens = array_merge($tokens, $card_vals, $card_provider_vals);
				}
				}
				
				
				$tokens = array_merge($tokens, $this->getBusinessRuleTokens($property_short, $config->mode));
				
				unset($tokens['ssn']);
	
				if(strcasecmp($property_short, 'generic') == 0)
				{
					$tokens['ReturnFee'] = '$15.00';
					$tokens['CompanyName'] = 'someloancompany.com';
					$tokens['CompanyNameFormal'] = 'Some Company Name';
					/* This needs to be changed to use the eCash business rules
					* principal_payment->principal_payment_type will be Fixed or Percentage, then based on that
					* we need to use principal_payment->principal_payment_percentage or
					* principal_payment->principal_payment_amount
					*/
					$tokens['PDPercent'] = '10%';
					$tokens['LoanFundDate2'] = $tokens['LoanFundDate'];
					$tokens['LoanFundDate'] = date('m/d/Y');
				}
	
				foreach($tokens as $key => $value)
				{
					$token_data[$key] = $this->Format($key, $value);
				}
			}
			catch(Exception $e)
			{
				$applog = Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE);
				$applog->Write('Failed to create Condor Data: ' . $e->getMessage());
			}
			if(!isset($token_data['LoginId']))
			{
				$token_data['LoginId'] = '';
			}
		}
		return $token_data;
	}
	
	
	protected function getBusinessRuleTokens($property_short, $mode)
	{
		$return = array();
		try 
		{
			$ldb = Setup_DB::Get_Instance('mysql', $mode, $property_short);
			$loan_type = $this->getLoanType($property_short);
		
			require_once('business_rules.class.php');
			$biz_rules = new Business_Rules($ldb);
			$loan_type_id = $biz_rules->Get_Loan_Type_For_Company($property_short, $loan_type);
			if(!is_numeric($loan_type_id))
			{
				throw new Exception("No loan type for $property_short / $loan_type");
			}
			$rule_tree = $biz_rules->Get_Latest_Rule_Set($loan_type_id);
			if(!is_array($rule_tree))
			{
				throw new Exception("No rule tree for $property_short (Loan Type: $loan_type Id: $loan_type_id");
			}
			foreach(self::$biz_rule_to_token_map as $business_rule => $token)
			{
				if(!empty($rule_tree[$business_rule]))
				{
					$return[$token] = $rule_tree[$business_rule];
				}
			}
		}
		catch (Exception $e)
		{
			$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE);
			$applog->Write(__CLASS__.'::'.__METHOD__.': Could not get tokens from business rules: '.$e->getMessage());
		}
		return $return;
	}
	
	protected function getLoanType($property_short)
	{
		require_once('qualify.2.php');
		require_once('OLP_Qualify_2.php');
		
		if(empty($_SESSION['data']['loan_type']))
		{
			$loan_type = OLP_Qualify_2::Get_Loan_Type($property_short);
		}
		else 
		{
			$loan_type = $_SESSION['data']['loan_type'];
		}
		return $loan_type;
	}
	
	
	/**
	 * Function for fetching the card provider information from the DB.
	 *
	 * @param string $property_short
	 * @return array 
	 */
	public function getCardProviderInfo($property_short)
	{
		$values = array();

		$property_map = array(
			'COMPANY_CARD_PROV_SERV' 		=> 'CardProvServName',
			'COMPANY_CARD_PROV_SERV_PHONE' 	=> 'CardProvServPhone',
			'COMPANY_FAX' 					=> 'CompanyFax'
		);

		try
		{
			$db = Setup_DB::Get_Instance('mysql', BFW_MODE, $property_short);
			$query = "SELECT property, value 
						FROM company_property 
						WHERE company_id = (SELECT company_id FROM company WHERE name_short = '".$property_short."' AND active_status = 'active') 
						AND property IN ('" . implode("', '", array_keys($property_map)) . "')";
			
			$result = $db->Query($query);
			
			while($row = $result->Fetch_Object_Row())
			{
				if(isset($property_map[strtoupper($row->property)]))
				{
					$values[$property_map[strtoupper($row->property)]] = $row->value;
				}
			}
		}
		catch(Exception $e)
		{
			$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE);
			$applog->Write('Failed to get the card provider data: ' . $e->getMessage());
		}
		
		return $values;
	}
	
	public function GetCardInfo($ssn, $prop_short)
	{
		$values = array();

		$prop_map = array(
			'COMPANY_CARD_PROV_BANK' => 'CardProvBankName',
			'COMPANY_CARD_NAME' => 'CardName'
		);

		try
		{
			$db = Setup_DB::Get_Instance('mysql', BFW_MODE, $prop_short);
			$query = "SELECT property, value
				FROM company_property
				WHERE company_id = (SELECT company_id FROM company WHERE name_short = 'd1' AND active_status = 'active')
					AND property IN ('" . implode("', '", array_keys($prop_map)) . "')";
			$result = $db->Query($query);

			while($row = $result->Fetch_Object_Row())
			{
				if(isset($prop_map[strtoupper($row->property)]))
				{
					$values[$prop_map[strtoupper($row->property)]] = $row->value;
				}
			}

			$query = "SELECT card_number FROM card INNER JOIN customer USING (customer_id) WHERE ssn = '{$ssn}'";
			$result = $db->Query($query);

			//eCash Crypt crap
			require_once('libolution/Util/Convert.1.php');
			$crypt = new Security_Crypt_1('jVlODYyZTAuMjQzMIzY2U5MzYuNDExNj');
			$crypt->setStaticIV('2NlNzlmMzEuNzc2M');
			$crypt->setUseStaticIV(true);
			
			if($result->Row_Count() > 0)
			{
				$row = $result->Fetch_Object_Row();
				$values['CardNumber'] = $crypt->decrypt(Util_Convert_1::string2Bin($row->card_number));
			}
		}
		catch(Exception $e)
		{
			//Ignore
		}

		return $values;
	}
	
	public function GetEsigDisplayText($type, $is_ivr = false)
	{
		$text = '';
		
		if($is_ivr)
		{
			$text = 'Authorized by IVR System';
		}
		else
		{
			switch(strtolower($type))
			{
				default:
				case 'app': $text = 'Electronic Signature of Applicant'; break;
				case 'loan':$text = 'Electronic Signature'; break;
				case 'auth':$text = 'Signature of Applicant'; break;
			}
		}
		
		return $text;
	}
	
	/*
		Map tokens from ent_cs's Prepare_Condor_Data
	*/
	public function Rename_Tokens($token_data)
	{
		$data = $token_data['data'];

		$config = clone $_SESSION['config'];
		
		$tokens = array(
			'BankABA'		=> $data['bank_aba'],
			'BankAccount'	=> $data['bank_account'],
			'BankName'		=> strtoupper($data['bank_name']),
			
			'CustomerCity'	=> strtoupper($data['home_city']),
			'CustomerState'	=> strtoupper($data['home_state']),
			'CustomerStreet'=> strtoupper($data['home_street']),
			'CustomerUnit'	=> strtoupper($data['home_unit']),
			'CustomerZip'	=> $data['home_zip'],
			'CustomerStateID' => strtoupper($data['state_id_number']),
			
			'CustomerDOB'		=> $data['dob'],
			'CustomerEmail'		=> $data['email_primary'],
			'CustomerFax'		=> $data['phone_fax'],
			'CustomerPhoneHome'	=> $data['phone_home'],
			'CustomerPhoneCell'	=> $data['phone_cell'],
			'CustomerNameFirst'	=> strtoupper($data['name_first']),
			'CustomerNameLast'	=> strtoupper($data['name_last']),
			'CustomerResidenceType' => 'unspecified',
			
			'CustomerSSNPart1'	=> $data['ssn_part_1'],
			'CustomerSSNPart2'	=> $data['ssn_part_2'],
			'CustomerSSNPart3'	=> $data['ssn_part_3'],
			
			'EmployerLength'	=> $data['employer_length'],
			'EmployerName'		=> strtoupper($data['employer_name']),
			'EmployerPhone'		=> $data['phone_work'],
			'EmployerTitle'		=> strtoupper($data['title']),
			'EmployerShift'		=> $token_data['employment']['shift'],
			
			'IncomeDD'			=> $data['income_direct_deposit'],
			'IncomeFrequency'	=> $data['paydate_model']['income_frequency'],
			'IncomeMonthlyNet'	=> $data['income_monthly_net'],
			'IncomeType'		=> $data['income_type'],
			'IncomeNetPay'		=> $data['qualify_info']['net_pay'],
			'LoanPayoffDate'	=> $this->Format_Date($data['qualify_info']['payoff_date']),
			
			'LoanApplicationID'	=> $token_data['application_id'],
			'LoanAPR'			=> $data['qualify_info']['apr'],
			'LoanFinCharge'		=> $data['qualify_info']['finance_charge'],
			'LoanFundAmount'	=> $data['fund_qualified'],
			'LoanBalance'       => $data['fund_qualified'],
			'PrincipalPaymentAmount' => $data['fund_qualified'],
			'LoanFundDate'		=> $this->Format_Date($data['qualify_info']['fund_date']),
			'TotalOfPayments'	=> $data['qualify_info']['total_payments'],
			
			'IncomePaydate1'	=> $this->Format_Date($token_data['pay_dates'][0]),
			'IncomePaydate2'	=> $this->Format_Date($token_data['pay_dates'][1]),
			'IncomePaydate3'	=> $this->Format_Date($token_data['pay_dates'][2]),
			'IncomePaydate4'	=> $this->Format_Date($token_data['pay_dates'][3]),
			
			'Ref01NameFull'		=> $data['ref_01_name_full'],
			'Ref01PhoneHome'	=> $data['ref_01_phone_home'],
			'Ref01Relationship'	=> $data['ref_01_relationship'],
			'Ref02NameFull'		=> $data['ref_02_name_full'],
			'Ref02PhoneHome'	=> $data['ref_02_phone_home'],
			'Ref02Relationship'	=> $data['ref_02_relationship'],
			
			'ESigDisplayTextApp'	=> $this->GetEsigDisplayText('app', !empty($config->is_ivr_app)),
			'ESigDisplayTextLoan'	=> $this->GetEsigDisplayText('loan', !empty($config->is_ivr_app)),
			'ESigDisplayTextAuth'	=> $this->GetEsigDisplayText('auth', !empty($config->is_ivr_app)),
			'LoginId' => '',
			
		);
		
		$tokens['CompanyNameLegal']	= $config->legal_entity;
		$tokens['CompanyNameShort']	= $token_data['config']->property_short;
		$tokens['CompanySupportFax']= $config->support_fax;
		$tokens['CompanyWebsite']	= $config->site_name;
		$tokens['CustomerNameFull']	= $tokens['CustomerNameFirst'] . ' ' . $tokens['CustomerNameLast'];
		$tokens['CustomerESig']		= ($this->type == 'preview') ? '' : $tokens['CustomerNameFull'];
		
		$tokens['SourcePromoID']	= $config->promo_id;
		$tokens['SourceSiteName']	= $config->site_name;
		
		$tokens = array_merge($this->tokens, $tokens);

        if($_SESSION['data']['loan_type'] == 'card' || $_SESSION['cs']['loan_type'] == 'card')
        {
            $card_vals = $this->GetCardInfo($data['ssn_part_1'] . $data['ssn_part_2'] . $data['ssn_part_3'], $token_data['config']->property_short);
            if(is_array($card_vals))
            {
                $tokens = array_merge($tokens, $card_vals);
            }
            
            $card_provider_vals = $this->getCardProviderInfo($token_data['config']->property_short);
            if(is_array($card_provider_vals))
            {
                $tokens = array_merge($tokens, $card_provider_vals);
            }
        }
        
        $property_short = (strtoupper($config->property_short) == 'BB') ? $config->bb_force_winner : $config->property_short;			
      	$tokens = array_merge($tokens, $this->getBusinessRuleTokens($property_short, $config->mode));
		
		foreach($tokens as $key => $value)
		{
			$tokens[$key] = $this->Format($key, $value);
		}
		return $tokens;
	}
	
	private function Format_Date($date)
	{
		list($y, $m, $d) = explode('-', $date);
		
		return "$m/$d/$y";
	}
	
	
    private function Get_Application_ID()
    {
		$app_id = null;
		
		if(!empty($_SESSION['application_id']))
		{
		    $app_id = $_SESSION['application_id'];
		}
		elseif(!empty($_SESSION['cs']['application_id']))
		{
		    $app_id = $_SESSION['cs']['application_id'];
		}
		elseif(!empty($_SESSION['data']['application_id']))
		{
		    $app_id = $_SESSION['data']['application_id'];
		}
		elseif(!empty($_SESSION['transaction_id']))
		{
			$app_id = $_SESSION['transaction_id'];
		}
		
		// GForge #4760 - Sometimes app_id is base64 encoded. If so, correct it. [RM]
		if(!is_numeric($app_id))
		{
			$app_id = base64_decode($app_id);
			
			if (!is_numeric($app_id))
			{
				$app_id = NULL;
			}
		}
		
		return $app_id;
    }

}
?>
