<?php

require_once("diag.1.php");
require_once("mysql.3.php");
require_once("lib_mode.1.php");
require_once("HTTP/Request.php"); # pear HTTP_Request, very handy
require_once("lgen.record.1.php");

// moved the methods into this file to make them specific. didnt want to break something else
//require_once("post.to.bmg.php");

define('BMG_ID', 147);
define('MAX_RECS_PER_DAY', 200);

Diag::Enable();






switch(Lib_Mode::Get_Mode())
{
case MODE_LOCAL:
/*
	define("DB_HOST", "localhost");
	define("DB_USER", "root");
	define("DB_PASS", "");
	define("DB_NAME", "olp_session_harvest");
	break;
*/
case MODE_LIVE:
	define("DB_HOST", "selsds001");
	define("DB_USER", "sellingsource");
	define("DB_PASS", "%selling\$_db");
	define("DB_NAME", "olp_session_harvest");
	define("DB_LOG_NAME", "lead_generation"); # we keep our log in the lead_gen db because that's where other logs are, because this is lead gen, and we don't want to clutter olp_session_harvest, we want it lean and mean
	break;
default:
	Diag::Bail("what mode are we in?!?");
	break;
}

# how many records more can we send today? and when did we last send records, so we can only find records newer than that
function get_prev_stats(&$sql)
{

	$max_recs = intval(MAX_RECS_PER_DAY);
	$early_ts = date("Ymd000000");
	$late_ts = date("Ymd235959");

	$query = <<<SQL
	SELECT
		$max_recs - COALESCE(SUM(records_sent), 0)
		,COALESCE(MAX(updated), 0)
	FROM
		log_bmg_idontagree
	WHERE
		updated
		BETWEEN
			'$early_ts'
			AND
			'$late_ts'
SQL;

	Diag::Out("query: $query");
	
	$rs = $sql->Query(
		DB_LOG_NAME
		,$query
		,Debug_1::Trace_Code(__FILE__, __LINE__)
	);

	Error_2::Error_Test($rs, TRUE);

	list($records_left, $earliest) = $sql->Fetch_Row($rs);

	$records_left = intval($records_left);

	return array($records_left, $earliest);
}

function save_point(&$sql, $count, $last_id)
{

	assert(is_int($count));

	# may be empty, we don't really care about it anyway
	$last_id = intval($last_id);

	$query = <<<SQL
INSERT INTO log_bmg_idontagree (
	id
	,updated
	,records_sent
	,last_id
) VALUES (
	NULL
	,NOW()
	,$count
	,$last_id
)
SQL;

	Diag::Out("query: $query");
	
	$rs = $sql->Query(
		DB_LOG_NAME
		,$query
		,Debug_1::Trace_Code(__FILE__, __LINE__)
	);

	Error_2::Error_Test($rs, TRUE);
}

Diag::Out("MODE_HOST: " . MODE_HOST);

$sql = new MySQL_3();
Error_2::Error_Test(
	$sql->Connect("BOTH", DB_HOST, DB_USER, DB_PASS),
	true
);

# how many records can we send w/o going over MAX_RECS_PER_DAY?
list($num_recs, $earliest) = get_prev_stats($sql);

if (0 >= $num_recs)
{
	die("We've already reached our limit of " . intval(MAX_RECS_PER_DAY) . " today, stopping.");
}
else
{
	Diag::Out("we have $num_recs records left to send, from '$earliest' onward!");
}

# calculate earliest timestamp if this is our first time
if (0 == $earliest)
{
	$earliest = date('YmdHis', time() - 3600);
	Diag::Out("recalculated ts to '$earliest'");
}

$end_of_today_ts = date("Ymd235959");

