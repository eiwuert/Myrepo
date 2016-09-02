<?php

/**
 * The factor trust CLH payment (PM) script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_PaymentsDueDateModCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustPaymentsDueDateMod_CLH($this->date);
		echo "Exporting " . count($apps) . " payment due date mod applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
