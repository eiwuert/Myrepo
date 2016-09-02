<?php

/**
 * The factor trust CLH charge-off (CO) script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_ChargeoffsCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustChargeoffs_CLH($this->date);
		echo "Exporting " . count($apps) . " charge off applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
