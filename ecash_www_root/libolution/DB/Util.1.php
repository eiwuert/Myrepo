<?php
/**
 * @package DB
 */

/**
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class DB_Util_1
{
	/**
	 * Creates a statement based on the query or using the query and arguments.
	 *
	 * The statement will be executed prior to being returned.
	 *
	 * @param DB_IConnection_1 $db
	 * @param string $query
	 * @param Array $prepare_args - If not sent then a query will be constructed.
	 * @return DB_IStatement_1
	 * @throws PDOException If there was an error executing the query.
	 * @todo Throw something that is not a PDOException
	 */
	private static function getStatement(DB_IConnection_1 $db, $query, array $prepare_args = NULL)
	{
		if ($prepare_args !== NULL)
		{
			$statement = self::queryPrepared($db, $query, $prepare_args);
		}
		elseif (($statement = $db->query($query)) === FALSE)
		{
			throw new PDOException('Unable to execute query');
		}

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
		$statement = $db->prepare($query);

		if ($statement === FALSE)
		{
			throw new PDOException('Unable to prepare query');
		}

		$statement->execute($prepare_args);

		return $statement;
	}

	/**
	 * Prepares and executes a query, same as queryPrepared, but returns the
	 * row count, same as PDO::exec()
	 *
	 * @param DB_IConnection_1 $db
	 * @param string $query
	 * @param array $prepare_args
	 * @return int
	 */
	public static function execPrepared(DB_IConnection_1 $db, $query, array $prepare_args)
	{
		$statement = self::queryPrepared($db, $query, $prepare_args);
		return $statement->rowCount();
	}

	/**
	 * Performs the given query and returns the value in the specified
	 * column of the first row. Intended to be used with queries of the
	 * "select count(*) from ..." nature.
	 *
	 * @param DB_IConnection_1 $db
	 * @param string $query
	 * @param array $prepare_args Arguments to pass to the prepared statement.
	 * @param int $column_number Column number to fetch.
	 * @todo Throw something that is not a PDOException
	 * @return mixed
	 */
	public static function querySingleValue(DB_IConnection_1 $db, $query, array $prepare_args = NULL, $column_number = 0)
	{
		$statement = self::getStatement($db, $query, $prepare_args);

		$column = $statement->fetch(DB_IStatement_1::FETCH_ROW);

		return $column[$column_number];
	}


	/**
	 * Performs the given query and returns the specified column of each
	 * row in a 0-indexed array
	 *
	 * @param DB_IConnection_1 $db
	 * @param string $query
	 * @param array $prepare_args Arguments to pass to the prepared statement.
	 * @param int $column_number Column number to fetch
	 * @return array
	 */
	public static function querySingleColumn(DB_IConnection_1 $db, $query, array $prepare_args = NULL, $column_number = 0)
	{
		$statement = self::getStatement($db, $query, $prepare_args);

		$columns = array();
		foreach ($statement->fetchAll(DB_IStatement_1::FETCH_ROW) as $row)
		{
			$columns[] = $row[$column_number];
		}

		return $columns;
	}

	/**
	 * Performs the query specified and returns the first row in the format
	 * specified.
	 *
	 * The default format is PDO::ASSOC, which will return an associative
	 * array.  Other common modes are PDO::FETCH_OBJ (stdClass), and
	 * PDO::FETCH_NUM (indexed array.)
	 *
	 * See the PDO documentation (http://php.net/pdo) for other modes.
	 *
	 * @param DB_IConnection_1 $db
	 * @param string $query
	 * @param string $prepare_args
	 * @param int $fetch_mode
	 * @return mixed
	 */
	public static function querySingleRow(DB_IConnection_1 $db, $query, array $prepare_args = NULL, $fetch_mode = NULL)
	{
		$statement = self::getStatement($db, $query, $prepare_args);

		if ($fetch_mode !== NULL)
		{
			$row = $statement->fetch($fetch_mode);
		}
		else
		{
			$row = $statement->fetch(DB_IStatement_1::FETCH_ASSOC);
		}

		return $row;
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
		if (count($where_args) > 0)
		{
			if ($named_params)
			{
				$where = array();
				foreach ($where_args as $key => $value)
				{
					$col = ($db ? $db->quoteObject($key) : $key);
					$where[] = "$col = :$key";
				}
				return ' where ' . implode(' and ', $where);
			}
			else
			{
				$fields = array_keys($where_args);
				if ($db) array_map(array($db, 'quoteObject'), $fields);
				return ' where '.implode(' = ? and ', $fields).' = ?';
			}
		}
		return '';
	}
}

?>
