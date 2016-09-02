<?php
	/**
	 * @package Unix
	 */

	/**
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	interface Unix_ISharedMemoryAccessor_1
	{
		/**
		 * Retrieve a variable from shared memory
		 *
		 * @param int $key
		 */
		public function get($key);
		
		/**
		 * Set a variable to a value in shared memory
		 *
		 * @param int $key
		 * @param mixed $data
		 */
		public function set($key, $data);
		
		/**
		 * Delete a variable from shared memory
		 *
		 * @param int $key
		 */
		public function delete($key);
	}
?>