$query = <<<SQL
SELECT
	p.application_id
	,p.first_name AS name_first
	,p.last_name AS name_last
	,p.home_phone AS phone_home
	,p.email AS email_primary
	,p.social_security_number
	,DATE_FORMAT(p.date_of_birth, '%m-%d-%Y') AS date_of_birth
	,r.address_1 AS home_street
	,r.apartment AS home_unit
	,r.city AS home_city
	,r.state AS home_state
	,r.zip AS home_zip
	,'TRUE' AS offers
	,e.work_phone AS phone_work
	,e.employer AS employer_name
	,i.net_pay AS income_monthly_net
	,i.pay_frequency AS income_frequency
	,DATE_FORMAT(i.pay_date_1, '%m-%d-%Y') AS pay_date_1
	,DATE_FORMAT(i.pay_date_2, '%m-%d-%Y') AS pay_date_2
	,b.direct_deposit AS income_direct_deposit
	,b.routing_number AS bank_aba
	,b.account_number AS bank_account
	,c.ip_address
	,c.url
	,p.drivers_license_number as dlnumber
FROM
	personal p
	,residence r
	,employment e
	,income i
	,bank_info b
	,campaign_info c
WHERE
-- pare the records waaayyyyy down, this field must be indexed
	p.modified_date >= '$earliest'
AND
-- the reason for all the != ''s is becuase, remember, these are
-- harvested, incomplete sessions; there are no guarentees
	p.first_name != ''
AND
	p.first_name != 'test'
AND
	p.last_name != ''
AND
	p.last_name != 'test'	
AND
	p.home_phone != ''
AND
	p.email != ''
AND
	p.social_security_number != ''
AND
	p.date_of_birth != ''
AND
	r.application_id = p.application_id
AND
	e.application_id = p.application_id
AND
	i.application_id = p.application_id
AND
	i.pay_date_1 != '000000000000'
AND
	i.pay_date_1 > '$end_of_today_ts'
AND
	i.pay_date_2 > i.pay_date_1
AND
	i.net_pay >= 800
AND
	r.zip != ''
AND
	r.state != 'NY'
AND
	r.state != 'GA'
AND
	r.state != 'VA'	
AND
	LENGTH(r.zip)>=5
AND
	b.application_id = p.application_id
AND
	b.routing_number != ''
AND
	b.account_number != ''
AND
	i.pay_frequency != ''
AND
	i.pay_frequency != 'MONTHLY'	
AND
	c.application_id = p.application_id
LIMIT
	$num_recs
SQL;

$rs = $sql->Query(
	DB_NAME,
	$query,
	Debug_1::Trace_Code(__FILE__, __LINE__)
);

Error_2::Error_Test($rs, true);

Diag::Out("found " . $sql->Row_Count($rs) . " records");

$post = new Post_To_BMG();

$cnt=0;

// exclude leads that a
$soap_sites = array 
(
	'26798'  => 'trustcenterlending.com'
	,'26797' => 'borrowsource.com'
	,'26796' => 'citizenloan.com'
	,'26795' => 'hwhlending.com'
	,'26789' => 'fastcashprovider.com'
	,'26820' => 'cashadvancestoday.net'
	,'26754' => 'cashadvance.name'
	,'26745' => '911paydayadvance.com'
	,'26790' => 'kapsmg.com'
	,'26777' => 'fastcashday.com'
	,'26747' => 'personalcashadvance.com'
	,'26778' => 'mydirectcashadvance.com'
	,'26818' => 'mytimeforcash.com'
	,'26819' => 'atlaspeakfinancial.com'
	,'26748' => 'ilending.com'
	,'26749' => 'iemergencyloans.com'
	,'26746' => 'expeditepayday.com'
	,'26775' => 'nationalfastcash.com'
	,'26776' => '123onlinepaydayloans.com'
	,'26815' => 'efastcashloans.com'
	,'26715' => 'maxoutloan.com'
	,'26716' => 'speedycashadvance.com'
	,'26717' => 'ineedbeermoney.com'
	,'26718' => 'autorepairloans.com'
	,'26693' => 'mypayday.com'
	,'26750' => 'nofax-paydayloans.com'
	,'26744' => 'speeddog.com'
	,'26752' => 'paydayloanform.com'
	,'26771' => 'payday911.com'
	,'26800' => 'getcashtomorrowapp.com'
	,'26858' => 'quickcashprovider.com'
);

