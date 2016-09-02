<?php
/**
 * datran_send - This script, a get-url sending engine initially designed for datran and 
 * extended for any company, pulls contacts from the datran table, parses them against 
 * rules defined on a per company level, then sends the data in a get request to the url
 * defined in the company table.
 *  
 * Contacts are pulled from the table based on their date_create (sic).  The delta used
 * is stored in the company table.  A DB Designer xml diagram (www.fabforce.net) is 
 * a part of this project.
 * 
 * Send logs are stored in the datran_company_xref table.  
 * Company info in the company table
 * Business rules, defined by strings, in the rules table.
 * The company_rules_xref table links rules to companies.
 * 
 * To add a new company:  
 * Insert the name, reporting delta (172800 = 2 days), and reporting url in company.  Then
 * add a serialized array to report_fields of dbfield => get name
	/*
	# example report_fields
	$array = array(
		'34632' => 'ac',
	    'segmentcode' => 'vs',
	    'ip_address' => 'ip',
	    'date_format(date_create, "%m/%d/%Y")' => 'ad',
	    'email' => 'email',
	    'first'=> 'firstname',
	    'last' => 'lastname',
	    'address' => 'address1',
	    'city' => 'city',
	    'state' => 'state',
	    'zip' => 'zipcode',
	    'phone' => 'phone',
	    'id' => 'external_id'
	);
 *
 * @author - Tom Anderson
 * @copyright Copyright &copy; 2005, TSS
 * @version 1.2 3/25/2005
 */

error_reporting(E_ALL - E_NOTICE); 
require_once ("/virtualhosts/lib/mysql.3.php");
require_once ("/virtualhosts/lib/error.2.php");

# require_once 'Net/Curl.php';

define('DB_USER', 'sellingsource');
define('DB_PASS', 'password');
define('DB_SERVER', 'selsds001');
define('DB_DATABASE', 'oledirect2');

$global_domains = array (
	"111cash.com", "internetpayday.com", "500dollaradvance.com", "aaapayday.com", "americacashadvance.com", 
	"americapaydayadvance.com", "americapaydayloan.com", "cashadvance2000.com",
	"cashadvance500.com", "cash-advance-city.com", "cashadvancecity.com", "cashadvanceman.com", "cashadvancenow.com",
	"cashadvanceusa.com", "cashloans4u.com", "citizens-cash-advance.com", "citizenscashadvance.com",
	"dollaradvance.com", "equityloans4u.com", "highdeltahunts.com", "lightening-cash.com", "lighteningcash.com",
	"moneyloans4u.com", "mypaydaytoday.com", "national-payday-loan.com", "nationalpaydayloan.com",
	"nationscashadvance.com", "paydaycash2000.com", "payday-cash-now.com", "paydaycashnow.com",
	"paydaycity.com", "paydayfromhome.com", "paydayloanman.com", "paydayloans4u.com", "payday-loan-usa.com",
	"paydayloanusa.com", "peoplespayday.com", "prestacash.com", "thepaydayloanplace.com",
	"unitedchecks.com", "sellingsource.com", "123onlinecash.com", "500fastcash.com", "cashbackvalues.com",
	"casinoratingclub.com", "driveawayloans.com", "epointmarketing.com", "equity1auto.com",
	"equityoneauto.com", "essenceofjewels.com", "expressgoldcard.com", "extremetrafficteam.com",
	"fast-funds-online.com", "fastcashsupport.com", "fcpcard.com", "financialhosting.com",
	"greatweboffers.com", "kingtutspub.com", "leadershipservices.com", "management.soapdataserver.com",
	"mbcash.com", "my-payday-loan.com", "mycash-online.com", "oledirect.com", "oledirect2.com",
	"oneclickcash.com", "partnerweekly.com", "preferredcashloans.com", "safetyprepared.com",
	"smartshopperonline.com", "ssbusadmin.com", "steaksofstlouis.com", "telewebcallcenter.com", "telewebmarketing.com",
	"unitedcashloans.com", "usfastcash.com", "yourcashnetwork.com", "yourfastcash.com", "flyfone.com", 
	"swiftglobaltelecom.com", "swiftphone.com", "123cheapcigarette.com", "webfastcash.com", "fastcashpreferred.com", 
	"louisianapaydayloans.com", "tssmasterd.com", "imagedataserver.com", "homelandproducts.net", "fastcashcard.com", 
	"500fastcash.com", "sirspeedycash.com", "123onlinecash.com", "thesellingsource.com", "xenlog.com", "xenlog1.com", 
	"pwccomm.com", "bonustravelcoupons.com", "gasreward.com", "i-cami.com", "peoplespayday.com", "eblasterpro.com"
);

