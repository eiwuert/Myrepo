<?PHP 
/** 			
	@version:
			1.0.0 2004-12-06 - StatPro Extension
				
	@author:	
			Nick White - version 1.0.0
				
	@Updates:	
	
	@Todo:
			Need to add the database insertion calls.
*/

// which version of the client do we use???
if( phpversion() >= 5 )
{
	require_once("prpc2/client.php");
}
else
{
	require_once ('prpc2/client.php');
}

require_once ('error.2.php');
require_once ('applog.1.php');
include_once ("lib_mode.1.php");

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

class StatPro_Ext
{
	var $statpro;
	var $enterprisepro;
	
	/**
	* @return bool
	* @param $db2 obj
	* @param $sp_table string
	* @desc Constructor, setup the initial prpc calls and determines server connection
	* @desc based on session data.
 	*/
	function StatPro_Ext($customer_key = NULL, $customer_pass = NULL)
	{ 
		// If we don't have a mode in the session, get it
		if ( !$_SESSION['config']->mode )
		{
			switch (Lib_Mode::Get_Mode())
			{
				case 1:
				$_SESSION['config']->mode = "LOCAL";
				break;
				
				case 2:
				$_SESSION['config']->mode = "RC";
				break;
				
				case 3:
				$_SESSION['config']->mode = "LIVE";
				return FALSE;
				break;
			}
		}
		
		// If this is an RC site,.. append "rc." to the server call
		preg_match('/^rc/i', $_SESSION['config']->mode) ? $mode = "test" : $mode = "live";
		$extension=(strtoupper($_SESSION['config']->mode)=='LOCAL'?'tss':'net');
		
		if( $extension == "tss" )
		{
			$mode = "test";
		}
			
		$sp_url = "1-1-{$mode}.statpro.epointps.{$extension}";
		$ep_url = "1-1-{$mode}.enterprisepro.epointps.{$extension}";
		
		// Connect to StatPro
		$this->statpro = new Prpc_Client2("prpc://$sp_url/");
		$this->enterprisepro = new Prpc_Client2("prpc://$ep_url/");
		//$this->statpro = new Prpc_Client2("prpc://{$mode}statpro.epointps.".$extension."/");
		//$this->enterprisepro = new Prpc_Client2("prpc://{$mode}enterprisepro.epointps.".$extension."/");
		
		// don't die
		$this->statpro->setPrpcDieToFalse();
		$this->enterprisepro->setPrpcDieToFalse();
		
		$this->Setup($customer_key, $customer_pass);
		
		return TRUE;
		
	}
	
	function Setup($ck,$cp)
	{
		// create session values
		$session_args = array($ck,$cp);
		
		// Create space key values, get space key requires and array not elements so this is an array of array.
		$space_args = 
			array(
				'page_id'=>$_SESSION['config']->page_id,
				'promo_id'=>$_SESSION['config']->promo_id,
				'promo_sub_code'=>$_SESSION['config']->promo_sub_code);

	 	// If the needed elements do not exist, then create them and apply to session as needed.
	 	// promo_override is for teleweb hot swaps
		if( !$this->Space_Exist() || $_SESSION["statpro"]["promo_override"] == TRUE )
		{
			// save the old info
			if( isset($_SESSION["statpro"]["space_key"]) )
				$_SESSION["statpro"]["space_key"] = $_SESSION["statpro"]["space_key"];
				
			$_SESSION['statpro']['space_key'] = $this->Call('Get_Space_Key',$space_args);
			
			// don't run the promo override code again
			isset($_SESSION["statpro"]["space_key"]) ? $_SESSION["statpro"]["promo_override"] = FALSE : NULL;
		}
		
		/*
		 * DO WE REALLY NEED THIS
		 * i think when we call return_glob that will overwrite our current space key in our sesion
		 * with our new space key.
		 *
		 * According to Paul this will be taken care of when we run Return_Glob with our new space key
		 *
		if( $_SESSION["statpro"]["promo_override"] == TRUE )
		{
			$this->Call("Associate_Space", array($_SESSION["statpro"]["session_key"],$_SESSION["statpro"]["space_key"]));
			$_SESSION["statpro"]["promo_override"] = FALSE;
		}
		*/
		
		if ( !$this->Track_Exist() ) 
		{
			$global_key = isset($_SESSION['data']['global_key'])?$_SESSION['data']['global_key']:NULL;
			$start_time = microtime_float();
			$ret = $this->statpro->Start_Glob($ck, $cp, $_SESSION["statpro"]["space_key"], $global_key, NULL);
			$end_time = microtime_float();
			
			$this->start_glob_time = $end_time - $start_time;
			
			if( is_array($ret) )
			{
				// now we loose our old data in the override situation :(
				$_SESSION['statpro'] = array_merge($_SESSION['statpro'], $ret);
			}// Check for errors
			elseif(is_object($ret) && get_class($ret) == 'error_2')
			{
				// store error in the session
				$_SESSION["statpro"]["sg_error"]["args"] = "Start_Glob(".$ck.",".$cp.",".$_SESSION["statpro"]["space_key"].",".$global_key.",NULL)";
				$_SESSION["statpro"]["sg_error"]["ret"] = $ret;
				
				ob_start();
				print_r($ret);
				$error_data = ob_get_clean();
				ob_end_clean();
				
				$app_log = new Applog();
				$app_log->Write("----START----2");
				$app_log->Write($error_data);
				$app_log->Write("Session ID: ".session_id());
				$app_log->Write("Start_Glob(".$ck.",".$cp.",".$_SESSION["statpro"]["space_key"].",".$global_key.",NULL)");
				$app_log->Write("Call('Get_Space_Key',".print_r($space_args,true).")"); 
				$app_log->Write("----END-----2\n\n");
			}
			
			$_SESSION['data']['global_key'] = $_SESSION['statpro']['global_key'];
		} 
		elseif ( $this->Track_Exist() && !$this->Session_Exist())
		{	
		    $start_time = microtime_float();
			$ret = $this->statpro->Return_Glob($ck, $cp, $_SESSION["statpro"]["space_key"], $_SESSION["statpro"]["track_key"]);
			$end_time = microtime_float();
			
			$this->return_glob_time = $end_time - $start_time;

			if( is_string($ret) )
			{
				// now we loose our old data in the override situation :(
				$_SESSION['statpro']["session_key"] = $ret;
			}// Check for errors
			elseif(is_object($ret) && get_class($ret) == 'error_2')
			{
				// store error in the session
				$_SESSION["statpro"]["sg_error"]["args"] = "Return_Glob(".$ck.",".$cp.",".$_SESSION["statpro"]["space_key"].",".$_SESSION["statpro"]["track_key"].",NULL)";
				$_SESSION["statpro"]["sg_error"]["ret"] = $ret;
				
				ob_start();
				print_r($ret);
				$error_data = ob_get_clean();
				ob_end_clean();
	
				$app_log = new Applog();
				
				$app_log->Write("----START----2");
				$app_log->Write($error_data);
				$app_log->Write("Session ID: ".session_id());
				$app_log->Write("Return_Glob(".$ck.",".$cp.",".$_SESSION["statpro"]["space_key"].",".$_SESSION["statpro"]["track_key"].")");
				$app_log->Write("Call('Get_Space_Key',".print_r($space_args,true).")");
				$app_log->Write("----END-----2\n\n");

			}
				
		}
		// Uncomment to turn on statpro_timer log
		/*
		$time_log = new Applog('statpro_timer');
		$time_log->Write($this->get_space_key_time.",".$this->start_glob_time.",".$this->return_glob_time);
		/* */
		
		return TRUE;
	}
	
