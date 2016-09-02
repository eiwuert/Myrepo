<?php
/**
 * Suppression list factory for CLK.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Factory_Legacy_SuppressionList extends OLPBlackbox_Factory_Legacy_SuppressionList
{
	/**
	 * Returns a RuleCollection of suppression lists for CLK targets.
	 *
	 * @param array $lists array of lists to generate
	 * @return OLPBlackbox_RuleCollection
	 */
	public function getSuppressionLists(array $lists)
	{
		switch (OLPBlackbox_Config::getInstance()->blackbox_mode)
		{
			case OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION:
				$allowed_fields = array('social_security_number', 'bank_aba', 'email_primary');
				$list_collection = parent::getSuppressionLists($lists, $allowed_fields);
				break;
			case OLPBlackbox_Config::MODE_ECASH_REACT:
				$allowed_fields = array('home_zip', 'social_security_number', 'employer_name');
				$list_collection = parent::getSuppressionLists($lists, $allowed_fields);
				break;
			case OLPBlackbox_Config::MODE_BROKER:
			default:
				$list_collection = parent::getSuppressionLists($lists);
				break;
		}

		return $list_collection;
	}
}
?>