<?php

class FastTruncate implements PHPUnit_Extensions_Database_Operation_IDatabaseOperation
{
	public function execute(PHPUnit_Extensions_Database_DB_IDatabaseConnection $connection, PHPUnit_Extensions_Database_DataSet_IDataSet $data_set)
	{
		foreach ($data_set as $table)
		{
			$table_name = $table->getTableMetaData()->getTableName();
			$table_name = $connection->quoteSchemaObject($table_name);

			try
			{
				$db = $connection->getConnection();

				$query = "SELECT * FROM {$table_name} LIMIT 1";
				$st = $db->query($query);

				if (($row = $st->fetch()) !== FALSE)
				{
					$query = "{$connection->getTruncateCommand()} $table_name";
					$db->exec($query);
				}
			}
			catch (PDOException $e)
			{
				throw new PHPUnit_Extensions_Database_Operation_Exception('TRUNCATE', $query, array(), $table, $e->getMessage());
			}
		}
	}
}

?>
