<?php

$per_day = 150;
$days=1;

/*

	03/22/2005 by myya perez (myya.perez@thesellingsource.com)
	changed start and end dates so will send leads from previous day
	dropped vp and added cg and ct4u vendors from query

	12/02/2004 by john hargrove (john.hargrove@thesellingsource.com)
	cleaned up some of the errors we've been getting back from BMG

	11/30/2004 by john hargrove (john.hargrove@thesellingsource.com)
	MODIFIED to exclude NY state leads.

	10/28/2004 by john hargrove (john.hargrove@thesellingsource.com)
	updated this to run _every_ night on crontab so this weekend reachback stuff is no longer necessary
	
*/

$day_start = date("Y-m-d", strtotime ("-2 day"));
$day_end = date("Y-m-d", strtotime ("-1 day"));

require_once ('mysql.3.php');
require_once ('config.3.php');
require_once ('setstat.1.php');
require_once ('pay_date_calc.1.php');

define ("HOST", "selsds001");
define ("USER", "sellingsource");
define ("PASS", "%selling\$_db");
define ("VISITOR_DB", "olp_bb_visitor");

$sql = new MySQL_3 ();
Error_2::Error_Test ($sql->Connect (NULL, HOST, USER, PASS), 1);


function conv_time($time)
{
	$y = substr($time,0,4);
	$m = substr($time,5,2);
	$d = substr($time,8,2);
	return "$m-$d-$y";
}

$query = "
	SELECT
		a.application_id,
		ci.license_key,
		first_name AS name_first,
		last_name AS name_last,
		address_1 AS home_street,
		apartment AS home_unit,
		city AS home_city,
		state AS home_state,
		zip AS home_zip,
		email AS email_primary,
		'FALSE' AS offers,
		work_phone AS phone_work,
		DATE_FORMAT(date_of_birth, '%m-%d-%Y') AS date_of_birth,
		employer AS employer_name,
		direct_deposit AS income_direct_deposit,
		net_pay AS net_income,
		pay_frequency AS income_frequency,
		routing_number AS bank_aba,
		home_phone AS phone_home,
		ip_address,
		social_security_number,
		account_number AS bank_account,
		p.drivers_license_number AS dlnumber,
		paydate.paydate_model_id,
		paydate.day_of_week,
		DATE_FORMAT(paydate.next_paydate,'%m/%d/%Y') as ref_paydate,
		paydate.day_of_month_1,
		paydate.day_of_month_2,
		paydate.week_1,
		paydate.week_2,
		paydate.accuracy_warning
	FROM
		application a,
		bank_info bi,
		blackbox_state bs,
		campaign_info ci,
		employment e,
		income i,
		personal p,
		personal_contact pc,
		residence r,
		paydate
	WHERE
		(bb_cg IS NOT NULL OR bb_ct4u IS NOT NULL OR bb_sun IS NOT NULL OR bb_sun2 IS NOT NULL)
		AND a.application_id = bi.application_id
		AND a.application_id = bs.application_id
		AND a.application_id = ci.application_id
		AND a.application_id = e.application_id
		AND a.application_id = i.application_id
		AND a.application_id = p.application_id
		AND a.application_id = paydate.application_id
		AND p.contact_id_1 = pc.contact_id
		AND a.application_id = r.application_id
		AND ci.active = 'TRUE'
		AND a.created_date between '{$day_start}' and '{$day_end}'
		AND r.state NOT IN ('NY','GA')
		AND net_pay != 0
		AND i.pay_frequency != ''
	";

$result = $sql->Query ('olp_bb_visitor', $query);
Error_2::Error_Test ($result, 1);


$pdc = new Pay_Date_Calc_1(NULL);
$pdc_start = date("m/d/Y");
$daymap = array("sun","mon","tue","wed","thu","fri","sat");
$i = 0;

