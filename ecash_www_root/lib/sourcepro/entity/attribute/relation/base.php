<?php
/**
	An association between storable objects.
*/

abstract class SourcePro_Entity_Attribute_Relation_Base extends SourcePro_Entity_Attribute_Base
{
	public $m_class;
	public $m_schema;
	public $m_field;
	public $m_type;
	public $m_load;

	public $m_entity;

	protected $v_notification;
	protected $m_index_key;
	
	/**
		Initializes an instance of this class.

	*/
	function __construct ($owner, $name, $class, $schema, $field, $type, $index_key, $load, $notification)
	{
		parent::__construct($owner, $name);

		$this->m_class = $class;
		$this->m_schema = $schema;
		$this->m_field = $field;
		$this->m_type = $type;
		$this->m_load = $load;
		$this->v_notification = $notification;
		$this->m_index_key = $index_key;
	}

        function __destruct ()
        {
                parent::__destruct ();
        }

	function Save ()
	{
		if (is_array($this->m_entity))
		{
			foreach ($this->m_entity as $obj)
			{
				$obj->Save();
			}
		}
		elseif ($this->m_entity instanceOf SourcePro_Entity_Storage)
		{
			$this->m_entity->Save();
		}
	}
	
	function Display ()
	{
		if (is_array($this->m_entity))
		{
			foreach ($this->m_entity as $obj)
			{
				$obj->Display ();
			}
		}
		elseif ($this->m_entity instanceOf SourcePro_Entity_Storage)
		{
			$this->m_entity->Display();
		}
	}		
}


?>
