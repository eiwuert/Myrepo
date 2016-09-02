<?php
/**
 * Test cases for OLP_Crypt.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLP_CryptTest extends OLP_CryptBase
{	
	public function testEncryptWithData()
	{
		$clear_data = $this->getRandomString();
		
		$encrypted_data = $this->getEncryptedValue($clear_data);
		
		$crypt = new OLP_Crypt($this->key, $this->iv);
		$this->assertEquals($encrypted_data, $crypt->encrypt($clear_data));
	}
	
	public function testEncryptWithEmptyString()
	{
		$crypt = new OLP_Crypt($this->key, $this->iv);
		$this->assertEquals('', $crypt->encrypt(''));
	}
	
	public function testDecryptWithData()
	{
		$encrypted_data = $this->getRandomEncryptedData();
		$clear_data = $this->getDecryptedValue($encrypted_data);
		
		$crypt = new OLP_Crypt($this->key, $this->iv);
		$this->assertEquals($clear_data, $crypt->decrypt($encrypted_data));
	}
	
	public function testDecryptWithEmptyString()
	{
		$crypt = new OLP_Crypt($this->key, $this->iv);
		$this->assertEquals('', $crypt->decrypt(''));
	}
}
