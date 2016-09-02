<?php

require_once('prpc2/client.php');
class tx_Mail_Client
{
	function tx_Mail_Client($prpc_die = true)
	{
		$this->prpc_die = $prpc_die;
	}

	function test($mode='live')
	{
		$this->setMode($mode);

		$tx = new Prpc_Client2($this->url);

		if (!$this->prpc_die)
		{
			$tx->setPrpcDieToFalse();
		}

		return $tx->test();	
	}

	/**
	 * Look up a queue via a transid
	 *
	 * @param varchar $mode
	 * @param INT $transid
	 * @return array
	 */
	function queueStatus($mode, $transid)
	{
		$this->setMode($mode);
		$tx = new Prpc_Client2($this->url);
		if (!$this->prpc_die)
		{
			$tx->setPrpcDieToFalse();
		}
		return $tx->queue_status($transid);
	}

	function sendMessage($mode, $message_id, $to_email, $track_key = '', $token = array(), $attach = array(), $suppression_list = false)
	{
		$this->setMode($mode);

		$token['email'] = $to_email;
		$token['track_key'] = $track_key;

		$tx = new Prpc_Client2($this->url);

		if (!$this->prpc_die)
		{
			$tx->setPrpcDieToFalse();
		}

		if($suppression_list == false)
		{
        	return $tx->sendMessage($message_id, $token, $attach, $track_key);
		}

		return $tx->sendMessage($message_id, $token, $attach, $track_key, $suppression_list);
	}

	function setMode($mode)
	{
		$this->mode = $mode;
		switch($mode)
		{
			case 'live':
				$this->url = 'prpc://app.hammerpanel.com/mail_list.php';
				break;
			case 'rc':
				$this->url = 'prpc://rc.hammerpanel.com/mail_list.php';
				break;
			default:
				$this->url = 'prpc://trendex_103.ds78.tss/mail_list.php';
				break;
		}
	}
}

?>
