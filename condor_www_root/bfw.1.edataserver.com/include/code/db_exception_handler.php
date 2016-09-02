<?php
/**
 * @publicsection
 * @public
 * @author
 *     Jason Duffy
 * @brief
 *     Some default handling for exceptions from databases.  Mostly log the
 *     exception and move on.
 *
 * @version
 *     1.0.0        2005-04-16 - Jason Duffy
 * @updates
 *
 * @todo
 *	   It would be nice if the applog object we log to were a global.
 */
class DB_Exception_Handler
{
	static public function Def(&$applog, &$exception, $message="")
	{
		$applog->Write("DB_Exception_Handler::Default->$message");
		if (DEBUG==TRUE)
		{
			throw $exception;
		}
	}
}
?>
