<?php
/* ex: set ts=8: */

/*****************************************************************************

Library for interfacing with the "scrubber" database

MAKE SURE YOU HAVE THE LATEST AND GREATEST FROM CVS

written by pizza (ryan.flynn@thesellingsource.com)

*****************************************************************************/

// if TRUE we check incoming data for every function with assertions to improve
// sanity. THERE IS NO REASON TO TURN THIS OFF. IF YOUR CODE DOESN'T WORK WITH
// THIS ON THEN IT WON'T WORK PROPERLY WITH IT OFF EITHER, FIX YOUR CODE.
define('SCRUB_PARANOID',	TRUE);

define('SCRUB_DEBUG',		FALSE); // do we want to see debug messages?
define('SCRUB_DEBUG_DUMP',	FALSE); // do we want to see debug dumps?
define('SCRUB_DEBUG_SQL',	FALSE); // do we want to see all the sql statements we generate?
define('SCRUB_WARNINGS',	TRUE); // do we want to see warnings?

// include inc.db.php before this, or...
// create a MySQL_3 connection to the scrubber db and define DB_NAME yourself

// status flags in the database
define('STATUS_TRUE',			'Y');
define('STATUS_FALSE',			'N');
define('STATUS_UNKNOWN',		'');

// internal comparison operators for some funky functions
define('CMP_EQ',			1);
define('CMP_NEQ',			2);
define('CMP_GT',			3);
define('CMP_GTEQ',			4);
define('CMP_LT',			5);
define('CMP_LTEQ',			6);

// define in the source table in scrubber DB and should NEVER, EVER CHANGE
define('SOURCE_UNVALIDATED',		0);
define('SOURCE_MANUAL',			1);
define('SOURCE_SCRUBBER_WEB_INTERFACE',	2);
define('SOURCE_NAME_SCRUBBER',		3);
define('SOURCE_EMAIL_SCRUBBER',		4);
define('SOURCE_ADDRESS_SCRUBBER',	5);
define('SOURCE_PHONE_SCRUBBER',		6);
define('SOURCE_OLE',			7);
define('SOURCE_COPIA_AUTODIALER',	8);

// these are all in the 'errno' table of the 'scrubber' db and should NEVER, EVER change
define('ERRNO_OK',				0);

// no real errno, it was simply input by a user
define('ERRNO_MANUAL',				1);

define('ERRNO_INT_GOOD_DOMAIN_NOMATCH',		10);
define('ERRNO_INT_BAD_DOMAIN_MATCH',		11);
define('ERRNO_INT_BAD_NAME_MATCH',		12);
define('ERRNO_INT_BAD_EMAIL_MATCH',		13);
define('ERRNO_INT_BAD_ADDR_MATCH',		14);
define('ERRNO_NATL_DO_NOT_CALL_MATCH',		100);
define('ERRNO_NATL_DO_NOT_MAIL_MATCH',		101);
define('ERRNO_NATL_DO_NOT_EMAIL_MATCH',		102);

define('ERRNO_EMAIL_TOO_SHORT',			1000);
define('ERRNO_EMAIL_NO_AT',			1001);
define('ERRNO_EMAIL_USERNAME_TOO_SHORT',	1002);
define('ERRNO_EMAIL_DOMAIN_TOO_SHORT',		1003);
define('ERRNO_EMAIL_USERNAME_TOO_LONG',		1004);
define('ERRNO_EMAIL_DOMAIN_TOO_LONG',		1005);
define('ERRNO_EMAIL_USERNAME_INVALID_CHAR', 	1006);
define('ERRNO_EMAIL_DOMAIN_INVALID_CHAR',	1007);
define('ERRNO_EMAIL_DOMAIN_INVALID_SYNTAX',	1008);
define('ERRNO_EMAIL_USERNAME_REPEATING_CHAR',	1009);
define('ERRNO_EMAIL_DOMAIN_NON_US',		1010);
define('ERRNO_EMAIL_USER_REQUESTED',		1020);

define('ERRNO_ADDR_EMPTY',			1100);
//define('ERRNO_ADDR_CITY_TOO_SHORT',		1110);
define('ERRNO_ADDR_STATE_UNRECOGNIZED',		1120);
define('ERRNO_ADDR_STATE_NA',			1121);
//define('ERRNO_ADDR_ZIP_INVALID_LENGTH',	1131);
define('ERRNO_ADDR_SATORI_INVALID',		1140);
define('ERRNO_ADDR_SATORI_UNAVAILABLE',		1141);

define('ERRNO_PHONE_TOO_SHORT',			1200);
define('ERRNO_PHONE_NON_NUMBERS',		1201);
define('ERRNO_PHONE_EXCHANGE_555',		1210);
define('ERRNO_PHONE_AREA_CODE_TOLLFREE',	1211);
define('ERRNO_PHONE_AREA_CODE_PREMIUM',		1212);
define('ERRNO_PHONE_AREA_CODE_SPECIAL',		1213);
define('ERRNO_PHONE_ALL_SAME_DIGIT',		1214);
define('ERRNO_PHONE_ALL_ASCENDING',		1215);
define('ERRNO_PHONE_ALL_DESCENDING',		1216);
define('ERRNO_PHONE_AREA_CODE_STATE_MISMATCH',	1220);


define('ERRNO_PHONE_COPIA_NO_CARRIER',		1230);
define('ERRNO_PHONE_COPIA_FAX',			1231);
define('ERRNO_PHONE_COPIA_INTERCEPT',		1232);
// this below refers to Transaction Log File
define('ERRNO_PHONE_COPIA_NO_ANSWER',		1233);
define('ERRNO_PHONE_COPIA_CALL_FAILED',		1234);
define('ERRNO_PHONE_COPIA_NO_DIALTONE',		1235);

define('ERRNO_NAME_EMPTY',			1300);
define('ERRNO_NAME_CONSECUTIVE_CONSONANTS',	1301);
define('ERRNO_NAME_CONSECUTIVE_VOWELS',		1302);
define('ERRNO_NAME_REPEATED_CHARS',		1303);
define('ERRNO_NAME_BAD_CHARS',			1304);
define('ERRNO_NAME_TOOMANY_WORDS',		1305);


// fields
define('T_UNUSED',	'X'); // represents a field that exist in the csv but is not utilized
define('T_CUSTID',	'CustID');
define('T_BATCHID',	'BatchID');
define('T_DATE',	'Date'); // this isn't actually used, just for format
define('T_APPID',	'AppID'); // this isn't actually used, just for format
define('T_TFULLNAME',	'Full Name');
define('T_TNAME',	'Title');
define('T_FNAME',	'First Name');
define('T_MNAME',	'Middle Name');
define('T_LNAME',	'Last Name');
define('T_XNAME',	'Extra Name');
define('T_EMAIL',	'Email');
define('T_PHONE',	'Home Phone');
define('T_PHONEWORK',	'Work Phone');
define('T_PHONECELL',	'Cellphone');
define('T_PHONEPAGER',	'Pager');
define('T_PHONEFAX',	'Fax');
define('T_ADDR1',	'Address');
define('T_ADDR2',	'Address2');
define('T_CITY',	'City');
define('T_STATE',	'State');
// the DIFFERENCE between ZIP, ZIP4 and ZIPCODE is that, if you were given
// '12345-6789', the ZIP would be '12345', the ZIP4 would be '6789' and the
// ZIPCODE would be '12345-6789'
// the REASON for this is that some people store their zipcodes whole and
// some people store them split up
define('T_ZIP',	'5-digit Zip');
define('T_ZIP4',	'4-digit Zip');
define('T_ZIPCODE',	'Zipcode');
define('T_URL_REFER',	'Referring URL');
define('T_IP',		'IP Address');
define('T_DATE_BIRTH',	'Date of Birth');
define('T_DATE_SIGNUP','Date of Signup');

// this is for display of the valid_ fields... can't remember if or where this
// is currently used
define('T_VALIDNAME',	'Valid Name?');
define('T_VALIDEMAIL',	'Valid Email?');
define('T_VALIDPHONE',	'Valid Phone?');
define('T_VALIDADDR',	'Valid Address?');
define('T_SOURCENAME',	'Name Source');
define('T_SOURCEEMAIL','Email Source');
define('T_SOURCEPHONE','Phone Source');
define('T_SOURCEADDR',	'Address Source');
define('T_ERRNAME',	'Name Error');
define('T_ERREMAIL',	'Email Error');
define('T_ERRPHONE',	'Phone Error');
define('T_ERRADDR',	'Address Error');
//define ('T_OVERRIDDEN',	"Overridden");

define('T_ID_BATCH',	'Batch ID');
define('T_ID_CUSTDATA','Custdata ID');

define('SCRUB_NAME',	1);
define('SCRUB_EMAIL',	2);
define('SCRUB_ADDRESS',	3);
define('SCRUB_PHONE',	4);

define('CLASS_ID_VENDORS', 4); // value from management db

define('CLASS_MYSQL_OBJ', "MySQL_3"); // current standard internal mysql wrapper class

// sql queries must be smaller than this value
$MYSQL_MAX_ALLOWED_PACKET = 1024000; // default max sql statement size, defaults to 1MB
define('MYSQL_HUGE_PACKET',	10240000); // if you set the mysql packet size above this size, we warn you. defaults to 10MB
define('MYSQL_FUDGE_FACTOR',	1024); // how close is too close to the MAX_ALLOWED_PACKET?

###### NOTE: local caching is not enabled yet
// global flag, if true, cache db data locally where possible to speedup
$CACHE_FLAG = FALSE;
// global variables that will be used for caching data if caching is enabled
$CACHE_AREA_CODE_STATE = array();
######

