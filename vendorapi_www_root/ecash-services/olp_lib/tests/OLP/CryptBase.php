<?php
abstract class OLP_CryptBase extends PHPUnit_Framework_TestCase
{
	/**
	 * The key to use for the test.
	 *
	 * @var string
	 */
	protected $key;
	
	/**
	 * The IV to use for the test.
	 *
	 * @var string
	 */
	protected $iv;
	
	/**
	 * Sets up the test.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->key = $this->getRandomString(8);
		$this->iv = $this->getRandomString(16);
	}
	
	/**
	 * Returns the base64 encoded, encrypted value of $clear_data.
	 *
	 * @param string $clear_data
	 * @return string
	 */
	protected function getEncryptedValue($clear_data)
	{
		$mcrypt_module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, NULL, MCRYPT_MODE_CFB, NULL);
		mcrypt_generic_init($mcrypt_module, md5($this->key), $this->iv);
		
		$encrypted_data = mcrypt_generic($mcrypt_module, $clear_data);
		
		mcrypt_generic_deinit($mcrypt_module);
		mcrypt_module_close($mcrypt_module);
		
		return base64_encode($encrypted_data);
	}
	
	/**
	 * Returns the decrypted, base64 decoded value of $encrypted_data.
	 *
	 * @param string $encrypted_data
	 * @return string
	 */
	protected function getDecryptedValue($encrypted_data)
	{
		$mcrypt_module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, NULL, MCRYPT_MODE_CFB, NULL);
		mcrypt_generic_init($mcrypt_module, md5($this->key), $this->iv);
		
		$clear_data = mdecrypt_generic($mcrypt_module, base64_decode($encrypted_data));
		
		mcrypt_generic_deinit($mcrypt_module);
		mcrypt_module_close($mcrypt_module);
		
		return $clear_data;
	}
	
	/**
	 * Returns random encrypted data.
	 *
	 * @return string
	 */
	protected function getRandomEncryptedData()
	{
		return $this->getEncryptedValue($this->getRandomString());
	}
	
	/**
	 * Returns a random string with length $length.
	 *
	 * @param int $length
	 * @return string
	 */
	protected function getRandomString($length = NULL)
	{
		$code = md5(uniqid(mt_rand(), TRUE));
		
		if ($length != NULL)
		{
			return substr($code, 0, $length);
		}
		
		return $code;
	}
}
