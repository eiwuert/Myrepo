<?php

class Error_Message_Resource
{
	var $codes;

	function Error_Message_Resource()
	{
		$this->codes = array(
									 // customer.CUSTOMER
									"name_first" => "First Name is a required field.",
									"name_middle" => "Middle Initial is a required field.",
									"name_last" => "Last Name is a required field.",
									"dob" => "Date of Birth must show you to be at least 18 years old.",
									"social_security_number" => "Social Security Number is a required "
																		. "field.",
									"state_id_number" => "State Id Number is a required field.",
									"opt_in" => "Opt In is a required field.",

									// customer.ADDRESS
									"home_street" => "Street is a required field.",
									"home_unit" => "Apartment/Suite is a required field.",
									"home_city" => "City is a required field.",
									"home_state" => "State is a required field.",
									"home_zip" => "Zip is a required field.",
									"home_type" => "Residence Type is a required field.",
									"residence_type" => "Residence Type is a required field.",
									"date_occupied" => "Date Occupied is a required field.",

									// customer.PHONE
									"phone_home" => "Home Phone is a required field.",
									"phone_fax" => "Fax is a required field.",
									"phone_cell" => "Mobile Phone is invalid.",
									"phone_best_time" => "'Best Time to Call' is a required field.",

									// customer.EMAIL
									"email_primary" => "Email must use a valid domain name and be in "
															. "the following form: yourname@yourdomain.com.",
									"email_alternate"  => "Email must use a valid domain name and be "
															. "in the following form: "
															. "yourname@yourdomain.com.",

									// customer.EMPLOYMENT
									"employer_name" => "Employer Name is a required field.",
									"employer_street" => "Employer Street is a required field.",
									"employer_unit" => "Employer Unit is a required field.",
									"employer_city" => "Employer City is a required field.",
									"employer_state" => "Employer State is a required field.",
									"employer_zip" => "Employer Zip is a required field.",
									"phone_work" => "Work Phone is a required field.",
									"phone_work_ext" => "Work Phone Extension is a required field.",
									"job_title" => "Job Title is a required field.",
									"job_supervisor" => "Job Supervisor is a required field.",
									"job_shift" => "Job Shift is a required field.",
									"employer_length" => "Employment Length is a required field.",

									// customer.AUTHENTICATION
									"login" => "Username/Login is a required field.",
									"crypt_password" => "Password is a required field.",

									// company.REFERENCE
									"ref_01_name_full" => "Reference #1 Name is a required field.",
									"ref_01_phone_home" => "Reference #1 Phone is a required field.",
									"ref_01_relationship" => "Reference #1 Relationship is a required "
																	. "field.",                   
									"ref_02_name_full" => "Reference #2 Name is a required field.",
									"ref_02_phone_home" => "Reference #2 Phone is a required field.",
									"ref_02_relationship" => "Reference #2 Relationship is a required "
															. "field.",

									// company.TRANSACTION
									"bank_name" => "Bank Name is a required field.",
									"bank_aba" => "Your Bank Routing Number (ABA) appears to be incorrect. Please check the number and type again.",
									// v--Mantis #10606 - Added in the check for the fail_code field returned.  [RV]
									"bank_aba_I" => 'Invalid ABA Number',
									"bank_aba_L" => 'Lookup Failed',
									"bank_aba_C" => "ABA/Bank's ACH transactions not permitted for consumers",
									"bank_aba_N" => "ABA/Bank's ACH transaction not permitted",
									"bank_aba_D" => "Cannot debit/credit account",
									// ^--Mantis #10606
									"bank_account" => "Bank Account Number is a required field.",
									"check_number" => "Check Number is a required field.",
									"fund_requested" => "Amount Requested is a required field.",
									"fund_qualified" => "fund_qualified is a required field.",
									"income_monthly_net" => "Monthly Income is a required field.",
									"income_frequency" => "Income Frequency is a required field.",
									"income_date1" => "Next Pay Date #1 is a required field.",
									"income_date2" => "Next Pay Date #2 is a required field.",
									"income_stream" => "You must be employed or receiving a recurring "
															. "income.",
									"checking_account" => "You must have a checking account.",
									"income_direct_deposit" => "You must enter how you receive your pay.",

									// BOOLEANS
									"legal_notice_1" => "The \"Notices and Disclosures\" checkbox at "
															. "the bottom of the page must be checked to "
															. "proceed.",
									"legal_approve_docs_1" => "The \"Application\" checkbox below must "
																		. "be checked to proceed.",
									"legal_approve_docs_2" => "The \"Privacy Policy\" checkbox below "
																		. "must be checked to proceed.",
									"legal_approve_docs_3" => "The \"Authorization Agreement For "
																		. "Preauthorized Payment\" checkbox below "
																		. "must be checked to proceed.",
									"monthly_1200" => "You must make at least $1000/month.",
									"citizen" => "You must be a citizen.",
									"offers" => "Please select Yes or No for our additional offer.",
									"cookies_off" => "This site requires that Cookies are turned on. "
															. "Please set your browser to accept cookies.",
									"military" => "You must select if you are in the military.",
	
									// PAY DATE ERRORS - In the past
									"pay_date1_past" => "Pay Date 1 is in the past.",
									"pay_date2_past" => "Pay Date 2 is in the past.",
									"pay_date3_past" => "Pay Date 3 is in the past.",
									"pay_date4_past" => "Pay Date 4 is in the past.",

									// PAY DATE ERRORS - On a holiday
									"pay_date1_weekend_holiday" => "Pay Date 1 lands on holiday or "
																				. "weekend.",
									"pay_date2_weekend_holiday" => "Pay Date 2 lands on holiday or "
																				. "weekend.",
									"pay_date3_weekend_holiday" => "Pay Date 3 lands on holiday or "
																				. "weekend.",
									"pay_date4_weekend_holiday" => "Pay Date 4 lands on holiday or "
																				. "weekend.",

									// PAY DATE ERRORS - Too many
									"too_many_twice_monthly" => "You have too many paydates in one "
																		. "month.",
									"too_many_monthly" => "You have too many paydates in one month",
									
									"invalid_twice_monthly" => "The submitted paydates are invalid "
																		. "for this frequency.",
									// PAY DATE ERRORS - Less than 7 days
									"pay_date2_weekly" => "Pay Date 2 is not 7 days from Pay Date 1.",
									"pay_date3_weekly" => "Pay Date 3 is not 7 days from Pay Date 1.",
									"pay_date4_weekly" => "Pay Date 4 is not 7 days from Pay Date 1.",
	
									// PAY DATE ERRORS - Less than 14 days
									"pay_date2_biweekly" => "Pay Date 2 is not 14 days from Pay Date 1.",
									"pay_date3_biweekly" => "Pay Date 3 is not 14 days from Pay Date 1.",
									"pay_date4_biweekly" => "Pay Date 4 is not 14 days from Pay Date 1.",

									// PAY DATE ERRORS - Too far in the future
									"pay_date1_weekly_far" => "Pay Date 1 starts too far in the future.",

									// System
									"try_again" => "A system error occured. Verify your License Key and "
														. "Site Type. If the error persists, notify an "
														. "administrator.",
									"confirm_address" => "This address cannot be verified or denied.",

									// Paperless
									"esignature" => "Your Electronic Signature must match your name as it appears.",
									"legal_approve_docs_1" => "The \"Application\" must be agreed to in order to proceed.",
									"legal_approve_docs_2" => "The \"Privacy Policy\" must be agreed to in order to proceed.",
									"legal_approve_docs_3" => "The \"Authorization Agreement For Preauthorized Payment\" must be agreed to in order to proceed.",
									"legal_approve_docs_4" => "The \"Loan Notice And Disclosure\" must be agreed to in order to proceed.",
									// used in emv ccr stuff
	                        "offers_not_checked" => "You must check all offers.",
	                        "partial_data" => "Please fill in fields completely.",
	                        "at_least_one" => "At least one entry is required.",

									// BB - EZM
							      "ezm_nsf_count" => "You may not have 4 or more NSFs",
	                        "ezm_signature" => "Please type your signature",
	                        "ezm_terms" => "You must agree to the Terms and Conditions",

									// other stuff
   								"cali_agree_conditional" => "You must agree/disagreeto the SSN clause.",
   								"cali_agree" => "You must agree/disagreeto the SSN clause.",
	   							"bank_account_type" => "Bank Account Type must be checking or savings."
							);
	}


	function Get_Error_Desc ($error_key)
	{
		$result = 'An unknown data validation error has occurred.'
						. ' Double check all the fields and try again.';

		if (isset($this->codes[$error_key]))
		{
			$result = $this->codes[$error_key];
		}

		return $result;
	}
}


?>
