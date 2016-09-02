<?php
define("VISITOR_DB", "olp_pcl_partial");
function Partial_Insert_Database($sql)
{
	//print_r($_SESSION);
	//exit;

	$query = "INSERT INTO campaign_info (application_id, promo_id, promo_sub_code, license_key, url) VALUES ('".$_SESSION["application_id"]."','".$_SESSION["config"]->promo_id."','".$_SESSION["promo"]["promo_sub_code"]."','".$_SESSION["config"]->license."','".$_SESSION["config"]->site_name."')";
	$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	if(is_array($_SESSION["bank_info"]))
	{
		$query = "INSERT INTO bank_info (application_id, bank_name, account_number, routing_number, check_number, direct_deposit) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["bank_info"]["bank_name"]."','".$_SESSION["bank_info"]["account_number"]."','".$_SESSION["bank_info"]["routing_number"]."','".$_SESSION["bank_info"]["check_number"]."','".$_SESSION["bank_info"]["direct_deposit"]."')";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if(is_array($_SESSION["employment"]))
	{
		$query = "INSERT INTO employment (application_id, employer, work_phone, work_ext, title, shift, date_of_hire, income_type) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["employment"]["employer"]."','".$_SESSION["employment"]["work_phone"]."','".$_SESSION["employment"]["work_ext"]."','".$_SESSION["employment"]["title"]."','".$_SESSION["employment"]["shift"]."','".$_SESSION["employment"]["dohy"]."-".$_SESSION["employment"]["dohm"]."-".$_SESSION["employment"]["dohd"]."','".$_SESSION["employment"]["income_type"]."')";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if(is_array($_SESSION["income"]))
	{
		$query = "INSERT INTO income (application_id, net_pay, pay_frequency, pay_date_1, pay_date_2, pay_date_3, pay_date_4) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["income"]["net_pay"]."', '".$_SESSION["income"]["pay_frequency"]."', '".$_SESSION["income"]["pay_date_1"]."', '".$_SESSION["income"]["pay_date_2"]."', '".$_SESSION["income"]["pay_date_3"]."', '".$_SESSION["income"]["pay_date_4"]."')";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if(is_array($_SESSION["loan_note"]))
	{
		$query = "INSERT INTO loan_note (application_id, estimated_fund_date, fund_amount, estimated_payoff_date, apr, finance_charge, total_payments) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["loan_note"]["fund_date"]."', '".$_SESSION["loan_note"]["fund_amount"]."', '".$_SESSION["loan_note"]["payoff_date"]."', '".$_SESSION["loan_note"]["apr"]."', '".$_SESSION["loan_note"]["finance_charge"]."', '".$_SESSION["loan_note"]["total_payments"]."')";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if(is_array($_SESSION["personal_contact"]))
	{
		$query = "INSERT INTO personal_contact (application_id, full_name, phone, relationship) VALUES ('".$_SESSION["application_id"]."','".$_SESSION["personal_contact"]["name_1"]."', '".$_SESSION["personal_contact"]["phone_1"]."', '".$_SESSION["personal_contact"]["relationship_1"]."')";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		$insert_id_1 = $sql->Insert_Id();

		$query = "INSERT INTO personal_contact (application_id, full_name, phone, relationship) VALUES ('".$_SESSION["application_id"]."','".$_SESSION["personal_contact"]["name_2"]."', '".$_SESSION["personal_contact"]["phone_2"]."', '".$_SESSION["personal_contact"]["relationship_2"]."')";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		$insert_id_2 = $sql->Insert_Id();
	}

	if(is_array($_SESSION["personal"]))
	{
		$query = "INSERT INTO personal (application_id, first_name, middle_name, last_name, home_phone, cell_phone, fax_phone, email, date_of_birth, social_security_number, drivers_license_number, contact_id_1, contact_id_2) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["personal"]["first_name"]."', '".$_SESSION["personal"]["middle_name"]."', '".$_SESSION["personal"]["last_name"]."', '".$_SESSION["personal"]["home_phone"]."', '".$_SESSION["personal"]["cell_phone"]."', '".$_SESSION["personal"]["fax_phone"]."', '".$_SESSION["personal"]["email"]."', '".$_SESSION["personal"]["date_of_birth"]."', '".$_SESSION["personal"]["social_security_1"].$_SESSION["personal"]["social_security_2"].$_SESSION["personal"]["social_security_3"]."', '".$_SESSION["personal"]["drivers_license_number"]."', '".$insert_id_1."', '".$insert_id_2."')";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if(is_array($_SESSION["residence"]))
	{
		$query = "INSERT INTO residence (application_id, residence_type, length_of_residence, address_1, apartment, city, state, zip) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["residence"]["residence_type"]."', '".$_SESSION["residence"]["length_of_residence"]."', '".$_SESSION["residence"]["address_1"]."', '".$_SESSION["residence"]["apartment"]."', '".$_SESSION["residence"]["city"]."', '".$_SESSION["residence"]["state"]."', '".$_SESSION["residence"]["zip"]."')";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}
}
?>