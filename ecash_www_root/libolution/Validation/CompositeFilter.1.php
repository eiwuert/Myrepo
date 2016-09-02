<?php

/**
 * @package Validation
 */

/**
 * Composite implementation of IFilter
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Validation_CompositeFilter_1 implements Validation_IFilter_1
{
	/**
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Add a filter to the composite
	 * @param Validation_IFilter_1 $f
	 * @return void
	 */
	public function addFilter(Validation_IFilter_1 $f)
	{
		$this->filters[] = $f;
	}

	/**
	 * Executes the contained filter
	 * @param arrayaccess $data
	 * @param Validation_ValidatorResult_1 $result
	 * @return array
	 */
	public function execute($data, Validation_ValidatorResult_1 $result)
	{
		foreach ($this->filters as $f)
		{
			$f->execute($data, $result);
		}
	}
}

?>