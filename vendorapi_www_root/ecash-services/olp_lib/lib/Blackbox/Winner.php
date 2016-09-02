<?php
/**
 * Blackbox_Winner class file.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

/**
 * Blackbox winner example class.
 * 
 * This is just a example implementation of the Blackbox_IWinner interface. It stores the winning
 * target and allows access to information within that target through this class.
 * 
 * When creating your own winner class, it is recommended that you implement the interface, not
 * extend this class.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Winner implements Blackbox_IWinner
{
	/**
	 * The Blackbox_Target instance of the winner.
	 *
	 * @var Blackbox_Target
	 */
	protected $target;
	
	/**
	 * Blackbox_Winner constructor.
	 * 
	 * @param Blackbox_ITarget $target the target object of the winner
	 */
	public function __construct(Blackbox_ITarget $target)
	{
		$this->target = $target;
	}
	
	/**
	 * Returns the winning target.
	 *
	 * @return Blackbox_ITarget
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * Returns the state data of this winner object.
	 *
	 * This returns the target's state data since it reduces coupling.
	 *
	 * @return Blackbox_IStateData internal state data
	 */
	public function getStateData()
	{
		return $this->getTarget()->getStateData();
	}
}
?>
