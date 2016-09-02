<?PHP
// *************************** PHP 5 ONLY **********************************

/**
	@publicsection
	@public
	@brief
		******* PHP 5 ONLY - Handles data validation, normalizaton and page flow
	@version
		2.0.0 2004-11-22 - A class to handle data validation, normalization and page flow
	@author:
		Jeff Calene - version 2.0.0
	@todo


*/

class Data_Validation
{
	protected $site_type_id;		// Int
	protected $page_ordering_on;	// Bool
	protected $current_page;		// String
	protected $data_array;			// Array
	protected $rtn_original_data; 	// Bool - return the original data back in the response?

	public $aba_call = array( "processed" => FALSE ); 	// Array - data returned from lib5/aba.1.php

	// commonly used email domains for which
	// MX lookups will not be performed
	protected static $email_domains = array (
	  'excite.com', 'juno.com', 'optonline.net', 'netscape.net', 'us.army.mil', 'adelphia.net', 'netzero.com',
	  'gmail.com', 'charter.net', 'peoplepc.com', 'cox.net', 'verizon.net', 'bellsouth.net', 'earthlink.net',
	  'comcast.net', 'sbcglobal.net', 'msn.com', 'hotmail.com', 'aol.com', 'yahoo.com', 'wmconnect.com');

	function __Construct($site_type_id, $page_ordering_on=NULL, $current_page, $data_array, $rtn_original_data=NULL)
	{
	$this->site_type_id = $site_type_id;
	$this->page_ordering_on = $page_ordering_on;
	$this->current_page = $current_page;
	$this->data_array = $data_array;
	$this->rtn_original_data = $rtn_original_data;
	}

	public function Normalize($field, $param)
	{
		/*
		* @publicsection
		* @public
		* @author:	Jeff Calene
		* @fn string Normalize_Array($field, $param)
		* @brief
			A function to determine the type of data sent, run that data through the Normalization Engine and package
		for return. This function is able to take an scalar, array or object, normalize the data and return the data
		in the same format it was sent in as (ie scaler, array or object).
		CONSTRAINTS:
			1) 	Normalization will only occur if there is a matching ['type'] one array dimension greater than the
				dimension the data that is passed in in the $param variable. See examples.
			2)	Normalization will only occur on the first level passed in - i.e. we do not dig through
				multi-dimensional arrays or dig through objects looking for data to be normalized at this point
		EXAMPLES:
				ARRAYS:
					$field['dob']='11/22/2004', $field['email']='joe.winner@thesellingsource.com',
					$param['dob']['type']='date', $param['email']['type']='email'
				OBJECTS:
					$field->dob='11/22/2004', $field->email='joe.winner@thesellingsource.com',
					$param->dob['type']='date', $param->email['type']='email'
				SCALARS:
					$field='11/22/2004'
					$param['type']='date'
		* @param $field			array:		An array with data to be normalized
		* @param $param 		array:		A matching array with the type of normalization needed for each field
		* @return
			The return variable will be of the same type as the data sent in. An array will return an array, an object
			will return an object and a scalar will return a scalar
		*/

		// If $field is an array, normalize elements of the array with matching normalize['type'] fields and return array
		if(is_array($field))
		{
			foreach($field as $key => $value)
			{
				$norm_data[$key] = $this->Normalize_Engine($value, $param[$key]);
			}
		} else

		// If $field is an object, normalize and return object
		if(is_object($field))
		{
			// Convert to array first
			$field = get_object_vars($field);
			$param = get_object_vars($param);

			foreach($field as $key => $value)
			{
				$norm_data->$key = $this->Normalize_Engine($value, $param[$key]);
			}
		} else

		// If $field is a variable, just normailze the vairable and return the variable
		{
		 	$norm_data = $this->Normalize_Engine($field, $param);
		}

		 return $norm_data;
	}

