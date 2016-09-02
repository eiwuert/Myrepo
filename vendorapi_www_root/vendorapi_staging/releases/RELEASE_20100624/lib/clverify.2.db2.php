<?php
	/**
		@publicsection
		@public
		@brief
			A library to handle clverify functionality

		@version
			2 2004-22-06 - Shelly Warren - copy of version clverify.1.db2.php to accomidate the 
						   need to clean up data - clverify chokes on apostrophy's - implimented 
						   a simple str_replace to accomplish this - search for v2 addition to locate offshoot code
						   would suggest finding out all expected formatting for clverify to work and 
						   create a library call to cleanup and format all values being passed, for now
						   this is the only issue regarding formatting of clverify information 

		@todo
	*/ 

	// A list of common tables
	require_once ("/virtualhosts/lib/db2_table_names.php");
	require_once ("clverify.1.php");
	
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
	class CLVerify_2_Db2 extends CLVerify_1
	{

		// This array is used to map source_ids defined in
		// clverify.2.php to IDs in the authentication_type
		// table (db2)
		var $auth_types = array(
			'1' => 'IDV_ADVANCED',
			'2' => 'IDV_BASIC_SSN_FULL',
			'3' => 'IDV_BASIC_SSN_SHORT',
			'4' => 'IDV_ADVANCED_V2',
		);

		function CLVerify_2_Db2 (&$db2_object, $live_mode = FALSE)
		{
			parent::CLVerify_1($live_mode);
			$this->db2 = &$db2_object;

			return TRUE;
		}

		//source_id not used (only by mysql)		
		function _Update($xml, $display_data, $authentication, $source_id, $authentication_id, $customer_id)
		{
				// Update the database
				$query = "
					update 
						authentication 
					set
						date_modified = current timestamp,
						sent_package = '" . trim ($xml["sent"]) . "', 
						received_package = '" . trim ($xml["received"]) . "',
						score = '" . $display_data->score . "',
						authentication_type_id = (SELECT
													authentication_type_id
												FROM
													authentication_type
												WHERE
													name = '".$this->auth_types[$source_id]."'
												)
					where
						authentication_id = {$authentication_id}
					and authentication_source_id =
						(
							SELECT
								authentication_source_id
							FROM
								authentication_source
							WHERE
								name = 'CLVERIFY'
						)";
				
				//echo "inserting new clverify record<br>";
					
				$result = $this->db2->Execute ($query);
				if(Error_2::Error_Test ($result, FALSE))
				{
					return $result;
				}			
		}

		//source_id is only used for mysql
		function _Update_Score($display_data, $authentication, $source_id)
		{
			
				// Update the record (not sure why this is done, but it is taken from the original)
				$query = "
					UPDATE 
						authentication 
					SET
						score = '" . $display_data->score . "',
						date_modified = current timestamp
					WHERE
						authentication_id = " . $authentication->authentication_id . "
				";
				$result = $this->db2->Execute ($query);
				if(Error_2::Error_Test ($result, FALSE))
				{
					return $result;
				}
				//echo "UPdated score to: {$display_data->score}\n";
		}

		//source_id not used (only by mysql)		
		function _Insert($customer_id, $source_id)
		{
			// Update the database
			$query = "
					INSERT INTO 
						authentication 
					(
						date_modified,
						date_created, 
						customer_id,
						authentication_source_id
					) 
					VALUES 
					(
						CURRENT TIMESTAMP,
						CURRENT TIMESTAMP,
						" . $customer_id . ",
						(
							SELECT
								authentication_source_id
							FROM
								authentication_source
							WHERE
								name = 'CLVERIFY'
						)
					)
				";
				
			//echo "inserting new clverify record<br>";
					
			$result = $this->db2->Execute ($query);
			if(Error_2::Error_Test ($result, FALSE))
			{
				return $result;
			}

			return $this->db2->Insert_Id();
		}

		//source_id not used (only by mysql)
		function _Get_Existing_Package ($customer_id, $source_id)
		{
			$query = "
				SELECT 
					authentication_id,
					received_package
				FROM
					authentication
				WHERE
					customer_id = " . $customer_id . "
					AND authentication_type_id IN
					(
						SELECT
							authentication_type_id
						FROM
							authentication_type
						WHERE
							name = '".$this->auth_types[$source_id]."'
					)
					AND authentication_source_id IN
					(
						SELECT
							authentication_source_id
						FROM
							authentication_source
						WHERE
							name = 'CLVERIFY'
					)
				ORDER BY
					date_created DESC
				FETCH FIRST 1 ROWS ONLY
			";
			$result = $this->db2->Execute ($query);
			if(Error_2::Error_Test ($result, FALSE))
			{
				return $result;
			}
			$result_object = $result->Fetch_Object ();
			
			if (empty ($result_object->AUTHENTICATION_ID))
			{
				$existing_package->num_rows = 0;	
			}
			else
			{
				$existing_package->num_rows = 1;
				$existing_package->authentication_id = $result_object->AUTHENTICATION_ID;
				$existing_package->received_package = $result_object->RECEIVED_PACKAGE;
			}
	
			//echo "fetched object<br>";
			
			return $existing_package;
		}

	//source_id not used (only by mysql)
	function _Fetch_Exist($customer_id, $source_id)
	{
			$fetch_exist = "
				SELECT
					sent_package,
					received_package
				FROM
					".CUSTOMER_AUTHENTICATION."
				WHERE
					customer_id = ".$customer_id."
					AND authentication_source_id IN
					(
						SELECT
							authentication_source_id
						FROM
							".REFERENCE_AUTHENTICATION_SOURCE."
						WHERE
							name = 'CLVERIFY'
					)
				ORDER BY
					date_modified DESC
				FETCH FIRST 1 ROWS ONLY
			";
			
			$result = $this->db2->Execute ($fetch_exist);
			return $result->Fetch_Object ();
			
		}
		
	}
?>
