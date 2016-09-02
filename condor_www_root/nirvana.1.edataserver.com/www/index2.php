<?php
/**
 * Gathers information from the client for Nirvana.
 *
 * @author Brian Feaver
 */

// Set the mode
switch( TRUE )
{
    case preg_match('/^rc\./', $_SERVER['SERVER_NAME']):
		define('MODE', 'rc');
		define('LDB_DB', 'ldb');
		define('OLP_DB', 'rc_olp');
		define("SMS_PRPC", 'prpc://rc.bfw.1.edataserver.com/smscom.php');
        break;
    case preg_match('/ds\d{1,3}\.tss/', $_SERVER['SERVER_NAME']):
        define('MODE', 'local');
		define('LDB_DB', 'ldb');
		define('OLP_DB', 'olp');
		define("SMS_PRPC", 'prpc://bfw.1.edataserver.com.ds56.tss:8080/smscom.php');
        break;
    default:
        define('MODE', 'live');
		define('LDB_DB', 'ldb');
		define('OLP_DB', 'olp');
		define("SMS_PRPC", 'prpc://bfw.1.edataserver.com/smscom.php');
        break;
}

define('LIB5_DIR', '/virtualhosts/lib5/');
define('LIB_DIR', '/virtualhosts/lib/');

require_once(LIB5_DIR . 'prpc/server.php');
require_once(LIB5_DIR . 'prpc/client.php');
require_once(LIB_DIR . 'mysql.4.php');
require_once(LIB_DIR . 'cashbuzz.1.php');

class Nirvana_PRPC extends Prpc_Server
{
	
	function __construct()
	{
		// Initialize consumer_data
		$this->consumer_data['DUE_DATE']			= null;		
		$this->consumer_data['PAYMENT_AMT']			= null;		
		$this->consumer_data['NAME_FIRST']			= null;
		$this->consumer_data['NAME_LAST']			= null;
		$this->consumer_data['NAME_MIDDLE']			= null;
		$this->consumer_data['EMAIL_PRIMARY']		= null;		
		$this->consumer_data['REFERRING_URL']		= null;		
		$this->consumer_data['COMPANY_NAME']		= null;		
		$this->consumer_data['CUSTOMER_SVC_NUMBER']	= null;		
		$this->consumer_data['CASHBUZZ_ID']			= null;
		$this->consumer_data['SESSION_ID'] 			= NULL;
		$this->consumer_data['SENDER'] 				= NULL;
		$this->consumer_data['COREG_SITE']			= NULL;

		$this->companies = array(	"pcl" => 	array(	"company_name" => "One Click Cash", 
													"phone"	=> "1-800-230-3266",
													"fax"	=> "1-800-803-9136",
													"email" => "customerservice@oneclickcash.com"),
													
									"ucl" => 	array(	"company_name" => "United Cash Loans", 
													"phone"	=> "1-800-279-8511",
													"fax"	=> "1-803-803-8794",
													"email" => "customerservice@unitedcashloans.com"),
													
									"ca" => 	array(	"company_name" => "AmeriLoan", 
													"phone"	=> "1-800-362-9090",
													"fax"	=> "1-800-256-9166",
													"email" => "customerservice@ameriloan.com"),
													
									"d1" => 	array(	"company_name" => "500 Fast Cash", 
													"phone"	=> "1-888-919-6669",
													"fax"	=> "1-800-361-5119",
													"email" => "customerservice@500fastcash.com"),
													
									"ufc" => 	array(	"company_name" => "US Fast Cash",
												 	"phone"	=> "1-800-640-1295",
												 	"fax"	=> "1-800-549-3324",
													"email" => "customerservice@usfastcash.com"));	
		// Run parent's constructor
		parent::__construct();
	}
	
