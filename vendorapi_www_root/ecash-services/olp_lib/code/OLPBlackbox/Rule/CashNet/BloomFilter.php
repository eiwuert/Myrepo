<?php
/**
 * The CashNet Bloom filter rule.
 * 
 * Rule needs to be setup to use the email address.
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_Rule_CashNet_BloomFilter extends OLPBlackbox_Rule
{
	/**
	 * Runs a check against a CashNet bloom filter database
	 *
	 * @param Blackbox_Data $data the data we run the rule on
	 * @param Blackbox_IStateData $state_data state data passed to us
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$keys = $this->getDataValue($data);

		$bloom = BloomFilter_CashNet::getInstance();		
		return !$bloom->exists($keys['name_last'], $keys['ssn_part_3']);
	}
}