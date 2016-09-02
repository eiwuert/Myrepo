<?php

/**
 * Class to add/retrieve records from mailing_list_addresses table
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLP_MailingList
{
	/**
	 * OLP Factory instance
	 *
	 * @var OLP_Factory
	 */
	protected $factory;
	
	/**
	 * Mailing lists
	 *
	 * @var array
	 */
	private $lists = array();
	
	/**
	 * Emails for the lists
	 *
	 * @var array
	 */
	private $emails = array();

	/**
	 * Constructor
	 *
	 * @param OLP_Factory $factory
	 */
	public function __construct(OLP_Factory $factory)
	{
		$this->application_id = $application_id;
		
		$this->factory = $factory;
	}
	
	/**
	 * Returns a mailing list model
	 *
	 * @return DB_Models_Decorator_ReferencedWritableModel_1
	 */
	protected function getModel()
	{
		return $this->factory->getReferencedModel('MailingListAddress');
	}
	
	/**
	 * Loads the mailing lists
	 *
	 * @return array
	 */
	public function loadLists()
	{
		$model = $this->factory->getModel('References_MailingList')->loadAllBy();

		foreach ($model as $list)
		{
			$this->lists[] = strtolower($list->name);
		}
		
		return $this->getLists();
	}
	
	/**
	 * Returns all mailing lists
	 *
	 * @return array
	 */
	public function getLists()
	{
		return $this->lists;
	}
	
	/**
	 * Checks if a list exists
	 *
	 * @param string $name Name of the list to check for
	 * @return bool TRUE if list exists
	 */
	public function listExists($name)
	{
		return in_array(strtolower($name), $this->lists);
	}
	
	/**
	 * Adds a new list
	 *
	 * @param string $name Name of the list to add
	 * @return void
	 */
	public function addList($name)
	{
		$name = strtolower($name);
		
		if (!$this->listExists($name))
		{
			$mailing_list = $this->factory->getModel('References_MailingList');
			$mailing_list->setInsertMode(DB_Models_WritableModel_1::INSERT_IGNORE);
			$mailing_list->name = $name;
			$application_flag->save();
			
			$this->lists[] = $name;
		}
	}
	
	public function loadEmails($name)
	{
		$name = strtolower($name);
		
		if ($this->listExists($name))
		{
			if (empty($this->emails[$name]))
			{
				$models = $this->getModel()->loadAllBy(array('name' => $name));

				$emails = array();
				foreach ($models as $row)
				{
					$emails[] = trim(strtolower($row->email_address));
				}
				
				$this->emails[$name] = $emails;
			}
		}
		else
		{
			throw new InvalidArgumentException("List {$name} does not exist!");
		}
		
		return $this->emails[$name];
	}

	/**
	 * Truncates a list
	 *
	 * @param string $name List name
	 * @return void
	 */
	protected function truncateList($name)
	{
		$name = strtolower($name);
		
		if ($this->listExists($name))
		{
			$models = $this->getModel()->loadAllBy(array('name' => $name));
			foreach ($models as $model)
			{
				/* @var $model OLP_Models_MailingListAddress */
				$model->setDeleted(TRUE);
				$model->delete();
			}
		}
		else
		{
			throw new InvalidArgumentException("List {$name} does not exist!");
		}
	}
	
	/**
	 * Add emails to a list
	 *
	 * @param string $name List name
	 * @param array $emails Emails to add
	 * @param bool $truncate TRUE to truncate the list before adding
	 * @return void
	 */
	public function addEmails($name, array $emails, $truncate = TRUE)
	{
		$name = strtolower($name);
		
		if ($this->listExists($name))
		{
			if ($truncate)
			{
				$this->truncateList($name);
			}
			
			foreach ($emails as $email)
			{
				$email = strtolower($email);
				
				$model = $this->getModel();
				$model->name = $name;
				$model->email_address = $email;
				$model->save();
				
				$this->emails[$name][] = $email;
			}
		}
		else
		{
			throw new InvalidArgumentException("List {$name} does not exist!");
		}
	}
}

?>