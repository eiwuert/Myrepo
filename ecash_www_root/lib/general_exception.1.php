<?PHP
/**
	@version:
			0.9.0 2004-11-22 - General exceptions class

	@author:
			tomr - version 0.9.0

	@Updates:

*/

require_once 'applog.1.php';

if ( !defined('APPLICATION') && !defined('APPLOG_SUBDIRECTORY') )	define('APPLICATION', 'all');
if ( !defined('APPLICATION') &&  defined('APPLOG_SUBDIRECTORY') )	define('APPLICATION', APPLOG_SUBDIRECTORY);
if ( !defined('APPLOG_SIZE_LIMIT') )								define('APPLOG_SIZE_LIMIT', 1000000);
if ( !defined('APPLOG_FILE_LIMIT') )								define('APPLOG_FILE_LIMIT', 5);
if ( !defined('APPLOG_CONTEXT') )									define('APPLOG_CONTEXT', '');
if ( !defined('APPLOG_ROTATE') )									define('APPLOG_ROTATE', FALSE);

class General_Exception extends Exception
{
	protected $log;
	protected $msg;
	protected $trace;

	function __construct($message, $level=LOG_DEBUG)
	{
		parent::__construct((string) $message, (int) $level);

		$this->log = new Applog(APPLICATION, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, APPLOG_CONTEXT, APPLOG_ROTATE);

		self::Log_Error();
	}


	public function Log_Error()
	{
		$this->msg = 'EXCEPTION \''.parent::getMessage().'\' IN FILE '.parent::getFile().' AT LINE '.parent::getLine();

		// Add "session id" if this is an OLP application
		if(defined('APPLICATION') && ((APPLICATION == 'olp') || (APPLICATION == 'rc_olp')))
		{
			$this->msg .= ' SESSION_ID OF '.session_id();
		}

		$this->log->Write($this->msg,parent::getCode());

		$this->trace = debug_backtrace();

		// Get rid of the extra [object] var at each level of the backtrace
		//  php documentation does not list it as something that will be coming from debug_backtrace()
		//  For us, it appeared to be getting the Server object recursively, taking all the memory
		//  and generating 20-30k rows per EACH error in the logs
		for( $x = 0 ; $x < count($this->trace) ; $x++ )
			if( isset($this->trace[$x]['object']) )
				unset($this->trace[$x]['object']);

		return TRUE;
	}

	public function Get_Message()
	{
		return $this->msg;
	}

	public function __toString()
	{
		$trace = print_r($this->trace, TRUE);
		$log = print_r($this->log, TRUE);
		return "<pre>Message: {$this->msg}\nLog: {$log}\nTrace: {$trace}</pre>";
	}
}

?>
