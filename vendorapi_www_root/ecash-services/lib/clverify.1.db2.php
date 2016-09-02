<?php

require_once ("/virtualhosts/lib/db2_table_names.php");
require_once '/virtualhosts/lib/applog.1.php';

/*********************
	THIS WAS MOVED TO ECASH 6/23/04 SO IF YOU USE THIS CODE LET KANSAS KNOW
	NOTE PUT HERE 06/23/04 - SW
**********************/
	// A list of common tables

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
	class CLVerify_1_Db2
	{
		function CLVerify_1_Db2 (&$db2_object, $live_mode=FALSE)
		{
			$this->db2 = &$db2_object;

			if ($live_mode)
			{
				$this->clv_port = 443;
				// may be able use 6442 if port is usable (preferred)
				$this->clv_url = "verify.clverify.com";
			}
			else
			{
				$this->clv_port = 6290;
				$this->clv_url = "staging.clverify.com";
			}

			return TRUE;
		}

		function Build_Object ($customer_id, $company_obj, $customer_info=NULL)
		{

			$log = new Applog();
			$log->Write('Build_Object: $customer_id:'.$customer_id);

			$got_package = false;
			$get_package_attempts = 0;

			// loop through entire process to verify that package can be received
			while (!$got_package && ($get_package_attempts < 3))
			{
				// Query DB for existing record
				//echo "going to try and get existing package<br>";
				$authentication = $this->_Get_Existing_Package ($customer_id);

				//print_r($authentication);

				// Verify Existing package
				if ($authentication->num_rows > 0)
				{
					// package exists
					//echo "found record in auth<br>";
					//print_r($temp);

					// Test for a received object
					$num_attempts = 0;

					// check a few times to see if the package is in the db
					while (!strlen ($authentication->package) && ($num_attempts < 3))
					{
						// No received package, wait and try again

						$log->Write('Build_Object: sleep(2)');
						sleep (2);

						// Discard the previous attempt
						unset ($authentication);

						// Query DB for existing record
						$authentication = $this->_Get_Existing_Package ($customer_id);
						//echo "re-attempt to find package in database<br>";

						// Count this attempt
						$num_attempts ++;
					}

					// if package still does not exist, create it and get it
					if (!strlen ($authentication->package))
					{
						//echo "still doesn't exist, create a new one<br>";
						// Create new record
						$this->_Create_New ($customer_id, $company_obj, $customer_info);
						// Query DB for existing record
						$authentication = $this->_Get_Existing_Package ($customer_id);
					}
					else
					{
						// Package is received, continue
						$got_package = true;
					}
				}
				else
				{
					//echo "must be a new one, create a package<br>";
					// Create new record
					$this->_Create_New ($customer_id, $company_obj, $customer_info);
					// Query DB for existing record
					$authentication = $this->_Get_Existing_Package ($customer_id);
				}
			}

			// FINALLY, if got package, return the flags
			if ($got_package)
			{
				//echo "Yeah, we have something!!<br>";
				// Yeah, we have something!!

				// Flags are not set, set them
				$parsed_xml = $this->_Parse_XML ($authentication->package);
				$display_data = $this->_Get_Flags ($parsed_xml);
				$log->Write('Build_Object: xml: '.print_r($authentication->package,TRUE));
				$log->Write('Build_Object: parsed_xml: '.print_r($parsed_xml,TRUE));
				$log->Write('Build_Object: $display_data: '.print_r($display_data,TRUE));

				// Set  the flags in the object
				$authentication->flags = $this->_Map_Flags ($display_data);

				// Update the record (not sure why this is done, but it is taken from the original)
				$query = "
					UPDATE
						authentication
					SET
						score = '" . $display_data->score . "'
					WHERE
						authentication_id = " . $authentication->authentication_id . "
				";
				$result = $this->db2->Execute ($query);
				Error_2::Error_Test ($result, TRUE);
			}

			//echo "<pre> final object returned\n";
			//print_r ($display_data);
			//echo "</pre>";

			$log->Write('Build_Object: exit');
			return $authentication;
		}

		function Build_Html ($customer_id)
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
			$clv = $result->Fetch_Object ();

			// Parse XML
			$sent = $this->_Parse_XML ($clv->SENT_PACKAGE);
			$received = $this->_Parse_XML ($clv->RECEIVED_PACKAGE);

			$lvl = 0;

			// Use the buffer for performance? (Per Rodric)
			ob_start();
			echo '<html><body>';

			//echo '<pre>'; print_r ($received);

			echo '<table><tr><th colspan="2">CLV Results</th></tr>';
			foreach($received as $val)
			{
				if(! in_array ($val["tag"], array("clv-response")))
				{
					switch ($val["type"])
					{
						case 'open':
							$lvl++;
							echo '<tr><td colspan="2" style="font-weight:bold;">'.$val['tag'].'</td></tr>';
							break;

						case 'complete':
							$pad = str_repeat ('&nbsp;', $lvl*2);
							if(is_array ($val["attributes"]) && count($val["attributes"]))
							{
								$att = '';
								foreach ($val["attributes"] as $k => $v)
								{
									$att .= $k.'='.$v.', ';
								}
								$att = substr ($att, 0, -2);
								echo "<tr><td>".$pad.$val["tag"].":</td><td>".$att."</td></tr>";
							}
							else
							{
								echo "<tr><td>".$pad.$val["tag"].":</td><td>".$val["value"]."</td></tr>";
							}

							break;

						case 'close':
							$lvl--;
							echo '<tr><td colspan="2">&nbsp;</td></tr>';
							break;
					}
				}
			}
			echo "<tr><th colspan=\"2\"><br><br>Sent Information</th></tr>";
			foreach($sent as $val)
			{
				if($val["tag"] != "clv-request" && $val["tag"] != "account" && $val["tag"] != "password" && $val["tag"] != "branch" && $val["tag"] != "type" && $val["tag"] != "inquiry")
				{
					echo "<tr><td>".$val["tag"].":</td><td>".$val["value"]."</td></tr>";
				}
			}
			echo "</table></body></html>";

			$page = ob_get_contents();
			ob_end_clean();

			return $page;
		}

		function _Create_New ($customer_id, $company_obj, $customer_info=NULL)
		{
			if ($customer_info == NULL)
			{
				// create query used to build clverify request if needed
				$query = "
					SELECT
						cust.name_first,
						cust.name_middle,
						cust.name_last,
						cust.social_security_number,
						cust.date_birth,
						(
							SELECT
								phone_number
							FROM
								phone
							WHERE
								phone_id = trans.active_home_phone_id
						) as phone_home,
						(
							SELECT
								(
									SELECT
										phone_number
									FROM
										phone ph
									WHERE
										ph.phone_id = emp.active_phone_id
								) as ph_work
							FROM
								employment emp
							WHERE
								emp.employment_id = trans.active_employment_id
						) as phone_work,
						(
							SELECT
								legal_id_number
							FROM
								legal_id lid
							WHERE
								lid.legal_id_id = trans.legal_id_id
								AND lid.legal_id_type_id IN
								(
									SELECT
										legal_id_type_id
									FROM
										legal_id_type lid_type
									WHERE
										name = 'DRIVERS LICENSE'
								)
						) as legal_id_number,
						trans.bank_aba,
						trans.bank_account,
						addr.street,
						addr.city,
						(
							SELECT
								name
							FROM
								state st
							WHERE
								st.state_id = addr.state_id
						) as state,
						addr.zip
					FROM
						transaction trans,
						customer cust,
						address addr
					WHERE
						trans.customer_id = " . $customer_id . "
						AND trans.customer_id = cust.customer_id
						AND trans.active_address_id = addr.address_id
				";

				$result = $this->db2->Execute ($query);
				Error_2::Error_Test ($result, TRUE);
				$customer_info = $result->Fetch_Object ();

				$customer_info->name_last = $customer_info->NAME_LAST;
				$customer_info->name_first = $customer_info->NAME_FIRST;
				$customer_info->street = $customer_info->STREET;
				$customer_info->city = $customer_info->CITY;
				$customer_info->state = $customer_info->STATE;
				$customer_info->zip = $customer_info->ZIP;
				$customer_info->social_security_number = $customer_info->SOCIAL_SECURITY_NUMBER;
				$customer_info->name_middle = $customer_info->NAME_MIDDLE;
				$customer_info->phone_home = $customer_info->PHONE_HOME;
				$customer_info->phone_work = $customer_info->PHONE_WORK;
				$customer_info->bank_aba = $customer_info->BANK_ABA;
				$customer_info->bank_account = $customer_info->BANK_ACCOUNT;
				$customer_info->date_birth = $customer_info->DATE_BIRTH;
				$customer_info->legal_id_number = $customer_info->LEGAL_ID_NUMBER;
			}

			//echo "query for new record\n";

			$msg = '
				<?xml version="1.0" encoding="ISO-8859-1" ?>
				<clv-request>
				<account>' . $company_obj->account .'</account>
				<password>' . $company_obj->password .'</password>
				<branch>' . $company_obj->branch .'</branch>
				<version>2</version>
				<type>inquiry</type>
				<search-type>advanced-idv</search-type>
				<scoring>yes</scoring>
				<inquiry>
					<last-name>' . trim ($customer_info->name_last) . '</last-name>
					<first-name>' . trim ($customer_info->name_first) . '</first-name>
					<street>' . trim ($customer_info->street) . '</street>
					<city>' . trim ($customer_info->city) . '</city>
					<state>' . trim ($customer_info->state) . '</state>
					<zip>' . trim ($customer_info->zip) . '</zip>
					<ssn>' . trim ($customer_info->social_security_number) . '</ssn>';

			if ($customer_info->name_middle)
			{
				$msg .= '
					<middle-name>' . trim ($customer_info->name_middle) . '</middle-name>';
			}
			if ($customer_info->phone_home)
			{
				$msg .= '
					<home-phone>' . trim ($customer_info->phone_home) . '</home-phone>';
			}
			if ($customer_info->phone_work)
			{
				$msg .= '
					<work-phone>' . trim ($customer_info->phone_work) . '</work-phone>';
			}
			if ($customer_info->bank_aba)
			{
				$msg .= '
					<aba>' . trim ($customer_info->bank_aba) . '</aba>';
			}
			if ($customer_info->bank_account)
			{
				$msg .= '
					<account-number>' . trim ($customer_info->bank_account) . '</account-number>';
			}
			if ($customer_info->date_birth)
			{
				$msg .= '
					<dob>' . substr (trim ($customer_info->date_birth), 0, 4) . substr (trim ($customer_info->date_birth), 5, 2) . substr (trim ($customer_info->date_birth), -2) . '</dob>';
			}
			if ($customer_info->legal_id_number)
			{
				$msg .= "
					<id-state>" . trim ($customer_info->state) . "</id-state>
					<id-state-number>" . trim ($customer_info->legal_id_number) . "</id-state-number>";
			}

			$msg .= '
				</inquiry>
				</clv-request>
			';

			$msg = trim ($msg);

		//echo "<pre>message to send\n" . $msg . "</pre>";

			$curl = curl_init ('https://' . $this->clv_url . ':' . $this->clv_port . '/clv/clvxml');

			curl_setopt ($curl, CURLOPT_VERBOSE, 0);
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($curl, CURLOPT_POSTFIELDS, $msg);
			curl_setopt ($curl, CURLOPT_HEADER, 0);
			curl_setopt ($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
			curl_setopt ($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // this line makes it work under https
			curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);

			$result = curl_exec ($curl);
			$xml["sent"] = $msg;
			$xml["received"] = $result;

			if ($xml["received"])
			{
				//save xml response and score
				$parsed_xml = $this->_Parse_XML ($xml ["received"]);
				$display_data = $this->_Get_Flags ($parsed_xml);
			//echo "<pre>data to display\n";
			//print_r ($display_data);
			//echo "</pre>";
				$authentication = new stdclass ();
				$authentication->score = $display_data->score;
				$authentication->flags = $this->_Map_Flags ($display_data);

				// Update the database
				$query = "
					INSERT INTO
						authentication
					(
						date_modified,
						date_created,
						customer_id,
						authentication_source_id,
						sent_package,
						received_package,
						score,
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
								name = 'CLVERIFY'
						),
						'" . trim ($xml["sent"]) . "',
						'" . trim ($xml["received"]) . "',
						'" . $display_data->score . "',
						(
							SELECT
								authentication_type_id
							FROM
								authentication_type
							WHERE
								name = 'ID'
						)
					)
				";

				//echo "inserting new clverify record<br>";

				$result = $this->db2->Execute ($query);
				Error_2::Error_Test ($result, TRUE);
			}

			return $authentication;
		}

		function _Parse_XML ($xml_response)
		{
			$p = xml_parser_create();
			xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
			xml_parse_into_struct ($p, $xml_response, $parsed_xml, $idx);
			xml_parser_free($p);

			return $parsed_xml;
		}

		function _Get_Flags ($parsed_xml)
		{
			$display_data = new stdclass ();
			foreach($parsed_xml as $data)
			{
				switch ($data ["tag"])
				{
					case "score":
						// Want ot make sure we only get the first one
						if (!isset ($display_data->{$data ["tag"]}))
						{
							$display_data->{$data["tag"]} = $data["value"];
						}
					break;

					case "warning":
						// Check if there is something (do not care what) in the warning
						if (count ($data ["attributes"]) > 1)
						{
							$display_data->{$data ["tag"]} = "Y";
						}
					break;

					case "ssn-valid-code":
					case "name-ssn-match-code":
					//default: // Not sure if I should grab all.  To grab all, remove the starting "//"
						// Set the value
						$display_data->{$data["tag"]} = $data["value"];
					break;

				}
			}

			// Did we get a warning flag?
			if (!$display_data->warning)
			{
				$display_data->warning = "N";
			}

			return $display_data;
		}

		function _Map_Flags ($display_data)
		{
			$flags->ssn_name_match = $display_data->{"name-ssn-match-code"};
			$flags->ssn_valid = $display_data->{"ssn-valid-code"};
			$flags->warning_flag = $display_data->{"warning"};
			$flags->score = $display_data->{"score"};

			return $flags;
		}

		function _Get_Existing_Package ($customer_id)
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
							name = 'ID'
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
			Error_2::Error_Test ($result, TRUE);
			$result_object = $result->Fetch_Object ();

			if (empty ($result_object->AUTHENTICATION_ID))
			{
				$existing_package->num_rows = 0;
			}
			else
			{
				$existing_package->num_rows = 1;
				$existing_package->authentication_id = $result_object->AUTHENTICATION_ID;
				$existing_package->package = $result_object->RECEIVED_PACKAGE;
			}

			//echo "fetched object<br>";

			return $existing_package;
		}
	}
?>
