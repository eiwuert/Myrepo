<?php

/**
 * [#3395] BBx - Check Giant - Dup Check
 * 
 * Handles bloom files from CashNet.
 * 
 * @author Check Giant <Some.Dude@cashnet.com>
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class BloomFilter_CashNet
{
	/**
	 * Directory where bloom files are held in /virtualhosts
	 */
	const BLOOM_DIR = 'olp_data';
	
	/**
	 * Name of the file to create in BLOOM_DIR
	 */
	const DUPE_FILE = 'cashnet_dupe.bloom';
	
	/**
	 * Path to the bloom file.
	 *
	 * @var string
	 */
	private $filename;

	/**
	 * Keys to create the hash
	 *
	 * @var array
	 */
	private $hash_keys = array(0, 0, 0);
	
	/**
	 * Vector size for the bloom filter
	 *
	 * @var int
	 */
	private $vector_size = 0;
	
	/**
	 * Array of instances of this class.
	 *
	 * @var array
	 */
	private static $instances;

	/**
	 * Constructor
	 *
	 * @param string $fn Filename
	 */
	public function __construct($fn)
	{
		$this->filename = $fn;
	}

	/**
	 * Returns an instance of this class.
	 *
	 * @param string $file_path If no string is passed, it will default to using
	 * 	the path to the dupe bloom file
	 * @return BloomFilter_CashNet
	 */
	public static function getInstance($file_path = NULL)
	{
		if ($file_path === NULL)
		{
			$file_path = self::getDupeFilePath();
		}
		
		if (!isset(self::$instances[$file_path]))
		{
			self::$instances[$file_path] = new self($file_path);
		}

		return self::$instances[$file_path];
	}
	
	/**
	 * Returns a path to the dupe bloom file for CashNet.
	 * 
	 * @return string
	 */
	public static function getDupeFilePath()
	{
		return sprintf(
			'/virtualhosts/%s/%s',
			self::BLOOM_DIR,
			self::DUPE_FILE
		); 
	}
	
	/**
	 * Look for the matching parts in the bloom filter file
	 *
	 * @param string $part1 First part to look for
	 * @param string $part2 Second part to look for
	 * @return bool TRUE if the data exists in the CG data, false otherwise
	 */
	public function exists($part1, $part2)
	{
		$bloom_key = strtolower(trim($part1) . trim($part2));
		return $this->includes($bloom_key);
	}

	/**
	 * Generates a hash string based on a string
	 *
	 * @param string $s String
	 * @return string The hash string
	 */
	private function hashString($s)
	{
		$work_hash_code = 0;
		for ($i = 0; $i < strlen($s); $i++)
		{
			$work_hash_code = ((($work_hash_code << ($i % 4)) + ord($s[$i])) % (pow(2, 28)));
		}

		return $work_hash_code;
	}

	/**
	 * Encodes a string
	 *
	 * @param string $s String
	 * @return string The encoded string
	 */
	private function encodeString($s)
	{
		return $this->hashString(md5($s));
	}

	/**
	 * Creates hashes based on a string
	 *
	 * @param string $str String to create hashes from
	 * @return array Array of hash keys
	 */
	private function createHashes($str)
	{
		$h1 = $this->encodeString($str);
		$h2 = $this->hashString($str);


		$this->hash_keys[0] = ($h1 % ($this->vector_size));
		$this->hash_keys[1] = (($h1 + $h2) % ($this->vector_size));
		$this->hash_keys[2] = (($h1 + 2*$h2) % ($this->vector_size));
		return $this->hash_keys;
	}

	/**
	 * Tests to see if the given string is in the bloom filter
	 *
	 * @param string $str String to test
	 * @return bool TRUE if it's found
	 */
	public function includes($str)
	{
		$fp = @fopen($this->filename, 'r');
		
		if (!$fp)
		{
			//return false on fail
			return FALSE;
		}

		$this->vector_size = filesize($this->filename) * 8;

		$this->createHashes($str);
		foreach ($this->hash_keys as $hash)
		{
			fseek($fp, floor($hash / 8) - (floor($hash / 8) % 4)); //seek to the word that would represent the byte we want
			$word_array = str_split(fread($fp, 4)); // get the word we want
			$word_num = ($hash % 32) >> 3;
			$bit_num = ($hash % 8);
			$byte = ord($word_array[$word_num]) & (1 << $bit_num);

			//parse the get the appropriate bit out of the byte
			//return false if bit didnt match
			if (!$byte)
			{
				fclose($fp);
				return FALSE;
			}
		}
		
		fclose($fp);
		return TRUE;
	}
}

?>
