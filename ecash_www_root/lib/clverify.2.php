<?php

require_once "applog.1.php";
require_once "strip_bad_chars.php";

/**
   @publicsection
   @public
   @brief This class is the parent to to previously seperate classes
   		  (clverify.1.mysql.php and clverify.2.db2.php).  Please instantiate
   		  those classes as this class is meant to be abstract.

   Jeebus... I created this file to hopefully avoid having 6 (different/wrong)
   implementations of the clverify code in different applications.  Changes
   common to CLVerify should happen here.  Changes for database specific things
   (MySQL/DB2) should be done in their respective sub-class.
   JRF

 */

class CLVerify_2
{
	var $clv_port;
	var $clv_url;
	
	/**
		@publicsection
		@public
		@fn boolean CLVerify_2($live_mode)
		@brief boolean CLVerify_2(boolean live_mode)
        	@param boolean live_mode \n 
			TRUE indicates this class should
        		connect to the live (pay) clverify site
  	    	@return boolean TRUE
    */
	function CLVerify_2($live_mode = FALSE)
	{
			if ($live_mode)
			{
				$this->clv_port = 443;
				// may be able use 6442 if port is usable (preferred)
				$this->clv_url = "verify.clverify.com";
				
				// datax server vars
				$this->datax_port = NULL;
				$this->datax_url = "http://datax.verihub.com/xmlWallet/";
			}
			else
			{
				$this->clv_port = 6290;
				$this->clv_url = "staging.clverify.com";
				
				// datax server vars
				$this->datax_port = NULL;
				$this->datax_url = "http://verihub.com/rcdatax/index2.php";
			}

			return TRUE;
	}


