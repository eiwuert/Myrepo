<?php
class VendorAPI_Save_DefaultTest extends PHPUnit_Framework_TestCase
{
	CONST TABLE = 'table';
	
	protected $_handler;
	protected $_factory;
	protected $_driver;
	protected $_db;
	
	public function setup()
	{
		$this->_driver = $this->getMock('VendorAPI_IDriver');
		$this->_db = $this->getMock('DB_IConnection_1');
	
		$this->_handler = new VendorAPI_Save_Default(
			$this->_driver, 
			self::TABLE, 
			$this->_db
		);
		
	}
	
	public function testSaveTo()
	{
		$model = new VendorAPITestModel();
		$this->_driver->expects($this->once())
			->method('getDataModelByTable')
			->with(self::TABLE, $this->isInstanceOf('DB_IConnection_1'))
			->will($this->returnValue($model));
		
		$batch = $this->getMock('DB_Models_Batch_1', array('save'), array(), '', FALSE);
		$batch->expects($this->once())
			->method('save')
			->with($this->isInstanceOf('VendorAPITestModel'));
		
		$data = array(
			'col1' => "hello",
			'col2' => "world"
		);
		$this->_handler->saveTo($data, $batch);
		$this->assertEquals($data['col1'], $model->col1);
		$this->assertEquals($data['col2'], $model->col2);
	}
	
	public function testSavesWithReferenceData()
	{
		$model = new VendorAPITestModel();
		$locator = $this->getMock('VendorAPI_ReferenceColumn_Locator', array(), array(), '', FALSE);
		$locator->expects($this->once())->method('resolveReference')->will($this->returnValue(12));
		
		$batch = $this->getMock('DB_Models_Batch_1', array('save'), array(), '', FALSE);
		$batch->expects($this->once())
			->method('save')
			->with($this->isInstanceOf('VendorAPITestModel'));
		
		$model->col1 = "Hello World";
		$model->other_model_id = $locator;
		
		$this->_driver->expects($this->once())->method('getDataModelByTable')
			->with(self::TABLE, $this->isInstanceOf('DB_IConnection_1'))
			->will($this->returnValue($model));
		
		$data = $model->getColumnData();
	
		$this->_handler->saveTo($data, $batch);
		$this->assertEquals(12, $model->other_model_id);
		
			
	}
}