<?php
	/**
	 * @package Unix
	 */

	/**
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class Unix_IPCKey_1 extends Object_1 
	{
		/**
		 * file system path for the key
		 * @var string
		 */
		protected $path;
		
		/**
		 * project identifier for system v ipc key
		 * @var char
		 */
		protected $project_identifier = 'a';
		
		/**
		 * cached result of ftok()
		 * @var resource
		 */
		protected $key = NULL;
		
		/**
		 * @param string $path
		 * @param char $project_identifier
		 */
		public function __construct($path, $project_identifier = 'a')
		{
			$this->path = $path;
			$this->project_identifier = $project_identifier;
		}
		
		/**
		 * used to make first call to ftok()
		 */
		protected function setupKey()
		{
			if (!file_exists($this->path))
			{
				if (!touch($this->path))
				{
					throw new Unix_IPCKeyException_1("The path could not be verified.");
				}
			}
			
			$this->key = ftok($this->path, $this->project_identifier);
		}

		/**
		 * Returns the valid system V IPC Key resource
		 * 
		 * @return resource
		 */
		public function getKey()
		{
			if ($this->key === NULL)
			{
				$this->setupKey();
			}
			return $this->key;
		}
	}
?>