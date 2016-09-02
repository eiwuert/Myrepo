<?php 

class Template_ArrayTokenProviderTest extends PHPUnit_Framework_TestCase
{
	
	public function testProviderReturnsString()
	{
		$provider = new Template_ArrayTokenProvider(array('mytoken' => 'value'));
		$this->assertEquals('value', $provider->getTokenValue('mytoken'));
	}
	
	public function testUnsetToken()
	{
		$provider = new Template_ArrayTokenProvider(array('mytoken' => 'value'));
		$this->assertEquals(NULL, $provider->getTokenValue('mytoken2'));
	}
}