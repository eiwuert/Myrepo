<?php
require_once('alertees.php');
require_once('reported_exception.1.php');
require_once('condor_client.php');

define('OLP_DB_HOST','reader.olp.ept.tss');
define('OLP_DB_USER','sellingsource');
define('OLP_DB_PASS','password');
define('OLP_DB_DATABASE','olp');
define('OLP_DB_PORT','3307');


/*define('OLP_DB_HOST','db101.ept.tss');
define('OLP_DB_USER','sellingsource');
define('OLP_DB_PASS','password');
define('OLP_DB_DATABASE','rc_olp');
define('OLP_DB_PORT','3317');
*/


define('FAILOVER_DB_HOST','writer.olp.ept.tss');
define('FAILOVER_DB_USER','sellingsource');
define('FAILOVER_DB_PASS','password');
define('FAILOVER_DB_DATABASE','olp');
define('FAILOVER_DB_PORT','3306');

define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

/*
define('FAILOVER_DB_HOST','monster.tss');
define('FAILOVER_DB_USER','olp');
define('FAILOVER_DB_PASS','hochimin');
define('FAILOVER_DB_DATABASE','olp');
define('FAILOVER_DB_PORT','3326');
*/
define('LOCK_FILE','/tmp/failover_lock');

/**
 * Returns TRUE if the lockfile exists, false otherwise
 */
function isLocked()
{
	return file_exists(LOCK_FILE);
}

/**
 * Sets the lock file
 *
 */
function Lock()
{
	if(!file_exists(LOCK_FILE))
	{
		touch(LOCK_FILE);
	}
}

/**
 * Removes the Lock File
 */
function Unlock()
{
	if(file_exists(LOCK_FILE))
	{
		unlink(LOCK_FILE);
	}
}

/**
 * Creates the cron object and runs the necessary checks depending on
 * how long ago it was last run.
 *
 */
function RunCron()
{
	//if we're locked, just exit out
	if(isLocked())
	{
		exit;
	}
	//Most of these should be trapped inside the other calls,
	//but just incase
	try 
	{
		//Lock so we don't have multiple copies running since
		//database timeouts are SLOW
		Lock();
		$old_error = error_reporting(0);
		//load up the array of the timestamps of the last time we ran stuff
		$last_run_times = @file_get_contents('data/cron_info.dat');
		//We had nothing in the file(or no file), so initialize to 0
		if($last_run_times == NULL)
		{
			$last_run_times = Array();
			$last_run_times['oneminutechecks'] = 0;
			$last_run_times['fiveminutechecks'] = 0;
			$last_run_times['thirtyminutechecks'] = 0;
			$last_run_times['twentyfourhourchecks'] = 0;
		}
		else
		{
			//just unserialize the file string and it should be all set up
			$last_run_times = unserialize($last_run_times);
		}
		//the cron object:|
		$cron = new OLP_Failover_Cron();
		$new_run_time = time();
		if(($new_run_time - $last_run_times['oneminutechecks']) >= 60)
		{
			//Stuff that runs every minute
			$cron->Check_Web_Connections();
			$cron->Check_SMS();
			$cron->Check_Condor4();
			$cron->Check_Condor3();
			$last_run_times['oneminutechecks'] = $new_run_time;
		}
		if(($new_run_time - $last_run_times['fiveminutechecks']) >= 300)
		{
			//stuff that runs every 5 minutes
			$cron->Ping_DataX();
			$cron->Check_DataX_ABA();
			$cron->Check_DataX_IDV();
			$cron->Check_MOneyHelper();
			$cron->Check_Databases();
			//Check eCash slaves
			$cron->checkECashDatabases('LIVE_READONLY');
			//Check eCash masters
			$cron->checkECashDatabases('LIVE');
			$last_run_times['fiveminutechecks'] = $new_run_time;
		}
		if(($new_run_time - $last_run_times['thirtyminutechecks']) >= 1800)
		{
			$cron->Check_Blackbox_Vendors();
			$last_run_times['thirtyminutechecks'] = $new_run_time;
		}
		if(($new_run_time - $last_run_times['twentyfourhourchecks']) >= 86400)
		{
			$cron->DailyEmail();
			$last_run_times['twentyfourhourchecks'] = $new_run_time;
		}
		//now store our data as a serialized array for the next time we run
		file_put_contents('data/cron_info.dat',serialize($last_run_times));
		error_reporting($old_error);
		Unlock();
	}
	catch (Exception $e)
	{
		//Unknown exception so just report it as a rpeorted exception 
		Reported_Exception::Add_Recipient('email','stephan.soileau@sellingsource.com');
		Reported_Exception::Add_Recipient('email','brian.feaver@sellingsource.com');
		Reported_Exception::Add_Recipient('email','matt.piper@sellingsource.com');
		Reported_Exception::Add_Recipient('email','chris.barmonde@sellingsource.com');
		Reported_Exception::Report($e);
		Unlock();
		exit;
	}
}
/**
 * The main 'cron' that contians all the functions necessary
 * to run checks and alert people when stuff is broken.
 *
 */
