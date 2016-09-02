<?php

class Advanced_Sort
{
	function Sort_Data($sort_array, $column_1, $sort_type_1=SORT_DESC, $column_2=null, $sort_type_2=SORT_ASC)
	{
		$sort_column_1 = array();
		$sort_column_2 = array();
		
		if( is_array($sort_array) )
		{		
			foreach($sort_array as $key => $sort_value)
			{
				if( is_object($sort_value) )
				{
					$sort_column_1[$key] = isset($sort_value->$column_1) ? $sort_value->$column_1 : "";
					if (isset($column_2))
					{
						$sort_column_2[$key] = isset($sort_value->$column_2) ? $sort_value->$column_2 : "";
					}
				}
				else
				{
					$sort_column_1[$key] = isset($sort_value[$column_1]) ? $sort_value[$column_1] : "";
					if (isset($column_2))
					{
						$sort_column_2[$key] = isset($sort_value[$column_2]) ? $sort_value[$column_2] : "";
					}
				}
			}
	
			if (isset($column_2))
			{
				array_multisort($sort_column_1, $sort_type_1, $sort_column_2, $sort_type_2, $sort_array);
			}
			else
			{
				array_multisort($sort_column_1, $sort_type_1, $sort_array);
			}
			
			return $sort_array;
		}
		return FALSE;
	}
}

?>