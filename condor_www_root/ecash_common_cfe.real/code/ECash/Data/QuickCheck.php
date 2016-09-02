<?php

	class ECash_Data_QuickCheck extends ECash_Data_DataRetriever
	{
		/**
		 * Returns a map of owner code to company_id
		 *
		 * [owner_code] => company_id
		 *
		 * @return array
		 */
		public function getOwnerCodeMap()
		{
		    $query = "
			SELECT
				value,
				company_id
			FROM company_property
			WHERE
				property='QC_OWNER_CODE'";

		    return $this->db->query($query)->fetchAll(PDO::FETCH_KEY_PAIR);
		}

		public function getInverseOwnerCodeMap()
		{
		    $query = "
			SELECT
				company_id,
				value
			FROM company_property
			WHERE
				property='QC_OWNER_CODE'";

		    return $this->db->query($query)->fetchAll(PDO::FETCH_KEY_PAIR);
		}
	}

?>