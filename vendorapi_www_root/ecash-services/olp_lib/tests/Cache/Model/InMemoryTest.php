<?php

/**
 * Tests an "in memory" (i.e. array) cache of models.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class Cache_Model_InMemoryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * List of models to test with.
	 *
	 * @var array
	 */
	protected $array_of_models;
	
	/**
	 * Load up some models to search with.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		$this->array_of_models = array(
			'red_model' => $this->freshModelWithProperties(array('color' => 'red')),
			'blue_model' => $this->freshModelWithProperties(array('color' => 'blue')),
			'green_model' => $this->freshModelWithProperties(array('color' => 'green')),
		);
	}
	
	/**
	 * Test storing and retrieving objects from the cache.
	 *
	 * @dataProvider storageAndRetrievalProvider
	 * @param array $search_params The parameters to search with.
	 * @return void
	 */
	public function testStorageAndRetrieval($search_params, $expected_keys)
	{
		$cache = new Cache_Model_InMemory();
		$cache->setCache(
			array_values($this->array_of_models)	// don't need/want the named keys
		);
		
		// actual method we're testing
		$find_results = $cache->find($search_params);
		
		// verify results
		$this->assertEquals(
			count($find_results),
			count($expected_keys),
			sprintf('Found an unexpected amount of objects (%s)', count($find_results))
		);
		
		foreach ($expected_keys as $key)
		{
			$this->assertTrue(in_array($this->array_of_models[$key], $find_results),
				"Unable to find model (named by $key) in cache."
			);
		}
	}
	
	/**
	 * Test retrieving objects in {@see testStorageAndRetrieval} with both 
	 * single search terms and an array of search terms.
	 *
	 * @return array
	 */
	public static function storageAndRetrievalProvider()
	{
		return array(
			// search with a simple string to compare
			array(array('color' => 'red'), array('red_model')),
			
			// search with a list of strings to compare to
			array(array('color' => array('blue', 'green')), array('blue_model', 'green_model')),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Fixture method to get a new OLP_MockIModel with properties set on it.
	 *
	 * @param array $properties
	 * @return OLP_IModel
	 */
	protected function freshModelWithProperties(array $properties = array())
	{
		$model = new OLP_MockIModel();
		foreach ($properties as $name => $value) $model->$name = $value;
		return $model;
	}
}

?>