<?php

/**
 * A wrapper around TSS_SuppressionList_1 that attaches some additional data
 *
 * @author Mike Lively <mike.lively@sellingsource.com
 */
class VendorAPI_SuppressionList_Wrapper implements TSS_ISuppressionList_1
{
	/**
	 * @var TSS_SuppressionList_1
	 */
	private $inner_list;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $loan_action;

	/**
	 * @var int
	 */
	private $list_id;

	/**
	 * @var string
	 */
	private $field;

	/**
	 * @param TSS_SuppressionList_1 $inner_list The list that is being wrapped.
	 * @param string $name
	 * @param string $loan_action
	 * @param string $field
	 * @param int $list_id
	 */
	public function __construct(TSS_ISuppressionList_1 $inner_list, $name, $loan_action, $field, $list_id)
	{
		$this->inner_list = $inner_list;
		$this->name = $name;
		$this->loan_action = $loan_action;
		$this->field = $field;
		$this->list_id = $list_id;
	}

	/**
	 * Matches $value against the suppression list and returns TRUE if there is a match.
	 *
	 * @param string $value
	 * @return bool
	 */
	public function match($value)
	{
		return $this->inner_list->match($value);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getLoanAction()
	{
		return $this->loan_action;
	}

	/**
	 * @return string
	 */
	public function getField()
	{
		return $this->field;
	}

	/**
	 * @return int
	 */
	public function getListId()
	{
		return $this->list_id;
	}

        /**
	 * @return array
	 */
	public function getInnerList()
	{
		return $this->inner_list;
	}
}