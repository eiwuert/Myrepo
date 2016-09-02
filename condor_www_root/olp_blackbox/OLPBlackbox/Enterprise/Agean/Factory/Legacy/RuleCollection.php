<?php
/**
 * RuleCollection factory for Agean.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Agean_Factory_Legacy_RuleCollection extends OLPBlackbox_Factory_Legacy_RuleCollection
{
	/**
	 * Returns the suppression list collection.
	 *
	 * @param array $lists the lists to put into the collection
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getSuppressionLists(array $lists)
	{
		return OLPBlackbox_Enterprise_Generic_Factory_Legacy_SuppressionList::getRuleCollection($lists);
	}
}
?>