// possible supported formats of the incoming files.
// THESE MUST NOT BE ALTERED ONCE THEY ARE CREATED, BECAUSE THE DATABASE WILL REFERENCE THEM
// NOTE: the NULL values are not currently used, but some apps depend on the field array being at $FORMATS[id][1]
$FORMATS = array(
	// original example
	1 => array(
		NULL,
		array(
			T_UNUSED, T_UNUSED, T_LNAME, T_FNAME,
			T_PHONE, T_ADDR1, T_ADDR2, T_CITY,
			T_STATE, T_ZIPCODE
		)
	),
	// faulty webclients data
	2 => array(
		NULL,
		array(
			T_EMAIL, T_FNAME, T_LNAME, T_ADDR1,
			T_CITY, T_STATE, T_ZIPCODE, T_PHONE,
			T_DATE_BIRTH, T_DATE_SIGNUP, T_IP, T_URL_REFER
		)
	),
	// akc files
	3 => array(
		NULL,
		array(
			T_FNAME, T_LNAME, T_EMAIL
		)
	),
	// data from rodric
	4 => array(
		NULL,
		array(
			T_FNAME, T_MNAME, T_LNAME, T_ADDR1,
			T_ADDR2, T_CITY, T_STATE, T_ZIPCODE,
			T_PHONE, T_PHONECELL, T_PHONEWORK, T_UNUSED
		)
	),
	// what we're aiming for... and almost never get
	5 => array(
		NULL,
		array(
			T_EMAIL, T_FNAME, T_LNAME, T_ADDR1,
			T_ADDR2, T_CITY, T_STATE, T_ZIPCODE,
			T_PHONE, T_DATE_BIRTH, T_DATE_SIGNUP, T_IP,
			T_URL_REFER
		)
	),
	// stuff for Terri
	//CUSTID, DATE, FIRSTNAME, LASTNAME,
	//EMAIL, ADDRESS, CITY, STATE,
	//ZIP, HOMEPHONE, WORKPHONE, AMOUNTOWED,
	//HOMEOWNER, CONTACTTIME,  CREDITOR1,CREDITBALANCE1,
	//INTERESTRATE1,INTERESTCHARGE1,CREDITORTYPE1,BEHIND1,
	//CREDITOR2,CREDITBALANCE2,INTERESTRATE2,INTERESTCHARGE2,
	//CREDITORTYPE2,BEHIND2
	6 => array (
		NULL,
		array(
			T_UNUSED, T_UNUSED, T_FNAME, T_LNAME,
			T_EMAIL, T_ADDR1, T_CITY, T_STATE,
			T_ZIP, T_PHONE, T_PHONEWORK, T_UNUSED,
			T_UNUSED, T_UNUSED, T_UNUSED, T_UNUSED,
			T_UNUSED, T_UNUSED, T_UNUSED, T_UNUSED,
			T_UNUSED
		)
	),
	// stuff for Terry
	// FIRSTNAME, LASTNAME,EMAIL, ADDRESS,
	// CITY, STATE, ZIP, HOMEPHONE,
	// WORKPHONE, AMOUNTOWED, HOMEOWNER, CONTACTTIME
	7 => array (
		NULL,
		array(
			T_FNAME, T_LNAME, T_EMAIL, T_ADDR1,
			T_CITY, T_STATE, T_ZIP, T_PHONE,
			T_PHONEWORK, T_UNUSED, T_UNUSED, T_UNUSED
		)
	),
	// stuff for Terri
	// 4 ID,Fist Name,Last Name,Email,
	// 4 Address,City,State,Zip,
	// 3 Daytime Phone,Nighttime Phone,Work Phone,
	// 4 Cell Phone,Best Time To Call,Total Debt,Creditor 1,
	// 4 Toal Owed 1,Monthly Payment 1,Type Of Debt 1,Months Behind 1,
	// 4 Creditor 2,Toal Owed 2,Monthly Payment 2,Type Of Debt 2,
	// 4 Months Behind 2,Creditor 3,Toal Owed 3,Monthly Payment 3,
	// 4 Type Of Debt 3,Months Behind 3,Creditor 4,Toal Owed 4,
	// 4 Monthly Payment 4,Type Of Debt 4,Months Behind 4,Creditor 5,
	// 4 Toal Owed 5,Monthly Payment 5,Type Of Debt 5,Months Behind 5,
	// 4 Own House,Why Interest,IP,Form Submitted Date
	8 => array(
		NULL,
		array(
			T_UNUSED, T_FNAME, T_LNAME, T_EMAIL,
			T_ADDR1, T_CITY, T_STATE, T_ZIP,
			T_PHONE, T_UNUSED, T_PHONEWORK,
			T_PHONECELL, T_UNUSED, T_UNUSED, T_UNUSED, 
			T_UNUSED, T_UNUSED, T_UNUSED, T_UNUSED, 
			T_UNUSED, T_UNUSED, T_UNUSED, T_UNUSED, 
			T_UNUSED, T_UNUSED, T_UNUSED, T_UNUSED, 
			T_UNUSED, T_UNUSED, T_UNUSED, T_UNUSED, 
			T_UNUSED, T_UNUSED, T_UNUSED, T_UNUSED, 
			T_UNUSED, T_UNUSED, T_UNUSED, T_UNUSED,
			T_UNUSED, T_UNUSED, T_UNUSED, T_UNUSED
		)
	),
	9 => array(
		NULL,
		array(
			T_EMAIL, T_FNAME, T_LNAME, T_UNUSED,
			T_UNUSED, T_URL_REFER 
		)
	),
	10 => array(
		NULL,
		array(
			T_FNAME, T_LNAME, T_EMAIL, T_UNUSED,
			T_UNUSED
		)
	),
	11 => array(
		NULL,
		array(
			T_EMAIL, T_FNAME, T_LNAME, T_UNUSED,
			T_UNUSED, T_UNUSED
		)
	),
	// nearly-correctly formatted data from indata
	12 => array(
		NULL,
		array(
			T_EMAIL, T_FNAME, T_LNAME, T_ADDR1,
			T_ADDR2, T_CITY, T_STATE, T_ZIPCODE,
			T_PHONE, T_DATE_SIGNUP, T_IP, T_URL_REFER
		)
	),
	// correct data format with the name fields reversed... stupid bastards
	13 => array(
		NULL,
		array(
			T_EMAIL, T_LNAME, T_FNAME, T_ADDR1,
			T_ADDR2, T_CITY, T_STATE, T_ZIPCODE,
			T_PHONE, T_DATE_BIRTH, T_DATE_SIGNUP, T_IP,
			T_URL_REFER
		)
	),
	// IMG data format for file IMG_02132004.dat
	14 => array(
		NULL,
		array(
			T_FNAME, T_LNAME, T_EMAIL, T_IP, T_DATE_SIGNUP, T_URL_REFER
		)
	),
	15 => array(
		NULL,
		array(
			T_EMAIL, T_FNAME, T_LNAME, T_ADDR1,
			T_CITY, T_STATE, T_ZIP, T_IP,
			T_URL_REFER, T_DATE_SIGNUP
		)
	),
	16 => array(
		NULL,
		array(
			T_EMAIL, T_DATE_SIGNUP, T_IP
		)
	)
);

//////////////////// parsing functions

function parse_phone($phone)
{
// returns 10 digits
	$phone = preg_replace('/\D/', '', $phone);
	// it's possible the phone# contains an extension...
	if (strlen($phone) >= 10)
	{
		// use the first 10 digits
		return substr($phone, 0, 10);
	}
        else
        {
                return FALSE;
        }
}

// parse and format an individual name field
function parse_name($name)
{
	if (SCRUB_PARANOID)
	{
		assert(is_string($name));
	}

	return name_cleanup($name);
}

function parse_email($email)
{
	if (SCRUB_PARANOID)
	{
		assert(is_string($email));
	}

	return email_cleanup($email);
}

//////////////////// error and debug-related functions

// output result function. you pass it the custdata_id, the value tested and
// the resulting errno, and this prints out a nice little line of output.
// useful for scrubbing
function result($id, $val, $code)
{
	if (SCRUB_PARANOID !== FALSE)
	{
		assert(is_numeric($id));
		assert(is_numeric($code));
	}
	print(($code == ERRNO_OK ? "[OK]" : "[!!]") . sprintf(" %5d", $code). ": $id ($val)\n");
}

function debug_dump ($var)
{
	if (SCRUB_DEBUG_DUMP !== FALSE)
	{
		$db = debug_backtrace();
		list($func, $line) = array($db[1]["function"], $db[1]["line"]);
		echo microtime() . ":$func:$line:";
		flush();
		var_dump($var);
	}
}

function debug_print ($msg)
{
	if (SCRUB_DEBUG !== FALSE)
	{
		$db = debug_backtrace();
		list($func, $line) = array($db[1]["function"], $db[1]["line"]);
		echo microtime() . ":$func:$line:$msg\n";
		flush();
	}
}

function debug_warn ($msg)
{
	if (SCRUB_WARNINGS !== FALSE)
	{
		$db = debug_backtrace();
		list($func, $line) = array($db[1]["function"], $db[1]["line"]);
		echo microtime() . ":$func:$line:$msg\n";
		flush();
	}
}

function debug_sql ($msg)
{

	if (SCRUB_DEBUG_SQL !== FALSE)
	{
		$db = debug_backtrace();
		list($func, $line) = array($db[1]["function"], $db[1]["line"]);
		echo microtime() . ":$func:$line:$msg\n";
		flush();
	}
}

function choke_and_die ($msg)
{
	var_dump(debug_backtrace());
	die("$msg\n");
}


//flag functions

function cache_data_locally($bool)
{

	if (SCRUB_PARANOID)
	{
		assert(is_bool($bool));
	}

	global $CACHE_DATA;

	$CACHE_DATA = $bool;
}



//////////////////////// database-centric functions

// tell the library how large of a net buffer is available... for bulk inserts
// larger sql statement finish faster, so we take advantage of every byte
function set_mysql_max_allowed_packet($len)
{
	if (SCRUB_PARANOID)
	{
		assert(is_numeric($len));
		assert($len > 0);
	}

	$len = intval($len);

	if ($len < MYSQL_FUDGE_FACTOR * 10)
	{
		// disallow setting the max packet very small, it's unreasonably small
		debug_warn("Sorry, $len bytes is way too small for the max allowed packet, absolutely " . 
			"smallest allow is " . number_format($len, 0) . " bytes");
		return;
	}

	if ($len > MYSQL_HUGE_PACKET)
	{
		debug_warn("Setting mysql_max_allowed_packet to very large value (" . number_format($len, 0) . " bytes)");
	}

	global $MYSQL_MAX_ALLOWED_PACKET;
	
	$MYSQL_MAX_ALLOWED_PACKET = $len;

}

//////////////////////// data source
/*
	 a data source identifies within a vendor account, where the data came from.
	 it is useful for us to know where a vendor got the data, so that we can
	 do different things with the data. how's that for an explanantion
*/

function data_source_create (&$sql, $vendor_id, $data_source_desc)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($vendor_id));
		assert(is_string($data_source_desc));
	}

	$query = "
	INSERT INTO data_source (
		data_source_id,
		vendor_id,
		data_source_desc
	) VALUES (
		NULL,
		'" . mysql_escape_string($vendor_id) . "',
		'" . mysql_escape_string($data_source_desc) . "'
	)";

	return query_custdata($sql, $query);
}

function data_source_list_by_vendor (&$sql, $vendor_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($vendor_id));
	}

	$query = "
	SELECT
		data_source_id		AS id,
		data_source_desc	AS description
	FROM
		data_source
	WHERE
		vendor_id = '" . mysql_escape_string($vendor_id) . "'";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$data_source = array();

	while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
	{
		$data_source[$rec['id']] = $rec['description'];
	}

	$sql->Free_Result($rs);

	return $data_source;
}

function data_source_fetch_by_id (&$sql, $id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($id));
	}

	$query = "
	SELECT
		vendor_id,
		data_source_desc	AS description
	FROM
		data_source
	WHERE
		data_source_id = '" . mysql_escape_string($id) . "'";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$data_source = $sql->Fetch_Array_Row($rs);

	$sql->Free_Result($rs);

	return $data_source;

}

//////////////////////// vendors

