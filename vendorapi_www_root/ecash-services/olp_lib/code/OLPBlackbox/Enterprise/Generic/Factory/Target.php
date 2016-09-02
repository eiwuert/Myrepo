<?php

/**
 * Base factory for Enterprise targets.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @package OLPBlackbox
 */
class OLPBlackbox_Enterprise_Generic_Factory_Target extends OLPBlackbox_Factory_Target
{
	/**
	 * Returns a new OLPECash_VendorAPI instance.
	 *
	 * @param string $property_short
	 * @return OLPECash_VendorAPI
	 */
	protected function getECashVendorApi($property_short)
	{
		return new OLPECash_VendorAPI(
			$this->getConfig()->mode,
			$this->getConfig()->application_id,
			$property_short,
			$this->getOLPFactory()
		);
	}

	/**
	 * Returns a new OLPBlackbox_Enterprise_Generic_Rule_PostAPI object
	 *
	 * @param string $property_short
	 * @return OLPBlackbox_Enterprise_Generic_Rule_PostAPI
	 */
	protected function getPostRule($property_short)
	{
		$rule = new OLPBlackbox_Enterprise_Generic_Rule_PostAPI(
			$this->getConfig(),
			$this->getConfig()->debug,
			$this->getECashVendorApi($property_short, $this->getConfig()->application_id),
			$this->getOLPFactory()->getReferencedModel('ApplicationValue'),
			new App_Campaign_Manager(
				$this->getConfig()->olp_db,
				$this->getConfig()->olp_db->db_info['db'],
				$this->getConfig()->applog
			),
			$this->postRuleShouldExpireApps(),
			$this->getDataXThrowsFailException()
		);
		$rule->setEventName('ECASH_API_POST');
		return $rule;
	}

	/**
	 * Controls whether a DataX failure in the API throws a FailException
	 * @return bool
	 */
	protected function getDataXThrowsFailException()
	{
		return FALSE;
	}

	/**
	 * Main method of this factory, returns a blackbox target building it with
	 * a model.
	 *
	 * @param Blackbox_Models_IReadableTarget $target_model The model of the target to use to build.
	 * @param OLPBlackbox_Factory_TargetStateData $state_data_factory Factory to
	 * produce state data for the target. 
	 * @return void
	 */
	public function getTarget(
		Blackbox_Models_IReadableTarget $target_model,
		OLPBlackbox_Factory_TargetStateData $state_data_factory
	)
	{
		$target = $this->getBlackboxTarget($target_model, $state_data_factory);

		$rule_collection_factory = $this->getRuleCollectionFactory($target_model->property_short);
		$rule_collection_factory->setCaching(TRUE);
		$rule_collection_factory->setRuleCollections($target_model, $target);

		if ($this->isBrokerMode() || $this->isEcashReactMode())
		{
			$target->setSellRule($this->getPostRule($target_model->property_short));
		}

		return $target;
	}
	/**
	 * Should the post rule expire apps 
	 *
	 * @return bool
	 */
	protected function postRuleShouldExpireApps()
	{
		return $this->isEcashReactMode() ||
			($this->isBrokerMode() && OLPBlackbox_Config::getInstance()->react_company);
	}

	/**
	 * Determines whether this factory is being run in broker mode.
	 * @return bool if mode is MODE_BROKER, TRUE. FALSE otherwise.
	 */
	protected function isBrokerMode()
	{
		return $this->getConfig()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER;
	}

	/**
	 * Determines whether this factory is being run in agree mode.
	 * @return bool if mode is MODE_AGREE, TRUE. FALSE otherwise.
	 */
	protected function isAgreeMode()
	{
		return $this->getConfig()->blackbox_mode == OLPBlackbox_Config::MODE_AGREE;
	}

	/**
	 * Determines whether this factory is being run in broker mode.
	 * @return bool if mode is MODE_BROKER, TRUE. FALSE otherwise.
	 */
	protected function isConfirmationMode()
	{
		$config = $this->getConfig();
		return ($config->blackbox_mode == OLPBlackbox_Config::MODE_CONFIRMATION
			|| $config->blackbox_mode == OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION);
	}

	/**
	 * Determines whether the factory is being run in ecash react mode.
	 * @return bool if MODE_ECASH_REACT, TRUE, otherwise FALSE.
	 */
	protected function isEcashReactMode()
	{
		return OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_ECASH_REACT;
	}

	/**
	 * Returns a concrete OLPBlackbox_Target based on a model.
	 * @param Blackbox_Models_IReadableTarget $target_model
	 * @param OLPBlackbox_Factory_TargetStateData $state_data_factory Used to
	 * seed the target with state data defined in the database.
	 * @return OLPBlackbox_Enterprise_Target
	 */
	protected function getBlackboxTarget(
		Blackbox_Models_IReadableTarget $target_model, 
		OLPBlackbox_Factory_TargetStateData $state_data_factory
	)
	{
		$target = new OLPBlackbox_Enterprise_Target(
			$target_model->property_short,
			$target_model->target_id,
			$state_data_factory->getTargetStateData($target_model),
			$this->getTargetTags($target_model)
		);
		$this->getListenerHandler()->registerChild($target_model->property_short, 'TARGET', $target);
		return $target;
	}
}

?>
