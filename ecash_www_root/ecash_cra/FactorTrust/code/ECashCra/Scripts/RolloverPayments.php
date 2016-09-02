<?php

/**
 * The factor trust new loan script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_RolloverPayments extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustRolloverPrePayments($this->date);
		echo "Exporting " . count($apps) . " rollover payment applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
