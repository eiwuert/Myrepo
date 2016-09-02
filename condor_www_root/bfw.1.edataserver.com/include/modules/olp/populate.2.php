<?php
/** Class to populate common OLP data values.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */

/** Static class to populate common OLP data values.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate
{
	/** Populate common OLP data values.
	 *
	 * @return array
	 */
	public static function Get_Random_Record($sql, $applog, $mode)
	{
		$number = new Populate_Item_Number();
		$word = new Populate_Item_Word();
		$name = new Populate_Item_Word(dirname(__FILE__) . '/names.txt');
		$address = new Populate_Item_Address();
		$phone = new Populate_Item_Phonenumber();
		$extension = new Populate_Item_Sometimes(new Populate_Item_Number(4), 0.333, '');
		$email = new Populate_Item_Email();
		$relationship = new Populate_Item_Array(array('parent', 'sibling', 'friend', 'Co-Worker', 'extended_family')); // Source: tss.2.shared.code/smt/master.build.tokens.php
		$aba = new Populate_Item_ABA();
		$call_time = new Populate_Item_Array(array('MORNING', 'AFTERNOON', 'EVENING'));
		$date = new Populate_Item_Date();
		$ssn = new Populate_Item_SSN();
		$boolean = new Populate_Item_Array(array('TRUE', 'FALSE'));
		$incometype = new Populate_Item_Array(array('BENEFITS', 'EMPLOYMENT'));
		$payfrequency = new Populate_Item_Array(array('WEEKLY', 'BI_WEEKLY', 'TWICE_MONTHLY', 'MONTHLY'));
		
		// Randomize the values that get split
		$date->getRandomItem('-20 year', '-60 year');
		$ssn->getRandomItem();
		$address->getRandomItem();
		
		// Fill in our array
		$data = array(
			'name_first' => $name->getRandomItem() . '_TSSTEST',
			'name_last' => $name->getRandomItem() . '_TSSTEST',
			'email_primary' => $email->getRandomItem(),
			'phone_home' => $phone->getRandomItem(),
			'phone_work' => $phone->getRandomItem(),
			'phone_cell' => $phone->getRandomItem(),
			'ext_work' => $extension->getRandomItem(0, 9999),
			'best_call_time' => $call_time->getRandomItem(),
			'date_dob_y' => $date->year,
			'date_dob_m' => $date->month,
			'date_dob_d' => $date->day,
			'ssn_part_1' => $ssn->ssn_1,
			'ssn_part_2' => $ssn->ssn_2,
			'ssn_part_3' => $ssn->ssn_3,
			'home_street' => $address->street,
			'home_city' => $address->city,
			'home_state' => $address->state_abbr,
			'home_zip' => $address->zip_code,
			'employer_name' => $word->getRandomItem() . ' ' . $word->getRandomItem(),
			'state_id_number' => $number->getRandomItem(10000000, 99999999),
			'state_issued_id' => $address->state_abbr,
			'income_direct_deposit' => $boolean->getRandomItem(),
			'income_type' => $incometype->getRandomItem(),
			'income_frequency' => $payfrequency->getRandomItem(),
			'income_monthly_net' => $number->getRandomItem(6000, 6999),
			'bank_name' => 'BANK OF ' . $word->getRandomItem(),
			'bank_aba' => $aba->getRandomItem(TRUE),
			'bank_account' => $number->getRandomItem(1000, 99999999),
			'ref_01_name_full' => $name->getRandomItem() . '_TSSTEST ' . $name->getRandomItem() . '_TSSTEST',
			'ref_01_phone_home' => $phone->getRandomItem(),
			'ref_01_relationship' => $relationship->getRandomItem(),
			'ref_02_name_full' => $name->getRandomItem() . '_TSSTEST ' . $name->getRandomItem() . '_TSSTEST',
			'ref_02_phone_home' => $phone->getRandomItem(),
			'ref_02_relationship' => $relationship->getRandomItem(),
			'legal_notice_1' => 'TRUE',
			'offers' => 'FALSE',
			'mh_offer' => 'FALSE',
			'paydate' => array(
				'frequency' => 'WEEKLY',
				'weekly_day' => 'MON',
			),
			'bank_account_type' => 'CHECKING',
			'legal_approve_docs_1' => 'checked',
			'legal_approve_docs_2' => 'checked',
			'legal_approve_docs_3' => 'checked',
			'legal_approve_docs_4' => 'checked',
			'military' => 'FALSE',
			'cali_agree' => $address->state_abbr == 'CA' ? 'agree' : '',
		);
		
		return $data;
	}
}

