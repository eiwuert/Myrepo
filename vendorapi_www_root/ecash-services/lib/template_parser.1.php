<?php
/**
 * Class to parse templates for tokens.
 *
 * @author Brian Feaver
 * @copyright 2006 The Selling Source, Inc.
 */
class Template_Parser
{
	private $token_id;
	private $subject;
	private $tokens;
	private $errors;
	private $rendered_template;
	
	function __construct($subject, $token_id)
	{
		$this->token_id = $token_id;
		$this->subject = $subject;
		$this->tokens = NULL;
		$this->errors = array();
	}
	
	/**
	 * Sets the subject for the parser.
	 *
	 * @param string $subject
	 */
	public function Set_Subject($subject)
	{
		$this->subject = $subject;
		$this->tokens = NULL; // We'll need to get a new list of tokens
	}
	
	/**
	 * Returns a list of tokens. If with_token_ids is TRUE, it will return the tokens with
	 * the token_ids attached (ie. %%%TOKEN%%% if the token ID is %%%)
	 *
	 * @return array
	 */
	public function Get_Tokens($with_token_ids = FALSE)
	{
		$this->tokens = NULL;
		
		preg_match_all('/'.$this->token_id.'(\w+)'.$this->token_id.'/', $this->subject, $this->tokens);
		
		return $with_token_ids ? $this->tokens[0] : $this->tokens[1];
	}
	
	/**
	 * Renders the template and returns a string containing it. This expects $data to be an
	 * associative array with the keys being the token names without the token identifiers
	 * (ie. TOKEN not %%%TOKEN%%%). If there were any errors in the rendering of the template,
	 * such as missing tokens, you will need to check for errors by making a call to
	 * Get_Errors().
	 *
	 * @param array $data
	 * @return string
	 */
	public function Get_Rendered_Template($data)
	{
		// Reset errors
		$this->errors = array();
		
		if($this->tokens == NULL)
		{
			$this->Get_Tokens();
		}
		
		// We don't want to try and replace tokens more than once
		$this->tokens[1] = array_unique($this->tokens[1]);
		
		foreach($this->tokens[1] as $token)
		{
			if(!in_array($token, array_keys($data)))
			{
				$this->errors[] = "Unable to find token, $token, in data array";
			}
		}
		
		$this->rendered_template = $this->subject;

		foreach($this->tokens[0] as $token)
		{
			// If the token didn't exist in the data passed in, replace it with an empty string
			$replacement = isset($data[trim($token, '%')]) ? $data[trim($token, '%')] : '';
			
			$this->rendered_template = str_replace($token, $replacement, $this->rendered_template);
		}

		return $this->rendered_template;
	}
	
	/**
	 * Returns an array of error strings or an empty array if there are no errors.
	 *
	 * @return array
	 */
	public function Get_Errors()
	{
		return $this->errors;
	}
}
?>
