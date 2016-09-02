<?php
/********************************************************************
Site		: emv
Filename	: offer_transmission.inc.php
Author		: DougH
Date		: 03-Aug-2004


	Modification History

Date        Name		Description
-----------	----------	----------------------------------------
03-Aug-2004	DougH		New for CCR II.

********************************************************************/

class Process_Transmissions
{

	var $sql;
	var $method				= NULL;
	var $schedule			= NULL;
	var	$count_processed 	= 0;
	var	$count_error	 	= 0;
	var $job_timestamp;
	var $latest_timestamp	= NULL;


	function Process_Transmissions (&$sql)
	{
		// Set the sql object for queries defined in this class
		$this->sql = &$sql;
	
		$this->raise_exception('BEGIN');
		
		return true;
	}
	
	function Destructor ()
	{
		$this->raise_exception('END');
	}

	function Set_Method ($method)
	{
		$this->method = $method;
		$this->count_processed	= 0;
		$this->count_error		= 0;
		unset($this->latest_timestamp);
	}

	function Set_Schedule ($schedule)
	{
		$this->schedule = $schedule;
	}

	function Track_Date_Highwater($datevalue)
	{
		if ($datevalue > $this->latest_timestamp) $this->latest_timestamp = $datevalue;
	}

	function format_mysql_timestamp($format, $timestamp)
	{
		$timestamp_MMDDYYY_HHMMSS = substr($timestamp,  4, 2)	. '/'	. 
									substr($timestamp,  6, 2)	. '/'	. 
									substr($timestamp,  0, 4)	.
									' '							.
									substr($timestamp,  8, 2)	. ':'	. 
									substr($timestamp, 10, 2)	. ':'	. 
									substr($timestamp, 12, 2);
		$utimestamp = strtotime($timestamp_MMDDYYY_HHMMSS);
		return date($format, $utimestamp);
	}
	
	function raise_exception($type='ERROR', $message_text='Unspecified', $return_option='exit')
	{
		static	$s_err, $s_catastrophic;

		$type			= strtoupper(trim($type));
		$message_text	= trim($message_text);
		$return_option	= strtolower(trim($return_option));

		if ($type == 'START')			$type = 'BEGIN';
		if ($type == 'OK'  )			$type = 'END';
		if ($type == 'OKAY')			$type = 'END';
		if ($type == 'CHECK')			$type = 'CHECKPOINT';
		if ($type == 'INFO')			$type = 'INFORMATIONAL';
		if ($type == 'WARN')			$type = 'WARNING';
		if ($type == 'DIAG')			$type = 'DIAGNOSTIC';
		if ($type == 'ERR' )			$type = 'ERROR';
		if ($type == 'CATASTROPHIC')	$type = 'CATASTROPHIC-ERROR';

		if ($type == 'BEGIN')
		{
			$this->job_timestamp = date("Y-m-d H:i:s");
			$message_text = "Script " . $_SERVER["argv"][0] . " execution started";
			$return_option = 'continue';
		}
		elseif ($type == 'END')
		{
			$return_option = 'exit';
		}
		elseif ($type == 'INFORMATIONAL' || $type == 'WARNING' || $type == 'DIAGNOSTIC' ||
			    $type == 'CHECKPOINT')
		{
			$return_option = 'continue';
		}
		elseif ($type == 'ERROR')
		{
			$s_err = true;
		}
		elseif ($type == 'CATASTROPHIC-ERROR')
		{
			$s_catastrophic = true;
			$return_option = 'exit';
		}

		if (
			 $type != 'END'			&&
			 $type != 'CHECKPOINT'
		   )
		{
			$echo_string = date("d-M-Y H:i:s ");
			if ($type != 'BEGIN' && $type != 'INFORMATIONAL')
			{
				$echo_string .= "$type: ";
			}
			$echo_string .= "$message_text.\r\n";
			echo $echo_string;
		}

		if ( ($type == 'CHECKPOINT' && strlen($this->method) > 0) || $return_option == 'exit' )
		{
			if ($this->latest_timestamp)
			{
				$latest_timestamp_dsp = $this->format_mysql_timestamp('d-M-Y H:i:s', $this->latest_timestamp);
				$latest_timestamp_sql = "'$this->latest_timestamp'";
			}
			else
			{
				$latest_timestamp_dsp = "[no pending transactions]";
				$latest_timestamp_sql = "NULL";
			}
			echo date("d-M-Y H:i:s ") . "$this->method: Latest unprocessed entry seen is for $latest_timestamp_dsp.\r\n";
			echo date("d-M-Y H:i:s ") . "$this->method: $this->count_processed entries processed.\r\n";
			echo date("d-M-Y H:i:s ") . "$this->method: $this->count_error entries resulted in error condition.\r\n";
			if (!$s_catastrophic)
			{
				$termination_code = $s_err ? 'Abnormal' : 'Normal';
				$query = "INSERT into process_log
							(
								process_date,
								xmit_method_name,
								schedule,
								latest_date_seen,
								count_processed,
								count_error,
								termination_code
							)
					  values(
								'$this->job_timestamp', 
								'$this->method',
								'$this->schedule',
								$latest_timestamp_sql,
								$this->count_processed,
								$this->count_error,
								'$termination_code'
							)";
				$rs = $this->sql->Query( DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__) );
				if ($rs !== TRUE)
				{
					echo date("d-M-Y H:i:s ") . "\$query=\r\n$query\r\n";
					echo date("d-M-Y H:i:s ") . "CATASTROPHIC-ERROR: (raise_exception) " .
												"Could not insert row into PROCESS_LOG.\r\n";
					$s_catastrophic = true;
					$return_option = 'exit';
				}
			} // if ~catastrophic

		} // if [type=checkpoint] v [exit]

