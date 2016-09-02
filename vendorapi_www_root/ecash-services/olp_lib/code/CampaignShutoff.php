<?php
/**
 * This cron will take care of shutting off campaigns with bad accept rates and too many timeouts or errors 
 * 
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class CampaignShutoff
{	
	/**
	 * OLP database connection
	 *
	 * @var DB_Connection_1
	 */
	protected $olp_db;
	
	/**
	 * Blackbox database connection
	 *
	 * @var DB_Connection_1
	 */
	protected $blackbox_db;
	
	/**
	 * Stores configuration data needed for running this cron
	 *
	 * @var stdClass
	 */
	protected $config;
	
	/**
	 * Stores information needed to disable and send alerts 
	 * for the non-working campaigns
	 *
	 * @var array
	 */
	protected $shutoff = array();
	
	/**
	 * constructor
	 * 
	 * @return void
	 */
	public function __construct($olp_db, $blackbox_db)
	{
		$this->olp_db = $olp_db;
		$this->blackbox_db = $blackbox_db;
		
		$this->loadConfig();
	}
	
	/**
	 * returns the current unix timestamp to use, mainly for testing
	 *
	 * @return int
	 */
	protected function getCurrentTimestamp()
	{
		return time();
	}
	
	/**
	 * Loads configuration values from the campaign_shutoff_config table
	 * and stores them in the class variable $this->config
	 * 
	 * @return void
	 */
	protected function loadConfig()
	{
		$this->config = new stdClass();
		
		// set some defaults first
		$this->config->min_timeouts = 20;
		$this->config->recent_timeouts = 30;
		$this->config->accept_rate = 0.05;
		$this->config->ignored_campaigns = array();
		
		$stmt = $this->olp_db->query('SELECT name, value FROM campaign_shutoff_config');		
		while ($row = $stmt->fetchObject())
		{
			$this->config->{$row->name} = $row->value;
		}
	}
	
	/**
	 * Returns a list of active campaigns from blackbox and will ignore 
	 * any campaigns specified in the "ignored_campaigns" config key
	 * 
	 * @return array
	 */
	protected function getCampaigns()
	{
		$ignored_campaigns = (isset($this->config->ignored_campaigns)) ? explode(',', $this->config->ignored_campaigns) : array();
		
		$query = "SELECT property_short FROM target t 
			JOIN blackbox_type bt ON t.blackbox_type_id = bt.blackbox_type_id
			WHERE bt.name = 'CAMPAIGN' AND t.active = 1";
		
		return array_diff(DB_Util_1::querySingleColumn($this->blackbox_db, $query), $ignored_campaigns);
	}
	
	/**
	 * Counts posts for a given campaign since the last time that campaign 
	 * was marked as ok or in the last 24 hours with a limit 	 
	 * of looking at the the last 1000 posts
	 * 
	 * @param string $campaign
	 * @return stdClass
	 */
	protected function getPostCounts($campaign)
	{
		$last_shutoff = $this->getLastShutoff($campaign);
		if (empty($last_shutoff))
		{
			$last_shutoff = new stdClass();
			$last_shutoff->activated_at = date('Y-m-d H:i:s', strtotime('-1 day', $this->getCurrentTimestamp()));
			$last_shutoff->times = 0;
		}
		
		$results = new stdClass();
		$results->property_short = $campaign;
		$results->total = 0;
		$results->shutoff_times = $last_shutoff->times;
		
		// only bother looking up posts if this campaign is not currently shutoff
		if (!empty($last_shutoff->activated_at) && strtotime($last_shutoff->activated_at) < $this->getCurrentTimestamp())
		{
			$query = "SELECT *, count(vendor_decision) AS count FROM (
				SELECT winner, success, LOWER(vendor_decision) AS vendor_decision FROM blackbox_post 
				WHERE winner = ? AND date_created > ? AND date_created < ?
				AND success IN ('TRUE', 'FALSE') AND vendor_decision IS NOT NULL AND vendor_decision != '' AND type = 'POST'
				ORDER BY date_created DESC
				LIMIT 1000
			) AS t0 GROUP BY vendor_decision";
			$stmt = $this->olp_db->prepare($query);
			$stmt->execute(array($campaign, $last_shutoff->activated_at, date('Y-m-d H:i:s', $this->getCurrentTimestamp())));
			
			while ($row = $stmt->fetchObject())
			{
				if (!isset($results->{$row->vendor_decision}))
				{
					$results->{$row->vendor_decision} = $row->count;
				}
				else
				{
					$results->{$row->vendor_decision} += $row->count;
				}
				$results->total += $row->count;
			}
		}
		
		return $results;
	}
	
	/**
	 * Counts posts that were neither accepted or rejected
	 * These include timeouts or errors or blank responses
	 * 
	 * @param string $campaign
	 * @return int
	 */
	protected function getRecentErrorCount($campaign)
	{
		$stmt = $this->olp_db->prepare("SELECT count(*) AS count FROM blackbox_post 
			WHERE winner = ? AND date_created > ? AND date_created < ?
			AND success IN ('TRUE', 'FALSE') AND vendor_decision IS NOT NULL AND vendor_decision != ''
			AND type = 'POST' AND vendor_decision NOT IN ('ACCEPTED', 'REJECTED')");
		$stmt->execute(array($campaign, date('Y-m-d H:i:s', $this->getCurrentTimestamp() - (60 * 30)), date('Y-m-d H:i:s', $this->getCurrentTimestamp())));
				
		return $stmt->fetchObject()->count;
	}
	
	/**
	 * Returns the last data about when and why a campaign was shutoff.
	 *
	 * @param string $campaign
	 * @return stdClass
	 */
	protected function getLastShutoff($campaign)
	{
		$stmt = $this->olp_db->prepare('SELECT shutoff_at, activated_at, times
			FROM campaign_shutoff
			WHERE property_short = ?
			ORDER BY shutoff_at DESC LIMIT 1');
		$stmt->execute(array($campaign));		
		
		return $stmt->fetchObject();
	}
	
	/**
	 * Returns an array of emails that this alert should be sent to.
	 * Fetches emails from the global list as well as the campaign specific list
	 *
	 * @return void
	 */
	public function getAlertRecipients($campaign_name)
	{
		$global_recipients = array_map('trim', explode(',', $this->config->global_alert_list));
		
		$stmt = $this->olp_db->prepare('SELECT value FROM campaign_shutoff_emails WHERE property_short = ?');
		$stmt->execute(array($campaign_name));
		$campaign_recipients = array_map('trim', explode(',', $stmt->fetchColumn()));
		
		$recipients = array_unique(array_diff(array_merge($global_recipients, $campaign_recipients), array('')));
		
		return $recipients;
	}
	
	/**
	 * Gets the next time a campaign will be reactivated. Returns null
	 * if a campaign must be manually reactivated
	 *
	 * @return int|NULL
	 */
	protected function getReactivationTime($shutoff_times)
	{
		// if this is the first offence for this campaign
		if ($shutoff_times == 0)
		{
			$activated_at = date('Y-m-d H:i:s', $this->getCurrentTimestamp() + (3600 * 3));
		}
		// 2nd offence gets disabled longer
		elseif ($shutoff_times == 1)
		{
			$activated_at = date('Y-m-d H:i:s', $this->getCurrentTimestamp() + (3600 * 10));
		}
		// 3 times and the campaign gets disabled until someone turns it back on
		else
		{
			$activated_at = NULL;
		}
		
		return $activated_at;
	}
	
	/**
	 * Insert a record into the campaign_shutoff table to disable
	 * the bad campaigns
	 *
	 * @return void
	 */
	public function disableCampaigns()
	{	
		$stmt = $this->olp_db->prepare('INSERT INTO campaign_shutoff (property_short, shutoff_at, reason, activated_at, times) VALUES (?, ?, ?, ?, ?)');
		foreach ($this->shutoff as $property_short => $campaign_stats)
		{
			$shutoff_at = date('Y-m-d H:i:s', $this->getCurrentTimestamp());
			$activated_at = $this->getReactivationTime($campaign_stats->shutoff_times);
			
			$stmt->execute(array($property_short, $shutoff_at,$campaign_stats->reason, $activated_at, $campaign_stats->shutoff_times + 1));
		}
	}
	
	/**
	 * checks a set of stats for a campaign against the configured thresholds
	 * for accept rate and timeouts.  if the campaign is in violation of the thresholds,
	 * it will be added to the shutoff array
	 * 
	 * @return void
	 */
	protected function checkAcceptRateAndTimeouts($campaign_stats)
	{
		$campaign_accept_rate = ($campaign_stats->total > 0) ? $campaign_stats->accepted / $campaign_stats->total : FALSE;
		$campaign_errors = $campaign_stats->total - $campaign_stats->accepted - $campaign_stats->rejected;
																	
		if ($campaign_stats->total > 0
			&& !isset($campaign_stats->reason)
			&& $campaign_accept_rate < $this->config->accept_rate 
			&& $campaign_errors >= $this->config->min_timeouts)
		{
			$campaign_stats->reason = sprintf('Accept Rate = %.1f%% AND Timeouts/Errors/Blanks = %d', $campaign_accept_rate * 100, $campaign_errors);
			$this->shutoff[$campaign_stats->property_short] = $campaign_stats;
		}
	}
	
	/**
	 * Counts posts for a campaign that were neither accepted or rejected
	 * over a configured period of time. these are added to the shutoff array
	 * if they don't meet the threshold
	 * 
	 * @return void
	 */
	protected function checkRecentTimeouts($campaign_stats)
	{
		$recent_timeouts = $this->getRecentErrorCount($campaign_stats->property_short);
		
		if ($campaign_stats->total > 0 
			&& !isset($campaign_stats->reason)
			&& $recent_timeouts > $this->config->recent_timeouts)
		{
			$campaign_stats->reason = sprintf('Recent Timeouts/Errors/Blanks = %d', $recent_timeouts);
			$this->shutoff[$counts->property_short] = $campaign_stats;
		}
	}
	
	/**
	 * Main function. Takes care of figuring out if campaigns don't meet
	 * the valid requirements and marks them to be shutoff and then sends the alerts
	 * 
	 * @return array
	 */
	public function runChecks()
	{		
		foreach ($this->getCampaigns() as $campaign)
		{
			$campaign_stats = $this->getPostCounts($campaign);
			
			$this->checkAcceptRateAndTimeouts($campaign_stats);
			$this->checkRecentTimeouts($campaign_stats);
		}
		
		return $this->shutoff;
	}
}
?>