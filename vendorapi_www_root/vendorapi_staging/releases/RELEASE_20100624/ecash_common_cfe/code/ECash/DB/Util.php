<?php

/**
 * @author Russell Lee <russell.lee@sellingsource.com>
 * @package DB
 */
class ECash_DB_Util
{
	/**
	 * Creates a new temp table with the columns in $column_spec and a primary key of $id_column and populates the
	 * table with data found in the 2 dimensional associative array $data_arr
	 * @param DB_IConnection_1 $db
	 * @param string $table_name
	 * @param array $data_arr
	 * @param array $column_spec
	 * @param string $id_column
 	 * @return void
	 */
	public static function generateTempTableFromArray(DB_IConnection_1 $db, $table_name, $data_arr, $column_spec, $id_column)
	{
		self::generateTempTable($db, $table_name, $column_spec, $id_column);
		self::insertIntoTempTableFromArray($db, $table_name, $data_arr, $column_spec);
	}
	
	/**
	 * Creates a temporary table based on the specified parameters.
	 * 
	 * @param DB_IConnection_1 $db
	 * @param string $table_name
	 * @param array $column_spec
	 * @param string $id_column
	 * @param array $index_column_list
	 */
	public static function generateTempTable(DB_IConnection_1 $db, $table_name, array $column_spec, $id_column = NULL, array $index_column_list = array())
	{
		if (empty($column_spec)) trigger_error('column_spec was empty, no columns for temporary table?', E_USER_WARNING);
		
		$column_sql_list = array();
		foreach ($column_spec as $column_name => $column_def)
		{
			$column_sql_list[] = $db->quoteObject($column_name) . ' ' . $column_def;
		}
		
		$column_sql = implode(', ', $column_sql_list);
		
		if (!is_null($id_column))
		{
			$primary_key = sprintf(", PRIMARY KEY (%s)", $db->quoteObject($id_column));
		}
		
		$index_sql = '';
		foreach ($index_column_list as $index_column)
		{
			$index_sql .= sprintf(", INDEX (%s)", $db->quoteObject($index_column));
		}

		$temp_query = sprintf('CREATE TEMPORARY TABLE %s ( %s%s%s )', $db->quoteObject($table_name), $column_sql, $primary_key, $index_sql);
		$statement = $db->prepare($temp_query);
		$statement->execute();
	}
	
	/**
	 * Inserts data into a table. This is typically called after the generateTempTable() method, since the data can be reused.
	 * 
	 * @param DB_IConnection_1 $db
	 * @param string $table_name
	 * @param array $data_array
	 * @param array $column_spec
	 */
	public static function insertIntoTempTableFromArray(DB_IConnection_1 $db, $table_name, array $data_array, array $column_spec)
	{
		if (count($data_array) > 0)
		{
			$query_map = array();
			$data_order = array();
			foreach ($data_array as $data_row)
			{
				$data_row = (array)$data_row;
				$combined_data = array_intersect_key($data_row, $column_spec);
				$query_map[] = array_values($combined_data);

				if (empty($data_order))
				{
					$data_order = array_map(array($db, 'quoteObject'), array_keys($combined_data));
				}
			}

			$row_placeholders = '(' . implode(',', array_fill(0, count($data_order), '?')) . ')';
			$values_sql = implode(',', array_fill(0, count($data_array), $row_placeholders));
			$insert_query = sprintf('INSERT INTO %s (%s) VALUES %s', $db->quoteObject($table_name), implode(',', $data_order), $values_sql);
			DB_Util_1::execPrepared($db, $insert_query,call_user_func_array('array_merge', $query_map));
		}
	}

	/**
	* @param DB_IConnection_1 $db
	* @param string $table
	* @param array $set
	* @param array $where
	* @return DB_IStatement_1
	*/
	public static function executeUpdate(DB_IConnection_1 $db, $table, Array $set, Array $where)
	{
		$sql = 'UPDATE ' . $db->quoteObject($table) . ' ';

		$args = array();

		$x = FALSE;
		$sql .= 'SET ';
		foreach ($set as $field => $value)
		{
			if ($x)
			{
				$sql .= ', ';
			}

			$sql .= $db->quoteObject($field) . ' = ?';
			$args[] = $value;

			$x = TRUE;
		}

		$x = FALSE;
		$sql .= 'WHERE ';
		foreach ($where as $field => $value)
		{
			if ($x)
			{
				$sql .= ' AND ';
			}

			$sql .= $db->quoteObject($field) . ' = ?';
			$args[] = $value;

			$x = TRUE;
		}

		$statement = $db->prepare($sql);
		$statement->execute($args);
		return $statement;
	}

