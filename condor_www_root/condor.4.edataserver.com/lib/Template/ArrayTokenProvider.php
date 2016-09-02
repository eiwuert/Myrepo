<?php 

/**
 * Provides tokens from an array
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class Template_ArrayTokenProvider implements Template_ITokenProvider 
{
	/**
	 * Array of tokens
	 *
	 * @var Array
	 */
	protected $tokens;
	
	/**
	 * CONSTRUCTS
	 *
	 * @param array $tokens
	 */
	public function __construct(array $tokens)
	{
		$this->tokens = $tokens;
	}
	
	/**
	 * Get the value for a token
	 *
	 * @param string $token
	 * @return String
	 */
	public function getTokenValue($token)
	{
		return isset($this->tokens[$token]) ? $this->tokens[$token] : NULL;
	}
}