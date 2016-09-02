<?php
/**
 * Unit tests for the inquiry client
 * 
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class ECash_WebService_InquiryClientTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Mocked inquiry webservice (webservice pointing to the inquiry wsdl)
	 *
	 * @var WebServices_WebService
	 */
	protected $mock_inquiry_service;

	/**
	 * Mocked inquiry client
	 *
	 * @var WebServices_Client_InquiryClient
	 */
	protected $mock_inquiry_client;

	/**
	 * Mock the inquiry webservice
	 *
	 * @return WebServices_WebService
	 */
	protected function mockInquiryService()
	{
		if (!($this->mock_inquiry_service instanceof WebServices_WebService))
		{
			$applog = $this->getMock('Applog');
			$this->mock_inquiry_service = $this->getMock(
				'WebServices_WebService',
				array('getEnabled', 'getReadEnabled', 'isEnabled', 'ssnHasFailure', 
					'ssnHasFailureWithinDays', 'recordSkipTrace', 'recordInquiry',
					'findInquiriesByApplicationID',	'retrieveReceivedPackages',
					'findInquiryById', 'getSkipTraceData'
				),
				array($applog, '', '', '')
			);
		}
		return $this->mock_inquiry_service;
	}

	/**
	 * Mock the application client
	 *
	 * @return WebServices_Client_AppClient
	 */
	protected function mockInquiryClient()
	{
		if (!($this->mock_inquiry_client instanceof WebServices_Client_InquiryClient))
		{
			$applog = $this->getMock('Applog');			
			$this->mock_inquiry_client = $this->getMock(
				'WebServices_Client_InquiryClient',
				array('isEnabled', 'logException', 'getInquiryService', 'getStatusName',
					'throwException'),
				array($applog),
				'',
				FALSE
			);
			$this->mock_inquiry_client->expects($this->any())->method('getInquiryService')->will($this->returnValue($this->mockInquiryService()));
		}
		return $this->mock_inquiry_client;
	}

	/**
	 * Test that when the inquiry service is not enabled we get the right return value
	 *
	 * @return void
	 */
	public function testInquiryServiceDisabled()
	{
		$this->mockInquiryService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(FALSE));

		$this->mockInquiryService()->expects($this->never())->method('ssnHasFailure');
		$this->assertFalse($this->mockInquiryClient()->ssnHasFailure('',''));
		$this->assertFalse($this->mockInquiryClient()->ssnHasFailure('','',3));
		
		$this->mockInquiryService()->expects($this->never())->method('recordSkipTrace');
		$this->assertFalse($this->mockInquiryClient()->recordSkipTrace('', 0, '', '','',0,array()));

		$this->mockInquiryService()->expects($this->never())->method('recordInquiry');
		$this->assertFalse($this->mockInquiryClient()->recordInquiry(''));

		$this->mockInquiryService()->expects($this->never())->method('getSkipTraceData');
		$this->assertFalse($this->mockInquiryClient()->getSkipTraceData(''));
		
		$this->mockInquiryService()->expects($this->never())->method('findInquiriesByApplicationID');
		$this->assertEquals($this->mockInquiryClient()->findInquiriesByApplicationID(''),array());
		
		$this->mockInquiryService()->expects($this->never())->method('getReceivedPackages');
		$this->assertFalse($this->mockInquiryClient()->getReceivedPackages('','','',''));
		
		$this->mockInquiryService()->expects($this->never())->method('findInquiryById');
		$this->assertFalse($this->mockInquiryClient()->findInquiryById(''));

		$this->mockInquiryService()->expects($this->never())->method('getSkipTraceData');
		$this->assertFalse($this->mockInquiryClient()->getSkipTraceData(''));
	}

	/**
	 * Test that when the inquiry service is not enabled we get the right return value
	 *
	 * @return void
	 */
	public function testInquiryServiceException()
	{
		$this->mockInquiryService()
			->expects($this->atLeastOnce())
			->method('isEnabled')
			->will($this->returnValue(TRUE));

		$this->mockInquiryClient()->expects($this->exactly(8))->method('logException');

		$this->mockInquiryService()
			->expects($this->once())
			->method('ssnHasFailure')
			->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockInquiryClient()->ssnHasFailure('',''));
		
		$this->mockInquiryService()
			->expects($this->once())
			->method('ssnHasFailureWithinDays')
			->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockInquiryClient()->ssnHasFailure('','',3));

		$this->mockInquiryService()
			->expects($this->once())
			->method('recordSkipTrace')
			->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockInquiryClient()->recordSkipTrace('', 0, '', '','',0,array()));

		$this->mockInquiryService()
			->expects($this->once())
			->method('recordInquiry')
			->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockInquiryClient()->recordInquiry(''));

		$this->mockInquiryService()
			->expects($this->once())
			->method('getSkipTraceData')
			->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockInquiryClient()->getSkipTraceData(''));

		$this->mockInquiryService()
			->expects($this->once())
			->method('findInquiriesByApplicationID')
			->will($this->throwException(new Exception('Web Service Error')));
		$this->assertEquals($this->mockInquiryClient()->findInquiriesByApplicationID(''),array());

		$this->mockInquiryService()
			->expects($this->once())
			->method('retrieveReceivedPackages')
			->will($this->throwException(new Exception('Web Service Error')));
		$this->mockInquiryClient()
			->expects($this->once())
			->method('throwException')
			->will($this->returnValue(FALSE));
		$this->assertFalse($this->mockInquiryClient()->getReceivedPackages('','','',''));

		$this->mockInquiryService()
			->expects($this->once())
			->method('findInquiryById')
			->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockInquiryClient()->findInquiryById(''));
	}
}
?>