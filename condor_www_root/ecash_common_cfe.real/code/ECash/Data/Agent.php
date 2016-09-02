<?php

	class ECash_Data_Agent extends ECash_Data_DataRetriever
	{
		public function getCollectionsAgents($company_id)
		{
			$query = "
				SELECT
					agent_id,
					name_first,
					name_last
				FROM agent a
				JOIN agent_access_group AS aag USING (agent_id)
				JOIN access_group_control_option AS agco USING (access_group_id)
				JOIN control_option AS co USING (control_option_id)
				WHERE
					co.name_short = 'populate_collections_agent'
					AND company_id = ?
				ORDER BY name_first ASC, name_last ASC
			";

			$st = DB_Util_1::queryPrepared($this->db, $query, array($company_id));

			$result = array();

			while ($row = $st->fetch(PDO::FETCH_OBJ))
			{
				$result[$row->agent_id] = ucfirst($row->name_first) . " " . ucfirst($row->name_last);
			}

			return $result;
		}

		public function getAllAgents($company_id)
		{
			/**
			 * @todo Why does this hardcode the system_id?
			 */
			$query = "
				SELECT DISTINCT agent_id, name_first, name_last
				FROM agent a
				JOIN agent_access_group AS aag USING (agent_id)
				WHERE
					company_id = ?
					AND a.system_id = 3
					AND a.active_status = 'active'
					ORDER BY name_first ASC, name_last ASC
			";

			$st = DB_Util_1::queryPrepared($this->db, $query, array($company_id));

			while (($row = $st->fetch(PDO::FETCH_OBJ)) !== FALSE)
			{
				$ids[$row->agent_id] = ucfirst($row->name_first) . " " . ucfirst($row->name_last);
			}

			return $ids;
		}
	}

?>