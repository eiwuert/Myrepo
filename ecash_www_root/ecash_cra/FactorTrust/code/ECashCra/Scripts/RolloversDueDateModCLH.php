<?php

/**
 * The factor trust CLH rollover (RO) script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_RolloversDueDateModCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustRolloverLoansDueDateMod_CLH($this->date);
		echo "Exporting " . count($apps) . " rolover due date mod applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
