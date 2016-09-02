<?php

/**
 * A rule that does... nothing.
 *
 * In the future, this will hit an event.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_Skip implements Blackbox_IRule
{
	/**
	 * Returns TRUE
	 * @see lib/blackbox/Blackbox/Blackbox_IRule#isValid()
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		return TRUE;
	}
}

?>
