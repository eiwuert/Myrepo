<?php
/**
 * The OLPBlackbox Winner class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Winner extends Blackbox_Winner
{
	/**
	 * Returns the campaign for this winner.
	 * 
	 * This is really just an alias for the getTarget() function. It allows us to differentiate
	 * between getting a Target object and getting a Campaign object. Hopefully will avoid some
	 * confusion.
	 *
	 * @return OLPBlackbox_ITarget
	 */
	public function getCampaign()
	{
		return $this->getTarget();
	}

	/**
	 * For now, this is just an alias for getting the target's data.
	 *
	 * Eventually, though, getStateData might have to do something else.
	 * The law of demeter says that I we should really only be allowed
	 * to talk to items that have been passed to us and their immediate methods,
	 * so calling $winner->getTarget()->getStateData() by methods handling
	 * this object have too much coupling.
	 *
	 * @return Blackbox_IStateData
	 */
	public function getStateData()
	{
		return $this->getCampaign()->getTarget()->getStateData();
	}
}
?>
