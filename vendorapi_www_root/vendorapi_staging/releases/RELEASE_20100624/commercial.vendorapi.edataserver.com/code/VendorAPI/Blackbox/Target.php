<?php

/**
 * Target for the vendor API; mainly exists to return the correct winner
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Target extends Blackbox_Target
{
	/**
	 * Gets the winner instance
	 *
	 * @param Blackbox_Data $data
	 * @return VendorAPI_Blackbox_Winner
	 */
	protected function getWinner(Blackbox_Data $data)
	{
		$hist = $this->state_data->customer_history;

		return new VendorAPI_Blackbox_Winner(
			$this,
			$hist
		);
	}
}

?>
