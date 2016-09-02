<?php
/**
  	@publicsection
	@public
	@brief
		Prepares/Checks user entered data

	@todo
		
*/
class Data_Preparation
{
	public static $TEST_ABAS = array('123123123');
	
	/**
	 * Is test ABA
	 * 
	 * Checks to see if it's a test ABA
	 */
	public static function Is_Test_ABA($aba)
	{
		return in_array($aba, self::$TEST_ABAS, TRUE);
	}

	/**
	 * @param $page string
	 * @desc assembles data which is broken apart in multiple fields
	 **/
	public static function Assemble_Data($collected_data, $field, $glue)
	{
		$assembled = array();

		foreach($field as $field_name => $blah)
		{
			if( isset($collected_data[$field[$field_name][0]]) )
			{
				foreach($field[$field_name] as $key => $field_piece)
				{
					if( !isset($assembled[$field_name]) )
					{
						$assembled[$field_name] = '';
					}

					$assembled[$field_name] .=	( isset($glue[$field_name])	&& $key ) ? $glue[$field_name] . $collected_data[$field_piece]	: $collected_data[$field_piece];
				}
			}
		}

		return $assembled;
	}

	/**
	 * @return bool
	 * @desc run validation rules
	 * 	It can be called directly as long as the given arguments are in the same format
	 *	as they would be in an OLP object.
	 *
	 **/
	public static function Validate_Data(&$config, &$collected_data, &$data_validation, &$pages, $current_page, $applog)
	{
		$normalized_data = Array();
		$errors = Array();

		// prevent form post hijacking
		foreach ($collected_data as $k => $v)
		{
			$v = str_replace(array("\r","\n\n",">","<","\"","'"),"",$v);// ><()" added for 10187:XSS [MJ]
			$v = str_replace("(", "&#40;",$v);
			$v = str_replace(")", "&#41;",$v);
			$collected_data[$k] = (is_string($v)) ? stripslashes($v) : $v;
		}

		// GForge #3367 - Bank ABA isn't normalized yet, so do a simple check here [RM]
		if (preg_match('/^\d{9}$/i', $collected_data['bank_aba']))
		{
			// Mantis 11120 - Removed so reacts tests ABA [RM]
			//if(isset($collected_data["dep_account"]))

				// Test if the deposit account is "NO_ACCOUNT" [AuMa]
				if(!($collected_data["dep_account"] === "NO_ACCOUNT" && $collected_data["bank_aba"] === "")) 
				{
				
					if(self::Is_Test_ABA($collected_data["bank_aba"]))
					{
						$_SESSION['aba_call_result'] = Array(
							'aba' => $collected_data["bank_aba"],
							'valid' => TRUE,
							'bank_name'=> 'Test Bank',
							'processed' => TRUE);
					}
					//Don't run ABA validation if we've done the deal.
					elseif((!defined('USE_DATAX_ABA') || DATAX_ABA == true) && ($_SESSION['aba_call_result']['aba'] != $collected_data["bank_aba"] || !$_SESSION['aba_call_result']['valid']))
					{
						$timer = microtime(TRUE);
						$data_validation->Run_ABA_Call($collected_data["bank_aba"]);
						$_SESSION["aba_call_result"] = $data_validation->aba_call;
						$_SESSION["aba_call_result"]['runtime'] = microtime(TRUE) - $timer;
						$_SESSION["aba_call_result"]['aba'] = $collected_data["bank_aba"];
						if($data_validation->aba_call["dataxerror"] && empty($data_validation->aba_call['fail_code']))
						{
							$applog->Write("[DataX ABA Validation] Error:".$data_validation->aba_call["dataxerror"]."\n");
						}
						if ($_SESSION["data"]["bank_name"] && strtolower(trim($_SESSION["data"]["bank_name"]))=='unknown')
						{
							$_SESSION["data"]["bank_name"] = $data_validation->aba_call["bank_name"].'*';
						}
					}
					elseif($_SESSION['aba_call_result']['aba'] == $collected_data["bank_aba"])
					{
						$_SESSION["data"]["bank_name"] = $_SESSION['aba_call_result']['bank_name'];
					}
					else
					{
						//we need to hit the verify stat once we have the
						//application id
						$_SESSION['aba_call_result'] = Array(
							'aba' => $collected_data["bank_aba"],
							'valid' => 'VERIFY',
							'bank_name'=>'',
							'processed' => TRUE);
					}
				}
		}

		if (isset($collected_data["bank_name"]))
		{
			// if we have already processed the aba call
			if ($_SESSION["aba_call_result"]["processed"])
			{
				if ($_SESSION["aba_call_result"]["valid"] && strtolower(trim($collected_data["bank_name"]))=='unknown')
				{
					// if a soap site populates bank_name with
					// unknown, grab the bank name from aba->Verify
					$collected_data["bank_name"] = $_SESSION["aba_call_result"]["bank_name"].'*';
				}
			}
		}
		if ($pages->{$current_page})
		{
			
			foreach($pages->{$current_page} as $field_name => $field_rules)
			{
				
				if ($field_name == 'next_page' || $field_name == 'stat'
					|| ( // This code allows the user to select that they do not have an account to use. [Task # 12398 - AuMa]
						($field_name === "bank_aba" || $field_name === "bank_account" || $field_name === "bank_name"
						|| $field_name === "dep_account" || $field_name === "income_direct_deposit"   || $field_name === "bank_account_type")
							
						&& ( $collected_data["dep_account"] === "NO_ACCOUNT" || $collected_data["income_direct_deposit"] === "NO_ACCOUNT"
							|| $_SESSION["data"]["dep_account"] === "NO_ACCOUNT" ||  $_SESSION["data"]["income_direct_deposit"] === "NO_ACCOUNT")
						)
					)
				{
						continue;
				}
				
				if ($collected_data[$field_name])
				{
					
					$normalized_data[$field_name] = $data_validation->Normalize_Engine($collected_data[$field_name], $field_rules);
					
					if ($normalized_data[$field_name])
					{
						
						if (($field_rules['min'] && strlen($normalized_data[$field_name])<$field_rules['min']) || ($field_rules['max'] && strlen($normalized_data[$field_name])>$field_rules['max']))
						{
							$errors[$field_name] = $field_name;
						}
						
						//if (!$errors[$field_name])
						if (!array_key_exists($field_name, $errors))
						{
							
							$val_response = $data_validation->Validate_Engine($normalized_data[$field_name], $field_rules);
							
							if (!$val_response['status'])
							{
								// Mantis #10606 - Added in the check for the fail_code field returned.  [RV]
								if($field_name == 'bank_aba' && isset($_SESSION["aba_call_result"]['fail_code']))
								{
									$errors[$field_name] = $field_name.'_'.$_SESSION["aba_call_result"]['fail_code'];
								}
								else 
								{
									$errors[$field_name] = $field_name;
								}
							}
						}
					}
					elseif ($field_rules['required'])
					{
						// Mantis #10744 - Non-required fields now validate, to prevent useless data being inserted into the database. [RM]
						$errors[$field_name] = $field_name;
					}
				}
				elseif ($field_rules['required'])
				{
					$errors[$field_name] = $field_name;
				}
				
				//*********************************************
				// Bringing the cali agree field to life - need to add
				// apprpriate error message though GForge 3918
				// [AuMa]
				// To make the cali_agree field light up - you
				// must first add it to the site type for the page that
				// it appears on as a non-required field 
				// (enum check is appropriate)
				//*********************************************
				if ($field_name == 'cali_agree')
				{
					if ($_SESSION["data"]['home_state'] == 'CA' || $collected_data['home_state'] == 'CA')
					{
							if (!isset ($collected_data['cali_agree']))
							{
								$errors['cali_agree'] = 'cali_agree';
							}
					}
				}
				//*********************************************
				// End Cali Agree field addition
				//*********************************************
				
				if( $field_name == 'esignature' )
				{
					if ($_SESSION['data']['name_first'])
					{
						$esig = trim($_SESSION['data']['name_first']) . ' ' . trim( $_SESSION['data']['name_last'] );
					}
					else // in customer service area
					{
						$esig = trim($_SESSION['cs']['name_first']) . ' ' . trim ( $_SESSION['cs']['name_last'] );	
					}
					
					$esig_submitted = strtolower(trim(stripslashes($collected_data['esignature'])));
					// remove extra whitespace between fname and lname & replace with a space
					$esig_submitted = preg_replace('/\s+/', ' ', $esig_submitted);

					if( $esig_submitted != strtolower($esig) || !isset( $collected_data['esignature'] ) )
					{
						$errors[$field_name] = 'Your Electronic Signature must match your name as it appears here: <strong>' . $esig . '</strong>';
					}
				}
			}
		}
		
		//Soap vendors are passing us the string "NULL", and so NULL is showing up in
		// our vendor posts.
		if(trim(strtolower($collected_data['home_unit'])) == 'null' || trim(strtolower($normalized_data['home_unit'] == 'null')))
		{
			unset($collected_data['home_unit']);
			unset($normalized_data['home_unit']);
		}
		
		if (is_array($normalized_data))
		{
			$normalized_data = array_merge($collected_data, $normalized_data);
		}
		else
		{
			$normalized_data = $collected_data;
		}
		
		
		
		if ((boolean)sizeof($normalized_data))
			return array($normalized_data, $errors);
		else
			return NULL;

	}	
}
