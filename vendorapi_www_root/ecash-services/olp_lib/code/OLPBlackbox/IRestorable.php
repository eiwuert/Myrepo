<?php
/**
 * Interface for classes that can have their runtime state saved and restored via
 * sleep() and wakeup().  These are often times classes that need to be built with
 * factories or are too large when serialized
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
interface OLPBlackbox_IRestorable
{
	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function sleep();

	/**
	 * Restore the runtime state to from a previous sleep 
	 *
	 * @param array $data Data to restore the object's state
	 * @return void
	 */
	public function wakeup(array $data);
}

?>