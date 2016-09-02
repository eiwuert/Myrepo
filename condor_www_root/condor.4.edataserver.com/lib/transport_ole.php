<?php

	require_once('prpc/client.php');
	require_once('condor_transport.php');
	require_once('ole_hack.php');

	/**
	 *
	 * Transport for sending emails through OLE.
	 *
	 * @author Andrew Minerd
	 * @date 2006-03-20
	 *
	 */
	class Transport_OLE implements Condor_Transport
	{

		// the property ID used for OLE
		const PROPERTY_ID = 17176;

		protected $ole;
		protected $mode;
		protected $api_auth;
		protected $condorObject;

		public function __construct($mode, $api_auth,$condorObject)
		{

			$debug = FALSE;
			$this->condorObject = $condorObject;
			$url = 'prpc://smtp.2.soapdataserver.com/ole_smtp.1.php';
			$this->ole = new PRPC_Client($url, $debug);
			$this->mode = $mode;

			$this->api_auth = $api_auth;
	
			return;

		}

		/**
		 * Sends an email through OLE. The $recipient parameter expects an array
		 * with the name and email of the recipient with email_primary_name as the key
		 * for the name and email_primary as the key for the email address.
		 *
		 * This version of Send will probably never use $cover_sheet.
		 *
		 * @param array $recipient
		 * @param Document $document
		 * @param int $dispatch_id
		 * @param string $cover_sheet
		 * @return int
		 */
		public function Send($recipient, Document &$document, $dispatch_id, $from, $cover_sheet = NULL)
		{

			// where we receive status notifications
			$callback = self::Callback($this->mode, $dispatch_id, $this->api_auth);
				
			// <OLE hack>

			$hack = new OLE_Hack($this->mode);
			$event_name = 'CONDOR_'.strtoupper($document->Get_Template_Name());

			// insert a dummy OLE event if it doesn't already exist
			if (!$hack->Event_Exists(self::PROPERTY_ID, $event_name))
			{
				$hack->Create_Dummy_Event(self::PROPERTY_ID, $event_name);
			}
			
			// </OLE hack>

			// upload attachments into OLE
			$doc_obj = $document->Get_Return_Object();

			//If the root is an RTF send it as an attachment
			if($doc_obj->content_type == CONTENT_TYPE_TEXT_RTF)
			{
				$root_attachment = $this->ole->Add_Attachment($doc_obj->data, 
					CONTENT_TYPE_TEXT_RTF,
					$doc_obj->template_name.'.rtf',
					'ATTACH');
				$data = 'The document was not of a standard email type. It has been sent as an attachment.';
			}
			else
			{
				$data = $doc_obj->data;
			}
			$attach_ids = $this->Upload_Attachments(
				$this->ole,
				$doc_obj->attached_data,
				$data
			);
			if(is_numeric($root_attachment))
			{
				$attach_ids[] = $root_attachment;
			}
			/*
				Take into consideration that there may not be an email_primary_name defined even if
				recipient is an array.
			*/
			if(is_array($recipient))
			{
				$email_primary_name = strlen($recipient['email_primary_name']) ? $recipient['email_primary_name'] : $recipient['email_primary'];
			}
			else
			{
				$email_primary_name = $recipient;
			}

			$ole_data = array(
				'from' => $from,
				'email_primary_name' => $email_primary_name,
				'email_primary' => (is_array($recipient) ? $recipient['email_primary'] : $recipient),
				'subject' => $document->Get_Subject(),
				'body' => $data,
				'attachment_id' => $attach_ids,
				'site_name' => 'condor.edataserver.com',
			);
			// send the message to OLE
			$queue_id = $this->ole->Ole_Send_Mail($event_name, self::PROPERTY_ID,  $ole_data, $callback);

			return $queue_id;

		}

		/**
		 * Uploads the attachments to the document to OLE.
		 *
		 * @param resource $ole
		 * @param array $attachments
		 * @param string $root
		 * @return array
		 */
		protected function Upload_Attachments(&$ole, $attachments, $root)
		{

			$attach_ids = array();

			foreach ($attachments as $child)
			{

				// for now, we assume that the attachment should be "embedded" if we
				// can find it's URI in the root document... otherwise, it gets attached
				$method = ($root && $child->uri && (strpos($root, $child->uri) !== FALSE)) ? 'EMBED' : 'ATTACH';

				if (empty($child->uri))
				{
					/*
						Currently generates a random number for the filename. We may want to change this in the
						near future so that each attachment can use it's template name as at least part of the
						filename/URI.
					*/
					$uri = rand(100, 1000).Filter_Manager::Get_Extension($child->content_type);
				}
				else
				{
					$uri = $child->uri;
				}

				// insert the attachment into OLE's database and return the attachment ID
				$attach_ids[] = $ole->Add_Attachment($child->data, $child->content_type, $uri, $method);

				if (count($child->attached_data))
				{

					// add sub-attachments
					$temp = $this->Upload_Attachments($ole, $child->attached_data, FALSE);

					// merge them in
					$attach_ids = array_merge($temp, $attach_ids);

				}

			}

			return $attach_ids;

		}

		/**
		 * Returns a URL for suitable an OLE callback.
		 *
		 * @param array $data
		 * @return array
		 */
		protected static function Callback($mode, $dispatch_id, $api_auth)
		{

			$callback = NULL;

			switch ($mode)
			{

				case MODE_DEV:
					$host = 'condor.4.edataserver.com.gambit.tss:8080';
					break;

				case MODE_RC:
					$host = 'rc.condor.4.edataserver.com';
					break;

				case MODE_LIVE:
					$host = 'condor.4.edataserver.com';
					break;

			}

			if (isset($host))
			{
				$callback = 'prpc://'.$api_auth.'@'.$host.'/condor_api.php/Update_Status?dispatch_id='.$dispatch_id.'&status=%%status%%';
			}

			return $callback;

		}

		public static function Valid_Recipient($recipient)
		{

			$valid = (strpos($recipient, '@') !== FALSE);
			return $valid;

		}

	}

?>
