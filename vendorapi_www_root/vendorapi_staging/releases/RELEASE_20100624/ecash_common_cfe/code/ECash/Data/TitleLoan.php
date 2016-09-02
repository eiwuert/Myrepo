<?php

/**
 * Data Utility Class for Title Loans - Currently only utilized by Agean, but available for all
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 */
class ECash_Data_TitleLoan extends ECash_Data_DataRetriever
{
	/**
	 * Returns the co-borrower on a Title Loan, if one exists
	 *
	 * @param <int> $application_id
	 * @return <string>
	 */
	public function getCoBorrowerData($application_id)
	{
		$query = "
			SELECT  ac.value AS name,
					ac.application_contact_id AS id
			FROM application_contact AS ac
			WHERE ac.application_id = ?
			AND ac.type = 'co_borrower'
			LIMIT 1" ;

		return DB_Util_1::querySingleRow($this->db, $query, array($application_id), PDO::FETCH_OBJ);
	}

	/**
	 * Returns the vehicle information for a Title Loan
	 *
	 * @param <int> $application_id
	 * @return <StdClass> - Object containing various pieces of vehicle data
	 */
	public function getVehicleInfo($application_id)
	{
		$query = "
			SELECT
				vin     AS vehicle_vin,
				year    AS vehicle_year,
				model   AS vehicle_model,
				series  AS vehicle_series,
				make    AS vehicle_make,
				mileage AS vehicle_mileage
			FROM vehicle
			WHERE application_id = ?";

		return DB_Util_1::querySingleRow($this->db, $query, array($application_id), PDO::FETCH_OBJ);
	}

	/**
	 * Returns the lien fee amount for the supplied state
	 * @param <string> $state
	 * @return <string|float>
	 */
	public function getLienFee($state)
	{
		$query = "
			SELECT	fee_amount AS lien_fee
			FROM lien_fees
			WHERE state = ?";

		$args = array($state);
		return DB_Util_1::querySingleValue($this->db, $query, $args);
	}
}
?>