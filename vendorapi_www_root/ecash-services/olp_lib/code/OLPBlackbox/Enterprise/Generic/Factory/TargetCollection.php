<?php

/**
 * This is deprecated until there's a good way to find out if a collection is "enterprise" and stuff. 
 * @todo Fix the 'CLK', make the target collection factory call this properly, etc.
 * @deprecated
 */
class OLPBlackbox_Enterprise_Generic_Factory_TargetCollection extends OLPBlackbox_Factory_TargetCollection 
{
	/**
	 * Overridden to account for the "invalidate_active" parameter needed by 
	 * enterprise target collections.
	 * 
	 * @param Blackbox_Models_IReadableTarget $target_model Collection model
	 * used to assemble the TargetCollection
	 * @return OLPBlackbox_Enterprise_TargetCollection
	 */
	protected function getCollectionClass(Blackbox_Models_IReadableTarget $target_model)
	{
		/* @var $is_clk bool indicates 'invalidate_active' should be true */
		$is_clk = strcasecmp(EnterpriseData::resolveAlias($target_model->name), EnterpriseData::COMPANY_CLK) == 0;

		$target_collection = new OLPBlackbox_Enterprise_TargetCollection(
			$target_model->name, 
			NULL, 
			$is_clk, 
			$this->getTargetTags($target_model)
		);
		
		$target_collection->setPicker($this->getPicker($target_model));
		
		return $target_collection;
	}
}

?>
