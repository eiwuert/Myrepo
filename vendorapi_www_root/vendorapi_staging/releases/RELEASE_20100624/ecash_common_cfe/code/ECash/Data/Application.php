<?php

class ECash_Data_Application extends ECash_Data_DataRetriever
{
	/**
	 * MSSQL has an insert limit of 1,000, so to
	 * be safe it's being limited to 999.
	 */
	const INSERT_LIMIT = 999;

	/**
	 * Batch the data retrieval to sets of this size
	 */
	const BATCH_SIZE_LIMIT = 1998;
	
	/**
	 * Maximum number of inserts that can be done into a SQL Server table type.
	 * 
	 * @var int
	 */
	const MAX_INSERTS = 1000;
	
	/**
	 * Returns the application service database connection.
	 * 
	 * @return DB_IConnection_1
	 */
	protected function getApplicationServiceDb()
	{
		return ECash::getAppSvcDB();
	}

	/**
	 * Used to limit the search on the Application Service
	 * to this many results.  This is limited for speed.
	 */
	const DUPE_IP_ADDRESS_LIMIT = 25;

	/**
	 * Returns some basic campaign info for the application
	 *
	 * @param <string> $application_id
	 * @result <Stdclass> - Object containing various pieces of campaign info
	 */
	public function getCampaignInfo($application_id)
	{
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$application_info = $app_client->getApplicationInfo($application_id);
		$campaign_info_records = $app_client->getCampaignInfo($application_id);

		$enterprise_url = NULL;

		if(! empty($application_info->enterpriseSiteId))
		{
			$site = ECash::getFactory()->getModel('Site');
			$site->loadByKey($application_info->enterpriseSiteId);
			$enterprise_url = $site->name;
			$license_key    = $site->license_key;
		}

		$result = array();
		$result['origin_url']     = NULL;
		$result['url']            = NULL;
		$result['campaign_name']  = NULL;
		$result['promo_id']       = NULL;
		$result['promo_sub_code'] = NULL;
		$result['license_key']    = NULL;

		if(! empty($campaign_info_records))
		{
			// We only want the LAST record.
			$record = max($campaign_info_records);

			$result['origin_url']     = $record->site;
			$result['url']            = $enterprise_url;
			$result['campaign_name']  = $record->campaign_name;
			$result['promo_id']       = $record->promo_id;
			$result['promo_sub_code'] = $record->promo_sub_code;

			$result['license_key']    = $license_key;
		}

		return $result;
	}

	public function getLoanTypeId($application_id)
	{
		$query = "
			select loan_type_id
			from application
			where
				application_id = ?
		";

		return DB_Util_1::querySingleValue($this->db, $query, array($application_id));
	}

	public function getStatusId($application_id)
	{
		$query = "
			select application_status_id
			from application
			where
				application_id = ?
		";

		return DB_Util_1::querySingleValue($this->db, $query, array($application_id));
	}

	public function getLoanAmountMetrics($application_id)
	{
		$query = "
			select
				income_monthly,
				is_react,
				rule_set_id
			from application
			where
				application_id = ?
		";

		return DB_Util_1::querySingleRow($this->db, $query, array($application_id));
	}

	public function getCompanyId($application_id)
	{
		$query = "
			select company_id
			from application
			where
				application_id = ?
		";

		return DB_Util_1::querySingleValue($this->db, $query, array($application_id));
	}

	public function getPersonalReferences($application_id)
	{
		return ECash::getFactory()->getModel("PersonalReference")
			->loadAllBy(array("application_id" => $application_id))
			->toArray();
	}

	public function getPreviousStatusId($application_id)
	{
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$history_list = $app_client->getApplicationStatusHistory($application_id)->item;

		if(count($history_list) > 1)
		{
			$index = count($history_list) - 2;
		}
		else
		{
			$index = count($history_list) - 1;
		}

		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		return $asf->toId($history_list[$index]->applicationStatus);
	}

