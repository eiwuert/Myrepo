<?php

require_once('libolution/Object.1.php');

class FraudPrototype extends Object_1
{
	//mess this up and you're on your own
	public $fields = array();
	
	private $id;
	
	// =================================================
	public function __construct($id)
	// =================================================
	{
		$this->id = $id;
	}

	public function getID()
	{
		return $this->id;
	}
	
	// =================================================
	public function addField($field_name, $comparison)
	// =================================================
	{
		$this->fields[$field_name] = $comparison;
	}

	public function getFieldNames()
	{
		return array_keys($this->fields);
	}

	/* This is a fun one, in order to compare an app like
	 * fname: justintsstest
	 * lname: foelltsstest
	 * to a rule like 'ENDS tsstest' you have to compare the values on
	 * the left using a like against the columns, formatted on the
	 * right :)
	 */
	public function formatSearch($field_name, $field_value, $column)
	{		
		switch($this->fields[$field_name])
		{
			case "STARTS":
				return "{$field_value} like concat({$column}, '%')";
				break;

			case "ENDS":
				return "{$field_value} like concat('%', {$column})";
				break;

			case "CONTAINS":
				return "{$field_value} like concat('%', {$column}, '%')";
				break;
				
			default:
			case "EQUALS":
				return "{$column} = {$field_value}";
				break;
		}
	}
}	

?>