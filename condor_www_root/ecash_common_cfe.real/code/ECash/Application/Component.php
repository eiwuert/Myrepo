<?php

	abstract class ECash_Application_Component
	{
		/**
		 * @var int
		 */
		protected $application_id;

		/**
		 * @var int
		 */
		protected $company_id;

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @param DB_IConnection_1 $db
		 * @param int $application_id
		 * @param int $company_id
		 */
		public function __construct(DB_IConnection_1 $db, $application_id, $company_id)
		{
			$this->application_id = $application_id;
			$this->company_id = $company_id;
			$this->db = $db;
		}
	}
?>