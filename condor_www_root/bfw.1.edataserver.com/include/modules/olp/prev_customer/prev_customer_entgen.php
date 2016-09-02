<?php


	class Previous_Customer_Entgen extends Previous_Customer_Check
	{
		public function __construct($sql, $db, $property, $mode, $bb_mode = null)
		{
			$this->properties = Enterprise_Data::getCompanyProperties(Enterprise_Data::COMPANY_GENERIC);
			parent::__construct($sql, $db, $property, $mode, $bb_mode);
		}
	}

?>