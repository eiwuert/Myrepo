<?php

/**
 * The factor trust CLH new loan (NL) script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_NewLoansCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getFactorTrustNewLoans_CLH($this->date);
		echo "Exporting " . count($apps) . " new loan applications.\n";

		$this->processApplicationBase($apps);
	}
}

?>
