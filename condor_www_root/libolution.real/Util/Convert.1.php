<?php

	/**
	 * @package Util
	 */

	/**
	 * Utility class for converting data to various formats
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Util_Convert_1 extends Object_1
	{
		/**
		 * Takes a raw hash of the data and returns it using bin2String
		 *
		 * @param string $data
		 * @return string
		 */
		public static function hash($data, $algo = 'sha1')
		{
			return self::bin2String(hash($algo, $data, true));
		}

		/**
		 * Converts raw binary data to a printable string, similar to base64
		 *
		 * @param string $bin
		 * @return string
		 */
		public static function bin2String($bin)
		{
			static $bin_chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,-";

			$p = 0;
			$q = strlen($bin);

			$w = 0;
			$have = 0;
			$mask = 0x3F;

			$out = '';

			while (1)
			{
				if ($have < 6)
				{
					if ($p < $q)
					{
						$w |= ord($bin{$p++}) << $have;
						$have += 8;
					}
					else
					{
						if ($have == 0)
							break;
						$have = 6;
					}
				}

				$out .= $bin_chars{($w & $mask)};

				$w >>= 6;
				$have -= 6;
			}

			return $out;
		}

		/**
		 * Decodes data encoded using Util_Convert_1::bin2String().  Similar to base64.
		 *
		 * @param string $str
		 * @return string
		 */
		public static function string2Bin($str)
		{
			$alpha = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,-";
			$length = strlen($str);
			$out = '';
			$at = 0;
			$rshift = 0;
			$char_out = chr(0);

			while (1)
			{
				$char_in = strcspn($alpha, $str{$at++});
				if ($rshift > 0)
				{
					$char_out |= chr($char_in << 8 - $rshift);
					$out .= $char_out;
					$char_out = chr(0);
					if ($at >= $length)
					{
						break;
					}
				}
				$char_out |= chr($char_in >> $rshift);
				$rshift += 2;
				if ($rshift === 8)
				{
					$rshift = 0;
				}
			}

			return $out;
		}

		/**
		 * Parses hex into binary data
		 *
		 * @param string $hex_string
		 * @return string
		 */
		public static function hex2Bin($hex_string)
		{
			return pack("H*", $hex_string);
		}

		/**
		 * Turns binary into hex string.
		 * Wraps the php internal bin2hex()
		 *
		 * @param string $bin
		 * @return string
		 */
		public static function bin2Hex($bin)
		{
			return bin2hex($bin);
		}

		/**
		 * Converts an IP address into a float to avoid issues with PHP's signed ints
		 * @param string $ip
		 * @return float
		 */
		public static function ip2Float($ip)
		{
			if (($f = ip2long($ip)) < 0)
			{
				return (($f & 0x7FFFFFFF) + 0x80000000);
			}
			return $f;
		}

		/**
		 * GZ compresses a string, but compatible with MySQL's COMPRESS()
		 * @param string $string
		 * @return string
		 */
		public static function compressMySQL($string)
		{
			return pack('L', strlen($string)).gzcompress($string);
		}

		/**
		 * GZ uncompresses a string, but compatible with MySQL's UNCOMPRESS()
		 * @param string $string
		 * @return string
		 */
		public static function uncompressMySQL($string)
		{
			return gzuncompress(substr($string, 4));
		}
	}

?>
