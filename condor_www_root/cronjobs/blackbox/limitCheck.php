<?php
/**
 * Alert script to check customers to see if they've gone over their daily limits.
 * 
 * Usage: limitChecks {mode}
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @copyright Selling Source, Inc.
 */

require_once('mysqli.1.php');
require_once('/virtualhosts/bfw.1.edataserver.com/include/code/server.php');

class LimitCheck
{
	/**
	 * The limit fudge factor.
	 *
	 * A limit fudge factor so that we don't immediately start throwing alerts
	 * if they go over by just a few.
	 *
	 * [gForge 7557] - Changed limit from 20 to 5 since calculated reacts shouldnt
	 * be incldued. - [MP]
	 */
	const LIMIT_FUDGE = 5;
	
	/**
	 * The email subject.
	 */
	const EMAIL_SUBJECT = "EXCEPTION: Campaigns over limit";
	
	/**
	 * The alert file name.
	 */
	const ALERT_FILE = '/tmp/limit_alerts';
	
	/**
	 * The amount of time to wait before sending another alert.
	 * 
	 * This is passed to strtotime().
	 */
	const ALERT_TIMEOUT = '-1 hour';
	
	/**
	 * Adds the High Importance header to the email.
	 *
	 * The Importance: High header is used by Outlook, and possibly other email
	 * clients, to show that this message was sent with high priority.
	 */
	const IMPORTANCE_HEADER = "Importance: High\r\n";
	
	/**
	 * OLP database connection.
	 *
	 * @var MySQLi_1
	 */
	private $db;
	
	/**
	 * A list of active target IDs.
	 *
	 * @var array
	 */
	private $target_list = array();
	
	/**
	 * A list of targets that are above their limit.
	 *
	 * @var array
	 */
	private $alert_list = array();
	
	/**
	 * A list of email addresses to whom alerts will be sent.
	 *
	 * @var array
	 */
	private $alert_email_recipients = array(
		'brian.feaver@sellingsource.com',
		'christopher.barmonde@sellingsource.com',
		'matt.piper@sellingsource.com',
		'mike.genatempo@sellingsource.com'
	);
	
	/**
	 * Class constructor.
	 *
	 * @param MySQLi_1 $db
	 * @param string $property_short
	 */
	public function __construct(MySQLi_1 &$db)
	{
		$this->db =& $db;
	}
	
	/**
	 * Runs the checks to see if any target is over their limit for today.
	 */
	public function runCheck()
	{
		// Pull the target id for the property short
		$this->getActiveTargets();
		
		// Check if the list has anything in it
		if (empty($this->target_list)) throw new Exception('Active target list is empty');
		
		// Loop through the targets and see if anyone is over their limit
		foreach ($this->target_list as $target)
		{
			$lead_count = $this->getCompanyLeads($target->target_id);
			
			if ($lead_count > $target->limit + self::LIMIT_FUDGE)
			{
				$target->lead_count = $lead_count;
				$this->alert_list[$target->property_short] = $target;
				$count++;
			}
		}

		$last_alert_time = 0;
		$last_alert_info = array();
		
		// Grab the information for past alerts
		if (file_exists(self::ALERT_FILE))
		{
			$last_alert_info = unserialize(file_get_contents(self::ALERT_FILE));
			
			if (isset($last_alert_info['last_alert_time']))
			{
				$last_alert_time = $last_alert_info['last_alert_time'];
			}
		}
		
		// Do we need to send out an alert? Have any counts changed?
		foreach ($this->alert_list as $target)
		{
			if (isset($last_alert_info['alerts'][$target->property_short])
				&& $last_alert_info['alerts'][$target->property_short] == $target->lead_count)
			{
				// Let's remove that target from the list, it hasn't changed
				unset($this->alert_list[$target->property_short]);
			}
		}
		
		// If the alert list isn't empty, we must have some problem targets
		if (!empty($this->alert_list) && $last_alert_time < strtotime('-1 hour'))
		{
			// RED ALERT!
			$this->sendAlert();
			
			// Grab all the alerts and store their counts
			$alert_file_contents = array('last_alert_time' => time());
			foreach ($this->alert_list as $target)
			{
				$alert_file_contents['alerts'][$target->property_short] = $target->lead_count;
			}
			
			// Keeps us from getting an alert every time this script runs
			file_put_contents(self::ALERT_FILE, serialize($alert_file_contents));
		}
	}
	