	public function getAuditLog($application_id)
	{
		$adds = array();
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$audit_info = $app_client->getApplicationAudit($application_id);

		if(is_array($audit_info) && !empty($audit_info))
		{
			foreach ($audit_info as $data_array)
			{
				/**
				 * if the field changed is a date, the date needs to be hacked to be able to string
				 * compare the dates
				 */
				if (
					strpos($data_array->column_name, 'date') !== FALSE
					&& ( // the above line by itself will do terrible things with columns like paydate_model
						strtotime($data_array->old_value) !== FALSE
						|| strtotime($data_array->new_value) !== FALSE
					)
				)
				{
					$data_array->old_value = date('Y-m-d', strtotime($data_array->old_value));
					$data_array->new_value = date('Y-m-d', strtotime($data_array->new_value));
				}

				if (!empty($data_array->table_name))
				{
					$agent = ECash_Agent::getByAgentId($this->db, $data_array->modifying_agent_id, $company_id);

					/* do some conversions to mock the previous output */
					$data_array->name_first = $agent->getNameFirst();
					$data_array->name_last = $agent->getNameLast();
					$data_array->agent_id = $data_array->modifying_agent_id;
					$data_array->date_created = date('Y-m-d H:i:s', strtotime($data_array->date_updated));
					$data_array->modification = "";
					$data_array->value_before = $data_array->old_value;
					$data_array->value_after = $data_array->new_value;

					/* remove unwanted data */
					unset($data_array->modifying_agent_id);
					unset($data_array->date_updated);
					unset($data_array->application_id);
					unset($data_array->primary_key_name);
					unset($data_array->primary_key_value);
					unset($data_array->old_value);
					unset($data_array->new_value);

					$adds[] = $data_array;
				}
			}
		}

		return $adds;
	}

	/**
	 * Unset the attribute on column '$column' in table '$table' for row id '$row_id'
	 *
	 * @param int $row_id
	 * @param string $flag
	 * @param string $column
	 * @param string $company_id
	 * @param string $table
	 */	
	public function clearContactFlags($flag, $column, $table = 'application', $company_id, $row_id, $agent_id = NULL)
	{
		/* GF #23262
         * This is a bit of a hack, but it works. We need to store the id of the Agent
		 * that removed the flag from the app. Unfortunately the SQL trigger that 
		 * catches DELETE ON application_field has no way of knowing the current Agent
		 * ID. So since we are going to be deleting this row in a few milliseconds anyway,
		 * we are going to store the modifying agent's ID in the row we are going to delete.
		 * Then the trigger can insert the agent ID from the row into the audit log
		 * and then MySQL can go about with deleting the row. [kb][01-08-2008]
		 */
		$args = array('company_id' => $company_id,
					   'table_row_id' => $row_id,
					   'column_name' => $column,
					   'table_name' => $table,
					   'application_field_attribute_id' => $flag,
					   'agent_id' => $agent_id);

		$query = "
			UPDATE application_field
			SET agent_id = :agent_id
			WHERE company_id = :company_id
			AND table_row_id = :table_row_id
			AND column_name = :column_name
			AND table_name = :table_name
			AND application_field_attribute_id = :application_field_attribute_id
			";
		
		DB_Util_1::execPrepared($this->db, $query, $args);			

		//Now Delete The Row
		$query = "
			delete from application_field
			where
				company_id = ?
				and table_row_id = ?
				and column_name = ?
				and table_name = ?
				and application_field_attribute_id = ?
		";

		DB_Util_1::execPrepared($this->db, $query, array(
			$company_id,
			$row_id,
			$column,
			$table,
			$flag
		));
	}	

	/**
	 * Unset the attribute on column '$column' in table '$table' for row id '$row_id'
	 *
	 * @param int $row_id
	 * @param string $flag
	 * @param string $column
	 * @param string $table
	 */
	public function clearContactFlagsByColumn($column, $table = 'application', $company_id, $row_id)
	{
		$query = "
			delete from application_field
			where
				company_id = ?
				and table_row_id = ?
				and column_name = ?
				and table_name = ?
		";

		DB_Util_1::execPrepared($this->db, $query, array(
			$company_id,
			$row_id,
			$column,
			$table
		));
	}
	
	public function clearContactFlagsByType($flag, $table = 'application', $company_id, $row_id)
	{
		$query = "
			delete from application_field
			where
				company_id = ?
				and table_row_id = ?
				and table_name = ?
				and application_field_attribute_id = ?
		";

		DB_Util_1::execPrepared($this->db, $query, array(
			$company_id,
			$row_id,
			$table,
			$flag
		));
	}	
	
	public function clearContactFlagsByRow($table = 'application', $company_id, $row_id)
	{
		$query = "
			delete from application_field
			where
				company_id = ?
				and table_row_id = ?
				and table_name = ?
		";

		DB_Util_1::execPrepared($this->db, $query, array(
			$company_id,
			$row_id,
			$table
		));
	}	
	
	public function getContactFlags($table = 'application', $row_id)
	{
		$query = "
			SELECT
				af.column_name,
				afa.field_name,
				af.agent_id
			FROM application_field af
			INNER JOIN application_field_attribute afa
				ON (afa.application_field_attribute_id = af.application_field_attribute_id)
			WHERE
				table_name = ?
				AND table_row_id = ?
		";

		$result = DB_Util_1::queryPrepared($this->db, $query, array($table, $row_id));

		return $result->fetchAll(PDO::FETCH_OBJ);
	}	
	
