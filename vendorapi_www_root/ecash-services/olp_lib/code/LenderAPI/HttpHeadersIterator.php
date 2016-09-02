<?php

/**
 * Parses rows of target_data and then can be iterated over to get http headers.
 * 
 * Example, if target_data has the following keys:
 * 	'vendor_api_header_name_1' => 'Content-Type',
 * 	'vendor_api_header_value_1' => 'text/html',
 * 
 * Then when iterating over this item, you will get a key/value of:
 * 'Content-Type' => 'text/html'
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package LenderAPI
 */
class LenderAPI_HttpHeadersIterator extends LenderAPI_ParseIterator
{
	/**
	 * Post type to use to get the key pattern
	 *
	 * @var string
	 */
	protected $post_type;
	
	/**
	 * Construct an iterator that can take rows of target_data and produce an
	 * iterable list of http headers.
	 * @param array|Traversable &$data A list of key/value rows representing 
	 * target_data items.
	 * @param bool $post_type what type of post to run (post, verify_post)
	 * @return void
	 */
	public function __construct(&$data, $post_type)
	{
		$this->post_type = $post_type;
		$this->parseIterable($data);
	}
	
	/**
	 * @see LenderAPI_ParseIterator::getKeyPattern()
	 * @return string
	 */
	protected function getKeyPattern()
	{
		if ($this->post_type == LenderAPI_Generic_Client::POST_TYPE_VERIFY)
		{
			$pattern = '/vendor_api_header_verify_name_([0-9]+)/';
		}
		else
		{
			$pattern =  '/vendor_api_header_name_([0-9]+)/';
		}
		return $pattern;
	}
	
	/**
	 * @see LenderAPI_ParseIterator::getValueattern()
	 * @return string
	 */
	protected function getValuePattern()
	{
		if ($this->post_type == LenderAPI_Generic_Client::POST_TYPE_VERIFY)
		{
			$pattern =  '/vendor_api_header_verify_value_([0-9]+)/';
		}
		else
		{
			$pattern =  '/vendor_api_header_value_([0-9]+)/';
		}
		return $pattern;
	}
}

?>
