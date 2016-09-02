<?php
/**
 * A class to manage a session variable as a bool, but represent it as a stack.
 *
 * This is just a temporary class to be used in olp to handle the process_rework
 * variable because the control flow during live apps is not clear.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class Session_Stack
{
	/**
	 * The name of the variable in $_SESSION we're managing.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Applog object.
	 * 
	 * @var object with Write() method.
	 */
	protected $log;

	/**
	 * @param string $name The name of the variable to control in session.
	 * @throws RuntimeException
	 */
	public function __construct($name, $log)
	{
		if (!method_exists($log, 'Write'))
		{
			throw new InvalidArgumentException(
				'must be able to write to log object.'
			);
		}
		$this->log = $log;
		 
		if (isset($_SESSION[$name]) && !is_array($_SESSION[$name]))
		{
			$this->log->Write(
				"_SESSION[$name] was set, but not an array. resetting."
			);
		}

		if (!isset($_SESSION[$name]) || !is_array($_SESSION[$name]))
		{
			$_SESSION[$name] = array();
		}

		$this->name = $name;
	}

	/**
	 * Appends an item to the 'stack' this object is managing.
	 *
	 * @param array $array The information about the state of the variable.
	 *
	 * @return void
	 */
	protected function add(array $array)
	{
		if (empty($array['time']))
		{
			$array['time'] = time();
		}
		$_SESSION[$this->name][] = $array;
	}

	/**
	 * Sets the stack variable to true.
	 *
	 * @return void
	 */
	public function setTrue()
	{
		$trace = debug_backtrace();

		$this->qualityControl();

		$call = sprintf("%s->%s called at %s in %s",
			$trace[1]['class'],
			$trace[1]['function'], 
			$trace[1]['line'], 
			$trace[1]['file']
		);
		$this->add(
			array('call' => $call, 'value' => TRUE)
		);
	}

	/**
	 * Sets the stack variable to false.
	 *
	 * @return void
	 */
	public function setFalse()
	{
		$trace = debug_backtrace();

		$this->qualityControl();

		$call = sprintf("%s->%s called at %s in %s",
			$trace[1]['class'],
			$trace[1]['function'],
			$trace[1]['line'], 
			$trace[1]['file']
		);
		$this->add(array('call' => $call, 'value' => FALSE));
	}

	/**
	 * Returns the current value for the managed variable.
	 *
	 * @return mixed Value set for the variable in session.
	 */
	public function value()
	{
		$this->qualityControl();
		$count = count($_SESSION[$this->name]);
		if ($count > 0 && isset($_SESSION[$this->name][$count-1]['value']))
		{
			$return =  $_SESSION[$this->name][$count-1]['value'];
		}
		else
		{
			$return = NULL;
		}

		/* DEBUGGING
		$t = debug_backtrace();
		$this->log->Write(sprintf(
			'value() got looked at by %s->%s at line %s in %s (%s)',
			$t[1]['class'], 
			$t[1]['function'],
			$t[1]['line'],
			$t[1]['file'], 
			var_export($return, true))
		);
		   END DEBUGGING */

		return $return;
	}

	/**
	 * Method to make sure the variable we're managing is the right type.
	 *
	 * @return void
	 */
	protected function qualityControl()
	{
		if (!isset($_SESSION[$this->name]) || !is_array($_SESSION[$this->name]))
		{
			$this->log->Write(sprintf(
				'_SESSION[%s] was not an array, resetting',
				$this->name)
			);
		}
	}
}
?>
