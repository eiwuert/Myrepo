<?php
	/**
	 * @package Session
	 *
	 * @todo We should probably add (correct) return codes to every method.
	 */

	/**
	 * A database session wrapper
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Session_DatabaseSession_1 implements ArrayAccess
	{
		/**
		 * @var string
		 */
		protected $db_alias;

		/**
		 * @var string
		 */
		protected $session_column_id = 'session_id';

		/**
		 * @var string
		 */
		protected $session_column_data = 'session_data';

		/**
		 * @param string $db_alias Alias for connection in DB_DatabaseConnectionPool_1
		 * @param string $name Session name (see http://php.net/session_name)
		 * @param string $column_name_id Column containing the session ID
		 * @param string $column_name_data Column containing the session data
		 * @return void
		 */
		public function __construct($db_alias, $name = NULL, $id = NULL, $column_name_id = NULL, $column_name_data = NULL)
		{
			$this->db_alias = $db_alias;
			if($column_name_id) $this->session_column_id = $column_name_id;
			if($column_name_data) $this->session_column_data = $column_name_data;

			$this->install();

			// set some session options
			if ($name) session_name($name);
			if ($id) session_id($id);

			session_start();
		}

		/** Overloading */

		/**
		 * Allows overloaded access to session data
		 * @deprecated
		 *
		 * @param mixed $name
		 * @return mixed
		 */
		public function __get($name)
		{
			return $_SESSION[$name];
		}

		/**
		 * Sets the session variable $name
		 * @deprecated
		 *
		 * @param mixed $name
		 * @return mixed
		 */
		public function __set($name, $value)
		{
			$_SESSION[$name] = $value;
		}

		/**
		 * Indicates whether the session variable of $name exists
		 * @deprecated
		 *
		 * @param mixed $name
		 * @return mixed
		 */
		public function __isset($name)
		{
			return isset($_SESSION[$name]);
		}

		/**
		 * Unsets the session variable $name
		 * @deprecated
		 *
		 * @param mixed $name
		 * @return mixed
		 */
		public function __unset($name)
		{
			unset($_SESSION[$name]);
		}

		/** Required by ArrayAccess */

		/**
		 * Returns the session variable at $offset
		 *
		 * @param mixed $offset
		 * @return mixed
		 */
		public function offsetGet($offset)
		{
			return $_SESSION[$offset];
		}

		/**
		 * Sets the session variable $offset to $value
		 *
		 * @param mixed $offset
		 * @param mixed $value
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			if ($offset !== NULL)
			{
				$_SESSION[$offset] = $value;
			}
			else
			{
				$_SESSION[] = $value;
			}
		}

		/**
		 * Unsets the session variable $offset
		 *
		 * @param mixed $offset
		 * @return void
		 */
		public function offsetUnset($offset)
		{
			unset($_SESSION[$offset]);
		}

		/**
		 * Indicates whether session variable $offset exists
		 *
		 * @param mixed $offset
		 * @return bool
		 */
		public function offsetExists($offset)
		{
			return isset($_SESSION[$offset]);
		}

		/**
		 * Opens the session by name
		 * @param string $path
		 * @param string $name
		 * @return bool
		 */
		public function open($path, $name)
		{
			return TRUE;
		}

		/**
		 * Closes the session
		 * @return bool
		 */
		public function close()
		{
			return TRUE;
		}

		/**
		 * Reads session data
		 * @param string $id Session ID
		 * @return string
		 */
		public function read($id)
		{
			$db = DB_DatabaseConfigPool_1::getConnection($this->db_alias);

			$query = "
				SELECT {$this->session_column_data}
				FROM {$this->getTableByID($id)}
				WHERE {$this->session_column_id} = ?
				LIMIT 1
			";
			$stmt = DB_Util_1::queryPrepared($db, $query, array($id));

			if ($row = $stmt->fetch())
			{
				return $row[$this->session_column_data];
			}

			return '';

		}

		/**
		 * Writes session data to the database
		 * @param string $id Session ID
		 * @param string $data session data
		 * @return bool
		 */
		public function write($id, $data)
		{
			$db = DB_DatabaseConfigPool_1::getConnection($this->db_alias);

			$query = "
				INSERT INTO {$this->getTableByID($id)} ({$this->session_column_id}, {$this->session_column_data})
				VALUES (?, ?)
				ON DUPLICATE KEY UPDATE
					{$this->session_column_data} = VALUES({$this->session_column_data}),
					date_modified = NOW()
			";

			$stmt = DB_Util_1::queryPrepared($db, $query, array($id));
			return TRUE;
		}

		/**
		 * Deletes a session from the database
		 * @param string $id Session ID
		 * @return bool
		 */
		public function destroy($id)
		{
			$db = DB_DatabaseConfigPool_1::getConnection($this->db_alias);

			$query = "
				DELETE FROM {$this->getTableByID($id)}
				WHERE {$this->session_column_id} = ?
				LIMIT 1
			";
			$stmt = $db->queryPrepared($query, array($id));
			return TRUE;
		}

		/**
		 * Runs garbage collection
		 * @param int $max_lifetime
		 * @return bool
		 */
		public function cleanUp($max_lifetime)
		{
			return TRUE;
		}

		/**
		 * Installs ourselves as the active session handler
		 * @return void
		 */
		protected function install()
		{
			session_set_save_handler(
				array($this, 'open'),
				array($this, 'close'),
				array($this, 'read'),
				array($this, 'write'),
				array($this, 'destroy'),
				array($this, 'cleanUp')
			);
		}

		/**
		 * Provided only to be overridden, allows you to segregate
		 * session data into separate tables by ID. Return the tablename.
		 * @param string $session_id Session ID
		 * @return string Table name
		 */
		protected function getTableByID($session_id)
		{
			return 'session';
		}
	}

?>
