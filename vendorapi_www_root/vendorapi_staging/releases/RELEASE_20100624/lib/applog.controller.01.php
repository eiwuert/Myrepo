<?php

defined('DIR_LIB') || define ('DIR_LIB', '/virtualhosts/lib/');

require_once(DIR_LIB . 'logsimple.php');
require_once(DIR_LIB . 'applog.1.php');

defined('APPLOGDIR') || define ('APPLOGDIR', '/virtualhosts/log/applog/');
defined('LOG_DEBUG') || define ('LOG_DEBUG', 7);

class Applog_Controller_01
{
	private $applog;
	private $log_dir;
	private $log_dir_full;
	private $log_config;
	private $size_limit;
	private $file_limit;
	private $context;
	private $rotate;
	

	private $dynamically_build_array_data;
	private $config_array_changed;

	// This represents the config file on disk.
	private $config  = array( 'VERSION' => 0, 'ALL' => 'some', 'SCRIPT' => 0, 'FILE' => array (), 'CLASS' => array (), 'METHOD' => array (), 'FUNCTION' => array(), 'SPECIALVALS' => array() );
	
	// This is data stored in this object at runtime.
	private $special = array();
	private $specialActive = false;   // Indicates if this memory object "special" is triggered based on the config "special"
	
	// ******************************************************************************************************************************
	// This section controls whether or not a debugging log message gets written to the log file.
	// ******************************************************************************************************************************
	
	public function __construct($log_dir='all', $size_limit=1000000, $file_limit=5, $context="", $rotate='TRUE', $dynamically_build_array_data = false)
	{
		$this->log_dir		= $log_dir;
		$this->log_dir_full = APPLOGDIR . $log_dir;
		$this->log_config   = $this->log_dir_full . '/log_control.php';
		$this->lock_rsrc	= $this->log_dir_full . '/lockresource';

		$this->applog       = new Applog($log_dir, $size_limit, $file_limit, $context, $rotate);

		if ( !file_exists($this->log_config) ) $this->Create_Control_File();

		$this->dynamically_build_array_data = $dynamically_build_array_data;
		
		$this->Reread_Config_File();

		return true;
	}


	public function __destruct()
	{
		// logsimplewrite( "__destruct: entering method, this->config=" . logsimpledump($this->config) );
	
		if ( $this->dynamically_build_array_data && $this->config_array_changed )
		{
			// I was going to try to prevent two users using the configuration interface from stepping on
			// each other.  Two users could open the same config file in each of their browsers.  One could
			// click update before the other but the second user's update would overlay the first user's.  I
			// don't think this check is worth the trouble so I'm not going to do it anymore.
			// -----------------------------------------------------------------------------------------------
			// Attempting to implement read/update integrity.  This is not perfect but should be close enough
			// to prevent two users with browser sessions from stepping on each other's updates.
			$version = $this->Get_Version_In_File();
			// if ( $version == $this->config['VERSION'] )
			// {
				// logsimplewrite( "Passed version test, UPDATING CONFIG, version=$version, config version=" . $this->config['VERSION'] );
				$this->config['VERSION'] = $version + 1;
				$this->Create_Control_File();
			// }
			// else
			// {
			// 	// logsimplewrite( "FAILED version test, NOT updating config, version=$version, config version=" . $this->config['VERSION'] );
			// }
		}
	}


	public function Get_Config_Array()
	{
		return $this->config;
	}

	
	public function Reread_Config_File()
	{
		include($this->log_config);
		$this->config = $config;
		$this->config_array_changed = false;
		$this->Check_Special_Value();
		return $this->config;
	}
	
	public function Rewrite_Config()
	{
		$this->config['VERSION']++;
		$this->Create_Control_File();
		return $this->Reread_Config_File();
	}


	public function Set_Dynamic_Config_Building( $true_or_false = true )
	{
		if ( $true_or_false ) {
			$this->dynamically_build_array_data = true;
			return;
		}
		$this->dynamically_build_array_data = false;
	}


	public function Write($msg, $level=LOG_DEBUG)
	{
		return $this->applog->Write($msg, $level);
	}


	public function Cout($module, $level, $msg, $old_level=LOG_DEBUG, $already_checked=false)
	{
		// logsimplewrite( __METHOD__ . ": entering, module=$module, level=$level, msg=$msg, old_level=$old_level, already_checked=$already_checked" );

		$result = '';

		if ( $already_checked || $this->Check_Cout( $module, $level ) || $level <= 0 )
		{
			$line = '';
			$file = '';
			
			$bt = debug_backtrace();
			
			if ( is_array($bt) )
			{
				if ( isset($bt[0]['line']) )
				{
					$line = ' line=' . $bt[0]['line'];
				}
				if ( isset($bt[0]['file']) )
				{
					$file = ' file=' . $bt[0]['file'];
				}
			}
	
			if ( !is_numeric($old_level) ) $old_level = LOG_DEBUG;
			if ( $level < 0 && $old_level == LOG_DEBUG ) $old_level = LOG_ERR;  // Negative values force error indicator.  Zero forces output but not error indicator.

			$result = $msg . " (module=$module $file $line)";
			
			$this->applog->Write($result, $old_level);
		}

		return $result;
	}


