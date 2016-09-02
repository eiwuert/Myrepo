<?php
	/**
	 * @package Security
	 */

	/**
	 * Interface for encryption libraries
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	interface Security_ICrypt_1
	{
		/**
		 * Encrypts a string or array of strings
		 *
		 * @param array|string $string
		 * @return array|string
		 */
		public function encrypt($string);

		/**
		 * Decrypts an encrypted string or array of strings
		 *
		 * @param array|string $string
		 * @return array|string
		 */
		public function decrypt($string);
	}

?>