	public function setFlag($flag ,$agent_id, $application_id, $company_id)
	{
		$query = "
			INSERT INTO application_flag SET
				modifying_agent_id = :agent_id,
				flag_type_id = (select flag_type_id from flag_type where name_short = :flag_name_short),
				application_id = :application_id,
				company_id = :company_id,
				active_status = 'active'
			ON DUPLICATE KEY UPDATE active_status = 'active', modifying_agent_id = :agent_id, company_id = :company_id";

		$args = array(
			'agent_id' => $agent_id,
			'flag_name_short' => $flag,
			'application_id' => $application_id,
			'company_id' => $company_id
		);

		$row_count = DB_Util_1::execPrepared($this->db, $query, $args);
		return ($row_count > 0);
	}

	public function clearFlag($flag,$agent_id,$application_id, $company_id)
	{
		$query = "
			UPDATE application_flag
			SET
				active_status = 'inactive',
				modifying_agent_id = :agent_id,
				company_id = :company_id
			WHERE
				application_id = :application_id
				AND flag_type_id = (select flag_type_id from flag_type where name_short = :flag_name_short)
		";

		$args = array(
			'agent_id' => $agent_id,
			'flag_name_short' => $flag,
			'application_id' => $application_id,
			'company_id' => $company_id
		);

		DB_Util_1::execPrepared($this->db, $query, $args);
	}	
	
	public function getFlag($flag,$application_id)
	{
		$query = "
			SELECT COUNT(*)
			FROM application_flag
			JOIN flag_type ON (flag_type.flag_type_id = application_flag.flag_type_id)
			WHERE
				application_id = ?
				AND flag_type.name_short = ?
				AND application_flag.active_status = 'active'
				AND flag_type.active_status = 'active'
			";

		return (DB_Util_1::querySingleValue(
			$this->db,
			$query,
			array($application_id, $flag)
			) > 0
		);
	}	
	
	public function getFlags($application_id)
	{
		$query = "
			SELECT
				flag_type.*
			FROM application_flag
			JOIN flag_type USING (flag_type_id)
			WHERE
				application_id = ?
				AND application_flag.active_status = 'active'
		";


		return DB_Util_1::queryPrepared($this->db, $query, array($application_id));

	}
	
