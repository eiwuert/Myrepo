<?php
/**
		@publicsection
		@public
		@brief
			A library to handle clverify functionality
		@version
			1
			
		@todo
	*/ 

// A list of common tables
require_once ("db2_table_names.php");
require_once ("id_perf.1.php");
require_once ("datax.1.php");

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
class Id_Perf_1_Db2 extends Id_Perf_1
{
	var $db2;
	var $type_map;

	function Id_Perf_1_Db2 (&$db2_object, $live_mode = FALSE)
	{
		parent::Id_Perf_1($live_mode);
		$this->db2 = &$db2_object;
		$this->type_map = array(  0 => 'IDV', //OLD DEFAULT
		1 => 'IDV_ADVANCED',
		2 => 'IDV_BASIC_SSN_FULL',
		3 => 'IDV_BASIC_SSN_SHORT',
		4 => 'IDV_ADVANCED_V2',
		10 => 'IDV_ADVANCED_V2',
		11 => 'ID', //NEW DATAX ID CALL
		12 => 'PERFORMANCE',
		13 => 'IDV_COMBINED',
		14 => 'fundupd-l1'
		);
		$this->source_map = array(	0 => 'CLVERIFY',
		1 => 'CLVERIFY',
		2 => 'CLVERIFY',
		3 => 'CLVERIFY',
		4 => 'CLVERIFY',
		10 => 'DATAX',
		11 => 'DATAX_IDV',
		12 => 'DATAX_PERFORMANCE',
		13 => 'DATAX_IDV',
		14 => 'DATAX_FUNDUP'
		);
		return TRUE;
	}

	// tempory for testing mysql
	function _Update_Sent_Package($sent_package, $source_id, $authentication_id)
	{
		return TRUE;
	}

