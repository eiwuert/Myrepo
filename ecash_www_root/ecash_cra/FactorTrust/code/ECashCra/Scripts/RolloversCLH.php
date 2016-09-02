<?php

/**
 * The factor trust CLH rollover (RO) script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_RolloversCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustRolloverLoans_CLH($this->date);
		echo "Exporting " . count($apps) . " rolover applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
