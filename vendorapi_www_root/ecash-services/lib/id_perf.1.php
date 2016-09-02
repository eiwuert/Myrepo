<?php

require_once "applog.1.php";
require_once "strip_bad_chars.php";
//require_once "id_perf.1.db2.php";
//require_once "datax.1.php";

/**
   @publicsection
   @public
   @brief This class is the parent to to previously seperate classes
   		  (clverify.1.mysql.php and clverify.2.db2.php).  Please instantiate
   		  those classes as this class is meant to be abstract.


 */

class Id_Perf_1
{

	/**
		@publicsection
		@public
		@fn boolean Id_Perf_1($live_mode)
		@brief boolean Id_Perf_1(boolean live_mode)
        	@param boolean live_mode \n 
			TRUE indicates this class should
        		connect to the live (pay) datax site
  	    	@return boolean TRUE
    */
	function Id_Perf_1()
	{
		return TRUE;
	}


	/*
	The best way to get DataX stuff

	@param - IDV block
	$application_id;
	$licensekey;
	$password;
	$trackid;
	$namefirst;
	$namelast;
	$street1;
	$city;
	$state: 	2 - letter state code;
	$zip: 		5 - digit zip code;
	$homephone	10 - digit including area code;
	$email;
	$ipaddress;
	$legalid;
	$legalstate;
	$dobyear - 	4-digit year;
	$dobmonth - 2-digit month;
	$dobday - 	2-digit day;
	$ssn		9-digit SSN complete;
	$namemiddle;
	$street2;
	$workphone;
	$workext;
	$bankname;
	$bankacct;
	$bankaba;
	$banktype;
	$source_id: indicates which type of call to make
	idv-l1 - basic id call;
	perf-l2 - basic performance call
	idv-l3 - combined Id and Performance Call
	fundupd-l1 - Fund Update call;
	$force_check: Make new ID call, no matter what;

	@return


	*/
	function Build_Object($customer_id,
	$trackid,
	$firstname,
	$lastname,
	$street1,
	$city,
	$state,
	$zip,
	$homephone,
	$email,
	$ipaddress,
	$legalid,
	$legalstate,
	$dobyear,
	$dobmonth,
	$dobday,
	$ssn,
	$namemiddle,
	$street2,
	$workphone,
	$workext,
	$bankname,
	$bankacct,
	$bankaba,
	$banktype,
	$property,
	$source_id,
	$force_check = FALSE)
	{
		if (!$force_check)
		{
			$authentication = $this->_Get_Existing_Package($customer_id, $source_id);
			if (Error_2::Error_Test($authentication))
			{
				return $authentication;
			}
		} 
		else
		{
			$authentication = new stdClass;
			$authentication->num_rows = 0;
		}
		//echo "<br>Auth Rows: ".$authentication->num_rows."<br>";
		if ($authentication->num_rows > 0 && strlen($authentication->received_package))
		{
			$temp = $authentication;
			$num_attempts = 1;
			if (!strlen($temp->received_package))
			{
				//echo "Empty Received Pack type: ".$source_id."<br>";
				return $authentication;
			}
			else // Yeah, we have something!!
			{
				// Set the flags
				//echo "Found record <pre>";var_dump($authentication);
				$parsed_xml = $this->_Parse_XML ($authentication->received_package,$source_id);
				//echo "Parsed XML <pre>";var_dump($parsed_xml);
				$display_data = $this->_Get_Flags ($parsed_xml,$source_id);
				//echo "Display <pre>";var_dump($display_data);
				// Set  the flags in the object
				$authentication->flags = $this->_Map_Flags ($display_data, $source_id);
				//print_r('here '. $display_data->score );
				$result = $this->_Update_Score($display_data, $temp, $source_id);
				if(Error_2::Error_Test($result))
				{
					return $result;
				}
			}
		}
		else // Get new authentication record
		{
			//echo "Else - Source ID Get New Auth Rec";
			//var_dump($source_id);var_dump($force_check);
			//echo "<br>";
			if($source_id <> 13)
			{
				return FALSE;
			}
			if (!$force_check) // double check for existing type 13 record
			{
				$authentication = $this->_Get_Existing_Package($customer_id, $source_id);
				//echo "Build_Object Auth type: ".$source_id."<pre>";var_dump($authentication);
				if (Error_2::Error_Test($authentication))
				{
					return $authentication;
				}
				if ($authentication->num_rows > 0);
				{
					return $authentication;
				}
			}
			$insert_id = $this->_Insert($customer_id, $source_id);
			//echo "<br>ID_Perf.1.php Insert <br>";
			//var_dump($customer_id);var_dump($source_id);echo "<br>";
			if(Error_2::Error_Test($insert_id))
			{
				return $insert_id;
			}
			//echo "Create New 2 ";var_dump($insert_id);
			$authentication = $this->_Create_New ($insert_id,
			$customer_id,
			"",
			//$trackid.
			$firstname,
			$lastname,
			$street1,
			$city,
			$state,
			$zip,
			$homephone,
			$email,
			$ipaddress,
			$legalid,
			$legalstate,
			$dobyear,
			$dobmonth,
			$dobday,
			$ssn,
			$namemiddle,
			$street2,
			$workphone,
			$workext,
			$bankname,
			$bankacct,
			$bankaba,
			$banktype,
			$property,
			$source_id);

			if(Error_2::Error_Test($authentication))
			{
				return $authentication;
			}
		}

		return $authentication;
	}





