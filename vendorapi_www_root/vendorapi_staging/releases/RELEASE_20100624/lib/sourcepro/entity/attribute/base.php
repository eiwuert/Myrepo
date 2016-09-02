<?php
/**
	An abstraction belonging to or characteristic of an entity.
*/

abstract class SourcePro_Entity_Attribute_Base
{
	/// The storable object that this attribute belongs to.
	public $m_owner;

	/// The name of this attribute.
	public $m_name;

	/**
		Initializes an instance of this class.

		@param owner	The storable object that this attribute belongs to.
		@param name		The name of this attribute.
	*/
	function __construct ($owner, $name)
	{
		$this->m_owner = $owner;
		$this->m_name = $name;
	}

	function __destruct ()
	{
		$this->m_owner = NULL;
	}

	abstract public function _get ();
	abstract public function _set ($value);
	abstract public function _call ($args);
}


?>
