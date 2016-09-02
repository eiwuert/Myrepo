<?php
	/**
		An abstract base for messages.
	*/
	
	abstract class SourcePro_Entity_Notification extends SourcePro_Entity_Base
	{
		/// Vector of message protocols
		public $v_protocol = array();
		
		/// Flagged when a change occurs
		public $f_changed = FALSE;
		
		/**
			Initializes an instance of this class.
	
			@param protocol		The optional array of protocol objects used to send.
			@param text		The optional text for this message.
			@param code			The optional code for this message.
			@param trace			The optional trace for this message.
		*/
		function __construct ($protocol)
		{
			parent::__construct();
	
			if (!isset ($protocol))
			{
				throw new SourcePro_Exception (get_class ($this).": Mandatory parameter protocol is not set.", 1000);
			}

			$this->v_protocol = (is_array($protocol) ? $protocol : array($protocol));
		}

		function __destruct ()
		{
			parent::__destruct ();
			unset ($this->v_protocol);
		}
	
		/**
			Send the message.
		*/
		public function Send ()
		{
			$throw_error = FALSE;
			$error_message = "";
	
			foreach ($this->v_protocol as $protocol)
			{
				try
				{
					$result [] = $protocol->Send_Entity ($this);
				}	
				catch (Exception $e)
				{
					throw $e;
					
					// Add the message to the list of messages
					$error_message .= get_class ($protocol).": (".$e->getCode ().") ".$e->getMessage ()."\n";

					$throw_error = TRUE;
				}
			}
			
			// Did something throw an error?
			if ($throw_error)
			{
				// At the end, some have failed, punt
				throw new SourcePro_Exception ($error_message);
			}
			
			return $result;
		}
	}
?>
