<?php

/**
 * Provides a response based on the contents of a php 'template'
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Site
 */
class Site_Response_Templated implements Site_IResponse
{

	/**
	 * @var string
	 */
	protected $template;

	/**
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * @var string
	 */
	protected $base_dir;

	/**
	 * Creates a new response using the specified template.
	 *
	 * The $template should be a path relative to the $base_dir. If no
	 * $base_dir is provided then it should be relative to the current working
	 * directory at the time render() is called.
	 *
	 * @param string $template
	 * @param string $base_dir
	 */
	public function __construct($template, $base_dir = NULL)
	{
		$this->template = $template;
		$this->base_dir = $base_dir;
	}

	/**
	 * Retrieves a token value.w
	 *
	 * @param string $name
	 * @return mixed
	 * @throws InvalidArgumentException when a token does not exist
	 */
	public function __get($name)
	{
		if (!$this->__isset($name))
		{
			throw new InvalidArgumentException('Invalid token, ' . $name);
		}

		return $this->tokens[$name];
	}

	/**
	 * Sets the value of a token.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->tokens[$name] = $value;
	}

	/**
	 * Determines if a token is set.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return array_key_exists($name, $this->tokens);
	}

	/**
	 * Renders the given template.
	 *
	 * @throws RuntimeException when a template could not be loaded.
	 */
	public function render()
	{
		$base_dir = $this->base_dir === NULL ? (getcwd() . '/templates') : rtrim($this->base_dir, '/');
		$old_include_path = set_include_path($base_dir . PATH_SEPARATOR . get_include_path());
		if ((include $this->template) === FALSE)
		{
			throw new RuntimeException("Could not load template '{$this->template}'. [base_dir:{$base_dir}]");
		}
		set_include_path($old_include_path);
	}
}

?>