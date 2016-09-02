<?php    
 	
require_once('logsimple.php');
 	
// see: comments by com dot gmail at algofoogle & salisbm at hotmail dot com
//      at http://www.php.net/manual/en/function.stat.php 
defined('S_IFMT')   || define('S_IFMT',  0170000);  // type of file
defined('S_IFIFO')  || define('S_IFIFO', 0010000);  // named pipe (fifo)
defined('S_IFCHR')  || define('S_IFCHR', 0020000);  // character special
defined('S_IFDIR')  || define('S_IFDIR', 0040000);  // directory 
defined('S_IFBLK')  || define('S_IFBLK', 0060000);  // block special
defined('S_IFREG')  || define('S_IFREG', 0100000);  // regular
defined('S_IFLNK')  || define('S_IFLNK', 0120000);  // symbolic link
defined('S_IFSOCK') || define('S_IFSOCK',0140000);  // socket
defined('S_IFWHT')  || define('S_IFWHT', 0160000);  // whiteout

defined('SFTP_1_READ_MODE') || define('SFTP_1_READ_MODE','rb');
defined('SFTP_1_WRITE_MODE') || define('SFTP_1_WRITE_MODE','wb');


class SFTP_1
{
	public $server       = '';
	public $username     = '';
	public $password     = '';
	public $port         = 22;
	
	public $connected    = false;
	public $error_state  = false;
	public $error_msg    = 'nothing done yet';
	public $buffered_msg = '';
	
	public $conn         = null;
	public $sftp         = null;
	
	
	public function __construct( $server='', $username='', $password='', $port=22 )
	{
		$this->server   = $server;
		$this->username = $username;
		$this->password = $password;
		$this->port     = $port;
	}


	public function is_connected() { return $this->connected; }

	
	public function get_error_msg() { return $this->error_msg; }

	
	public function get_buffered_msg() { return $this->buffered_msg; }


	public function connect()
	{
		ob_start();

		$this->connected = false;
		$this->clear_error();
		
		if ( ! $this->error_state && ! $this->conn = ssh2_connect($this->server, $this->port) )
			$this->error(__METHOD__, __LINE__, "ssh2_connect FAILED, server=$this->server, port={$this->port}");

		if ( ! $this->error_state && ! $result = ssh2_auth_password($this->conn, $this->username, $this->password) )
			$this->error(__METHOD__, __LINE__, "ssh2_auth_password FAILED, conn='$this->conn', username='$this->username', password='$this->password' ");

		if ( ! $this->error_state && ! $this->sftp = ssh2_sftp($this->conn) )
			$this->error(__METHOD__, __LINE__, "ssh2_sftp FAILED ");

		$this->buffered_msg = trim(ob_get_contents());
		ob_end_clean();
		
		if ( $this->error_state || $this->buffered_msg != '' )
		{
			$this->error(__METHOD__, __LINE__, $this->error_msg . '; buffered messages=' . logsimpledump($this->buffered_msg) );
			return false;
		}

		$this->connected = true;
		return true;
	}


	public function check_connection()
	{
		if ( ! $this->connected ) return $this->connect();
		return true;
	}

	
	public function get_file_list( $remote_file_name='' )  // $dest_file_name should be '' for root directory.
	{
		if ( !$this->check_connection() ) return false;

		$file_array = array();
	
		ob_start();

		if ( ! $this->error_state && ! $stream = opendir("ssh2.sftp://$this->sftp/$remote_file_name") )
			$this->error(__METHOD__, __LINE__, "opendir FAILED ");
			
		if ( ! $this->error_state )
		{
			while(false !== ($file = readdir($stream)))
			{
				// is_dir() does NOT work over secure ftp!!!
				$statinfo = ssh2_sftp_stat($this->sftp, $file); // does not seem to work well on remote subdirectories
				$file_array[$file] = $statinfo;                 // but seems reliable on remote root directory.
				// logsimplewrite("get_file_list: file=$file, statinfo=" . logsimpledump($statinfo));
			}
		}

		if ( $stream ) closedir($stream);
	
		$this->buffered_msg = trim(ob_get_contents());
		ob_end_clean();
		
		if ( $this->error_state )
		{
			$this->error(__METHOD__, __LINE__, $this->error_msg . '; buffered messages=' . logsimpledump($this->buffered_msg) );
			return false;
		}

		logsimplewrite("list success, file_array=" . logsimpledump($file_array));  // see: /tmp/logsimple.log
		return $file_array;
	}


