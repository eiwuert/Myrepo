<?php

require_once("error.2.php");
 
class RAF_DB2
{
	var $sql;
	var $db2;
	var $errors;
	var $schema;
	var $ach_login;
	var $ach_pass;
	var $company_list;
	
	function RAF_DB2($sql, $db2, $schema, $mode)
	{
		$this->sql = $sql;
		$this->db2 = $db2;
		$this->schema = $schema;
		$this->errors = array();
		
		switch ($mode)
		{
			case 'rc':
			case 'local':
			 // Test account
				$this->ach_login = 'IC TEST2';
				$this->ach_pass  = '60Q2302T';
				break;
				
			case 'live':
			default:
			// live login
				$this->ach_login = 'consumer tuckers';
				$this->ach_pass  = 'gqcwvu1w';
				break;
		}

		// company array
		// used to loop queries from multiple db schemas / views
		$this->company_list = array('D1','UFC');  
		
	}
	

	function Insert_Customer( $session, $schema )
	{
		
		// Customer 
		$cust_hash = array();
		$cust_hash['DATE_MODIFIED'] = "CURRENT TIMESTAMP";
		$cust_hash['DATE_CREATED'] = "CURRENT TIMESTAMP";
		$cust_hash['NAME_FIRST'] = "'{$session['data']['name_first']}'";
		$cust_hash['NAME_LAST'] = "'{$session['data']['name_last']}'";
		$cust_hash['NAME_MIDDLE'] = "'{$session['data']['name_middle']}'";
		$cust_hash['DATE_BIRTH'] = "'01/01/0001'";
		$cust_hash['SOCIAL_SECURITY_NUMBER'] = "'{$session['data']['social_security_number']}'";
	
		
		// Email
		$table_hash['EMAIL'] = array();
		$table_hash['EMAIL']['insert'] = array();
		$table_hash['EMAIL']['insert']['date_modified'] = "CURRENT TIMESTAMP";
		$table_hash['EMAIL']['insert']['date_created'] = "CURRENT TIMESTAMP";
		$table_hash['EMAIL']['insert']['verification_status_id'] = "(SELECT verification_status_id FROM {$this->schema}.VERIFICATION_STATUS WHERE name = 'NOT VERIFIED')";
		$table_hash['EMAIL']['compare'] = array();
		$table_hash['EMAIL']['compare']['email_address'] =  "'{$session['data']['email_primary']}'";
		
		// Home Address
		$table_hash['ADDRESS'] = array();
		$table_hash['ADDRESS']['insert'] = array();
		$table_hash['ADDRESS']['insert']['date_occupied'] = "'2002-01-01'";
		$table_hash['ADDRESS']['insert']['date_modified'] = "CURRENT TIMESTAMP";
		$table_hash['ADDRESS']['insert']['date_created'] = "CURRENT TIMESTAMP";
		$table_hash['ADDRESS']['insert']['verification_status_id'] = "(SELECT verification_status_id FROM {$this->schema}.VERIFICATION_STATUS WHERE name = 'NOT VERIFIED')";
		$table_hash['ADDRESS']['insert']['address_type_id'] = "(SELECT address_type_id FROM {$this->schema}.ADDRESS_TYPE WHERE name = 'HOME')";
		$table_hash['ADDRESS']['insert']['address_ownership_id'] = "(SELECT address_ownership_id FROM {$this->schema}.ADDRESS_OWNERSHIP WHERE name = 'OWN')";
		$table_hash['ADDRESS']['insert']['state_id'] =  "(SELECT state_id FROM {$this->schema}.STATE WHERE name = '{$session['data']['home_state']}')";
		$table_hash['ADDRESS']['compare'] = array();
		$table_hash['ADDRESS']['compare']['street'] = "'{$session['data']['home_street']}'";
		$table_hash['ADDRESS']['compare']['unit'] =  "'{$session['data']['home_unit']}'";
		$table_hash['ADDRESS']['compare']['city'] =  "'{$session['data']['home_city']}'";
		$table_hash['ADDRESS']['compare']['zip'] =  "'{$session['data']['home_zip']}'";		
		$license_key = $session['config']->license;
		
		// Home Phone
		$table_hash['PHONE'] = array();
		$table_hash['PHONE']['insert']['date_modified'] = "CURRENT TIMESTAMP";
		$table_hash['PHONE']['insert']['date_created'] = "CURRENT TIMESTAMP";
		$table_hash['PHONE']['insert']['phone_type_id'] = "(SELECT phone_type_id FROM {$this->schema}.PHONE_TYPE WHERE name = 'HOME')";
		$table_hash['PHONE']['insert']['verification_status_id'] = "(SELECT verification_status_id FROM {$this->schema}.VERIFICATION_STATUS WHERE name = 'NOT VERIFIED')";
		$table_hash['PHONE']['compare'] = array();
		$table_hash['PHONE']['compare']['phone_number'] =  "'{$session['data']['phone_home']}'";
		
		
		// Bank_Info
		$table_hash['BANK_INFO'] = array();
		$table_hash['BANK_INFO']['insert'] = array();
		$table_hash['BANK_INFO']['insert']['date_modified'] = "CURRENT TIMESTAMP";
		$table_hash['BANK_INFO']['insert']['date_created'] = "CURRENT TIMESTAMP";
		$table_hash['BANK_INFO']['insert']['bank_name'] = "'{$session['data']['bank_name']}'";
		$table_hash['BANK_INFO']['insert']['bank_aba'] = "'{$session['data']['bank_aba']}'";
		$table_hash['BANK_INFO']['insert']['bank_account'] = "'{$session['data']['bank_account']}'";
		$table_hash['BANK_INFO']['insert']['bank_account_type_id'] = "(SELECT BANK_ACCOUNT_TYPE_ID FROM {$this->schema}.BANK_ACCOUNT_TYPE WHERE NAME='{$session['data']['bank_account_type']}')";
		
		// query the customer table for an existing record
		$query = "SELECT COUNT(*) AS COUNT FROM {$this->schema}.CUSTOMER
				WHERE
						social_security_number = '{$session['data']['social_security_number']}'
						AND date_birth = '{$session['data']['dob']}'
						AND name_last = '{$session['data']['name_last']}'
						AND name_first = '{$session['data']['name_first']}' FOR READ ONLY";
		$result = $this->db2->Execute($query);
		$count_obj = method_exists( $result, "Fetch_Object" ) ? $result->Fetch_Object() : 0;
		
		$count = $count_obj->COUNT;
		
		// If there was row count then there is already a RAF account for the person applying
		if($count)
		{
			return FALSE;
		}
		else // The customer is a new customer, perform insert queries.
		{
			// Run our customer insert
			$customer_id = $this->Table_Insert("{$this->schema}.CUSTOMER", $cust_hash);
						
			// Loop over and insert into auxilary tables.
			foreach($table_hash as $table => $field_array)
			{
				// Ignore some phone numbers if they are blank.
				if( in_array(strtolower($table), array("cell_phone", "fax_phone")) )
				{	
					if( !isset($field_array['compare']['phone_number']) || trim($field_array['compare']['phone_number']) == "''")
					{
						$active_ids[$table] = "NULL";
						continue;
					}					
				}
				
				// This needs to be switched to use the link table structure (which should be functionalized), hacked for now.
				if($table == "EMPLOYMENT")
				{
					$field_array['insert']['active_phone_id'] = isset($active_ids['EMPLOYMENT_PHONE']) ? $active_ids['EMPLOYMENT_PHONE'] : 0;
				}
				
				// Merge our compare fields with our normal insert fields
				$new_field_array = array_merge($field_array['compare'], $field_array['insert'], array("customer_id" => $customer_id));
								
				// Check aliased tables array to see if we need to change our table name.
				$table_real = isset($aliased_tables[$table]) ? $aliased_tables[$table]: $table;
				
				// Add schema or view before table.
				$schema_table = $this->schema.".".$table_real;
				
				// Insert and get the pkey back and in our active_ids table.
				$active_ids[$table] = $this->Table_Insert($schema_table, $new_field_array);
			}
			
			// generate raf_number
			$_SESSION['data']['raf_number'] = $this->Generate_RAF_Number($session['data']['email_primary']);		
		
			// run raf_account insert
			$raf_hash = array();
			$raf_hash['DATE_MODIFIED'] = "CURRENT TIMESTAMP";
			$raf_hash['DATE_CREATED'] = "CURRENT TIMESTAMP";
			$raf_hash['customer_id'] = $customer_id;
			$raf_hash['bank_info_id'] = $active_ids['BANK_INFO'];
			$raf_hash['email_id'] = $active_ids['EMAIL'];
			$raf_hash['phone_home'] = $active_ids['PHONE'];
			$raf_hash['address_id'] = $active_ids['ADDRESS'];
			$raf_hash['raf_number'] = "'{$_SESSION['data']['raf_number']}'";
			$this->Table_Insert("{$this->schema}.RAF_ACCOUNT", $raf_hash);
		
			
			// create login for user
			if ($_SESSION['data']['raf_number'])
			{
				$new_username = '';
				$new_encrypted_password = '';
	
				list($_SESSION['data']['username'], $_SESSION['data']['password']) = $this->Create_Login( $_SESSION['data']['raf_number'], session_id() );	
			}	
			
		}
		
		return $_SESSION['data']['raf_number'];
	}
	
