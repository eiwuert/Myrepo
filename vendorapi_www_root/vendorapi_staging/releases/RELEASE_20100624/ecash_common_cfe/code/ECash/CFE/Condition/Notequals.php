<?php
	
	class ECash_CFE_Condition_Notequals extends ECash_CFE_Condition_ComparisonCondition
	{
		protected function compare($value1, $value2)
		{
			return ($value1 != $value2);
		}
	}
	
?>
