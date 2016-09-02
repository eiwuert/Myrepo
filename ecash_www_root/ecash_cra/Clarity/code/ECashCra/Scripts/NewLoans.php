<?php

/**
 * The clarity new loan script class
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_NewLoans extends ECashCra_Scripts_Base
{
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getClarityNewLoans($this->date);
		echo "Exporting " . count($apps) . " new loans.\n";

		$this->processApplicationBase($apps);
	}
}

?>
