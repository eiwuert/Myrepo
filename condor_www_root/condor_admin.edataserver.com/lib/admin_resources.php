<?php


/**
*
*/
class Admin_Resources
{
	private $unsorted_master_tree;
	private $display_level;
	private $display_sequence;
	private $sorted_master_keys;


	/**
	 *
	 * @params
	 *	$unsorted_master_tree  This is the tree that will be sorted.
	 *	$display_level  This is the first level to display
	 *	$display_sequence  This is the first node of the display sequence
	 */
	public function __construct($unsorted_master_tree, $display_level, $display_sequence)
	{
		$this->unsorted_master_tree = $unsorted_master_tree;
		$this->display_level = $display_level;
		$this->display_sequence = $display_sequence;
		$this->sorted_master_keys = array();
	}

	/**
	*
	*/
	public function Get_Sorted_Master_Tree()
	{
		// create master sort array.
		$unsorted_array = array();
		$i = 0;
		
		foreach ($this->unsorted_master_tree as $section)
		{
			if ($section->level >= $this->display_level)
			{
				$unsorted_array[$i] = array('section_id'		=>$section->section_id,
											'section_desc'		=>$section->description,
											'section_parent_id'	=>$section->section_parent_id,
											'level'				=>$section->level,
											'sequence_no'		=>$section->sequence_no,
		                               		'read'				=>'0');
		    $i++;
			}
		}
 
		$sorted_position = 0;
		$sorted_array = array();
		$read_nodes = 0;
		$total_nodes = count($unsorted_array);

		// get the starting node
		$low_level = $this->Get_Low_Level($unsorted_array);
		$section_id = $this->Get_Low_Sequence($unsorted_array, $low_level);
		$this->Mark_Node_As_Read($unsorted_array, $section_id);
		$sorted_array[$sorted_position] = $section_id;
		$read_nodes++;

		// get any following nodes
		while ($read_nodes < $total_nodes)
		{
			if ($this->Current_Node_Has_Children($unsorted_array, $section_id))
			{
				// get the lowest child
				$low_level = $this->Get_Low_Level_Child($unsorted_array, $section_id);
				$section_id = $this->Get_Low_Sequence_Child($unsorted_array, $low_level, $section_id);
				$this->Mark_Node_As_Read($unsorted_array, $section_id);
				$sorted_position = count($sorted_array);
				$sorted_array[$sorted_position] = $section_id;
				$read_nodes++;
			}
			else
			{
				if ($sorted_position > 0)
				{
					$sorted_position--;
					$section_id =  $sorted_array[$sorted_position];
				}
				else
				{
					$low_level = $this->Get_Low_Level($unsorted_array);
					$section_id = $this->Get_Low_Sequence($unsorted_array, $low_level);
					$this->Mark_Node_As_Read($unsorted_array, $section_id);
					$sorted_position = count($sorted_array);
					$sorted_array[$sorted_position] = $section_id;
					$read_nodes++;
				}
			}
		}

		$this->sorted_master_keys = $sorted_array;

		return $sorted_array;
	}


	public function Merge_Into_Parent(&$sorted_array, $parent_desc, $section_desc, $section_array)
	{
		if (is_array($sorted_array))
		{
			foreach ($sorted_array as $desc => &$info)
			{
				if($desc == $parent_desc)
				{
					$sorted_array[$parent_desc]['children'][$section_desc] = $section_array;
				}
				elseif ($info)
				{
					$this->Merge_Into_Parent($info, $parent_desc, $section_desc, $section_array);
				}
			}
		}
	}
	

