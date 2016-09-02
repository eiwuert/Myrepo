<?php

	/**
	 * The winner object for enterprise companies
	 * This includes additional information, such as whether the application
	 * was determined to be a react.
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class OLPBlackbox_Enterprise_Winner extends OLPBlackbox_Winner
	{
		/**
		 * @var OLPBlackbox_Enterprise_CustomerHistory
		 */
		protected $history;

		/**
		 * OLPBlackbox_Enterprise_Winner constructor.
		 *
		 * @param OLPBlackbox_ITarget $target the target who won
		 * @param OLPBlackbox_Enterprise_CustomerHistory $history the customer history of the target
		 */
		public function __construct(OLPBlackbox_ITarget $target, OLPBlackbox_Enterprise_CustomerHistory $history)
		{
			$this->target = $target;
			$this->history = $history;
		}

		/**
		 * Gets the customer history from the previous customer checks
		 *
		 * @return OLPBlackbox_Enterprise_CustomerHistory
		 */
		public function getCustomerHistory()
		{
			return $this->history;
		}

		/**
		 * Returns whether the app was determined to be react
		 *
		 * @return bool
		 */
		public function getIsReact()
		{
			// OLPBlackbox_Enterprise_Generic_Target::pickTarget sets this...
			$state = $this->getStateData();
			return (isset($state->is_react) && $state->is_react);
		}

		/**
		 * Returns the companies with DNL flags
		 *
		 * @return array
		 */
		public function getDoNotLoan()
		{
			return $this->history->getDoNotLoan();
		}

		/**
		 * Returns the companies with DNL overrides
		 *
		 * @return array
		 */
		public function getDoNotLoanOverride()
		{
			return $this->history->getDoNotLoanOverride();
		}
	}

?>