		if ($return_option == 'exit')
		{
			$exit_condition_adverb = ($s_err || $s_catastrophic) ? 'abnormally' : 'normally';
			echo date("d-M-Y H:i:s ") . "Script " . $_SERVER["argv"][0] . " ended $exit_condition_adverb.\r\n\r\n";
			exit;
		}
		else
		{
			return;
		}
	}


	function substitute_row_variables(&$values_array, &$serialized_value, $row_layout_str, $encode=FALSE)
	{
		$addl_info_values_ary = unserialize($serialized_value);
		$merged_values_ary	  = array_merge($values_array, $addl_info_values_ary);
		reset($merged_values_ary);
		while ( list($key, $value) = each($merged_values_ary) )
		{
			if ($encode) 
			{
				$value = urlencode($value);
			}
			else
			{
				if (strpos($row_layout_str, "'") !== FALSE)
				{
					$value = str_replace("'", "''", $value);
				}
				if (strpos($row_layout_str, '"') !== FALSE)
				{
					$value = str_replace('"', '', $value);
				}
			}

			$row_layout_str = str_replace("@@$key@@", $value, $row_layout_str);
		}
		return $row_layout_str;
	}


	function transmission_register_update($sequence_no, $logged_data=NULL)
	{
		$logged_data_sqlval = (strlen($logged_data) > 0) ? "'" . mysql_escape_string($logged_data) . "'" : "NULL";

		$query = " UPDATE transmission_register
					  SET processed_date = NOW(),
						  logged_data = $logged_data_sqlval
					WHERE sequence_no = $sequence_no
					  AND (processed_date = 0 OR processed_date IS NULL)
				 ";
		$rs = $this->sql->Query( DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__) );
		if ($rs !== TRUE)
		{
			$this->raise_exception('DIAGNOSTIC', "\$query=\r\n$query\r\n");
			$this->raise_exception('ERROR', "(transmission_register_update) Could not update processed " . 
											"status in TRANSMISSION_REGISTER; syntax error, lock wait "  .
											"timeout, or DBMS croaked ($this->method, $sequence_no)");
		}
		return true;
	}


	function ftp_put (	$subtype,
						$local_host,
						$local_path,
						$local_filename,
						$remote_host,
						$remote_path,
						$remote_user,
						$remote_password	)
	{
		// Take care of some preliminaries...
		$subtype = strtoupper(trim($subtype));

		$ftp_stream = ftp_connect($remote_host);
		if($ftp_stream === FALSE)
		{
			$this->raise_exception('ERROR', "(ftp_put) FTP connection error or connection timed out " .
											"($this->method, $remote_host)", 'continue');
			return false;
		}
		$login = ftp_login($ftp_stream, $remote_user, $remote_password);
		if (!$login)
		{
			$this->raise_exception('ERROR', "(ftp_put) FTP login failed; re-check username and/or password " .
											"($this->method, $remote_host)", 'continue');
			ftp_close($ftp_stream);
			return false;
		}
		if ($subtype == 'PASSIVE')
		{
			$cmd = ftp_pasv($ftp_stream, TRUE);
			if (!$cmd)
			{
				$this->raise_exception('ERROR', "(ftp_put) FTP PASV failed ($this->method, $remote_host)", 'continue');
				ftp_close($ftp_stream);
				return false;
			}
		}
		if (!empty($remote_path))
		{
			$cmd = ftp_chdir($ftp_stream, $remote_path);
			if (!$cmd)
			{
				$this->raise_exception('ERROR', "(ftp_put) FTP change directory failed " .
												"($this->method, $remote_host, '$remote_path')", 'continue');
				ftp_close($ftp_stream);
				return false;
			}
		}

		// Process FTP PUT operation...
		$ftp_filename_local  = "$local_path/$local_filename";
		$ftp_filename_remote =  $local_filename;
		$cmd = ftp_put($ftp_stream, $ftp_filename_remote, $ftp_filename_local, FTP_ASCII);
		if (!$cmd)
		{
			$this->raise_exception('ERROR', "(ftp_put) FTP GET failed " .
											"($this->method, $remote_host, '$current_directory', " .
											"$ftp_filename_remote)", 'continue');
			ftp_close($ftp_stream);
			return false;
		}

		$this->raise_exception('INFO', "File $ftp_filename_remote was created the remote server " .
									   "($this->method, $ftp_filename_local)");

		ftp_close($ftp_stream);
		return true;
	}

}
?>