<?php

	/**
	 * A base class for all libolution exceptions that can be observed
	 *
	 * This complements the functionality of set_exception_handler, as the
	 * exception handler only gets called if the exception is never caught, thus
	 * you may miss important exceptions because your code is handling them
	 * gracefully.
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Exception_1 extends Exception
	{
		/**
		 * Exception listeners
		 * @var array
		 */
		protected static $listen = array();

		/**
		 * Registers a listener that will get called whenever an exception is constructed
		 * Note that your delegate will be called on CONSTRUCTION, not when the
		 * exception is thrown -- which may or may not occur at the same time. It's
		 * entirely possible to construct an exception and NEVER throw it...
		 *
		 * @param Delegate_1 $d
		 * @return void
		 */
		public static function attachListener(Delegate_1 $d)
		{
			self::$listen[] = $d;
		}

		/**
		 * Notifies all listeners of an exception
		 * You can also use this method to send notifications for exceptions
		 * that do not extend this class, and, thus, do not send notifications
		 * automatically.
		 *
		 * @param Exception $e
		 * @return void
		 */
		public static function notifyListeners(Exception $e)
		{
			foreach (self::$listen as $d)
			{
				$d->invoke($e);
			}
		}

		/**
		 * @param string $message
		 * @param int $code
		 */
		public function __construct($message = NULL, $code = 0)
		{
			parent::__construct($message, $code);

			self::notifyListeners($this);
		}
	}

?>
