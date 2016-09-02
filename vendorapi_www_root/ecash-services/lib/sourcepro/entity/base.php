<?php
/**
	An abstract base for objects that can be stored.
*/

abstract class SourcePro_Entity_Base extends SourcePro_Metaobject
{
	/// A reference to our id field
	public $r_field_id = NULL;

	/// A reference to our key field
	public $r_field_key = NULL;

	/// Vector of fields
	public $v_field = array();

	/// Vector of assets
	public $v_asset = array();

	/// Vector of properties
	public $v_property = array();

	/// Flagged when our value changes
	public $f_changed = TRUE;

	/// Flag to send notifications
	public $f_send_notice = FALSE;

	/**
		Initializes an instance of this class.

		@param store		The array of storage objects used to save or load this object.
		@param schema		The optional schema that this object is stored in.
	*/
	function __construct ()
	{
		parent::__construct();
	}

	function __destruct ()
	{
		unset ($this->r_field_id, $this->r_field_key, $this->v_field, $this->v_asset, $this->v_property);
		parent::__destruct();	
	}

	/**
		Adds a field attribute to this object.

		@param field			The field object.
	*/
	protected function Add_Field ($field)
	{
		$this->v_attribute[$field->m_name] = $field;
		$this->v_field[$field->m_name] = $field;
	}

	/**
		Adds a number field attribute to this object.

		@param name			The attribute name.
		@param column		The optional column name.
		@param value		The inital value.
		@param min			The minimum value.
		@param max			The maximum value.
		@param regex		Regex constraints.
	*/
	protected function Add_Field_Number ($name, $column = NULL, $value = NULL, $min = NULL, $max = NULL, $regex = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Field_Number ($this, $name, $column, $value, SourcePro::TYPE_INT, NULL, $min, $max, $regex);
		$this->Add_Field ($a);
	}

	/**
		Adds an id field attribute to this object.

		@param name			The attribute name.
		@param column		The optional column name.
		@param value		The inital value.
	*/
	protected function Add_Field_Id ($name, $column = NULL, $value = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Field_Number ($this, $name, $column, $value, SourcePro::TYPE_INT, SourcePro::ROLE_ID);
		$this->Add_Field ($a);
		$this->r_field_id = $this->v_attribute[$name];
		$this->Add_Index ($name);
	}

	/**
		Adds a external id field attribute to this object.

		@param name			The attribute name.
		@param column		The optional column name.
		@param value		The inital value.
	*/
	protected function Add_Field_Eid ($name, $column = NULL, $value = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Field_Number ($this, $name, $column, $value, SourcePro::TYPE_INT, SourcePro::ROLE_EID);
		$this->Add_Field ($a);
		$this->Add_Index ($name);
	}

	/**
		Adds a string field attribute to this object.

		@param name			The attribute name.
		@param column		The optional column name.
		@param value		The inital value.
		@param min			The minimum length.
		@param max			The maximum length.
		@param regex		Regex constraints.
	*/
	protected function Add_Field_String ($name, $column = NULL, $value = NULL, $min = NULL, $max = NULL, $regex = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Field_String ($this, $name, $column, $value, SourcePro::TYPE_CHAR, NULL, $min, $max);
		$this->Add_Field ($a);
	}

	/**
		Adds a key field attribute to this object.

		@param name			The attribute name.
		@param column		The optional column name.
		@param value		The inital value.
		@param min			The minimum length.
		@param max			The maximum length.
	*/
	protected function Add_Field_Key ($name, $column = NULL, $value = NULL, $min = 1, $max = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Field_String ($this, $name, $column, $value, SourcePro::TYPE_CHAR, SourcePro::ROLE_KEY, $min, $max);
		$this->Add_Field ($a);
		$this->r_field_key = $this->v_attribute[$name];
		$this->Add_Index($name);
	}


	/**
		Adds a time field attribute to this object.

		@param name			The attribute name.
		@param column		The optional column name.
		@param value		The inital value.
		@param min			The minimum length.
		@param max			The maximum length.
	*/
	protected function Add_Field_Time ($name, $column = NULL, $value = NULL, $min = NULL, $max = NULL, $regex = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Field_Time ($this, $name, $column, $value, SourcePro::TYPE_TIMESTAMP, NULL, $min, $max);
		$this->Add_Field ($a);
	}

