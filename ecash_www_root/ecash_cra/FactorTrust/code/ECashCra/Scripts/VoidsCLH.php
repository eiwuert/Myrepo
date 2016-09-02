<?php

/**
 * The factor trust CLH void (VO) script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_VoidsCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustVoids_CLH($this->date);
		echo "Exporting " . count($apps) . " void applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
