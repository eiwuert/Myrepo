<?php
/**
	A defined area that is used to record a type of information consistently.
*/

class SourcePro_Entity_Attribute_Field_Number extends SourcePro_Entity_Attribute_Field_Base
{

	/**
		Initializes an instance of this class.

		@param owner		The storable object that this attribute belongs to.
		@param name		The name of this attribute.
		@param value		May provide the inital value for this field.  Must be an array with an element whos key is $name.
		@param type		The type of this field.  (see the constants in the SourcePro class)
		@param role		The role that this field plays.  (see the constants in the SourcePro class)
	*/
	function __construct ($owner, $name, $column = NULL, $value = NULL, $type = NULL, $role = NULL, $min = NULL, $max = NULL, $regex = NULL)
	{
		parent::__construct($owner, $name, $column, $value, $type, $role, $min, $max, $regex);
	}

	function __destruct ()
	{
		parent::__destruct ();
	}

	function _set ($value)
	{
		if (! is_null ($value))
		{
			if (! is_null ($this->m_max) && $value > $this->m_max)
			{
				throw new SourcePro_Exception ("max exceded", 1000);
			}
			if (! is_null ($this->m_min) && $value < $this->m_min)
			{
				throw new SourcePro_Exception ("min exceded", 1000);
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
