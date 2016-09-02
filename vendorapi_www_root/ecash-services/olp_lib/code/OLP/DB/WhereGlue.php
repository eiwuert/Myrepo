<?php

/**
 * Base class for AND and OR where containers.
 *
 * Used to splice together little bits of sql for where clauses. Can contain
 * other objects like itself.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package DB
 */
class OLP_DB_WhereGlue extends OLP_DB_AbstractWherePart implements Countable
{
	/**
	 * Referenced to implode where parts together into a string.
	 * 
	 * @var string
	 */
	const AND_GLUE = ' AND ';
	
	/**
	 * Referenced to implode where parts together into a string.
	 * 
	 * @var string
	 */
	const OR_GLUE = ' OR ';
	
	/**
	 * The actual string this class will use to implode where parts together.
	 *
	 * @var string
	 */
	protected $glue;
	
	/**
	 * The bits of where SQL this class will assemble together.
	 *
	 * @var array
	 */
	protected $parts = array();
	
	/**
	 * Make a new WhereGlue part.
	 *
	 * @param string $glue OLP_DB_WhereGlue::AND_GLUE or OLP_DB_WhereGlue::OR_GLUE
	 * @param array|Traversable $where_parts Collection of SQL bits or, more
	 * likely, OLP_DB_IWherePart objects.
	 */
	public function __construct($glue = self::AND_GLUE, $where_parts = array())
	{
		$this->glue = $glue;
		foreach ($where_parts as $part)
		{
			$this->add($part);
		}
	}
	
	/**
	 * Add a OLP_DB_IWherePart object or SQL string. Chainable.
	 *
	 * @param string|IWherePart $part
	 * @return OLP_DB_WhereGlue $this (For chaining.)
	 */
	public function add($part)
	{
		if (is_string($part) || $part instanceof OLP_DB_IWherePart)
		{
			$this->parts[] = $part;
		}
		else
		{
			throw new OLP_DB_InvalidArgumentException(
				'part of ' . __CLASS__ . ' must be string or where fragment, not: ' 
				. var_export($part, TRUE)
			);
		}
		
		return $this;
	}
	
	/**
	 * Returns a version of this container which is suitable for concating as
	 * sql.
	 *
	 * @param string $table_fallback The name of a table to use by this container's parts.
	 * @param mixed $escape_callback A callback (string, array(obj,methodName) or reflection
	 * object which can have invokeArgs() called. This parameter is used to 
	 * @return string SQL suitable to be a piece of a where.
	 */
	public function toSql($table_fallback = NULL, $escape_callback = NULL)
	{
		$table = ($this->table ? $this->table : $table_fallback);
		$callback = is_null($this->escape_callback) ? $escape_callback : $this->escape_callback;
		
		$parts = array();
		foreach ($this->parts as $part)
		{
			$parts[] = $this->partAsSql($part, $table, $callback);
		}
		return '(' . implode($this->glue, $parts) . ')';
	}
	
	/**
	 * Return a "part" of this and formatted for inclusion in a SQL statement.
	 *
	 * @param string|object $part Either a string containing sql like 'x = 1' or
	 * an object with a toSQL method.
	 * @param string $table The name of a table to pass to object's toSql() method.
	 * @param mixed $escape_callback A callback (string, array(obj,methodName) or reflection
	 * object which can have invokeArgs() called. This parameter is used to 
	 * @return string
	 */
	protected function partAsSql($part, $table = NULL, $escape_callback = NULL)
	{	
		if ($part instanceof OLP_DB_IWherePart)
		{
			return $part->toSql($table, $escape_callback);
		}
		else 
		{ 
			return strval($part);
		}
	}
	
	/**
	 * Return the amount of parts in this where glue object.
	 * @return int
	 */
	public function count()
	{
		return count($this->parts);
	}
}

?>
