<?php
	/**
		An abstract base for objects that can send messages.
	*/

	abstract class SourcePro_Notification_Base extends SourcePro_Metaobject
	{
		function __construct ()
		{
			parent::__construct ();
		}
		
		abstract public function Send_Entity ($entity);
	}
?>