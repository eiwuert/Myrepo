<?php
/*
	@version
		0.1.0 2004.11.04 - Tom R

	Synopsis:
		This session handler shares session files among multiple web servers so that web clients can be seamlessly
		serviced by any server in the pool.  It does this by having each of the web servers use NFS to export a
		session directory that the other web servers mount and use.  This class handles reads and writes to these
		shared session directories.

		During normal processing, this session handler detects session shares that are exported by the other web
		servers and mounted by this web server.  It then writes session info not only to the local session directory
		but also to the session directory of every other web server.  So if there are four web servers, then the session
		info is written four times.  The overhead of these writes can be minimized by configuring NFS to export these
		shared directories asynchronously.

		When reading session info, this server gets the timestamp on all copies of the session and reads the file
		with the most recent mtime.  This requires that all web servers keep their clocks sync'd since NFS uses the
		clock on the exporting server, not the client pc, to set the mtime of the file.  Some simple tests are done
		within this class to detect servers whose clock is out of sync.  Reads will favor the local copy because it
		is written last thus having the most recent mtime.

		In the event that any web server dies, processing continues on the other web servers since they have the
		session info of the dead server.  When the server comes back online, it automatically gets the session info
		with the most recent mtime.

	System Setup:
		This class assumes a certain system-level setup.  It assumes that SESSION_DIR contains one directory for each
		web server, including itself.  It also assumes that the first, alphabetical, entry contains the local directory
		and all the rest are NFS mounts.  So each web server exports SESSION_DIR/0 and mounts SESSION_DIR/1 .. 9,
		or exports SESSION_DIR/a and mounts SESSION_DIR/b .. z.  Thus each web server needs the following files:

			/etc/exports
				...
				/var/sessions/a    *.tss(rw,async)
			/etc/fstab
				...
				ws2.tss:/var/sessions/a    /var/sessions/b nfs     rw,soft,timeo=1,retrans=1      0 0
				ws3.tss:/var/sessions/a    /var/sessions/c nfs     rw,soft,timeo=1,retrans=1      0 0
				ws4.tss:/var/sessions/a    /var/sessions/d nfs     rw,soft,timeo=1,retrans=1      0 0

		SESSION_DIR should be owned by root and each directory below should be owned by apache:
			{} ls -l /var
			drwxrwxrwx   4 root root    4096 Nov 10 09:35 sessions
			{} ls -l /var/sessions
			drwxr-xr-x  2 apache apache 4096 Nov  9 15:13 a
			drwxr-xr-x  2 apache apache 4096 Nov  9 15:11 b

		For you fans of ascii art, here's a graphical representation:

		-------------------------------------------------------------------------------------------------------
			web server 1
		+-----------------------+					/etc/exports
		|	/var/sessions/a		|->-+					/var/sessions/a    *.tss(rw,async)
		+-----------------------+	|				/etc/fstab
		|	/var/sessions/b		|-<-|-<-+				ws2.tss:/var/sessions/a  /var/sessions/b nfs  ...
		+-----------------------+	|	|				ws3.tss:/var/sessions/a  /var/sessions/c nfs  ...
		|	/var/sessions/c		|-<-|-<-|-<-+
		+-----------------------+	|	|	|
									v	^	^
			web server 2			|	|	|
		+-----------------------+	|	|	|		/etc/exports
		|	/var/sessions/a		|->-|->-+	^			/var/sessions/a    *.tss(rw,async)
		+-----------------------+	|	|	|		/etc/fstab
		|	/var/sessions/b		|-<-+	v	^			ws1.tss:/var/sessions/a  /var/sessions/b nfs  ...
		+-----------------------+	|	|	|			ws3.tss:/var/sessions/a  /var/sessions/c nfs  ...
		|	/var/sessions/c		|-<-|-<-|-<-+
		+-----------------------+	|	|	|
									|	|	|
			web server 3			v	v	^
		+-----------------------+	|	|	|		/etc/exports
		|	/var/sessions/a		|->-|->-|->-+			/var/sessions/a    *.tss(rw,async)
		+-----------------------+	|	|			/etc/fstab
		|	/var/sessions/b		|-<-+	v				ws1.tss:/var/sessions/a  /var/sessions/b nfs  ...
		+-----------------------+		|				ws2.tss:/var/sessions/a  /var/sessions/c nfs  ...
		|	/var/sessions/c		|-<--<--+
		+-----------------------+

		-------------------------------------------------------------------------------------------------------

	Updates:

	Notes:
		This file is short,... keep it that way.
*/

require_once 'applog.1.php';

if ( !defined('SESSION_DIR') ) 
{
	define ('SESSION_DIR','/virtualhosts/sessions/');
}


