<?php

/**
 * The update payments script class
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECashCra_Scripts_UpdatePayments extends ECashCra_Scripts_Base
{
	/**
	 * Processes payment updates
	 *
	 * <b>Revision History</b>
 	 * <ul>
 	 *     <li><b>2008-10-29 - alexanderl</b><br>
 	 *         passed application id to the output (logMessage) [#18902]
 	 *     </li>
 	 * </ul>
	 *
	 * @param ECashCra_IDriver $driver
	 * @return null
	 */
	public function processApplications(ECashCra_IDriver $driver)
	{
		$apps = $driver->getPayments($this->date);
		$successful = $failed = 0;

		foreach ($apps as $payment)
		{
			$response = $this->createResponse();
			
			$packet = new ECashCra_Packet_TradelinePayments($payment);
			
			$this->getApi()->sendPacket($packet, $response);
			
			$response->isSuccess() ? ++$successful: ++$failed;
			
			$application_id = $payment->getApplication()->getApplicationId(); //#18902			
			$this->logMessage($response->isSuccess(), $payment->getId(), $response, $application_id); //#18902
		}
		if ($failed > 0)
		{
			echo ($successful + $failed) . " attempted, " . $successful . " good, " . $failed . " bad.\n";
		}
	}
}

?>
