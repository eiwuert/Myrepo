<?php

	class Analysis_LoanException extends Exception
	{

		protected $loan;

		function __construct($message, $loan)
		{
			$this->loan = $loan;
			parent::__construct($message);

			return;
		}

		public function getLoan()
		{
			return $this->loan;
		}

	}

?>