<?php
/**
	A defined area that is used to record a type of information consistently.
*/

class SourcePro_Entity_Attribute_Asset_Time extends SourcePro_Entity_Attribute_Asset_Base
{
	
	/**
		Initializes an instance of this class.

		@param owner	The storable object that this attribute belongs to.
		@param name		The name of this attribute.
		@param value	May provide the inital value for this field.  Must be an array with an element whos key is $name.
	*/
	function __construct ($owner, $name, $value = NULL, $type = NULL, $min = NULL, $max = NULL)
	{
		parent::__construct ($owner, $name, $value, $type, $min, $max);
	}

        function __destruct ()
        {
                parent::__destruct ();
        }

}

?>
