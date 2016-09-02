<?php

/**
 * The factor trust returns script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_Voids extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustVoids($this->date);
		echo "Exporting " . count($apps) . " void applications.\n";

		$this->processApplicationBase($apps);
		
		//$apps = $driver->getFactorTrustReturnVoids($this->date);
		//echo "Exporting " . count($apps) . " void rollover applications.\n";

		//$this->processApplicationBase($apps);
	}
}

?>
