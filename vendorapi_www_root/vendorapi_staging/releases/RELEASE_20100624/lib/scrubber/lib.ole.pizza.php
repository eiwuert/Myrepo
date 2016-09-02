<?php

/*
	pizza rewrote the ole_import code:
	old code: for each record:
		spawned its own process
		created a fresh connection
		had no error checking
		used mysql.2
		was poorly formatted
		has a large section of code duplicated
	this one will use an existing mysql.3 conn, or create
	one for you and give it back so you can reuse it,
	this also accepts batches and does as much error checking
	as was possible in the few hours i spent on it

	FIXME: the ole_import function could be broken down even further
	into distinct functions that do one thing well
*/

// ole OLE used mysql.2, which is infinately not better than mysql.3
require_once("/virtualhosts/lib/mysql.3.php");
require_once("/virtualhosts/lib/debug.1.php");
require_once("/virtualhosts/lib/error.2.php");

// database information
define('OLE_DB_HOST',	'selsds001');
define('OLE_DB_USER',	'sellingsource');
define('OLE_DB_PASS',	'password');
define('OLE_DB_PORT',	3306);
define('OLE_DB_NAME',	'oledirect2');

// domains OLE should not insert records for, as they are test addresses
// these should all be lowercase
$OLE_IGNORE_DOMAINS = array (
	"sellingsource.com",
	"nowhere.com",
	"test.test"
);

function ole_debug_sql($query)
{
	echo array_sum(explode(' ', microtime())) . ": " . preg_replace('/\s+/', ' ', $query) . "\n";
}

function create_ole_conn()
{
	$sql = new MySQL_3();
	// connect for reading and writing
	$sql->Connect(NULL, OLE_DB_HOST, OLE_DB_USER, OLE_DB_PASS, Debug_1::Trace_Code(__FILE__, __LINE__));
	Error_2::Error_Test($rs, TRUE);

	return $sql;
}

/*
	$sql can either be an exising MySQL_3 object so you can avoid the overhead of making
	another database connection, or if it is NULL then the function will connect for you

	$recs can either be a single-dimensional array of
		array("email", "first", "last", list_id, site_id, "ip")
	or an array of the same record

	NOTE:
		nah
*/
function ole_import(&$sql, $recs)
{

	############################### begin opening assertions. ensure we get sane stuff in

	// this stuff is critical and should kill the script on failure
	//assert_options(ASSERT_BAIL, 1);

	assert((is_object($sql) && is_a($sql, "MySQL_3")) || $sql == NULL);
	assert(is_array($recs));

	if (!is_array($recs[0]))
	{
		assert(count($recs) == 6);
		// make a single-dimensional array into a 2x dimensional one, so
		// we can loop over any input exactly the same way
		$recs = array($recs);
	}

	// scan every record going in, make sure they all contain all the right things
	// and nothing else
	// does this seem like overkill? too bad, i'd rather write better code

	for ($i = 0; $i < count($recs); $i++)
	{
		assert(count($recs[$i]) == 6);
		// email
		assert(isset($recs[$i][0]));
			assert(is_string($recs[$i][0]));
		// first
		assert(isset($recs[$i][1]));
			assert(is_string($recs[$i][1]));
		// last
		assert(isset($recs[$i][2]));
			assert(is_string($recs[$i][2]));
		// list_id
		assert(isset($recs[$i][3]));
			assert(is_numeric($recs[$i][3]));
		// site_id
		assert(isset($recs[$i][4]));
			assert(is_numeric($recs[$i][4]));
		// ip
		assert(isset($recs[$i][5]));
			assert(is_string($recs[$i][5]));
	}

	############################### end opening assertions


	// make global domain array of domains we should ignore available in this scope
	// NOTE: this was being defined in the function itself. this isn't efficient because
	// if you load the file once and run the function 1000 times, you're redeclaring the
	// array 999 times more than you need to. this way it's only declared once
	global $OLE_IGNORE_DOMAINS;

	// if $sql was passed in as NULL then automatically create a connection
	if ($sql === NULL)
	{
		$sql = create_ole_conn();
	}

	for ($i = 0; $i < count($recs); $i++)
	{

		//for ($j = $i; $j < $i + $AT_A_TIME; $j++) {

		// get the $i-th record to be inserted
		$fields =
			array(
				'email'		=> $recs[$i][0],
				'first'		=> $recs[$i][1],
				'last'		=> $recs[$i][2],
				'list_id'	=> $recs[$i][3],
				'site_id'	=> $recs[$i][4],
				'ip'		=> $recs[$i][5]
			);

		// split email address at the @ so we can get the domain
		$email = strtolower($fields["email"]);

		// a little debug code
		echo "processing $i:$email...\n";

		list($fields["email_user"], $fields["email_domain"]) = explode("@", $email);
	
		// [development test-URL filter]
		if (!in_array($fields["email_domain"], $OLE_IGNORE_DOMAINS))
		{

			list($already_in_ole, $personindex_id, $lists) = ole_person_exists($sql, $email);

			if (!$already_in_ole)
			{
				echo "not in ole! adding...\n";
				// throw them into the personindex table 
				$personindex_id = ole_person_add($sql, $fields);
				$lists = array();//strval($fields['list_id']));
			}

			// the person now exists in OLE
			// personindex_id and lists now exist
			assert(isset($personindex_id));
			assert(is_array($lists));
			
			// lists will always contain strings, either from the database or from our supplied list_id which
			// is converted to strval()

			if (!in_array(strval($fields['list_id']), $lists))
			{

				// put person in list_# table
				ole_person_add_to_list($sql, $personindex_id, $fields);

				// if we just added this person to a new list but didn't just
				// insert their user, then their record doesn't have the current list
				// in it. update it
				if ($already_in_ole)
				{
					ole_list_add_to_person($sql, $personindex_id, $fields['list_id']);
				}
			} // end $fields[list_id] in $lists
		} // end ignored domain check
	} //end $recs loop

	// assuming we make it all the way through, we return the mysql connection, which the caller
	// can use if they don't have one... they can then pass it back if they call this function
	// again, saving another connection
	return $sql;

} //emd function

