<?php
require_once 'autoload_setup.php';

class Security_CryptTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
	}

	public function tearDown()
	{
	}

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
}