<?php
/**
    @publicsection
    @brief  Base Frame Work class handles configuration, sessions, and includes modules

    The Base Frame Work class handles the configuration, sessions, and includes
    the module config based on the site configuration.

 	@author Nick White, Jason Gabriele <jason.gabriele@sellingsource.com>

	@version
			1.0.0 2004-09-20 -
				- A class file to handle base frame work for sites
            1.1.0 2006-03-27
                - Updated to use session for storage of config data [JAG]
*/

// Setting this here, since sessions are setup here. Max lifetime on sessions is 15 days.
ini_set('session.gc_maxlifetime', 1296000);
require_once('config.6.php');
require_once('session.8.php');
require_once('security.3.php');
require_once('security.4.php');
require_once('mysql.4.php');
require_once(BFW_CODE_DIR.'OLP_Applog_Singleton.php');
require_once(BFW_CODE_DIR.'Cache_Site_Type_Manager.php');
require_once(BFW_CODE_DIR.'server.php');
require_once(BFW_CODE_DIR.'setup_db.php');
require_once(BFW_CODE_DIR.'Cache_Config.php');
require_once(BFW_CODE_DIR.'SessionHandler.php');
require_once(BFW_CODE_DIR.'SiteConfig.php');
require_once('setstat.3.php');

class Base_Frame_Work
{
    public $module = null;

	private $promo_override = false; // bool saying whether to over ride the default campaign already in session

    private $mode = null;

    private $license_key = null;

    private $collected_data = null;

    private $site_type = null;

    private $session_id = null;

    private $session = null;

    private $module_path = null;

	/**
      @publicsection
      @fn void __construct($license_key,$collected_data,$mode,$site_type,$session_id)
	  @return void
	  @param $license_key string
	  @param $collected_data array
	  @param $mode string
	  @param $site_type string
      @param $session_id string
	*/
	function __construct($license_key, $collected_data, $mode, $site_type, $session_id)
	{
		// Set the mode
		$this->mode = $mode;

		// set the site_type
		$this->site_type = $site_type;

        // Set the Module and Include its configuration
        $this->module_path = BFW_USE_MODULE;
        $this->Include_Module_Config();

		// should we override the current promotion
		$this->promo_override = isset($collected_data["promo_override"]) ? TRUE : FALSE;

		// If theres an old school ref_id, use that, otherwise check for a promo_id or use 10000 default if none available.
		if (isset($collected_data["ref"]) && strlen($collected_data ["ref"]) )
		{
			$promo_id = $collected_data ["ref"];
		}
		else
		{
			$promo_id = (isset($collected_data["promo_id"]) && strlen($collected_data["promo_id"]) ? $collected_data ["promo_id"] : "10000");
		}

		// Set promo_sub_code from gets or PID (commission junction) or posts
		if ( isset($collected_data["PID"]) && strlen($collected_data ["PID"]))
		{
			$promo_sub_code = $collected_data["PID"];
		}
		else
		{
			$promo_sub_code = (isset($collected_data["promo_sub_code"]) && strlen($collected_data["promo_sub_code"]) ? $collected_data ["promo_sub_code"] : "");
		}

		// Setup config
        $this->license_key = $license_key;
        $this->promo_id = $promo_id;
        $this->promo_sub_code = $promo_sub_code;
        $this->collected_data = $collected_data;

        //Set Defined Key
        define("BFW_ORIGINAL_KEY",$license_key);
        define("BFW_ORIGINAL_PROMO",$promo_id);
        define("BFW_ORIGINAL_SUB_CODE", $promo_sub_code);

        // If we don't get a session id we need to create one.
        $this->session_id = ($session_id!=NULL && $session_id != "") ? $session_id : $this->Create_Session_Id();

        $this->Setup_Site();
	}

	/**
      @publicsection
      @brief
        Sets up the session and retrieves or stores the config

      This method sets up the session and stores or retrieves the config based on
      whether the config is already stored in the session.
      @fn boolean Setup_Site()
	  @return boolean True on success
	*/
	private function Setup_Site()
	{
		try
		{
			// setup mysql obj
			$this->sql = Setup_DB::Get_Instance(BASE_DB, $this->mode);
		}
		catch ( MySQL_Exception $e )
		{
			throw $e;
		}

		// Setup session and stats
		$this->Setup_Session();

		// retreive site_type_obj
		// check for site_type_obj before we grab it
		if($this->site_type && !is_object(SiteConfig::getInstance()->site_type_obj))
		{
			// set the site type
			SiteConfig::getInstance()->site_type = $this->site_type;

			try
			{
				// make a new connect
				$site_type_sql = Setup_DB::Get_Instance("site_types", $this->mode);

				// get the site type
				$st_object = new Cache_Site_Type_Manager( $site_type_sql, $site_type_sql->db_info["db"] );

				SiteConfig::getInstance()->site_type_obj = $st_object->Get_Site_Type($this->site_type);

                //Resync config and SESSION["config"]
                $_SESSION["config"] = SiteConfig::getInstance()->asObject();
			}
			catch ( MySQL_Exception $e )
			{
				throw $e;
			}
		}

		// Setup Site Modules
		$this->Setup_Module();

		return true;
	}