	public function Cout_Trace($module, $level, $msg, $old_level=LOG_DEBUG, $already_checked=false)
	{
		// logsimplewrite( "Cout: entering method, module=$module, level=$level, msg=$msg, old_level=$old_level, already_checked=$already_checked" );

		$result = '';

		if ( $already_checked || $this->Check_Cout( $module, $level ) || $level <= 0 )
		{
			$line = '';
			$file = '';
			
			$bt = debug_backtrace();
			
			$trace = '';
			if ( is_array($bt) )
			{
				foreach( $bt as $key => $val )
				{
					if ( $trace != '' ) $trace .= ', ';
					if ( isset($bt[$key]['file']) )
					{
						$trace .= ' file=' . $bt[$key]['file'];
					}
					if ( isset($bt[$key]['line']) )
					{
						$trace .= ' line=' . $bt[$key]['line'];
					}
				}
			}
	
			if ( !is_numeric($old_level) ) $old_level = LOG_DEBUG;
			if ( $level < 0 && $old_level == LOG_DEBUG ) $old_level = LOG_ERR;  // Negative values force error indicator.  Zero forces output but not error indicator.

			$result = $msg . " (module=$module $trace)";
			
			$this->applog->Write($result, $old_level);
		}

		return $result;
	}


	// This module simply determines if output will happen or not for a particular module and level.
	public function Check_Cout($module, $level)
	{
		// logsimplewrite( __METHOD__ . ": entering, module=$module, level=$level" );

		if ( $this->dynamically_build_array_data )
		{
			$parsed_module_array = $this->Parse_Module_Text( $module );
			$this->Dynamically_Populate_Config( $parsed_module_array );
		}
		
		if ( $this->config['ALL'] == 'none' ) return false;   // return as fast as possible

		if ( count($this->config['SPECIALVALS']) > 0 )
		{
			if ( $this->specialActive )
			{
				// If the specials array is populated it means ONLY allow log messages that satisfy the "special" filter.
				// If not 'all', then the logging will be controlled through the module/level mechanism.
				if ( $this->config['ALL'] == 'all' ) return true;
			}
			else
			{
				// If the specials array is populated it means ONLY allow log messages that satisfy the "special" filter.
				return false;
			}
		}
		else
		{
			if ( $this->config['ALL'] == 'all' )  return true;
		}
		
		$module = $this->Make_Not_Null($module);
		$level  = $this->Make_Not_Null($level);
		
		if ( !is_numeric($level) ) $level = 0;
		
		if ( $module == '' ) return ($this->config['SCRIPT'] > $level ? true : false);

		if ( !$this->dynamically_build_array_data )
		{
			// no need to parse again if we've already parsed
			$parsed_module_array = $this->Parse_Module_Text( $module );
		}
		
		return $this->Check_Module_Output_Control( $parsed_module_array, $level );
	}


	public function Set_Unknown_Msg_Level( $val )
	{
		if ( !is_numeric($val) ) $val = 0;
		$this->config['SCRIPT'] = $val;
	}


	public function Set_Global_Control( $val )
	{
		if ( strtolower($val) == 'all' )
		{
			$this->config['ALL'] = 'all';
		}
		else if ( strtolower($val) == 'none' )
		{
			$this->config['ALL'] = 'none';
		}
		else
		{
			$this->config['ALL'] = 'some';
		}
	}


	// This function is for building the config data on disk.
	//
	// This function can be used to set or unset "SPECIAL VALUES".
	// A special value is generally something tied to a specific user such
	// as a userid or a session id or an ipaddress.  The purpose is to enable
	// activating and disactivating debug messages for just one specific user.
	public function Set_Special_Vals( $key, $val )
	{
		// logsimplewrite( "Set_Special_Vals: entering, key=$key, val=$val" );
	
		$key = $this->Make_Not_Null($key);
		$val = $this->Make_Not_Null($val);

		if ( $key == '' ) return;

		if ( $val == '' )
		{
			if ( isset( $this->config['SPECIALVALS'][$key] ) )
			{
				$this->config_array_changed = true;   // I really should be using a method for this operation.
				unset( $this->config['SPECIALVALS'][$key] );
			}
		}
		else
		{
			if ( isset( $this->config['SPECIALVALS'][$key] ) )
			{
				if (  $this->config['SPECIALVALS'][$key] != $val )
				{
					$this->config_array_changed = true;   // I really should be using a method for this operation.
					$this->config['SPECIALVALS'][$key] = $val;
				}
			}
			else
			{
				$this->config_array_changed = true;   // I really should be using a method for this operation.
				$this->config['SPECIALVALS'][$key] = $val;
			}
		}
	}


