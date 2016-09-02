<?php

function Partial_Insert_Database($sql, $property, $db_exact="", &$data)
{
	
	//echo "  PID\n";
	//ob_flush();
	//flush();
	// set partial database to insert into
	if($db_exact!="")
	{
		$PARTIAL_DB = $db_exact;
	}
	else
	{
		$PARTIAL_DB = "olp_" . $property . "_partial";
	}

	$query = "
	INSERT INTO campaign_info (
		application_id,
		promo_id,
		promo_sub_code,
		license_key,
		ip_address,
		url,
		created_date,
		scrubbed_date
	) VALUES (
		'" . mysql_escape_string($data["application_id"]) . "',
		'" . mysql_escape_string($data["config"]->promo_id) . "',
		'" . mysql_escape_string($data["config"]->promo_sub_code) . "',
		'" . mysql_escape_string($data["config"]->license) . "',
		'" . mysql_escape_string($data["data"]["client_ip_address"]) . "',
		'" . mysql_escape_string($data["config"]->site_name) . "',
		'" . mysql_escape_string($data["date_modified"]) . "',
		NOW()
		
	)";

	$result = $sql->Query ($PARTIAL_DB, $query);
	//sleep(1);
	/*
		the old way
	if ($data["data"]["bank_name"])
	this doesnt always work. there are some sites that we get other
	bank_info fields before we get bank_name.  ex: budgetcashloans.com
	we collect direct deposit on the first page, but get bank name on the 2nd
	so in that case we're ditching DD info
	*/
	if (
		// Much better.....
		   $data["data"]["bank_name"]
		|| $data["data"]["bank_account"]
		|| $data["data"]["bank_aba"]
		|| $data["data"]["check_number"]
		
		// hack explained below.
		|| $data["data"]["direct_deposit"]
		|| $data["data"]["income_direct_deposit"]
		)
	{
		$query = "
		INSERT INTO bank_info (
			application_id,
			bank_name,
			account_number,
			routing_number,
			check_number,
			direct_deposit
		) VALUES (
			'" . mysql_escape_string($data["application_id"]) . "',
			'" . mysql_escape_string($data["data"]["bank_name"]) . "',
			'" . mysql_escape_string($data["data"]["bank_account"]) . "',
			'" . mysql_escape_string($data["data"]["bank_aba"]) . "',
			'" . mysql_escape_string($data["data"]["check_number"]) . "',
			'" . mysql_escape_string(
				// this is a HACK, children!
				// i did this because apparently sometimes its named one, and other times
				// it is named something else.
				// so its "fixed", bitches!
					$data["data"]["direct_deposit"] ? 
					$data["data"]["direct_deposit"] :
					$data["data"]["income_direct_deposit"]
				) . "'
		)";
		try
		{
			$result = $sql->Query ($PARTIAL_DB, $query);
		}
		catch(Exception $e)
		{
			//echo 'Caught exception: ', $e->getMessage(),"\n";
		}
	}

	if ($data["data"]["phone_work"])
	{
		$query = "
		INSERT INTO employment (
			application_id,
			employer,
			work_phone,
			work_ext,
			title,
			shift,
			date_of_hire,
			income_type
		) VALUES (
			'" . mysql_escape_string($data["application_id"]) . "',
			'" . mysql_escape_string($data["data"]["employer_name"]) . "',
			'" . mysql_escape_string($data["data"]["phone_work"]) . "',
			'" . mysql_escape_string($data["data"]["work_ext"]) . "',
			'" . mysql_escape_string($data["data"]["title"]) . "',
			'" . mysql_escape_string($data["data"]["shift"]) . "',
			'" . mysql_escape_string($data["data"]["date_of_hire"]) . "',
			'" . mysql_escape_string($data["data"]["income_type"]) . "'
		)";
		try
		{
			$result = $sql->Query ($PARTIAL_DB, $query);
		}
		catch(Exception $e)
		{
			//echo 'Caught exception: ', $e->getMessage(),"\n";
		}
	}

	if ($data["data"]["income_monthly_net"])
	{
		/***
			added 02-08-2005
			--
			in applications where the user dropped after page1..
			the session will hold income frequency only in the data->paydate subarray
			in apps that have progressed farther, income frequency is in data->income_frequency
			this workaround stops income frequency from being discarded on page1 drop apps.
		***/
		if ( $data["data"]["income_frequency"] )
		{
			$pf = $data["data"]["income_frequency"];
		}
		else if ( $data["data"]["paydate"]["frequency"] )
		{
			$pf = $data["data"]["paydate"]["frequency"];
		}
		else
		{
			$pf = "";
		}
		
		$query = "
		INSERT INTO income (
			application_id,
			net_pay,
			pay_frequency,
			pay_date_1,
			pay_date_2,
			pay_date_3,
			pay_date_4
		) VALUES (
			'" . mysql_escape_string($data["application_id"]) . "',
			'" . mysql_escape_string($data["data"]["income_monthly_net"]) . "',
			'" . mysql_escape_string($pf) . "',
			'" . mysql_escape_string($data["pay_dates"]["pay_date1"]) . "',
			'" . mysql_escape_string($data["pay_dates"]["pay_date2"]) . "',
			'" . mysql_escape_string($data["pay_dates"]["pay_date3"]) . "',
			'" . mysql_escape_string($data["pay_dates"]["pay_date4"]) . "')";
	
		try
		{
			$result = $sql->Query ($PARTIAL_DB, $query);
		}
		catch(Exception $e)
		{
			//echo 'Caught exception: ', $e->getMessage(),"\n";
		}
	}

	if ($data["data"]["fund_date"])
	{
		$query = "
		INSERT INTO loan_note (
			application_id,
			estimated_fund_date,
			fund_amount,
			estimated_payoff_date,
			apr,
			finance_charge,
			total_payments
		) VALUES (
			'" . mysql_escape_string($data["application_id"]) . "',
			'" . mysql_escape_string($data["loan_note"]["fund_date"]) . "',
			'" . mysql_escape_string($data["data"]["fund_amount"]) . "',
			'" . mysql_escape_string($data["data"]["payoff_date"]) . "',
			'" . mysql_escape_string($data["data"]["apr"]) . "',
			'" . mysql_escape_string($data["data"]["finance_charge"]) . "',
			'" . mysql_escape_string($data["data"]["total_payments"]) . "'
		)";
		try
		{
			$result = $sql->Query ($PARTIAL_DB, $query);
		}
		catch(Exception $e)
		{
			//echo 'Caught exception: ', $e->getMessage(),"\n";
		}
	}

	if ($data["data"]["ref_01_name_full"])
	{
		$query = "
		INSERT INTO personal_contact (
			application_id,
			full_name,
			phone,
			relationship
		) VALUES (
			'" . mysql_escape_string($data["application_id"]) . "',
			'" . mysql_escape_string($data["data"]["ref_01_name_full"]) . "',
			'" . mysql_escape_string($data["data"]["ref_01_phone_home"]) . "',
			'" . mysql_escape_string($data["data"]["ref_01_relationship"]) . "'
		)";
		try
		{
			$result = $sql->Query ($PARTIAL_DB, $query);
		}
		catch(Exception $e)
		{
			//echo 'Caught exception: ', $e->getMessage(),"\n";
		}
		$insert_id_1 = $sql->Insert_Id();
	}

	if	($data["data"]["ref_02_name_full"])
	{
		$query = "
		INSERT INTO personal_contact (
			application_id,
			full_name,
			phone,
			relationship
		) VALUES (
			'" . mysql_escape_string($data["application_id"]) . "',
			'" . mysql_escape_string($data["data"]["ref_02_name_full"]) . "',
			'" . mysql_escape_string($data["data"]["ref_02_phone_home"]) . "',
			'" . mysql_escape_string($data["data"]["ref_02_relationship"]) . "'
		)";
		try
		{
			$result = $sql->Query ($PARTIAL_DB, $query);
		}
		catch(Exception $e)
		{
			//echo 'Caught exception: ', $e->getMessage(),"\n";
		}
		$insert_id_2 = $sql->Insert_Id();
	}
	

	if ($data["data"]["email_primary"])
	{
		$date_of_birth = "{$data["data"]["date_dob_y"]}-{$data["data"]["date_dob_m"]}-{$data["data"]["date_dob_d"]}";
	
		$query = "
		INSERT INTO personal (application_id,
			first_name,
			middle_name,
			last_name,
			home_phone,
			cell_phone,
			fax_phone,
			email,
			date_of_birth,
			social_security_number,
			drivers_license_number,
			contact_id_1,
			contact_id_2,
			best_call_time
		) VALUES (
			'" . mysql_escape_string($data["application_id"]) . "',
			'" . mysql_escape_string($data["data"]["name_first"]) . "',
			'" . mysql_escape_string($data["data"]["name_middle"]) . "',
			'" . mysql_escape_string($data["data"]["name_last"]) . "',
			'" . mysql_escape_string($data["data"]["phone_home"]) . "',
			'" . mysql_escape_string($data["data"]["phone_cell"]) . "',
			'" . mysql_escape_string($data["data"]["phone_fax"]) . "',
			'" . mysql_escape_string($data["data"]["email_primary"]) . "',
			'" . mysql_escape_string($date_of_birth) . "',
			'" . mysql_escape_string($data["data"]["social_security_number"]) . "',
			'" . mysql_escape_string($data["data"]["state_id_number"]) . "',
			'" . intval($insert_id_1) . "',
			'" . intval($insert_id_2) . "',
			'" . mysql_escape_string($data["data"]["best_call_time"]) ."'
		)";
		try
		{
			$result = $sql->Query ($PARTIAL_DB, $query);
		}
		catch(Exception $e)
		{
			//echo 'Caught exception: ', $e->getMessage(),"\n";
		}
	}

	if ($data["data"]["home_street"])
	{
		$query = "
		INSERT INTO residence (
			application_id,
			residence_type,
			length_of_residence,
			address_1,
			apartment,
			city,
			state,
			zip
		) VALUES (
			'" . mysql_escape_string($data["application_id"]) . "',
			'" . mysql_escape_string($data["data"]["residence_type"]) . "',
			'" . mysql_escape_string($data["data"]["length_of_residence"]) . "',
			'" . mysql_escape_string($data["data"]["home_street"]) . "',
			'" . mysql_escape_string($data["data"]["home_unit"]) . "',
			'" . mysql_escape_string($data["data"]["home_city"]) . "',
			'" . mysql_escape_string($data["data"]["home_state"]) . "',
			'" . mysql_escape_string($data["data"]["home_zip"]) . "'
		)";
		try
		{
			$result = $sql->Query ($PARTIAL_DB, $query);
		}
		catch(Exception $e)
		{
			//echo 'Caught exception: ', $e->getMessage(),"\n";
		}
		$address_id = $sql->Insert_Id();
	}

	if (
		// isnt talk like a pirate day in september??
		# yarrr! salvage what we can, mateys!
		$data["data"]["employer_name"]
		|| $data["data"]["phone_work"]
		|| $data["data"]["phone_work_ext"]
		|| $data["data"]["employer_title"]
		|| $data["data"]["employer_shift"]
		|| $data["data"]["date_hire"]
		|| $data["data"]["income_type"]
	)
	{
		$query = "
		INSERT INTO employment (
			application_id,
			employer,
			address_id,
			work_phone,
			work_ext,
			title,
			shift,
			date_of_hire,
			income_type
		) VALUES (
			NULL,
			'" . mysql_escape_string($data["data"]["employer_name"]) . "',
			" . (isset($address_id) ? intval($address_id) : "NULL") . ",
			'" . mysql_escape_string($data["data"]["phone_work"]) . "',
			'" . mysql_escape_string($data["data"]["phone_work_ext"]) . "',
			'" . mysql_escape_string($data["data"]["employer_title"]) . "',
			'" . mysql_escape_string($data["data"]["employer_shift"]) . "',
			'" . mysql_escape_string($data["data"]["date_hire"]) . "',
			'" . mysql_escape_string($data["data"]["income_type"]) . "'
		)";
		try
		{
			$result = $sql->Query ($PARTIAL_DB, $query);
		}
		catch(Exception $e)
		{
			//echo 'Caught exception: ', $e->getMessage(),"\n";
		}
	}

}
?>
