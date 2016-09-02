<?php

# ex: set ts=4:
# descended from /vh/cronjobs/www/batch.onetime.bb.vendorpromotions.phone.php

require_once("diag.1.php");
require_once("lib_mode.1.php");
require_once("mysql.3.php");
require_once("csv.1.php");

Diag::Enable();

define("DEBUG",		true);
define("DB_HOST",	"selsds001");
define("DB_USER",	"sellingsource");
define("DB_PASS",	"%selling\$_db");
define("DB_NAME",	"lead_generation");
define("LICENSE_KEY",	"3301577eb098835e4d771d4cceb6542b");

$sql = new MySQL_3();
$sql->Connect("BOTH" DB_HOST, DB_USER, DB_PASS, Debug_1::Trace_Code(__FILE__, __LINE__));

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

$fp = fopen("/tmp/vendorpromotions.onetime.400k.csv", "w") or die("cannot open csv");

$csv = new CSV(
	array(
		"flush" => false # don't want to flush
		,"nl" => CSV_NL_WIN
		,"forcequotes" => true # looks prettier 
		,"stream" => $fp
		,"titles" => $fields
	)
);

# get phone data

$query = "
SELECT
	email
	first_name
	last_name
	address_1
	address_2
	apartment
	city
	state
	zip
	home_phone
	datestamp
	ip_address
	url
FROM
	vp_400k
ORDER BY
	email ASC
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

Diag::Out("done and... done.");

?>

