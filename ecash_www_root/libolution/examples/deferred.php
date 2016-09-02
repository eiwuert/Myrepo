<?php

	require 'libolution/AutoLoad.1.php';

	class AsynchClass
	{
		/**
		 * @var Deferred_1
		 */
		protected $deferred;

		/**
		 * @return Deferred_1
		 */
		public function startLongAction()
		{
			// pretend to start something here...

			// give them a deferred object to do stuff with
			return ($this->deferred = new Deferred_1());
		}

		public function finishLongAction()
		{
			// SUCCESS!
			$result = 0;

			// let 'em know
			$this->deferred->complete($result);
		}
	}

	// this will get called when the action is done
	function done()
	{
		echo "Long action completed!\n";
	}

	$long = new AsynchClass();

	echo "Beginning long action...\n";

	// calling startLongAction() returns an instance of Deferred_1,
	// to which we can add delegates that will get called upon completion
	$waiting = $long->startLongAction();
	$waiting->addOnComplete(Delegate_1::fromFunction('done'));

	// in a real situation, the action would get completed by
	// (for instance) a file/network event; we'll just emulate that
	$long->finishLongAction();

?>