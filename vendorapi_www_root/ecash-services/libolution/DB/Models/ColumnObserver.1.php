<?php

	/**
	 * @package DB.Models
	 */

	/**
	 * An observer that binds the value of a column on one model to a column on another
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 * @example examples/observable_model.php
	 */
	class DB_Models_ColumnObserver_1
	{
		/**
		 * @var DB_Models_ObservableWritableModel_1
		 */
		protected $subject;

		/**
		 * @var string
		 */
		protected $subject_column;

		/**
		 * @var DB_Models_WritableModel_1
		 */
		protected $target = array();

		/**
		 * @var Delegate_1
		 */
		protected $delegate;

		/**
		 * If $target_field is null, uses $subject_field
		 *
		 * @param DB_Models_ObservableWritableModel $subject
		 * @param string $subject_field
		 */
		public function __construct(DB_Models_ObservableWritableModel_1 $subject, $subject_column)
		{
			$this->subject = $subject;
			$this->subject_column = $subject_column;

			// store this?
			$this->delegate = Delegate_1::fromMethod($this, 'onNotify');
			$subject->attachObserver($this->delegate);
		}

		/**
		 * Cleans up, like a good little destructor
		 */
		public function __destruct()
		{
			$this->subject->detachObserver($this->delegate);
		}

		/**
		 * Add a target class
		 *
		 * @param DB_Models_WritableModel $target
		 * @param string $target_field
		 * @return void
		 */
		public function addTarget(DB_Models_WritableModel_1 $target, $target_column = NULL)
		{
			if ($target_column === NULL) $target_column = $this->subject_column;
			$this->target[] = array($target, $target_column);
		}

		/**
		 * Receives a notification from the subject
		 *
		 * @param object $event
		 * @internal
		 * @return void
		 */
		public function onNotify($event)
		{
			// ensure that it's an event we care about
			if (($event->type & DB_Models_ObservableWritableModel_1::EVENT_VALUES)
				&& $event->column == $this->subject_column)
			{
				foreach ($this->target as $target)
				{
					list($model, $col) = $target;
					$model->{$col} = $event->new;
				}
			}
		}
	}

?>
