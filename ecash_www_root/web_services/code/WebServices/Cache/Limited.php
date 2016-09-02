<?php

/**
 * I did some benchmarks against the parent cache vs this cache, and I found that
 * storing the index and checking the size adds about a 66% performance penalty
 * if you never hit the cache limit. Also, the larger the cache is the larger the
 * performance pentaly is if you have to expire anything. A 1,000,000 item cache
 * has the same 66% penalty at a million items as it does for 10, but a 500,000
 * limit cache takes a 350% penalty
 *
 * @author Asa Ayers <Asa.Ayers@SellingSource.com>
 */
class WebServices_Cache_Limited extends WebServices_Cache  {
    protected $max;

	protected $index = array();

	/**
	 * I'm maintaining the count in a variable instead of using count($this->index)
	 * because it is faster this way.
	 *
	 * @var int
	 */
	protected $count = 0;

	public function  __construct(Applog $log, $limit) {
		parent::__construct($log);
		$this->max = $limit;
	}

	/**
	 * Stores a call in the cach
	 *
	 * @param string $function
	 * @param string $id
	 * @param object $value
	 * @return void
	 */
	public function storeCache($function, $id, $value)
	{
		parent::storeCache($function, $id, $value);
		$this->index[] = "{$function},{$id}";
		$this->count++;

		if ($this->count > $this->max)
		{
			list($function, $id) = explode(',', array_shift($this->index));
			// because array_shift pulls the item out of the index, the parent
			// can be called to avoid trying to find the item in the index again.
			parent::removeCache($function, $id);
			$this->count--;
		}

	}
	/**
	 * Removes value from the cache
	 *
	 * @param string $function
	 * @param string $id
	 * @return void
	 */
	public function removeCache($function, $id)
	{
		parent::removeCache($function, $id);
		$this->count--;
		$idx = array_search("{$function},{$id}", $this->index);
		if (!is_null($idx))
		{
			unset($this->index[$idx]);
		}
	}
}
?>
