<?php

require_once('statpro_client.php');
require_once('lib_mode.2.php');

/**
 * Replacement for Set_Stat_2
 *
 * This isn't a rewrite, so much as it is a gutting and replacement
 * to remove old stats completely.
 *
 * @author John Hargrove <john.hargrove@sellingsource.com>
 *
 */
class Set_Stat_3
{
	/**
	 * @var array
	 */
	protected static $mode_lookup = array(
		MODE_LIVE => "live",
		MODE_RC  => "test",
		MODE_UNKNOWN => "test",
		MODE_LOCAL => "test"
	);

	/**
	 * @var string
	 */
	protected $mode;

	/**
	 * If mode is a string, 'live' for live, anything else for testing.
	 * If mode is an integer, it is assumed to be one of the mode constants from
	 * Lib_Mode, or Lib_Mode_2
	 *
	 * @param mixed $mode
	 */
	public function Set_Mode($mode)
	{
		if (is_string($mode))
		{
			switch (strtolower($mode))
			{
				case "live":
					$mode = MODE_LIVE;
					break;

				case "rc":
				case "local":
				case "dev":
				default:
					$mode = MODE_RC;
					break;
			}
		}
		else if (!is_int($mode) && !array_key_exists($mode, self::$mode_lookup))
		{
			throw new Exception("Mode not recognized.");
		}

		$this->mode = $mode;
	}

	/**
	 * Hits the stat specified by $event_key, $count times.
	 * $property_id is the integer for the property you're hitting the stat for.
	 * Uses stat time in session unless otherwise specified.
	 * If mode is a string, 'live' for live, anything else for testing.
	 * If mode is null, will use Lib_Mode to determine the operating mode.
	 * If mode is an integer, it assumed to be one of the mode constants from
	 * this class.
	 *
	 * @param mixed $mode Operating mode
	 * @param int $property_id Property ID to hit the stat for
	 * @param string $event_key Event key to hit the stat for
	 * @param int $count Number of times to hit the stat
	 * @param int $date_occurred Time stat occurred, null = now
	 */
	public function Set_Stat($property_id, $event_key, $count = 1, $date_occurred = null)
	{
		if ($this->mode === null)
		{
			throw new Exception("Mode not set. Call Set_Stat_3::Set_Mode() First!");
		}

		$statpro_auth = $this->getStatProAuthentication($property_id);

		if ($date_occurred === NULL && isset($_SESSION['stat_info']->stat_time))
		{
			$date_occurred = $_SESSION['stat_info']->stat_time;
		}

		$count = (int)$count;

		while ($count > 0)
		{
			/**
			 * The output buffering is for the CLI statpro stuff.
			 * I'll be sure to mention it at my next confession.
			 */
			ob_start();
				$statpro = new StatPro_Client(
					'/opt/statpro/bin/spc_' . $statpro_auth['key'] . '_' . self::$mode_lookup[$this->mode],
					'-v',
					$statpro_auth['key'],
					$statpro_auth['pass']
				);

				// Hit the stat
				$statpro->Record_Event($event_key, $date_occurred);

				// For some reason this is shoved in the session
				$_SESSION['statpro']['statpro_obj'] = $statpro;
			ob_end_clean();

			$count--;
		}
	}

	/**
	 * Given a property id, determine which statpro login to use
	 *
	 * @param int $property_id
	 * @return array
	 */
	protected function getStatProAuthentication($property_id)
	{
		switch ($property_id)
		{
			case 9278:
				$statpro_key = 'equityone';
				$statpro_pass = '3337b7d5b3321b075c8582540';
				break;

			case 34676:
				$statpro_key = 'emv';
				$statpro_pass = 'a51a5c87c5f2c030de8dee2da';
				break;

			case 28400:
				$statpro_key = 'leadgen';
				$statpro_pass = '04b650f6350a863089a015164';
				break;

			case 4967:
				$statpro_key = 'ge';
				$statpro_pass = '3818ca3aab5960549fb32d4c5';
				break;

			case 35459:
				// For LeadGen partner=PW, DO NOT RECORD STATS ON NEW MODEL
				$statpro_key = 'pwsites';
				$statpro_pass = 'bfa657d3633';
				break;

			case 55459:
				$statpro_key = 'smt';
				$statpro_pass = 'moosow1U';
				break;

			case 1571: // Express Gold Card
			case 44024: //Cubis Financial Cards
				$statpro_key = 'cubis';
				$statpro_pass = 'FtT7CYMFMyrC0';
				break;

			case 48204: // Impact
			case 48206: // Impact
			case 61229: // IFS
			case 61532: // IPDL
			case 61529: // ICF
			case 65388: // IIC
				$statpro_key = 'imp';
				$statpro_pass = 'h0l3iny0urp4nts';
				break;
			case 31631:
			case 3018:
			case 9751:
			case 1583:
			case 1581:
			case 1579:
			case 1720:
			case 17208:
			case 10985:
				$statpro_key = 'clk';
				$statpro_pass = 'dfbb7d578d6ca1c136304c845';
				break;

			case 57178: // Agean
				$statpro_key = 'agean';
				$statpro_pass = 'ohChua3t';
				break;
				
			case 60883: 
				$statpro_key = 'generic';
				$statpro_pass = 'password';
				break;				

			case 57458:
				$statpro_key = 'ocp';
				$statpro_pass = 'raic9Cei';
				break;

			/** Added new Enterprise Client LCS for GForge #9888 [AE]**/
			case 64656:
				$statpro_key = 'lcs';
				$statpro_pass = 'F7eu5Kr1';
				break;
			
			/** Added new Enterprise Client QEasy for GForge #10313 [AE]**/
			case 64797:
				$statpro_key = 'qeasy';
				$statpro_pass = '37ez2b9';
				break;
			
			case 66962:
			case 66965:
			case 66968:
			case 66971:
			case 66974:
			case 66977:
			case 66980:
			case 66983:
				$statpro_key = 'hms';
				$statpro_pass = 'hd73hf9j3kc';
				break;
				
			default:
				$statpro_key = 'catch';//'catch_all';
				$statpro_pass = 'bd27d44eb515d550d43150b9b';
				break;
		}

		return array('key' => $statpro_key,  'pass' => $statpro_pass);
	}

	/**
	 * Returns an object containing various stat metrics
	 *
	 * @param int $date
	 * @param int $site_id
	 * @param int $vendor_id
	 * @param int $page_id
	 * @param int $promo_id
	 * @param int $promo_sub_code
	 * @param int $promo_status
	 * @param int $batch_id
	 * @return stdClass
	 */
	public static function Setup_Stats ($date, $site_id, $vendor_id, $page_id, $promo_id, $promo_sub_code, $promo_status, $batch_id = NULL)
	{
		if (is_null($date))
		{
			$time = time();
			$date = date('Y-m-d', $time);
		}
		elseif (is_numeric($date))	// date is a unix timestamp
		{
			$time = $date;
			$date = date('Y-m-d', $time);
		}
		else
		{
			$time = NULL;
		}

		// Prep the return values
		$stat_info = new stdClass ();
		$stat_info->stat_date = $date;
		$stat_info->stat_time = $time;

		// Return our arguments so the session can re-call us with the same args when the date changes
		$stat_info->cache = new stdClass();
		$stat_info->cache->site_id = $site_id;
		$stat_info->cache->vendor_id = $vendor_id;
		$stat_info->cache->page_id = $page_id;
		$stat_info->cache->promo_id = $promo_id;
		$stat_info->cache->promo_sub_code = $promo_sub_code;
		$stat_info->cache->batch_id = $batch_id;

		return $stat_info;
	}
}
?>
