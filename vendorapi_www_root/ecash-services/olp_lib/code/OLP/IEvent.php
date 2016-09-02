<?php

interface OLP_IEvent
{
	/**
	 * Gets the type of even this thing is.
	 *
	 * @return string|int Identifier for this event type.
	 */
	public function getType();
}

?>