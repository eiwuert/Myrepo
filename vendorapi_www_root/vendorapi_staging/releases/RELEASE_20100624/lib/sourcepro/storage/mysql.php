<?php
/**
	MySQL storage engine.
*/

class SourcePro_Storage_Mysql_Query
{
	function __construct ($store, $query)
	{
		$this->m_store = $store;
		$this->m_query = $query;
		$this->m_last_query = "";
	}

	function __destruct ()
	{
		unset($this->m_store);
	}

	public function Execute ($arg = NULL)
	{
		$query = $this->m_query;

		if (is_array ($arg) && count ($arg))
		{
			$part = preg_split ('/([?#])/', $query, -1, PREG_SPLIT_DELIM_CAPTURE);
			if (count($part) != count ($arg)*2+1)
			{
				throw new SourcePro_Exception ("invalid number of parameters:\n{$query}\n".print_r ($arg, 1), 1000);
			}

			$query = array_shift ($part);
			foreach ($arg as $a)
			{
				if (is_object ($a))
				{
					switch (get_class ($a))
					{
						case 'SourcePro_Time':
							$a = $a->m_time;
							break;

						default:
							throw new SourcePro_Exception('invalid object class ('.get_class($a).') used as parameter');
							break;
					}
				}
				$type = array_shift($part);
				switch ($type)
				{
					case '?':
						$query .= "'".mysql_escape_string($a)."'".array_shift($part);
						break;

					case '#':
						$query .= $a.array_shift($part);
						break;
				}
			}
		}

		$this->m_last_query = $query;

		$ts0 = microtime (1);
		$this->m_res = @mysql_query ($query, $this->m_store->m_link);
		$ts1 = microtime (1);
		$this->m_store->timer_query += $ts1 - $ts0;
		$this->m_store->count_query++;

		if ($this->m_res === FALSE)
		{
			throw new SourcePro_Exception('mysql_query failed - '.mysql_error($this->m_store->m_link)."\n{$query}\n", 1000);
		}
	}

	function Fetch_Array ()
	{
		return @mysql_fetch_assoc ($this->m_res);
	}

	function Fetch_Object ()
	{
		return @mysql_fetch_object ($this->m_res);
	}

	function Num_Rows ()
	{
		return @mysql_num_rows ($this->m_res);
	}


}

class SourcePro_Storage_Mysql extends SourcePro_Storage_Base
{

	/// The php resource representing the db connection
	public $m_link;

	/// Catalog of alias -> host:port mappings
	public $m_catalog;

	/// Total time spent connecting
	public $timer_connect = 0;

	/// Total time for queries executed
	public $timer_query = 0;
	public $count_query = 0;

	/**
		Initializes an instance of this class.

		@param data		The optional array containing initial field values.
	*/
	function __construct ($data = NULL)
	{
		parent::__construct($data);

		$catalog_file = "";

		switch (TRUE)
		{
			case (!empty($_SERVER["VLIB"]) && file_exists($_SERVER["VLIB"]."/catalog_mysql.php")):
				$catalog_file = $_SERVER["VLIB"]."/catalog_mysql.php";
				break;

			case (file_exists('/virtuallib/catalog_mysql.php')): 
				$catalog_file = "/virtuallib/catalog_mysql.php";
				break;
		}
		
		if (strlen ($catalog_file))
		{
			global $m_catalog;
			include ($catalog_file);
			$this->m_catalog = $m_catalog;
		}
		else
		{
			switch (@$_SERVER['VMODE'])
			{
				case 'live':
					$this->m_catalog = array (
						'local' => 'db01:3306',
					);
					break;

				case 'rc':
					$this->m_catalog = array (
						'local' => 'db01:3307',
					);
					break;

				default:
					$this->m_catalog = array (
						'local' => 'db01:3308',
					);
					break;
			}
		}
	}

	function Is_Open ()
	{
		return $this->m_link ? 1 : 0;
	}

	function Affected_Rows ()
	{
		return mysql_affected_rows ($this->m_link);
	}

	function Num_Rows ()
	{
		return mysql_num_rows ($this->m_link);
	}

	function Open ()
	{
		if (! $this->m_link)
		{
			$host = isset ($this->m_catalog[$this->alias]) ? $this->m_catalog[$this->alias] : $this->alias;
			$ts0 = microtime(1);
			$this->m_link = mysql_connect ($host, $this->user, $this->pass, false, MYSQL_CLIENT_COMPRESS);
			$ts1 = microtime(1);
			$this->timer_connect += $ts1 - $ts0;

			if ($this->m_link === FALSE )
			{
				throw new SourcePro_Exception('mysql_connect failed to ('.$this->alias.')=('.$this->m_catalog[$this->alias].') - '.mysql_error(), 1000);
			}
		}
	}

	function Close ()
	{
		$this->m_link = NULL;
	}

