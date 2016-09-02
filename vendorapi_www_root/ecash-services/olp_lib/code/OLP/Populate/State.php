<?php
/** Return a random US state.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_State extends OLP_Populate_PopulateItem
{
	/**
	 * @var array
	 */
	protected $states = array(
		'ALABAMA' => 'AL',
		'ALASKA' => 'AK',
		'ARIZONA' => 'AZ',
		'ARKANSAS' => 'AR',
		'CALIFORNIA' => 'CA',
		'COLORADO' => 'CO',
		'CONNECTICUT' => 'CT',
		'DELAWARE' => 'DE',
		'DISTRICT OF COLUMBIA' => 'DC',
		'FLORIDA' => 'FL',
		'GEORGIA' => 'GA',
		'HAWAII' => 'HI',
		'IDAHO' => 'ID',
		'ILLINOIS' => 'IL',
		'INDIANA' => 'IN',
		'IOWA' => 'IA',
		'KANSAS' => 'KS',
		'KENTUCKY' => 'KY',
		'LOUISIANA' => 'LA',
		'MAINE' => 'ME',
		'MARYLAND' => 'MD',
		'MASSACHUSETTS' => 'MA',
		'MICHIGAN' => 'MI',
		'MINNESOTA' => 'MN',
		'MISSISSIPPI' => 'MS',
		'MISSOURI' => 'MO',
		'MONTANA' => 'MT',
		'NEBRASKA' => 'NE',
		'NEVADA' => 'NV',
		'NEW HAMPSHIRE' => 'NH',
		'NEW JERSEY' => 'NJ',
		'NEW MEXICO' => 'NM',
		'NEW YORK' => 'NY',
		'NORTH CAROLINA' => 'NC',
		'NORTH DAKOTA' => 'ND',
		'OKLAHOMA' => 'OK',
		'OREGON' => 'OR',
		'PENNSYLVANIA' => 'PA',
		'RHODE ISLAND' => 'RI',
		'SOUTH CAROLINA' => 'SC',
		'SOUTH DAKOTA' => 'SD',
		'TENNESSEE' => 'TN',
		'TEXAS' => 'TX',
		'UTAH' => 'UT',
		'VERMONT' => 'VT',
		'VIRGINIA' => 'VA',
		'WASHINGTON' => 'WA',
		'WEST VIRGINIA' => 'WV',
		'WISCONSIN' => 'WI',
		'WYOMING' => 'WY',
	);
	
	/** Initializes class.
	 */
	public function __construct()
	{
		$this->data = array(
			'state' => '',
			'state_abbr' => '',
		);
	}
	
	/** Return a random US state abbrivation. Includes DC.
	 *
	 * @param mixed $min If passed, exclude these states.
	 * @param mixed $max Not used
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$states = array();
		
		if ($min)
		{
			if (!is_array($min)) $min = explode(',', $min);
			$exclude_states = array_map('strtoupper', $min);
			$exclude_states = array_map('trim', $min);
			$exclude_states = array_fill_keys($exclude_states, TRUE);
			
			foreach ($this->states AS $state => $state_abbr)
			{
				if (!isset($exclude_states[$state]) && !isset($exclude_states[$state_abbr]))
				{
					$states[$state] = $state_abbr;
				}
			}
		}
		else
		{
			$states = $this->states;
		}
		
		$this->state = array_rand($states);
		$this->state_abbr = $states[$this->state];
		
		return $this->state_abbr;
	}
	
	/**
	 * Returns the list of states in the form of: FULLNAME => ABBREVIATION
	 *
	 * @return array
	 */
	public function getStateList()
	{
		return $this->states;
	}
}

?>
