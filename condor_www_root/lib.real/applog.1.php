<?php

/**
 * Applog - class to safely log application messages
 * applog requires the directory APPLOGDIR with mode '1777'
 */

/*
These are defined by PHP

define('LOG_EMERG', 0);
define('LOG_ALERT', 1);
define('LOG_CRIT', 2);
define('LOG_ERR', 3);
define('LOG_WARNING', 4);
define('LOG_NOTICE', 5);
define('LOG_INFO', 6);
define('LOG_DEBUG', 7);
*/

define ('APPLOGDIR','/virtualhosts/log/applog/');

class Applog
{

	var $log_dir;
	var $log_file;
	var $size_limit;
	var $file_limit;
	var $context;
	var $file_name;
	var $lock_rsrc;
	var $file_size;
	var $fh;
	var $lh;
	var $errstr;
	var $rotate;
	var $umask;			// The umask for writing new logs


	function Applog($log_dir='all', $size_limit=1000000, $file_limit=5, $context="", $rotate='TRUE', $umask=022)
	{
		$this->log_dir		= APPLOGDIR.$log_dir;
		$this->size_limit	= $size_limit;
		$this->file_limit	= $file_limit;
		$this->context		= $context;
		$this->file_name	= 'current';
		$this->rsrc_name	= 'lockresource';
		$this->log_file		= $this->log_dir . "/" . $this->file_name;
		$this->lock_rsrc	= $this->log_dir . "/" . $this->rsrc_name;
		$this->rotate		= $rotate;
		$this->umask		= $umask;

		if ( !file_exists($this->log_dir) )
		{
			umask(002);
			if ( !mkdir($this->log_dir, 01775, true) )
			{
				$this->errstr = "Applog:" . __LINE__ . ": could not create log directory '" . $this->log_file . "'";
				return false;
			}
		}
		umask(022);

		$this->LockRsrc('shared');

		// This should only happen on first use with new applog subdirectory
		if ( !file_exists($this->log_file) )
		{
			$this->OpenLogFile();
			$this->CloseLogFile();
		}

		$this->UnlockRsrc();

		return true;
	}


	function Write($text, $level=LOG_DEBUG)
	{
		$level = ( $level < LOG_EMERG || $level > LOG_DEBUG ) ? LOG_DEBUG : $level;
		$prefix_context = ( strlen($this->context) > 0 ) ? '[' . $this->context . '] ' : '';
		$prefix = date('Y.m.d H:i:s') . ' [' . intval($level) . '] ' . $prefix_context;
		$text = str_replace("\r", "", trim($text));
		$text = $prefix . preg_replace('/(?<=\n)/s', "\t", $text) . "\n";

		$this->LockRsrc('shared');

		clearstatcache();

		// only if rotate is turned on
		if ($this->rotate)
		{
			$this->file_size = filesize($this->log_file);
			$this->file_size += strlen($text);

			if ( $this->file_size > $this->size_limit )
			{
				$this->LockRsrc('exclusive');

				clearstatcache();
				$this->file_size = filesize($this->log_file);
				$this->file_size += strlen($text);

				if ( $this->file_size > $this->size_limit )
				{
					$this->Rotate();
				}
			}
		}

		$this->OpenLogFile();

		fwrite($this->fh, $text);

		$this->CloseLogFile();

		$this->UnlockRsrc();

		return true;
	}


	function LockRsrc($type)
	{
		$this->lh = fopen($this->lock_rsrc,'w');
		if ($this->lh === false)
		{
			$this->errstr = "Applog:" . __LINE__ . ": could not access locking resource '" . $this->lock_rsrc . "'";
			return false;
		}

		$type = strtoupper($type);
		if ($type == 'SHARED')
		{
			$lockmode = LOCK_SH;
		}
		else
		{
			$lockmode = LOCK_EX;
		}

		$result = flock($this->lh, $lockmode);
		if ( !$result )
		{
			$this->errstr = "Applog:" . __LINE__ . ": could not lock resource '" . $this->lock_rsrc . "' ($type)";
			return false;
		}

		return true;
	}


	function UnlockRsrc()
	{
		flock($this->lh, LOCK_UN);
		return true;
	}


	function OpenLogFile()
	{
		// By default it will only allow the user to write to the log (umask 022)
		umask($this->umask);
		
		$this->fh = fopen($this->log_file,'a');
		if ($this->fh === false)
		{
			$this->errstr = "Applog:" . __LINE__ . ": could not open logfile '" . $this->log_file . "'";
			return false;
		}
	}


	function CloseLogFile()
	{
		fclose($this->fh);
	}


	function Err()
	{
		return (bool)($this->errstr);
	}


	function Errstr()
	{
		return $this->errstr;
	}


	function Rotate()
	{
		for ($i = $this->file_limit - 2; $i >= 0; $i--)
		{
			$j = $i + 1;

			// In case previous version of Applog was used...
			if ( $i < 10 && file_exists($this->log_dir."/log.".$i.".gz") )
			{
				$file_to_rename = $this->log_dir."/log.".$i.".gz";
			}
			else
			{
				$file_to_rename = $this->log_dir."/log.".str_pad($i,2,'0',STR_PAD_LEFT).".gz";
			}

			if ( file_exists($file_to_rename) )
			{
				$filename_new = str_pad($j,2,'0',STR_PAD_LEFT);
				if ( ! rename($file_to_rename,$this->log_dir."/log.".$filename_new.".gz") )
				{
					$this->errstr = "could not rename '$file_to_rename' to 'log.".$filename_new.".gz'!";
					return false;
				}
			}
		}
		if ( ! rename($this->log_file,$this->log_dir."/log.00") )
		{
			$this->errstr = "could not rename '{$this->log_file}' to 'log.00'!";
			return false;
		}
		if ( system("gzip {$this->log_dir}/log.00 2>/dev/null") === false )
		{
			$this->errstr = "could not 'gzip log.00' in dir '{$this->log_dir}'";
			return false;
		}

		return true;
	}
	
	/**
	 * Sets the umask for creating new logs.
	 *
	 * @param int $umask
	 */
	function Set_Umask($umask)
	{
		$this->umask = $umask;
	}

}

?>
