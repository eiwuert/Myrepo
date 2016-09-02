<?php

	// Feel free to trash this completely, just the start of an idea.
	class Stats
	{

		private static $stats_hit = array();

		// map new stat names to old stat names
		protected static $map_new_to_old = array(
			'visitor' => 'visitors',
			'prequal' => 'base',
			'submit' => 'income',
			'agree' => 'accepted',
			'nms_prequal' => 'post',
			'popagree' => 'legal',
		);

		/**
		 *
		 * Accepts an array of stat names and returns the
		 * corresponding new and/or old stat names for them.
		 *
		 * @param $names mixed String or array of stat names
		 * @param $to string Which names to return, OLD, NEW, or BOTH
		 * @return array Array of new and old stat names
		 *
		 **/
		static public function Translate($names, $to = 'BOTH')
		{

			// get the names in the proper format
			$names = is_array($names) ? array_map('strtolower', $names) : array(strtolower($names));

			// always return what they gave us if we're translating to BOTH
			$result = ($to === 'BOTH') ? $names : array();

			// now, find the other half of the map, if it exists
			foreach ($names as $name)
			{

				if (isset(self::$map_new_to_old[$name]))
				{
					$result[] = ($to === 'NEW') ? $name : self::$map_new_to_old[$name];
				}
				elseif (($key = array_search($name, self::$map_new_to_old)) !== FALSE)
				{
					$result[] = ($to === 'OLD') ? $name : $key;
				}
				elseif ($to !== 'BOTH')
				{
					$result[] = $name;
				}

			}

			return $result;

		}

		/**
		 *		@desc Tries to hit web admin stats, statpro stats, and log an event
		 * 			Doesn't die on error just writes to the log'
		 *
		 *
		 *		@param $stats mixed String, array of stats to hit right now
		 * 		@param $session object Session object used to hit web admin stats
		 * 		@param $event object Event object used to hit events
		 * 		@param $log object Applog object used to write to our log
		 * 		@param $application_id int app-id to log to if available
		 * 		@param int $target
		 *
		 **/
		static public function Hit_Stats($stats, $session, $event, $log, $application_id = NULL, $target = NULL, $use_new_stat = FALSE)
		{
			// NOTE: stat map array is now a class variable (self::$map_new_to_old)

			// stats that can be capped (i.e., that
			// we should keep totals for)
			//
			// NEW stat names
			$map_cap_stats = array(
				'pre_prequal_pass',
				'nms_prequal',
				'agree',
			);

			$check_unique_stats = array(
				'popconfirm',
				'redirect',
				'popagree',
				'confirmed',
				'agree',
				'popty',
				'react_confirmed',
				'react_agree',
				'react_optout',
				// CLK confirms
				'ca_confirm',
				'd1_confirm',
				'pcl_confirm',
				'ucl_confirm',
				'ufc_confirm',
				// CLK bb_*_agree's
				'bb_ca_agree',
				'bb_d1_agree',
				'bb_pcl_agree',
				'bb_ucl_agree',
				'bb_ufc_agree',
				// Impact agree
				'bb_ic_agree',
				// Customer Motivation Stat
				'__context',
				// Direct Mail stat
				'dm_no_market',
				//New Loans
				'bb_ic_new_app',
			
				//Hit if we hit nms_prequal, but CLK did not get a look at the app.
				'clk_no_look'
			);

			// make sure we have an array
			if (!is_array($stats))
			{
				$stats = explode(',', $stats);
			}

			//To lower all stat names
			$stats = array_map('strtolower', $stats);

			// Should we really be calling this with $events not set?
			if(isset($event) && !empty($application_id))
			{
				// If it's empty we either haven't pulled anything or haven't hit
				// any stats yet.
				if(empty(self::$stats_hit))
				{
					// Grab the stats we've already hit from the event_log
					self::$stats_hit = $event->Get_Stat_Events($application_id);

					if(self::$stats_hit === false)
					{
						self::$stats_hit = array();
					}
					else
					{
						self::$stats_hit = array_map('strtolower', self::$stats_hit);
					}
				}
			}

			//echo "<pre>Start\n";

			foreach ($stats as $stat_column)
			{
				/*
					Since we use a different session for CS, it's possible for a user to clear
					their session, refresh the page and hit popconfirm again. So we need to
					check that they haven't already hit it. If they have, don't hit it again
					since the session won't have the unique stat for popconfirm. [BF]
				*/

				$stat = isset(self::$map_new_to_old[trim($stat_column)]) ? self::$map_new_to_old[trim($stat_column)] : $stat_column;

				/*
					If the stat is in the $check_unique_stats array and it's in the event_log,
					don't hit it again.
				*/
				if(!in_array($stat_column, $check_unique_stats) || !in_array("stat_{$stat}", self::$stats_hit))
				{

					/*
					 * Confusing:
					 * We are going to call the Hit_Stat function with the old stat column name.
					 * When the stat wrapper will then hit the new stats with the correct new
					 * stat name
					 */
					try
					{
						// hit both new and old stats (depending on $stat_model)
						//   $stat_model = NEW ; hit new stats
						//   $stat_model = OLD ; hit old stats (this shouldn't happen here)
						//   $stat_model = BOTH ; hit new and old stats
						$hit = $session->Hit_Stat($stat, TRUE, TRUE, 'NEW');

						// We don't want to replace stat 'vendor_post_timeout' with 'vendor_post_timeout_%bb_name%'
						// coz stat 'vendor_post_timeout' may have been used by StatPro/ReportPro. (Mantis #8353 [DY])
						if (($stat == 'vendor_post_timeout') && $target) {
							$session->Hit_Stat('vendor_post_timeout_' . $target, TRUE, TRUE, 'NEW');
						}

						$status = 'PASS';

					}
					catch( Exception $e )
					{
						$status = 'FAIL';
						$log->Write("Failed to write stat ". $stat_column . " for application id ".$application_id, LOG_INFO);
					}

					//echo "\nStatus: ". $status;

					// did we actually hit the
					// stat (it wasn't hit before)?
					if ($hit)
					{

						// try to only store records for sites
						// that really have limits
						$has_limit = (isset($_SESSION['config']->limits->stat_cap) && (!empty($_SESSION['config']->limits->stat_cap->stat_name)));

						// can we cap on this stat?
						if ($has_limit && in_array($stat_column, $map_cap_stats))
						{

							// for now, we have to piggyback off of the security object:
							// if we open a new connection here, it will use the same
							// resource ID as OLP's connection, and will end up disconnecting
							// OLP from the database (and there's no other connection here)
							$server = Server::Get_Server($_SESSION['config']->mode, 'BLACKBOX');
							$sql = &$_SESSION['security']->sql;

							// increment our counter for this stat
							$limits = new Stat_Limits($sql, $server['db']);
							$limits->Increment($stat_column, $_SESSION['config']->site_id, $_SESSION['config']->promo_id, $_SESSION['config']->vendor_id);

						}

						if (!empty($application_id))
						{
							// Now lets hit an event for this guy so we know what time this stat happened
							// ...as long as this stat is not in our non event stat
							$event->Log_Event('STAT_'.strtoupper($stat), $status, $target, $application_id);
						}

						//********************************************* 
						// GForge #9534 [AuMa]
						// We changed the value here so that the stats
						// will appear on the current page
						//********************************************* 
						$_SESSION['current_page_stat']->$stat = TRUE;

					}

				}

			}

			return;

		}

	}
?>
