<?php

class ECashCra_Driver_Commercial_Driver implements ECashCra_IDriver 
{
	/**
	 * @var ECashCra_Driver_CLK_DataBuilder
	 */
	protected $data_builder;
	
	/**
	 * @var ECashCra_Driver_CLK_ApplicationQueryBuilder
	 */
	protected $application_query_builder;
	
	/**
	 * @var ECashCra_Driver_CLK_PaymentQueryBuilder
	 */
	protected $payment_query_builder;
	
	/**
	 * @var ECashCra_Driver_CLK_Config
	 */
	protected $config;
	
	/**
	 * @var Status_Utility
	 */
	protected $status_map;

	public function __construct()
	{
		$this->data_builder = new ECashCra_Driver_Commercial_DataBuilder;
		$this->application_query_builder = new ECashCra_Driver_Commercial_ApplicationQueryBuilder;
		$this->payment_query_builder = new ECashCra_Driver_Commercial_PaymentQueryBuilder;
		$this->config = new ECashCra_Driver_Commercial_Config;
	}
	
	/**
	 * Returns the url used to connect to cra
	 *
	 * @return string
	 */
	public function getCraApiUrl()
	{
		return $this->config->getApiUrl();
	}
	
	/**
	 * Returns the username used to connect to cra
	 * 
	 * @return string
	 */
	public function getCraApiUsername()
	{
		return $this->config->getApiUsername();
	}
	
	/**
	 * Returns the password used to connect to cra
	 *
	 * @return string
	 */
	public function getCraApiPassword()
	{
		return $this->config->getApiPassword();
	}
	
