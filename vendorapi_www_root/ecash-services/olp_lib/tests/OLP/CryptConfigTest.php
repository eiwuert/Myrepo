<?php
class OLP_CryptConfigTest extends OLP_CryptBase
{
	public function setUp()
	{
		$this->key = OLP_CryptConfig::CONFIG_KEY;
		$this->iv = OLP_CryptConfig::IV;
	}
	
	public function testGetKey()
	{
		$key = $this->getRandomString();
		
		$config = $this->getMock('OLP_CryptConfig', array('getFileContents'));
		$config->expects($this->any())
			->method('getFileContents')
			->will($this->returnValue($this->getEncryptedValue($key)));
		
		$this->assertEquals($key, $config->getKey());
	}
	
	public function testGetIV()
	{
		$config = $this->getMock('OLP_CryptConfig', array('getFileContents'));
		$config->expects($this->any())
			->method('getFileContents')
			->will($this->returnValue($this->getEncryptedValue($this->getRandomString())));
		
		$this->assertEquals('0123456789ABCDEF', $config->getIV());
	}
}