while (($row = $sql->Fetch_Array_Row ($result)) && ($i < $days * $per_day))
{

	$args = array(
		"day_string_one" => $daymap[$row['day_of_week']],
		"next_pay_date" => $row['ref_paydate'],
		"day_int_one" => $row['day_of_month_1'],
		"day_int_two" => $row['day_of_month_2'],
		"week_one" => $row['week_1'],
		"week_two" => $row['week_2']
	);

	list($day_1,$day_2) = 
		$pdc->Calculate_Payday(
			$row['paydate_model_id'],
			$pdc_start,
			$args,
			2);

	
	$row['pay_date_1'] = conv_time($day_1);
	$row['pay_date_2'] = conv_time($day_2);
	
	if ( $day_1 == '' || $day_2 == '' )
		continue;	
	
	_Send_Efm_Loan ($row);

	$cfg = Config_3::Get_Site_Config ($row['license_key'], 10000, '');
	$inf = Set_Stat_1::Setup_Stats ($cfg->site_id, $cfg->vendor_id, $cfg->page_id, $cfg->promo_id, '', $sql, $cfg->stat_base, $cfg->promo_status);

	Set_Stat_1::Set_Stat ($inf->block_id, $inf->tablename, $sql, $cfg->stat_base, 'bb_efm2', 1);
	
	$i++;
}


function _Send_Efm_Loan ($data)
{
	global $sql;

	ini_set ('default_socket_timeout', '60');

	$ua = new BB_User_Agent ();

	$epd_map = array (
		'WEEKLY' => 'Weekly',
		'MONTHLY' => 'Monthly',
		'BI_WEEKLY' => 'Bi-Weekly',
		'TWICE_MONTHLY' => 'Semi-Monthly',
	);
	
	$mf_map = array (
		'WEEKLY' => 4,
		'MONTHLY' => 1,
		'BI_WEEKLY' => 2,
		'TWICE_MONTHLY' => 2,
	);	
	
	$Employee_Days_Paid = $epd_map[$data['income_frequency']];
	$mult_factor = $mf_map[$data['income_frequency']];

	$data['income_monthly_net'] = $data['net_income'] * $mult_factor;

	$url = 'http://www.efastmedia.com/directpost.aspx';
	$fields = array (
		'C' => 138,
		'First_Name' => $data['name_first'],
		'Last_Name' => $data['name_last'],
		'Address' => $data['home_street'].($data['home_unit'] ? ' '.$data['home_unit'] : ''),
		'City'	=> $data['home_city'],
		'State' => $data['home_state'],
		'Zip' => $data['home_zip'],
		'Email' => $data['email_primary'],
		'Future_Offers' => $data['offers'] == "TRUE" ? 1 : 0,
		'Alt_Phone' => substr($data['phone_work'],0,3).'-'.substr($data['phone_work'],3,3).'-'.substr($data['phone_work'],6,4),
		'Gender' => 'M',
		'DOB'	=>  $data['date_of_birth'],
		'Employer' => $data['employer_name'],
		'Direct_Deposit' => $data['income_direct_deposit'] == 'TRUE' ? 'Y' : 'N',
		'Employee_Income' => $data['income_monthly_net'],
		'Employee_Days_Paid' => $Employee_Days_Paid,
		'Employee_Next_Pay_Day' => $data['pay_date_1'],
		'Employee_Next_Next_Pay_Day' => $data['pay_date_2'],
		'Bank_ABA' => $data['bank_aba'],
		'Phone' => substr($data['phone_home'],0,3).'-'.substr($data['phone_home'],3,3).'-'.substr($data['phone_home'],6,4),
		'IPAddress' => $data['ip_address'],
		'SSN' => substr($data['social_security_number'],0,3).'-'.substr($data['social_security_number'],3,2).'-'.substr($data['social_security_number'],5,4),
		'Account_Number' => $data['bank_account'],
		'DL_Number' => $data['dlnumber'],
		'DL_State' => $data['home_state'],
	);

	echo date ("Y-m-d H:i:s"), " - start send app ".$data['application_id']."\n";

	$re = $ua->Http_Post ($url, $fields);
	
	echo date ("Y-m-d H:i:s"), " - finish send app ".$data['application_id']."\n";

	$query = "INSERT INTO blackbox_batch (application_id, winner, date_created, data_sent, data_recv) VALUES (".mysql_escape_string($data['application_id']).", 'efm', NOW(), '".mysql_escape_string(serialize($fields))."', '".mysql_escape_string($re)."')";
	Error_2::Error_Test ($sql->Query ('olp_bb_visitor', $query), 1);

	if (preg_match ('/Status=(\d+);.*?<error\/>/is', $re, $m) && $m[1] == '0')
	{
		return TRUE;
	}
	else
	{
		return FALSE;
	}
}


