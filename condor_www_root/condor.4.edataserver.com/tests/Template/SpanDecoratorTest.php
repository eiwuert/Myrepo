<?php 

class Template_SpanDecoratorTest extends PHPUnit_Framework_TestCase
{
	public function testSingleToken()
	{
		$provider = $this->getMock('Template_ITokenProvider');
		$provider->expects($this->once())->method('getTokenValue')
			->with('token')->will($this->returnValue('value'));
		$decorator = new Template_SpanDecorator($provider);
		$this->assertEquals("<span class=\"token token1\">value</span>",	$decorator->getTokenValue('token'));	
	}
	
	public function testMultipleTokens()
	{
		$tokens = array('token1', 'token2', 'token1');
		$provider = $this->getMock('Template_ITokenProvider');
		$provider->expects($this->at(0))->method('getTokenValue')
			->with('token1')->will($this->returnValue('value1'));
		$provider->expects($this->at(1))->method('getTokenValue')
			->with('token2')->will($this->returnValue('value2'));
		$provider->expects($this->at(2))->method('getTokenValue')
			->with('token1')->will($this->returnValue('value1'));
		$decorator = new Template_SpanDecorator($provider);
		$this->assertEquals("<span class=\"token1 token11\">value1</span>",	$decorator->getTokenValue('token1'));
		$this->assertEquals("<span class=\"token2 token21\">value2</span>",	$decorator->getTokenValue('token2'));
		$this->assertEquals("<span class=\"token1 token12\">value1</span>",	$decorator->getTokenValue('token1'));	
	}
}