function vendor_list (&$sql)
{
// NOTE: this talks to dev01 to get this list, so the db object you pass has to be
// connected to dev01

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	$query = "
	SELECT
		object_id	AS id,
		name
	FROM
		Object
	WHERE
		class_id = '" . mysql_escape_string(CLASS_ID_VENDORS) . "'
	ORDER BY
		name ASC";

	$rs = query_mgmt($sql, $query);
	Error_2::Error_Test($rs, TRUE);
	$data = array();

	while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
	{
		$data[$rec['id']] = array($rec['name'], $rec['vendor_name']);
	}

	$sql->Free_Result($rs);

	return $data;
}

function vendor_fetch_by_id (&$sql, $vendor_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($vendor_id));
	}

	$query = "
	SELECT
		object_id	AS id,
		name
	FROM
		Object
	WHERE
		class_id = '" . mysql_escape_string(CLASS_ID_VENDORS) . "'
	AND
		object_id = '" . mysql_escape_string($vendor_id) . "'
	ORDER BY
		name ASC";

	$rs = query_mgmt($sql, $query);
	Error_2::Error_Test($rs, TRUE);

	$vendor = $sql->Fetch_Array_Row($rs);

	$sql->Free_Result($rs);

	return $vendor;

}

//////////////////////// account-related functions

function account_create (&$sql, $data)
{

	if (SCRUB_PARANOID)
	{
		assert(is_array($data));
		assert(count(array_keys($data)) == 4);
			assert(isset($data['vendor_id']));
			assert(isset($data['username']));
			assert(isset($data['passwd']));
			assert(isset($data['vendor_name']));
	}

	$query = "
	INSERT INTO account (
		account_id,
		vendor_id,
		username,
		passwd,
		vendor_name,
		account_created
	) VALUES (
		NULL,
		'" . mysql_escape_string($data['vendor_id']) . "',
		'" . mysql_escape_string($data['username']) . "',
		'" . mysql_escape_string($data['passwd']) . "',
		'" . mysql_escape_string($data['vendor_name']) . "',
		NOW()
	)";

	return query_custdata($sql, $query);
}

function account_update_by_id (&$sql, $account_id, $data)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_array($data));
		assert(count($data) == 5);
			assert(isset($data['vendor_id']));
			assert(isset($data['username']));
			assert(isset($data['passwd']));
			assert(isset($data['vendor_name']));
			assert(isset($data['account_id']));
	}

	$query = "
	UPDATE account
	SET
		vendor_id = '" . mysql_escape_string($data['vendor_id']) . "',
		username = '" . mysql_escape_string($data['username']) . "',
		passwd = '" . mysql_escape_string($data['passwd']) . "',
		vendor_name = '" . mysql_escape_string($data['vendor_name']) . "'
	WHERE
		account_id = '" . mysql_escape_string($data['account_id']) . "'";

	return query_custdata($sql, $query);
}

function account_delete_by_id (&$sql, $account_id)
{
	$query = "
	DELETE
	FROM
		account
	WHERE
		account_id = '" . mysql_escape_string($account_id) . "'";

	return query_custdata($sql, $query);
}

function account_list (&$sql)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	$query = "
	SELECT
		account_id,
		vendor_id,
		username,
		passwd,
		vendor_name,
		account_created,
		account_updated,
		account_loggedin
	FROM
		account
	ORDER BY
		username ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$accounts = array();

	while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
	{
		$accounts[$rec['account_id']] = $rec;
	}

	$sql->Free_Result($rs);

	return $accounts;
}

function account_fetch_by_id (&$sql, $account_id)
{
	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($account_id));
	}

	$query = "
	SELECT
		account_id,
		vendor_id,
		username,
		passwd,
		vendor_name,
		account_created,
		account_updated,
		account_loggedin
	FROM
		account
	WHERE
		account_id = '" . mysql_escape_string ($account_id) . "'
	ORDER BY
		username ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$rec = $sql->Fetch_Array_Row($rs);

	return $rec;
}

function account_login (&$sql, $username, $passwd)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($username));
		assert(is_string($passwd));
	}

	$query = "
	SELECT
		account_id,
		vendor_id,
		username,
		passwd,
		vendor_name,
		UNIX_TIMESTAMP(account_created)		AS created,
		UNIX_TIMESTAMP(account_updated)		AS updated,
		UNIX_TIMESTAMP(account_loggedin)	AS loggedin
	FROM
		account
	WHERE
		username = '" . mysql_escape_string($username) . "'
	AND
		passwd = '" . mysql_escape_string($passwd) . "'
	LIMIT
		1";

	//echo "<pre>$query</pre>";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	// success!
	if ($sql->Row_Count($rs) > 0)
	{
		return $sql->Fetch_Array_Row($rs);
	}
	// failure!
	else
	{
		return FALSE;
	}
}


//////////////////////// customer data-related functions

function custdata_insert (&$sql, $batch_id, &$data)
{
	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
		assert(is_array($data));
	}

	$query = "
	INSERT INTO custdata (
		custdata_id,
		batch_id,
		date_signup,
		name_title,
		name_first,
		name_middle,
		name_last,
		name_extra,
		date_birth,
		email,
		phone,
		phone_work,
		phone_cell,
		phone_pager,
		phone_fax,
		addr1,
		addr2,
		city,
		state,
		zip,
		zip4,
		ip,
		url_refer
	) VALUES (
		NULL," .
		"'" . mysql_escape_string($batch_id) . "'," .
		"'" . mysql_escape_string($data[T_DATE_SIGNUP]) . "'," .
		"'" . mysql_escape_string($data[T_TNAME]) . "'," .
		"'" . mysql_escape_string($data[T_FNAME]) . "'," .
		"'" . mysql_escape_string($data[T_MNAME]) . "'," .
		"'" . mysql_escape_string($data[T_LNAME]) . "'," .
		"'" . mysql_escape_string($data[T_XNAME]) . "'," .
		"'" . mysql_escape_string($data[T_DATE_BIRTH]) . "'," .
		"'" . mysql_escape_string($data[T_EMAIL]) . "'," .
		"'" . mysql_escape_string($data[T_PHONE]) . "'," .
		"'" . mysql_escape_string($data[T_PHONEWORK]) . "'," .
		"'" . mysql_escape_string($data[T_PHONECELL]) . "'," .
		"'" . mysql_escape_string($data[T_PHONEPAGER]) . "'," .
		"'" . mysql_escape_string($data[T_PHONEFAX]) . "'," .
		"'" . mysql_escape_string($data[T_ADDR1]) . "'," .
		"'" . mysql_escape_string($data[T_ADDR2]) . "'," .
		"'" . mysql_escape_string($data[T_CITY]) . "'," .
		"'" . mysql_escape_string($data[T_STATE]) . "'," .
		"'" . mysql_escape_string($data[T_ZIP]) . "'," .
		"'" . mysql_escape_string($data[T_ZIP4]) . "'," .
		"'" . mysql_escape_string($data[T_IP]) . "'," .
		"'" . mysql_escape_string($data[T_URL_REFER]) . "'" .
	")";

	return query_custdata($sql, $query);

}


/*
	insert more than one record into custdata. uses the more efficient
	multi-value mysql insert syntax
*/
function custdata_bulk_insert (&$sql, $batch_id, &$data, $ignore=TRUE)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
		assert(is_array($data));
		assert(is_bool($ignore));
	}

	global $MYSQL_MAX_ALLOWED_PACKET;

	$SQLLEN = 0;

	$vals = array();
	$val = "";
	$RECORDS = count($data);
	$i = 0;

	while ($i < $RECORDS)
	{
		$rec = $data[$i];
		$val =
		"(NULL," .
		"'" . mysql_escape_string($batch_id) . "'," .
		"'" . mysql_escape_string(@$rec[T_DATE_SIGNUP]) . "'," .
		"'" . mysql_escape_string(@$rec[T_TNAME]) . "'," .
		"'" . mysql_escape_string(@$rec[T_FNAME]) . "'," .
		"'" . mysql_escape_string(@$rec[T_MNAME]) . "'," .
		"'" . mysql_escape_string(@$rec[T_LNAME]) . "'," .
		"'" . mysql_escape_string(@$rec[T_XNAME]) . "'," .
		"'" . mysql_escape_string(@$rec[T_DATE_BIRTH]) . "'," .
		"'" . mysql_escape_string(@$rec[T_EMAIL]) . "'," .
		"'" . mysql_escape_string(@$rec[T_PHONE]) . "'," .
		"'" . mysql_escape_string(@$rec[T_PHONEWORK]) . "'," .
		"'" . mysql_escape_string(@$rec[T_PHONECELL]) . "'," .
		"'" . mysql_escape_string(@$rec[T_PHONEPAGER]) . "'," .
		"'" . mysql_escape_string(@$rec[T_PHONEFAX]) . "'," .
		"'" . mysql_escape_string(@$rec[T_ADDR1]) . "'," .
		"'" . mysql_escape_string(@$rec[T_ADDR2]) . "'," .
		"'" . mysql_escape_string(@$rec[T_CITY]) . "'," .
		"'" . mysql_escape_string(@$rec[T_STATE]) . "'," .
		"'" . mysql_escape_string(@$rec[T_ZIP]) . "'," .
		"'" . mysql_escape_string(@$rec[T_ZIP4]) . "'," .
		"'" . mysql_escape_string(@$rec[T_IP]) . "'," .
		"'" . mysql_escape_string(@$rec[T_URL_REFER]) . "'" .
		")";

		// if we approach the max allowed packet size, do an insert, reset and continue
		if ($SQLLEN + strlen($val) >= $MYSQL_MAX_ALLOWED_PACKET - MYSQL_FUDGE_FACTOR)
		{
			_custdata_bulk_insert($sql, $vals, $ignore);
			// reset
			$SQLLEN = 0;
			$vals = array();
		}

		$SQLLEN += strlen($val);
		$vals[] = $val;
		$i++;

	}

	if ($SQLLEN > 0)
	{
		_custdata_bulk_insert($sql, $vals, $ignore);
	}

	$vals = $val = $SQLLEN = NULL;

	return TRUE;

}

/*
	PRIVATE FUNCTION that actually does bulk insertion for custdata_bulk_insert
	DO NOT USE, use custdata_bulk_insert instead
*/
function _custdata_bulk_insert(&$sql, &$vals, $ignore)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_array($vals));
		assert(is_bool($ignore));
	}

	// we have this ugly query with minimal whitespace to save every byte, bulk inserts, by
	// definition, can be very large
	$query = "INSERT " . ($ignore ? "IGNORE " : "") . "INTO custdata (" .
		"custdata_id," .
		"batch_id," .
		"date_signup," .
		"name_title," .
		"name_first," .
		"name_middle," .
		"name_last," .
		"name_extra," .
		"date_birth," .
		"email," .
		"phone," .
		"phone_work," .
		"phone_cell," .
		"phone_pager," .
		"phone_fax," .
		"addr1," .
		"addr2," .
		"city," .
		"state," .
		"zip," .
		"zip4," .
		"ip," .
		"url_refer" .
		") VALUES " . join(",", $vals);

	$rs = query_custdata($sql, $query);
	Error_2::Error_Test($rs, TRUE);

	$query = NULL;

}

