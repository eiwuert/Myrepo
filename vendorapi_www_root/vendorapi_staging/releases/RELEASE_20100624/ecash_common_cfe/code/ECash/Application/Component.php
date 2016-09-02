<?php

	abstract class ECash_Application_Component
	{
		/**
		 * @var ECash_Application
		 */
		protected $application;

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @param DB_IConnection_1 $db
		 * @TODO remove these two and replace with ECash_Application
		 * @param int $application_id
		 * @param int $company_id
		 */
		public function __construct(DB_IConnection_1 $db, ECash_Application $application)
		{
			$this->application = $application;
			$this->db = $db;
		}
	}
?>