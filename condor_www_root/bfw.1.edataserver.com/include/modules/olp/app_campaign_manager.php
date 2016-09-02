<?php
/**
	@version:
			1.0.0 2005-04-16 - Helper class for talking to application and campaign_info tables
	@author:
			Jason Duffy - version 1.0.0
	@Updates:

	@Todo:
*/

class App_Campaign_Manager
{

	private $sql;
	private $database;
	private $applog;
	private $crypt_object;
	const COREG_SUCCESS_TRUE = 'TRUE';
	const COREG_SUCCESS_FALSE = 'FALSE';

	protected static $synch_status = array(
		'PENDING', 
		'AGREED', 
		'DISAGREED',
		'CONFIRMED',
		'CONFIRMED_DISAGREED',
	);

	/**
	* @return App_Campaign_Manager
	* @param &$sql mysql database connection
	* @param $database string
	* @param &$applog applog object for noting exceptional conditions
	* @desc Constructor - just store db info
	*/
	public function __construct(&$sql, $database, &$applog)
	{
		$this->sql = $sql;
		$this->database = $database;
		$this->applog = $applog;
		$this->crypt_object = Crypt_Singleton::Get_Instance();
	}

	/**
	* @return $application_id string or null on error
	* @param $statpro_track_key string
	* @param $config object
	* @param $offers string (YES|NO)
	* @param $license_key string
	* @param $client_ip string
	* @desc Insert into application and campaign_info
	*/
	public function Create_Application($statpro_track_key, $config, $offers, $license_key, $client_ip, $tel_app_proc, $reservation_id, $olp_process_info)
	{
		$application_id = null;
		$session_id = session_id();
		$promo_id = ($config->promo_id) ? $config->promo_id : 10000;
		$promo_sub_code = $config->promo_sub_code;
		$site_name = $config->site_name;
		$tel_app_proc = ( strtoupper($tel_app_proc) == 'TRUE') ? 'TRUE' : 'FALSE';

		$olp_process = self::Find_Olp_Process($promo_id,
			$olp_process_info['is_online_confirmation'],
			$olp_process_info['is_ecashapp'],
			$olp_process_info['has_reckey'],
			$olp_process_info['is_preact']);

		$query = "
				INSERT INTO application
					(session_id,
					 created_date,
					 track_id,
					 olp_process)
				VALUES
					('$session_id',
					 CURRENT_TIMESTAMP(),
					 '$statpro_track_key',
					 '$olp_process')";

		// let exception bubble up if it throws
		if(defined('OLP_DUAL_WRITE') && OLP_DUAL_WRITE === true)
		{
			//If we're dual writing, we have to forcefully set the
			//app_id for parallel incase they end up out of sync
			$result = $this->sql->QueryMainDB( $this->database, $query );
			$application_id = $this->sql->Insert_Id();
			$query = "INSERT INTO application
				(application_id,
				session_id,
				created_date,
				track_id,
				olp_process)
				VALUES
				($application_id,
				'$sesion_id',
				CURRENT_TIMESTAMP(),
				'$statpro_track_key',
				'$olp_process')";
			$this->sql->QueryParallelDB($this->database, $query);
		}
		else
		{
			$result = $this->sql->Query ( $this->database, $query );
			$application_id = $this->sql->Insert_Id();
		}

		// prepare campaign_info insert
		$campaign_url_root = ($config->site_name) ? ereg_replace('http://', '', $config->site_name) : '';
		$offers = ($offers) == 'TRUE' ? 'TRUE' : 'FALSE';

		if(empty($reservation_id) || !is_numeric($reservation_id))
		{
			$reservation_id = 'NULL';
		}

		$query = "
			INSERT INTO campaign_info
				(application_id,
				 promo_id,
				 promo_sub_code,
				 license_key,
				 created_date,
				 url,
				 ip_address,
				 offers,
				 active,
				 tel_app_proc,
				 reservation_id)
			VALUES
				($application_id,
				 $promo_id,
				 '$promo_sub_code',
				 '$license_key',
				 CURRENT_TIMESTAMP(),
				 '$campaign_url_root',
				 '$client_ip',
				 '$offers',
				 'TRUE',
				 '$tel_app_proc',
				 $reservation_id)";

		try
		{
			$result = $this->sql->Query ( $this->database, $query );
		} catch (MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "working with the application or campaign_info tables in App_Campaign_Manager->Create_Application.");
		}

