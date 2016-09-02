<?php


	class Previous_Customer_Agean extends Previous_Customer_Check
	{
		protected $olp_active_statuses = array('AGREED', 'CONFIRMED', 'CONFIRMED_DISAGREED', 'DISAGREED');
		
		public function __construct($sql, $db, $property, $mode, $bb_mode = null)
		{
			$this->properties = Enterprise_Data::getCompanyProperties(Enterprise_Data::COMPANY_AGEAN);
			
			parent::__construct($sql, $db, $property, $mode, $bb_mode);

			//Agean doesn't want inactive (recovered) apps.
			self::$status_map[Previous_Customer_Check::STATUS_REACT] = '/customer/paid';
		}
		

		//Agean denies anyone if they have one active loan among any of the companies.
		protected function Decide_Active($results, &$targets)
		{
			if(count($results[self::STATUS_ACTIVE]) >= $this->active_threshold)
			{
				$result = self::RESULT_OVERACTIVE;
				$targets = array();
			}
			
			return $result;
		}
	}

?>