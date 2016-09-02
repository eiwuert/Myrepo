<?php

class ECash_Data_Application extends ECash_Data_DataRetriever
{
	/**
	 *  @TODO remove 'paid' and 'recovered' from WHERE and replace
	 *  with application_status_id(s) from
	 *  ECash_Models_Reference_ApplicationStatusFlat
	 */
	public function getMaxPreviousLoanAmount($application_id)
	{
		$query = "
			SELECT
				MAX(app.fund_actual)
			FROM
				application AS app
			JOIN
				application AS app_orig ON app.ssn = app_orig.ssn
			JOIN
				application_status AS app_status ON app.application_status_id = app_status.application_status_id
			WHERE
				app_orig.application_id = ?
				AND app_status.name_short IN ('paid', 'recovered')";

		$st = $this->db->prepare($query);
		$st->execute(array($this->application->application_id));

		return $st->fetchColumn();
	}

	/**
	 * Returns summary info about the site for this particular application
	 *
	 * @param int $application_id
	 * @return stdClass
	 */
	public function getCampaignInfo($application_id)
	{
		//GF #22158 - Added camp.site_id to selected column list
		$query = "
				SELECT
					camp.campaign_info_id,
					camp.promo_id,
					camp.promo_sub_code,
					camp.site_id,
					s.name as url,
					s.license_key,
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

		$result = DB_Util_1::queryPrepared($this->db, $query, array(
			'application_id' => $application_id
		));

		return $result->fetch(PDO::FETCH_OBJ);
	}

	public function getLoanTypeId($application_id)
	{
		$query = "
			select loan_type_id
			from application
			where
				application_id = ?
		";

		return DB_Util_1::querySingleValue($this->db, $query, array($application_id));
	}

	public function getStatusId($application_id)
	{
		$query = "
			select application_status_id
			from application
			where
				application_id = ?
		";

		return DB_Util_1::querySingleValue($this->db, $query, array($application_id));
	}

	public function getLoanAmountMetrics($application_id)
	{
		$query = "
			select
				income_monthly,
				is_react,
				rule_set_id
			from application
			where
				application_id = ?
		";

		return DB_Util_1::querySingleRow($this->db, $query, array($application_id));
	}

	public function getCompanyId($application_id)
	{
		$query = "
			select company_id
			from application
			where
				application_id = ?
		";

		return DB_Util_1::querySingleValue($this->db, $query, array($application_id));
	}

	public function getPersonalReferences($application_id)
	{
		$query = "
			SELECT 	personal_reference_id,
					name_full,
					phone_home,
					relationship,
					name_full AS full_name,
					phone_home AS phone
			FROM personal_reference
			WHERE application_id = ?";

		return DB_Util_1::queryPrepared($this->db, $query, array($application_id))->fetchAll(PDO::FETCH_OBJ);
	}

	public function getPreviousStatusId($application_id)
	{
		$query = "
			SELECT DISTINCT
				sh.application_status_id
			FROM status_history sh
			JOIN application app on sh.application_id = app.application_id
			WHERE
				sh.application_id = ?
				and sh.application_status_id <> app.application_status_id
			ORDER BY sh.date_created DESC
			LIMIT 1
		";

		return DB_Util_1::querySingleValue($this->db, $query, array($application_id));
	}

	public function getStatusHistory($application_id)
	{
		$query = "
			SELECT DISTINCT
				sh.application_status_id
			FROM status_history sh
			JOIN application app on sh.application_id = app.application_id
			WHERE
				sh.application_id = ?
				and sh.application_status_id <> app.application_status_id
			ORDER BY sh.date_created DESC
		";

		return DB_Util_1::querySingleColumn($this->db, $query, array($application_id));
	}

	public function getABACount($aba,$company_id)
	{
		$query = "		
				SELECT
					count(distinct(ssn)) as count
				FROM
					application
				WHERE
					bank_aba = ?
				AND 
					company_id = ?
		";
		return DB_Util_1::querySingleValue($this->db, $query, array($aba,$company_id));
	
	}
	