	/**
	 * Returns a count of the number of applications a company has received
	 * in the current day.
	 *
	 * @param int $target_id an integer of the target ID
	 * @return int an integer of the number of leads
	 */
	private function getCompanyLeads($target_id)
	{
		$app_count = 0;
		$query_date = date('Y-m-d 00:00:00');
		
		$target_id = $this->db->Escape_String($target_id);
		
		/* [gForge 7557] - Add is_react=0 check to exclude calculated reacts - [MP] */
		$query = "
			SELECT
				COUNT(a.application_id) AS app_count
			FROM
				application a
			WHERE
				a.target_id = {$target_id}
				AND a.created_date > '{$query_date}'
				AND a.olp_process = 'online_confirmation'
				AND a.is_react='0'";
		$result = $this->db->Query($query);
		
		if (($row = $result->Fetch_Object_Row()))
		{
			$app_count = $row->app_count;
		}
		
		return $app_count;
	}
	
	/**
	 * Sets the target list with currently active targets.
	 * 
	 * Populates the class target list with currently active targets. This will
	 * reset any existing list.
	 */
	private function getActiveTargets()
	{
		$this->target_list = array();
		
		/*
			We don't get any campaigns with a limit of 0 because those are either special
			campaigns where we aren't checking for limits anyway, or they're in testing
			and they won't be getting leads anyway.
			
			We also exclude CLK, which by their very nature, do not have caps.
		*/
		$query = "
			SELECT
				t.target_id,
				t.property_short,
				c.limit,
				c.daily_limit
			FROM
				target t
				INNER JOIN campaign c USING (target_id)
			WHERE
				t.status = 'ACTIVE'
				AND t.deleted = 'FALSE'
				AND t.property_short NOT IN ('ucl', 'pcl', 'ca', 'd1', 'ufc')
				AND c.status = 'ACTIVE'
				AND c.type = 'ONGOING'
				AND c.limit != 0
			ORDER BY t.property_short";
		
		$result = $this->db->Query($query);
		
		while (($row = $result->Fetch_Object_Row()))
		{
			/* [gForge 7557] - Modify limit checking script to include daily_limits - [MP]*/
			if ($row->daily_limit)
			{
				$daily_limit_array = unserialize($row->daily_limit);
				if ($daily_limit_array[7] == '1')
				{
					// blackbox.target.php told me index 7 indicates to use the
					//   "Detailed Daily Limits" if the value is '1', otherwise
					//   just use the "Default Limit".
					$day_index = date('N') - 1;
					$row->limit = $daily_limit_array[$day_index];
				}
			}
			$this->target_list[] = $row;
		}
	}
	
	/**
	 * Send out the alerts for campaigns that are over their limit.
	 */
	private function sendAlert()
	{
		// Construct the email
		$email_body = "Campaigns below are over their daily limit:\n";
		foreach ($this->alert_list as $target)
		{
			$email_body .= sprintf(
				'Campaign: %-7s Limit: %-4u Count: %u',
				$target->property_short,
				$target->limit,
				$target->lead_count
			);
			$email_body .= "\n";
		}
		
		$recipients = implode(',', $this->alert_email_recipients);
		mail($recipients, self::EMAIL_SUBJECT, $email_body, self::IMPORTANCE_HEADER);
	}
	
	// TODO: In alerts, show the number of current confirms and agrees for enterprise customers
//	private function getCompanyConfirms($property_short)
//	{
//		
//	}
//	
//	private function getCompanyAgrees($property_short)
//	{
//		
//	}
}

// =================================
// The actual script
// =================================

global $argc;
global $argv;

date_default_timezone_set('America/Los_Angeles');

// Check for passed in mode
if ($argc == 2)
{
	if(!is_string($argv[1]) || empty($argv[1])) die("{$argv[1]} is not a valid mode\n");
	$mode = $argv[1];
}
else
{
	die("No mode defined. Please specify a mode.\n");
}

$db_info = Server::Get_Server($mode, 'BLACKBOX');
try
{
	$db = new MySQLi_1($db_info['host'], $db_info['user'], $db_info['password'], $db_info['db']);
	$alert = new LimitCheck($db);
	$alert->runCheck();
}
catch(Exception $e)
{
	print "Exception Thrown\nFile: {$e->getFile()} - Line: {$e->getLine()}\nMessage: {$e->getMessage()}\n";
}
?>
