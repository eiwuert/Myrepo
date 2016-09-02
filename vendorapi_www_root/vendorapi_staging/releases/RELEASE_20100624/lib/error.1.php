<?php
	// The error class
	class Error_1
	{
		function Error_1 ()
		{
			return TRUE;
		}
		
		function Report_Error ($trace_code, $message, $other = NULL)
		{
			$err_from = "error@".$_SERVER ["SERVER_NAME"];
			$err_to = "errors@sellingsource.com";
			$err_subject = "An error has occured on ".$_SERVER ["SERVER_NAME"];
			$mode = "DEBUG";

			switch ($mode)
			{
				case "DEBUG":
					echo "<pre>".$trace_code."\n".$message."\n".$other."\n\n"."</pre>";
					break;
				
				case "LIVE":
					mail ($err_to, $err_subject, $trace_code.$message.$other);
					break;
			}

			// Punt and let the other team deal with the ball
			return TRUE;
		}
	
		function Error_Test ($result)
		{
			// Error checking
			if (is_a ($result, "Error_1"))
			{
				// An error occured, blow chunks
				Error_1::Report_Error ("Trace Code:\n".$result->trace_code, @mysql_error ($result->link_id), Error_1::Dump ($result));
				
				// Should we die here?
				if ($result->fatal)
				{
					// Perhaps we should show something to the user?
					echo "A fatal server error has occured.  The site admin has been notified.  Please try your request again in 30 minutes";
					exit;
				}
				
				return TRUE;
			}

			return FALSE;
		}
		
		function Dump ($object)
		{
			ob_start ();
			print_r ($object);
			$ob = ob_get_contents ();
			ob_end_clean ();
			
			return $ob;
		}
		
		function Get_Version ()
		{
			$version = new stdClass ();

			$version->api = 1;
			$version->feature = 0;
			$version->bug = 0;
			$version->version = $version->api.".".$version->feature.".".$version->bug;

			return $version;
		}
	}
?>
