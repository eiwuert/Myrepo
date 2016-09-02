<?php

	/**
	 *
	 * Implements basic URL-based "callback" functionality, as well as
	 * PRPC based callbacks, depending on the URL scheme. Tokens are
	 * specified in the URL as %%token_name%%.
	 *
	 * PRPC callbacks use the following format:
	 *
	 *
	 * (Note that the argument names for a PRPC callback don't matter;
	 * the arguments are passed to the function in the order they appear.)
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 *
	 */
	
	class Callback
	{
		protected $url;

		public function __construct($url)
		{
			$this->url = $url;
		}

		public function Process($tokens)
		{

			if (is_array($tokens))
			{
				// replace tokens in the URL
				$url = preg_replace('/%%([^%]+)%%/e', "isset(\$tokens[\$t = strtolower('\\1')]) ? urlencode(\$tokens[\$t]) : ''", $this->url);
			}
			else
			{
				$url = $this->url;
			}

			// PRPC callbacks are arranged as follows:
			// prpc://host[:port]/filename/function?arg1=test
			if (substr($url, 0, 7) === 'prpc://')
			{
				$this->PRPC_Callback($url);
			}
			else
			{
				file_get_contents($url);
			}

			return;

		}
		protected function PRPC_Callback($info)
		{

			require_once('prpc/client.php');

			if (!is_array($info))
			{
				$info = parse_url($info);
			}

			// parse out the function name
			$last = strrpos($info['path'], '/');

			if ($last !== FALSE)
			{

				$function = substr($info['path'], ($last + 1));
				$info['path'] = substr($info['path'], 0, $last);

				if (isset($info['query']))
				{
					parse_str($info['query'], $args);
				}
				else
				{
					$args = array();
				}

				// rebuild the URL
				$url = $info['scheme'].'://'.$info['host'].(isset($info['port']) ? ':'.$info['port'] : '').$info['path'];

				// make the PRPC call
				$client = new PRPC_Client($url);
				call_user_func_array(array(&$client, $function), $args);

			}

			return;

		}

	}

?>
