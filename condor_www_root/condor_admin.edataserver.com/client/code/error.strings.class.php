<?php

class Error_Strings
{
	public static $error_strings = array
			(
			// customer.CUSTOMER
			"name_first" => "First Name is a required field.",
			"name_middle" => "Middle Initial is a required field.",
			"name_last" => "Last Name is a required field.",
			"your_name" => "Your name is required.",
			"gender" => "Gender is a required field.",
			"dob" => "Date of Birth must show you to be at least 18 years old.",
			"dob_invalid" => "Your Date of Birth is not a valid date.",
			"social_security_number" => "Social Security Number is invalid.",
			"ssn_part_3"	=> "The last 4 numbers of your Social Security # is required.",
			"ssn_part_3_confirm"	=> "Please confirm the last 4 numbers of your Social Security #.",
			"ssn_part_3_confirm_compare" => "The last 4 numbers of your Social Security # does not match confirmation.",
			"state_id_number" => "Driver's License / State ID Number is a required field.",
			"opt_in" => "Opt In is a required field.",
			"stat_name" => "Stat Name is a required field.",
			"stat_type" => "Stat Type is a required field.",
			"site_name" => "Site Name is a required field.",
			
			// customer.ADDRESS
			"home_street" => "Street is a required field.",
			"street" => "Street is a required field.",
			//"home_unit" => "Apartment/Suite is a required field.",
			"home_unit" => "Apartment/Suite value is too long.",
			"unit" => "Apartment/Suite is a required field.",
			"home_city" => "City is a required field.",
			"city" => "City is a required field.",
			"home_state" => "State is a required field.",
			"state" => "State is a required field.",
			"home_zip" => "Zip is invalid.",
			"zip" => "Zip is invalid.",
			"home_type" => "Residence Type is a required field.",
			"date_occupied" => "Date Occupied is a required field.",
			"cali_agree_conditional" => "You must agree/disagree to the SSN  clause.",

			// customer.PHONE
			"phone_home" => "Home Phone is invalid.",
			"fax_number" => "Fax is invalid.",
			"phone_cell" => "Mobile Phone is invalid.",
			"phone_best_time" => "'Best Time to Call' is a required field.",

			// customer.EMAIL
			"email_primary" => "Email must use a valid domain name and be in the following form: yourname@yourdomain.com.",
			"email_alternate"  => "Email must use a valid domain name and be in the following form: yourname@yourdomain.com.",
			"email_friend"  => "Friend email must use a valid domain name and be in the following form: yourname@yourdomain.com.",

		    // customer.EMPLOYMENT
			"employer_name" => "Employer Name is a required field.",
			"employer_street" => "Employer Street is a required field.",
			"employer_unit" => "Employer Unit is a required field.",
			"employer_city" => "Employer City is a required field.",
			"employer_state" => "Employer State is a required field.",
			"employer_zip" => "Employer Zip is invalid.",
			"phone_work" => "Work Phone is invalid.",
			"phone_work_ext" => "Work Phone Extension is NOT a required field.",
			"ext_work" => "Work Phone Extension is too long.",
			"job_title" => "Job Title is a required field.",
			"job_supervisor" => "Job Supervisor is a required field.",
			"job_shift" => "Job Shift is a required field.",
			"employer_length" => "Employment Length is a required field.",

			// customer.AUTHENTICATION
			"login" => "Username/Login is a required field.",
			"crypt_password" => "Password is a required field.",

		    // company.REFERENCE
			"ref_01_name_full" => "Reference #1 Name is a required field.",
			"ref_01_phone_home" => "Reference #1 Phone is invalid.",
			"ref_01_relationship" => "Reference #1 Relationship is a required field.",
			"ref_02_name_full" => "Reference #2 Name is a required field.",
			"ref_02_phone_home" => "Reference #2 Phone is invalid.",
			"ref_02_relationship" => "Reference #2 Relationship is a required field.",

			// company.TRANSACTION
			"bank_name" => "Bank Name is a required field.",
			"bank_aba" => "Your Bank Routing Number (ABA) appears to be incorrect.  Please check the number and type again.",
			"bank_account" => "Bank Account Number is invalid.",
			"bank_account_type" => "Bank Account Type must be CHECKING or SAVINGS.",
			"check_number" => "Check Number is a required field.",
			"fund_requested" => "Amount Requested is a required field.",
			"fund_qualified" => "fund_qualified is a required field.",
			"income_monthly_net" => "Monthly Income is a required field.",
			"income_frequency" => "Income Frequency is a required field.",
			"income_date1" => "Next Pay Date #1 is a required field.",
			"income_date2" => "Next Pay Date #2 is a required field.",
			"income_stream" => "You must be employed or receiving a recurring income.",
			"checking_account" => "You must have a checking account.",
			"income_direct_deposit" => "You must indicate how you receive your pay.",
			"dep_account" => "You must indicate how you receive your pay.",
			"amount" => "Please enter an amount in the Amount field.",
			"comment" => "You must fill out the comment field.",
			"pin" => "You must fill out the PIN that you want to change to.",
			"card_bin" => "You must enter a Card Bin for this Card Type.",
			"card_stock" => "You must enter a Card Stock for this Card Type.",
			"name" => "You must fill out the Name field.",
			"description" => "You must fill out Description for this.",
			"recurrence" => "You must fill out Recurrence for this. e.g. 30 for every 30 days",

			// BOOLEANS
			"legal_notice_1" => "The \"Notices and Disclosures\" checkbox at the bottom of the page must be checked to proceed.",
			"legal_approve_docs_1" => "The \"Application\" checkbox below must be checked to proceed.",
			"legal_approve_docs_2" => "The \"Privacy Policy\" checkbox below must be checked to proceed.",
			"legal_approve_docs_3" => "The \"Authorization Agreement For Preauthorized Payment\" checkbox below must be checked to proceed.",
			"legal_approve_docs_4" => "The \"Loan Note And Disclosure\" checkbox below must be checked to proceed.",
			"monthly_1200" => "You must make at least \$1000/month.",
			"citizen" => "You must be a citizen.",
			"offers" => "Please select an offer.",
			"cookies_off" => "This site requires that Cookies are turned on.  Please set your browser to accept cookies.",
			"unsecured_debt" => "The \"Unsecured Debt\" checkbox must be checked to proceed.",


			// SST
			"cc_type" => "Credit Card type is required.",
			"email1" => "Email is invalid",
			"email2" => "Email 2 is invalid",
			"email1_compare" => "The emails submitted do not match.",
			"cc_type" => "Credit Card type is required.",
			"cc_number" => "Credit Card number is invalid.",
			"cc_exp_month" => "Credit Card expiration month is invalid.",
			"cc_exp_year" => "Credit Card expiration year is invalid.",
			"cc_cvv2" => "CVV2 number is invalid.",
			"cc_name" => "Name on Credit Card is invalid.",
			"cc_exp" => "Credit Card Expiration date is invalid.",
			"billing_address_1" => "Billing Address is a required field.",
			"billing_city" => "Billing City is a required field",
			"billing_state" => "Billing State is a required field",
			"billing_zip" => "Billing Zip code is a required field",
			"shipping_address_1" => "Shipping Address is a required field",
			"shipping_city" => "Shipping City is a required field",
			"shipping_state" => "Shipping State is a required field",
			"shipping_zip" => "Shipping Zip code is a required field",

			"pay_date1" => "Pay Date 1 is invalid.",
			"pay_date2" => "Pay Date 2 is invalid.",
			
			//SST Mortgage Sites
			"mortgage_lender_first" => "1st Mortgage Lender is a required field.",
			"loan_type" => "Mortgage Loan Type is a required field.",
			"mortgage_first_monthly_payment" => "Monthly Payment is invalid.",
			"mortgage_first_months_behind" => "Months Behind is a required field.",
			"home_purchase_price" => "Home Purchase Price is invalid.",
			"owed_balance" => "Owed Balance is invalid.",

			//SST d1 Sites
			"legal_id_number" => "Legal Id is a required field.",
			"legal_id_type" => "Legal Id Type is  a required field.",
			"card_id" => "Card Number is a required field.",			

			// PAY DATE ERRORS - In the past
			"pay_date1_past" => "Pay Date 1 is in the past.",
			"pay_date2_past" => "Pay Date 2 is in the past.",
			"pay_date3_past" => "Pay Date 3 is in the past.",
			"pay_date4_past" => "Pay Date 4 is in the past.",

			// PAY DATE ERRORS - On a holiday
			"pay_date1_weekend_holiday" => "Pay Date 1 lands on holiday or weekend.",
			"pay_date2_weekend_holiday" => "Pay Date 2 lands on holiday or weekend.",
			"pay_date3_weekend_holiday" => "Pay Date 3 lands on holiday or weekend.",
			"pay_date4_weekend_holiday" => "Pay Date 4 lands on holiday or weekend.",

			// PAY DATE ERRORS - Too many
			"too_many_twice_monthly" => "You have too many paydates in one month.",
			"too_many_monthly" => "You have too many paydates in one month",

			// PAY DATE ERRORS - Less than 7 days
			"pay_date2_weekly" => "Pay Date 2 is not 7 days from Pay Date 1.",
			"pay_date3_weekly" => "Pay Date 3 is not 7 days from Pay Date 1.",
			"pay_date4_weekly" => "Pay Date 4 is not 7 days from Pay Date 1.",

			// PAY DATE ERRORS - Less than 14 days
			"pay_date2_biweekly" => "Pay Date 2 is not 14 days from Pay Date 1.",
			"pay_date3_biweekly" => "Pay Date 3 is not 14 days from Pay Date 1.",
			"pay_date4_biweekly" => "Pay Date 4 is not 14 days from Pay Date 1.",

			// ENTERPRISE CUSTOMER SERVICE PAY DATE ERRORS
			"pay_date3_before_date2" => "Pay Date 3 is before Pay Date 2",
			"pay_date4_before_date3" => "Pay Date 4 is before Pay Date 3",

			// ENTERPRISE CUSTOMER SERVICE PAY DATE ERRORS
			"ent_pay_date3" => "Pay Date 3 is invalid",
			"ent_pay_date4" => "Pay Date 4 is invalid",
			"pay_date3_before_date2" => "Pay Date 3 is before Pay Date 2",
			"pay_date4_before_date3" => "Pay Date 4 is before Pay Date 3",


			// PAY DATE ERRORS - Too far in the future
			"pay_date1_weekly_far" => "Pay Date 1 starts too far in the future.",
			
			// PAY DATE ERRORS - Too far in the future
			"userid" => "Please enter a User ID for this Client.",
			"password" => "Please enter a Password for this Client.",
			"ipaddress" => "Please enter an IP Address for this Client.",
			"name_short" => "Name Short is a required field.",
			"url_live" => "URL Live is a required field.",
			"url_test" => "URL Test is a required field.",
			"port_live" => "Port Live is a required field.",
			"port_test" => "Port Test is a required field.",
			
			// Used in emv ccr stuff
			"offers_not_checked"	=> "You must respond 'Yes' or 'No' to each of the displayed offers.",
			"partial_data"			=> "Please fill in fields completely.",
			"at_least_one"			=> "At least one entry is required.",
			"offers_too_few"		=> "You have not responded 'Yes' to enough of the displayed offers.",

			// CCR:	PrimeQ offer stuff
			"hair_loss_time" 		=> "Please respond with how long you have been experiencing hair loss.",
			"hair_MD_before" 		=> "Please indicate if you have seen a doctor for hair transplantation before.",
			"hair_MD_consult" 		=> "Please indicate if you would be interested in a consultation with a doctor.",
			"have_health_ins"		=> "You must state whether you currently have any health insurance.",
			"household_size"		=> "Please respond with the number of persons in your household.",
			"pregnant_status"		=> "You must state whether you are currently pregnant.",
			"checking_or_cc"		=> "You must indicate whether you have access to a checking account or major credit card.",
			"medicaid_status"		=> "Please state whether you are currently receiving or have applied for Medicaid.",
			"ssn_required"			=> "Social Security Number is required.",
			"state_id_state"		=> "You must respond with state of drivers license.",
			"state_id_invalid"		=> "Driver's License / State ID Number appears to be invalid.",
			"diabetes_condition"	=> "Please state whether you have diabetes.",
			"medicare_coverage"		=> "Please indicate whether you are covered by Medicare.",
			"car_make"				=> "You must provide your car make.",
			"car_model"				=> "Please provide your car model.",
			"car_year"				=> "You must provide your car year.",
			"odometer_mileage"		=> "You must enter a valid number for your odomoter mileage.",
			"vehicle_4WD"			=> "Please state if your vehicle has 4-wheel drive.",
			"vehicle_fuel"			=> "You must indicate whether your vehicle runs on gas or diesel fuel.",
			"vehicle_warranty_left"	=> "Do you have at least 1 month and 1000 miles remaining on your existing warranty?",
			"ssn4_required"			=> "You must provide the last 4 digits of your Social Security Number for security verification purposes.",
			"pref_pmt_method"		=> "Please provide your preferred method of payment if you are interested in this offer.",
			"have_valid_cc"			=> "You must indicate whether you have a valid credit card.",
			"authorize_call_reqd"	=> "You must either authorize contact by telephone or indicate that you're no longer interested.",
			// CCR: eMarketMakers offer stuff
			"own_or_rent"			=> "You must state whether you currently own or rent your primary residence.",
			"offer_opt_in"			=> "You must either opt in to the selected offer or indicate that you're no longer interested.",
			"authorize_call"		=> "Please provide your authorization for contact by phone.",
			"credit_self_assess"	=> "Please indicate how you would rate your credit.",
												

			
			// BB - EZM
			"ezm_nsf_count" => "You may not have 4 or more NSFs",
			"ezm_signature" => "Please type your signature",
			"ezm_terms" => "You must agree to the Terms and Conditions",
			
			//  PAYDATE WIZARD ERRORS
			"frequency" => "You must indicate how often you are paid",
			"weekly_day" => "You must indicate what day you are paid",
			"biweekly_day" => "You must indicate what day you are paid",
			"biweekly_date" => "You must choose the most recent pay date",
			"twicemonthly_type" => "You must choose whether you are paid on a date or day of the week",
			"twicemonthly_date1" => "You must indicate your 1st paydate for the month",
			"twicemonthly_date2" => "You must indicate your 2nd paydate for the month",
			"twicemonthly_week" => "You must indicate which weeks you get paid each month",
			"twicemonthly_day" => "You must indicate what day you are paid",
			"twicemonthly_order" => "The second paydate must be later than the first paydate",
			"monthly_type" => "You must indicate when you are paid each month",
			"monthly_date" => "You must indicate which day you are paid each month",
			"monthly_week" => "You must choose which week of the month you are paid",
			"monthly_day" => "You must choose which day of the week you are paid",
			"monthly_after_date" => "You must choose which day of the week you are paid",
			"monthly_after_day" => "You must indicate the appropriate day",
			
			//  RE-APPLY ERRORS
			"dob_match" => "Your Date of Birth does not match our records.",
			"incorrect_link" => "Your e-mail link appears to be incomplete. If you used cut and paste to insert the link, please check that you included the entire link.",
			"rec_nomatch" => "The social security number does not match our records.",
			"too_many_attempts" => "You have attempted to login incorrectly too many times using this link. Please reapply below.",
			"already_applied" => "Our records indicate you have already submitted an application with this link.",
			"link_bad" => "Your link appears to be incorrect or may have expired.  Please reapply below."	
			);
	
	public static function Get_Error($field_name)
	{
		if ($message = self::$error_strings[strtolower($field_name)])
		{
			return $message;
		}
		return FALSE;
	}
}