function custdata_email_by_batch (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	SELECT
		custdata_id,
		email
	FROM
		custdata
	WHERE
		batch_id " . sql_where($batch_id) . "
	ORDER BY
		custdata_id ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$data = array();

	while (($rec = $sql->Fetch_Array_Row ($rs)) !== FALSE)
	{
		$data[$rec['custdata_id']] = $rec['email'];
	}

	$sql->Free_Result($rs);

	return $data;
}

// fetch all customer data by batch_id that has passed certain scrubs
// scrubs_passed is completely optional
function custdata_by_batch (&$sql, $batch_id, $scrubs_passed=array())
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
		assert(is_array($scrubs_passed));
	}

	$query = "
	SELECT
		custdata_id,
		batch_id,
		date_signup,
		name_title,
		name_first,
		name_middle,
		name_last,
		name_extra,
		date_birth,
		email,
		phone,
		phone_work,
		phone_cell,
		phone_pager,
		phone_fax,
		addr1,
		addr2,
		city,
		state,
		zip,
		zip4,
		ip,
		url_refer
	FROM
		custdata
	WHERE
		batch_id " . sql_where($batch_id) . "
		" . sql_custdata_scrubs_passed($scrubs_passed) . "
	ORDER BY
		custdata_id ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$data = array();

	while (($rec = $sql->Fetch_Array_Row ($rs)) !== FALSE)
	{
		$data[$rec['custdata_id']] = array(
			T_ID_CUSTDATA	=> $rec['custdata_id'],
			T_ID_BATCH	=> $rec['batch_id'],
			T_TNAME		=> $rec['name_title'],
			T_FNAME		=> $rec['name_first'],
			T_MNAME		=> $rec['name_middle'],
			T_LNAME		=> $rec['name_last'],
			T_XNAME		=> $rec['name_extra'],
			T_FULLNAME	=> $rec['name_first'] . ' ' . $rec['name_last'],
			T_EMAIL		=> $rec['email'],
			T_PHONE		=> $rec['phone'],
			T_PHONEWORK	=> $rec['phone_work'],
			T_PHONECELL	=> $rec['phone_cell'],
			T_PHONEPAGER	=> $rec['phone_pager'],
			T_PHONEFAX	=> $rec['phone_fax'],
			T_ADDR1		=> $rec['addr1'],
			T_ADDR2		=> $rec['addr2'],
			T_CITY		=> $rec['city'],
			T_STATE		=> $rec['state'],
			T_ZIP		=> $rec['zip'],
			T_ZIP4		=> $rec['zip4'],
			T_ZIPCODE	=> $rec['zip'] . ($rec['zip4'] ? '-' . $rec['zip4'] : ''),
			T_URL_REFER	=> $rec['url_refer'],
			T_DATE_BIRTH	=> $rec['date_birth'],
			T_DATE_SIGNUP	=> $rec['date_signup'],
			T_URL_REFER	=> $rec['ip']
		);
	}

	$sql->Free_Result($rs);

	return $data;
}

// fetch all customer data by batch_id that has passed certain scrubs
// scrubs_passed is completely optional
function custdata_ole_by_batch (&$sql, $batch_id, $scrubs_passed=array())
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
		assert(is_array($scrubs_passed));
	}

	$query = "
	SELECT
		custdata_id,
		name_first,
		name_middle,
		name_last,
		email,
		url_refer
	FROM
		custdata
	WHERE
		batch_id " . sql_where($batch_id) . "
		" . sql_custdata_scrubs_passed($scrubs_passed) . "
	ORDER BY
		custdata_id ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$data = array();

	while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
	{
		$data[$rec['custdata_id']] = array(
			T_FNAME		=> $rec['name_first'],
			T_MNAME		=> $rec['name_middle'],
			T_LNAME		=> $rec['name_last'],
			T_FULLNAME	=> $rec['name_first'] . ' ' . $rec['name_last'],
			T_EMAIL		=> $rec['email'],
			T_URL_REFER	=> $rec['url_refer']
		);
	}

	$sql->Free_Result($rs);

	return $data;
}

function custdata_name_by_batch (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	SELECT
		custdata_id,
		name_title,
		name_first,
		name_middle,
		name_last,
		name_extra
	FROM
		custdata
	WHERE
		batch_id " . sql_where($batch_id) . "
	ORDER BY
		custdata_id ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$data = array();

	while (($rec = $sql->Fetch_Array_Row ($rs)) !== FALSE)
	{
		$data[$rec['custdata_id']] = array(
			T_TNAME		=> $rec['name_title'],
			T_FNAME		=> $rec['name_first'],
			T_MNAME		=> $rec['name_middle'],
			T_LNAME		=> $rec['name_last'],
			T_XNAME		=> $rec['name_extra'],
			T_FULLNAME	=> $rec['name_first'] . ' ' . $rec['name_last'],
		);
	}

	$sql->Free_Result($rs);

	return $data;
}

function custdata_phone_by_batch (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	SELECT
		custdata_id,
		phone,
		phone_work,
		phone_cell,
		phone_pager,
		phone_fax,
		city,
		state,
		zip
	FROM
		custdata
	WHERE
		batch_id " . sql_where($batch_id) . "
	ORDER BY
		custdata_id ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$phone = array();

	while (($rec = $sql->Fetch_Array_Row ($rs)) !== FALSE)
	{
		$phone[$rec['custdata_id']] = array(
			T_PHONE		=> $rec['phone'],
			T_PHONEWORK	=> $rec['phone_work'],
			T_PHONECELL	=> $rec['phone_cell'],
			T_PHONEPAGER	=> $rec['phone_pager'],
			T_PHONEFAX	=> $rec['phone_fax'],
			T_CITY		=> $rec['city'],
			T_STATE		=> $rec['state'],
			T_ZIP		=> $rec['zip']
		);
	}

	$sql->Free_Result($rs);

	return $phone;
}

function custdata_address_by_batch (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	SELECT
		custdata_id,
		name_first,
		name_last,
		addr1,
		addr2,
		city,
		state,
		zip,
		zip4
	FROM
		custdata
	WHERE
		batch_id " . sql_where($batch_id) . "
	ORDER BY
		custdata_id ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$data = array();

	while (($rec = $sql->Fetch_Array_Row ($rs)) !== FALSE)
	{
		// convert data to standardized record
		$data[$rec['custdata_id']] = array(
			T_FNAME		=> $rec['name_first'],
			T_LNAME		=> $rec['name_last'],
			T_ADDR1		=> $rec['addr1'],
			T_ADDR2		=> $rec['addr2'],
			T_CITY		=> $rec['city'],
			T_STATE		=> $rec['state'],
			T_ZIP		=> $rec['zip'],
			T_ZIP4		=> $rec['zip4'],
			T_ZIPCODE	=> $rec['zip'] . ($rec['zip4'] ? '-' . $rec['zip4'] : ''),
		);
	}

	$sql->Free_Result($rs);

	return $data;
}

function custdata_update_name_by_id (&$sql, $id, $data)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($id));
		assert(is_array($data));
		//assert(count($data) == 5);
	}

	$query = "
	UPDATE custdata
	SET
		name_title = '" . mysql_escape_string(@$data[T_TNAME]) . "'
		,name_first = '" . mysql_escape_string(@$data[T_FNAME]) . "'
		,name_middle = '" . mysql_escape_string(@$data[T_MNAME]) . "'
		,name_last = '" . mysql_escape_string(@$data[T_LNAME]) . "'
		,name_extra = '" . mysql_escape_string(@$data[T_XNAME]) . "'
	WHERE
		custdata_id = '" . mysql_escape_string($id) . "'";

	return query_custdata($sql, $query);
}

function custdata_update_address_by_id (&$sql, $id, $addr)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($id));
		assert(is_array($addr));
	}

	$query = "
	UPDATE custdata
	SET
		addr1 = '" . mysql_escape_string($addr[T_ADDR1]) . "'
		,addr2 = '" . mysql_escape_string($addr[T_ADDR2]) . "'
		,city = '" . mysql_escape_string($addr[T_CITY]) . "'
		,state = '" . mysql_escape_string($addr[T_STATE]) . "'
		,zip = '" . mysql_escape_string($addr[T_ZIP]) . "'
		,zip4 = '" . mysql_escape_string($addr[T_ZIP4]) . "'
	WHERE
		custdata_id = '" . mysql_escape_string($id) . "'";

	return query_custdata($sql, $query);
}

function custdata_update_email_by_id (&$sql, $id, $email)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($id));
		assert(is_string($email));
	}

	$query = "
	UPDATE custdata
	SET
		email = '" . mysql_escape_string($email) . "'
	WHERE
		custdata_id = '" . mysql_escape_string($id) . "'";

	return query_custdata($sql, $query);
}

function custdata_update_phone_by_id (&$sql, $id, $phone_data)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($id));
		assert(is_array($phone_data));
	}

	$query = "
	UPDATE custdata
	SET
		phone		= '" . mysql_escape_string($phone_data[T_PHONE]) . "'
		,phone_work	= '" . mysql_escape_string($phone_data[T_PHONEWORK]) . "'
		,phone_cell	= '" . mysql_escape_string($phone_data[T_PHONECELL]) . "'
		,phone_pager	= '" . mysql_escape_string($phone_data[T_PHONEPAGER]) . "'
		,phone_fax	= '" . mysql_escape_string($phone_data[T_PHONEFAX]) . "'
	WHERE
		custdata_id = '" . mysql_escape_string($id) . "'";

	return query_custdata($sql, $query);
}

// update a custdata (user) record with fields in $data by id
// general function if the other custdata_update_*_by_id functions don't do it for you
function custdata_update_by_id (&$sql, $id, $data)
{
	die("you haven't written this yet!\n");
}

function custdata_delete_by_id (&$sql, $id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($id));
	}

	$query = "
	DELETE
	FROM
		custdata
	WHERE
		custdata_id = '" . mysql_escape_string($id) . "'";

	return query_custdata($sql, $query);
}

// deletes an entire batch-worth of records. use with extreme preju... i mean caution
function custdata_delete_by_batch (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	DELETE
	FROM
		custdata
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	return query_custdata($sql, $query);
}



// mark one or more emails invalid by their custdata_ids
function custdata_update_address_invalid_by_id (&$sql, $ids, $source_id, $errno)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
		assert(is_numeric($errno));
	}

	return custdata_update_invalid_by_id($sql, "addr", $ids, $source_id, $errno);
}

function custdata_update_address_valid_by_id (&$sql, $ids, $source_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
	}

	return custdata_update_valid_by_id($sql, "addr", $ids, $source_id);
}

// mark one or more emails invalid by their custdata_ids
function custdata_update_names_invalid_by_id (&$sql, $ids, $source_id, $errno)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
		assert(is_numeric($errno));
	}

	//debug_print("mark_names_invalid_by_id:ids: " . count($ids) . "\n");
	return custdata_update_invalid_by_id($sql, "name", $ids, $source_id, $errno);
}

function custdata_update_names_valid_by_id (&$sql, $ids, $source_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
	}

	return custdata_update_valid_by_id($sql, "name", $ids, $source_id);
}

