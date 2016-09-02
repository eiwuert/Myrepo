<?php
/**
 * A WHERE part container using "AND"s.
 *
 * This can represent something like "WHERE x=1 AND y=2" with an arbitrary depth.
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package DB
 */
class OLP_DB_And extends OLP_DB_WhereGlue
{
	/**
	 * Create an And container. Accepts variable amounts of arguments!
	 *
	 * @param string|object $where_part Either a string part of sql such as 
	 * 'x = 1' or an object with a toSql() method.
	 * @return void
	 */
	public function __construct($where_part = NULL)
	{
		$args = func_get_args();
		parent::__construct(self::AND_GLUE, $args);
	}
	
}

?>