	/**
	* @return obj/array/int/string
	* @param $function string
	* @param $args array
	* @desc Allows statpro calls through this extension class and return the response
	*/
	function Call($function, $args = array())
	{
		//$result = $this->statpro->$function($args);
		//$result = call_user_func_array (array($this->statpro, $function), $args);
		//$this->statpro->__call($function, $args, $result);
		
		// This is really lame,.. I need to get with Rodric to get one of the above codes to work, which is
		// much cleaner than the below switch.
		

		switch($function)
		{
			case "Create_Session":
			case "Create_Track":
			case "Associate_Consumer":
			case "Associate_Space":
			case "Associate_Track":
			$result = $this->statpro->$function($args[0],$args[1]);
			break;
			case "Record_Event":
			$result = $this->statpro->$function($args[0],$this->Map_Column($args[1]));
			//echo "<pre>(";print_r($args);echo")".$function;print_r($result);exit;
			break;
			
			case "Get_Space_Key":
			$start_time = microtime_float();
			$result = $this->enterprisepro->$function($args);
			$end_time = microtime_float();
			
			$this->get_space_key_time = $end_time - $start_time;
			break;
		}
		
		// Check for errors
		if(is_object($result) && get_class($result) == 'error_2')
		{
			ob_start();
			print_r($result);
			$error_data = ob_get_clean();
			ob_end_clean();

			$app_log = new Applog();
			$app_log->Write($error_data);
			$app_log->Write($function."(".$args[0].",".$this->Map_Column($args[1]).")");
		}
		else
		{
			return $result;
		}
	}
	
	function Save_Statpro($sp_session_id,$sp_track_id,$sp_space_key,$sp_consumer_id)
	{
		$this->Save_Session($sp_session_id);
		$this->Save_Track($sp_track_id);
		$this->Save_Space($sp_space_key);
		$this->Save_Consumer($sp_consumer_id);
		
		return TRUE;
	}
	
	function Save_Session($sp_session_id)
	{
		$query = "";
		echo "Saved Session: " . $sp_session_id . "<br>";
	}
	
	function Save_Track($sp_track_id)
	{
		echo "Saved Track: ". $sp_track_id . "<br>";
	}
	
	function Save_Consumer($sp_consumer_id)
	{
		echo "Saved Consumer: ". $sp_consumer_id . "<br>";	
	}
	
	function Save_Space($sp_space_key)
	{
		echo "Saved Space: ". $sp_space_key . "<br>";
	}
	
	function Space_Exist()
	{
		return $_SESSION['statpro']['space_key'] ? TRUE : FALSE;	
	}
	
	function Session_Exist()
	{
		return $_SESSION['statpro']['session_id'] ? TRUE : FALSE;
	}
	
	function Track_Exist()
	{
		return $_SESSION['statpro']['track_key'] ? TRUE : FALSE;
	}
	
	function Consumer_Exist()
	{
		return $_SESSION['statpro']['consumer_id'] ? TRUE : FALSE;	
	}

	/**
	* Some stat names tend to change in transit.  map_hack will
	* keep track of these changes until we switch over to
	* StatPro completely.
	*/
	function Map_Column($column=NULL) {
		// we will need to break this out to a switch statement
		// later to support non-olp sites (if necessary)
		$map =  array(
			'base'	=> 'prequal',
			'income'	=> 'submit',
			'post'	=> 'nms_prequal',
			'base'	=> 'prequal',
			'home'	=> 'visitor',
			'apply'	=> 'submit',
			'visitors'	=> 'visitor',
		);
		return (is_null($column)?$map:(isset($map[$column])?$map[$column]:$column));
	}
}
?>
