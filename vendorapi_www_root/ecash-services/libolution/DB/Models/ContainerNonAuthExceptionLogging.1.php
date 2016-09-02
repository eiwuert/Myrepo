<?php
/**
 * Model container observer that will log the message set by a
 * swallowed non-authoritative model exception within a container
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_ContainerNonAuthExceptionLogging_1
		implements DB_Models_IContainerObserver_1
{
	/**
	 * @var Applog
	 */
	protected $log;

	/**
	 * Constructor
	 *
	 * @param Applog $app_log
	 * @return void
	 */
	function __construct(Applog $app_log)
	{	
		$this->log = $app_log;
	}
	
	/**
	 * @param DB_Models_IContainer_1 $observed
	 * @return void
	 * @see DB_Models_IContainerObserver_1::update()
	 */
	public function update(DB_Models_IContainer_1 $observed)
	{	
		$exception = $observed->getNonAuthoritativeModelException();
		$this->log->Write(sprintf(
			"Non-authoritative model exception from container in File: %s Line: %s Message: %s",
			$exception->getFile(),
			$exception->getLine(),
			$exception->getMessage()));
	}
}

?>