<?php

if (count($argv) < 3)
{
	echo <<<USAGE
Usage:   {$argv[0]} [mode] [customer]
Example: {$argv[0]} RC CLK

USAGE;
	exit;
}

require_once('libolution/AutoLoad.1.php');
AutoLoad_1::addSearchPath(dirname(__FILE__) . '/code/');

$mode     = $argv[1];
$customer = strtoupper($argv[2]);

$analysis_db = call_user_func(array($customer . '_Batch', 'getAnalysisDb'), $mode)->getConnection();



$b = new Blah($analysis_db);
$b->updateCosts();

exit;



class Blah
{
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var DB_IStatement_1
	 */
	protected $st_update_promo;

	/**
	 * @var DB_IStatement_1
	 */
	protected $st_update_month;

	/**
	 * @var DB_IStatement_1
	 */
	protected $st_update_overhead;

	/**
	 * @param DB_IConnection_1 $db
	 */
	public function __construct(DB_IConnection_1 $db)
	{
		$this->db = $db;
	}

	/**
	 * Update all costs
	 * @return void
	 */
	public function updateCosts()
	{
		$this->updateCampaignCosts();
		$this->updateOnlineCosts();
		$this->updateCalculatedCosts();
	}

	/**
	 * Update the campaign/promo specific costs
	 * @return void
	 */
	protected function updateCampaignCosts()
	{
		$costs = $this->fetchCostsByPromo();

		foreach ($this->countLoansByPromo() as $row)
		{
			$promo_id = $row['promo_id'];

			$this->updatePromoAcquistion(
				$promo_id,
				$this->divideCosts($costs[$promo_id], $row['total'])
			);
		}
	}

	/**
	 * Update online and monthly costs, not associated to campaigns/promos
	 * @return void
	 */
	protected function updateOnlineCosts()
	{
		$costs = $this->fetchCostsByMonth();

		foreach ($this->countLoansByMonth() as $row)
		{
			$m = $row['month'];
			$date = strtotime($row['month'].'-1');

			if (!isset($costs[$row['company_id']][$m]))
			{
				echo "No costs found for {$row['company_id']}/{$m}, skipping...\n";
				continue;
			}

			$c = $costs[$row['company_id']][$m];

			$this->updateOnlineAcquistion(
				$row['company_id'],
				$date,
				$this->divideCosts($c['tss_acq'], $row['new'])
			);

			$this->updateOverhead(
				$row['company_id'],
				$date,
				$this->divideCosts($c['tss_ovh'], $row['total']),
				$this->divideCosts($c['clk_ovh'], $row['total'])
			);
		}
	}

	/**
	 * Divide $costs by $loans and protect against division by zero
	 *
	 * @param float $costs
	 * @param int $loans
	 * @return float
	 */
	protected function divideCosts($costs, $loans)
	{
		return ($loans > 0) ? ($costs / $loans) : 0;
	}

	/**
	 * Fetch campaign costs indexed by promo ID
	 *
	 * @return array
	 */
	protected function fetchCostsByPromo()
	{
		$query = "
			SELECT promo_id,
				cost
			FROM promo
		";
		$st = $this->db->query($query);

		$costs = array();

		foreach ($st as $r)
		{
			$costs[$r['promo_id']] = $r['cost'];
		}
		return $costs;
	}

	/**
	 * Fetch online and monthly costs indexed by company and month
	 *
	 * @return array
	 */
	protected function fetchCostsByMonth()
	{
		$query = "
			SELECT *
			FROM clk_costs
		";
		$st = $this->db->query($query);

		$costs = array();
		$company_map = $this->fetchCompanyMap();

		foreach ($st as $row)
		{
			$row = array_change_key_case($row);

			$month = substr_replace((string)$row['date'], '-', 4, 0);
			$company_id = $company_map[$row['company']];

			if (!isset($costs[$company_id])) $costs[$company_id] = array();
			$costs[$company_id][$month] = $row;
		}

		return $costs;
	}

	/**
	 * Fetch a map of company name to company ID
	 *
	 * @return array
	 */
	protected function fetchCompanyMap()
	{
		$query = "
			SELECT company_id,
				name_short
			FROM company
		";
		$st = $this->db->query($query);

		$map = array();

		foreach ($st as $r)
		{
			$map[strtolower($r['name_short'])] = $r['company_id'];
		}
		return $map;
	}

