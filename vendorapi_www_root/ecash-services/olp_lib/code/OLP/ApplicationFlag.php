<?php

/**
 * Class to add/retrieve records from application_flag table
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLP_ApplicationFlag
{
	const TEST_APP		= 'TEST_APP';
	const UK_APP		= 'UK_APP';
	const IS_SOAP		= 'IS_SOAP';
	const TITLE_LOAN	= 'TITLE_LOAN';
	const CALL_CENTER	= 'CALL_CENTER';
	const SECOND_LOAN	= 'SECOND_LOAN';
	const UNSIGNED_APP	= 'UNSIGNED_APP';
	const DROP_LINK		= 'DROP_LINK';
	const DROP_LINK_REACT	= 'DROP_LINK_REACT';
	const ECASH_SIGN_DOC	= 'ECASH_SIGN_DOC';
	const MULTI_LOAN_PARENT = 'MULTI_LOAN_PARENT';
	const MULTI_LOAN_CHILD  = 'MULTI_LOAN_CHILD';
	
	/**
	 * Current application ID
	 *
	 * @var int
	 */
	protected $application_id;
	
	/**
	 * Flags that have been set
	 *
	 * @var array
	 */
	protected $flags = array();
	
	/**
	 * OLP Factory instance
	 *
	 * @var OLP_Factory
	 */
	protected $factory;

	/**
	 * Constructor
	 *
	 * @param int $application_id
	 * @param OLP_Factory $factory
	 */
	public function __construct($application_id, OLP_Factory $factory)
	{
		$this->application_id = $application_id;
		
		$this->factory = $factory;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DB_Models_Decorator_ReferencedWritableModel_1
	 */
	protected function getModel()
	{
		return $this->factory->getReferencedModel('ApplicationFlag');
	}
	
	/**
	 * Loads the flags for this application
	 *
	 * @return void
	 */
	public function loadFlags()
	{
		$flags_model = $this->getModel()->loadAllBy(array('application_id' => $this->application_id));
		
		foreach ($flags_model as $flag)
		{
			$this->flags[] = strtoupper($flag->application_type_name);
		}
	}
	
	/**
	 * Returns the current flags for this application
	 *
	 * @return array
	 */
	public function getFlags()
	{
		return $this->flags;
	}
	
	/**
	 * Checks if a flag exists
	 *
	 * @param string $name Name of the flag to check for
	 * @return bool TRUE if flag exists
	 */
	public function flagExists($name)
	{
		return in_array(strtoupper($name), $this->flags);
	}
	
	/**
	 * Adds flag for the application
	 *
	 * @param string $name Name of the flag to add
	 * @return void
	 */
	public function addFlag($name)
	{
		$name = strtoupper($name);
		
		if (!$this->flagExists($name))
		{
			$application_flag = $this->getModel();
			$application_flag->setInsertMode(DB_Models_WritableModel_1::INSERT_IGNORE);
			$application_flag->application_id = $this->application_id;
			$application_flag->application_type_name = $name;
			$application_flag->save();
			
			$this->flags[] = $name;
		}
	}
}

?>
