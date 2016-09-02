<?php

/**
 * The driver interface
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
interface ECashCra_IDriver
{
	/**
	 * Returns the various configuration variables
	 *
	 * @return string
	 */
	public function getCraApiConfig($cra_source, $item);
	
	/**
	 * Returns application objects with relevant status changes
	 * 
	 * Only status changes that occured on the given date should be returned.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getStatusChanges($date);
	
	/**
	 * Returns application objects with cancellations on the given date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getCancellations($date);
	
	/**
	 * Returns application objects with recoveries on the given date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getRecoveries($date);
	
	/**
	 * Returns application objects that are reacts and were funded on the given 
	 * date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getFundedReacts($date);
	
	/**
	 * Returns payment objects for all payments made on the given date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getPayments($date);
	
	/**
	 * Returns the current balance for the given application.
	 *
	 * @param ECashCra_Data_Application $application
	 * @return float
	 */
	public function getApplicationBalance(ECashCra_Data_Application $application);
	
	/**
	 * Translates the status of the given application into a valid CRA status.
	 *
	 * @param ECashCra_Data_Application $application
	 * @return string
	 */
	public function translateStatus(ECashCra_Data_Application $application);
	
	/**
	 * Returns the amount that was recovered for the given application on the 
	 * given date.
	 *
	 * @param ECashCra_Data_Application $application
	 * @param string $date YYYY-MM-DD
	 * @return float
	 */
	public function getRecoveryAmount(ECashCra_Data_Application $application, $date);
	
	/**
	 * Scripts will pass extra arguments used on the command line to the driver 
	 * using this function.
	 *
	 * @param array $arguments
	 * @return null
	 */
	public function handleArguments(array $arguments);
}
?>