	public function Get_Indented_Sorted_Master_Tree()
	{
		// create master sort array.
		$unsorted_array = array();
		$i = 0;

		foreach ($this->unsorted_master_tree as $key => $section)
		{
			if ($section->level >= $this->display_level )
			{
				$unsorted_array[$section->level][$section->section_id] = array(
					'name'				=> $key,
					'section_id'		=> $section->section_id,
					'section_desc'		=> $section->description,
					'section_parent_id'	=> $section->section_parent_id,
					'level'				=> $section->level,
					'sequence_no'		=> $section->sequence_no,
       				'read'				=> '0');
            	$i++;
			}
		}
		
		/*
			In order to get this to work, we have to sort the levels in the
			"unsorted_array". "Why?" you might ask, when the code supposedly exists
			below that should be sorting it? Well, in the code below, if the child
			is before the parent, the parent will overwrite it. So, now, we sort it
			so that the parent has to be on top.
			
			TODO: Fix Merge_Into_Parent() so it's not so stupid.
		*/
		ksort($unsorted_array);

		foreach ($unsorted_array as $section_level => $section_data)
		{
			while(list($section_id, $section_info) = each($section_data))
      		{
				if ($section_info['level'] > 0)
				{
					$parent_desc = $unsorted_array[$section_info['level'] - 1][$section_info['section_parent_id']]['section_desc'];
					$section_array['name'] = 			$section_info['name'];
					$section_array['parent_id'] = 		$section_info['section_parent_id'];
					$section_array['section_id'] = 		$section_info['section_id'];
					$section_array['sequence_no'] = 	$section_info['sequence_no'];
					$section_array['level'] = 			$section_info['level'];
					$section_array['section_desc'] = 	$section_info['section_desc'];
					
					reset($sorted_array);
					$result = false;
					$l = 10;
					$a = 0;
					
					while(($a < $l) && !$result)
					{
						$result = $this->Merge_Into_Parent($sorted_array, $parent_desc, $section_info['section_desc'], $section_array);
						++$a;
					}
				}
				else 
				{
					$sorted_array[$section_info['section_desc']]['name'] = 			$section_info['name'];
					$sorted_array[$section_info['section_desc']]['parent_id'] = 	$section_info['section_parent_id'];
					$sorted_array[$section_info['section_desc']]['section_id'] = 	$section_info['section_id'];
					$sorted_array[$section_info['section_desc']]['sequence_no'] = 	$section_info['sequence_no'];
					$sorted_array[$section_info['section_desc']]['level'] = 		$section_info['level'];
					$sorted_array[$section_info['section_desc']]['section_desc'] = 	$section_info['section_desc'];
				}
      		}
		}

		$this->sorted_master_keys = $sorted_array;

		return $sorted_array;
	}



	/**
	*
	*/
	private function Get_Low_Level($unsorted_array)
	{
		$low_level = 1000;

		reset($unsorted_array);
		foreach($unsorted_array as $sections)
		{
			if ( $sections['level'] >= $this->display_level
				&& $sections['level'] < $low_level
				&& $sections['read'] == 0)
			{
				$low_level = $sections['level'];
			}
		} // end foreach

		return $low_level;
	}



	/**
	*
	*/
	private function Get_Low_Sequence($unsorted_array, $low_level)
	{
		$section_id = -1;
		$low_sequence = 1000;

		// get the lowest sequence
		reset($unsorted_array);
		foreach($unsorted_array as $sections)
		{
			if ($sections['sequence_no'] < $low_sequence
					&& $sections['level'] == $low_level
					&& $sections['read'] == 0)
			{
				$low_sequence = $sections['sequence_no'];
				$section_id = $sections['section_id'];
			}
		} // end foreach

		return $section_id;
	}

	/**
	*
	*/
	private function Get_Low_Sequence_Desc($unsorted_array, $low_level)
	{
		$section_id = -1;
		$low_sequence = 1000;

		// get the lowest sequence
		reset($unsorted_array);
		foreach($unsorted_array as $sections)
		{
			if ($sections['sequence_no'] < $low_sequence
					&& $sections['level'] == $low_level
					&& $sections['read'] == 0)
			{
				$low_sequence = $sections['sequence_no'];
				$section_id = $sections['section_desc'];
			}
		} // end foreach

		return $section_id;
	}

