<?php

/**
 * Tests the OLPECash_VendorAPI class.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLPECash_VendorAPITest extends PHPUnit_Framework_TestCase
{
	const PROPERTY_SHORT = 'VENDOR_API_TEST';
	const MODE = 'unittest';
	const APPLICATION_ID = 1;
	const TARGET_ID = 2;
	
	public function testSaveChangedStateObject()
	{
		$state_object_model = $this->getMock('OLP_Models_VendorStateObject', array('save'), array(), '', FALSE);
		
		// State object
		$state_object = array(
			'internal state data' => TRUE,
		);
		
		$state_object_model->state_object = $state_object;
		$bbx_factory = $this->getMock('Blackbox_ModelFactory', array('getReferenceTable'), array(), '', FALSE);
		
		$factory = $this->getMock('OLP_Factory', array('getModel', 'getReferenceModel', 'getBlackboxModelFactory'), array(), '', FALSE);
		$factory->expects($this->any())->method('getBlackboxModelFactory')
			->will($this->returnValue($bbx_factory));

		// Create mocked API object
		$vendor_api = $this->getMock(
			'OLPECash_VendorAPI',
			array(
				'call',
				'getStateObjectModel',
				'storePackets',
			),
			array(
				self::MODE,
				self::APPLICATION_ID,
				self::PROPERTY_SHORT,
				$factory
			)
		);
		$vendor_api->expects($this->once())->method('getStateObjectModel')
			->will($this->returnValue($state_object_model));
		$state_object_model->expects($this->once())->method('save');
		// Call will return the state object
		$vendor_api->expects($this->once())
			->method('call')
			->will($this->returnValue(
				array(
					'outcome' => 1,
					'state_object' => "some super fancy new state object that needs to be saved.",
					'doesntreally matter' => TRUE
				)
			));
		$vendor_api->test();
	}
	
	public function testDontSaveUnchangedStateObject()
	{
		$state_object_model = $this->getMock('OLP_Models_VendorStateObject', array('save'), array(), '', FALSE);
		
		// State object
		$state_object = array(
			'internal state data' => TRUE,
		);
		
		$state_object_model->state_object = $state_object;
		$bbx_factory = $this->getMock('Blackbox_ModelFactory', array('getReferenceTable'), array(), '', FALSE);
		
		$factory = $this->getMock('OLP_Factory', array('getModel', 'getReferenceModel', 'getBlackboxModelFactory'), array(), '', FALSE);
		$factory->expects($this->any())->method('getBlackboxModelFactory')
			->will($this->returnValue($bbx_factory));

		// Create mocked API object
		$vendor_api = $this->getMock(
			'OLPECash_VendorAPI',
			array(
				'call',
				'getStateObjectModel',
				'storePackets',
			),
			array(
				self::MODE,
				self::APPLICATION_ID,
				self::PROPERTY_SHORT,
				$factory
			)
		);
		$vendor_api->expects($this->once())->method('getStateObjectModel')
			->will($this->returnValue($state_object_model));
		$state_object_model->expects($this->never())->method('save');
		// Call will return the state object
		$vendor_api->expects($this->once())
			->method('call')
			->will($this->returnValue(
				array(
					'outcome' => 1,
					'state_object' => $state_object,
					'doesntreally matter' => TRUE
				)
			));
		$vendor_api->test();
	}
	
	public function testToECashArray()
	{

		$factory = $this->getMock('OLP_Factory', array('getModel', 'getReferenceModel', 'getBlackboxModelFactory'), array(), '', FALSE);
		// Create mocked API object
		$vendor_api = $this->getMock(
			'OLPECash_VendorAPI',
			array(
				'call',
				'getStateObjectModel',
				'storePackets',
			),
			array(
				self::MODE,
				self::APPLICATION_ID,
				self::PROPERTY_SHORT,
				$factory
			)
		);
		
		$data['first_name'] = "FirstName";
		$data['last_name'] = "LastName";
		$data['middle_name'] = "MidName";
		$data['home_street'] = "Home Street";
		$data['home_unit'] = "HomeUnit";
		$data['home_city'] = "HomeCity";
		$data['home_state'] = "HomeState";
		$data['home_zip'] = 12345;
		$data['ext_work'] = 666;
		$data['email_primary'] = "test@tss.tss";
		$data['state_id_number'] = "12345";
		$data['state_issued_id'] = "CA";
		$data['react_app_id'] = 954321;
		$data['income_monthly_net'] = 1400;
		$data['income_type'] = "employment";
		$data['model_name'] = "dwpd";
		$data['social_security_number'] = "123121234";
		$data['client_ip_address'] = "192.168.1.1";
		$data['week_one'] = NULL;
		$data['week_two'] = NULL; 
		$data['day_int_one'] = NULL;
		$data['day_int_two'] = NULL;
		$data['last_pay_date'] = "03/10/2009";
		$data['track_key'] = "fklsjd349kldfjsdf";
		$data['work_title'] = "Boss";
		$data['date_of_hire'] =  "03/10/2009";
		$data['day_string_one'] = "Tue";
		$data['income_direct_deposit'] = "True";
		$data['date_dob_m'] = 3;
		$data['date_dob_d'] = 17;
		$data['date_dob_y'] = 1978;

		
		$data["ref_01_name_full"] = "1Name";
		$data["ref_01_phone_home"] = "1Phone";
		$data["ref_01_relationship"]= "1Rela";		
		
		$data['paydates'][] = "04/01/2009";
		$data['paydates'][] = "04/10/2009";
		$data['paydates'][] = "04/21/2009";
		
		// Vehicle Data
		$data['vehicle_vin'] = "234df45345";
		$data['vehicle_make'] = "Make";
		$data['vehicle_year'] = "1999";
		$data['vehicle_type'] = "Type";
		$data['vehicle_model'] = "Model";
		$data['vehicle_style'] = "Style";
		$data['vehicle_series'] = "Series";
		$data['vehicle_mileage'] = 324013;
		$data['vehicle_license_plate'] = "DM2d3";
		$data['vehicle_color'] = "Red";
		$data['vehicle_value'] = 1000;
		$data['vehicle_title_state'] = "CA";		
		
		
		$result = $vendor_api->toECashArray($data);
		
		$this->assertEquals($data['first_name'], $result['name_first']);
		$this->assertEquals($data['last_name'], $result['name_last']);
		$this->assertEquals($data['middle_name'], $result['name_middle']);
		$this->assertEquals($data['home_street'], $result['street']);
		$this->assertEquals($data['home_unit'], $result['unit']);
		$this->assertEquals($data['home_city'], $result['city']);
		$this->assertEquals($data['home_state'], $result['state']);
		$this->assertEquals($data['home_zip'], $result['zip_code']);
		$this->assertEquals($data['ext_work'], $result['phone_work_ext']);
		$this->assertEquals($data['email_primary'], $result['email']);
		$this->assertEquals($data['state_id_number'], $result['legal_id_number']);
		$this->assertEquals($data['state_issued_id'], $result['legal_id_state']);
		$this->assertEquals($data['react_app_id'], $result['react_application_id']);
		$this->assertEquals($data['income_monthly_net'], $result['income_monthly']);
		$this->assertEquals($data['income_type'], $result['income_source']);
		$this->assertEquals($data['model_name'], $result['paydate_model']);
		$this->assertEquals($data['social_security_number'], $result['ssn']);
		$this->assertEquals($data['client_ip_address'], $result['ip_address']);
		$this->assertEquals($data['week_one'], $result['week_1']);
		$this->assertEquals($data['week_two'], $result['week_2']);
		$this->assertEquals($data['day_int_one'], $result['day_of_month_1']);
		$this->assertEquals($data['day_int_two'], $result['day_of_month_2']);
		$this->assertEquals($data['last_pay_date'], $result['last_paydate']);
		$this->assertEquals($data['track_key'], $result['track_id']);
		$this->assertEquals($data['work_title'], $result['job_title']);
		$this->assertEquals($data['date_of_hire'], $result['date_hire']);
		
				// Vehicle Data
		// Vehicle Data
		$this->assertEquals($data['vehicle_vin'],$result['vin']);
		$this->assertEquals($data['vehicle_make'],$result['make']);
		$this->assertEquals($data['vehicle_year'] ,$result['year']);
		$this->assertEquals($data['vehicle_type'], $result['type']);
		$this->assertEquals($data['vehicle_model'] ,$result['model']);
		$this->assertEquals($data['vehicle_style'] ,$result['style']);
		$this->assertEquals($data['vehicle_series'] ,$result['series']);
		$this->assertEquals($data['vehicle_mileage'] , $result['mileage']);
		$this->assertEquals($data['vehicle_license_plate'] , $result['license_plate']);
		$this->assertEquals($data['vehicle_color'] ,$result['color']);
		$this->assertEquals($data['vehicle_value'] , $result['value']);
		$this->assertEquals($data['vehicle_title_state'] , $result['title_state']);			

		$this->assertEquals($data['day_string_one'] , $result['day_of_week']);	
		
		$this->assertEquals($data['income_direct_deposit'], TRUE);
		
		$this->assertEquals("3/17/1978", $result['dob']);
		
		$ref = $result['personal_reference'][0];		
		$this->assertEquals($data["ref_01_name_full"], $ref['name_full']);
		$this->assertEquals($data["ref_01_phone_home"], $ref['phone_home']);
		$this->assertEquals($data["ref_01_relationship"], $ref['relationship']);
		
		$this->assertEquals($data["paydates"], $result['paydates']);
		
	} 
}

?>
