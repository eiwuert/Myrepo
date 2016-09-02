<?php

/**
 * Rule factory for CLK.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Factory_Rule extends OLPBlackbox_Factory_Rule
{
	/**
	 * Returns an instance of OLPBlackbox_Enterprise_CLK_Factory_Legacy_SuppressionList.
	 *
	 * @return OLPBlackbox_Enterprise_CLK_Factory_Legacy_SuppressionList
	 */
	protected function getSuppressionListFactory()
	{
		return new OLPBlackbox_Enterprise_CLK_Factory_Legacy_SuppressionList();
	}
}

?>