/** Common interface for populate items. Does magic with $data.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class Populate_Item
{
	protected $data = array();
	
	public function __get($name)
	{
		if (isset($this->data[$name]))
			return $this->data[$name];
	}
	
	protected function __set($name, $value)
	{
		if (isset($this->data[$name]))
			$this->data[$name] = $value;
	}
	
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}
	
	/** Return a random item.
	 *
	 * @return mixed
	 */
	abstract public function getRandomItem($min = NULL, $max = NULL);
}

/** Wrapper class to sometimes return random data.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_Sometimes extends Populate_Item
{
	protected $subclass;
	protected $percent_happens;
	protected $default_value;
	
	/** Pass in a Populate_Item and how often you want it to return data.
	 * Default value is what it will return otherwise. Percent_happens is
	 * a decimal between 0 (0%) and 1 (100%).
	 */
	public function __construct(Populate_Item $subclass, $percent_happens = 0.5, $default_value = '')
	{
		$this->data = array(
			'random_picked' => FALSE,
			'random_value' => $default_value,
		);
		
		$this->subclass = $subclass;
		$this->percent_happens = $percent_happens;
		$this->default_value = $default_value;
	}
	
	/** Randomly pick random item. Min/max get passed to subobject.
	 *
	 * @return mixed
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->random_picked = mt_rand() / mt_getrandmax() < $this->percent_happens;
		
		if ($this->random_picked)
		{
			$this->random_value = $this->subclass->getRandomItem($min, $max);
		}
		else
		{
			$this->random_value = $this->default_value;
		}
		
		return $this->random_value;
	}
	
	/** Magic magic getter. If we didn't pick the subobject, return default
	 * values instead of what the item would normally return. Otherwise,
	 * return what the subobject has.
	 */
	public function __get($name)
	{
		if (isset($this->data[$name]))
			return $this->data[$name];
		elseif ($this->random_picked)
		{
			return $this->subclass->{$name};
		}
		elseif (isset($this->subclass->{$name}))
		{
			return $this->default_value;
		}
	}
}

