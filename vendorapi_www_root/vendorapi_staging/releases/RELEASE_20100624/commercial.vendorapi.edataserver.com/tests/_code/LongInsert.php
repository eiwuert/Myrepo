<?php

class LongInsert implements PHPUnit_Extensions_Database_Operation_IDatabaseOperation
{
	public function execute(PHPUnit_Extensions_Database_DB_IDatabaseConnection $connection, PHPUnit_Extensions_Database_DataSet_IDataSet $dataSet)
	{
		$databaseDataSet = $connection->createDataSet();
		$dsIterator = $dataSet->getIterator();

		foreach ($dsIterator as $table)
		{
			$columns = $table->getTableMetaData()->getColumns();
			$columnCount = count($columns);
			$rowCount = $table->getRowCount();

			if ($columnCount > 0
				&& $rowCount > 0)
			{
				$placeHolders = '('.implode(', ', array_fill(0, $columnCount, '?')).')';

				$cols = implode(
					',',
					array_map(
						array($connection, 'quoteSchemaObject'),
						$columns
					)
				);

				$query = "INSERT INTO {$connection->quoteSchemaObject($table->getTableMetaData()->getTableName())}
					({$cols}) VALUES " . str_repeat($placeHolders.', ', $rowCount - 1).$placeHolders;

				$args = array();
				for ($i = 0; $i < $rowCount; $i++) {
					foreach ($columns as $columnName) {
						$args[] = $table->getValue($i, $columnName);
					}
				}

				$db = $connection->getConnection();

				$st = $db->prepare($query);
				$st->execute($args);
			}
		}
	}
}

?>