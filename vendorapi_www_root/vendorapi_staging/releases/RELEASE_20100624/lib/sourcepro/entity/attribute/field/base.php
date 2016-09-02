<?php
/**
	A defined area that is used to record a type of information consistently.
*/

class SourcePro_Entity_Attribute_Field_Base extends SourcePro_Entity_Attribute_Asset_Base
{
	/// The role this field plays.
	public $m_role;

	/// The role this field plays.
	public $m_column;

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
		parent::__construct ($owner, $name, $value, $type, $min, $max, $regex);

		$this->m_column = $column;
		$this->m_role = $role;
	}

	function __destruct ()
	{
		parent::__destruct ();
	}

/*
	function _get ()
	{
		return $this->m_value;
	}

	function _set ($value)
	{
		if (is_array ($this->m_regex) && count ($this->m_regex))
		{
			foreach ($this->m_regex as $regex)
			{
				if (! preg_match ($regex, $value))
				{
					throw new SourcePro_Exception ("regex constraint ({$regex}) failed for value ({$value})", 1000);
				}
			}
		}

		if ($this->m_value != $value)
		{
			$this->m_value = $value;
			$this->f_changed = TRUE;
			$this->m_owner->f_changed = TRUE;
		}
	}

	function _call ($args)
	{
		throw new SourcePro_Exception("object field ({$this->m_name}) used as method", 1000);
	}
*/
}

?>
