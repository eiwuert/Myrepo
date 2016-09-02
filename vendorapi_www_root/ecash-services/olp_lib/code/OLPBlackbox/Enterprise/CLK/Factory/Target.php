<?php
/**
 * @see OLPBlackbox_Enterprise_Generic_Factory_Target
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Factory_Target extends OLPBlackbox_Enterprise_Generic_Factory_Target
{
	/**
	 * Controls whether a DataX failure in the API throws a FailException
	 * @return bool
	 */
	protected function getDataXThrowsFailException()
	{
		return TRUE;
	}

	protected function getPostRule($property_short)
	{
		return new OLPBlackbox_Rule_NoBillRateDecorator(parent::getPostRule($property_short));
	}
}

?>
