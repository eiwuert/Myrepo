<?php

/**
 * The factor trust CLH payment (PM) script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_PaymentsCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustPayments_CLH($this->date);
		echo "Exporting " . count($apps) . " payment applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