	// This function is for setting the special value of this particular run-time object.
	// When the applog controller is instantiated, userid, ipaddress, sessionid etc can be
	// placed in here.  Then, when it comes time to print a message, this array will be checked
	// against the config array to determine if the message should be printed.
	public function Set_This_Special_Val( $key, $val )
	{
		$key = $this->Make_Not_Null($key);
		$val = $this->Make_Not_Null($val);

		if ( $key == '' ) return;
		
		if ( $val == '' )
		{
			if ( isset( $this->special[$key] ) ) unset( $this->special[$key] );
		}
		else
		{
			$this->special[$key] = $val;
		}

		$this->Check_Special_Value();
	}


	public function Add_File( $mod_name, $mod_level ) {
		$this->Add_Modify_Item( 'FILE', $mod_name, $mod_level );
	}


	public function Add_Class( $mod_name, $mod_level ) {
		$this->Add_Modify_Item( 'CLASS', $mod_name, $mod_level );
	}


	public function Add_Method( $mod_name, $mod_level ) {
		$this->Add_Modify_Item( 'METHOD', $mod_name, $mod_level );
	}


	public function Add_Function( $mod_name, $mod_level ) {
		$this->Add_Modify_Item( 'FUNCTION', $mod_name, $mod_level );
	}


	public function Delete_File( $mod_name ) {
		$this->Delete_Item( 'FILE', $mod_name );
	}


	public function Delete_Class( $mod_name ) {
		$this->Delete_Item( 'CLASS', $mod_name );
	}


	public function Delete_Method( $mod_name ) {
		$this->Delete_Item( 'METHOD', $mod_name );
	}


	public function Delete_Function( $mod_name ) {
		$this->Delete_Item( 'FUNCTION', $mod_name );
	}


	protected function Check_Special_Value()
	{
		// logsimplewrite( "Check_Special_Value: entering, special = " . logsimpledump($this->special, false) . ', config=' . logsimpledump($this->config,false) );
		
		$this->specialActive = false;
	
		foreach( $this->special as $key_this => $val_this )
		{
			// logsimplewrite( "checking: key_this=$key_this, val_this=$val_this" );
			if ( isset( $this->config['SPECIALVALS'][$key_this] ) && $this->config['SPECIALVALS'][$key_this] == $val_this )
			{
				$this->specialActive = true;
			}
		}
	}


	protected function Delete_Item( $mod_category, $mod_name )
	{
		if ( isset($this->config[$mod_category][$mod_name]) )
		{
			unset($this->config[$mod_category][$mod_name]);
		}
	}


	protected function Add_Modify_Item( $mod_category, $mod_name, $level = 0 )
	{
		if ( !is_numeric($level) ) $level = 0;
		
		if ( !isset($this->config[$mod_category][$mod_name]) || $this->config[$mod_category][$mod_name] != $level )
		{
			$this->config_array_changed = true;
			$this->config[$mod_category][$mod_name] = $level;
		}
	}


	protected function Dynamically_Populate_Config( &$parsed_module_array )
	{
		if ( $parsed_module_array['file'] != '' )
		{
			$this->Dynamically_Populate_Config_Part( 'FILE', $parsed_module_array['file'] );
		}
		else if ( $parsed_module_array['class'] != '' && $parsed_module_array['method'] != '' )
		{
			// I think that if class is populated then method MUST be populated and vice-versa.
			$this->Dynamically_Populate_Config_Part( 'CLASS', $parsed_module_array['class'] );
			$this->Dynamically_Populate_Config_Part( 'METHOD', $parsed_module_array['class'] . '::' . $parsed_module_array['method'] );
		}
		else if ( $parsed_module_array['function'] != '' )
		{
			$this->Dynamically_Populate_Config_Part( 'FUNCTION', $parsed_module_array['function'] );
		}
	}


	protected function Dynamically_Populate_Config_Part( $mod_category, $mod_name )
	{
		if ( !isset($this->config[$mod_category][$mod_name]) )
		{
			$this->config_array_changed = true;
			$this->config[$mod_category][$mod_name] = 50; // default to displaying new modules of level 50 or below.  
		}                                                 // This is handy to control the default display of messages when the log control
	}                                                     // file is built for the first time.


