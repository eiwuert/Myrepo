<?php

/**
 * Factory to create OLPBlackbox_Campaign objects.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 * @subpackage Blackbox_Factory
 */
class OLPBlackbox_Factory_Campaign extends OLPBlackbox_Factory_ModelFactory
{
	/**
	 * Return a OLPBlackbox Campaign built from a Blackbox_Models_Target.
	 *
	 * @param Blackbox_Models_Target $target_model The target to build with.
	 * @param OLPBlackbox_Factory_TargetStateData $state_data_factory Used to 
	 * produce state data key/values for the campaign.
	 * @return OLPBlackbox_Campaign
	 */
	public function getCampaign(
		Blackbox_Models_View_TargetCollectionChild $target_model,
		OLPBlackbox_Factory_TargetStateData $state_data_factory)
	{
		$campaign_property_short = NULL;
		$campaign_id = NULL;
		$campaign_model = NULL;
		$state_data = NULL;
		
		if (strcasecmp($target_model->class_name, 'CAMPAIGN') == 0)
		{
			// model we've been passed is a campaign, get child model (target)
			$child = $this->getChildTargetModel($target_model);

			if (!$child)
			{
				// target is undefined or inactive, do not create the campaign
				return NULL;
			}

			/**
			 * @todo Dan said I should put a todo here so I can have an excuse later when it still exists in Blackbox 5
			 * 
			 * Basically, we needed to move the OCS campaigns out of the Original Five AMG targets. In order to have this
			 * actually work with VAPI, though, we would have needed to create five individual targets for each of the OCS
			 * campaigns. And that sucks from a maintenance perspective. So we decided to create one target for all five individual
			 * campaigns. The problem is that they need to act like the Original Five targets, so what we do here is set the
			 * property_short to the resolved alias of the current target, which will be the property short of one of the
			 * Original Five, which will work with things like resolveAlias() and other EnterpriseData functions that are
			 * scattered throughout the code.
			 */
			if (strcasecmp($child->property_short, 'amg_ocs') == 0)
			{
				$child->property_short = strtolower(EnterpriseData::resolveAlias($target_model->property_short));
			}
			
			$target = $this->getTargetOrCollection($child, $state_data_factory);
			$campaign_property_short = $target_model->property_short;
			$campaign_id = $target_model->target_id;
			$campaign_model = $target_model;
			$state_data = $state_data_factory->getTargetStateData($target_model);
		}
		else
		{
			// we've been passed a target or collection model, we'll wrap with a campaign
			// but we won't do the campaign stuff like make state data or get campaign_id
			$target = $this->getTargetOrCollection($target_model, $state_data_factory);
			$campaign_property_short = $target_model->property_short;
		}

		if ($target == NULL)
		{
			// configuration of this target failed! (most likely collection)
			return NULL;
		}
		
		$campaign =  new OLPBlackbox_Campaign(
			$campaign_property_short,
			$campaign_id,
			$target_model->weight,
			$target,
			NULL,
			$state_data
		);
		$this->getListenerHandler()->registerChild($campaign_property_short, 'CAMPAIGN', $campaign);
		if ($campaign_model)
		{
			$this->getRuleCollectionFactory($campaign_property_short)
				->setRuleCollections($campaign_model, $campaign);
		}

		return $campaign;
	}
	
	/**
	 * Returns a new TargetData model.
	 * 
	 * Normally a target data model will be provided to this factory when 
	 * constructing a campaign (since it will be a cached model) but in the case
	 * that this factory is not called by the TargetCollection factory, this 
	 * allows the getCampaign() functionality to work.
	 *
	 * @return Blackbox_Models_TargetData
	 */
	protected function getTargetDataModel()
	{
		return $this->getModelFactory()->getModel('TargetData');
	}
	
	/**
	 * Returns a new TargetDataType model.
	 * 
	 * Normally a target data type model will be provided to this factory when 
	 * constructing a campaign (since it will be a cached model) but in the case
	 * that this factory is not called by the TargetCollection factory, this 
	 * allows the getCampaign() functionality to work.
	 * 
	 * @return Blackbox_Models_TargetDataType
	 */
	protected function getTargetDataTypeModel()
	{
		return  $this->getModelFactory()->getModel('TargetDataType');
	}
		
	/**
	 * Get the child of a campaign represented by a Blackbox_Models_Target.
	 *
	 * @pre $target_model passed in is a CAMPAIGN
	 * @param Blackbox_Models_View_TargetCollectionChild $target_model Parent
	 *  (must be campaign.)
	 * @return Blackbox_Models_View_TargetCollectionChild Child of parameter.
	 */
	protected function getChildTargetModel(Blackbox_Models_View_TargetCollectionChild $target_model)
	{
		if (strcasecmp($target_model->class_name, 'CAMPAIGN') != 0)
		{
			throw new Blackbox_Exception(
				'argument to '. __METHOD__ .' must be a campaign'
			);
		}

		foreach ($target_model->getCollectionTargets($target_model->target_id) as $child)
		{
			return $child;
		}

		return NULL;
	}

	/**
	 * @pre $target_model is type COLLECTION or TARGET
	 * @throws Blackbox_Exception
	 * @param Blackbox_Models_View_TargetCollectionChild $target_model
	 * @param OLPBlackbox_Factory_TargetStateData $state_data_factory
	 * @return OLPBlackbox_Target|OLPBlackbox_TargetCollection
	 */
	protected function getTargetOrCollection(
		Blackbox_Models_View_TargetCollectionChild $target_model,
		OLPBlackbox_Factory_TargetStateData $state_data_factory
	)
	{
		if (strcasecmp($target_model->class_name, 'TARGET') == 0)
		{
			return $this->getTargetFactory()->getTarget($target_model, $state_data_factory);
		}
		elseif (strcasecmp($target_model->class_name, 'COLLECTION') == 0)
		{
			return $this->getTargetCollectionFactory($target_model->property_short)->getTargetCollection($target_model);
		}
		else
		{
			throw new Blackbox_Exception(
				'unknown model type passed to ' . __METHOD__
			);
		}
	}
}

?>
