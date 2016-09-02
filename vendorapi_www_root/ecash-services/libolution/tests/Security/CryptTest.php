<?php

class Security_CryptTest extends PHPUnit_Framework_TestCase
{
	public function testEncrypt128()
	{
		$crypt = new Security_Crypt_1('TestKey1');

		$crypt->StaticIV =
			Util_Convert_1::hex2Bin(
				"3b7f409f2bb207022b07fb39ac406413"
			);

		$crypt->UseStaticIV = TRUE;

		$this->assertEquals(
			"a1930e1fa4c538d6042a3d3fc6",
			Util_Convert_1::bin2Hex(
				$crypt->encrypt("Test String 1")
			)
		);

	}

	public function testDecrypt128()
	{
		$crypt = new Security_Crypt_1('TestKey1');

		$crypt->StaticIV =
			Util_Convert_1::hex2Bin(
				"3b7f409f2bb207022b07fb39ac406413"
			);

		$crypt->UseStaticIV = TRUE;

		$this->assertEquals(
			"Test String 1",
			$crypt->decrypt(Util_Convert_1::hex2Bin("a1930e1fa4c538d6042a3d3fc6"))
		);
	}

	public function testEncrypt256()
	{
		$crypt = new Security_Crypt_1('TestKey1', Security_Crypt_1::CIPHER_256_BIT);

		$iv = "f711069820e88f14f37133ca740d49b8ce0f8fb7bdffd1ec1b23b72e86acb9be";
		$enc = "25ab163f0e7c79b6802ad0a569";

		$crypt->StaticIV = Util_Convert_1::hex2Bin($iv);
		$crypt->UseStaticIV = TRUE;

		$this->assertEquals(
			$enc,
			Util_Convert_1::bin2Hex(
				$crypt->encrypt("Test String 1")
			)
		);
	}
	public function testDecrypt256()
	{
		$crypt = new Security_Crypt_1('TestKey1', Security_Crypt_1::CIPHER_256_BIT);

		$iv = "f711069820e88f14f37133ca740d49b8ce0f8fb7bdffd1ec1b23b72e86acb9be";
		$enc = "25ab163f0e7c79b6802ad0a569";

		$crypt->StaticIV = Util_Convert_1::hex2Bin($iv);
		$crypt->UseStaticIV = TRUE;

		$this->assertEquals(
			"Test String 1",
			$crypt->decrypt(Util_Convert_1::hex2Bin($enc))
		);
	}
	
	public function testModuleClosesCorrectlyOnDestruct()
	{
		$crypt = new Security_Crypt_1('TestKey1');
		
		try
		{
			// The invalid cipher will cause the mcrypt module to close, but not be able to reopen
			$crypt->setCipher(7567286926);
		}
		catch (Exception $e)
		{
			// do nothing
		}
		
		// Unsetting $crypt will call __destruct(). Originally, this would have caused a warning because
		// the resource wasn't set correctly
		unset($crypt);
	}

	public function testArrayEncryptionAndDecryptionMaintainsValuesWithDifferentOrder()
	{
		$crypt = new Security_Crypt_1('TestKey1', Security_Crypt_1::CIPHER_256_BIT);

		$iv = "f711069820e88f14f37133ca740d49b8ce0f8fb7bdffd1ec1b23b72e86acb9be";
		$enc = "25ab163f0e7c79b6802ad0a569";

		$crypt->setStaticIV(Util_Convert_1::hex2Bin($iv));
		$crypt->setUseStaticIV(true);

		$data_1 = array(
			'monkey',
			'llama'
		);
		
		$e_data_1 = array_map('base64_encode', $crypt->encrypt($data_1));

		$data_2 = array(
			'llama',
			'monkey'
		);

		$e_data_2 = array_map('base64_encode', $crypt->encrypt($data_2));

		foreach ($e_data_1 as $k => $v)
		{
			$this->assertContains($v, $e_data_2);
		}

		$d_data_1 = $crypt->decrypt(array_map('base64_decode', $e_data_1));
		$d_data_2 = $crypt->decrypt(array_map('base64_decode', $e_data_2));

		foreach ($d_data_1 as $k => $v)
		{
			$this->assertContains($v, $d_data_2);
		}
	}

	public function testArrayEncryptionWithDynamicIVMaintainsValuesWithDifferentOrder()
	{
		$crypt = new Security_Crypt_1('TestKey1');

		$data = array('foo', 'bar');

		$enc_data = array_map('base64_encode', $crypt->encrypt($data));

		$this->assertEquals($data[1], $crypt->decrypt(base64_decode($enc_data[1])));
	}
}
