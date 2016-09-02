<?php

	class ECash_CFE_CompositeAction implements ECash_CFE_IAction
	{
		protected $actions;

		public function __construct(array $actions)
		{
			$this->actions = $actions;
		}

		// these are gay so far
		public function getType() {}
		public function getParameters() {}

		public function getActions()
		{
			return $this->actions;
		}

		public function execute(ECash_CFE_IContext $c)
		{
			foreach ($this->actions as $a)
			{
				try
				{
					$a->execute($c);
				}
				catch (ECash_CFE_FatalException $e)
				{
					// fatal exceptions must proceed up the stack
					throw $e;
				}
				catch (ECash_CFE_RuntimeException $e)
				{
					// non-fatal exceptions can be ignored
				}
			}
		}
	}

?>