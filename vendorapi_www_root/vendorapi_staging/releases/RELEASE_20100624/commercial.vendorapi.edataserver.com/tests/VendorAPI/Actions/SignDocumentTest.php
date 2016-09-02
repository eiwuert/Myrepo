<?php
class VendorAPI_Actions_SignDocumentTest extends VendorApiBaseTest 
{
	const TEST_APPID = "99999";

	protected $_app_factory;
	protected $_document;
	protected $_provider;
	protected $_application;
	protected $_driver;
	protected $_action;
	protected $_context;

	public function setUp()
	{
		$this->markTestSkipped('This action is no longer used');
		
		$this->_app_factory = $this->getMock('VendorAPI_IApplicationFactory');
		$this->_document    = $this->getMock('VendorAPI_IDocument');
		$this->_provider    = $this->getMock('VendorAPI_ITokenProvider');
		$this->_application = $this->getMock('VendorAPI_IApplication');
		$this->_driver      = $this->getMock('VendorAPI_IDriver');
		$this->_context     = $this->getMock('VendorAPI_CallContext');

		$this->_action      = new VendorAPI_Actions_SignDocument(
			$this->_app_factory,
			$this->_document,
			$this->_provider,
			$this->_driver
		);
		$this->_action->setCallContext($this->_context);
	}

	public function testErrorOnCreateDocumentFail()
	{
		$this->setGetApplicationExpectation($this->_app_factory, self::TEST_APPID, $this->_application);
		$this->_document->expects($this->once())->method('create')
			->with('test_template', $this->isInstanceOf('VendorAPI_IApplication'), $this->isInstanceOf('VendorAPI_ITokenProvider'), $this->isInstanceOf('VendorAPI_CallContext'))
			->will($this->throwException(new VendorAPI_DocumentCreateException()));
		$result = $this->_action->execute(self::TEST_APPID, 'test_template', FALSE);
		$result = $result->toArray();
		$this->assertEquals(0, $result['outcome']);

	}

	public function testErrorOnSignFail()
	{
		$doc = $this->getMock('VendorAPI_DocumentData');
		$this->setGetApplicationExpectation($this->_app_factory, self::TEST_APPID, $this->_application);
		$this->_document->expects($this->once())->method('create')
			->with('test_template', $this->isInstanceOf('VendorAPI_IApplication'), $this->isInstanceOf('VendorAPI_ITokenProvider'))
			->will($this->returnValue($doc));
		$this->_document->expects($this->once())->method('signDocument')
			->with($this->isInstanceOf('VendorAPI_IApplication'), $this->isInstanceOf('VendorAPI_DocumentData'))
			->will($this->returnValue(FALSE));
		$result = $this->_action->execute(self::TEST_APPID, 'test_template', FALSE);
		$result = $result->toArray();
		$this->assertEquals(0, $result['outcome']);
	}

	public function testSuccess()
	{
		$doc = $this->getMock('VendorAPI_DocumentData');
		$this->_app_factory->expects($this->once())->method('getApplication')
			->with(self::TEST_APPID, $this->isInstanceOf('VendorAPI_IModelPersistor'), $this->isInstanceOf('VendorAPI_StateObject'))
			->will($this->returnValue($this->_application));
		$this->_document->expects($this->once())->method('create')
			->with('test_template', $this->isInstanceOf('VendorAPI_IApplication'), $this->isInstanceOf('VendorAPI_ITokenProvider'))
			->will($this->returnValue($doc));
		$this->_document->expects($this->once())->method('signDocument')
			->with($this->isInstanceOf('VendorAPI_IApplication'), $this->isInstanceOf('VendorAPI_DocumentData'))
			->will($this->returnValue(TRUE));

		$this->_action      = new VendorAPI_Actions_SignDocument(
			$this->_app_factory,
			$this->_document,
			$this->_provider,
			$this->_driver
		);
		$this->_action = $this->getMock(
			'VendorAPI_Actions_SignDocument',
			array('saveState'),
			array($this->_app_factory, $this->_document, $this->_provider, $this->_driver)
		);
		$this->_action->setCallContext($this->_context);
		$this->_application->expects($this->once())->method('addDocument')
			->with($doc, $this->isInstanceOf('VendorAPI_CallContext'));
		$this->_action->expects($this->once())->method('saveState')
			->with($this->isInstanceOf('VendorAPI_StateObject'));
		$doc->expects($this->once())->method('getDocumentId')
			->will($this->returnValue(1));
		$result = $this->_action->execute(self::TEST_APPID, 'test_template', FALSE);
		$result = $result->toArray();
		$this->assertEquals(1, $result['outcome']);
		$this->assertEquals(1, $result['result']['archive_id']);
		$this->assertTrue($result['result']['signed']);
	}

	public function testSuccessButSaveNow()
	{
		$doc = $this->getMock('VendorAPI_DocumentData');
		$this->_app_factory->expects($this->once())->method('getApplication')
			->with(self::TEST_APPID, $this->isInstanceOf('VendorAPI_IModelPersistor'), $this->isInstanceOf('VendorAPI_StateObject'))
			->will($this->returnValue($this->_application));
		$this->_document->expects($this->once())->method('create')
			->with('test_template', $this->isInstanceOf('VendorAPI_IApplication'), $this->isInstanceOf('VendorAPI_ITokenProvider'))
			->will($this->returnValue($doc));
		$this->_document->expects($this->once())->method('signDocument')
			->with($this->isInstanceOf('VendorAPI_IApplication'), $this->isInstanceOf('VendorAPI_DocumentData'))
			->will($this->returnValue(TRUE));

		$this->_action = $this->getMock(
			'VendorAPI_Actions_SignDocument',
			array('saveState', 'getDAOApplication'),
			array($this->_app_factory, $this->_document, $this->_provider, $this->_driver)
		);

		$mock_dao = $this->getMock('VendorAPI_DAO_IApplication');
		$mock_dao->expects($this->once())->method('save')
			->with($this->isInstanceOf('VendorAPI_StateObject'))
			->will($this->returnValue(TRUE));

		$this->_action->expects($this->once())->method('getDAOApplication')
			->will($this->returnValue($mock_dao));
		$this->_action->setCallContext($this->_context);
		$this->_application->expects($this->once())->method('addDocument')
			->with($this->isInstanceOf('VendorAPI_DocumentData'), $this->isInstanceOf('VendorAPI_CallContext'));
		$this->_action->expects($this->never())->method('saveState');

		$doc->expects($this->once())->method('getDocumentId')
			->will($this->returnValue(1));

		$result = $this->_action->execute(self::TEST_APPID, 'test_template', TRUE);
		$result = $result->toArray();
		$this->assertEquals(1, $result['outcome']);
		$this->assertEquals(1, $result['result']['archive_id']);
		$this->assertTrue($result['result']['signed']);
	}
}
