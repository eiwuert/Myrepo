<?php

/**
 * The export recoveries script class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Scripts_ExportRecoveries extends ECashCra_Scripts_Base
{
	/**
	 * Processes recoveries
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getRecoveries($this->date);
		$successful = $failed = 0;

		foreach ($apps as $application)
		{
			$response = $this->createResponse();
			
			$packet = new ECashCra_Packet_Recovery(
				$application,
				$this->date,
				$driver->getRecoveryAmount($application, $this->date),
				$driver->getApplicationBalance($application)
			);
			
			$this->getApi()->sendPacket($packet, $response);
			
			$response->isSuccess() ? ++$successful: ++$failed;
			
			$this->logMessage($response->isSuccess(), $application->getApplicationId(), $response);
		}
		if ($failed > 0)
		{
			echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";
		}
	}
}

?>