	public function Set_Schema ($schema)
	{
		$this->Open ();
		if (! mysql_select_db ($schema, $this->m_link))
		{
			throw new SourcePro_Exception('mysql_select_db failed - '.mysql_error($this->m_link), 1000);
		}
	}

	public function Prepare ($query)
	{
		if (! $this->m_link)
		{
			try
			{
				$this->Open ();
			}
			catch (Exception $e)
			{
				throw $e;
			}
		}

		return new SourcePro_Storage_Mysql_Query ($this, $query);
	}

	public function Execute ($query, $arg = NULL)
	{
		try
		{
			if (! $this->m_link)
				$this->Open ();

			$sql = $this->Prepare ($query);
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
		return @mysql_insert_id ($this->m_link);
	}


	/**
		Selects an object from this store.

		@param obj		The object to select.
		@param what		What we want back from the select.
		@param index	Array of columns to index on
	*/
	public function Select_Object ($obj, $what, $index = NULL)
	{
		if (is_null ($index))
		{
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
				break;
			}

			if (is_null ($index))
			{
				throw new SourcePro_Exception("no indexes are complete", 1000);
			}
		}

		$sql = "SELECT {$what} FROM {$obj->m_schema}.{$obj->m_table} WHERE ";

		foreach ($index as $field)
		{
			$sql .= "{$obj->m_schema}.{$obj->m_table}.{$field} = '".mysql_escape_string($obj->{$field})."' AND ";
		}
		$sql = substr ($sql, 0, -5);

		try
		{
			$query = $this->Execute ($sql);

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

	/**
		Inserts an object into this store.

		@param obj		The object to insert.
	*/
	public function Insert_Object ($obj)
	{
		$sql = "INSERT INTO {$obj->m_schema}.{$obj->m_table} ";

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
					$field_value[] = 'UNIX_TIMESTAMP()';
					break;

				default:
					$field_name[] = $name;
					$field_value[] = "'".mysql_escape_string($obj->{$name})."'";
			}
		}

		$sql .= '('.implode(', ', $field_name).') VALUES ';
		$sql .= '('.implode(', ', $field_value).')';

		$this->Execute ($sql, $arg);

		if (is_object ($obj->r_field_id))
		{
			$obj->r_field_id->_set($this->Insert_Id());
		}
	}

	/**
		Deletes an object from this store.

		@param obj		The object to delete.
	*/
	public function Delete_Object ($obj)
	{
		$id = $obj->r_field_id->_get();
		if (! $id)
		{
			throw new SourcePro_Exception ("Can not delete an object without an id", 1000);
		}

		$sql = "DELETE FROM {$obj->m_schema}.{$obj->m_table} WHERE ".$obj->r_field_id->m_name." = ".$id." LIMIT 1";

		try
		{
			$this->Execute ($sql);
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	/**
		Updates an object in this store.

		@param obj		The object to update.
	*/
	public function Update_Object ($obj)
	{
		$sql = "UPDATE {$obj->m_schema}.{$obj->m_table} ";

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
					$field_value[] = 'UNIX_TIMESTAMP()';
					$field_count++;
					break;

				default:
					$field_name[] = $name;
					$field_value[] = "'".mysql_escape_string($obj->{$name})."'";
					$field_count++;
			}
		}

		$sql .= "SET ";
		for ($i = 0 ; $i < $field_count ; $i++)
		{
			$sql .= $field_name[$i].' = '.$field_value[$i].', ';
		}
		$sql = substr ($sql, 0, -2)." WHERE ";
		
		if ($obj->r_field_id)
		{
			$sql .= $obj->r_field_id->m_name." = ".$obj->r_field_id->_get();
		}
		elseif ($obj->r_field_key)
		{
			$sql .= $obj->r_field_key->m_name." = '".mysql_escape_string($obj->r_field_key->_get())."'";
		}
		else
		{
			throw new SourcePro_Exception("No id or key for Update_Object");
		}

		$this->Execute ($sql, $arg);
	}

	/**
		Checks if an object is in  this store.

		@param obj		The object to find.
		@param index	Array of columns to index on
	*/
	public function Exist_Object ($obj, $index = NULL)
	{
		try
		{
			$rs = $this->Select_Object ($obj, "count(*) AS n", $index);
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
		@param index	Array of columns to index on
	*/
	public function Load_Object ($obj, $index = NULL)
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
			return $this->Select_Object ($obj, "*", $index);
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
					$obj->{$a->m_field} = $a->m_entity->Get_Id ();
					break;
			}
		}

		if (! $obj->Get_Id ())
		{
			$key = $obj->Get_Key ();
			if ($key && $this->Exist_Object ($obj))
			{
				if ($obj->r_field_id)
				{
					$name = $obj->r_field_id->m_name;
					$row = $this->Select_Object ($obj, $name);
					$this->$name = $row[$name];
				}

				$this->Update_Object ($obj);
			}
			else
			{
				$this->Insert_Object ($obj);
			}
		}
		elseif ($obj->f_changed)
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