	public function removeTags($prefix, $application_id)
	{
			$query = "
				DELETE FROM 
					application_tags 
				WHERE 
					application_id = ? 
				AND EXISTS (
						SELECT 1
						FROM application_tag_details
						WHERE
						tag_id = application_tags.tag_id AND
						tag_name LIKE '?%'
					)";
			
			DB_Util_1::execPrepared($this->db, $query, array($application_id, $prefix));					

	}
	/**
	 * Get Application Data - Used for Documents & Tokens!
	 *
	 * Selects applicaton information for the LDB (eCash Database)
	 * 
	 * @param int $application_id
	 * @return object $application
	 */
	public function Get_Application_Data($application_id)
	{
		$app_model = ECash::getFactory()->getModel('Application');
		$app_model->loadByKey($application_id);
		$app_model->loadLegacyAll($application_id, $row);

		if(empty($row->application_id))
		{
			throw new ECash_Application_NotFoundException("No Application data {$application_id}");
		}

		$row->fund_amount = (isset($row->fund_actual) && $row->fund_actual > 0) ? $row->fund_actual : $row->fund_qualified;

		$row->date_first_payment = date('m/d/Y', $app_model->date_first_payment);
		$row->date_first_payment_day   = date('d', $app_model->date_first_payment);
		$row->date_first_payment_month = date('m', $app_model->date_first_payment);
		$row->date_first_payment_year  = date('Y', $app_model->date_first_payment);

		if(! empty($app_model->date_fund_actual))
		{
			$row->date_fund_actual_day   = date('d', $app_model->date_fund_actual);
			$row->date_fund_actual_month = date('m', $app_model->date_fund_actual);
			$row->date_fund_actual_year  = date('Y', $app_model->date_fund_actual);
			$row->date_fund_actual_ymd   = date('Y-m-d', $app_model->date_fund_actual);
		}
		else
		{
			$row->date_fund_actual_day   = date('d');
			$row->date_fund_actual_month = date('m');
			$row->date_fund_actual_year  = date('Y');
			$row->date_fund_actual_ymd   = date('Y-m-d');
		}

		if(! empty($app_model->date_fund_actual))
		{
			$row->date_fund_stored = date('m-d-Y', $app_model->date_fund_actual);
		}
		else
		{
			$row->date_fund_stored = '';
		}

		/**
		 * Use a safe estimation date
		 */
		$today = strtotime(date('Y-m-d'));
		$tomorrow = strtotime("+1 day", $today);
		$estimated = strtotime($app_model->date_fund_estimated);
		if($estimated < $tomorrow)
		{
			$estimated = $tomorrow;
		}

		$row->date_fund_estimated_day = date('d', $estimated);
		$row->date_fund_estimated_month = date('m', $estimated);
		$row->date_fund_estimated_year = date('Y', $estimated);
		$row->date_fund_estimated_ymd = date('Y-m-d', $estimated);
		$row->date_fund_estimated = date('m-d-Y', $estimated);
		$row->original_fund_estimate_date = date('Y-m-d', $app_model->date_fund_estimated);

		$row->customer_county = $row->county;
		$row->login_id = $row->login;
		
		//asm 83
		/*
		$company_id = $app_model->company_id;
		$tt_model = ECash::getFactory()->getModel('TransactionType');
		$loaded = $tt_model->loadBy(array('company_id' => $company_id, 'name_short' => 'converted_principal_bal',));
		if ($loaded)
		{
			$transaction_type_id = $tt_model->transaction_type_id;

			$amount = 0;
			$tr_model = ECash::getFactory()->getModel('TransactionRegister');
			$tr_array = $tr_model->loadAllBy(array('application_id' => $application_id, 'transaction_type_id' => $transaction_type_id,));
			if ($tr_array->count() > 0)
			{
				foreach($tr_array as $tr)
				{
					$amount = $amount + intval($tr->amount);
				}

				$row->converted_principal_bal_amount = $amount;
			}
			else
			{
				$row->converted_principal_bal_amount = 0;
        
				$ssn = $app_model->ssn;
				$customer = ECash::getFactory()->getCustomerBySSN($ssn, $company_id);
				$applications = $customer->getApplications();
				
				foreach($applications as $app)
				{
					$status = $app->getStatus();
					if(
						($status->level0 == 'refi') && ($app->application_id != $application_id)
					)
					{
						$balance_info = Fetch_Balance_Information($app->application_id);
						$row->converted_principal_bal_amount = abs($balance_info->principal_balance);
						break;
					}
				}
			}
		}
		else
		{
			$row->converted_principal_bal_amount = 0;

			$ssn = $app_model->ssn;
			$customer = ECash::getFactory()->getCustomerBySSN($ssn, $company_id);
			$applications = $customer->getApplications();
			
			foreach($applications as $app)
			{
				$status = $app->getStatus();
				if(
					($status->level0 == 'refi') && ($app->application_id != $application_id)
				)
				{
					$balance_info = Fetch_Balance_Information($app->application_id);
					$row->converted_principal_bal_amount = abs($balance_info->principal_balance);
					break;
				}
			}
		}
		*/

		//tribal
		$app_server_model = ECash::getFactory()->getModel('ApplicationServer');
		
		$app_server_model->loadBy(array('application_id' => $application_id));
		$row->tribal_ip = $app_server_model->server_ip;
		$row->tribal_response_date_time = $app_server_model->date_created;
		/*
		$app_server_array = $app_server_model->loadAllBy(array('application_id' => $application_id));
		if ($app_server_array->count() > 0)
		{
			foreach($app_server_array as $as)
			{
				$row->tribal_ip = $as->server_ip;
				$row->tribal_response_date_time = $as->date_created;
			}
		}
		*/
		return $row;
	}

	/**
	 * Meant to replace agean_api countNumberPaidApplications()
	 */
	public function getNumberPaidApplications($application_id, $company_id)
	{		
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$paid_status_id = $asf->toId('paid::customer::*root');
		$settled_status_id = $asf->toId('settled::customer::*root');
		$refi_status_id = $asf->toId('refi::servicing::customer::*root');

		$sql = "
        SELECT 	count(application_id) as num_paid
		FROM application
		WHERE ssn = (SELECT ssn
					 FROM application
					 WHERE application_id = ?)
		AND company_id = ? 
		AND application_status_id IN (?, ?, ?)";

		return DB_Util_1::querySingleValue($this->db, $sql, array($application_id, $company_id, $paid_status_id, $settled_status_id, $refi_status_id));
	}

