<?php

/**
 * The clarity payment missed for extended anoubt of time script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_PaymentsCaughtUp extends ECashCra_Scripts_Base
{
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getClarityPaymentsCaughtUp($this->date);
		echo "Exporting " . count($apps) . " payments caught up.\n";

		$this->processApplicationBase($apps);
	}
}

?>
