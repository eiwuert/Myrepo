<?php
/**
 * ViewDocument action test class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Actions_ViewDocumentTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->markTestSkipped("Broken test");
	}

	/**
	 * Tests that we return success if validation is successful and getting the document
	 * doesn't throw an exception.
	 *
	 * @return void
	 */
	public function testExecuteReturnsSuccess()
	{
		$app_factory = $this->getMock('VendorAPI_IApplicationFactory');
		$document = $this->getMock('VendorAPI_IDocument');
		$validator = $this->getMock('VendorAPI_Actions_Validators_ViewDocument');
		$state = $this->getMock('VendorAPI_StateObject');
		$driver = $this->getMock('VendorAPI_IDriver');

		$mock_doc = $this->getMock('VendorAPI_DocumentData');
		$mock_doc->expects($this->any())->method('getByArchiveId')
			->will($this->returnValue(1));
		$mock_doc->expects($this->any())->method('getApplicationId')
			->will($this->returnValue(NULL));
		$document->expects($this->once())->method('getByArchiveId')
			->will($this->returnValue($mock_doc));
		$validator->expects($this->any())->method('validate')->will($this->returnValue(TRUE));

		$action = $this->getMock(
			'VendorAPI_Actions_ViewDocument',
			array('getStateObject', 'getValidationErrors'),
			array($app_factory, $document, $validator, $driver)
		);

		$action->expects($this->any())->method('getStateObject')->will($this->returnValue($state));

		$response = $action->execute(NULL, NULL, 'noObject');
		$this->assertTrue((bool)$response->getOutcome());
	}

	/**
	 * Tests that we get a document in the response when we pass an archive ID
	 * to the action and the document object returns a string.
	 *
	 * @return void
	 */
	public function testDocumentStringReturnedInResponse()
	{
		$data = array('archive_id' => 5);
		$my_doc = 'This is a simple document';

		$app_factory = $this->getMock('VendorAPI_IApplicationFactory');
		$document = $this->getMock('VendorAPI_IDocument');
		$validator = $this->getMock('VendorAPI_Actions_Validators_ViewDocument');
		$state = $this->getMock('VendorAPI_StateObject');
		$driver = $this->getMock('VendorAPI_IDriver');

		$mock_doc = $this->getMock('VendorAPI_DocumentData');
		$mock_doc->expects($this->once())->method('getApplicationId')
			->will($this->returnValue(NULL));
		$mock_doc->expects($this->once())->method('getContents')
			->will($this->returnValue($my_doc));
		$document->expects($this->once())->method('getByArchiveId')
			->will($this->returnValue($mock_doc));
		$validator->expects($this->any())->method('validate')->will($this->returnValue(TRUE));

		$document->expects($this->once())
			->method('getByArchiveId')
			->with($data['archive_id'])
			->will($this->returnValue($mock_doc));

		$action = $this->getMock(
			'VendorAPI_Actions_ViewDocument',
			array('getStateObject', 'getValidationErrors'),
			array($app_factory, $document, $validator, $driver)
		);

		$action->expects($this->any())->method('getStateObject')->will($this->returnValue($state));

		// Simple test to make sure we still had a successful outcome
		$response = $action->execute(NULL, $data['archive_id'], 'noObject');
		$this->assertTrue((bool)$response->getOutcome());

		// Check that we actually got our document back
		$result = $response->getResult();
		$this->assertEquals($my_doc, $result['document']);
	}

	/**
	 * Tests that we pass validation with the correct parameters.
	 *
	 * @return void
	 */
	public function testValidationPasses()
	{
		$data = array('application_id' => 1, 'archive_id' => 2);

		$app_factory = $this->getMock('VendorAPI_IApplicationFactory');
		$document = $this->getMock('VendorAPI_IDocument');
		$validator = new VendorAPI_Actions_Validators_ViewDocument();
		$state = $this->getMock('VendorAPI_StateObject');
		$driver = $this->getMock('VendorAPI_IDriver');
		$mock_doc = $this->getMock('VendorAPI_DocumentData');
		$mock_doc->expects($this->once())->method('getApplicationId')
			->will($this->returnValue($data['application_id']));
		$mock_doc->expects($this->once())->method('getContents')
			->will($this->returnValue("Hello World"));
		$document->expects($this->once())->method('getByArchiveId')
			->will($this->returnValue($mock_doc));

		$action = $this->getMock(
			'VendorAPI_Actions_ViewDocument',
			array('getStateObject', 'getValidationErrors'),
			array($app_factory, $document, $validator, $driver)
		);

		$action->expects($this->any())->method('getStateObject')->will($this->returnValue($state));

		$response = $action->execute($data['application_id'], $data['archive_id'], 'noObject');
		$this->assertTrue((bool)$response->getOutcome());
	}

	/**
	 * Tests that we fail validation with incorrect parameters.
	 *
	 * @return void
	 */
	public function testValidationFails()
	{
		$app_factory = $this->getMock('VendorAPI_IApplicationFactory');
		$document = $this->getMock('VendorAPI_IDocument');
		$validator = new VendorAPI_Actions_Validators_ViewDocument();
		$state = $this->getMock('VendorAPI_StateObject');
		$driver = $this->getMock('VendorAPI_IDriver');

		$action = $this->getMock(
			'VendorAPI_Actions_ViewDocument',
			array('getStateObject'),
			array($app_factory, $document, $validator,$driver)
		);

		$action->expects($this->any())->method('getStateObject')->will($this->returnValue($state));

		$response = $action->execute(NULL, NULL, 'noObject');
		$this->assertFalse((bool)$response->getOutcome());

		// Pull out all our field errors
		$errors = $response->getError();
		$field_errors = array();
		foreach ($errors as $validation_error)
		{
			$field_errors[] = $validation_error['field'];
		}

		$this->assertContains('archive_id', $field_errors);
		$this->assertContains('application_id', $field_errors);
	}
}