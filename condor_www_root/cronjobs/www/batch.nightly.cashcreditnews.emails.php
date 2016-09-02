<?
// Mantis 7988 - [AuMa]
/**
 * *Retrieves lead information for www.cashcreditnews.com
 * Uncomment $server  and $recipients when ready to go live
 */
 
define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
require_once(BFW_CODE_DIR.'server.php');
require_once('mysql.4.php');
require_once('mysqli.1.php');
include_once('prpc/client.php');
define('DEBUG',FALSE);


$mode = strtoupper($argv[1]);
if(!in_array($mode,array('LIVE','RC','LOCAL','REPORT')))
{
	echo("Invalid mode $mode\n");
}

//$server = Server::Get_Server('LOCAL','BLACKBOX');
$server = Server::Get_Server($mode,'BLACKBOX');
// WHEN READY TO GO LIVE;
//$server = Server::Get_Server('REPORT','BLACKBOX');


$today = mktime(0,0,0,date("m"),date("d")  ,date("Y"));
$date1 = date("Y-m-d", $today );

$myObj = new CcnAppIdSearch( $server );
//$myObj->get_data('2007-11-07');

// This is assumed that it is run once a day after midnight 
// (no check for duplicate dates)
$myObj->get_data($date1);

// run without the date to get everything in the database
//$myObj->get_data();


class CcnAppIdSearch
{
	private $sql;
	private $server;
	private $debug;
	
	function __construct($server)
	{
		if(defined('DEBUG'))
		{
			$this->debug = DEBUG;
		}
		else 
		{
			$this->debug = false;
		}
		if(isset($server['port']) && strpos($server['host'],':') === false) $server['host'] .= ':'.$server['port'];	
		$this->sql =  new MySQL_4($server['host'], $server['user'], $server['password'],FALSE);
		$this->sql->Connect();
		$this->server = $server;
	}

	// This array will contain the applications 
	// that we are still searching for
	// application_id | session_table
	private $application_ids_searching_session = array();

	// this array will contain all the application_ids
	// that we collected from the database table
	// and we will use this array to remove the 
	// application_ids from the table when we are through
	// application_id | first_name | last_name | email
	private $application_ids = array();

	
	
	private function display($str)
	{
		if($this->debug === true)
		{
			print $str;
		}
	}
	/*
	 * I am the function that runs everything that it needs to in order
	 * to get the data for the cron job. (I am the brain!)
	 * I return an array containing: first_name, last_name, email
	 */
	public function get_data($date = NULL)
	{
		$this->application_ids = $this->get_application_ids($date);
		if(count($this->application_ids) > 0)
		{
			//WE have apps to update. Lets loop through
			//what we have and try and load any data we're
			//missing from the session
			foreach($this->application_ids as $key => $app)
			{
				if($app['email'] === NULL || empty($app['name_first']) || empty($app['name_last']))
				{
					$info = $this->getInfoFromSession($app['application_id']);
					if(is_array($info))
					{
						$this->application_ids[$key] = array_merge($this->application_ids[$key],$info);
					}
				} 
			}
			
			// now we create and send the email
			$this->send_data($date);
			
			// Clean up the database
			//$this->clean_up_database($date);
		} 
		else 
		{
			$this->display("\r\n" . "There were no application ids to send out" . "\n");
		}
	}
	
	/**
	 * Takes an app id and loads the session 
	 * to look for any information we're missing
	 *
	 * @param int $app_id
	 * @return array
	 */
	private function getInfoFromSession($app_id)
	{
		if(is_numeric($app_id))
		{
			$session_id = $this->getSessionIdByAppId($app_id);
			$session_data = $this->getSessionData($session_id);
			$app_data = array(
				'application_id' => $session_data['application_id'],
				'first_name' => $session_data['data']['name_first'],
				'last_name' => $session_data['data']['name_last'],
				'email' => $session_data['data']['email_primary']
			);
			return $app_data;
		}
		else 
		{
			throw new Exception("Tried to load session info for invalid app_id {$app_id}.");
		}
	}
	