class OLP_Failover_Cron
{
	private $olp_db;
	private $failover_db;
	private $event_log;

	const ALERT_METHOD_SMS   = 0;
	const ALERT_METHOD_EMAIL = 1;
	const ALERT_METHOD_BOTH  = 2;

	const CONDOR4_SERVER = 'condor.4.edataserver.com/condor_api.php';
	const CONDOR4_USER   = 'catAPI';
	const CONDOR4_PASS   = 'catApiPass';
	const CONDOR4_TEMPLATE = 'APITest';

	const CONDOR3_SERVER ='condor.3.edataserver.com/';

	//define the alert name and set it's value to true
	//to enable the alert, and false to disable
	private static $alerts = Array(
		//Enables/disables all alerts.
		//If false NO alerts will be sent, if true
		//only those set to true will be sent.
		'ALL_ALERTS'                          => true,
		//Enables all DATAX alerts
		'DATAX_ALL'                           => true,
		'DATAX_ABA_CHECK'                     => true,
		'DATAX_IDV_CHECK'                     => true,
		// ENABLES money helper errors
		'MONEYHELPER_ALL'                     => true,
		'MONEYHELPER_OPTIN_165'               => true,
		'MONEYHELPER_OPTIN_155'               => false,
		'MONEYHELPER_OPTIN_175'               => false,
		//Enable all database errors
		'DATABASE_ALL'                        => true,
		'DATABASE_EPM_COLLECT'                => true,
		'DATABASE_OLP_SLAVE'                  => true,
		'DATABASE_ECASH_CLK_MASTER'           => false,
		'DATABASE_ECASH_IMPACT_MASTER'        => false,
		'DATABASE_OLE_MASTER'                 => false,
		'DATABASE_ECASH_OLP_SLAVE'            => true,
		//disables all of the webconnect derrors
		'WEBCONNECT_ALL'                      => true,
		'WEBCONNECT_GROOPZ.SELLINGSOURCE.COM' => true,
		'WEBCONNECT_ABSOLUTEROI.COM'          => true,
		'WEBCONNECT_IMAGEDATASERVER.COM'      => true,
		'WEBCONNECT_BFW.1.EDATASERVER.COM'    => true,

		'CONDOR4'                             => true,
		'CONDOR3'                             => true,
		//Disable blackbox vendor alerts
		'BLACKBOX_ALL'                        => true
		 //to disable alerts for one vendor just an an entry like
		 //BLACKBOX_CAC => false where CAC is the all
		 //uppercase version of whatever would be in the winner
		 //column in the bb_post table
	);

	/**
	 *
	 */
	public function __construct()
	{
		try 
		{
			$this->olp_db = new MySQLi_2(OLP_DB_HOST,
				OLP_DB_USER,OLP_DB_PASS,
				OLP_DB_DATABASE,OLP_DB_PORT);
			$this->failover_db = new MySQLi_2(FAILOVER_DB_HOST,
				FAILOVER_DB_USER,FAILOVER_DB_PASS,
				FAILOVER_DB_DATABASE,FAILOVER_DB_PORT);
			$this->event_log = new OLP_Event_Log($this->olp_db);
		}
		catch (Exception $e)
		{
			$sent = $this->Alert(
				Array(),
				"Could not connect to database.\n".$e->getMessage(),
				"Database Error",
				Array('stephan soileau',
					'brian feaver',
					'matt piper',
					'chris barmonde',
					'mike genatempo',
					'jeff fiegel'),
				self::ALERT_METHOD_EMAIL
			);
			exit;
		}
	}
	
	/**
	 * Just sends out a daily email so that we know
	 * the Cron is still running
	 *
	 */
	public function DailyEmail()
	{
		$this->alert(NULL,
			'Failover Cron is running.',
			'Daily Email',
			array('stephan soileau','olpcron'),
			self::ALERT_METHOD_EMAIL
		);	
	}
	
