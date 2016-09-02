<?php 

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'ITokenProvider.php');

/**
 * Parses. Templates.
 *
 * @author Stephan Soileau <stephan.soileau@sellignsource.com>
 */
class Template_Parser
{
	/**
	 * Token provider
	 *
	 * @var Template_ITokenProvider
	 */
	protected $provider;
	
	/**
	 * Identifies tokens
	 *
	 * @var string
	 */
	protected $token_identifier;
	
	/**
	 * The template we'll be rendering
	 * using the token provider
	 *
	 * @var String
	 */
	protected $template_data;
	
	/**
	 * Construct!
	 *
	 * @param string $identifier
	 * @param Template_ITokenProvider $provider
	 */
	public function __construct($identifier, Template_ITokenProvider $provider)
	{
		$this->setTokenIdentifier($identifier);
		$this->setProvider($provider);	
	}
	
	/**
	 * Set the token provider
	 *
	 * @param Template_ITokenProvider $provider
	 * @return void
	 */
	public function setProvider(Template_ITokenProvider $provider)
	{
		$this->provider = $provider;
	}
	
	/**
	 * Sets the token identifier to use
	 *
	 * @param string $id
	 * @return void
	 */
	public function setTokenIdentifier($id)
	{
		$this->token_identifier = $id;
	}

	/**
	 * Sets the template data that we'll 
	 * render using the token provider
	 *
	 * @param string $data
	 * @return void
	 */
	public function setTemplateData($data)
	{
		$this->template_data = $data;
	}
	
	/**
	 * Gets all of the tokens as they appear in
	 * this document. In order!
	 *
	 * @param Boolean $with_identifier
	 * @return void
	 */
	public function getTokens($with_identifier = FALSE)
	{
		$tokens = array();
		preg_match_all('/'.$this->token_identifier.'(\w+)'.$this->token_identifier.'/',
			$this->template_data, $tokens);
		return $with_identifier ? $tokens[0] : $tokens[1];
	}
	
	/**
	 * Call back for the preg_replace really
	 *
	 * @param array $data
	 * @return string
	 */
	public function processToken($data)
	{
		return $this->provider->getTokenValue($data[1]);
	}
	
	/**
	 * Parses the template data and returns
	 * it rendered using tokens from the
	 * provider.
	 *
	 * @param string $data
	 * @return string
	 */
	public function parse($data = NULL)
	{
		if (!is_null($data))
		{
			$this->setTemplateData($data);
		}
		return preg_replace_callback(
			'/'.$this->token_identifier.'([\w]+)'.$this->token_identifier.'/',
			array($this, 'processToken'),
			$this->template_data
		);
	}

}