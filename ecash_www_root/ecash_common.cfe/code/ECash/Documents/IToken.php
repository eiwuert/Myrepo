<?php
/**
 * ECash_Documents_IToken
 * interface to define token providers for ECash Documents
 * 
 */
interface ECash_Documents_IToken
{
	/**
	 * getTokens
	 * 
	 * @return array associative array of token names and their values
	 */
	public function getTokens();
	/**
	 * getToken
	 * 
	 * @param string $name
	 * 
	 * @return string value of token with given name 
	 * 
	 * */
	public function getToken($name);

}


?>