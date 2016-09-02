<?php
if(!defined('ECASH_COMMON_DIR')) define('ECASH_COMMON_DIR', '/virtualhosts/ecash_common/');
if(!defined('ECASH_COMMON_CODE_DIR')) define('ECASH_COMMON_CODE_DIR', ECASH_COMMON_DIR . 'code/');

require_once ECASH_COMMON_CODE_DIR . 'ECash/API.php';
require_once ECASH_COMMON_CODE_DIR . 'ECash/Models/Queue.php';
require_once ECASH_COMMON_CODE_DIR . 'ECash/Models/CurrentQueueStatus.php';
require_once ECASH_COMMON_CODE_DIR . 'ECash/Config.php';

class AALM_API extends ECash_API
{
	/**
	 * ECash AsynchEngine result object
	 *
	 * @var ECash_CFE_AsynchResult
	 */
	protected $ar;
	protected $is_update;
	const ENTERPRISE = 'AALM';

	public function __construct($mode = 'RC')
	{
		parent::__construct();
		$this->addRequiredIndex(array(
			//self::INDEX_BUREAU_INQUIRY, //this should be required, yes/no?
			self::INDEX_PERSONAL_REFERENCE,
			//self::INDEX_STATUS_HISTORY, // not required, i.e., ecash_apps
			//self::INDEX_CUSTOMER, // this is not strictly neccessary (may already exist)
			//self::INDEX_CARD // this is optional too
			));
		$this->loadConfig($mode, self::ENTERPRISE);
	}
	
    public function updateApplication($model, array $data)
    {
		$this->is_update = TRUE;
   
        // if this key exists inside the parent insertApplication,
        // it will attempt to load ECash_Models_AsynchResult
        $this->ar = $data[self::INDEX_ASYNCH_RESULT];
        unset($data[self::INDEX_ASYNCH_RESULT]);
        parent::updateApplication($model, $data);
        if ($this->application->isAltered())
        {
            $this->application->save();
        }
    }
	
	public function insertApplication(array $data)
	{
		if (!isset($data[self::INDEX_ASYNCH_RESULT])
			|| !$data[self::INDEX_ASYNCH_RESULT] instanceof ECash_CFE_AsynchResult)
		{
			throw new Exception('An AsynchResult object was expected but not found.');
		}

		// if this key exists inside the parent insertApplication,
		// it will attempt to load ECash_Models_AsynchResult
		$this->ar = $data[self::INDEX_ASYNCH_RESULT];
		unset($data[self::INDEX_ASYNCH_RESULT]);
		$db = ECash::getMasterDb();
		$application_id = parent::insertApplication($data);

		// a lot of actions will depend on this values existing like this
		ECash::setCompany($this->getCompany($this->application->company_id));
		ECash::setAgent($this->getAgent($this->application->modifying_agent_id));
		ECash::setApplication($this->application);

		// runs the actions for the rules this app
		// fulfills, should also put it in a queue
		$cfe = new ECash_CFE_AsynchEngine($db, $this->application->company_id);
		$cfe->endExecution(
			$this->application,
			$this->ar
		);

		// modifications can be made during CFE execution
		if ($this->application->isAltered())
		{
			$this->application->save();
		}

		return $application_id;
	}

	protected function getModelClass($index)
	{
		switch($index)
		{
			case self::INDEX_APPLICATION:
				include_once self::ENTERPRISE . "/Models/{$index}.php";
				return self::ENTERPRISE . "_Models_{$index}";
				break;

			default:
				return parent::getModelClass($index);
				break;
		}
	}

	protected function saveApp()
	{
		if ($this->is_update !== TRUE)
		{
			// we have to setup the loan type and ruleset before the application is inserted
			// these are based off the asynch result... if there isn't a result, we have a problem.
			$ruleset = $this->getRuleset($this->ar->getRulesetId());

			if ($ruleset === FALSE)
			{
				throw new Exception('Missing loan_type for cfe_rule_set, '.$this->ar->getRulesetId());
			}

			$this->inserts[self::INDEX_APPLICATION]->loan_type_id = $ruleset['loan_type_id'];
			$this->inserts[self::INDEX_APPLICATION]->cfe_rule_set_id = $this->ar->getRulesetId();
			$this->inserts[self::INDEX_APPLICATION]->rule_set_id = $ruleset['rule_set_id'];
		}

		parent::saveApp();
	}

	private function insertIntoQueue($queue_name_short)
	{
		//new style (stylin')
		$qm = ECash::getFactory()->getQueueManager();
		$q = $qm->getQueue($queue_name_short);
		$my_new_queue_item = new ECash_Queues_BasicQueueItem($this->application->application_id);
		$q->insert($my_new_queue_item);
	}
}

?>