	/**
	 * Returns application objects with relevant status changes
	 * 
	 * Only status changes that occured on the given date should be returned.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getStatusChanges($date)
	{
		$args = array();
		$query = $this->application_query_builder->getStatusHistoryQuery(
			$date, 
			$this->getUpdatableStatusIds(), 
			$this->config->getCompany(), 
			$args
		);
		
		//$st = $this->config->getConnection()->prepare($query);
		$rs = $this->queryPrepared($query,$args);
		
		$this->data_builder->attachObserver(
			new Delegate_1(array($this, 'setApplicationBalance'))
		);
		
		$this->data_builder->attachObserver(
			new Delegate_1(array($this, 'setStatusChain'))
		);
		
		return $this->data_builder->getApplicationData($rs);
	}
	
	/**
	 * Returns application objects with relevant status changes
	 * 
	 * Only status changes that occured on the given date should be returned.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getApplicationStatusChanges($application_id)
	{
		$args = array();
		$query = $this->application_query_builder->getApplicationStatusHistoryQuery(
			$application_id, 
			$this->getUpdatableStatusIds(), 
			$this->config->getCompany(), 
			$args
		);
		
		//$st = $this->config->getConnection()->prepare($query);
		$rs = $this->queryPrepared($query,$args);
		
		$this->data_builder->attachObserver(
			new Delegate_1(array($this, 'setApplicationBalance'))
		);
		
		$this->data_builder->attachObserver(
			new Delegate_1(array($this, 'setStatusChain'))
		);
		
		return $this->data_builder->getApplicationData($rs);
	}
	

	/**
	 * Returns application objects with cancellations on the given date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getCancellations($date)
	{
		$args = array();
		
		$trans_query = $this->application_query_builder->getCancellationTransactionsQuery(
			$date, 
			$this->config->getCompany(), 
			$args
		);
		$trans_rs = DB_Util_1::queryPrepared($this->config->getConnection(), $trans_query, $args);
		$trans_apps = $this->data_builder->getApplicationData($trans_rs);
		
		$args = array();
		$stat_query = $this->application_query_builder->getCancellationStatusesQuery(
			$date, 
			$this->getCancellationStatusIds(),
			$this->config->getCompany(),
			$args);
		$stat_rs = DB_Util_1::queryPrepared($this->config->getConnection(), $stat_query, $args);
		$stat_apps = $this->data_builder->getApplicationData($stat_rs);
		
		return array_merge($trans_apps, $stat_apps);
	}
	
	/**
	 * Returns application objects with cancellations on the given date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getApplicationCancellations($application_id)
	{
		$args = array();
		
		$query = $this->application_query_builder->getApplicationCancellationsQuery(
			$application_id, 
			$this->config->getCompany(), 
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		
		return $this->data_builder->getApplicationData($rs);
	}
	
	/**
	 * Returns application objects with recoveries on the given date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getRecoveries($date)
	{
		$args = array();
		$query = $this->application_query_builder->getRecoveriesQuery(
			$date, 
			$this->config->getCompany(), 
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		
		$this->data_builder->attachObserver(
			new Delegate_1(array($this, 'setApplicationBalance'))
		);
		$this->data_builder->attachObserver(
			new Delegate_1(array($this, 'setRecoveryAmount'))
		);
		
		return $this->data_builder->getApplicationData($rs);
	}
	
	/**
	 * Returns application objects with recoveries on the given date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getApplicationRecoveries($application_id)
	{
		$args = array();
		$query = $this->application_query_builder->getApplicationRecoveriesQuery(
			$application_id, 
			$this->config->getCompany(), 
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		
		$this->data_builder->attachObserver(
			new Delegate_1(array($this, 'setApplicationBalance'))
		);
		$this->data_builder->attachObserver(
			new Delegate_1(array($this, 'setRecoveryAmount'))
		);
		
		return $this->data_builder->getApplicationData($rs);
	}
	
	/**
	 * Returns application objects with failed re-disbursements on the given date
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getActiveStatusChanges($date)
	{
		$args = array();
		$disb_query = $this->application_query_builder->getFailedRedisbursementsQuery(
			$date,
			$this->config->getCompany(),
			$args
		);

		$disb_rs = DB_Util_1::queryPrepared($this->config->getConnection(), $disb_query, $args);
		$disb_apps = $this->data_builder->getApplicationData($disb_rs);
		
		$args = array();
		$inactive_query = $this->application_query_builder->getStatusChangesFromInactiveQuery(
			$date,
			$this->config->getCompany(),
			$args
		);
		$inactive_rs = DB_Util_1::queryPrepared($this->config->getConnection(), $inactive_query, $args);
		$inactive_apps = $this->data_builder->getApplicationData($inactive_rs);

		return array_merge(
			$disb_apps,
			$inactive_apps
		);
	}
	/**
	 * Returns application objects that are reacts and were funded on the given 
	 * date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getFundedReacts($date)
	{
		$args = array();
		$query = $this->application_query_builder->getFundedReacts(
			$date, 
			$this->config->getCompany(), 
			$this->getActiveStatusId(),
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		
		return $this->data_builder->getApplicationData($rs);
	}
	
	/**
	 * Returns application objects that are reacts and were funded on the given 
	 * date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getApplicationFunded($application_id)
	{
		$args = array();
		$query = $this->application_query_builder->getApplicationFunded(
			$application_id, 
			$this->config->getCompany(), 
			$this->getActiveStatusId(),
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		
		return $this->data_builder->getApplicationData($rs);
	}
	
	/**
	 * Returns payment objects for all payments made on the given date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getPayments($date)
	{
		return array_merge(
			$this->getNonAchPayments($date),
			$this->getAchFailures($date),
			$this->getAchPayments($date)
		);
	}
	
	/**
	 * Returns payment objects for all payments made on the given date.
	 *
	 * @param string $date YYYY-MM-DD
	 * @return array
	 */
	public function getApplicationPayments($application_id)
	{
		return array_merge(
			$this->getAppNonAchPayments($application_id),
			$this->getAppAchFailures($application_id),
			$this->getAppAchPayments($application_id)
		);
	}
	
