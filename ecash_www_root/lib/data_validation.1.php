<?php
	/**
		@publicsection
		@public
		@brief
			A library to handle data validation and display

		@version
			1.0.1 2003-11-03 - Todd Huish

		@todo
	*/

	require_once 'debug.1.php';
	require_once 'error.2.php';
    //mysql required for ssn validation.
    require_once 'mysql.3.php';

	class Data_Validation
	{
		var $holidays;
		
		function Data_Validation($holidays = array())
		{
			$this->holidays = $holidays;
			
			if( count($this->holidays) )
			{
				//echo "<prE>"; print_r($this->holidays); echo "</pre>";
				
				if( !preg_match('/^(\d{4})(-|\/)(\d{1,2})\2(\d{1,2})$/', $this->holidays[0]) )
				{
					foreach($this->holidays as $holiday_key => $holiday)
					{
						$this->holidays[$holiday_key] = date("Y-m-d", strtotime($holiday) );
					}
				}
			}
			
		}
		
		/**
			@publicsection
			@public
			@fn boolean validate ($field, $param)
			@brief
				A function to normalize form data

			A function to normalize all incoming form data to prepare it for database inserting. Usualy followed by a validation call.

			@param $collected_data array \n This is a single dimensional array holding all data to be normalized

			@return
				Will return the result of the normalization

			@todo
		*/
		function Normalize ($field, $param)
		{
			//print 'Normalize: <pre>$field: \''.$field.'\' $param: '.print_r($param,true).'</pre>';
			switch ($param["type"])
			{
		
				case "email":
					$field = trim(strtoupper($field));
				break;
				
				case "all_digits":
					$field = preg_replace ('/\D+/', '', $field);
				break;
				
				// rewritten by pizza. not beautiful, but not as broken as before   ...after 4 fixes
				case "date":
					// strip everything other than numeric chars and - / 
					$field = preg_replace('/[^0-9-\/]/', '', $field);
					// if the date is passed in a format other than year.month.day  
					if (preg_match('/^(\d+)([\/-])(\d+)\2(\d+)$/', $field, $m))
					{				
						list($a, $b, $c) = array($m[1], $m[3], $m[4]);
						// dates can be Y-m-d or m-d-Y
						// find the year
						list($day, $month, $year) = ($a > $c) ? array($c, $b, $a) : array($b, $a, $c);

						if ($year < 1970)
						{
							$swapyear = $year;
							// FIXME: we need to try to fix the year to match up at the same leap-year
							// pattern as the actual pre-1970 year
							$year = 1970;
						}
						else
						{
							$swapyear = false;
						}
					
						// modified to NOT use DATE as it was converting bad dates
						// to good dates (ie. 9/31/04 became 10/01/04
						// K McMillen - 8/17/47
						// $field = date("Y-m-d", strtotime("$year/$month/$day"));
						
						$field = $year . "/" . $month . "/" . $day;
	
						
						// Make sure the month and day are two digits - if not pad left with 0
						
						$day = str_pad($day,2, "0", STR_PAD_LEFT);
						$month = str_pad($month,2, "0", STR_PAD_LEFT);
						
						//$field = date("Y-m-d", strtotime("$year/$month/$day")); 					
						$field = $year . "-" . $month . "-" . $day;
						
						if ($swapyear)
						{
							$field = $swapyear . substr($field, 4);
						}

					}
					else
					{
						$field = false;	
					}
					
				break;
				
				case "boolean":	
				$field = ($field == "TRUE" ? "TRUE" : ($field == "FALSE" ? "FALSE" : NULL));					
				break;
			
				case 'dollar_amount':
					// remove $, commas, and decimal figure
					$field = preg_replace('/\$|(\.\w*)|,|[a-zA-Z]/', '', $field);
				break;

				case 'real_dollar_amount':
					// above 'dollar_amount' is broken by design and is already relied upon by external code. sigh.
					// only do replacement on a properly formatted dollar amount, because an improperly-formatted
					// one should fail
					// this only handles american syntax for commas and decimal points
					if (preg_match('/^\$?(?:\d{1,3}(?:,\d{3})*|\d+)(?:\.\d{2})?$/', $field))
					{
						$field = floatval(preg_replace('/\$\,/', '', $field));
					}
					break;

				case 'name':
					//  replace everything except for alpha-numeric chars, single quotes,
					// commas, and dashes //  eval return to replace 2+ spaces into a single space
					$field = trim(preg_replace('/[^a-zA-Z0-9\'-]+/', '', preg_replace('/[ ]{2,}/', ' ', $field)));
				break;

				case 'string':
					// only allow up to 1 simultaneous spaces
					$field = trim(strtoupper(preg_replace('/[ ]{2,}/', ' ', $field)));
				break;

				case 'drivers_license':
					// remove everything except for alpha-numeric characters and dashes
					$field = trim(preg_replace('/[^a-zA-Z0-9-]+/', '', stripslashes($field)));
				break;

				case 'text_only':
					//  removes everything other than alpha chars and limits only one simultaneous space
					$field = trim(preg_replace('/[^a-zA-Z]+/', preg_replace('/[ ]{2,}/', ' ', $field)));
				break;

				case 'text_number':
					//  remove everything except for alpha numeric characters
					$field = trim(preg_replace('/[^a-zA-Z0-9]+/', '', $field));
				break;				
				
				case 'gender':
					switch (strtoupper($field))
					{
					case 'F': case 'FEMALE':
						$field = 'F';
						break;
					case 'M': case 'MALE':
						$field = 'M';
						break;
					// clear out any other value, as it would not be considered valid
					default:
						$field = '';
						break;
					}
					break;

				case 'cashline_promo_sub_code':
					$field = trim(preg_replace('/\|/','',$field));
					break;

				case 'cashline_string':
					//remove ' " ` ;
					$field = trim(strtr($field, '"\'`;', '    '));
			}

			return $field;
		}


		/**
			@publicsection
			@public
			@fn boolean validate ($field, $param)
			@brief
				A function to normalize form data

			A function to normalize all incoming form data to prepare it for database inserting. Usualy followed by a validation call.

			@param $collected_data array \n This is a single dimensional array holding all data to be normalized

			@return
				Will return the result of the normalization

			@todo
			
			@comment
				Validate() is not designed well. it should return a boolean value... if more info is necessary, then
				an array should be passed in by reference that gets filled. because 90% of the time we pass back an array
				with one boolean "status" field and php doesn't allow f()[index], instead of doing:

				if (!Data_Validation::Validate(...)) { ... }

				we have to do the extra, unecessary step of

				$a = Data_Validation::Validate(...);
				if (!$a["status"]){ ... }

				it's annoying to have to do twice as many steps as necessary to get a boolean value.

				also, it is impossible to chain the functions together in order to short-circuit the logic, so if we
				have 3 tests all reliant that they all pass, we either end up with big, ugly code or slightly shorter, less
				efficient code. sigh.

				-- pizza

		*/
		function Validate ($field, $param)
		{
			//print '<pre>$field: \''.$field.'\' $param: '.print_r($param,true).'</pre>';
			if(!isset($param["min"]))
			{
				$param["min"] = 1;	
			}

			if(!isset($param["max"])) 
			{
				$param["max"] = 255;
			}
						
			switch($param["type"])
			{
				case "string":
					$len = strlen(trim($field));
					$status["status"] = ($len >= $param["min"] && $len <= $param["max"]);
					break;
				
				case "all_digits":
					// does field meet min/max requirements?
					$status["status"] = preg_match('/^\d{' . $param['min'] . ',' . $param['max'] . '}$/', $field);
					break;
					
				case "all_digits_not_all_zeros":
					// does field meet min/max requirements?
					if ($status["status"] = preg_match('/^\d{' . $param['min'] . ',' . $param['max'] . '}$/', $field))
					{
						// make sure that it is not all zeros
						$status["status"] = (preg_match('/^(0)\1{0,}$/', $field)) ? FALSE : TRUE;
					}
					break;	

				case 'phone_number':
					if ($status["status"] = preg_match('/^\d{' . $param['min'] . ',' . $param['max'] . '}$/', $field))
					{
						// return false if area code or prefix consists of all zeros
						if ( preg_match('/^(0)\1{0,}$/', substr($field, 0,3)) || preg_match('/^(0)\1{0,}$/', substr($field, 3,3)) || preg_match('/^(0)\1{0,}$/', $field) )
							return FALSE;

					}
					break;					
					
				// added validation for work phone extension, minimum length & maximum length, all digits
				case "work_ext":
					if($status["status"] = preg_match('/^\d{' . $param['min'] . ',' . $param['max'] . '}$/', $field))
					{
						return TRUE;
					} else {
						return FALSE;
					}
				break;
			

				case "phone_work_extension":
					if($status["status"] = preg_match('/^\d{' . $param['min'] . ',' . $param['max'] . '}$/', $field))
					{
						return TRUE;
					} else {
						return FALSE;
					}
				break;




				case "date":
					
				
					// if the date is in valid format and checkdate returns true then the date is valid
					// YYYY-MM-DD
					if (preg_match('/^(\d{4})(-|\/)(\d{1,2})\2(\d{1,2})$/', $field, $match))
					{
						list($unused, $year, $delim, $month, $day) = $match;
						// return output from checkdate
						$status["status"] = checkdate($month, $day, $year);
					}
					// MM-DD-YYYY
					else if (preg_match('/^(\d{1,2})(-|\/)(\d{1,2})\2(\d{4})$/', $field, $match))
					{
						list($unused, $month, $delim, $day, $year) = $match;
						// return output from checkdate
						$status["status"] = checkdate($month, $day, $year);
					}
					else
					{
						$status["status"] = FALSE;
					}

				break;
				
				case "weekend_holiday_check":
				
					$day_of_week = date("w", strtotime($field));
					$yyyy_mm_dd_field = ( date("Y-m-d", strtotime($field) ) );
						
					if( $day_of_week == 6 || $day_of_week == 0)
					{
						$status['status'] = FALSE;						
					}
					elseif ( in_array($yyyy_mm_dd_field, $this->holidays) )
					{
						$status['status'] = FALSE;						
					}
					else
					{
						$status['status'] = TRUE;
					}
				break;
				
				case "boolean":	
				if (strtoupper($field) != "TRUE" ) 
					{
						$status["status"] = FALSE;
					} else 
					{
						$status["status"] = TRUE;
					}
				break;

				case "email":	
				if (preg_match('/^[a-zA-Z0-9_-]+(?:\.[a-zA-Z0-9_-]+)*@(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $field))
					{
						list($userName, $mailDomain) = split("@", strtolower($field));

						// check tld - this list must be updated as new tld's are added
						if (preg_match('/\.(ad|ae|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|fi|fj|fk|fm|fo|fr|fx|ga|gb|gov|gd|ge|gf|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nato|nc|ne|net|nf|ng|ni|nl|no|np|nr|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)$/', $mailDomain))
						{
							if (isset($param['ck_max']) && strtolower($param["ck_mx"]) != "n")
							{
								if (FALSE == ($status["status"] = checkdnsrr($mailDomain, 'MX')))
								{
									$status["code"] = "MXFAILED";
								}
							}
							else
							{
								$status["status"] = TRUE;
							}
						}
						else
						{
							$status["status"] = FALSE;
							$status["code"] = "TLDFAILED";
						}
					}
					else
					{
						$status["status"] = FALSE;
						$status["code"] = "FORMATFAILED";
					}
				break;

				case "address";
					// turned off by rsk 3/24/05 webadmin ticket #6747
					$on = 0;
					if (isset($param['validation_method']) && $param["validation_method"] == 'satori' && $on == 1)
					{
						//prpc client for sartori call -- moved here to decrease overhead.
						require_once 'prpc/client.php';
						
						//check address through satori
						include_once("satori.1.php");
						$satori = new Satori_1();
						
						$request_object = new stdClass();
						$request_object->request_id = 123;
						$request_object->organization = "";
						$request_object->address_1 = $field["address_1"];
						$request_object->address_2 = "";
						$request_object->city = $field["city"];
						$request_object->state = $field["state"];
						$request_object->zip = $field["zip"];
						$request_object->user_defined_1 = "";
						$request_object->user_defined_2 = "";


						$satori_result = $satori->Validate_Address($request_object, Debug_1::Trace_Code(__FILE__, __LINE__));
					
						if( is_a($satori_result, "Error_2") or $satori_result == false )
						{
							$status["status"] =  -1;
						}
						elseif($satori_result->valid == "TRUE") /* data is good */
						{
							$status["status"] =  1;
							//set satori information
							$status["data"]["address_1"] = $satori_result->address_1;
							$status["data"]["address_2"] = $satori_result->address_2;
							$status["data"]["city"] = $satori_result->city;
							$status["data"]["state"] = $satori_result->state;
							$status["data"]["zip"] = $satori_result->zip;
						}
						else /* address information is bad */
						{
							$status["status"] =  0;
							$status["error_message"] = $satori_result->error_string;
							//set satori information
							$status["data"]["address_1"] = $satori_result->address_1;
							$status["data"]["address_2"] = $satori_result->address_2;
							$status["data"]["city"] = $satori_result->city;
							$status["data"]["state"] = $satori_result->state;
							$status["data"]["zip"] = $satori_result->zip;
						}						
					}
					else
					{
						$status["status"] = 1;
						$status["data"]["address_1"] = $field["address_1"];
						$status["data"]["address_2"] = $field["address_2"];
						$status["data"]["city"] = $field["city"];
						$status["data"]["state"] = $field["state"];
						$status["data"]["zip"] = $field["zip"];
					}

					
				break;
				
				case "routing_number":
					$status["status"] = $this->Validate_Routing_Number ($field);
				break;
				
				case "checkbox":
					$status["status"] = $field=="YES" ? TRUE : FALSE;
				break;
				
			
				// added basic date tests - 9/16/04	
				case "over_18":
// 				The basic date validation already existed as a hack in OLP ( Check_and_Collect() function )
//					if (preg_match('/^(\d{4})(-|\/)(\d{1,2})\2(\d{1,2})$/', $field, $match))
//					{
//						list($unused, $year, $delim, $month, $day) = $match;
//						// return output from checkdate
//						$status["status"] = checkdate($month, $day, $year);
//						break;
//					}
					$status['status'] = (strtotime($field) <= strtotime("-18 years"));
					break;

				case "gender":
					$status["status"] = ($field == "F" || $field == "M");
					break;
				
				case "name":
					// per NMS request, do not allow name to be submitted with a ampersand 
					$len = strlen(trim($field)); 
					$status["status"] = (ereg('&', $field) || $len < $param["min"] || $len > $param["max"]) ? FALSE : TRUE;
					break;	
					
				case "compare":
					$status["status"] = ($field[0] == $field[1]) ? TRUE : FALSE;
					break;

				case "zip_code":
					$status["status"] = (false !== preg_match('/^\d{5}(-\d{4})?$/', $field));
					break;

				case "real_dollar_amount":
					// if you use Normalize just do an is_numeric()
					$stats["status"] = (false !== preg_match('/^\$?(?:\d{1,3}(?:,\d{3})*|\d+)(?:\.\d{2})?$/', $field));
					break;

				case "bank_account_type":
					$status["status"] = ($field == "CHECKING" || $field == "SAVINGS");
					break;

//				case "conditional":
//					$variable = $field[0];
//					$varvalue = $field[1];
//					$condition = $field[2];
//					$condvalue = $field[3];
//
//					switch ($variable) {
//						case 'cali_agree':
//							$status["status"] = (strtolower($condvalue)=='ca'?(strtolower($varvalue)=='agree'||strtolower($varvalue)=='disagree'?TRUE:FALSE):TRUE);
//							break;
//					}
//					break;

                case "ssn-validation":
                    $sql    = new MySQL_3();
                    $user   = "root";
                    $pass   = "sellingsource";
                    $db     = "temp";
                    $table  = "ssn_validation";

                    if(strlen($field) != 9 || !(ctype_digit($field)))
                    {
                        $status["status"] = FALSE;
                    }
                    else
                    {
                    Error_2::Error_Test ($sql->Connect(NULL, $host, $user, $pass, Debug_1::Trace_Code(__FILE__, __LINE__)), TRUE);
                    
                    $ssn_area  = substr($field,0,3);
                    $ssn_group = substr($field,3,2);

                    $query = "
                        SELECT
                            ssn_group
                        FROM
                            ssn_validation
                        WHERE
                            ssn_area = ".$ssn_area."
                        ";

                    $results = $sql->Query($db, $query);
                    Error_2::Error_Test($results, TRUE);
                    
                    if($row = $sql->Fetch_Array_Row($results))
                    {
                        if(!(is_int($row["ssn_group"] / 2)) && $row["ssn_group"] <= 9)
                        {
                            if(!(is_int($ssn_group / 2)) && $ssn_group <= $row["ssn_group"])
                            {
                                $status["status"] = TRUE;
                            } else {
                                $status["status"] = FALSE;
                            }
                        }
                        elseif(is_int($row["ssn_group"] / 2) && $row["ssn_group"] >= 10)
                        {
                            if(!(is_int($ssn_group / 2)) && $ssn_group <= 9)
                            {
                                $status["status"] = TRUE;
                            }
                            elseif(is_int($ssn_group / 2) && $ssn_group >= 10 && $ssn_group <= $row["ssn_group"])
                            {
                                $status["status"] = TRUE;
                            }
                            else
                            {
                                $status["status"] = FALSE;
                            }
                        }
                        elseif(is_int($row["ssn_group"] / 2) && $row["ssn_group"] <= 8)
                        {
                            if(!(is_int($ssn_group / 2)) && $ssn_group <= 9)
                            {
                                $status["status"] = TRUE;
                            }
                            elseif(is_int($ssn_group / 2) && $ssn_group >= 10)
                            {
                                $status["status"] = TRUE;
                            }
                            elseif(is_int($ssn_group / 2) && $ssn_group <= $row["ssn_group"])
                            {
                                $status["status"] = TRUE;
                            }
                            else
                            {
                                $status["status"] = FALSE;
                            }
                        }
                        elseif(!(is_int($row["ssn_group"] / 2)) && $row["ssn_group"] >= 11)
                        {
                            if(is_int($ssn_group / 2))
                            {
                                $status["status"] = TRUE;
                            }
                            elseif($ssn_group <= $row["ssn_group"])
                            {
                                $status["status"] = TRUE;
                            }
                            else
                            {
                                $status["status"] = FALSE;
                            }
                        }
                        else
                        {
                            $status["status"] = FALSE;
                        }
                    }
                    }
                    break;

			}
			
			return $status;
		}

		/**
			@publicsection
			@public
			@fn boolean validate ($field, $type)
			@brief
				A function to normalize form data

			A function to normalize all incoming form data to prepare it for database inserting. Usualy followed by a validation call.

			@param $collected_data array \n This is a single dimensional array holding all data to be normalized

			@return
				Will return the result of the normalization

			@todo
		*/
		
		function Display ($field, $type)
		{
			switch ($type)
			{
				case "phone":
					$field = "(".substr($field,0,3).")".substr($field,3,3)."-".substr($field,6,4);
				break;

				case "ssn":
					$field = substr($field,0,3)."-".substr($field,3,2)."-".substr($field,5,4);
				break;

				case "date":
					$field = substr($field, 5,2)."/".substr($field, -2)."/".substr($field,0,4);
				break;

				case "string":
					$field = ucwords($field);
				break;
				case "phone2":
					$field = substr ($field, 0, 3)."-".substr ($field, 3, 3)."-".substr ($field,6);
					break;
				
				case "money":
					$field = sprintf ("%0.2f", $field);
					break;
					
				case "upper case":
					$field = strtoupper ($field);
					break;

				case "email":
				case "lower case":
					$field = strtolower ($field);
					break;

				case "smart case":
					$field = ucwords (strtolower ($field));
					break;
			}
			return $field;
		}
		
		function Verify_Mod10 ($routing_number)
		{
			if (strlen ($routing_number) != 9 || preg_match ("/\D/", $routing_number) || $routing_number == 0)
			{
				return FALSE;
			}
	
			$start_check = substr ($routing_number, 0, 2);
			if (($start_check > 12 && $start_check < 21) || ($start_check > 32 && $start_check < 61) || ($start_check > 72))
			{
				return FALSE;
			}
	
			# FIXME: we don't need  to split into an array, we can access chars with $str[n]
			$digit = preg_split ("//", $routing_number, -1, PREG_SPLIT_NO_EMPTY);
	
			$total =
				($digit[0] * 3) + ($digit[1] * 7) + $digit[2] + ($digit[3] * 3) +
				($digit[4] * 7) + $digit[5] + ($digit[6] * 3) + ($digit[7] * 7);
	
			$remainder = $total % 10;
	
			$round = $remainder > 0 ? (10 - $remainder) : 0;
	
			return $digit[8] != $round ? FALSE : TRUE;
		}
		
		function Validate_Routing_Number ($routing_number)
		{
			
			include_once("aba.1.php");
			$aba = new ABA_1();
			$res = $aba->Verify($_SESSION["license_key"], $_SESSION["application_id"], $routing_number);

			if(isset($res["valid"]))
			{
				if($res["valid"])
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
			else
			{
				return $this->Verify_Mod10 ($routing_number);
			}
		}		
		
	}
?>
