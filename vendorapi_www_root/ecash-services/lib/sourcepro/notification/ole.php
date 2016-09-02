<?php
	class SourcePro_Notification_Ole extends SourcePro_Notification_Base
	{
		private $m_ole_url = NULL;
		
		private $m_event_id = NULL;

		public function __construct ($ole_url, $event_id)
		{
			if (!isset ($ole_url))
			{
				throw new SourcePro_Exception (get_class ($this).": Mandatory parameter prpc_url is not set.", 1000);
			}
			
			if (!isset ($event_id))
			{
				throw new SourcePro_Exception (get_class ($this).": Mandatory parameter event_id is not set.", 1000);
			}
			
			$this->m_ole_url = $ole_url;
			$this->m_event_id = $event_id;
		}
		
		public function Send_Entity ($entity)
		{
			$data = array ();
			
			//TODO: Add glob elements for email template ease.
			foreach ($entity->v_asset as $name => $value)
			{
				$data [$name] = $entity->$name;
			}
			
			foreach ($entity->v_field as $name => $value)
			{
				$data [$name] = $entity->$name;
			}
			
			$smtp = new SourcePro_Prpc_Client ($this->m_ole_url, 1);
			
			// Set up the loop parameters
			$tries = 0;
			$result = 0;
			
			do
			{
				// Attempt to send
				$result = $smtp->Ole_Send_Mail ($this->m_event_id, $data);
				
				// Should I wait?
				if ($result == 0)
				{
					sleep (1);
				}
				
				// Bounce the number  of attempts
				$tries ++;
				
			} while ($result == 0 && $tries < 4);
			
			return $result;
		}
	}
?>