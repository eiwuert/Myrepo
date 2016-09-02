<?php

require_once("FraudPrototype.php");

/** This is not *exactly* a child of a Prototype... a prototype is *
 *  more of a signature of a set of conditions.  However, Conditions *
 *  and Prototypes both belong to Rules, so we can set both of them on
 *  a Rule.  Also, they contain similar (but different) formatSearch
 *  methods for building queries either to compare a rule against apps
 *  (Conditions) or to compare an app against rules (Prototypes).
 */
class FraudCondition extends FraudPrototype
{
	private $field_name;
	private $field_comparison;
    private $field_value;

	public function __construct($field_name, $field_comparison, $field_value)
	{
		$this->field_name = $field_name;
		$this->field_comparison = $field_comparison;
		$this->field_value = $field_value;
	}

	public function formatSearch()
	{		
		switch($this->field_comparison)
		{
			case "STARTS":
				return "like '{$this->field_value}%'";
				break;

			case "ENDS":
				return "like '%{$this->field_value}'";
				break;

			case "CONTAINS":
				return "like '%{$this->field_value}%'";
				break;
				
			default:
			case "EQUALS":
				return "= '{$this->field_value}'";
				break;
		}
	}

	public function getFieldName()
	{
		return($this->field_name);
	}

	public function getFieldComparison()
	{
		return($this->field_comparison);
	}

	public function getFieldValue()
	{
		return($this->field_value);
	}
}

?>