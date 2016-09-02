<?php
/**
	A defined area that is used to record a type of information consistently.
*/

class SourcePro_Entity_Attribute_Asset_Base extends SourcePro_Entity_Attribute_Base
{
	/// The current value of this field.
	protected $m_value = NULL;

	/// The type of this field.
	public $m_type;

	/// Minimum size
	protected $m_min;

	/// Maximum size
	protected $m_max;

	/// Flag indicating if our value has changed.
	public $f_changed = FALSE;

	/// Flag
	public $f_allow_null = TRUE;

	/**
		Initializes an instance of this class.

		@param owner		The storable object that this attribute belongs to.
		@param name		The name of this attribute.
		@param value		May provide the inital value for this field.  Must be an array with an element whos key is $name.
		@param type		The type of this field.  (see the constants in the SourcePro class)
		@param role		The role that this field plays.  (see the constants in the SourcePro class)
	*/
	function __construct ($owner, $name, $value = NULL, $type = NULL, $min = NULL, $max = NULL, $regex = NULL)
	{
		parent::__construct($owner, $name);

		$value = is_array ($value) && isset ($value[$name]) ? $value[$name] : NULL;
		$this->m_value = $value;

		$this->m_type = $type;
		$this->m_min = $min;
		$this->m_max = $max;
		$this->m_regex = is_null ($regex) || is_array ($regex) ? $regex : array ($regex);
	}

        function __destruct ()
        {
                parent::__destruct ();
        }

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
					throw new SourcePro_Exception ("regex constraint ({$regex}) failed for {$this->m_name} attempt to set value ({$value})", 1000);
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
}

?>