	/**
	 * Checks to see if DataX ABA calls are erroring or not
	 *
	 */
	public function Check_DataX_ABA()
	{
		$time = time();
		$start_time = $time - 300;
		$end_time = $time;
		$errors = $this->event_log->Responses_Between_Dates('DATAX_ABA','ERROR',
			$start_time,$end_time);
		if($errors >= 5)
		{
			$sent = $this->Alert(
				Array('DATAX_ALL','DATAX_ABA_CHECK'),
				"There are $errors DataX ABA errors in the last 5 minutes.",
				"DataX: ABA",
				Array('stephan soileau',
					'brian feaver',
					'matt piper',
					'chris barmonde',
					'mike genatempo',
					'jeff fiegel'),
				self::ALERT_METHOD_EMAIL);
			$this->Log_Fail('DATAX_ABA_CHECK',$sent);

		}
		else
		{
		}
	}
	/**
	 * Checks to see if DataX IDV calls are erroring or not
	 *
	 */
	public function Check_DataX_IDV()
	{
		$time = time();
		$start_time = $time - 300;
		$end_time = $time;
		$errors = $this->event_log->Responses_Between_Dates('DATAX_IDV','ERROR',
			$start_time,$end_time);
		if($errors >= 5)
		{
			$sent = $this->Alert(
				Array('DATAX_ALL','DATAX_IDV_CHECK'),
				"There are $errors DataX IDV errors in the last 5 minutes.",
				"DataX: IDV",
				Array('stephan soileau',
					'brian feaver',
					'matt piper',
					'chris barmonde',
					'mike genatempo',
					'jeff fiegel'),
				self::ALERT_METHOD_EMAIL);
			$this->Log_Fail('DATAX_IDV_CHECK',$sent);

		}
		else
		{
		}
	}
	
	/**
	 * Checks to see if we can connect to, make a request, and get
	 * a response from the SMS server.
	 */
	public function Check_SMS()
	{
		require_once('sms.1.php');
		//Just checks a number on the blacklist
		//it shouldnt' be on there, but we'll get
		//a boolean if it works, and it'll throw
		//an exception if it can't do the check
		//The response is then passed to SMS_Check_Response
		//to decide if it's a real response or something
		//slightly more error like.
		try
		{
			$sms_client = new SMS('LIVE');
			$bool = $sms_client->Check_Blacklist('7028519576');
			$this->SMS_Check_Response($bool);
		}
		catch (Exception $e)
		{
			$this->SMS_Check_Response(NULL);
		}
	}

	/** 
	* Actually checks the response from the 
	* sms lib to see if it passed or failed.
	*/
	private function SMS_Check_Response($bool)
	{
		$data = $this->Load_File('sms_check.dat');
		if(empty($data))
		{
			$sms_fails = 0;
			$time_first_fail = 0;
		}
		else
		{
			$data = unserialize($data);
			$sms_fails = $data['fails'];
			$time_first_fail = $data['time_first_fail'];
		}
		if(!is_bool($bool))
		{
			$sms_fails++;
			if($sms_fails == 1)
				$time_first_fail = time();
			//only send an alert every 5th fail
			if($sms_fails >= 5 )
			{
				if($sms_fails % 5 == 0)
				{
					//only alert every 5th fail
					$sent = $this->Alert(
						'SMS_CHECK',
						"SMS Check failed $sms_fails in the last 5 minutes. First fail being ".date('Y-m-d H:i:s',$time_first_fail).'.',
						'SMS Check',
						Array('stephan soileau',
							'brian feaver',
							'matt piper',
							'chris barmonde',
							'mike genatempo',
							'jeff fiegel'),
						self::ALERT_METHOD_EMAIL);
				}
				else
				{
					$sent = false;
				}
				$this->Log_Fail('SMS_CHECK',$sent);
			}

		}
		else
		{
			$sms_fails = 0;
			$time_first_fail = 0;
		}
		$data['fails'] = $sms_fails;
		$data['time_first_fail'] = $time_first_fail;
		$this->Save_File('sms_check.dat',serialize($data));
	}
	
