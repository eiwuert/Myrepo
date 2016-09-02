<?php

	/**
	 * interface for classes which implement mailing functionality.
	 * assumes class is using an external provider, or local system resources
	 * to deliver the mail.  This interface is for sending raw emails, and does
	 * not expect mail content to be stored in any database. For functionality
	 * involving mail message IDs and so on, see IMailer
	 * 
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 * @package Mail
	 */
	interface Mail_IRawMailer_1
	{
		/**
		 * Primary method for sending raw emails, either through a remote provider or using local
		 * system resources. Message data is provided at call time.
		 *
		 * @param string $operating_mode Allows a class to receive mode information from the application
		 * @param string $subject E-mail subject
		 * @param string $body E-mail body
		 * @param string $dest_address Destination email address
		 * @param string $track_key Track key, if needed for stats
		 * @param array $attachments Array of attachments in file_name => bindata format
		 * 
		 * @return int 0 on success, non-0 for acceptable errors. Exceptions for unexpected errors.
		 */
		public function sendRawMail(
			$operating_mode,
			$subject,
			$body,
			$additional_headers,
			$dest_address,
			$track_key,
			$attachments
		);
	}
?>