	/**
	 * Meant to replace agean_api Fetch_Application_List()
	 */
	public function getPrevMaxQualify($application_id, $company_id)
	{		
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$sql = "
	        SELECT 	fund_qualified
			FROM application
			WHERE ssn = (SELECT ssn
						 FROM application
						 WHERE application_id = ?)
				AND company_id = ?
				AND fund_actual IS NOT NULL
				AND application_status_id NOT IN (
					SELECT application_status_id FROM application_status WHERE name IN ('Withdrawn','Cancel')
				)
			ORDER BY fund_qualified DESC";

		$max_qualified = DB_Util_1::querySingleValue($this->db, $sql, array($application_id, $company_id));
	
			$sql = "
		SELECT 	fund_actual
			FROM application
			WHERE ssn = (SELECT ssn
						 FROM application
						 WHERE application_id = ?)
				AND company_id = ?
				AND fund_actual IS NOT NULL
				AND application_status_id NOT IN (
					SELECT application_status_id FROM application_status WHERE name IN ('Withdrawn','Cancel')
				)
			ORDER BY fund_actual DESC";
	
		$max_actual = DB_Util_1::querySingleValue($this->db, $sql, array($application_id, $company_id));
		
		$max_funded = max($max_qualified*1,$max_actual*1,0);
        
		return $max_funded;
	}
	
	/**
	 * For VendorAPI
	 */
	public function getNumberPaidApplicationsBySSN($ssn, $company_id)
	{		
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$paid_status_id = $asf->toId('paid::customer::*root');
		$settled_status_id = $asf->toId('settled::customer::*root');
		$refi_status_id = $asf->toId('refi::servicing::customer::*root');
		
		$sql = "
         SELECT 	count(application_id) as num_paid
		FROM application
		WHERE ssn = ?
		AND company_id = ? 
		AND application_status_id IN (?, ?, ?)";

		return DB_Util_1::querySingleValue($this->db, $sql, array($ssn, $company_id, $paid_status_id, $settled_status_id, $refi_status_id));
	}	
	/**
	 * For VendorAPI
	 */
	public function getNumberActiveApplicationsBySSN($ssn, $company_id)
	{		
		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$active_status_id = $asf->toId('active::servicing::customer::*root');
		
		$sql = "
         SELECT count(application_id) as num_paid
		FROM application
		WHERE ssn = ?
		AND company_id = ? 
		AND application_status_id = ?";

		return DB_Util_1::querySingleValue($this->db, $sql, array($ssn, $company_id, $active_status_id));
	}
	
	/**
	 * Returns the Delinquency Date (only used by Agean ATM).  Replaces Agean_eCash_API_2::getDelinquencyDate()
	 *
	 * @param integer $application_id
	 * 
	 * @return string 
	 */
	public function getDelinquencyDate($application_id)
	{
		$application = ECash::getApplicationById($application_id);
		$status_history = $application->getStatusHistory();

		$delinquency_date = NULL;

		foreach($status_history as $rec)
		{
			if($rec->applicationStatus == 'queued::contact::collections::customer::*root')
			{
				$delinquency_date = $rec->dateCreated;
				break;
			}
		}

		return $delinquency_date;

	}
        /**
         * Returns delinquent applications (only used by Agean ATM).
         *
         * @param integer $days
         *
         * @return array of integers (appliation_id)
         */
        public function getDelinquentApplications($days)
        {
                $asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

                $collections_contact_id = $asf->toId('queued::contact::collections::customer::*root');

                $values = array(
                        $asf->toId('past_due::servicing::customer::*root'),
                        $asf->toId('new::collections::customer::*root'),
                        $asf->toId('collections::customer::*root'),
                        $asf->toId('arrangements_failed::arrangements::collections::customer::*root'),
                        $asf->toId('indef_dequeue::collections::customer::*root'),
                        $asf->toId('dequeued::contact::collections::customer::*root'),
                        $asf->toId('current::arrangements::collections::customer::*root'),
                        $collections_contact_id
                        );

                $placeholders = substr(str_repeat('?,', count($values)), 0, -1);

                //let's make this query not non-deterministic
                $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

                $query = "
                        SELECT sh.application_id
                        FROM status_history sh
                        JOIN application app on (app.application_id = sh.application_id)
                        WHERE sh.application_status_id = ?
                        AND sh.date_created < ?
                        AND app.application_status_id in ({$placeholders})
                        GROUP BY application_id
                ";

                array_unshift($values, $collections_contact_id, $date);

                return DB_Util_1::querySingleColumn($this->db, $query, $values);
        }


	/**
	 * @return array of hold status ids
	 */
	public function getHoldingStatusIds()
	{
		$disallowed_statuses = array();

		$asf = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');

		$disallowed_statuses[] = $asf->toId('hold::servicing::customer::*root');
		$disallowed_statuses[] = $asf->toId('hold::arrangements::collections::customer::*root');
		$disallowed_statuses[] = $asf->toId('unverified::bankruptcy::collections::customer::*root');
		$disallowed_statuses[] = $asf->toId('verified::bankruptcy::collections::customer::*root');
		$disallowed_statuses[] = $asf->toId('amortization::bankruptcy::collections::customer::*root');
		$disallowed_statuses[] = $asf->toId('skip_trace::collections::customer::*root');

		return $disallowed_statuses;
	}