	/**
	 * Loads a session by session_id and returns
	 * an array
	 *
	 * @param string $session_id
	 * @return array
	 */
	private function getSessionData($session_id)
	{
		if(is_string($session_id))
		{
			$tbl = $this->getSessionTable($session_id);
			$s_id = mysql_real_escape_string($session_id);
			$query = "SELECT
				session_info,
				compression
			FROM
				$tbl
			WHERE
				session_id='$s_id'
			LIMIT 1
			";
			$res = $this->sql->query($this->server['db'],$query);
			if($row = $this->sql->Fetch_Object_Row($res))
			{
				$data = false;
				switch(strtolower($row->compression))
				{
					case 'gz':
						$data = gzuncompress($row->session_info);
						break;
					case 'bz':
						$data = bzuncompress($row->session_info);
						break;
					default:
						$data = $row->session_info; 
				}
				$array = $this->SessionRealDecode($data);
				return $array;
			}
			return false;
		}
		else
		{
			throw new Exception("Invalid session id $session_id");
		}
	}

	/**
	 * Finds a session_id based on
	 * the application id
	 *
	 * @param int $app_id
	 * @return string
	 */
	private function getSessionIdByAppId($app_id)
	{
		$query = 'SELECT 
			session_id
		FROM
			application
		WHERE
			application_id=\''.$app_id.'\'
		LIMIT 1	
		';
		$res = $this->sql->Query($this->server['db'],$query);
		if($row = $this->sql->Fetch_Object_Row($res))
		{
			return $row->session_id;
		}
		else
		{
			throw new Exception("Could not find session id for {$app_id}\n");
		}
	}

	/**
	 * Takes a session id and returns the session_table
	 * it should be in
	 *
	 * @param int $sid
	 * @return string
	 */
	private function getSessionTable($sid)
	{
		return 'session_'.substr($sid, 0, 1);
	}

	/*
	 * I am responsible for getting the application ids
	 * that are stored in the ccn_daily table
	 * for a specified datetime + 24 hours
	 * if no date is specified then it will grab all of 
	 * the data in the table
	 */
	private function get_application_ids($date)
	{
		// Prepare Query
		$query = "SELECT
			a.application_id,
			first_name,
			last_name,
			email,
			session_id
		FROM
			ccn_daily c
		JOIN 
			application a
		ON
			 a.application_id = c.application_id
		LEFT JOIN
			personal_encrypted p
		ON
			a.application_id = p.application_id";
		if($date != NULL)
		{
			$query .= " WHERE 
				c.date_created BETWEEN date_sub('$date', INTERVAL 1 DAY) and '$date'";
		}
		// Gather Data
		$results = $this->sql->Query($this->server['db'],$query);
		
		$result_array = array();
		
		// Loop over the results so 
		while($row = $this->sql->Fetch_Array_Row($results))
		{
			$result_array[$row['application_id']] = $row;
		} // end while

		return $result_array;
	}

	
	
	/*
	 * I send the data to the who we need to send it to
	 */
	private function send_data($date)
	{
		// Prepare Email
		$tx = new OlpTxMailClient(false,$GLOBALS['mode']);
		
		$recipients = array();
		
		if($this->debug)
		{
			$recipients[] = 
				array
				(
					"email_primary_name" => "August Malson", 
					"email_primary" => "august.malson@sellingsource.com"
				);
		} 
		else 
		{
			$recipients[] = 
				array(
					"email_primary_name" => "ccnadmin", 
					"email_primary" => "ccnadmin@partnerweekly.com"
				);
            
			$recipients[] = 
				array
				(
					"email_primary_name" => "August Malson", 
					"email_primary" => "august.malson@sellingsource.com"
				);
		}
		
		// Manage Data
		$csv = "email,Name\n";
		
		// Loop over the results  
		$rowcount = 0;
		foreach($this->application_ids as $app)
		{	
			if(isset($app['email']))
			{
				$csv .= "{$app['email']},{$app['first_name']} {$app['last_name']}\n";
				$rowcount++;
			}
			
		} // end while
		
		if($rowcount == 1)
		{
			$subject = "{$rowcount} - www.cashcreditnews.com lead";
		} else 
		{
			$subject = "{$rowcount} - www.cashcreditnews.com leads";	
		}

		if($date != NULL)
		{
      	$subject .= "  for {$date}"; 
		}
			
		$header = array
		(
		"sender_name"           => "Selling Source <no-reply@sellingsource.com>",
		"subject" 	        	=> $subject,
		"site_name" 	        => "sellingsource.com",
		"message"				=> $subject
		);
		
		
		$attach = array(
			'method' => 'ATTACH',
			'filename' => 'cashcreditnow.txt',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_size' => strlen($csv),
		);
		
		if($rowcount != 0 )
		{
			foreach($recipients as $r){
				$data = array_merge($r,$header);
				
				try
				{
					$result = $tx->sendMessage('live', 'PDDLEADS_CRON', $data['email_primary'], '', $data, array($attach));
				}
				catch(Exception $e)
				{
					$result = FALSE;
				}
				
				if($result)
				{
					$this->display("\r\nEMAIL HAS BEEN SENT TO: ".$r['email_primary'].".\n");
				}
				else
				{
					$this->display("\r\nERROR SENDING EMAIL TO: ".$r['email_primary'].".\n");
				}
			
			}
		} 
		else 
		{
			$this->display("\r\nNo email sent -- no data to send" . ".\n");
		}
	}
	
