<?php

	/**
	 * The CFE engine operates in two phases: first, it evaluates all the
	 * conditions in all rules for the current event. If all the conditions
	 * for a particular rule are met, the rule's actions are recorded in the
	 * agenda. In the second phase, all of the actions in the agenda are
	 * executed, highest salience first.
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Ecash_CFE_Engine
	{
		protected static $instance;

		/**
		 * @param ECash_CFE_IContext $c
		 * @return return ECash_CFE_Engine
		 */
		public static function getInstance(ECash_CFE_IContext $c)
		{
			if (!self::$instance)
			{
				self::$instance = new self();
			}

			self::$instance->setContext($c);
			return self::$instance;
		}

		/**
		 * @var array
		 */
		protected $ruleset = array();

		/**
		 * @var ECash_CFE_Agenda
		 */
		protected $agenda;

		/**
		 * @var ECash_CFE_IContext
		 */
		protected $context;

		/**
		 * Events waiting to be executed
		 *
		 * @var array
		 */
		protected $stack = array();

		/**
		 * The current execution backtrace
		 *
		 * @var array
		 */
		protected $trace = array();

		/**
		 * @var bool
		 */
		protected $in_execution = FALSE;

		/**
		 * Don't allow this to be instantiated; use ::getInstance()
		 */
		protected function __construct()
		{
			$this->agenda = new ECash_CFE_Agenda();
		}

		/**
		 * Sets the context instance that will be used
		 *
		 * @param ECash_CFE_IContext $c
		 */
		public function setContext(ECash_CFE_IContext $c)
		{
			$this->context = $c;
		}

		/**
		 * Sets the ruleset that will be used
		 *
		 * @param array $rules
		 */
		public function setRuleset(array $rules)
		{
			$this->ruleset = $rules;
		}

		/**
		 * Executes the rules for a given event and returns modified attributes
		 *
		 * If the engine is not currently in execution, executes actions for a
		 * specific event (which could trigger other events) and returns the
		 * attributes modified during execution. If the engine is executing, then
		 * the event is simply queued for execution and nothing is returned.
		 *
		 * @param string $event
		 * @param array $args
		 * @return array|void
		 */
		public function executeEvent($event, array $args = array(),$immediate = false)
		{
			if (isset($this->ruleset[$event])
				&& is_array($this->ruleset[$event]))
			{
				

				// if we're not currently in execution (in which case, the
				// rules we just added to the stack will get picked up
				// automatically), then start executing now!
				if (!$this->in_execution)
				{	
					$this->stack[] = array($event, $args, $this->trace);
					return $this->run();
				}
				elseif($immediate)
				{
					$new_engine = new self();
					$new_engine->setContext($this->context);
					$new_engine->setRuleset($this->ruleset);
					$new_engine->executeEvent($event, $args,false);
				}
				else
				{
					$this->stack[] = array($event, $args, $this->trace);
				}
			}
		}

		/**
		 * Executes the current rule stack and returns modified attributes
		 *
		 * This executes the current event and continues execution until no
		 * more events are on the stack. Any attributes that are modified
		 * during execution will be returned. This allows rules that set the
		 * fund amount, etc.
		 *
		 * @return array
		 */
		protected function run()
		{
			// this will track everything that's set during execution
			$copy = new ECash_CFE_CarbonContext($this->context);

			$this->in_execution = TRUE;

			while (count($this->stack))
			{
				// shift the next event off the stack
				list($event, $args, $trace) = array_shift($this->stack);
				$rules = $this->ruleset[$event];

				// set up the back trace and add the current event
				$this->trace = $trace;
				$this->trace[] = array($event, $args);

				$copy->pushVars($args);

				try
				{
					// NOTE: Execution continues until either a) no rules match, or
					// b) a BREAK is encountered. Huge potential for infinite loops!
					//Yes inded causing an infinite loop.
//					while ($this->evaluateRules($rules, $this->context) !== FALSE
//						&& $this->executeAgenda($copy) !== FALSE);
					
					$this->evaluateRules($rules, $copy);
					$this->executeAgenda($copy);
				}
				catch (ECash_CFE_RuntimeException $e)
				{
					// add the backtrace
					$e->setBacktrace($this->trace);
					throw $e;
				}

				$copy->popVars();
			}

			// reset everything for next time
			$this->in_execution = FALSE;
			$this->stack = array();
			$this->trace = array();

			// return everything that was modified
			return $copy->getCopy();
		}

		/**
		 * Evaluate all rules currently on the stack
		 * @return bool
		 */
		protected function evaluateRules(array $rules, ECash_CFE_IContext $c)
		{
			$matched = FALSE;

			// evaluate all rules, adding the matching ones to our agenda
			foreach ($rules as $rule)
			{
				try
				{
					$valid = $rule->isValid($c);
					if ($valid) $matched = TRUE;
				}
				catch (ECash_CFE_FatalException $e)
				{
					throw $e;
				}
				catch (ECash_CFE_RuntimeException $e)
				{
					$valid = FALSE;
				}

				if ($valid)
				{
					$this->scheduleRule($rule);
				}
			}

			return $matched;
		}

		/**
		 * Schedules a given rule on the agenda, respecting the ISchedulable interface
		 *
		 * @param ECash_CFE_IRule $rule
		 */
		protected function scheduleRule($rule)
		{
			// allow rules the option of scheduling themselves
			if ($rule instanceof ECash_CFE_ISchedulable)
			{
				$rule->addToAgenda($this->agenda);
			}
			else
			{
				$this->agenda->addAction($rule);
			}
		}

		/**
		 * Execute all actions currently on the agenda
		 *
		 * Execute currently scheduled actions and indicate whether rule evaluation
		 * should continue (i.e., whether a break was encountered).
		 *
		 * @return bool
		 */
		protected function executeAgenda(ECash_CFE_IContext $c)
		{
			foreach ($this->agenda as $action)
			{
				try
				{
					$action->execute($c);
				}
				catch (ECash_CFE_Break $e)
				{
					// we encountered a break, stop execution
					$this->agenda->clear();
					return FALSE;
				}
			}

			$this->agenda->clear();
			return TRUE;
		}
	}

?>