	/**
	 * Searches the Application Service Database for applications based on
	 * an array of application status strings and option based on
	 * the minimum date_created and/or the minimum date_application_status_set.
	 *
	 * @param array $status_list
	 * @param <string> $min_date_created
	 * @param <string> $min_date_application_status_set
	 * @return <array> An array of Objects
	 */
	public function getAppInfoByStatus(array $status_list, $min_date_created = NULL, $min_date_application_status_set = NULL)
	{
		if(empty($status_list))
			return array();

		$db = ECash::getAppSvcDB();

		$insert_list = $this->getStringList($insert_list, true);

		// Values must be quoted
		$min_date_created = (empty($min_date_created)) ? 'NULL' : "'$min_date_created'";
		$min_date_application_status_set = (empty($min_date_application_status_set)) ? 'NULL' : "'$min_date_application_status_set'";

		$query = 'CALL sp_fetch_app_info_by_status ("'.$insert_list.'","'.$min_date_created.'","'.$min_date_application_status_set.'");';

		$result = $db->query($query);
		return $result->fetchAll(DB_IStatement_1::FETCH_ASSOC);
	}
        /**
         * Searches the Application Service Database for applications based on
         * an  application status string.
         *
         * @param  $status
         * @return <array> An array of Objects
         */
	public function getAppIdsByStatus($status)
	{
		if(empty($status))
			return array();

		$db = ECash::getAppSvcDB();

		$query = 'CALL sp_fetch_application_ids_by_application_status ("'.$status.'");';
		ECash::getLog()->Write($query);
		$result = $db->query($query);
		return $result->fetchAll(DB_IStatement_1::FETCH_ASSOC);
	}


	/**
	 * Performs an Application Search against the App Service using the bank_aba
	 * and bank_account as the search criteria.  Since this is used in two places
	 * (getting a count for the App Display and a more detailed pop-up) there is
	 * the $return_count option to toggle the return type.
	 *
	 * @param <string/int> $bank_aba
	 * @param <string/int> $bank_account
	 * @param <bool> $return_count - Return a count instead of the array results
	 * @return <mixed> array|int - Array is the default, int if the return_count parameter = TRUE
	*/
	public function getDuplicateBankInfo($bank_aba, $bank_account, $return_count = FALSE)
	{
		$total   = 0;

		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$bank_search_criteria = array(
			array(  'field'          => 'bank_aba',
					'strategy'       => 'is',
					'searchCriteria' => $bank_aba),
			array(  'field'          => 'bank_account',
					'strategy'       => 'is',
					'searchCriteria' => $bank_account),
			);

		$records = $app_client->applicationSearch($bank_search_criteria, 100);

		/**
		 * If for some reason we don't get any bank info...
		 */
		if(empty($records))
		{
			if($return_count == TRUE) return 0;

			return array();
		}

		if($return_count == TRUE)
		{
			return count($records);
		}
		else
		{
			return $records;
		}
	}

	/**
	 * Performs an Application Search against the App Service using the ip_address
	 * as the search criteria.  Since this is used in two places(getting a count 
	 * for the App Display and a more detailed pop-up) there is the $return_count 
	 * option to toggle the return type.
	 *
	 * @param <string/> $ip_address
	 * @param <bool> $return_count - Return a count instead of the array results
	 * @return <mixed> array|int - Array is the default, int if the return_count parameter = TRUE
	*/
	public function getDuplicateIpAddress($ip_address, $return_count = FALSE)
	{
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$ip_search_criteria = array(
			array(	'field'          => 'ip_address',
					'strategy'       => 'is',
					'searchCriteria' => $ip_address));

		$records = $app_client->applicationSearch($ip_search_criteria, self::DUPE_IP_ADDRESS_LIMIT);

		if(empty($records))
		{
			if($return_count == TRUE) return 0;

			return array();
		}
		else
		{
			if($return_count == TRUE)
			{
				return count($records);
			}
			else
			{
				return $records;
			}
		}
	}

