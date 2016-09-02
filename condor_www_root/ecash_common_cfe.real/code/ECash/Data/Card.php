<?php

class ECash_Data_Card extends ECash_Data_DataRetriever
{

	public function getFundableCardID($ssn, $company_id)
	{
		$query = "
				SELECT card.provider_card_id
				FROM
					card
					JOIN customer c ON c.customer_id = card.customer_id
					JOIN card_status cs ON cs.card_status_id = card.card_status_id
				WHERE
					c.ssn = ?
				AND c.company_id = ?
				and cs.is_fundable
				LIMIT 1";

		$st = $this->db->prepare($query);
		$st->execute(array($ssn, $company_id));

		return $st->fetchColumn();
	}

	public function getValidCardID($ssn, $company_id)
	{
		$query = "
				SELECT card.provider_card_id
				FROM
					card
					JOIN customer c ON (c.customer_id = card.customer_id)
					JOIN card_status cs ON (cs.card_status_id = card.card_status_id)
				WHERE
					c.ssn = ?
				AND c.company_id = ?
				and	cs.is_valid
				ORDER BY card.date_created DESC
				LIMIT 1";

		$st = $this->db->prepare($query);
		$st->execute(array($ssn, $company_id));

		return $st->fetchColumn();
	}


}

?>