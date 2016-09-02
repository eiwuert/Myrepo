<?php

/**
 * interface for classes which implement mailing functionality.
 * assumes class is using external provider, and that the mail items
 * are stored at the external provider.  For classes that send email
 * without having it stored, see IRawMailer
 *
 * @author John Hargrove <john.hargrove@sellingsource.com
 * @package Mail
 */
interface Mail_IMailer_1
{
	/**
	 * Primary method for sending mails where mail is stored in the provider
	 *
	 * @param mixed $mail_id Provider specific mail identifier
	 * @param string $dest_address Destination email address
	 * @param string $track_key Track key, if needed for stats
	 * @param array $tokens Array of tokens, in token_name => value format
	 * @param array $attachments Array of attachments where each attachment is an array with the elements: method (EMBED|ATTACH), filename, mime_type, file_data, file_data_size
	 * @param mixed $supression_list Provider specific supression list identifier
	 *
	 * @return int 
	 */
	public function sendMail(
		$mail_id,
		$dest_address,
		$track_key = NULL,
		$tokens = array(),
		$attachments = array(),
		$supression_list = NULL
	);
}

?>