<?php
	// A list of common tables
	require_once ("/virtualhosts/lib/mysql_table_names.php");
	require_once ("mysql.3.php");
	require_once ("clverify.2.php");
	/*
		customer_info object
		REQUIRED PROPERTIES
			->name_last
			->name_first
			->street
			->city
			->state
			->zip
			->social_security_number
		OPTIONAL PROPERTIES
			->name_middle
			->phone_home
			->phone_work
			->bank_aba
			->bank_account
			->date_birth
			->legal_id_number
			->legal_id_state

	*/
	class CLVerify_2_Mysql extends CLVerify_2
	{
		function CLVerify_2_Mysql (&$sql, $base, $live_mode = FALSE)
		{
			parent::CLVerify_2($live_mode);
			$this->sql = &$sql;
			$this->base = $base;
			return TRUE;
		}

		function Get_Record ($application_id, $source_id = 1)
		{
			$fetch_exist = "
				SELECT
					date_modified, date_created, sent_package, received_package, score, authentication_source_id 
				FROM
					".CUSTOMER_AUTHENTICATION."
				WHERE
					application_id = '".$application_id."'
					AND authentication_source_id = {$source_id}
					AND sent_package <> '1'
					AND received_package <> '1'
				ORDER BY
					date_modified DESC
				LIMIT 1";
			$result = $this->sql->Query ($this->base, $fetch_exist);
			Error_2::Error_Test ($result, TRUE);

			return $this->sql->Fetch_Array_Row ($result);
		}

		//for db2 compatibility -- you could add a mysql query here to
		//retrieve data if it's missing
		function _Get_Customer_Info($application_id)
		{
			return new stdClass();
		}

		//customer_id not needed here, for db2 compatibility
		function _Update($xml, $display_data, $authentication, $source_id, $authentication_id, $customer_id)
		{
				// Update the database
				$query = "
					UPDATE
						authentication
					SET
						received_package = '".mysql_escape_string($xml["received"])."',
						score = '".mysql_escape_string($display_data->score)."',
						bb_result = ".($authentication->flags->blackbox_pass ? 1 : 0).",
						display_data = '".mysql_escape_string(serialize($display_data))."'
					WHERE
						authentication_id = {$authentication_id}
						AND authentication_source_id = {$source_id}
					";

				$result = $this->sql->Query ($this->base, $query);
				Error_2::Error_Test ($result, TRUE);
			
		}
		
		function _Update_Sent_Package($sent_package, $source_id, $authentication_id)
		{
			$query = "
					UPDATE
						authentication
					SET
						sent_package = '".mysql_escape_string($sent_package)."'
					WHERE
						authentication_id = {$authentication_id}
						AND authentication_source_id = {$source_id}
				";
			$result = $this->sql->Query ($this->base, $query);
			Error_2::Error_Test ($result, TRUE);	
		}	

		function _Update_Score($display_data, $temp, $source_id)
		{
				// Update the record
				$query = "
						UPDATE
							authentication
						SET
							score = '".$display_data->score."'
						WHERE
							authentication_id = ".$temp->authentication_id."
							AND authentication_source_id = {$source_id}
						";
				$result = $this->sql->Query ($this->base, $query);
				Error_2::Error_Test ($result, TRUE);			
		}

		function _Insert($application_id, $source_id)
		{
			// Insert a record so we know the clv check is happening
			$query = "
					INSERT INTO ".CUSTOMER_AUTHENTICATION."
					(
						date_created,
						application_id,
						authentication_source_id
					)
					VALUES
					(
						NOW(),
						{$application_id},
						{$source_id}
					)";

			$result = $this->sql->Query ($this->base, $query);
			//print_r($result);
			Error_2::Error_Test ($result, TRUE);
			return $this->sql->Insert_Id ($result);			
		}

		function _Get_Existing_Package ($application_id, $source_id)
		{
			// Check if CLV is available already
			$fetch_exist = "
				SELECT
					*
				FROM
					".CUSTOMER_AUTHENTICATION."
				WHERE
					application_id = '".$application_id."'
					AND authentication_source_id = {$source_id}
				ORDER BY
					date_modified DESC
				LIMIT 1";
			$result = $this->sql->Query ($this->base, $fetch_exist);
			Error_2::Error_Test ($result, TRUE);

			$result_object = $this->sql->Fetch_Object_Row($result);
			//print_r($result_object);
			
			$existing_package = new stdClass();
			
			if (empty ($result_object->authentication_id))
			{
				$existing_package->num_rows = 0;
			}
			else
			{
				$existing_package->num_rows = 1;
				$existing_package->authentication_id = $result_object->authentication_id;
				$existing_package->received_package = $result_object->received_package;
			}
	
			//echo "fetched object<br>";
			
			return $existing_package;
			
		}

	function _Fetch_Exist($customer_id, $source_id)
	{
		$fetch_exist = "
				SELECT
					sent_package,
					received_package
				FROM
					".CUSTOMER_AUTHENTICATION."
				WHERE
					customer_id = '".$customer_id."'
					AND authentication_source_id = {$source_id}
				ORDER BY
					date_modified DESC
				LIMIT 1";
		$result = $this->sql->Query ($this->base, $fetch_exist);
		Error_2::Error_Test ($result, TRUE);
		return $this->sql->Fetch_Object_Row($result);
		
	}
		
		
	}
?>
