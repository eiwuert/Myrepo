<?php

/** Handle pixels. This depends on SessionHandler's Check_Pixel() to handle
 * and process the stat.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_OLP_Observe_Pixel extends Stats_OLP_Observe_Observer
{
	/**
	 * @var SessionHandler
	 */
	protected $session;
	
	/** Record the session we'll hit pixels through.
	 *
	 * @param SessionHandler
	 */
	public function __construct(SessionHandler $session)
	{
		$this->session = $session;
	}
	
	/** A stat was hit through the Stats_OLP_Client system.
	 *
	 * @param Stats_OLP_Client $stats
	 * @param string $event_type_key
	 * @param int $date_occurred
	 * @param int $event_amount
	 * @param string $track_key
	 * @param string $space_key
	 * @return void
	 */
	public function statHit(Stats_OLP_Client $stats, $event_type_key, $date_occurred = NULL, $event_amount = NULL, $track_key = NULL, $space_key = NULL)
	{
		$this->session->Check_Pixel($event_type_key);
	}
}

?>