// delete one or more custdata records because they are duplicated
// remember to update the batchs' duplicate field to reflect these deleted records!
function custdata_update_duplicate_by_id (&$sql, $ids)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
	}

	$query = "
	DELETE
	FROM
		custdata
	WHERE
		custdata_id " . sql_where($ids);

	return query_custdata($sql, $query);
}
















//////////////////////// BATCH-RELATED FUNCTIONS


function batch_create (&$sql, $account_id, $filename, $format_id, $raw_records=0, $data_source_id=0)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($account_id));
		assert(is_string($filename));
		assert(is_numeric($format_id));
		assert(is_numeric($raw_records));
		assert(is_numeric($data_source_id));
	}

	$query = "
	INSERT INTO batch (
		batch_id,
		account_id,
		data_source_id,
		batch_filename,
		batch_size,
		batch_active,
		batch_format,
		batch_created,
		batch_updated
	) VALUES (
		NULL,
		'" . mysql_escape_string($account_id) . "',
		'" . mysql_escape_string($data_source_id) . "',
		'" . mysql_escape_string($filename) . "',
		'" . mysql_escape_string($raw_records) . "',
		'" . STATUS_FALSE . "',
		'" . mysql_escape_string($format_id) . "',
		NOW(),
		NOW()
	)";

	$rs = query_custdata($sql, $query);
	Error_2::Error_Test($rs, TRUE);

	return $sql->Insert_Id();
}

function batch_num_recs (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	SELECT
		COUNT(*) AS cnt
	FROM
		custdata
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	$rs = query_custdata($sql, $query);
	Error_2::Error_Test($rs);

	$rec = $sql->Fetch_Array_Row($rs);

	return $rec["cnt"];

}

// date_from and date_to should be unix timestamps. easiest to create with php's strtotime() function
// returns an array of ids that match
// example to fetch only batches from 3 days ago that passed a name scrub:
//	batch_fetch_by_date($sql, array(SCRUB_NAME), strtotime("-3 days 0:00:00"), strtotime("-3 days 23:59:59"));

function batch_fetch_by_date (&$sql, $scrubs, $date_from, $date_to=NULL)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_array($scrubs));
		assert(is_numeric($date_from));
		assert($date_from > 0);
	}

	$WHERE = "batch_created >= FROM_UNIXTIME(" . intval($date_from) . ")";
	if ($date_to !== NULL)
	{
		$WHERE .= " AND batch_created <= FROM_UNIXTIME($date_to)";
	}

	$query = "
	SELECT
		batch_id
	FROM
		batch
	WHERE
		batch_active = '" . STATUS_TRUE . "'
	AND
		batch_locked = '" . STATUS_FALSE . "'
	AND
		$WHERE
	AND
		" . sql_batch_scrubs_passed($scrubs) . "
	ORDER BY
		batch_id ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$batch_ids = array();

	while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
	{
		$batch_ids[] = $rec['batch_id'];
	}

	$sql->Free_Result($rs);

	return $batch_ids;
}


function batch_fetch_by_vendor_id (&$sql, $scrubs, $vendor_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_array($scrubs));
		assert(is_numeric($vendor_id));
	}

	$query = "
	SELECT
		batch_id
	FROM
		batch
	WHERE
		batch_active = '" . STATUS_TRUE . "'
	AND
		batch_locked = '" . STATUS_FALSE . "'
	AND
		account_id = '" . mysql_escape_string($vendor_id) . "'
	AND
		" . sql_batch_scrubs_passed($scrubs) . "
	ORDER BY
		batch_id ASC";
	
	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$batch_ids = array();

	while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
	{
		$batch_ids[] = $rec['batch_id'];
	}

	$sql->Free_Result($rs);

	return $batch_ids;
}

function batch_fetch_by_id_cmp (&$sql, $ids, $cmp, $batch_active=STATUS_TRUE, $batch_locked=NULL)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
		assert(is_int($cmp));
	}

	if (!in_array($batch_locked, array(NULL, STATUS_TRUE, STATUS_FALSE)))
	{
		die("batch_locked needs to be either NULL or STATUS_TRUE or STATUS_FALSE. use NULL if you don't care.");
	}

	$query = "
	SELECT
		batch_id,
		account_id,
		data_source_id,
		batch_size,
		batch_dupes,
		batch_active,
		batch_format,
		batch_updated,
		batch_created,
		batch_scrubbed_name,
		batch_scrubbed_email,
		batch_scrubbed_phone,
		batch_scrubbed_addr,
		batch_locked,
		batch_locked_pid,
		batch_locked_host
	FROM
		batch
	WHERE
		" . sql_where_cmp($ids, $cmp, "batch_id") . "
	" . ($batch_active !== NULL ? " AND batch_active = '" . mysql_escape_string($batch_active) . "'" : '') . "
	" . ($batch_locked !== NULL ? " AND batch_locked = '" . mysql_escape_string($batch_locked) . "'" : '') . "";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$batches = array();

	while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
	{
		$batches[$rec['batch_id']] = $rec;
	}

	$sql->Free_Result($rs);

	return $batches;
}

function batch_fetch_by_id (&$sql, $ids, $batch_active=STATUS_TRUE, $batch_locked=NULL)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
	}

	if (!in_array($batch_locked, array(NULL, STATUS_TRUE, STATUS_FALSE)))
	{
		die("batch_locked needs to be either NULL or STATUS_TRUE or STATUS_FALSE. use NULL if you don't care.");
	}

	$query = "
	SELECT
		batch_id,
		account_id,
		data_source_id,
		batch_size,
		batch_dupes,
		batch_active,
		batch_format,
		batch_updated,
		batch_created,
		batch_scrubbed_name,
		batch_scrubbed_email,
		batch_scrubbed_phone,
		batch_scrubbed_addr,
		batch_locked,
		batch_locked_pid,
		batch_locked_host
	FROM
		batch
	WHERE
		batch_id " . sql_where($ids) . "
	" . ($batch_active !== NULL ? " AND batch_active = '" . mysql_escape_string($batch_active) . "'" : '') . "
	" . ($batch_locked !== NULL ? " AND batch_locked = '" . mysql_escape_string($batch_locked) . "'" : '') . "";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$batches = array();

	while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
	{
		$batches[$rec['batch_id']] = $rec;
	}

	$sql->Free_Result($rs);

	return $batches;

}


function batch_describe (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	SELECT
		batch_size	AS size,
		batch_dupes	AS dupes
	FROM
		batch
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	$rs = query_custdata($sql, $query);

	return $sql->Fetch_Array_Row($rs);
}

function batch_update_size (&$sql, $batch_id, $size, $relative=FALSE)
{
// this is only run when we first import the data

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
		assert(is_int($size));
		assert(is_bool($relative));
	}

	$query = "
	UPDATE
		batch
	SET
		batch_size = " . ($relative ? " batch_size + " : '') . mysql_escape_string($size) . "
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	return query_custdata($sql, $query);
}

function batch_update_dupes (&$sql, $batch_id, $dupes, $relative=TRUE)
{
// update the number of duplicate records found within a batch
// this is only run when we first import the data

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
		assert(is_int($dupes));
		assert(is_bool($relative));
	}

	$query = "
	UPDATE
		batch
	SET
		batch_dupes = " . ($relative ? " batch_dupes + " : '') . mysql_escape_string($dupes) . "
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	return query_custdata($sql, $query);
}

function batch_activate (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	UPDATE
		batch
	SET
		batch_active = '" . STATUS_TRUE . "'
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	return query_custdata($sql, $query);
}

function batch_deactivate (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	UPDATE
		batch
	SET
		batch_active = '" . STATUS_FALSE . "'
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	return query_custdata($sql, $query);
}

// locks batch in preparation for a scrub routine which will apply to the entire batch
// this is mainly so simultaneous scrub processes don't scrub the same batch
function batch_lock (&$sql, $batch_id, $field = "")
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
		assert(is_string($field));
	}

	$query = "
	UPDATE
		batch
	SET
		batch_locked" . (strlen($field) ? $field : "") . " = '" . STATUS_TRUE . "',
		batch_locked_pid = '" . mysql_escape_string(getmypid()) . "',
		batch_locked_host = '" . mysql_escape_string($_ENV["HOSTNAME"]) . "'
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	debug_print("Locking batch $batch_id...");

	$rs = query_custdata($sql, $query);

	debug_print("Batch $batch_id locked.");

	return $rs;
}

function batch_lock_name (&$sql, $batch_id)
{

	return batch_lock($sql, $batch_id, "_name");
}

function batch_lock_email (&$sql, $batch_id)
{

	return batch_lock($sql, $batch_id, "_email");
}

function batch_lock_phone (&$sql, $batch_id)
{

	return batch_lock($sql, $batch_id, "_phone");
}

function batch_lock_addr (&$sql, $batch_id)
{

	return batch_lock($sql, $batch_id, "_addr");
}

// reverse batch_unlock and makes batch available again
function batch_unlock (&$sql, $batch_id, $field = "")
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
		assert(is_string($field));
	}

	$query = "
	UPDATE
		batch
	SET
		batch_locked" . (strlen($field) ? $field : "") . " = '" . STATUS_FALSE . "'
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	debug_print("Unlocking batch $batch_id with: $query");

	$rs = query_custdata($sql, $query);

	debug_print("Batch $batch_id unlocked.");

	return $rs;
}


function batch_unlock_name (&$sql, $batch_id)
{

	return batch_unlock($sql, $batch_id, "_name");
}

function batch_unlock_email (&$sql, $batch_id)
{

	return batch_unlock($sql, $batch_id, "_email");
}

function batch_unlock_phone (&$sql, $batch_id)
{

	return batch_unlock($sql, $batch_id, "_phone");
}

function batch_unlock_addr (&$sql, $batch_id)
{

	return batch_unlock($sql, $batch_id, "_addr");
}


function batch_delete (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	$query = "
	DELETE
	FROM
		batch
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	return query_custdata($sql, $query);
}

function batch_update_scrubbed_name (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	return batch_update_scrubbed($sql, $batch_id, "name");
}

function batch_update_scrubbed_email (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	return batch_update_scrubbed($sql, $batch_id, "email");
}

function batch_update_scrubbed_address (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	return batch_update_scrubbed($sql, $batch_id, "addr");
}

function batch_update_scrubbed_phone (&$sql, $batch_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	return batch_update_scrubbed($sql, $batch_id, "phone");
}

// update one of the timestamp fields to reflect that the batch has been scrubbed
function batch_update_scrubbed (&$sql, $batch_id, $field)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($batch_id));
	}

	assert(strlen($field) > 0);

	$query = "
	UPDATE
		batch
	SET
		batch_scrubbed_{$field} = NOW()
	WHERE
		batch_id = '" . mysql_escape_string($batch_id) . "'";

	return query_custdata($sql, $query);
}

