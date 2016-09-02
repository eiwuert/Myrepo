<?php
/**
 * Adds crypt processing functionality to the writable model.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLP_Models_CryptWritableModel extends OLP_Models_WritableModel
{
	/**
	 * Encrypt process
	 */
	const PROCESS_ENCRYPT = 'processEncrypt';
	
	/**
	 * @var Security_ICrypt_1
	 */
	protected $crypt_object;
	
	/**
	 * Allow us to receive a different crypt object to handle encryption/decryption.
	 *
	 * @param DB_IConnection_1 $db
	 * @param Security_ICrypt_1 $crypt_object
	 */
	public function __construct(DB_IConnection_1 $db, Security_ICrypt_1 $crypt_object)
	{
		parent::__construct($db);
		
		$this->crypt_object = $crypt_object;
	}
	
	/** Sets up our crypt object.
	 *
	 * @return Security_ICrypt_1
	 */
	protected function getCryptObject()
	{
		if ($this->crypt_object === NULL)
		{
			$crypt_config = new OLP_CryptConfig();
			$this->crypt_object = new OLP_Crypt($crypt_config->getKey(), $crypt_config->getIV());
		}
		
		return $this->crypt_object;
	}
	
	/** Encrypt or decrypt a value.
	 *
	 * @param string $data
	 * @param int $processing_mode
	 * @return string
	 */
	public function processEncrypt($data, $processing_mode)
	{
		$result = NULL;
		
		switch ($processing_mode)
		{
			case self::PROCESSMODE_APPLY:
				$result = $this->getCryptObject()->encrypt($data);
				break;
			case self::PROCESSMODE_REMOVE:
				$result = $this->getCryptObject()->decrypt($data);
				break;
		}
		
		return $result;
	}
}
