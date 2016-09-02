<?php
	// Version 1.0.0
	/**
		@publicsection
		@public
		@file xmlrpc.1.php
		@brief
			Handle XML-RPC communications between two sites. (Depricated)

		Handle XML-RPC communications between two sites.  Will use built in XML-RPC
		procedures if available.  If not, will mimic them with built in functions.
		Also will try to compress the communications if at all possible.

		@version
			1.1.2 2003-05-19 - Paul Strange
				- [Bug] Remove use of sha1 for compatability isues

		@version
			1.1.1 2003-05-15 - Paul Strange
				- [Feature] Allow xmlrpc to flow with or without compression to the envelope
				- [Bug] Fixed backwards compatability issue.

	*/

	/**
		@publicsection
		@public
		@fn string Xmlrcp_Response ($raw_post_data)
		@brief
			The server side.

		Listen for a request to the server from a client and process that request.

		@param $raw_post_data string \n The post header from the http stream

		@return
			string
	*/
	function Xmlrpc_Response ($raw_post_data)
	{
		// Yank the boundaries
		$data_boundry = _Xmlrpc_Data_Boundry ();
		$header_boundry = _Xmlrpc_Header_Boundry ();

		preg_match ("/".$header_boundry."(.*)".$header_boundry."/ms", $raw_post_data, $headers);
		preg_match ("/".$data_boundry."(.*)".$data_boundry."/ms", $raw_post_data, $matches);

		switch ($headers[1])
		{
			case "COMPRESS_FALSE":
				// We should not use compression
				$COMPRESS = FALSE;

				// Get the response
				$xmlrpc_envelope = base64_decode ($matches [1]);
				break;

			case "COMPRESS_TRUE":
			default:
				// We can use encryption
				$COMPRESS = TRUE;

				// Unzip the response
				$xmlrpc_envelope = @gzinflate (substr (base64_decode ($matches [1]), 10, -4));
				break;
		}

		// Get the data structure
		$parameters = xmlrpc_decode ($xmlrpc_envelope);

		// Get the method to call
		$method = xmlrpc_parse_method_descriptions ($xmlrpc_envelope);

		// Test if the method exists
		if (function_exists ($method ["methodName"]))
		{
			// Get the encoded response
			$response = $method ["methodName"] ($parameters[0]);
		}
		else
		{
			// Method does not exist. Prepare the failure response
			$response = new stdClass ();
			$response->XMLRPC_RESULT = FALSE;
			$response->XMLRPC_FAULT_STRING = "Function ".$method ["methodName"]." does not exist on this server";
		}

		// Compress the envelope if we can. (Try to match the same as we recieved)
		if ($COMPRESS)
		{
			$envelope = $header_boundry."COMPRESS_TRUE".$header_boundry.$data_boundry.base64_encode (gzencode (xmlrpc_encode ($response))).$data_boundry;
		}
		else
		{
			$envelope = $header_boundry."COMPRESS_FALSE".$header_boundry.$data_boundry.base64_encode (xmlrpc_encode ($response)).$data_boundry;
		}

		// Send it back
		return $envelope;
	}

	/**
		@publicsection
		@public
		@fn string Xmlrpc_Request ($host, $port, $path, $method, $data)
		@brief
			The client side.

		Send a request to the server and get the response.

		@param $host string \n The name of the server to connect to.
		@param $port string \n The port the server is listening on (80 or 443)
		@param $path string \n The path to the service on the server
		@param $method string \n The function to use on the server
		@param $data string \n The data passed to the function called on the server

		@return
			string
	*/
	function Xmlrpc_Request ($host, $port, $path, $method, $data)
	{
		$debug_data = $data;

		if(is_a($debug_data, "stdClass"))
		{
			unset ($debug_data->XMLRPC_DEBUG);
		}

		if(is_a($debug_data, "array"))
		{
			unset ($debug_data ["XMLRPC_DEBUG"]);
		}

		// Place debug code in the response
		$XMLRPC_DEBUG =  "----- START XMLRPC DEBUG ON ".$_SERVER ["SERVER_NAME"].$_SERVER ["REQUEST_URI"]." -----<pre>\n\n";

		$XMLRPC_DEBUG .=  "----- Parameters -----\nHost: ".$host."\nPort: ".$port."\nPath: ".$path."\nFunction: ".$method."\nData:\n";
		$XMLRPC_DEBUG .=  _Buffered_Dump ($debug_data);
		$XMLRPC_DEBUG .=  "\n\n";


		// open a connection to the server
		$xml_server = @fsockopen ($host, $port);

		// Test the connection for failure
		if (!$xml_server)
		{
			$xmlrpc_response = new stdClass ();
			$xmlrpc_response->XMLRPC_RESULT = FALSE;
			$xmlrpc_response->XMLRPC_FAULT_STRING = "Could not connect to ".$host.":".$port;
			$xml_recieve_envelope= xmlrpc_encode ($xmlrpc_response);

			// Variables for Debug Mode
			$headers = "Not set.  No connection was made";
			$envelope = "No Envelope Sent.";
		}
		else
		{
			$data_boundry = _Xmlrpc_Data_Boundry ();
			$header_boundry = _Xmlrpc_Header_Boundry();

			if (function_exists ("gzencode"))
			{
				// We can use compression
				$COMPRESS = TRUE;

				// Compress the envelope
				$envelope = $header_boundry."COMPRESS_TRUE".$header_boundry.$data_boundry.base64_encode (gzencode (xmlrpc_encode_request ($method, $data))).$data_boundry;
			}
			else
			{
				// We cannot use encryption
				$COMPRESS = FALSE;

				// Build a standard envelope
				$envelope = $header_boundry."COMPRESS_FALSE".$header_boundry.$data_boundry.base64_encode (xmlrpc_encode_request ($method, $data)).$data_boundry;
			}

			$headers =
				"POST ".$path." HTTP/1.0\r\n" .
				"Host: $host\r\n" .
				"Connection: close\r\n" .
				"User-Agent: xmlrpc_request\r\n".
				"Content-Type: text/xml\r\n" .
				"Content-Length: ".strlen ($envelope) . "\r\n\r\n";

			// Send the headers
			fwrite ($xml_server, $headers, strlen ($headers));

			// Send the data
			fwrite ($xml_server, $envelope, strlen ($envelope));

			// Get the response
			unset ($raw_response);

			$XMLRPC_DEBUG .=  "----- Sent -----\n".$headers."\n\n".$envelope;
			$XMLRPC_DEBUG .=  "\n\n";

			// Wait for the response to complete
			$raw_response = '';
			while (!feof ($xml_server))
			{
				$raw_response .= fread ($xml_server, 1024);
			}

			// Close the connection
			fclose ($xml_server);

			if (strlen ($raw_response))
			{
				// Trim off the http headers so we have just the compressed response
				preg_match ("/".$header_boundry."(.*)".$header_boundry."/ms", $raw_response, $headers);
				preg_match ("/".$data_boundry."(.*)".$data_boundry."/ms", $raw_response, $matches);

				if (isset($matches[1]) && strlen ($matches [1]))
				{
					switch ($headers [1])
					{
						case "COMPRESS_FALSE":
							// Get the response
							$xml_recieve_envelope = base64_decode ($matches [1]);
							break;

						case "COMPRESS_TRUE":
						default:
							// Unzip the response
							$xml_recieve_envelope = @gzinflate (substr (base64_decode ($matches [1]), 10, -4));
							break;

					}
				}
				else
				{
					// No response from the server throw error
					$xmlrpc_response = new stdClass ();
					$xmlrpc_response->XMLRPC_RESULT = FALSE;
					$xmlrpc_response->XMLRPC_FAULT_STRING = "No compressed or uncompressed response from ".$host.":".$port;
					$xml_recieve_envelope= xmlrpc_encode ($xmlrpc_response);
				}
			}
			else
			{
				$xmlrpc_response = new stdClass ();
				$xmlrpc_response->XMLRPC_RESULT = FALSE;
				$xmlrpc_response->XMLRPC_FAULT_STRING = "No response from ".$host.":".$port;
				$xml_recieve_envelope= xmlrpc_encode ($xmlrpc_response);
			}
		}

		$debug_response = $decoded_response = xmlrpc_decode ($xml_recieve_envelope);

		unset ($debug_response ["XMLRPC_DEBUG"]);
		
		$XMLRPC_DEBUG .= (!empty($decoded_response ["XMLRPC_DEBUG"]) && strlen ($decoded_response ["XMLRPC_DEBUG"])) ? "\n\n".$decoded_response ["XMLRPC_DEBUG"]."\n\n" : NULL;
		$XMLRPC_DEBUG .= "----- Received -----\nPacket Size: ".strlen ($raw_response)." bytes\n".$raw_response."\n\nUncompressed XML String:\n";
		$XMLRPC_DEBUG .= htmlentities ($xml_recieve_envelope)."\n\n";
		$XMLRPC_DEBUG .= "\n\nUncompressed Data Structure:\n";
		$XMLRPC_DEBUG .= _Buffered_Dump ($debug_response);
		$XMLRPC_DEBUG .= "\n\n----- END XMLRPC DEBUG ON ".$_SERVER ["SERVER_NAME"].$_SERVER ["REQUEST_URI"]." -----</pre>";

		// Add the debug to the envelope
		$decoded_response ["XMLRPC_DEBUG"] = $XMLRPC_DEBUG;

		return $decoded_response;
	}

