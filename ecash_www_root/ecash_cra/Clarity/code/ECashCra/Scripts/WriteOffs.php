<?php

/**
 * The clarity write off loans report
 *
 * @package ECashCra
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */
class ECashCra_Scripts_WriteOffs extends ECashCra_Scripts_Base
{
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getClarityWriteOffs($this->date);
		echo "Exporting " . count($apps) . " write offs.\n";

		$this->processApplicationBase($apps);
	}
}

?>
