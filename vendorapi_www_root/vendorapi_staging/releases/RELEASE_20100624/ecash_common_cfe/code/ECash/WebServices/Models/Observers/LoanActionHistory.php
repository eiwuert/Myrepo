<?php

/**
 * Observer class on the loan action history model for inserting loan actions
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 */
class ECash_WebServices_Models_Observers_LoanActionHistory
{
	/**
	 * @var Delegate_1
	 */
	protected $delegate;

	/**
	 * Application service client for communication with the application service
	 *
	 * @see function getLoanActionClient
	 * @var ECash_WebService_LoanActionClient
	 */
	protected $la_client;

	/**
	 * Constructor for the ApplicationModelObserver object
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->delegate = Delegate_1::fromMethod($this, 'onEvent');
	}

	/**
	 * Attach to an observable model
	 *
	 * @param IObservable_1 $model
	 * @return void
	 */
	public function attach(IObservable_1 $model)
	{
		$model->attachObserver($this->delegate);
	}

	/**
	 * Fired when a change occurs on the model we're watching
	 *
	 * @param stdClass $event
	 * @return void
	 */
	public function onEvent($event)
	{

		if ($event->type == DB_Models_ObservableWritableModel_1::EVENT_BEFORE_INSERT)
		{
			$this->insertLoanAction($event->model);

		}
	}

	/**
	 * Get a new loan action service client
	 *
	 * @return ECash_WebService_LoanActionClient
	 */
	protected function getLoanActionClient()
	{
		if (!isset($this->la_client))
		{
			$this->la_client = ECash::getFactory()->getLoanActionClient();
		}

		return $this->la_client;
	}

	/**
	 * Insert Loan action into the app service
	 *
	 * @param ECash_Models_LoanActionHistory $app
	 * @return void
	 */
	protected function insertLoanAction(ECash_Models_LoanActionHistory $la)
	{
		$factory = ECash::getFactory();

		$LoanAction = $factory->getModel('LoanActions');
		$LoanAction->loadBy(array('loan_action_id' => $la->loan_action_id));

		$LoanActionSection = $factory->getModel('LoanActionSection');
		$LoanActionSection->loadBy(array('loan_action_section_id' => $la->loan_action_section_id));

		$ref_list = $factory->getReferenceList('ApplicationStatusFlat');
		$status = $ref_list->toName($la->application_status_id);

		$args = array();
		$args['application_id'] = $la->application_id;
		$args['loan_action'] = $LoanAction->name_short;
		$args['loan_action_section'] = $LoanActionSection->name_short;
		$args['application_status'] = $status;
		$args['agent_id'] = $la->agent_id;

		$la_client = $this->getLoanActionClient();
		$lah_id = $la_client->insert($args);

		if ($lah_id) {
			$la->loan_action_history_id = $lah_id;
		}
	}
}

?>
