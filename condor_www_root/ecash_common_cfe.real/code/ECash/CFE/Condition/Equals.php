<?php
	
	class ECash_CFE_Condition_Equals extends ECash_CFE_Condition_ComparisonCondition
	{
		protected function compare($value1, $value2)
		{
			return ($value1 == $value2);
		}
	}
	
?>
