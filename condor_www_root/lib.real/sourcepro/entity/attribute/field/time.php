<?php
/**
	A defined area that is used to record a type of information consistently.
*/

class SourcePro_Entity_Attribute_Field_Time extends SourcePro_Entity_Attribute_Field_Base
{

	/**
		Initializes an instance of this class.

		@param owner	The storable object that this attribute belongs to.
		@param name		The name of this attribute.
		@param value	May provide the inital value for this field.  Must be an array with an element whos key is $name.
		@param type		The type of this field.  (see the constants in the SourcePro class)
		@param role		The role that this field plays.  (see the constants in the SourcePro class)
	*/
	function __construct ($owner, $name, $column = NULL, $value = NULL, $type = NULL, $role = NULL, $min = NULL, $max = NULL)
	{
		parent::__construct ($owner, $name, $column, $value, $type, $role, $min, $max);
	}

        function __destruct ()
        {
                parent::__destruct ();
        }

}

?>
