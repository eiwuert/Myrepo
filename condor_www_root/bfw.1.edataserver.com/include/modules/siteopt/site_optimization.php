<?php
ini_set('include_path', '.:/virtualhosts:'.ini_get('include_path'));
require_once('mysql.4.php');
require_once('../include/code/server.php');
require_once('../include/code/SessionHandler.php');


define('DATABASE_BASE_OLP', 'olp');

// Set the default session lifetime to 15 days
ini_set('session.gc_maxlifetime', 1296000);

/**
 * The Site Optimization class handles determining which landing page to use for a site where
 * multiple landing pages exist.
 * 
 * @author Brian Feaver
 */
class Site_Optimization
{
	private $sql;
	private $db_info;
	private $olp_info;
	
	public function __construct($mode)
	{
		// Setup MySQL connection
		$this->db_info = Server::Get_Server($mode, 'MANAGEMENT');
		$this->olp_info = Server::Get_Server($mode, 'BLACKBOX');
		$this->sql = new MySQL_4(
			$this->db_info['host'],
			$this->db_info['user'],
			$this->db_info['password']);
		$this->sql->Connect();
	}
	
	/**
	 * Randomly picks a landing page to display for the site and returns the page ID
	 * of the landing page or 'site' if the original site should be used. Will return
	 * boolean FALSE on error.
	 *
	 * @param string $license_key The site license key
	 * @param string $session_id Customer's session unique_id
	 * @param string $force_page Landing page forced to use
	 * @return mixed Page id of the config/site to use, or 'site' if the original site
	 */
	public function Landing_Page($license_key, $session_id, $force_page = '')
	{
		$ret_val = FALSE;
		
		$session = new SessionHandler(
			$this->sql,
			$this->olp_info['db'],
			'session',
			$session_id,
			'ssid',
			'gz',
			TRUE
		);
		
		if(!empty($force_page))
		{
			$_SESSION['site_opt_page'] = $force_page;
			$ret_val = $force_page;
		}
		elseif(isset($_SESSION['site_opt_page']))
		{
			$ret_val = $_SESSION['site_opt_page'];
		}
		elseif(is_string($license_key) && is_string($session_id))
		{
			$landing_pages = array();
			$page_id = 0;
			
			if(get_magic_quotes_gpc())
			{
				stripslashes($license_key);
			}
			
			try
			{
				$result = $this->sql->Query(
					$this->db_info['db'], "
					SELECT run_state, page_id FROM license_map
					WHERE license = '".mysql_real_escape_string($license_key)."'"
				);
				
				if(($row = $this->sql->Fetch_Object_Row($result)))
				{
					$run_state = unserialize($row->run_state);
					$page_id = intval($row->page_id);
					
					if(is_array($run_state->landing_pages))
					{
						$landing_pages = $run_state->landing_pages;
					}
				}
			}
			catch(Exception $e)
			{
				$ret_val = false;
			}
			
			if(!empty($landing_pages))
			{
				$count = 0;
				$random = rand(0, 100);
				
				if(isset($landing_pages['time_zone']))
				{
					$tz = $landing_pages['hour'][date("H")-1];
					foreach($landing_pages['time_zone'][$tz] as $tpage_id => $weight)
					{
						$count += $weight;
						if($random <= $count)
						{
							$ret_val = ($page_id != $tpage_id) ? $tpage_id : 'site';
							break;
						}
					}
				}
				else
				{
					foreach($landing_pages as $page)
					{
						$count += $page->weight;
						if($random <= $count && !$ret_val)
						{
							$ret_val = ($page_id != $page->page_id) ? $page->page_id : 'site';
						}
					}
				}
			}
			
			if($ret_val)
			{
				if($page_id == $ret_val) $ret_val = 'site';
				
				// If we got a landing page, set the session, otherwise set it to the default
				$_SESSION['site_opt_page'] = $ret_val ? $ret_val : 'site';
			}
		}
		
		return $ret_val;
	}
}
?>
