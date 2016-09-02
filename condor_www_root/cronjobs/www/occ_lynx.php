<?php
ini_set ("session.use_cookies", 0);

$file_path = "/virtualhosts/cronjobs/atm/";

$file_name = $file_path.$_SERVER["argv"][1];

$sql_host = 'selsds001';
$sql_user = 'sellingsource';
$sql_pass = "%selling\$_db";

$site_url = 'oneclickcash.com';

//$license_key = 'c9a110aefde22fd5ddf645e79d7fc12e';
$license_key = '3dc1d0a6adce81fba79d7f075107a32a'; // LIVE
//$license_key = 'feb77f8f4d553a64e26fd0de36005a1a'; // RC

$promo_id = 12016;
$promo_sub_code = '';


define ("SQL_BASE", "olp_pcl_visitor");
define ("URL_BASE", "oneclickcash.com");

define ("VISITOR_DB", SQL_BASE);

require_once ('mysql.3.php');
require_once ('security.3.php');
require_once ('null_session.1.php');
require_once ('setstat.1.php');
require_once ('config.3.php');
require_once ('ole_mail.2.php');

require_once ('/virtualhosts/soapdataserver.com/olp.2/live/include/code/occ_app/qualify.class.php');


if (! ($fp = fopen ($file_name, "r")))
{
	echo "Unable to open ".$file_name."\n";
	exit;
}

$sql = new MySQL_3 ();
Error_2::Error_Test (
	$sql->Connect (NULL, $sql_host, $sql_user, $sql_pass, Debug_1::Trace_Code (__FILE__, __LINE__)), TRUE
);

$session = new Null_Session_1 ();
@session_start ();