	public function put( $remote_file_name='', $string_data='', $write_mode=SFTP_1_WRITE_MODE)
	{
		if ( !$this->check_connection() ) return false;
	
		ob_start();

		if ( ! $this->error_state && ! $stream = fopen("ssh2.sftp://$this->sftp/$remote_file_name", $write_mode) )
			$this->error(__METHOD__, __LINE__, "fopen FAILED, remote_file_name=$remote_file_name");
			
		if ( ! $this->error_state && ! fwrite($stream, $string_data) )
			$this->error(__METHOD__, __LINE__, "fwrite FAILED, remote_file_name=$remote_file_name, data=$string_data");

		if ( $stream ) fclose($stream);
	
		$this->buffered_msg = trim(ob_get_contents());
		ob_end_clean();
		
		if ( $this->error_state || $this->buffered_msg != '' )
		{
			$this->error(__METHOD__, __LINE__, $this->error_msg . '; buffered messages=' . logsimpledump($this->buffered_msg) );
			return false;
		}

		logsimplewrite("put success, remote_file_name=$remote_file_name");    // see: /tmp/logsimple.log
		return true;
	}


	public function put_from_file( $remote_file_name='', $local_file_name='', $write_mode=SFTP_1_WRITE_MODE, $read_mode=SFTP_1_READ_MODE )
	{
		if ( !$this->check_connection() ) return false;

		$local_file_data = '';
	
		ob_start();

		if ( ! $this->error_state && ! $stream = fopen("ssh2.sftp://$this->sftp/$remote_file_name", $write_mode) )
			$this->error(__METHOD__, __LINE__, "fopen remote file ($remote_file_name) FAILED ");
			
		if ( ! $this->error_state && ! $local_file_handle = fopen($local_file_name, $read_mode) )
			$this->error(__METHOD__, __LINE__, "fopen local file ($local_file_name) FAILED ");

		if (  ! $this->error_state && $local_file_handle )
		{
			do
			{
				$local_file_data = fread($local_file_handle, 1024);
				fwrite($stream, $local_file_data) || $this->error(__METHOD__, __LINE__, "fwrite FAILED, remote file=$remote_file_name, data=$local_file_data");
			}
			while( ! $this->error_state && !feof($local_file_handle) && strlen($local_file_data) > 0 );
		}

		if ( $stream ) fclose($stream);
		if ( $local_file_handle ) fclose($local_file_handle);
	
		$this->buffered_msg = trim(ob_get_contents());
		ob_end_clean();
		
		if ( $this->error_state || $this->buffered_msg != '' )
		{
			$this->error(__METHOD__, __LINE__, $this->error_msg . '; buffered messages=' . logsimpledump($this->buffered_msg) );
			return false;
		}

		logsimplewrite("put_from_file success, remote_file_name=$remote_file_name, local_file_name=$local_file_name");    // see: /tmp/logsimple.log
		return true;
	}


	// get a remote file
	public function get( $remote_file_name='', $max_length=1024, $read_mode=SFTP_1_READ_MODE, $check_existence_only=false )
	{
		if ( !$this->check_connection() ) return false;
	
		$contents = $data_received = '';

		ob_start();

		if ( ! $this->error_state && ! $stream = fopen("ssh2.sftp://$this->sftp/$remote_file_name", $read_mode) )
			$this->error(__METHOD__, __LINE__, "fopen FAILED ");
			
		if ( ! $this->error_state && ! $check_existence_only ) // if only checking existence of file then don't read data.
		{
			do
			{
				$data_received = fread($stream, $max_length);
				$contents .= $data_received;
			}
			while ( !feof($stream) && isset($data_received) && strlen($data_received) > 0 );
		}
				
		if ( $stream ) fclose($stream);
	
		$this->buffered_msg = trim(ob_get_contents());
		ob_end_clean();
		
		if ( $this->error_state || $this->buffered_msg != '')
		{
			$this->error(__METHOD__, __LINE__, $this->error_msg . '; buffered messages=' . logsimpledump($this->buffered_msg) );
			return false;
		}

		logsimplewrite("get success, remote_file_name=$remote_file_name, contents=$contents");    // see: /tmp/logsimple.log
		return $contents;
	}


