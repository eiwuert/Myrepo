<?php
/**
	An association between storable objects.
*/

class SourcePro_Entity_Attribute_Relation_Single extends SourcePro_Entity_Attribute_Relation_Base
{
	public $m_multi = FALSE;

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
		return $this->m_entity;
	}

	function _set ($value)
	{
		$this->m_entity = $value;
	}

	function _call ($args)
	{
		throw new SourcePro_Exception("object relation ({$this->m_name}) used as method", 1000);
	}

	function Save ()
	{
		$this->m_entity->Save();
	}
	
	function Load ($store, $r_field_id)
	{
		switch ($this->m_type)
		{
			case SourcePro::LINK_INTERNAL:
				$this->m_entity->r_field_id->_set($r_field_id);
				break;

			case SourcePro::LINK_EXTERNAL:
				$this->m_entity->{$this->m_field} = $$r_field_id;
				break;
		}		
		
		$this->m_entity->Load();
	}
	
}

?>
