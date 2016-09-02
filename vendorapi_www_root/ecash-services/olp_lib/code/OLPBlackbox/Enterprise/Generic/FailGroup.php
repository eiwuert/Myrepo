<?php

/**
 * Simple class that provides shared storage for the targets in a fail group
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_FailGroup
{
	/**
	 * @var mixed
	 */
	protected $winner;

	/**
	 * Gets the current set winner
	 * @return string
	 */
	public function getWinner()
	{
		return $this->winner;
	}

	/**
	 * Sets the current winner
	 * @param string $winner
	 * @return void
	 */
	public function setWinner($winner)
	{
		$this->winner = $winner;
	}
}

?>