<?php
	/**
	 * Doesn't really do anything. Just provides a type for queue-specific
	 * exceptions, so they can be easily distinguished from database errors and other things
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class ECash_Queues_QueueException extends Exception { }
?>