<?php

/**
 * The clarity paid off loans report
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_PaidInFull extends ECashCra_Scripts_Base
{
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getClarityPaidInFull($this->date);
		echo "Exporting " . count($apps) . " loans paid in full.\n";

		$this->processApplicationBase($apps);
	}
}

?>