	/**
	 * Attempts to pull consumer data from the ldb database, olp database, and finally the session
	 * and then send it to Nirvana thru PRPC.
	 *
	 * @param string $property The property, we ignore it.
	 * @param string $track_id The track id for the application
	 * @param string $date The current date
	 * @param boolean $cashbuzz Run cashbuzz if true, assuming it's always true
	 * @param boolean $debug Run in debug mode
	 *
	 * @return array The consumer data
	 */
	public function Get_Consumer_Data($property, $track_id, $date, $cashbuzz = true, $debug = true)
	{
		$formatted_date = date('Ymd', strtotime($date));
		
		// Attempt to get information from ldb database
		if(!$this->Get_LDB_Info($track_id, $formatted_date))
		{
			// ldb didn't have the info, attempt to get information from olp database
			$this->Get_OLP_Info($track_id, $formatted_date);
		}
		
		// Get the information from the session
		if(!$this->consumer_data['REFERRING_URL'])
		{
			$this->Get_Session_Info($track_id, $formatted_date);
		}
		
		if(!is_null($this->consumer_data['EMAIL_PRIMARY']) && $cashbuzz)
		{
			// Get cashbuzz id
			$cashbuzz_obj = new Cashbuzz_1();
			$this->consumer_data['CASHBUZZ_ID'] = $cashbuzz_obj->Get_Cashbuzz_Id($this->consumer_data['EMAIL_PRIMARY']);
		}
		
		return serialize($this->consumer_data);
	}
	
	/**
	 * Attempts to retrieve the information from the ldb database.
	 *
	 * @param string $track_id The track_id of the application
	 * @param string $date The date of the application(?)
	 *
	 * @return boolean True if ldb had the info, false otherwise
	 */
	private function Get_LDB_Info($track_id, $date)
	{
		$ret_val = false;
		
		$this->Setup_MySQL(LDB_DB);
		
		// eCash uses Central Time, so for our numbers to match theirs, we also
		// have to use Central Time.
		// REMOVED for now, may or may not need to be added back in
//		$this->mysql->Query($server['db'], "SET time_zone = '-6:00'");
		
		$query = "
			SELECT
				app.email AS EMAIL_PRIMARY,
				app.name_first AS NAME_FIRST,
				app.name_last AS NAME_LAST,
				app.name_middle AS NAME_MIDDLE,
				company.name_short	as COMPANY_SHORT
			FROM
				application as app
				JOIN company ON company.company_id = app.company_id
			WHERE
				app.track_id = '$track_id' ";
		
//		print "<pre>$query"; die();
		
		$result = $this->mysql->Query(LDB_DB, $query);
		
		if($row = $this->mysql->Fetch_Array_Row($result))
		{
			$this->consumer_data['EMAIL_PRIMARY']	= $row['EMAIL_PRIMARY'];
			$this->consumer_data['NAME_FIRST']		= $row['NAME_FIRST'];
			$this->consumer_data['NAME_LAST']		= $row['NAME_LAST'];
			$this->consumer_data['NAME_MIDDLE']		= $row['NAME_MIDDLE'];

			// Pass back Enterprise Data
			if($this->companies[strtolower($row['COMPANY_SHORT'])])
			{
				$prpc_result = new Prpc_Client(SMS_PRPC, FALSE, 32);
				$comp = $this->companies[strtolower($row['COMPANY_SHORT'])];
				$this->consumer_data['COMPANY_NAME']			= $comp["company_name"];
				$this->consumer_data['CUSTOMER_SVC_NUMBER']		= $comp["phone"];
				$this->consumer_data['REFERRING_URL']			= $prpc_result->SMS_ReactURL($track_id);
				$tmper											= split("\?",$this->consumer_data['REFERRING_URL']);
				$this->consumer_data['SENDER']					= $tmper[0];
				//$this->consumer_data['COMPANY_EMAIL']		= $comp["email"];				
			}
				
			$ret_val = true;
		}
		
		return $ret_val;
	}
	