$global_enterprise_domains = array ("http://ameriloan.com", "http://500fastcash.com", "http://preferredcashloans.com", "http://unitedcashloans.com", "http://unitedfastcash.com");
$global_denied_sites = array("credit.com");

/*Main ********************************************/
$db = new MySQL_3();
if (!$db->Connect("BOTH", DB_SERVER, DB_USER, DB_PASS)) {
	$fp = fopen("/tmp/datran.txt", "a");
	fputs($fp, "Could not connect to database\n");
	return false;
}

// The delta date, and reporting url are pulled from datran_company
// and this script iterates through all companies in that table
$db_hnd = $db->Query (DB_DATABASE, "SELECT * FROM company WHERE active = 1 ORDER BY company_key", Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($res, TRUE);


while ($company = $db->fetch_array_row($db_hnd)) { # begin main loop
	$company_key = $company['company_key'];
	$company_name = $company['name'];
	$report_url = $company['report_url'];
	$report_fields = unserialize($company['report_fields']);


	// Fetch special processing rules for this company
	$rules = array();
	$sql = "SELECT rule 
			FROM rules, company_rules_xref 
			WHERE ref_rules = rules_key 
				AND ref_company = $company_key
	";
//	$rule_res = $db->query(DB_DATABASE, $sql); 
	$rule_res = $db->Query (DB_DATABASE, $sql, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($res, TRUE);	

	while ($rule = $db->fetch_array_row($rule_res)) {
		// Rules are put into an assoc array to avoid in_array calls
		$rules[$rule['rule']] = true;
	}

	// The start date is defined by the most recent sent from the previous run
	$sql ="
		SELECT unix_timestamp(date_create) as date_create
		FROM datran_company_xref, datran
		WHERE ref_company = $company_key
			AND ref_datran = datran.id
		ORDER BY datran_company_xref.date_sent DESC LIMIT 1
	";
		
	$start_res = $db->Query (DB_DATABASE, $sql, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($res, TRUE);			
		
	$start_array = $db->fetch_array_row($start_res);
	$start_date = $start_array['date_create'] - $company['reporting_delta'];
	if (!$start_date) $start_date = time(); # for first run of new company
	$start_date = date('YmdHis', $start_date);
	
	// The begin date defines, based on datran.date_create[d], who to filter
	$end_date = date('YmdHis', (time() - $company['reporting_delta']));
	
	// Get all data for unsent users for this company
	$sql = '';
	foreach ((array)$report_fields as $alias => $get) {
		// Build get query using sql_format serialized array to alias fields
		$sql .= " $alias as $get, ";
	}
	$sql = 'SELECT ' . $sql;
	$sql .= "
				datran.id as _row_key 
		 	FROM datran left outer join
		 			datran_company_xref ON
		 					ref_datran = datran.id
		 				AND ref_company = $company_key
			left outer join datran_groups on datran.datran_group = datran_groups.gid
		 	WHERE 
		 		ref_datran is null
		 	AND date_create > $start_date
		 	AND date_create < $end_date
		 	ORDER BY date_create
		";
	
	echo $sql."\n";

//	$client_res = $db->query(DB_DATABASE, $sql);
	$client_res = $db->Query (DB_DATABASE, $sql, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($res, TRUE);		

	if (!$db->row_count($client_res)) {
		echo "Nothing to send to $company_name\n";
	} elseif (!$report_url) {
		echo "No reporting url defined for $company_name\n";
		unset($client_res);
	} else {
		$row_count = $db->row_count($client_res);
		$current_count = 1;
		echo "Begin processing for $company_name: " . $row_count . "\n";
	}

	while ($row = $db->fetch_array_row($client_res)) {
		$datran_key = $row['_row_key'];
		$result = '';
		if (CanSendData($row, $rules, $result)) {
			$result = SendData($row, $report_url, $rules);
			UpdateSentLog($company_key, $datran_key, 'sent', $result);
			echo "Company {$company_name} {$row_count}:{$current_count} Success: $datran_key\n";
		} else {
			echo "Company {$company_name} {$row_count}:{$current_count} Failed: {$datran_key}: {$result}\n";
			UpdateSentLog($company_key, $datran_key, 'denied', $result);
		}
		$current_count++;
	}
}

/*Functions ****************************************/
function SendData($data, $report_url, $rules) {
	
	// Build get string
	$get = '';
	foreach ($data as $key => $value) {
		if ($get)
			$get .= '&';
		$get .= $key.'='.urlencode(trim($value));
	}
/*
	// Send data to company and return response
	$curl = new Net_Curl($report_url.$get);
	$curl->return_transfer = 1;
	$result = $curl->execute();
	$curl->close();
	return $result;
*/
	$res = file($report_url.$get);
	if (is_array($res)) $res = implode('', $res);
	return $res;
}

// This function does sanity checking on the passed row data
// and runs any rules defined for the company
function CanSendData(&$data, $rules, &$denied) {
	global $db, $global_domains, $global_enterprise_domains, $global_denied_sites;
	
	// Retrieve row data as non-aliased for sanity check
	$sql = "SELECT email, state, segmentcode FROM datran WHERE id = $data[_row_key]";
//	$sanity_res = $db->query(DB_DATABASE, $sql);
	$sanity_res = $db->Query (DB_DATABASE, $sql, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($res, TRUE);		
	
	$sanity = $db->fetch_array_row($sanity_res);

	// Clean row id from data
	unset($data['_row_key']);
	
	if (in_array($sanity['segmentcode'], $global_denied_sites)) {
		$denied = "Domain in global denied sites list\n";	
		return false;		
	}

	$domain = substr($sanity['email'], strpos($sanity['email'], "@") + 1);

	// Don't send anything to addresses hosted in the domain list above
	if (in_array($domain, $global_domains)) {
		$denied = "Domain in global domains list\n";	
		return false;
	}

	// Nothing can be sent to CA residents
	if (strtoupper(trim($sanity['state'])) == 'CA') {
		$denied = "This person is a resident of CA\n";
		return false;
	}

	// Special rules, stored in the rules table, are inforced here
	if ($rules['nms_funded']) {
		$query = "SELECT count(*) as count FROM nms_funded WHERE email='".$sanity['email']."'";
		$result = $db->Query("scrubber", $query);
		if (Error_2 :: Error_Test($result)) {
			var_dump($result);
		}
		$row = $db->Fetch_Array_Row($result);
		if ($row['count'] > 0) {
			$denied = "This person already exists in nms_funded\n";
			return false;
		}
	}

	// If any data is null, set 'r' to 1	
	if ($rules['null_check']) {
		foreach ($data as $null_check) {
			if (!$null_check) {
				$data['r'] - 1;
				break;
			}			
			$data['r'] = (in_array($data['d'],$global_enterprise_domains))? 3 : 2;
		}
	}
		
	return true;
}

// This function updates or inserts a row for the current company
function UpdateSentLog($company_key, $row_key, $status, $result = '') {
	global $db;
	// Check for existing record 
	$sql = "select ref_datran from datran_company_xref where ref_company = $company_key AND ref_datran = $row_key";
	//$res = $db->query(DB_DATABASE, $sql);
	$res = $db->Query (DB_DATABASE, $sql, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($res, TRUE);
	
	$result = addslashes(substr($result, 0, 200));
	if (!$db->row_count($res)) {
		// No row exists, add new
		$sql = "INSERT INTO datran_company_xref 
					(ref_company, ref_datran, date_sent, $status, result)
				VALUES
					($company_key, $row_key, now(), 1, '$result')
		";
	} else {
		// Row exists, update
		$sql = "UPDATE datran_company_xref 
				SET ref_company = $company_key,
					ref_datran = $row_key,
					date_sent = now(),
					$status = $status + 1
				WHERE ref_company = $company_key
					AND ref_datran = $row_key
		";
	}
	//$db->query(DB_DATABASE, $sql);
	$res = $db->Query (DB_DATABASE, $sql, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($res, TRUE);
	return true;
}
?>
