<?php

/**
 * Class to represent a SQL Where clause.
 * 
 * @see OLP_DB_And
 * @see OLP_DB_Or
 * @package DB
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLP_DB_WhereCond extends OLP_DB_AbstractWherePart
{
	const LIKE = 'LIKE';
	const NOT_LIKE = 'NOT LIKE';
	const GREATER_THAN = '>';
	const LESS_THAN = '<';
	const GREATER_THAN_EQUALS = '>=';
	const EQUALS = '=';
	const LESS_THAN_EQUALS = '>=';
	const REGEXP = 'REGEXP';
	const IS_NOT = 'IS NOT';
	
	/**
	 * The column in the database this where condition will act upon.
	 * 
	 * Eventually this might be a string OR an object representing a field, but
	 * for now it's just a string.
	 *
	 * In the expression "minimum_age > 100" the field is "minimum_age"
	 * 
	 * @var string
	 */
	protected $field;
	
	/**
	 * The operator (or symbol for the operator) which will be used to compare the
	 * field to the value.
	 * 
	 * In the expression "minimum_age > 100" the operator is ">"
	 *
	 * @var string
	 */
	protected $operator;
	
	/**
	 * The value which triggers a successful comparison on the field.
	 *
	 * In the expression "minimum_age > 100" the value is "100"
	 * @var mixed Usually a float, int or string.
	 */
	protected $value;
	
	/**
	 * Make a simple where clause.
	 * @param string $field The column/field to compare.
	 * @param string $operator The operator to use (such as '>' or '='). See 
	 * this class' constants.
	 * @param mixed $value The right side operand, a string, int or float usually.
	 * @param string $table The table to prefix columns with.
	 */
	public function __construct($field, $operator = NULL, $value = NULL, $table = NULL)
	{
		if (!$field || !is_string($field))
		{
			// eventually, field might be a field object from a model or something.
			throw new OLP_DB_InvalidArgumentException(
				'cannot construct where object with no fieldname'
			);
		}
		$this->setField($field);
		$this->setOperator($operator);
		$this->setValue($value);
		$this->setTable($table);
	}

	/**
	 * Main driver function for returning this where object as a FRAGMENT.
	 * 
	 * I.E. This function will NOT return the WHERE part of the sql. Use either
	 * the built-in __toString() for that or the toWhere() method.
	 *
	 * @param string $table_fallback Name of a table to use if this object does
	 * not have it's own table stored. 
	 * @param mixed $escape_callback A callback (string, array(obj,methodName) or reflection
	 * object which can have invokeArgs() called. This parameter is used to 
	 * escape sql string values.
	 * 
	 * @return string SQL
	 */
	public function toSql($table_fallback = NULL, $escape_callback = NULL)
	{
		// This assumes $this->table is a string, but it could be a model ref eventually
		$table_name = ($this->table ? $this->table : $table_fallback);
		$table_name = ($table_name ? $table_name . '.' : '');
		
		$sql = "$table_name{$this->field} ";
		
		if ($this->operator) 
		{
			$sql .= $this->operator . ' ' . $this->getValueForQuery($escape_callback);
		}
		
		return $sql;
	}
	
	/**
	 * Returns the value for this WHERE, formatted for SQL.
	 * 
	 * Note: Eventually, obviously, this might need to be ? when the sql is
	 * returned for PDO uses and stuff.
	 *
	 * @param mixed $escape_callback A callback (string, array(obj,methodName) or reflection
	 * object which can have invokeArgs() called. This parameter is used to 
	 * escape sql string values.
	 * 
	 * @return string|int|float
	 */
	protected function getValueForQuery($escape_callback = NULL)
	{
		if (is_null($this->getValue()))
		{
			return 'NULL';
		}
		
		if (is_numeric($this->getValue()))
		{
			if (stripos($this->getValue(), '.') !== FALSE)
			{
				return strval(floatval($this->getValue()));
			} 
			else 
			{
				return intval($this->getValue());
			}
		}
		
		$quoted = $this->escape($this->getValue(), $escape_callback);
		if (substr($quoted, 0, 1) != "'")
		{
			$quoted = "'$quoted'";
		}
		return $quoted;
	}
	
	/**
	 * Escape a SQL string value 
	 *
	 * @param string $value The value to escape.
	 * @param array|string|ReflectionFunction $escape_callback
	 * @return string The escaped string.
	 */
	protected function escape($value, $escape_callback = NULL)
	{
		$callback = is_null($this->escape_callback) 
			? $escape_callback
			: $this->escape_callback;
			
		$callback = is_null($callback)
			? 'addslashes'
			: $callback;
		
		$this->validateEscapeCallback($callback);
		
		if (is_string($callback))
		{
			$value = $callback($value);
		}
		elseif (is_array($callback))
		{
			$object = $callback[0];
			$method_name = $callback[1];
			
			$value = $object->$method_name($value);
		}
		else
		{
			/* @var $callback ReflectionFunction */
			$value = $callback->invokeArgs(array($value));
		}
		
		return $value;
	}
	
	// --------  SET / GET stuff for Object_1
	
	/**
	 * Return the "value" for this conditional, the right side operand.
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * Set the field/column to compare. (Left hand operand)
	 *
	 * @param string $field The field/column name.
	 * @return void
	 */
	public function setField($field)
	{
		if (!$field) return;
		
		// $field could eventually be model's field object or something
		if (!is_string($field))
		{
			throw new OLP_DB_InvalidArgumentException(
				'field  must be a string, not ' . var_export($field, TRUE)
			);
		}
		$this->field = $field;
	}
	
	/**
	 * Set the operand to use, such as '>' or '=' ... see this class' constants.
	 *
	 * @param string $operator
	 * @return void
	 */
	public function setOperator($operator)
	{
		$this->operator = $operator;
	}
	
	/**
	 * Set the right hand operand for this comparison object.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
}

?>
