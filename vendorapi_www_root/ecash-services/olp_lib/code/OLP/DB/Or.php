<?php

/**
 * A WHERE part container using "OR"s.
 *
 * This can represent something like "WHERE x=1 OR y=2" with an arbitrary depth.
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package DB
 */
class OLP_DB_Or extends OLP_DB_WhereGlue
{
	/**
	 * Create an "Or" container. Accepts variable amounts of arguments!
	 *
	 * @param string|object $where_part Either a string part of sql such as 
	 * 'x = 1' or an object with a toSql() method.
	 * @return void
	 */
	public function __construct($where_part = NULL)
	{
		$args = func_get_args();
		parent::__construct(self::OR_GLUE, $args);
	}
}

?>