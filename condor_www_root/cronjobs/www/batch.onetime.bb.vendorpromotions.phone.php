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
define("DB_NAME",	"olp_ca_visitor");
define("LICENSE_KEY",	"3301577eb098835e4d771d4cceb6542b");

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
	,"url"
);

$fp = fopen("/tmp/pizza/vendorpromotions.onetime.20k", "w") or die("cannot open csv");

$csv = new CSV(
	array(
		"flush" => false
		,"nl" => CSV_NL_UNIX
		,"forcequotes" => false
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
	,c.url
FROM
	personal p
	,residence r
	,campaign_info c
WHERE
	p.modified_date
		BETWEEN
			20040724000000
		AND
			20040824000000
AND
	p.first_name NOT IN ('', 'test')
AND
	p.last_name NOT IN ('', 'test')
AND
	p.email != ''
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
AND
	c.url != ''
GROUP BY
	p.email
ORDER BY
	home_phone ASC
LIMIT
	20000
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

/*
$query = "
SELECT
	session_info
	,compression
FROM
	session_0
WHERE
	date_created
		BETWEEN
			20040723000000
		AND
			20040823000000
LIMIT
	1
";

$rs = $sql->Query(
	DB_NAME
	,$query
	,Debug_1::Trace_Code(__FILE__, __LINE__)
);

Error_2::Error_Test($rs, TRUE);

var_export(mysql_fetch_assoc($rs));
*/

?>