	function Build_Html ($customer_id, $source_id = 1)
	{
		//echo "Build HTML Customer, Source <pre>".$customer_id." ".$source_id;
		$clv = $this->_Fetch_Exist($customer_id, $source_id);
		if ($source_id < 11) // Older CLV and orginal DataX packages
		{

			//echo "Build HTML <pre>";var_dump($clv);
			if($clv == '')
			{
				return $clv;
			}

			// Parse XML
			$sent = $this->_Parse_XML ($clv->SENT_PACKAGE,$source_id);
			$received = $this->_Parse_XML ($clv->RECEIVED_PACKAGE,$source_id);
			//echo "Build_HTML Parsed data Sent: <pre>";var_dump($sent);
			//echo "Recieved: ";var_dump($received);
			// return False if record is empty

			$lvl = 0;

			// Use the buffer for performance? (Per Rodric)
			ob_start();
			echo '<html><body>';
			echo '<table><tr><th colspan="2">CLV Results</th></tr>';
			foreach($received as $val)
			{
				//echo "Row: <pre>";var_export($val);
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
						if(isset($val["attributes"]))
						{
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
						}
						else
						{
							if (!isset($val["value"]))
							{
								$val["value"] = "";
							}
							echo "<tr><td>".$pad.$val["tag"].":</td><td>".$val["value"]."</td></tr>";
						}

						break;

						case 'close':
						$lvl--;
						break;
					}
				}
			}
			echo "<tr><th colspan=\"2\"><br><br>Sent Information</th></tr>";
			foreach($sent as $val)
			{
				if($val["tag"] != "clv-request" && $val["tag"] != "account" && $val["tag"] != "password" && $val["tag"] != "branch" && $val["tag"] != "type" && $val["tag"] != "inquiry")
				{
					if (!isset($val["value"]))
					{
						$val["value"] = "";
					}
					echo "<tr><td>".$val["tag"].":</td><td>".$val["value"]."</td></tr>";
				}
			}
			echo "</table></body></html>";

			$page = ob_get_contents();
			ob_end_clean();