while (false !== ($row = $sql->Fetch_Array_Row($rs)))
{
	if ( array_search($row["url"], $soap_sites) == FALSE ) 
	{
		if ( !Leadgen_Record::Check_BMG($sql,$row["email_primary"]) &&
			!Leadgen_Record::Check_DS ($sql,$row["email_primary"]) )
		{
			$cnt++;
			var_export($post->Post(BMG_ID, $row, true));
			
			Leadgen_Record::Record_BMG($sql,$row["application_id"],BMG_ID,$row["name_first"],$row["name_last"],$row["phone_home"],$row["email_primary"]);
		}
	}	
}

# save our knowledge of the present for the benefit of future generations
save_point($sql, $cnt, $row["application_id"]);	

$sql->Free_Result($rs);






























# ex: set ts=4:
# code to send personal data to bmg via the efastmedia website with an HTTP POST
# from OLP data



class Post_To_BMG
{

	function Post_To_BMG()
	{

	}

	function & Post($C, &$data, $live=false, $url='http://www.efastmedia.com/directpost.aspx')
	{
		
		# sorry, we can't have people posting incomplete data. the values can be empty, but the keys
		# must exist! assertions are a good thing, they catch bad code. don't turn them off!
		$assert = assert_options(ASSERT_BAIL);
		assert_options(ASSERT_BAIL, true); # none shall pass
			assert(is_numeric($C));
			assert(is_array($data));
				assert(isset($data['name_first']));
				assert(isset($data['name_last']));
				assert(isset($data['home_street']));
				assert(isset($data['home_unit']));
				assert(isset($data['home_city']));
				assert(isset($data['home_state']));
				assert(isset($data['home_zip']));
				assert(isset($data['email_primary']));
				assert(isset($data['offers']));
				assert(isset($data['phone_work']));
				#assert(isset($data['gender'])); # we don't collect gender
				assert(isset($data['date_of_birth']));
				assert(isset($data['employer_name']));
				assert(isset($data['income_direct_deposit']));
				assert(isset($data['income_monthly_net']));
				assert(isset($data['income_frequency']));
				assert(isset($data['pay_date_1']));
				assert(isset($data['pay_date_2']));
				assert(isset($data['bank_aba']));
				assert(isset($data['phone_home']));
				assert(isset($data['ip_address']));
				assert(isset($data['social_security_number']));
				assert(isset($data['bank_account']));
		assert_options(ASSERT_BAIL, $assert); # running away, eh? i'll bite your legs off!
	
		############### gratuitously ripped off from rodric's lead_batch.php script in /vh/cronjobs
	
		$epd_map = array (
			'WEEKLY' => 'Weekly',
			'MONTHLY' => 'Monthly',
			'BI_WEEKLY' => 'Bi-Weekly',
			'TWICE_MONTHLY' => 'Semi-Monthly',
		);
	
		$Employee_Days_Paid = $epd_map[$data['income_frequency']];
	
		$fields = array (
			'C' => $C,
			'First_Name' => $data['name_first'],
			'Last_Name' => $data['name_last'],
			'Address' => $data['home_street'] . (@$data['home_unit'] ? ' '.$data['home_unit'] : ''),
			'City'	=> $data['home_city'],
			'State' => $data['home_state'],
			'Zip' => $data['home_zip'],
			'Email' => $data['email_primary'],
			'Future_Offers' => $data['offers'] == "TRUE" ? 1 : 0,
			'Alt_Phone' => substr($data['phone_work'],0,3).'-'.substr($data['phone_work'],3,3).'-'.substr($data['phone_work'],6,4),
			'Gender' => isset($data['gender']) ? $data['gender'] : 'M',
			'DOB' =>  $data['date_of_birth'],
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
			'DL_State' => $data['home_state']
		);
	
		$net = new HTTP_Request($url);
		$net->setMethod(HTTP_REQUEST_METHOD_POST);
		reset($fields);
		while (list($k, $v) = each($fields))
			$net->addPostData($k, $v);

		# only actually send the request if this is "live", because we want coders to
		# be able to confirm that the right stuff is happening before we throw the
		# master switch
		if ($live)
		{
			$net->sendRequest();
		}
	
		return $net;
	
	}

}








?>