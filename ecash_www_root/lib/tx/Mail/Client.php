<?php
// Changed this class to simply convert the token and send it to the smtp mail driver instead of the to trendex.
// Eventually it will handle the drive the template($message_id) manager.
//   Randy Klepetko 08/16/2012
//

require_once('send_mail.php');
class tx_Mail_Client {
	private $log;
	
	public function __construct($mode) {}

		$this->log = ECash::getLog('email');
        $this->log->Write("Constructed tx_Mail_Client class");
	}

	function sendMessage($mode, $message_id, $to_email, $track_key = '', $token = array(), $attach = array(), $suppression_list = false) {
        // Depreciated
        //  mode
        //  track_key
        //  attach
        //  suppression_list

		$obj->to = 'rktest@gametruckparty.com';  //$to_email
		$obj->subject = $tokens['subject'];
        $obj->content_type = CONTENT_TYPE_TEXT_PLAIN;
        $obj->data = $to_email . " :: " . $message_id . " :: " . serialize($token);

        $this->log->Write("Calling Send_Mail class");
		$tx = new Send_Mail();

		return $tx->Send_EMail($obj);
	}
}

?>
