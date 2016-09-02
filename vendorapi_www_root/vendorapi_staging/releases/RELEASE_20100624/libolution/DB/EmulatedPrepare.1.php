<?php
	/**
	 * Emulated prepare
	 *
	 * @package DB
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */

	/**
	 * Class for emulating PDO's prepare functionality. Used by some DB adapters for
	 * compatibility with other libolution libraries
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class DB_EmulatedPrepare_1
	{
		const IDENT_MASK = '_0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @var string
		 */
		protected $query;

		/**
		 * True if the query contains named params
		 * @var bool
		 */
		protected $has_named = FALSE;

		/**
		 * True if the query contains indexed params
		 * @var bool
		 */
		protected $has_indexed = FALSE;

		/**
		 * @param DB_IConnection_1 $db
		 * @param string $query The query to use
		 */
		public function __construct(DB_IConnection_1 $db, $query)
		{
			$this->db = $db;
			$this->query = $query;
			$this->checkQueryParams($this->query);
		}

		/**
		 * Returns the properly quoted query back with tokens replaced
		 *
		 * @param array $data The data that you would normally pass to PDOStatement::execute()
		 * @return string
		 */
		public function getQuery(array $data = NULL)
		{
			if ($data !== NULL)
			{
				$is_named = $this->checkParamsType($data);

				return ($is_named)
					? $this->replaceNamedParameters($this->query, $data)
					: $this->replaceParameters($this->query, $data);
			}
			return $this->query;
		}

		/**
		 * Replaces named parameters in a query
		 *
		 * @param string $query
		 * @param array $data
		 * @return string
		 */
		protected function replaceNamedParameters($query, array $data)
		{
			$output = '';
			$last = 0;
			$used = array();

			while (($offset = strpos($this->query, ':', $last)) !== FALSE)
			{
				$len = strspn($this->query, self::IDENT_MASK, $offset + 1);
				$tok = substr($this->query, $offset + 1, $len);

				if (!array_key_exists($tok, $data))
				{
					throw new Exception("Missing parameter, {$tok}");
				}
				elseif (in_array($tok, $used))
				{
					throw new Exception("Parameter '{$tok}' cannot be used twice");
				}

				$output .= substr($this->query, $last, ($offset - $last))
					.$this->quote($data[$tok]);

				$last = ($offset + $len + 1);
				$used[] = $tok;
			}

			$output .= substr($this->query, $last);
			return $output;
		}

		/**
		 * Replaces non-named parameters in a query
		 *
		 * @param string $query
		 * @param array $data
		 * @return string
		 */
		protected function replaceParameters($query, array $data)
		{
			$parts = explode('?', $query);
			$output = array_shift($parts);
			$index = 0;

			if (count($data) != count($parts))
			{
				throw new Exception('Not enough parameters for query');
			}

			foreach ($parts as $part)
			{
				$output .= $this->quote($data[$index++]).$part;
			}

			return $output;
		}

		/**
		 * Given a piece of data, return it properly quoted for use in a query
		 *
		 * @param mixed $data
		 * @return string
		 */
		protected function quote($data)
		{
			if ($data === NULL)
			{
				return 'NULL';
			}
			return $this->db->quote($data);
		}

		/**
		 * Checks a query for mixed parameter types
		 *
		 * @param string $query
		 * @return void
		 */
		protected function checkQueryParams($query)
		{
			$this->has_named = (strpos($this->query, ":") !== FALSE);
			$this->has_indexed = (strpos($this->query, "?") !== FALSE);

			if ($this->has_named && $this->has_indexed)
			{
				throw new Exception("Mixed parameters in query.");
			}
		}

		/**
		 * Performs checks to see if the params are valid.
		 *
		 * @param array $args
		 * @return bool
		 */
		protected function checkParamsType($args)
		{
			$is_named = FALSE;
			$is_indexed = FALSE;

			foreach ($args as $key=>$val)
			{
				if (is_string($key))
				{
					$is_named = TRUE;
				}
				else if (is_int($key))
				{
					$is_indexed = TRUE;
				}
			}

			if ($is_indexed && $is_named)
			{
				throw new Exception("Mixed parameters passed to prepare");
			}
			elseif ($is_named !== $this->has_named)
			{
				throw new Exception("Passed indexed parameters to named query string.");
			}
			else if ($is_indexed !== $this->has_indexed)
			{
				throw new Exception("Passed named parameters to indexed query string.");
			}

			return $is_named;
		}
	}
?>
