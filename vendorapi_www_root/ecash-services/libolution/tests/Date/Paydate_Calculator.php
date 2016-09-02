<?php

class Paydate_Calculator
{
	public function Calculate_Due_Date($fund_day, $holiday_array, $pay_span, $pay_day, $direct_deposit)
	{
		//do this because andrew's test is stupid
		sort($holiday_array);

		$calc = new Date_PayDateCalculator_1($this->getModel($pay_span, $pay_day),
												new ChallengeNormalizer(new ArrayIterator($holiday_array), $direct_deposit),
												$pay_day);

		$ten_after_fund = strtotime('+10 days', $fund_day);
		//echo "\nfund_day: ", date('Y-m-d', $fund_day), " (+10): ", date('Y-m-d', $ten_after_fund), " pay_day; ", date('Y-m-d', $pay_day), "\n";

		$next_paydate = $calc->current();
		while($next_paydate < $ten_after_fund)
		{
			$calc->next();
			$next_paydate = $calc->current();
		}
		return $next_paydate;
	}

	private function getModel($pay_span, $pay_day)
	{
		switch(strtoupper($pay_span))
		{
			case 'WEEKLY':
				$type = Date_PayDateModel_1::WEEKLY_ON_DAY;
				break;

			case 'BI-WEEKLY':
				$type = Date_PayDateModel_1::EVERY_OTHER_WEEK_ON_DAY;
				break;

			case 'MONTHLY':
			default:
				$type = Date_PayDateModel_1::MONTHLY_ON_DAY;
				break;
		}
		return Date_PayDateModel_1::getModel($type,  strtolower(date('l', $pay_day)), $pay_day, idate('d', $pay_day));
	}
}

class ChallengeNormalizer extends Date_PayDateNormalizer_1
{
	private $loop_type_forward = TRUE; //TRUE = forward, FALSE = reverse

	public function normalize($timestamp)
	{
		//add a day if no direct deposit
		if (!$this->direct_deposit)
		{
			$timestamp = strtotime('+1 day', $timestamp);
		}

		//skip forward or back if on a weekend or holiday, depending on loop type
		while($this->isWeekend($timestamp) || $this->isHoliday($timestamp))
		{
			if($this->isHoliday($timestamp))
			{
				$this->loop_type_forward = FALSE;
			}

			$timestamp = $this->loop_type_forward ? strtotime('+1 day', $timestamp): strtotime('-1 day', $timestamp);
		}
		//reset this for next time 'round
		$this->loop_type_forward = TRUE;

		//echo "\nNext Normalized Paydate: ", date('Y-m-d', $timestamp), "\n";
		return $timestamp;
	}
}

?>
