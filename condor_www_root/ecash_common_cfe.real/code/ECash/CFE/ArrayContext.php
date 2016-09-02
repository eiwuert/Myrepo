<?php

	class ECash_CFE_ArrayContext implements ECash_CFE_IContext
	{
		/**
		 * @var array
		 */
		protected $attrs = array();

		public function __construct(array $attrs)
		{
			$this->attrs = $attrs;
		}

		public function getAttribute($name)
		{
			return $this->attrs[$name];
		}

		public function setAttribute($name, $value)
		{
			$this->attr[$name] = $value;
		}
	}
			
?>
