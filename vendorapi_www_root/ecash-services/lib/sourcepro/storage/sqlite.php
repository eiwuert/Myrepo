<?php
/**
	sqlite storage engine.
*/

class SourcePro_Storage_Sqlite_Query
{
	function __construct ($store, $query)
	{
		$this->m_store = $store;
		$this->m_query = $query;
	}

	public function Execute ($arg = NULL)
	{
		$query = $this->m_query;

		if (is_array ($arg) && count ($arg))
		{
			$part = explode ('?', $query);
			if (count($part) != count ($arg)+1)
			{
				throw new SourcePro_Exception ("invalid number of paramaters:\n{$query}\n".print_r ($arg, 1), 1000);
			}

			$query = array_shift ($part);
			foreach ($arg as $a)
			{
				$query .= "'".sqlite_escape_string($a)."'".array_shift ($part);
			}
		}

		$ts0 = microtime (1);
		$this->m_res = @sqlite_unbuffered_query ($query, $this->m_store->m_link);
		$ts1 = microtime (1);
		$this->m_store->timer_query += $ts1 - $ts0;

		if ($this->m_res === FALSE)
		{
			throw new SourcePro_Exception('sqlite_query failed - '.sqlite_error_string(sqlite_last_error($this->m_store->m_link))."\n{$query}\n", 1000);
		}
	}

	function Fetch_Array ()
	{
		return @sqlite_fetch_array ($this->m_res, SQLITE_ASSOC);
	}

	function Fetch_Object ()
	{
		return @sqlite_fetch_object ($this->m_res);
	}


}

class SourcePro_Storage_Sqlite extends SourcePro_Storage_Base
{

	/// The php resource representing the db connection
	public $m_link;

	/// Catalog of alias -> host:port mappings
	public $m_catalog = array (
		'local' => '/var/lib/sqlite/local/live/',
	);

	/// The currently open schema
	public $m_open_schema = NULL;

	/// Total time spent connecting
	public $timer_connect = 0;

	/// Total time for queries executed
	public $timer_query = 0;


	/**
		Initializes an instance of this class.

		@param data		The optional array containing initial field values.
	*/
	function __construct ($data = NULL)
	{
		parent::__construct($data);

		$this->m_catalog = array (
			'local' => '/var/lib/sqlite/local/'.$_SERVER['VMODE'].'/',
		);
	}

	function Is_Open ()
	{
		return $this->m_link ? 1 : 0;
	}

	function Open ($schema)
	{
		if (! $this->m_link || $this->m_open_schema != $schema)
		{
			$host = isset ($this->m_catalog[$this->alias]) ? $this->m_catalog[$this->alias] : $this->alias;
			$errmsg = '';
			$ts0 = microtime(1);
			$this->m_link = @sqlite_popen ($host.$schema, 0666, $errmsg);
			$ts1 = microtime(1);
			$this->timer_connect += $ts1 - $ts0;

			if ($this->m_link === FALSE )
			{
				throw new SourcePro_Exception('sqlite_connect failed - '.$errmsg, 1000);
			}
			sqlite_busy_timeout($this->m_link, 10000);
		}
	}

	function Close ()
	{
		$this->m_link = NULL;
	}

	public function Prepare ($query, $schema = NULL)
	{
		if (! $this->m_link)
		{
			try
			{
				$this->Open ($schema);
			}
			catch (Exception $e)
			{
				throw $e;
			}
		}

		return new SourcePro_Storage_Sqlite_Query ($this, $query);
	}

	public function Execute ($query, $arg = NULL, $schema = NULL)
	{
		try
		{
			$sql = $this->Prepare ($query, $schema);
			$sql->Execute ($arg);
		}
		catch (Exception $e)
		{
			throw $e;
		}
		return $sql;
	}

	public function Insert_Id ()
	{
		return @sqlite_last_insert_rowid ($this->m_link);
	}


	/**
		Selects an object from this store.

		@param obj		The object to select.
		@param what	What we want back from the select.
		@param join		Perform joins flag
	*/
	private function Select_Object ($obj, $what, $join)
	{
		$index = NULL;
		foreach  ($obj->v_index as $idx)
		{
			foreach ($idx as $field)
			{
				if (is_null ($obj->{$field}))
				{
					continue 2;
				}
			}
			$index = $idx;
		}

		if (is_null ($index))
		{
			throw new SourcePro_Exception("no indexes are complete", 1000);
		}


		$sql = "SELECT {$what} FROM {$obj->m_table} WHERE ";

		foreach ($index as $field)
		{
			$sql .= "{$obj->m_table}.{$field} = '".sqlite_escape_string($obj->{$field})."' AND ";
		}
		$sql = substr ($sql, 0, -5);

		try
		{
			$query = $this->Execute ($sql, NULL, $obj->m_schema);

			$rs = array ();
			while ($row = $query->Fetch_Array ())
			{
				$rs[] = $row;
			}
			return $rs;
		}
		catch (Exception $e)
		{
			throw $e;
		}

		return NULL;
	}