function ole_person_exists(&$sql, $email)
{

	// see if this email is in OLE at all
	$query = "
	SELECT
		ID,
		lists
	FROM
		personindex
	WHERE
		email = '" . mysql_escape_string($email) ."'";

	$rs = $sql->Query(
		OLE_DB_NAME,
		$query
	);
	Error_2::Error_Test($rs);

	$rec = $sql->Fetch_Array_Row($rs);

	// if the email does not already exist in personindex
	// we need to remember this fact later, so store it in a variable
	$exists_in_ole = ($rec !== FALSE);

	assert(is_bool($exists_in_ole));

	// assign lists from this user to the $lists var passed in by ref
	if ($exists_in_ole)
	{
		$personindex_id = $rec['ID'];
		$lists = explode(',', $rec['lists']);
	}
	else
	{
		$personindex_id = NULL;
		$lists = array();
	}

	echo "return from ole_person_exists\n";
	// hmmm, should we do this or pass these in by ref and alter them? not sure...
	return array($exists_in_ole, $personindex_id, $lists);

}

function ole_person_add(&$sql, $fields)
{

	echo "ole_person_add($sql, $fields)\n";

	assert(is_object($sql) && is_a($sql, "MySQL_3"));
	assert(is_array($fields));

	assert(is_string($fields['email']));
	assert(is_numeric($fields['list_id']));
	assert(is_string($fields['first']));
	assert(is_string($fields['last']));

	// we really like md5s
	$secret_code = md5($fields['list_id'] . $fields['email']);

	// insert user into a particular list

	// they are not in the system at all, add personindex then list_### row
	$query = "
	INSERT INTO personindex (
		email,
		name,
		last,
		first,
		lists
	) VALUES (
		'" . mysql_escape_string($fields['email']) . "',
		'" . mysql_escape_string($fields['first'] . ' ' . $fields['last']) . "',
		'" . mysql_escape_string($fields['last']) . "',
		'" . mysql_escape_string($fields['first']) . "',
		'" . mysql_escape_string($fields['list_id']) . "'
	)";

	ole_debug_sql($query);

	// insert record into table
	$rs = $sql->Query(OLE_DB_NAME, $query);//, Debug_1::Trace_Code(__FILE__, __LINE__));
	Error_2::Error_Test($rs);

	return $sql->Insert_Id();

}

/*
	
*/
function ole_person_add_to_list(&$sql, $personindex_id, $fields)
{

	assert(is_object($sql) && is_a($sql, "MySQL_3"));
	assert(is_numeric($personindex_id));
	assert(is_array($fields));

	assert(is_numeric($fields['list_id']));
	assert(is_string($fields['email']));
	assert(is_numeric($fields['site_id']));
	assert(is_string($fields['first']));
	assert(is_string($fields['last']));
	assert(is_string($fields['email_domain']));
	assert(is_string($fields['ip']));

	// we really like md5s
	$secret_code = md5($fields['list_id'] . $fields['email']);

	// insert user into a particular list
	//NOTE: storing fields a = a, b = b and c = a . b is a waste of a + b space!
	$query = "
	INSERT INTO list_{$fields["list_id"]} (
		piID,
		sID,
		email,
		name,
		last,
		first,
		secret_code,
		domain,
		added,
		addedtime,
		IPaddress
	) VALUES (
		'" . mysql_escape_string($personindex_id) . "',
		'" . mysql_escape_string($fields['site_id']) . "',
		'" . mysql_escape_string($fields['email']) . "',
		'" . mysql_escape_string($fields['first'] . ' ' . $fields['last']) . "',
		'" . mysql_escape_string($fields['last']) . "',
		'" . mysql_escape_string($fields['first']) . "',
		'" . mysql_escape_string($secret_code) . "',
		'" . mysql_escape_string($fields['email_domain']) . "',
		NOW(),
		CURTIME(),
		'" . mysql_escape_string($fields['ip']) . "'
	)";

	ole_debug_sql($query);

	// insert record into table
	$rs = $sql->Query(
		OLE_DB_NAME,
		$query,
		Debug_1::Trace_Code(__FILE__, __LINE__)
	);

	//FIXME: we might want to move the error handling out into the caller function, because
	// different functions might want to handle different failures differently, not sure...
	$errno = $sql->Get_Errno();
	switch ($errno)
	{
	case 0:
		// life is good
		break;
	case 1062:
		// tried to insert into a list the person is already in
		echo "{$fields["email"]} is already in {$fields["list_id"]}...\n";
		break;
	case 1146:
		// ER_NO_SUCH_TABLE: tried to insert into a non-existant list
		echo "non-existant list ({$fields["list_id"]})...\n";
		break;
	default:
		echo "unexpected mysql error #$errno!\n";
		Error_2::Error_Test($rs, TRUE);
		break;
	}
				
}