	function Create_Login( $username, $session_id ) 
	{
		// strip out all other chars but alpha-numeric chars
		$username = preg_replace("/[^a-zA-Z0-9]/", "", $username);
		
		// check for existing accounts with this username
		$query = "SELECT count(*) AS count FROM {$this->schema}.LOGIN WHERE login LIKE '{$username}%'";
		$res = $this->db2->Execute($query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		$count =  method_exists($res, "Fetch_Array") ? $res->Fetch_Array() : array("COUNT" => 0);
		$username = ($count["COUNT"]) ? $username.(++$count["COUNT"]) : $username;
		$password =  $_SESSION["security"]->_Mangle_Password("cash4friends".substr(microtime(),-3));

		// insert into login table
		$query = "INSERT INTO ".$this->schema.".LOGIN ( date_modified, date_created, session_row_id, company_id, login, crypt_password, crypt_temp ) values
			( current timestamp, current timestamp, 0, (SELECT company_id FROM ".$this->schema.".COMPANY WHERE property_id = {$_SESSION["config"]->property_id}) ,'{$username}','{$password}','
		' )";
		$res = $this->db2->Execute($query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		return array($username,$password);
	}

	function Check_If_Customer_Exists($social_security_number)
	{
		$query = "
			SELECT COUNT(*) AS count 
			FROM raf_account 
			INNER JOIN customer 
				ON raf_account.customer_id = customer.customer_id 
			WHERE customer.social_security_number = '{$social_security_number}'
			";
		$result = $this->db2->Execute($query, Debug_1::Trace_Code(__FILE__, __LINE__));
		Error_2::Error_Test($result, FATAL_DEBUG);
		
		$row = $result->Fetch_array();

		return (boolean)$row['COUNT'];
	}
	
	
	function Generate_RAF_Number($seed)
	{
		// length of refer a friend number
		$length = 8;
		
		// run a loop to generate a 8 char raf number
		while (!$raf_number)
		{
			// randomly generate a raf number
			mt_srand(time()); 
			
			// create random alpha numberic hash with random num and seed
			$raf_tmp = preg_replace("/\W/", '',  crypt($seed.mt_rand(-100000, 100000)) );
			
			// make sure the raf number is only 1-9 a-f
			$raf_tmp = preg_replace("/[^1-9a-f]/i", '',  $raf_tmp);
			
			if (strlen($raf_tmp)>=8)
			{
				// trim hash to specified length
				$raf_number = strtoupper(substr($raf_tmp, 0, $length));	
			}
			
		}
		
		
		
		/* 
		 * Check to see if there is a user using the generated raf number in the database
		 */
		$query = "SELECT raf_number FROM {$this->schema}.RAF_ACCOUNT WHERE RAF_NUMBER = '{$raf_number}'";
		$raf_dup_check = $this->db2->Execute($query);
		Error_2::Error_Test($raf_dup_check, FATAL_DEBUG);
		$dup_chk = $raf_dup_check->Fetch_Object();
		
		// if there is a return then run this function again until there is a unique raf number returned
		if ($dup_chk->RAF_NUMBER)
		{
			$raf_number = $this->Generate_RAF_Number($seed);
		}
		
		return $raf_number;
	} 		

	
	function Table_Insert($table_name, $field_array)
	{
		//echo "<pre>\n\n----------------------------------------\n";
		//echo "QUERY FOR TABLE {$table_name}\n";
		//echo "----------------------------------------\n\n";
		
		// Setup our query pieces
		$insert_query = "INSERT INTO {$table_name} ";
		$fields = "(";
		$values = " values (";
		
		$count = 1;
		
		// Loop over the fields
		foreach($field_array as $field => $new_val)
		{
			// If theres more fields or values add a comma to the end.
			if($count == count($field_array) )
			{
				$fields .= $field . ")";
				$values .= $new_val . ")";										
			}
			else // Theres no more fields or values, so add a )
			{
				$fields .= $field . ",";
				$values .= $new_val . ",";
			}
			$count++;
		}
		
		// Put our query pieces together
		$insert_query = $insert_query . $fields . $values; 
		
		//echo $insert_query;
				
		// Run Query
		$result = $this->db2->Execute($insert_query);
		//echo "\n";
		 $insert_id = $this->db2->Insert_Id($result);
		
		Error_2::Error_Test($result, FATAL_DEBUG);

		return $insert_id; 		
	}
	
	function Customer_Search( $customer_type, $raf_number, $customer_info, $email, $date_constraint = '' )
	{
		switch ($customer_type)
		{
			case 'referer':
			
				// prepare query
				$query = "SELECT customer.*, 
								bank_info.*, 
								email.email_address, 
								raf_account.raf_number as raf_number, 
								(SELECT count(*) FROM raf_trans WHERE raf_trans.raf_number = raf_account.raf_number) as transaction_count, 
								(SELECT count(*) FROM raf_ach JOIN raf_trans ON (raf_ach.raf_trans_id = raf_trans.raf_trans_id) WHERE raf_trans.raf_number = raf_account.raf_number) as ach_transaction_count, 
								raf_account.date_created as member_since
						  FROM {$this->schema}.raf_account 
								JOIN {$this->schema}.customer ON (customer.customer_id = raf_account.customer_id)
								JOIN {$this->schema}.bank_info ON (raf_account.customer_id = bank_info.customer_id) 
								JOIN {$this->schema}.email ON (raf_account.email_id = email.email_id)  ";
				
				$conditions = array();
				// raf_number condition
				if ($raf_number) 
					$conditions[] = " raf_account.raf_number = '".trim(strtoupper($raf_number))."' ";
				
				// email condition
				if ($email) 
					$conditions[] = " email.email_address = '".trim(strtoupper($email))."' ";
				
				if ($customer_info)
				{
					// customer info
					foreach( $customer_info as $cust_col => $cust_val)
					{
						if ($cust_val)
						{
							$conditions[] .= " {$cust_col} = ".strtoupper($cust_val)." ";
						}
					}
				}
				
				// apply conditions loop
				if ( count($conditions) )
				{
					$count = 0;
					foreach ($conditions as $condition)
					{
						$query .= ($count) ? " AND " . $condition : " WHERE ". $condition;
						
						++$count; 
					}
				}
					
				$query .= " ORDER BY customer.customer_id DESC ".$limit_val;


				$res = $this->db2->Execute($query);
				Error_2::Error_Test($res, FATAL_DEBUG);
				
				$count = 0;	
				$results = array(); //for when we do multiple transactions
				while ($row = $res->Fetch_Array() )
				{
					$results[$row['RAF_NUMBER']] = $row;		
				}
				
			break;
			
			case 'referee':
			
				// set array of schemas since web_raf user can not query multiple transaction schemas
				$schemas = array('D1', 'UFC');
				$results = array();
			
				foreach($schemas as $current_property)
				{
					$current_schema = $current_property;
					
					// prepare query
					$query = "SELECT customer.*, {$current_schema}.transaction.*, {$current_schema}.transaction.date_created as date_applied, transaction_sub_status.name as transaction_sub_status, transaction_status.name as transaction_status, raf_trans.*, 
							(SELECT date_created 
							FROM transaction_history 
							WHERE transaction_history.company_id = (SELECT company_id FROM company WHERE abbrev = '{$current_property}') 
								AND transaction_history.transaction_id = raf_trans.transaction_id 
								AND transaction_sub_status_id = 
									(SELECT transaction_sub_status_id 
									FROM transaction_sub_status
									WHERE name = 'ACTIVE')
							ORDER BY transaction_history.date_created DESC
							FETCH FIRST ROW ONLY
							) AS funded_date,
						(select email_address from email where email.email_id = {$current_schema}.transaction.active_email_id) as email_primary,
						(select abbrev from company where company.company_id = raf_trans.company_id) as property_short 
					FROM raf_trans 
						join raf_account on (raf_account.raf_number = raf_trans.raf_number) 
						join {$current_schema}.transaction on (raf_trans.transaction_id = {$current_schema}.transaction.transaction_id) 
						join customer on ({$current_schema}.transaction.customer_id = customer.customer_id) 
						left join transaction_sub_status on (transaction_sub_status.transaction_sub_status_id = {$current_schema}.transaction.transaction_sub_status_id) 
						left join transaction_status on (transaction_status.transaction_status_id = {$current_schema}.transaction.transaction_status_id) 
					";
					
					$conditions = array();
					
					// raf_number condition
					if ($raf_number) 
						$conditions[] = " raf_trans.raf_number = '".trim(strtoupper($raf_number))."' ";
					
					// company id condition
					$conditions[] = " raf_trans.company_id = (SELECT company_id FROM company WHERE abbrev = '{$current_property}') ";
					
					
					// email condition
					if ($email) 
						$conditions[] = " email.email_address = '".trim(strtoupper($email))."' ";
					
					if ($customer_info)
					{
						// customer info
						foreach( $customer_info as $cust_col => $cust_val)
						{
							if ($cust_val)
							{
								$conditions[] .= " {$cust_col} = ".strtoupper($cust_val)." ";
							}
						}
					}
					if ($date_constraint)
					{
						// activity report date constraint
						$conditions[] = " cast({$current_schema}.transaction.date_created AS DATE) = '{$date_constraint}' ";
					}
					
					// apply conditions loop
					if ( count($conditions) )
					{
						$count = 0;
						foreach ($conditions as $condition)
						{
							$query .= ($count) ? " AND " . $condition : " WHERE ". $condition;
							
							++$count; 
						}
							
					}
					
					$query .= " ORDER BY {$current_schema}.transaction.transaction_id DESC ";
					
					$res = $this->db2->Execute($query);
					Error_2::Error_Test($res, FATAL_DEBUG);
	
					$count = 0;	
					
					while ($row = $res->Fetch_Array() )
					{
						$results[$row['RAF_TRANS_ID']] = $row;
					}
				}
				krsort($results);
				
			break;

		}
		return $results;

	}

	function Get_Activity_Between_Dates($sql_date_from, $sql_date_to)
	{
		$results = array();
	
		{
			$current_schema = $current_property;
			
			// prepare query
			$query = "
				SELECT raf_account.raf_number, customer.name_first, customer.name_last, ach_status.name AS transaction_status, ach.amount 
				FROM raf_ach 
				INNER JOIN ach ON raf_ach.ach_id = ach.ach_id 
				INNER JOIN ach_status ON ach.ach_status_id = ach_status.ach_status_id 
				INNER JOIN raf_trans ON raf_ach.raf_trans_id = raf_trans.raf_trans_id 
				INNER JOIN raf_account ON raf_trans.raf_number = raf_account.raf_number 
				INNER JOIN customer ON raf_account.customer_id = customer.customer_id 
				WHERE CAST(raf_ach.date_modified AS DATE) BETWEEN '{$sql_date_from}' AND '{$sql_date_to}'
				";
			
			$res = $this->db2->Execute($query);
			Error_2::Error_Test($res, FATAL_DEBUG);

			$count = 0;	

			while ($row = $res->Fetch_Array())
			{
				$new_debit = 0;
				$new_credit = 0;
				$new_return = 0;

				switch (trim($row['TRANSACTION_STATUS']))
				{
					case 'SENT':
					case 'REFUNDED':
						$new_credit = $row['AMOUNT'];
						break;
					case 'REVERSED':
					default:
						$new_return = 1;
						$new_debit = $row['AMOUNT'];
						break;
				}

				if (!isset($results[$row['RAF_NUMBER']]))
				{
					// Store the redundant columns once
					$results[$row['RAF_NUMBER']] = array();
					$results[$row['RAF_NUMBER']]['RAF_NUMBER'] = $row['RAF_NUMBER'];
					$results[$row['RAF_NUMBER']]['NAME_FIRST'] = trim($row['NAME_FIRST']);
					$results[$row['RAF_NUMBER']]['NAME_LAST'] = trim($row['NAME_LAST']);
					
					// Get number of Referrals between the provided dates
					$query2 = "
						SELECT count(*) as count 
						FROM raf_trans 
						WHERE raf_number = '{$row['RAF_NUMBER']}' AND CAST(date_created AS DATE) BETWEEN '{$sql_date_from}' AND '{$sql_date_to}' 
						";
					$res2 = $this->db2->Execute($query2);
					if ($row2 = $res2->Fetch_Array())
					{
						$results[$row['RAF_NUMBER']]['num_referred'] = $row2['COUNT'];
					}
				}
				// Total transactions
				$results[$row['RAF_NUMBER']]['num_ach']++;

				// Total amount (credit+debit)
				$results[$row['RAF_NUMBER']]['AMOUNT'] += $row['AMOUNT'];

				// Update the totals, based on the transaction status
				$results[$row['RAF_NUMBER']]['debit'] += $new_debit;
				$results[$row['RAF_NUMBER']]['credit'] += $new_credit;
				$results[$row['RAF_NUMBER']]['returns'] += $new_return;
			}
		}

		return $results;
	}

	function Get_ACH_Transaction_List($raf_number)
	{
		$query = "
		SELECT ach.*, comment.comment, raf_trans.raf_number, 
			(SELECT name FROM ach_status WHERE ach_status.ach_status_id = ach.ach_status_id ) as ach_status, 
			(SELECT name FROM ach_type WHERE ach_type.ach_type_id = ach.ach_type_id ) as ach_type, 
			(SELECT name FROM bank_account_type WHERE bank_account_type.bank_account_type_id = ach.bank_account_type_id ) as bank_account_type
		FROM raf_trans 
			JOIN raf_ach ON (raf_ach.raf_trans_id=raf_trans.raf_trans_id) 
			LEFT JOIN comment on (raf_ach.comment_id = comment.comment_id) 
			JOIN ach ON (raf_ach.ach_id=ach.ach_id) 
		WHERE raf_trans.raf_number='{$raf_number}' 
		ORDER BY ach.date_created DESC
		";

		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		$count = 0;	
		$results = array(); //for when we do multiple transactions
		while ($row = $res->Fetch_Array() )
		{
			$results[$row['ACH_ID']] = $row;
		}

		return $results;
	}

	function Get_ACH_Transaction_List_For_Date($sql_date)
	{
		$query = "
		SELECT ach.*, comment.comment, raf_trans.raf_number, 
			(SELECT name FROM ach_status WHERE ach_status.ach_status_id = ach.ach_status_id ) as ach_status, 
			(SELECT name FROM ach_type WHERE ach_type.ach_type_id = ach.ach_type_id ) as ach_type, 
			(SELECT name FROM bank_account_type WHERE bank_account_type.bank_account_type_id = ach.bank_account_type_id ) as bank_account_type
		FROM raf_trans 
			JOIN raf_ach ON (raf_ach.raf_trans_id=raf_trans.raf_trans_id) 
			LEFT JOIN comment on (raf_ach.comment_id = comment.comment_id) 
			JOIN ach ON (raf_ach.ach_id=ach.ach_id) 
		WHERE CAST(ach.date_modified AS DATE) = '{$sql_date}' 
		ORDER BY ach.date_created DESC
		";

		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		$count = 0;	
		$results = array(); //for when we do multiple transactions
		while ($row = $res->Fetch_Array() )
		{
			$results[$row['ACH_ID']] = $row;
		}

		return $results;
	}
	
	function Get_Referer_Info( $raf_number )
	{

		// prepare query
		$query = "SELECT customer.*, bank_info.*, address.*, raf_account.*, login.crypt_password, 
			(select email_address from email where email.customer_id = customer.customer_id order by date_created desc fetch first row only) as email_primary,
			(select name from state where state.state_id = address.state_id) as state_name,
			(select phone_number from phone where phone.phone_id = raf_account.phone_home) as phone_home,
			(select phone_number from phone where phone.phone_id = raf_account.phone_cell) as phone_cell,
			(select phone_number from phone where phone.phone_id = raf_account.phone_work) as phone_work,
			(select name from bank_account_type where bank_account_type.bank_account_type_id = bank_info.bank_account_type_id) as bank_account_type 
		FROM {$this->schema}.raf_account
			left join {$this->schema}.customer on (customer.customer_id = raf_account.customer_id) 	
			left join {$this->schema}.address on (raf_account.customer_id = address.customer_id) 		
			left join {$this->schema}.bank_info on (raf_account.customer_id = bank_info.customer_id) 			
			left join {$this->schema}.login on (raf_account.raf_number = login.login)
		WHERE 
			raf_account.raf_number='{$raf_number}'
		";

		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		$row = $res->Fetch_Array();
		
		return $row;
	}
	
	function Update_ACH_Record( $data )
	{
		// update bank info table
		$query = "UPDATE {$this->schema}.ach
			SET date_modified = current timestamp,
				ach_date = current timestamp,	
				ach_status_id = (SELECT ach_status_id FROM ach_status WHERE name='{$data['ach_status']}')";
		// only update authentication id if it is passed in
		if ($data["authentication_id"])
			$query .= ",authentication_id = {$data['authentication_id']} ";
			
		$query .="	WHERE  
				ach_id = {$data['ach_id']}";
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		return true;
	}
	
	function Update_Referer_Record( $data )
	{
		require_once('security.4.php');
		define('PASSWORD_ENCRYPTION', 'ENCRYPT');
		
		$data = $this->Escape_Data($data);
		
		$encrypted_password = Security_4::_Mangle_Password($data['password']);
		
		// retrieve existing referer account info
		$referer_info = $this->Get_Referer_Info($data['raf_number']);

		$query = "UPDATE {$this->schema}.login
			SET crypt_password = '{$encrypted_password}' 
			WHERE login = '{$data['raf_number']}'
			";
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		// update bank info table
		$query = "UPDATE {$this->schema}.bank_info 
			SET date_modified = current timestamp,
				bank_name = '{$data['bank_name']}',	
				bank_aba = '{$data['bank_aba']}',
				bank_account = '{$data['bank_account']}',
				bank_account_type_id = {$data['bank_account_type_id']}
			WHERE  
				bank_info_id = {$referer_info['BANK_INFO_ID']}
			";
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		
		// update customer table
		$query = "UPDATE {$this->schema}.customer
			SET 
				name_first = '{$data['name_first']}',	
				name_last = '{$data['name_last']}',
				name_middle = '{$data['name_middle']}',
				social_security_number = '{$data['ssn']}'
			WHERE  
				customer_id = {$data['customer_id']}
			";
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		
		// update email table
		$query = "UPDATE {$this->schema}.email
			SET 
				email_address = '{$data['email']}'
			WHERE  
				email_id = {$referer_info['EMAIL_ID']}";
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		// update phone table
		foreach( array('home','work','cell') as $phone_type)
		{
			if ($data['phone_'.$phone_type])
			{
				if ($referer_info['PHONE_'.strtoupper($phone_type)])
				{
					// update email table
					$query = "UPDATE {$this->schema}.phone
						SET 
							phone_number = '".$data['phone_'.$phone_type]."'
						WHERE  
							phone_id = {$referer_info['PHONE_'.strtoupper($phone_type)]}";
					
					$res = $this->db2->Execute($query);
					Error_2::Error_Test($res, FATAL_DEBUG);
				}
				else
				{
					// insert the phone number	
					$query = "INSERT INTO {$this->schema}.phone (date_modified, date_created, customer_id, phone_number, phone_type_id, verification_status_id) values (current timestamp, current timestamp, {$data['customer_id']}, '{$data['phone_'.$phone_type]}', (SELECT phone_type_id FROM phone_type WHERE name='".strtoupper($phone_type)."'), 1)";
					
					$res = $this->db2->Execute($query);
					Error_2::Error_Test($res, FATAL_DEBUG);
					
					// get insert id
					$phone_id = $this->db2->Insert_Id($res);
					
					// update raf_account phone_id
					$query ="UPDATE {$this->schema}.raf_account SET phone_{$phone_type} = {$phone_id} WHERE RAF_NUMBER='{$data['raf_number']}'";
					$res = $this->db2->Execute($query);
					Error_2::Error_Test($res, FATAL_DEBUG);
				}
			}
		}
		

		// update address table
		$query = "UPDATE {$this->schema}.address
			SET date_modified = current timestamp,
				street = '{$data['home_street']}',	
				unit = '{$data['home_unit']}',
				city = '{$data['home_city']}',
				state_id = (SELECT state_id FROM {$this->schema}.STATE WHERE name = '{$data['home_state']}'),
				zip = '{$data['home_zip']}'
			WHERE  
				address_id = {$referer_info['ADDRESS_ID']}";
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		
		return true;
	}
	
	function Get_Eligible_ACH_List()
	{
		// set date limit to only select application funded at least 6 days ago
		$date_limit = date("Y-m-d-H.i.m.999999", strtotime("-6 days"));
		
		foreach ($this->company_list as $company)
		{
			// prepare query to query eligible raf trans
			$query = "SELECT  raf_trans.*, raf_account.* , raf_trans.date_created as app_date, raf_payout.amount
			FROM raf_trans 
				join raf_account on (raf_account.raf_number = raf_trans.raf_number)  
				left join raf_payout on (raf_payout.raf_payout_id = raf_trans.raf_payout_id)  
			WHERE
				 ((SELECT  transaction_history.transaction_sub_status_id
							FROM transaction_history 
							WHERE transaction_history.company_id = (SELECT company_id FROM company WHERE abbrev = '{$company}') 
								AND transaction_history.transaction_id = raf_trans.transaction_id 
							ORDER BY transaction_history.date_created DESC
							FETCH FIRST ROW ONLY
							) = (SELECT transaction_sub_status_id FROM transaction_sub_status WHERE name = 'ACTIVE')
				AND
				 (SELECT date_created 
							FROM transaction_history 
							WHERE transaction_history.company_id = (SELECT company_id FROM company WHERE abbrev = '{$company}') 
								AND transaction_history.transaction_id = raf_trans.transaction_id 
							ORDER BY transaction_history.date_created DESC
							FETCH FIRST ROW ONLY
							) < '{$date_limit}' )
				AND raf_trans.funded < 1 
				AND raf_trans.company_id = (SELECT company_id FROM company WHERE company.abbrev = '{$company}')  
			order by raf_trans.transaction_id desc";
	
			
			$res = $this->db2->Execute($query);
			Error_2::Error_Test($res, FATAL_DEBUG);
		
			//  add up the payout into an array keyed by raf_number
			while($row = $res->Fetch_Object())
			{	

				$raf_cust[$row->RAF_NUMBER]['AMOUNT'] += $row->AMOUNT; // array key is raf_number and customer id combined - to be split in the next loop
			}
		}
		
		
		
		if ($raf_cust)
		{
			// get referer account info for each raf_number
			foreach($raf_cust as $raf_number => $val)
			{
				// get referer info
				$referer_info = $this->Get_Referer_Info( $raf_number );
							
				// combine the val and referer info into result array
				$results[$raf_number] = array_merge($referer_info, $val);
			}
		}
			
		return $results;
	}
	
	function Process_ACH_Batch( $raf_ids, &$session  )
	{
		// set date limit to only select application funded at least 7 days ago
		$date_limit = date("Y-m-d-H.i.s.999999", strtotime("-6 days"));
		
		// prepare raf_batch for IN condition in query
		// array of ids are passed in from the selected checkboxes
		$raf_batch = '';
		$i = 0;
		foreach( $raf_ids as $raf)
		{
			$raf_batch .= ($i) ? ", '".$raf."'" : "'".$raf."'" ;
			$i++;
		}
		
		$billing_obj = new stdClass();
		
		foreach($this->company_list as $company)
		{
			// prepare query to query selected raf ids
			$query = "
			SELECT  raf_trans.*, raf_account.*, email.email_address, 
				(SELECT amount FROM raf_payout WHERE raf_payout.raf_payout_id = raf_trans.raf_payout_id) AS amount
			FROM raf_trans 
				join raf_account on (raf_account.raf_number = raf_trans.raf_number)  
				join email on (raf_account.email_id = email.email_id) 
			WHERE 
				raf_trans.raf_number in ({$raf_batch}) 
			AND (SELECT date_created 
							FROM transaction_history 
							WHERE transaction_history.company_id = (SELECT company_id FROM company WHERE abbrev = '{$company}') 
								AND transaction_history.transaction_id = raf_trans.transaction_id 
								AND transaction_sub_status_id = 
									(SELECT transaction_sub_status_id 
									FROM transaction_sub_status
									WHERE name = 'ACTIVE')
							ORDER BY transaction_history.date_created DESC
							FETCH FIRST ROW ONLY
							) < '{$date_limit}' 
			AND raf_trans.funded < 1  
			AND raf_trans.company_id = (SELECT company_id FROM company WHERE company.abbrev = '{$company}')  
			order by raf_trans.transaction_id desc";
	
	
			$res = $this->db2->Execute($query);
			Error_2::Error_Test($res, FATAL_DEBUG);
			
			
			$raf_cust = array();
			$total_amount = 0;
			
			//  add up the payout into an array keyed by raf_number
			while($row = $res->Fetch_Object())
			{				
				if (!$session)
					die("No session");
				$session->Hit_Stat('funded');
				
				$billing_obj->{$row->RAF_NUMBER}->amount += $row->AMOUNT;
				$billing_obj->{$row->RAF_NUMBER}->raf_number = $row->RAF_NUMBER;
				$billing_obj->{$row->RAF_NUMBER}->email_address = $row->EMAIL_ADDRESS;
				
				// create object of transactions to update
				$billing_obj->{$row->RAF_NUMBER}->transactions[$row->TRANSACTION_ID] = $row->RAF_TRANS_ID;
				
				// total amount to be credited
				$total_amount += $row->AMOUNT;
			}
		}	
			
		
		
		
		// insert ach record and retrieve bank info for each of the biilling objects
		while(list($raf_number, $val) = each($billing_obj))
		{
			// get referer account info
			$raf_acct = $this->Get_Referer_Info( $raf_number );
			
			// add bank info to billing object
			$billing_obj->{$raf_number}->routing = $raf_acct['BANK_ABA'];
			$billing_obj->{$raf_number}->account = $raf_acct['BANK_ACCOUNT'];
			$billing_obj->{$raf_number}->account_type = $raf_acct['BANK_ACCOUNT_TYPE'];
			$billing_obj->{$raf_number}->first_name = $raf_acct['NAME_FIRST'];
			$billing_obj->{$raf_number}->last_name = $raf_acct['NAME_LAST'];
			
			// prepare ach data
			$ach_insert['amount'] = $val->amount;
			$ach_insert['bank_account'] = $raf_acct['BANK_ACCOUNT'];
			$ach_insert['bank_aba'] = $raf_acct['BANK_ABA'];
			$ach_insert['ach_status'] = 'PENDING';
			$ach_insert['authentication_id'] = 0;
			$ach_insert['bank_account_type'] = $raf_acct['BANK_ACCOUNT_TYPE'];
			$ach_insert['ach_type'] = 'CREDIT';
			$ach_insert['ach_return_code_id'] = NULL;
							
			// insert record and add ach_id to the billing object record trace number
			$billing_obj->{$raf_number}->trace_number = $this->Insert_ACH_Record( $ach_insert );
		}

		// send ach billing object ach batch
		$response = $this->Send_ACH_Batch( $billing_obj, 'CREDIT', $total_amount);

		if ($response['intercept'] && $response['intercept']['ER'] == '0')
		{
			//reset pointer
			reset($billing_obj);

			// insert ach record for each of the billing objects and update raf_trans funded to 1
			while(list($raf_number, $val) = each($billing_obj))
			{
				// update ach data status to sent and authentication_id
				$ach_update['authentication_id'] = $response['authentication_id'];
				$ach_update['ach_id'] = $val->trace_number;
				$ach_update['ach_status'] = 'SENT';

				$this->Update_ACH_Record( $ach_update );
				
				foreach ($val->transactions as $transaction_id => $raf_trans_id)
				{
					// update ach record
					$raf_ach_data['raf_trans_id'] = $raf_trans_id;	
					$raf_ach_data['ach_id'] = $val->trace_number;
					$this->Insert_RAF_ACH_Record( $raf_ach_data );
					
					// update raf_trans funded col to 1
					$query = "UPDATE {$this->schema}.raf_trans
					SET funded = 1
					WHERE raf_number = '{$raf_number}'
					AND transaction_id={$transaction_id}";
					$res = $this->db2->Execute($query);
					Error_2::Error_Test($res, FATAL_DEBUG);
				}

				$this->Send_Funded_Email($val->email_address, trim($val->first_name) . ' ' . trim($val->last_name));
			}
		}
		
		return $response;
	}
	
	function Send_ACH_Batch( $billing_obj, $method, $amount )
	{
		// set nms ach vars
		$company_name = strtoupper('ConsumerServiceCorp');
		$tax_id = '9880390002';
		$bank_account_number = '4343923316';
		$bank_aba = '101000187';
		$bank_account_type = 'CHECKING';
		$phone_number = '8008605417';
		
		// prepare ach insert for master account
		$ach_insert['amount'] = $amount;
		$ach_insert['bank_account'] = $bank_account_number;
		$ach_insert['bank_aba'] = $bank_aba;
		$ach_insert['authentication_id'] = 0;
		$ach_insert['ach_status'] = 'PENDING';
		$ach_insert['bank_account_type'] = $bank_account_type;
		$ach_insert['ach_type'] = ($method == 'CREDIT') ? 'DEBIT' : 'CREDIT';
		$ach_insert['ach_return_code_id'] = NULL;

		// insert master ach record and set master trace number var
		$master_ach_id = $this->Insert_ACH_Record( $ach_insert );

		// include/instatiate ach object
		require_once("ach.1.php");
		$ach_obj = new ACH ();
		
		// build ach batch data
		$ach_file = $ach_obj->Build_Ach($billing_obj, $company_name, $tax_id, $bank_account_number, $bank_aba, $bank_account_type, $phone_number, $method, $master_ach_id);
		
		
		// create temp ach file in tmp
		$tmp_file_name = date("Ymdhis");
		$ach_file_path = '/tmp/RAF-'.$tmp_file_name.'.ach';
		$this->Create_File ($ach_file_path , $ach_file);
		
		// post file to intercept
		$ach_post_response = $this->Post_ACH ($ach_obj, $ach_file_path);
		
		//delete temp ach file
		unlink($ach_file_path);
		
		if ($ach_post_response['received'])
		{
			foreach (split('&', $ach_post_response['received']) as $var)
			{
				list($key, $val) = split('=', $var);
				$intercept_return[$key] = $val;
			}
			
			// update ach data for master account
			$ach_update['authentication_id'] = $ach_post_response['authentication_id'];
			$ach_update['ach_id'] = $master_ach_id;
			$ach_update['ach_status'] = 'SENT';
			
			$this->Update_ACH_Record( $ach_update );
		}
		
		// combine return vars
		$return['ach_file'] = $ach_file;
		$return['master_ach_id'] = $master_ach_id;
		$return['authentication_id'] = $ach_post_response['authentication_id'];
		$return['intercept'] = $intercept_return;

		return $return;
	}
	
	
	function Process_ACH_Returns()
	{
		// get the most recent date of the last returns report
		$query = "SELECT date_created FROM {$this->schema}.authentication 
					WHERE authentication_type_id=(SELECT authentication_type_id FROM authentication_type WHERE name='ACH_RETURN') 
					ORDER BY date_created DESC fetch first row only";
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		if ($count = $res->Num_Rows())
		{
			$recent_auth = $res->Fetch_Array();
			list($date, $time) = split(" ", $recent_auth['DATE_CREATED']);
			$start_date = date(Ymd, strtotime("+1 day", strtotime($date)));
		}
		else
		{
			$start_date = date("Ymd", strtotime("-10 days"));	
		}

		// current date
		$current_date = date("Ymd");
		
		// original start date
		$original_start_date = $start_date;

		// create var to gather all return dbf lines used to display in email to raf administrator
		$ach_return_lines = NULL;
		
		// include/instatiate ach object
		require_once("ach.1.php");
		$ach_obj = new ACH ();

		while($start_date < $current_date)
		{
			if ($response = $this->ACH_Return_Report_Post ( $ach_obj, $start_date ))
			{
				if (!ereg('ER=', $response['received']))
				{
					// gather return dbf lines for email notification to admin
					$ach_return_lines .= "\n\n".date("m-d-Y", strtotime($start_date))."\n".$response['received'];
					
					$ach_return_data = $ach_obj->ACH_Return_Batch ( $response['received']);

					foreach ($ach_return_data as $return_data)
					{
						$ach_id = preg_replace('/^0{0,}/', '', $return_data['recipient_id']);
						
						//  make sure that the ach id is numeric
						if (is_numeric($ach_id))
						{
							// update the ach status to returned
							$query = "
								UPDATE {$this->schema}.ach 
								SET ach_status_id = (SELECT ach_status_id FROM ach_status WHERE name='RETURNED') 
								WHERE ach_id = {$ach_id}";
							$res = $this->db2->Execute($query);
							Error_2::Error_Test($res, FATAL_DEBUG);
						}
					}
				}
				
				// increment start date + one day
				$start_date = date("Ymd", strtotime("+1 day", strtotime($start_date)));	
			}	
		}
		
		// send admin the ach return reports
		$email_body = ($ach_return_lines) ? $ach_return_lines : "There are no returns for the following date range: {$original_start_date} - ".date("Ymd", strtotime("-1 day"));
		$this->Send_Admin_Email( "Intercept return ACH report - {$original_start_date} - ".date("Ymd", strtotime("-1 day")), $email_body );

	}
	
	function Refund_ACH( $ach_id, $raf_number )
	{
		// get referer account info
		$raf_acct = $this->Get_Referer_Info( $raf_number );

		// query all transactions in specified ach id and raf number
		$query = "SELECT  raf_trans.*
		FROM {$this->schema}.raf_ach 
			join {$this->schema}.raf_trans on (raf_trans.raf_trans_id = raf_ach.raf_trans_id)  
			join {$this->schema}.raf_account ON (raf_account.raf_number = raf_trans.raf_number) 
		WHERE 
			raf_ach.ach_id = {$ach_id} 
		AND raf_trans.raf_number = '{$raf_number}' 
		order by raf_trans.transaction_id desc";
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		while($row = $res->Fetch_Array())
		{
			// build raf_trans_ids for the update statement IN condition
			$raf_trans_ids .= ($raf_trans_ids) ? ','.$row['RAF_TRANS_ID'] : $row['RAF_TRANS_ID'] ;
		}

		// update raf_trans to 0 so it will be picked up to funded again
		$query = "UPDATE {$this->schema}.raf_trans SET funded=0 WHERE raf_trans_id in ({$raf_trans_ids})";
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		// update original ach record to refunded status
		$ach_update['ach_id'] = $ach_id;
		$ach_update['ach_status'] = 'REFUNDED';
		$this->Update_ACH_Record( $ach_update );


		return TRUE;
	}

	function Refund_Trans( $raf_trans_id )
	{
		// update raf_trans to 0 so it will be picked up to funded again
		$query = "UPDATE {$this->schema}.raf_trans SET funded=0 WHERE raf_trans_id = {$raf_trans_id}";
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		return true;
	}
	
	
	function Debit_ACH( $comment, $ach_id, $raf_number )
	{
		// get referer account info
		$raf_acct = $this->Get_Referer_Info( $raf_number );
		
		// query all transactions in specified ach id and raf number
		$query = "SELECT  raf_trans.*, raf_account.*, 
			(SELECT amount FROM raf_payout WHERE raf_payout.raf_payout_id = raf_trans.raf_payout_id) AS amount 
		FROM {$this->schema}.raf_ach 
			join {$this->schema}.raf_trans on (raf_trans.raf_trans_id = raf_ach.raf_trans_id)  
			join {$this->schema}.raf_account ON (raf_account.raf_number = raf_trans.raf_number) 
		WHERE 
			raf_ach.ach_id = {$ach_id} 
		AND raf_trans.raf_number = '{$raf_number}' 
		AND raf_trans.funded != 2 
		order by raf_trans.transaction_id desc";
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		// if there are no results return false
		if (!$count = $res->Num_Rows())
			return false;
		
		while($row = $res->Fetch_Object())
		{	
			
			$billing_obj->{$row->RAF_NUMBER}->amount += $row->AMOUNT;
			$billing_obj->{$row->RAF_NUMBER}->routing = $raf_acct['BANK_ABA'];
			$billing_obj->{$row->RAF_NUMBER}->account = $raf_acct['BANK_ACCOUNT'];
			$billing_obj->{$row->RAF_NUMBER}->account_type = $raf_acct['BANK_ACCOUNT_TYPE'];
			$billing_obj->{$row->RAF_NUMBER}->first_name = $raf_acct['NAME_FIRST'];
			$billing_obj->{$row->RAF_NUMBER}->last_name = $raf_acct['NAME_LAST'];
			$billing_obj->{$row->RAF_NUMBER}->raf_number = $raf_number;
			
			// collect raf_trans_id to update later
			$billing_obj->{$row->RAF_NUMBER}->transactions[$row->TRANSACTION_ID] = $row->RAF_TRANS_ID;
			
			// total amount to be credited
			$total_amount += $row->AMOUNT;
		}

		// insert ach record for each of the billing objects
		while(list($raf_number, $val) = each($billing_obj))
		{
			// prepare ach data
			$ach_insert['amount'] = $val->amount;
			$ach_insert['bank_account'] = $val->account;
			$ach_insert['bank_aba'] = $val->routing;
			$ach_insert['ach_status'] = 'PENDING';
			$ach_insert['authentication_id'] = 0;
			$ach_insert['bank_account_type'] = $val->account_type;
			$ach_insert['ach_type'] = 'DEBIT';
			$ach_insert['ach_return_code_id'] = NULL;
					
			// insert record and add ach_id to the billing object record trace number
			$billing_obj->{$raf_number}->trace_number = $this->Insert_ACH_Record( $ach_insert );
		}


		// send ach billing object ach batch
		$response = $this->Send_ACH_Batch( $billing_obj, 'DEBIT', $total_amount);

		if ($response['intercept'] && $response['intercept']['ER'] == '0')
		{
			//reset pointer
			reset($billing_obj);
			
			// insert comment
			$comment_data['comment'] = $comment;
			$comment_data['transaction_id'] = 0;
			$comment_id = $this->Insert_Comment( $comment_data );
			
			// insert ach record for each of the billing objects and update raf_trans funded to 1
			while(list($raf_number, $val) = each($billing_obj))
			{
	
				// update ach data status to sent and authentication_id
				$ach_update['authentication_id'] = $response['authentication_id'];
				$ach_update['ach_id'] = $val->trace_number;
				$ach_update['ach_status'] = 'SENT';
				
				$this->Update_ACH_Record( $ach_update );
				
				
				
				foreach ($val->transactions as $transaction_id => $raf_trans_id)
				{
					// update ach record
					$raf_ach_data['raf_trans_id'] = $raf_trans_id;	
					$raf_ach_data['ach_id'] = $val->trace_number;
					$raf_ach_data['comment_id'] = $comment_id;
					$this->Insert_RAF_ACH_Record( $raf_ach_data );
					
				}
			}
			
			// update original ach record to refunded status
			$ach_update['authentication_id'] = NULL;
			$ach_update['ach_id'] = $ach_id;
			$ach_update['ach_status'] = 'REVERSED';
			$this->Update_ACH_Record( $ach_update );
		}
		
		return $response;
	}
	
	function Post_ACH ($ach_obj, $ach_file_path)
	{
		// set intercept vars
		$post_vars['url'] = "https://www.intercepteft.com/uploadach.icp";
		$post_vars['fields']['login'] = $this->ach_login;
		$post_vars['fields']['pass'] = $this->ach_pass;
		$post_vars['fields']['force'] = "T";
		$post_vars['fields']['filename'] = "@".$ach_file_path;
		$post_vars['fields']['file'] = implode('', file($ach_file_path));
		

		$response = $ach_obj->HTTP_Post($post_vars);

		if ($response["received"])
		{
			// Insert Authentication Record
			$response['authentication_id'] = $this->Insert_Authentication( 0, NULL, $response['received'], $post_vars['fields']['file'], NULL, 'INTERCEPT', 'ACH_BATCH');
		}
		
		return $response;
	}
	
	function ACH_Return_Report_Post ( $ach_obj, $start_date )
	{
		// set intercept vars
		$post_vars['url'] = "https://www.intercepteft.com/getintercepteftreport.icp";
		$post_vars['fields']['login'] = $this->ach_login;
		$post_vars['fields']['pass'] = $this->ach_pass;
		$post_vars['fields']['report'] = "RET";
		$post_vars['fields']['format'] = "CSV";
		$post_vars['fields']['sdate'] = date("Ymd", strtotime($start_date));
		$post_vars['fields']['edate'] = date("Ymd");
		
		$response = $ach_obj->HTTP_Post($post_vars);

		if ($response["received"])
		{
			// Insert Authentication Record
			$response['authentication_id'] = $this->Insert_Authentication( 0, NULL, $response['received'], $post_vars['fields']['file'], NULL, 'INTERCEPT', 'ACH_RETURN');
		}
		else 
		{
			// try again
			$response = $this->Get_ACH_Return_Post ( $ach_obj, $start_date );	
		}
		
		
		
		return $response;
	}
	
	function Get_ACH_Return_Authentication( $dates )
	{
		$query = "
		SELECT * 
		FROM {$this->schema}.authentication 
		WHERE authentication_type_id=(SELECT authentication_type_id FROM authentication_type WHERE name='ACH_RETURN')
		AND date_created BETWEEN '".date("Y-m-d", strtotime($dates['from_date']))."-00.00.00.000000' and '".date("Y-m-d", strtotime($dates['to_date']))."-23.59.59.999999'
		AND received_package not like '%ER=%'
		";
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		while ($row = $res->Fetch_Array() )
		{
			$results[$row['AUTHENTICATION_ID']] = $row;
		}
		
		return $row;
	}
	
	function Get_ACH_Returns( $dates )
	{
		$query = "
		SELECT 
			ach_history.date_created as date_returned, 
			ach.*, 
			raf_trans.*, 
			raf_ach.*, 
			authentication.*,
			raf_account.*,
		 	(SELECT name FROM bank_account_type WHERE bank_account_type.bank_account_type_id= ach.bank_account_type_id) as bank_account_type
		FROM ach_history
			JOIN ach ON (ach.ach_id = ach_history.ach_id) 
			JOIN raf_ach ON (raf_ach.ach_id = ach.ach_id) 
			JOIN authentication ON (ach.authentication_id = authentication.authentication_id)
			JOIN raf_trans ON (raf_trans.raf_trans_id = raf_ach.raf_trans_id) 
			JOIN raf_account ON (raf_account.raf_number = raf_trans.raf_number)
		WHERE ach_history.ach_status_id = (SELECT ach_status_id FROM ach_status WHERE name = 'RETURNED') 
		AND ach_history.date_created BETWEEN '".date("Y-m-d", strtotime($dates['date_start']))."-00.00.00.000000' and '".date("Y-m-d", strtotime($dates['date_end']))."-23.59.59.999999' 
		";
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		while ($row = $res->Fetch_Array() )
		{
			$results[$row['ACH_ID']] = $row;
			
		}

		return $results;
	}
	
	function Insert_ACH_Record( $data )
	{
		$data = $this->Escape_Data($data);
		
		// repare insert stmt
		$query = "INSERT INTO {$this->schema}.ach 
			(
				date_modified, 
				date_created, 
				amount,
				ach_date, 
				authentication_id, 
				bank_account,
				aba,
				ach_status_id,
				bank_account_type_id,
				ach_type_id,
				ach_return_code_id
			) 
			VALUES 
			(
				current timestamp,
				current timestamp, 
				{$data['amount']},
				current timestamp,
				{$data['authentication_id']},
				'{$data['bank_account']}',
				'{$data['bank_aba']}',
				(SELECT ach_status_id FROM ach_status WHERE name='{$data['ach_status']}'),
				(SELECT bank_account_type_id FROM bank_account_type where name='{$data['bank_account_type']}'),
				(SELECT ach_type_id FROM ach_type WHERE name='{$data['ach_type']}'),
				NULL
			)";
		
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		$insert_id = $this->db2->Insert_Id($res);
		
		return $insert_id;
	}
	
	
	function Insert_RAF_ACH_Record( $data )
	{
		$data = $this->Escape_Data($data);
		
		$data['comment_id'] = ($data['comment_id']) ? $data['comment_id'] : 'NULL';
		
		// repare insert stmt
		$query = "INSERT INTO {$this->schema}.raf_ach 
			(
				date_modified, 
				date_created, 
				raf_trans_id,
				comment_id,
				ach_id
			) 
			VALUES 
			(
				current timestamp,
				current timestamp,
				{$data['raf_trans_id']}, 
				{$data['comment_id']}, 
				{$data['ach_id']}
			)";
		
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		$insert_id = $this->db2->Insert_Id($res);
		
		return $insert_id;
	}
	
	
	function Insert_RAF_Payout( $data )
	{
		// repare insert stmt
		$query = "INSERT INTO {$this->schema}.raf_payout 
			(
				date_modified, 
				date_created, 
				date_start,"; 
		
		if ($data['date_end']) $query .= " date_end, \n";  // condition for date end since its not required
		
		$query .= " amount, 
				comment, 
				type
			) 
			VALUES 
			(
				current timestamp,
				current timestamp, 
				'{$data['date_start']}', ";
		
		if ($data['date_end']) $query .= " '{$data['date_end']}', \n";  // condition for date end since its not required
		
		$query .= " {$data['amount']}, 
				'{$data['comment']}', 
				'{$data['type']}'
			)";
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		return true;
	}
	
	function Get_ACH_Batch_List(  )
	{
		$query = "
		SELECT 
			ach.*, 
			authentication.*, 
			(SELECT ach_type.name FROM ach_type WHERE ach.ach_type_id = ach_type.ach_type_id) as method
		FROM raf_ach 
		JOIN ach ON (raf_ach.ach_id = ach.ach_id) 
		JOIN authentication ON (ach.authentication_id=authentication.authentication_id) 
		ORDER BY ach.date_created DESC
		";
		
//		echo $query = "
//		SELECT 
//			ach.authentication_id,
//			sum(ach.amount),
//			max(ach.date_created)
//		FROM ach 
//		JOIN raf_ach ON (raf_ach.ach_id = ach.ach_id) 
//		GROUP BY ach.authentication_id
//		";		
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);

		while ($row = $res->Fetch_Array() )
		{

			if (!$results[$row['AUTHENTICATION_ID']][$row['ACH_ID']])
			{
				$results[$row['AUTHENTICATION_ID']]['TOTAL_AMOUNT'] += $row['AMOUNT'];
				$results[$row['AUTHENTICATION_ID']]['METHOD'] = $row['METHOD'];
				$results[$row['AUTHENTICATION_ID']]['AUTHENTICATION_ID'] = $row['AUTHENTICATION_ID'];
				$results[$row['AUTHENTICATION_ID']]['DATE_CREATED'] = $row['DATE_CREATED'];
				$results[$row['AUTHENTICATION_ID']][$row['ACH_ID']] = true;
				$results[$row['AUTHENTICATION_ID']]['TOTAL_LINES']++;
			}
		}

		return $results;
	}
	
	function Get_RAF_Payout_Info( $raf_payout_id )
	{
		$query = "SELECT * FROM {$this->schema}.raf_payout WHERE raf_payout_id={$raf_payout_id}";
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		$row = $res->Fetch_Array();
		
		return $row;
	}
	
	function Get_Authentication( $authentication_id )
	{
		$query = "SELECT * FROM {$this->schema}.authentication WHERE authentication_id={$authentication_id}";
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		$row = $res->Fetch_Array();
		
		return $row;
	}
	
	function Current_RAF_Payout( $date )
	{
		$query = "SELECT * FROM {$this->schema}.raf_payout WHERE date_start < '{$date}' and (date_end>'{$date}' or date_end is null)  order by date_start desc fetch first row only";

		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		$row = $res->Fetch_Array();
		
		return $row;
	}
	
	function Get_RAF_Payout_List()
	{
		$query = "SELECT * FROM {$this->schema}.raf_payout ORDER BY date_start ASC";
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		$results = array(); 
		while ($row = $res->Fetch_Array() )
		{
			$results[$row['RAF_PAYOUT_ID']] = $row;
		}
		return $results;
	}
	
	function Edit_RAF_Payout( $data )
	{
		$data = $this->Escape_Data($data);

		$query = "UPDATE {$this->schema}.raf_payout 
					SET 
					date_start = '{$data['date_start']}', ";

		// edit date end if exists
		if ($data['date_end']) $query .= " date_end = '{$data['date_end']}', ";

		$query .= "		amount = {$data['amount']}, 
						comment = '{$data['comment']}', 
						type = '{$data['type']}' 
					WHERE 
						raf_payout_id = {$data['raf_payout_id']}";
		
		$res = $this->db2->Execute($query);
		Error_2::Error_Test($res, FATAL_DEBUG);
		
		return true;
	}
	
		
	function Escape_Data($data)
	{
		
		foreach($data as $key => $sub_data)
		{
			if( is_array($sub_data) || is_object($sub_data) )
			{
				is_object($sub_data) ? $escaped->{$key} = $this->Escape_Data($sub_data) : $escaped[$key] = $this->Escape_Data($sub_data);		
			}
			else
			{
				is_object($sub_data) ? $escaped->{$key} = $this->db2_escape_chars($sub_data):$escaped[$key] = $this->db2_escape_chars($sub_data);				
			}
		}
		return $escaped;	
	}
	
	function Fetch_Errors()
	{
		return $this->errors;
	}
	
	function db2_escape_chars($string)
	{
		if( is_string($string) )
		{
			$string = str_replace("'", "''", $string);
		}
		return $string;
	}
	
	function Create_File ($file, $content)
	{		
		   $handle = fopen($file, 'w+'); 
		   fwrite($handle, $content);
		   fclose($handle);
		   
		   return true;
	}
	
	function Insert_Authentication( $customer_id, $session_id, $received_package, $sent_package, $score, $auth_src_name, $auth_type_name) 
	{ 
		$received_package = $this->db2_escape_chars($received_package);
		$sent_package = $this->db2_escape_chars($sent_package);
		
		// insert into authentication table
		$query = "INSERT INTO ".$this->schema.".AUTHENTICATION ( date_modified, date_created, customer_id, session_id, authentication_source_id, sent_package, received_package, score, authentication_type_id ) values
			( current timestamp, current timestamp, {$customer_id}, '{$session_id}', (SELECT authentication_source_id FROM ".$this->schema.".AUTHENTICATION_SOURCE WHERE name = '{$auth_src_name}'), ? , ? ,'{$score}',(SELECT authentication_type_id FROM ".$this->schema.".AUTHENTICATION_TYPE WHERE name = '{$auth_type_name}') )";

		// Prepare the query
		$p_result = $this->db2->Query($query);
		
		// execute query
		$result = $p_result->Execute($sent_package, $received_package);
		
		//check for errors
		if ($err = Error_2::Error_Test($result, FATAL_DEBUG))
		{
			return FALSE;	
		}
		$insert_id = $this->db2->Insert_Id($result);
		return $insert_id;
	}

	function Insert_Comment( $comment_data )
	{
		$comment_data = $this->Escape_Data($comment_data);
		
		// do we have comments to insert
		$comment = array();
		$comment['date_modified'] = "CURRENT TIMESTAMP";
		$comment['date_created'] = "CURRENT TIMESTAMP";
		$comment['comment_source_id'] = "(SELECT comment_source_id FROM {$this->schema}.comment_source WHERE name = 'SYSTEM')";
		$comment['agent_id'] = "(SELECT agent_id FROM agent WHERE login = 'akc')";
		$comment['visibility_id'] = "(SELECT visibility_id FROM {$this->schema}.visibility WHERE name = 'RAF')";
		$comment['comment'] = "'{$comment_data["comment"]}'";
		$comment['customer_id'] = "0";
			
		$comment_id = $this->Table_Insert($this->schema ."."."comment", $comment);
		
		return $comment_id;
	
	}
	
	function Send_Admin_Email( $subject, $body )
	{
		require_once ("prpc/client.php");
		$ole_mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
		
		//  admin email array
		$admin_emails = array(
			"don.adriano@thesellingsource.com" => "Don Adriano",
			//"syoakum@41cash.com" => "Shalite Yaokum"
		);
		
		// loop through each admin email (ghetto loop since ole can not handle multiple email sending)
		foreach($admin_emails as $email => $name)
		{
			// prepare ole event email tokens and required data
			$email_data['email_primary'] = $email; 
			$email_data['email_primary_name'] = $name; 
			$email_data['site_name'] = 'cash4friends.com';
			$email_data['name_view'] = 'RAF Admin';
			$email_data['body'] = $body;
			$email_data['subject'] = $subject;
			
			$ole_mailing_id = $ole_mail->Ole_Send_Mail ("GENERIC", NULL, $email_data);
		}
		
		return TRUE;
	}
	
	function Send_Funded_Email( $email_address, $customer_name )
	{
		require_once ("prpc/client.php");
		$ole_mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
		
		$to_emails = array(
			$email_address => $customer_name);
		
		// loop through each customer email (ghetto loop since ole can not handle multiple email sending)
		foreach($to_emails as $email => $name)
		{
			// prepare ole event email tokens and required data
			$email_data['email_primary'] = $email; 
			$email_data['email_primary_name'] = $name; 
			$email_data['site_name'] = 'cash4friends.com';
			$email_data['name_view'] = $email_data['site_name'];
			$email_data['name'] = $customer_name;
			
			$ole_mailing_id = $ole_mail->Ole_Send_Mail ("RAF_FUNDED", NULL, $email_data);
		}
		
		return TRUE;
	}
	
	function Get_Customer_Details($raf_number)
	{
		$query = "
			SELECT raf_account.raf_number, customer.name_first, customer.name_last, customer.social_security_number, email.email_address, phone.phone_number, address.street, address.city, state.name AS state_name, address.zip, bank_info.bank_name, bank_info.bank_aba, RTRIM(bank_info.bank_account) AS bank_account, bank_account_type.name AS bank_account_type_name 
			FROM raf_account 
			INNER JOIN customer ON raf_account.customer_id = customer.customer_id 
			INNER JOIN email ON raf_account.email_id = email.email_id 
			INNER JOIN phone on raf_account.phone_home = phone.phone_id 
			INNER JOIN address on raf_account.address_id = address.address_id 
			INNER JOIN state on address.state_id = state.state_id 
			INNER JOIN bank_info on raf_account.bank_info_id = bank_info.bank_info_id 
			INNER JOIN bank_account_type ON bank_info.bank_account_type_id = bank_account_type.bank_account_type_id 
			WHERE raf_account.raf_number = '{$raf_number}'
			";
		
		$result = $this->db2->Execute($query);
		Error_2::Error_Test($result, FATAL_DEBUG);
		
		if ($row = $result->Fetch_Array())
		{
			return $row;
		}
		
		return NULL;
	}
	
	function Update_Customer_Details(&$new_data, $raf_number)
	{
//		print_r($new_data);
//		exit();
		require_once('security.4.php');

		$new_password_encrypted = Security_4::_Mangle_Password($new_data['password']);
		
		$sql_error = FALSE;

		$this->db2->Autocommit(FALSE);

		if ($new_data['password'])
		{
  			$query = "
  				UPDATE login
				SET crypt_password = '{$new_password_encrypted}' 
				WHERE login = '{$raf_number}'
				";
			$result = $this->db2->Execute($query);
			$sql_error = (boolean)($sql_error || is_a($result, 'Error_2'));
			if (is_a($result, 'Error_2'))
  				$errors_on[] = $query;
		}

		
		$query = "
			UPDATE phone 
			SET phone_number = '{$new_data['phone_home']}' 
			WHERE phone_id IN(
				SELECT phone_home 
				FROM raf_account 
				WHERE raf_number = '{$raf_number}'
				)
			";
		$result = $this->db2->Execute($query);
		$sql_error = (boolean)($sql_error || is_a($result, 'Error_2'));
		if (is_a($result, 'Error_2'))
			$errors_on[] = $query;

		$query = "
			UPDATE address 
			SET street = '{$new_data['home_street']}', city = '{$new_data['home_city']}', zip = '{$new_data['home_zip']}', state_id = (SELECT state_id FROM state where name = '{$new_data['home_state']}') 
			WHERE address_id IN(
				SELECT address_id 
				FROM raf_account 
				WHERE raf_number = '{$raf_number}'
				)
			";
		$result = $this->db2->Execute($query);
		$sql_error = (boolean)($sql_error || is_a($result, 'Error_2'));
		if (is_a($result, 'Error_2'))
			$errors_on[] = $query;

		$query = "
			UPDATE email 
			SET email_address = '{$new_data['email_primary']}' 
			WHERE email_id IN(
				SELECT email_id 
				FROM raf_account 
				WHERE raf_number = '{$raf_number}'
				)
			";
		$result = $this->db2->Execute($query);
		$sql_error = (boolean)($sql_error || is_a($result, 'Error_2'));
		if (is_a($result, 'Error_2'))
			$errors_on[] = $query;

		$query = "
			UPDATE bank_info 
			SET bank_name = '{$new_data['bank_name']}', bank_aba = '{$new_data['bank_aba']}', bank_account = '{$new_data['bank_account']}', bank_account_type_id = (SELECT bank_account_type_id FROM bank_account_type WHERE name = '{$new_data['bank_account_type']}') 
			WHERE bank_info_id IN(
				SELECT bank_info_id 
				FROM raf_account 
				WHERE raf_number = '{$raf_number}'
				)
			";
		$result = $this->db2->Execute($query);
		$sql_error = (boolean)($sql_error || is_a($result, 'Error_2'));
		if (is_a($result, 'Error_2'))
			$errors_on[] = $query;

		if ($sql_error)
		{
			$this->db2->Rollback();
			$this->db2->Autocommit(TRUE);
		}
		else
		{
			$this->db2->Commit();
			$this->db2->Autocommit(TRUE);
		}
		
		$this->db2->Autocommit(TRUE);
		
		return !$sql_error;
	}
	
	function Validate_Customer_Login($raf_number, $password)
	{
		require_once('security.4.php');

		$crypt_password = Security_4::_Mangle_Password($password);
		
		$query = "
			SELECT count(*) AS count
			FROM login
			WHERE login.login = '{$raf_number}' AND login.crypt_password = '{$crypt_password}'
			";
		
		$result = $this->db2->Execute($query);
		Error_2::Error_Test($result, FATAL_DEBUG);
		$row = $result->Fetch_Array();
		
		$security = new Security_4($this->db2, 'agent', $this->schema);

		return (boolean)$row['COUNT'];
	}
	
	function Get_ACH_Funded_Date($raf_trans_id)
	{
		$query = "
			SELECT date_created 
			FROM raf_ach 
			WHERE raf_trans_id = {$raf_trans_id}
		";
		
		$result = $this->db2->Execute($query);
		Error_2::Error_Test($result, FATAL_DEBUG);
		if ($row = $result->Fetch_Array())
		{
			return $row['DATE_CREATED'];
		}
		else
			return NULL;
	}
}

?>
