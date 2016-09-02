<?php
require_once 'olp_lib_setup.php';

/**
 * Test case for the EnterpriseData object.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class EnterpriseDataTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for isCompanyProperty test.
	 *
	 * @return array
	 */
	public static function isCompanyPropertyDataProvider()
	{
		return array(
			// Existing companies, all these should always return TRUE
			array('generic', EnterpriseData::COMPANY_GENERIC, TRUE)
		);
	}
	/**
	 * Test that isCompanyProperty returns the correct values.
	 *
	 * @param string $property the property short to test
	 * @param string $company the company to test
	 * @param bool $expected the expected return
	 * @dataProvider isCompanyPropertyDataProvider
	 * @return void
	 */
	public function testIsCompanyProperty($property, $company, $expected)
	{
		$this->assertEquals($expected, EnterpriseData::isCompanyProperty($company, $property));
	}
	
	/**
	 * Data provider for testSiteIsEnterprise test.
	 *
	 * @return array
	 */
	public static function siteIsEnterpriseDataProvider()
	{
		return array(
			array('usfastcash.com', TRUE),
			array('ameriloan.com', TRUE),
			array('oneclickcash.com', TRUE),
			array('500fastcash.com', TRUE),
			array('unitedcashloans.com', TRUE),
			array('impactcashusa.com', TRUE),
			array('impactsolutiononline.com', TRUE),
			array('impactcashcap.com', TRUE),
			array('cashfirstonline.com', TRUE),
			array('nationalfastcash.com', FALSE)
		);
	}
	
	/**
	 * Tests that siteIsEnterprise returns the correct values.
	 *
	 * @param string $site the site to test
	 * @param bool $expected the expected result
	 * @dataProvider siteIsEnterpriseDataProvider
	 * @return void
	 */
	public function testSiteIsEnterprise($site, $expected)
	{
		$this->assertEquals($expected, EnterpriseData::siteIsEnterprise($site));
	}

	/**
	 * Data provider for siteIsCompany test
	 *
	 * @return array
	 */
	public static function siteIsCompanyDataProvider()
	{
		return array(
			array('clk', 'unitedcashloans.com', TRUE),
			array('clk', 'impactcashusa.com', FALSE),
			array('impact', 'impactcashcap.com', TRUE),
			array('impact', '500fastcash.com', FALSE),
			array('generic', 'someloancompany.com', TRUE),
			array('generic', 'multi-loan-source.com', FALSE),
			array('agean', 'cashbanc.com', TRUE),
			array('agean', 'easycashcrew.com', FALSE),
			array('not_a_real_company', 'usfastcash.com', FALSE),
		);
	}

	/**
	 * Tests that siteIsCompany returns the correct value.
	 *
	 * @param string $company The company to check for
	 * @param string $site The name of the site
	 * @param bool $expected The expected result
	 * @dataProvider siteIsCompanyDataProvider
	 * @return void
	 */
	public function testSiteIsCompany($company, $site, $expected)
	{
		$this->assertEquals($expected, EnterpriseData::siteIsCompany($company, $site));
	}
	
	/**
	 * Data provider for resolveAlias test.
	 *
	 * @return array
	 */
	public static function resolveAliasDataProvider()
	{
		return array(
			array('ic', 'ic'),
			array('ic_t1', 'ic')
		);
	}
	
	/**
	 * Tests the resolveAlias function.
	 *
	 * @param string $property property alias to check
	 * @param string $expected expected property short to get back
	 * @dataProvider resolveAliasDataProvider
	 * @return void
	 */
	public function testResolveAlias($property, $expected)
	{
		$this->assertEquals(strtoupper($expected), EnterpriseData::resolveAlias($property));
	}
	
	/**
	 * Data provider for the getAliasPromo test.
	 *
	 * @return array
	 */
	public static function getAliasPromoDataProvider()
	{
		return array(
			array('ic_t1', 30790),
			array('ca', NULL)
		);
	}
	
	/**
	 * Tests the getAliasPromo function.
	 *
	 * @param string $property the property to test
	 * @param int $expected the expected result
	 * @dataProvider getAliasPromoDataProvider
	 * @return void
	 */
	public function testGetAliasPromo($property, $expected)
	{
		$this->assertEquals($expected, EnterpriseData::getAliasPromo($property));
	}
	
	/**
	 * Tests that isEnteprise works correctly.
	 *
	 * @return void
	 */
	public function testIsEnterprise()
	{
		$this->assertTrue(EnterpriseData::isEnterprise('ufc'));
		$this->assertFalse(EnterpriseData::isEnterprise('cg'));
	}
	
	/**
	 * Tests the getEnterpriseData function.
	 *
	 * @return void
	 */
	public function testGetEnterpriseData()
	{
		$expected_array = array(
			"site_name" => "someloancompany.com", 
			"license" => array (
							'LIVE' => 'somelicensekey',
							'RC' => 'somelicensekey',
							'LOCAL' => 'somelicensekey',
						),
			"legal_entity" => "someloancompany.com",
			"fax" => "877-000-0000",
			"db_type" => "mysql",
			"phone"=>"800-000-0000",
			'use_verify_queue',
			'new_ent' => FALSE,
			'use_soap' => FALSE,
			'property_short' => 'GENEIC',
			'use_cfe' => TRUE
		);
		
		$this->assertEquals($expected_array, EnterpriseData::getEnterpriseData('generic'));
	}
	
	/**
	 * Tests the getCompanyData function.
	 *
	 * @return void
	 */
	public function testGetCompanyData()
	{
		$expected_array = array(
			'GENERIC' => array(
				"site_name" => "someloancompany.com",
				"license" => array (
								'LIVE' => 'somelicensekey',
								'RC' => 'somelicensekey',
								'LOCAL' => 'somelicensekey',
							),
				"legal_entity" => "Some Loan Company",
				"fax" => "800-00-0000",
				"db_type" => "mysql",
				"phone"=>"800-000-0000",
				'use_verify_queue',
				'new_ent' => FALSE,
				'use_soap' => FALSE,
				'property_short' => 'GENERIC',
				'use_cfe' => TRUE,
			)
		);
		
		$this->assertEquals($expected_array, EnterpriseData::getCompanyData(EnterpriseData::COMPANY_IMPACT));
	}
	
	/**
	 * Tests the getEnterpriseData function.
	 *
	 * @return void
	 */
	public function testGetEnterpriseOption()
	{
		$this->assertEquals('Cash First LLC', EnterpriseData::getEnterpriseOption('ICF', 'legal_entity'));
	}
	
	/**
	 * Tests the getProperty function.
	 *
	 * @return void
	 */
	public function testGetProperty()
	{
		$this->assertEquals('UFC', EnterpriseData::getProperty('usfastcash.com'));
	}
	
	/**
	 * Tests the getCompany function.
	 *
	 * @return void
	 */
	public function testGetCompany()
	{
		$this->assertEquals('clk', EnterpriseData::getCompany('UFC'));
		$this->assertEquals(EnterpriseData::COMPANY_CLK, EnterpriseData::getCompany('UFC'));
		
		$this->assertEquals('impact', EnterpriseData::getCompany('IC'));
		$this->assertEquals(EnterpriseData::COMPANY_IMPACT, EnterpriseData::getCompany('IC'));
	}
	
	/**
	 * Tests the getLicenseKey function.
	 *
	 * @return void
	 */
	public function testGetLicenseKey()
	{
		$this->assertEquals('d386ac4380073ed7d193e350851fe34f', EnterpriseData::getLicenseKey('ucl', 'LIVE'));
	}
	
	/**
	 * Tests the getAllProperties function.
	 * 
	 * This does not test that all the values are returned, just that Ameriloan is in the list.
	 * 
	 * @return void
	 */
	public function testGetAllProperties()
	{
		$this->assertContains('CA', EnterpriseData::getAllProperties());
	}
	
	/**
	 * Tests that we get back the PCL property in the list of properties returned from getEntPropList.
	 *
	 * @return void
	 */
	public function testGetEntPropList()
	{
		$expected_array = array(
			'site_name' => 'oneclickcash.com',
			'license' => array (
						'LIVE' => '1f1baa5b8edac74eb4eaa329f14a03619f025e2000e0a7b26429af2395f847ce',
						'RC' => '1f1baa5b8edac74eb4eaa329f14a03610a2177d7a01cd1a59258c95fdb31f87b',
						'LOCAL' => '1f1baa5b8edac74eb4eaa329f14a0361604521a4b54937ed3385eb0b5e274b2a'
						),
			'legal_entity' => 'One Click Cash',
			'fax' => '8008039136',
			'phone' => '800-230-3266',
			'cs_phone' => '800-230-3266',
			'cs_fax' => '800-803-9136',
			'db_type' => 'mysql',
			'use_verify_queue',
			'new_ent' => TRUE,
			'use_soap' => FALSE,
			'property_short' => 'PCL',
			'ctc_promo_id' => '29704',
			'egc_promo_id' => '29703',
			'use_cfe' => FALSE,
		);
		$this->assertContains($expected_array, EnterpriseData::getEntPropList());
	}
	
	/**
	 * Tests that we get UCL back form the getEntPrpoShortList.
	 *
	 * @return void
	 */
	public function testGetEntPropShortList()
	{
		$this->assertContains('UCL', EnterpriseData::getEntPropShortList());
	}

	public static function cfeDataProvider()
	{
		return array(
			array(TRUE, 'generic'),
			array(FALSE, 'pcl'),
			array(TRUE, 'IC'),
			array(FALSE, 'bi2'),
		);
	}
    /** 
     * Test that isCFE returns correct v alues.
     *
     * @param bool $expected the expected return
	 * @param string $prop_short
     * @dataProvider cfeDataProvider
     * @return void
     */
	public function testIsCFE($expected, $prop_short)
	{
		$this->assertEquals($expected, EnterpriseData::isCFE($prop_short));
	}
}
?>
