<?php

/**
 * A queryset object for Blackbox Target models based on rule values for targets.
 * 
 * @see filter() for an explaination on how this class works.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 * @subpackage Models
 */
class Blackbox_Models_TargetRuleQueryset extends Object_1 implements IteratorAggregate
{
	/**
	 * The target model we will use to produce more target models. :D
	 *
	 * @var Blackbox_Models_Target
	 */
	protected $target;
	
	/**
	 * A OLP_DB_WhereGlue type which indicates which type of container to use.
	 *
	 * @var string
	 */
	protected $glue;
	
	/**
	 * The type of target to restrict this queryset to (e.g. TARGET, CAMPAIGN, etc.)
	 *
	 * @var string
	 */
	protected $target_type = NULL;
	
	/**
	 * Whether or not the targets should be active (1), inactive (0) or either (NULL).
	 *
	 * @var bool
	 */
	protected $active = NULL;
	
	/**
	 * Container to build where clause.
	 *
	 * @var OLP_DB_WhereGlue
	 */
	protected $where;
	
	/**
	 * A list of string joins.
	 *
	 * @var array
	 */
	protected $joins;
	
	/**
	 * Keeps track of the affix for joins.
	 *
	 * @var int
	 */
	protected $join_counter = 0;
	
	/**
	 * Make a queryset iterator object which can filter a target based on rule values.
	 *
	 * @param Blackbox_Models_Target $target The target model to operate on.
	 * Mostly used to produce more target_models with loadBy*() type methods.
	 * @param string $glue a OLP_DB_WhereGlue constant which indicates whether 
	 * this object should use AND or OR for the filter concatenation.
	 * @return void
	 */
	public function __construct(Blackbox_Models_Target $target, $glue = OLP_DB_WhereGlue::AND_GLUE)
	{
		$this->target = $target;
		$this->glue = $this->validateGlue($glue);
		$this->where = new OLP_DB_WhereGlue($glue);
		$this->where->setEscapeCallback(array($target, 'quote'));
	}
	
	/**
	 * Validate the glue passed in.
	 *
	 * @throws InvalidArgumentException
	 * @param string $glue OLP_DB_WhereGlue::AND_GLUE or OLP_DB_WhereGlue::OR_GLUE
	 * @return OLP_DB_WhereGlue
	 */
	protected function validateGlue($glue)
	{	
		if ($glue != OLP_DB_WhereGlue::AND_GLUE
			&& $glue != OLP_DB_WhereGlue::OR_GLUE)
		{
			throw new InvalidArgumentException("invalid glue! ($glue)");
		}

		return $glue;
	}
	
	/**
	 * Tell the queryset to only return a particular type of target. Chainable.
	 *
	 * @param string $typename 'TARGET' or 'CAMPAIGN'
	 * @return Blackbox_Models_TargetRuleQueryset $this (for chaining.)
	 */
	public function targetTypeFilter($typename = 'TARGET')
	{
		$this->target_type = $typename;
		
		return $this;
	}
	
	/**
	 * Tell the queryset object to only return active/inactive objects. Chainable.
	 *
	 * @param bool $active TRUE = active targets, FALSE = inactive, NULL = either
	 * @return Blackbox_Models_TargetRuleQueryset $this (for chaining.)
	 */
	public function targetActiveFilter($active = TRUE)
	{
		if (is_null($active))
		{
			$this->active = NULL;
		}
		else
		{
			$this->active = ($active ? TRUE : FALSE);
		}
		
		return $this;
	}
	
	/**
	 * Filter this queryset based on a rule value for a target. Chainable.
	 *
	 * The main method of this class, it drills down the resulting set of targets
	 * by rule value. I.E. saying "filter('minimum_age', '>', 20);" will cause
	 * this queryset to look for targets with a minimum age rule requiring greater
	 * than 20. How these filters behave/work in concert depends on the glue
	 * provided to this class in each object's constructor.
	 * 
	 * @param string $field
	 * @param string $operator
	 * @param string $value
	 * @return Blackbox_Models_TargetRuleQueryset $this (for chaining.)
	 */
	public function filter($field, $operator, $value)
	{
		$this->joins[] = $this->getNewJoin(
			++$this->join_counter, 
			$field,
			$operator,
			$value,
			// left join when doing ORs
			$this->glue == OLP_DB_WhereGlue::OR_GLUE
		);
		
		$this->where->add(new OLP_DB_And(
			new OLP_DB_WhereCond('rule_value', OLP_DB_WhereCond::IS_NOT, NULL, 'rule_' . $this->join_counter),
			new OLP_DB_WhereCond('name_short', OLP_DB_WhereCond::IS_NOT, NULL, 'rule_definition_' . $this->join_counter)
		));
		
		return $this;
	}
	