/*
**
**	From here down are private methods to maintain compatability with the compiled in version if not compiled in.
**
*/
	/**
		@privatesection
		@private
		@fn string _Xmlrpc_Data_Boundry ()
		@brief
			A boundry for the data

		Place a boundry around the data for ease of parsing.

		@return
			string
	*/
	function _Xmlrpc_Data_Boundry ()
	{
		return "%%".md5 ("Boundry")."%%";
	}

	/**
		@privatesection
		@private
		@fn string _Xmlrpc_Header_Boundry ()
		@brief
			A boundry for the header

		Place a boundry around the header for ease of parsing.

		@return
			string
	*/
	function _Xmlrpc_Header_Boundry ()
	{
		return "%%82e425f41aed2fdc78fd23f9da958cddc8af24b1%%";
	}

	/**
		@privatesection
		@private
		@fn string Xmlrpc_Encode_Request ($method, $parameters)
		@brief
			Encode the method call (only used if no XML_RPC compiled in.

		Encode the request into XML for sending to the server.  This function will
		not be used if the compiled in version of XML_RPC is available.

		@param method string \n The function to call on the server
		@param parameters string \n The parameters to pass to the function on the remote server.

		@return
			string
	*/
	if (!function_exists ("xmlrpc_encode_request"))
	{
		function Xmlrpc_Encode_Request ($method, $parameters)
		{
			$tab_char = " ";
			$tab_depth = 0;
			$xml =
				"<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n".
				str_repeat ($tab_char, $tab_depth)."<methodCall>\n".
				str_repeat ($tab_char, $tab_depth)."<methodName>".$method."</methodName>\n".
				str_repeat ($tab_char, $tab_depth)."<params>\n".
				str_repeat ($tab_char, ++$tab_depth)."<param>\n".
				str_repeat ($tab_char, ++$tab_depth)."<value>\n".
				str_repeat ($tab_char, ++$tab_depth)."<struct>\n".
				_Build_Xmlrpc ($parameters, ++$tab_depth, $tab_char).
				str_repeat ($tab_char, --$tab_depth)."</struct>\n".
				str_repeat ($tab_char, --$tab_depth)."</value>\n".
				str_repeat ($tab_char, --$tab_depth)."</param>\n".
				str_repeat ($tab_char, --$tab_depth)."</params>\n".
				str_repeat ($tab_char, $tab_depth)."</methodCall>\n";

			return $xml;
		}
	}


	/**
		@privatesection
		@private
		@fn string Xmlrpc_Encode ($parameters)
		@brief
			Encode the parameters (only used if no XML_RPC compiled in.

		Encode the parameters into XML for sending to the server.  This function will
		not be used if the compiled in version of XML_RPC is available.

		@param parameters string \n The parameters to pass to the function on the remote server.

		@return
			string
	*/
	if (!function_exists ("xmlrpc_encode"))
	{
		function Xmlrpc_Encode ($parameters)
		{
			$tab_char = " ";
			$tab_depth = 0;
			$xml =
				"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
				str_repeat ($tab_char, ++$tab_depth)."<params>\n".
				str_repeat ($tab_char, ++$tab_depth)."<param>\n".
				str_repeat ($tab_char, ++$tab_depth)."<value>\n".
				str_repeat ($tab_char, ++$tab_depth)."<struct>\n".
				_Build_Xmlrpc ($parameters, ++$tab_depth, $tab_char).
				str_repeat ($tab_char, --$tab_depth)."</struct>\n".
				str_repeat ($tab_char, --$tab_depth)."</value>\n".
				str_repeat ($tab_char, --$tab_depth)."</param>\n".
				str_repeat ($tab_char, --$tab_depth)."</params>\n";

			return $xml;
		}
	}

	/**
		@privatesection
		@private
		@fn string Xmlrpc_Decode ($xml_string)
		@brief
			Decode the request call (only used if no XML_RPC compiled in.

		Decode the request into XML for processing on the server.  This function will
		not be used if the compiled in version of XML_RPC is available.

		@param xml_string string \n The string of XML to decode

		@return
			string
	*/
	if (!function_exists ("xmlrpc_decode"))
	{
		function Xmlrpc_Decode ($xml_string)
		{
			$xml_parser = new _Xml();
			return $xml_parser->_Parse($xml_string);
		}
	}

	/**
		@privatesection
		@private
		@fn string Xmlrpc_Parse_Method_Descriptions ($xml_string)
		@brief
			Decode the method call (only used if no XML_RPC compiled in.

		Decode the request into XML for processing on the server.  This function will
		not be used if the compiled in version of XML_RPC is available.

		@param method string \n The function to call on the server
		@param parameters string \n The parameters to pass to the function on the remote server.

		@return
			string
	*/
	if (!function_exists ("xmlrpc_parse_method_descriptions"))
	{
		function Xmlrpc_Parse_Method_Descriptions ($xml_string)
		{
			preg_match ("/<methodName>(.*?)<\/methodName>/", $xml_string, $matches);
			return array ("methodName" => $matches[1], "params" => array ("param" => array ()));
		}
	}

	/**
		@privatesection
		@private
		@fn string _Build_Xmlrpc ($parameters, &$tab_depth, &$tab_char)
		@brief
			Build the actual XML that is transmitted.

		Build the well structured XML that is sent to and from the server.

		@param parameters string \n The parameters to pass to the function on the remote server.
		@param tab_depth int \n The number of tabs to put in front of the line
		@param tab_char char \n The char to use as a tab

		@return
			string
	*/
	function _Build_Xmlrpc ($parameters, &$tab_depth, &$tab_char)
	{
		if (is_object ($parameters) || is_array ($parameters))
		{
			$xml = '';
			foreach ($parameters as $name => $value)
			{
				$xml .=
					str_repeat ($tab_char, $tab_depth)."<member>\n".
					str_repeat ($tab_char, ++$tab_depth)."<name>".$name."</name>\n".
					str_repeat ($tab_char, $tab_depth)."<value>\n";

				if (is_array ($value) || is_object ($value))
				{
					$xml .=
						str_repeat ($tab_char, ++$tab_depth)."<struct>\n"
						._Build_Xmlrpc ($value, ++$tab_depth, $tab_char).
						str_repeat ($tab_char, --$tab_depth)."</struct>\n".
						str_repeat ($tab_char, --$tab_depth)."</value>\n".
						str_repeat ($tab_char, --$tab_depth)."</member>\n";
				}
				else
				{
					$type = (gettype ($value) == "integer" ? "int" : gettype ($value));

					if ($type == "string")
					{
						$value = "<![CDATA[".$value."]]>";
					}

					$xml .=
						str_repeat ($tab_char, ++$tab_depth)."<".$type.">".$value."</".$type.">\n".
						str_repeat ($tab_char, --$tab_depth)."</value>\n".
						str_repeat ($tab_char, --$tab_depth)."</member>\n";
				}
			}
		}

		return $xml;
	}

	/**
		@privatesection
		@private
		@fn string _Buffered_Dump ($object, $file = NULL, $line = NULL)
		@brief
			Dump the object into a buffer.

		Dump the object into a buffer for display or email latter.  This is for debugging
		purposes only.

		@param object string \n The object that should be dumped
		@param file string \n The name that is making the call.
		@param line int \n The line number in the file making the call

		@return
			string
	*/
	function _Buffered_Dump ($object, $file = NULL, $line = NULL)
	{
		// Start the buffer
		ob_start ();

		// Show some location information
		if (!is_null ($file) || !is_null ($line))
		{
			echo $file." -> ".$line."\n";
		}

		// Dump the contents into the buffer
		print_r ($object);

		// Get the buffer contents
		$buffer_contents = ob_get_contents ();

		// Purge the buffer
		ob_end_clean ();

		// Return the content
		return $buffer_contents;
	}

	/**
		@privatesection
		@private
		@brief
			A class to handle xml for parsing and creation.

		Handle building xml documents and converting xml documents to arrays.  This class is
		only to be used by the XML-RPC functions and is undocumented for that reason.
	*/
	class _Xml
	{
		var $parser;

		var $root;

		var $parent;

		var $param_level;

	   	var $current_param_level;

		var $current_path;

		var $add_method;

		var $current_name;		
		
		var $current_struct_name;

		function _Xml ()
		{
			$this->root = array();
			$this->parent = array ();
			$this->current_path = "";
			$this->param_level = 0;

			$this->parser = xml_parser_create();

			xml_set_object ($this->parser, $this);
			xml_set_element_handler ($this->parser, "_Tag_Open", "_Tag_Close");
			xml_set_character_data_handler ($this->parser, "_Cdata");

			return TRUE;
		}

		function _Parse ($data)
		{
			xml_parse ($this->parser, $data);

			$ret_val = $this->root;

			return $ret_val;
		}

		function _Tag_Open ($parser, $tag, $attributes)
		{
			switch ($tag)
			{
				case "METHODNAME":
					$this->add_method = TRUE;
					break;

				case "PARAM":
					if ($this->add_method)
					{
						$this->current_param_level = (string)$this->param_level++;
						$this->current_struct_name = "[\"".$this->current_param_level."\"]";
					}
					break;

				case "STRUCT":
					// Add one to the depth
					$this->add_struct = TRUE;
					if ($this->current_name)
					{
						$this->current_struct_name = "[\"".$this->current_name."\"]";
					}
					break;

				case "NAME":
					// Add one to this level
					$this->add_name = TRUE;
					break;

				case "INT":
				case "STRING":
				case "BOOLEAN":
				case "DOUBLE":
				case "FLOAT":
				//case "BASE64":
					// Add the value to the object
					$this->type = $tag;
					$this->add_value = TRUE;
					break;
			}
			return TRUE;
		}

		function _Cdata ($parser, $cdata)
		{
			if (strlen (trim ($cdata)))
			{
				switch (TRUE)
				{
					case $this->add_name:
						if ($this->add_struct && $this->current_struct_name)
						{
							// Set the parent string
							$parent_path = $this->current_path;
							$this->current_path .= $this->current_struct_name;

							$path_string = "\$this->root".$this->current_path;
							eval ($path_string." = array ();");

							$parent_string = "\$this->parent [\"".(preg_replace ("/(\[|\]|\")/", "", $this->current_path))."\"]";
							eval ($parent_string." = \$parent_path;");

							$this->add_struct = FALSE;
						}

						$this->current_name = $cdata;
						$this->add_name = FALSE;
						break;

					case $this->add_value:
						$value_string = "\$this->root".$this->current_path."[\"".$this->current_name."\"]";
						switch ($this->type)
						{
							case "INT":
								eval ($value_string." =  (integer)\$cdata;");
								break;

							case "STRING":
								$is_empty = eval("return empty(\$this->root".$this->current_path."[\"".$this->current_name."\"]);");
								$operator = $is_empty ? "=" : ".=";
								eval ($value_string." {$operator}  (string)\$cdata;");
								break;

							case "BOOLEAN":
								if ($cdata != 0)
								{
									eval ($value_string." =  TRUE;");
								}
								else
								{
									eval ($value_string." =  FALSE;");
								}
								break;

							case "FLOAT":
							case "DOUBLE":
								eval ($value_string." =  (float)\$cdata;");
								break;

						}

						// If the string is == 1024 then there is most likely more data.  Try again.
						$this->add_value = (strlen ($cdata) == 1024 ? TRUE : FALSE);
						break;
				}
			}

			return TRUE;
		}

		function _Tag_Close ($parser, $tag)
		{
			switch ($tag)
			{
				case "STRUCT":
					// Remove one to the depth
					$parent_string = "\$this->parent [\"".(preg_replace ("/(\[|\]|\")/", "", $this->current_path))."\"]";
					if (strlen ($this->current_path))
					{
						eval ("\$this->current_path = ".$parent_string.";");
					}
					break;
			}
			return TRUE;
		}
	}
?>