	/**
	 * Checks for errors with the MoneyHelper Optin
	 *
	 */
	public function Check_MoneyHelper()
	{
		//There's 3 different ones,
		//just add any of them in here
		//if we decide to check those
		$moneyhelper_stats = Array(
			'MONEYHELPER_OPTIN_165'
		//	,'MONEYHELPER_OPTIN_175'
		//	,'MONEYHELPER_OPTIN_155'
		);
		$time = time();
		$start_time = $time - 300;
		$end_time = $time;
		foreach($moneyhelper_stats as $stat)
		{
			$errors = $this->event_log->Responses_Between_Dates($stat,'ERROR',
				$start_time,$end_time);
			if($errors >= 5)
			{
				$sent = $this->Alert(
					Array('MONEYHELPER_ALL',$stat),
					"There are $errors having to do $stat errors in the last 5 minutes.",
					"MoneyHelper",
					Array('stephan soileau',
						'brian feaver',
						'matt piper',
						'chris barmonde',
						'mike genatempo',
						'jeff fiegel'),
					self::ALERT_METHOD_EMAIL);
				$this->Log_Fail($stat,$sent);
			}
		}
	}
	
	/**
	 * loops an array of databases and checks
	 * to make sure we can still connect to them
	 *
	 */
	public function Check_Databases()
	{
		//to check more database connections
		//just add to this array. Also
		//add an entry Failover_Cron::alerts
		//like DATABASE_MYAWESOMEDATABASE
		$db_array = array(
			'epm_collect' => Array(
				'host' => 'writer.dx.tss',
				'user' => 'olp',
				'pass' => '7Kr8NmdS',
				'database' => 'livefeed',
				'port' => 3306,
				'fails_before_alert' => 2),
			'olp_slave' => Array(
				'host' => 'reader.olp.ept.tss',
				'user' => 'sellingsource',
				'pass' => 'password',
				'database' => 'olp',
				'port' => 3307,
				'fails_before_alert' => 2),
		);
		//turn error reporting off because MySQLi sprays a ton
		//of warnings when it can't connect
		$old_error = error_reporting(0);
		$data = $this->Load_File('database_checks.dat');
		if(!empty($data))
			$data = unserialize($data);
		else
		{
			$data = Array();
		}
		foreach ($db_array as $name=>$info)
		{
			if(!isset($data[$name]))
			{
				$data[$name] = Array('fails'=>0);
			}
			if($this->Check_Database(
				$info['host'],
				$info['user'],
				$info['pass'],
				$info['database'],
				$info['port']) === false)
			{
				$data[$name]['fails']++;
				$sent = false;
				if($data[$name]['fails'] >= $info['fails_before_alert'])
				{
					$subject = "Database Alert: $name";
					$msg = "Could not connect to database $name.";
					$sent = $this->Alert(
						Array('DATABASE_ALL','DATABASE_'.strtoupper($name)),
						$msg,
						$subject,
						Array('stephan soileau',
							'brian feaver',
							'matt piper',
							'chris barmonde',
							'mike genatempo',
							'jeff fiegel',
							'devin egan'),
						self::ALERT_METHOD_EMAIL);
				}
				$this->Log_Fail('DATABASE_'.strtoupper($name),$sent);
			}
			else
			{
				//mail people saying we're back up
				if($data[$name]['fails'] >= $info['fails_before_alert'])
				{
					$this->Alert(
						Array('DATABASE_ALL','DATABASE_'.strtoupper($name)),
						"Database $name is back up.",
						"Database Alert: $name back up",
						Array('stephan soileau',
							'brian feaver',
							'matt piper',
							'chris barmonde',
							'mike genatempo',
							'jeff fiegel',
							'devin egan'),
						self::ALERT_METHOD_EMAIL);
				}
				$data[$name]['fails'] = 0;
			}
		}
		$this->Save_File('database_checks.dat',serialize($data));
		//set it back to what it was
		error_reporting($old_error);
	}
	
