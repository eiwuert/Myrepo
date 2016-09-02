<?php
	abstract class SourcePro_Prpc_Utility_Base
	{
		protected $m_result;
		
		public function __construct () 
		{
		}
		
		public function __destruct ()
		{
		}
		
		public function __get ($var)
		{
			return $this->m_result;
		}
			
		public function __set ($var, $val)
		{
			throw new SourcePro_Exception ("Cannot set a {$var} to {$val}", 1000);
		}
			
		public function __call ($method, $args)
		{
			throw new SourcePro_Exception ("Cannot call a {$method} with args".var_dump($args), 1000);
		}		
	}
?>