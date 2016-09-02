<?php
	/**
	 * @package Message
	 */

	/**
	 * Interface for message consumers
	 * @author Rodric Glaser <rodric.glaser@sellingsource.com>
	 */
	interface Message_IConsumer_1
	{
		/**
		 * Consume a message
		 *
		 * @param Message_Container_1 $msg
		 * @return void
		 */
		public function consumeMessage(Message_Container_1 $msg);
	}

?>
