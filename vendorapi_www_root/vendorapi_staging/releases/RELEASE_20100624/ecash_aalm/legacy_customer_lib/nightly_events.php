<?php

	require_once(LIB_DIR . 'NightlyEvents/Handler.class.php');
	require_once(LIB_DIR . 'Nightly_Event.class.php');
	require_once('NightlyEvents/MoveHotfilesToPendingExpired.php');
	require_once('NightlyEvents/ExpireAdditionalVerification.php');
	require_once('NightlyEvents/SendEmailReminders.php');
	require_once('NightlyEvents/WithdrawSoftFax.php');
	require_once('NightlyEvents/MoveArrangementsToMyQueue.php');
	require_once('NightlyEvents/ResolveARReport.php');
	require_once('NightlyEvents/MoveToSecondTier.php');
	require_once('NightlyEvents/CollectionsMailing.php');
	require_once('NightlyEvents/MoveDelinquentToCollectionsRework.php');
	require_once('NightlyEvents/MoveNewToGeneral.php');
	require_once('NightlyEvents/MoveGeneralToRework.php');
	require_once('NightlyEvents/MoveReworkToSecondTier.php');
	require_once('NightlyEvents/ResolvePortfolioSnapshotReport.php');

	
	require_once(LIB_DIR . 'NightlyEvents/TeletrackUpdates.php');

	class AALM_ECash_NightlyEvents_Handler extends ECash_NightlyEvents_Handler
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
			//$manager->Add_Task(new ECash_NightlyEvent_ResolveARReport());
			$manager->Add_Task(new ECash_NightlyEvent_ExpireAdditionalVerification());
			$manager->Add_Task(new ECash_NightlyEvent_SendEmailReminders());
			$manager->Add_Task(new ECash_NightlyEvent_MoveArrangementsToMyQueue());
			
			// GForge 18259 - We're now reporting Paid, Cancel, and Chargeoffs
			// back to TeleTrack
			$manager->Add_Task(new ECash_NightlyEvent_TeletrackUpdates());

			// Per GF # 9787 Joe Raffo does not want this to run yet.
			// MoveToSecondTier will need to be fixed to work with the application service before turninig it on.
			// $manager->Add_Task(new ECash_NightlyEvent_MoveToSecondTier());

			// New [#13633]
			$manager->Add_Task(new ECash_NightlyEvent_CollectionsMailing());
			$manager->Add_Task(new ECash_NightlyEvent_MoveDelinquentToCollectionsRework());
			$manager->Add_Task(new ECash_NightlyEvent_MoveNewToGeneral());
			$manager->Add_Task(new ECash_NightlyEvent_MoveGeneralToRework());
			//removed as per [#36566] / re-added per [#46292]
			$manager->Add_Task(new ECash_NightlyEvent_MoveReworkToSecondTier());
			//[#44294]
			$manager->Add_Task(new ECash_NightlyEvent_ResolvePortfolioSnapshotReport());
		}
	}
	
?>
