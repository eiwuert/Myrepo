<?php
	/**
	 * @ignore
	 */

	require 'libolution/AutoLoad.1.php';

	class MySubject implements IObservable_1
	{
		protected $observers = array();

		protected $name;

		public function __set($name, $value)
		{
			// make the modification
			$old = $this->{$name};
			$this->{$name} = $value;

			$this->notifyObservers("CHANGED '{$name}' FROM '{$old}' TO '{$value}'");
		}

		public function attachObserver(Delegate_1 $d)
		{
			// get a unique hash for this delegate
			$hash = spl_object_hash($d);

			// add to our list of observers
			$this->observers[$hash] = $d;
		}

		public function detachObserver(Delegate_1 $d)
		{
			// detach this observer
			$hash = spl_object_hash($d);
			unset($this->observers[$hash]);
		}

		protected function notifyObservers($event)
		{
			// the actual parameter list is left up to your
			// discretion: here, I'll just send the event
			foreach ($this->observers as $d)
			{
				$d->invoke($event);
			}
		}
	}

	class MyObserver
	{
		public function observe(MySubject $object)
		{
			// we pass the object we're observing as a 'static' parameter
			// to the delegate, in case we're observing multiple objects
			$d = Delegate_1::fromMethod($this, 'onUpdate');
			$object->attachObserver($d);
		}

		public function onUpdate($event)
		{
			echo "The observer got:\n\t{$event}\n";
		}
	}

	// create a subject
	$subject = new MySubject();

	// tell our observer to start watching
	$observer = new MyObserver();
	$observer->observe($subject);

	// see what happens!
	$subject->name = 'This is just a test';

?>