	public function are_files_identical( $remote_file_name='', $local_file_name='', $read_mode=SFTP_1_READ_MODE )
	{
		if ( !$this->check_connection() ) return false;

		$local_chunk = '';
		$remote_chunk = '';

		ob_start();

		if ( ! $this->error_state && ! $local_file_handle = fopen($local_file_name, $read_mode) )
			$this->error(__METHOD__, __LINE__, "fopen local file ($local_file_name) FAILED ");

		if ( ! $this->error_state && ! $remote_file_handle = fopen("ssh2.sftp://$this->sftp/$remote_file_name", $read_mode) )
			$this->error(__METHOD__, __LINE__, "fopen remote file ($remote_file_name) FAILED ");
			
		if (  ! $this->error_state && $local_file_handle && $remote_file_handle )
		{
			do
			{
				$local_chunk = fread($local_file_handle, 1024);
				$remote_chunk = fread($remote_file_handle, 1024);
				$local_chunk === $remote_chunk || $this->error(__METHOD__, __LINE__, "files are NOT equal: remote=$remote_file_name, local=$local_file_name, remote_chunk=$remote_chunk, local_chunk=$local_chunk");
			}
			while(  !$this->error_state &&
				!feof($local_file_handle) && !feof($remote_file_handle) &&
				isset($local_chunk) && isset($remote_chunk) &&
				strlen($local_chunk) > 0 && strlen($remote_chunk) > 0 );
		}
		
		if ( $local_file_handle ) fclose($local_file_handle);
		if ( $remote_file_handle ) fclose($remote_file_handle);

		$this->buffered_msg = trim(ob_get_contents());
		ob_end_clean();
		
		if ( $this->error_state || $this->buffered_msg != '' )
		{
			$this->error(__METHOD__, __LINE__, $this->error_msg . '; buffered messages=' . logsimpledump($this->buffered_msg) );
			return false;
		}

		logsimplewrite("files ARE equal: remote=$remote_file_name, local=$local_file_name");    // see: /tmp/logsimple.log
		return true;
	}


	public function mkdir( $remote_file_name='', $write_mode=SFTP_1_WRITE_MODE)
	{
		if ( !$this->check_connection() ) return false;

		ob_start();

		if ( ! $this->error_state && ! ssh2_sftp_mkdir($this->sftp, $remote_file_name) )
			$this->error(__METHOD__, __LINE__, "mkdir FAILED, remote_file_name=$remote_file_name");

		if ( $stream ) fclose($stream);
	
		$this->buffered_msg = trim(ob_get_contents());
		ob_end_clean();
		
		if ( $this->error_state || $this->buffered_msg != '' )
		{
			$this->error(__METHOD__, __LINE__, $this->error_msg . '; buffered messages=' . logsimpledump($this->buffered_msg) );
			return false;
		}

		logsimplewrite("mkdir success, remote_file_name=$remote_file_name");    // see: /tmp/logsimple.log
		return true;
	}


	public function rmdir( $remote_file_name='', $write_mode=SFTP_1_WRITE_MODE)
	{
		if ( !$this->check_connection() ) return false;

		ob_start();

		if ( ! $this->error_state && ! ssh2_sftp_rmdir($this->sftp, $remote_file_name) )
			$this->error(__METHOD__, __LINE__, "rmdir FAILED, remote_file_name=$remote_file_name");

		if ( $stream ) fclose($stream);
	
		$this->buffered_msg = trim(ob_get_contents());
		ob_end_clean();
		
		if ( $this->error_state || $this->buffered_msg != '' )
		{
			$this->error(__METHOD__, __LINE__, $this->error_msg . '; buffered messages=' . logsimpledump($this->buffered_msg) );
			return false;
		}

		logsimplewrite("rmdir success, remote_file_name=$remote_file_name");    // see: /tmp/logsimple.log
		return true;
	}


