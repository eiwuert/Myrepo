<?php
/**
	An association between storable objects.
*/

class SourcePro_Entity_Attribute_Relation_Multi extends SourcePro_Entity_Attribute_Relation_Base implements ArrayAccess, IteratorAggregate
{
	public $m_multi = TRUE;
	
	public $m_entity = array ();
	
	/**
		Initializes an instance of this class.

	*/
	function __construct ($owner, $name, $class, $schema, $field, $type, $index_key, $load, $notification)
	{
		parent::__construct($owner, $name, $class, $schema, $field, $type, $index_key, $load, $notification);
	}

        function __destruct ()
        {
                parent::__destruct ();
        }

	function _get ()
	{
		return $this;
	}

	function _set ($value)
	{
		throw new SourcePro_Exception("multi object relation ({$this->m_name}) can not be set", 1000);
	}

	function _call ($args)
	{
		throw new SourcePro_Exception("object relation ({$this->m_name}) used as method", 1000);
	}

	function Save ()
	{
		foreach ($this->m_entity as $obj)
		{
			$obj->Save();	
		}	
	}
	
	function Load ($store, $r_field_id)
	{
		$tmp = new $this->m_class ($store, $this->m_schema);
		
		switch ($this->m_type)
		{
			case SourcePro::LINK_INTERNAL:
				$tmp->r_field_id->_set($r_field_id);
				break;

			case SourcePro::LINK_EXTERNAL:
				$tmp->{$this->m_field} = $r_field_id;
				break;
		}
		
		$rs = $store->Load_Object ($tmp);
		if (is_array ($rs))
		{
			foreach ($rs as $row)
			{
				// TODO: Find a better way?
				$current_entity = new $this->m_class ($store, $this->m_schema, $row, $this->v_notification);
				
				if (strlen ($this->m_index_key) && strlen ($row[$this->m_index_key]))
				{
					$this->m_entity[$row[$this->m_index_key]] = $current_entity;
				}
				else
				{
					$this->m_entity[] = $current_entity;
				}				
				
				foreach ($current_entity->v_relation as $relation)
				{
					if ($relation->m_load)
					{
						$relation->Load ($store, $current_entity->{$relation->m_field});	
					}
				}
			}	
		}
	
	}
	
	function Get ($key)
	{
		return $this->m_entity[$key];
	}

	function offsetSet ($key, $value)
	{
		$this->m_entity[$key] = $value;
	}

	function offsetGet ($key)
	{
		if (isset ($this->m_entity[$key]))
		{
			return $this->m_entity[$key];
		}
	}

	function offsetUnset ($key)
	{
		unset ($this->m_entity[$key]);
	}

	function offsetExists ($key)
	{
		return isset ($this->m_entity[$key]);
	}

	function getIterator ()
	{
		return new ArrayIterator($this->m_entity);
	}
}


?>
