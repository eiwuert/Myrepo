<?PHP
/**
* An exception to throw when you want to notify people
* condor broke
**/
require_once('reported_exception.1.php');
if(!defined('MODE_LIVE'))
{
	define('MODE_LIVE', 'LIVE');
	define('MODE_RC', 	'RC');
	define('MODE_DEV', 	'LOCAL');
	define('MODE_DEMO', 'DEMO');
}

if(!defined('EXECUTION_MODE'))
{
	define('EXECUTION_MODE',MODE_RC);
}

class CondorException extends Reported_Exception
{
	const ERROR_HYLAFAX   = 1;
	const ERROR_OLE       = 2;
	const ERROR_MOUNT     = 3;
	const ERROR_DATABASE  = 4;
	const ERROR_UNKNOWN   = 5;
	const ERROR_EMAIL     = 6;

	public static function clearRecipients()
	{
		self::$recipients = Array();
	}

	/**
	* Add message recipients to this thign
	*/
	public static function buildRecipients($code)
	{
		self::clearRecipients();
		//figure out who we should send it too
		switch($code)
		{
			case self::ERROR_HYLAFAX:
				switch(EXECUTION_MODE)
				{
					case MODE_LIVE:
					case MODE_RC:
					case MODE_DEV:
						self::Add_Recipient('email','condor-alerts@dev.amgsrv.com');
						self::Add_Recipient('email','richard.bunce@sellingsource.com');
						break;
				}
				break;
			case self::ERROR_DATABASE:
				switch(EXECUTION_MODE)
				{
					case MODE_LIVE:
						self::Add_Recipient('sms', '6613042806'); // Brian R
						self::Add_Recipient('email','richard.bunce@sellingsource.com');
					case MODE_RC:
					case MODE_DEV:
						self::Add_Recipient('email','condor-alerts@dev.amgsrv.com');
						break;
					
				}
				break;
			case self::ERROR_MOUNT:
				switch(EXECUTION_MODE)
				{
					case MODE_LIVE:
						self::Add_Recipient('sms', '6613042806'); // Brian R
						self::Add_Recipient('email','richard.bunce@sellingsource.com');
					case MODE_RC:
					case MODE_DEV:
						self::Add_Recipient('email','condor-alerts@dev.amgsrv.com');
					break;

				}
				break;
			case self::ERROR_EMAIL:
			case self::ERROR_OLE:
				switch(EXECUTION_MODE)
				{
					case MODE_LIVE:
					case MODE_RC:
					case MODE_DEV:
						self::Add_Recipient('email','richard.bunce@sellingsource.com');
						self::Add_Recipient('email','condor-alerts@dev.amgsrv.com');
						break;
				}
				break;
			case self::ERROR_UNKNOWN:
				switch(EXECUTION_MODE)
				{
					case MODE_LIVE:
					case MODE_RC:
					case MODE_DEV:
						self::Add_Recipient('email','richard.bunce@sellingsource.com');
						self::Add_Recipient('email','condor-alerts@dev.amgsrv.com');
						break;
				}
				break;
			default:
				break;
		}
	}
	public function __construct($desc,$code = CondorException::ERROR_UNKNOWN)
	{
		self::clearRecipients();
		self::buildRecipients($code);
		parent::__construct($desc,$code);
	}
};
?>
