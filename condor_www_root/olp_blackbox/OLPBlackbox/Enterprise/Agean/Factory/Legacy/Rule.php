<?php

/**
 * Rule factory for Agean.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Agean_Factory_Legacy_Rule extends OLPBlackbox_Factory_Legacy_Rule
{
	/**
	 * Returns an instance of OLPBlackbox_Enterprise_Generic_Factory_Legacy_SuppressionList.
	 *
	 * @return OLPBlackbox_Enterprise_Generic_Factory_Legacy_SuppressionList
	 */
	protected function getSuppressionListFactory()
	{
		return new OLPBlackbox_Enterprise_Generic_Factory_Legacy_SuppressionList();
	}
}

?>