	//Loop through all property shorts in entPropList of
	//EnterpriseData and test ther ECash database connections
	//for whatever mode.
	public function checkECashDatabases($mode = 'RC')
	{
		require_once(BFW_CODE_DIR.'Enterprise_Data.php');
		require_once(BFW_CODE_DIR.'server.php');
		
		$properties = array_keys(Enterprise_Data::getEntPropList());
		
		//turn error reporting off because MySQLi sprays a ton
		//of warnings when it can't connect
		$old_error = error_reporting(0);
		$data = $this->Load_File('database_checks.dat');
		if(!empty($data))
			$data = unserialize($data);
		else
		{
			$data = Array();
		}
		
		foreach($properties as $prop_short)
		{
			$name = 'ldb_'.$prop_short.'_'.$mode;
			$info = Server::Get_Server($mode, 'MySQL', $prop_short);
			if($this->Check_Database(
				$info['host'],
				$info['user'],
				$info['password'],
				$info['db'],
				$info['port']) === false)
			{
				$data[$name]['fails']++;
				$sent = false;
				if($data[$name]['fails'] >= 2)
				{
					$subject = "Database Alert: $name";
					$msg = "Could not connect to database $name.";
					$sent = $this->Alert(
						Array('DATABASE_ALL','DATABASE_'.strtoupper($name)),
						$msg,
						$subject,
						Array('stephan soileau',
							'brian feaver',
							'mike genatempo',
							'jeff fiegel',
							'devin egan',
						),
						self::ALERT_METHOD_EMAIL);
				}
				$this->Log_Fail('DATABASE_'.strtoupper($name),$sent);
			}
			else
			{
				//mail people saying we're back up
				if($data[$name]['fails'] >= 2)
				{
					$this->Alert(
						Array('DATABASE_ALL','DATABASE_'.strtoupper($name)),
						"Database $name is back up.",
						"Database Alert: $name back up",
						Array('stephan soileau',
							'brian feaver',
							'mike genatempo',
							'jeff fiegel',
							'devin egan',
						),
						self::ALERT_METHOD_EMAIL);
				}
				$data[$name]['fails'] = 0;
			}
		}
		$this->Save_File('database_checks.dat',serialize($data));
		//set it back to what it was
		error_reporting($old_error);
	}

	
	/**
	 * Pings DataX and updates the failover data table with
	 * the appropriate info.
	 *
	 */
	public function Ping_DataX()
	{
		//$url = 'http://verihub.com/datax/index.php';
		$url = 'http://verihub.com/dataxv3/xmltest.php';
		if($this->Check_Web_Connect($url,7) === false)
		{
			$this->Update_Data('DATAX_DOWN','true');
		}
		else 
		{
			$this->Update_Data('DATAX_DOWN','false');
		}
	}
	
	private function Update_Data($key,$val)
	{
		$query = "REPLACE into failover_data SET 
			name='$key',
			value='$val',
			date_set=NOW(),
			set_by='AUTO'
		";
		$query = $this->failover_db->Query($query);
	}
	
	/**
	 * Check to make sure these URLS are
	 * still up and running.
	 *
	 */
	public function Check_Web_Connections()
	{
		$urls = Array(
			'groopz.sellingsource.com',
			'absoluteroi.com',
			'imagedataserver.com',
			'bfw.1.edataserver.com'
		);
		$data = $this->Load_File('webconnects.dat');
		if(is_null($data))
		{
			$data = Array();
		}
		else
		{
			$data = unserialize($data);
		}
		foreach ($urls as $url)
		{
			if($this->Check_Web_Connect($url) === false)
			{
				$data[$url]++;
				if($data[$url] > 5)
				{
					//only send an alert every 5th fail
					if($data[$url] % 5 == 0)
					{
						$subject = "Web Connect: $url";
						$msg = "Could not connect to $url";
						$sent = $this->Alert(
							Array('WEBCONNECT_ALL','WEBCONNECT_'.strtoupper($url)),
							$msg,$subject,
							Array('stephan soileau',
								'brian feaver',
								'matt piper',
								'chris barmonde',
								'mike genatempo',
								'jeff fiegel'),
							self::ALERT_METHOD_EMAIL);
					}
					else
					{
						$sent = false;
					}
					$this->Log_Fail('WEBCONNECT_'.strtoupper($url),$sent);
				}
			}
			else
			{
				$data[$url] = 0;
			}
		}
		$this->Save_File('webconnects.dat',serialize($data));
	}
	
	/**
	 * Get all the vendors that have 10 or more timeouts in the last
	 * 5 minutes and alert/log them.
	 *
	 */
	public function Check_Blackbox_Vendors()
	{
		$query = "SELECT winner, count(winner) as total
           FROM blackbox_post
           WHERE post_time > 21
           AND date_created > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
           GROUP BY winner HAVING total >= 15";
		try 
		{
			$this->olp_db->Ping();
			$res = $this->olp_db->Query($query);
			while($row = $res->Fetch_Object_Row())
			{
				$msg = "BlackBox Vendor {$row->winner} has {$row->total} timeouts".
					" in the last 30 minutes.";
				$sent = $this->Alert(
					Array('BlACKBOX_ALL','BLACKBOX_'.strtoupper($row->winner)),
					$msg,'Blackbox Vendor Timeout',
					Array('stephan soileau',
						'brian feaver',
						'matt piper',
						'chris barmonde',
						'mike g',
						'hope pacariem',
						'jeff fiegel',
						'august malson'),
				self::ALERT_METHOD_EMAIL);
				$this->Log_Fail('BLACKBOX_TIMEOUT_'.strtoupper($row->winner),$sent);
			}
		}
		catch (Exception $e)
		{
			
		}
	}
	