class BB_User_Agent
{
	var $loc;
	var $cookie_jar;

	function BB_User_Agent ()
	{
		$this->cookie_jar = array();
	}

	function Http_Get ($url, $fields = NULL)
	{
		$this->head = '';

		$curl = curl_init ();

		if (is_array ($fields))
		{
			$url = $url.'?'.$this->Url_Encode ($fields);
		}

		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_HEADER, 1);
		curl_setopt ($curl, CURLOPT_FAILONERROR, 1);
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_TIMEOUT, 60);

		if (count ($this->cookie_jar))
		{
			curl_setopt ($curl, CURLOPT_HTTPHEADER, array ($this->Cookie_Header ()));
		}

		if (preg_match ('/^https/', $url))
		{
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
		}

		$result = curl_exec ($curl);

		$lines = explode ("\n", str_replace ("\r\n", "\n", $result));

		do
		{
			$line = array_shift ($lines);
			$this->Process_Header ($line);
		}
		while (trim ($line));

		$result = implode ("\n", $lines);

		return $result;
	}

	function Http_Post ($url, $fields)
	{
		$this->loc = '';
		$this->head = '';

		$curl = curl_init ();

		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_HEADER, 1);
		curl_setopt ($curl, CURLOPT_FAILONERROR, 1);
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt ($curl, CURLOPT_POST, 1);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, $this->Url_Encode ($fields));

		if (count ($this->cookie_jar))
		{
			curl_setopt ($curl, CURLOPT_HTTPHEADER, array ($this->Cookie_Header ()));
		}

		if (preg_match ('/^https/', $url))
		{
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
		}

		$result = curl_exec ($curl);

		$lines = explode ("\n", str_replace ("\r\n", "\n", $result));

		do
		{
			$line = array_shift ($lines);
			$this->Process_Header ($line);
		}
		while (trim ($line));

		$result = implode ("\n", $lines);

		if ($this->loc)
		{
			if (! preg_match ('/^http/i', $this->loc))
			{
				if (preg_match ('/^\//', $this->loc))
				{
					preg_match ('/(https?:\/\/[^\/]+\/)/', $url, $m);
				}
				else
				{
					preg_match ('/(https?:\/\/.+\/)/', $url, $m);
				}
				$loc = $m[1].$this->loc;
			}
			else
			{
				$loc = $this->loc;
			}
			$result = $this->Http_Get ($loc);
		}

		return $result;
	}

	function Url_Encode ($fields)
	{
		$re = '';
		foreach ($fields as $k => $v)
		{
			$re .= urlencode ($k).'='.urlencode ($v).'&';
		}
		$re = substr ($re, 0, -1);
		return $re;
	}

	function Process_Header ($line)
	{
		switch (TRUE)
		{
			case (preg_match ('/set-cookie:\s*([^=]+)=([^; ]+)/i', $line, $m)):
				$this->cookie_jar [$m[1]] = $m[2];
				break;

			case (preg_match ('/^location:\s*(\S+)\s*$/i', $line, $m)):
				$this->loc = $m[1];
				break;
		}
	}

	function Cookie_Header ()
	{
		$re = 'Cookie: ';
		foreach ($this->cookie_jar as $k => $v)
		{
			$re .= $k.'='.$v.'; ';
		}
		return substr ($re, 0, -2);
	}
}


?>
