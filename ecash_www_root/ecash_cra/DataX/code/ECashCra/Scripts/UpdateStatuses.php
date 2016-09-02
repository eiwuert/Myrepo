<?php

/**
 * The update status script class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Scripts_UpdateStatuses extends ECashCra_Scripts_Base
{
	const STATUS_CLOSED = 'closed';
	const STATUS_FULL_RECOVERY = 'full_recovery';
	const STATUS_CHARGEOFF = 'chargeoff';
	
	/**
	 * Processes status updates
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getStatusChanges($this->date);
		$successful = $failed = 0;

		foreach ($apps as $application)
		{
			$response = $this->createResponse();
			
			/* @var $application ECashCra_Data_Application */
			switch ($driver->translateStatus($application))
			{
				case self::STATUS_CHARGEOFF:
					$packet = new ECashCra_Packet_ChargeOff(
						$application,
						$this->date,
						$driver->getApplicationBalance($application)
					);
					break;
				
				case self::STATUS_FULL_RECOVERY:
					$packet = new ECashCra_Packet_Recovery(
						$application, 
						$this->date,
						0,
						0
					);
					break;
				
				case self::STATUS_CLOSED:
					$packet = new ECashCra_Packet_PaidOff(
						$application,
						$this->date
					);
					break;
				
				default:
					continue(2);
			}
			
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