	/**
	 * Attempt to 'preview' a test document on condor 4.
	 *
	 */
	public function Check_Condor4()
	{
		require_once('prpc/client.php');
		try
		{
			//Connect to condor4, create a fake document(don't archive it
			//though. If we get back an object, it worked, if not, we failed
			//so save that, and move along.
			
			$condor = new PRPC_Client('prpc://'.self::CONDOR4_USER .':'.
			self::CONDOR4_PASS .'@'.self::CONDOR4_SERVER);
			$doc = $condor->Create(self::CONDOR4_TEMPLATE,Array());
			$this->Condor4_Alert($doc);
		}
		catch (Exception $e)
		{
			$this->Condor4_Alert(NULL);
		}
	}
	
	/**
	 * Check the doc for data and send the proper stuff
	 *
	 * @param unknown_type $doc
	 */
	
	private function Condor4_Alert($doc)
	{
		if(!(is_object($doc) && isset($doc->data)))
		{
			$data = $this->Load_File('check_condor4.dat');
			$data++;
			if($data > 5)
			{
				//only send an alert every 5th fail
				if($data % 5 == 0)
				{
					$sent = $this->Alert(
						Array('CONDOR4'),
						'OLP Failover could not create a condor4 test document.',
						'Condor4',
						Array('stephan soileau',
							'brian feaver',
							'matt piper',
							'chris barmonde',
							'mike genatempo',
							'jeff fiegel'),
						self::ALERT_METHOD_EMAIL);
				}
				else
				{
					$sent = false;
				}
				$this->Log_Fail('CONDOR4',$sent);
			}
		}
		else
		{
			$data = 0;
		}
		$this->Save_File('check_condor4.dat',$data);
	}
	
	/**
	 * Makes sure we can connect to condor3
	 */
	public function Check_Condor3()
	{
		require_once('condor_client.php');
		try
		{
			//Use the condor3 client class to attempt
			//and run a Test_Condor_PRPC call on condor3
			$data = $this->Load_file('check_condor3.dat');
			$client = new Condor3_Client('prpc://'.self::CONDOR3_SERVER);
			$client->Test_Condor_PRPC();
			$res = $client->response;
			$this->Condor3_Alert($res);
		}
		catch (Exception $e)
		{
			$this->Condor3_Alert('');
		}

	}
	
	/**
	 * Takes the response from condor3 and generates the 
	 * alert as necessary
	 *
	 * @param unknown_type $res
	 */
	private function Condor3_Alert($res)
	{
		if(empty($res))
		{
			$data++;
			if($data > 5)
			{
				//only send an alert every 5th fail (5 minutes)
				if($data % 5 == 0)
				{
					$sent = $this->Alert(
						Array('CONDOR3'),
						'OLP Failover could not use condor3.',
						'Condor3',
						Array('stephan soileau',
							'brian feaver',
							'matt piper',
							'chris barmonde',
							'mike genatempo',
							'jeff fiegel'),
						self::ALERT_METHOD_EMAIL);
				}
				else
				{
					$sent = false;
				}
				$this->Log_Fail('CONDOR3',$sent);
			}
		}
		else
		{
			$data = 0;
		}
		$this->Save_File('check_condor3.dat',$data);
	}
	
