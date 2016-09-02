<?php
/**
	A defined area that is used to record a type of information consistently.
*/

class SourcePro_Entity_Attribute_Field_String extends SourcePro_Entity_Attribute_Field_Base
{

	/**
		Initializes an instance of this class.

		@param owner	The storable object that this attribute belongs to.
		@param name		The name of this attribute.
		@param value	May provide the inital value for this field.  Must be an array with an element whos key is $name.
		@param type		The type of this field.  (see the constants in the SourcePro class)
		@param role		The role that this field plays.  (see the constants in the SourcePro class)
	*/
	function __construct ($owner, $name, $column = NULL, $value = NULL, $type = NULL, $role = NULL, $min = NULL, $max = NULL, $regex = NULL)
	{
		parent::__construct ($owner, $name, $column, $value, $type, $role, $min, $max, $regex);
	}

        function __destruct ()
        {
                parent::__destruct ();
        }

	function _set ($value)
	{
		if (is_null ($value))
		{
			if (! $this->f_allow_null)
			{
				throw new SourcePro_Exception ("{$this->m_name} can not be NULL", 1000);
			}
		}
		else
		{
			if ($this->m_max > 0 && strlen ($value) > $this->m_max)
			{
				throw new SourcePro_Exception ("{$this->m_name} has max strlen of {$this->m_max}", 1000);
			}
			if ($this->m_min > 0 && strlen ($value) < $this->m_min)
			{
				throw new SourcePro_Exception ("{$this->m_name} has min strlen of {$this->m_min}", 1000);
			}
		}

		try
		{
			parent::_set ($value);
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}
}

?>