//build the holiday array for use in javascript and later in the qualify class
if(! $_SESSION["holiday_array"])
{
	$query = "SELECT * from holidays WHERE date > '".date("Y-m-d")."' and date < '".date("Y-m-d", strtotime("+4 months"))."' ";
	$result = $sql->Query ("d2_management", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	while($hol_array = $sql->Fetch_Array_Row($result))
	{
		$holiday_array[] = $hol_array["date"];
	}
}


// Hit the stat
$config = Config_3::Get_Site_Config ($license_key, $promo_id, $promo_sub_code);
$stat_info = Set_Stat_1::Setup_Stats ($config->site_id, $config->vendor_id, $config->page_id, $config->promo_id, $promo_sub_code, $sql, $config->stat_base, $config->promo_status, NULL);
Set_Stat_1::Set_Stat ($stat_info->block_id, $stat_info->tablename, $sql, $config->stat_base, 'visitors', 1);



for ($i = 0 ; $row = fgetcsv ($fp, 8192, ',', '"') ; $i++)
{
	if (! $i)
		continue;

	$_SESSION["promo"]["promo_id"] = $promo_id;
	$_SESSION["promo"]["promo_sub_code"] = '';

	$_SESSION["personal"]["first_name"] = mysql_escape_string ($row[0]);
	$_SESSION["personal"]["last_name"] = mysql_escape_string ($row[1]);

	$gender = $row[2];

	$_SESSION["personal"]["date_of_birth"] = date ('Y-m-d', strtotime ($row[3]));

	$_SESSION["residence"]["address_1"] = mysql_escape_string ($row[4]);
	$_SESSION["residence"]["city"] = mysql_escape_string ($row[5]);
	$_SESSION["residence"]["state"] = mysql_escape_string ($row[6]);
	$_SESSION["residence"]["zip"] = mysql_escape_string ($row[7]);

	$_SESSION["personal"]["home_phone"] = preg_replace ('/[^\d]/', '', $row[8]);
	$_SESSION["personal"]["cell_phone"] = preg_replace ('/[^\d]/', '', $row[9]);
	$_SESSION["personal"]["email"] = mysql_escape_string ($row[10]);

	$_SESSION["employment"]["employer"] = mysql_escape_string ($row[11]);
	$_SESSION["employment"]["work_phone"] = preg_replace ('/[^\d]/', '', $row[12]);

	$_SESSION["income"]["net_pay"] = preg_replace ('/[^\d.]/', '', $row[13]);
	$_SESSION["income"]["pay_frequency"] = strtoupper (str_replace ('-', '_', $row[14]));

	$_SESSION["income"]["pay_date_1"] = date ('Y-m-d', strtotime ($row[15]));
	$_SESSION["income"]["pay_date_2"] = date ('Y-m-d', strtotime ($row[16]));

	$_SESSION["bank_info"]["routing_number"] = preg_replace ('/[^\d]/', '', $row[17]);
	$_SESSION["bank_info"]["direct_deposit"] = mysql_escape_string (strtoupper ($row[18]) == "Y" ? "TRUE" : "FALSE");

	$_SESSION["completed"]["step1"] = TRUE;
	$_SESSION["completed"]["step2"] = TRUE;

	//print_r ($_SESSION); exit;

	$_SESSION["holiday_array"] = $holiday_array;

	$epm = new epm_collect (
		$_SESSION["personal"]["email"], $_SESSION["personal"]["first_name"], $_SESSION["personal"]["last_name"],
		$config->ole_site_id, $config->ole_list_id, "127.0.0.1"
	);

	$qualify = new Qualify ();
	if (! $qualify->Qualify_Amount ($sql, NULL))
	{
		// TODO: handle qual error
		// echo "THHHPT: we didnt qualify :(\n";	print_r($qualify->errors);

		// Build the header
		$header = new StdClass ();
		$header->url = "oneclickcash.com";
		$header->subject = "Your payday loan application";
		$header->sender_name = "Randall Stone";
		$header->sender_address = "info@oneclickcash.com";

		// Build the to recipient
		$recipient = new StdClass ();
		$recipient->type = "to";
		$recipient->name = $_SESSION ["personal"]["first_name"]." ".$_SESSION["personal"]["last_name"];
		$recipient->address = $_SESSION ["personal"]["email"];

		// Build the message
		$message = new StdClass ();
		$message->text =
"

Thank you for applying for a payday loan through OneClickCash.com.  We are sorry to advise you that we cannot grant a loan to you at this time.  You must make at least \$1000 in job income to qualify for a loan.


Please keep us in mind for your future loan needs.

Thank you,
www.OneClickCash.com

";

		exec ("mv ".$file_name." ".$file_path."_fail/", $out, $rc);

		if ($rc)
		{
			echo "mv ".$file_name." ".$file_path."_fail/ failed!\n";
			exit;
		}

		Send_Mail ($header, $message, array ($recipient), 0);

		break;
	}

	$_SESSION["income"]["monthly_net_pay"] = $_SESSION["income"]["net_pay"];

	switch ($_SESSION["income"]["pay_frequency"])
	{
		case "WEEKLY":
			$_SESSION["income"]["net_pay"] = $_SESSION["income"]["monthly_net_pay"] / 4;
			break;

		case "BI_WEEKLY":
		case "TWICE_MONTHLY":
			$_SESSION["income"]["net_pay"] = $_SESSION["income"]["monthly_net_pay"] / 2;
			break;

		case "MONTHLY":
			$_SESSION["income"]["net_pay"] = $_SESSION["income"]["monthly_net_pay"];
			break;
	}


	$query = "INSERT INTO application (created_date, type) VALUES (NOW(), 'PROSPECT')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	$_SESSION["application_id"] = $sql->Insert_Id ();

	//create account
	$uid = md5 (uniqid (posix_getpid().mt_rand(), TRUE));

	define ("PASSWORD_ENCRYPTION", "ENCRYPT");
	$security = new Security_3($sql, SQL_BASE, "account");
	$result = $security->Create_Customer_Account ($uid, $_SESSION["application_id"], $_SESSION["personal"]["email"], Debug_1::Trace_Code (__FILE__, __LINE__));
	if (is_a ($result, "Error_2"))
	{
		$query = "UPDATE account SET active_application_id = '".$_SESSION["application_id"]."' WHERE login = '".$_SESSION["personal"]["email"]."'";
		$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test($result, TRUE);
	}

	$query = "SELECT account_id FROM account WHERE login = '".$_SESSION["personal"]["email"]."' LIMIT 1";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	if (! $sql->Row_Count ($result))
	{
		echo "Account record not found!!!! this should never happen!!\n";
		exit;
	}

	$row = $sql->Fetch_Object_Row ($result);

	$query = "UPDATE application SET account_id = '".$row->account_id."', session_id = '".$uid."' WHERE application_id = ".$_SESSION["application_id"]." LIMIT 1";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	$query = "INSERT INTO campaign_info set license_key = '".$license_key."', application_id = '".$_SESSION["application_id"]."', promo_id = '".$promo_id."', url = '".$site_url."', promo_sub_code = '".$promo_sub_code."', created_date = NOW()";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	$query = "INSERT INTO bank_info (application_id, bank_name, account_number, routing_number, check_number, direct_deposit) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["bank_info"]["bank_name"]."','".$_SESSION["bank_info"]["account_number"]."','".$_SESSION["bank_info"]["routing_number"]."','".$_SESSION["bank_info"]["check_number"]."','".$_SESSION["bank_info"]["direct_deposit"]."')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	$query = "INSERT INTO employment (application_id, employer, work_phone, work_ext, title, shift, date_of_hire, income_type) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["employment"]["employer"]."','".$_SESSION["employment"]["work_phone"]."','".$_SESSION["employment"]["work_ext"]."','".$_SESSION["employment"]["title"]."','".$_SESSION["employment"]["shift"]."','".$_SESSION["employment"]["dohy"]."-".$_SESSION["employment"]["dohm"]."-".$_SESSION["employment"]["dohd"]."','".$_SESSION["employment"]["income_type"]."')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	$query = "INSERT INTO income (application_id, net_pay, pay_frequency, pay_date_1, pay_date_2, pay_date_3, pay_date_4) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["income"]["net_pay"]."', '".$_SESSION["income"]["pay_frequency"]."', '".$_SESSION["income"]["pay_date_1"]."', '".$_SESSION["income"]["pay_date_2"]."', '".$_SESSION["income"]["pay_date_3"]."', '".$_SESSION["income"]["pay_date_4"]."')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	$query = "INSERT INTO residence (application_id, residence_type, length_of_residence, address_1, apartment, city, state, zip) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["residence"]["residence_type"]."', '".$_SESSION["residence"]["length_of_residence"]."', '".$_SESSION["residence"]["address_1"]."', '".$_SESSION["residence"]["apartment"]."', '".$_SESSION["residence"]["city"]."', '".$_SESSION["residence"]["state"]."', '".$_SESSION["residence"]["zip"]."')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	$query = "INSERT INTO personal_contact (application_id, full_name, phone, relationship) VALUES ('".$_SESSION["application_id"]."','".$_SESSION["personal_contact"]["name_1"]."', '".$_SESSION["personal_contact"]["phone_1"]."', '".$_SESSION["personal_contact"]["relationship_1"]."')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);
	$insert_id_1 = $sql->Insert_Id();

	$query = "INSERT INTO personal_contact (application_id, full_name, phone, relationship) VALUES ('".$_SESSION["application_id"]."','".$_SESSION["personal_contact"]["name_2"]."', '".$_SESSION["personal_contact"]["phone_2"]."', '".$_SESSION["personal_contact"]["relationship_2"]."')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);
	$insert_id_2 = $sql->Insert_Id();

	$query = "INSERT INTO personal (application_id, first_name, middle_name, last_name, home_phone, cell_phone, fax_phone, email, date_of_birth, social_security_number, drivers_license_number, contact_id_1, contact_id_2) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["personal"]["first_name"]."', '".$_SESSION["personal"]["middle_name"]."', '".$_SESSION["personal"]["last_name"]."', '".$_SESSION["personal"]["home_phone"]."', '".$_SESSION["personal"]["cell_phone"]."', '".$_SESSION["personal"]["fax_phone"]."', '".$_SESSION["personal"]["email"]."', '".$_SESSION["personal"]["date_of_birth"]."', '".$_SESSION["personal"]["social_security_1"].$_SESSION["personal"]["social_security_2"].$_SESSION["personal"]["social_security_3"]."', '".$_SESSION["personal"]["drivers_license_number"]."', '".$insert_id_1."', '".$insert_id_2."')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	$query = "INSERT INTO loan_note (application_id, estimated_fund_date, fund_amount, estimated_payoff_date, apr, finance_charge, total_payments) VALUES ('".$_SESSION["application_id"]."', '".$_SESSION["loan_note"]["fund_date"]."', '".$_SESSION["loan_note"]["fund_amount"]."', '".$_SESSION["loan_note"]["payoff_date"]."', '".$_SESSION["loan_note"]["apr"]."', '".$_SESSION["loan_note"]["finance_charge"]."', '".$_SESSION["loan_note"]["total_payments"]."')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	unset ($_SESSION["holiday_array"], $_SESSION["security"]);

	$_SESSION ['app_prefix'] = 'OCC-ATM-';

	$query = "INSERT INTO session_site (session_id, created_date, session_info) VALUES ('$uid', NOW(), '".mysql_escape_string (session_encode())."')";
	$result = $sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);


	// Hit the stat
	//$config = Config_3::Get_Site_Config ($license_key, $promo_id, $promo_sub_code);
	//$stat_info = Set_Stat_1::Setup_Stats ($config->site_id, $config->vendor_id, $config->page_id, $config->promo_id, $promo_sub_code, $sql, $config->stat_base, $config->promo_status, NULL);
	Set_Stat_1::Set_Stat ($stat_info->block_id, $stat_info->tablename, $sql, $config->stat_base, 'accepted', 1);



	// Prepare the email

	// Build the header
	$header = new StdClass ();
	$header->url = "oneclickcash.com";
	$header->subject = "Your payday loan application";
	$header->sender_name = "Randall Stone";
	$header->sender_address = "info@oneclickcash.com";

	// Build the to recipient
	$recipient = new StdClass ();
	$recipient->type = "to";
	$recipient->name = $_SESSION ["personal"]["first_name"]." ".$_SESSION["personal"]["last_name"];
	$recipient->address = $_SESSION ["personal"]["email"];

	// Build the message
	$message = new StdClass ();
	$message->text =
