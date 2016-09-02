<?php

	class ECash_CFE_ApplicationModelObserver
	{
		/**
		 * @var Delegate_1
		 */
		protected $delegate;

		/**
		 * @var IObservable
		 */
		protected $app;

		/**
		 * @var array
		 */
		protected $events = array();

		public function __construct()
		{
			$this->delegate = Delegate_1::fromMethod($this, 'onEvent');
		}

		/**
		 * Attach to an observable model
		 *
		 * @param IObservable_1 $model
		 */
		public function attach(IObservable_1 $model)
		{
			if ($model !== $this->app)
			{
				$this->detach();

				// some nice circular references
				$model->attachObserver($this->delegate);
				$this->app = $model;
			}
		}

		/**
		 * Detach from the current model
		 *
		 */
		public function detach()
		{
			if ($this->app)
			{
				$this->app->detachObserver($this->delegate);
			}
		}

		/**
		 * Fired when a change occurs on the model we're watching
		 *
		 * @param stdClass $event
		 */
		public function onEvent($event)
		{
			switch($event->type)
			{
				case DB_Models_ObservableWritableModel_1::EVENT_VALUES:
					$this->queueEventForColumn($event->column, $event);
					break;

				// we execute BEFORE the application has actually been saved
				// to group any updates we might make into a single update
				case DB_Models_ObservableWritableModel_1::EVENT_BEFORE_UPDATE:
					$this->executeEvents();
					break;

				// we need to execute AFTER the application has been inserted
				// because of dependancies upon the application_id
				case DB_Models_ObservableWritableModel_1::EVENT_INSERT:
					$this->executeEvents();
					$this->app->update();
					break;
			}
		}

		/**
		 * Determine the proper event to fire given a column that was changed
		 *
		 * @param string $column
		 * @return string
		 */
		protected function queueEventForColumn($column, $event)
		{
			switch ($column)
			{
				case 'application_status_id':
					// @todo these should be strings, not IDs
					$status = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
					$this->queueEvent(
						$column,
						'APPLICATION_STATUS',
						array(
							'application_status_old' => $status->toName($event->old),
						)
					);
					break;
			}
		}

		/**
		 * Queue a CFE_Engine event for later execution
		 * Index events by the column that triggered them so that
		 * we only fire an event for that column once...
		 *
		 * @param string $name
		 */
		protected function queueEvent($col, $name, array $args = array())
		{
			$this->events[$col] = array($name, $args);
		}

		/**
		 * Execute all queued events
		 *
		 */
		protected function executeEvents()
		{
			$engine = ECash::getEngine();

			foreach ($this->events as $e)
			{
				list($event, $args) = $e;
				$engine->executeEvent($event, $args,true);
			}

			$this->events = array();
		}
	}

?>
