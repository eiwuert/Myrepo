<?php

/**
 * The cancel script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_CancelCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for cancel
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getCancelCLH($this->date);
		$successful = $failed = 0;
		echo "Exporting " . count($apps) . " cancel.\n";

		foreach ($apps as $app_data)
		{
			$response = $this->createResponse();

			$application = new ECashCra_Data_ApplicationCLH($app_data);
			
			$packet = new ECashCra_Packet_CancelCLH($application);
			
			$this->getApi()->sendPacket($packet, $response);
			
			$response->isSuccess() ? ++$successful: ++$failed;
			
			$this->logMessage($response->isSuccess(), $application->getApplicationId(), $response);
		}
		echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";
	}
}

?>