	protected function Check_Module_Output_Control( &$parsed_module_array, $level )
	{
		// logsimplewrite( "Check_Module_Output_Control: level=$level, parsed_module_array=" . logsimpledump($parsed_module_array) );
		
		if ( $parsed_module_array['file'] != '' )
		{
			$config_level = $this->Get_Config_Level( 'FILE', $parsed_module_array['file'] );
			// logsimplewrite( "Check_Module_Output_Control: FILE    : config_level=$config_level, level=$level, returning: " . ($config_level >= $level ? 'true' : 'false') );
			if ( $config_level >= $level ) return true;
		}
		
		if ( $parsed_module_array['class'] != '' || $parsed_module_array['method'] != '' )
		{
			$config_level = $this->Get_Config_Level( 'CLASS', $parsed_module_array['class'] );
			// logsimplewrite( "Check_Module_Output_Control: CLASS   : config_level=$config_level, level=$level, returning: " . ($config_level >= $level ? 'true' : 'false') );
			if ( $config_level >= $level ) return true;
			
			$config_level = $this->Get_Config_Level( 'METHOD', $parsed_module_array['class'] . '::' . $parsed_module_array['method'] );
			// logsimplewrite( "Check_Module_Output_Control: METHOD  : config_level=$config_level, level=$level, returning: " . ($config_level >= $level ? 'true' : 'false') );
			if ( $config_level >= $level ) return true;
		}

		if ( $parsed_module_array['function'] != '' )
		{
			$config_level = $this->Get_Config_Level( 'FUNCTION', $parsed_module_array['function'] );
			// logsimplewrite( "Check_Module_Output_Control: FUNCTION: config_level=$config_level, level=$level, returning: " . ($config_level >= $level ? 'true' : 'false') );
			if ( $config_level >= $level ) return true;
		}

		return false;
	}


	protected function Get_Config_Level( $config_array_name, $module_value )
	{
		if ( isset( $this->config[$config_array_name][$module_value] ) )
		{
			return $this->config[$config_array_name][$module_value];
		}

		return 0;
	}


	protected function Parse_Module_Text( &$module )
	{
		$result_array = array( 'file' => '', 'class' => '', 'method' => '', 'function' => '' );
		if ( $module == '' ) return $result_array;
		
		$first_char = substr( $module, 0, 1 );
		if ( $first_char == '/' )
		{
			$result_array['file'] = $module;
			return $result_array;
		}

		$len = strlen( $module );

		$pos_colon_colon = strpos( $module, '::' );
		if ( $pos_colon_colon === false )
		{
			$result_array['function'] = $module;
			return $result_array;
		}
		
		$result_array['class'] = substr( $module, 0, $pos_colon_colon );
		$result_array['method'] = substr( $module, $pos_colon_colon + 2 );
		return $result_array;
	}


	protected function Create_Control_File()
	{
		$file_contents = $this->Get_Var_Export($this->config, false);

		$file_comment = " // possible values for ALL = all|some|none \r\n";
		
		$lock_file_handle = fopen($this->lock_rsrc, 'w');
		if ($lock_file_handle === false)
		{
			$this->applog->Write('Unable to access locking resource: ' . $this->lock_rsrc);
			return false;
		}
		
		$exclusive_lock = flock($lock_file_handle, LOCK_EX);
		if ( !$exclusive_lock )
		{
			flock($lock_file_handle, LOCK_UN);
			$this->applog->Write('Unable to get exclusive lock on locking resource: ' . $this->lock_rsrc);
			return false;
		}

		$fp = fopen( $this->log_config, 'w' );
		if ( $fp )
		{
			fwrite($fp, '<' . "?\r\n$file_comment" . ' $config = ' . $file_contents . ";\r\n?" . '>');
			fclose($fp);
			// if I don't unflock before writing to applog I get a deadlock since I'm using the same lock resource as applog.
			flock($lock_file_handle, LOCK_UN);
			// $this->applog->Write('log control file created');
		}
		else
		{
			// if I don't unflock before writing to applog I get a deadlock since I'm using the same lock resource as applog.
			flock($lock_file_handle, LOCK_UN);
			$this->applog->Write('Unable to open log control file');
		}

	}


	protected function Get_Version_In_File()
	{
		include($this->log_config);
		return $config['VERSION'];
	}


	protected function Get_Var_Export( $var, $stripnewlines=true ) {
		ob_start();
		var_export( $var );
		$output = ob_get_contents();
		ob_end_clean();
		if ( $stripnewlines ) $output = preg_replace( '/(\s)+/', ' ', $output );
		return $output;
	}


	public function Print_Var( $var, $stripnewlines=true ) {
		ob_start();
		print_r( $var );
		$output = ob_get_contents();
		ob_end_clean();
		if ( $stripnewlines ) $output = preg_replace( '/(\s)+/', ' ', $output );
		return $output;
	}


	protected function Make_Not_Null( $s )
	{
		return isset($s) ? trim($s) : '';
	}
	

	// ******************************************************************************************************************************
	// This section will be the browser interface for configuring debug logging control.
	// ******************************************************************************************************************************
	
}

?>
