<?php

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

class CLVerify_1
{
	var $clv_port;
	var $clv_url;

	/**
		@publicsection
		@public
		@fn boolean CLVerify_1($live_mode)
		@brief boolean CLVerify_1(boolean live_mode)
        @param boolean live_mode \n TRUE indicates this class should
        							connect to the live (pay) clverify site
  	    @return boolean TRUE
    */	
	function CLVerify_1($live_mode = FALSE)
	{
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
                           $source_id = 1)
	{
		$authentication = $this->_Get_Existing_Package ($application_id, $source_id);
		if(Error_2::Error_Test($authentication))
		{
			return $result;
		}

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
				//echo "Sleeping {$num_attempts}\n";
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
				//echo "Creating new\n";
            // we have nothing, try to build a new one again
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
								                           $source_id);

				if(Error_2::Error_Test($authentication))
				{
					return $authentication;
				}
			}
			else // Yeah, we have something!!
			{
				//echo "We have something\n";

				// Set the flags
				$parsed_xml = $this->_Parse_XML ($authentication->received_package);
				$display_data = $this->_Get_Flags ($parsed_xml);

				// Set  the flags in the object
				$authentication->flags = $this->_Map_Flags ($display_data, $source_id);

				//echo "Updating score\n";
				$result = $this->_Update_Score($display_data, $temp, $source_id);
				if(Error_2::Error_Test($result))
				{
					return $result;
				}
			}
		}
		else
		{
			//echo "Inserting new record...";
			$insert_id = $this->_Insert($application_id, $source_id);
			//echo "done\n";
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
							                           $source_id);
			//echo "Done creating new\n";
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
                           $source_id)
   {
      // Build a new entry in the database for this customer
	   //echo "in Create_New\n";
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
                              $source_id);                              
                              
      if($xml["received"])
      {
         if ($source_id == 2 && preg_match ('/(<score-items>.*<\/score-items>)/is', $xml["received"], $m))
         {
            $xml_received = $m[1];
         }
			else if ($source_id == 3)
			{

			}
         else
         {
            $xml_received = $xml["received"];
         }

         //save xml response and score
         $parsed_xml = $this->_Parse_XML ($xml_received);
         $display_data = $this->_Get_Flags ($parsed_xml);

         $authentication = new stdclass();
         $authentication->score = $display_data->score;
         $authentication->flags = $this->_Map_Flags ($display_data, $source_id);

			$result = $this->_Update($xml,
											$display_data,
											$authentication,
											$source_id,
											$authentication_id,
											$application_id);

         Error_2::Error_Test ($result, TRUE);
      } 
      
      

	  //echo "Done in Create_New\n";
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
                  if (in_array (strtoupper ($data ["attributes"]["code"]), array ('4A', '5A', '5B', '5C', '5D', '5
E', '5F', '5H', '5I', '5J', '5K', '5M', '5X', '5Z')))
                  {
                     $display_data->blackbox_warn = TRUE;
                  }
               }
            break;

            case "ssn-valid-code":
            case "name-ssn-match-code":
            case "ssn":
            case "name":
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





   function _Map_Flags ($display_data, $source_id)
   {
      $flags = new stdClass ();
      $flags->ssn_name_match = $display_data->{"name-ssn-match-code"};
      $flags->ssn_valid = $display_data->{"ssn-valid-code"};
      $flags->warning_flag = $display_data->{"warning"};


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
         $flags->blackbox_pass = ($display_data->ssn == 100 && $display_data->name > 49) ? TRUE : FALSE;
      }
      else if ($source_id == 3)
      {
         $flags->blackbox_pass = ($display_data->score == 100 && $display_data->name > 49) ? TRUE : FALSE;
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
                     $authentication_id = NULL)
   {
      $xml = '';

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
         $xml = $this->_Generate_Basic_IDV_Full_SSN_XML_Document($name_last,
                                                                  $name_first,
                                                                  $street,
                                                                  $city,
                                                                  $state,
                                                                  $zip,
                                                                  $ssn_part_1,
                                                                  $ssn_part_2,
                                                                  $ssn_part_3,
																						$account,
																						$password,
																						$branch);
      }
      // create basic idv short ssn xml doc
      else if ($source_id == 3)
      {
         $xml = $this->_Generate_Basic_IDV_Short_SSN_XML_Document($name_last,
                                                                  $name_first,
                                                                  $street,
                                                                  $city,
                                                                  $state,
                                                                  $zip,
                                                                  $ssn_part_3,
																						$account,
																						$password,
																						$branch);
      }

      $xml = trim($xml);
      
	  //echo "curl_init...";
      $curl = curl_init('https://'.$this->clv_url.':'.$this->clv_port.'/clv/clvxml');
	  //echo "done\n";
	  
      curl_setopt($curl, CURLOPT_VERBOSE, 0);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
      curl_setopt($curl, CURLOPT_HEADER, 0);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
      curl_setopt($curl, CURLOPT_TIMEOUT, 30);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // this line makes it work under https
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

	  //echo "curl_exec...";
      $result = curl_exec($curl);
      
      // insert the sent package into the data base
      $this->_Update_Sent_Package($xml, $source_id, $authentication_id);
      
	  //echo "done\n";
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
					<last-name>'.$name_last.'</last-name>
               <first-name>'.$name_first.'</first-name>
               <street>'.$street.'</street>
               <city>'.$city.'</city>
               <state>'.$state.'</state>
               <zip>'.$zip.'</zip>
               <ssn>'.$ssn_part_1 . $ssn_part_2 . $ssn_part_3.'</ssn>
               <middle-name>'. $name_middle .'</middle-name>
               <home-phone>'. $phone_home .'</home-phone>
               <work-phone>'. $phone_work.'</work-phone>
               <aba>'. $bank_aba .'</aba>
               <account-number>'. $bank_account . '</account-number>
               <dob>'. str_pad($dob_year, 4, "0", STR_PAD_LEFT)
							. str_pad($dob_month, 2, "0", STR_PAD_LEFT)
							. str_pad($dob_day, 2, "0", STR_PAD_LEFT).
					'</dob>
               <id-state>' . $legal_id_state .'</id-state>
               <id-state-number>'. $legal_id_number .'</id-state-number>
            </inquiry>
         </clv-request>';

      return $xml;
   }


   /*
      This is used to generate the Basic IDV Full SSN XML Document

      @param
         $name_last: last name
         $name_first: first name
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

      @return
         $xml: This is a constructed xml document.
   */
   function _Generate_Basic_IDV_Full_SSN_XML_Document($name_last,
                                                      $name_first,
                                                      $street,
                                                      $city,
                                                      $state,
                                                      $zip,
                                                      $ssn_part_1,
                                                      $ssn_part_2,
                                                      $ssn_part_3,
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
            <search-type>idv-basic</search-type>
            <scoring>yes</scoring>
            <inquiry>
               <last-name>' .$name_last .'</last-name>
               <first-name>' .$name_first .'</first-name>
               <street>' .$street .'</street>
               <city>' .$city .'</city>
               <state>' .$state .'</state>
               <zip>' .$zip .'</zip>
               <ssn>' . $ssn_part_1 . $ssn_part_2 . $ssn_part_3 .'</ssn>
            </inquiry>
         </clv-request>';

      return $xml;
   }




	/*
		This is used to generate the Basic IDV short ssn XML document

      @param
         $name_last: last name
         $name_first: first name
         $street: street name
         $city: city name
         $state: two letter state abbrivation
         $zip: five digit zip code
         $ssn_part_3: last four digits of an ssn
			$account:
			$password:
			$branch:

      @return
         $xml: This is a constructed xml document.
   */
	function _Generate_Basic_IDV_Short_SSN_XML_Document($name_last,
																		$name_first,
                                                      $street,
                                                      $city,
                                                      $state,
                                                      $zip,
                                                      $ssn_part_3,
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
            <search-type>idv-basic</search-type>
            <scoring>yes</scoring>
            <inquiry>
               <last-name>' .$name_last .'</last-name>
               <first-name>' .$name_first .'</first-name>
               <street>' .$street .'</street>
               <city>' .$city .'</city>
               <state>' .$state .'</state>
               <zip>' .$zip .'</zip>
               <ssn>' .$ssn_part_3 .'</ssn>
            </inquiry>
         </clv-request>';

      return $xml;
   }



	function Clean ($data)
	{
		return trim (str_replace ('\'', '', $data));
	}

}

?>
