<?php

if(!defined('PASSWORD_ENCRYPTION')) DEFINE('PASSWORD_ENCRYPTION',TRUE);

require_once(BFW_CODE_DIR.'OLPSecurity.php');


abstract class OLP_LDB
{

	protected $mysql;
	protected $property_short;

	protected $database;
	protected $data;
	protected $insert_ids;

	protected $replace;

	protected $hash_key = 'l04ns';
	protected $errors = array();

	protected $ent_prop_list = array();

		// required table inserts prior to inserting transaction
	protected $required_tables = array(
		'Customer',
		'Site',
		'Application',
		'Campaign_Info',
		'Personal_Reference',
		'Bureau_Inquiry',
		'Card'
	);


		// post transaction inserts
	protected $post_transaction_inserts = array(
		'CSR_Complete',
		'Demographics'
	);

	protected $loan_type_id;
	protected $rule_set_id;
	protected $loan_type;
	protected $company_ids;

	public function __construct(&$mysql, $property_short = null, $data = array())
	{
		$this->mysql = $mysql;
		$this->property_short = Enterprise_Data::resolveAlias($property_short);

		$this->replace = array("\n", "\r");

		if(!empty($data))
		{
			$this->data = $data;
		}

		if($_SESSION['config']->use_new_process)
		{
			$this->required_tables[] = 'Statuses';
			$this->required_tables[] = 'Document_Event';
		}
	}

	protected function isEcashAppReact()
	{
		return (!empty($this->data['ecashapp']) || $this->data['olp_process'] == 'ecashapp_react');
	}

