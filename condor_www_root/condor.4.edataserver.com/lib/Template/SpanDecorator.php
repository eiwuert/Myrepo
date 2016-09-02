<?php 

/**
 * SpanDecorator?
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class Template_SpanDecorator implements Template_ITokenProvider 
{
	/**
	 * Provides
	 *
	 * @var Template_ITokenProvider
	 */
	protected $provider;
	
	/**
	 *
	 * @var array
	 */
	protected $tokens;
	
	/**
	 * Construct the decorator
	 *
	 * @param Template_ITokenProvider $provider
	 */
	public function __construct(Template_ITokenProvider $provider)
	{
		$this->provider = $provider;
		$this->tokens   = array();
	}
	
	/**
	 * Returns a value
	 *
	 * @param string $token
	 * @return string
	 */
	public function getTokenValue($token)
	{
		if (!isset($this->tokens[$token]))
		{
			$this->tokens[$token] = 0;
		}
		$this->tokens[$token]++;
		$inc = $this->tokens[$token];
		
		return "<span class=\"$token $token$inc\">".$this->provider->getTokenValue($token)."</span>";
	}
}