/*
	PRIVATE FUNCTION, used by other functions . do not use directly
	FIXME: all private functions should have leading _underscores in their names

	returns id of next available batch where $field is unscrubbed
	will return NULL if no batches are available
*/
function batch_next_unscrubbed (&$sql, $field)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($field));
	}

	assert(strlen($field) > 0);

	sql_lock_batch_table($sql);

	$query = "
	SELECT
		batch_id
	FROM
		batch
	WHERE
	(
		batch_locked = '" . STATUS_FALSE . "'
		AND batch_locked_name = '" . STATUS_FALSE . "'
		AND batch_locked_email = '" . STATUS_FALSE . "'
		AND batch_locked_phone = '" . STATUS_FALSE . "'
		AND batch_locked_addr = '" . STATUS_FALSE . "'
	) AND
		batch_active = '" . STATUS_TRUE . "'
	AND
		batch_scrubbed_{$field} = \"0000000000000000\"
	ORDER BY
		batch_id ASC
	LIMIT 1";

	$rs = query_custdata($sql, $query);

	if (Error_2::Error_Test($rs, FALSE))
	{
		// error has occured
		sql_unlock_tables($sql);
		exit;
	}

	$rec = $sql->Fetch_Array_Row($rs);

	sql_unlock_tables($sql);

	return $rec["batch_id"];

}

function batch_next_unscrubbed_name (&$sql)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	return batch_next_unscrubbed($sql, "name");
}

function batch_next_unscrubbed_email (&$sql)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}
	return batch_next_unscrubbed($sql, "email");
}

function batch_next_unscrubbed_phone (&$sql)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	return batch_next_unscrubbed($sql, "phone");
}

function batch_next_unscrubbed_address (&$sql)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	return batch_next_unscrubbed($sql, "addr");
}

// return the id of the next available batch that has had certain scrubs applied
// returns NULL if no batches are available that match the criteria
function batch_next_scrubbed(&$sql, $scrubs=array())
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_array($scrubs));
	}

	$query = "
	SELECT
		DISTINCT custdata.batch_id	AS batch_id
	FROM
		custdata
	JOIN
		batch
	ON
		batch.batch_id = custdata.batch_id
	AND
		batch.batch_locked = '" . STATUS_FALSE . "'
	AND
		batch.batch_active = '" . STATUS_TRUE . "'
	WHERE
		" . sql_batch_scrubs_passed($scrubs) . "
	ORDER BY
		batch_id ASC
	LIMIT
		1";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$rec = $sql->Fetch_Array_Row($rs);

	return $rec['batch_id'];
}


///////////////////////////////////////////// END OF BATCH-RELATED FUNCTIONS











//////////////////////////////////////////// NATL DATA-RELATED FUNCTIONS

function is_area_code_in_state (&$sql, $area_code, $state)
{
// we can use local caching with this data, because there aren't that many records in this table,
// around 100k or so

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($area_code));
		assert(is_string($state));
	}

	$query = "
	SELECT
		COUNT(*)	AS cnt
	FROM
		natl_fonedata_tel
	WHERE
		state = '" . mysql_escape_string($state) . "'
	AND
	(
		area_code = '" . mysql_escape_string($area_code) . "'
		OR
		new_area_code = '" . mysql_escape_string($area_code) . "'
	)";

	$rs = query_custdata($sql, $query);

	$rec = $sql->Fetch_Array_Row($rs);

	return $rec["cnt"] > 0;
}

function is_zip_code_in_state (&$sql, $zip_code, $state)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	// the 3 zipcode fields are not my doing, that was format of
	// the national data we bought and i wasn't given time to do anything
	// but a straight import, this should really be broken into separate records
	// based on zip for efficiency
	$query = "
	SELECT
		COUNT(*)	AS cnt
	FROM
		natl_fonedata_tel
	WHERE
		state = '" . mysql_escape_string($state) . "'
	AND
	(
		zip1 = '" . mysql_escape_string($zip_code) . "'
		OR
		zip2 = '" . mysql_escape_string($zip_code) . "'
		OR
		zip3 = '" . mysql_escape_string($zip_code) . "'
	)";

	$rs = query_custdata($sql, $query);

	$rec = $sql->Fetch_Array_Row($rs);

	return $rec["cnt"] > 0;

}

function is_phone_on_natl_donotcall (&$sql, $phone)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($phone));
	}

	$query = "
	SELECT
		COUNT(*)	AS cnt
	FROM
		natl_donotcall
	WHERE
		dnc_phone = '" . mysql_escape_string($phone) . "'";

	$rs = query_custdata($sql, $query);

	$rec = $sql->Fetch_Array_Row($rs);

	return $rec["cnt"] > 0;
}

function is_address_on_natl_donotmail (&$sql, $data)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_array($data));
	}

	$query = "
	SELECT
		COUNT(*)	AS cnt
	FROM
		natl_donotmail
	WHERE
		dnm_name_first = '" . mysql_escape_string($data[T_FNAME]) . "'
	AND
		dnm_name_last = '" . mysql_escape_string($data[T_LNAME]) . "'
	AND
		dnm_addr = '" . mysql_escape_string($data[T_ADDR1]) . "'
	AND
		dnm_city = '" . mysql_escape_string($data[T_CITY]) . "'
	AND
		dnm_state = '" . mysql_escape_string($data[T_STATE]) . "'
	AND
		dnm_zip = '" . mysql_escape_string($data[T_ZIP]) . "'";

	$rs = query_custdata($sql, $query);

	$rec = $sql->Fetch_Array_Row($rs);

	return $rec["cnt"] > 0;
}







////////////////////////////////////////// INTERNAL-LIST-RELATED FUNCTIONS

function add_internal_name_mask (&$sql, $mask, $source_id, $errno_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($mask));
		assert(is_numeric($source_id));
		assert(is_numeric($errno_id));
	}

	$query = "
	INSERT INTO bogus_name (
		bogus_name_id,
		mask,
		source_id,
		errno_id
	) VALUES (
		NULL,
		'" . mysql_escape_string($mask) . "',
		$source_id,
		$errno_id
	)";

	return query_custdata($sql, $query);
}

function delete_internal_name_mask_by_id (&$sql, $ids)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
	}

	$query = "
	DELETE
	FROM
		bogus_name
	WHERE
		bogus_name_id " . sql_where($ids);

	return query_custdata($sql, $query);
}

// returns array of patterns based on *-style from bogus_name
function fetch_internal_name_masks (&$sql)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	// order by length to try to shortest (and most likely) patterns
	// first
	$query = "
	SELECT
		bogus_name_id	AS id,
		mask
	FROM
		bogus_name
	ORDER BY
		LENGTH(mask) ASC,
		mask ASC";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	$bad_names  = array();

	while (($rec = $sql->Fetch_Array_Row ($rs)) !== FALSE)
	{
		$bad_names[$rec["id"]] = $rec["mask"];
	}

	$sql->Free_Result($rs);

	return $bad_names;
}


function delete_internal_domain_masks_by_id (&$sql, $ids)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
	}

	$query = "
	DELETE
	FROM
		domain_bad
	WHERE
		domain_bad_id " . sql_where($ids);

	return query_custdata($sql, $query);
}


function add_internal_domain_mask (&$sql, $mask, $source_id, $errno_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($mask));
		assert(is_numeric($source_id));
		assert(is_numeric($errno_id));
	}

	$query = "
	INSERT INTO domain_bad (
		domain_bad_id,
		domain_mask,
		source_id,
		errno_id
	) VALUES (
		NULL,
		'" . mysql_escape_string($mask) . "',
		$source_id,
		$errno_id
	)";

	return query_custdata($sql, $query);
}

// returns array of patterns based on *-style from bogus_name
function fetch_internal_domain_masks (&$sql)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	$query = "
	SELECT
		domain_bad_id	AS id,
		domain_mask	AS mask
	FROM
		domain_bad
	ORDER BY
		LENGTH(domain_mask) ASC,
		domain_mask ASC";

	$rs = query_custdata($sql, $query);

	$domains = array();

	while (($rec = $sql->Fetch_Array_Row ($rs)) !== FALSE)
	{
		$domains[$rec["id"]] = $rec["mask"];
	}

	$sql->Free_Result($rs);

	return $domains;
}

function add_internal_email_mask (&$sql, $mask, $source_id, $errno_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($mask));
		assert(is_numeric($source_id));
		assert(is_numeric($errno_id));
	}

	$query = "
	INSERT INTO email_bad (
		email_bad_id,
		email_mask,
		email_updated,
		source_id,
		errno_id
	) VALUES (
		NULL,
		'" . mysql_escape_string($mask) . "',
		NOW(),
		$source_id,
		$errno_id
	)";

	return query_custdata($sql, $query);
}


// returns array of patterns based on *-style from email_bad
function fetch_internal_email_masks (&$sql)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	$query = "
	SELECT
		email_bad_id
		,email_mask
	FROM
		email_bad
	ORDER BY
		email_mask ASC";

	$rs = query_custdata($sql, $query);

	$emails  = array();

	while (($rec = $sql->Fetch_Array_Row ($rs)) !== FALSE)
	{
		$emails[$rec["email_bad_id"]] = $rec["email_mask"];
	}

	$sql->Free_Result($rs);

	return $emails;
}


function delete_internal_email_masks_by_id (&$sql, $ids)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
	}

	$query = "
	DELETE
		FROM email_bad
	WHERE
		email_bad_id " . sql_where($ids);

	return query_custdata($sql, $query);
}


///////////////////////////////////////////////////// NAME-RELATED FUNCTIONS

function name_data_cleanup ($name_data)
{

	if (SCRUB_PARANOID)
	{
		assert(is_array($name_data));
	}

	reset($name_data);
	while (list($key, $name) = each($name_data))
	{
		$name_data[$key] = name_cleanup($name_data[$key]);
	}

	return $name_data;
}

// clean up a single piece of name data
function name_cleanup ($name)
{

	if (SCRUB_PARANOID)
	{
		assert(is_string($name));
	}

	// strip backslashes from name
	$name = str_replace('\\', '', $name);
	// force case
	// ucwords does not work on hypenated names
	$name = preg_replace('/\b([A-Za-z])([A-Za-z]*)\b/e', 'strtoupper("\\1").strtolower("\\2")', $name);
	// replace all periods... if they're followed by non-space chars, add a space
	$name = preg_replace('/\.([\S])?/e', '(strlen("\\1") ? " \\1" : "")', $name);
	// properly format "Bob Jones III"
	$name = str_replace('Iv', "IV", $name);
	$name = str_replace('Iii', "III", $name);
	$name = str_replace('Ii', "II", $name);
	// replace multiple spaces
	$name = preg_replace('/\s+/', ' ', trim($name));
	// replace all outlying non-alphas
	$name = preg_replace('/((^|\s)[^a-zA-Z]+|[^a-zA-Z]+(\s|$))/', '', $name);

	// strips quotes from names
	//$name = preg_replace('/\'"/', '', $name);

	return trim($name);
}

function address_cleanup ($addr)
{

	if (SCRUB_PARANOID)
	{
		assert(is_string($addr));
	}

	// trim spaces
	$addr = trim($addr);
	// force case
	$addr = preg_replace('/\b([a-zA-Z])(\w*)/e', 'strtoupper("\\1") . strtolower("\\2")', $addr);
	return $addr;

}


