<?php

/**
 * The site request to process.
 *
 * Provides an iterator for the request as well as array access to the request.
 * Normally the $_REQUEST superglobal will be passed in here and if at all
 * possible it would be considered a best practice to unset the super global
 * once the request is passed in.
 *
 * One may be tempted to extend this and make it a singleton. I would however
 * recommend avoiding this completely as it would encourage access to the
 * request in places that quite frankly shouldn't have it.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Site
 */
class Site_Request implements ArrayAccess, IteratorAggregate
{
	const SCHEME_HTTP = 'http';
	const SCHEME_HTTPS = 'https';

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	/**
	 * Creates a request from the standard PHP globals
	 *
	 * If $unset is TRUE, the globals will be unset, ensuring that
	 * they're not accessed throughout your application. Unsetting
	 * the superglobals is HIGHLY RECOMMENDED.
	 *
	 * @param bool $unset
	 * @return Site_Request
	 */
	public static function fromGlobals($unset = TRUE, $class = '')
	{
		// have to do this first because we're going
		// to provide $data to the constructor
		$method = strtoupper($_SERVER['REQUEST_METHOD']);
		$data = ($method === self::METHOD_POST ? $_POST : $_GET);

		if (!$class) $class = __CLASS__;

		$r = new $class($data);
		$r->method = $method;
		$r->query = $_GET;
		$r->post = $_POST;
		$r->cookie = $_COOKIE;
		$r->info = $_SERVER;

		if ($unset)
		{
			unset($_REQUEST, $_SERVER, $_GET,
				$_POST, $_COOKIE);
		}

		return $r;
	}

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @var array
	 */
	protected $query = array();

	/**
	 * @var array
	 */
	protected $post = array();

	/**
	 * @var array
	 */
	protected $cookie = array();

	/**
	 * @var array
	 */
	protected $info = array();

	/**
	 * Either query or post data, depending on the method
	 * @var array
	 */
	protected $data = array();

	/**
	 * Creates a new request.
	 * @deprecated Use ::fromGlobals()
	 * @param array $data
	 */
	public function __construct(array $data)
	{
		$this->data = $data;
	}

	/**
	 * Returns the HTTP method used in the request
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * Gets a full URL for the current page
	 *
	 * @return string
	 */
	public function getURL()
	{
		$url = $this->getScheme().'://'
			.$this->info['SERVER_NAME'];

		if ($this->info['SERVER_PORT'] != '80')
		{
			$url .= ':'.$info['SERVER_PORT'];
		}

		$url .= $this->getURI();
		return $url;
	}

	/**
	 * Returns the URI scheme used in the request (http/https)
	 *
	 * @return string
	 */
	public function getScheme()
	{
		// IIS uses off?
		$https = (!empty($this->info['HTTPS'])
			&& $this->info['HTTPS'] != 'off');

		return ($https ? self::SCHEME_HTTPS : self::SCHEME_HTTP);
	}

	/**
	 * Indicates whether the request was made securely via HTTPS
	 *
	 * @return bool
	 */
	public function isSecure()
	{
		return ($this->getScheme() === self::SCHEME_HTTPS);
	}

	/**
	 * Returns the URI used in the request (e.g., /index.php)
	 *
	 * @return string
	 */
	public function getURI()
	{
		return $this->info['REQUEST_URI'];
	}

	/**
	 * Gets a query ($_GET) variable
	 *
	 * If the named variable doesn't exist, returns FALSE
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getQuery($name)
	{
		return isset($this->query[$name])
			? $this->query[$name]
			: FALSE;
	}

	/**
	 * Gets a post ($_POST) variable
	 *
	 * If the named variable doesn't exist, returns FALSE
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getPost($name)
	{
		return isset($this->post[$name])
			? $this->post[$name]
			: FALSE;
	}

	/**
	 * Gets a request cookie ($_COOKIE)
	 *
	 * If the named cookie doesn't exist, returns FALSE
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getCookie($name)
	{
		return isset($this->cookie[$name])
			? $this->cookie[$name]
			: FALSE;
	}

	/**
	 * Gets the value of an HTTP header in the request
	 *
	 * If the named header doesn't exist, returns FALSE
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getHeader($name)
	{
		// all HTTP headers are stored
		// in $_SERVER prefixed with HTTP_
		$header = 'HTTP_'.strtoupper($name);

		return isset($this->info[$header])
			? $this->info[$header]
			: FALSE;
	}


	/**
	 * Determines if the given field has been set in the request.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->data);
	}

	/**
	 * Returns the value of the given field in the request.
	 *
	 * Returns NULL of the field does not exist.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : NULL;
	}

	/**
	 * This method is not supported
	 *
	 * @param string $name
	 * @param mixed $value
	 * @throws BadMethodCallException
	 */
	public function offsetSet($name, $value)
	{
		throw new BadMethodCallException("You cannot set request variables manually (attempted to set '{$name}' to '{$value}'.)");
	}

	/**
	 * This method is not supported
	 *
	 * @param string $name
	 * @throws BadMethodCallException
	 */
	public function offsetUnset($name)
	{
		throw new BadMethodCallException("You cannot unset request variables manually. (attempted to unset '{$name}'.");
	}

	/**
	 * Returns the iterator for the request data.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}
}

?>
