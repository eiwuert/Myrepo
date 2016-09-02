<?php
/**
 * @package library
 */

require_once('libolution/Security/Crypt.1.php');
require_once('libolution/Util/Convert.1.php');

/**
 * Provides singleton access to the libolution crypt library and forces it to 
 * always use a static IV. Also forces encrypted values to base64ish strings.
 * 
 * The default key and iv can currently be set by defining the ENCRYPTION_KEY 
 * and ENCRYPTION_IV accordingly.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @see http://libdoc.arch.tss/Security/Security_Crypt_1.html
 */
class eCash_Crypt
{
	/**
	 * The key to use if none is specified.
	 */
	const DEFAULT_KEY = ENCRYPTION_KEY;
	
	/**
	 * The IV to use if none is specified.
	 */
	const DEFAULT_STATIC_IV = ENCRYPTION_IV;
	
	/**
	 * @var Security_Crypt_1
	 */
	protected $crypt_object;
	
	/**
	 * Returns a singleton instance of the crypt object.
	 *
	 * @param string $key
	 * @param string $static_iv
	 * @return eCash_Crypt
	 */
	static public function getInstance($key = self::DEFAULT_KEY, $static_iv = self::DEFAULT_STATIC_IV)
	{
		static $crypt_instance;
		
		if (empty($crypt_instance))
		{
			$crypt_instance = new self($key, $static_iv);
		}
		
		return $crypt_instance;
	}
	
	/**
	 * Creates a new crypt object using a key and static iv.
	 *
	 * @param string $key
	 * @param string $static_iv
	 */
	protected function __construct($key, $static_iv)
	{
		$this->crypt_object = $this->getCryptObject($key);
		$this->crypt_object->setUseStaticIV(true);
		$this->crypt_object->setStaticIV($static_iv);
	}
	
	/**
	 * Returns a base64 representation of an encryption of the given data. 
	 *
	 * @param string $data
	 * @return string
	 */
	public function encrypt($data)
	{
		$encrypted_data = $this->crypt_object->encrypt($data);
		
		return Util_Convert_1::bin2String($encrypted_data);
	}
	
	/**
	 * Returns a string decrypted from a given base64 representation of an 
	 * encrypted string.
	 *
	 * @param string $data
	 * @return string
	 */
	public function decrypt($encrypted)
	{
		$binary_encrypted_data = Util_Convert_1::string2Bin($encrypted);
		return $this->crypt_object->decrypt($binary_encrypted_data);
	}
	
	/**
	 * returns a new libolution crypt object.
	 *
	 * @param string $crypt_key
	 * @return Security_Crypt_1
	 */
	private function getCryptObject($crypt_key)
	{
		return new Security_Crypt_1($crypt_key);
	}
}

?>