   /*
      The best way to get CLV stuff

      @param
         $application_id:
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
	$email:
	$force_check: Flag to force recheck from CLV or DataX, default to FALSE

      @return

   */
   function Build_Object ($application_id,
                           $name_last,
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
     			   		$branch,
                           $source_id = 1,
                           $email = NULL,
			   $force_check = FALSE)
	{
		if (!$force_check)
		{
			$authentication = $this->_Get_Existing_Package ($application_id, $source_id);
			if(Error_2::Error_Test($authentication))
			{
				return $result;
			}
		}
		else
		{
			$authentication = new stdClass;
			$authentication->num_rows = 0;
		}

		//echo "<br>Authentication <pre>";var_export($authentication);
		//echo "<br>This <pre>";var_export($this);
		// Test if we found a result
		if ($authentication->num_rows > 0)
		{
			// Put in my object
			$temp = $authentication;

			// Test for a recieved object
			$num_attempts = 1;

			// Has the received package been updated yet?
			while (!strlen ($temp->received_package) && $num_attempts < 5)
			{
				// No received package, wait and try again
				sleep (3);

				// Discard the previous attempt
				unset ($temp);
				unset ($authentication);

				// Get new data from the db
				$authentication = $this->_Get_Existing_Package ($application_id, $source_id);
				if(Error_2::Error_Test($authentication))
				{
					return $authentication;
				}

				// Put in my object
				$temp = $authentication;

				// Count this attempt
				//print_r($temp);
				$num_attempts ++;
			}

			// Either we have something or we do not, test one more time to make sure
			if (!strlen ($temp->received_package))
			{
            			// we have nothing, try to build a new one again
				//echo "<br>Go to _Create_New";
            			$authentication = $this->_Create_New ($authentication->authentication_id,
											   $application_id,
								                           $name_last,
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
                           								   $branch,
								                           $source_id,
								                           $email);
				if(Error_2::Error_Test($authentication))
				{
					return $authentication;
				}
			}
			else // Yeah, we have something!!
			{
				// Set the flags
				$parsed_xml = $this->_Parse_XML ($authentication->received_package);
				$display_data = $this->_Get_Flags ($parsed_xml);

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
		else
		{
			$insert_id = $this->_Insert($application_id, $source_id);
			if(Error_2::Error_Test($insert_id))
			{
				return $insert_id;
			}

         		$authentication = $this->_Create_New ($insert_id,
								   $application_id,
								   $name_last,
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
                          					   $branch,
							           $source_id,
							           $email);

			if(Error_2::Error_Test($authentication))
			{
				return $authentication;
			}
		}

		return $authentication;
	}





	function Build_Html ($application_id, $source_id = 1)
	{
		$clv = $this->_Fetch_Exist($application_id, $source_id);

		
		if($clv == '')
		{
			return $clv;
		}
		
		// Parse XML
		$sent = $this->_Parse_XML ($clv->SENT_PACKAGE);
		$received = $this->_Parse_XML ($clv->RECEIVED_PACKAGE);



		$lvl = 0;

		// Use the buffer for performance? (Per Rodric)
		ob_start();
		echo '<html><body>';
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
			   $application_id,
                           $name_last,
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
			   $branch,
                           $source_id,
                           $email)
   {

	
	//echo "<br>Entering _Create_New<br>";var_export($this);
	
   	$authentication = NULL;

	//do CLVerify check
      $xml = $this->_Request ($application_id,
			      $name_last,
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
			      $branch,
                              $source_id,
                              $authentication_id,
                              $email);
	//echo "<br>After Request <pre>";var_dump($xml);
		if($xml["received"])
		{
         		$xml_received = $xml["received"];

			// there are fields in the short form xml that
			// we don't need. we should really rebuild the display_data
			// object to represent the xml levels
			if ($source_id==2)
				$xml_received = preg_replace("/<ssn>\d{9}<\/ssn>\n/", "", $xml_received);

         //save xml response and score
         $parsed_xml = $this->_Parse_XML ($xml_received);
         $display_data = $this->_Get_Flags ($parsed_xml);

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
									$application_id
								);

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

					if (in_array (strtoupper ($data ["attributes"]["code"]), array ('4A', '5A', '5B', '5C', '5D', '5E', '5F', '5H', '5I', '5J', '5K', '5M', '5X', '5Z', '6A')))
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
                  $display_data->{$data["tag"]} = $data["value"];

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
   function _Request ($application_id,
	  	     		 $name_last,
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
		     		 $branch,
                     $source_id, 
                     $authentication_id = NULL,
                     $email)
   {
      $xml = '';
	  //echo "<br>Entering _Request Source: ".$source_id."<br>";
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
      else if ($source_id == 10)
      {
         $xml = $this->_DataX_Package( $name_first, 
										$name_last, 
										$name_middle, 
										$street, 
										$city, 
										$state, 
										$zip,
										$ssn_part_1.$ssn_part_2.$ssn_part_3, 
										$phone_home, 
										$dob_year, 
										$dob_month, 
										$dob_day, 
										$email, 
										$_SERVER['REMOTE_ADDR'], 
										$application_id, 
										$legal_id_number, 
										$legal_id_state);
										
      }
      
		$xml = trim($xml);
	
		$this->_Update_Sent_Package($xml, $source_id, $authentication_id);
     	
		$try_limit = 2;
		$tries = 0;
		$result = array();
		while(empty($result) && $tries < $try_limit)
		{
			$target_url = ($source_id != 10) ? 'https://'.$this->clv_url.':'.$this->clv_port.'/clv/clvxml' : $this->datax_url;
			$curl = curl_init($target_url);
	
			curl_setopt($curl, CURLOPT_VERBOSE, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
			curl_setopt($curl, CURLOPT_TIMEOUT, 15);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // this line makes it work under https
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	
			list($x, $y) = explode(" ", microtime());
			$start = (float)$y + (float)$x;
	
			$result = curl_exec($curl);
			
			list($x, $y) = explode(" ", microtime());
			$stop = (float)$y + (float)$x;
	
			if (empty($result))
			{
				$ssn_assembled_text = $ssn_part_1."-".$ssn_part_2."-".$ssn_part_3;
				$msg = array();
				$msg[] = "Application ID: ".$application_id;
				$msg[] = "Source ID: ".$source_id;
				$msg[] = "Authentication ID: ".$authentication_id;
				$msg[] = "Name: ".$name_first." ".$name_last;
				$msg[] = "SSN: ".$ssn_assembled_text;
				$msg[] = "Exec Time: ".($stop-$start)." seconds";

				@mail (	"nick.white@thesellingsource.com; andy.roberts@thesellingsource.com",
							"CLVerify_2::_Request - No Received XML - ({$application_id}, {$ssn_assembled_text})",
							"XML not received from {$target_url}\n\n".implode("\n", $msg),
							"From:error_checking@thesellingsource.com\r\n"
				);
			}
			
			++$tries;
		}
		
		$return_val["sent"] = $xml;
		$return_val["received"] = $result;
		return $return_val;
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
                        <LOGONPASSWORD>MUNKEY1</LOGONPASSWORD>
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
