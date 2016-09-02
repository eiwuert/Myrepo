<?php

	/**
	 * @package DB.Models
	 */

	require_once 'libolution/IObservable.1.php';
	require_once 'libolution/DB/Models/WritableModel.1.php';

	/**
	 * A writable model that is observable
	 *
	 * NOTE: Type-hint for IObservable_1
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 * @example examples/observable_model.php
	 */
	abstract class DB_Models_ObservableWritableModel_1 extends DB_Models_WritableModel_1 implements IObservable_1
	{
		const EVENT_UPDATE			= 0x00000001;
		const EVENT_INSERT			= 0x00000002;
		const EVENT_DELETE			= 0x00000004;
		const EVENT_VALUES			= 0x00000008;
		const EVENT_BEFORE_UPDATE	= 0x00000010;
		const EVENT_BEFORE_INSERT	= 0x00000020;
		const EVENT_BEFORE_DELETE	= 0x00000040;

		const EVENT_AFTER_UPDATE	= 0x00000100;
		const EVENT_AFTER_INSERT	= 0x00000200;
		const EVENT_AFTER_DELETE	= 0x00000400;

		const EVENT_BEFORE_ALL		= 0x00000070;
		const EVENT_AFTER_ALL		= 0x00000700;

		const EVENT_ALL				= 0xffffffff;

		/**
		 * @var array
		 */
		protected $observers = array();

		/**
		 * Attach an observer delegate
		 * You can filter which events you receiving by setting the $mask parameter
		 * to an appropriate self::EVENT_* const
		 *
		 * @param Delegate_1 $d
		 * @param int $mask
		 * @return void
		 */
		public function attachObserver(Delegate_1 $d, $mask = self::EVENT_ALL)
		{
			$hash = spl_object_hash($d);
			$this->observers[$hash] = array($mask, $d);
		}

		/**
		 * Detach a delegate from receiving notifications
		 *
		 * @param Delegate_1 $d
		 * @return void
		 */
		public function detachObserver(Delegate_1 $d)
		{
			$hash = spl_object_hash($d);
			unset($this->observers[$hash]);
		}

		/**
		 * Adds observer notification to the base insert functionality
		 * @return void
		 */
		public function insert()
		{
			$thrown = false;
			try
			{
				$event = new stdClass();
				$event->type = self::EVENT_BEFORE_INSERT;
				$this->notifyObservers($event);

				$result = parent::insert();

				$event = new stdClass();
				$event->type = self::EVENT_INSERT;
				$this->notifyObservers($event);
			}
			catch(Exception $e)
			{
				$thrown = true;
			}

			$event = new stdClass();
			$event->type = self::EVENT_AFTER_INSERT;
			$this->notifyObservers($event);

			if($thrown)
			{
				throw $e;
			}

			return $result;
		}

		/**
		 * Adds observer notification to the base update functionality
		 * @return void
		 */
		public function update()
		{
			$thrown = false;
			try
			{
				$event = new stdClass();
				$event->type = self::EVENT_BEFORE_UPDATE;
				$this->notifyObservers($event);

				$result = parent::update();

				$event = new stdClass();
				$event->type = self::EVENT_UPDATE;
				$this->notifyObservers($event);
			}
			catch(Exception $e)
			{
				$thrown = true;
			}

			$event = new stdClass();
			$event->type = self::EVENT_AFTER_UPDATE;
			$this->notifyObservers($event);

			if($thrown)
			{
				throw $e;
			}

			return $result;
		}

		/**
		 * Adds observer notification to the base update functionality
		 * @return void
		 */
		public function delete()
		{
			$thrown = false;
			try
			{
				$event = new stdClass();
				$event->type = self::EVENT_BEFORE_DELETE;
				$this->notifyObservers($event);

				$result = parent::delete();

				$event = new stdClass();
				$event->type = self::EVENT_DELETE;
				$this->notifyObservers($event);
			}
			catch(Exception $e)
			{
				$thrown = true;
			}

			$event = new stdClass();
			$event->type = self::EVENT_AFTER_DELETE;
			$this->notifyObservers($event);

			if($thrown)
			{
				throw $e;
			}

			return $result;
		}

		/**
		 * Adds observer notification to the base __set functionality
		 * @return void
		 */
		public function __set($name, $value)
		{
			$old = $this->{$name};

			parent::__set($name, $value);

			// update our observers to the change
			$event = new stdClass();
			$event->type = self::EVENT_VALUES;
			$event->column = $name;
			$event->old = $old;
			$event->new = $value;

			$this->notifyObservers($event);
		}

		/**
		 * Notifies all observers of an event that occured
		 *
		 * @param object $event
		 * @return void
		 */
		protected function notifyObservers($event)
		{
			// $observer is a two-element array:
			// array(0 => $mask, 1 => $delegate)
			foreach ($this->observers as $observer)
			{
				if ($observer[0] & $event->type)
				{
					$observer[1]->invoke($event);
				}
			}
		}
	}

?>