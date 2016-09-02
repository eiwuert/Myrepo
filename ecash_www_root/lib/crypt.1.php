<?php
	// Version 1.0.0
	// A class to handle crypto tools

	/* PROTOTYPES
		bool Crypt_1 ([bool base64])
		string Encrypt (string Clear_Text, [string Secret_Key])
		string Decrypt (string Encrypted_Text, [string Secret_Key])
	*/
	
	/* SAMPLE USAGE
		$mycrpt = new Crypt_1 (TRUE);
		$encrypted = $mycrypt->Encrypt ("Value_No_Spaces", "Something secret");
		$decrypted = $mycrpt->Decrypt ($encrypted, "Something secret");
	*/
	
	class Crypt_1
	{
		function Crypt_1 ()
		{
			$this->left_module = MCRYPT_SERPENT;
			$this->right_module = MCRYPT_TWOFISH;
			$this->final_module = MCRYPT_RIJNDAEL_256;

			return TRUE;
		}
		
		function Encrypt ($clear_value, $secret_key = "NOKEY")
		{
			// Set the initialization vector for encryption
			$iv_source = strlen ($secret_key);
			
			// Base 64 encode the string to remove spaces
			$clear_value = base64_encode ($clear_value);

			// Split the string
			$half = (int)strlen ($clear_value) / 2;
			$left_clear = substr ($clear_value, 0, $half);
			$right_clear = substr ($clear_value, $half);

			// Encode the left half
			$left_module = mcrypt_module_open ($this->left_module, "", MCRYPT_MODE_ECB, "");
			$left_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($left_module));
			$left_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($left_module), $iv_source);
			mcrypt_generic_init ($left_module, $left_key, $left_iv);
			$left_encrypt = mcrypt_generic ($left_module, $left_clear);
			mcrypt_generic_deinit ($left_module);
			mcrypt_module_close ($left_module);

			// Encode the right half
			$right_module = mcrypt_module_open ($this->right_module, "", MCRYPT_MODE_ECB, "");
			$right_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($right_module));
			$right_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($right_module), $iv_source);
			mcrypt_generic_init ($right_module, $right_key, $right_iv);
			$right_encrypt = mcrypt_generic ($right_module, $right_clear);
			mcrypt_generic_deinit ($right_module);
			mcrypt_module_close ($right_module);
			
			// Encode the whole thing
			$final_module = mcrypt_module_open ($this->final_module, "", MCRYPT_MODE_ECB, "");
			$final_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($final_module));
			$final_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($final_module), $iv_source);
			mcrypt_generic_init ($final_module, $final_key, $final_iv);
			$final_encrypt = mcrypt_generic ($final_module, $right_encrypt.$left_encrypt);
			mcrypt_generic_deinit ($final_module);
			mcrypt_module_close ($final_module);
			
			return chunk_split (base64_encode ($final_encrypt), 5, " ");
		}

		function Decrypt ($encrypted_value, $secret_key = "NOKEY")
		{
			// Set the initialization vector for decryption
			$iv_source = strlen ($secret_key);

			// Base64 Decode?
			$delivered_encrypt = base64_decode (preg_replace ("/ /", "", $encrypted_value));

			// Dencode the whole thing
			$final_module = mcrypt_module_open ($this->final_module, "", MCRYPT_MODE_ECB, "");
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
			$left_module = mcrypt_module_open ($this->left_module, "", MCRYPT_MODE_ECB, "");
			$left_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($left_module));
			$left_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($left_module), $iv_source);
			mcrypt_generic_init ($left_module, $left_key, $left_iv);
			$left_clear = trim (mdecrypt_generic ($left_module, $left_encrypt));
			mcrypt_generic_deinit ($left_module);
			mcrypt_module_close ($left_module);

			// Encode the right half
			$right_module = mcrypt_module_open ($this->right_module, "", MCRYPT_MODE_ECB, "");
			$right_key = substr ($secret_key, 0, mcrypt_enc_get_key_size ($right_module));
			$right_iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($right_module), $iv_source);
			mcrypt_generic_init ($right_module, $right_key, $right_iv);
			$right_clear = trim (mdecrypt_generic ($right_module, $right_encrypt));
			mcrypt_generic_deinit ($right_module);
			mcrypt_module_close ($right_module);

			return base64_decode ($left_clear.$right_clear);
		}
		
		function Seed_Random_Generator ()
		{
			$hash = md5 (microtime());
			$sub_length = ((substr ($hash, 0, 1) < 8) ? 8 : 7 );
			$seed = base_convert (substr ($hash, 0, $sub_length), 16, 10);
			mt_srand ($seed);
			
			define ("RANDOM_SEED_SET", TRUE);
			
			return TRUE;
		}

		function Hash ($value, $hash_key = NULL)
		{
			if (is_null ($hash_key) && defined ("MD5_HASH_KEY"))
			{
				$hash_key = MD5_HASH_KEY;
			}

			return md5 ($hash_key.$value);
		}

		function Random_Char ($length = 8)
		{
			// Has the seed been set?
			if (!defined ("RANDOM_SEED_SET"))
			{
				Crypt_1::Seed_Random_Generator ();
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
