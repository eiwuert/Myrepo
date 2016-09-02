<?php

/**
 * The clarity collections loans report
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_Collections extends ECashCra_Scripts_Base
{
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getClarityCollections($this->date);
		echo "Exporting " . count($apps) . " collections.\n";

		$this->processApplicationBase($apps);
	}
}

?>
