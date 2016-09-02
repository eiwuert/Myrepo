<?php 

/**
 * Contains information about a trigger for a loan action
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_Trigger
{
	/**
	 * The name of the loan action that
	 * triggers this trigger
	 *
	 * @var string
	 */
	protected $loan_action;
	
	/**
	 * The stat that gets hit from this 
	 * trigger
	 *
	 * @var string
	 */
	protected $email_stat;
	
	/**
	 * Has this trigger been hit yet?
	 *
	 * @var boolean
	 */
	protected $is_hit;
	
	/**
	 * Set us up?
	 *
	 * @param string $action
	 * @param string $stat
	 */
	public function __construct($action, $stat = FALSE)	
	{
		$this->loan_action = $action;
		$this->email_stat = $stat;
		$this->is_hit = FALSE;
			
	}
	
	/**
	 * Mark this trigger as hit
	 * 
	 * @return void
	 */
	public function hit()
	{
		$this->is_hit = TRUE;
	}
	
	/**
	 * Mark this trigger as NOT hit.
	 *
	 * @return void
	 */
	public function unhit()
	{
		$this->is_hit = FALSE;
	}
	
	/**
	 * Returns if the trigger has been 
	 * hit or not.
	 *
	 * @return boolean
	 */
	public function isHit()
	{
		return $this->is_hit;
	}
	
	/**
	 * Set the loan action name that 
	 * triggers this
	 *
	 * @param string $action
	 * @return string
	 */
	public function setAction($action)
	{
		if (!is_string($action))
		{
			throw new InvalidArgumentException('Invalid action.');
		}
		$this->loan_action = $action;
		return $this->loan_action;
	}
	
	/**
	 * Return the action name that triggers
	 * this particular trigger?
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->loan_action;
	}
	
	/**
	 * Setup the stat to hit during the 
	 * agree process for this trigger
	 *
	 * @param string $stat
	 */
	public function setStat($stat)
	{
		if (!is_string($stat))
		{
			throw new InvalidArgumentException('Invalid stat name.');
		}
		$this->email_stat = $stat;
	}
	
	/**
	 * Return the stat name for this particular object?
	 *
	 * @return string
	 */
	public function getStat()
	{
		return $this->email_stat;
	}
}