<?php 

class VendorAPI_StateObjectModelTest extends PHPUnit_Framework_TestCase
{
	protected $model;
	public function setUp()
	{
		$this->model = $this->getMock('VendorAPI_StateObjectModel', array('getPathFormat'), array('ufc'), '', FALSE);
		$this->model->expects($this->any())->method('getPathFormat')->will($this->returnValue('./%%%NAME_SHORT%%%/'));
		$this->model->setNameShort('ufc');
	}
	
	public function tearDown()
	{
		self::cleanupDirectory($this->model->getBasePath());
			
	}
	
	public function testSave()
	{
		$state = new VendorAPI_StateObject();
		$this->assertTrue(is_numeric($this->model->save($state)));
	}
	
	public static function cleanupDirectory($path)
	{
		$files = glob($path.DIRECTORY_SEPARATOR.'*');
		foreach ($files as $file)
		{
			if (is_dir($file))
			{
				self::cleanupDirectory($file);
			}
			else 
			{
				unlink($file);
			}
		}
		rmdir($path);
	}
}

