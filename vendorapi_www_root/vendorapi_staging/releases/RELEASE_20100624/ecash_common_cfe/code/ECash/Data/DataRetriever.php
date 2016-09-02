<?php

	abstract class ECash_Data_DataRetriever
	{
		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @param DB_IConnection $db
		 */
		public final function __construct(DB_IConnection_1 $db)
		{
			$this->db = $db;
		}
	}

?>