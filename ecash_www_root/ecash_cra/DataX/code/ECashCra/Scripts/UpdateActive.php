<?php

/**
 * The active update script class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Scripts_UpdateActive extends ECashCra_Scripts_Base
{
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getActiveStatusChanges($this->date);
		$successful = $failed = 0;
		echo "Exporting " . count($apps) . " reacts.\n";

		foreach ($apps as $application)
		{
			$response = $this->createResponse();

			$packet = new ECashCra_Packet_Active($application);

			$this->getApi()->sendPacket($packet, $response);

			$response->isSuccess() ? ++$successful: ++$failed;

			$this->logMessage($response->isSuccess(), $application->getApplicationId(), $response);
		}
		echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";
	}
}

?>
