<?php

	/**
	 * A class designed to manage multiple syslog program inputs in
	 * a single application.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Log_SysLogMultiplex_1 extends Object_1
	{
		/**
		 * Constructor is not meant to be called externally.
		 *
		 */
		private function __construct()
		{
		}

		/**
		 * @var array
		 */
		private $programs = array();

		/**
		 * @var string
		 */
		protected $active_program;

		/**
		 * Define a syslog program as an input. You may call this
		 * multiple times to change the flags or facility.
		 *
		 * @param string $name
		 * @param int $option
		 * @param int $facility
		 */
		public function setProgram($name, $option, $facility)
		{
			if (!array_key_exists($name, $this->programs))
			{
				$this->programs[$name] = array();
			}

			$this->programs[$name]['option'] = $option;
			$this->programs[$name]['facility'] = $facility;
		}

		/**
		 * Clears this program specification
		 *
		 * @param unknown_type $name
		 */
		public function clearProgram($name)
		{
			if (!array_key_exists($name, $this->programs))
			{
				unset($this->programs[$name]);
			}
		}

		/**
		 * Sets the active program to the one specified.
		 *
		 * WARNING: To avoid opening the log a jillion times, this mechanism
		 * is meant to be the sole responsible entity for syslog operations.
		 * Making your own calls to openlog() or closelog() will likely
		 * result in UNDEFINED BEHAVIOR.  This method will NOT attempt to
		 * reopen the log if it believes it already has.
		 *
		 * @param string $name
		 */
		public function setActiveProgram($name)
		{
			if (!array_key_exists($name, $this->programs))
			{
				throw new Log_SysLogException_1("The program has not yet been defined within the multiplex! Call " . __CLASS__ . "::setProgram first!");
			}

			if ($this->active_program !== $name)
			{
				// We have no real way of knowing if the syslog descriptor
				// has already been obtained. All we know is that what we last
				// set does not match.  Blindly attempt to close it before
				// we attempt to open it again.
				@closelog();

				if (!@openlog(
					$name,
					$this->programs[$name]['option'],
					$this->programs[$name]['facility']
					))
				{
					throw new Log_SysLogException_1("Unable to establish syslog connection.");
				}

				$this->active_program = $name;
			}
		}

		/**
		 * @var Log_SysLogMultiplex_1
		 */
		private static $instance;

		/**
		 * Get the syslog multiplex object
		 *
		 * @return Log_SysLogMultiplex_1
		 */
		public static function getInstance()
		{
			if (self::$instance === NULL)
			{
				self::$instance = new Log_SysLogMultiplex_1();
			}

			return self::$instance;
		}
	}
?>