	/**
	 * Returns the basic contact info fields
	 *
	 * @TODO: This needs to look for a 'primary' flag as soon as it's implemented!
	 *
	 * @param <string> $application_id
	 * @result <Stdclass> - Object containing various pieces of contact info
	 */
	public function getContactInfo($application_id)
	{
		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');
		$contact_info = $app_client->getContactInfo($application_id);


                if(empty($contact_info))
                {
                        $contact_info = array();
                }

		$result = new stdClass();
		$result->phone_home = NULL;
		$result->phone_cell = NULL;
		$result->phone_fax  = NULL;
		$result->email      = NULL;

		foreach($contact_info as $info)
		{
			if(empty($info->type)) continue; //[#40090]
			switch($info->type)
			{
				case 'phone_home':
					$result->phone_home = $info->value;
					break;
				case 'phone_cell':
					$result->phone_cell = $info->value;
					break;
				case 'phone_fax':
					$result->phone_fax  = $info->value;
					break;
				case 'email':
					$result->email      = $info->value;
					break;
			}
		}

		return $result;
	}

	/**
	 * Helper function used to find the first occurance of a status in the
	 * status history.  Returns NULL if none is found.
	 *
	 * @param <array> $status_history
	 * @param <string> $status_string
	 * @return <string|NULL> - date string on success, NULL on failure
	 */
	public function getFirstStatusDate(array $status_history, $status_string = NULL)
	{
		if(empty($status_history) || empty($status_string))
			return NULL;

		foreach($status_history as $record)
		{
			if($record->applicationStatus === $status_string)
				return $record->dateCreated;
		}
		return NULL;
	}

	/**
	 * Takes a list of application_id's from the application service and calls
	 * a stored procedure in the MSSQL Database.
	 *
	 * How this works: With a stored procedure, there is no good way to pass in
	 * a variable list of arguments such as a list of application_ids that will go
	 * inside an IN() clause, so we have to do some hackery.
	 *
	 * First we're creating a temporary table using a user-defined table type
	 * that the DBA's have created.  Next we insert all of our applications into that
	 * table, which will then be called within a subquery inside the stored procedure.
	 * Finally, we call the stored procedure and pass it the table name as an argument
	 * and it will do all the dirty work inside the stored procedure.
	 *
	 * @param <array> $source_list - Array of application_ids
	 * @return <array> $applications - Associative array of data, indexed by application_id
	 */
	public function getApplicationData($source_list)
	{
		if(empty($source_list)) return array();
		$application_info = array();
		$mssql_db = ECash::getAppSvcDB();

		$batched_list = array_chunk($source_list, self::BATCH_SIZE_LIMIT);
		foreach($batched_list as $application_list)
		{
			$insert_statement = implode(",", array_unique($application_list));

			$query = 'CALL sp_ach_fetch_common_data_by_application_id ("'.$insert_statement.'");';

			$result = $mssql_db->query($query);
			while ($app = $result->fetch(DB_IStatement_1::FETCH_ASSOC))
			{
				$application_info[$app['application_id']] = $app;
			}
		}

		unset($source_list);
		unset($batched_list);
		unset($insert_statement);
		unset($reduced_list);

		return $application_info;
	}
	
	// TODO REMOVE ME!!!
	
	/**
	 * Returns CRA payment data.
	 * 
	 * @param array $applications a list of application ID's
	 * @return array an array of application data, indexed by application ID
	 */
	public function getCraApplicationDataArray(array $applications, $finance_charge)
	{
		if (empty($applications)) return array();
		$result = $this->getCraApplicationData($applications, $finance_charge);
		
		$application_data = array();
		while ($row = $result->fetch(DB_IStatement_1::FETCH_ASSOC))
		{
			$application_data[$row['application_id']] = $row;
		}
		
		return $application_data;
	}
	
	/**
	 * Returns the CRA data needed from the application service for the specified application ID's.
	 * 
	 * @param array $applications
	 * @param float $finance_charge
	 * @return DB_IStatement_1
	 */
	public function getCraApplicationData(array $applications, $finance_charge)
        {
                if (1 < $finance_charge || 0 > $finance_charge) throw new InvalidArgumentException('finance charge must be between 0 and 1');

                $app_list = $this->getStringList($applications , false);

                $query = 'CALL sp_cra_fetch_data_by_application_id ("'.$app_list.'",'.$finance_charge.' );';

                return $this->getApplicationServiceDb()->query($query);
        }
	
	/**
	 * Returns the result containing application data from CRA status changes from inactive.
	 * 
	 * @param string $date
	 * @param string $prev_date
	 * @param float $finance_charge
	 * @return DB_IStatement_1
	 */
	public function getCraStatusChangesFromInactive($date, $finance_charge)
	{
		$date = date('Y-m-d', strtotime($date));
		$prev_date = date('Y-m-d', strtotime($prev_date, strtotime('-1 day')));
		
		$query = "CALL sp_cra_fetch_status_changes_from_inactive ('".$date."','".$prev_date."',".$finance_charge.");";
		
		return $this->getApplicationServiceDb()->query($query);
	}
    
