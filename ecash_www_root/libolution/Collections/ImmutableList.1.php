<?php

/**
 * An Immutable list object to be initialized and read, but never changed.
 *
 * NOTE: any objects contained in your list will be cloned as they are
 * retrieved to prevent modifications to the originals.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Collections
 */
class Collections_ImmutableList_1 extends Collections_List_1
{
	/**
	 * Construct an immutable list from something which can be 'foreach'ed.
	 *
	 * @param array|Traversable
	 */
	final public function __construct($items = NULL)
	{
		if ($items === NULL) return;

		if (!$items instanceof Traversable
			&& !is_array($items))
		{
			throw new InvalidArgumentException(
				get_class($this).' must be constructed with array or Traversable'
			);
		}

		foreach ($items as $key => $value)
		{
			$this->items[$key] = $value;
		}
	}

	/**
	 * INVALID METHOD for an immutable list. Do not call.
	 *
	 * @throws BadMethodCallException
	 * @return void
	 */
	final public function add($item)
	{
		$class = get_class($this);
		throw new BadMethodCallException(
			"Cannot add item ".var_export($item, TRUE)." to {$class}, immutable"
		);
	}

	/**
	 * INVALID METHOD for an immutable list. Do not call.
	 *
	 * @throws BadMethodCallException
	 * @return void
	 */
	final public function clear()
	{
		throw new BadMethodCallException(
			'Cannot clear immutable list'
		);
	}

	/**
	 * INVALID METHOD for an immutable list. Do not call.
	 *
	 * @throws BadMethodCallException
	 * @return void
	 */
	final public function offsetSet($offset, $value)
	{
		$class = get_class($this);
		throw new BadMethodCallException(
			"Cannot set item {$offset} of {$class}, immutable"
		);
	}

	/**
	 * INVALID METHOD for an immutable list. Do not call.
	 *
	 * @throws BadMethodCallException
	 * @return void
	 */
	final public function offsetUnset($offset)
	{
		$class = get_class($this);
		throw new BadMethodCallException(
			"Cannot unset item {$offset} in {$class}, immutable"
		);
	}

	/**
	 * Returns the next item in the list. (objects returned are clones)
	 *
	 * @return mixed Next item
	 */
	final public function next()
	{
		$next = parent::next();

		if (is_object($next))
		{
			return clone $next;
		}

		return $next;
	}

	/**
	 * Returns the current item for this list. (objects returned are clones)
	 * @return mixed
	 */
	final public function current()
	{
		$current = parent::current();

		if (is_object($current))
		{
			return clone $current;
		}

		return $current;
	}
}

?>