/*
	add the list entry into the personindex table

	NOTE: it is necessary to seperate this from the ole_add_person_to_list because
	when we create a new personindex entry we already do this, so we can save an
	update call, it makes a difference when you're inserting 1M records
*/
function ole_list_add_to_person(&$sql, $personindex_id, $list_id)
{

	assert(is_object($sql) && is_a($sql, "MySQL_3"));
	assert(is_numeric($personindex_id));
	assert(is_numeric($list_id));

	$query = "
	UPDATE
		personindex
	SET
		lists = CONCAT(lists, \",$list_id\")
	WHERE
		ID = '" . mysql_escape_string($personindex_id) . "'";

	ole_debug_sql($query);

	$rs = $sql->Query(OLE_DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
	Error_2::Error_Test($rs, TRUE);

	$sql->Free_Result($rs);
}

/*
	get the site id, as ole knows it
	creates id if does not exist
*/
function ole_site_id(&$sql, $url, &$site_cache)
{
	assert(is_object($sql) && is_a($sql, "MySQL_3"));
	assert(is_string($url));
	assert(is_null($site_cache) || is_array($site_cache));

	// force url lowercase
	$url = strtolower($url);
	$CACHE_PASSED = is_array($site_cache);

	if ($CACHE_PASSED)
	{
		if (isset($site_cache[$url]))
		{
			return $site_cache[$url];
		}
	}

	$query = "
	SELECT
		ID
	FROM
		sites
	WHERE
		name = '" . mysql_escape_string($url) . "'";
	
	$rs = $sql->Query(OLE_DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__));

	$rec = $sql->Fetch_Array_Row($rs);

	// matching record
	if (is_array($rec))
	{
		$site_id = $rec['ID'];
		// save result to site cache, if it exists
		if ($CACHE_PASSED)
		{
			$site_cache[$url] = $site_id;
		}
		return $site_id;
	}

	// url does not exist in the db
	return ole_site_add($sql, $url, NULL, $site_cache);

}

/*
	load existing sites into a hash for easy lookups w/o hitting the db
	there aren't that many sites, possibly a few hundred at most
*/
function ole_site_load_all(&$sql)//, $sites=array())
{

	assert(is_object($sql) && is_a($sql, "MySQL_3"));

	$query = "
	SELECT
		DISTINCT name,
		ID
	FROM
		sites";

	ole_debug_sql($query);

	$rs = $sql->Query(OLE_DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
	Error_2::Error_Test($rs, TRUE);

	$sites = array();

	while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
	{
		//NOTE: the sites in the database are not lowercased or uppercased, so we
		// do it for your sanity... they really should be in the database
		$sites[strtolower($rec['name'])] = $rec['ID'];
	}

	$sql->Free_Result($rs);

	return $sites;
}

function ole_site_add(&$sql, $url, $type, &$site_cache)
{

	echo "ole_add_site($sql, $url, $type, $sites)\n";

	assert(is_object($sql) && is_a($sql, "MySQL_3"));
	assert(is_string($url));
	assert($type === NULL || is_numeric($type));
	assert(is_null($site_cache) || is_array($site_cache));

	$query = "
	INSERT IGNORE INTO sites (
		ID,
		name,
		type
	) VALUES (
		NULL,
		'" . mysql_escape_string($url) . "',
		" . ($type === NULL ? "NULL" : "'". mysql_escape_string($type) . "'") . "
	)";
	// NOTE: the value NULL does not interpolate to "NULL", so we have to do it ourselves

	ole_debug_sql($query);

	$rs = $sql->Query(OLE_DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
	Error_2::Error_Test($rs, TRUE);

	$sql->Free_Result($rs);

	$insert_id = $sql->Insert_Id();

	if (is_numeric($insert_id) && is_array($site_cache))
	{
		$site_cache[$url] = $sql->Insert_Id();
	}

	return $insert_id;

}

?>