	public function getAuditLog($application_id)
	{
		$query = "
			SELECT
				aa.date_created,
				aa.table_name,
				aa.column_name,
				aa.value_before,
				aa.value_after,
				aa.agent_id,
				a.name_first,
				a.name_last
			FROM
				application_audit AS aa
			LEFT JOIN agent a ON aa.agent_id = a.agent_id
			WHERE
				application_id = ?
			ORDER BY
				date_created";

		$st = DB_Util_1::queryPrepared($this->db, $query, array($application_id));

		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	 * Unset the attribute on column '$column' in table '$table' for row id '$row_id'
	 *
	 * @param int $row_id
	 * @param string $flag
	 * @param string $column
	 * @param string $company_id
	 * @param string $table
	 */	
	public function clearContactFlags($flag, $column, $table = 'application', $company_id, $row_id)
	{

		$query = "
			delete from application_field
			where
				company_id = ?
				and table_row_id = ?
				and column_name = ?
				and table_name = ?
				and application_field_attribute_id = ?
		";

		DB_Util_1::execPrepared($this->db, $query, array(
			$company_id,
			$row_id,
			$column,
			$table,
			$flag
		));
	}	
	
	/**
	 * Unset the attribute on column '$column' in table '$table' for row id '$row_id'
	 *
	 * @param int $row_id
	 * @param string $flag
	 * @param string $column
	 * @param string $table
	 */
	public function clearContactFlagsByColumn($column, $table = 'application', $company_id, $row_id)
	{
		$query = "
			delete from application_field
			where
				company_id = ?
				and table_row_id = ?
				and column_name = ?
				and table_name = ?
		";

		DB_Util_1::execPrepared($this->db, $query, array(
			$company_id,
			$row_id,
			$column,
			$table
		));
	}
	
	public function clearContactFlagsByType($flag, $table = 'application', $company_id, $row_id)
	{
		$query = "
			delete from application_field
			where
				company_id = ?
				and table_row_id = ?
				and table_name = ?
				and application_field_attribute_id = ?
		";

		DB_Util_1::execPrepared($this->db, $query, array(
			$company_id,
			$row_id,
			$table,
			$flag
		));
	}	
	
	public function clearContactFlagsByRow($table = 'application', $company_id, $row_id)
	{
		$query = "
			delete from application_field
			where
				company_id = ?
				and table_row_id = ?
				and table_name = ?
		";

		DB_Util_1::execPrepared($this->db, $query, array(
			$company_id,
			$row_id,
			$table
		));
	}	
	
	public function getContactFlags($table = 'application', $row_id)
	{
		$query = "
			SELECT
				af.column_name,
				afa.field_name,
				af.agent_id
			FROM application_field af
			INNER JOIN application_field_attribute afa
				ON (afa.application_field_attribute_id = af.application_field_attribute_id)
			WHERE
				table_name = ?
				AND table_row_id = ?
		";

		$result = DB_Util_1::queryPrepared($this->db, $query, array($table, $row_id));

		return $result->fetchAll(PDO::FETCH_OBJ);
	}	
	
	public function setFlag($flag,$agent_id,$application_id, $company_id)
	{
		$query = "
			INSERT INTO application_flag SET
				modifying_agent_id = :agent_id,
				flag_type_id = (select flag_type_id from flag_type where name_short = :flag_name_short),
				application_id = :application_id,
				company_id = :company_id,
				active_status = 'active'
			ON DUPLICATE KEY UPDATE active_status = 'active', modifying_agent_id = :agent_id, company_id = :company_id";

		$args = array(
			'agent_id' => $agent_id,
			'flag_name_short' => $flag,
			'application_id' => $application_id,
			'company_id' => $company_id
		);

		DB_Util_1::execPrepared($this->db, $query, $args);
	}

	public function clearFlag($flag,$agent_id,$application_id, $company_id)
	{
		$query = "
			UPDATE application_flag
			SET
				active_status = 'inactive',
				modifying_agent_id = :agent_id,
				company_id = :company_id
			WHERE
				application_id = :application_id
				AND flag_type_id = (select flag_type_id from flag_type where name_short = :flag_name_short)
		";

		$args = array(
			'agent_id' => $agent_id,
			'flag_name_short' => $flag,
			'application_id' => $application_id,
			'company_id' => $company_id
		);

		DB_Util_1::execPrepared($this->db, $query, $args);
	}	
	
	public function getFlag($flag,$application_id)
	{
		$query = "
			SELECT COUNT(*)
			FROM application_flag
			JOIN flag_type ON (flag_type.flag_type_id = application_flag.flag_type_id)
			WHERE
				application_id = ?
				AND flag_type.name_short = ?
				AND application_flag.active_status = 'active'
				AND flag_type.active_status = 'active'
			";

		return (DB_Util_1::querySingleValue(
			$this->db,
			$query,
			array($application_id, $flag)
			) > 0
		);
	}	
	
	public function getFlags($aapplication_id)
	{
		$query = "
			SELECT
				flag_type.*
			FROM application_flag
			JOIN flag_type USING (flag_type_id)
			WHERE
				application_id = ?
				AND application_flag.active_status = 'active'
		";


		return DB_Util_1::queryPrepared($this->db, $query, array($aapplication_id));

	}
	
	public function removeTags($prefix, $application_id)
	{
			$query = "
				DELETE FROM 
					application_tags 
				WHERE 
					application_id = ? 
				AND EXISTS (
						SELECT 1
						FROM application_tag_details
						WHERE
						tag_id = application_tags.tag_id AND
						tag_name LIKE '?%'
					)";
			
			DB_Util_1::execPrepared($this->db, $query, array($application_id, $prefix));					

	}
}

?>