"
You recently completed a payday loan application at one of our local retail locations.

You have been pre-qualified for an advance of up to $500.00 against your next pay check!

In order to view print your loan documents and receive your cash, please visit us at:

http://".URL_BASE."/?page=view_docs&unique_id=".$uid."

Randall Stone
Payday Loan Specialist

";
	$message->html =
'
<html>
<head>
<title>Get up to $500.00 by Tomorrow!!!</title>
</head>
<body bgcolor="#ffffff">
<table width="550" border="0" align="center" cellpadding="3" cellspacing="0" bgcolor="#000000">
  <tr>
    <td><table width="550" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
        <tr>
          <td colspan="2"><a href="http://'.URL_BASE.'/?page=view_docs&unique_id='.$uid.'" target="_blank"><img src="http://www.imagedataserver.com/nms/generic_emails/email_atm/atmcomplete_r1_c1.jpg" width="550" height="317" border="0"></a></td>
        </tr>
        <tr>
          <td colspan="2"><a href="http://'.URL_BASE.'/?page=view_docs&unique_id='.$uid.'" target="_blank"><img src="http://www.imagedataserver.com/nms/generic_emails/email_atm/atmcomplete_r2_c1.gif" width="243" height="83" border="0"><img src="http://www.imagedataserver.com/nms/generic_emails/email_atm/atmcomplete_r2_c2.gif" width="307" height="83" border="0"></a></td>
        </tr>
      </table></td>
  </tr>
