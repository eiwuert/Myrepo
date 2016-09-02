<?php
	//require_once('prpc/client.php');
	require_once('condor_transport.php');

	/**
	 *
	 * Condor transport for sending faxes through HylaFax.
	 *
	 * @author Andrew Minerd
	 * @date 2006-03-20
	 *
	 */
	class Transport_Efax implements Condor_Transport
	{

		 const TEMP_DIR = '/tmp';

		private $mime;

		protected $mode;
	//	protected $hylafax;
		protected $api_auth;
		protected $condorObject;
		protected $db_link;

		public function __construct($mode, $api_auth,$condorObject, $link)
		{
               $debug = FALSE;
                $this->condorObject = $condorObject;
                $this->mode = $mode;
                $this->api_auth = $api_auth;
                $this->db_link = $link;

		}

		public function Send($recipient, Document &$document, $dispatch_id, $from, $cover_sheet = NULL)
		{

                //Queue it up to be sent
                $doc_id = $document->Get_Archive_Id();


//                if($this->Valid_Recipient($recipient['email_primary']))
  //              {
                        $queue = new Mail_Queue($this->mode,$this->db_link);
	                 return $queue->Insert_Queue($doc_id, $from,$dispatch_id, $document->Get_Send_Priority());

    //            }
      //          else
        //        {
          //              return FALSE;
            //    }


/*

			// the format for PRPC callbacks is described in the Callback class
			$callback = self::Callback($this->mode, $dispatch_id, $this->api_auth);
			$doc_obj = $document->Get_Return_Object();
			//DO NOT QUEUE AN RTF, IT WONT WORK
			if($doc_obj->content_type == CONTENT_TYPE_TEXT_RTF)
			{
				return false;
			}

			// Get array of stdClass objects of the attached data
			$doc = $document->Get_As_PostScript();

			// Merge all the data into one array
			$content = array_merge(
				array($doc->data),
				$this->Get_Attached_Data($doc->attached_data)
			);
			$number = (is_array($recipient) ? $recipient['fax_number'] : $recipient);
			if(substr($number,0,1) != '1' && substr($number,0,3) != '702')
			{
				$number = '1'.$number;
			}
			$from = (is_array($from) ? $from['fax_number'] : $from);
			$job_id = $this->hylafax->Submit_Job($from, $number, NULL, $content, $callback, $cover_sheet);
			return $job_id;
*/
		}

		/**
		 * Returns a one dimensional array of the attached data.
		 *
		 * @param array $data
		 * @return array
		 */
		private function Get_Attached_Data($data)
		{

			$attached_data = array();

			if(is_array($data) && !empty($data))
			{
				foreach($data as $attachment)
				{
					if($attachment->content_type == CONTENT_TYPE_TEXT_HTML)
					{
						$attached_data[] = Filter_Manager::Transform($attachment->data."<!-- PAGE BREAK -->",'Html','Ps');
					}
					else if($attachment->content_type == CONTENT_TYPE_APPLICATION_PDF)
					{
						$attached_data[] = $attachment->data;
					}
					if(in_array($attachment->content_type,array(CONTENT_TYPE_TEXT_HTML,CONTENT_TYPE_APPLICATION_PDF)))
					{
						$attached_data = array_merge(
							$attached_data,
							$this->Get_Attached_Data($attachment->attached_data)
						);
					}
				}
			}

			return $attached_data;
		}

		/**
		 * Returns a URL for suitable a HylaFax callback.
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

		/**
		 * Simple check for a valid FAX number.
		 *
		 * @param mixed $recipient
		 * @return boolean
		 */
		public static function Valid_Recipient($recipient)
		{
			return true;
			if (is_array($recipient))
			{
				$recipient = (isset($recipient['fax_number']) ? $recipient['fax_number'] : FALSE);
			}

			$valid = (is_numeric($recipient) && (strlen($recipient) >= 7));
			return $valid;

		}

	}

?>
