<?php

/**
 * Interfacing that denotes that IRules implementing this CAN BE REUSED in the factory.
 * 
 * If this interface is set on an IRule, the factory will assume it is OK for 
 * it to keep a reference to one version of the class in question and just pass
 * a reference to new RuleCollections/Targets.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
interface OLPBlackbox_Factory_Legacy_IReusableRule
{
	// pass
}

?>