	private function Insert_Object ($obj)
	{
		$sql = "INSERT INTO {$obj->m_table} ";

		$field_name = $field_value = $arg = array();
		foreach ($obj->v_field as $name => $attribute)
		{
			switch ($attribute->m_role)
			{
				case SourcePro::ROLE_ID:
					break;

				case SourcePro::ROLE_MTIME:
				case SourcePro::ROLE_CTIME:
					$field_name[] = $name;
					$field_value[] = "'".date("Y-m-d H:i:s")."'";
					break;

				default:
					$field_name[] = $name;
					$field_value[] = "'".sqlite_escape_string($obj->{$name})."'";
			}
		}

		$sql .= '('.implode(', ', $field_name).') VALUES ';
		$sql .= '('.implode(', ', $field_value).')';

		$this->Execute ($sql, $arg, $obj->m_schema);

		if (is_object($obj->r_field_id))
		{
			$obj->r_field_id->_set($this->Insert_Id());
		}
	}

	private function Update_Object ($obj)
	{
		$sql = "UPDATE {$obj->m_table} ";

		$field_count = 0;
		$field_name = $field_value = $arg = array();
		foreach ($obj->v_field as $name => $attribute)
		{
			switch ($attribute->m_role)
			{
				case SourcePro::ROLE_ID:
				case SourcePro::ROLE_CTIME:
					break;

				case SourcePro::ROLE_MTIME:
					$field_name[] = $name;
					$field_value[] = "'".date("Y-m-d H:i:s")."'";
					$field_count++;
					break;

				default:
					$field_name[] = $name;
					$field_value[] = "'".sqlite_escape_string($obj->{$name})."'";
					$field_count++;
			}
		}

		$sql .= "SET ";
		for ($i = 0 ; $i < $field_count ; $i++)
		{
			$sql .= $field_name[$i].' = '.$field_value[$i].', ';
		}
		$sql = substr ($sql, 0, -2)." WHERE ".$obj->r_field_id->m_name." = ".$obj->r_field_id->_get();

		$this->Execute ($sql, $arg, $obj->m_schema);
	}

	/**
		Checks if an object is in  this store.

		@param obj		The object to find.
	*/
	public function Exist_Object ($obj)
	{
		try
		{
			$rs = $this->Select_Object ($obj, "count(*) AS n", FALSE);
			return $rs[0]['n'];
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	/**
		Loads an object from this store.

		@param obj		The object to load.
	*/
	public function Load_Object ($obj)
	{
		try
		{
			if ($obj->f_autocreate)
			{
				if (! $this->Exist_Object ($obj))
				{
					$this->Insert_Object ($obj);
					return NULL;
				}
			}
			return $this->Select_Object ($obj, "*", FALSE);
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	/**
		Saves an object to this store.

		@param obj		The object to save.
	*/
	public function Save_Object ($obj)
	{
		foreach ($obj->v_relation as $a)
		{
			$a->Save();
			switch ($a->m_type)
			{
				case SourcePro::LINK_INTERNAL:
					//print_r($a->m_entity); exit;
					$obj->{$a->m_field} = $a->m_entity->Get_Id ();
					break;
			}
		}

		if (! $obj->Get_Id ())
		{
			$key = $obj->Get_Key ();
			if ($key && $this->Exist_Object ($obj))
			{
				$name = $obj->r_field_id->m_name;
				$row = $this->Select_Object ($obj, $name, FALSE);
				$this->$name = $row[$name];

				$this->Update_Object ($obj);
			}
			else
			{
				$this->Insert_Object ($obj);
			}
		}
		else
		{
			$this->Update_Object ($obj);
		}

		foreach ($obj->v_relation as $a)
		{
			$a->Save();
			switch ($a->m_type)
			{
				case SourcePro::LINK_INTERNAL:
					$obj->r_field_id->_set($a->m_entity->Get_Id ());
					break;
			}
		}

	}

}

?>
