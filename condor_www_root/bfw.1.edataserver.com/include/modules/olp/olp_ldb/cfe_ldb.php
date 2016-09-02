<?php

	ini_set('include_path', get_include_path().":/virtualhosts/ecash_common");

	require_once 'olp_ldb.php';
	require_once('libolution/AutoLoad.1.php');

	class CFE_LDB extends OLP_LDB
	{
		/**
		 * @var string
		 */
		protected static $mode;

		/**
		 * @var Security_ICrypt_1
		 */
		protected static $crypt;
		
		public function __construct($mysql, $property_short = null, $data = array())
		{
			parent::__construct($mysql, $property_short, $data);
			$this->ent_prop_list = Enterprise_Data::getEntPropList();

		}
		/**
		 * Gets an instance of the new ECash_API for inserting apps
		 * @todo This is harded coded to AALM --- FIIIIIX!
		 * @param string $mode
		 * @return ECash_API
		 */
		protected static function getAPI($mode, $property_short)
		{
			switch (strtolower($property_short))
			{
				default:
				case 'generic':
					$path = '/virtualhosts/ecash_generic';
					$class = 'GENERIC_API';
					break;
			}
			
			// gForge [#10345] Receiving multiple apps from the same customer [MP]
			// This ticket was caused because this used to be a singleton and when it
			// reused the class there was data persisting resulting in customers being
			// linked incorrectly.  It worked fine when it was just inserting, but with
			// the addition of unsigned apps, apps were synced and then updated, and during
			// the update cusotmer_ids were getting jacked.
			if (strpos(get_include_path(), $path) === FALSE)
			{
				// Since this code is ran over and over in a loop to sync apps, we dont want
				// to keep adding the same thing to the path every time, so if the $path isnt
				// already in our include path, we need to add it.
				ini_set('include_path', get_include_path().":$path:/virtualhosts/ecash_common");
			}
			if (class_exists($class))
			{
				return new $class($mode);
			}
			else
			{
				throw new Exception(__CLASS__.'::'.__METHOD__.' - Could not load class '.$class);
			}
		}

		/**
		 * Get an instance of Security_ICrypt_1 for decrypting session data
		 *
		 * @param string $mode
		 * @return crypt
		 */
		protected static function getCrypt($mode)
		{
			if (!self::$crypt)
			{
				$config = Crypt_Config::Get_Config($mode);
				self::$crypt = Crypt_Singleton::Get_Instance($config['KEY'], $config['IV']);
			}
			return self::$crypt;
		}

		/**
		 * Insert an application using the "new" API
		 * @param array $data
		 * @param bool $send_email
		 * @return int application_id
		 */
		public function Create_Transaction($data, $send_email = TRUE)
		{
			if (is_array($this->data))
			{
				$this->data = array_merge($data, $this->data);
			}
			else
			{
				$this->data = $data;
			}
			$crypt = self::getCrypt(BFW_MODE);
			$api = self::getAPI(BFW_MODE, $data['property_short']);
			$olp_agent_id = ECash_API::getAgentID('olp');
			$company_id = ECash_API::getCompanyID(strtolower($data['property_short']));
			// get marketing and enterprise site IDs
			$source_id = ECash_API::getSiteID($data['config']->license, $data['config']->site_name);
			$ent_id = ECash_API::getSiteID($data['ent_config']->license, $data['ent_config']->site_name);

			//Grab the SSN if its in there
			if (isset($data['social_security_number']) && is_numeric($data['social_security_number']))
			{
				$ssn = $data['social_security_number'];
			}
			// if we didn't find it try decrypting it from the session  
			elseif (isset($data['social_security_number_encrypted']))
			{
				$ssn = $crypt->decrypt($data['social_security_number_encrypted']);
			}
			// We have nothing
			else
			{
				throw new Exception("No SSN for {$data['application_id']}");
			}
			
			if (isset($data['dob']))
			{
				$dob = strtotime($data['dob']);
				$dob = (is_numeric($dob)) ? $dob : NULL;				
			}
			// If it wasn't in 'dob' or we couldn't get the timestamp try
			// and decrypt it from the session data.
			if (empty($dob) && isset($data['dob_encrypted']))
			{
				$dob = strtotime($crypt->decrypt($data['dob_encrypted']));
			}
			// Everything failed 
			if (!is_numeric($dob))
			{
				throw new Exception("No DOB for {$data['application_id']}");
			}
			
			if (isset($data['bank_aba']) && is_numeric($data['bank_aba']))
			{
				$bank_aba = $data['bank_aba'];
			}
			elseif (isset($data['bank_aba_encrypted']))
			{
				$bank_aba = $crypt->decrypt($data['bank_aba_encrypted']);	
			}
			else 
			{
				throw new Exception("No bank aba for {$data['application_id']}");
			}
			
			if (isset($data['bank_account']))
			{
				$bank_account = $data['bank_account'];
			}
			elseif (isset($data['bank_account_encrypted']))
			{
				$bank_account = $crypt->decrypt($data['bank_account_encrypted']);
			}
			
			$app = array(
				ECash_API::INDEX_APPLICATION =>
				array(
					//'application_status_id' => //this will be set by the status history list
					'application_id' => $data['application_id'],
					'company_id' => $company_id,
					'application_status_id' => ECash_API::getApplicationStatusID('pending', isset($data['preact'])),
					'ip_address' => $data['client_ip_address'],
					'name_first' => $data['name_first'],
					'name_last' => $data['name_last'],
					'email' => $data['email_primary'],
					'phone_home' => $data['phone_home'],
					'phone_work' => $data['phone_work'],
					'phone_cell' => $data['phone_cell'],
					'phone_work_ext' => (empty($data['ext_work']) ? NULL : $data['ext_work']),
					'call_time_pref' => strtolower($data['bast_call_time']),
					'street' => $data['home_street'],
					'unit' => $data['home_unit'],
					'city' => $data['home_city'],
					'state' => $data['home_state'],
					'zip_code' => $data['home_zip'],
					'employer_name' => $data['employer_name'],
					'date_hire' => date('Y-m-d', strtotime('-3 months')), //this one is brilliant
					'legal_id_number' => $data['state_id_number'],
					'legal_id_state' => ($data['state_issued_id'] ? $data['state_issued_id'] : $data['home_state']),
					'legal_id_type' => 'dl',
					'income_direct_deposit' => ((strtoupper($data['income_direct_deposit']) == 'TRUE') ? 'yes' : 'no'),
					'income_source' => $data['income_type'],
					'income_frequency' => $data['paydate_model']['income_frequency'],
					'bank_name' => $data['bank_name'],
					'bank_account_type' => $data['bank_account_type'],
					'income_monthly' => $data['income_monthly_net'],
					'ssn' => $ssn, //decrypt,
					'dob' => $dob,
					'bank_aba' => $bank_aba,
					'bank_account' => $bank_account, //decrypt
					'paydate_model' => $data['paydate_model']['model_name'],
					'olp_process' => $data['olp_process'],
					'track_id' => $data['track_key'],
					'phone_fax' => (empty($data['phone_fax']) ? NULL : $data['phone_fax']),
					'application_type' => 'paperless',
					'date_fund_estimated' => strtotime($data['qualify_info']['fund_date']),
					'date_first_payment' => strtotime($data['qualify_info']['payoff_date']),
					'fund_qualified' => $data['qualify_info']['fund_amount'],
					'finance_charge' => $data['qualify_info']['finance_charge'],
					'payment_total' => $data['qualify_info']['total_payments'],
					'apr' => $data['qualify_info']['apr'],
					'income_monthly' => (isset($data['qualify_info']['monthly_net']) ?
									 $data['qualify_info']['monthly_net'] :
									 $data['income_monthly_net']),
					'is_react' => ($data['is_react'] == 1 ? 'yes' : 'no'),
					'pwadvid' => (isset($data['pwadvid']) ? $data['pwadvid'] : NULL),
					'agent_id' => $olp_agent_id,
					'modifying_agent_id' => $olp_agent_id,
					'enterprise_site_id' => $ent_id,
					),
				ECash_API::INDEX_PERSONAL_REFERENCE =>
				array(
					array('name_full' => $data['ref_01_name_full'],
						  'phone_home' => $data['ref_01_phone_home'],
						  'relationship' => $data['ref_01_relationship'],
						  'date_created' => time(),
						  ),
					array('name_full' => $data['ref_02_name_full'],
						  'phone_home' => $data['ref_02_phone_home'],
						  'relationship' => $data['ref_02_relationship'],
						  'date_created' => time(),
						  )
					),
				ECash_API::INDEX_DEMOGRAPHICS =>
				array(
					'date_created' => time(),
					'has_income' => (strtoupper($data['income_stream']) == 'TRUE' ? 'yes' : 'no'),
					'has_minimum_income' => (strtoupper($data['monthly_1200']) == 'TRUE' ? 'yes' : 'no'),
					'has_checking' => (strtoupper($data['checking_account']) == 'TRUE' ? 'yes' : 'no'),
					'minimum_age' => (strtoupper($data['citizen']) == 'TRUE' ? 'yes' : 'no'),
					'opt_in' => (strtoupper($data['offers']) == 'TRUE' ? 'yes' : 'no'),
					'us_citizen' => (strtoupper($data['citizen']) == 'TRUE' ? 'yes' : 'no'),
					'ca_resident_agree' => (strtolower($data['cali_agree']) == 'agree' ? 'yes' : 'no'),
					'email_agent_created' => (strtoupper($data['email_agent_created']) == 'TRUE' ? 'yes' : 'no'),
					'tel_app_proc' => (strtoupper($data['tel_app_proc']) == 'TRUE' ? 'yes' : 'no')
					),
				);

			foreach($data['campaign_info'] as $ci)
			{
				$app[ECash_API::INDEX_CAMPAIGN_INFO][] = array(
					'date_created' => $ci['modified_date'],
					'site_id' => $source_id,
					'promo_id' => ($ci['promo_id'] ? $ci['promo_id'] : 10000),
					'promo_sub_code' => $ci['promo_sub_code'],
					'reservation_id' => (!empty($ci['reservation_id']) ? $ci['reservation_id'] : NULL)
				);
			}

			foreach($data['status_times'] as $status)
			{
				$app[ECash_API::INDEX_STATUS_HISTORY][] = array(
					'date_created' => $status['date'],
					'application_status_id' => ECash_API::getApplicationStatusID($status['name'], isset($data['preact']))
				);
			}

			if(empty($data['authentication']['received_package']))
			{
				//take care of this requirement here, since it's skipped for development
				//if($this->mode != 'LOCAL')
				//	throw new Exception("BureauInquiry is required).");
			}
			else
			{
				$app[ECash_API::INDEX_BUREAU_INQUIRY] = ECash_API::getBureauInquiryData($data['authentication']);
			}

			if($data['card'])
			{
				$app[ECash_API::INDEX_CARD] = ECash_API::getCardData($data['card']);
			}

			//Set up the DB
			$db = ECash_Config::getMasterDbConnection();

			ECash_API::loadPaydateModelData($app[ECash_API::INDEX_APPLICATION], $data['paydate_model']);

			$customer = ECash_Models_Customer::getByCompanyExisting($data['property_short'], $data['application_id'], $ssn);
			if($customer !== NULL)
			{
				$app[ECash_API::INDEX_APPLICATION]['customer_id'] = $customer->customer_id;
			}
			else
			{
				$customer = ECash_API::getNewCustomerData($company_id, $olp_agent_id, $ssn, $data['name_first'], $data['name_last']);
				$app[ECash_API::INDEX_CUSTOMER] = $customer;
			}

			// fetch the asynch result by OLP application ID
			if (!is_object($data['asynch_result']))
			{
				$data['asynch_result'] = $this->getAsynchResult($data['application_id']);
			}
			$app[ECash_API::INDEX_ASYNCH_RESULT] = $data['asynch_result'];
			$doc_list = ECash_Config::getFactory()->getReferenceList('DocumentList');
			$doc_list_id = $doc_list->toId('loan_document');
			$archive_id= (isset($_SESSION['condor_data']['archive_id'])) ? $_SESSION['condor_data']['archive_id'] : $data['condor_doc_id'];
			if (is_numeric($archive_id))
			{
				$app[ECash_API::INDEX_DOCUMENT_EVENT] = array(
            		'date_created' => date('Y-m-d H:i:s'),
            		'company_id' => $company_id,
            		'application_id' => $data['application_id'],
            		'document_list_id' => $doc_list_id,
            		'document_method' => 'olp',
            		'transport_method' => 'web',
            		'agent_id' => $olp_agent_id,
            		'document_event_type' => 'sent',
            		'archive_id' => $archive_id
        		);
			}
			
			$db->beginTransaction();
			try
			{
				$old_timezone = FALSE;
				if(phpversion() >= 5.1)
				{
					if($tz = eCash_Config::getInstance()->TIME_ZONE)
					{
						$old_timezone = date_default_timezone_get();
						date_default_timezone_set($tz);
					}
				}
				$is_update = FALSE;
				$model = ECash::getInstance()->getApplicationById($data['application_id']);
				if (is_object($model) && is_numeric($model->application_id))
				{
					$app_id = $api->updateApplication($model, $app);
					$is_update = TRUE;
				}
				else 
				{
					$app_id = $api->insertApplication($app);
				}
				
				if ($send_email && (
					// If it's not ecash app react, send mail at agreed
					($data['olp_application_type'] == 'AGREED' && !$this->isEcashAppReact()) ||
					// If it's ecash app react, send on initial insert
					(!$is_update && $this->isEcashAppReact())
				))
				{
					define('PASSWORD_ENCRYPTION','ENCRYPT');
					if(is_object($customer))
					{
						$this->data['username'] = $customer->login;
						$this->data['password'] = Security_8::Decrypt_Password($customer->password);
					}
					else
					{
						$this->data['username'] = $customer['login'];
						$this->data['password'] = Security_8::Decrypt_Password($customer['password']);
					}
					if ($old_timezone !== FALSE)
					{
						date_default_timezone_set($old_timezone);
					}
					$this->Mail_Confirmation();					
				}
				$db->commit();
			}
			catch (Exception $e)
			{
				if ($old_timezone !== FALSE)
				{
					date_default_timezone_set($old_timezone);
				}
				$db->rollBack();
				throw $e;
			}

			return $app_id;
		}

		protected function isEcashAppReact()
		{
			return (!empty($this->data['ecashapp']) || $this->data['olp_process'] == 'ecashapp_react');
		}
		
		/**
		 * Load the most recent asynch result object for
		 * an application id. Either Returns an Asynch_Result_Object
		 * or something FALSE if it couldnt' find one.
		 * 
		 * @param int $app_id
		 * @return mixed
		 * */
		
		protected function getAsynchResult($app_id)
		{
			$return = false;
			$db = Setup_DB::Get_Instance('blackbox', BFW_MODE);
			if (is_numeric($this->data['winning_target_id']))
			{
				$query = "SELECT
					asynch_result.asynch_result_object
				FROM
					asynch_result
				WHERE
					asynch_result.application_id='{$app_id}'
				and 
					target_id = '{$this->data['winning_target_id']}'
				ORDER BY
					asynch_result.date_created DESC
				LIMIT 1
				";
			}
			else
			{
				$props = EnterpriseData::getAliases($this->data['property_short']);
				$props[] = $this->data['property_short'];
				$query = "SELECT
					asynch_result.asynch_result_object
				FROM
					asynch_result
				JOIN target USING (target_id)
				WHERE
					asynch_result.application_id='{$app_id}'
				and target.property_short IN ('".implode("','", $props)."')
				ORDER BY
					asynch_result.date_created DESC
				LIMIT 1;
				";
			}
			$res = $db->Query($db->db_info['db'], $query);
			if ($row = $db->Fetch_Object_Row($res))
			{
				$return = unserialize(gzuncompress($row->asynch_result_object));
			}
			else
			{
				return $this->oldGetAsynchResult($app_id);
			}
			
			return $return;
		}
		
		/**
		 * The old way of finding an AsynchResult which is only 
		 * used for applications that were created before the new way was 
		 * pusehd, but synched after
		 *
		 * @param unknown_type $app_id
		 * @return unknown
		 */
		protected function oldGetAsynchResult($app_id)
		{
			$return = FALSE;
			$db = Setup_DB::Get_Instance('blackbox', BFW_MODE);
			$query = "SELECT
				asynch_result_object
			FROM
				asynch_result
			WHERE
				application_id='{$app_id}'
			ORDER BY
				date_created DESC
			LIMIT 1	";
			$res = $db->Query($db->db_info['db'], $query);
			if ($row = $db->Fetch_Object_Row($res))
			{
				$return = unserialize(gzuncompress($row->asynch_result_object));
			}
			return $return;
		}
		
	}

?>
