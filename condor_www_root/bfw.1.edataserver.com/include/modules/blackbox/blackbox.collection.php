<?php

	abstract class BlackBox_Collection implements iBlackBox_Serializable, Iterator
	{
		protected $objects;		//Array of objects
		protected $use;			//Objects being used
		protected $removed = array();

		protected $config;	//BlackBox_Config object

		private $obj_type;

		public function __construct(&$bb_config, $obj_type = 'BlackBox_Collection')
		{
			$this->config = &$bb_config;
			$this->obj_type = $obj_type;

			$this->objects = array();
			$this->use = array();
			$this->removed = array();
		}


		public function __destruct()
		{
			unset($this->objects);
			unset($this->config);
		}


		
		public function Set_Type($type)
		{
			$this->obj_type = $type;
		}
		
		/**
			@desc Checks if a tier is currently being used

			@param $name string The name of the tier

			@return bool True if found in $this->use

		*/
		public function In_Use($name)
		{
			return (in_array($name, $this->use));
		}



		/**
			@desc Returns the use array

			@return array The use array containing the names
				of the tiers still being used

		*/
		public function Get_Use()
		{
			return $this->use;
		}


		public function Use_Default()
		{
			$this->use = array_keys($this->objects);
		}


		public function Has_Object($name)
		{
			return isset($this->objects[$name]);
		}


		public function Object($name)
		{
			// error
			$object = FALSE;

			if (isset($this->objects[$name]))
			{
				$object = &$this->objects[$name];
				if (!$object instanceof $this->obj_type) $object = FALSE;
			}

			return $object;
		}


		protected function Get_Objects()
		{
			return $this->objects;
		}

		protected function Set_Objects(&$objects)
		{
			$this->objects = $objects;
		}



		/**
			@desc Add a tier to the tiers array

			@param $tier BlackBox_Tier The tier object to add
				Alternately, you can pass in an array of tier
				objects and it will add them all in.
		*/
		public function Add(&$obj, $set_use = FALSE)
		{
			//Make sure we really have a tier
			if($obj instanceof $this->obj_type)
			{
				//Add it and set the collection
				$this->objects[$obj->Name()] = &$obj;

				if(method_exists($obj, 'Set_Collection'))
				{
					$obj->Set_Collection($this);
				}
			}
			//If we have an array, loop through and try
			//to add each element
			elseif(is_array($obj) && !empty($obj))
			{
				foreach($obj as &$o)
				{
					$this->Add($o);
					unset($o);
				}
			}
			
			if($set_use)
			{
				$this->Use_Default();
			}
		}


		/**
			@desc Removes a tier from the tiers array

			@param $tier BlackBox_Tier The tier object to remove
				If you pass in a string, it will assume that is
				the tier name and try to remove that tier.

		*/
		public function Remove(&$obj, $remove_obj = false)
		{
			if($obj instanceof $this->obj_type)
			{
				$this->removed[strtoupper($obj->Name())] = true;
				
				if($remove_obj)
				{
					unset($this->objects[$obj->Name()]);
				}
			}
			elseif(!is_null($obj) && isset($this->objects[$obj]))
			{
				$this->removed[strtoupper($obj)] = true;
				
				if($remove_obj)
				{
					unset($this->objects[$obj]);
				}
			}
		}


		public function Get_Removed()
		{
			return $this->removed;
		}

		public function Removed($name)
		{
			return isset($this->removed[strtoupper($name)]);
		}



		public function Use_Object($names = NULL, $use = NULL)
		{
			if (!is_null($names))
			{

				// should we be changing the array?
				if (!is_null($use))
				{

					if (is_array($names))
					{
						foreach ($names as $name)
						{
							$this->Set_Use($name, $use);
						}
					}
					else
					{
						$this->Set_Use($names, $use);
					}

				}

				// RETURN OPEN STATUS
				if (is_string($names))
				{
					$use = (array_search($names, $this->use) !== FALSE);
				}
				elseif (is_array($names))
				{
					$use = (empty($names)) ? $this->use : array_intersect($names, $this->use);
					$use = $this->use;
				}
			}
			else
			{
				if ($use === TRUE)
				{
					// Open ALL tiers!
					$this->use = array_keys($this->objects);
				}
				elseif ($use === FALSE)
				{
					// Feel the power: CLOSES ALL TIERS!
					$this->use = array();
				}

				$use = $this->use;
			}

			return (!$use) ? FALSE : $use;
		}






		protected function Set_Use($obj, $use)
		{

			if(isset($this->objects[$obj]))
			{

				$exists = (($key = array_search($obj, $this->use)) !== FALSE);

				if ($use && !$exists)
				{
					$this->use[] = $obj;
				}
				elseif(!$use && $exists)
				{
					unset($this->use[$key]);
				}

			}
			else
			{
				$use = FALSE;
			}

			return $use;

		}








		abstract protected function Exclude($names);
		abstract protected function Restrict($names);






		public function Reset()
		{
			foreach($this->objects as &$obj)
			{
				$obj->Reset();
				unset($obj);
			}
		}



		public function Sleep()
		{
			$objects = array();

			foreach ($this->objects as $name => &$obj)
			{

				if($obj instanceof $this->obj_type && !$this->Removed($name))
				{
					$objects[$name] = $obj->Sleep();
				}

				unset($obj);
			}

			return $objects;
		}
		
		
		
		/**
			@desc Restore from Sleep

			@param $data Data used to restore (an array of objects)
			@param $config BlackBox_Config object
			@param $obj_type Type of object we're storing in the collection
			@param $col_type Type of collection we are creating
			
			@return BlackBox_Collection object
		*/
		public function Restore($data, &$config, $obj_type = NULL, $col_type = 'BlackBox_Collection')
		{
			$collection = NULL;
			
			if (isset($this) && ($this instanceof $col_type))
			{
				$collection = &$this;
			}
			elseif ($config)
			{
				$collection = new $col_type($config);
			}
		
			
			if(isset($collection) && !empty($data) && class_exists($obj_type))
			{
				$collection->Set_Type($obj_type);
				
				foreach ($data as $name => $object)
				{
					$new_object = call_user_func_array(array($obj_type, 'Restore'), array($object, $config));
					
					$collection->Add($new_object);
					unset($new_object);
				}
			}
		
			return $collection;
		}
		
		
		
		
		/*** ITERATOR INTERFACE ***/
		public function rewind()
		{
			reset($this->objects);
		}

		public function current()
		{
			return current($this->objects);
		}

		public function key()
		{
			return key($this->objects);
		}

		public function next()
		{
			return next($this->objects);
		}

		public function valid()
		{
			return ($this->current() !== false);
		}

		/*** END INTERATOR INTERFACE ***/

	}

?>