/** Generate random numbers.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_Number extends Populate_Item
{
	protected $pad_length;
	
	/** If you want 0's padded up front to make a specific length,
	 * pass in the string length you wish here.
	 */
	public function __construct($pad_length = 0)
	{
		$this->data = array(
			'number' => 0,
		);
		
		$this->pad_length = $pad_length;
	}
	
	/** Generate a random number between min/max. Defaults to full
	 * random range. If min is set but max isn't, does range between
	 * 0 and min.
	 *
	 * @return mixed If padding, may be string. Otherwise, integer.
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		// mt_rand handles NULLs for us.
		$this->number = mt_rand($min, $max);
		
		if ($this->pad_length)
		{
			$this->number = str_pad($this->number, $this->pad_length, '0', STR_PAD_LEFT);
		}
		
		return $this->number;
	}
}

/** Generate random words.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_Word extends Populate_Item
{
	protected $valid_characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	protected $file_source;
	
	/** File source is a file that contains a word per line. Defaults
	 * to the unix path for dictionary words.
	 */
	public function __construct($file_source = '/usr/share/dict/words')
	{
		$this->data = array(
			'word' => '',
			'source' => 'unknown',
		);
		
		$this->file_source = $file_source;
	}
	
	/** Returns a random word between MIN/MAX length. Defaults to 3 - 10.
	 * If cannot randomly pick a real word, returns a randomly generated
	 * string instead.
	 *
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		// Initialize values
		if (is_null($min)) $min = 3;
		if (is_null($max)) $max = 10;
		
		// Try getting a dictionary word first
		$this->word = $this->getDictionaryWord($min, $max);
		$this->source = 'dictionary';
		
		// If that failed, just generate purely random string
		if (!$this->word)
		{
			$this->word = $this->getRandomWord($min, $max);
			$this->source = 'random';
		}
		
		return $this->word;
	}
	
	/** Get a random word from /usr/share/dict/words. Verifies that word does
	 * not contain non-standard characters. Converts the word into uppercase.
	 *
	 * NOTE: This code is designed to not need to read in the whole file. That
	 * means that we will never read the first line, and word's chances of
	 * being picked depend on how big the word before them is. But it can
	 * handle files of huge lengths.
	 *
	 * Can fail to return a string if you ask for a length that is not very
	 * popular in the file. Will try X number of random words until it fails.
	 *
	 * @return string or FALSE if failed to find one in time.
	 */
	protected function getDictionaryWord($min, $max)
	{
		$handle = fopen($this->file_source, 'r');
		$tries = 15; // How many times we should try before we give up
		$word = FALSE;
		
		if ($handle)
		{
			// Get max file length
			fseek($handle, 0, SEEK_END);
			$length = ftell($handle);
			
			do
			{
				// Go to a random location in the file
				fseek($handle, mt_rand(0, $length), SEEK_SET);
				
				// Ignore the first line, as we may be in a middle of a word.
				fgets($handle);
				
				// Read in the word. If at end of file, will fail preg_match and try again
				$word = strtoupper(rtrim(fgets($handle)));
				if (preg_match('/^[' . preg_quote($this->valid_characters) . ']{'.$min.','.$max.'}$/', $word) == 1)
				{
					break;
				}
				else
				{
					$word = FALSE;
				}
			} while (--$tries);
			
			fclose($handle);
		}
		
		return $word;
	}
	
	/** Generate a random word, all uppercase.
	 *
	 * @return string
	 */
	protected function getRandomWord($min, $max)
	{
		$valid_length = strlen($this->valid_characters) - 1;
		$word = '';
		
		for ($length = mt_rand($min, $max); $length; $length--)
		{
			$word .= substr($this->valid_characters, mt_rand(0, $valid_length), 1);
		}
		
		return $word;
	}
}

