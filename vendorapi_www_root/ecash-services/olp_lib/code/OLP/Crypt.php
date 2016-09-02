<?php
/**
 * OLP implementation for encryption.
 * 
 * @todo Need to stop using static IV.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLP_Crypt extends Security_Crypt_1
{
	/**
	 * Constructor.
	 *
	 * @param string $key
	 * @param string $iv
	 */
	public function __construct($key, $iv)
	{
		parent::__construct(md5($key));
		$this->setStaticIV($iv);
		$this->setUseStaticIV(TRUE);
	}
	
	/**
	 * Encrypts $data and base64 encodes the data.
	 *
	 * @param string $data
	 * @return string
	 */
	public function encrypt($data)
	{
		if (empty($data))
		{
			return '';
		}
		
		return base64_encode(parent::encrypt($data));
	}
	
	/**
	 * Base64 decodes and decrypts $data.
	 *
	 * @param string $data
	 * @return string
	 */
	public function decrypt($data)
	{
		if (empty($data))
		{
			return '';
		}
		
		return parent::decrypt(base64_decode($data));
	}
}