class Session_1
{
	protected $log;
	protected $share_list = array();

	public function __construct($sid = NULL, $name = 'nfsid')
	{

		$this->log = new Applog('nfs_session');

		if ( is_dir(SESSION_DIR) ) 
		{
			if ( $this->share_list = scandir(SESSION_DIR) ) 
			{
				array_shift($this->share_list); array_shift($this->share_list); // get rid of . and ..
			}
			else
			{
				$this->log->Write("can't open top-level session directory");
			}
		}
		else
		{
			$this->log->Write("top-level session directory does not exist");
		}

		session_name($name);

		if ( !is_null($sid) )
			session_id($sid);

		session_set_save_handler
		(
			array(&$this, 'Open'),
			array(&$this, 'Close'),
			array(&$this, 'Read'),
			array(&$this, 'Write'),
			array(&$this, 'Destroy'),
			array(&$this, 'Garbage_Collection')
		);

		session_start();

		return TRUE;
	}


	public function Open($save_path, $session_name)
	{
		return TRUE;
	}

	public function Close()
	{
		return TRUE;
	}

	/**
	 * @return string
	 * @param $id
	 * @desc Reads the session data
	 */
	public function Read($id)
	{
		$current_time = time();

		// find the most recent session file
		$most_recent_mtime = 0;
		foreach ($this->share_list as $d) 
		{
			if ( is_dir(SESSION_DIR.$d) && file_exists(SESSION_DIR."$d/$id") ) 
			{
				$mtime = filemtime(SESSION_DIR."$d/$id");
				#$this->log->Write("dir=$d; mtime=$mtime (".date('Y.m.d H:i:s',$mtime)."); most_recent_mtime=$most_recent_mtime (".date('Y.m.d H:i:s',$most_recent_mtime).")");
				if ( $mtime > $current_time ) 
				{
					$this->log->Write("Critical  Read Error: web server clocks are not sync'd.\n\t\t".SESSION_DIR."a: ".date('Y.m.d H:i:s',$current_time)."\n\t\t".SESSION_DIR.$d.": ".date('Y.m.d H:i:s',$mtime));
				}
				if ( $mtime > $most_recent_mtime ) 
				{
					$most_recent_mtime = $mtime;
					$use_dir = $d;
				}
			}
		}
		#$this->log->Write("use_dir=$use_dir; most_recent_mtime=$most_recent_mtime (".date('Y.m.d H:i:s',$most_recent_mtime).")");

		// if the session file doesn't exist anywhere, return an empty string;
		if ( !$most_recent_mtime ) 
		{
			return '';
		}

		#$this->log->Write(".Read: $use_dir complete");
		return(gzuncompress(file_get_contents(SESSION_DIR."$use_dir/$id")));

	}

	/**
	 * @return bool
	 * @param $id string
	 * @param $info string
	 * @desc Writes the session data
	 */
	public function Write($id, $info)
	{
		$compressed_info = gzcompress($info);

		// write the session info to every share; write the local copy last
		rsort($this->share_list);
		foreach ($this->share_list as $d) 
		{
			if ( is_dir(SESSION_DIR.$d) ) 
			{
				file_put_contents(SESSION_DIR."$d/$id", $compressed_info);
			}
		}

		// check the mtimes for inconsistency;  this may render the 'async' option in /etc/exports useless;
		$current_time = time();
		sort($this->share_list);
		foreach ($this->share_list as $d) 
		{
			if ( is_dir(SESSION_DIR.$d) ) 
			{
				$mtime = filemtime(SESSION_DIR."$d/$id");
				if ( empty($write_time) ) 
				{
					$write_time = $mtime;
				}
				if ( (abs($mtime - $write_time) > 1) || ($mtime > $current_time+1) ) 
				{
					$this->log->Write("Critical Write Error: web server clocks are not sync'd.\n\t\t".SESSION_DIR."a: ".date('Y.m.d H:i:s',$current_time)."\n\t\t".SESSION_DIR.$d.": ".date('Y.m.d H:i:s',$mtime));
				}
			}
		}

		return TRUE;
	}

	/**
	 * @return bool
	 * @param $session_id string
	 * @desc Remove the session from the database
	 */
	public function Destroy($id)
	{
		// delete all session files
		foreach ($this->share_list as $d) 
		{
			if ( is_dir(SESSION_DIR.$d) ) 
			{
				if ( file_exists(SESSION_DIR."$d/$id") ) 
				{
					unlink(SESSION_DIR."$d/$id");
				}
			}
		}
		return TRUE;
	}

	/**
	 * @return bool
	 * @param $session_life string
	 * @desc Does nothing right now.
	 */
	public function Garbage_Collection($session_life)
	{
		return TRUE;
	}


}
?>
