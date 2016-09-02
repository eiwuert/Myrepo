<?php

	require_once(LIB_DIR . 'NightlyEvents/Handler.class.php');
	require_once(LIB_DIR . 'Nightly_Event.class.php');
	require_once('NightlyEvents/DelinquentFullPull.php');
	require_once('NightlyEvents/MoveOldCollectionsToChargeoff.php');
	require_once('NightlyEvents/ResolveARReport.php');
	require_once(LIB_DIR . 'NightlyEvents/TeletrackUpdates.php');

	class IMPACT_ECash_NightlyEvents_Handler extends ECash_NightlyEvents_Handler
	{
		
		/**
		 * Use this customer-specific function to add new tasks to the
		 * CronScheduler object which is provided by Nightly.
		 *
		 * @param CronScheduler $manager
		 */
		public function registerEvents(CronScheduler $manager)
		{
			// The following is optional and will register any
			// common eCash 3.0 nightly events
			parent::registerEvents($manager);
			
			// Add the event class to the Cron Scheduler
			$manager->Add_Task(new ECash_NightlyEvent_MoveOldCollectionsToChargeoff());
			$manager->Add_Task(new ECash_NightlyEvent_ResolveARReport());
			$manager->Add_Task(new ECash_NightlyEvent_DelinquentFullPull());
			 // GForge 19861 - We're now reporting Paid, Cancel, and Chargeoffs
			 // back to TeleTrack
			 $manager->Add_Task(new ECash_NightlyEvent_TeletrackUpdates());
			
		}
	}
	
	
	
?>
