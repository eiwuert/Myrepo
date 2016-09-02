<?php

/**
 * The factor trust CLH bankruptcy (BK) script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_BankruptcyCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for bankruptcy apps
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustBankruptcy_CLH($this->date);
		echo "Exporting " . count($apps) . " bankruptcy applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
