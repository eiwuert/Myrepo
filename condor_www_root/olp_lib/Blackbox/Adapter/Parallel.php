<?php
/**
 * Parallel Blackbox adapter.
 * 
 * This class will run both old and new Blackbox in parallel.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Adapter_Parallel
{
	/**
	 * Instance of Blackbox_Adapter_Parallel.
	 *
	 * @var Blackbox_Adapter_Parallel
	 */
	protected static $instance;
	
	/**
	 * New adapater instance.
	 *
	 * @var Blackbox_Adapter_New
	 */
	protected $new_instance;
	
	/**
	 * Old adapter instance.
	 *
	 * @var Blackbox_Adapter_Old
	 */
	protected $old_instance;
	
	/**
	 * List of functions we can't run in the new Blackbox.
	 *
	 * @var array
	 */
	protected $invalid_functions = array(
		'updateSession'
	);
	
	/**
	 * Determines if we're going to run new blackbox on this run or not.
	 *
	 * @var bool
	 */
	protected $run_new_blackbox = FALSE;
	
	/**
	 * The percentage of time we're going to run new blackbox.
	 */
	const PERCENT = 0;
	
	/**
	 * Constructor for Blackbox_Adapter_Parallel.
	 *
	 * @param string $mode
	 * @param array $config_data
	 */
	protected function __construct($mode, $config_data)
	{
		$this->new_instance = new Blackbox_Adapter_NewParallel($mode, $config_data);
		$this->old_instance = new Blackbox_Adapter_Old($mode, $config_data);
		$this->run_new_blackbox = $this->runNewBlackbox();
	}
	
	/**
	 * Returns an instance of the Blackbox_Adapter_Parallel class.
	 *
	 * @param string $mode
	 * @param array $config_data
	 * @param bool $reset
	 * @return Blackbox_Adapter_Parallel
	 */
	public static function getInstance($mode = MODE_DEFAULT, $config_data = NULL, $reset = FALSE)
	{
		if (!isset(self::$instance) || $reset)
		{
			self::$instance = new Blackbox_Adapter_Parallel($mode, $config_data);
		}
		
		return self::$instance;
	}
	
	/**
	 * Overloaded __call method.
	 * 
	 * Will call both old and new Blackbox, ignoring the output from the new blackbox.
	 *
	 * @param unknown_type $name
	 * @param unknown_type $args
	 * @return unknown
	 */
	public function __call($name, $args)
	{
		// Call the old blackbox and actually do something with it.
		$ret_val = call_user_func_array(array($this->old_instance, $name), $args);
		
		// Call the new blackbox, but ignore whatever it sends back
		if ($this->isValidFunction($name) && $this->run_new_blackbox)
		{
			try
			{
				call_user_func_array(array($this->new_instance, $name), $args);
			}
			catch (Exception $e)
			{
				// We don't want to risk anything blowing up on us...
				$this->run_new_blackbox = FALSE;
				OLPBlackbox_Config::getInstance()->applog->Write($e->getMessage());
			}
		}
		
		return $ret_val;
	}
	
	/**
	 * Determines if we should run new Blackbox on this run.
	 *
	 * @return unknown
	 */
	protected function runNewBlackbox()
	{
		if (mt_rand(1, 100) <= self::PERCENT)
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Returns if the function is a valid function to run for new BBx.
	 *
	 * @param string $name
	 * @return bool
	 */
	protected function isValidFunction($name)
	{
		foreach ($this->invalid_functions as $function)
		{
			if (strcasecmp($function, $name) == 0)
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Returns FALSE, because we'll always consider the parallel to be the old Blackbox.
	 *
	 * @return unknown
	 */
	public static function isNewBlackbox()
	{
		// Consider this to always be the old Blackbox
		return FALSE; 
	}

	/**
	* Sets a blackbox object (used for oldschool sleeping)
 	*
	* @param object $blackbox Blackbox object
	* @param object $winner Blackbox target object
	* @return void
	*/
	public function setBlackbox($blackbox, $winner = NULL)
	{
		$this->old_instance->setBlackbox($blackbox, $winner);
	}
}
?>
