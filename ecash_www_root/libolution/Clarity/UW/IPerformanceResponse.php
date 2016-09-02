<?php

/**
 * Response interface that the Clarity calls require
 *
 * Because we've more or less standardized on a single type-ish of
 * call, there are some methods here that normally might be put
 * in a separate interface (isIDVFailure, for instance). If in the
 * future things vary more, it should be moved.
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
interface Clarity_UW_IPerformanceResponse extends Clarity_UW_IResponse
{
	/**
	 * Indicate whether the failure was in IDV or otherwise
	 * @return bool
	 */
	public function isIDVFailure();

	/**
	 * Return the global decision
	 *
	 * @return string
	 */
	public function getDecision();

	/**
	 * Return the performance score
	 * @return int
	 */
	public function getScore();

	/**
	 * Return the decision buckets
	 *
	 * @return array
	 */
	public function getDecisionBuckets();
}
