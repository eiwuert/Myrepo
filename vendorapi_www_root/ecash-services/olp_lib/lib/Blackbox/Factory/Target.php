<?php
/**
 * Blackbox_Factory_Target class file.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

/**
 * Blackbox target factory.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Factory_Target
{
	/**
	 * Returns a Blackbox_TargetCollection object.
	 * 
	 * This function would be overloaded in a project specific version to load up the collection
	 * target.
	 * 
	 * @param array $excluded_targets   A list of target name shorts that will be excluded
	 * @param array $restricted_targets A list of target name shorts that Blackbox will be restricted to
	 *
	 * @return Blackbox_TargetCollection
	 */
	public static function getTargetCollection($excluded_targets = array(), $restricted_targets = array())
	{
		/*
			Here we would pull our targets and rules out of the database and restrict or exclude
			based on $excluded_targets and $restricted_targets.
		*/
		return new Blackbox_TargetCollection();
	}
}
?>
