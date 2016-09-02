<?php


/**
 * Implements an interface to the Dupe_Bloom_Singleton API.
 *
 * @desc Gforge [#3395] BBx - Check Giant - Dup Check
 * @author Tym Feindel
 *
 **/
class Dupe_Bloom_Singleton
{

	private static $instance;
	private static $file_path1;
	private static $file_path2;
	private static $bloom_instance;

	/**
	 * Constructor. Private so that no one can directly
	 * instantiate this object without using Get_Instance().
	 */
	private function __construct()
	{
		// provide the main path and an alternate, must match cronjob blackbox/cg_get_remote.php

		$file_path1 = "/virtualhosts/bloom_files/working.bloom";
		$file_path2 = "";
		$this->bloom_instance = new BloomFilter($file_path1);
	}

	/**
	 * Overrides the clone object. Private so that no one can clone this object.
	 *
	 */
	private function __clone() {}

	/**
	 * Returns an instance of the Dupe_Bloom_Singleton class.
	 *
	 * @return object
	 */
	public static function Get_Instance()
	{
		if(!isset(self::$instance))
		{
			self::$instance = new Dupe_Bloom_Singleton();
		}
		return self::$instance;
	}

	/**
	 *
	 *
	 * @param string $hydrangea search string prefix
	 * @param string $orchid search string postfix
	 * @return boolean TRUE if the data exists in the bloom, false otherwise
	 */
	public function in_bloom($hydrangea,$orchid)
	{
		$hybrid = strtolower(trim($hydrangea).trim($orchid));
		return $this->bloom_instance->includes($hybrid);
	}

	/**
	 * Look for the email/ssn in the bloom filter file
	 *
	 * @param string $email email
	 * @param string $ssn ssn (numbers only)
	 * @return boolean TRUE if the data exists in the CG data, false otherwise
	 */
	public function is_dupe($email,$ssn)
	{
		$testval = strtolower(trim($email).trim($ssn));
		return $this->bloom_instance->includes($testval);
	}
}

/*
 * [#3395] BBx - Check Giant - Dup Check
 *
 * @desc internal class that defines interaction with the bloom file
 */
class BloomFilter
{
	private $hashbits;
	private $hashKeys = array(0,0,0);
	private $fileName;
	private $vector_size = 0;

	public function __construct($fn)
	{
		$this->fileName = $fn;
	}


	private function hashString($s)
	{
		$workHashCode = 0;
		for ($i = 0; $i < strlen($s); $i++)
		$workHashCode = ((($workHashCode << ($i % 4)) + ord($s[$i])) % (pow(2,28)));
		return $workHashCode;
	}

	private function encodeString($s)
	{
		return $this->hashString(md5($s));
	}

	private function createHashes($str)
	{
		$h1 = $this->encodeString($str);
		$h2 = $this->hashString($str);


		$this->hashKeys[0] = ($h1 % ($this->vector_size));
		$this->hashKeys[1] = (($h1 + $h2) % ($this->vector_size));
		$this->hashKeys[2] = (($h1 + 2*$h2) % ($this->vector_size));
		return $this->hashKeys;
	}

	public function includes($str)
	{
		$fp = @fopen($this->fileName, "r");
		
		if(!$fp){
			//return false on fail
			return false;
		}
		$this->vector_size = filesize($this->fileName)*8;


		$this->createHashes($str);
		foreach($this->hashKeys as $hash)
		{
			fseek($fp, floor($hash / 8)-(floor($hash/8)%4)); //seek to the word that would represent the byte we want
			$word_array = str_split(fread($fp,4)); // get the word we want
			$word_num = ($hash % 32) >> 3;
			$bit_num = ($hash % 8);
			$byte = ord($word_array[$word_num]) & (1 << $bit_num);
			//parse the get the appropriate bit out of the byte
			//return false if bit didnt match
			if (!$byte)
			{
				fclose($fp);
				return false;
			}
		}
		fclose($fp);
		return true;
	}
}
?>
