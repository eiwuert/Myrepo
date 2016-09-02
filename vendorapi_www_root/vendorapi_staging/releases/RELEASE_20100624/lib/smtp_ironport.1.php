<?php
	require_once ("/virtualhosts/lib/error.2.php");
	require_once ("/virtualhosts/lib/debug.1.php");
	
	class Smtp_IronPort_1
	{
		var $remote_host;
		var $port;
		var $localhost;
		var $auth_methods;
		var $_debug;
		var $_socket_handle;
		var $_socket_read;
		var $_socket_write;
		var $_socket_exception;
	
	    function Smtp_IronPort_1 ($remote_host = null, $port = null, $localhost = null)
	    {
		    $this->remote_host = (isset ($remote_host) ? $remote_host : "64.119.217.188");
		    $this->port = (isset ($port ) ? $port : 25);
		    $this->localhost = (isset ($localhost) ? $localhost : "localhost");
		    
		    // The socket parameters
	        $this->_socket_handle = socket_create(AF_INET, SOCK_STREAM, 0);
	        $this->_socket_read = array ($this->_socket);
	        $this->_socket_write = array ();
	        $this->_socket_exception = array ();
			
	        return TRUE;
	    }
	
	    function Connect ($trace_code = NULL)
	    {
	    	// Connect to the server
	        $result = socket_connect ($this->_socket_handle, $this->remote_host, $this->port);

	        if (!$result)
	        {
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "Failed to connect to the mail server ".$this->remote_host;
				$error->fatal = FALSE;

				return $error;
	        }
			$result = $this->_Parse_Response (220, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			
			if (Error_2::Error_Test ($result))
			{
				return $result;
			}

	        // Negotiate the handshake with the server
	        $this->_Send ("EHLO ".$this->localhost."\r\n");
	
			$result = $this->_Parse_Response (250, $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
			
			if (Error_2::Error_Test ($result))
			{
				return $result;
			}

			return TRUE;
	    }
	
	    function Mail_From($sender, $trace_code = NULL)
	    {
	    	$result = $this->_Send ("MAIL FROM:<".$sender.">\r\n", $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
	        if (Error_2::Error_Test ($result))
	        {
	        	return $result;
	        }
			
	        return true;
	    }

	    function Rcpt_To ($recipient, $trace_code = NULL)
	    {
	        $result = $this->_Send ("RCPT TO:<".$recipient.">\r\n", $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
	        if (Error_2::Error_Test ($result))
	        {
	      		return $result;
	        }

	        return true;
	    }
	
	    function Data ($data, $trace_code = NULL)
	    {
	        // Change Unix (\n) and Mac (\r) linefeeds into Internet-standard CRLF (\r\n) linefeeds.
	        $data = preg_replace("/([^\r]{1})\n/", "\\1\r\n", $data);
	        $data = preg_replace("/\n\n/", "\n\r\n", $data);
	
	        // Because a single leading period (.) signifies an end to the data, legitimate leading periods need to be "doubled" (e.g. '..').
	        $data = preg_replace ("/\n\./", "\n..", $data);
	
	        $result = $this->_Send ("DATA\r\n", $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
	        if (Error_2::Error_Test ($result))
	        {
	      		return $result;
	        }

	        $result = $this->_Send ($data."\r\n.\r\n", $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
	        if (Error_2::Error_Test ($result))
	        {
	      		return $result;
	        }
	        
			$line = socket_read ($this->_socket_handle, 1024);
	        return true;
	    }

	    function Disconnect($trace_code = NULL)
	    {
	        $result = $this->_Send ("QUIT\r\n", $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__));
	        if (Error_2::Error_Test ($result))
	        {
	      		return $result;
	        }
	        
	        socket_close ($this->_socket_handle);
	
	        return true;
	    }
	
	    function _Send ($buffer, $trace_code = NULL)
	    {
	    	$buffer_size = strlen ($buffer);
	    	$buffer_sent = 0;
	    	$send_result = TRUE;

	    	while ($buffer_sent < $buffer_size) 
	    	{
	    		$buffer_sent += $send_result = socket_write ($this->_socket_handle, $buffer, $buffer_size);
	    		
	    		if ($send_result === FALSE)
	    		{
					$error = new Error_2 ();
					$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
					$error->message = "Unable to write to the server";
					$error->fatal = FALSE;
	
					return $error;
	    		}
	    	}
	
	        return TRUE;
	    }
	
	    function _Parse_Response ($valid_code, $trace_code = NULL)
	    {
	        $line = socket_read ($this->_socket_handle, 1024);
	        
	        if (!$line)
	        {
				$error = new Error_2 ();
				$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
				$error->message = "No response from the server";
				$error->fatal = FALSE;

				return $error;
	        }
	
	        // Compare the server's response code with the valid code.
	        if (is_int ($valid_code) && ((int)substr($line, 0, 3) === $valid_code)) 
	        {
	            return TRUE;
	        }
	        
	        // Did not find what we wanted
			$error = new Error_2 ();
			$error->trace_code = $trace_code.Debug_1::Trace_Code (__FILE__, __LINE__);
			$error->message = "Expected code ".$valid_code.".  Recieved ".$this->_code;
			$error->fatal = FALSE;

			return $error;
	    }
	}
?>
