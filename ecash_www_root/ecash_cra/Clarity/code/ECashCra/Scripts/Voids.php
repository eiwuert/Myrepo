<?php

/**
 * The clarity voided loans report
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_Voids extends ECashCra_Scripts_Base
{
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getClarityVoids($this->date);
		echo "Exporting " . count($apps) . " voids.\n";

		$this->processApplicationBase($apps);
	}
}

?>