	/**
	*
	*/
	private function Get_Low_Sequence_Name($unsorted_array, $low_level)
	{
		$section_name = '';
		$low_sequence = 1000;

		// get the lowest sequence
		reset($unsorted_array);
		foreach($unsorted_array as $sections)
		{
			if ($sections['sequence_no'] < $low_sequence
					&& $sections['level'] == $low_level
					&& $sections['read'] == 0)
			{
				$low_sequence = $sections['sequence_no'];
				$section_name = $sections['name'];
			}
		} // end foreach

		return $section_name;
	}

	/**
	*
	*/
	private function Mark_Node_As_Read(& $unsorted_array, $section_id)
	{
		$a = 0;
		foreach ($unsorted_array as $sections)
		{
			if ($sections['section_id'] == $section_id)
			{
				$unsorted_array[$a] = array('section_id'				=>$sections['section_id'],
													'section_desc'		=>$sections['section_desc'],
													'section_parent_id'	=>$sections['section_parent_id'],
													'level'				=>$sections['level'],
													'sequence_no'		=>$sections['sequence_no'],
													'read'				=>'1');
			}
			$a++;
		}
	}

	/**
	*
	*/
	private function Get_Low_Level_Child($unsorted_array, $section_id)
	{
		$low_level = 1000;

		reset($unsorted_array);
		foreach($unsorted_array as $sections)
		{
			if ($sections['level'] < $low_level
				&& $sections['section_parent_id'] == $section_id
				&& $sections['read'] == 0)
			{
				$low_level = $sections['level'];
			}
		} // end foreach

		return $low_level;
	}

	/**
	*
	*/
	private function Get_Low_Sequence_Child($unsorted_array, $low_level, $section_id)
	{
		$result = -1;
		$low_sequence = 1000;

		// get the lowest sequence
		reset($unsorted_array);
		foreach($unsorted_array as $sections)
		{
			if ($sections['sequence_no'] < $low_sequence
				&& $sections['level'] == $low_level
				&& $sections['section_parent_id'] == $section_id
				&& $sections['read'] == 0)
			{
				$low_sequence = $sections['sequence_no'];
				$result = $sections['section_id'];
			}
		} // end foreach

		return $result;
	}

	/**
	*
	*/
	private function Get_Low_Sequence_Child_Desc($unsorted_array, $low_level, $section_id)
	{
		$result = -1;
		$low_sequence = 1000;

		// get the lowest sequence
		reset($unsorted_array);
		foreach($unsorted_array as $sections)
		{
			if ($sections['sequence_no'] < $low_sequence
				&& $sections['level'] == $low_level
				&& $sections['section_parent_id'] == $section_id
				&& $sections['read'] == 0)
			{
				$low_sequence = $sections['sequence_no'];
				$result = $sections['section_desc'];
			}
		} // end foreach

		return $result;
	}


	/**
	*
	*/
	private function Get_Low_Sequence_Child_Name($unsorted_array, $low_level, $section_id)
	{
		$result = -1;
		$low_sequence = 1000;

		// get the lowest sequence
		reset($unsorted_array);
		foreach($unsorted_array as $sections)
		{
			if ($sections['sequence_no'] < $low_sequence
				&& $sections['level'] == $low_level
				&& $sections['section_parent_id'] == $section_id
				&& $sections['read'] == 0)
			{
				$low_sequence = $sections['sequence_no'];
				$result = $sections['name'];
			}
		} // end foreach

		return $result;
	}




	/**
	*
	*/
   private function Current_Node_Has_Children($unsorted_array, $section_id)
   {
      $result = false;

      reset($unsorted_array);
      foreach($unsorted_array as $sections)
      {
         if ($sections['section_parent_id'] == $section_id
            && $sections['read'] == 0)
         {
            $result = true;
            break;
         }
      } // end foreach

      return $result;
   }
}

?>