	/**
	 * Attempts to retrieve the consumer info from the OLP database.
	 *
	 * @param string $track_id The track_id for the application
	 * @param string $date The date of the application
	 *
	 * @return boolean True if information was retrieved, false otherwise.
	 */
	private function Get_OLP_Info($track_id, $date)
	{
		$ret_val = false;
		
		$this->Setup_MySQL(OLP_DB);
		
		$query = "
			SELECT
				p.email AS EMAIL_PRIMARY,
				p.first_name AS NAME_FIRST,
				p.last_name AS NAME_LAST,
				p.middle_name AS NAME_MIDDLE,
				DATE_FORMAT(a.created_date,'%M %e, %Y') as APPLICATION_DATE,
				a.application_id as APPLICATION_ID
			FROM
				application a
				JOIN personal p ON a.application_id = p.application_id
			WHERE
				a.track_id = '$track_id'";
		
		$result = $this->mysql->Query(OLP_DB, $query);
		
		if($row = $this->mysql->Fetch_Array_Row($result))
		{				
			$this->consumer_data['EMAIL_PRIMARY']		= $row['EMAIL_PRIMARY'];
			$this->consumer_data['NAME_FIRST']			= $row['NAME_FIRST'];
			$this->consumer_data['NAME_LAST']			= $row['NAME_LAST'];
			$this->consumer_data['NAME_MIDDLE']			= $row['NAME_MIDDLE'];
			$this->consumer_data['APPLICATION_DATE']	= $row['APPLICATION_DATE'];
			$this->consumer_data['APPLICATION_ID']		= $row['APPLICATION_ID'];
			$ret_val = true;
		}
		
		return $ret_val;
	}
	
	/**
	 * Attempts to retrieve consumer information from the session.
	 *
	 * @param string $track_id The track_id for the application
	 * @param string $date The date of the application
	 */
	private function Get_Session_Info($track_id, $date)
	{		
		$this->Setup_MySQL(OLP_DB);
		
		require_once(LIB_DIR.'session.6.php');
		
		$query = "
            SELECT
                session_id
            FROM
                application
            WHERE
                track_id = '$track_id' ";
		
		$result = $this->mysql->Query(OLP_DB, $query);
		
		$session_id = $this->mysql->Fetch_Column($result, 'session_id');
		
		if(!empty($session_id))
		{
			$table = 'session_'.substr($session_id, 0, 1);
			$session = new Session_6($this->mysql, OLP_DB, $table, $session_id);
			
			$result = $session->Record_Fetch($session_id);
			
		    if($result['session_info'])
		    {
		    	$data = gzuncompress($result['session_info']);
		    	session_decode($data);
		    }
		    
	        $enterprise_sites = array(
	        	'ameriloan.com',
	        	'www.ameriloan.com',
	        	'http://ameriloan.com',
	        	'http://www.ameriloan.com',
	        	'oneclickcash.com',
	        	'www.oneclickcash.com',
	        	'http://oneclickcash.com',
	        	'http://www.oneclickcash.com',
	        	'500fastcash.com',
	        	'www.500fastcash.com',
	        	'http://500fastcash.com',
	        	'http://www.500fastcash.com',
	        	'unitedcashloans.com',
	        	'www.unitedcashloans.com',
	        	'http://unitedcashloans.com',
	        	'http://www.unitedcashloans.com',
	        	'usfastcash.com',
	        	'www.usfastcash.com',
	        	'http://usfastcash.com',
	        	'http://www.usfastcash.com',
	        );
	        
	        if(!in_array( $_SESSION['data']['client_url_root'], $enterprise_sites ))
	        {
	        	if(is_null($this->consumer_data['NAME_FIRST']))
	        	{
	        		$this->consumer_data['NAME_FIRST'] = $_SESSION['data']['name_first'];
	        	}
	        	
	        	if(is_null($this->consumer_data['NAME_LAST']))
	        	{
	        		$this->consumer_data['NAME_LAST'] = $_SESSION['data']['name_last'];
	        	}
	        	
	        	if(is_null($this->consumer_data['NAME_MIDDLE']))
	        	{
	        		$this->consumer_data['NAME_MIDDLE'] = $_SESSION['data']['name_middle'];
	        	}
	        	
	        	if(is_null($this->consumer_data['EMAIL_PRIMARY']))
	        	{
	        		$this->consumer_data['EMAIL_PRIMARY'] = $_SESSION['data']['email_primary'];
	        	}
	        	

	        	// Added session ID and sender
	        	// Sender doesn't really do anything at this point, but it's avaialable if needed
	        	
	        	// Too many items will breack nirvana [RL] 02/13/06
	        	$this->consumer_data['SESSION_ID'] = $session_id;
	        	$this->consumer_data['SENDER'] = NULL;
	        	$this->consumer_data['COREG_SITE'] = $_SESSION['data']['coreg_site_url'];
	        	
	        	// Believe this is the only place you can get this info
	        	if($_SESSION["process_rework"] == 1) 
	        	{
	        		$return_url = $_SESSION['data']['client_url_root']."?page=return_visitor&unique_id=".$session_id;
	        		$this->consumer_data['REFERRING_URL'] = $return_url;
	        		$this->consumer_data['SENDER'] = $_SESSION['config']->site_name;
	        		// Some Sites have a .com go figuire [RL]
	        		$this->consumer_data['COMPANY_NAME'] =  str_replace(".com","",$_SESSION['config']->name_view);
	        	}
	        	else
	        	{
	        		$this->consumer_data['REFERRING_URL'] = $_SESSION['data']['client_url_root'];
	        		
	        		// Only overwrite this information if it's already NULL
	        		// Yes, it's a duplicate of above, but I'm not sure if that overwrites for rework
	        		if(is_null($this->consumer_data['SENDER']))
		        	{
		        		$this->consumer_data['SENDER'] = $_SESSION['config']->site_name;
		        	}
		        	
		        	if(is_null($this->consumer_data['COMPANY_NAME']))
		        	{
		        		$this->consumer_data['COMPANY_NAME'] =  str_replace(".com","",$_SESSION['config']->name_view);
		        	}
	        	}
	        }
		}
	}
	
