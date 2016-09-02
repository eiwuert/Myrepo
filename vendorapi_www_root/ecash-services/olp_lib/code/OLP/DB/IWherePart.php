<?php

/**
 * Indicates this object can be used as a part of a sql where clause.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
interface OLP_DB_IWherePart
{
	/**
	 * Method which produces a piece of WHERE sql (without the WHERE bit).
	 *
	 * @param string $fallback_table The table name to use if the object doesn't
	 * have a more specific one set already. Will be passed on to subsequent
	 * WhereParts.
	 * @param mixed $escape_callback A callback (string, array(obj,methodName) or reflection
	 * object which can have invokeArgs() called. This parameter is used to 
	 * escape sql string values.
	 * 
	 * @return string
	 */
	public function toSql($fallback_table = NULL, $escape_callback = NULL);
}

?>
