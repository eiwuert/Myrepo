<?php

/**
 * The Agent Affiliation Model
 *
 * @package Models
 * @author  Mike Lively <mike.lively@sellingsource.com>
 */
class ECash_Models_AgentAffiliation extends ECash_Models_WritableModel
{
	/**
	 * The columns in the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array(
			'date_modified',
			'date_created',
			'date_expiration_actual',
			'company_id',
			'application_id',
			'agent_affiliation_id',
			'affiliation_area',
			'affiliation_type',
			'agent_id',
			'affiliation_status',
			'agent_affiliation_reason_id',
		);
	}

	/**
	 * The name of the model table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'agent_affiliation';
	}

	/**
	 * The primary key columns
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array(
			'agent_affiliation_id',
		);
	}

	/**
	 * The auto increment column
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'agent_affiliation_id';
	}
	
	/**
	 * Loads the active affiliation for the application, area and type.
	 *
	 * True will be returned if the affiliation is found, false otherwise.
	 *
	 * @param int $agent_id
	 * @param int $application_id
	 * @return bool
	 */
	public function loadActiveAffiliation($application_id, $area, $type)
	{
		$query = "
			SELECT * FROM {$this->getTableName()}
			WHERE
				application_id = ?
				AND affiliation_area = ?
				AND affiliation_type = ?
				AND (
					date_expiration_actual IS NULL OR
					date_expiration_actual > NOW()
				)
			ORDER BY date_created DESC
			LIMIT 1
		";
			
		$row = DB_Util_1::querySingleRow(
			$this->getDatabaseInstance(), 
			$query,
			array($application_id, $area, $type)
		);
		
		if (!empty($row))
		{
			$this->fromDbRow($row);
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Expires all affiliations matching the where args and have not already 
	 * expired.
	 *
	 * If the date_expiration_actual column is passed in $where_args it will 
	 * be ignored. The number of affiliations expired is returned.
	 *
	 * @param array $where_args
	 * @return int
	 */
	public function expireAffiliations(array $where_args)
	{
		if (isset($where_args['date_expiration_actual']))
		{
			unset($where_args['date_expiration_actual']);
		}
		
		$query = "
			UPDATE {$this->getTableName()}
			SET
				date_expiration_actual = NOW()
			".DB_Util_1::buildWhereClause($where_args)."
				AND (
					date_expiration_actual IS NULL
					OR date_expiration_actual > NOW()
				)
		";
				
		return DB_Util_1::execPrepared($this->db, $query, $where_args);
	}

	/**
	 * Reassigns all active affiliations of an agent to another agent. 
	 *
	 * @param int $from_agent_id
	 * @param int $to_agent_id
	 */
	public function reassign($from_agent_id, $to_agent_id)
	{
		$query = "
			UPDATE {$this->getTableName()}
			SET
				agent_id = ?
			WHERE
				agent_id = ?
				AND (
					date_expiration_actual IS NULL OR
					date_expiration_actual > NOW()
				)
		";
		
		return DB_Util_1::execPrepared(
			$this->getDatabaseInstance(),
			$query,
			array($to_agent_id, $from_agent_id)
		);
	}
}

?>
