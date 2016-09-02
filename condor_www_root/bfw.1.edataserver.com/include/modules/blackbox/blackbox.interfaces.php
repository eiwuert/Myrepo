<?php

	interface iBlackBox_Target 
	{
		public function Name();
		public function Failed();
		public function Pick();
		
		public function Get_Target_List($in_use = true, $flat = true, $use_objects = false);

		public function Valid();
		public function Validate($data, &$config = NULL);
	}


	interface iBlackBox_Serializable
	{
		public function Sleep();
		public function Restore($data, &$config);
	}


	interface iBlackBox_Filter
	{
		public function Name();
		public function Check_Filter($data, $tier = NULL);
	}
	

?>