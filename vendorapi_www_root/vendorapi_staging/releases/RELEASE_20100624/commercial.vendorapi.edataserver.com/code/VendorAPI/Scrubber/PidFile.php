<?php
/**
 * Crappy class to handle the pid file
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Scrubber_PidFile
{
	/**
	 * Where to store the pid information?
	 *
	 * @var string
	 */
	protected $file_name;

	/**
	 * Build us a object??
	 *
	 * @param string $file_name
	 */
	public function __construct($file_name)
	{
		$this->file_name = $file_name;
	}

	/**
	 * Check if the pid file exists and is already running
	 * or something.
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function check()
	{
		if (file_exists($this->file_name))
		{
			$pid = trim(file_get_contents($this->file_name));
			if ($pid && posix_kill($pid, 0))
			{
				throw new Exception('Process running with PID: '.$pid);
			}
		}
		register_shutdown_function('call_user_func', array($this, 'unlock'));
		file_put_contents($this->file_name, posix_getpid(), LOCK_EX);
		return TRUE;
	}

	/**
	 * Remove the lock?
	 *
	 * @return void
	 */
	public function unlock()
	{
		if (file_exists($this->file_name))
		{
			unlink($this->file_name);
		}
	}
}