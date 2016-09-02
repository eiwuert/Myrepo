<?php
	/**
	 * @package Unix
	 */

	/**
	 * ticks is the callback method used by the process control
	 * signal handlers in php. This command enables and configures
	 * that functionality.
	 */
	declare (ticks = 1);

	/**
	 * Base class for all forked processes. Most of the magic happens here,
	 * providing the forking mechanism itself, signal handling and the main
	 * program loop.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	abstract class Unix_ForkedProcess_1
	{
		/**
		 * @var bool
		 */
		protected $continue_execution = FALSE;

		/**
		 * @var bool
		 */
		protected $detached = FALSE;

		/**
		 * @var int
		 */
		protected $sleep_time;

		/**
		 * @var int
		 */
		protected $pid;

		/**
		 * @var array
		 */
		protected $signal_cache = array();

		/**
		 * @param int $sleep_time Time to sleep between each poll
		 */
		public function __construct($sleep_time = 100000)
		{
			$this->sleep_time = $sleep_time;
			$this->signal_cache = array_fill(0, 64, FALSE);
		}

		/**
		 * Function that stores signals very quickly. Acts as the signal handler for php.
		 *
		 * @param int $signal
		 */
		public function handleSignal($signal)
		{
			$this->signal_cache[$signal] = TRUE;
		}

		/**
		 * @param int $signal
		 */
		protected function enableSignal($signal)
		{
			pcntl_signal($signal, array($this, "handleSignal"));
		}

		/**
		 * returns TRUE if the signal has been received. If it has been received,
		 * the signal is reset in the signal cache.
		 *
		 * @param int $signal
		 * @return bool
		 */
		protected function hasSignal($signal)
		{
			if ($this->signal_cache[$signal])
			{
				$this->signal_cache[$signal] = FALSE;
				return TRUE;
			}
			return FALSE;
		}

		/**
		 * Create background fork.
		 * @return int The PID of the child process
		 */
		public function fork($detach = TRUE)
		{
			$pid = pcntl_fork();

			if ($pid == -1)
			{
				throw new Exception("Unable to fork");
			}
			else if ($pid > 0)
			{
				return $pid;
			}

			$this->enableSignal(SIGTERM);
			$this->enableSignal(SIGINT);

			if ($detach == TRUE)
			{
				if (posix_setsid() == -1)
				{
					throw new Exception("Unable to detach from controlling terminal!");
				}
				$this->detached = TRUE;
			}

			$this->main();
		}
		
		/**
		 * Main execution loop
		 *
		 */
		public function main()
		{
			$this->pid = posix_getpid();
			$this->continue_execution = TRUE;
			$this->onStartup();

			while ($this->continue_execution)
			{
				if ($this->hasSignal(SIGTERM) || $this->hasSignal(SIGINT))
				{
					$this->quit();
				}
				$this->tick();
				usleep($this->sleep_time);
			}
			$this->onExit();
			exit(0);
		}

		/**
		 * Cleans up and exits
		 * @return void
		 */
		protected function quit()
		{
			$this->continue_execution = FALSE;
		}

		/**
		 * Logs a message
		 *
		 * @param string $line
		 */
		protected function log($line)
		{
			echo date("H:i:s") . "\\{$this->pid}: $line\n";
		}

		/**
		 * Called on startup
		 */
		protected abstract function onStartup();

		/**
		 * Called before exiting
		 */
		protected abstract function onExit();

		/**
		 * Called every $sleep_time; allows the child class to do work
		 */
		protected abstract function tick();
	}
?>