	public function Validate($db, $site_type_id, $page_ordering_on=NULL, $current_page, $data_array, $rtn_original_data=NULL)
	{
		/*
		* @publicsection
		* @public
		* @author:	Jeff Calene
		* @fn array Validate($db, $site_type_id, $page_ordering_on=NULL, $current_page, $data_array, $rtn_original_data=NULL)
		* @brief
			A function to use the validation object which should exist in session and return normalized, validated data and next page if requested.
		* @param $db				object:			The database connection object
		* @param $site_type_id		int:			The site_type_id in case the site type object is not passed in
		* @param $page_ordering_on	bool:			Will return the next page if turned on
		* @param $current_page		string:			The page that sent the data
		* @param $data_array		object/array:	The data array should pass in two objects: val_rules and data with arrays containing the data for each
		* @param $rtn_original_data	bool:			If on, will return the original data
		* @return
			An array is returned containing: fatal and non-fatal errors with fields and error_codes, the existing page, the next page if requested, an array of the data after normalization and the data as it was passed in, if requested
		* @todo
			2004-11-24 	Once the function has been created to pull the validation object out of the database, we need
						to check for it and pull it in if it is not in session
		*/

		// We need to pull our validation object from the db if one doesn't exist in the session

		//Return the current page
		$val_result['existing_page'] = $current_page;

		// Determine next page
		if($page_ordering_on==1)
		{
			foreach( $data_array->page_order as $name => $name_value )
			{
				if($name_value==$current_page)
				{
					$val_result['next_page'] = $data_array->page_order[++$name];
				}
			}
		}

		// Then we should iterate through site_type object and find those FIELDS that require validation
		foreach( $data_array->val_rules[$current_page] as $name => $name_value )
		{
			//We can have multifple validation checks for each field...check them all
			foreach( $data_array->val_rules[$current_page][$name] as $val_name => $val_name_value )
			{
				// If the field is required by page validation and the field is empty, return an error
				if($data_array->val_rules[$current_page][$name][$val_name]['required']==1 && strlen($data_array->data[$name])==0)
				{
					$val_result['error']['error_non_fatal']=1;
					$val_result['error'][$name]['error_message'] = 'A value is required for this field';
				} else
				// Otherwise run the validation check
				if(strlen($data_array->data[$name])>0)
				{
					$param['type'] = $val_name;
					$param['min'] = $data_array->val_rules[$current_page][$name][$val_name]["min"];
					$param['max'] = $data_array->val_rules[$current_page][$name][$val_name]["max"];
					$param['data_min'] = $data_array->val_rules[$current_page][$name][$val_name]["data_min"];
					$param['data_max'] = $data_array->val_rules[$current_page][$name][$val_name]["data_max"];
					$normal['type'] = $data_array->val_rules[$current_page][$name][$val_name]["norm_array"];

					// First, normalize the data using the fields specified in the site type object norm_array
					$val_result["normalized_data"][$name]["value"] = $this->Normalize($data_array->data[$name],$normal);

					// Then if there is a data_min or data_max value passed in, make sure the data is within parameters

					if((isset($param['data_min']) && $val_result["normalized_data"][$name]["value"]<$param['data_min']) || (isset($param['data_max']) && $val_result["normalized_data"][$name]["value"]>$param['data_max']))
					{
						$val_result['error']['error_non_fatal']=1;
						$val_result['error'][$name]['error_message']="The value for this field is either too small or too large";
					}

					// Finally, run through the validation engine

					$internal_val = $this->Validate_Engine($val_result["normalized_data"][$name]["value"],$param);

					// There are two ways to throw an error: return $status['status'] as FALSE or return a $status['error_message']
					//if an error message is returned, then use that message, otherwise use default message for field stored in session/db
					if(isset($internal_val['error_message']))
					{
						$val_result['error']['error_non_fatal']=1;
						$val_result['error'][$name]['error_message']=$internal_val['error_message'];
					} else
					if($internal_val['status']==FALSE)
					{
						$val_result['error']['error_non_fatal']=1;
						$val_result['error'][$name]['error_message']= $data_array->val_rules[$current_page][$name][$val_name]["error_msg"];
					}
				}
			}

		}

		// Return original data if it is asked for
		if($rtn_original_data==1)
		{
			$val_result['original_data'] = $data_array->data;
		}

		// If a non-fatal error occurs, send the user back to the original page
		// If a fatal error occurs, send them to the site type fatal error page

		if($val_result['error']['error_non_fatal']==1)
			$val_result['next_page']=$current_page;
		else if($val_result['error']['error_fatal']==1)
			$val_result['next_page']=$data_array->fatal_page;


		return $val_result;
	}

