<?php
/**
 * Interface to the olp.vetting_ssns table.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class Vetting_SSN
{
	/**
	 * PDO-like DB object.
	 *
	 * @var object 
	 */
	protected $db;
	
	/**
	 * Number of days before SSNs are not "overactive"
	 *
	 * @var int
	 */
	protected $expires_after;

	/**
	 * Create a Vetting_SSN object which is used to manipulate/report on the olp.vetting_ssns table.
	 *
	 * @param object $db PDO like database object.
	 * @param int $expires_after Number of days before SSNs are "overactive"
	 * 
	 * @return void
	 */
	public function __construct($db, $expires_after = 120)
	{
		if (!method_exists($db, 'prepare'))
		{
			throw new InvalidArgumentException(
				'db must be PDO compatible'
			);
		}
		// ctype_digit not available on RC/LIVE :(
		if (!is_numeric(strval($expires_after)))
		{
			throw new InvalidArgumentException(sprintf(
				'expires_after argument must be int, got %s',
				var_export($expires_after, TRUE))
			);
		}
		$this->expires_after = intval($expires_after);
		$this->db = $db;
	}

	/**
	 * Records when an SSN was seen.
	 *
	 * @param string $ssn SSN of applicant, should be encrypted.
	 * @param int $application_id The application which is using the SSN.
	 * @throws DB_MySQL4AdapterException_1
	 * @throws PDOException
	 * @return void
	 */
	public function ssnSeen($ssn, $application_id)
	{
		$ids = array();
		$now = $this->getNow();

		$select = "SELECT id 
			FROM vetting_ssns 
			WHERE ssn_encrypted = ? AND application_id = ?";
		$statement = $this->db->prepare($select);
		$args = array($ssn, $application_id);
		if ($statement->execute($args)) 
		{
			foreach ($statement as $row)
			{
				$ids[] = $row['id'];
			}
		}

		if (!count($ids))
		{
			$query = "INSERT INTO vetting_ssns 
					(ssn_encrypted, application_id, date_created, date_modified)
				VALUES(?, ?, ?, ?)";
			$args = array($ssn, $application_id, $now, $now);
		}
		else
		{
			$ids = implode(', ', $ids);
			$query = "UPDATE vetting_ssns
				SET ssn_encrypted = ?, application_id = ?,
					date_modified = ?
				WHERE id IN ('$ids')";
			$args = array($ssn, $application_id, $now);
		}

		$statement = $this->db->prepare($query);
		$statement->execute($args);
	}

	/**
	 * Returns the current date. 
	 *
	 * Designed to be mocked for PHPUnit.
	 *
	 * @return string Today's date.
	 */
	protected function getNow()
	{
		return date('Y-m-d');
	}

	/**
	 * Checks to see whether an ssn is overactive.
	 *
	 * @param string $ssn Encrpyted SSN to check.
	 * @param int $exclude_id Application id to exclude from search.
	 * @param string $now Date string (YYYY-MM-DD)
	 * @throws DB_MySQL4AdapterException_1
	 * @throws PDOException
	 * @throws Exception
	 * @return bool TRUE == overactive, FALSE otherwise
	 */
	public function ssnOverused($ssn, $exclude_id, $now = NULL)
	{
		if (!$now) $now = $this->getNow();

		$query = "SELECT COUNT(ssn_encrypted) AS ssn_count
			FROM vetting_ssns
			WHERE ssn_encrypted = ?
				AND date_modified >= DATE_SUB(?, INTERVAL ? DAY)
				AND application_id != ?";

		$statement = $this->db->prepare($query);
		$args = array(
			$ssn,
			$now,
			$this->expires_after,
			$exclude_id
		);
		$statement->execute($args);
		return $statement->fetch(DB_MySQL4StatementAdapter_1::FETCH_OBJ)->ssn_count < 1;
	}
}
?>
