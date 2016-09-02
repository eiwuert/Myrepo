<?php

require_once 'prpc/client.php';

/**
 * GSO class (copied from BBXAdmin)
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLP_GSO
{
	const PRPC_URL = 'prpc://live.globalsignon.com/prpc.php';
	const URL = 'http://live.globalsignon.com';
	
	const TIMEOUT = '-30 minutes';
	
	/**
	 * The name of the cookie to use.
	 *
	 * @var string
	 */
	protected $cookie_name;
	
        /**
         * The value of the cookie. We store it here incase we need to set and get cookie in the
		 * same instantaion of the object
         *
         * @var string
         */
        protected $cookie_value;

	/**
	 * PRPC client to the GSO server.
	 *
	 * @var Prpc_Client
	 */
	protected $gso_server;
	
	/**
	 * Customer short identifier use to determine which tree to use for authorization requests
	 *
	 * @var string
	 */
	protected $gso_customer_short;
	
	/**
	 * Sets the name of the cookie.
	 *
	 * @param Prpc_Client $gso_server
	 * @param string $cookie_name
	 * @param string $gso_customer_short Customer short for authorization requests
	 */
	public function __construct(Prpc_Client $gso_server, $cookie_name = 'gso_key', $gso_customer_short = 'olp')
	{
		$this->cookie_name = $cookie_name;
		$this->gso_server = $gso_server;
		$this->gso_customer_short = $gso_customer_short;
	}
	
	/**
	 * Checks GSO with the default settings.
	 *
	 * Returns the username if successful.
	 *
	 * @return string
	 */
	public function check()
	{
		$username = FALSE;
		
		if ($this->getCookie())
		{
			if (FALSE == ($username = $this->validateKey($_COOKIE[$this->cookie_name])))
			{
				$this->redirectToGSO();
			}
		}
		elseif (isset($_REQUEST['key']) && (FALSE != ($username = $this->validateKey($_REQUEST['key']))))
		{
			$this->setCookie($_REQUEST['key']);
		}
		else
		{
			$this->redirectToGSO();
		}
		
		return $username;
	}
	
	/**
	 * Validates the key.
	 *
	 * @param string $key
	 * @return string|bool
	 */
	public function validateKey($key)
	{
		$username = $this->gso_server->validateKey($key);
		
		if (!$username)
		{
			$this->resetCookie();
		}
		
		return $username;
	}
	
	/**
	 * Redirects to GSO and passes the redirect based on the current URL.
	 *
	 * If full_url is TRUE, request parameters will be kept. If FALSE, they'll be removed.
	 *
	 * @param boolean $full_url
	 * @param string $redirect Redirect URL
	 * @return void
	 */
	public function redirectToGSO($full_url = TRUE, $redirect = NULL)
	{
		if (empty($redirect))
		{
			$is_https = !empty($_SERVER['HTTPS']);
			$prefix = $is_https ? 'https://' : 'http://';
	
			$redirect = $prefix . $_SERVER['SERVER_NAME'];
		
			if ((!$is_https && $_SERVER['SERVER_PORT'] != 80)
				|| ($is_https && $_SERVER['SERVER_PORT'] != 443))
			{
				$redirect .= ':'.$_SERVER['SERVER_PORT'];
			}
	
			if ($full_url)
			{
				$redirect .= $_SERVER['REQUEST_URI'];
			}
		}
	
		header('Location: ' . self::URL . '/?v=2&r=' . urlencode(base64_encode($redirect)));
		exit();
	}
	
	/**
	 * Sets the GSO cookie.
	 *
	 * @param unknown_type $key
	 * @return void
	 */
	public function setCookie($key)
	{
		setcookie($this->cookie_name, $key, 0, '/');
		$this->cookie_value = $key;
	}

	/**
	 * Get the value of the GSO cookie
	 *
	 * @return string
	 */
	public function getCookie()
	{
		if (empty($this->cookie_value) && isset($_COOKIE[$this->cookie_name]))
		{
			$this->cookie_value = $_COOKIE[$this->cookie_name];
		}
		return $this->cookie_value;
	}
	
	/**
	 * Returns the cookie name.
	 *
	 * @return string
	 */
	public function getCookieName()
	{
		return $this->cookie_name;
	}
	
	/**
	 * Expires the GSO key and kills the cookie
	 *
	 * @return void
	 */
	public function logoutGSO()
	{
		$this->gso_server->expireKey($_REQUEST[$this->cookie_name]);
		$this->resetCookie();
	}

	/**
	 * Get the current users permissions for the current GSO Customer
	 *
	 * @return array
	 */
	public function getUserPermissions()
	{
		$user_data = $this->getUserData();
		return $this->gso_server->getUserPermissions($this->gso_customer_short, $user_data['user_name']);
	}
	
	/**
	 * Does the current user have authorization for the supplied key
	 *
	 * @param string $permission_key Key name for permission item in the current customer tree
	 * @return bool
	 */
	public function hasPermission($permission_key)
	{
		$user_data = $this->getUserData();
		$access = $this->gso_server->getPermissionStatus($this->gso_customer_short, $permission_key, $user_data['user_name']);
		return ($access === 'all' || $access === 'part');
	}

	/**
	 * Get the current user's data from GSO
	 *
	 * @return array
	 */
	public function getUserData()
	{
		return $this->gso_server->getUserData($this->getCookie());
	}
	
	/**
	 * Resets cookie.
	 *
	 * @return void
	 */
	protected function resetCookie()
	{
		setcookie($this->cookie_name, '', strtotime(self::TIMEOUT), '/');
	}
	
}
