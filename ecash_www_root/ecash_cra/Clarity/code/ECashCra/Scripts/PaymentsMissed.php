<?php

/**
 * The clarity payment missed and returned script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_PaymentsMissed extends ECashCra_Scripts_Base
{
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getClarityPaymentsMissed($this->date);
		echo "Exporting " . count($apps) . " payments missed (returns).\n";

		$this->processApplicationBase($apps);
	}
}

?>
