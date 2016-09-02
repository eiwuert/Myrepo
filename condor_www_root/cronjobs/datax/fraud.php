#!/usr/bin/php
<?php
/**
 * Script to handle fraud report for DataX.
 * 
 * This report is run twice weekly on Monday morning at 8 AM and Thursday morning at 8 AM. It
 * currently runs checking the past 7 days.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once('mysql.4.php');
require_once(BFW_CODE_DIR.'server.php');
require_once(BFW_CODE_DIR.'setup_db.php');
require_once(BFW_CODE_DIR.'crypt_config.php');
require_once(BFW_CODE_DIR.'crypt.singleton.class.php');
require_once('libolution/AutoLoad.1.php');
require_once('tx/Mail/Client.php');

// Set our default timezone
date_default_timezone_set('America/Los_Angeles');

/**
 * DataXFraud class definition.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DataXFraud
{
	/**
	 * The libolution crypt object.
	 *
	 * @var Crypt_Singleton
	 */
	private $crypt;
	
	/**
	 * The mode we're running in.
	 *
	 * @var string
	 */
	private $mode = 'REPORT';
	
	/**
	 * The database object.
	 *
	 * @var MySQL_4
	 */
	private $db;
	
	/**
	 * The start time of the job.
	 *
	 * @var int
	 */
	private $real_start_time;
	
	/**
	 * The end time of the job.
	 *
	 * @var int
	 */
	private $real_end_time;
	
	/**
	 * Array of dates to be used to break up the queries.
	 *
	 * @var array
	 */
	private $query_dates = array();
	
	/**
	 * The query we use to get the DataX fraud information.
	 */
	const FRAUD_QUERY = "
		SELECT   
			c.ip_address,
			c.url AS originating_url,
			p.social_security_number,
			a.date_created,
			a.reason,
			a.application_id,
			ap.application_type,
			a.date_modified,
			ap.target_id,
			p.first_name,
			p.middle_name,
			p.last_name,
			p.home_phone,
			p.cell_phone,
			p.fax_phone,
			p.email,
			p.date_of_birth,
			p.drivers_license_number,
			p.best_call_time,
			p.drivers_license_state,
			r.residence_type,
			r.address_1,
			r.address_2,
			r.city,
			r.state,
			r.zip,
			b.bank_name,
			b.routing_number,
			b.account_number,
			b.direct_deposit,
			b.bank_account_type,
			e.employer,
			e.work_phone,
			i.monthly_net
			FROM
				%s a
				INNER JOIN application ap
					ON a.application_id = ap.application_id
				INNER JOIN personal_encrypted p
					ON a.application_id = p.application_id
				INNER JOIN campaign_info c
					ON a.application_id = c.application_id
				INNER JOIN residence r
					ON a.application_id = r.application_id
				INNER JOIN bank_info_encrypted b
					ON a.application_id = b.application_id
				INNER JOIN employment e
					ON a.application_id = e.application_id
				INNER JOIN income i
					ON a.application_id = i.application_id
			WHERE 
				a.authentication_source_id = 2 
				AND a.decision = 'PASS' 
				AND a.date_created >= '%s'
				AND a.date_created < '%s'";
	
	const OLP_DB = 'olp';
	
	const ONE_WEEK = 7;
	
	const TMP_FILE = '/tmp/dataxfraudreport.csv';
	
	const ZIP_FILE = '/tmp/datax_fraud_%s.zip';
	
	/**
	 * DataXFraud constructor.
	 * 
	 * The constructor sets the mode and database connection based on the passed in values. It also
	 * sets up the start time and end time as well as the date intervals the queries will run.
	 *
	 * @param MySQL_Wrapper $db       a MySQL_Wrapper object with the database instance
	 * @param int           $end_date a integer represting the start date to use
	 */
	public function __construct(MySQL_Wrapper &$db, $end_date = NULL)
	{
		$this->db =& $db;
		
		$crypt_config = Crypt_Config::Get_Config('LIVE');
		$this->crypt = Crypt_Singleton::Get_Instance($crypt_config['KEY'], $crypt_config['IV']);
		
		/**
		 * The end time is 8 AM today and we'll calculate the start time as 7 days ago at 8 AM,
		 * unless we've gotten the end date from the command line.
		 */
		if (is_null($end_date))
		{
			$current_time = getdate();
			$this->real_end_time = mktime(8, 0, 0);
		}
		else
		{
			$current_time = getdate($end_date);
			$this->real_end_time = mktime(8, 0, 0,
				$current_time['mon'],
				$current_time['mday'],
				$current_time['year']
			);
		}
		
		$this->real_start_time = mktime(8, 0, 0,
			$current_time['mon'],
			$current_time['mday'] - self::ONE_WEEK,
			$current_time['year']
		);
		
		/**
		 * We want to break up the queries by day so we're not monopolizing the database for long
		 * periods of time. We'll break out the query dates by day.
		 */
		$i = $this->real_start_time;
		while ($i < $this->real_end_time)
		{
			$query_start_time = getdate($i);
			$query_end_time = mktime(8, 0, 0, $query_start_time['mon'], $query_start_time['mday'] + 1);
			
			// If the months change between start and end time, we need to split this into two
			if (date('m', $i) != date('m', $query_end_time))
			{
				// Change the end time to be the same day right before midnight
				$modified_end_time = mktime(23, 59, 59, $query_start_time['mon'], $query_start_time['mday']);
				$this->query_dates[] = array(date('Y-m-d H:i:s', $i), date('Y-m-d H:i:s', $modified_end_time));
				
				// Change the start time on the next entry to be midnight to the original end time
				$modified_start_time = mktime(0, 0, 0,
					$query_start_time['mon'],
					$query_start_time['mday'] + 1
				);
				$this->query_dates[] = array(
					date('Y-m-d H:i:s', $modified_start_time),
					date('Y-m-d H:i:s', $query_end_time)
				);
			}
			else
			{
				$this->query_dates[] = array(date('Y-m-d H:i:s', $i), date('Y-m-d H:i:s', $query_end_time));
			}
			
			$i = $query_end_time;
		}
	}
	
	/**
	 * Run the fraud script.
	 *
	 */
	public function run()
	{
		if (($file = fopen(self::TMP_FILE, 'w')) == FALSE)
		{
			die('Could not open temporary CSV file' . PHP_EOL);
		}
		
		$wrote_headers = FALSE;
		
		foreach ($this->query_dates as $query_dates)
		{
			if (date('m') != date('m', strtotime($query_dates[0])))
			{
				$auth_table = 'olp_archive.authentication_'.date('Ym01');
			}
			else 
			{
				$auth_table = 'authentication';
			}
			
			// Create the query, filling in the dates and the authentication table to use
			$q2 = sprintf(self::FRAUD_QUERY, $auth_table, $query_dates[0], $query_dates[1]);
			
			try
			{
				$result = $this->db->Query(self::OLP_DB, $q2);
			}
			catch (MySQL_Exception $e)
			{
				die($e->getMessage());
			}
			
			// We only want to write the headers once
			if (!$wrote_headers)
			{
				// Get the column names
				for ($i = 0; $i < mysql_num_fields($result); $i++)
				{
					$meta = mysql_fetch_field($result, $i);
					$csv_header[] = $meta->name;
				}
				
				// Output the headers (which just so happen to be the database column names)
				fputcsv($file, $csv_header);
				$wrote_headers = TRUE;
			}
			
			while (($row = $this->db->Fetch_Array_Row($result)))
			{
				// Fields that need to be decrypted
				$row['social_security_number'] = $this->formatSSN(
					$this->crypt->decrypt($row['social_security_number'])
				);
				$row['date_of_birth'] = $this->crypt->decrypt($row['date_of_birth']);
				$row['routing_number'] = $this->crypt->decrypt($row['routing_number']);
				$row['account_number'] = $this->crypt->decrypt($row['account_number']);
				
				// Fields that just need to be formatted
				$row['home_phone'] = $this->formatPhone($row['home_phone']);
				$row['cell_phone'] = $this->formatPhone($row['cell_phone']);
				$row['fax_phone'] = $this->formatPhone($row['fax_phone']);
				$row['work_phone'] = $this->formatPhone($row['work_phone']);
				
				// Put it out as a CSV line in our file
				fputcsv($file, $row);
			}
		}
		
		fclose($file);
		
		if (!$this->zipFile())
		{
			echo 'Failed to create ZIP file' . PHP_EOL;
		}
	}
	
	/**
	 * Zips up the temporary file.
	 * 
	 * Returns TRUE if it created the zip file, FALSE otherwise.
	 *
	 * @return bool.
	 */
	private function zipFile()
	{
		$zip_file = sprintf(self::ZIP_FILE, date('m_d_Y', $this->real_end_time));
		$cmd = sprintf('zip -j %s %s', $zip_file, self::TMP_FILE);
		exec($cmd);
		
		if (file_exists($zip_file))
		{
			unlink(self::TMP_FILE);
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Returns a social security number with the hyphens added back in.
	 *
	 * @param string $ssn
	 * @return string
	 */
	private function formatSSN($ssn)
	{
		return sprintf("%s-%s-%s", substr($ssn, 0, 3), substr($ssn, 3, 2), substr($ssn, 5, 4));
	}
	
	/**
	 * Returns a formatted phone number with hyphens added in.
	 *
	 * @param string $number
	 * @return string
	 */
	private function formatPhone($number)
	{
		if (!is_numeric($number)) return '';
		return sprintf("%s-%s-%s", substr($number, 0, 3), substr($number, 3, 3), substr($number, 6, 4));
	}
}

// Bad word, I know, but it gets rid of the Zend Studio Analyze Code warnings
global $argv;

// Setup the database connection
try
{
	// Setup the mode.
	if (!isset($argv[1])) 
	{
		die('Mode not specified, please specify as first parameter' . PHP_EOL);
	}
	else
	{
		$mode = strtoupper($argv[1]);
	}
	
	if (isset($argv[2]) && preg_match('/\d{4}-\d{2}-\d{2}/', $argv[2]))
	{
		$manual_end_date = $argv[2];
	}
	elseif (isset($argv[2]))
	{
		die('Invalid date specified. Use format YYYY-MM-DD');
	}
	
	$db = Setup_DB::Get_Instance('blackbox', $mode);
} 
catch (MySQL_Exception $e)
{
	die($e->getMessage() . PHP_EOL);
}

$end_date = NULL;
if (isset($manual_end_date)) $end_date = strtotime($manual_end_date);

$datax_fraud = new DataXFraud($db, $end_date);
$datax_fraud->run();
?>