/** Return a random US state.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_State extends Populate_Item
{
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
		'OHIO' => 'OH',
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
	
	public function __construct()
	{
		$this->data = array(
			'state' => '',
			'state_abbr' => '',
		);
	}
	
	/** Return a random US state abbrivation. Includes DC.
	 *
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->state = array_rand($this->states);
		$this->state_abbr = $this->states[$this->state];
		
		return $this->state_abbr;
	}
}

/** Return a random street suffix.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_AddressSuffix extends Populate_Item
{
	protected $addresssuffix = array(
		'STREET' => 'ST',
		'DRIVE' => 'DR',
		'LANE' => 'LN',
		'CIRCLE' => 'CR',
	);
	
	public function __construct()
	{
		$this->data = array(
			'suffix' => '',
			'suffix_abbr' => '',
		);
	}
	
	/** Returns a random street suffix.
	 *
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->suffix = array_rand($this->addresssuffix);
		$this->suffix_abbr = $this->addresssuffix[$this->suffix];
		
		return $this->suffix_abbr;
	}
}

/** Generate a random, fake address.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_Address extends Populate_Item
{
	protected $random_number;
	protected $random_suffix;
	protected $random_state;
	protected $random_word;
	
	public function __construct()
	{
		$this->data = array(
			'street' => '',
			'city' => '',
			'state' => '',
			'state_abbr' => '',
			'zip_code' => 0,
			
			'address' => '', // All merged into one line
		);
		
		$this->random_number = new Populate_Item_Number();
		$this->random_suffix = new Populate_Item_AddressSuffix();
		$this->random_state = new Populate_Item_State();
		$this->random_word = new Populate_Item_Word();
	}
	
	/** Generate a random, fake address.
	 *
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->street = $this->random_number->getRandomItem(100, 20000) . ' ' .
			$this->random_word->getRandomItem(4, 20) . ' ' .
			$this->random_suffix->getRandomItem();
		$this->city = $this->random_word->getRandomItem(5, 20);
		$this->state_abbr = $this->random_state->getRandomItem();
		$this->state = $this->random_state->state;
		$this->zip_code = $this->random_number->getRandomItem(10000, 99999);
		
		$this->address = "{$this->street}, {$this->city}, {$this->state_abbr} {$this->zip_code}";
		
		return $this->address;
	}
}

/** Generate a random, fake phone number.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_Phonenumber
{
	protected $random_number_pad3;
	protected $random_number_pad4;
	
	public function __construct()
	{
		$this->data = array(
			'divider' => '-',
			
			'area_code' => 0,
			'local_a' => 0,
			'local_b' => 0,
			'local' => '',
			
			'phone_number' => '',
		);
		
		$this->random_number_pad3 = new Populate_Item_Number(3);
		$this->random_number_pad4 = new Populate_Item_Number(4);
	}
	
	/** Generate a random, fake phone number.
	 *
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->area_code = $this->random_number_pad3->getRandomItem(200, 999);
		$this->local_a = $this->random_number_pad3->getRandomItem(555, 555);
		$this->local_b = $this->random_number_pad4->getRandomItem(0, 9999);
		
		$this->local = $this->local_a . $this->divider . $this->local_b;
		$this->phone_number = $this->area_code . $this->divider . $this->local;
		
		return $this->phone_number;
	}
}

/** Generate a random, real email address.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_Email extends Populate_Item
{
	protected $random_number;
	
	public function __construct()
	{
		$this->data = array(
			'email' => '',
		);
		
		$this->random_number = new Populate_Item_Number();
	}
	
	/** Generate a random, fake phone number.
	 *
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->email = $this->random_number->getRandomItem(1000, 99999999) . '@tssmasterd.com';
		
		return $this->email;
	}
}

/** Select randomly from a simple array.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_Array extends Populate_Item
{
	protected $source_array;
	
	public function __construct(array $source_array)
	{
		$this->data = array(
			'key' => 0,
			'value' => '',
		);
		
		$this->source_array = $source_array;
	}
	
	/** Return a random value from the array.
	 *
	 * @return mixed
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->key = array_rand($this->source_array);
		$this->value = $this->source_array[$this->key];
		
		return $this->value;
	}
}

/** Generate a random Bank ABA that passes checksum.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_ABA extends Populate_Item_Number
{
	protected $real_list;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->data['aba'] = 0;
		
		// Because we sometimes need real ones. Randomly picked from real list.
		// Source: https://www.fededirectory.frb.org/fpddir.txt
		$this->real_list = new Populate_Item_Array(array(
			'011000028', '011300142', '011601142', '021206469', '031100102',
			'031101208', '031318499', '041208421', '041208735', '041215553',
			'042101268', '042102270', '044114716', '051501257', '052100466',
			'052100741', '052202225', '053105936', '053112482', '053201872',
			'056008849', '061101197', '061104246', '061202025', '061202957',
			'062201711', '063104626', '063113811', '064107994', '064202763',
			'064207946', '065200528', '071001180', '071105536', '071208297',
			'071905817', '071905930', '071909363', '071925538', '071925923',
			'071925965', '073903503', '073913836', '073913959', '073918077',
			'073922869', '075908001', '081000553', '081206328', '081501227',
			'081507166', '081517729', '082904373', '082905343', '084107699',
			'084301408', '086503424', '091008299', '091200848', '091204080',
			'091300641', '091803274', '091809980', '091901192', '096010415',
			'101102182', '101203256', '104113343', '104900750', '104901241',
			'111909210', '111916724', '112323387', '113000049', '113005549',
			'113008083', '113118630', '121141615', '122038691', '122216439',
			'122239270', '122243855', '123205054', '124103582', '211288640',
			'211370626', '211372378', '211770200', '211870142', '221371563',
			'243374221', '253171621', '256078514', '263184488', '265472415',
			'271070814', '275071330', '303085227', '303085476', '311376850',
		));
	}
	
	/** Generate a random ABA with valid checksum.
	 *
	 * @param $min If TRUE, returns a real ABA from list.
	 * @return integer
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		if (is_null($min))
		{
			return $this->generateRandom();
		}
		else
		{
			return $this->real_list->getRandomItem();
		}
	}
	
	protected function generateRandom()
	{
		$this->pad_length = 2;
		switch (mt_rand(1, 3))
		{
			case 1:
				$this->aba = parent::getRandomItem(0, 12);
				break;
			case 2:
				$this->aba = parent::getRandomItem(21, 32);
				break;
			case 3:
				$this->aba = parent::getRandomItem(61, 72);
				break;
		}
		
		$this->pad_length = 6;
		$this->aba .= parent::getRandomItem(1, str_repeat('9', $this->pad_length));
		
		// Determine checksum digit
		$checksum =
			(10 -
				(
					3 * ($this->aba[0] + $this->aba[3] + $this->aba[6]) +
					7 * ($this->aba[1] + $this->aba[4] + $this->aba[7]) +
					1 * ($this->aba[2] + $this->aba[5])
				) % 10
			) % 10;
		
		// Checksum is the ninth digit
		$this->aba .= $checksum;
		
		return $this->aba;
	}
}

/** Generate a random date.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_Date extends Populate_Item
{
	protected $format;
	
	/** Pass in the requested string format for the date.
	 */
	public function __construct($format = 'Y-m-d')
	{
		$this->data = array(
			'timestamp' => 0,
			'date' => '',
			
			'year' => 0,
			'month' => 0,
			'day' => 0,
		);
		
		$this->format = $format;
	}
	
	/** Return a random date between MIN/MAX. Defaults to between
	 * today and 50 years ago. Uses strtotime to parse inputs.
	 *
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		// Setup defaults
		if (is_null($min)) $min = time();
		if (is_null($max)) $max = strtotime('-50 year');
		
		// Convert non-timestamps to timestamps
		if (!is_int($min)) $min = strtotime($min);
		if (!is_int($max)) $max = strtotime($max);
		
		if ($min > $max)
		{
			$this->timestamp = mt_rand($max, $min);
		}
		else
		{
			$this->timestamp = mt_rand($min, $max);
		}
		
		$this->date = date($format, $this->timestamp);
		$this->year = date('Y', $this->timestamp);
		$this->month = date('m', $this->timestamp);
		$this->day = date('d', $this->timestamp);
		
		return $this->date;
	}
}

/** Generate a random, fake social security number.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Populate_Item_SSN extends Populate_Item_Number
{
	public function __construct()
	{
		parent::__construct(2);
		
		$this->data = array_merge($this->data, array(
			'ssn_1' => '',
			'ssn_2' => '',
			'ssn_3' => '',
			'ssn' => '',
		));
	}
	
	/** Generate a random, fake social security number.
	 *
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		$this->ssn_1 = '8' . parent::getRandomItem(0, 99);
		$this->ssn_2 = parent::getRandomItem(1, 99);
		$this->ssn_3 = parent::getRandomItem(1000, 9999);
		
		$this->ssn = "{$this->ssn_1}-{$this->ssn_2}-{$this->ssn_3}";
		
		return $this->ssn;
	}
}

?>
