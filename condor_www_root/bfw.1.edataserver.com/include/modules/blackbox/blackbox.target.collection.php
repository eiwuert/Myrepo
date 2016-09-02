<?php

	class BlackBox_Target_Collection extends BlackBox_Collection
	{
		private $targets;		//Array of targets
		protected $target_list = array();

		public function __construct(&$bb_config = NULL, $targets = NULL)
		{
			parent::__construct($bb_config, 'iBlackBox_Target');


			if(is_array($targets))
			{
				foreach ($targets as &$target)
				{
					$this->Add($target);
				}
			}

			$this->targets = &$this->objects;

			$this->Use_Default();
		}


		public function __destruct()
		{
			parent::__destruct();
			
			unset($this->targets);
		}

		
		public function Is_Open($name)
		{
			return $this->In_Use($name);
		}

		/**
		 * Checks if the given target has any children.
		 *
		 * @param string $name The property short of the target
		 * @return bool TRUE if the target has children.
		 */
		public function Target_Has_Children($name)
		{
			$has_children = false;
			
			if($this->Has_Object($name))
			{
				$target = $this->Target($name);
				$has_children = $target->Has_Children();
			}
			
			return $has_children;
		}

		public function Target($name)
		{
			$target = $this->Object($name);
			
			if($target === false)
			{
				$list = $this->Get_Target_List(true, true, true);
				if(isset($list[strtoupper($name)]))
				{
					$target = $list[strtoupper($name)];
				}
			}
			
			return $target;
		}

		public function Has_Target($name, $include_children = true)
		{
			if($include_children)
			{
				$list = $this->Get_Target_List();
				$result = isset($list[strtoupper($name)]);
			}
			else
			{
				$result = $this->Has_Object($name);
			}
			
			return $result;
		}

		public function Get_Targets($in_use = true)
		{
			$return = array();

			if($in_use)
			{
				$targets = $this->Get_Use();
				foreach($targets as $name)
				{
					if(isset($this->targets[$name]))
					{
						$return[$name] = $this->targets[$name];
					}
				}
			}
			else
			{
				$return = $this->Get_Objects();
			}
			
			return $return;
		}

		private function Set_Targets(&$targets)
		{
			$this->Set_Objects($targets);
		}

		public function Use_Target($targets = NULL, $use = NULL)
		{
			return $this->Use_Object($targets, $use);
		}


		public function Open($names, $open = FALSE)
		{
			if(is_null($names))
			{
				$this->Use_Object($names, $open);
			}
			else
			{
				if(!is_array($names))
				{
					$names = array($names);
				}
	
				$names = array_map('strtoupper', $names);
				foreach($names as $name)
				{
					$target = $this->Target($name);
	
					//If we didn't find the target for some reason,
					//we don't want it throwing exceptions!
					if($target === false)
					{
						$this->config->Applog_Write("Unable to find target '{$name}' in Open(). session_id: " . session_id());
						continue;
					}
	
					//If we're checking a parent, we'll want to check its children, too.
					if($target->Has_Children())
					{
						$collection = $target->Get_Target_Collection();
	
						//We only want to check the children themselves
						$targets = array_intersect(array_keys($collection->Get_Targets(false)), $names);
	
						$result = (empty($targets)) ? false : $collection->Open($targets, $open);
	
						//If we get false back, then all the targets are gone and we'll close it out.
						if($result === false || $open === true)
						{
							$target->Get_Collection()->Use_Object($name, $open);
						}
					}
					else
					{
						$target->Get_Collection()->Use_Object($name, $open);
					}
					
					//If the parent has no more targets, close it out.
					if($target->Has_Parent() && count($target->Get_Collection()->Get_Open()) === 0)
					{
						$parent = $target->Get_Parent();
						if(is_object($parent))
						{
							$parent->Get_Collection()->Use_Object($parent->Name(), false);
						}
						else
						{
							$parent_id = $target->Get_Parent_ID();
							$this->config->Applog_Write("Has parent ({$parent_id}) but parent null? session_id: " . session_id());
						}
					}
				}
			}

			$result = $this->Get_Open();
			return (empty($result)) ? false : $result;
		}

		public function Get_Open()
		{
			return $this->Get_Use();
		}
		
		public function Has_Open()
		{
			return !empty($this->use);
		}


		public function Restrict_Targets($target_names, $exclude = FALSE)
		{
			if(!is_array($target_names))
			{
				$target_names = array($target_names);
			}

			// uppercase these
			$target_names = array_map('strtoupper', $target_names);
			$this->target_list = $this->Get_Target_List();//$this->Get_Open();

			if($exclude)
			{
				$this->Exclude($target_names);
			}
			else
			{
				$this->Restrict($target_names);
			}
		}

		
		protected function Restrict($names)
		{
			$targets = array_diff($this->target_list, $names);

			$list = $this->Get_Target_List(true, false);
			foreach($list as $parent => $children)
			{
				if(is_array($children))
				{
					//If we restrict a parent, we don't want to end
					//up accidentally restricting its children
					if(!isset($targets[$parent]))
					{
						$targets = array_diff($targets, $children);
					}
					//And vice versa
					elseif(isset($targets[$parent]) && count(array_intersect($children, $targets)) === 0)
					{
						unset($targets[$parent]);
					}
				} 
			}

			if(!empty($targets))
			{
				$this->Open($targets, FALSE);
				array_map(array($this, 'Remove'), $targets);				
			}
		}


		protected function Exclude($names)
		{
			$targets = array_intersect($this->target_list, $names);

			if(!empty($targets))
			{
				$this->Open($targets, FALSE);
				array_map(array($this, 'Remove'), $targets);
			}
		}
		
	
		public function Get_Target_List($in_use = true, $flat = true, $use_objects = false)
		{
			$targets = $this->Get_Targets($in_use);

			$list = array();
			foreach($targets as $target)
			{
				if($flat)
				{
					if($target->Has_Children())
					{ 
						$children = $target->Get_Target_List($in_use, $flat, $use_objects);
						$list = array_merge($list, $children);
					}

					$list[strtoupper($target->Name())] = 
						($use_objects) ? $target : $target->Name();
				}
				else
				{
					$list[strtoupper($target->Name())] = ($target->Has_Children())
						? $target->Get_Target_List($in_use, $flat, $use_objects)
						: (($use_objects) ? $target : $target->Name());
				}
			}
			
			return $list;
		}
		
		
		public function Restore($data, &$config)
		{
			$collection = parent::Restore($data, $config, 'BlackBox_Target_OldSchool', get_class());
			$collection->Set_Type('iBlackBox_Target');

			return $collection;
		}

	}

?>
