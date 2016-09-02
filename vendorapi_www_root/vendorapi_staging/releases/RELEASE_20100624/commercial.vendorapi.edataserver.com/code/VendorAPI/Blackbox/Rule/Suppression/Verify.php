<?php

/**
 * Suppression list to create loan actions for matches to the list
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_Suppression_Verify extends VendorAPI_Blackbox_Rule
{
	/**
	 * @var VendorAPI_SuppressionList_ILoader
	 */
	protected $suppression_list;

	protected $list_name;

	/**
	 * @var VendorAPI_SuppressionList_Wrapper
	 */
	protected $list;
	
	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param VendorAPI_SuppressionList_ILoader $suppression_list
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, VendorAPI_SuppressionList_ILoader $suppression_list, $list_name)
	{
		$this->suppression_list = $suppression_list;
		$this->list_name = $list_name;
		parent::__construct($log);
	}

	/**
	 * @return string
	 */
	public function getEventName()
	{
		return 'LIST_VERIFY_'.strtoupper($this->list_name);
	}

	/**
	 * Run this rule.  Test if the field is in the verify list, add
	 * loan action if it is.  Always return TRUE
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return boolean
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->list = $this->suppression_list->getByName($this->list_name);

		if (isset($this->list)
			&& isset($data->{$this->list->getField()})
			&& !empty($data->{$this->list->getField()})
			&& $this->list->match($data->{$this->list->getField()}))
		{

			/**
			 * #50710 - Try to locate an appropriate loan action based
			 * on the loan_action column in the suppression_lists table.  If
			 * we do find one, we pass the name_short as the event_name,
			 * otherwise we make something up, which was the previous method.
			 */
			if($this->list->getLoanAction() instanceof ECash_Models_LoanActions)
			{
				/**
				 * !!!!!!
				 * !!!!!! This code is not getting hit right now.
				 * !!!!!! Loan action will always be a string. I am only here to make things faster, not to change functionality.
				 * !!!!!! If this is supposed to be allowing a configurable loan action then it really just needs to check for
				 * !!!!!! empty() on $this->list->getLoanAction(). That is of course provided that loan actions are really
				 * !!!!!! being dynamically created.
				 * !!!!!!
				 */
				$event_name = $this->list->getLoanAction();
			}
			else
			{
				$event_name = sprintf('LIST_VERIFY_%s_%u', strtoupper($this->list->getField()), $this->list->getListId());
			}

			if (!isset($state_data->loan_actions)
				|| !($state_data->loan_actions instanceof VendorAPI_Blackbox_LoanActions))
			{
				$state_data->loan_actions = new VendorAPI_Blackbox_LoanActions();
			}
			$state_data->loan_actions->addLoanAction($event_name);			
		}

		return TRUE;
	}
	
	/**
	 * Return a comment?
	 * @return string
	 */
	protected function failureComment()
	{
		return $this->list_name;
	}

}

?>
