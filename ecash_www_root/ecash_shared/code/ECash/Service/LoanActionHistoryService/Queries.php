<?php

/**
 * ECash Commercial Loan Action History Sevice Query Class
 *
 * @copyright Copyright &copy; 2014 aRKaic Equipment
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

class ECash_Service_LoanActionHistoryService_Queries
{

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;
	
	public function __construct() {
		$this->db = ECash::getAppSvcDB();
	}

	/**
	 * Records a loan action. 
	 *
	 * @returns the loan_action_history_id key geneterated by the database auto number
	 */
	public function saveLoanActionHistoryQuery($loanAction){
		$date = date("Y-m-d G:i:s");
		$query = 'INSERT INTO loan_action_history (
				loan_action_id,
				application_id,
				application_status_id,
				date_created,
				agent_id,
				comment_id,
				loan_action_section_id
			) VALUES (
				(SELECT loan_action_id FROM loan_actions WHERE LOWER(name_short) = '.strtolower($loanAction->loan_action).' LIMIT 1),
				(SELECT application_id FROM application WHERE application_id = '.$loanAction->application_id.' LIMIT 1),
				(SELECT application_status_id FROM application_status WHERE LOWER(application_status_name) = "'.strtolower($loanAction->application_status).'" LIMIT 1),
				'.$date.',
				'.$loanAction->agent_id.',
				'.$loanAction->coment_id.',
				(SELECT loan_action_section_id FROM loan_action_section WHERE LOWER(section) = '.strtolower($loanAction->loan_action_section).' LIMIT 1)
			);';
		$result = $this->db->query($query);
		$rows = $result->execute();
		return($this->db->lastInsertId());
	}

	/**
	 * Displays the loan actions for an application. 
	 *
	 * @returns query result set object
	 */
	public function getLoanActionQuery($application_id,$action) {

		$query = 'SELECT lah.date_created AS date_created,
				lah.date_created AS loan_action_timestamp,
				lah.loan_action_history_id AS loan_action_history_id,
				lah.agent_id AS agent_id,
				0 AS comment_id,
				la.loan_action_id AS loan_action_id,
				la.name_short AS loan_action_name_short,
				las.loan_action_section_id AS loan_action_section_id,
				las.name_short AS loan_action_section_name_short,
				st.application_status_name AS status
			FROM loan_action_history AS lah
				JOIN application AS ap USING (application_id)
				LEFT JOIN loan_actions AS la USING (loan_action_id)
				LEFT JOIN loan_action_section AS las USING (loan_action_section_id)
				LEFT JOIN application_status AS st ON (lah.application_status_id = st.application_status_id)
			WHERE ap.application_id = '.$application_id;
		if ($action && (is_string($action)) && ($action != "null")) $query .= ' AND LOWER(la.name_short) = '.strtolower($action);
		$query .= ';';

		$result = $this->db->query($query);
		$rows = $result->fetchAll(DB_IStatement_1::FETCH_OBJ);
		return($rows);
	}


}
