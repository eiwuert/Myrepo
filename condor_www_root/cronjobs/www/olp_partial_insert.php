<?php

function Partial_Insert_Database($sql, $property)
{
	// set partial database to insert into
	$PARTIAL_DB = "olp_".$property."_partial";

	$query = "INSERT INTO campaign_info (application_id, promo_id, promo_sub_code, license_key, url) VALUES ('".$_SESSION["application_id"]."','".$_SESSION["config"]->promo_id."','".$_SESSION["config"]->promo_sub_code."','".$_SESSION["config"]->license."','".$_SESSION["config"]->site_name."')";
	$result = $sql->Query ($PARTIAL_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	if($_SESSION["data"]["bank_name"])
	{
		$query = "INSERT INTO bank_info_encrypted (application_id, bank_name, account_number, routing_number, check_number, direct_deposit) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["data"]["bank_name"]."','".$_SESSION["data"]["account_number"]."','".$_SESSION["data"]["routing_number"]."','".$_SESSION["data"]["check_number"]."','".$_SESSION["data"]["direct_deposit"]."')";
		$result = $sql->Query ($PARTIAL_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if($_SESSION["data"]["phone_work"])
	{
		$query = "INSERT INTO employment (application_id, employer, work_phone, work_ext, title, shift, date_of_hire, income_type) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["data"]["employer_name"]."','".$_SESSION["data"]["phone_work"]."','".$_SESSION["data"]["work_ext"]."','".$_SESSION["data"]["title"]."','".$_SESSION["data"]["shift"]."','".$_SESSION["data"]["date_of_hire"]."','".$_SESSION["data"]["income_type"]."')";
		$result = $sql->Query ($PARTIAL_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if($_SESSION["data"]["income_monthly_net"])
	{
		$query = "INSERT INTO income (application_id, net_pay, pay_frequency, pay_date_1, pay_date_2, pay_date_3, pay_date_4) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["data"]["income_monthly_net"]."', '".$_SESSION["data"]["income_frequency"]."', '".$_SESSION["pay_dates"]["pay_date1"]."', '".$_SESSION["pay_dates"]["pay_date2"]."', '".$_SESSION["pay_dates"]["pay_date3"]."', '".$_SESSION["pay_dates"]["pay_date4"]."')";
		$result = $sql->Query ($PARTIAL_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if($_SESSION["data"]["fund_date"])
	{
		$query = "INSERT INTO loan_note (application_id, estimated_fund_date, fund_amount, estimated_payoff_date, apr, finance_charge, total_payments) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["loan_note"]["fund_date"]."', '".$_SESSION["data"]["fund_amount"]."', '".$_SESSION["data"]["payoff_date"]."', '".$_SESSION["data"]["apr"]."', '".$_SESSION["data"]["finance_charge"]."', '".$_SESSION["data"]["total_payments"]."')";
		$result = $sql->Query ($PARTIAL_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if($_SESSION["data"]["ref_01_name_full"])
	{
		$query = "INSERT INTO personal_contact (application_id, full_name, phone, relationship) VALUES ('".$_SESSION["application_id"]."','".$_SESSION["data"]["ref_01_name_full"]."', '".$_SESSION["data"]["ref_01_phone_home"]."', '".$_SESSION["data"]["ref_01_relationship"]."')";
		$result = $sql->Query ($PARTIAL_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		$insert_id_1 = $sql->Insert_Id();
	}
	
	if($_SESSION["data"]["ref_02_name_full"])
	{
		$query = "INSERT INTO personal_contact (application_id, full_name, phone, relationship) VALUES ('".$_SESSION["application_id"]."','".$_SESSION["data"]["ref_02_name_full"]."', '".$_SESSION["data"]["ref_02_phone_home"]."', '".$_SESSION["data"]["ref_02_relationship"]."')";
		$result = $sql->Query ($PARTIAL_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		$insert_id_2 = $sql->Insert_Id();
	}

	if($_SESSION["data"]["email_primary"])
	{
		$query = "INSERT INTO personal_encrypted (application_id, first_name, middle_name, last_name, home_phone, cell_phone, fax_phone, email, date_of_birth, social_security_number, drivers_license_number, contact_id_1, contact_id_2) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["data"]["name_first"]."', '".$_SESSION["data"]["name_middle"]."', '".$_SESSION["data"]["name_last"]."', '".$_SESSION["data"]["phone_home"]."', '".$_SESSION["data"]["phone_cell"]."', '".$_SESSION["data"]["phone_fax"]."', '".$_SESSION["data"]["email_primary"]."', '".$_SESSION["data"]["dob"]."', '".$_SESSION["data"]["social_security_number"]."', '".$_SESSION["data"]["state_id_number"]."', '".$insert_id_1."', '".$insert_id_2."')";
		$result = $sql->Query ($PARTIAL_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if($_SESSION["data"]["home_street"])
	{
		$query = "INSERT INTO residence (application_id, residence_type, length_of_residence, address_1, apartment, city, state, zip) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["data"]["residence_type"]."', '".$_SESSION["data"]["length_of_residence"]."', '".$_SESSION["data"]["home_street"]."', '".$_SESSION["data"]["home_unit"]."', '".$_SESSION["data"]["home_city"]."', '".$_SESSION["data"]["home_state"]."', '".$_SESSION["data"]["home_zip"]."')";
		$result = $sql->Query ($PARTIAL_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}
}
?>