	/**
	 * Similar to PDO::query(). This, however, expects the query to have
	 * prepare tokens (such as ? or :<name>), with the data for said tokens
	 * provided as the second argument.  This is largely because a very common
	 * operation is to simply use prepare() for it's security benefits, like
	 * automatic string encapsulation.
	 *
	 * @param DB_IConnection_1 $db
	 * @param string $query
	 * @param array $prepare_args
	 * @return DB_IStatement_1
	 * @throws PDOException If there was an error executing the query.
	 * @todo Throw something that is not a PDOException
	 */
	public static function queryPrepared(DB_IConnection_1 $db, $query, array $prepare_args = NULL)
	{
		$new_prepare_args = array();
		foreach ($prepare_args as $key=> $value)
		{
			if (is_array($value))
			{
				/**
				 * if the $value is an array, the key is an operator that
				 * can be disregarded
				 */
				list($k, $v) = each($value);
				$new_prepare_args[$key] = $v;
			}
			else
			{
				$new_prepare_args[$key] = $value;
			}
		}
		$statement = $db->prepare($query);

		if ($statement === FALSE)
		{
			throw new PDOException('Unable to prepare query');
		}

		$statement->execute($new_prepare_args);

		return $statement;
	}

	/**
	 * Returns a string containing statement prepare-friendly
	 * where clause. not intended to be use as part of another where clause
	 * this is standalone
	 *
	 * ex:
	 * <code>
	 * echo self::buildWhere(array('row_id' => 3, 'row_name' => 'foo'));
	 * echo self::buildWhere(array());
	 * </code>
	 *
	 * @param array $where_args
	 * @param bool $named_params
	 * @return string
	 */
	public static function buildWhereClause($where_args, $named_params = TRUE, DB_IConnection_1 $db = NULL)
	{
		if (count($where_args) == 0)
		{
			return '';
		}

		$where = array();
		foreach ($where_args as $key => $value)
		{
			if (is_array($value))
			{
				list($operator, $value) = each($value);
			}
			elseif (is_null($value))
			{
				$operator = 'IS';
			}
			else
			{
				$operator = '=';
			}

			$col = ($db ? $db->quoteObject($key) : $key);

			if ($named_params)
			{
				$where[] = "{$col} {$operator} :{$key}";
			}
			else
			{
				$where[] = "{$col} {$operator} ?";
			}
		}

		return ' where ' . implode(' and ', $where);
	}

	/**
	 * Returns a ? string for the number of values in the array for the IN portion
	 *	of an SQL statement
	 *
	 * @param array $in_variable
	 * @return string
	 */
	public static function prepareArrayString(array $in_variable)
	{
		return (empty($in_variable)) ? '' : '?' . str_repeat(',?', count($in_variable)-1);
	}

	/**
	 * This helper function will retry a query several times if it returns a deadlock.
	 *
	 * If it detects that it is in a transaction it will not retry.
	 *
	 * @param DB_IConnection_1 $db
	 * @param string $query
	 * @param integer $retry
	 * @param integer $wait micro seconds (usleep)
	 * @return mixed
	 */
	public static function queryWithDeadlockRetry(DB_IConnection_1 $db, $query, $retry=3, $wait=300)
	{
		for ($i = 0; $i < $retry; $i++)
		{
			try
			{
				return $db->query($query);
			}
			catch (Exception $e)
			{
				if ($i + 1 == $retry
					|| strpos($e->getMessage(), 'Deadlock found when trying to get lock; try restarting transaction in query') === FALSE
					|| $db->getInTransaction()
					)
				{
					throw $e;
				}

				// If we didn't rethrow from the above conditions then sleep before we loop again.
				usleep($wait);
			}
		}

		// Sanity check? Insane catch?
		throw $e;
	}
}

?>
