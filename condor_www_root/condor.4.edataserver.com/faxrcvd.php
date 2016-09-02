<?php
	/**
	 *
	 * PHP replacement for the HylaFax faxrcvd script, used
	 * to upload received faxes into Condor.
	 *
	 * This script is called by HylaFax with the following
	 * arguments:
	 *
	 * - File
	 * - Device ID
	 * - Comm ID
	 * - Error Message
	 * - [Caller ID Number]
	 * - [Caller ID Name]
	 *
	 * @author Andrew Minerd
	 * @date: 2006-03-28
	 *
	 */

	//A lot of the server specific defines are
	//setup in the ACTUAL /var/spool/fax/bin/faxrcvd which
	//then just requires this

	require(DIR_CONDOR.'/lib/hylafax_routing.php');	
	require(DIR_LIB.'/callback.1.php');
	require(DIR_LIB.'/reported_exception.1.php');
	require(DIR_CONDOR.'/lib/condor_exception.php');
	
	// make sure we have the correct number of arguments
	if ($argc < 5)
	{
		die("Usage: {$argv[0]} file device-id comm-id error-msg [cid-number] [cid-name]\n");
	}
	
	
	// pull out our arguments
	list($script, $file_name, $device_id, $comm_id, $error) = $argv;
	$cid_number = ($argc >= 6) ? $argv[5] : NULL;
	$cid_name = ($argc >= 7) ? $argv[6] : NULL;
	
	// assume we fail
	$result = FALSE;
	
	$routing = new HylaFax_Routing(EXECUTION_MODE);
	if(defined('DID_ROUTING') && DID_ROUTING === true)
	{
		//Use the DID passed into the Caller ID information 
		//to route the fax to the appropriate Condor User.
		if(isset($cid_number) && !is_null($cid_number))
		{
			list($incoming_url, $number) = $routing->Find_Incoming_By_DID($cid_number);
		}
		else 
		{
			//We couldn't associate that DID with a User
			//so fail and hopefully someone can figure out
			//who to route this fax to.
			Report("Could not route by cid: $cid_number");
		}
	}
	else 
	{
		$number = $routing->Find_Incoming_Number($device_id);
		if($number)
		{
			$incoming_url = $routing->Find_Incoming_URL($number);
		}
		else 
		{
			Report("Could not route by device: $device_id");
		}
	}
	if ($incoming_url !== FALSE)
	{
		//Send as a TIFF. It's easier to manipulate than a PDF
		$content_type = 'image/tiff';

		// There's some issues here
		// Hylafax does not always produce a valid tiff,
		// this may be a problem with the modem's hardware, firmware, or the current lunar cycle
		// Agents are complaining that the corrupted PDFs are freezing their stations, so let's check them
		if (!Verify_Tiff($file_name) && file_exists(CORRUPT_TIFF))
		{
			$data = file_get_contents(CORRUPT_TIFF);
		}
		else
		{	
			$data = file_get_contents($file_name);
		}
	
		try
		{
			//Grab the page count from the faxinfo program [SS] 
			$info = Fax_Info($file_name);
			$tokens = array(
				'modem' => $device_id,
				'sender' => $cid_number,
				'number' => $number,
				'content_type' => $content_type,
				'document' => $data,
			);
	
			$tokens = array_merge($tokens,$info);
			$callback = new Callback($incoming_url);
			$id = $callback->Process($tokens);
			
			// only succeed if we get a database ID back
			$result = is_numeric($id);
			
		}
		catch (Exception $e)
		{
			// report this exception
			Report($e->getMessage());
		}
	}
	else 
	{
	}
	
	return ($result ? 0 : 1);
	
	/**
	 * Throws a condorException which sends alerts out and includes
	 * a bunch of information along with the the message or 
	 * something of that nature
	 *
	 * @param string $msg
	 * @return unknown
	 */
	function Report($msg)
	{
		//basically the CLI Arguments.
		global $file_name,$device_id,$comm_id,$error,$cid_number,$cid_name;
		
		$server = `hostname`;
		throw new CondorException("$msg
			Server: $server
			File: $file_name
			Device: $device_id
			Comm Id: $comm_id
			Error: $error
			CID Number: $cid_number
			CID Name: $cid_name
		",CondorException::ERROR_HYLAFAX);
	}
	
	/**
	 * Parses output from the faxinfo program
	 * and returns an associative array of the stuff in it
	 * @return array
	 */
	function Fax_Info($file)
	{
		$info = shell_exec(BIN_FAXINFO. ' -n '. $file);
		
		$lines = explode("\n", $info);
		$info = array();
		foreach($lines as $line)
		{
			if(preg_match('/^[\s]*([\w]+):[\s]*([\w\s]+)$/', $line, $matches))
			{
				$info[strtolower($matches[1])] = $matches[2];
			}
		}
		return $info;
	}

	function Verify_Tiff($file)
	{
		$cmd = BIN_TIFFINFO . ' ' . $file;

		$ds_set = array(0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w"));

		$proc = proc_open($cmd, $ds_set, $pipes, NULL, NULL);

		$discard = stream_get_contents($pipes[1]);

		stream_set_blocking($pipes[2], 0);

		$output = trim(stream_get_contents($pipes[2]));

		proc_close($proc);

		// File is bad
		if (!empty($output))
			return FALSE;
	
		// File is good
		return TRUE;
	}

?>
