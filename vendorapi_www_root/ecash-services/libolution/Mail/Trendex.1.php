<?php

/**
 * Class that sends mail via Trendex
 *
 * @package Mail
 * @author Rodric Glaser <rodric.glaser@sellingsource.com>
 */
class Mail_Trendex_1 extends Object_1 implements Mail_IMailer_1
{
	/**
	 * @var bool
	 */
	private $prpc_die;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * Constructor
	 *
	 * @param string $operating_mode Valid options: LIVE, RC
	 * @param bool $prpc_die PRPC Die-On-Error flag sent to underlying prpc client.
	 */
	public function __construct($operating_mode, $prpc_die = TRUE)
	{
		$this->prpc_die = $prpc_die;
		$this->setMode($operating_mode);
	}

	/**
	 * Trendex implmenetation of mailer.
	 *
	 * @param mixed $mail_id Provider specific mail identifier
	 * @param string $dest_address Destination email address
	 * @param string $track_key Track key, if needed for stats
	 * @param array $tokens Array of tokens, in token_name => value format
	 * @param array $attachments Array of attachments where each attachment is an array with the elements: method (EMBED|ATTACH), filename, mime_type, file_data, file_data_size
	 * @param mixed $supression_list Provider specific supression list identifier
	 *
	 * @return int provider specific queue id. Exceptions for unexpected errors.
	 */
	public function sendMail($mail_id, $dest_address, $track_key = NULL, $tokens = array(), $attachments = array(), $suppression_list = FALSE)
	{
		$tokens['email'] = $dest_address;
		$tokens['track_key'] = $track_key;

		$tx = new Prpc_Client2($this->url);

		if (!$this->prpc_die)
		{
			$tx->setPrpcDieToFalse();
		}

		if($suppression_list == FALSE)
		{
			return $tx->sendMessage($mail_id, $tokens, $attachments, $track_key);
		}

		return $tx->sendMessage($mail_id, $tokens, $attachments, $track_key, $suppression_list);
	}

	/**
	 * Changes the active mode for the trendex client library.
	 *
	 * @param string $mode Mode identifier. Valid options: LIVE, RC
	 */
	public function setMode($mode)
	{
		$mode = strtolower($mode);
		switch($mode)
		{
			case 'live':
				$this->url = 'prpc://app1.mail-forge.com/mail_list.php';
				break;
			case 'rc':
				$this->url = 'prpc://mwiseman2.mail-forge.com/mail_list.php';
				break;
			default:
				$this->url = 'prpc://rc.mail-forge.com/mail_list.php';
				break;
		}
	}
}

?>
