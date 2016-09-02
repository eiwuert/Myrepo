<?php 

class Template_ParserTest extends PHPUnit_Framework_TestCase
{
	protected $_provider;
	protected $_parser;
	
	public function setUp()
	{
		$this->_provider = $this->getMock('Template_ITokenProvider');
		$this->_parser = new Template_Parser('%%%', $this->_provider);
	}
	
	public function tearDown()
	{
		
	}
	
	public function testGetTokens()
	{
		$data = "This is a %%%TOKEN1%%%"; 
		$this->_parser->setTemplateData($data);
		$this->assertEquals(array('TOKEN1'), $this->_parser->getTokens());
	}
	
	public function testGetMultipleTokens()
	{
		$data = "This is a %%%TOKEN%%% and this is a %%%ANOTHERTOKEN%%% and this is the first %%%TOKEN%%%";
		$this->_parser->setTemplateData($data);
		$this->assertEquals(array('TOKEN', 'ANOTHERTOKEN', 'TOKEN'), $this->_parser->getTokens());
	}
	
	public function testGetTokensWithId()
	{
		$data = "This is a %%%TOKEN1%%%"; 
		$this->_parser->setTemplateData($data);
		$this->assertEquals(array('%%%TOKEN1%%%'), $this->_parser->getTokens(TRUE));
	}
	
	public function testGetMultipleTokensWithId()
	{
		$data = "This is a %%%TOKEN%%% and this is a %%%ANOTHERTOKEN%%% and this is the first %%%TOKEN%%%";
		$this->_parser->setTemplateData($data);
		$this->assertEquals(array('%%%TOKEN%%%', '%%%ANOTHERTOKEN%%%', '%%%TOKEN%%%'), $this->_parser->getTokens(TRUE));
	}
	
	public function testParseSimpleTemplate()
	{
		$data = "This is a %%%TOKEN%%%";
		$this->_provider->expects($this->at(0))->method('getTokenValue')->with('TOKEN')
			->will($this->returnValue("TOKENVALUE"));
		$str = $this->_parser->parse($data);
		$this->assertEquals("This is a TOKENVALUE", $str);
	}
	
	public function testLongerTemplate()
	{
		$data = "This is a <b>really</b> long template with %%%TOKEN1%%% a couple of different
		tokens and it does lots of stuff. The second token is %%%TOKEN2%%% and then we can
		go back to the first one with %%%TOKEN1%%% and thesecond one will be %%%TOKEN2%%% and I
		really like pizza and token 3 is %%%TOKEN3%%%. We also <span class='ono'>hope</span> that % signs won't break
		things incase someone puts 100% in there or something.";
		$this->_parser->setTemplateData($data);
		$this->_provider->expects($this->at(0))->method('getTokenValue')
			->with('TOKEN1')->will($this->returnValue('TOKEN1VALUE'));
		$this->_provider->expects($this->at(1))->method('getTokenValue')
			->with('TOKEN2')->will($this->returnValue('TOKEN2VALUE'));
		$this->_provider->expects($this->at(2))->method('getTokenValue')
			->with('TOKEN1')->will($this->returnValue('TOKEN1VALUE'));
		$this->_provider->expects($this->at(3))->method('getTokenValue')
			->with('TOKEN2')->will($this->returnValue('TOKEN2VALUE'));
		$this->_provider->expects($this->at(4))->method('getTokenValue')
			->with('TOKEN3')->will($this->returnValue('TOKEN3VALUE'));
		$expected = str_replace(
			array('%%%TOKEN1%%%', '%%%TOKEN2%%%', '%%%TOKEN3%%%'),
			array('TOKEN1VALUE', 'TOKEN2VALUE', 'TOKEN3VALUE'),
			$data
		);
		$this->assertEquals($expected, $this->_parser->parse());
	}
	
}