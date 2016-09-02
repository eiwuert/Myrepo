<?php

/**
 * Iterates over special keys presented in a traversable representation of target
 * data for a campaign/target.
 * 
 * These keys are used to assemble a 'data source' for the LenderAPI to use to
 * make XML to be used as "source" for the XSLT transformation in the post.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package LenderAPI
 */
class LenderAPI_ConstantDataSource extends LenderAPI_ParseIterator
{
	/**
	 * Provides the prefix for the value key we're looking for in target data, 
	 * required by parent.
	 *
	 * @return string
	 */
	protected function getValuePattern()
	{
		return '/vendor_api_constant_value_([0-9]+)/i';
	}
	
	/**
	 * provides the prefix for the key's key in the target data, required by parent.
	 *
	 * @return string
	 */
	protected function getKeyPattern()
	{
		return '/vendor_api_constant_name_([0-9]+)/i';
	}
	
	/**
	 * Makes keys to store suitable as XML/XSL tag names.
	 *
	 * @param itn $number The number 
	 * @param unknown_type $value
	 * @param array &$data
	 * @return void
	 */
	protected function addKey($number, $value, array &$data)
	{
		$value = strtolower(str_replace(' ', '_', $value));
		parent::addKey(
			$number, 
			preg_replace('/[^-_0-9a-z]/i', '', str_replace(' ', '_', strtolower($value))),
			$data
		);
	}
	
	/**
	 * Construct a ConstantDataSource from an iterable item.
	 * 
	 * The reason this function is so awkward is that the data is in target data
	 * and stored so that one key/value pair is:
	 * 	vendor_api_constant_name_1 => 'username'
	 *  vendor_api_constant_value_1 => 'sellingsource1'
	 * 
	 * To represent the key/value of 'username' => 'sellingsource1'
	 * 
	 * @throws InvalidArgumentException
	 * @param Traversable|array &$iterable Data representing target data for a target.
	 * @param bool $unset_mode Whether, upon finding keys in the target_data that
	 * this class needs to unset those keys in the original iterable passed in.
	 * @return void
	 */
	function __construct(&$iterable, $unset_mode = FALSE)
	{
		$this->parseIterable($iterable, $unset_mode);
	}
}
?>