	public function Normalize_Engine($field, $param)
	{
		/*
		* @publicsection
		* @fn string Normalize_Engine($field, $param)
		* @brief
			A function to normalize a field of data - called by Normalize()
		* @param $field				string:		data to be normalized
		* @param $param				array:		$param['type'] = normalization case to use
		* @return
			Returns a scalar of normalized data
		*/
			switch ($param["type"])
			{
				case "email":
					$field = trim(strtoupper($field));
				break;

				case 'uk_currency_format':
					//*********************************************
					// this actually needs to be thought out more
					//*********************************************
					$field = preg_replace('/,/', '.', $field);
					$test =  preg_match ("/^(-){0,1}([0-9]+)*(.[0-9]*)*([,][0-9]){0,1}([0-9]*)$/", $field,$m);
					if($test == 0)
   					{
						$field = FALSE;
					}
   					$field = preg_replace('/./', '', $field);
					$field = intval($field);
				break;

				case 'us_currency_format':
					// Yeah, let's just get rid of the dollar sign
					$field = preg_replace('/^\${0,1}/', '', $field);
					// intentional fall through;
				case 'integer_only':
					// run the preg match on the number to make sure that we
					// have a real number and not some strange variation
  					$test =  preg_match ("/^(-){0,1}([0-9]+)*(,[0-9]*)*([.][0-9]){0,1}([0-9]*)$/", $field,$m);
					if($test == 0)
   					{
						$field = FALSE;
					}
					// drop the comma because it causes issues with the
					// intval function
   					$field = preg_replace('/,/', '', $field);
					$field = intval($field);
				break;

				case "all_digits":
				case "all_digits_not_all_zeros":
				case "routing_number":
					$field = preg_replace ('/\D+/', '', $field);
				break;

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

						// Make sure the month and day are two digits - if not pad left with 0

						$day = str_pad($day,2, "0", STR_PAD_LEFT);
						$month = str_pad($month,2, "0", STR_PAD_LEFT);

						$field = $year . "-" . $month . "-" . $day;
					}
					else
					{
						$field = false;
					}
				break;

				case "boolean":
					if ($field != 'TRUE' && $field != 'FALSE') $field = NULL;
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
					$field = trim(strtoupper(preg_replace('/[^a-zA-Z\s-]+/', '', preg_replace('/[ ]{2,}/', ' ', $field))));
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
					//  removes everything other than alpha chars and spaces
					$field = trim(preg_replace('/[^a-zA-Z ]+/', '', $field));
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
					default:	// clear out any other value, as it would not be considered valid
						$field = '';
						break;
					}

				case 'phone_number':
					$field = preg_replace('/[^\d]/', '', $field);
					break;

				case "bank_account_type":
					$field = strtoupper($field);
					break;

				case "ip_address":
					if($field != "" && strstr($field,","))
					{
						$field = preg_replace("/[^0-9.,]+/","",$field);
						$rfield = null;
						$bad_ips = array("127","192.168","10.","0.");
						$ips = explode(",",$field);
						foreach($ips as $ip)
						{
							if($ip == "") continue;
							$bad_ip = false;
							foreach($bad_ips as $bad)
							{
								if(strncmp($ip,$bad,strlen($bad)) === 0)
								{
									$bad_ip = true;
									break;
								}
							}
							if($bad_ip)
							{
								continue;
							}
							else
							{
								$rfield = $ip;
								break;
							}
						}
						$field = (!isset($rfield)) ? $ips[0] : $rfield;
						$field = long2ip(ip2long($field));
					}
					elseif($field != "")
					{
						$field = long2ip(ip2long($field));
					}
					else
					{
						$field = "0.0.0.0";
					}