	/**
	 * Produce a multi-table join representation for each filter added to this
	 * queryset.
	 * 
	 * (Which sucks for performance but due to the way information is stored in 
	 * blackbox admin ((serialized arrays and such)) it's a flexible solution 
	 * which will work for most queries.)
	 * 
	 * @param string $affix $the affix to add to the table names.
	 * @param string $field The type of rule we'd like to filter on by name, 
	 * such as "minimum_income" 
	 * @param string $operator The operator we'd like to use to compare the rule
	 * value to. ("=", "LIKE", "<", etc) 
	 * @param string $value The value we're comparing the rule value with.
	 * @param bool $left_join Whether to left join (used for OR searches)
	 * @return string A string of SQL to use as a join clause.
	 */
	protected function getNewJoin($affix, $field, $operator, $value, $left_join = FALSE)
	{
		if (!is_numeric($value)
			|| in_array($operator, array(OLP_DB_WhereCond::LIKE, OLP_DB_WhereCond::NOT_LIKE)))
		{
			$value = $this->target->quote($value);
		}
		
		$left_join = $left_join ? 'LEFT ' : '';
		return "$left_join JOIN rule_revision rule_revision_{$affix} 
					ON rule_revision_{$affix}.rule_id=target.rule_id
					AND rule_revision_{$affix}.active
				$left_join JOIN rule_relation rule_relation_{$affix} 
					ON rule_relation_{$affix}.rule_id=target.rule_id
					AND rule_relation_{$affix}.rule_revision_id=rule_revision_{$affix}.rule_revision_id
				$left_join JOIN rule rule_{$affix} 
					ON rule_{$affix}.rule_id=rule_relation_{$affix}.child_id 
					AND rule_{$affix}.rule_value $operator $value 
				$left_join JOIN rule_definition rule_definition_{$affix} 
					ON rule_definition_{$affix}.rule_definition_id=rule_{$affix}.rule_definition_id 
					AND rule_definition_{$affix}.name_short = '$field' ";
	}
	
	/**
	 * Lazy-load iterator which will query target model and return an iterable.
	 *
	 * The weird thing at the end with the count is thrown is for AND situations.
	 * The way the queries work, consider trying to find a target where minimum
	 * age is 23 and minimum income is > 1000. The rows which the query
	 * in the model will return might be similar to:
	 * 
	 * row 1 will be where Target A joins on the minimum_income rule
	 * row 2 will be where Target A joins on the minimum_age rule.
	 * 
	 * Therefore if we're using "AND" glue, we have to make sure that the targets
	 * we're matching  up on met BOTH criteria even though we had to OR the two
	 * WHERE bits together because you can't match BOTH on one row.
	 * 
	 * NOTE: This only works, currently, because it is impossible for blackbox 
	 * admin to nest rule collections in the interface. If nested rule collections
	 * become used, the way this works will have to be fixed. Probably, we will
	 * have to re-evaluate and store metadata for targets in a flat manner to 
	 * allow querying for rules/rule values.
	 * 
	 * @return Traversable
	 */
	public function getIterator()
	{
		// we'll create a new $where every time to avoid messing up our filter where.
		$where = clone $this->where;
		
		// no matter what type of glue we're using, we always want target_type,
		// etc. to be ANDed so wrap it in an AND
		$where = new OLP_DB_And($where);
		
		if (is_string($this->target_type))
		{
			$where->add(new OLP_DB_WhereCond('name', '=', $this->target_type, 'blackbox_type'));
		}
		
		if (is_bool($this->active))
		{
			$where->add(new OLP_DB_WhereCond('active', '=', intval($this->active), 'target'));
		}
		
		$query = "SELECT target.target_id
			FROM target
			JOIN blackbox_type ON blackbox_type.blackbox_type_id=target.blackbox_type_id "
			. implode('', $this->joins) ."
			$where
			GROUP BY target.target_id
			ORDER BY target.target_id";
		return $this->target->fromTargetIdSubquery($query);
	}
	
	// ----------- Object_1 get/set stuff -------------------
	
	/**
	 * Provides the "glue" that this object is using to chain filters.
	 * 
	 * If this is equal to OLP_DB_WhereGlue::OR_GLUE then you will have filters
	 * such as "where minimum_age=19 OR minimum_income > 1000"  ... similarly,
	 * the alternative is OLP_DB_WhereGlue::AND_GLUE which will produce the 
	 * same effect in SQL but substitute AND for OR.
	 *
	 * @return string
	 */
	public function getGlue()
	{
		return $this->glue;
	}
}

?>