		return $application_id;
	}


	public static function Find_Olp_Process($promo_id = 10000, $is_online_confirmation = TRUE, $is_ecashapp = FALSE, $has_reckey = FALSE, $is_preact = FALSE)
	{
		$olp_process = NULL;

		//Taken from olp.mysql [CB]
		if($is_preact)
		{
			$olp_process = 'ecashapp_preact';
		}
		elseif($is_ecashapp)
		{
			// This is an ecash react app [RL]
			$olp_process = 'ecashapp_react';
		}
		elseif(isset($_SESSION['data']['ecashnewapp']))
		{
			$olp_process = 'ecashapp_new';
		}
		elseif($has_reckey)
		{
			// Based on the Email React Promo ID do we set if this is a email react or a cs_react [RL]
			$olp_process = (in_array(intval($promo_id), array(26181,26182,26183,26184,26185,28155))) ? 'email_react' : 'cs_react';
		}
		elseif(isset($_SESSION['is_yellowpage']) && $_SESSION['is_yellowpage'] == true)
		{
			$olp_process = 'ecash_yellowpage';
		}
		else
		{
			// All other processes are brand new loan confirmations and should be marked as such [RL]
			$olp_process = ($is_online_confirmation) ? 'online_confirmation' : 'email_confirmation';
		}

		return $olp_process;
	}


	/**
	* @return null
	* @param $application_id string
	* @param $new_promo_id string
	* @param $new_promo_sub_code string
	* @desc Set new promotion codes for the given application_id
	*/
	public function Update_Campaign($application_id, $config, $reservation_id = NULL)
	{

		if($application_id == null)
		{
            if(isset($_SESSION["cs"]["application_id"]))
			{
                $application_id = $_SESSION["cs"]["application_id"];
			}
            elseif(isset($_SESSION["application_id"]))
			{
                $application_id = $_SESSION["application_id"];
			}
            else
			{
            	throw new Exception("Cannot update campaign - no app id");
			}
		}

		if(empty($reservation_id) || !is_numeric($reservation_id))
		{
			$reservation_id = 'NULL';
		}

		try
		{
			$query = "
				UPDATE campaign_info
				SET
					active='FALSE'
				WHERE
					application_id=$application_id";

			$result = $this->sql->Query ( $this->database, $query );

			$query = "
				SELECT
					license_key,
					created_date,
					url,
					ip_address,
					offers,
					active,
					tel_app_proc
				FROM
					campaign_info
				WHERE
					application_id=$application_id";
			$rid = $this->sql->Query ($this->database, $query);

			$row = $this->sql->Fetch_Array_Row($rid);

			$license_key = $row['license_key'];
			$created_date = $row['created_date'];
			$url = $row['url'];
			$ip_address = $row['ip_address'];
			$offers = $row['offers'];
			$tel_app_proc = $row['tel_app_proc'];

			$query = "
				INSERT INTO campaign_info
					(application_id,
					 promo_id,
					 promo_sub_code,
					 license_key,
					 created_date,
					 url,
					 ip_address,
					 offers,
					 active,
					 tel_app_proc,
					 reservation_id)
				VALUES
					($application_id,
					 $config->promo_id,
					 '$config->promo_sub_code',
					 '$license_key',
					 CURRENT_TIMESTAMP(),
					 '$url',
					 '$ip_address',
					 '$offers',
					 'TRUE',
					 '$tel_app_proc',
					 $reservation_id)";

			$result = $this->sql->Query ($this->database, $query);
		} catch (MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "working with the campaign_info tables in App_Campaign_Manager->Update_Campaign.");
		}

	}

	/**
	* @return null
	* @param $application_id string
	* @param $transaction_id string
	* @desc Set new transaction code for the given application_id
	*/
	public function Update_Application($application_id, $transaction_id, $winner)
	{
		try
		{
			$query = "
				SELECT
					target_id
				FROM
					target
				WHERE
					property_short='$winner'
					AND status='ACTIVE'
					AND deleted='FALSE'";

			$rid = $this->sql->Query ($this->database, $query);
			$row = $this->sql->Fetch_Array_Row($rid);

			$target_id = $row['target_id'];

			$transaction_id = ($transaction_id) ? $transaction_id : 0;
			$is_react = ($transaction_id) ? 1 : 0;
			$query = "
				UPDATE application
				SET
					transaction_id = $transaction_id,
					target_id = $target_id,
					is_react = $is_react
				WHERE
					application_id = $application_id";
			$this->sql->Query($this->database, $query);
		}
		catch (MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "updating the application table in App_Campaign_Manager->Update_Application.");
		}
	}


	public function Update_Olp_Process($application_id, $process)
	{
		try
		{
			$query = "UPDATE application SET olp_process='{$process}' WHERE application_id = '{$application_id}' LIMIT 1";

			$this->sql->Query($this->database, $query);
		}
		catch(MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "updating the application table in App_Campaign_Manager->Update_Olp_Process.");
		}
	}


	/**
	* @return null
	* @param $application_id string
	* @desc Set new application status code for the given application_id
	*/
	public function Update_Application_Status($application_id, $status)
	{

		if($application_id == null)
        {
            if(isset($_SESSION["cs"]["application_id"]))
            {
                $application_id = $_SESSION["cs"]["application_id"];
            }
            elseif(isset($_SESSION["application_id"]))
            {
                $application_id = $_SESSION["application_id"];
            }
            else
            {
            	throw new Exception("Cannot update application status - no app id");
            }
        }

        try
		{
			$query = "
				UPDATE application
				SET
					application_type = '" . $status . "'
				WHERE
					application_id = $application_id";
			$this->sql->Query($this->database, $query);
		}
		catch (MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "updating the app status in application table in App_Campaign_Manager->Update_Application_Status.");
		}


		//See if we should update status history
		$status_map = array(
			'PENDING'		=> 'pending',
			'AGREED'		=> 'agree',
			'DISAGREED'		=> 'disagree',
			'CONFIRMED'		=> 'confirmed',
			'CONFIRMED_DISAGREED' => 'confirm_declined',
			'PREACT_PENDING' => 'preact_pending',
			'PREACT_AGREED' => 'preact_agree',
			'PREACT_CONFIRMED' => 'preact_confirmed',
			'EXPIRED' => 'expired'
		);

		//Only do failed for ecashapp
		if(isset($_SESSION['data']['ecashapp']))
		{
			$status_map['FAILED'] = 'denied';
		}

		//Add stuff for preacts
		if($this->Is_Preact() && in_array($status,array('PENDING','AGREED','DISAGREED'))) $status = "PREACT_" . $status;

		if(isset($status_map[$status]))
		{
			$this->Update_Status_History($application_id, $status_map[$status]);
		}
		
		// If we're updating to a status we need to synch on
		// AND we're either CFE or WE're in the AGREED status and NOT
		// an ecash app react, add the unsynched status.
		if ((in_array($status, self::$synch_status) && (
			EnterpriseData::isCFE($_SESSION['config']->property_short)) || 
			($status == 'AGREED' && strcasecmp($this->Get_Olp_Process($application_id), 'ecashapp_react')))) 
		{
			$this->Update_Status_History($application_id, 'ldb_unsynched');
		}
	}

	public function Update_Status_History($application_id, $status)
	{
		try
		{
			//We're going to prevent ldb_unsynched from being entered if an app is in synched status
			if (!EnterpriseData::isCFE($_SESSION['config']->property_short))
			{
				$statuses = ($status == 'ldb_unsynched') ? array('ldb_synched', 'ldb_unsynched') : array($status);	
			}
			else
			{
				$statuses =  array($status);
			}
			$app_status_id = NULL;

			foreach($statuses as $status)
			{

				$query = "SELECT application_status_id FROM application_status WHERE name = '{$status}'";
				$result = $this->sql->Query($this->database, $query);

				$app_status_id = $this->sql->Fetch_Column($result, 0);

				if(is_numeric($app_status_id))
				{
					//Make sure we have a unique status.
					$query = "SELECT COUNT(*) AS count
						FROM
							status_history
						WHERE
							application_id = {$application_id}
							AND application_status_id = {$app_status_id}
						";

					$result = $this->sql->Query($this->database, $query);

					if($this->sql->Fetch_Column($result, 0) > 0)
					{
						$app_status_id = NULL;
						break;
					}
				}
			}


			if(is_numeric($app_status_id))
			{
				$query = "INSERT INTO status_history (application_id, application_status_id, date_created)
					VALUES ({$application_id}, {$app_status_id}, NOW())";

				$this->sql->Query($this->database, $query);
			}

		}
		catch(MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, 'updating the app status history in application table in App_Campaign_Manager->Update_Application_Status_History.');
		}
	}



	public function Get_Statuses($application_id, $date_format = '')
	{
		$statuses = array();

		if(is_numeric($application_id))
		{
			$date = (!empty($date_format)) ? "DATE_FORMAT(h.date_created, '{$date_format}') AS date_created" : 'h.date_created';

			$query = "SELECT {$date}, name
				FROM status_history h
				INNER JOIN application_status USING (application_status_id)
				WHERE application_id = {$application_id}";

			$result = $this->sql->Query($this->database, $query);

			while($row = $this->sql->Fetch_Array_Row($result))
			{
				$statuses[$row['name']] = $row['date_created'];
			}
		}

		return $statuses;
	}


	/**
	* @return null
	* @param $application_id string
	* @param $data array from session[data]
	* @desc Insert all of the application data in appropriate tables
	*		Code is grandfathered and hacked from olp5 excuse the mess
	*/
	public function Insert_Application($application_id, $data, $title_loan = false)
	{
		$data = $this->Escape_Data($data);

		//Gforge 3076 - API for HnP [VT]
		if($this->bad_aba($data['bank_aba'])) $this->recordBadEmailABA($data['email_primary']);

		// Added in a check for the is_react flag and updating app appropriately	[RV]
		if($_SESSION['is_react']) $this->Update_Is_React($application_id);

		try
		{
			$table_queries = array();
			$table_queries['bank_info_encrypted'] = $this->Application_Set_Bank_Info_Encrypted($application_id,$data);
			$table_queries['employment'] = $this->Application_Set_Employment($application_id, $data);
			$table_queries['loan_note'] = $this->Application_Set_Loan_Note($application_id, $data);
			$table_queries['personal_encrypted'] = $this->Application_Set_Personal_Encrypted($application_id, $data);
			$table_queries['residence'] = $this->Application_Set_Residence($application_id, $data);

			if($title_loan)
			{
				$table_queries['vehicle'] = $this->Application_Set_Vehicle($application_id, $data);
			}

			foreach($table_queries as $table => $query)
			{
				$query_start = "REPLACE INTO {$table} ";
				$completed_query = $query_start . $query;
				$result = $this->sql->Query($this->database, $completed_query);
			}

			$this->Insert_Paydate_Model($application_id, $data);
			$this->Insert_Personal_Contacts($application_id, $data);

		}
		catch(MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id record insert process failed.");
			return FALSE;
		}

		return $application_id;
	}


	public function Insert_Personal_Contacts($application_id, $data)
	{
		$contact_ids = array();

		try
		{
			// Loop thru personal contacts until theres no left to be found and add them to the database.
			for($i = 1; $i < 5; $i++)
			{
				if(!empty($data["ref_0{$i}_name_full"]))
				{
					$query = "INSERT INTO personal_contact SET
									application_id = $application_id,
									full_name = '" . mysql_escape_string( $data["ref_0{$i}_name_full"] ). "',
									phone = '{$data["ref_0{$i}_phone_home"]}',
									relationship = '" . mysql_escape_string( stripslashes($data["ref_0{$i}_relationship"] )) . "'";

					$result_id = $this->sql->Query($this->database, $query);
					$contact_ids[] = $this->sql->Insert_Id($result_id);
				}
				else
				{
					break;
				}
			}

			if(!empty($contact_ids))
			{
				$query = "UPDATE personal_encrypted
					SET contact_id_1 = '{$contact_ids[0]}',
						contact_id_2 = '{$contact_ids[1]}'
					WHERE application_id = {$application_id}
					LIMIT 1";

				$this->sql->Query($this->database, $query);
			}
		}
		catch(MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id insert personal contacts failed.");
			return FALSE;
		}
	}

	/**
	* @return null
	* @param $application_id string
	* @param $data array from session[data]
	* @desc Insert all of the application data in appropriate tables
	*		that have changed after the confirmation page
	*		Code is grandfathered and hacked from olp5 excuse the mess
	*
	*/
	public function Insert_Application_Confirmation($application_id, $data)
	{

		$data = $this->Escape_Data($data);

		try
		{

			$query = $this->Application_Set_Bank_Info_Encrypted($application_id, $data);
			$query = 'REPLACE INTO bank_info_encrypted ' . $query;
			$result = $this->sql->Query($this->database, $query);

			// insert paydate model
			$this->Insert_Paydate_Model($application_id, $data);

		}
		catch(MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id record insert process failed.");
			return FALSE;
		}

		return $application_id;
	}

	/**
	 * Returns the set query for inserting/updating the vehicle table
	 *
	 * @param int $application_id
	 * @param array $data
	 * @return string
	 */
	function Application_Set_Vehicle($application_id, &$data)
	{
    	return "SET
        	application_id={$application_id},
        	year='{$data['vehicle_year']}',
        	make='{$data['vehicle_make']}',
        	model='{$data['vehicle_model']}',
        	series='{$data['vehicle_series']}',
        	style='{$data['vehicle_style']}',
        	mileage='{$data['vehicle_mileage']}',
        	vin='{$data['vehicle_vin']}',
			value='{$data['vehicle_value']}',
        	color='{$data['vehicle_color']}',
        	license_plate='{$data['vehicle_license_plate']}',
        	title_state='{$data['vehicle_title_state']}'
    	";
	}


	private function recordBadEmailABA($email)
	{
		$email_escaped = strtoupper(mysql_escape_string($email));

		$time = date('YmdHis',time());

		$query = "
				SELECT
					id
				FROM
					bad_email_aba
				WHERE
					email_primary = '$email_escaped'
			";

		$result = $this->sql->Query($this->database,$query);

		if($row = $this->sql->Fetch_Array_Row($result))
		{
			$id = $row['id'];

			$query = "
				UPDATE
					bad_email_aba
				SET
					date_modified = $time
				WHERE
					id = $id
			";
		}
		else
		{

			$query = "
				INSERT INTO
					bad_email_aba
					(date_created,id,email_primary,date_modified)
				VALUES
					(null,null,'$email_escaped',$time)
			";
		}

		$result = $this->sql->Query($this->database,$query);
	}

	public function Application_Set_Bank_Info_Encrypted($application_id, $data)
	{
		$start_date = '';
		// gForge #5209 - double checking date format and formatting it correctly for mysql.	[RV]
		if (!empty($data['banking_start_date']) && strtotime($data['banking_start_date']))
		{
			$start_date = sprintf("banking_start_date = '%s',", date('Y-m-d', strtotime($data['banking_start_date'])));
		}

		// just for normalization per Chris [AuMa] - GFORGE 4149
		if (empty($data['debit_card']))
		{
			$data['debit_card'] = 0; // setting it to "0"
		}

		$insert_direct_deposit = ($data['income_direct_deposit'] == 'TRUE') ? 'TRUE' : 'FALSE';
		$table_query  = "SET
			application_id = $application_id,
			bank_name = '" . mysql_escape_string( stripslashes($data['bank_name']) ) . "',
			routing_number = '{$data['bank_aba_encrypted']}',
			account_number = '{$data['bank_account_encrypted']}',
			bank_account_type = '{$data['bank_account_type']}',
			debit_card_id = '{$data['debit_card']}',
			check_number = '{$data['bank_check']}',
			{$start_date}
			direct_deposit = '{$data['income_direct_deposit']}'";
		return $table_query;
	}

	public function Application_Set_Employment($application_id, $data)
	{
		if (!isset($data['date_of_hire']) && $data['employer_length'] != 9)
		{
			$data['date_of_hire'] = date('Y-m-d', strtotime('-3 months'));
		}

		$start_date = '';
		if (!empty($data['date_of_hire']) )
		{
			$start_date = sprintf("date_of_hire = '%s',", date('Y-m-d', strtotime($data['date_of_hire'])));
		}

		//********************************************* 
		// Modified the query GF#6031 [AuMa]
		// Adding the supervisor information because
		// cg_uk requires a seperate phone number
		// for the supervisor of the user
		//*********************************************
		
		// We don't want the income type to be blank if it doesn't exist when we insert the application
		$income_type = isset($data['income_type'])
			? $data['income_type']
			: 'EMPLOYMENT';
		
		$table_query = "SET
								application_id = $application_id,
								employer = '" . mysql_escape_string( stripslashes($data['employer_name'] )) . "',
								work_phone = '{$data['phone_work']}',
								work_ext = '{$data['ext_work']}',
								{$start_date}
								income_type = '{$income_type}',
								title = '{$data['work_title']}',
								employer_phone = '{$data['supervisor_phone']}',
								employer_phone_ext = '{$data['supervisor_phone_ext']}'
		";
		return $table_query;
	}

	public function Application_Set_Loan_Note($application_id, $data)
	{
		//********************************************* 
		// GForge 6672 
		// this will allow us to store the loan
		// amount requested amount into the database
		// without interfering with the data if we
		// already have it
		// [AuMa]
		//********************************************* 
		if (isset($data['qualify_info']['fund_amount']) &&
			$data['qualify_info']['fund_amount'] !== 0)
		{
			$fund_amount = $data['qualify_info']['fund_amount'];
		} 
		elseif ($data['loan_amount_desired'] !== 0) 
		{
			$fund_amount = $data['loan_amount_desired'];
		}
		$table_query = "SET
								application_id = $application_id,
								estimated_fund_date = '{$data['qualify_info']['fund_date']}',
								actual_fund_date = '{$data['qualify_info']['fund_date']}',
								fund_amount = '{$fund_amount}',
								apr = '{$data['qualify_info']['apr']}',
								finance_charge = '{$data['qualify_info']['finance_charge']}',
								total_payments = '{$data['qualify_info']['total_payments']}',
								estimated_payoff_date = '{$data['qualify_info']['payoff_date']}'";
		return $table_query;
	}

	public function Application_Set_Personal_Encrypted($application_id, $data)
	{

			if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $data['dob'], $m))
			{
				$dob = $m[3].'-'.$m[1].'-'.$m[2];
			}
			$dob_encrypted = $this->crypt_object->encrypt($dob);

			$issued_state = ($data['state_issued_id']) ? $data['state_issued_id'] : $data['home_state'];
			$email_agent_created = (strtoupper($data['email_agent_created']) == "TRUE") ? 'TRUE' : 'FALSE';

			$phone_fax = (isset($data['phone_fax'])) ? "fax_phone = '{$data['phone_fax']}'," : '';

			if(strcasecmp($data['military'], "TRUE") == 0)
			{
				$military = "yes";
			}
			elseif(strcasecmp($data['military'], "FALSE") == 0)
			{
				$military = "no";
			}
			else
			{
				$military = "n/a";
			}

			$table_query = "SET
									application_id = $application_id,
									first_name = '" . mysql_escape_string( $data['name_first'] ) . "',
									last_name = '" . mysql_escape_string( $data['name_last'] ) . "',
									home_phone = '{$data['phone_home']}',
									cell_phone = '{$data['phone_cell']}',
									{$phone_fax}
									email = '{$data['email_primary']}',
									date_of_birth = '{$dob_encrypted}',
									social_security_number = '{$data['social_security_number_encrypted']}',
									best_call_time = '{$data['best_call_time']}',
									drivers_license_number = '{$data['state_id_number']}',
									drivers_license_state = '$issued_state',
					military = '$military',
									email_agent_created = '$email_agent_created'";
			return $table_query;
	}


	public function Application_Set_Residence($application_id, $data)
	{
		switch ( $data['cali_agree'] )
		{
			case 'agree':
				$ca_agree = 1;
				break;
			case 'disagree':
				$ca_agree = 0;
				break;
			default:
				$ca_agree = 'NULL';
		}

		//********************************************* 
		// GForge 6672
		// If Residence Type is not RENT or OWN
		// it will not insert the data, then we have to
		// verify that the data is ok for inserting
		// - if it's not then don't even try
		//********************************************* 
		// Gforge 6672 - Added residence type to the 
		// database query [AuMa]
		//********************************************* 
		switch ($data['residence_type'])
		{
			case 'RENT':
				$residence_type = 'RENT';
			break;
			case 'OWN':
				$residence_type = 'OWN';
			break;
			default:
				$residence_type = '';
		}
		
		$start_date = '';
		// gForge #5209 - double checking date format and formatting it correctly for mysql.	[RV]
		if (!empty($data['residence_start_date']) && strtotime($data['residence_start_date']))
		{
			$start_date = sprintf("residence_start_date = '%s',", date('Y-m-d', strtotime($data['residence_start_date'])));
		}

		$table_query  = "SET
								application_id = $application_id,
								address_1 = '" . mysql_escape_string( stripslashes($data['home_street'] )) . "',
								apartment = '" . mysql_escape_string( stripslashes( $data['home_unit'] )) . "',
								city = '" . mysql_escape_string( stripslashes($data['home_city'] )) . "',
								state = '{$data['home_state']}',
								zip = '{$data['home_zip']}',
								residence_type = '{$residence_type}',
								{$start_date}
									ca_resident_agree = {$ca_agree},
									county = '" . mysql_escape_string( stripslashes($data['county'] )) . "',
									country = '" . mysql_escape_string( stripslashes($data['country'] )) . "'";

			return $table_query;
	}

	public function Insert_Paydate_Model($application_id, $data)
	{
		// did the user generate the model or did the application
		//error_log ("Insert Model: ".$application_id." in ".$this->database." with ".$type."\n",3,"/tmp/olpdb.log");


		if ($data['qualify_info']['net_pay'] == 0)
		{
			$data['qualify_info']['net_pay'] = $data['data']['net_pay'];
		}

		// We don't want to insert a blank string if income_frequency is blank or doesn't exist
		$income_frequency = isset($data['paydate_model']['income_frequency'])
				? $data['paydate_model']['income_frequency']
				: 'WEEKLY';

		if (empty($data['paydates']))
		{
			$query = "
				INSERT INTO income
				(
					application_id,
					net_pay,
					monthly_net,
					pay_frequency
				)
				VALUES
				(
					%1\$u,
					'%2\$s',
					'%3\$s',
					'%4\$s'
				)
				ON DUPLICATE KEY UPDATE
					net_pay = '%2\$s',
					monthly_net = '%3\$s',
					pay_frequency = '%4\$s'";
			
			$query = sprintf(
				$query,
				$application_id,
				$data['qualify_info']['net_pay'],
				$data['income_monthly_net'],
				$income_frequency
			);
		}
		else
		{
			$query = "
				INSERT INTO income
				(
					application_id,
					net_pay,
					monthly_net,
					pay_frequency,
					pay_date_1,
					pay_date_2,
					pay_date_3,
					pay_date_4
				)
				VALUES
				(
					%1\$u,
					'%2\$s',
					'%3\$s',
					'%4\$s',
					'%5\$s',
					'%6\$s',
					'%7\$s',
					'%8\$s'
				)
				ON DUPLICATE KEY UPDATE
					net_pay = '%2\$s',
					monthly_net = '%3\$s',
					pay_frequency = '%4\$s',
					pay_date_1 = '%5\$s',
					pay_date_2 = '%6\$s',
					pay_date_3 = '%7\$s',
					pay_date_4 = '%8\$s'";
			
			$query = sprintf(
				$query,
				$application_id,
				$data['qualify_info']['net_pay'],
				$data['income_monthly_net'],
				$income_frequency,
				$data['paydates'][0],
				$data['paydates'][1],
				$data['paydates'][2],
				$data['paydates'][3]
			);
		}

		$res = $this->sql->Query($this->database,$query);

		if( isset($data["paydate_model"]) )
		{
			$sql_query = "DELETE from paydate where application_id = $application_id";
			$res = $this->sql->Query($this->database,$sql_query);

			// Day of week conversion array for monthly/semi-monthly paydate_model [LR]
			$dow = array('SUN' => 0, 'MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6);


			$query = "INSERT INTO paydate SET
				date_created = NOW(),
				application_id = $application_id,
				paydate_model_id = '{$data["paydate_model"]["model_name"]}'";

			// What fields are we supposed to insert
			if( isset($data["paydate_model"]["day_of_week"]) )
			{
				// This is good for weekly/biweekly, but won't work for some monthly/semi-monthly models
				$query .= ", day_of_week = {$data["paydate_model"]["day_of_week"]}";
			}

			// This is for the monthly/semi-monthly models that use day_string_one instead of day_of_week [LR]
			else if( isset($data["paydate_model"]["day_string_one"]) )
			{
				$query .= ", day_of_week = {$dow[$data["paydate_model"]["day_string_one"]]}";
			}

			if( isset($data["paydate_model"]["next_pay_date"]) )
			{
				$query .= ", next_paydate = '".date("Y-m-d", strtotime($data["paydate_model"]["next_pay_date"]))."'";
			}

			if( isset($data["paydate_model"]["day_int_one"]) )
			{
				$query .= ", day_of_month_1 = {$data["paydate_model"]["day_int_one"]}";
			}

			if( isset($data["paydate_model"]["day_int_two"]) )
			{
				$query .= ", day_of_month_2 = {$data["paydate_model"]["day_int_two"]}";
			}

			if( isset($data["paydate_model"]["week_one"]) )
			{
				$query .= ", week_1 = {$data["paydate_model"]["week_one"]}";
			}

			if( isset($data["paydate_model"]["week_two"]) )
			{
				$query .= ", week_2 = {$data["paydate_model"]["week_two"]}";
			}

			if( isset($data["paydate_model"]["accuracy_warning"]) )
			{
				$query .= ", accuracy_warning = 1 ";
			}


			$res = $this->sql->Query($this->database, $query);
		}

		return TRUE;
	}

	/**
	 * Updates the pay dates without overwriting the income or monthly net.
	 *
	 * @param string $application_id
	 * @param array $data
	 * @return bool
	 */
	public function updatePaydateModel($application_id, $data)
	{
		// We don't want to insert a blank string if income_frequency is blank or doesn't exist
		$income_frequency = isset($data['paydate_model']['income_frequency'])
				? $data['paydate_model']['income_frequency']
				: 'WEEKLY';
		
		$query = "
			UPDATE income
			SET
				pay_frequency = '{$income_frequency}',
				pay_date_1 = '{$data['paydates'][0]}',
				pay_date_2 = '{$data['paydates'][1]}',
				pay_date_3 = '{$data['paydates'][2]}',
				pay_date_4 = '{$data['paydates'][3]}'
			WHERE application_id = $application_id";

		$res = $this->sql->Query($this->database,$query);

		if (isset($data["paydate_model"]))
		{
			$sql_query = "DELETE from paydate where application_id = $application_id";
			$res = $this->sql->Query($this->database,$sql_query);

			// Day of week conversion array for monthly/semi-monthly paydate_model [LR]
			$dow = array('SUN' => 0, 'MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6);


			$query = "INSERT INTO paydate SET
				date_created = NOW(),
				application_id = $application_id,
				paydate_model_id = '{$data["paydate_model"]["model_name"]}'";

			// What fields are we supposed to insert
			if( isset($data["paydate_model"]["day_of_week"]) )
			{
				// This is good for weekly/biweekly, but won't work for some monthly/semi-monthly models
				$query .= ", day_of_week = {$data["paydate_model"]["day_of_week"]}";
			}

			// This is for the monthly/semi-monthly models that use day_string_one instead of day_of_week [LR]
			else if( isset($data["paydate_model"]["day_string_one"]) )
			{
				$query .= ", day_of_week = {$dow[$data["paydate_model"]["day_string_one"]]}";
			}

			if( isset($data["paydate_model"]["next_pay_date"]) )
			{
				$query .= ", next_paydate = '".date("Y-m-d", strtotime($data["paydate_model"]["next_pay_date"]))."'";
			}

			if( isset($data["paydate_model"]["day_int_one"]) )
			{
				$query .= ", day_of_month_1 = {$data["paydate_model"]["day_int_one"]}";
			}

			if( isset($data["paydate_model"]["day_int_two"]) )
			{
				$query .= ", day_of_month_2 = {$data["paydate_model"]["day_int_two"]}";
			}

			if( isset($data["paydate_model"]["week_one"]) )
			{
				$query .= ", week_1 = {$data["paydate_model"]["week_one"]}";
			}

			if( isset($data["paydate_model"]["week_two"]) )
			{
				$query .= ", week_2 = {$data["paydate_model"]["week_two"]}";
			}

			if( isset($data["paydate_model"]["accuracy_warning"]) )
			{
				$query .= ", accuracy_warning = 1 ";
			}


			$res = $this->sql->Query($this->database, $query);
		}

		return TRUE;
	}


	/**
	* @return null
	* @param $application_id string
	* @param $new_promo_id string
	* @param $new_promo_sub_code string
	* @desc Set new promotion codes for the given application_id
	*/
	public function Get_Campaign_Info($application_id)
	{
		try
		{
			$query = "
				SELECT *
				FROM
					campaign_info
				WHERE
					application_id=$application_id
				ORDER BY active DESC, created_date ASC";
			$result = $this->sql->Query ($this->database, $query);

			$i = 0;
			while($row = $this->sql->Fetch_Array_Row($result))
			{
				$campaigns[$i] = $row;
				++$i;
			}


		} catch (MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "working with the campaign_info tables in App_Campaign_Manager->Update_Campaign.");
		}

		return $campaigns;

	}

	/**
	* @return null
	* @param $data array/object
	* @desc Normalizes Data prior to Insert
	*/
	public function Escape_Data($data)
	{
		$escaped = array();

		foreach($data as $key => $sub_data)
		{
			if( is_array($sub_data) || is_object($sub_data) )
			{
				$escaped[$key] = $this->Escape_Data($sub_data);
			}
			else
			{
				if( is_string($sub_data) )
				{
					$sub_data = trim($sub_data);
				}

				$escaped[$key] = mysql_escape_string($sub_data);
			}
		}
		return $escaped;
	}


	/**
	 * Returns an array of holiday dates. It will grab it from LDB if $mysql is not NULL.
	 *
	 * @param object $mysql LDB MySQl connection.
	 * @return array
	 */
	public function Get_Holidays(&$mysql = NULL)
	{
		$holidays = array();

		if($mysql != NULL)
		{
			try
			{
				$result = $mysql->Query("
					SELECT holiday
					FROM holiday
					WHERE holiday BETWEEN DATE_SUB(CURDATE(), INTERVAL 90 DAY)
						AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)");

				while(($row = $result->Fetch_Array_Row()))
				{
					$holidays[] = $row['holiday'];
				}
			}
			catch(MySQL_Exception $e)
			{
				DB_Exception_Handler::Def($this->applog, $e, "attempting to get holiday array from ldb.");
			}
		}
		else
		{
			try
			{
				$query = "
					SELECT date
					FROM
						holidays
					WHERE
						date>=NOW()";
				$result = $this->sql->Query ('d2_management', $query);

				while($row = $this->sql->Fetch_Array_Row($result))
				{
					$holidays[] = $row['date'];
				}
			}
			catch (MySQL_Exception $e)
			{
				DB_Exception_Handler::Def($this->applog, $e, "attempting to get holiday array from d2_management.");
			}
		}

		return $holidays;

	}

	/**

	*/
	static public function Get_Todays_Campaign_Request(&$sql, $database, $promo_id)
	{

		$count = 0;

		try
		{

			$query = "SELECT COUNT(*) AS count FROM campaign_info, application
				WHERE application.application_id=campaign_info.application_id AND campaign_info.promo_id=$promo_id
				AND campaign_info.created_date>=CURRENT_DATE() AND application_type IN ('AGREED', 'CONFIRMED')";
			$result = $sql->Query ($database, $query);

			if ($result && ($row = $sql->Fetch_Array_Row($result)))
			{
				$count = $row['count'];
			}

		}
		catch (MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "working with the campaign_info tables in App_Campaign_Manager::Get_Todays_Campaign_Request.");
		}

		return $count;

	}

	public function Get_Application_Type($application_id)
	{
		$application_type = FALSE;

		try
		{

			$query = "SELECT application_type FROM application
				WHERE application_id=$application_id";

			//make sure we actualy have an application_id before trying to get type from it. gforge #4867 [TP]
			if(isset($application_id) && trim($application_id) != "")
			{
				$result = $this->sql->Query($this->database, $query);
				if ($row = $this->sql->Fetch_Array_Row($result))
				{
					$application_type = $row['application_type'];
				}
			}
		}
		catch (Exception $e)
		{
		}

		return($application_type);

	}

	public function Get_Olp_Process($application_id)
	{
		$olp_process = FALSE;

		try
		{
			$query = "SELECT olp_process FROM application WHERE application_id = {$application_id}";
			$result = $this->sql->Query($this->database, $query);

			if($row = $this->sql->Fetch_Array_Row($result))
			{
				$olp_process = $row['olp_process'];
			}
		}
		catch(Exception $e)
		{
		}

		return $olp_process;
	}

	/**
	* @return Data from personal table (if it exists), otherwise FALSE
	* @desc Let's check to see if we have a personal record for this customer.
	*/
	public function Get_Personal_Info($application_id)
	{

		$personal_info = FALSE;
		try
		{
			$query = "
			SELECT
				application_id,
				modified_date,
				first_name,
				middle_name,
				last_name,
				home_phone,
				cell_phone,
				fax_phone,
				email,
				alt_email,
				date_of_birth,
				contact_id_1,
				contact_id_2,
				social_security_number,
				drivers_license_number,
				best_call_time,
				drivers_license_state,
				email_agent_created,
				military
			FROM
				personal_encrypted
			WHERE
				application_id = $application_id";

			$result = $this->sql->Query($this->database, $query);

			if ($row = $this->sql->Fetch_Array_Row($result))
			{
				$personal_info = $row;
				$personal_info['date_of_birth'] = $this->crypt_object->decrypt($personal_info['date_of_birth']);
				$personal_info['social_security_number'] = $this->crypt_object->decrypt($personal_info['social_security_number']);
			}

		}
		catch (Exception $e)
		{
		}

		return($personal_info);
	}

	/**
	* @return application_id
	* @desc This function was created primarily to insert into the personal table
	* 		before we run blackbox.  We are getting several "submit happy" customers
	* 		from soap sites that are sending duplicate posts.  Without a personal record,
	* 		it's possible to pass bb rules multiple times within the time it takes for that
	* 		first soap app to loop through possible winners. [NR]
	*/

	public function Insert_Personal_Encrypted($application_id, $data)
	{
		$data = $this->Escape_Data($data);

		try
		{
			$query = $this->Application_Set_Personal_Encrypted($application_id, $data);
			$query = 'REPLACE INTO personal_encrypted ' . $query;
			$result = $this->sql->Query($this->database, $query);
		}
		catch(MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id PERSONAL_ENCRYPTED record insert process failed.");
			return FALSE;
		}

		return $application_id;
	}

	public function Insert_Cell_Phone($application_id, $cell)
	{
		try
		{
			$query = "INSERT INTO personal_encrypted (application_id, cell_phone) VALUES ({$application_id}, '{$cell}')";
			$result = $this->sql->Query($this->database, $query);
		}
		catch(MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id CELL_PHONE record insert process failed.");
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Inserts the CoReg reponse and sent data into the coreg_request table.
	 *
	 * @param int $application_id
	 * @param array $recv
	 * @param string $sent
	 * @param string $success
	 */
	public function Insert_Coreg_Response($application_id, $type, $recv, $sent, $success)
	{
		// Convert the array to a string
		if(is_array($recv))
		{
			$recv = print_r($recv, true);
		}

		// There is no such thing as magic
		if(get_magic_quotes_gpc())
		{
			$recv = stripslashes($recv);
			$sent = stripslashes($sent);
		}

		$compressed_recv = mysql_real_escape_string(gzcompress($recv));
		$compressed_sent = mysql_real_escape_string(gzcompress($sent));

		$query = "
			INSERT INTO
				coreg_request
			SET
				application_id = $application_id,
				coreg_type = '$type',
				date_created = NOW(),
				data_recv = '$compressed_recv',
				data_sent = '$compressed_sent',
				success = '$success'";

		try
		{
			$this->sql->Query($this->database, $query);
		}
		catch(Exception $e) {}
	}


	/**
	 * Sets the flag for is_react in the DB
	 *
	 * @param int $application_id
	 */
	public function Update_Is_React($application_id)
	{
		$query = "UPDATE application SET is_react = 1 WHERE application_id = $application_id";

		try
		{
			$this->sql->Query($this->database, $query);
		}
		catch(MySQL_Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "Application $application_id is_react insert process failed.");
			return FALSE;
		}

		return $application_id;
	}

	/**
	 * Updates the loan_note table with updated information.
	 *
	 * @param int $application_id
	 * @param string $estimated_fund_date
	 * @param int $fund_amount
	 * @param string $apr
	 * @param string $estimated_payoff_date
	 * @param int $total_payments
	 */
	public function Update_Loan_Note(
		$application_id,
		$fund_date,
		$fund_amount,
		$apr,
		$payoff_date,
		$finance_charge,
		$total_payments)
	{
		$query = "
			/* File: ".__FILE__.", Line: ".__LINE__." */
			UPDATE loan_note
			SET
				estimated_fund_date = '$fund_date',
				actual_fund_date = '$fund_date',
				fund_amount = $fund_amount,
				finance_charge = $finance_charge,
				apr = $apr,
				estimated_payoff_date = '$payoff_date',
				total_payments = $total_payments
			WHERE
				application_id = $application_id";

		try
		{
			$this->sql->Query($this->database, $query);
		}
		catch(Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id loan_note record insert process failed.");
		}
	}

	/**
	 * Updates the estimated payoff date in the database
	 *
	 * @param string $application_id
	 * @param string $payoff_date
	 */
	public function updatePayoffDate($application_id, $payoff_date)
	{
		$query = "
			UPDATE loan_note
			SET estimated_payoff_date = '$payoff_date'
			WHERE application_id = $application_id";

		try
		{
			$this->sql->Query($this->database, $query);
		}
		catch(Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id estimated_payoff_date update process failed.");
		}
	}


	public function Update_Fund_Amount($application_id, $fund_amount)
	{
		$query = "
			/* File: ".__FILE__.", Line: ".__LINE__." */
			UPDATE loan_note
			SET
				fund_amount = $fund_amount
			WHERE
				application_id = $application_id";

		try
		{
			$this->sql->Query($this->database, $query);
		}
		catch(Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id loan_note record insert process failed.");
		}
	}


	public function Update_Bank_Info_Encrypted($application_id, $aba, $acct)
	{

		$acct_encrypted = $this->crypt_object->encrypt($acct);
		$aba_encrypted = $this->crypt_object->encrypt($aba);

		$query = "
			/* File: ".__FILE__.", Line: ".__LINE__." */
			UPDATE bank_info_encrypted
			SET
				account_number = '{$acct_encrypted}',
				routing_number = '{$aba_encrypted}'
			WHERE
				application_id = {$application_id}
			LIMIT 1";

		try
		{
			$this->sql->Query($this->database, $query);
		}
		catch(Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id bank_info record insert process failed.");
		}
	}

	public function Update_Paydate($application_id, $data)
	{
		$query = "
			/* File: ".__FILE__.", Line: ".__LINE__." */
			UPDATE paydate
			SET
		";

		$set = array();
		foreach($data as $column => $value)
		{
			if(!empty($value) || $value === 'NULL')
			{
				$value = mysql_escape_string(trim($value));
				$set[] = "{$column} = '{$value}'";
			}
			else
			{
				$set[] = "{$column} = NULL";
			}
		}

		$query .= implode(',', $set) .
			" WHERE application_id = {$application_id} LIMIT 1";

		try
		{
			$this->sql->Query($this->database, $query);
		}
		catch(Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id paydate record insert process failed.");
		}
	}



	public function Update_Income($application_id, $pay_frequency)
	{
		$pay_frequency = strtoupper($pay_frequency);
		$this->applog->Write('Updating pay frequency to: ' . $pay_frequency);

		$query = "
			/* File: ".__FILE__.", Line: ".__LINE__." */
			UPDATE income
			SET
				pay_frequency = '{$pay_frequency}'
			WHERE
				application_id = {$application_id}
			LIMIT 1";

		try
		{
			$this->sql->Query($this->database, $query);
		}
		catch(Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id income record insert process failed.");
		}
	}



	public function Insert_Loan_Action($application_id, $loan_action)
	{
		try
		{
			$query = "
			/* File: ".__FILE__.", Line: ".__LINE__." */
			INSERT INTO application_loan_action (application_id, action_name)
				VALUES ({$application_id}, '{$loan_action}')";

			$this->sql->Query($this->database, $query);
		}
		catch(Exception $e)
		{
			DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id loan action record insert process failed.");
		}
	}



	/**
	 * Checks for other applications with the same social security number
	 * in a given day. Returns TRUE if the SSN is unique for a calendar day.
	 *
	 * An application is considered unique if all of the following hold true:
	 *  * SSN has not been used in an application in the calendar day
	 *  * The application has been submitted
	 *
	 * @param string $ssn Customer's social security number.
	 * @return bool TRUE if the application is unique, FALSE otherwise
	 */
	public function Check_Unique_Lead($ssn)
	{
		$ssn = mysql_escape_string($ssn);
		$ssn_encrypted = $this->crypt_object->encrypt($ssn);

		$query = "
			SELECT
				COUNT(a.application_id) app_count
			FROM
				personal_encrypted p
				INNER JOIN application a ON p.application_id = a.application_id
			WHERE
				a.created_date > '".date('Y-m-d 00:00:00')."'
				AND p.social_security_number = '$ssn_encrypted'
				AND a.application_type != 'VISITOR'";

		try
		{
			$result = $this->sql->Query($this->database, $query);

			// If it equals one (1), because we have to account for this application
			$unique_app = $this->sql->Fetch_Object_Row($result)->app_count == 1 ? TRUE : FALSE;
		}
		catch(Exception $e)
		{
			DB_Exception_Handler::Def(
				$this->applog,
				$e,
				"Check_Unique_Lead() in App_Campaign_Manager."
			);
			$unique_app = FALSE;
		}

		return $unique_app;
	}

	/**
	 * Document Event
	 *
	 * Inserts the condor doc id into the db
	 * @param int Application ID
	 * @param int Condor Doc ID
	 * @return boolean True on success
	 */
	public function Document_Event($application_id, $condor_doc_id)
	{
		$application_id = (int)$application_id;
		if(!isset($condor_doc_id))
		{
			$condor_doc_id = 0;
		}
		else
		{
			$condor_doc_id = (int)$condor_doc_id;
		}

		$query = "
			/* File: ".__FILE__.", Line: ".__LINE__." */
			INSERT INTO application_documents
				(application_id, document_id)
			VALUES ({$application_id}, {$condor_doc_id})
			ON DUPLICATE KEY UPDATE
				application_id = {$application_id},
				document_id = {$condor_doc_id}";

		try
		{
			$result = $this->sql->Query($this->database, $query);
		}
		catch(Exception $e)
		{
			throw $e;
		}

		return true;
	}


	/**
	 * Is Preact
	 *
	 * Checks whether the current app is a preact
	 * @return boolean True if preact
	 */
	public function Is_Preact()
	{
		if(isset($_SESSION['is_preact']) ||
		  (isset($_SESSION['cs']['olp_process']) && $_SESSION['cs']['olp_process']=='ecashapp_preact'))
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	public function Get_Personal_Contacts($application_id)
	{
		$contacts = array();

		if(!empty($application_id) && is_numeric($application_id))
		{

			try
			{
				$query = "SELECT full_name, phone, relationship
					FROM personal_contact
					WHERE application_id = {$application_id}";

				$result = $this->sql->Query($this->database, $query);

				$x = 0;
				while($ref = $this->sql->Fetch_Array_Row($result))
				{
					$x++;
					$contacts[$x]['name_full']		= $ref['full_name'];
					$contacts[$x]['phone_home']		= $ref['phone'];
					$contacts[$x]['relationship']	= $ref['relationship'];
				}
			}
			catch(MySQL_Exception $e)
			{
				DB_Exception_Handler::Def($this->applog, $e, "BB Application $application_id failed to get reference count.");
			}

		}

		return $contacts;
	}
	public function Get_Winner($application_id)
	{
		$winner = null;

		if(!empty($application_id))
		{
			try
			{
				$query = "SELECT property_short FROM application INNER JOIN target USING (target_id) WHERE application_id = {$application_id}";
				$result = $this->sql->Query($this->database, $query);

				if($row = $this->sql->Fetch_Array_Row($result))
				{
					$winner= $row['property_short'];
				}
			}
			catch(Exception $e)
			{
			}
		}

		return $winner;
	}




	/**
	 * Add a new campaign info record with a new promo and/or sub code without going through
	 * the stupid crap promo_override makes you go through.  Hooray!
	 *
	 * @param string $property_short
	 * @param int $application_id
	 * @param string $license
	 * @param int $promo_id
	 * @param string $promo_sub_code
	 */
	public static function updatePromo($property_short, $application_id, $license, $promo_id, $promo_sub_code = null)
	{
		if(!empty($property_short) && !empty($application_id) && !empty($promo_id))
		{
			//Set up a fake config
			$config = new stdClass();
			$config->promo_id = $promo_id;
			$config->promo_sub_code = $promo_sub_code;
			$config->property_short = Enterprise_Data::resolveAlias($property_short);
			$config->license = $license;

			try
			{
				$sql = Setup_DB::Get_Instance('blackbox', BFW_MODE, $property_short);
				$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, 'ACM', APPLOG_ROTATE, APPLOG_UMASK);

				$app_campaign_manager = new App_Campaign_Manager($sql, $sql->db_info['db'], $applog);
				$app_campaign_manager->Update_Campaign($application_id, $config);

				if(!$_SESSION['config']->use_new_process)
				{
					$olp_db = OLP_LDB::Get_Object($config->property_short);
					$olp_db->Insert_Campaign_Info($application_id, $config);
				}
			}
			catch(Exception $e)
			{
				$this->Applog_Write('Failed to update promo information for application ' . $application_id);
			}
		}
	}


	public function Update_Denied_Target($application_id, $target)
	{
		//We don't need to get the target_id if it was passed in
		if(!is_numeric($target))
		{
			try
			{
				$query = "SELECT target_id FROM target WHERE property_short = '{$target}'";
				$result = $this->sql->Query($this->database, $query);

				if($row = $this->sql->Fetch_Array_Row($result))
				{
					$target = $row['target_id'];
				}
			}
			catch(Exception $e)
			{
				$target = null;
			}
		}


		if(is_numeric($target) && !empty($application_id))
		{
			$query =
				"UPDATE application
					SET denied_target_id = '{$target}'
				WHERE application_id = {$application_id}
				LIMIT 1";

			try
			{
				$result = $this->sql->Query($this->database, $query);
			}
			catch(MySQL_Exception $e)
			{
				$this->applog->Write("Failed to update application with denied_target_id: {$query}");
			}
		}
	}

	private function bad_aba($aba)
	{
		$aba_suppression_list_names = array(
			"CLK ABA Performance Suppression List",
		);

		$aba_supp_list = $this->get_bad_abas($aba_suppression_list_names);

		return (in_array($aba,$aba_supp_list)) ?  1 : 0;
	}

	private function get_bad_abas($aba_list_names)
	{
		$bad_abas = array();

		foreach($aba_list_names as $name)
		{
			$rev_query = "
				SELECT
					*
				FROM
					lists l
				JOIN
					list_revisions lr USING (list_id)
				WHERE l.name = '$name' and lr.status = 'ACTIVE'
			";

			$result = $this->sql->Query($this->database,$rev_query);
			$row = $this->sql->Fetch_Array_Row($result);
			$revision_id = $row['revision_id'];

			$query = "
				SELECT
					value
				FROM
					lists l
				JOIN list_revision_values lvr USING (list_id)
				JOIN list_values lv USING (value_id)
				WHERE l.name = '$name'
				and lvr.revision_id = $revision_id;
			";

			$result = $this->sql->Query($this->database,$query);

			while($row = $this->sql->Fetch_Array_Row($result))
			{
				$bad_abas[] = $row['value'];
			}
		}

		return $bad_abas;
	}

	public function Get_Asynch_Result($app_id)
	{
		$query = "
			SELECT asynch_result_object
			FROM asynch_result
			WHERE application_id = '{$app_id}'
		";
		$r = $this->sql->Query($this->database, $query);

		if ($row = $this->sql->Fetch_Array_Row($r))
		{
			// see vendor_post_impl_cfe.php::SaveAsynchResult()
			return unserialize(gzuncompress($row['asynch_result_object']));
		}
		return FALSE;
	}

	/**
	 * Tags an application with the given tag
	 * For Do Not Loan, these tags are later imported into ldb by import_ldb
	 *
	 * @param int $app_id The application ID to be tagged
	 * @param string $tag_name The name of the application tag
	 * @return bool
	*/
	public function Tag_Application($app_id, $tag_name)
	{
		$tag_id = $this->getApplicationTagId($tag_name);

		// First check for duplicate entries where application ID and tag_id combinations already exist.
		// This should exclude the subsequent attempts to set a particular tag for this app_id for the same company.
		if (!$this->hasApplicationTag($app_id, $tag_id))
		{
			$tag_query = "
				INSERT INTO application_tags
					(tag_id, application_id, date_created)
				VALUES
					({$tag_id}, {$app_id}, NOW())
			";
			$result = $this->sql->Query($this->db, $tag_query);

			return ($result == TRUE);
		}
		return FALSE;
	}

	/**
	 * Indicates whether an application has the given tag
	 *
	 * @param int $app_id
	 * @param int $tag_id
	 * @return bool
	 */
	protected function hasApplicationTag($app_id, $tag_id)
	{
		$query = "
			SELECT app_tag_id
			FROM application_tags
			WHERE application_id = '{$app_id}'
				AND tag_id = '{$tag_id}'
		";
		$result = $this->sql->Query($this->db, $query);

		return ($result &&
			$this->sql->Row_Count($result) > 0);
	}

	/**
	 * Gets a tag_id by name.
	 * Returns FALSE on error.
	 *
	 * @param string $name
	 * @return int
	 */
	protected function getApplicationTagId($name)
	{
		$query = "
			SELECT tag_id
			FROM application_tag_details
			WHERE tag_name='{$tag_name}'
		";
		$result = $this->sql->Query($this->db, $query);

		if ($id_result
			&& ($row = $this->sql->Fetch_Array_Row($result)) !== FALSE)
		{
			return $row['tag_id'];
		}
		return FALSE;
	}
}

?>
