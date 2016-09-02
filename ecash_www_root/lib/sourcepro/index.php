<?php
/**
	Contains static variables/methods that are global.
*/

class SourcePro
{
	/// The SourcePro framework version.
	const VERSION = "1.0.0";

	/// The name of the host that this code is executing on.
	static public $node;

	/// Fields that are integers (ie. 3).
	const TYPE_INT = 1;

	/// Fields that are reals (ie. 3.1415).
	const TYPE_REAL = 2;

	/// Fields that are strings.
	const TYPE_CHAR = 3;

	/// Fields that are timestamps.
	const TYPE_TIMESTAMP = 4;

	/// Fields that are auto incrementing ids.
	const ROLE_ID = 1;

	/// Fields that are a unique string.
	const ROLE_KEY = 2;

	/// Fields representing the last modified time.
	const ROLE_MTIME = 4;

	/// Fields representing the created time.
	const ROLE_CTIME = 8;

	/// Fields representing an external id
	const ROLE_EID = 16;

	/// Relations where the link field is held locally
	const LINK_INTERNAL = 1;

	/// Relations where the link field is held remotely
	const LINK_EXTERNAL = 2;

	/**
		Returns a URL friendly version of a binary string.

		@param bin		The binary string.
		@param nbits	The number of bits per character in the final string (4,5,6).
		@exception		SourcePro_Exception General exception occured.
	*/
	static function Bin2Str ($bin, $nbits = 6)
	{
		$alpha = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,-";

		$p = 0;
		$q = strlen($bin);

		$w = 0;
		$have = 0;
		$mask = (1 << $nbits) - 1;

		$out = '';

		while (1)
		{
			if ($have < $nbits)
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
					$have = $nbits;
				}
			}

			$out .= $alpha{($w & $mask)};

			$w >>= $nbits;
			$have -= $nbits;
		}

		return $out;
	}

	/**
		Reverse bin to string conversion performed by Bin2Str (see above).

		@param str		The character string.
		@param nbits	The number of bits per character used in original conversion (4,5,6).
		@exception		SourcePro_Exception General exception occured.
	*/
	static function Str2Bin ($inStr, $nbits = 6)
	{
		$alpha = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,-";
		$length = strlen ($inStr);
		$out = '';
		$at = 0;
		$rshift = 0;
		$charIn = 0;
		$charOut = chr (0);

		while (1)
		{
			$charIn = strcspn ($alpha, $inStr{$at++});
			if ($rshift > 0)
			{
				$charOut |= chr ($charIn << 8 - $rshift);
				$out .= $charOut;
				$charOut = chr (0);
				if ($at >= $length)
				{
					break;
				}
			}
			$charOut |= chr ($charIn >> $rshift);
			$rshift += 2;
			if ($rshift === 8)
			{
				$rshift = 0;
			}
		}
		
		return $out;
	}

	/**
		Returns a one-way hash digest of a message.

		@param msg	The message to hash.
		@param key	The optional key to use while hashing.
		@param type	The type of hash to perform (defaults to SHA1).
		@param bits	The number of bits per character in the final string (4,5,6).
		@exception	SourcePro_Exception General exception occured.
	*/
	static function Hash ($msg, $key = NULL, $type = MHASH_SHA1, $bits = 6)
	{
		$hash = SourcePro::Bin2Str(sha1($msg.$key, 1), $bits);
		if ($hash === FALSE)
		{
			throw new SourcePro_Exception("hash failed", 1000);
		}
		return $hash;
	}

	/**
		Returns a globaly unique id.
	*/
	static function Guid ()
	{
		return SourcePro::Hash (uniqid (mt_rand (), 1).microtime ().`hostid`);
	}

	static function Encrypt ($msg, $key, $alg = MCRYPT_TWOFISH)
	{
		$mod = mcrypt_module_open($alg, '', MCRYPT_MODE_ECB, '');
		$key = substr($key, 0, mcrypt_enc_get_key_size($mod));
		$vec = mcrypt_create_iv(mcrypt_enc_get_iv_size($mod), MCRYPT_DEV_URANDOM);
		if (($re = mcrypt_generic_init($mod, $key, $vec)) < 0)
		{
			throw new SourcePro_Exception("mcrypt_generic_init failed with $re", 1000);
		}
		$ret = mcrypt_generic($mod, $msg);
		mcrypt_generic_deinit($mod);
		mcrypt_module_close($mod);
	}

	static function Decrypt ($msg, $key, $alg = MCRYPT_TWOFISH)
	{
		$mod = mcrypt_module_open($alg, '', MCRYPT_MODE_ECB, '');
		$key = substr($key, 0, mcrypt_enc_get_key_size($mod));
		$vec = mcrypt_create_iv(mcrypt_enc_get_iv_size($mod), MCRYPT_DEV_URANDOM);
		if (($re = mcrypt_generic_init($mod, $key, $vec)) < 0)
		{
			throw new SourcePro_Exception("mcrypt_generic_init failed with $re", 1000);
		}
		$ret = mdecrypt_generic($mod, $msg);
		mcrypt_generic_deinit($mod);
		mcrypt_module_close($mod);
	}

	/**
		Database table compression utility.
		Compressess a column by packing either hexadecimal strings or 
		URL-friendly strings produced by Bin2Str(). Before this function
		is called an empty column of the type BLOB (20) must be added to 
		the table.

		@param db_host	MySQL host name.
		@param db_port	MySQL port number.
		@param db_user	MySQL login.
		@param db_pass	MySQL password.
		@param db_name	MySQL database name.
		@param table	MySQL table name.
		@param id_col	Name of the column that contains unique IDs.
		@param source_col	Name of the column that contains OLD (long) binary values.
		@param dest_col	Name of the column that will hold compressed data [BLOB(20)].
	*/
	static function compress_table ($db_host, 
						 			$db_port, 
						 			$db_user, 
						 			$db_pass, 
						 			$db_name,
						 			$table,
						 			$id_col,
						 			$source_col,
						 			$dest_col)
	{
		$connect_handle = mysql_connect ($db_host.":".$db_port, $db_user, $db_pass);
		if (!$connect_handle)
		{
			die (mysql_error()."\n");
		}

		$db = mysql_select_db ($db_name);
		if (!$db)
		{
			throw new SourcePro_Exception ("Database {$db_name} not found : " .
											mysql_error(),
											2001);
			return;
		}

		$query = "select {$id_col}, {$source_col} from {$table};";
		$result_set = mysql_query ($query);
		if ($result_set === FALSE)
		{
			throw new SourcePro_Exception ("MySQL query failed : " .
											mysql_error() .
											". Query = {$query}.",
											2002);
			return;
		}

		$num_rows = mysql_num_rows ($result_set);
		if ($num_rows === FALSE)
		{
			throw new SourcePro_Exception ("Error getting number of rows : " .
											mysql_error(),
											2003);
			return;
		}

		while ($row = mysql_fetch_row ($result_set))
		{
			$at = $row[0];
			$long_hash = $row[1];
			if (strlen ($long_hash) === 27)
			{
				$short_hash = SourcePro::Str2Bin ($long_hash);
			}
			else
			{
				$short_hash = pack ('H*', $long_hash);
			}

			$upd_query = "update {$table} set {$dest_col} = '" .
			mysql_real_escape_string ($short_hash) . 
				"' where ({$id_col} = {$at});";

			$update_result = mysql_query ($upd_query);
			if (!$update_result)
			{
				throw new SourcePro_Exception ("MySQL query failed : " .
											mysql_error() .
											". Query = {$upd_query}.",
											2003);
			}
		}

		mysql_close ($connect_handle);

	}	//  compress_table

}

