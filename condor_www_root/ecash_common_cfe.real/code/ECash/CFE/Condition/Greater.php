<?php
	
	class ECash_CFE_Condition_Greater extends ECash_CFE_Condition_ComparisonCondition
	{
		protected function compare($value1, $value2)
		{
			echo "Is {$value1} > {$value2} ?\n";
			return ((float)$value1 > (float)$value2);
		}
	}
	
?>