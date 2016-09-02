<?php
	class Error_2
	{
		public $message;
		public $backtrace;
		
		function __construct ($info, $trace)
		{
			$this->message = $info;
			$this->backtrace  = $trace;
		}
		
		function Report_Error ($trace_code, $message, $other = NULL)
		{
			$err_from = "error@".$_SERVER ["SERVER_NAME"];
			$err_to = "rebel75cell@gmail.com, brian.gillingham@gmail.com, randy.klepetko@sbcglobal.net";
			$err_subject = "An error has occured on ".$_SERVER ["SERVER_NAME"];
			$mode = "DEBUG";

			switch ($mode)
			{
				case "DEBUG":
				echo '<pre>' . $trace_code . '\n'.$message.'\n'.$other.'\n\n</pre>';
					break;

				case "LIVE":
					mail ($err_to, $err_subject, $trace_code.$message.$other);
					break;
			}

			// Punt and let the other team deal with the ball
			return TRUE;
		}

		function Check ($result)
		{
			if (preg_match('/^error/i', get_class ($result)) > 0)
			{
				return TRUE;
			}

			return FALSE;
		}

		function Error_Test ($result, $force_fatal = FALSE)
		{
			// Error checking
			if (is_a ($result, "Error_2"))
			{
				// Should we notify the admin of the error?
				if (! isset($result->notify_admin) || $result->notify_admin !== FALSE)
				{
					// An error occured, blow chunks
					Error_2::Report_Error ("Trace Code:\n".$result->trace_code,
													$result->message,
													Debug_1::Buffered_Dump ($result));
				}

				if ($result->fatal || $force_fatal)
				{
					echo 'A fatal server error has occured. The site admin has been notified. Please try your request again in 30 minutes.';
					exit;
				}

				return TRUE;
			}

			return FALSE;
		}
	}
?>
