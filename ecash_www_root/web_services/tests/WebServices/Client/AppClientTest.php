<?php
/**
 * Unit tests for the app client
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class WebServices_Client_AppClientTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Mocked Applog
	 *
	 * @var Applog
	 */
	protected $mock_applog;

	/**
	 * Mocked application webservice (webservice pointing to the app wsdl)
	 *
	 * @var WebServices_WebService
	 */
	protected $mock_app_service;

	/**
	 * Mocked application client
	 *
	 * @var WebServices_Client_AppClient
	 */
	protected $mock_app_client;

	/**
	 * Create a mock Applog object
	 *
	 * @return Applog
	 */
	protected function mockApplog()
	{
		if (!($this->mock_applog instanceof Applog))
		{
			$this->mock_applog = $this->getMock('Applog', array('Write'));
		}
		return $this->mock_applog;
	}

	/**
	 * Mock the application webservice
	 *
	 * @return WebServices_WebService
	 */
	protected function mockAppService()
	{
		if (!($this->mock_app_service instanceof WebServices_WebService))
		{
			$this->mock_app_service = $this->getMock(
				'WebServices_WebService',
				array('getEnabled', 'getReadEnabled', 'isReadEnabled', 'isEnabled', 
					'bulkUpdateApplicationPricePoint', 'bulkUpdateApplicationStatus',
					'updateApplicationStatus', 'getApplicationStatus', 
					'getApplicationStatusHistoryResponse', 'updateApplicant', 'updateContactInfo', 
					'getEmploymentInfo', 'updateEmploymentInfo', 'updatePaydateInfo', 'insert',
					'insertApplicantAccount', 'getApplicationPersonalReferences',
					'updatePersonalReference', 'updateApplication', 'insertDoNotLoanFlag',
					'deleteDoNotLoanFlag', 'overrideDoNotLoanFlag', 'updateRegulatoryFlag',
					'getDoNotLoanFlagAll','getDoNotLoanFlagOverrideAll', 'applicationSearch',
					'getApplicationAuditInfo', 'createSoapClient', 'flagSearchBySsn', 'getContactInfo'
				),
				array($this->mockApplog(),'','',''));
				$this->mock_app_service->expects($this->any())->method('createSoapClient')->will($this->returnValue($this->mockSoapClientWrapper()));
		}
		return $this->mock_app_service;
	}

	/**
	 * Mock the application client
	 *
	 * @return WebServices_Client_AppClient
	 */
	protected function mockAppClient()
	{
		if (!($this->mock_app_client instanceof WebServices_Client_AppClient))
		{
			$cache = $this->getMock('WebServices_Cache', array(), array($this->mockApplog()));			
			$this->mock_app_client = $this->getMock(
				'WebServices_Client_AppClient',
				array('isEnabled', 'logException', 'getAppService', 'getStatusName'),
				array($this->mockApplog(), $this->mockAppService(), 1, $cache)
			);
			$this->mock_app_client->expects($this->any())->method('getAppService')->will($this->returnValue($this->mockAppService()));
		}
		return $this->mock_app_client;
	}

	/**
	 * Mocks a soapclient wrapper
	 * @return SoapClientWrapper
	 */
	protected function mockSoapClientWrapper()
	{
		if (!($this->mock_soap_client instanceof SoapClientWrapper))
		{
			$this->mock_soap_client = $this->getMock(
				'SoapClientWrapper',
				array(),
				array('', array(), $this->mockApplog())
			);
		}

		return $this->mock_soap_client;
	}

	/**
	 * Test that when the app service is not enabled we get the right return value
	 *
	 * @return void
	 */
	public function testAppServiceDisabled()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(FALSE));		
		$this->mockAppClient()->expects($this->any())->method('getStatusName')->will($this->returnValue('pending::prospect::*root'));
		
		$this->mockAppService()->expects($this->never())->method('bulkUpdateApplicationPricePoint');
		$this->assertFalse($this->mockAppClient()->bulkUpdateApplicationPricePoint(array('test')));
		
		$this->mockAppService()->expects($this->never())->method('bulkUpdateApplicationStatus');
		$this->assertFalse($this->mockAppClient()->bulkUpdateApplicationStatus(array(1), 1, 'pending::prospect::*root'));
		
		$this->mockAppService()->expects($this->never())->method('updateApplicationStatus');
		$this->assertFalse($this->mockAppClient()->updateApplicationStatus(array(1), 1, 'pending::prospect::*root'));
		
		$this->mockAppService()->expects($this->never())->method('getApplicationStatus');
		$this->assertFalse($this->mockAppClient()->getApplicationStatus(1));
		
		$this->mockAppService()->expects($this->never())->method('getApplicationStatusHistory');
		$this->assertNull($this->mockAppClient()->getApplicationStatusHistory(1));
		
		$this->mockAppService()->expects($this->never())->method('updateApplicant');
		$this->assertFalse($this->mockAppClient()->updateApplicant(1, array('city' => 'test')));
		
		$this->mockAppService()->expects($this->never())->method('updateContactInfo');
		$this->assertFalse($this->mockAppClient()->updateContactInfo(1, array('city' => 'test')));
		
		$this->mockAppService()->expects($this->never())->method('getEmploymentInfo');
		$this->assertFalse($this->mockAppClient()->getEmploymentInfo(1));

		$this->mockAppService()->expects($this->never())->method('getApplicationPersonalReferences');
		$this->assertFalse($this->mockAppClient()->getAppPersonalRefs(1));
		
		$this->mockAppService()->expects($this->never())->method('updateEmploymentInfo');
		$this->assertFalse($this->mockAppClient()->updateEmploymentInfo(1, array('employer_name' => 'SellingSource')));
		
		$this->mockAppService()->expects($this->never())->method('updatePaydateInfo');
		$this->assertFalse($this->mockAppClient()->updatePaydateInfo(1, array('income_monthly' => 27)));

		$this->mockAppService()->expects($this->never())->method('insert');
		$this->assertFalse($this->mockAppClient()->insert(array('application_id' => '1234567')));
		
		$this->mockAppService()->expects($this->never())->method('insertApplicantAccount');
		$this->assertFalse($this->mockAppClient()->insertApplicantAccount('12345678', 'BASE', 'PASSWORD'));

		$this->mockAppService()->expects($this->never())->method('updatePersonalReference');
		$this->assertFalse($this->mockAppClient()->updatePersonalReference(1, 1, 1, 'John Doe','555-555-55555','Friend','do not contact','unverified'));

		$this->mockAppService()->expects($this->never())->method('updateApplication');
		$this->assertFalse($this->mockAppClient()->updateApplication(1, array('price_point' => 30.25)));
		
		$this->mockAppService()->expects($this->never())->method('insertDoNotLoanFlag');
		$this->assertFalse($this->mockAppClient()->insertDoNotLoanFlag(1,123456789, 'fraud', '', ''));
		
		$this->mockAppService()->expects($this->never())->method('deleteDoNotLoanFlag');
		$this->assertFalse($this->mockAppClient()->deleteDoNotLoanFlag(1,123456789));
		
		$this->mockAppService()->expects($this->never())->method('overrideDoNotLoanFlag');
		$this->assertFalse($this->mockAppClient()->overrideDoNotLoanFlag(1,123456789));
		
		$this->mockAppService()->expects($this->never())->method('updateRegulatoryFlag');
		$this->assertFalse($this->mockAppClient()->updateRegulatoryFlag(1, 1, TRUE, 'add_reg_flag_agency', 'REG_FLAG'));
		
		$this->mockAppService()->expects($this->never())->method('getDoNotLoanFlagOverrideAll');
		$this->assertFalse($this->mockAppClient()->getDoNotLoanFlagOverrideAll(123456789));
		
		$this->mockAppService()->expects($this->never())->method('getDoNotLoanFlagAll');
		$this->assertFalse($this->mockAppClient()->getDoNotLoanFlagAll(123456789));
		
		$this->mockAppService()->expects($this->never())->method('applicationSearch');
		$this->assertFalse($this->mockAppClient()->applicationSearch(array(array('field' => 'phone', 'strategy' => 'is', 'searchCriteria' => '4845559614')), 100));

		$this->mockAppService()->expects($this->never())->method('getApplicationAudit');
		$this->assertFalse($this->mockAppClient()->getApplicationAudit(123456789));
		
		$this->mockAppService()->expects($this->never())->method('flagSearchBySsn');
		$this->assertFalse($this->mockAppClient()->flagSearchBySsn(123456789));

		$this->mockAppService()->expects($this->never())->method('getContactInfo');
		$this->assertFalse($this->mockAppClient()->getContactInfo(123456789));
	}

	/**
	 * Test that when the app service has reads disabled we get the right return value
	 *
	 * @return void
	 */
	public function testAppServiceReadDisabled()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isReadEnabled')->will($this->returnValue(FALSE));		
		
		$this->mockAppService()->expects($this->never())->method('getEmploymentInfo');
		$this->assertFalse($this->mockAppClient()->getEmploymentInfo(1));
		
		$this->mockAppService()->expects($this->never())->method('getApplicationPersonalReferences');
		$this->assertFalse($this->mockAppClient()->getAppPersonalRefs(1));

		$this->mockAppService()->expects($this->never())->method('getDoNotLoanFlagOverrideAll');
		$this->assertFalse($this->mockAppClient()->getDoNotLoanFlagOverrideAll(123456789));

		$this->mockAppService()->expects($this->never())->method('getDoNotLoanFlagAll');
		$this->assertFalse($this->mockAppClient()->getDoNotLoanFlagAll(123456789));
		
		$this->mockAppService()->expects($this->never())->method('applicationSearch');
		$this->assertFalse($this->mockAppClient()->applicationSearch(array(array('field' => 'phone', 'strategy' => 'is', 'searchCriteria' => '4845559614')), 100));

		$this->mockAppService()->expects($this->never())->method('getApplicationAudit');
		$this->assertFalse($this->mockAppClient()->getApplicationAudit(123456789));
		
		$this->mockAppService()->expects($this->never())->method('flagSearchBySsn');
		$this->assertFalse($this->mockAppClient()->flagSearchBySsn(123456789));

		$this->mockAppService()->expects($this->never())->method('getContactInfo');
		$this->assertFalse($this->mockAppClient()->getContactInfo(123456789));
	}

	/**
	 * Test that when the app service doesn't find the record we get the right return value
	 *
	 * @return void
	 */
	public function testAppServiceApplicationNotFound()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isReadEnabled')->will($this->returnValue(TRUE));
		$this->mockApplog()->expects($this->once())->method('Write')->will($this->returnValue(TRUE));
		
		$this->mockAppService()->expects($this->once())->method('getEmploymentInfo')->will($this->returnValue(new stdClass()));
		$this->assertFalse($this->mockAppClient()->getEmploymentInfo(1));
	}

	/**
	 * Test that when the app service throws an exception we get the right return value and log it
	 *
	 * @return void
	 */
	public function testAppServiceException()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(TRUE));
		$this->mockAppService()->expects($this->atLeastOnce())->method('isReadEnabled')->will($this->returnValue(TRUE));
		$this->mockAppClient()->expects($this->any())->method('getStatusName')->will($this->returnValue('pending::prospect::*root'));
		$this->mockAppClient()->expects($this->exactly(24))->method('logException');
		
		$this->mockAppService()->expects($this->once())->method('bulkUpdateApplicationPricePoint')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->bulkUpdateApplicationPricePoint(array('test')));
		
		$this->mockAppService()->expects($this->once())->method('bulkUpdateApplicationStatus')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->bulkUpdateApplicationStatus(array(1), 1, 'pending::prospect::*root'));
		
		$this->mockAppService()->expects($this->once())->method('updateApplicationStatus')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->updateApplicationStatus(array(1), 1, 'pending::prospect::*root'));
		
		$this->mockAppService()->expects($this->once())->method('getApplicationStatus')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->getApplicationStatus(1));
		
		$this->mockAppService()->expects($this->once())->method('getApplicationStatusHistoryResponse')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertNull($this->mockAppClient()->getApplicationStatusHistory(1));
		
		$this->mockAppService()->expects($this->once())->method('updateApplicant')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->updateApplicant(1, array('city' => 'test')));
		
		$this->mockAppService()->expects($this->once())->method('updateContactInfo')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->updateContactInfo(1, array('phone_home' => '5555555555')));
		
		$this->mockAppService()->expects($this->once())->method('getEmploymentInfo')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->getEmploymentInfo(1));
		
		$this->mockAppService()->expects($this->once())->method('updateEmploymentInfo')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->updateEmploymentInfo(1, array('employer_name' => 'SellingSource')));
		
		$this->mockAppService()->expects($this->once())->method('updatePaydateInfo')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->updatePaydateInfo(1, array('income_monthly' => 27)));
		
		$this->mockAppService()->expects($this->once())->method('insert')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->insert(array('name_first' => 'test')));
		
		$this->mockAppService()->expects($this->once())->method('insertApplicantAccount')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->insertApplicantAccount('12345678', 'BASE', 'PASSWORD'));
		
		$this->mockAppService()->expects($this->once())->method('getApplicationPersonalReferences')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->getAppPersonalRefs(1));
		
		$this->mockAppService()->expects($this->once())->method('updatePersonalReference')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->updatePersonalReference(1, 1, 1, 'John Doe','555-555-55555','Friend','do not contact','unverified'));

		$this->mockAppService()->expects($this->once())->method('updateApplication')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->updateApplication(1, array('price_point' => 30.25)));

		$this->mockAppService()->expects($this->once())->method('insertDoNotLoanFlag')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->insertDoNotLoanFlag(1, 123456789, 'other', 'reason', 'explanation'));
		
		$this->mockAppService()->expects($this->once())->method('deleteDoNotLoanFlag')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->deleteDoNotLoanFlag(1, 123456789));
		
		$this->mockAppService()->expects($this->once())->method('overrideDoNotLoanFlag')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->overrideDoNotLoanFlag(1, 123456789));

		$this->mockAppService()->expects($this->once())->method('updateRegulatoryFlag')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->updateRegulatoryFlag(1, 1, TRUE, 'add_reg_flag_agency', 'REG_FLAG'));

		$this->mockAppService()->expects($this->once())->method('getDoNotLoanFlagOverrideAll')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->getDoNotLoanFlagOverrideAll(123456789));
	
		$this->mockAppService()->expects($this->once())->method('getDoNotLoanFlagAll')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->getDoNotLoanFlagAll(123456789));
		
		$this->mockAppService()->expects($this->once())->method('applicationSearch')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertEquals(
			array(),
			$this->mockAppClient()->applicationSearch(array(array('field' => 'phone', 'strategy' => 'is', 'searchCriteria' => '4845559614')), 100)
		);

		$this->mockAppService()->expects($this->once())->method('getApplicationAuditInfo')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->getApplicationAudit(123456789));

		$this->mockAppService()->expects($this->once())->method('flagSearchBySsn')->will($this->throwException(new Exception('Web Service Error')));
		$this->assertFalse($this->mockAppClient()->flagSearchBySsn(123456789));
	}

	/**
	 * Test that filtering removes invalid fields
	 *
	 * @return void
	 */
	public function testFilteringFail()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(TRUE));
		$this->assertFalse($this->mockAppClient()->updateApplicant('1', array('bad_field'=>'ignore')));
		$this->assertFalse($this->mockAppClient()->updateContactInfo('1',array('bad_field'=>'ignore')));
		$this->assertFalse($this->mockAppClient()->updateApplication('1', array('bad_field' => 'ignore')));
	}

	/**
	 * Test the regulatory flag
	 * 
	 * @return void
	 */
	public function testUpdateRegulatoryFlag()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(TRUE));

		$dto_data = array(
			'application_id' => 1,
			'active_status' => TRUE,
			'loan_action_name' => 'test_name',
			'loan_action_section' => 'test_section',
			'modifying_agent_id' => 101
		);

		$this->mockAppService()->expects($this->once())->method('updateRegulatoryFlag')->with($this->equalTo($dto_data))->will($this->returnValue(TRUE));
		$this->assertTrue($this->mockAppClient()->updateRegulatoryFlag(1, 101, TRUE, 'test_name', 'test_section'));
	}

	/**
	 * Test that filtering for applicant works
	 *
	 * @return void
	 */
	public function testFilteringPassApplicant()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(TRUE));

		// Base data that should remain the same
		$data = array('ssn' => '123456789');
		// Data the filter will remove
		$data_bad = array('bad_field'=>'123456');
		// Data to be passed to the filter
		$data_unfiltered = array_merge($data,$data_bad);
		// Data the filter will add
		$data_additional = array('ssn_last_four' => '6789', 'application_id' => '123456', 'modifying_agent_id' => 1);
		// Data the filter should return
		$data_filtered = array_merge($data, $data_additional);
		$this->mockAppService()->expects($this->once())->method('updateApplicant')->with($this->equalTo($data_filtered))->will($this->returnValue(TRUE));
		$this->assertTrue($this->mockAppClient()->updateApplicant('123456',$data_unfiltered));
	}
	/**
	 * Test that filtering for applicant works
	 *
	 * @return void
	 */
	public function testFilteringPassApplication()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(TRUE));

		// Base data that should remain the same
		$data = array('fund_actual' => '123456789');
		// Data the filter will remove
		$data_bad = array('bad_field'=>'123456');
		// Data to be passed to the filter
		$data_unfiltered = array_merge($data,$data_bad);
		// Data the filter will add
		$data_additional = array('application_id' => '123456', 'modifying_agent_id' => 1);
		// Data the filter should return
		$data_filtered = array_merge($data, $data_additional);
		$this->mockAppService()->expects($this->once())->method('updateApplication')->with($this->equalTo($data_filtered))->will($this->returnValue(TRUE));
		$this->assertTrue($this->mockAppClient()->updateApplication('123456',$data_unfiltered));
	}

	/**
	 * Test that filtering for contact info works
	 *
	 * @return void
	 */
	public function testFilteringPassContactInfo()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(TRUE));
		
		$application_id = '123456789';
		// Base data that should remain the same
		$data = array('phone_home'=>'123-456-7890','email' => 'test@phpunit.com');
		// Data the filter will remove
		$data_bad = array('bad_field'=>'123456');
		// Data to be passed to the filter
		$data_unfiltered = array_merge($data, $data_bad);
		// Data the filter should return
		$data_filtered_args = array();

		foreach ($data as $type => $value)
		{
			$data_filtered_args[] = array(
					'application_id' => $application_id,
					'type' => $type,
					'value' => $value,
					'modifying_agent_id' => 1
				);
		}

		$this->mockAppService()->expects($this->once())->method('updateContactInfo')->with($this->equalTo($data_filtered_args))->will($this->returnValue(TRUE));
		$this->assertTrue($this->mockAppClient()->updateContactInfo($application_id, $data_unfiltered));
	}

	/**
	 * Test that filtering for employment info works
	 *
	 * @return void
	 */
	public function testFilteringPassEmploymentInfo()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(TRUE));

		// Base data that should remain the same
		$data = array('employer_name'=>'SellingSource','job_title' => 'PHP Developer');
		// Data the filter will remove
		$data_bad = array('bad_field'=>'123456');
		// Data to be passed to the filter
		$data_unfiltered = array_merge($data, $data_bad);
		// Data the filter will add
		$data_additional = array('application_id' => '123456', 'modifying_agent_id' => 1);
		// Data the filter should return
		$data_filtered = array_merge($data, $data_additional);

		$this->mockAppService()->expects($this->once())->method('updateEmploymentInfo')->with($this->equalTo($data_filtered))->will($this->returnValue(TRUE));
		$this->assertTrue($this->mockAppClient()->updateEmploymentInfo('123456',$data_unfiltered));
	}
	
	/**
	 * Test that filtering for paydate info works
	 *
	 * @return void
	 */
	public function testFilteringPassPaydateInfo()
	{
		$this->mockAppService()->expects($this->atLeastOnce())->method('isEnabled')->will($this->returnValue(TRUE));

		// Base data that should remain the same
		$data = array('paydate_model'=>'dw','income_monthly' => '1462');
		// Data the filter will remove
		$data_bad = array('bad_field'=>'123456');
		// Data to be passed to the filter
		$data_unfiltered = array_merge($data, $data_bad);
		// Data the filter will add
		$data_additional = array('application_id' => '123456', 'modifying_agent_id' => 1);
		// Data the filter should return
		$data_filtered = array_merge($data, $data_additional);

		$this->mockAppService()->expects($this->once())->method('updatePaydateInfo')->with($this->equalTo($data_filtered))->will($this->returnValue(TRUE));
		$this->assertTrue($this->mockAppClient()->updatePaydateInfo('123456',$data_unfiltered));
	}
}
?>
