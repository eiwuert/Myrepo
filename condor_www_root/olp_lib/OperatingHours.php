<?php
/**
 * Defines the OperatingHours class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * This class allows you to specify different operating hours for each day of
 * the week.  You can also setup multiple operating hour blocks for each day
 * so if your operating hours are 8-11 and 12-4 on monday, you can specify that.
 * You can also override normal day of the week business hours with different
 * hours on specific dates, such as holidays, etc.
 *
 * array(
 * 	'day_of_week'=>array(
 * 		'mon'=>array(
 *			array( // First set of hours for monday
 * 				'start'=>H:i // Note, 24 hour format expected!
 * 				,'end'=>H:i
 * 			)
  *			,array( // Second set of hours for monday
 * 				'start'=>H:i
 * 				,'end'=>H:i
 * 			)
 * 		)
 * 		,'tues'=>array(
 * 			'start'=>H:i
 * 			,'end'=>H:i
 * 		)
 * 		//,... // Rest of days...
 * 	)
 * 	,'date'=>array(
 * 		'YYYY-MM-DD'=>array(
 * 			'start'=>H:i
 * 			,'end'=>H:i
 * 		)
 * 		//,... // Any additional dates with specific hours...
 * 	)
 * )
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OperatingHours
{
	/**
	 * Array with operating hours for each day of the week
	 *
	 * @var array
	 */
	protected $day_of_week;
	
	/**
	 * Array with operating hours for specific dates
	 *
	 * @var array
	 */
	protected $date;
	
	/**
	 * Adds operating hours for a specific day of the week.
	 * 
	 * @param string $day_of_week Abbreviation of the day for the operating hours.
	 * @param string $start The 24 hour formatted HH:MM start time.
	 * @param string $end The 24 hour formatted HH:MM end time.
	 *
	 * @return void
	 */
	public function addDayOfWeekHours($day_of_week, $start, $end)
	{
		$this->day_of_week[strtolower($day_of_week)][] = array('start'=>$start, 'end'=>$end);
	}
	
	/**
	 * Adds operating hours for a specific date.
	 * 
	 * @param string $date String with the date for the operating hours.
	 * @param string $start The 24 hour formatted HH:MM start time.
	 * @param string $end The 24 hour formatted HH:MM end time.
	 *
	 * @return void
	 */
	public function addDateHours($date, $start, $end)
	{
		$this->date[$date][] = array('start'=>$start, 'end'=>$end);
	}
	
	/**
	 * Gets the operating hours for a specific day of the week.
	 * 
	 * @param string $day_of_week The day of the week you want the operating hours for
	 *
	 * @return array
	 */
	public function getDayOfWeekHours($day_of_week)
	{
		return $this->day_of_week[strtolower($day_of_week)];
	}
	
	/**
	 * Gets the operating hours for a specific date.
	 * 
	 * @param string $date The date you want the operating hours for
	 *
	 * @return array
	 */
	public function getDateHours($date)
	{
		return $this->date[$date];
	}
}

?>