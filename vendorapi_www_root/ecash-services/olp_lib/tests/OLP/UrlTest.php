<?php

/**
 * Test case for OLP_Url
 * 
 * @author David Watkins <david.watkins@sellingsource.com>
 */
class OLP_UrlTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testBuildUrl
	 *
	 * @return array
	 */
	public static function buildUrlDataProvider()
	{
		return array(
			array(
				'live',
				'sitename.com',
				array(
					'var1' => 'val1',
					'var2' => 'val2',
				),
				array(
					'http' => 'https://',
					'server_name' => '500fastcash.com'
				),
				'https://sitename.com/?var1=val1&var2=val2'
			),
			array(
				'local',
				'sitename.com',
				array(
					'var1' => 'val1',
					'var2' => 'val2',
				),
				array(
					'http' => 'http://',
					'server_name' => 'ent.500fastcash.com.ds01.tss'
				),
				'http://sites.sitename.com.ds01.tss/?var1=val1&var2=val2'
			),
			array(
				'local',
				'usfastcash.com',
				array(
					'var1' => 'val1',
					'var2' => 'val2',
				),
				array(
					'http' => 'http://',
					'server_name' => 'ent.500fastcash.com.ds01.tss'
				),
				'http://ent.usfastcash.com.ds01.tss/?var1=val1&var2=val2'
			),
			array(
				'rc',
				'sitename.com',
				array(
					'var1' => 'val1',
					'var2' => 'val2',
				),
				array(
					'http' => 'http://',
					'server_name' => 'rc.500fastcash.com'
				),
				'http://rc.sitename.com/?var1=val1&var2=val2'
			),
		);
	}
	
	/**
	 * Test buildUrl function.
	 * Overwrites buildHttp and buildLocalHost because they use $_SERVER
	 * 
	 * @dataProvider buildUrlDataProvider
	 *
	 * @param string $mode
	 * @param string $site
	 * @param array $params
	 * @param array $returns
	 * @param string $expects
	 * @return void
	 */
	public function testBuildUrl($mode, $site, $params, $returns, $expects)
	{
		$url_builder = $this->getMock(
			'OLP_Url',
			array('buildHttp', 'buildLocalHostName'),
			array($mode)
		);
		
		$url_builder->expects($this->once())
			->method('buildHttp')
			->will($this->returnValue($returns['http']));
		$_SERVER['SERVER_NAME'] = $returns['server_name'];
		
		$url = $url_builder->buildUrl($site, $params);
		
		$this->assertEquals($expects, $url);
	}
	
	/**
	 * Data provider for testBuildSite
	 *
	 * @return array
	 */
	public static function buildSiteDataProvider()
	{
		return array(
			array(
				'live',
				'',
				'sitename.com',
				'500fastcash.com',
				'sitename.com'
			),
			array(
				'local',
				'',
				'sitename.com',
				'ent.500fastcash.com.ds01.tss',
				'sites.sitename.com.ds01.tss'
			),
			array(
				'local',
				'',
				'usfastcash.com',
				'ent.500fastcash.com.ds01.tss',
				'ent.usfastcash.com.ds01.tss'
			),
			array(
				'rc',
				'',
				'sitename.com',
				'rc.500fastcash.com',
				'rc.sitename.com'
			),
			array(
				'rc',
				'QA_MANUAL',
				'sitename.com',
				'qa.500fastcash.com',
				'qa.sitename.com'
			),
			array(
				'rc',
				'QA_SEMIAUTOMATED',
				'sitename.com',
				'saqa.500fastcash.com',
				'saqa.sitename.com'
			),
			array(
				'live',
				'STAGING',
				'sitename.com',
				'staging.500fastcash.com',
				'staging.sitename.com'
			),
		);
	}
	
	/**
	 * Test buildSite function.
	 * Overwrites buildLocalHost because they use $_SERVER
	 * 
	 * @dataProvider buildSiteDataProvider
	 *
	 * @param string $mode
	 * @param string $application_environment
	 * @param string $site
	 * @param string $localhost
	 * @param string $expects
	 * @return void
	 */
	public function testBuildSite($mode, $application_environment, $site, $server_name, $expects)
	{
		if (!empty($application_environment))
		{
			$_SERVER['APPLICATION_ENVIRONMENT'] = $application_environment;
		}
		
		$url_builder = $this->getMock(
			'OLP_Url',
			array('buildLocalHostName'),
			array($mode)
		);
		$_SERVER['SERVER_NAME'] = $server_name;
		
		$url = $url_builder->buildSite($site);
		
		$this->assertEquals($expects, $url);
	}
	
	/**
	 * Data provider for testBuidQueryString
	 *
	 * @return array
	 */
	public static function buildQueryStringDataProvider()
	{
		return array(
			array(
				array(
					'var1' => 'val1',
					'var2' => 'val2',
				),
				'/?var1=val1&var2=val2'
			),
			array(
				array(),
				''
			),
		);
	}
	
	/**
	 * Tests buildQueryString function
	 * 
	 * @dataProvider buildQueryStringDataProvider
	 *
	 * @param array $params
	 * @param string $expects
	 * @return void
	 */
	public function testBuildQueryString($params, $expects)
	{
		$url_builder = new OLP_Url('local');
		
		$query_string = $url_builder->buildQueryString($params);
		
		$this->assertEquals($expects, $query_string);
	}
	
	/**
	 * Data provider for testBuildLocalHostName
	 *
	 * @return array
	 */
	public static function buildHttpDataProvider()
	{
		return array(
			array(
				NULL,
				'http://'
			),
			array(
				1,
				'https://'
			),
		);
	}
	
	/**
	 * Tests buildLocalHostName function.
	 * 
	 * @dataProvider buildHttpDataProvider
	 *
	 * @param string $http
	 * @param string $expects
	 * @return void
	 */
	public function testBuildHttp($http, $expects)
	{
		if (!is_null($http))
		{
			$_SERVER['HTTPS'] = $http;
		}
		
		$url_builder = new OLP_Url('local');
		
		$local_host_name = $url_builder->buildHttp();
		
		$this->assertEquals($expects, $local_host_name);
	}
}