    /**
     @publicsection
     @brief
        Set's up the config for the bfw instance

     This method sets up the config for this bfw instance

     @return boolean
    */
    private function Setup_Config()
    {
		$sql = Setup_DB::Get_Instance("management", $this->mode);
		$config_obj = new Cache_Config($sql);
		
		try
		{
			$config = $config_obj->Get_Site_Config(
				$this->license_key,
				$this->promo_id,
				$this->promo_sub_code
			);
			SiteConfig::fromObject($config);
		    
			if (isset(SiteConfig::getInstance()->event_pixel))
			{
				$event_pixel = SiteConfig::getInstance()->event_pixel;
				unset(SiteConfig::getInstance()->event_pixel);
				SiteConfig::getInstance()->event_pixel = array_change_key_case($event_pixel, CASE_LOWER);
			}
		}
		catch (Exception $e)
		{
		    throw $e;
		}
		
		//Make sure mode is same as mode in license
		if ((strtoupper(SiteConfig::getInstance()->mode) == "LIVE" && strtoupper($this->mode) == "RC") ||
		   (strtoupper(SiteConfig::getInstance()->mode) == "RC" && strtoupper($this->mode) == "LIVE"))
		{
		    throw new Exception("License mode does not match config mode");
		}
    }

	/**
      @publicsection
      @brief
        Starts a session for the Base_Frame_Work
      Call this method to start a session for the base_frame_work

      @fn boolean Setup_Session()
      @return boolean True on success
	*/
	private function Setup_Session()
	{
        if ($this->session == null)
        {
    		$session_sql['session'] = $this->sql;
    		$session_sql['stats']	= Setup_DB::Get_Instance(STAT_DB, $this->mode);

            if (MYSQL4_LOG)
            {
                $tempMTime = $_SESSION["mysql4_timer"];
                $tempMQueries = $_SESSION["mysql4_query_count"];
                $_SESSION["mysql4_timer"] = (float)0;
                $_SESSION["mysql4_query_count"] = (float)0;
            }

            if (STATPRO_LOG)
            {
                $tempPTime = $_SESSION["statpro_timer"];
                $_SESSION["statpro_timer"] = (float)0;
            }

    		$this->session = new SessionHandler(
    			$session_sql,
    			$this->sql->db_info["db"],
    			'session',
    			$this->session_id,
    			'ssid',
    			'gz',
    			TRUE
    		);
			$this->session->Maximum_Size(MAX_SESSION_SIZE);

            if (MYSQL4_LOG)
            {
                $_SESSION["mysql4_timer"] = (float)$tempMTime;
                $_SESSION["mysql4_query_count"] = (float)$tempMQueries;
            }

            if (STATPRO_LOG)
            {
                $_SESSION["statpro_timer"] = (float)$tempPTime;
            }

    		$this->session->Enable_Pixel_Handler();

            if (isset($_SESSION["config"]) && !$this->promo_override)
            {
                //Load config from session if exists
                SiteConfig::fromObject($_SESSION["config"]);
            }
            else
            {
                //Load config from db
                $this->Setup_Config();

                // Put config into session
                $_SESSION["config"] = SiteConfig::getInstance()->asObject();

                try
                {
                    $_SESSION["stat_info"] = Set_Stat_3::Setup_Stats(NULL,
                    												 $_SESSION["config"]->site_id,
                                                                     $_SESSION["config"]->vendor_id,
                                                                     $_SESSION["config"]->page_id,
                                                                     $_SESSION["config"]->promo_id,
                                                                     $_SESSION["config"]->promo_sub_code,
                                                                     $_SESSION["config"]->promo_status);
                }
                catch( Exception $e )
                {
                    // we are going to do nothing
                    // let the user continue
                }

                // make sure we have unique stats
                if ( !isset($_SESSION["unique_stat"]) )
                {
                    $_SESSION["unique_stat"] = new stdClass ();
                }

                // set promo_override flag in session
                if( $this->promo_override )
                {
                    // hack for stat pro
                    $_SESSION["statpro"]["promo_override"] = TRUE;

                    $_SESSION["promo_override"] = TRUE;
                }
            }

    		$_SESSION["security"] = new Security_3($this->sql, SiteConfig::getInstance()->site_base, 'account');
        }

        return true;
	}

