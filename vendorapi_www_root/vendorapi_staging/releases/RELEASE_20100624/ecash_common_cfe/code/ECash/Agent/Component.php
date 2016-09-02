<?php

	/**
	 * Agent component base class
	 * seriously
	 *
	 */
	class ECash_Agent_Component
	{
		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @var int
		 */
		protected $company_id;

		/**
		 * @var int
		 */
		protected $agent_id;

		/**
		 * @param DB_IConnection_1 $db
		 * @param int $agent_id
		 * @param int $company_id
		 */
		public function __construct(DB_IConnection_1 $db, $agent_id, $company_id)
		{
			$this->db = $db;
			$this->agent_id = $agent_id;
			$this->company_id = $company_id;
		}
	}
?>