	/*
	 * I clean up the database from the entries
	 */ 
	private function clean_up_database($date)
	{
		
		// Prepare Query
		$query = "	
		delete
		from
		ccn_daily ";
		if($date != NULL)
		{
			$query .= "
			where date_created between
			date_sub('$date', INTERVAL 1 DAY) and '$date'
			";
		}
		// Gather Data
		$results = $this->sql->Query($this->server['db'],$query);
	}
	
	/**
	 * Unserialize session data. This function is copied from old SOAP data tool.
	 *
	 * @param string $str Serialized session data.
	 * @return string Unserialized session data.
	 */
	private function SessionRealDecode($str)
	{
		define('PS_DELIMITER', '|');
		define('PS_UNDEF_MARKER', '!');
	
		$str = (string)$str;
	
		$endptr = strlen($str);
		$p = 0;
	
		$serialized = '';
		$items = 0;
		$level = 0;
	
		while ($p < $endptr) {
			$q = $p;
			while ($str[$q] != PS_DELIMITER)
				if (++$q >= $endptr) break 2;
	
			if ($str[$p] == PS_UNDEF_MARKER) {
				$p++;
				$has_value = FALSE;
			} else {
				$has_value = TRUE;
			}
	
			$name = substr($str, $p, $q - $p);
			$q++;
	
			$serialized .= 's:' . strlen($name) . ':"' . $name . '";';
	
			if ($has_value) {
				for (;;) {
					$p = $q;
					switch ($str[$q]) {
						case 'N': /* NULL */
						case 'b': /* boolean */
						case 'i': /* integer */
						case 'd': /* decimal */
							do $q++;
							while ( ($q < $endptr) && ($str[$q] != ';') );
							$q++;
							$serialized .= substr($str, $p, $q - $p);
							if ($level == 0) break 2;
							break;
						case 'r': /* reference  */
						case 'R': /* reference  */
							$q+= 2;
							for ($id = ''; ($q < $endptr) && ($str[$q] != ';'); $q++) $id .= $str[$q];
							$q++;
							$serialized .= 'R:' . ($id + 1) . ';'; /* increment pointer because of outer array */
							if ($level == 0) break 2;
							break;
						case 's': /* string */
							$q+=2;
							for ($length=''; ($q < $endptr) && ($str[$q] != ':'); $q++) $length .= $str[$q];
							$q+=2;
							$q+= (int)$length + 2;
							$serialized .= substr($str, $p, $q - $p);
							if ($level == 0) break 2;
							break;
						case 'a': /* array */
						case 'O': /* object */
							do $q++;
							while ( ($q < $endptr) && ($str[$q] != '{') );
							$q++;
							$level++;
							$serialized .= substr($str, $p, $q - $p);
							break;
						case '}': /* end of array|object */
							$q++;
							$serialized .= substr($str, $p, $q - $p);
							if (--$level == 0) break 2;
							break;
						default:
							return FALSE;
					}
				}
			} else {
				$serialized .= 'N;';
				$q+= 2;
			}
			$items++;
			$p = $q;
		}
	
		return @unserialize( 'a:' . $items . ':{' . $serialized . '}' );
	}
	
}
?>
