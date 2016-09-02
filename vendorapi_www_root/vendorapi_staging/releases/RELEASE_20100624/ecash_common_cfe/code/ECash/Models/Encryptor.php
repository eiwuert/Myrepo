<?php

/**
 * Description of Encryptor
 *
 * @copyright Copyright 2009 The Selling Source, Inc.
 * @package Models
 * @author Bill Szerdy <bill.szerdy@sellingsource.com>
 * @created Mar 26, 2009
 */
class ECash_Models_Encryptor
{
	// these are strictly for testing and will change
	const IV = "06f343d684f37110695178d35c126121";

	/**
	 *
	 * @var Security_Crypt_1
	 */
	private $crypt;

	/**
	 *
	 * @var DB_IConnection_1
	 */
	private $db;
	
	/**
	 *
	 * @var string
	 */
	private $key_file;

	/**
	 * Object creation
	 *
	 * @param DB_IConnection_1 $db
	 */
	public function __construct(DB_IConnection_1 $db = NULL, $fs_key_path = NULL, $iv = NULL)
	{
		$this->db 		= ($db == NULL) ? ECash::getMasterDb() : $db;
		$this->key_file = ($fs_key_path == NULL) ? ECash::getConfig()->FILE_SYSTEM_KEY_PATH : $fs_key_path;
		$static_iv = ($iv == NULL) ? self::IV : $iv;
		$this->crypt = new Security_Crypt_1(NULL);
		$this->crypt->setCipher(Security_Crypt_1::CIPHER_256_BIT);
		
		/**
		 * We may need to pad the IV to the appropriate size 
		 * required for the type of encryption we're using.
		 */
		$iv_size = $this->crypt->getIVSize();
		$static_iv = $this->padIV($static_iv, $iv_size);
		
		$this->crypt->setStaticIV($static_iv);
		$this->crypt->setUseStaticIV(TRUE);
	}

	/**
	 * Object destruction
	 */
	public function __destruct()
	{
		$this->crypt = NULL;
	}

	/**
	 * Returns the input data encrypted with the current version of the key and
	 * base64 encrypted.
	 *
	 * @param mixed $data
	 * @return The $data encrypted
	 */
	public function encrypt($data, $key_version = NULL)
	{
		// If there's no keys to use, and key version passed in is null, go ahead and just return the data
		if (!$this->getLatestEncryptionKeyVersion() || $key_version == NULL)
			return $data;

		$clear_key = $this->getKeyOfVersion($key_version);

		// encrypt the data
		$this->crypt->setCryptKey($clear_key);
		$encrypted_data = $this->crypt->encrypt($data);
		$encrypted_data = base64_encode($encrypted_data);
		return $encrypted_data;
	}


	/**
	 * Returns the data encrypted with all keys for use with a IN sql statement during key switchover processes
	 *
	 * @param mixed $data
	 * @return array The encrypted data
	 */
	public function encryptWithAllActiveKeys($data)
	{
		$encryption_keys = ECash::getFactory()->getModel('EncryptionKeyList');
		$encryption_keys->loadBy(array('active_status' => 'active'));
		$retval = array();	
		$retval[] = $data;

		// Loop through the encryption keys, decrypting the actual key, then encrypting and base64ing the data
		foreach ($encryption_keys as $ek)
		{
			$crypted_key = $ek->key_data;
			$fs_key      = $this->getFileSystemKey($ek->fs_key_id);
			$this->crypt->setCryptKey($fs_key);
			$enc_key     = $this->crypt->decrypt($crypted_key);

			$this->crypt->setCryptKey($enc_key);
			$retval[]    = base64_encode($this->crypt->encrypt($data));
		}

		return $retval;
	}

	/**
	 * Returns the data encrypted with all keys for use with a IN sql statement during key switchover processes
	 * admittedly this is horrible, but I did not have time to refactor every single plain sql query into a model
	 *
	 * @param mixed $data
	 * @return string a SQL statement which can be used in an IN statement to contain all values
	 */
	public function generateInBlock($data)
	{
		$values = $this->encryptWithAllActiveKeys($data);
		
		$statement = "(";

		foreach ($values as $value)
		{
			$statement .= $this->db->quote($value) . ",";
		}

		$statement = substr($statement, 0, strlen($statement)-1) . ")";

		return $statement;
	}

