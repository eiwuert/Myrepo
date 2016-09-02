<?php

	require_once(LIB_DIR . 'NightlyEvents/Handler.class.php');
	require_once(LIB_DIR . 'Nightly_Event.class.php');
	require_once('NightlyEvents/MoveHotfilesToPendingExpired.php');
	require_once('NightlyEvents/ExpireAdditionalVerification.php');
	require_once('NightlyEvents/SendEmailReminders.php');
	require_once('NightlyEvents/MoveToReminderQueues.php');
	require_once('NightlyEvents/WithdrawSoftFax.php');
	require_once('NightlyEvents/MoveArrangementsToMyQueue.php');
	require_once('NightlyEvents/RegenerateSchedules.php');
	require_once('NightlyEvents/ResolveARReport.php');
	require_once('NightlyEvents/CheckForActiveDefaults.php');
	require_once('NightlyEvents/CSODefaultedLoans.php');
	require_once('NightlyEvents/CSOAssessLateFee.php');
	require_once('NightlyEvents/QueueLostArrangements.php');
	require_once(ECASH_COMMON_DIR . "ecash_api/ecash_api.2.php");

	class MCC_ECash_NightlyEvents_Handler extends ECash_NightlyEvents_Handler
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
			$manager->Add_Task(new ECash_NightlyEvent_ResolveARReport());
			$manager->Add_Task(new ECash_NightlyEvent_MoveHotfilesToPendingExpired());
			$manager->Add_Task(new ECash_NightlyEvent_ExpireAdditionalVerification());
			$manager->Add_Task(new ECash_NightlyEvent_SendEmailReminders());
		//	$manager->Add_Task(new ECash_NightlyEvent_MoveToReminderQueues());
			$manager->Add_Task(new ECash_NightlyEvent_WithdrawSoftFax());
			$manager->Add_Task(new ECash_NightlyEvent_MoveArrangementsToMyQueue());
			$manager->Add_Task(new ECash_NightlyEvent_RegenerateSchedules());
			$manager->Add_Task(new ECash_NightlyEvent_CheckForActiveDefaults());
			//We're no longer doing this because MCC doesn't believe in grace periods
			//$manager->Add_Task(new ECash_NightlyEvent_CSODefaultedLoans());
			$manager->Add_Task(new ECash_NightlyEvent_CSOAssessLateFee());
			$manager->Add_Task(new ECash_NightlyEvent_QueueLostArrangements());
		}
	}
	
?>
