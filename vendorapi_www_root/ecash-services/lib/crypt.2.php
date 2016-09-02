<?php
	// Version 2.0.0
	// A class to handle encryption tools

	/* UPDATES
		Features:

		Bugs:
	*/

	/* PROTOTYPES
		bool Crypt_2 ()
		string Encrypt (string Clear_Text, [string Secret_Key])
		string Decrypt (string Encrypted_Text, [string Secret_Key])
		string Hash (string Value, [string Secret_Key])
		bool Seed_Random_Generator ()
		string Random_Char (int Length)
		string _Secret_Key (string Secret_Key)
		object Get_Version ()
	*/
	
	/* OPTIONAL CONSTANTS
		SECRET_KEY = The secrect key you use to encrypt one way hashes.  If not set will default to md5 ("NOKEY").
		LEFT_MODULE = The mcrypt module to use for the left half of the string.  If not set will default to MCRYPT_SERPENT;
		RIGHT_MODULE = The mcrypt module to use for the right half of the string.  If not set will default to MCRYPT_TWOFISH;
		FINAL_MODULE = The mcrypt module to use for the re-constituted string.  If not set will default to MCRYPT_RIJNDAEL_256;
	*/

	/* SAMPLE USAGE
		$encrypted = Crypt_2::Encrypt ("Value", "Something secret");
		$decrypted = Crypt_2::Decrypt ($encrypted, "Something secret");
		$hashed = Crypt_2::Hash ("Value", "Something secret");
		$random_char = Crypt_2::Random_Char (8);
	*/
	
	class Crypt_2
	{
		function Crypt_2 ()
		{
			return TRUE;
		}

		function Encrypt ($clear_value, $secret_key = NULL)
		{
			// Get the secret_key
			$secret_key = Crypt_2::_Secret_Key ($secret_key);

			// Determine the encryption type for the modules
			if (!defined ("LEFT_MODULE"))
			{
				// Set the value
				define ("LEFT_MODULE", MCRYPT_SERPENT);
			}

			if (!defined ("RIGHT_MODULE"))
			{
				// Set the default
				define ("RIGHT_MODULE", MCRYPT_TWOFISH);
			}

			if (!defined ("FINAL_MODULE"))
			{
				// Set the default
				define ("FINAL_MODULE", MCRYPT_RIJNDAEL_256);
			}
			
			// Set the initialization vector for encryption
			$iv_source = strlen ($secret_key);
			
			// Base 64 encode the string to remove spaces
			$clear_value = base64_encode ($clear_value);

			// Split the string
			$half = (int)strlen ($clear_value) / 2;
			$left_clear = substr ($clear_value, 0, $half);
			$right_clear = substr ($clear_value, $half);

			// Encode the left half
			$left_module = mcrypt_module_open (LEFT_MODULE, "", MCRYPT_MODE_ECB, "");
			$left_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($left_module));
			$left_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($left_module), $iv_source);
			mcrypt_generic_init ($left_module, $left_key, $left_iv);
			$left_encrypt = mcrypt_generic ($left_module, $left_clear);
			mcrypt_generic_deinit ($left_module);
			mcrypt_module_close ($left_module);

			// Encode the right half
			$right_module = mcrypt_module_open (RIGHT_MODULE, "", MCRYPT_MODE_ECB, "");
			$right_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($right_module));
			$right_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($right_module), $iv_source);
			mcrypt_generic_init ($right_module, $right_key, $right_iv);
			$right_encrypt = mcrypt_generic ($right_module, $right_clear);
			mcrypt_generic_deinit ($right_module);
			mcrypt_module_close ($right_module);
			
			// Encode the whole thing
			$final_module = mcrypt_module_open (FINAL_MODULE, "", MCRYPT_MODE_ECB, "");
			$final_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($final_module));
			$final_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($final_module), $iv_source);
			mcrypt_generic_init ($final_module, $final_key, $final_iv);
			$final_encrypt = mcrypt_generic ($final_module, $right_encrypt.$left_encrypt);
			mcrypt_generic_deinit ($final_module);
			mcrypt_module_close ($final_module);
			
			return chunk_split (base64_encode ($final_encrypt), 5, " ");
		}

		function Decrypt ($encrypted_value, $secret_key = NULL)
		{
			// Get the secret_key
			$secret_key = Crypt_2::_Secret_Key ($secret_key);

			// Determine the encryption type for the modules
			if (!defined ("LEFT_MODULE"))
			{
				// Set the value
				define ("LEFT_MODULE", MCRYPT_SERPENT);
			}

			if (!defined ("RIGHT_MODULE"))
			{
				// Set the default
				define ("RIGHT_MODULE", MCRYPT_TWOFISH);
			}

			if (!defined ("FINAL_MODULE"))
			{
				// Set the default
				define ("FINAL_MODULE", MCRYPT_RIJNDAEL_256);
			}
			
			// Set the initialization vector for decryption
			$iv_source = strlen ($secret_key);

			// Base64 Decode?
			$delivered_encrypt = base64_decode (preg_replace ("/ /", "", $encrypted_value));

			// Dencode the whole thing
			$final_module = mcrypt_module_open (FINAL_MODULE, "", MCRYPT_MODE_ECB, "");
			$final_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($final_module));
			$final_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($final_module), $iv_source);
			mcrypt_generic_init ($final_module, $final_key, $final_iv);
			$final_encrypt = trim (mdecrypt_generic ($final_module, $delivered_encrypt));
			mcrypt_generic_deinit ($final_module);
			mcrypt_module_close ($final_module);

			// Split the string
			$part_count = strlen ($final_encrypt) / 8;
			if (!$part_count % 2)
			{
				$half = (int)(($part_count / 2) * 8) + 8;
			}
			else
			{
				$half = (int)(($part_count / 2) * 8);
			}
			
			$right_encrypt = substr ($final_encrypt,0, $half);
			$left_encrypt = substr ($final_encrypt, $half);

			// Encode the left half
			$left_module = mcrypt_module_open (LEFT_MODULE, "", MCRYPT_MODE_ECB, "");
			$left_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($left_module));
			$left_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($left_module), $iv_source);
			mcrypt_generic_init ($left_module, $left_key, $left_iv);
			$left_clear = trim (mdecrypt_generic ($left_module, $left_encrypt));
			mcrypt_generic_deinit ($left_module);
			mcrypt_module_close ($left_module);

			// Encode the right half
			$right_module = mcrypt_module_open (RIGHT_MODULE, "", MCRYPT_MODE_ECB, "");
			$right_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($right_module));
			$right_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($right_module), $iv_source);
			mcrypt_generic_init ($right_module, $right_key, $right_iv);
			$right_clear = trim (mdecrypt_generic ($right_module, $right_encrypt));
			mcrypt_generic_deinit ($right_module);
			mcrypt_module_close ($right_module);

			return base64_decode ($left_clear.$right_clear);
		}
		
		function Hash ($value, $secret_key = NULL)
		{
			// Get the secret_key
			$secret_key = Crypt_2::_Secret_Key ($secret_key);

			return md5 ($secret_key.$value);
		}

		function Seed_Random_Generator ()
		{
			mt_srand (hexdec (substr (md5 (microtime ()), -8)) & 0x7fffffff);

			define ("RANDOM_SEED_SET", TRUE);

			return TRUE;
		}

		function Random_Char ($length = 8)
		{
			// Has the seed been set?
			if (!defined ("RANDOM_SEED_SET"))
			{
				Crypt_2::Seed_Random_Generator ();
			}

			// Store the typable characters
			$chars =
				"abcdefghijklmnopqrstuvwxyz".
				"ABCDEFGHIJKLMNOPQRSTUVWXYZ".
				"0123456789";

			// Generate the password
			for ($i = 0; $i < $length; $i++)
			{
				// Create the password
				$passwd .=  substr ($chars, mt_rand (0, strlen ($chars)), 1);
			}

			return $passwd;
		}
		
		function _Secret_Key ($secret_key)
		{
			if (is_null ($secret_key))
			{
				if (defined ("SECRET_KEY"))
				{
					$secret_key = SECRET_KEY;
				}
				else
				{
					$secret_key = md5 ("NOKEY");
				}
			}
			
			return $secret_key;
		}

		function Get_Version ()
		{
			$version = new stdClass ();

			$version->api = 1;
			$version->feature = 0;
			$version->bug = 0;
			$version->version = $version->api.".".$version->feature.".".$version->bug;

			return $version;
		}
	}
?>