    /**
      @publicsection
      @brief
        Includes Modules (if any)

      This method will include a module's config if one is specified in the site's config
      @fn boolean Include_Module_Config()
      @return boolean
     */
    private function Include_Module_Config()
    {
        if($this->module_path != NULL)
        {
            if (file_exists(BFW_MODULE_DIR . $this->module_path . '/config.php'))
            {
                include_once(BFW_MODULE_DIR . $this->module_path . '/config.php');
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return true;
        }
    }

    /**
      @publicsection
      @brief
        Sets up a module

      Sets up the module that is specified in the config
      @fn boolean Setup_Module()
      @return boolean True on success
     */
	private function Setup_Module()
	{
        if (file_exists(BFW_MODULE_DIR . $this->module_path . '/' . $this->module_path . '.php'))
        {
            include_once(BFW_MODULE_DIR . $this->module_path . '/' . $this->module_path . '.php');

			$this->module = new $this->module_path(
				$this->session,
				$this->sql,
				$this->sql->db_info["db"],
				SiteConfig::getInstance()->asObject()
			);

            return true;
        }
        else
        {
            return false;
        }
	}

	/**
	  @publicsection
      @brief
        Creates a session id

      Creates a session id
	  @fn void Create_Session_Id()
      @return string session id
	*/
	private function Create_Session_Id()
	{
		return md5(microtime());
	}

	/**
	 * Mantis #8361: Disable Accepting Leads from a Promo ID based on Sub Promo [DY]
	 *
	 * @param string $promo_sub_code Promo sub code.
	 * @param string|array $block_sub_codes A list of blocked sub codes.
	 * @return boolean true if $promo_sub_code is in $block_sub_codes; otherwise, false.
	 */
	function In_Blocked_Sub_Codes($promo_sub_code = NULL, $block_sub_codes = NULL) {
		if (!$promo_sub_code)
			$promo_sub_code  = trim(SiteConfig::getInstance()->promo_sub_code);

		if (!$block_sub_codes)
			$block_sub_codes = SiteConfig::getInstance()->block_sub_codes;

		if ($promo_sub_code && $block_sub_codes) {
			if (is_array($block_sub_codes)) { // $block_sub_codes is in ARRAY format
				array_walk($block_sub_codes, create_function('&$val','$val=strtolower(trim($val));'));
				if (in_array(strtolower($promo_sub_code), $block_sub_codes)) {
					return TRUE;
				}
			} else { // $block_sub_codes is in STRING format
				if (strpos($block_sub_codes, ',') !== FALSE) {
					$block_sub_codes = explode(',', $block_sub_codes);
					return $this->In_Blocked_Sub_Codes($promo_sub_code, $block_sub_codes);
				} else if (strcasecmp($promo_sub_code, trim($block_sub_codes)) == 0) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	//Added for mantis #8617 [MJ]
	public function inTimeCap ()
	{
		//Localize timespan array
		$time_cap = SiteConfig::getInstance()->time_cap;

		//Time cap is not set for unconfigured promos.
		if (!is_array($time_cap))
		{
			return TRUE;
		}

		//Get current day and time
		$cur_week_day = date('w');
		$cur_time = date('G');

		//end is currently set 1 too far so we subtract 1. (For 1am->2am start:1 end:3)
		if ($time_cap[$cur_week_day]['start'] == $time_cap[$cur_week_day]['end']-1)
		{
			//Times are the same, no time is allowed during this day
			$start_time_ok = $end_time_ok = FALSE;
		}
		else 
		{
			//Check to see if the time is in range
			$start_time_ok = ($time_cap[$cur_week_day]['start'] <= $cur_time)? TRUE : FALSE;
			$end_time_ok = ($time_cap[$cur_week_day]['end']-1 > $cur_time)? TRUE : FALSE;
		}
		
		//If times are ok we do nothing.
		// Mantis #13307 - Added in the case for if they are logged into the CS site, 
		// because if they are we shouldn't care about timecaps	[RV]
		if (($start_time_ok && $end_time_ok) || $_SESSION['cs']['logged_in'])
		{//Times are NOT ok
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}
?>
