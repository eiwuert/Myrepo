<?php
/********************************************************************
Site		: emv
Filename	: offer_transmission.php
Author		: DougH
Date		: 03-Aug-2004


	Modification History

Date        Name		Description
-----------	----------	----------------------------------------
03-Aug-2004	DougH		New for CCR II.

********************************************************************/

declare(ticks=1);

set_time_limit(0);
ob_implicit_flush(true);
set_magic_quotes_runtime(0);

require_once ("/virtualhosts/lib/mysql.3.php");
require_once ("/virtualhosts/cronjobs/includes/offer_transmission.inc.php");
require_once ("/virtualhosts/site_config/server.cfg.php");

define("HTTP_OPEN_RETRY_LIMIT",	 5);	// iterations
define("HTTP_OPEN_RETRY_DELAY",	10);	// seconds

$Px = new Process_Transmissions($sql);

// Fetch mandatory execution parm designating environment to process (live vs. RC)
//	and define database to use accordingly
if ($_SERVER["argc"] < 2 || strlen($_SERVER["argv"][1]) == 0)
{
	$Px->raise_exception('CATASTROPHIC', __FILE__ . " called without required environment parm");
}
$environment_parm = strtoupper($_SERVER["argv"][1]);
switch($environment_parm)
{
	case 'LIVE':
		define(DB_NAME, 'emv_visitor');
		break;
	case 'RC':
		define(DB_NAME, 'rc_emv_visitor');
		break;
	default:
		$Px->raise_exception('CATASTROPHIC', __FILE__ . " called with invalid environment parm ('$environment_parm')");
}
$Px->raise_exception('INFO', "Execution requested against $environment_parm database ('" . DB_NAME . "') on host " . 
							 $_ENV["HOSTNAME"]);

// Fetch mandatory execution parm designating schedule to process
if ($_SERVER["argc"] < 3 || strlen($_SERVER["argv"][2]) == 0)
{
	$Px->raise_exception('CATASTROPHIC', __FILE__ . " called without required schedule parm");
}
$schedule_parm = $_SERVER["argv"][2];

function signal_handler($signal)
{
	Global $Px;

	switch($signal)
	{
		case SIGTERM:
			// Handle graceful termination tasks
			$Px->raise_exception('ERROR', "Process termination requested");
			break;
		case SIGINT:
			// Handle graceful termination tasks
			$Px->raise_exception('ERROR', "CTRL-C interrupt");
			break;
		default:
			// Handle all other signals
	}
}

pcntl_signal(SIGTERM, "signal_handler");
pcntl_signal(SIGINT , "signal_handler");

// Mainline

$query =	"SELECT code 
			   FROM code 
			  WHERE code_type = 'SCHEDULE' 
				AND code = '$schedule_parm'
				AND upper(code) <> 'NEVER'
			";
$rs = $sql->Query( DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__) );
if (!is_resource($rs))
{
	$Px->raise_exception('CATASTROPHIC',"CODE query failed for this schedule. Probable syntax "	.
										"error or DBMS croaked");
}
if ($sql->Row_Count($rs) < 1)
{
	$Px->raise_exception('CATASTROPHIC',"Invalid schedule parm supplied ('$schedule_parm')");
}

$Px->raise_exception('INFO', "Execution requested for processing schedule '$schedule_parm'");
$Px->Set_Schedule($schedule_parm);

$query =	"SELECT DISTINCT tr.xmit_method_name AS xmit_method_name
						FROM transmission_register tr
				  INNER JOIN transmission_method   tm
						  ON tr.xmit_method_name = tm.xmit_method_name
					   WHERE (tr.processed_date = 0 OR tr.processed_date IS NULL)
						 AND tm.schedule = '$schedule_parm'
			";
$rs = $sql->Query( DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__) );
if (!is_resource($rs))
{
	$Px->raise_exception('CATASTROPHIC',"Distinct method query failed for this schedule. Probable syntax "	.
										"error or DBMS croaked");
}
$method_count = $sql->Row_Count($rs);
$method_list = array();
while (($rec = $sql->Fetch_Array_Row($rs)) !== FALSE)
{
	$method_list[] = $rec["xmit_method_name"];
}
$Px->raise_exception('INFO', "Methods to be processed during this execution: (" . join(", ", $method_list) . ")");
$sql->Free_Result($rs);

