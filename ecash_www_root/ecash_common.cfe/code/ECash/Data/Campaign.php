<?php

class ECash_Data_Campaign extends ECash_Data_DataRetriever
{
	public function getConfigInfoRow($application_id)
	{
		//GF #22158 - Added camp.site_id to selected column list
		//GF #25292 - Added camp.tier
		$query = "
				SELECT
					camp.promo_id,  --
					camp.promo_sub_code, --
					camp.site_id,
					camp.campaign_name, --
					s.name as url, --
					s.license_key, --
					cs.name as origin_url
				FROM
					application a,
					site s,
					site cs,
					campaign_info camp
				WHERE
					a.application_id = :application_id
				AND camp.application_id = a.application_id
				AND camp.campaign_info_id =
					(
						SELECT
							MAX(campaign_info_id)
						FROM
							campaign_info cref
						WHERE
							cref.application_id = camp.application_id
					)
					AND cs.site_id = camp.site_id
					AND a.enterprise_site_id = s.site_id
			";

		if (($row = DB_Util_1::querySingleRow($this->db, $query, array($application_id))) !== FALSE)
		{
			return $row;
		}
		return NULL;
	}

}
?>