	function Get_Record($customer_id, $source_id = 1)
	{
		//echo "Get Auth Rec, Source: ".$source_id." Cust: ".$customer_id."<pre>";
		$fetch_exist = "
				SELECT
					date_modified,
					date_created,
					sent_package,
					received_package,
					track_hash,
					authentication_id,
					score
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
							name = '{$this->source_map[$source_id]}'
					)
					AND authentication_type_id IN
					(
						SELECT
							authentication_type_id
						FROM
							authentication_type
						WHERE
							name = '{$this->type_map[$source_id]}'
					)
				ORDER BY
					date_modified DESC
				FETCH FIRST 1 ROWS ONLY
			";
		//echo "Get Record SQL: ";var_dump($fetch_exist);
		$result = $this->db2->Execute ($fetch_exist);
		if(Error_2::Error_Test ($result))
		{
			return $result;
		}
		//echo "Result: <pre>";var_dump($result->Fetch_Array());
		return $result->Fetch_Object ();
	}

	function _Update($xml, $display_data, $authentication, $source_id, $authentication_id, $customer_id)
	{
		// Update the database
		//echo "\$display_data: <pre>";var_dump($display_data); echo "</pre>"; //**DEBUG
		$set_track_hash = ( isset($display_data->TrackHash) && strlen(trim($display_data->TrackHash)) > 0 )
							? "track_hash = '" . trim($display_data->TrackHash) . "',"
							: "";
		$score = empty($display_data->score) ? NULL : $display_data->score;
		$query = "
					update 
						authentication 
					set
						date_modified = current timestamp,
						sent_package	 = '" . str_replace("'", "''", trim($xml["sent"]	)) . "', 
						received_package = '" . str_replace("'", "''", trim($xml["received"])) . "',
						{$set_track_hash}
						score = '" . $score . "',
						authentication_type_id =
							(
								SELECT
									authentication_type_id
								FROM
									authentication_type
								WHERE
									name = '{$this->type_map[$source_id]}'
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
								name = '{$this->source_map[$source_id]}'
						)";

		//echo "updating authentication record<br>";var_dump($query);

		$result = $this->db2->Execute ($query);
		if(Error_2::Error_Test ($result, FALSE))
		{
			return $result;
		}
	}

	function _Update_Score($display_data, $authentication, $source_id)
	{
		$score = empty($display_data->score) ? NULL : $display_data->score;

		// Update the record (not sure why this is done, but it is taken from the original)
		$query = "
					UPDATE 
						authentication 
					SET
						score = '" . $score . "',
						date_modified = current timestamp
					WHERE
						authentication_id = " . $authentication->authentication_id . "
					and authentication_source_id =
						(
							SELECT
								authentication_source_id
							FROM
								authentication_source
							WHERE
							name = 'CLVERIFY'
						)
					AND authentication_type_id IN
						(
							SELECT
								authentication_type_id
							FROM
								authentication_type
							WHERE
								name = '{$this->type_map[$source_id]}'
						)";
		$result = $this->db2->Execute ($query);
		if(Error_2::Error_Test ($result, FALSE))
		{
			return $result;
		}
		//echo "UPdated score to: {$display_data->score}\n";
	}

	function _Insert($customer_id, $source_id)
	{
		//echo "ID_PERF Insert \n";
		//var_dump($customer_id);
		//var_dump($source_id);

		// Update the database
		$query = "
					INSERT INTO 
						authentication 
					(
						date_modified,
						date_created, 
						customer_id,
						authentication_source_id,
						authentication_type_id
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
								name = '{$this->source_map[$source_id]}'
						),
						(
							SELECT
								authentication_type_id
							FROM
								authentication_type
							WHERE
								name = '{$this->type_map[$source_id]}'
						)
					)
				";

		//echo "inserting new IDV record<br>";var_dump($query);

		$result = $this->db2->Execute ($query);
		if(Error_2::Error_Test ($result, FALSE))
		{
			return $result;
		}

		return $this->db2->Insert_Id();
	}

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
					AND authentication_source_id IN
					(
						SELECT
							authentication_source_id
						FROM
							authentication_source
						WHERE
							name = '{$this->source_map[$source_id]}'
					)
					AND authentication_type_id IN
					(
						SELECT
							authentication_type_id
						FROM
							authentication_type
						WHERE
							name = '{$this->type_map[$source_id]}'
					)
				ORDER BY
					date_created DESC
				FETCH FIRST 1 ROWS ONLY
			";
		//echo "ID_Perf_1_db2 Get Package <br>";
		//var_dump($query);
		$result = $this->db2->Execute ($query);
		if(Error_2::Error_Test ($result, FALSE))
		{
			return $result;
		}
		$result_object = $result->Fetch_Object ();

		//echo "Result <br> <pre>";var_dump($result_object);

		if (empty ($result_object->AUTHENTICATION_ID))
		{
			$existing_package->num_rows = 0;
			//echo "ID_Perf_1_db2 - No Authentication ID <br>";
		}
		else
		{
			$existing_package->num_rows = 1;
			$existing_package->authentication_id = $result_object->AUTHENTICATION_ID;
			$existing_package->received_package = $result_object->RECEIVED_PACKAGE;
			//echo "ID_Perf_1_db2 - Yes Authentication ID <br><pre>";
			//var_dump($existing_package->received_package);
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
					customer_id = ".$customer_id."
					AND authentication_source_id IN
					(
						SELECT
							authentication_source_id
						FROM
							".REFERENCE_AUTHENTICATION_SOURCE."
						WHERE
							name = '{$this->source_map[$source_id]}'
					)
					AND authentication_type_id IN
					(
						SELECT
							authentication_type_id
						FROM
							authentication_type
						WHERE
							name = '{$this->type_map[$source_id]}'
					)
				ORDER BY
					date_modified DESC
				FETCH FIRST 1 ROWS ONLY
			";

		//echo "Fetch Exist <pre>"; var_dump($fetch_exist);
		$result = $this->db2->Execute ($fetch_exist);

		if(Error_2::Error_Test ($result))
		{
			return $result;
		}

		//echo "Fetch result <pre>";
		//var_dump($result->Fetch_Object());
		return $result->Fetch_Object ();

	}

}

?>