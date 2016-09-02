<?php

/**
 * A citems that can traverse the state data tree but will return the total combination of data for
 * the base branch to the tip
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_StateData_CombineKey implements Blackbox_StateData_ICombineKey
{
	/**
	 * Container array for data
	 * @var array
	 */
	protected $data;

	/**
	 * Constructor allows for optional initialization
	 *
	 * @param array $data
	 * @return void
	 */
	public function __construct(array $data = array())
	{
		$this->data = $data;
	}

	/**
	 * Combines this data items with another.
	 * @param Blackbox_StateData_ICombineKey $data Another combine key to combine
	 * entries with.
	 * @return OLPBlackbox_StateData_CombineKey
	 */
	public function combine(Blackbox_StateData_ICombineKey $other = NULL)
	{
		if ($other)
		{
			if (!$other instanceof OLPBlackbox_StateData_CombineKey)
			{
				throw new Blackbox_Exception(sprintf(
					'%s cannot be combined with %s',
					get_class($this),
					get_class($other))
				);
			}

			foreach ($other->getData() as $data)
			{
				$this->addDataItem($data);
			}
		}

		return $this;
	}

	/**
	 * Returns data
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Adds a data item to the array
	 *
	 * @param string $data_item
	 * @return void
	 */
	public function addDataItem($data_item)
	{
		$this->data[] = $data_item;
	}

	/**
	 * Adds an array of loan actions
	 *
	 * @param array $loan_actions
	 * @return void
	 */
	public function addData(array $data_items)
	{
		$this->data = array_merge($this->data, $data_items);
	}

	/**
	 * Display a pretty string for this class.
	 *
	 * @return string Blank if no loans found.
	 */
	public function __toString()
	{
		return implode("\n", $this->data);
	}
}

?>