	/**
	 * Log a failed check to the log table. It returns the id of the log
	 * entry.
	 *
	 * @param string $name The check that failed
	 * @param boolean $alert_sent Was the an alert sent?
	 * @return int
	 */
	private function Log_Fail($name,$alert_sent=false)
	{
		$alert_sent = "'".$this->failover_db->Escape_String(($alert_sent ? 'TRUE' : 'FALSE'))."'";
		$name = "'".$this->failover_db->Escape_String($name)."'";
		$this->failover_db->Ping();
		$this->failover_db->Query("INSERT INTO fail_log
			(failover_name,date_created,alert_sent) VALUES($name,NOW(),$alert_sent);");
		return $this->failover_db->Insert_Id();
	}
	
	/**
	 * Uses curl to make sure we can connect to a url. Default timeout is 12.
	 * Retruns true if the connection can be made, false otherwise.
	 * @param string $url
	 * @param int $timeout
	 * @return boolean
	 */
	private function Check_Web_Connect($url,$timeout=12)
	{
		$curl = curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_FRESH_CONNECT,true);
		curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,$timeout);
		curl_setopt($curl,CURLOPT_FORBID_REUSE,true);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		$res = @curl_exec($curl);
		$error_number = @curl_errno($curl);
		if($error_number == 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
	 * Checks to see if a particular database is up and running
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param string $database
	 * @param string $port
	 * @return boolean
	 */
	private function Check_Database($host,$user,$pass,$database,$port=3306)
	{
		try
		{
			$link = new mysqli();
			$link->init();
			$link->options(MYSQLI_OPT_CONNECT_TIMEOUT,8);
			$link->real_connect($host,$user,$pass,$database,$port);
			if(mysqli_connect_errno())
			{
				return FALSE;
			}
			return true;

		}
		catch (Exception $e)
		{
			return FALSE;
		}
		return FALSE;
	}
	/**
	 * Sends an alert to all the people in the alertees array via the
	 * given method.
	 *
	 * @param string $msg
	 * @param string $subject
	 * @param Array $alertees
	 * @param int $method
	 */
	private function Alert($alert,$msg,$subject,$alertees,$method)
	{
		if($alert != NULL)
		{
			if(!is_array($alert)) $alert = Array($alert);
			$alert[] = 'ALL_ALERTS';
			//check to see if the alert is enabled or not
			foreach($alert as $val)
			{
				if(isset(self::$alerts[$val]) && self::$alerts[$val] === false)
				{
					return false;
				}
			}
		}
		if(!is_array($alertees)) $alertees = Array($alertees);
	
		foreach($alertees as $val)
		{
			switch($method)
			{
				case self::ALERT_METHOD_BOTH:
				case self::ALERT_METHOD_SMS:
					$sms = Alertee::Get_SMS($val);
					if(is_numeric($sms))
					{
						SMS_Reporter::Send($sms,$msg,'OLP Failover: '.$subject);
					}
					//Don't break if we're supposed to be doing both
					if($method != self::ALERT_METHOD_BOTH)
					{
						break;
					}
				case self::ALERT_METHOD_EMAIL:
					$email = Alertee::Get_Email($val);
					if(strpos($email,'@') !== false)
					{
						EMail_Reporter::Send($email,$msg,'OLP Failover: '.$subject);
					}
					break;
			}
		}
		return true;
	}

	/**
	 * just reads and returns the contents of a file in the data directory
	 *
	 * @param string $file
	 * @return string
	 */
	private function Load_File($file)
	{
		if(!is_dir('data'))
		{
			mkdir('data',0755);
		}
		if(@file_exists('data/'.$file))
		{
			return file_get_contents('data/'.$file);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Saves a string to that directory
	 *
	 * @param string $file
	 * @param string $data
	 */
	private function Save_File($file,$data)
	{
		if(!is_dir('data'))
		{
			mkdir('data',07555);
		}
		file_put_contents('data/'.$file,$data);
	}
}

/**
 * Class to check a few things dealing with OLP's event_log
 *
 */
class OLP_Event_Log
{
	private $olp_db;

	/**
	 * Instantiate myself and setup the olp database. You can optionally
	 * pass a MySQLi_1 object for it to use, otherwise it creates it's own
	 *
	 * @param mixed $db
	 */
	public function __construct($db = NULL)
	{
		if(!($db instanceof MySQLi_1))
		{
			$db = new MySQLi_1(OLP_DB_HOST,
			OLP_DB_USER,OLP_DB_PASS,
			OLP_DB_DATABASE,OLP_DB_PORT);
		}
		$this->olp_db = $db;
	}

	/**
	 * Find the number of times a given response to a given event between
	 * a given set of timestamps
	 *
	 * @param mixed $response The name or id of the response
	 * @param mixed $event The name or id of the event to look for
	 * @param unix_timestamp $start_time The timestamp to start searching from
	 * @param unix_timestamp $end_time The timestamp to stop searching at
	 * @return int
	 */
	public function Responses_Between_Dates($event,$response,$start_time,$end_time=NULL)
	{
		return -1;

		if($end_time == NULL)
			$end_time = time();
		$res_id = $this->Get_Response_Id($response);

		$event_id = $this->Get_Event_Id($event);
		$event_table = $this->Get_Event_Table($start_time);
		$start_date = date('YmdHis',$start_time);
		$end_date = date('YmdHis',$end_time);
		$query = "SELECT count(*) as cnt FROM $event_table WHERE
			date_created BETWEEN '$start_date' AND '$end_date'
			AND event_id='$event_id' AND response_id='$res_id'";
		if($this->olp_db->Ping())
		{
			$res = $this->olp_db->Query($query);
			if($row = $res->Fetch_Object_Row())
				return $row->cnt;
			else
				return -1;
		}
		else 
		{
			return -1;
		}
	}

	/**
	 * Returns the event_id associated with a given event_name. If you give
	 * it a number, it'll assume it's the id and give it back to you since
	 * all of our events have non-numeric names.
	 *
	 * @param mixed $event_name
	 * @return int
	 */
	public function Get_Event_Id($event_name)
	{
		//if it's number, assume it's the ID already
		if(is_numeric($event_name))
		{
			return $event_name;
		}
		$safe_event_name = "'".$this->olp_db->Escape_String($event_name)."'";
		$query = "SELECT event_id FROM events WHERE event=$safe_event_name";
		$this->olp_db->Ping();
		$res = $this->olp_db->Query($query);
		if($res->Row_Count())
		{
			$row = $res->Fetch_Object_Row();
			return $row->event_id;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Returns the response id associated with a given response name. If you
	 * give it a number, it'll assume it's the id and give it back to you since
	 * all of our responses have non-numeric names
	 *
	 * @param mixed $response_name
	 * @return int
	 */
	public function Get_Response_Id($response_name)
	{
		if(is_numeric($response_name))
		{
			return $response_name;
		}
		$safe_response_name = "'".$this->olp_db->Escape_String($response_name)."'";
		$query = "SELECT response_id FROM event_responses WHERE response=$safe_response_name";
		$this->olp_db->Ping();
		$res = $this->olp_db->Query($query);
		if($res->Row_Count())
		{
			$row = $res->Fetch_Object_Row();
			return $row->response_id;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Returns the proper event_log table name based on the timestamp given
	 *
	 * @param timestamp $time_stamp
	 * @return string
	 */
	public function Get_Event_Table($time_stamp)
	{
		return 'event_log_'.date('Ym',$time_stamp);
	}
}

/**
 * so I can change the condor3 server
 */
class Condor3_Client extends Condor_Client
{
	public function __construct($url)
	{
		require_once('prpc/client.php');
		$this->condor = new PRPC_Client($url);
	}
}

/**
 * New MySQLi Class that includes the ping function
 * without the other crazy stuff that is included
 * in the new version of the library
 *
 */
class MySQLi_2 extends MySQLi_1
{
	public function Ping()
	{
		$link = $this->Get_Link();
		return $link->ping();
	}
}

//Just lists the failover cron methods that can be called
//for the purpose of testing individual checks.
function list_OLP_Failover_Methods()
{
	$reflected_obj = new ReflectionClass('OLP_Failover_Cron');
	$methods = $reflected_obj->getMethods();
	if(count($methods > 0))
	{
		$i = 0;
		$str = '';
		foreach($methods as $method)
		{
			if($method->isPublic() && substr($method->name,0,2) != '__')
			{
				$i++;
				$str = sprintf("%s %22s",$str,$method->name);
				if($i % 3 ==0)
				{
					$str .= "\n";
				}
			}
		}
		echo $str;
	}
	else
	{
		echo "There are no methods available.";
	}
}

//if we have no argument OR it's 'cron' run the cron!
if(empty($argv[1]) || strcasecmp($argv[1],'cron') == 0)
{
	RunCron();
	exit;
}
//if it's help list all the methods that are possible to call
elseif(strcasecmp($argv[1],'help') == 0)
{
	echo "Usage: ".basename(__FILE__)." <method>\n";
	echo "Here's the current list of methods you can run\n";
	list_OLP_Failover_Methods();
}
else
{
	//if it's anything else, check to see if it's 
	//a callable method of OLP_Failover_Cron. If it is
	//call that otherwise, list methods.
	$failover = new OLP_Failover_Cron();
	if(is_callable(Array($failover,$argv[1])))
	{
		$failover->{$argv[1]}();
	}
	else
	{
		echo("Unknown method. Try one of the following:\n");
		list_OLP_Failover_Methods();
	}
}
