<?php
/**
 * Calculates Pay Dates given a pattern in the form of a PayDateModel
 *
 * @author Justin Foell
 * @package Date
 */
class Date_PayDateCalculator_1 extends Object_1 implements Iterator
{
	protected $model;
	
	private $date_normalizer;
	private $last_paydate;
	private $paydate_cache;
	private $paydate_key;

	/**
	 * NOTE: it's up to the developer to insure the holiday_iterator's first holiday somewhat coincides
	 * with the start_date (if specified)
	 * 
	 * @param Date_PayDateModel_1 $model a paydate model obtained from Date_PayDateModel_1::getModel()
	 * @param ArrayIterator $holiday_iterator holiday array in the format of "YYYY-MM-DD" wrapped in a ArrayIterator usually by new Date_BankHolidays_1() or new ArrayIterator($holiday_array)
	 * @param int $start_date unix timestamp of where you want the list to start
	 *
	 */
	public function __construct(Date_PayDateModel_1 $model, Date_PayDateNormalizer_1 $date_normalizer, $start_date = NULL)
	{
		$this->model = $model;
		$this->date_normalizer = $date_normalizer;

		if($start_date === NULL)
		{
			$start_date = time();
		}
		
		//this will set the start date, clear the cache, & get the first element -- incase the developer tries to call next(), rewind(), current()
		//do everything that setStartDate does, minus resetting the holiday_cache
		$this->last_paydate = $this->model->LastPayDate ? $this->model->LastPayDate : $start_date;
		$this->rewind();
		$this->current();		
	}

	/**
	 * Gets an array of pay dates based on the start_date (passed to constructor) and end_date
	 *
	 * If no start_date or end_date specified, defaults starting today until one year from today
	 *
	 * @param int $end_date unix timestamp to end at (default now +1 year)
	 * @return Array paydates for date range
	 */
	public function getPayDateArray($end_date = NULL)
	{
		$end_date = $end_date ? $end_date : strtotime('+1 year', $this->last_paydate);

		if(!$this->last_paydate || $end_date < $this->last_paydate)
			throw new Exception('start_date must be set to a valid timestamp and must be before end_date');

		$this->rewind();

		$pay_dates = array();

		$pay_date = $this->current();

		while($pay_date <= $end_date)
		{
			$pay_dates[] = $pay_date;
			$this->next();
			$pay_date = $this->current();
		}

		$this->rewind();
		return $pay_dates;
	}
	
	/**
	 *  Calling setStartDate after construction (or use) essentially
	 *  clears all dates and resets the object to start over.
	 */ 
	public function setStartDate($timestamp)
	{
		$this->last_paydate = $this->model->LastPayDate ? $this->model->LastPayDate : $timestamp;
		$this->date_normalizer->reset();
		$this->rewind();
		$this->current();
	}

	private function getPayDate()
	{
		if(!isset($this->paydate_cache[$this->paydate_key]))
		{
			/**
			 * To explain what's going on here:
			 *
			 * If start_date (seen here as last_paydate since we have
			 * yet to get the first paydate) is Friday, Feb. 1st,
			 * 2008, and the customer has no direct deposit, their
			 * next *normalized* paydate will be Monday, Feb. 4th.
			 * Since the paydate model is asking for the 'next' 1st of
			 * the month, it will normally return Mar. 1st.  To insure
			 * we're not missing a date that would be normalized to
			 * the future (Feb. 4th), go back one day so the 'next'
			 * payday could still be 'today', normalize that and see
			 * if it's greater then the start date. [JustinF]
			 */
			$preloaded = FALSE;
			if(empty($this->paydate_cache))
			{
				$first_paydate = $this->model->nextPayDate(strtotime('-1 day', $this->last_paydate));
				$first_normalized_paydate = $this->date_normalizer->normalize($first_paydate);				
				if($first_normalized_paydate > $this->last_paydate)
				{
					$this->last_paydate = $first_paydate;
					$this->paydate_cache[$this->paydate_key] = $first_normalized_paydate;
					$preloaded = TRUE;						
				}
			}
			
			if(!$preloaded)
			{
				$this->last_paydate = $this->model->nextPayDate($this->last_paydate);
				$this->paydate_cache[$this->paydate_key] = $this->date_normalizer->normalize($this->last_paydate);
			}
		}
		return $this->paydate_cache[$this->paydate_key];
	}

	public function rewind()
	{
		$this->paydate_key = 0;
	}

	public function current()
	{
		return $this->getPayDate();
	}

	public function next()
	{
		$this->paydate_key++;
		return $this->getPayDate();
	}

	public function key()
	{
		return $this->paydate_key;
	}

	public function valid()
	{
		return TRUE;
	}
}

?>
