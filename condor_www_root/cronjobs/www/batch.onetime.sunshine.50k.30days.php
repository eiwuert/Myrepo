<?php

require_once("diag.1.php");
require_once("lib_mode.1.php");
require_once("mysql.3.php");
require_once("csv.1.php");

Diag::Enable();

define("DEBUG",		true);
define("DB_HOST",	"selsds001");
define("DB_USER",	"sellingsource");
define("DB_PASS",	"%selling\$_db");
define("DB_NAME",	"olp_bb_visitor");

$sql = new MySQL_3();
$sql->Connect(NULL, DB_HOST, DB_USER, DB_PASS, Debug_1::Trace_Code(__FILE__, __LINE__));

$fields = array(
	"first_name"
	,"last_name"
	,"home_phone"
	,"email"
	,"address_1"
	,"address_2"
	,"apartment"
	,"city"
	,"state"
	,"zip"
	,"datestamp"
	,"ip_address"
);

$fp = fopen("/tmp/pizza/sunshine.50k.csv", "w") or die("cannot open csv");

$csv = new CSV(
	array(
		"flush" => false
		,"nl" => CSV_NL_WIN
		,"forcequotes" => true 
		,"stream" => $fp
		,"titles" => $fields
	)
);

# get phone data

$query = "
SELECT
	p.email
	,p.first_name
	,p.last_name
	,r.address_1
	,r.address_2
	,r.apartment
	,r.city
	,r.state
	,r.zip
	,p.home_phone
	,p.modified_date AS datestamp
	,c.ip_address
FROM
	personal p
	,residence r
	,campaign_info c
WHERE
	p.modified_date
		BETWEEN
			20040912000000
		AND
			20041012235959
AND
	p.first_name NOT IN ('', 'test')
AND
	p.last_name NOT IN ('', 'test')
AND
	p.home_phone NOT IN ('', '1231231234')
AND
	p.email != ''
	AND
	p.email LIKE '%@%'
	AND
	p.email NOT LIKE 'TEST%'
	AND
	p.email NOT LIKE '%@TSSMASTERD.COM'
AND
	r.application_id = p.application_id
AND
	r.address_1 != ''
AND
	r.city != ''
AND
	r.state != ''
AND
	r.zip != ''
AND
	c.application_id = p.application_id
AND
	c.ip_address != ''
GROUP BY
	p.email
ORDER BY
	home_phone ASC
LIMIT
	50000
";

Diag::Out("query: " . preg_replace('/\s+/', ' ', $query));

$rs = $sql->Query(
	DB_NAME
	,$query
	,Debug_1::Trace_Code(__FILE__, __LINE__)
);

Error_2::Error_Test($rs, TRUE);

Diag::Out("fetched " . $sql->Row_Count($rs) . " records...");

$csv->recordsFromWrapper($sql, $rs, $fields);
$csv->flush();

?>