	public static function Get_Object($prop, $db = null, $data = array())
	{
		if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $prop))
		{
			$class = 'Impact_LDB';
		}
		elseif(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_AGEAN, $prop))
		{
			$class = 'Agean_LDB';
		}
		elseif(Enterprise_Data::isCFE($prop))
		{
			$class = 'CFE_LDB';
		}
		elseif(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_GENERIC, $prop))
		{
			$class = 'Entgen_LDB';
		}
		else
		{
			$class = 'CLK_LDB';
		}

		require_once(OLP_DIR . 'olp_ldb/' . strtolower($class) . '.php');

		if(empty($db))
		{
			$db = Setup_DB::Get_Instance('mysql', BFW_MODE, $prop);
		}

		return new $class($db, $prop, $data);
	}

	/**
	* @return string: transaction id
	* @desc function to insert transaction record and all relating tables
	*		will insert all of the required tables before creating the transaction record
	*		will also run all the post transaction inserts
	**/
	public function Create_Transaction($data, $send_email = TRUE)
	{
		// clear errors
		$this->errors = array();

		// set data var and normalize data
		$this->data = $this->Process_Data($data);

		$this->Initialize();

		try
		{
			// start transaction set autocommit to false
			$this->mysql->Start_Transaction();

			// run the Insert function for each of the required tables
			foreach($this->required_tables as $table)
			{
				$insert_info = $this->{'Insert_'.$table}();

				if(!is_null($insert_info))
				{
					if(!empty($insert_info['table']))
					{
						$insert_id = $this->Insert_Record($insert_info['table'], $insert_info['data'], FALSE, $insert_info['insert_id_column']);
					}

					$this->insert_ids[strtolower($table)] = (isset($insert_info['insert_id']))
															? $insert_info['insert_id']
															: $insert_id;
				}
			}

			$this->mysql->Commit();
		}
		catch(MySQL_Exception $e)
		{
			$this->mysql->Rollback();
			throw $e;
		}

		if($this->insert_ids['application'])
		{

			// run the Insert functions for post transaction inserts
			foreach($this->post_transaction_inserts as $table)
			{

				try
				{
					// start transaction set autocommit to false
					$this->mysql->Start_Transaction();

					$insert_info = $this->{'Insert_'.$table}();
					if(!is_null($insert_info))
					{
						if(!empty($insert_info['table']))
						{
							$insert_id = $this->Insert_Record($insert_info['table'], $insert_info['data']);
						}

						$this->insert_ids[strtolower($table)] = (isset($insert_info['insert_id']))
																? $insert_info['insert_id']
																: $insert_id;
					}


					$this->mysql->Commit();
				}
				catch(MySQL_Exception $e)
				{
					$this->mysql->Rollback();
					throw $e;
				}
			}

		}

		//Mail out confirmation email
		if($send_email)
		{
			try
			{
				$this->Mail_Confirmation();
			}
			catch(Exception $e)
			{
				//throw new Exception("Problem sending I agree email with app: " . $this->data['application_id'] . "\n" . $e->getMessage());
			}
		}

		return $this->insert_ids['application'];
	}


	protected function Get_Loan_Type_And_Rule_Set()
	{
		if(!empty($this->property_short) && !empty($this->loan_type))
		{
			$query = "SELECT
					lt.loan_type_id,
					rs.rule_set_id
				FROM loan_type lt
				INNER JOIN rule_set rs USING (loan_type_id)
				WHERE lt.company_id = (
						SELECT company_id
						FROM company
						WHERE name_short = '{$this->property_short}'
						AND active_status = 'active'
					)
					AND lt.name_short = '{$this->loan_type}'
					AND lt.active_status = 'active'
				ORDER BY rs.date_effective DESC LIMIT 1";

			$result = $this->mysql->Query($query);

			if($result->Row_Count() > 0 && ($row = $result->Fetch_Object_Row()))
			{
				$this->loan_type_id = $row->loan_type_id;
				$this->rule_set_id = $row->rule_set_id;
			}
		}
	}

	/**
	* @return transaction_id
	* @desc insert transaction record
	*
	**/
	protected function Insert_Application()
	{
		$field_array = array();

		$this->Get_Loan_Type_And_Rule_Set();

		$field_array['date_modified']	= 'NOW()';
		$field_array['date_created']	= 'NOW()';
		$field_array['company_id']		= $this->Company_ID($this->property_short);
		$field_array['application_id']	= $this->data['application_id'];
		$field_array['customer_id']		= $this->insert_ids['customer'];
		$field_array['track_id']		= $this->data['track_key'];
		$field_array['loan_type_id']	= $this->loan_type_id;
		$field_array['rule_set_id']		= $this->rule_set_id;
		$field_array['enterprise_site_id']	= $this->insert_ids['originating_source_id'];
		$field_array['modifying_agent_id']	= 'olp';
		$field_array['application_status_id']	= ($this->Is_Preact()) ? 'preact_pending' : 'pending';

		// GForge #5632 - Use is_react column from database to determine if is a react or not. [RM]
//		$field_array['is_react']		= (isset($this->data['react']) || isset($this->data['reckey'])) ? 'yes' : 'no';
		$field_array['is_react']		= $this->data['is_react'] ? 'yes' : 'no';
		$field_array['application_type']= 'paperless';
		$field_array['ip_address']		= $this->data['client_ip_address'];

		$field_array['bank_name']			= mysql_escape_string(stripslashes($this->data['bank_name']));
		$field_array['bank_aba']			= $this->data['bank_aba'];
		$field_array['bank_account']		= $this->data['bank_account'];
		$field_array['bank_account_type']	= $this->data['bank_account_type'];
		$field_array['date_fund_estimated']	= $this->data['qualify_info']['fund_date'];
		$field_array['date_first_payment']	= $this->data['qualify_info']['payoff_date'];
		$field_array['fund_qualified']		= $this->data['qualify_info']['fund_amount'];
		$field_array['finance_charge']		= $this->data['qualify_info']['finance_charge'];
		$field_array['payment_total']		= $this->data['qualify_info']['total_payments'];
		$field_array['apr']					= $this->data['qualify_info']['apr'];
		$field_array['income_monthly']		= (isset($this->data['qualify_info']['monthly_net']))
												? $this->data['qualify_info']['monthly_net']
												: $this->data['income_monthly_net'];
		$field_array['income_source']		= $this->data['income_type'];
		$field_array['income_direct_deposit']	= (strtoupper($this->data['income_direct_deposit']) == 'TRUE') ? 'yes' : 'no';
		$field_array['income_frequency']	= $this->data['paydate_model']['income_frequency'];
		$field_array['paydate_model']		= $this->data['paydate_model']['model_name'];


		$field_array['legal_id_type']		= 'dl';
		$field_array['legal_id_number']		= $this->data['state_id_number'];
		$field_array['legal_id_state']		= ($this->data['state_issued_id'])
												? $this->data['state_issued_id']
												: $this->data['home_state'];

		$field_array['email']				= $this->data['email_primary'];
		$field_array['name_last']			= $this->data['name_last'];
		$field_array['name_first']			= $this->data['name_first'];
		$field_array['name_middle']			= $this->data['name_middle'];
		$field_array['dob']					= $this->data['date_dob_y'] . '-' .
											  $this->data['date_dob_m'] . '-' .
											  $this->data['date_dob_d'];
		$field_array['ssn']					= $this->data['social_security_number'];

		$field_array['street']				= mysql_escape_string(stripslashes($this->data['home_street']));
		$field_array['unit']				= mysql_escape_string(stripslashes($this->data['home_unit']));
		$field_array['city']				= mysql_escape_string(stripslashes($this->data['home_city']));
		$field_array['state']				= $this->data['home_state'];
		$field_array['zip_code']			= $this->data['home_zip'];
		$field_array['phone_home']			= $this->data['phone_home'];
		$field_array['phone_cell']			= $this->data['phone_cell'];
		$field_array['phone_fax']			= (empty($this->data['phone_fax'])) ? 'NULL' : $this->data['phone_fax'];
		$field_array['call_time_pref']		= strtolower($this->data['best_call_time']);
		$field_array['employer_name']		= mysql_escape_string(stripslashes($this->data['employer_name']));
		$field_array['date_hire']			= date('Y-m-d', strtotime('-3 months'));
		$field_array['phone_work']			= $this->data['phone_work'];
		$field_array['phone_work_ext']		= (empty($this->data['ext_work'])) ? 'NULL' : $this->data['ext_work'];

		if(isset($this->data['pwadvid']))
		{
			$field_array['pwadvid'] = $this->data['pwadvid'];
		}

		//$field_array['banking_start_date'] = date('Y-m-d', strtotime('-6 months'));
		//$field_array['residence_start_date'] = date('Y-m-d', strtotime('-2 years'));


		// only store these dates if the user didn't create the model and the system did
		if(empty($this->data['paydate_model']['user_generated'])
			&& isset($this->data['pay_date1'])
			&& isset($this->data['pay_date2']))
		{
			$field_array['income_date_soap_1'] = $this->data['pay_date1'];
			$field_array['income_date_soap_2'] = $this->data['pay_date2'];
		}


		// day string to day int array
		$days = array('sun' => 1, 'mon' => 2, 'tue' => 3, 'wed' => 4, 'thu' => 5, 'fri' => 6, 'sat' => 7);

		$pd_model = $this->data['paydate_model'];
		if(isset($pd_model['day_of_week']))
		{
			$field_array['day_of_week'] = $pd_model['day_of_week'] + 1;
		}
		elseif(isset($pd_model['day_string_one']))
		{
			if(is_numeric($pd_model['day_string_one']))
			{
				$field_array['day_of_week'] = $pd_model['day_string_one'] + 1;
			}
			else
			{
				$field_array['day_of_week'] = strtolower($days[strtolower($pd_model['day_string_one'])]);
			}
		}

		$pd_map = array(
			'next_pay_date'	=> 'last_paydate',
			'day_int_one'	=> 'day_of_month_1',
			'day_int_two'	=> 'day_of_month_2',
			'week_one'		=> 'week_1',
			'week_two'		=> 'week_2'
		);

		foreach($pd_map as $model_key => $field)
		{
			if(isset($pd_model[$model_key]))
			{
				$field_array[$field] = $pd_model[$model_key];
			}
		}


		if($this->Is_Preact())
		{
			$field_array['olp_process'] = 'ecashapp_preact';
		}
		elseif(isset($this->data['olp_process']))
		{
			$field_array['olp_process'] = $this->data['olp_process'];
		}
        elseif(isset($this->data['ecashapp']))
        {
			$field_array['olp_process'] = 'ecashapp_react';
        }
        elseif(isset($this->data['reckey']))
        {
        	// Based on the Email React Promo ID do we set if this is a email react or a cs_react [RL]
        	$field_array['olp_process'] = (in_array($this->data['campaign_info'][0]['promo_id'],array(26181,26182,26183,26184,26185,28155)))
											? 'email_react'
											: 'cs_react';
        }
        else
        {
			//All other processes are brand new loan confirmations and should be marked as such [RL]
			$field_array['olp_process'] = (isset($_SESSION['config']->online_confirmation))
											? 'online_confirmation'
											: 'email_confirmation';
        }


		// ec3 additions
		//$field_array['loan_type_id'] = $this->data['config']->loan_type_id;
		//$field_array['rule_set_id'] = $this->data['config']->rule_set_id;

		return array(
			'table' => 'application',
			'data' => $field_array,
			'insert_id' => $this->data['application_id'],
			'insert_id_column' => 'application_id',
		);
	}

	/**
	 * Insert Bureau Inquiry
	 *
	 * Inserts DataX responses
	 */
	protected function Insert_Bureau_Inquiry()
	{
		foreach($this->data['authentication'] as $title => $dx_entry)
		{
			if(substr($title, 0, 6) != 'DATAX_') continue;
			$type = substr($title, 6);

			$field_array = array();
			$field_array['date_modified']	= 'NOW()';
			$field_array['date_created']	= 'NOW()';
			$field_array['company_id']		= $this->company_id;
			$field_array['application_id']	= $this->insert_ids['application'];
			$field_array['bureau_id']		= 'datax';
			$field_array['inquiry_type']	= strtolower($type);
			$field_array['sent_package']	= $this->mysql->Escape_String($this->data['authentication']['DATAX_' . $type]['sent_package']);
			$field_array['received_package']= $this->mysql->Escape_String($this->data['authentication']['DATAX_' . $type]['received_package']);

			$field_array['trace_info']		=  $this->data['authentication']['track_hash'];
			$field_array['outcome']			= $this->data['authentication']['DATAX_' . $type]['score'];

			if(strlen(trim($field_array['received_package'])) > 0)
			{
				$this->Insert_Record('bureau_inquiry', $field_array);
			}
		}
	}

	/**
	* @return string: insert id
	* @desc insert campaign record
	*
	**/
	public function Insert_Campaign_Info($application_id = null, $config = null)
	{
		$field_array = array();

		if(!empty($this->data['campaign_info']))
		{
			foreach($this->data['campaign_info'] as $campaign)
			{
				$field_array['date_modified']	= 'NOW()';
				$field_array['date_created']	= 'NOW()';
				$field_array['company_id']		= $this->company_id;
				$field_array['application_id']	= $this->insert_ids['application'];
				$field_array['promo_id']		= ($campaign['promo_id']) ? $campaign['promo_id'] : 10000;
				$field_array['promo_sub_code']	= $campaign['promo_sub_code'];
				$field_array['site_id']			= $this->insert_ids['referring_source_id'];
				$field_array['reservation_id']	= (!empty($campaign['reservation_id'])) ? $campaign['reservation_id'] : 'NULL';

				$insert_id[] = $this->Insert_Record('campaign_info', $field_array);
			}
		}
		elseif(isset($application_id) && isset($config))
		{

			$field_array['date_modified']	= 'NOW()';
			$field_array['date_created']	= 'NOW()';
			$field_array['company_id']		= $this->Company_ID($config->property_short);
			$field_array['application_id']	= $application_id;
			$field_array['promo_id']		= ($config->promo_id) ? $config->promo_id : 10000;
			$field_array['promo_sub_code']	= $config->promo_sub_code;
			$field_array['site_id']			= (isset($this->insert_ids['originating_source_id'])
												? $this->insert_ids['originating_source_id']
												: $this->Site_ID($config->license));

			$insert_id = $this->Insert_Record('campaign_info', $field_array);
		}
		else
		{
			$field_array['date_modified']	= 'NOW()';
			$field_array['date_created']	= 'NOW()';
			$field_array['company_id']		= $this->company_id;
			$field_array['application_id']	= $this->insert_ids['application'];
			$field_array['promo_id']		= ($this->data['promo_id']) ? $this->data['promo_id'] : 10000;
			$field_array['promo_sub_code']	= $this->data['promo_sub_code'];
			$field_array['site_id']			= $this->insert_ids['originating_source_id'];

			$insert_id = $this->Insert_Record('campaign_info', $field_array);
		}

		return array('insert_id' => $insert_id);

	}

	/**
	* @return string: insert id
	* @desc insert legal_id
	*
	**/
	protected function Insert_CSR_Complete()
	{
		//  Exception Insert if application is filled out by CSR
		if(isset($this->data['csr_complete']))
		{
			$exception = array(
				'date_created'	=> 'NOW()',
				'flag_type_id'	=> 'csr',
				'company_id'	=> $this->company_id,
				'application_id'=> $this->data['application_id']
			);

			return array('insert_id' => $this->Insert_Record('application_flag', $exception));
		}
	}


	/**
	* @return bool
	* @desc insert originating source
	*
	**/
	protected function Insert_Site()
	{
		// marketing
		// select originating source id, if it doesnt exist.. insert it and use the new id
		$query = "SELECT site_id FROM site WHERE license_key = '{$this->data['config']->license}' ORDER BY date_created LIMIT 1";
		$result = $this->mysql->Query($query);

		$source_result = $result->Fetch_Array_Row();
		$field_array['date_modified']	= 'NOW()';
		$field_array['date_created']	= 'NOW()';
		$field_array['name']			= $this->data['config']->site_name;
		$field_array['license_key']		= $this->data['config']->license;

		$this->insert_ids['referring_source_id'] = ($source_result['site_id'])
				? $source_result['site_id']
				: $this->Insert_Record('site', $field_array);

		// enterprise
		// select originating source id, if it doesnt exist.. insert it and use the new id
		$query = "SELECT site_id FROM site WHERE license_key = '{$this->data['ent_config']->license}' ORDER BY date_created LIMIT 1";
		$result = $this->mysql->Query($query);

		$source_result = $result->Fetch_Array_Row();
		$field_array['date_modified']	= 'NOW()';
		$field_array['date_created']	= 'NOW()';
		$field_array['name']			= $this->data['ent_config']->site_name;
		$field_array['license_key']		= $this->data['ent_config']->license;

		$this->insert_ids['originating_source_id'] = ($source_result['site_id'])
				? $source_result['site_id']
				: $this->Insert_Record('site', $field_array);
	}
	/**
	* @return string: insert id
	* @desc insert personal references
	*
	**/
	protected function Insert_Personal_Reference()
	{
		for($i = 1; $i <= 2; $i++)
		{
			if ($this->data["ref_0{$i}_name_full"])
			{
				$field_array['date_modified']	= 'NOW()';
				$field_array['date_created']	= 'NOW()';
				$field_array['company_id']		= $this->company_id;
				$field_array['application_id']	= $this->insert_ids['application'];
				$field_array['name_full']		= $this->data["ref_0{$i}_name_full"];
				$field_array['phone_home']		= $this->data["ref_0{$i}_phone_home"];
				$field_array['relationship']	= mysql_escape_string(stripslashes($this->data["ref_0{$i}_relationship"]));

				$insert_ids[$i] = $this->Insert_Record('personal_reference', $field_array);
			}
		}

		return array('insert_id' => $insert_ids);
	}


	/**
	* @desc inserts doc records
	*
	**/
	public function Document_Event($application_id, $property_short, $type = 'web')
	{
		$company_id = $this->Company_ID(Enterprise_Data::resolveAlias($property_short));

		$archive_id = (isset($_SESSION['condor_data']['archive_id']))
						? $_SESSION['condor_data']['archive_id']
						: $this->data['condor_doc_id'];

		if($type == 'fax')
		{
			$docs = array(
				array (
					'date_created'		=> 'NOW()',
					'company_id'		=> $company_id,
					'application_id'	=> $application_id,
					'document_list_id'	=> $this->Get_Condor_Template(),
					'document_method'	=> 'fax',
					'transport_method'	=> 'condor',
					'agent_id'			=> 'olp',
					'document_event_type'=> 'sent',
					'archive_id'		=> $archive_id,
					'system_id'			=> 3
				)
			);
		}
		else
		{
			/*
				If we've already signed the docs, we don't want to attempt to insert
				the other docs again (it would in fact fail). But for Impact we want
				to insert the Loan Documents after they've been signed.
			*/
			$docs = array(
				array (
					'date_created'		=> 'NOW()',
					'company_id'		=> $company_id,
					'application_id'	=> $application_id,
					'document_list_id'	=> $this->Get_Condor_Template(),
					'document_method'	=> 'olp',
					'transport_method'	=> 'web',
					'agent_id'			=> 'olp',
					'document_event_type'=> 'sent',
					'archive_id'		=> $archive_id,
					'system_id'			=> 3
				)
			);
		}

		foreach($docs as $doc)
		{
			$field_array['insert'] = $doc;
			$this->Insert_Record('document', $doc);
		}
	}

	public function Get_Condor_Template()
	{
		return 'Loan Document';
	}

	/**
	* @desc inserts doc records
	*
	**/
	public function Insert_Document_Event()
	{
		return $this->Document_Event($this->data['application_id'], $this->property_short);
	}

	/**
	* @return string: insert id
	* @desc insert demographics record
	*
	**/
	protected function Insert_Demographics()
	{
		$field_array['date_modified']		= 'NOW()';
		$field_array['date_created']		= 'NOW()';
		$field_array['company_id']			= $this->company_id;
		$field_array['application_id']		= $this->insert_ids['application'];
		$field_array['has_income']			= (strtoupper($this->data['income_stream']) == 'TRUE')		? 'yes' : 'no';
		$field_array['has_minimum_income']	= (strtoupper($this->data['monthly_1200']) == 'TRUE')		? 'yes' : 'no';
		$field_array['has_checking']		= (strtoupper($this->data['checking_account']) == 'TRUE')	? 'yes' : 'no';
		$field_array['minimum_age']			= (strtoupper($this->data['citizen']) == 'TRUE')			? 'yes' : 'no';
		$field_array['opt_in']				= (strtoupper($this->data['offers']) == 'TRUE')				? 'yes' : 'no';
		$field_array['us_citizen']			= (strtoupper($this->data['citizen']) == 'TRUE')			? 'yes' : 'no';
		$field_array['ca_resident_agree']	= (strtolower($this->data['cali_agree']) == 'agree')		? 'yes' : 'no';
		$field_array['email_agent_created']	= (strtoupper($this->data['email_agent_created']) == 'TRUE')? 'yes' : 'no';
		$field_array['tel_app_proc']		= (strtoupper($this->data['tel_app_proc']) == 'TRUE')		? 'yes' : 'no';

		return array('table' => 'demographics', 'data' => $field_array);
	}


	/**
	* @return array/object
	* @desc This just strtolower data, doesn't escape
	*
	**/
	protected function Process_Data($data)
	{

		// excluded date from being normalized
		$excluded_keys = array(
			'authentication',
			'track_key',
			'pwadvid',
		);


		foreach($data as $key => $sub_data)
		{
			if(!in_array($key, $excluded_keys))
			{
				if(is_array($sub_data) || is_object($sub_data))
				{
					if(is_object($data))
					{
						$escaped->{$key} = $this->Process_Data($sub_data);
					}
					else
					{
						$escaped[$key] = $this->Process_Data($sub_data);
					}
				}
				elseif(is_object($data))
				{
					$escaped->{$key} = strtolower($sub_data);
				}
				else
				{
					$escaped[$key] = strtolower($sub_data);
				}
			}
			else
			{
				$escaped[$key] = $sub_data;
			}
		}

		return $escaped;

	}

	protected function Initialize()
	{
		if(!empty($this->data))
		{
			if(isset($this->data['property_short']))
			{
				$this->property_short = strtoupper($this->data['property_short']);
				$this->company_id = $this->Company_ID($this->property_short);
			}

			$this->Get_Loan_Type();
		}
	}

	protected function Get_Loan_Type()
	{
		$this->loan_type = 'standard';
	}

	/**
	* @return bool
	* @desc updates done after a application has been completed
	*
	**/
	public function App_Completed_Updates($application_id)
	{
		return $this->Update_Application_Status('agree', $application_id);
	}

	/**
	* @return bool
	* @desc updates done after a application has been confirmed
	*
	**/
	public function App_Confirmed_Updates($application_id)
	{
		return $this->Update_Application_Status('confirmed', $application_id);
	}

	public function Update_Application_Status($status, $application_id, &$loan_action = null, $ecash_doc_id = null)
	{
		$history_id = FALSE;
		$status = strtolower($status);

		// make sure we have an app_id first
		if(isset($application_id))
		{
			// we do something special for this psuedo-status
			if($status !== 'ecash_sign_docs')
			{
				// translate our status to the eCash
				// status "path" (i.e., in the status tree)
				switch($status)
				{
					case 'verification':
					case 'underwriting':
					case 'fraud':
					case 'high_risk':
						// queue me up, baby!
						if($this->Is_Preact()) $status = 'preact';
						$path = "/applicant/$status/queued>";
						break;
					case 'denied':
						$path = "/applicant/$status>";
						break;
					case 'addl':
						$path = "/applicant/verification/$status>";
						break;
					case 'pending':
					case 'agree':
					case 'confirmed':
					case 'soft_fax':
						if($this->Is_Preact()) $status = 'preact_' . $status;
						$path = "/prospect/$status>";
						break;
					case 'confirm_declined':
					case 'disagree':
					case 'declined':
						$path = "/prospect/$status>";
						break;
					default:
						$path = '/prospect/pending>';

				}

				// get the actual status ID
				$status_id = $this->Status_Glob($path);

				// FALSE is returned on failure
				if (is_array($status_id) && count($status_id))
				{
					// returned as an array
					$status_id = reset($status_id);

					// update the database
					$query = "
						UPDATE application
						SET
							application_status_id = $status_id,
							modifying_agent_id = (
								SELECT agent_id
								FROM agent
								WHERE login = 'olp' AND active_status = 'active'
							)
						WHERE application_id = $application_id";
					$result = $this->mysql->Query($query);

					// get the new status history ID
					$query = "
						SELECT
							status_history_id,
							agent_id,
							application_status_id
						FROM
							status_history
						WHERE
							application_id = $application_id
						ORDER BY date_created DESC LIMIT 1";
					$result = $this->mysql->Query($query);

					if($result->Row_Count() < 1)
					{
						$history_id = null;
						$agent_id = null;
						$application_status_id = null;
					}
					else
					{
						$obj = $result->Fetch_Object_Row();
						$history_id = $obj->status_history_id;
						$agent_id = $obj->agent_id;
						$application_status_id = $obj->application_status_id;
					}
				}
			}
			else // 'ecash_sign_docs'
			{
				// set our document status as signed or something
				$query = "UPDATE document set signature_status='esig' WHERE document_id='$ecash_doc_id'";
				$result = $this->mysql->Query($query);

				// just return TRUE: we don't get a new
				// status_history_id here anyways
				$history_id = TRUE;
			}

			// Added from Ent_CS_MySQLi
			if(is_array($loan_action) && count($loan_action))
			{
				$values = array();

				foreach($loan_action as $action_name)
				{
					$values[] = "
						(
							(SELECT loan_action_id FROM loan_actions where name_short ='{$action_name}'),
							$application_id,
							$agent_id,
							$application_status_id
						)";
				}

				try
				{
					// do this all in one shot
					$query = 'INSERT INTO loan_action_history (loan_action_id, application_id,agent_id,application_status_id)
						VALUES '.implode(', ',$values);
					$this->mysql->Query($query);
				}
				catch (Exception $e)
				{
				}
			}
		}

		return $history_id;
	}

	/**
	 * @desc Inserts into loan_actions
	 */
	public function Insert_Loan_Action($name_short, $description)
	{
		$description = mysql_escape_string($description);
		$query = "
				INSERT INTO loan_actions
					(
						name_short,
						description,
						status,
						type
					)
				VALUES
					(
						'{$name_short}',
						'{$description}',
						'INACTIVE',
						'PRESCRIPTION'
					)";

		$this->mysql->Query($query);
	}

	/**
	* @return array An array containing the new username and password
	* @desc generates a new username and password combo for the customer table
	* and inserts it.
	**/
	protected function Insert_Customer()
	{
		//Check for existing logins using ssn
		$query = "SELECT c.customer_id, c.login, c.password
				  FROM customer AS c
				  INNER JOIN application AS a USING (customer_id)
				  WHERE c.company_id = {$this->company_id}
					AND (a.application_id = {$this->data['application_id']}
					  OR a.ssn = '{$this->data['social_security_number']}')
				  LIMIT 1";

		$result = $this->mysql->Query($query, $this->database);

		if($result->Row_Count() == 0)
		{
			//Check for existing logins using dob & email - GForge #8603 [DW]
			$query = "SELECT c.customer_id, c.login, c.password
					  FROM customer AS c
					    INNER JOIN application AS a USING (customer_id)
					  WHERE c.company_id = {$this->company_id}
						AND (a.application_id = {$this->data['application_id']}
						  OR (a.dob = '{$this->data['dob']}'
						    AND a.email = '{$this->data['email_primary']}'))
					  LIMIT 1";

			$result = $this->mysql->Query($query, $this->database);

			if($result->Row_Count() == 0)
			{
				// Nothing was found using any of the queries. Insert as a new customer - GForge #8603 [DW]
				// create base username (flastname_)
				$username = strtoupper($this->data['name_first']{0} . $this->data['name_last'] . '_');
				$username = preg_replace('/[^a-zA-Z0-9\-_]+/', '', $username);
		
				//Underscore is a wildcard character, so we should escape it in the query.
				$query_username = str_replace('_', '\_', $username);
		
				$query = "
					SELECT LPAD(SUBSTRING_INDEX(login, '_', -1), 10, ' ') AS num
					FROM customer
					WHERE login LIKE '{$query_username}%'
					ORDER BY num DESC
					LIMIT 1";
		
				$result = $this->mysql->Query($query, $this->database);
		
				if($result->Row_Count() == 0)
				{
					$num = 0;
				}
				else
				{
					$row = $result->Fetch_Array_Row();
					$num = intval($row['num']);
				}
		
				// add the count at the end
				$username .= ++$num;
		
				// create a random password
				$clear_pass = 'cash' . substr(microtime(), - 3);
				$password = OLPSecurity::Encrypt_Password($clear_pass);
		
				// prepare array to insert record
				$field_array['date_created']	=  'NOW()';
				$field_array['company_id']		=  $this->Company_ID($this->data['property_short']);
				$field_array['login']			=  $username;
				$field_array['password']		=  $password;
				$field_array['ssn']				= $this->data['social_security_number'];
		
				$login_id = $this->Insert_Record('customer', $field_array);
				
				if(!empty($_SESSION))
				{
					$_SESSION['data']['cust_username'] = $this->data['username'] = $username;
					$_SESSION['data']['cust_password'] = $this->data['password'] = $clear_pass;
				}
		
				//Put it into session in case we need to look at it in the soap data tool
				//file_put_contents("/tmp/jason1",$username . " " . $clear_pass);

				//Return new login_id as insert_id - GForge #8063 [DW]
				return array('insert_id' => $login_id);
			}
		}
		
		// Something was found using one of the queries. Insert as an existing customer - GForge #8603 [DW]
		$row = $result->Fetch_Array_Row();
		$login_id = $row['customer_id'];

		$password = OLPSecurity::Decrypt_Password(trim($row['password']));
		$username = $row['login'];

		if(!empty($_SESSION))
		{
			$_SESSION['data']['cust_username'] = $this->data['username'] = $username;
			$_SESSION['data']['cust_password'] = $this->data['password'] = $password;
		}

		//Return existing login_id as insert_id - GForge #8063 [DW]
		return array('insert_id' => $login_id);
	}

	/**
	*
	* @desc updates the ecash (ldb) application table with changed data from cust service
	*
	**/
	public function Update_Application($fields, $application_id)
	{
		$query = "
			UPDATE application
			SET modifying_agent_id =
				(SELECT agent_id
				FROM agent
				WHERE login = 'olp' AND active_status = 'active'),";

		foreach($fields as $column => $value)
		{
			$fields[$column] = $this->Escape_String($value);
			$set_cols .= (($set_cols) ? ',' : '') . $column . '=' . $this->Escape_String($value);
		}

		$query .= $set_cols;
		$query .= " WHERE application_id = $application_id";
		$this->mysql->Query($query);
	}


	/**
	* @return string : insert id
	* @desc run table insert for passed in table and data array
	*
	**/
	public function Insert_Record($table_name, $fields, $agent_id = FALSE, $insert_id_column = NULL)
	{
		// create a generic function for performing inserts to the various tables
		if (empty($insert_id_column))
		{
			$insert_id_column = $table_name.'_id';
		}
		// used for substitutions function
		$this->substitution_data = $fields;

		$substitutions = array(
			//'company_id'		=> "(SELECT company_id FROM company WHERE name_short='%value%')",
			'bureau_id'			=> "(SELECT bureau_id FROM bureau WHERE name_short='%value%')",
			'flag_type_id'		=> "(SELECT flag_type_id FROM flag_type WHERE name_short='%value%')",
			'document_list_id'	=> "(SELECT document_list_id FROM document_list WHERE name='%value%' AND company_id = %%%company_id%%% AND system_id = %%%system_id%%%)",
			'agent_id'			=> "(SELECT agent_id from agent where login='%value%' and active_status='active')",
			'modifying_agent_id'=> "(SELECT agent_id from agent where login='%value%' and active_status='active')",
			'sent_package'		=> "COMPRESS(\"%value%\")",
			'received_package'	=> "COMPRESS(\"%value%\")",
			'application_contact_category_id' => "IFNULL((SELECT application_contact_category_id FROM application_contact_category WHERE company_id = %%%company_id%%% AND category = '%value%'), 0)"
		);

		//Do search if status id is not an id
		if(isset($fields['application_status_id']) && !is_numeric($fields['application_status_id']))
		{
			$substitutions['application_status_id'] = "(SELECT application_status_id FROM application_status_flat WHERE level0 = '%value%'
														AND level1 = 'prospect' AND level2 = '*root')";
		}

		/*
			If agent is true, we're actually passing the agent_id (int) to use and don't need to
			substitute the value.
		*/
		if(!empty($agent_id))
		{
			unset($substitutions['agent_id']);
		}

		// removed from above
		//'loan_type_id'			=> "(SELECT loan_type_id FROM loan_type WHERE company_id = %%%company_id%%% and name_short = '%value%')",

		$query = "insert into {$table_name}\n SET \n ";

		//do substitutions first b/c of neccessary order
		foreach($substitutions as $column => $value)
		{
			if(!empty($this->substitution_data[$column]))
			{
				$this->substitution_data[$column] = $this->Substitute($substitutions[$column], $this->substitution_data[$column]);
			}
		}

		//Lame hack to remove system_id from document table query
		if($table_name == 'document')
		{
			unset($this->substitution_data['system_id']);
		}
		// Lame hack in the spirit of the one above to drop the company_id from application_contact query
		elseif($table_name == 'application_contact')
		{
			unset($this->substitution_data['company_id']);
		}

		foreach($this->substitution_data as $column => $value)
		{
			//only escape columns that we don't substitute
			if(empty($substitutions[$column]))
			{
				$this->substitution_data[$column] = $this->Escape_String($value);
				$set_cols .= ($set_cols)
								? ", $column =" . $this->Escape_String($value)
								: "$column =" . $this->Escape_String($value);
			}
			else
			{
				$set_cols .= ($set_cols)
								? ", $column = $value"
								: "$column = $value";
			}
		}
		$query .= $set_cols;
		$this->mysql->Query($query);
		
		return $this->mysql->Insert_Id();
		
	}

	public function Escape_String($value)
	{
		$return = $value;
		//$value = strtolower($value);
		// escape if not an in or not a timestamp function
		if(!(is_int($value) || strtolower($value) == 'now()' || $value == 'NULL'))
		{
			$escaped = "'" . $this->mysql->Escape_String($value) . "'";
			$return = $escaped;
		}

		return $return;
	}

	public function Substitute($substitution_string, $value)
	{
		//don't do the substitution if the value is already an ID
		if(is_numeric($value))
		{
			return $value;
		}
   		$string = str_replace("%value%", $value, $substitution_string);
		$data = (object)$this->substitution_data;
		return preg_replace ("/%%%(.*?)%%%/e", "\$data->\\1", $string);
	}


	/**
	* @return string: insert id
	* @desc insert legal_id
	*
	**/
	protected function Insert_Card()
	{
		//  Exception Insert if application is filled out by CSR
		if(!empty($this->data['card']))
		{
			$data = array();
			$data['date_created']	= 'NOW()';
			$data['card_number']	= $this->data['card']['card_number'];
			$data['card_ref_id']	= $this->data['card']['card_ref_id'];
			$data['card_bin']		= $this->data['card']['card_bin'];
			$data['card_stock']		= $this->data['card']['card_stock'];
			$data['card_account_number'] = $this->data['card']['card_account_number'];
			$data['company_id']		= $this->data['property_short'];
			$data['customer_id']	= $this->insert_ids['customer'];

			return array('table' => 'card', 'data' => $data);
		}
	}

	public function Company_ID($property_short)
	{
		$as_array = is_array($property_short);

		$id = array();

		if (!$as_array || count($property_short))
		{

			$query = "
				SELECT
					name_short,
					company_id
				FROM
					company
				WHERE
					name_short ".($as_array ? "IN ('".implode("', '", $property_short)."')" : "='{$property_short}'")."
			";
			$result = $this->mysql->Query($query);

			while ($rec = $result->Fetch_Array_Row())
			{
				$id[strtoupper($rec['name_short'])] = $rec['company_id'];
			}

		}

		// return it like we got it
		if (!$as_array)
		{
			$id = $id[strtoupper($property_short)];
		}

		return $id;
	}

	protected function Site_ID($license_key)
	{

		$query = "SELECT site_id FROM site
			WHERE license_key = '". mysql_escape_string($license_key) ."'";
		$result = $this->mysql->Get_Column($query);

		return($result[0]);
	}


	public function Insert_Comment($comment, $agent_id = NULL)
	{
		$field_array['date_modified']	= 'NOW()';
		$field_array['date_created']	= 'NOW()';
		$field_array['company_id']		= $this->Company_ID($comment['property_short']);
		$field_array['application_id']	= $comment['application_id'];
		$field_array['source']			= (isset($comment['source'])) ? $comment['source'] : 'system';
		$field_array['type']			= (isset($comment['type'])) ? $comment['type'] : 'standard';
		$field_array['visibility']		= (isset($comment['visibility'])) ? $comment['visibility'] : 'public';
		$field_array['agent_id']		= (!is_null($agent_id)) ? $agent_id : 'olp';
		$field_array['comment']			= $comment['comment'];

		$this->Insert_Record('comment', $field_array, !is_null($agent_id));
	}

	/**
	 * Update Status History Times
	 */
	public function Insert_Statuses()
	{
		if(!is_array($this->data['status_times'])) return false;

		$application_id = (int)$this->data['application_id'];

		foreach($this->data['status_times'] as $status)
		{
			$this->Update_Application_Status($status['name'], $application_id, $status['loan']);

			$new_time = mysql_escape_string($status['date']);
			$status_name = strtolower($status['name']);

			//Get the status id
			switch ($status_name)
			{
				case 'verification':
				case 'underwriting':
				case 'fraud':
				case 'high_risk':
					if($this->Is_Preact()) $status_name = 'preact';
					$path = "/applicant/$status_name/queued>";
					break;
				case 'denied':
					$path = "/applicant/$status_name>";
					break;
				case 'addl':
					$path = "/applicant/verification/$status>";
					break;
				case 'pending':
				case 'agree':
				case 'confirmed':
				case 'soft_fax':
					if($this->Is_Preact()) $status_name = 'preact_' . $status_name;
					$path = "/prospect/$status_name>";
					break;
				case 'confirm_declined':
				case 'disagree':
				case 'declined':
				default:
					$path = "/prospect/$status_name>";
					break;
			}

			$s = $this->Status_Glob($path);
			if(is_array($s))
			{
				$status_id = reset($s);
				$query = "UPDATE status_history
					  SET date_created='{$new_time}'
					  WHERE application_id={$application_id}
						AND application_status_id={$status_id}";

		    	$result = $this->mysql->Query($query);
			}
		}
	}

	/**
	 * Insert React Affiliation
	 *
	 * Inserts in to the react affiliation table
	 * @param int React App ID
	 * @param string Property Short
	 * @return void
	 */
	public function Insert_React_Affiliation($app_id, $react_app_id, $prop_short = NULL, $agent_id = NULL)
	{
		//Prop Short
		if(!isset($prop_short)) $prop_short = $this->data['property_short'];

		//Agent ID
		//React App ID and App ID are backwards in LDB - DOH!
		if(!isset($agent_id))
		{
			$aff = array('company_id'		=> $this->Company_ID($prop_short),
						 'agent_id'			=> 'olp',
						 'application_id'	=> $react_app_id,
						 'react_application_id' => $app_id,
						 'date_modified'	=> 'NOW()',
						 'date_created'		=> 'NOW()'
			);

			$this->Insert_Record('react_affiliation', $aff);
		}
		else
		{
			$aff = array('company_id'		=> $this->Company_ID($prop_short),
						 'agent_id'			=> $agent_id,
						 'application_id'	=> $react_app_id,
						 'react_application_id' => $app_id,
						 'date_modified'	=> 'NOW()',
						 'date_created'		=> 'NOW()'
			);

			$this->Insert_Record('react_affiliation', $aff, $agent_id);
		}
	}

	/**
	 * Is Preact
	 *
	 * Checks whether the current app is a preact
	 * @return boolean True if preact
	 */
	public function Is_Preact()
	{
		return (isset($this->data['preact'])
				|| isset($_SESSION['is_preact'])
				|| (isset($_SESSION['cs']['olp_process'])
					&& $_SESSION['cs']['olp_process'] == 'ecashapp_preact'));
	}

	/**
	 * This may seem like a bit of over-kill, but I had it pre-existing, so
	 * I reused it here. This works like the PHP 'glob' function, but returns
	 * status IDs as an array (path => ID). In the "path", a leading '/' anchors
	 * the status at the '*root' node. A trailing '/' or '/*' finds all children
	 * for that branch. Paths ending in '>' are forced to be leaf nodes. Multiple
	 * paths can be chained together using colons.
	 *
	 * Examples:
	 *
	 * 	/applicant/denied == *root=>applicant=>denied
	 * 	/customer/collections/ == all children of *root=>customer=>collections
	 * 	/customer/collections/> == all children that are leaf nodes
	 *	/prospect> == nothing, prospect is a branch, not a leaf
	 * 	/prospect/pending:/prospect/agree == *root=>prospect=>pending, *root=>prospect=>agree
	 *
	 * @param string $path
	 * @return array
	 */
	public function Status_Glob($path)
	{

		// our tree depth
		$tree_depth = 6;

		$paths = array_map('trim', split(':', $path));
		$query = array();

		foreach ($paths as $path)
		{

			// if we're looking for the root
			// node, translate that now
			if ($path === '/') $path = '*root';

			// if we're anchoring on the root of
			// the tree, then add that to our path
			if ($path{0} === '/') $path = '*root'.$path;

			// if we're matching a branch, then
			// add the special wildcard operator
			if (substr($path, -1) === '/') $path .= '*';

			// split our search string and reverse it,
			// since we're going to work backwards
			$path = array_reverse(explode('/', $path));

			if (count($path) <= $tree_depth)
			{

				$temp = '';
				$no_children = FALSE;

				if (substr(reset($path), -1) === '>')
				{

					// we don't want a branch with children
					$no_children = TRUE;

					// remove the > from the path
					$leaf = array_shift($path);

					if ($leaf !== '>')
					{
						array_unshift($path, substr($leaf, 0, -1));
					}
					else
					{
						array_unshift($path, '*');
					}

				}

				// if we only have one entry in our path,
				// we don't need to build a fancy query
				if (count($path) == 1)
				{
					// find all statuses with this name
					$temp = "level0='".reset($path)."'";
				}
				elseif (reset($path) === '*')
				{

					// take off our wildcard
					array_shift($path);
					$level = ($tree_depth - count($path));

					do
					{

						$parents = array();

						foreach ($path as $key=>$status)
						{
							$parents[] = 'level'.($level + $key)." = '".$status."'";
						}

						$temp .= '('.implode(' AND ', $parents).')';

					}
					while ((--$level > 0) && ($temp .= ' OR '));

				}
				else
				{

					$temp = array();

					foreach ($path as $key=>$status)
					{
						$temp[] = 'level'.$key." = '".$status."'";
					}

					$temp = implode(' AND ', $temp);

				}

				/*if ($temp && $no_children)
				{
					$temp .= ' AND NOT EXISTS (SELECT application_status.application_status_id FROM application_status
						WHERE	application_status_parent_id = application_status_flat.application_status_id)';
				}*/

				// add to our list of queries
				$query[] = $temp;

			}

		}

		try
		{

			// run the query
			$query = "SELECT application_status_id, level5, level4, level3, level2, level1, level0
				FROM application_status_flat WHERE (".implode(') OR (', $query).')';

			$result = $this->mysql->Query($query);

			$status = array();

			while ($rec = $result->Fetch_Row())
			{
				$path = '/'.implode('/', array_slice($rec, array_search('*root', $rec) + 1));
				$status[$path] = (int)reset($rec);
			}

		}
		catch (Exception $e)
		{
			$status = FALSE;
		}

		return($status);

	}

	/**
	 * Mail Process Done
	 *
	 * Sends out the email at the end of the process
	 */
	public function Mail_Confirmation()
	{
		//Do not email if an OC react
		if(isset($_SESSION['calculated_react']) && !$this->isEcashAppReact()) return false;

		if(empty($this->property_short))
		{
			$this->property_short = strtoupper($this->data['property_short']);
		}

		$data = $this->Get_Mail_Data();
		$template = $this->Get_Mail_Template();
		$this->Send_Mail($template, $data);
	}

	protected function Get_Mail_Template()
	{
		return 'OLP_PAPERLESS_FUNDER_REVIEW';
	}

	protected function Get_Mail_Data()
	{
		$prefix = (BFW_MODE == 'RC') ? 'rc.' : '';
		$site_name = $prefix . $this->ent_prop_list[$this->property_short]['site_name'];
		$confirm_url = $this->Get_Login_Link($site_name);

		$data = array(
			'email_primary'			=> $this->data['email_primary'],
			'email_primary_name'	=> strtoupper( $this->data['name_first'] . ' ' . $this->data['name_last'] ),
			'name'					=> strtoupper( $this->data['name_first'] . ' ' . $this->data['name_last'] ),
			'applicationid'			=> $this->data['application_id'],
			'amount'				=> '$' . number_format($this->data['qualify_info']['fund_amount'], 2),
			'date'					=> date('m/d/Y', strtotime($this->data['qualify_info']['fund_date'])),
			'confirm'				=> $confirm_url,
			'csphone'				=> $this->ent_prop_list[$this->property_short]['phone'],
			'username'				=> $this->data['username'],
			'password'				=> $this->data['password'],
			'site'					=> $site_name,
			'site_name'				=> $site_name,
			'name_view'				=> $this->ent_prop_list[$this->property_short]['legal_entity'],
		);

		return $data;
	}

	protected function Get_Login_Link($site_name = '')
	{
		if(empty($site_name))
		{
			$prefix = (BFW_MODE == 'RC') ? 'rc.' : '';
			$site_name = $prefix . $this->ent_prop_list[$this->property_short]['site_name'];
		}

		$login_hash = md5($this->data['application_id'] . $this->hash_key);
		$encoded_app_id = urlencode(base64_encode($this->data['application_id']));
		
		$link_query = array(
			'application_id' => $encoded_app_id,
			'page' => 'ent_cs_login',
			'login' => $login_hash,
			'ecvt' => 1,
			'force_new_session' => 1
		);
		
		// Ecashapp reacts need to add the ecash_confirm parameter
		if (isset($_SESSION['data']['ecashapp']) && EnterpriseData::isEnterprise($_SESSION['data']['ecashapp']))
		{
			$link_query['ecash_confirm'] = 1;
		}
		
		$link = sprintf("http://%s/?%s", $site_name, http_build_query($link_query));

		return $link;
	}

	protected function Send_Mail($template, $data)
	{
		try
		{
			require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
			$tx = new OlpTxMailClient(false);
			$res = $tx->sendMessage('live', $template, $data['email_primary'], $this->data['track_key'], $data);
		}
    	catch(Exception $e)
		{
			throw new Exception("Trendex mail $template failed. ".$e->getMessage()." (App ID: ". $this->data['application_id'] . ")");
		}
	
		if($res === FALSE)
		{
			throw new Exception("Trendex mail $template failed. (App ID: ". $this->data['application_id'] . ")");
		}
	}

	protected function Insert_Fraud($fraud_rule_id, $application_id)
	{
		$query = "INSERT INTO fraud_application
			(fraud_rule_id, application_id)
			VALUES($fraud_rule_id,$application_id)";
		$this->mysql->Query($query);
	}

	/**
	 * Sets a group of applications to the expired status
	 *
	 * @param array $id
	 * @return void
	 */
	public function Expire_Applications(array $id)
	{
		$status_id = array_pop($this->Status_Glob('/prospect/expired'));

		$query = "
			UPDATE application
			SET
				modifying_agent_id = (
					SELECT agent_id
					FROM agent
					WHERE login = 'olp'
						AND active_status = 'active'
				),
				application_status_id = {$status_id}
			WHERE
				application_id IN (" . implode(', ', $id) . ")
		";
		$this->mysql->Query($query);
	}
}
?>
