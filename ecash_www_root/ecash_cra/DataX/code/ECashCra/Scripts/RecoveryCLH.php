<?php

/**
 * The recovery script class
 *
 * @package ECashCra
 */
class ECashCra_Scripts_RecoveryCLH extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for recovery
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getRecoveryCLH($this->date);
		$successful = $failed = 0;
		echo "Exporting " . count($apps) . " recovery.\n";

		foreach ($apps as $app_data)
		{
			$response = $this->createResponse();

			$application = new ECashCra_Data_ApplicationCLH($app_data);
			
			$packet = new ECashCra_Packet_RecoveryCLH($application);
			
			$this->getApi()->sendPacket($packet, $response);
			
			$response->isSuccess() ? ++$successful: ++$failed;
			
			$this->logMessage($response->isSuccess(), $application->getApplicationId(), $response);
		}
		echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";
	}
}

?>
