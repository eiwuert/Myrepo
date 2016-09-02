<?php
	// Version 1.0.0
	// A tool to handle NULL sessions

	class Null_Session_1
	{
		function Null_Session_1 ()
		{
			return TRUE;
		}

		function Open ($save_path, $session_name) 
		{
			return true;
		}

		function Close ()
		{
			return true;
		}

		function Read ($session_id)
		{
			return '';
		}

		function Write($session_id, $session_info)
		{
			return TRUE;
		}

		function Destroy ($session_id)
		{
			return TRUE;
		}

		function Garbage_Collection ($session_life) 
		{
			return TRUE;
		}
	}
?>
