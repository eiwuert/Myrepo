<?php

/**
 * The factor trust new loan script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_OldZeroBalance extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustOldZeroBalance($this->date);
		echo "Exporting " . count($apps) . " zero balance applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
