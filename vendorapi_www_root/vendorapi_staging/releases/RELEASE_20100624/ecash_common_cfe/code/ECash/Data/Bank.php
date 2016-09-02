<?php

	class ECash_Data_Bank extends ECash_Data_DataRetriever
	{
		public function getDetailsByABA($aba)
		{
			$query = "
				SELECT
					Institution_Name_Full,
					ACH_Contact_Area_Code,
					ACH_Contact_Phone_Number,
					ACH_Contact_Extension
				FROM
					aba_list
				WHERE
					Routing_Number_MICR_Format = ? LIMIT 1";

			return DB_Util_1::querySingleRow($this->db, $query, array($aba), PDO::FETCH_OBJ);
		}
	}
?>