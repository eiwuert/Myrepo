<?php

class Site_Response_Http implements Site_IResponse
{
	/**
	 * HTTP response code
	 * @var int
	 */
	protected $code = 200;

	/**
	 * HTTP status message
	 * @var string
	 */
	protected $message = 'OK';

	/**
	 * HTTP headers (name=>value)
	 * @var array
	 */
	protected $header = array();

	/**
	 * Cookies (name=>value)
	 * @var array
	 */
	protected $cookie = array();

	/**
	 * Response content
	 * @var mixed
	 */
	protected $content;

	/**
	 * @param Site_IResponse $response
	 * @param unknown_type $code
	 * @param unknown_type $message
	 */
	public function __construct($content = NULL, $code = 200, $message = 'OK')
	{
		$this->content = $content;
		$this->code = $code;
		$this->message = $message;
	}

	/**
	 * Returns the HTTP status code.
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->code;
	}

	/**
	 * Returns the HTTP status message.
	 * @return string
	 */
	public function getStatus()
	{
		return $this->message;
	}

	/**
	 * Gets the "inner" response
	 *
	 * @return Site_IResponse
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * Sets a cookie on the response
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setCookie($name, $value)
	{
		$this->cookie[$name] = $value;
	}

	/**
	 * Sets a header on the response
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setHeader($name, $value)
	{
		$this->header[$name] = $value;
	}

	/**
	 * Renders the response, including headers, etc.
	 */
	public function render()
	{
		header('HTTP/1.1 '.$this->code.' '.$this->message);

		foreach ($this->header as $name=>$value)
		{
			header($name.': '.$value);
		}

		foreach ($this->cookie as $name=>$value)
		{
			setcookie($name, $value);
		}

		if ($this->content instanceof Site_IResponse)
		{
			$this->content->render();
		}
		else
		{
			echo $this->content;
		}
	}
}

?>
