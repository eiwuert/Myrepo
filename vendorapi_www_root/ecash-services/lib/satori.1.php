<?php
	// A file to test the address verifcation process by Satori Software

	require_once ("/virtualhosts/lib/debug.1.php");
	require_once ("/virtualhosts/lib/error.2.php");

	class Satori_1
	{
		var $seperator;
		var $serial_number;
		var $request_object;
		var $satori_url;
		var $satori_port;
		var $eol;
		var $return_short_error;

		function Satori_1 ()
		{
			$this->satori_url = "tcp://mailroom1.satorisoftware.com";
			//$this->satori_url = "tcp://192.168.1.171";
			$this->satori_port = 5150;
			// This serial number changes from time to time - contact satori for a new one - Their tech support is 800-357-3020.
			$this->serial_number = "5KJ9Z2-GHBC6-CCCD-NOUYC";
			$this->seperator = "\t";
			$this->eol = "\n";
			$this->return_short_error = "N"; // Could also be "Y"
			$this->format_id = "5"; // US Web DPV
			$this->_debug = FALSE;
			$this->request_object = new stdClass();
			$this->request_object->request_id = "";
			$this->request_object->organization = "";
			$this->request_object->address_1 = "";
			$this->request_object->address_2 = "";
			$this->request_object->city = "";
			$this->request_object->zip = "";
			$this->request_object->state = "";
			$this->request_object->user_defined_1 = "";
			$this->request_object->user_defined_2 = "";

			return TRUE;
		}

		function Request_Object_Structure ()
		{
			return Debug_1::Buffered_Dump ($this->request_object);
		}

		function Validate_Address ($request_object, $trace_code = NULL) //$request_id, $organization, $address_1, $address_2, $city, $state, $zip, $user_defined_1, $user_defined_2)
		{
			if (!is_object ($request_object))
			{
				// We have a failure
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Parameter 'requested_object' is not an object";
				$error->fatal = FALSE;

				return $error;
			}

			// Build the data string
			$request = $this->_Build_String ($request_object);

			// Query the server
			$response = $this->_Request_Data ($request, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));

			if (Error_2::Error_Test($response))
			{
				return $response;
			}

			// Analyze the data
			$result = $this->_Analyze_Response ($response);

			if ($this->_debug)
			{
				var_dump($result);
			}

			return $result;
		}

		function _Request_Data ($request_string, $trace_code = NULL)
		{
			$response = "";

			// Connect to the satori server
			$socket_handle = fsockopen ($this->satori_url, $this->satori_port, $errnum, $error, 15);

			// Test if we have a valid connection
			if (is_resource ($socket_handle))
			{
				// We have a connection to the server
				fputs ($socket_handle, $request_string);

				// Get the response from the server
				while (!feof ($socket_handle))
				{
					$response .= fgets ($socket_handle);
				}
			}
			else
			{
				// We have a failure
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Unable to connect to ".$this->satori_url.":".$this->satori_port;
				$error->fatal = FALSE;

				return $error;
			}

			return $response;
		}

		function _Analyze_Response ($response)
		{
			$response_parts = explode ($this->seperator, $response);
			/* Break down of response parts
				0 = Formatting Information
				1 = Request Id
				2 = Result Code
					0 = Success
				3-8 = Address Information (could be modified to include zip+4, corrected street name, etc)
				9-10 = User Defined 1 and 2
				11 = Error String
				12 = Record Type
					S = Street
					P = PO Box
					R = Rural Route or Highway Contract
					H = High-Rise, Building, Apartment
					F = Firm Record
					G = General Delivery
					M = Multi-Carrier Record
				13 = Matched to Default (Y||N)
				14 = Error Code (0-99 or 500-599 are success all else are fail)
					70 = Primary Address Failed DPV
					71 = Secondary Address Failed DPV
					72 = Missing Secondary Information when it is required
				15 = DPV DB Used (Y||N)
				16 = Commercial Mail Receiving Agent (Y||N)
				17 = Level of Validity
					Y = Address is valid
					S = Primary is valid, secondary failed if present
					D = Primary is valid, needs secondary that was not present
					N = Primary failed
					  = Address was not presented to DPV
					X = DPV is locked out
					E = DPB is too old to be used
			*/

			// Build the analyzed response
			$analyzed_response = new stdClass();
			$analyzed_response->request_id = $response_parts [1];
			$analyzed_response->organization = $response_parts [3];
			$analyzed_response->address_1 = $response_parts [4];
			$analyzed_response->address_2 = $response_parts [5];
			$analyzed_response->city = $response_parts [6];
			$analyzed_response->state = $response_parts [7];
			$analyzed_response->zip = $response_parts [8];
			$analyzed_response->user_defined_1 = $response_parts [9];
			$analyzed_response->user_defined_2 = $response_parts [10];
			$analyzed_response->result_code = $response_parts [2];
			$analyzed_response->result_code_string = $this->_Result_Code_String($response_parts [2]);
			$analyzed_response->record_type = $this->_Record_Type_String($response_parts[12]);
			$analyzed_response->error_string = $response_parts [11];

			// Develope a PASS or FAIL for address
			//if (trim ($response_parts [17]) != "Y")
			if (
				($response_parts[14] <= 100 && !in_array($response_parts[14], array(70, 71, 72))) ||
				$response_parts[14] >= 500
			)
			{
				// This did not pass
				$analyzed_response->valid = "TRUE";
			}
			else
			{
				$analyzed_response->valid = "FALSE";
			}

			if ($this->_debug)
			{
				Debug_1::Raw_Dump ($response_parts);
				Debug_1::Raw_Dump ($analyzed_response);
			}

			return $analyzed_response;
		}

		function _Build_String ($request_object)
		{

			$body =
				$request_object->request_id
				.$this->seperator
				.$this->serial_number
				.$this->seperator
				.$request_object->organization
				.$this->seperator
				.$request_object->address_1
				.$this->seperator
				.$request_object->address_2
				.$this->seperator
				.$request_object->city
				.$this->seperator
				.$request_object->state
				.$this->seperator
				.$request_object->zip
				.$this->seperator
				.$request_object->user_defined_1
				.$this->seperator
				.$request_object->user_defined_2
				.$this->seperator
				.$this->return_short_error
				.$this->eol;

			return ("ZTI".$this->format_id."=".strlen ($body).$this->seperator.$body);
		}

		function _Record_Type_String ($record_type)
		{
			$record_string = array
			(
				"S" => "Street",
				"P" => "PO Box",
				"R" => "Rural Route or Highway Contract",
				"H" => "High-Rise, Building, Apartment",
				"F" => "Firm Record",
				"G" => "General Delivery",
				"M" => "Multi-Carrier Record",
			);

			if (array_key_exists($record_type, $record_string))
			{
				return $record_string [$record_type];
			}
			else
			{
				return "Unknown Record Type";
			}
		}

		function _Result_Code_String ($result_code)
		{
			$result_string = array
			(
				"0" => "SUCCESS",
				"0x80040400" => "Unable to initialize the CASSTask Object",
				"0x80040401" => "A batch record process was attempted when not in batch mode",
				"0x80040402" => "CASS Matching Engine could not be loaded",
				"0x80040403" => "CASS Matching Engine could not be initialized",
				"0x80040404" => "ZIP Browser could not be initialized",
				"0x80040405" => "Interface can be created or used",
				"0x80040406" => "Not enough memory was available to allocate an object or interface",
				"0x80040407" => "NULL was used as an argument to a function that requires non-NULL arguments",
				"0x80040408" => "Function argument was not in the correct range",
				"0x8004040a" => "Some fields required for CASS certification were not sent",
				"0x8004040b" => "Input block is formatted incorrectly",
				"0x8004040c" => "Sequence of function calls in task was not correct",
				"0x8004040d" => "Required data file could not be found",
				"0x8004040e" => "Format of data files is incorrect",
				"0x8004040f" => "Data files have expired",
				"0x80040410" => "New or Current version of MailRoom ToolKit is trying to access expired data files",
				"0x80040411" => "Old MailRoom ToolKit is trying to access expired data files",
				"0x80040413" => "Component of MailRoom ToolKit has expired",
				"0x80040414" => "Cancel was hit during a process",
				"0x80040415" => "Specific feature, function or interface has not been implemented",
				"0x80040416" => "General printer error",
				"0x80040417" => "System reboot is required to complete MailRoom ToolKit update",
				"0x80040418" => "AutoUpdater failed to update MailRoom ToolKit",
				"0x80040419" => "Minimum number of pieces required for sort were not provided",
				"0x8004041a" => "Temporary file manager failed during construction",
				"0x8004041b" => "Current registration key is invalid",
				"0x80040440" => "Request key is invalid",
				"0x80040442" => "Input field count is too low or too high",
				"0x80040443" => "Output field count is too low",
				"0x80040444" => "Output filed count is too high",
				"0x80040445" => "Request is overf 1024K",
				"0x80040446" => "Size specified within request does not match actual size",
				"0x80040447" => "Resquest format is invalid",
				"0x80040448" => "Registration key specified is invalid",
				"0x80040449" => "Client is unable to connect to the remote computer and local networking is ok",
				"0x8004044a" => "MailRoomServer specified is incorrect",
				"0x8004044b" => "Client was unable to load the Winsock communciation libraries or local network is not setup correctly",
				"0x8004044c" => "MRTK was able to connect to the Server, but the connection was refused",
				"0x80040450" => "General Error",
				"0x80040480" => "AutoUpdater failed because disk was full",
			);

			if (array_key_exists ($result_code, $result_string))
			{
				return $result_string [$result_code];
			}
			else
			{
				return "Unknown Result Code";
			}
		}
	}

?>
