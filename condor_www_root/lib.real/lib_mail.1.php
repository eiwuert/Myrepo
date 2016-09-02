<?php
	/**
		@publicsection
		@public
		@brief
			A library to handle sending email

		@version
			1.0.1 2003-09-12 - Paul Strange
				[bug] Brought the comments in line with the style guide
				[bug] Fix problem with mailing_id 0

		@todo
	*/

	// Required files
	require_once ("prpc/client.php");
	
	class Lib_Mail
	{
		/**
			@publicsection
			@public
			@fn boolean Lib_Mail ()
			@brief
				The constructor

			The class constructor

			@return
				Will always return true
			@todo
		*/
		function Lib_Mail ()
		{
			return TRUE;
		}

		/**
			@publicsection
			@public
			@fn boolean mail ($to, $subject, $email_message, $headers)
			@brief
				A backwards compatable function call for ease of upgrade

			A backwards compatable function call for ease of upgrade to the new soap server.
			Does not handle attachments

			@param $to string \n The email address to send the email to
			@param $subject string \n The subject of the message
			@param $email_message string \n The message body (html or text)
			@param $headers string \n The headers (currently only from is used)

			@return
				Will return the result of the mail call

			@todo
		*/
		function mail ($to, $subject, $email_message, $headers=NULL)
		{
			// Headers first!!
			$header = new StdClass ();
	
			if (!is_null ($headers))
			{
				$temp = explode ("\r\n", $headers);

				foreach ($temp as $header_info)
				{
					list ($header_name, $header_data) = explode (": ", $header_info);

					$header_array [$header_name] = $header_data;
				}
			}

			if (isset($header_array['From']) && strlen($header_array ['From']))
			{
				$header->sender_name = "";
				$header->sender_address = $header_array ["From"];
			}
			else
			{
				$header->sender_name = "";
				$header->sender_address = "noreply@maildataserver.com";
			}

			$header->port = 25;
			$header->url = "maildataserver.com";
			$header->subject = $subject;

			// Now the to
			$receivers = explode (",", $to);

			// Walk each of the to list
			foreach ($receivers as $receiver_info)
			{
				//trim spaces in lists of receivers
				$receiver_info = trim($receiver_info);

				$recipient = new StdClass ();
				// Test if email is "fancy" (John Doe <jdoe@something.org>) or "plain" (jdoe@something.org)
				if (preg_match ("/(.*?)\<(.*?)\>/", $receiver_info, $matches))
				{
					// Fancy Email address
					$recipient->type = "to";
					$recipient->name = $matches [1];
					$recipient->address = $matches [2];
				}
				else
				{
					// Plain email address
					$recipient->type = "to";
					$recipient->name = $receiver_info;
					$recipient->address = $receiver_info;
				}

				// Put in the array
				$recipient_array [] = $recipient;

				unset ($recipient);
			}

			// Build the message
			$message = new StdClass ();
			if (preg_match ("/\<\/.*?\>/", $email_message))
			{
				// I think it is html because I found a closing tag </.*>
				$message->html = $email_message;
			}
			else
			{
				// Must be text?
				$message->text = $email_message;
			}


			return Lib_Mail::_Send_Mail ($header, $recipient_array, $message);
		}

		/**
			@privatesection
			@private
			@brief
				Send the message to the recipient

			Send the message to the recipient.  Also use recursion to try multiple times

			@param header object \n The headers required for the email
			@param recipient_array array \n The list of recipients to send the message to
			@param message string \n The message to send

			@return
				Return the value from the SendMail call or FALSE if it cannot be done

			@todo
		*/
		function _Send_Mail ($header, $recipient_array, $message, $call_count = 0)
		{
			// Actually send the email
			$host = "prpc://smtp.2.soapdataserver.com/smtp.1.php";

			$mail = new Prpc_Client ($host);
			$mailing_id = $mail->CreateMailing ("Auto Responder", $header, NULL, NULL);

			if (is_numeric ($mailing_id) && $mailing_id > 0)
			{
				$package_id = $mail->AddPackage ($mailing_id, $recipient_array, $message, array ());
				if (! (is_numeric ($package_id) && $package_id > 0))
				{
					mail ("libmail@sellingsource.com", "Error in ".__FILE__.":".__LINE__, "Adding package failed!\n\n".var_export ($package_id, 1));
					return FALSE;
				}
				else
				{
					return $mail->SendMail ($mailing_id);
				}
			}
			elseif ($call_count < 3)
			{
				mail ("libmail@sellingsource.com", "Error in ".__FILE__.":".__LINE__, "Create Mailing Failed!\n\n".var_export ($mailing_id, 1));
				Lib_Mail::_Send_Mail ($header, $recipient_array, $message, $call_count + 1);
			}

			return FALSE;
		}
	}
