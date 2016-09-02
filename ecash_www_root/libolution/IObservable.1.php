<?php

	/**
	 * @package Core
	 */

	/**
	 * Generic interface for objects that are observable
	 * @see Delegate_1
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 * @example examples/observer.php
	 */
	interface IObservable_1
	{
		/**
		 * Attach an observer that wishes to begin receiving notifications
		 * @param Delegate_1 $d
		 * @return void
		 */
		public function attachObserver(Delegate_1 $d);

		/**
		 * Detach an observer that no longer wishes to receive notifications
		 * @param Delegate_1 $d
		 * @return void
		 */
		public function detachObserver(Delegate_1 $d);
	}

?>
