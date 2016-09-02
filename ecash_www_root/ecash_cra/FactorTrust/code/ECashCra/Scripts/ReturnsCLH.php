<?php

/**
 * The factor trust CLH returns (RI) script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_ReturnsCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustReturns_CLH($this->date);
		echo "Exporting " . count($apps) . " returns applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