SourcePro::$node = php_uname('n');

require_once ('sourcepro/exception.php');
require_once ('sourcepro/metaobject.php');
require_once ('sourcepro/time.php');

require_once ('sourcepro/prpc/message/base.php');
require_once ('sourcepro/prpc/message/call.php');
require_once ('sourcepro/prpc/message/except.php');
require_once ('sourcepro/prpc/message/return.php');

require_once ('sourcepro/prpc/base.php');
require_once ('sourcepro/prpc/client.php');
require_once ('sourcepro/prpc/server.php');

require_once ('sourcepro/prpc/utility/base.php');
require_once ('sourcepro/prpc/utility/serialize.php');
require_once ('sourcepro/prpc/utility/compress.php');
require_once ('sourcepro/prpc/utility/exception.php');

require_once ('sourcepro/notification/base.php');
require_once ('sourcepro/notification/ole.php');

require_once ('sourcepro/entity/attribute/base.php');

require_once ('sourcepro/entity/attribute/asset/base.php');
require_once ('sourcepro/entity/attribute/asset/number.php');
require_once ('sourcepro/entity/attribute/asset/string.php');
require_once ('sourcepro/entity/attribute/asset/time.php');

require_once ('sourcepro/entity/attribute/field/base.php');
require_once ('sourcepro/entity/attribute/field/number.php');
require_once ('sourcepro/entity/attribute/field/string.php');
require_once ('sourcepro/entity/attribute/field/time.php');
require_once ('sourcepro/entity/attribute/field/stamp.php');

require_once ('sourcepro/entity/attribute/property.php');

require_once ('sourcepro/entity/attribute/relation/base.php');
require_once ('sourcepro/entity/attribute/relation/single.php');
require_once ('sourcepro/entity/attribute/relation/multi.php');

require_once ('sourcepro/entity/base.php');
require_once ('sourcepro/entity/storage.php');
require_once ('sourcepro/entity/notification.php');

require_once ('sourcepro/storage/base.php');
require_once ('sourcepro/storage/mysql.php');
require_once ('sourcepro/storage/db2.php');
require_once ('sourcepro/storage/sqlite.php');

require_once ('sourcepro/session/base.php');

?>