	/**
	 * Returns the result containing application data from CRA status changes from inactive.
	 * 
	 * @param string $date
	 * @param string $prev_date
	 * @param float $finance_charge
	 * @return DB_IStatement_1
	 */
	public function getCraFactorTrustNewLoans($date, $finance_charge)
	{
		$date = date('Y-m-d', strtotime($date));
		$prev_date = date('Y-m-d', strtotime($prev_date, strtotime('-1 day')));
		
		$query = "CALL sp_cra_factortrust_new_loans ('".$date."','".$prev_date."',".$finance_charge.");";
		
		return $this->getApplicationServiceDb()->query($query);
	}
	
	/**
	 * Returns the result containing application data for CRA on active status changes.
	 * 
	 * @param string $date
	 * @param string $active_status
	 * @param array $cancellation_statuses
	 * @param float $finance_charge
	 * @return DB_IStatement_1
	 */
	public function getCraActiveStatusChanges($date, $active_status, array $cancellation_statuses, $finance_charge)
	{
		$date = date('Y-m-d', strtotime($date));
		
		$cancel_list = $this->getStringList($cancellation_statuses , true);
		
		$query = 'CALL sp_cra_fetch_active_status_changes ("'.$date.'","'.$active_status.'","'.$cancel_list.'",'.$finance_charge.');';
		
		return $this->getApplicationServiceDb()->query($query);
	}
	
	/**
	 * Returns an array of application data based on status history from the application service database.
	 * 
	 * @param string $date a string with the date in a format suitable for strtotime
	 * @param array $statuses an array of status chain strings
	 * @param float $finance_charge a float with the percentage of the finance charge
	 * @return array
	 */
	public function getCraStatusHistory($date, array $statuses, $finance_charge)
	{
		if (empty($statuses))
		{
			trigger_error('statuses was empty', E_USER_WARNING);
			return array();
		}
		$date = date('Y-m-d', strtotime($date));
		
		$insert_list = '';
		$insert_list = $this->getStringList($statuses , true);
		
		$query = 'CALL sp_cra_fetch_status_history ("'.$date.'", "'.$insert_list.'", '.$finance_charge.');';
		
		$results = $this->getApplicationServiceDb()->query($query);
		
		$application_data = array();
		while ($row = $results->fetch(DB_IStatement_1::FETCH_ASSOC))
		{
			$application_data[$row['application_id']] = $row;
		}
		
		return $application_data;
	}
	
	/**
	 * Returns CRA data for cancellation statuses.
	 * 
	 * @param string $date
	 * @param array $statuses
	 * @param float $finance_charge
	 * @return DB_IStatement_1
	 */
	public function getCraCancellationStatusData($date, array $statuses_initial, array $statuses_final, $finance_charge)
	{
		if (empty($statuses_initial)) trigger_error('initial statuses was empty', E_USER_WARNING);
		if (empty($statuses_final)) trigger_error('final statuses was empty', E_USER_WARNING);
		$date = date('Y-m-d', strtotime($date));
		$var_table_name = '@applicationStatuses';
		
		$insert1 = $this->getStringList($statuses_initial , true);
		$insert2 = $this->getStringList($statuses_final , true);
		
		$query = 'CALL sp_cra_fetch_canceled_prefunded ("'.$date.'","'.$insert1.'","'.$insert2.'",'.$finance_charge.');';
			
		return $this->getApplicationServiceDb()->query($query);
	}
	
	/**
	 * Returns a list of insert statements batched in groups the specified batch size.
	 * 
	 * @param string $table_name the name of the table
	 * @param string $column_type the column type to insert
	 * @param array $list an array of values to insert
	 * @param int $batch_size the size of the batches to create
	 * @return string
	 */
	private function getInsertStatements($table_name, $column_type, array $list, $quoted = false, $batch_size = self::MAX_INSERTS)
	{
		$list = array_chunk($list, $batch_size);
		$query = '';
		
		foreach ($list as $chunk)
		{
			$insert_list = '';
			
			foreach ($chunk as $value)
			{
				$insert_list .= $quoted ? "('$value')," : "($value),";
			}
			
			$insert_list = rtrim($insert_list, ',');
			$query .= sprintf("INSERT INTO %s (%s) VALUES %s;\n", $table_name, $column_type, $insert_list);
		}
		
		return $query;
	}	
	/**
	 * Returns an of strings out of an array.
	 * 
	 * @return string
	 */
	private function getStringList(array $list, $quoted = false)
	{

		$string_list = $quoted ? "'".implode("','",$list)."'" : implode(",",$list);
		
		return $string_list;
	}
}

?>