	/**
	 * Returns the data encrypted with all keys for use with a IN sql statement during key switchover processes
	 * admittedly this is horrible, but I did not have time to refactor every single plain sql query into a model
	 *
	 * @param mixed $data
	 * @return string a SQL statement which can be used in an IN statement to contain all values
	 */
	public function generatePreparedInBlock($data)
	{
		$values = $this->encryptWithAllActiveKeys($data);
		
		$statement = "(";

		foreach ($values as $value)
		{
			$statement .= "?,";
		}

		$statement = substr($statement, 0, strlen($statement)-1) . ")";

		return $statement;
	}



	/**
	 * Returns the data decrypted
	 *
	 * @param mixed $data
	 * @param string $key_version
	 * @return The decrypted data
	 */
	public function decrypt($data, $key_version = NULL)
	{
		// If there's no keys to use, and key version passed in is null, go ahead and just return the data
		if (!$this->getLatestEncryptionKeyVersion() || $key_version == NULL)
			return $data;

		// get the encryption key from the database
		$clear_key = $this->getKeyOfVersion($key_version);

		// decrypt the data
		$this->crypt->setCryptKey($clear_key);
		$data = base64_decode($data);
		$clear_data = $this->crypt->decrypt($data);

		return $clear_data;
	}

	/**
	 * Creates a new encryption key and places it into the database, returning
	 *  the last inserted id as the version number.
	 *
	 * @param string $data
	 * @return int
	 */
	public function createNewEncryptionKey($key = NULL, $fs_key_id = NULL)
	{
		if ($fs_key_id == NULL)
			throw new Exception('Filesystem key version not provided');

		$retval = 0;

		// if a key is not provided, create one.
		$key = ($key == NULL) ? md5(rand()) : $key;

		// get the file system key and encrypt the new key		
		$fs_key = $this->getFileSystemKey($fs_key_id);
		$this->crypt->setCryptKey($fs_key);
		$enc_data = $this->crypt->encrypt($key);
		$enc_data = base64_encode($enc_data);

		// insert the new key into the database
		$query = "
			INSERT INTO
				encryption_key (
					date_modified,
					date_created,
					key_data,
					fs_key_id)
			VALUES (
				NOW(),
				NOW(),
				?,
				?);
		";

		$st = $this->db->prepare($query);
		$this->db->beginTransaction();
		$st->execute(array($enc_data, $fs_key_id));
		$retval = $this->db->lastInsertId();
		$this->db->commit();

		return $retval;
	}

	/**
	 * Retrieves the most recent encryption key from the database and returns
	 *  it in the clear.
	 *
	 * @return string
	 */
	public function getLatestEncryptionKeyVersion()
	{
		// retrieve the latest version number of the database encryption key
		$query = "
			SELECT
				MAX(encryption_key_id) AS version
			FROM
				encryption_key
		";

		$st = $this->db->query($query);

		$latest = $st->fetch(DB_IStatement_1::FETCH_OBJ);

		if (!$latest)
		{
			return NULL;
		}

		return $latest->version;
	}

	/**
	 * Retrieves the encryption key for the given version and returns it
	 *  in the clear.
	 * 
	 * @param integer $key_version
	 * @return string
	 */
	private function getKeyOfVersion($key_version = NULL)
	{
		$key_version = ($key_version == NULL) ? $this->getLatestEncryptionKeyVersion() : $key_version;

		$query = "
			SELECT
				key_data,
				fs_key_id
			FROM
				encryption_key
			WHERE
				encryption_key_id = ?;
		";

		$st = $this->db->prepare($query);
		$st->execute(array($key_version));

		$latest = $st->fetch(DB_IStatement_1::FETCH_OBJ);

		if (!$latest)
		{
			throw new Exception('Encryption key was not retrieved from the database.');
		}

		$enc_key = $latest->key_data;

		// decrypt the database key
		$this->crypt->setCryptKey($this->getFileSystemKey($latest->fs_key_id));
		$clear_key = $this->crypt->decrypt($enc_key);

		return $clear_key;
	}

	/**
	 * Retrieves the encryption key from the file system
	 * 
	 * @return string
	 */
	private function getFileSystemKey($fs_key_id = 1)
	{
		if (!file_exists($this->key_file . '.' . $fs_key_id))
		{
			throw new Exception('Failed to retrieve the encryption key from the file system.');
		}

		$key = file_get_contents($this->key_file . '.' . $fs_key_id);
		$key = trim($key);

		if (!$key)
		{
			throw new Exception('Key is asleep');
		}

		return $key;
	}
	
	
	/**
	 * Pad's the IV with zeros to the required length
	 *
	 * @param string $iv
	 * @param integer $size
	 */
	private function padIV($iv, $size = 32)
	{
		$format = "%-0{$size}s";
		$iv = sprintf($format, $iv);
		return $iv;	
	}
}

?>
