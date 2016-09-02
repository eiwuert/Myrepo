<?php

/**
 * Test the OLP_Models_Cacheable class which caches a list of models for retrival
 * via an interface which defines models.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLP_Models_CacheableTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test basic cacheable model.
	 *
	 * @return void
	 */
	public function testIModelCacheUsage()
	{
		$model = $this->freshIModel();
		
		$search_params = array('column' => 'value');
		
		$list_of_models = array($this->freshIModel());
		
		// build fake cache implementation to be utilized to find models
		$model_cache = $this->freshIModelCache();
		$model_cache->expects($this->exactly(2))
			->method('find')
			->with($search_params)
			->will($this->returnValue($list_of_models));
		$model_cache->expects($this->once())
			->method('store')
			->will($this->returnValue(TRUE))
			->with($this->equalTo($model));
		
		$cacheable = new OLP_Models_Cacheable($model, $model_cache);
		
		
		// run the methods on the cacheable model that we expect to call the cache
		$this->assertEquals($list_of_models, $cacheable->loadAllBy($search_params), 
			'Unable to loadAllBy() and get the right models back.'
		);
		$this->assertEquals(TRUE, $cacheable->loadBy($search_params), 
			'Unable to load the model queried for individually.'
		);
		$this->assertEquals(1, $cacheable->save(), 'Save did not work.');
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Fixture method to return mock OLP_IModel.
	 *
	 * @return OLP_IModel
	 */
	protected function freshIModel()
	{
		return $this->getMock('OLP_IModel');
	}
	
	/**
	 * Fixture method to return an OLP_IModelCache
	 *
	 * @return OLP_IModelCache
	 */
	protected function freshIModelCache()
	{
		return $this->getMock('OLP_IModelCache');
	}
}

?>
