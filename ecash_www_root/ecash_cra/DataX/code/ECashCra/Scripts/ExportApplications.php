<?php

/**
 * The export cancels script class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Scripts_ExportApplications extends ECashCra_Scripts_Base
{
	const STATUS_CLOSED = 'closed';
	const STATUS_FULL_RECOVERY = 'full_recovery';
	const STATUS_CHARGEOFF = 'chargeoff';

	private $application_ids = array();
	
	/**
	 * Sets the file which contains the applications
	 *
	 * @param string $filename
	 */
	public function setFile($filename)
	{
		if(!is_file($filename))
		{
			throw new Exception("Not a valid file.");
		}
		$this->application_ids = explode("\n", file_get_contents($filename));
	}
	
	/**
	 * Processes the applications that were in the passed file
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		foreach($this->application_ids as $application_id) 
		{
			echo "Exporting information for $application_id.\n";
			$this->processFunding($driver, $application_id);
			$this->processCancels($driver, $application_id);
			$this->processPayments($driver, $application_id);
			$this->processRecoveries($driver, $application_id);
			$this->processStatusChanges($driver, $application_id);
			echo "\n\n";
		}
	}
	
	/**
	 * Processes fund updates for reacts
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processFunding(ECashCra_IDriver $driver, $application_id)
	{
		$app = $driver->getApplicationFunded($application_id);
		echo "Exporting funding info.\n";

		foreach($app as $application) {
			$response = $this->createResponse();
			
			$packet = new ECashCra_Packet_FundUpdate($application);
			
			$this->getApi()->sendPacket($packet, $response);
			
			$response->isSuccess() ? ++$successful: ++$failed;
			
			$this->logMessage($response->isSuccess(), $application->getApplicationId(), $response);
			echo ($response->isSuccess() ? "Success." : "Failure.") . "\n";
		}
	}

	/**
	 * processes any cancels for this application
	 *
	 * @param ECashCra_IDriver $driver
	 * @param unknown_type $application_id
	 */
	private function processCancels(ECashCra_IDriver $driver, $application_id)
	{
		$cancels = $driver->getApplicationCancellations($application_id);
		$successful = $failed = 0;
		echo "Exporting " . count($cancels) . " cancels.\n";

		foreach ($cancels as $cancel)
		{
			$response = $this->createResponse();
			
			$packet = new ECashCra_Packet_Cancellation(
				$cancel, 
				$cancel->date
			);
			
			$this->getApi()->sendPacket($packet, $response);
			
			$response->isSuccess() ? ++$successful: ++$failed;
			
			$this->logMessage($response->isSuccess(), $cancel->getApplicationId(), $response);
		}
		echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";		
	}
	
	/**
	 * Processes payment updates
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processPayments(ECashCra_IDriver $driver, $application_id)
	{
		$payments = $driver->getApplicationPayments($application_id);
		$successful = $failed = 0;
		echo "Exporting " . count($payments) . " payments.\n";

		foreach ($payments as $payment)
		{
			$response = $this->createResponse();
			
			$packet = new ECashCra_Packet_TradelinePayments($payment);
			
			$this->getApi()->sendPacket($packet, $response);
			
			$response->isSuccess() ? ++$successful: ++$failed;
			
			$this->logMessage($response->isSuccess(), $payment->getId(), $response);
		}
		echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";
	}

	/**
	 * Processes status updates
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processStatusChanges(ECashCra_IDriver $driver, $application_id)
	{
		$apps = $driver->getApplicationStatusChanges($application_id);
		$successful = $failed = 0;
		echo "Exporting " . count($apps) . " status changes.\n";

		foreach ($apps as $application)
		{
			$response = $this->createResponse();
			
			/* @var $application ECashCra_Data_Application */
			switch ($driver->translateStatus($application))
			{
				case self::STATUS_CHARGEOFF:
					$packet = new ECashCra_Packet_ChargeOff(
						$application,
						$application->date,
						$driver->getApplicationBalance($application)
					);
					break;
				
				case self::STATUS_FULL_RECOVERY:
					$packet = new ECashCra_Packet_Recovery(
						$application, 
						$application->date,
						0,
						0
					);
					break;
				
				case self::STATUS_CLOSED:
					$packet = new ECashCra_Packet_PaidOff(
						$application,
						$application->date
					);
					break;
				
				default:
					continue(2);
			}
			
			$this->getApi()->sendPacket($packet, $response);
			
			$response->isSuccess() ? ++$successful: ++$failed;
			
			$this->logMessage($response->isSuccess(), $application->getApplicationId(), $response);
		}
		echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";
	}

	/**
	 * Processes recoveries
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processRecoveries(ECashCra_IDriver $driver, $application_id)
	{
		$apps = $driver->getApplicationRecoveries($application_id);
		$successful = $failed = 0;
		echo "Exporting " . count($apps) . " recoveries.\n";

		foreach ($apps as $application)
		{
			$response = $this->createResponse();
			
			$packet = new ECashCra_Packet_Recovery(
				$application,
				$application->date,
				$driver->getRecoveryAmount($application, $application->date),
				$driver->getApplicationBalance($application)
			);
			
			$this->getApi()->sendPacket($packet, $response);
			
			$response->isSuccess() ? ++$successful: ++$failed;
			
			$this->logMessage($response->isSuccess(), $application->getApplicationId(), $response);
		}
		echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";
	}
}

?>