					break;

				case "paydate":
					$field = (is_array($field)) ? $field : NULL;
					break;
				case 'street':
					$field = preg_replace('/[^\w\d#\&\/\s-\.:]/','',$field);
					$field = preg_replace('/[\r\n]/', '', $field); //Mantis #5333 - Strip Carriage Returns
					break;
				case 'state_abbrev':
					$field = strtoupper(substr($field,0,2));
					break;
				case 'street_no_po_box':
					$field = preg_replace('/[^\w\d#\&\/\s-\.:]/','',$field);
					break;
			}
			return $field;
		}

	public function Run_ABA_Call($routing_number)
	{
		require_once("aba.1.php");
		$aba = new ABA_1();
		// Mantis #10606 - Changed the call from Verify() to VerifyVerbose() to check for the fail_code field returned.  [RV]
		$res = $aba->VerifyVerbose($_SESSION["config"]->license, $_SESSION["application_id"], $routing_number);

		if($res['DataXError'] || !empty($res['fail_code']))
		{
			// Mantis #10606 - Added in the check for the fail_code field returned.  [RV]
			switch (strtoupper($res['fail_code']))
			{
				case 'I':
					$reason = 'Invalid ABA Number';
					break;

				case 'L':
					$reason = 'Lookup Failed';
					break;

				case 'C':
					$reason = "ABA/Bank's ACH transactions not permitted for consumers";
					break;

				case 'N':
					$reason = "ABA/Bank's ACH transaction not permitted";
					break;

				case 'D':
					$reason = "Cannot debit/credit account";
					break;

				default:
					$reason = $res['DataXError'];
					break;
			}

			$this->aba_call["valid"] = $res["valid"];
			$this->aba_call["bank_name"] = "";
			$this->aba_call["processed"] = TRUE;
			$this->aba_call["fail_code"] = $res['fail_code'];
			$this->aba_call["dataxerror"] = $reason;
		}
		else
		{
			$this->aba_call["valid"] = $res["valid"];
			$this->aba_call["bank_name"] = $res["bank_name"];
			$this->aba_call["processed"] = TRUE;
		}

	}

	public function Validate_Engine($field, $param)
	{
		/*
		* @publicsection
		* @fn string Validate_Engine($field, $param)
		* @brief
			A function to validate a field of data - called by Validate()
		* @param $field				string:		data to be normalized
		* @param $param				array:		$param['type'] = normalization case to use
		* @return
			Returns a status array with bool $status['status'] (whether or not the data passed the test) and $status['error_message'] which is a custom error message that can be hardcoded and passed back (otherwise the default message for the field is returned)
		* @todo
		*/

		$status = array();

		if(!isset($param["min"]))
		{
			$param["min"] = 1;
		}

		if(!isset($param["max"]))
		{
			$param["max"] = 255;
		}

		$status['status'] = TRUE;

		switch($param["type"])
		{
			case 'enum_check':
				$status['status'] = FALSE;
				foreach ($param['enum'] as $value)
				{
					if (!strcasecmp($field, trim($value)))
					{
						$status['status'] = TRUE;
						break;
					}
				}
				break;

			case "string":

				if (is_numeric($param['min']) && is_numeric($param['max']))
				{
					$len = strlen(trim($field));
					$status["status"] = ($len >= $param["min"] && $len <= $param["max"]);
				}

				break;

			case "all_digits":
				// does field meet min/max requirements?
				$status["status"] = preg_match('/^\d{' . $param['min'] . ',' . $param['max'] . '}$/', $field);
				break;

			case 'phone_number':
				// does field meet min/max requirements?
				if ($status["status"] = preg_match('/^\d{' . $param['min'] . ',' . $param['max'] . '}$/', $field) && strlen($field)==10)
				{
					$format = preg_match('/^([2-9]\d{2}){1}([2-9]\d{2}){1}[0-9]{4}$/', $field); // Check Phone Number Formatting
					//$repeats = preg_match('/^\d{3}(\d)\1{6}$/', $field);	// Check for repeating numbers
					if($format /* && !$repeats --- removed [AuMa] per instructions from [BF] */){
						$status["status"] = TRUE;
					}
					else
					{
						$status["status"] = FALSE;
					}


// 					// make sure that the last 7 digits are not all 0
// 					$status["status"] = (preg_match('/^\d{3}0{7}$/', $field)) ? FALSE : TRUE;
				}
				break;
			case "ssn":
					// first regex makes sure it's 9 digits and that no section is all 0s
					// second makes sure that it's not all one number across the entire ssn
					$status['status'] = (bool)preg_match('/^(?!0{3})(\d)(?!\1{2}-?\1{2}-?\1{4})\d{2}-?(?!0{2})\d{2}-?(?!0{4})\d{4}$/', $field);
				break;
			case "all_digits_not_all_zeros":
				// does field meet min/max requirements?
				if ($status["status"] = preg_match('/^\d{' . $param['min'] . ',' . $param['max'] . '}$/', $field))
				{
					// make sure that it is not all zeros
					$status["status"] = (preg_match('/^(0)\1{0,}$/', $field)) ? FALSE : TRUE;
				}
				break;

			case "date":

				// if the date is in valid format and checkdate returns true then the date is valid
				// YYYY-MM-DD
				if (preg_match('/^(\d{4})(-|\/)(\d{1,2})\2(\d{1,2})$/', $field, $match))
				{
					list($unused, $year, $delim, $month, $day) = $match;

					//Make sure the days and month aren't zero
					if($day == 0 || $month == 0)
					{
						$status["status"] = FALSE;
						break;
					}

					// return output from checkdate
					$status["status"] = checkdate($month, $day, $year);

				}
				// MM-DD-YYYY
				else if (preg_match('/^(\d{1,2})(-|\/)(\d{1,2})\2(\d{4})$/', $field, $match))
				{
					list($unused, $month, $delim, $day, $year) = $match;

					//Make sure the days and month aren't zero
					if($day == 0 || $month == 0)
					{
						$status["status"] = FALSE;
						break;
					}

					// return output from checkdate
					$status["status"] = checkdate($month, $day, $year);
				}
				else
				{
					$status["status"] = FALSE;
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

					list($userName, $mailDomain) = split('@', strtolower($field));

					// check tld - this list must be updated as new tld's are added
					if (preg_match('/\.(ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|fx|ga|gb|gov|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|je|jm|jo|jobs|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nato|nc|ne|net|nf|ng|ni|nl|no|np|nr|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|travel|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)$/', $mailDomain))
					{

						if (isset($param['ck_mx']) && strtolower($param['ck_mx']) != 'n')
						{
							// check if this domain is in our list of common domains,
							// or, if not, check that an MX record exists for the domain

							$status['status'] = (in_array(strtolower($mailDomain), self::$email_domains) || checkdnsrr($mailDomain, 'MX'));

							if ($status['status'] === FALSE)
							{
								$status['error_message'] = 'MX FAILED: Email must use a valid domain name and be in the following form: yourname@yourdomain.com.';
							}

						}
						else
						{
							$status['status'] = TRUE;
						}

					}
					else
					{
						$status['status'] = FALSE;
						$status['error_message'] = 'TLD FAILED: Email must use a valid domain name and be in the following form: yourname@yourdomain.com.';
					}

				}
				else
				{
					$status['status'] = FALSE;
					$status['error_message'] = 'FORMAT FAILED: Email must use a valid domain name and be in the following form: yourname@yourdomain.com.';
				}
			break;

			case "address";

				if (isset($param['validation_method']) && $param["validation_method"] == 'satori')
				{
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
				// Mantis #10606 - Added the specific error message to be passed back if we got a dataxerror returned on the ABA check. [RV]
				if(isset($this->aba_call["dataxerror"])) $status['error_message'] = $this->aba_call["dataxerror"];
			break;


			case "over_18":
				// hack to check date first
				$check = Data_Validation::Validate_Engine($field, array('type'=>'date'));

                if(!$check['status'])
                {
                	$status['status'] = FALSE;
                }
                else //Check date in a manner to avoid unix dates
                {
                    $b_month = substr($field,0,2);
                    $b_day = substr($field,3,2);
                    $b_year = substr($field,6,4); //4 digit year

                    $check_month = date("n");
                    $check_day = date("j");
                    $check_year = date("Y") - 18;

                    if($b_year > $check_year)
                    {
                        $status['status'] = false;
                    }
                    elseif($b_year == $check_year) //same year
                    {
                        if($b_month > $check_month)
                        {
                            $status['status'] = false;
                        }
                        elseif($b_month == $check_month) //same month
                        {
                            if($b_day > $check_day) $status['status'] = false;
                        }
                    }
                }
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
				$status["status"] = preg_match('/^\d{5}(-\d{4})?$/', $field) ? TRUE : FALSE;
				break;

			case "real_dollar_amount":
				// if you use Normalize just do an is_numeric()
				$stats["status"] = (false !== preg_match('/^\$?(?:\d{1,3}(?:,\d{3})*|\d+)(?:\.\d{2})?$/', $field));
				break;

			case "bank_account_type":
				$status["status"] = ($field == "CHECKING" || $field == "SAVINGS");
				break;

			case "conditional":
				$variable = $field[0];
				$varvalue = $field[1];
				$condition = $field[2];
				$condvalue = $field[3];

				switch ($variable) {
					case 'cali_agree':
						$status["status"] = (strtolower($condvalue)=='ca'?(strtolower($varvalue)=='agree'||strtolower($varvalue)=='disagree'?TRUE:FALSE):TRUE);
						break;
				}
				break;

			case "ip_address":
				$status["status"] = ($field == "0.0.0.0") ? false : true;
				break;
			case 'street':
				$status['status'] = preg_match('/^[\w\d\s\&\.\/:#-]+$/',$field) ? TRUE : FALSE;
				break;

			case 'street_no_po_box':
				$status['status'] = preg_match('/^[\w\d\&\.\/:#-]+ [\w\d\s\&\.\/:#-]+$/',$field) ? TRUE : FALSE;
				if($status['status'])
				{
					//$status['status'] = preg_match("/([p][-\/. ]*[o][-\/. ]*[b][-\/. ]*[0-9]{2,})|(([p][-\/. ]*([o0]{1,2})?[-\/. ]*)|((p[o0]st(al)?)[. ]*([o0]ff.+)?))([b][. ]*[o0][. ]*[x]?)/i", strtolower(trim($field))) ? FALSE : TRUE;
					$status['status'] = preg_match("/P\.* *O\.* *B*O*X*/i", strtolower(trim($field))) ? FALSE : TRUE;
					if(!$status['status'])
					{
						$status['error_message'] = 'ADDRESS FAILED: Address cannot be a P.O. Box.';
					}
				}
				break;

			case "paydate":
				if(is_array($field) && isset($field['frequency']) && $field['frequency']!='')
				{
					$status["status"] = true;
				}
				else
				{
					$status["status"] = false;
				}
				break;
			case 'state_abbrev':
				$status['status'] = (strlen($field) == 2 && $this->Check_State_Abbrev($field));
				break;
			case 'vin_number':
				$status['status'] = $this->Valid_Vin($field);
				break;
			case 'po_box':
				$status['status'] = preg_match("/P\.* *O\.* *B*O*X*/i", strtolower(trim($field))) ? FALSE : TRUE;
				if(!$status['status'])
				{
					$status['error_message'] = 'ADDRESS FAILED: Address cannot be a P.O. Box.';
				}
			break;

			case 'apo':
				$status['status'] = preg_match("/\b(a|f)[. ]*p[. ]*o\.*\b/i", strtolower(trim($field))) ? FALSE : TRUE;
				if(!$status['status'])
				{
					$status['error_message'] = 'ADDRESS FAILED: APO addresses are not allowed.';
				}
			break;
			case 'ssn_valid':
				//Separator class.
				$sep 			= "[ -]?";

				//Static invalid SSNs due to use in advertising and public display.
				//078-05-1120
				//987-65-432x
				$static_regex 	 = "(?!078{$sep}05{$sep}1120)";
				$static_regex 	.= "(?!987{$sep}65{$sep}432\d)";

				//Static invalid area numbers.
				$static_regex	.= "(?!000)";
				$static_regex	.= "(?!666)";

				//validation rule for the maximum area assigned.
				//current maximum area number is 772
				$area_regex 	= "([0-6]\d{2}|7([0-6]\d|7[0-2]))";

				//validation rule for group number.
				// 00 is invalid.
				$group_regex	= "(?!00)\d{2}";

				//validation rule for the serial number.
				// 0000 is invalid.
				$serial_regex	= "(?!0000)\d{4}";

				$ssn_regex		= "/^{$static_regex}{$area_regex}({$sep}){$group_regex}({$sep}){$serial_regex}$/";

				$status['status'] = preg_match($ssn_regex, trim($field));
				if(!$status['status'])
				{
					$status['error_message'] = 'Social Security Number given is invalid.';
				}
			break;

		}

		return $status;

	}


	public function Verify_Mod10 ($routing_number)
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

	public function Validate_Routing_Number ($routing_number)
	{
		if(isset($this->aba_call["valid"]))
		{
			if($this->aba_call["valid"])
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

	private function Check_State_Abbrev($abr)
	{
		$states = array(
			'AK', 'AL', 'AR', 'AZ', 'CA', 'CO', 'CT',
			'DC', 'DE', 'FL', 'GA', 'HI', 'IA', 'ID',
			'IL', 'IN', 'KS', 'KY', 'LA', 'MA', 'MD',
			'ME', 'MI', 'MN', 'MO', 'MS', 'MT', 'NC',
			'ND', 'NE', 'NH', 'NJ', 'NM', 'NV', 'NY',
			'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD',
			'TN', 'TX', 'UT', 'VA', 'VT', 'WA', 'WI',
			'WV', 'WY',
		);
		return in_array(strtoupper($abr),$states);
	}

	private function Valid_Vin($vin)
	{
		$vin = strtoupper($vin);

		//each char in $chars translates the number
		//in $nums when calculating the sum.
		$chars = 'ABCDEFGHJKLMNPRSTUVWXYZ';
		$nums  = '12345678123457923456789';

		//How to weight each position in the vin.
		//Position 9(index 8) is actually used to check the sum against and is
		//not included in the sum, so it's weighted at 0.
		$weight_map = array(
			0 => 8, 1 => 7, 2 => 6, 3 => 5, 4 => 4, 5 => 3, 6 => 2, 7 => 10,
			8 => 0, 9 => 9, 10 => 8, 11 => 7, 12 => 6, 13 => 5, 14 => 4,
			15 => 3, 16 => 2
		);


		//This is the digit that the weighted sum thing mod 11 must be equal
		//to for the vin to be considered valid.
		$check_digit = $vin[8];

		//map the letters to their numerical value
		$new_vin = strtr($vin,$chars,$nums);

		//Calculate the sum. Each position is weighted and we sum up the whole mess
		$sum = 0;
		for($i = 0,$sum = 0,$len = strlen($vin);$i < $len;$i++)
		{
			$sum += ($new_vin[$i] * $weight_map[$i]);
		}

		//mod 11 to get the remainder and compare against the check_digit
		$check_sum = $sum % 11;
		//if the mod11 is 10, the check_digit is X, otherwise they must be equal.
		return (($check_sum == 10 && $check_digit == 'X') || $check_sum == $check_digit);

	}

}
?>
