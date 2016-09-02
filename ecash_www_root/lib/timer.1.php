<?PHP
/** 			
	@version
			1.0.0 2004-03-04 - 
				- A PHP Class to handle timing of a script
			
			1.0.1 2004-08-24 -
				- Minor code changes to clean up the code base
			
			1.1.0 2004-09-27
				- PHP 5 compliant, not backwards compatible
			
	
	@Updates:
	
	@Usage Example:
	<?PHP
	$new_timer = new Code_Timer();	// Instantiate the timer class
	$example->Run_Code_2_Time();	   	// Insert the code block that you would like timed
	$new_timer->Stop_Timer();		// Stop the timer
	echo $new_timer->Get_Time();		// Return the time
	?>	
*/

// Start the class to hold the functions
class Code_Timer
{	
	/*
	* @param $start_time	int:		start in microtime
	* @param $ent_time 		int:		end in microtime
	*/
	
	 protected $start_time;
	 protected $end_time;
	
	/**
	* @return bool
	* @desc Constructor
 	*/
	function __construct()
	{ 
		$this->Start_Timer();
		return TRUE;
	}
	
	/**
	* @return bool
	* @desc Start the timer
 	*/
 	public function Start_Timer()
 	{ 
 		$this->start_time = microtime(true);
		return TRUE;
	}
	
	/**
	* @return bool
	* @desc Stop the timer
 	*/
 	public function Stop_Timer()
 	{ 
 		$this->end_time = microtime(true);
		return TRUE;
	}
	
	/**
	* @return 		int
	* @param $decimals 	int
	* @desc Returns the timer with the specified decimal place
 	*/
	public function Get_Time($decimals = 2)
	{ 
		$decimals = intval($decimals);
		
		$decimals > 8 ? $decimals = 8 : NULL;
		$decimals < 0 ? $decimals = 0 : NULL;
		 
		return number_format($this->end_time - $this->start_time, $decimals, '.', '');
	}
	
	/**
	* @return bool
	* @desc Destructor
 	*/
	function __destruct()
	{
		return TRUE;	
	}
}
?>