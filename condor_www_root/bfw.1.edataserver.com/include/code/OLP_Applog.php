<?php

/** @file
 * An extension of lib/applog.1.php to better fit OLP debugging.
 */

require_once('applog.1.php');

class OLP_Applog extends Applog
{
	/**
	 * Get Application ID from Session. Stripped from olp.php down to only
	 * the session-looking statements.
	 *
	 * @return int Application ID/false on failure
	 */
	protected function Get_Application_ID()
	{
		if (isset($_SESSION["application_id"]) && is_numeric($_SESSION["application_id"]))
		{
			return $_SESSION["application_id"];
		}
		elseif (isset($_SESSION["cs"]["application_id"]) && is_numeric($_SESSION["cs"]["application_id"]))
		{
			return $_SESSION["cs"]["application_id"];
		}
		elseif (isset($_SESSION['transaction_id']) && is_numeric($_SESSION['transaction_id']))
		{
			return $_SESSION['transaction_id'];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Get the Session ID.
	 *
	 * @return string Session ID/false on failure
	 */
	protected function Get_Session_ID()
	{
		if (strlen(session_id()) == 32)
		{
			return session_id();
		}
		else
		{
			return FALSE;
		}
	}
	
	function Write($text, $level=LOG_DEBUG)
	{
		$application_id = $this->Get_Application_ID();
		$session_id = $this->Get_Session_ID();
		
		if ($application_id)
		{
			$text .= " [AppID: {$application_id}]";
		}
		elseif ($session_id)
		{
			$text .= " [SessionID: {$session_id}]";
		}
		
		parent::Write($text, $level);
	}
}

?>
