<?php

/**
 * abstract rate calculator
 * 
 * Originally from http://gforge.sellingsource.com/svn/ecash/ecash_common/branches/recash_prep/code/ECash/Scheduling/InterestCalculator.php
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Transactions
 */
abstract class ECash_Transactions_RateCalculator implements ECash_Transactions_IRateCalculator
{
	const DEFAULT_PERCENT = 30;	

	/**
	 * @var int
	 */ 
	protected $percent;
	
	/**
	 * @var ECash_Transactions_Rounder
	 */
	protected $rounder;

	/**
	 * @param ECash_Transactions_Rounder $rounder 
	 * @param int $percent percent as an integer (0-100)
	 */
	public function __construct(ECash_Transactions_Rounder $rounder, $percent)
	{
		$this->rounder = $rounder === NULL ? new ECash_Transactions_Rounder() : $rounder;
		$this->percent = ($percent === NULL) ? self::DEFAULT_PERCENT : $percent;
	}

	/**
	 * Rounds the amount using the calculators current rules.
	 *
	 * Public so the interest rounder can be used in various places
	 * 
	 * @param float $amount
	 * @return float
	 */
	public function round($amount)
	{
		return $this->rounder->round($amount);
	}

	public function getPercent()
	{
		return $this->percent;
	}
}

?>