	/**
	 * Count the number of loans by promo
	 *
	 * @return DB_IStatement_1
	 */
	protected function countLoansByPromo()
	{
		$query = "
			SELECT loan.promo_id,
				COUNT(*) AS total
			FROM loan
				JOIN loan_performance USING (loan_id)
				JOIN promo ON (promo.promo_id=loan.promo_id)
			WHERE is_funded = 1
			GROUP BY loan.promo_id
		";
		return $this->db->query($query);
	}

	/**
	 * Count the nuber of new and total funded loans by month
	 *
	 * @return DB_IStatement_1
	 */
	protected function countLoansByMonth()
	{
		$query = "
			SELECT loan.company_id,
				DATE_FORMAT(date_advance, '%Y-%m') AS month,
				COUNT(*) AS total,
				SUM(loan_number = 1
					AND promo.promo_id IS NULL) AS new
			FROM loan
				JOIN loan_performance USING (loan_id)
				LEFT JOIN promo ON (promo.promo_id = loan.promo_id)
			WHERE is_funded = 1
			GROUP BY company_id,
				month
		";
		return $this->db->query($query);
	}

	/**
	 * Update the acquisition cost for a specific campaign
	 *
	 * @param int $promo_id
	 * @param float $acquistion
	 * @return void
	 */
	protected function updatePromoAcquistion($promo_id, $acquistion)
	{
		if (!$this->st_update_promo)
		{
			$query = "
				UPDATE loan_performance perf,
					loan
				SET acquisition_cost = ?
				WHERE loan.loan_id = perf.loan_id
					AND loan.promo_id = ?
					AND is_funded = 1
			";
			$this->st_update_promo = $this->db->prepare($query);
		}

		$this->st_update_promo->execute(array(
			$acquistion,
			$promo_id,
		));
	}

	/**
	 * Update online acquisition costs for the given company and month
	 *
	 * @param int $company_id
	 * @param int $month Unix timestamp
	 * @param float $acquisition
	 */
	protected function updateOnlineAcquistion($company_id, $month, $acquisition)
	{
		if (!$this->st_update_month)
		{
			$query = "
				UPDATE loan_performance perf,
					loan
				SET acquisition_cost = ?
				WHERE loan.loan_id = perf.loan_id
					AND perf.company_id = ?
					AND loan.date_advance BETWEEN ? AND ?
					AND loan.loan_number = 1
					AND is_funded = 1
					AND NOT EXISTS (
						SELECT *
						FROM promo
						WHERE promo.promo_id = loan.promo_id
					)
			";
			$this->st_update_month = $this->db->prepare($query);
		}

		$this->st_update_month->execute(array(
			$acquisition,
			$company_id,
			date('Y-m-1', $month),
			date('Y-m-t', $month),
		));
	}

	/**
	 * Update the internal and external overhead costs for a given month
	 *
	 * @param int $company_id
	 * @param int $month Unix timestamp
	 * @param float $tss_overhead
	 * @param float $ext_overhead
	 */
	protected function updateOverhead($company_id, $month, $tss_overhead, $ext_overhead)
	{
		if (!$this->st_update_overhead)
		{
			$query = "
				UPDATE loan_performance perf,
					loan
				SET overhead_cost = ?,
					external_cost = ?
				WHERE loan.loan_id = perf.loan_id
					AND perf.company_id = ?
					AND loan.date_advance BETWEEN ? AND ?
					AND is_funded = 1
			";
			$this->st_update_overhead = $this->db->prepare($query);
		}

		$this->st_update_overhead->execute(array(
			$tss_overhead,
			$ext_overhead,
			$company_id,
			date('Y-m-1', $month),
			date('Y-m-t', $month),
		));
	}

	protected function updateCalculatedCosts()
	{
		$query = "
			UPDATE loan_performance lp,
				loan l
			SET
				lp.cost = (lp.baddebt_principal_and_fees - lp.baddebt_paid_principal_and_fees
					+ IF(overhead_cost is null, 0, overhead_cost)
					+ IF(acquisition_cost is null, 0, acquisition_cost)),
				lp.profit = (l.fees_accrued
					- (lp.baddebt_principal_and_fees - lp.baddebt_paid_principal_and_fees
						+ IF(overhead_cost is null, 0, overhead_cost)
						+ IF(acquisition_cost is null, 0, acquisition_cost)))
			WHERE lp.loan_id = l.loan_id
				AND lp.overhead_cost IS NOT null
				AND lp.acquisition_cost IS NOT null
		";
	}
}

?>