			return $page;
		}
		else // new Datax Format
		{
			if($clv == '')
			{
				return $clv;
			}

			// Parse XML
			$sent = $this->_Parse_XML ($clv->SENT_PACKAGE,$source_id);
			$received = $this->_Parse_XML ($clv->RECEIVED_PACKAGE,$source_id);

			if (!strlen($clv->RECEIVED_PACKAGE))
			{
				return;
			}
			$lvl = 0;

			// Use the buffer for performance? (Per Rodric)
			ob_start();
			echo '<html><body>';
			echo '<table><tr><th colspan="2">DataX Results</th></tr>';
			// format sent packet
			foreach ($sent as $val)
			{
				if(isset($val["QUERY"]["DATA"]))
				{
					echo "<tr><td colspan='2' style='font-weight:bold;'>DataX Sent</td></tr>";
					foreach ($val["QUERY"]["DATA"] as $key => $tag)
					{
						echo "<tr><td>".$key."</td><td>".$tag."<br></td></tr>";
					}
					echo "<tr><td colspan=2>&nbsp;</td></tr>";
				}
			}

			foreach($received as $val)
			{
				if (!is_array($val)) // check for any data
				{
					break;
				}

				if (isset($val["GenerationTime"]))
				{
					$PackTime = $val["GenerationTime"];
				}
				if (isset($val["Response"]["Summary"]) && is_array($val["Response"]["Summary"]))
				{
					echo "<tr><td colspan='2' style='font-weight:bold;'>DataX Summary</td></tr>";
					if (isset($val["GenerationTime"]))
					{
						echo "<tr><td>Generated</td><td>".substr($PackTime,4,2)."/".substr($PackTime,6,2).
						"/".substr($PackTime,0,4)." ".substr($PackTime,8,2).
						":".substr($PackTime,10,2)."</td></tr>";
					}
					foreach ($val["Response"]["Summary"] as  $key => $tag)
					{
						echo "<tr><td>".$key."</td><td>".$tag."<br></td></tr>";
					}
					echo "<tr><td colspan=2>&nbsp;</td></tr>";
				}
				else
				{
					echo "<tr><td colspan='2' >No Summary Data</td></tr>";
				}

				if (isset($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["SSN_Detail"]["SSN_Detail_Set"])) //is there SSN detail?
				{
					echo "<tr><td colspan='2' style='font-weight:bold;'>SSN Data</td></tr>";
					if (isset($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["SSN_Detail"]["SSN_Detail_Set"]["_num"])) // is there more than one
					{
						$knt = $val["Response"]["Checkpoint"]["Checkpoint_Detail"]["SSN_Detail"]["SSN_Detail_Set"]["_num"];
						$it = 0;
						foreach ( $val["Response"]["Checkpoint"]["Checkpoint_Detail"]["SSN_Detail"]["SSN_Detail_Set"]	as $ssr)
						{
							if (is_array($ssr))
							{
								foreach ($ssr as $key => $value)
								{
									echo "<tr><td>".$key."</td><td>".$value."<br></td></tr>";
								}
								echo "<tr><td colspan=2>&nbsp;</td></tr>";
								$it++;
								if ($it == $knt) break ;
							}
						}
					}
					else // only one SSN detail
					{
						if (isset($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["SSN_Detail"]["SSN_Detail_Set"]))
						{
							$ssn = $val["Response"]["Checkpoint"]["Checkpoint_Detail"]["SSN_Detail"]["SSN_Detail_Set"];
							//echo "Row: <pre>";var_dump($ssn);
							foreach ($ssn as $key => $value)
							{
								echo "<tr><td>".$key."</td><td>".$value."<br></td></tr>";
							}
							echo "<tr><td colspan=2>&nbsp;</td></tr>";
						}
					}
				}
				else // no SSN detail
				{
					echo "<tr><td colspan='2' >No SSN Data</td></tr>";
				}

				if (isset($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["Previous_Address"]["Previous_Address_Set"])) //is there PA data?
				{
					echo "<tr><td colspan='2' style='font-weight:bold;'>Previous Address </td></tr>";
					if (isset($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["Previous_Address"]["Previous_Address_Set"]["_num"]))//is there more than one
					{
						$knt = $val["Response"]["Checkpoint"]["Checkpoint_Detail"]["Previous_Address"]["Previous_Address_Set"]["_num"];
						$it = 0;
						foreach ($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["Previous_Address"]["Previous_Address_Set"]	as $par)
						{
							foreach ($par as $key => $value)
							{
								echo "<tr><td>".$key."</td><td>".$value."<br></td></tr>";
							}
							echo "<tr><td colspan=2>&nbsp;</td></tr>";
							$it++;
							if ($it == $knt) break;
						}
					}
					else // only 1 PA record
					{
						if (isset($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["Previous_Address"]["Previous_Address_Set"]))
						{
							$par = $val["Response"]["Checkpoint"]["Checkpoint_Detail"]["Previous_Address"]["Previous_Address_Set"];
							foreach ($par as $key => $value)
							{
								echo "<tr><td>".$key."</td><td>".$value."<br></td></tr>";
							}
							echo "<tr><td colspan=2>&nbsp;</td></tr>";
						}
					}
				}
				else // no PA records
				{
					echo "<tr><td colspan='2' >No Previous Address</td></tr>";
				}

				if (isset($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["DriverLicenseDetail"])) // is there DL data?
				{
					echo "<tr><td colspan='2' style='font-weight:bold;'>Driver License Detail </td></tr>";
					if (isset($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["DriverLicenseDetail"]["_num"])) // is there more than one
					{
						$knt = $val["Response"]["Checkpoint"]["Checkpoint_Detail"]["DriverLicenseDetail"]["_num"];
						$it = 0;
						foreach ( $val["Response"]["Checkpoint"]["Checkpoint_Detail"]["DriverLicenseDetail"] as $dld)
						{
							foreach ($dld as $key => $value)
							{
								echo "<tr><td>".$key."</td><td>".$value."<br></td></tr>";
							}
							echo "<tr><td colspan=2>&nbsp;</td></tr>";
							$it++;
							if ($it == $knt) break;
						}
					}
					else
					{
						if (isset($val["Response"]["Checkpoint"]["Checkpoint_Detail"]["DriverLicenseDetail"]))
						{
							$dld = $val["Response"]["Checkpoint"]["Checkpoint_Detail"]["DriverLicenseDetail"];
							foreach ($dld as $key => $value)
							{
								echo "<tr><td>".$key."</td><td>".$value."<br></td></tr>";
							}
							echo "<tr><td colspan=2>&nbsp;</td></tr>";
						}
					}
				}
				else
				{
					echo "<tr><td colspan='2'>No Driver License Detail</td></tr>";
				}
			}

			/*		echo "<tr><th colspan=\"2\"><br><br>Sent Information</th></tr>";
			foreach($sent as $val)
			{
			if($val["tag"] != "clv-request" && $val["tag"] != "account" && $val["tag"] != "password" && $val["tag"] != "branch" && $val["tag"] != "type" && $val["tag"] != "inquiry")
			{
			if (!isset($val["value"]))
			{
			$val["value"] = "";
			}
			echo "<tr><td>".$val["tag"].":</td><td>".$val["value"]."</td></tr>";
			}
			}
			*/
			echo "</table></body></html>";

			$page = ob_get_contents();
			ob_end_clean();

			return $page;
		}
	}


	/*

	@param
	$authentiction_id:
	$name_last: the last name
	$name_first: the first name
	$street: the street name and address
	$city: the city name
	$state: the 2 letter state abbrivation
	$zip: a 5 digit zip code
	$ssn_part_1: first 3 digits of the ssn
	$ssn_part_2: middle 2 digits of the ssn
	$ssn_part_3: last 4 digits of the ssn
	$name_middle: middle name
	$phone_home: home phone number
	$phone_work: work phone number
	$bank_aba: bank aba
	$bank_account: bank account number
	$dob_year: date of birth year
	$dob_month: date of birth month
	$dob_day: date of birth day
	$legal_id_state: legal state id
	$legal_id_number: legal id number
	$account:
	$password:
	$branch:
	$source_id: This tells which xml document to send. A 1 represnts
	the advanced-idv, a 2 represents idv-basic call with
	the full socal security number, and a 3 represnts
	an idv-basic call with a partial social security number
	(last 4 digits).

	@return

	*/
	function _Create_New ($authentication_id,
	$customer_id,
	$trackid,
	$firstname,
	$lastname,
	$street1,
	$city,
	$state,
	$zip,
	$homephone,
	$email,
	$ipaddress,
	$legalid,
	$legalstate,
	$dobyear,
	$dobmonth,
	$dobday,
	$ssn,
	$namemiddle,
	$street2,
	$workphone,
	$workext,
	$bankname,
	$bankacct,
	$bankaba,
	$banktype,
	$property,
	$source_id=13)
	{

		//echo "<br>Entering _Create_New<br>";var_export($this);

		$authentication = NULL;

		//do CLVerify check`
		$xml = $this->_Request (
		$customer_id,
		$trackid,
		$firstname,
		$lastname,
		$street1,
		$city,
		$state,
		$zip,
		$homephone,
		$email,
		$ipaddress,
		$legalid,
		$legalstate,
		$dobyear,
		$dobmonth,
		$dobday,
		$ssn,
		$namemiddle,
		$street2,
		$workphone,
		$workext,
		$bankname,
		$bankacct,
		$bankaba,
		$banktype,
		$property,
		$source_id=13);
		//echo "<br>After Request <pre>";var_dump($xml);
		if($xml["received"])
		{
			$xml_received = $xml["received"];

			// there are fields in the short form xml that
			// we don't need. we should really rebuild the display_data
			// object to represent the xml levels
			//if ($source_id==2)
			//$xml_received = preg_replace("/<ssn>\d{9}<\/ssn>\n/", "", $xml_received);
		}
		//save xml response and score
		$parsed_xml = $this->_Parse_XML ($xml["received"],$source_id);
		$display_data = $this->_Get_Flags ($parsed_xml,$source_id);

		// echo $xml_received;

		$authentication = new stdclass();
		$authentication->score = empty($display_data->score) ? NULL : $display_data->score;
		$authentication->flags = $this->_Map_Flags ($display_data, $source_id);

		$result = $this->_Update(
		$xml,
		$display_data,
		$authentication,
		$source_id,
		$authentication_id,
		$customer_id
		);

		if(Error_2::Error_Test ($result, TRUE))
		{
			return FALSE;
		}

		return $authentication;
	}




	function _Parse_XML ($xml_response, $source_id)
	{
		// Decide here if this is old CLVERIFY Stuff
		// or new DataX stuff

		//echo "Decision 2005 - ".$source_id."<br>";
		if ($source_id < 11)
		{
			$p = xml_parser_create();
			xml_parser_set_option ($p, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option ($p, XML_OPTION_SKIP_WHITE, 1);
			xml_parse_into_struct ($p, $xml_response, $parsed_xml, $idx);
			xml_parser_free($p);
			//echo "XML CLV Parser Output <pre>";var_dump($parsed_xml);
			return $parsed_xml;
		}
		else
		{
			require_once('minixml/minixml.inc.php');
			$mini = new MiniXMLDoc();
			$mini->fromString($xml_response);
			$parsed_xml = $mini->toArray();
			//echo "XML DATAX Parser Output <pre>";var_dump($parsed_xml);
			return $parsed_xml;
		}
	}

	function _Get_Flags ($parsed_xml,$source_id)
	{

		//echo "Get Flags - decision point ".$source_id."<br>";
		if ($source_id < 11) // Old CLVerify format
		{

			$display_data = new stdclass ();
			$display_data->blackbox_warn = FALSE;
			foreach($parsed_xml as $data)
			{
				switch ($data ["tag"])
				{
					case "score":
					// Want to make sure we only get the first one
					if (!isset ($display_data->{$data ["tag"]}))
					{
						$display_data->{$data["tag"]} = $data["value"];
					}
					break;

					case "warning":
					case "aa-item":
					// Check if there is something (do not care what) in the warning
					if (count ($data ["attributes"]) > 1)
					{
						$display_data->{$data ["tag"]} = "Y";

						// 2004.10.10 tomr: cashline needs this warning flag set but i'm not sure if anyone else uses this;
						// i set this here but if having this set breaks something else, then a new cashline flag should be set
						// and method_data_transport_get_data should be modified to use the new flag;
						$display_data->warning = "Y";

						if (in_array (strtoupper ($data ["attributes"]["code"]), array ('4A', '5A', '5B', '5C', '5D', '5E', '5F', '5H', '5I', '5J','5K', '5M', '5X', '5Z', '6A')))
						{
							$display_data->blackbox_warn = TRUE;
						}

						if (in_array (strtoupper ($data ["attributes"]["code"]), array ('4A', '5A', '5B', '5C', '5D', '5M', '5X')))
						{
							$display_data->id_warn = TRUE;
						}
					}
					break;

					case "ssn-valid-code":
					case "name-ssn-match-code":
					case "ssn":
					case "name":
					case "ofac-result":
					case "approved":
					case "clv-ra":
					case "tran-date":
					case "Error":
					if (isset($data["value"]))
					{
						$display_data->{$data["tag"]} = $data["value"];
					}

					break;
				}
			}

			// Did we get a warning flag?
			if (empty($display_data->warning))
			{
				$display_data->warning = "N";
			}
			return $display_data;
		}
		else //new Datax Format
		{

			//echo "Get DataX Flags: <pre>";var_export($parsed_xml);echo "<br>Source ID: ".$source_id."<br>";
			$display_data = new stdclass ();
			$display_data->blackbox_warn = FALSE;
			//echo "<pre>" . print_r($parsed_xml,true) . "</pre>\n"; //*DEBUG
			if (isset($parsed_xml["DataxResponse"]))
			{
				//echo "-->Datax Response <br>";
				//var_export($parsed_xml["DataxResponse"]["Response"]["Summary"]);

				if (isset($parsed_xml["DataxResponse"]["Response"]["Summary"]) && is_array($parsed_xml["DataxResponse"]["Response"]["Summary"]))
				{
					foreach ($parsed_xml["DataxResponse"]["Response"]["Summary"] as  $key => $tag)
					{
						$display_data->$key = $tag;
					}
					//echo "Display Data: <pre>";var_dump($display_data);
				}
				if (isset($parsed_xml["DataxResponse"]["Response"]["ErrorCode"]))
				{
					//echo "Error: <pre>";var_dump($parsed_xml["DataxResponse"]["Response"]["ErrorCode"]);
					//foreach ($parsed_xml["DataxResponse"]["Response"]["ErrorCode"] as $key => $tag)
					//{
					//echo "Line Error <pre>";var_dump($key);var_dump($tag);
					$display_data->{"ErrorCode"} = $parsed_xml["DataxResponse"]["Response"]["ErrorCode"];
					$display_data->{"ErrorMsg"} = $parsed_xml["DataxResponse"]["Response"]["ErrorMsg"];
					//}
				}
				if (isset($parsed_xml["DataxResponse"]["TrackHash"]))
				{
					$display_data->{"TrackHash"} = $parsed_xml["DataxResponse"]["TrackHash"];
				}
				if (isset($parsed_xml["DataxResponse"]["Response"]["General"]["AuthenticationScoreSet"]["AuthenticationScore"]))
				{
					$display_data->score = $parsed_xml["DataxResponse"]["Response"]["General"]["AuthenticationScoreSet"]["AuthenticationScore"];
				}				
			}
			//echo "DataX Flags <pre>";var_dump($display_data);
			return $display_data;
		}
	}


	/*
	*/
	function _Map_Flags ($display_data, $source_id)
	{
		$flags = new stdClass ();
		//check empty to avoid PHP5 'Notice'
		$flags->ssn_name_match = empty($display_data->{"name-ssn-match-code"}) ? NULL : $display_data->{"name-ssn-match-code"};
		$flags->ssn_valid = empty($display_data->{"ssn-valid-code"}) ? NULL : $display_data->{"ssn-valid-code"};
		$flags->warning_flag = empty($display_data->{"warning"}) ? NULL : $display_data->{"warning"};
		$flags->score = empty($display_data->{"score"}) ? NULL : $display_data->{"score"};
		$flags->ssn = empty($display_data->{"ssn"}) ? NULL : $display_data->{"ssn"};
		$flags->name = empty($display_data->{"name"}) ? NULL : $display_data->{"name"};
		$flags->ecash_warn = empty($display_data->blackbox_warn) ? NULL : $display_data->blackbox_warn;
		$flags->id_warn = empty($display_data->id_warn) ? NULL : $display_data->id_warn;
		// new flags for DataX
		$flags->decision = empty($display_data->{"Decision"}) ? NULL : $display_data->{"Decision"};
		$flags->ofac = empty($display_data->{"Ofac"}) ? NULL : $display_data->{"Ofac"};
		$flags->bankruptcy = empty($display_data->{"Bankruptcy"}) ? NULL : $display_data->{"Bankruptcy"};
		$flags->inquiries = empty($display_data->{"Inquiries"}) ? NULL : $display_data->{"Inquiries"};
		$flags->chargeoffs = empty($display_data->{"ChargeOff"}) ? NULL : $display_data->{"ChargeOff"};
		$flags->decision_bucket = empty($display_data->{"DecisionBucket"}) ? NULL : $display_data->{"DecisionBucket"};
		// track hash
		$flags->track_hash = empty($display_data->{"TrackHash"}) ? NULL : $display_data->{"TrackHash"};

		$flags->tran_date_returned = (!empty($display_data->{"tran-date"}) && strlen($display_data->{"tran-date"}) >= 10) ? TRUE : FALSE;

		if ($source_id == 1 && isset ($display_data->score))
		{
			$flags->blackbox_pass = (
			$display_data->blackbox_warn == FALSE
			&& $flags->ssn_valid != 'N'
			&& $display_data->score > 480
			) ? TRUE :  FALSE;
		}
		elseif ($source_id == 2 && isset ($display_data->ssn))
		{
			$flags->blackbox_pass = ($display_data->ssn == 100
			&& $display_data->name > 49
			&& trim(strtolower($display_data->{"ofac-result"})) == 'passed')
			? TRUE : FALSE;
		}
		else if ($source_id == 3)
		{
			$flags->blackbox_pass = ($display_data->{"score"} > 0
			&& trim(strtolower($display_data->{"ofac-result"})) == 'passed')
			? TRUE : FALSE;
		}
		else if ($source_id == 4)
		{
			$flags->approved = (isset($display_data->{"approved"}) && $display_data->{"approved"} == 'N'?FALSE:TRUE);
			if (!$flags->approved) {
				$flags->clvdenial = (isset($display_data->{"clv-ra"}) && $display_data->{"clv-ra"} == 'Y' ? 'CLV' : 'ID' );
			}
			if (isset($display_data->{"Error"}) && strlen(trim($display_data->{"Error"})) > 0)
			{
				$flags->error = trim($display_data->{"Error"});
			}
		}
		else if ($source_id == 10)
		{
			$flags->approved = (isset($display_data->{"approved"}) && $display_data->{"approved"} == 'N'?FALSE:TRUE);
			if (!$flags->approved) {
				$flags->clvdenial = (isset($display_data->{"clv-ra"}) && $display_data->{"clv-ra"} == 'Y' ? 'CLV' : 'ID' );
			}
			if (isset($display_data->{"Error"}) && strlen(trim($display_data->{"Error"})) > 0)
			{
				$flags->error = trim($display_data->{"Error"});
			}
		}
		else if ($source_id > 10)
		{
			//echo "Map Flags: <pre>";var_dump($display_data); echo "</pre>";
			$flags->approved = (isset($display_data->{"Decision"}) && $display_data->{"Decision"} == 'Y' ? TRUE : FALSE );
			$flags->ofac = (isset($display_data->{"Ofac"}) && $display_data->{"Ofac"} == 1 ? 'PASS' : 'FAIL' );
			$flags->bankruptcy = (isset($display_data->{"Bankruptcy"}) && $display_data->{"Bankruptcy"} > 0 ? TRUE : FALSE );
			$flags->inquiries = (isset($display_data->{"Inquiries"}) && $display_data->{"Inquiries"} > 0 ? $display_data->{"Inquiries"} : 0);
			$flags->chargeoffs = (isset($display_data->{"ChargeOffs"}) && $display_data->{"ChargeOffs"} > 0 ? $display_data->{"ChargeOffs"} : 0);
			$flags->decision_bucket = (isset($display_data->{"DecisionBucket"}) ? $display_data->{"DecisionBucket"} : '');
			$flags->error = (isset($display_data->{"ErrorMsg"}) ? $display_data->{"ErrorMsg"} : '');
		}

		return $flags;
	}


	/*

	@param
	$name_last: the last name
	$name_first: the first name
	$street: the street name and address
	$city: the city name
	$state: the 2 letter state abbrivation
	$zip: a 5 digit zip code
	$ssn_part_1: first 3 digits of the ssn
	$ssn_part_2: middle 2 digits of the ssn
	$ssn_part_3: last 4 digits of the ssn
	$name_middle: middle name
	$phone_home: home phone number
	$phone_work: work phone number
	$bank_aba: bank aba
	$bank_account: bank account number
	$dob_year: date of birth year
	$dob_month: date of birth month
	$dob_day: date of birth day
	$legal_id_state: legal state id
	$legal_id_number: legal id number
	$account:
	$password:
	$branch:
	$source_id: This tells which xml document to send. A 1 represnts
	the advanced-idv, a 2 represents idv-basic call with
	the full socal security number, and a 3 represnts
	an idv-basic call with a partial social security number
	(last 4 digits).

	@return

	*/
	function _Request (
	$customer_id,
	$track_id,
	$firstname,
	$lastname,
	$street1,
	$city,
	$state,
	$zip,
	$homephone,
	$email,
	$ipaddress,
	$legalid,
	$legalstate,
	$dobyear,
	$dobmonth,
	$dobday,
	$ssn,
	$namemiddle,
	$street2,
	$workphone,
	$workext,
	$bankname,
	$bankacct,
	$bankaba,
	$banktype,
	$property,
	$source_id)
	{
		$xml = '';
		//echo "<br>Entering _Request Source: ".$source_id."<br>Property:".$property."<br>";
		// create advanced idv xml doc
		if ($source_id == 1)
		{
			$xml = $this->_Generate_Advanced_IDV_XML_Document($name_last,
			$name_first,
			$street,
			$city,
			$state,
			$zip,
			$ssn_part_1,
			$ssn_part_2,
			$ssn_part_3,
			$name_middle,
			$phone_home,
			$phone_work,
			$bank_aba,
			$bank_account,
			$dob_year,
			$dob_month,
			$dob_day,
			$legal_id_state,
			$legal_id_number,
			$account,
			$password,
			$branch);
		}
		// create basic idv full ssn xml doc
		else if ($source_id == 2)
		{
			$xml = $this->_Generate_Basic_IDV_Full_SSN_XML_Document( $name_last,
			$name_first,
			$name_middle,
			$street,
			$city,
			$state,
			$zip,
			$ssn_part_1,
			$ssn_part_2,
			$ssn_part_3,
			$account,
			$password,
			$branch,
			$dob_year,
			$dob_month,
			$dob_day,
			$phone_home);
		}
		// create basic idv short ssn xml doc
		else if ($source_id == 3)
		{
			$xml = $this->_Generate_Basic_IDV_Short_SSN_XML_Document($name_last,
			$name_first,
			$name_middle,
			$street,
			$city,
			$state,
			$zip,
			$ssn_part_3,
			$account,
			$password,
			$branch,
			$dob_year,
			$dob_month,
			$dob_day,
			$phone_home);
		}
		// this is basically the same as the advanced
		// call except with added fields (approved, clv-ra)
		else if ($source_id == 4)
		{
			//echo "<br>Type 4<br>";
			$xml = $this->_Generate_Advanced_IDV_XML_Document($name_last,
			$name_first,
			$street,
			$city,
			$state,
			$zip,
			$ssn_part_1,
			$ssn_part_2,
			$ssn_part_3,
			$name_middle,
			$phone_home,
			$phone_work,
			$bank_aba,
			$bank_account,
			$dob_year,
			$dob_month,
			$dob_day,
			$legal_id_state,
			$legal_id_number,
			$account,
			$password,
			$branch);
			//echo "<br>XML<br>";
			//var_export($xml);
		}

		// DataX
		else if ($source_id == 13)
		{
			$idv_data = array(
			"licensekey" 	=> '',
			"password" 		=> '',
			"trackid" 		=> '',
			"namefirst"		=> Alt_Strip_Bad($firstname),
			"namelast"		=> Alt_Strip_Bad($lastname),
			"street1"		=> Alt_Strip_Bad($street1),
			"city"			=> Alt_Strip_Bad($city),
			"state"			=> $state,
			"zip"			=> Alt_Strip_Bad($zip),
			"homephone"		=> Alt_Strip_Bad($homephone),
			"email"			=> $email,
			"ipaddress"		=> $ipaddress,
			"legalid"		=> Alt_Strip_Bad($legalid),
			"legalstate"	=> Alt_Strip_Bad($legalstate),
			"dobyear"		=> Alt_Strip_Bad($dobyear),
			"dobmonth"		=> Alt_Strip_Bad($dobmonth),
			"dobday"		=> $dobday,
			"ssn"			=> Alt_Strip_Bad($ssn),
			"namemiddle"	=> Alt_Strip_Bad($namemiddle),
			"street2"		=> Alt_Strip_Bad($street2),
			"workphone"		=> Alt_Strip_Bad($workphone),
			"workext"		=> Alt_Strip_Bad($workext),
			"bankname"		=> Alt_Strip_Bad($bankname),
			"bankacct"		=> Alt_Strip_Bad($bankacct),
			"bankaba"		=> Alt_Strip_Bad($bankaba),
			"banktype"		=> $banktype
			);

			//$xml = trim($xml);

			//$this->_Update_Sent_Package($idv_data, $source_id, $authentication_id);
			//echo "New DataX call, with: <pre>";var_dump($this);
			if (IDVERIFY_MODE)
			{
				$DataxMode = 'LIVE';
			}
			else
			{
				$DataxMode = 'RC';
			}

			//echo "Calling DataX ".$DataxMode." - ". $property."<br>";var_dump($idv_data);
			$Datax = new Data_X();
			$Datax->DataX_Call('idv-l3',$idv_data,$DataxMode,$property);
			$sent_pack = $Datax->Get_Sent_Packet();
			$get_pack = $Datax->Get_Received_Packet();
			//echo "Sent<pre>";var_dump($sent_pack);
			//echo "Get<pre>";var_dump($get_pack);
			$return_val["sent"]=$sent_pack;
			$return_val["received"]=$get_pack;
			return $return_val;
		}
	}


	/*
	This is used to generate the Advanced IDV XML Document

	@param
	$name_last: the last name
	$name_first: the first name
	$street: the street name and address
	$city: the city name
	$state: the 2 letter state abbrivation
	$zip: a 5 digit zip code
	$ssn_part_1: first 3 digits of the ssn
	$ssn_part_2: middle 2 digits of the ssn
	$ssn_part_3: last 4 digits of the ssn
	$name_middle: middle name
	$phone_home: home phone number
	$phone_work: work phone number
	$bank_aba: bank aba
	$bank_account: bank account number
	$dob_year: date of birth year
	$dob_month: date of birth month
	$dob_day: date of birth day
	$legal_id_state: legal state id
	$legal_id_number: legal id number
	$account:
	$password:
	$branch:

	@return
	$xml: This is the advanced-idv xml document
	*/
	function _Generate_Advanced_IDV_XML_Document($name_last,
	$name_first,
	$street,
	$city,
	$state,
	$zip,
	$ssn_part_1,
	$ssn_part_2,
	$ssn_part_3,
	$name_middle,
	$phone_home,
	$phone_work,
	$bank_aba,
	$bank_account,
	$dob_year,
	$dob_month,
	$dob_day,
	$legal_id_state,
	$legal_id_number,
	$account,
	$password,
	$branch)
	{
		$xml ='<?xml version="1.0" encoding="ISO-8859-1" ?>
         <clv-request>
            <account>' . $account . '</account>
            <password>' . $password . '</password>
            <branch>' . $branch . '</branch>
            <version>2</version>
            <type>inquiry</type>
            <search-type>advanced-idv</search-type>
            <scoring>yes</scoring>
            <inquiry>
               <last-name>'.Alt_Strip_Bad($name_last).'</last-name>
               <first-name>'.Alt_Strip_Bad($name_first).'</first-name>
               <street>'.Alt_Strip_Bad($street).'</street>
               <city>'.Alt_Strip_Bad($city).'</city>
               <state>'.Alt_Strip_Bad($state).'</state>
               <zip>'.Alt_Strip_Bad($zip).'</zip>
               <ssn>'.Alt_Strip_Bad($ssn_part_1) . Alt_Strip_Bad($ssn_part_2) . Alt_Strip_Bad($ssn_part_3).'</ssn>
               <middle-name>'. Alt_Strip_Bad($name_middle) .'</middle-name>
               <home-phone>'. Alt_Strip_Bad($phone_home) .'</home-phone>
               <work-phone>'. Alt_Strip_Bad($phone_work).'</work-phone>
               <aba>'. Alt_Strip_Bad($bank_aba) .'</aba>
               <account-number>'. Alt_Strip_Bad($bank_account) . '</account-number>
               <dob>'. str_pad($dob_year, 4, "0", STR_PAD_LEFT)
		. str_pad($dob_month, 2, "0", STR_PAD_LEFT)
		. str_pad($dob_day, 2, "0", STR_PAD_LEFT).
		'</dob>
               <id-state>' . $legal_id_state .'</id-state>
               <id-state-number>'. Alt_Strip_Bad($legal_id_number) .'</id-state-number>
            </inquiry>
         </clv-request>';

		return $xml;
	}



	/*
	This is used to generate the Basic IDV Full SSN XML Document

	@param
	$name_last: last name
	$name_first: first name
	$name_middle: middle name
	$street: street name
	$city: city name
	$state: two letter state abbrivation
	$zip: five digit zip code
	$ssn_part_1: first three digits of an ssn
	$ssn_part_2: middle two digits of an ssn
	$ssn_part_3: last four digits of an ssn
	$account:
	$password:
	$branch:
	$dob_year: date of birth year
	$dob_month: date of birth month
	$dob_day: date of birth day
	$phone_home: home phone number

	@return
	$xml: This is a constructed xml document.
	*/
	function _Generate_Basic_IDV_Full_SSN_XML_Document($name_last,
	$name_first,
	$name_middle,
	$street,
	$city,
	$state,
	$zip,
	$ssn_part_1,
	$ssn_part_2,
	$ssn_part_3,
	$account,
	$password,
	$branch,
	$dob_year,
	$dob_month,
	$dob_day,
	$phone_home)
	{
		$xml ='<?xml version="1.0" encoding="ISO-8859-1" ?>
        <clv-request>
            <account>' . $account . '</account>
			<password>' . $password . '</password>
            <branch>' . $branch . '</branch>
            <version>2</version>
            <type>inquiry</type>
            <search-type>idv-basic</search-type>
            <search-type>ofac</search-type>
            <scoring>yes</scoring>
            <inquiry>
               <last-name>' . Alt_Strip_Bad($name_last) .'</last-name>
               <first-name>' . Alt_Strip_Bad($name_first) .'</first-name>
               <middle-name>'. Alt_Strip_Bad($name_middle) .'</middle-name>
               <street>' . Alt_Strip_Bad($street) .'</street>
               <city>' . Alt_Strip_Bad($city) .'</city>
               <state>' . $state .'</state>
               <zip>' . Alt_Strip_Bad($zip) .'</zip>
               <ssn>' . Alt_Strip_Bad($ssn_part_1) . Alt_Strip_Bad($ssn_part_2) . Alt_Strip_Bad($ssn_part_3) .'</ssn>
               <dob>'. str_pad($dob_year, 4, "0", STR_PAD_LEFT)
		. str_pad($dob_month, 2, "0", STR_PAD_LEFT)
		. str_pad($dob_day, 2, "0", STR_PAD_LEFT). '</dob>
               <home-phone>'. Alt_Strip_Bad($phone_home) .'</home-phone>
            </inquiry>
         </clv-request>';

		return $xml;
	}




	/*
	This is used to generate the Basic IDV short ssn XML document

	@param
	$name_last: last name
	$name_first: first name
	$name_middle: middle name
	$street: street name
	$city: city name
	$state: two letter state abbrivation
	$zip: five digit zip code
	$ssn_part_3: last four digits of an ssn
	$account:
	$password:
	$branch:
	$dob_year: date of birth year
	$dob_month: date of birth month
	$dob_day: date of birth day
	$phone_home: home phone number

	@return
	$xml: This is a constructed xml document.
	*/
	function _Generate_Basic_IDV_Short_SSN_XML_Document($name_last,
	$name_first,
	$name_middle,
	$street,
	$city,
	$state,
	$zip,
	$ssn_part_3,
	$account,
	$password,
	$branch,
	$dob_year,
	$dob_month,
	$dob_day,
	$phone_home)
	{
		$xml ='<?xml version="1.0" encoding="ISO-8859-1" ?>
         <clv-request>
            <account>' . $account . '</account>
            <password>' . $password . '</password>
            <branch>' . $branch . '</branch>
            <version>2</version>
            <type>inquiry</type>
            <search-type>ofac</search-type>
            <search-type>idv-basic</search-type>
            <scoring>yes</scoring>
            <inquiry>
               <last-name>' . Alt_Strip_Bad($name_last) .'</last-name>
               <first-name>' . Alt_Strip_Bad($name_first) .'</first-name>
               <middle-name>'. Alt_Strip_Bad($name_middle) .'</middle-name>
               <street>' . Alt_Strip_Bad($street) .'</street>
               <city>' . Alt_Strip_Bad($city) .'</city>
               <state>' . $state .'</state>
               <zip>' . Alt_Strip_Bad($zip) .'</zip>
               <ssn>' . Alt_Strip_Bad($ssn_part_3) .'</ssn>';

		if ($dob_year)
		{
			$xml .= '<dob>'. str_pad($dob_year, 4, "0", STR_PAD_LEFT)
			. str_pad($dob_month, 2, "0", STR_PAD_LEFT)
			. str_pad($dob_day, 2, "0", STR_PAD_LEFT). '</dob>';
		}

		$xml .= '<home-phone>'. Alt_Strip_Bad($phone_home) .'</home-phone>
            </inquiry>
         </clv-request>';

		return $xml;
	}


	function _DataX_Package( $name_first,
	$name_last,
	$name_middle,
	$home_street,
	$home_city,
	$home_state,
	$home_zip,
	$social_security_number,
	$phone_home,
	$dob_year,
	$dob_month,
	$dob_day,
	$email,
	$ip_address,
	$application_id,
	$legal_id_number,
	$legal_id_state )
	{
		$xml = "
        <ENVELOPEDATA>
                <AUTHENTICATION>
                        <LICENSEKEY>NMS1000</LICENSEKEY>
                        <LOGONPASSWORD>PASSWORD</LOGONPASSWORD>
                </AUTHENTICATION>
                <INQUERYDATA>
                        <NAMEFIRST>" . Alt_Strip_Bad($name_first) . "</NAMEFIRST>
                        <NAMELAST>" . Alt_Strip_Bad($name_last) . "</NAMELAST>
                        <NAMEMIDDLE>" . Alt_Strip_Bad($name_middle) . "</NAMEMIDDLE>
                        <STREET1>" . Alt_Strip_Bad($home_street) . "</STREET1>
                        <STREET2></STREET2>
                        <CITY>" . Alt_Strip_Bad($home_city) . "</CITY>
                        <STATE>" . Alt_Strip_Bad($home_state) . "</STATE>
                        <ZIP>" . Alt_Strip_Bad($home_zip) . "</ZIP>
                        <SSN>" . Alt_Strip_Bad($social_security_number) . "</SSN>
                        <PHONEHOME>" . Alt_Strip_Bad($phone_home) . "</PHONEHOME>
                        <PHONELISTED>T</PHONELISTED>
                        <DOBYEAR>" . str_pad($dob_year, 4, "0", STR_PAD_LEFT) . "</DOBYEAR>
                        <DOBMONTH>" . str_pad($dob_month, 2, "0", STR_PAD_LEFT) . "</DOBMONTH>
                        <DOBDAY>" . str_pad($dob_day, 2, "0", STR_PAD_LEFT) . "</DOBDAY>
                        <EMAIL>{$email}</EMAIL>
                        <IPADDRESS>" . Alt_Strip_Bad($ip_address) . "</IPADDRESS>
                        <DATAXREFNUMBER>" . Alt_Strip_Bad($application_id) . "</DATAXREFNUMBER>
						<DRIVERLICENSENUMBER>" . Alt_Strip_Bad($legal_id_number) . "</DRIVERLICENSENUMBER>
						<DRIVERLICENSESTATE>" . Alt_Strip_Bad($legal_id_state) . "</DRIVERLICENSESTATE>
                </INQUERYDATA>
        </ENVELOPEDATA>";

		return $xml;
	}


	function Clean ($data)
	{
		return trim (str_replace ('\'', '', $data));
	}

}

?>