// a bunch of masks are stored in the database, we pull them out, convert them
// to regular expressions and run checks against them. this function does the
// conversion from *hello* to ^.*hello.*$
function asterisk_to_preg($strs=array())
{

	if (SCRUB_PARANOID)
	{
		assert(is_array($strs));
	}

	debug_print("asterisk_to_preg($strs)...");

	$results = array();

	debug_dump($strs);

	for ($i = 0; $i < count($strs); $i++)
	{
		$str = $strs[$i];
		$tmp = preg_replace('/([].+^$[|\/?-])/', "\\\\$1", $str);
		$tmp = str_replace('*', '.*?', $tmp);
		if (substr($tmp, 0, 3) == '.*?')
		{
			$tmp = substr($tmp, 3);
		}
		else
		{
			$tmp = '^' . $tmp;
		}

		if (substr($tmp, -3) == '.*?'){
			$tmp = substr($tmp, 0, strlen($tmp) - 3);
		}
		else
		{
			$tmp .= '$';
		}
		$results[] = "/$tmp/i";
	}

	return $results;
}




//////////////////////////////////////////// PHONE_RELATED FUNCTIONS

// mark one or more emails invalid by their custdata_ids
function custdata_update_phone_invalid_by_id (&$sql, $ids, $source_id, $errno)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
		assert(is_numeric($errno));
	}

	return custdata_update_invalid_by_id($sql, "phone", $ids, $source_id, $errno);
}

function custdata_update_phone_valid_by_id (&$sql, $ids, $source_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
	}

	return custdata_update_valid_by_id($sql, "phone", $ids, $source_id);
}

function custdata_update_phone_invalid_by_value (&$sql, $vals, $source_id, $errno)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($vals) || is_array($vals));
		assert(is_numeric($source_id));
		assert(is_numeric($errno));
	}
	return custdata_update_invalid_by_value($sql, "phone", $vals, $source_id, $errno);
}

function custdata_update_phone_valid_by_value (&$sql, $vals, $source_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($vals) || is_array($vals));
		assert(is_numeric($source_id));
	}

	return custdata_update_valid_by_value($sql, "phone", $vals, $source_id);
}

function custdata_update_phone_is_fax_by_value (&$sql, $vals, $source_id, $errno)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($vals) || is_array($vals));
		assert(is_numeric($source_id));
		assert(is_numeric($errno));
	}

	// set the phone
	$query = "
	UPDATE
		custdata
	SET
		phone_fax = phone,
		phone = '',
		valid_phone = '" . STATUS_TRUE . "',
		source_id_phone = '" . mysql_escape_string($source_id) . "',
		errno_phone = '" . mysql_escape_string($errno) . "'
	WHERE
		phone " . sql_where($vals) . "
	AND
		phone_fax = ''";

	return query_custdata($sql, $query);
}




/////////////// EMAIL-RELATED FUNCTIONS

// mark one or more emails invalid by their custdata_ids
function custdata_update_email_invalid_by_id (&$sql, $ids, $source_id, $errno)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
		assert(is_numeric($errno));
	}

	return custdata_update_invalid_by_id($sql, "email", $ids, $source_id, $errno);
}

function custdata_update_email_valid_by_id (&$sql, $ids, $source_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
	}

	return custdata_update_valid_by_id($sql, "email", $ids, $source_id);
}

function custdata_update_email_invalid_by_value (&$sql, $vals, $source_id, $errno)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($vals) || is_array($vals));
		assert(is_numeric($source_id));
		assert(is_numeric($errno));
	}

	return custdata_update_invalid_by_value($sql, "email", $vals, $source_id, $errno);
}

function custdata_update_email_valid_by_value (&$sql, $vals, $source_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_numeric($vals) || is_array($vals));
		assert(is_numeric($source_id));
	}

	return custdata_update_valid_by_value($sql, "email", $vals, $source_id);
}

// correct common, simple data input errors in email addresses
function email_cleanup ($email)
{

	if (SCRUB_PARANOID)
	{
		assert(is_string($email));
	}

	if (strpos($email, '@') === FALSE)
	{
		return $email;
	}

	// remove all space characters
	$email = preg_replace('/\s+/', '', $email);

	// split into user, domain
	list ($username, $domain) = explode('@', $email);

	// CLEAN UP USERNAME

	// nah

	// CLEAN UP DOMAIN NAME

	// replace doubled-up dots in domain, which would never be legal anyhow
	$domain = preg_replace('/\.{2,}/', '.', $domain);

	// remove dots at the beginning or end of the domain
	$domain = preg_replace('/(^\.+|\.+$)/', '', $domain);

	// recombine pieces back into email address
	$email = "$username@$domain";

	return $email;
}


/////////////////////////////////////////// DOMAIN-RELATED FUNCTIONS

function validate_domain (&$sql, $domain, $new=FALSE)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($domain));
		assert(is_bool($new));
	}

	debug_print("validate_domain($sql, $domain, $new)...");

	$ok = checkdnsrr($domain, 'MX') ? TRUE : FALSE;

	$query = "
	INSERT INTO domain_good (
		domain_good_id,
		domain,
		ok,
		lastchecked
	) VALUES (
		NULL,
		'" . mysql_escape_string($domain) . "',
		'" . ($ok ? 'Y' : 'N') . "',
		NOW()
	)";

	$rs = query_custdata($sql, $query);

	switch ($sql->Get_Errno())
	{
	case MYSQL_OK:
		// everything is cool
		break;
	case MYSQL_ERR_DUP_ENTRY:
		// domain already exists, this is ok
		break;
	default: // some other weird error
		// force code to die with error
		Error_2::Error_Test($rs, TRUE);
		break;
	}

	// return earlier results of the checkdnsrr call earlier in the function
	return $ok;
}

// check if the domain is good or not
function lookup_domain (&$sql, $domain, $days_old=1)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($domain));
		assert(is_int($days_old));
	}


	debug_print("lookup_domain($sql, $domain, $days_old)...");

	if ($days_old < 1)
	{
		$days_old = 1;
	}

	$timestamp = strtotime("-$days_old days 00:00:00");

	$query = "
	SELECT
		ok,
		UNIX_TIMESTAMP(lastchecked) AS lastchecked
	FROM
		domain_good
	WHERE
		domain = '" . mysql_escape_string($domain) . "'";

	$rs = query_custdata($sql, $query);

	Error_2::Error_Test($rs, TRUE);

	// if the domain is NOT cached in the domain_good table, look it up
	if ($sql->Row_Count($rs) == 0)
	{
		return validate_domain($sql, $domain, TRUE);
	}
	else // if it IS cached, check if it's recent enough
	{
		$rec = $sql->Fetch_Array_Row($rs);
		if ($rec["lastchecked"] < $timestamp)
		{
			return validate_domain($sql, $domain, FALSE);
		}
		else
		{
			return $rec["ok"] == "Y";
		}
	}



}

/////////////////////////////////////////////// PRIVATE FUNCTIONS
// generally, these are generalized functions that are built upon by other public functions in the library
// do not use these directly!



/**
	@publicsection
	@public
	@fn custdata_update_valid_by_id ($sql, $field, $id, $source_id, $errno)
	@brief
		Mark a set of customer data valid by the custdata_id

	@param $sql resource		MySQL3 object
	@param $field resource		name of the field we're updating
	@param $id mixed		lone id or array of ids we're updating
	@param $source_id integer	source id of the application making the change
	@param $errno integer

	@return
**/
function custdata_update_invalid_by_value (&$sql, $field, $vals, $source_id, $errno)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($field) && strlen($field) > 0);
		assert(is_numeric($vals) || is_array($vals));
		assert(is_numeric($source_id));
		assert(is_numeric($errno));
	}

	// since we're dealing with large recordsets, this update statement could be too large for mysql's
	// net buffer to handle, so we need to split it up into multiple update statements
	// downside: this is slower and more complicated, and there is some duplicate code

	// the basic structure of the query stays the same, no matter what $vals is... it's just the
	// WHERE clause that changes
	$basequery = "
	UPDATE
		custdata
	SET
		valid_{$field} = '" . STATUS_FALSE . "',
		source_id_{$field} = {$source_id},
		errno_{$field} = {$errno}
	WHERE
		{$field} ";

	
	// longest likely mean length of items in $vals, should $vals be an array
	$LENGTH = 32;

	return custdata_base_query($sql, $basequery, $vals, $LENGTH);

}


/**
	@publicsection
	@public
	@fn custdata_update_valid_by_id ($sql, $field, $id, $source_id, $errno)
	@brief
		Mark a set of customer data valid by the custdata_id

	@param $sql resource		MySQL3 object
	@param $field resource		name of the field we're updating
	@param $id mixed		lone id or array of ids we're updating
	@param $source_id integer	source id of the application making the change
	@param $errno integer

	@return
**/
function custdata_update_valid_by_value (&$sql, $field, $vals, $source_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($field) && strlen($field) > 0);
		assert(is_numeric($vals) || is_array($vals));
		assert(is_numeric($source_id));
	}

	$basequery = "
	UPDATE
		custdata
	SET
		valid_{$field} = '" . STATUS_TRUE . "',
		source_id_{$field} = $source_id
	WHERE
		{$field} ";

	// longest likely mean length of items in $vals, should $vals be an array
	$LENGTH = 32;

	return custdata_base_query($sql, $basequery, $vals, $LENGTH);

}

/**
	@publicsection
	@public
	@fn custdata_update_valid_by_id ($sql, $field, $id, $source_id, $errno)
	@brief
		Mark a set of customer data valid by the custdata_id

	@param $sql resource		MySQL3 object
	@param $field resource		name of the field we're updating
	@param $id mixed		lone id or array of ids we're updating
	@param $source_id integer	source id of the application making the change
	@param $errno integer

	@return
**/
function custdata_update_invalid_by_id (&$sql, $field, $ids, $source_id, $errno)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($field) && strlen($field) > 0);
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
		assert(is_numeric($errno));
	}

	debug_print("custdata_update_invalid_by_id($sql, $field, $id(" . count($id) . "), $source_id, $errno)");

	$basequery = "
	UPDATE
		custdata
	SET
		valid_{$field} = '" . STATUS_FALSE . "',
		source_id_{$field} = '" . mysql_escape_string($source_id) . "',
		errno_{$field} = '" . mysql_escape_string($errno) . "'
	WHERE
		custdata_id ";

	// the longest likely average length of ids in $ids, should $ids be an array
	$LENGTH = 9; // we shouldn't have too many over 999,999,999

	return custdata_base_query($sql, $basequery, $ids, $LENGTH);

}