</table>
</body>
</html>
';


	exec ("mv ".$file_name." ".$file_path."_pass/", $out, $rc);

	if ($rc)
	{
		echo "mv ".$file_name." ".$file_path."_pass/ failed!\n";
		exit;
	}

	Send_Mail ($header, $message, array ($recipient), 0);

	break;
}

fclose ($fp);


function Send_Mail ($header, $message, $recipient_array, $try_count)
{
	// Send email to the customer
	require_once ("prpc/client.php");

	// Configure the server
	$server = "prpc://smtp.1.soapdataserver.com/smtp.1.php";
	$debug = FALSE;
	$trace = 0;

	$mail = new Prpc_Client ($server, $debug, $trace);
	$mailing_id = $mail->CreateMailing ("ATM", $header, NULL, NULL);

	if ($mailing_id > 0)
	{
		//echo "Mailing_Id: $mailing_id\n";

		$package_id = $mail->AddPackage ($mailing_id, $recipient_array, $message, array ());

		//echo "Package_Id: $package_id\n";

		$result = $mail->SendMail ($mailing_id);

		//echo "SendMail: $result\n\n";
	}
	else if ($try_count < 3)
	{
		sleep (3);
		Send_Mail ($header, $message, $recipient_array, ++$try_count);
	}

	return TRUE;
}
?>