	/**
	 * Sets up a MySQL connection based on the database needed.
	 *
	 * @param string $database The database that needs to be connected to
	 */
	private function Setup_MySQL($database)
	{
		// Close the connection if called again or the database is different
		if($this->server['db'] != $database && isset($this->mysql))
		{
			$this->mysql->Close_Connection();
			unset($this->mysql);
		}
		elseif(isset($this->mysql))
		{
			// Don't reconnect if using the same db
			return;
		}
		
		switch($database)
		{
			case LDB_DB:
				switch(MODE)
				{					
					case "local":
						$this->server = array( 
							"host" => "db101.clkonline.com:3308",
							"user" => "ldb_writer",
							"db" => "ldb",
							"password" => "password");
					break;
			
					case "rc":
						$this->server = array( 
							"host" => "db101:3308",
							"user" => "ldb_writer",
							"db" => "ldb",
							"password" => "password");
					break;
					
					case "live":
						$this->server = array( 
							"host" => "db3",
							"user" => "olp",
							"db" => "ldb",
							"password" => "password");
					break;			
				}
				break;
			case OLP_DB:
				switch(MODE)
				{
					case "local":// Set MySQL conn vars
						$this->server = array("db"		=> "olp",
										"host"		=> "monster.tss:3310",
										"user"		=> "olp",
										"password"	=> "password");
					break;
					
					case "rc":
						$this->server = array("db"		=> "rc_olp",
										"host"		=> "db101.clkonline.com",
										"user"		=> "sellingsource",
										"password"	=> "password");
					break;
					
					case "live":
						$this->server = array("db"		=> "olp",
										"host"		=> "olpdb.internal.clkonline.com",
										"user"		=> "sellingsource",
										"password"	=> "password");
					break;
				}
				break;
		}
		
		try
		{
			$this->mysql = new MySQL_4($this->server['host'], $this->server['user'], $this->server['password']);
			$this->mysql->Connect();
		}
		catch(Exception $e)
		{
			print $e->getMessage();
			die();
		}
	}
	
	private $mysql;			// The MySQL connection
	private $server;		// MySQL server information
	private $consumer_data; // The data to pass to Nirvana
	private $companies;		// Contact Details for Enterprise Companies

	
}

//$nirvana_prpc = new Nirvana_PRPC();
//$nirvana_prpc->Get_Consumer_Data(null, 'blFzehaAb8Cgf1YOc70oppwHHH0', '20051130', true, true);

$nirvana_prpc = new Nirvana_PRPC();
$nirvana_prpc->_Prpc_Strict = TRUE;
$nirvana_prpc->Prpc_Process();
?>
