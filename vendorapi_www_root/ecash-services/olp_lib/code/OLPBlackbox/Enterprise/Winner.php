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
		 * OLPBlackbox_Enterprise_Winner constructor.
		 *
		 * @param OLPBlackbox_ITarget $target the target who won
		 */
		public function __construct(OLPBlackbox_ITarget $target)
		{
			$this->target = $target;
		}

		/**
		 * Returns whether the app was determined to be react
		 *
		 * @return bool
		 */
		public function getIsReact()
		{
			// OLPBlackbox_Enterprise_Target::pickTarget sets this...
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
