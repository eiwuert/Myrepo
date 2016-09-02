<?php

/**
 * The export cancels script class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Scripts_ExportCancels extends ECashCra_Scripts_Base
{
	/**
	 * Processes cancellations
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getCancellations($this->date);
		$successful = $failed = 0;

		foreach ($apps as $application)
		{
			$response = $this->createResponse();
			
			$packet = new ECashCra_Packet_Cancellation(
				$application, 
				$this->date
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
