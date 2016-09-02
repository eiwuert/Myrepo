<?php
/**
	DB2 storage engine.
*/

class SourcePro_Storage_Db2_Query
{
	function __construct ($store, $query)
	{
		$this->m_store = $store;
		$this->m_query = $query;
		$this->m_sql = @odbc_prepare($this->m_store->m_link, $this->m_query);

		if ($this->m_sql === FALSE)
		{
			throw new SourcePro_Exception('odbc_prepare failed - '.odbc_errormsg($this->m_store->m_link)."\n$query\n", 1000);
		}
	}

	public function Execute ($arg = NULL)
	{
		if (is_null ($arg))
		{
			$res = @odbc_execute ($this->m_sql);
		}
		else
		{
			for ($i = 0 ; $i < count ($arg) ; $i++)
			{
				if (is_object ($arg[$i]))
				{
					switch (get_class ($arg[$i]))
					{
						case 'SourcePro_Time':
							$arg[$i] = date ('m/d/Y H:i:s', strtotime ($arg[$i]));
							break;

						default:
							throw new SourcePro_Exception('invalid object class ('.get_class($arg[$i]).') used as parameter');
							break;
					}
				}
			}
			$res = @odbc_execute ($this->m_sql, $arg);
		}

		if ($res === FALSE)
		{
			throw new SourcePro_Exception('odbc_execute failed - '.odbc_errormsg($this->m_store->m_link)."\n{$this->m_query}\n".print_r($arg,1)."\n", 1000);
		}
	}

	function Fetch_Array ()
	{
		return @odbc_fetch_array ($this->m_sql);
	}

	function Fetch_Object ()
	{
		return @odbc_fetch_object ($this->m_sql);
	}
}

class SourcePro_Storage_Db2 extends SourcePro_Storage_Base
{

	/// The php resource representing the db connection
	public $m_link;

	public $use_pconnect = FALSE;

	/**
		Initializes an instance of this class.

		@param data		The optional array containing initial field values.
	*/
	function __construct ($data = NULL)
	{
		parent::__construct($data);
	}

	function Is_Open ()
	{
		return $this->m_link ? 1 : 0;
	}

	function Open ()
	{
		if (! $this->m_link)
		{
			$connect_func = $this->use_pconnect ? "odbc_pconnect" : "odbc_connect";
			if ( ($this->m_link = @$connect_func ($this->alias, $this->user, $this->pass)) === FALSE )
			{
				throw new SourcePro_Exception($connect_func.' failed - '.odbc_errormsg($this->m_link), 1000);
			}
		}
	}

	function Close ()
	{
		$this->m_link = NULL;
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

		return new SourcePro_Storage_Db2_Query ($this, $query);
	}

	public function Execute ($query, $arg = NULL)
	{
		try
		{
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
		$res = @odbc_exec ($this->m_link, "values identity_val_local()");
		if ($res === FALSE)
		{
			throw new SourcePro_Exception('odbc_execute failed - '.odbc_errormsg($this->m_link), 1000);
		}

		$row = odbc_fetch_array ($res);
		$id = array_pop ($row);
		return $id;
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


		$sql = "SELECT {$what} FROM {$obj->m_schema}.{$obj->m_table} WHERE ";

		$arg = array ();
		foreach ($index as $field)
		{
			$arg[] = $obj->{$field};
			$sql .= "{$obj->m_schema}.{$obj->m_table}.{$field} = ? AND ";
		}
		$sql = substr ($sql, 0, -5)." FOR READ ONLY";

		try
		{
			$query = $this->Execute ($sql, $arg);

			if ($row = $query->Fetch_Array ($res))
			{
				return $row;
			}
		}
		catch (Exception $e)
		{
			throw $e;
		}

		return NULL;
	}

	private function Insert_Object ($obj)
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
					$field_value[] = 'CURRENT TIMESTAMP';
					break;

				default:
					$field_name[] = $name;
					$field_value[] = "?";
					$arg[] = $obj->{$name};
			}
		}

		$sql .= '('.implode(', ', $field_name).') VALUES ';
		$sql .= '('.implode(', ', $field_value).')';

		$this->Execute ($sql, $arg);

		$obj->r_field_id->_set($this->Insert_Id());
	}

	private function Update_Object ($obj)
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
					$field_value[] = 'CURRENT TIMESTAMP';
					$field_count++;
					break;

				default:
					$field_name[] = $name;
					$field_value[] = "?";
					$arg[] = $obj->{$name};
					$field_count++;
			}
		}

		$sql .= "SET ";
		for ($i = 0 ; $i < $field_count ; $i++)
		{
			$sql .= $field_name[$i].' = '.$field_value[$i].', ';
		}
		$sql = substr ($sql, 0, -2)." WHERE ".$obj->r_field_id->m_name." = ".$obj->r_field_id->_get();

		$this->Execute ($sql, $arg);
	}

	/**
		Checks if an object is in  this store.

		@param obj		The object to find.
	*/
	public function Exist_Object ($obj)
	{
		try
		{
			$row = $this->Select_Object ($obj, "count(*) AS n", FALSE);
			return $row['n'];
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
			return $this->Select_Object ($obj, "*", TRUE);
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