	protected function getNonAchPayments($date)
	{
		$args = array();
		$query = $this->payment_query_builder->getNonACHPaymentsQuery(
			$date,
			$this->config->getCompany(),
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		
		return $this->data_builder->getPaymentData($rs);
	}
	
	protected function getAchFailures($date)
	{
		$args = array();
		$query = $this->payment_query_builder->getACHReturnsQuery(
			$date,
			$this->config->getCompany(),
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		return $this->data_builder->getPaymentData($rs);
	}
	
	protected function getAchPayments($date)
	{
		$args = array();
		$query = $this->payment_query_builder->getACHPaymentsQuery(
			$date,
			$this->config->getCompany(),
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		return $this->data_builder->getPaymentData($rs);
	}
	
	protected function getAppNonAchPayments($application_id)
	{
		$args = array();
		$query = $this->payment_query_builder->getAppNonACHPaymentsQuery(
			$application_id,
			$this->config->getCompany(),
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		
		return $this->data_builder->getPaymentData($rs);
	}
	
	protected function getAppAchFailures($application_id)
	{
		$args = array();
		$query = $this->payment_query_builder->getAppACHReturnsQuery(
			$application_id,
			$this->config->getCompany(),
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		return $this->data_builder->getPaymentData($rs);
	}
	
	protected function getAppAchPayments($application_id)
	{
		$args = array();
		$query = $this->payment_query_builder->getAppACHPaymentsQuery(
			$application_id,
			$this->config->getCompany(),
			$args
		);
		
		$rs = $this->queryPrepared($query, $args);
		return $this->data_builder->getPaymentData($rs);
	}
	
	/**
	 * Returns the current balance for the given application.
	 *
	 * @param ECashCra_Data_Application $application
	 * @return float
	 */
	public function getApplicationBalance(ECashCra_Data_Application $application)
	{
		$aux_data = new ECashCra_Driver_Commercial_DataDecorator($application);
		return $aux_data->balance;
	}
	
	/**
	 * Translates the status of the given application into a valid CRA status.
	 *
	 * @param ECashCra_Data_Application $application
	 * @return string
	 */
	public function translateStatus(ECashCra_Data_Application $application)
	{
		$aux_data = new ECashCra_Driver_Commercial_DataDecorator($application);
		$status_chain = $aux_data->status_chain;
		
		switch ($status_chain)
		{
			case 'sent::external_collections::*root':
			case 'chargeoff::collections::customer::*root':
				return ECashCra_Scripts_UpdateStatuses::STATUS_CHARGEOFF;
				break;
				
			case 'recovered::external_collections::*root':
				return ECashCra_Scripts_UpdateStatuses::STATUS_FULL_RECOVERY;
				break;
				
			case 'paid::customer::*root':
				return ECashCra_Scripts_UpdateStatuses::STATUS_CLOSED;
				break;
				
			default:
				return NULL;
		}
	}
	
	/**
	 * Returns the amount that was recovered for the given application on the 
	 * given date.
	 *
	 * @param ECashCra_Data_Application $application
	 * @param string $date YYYY-MM-DD
	 * @return float
	 */
	public function getRecoveryAmount(ECashCra_Data_Application $application, $date)
	{
		$aux_data = new ECashCra_Driver_Commercial_DataDecorator($application);
		return empty($aux_data->recovery_amount) 
			? 0 
			: $aux_data->recovery_amount;
	}
	
	/**
	 * Scripts will pass extra arguments used on the command line to the driver 
	 * using this function.
	 *
	 * @param array $arguments
	 * @return null
	 */
	public function handleArguments(array $arguments)
	{
		try {
			$this->config->useArguments($arguments);
		}
		catch (InvalidArgumentException $e)
		{
			die($e->getMessage() . "\n");
		}
		
		require_once(dirname(__FILE__).'/../../../../www/config.php');
	}
	
	protected function getUpdatableStatusIds()
	{
		$retval = array();
		$status = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		foreach($this->config->getUpdateableStatuses() as $chain)
		{
			try
			{
				$retval[] = $status->toId($chain);
			}
			catch(Exception $e)
			{
				//this means that there was a status that isn't valid for this company
				continue;
			}
		}
		return $retval;
	}
        protected function getCancellationStatusIds()
        {
               $status = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
               foreach($this->config->getCancellationStatuses() as $chain)
               {
                       try
                       {
                               $retval[] = $status->toId($chain);
                       }
                       catch(Exception $e)
                       {
                               //this means that there was a status that isn't valid for this company
                               continue;
                       }
               }
               return $retval;
        }

	protected function getActiveStatusId()
	{
		$status = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		return $status->toid($this->config->getActiveStatus());
	}
	
	public function setApplicationBalance(ECashCra_Data_Application $application, array $db_row)
	{
		$aux_data = new ECashCra_Driver_Commercial_DataDecorator($application);
		$aux_data->balance = $db_row['balance'];
	}
	
	public function setStatusChain(ECashCra_Data_Application $application, array $db_row)
	{
		$aux_data = new ECashCra_Driver_Commercial_DataDecorator($application);
		$status = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$aux_data->status_chain = $status->toName($db_row['application_status_id']);
	}
	
	public function setRecoveryAmount(ECashCra_Data_Application $application, array $db_row)
	{
		$aux_data = new ECashCra_Driver_Commercial_DataDecorator($application);
		$aux_data->recovery_amount = $db_row['recovery_amount'];
	}
	
	private function queryPrepared($query, $args)
	{
		//var_dump($query, $args);
		return $this->config->getConnection()->queryPrepared($query, $args);
	}
}

?>
