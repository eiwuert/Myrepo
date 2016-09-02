<?php
	// Version 1.0.0
	// A class to handle debugcode
	class Debug_1
	{
		function Debug_1 ()
		{
			return TRUE;
		}
		
		function Trace_Code ($file, $line)
		{
			return "\t".$file." -> ".$line."\n";
		}
		
		function Buffered_Dump ($object, $file = NULL, $line = NULL)
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

		function Raw_Dump ($object, $file = NULL, $line = NULL)
		{
			echo "<pre>";
			if (!is_null ($file) || !is_null ($line))
			{
				echo $file." -> ".$line."\n";
			}
			print_r ($object);
			echo "</pre>";
			
			return TRUE;
		}
	}
?>
