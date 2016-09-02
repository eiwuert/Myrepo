<?php

class Date_PayDateNormalizer_1 extends Date_Normalizer_1
{
	protected $direct_deposit;

	public function __construct(Iterator $holiday_iterator, $direct_deposit = FALSE, $holiday_start_date = NULL)
	{
		parent::__construct($holiday_iterator, $holiday_start_date);
		$this->direct_deposit = $direct_deposit;
	}

	public function normalize($timestamp, $forward = TRUE)
	{
		//add a day if no direct deposit
		if (!$this->direct_deposit)
		{
			$timestamp = strtotime('+1 day', $timestamp);
		}

		return parent::normalize($timestamp, !$this->direct_deposit);
	}
}

?>