// Retrieve xmission method details for all methods to be processed in this run
$method_ary	 = array();
for ($i=0; $i<$method_count; $i++)
{
	$query = 	"SELECT
					xmit_method_name,
					type,
					subtype,
					local_host,
					local_path,
					local_archive_path,
					local_file_pfx,
					remote_host,
					remote_path,
					remote_user,
					remote_password,
					interface_properties,
					content_format,
					processing_script,
					processing_function,
					response_fingerprint,
					target_email,
					target_url
			   FROM transmission_method
			  WHERE xmit_method_name = '" . mysql_escape_string($method_list[$i]) . "'
				";
	$rs = $sql->Query( DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__) );
	if (!is_resource($rs))
	{
		$Px->raise_exception('DIAG', "\n\$query = $query\n");
		$Px->raise_exception('CATASTROPHIC',"Method details query failed for this schedule. Probable syntax "	.
											"error or DBMS croaked");
	}
	if (($rec = $sql->Fetch_Array_Row($rs)) === FALSE)
	{
		$Px->raise_exception('CATASTROPHIC',"Row deleted from TRANSMISSION_METHOD by another process since "	.
											"it was retrieved earlier in this jobstream (" . $method_list[$i] . ")");
	}
	$method_ary[$i]["xmit_method_name"]		= $rec["xmit_method_name"];
	$method_ary[$i]["type"] 				= $rec["type"];
	$method_ary[$i]["subtype"] 				= $rec["subtype"];
	$method_ary[$i]["local_host"]			= $rec["local_host"];
	$method_ary[$i]["local_path"]			= $rec["local_path"];
	$method_ary[$i]["local_archive_path"]	= $rec["local_archive_path"];
	$method_ary[$i]["local_file_pfx"]		= $rec["local_file_pfx"];
	$method_ary[$i]["remote_host"]			= $rec["remote_host"];
	$method_ary[$i]["remote_path"]			= $rec["remote_path"];
	$method_ary[$i]["remote_user"]			= $rec["remote_user"];
	$method_ary[$i]["remote_password"]		= $rec["remote_password"];
	$method_ary[$i]["interface_properties"]	= $rec["interface_properties"];
	$method_ary[$i]["content_format"] 		= $rec["content_format"];
	$method_ary[$i]["processing_script"]	= $rec["processing_script"];
	$method_ary[$i]["processing_function"]	= $rec["processing_function"];
	$method_ary[$i]["response_fingerprint"]	= $rec["response_fingerprint"];
	$method_ary[$i]["target_email"] 		= $rec["target_email"];
	$method_ary[$i]["target_url"] 			= $rec["target_url"];

	$sql->Free_Result($rs);
}

