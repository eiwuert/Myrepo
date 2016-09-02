<?php

require_once('dlhdebug.php');

// This is for staging files.  For example, when files need to be processed by a cron,
// it might be handy to first move the files from the files_ready directory to a in_process
// directory.  After the files are successfully processed in the in_process directory, they
// can be moved to the processing_done directory.  This way, a file that fails processing is
// automatically still queued to be processed.
//
// This class either moves all files in a directory to another directory or else moves
// a single file from one directory to another.
//
// When moving all files, a single lock file will be created in the source directory
// and locked with an exclusive lock to make sure there are not two instances of this
// class running at the same time.  When moving a single file, a lock file named similar
// to the file being moved will be created in the source directory to make sure there are
// not two instances of this class trying to move the file at the same time.


defined('FILE_STAGER_LOCK') || define('FILE_STAGER_LOCK', '_File_Stager.lock');


class File_Stager
{
	public $source_dir = '';
	public $target_dir = '';

	protected $sleep             = 0;
	protected $error             = false;
	protected $errormsg          = 'nothing done yet';
	protected $lock_file_pointer = null;


	public function __construct( $source_dir='', $target_dir='', $sleep=0 ) // sleep is just for testing locks.
	{
		$this->source_dir = rtrim(trim(realpath($source_dir)), '/') . '/';  // easier to strip '/' and re-add 
		$this->target_dir = rtrim(trim(realpath($target_dir)), '/') . '/';  // than to check for presence.
		$this->sleep = $sleep;
		$this->Clear_Error();
	}


    public function Get_Errormsg()
    {
    	return $this->errormsg;
    }


	public function Clear_Error( $msg='ok' )
	{
		$this->error = false;
		$this->errormsg = $msg;
	}


	// move all files from source directory to destination directory
	// destination file will be overlayed without warning if it already exists!
	public function Move_All_Files()  
	{
		if ( $this->error ) return false;
	
		!$this->error && ($file_array = $this->Get_All_Files_In_Directory());

		!$this->error && $this->Lock_Directory();

		!$this->error && ($this->Move_Files_In_Array($file_array));

		$this->Unlock_Directory();
		
		if ( $this->error ) return false;

		return true;
	}


	// destination file will be overlayed without warning if it already exists!
	public function Move_Single_File( $filename='' )
	{
		if ( $this->error ) return false;
	
		$filename = trim(trim($filename), '/');
		$file_array = array($filename);
		
		!$this->error && $this->Lock_File($filename);

		!$this->error && ($this->Move_Files_In_Array($file_array));

		$this->Unlock_File($filename);
		
		if ( $this->error ) return false;

		return true;
	}


	protected function Get_All_Files_In_Directory()
	{
		if ( $this->error ) return false;
	
		$result = array();
	
		($dir_handle = opendir($this->source_dir)) ||
			$this->Set_Error(__METHOD__, __LINE__, "failed to opendir ($this->source_dir)");

		while ( !$this->error && (false !== ($filename = readdir($dir_handle))) )
		{
			if ( $filename != '.' && $filename != '..' && !is_dir($filename) ) $result[] = $filename;
		}

		if ( $dir_handle ) closedir($dir_handle);

		if ( $this->error ) return false;

		return $result;
	}


	protected function Lock_Directory()
	{
		if ( $this->error ) return false;
	
		$lockfile = $this->source_dir . FILE_STAGER_LOCK;
	
		false !== ($this->lock_file_pointer = fopen($lockfile, 'w')) ||
			$this->Set_Error(__METHOD__, __LINE__, "failed to create logfile ($lockfile)");

		if ( $this->error ) return false;

		$would_block = false;

		flock($this->lock_file_pointer, LOCK_EX + LOCK_NB, $would_block) ||
			$this->Set_Error(__METHOD__, __LINE__, "failed to get exclusive lock on lockfile ($lockfile)");

		if ( !$this->error && is_numeric($this->sleep) && $this->sleep > 0 ) sleep($this->sleep);

		return $this->error ? false : true;
	}


	protected function Unlock_Directory()
	{
		$lockfile = $this->source_dir . FILE_STAGER_LOCK;
		if ( $this->lock_file_pointer ) flock($this->lock_file_pointer, LOCK_UN + LOCK_NB);
		if ( file_exists($lockfile) ) unlink($lockfile);
		$this->lock_file_pointer = null;
	}
	

	protected function Lock_File( $filename )
	{
		if ( $this->error ) return false;
	
		$lockfile = $this->source_dir . $filename . FILE_STAGER_LOCK;
	
		false !== ($this->lock_file_pointer = fopen($lockfile, 'w')) ||
			$this->Set_Error(__METHOD__, __LINE__, "failed to lock file ($lockfile)");

		if ( $this->error ) return false;

		$would_block = false;

		flock($this->lock_file_pointer, LOCK_EX + LOCK_NB, $would_block) ||
			$this->Set_Error(__METHOD__, __LINE__, "failed to get exclusive lock on file ($lockfile)");

		if ( !$this->error && is_numeric($this->sleep) && $this->sleep > 0 ) sleep($this->sleep);
		
		return $this->error ? false : true;
	}
	

	protected function Unlock_File( $filename )
	{
		$lockfile = $this->source_dir . $filename . FILE_STAGER_LOCK;
		if ( $this->lock_file_pointer ) flock($this->lock_file_pointer, LOCK_UN + LOCK_NB);
		if ( file_exists($lockfile) ) unlink($lockfile);
		$this->lock_file_pointer = null;
	}
	

	protected function Move_Files_In_Array(&$file_array)
	{
		if ( $this->error ) return false;

		$count = 0;
	
		foreach( $file_array as $file )
		{
			$srcfile = $this->source_dir . $file;
			$targetfile = $this->target_dir . $file;
			if ( ! $this->Exec_Command("mv -v", "$srcfile $targetfile") )
			{
				return false;
			}
			$count++;
		}

		$this->Clear_Error("ok: moved $count files");

		return true;
	}


	// copied from /virtualhosts/ecash2.7/cronjobs/cashline_dump_transfer.class.php
	protected function Exec_Command($command, $args)
	{
		if ( $this->error ) return false;
	
		exec("{$command} {$args}", $lines, $return_code);
		
		if($return_code != 0)
		{
			$text = '';
			foreach($lines as $line) $text .= $line."\n";
			$this->Set_Error(__METHOD__, __LINE__, "failed to exec command=$command, args=$args, return_code=$return_code, text=$text");
			return false;
		}
		
		return true;
	}


	protected function Set_Error($method, $line, $msg)
	{
		$this->error = true;
		$this->errormsg = "$method::$line: $msg";
	}
	
}


?>