<?php
	
	/**
	 *
	 * Implements basic URL-based "callback" functionality, as well as
	 * PRPC based callbacks, depending on the URL scheme. Tokens are
	 * specified in the URL as %%token_name%%.
	 *
	 * PRPC callbacks use the following format:
	 *
	 * prpc://host[:port]/filename.php/function?arg1=test
	 *
	 * (Note that the argument names for a PRPC callback don't matter;
	 * the arguments are passed to the function in the order they appear.)
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
			
			$url = $this->url;
			
			// PRPC callbacks are arranged as follows:
			// prpc://host[:port]/filename/function?arg1=test
			if (substr($url, 0, 7) === 'prpc://')
			{
				$return = $this->PRPC_Callback($url, $tokens);
			}
			else
			{
				
				if (is_array($tokens))
				{
					// replace tokens in the URL
					$url = preg_replace('/%%([^%]+)%%/e', "isset(\$tokens[\$t = strtolower('\\1')]) ? urlencode(\$tokens[\$t]) : ''", $this->url);
				}
				
				$return = file_get_contents($url);
				
			}
			
			return $return;
			
		}
		
		protected function PRPC_Callback($url, &$tokens)
		{
			
			require_once('prpc/client.php');
			
			$return = NULL;
			
			// parse out the function name
			$last = strrpos($url, '/');
			
			if ($last !== FALSE)
			{
				
				$function = substr($url, ($last + 1));
				$url = substr($url, 0, $last);
				
				// check for a query string
				if (($last = strrpos($function, '?')) !== FALSE)
				{
					
					// parse out the query variables
					parse_str(substr($function, ($last + 1)), $args);
					
					// strip the query string off of the function name
					$function = substr($function, 0, $last);
					
					// replace these here to allow large variables to be passed back in a
					// callback (just in case!) without making parse_str parse a HUGE url
					foreach ($args as &$value)
					{
						
						if ((substr($value, 0, 2) == '%%') && (substr($value, -2) == '%%'))
						{
							$value = (isset($tokens[substr($value, 2, -2)]) ? $tokens[substr($value, 2, -2)] : '');
						}
						else
						{
							$value = preg_replace('/%%([^%]+)%%/e', "isset(\$tokens['\\1']) ? urlencode(\$tokens['\\1']) : ''", $value);
						}
						
					}
					
				}
				else
				{
					$args = array();
				}
				
				try
				{
					// make the PRPC call
					$client = new PRPC_Client($url);
					$return = call_user_func_array(array(&$client, $function), $args);
				}
				catch (Exception $e)
				{
					// gulp
				}
				
			}
			
			return $return;
			
		}
		
	}
	
?>