	public function unlink( $remote_file_name='', $write_mode=SFTP_1_WRITE_MODE)
	{
		if ( !$this->check_connection() ) return false;

		ob_start();

		if ( ! $this->error_state && ! ssh2_sftp_unlink($this->sftp, $remote_file_name) )
			$this->error(__METHOD__, __LINE__, "unlink FAILED, remote_file_name=$remote_file_name");

		if ( $stream ) fclose($stream);
	
		$this->buffered_msg = trim(ob_get_contents());
		ob_end_clean();
		
		if ( $this->error_state || $this->buffered_msg != '' )
		{
			$this->error(__METHOD__, __LINE__, $this->error_msg . '; buffered messages=' . logsimpledump($this->buffered_msg) );
			return false;
		}

		logsimplewrite("unlink success, remote_file_name=$remote_file_name");    // see: /tmp/logsimple.log
		return true;
	}


	public function error( $method, $line, $msg )
	{
		$this->set_error("$method::$line: $msg");
	}

	public function set_error( $msg )
	{
		logsimplewrite("set_error: msg=$msg" );    // see: /tmp/logsimple.log
		$this->error_state = true;
		$this->error_msg = $msg;
	}

	public function clear_error()
	{
		$this->error_state = false;
		$this->error_msg = 'ok';
	}
	
	public function get_mode   ( $statinfo_or_mode ) { return is_array($statinfo_or_mode) ? ($statinfo_or_mode['mode'] & S_IFMT) : ($statinfo_or_mode & S_IFMT); }
	public function is_dir     ( $statinfo_or_mode ) { return ($this->get_mode($statinfo_or_mode) == S_IFDIR);  }
	public function is_pipe    ( $statinfo_or_mode ) { return ($this->get_mode($statinfo_or_mode) == S_IFIFO);  }
	public function is_regular ( $statinfo_or_mode ) { return ($this->get_mode($statinfo_or_mode) == S_IFREG);  }
	public function is_link    ( $statinfo_or_mode ) { return ($this->get_mode($statinfo_or_mode) == S_IFLNK);  }
	public function is_block   ( $statinfo_or_mode ) { return ($this->get_mode($statinfo_or_mode) == S_IFBLK);  }
	public function is_socket  ( $statinfo_or_mode ) { return ($this->get_mode($statinfo_or_mode) == S_IFSOCK); }
	public function is_char    ( $statinfo_or_mode ) { return ($this->get_mode($statinfo_or_mode) == S_IFCHR);  }
	public function is_whiteout( $statinfo_or_mode ) { return ($this->get_mode($statinfo_or_mode) == S_IFWHT);  }

	public function get_type_display( $statinfo_or_mode, $filename='' ) // filename is just to help with debugging
	{
		$result = 'unk';

		switch( true )
		{
			case $this->is_dir($statinfo_or_mode)      : $result = 'DIR'         ; break;
			case $this->is_pipe($statinfo_or_mode)     : $result = 'PIPE'        ; break;
			case $this->is_regular($statinfo_or_mode)  : $result = 'REGULAR'     ; break;
			case $this->is_link($statinfo_or_mode)     : $result = 'LINK'        ; break;
			case $this->is_block($statinfo_or_mode)    : $result = 'BLOCK'       ; break;
			case $this->is_socket($statinfo_or_mode)   : $result = 'SOCKET'      ; break;
			case $this->is_char($statinfo_or_mode)     : $result = 'CHAR'        ; break;
			case $this->is_whiteout($statinfo_or_mode) : $result = 'WHITEOUT'    ; break;
			default                                    : $result = 'unknown'     ; break;
		}

		return $result;
	}

}

?>
