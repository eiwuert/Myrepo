<?php

	class ECash_Data_SecondTier extends ECash_Data_DataRetriever
	{

		/**
		 * Takes a list of application_id's from the application service and calls
		 * a stored procedure in the MSSQL Database.
		 *
		 * @param <array> $source_list - Array of application_ids
		 * @return <array> $applications - Associative array of data, indexed by application_id
		 */
		public function getApplicationBatchData($source_list)
		{
			if(empty($source_list)) return array();
			$insert_list = implode( ',',$source_list);

			/**
			 * I would like a more elegant way to grab the database connection
			 *
			 * @var DB_MSSQLAdapter_1 $db
			 */
			$db = ECash::getAppSvcDB();

			/**
			 * How this works: With a stored procedure, there is no good way to pass in
			 * a variable list of arguments such as a list of application_ids that will go
			 * inside an IN() clause, so we have to do some hackery.
			 *
			 * First we're creating a temporary table using a user-defined table type
			 * that the DBA's have created.  Next we insert all of our applications into that
			 * table, which will then be called within a subquery inside the stored procedure.
			 * Finally, we call the stored procedure and pass it the table name as an argument
			 * and it will do all the dirty work inside the stored procedure.
			 */
			 $query = 'CALL sp_stb_fetch_information_by_application_id ("'.$insert_list.'");';

			$result = $db->query($query);
			while ($app = $result->fetch(DB_IStatement_1::FETCH_ASSOC))
			{
				$application_info[$app['application_id']] = $app;
			}

			return $application_info;
		}
	}

?>
