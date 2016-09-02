<?php

/**
 * The factor trust returns script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_Chargeoffs extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustChargeoffs($this->date);
		echo "Exporting " . count($apps) . " charge off applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
