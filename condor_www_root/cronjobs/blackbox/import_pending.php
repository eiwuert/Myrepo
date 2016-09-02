<?php
/** Import pending *and* confirmed applications from OLP/BlackBox to
 *  LDB/eCash.and updates statuses in OLP/BlackBox if confirmation
 *  occurs in LDB/eCash
 *
 *  This version does not currently do a fraud check.
 *
 * @author Brian Feaver (modified by Justin Foell)
 * @copyright Copyright 2006 Selling Source, Inc.
 */

define('CHECK_FRAUD', FALSE);

ini_set('include_path', ini_get('include_path') . ':/virtualhosts/libolution:/virtualhosts/ecash_aalm:/virtualhosts/ecash_common');
$app_file = NULL;
require_once 'import_async.php';
require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');
require_once 'libolution/AutoLoad.1.php';

class Import_Pending extends Import_Async
{
	private $api;
	protected $lock_file_path = '/tmp/importpending';

	/**
	 * Import_LDB constructor.
	 *
	 * @TODO the API should probably be obtained from the Factory (for different companies) [JustinF]
	 */
	public function __construct($mode, array $properties = array())
	{
		parent::__construct($mode, $properties);
		$this->api = new AALM_API($this->mode);
	}

        protected function Get_Property_Short($application_id)
        {
    		 $application_id = (int)$application_id;
      
                $query = "SELECT
                                   t.property_short
                                  FROM
                                   target as t,
                                   application as a
                                  WHERE a.target_id = t.target_id
                                        AND a.application_id = " . $application_id;
    
                $result = $this->QueryOLP($query);
    
                $p = $this->olp_sql->Fetch_Object_Row($result);

                $returnPropertyShort = $p->property_short;
                return $returnPropertyShort;
        }

	/**
	 * Most of the setup was copied from olp.mysql.class.php in BFW
	 */
	protected function Insert_Transaction($data, $send_email = TRUE)
	{
		//die(print_r($data, TRUE). PHP_EOL);
		$ssn = $this->crypt_object->decrypt($data['social_security_number_encrypted']);
		$olp_agent_id = ECash_API::getAgentID('olp');
		$company_id = ECash_API::getCompanyID($data['property_short']);

		// get marketing and enterprise site IDs
		$source_id = ECash_API::getSiteID($data['config']->license, $data['config']->site_name);
		$ent_id = ECash_API::getSiteID($data['ent_config']->license, $data['ent_config']->site_name);

		$app = array(
			ECash_API::INDEX_APPLICATION =>
			array(
				//'application_status_id' => //this will be set by the status history list
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
				'dob' => strtotime($this->crypt_object->decrypt($data['dob_encrypted'])), //decrypt
				'bank_aba' => $this->crypt_object->decrypt($data['bank_aba_encrypted']), //decrypt
				'bank_account' => $this->crypt_object->decrypt($data['bank_account_encrypted']), //decrypt
				'paydate_model' => $data['paydate_model']['model_name'],
				'olp_process' => $data['olp_process'],
				'application_id' => $data['application_id'],
				'track_id' => $data['track_key'],
				'company_id' => $company_id,
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
			$app[ECash_API::INDEX_STATUS_HISTORY][] =
				array(
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
		if(is_null($customer))
		{
			$customer = ECash_API::getNewCustomerData($company_id, $olp_agent_id, $ssn, $data['name_first'], $data['name_last']);
		}

		$olp_ldb = OLP_LDB::Get_Object($data['property_short'], NULL, $data);
		$app[ECash_API::INDEX_CUSTOMER] = $customer;
		$doc_list = ECash_Config::getFactory()->getReferenceList('DocumentList');
		
		$doc_list_id = $doc_list->toId('loan_document');
		$archive_id= (isset($_SESSION['condor_data']['archive_id'])) ? $_SESSION['condor_data']['archive_id'] : $data['condor_doc_id'];

		$app[ECash_API::INDEX_DOCUMENT_EVENT] = array(
			'date_created' => time(),
			'company_id' => $company_id,
			'application_id' => $data['application_id'],
			'document_list_id' => $doc_list_id,
			'document_method' => 'olp',
			'transport_method' => 'web',
			'agent_id' => $olp_agent_id,
			'document_event_type' => 'sent',
			'archive_id' => $archive_id
		);
		// fetch the asynch result by OLP application ID
		$app[ECash_API::INDEX_ASYNCH_RESULT] = $this->getAsynchResult($data['application_id']);
		$db->beginTransaction();
		try
		{
			$this->api->insertApplication($app);
			if($send_email)
			{
				require_once('security.8.php');
				define('PASSWORD_ENCRYPTION','ENCRYPT');
				if(is_object($customer))
				{
					$data['username'] = $customer->login;
					$data['password'] = Security_8::Decrypt_Password($customer->password);
				}
				else
				{
					$data['username'] = $customer['login'];
					$data['password'] = Security_8::Decrypt_Password($customer['password']);
				}
				if(is_object($olp_ldb))
				{
					$olp_ldb->Mail_Confirmation();
				}
			}
			$db->commit();
		}
		catch (Exception $e)
		{
			$db->rollBack();
			throw $e;
		}
	}

	protected function Update_OLP_Application($application_id, $status, $fund_amount, $pay_dates)
	{
		//back date changes in OLP if neccessary
	}

	/**
	 * Sets up our OLP and LDB database connections.
	 */
	protected function Setup_LDB_DB($prop_short = null)
	{
		//do nothing, we'll call the eCash API and it will take care of this crap
		echo 'not setting up LDB (will use API)', PHP_EOL;
	}

	protected function Fraud_Check($data)
	{
		echo 'skipping fraud check', PHP_EOL;
		return FALSE;
	}

	protected function getAsynchResult($app_id)
	{
		$log = $this->getApplog();
		$query = "
			SELECT asynch_result_object
			FROM asynch_result
			WHERE application_id = '{$app_id}'
		";
		$r = $this->QueryOLP($query);

		if ($row = $this->olp_sql->Fetch_Object_Row($r))
		{
			// see vendor_post_impl_cfe.php::SaveAsynchResult()
			$res = unserialize(gzuncompress($row->asynch_result_object));
			return $res;
		}
		return FALSE;
	}

	/**
	 * Gets an applog instance
	 *
	 * @return Applog
	 */
	protected function getApplog()
	{
		if (!$this->applog)
		{
			return $this->applog = Applog_Singleton::Get_Instance('ldb', 1000000, 20, 'Import LDB Pending', TRUE);
		}
		return $this->applog;
	}
}


?>
