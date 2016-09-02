<?php

	class Analysis_CustomerException extends Exception
	{

		protected $cust;

		function __construct($message, $customer)
		{
			$this->cust = $customer;
			parent::__construct($message);

			return;
		}

		public function getCustomer()
		{
			return $this->cust;
		}

	}

?>