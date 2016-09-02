<?php
/**
 * The CryptConfig class is adpated from the Crypt_Config class that used to exist in BFW.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLP_CryptConfig
{
	const IV = '0123456789ABCDEF';
	const CONFIG_KEY = 'Se||ing$0rc3Se||ing$0rc3';
	const KEY_FILE = '/virtualhosts/crypt/key.dat';
	
	/**
	 * The encryption key to use.
	 *
	 * @var string
	 */
	protected $key;
	
	/**
	 * The initialization vector to use for encryption.
	 *
	 * @var string
	 */
	protected $iv;
	
	/**
	 * Returns an array containing the key and IV to use for encryption.
	 *
	 * @return array
	 */
	protected function loadConfig()
	{
		$crypt = $this->buildCryptObject();
		
		$this->key = $crypt->decrypt(base64_decode($this->getFileContents(self::KEY_FILE)));
		$this->iv = self::IV;
	}
	
	/**
	 * Returns the key to use for encryption.
	 *
	 * @return string
	 */
	public function getKey()
	{
		if (!isset($this->key)) $this->loadConfig();
		return $this->key;
	}
	
	/**
	 * Returns the initialization vector to use for encryption.
	 *
	 * @return string
	 */
	public function getIV()
	{
		if (!isset($this->iv)) $this->loadConfig();
		return $this->iv;
	}
	
	/**
	 * Returns the file contents.
	 *
	 * @param string $file
	 * @return string
	 */
	protected function getFileContents($file)
	{
		if (file_exists($file))
		{
			return trim(file_get_contents($file));
		}
		
		throw new InvalidArgumentException("file '$file' does not exist");
	}
	
	/**
	 * Creates a new Security_ICrypt_1 object.
	 *
	 * @return Security_ICrypt_1
	 */
	protected function buildCryptObject()
	{
		$crypt = new Security_Crypt_1(md5(self::CONFIG_KEY));
		$crypt->setStaticIV(self::IV);
		$crypt->setUseStaticIV(TRUE);
		return $crypt;
	}
}
