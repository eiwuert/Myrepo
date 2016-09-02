<?php
/**
	An abstract base for objects that can store objects.
*/

abstract class SourcePro_Storage_Base extends SourcePro_Entity_Storage
{
	/**
		Initializes an instance of this class.

		@param store	The storage object used to save or load this object.
		@param schema	The optional schema that this object is stored in.  If null the default schema for the store will be used.
		@param data		The optional array containing initial field values.
	*/
	function __construct ($data = NULL)
	{
		parent::__construct(NULL, NULL);

		$this->Add_Field_String ('alias', NULL, $data);
		$this->Add_Field_String ('user', NULL, $data);
		$this->Add_Field_String ('pass', NULL, $data);
	}

	abstract public function Save_Object ($obj);
	abstract public function Load_Object ($obj);
	abstract public function Exist_Object ($obj);

}

?>