<?php
	/**
	 * @package Security
	 */

	require_once 'libolution/Object.1.php';
	require_once 'libolution/Security/ICrypt.1.php';

	/**
	 * Class for encrypting and decrypting binary data.
	 * Encryption methods return raw binary data, and decryption methods
	 * expect raw binary data.
	 *
	 * If you wish to get a base-n representation of encrypted data, see
	 * Util_Convert_1.
	 *
	 * Example using Util_Convert_1 to produce compact readable strings.
	 * This is the same type of encoding used in statpro for tracks and spaces.
	 * It is the preferred method of storage, as it combines printable characters
	 * with a (somewhat) compact storage.  There is a 33% growth cost, whereas hex
	 * has a 100% growth cost.
	 *
	 *
	 * <code>
	 * require_once('libolution/AutoLoad.1.php');
	 *
	 * $source = "123456789";
	 *
	 * $key = "My crypt key.";
	 * $crypt = new Security_Crypt_1($key);
	 *
	 * $enc = $crypt->encrypt($source);
	 * $enc_hex = Util_Convert_1::bin2Hex($enc);
	 * $enc_b64 = Util_Convert_1::bin2String($enc);
	 *
	 * $dec = $crypt->decrypt($enc);
	 *
	 * echo "Source: $source (".strlen($source).")\n";
	 * echo "Key: $key (".strlen($key).")\n";
	 * echo "Encrypted (hex): $enc_hex (".strlen($enc_hex).")\n";
	 * echo "Encrypted (b64-ish): $enc_b64 (".strlen($enc_b64).")\n";
	 * echo "Decrypted: $dec (".strlen($dec).")\n";
	 * </code>
	 *
	 * The output:
	 *
	 * <code>
	 * Source: 123456789 (9)
	 * Key: My crypt key. (13)
	 * Encrypted (hex): dd730c2f7d3e15d17c17237f656df8119fb286acd46d471369 (50)
	 * Encrypted (b64-ish): tf73LQDfl4dvncOvBR6,hYFI6OaRJtQ4F1 (34)
	 * Decrypted: 123456789 (9)
	 * </code>
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class Security_Crypt_1 extends Object_1 implements Security_ICrypt_1
	{
		/**
		 * @var string
		 */
		protected $crypt_key;

		/**
		 * @var string
		 */
		protected $static_iv;

		/**
		 * @var bool
		 */
		protected $use_static_iv;

		/**
		 * @var resource
		 */
		protected $mcrypt_module;

		/**
		 * @var int
		 */
		protected $iv_size;

		/**
		 * @var int
		 */
		protected $key_size;

		/**
		 * @var bool
		 */
		protected $module_init = FALSE;

		/**
		 * @var int
		 */
		protected $cipher = self::CIPHER_128_BIT;

		/**
		 * 128-bit encryption cipher. (AES-128)
		 *
		 */
		const CIPHER_128_BIT = 1;

		/**
		 * 192-bit encryption cipher. (AES-192)
		 *
		 */
		const CIPHER_192_BIT = 2;

		/**
		 * 256-bit encryption cipher. (AES-256)
		 *
		 */
		const CIPHER_256_BIT = 3;

		/**
		 * @return string
		 */
		public function getCryptKey()
		{
			return $this->crypt_key;
		}

		/**
		 * @param string $value
		 * @return void
		 */
		public function setCryptKey($value)
		{
			if (strlen($value) > $this->key_size)
			{
				throw new Exception("Supplied crypt key is too large.  Expected {$this->key_size} max, got ".strlen($value));
			}
			$this->crypt_key = $value;
		}

		/**
		 * @param string $value
		 * @return void
		 */
		public function setStaticIV($value)
		{
			if (strlen($value) !== $this->iv_size)
			{
				throw new Exception("Supplied initialization vector is not proper length. Expected {$this->iv_size}, got ".strlen($value));
			}
			$this->static_iv = $value;
		}

		/**
		 * @return string
		 */
		public function getStaticIV()
		{
			return $this->static_iv;
		}

		/**
		 * @param bool $value
		 * @return void
		 */
		public function setUseStaticIV($value)
		{
			$this->use_static_iv = $value;
		}

		/**
		 * @return bool
		 */
		public function getUseStaticIV()
		{
			return $this->use_static_iv;
		}

		/**
		 * Returns the IV size
		 * @return int
		 */
		public function getIVSize()
		{
			if (!$this->mcrypt_module) $this->openModule();
			return $this->iv_size;
		}

		/**
		 * Returns the key size
		 * @return int
		 */
		public function getKeySize()
		{
			if (!$this->mcrypt_module) $this->openModule();
			return $this->key_size;
		}

		/**
		 * Sets the cipher that will be used
		 * @param int $value
		 * @return void
		 */
		public function setCipher($value)
		{
			$this->cipher = $value;

			if ($this->module_init) $this->deinitModule();
			if ($this->mcrypt_module !== NULL) $this->closeModule();

			$this->openModule();
		}

		/**
		 * Gets the cipher in use
		 * @return int
		 */
		public function getCipher()
		{
			return $this->cipher;
		}

		/**
		 * constructor
		 *
		 * @param string $crypt_key Encryption key. The longer the better. Will be limited by the key size of AES-128
		 * @param int $cipher The cipher to use
		 */
		public function __construct($crypt_key, $cipher = self::CIPHER_128_BIT)
		{
			$this->cipher = $cipher;
			$this->openModule();
			$this->setCryptKey($crypt_key);
		}

		/**
		 * destructor!
		 *
		 */
		public function __destruct()
		{
			if ($this->module_init === TRUE)
			{
				$this->deinitModule();
			}
			if ($this->mcrypt_module !== NULL)
			{
				$this->closeModule();
			}
		}

		/**
		 * Allows you to generate a random IV that will be later
		 * used as a static IV (i.e., stored in a configuration)
		 * @return string
		 */
		public function generateRandomIV()
		{
			if (!$this->mcrypt_module) $this->openModule();
			return mcrypt_create_iv($this->iv_size, MCRYPT_DEV_URANDOM);
		}

		/**
		 * Translates the internal cipher constants to MCrypt constants
		 *
		 * @return int
		 */
		protected function getCipherModule()
		{
			switch ($this->cipher)
			{
				case self::CIPHER_128_BIT:
					return MCRYPT_RIJNDAEL_128;
				case self::CIPHER_192_BIT:
					return MCRYPT_RIJNDAEL_192;
				case self::CIPHER_256_BIT:
					return MCRYPT_RIJNDAEL_256;
				default:
					throw new Exception("Invalid encryption cipher.");
			}
		}

		/**
		 * Opens the mcrypt module.
		 * @return void
		 */
		protected function openModule()
		{
			$this->mcrypt_module = mcrypt_module_open($this->getCipherModule(), NULL, MCRYPT_MODE_CFB, NULL);
			$this->key_size = mcrypt_enc_get_key_size($this->mcrypt_module);
			$this->iv_size = mcrypt_enc_get_iv_size($this->mcrypt_module);
		}

		/**
		 * closes the mcrypt module resource
		 * @return void
		 */
		protected function closeModule()
		{
			mcrypt_module_close($this->mcrypt_module);
		}

		/**
		 * initializes the mcrypt module using the given encryption key and IV
		 * this should be called anytime either of those is changed
		 *
		 * @param string $iv
		 * @return void
		 */
		protected function initModule($iv)
		{
			if ($this->mcrypt_module === NULL)
			{
				$this->openModule();
			}

			if ($this->module_init === TRUE)
			{
				$this->deinitModule();
			}

			if (($mcrypt_status = @mcrypt_generic_init($this->mcrypt_module, $this->crypt_key, $iv)) < 0)
			{
				switch ($mcrypt_status)
				{
					case -3:
						throw new Exception("MCrypt generic init failed because the key length was invalid.");
					case -4:
						throw new Exception("MCrypt generic init failed because of a memory allocation error.");
					default:
						throw new Exception("MCrypt generic init failed because of an unknown error.");
				}
			}

			$this->module_init = TRUE;
		}

		/**
		 * performs per-operation deinitialization
		 * @return void
		 */
		protected function deinitModule()
		{
			mcrypt_generic_deinit($this->mcrypt_module);
			$this->module_init = FALSE;
		}

		/**
		 * Encrypts the string and returns raw encrypted data.  If a static IV is provided, it will not be
		 * included in the encrypted data. At that point, it is on the application to decide what to do
		 * with the static IV.
		 *
		 * @param array|string $data
		 * @return array|string encrypted data
		 */
		public function encrypt($data)
		{
			$return_string = FALSE;
			if (! is_array($data))
			{
				$data = array($data);
				$return_string = TRUE;
			}

			if ($this->mcrypt_module === NULL)
			{
				$this->openModule();
			}

			if ($this->use_static_iv)
			{
				$this->initModule($this->static_iv);
				foreach ($data as $key => $clear)
				{
					$data[$key] = $clear !== '' ? mcrypt_generic($this->mcrypt_module, $clear) : '';
				}
				$this->deinitModule();
			}
			else
			{
				foreach ($data as $key => $clear)
				{
					$iv = $this->generateRandomIV();
					$this->initModule($iv);
					$data[$key] = $clear !== '' ? $iv . mcrypt_generic($this->mcrypt_module, $clear) : '';
					$this->deinitModule();
				}
			}


			return $return_string ? $data[0] : $data;
		}

		/**
		 * Decrypts the given binary data and returns the decrypted data.  If no static IV
		 * is provided to our object, this method will attempt to pull the IV out of the encrypted
		 * data.
		 *
		 * @param array|string $encrypted
		 * @return array|string decrypted data
		 */
		public function decrypt($encrypted)
		{
			$return_string = FALSE;
			if (! is_array($encrypted))
			{
				$encrypted = array($encrypted);
				$return_string = TRUE;
			}

			if ($this->mcrypt_module === NULL)
			{
				$this->openModule();
			}

			if ($this->use_static_iv)
			{
				$iv = $this->static_iv;
				$this->initModule($iv);
				foreach ($encrypted as $key => $crypt)
				{
					$encrypted[$key] = $crypt !== '' ? mdecrypt_generic($this->mcrypt_module, $crypt) : '';
				}
				$this->deinitModule();
			}
			else
			{
				foreach ($encrypted as $key => $crypt)
				{
					if ($crypt !== '')
					{
						$iv = substr($crypt, 0, $this->iv_size);
						$crypt = substr($crypt, $this->iv_size);
						$this->initModule($iv);
						$encrypted[$key] = mdecrypt_generic($this->mcrypt_module, $crypt);
						$this->deinitModule();
					}
					else
					{
						$encrypted[$key] = '';
					}
				}
			}

			return $return_string ? $encrypted[0] : $encrypted;
		}
	}
?>
