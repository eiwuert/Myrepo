<?php

	class ECash_Data_Campaign extends ECash_Data_DataRetriever
	{
		public function getConfigInfoRow($application_id)
		{
			$query = "
			SELECT
				camp.campaign_info_id, 
				camp.promo_id, 
				camp.promo_sub_code, 
				s.name as url,
				s.license_key,
				camp.campaign_name
			FROM
				application a
			inner join site s on (a.enterprise_site_id = s.site_id)
			inner join campaign_info camp on (camp.application_id = a.application_id) 		
			WHERE
				a.application_id = ?
			AND camp.campaign_info_id = 
				(
					SELECT
						MAX(campaign_info_id) 
					FROM
						campaign_info cref
					WHERE
						cref.application_id = camp.application_id
				)";
			
			if (($row = DB_Util_1::querySingleRow($this->db, $query, array($application_id))) !== FALSE)
			{
				return $row;
			}
			return NULL;
		}
		
	}
?>