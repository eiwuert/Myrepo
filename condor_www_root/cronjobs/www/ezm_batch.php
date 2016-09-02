<?php

$day_start = date("Y-m-d H:i:s", strtotime ("-1 hour"));
$day_end = date("Y-m-d H:i:s");

require_once ('mysql.3.php');
require_once ('config.3.php');
require_once ('setstat.1.php');

define ("HOST", "selsds001");
define ("USER", "sellingsource");
define ("PASS", "%selling\$_db");
define ("VISITOR_DB", "olp_ezm_visitor");

$sql = new MySQL_3 ();
Error_2::Error_Test ($sql->Connect (NULL, HOST, USER, PASS), 1);

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
		net_pay AS net_per_week,
		pay_frequency AS income_frequency,
		DATE_FORMAT(pay_date_1, '%m-%d-%Y') AS pay_date_1,
		DATE_FORMAT(pay_date_2, '%m-%d-%Y') AS pay_date_2,
		routing_number AS bank_aba,
		home_phone AS phone_home,
		ip_address,
		social_security_number,
		account_number AS bank_account
	FROM
		application a,
		bank_info bi,
		campaign_info ci,
		employment e,
		income i,
		personal p,
		personal_contact pc,
		residence r
	WHERE
		a.application_id = bi.application_id
		AND a.application_id = ci.application_id
		AND a.application_id = e.application_id
		AND a.application_id = i.application_id
		AND a.application_id = p.application_id
		AND p.contact_id_1 = pc.contact_id
		AND a.application_id = r.application_id
		AND ci.active = 'TRUE'
		AND a.created_date between '{$day_start}' and '{$day_end}'
		AND r.state NOT IN ('NY')
	";

$result = $sql->Query (VISITOR_DB, $query);
Error_2::Error_Test ($result, 1);

$i = 0;
while ($row = $sql->Fetch_Array_Row ($result))
{
	_Send_Efm_Loan ($row);

	//$cfg = Config_3::Get_Site_Config ($row['license_key'], 10000, '');
	//$inf = Set_Stat_1::Setup_Stats ($cfg->site_id, $cfg->vendor_id, $cfg->page_id, $cfg->promo_id, '', $sql, $cfg->stat_base, $cfg->promo_status);
	//Set_Stat_1::Set_Stat ($inf->block_id, $inf->tablename, $sql, $cfg->stat_base, 'bb_efm2', 1);

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
	$Employee_Days_Paid = $epd_map[$data['income_frequency']];

	$data['income_monthly_net'] = $data['net_per_week'] * 4;

	$url = 'http://www.efastmedia.com/directpost.aspx';
	$fields = array (
		'C' => 144,
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
	);

	echo date ("Y-m-d H:i:s"), " - start send app ".$data['application_id']."\n";

	$re = $ua->Http_Post ($url, $fields);

	echo date ("Y-m-d H:i:s"), " - finish send app ".$data['application_id']."\n";

	$query = "INSERT INTO batch (application_id, winner, date_created, data_sent, data_recv) VALUES (".mysql_escape_string($data['application_id']).", 'efm', NOW(), '".mysql_escape_string(serialize($fields))."', '".mysql_escape_string($re)."')";
	Error_2::Error_Test ($sql->Query (VISITOR_DB, $query), 1);

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
		curl_setopt ($curl, CURLOPT_TIMEOUT, 30);

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
		curl_setopt ($curl, CURLOPT_TIMEOUT, 30);
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
			//mail ('rodricg@sellingsource.com', 'BB::Http_Post::Redir', "\$url=\n{$url}\n\n\$this->loc=\n{$this->loc}\n\n\$loc=\n{$loc}\n\n\$result=\n{$result}\n\n");
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
