<?

/**
	@publicsection
	@public
	@brief Send SMS Class for OLP

	Forks a process to send SMS messages from OLP driven events.
	This is initially written for the event when a customer agrees.  This code will need
	to be altered slightly if we choose to send sms messages triggered by other
	OLP driven events.

	@version
		1.0 2005-12-15 - Norbinn Rodrigo (Initial revision)

*/

define("PHP5_BIN", "/usr/lib/php5/bin/php");
define("OLP_SMS_SCRIPT", "/virtualhosts/bfw.1.edataserver.com/include/modules/olp/olp.sms_exec.php");

class OLP_SMS
{

	private $sql;
	private $license;
	private $mode;
	private $promo_id;
	private $promo_sub_code;
	private $cell_phone;
	private $property_short;

	function __construct(&$sql, $license, $mode)
	{
		$this->sql = $sql;
		$this->license = $license;
		$this->mode = $mode;
		$this->promo_id = $_SESSION['config']->promo_id;
		$this->promo_sub_code = $_SESSION['config']->promo_sub_code;
		$this->cell_phone = $_SESSION['data']['phone_cell'];
		$this->property_short = $_SESSION['blackbox']['winner'];
	}

	function Run_SMS_Script()
	{
		exec(PHP5_BIN.' '.OLP_SMS_SCRIPT.' '.$this->license.' '.$this->mode.' '.$this->cell_phone.' '.$this->property_short.' '.$this->promo_id.'_p_'.$this->promo_sub_code.' > /dev/null &');
	}

}

?>