// Main loop - one iteration per method
for ($i=0; $i<$method_count; $i++)
{
	$Px->raise_exception('CHECKPOINT');
	$xmit_method = $method_ary[$i]["xmit_method_name"];
	$xmit_type	 = $method_ary[$i]["type"];
	$Px->raise_exception('INFO', "Processing method '$xmit_method', type '$xmit_type'");
	$Px->Set_Method($xmit_method);
	
	// Pre-gather transmission details for all pending transmission_register rows for this method
	$query =	"SELECT tr.sequence_no			AS sequence_no,
						tr.created_date			AS acceptance_date,
						oa.addl_info_serialized	AS addl_info_serialized,
						 u.email				AS email,
						 u.gender				AS gender,
						 u.name_first			AS name_first,
						 u.name_last			AS name_last,
						 u.address_street		AS address_street,
						 u.address_unit			AS address_unit,
						 u.city					AS city,
						 u.state				AS state,
						 u.zip_code				AS zip_code,
						 u.phone_areacode		AS phone_areacode,
						 u.phone_prefix			AS phone_prefix,
						 u.phone_suffix			AS phone_suffix,
						 u.birth_date			AS birth_date
				   FROM transmission_register tr
			 INNER JOIN offer_accepted oa
					 ON tr.user_id	= oa.user_id
					AND tr.offer_id	= oa.offer_id
			 INNER JOIN user u
					 ON tr.user_id	=  u.user_id
				  WHERE tr.xmit_method_name = '" . mysql_escape_string($xmit_method) . "'
					AND (tr.processed_date = 0 OR tr.processed_date IS NULL)
				";
	$rs = $sql->Query( DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__) );
	if (!is_resource($rs))
	{
		$Px->raise_exception('DIAGNOSTIC', "\$query=\r\n$query\r\n");
		$Px->raise_exception('ERROR',"Transmission details query failed for method $xmit_method. Probable syntax "	.
									 "error or DBMS croaked");
	}
	$xmit_row_count = $sql->Row_Count($rs);
	$xmit_dtl = array();
	for ($j=0; $j<$xmit_row_count; $j++)
	{
		$rec = $sql->Fetch_Array_Row($rs);

		$xmit_dtl[$j]["sequence_no"]			= $rec["sequence_no"];
		$xmit_dtl[$j]["acceptance_date"]		= $rec["acceptance_date"];
		$xmit_dtl[$j]["addl_info_serialized"]	= $rec["addl_info_serialized"];
		$xmit_dtl[$j]["email"]					= $rec["email"];
		$xmit_dtl[$j]["gender"]					= $rec["gender"];
		$xmit_dtl[$j]["name_first"]				= $rec["name_first"];
		$xmit_dtl[$j]["name_last"]				= $rec["name_last"];
		$xmit_dtl[$j]["address_street"]			= $rec["address_street"];
		$xmit_dtl[$j]["address_unit"]			= $rec["address_unit"];
		$xmit_dtl[$j]["city"]					= $rec["city"];
		$xmit_dtl[$j]["state"]					= $rec["state"];
		$xmit_dtl[$j]["zip_code"]				= $rec["zip_code"];
		$xmit_dtl[$j]["phone_areacode"]			= $rec["phone_areacode"];
		$xmit_dtl[$j]["phone_prefix"]			= $rec["phone_prefix"];
		$xmit_dtl[$j]["phone_suffix"]			= $rec["phone_suffix"];
		$xmit_dtl[$j]["birth_date"]				= $rec["birth_date"];

		$xmit_dtl[$j]["address_1"]	=	$xmit_dtl[$j]["address_unit"] ? 
										$xmit_dtl[$j]["address_street"] . ' #' . $xmit_dtl[$j]["address_unit"] : 
										$xmit_dtl[$j]["address_street"];

		$xmit_dtl[$j]["dob_yr"]	= substr($xmit_dtl[$j]["birth_date"], 0, 4);
		$xmit_dtl[$j]["dob_mo"]	= substr($xmit_dtl[$j]["birth_date"], 5, 2);
		$xmit_dtl[$j]["dob_dy"]	= substr($xmit_dtl[$j]["birth_date"], 8, 2);
		
		$Px->Track_Date_Highwater($xmit_dtl[$j]["acceptance_date"]);
	}
	$sql->Free_Result($rs);

	switch($xmit_type)
	{
		case 'HTTP_GET':
			// Handle HTTP GET transmission type (one-by-one)
	
			// Make GET request per row based on target URL and substitution parms for this method...
			for ($j=0; $j<$xmit_row_count; $j++)
			{
				// Resolve values for interface row variables
				$target_url = trim($method_ary[$i]["target_url"]);
				$target_url = $Px->substitute_row_variables($xmit_dtl[$j], $xmit_dtl[$j]["addl_info_serialized"], $target_url, TRUE);
				// Open HTTP stream to target url
				$retry_count = 0;
				do {
					$http_rs = fopen($target_url, 'rb');
					$retry_count++;
					if  ($http_rs === FALSE && $retry_count < HTTP_OPEN_RETRY_LIMIT)
					{
						$Px->raise_exception('WARN', "Open to HTTP stream '$target_url' failed; will try again in " .
													 HTTP_OPEN_RETRY_DELAY . " seconds...");
						sleep(HTTP_OPEN_RETRY_DELAY);
					}
				} while ($http_rs === FALSE && $retry_count < HTTP_OPEN_RETRY_LIMIT);
				if ($http_rs === FALSE)
				{
					$Px->raise_exception('ERROR', "Open to HTTP stream '$target_url' failed after " . 
													HTTP_OPEN_RETRY_LIMIT . " attempts (" . 
												 	$xmit_dtl[$j]["sequence_no"] . 
												 	", '$xmit_method', '$xmit_type')", 'continue');
					$Px->count_error++;
				}
				else
				{
					$http_response = "";
					do {
						$chunk = fread($http_rs, 1024);
						$http_response .= $chunk;
					} while (strlen($chunk) > 0);
					fclose($http_rs);
					// Check if response is nonempty and compares favorably to the fingerprint 
					//	stored in transmission_method
					$eval_str = str_replace("@@RESPONSE@@", "\$http_response", $method_ary[$i]["response_fingerprint"]);

					if ( strlen(trim($http_response)) == 0 || eval($eval_str) !== TRUE )
					{
						$Px->raise_exception('WARN', "Remote HTTP server did not respond affirmatively or responded with unexpected data " .
													  "('$target_url', '$xmit_method', '$xmit_type', '$http_response')");
						$negative_log_data = 'UNIDENTIFIED RESPONSE: ' . trim($http_response);
						// Flag transmission_register row as "processed" even though data may have been rejected by receiver
						$Px->transmission_register_update($xmit_dtl[$j]["sequence_no"], $negative_log_data);
						$Px->count_processed++;
					}
					else
					{
						$Px->raise_exception('INFO', "GET successful: remote HTTP server responded with '$http_response' " .
													 "('$xmit_method', '$xmit_type')");
						// Flag transmission_register row as processed
						$Px->transmission_register_update($xmit_dtl[$j]["sequence_no"], $target_url);
						$Px->count_processed++;
					}
				}
			}
			break;
		case 'FTP_PUT':
			// Handle FTP PUT transmission type (batched)
			
			// Create and open local file with unique name based on local_file_pfx...
			//	 but, first, we assert sufficient permissions in local archive directory;
			//	 we don't use is_writable() here due to a past history of PHP bugs in this area
			$local_filename_qualified = $method_ary[$i]["local_archive_path"] . '/' . '__PermissionsCheck.txt';
			@unlink($local_filename_qualified);
			$rs_lcl_crt = fopen($local_filename_qualified, 'x');
			if ($rs_lcl_crt === FALSE)
			{
				$Px->raise_exception('ERROR', "Insufficient permissions to create or delete local archive file in directory '" . 
												$method_ary[$i]["local_archive_path"] . "' on server" . $_SERVER["HOSTNAME"]);
			}
			unlink($local_filename_qualified);
			//	 next, we assert sufficient permissions in local directory
			$local_filename_qualified = $method_ary[$i]["local_path"] . '/' . '__PermissionsCheck.txt';
			@unlink($local_filename_qualified);
			$rs_lcl_crt = fopen($local_filename_qualified, 'x');
			if ($rs_lcl_crt === FALSE)
			{
				$Px->raise_exception('ERROR', "Insufficient permissions to create or delete local file in directory '" . 
												$method_ary[$i]["local_path"] . "' on server" . $_SERVER["HOSTNAME"]);
			}
			unlink($local_filename_qualified);
			$retry_count = 0;
			do {
				list($microseconds, $seconds) = explode(" ", microtime());
				$wk_suffix_1 = (float)$seconds;
				$wk_suffix_2 = 1000000 * (float)$microseconds;
				$wk_suffix	 = $wk_suffix_1 . $wk_suffix_2;
				$local_file_sfx = '_' . $wk_suffix;
				$local_filename = $method_ary[$i]["local_file_pfx"] . $local_file_sfx;
				$local_filename_qualified = $method_ary[$i]["local_path"] . '/' . $local_filename;
				$rs_lcl_crt = fopen($local_filename_qualified, 'xb');
				$retry_count++;
			} while ($rs_lcl_crt === FALSE && $retry_count < 10);
			if ($rs_lcl_crt === FALSE)
			{
				// Never ??!?
				$Px->raise_exception('ERROR', "Error creating a local file with prefix '" . 
												$method_ary[$i]["local_file_pfx"] . "' in directory '" . 
												$method_ary[$i]["local_path"] . "' on server" . $_SERVER["HOSTNAME"]);
			}
			$Px->raise_exception('INFO', "Created local file '$local_filename_qualified'");
			
			// Get layouts for detail record and... the header, too, if defined in the interface properties...
			$properties_str = trim($method_ary[$i]["interface_properties"]);
			$xmit_header_layout = "";
			$xmit_detail_layout = "";
			if ( preg_match("/HEADER[ ]*=[ ]*%%(.+)%%/isU", $properties_str, $properties_matches) )
			{
				$xmit_header_layout = $properties_matches[1];
			}
			if ( preg_match("/DETAIL[ ]*=[ ]*%%(.+)%%/isU", $properties_str, $properties_matches) )
			{
				$xmit_detail_layout = $properties_matches[1];
			}
			if ( strlen($xmit_detail_layout) == 0 && strlen($xmit_header_layout) == 0)
			{
				$xmit_detail_layout = $properties_str;
			}
			if ( strlen($xmit_header_layout) > 0)
			{
				$xmit_header_layout = str_replace('\t', "\t", $xmit_header_layout);
				$xmit_output_line = $xmit_header_layout . "\n";
				$write_result = fwrite($rs_lcl_crt, $xmit_output_line);
				if ($write_result === FALSE)
				{
					$Px->raise_exception('ERROR', "Write to file '$local_filename_qualified' failed when writing header " . 
												  "('$xmit_method', '$xmit_type')");
				}
			}
			$xmit_detail_layout = str_replace('\t', "\t", $xmit_detail_layout);
				
			// Write out all data per row based on format, substitution variables in interface properties...
			for ($j=0; $j<$xmit_row_count; $j++)
			{
				// Resolve values for interface row variables
				$xmit_output_line = $Px->substitute_row_variables($xmit_dtl[$j], $xmit_dtl[$j]["addl_info_serialized"], 
																  $xmit_detail_layout);
				$xmit_output_line .= "\n";
				$write_result = fwrite($rs_lcl_crt, $xmit_output_line);
				if ($write_result === FALSE)
				{
					$Px->raise_exception('ERROR', "Write to file '$local_filename_qualified' failed (" . 
												  $xmit_dtl[$j]["sequence_no"] . ", '$xmit_method', '$xmit_type')");
				}
				// Flag transmission_register row as processed
				$upd_result = $Px->transmission_register_update($xmit_dtl[$j]["sequence_no"], $local_filename);
				$Px->count_processed++;
			}

			// Close local file
			$close_result = fclose($rs_lcl_crt);
			if ($close_result === FALSE)
			{
					$Px->raise_exception('ERROR', "Close of file '$local_filename_qualified' failed " .
												  "('$xmit_method', '$xmit_type')");
			}
			// Make FTP PUT call
			$type_result = $Px->ftp_put(
										$method_ary[$i]["subtype"],
										$method_ary[$i]["local_host"],
										$method_ary[$i]["local_path"],
										$local_filename,
										$method_ary[$i]["remote_host"],
										$method_ary[$i]["remote_path"],
										$method_ary[$i]["remote_user"],
										$method_ary[$i]["remote_password"]
									   );
			if ($type_result === FALSE)
			{
				$Px->raise_exception('ERROR', "FTP PUT operation was not successful; see previously listed errors", 'continue');
			}
			else
			{
				// FTP was successful, so we move local file to specified archive directory
				$archive_filename_qualified = $method_ary[$i]["local_archive_path"] . '/' . $local_filename;
				$rename_result = rename($local_filename_qualified, $archive_filename_qualified);
				if ($rename_result === FALSE)
				{
					// Never?
					$Px->raise_exception('ERROR', "Local rename operation was not successful. " .
												  "File $local_filename_qualified not archived to " . 
												  $method_ary[$i]["local_archive_path"], 'continue');
				}
			}
			break;
		case 'NO-OP':
			// Do nothing... but count as "processed" in transmission_register row
			break;
		default:
			// Handle all other transmission types
			$Px->raise_exception('ERROR', "Unimplemented transmission type for method $xmit_method ('$xmit_type')", 'continue');
	} // switch
} // for $i method loop

// Wind-down processing
$Px->Destructor();	// Pre-PHP5

?>