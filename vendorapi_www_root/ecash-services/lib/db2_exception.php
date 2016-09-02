<?PHP

require_once 'general_exception.1.php';

class Db2_Exception extends General_Exception
{

	function __construct($message, $level=LOG_CRIT)
	{
		parent::__construct($message,$level);
	}

	function Clean_Up()
	{
		echo "Your In Clean Up:\n\n";
	}
}
?>