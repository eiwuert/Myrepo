<?php
require_once('libolution/Object.1.php');
require_once('libolution/Security/ICrypt.1.php');
require_once('libolution/Security/Crypt.1.php');

/**
 * A wrapper around libolutions Security_Crypt_1 class
 * to support singletons and a few other things that 
 * condor uses that are non-standard.
 *
 */
class Condor_Crypt 
{
	//const FILE_KEY = 'T}{3/^\/^\[]5+53[3R3KeY3v@r';
	const FILE_KEY = 'Se||ing$0rc3Se||ing$0rc3';
	protected static $instance = array();
	
	/**
	 * Sets up an instance or returns an existing 
	 * instance of Condor_Crypt depending on the
	 * crypt key
	 *
	 * @param mixed $key
	 * @return Condor_Crypt
	 */
	public static function singleton($key)
	{
		if(!isset(self::$instance[$key]) || !self::$instance[$key] instanceof Security_Crypt_1)
		{
			self::$instance[$key] = new Security_Crypt_1($key);
		}
		return self::$instance[$key];
	}
	
	/**
	 * Figures out the key by the mode, and returns
	 * a Condor_Crypt object using that key.
	 *
	 * @param string $mode
	 */
	public static function instanceByMode($mode = EXECUTION_MODE)
	{
		$key = self::Get_Key($mode);
		$obj = false;
		if($key !== false)
		{
			$obj = self::singleton($key);
		}
		return $obj;
	}
	
	/**
	 * Returns an encrypted version of the string, using
	 * the key associated with $mode
	 *
	 * @param string $data
	 * @param string $mode
	 * @return string
	 */
	public static function Encrypt($data, $mode = EXECUTION_MODE)
	{
		$crypt = self::instanceByMode($mode);
		return $crypt->Encrypt($data);
	}
	
	/**
	 * Returns a decrypted version of $data
	 *
	 * @param string $data
	 * @param string $mode
	 * @return string
	 */
	public static function Decrypt($data, $mode = EXECUTION_MODE)
	{
		$crypt = self::instanceByMode($mode);
		return $crypt->decrypt($data);
	}
	
	/**
	 * Returns the file_name where the database key is 
	 * located based on the mode
	 *
	 * @param string $mode
	 * @return string
	 */
	public static function Get_Key_File($mode)
	{
		switch($mode)
		{
			case MODE_LIVE: 
				$file = '/virtualhosts/crypt/condor_key.dat';
				break;
			default:
			case MODE_DEV:
			case MODE_RC:
				$file = CONDOR_DIR.'key.dat';
				break;
		}
		return $file;
	}
	
	/**
	 * Returns the key based on the file mode thing.
	 *
	 * @return string
	 */
	public static function Get_Key($mode = EXECUTION_MODE)
	{
		$file_name = self::Get_Key_File($mode);
		$key = false;
		if(file_exists($file_name))
		{	
			$crypt = self::singleton(self::FILE_KEY);
			$key = $crypt->decrypt(file_get_contents($file_name));
		}
		return $key;
	}
}
