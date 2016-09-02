<?php
/**
 * Model container observer that will log the messages contained withing
 * the validation exception stack
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_ContainerValidationFailureLogging_1
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
		$stack = $observed->getValidationExceptionStack();
		foreach ($stack as $item)
		{
			$this->log->Write("Validation Exception: " . $item->getMessage());
		}
	}
}

?>