<?php

	class ECash_Customer_Component
	{
		/**
		 * @var ECash_Customer
		 */
		protected $customer;
		
		/**
		 * @var int
		 */
		protected $company_id;
		
		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		public function __construct(DB_IConnection_1 $db, ECash_Customer $customer, $company_id)
		{
			$this->customer = $customer;
			$this->company_id = $company_id;
			$this->db = $db;
		}
	}
?>