	/**
		Adds a time field attribute to this object.

		@param name			The attribute name.
		@param column		The optional column name.
		@param value		The inital value.
		@param min			The minimum length.
		@param max			The maximum length.
	*/
	protected function Add_Field_Time_Stamp ($name, $column = NULL, $value = NULL, $min = NULL, $max = NULL, $regex = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Field_Stamp ($this, $name, $column, $value, SourcePro::TYPE_INT, NULL, $min, $max);
		$this->Add_Field ($a);
	}

	/**
		Adds a time modified field attribute to this object.

		@param name			The attribute name.
		@param column		The optional column name.
		@param value		The inital value.
	*/
	protected function Add_Field_Time_Modified ($name, $column = NULL, $value = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Field_Stamp ($this, $name, $column, $value, SourcePro::TYPE_INT, SourcePro::ROLE_MTIME);
		$this->Add_Field ($a);
	}

	/**
		Adds a time created field attribute to this object.

		@param name			The attribute name.
		@param column		The optional column name.
		@param value		The inital value.
	*/
	protected function Add_Field_Time_Created ($name, $column = NULL, $value = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Field_Stamp ($this, $name, $column, $value, SourcePro::TYPE_INT, SourcePro::ROLE_CTIME);
		$this->Add_Field ($a);
	}


	/**
		Adds a property attribute to this object.

		@param name			The name of this property.
		@param get			The method to use when reading from this property.
		@param set			The method to use when writing to this property.
	*/
	protected function Add_Property ($name, $get = NULL, $set = NULL)
	{
		$this->v_attribute[$name] = new SourcePro_Entity_Attribute_Property ($this, $name, array('get' => $get, 'set' => $set));
		$this->v_property[$name] = $this->v_attribute[$name];
	}

	/**
		Adds a asset attribute to this object.

		@param asset			The field object.
	*/
	protected function Add_Asset ($asset)
	{
		$this->v_attribute[$asset->m_name] = $asset;
		$this->v_asset[$asset->m_name] = $asset;
	}

	/**
		Adds a number field attribute to this object.

		@param name			The attribute name.
		@param value		The inital value.
		@param min			The minimum value.
		@param max			The maximum value.
		@param regex		Regex constraints.


	*/
	protected function Add_Asset_Number ($name, $value = NULL, $min = NULL, $max = NULL, $regex = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Asset_Number ($this, $name, $value, SourcePro::TYPE_INT, $min, $max, $regex);
		$this->Add_Asset ($a);
	}

	/**
		Adds a string field attribute to this object.

		@param name			The attribute name.
		@param value		The inital value.
		@param min			The minimum length.
		@param max			The maximum length.
		@param regex		Regex constraints.
	*/
	protected function Add_Asset_String ($name, $value = NULL, $min = NULL, $max = NULL, $regex = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Asset_String ($this, $name, $value, SourcePro::TYPE_CHAR, $min, $max, $regex);
		$this->Add_Asset ($a);
	}

	/**
		Adds a time created field attribute to this object.

		@param name			The attribute name.
		@param value		The inital value.
		@param min			The minimum length.
		@param max			The maximum length.
	*/
	protected function Add_Asset_Time ($name, $value = NULL, $min = NULL, $max = NULL)
	{
		$a = new SourcePro_Entity_Attribute_Asset_Time ($this, $name, $value, SourcePro::TYPE_CHAR, $min, $max);
		$this->Add_Asset ($a);
	}

	/**
		Dump the values of an object for debug purposes (Removes recursion).

		@param level		The number of tabs to display.
	*/
	public function Display ($level = 0)
 	{
		if (!$level)
		{
			echo str_repeat("\t", $level);
		}

		echo get_class($this)."\n";

		foreach ($this->v_attribute as $n => $a)
		{
			$v = $a->_get();

			if ($v instanceOf SourcePro_Entity_Base)
			{
				echo str_repeat("\t", $level+1)."{$n} = ";
				$v->Display ($level+1);
			}
			elseif ($v instanceOf SourcePro_Entity_Attribute_Relation_Base)
			{
				foreach ($v as $mn => $ma)
				{
					echo str_repeat("\t", $level+1).$n."[".$mn."] = ";
					$ma->Display ($level+1);
				}
			}
			else
			{
				echo str_repeat("\t", $level+1)."$n = ".$v."\n";
			}
		}
		echo "\n";
	}
}

?>