/**
	@publicsection
	@public
	@fn custdata_update_valid_by_id ($sql, $field, $id, $source_id, $errno)
	@brief
		Mark a set of customer data valid by the custdata_id

	@param $sql resource		MySQL3 object
	@param $field resource		name of the field we're updating
	@param $id mixed		lone id or array of ids we're updating
	@param $source_id integer	source id of the application making the change
	@param $errno integer

	@return
**/
function custdata_update_valid_by_id (&$sql, $field, $ids, $source_id)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($field) && strlen($field) > 0);
		assert(is_numeric($ids) || is_array($ids));
		assert(is_numeric($source_id));
	}

	debug_print("custdata_update_valid_by_id($sql, $field, $id(" . count($id) . "), $source_id)");

	$basequery = "
	UPDATE
		custdata
	SET
		valid_{$field} = '" . STATUS_TRUE . "',
		source_id_{$field} = '" . mysql_escape_string($source_id) . "'
	WHERE
		custdata_id ";

	// the longest likely average length of ids in $ids, should $ids be an array
	//FIXME: geto
	$LENGTH = 9;

	return custdata_base_query($sql, $basequery, $ids, $LENGTH);
}

/*
	a generalized function for splitting a potentially large query into smaller
	queries to get around the limitation of mysql's net buffer length

	any queries that can operate on many specific values in the case of an IN()
	clause use this to split up the query

*/
function custdata_base_query(&$sql, $basequery, $where_values, $length, $error_test=TRUE, $die_on_failure=TRUE)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($basequery));
		assert(is_numeric($where_values) || is_array($where_values));
		assert(is_int($length));
		assert(is_bool($error_test));
		assert(is_bool($die_on_failure));
	}

	global $MYSQL_MAX_ALLOWED_PACKET;

	// more than one value, potentially ALOT more
	// we split data up into possibly separate queries, based on $MYSQL_MAX_ALLOWED_PACKET
	if (is_array($where_values))
	{
		// account for quotes and a comma
		$length += 3;

		$STEP = intval($MYSQL_MAX_ALLOWED_PACKET / $length);

		for ($i = 0; $i < count($where_values); $i += $STEP)
		{
			$tmpwhere_values = array_slice($where_values, $i, $STEP - 1);
			$query = $basequery . sql_where($tmpwhere_values);
			debug_print($query);
			$rs = query_custdata($sql, $query);
			if ($error_test)
			{
				Error_2::Error_Test($rs, $die_on_failure);
			}
		}
	}
	else
	{
		$query = $basequery . sql_where($where_values);
		debug_print($query);
		$rs = query_custdata($sql, $query);
		if ($error_test)
		{
			Error_2::Error_Test($rs, $die_on_failure);
		}
	}

	// return the last resultset
	return $rs;

}

function cmp_to_sql_op($cmp)
{

	if (SCRUB_PARANOID)
	{
		assert(is_int($cmp));
	}

	switch ($cmp)
	{
	case CMP_EQ:
		return '=';
		break;
	case CMP_NEQ:
		return '!=';
		break;
	case CMP_GT:
		return '>';
		break;
	case CMP_GTEQ:
		return '>=';
		break;
	case CMP_LT:
		return '<';
		break;
	case CMP_LTEQ:
		return '<=';
		break;
	default:
		die("Invalid comparator '" . $cmp . "'");
		break;
	}
}

function sql_where ($mixed)
{

	if (SCRUB_PARANOID)
	{
		assert(is_string($mixed) || is_array($mixed));
	}

	if (is_array($mixed))
	{
		$where = "IN ('" . join("','", $mixed) . "')";
	}
	else
	{
		$where = "= '$mixed'";
	}

	return $where;
}

// creates an sql WHERE clause based on input
// TODO: right now we OR everything... add ability to customize logic as well
function sql_where_cmp ($mixed, $cmp, $field)
{

	if (SCRUB_PARANOID)
	{
		assert(is_numeric($mixed) || is_array($mixed));
		assert(is_int($cmp));
		assert(is_string($field));
	}

	if ($cmp == CMP_EQ)
	{
		if (is_array($mixed))
		{
			$where = "$field IN ('" . join("','", sql_escape_array($mixed)) . "')";
		}
		else
		{
			$where = "$field = '" . mysql_escape_string($mixed) . "'";
		}
	}
	else if ($cmp == CMP_NEQ)
	{
		if (is_array($mixed))
		{
			$where = "$field NOT IN ('" . join("','", sql_escape_array($mixed)) . "')";
		}
		else
		{
			$where = "$field != '" . mysql_escape_string($mixed) . "'";
		}
	}
	else // requires $field
	// other comparisons should operate only on one element... you wouldn't say a > 5 or a > 6
	{
		// more than one comparison
		if (is_array($mixed))
		{
			if (!is_array($cmp))
			{
				debug_dump($mixed);
				debug_dump($cmp);
				debug_dump($field);
				die("if mixed is an array, all ops must be an array");
			}
			if (count($cmp) != count($mixed))
			{
				debug_dump($mixed);
				debug_dump($cmp);
				debug_dump($field);
				die("cmp and value arrays must be the same length");
			}

			$vals = array();
			// with every comparison...
			for ($i = 0; $i < count($cmp); $i++)
			{
				if (is_array($field))
				{
					if ($i >= count($field))
					{
						// stick to the last field if we run out of entries
						$tmpfield = $field[count($field)-1];
					}
					else
					{
						$tmpfield = $field[$i];
					}
				}
				else
				{
					$tmpfield = $field;
				}
				if (is_array($mixed[$i]))
				{
					// recursion to handle complex comparisons
					$vals[] = sql_where_cmp($mixed[$i], $cmp[$i], $tmpfield);
				}
				else
				{
					$vals[] = "({$tmpfield} " . cmp_to_sql_op($cmp[$i]) . " " . mysql_escape_string($mixed[$i]) . ")";
				}
			}
			$where = "(" . join(" OR ", $vals) . ")";
			
		}
		else
		{
			$where = "(" . mysql_escape_string($field) . " " . cmp_to_sql_op($cmp) . " '" . mysql_escape_string($mixed) . "')";
		}
	}

	return $where;
}

// apply mysql_escape_string to all items in an array
function sql_escape_array($array)
{

	if (SCRUB_PARANOID)
	{
		assert(is_array($array));
	}

	return array_map("mysql_escape_string", $vals);

}

// build the sql necessary to only fetch customer who have passed a certain combination of scrubs
function sql_batch_scrubs_passed ($scrubs=array())
{

	if (SCRUB_PARANOID)
	{
		assert(is_array($scrubs));
	}

	$SQL = "";
	for ($i = 0; $i < count($scrubs); $i++)
	{
		$scrub = $scrubs[$i];
		switch ($scrub)
		{
		case SCRUB_NAME:
			$field = 'batch_scrubbed_name';
			break;
		case SCRUB_EMAIL:
			$field = 'batch_scrubbed_email';
			break;
		case SCRUB_ADDRESS:
			$field = 'batch_scrubbed_addr';
			break;
		case SCRUB_PHONE:
			$field = 'batch_scrubbed_phone';
			break;
		default:
			die("PROGRAMMING ERROR: scrub '$scrub' is not defined! check your the defined SCRUB_* at the top of the library!!!!\n");
			break;
		}
		$SQL .= ($scrub != $scrubs[0] ? " AND" : "") .  " $field > 0";
	}
	return $SQL;
}

// build the sql necessary to only fetch customer who have passed a certain combination of scrubs
function sql_custdata_scrubs_passed ($scrubs=array())
{

	if (SCRUB_PARANOID)
	{
		assert(is_array($scrubs));
	}

	$query = "";

	debug_dump($scrubs);

	if (!is_array($scrubs))
	{
		debug_dump($scrubs);
		debug_print("scrubs is empty?!");
	}

	for ($i = 0; $i < count($scrubs); $i++)
	{
		$scrub = $scrubs[$i];
		switch ($scrub)
		{
		case SCRUB_NAME:
			$field = 'valid_name';
			break;
		case SCRUB_EMAIL:
			$field = 'valid_email';
			break;
		case SCRUB_ADDRESS:
			$field = 'valid_addr';
			break;
		case SCRUB_PHONE:
			$field = 'valid_phone';
			break;
		default:
			die("PROGRAMMING ERROR: scrub '$scrub' is not defined! check your the defined SCRUB_* at the top of the library!!!!\n");
			break;
		}
		$query .= " AND $field = '" . STATUS_TRUE . "'";
	}
	return $query;
}

// connect to
function connect_mgmt()
{

	$sql = new MySQL_3();
	$sql->Connect (
		NULL,
		DB_MGMT_HOST,
		DB_MGMT_USER,
		DB_MGMT_PASS,
		Debug_1::Trace_Code(__FILE__, __LINE__)
	);

	return $sql;
}

// connect to
function connect_scrubber()
{

	$sql = new MySQL_3();
	$sql->Connect (
		NULL,
		DB_HOST,
		DB_USER,
		DB_PASS,
		Debug_1::Trace_Code(__FILE__, __LINE__)
	);

	return $sql;
}

/**
	@publicsection
	@public
	@fn
	@brief

	@param
	@param
	@param

	@return
**/
function query_custdata (&$sql, &$query, $db=DB_NAME)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($query));
		assert(is_string($db));
	}
	
	global $MYSQL_MAX_ALLOWED_PACKET;

	if (strlen($query) > $MYSQL_MAX_ALLOWED_PACKET)
	{
		debug_warn("query is larger than \$MYSQL_MAX_ALLOWED_PACKET ($MYSQL_MAX_ALLOWED_PACKET)!");
	}

	debug_sql($query);

	$rs = $sql->Query (
		$db,
		$query,
		NULL
	);

	//Error_2::Error_Test($rs, TRUE);

	return $rs;
}

// execute a query against the mgmt database, ob dev01 as of this writing
function query_mgmt (&$sql, $query)
{

	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
		assert(is_string($query));
	}

	debug_sql($query);

	$rs = $sql->Query (
		DB_MGMT_NAME,
		$query,
		Debug_1::Trace_Code (__FILE__, __LINE__)
	);

	Error_2::Error_Test($rs, TRUE);

	return $rs;
}

// http://www.mysql.com/doc/en/LOCK_TABLES.html
function sql_lock_batch_table(&$sql)
{
	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}

	$query = "
	LOCK TABLES batch";

	return query_custdata($sql, $query);
}


// unlock batch table
function sql_unlock_batch_table(&$sql)
{
	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}
	// in reality mysql can only unlock all tables one has a lock on, so that's exactly what we do
	return sql_unlock_tables($sql);
}

/*
	releases locks on all tables this connection currently has a lock on
	this the way mysql works
	http://www.mysql.com/doc/en/LOCK_TABLES.html
*/
function sql_unlock_tables(&$sql)
{
	if (SCRUB_PARANOID)
	{
		assert(is_object($sql) && is_a($sql, CLASS_MYSQL_OBJ));
	}
	$query = "UNLOCK TABLES";
	return query_custdata($sql, $query);
}


// data manipulation function... puts $mixed in an array unless it already is one

function to_array(&$mixed)
{

	if (SCRUB_PARANOID)
	{
		//assert(isset($mixed));
	}

	debug_print("to_array($mixed)...");

	if (!is_array($mixed))
	{
		$mixed = array($mixed);
	}

	return $mixed;
}

// vim: set ts=8:

?>
